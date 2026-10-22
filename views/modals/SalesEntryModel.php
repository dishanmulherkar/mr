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

    public function getCustomer($hq_id, $type = '')
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
            $stmt->bind_param("isi", $hq_id, $type, $status);
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
            $stmt->bind_param("ii", $hq_id, $status);
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
   public function searchMedicine($stockist_id, $q = '')
{
    $stockist_id = (int)$stockist_id;
    if ($stockist_id <= 0) {
        return [];
    }

    $q = trim((string)$q);

    $filterSql = '';
    $types     = 'i';
    $params    = [$stockist_id];

    if ($q !== '') {
        // Escape LIKE wildcards typed by the user, then wrap with %
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $filterSql = " AND (p.product_name LIKE ? OR pb.batch_no LIKE ?) ";
        $types    .= 'ss';
        $params[]  = $like;
        $params[]  = $like;
    }

    $sql = "
        SELECT
            p.p_id,
            p.product_name,
            pb.batch_id,
            pb.batch_no,
            pb.expiry_date,
            pb.mrp,
            (
                SELECT ROUND(sid.rate - ((sid.rate * COALESCE(sid.discount_percent, 0)) / 100), 2)
                FROM stock_inward_details sid
                WHERE sid.batch_id = pb.batch_no OR sid.batch_id = pb.batch_id
                LIMIT 1
            ) AS pts,
            SUM(COALESCE(sl.qty_in, 0) - COALESCE(sl.qty_out, 0)) AS batch_stock
        FROM stock_ledger sl
        INNER JOIN products p        ON p.p_id = sl.p_id
        INNER JOIN product_batches pb ON pb.batch_id = sl.batch_id
        LEFT JOIN stock_inward si    ON si.inward_id = sl.reference_id
                                    AND sl.reference_table = 'stock_inward'
        LEFT JOIN orders o           ON o.order_id = si.order_id
        WHERE sl.stockist_id = ?
          AND sl.stockist_type = 'STOCKIST'
          {$filterSql}
          AND (sl.reference_table != 'stock_inward'
               OR LOWER(COALESCE(o.status, 'processed')) = 'processed')
        GROUP BY p.p_id, p.product_name, pb.batch_id, pb.batch_no, pb.expiry_date, pb.mrp
        HAVING batch_stock > 0
        ORDER BY p.product_name ASC, pb.expiry_date ASC
        LIMIT 50
    ";

    $stmt = $this->con->prepare($sql);
    if (!$stmt) {
        return ['error' => 'Prepare failed: ' . $this->con->error];
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        return ['error' => 'Execute failed: ' . $err];
    }

    $result = $stmt->get_result();
    $products = [];

    while ($row = $result->fetch_assoc()) {
        $p_id  = (int)$row['p_id'];
        $stock = (float)$row['batch_stock'];

        if (!isset($products[$p_id])) {
            $products[$p_id] = [
                'id'          => $p_id,
                'name'        => $row['product_name'],
                'total_stock' => 0.0,
                'batches'     => [],
            ];
        }

        $products[$p_id]['total_stock'] += $stock;

        $products[$p_id]['batches'][] = [
            'batch_id'    => (int)$row['batch_id'],
            'batch_no'    => $row['batch_no'],
            'expiry_date' => $row['expiry_date'] ?? null,
            'mrp'         => (float)$row['mrp'],
            'pts'         => (float)($row['pts'] ?? 0),
            'stock'       => $stock,
        ];
    }

    $stmt->close();
    return array_values($products);
}
   
   public function saveSale($data)
    {
        $mr_id            = $data['mr_id'];
        $sale_date        = $data['sale_date'];
        $customer_id      = $data['customer_id'];
        $stockist_id      = (int)$data['stockist_id'];
        $total_amt        = (float)$data['total_amt'];
        $discount_percent = (float)($data['discount_percent'] ?? 0.00);
        $discount_amt     = (float)($data['discount_amt'] ?? 0.00);
        $net_amt          = (float)($data['net_amt'] ?? ($total_amt - $discount_amt));
        $total_mrp_amt    = (float)$data['mrp_total'];
        $items            = $data['items'];

        if (!$customer_id || !$stockist_id || empty($items)) {
            return [
                'success' => false,
                'msg'     => 'Missing required fields.'
            ];
        }

        try {
            $fy_id = 1; 

            // Begin Transaction
            $this->con->begin_transaction();

            //-----------------------------------
            // Insert Sales Master with Discounts
            //-----------------------------------
            $stmt = $this->con->prepare("
                INSERT INTO sales_entries
                (
                    m_id, fy_id, c_id, stockist_id, total_amt, 
                    discount_percent, discount_amt, net_amt, sale_date, mrp_total
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // Param types:
            // m_id (i), fy_id (i), c_id (i), stockist_id (i), total_amt (d),
            // discount_percent (d), discount_amt (d), net_amt (d), sale_date (s), mrp_total (d)
            $stmt->bind_param(
                "iiiiddddsd",
                $mr_id,
                $fy_id,
                $customer_id,
                $stockist_id,
                $total_amt,
                $discount_percent,
                $discount_amt,
                $net_amt,
                $sale_date,
                $total_mrp_amt
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to insert sales entry: " . $stmt->error);
            }
            $entry_id = $this->con->insert_id;
            $stmt->close();

            //-----------------------------------
            // Process Direct User-Selected Batches
            //-----------------------------------
            $this->insertSaleItemsDirect($entry_id, $stockist_id, $sale_date, $items);

            $this->con->commit();

            return [
                'success'  => true,
                'msg'      => 'Sale Saved Successfully.',
                'entry_id' => $entry_id
            ];

        } catch (Exception $e) {
            $this->con->rollback();
            return [
                'success' => false,
                'msg'     => $e->getMessage()
            ];
        }
    }

    //----------------------------------------------------
    // Direct Batch Validation & Deduction (No FIFO)
    //----------------------------------------------------
    private function insertSaleItemsDirect($entry_id, $stockist_id, $sale_date, $items)
    {
        $trans_datetime = date('Y-m-d H:i:s');
        $admin_id = 1;

        // Prepare detail and ledger statements outside the loop for high performance
        $detailStmt = $this->con->prepare("
            INSERT INTO sales_details (s_id, p_id, batch_id, qty, rate, amount)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $ledgerStmt = $this->con->prepare("
            INSERT INTO stock_ledger
            (
                trans_date, trans_datetime, stockist_type, stockist_id, admin_id, 
                p_id, batch_id, trans_type, qty_out, qty, rate, 
                reference_table, reference_id, remarks, amount
            )
            VALUES (?, ?, 'STOCKIST', ?, ?, ?, ?, 'SALE', ?, ?, ?, 'sales_entries', ?, '', ?)
        ");

        // Statement to lock and verify stock for the exact batch chosen
        $stockCheckStmt = $this->con->prepare("
            SELECT (SUM(COALESCE(sl.qty_in, 0)) - SUM(COALESCE(sl.qty_out, 0))) AS current_qty
            FROM stock_ledger sl
            WHERE sl.stockist_id = ? 
                AND sl.stockist_type = 'STOCKIST'
                AND sl.p_id = ?
                AND sl.batch_id = ?
            FOR UPDATE
        ");

        foreach ($items as $item) {
            $product_id = (int)($item['product_id'] ?? $item['id'] ?? 0);
            $batch_id   = (int)($item['batch_id'] ?? 0);
            $qty        = (float)($item['qty'] ?? 0);
            $rate       = (float)($item['pts'] ?? $item['rate'] ?? 0);
            $amount     = round($qty * $rate, 2);

            if ($product_id <= 0 || $batch_id <= 0 || $qty <= 0) {
                continue;
            }

            // 1. Verify batch-specific inventory
            $stockCheckStmt->bind_param("iii", $stockist_id, $product_id, $batch_id);
            $stockCheckStmt->execute();
            $stockRes = $stockCheckStmt->get_result()->fetch_assoc();
            $availableStock = (float)($stockRes['current_qty'] ?? 0);

            if ($availableStock < $qty) {
                throw new Exception("Insufficient stock for batch ID {$batch_id} (Product ID {$product_id}): requested {$qty}, but only {$availableStock} available.");
            }

            // 2. Insert into sales_details
            $detailStmt->bind_param(
                "iiiddd",
                $entry_id,
                $product_id,
                $batch_id,
                $qty,
                $rate,
                $amount
            );

            if (!$detailStmt->execute()) {
                throw new Exception("Failed to insert sales detail: " . $detailStmt->error);
            }

            // 3. Insert into stock_ledger (Deduction)
            $ledgerStmt->bind_param(
                "ssiiiidddid",
                $sale_date,
                $trans_datetime,
                $stockist_id,
                $admin_id,
                $product_id,
                $batch_id,
                $qty,        // qty_out
                $qty,        // qty
                $rate,
                $entry_id,
                $amount
            );

            if (!$ledgerStmt->execute()) {
                throw new Exception("Failed to update Stock Ledger: " . $ledgerStmt->error);
            }
        }

        $stockCheckStmt->close();
        $detailStmt->close();
        $ledgerStmt->close();
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

// -----------------------------------------------------------------
// DELETE SALE (Reverts Ledger Entries & Deletes Record in Transaction)
// -----------------------------------------------------------------
public function deleteSale($sale_id, $mr_id)
{
    try {
        $this->con->begin_transaction();

        // Verify the sale belongs to this MR
        $chk = $this->con->prepare("SELECT s_id FROM sales_entries WHERE s_id = ? AND m_id = ? LIMIT 1");
        $chk->bind_param("ii", $sale_id, $mr_id);
        $chk->execute();
        if ($chk->get_result()->num_rows === 0) {
            $chk->close();
            throw new Exception("Sale entry not found or permission denied.");
        }
        $chk->close();

        // 1. Delete associated stock ledger deductions
        $delLedger = $this->con->prepare("
            DELETE FROM stock_ledger 
            WHERE reference_table = 'sales_entries' AND reference_id = ?
        ");
        $delLedger->bind_param("i", $sale_id);
        $delLedger->execute();
        $delLedger->close();

        // 2. Delete item details
        $delDetails = $this->con->prepare("DELETE FROM sales_details WHERE s_id = ?");
        $delDetails->bind_param("i", $sale_id);
        $delDetails->execute();
        $delDetails->close();

        // 3. Delete master entry
        $delMaster = $this->con->prepare("DELETE FROM sales_entries WHERE s_id = ?");
        $delMaster->bind_param("i", $sale_id);
        $delMaster->execute();
        $delMaster->close();

        $this->con->commit();

        return [
            'success' => true,
            'msg'     => 'Sale entry deleted and stock restored successfully.'
        ];

    } catch (Exception $e) {
        $this->con->rollback();
        return [
            'success' => false,
            'msg'     => $e->getMessage()
        ];
    }
}

// -----------------------------------------------------------------
// FETCH SALE RECORD & AGGREGATED ITEMS FOR EDIT FORM
// -----------------------------------------------------------------
public function getSaleForEdit($sale_id, $mr_id)
{
    // 1. Fetch Header (including total_amt and mrp_total)
   $stmt = $this->con->prepare("
        SELECT se.*, c.customer_name AS customer_name, s.stockist_name AS stockist_name , c.customer_type AS customer_type
        FROM sales_entries se
        LEFT JOIN customers c ON c.c_id = se.c_id
        LEFT JOIN stockists s ON s.stockist_id = se.stockist_id
        WHERE se.s_id = ? AND se.m_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $sale_id, $mr_id);
    $stmt->execute();
    $sale = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$sale) {
        return null;
    }

    // 2. Fetch Items: Fallback to product/batch MRP and compute real available stock
    $stmtItems = $this->con->prepare("
        SELECT 
            sd.p_id AS product_id,
            sd.batch_id,
            COALESCE(pb.batch_no, '') AS batch_no,
            COALESCE(pb.mrp, 0) AS mrp,
            p.product_name,
            sd.qty,
            sd.rate AS pts,
            sd.amount,
            -- Available stock in stockist ledger + qty in this existing invoice
            (
                COALESCE((
                    SELECT SUM(sl.qty_in) - SUM(sl.qty_out)
                    FROM stock_ledger sl 
                    WHERE sl.batch_id = sd.batch_id 
                      AND sl.stockist_id = se.stockist_id 
                      AND sl.stockist_type = 'STOCKIST'
                ), 0) + sd.qty
            ) AS available_stock
        FROM sales_details sd
        JOIN sales_entries se ON se.s_id = sd.s_id
        LEFT JOIN products p ON p.p_id = sd.p_id
        LEFT JOIN product_batches pb ON pb.batch_id = sd.batch_id
        WHERE sd.s_id = ?
    ");
    $stmtItems->bind_param("i", $sale_id);
    $stmtItems->execute();
    $sale['items'] = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtItems->close();

    return $sale;
}

// -----------------------------------------------------------------
// UPDATE SALE (Replaces Stock Allocations Cleanly in Transaction)
// -----------------------------------------------------------------
public function updateSale($data)
    {
        $sale_id          = (int)($data['s_id'] ?? 0);
        $mr_id            = (int)($data['mr_id'] ?? 0);
        $sale_date        = $data['sale_date'];
        $customer_id      = (int)($data['customer_id'] ?? 0);
        $stockist_id      = (int)($data['stockist_id'] ?? 0);
        $total_amt        = (float)($data['total_amt'] ?? 0);
        $discount_percent = (float)($data['discount_percent'] ?? 0.00);
        $discount_amt     = (float)($data['discount_amt'] ?? 0.00);
        $net_amt          = (float)($data['net_amt'] ?? ($total_amt - $discount_amt));
        $total_mrp        = (float)($data['mrp_total'] ?? 0);
        $items            = $data['items'];

        if (!$sale_id || !$customer_id || !$stockist_id || empty($items)) {
            return [
                'success' => false,
                'msg'     => 'Missing required parameters.'
            ];
        }

        try {
            $this->con->begin_transaction();

            // 1. Clear old ledger entries and old sales detail allocations
            $delLedger = $this->con->prepare("DELETE FROM stock_ledger WHERE reference_table = 'sales_entries' AND reference_id = ?");
            $delLedger->bind_param("i", $sale_id);
            $delLedger->execute();
            $delLedger->close();

            $delDetails = $this->con->prepare("DELETE FROM sales_details WHERE s_id = ?");
            $delDetails->bind_param("i", $sale_id);
            $delDetails->execute();
            $delDetails->close();

            // 2. Update Master Entry with Discount Columns
            $stmtUpdate = $this->con->prepare("
                UPDATE sales_entries 
                SET c_id = ?, 
                    stockist_id = ?, 
                    total_amt = ?, 
                    discount_percent = ?, 
                    discount_amt = ?, 
                    net_amt = ?, 
                    sale_date = ?, 
                    mrp_total = ?
                WHERE s_id = ? AND m_id = ?
            ");

            // Param types:
            // c_id (i), stockist_id (i), total_amt (d), discount_percent (d),
            // discount_amt (d), net_amt (d), sale_date (s), mrp_total (d), s_id (i), m_id (i)
            $stmtUpdate->bind_param(
                "iiddddsdii",
                $customer_id,
                $stockist_id,
                $total_amt,
                $discount_percent,
                $discount_amt,
                $net_amt,
                $sale_date,
                $total_mrp,
                $sale_id,
                $mr_id
            );

            if (!$stmtUpdate->execute()) {
                throw new Exception("Failed to update sales master: " . $stmtUpdate->error);
            }
            $stmtUpdate->close();

            // 3. Re-insert direct batch items & write updated stock_ledger rows (No FIFO)
            $this->insertSaleItemsDirect($sale_id, $stockist_id, $sale_date, $items);

            $this->con->commit();

            return [
                'success' => true,
                'msg'     => 'Sale entry updated successfully.'
            ];

        } catch (Exception $e) {
            $this->con->rollback();
            return [
                'success' => false,
                'msg'     => $e->getMessage()
            ];
        }
    }
}
?>