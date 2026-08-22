<?php

class SalesModel
{
    private $con;

    public function __construct()
    {
        global $con;
        $this->con = $con;
    }

    public function getStockists($mr_id)
    {
        $status = 1;
        $stmt = $this->con->prepare("
            SELECT 
                s.stockist_id,
                s.stockist_name,
                h.hq_name
            FROM stockists s
            INNER JOIN mr_users m ON m.hq_id = s.hq_id
            LEFT JOIN headquarter h ON h.headquarter_id = s.hq_id
            WHERE m.m_id = ? AND s.status = ?
            ORDER BY s.stockist_name ASC
        ");

        $stmt->bind_param("ii", $mr_id, $status);
        $stmt->execute();

        $result = $stmt->get_result();
        $stockists = [];

        while ($row = $result->fetch_assoc()) {
            $stockists[] = $row;
        }

        $stmt->close();
        return $stockists;
    }

    public function getCustomer($mr_id, $type = '')
    {
        $status = 1;

        if (!empty($type)) {
            $stmt = $this->con->prepare("
                SELECT
                    c_id,
                    customer_name
                FROM customers
                WHERE hq_id = ?
                AND customer_type = ?
                AND status = ?
                ORDER BY customer_name
            ");
            $stmt->bind_param("isi", $mr_id, $type, $status);
        } else {
            $stmt = $this->con->prepare("
                SELECT
                    c_id,
                    customer_name
                FROM customers
                WHERE hq_id = ?
                AND status = ?
                ORDER BY customer_name
            ");
            $stmt->bind_param("ii", $mr_id, $status);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $customers = [];

        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }

        $stmt->close();
        return $customers;
    }

    // ------------------------------------------------------------------------
    // Merged Search: Groups batches together so the UI just shows the Product
    // ------------------------------------------------------------------------
  public function searchMedicine($stockist_id, $q = '')
    {
        if (!$stockist_id) {
            return [];
        }

        $stmt = $this->con->prepare("
            SELECT 
                p.p_id,
                p.product_name,
                MAX(pb.mrp) AS mrp,
                
                -- Calculate PTS (Sale Rate - Discount %) safely
                MAX((
                    SELECT ROUND((sid.rate - ((sid.rate * COALESCE(sid.discount_percent, 0)) / 100)), 2) 
                    FROM `stock_inward_details` sid
                    WHERE sid.batch_id = pb.batch_no
                    LIMIT 1
                )) AS pts,

                -- Calculate accurate stock (IN - OUT) 
                SUM(COALESCE(sl.qty_in, 0) - COALESCE(sl.qty_out, 0)) AS total_stock
                
            FROM stock_ledger sl
            INNER JOIN products p ON p.p_id = sl.p_id
            INNER JOIN product_batches pb ON pb.batch_id = sl.batch_id
            
            -- THE FIX: Use LEFT JOIN to connect the exact order for each ledger row
            LEFT JOIN stock_inward si ON si.inward_id = sl.reference_id AND sl.reference_table = 'stock_inward'
            LEFT JOIN orders o ON o.order_id = si.order_id
            
            WHERE sl.stockist_id = ? 
                AND sl.stockist_type = 'STOCKIST' 
                AND p.product_name LIKE ?
                
                -- THE LOGIC: Keep the ledger row ONLY if:
                -- 1. It is NOT an inward entry (e.g., it is a SALE deduction, so keep it)
                -- 2. OR it IS an inward entry, AND the order status is officially 'Processed'
                AND (sl.reference_table != 'stock_inward' OR o.status = 'Processed')

            GROUP BY p.p_id, p.product_name
            HAVING total_stock > 0
            ORDER BY p.product_name ASC
            LIMIT 50
        ");
        
        $like = "%{$q}%";
        $stmt->bind_param("is", $stockist_id, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                'id'    => (int)$row['p_id'],
                'name'  => $row['product_name'],
                'mrp'   => (float)$row['mrp'], 
                'pts'   => (float)$row['pts'], 
                'stock' => (float)$row['total_stock']
            ];
        }

        $stmt->close();
        return $products;
    }
    // ------------------------------------------------------------------------
    // FIFO Batch Allocation (Used for Previewing Amount AND Saving)
    // ------------------------------------------------------------------------
    public function calculateSalesBatchAllocation($stockist_id, $product_id, $ordered_qty, $is_save = false)
    {
        $sql = "
            SELECT 
                pb.batch_id,
                pb.batch_no,
                pb.mrp,
                
                -- FIXED: Added 'sid.' here as well
                (
                    SELECT ROUND((sid.rate - ((sid.rate * COALESCE(sid.discount_percent, 0)) / 100)), 2) 
                    FROM `stock_inward_details` sid
                    WHERE sid.batch_id = pb.batch_no 
                    LIMIT 1
                ) AS pts,
                -- ---------------------------------

                pb.sale_tax AS gst,
                (SUM(COALESCE(sl.qty_in, 0)) - SUM(COALESCE(sl.qty_out, 0))) AS current_qty
            FROM product_batches pb
            INNER JOIN stock_ledger sl ON sl.batch_id = pb.batch_id
            WHERE sl.stockist_id = ? 
            AND sl.stockist_type = 'STOCKIST'
            AND pb.product_id = ?
            GROUP BY pb.batch_id
            HAVING current_qty > 0
            ORDER BY pb.expiry_date ASC, pb.batch_id ASC
        ";

        if ($is_save) {
            $sql .= " FOR UPDATE";
        }

        $batchStmt = $this->con->prepare($sql);
        $batchStmt->bind_param("ii", $stockist_id, $product_id);
        $batchStmt->execute();
        $batches = $batchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $batchStmt->close();

        $total_available = array_sum(array_column($batches, 'current_qty'));
        if ($total_available < $ordered_qty) {
            throw new Exception("Insufficient stock for product ID {$product_id}: requested {$ordered_qty}, only {$total_available} available.");
        }

        $remaining_qty = $ordered_qty;
        $allocated_batches = [];

        foreach ($batches as $row) {
            if ($remaining_qty <= 0) break;

            $batch_id  = (int)$row['batch_id'];
            $available = (float)$row['current_qty'];

            $alloc_qty = min($remaining_qty, $available);
            if ($alloc_qty <= 0) continue;

            $mrp    = (float)$row['mrp'];
            $rate   = (float)$row['pts']; 
            $amount = round($alloc_qty * $rate, 2);

            $allocated_batches[] = [
                'batch_id' => $batch_id,
                'batch_no' => $row['batch_no'],
                'alloc_qty'=> $alloc_qty,
                'mrp'      => $mrp,      
                'pts'      => $rate,     
                'rate'     => $rate,     
                'amount'   => $amount
            ];

            $remaining_qty -= $alloc_qty;
        }

        if ($remaining_qty > 0) {
            throw new Exception("Batch allocation mismatch for product ID {$product_id}: {$remaining_qty} units left unallocated.");
        }

        return [
            'success'           => true,
            'product_id'        => $product_id,
            'requested_qty'     => $ordered_qty,
            'allocated_batches' => $allocated_batches
        ];
    }

    public function saveSale($data)
    {
        $mr_id       = $data['mr_id'];
        $sale_date   = $data['sale_date'];
        $customer_id = $data['customer_id'];
        $stockist_id = $data['stockist_id'];
        $total_amt   = $data['total_amt'];
        $total_mrp_amt = $data['mrp_total'];
        $items       = $data['items'];

        if (!$customer_id || !$stockist_id || empty($items)) {
            return [
                'success' => false,
                'msg' => 'Missing required fields.'
            ];
        }

        try {
            //=========================
            // Financial Year Check
            //=========================
            // $fy = $this->con->prepare("
            //     SELECT fy_id
            //     FROM financial_year
            //     WHERE hq_id=?
            //     AND status=1
            //     AND ? BETWEEN start_date AND end_date
            //     LIMIT 1
            // ");

            // $fy->bind_param("is", $mr_id, $sale_date);
            // $fy->execute();
            // $result = $fy->get_result();

            // if($result->num_rows == 0){
            //     throw new Exception("No active Financial Year found.");
            // }

            // $fy_id = $result->fetch_assoc()['fy_id'];

            // FIX: Since the FY code above is commented out, we must define $fy_id 
            // so the bind_param function below doesn't crash.
            $fy_id = 1; 

            // Begin Transaction
            $this->con->begin_transaction();

            //-----------------------------------
            // Insert Sales Master
            //-----------------------------------
            $stmt = $this->con->prepare("
                INSERT INTO sales_entries
                (m_id, fy_id, c_id, stockist_id, total_amt, sale_date, mrp_total)
                VALUES(?,?,?,?,?,?,?)
            ");

            // FIX: Changed "iiiidsi" to "iiiidsd" (the last parameter is a decimal/double, not an integer)
            $stmt->bind_param("iiiidsd", $mr_id, $fy_id, $customer_id, $stockist_id, $total_amt, $sale_date, $total_mrp_amt);
            
            $stmt->execute();
            $entry_id = $this->con->insert_id;
            $stmt->close();

            //-----------------------------------
            // Items FIFO Allocation & Loop
            //-----------------------------------
            $this->allocateAndInsertSaleItems($entry_id, $stockist_id, $sale_date, $items);

            $this->con->commit();

            return [
                'success' => true,
                'msg'     => 'Sale Saved Successfully.',
                'entry_id'=> $entry_id
            ];

        } catch(Exception $e) {
            $this->con->rollback();
            return [
                'success' => false,
                'msg'     => $e->getMessage()
            ];
        }
    }


    //----------------------------------------------------
    // Execute FIFO Logic and Deduct Stock via Ledger
    //----------------------------------------------------
    private function allocateAndInsertSaleItems($entry_id, $stockist_id, $sale_date, $items)
    {
        $trans_datetime = date('Y-m-d H:i:s');
        $admin_id = 1; // Default admin ID to satisfy the database constraint

        foreach ($items as $item) {
            $product_id  = (int)($item['product_id'] ?? $item['id'] ?? 0);
            $ordered_qty = (float)($item['qty'] ?? 0);

            if ($product_id <= 0 || $ordered_qty <= 0) continue;

            // $is_save is true, meaning "FOR UPDATE" row locking will apply
            $allocation = $this->calculateSalesBatchAllocation($stockist_id, $product_id, $ordered_qty, true);

            foreach ($allocation['allocated_batches'] as $alloc) {
                
                // 1. Insert Sales Detail
                $detailStmt = $this->con->prepare("
                    INSERT INTO sales_details (s_id, p_id, batch_id, qty, rate, amount)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $detailStmt->bind_param(
                    "iiiddd",
                    $entry_id,
                    $product_id,
                    $alloc['batch_id'],
                    $alloc['alloc_qty'],
                    $alloc['rate'],
                    $alloc['amount']
                );
                
                if (!$detailStmt->execute()) {
                    throw new Exception("Failed to insert sale details for batch {$alloc['batch_no']}: " . $detailStmt->error);
                }
                $detailStmt->close();

                // 2. Stock Ledger OUTWARD (SALE) Deduction
                // ADDED admin_id to the columns and VALUES
                $ledger = $this->con->prepare("
                    INSERT INTO stock_ledger
                    (
                        trans_date, trans_datetime, stockist_type, stockist_id, admin_id, 
                        p_id, batch_id, trans_type, qty_out, qty, rate, 
                        reference_table, reference_id, remarks , amount
                    )
                    VALUES (?, ?, 'STOCKIST', ?, ?, ?, ?, 'SALE', ?, ?, ?, 'sales_entries', ?, '',?)
                ");

                // Updated type string to "ssiiiidddi" (10 parameters)
                $ledger->bind_param(
                    "ssiiiidddid",
                    $sale_date,
                    $trans_datetime,
                    $stockist_id,
                    $admin_id,         // <--- Added admin_id here
                    $product_id,
                    $alloc['batch_id'],
                    $alloc['alloc_qty'], // Assigned to qty_out
                    $alloc['alloc_qty'], // Assigned to qty
                    $alloc['rate'],
                    $entry_id,
                    $alloc['amount']
                );

                if (!$ledger->execute()) {
                    throw new Exception("Failed to update Stock Ledger: " . $ledger->error);
                }
                $ledger->close();
            }
        }
    }

    public function getFilteredSales($mr_id, $stockist_id, $sale_type, $sale_date) {
        $cond = "s.m_id = " . (int)$mr_id;

        if ($stockist_id > 0) {
            $cond .= " AND s.stockist_id = $stockist_id";
        }
        if (!empty($sale_type)) {
            $sale_type = mysqli_real_escape_string($this->con, $sale_type);
            $cond .= " AND c.customer_type = '$sale_type'";
        }
        if (!empty($sale_date)) {
            $sale_date = mysqli_real_escape_string($this->con, $sale_date);
            $cond .= " AND DATE(s.sale_date) = '$sale_date'";
        }

        $query = "
            SELECT s.*, st.stockist_name, c.customer_name ,c.customer_type
            FROM sales_entries s
            LEFT JOIN stockists st ON s.stockist_id = st.stockist_id
            LEFT JOIN customers c ON s.c_id = c.c_id
            WHERE $cond
            ORDER BY s.sale_date DESC
        ";

        $result = $this->con->query($query);
        $sales = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $sales[] = $row;
            }
        }
        return $sales;
    }
}
?>