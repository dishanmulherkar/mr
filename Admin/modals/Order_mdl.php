<?php

class OrderModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getStates()
    {
        // Super Admin can see all states
        if ($_SESSION['admin_role'] == 'Super Admin') {

            return mysqli_query(
                $this->con,
                "SELECT *
                FROM state
                WHERE state_status = 1
                ORDER BY state_name"
            );
        }

        // Admin can see only assigned states
        $admin_id = (int)$_SESSION['admin_id'];

        return mysqli_query(
            $this->con,
            "SELECT
                s.*
            FROM state s
            INNER JOIN admin_state ast
                ON s.state_id = ast.state_id
            WHERE ast.admin_id = '$admin_id'
            AND s.state_status = 1
            ORDER BY s.state_name"
        );
    }

    public function getHQ()
    {
        return mysqli_query(
            $this->con,
            "SELECT * FROM headquarter"
        );
    }


    public function getHqByState($state_id)
    {
        $state_id = (int)$state_id;
        return mysqli_query(
            $this->con,
            "SELECT headquarter_id, hq_name FROM headquarter WHERE state_id = '$state_id' ORDER BY hq_name ASC"
        );
    }

    // Fetch list of all orders with Super Stockist names
    public function getOrders($filters = [])
    {
        // Base SQL
        $sql = "SELECT o.*, s.stockist_name AS ss_name, m.mr_name, s.gst_type
                FROM orders o
                LEFT JOIN stockists s ON s.stockist_id = o.stockist_id
                LEFT JOIN mr_users m ON m.m_id = o.mr_id
                LEFT JOIN headquarter h ON h.headquarter_id = m.hq_id";

        // If not a Super Admin, strictly join with admin_state to restrict by assigned states
        if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] !== 'Super Admin') {
            $sql .= " INNER JOIN admin_state ast ON h.state_id = ast.state_id";
        }

        $sql .= " WHERE 1=1"; 
        
        $params = [];
        $types = "";

        // Apply Admin Role Filter (Restrict to specific admin's states)
        if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] !== 'Super Admin') {
            $sql .= " AND ast.admin_id = ?";
            $params[] = (int)$_SESSION['admin_id'];
            $types .= "i";
        }

        // Apply State Filter
        if (!empty($filters['state_id'])) {
            $sql .= " AND h.state_id = ?";
            $params[] = $filters['state_id'];
            $types .= "i";
        }

        // Apply Headquarter Filter
        if (!empty($filters['hq_id'])) {
            $sql .= " AND m.hq_id = ?";
            $params[] = $filters['hq_id'];
            $types .= "i";
        }
        
        // Apply Date Filter
        if (!empty($filters['order_date'])) {
            $sql .= " AND DATE(o.order_date) = ?";
            $params[] = $filters['order_date'];
            $types .= "s";
        }
        
        $sql .= " ORDER BY o.order_id DESC";

        $stmt = $this->con->prepare($sql);
        if (!empty($params)) {
            // Bind parameters dynamically
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        
        return $stmt->get_result();
    }

    public function getProducts()
    {
        $result = $this->con->query("SELECT * FROM products ORDER BY product_name ASC");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getStockist()
    {
        $result = $this->con->query("SELECT * FROM stockists ORDER BY stockist_name ASC");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getOrderById($order_id)
    {
        $stmt = $this->con->prepare("
            SELECT 
                o.*, 
                s.stockist_name AS ss_name, 
                s.gst_type,
                m.mr_name AS mr_name, 
                s.stockist_id, 
                h.super_stockist_id,
                si.lr_no,
                si.cd_percent,
                si.eway_bill_no AS eway_bill,
                si.vehicle_no,
                si.transport_name,
                si.credit_days,
                si.discount AS header_discount,
                si.other_charges,
                si.remarks,
                s.transport AS stockist_transport,
                s.credit_days AS stockist_credit_days,
                s.dispatch_to AS stockist_dispatch_to,
                ss.state as super_stockist_state,
                s.state as stockist_state
            FROM orders o
            LEFT JOIN mr_users m ON m.m_id = o.mr_id
            INNER JOIN headquarter h ON h.headquarter_id = m.hq_id
            LEFT JOIN stockists s ON s.stockist_id = o.stockist_id
            LEFT JOIN stock_inward si ON si.order_id = o.order_id
            LEFT JOIN super_stockist ss ON ss.super_stockist_id = h.super_stockist_id 
            WHERE o.order_id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }


    public function getOrderDetails($order_id)
    {
        $stmt = $this->con->prepare("
            SELECT 
                od.*, 
                p.product_name, 
                pb.batch_no, 
                pb.expiry_date,
                pb.mrp
            FROM order_details od

            INNER JOIN products p ON p.p_id = od.product_id
            LEFT JOIN product_batches pb ON pb.batch_id = od.batch_id
            WHERE od.order_id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['expiry_date']) && $row['expiry_date'] !== '0000-00-00') {
                $dt = DateTime::createFromFormat('Y-m-d', $row['expiry_date']);
                if ($dt) $row['expiry_formatted'] = $dt->format('m/Y');
            }
            $items[] = $row;
        }
        return $items;
    }

    public function getBatchesByStockistAndProduct($stockist_id, $product_id, $current_order_id = 0)
    {
        // 1. Get inward_id for the current order to ignore its allocated stock
        $inward_id = 0;
        if ((int)$current_order_id > 0) {
            $stmt_inw = $this->con->prepare("SELECT inward_id FROM stock_inward WHERE order_id = ?");
            $stmt_inw->bind_param("i", $current_order_id);
            $stmt_inw->execute();
            $res = $stmt_inw->get_result();
            if ($row = $res->fetch_assoc()) {
                $inward_id = (int)$row['inward_id'];
            }
            $stmt_inw->close();
        }

        // 2. Updated SQL: Adds back the reserved quantity for this order directly into the stock math
        $stmt = $this->con->prepare("
            SELECT
                pb.batch_id,
                pb.batch_no,
                pb.expiry_date,
                pb.mrp,
                pb.sale_rate,
                pb.sale_tax,
                ROUND(
                    (SUM(COALESCE(sl.qty_in, 0)) - 
                    SUM(IF(sl.reference_table = 'stock_inward' AND sl.reference_id = ?, 0, COALESCE(sl.qty_out, 0))))
                    + 
                    COALESCE((
                        SELECT SUM(od.approved_qty) 
                        FROM order_details od 
                        WHERE od.order_id = ? AND od.batch_id = pb.batch_id
                    ), 0)
                , 3) AS current_qty
            FROM product_batches pb
            INNER JOIN stock_ledger sl
                ON sl.batch_id = pb.batch_id
            WHERE sl.stockist_id = ?
                AND sl.stockist_type = 'Super-Stockist'
                AND pb.product_id = ?
            GROUP BY pb.batch_id
            HAVING current_qty > 0
            ORDER BY pb.expiry_date ASC, pb.batch_id ASC
        ");

        $stmt->bind_param("iiii", $inward_id, $current_order_id, $stockist_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $batches = [];

        while($row = $result->fetch_assoc())
        {
            if($row['expiry_date'] && $row['expiry_date'] != '0000-00-00')
            {
                $row['expiry_formatted'] = date('m/Y', strtotime($row['expiry_date']));
            }
            else
            {
                $row['expiry_formatted'] = '';
            }

            $batches[] = $row;
        }

        $stmt->close();

        return $batches;
    }

    public function getProductsBySuperStockist($super_stockist_id)
    {
        $stmt = $this->con->prepare("
            SELECT DISTINCT p.p_id, p.product_name
            FROM stock_ledger sl
            INNER JOIN products p ON p.p_id = sl.p_id
            WHERE sl.stockist_id = ? AND sl.stockist_type = 'Super-Stockist'
            GROUP BY p.p_id, p.product_name
            HAVING SUM(COALESCE(sl.qty_in,0) - COALESCE(sl.qty_out,0)) > 0
            ORDER BY p.product_name ASC
        ");
        $stmt->bind_param("i", $super_stockist_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Generates a unique, gapless INVOICE sequence using your existing 'order_' columns
     * Call this ONLY when an order is Approved.
     */
    private function generateInvoiceNo($stockist_id) 
    {
        $stmt = $this->con->prepare("
            SELECT 
                ss.super_stockist_id, 
                ss.order_prefix, 
                ss.tally_start_no, 
                ss.fy_start_month, 
                ss.financial_year, 
                ss.order_sequence 
            FROM stockists st
            INNER JOIN headquarter hq ON st.hq_id = hq.headquarter_id
            INNER JOIN super_stockist ss ON hq.super_stockist_id = ss.super_stockist_id
            WHERE st.stockist_id = ? 
            FOR UPDATE
        ");
        
        $stmt->bind_param("i", $stockist_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Super Stockist configuration not found for this Stockist.");
        }
        
        $stockist = $result->fetch_assoc();
        $stmt->close();

        $super_stockist_id = $stockist['super_stockist_id'];
        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');
        
        // Calculate the Financial Year
        if ($currentMonth >= (int)$stockist['fy_start_month']) {
            $active_fy = $currentYear . '-' . substr($currentYear + 1, 2);
        } else {
            $active_fy = ($currentYear - 1) . '-' . substr($currentYear, 2);
        }

        // Determine next sequence based on existing database columns
        if ((int)$stockist['order_sequence'] === 0) {
            $next_sequence = (int)$stockist['tally_start_no'];
        } 
        elseif ($stockist['financial_year'] !== $active_fy) {
            $next_sequence = 1;
        } 
        else {
            $next_sequence = (int)$stockist['order_sequence'] + 1;
        }

        // Add this safeguard: If the resulting sequence is 0 (or less), force it to 1
        if ($next_sequence <= 0) {
            $next_sequence = 1;
        }

        // Update the sequence and financial year back to the database
        $updateStmt = $this->con->prepare("
            UPDATE super_stockist 
            SET order_sequence = ?, financial_year = ? 
            WHERE super_stockist_id = ?
        ");
        $updateStmt->bind_param("isi", $next_sequence, $active_fy, $super_stockist_id);
        $updateStmt->execute();
        $updateStmt->close();

        // Format the invoice number with FY to ensure uniqueness 
        $invoice_no = $stockist['order_prefix'] . $next_sequence;

        return [
            'invoice_no' => $invoice_no,
            'sequence'   => $next_sequence,
            'fy'         => $active_fy
        ];
    }

   public function processOrderApproval($data)
    {
        try {
            $this->con->begin_transaction();

            $current_date     = date('Y-m-d');
            $current_datetime = date('Y-m-d H:i:s');
            
            $order_id          = (int)$data['order_id'];
            $stockist_id       = (int)$data['stockist_id'];
            $super_stockist_id = (int)$data['super_stockist_id'];
            
            $exact_grand_total  = (float)$data['grand_total']; 
            $rounded_net_amount = round($exact_grand_total);
            $round_off          = (float)($data['round_off'] ?? 0); 
            
            $approved_qtys = $data['approved_qty'] ?? [];
            $product_ids   = $data['product_id'] ?? [];
            $batch_ids     = $data['batch_id'] ?? [];
            $batches       = $data['batch'] ?? [];
            $mrps          = $data['mrp'] ?? [];
            $rates         = $data['rate'] ?? [];
            $taxes         = $data['tax'] ?? [];
            $discs         = $data['disc'] ?? [];
            $amounts       = $data['amount'] ?? [];
            $detail_ids    = $data['detail_id'] ?? [];

            $lr_no            = $data['lr_no'] ?? '';
            $eway_bill_no     = $data['eway_bill_no'] ?? '';
            $vehicle_no       = $data['vehicle_no'] ?? '';
            $transport_name   = $data['transport_name'] ?? '';
            $credit_days      = (int)($data['credit_days'] ?? 0);
            $total_qty        = (float)($data['total_qty'] ?? 0);
            $cd_percent       = (float)($data['cd_percent'] ?? 0);
            $header_discount  = (float)($data['header_discount'] ?? 0);
            $gst_amt          = (float)($data['gst_amt'] ?? 0);
            $other_charges    = (float)($data['other_charges'] ?? 0);
            $cgst             = (float)($data['cgst'] ?? 0);
            $sgst             = (float)($data['sgst'] ?? 0);
            $igst             = (float)($data['igst'] ?? 0);
            $remarks          = $data['remarks'] ?? '';

            $sub_total            = 0;
            $total_business_value = 0; 
            
            if (!empty($approved_qtys)) {
                foreach ($approved_qtys as $key => $raw_qty) {
                    $qty = (int)$raw_qty;
                    if ($qty > 0) {
                        $rate       = (float)($rates[$key] ?? 0);
                        $disc       = (float)($discs[$key] ?? 0);
                        $base       = $qty * $rate;
                        $first_disc = $base - ($base * ($disc / 100));
                        
                        $total_business_value += $first_disc;
                        
                        $taxable    = $first_disc - ($first_disc * ($cd_percent / 100)); 
                        $sub_total += $taxable;
                    }
                }
            }

            // Fetch MR ID
            $mr_id    = 0;
            $mr_query = $this->con->prepare("SELECT mr_id FROM orders WHERE order_id = ?");
            $mr_query->bind_param("i", $order_id);
            $mr_query->execute();
            $mr_res = $mr_query->get_result();
            if ($mr_row = $mr_res->fetch_assoc()) {
                $mr_id = (int)$mr_row['mr_id'];
            }
            $mr_query->close();

            // Fetch Stockist Info
            $stockist_name = '';
            $gst_no        = '';
            $st_query      = $this->con->prepare("SELECT stockist_name, gst_no FROM stockists WHERE stockist_id = ?"); 
            $st_query->bind_param("i", $stockist_id);
            $st_query->execute();
            $st_res = $st_query->get_result();
            if ($st_row = $st_res->fetch_assoc()) {
                $stockist_name = $st_row['stockist_name'] ?? '';
                $gst_no        = $st_row['gst_no'] ?? '';
            }
            $st_query->close();

            // 1. Generate official Invoice Number
            $invoiceData = $this->generateInvoiceNo($stockist_id);
            $inward_no   = $invoiceData['invoice_no'];
            
            // 2. Update orders table with approval status
            $stmt1 = $this->con->prepare("UPDATE orders SET status = 'Approved', total_amt = ?, round_off = ?, order_no = ? WHERE order_id = ?");
            $stmt1->bind_param("ddsi", $rounded_net_amount, $round_off, $inward_no, $order_id);
            $stmt1->execute();
            $stmt1->close();
            
            $admin_id = 1; 
            $fy_id    = 1;    

            // 3. Insert into stock_inward (defaults to 'unpaid')
            $stmt2 = $this->con->prepare("
                INSERT INTO stock_inward (
                    inward_no, super_stockist_id, stockist_id, stockist_name, gst_no, mr_id, order_id, 
                    lr_no, eway_bill_no, vehicle_no, transport_name, credit_days, 
                    admin_id, fy_id, inward_date, 
                    total_qty, sub_total, discount, gst_amount, other_charges, grand_total, round_off, 
                    cgst_amount, sgst_amount, igst_amount, remarks, cd_percent, business_value,
                    paid_amt, pay_status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, 
                    ?, ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, ?,
                    0.00, 'unpaid'
                )
            ");
            
            $stmt2->bind_param(
                "siissiissssiiisddddddddddsdd", 
                $inward_no, $super_stockist_id, $stockist_id, $stockist_name, $gst_no, $mr_id, $order_id, 
                $lr_no, $eway_bill_no, $vehicle_no, $transport_name, $credit_days,
                $admin_id, $fy_id, $current_date,
                $total_qty, $sub_total, $header_discount, $gst_amt, $other_charges, $rounded_net_amount, $round_off,
                $cgst, $sgst, $igst, $remarks, $cd_percent, $total_business_value
            );

            $stmt2->execute();
            $inward_id = (int)$this->con->insert_id;
            $stmt2->close();

            // 4. Record bill creation in payment_ledgers
            $check_ledger = $this->con->prepare("SELECT id FROM payment_ledgers WHERE transaction_type = 'bill_added' AND reference_id = ? AND ledger_type = 'debt'");
            $check_ledger->bind_param("i", $inward_id);
            $check_ledger->execute();
            $res_ledger = $check_ledger->get_result();
            
            if ($row_ledger = $res_ledger->fetch_assoc()) {
                $ledger_id  = $row_ledger['id'];
                $upd_ledger = $this->con->prepare("UPDATE payment_ledgers SET amount = ? WHERE id = ?");
                $upd_ledger->bind_param("di", $rounded_net_amount, $ledger_id);
                $upd_ledger->execute();
                $upd_ledger->close();
            } else {
                $ins_ledger = $this->con->prepare("INSERT INTO payment_ledgers (stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action) VALUES (?, 'debt', 'bill_added', ?, ?, 'increase')");
                $ins_ledger->bind_param("iid", $stockist_id, $inward_id, $rounded_net_amount);
                $ins_ledger->execute();
                $ins_ledger->close();
            }
            $check_ledger->close();

            // -----------------------------------------------------------------
            // 5. AUTO-DEDUCT AVAILABLE ADVANCE AGAINST THIS BILL (CORRECTED)
            // -----------------------------------------------------------------
            $pending_on_bill       = round((float)$rounded_net_amount, 2);
            $total_advance_settled = 0.00;

            if ($pending_on_bill > 0) {
                // Strict-mode safe query with rounding
                $stmtAdv = $this->con->prepare("
                    SELECT 
                        pl.id AS ledger_id,
                        pl.amount,
                        COALESCE(SUM(pa.amount_allocated), 0) AS used_amount,
                        ROUND(pl.amount - COALESCE(SUM(pa.amount_allocated), 0), 2) AS available_advance
                    FROM payment_ledgers pl
                    LEFT JOIN payment_allocations pa ON pa.ledger_id = pl.id
                    WHERE pl.stockist_id = ? 
                    AND (
                        -- 1. Credit Opening Balance
                        (pl.transaction_type = 'opening_balance' AND (pl.ledger_type = 'credit' OR pl.balance_action = 'decrease'))
                        -- 2. Real Payment Receipts (excludes automatic CD discounts)
                        OR (
                            pl.transaction_type = 'payment_made' 
                            AND (pl.ledger_type = 'credit' OR pl.balance_action = 'decrease')
                            AND (pl.notes IS NULL OR (pl.notes NOT LIKE '%CD on Invoice%' AND pl.notes NOT LIKE '%CD Reversed%'))
                        )
                    )
                    GROUP BY pl.id, pl.amount, pl.created_at
                    HAVING available_advance >= 0.01
                    ORDER BY pl.created_at ASC, pl.id ASC
                ");
                $stmtAdv->bind_param("i", $stockist_id);
                $stmtAdv->execute();
                $advRes = $stmtAdv->get_result();

                // Prepare statement once outside the loop
                $stmtAlloc = $this->con->prepare("
                    INSERT INTO payment_allocations (ledger_id, inward_id, amount_allocated) 
                    VALUES (?, ?, ?)
                ");

                while ($pending_on_bill >= 0.01 && ($advRow = $advRes->fetch_assoc())) {
                    $adv_ledger_id     = (int)$advRow['ledger_id'];
                    $available_advance = round((float)$advRow['available_advance'], 2);

                    $settle_amount = round(min($available_advance, $pending_on_bill), 2);

                    if ($settle_amount >= 0.01) {
                        $stmtAlloc->bind_param("iid", $adv_ledger_id, $inward_id, $settle_amount);
                        $stmtAlloc->execute();

                        $total_advance_settled = round($total_advance_settled + $settle_amount, 2);
                        $pending_on_bill       = round($pending_on_bill - $settle_amount, 2);
                    }
                }
                
                $stmtAlloc->close();
                $stmtAdv->close();

                // Update invoice status if any advance was settled
                if ($total_advance_settled > 0) {
                    $final_status = ($total_advance_settled >= $rounded_net_amount) ? 'paid' : 'partial';
                    $stmtUpdateBill = $this->con->prepare("UPDATE stock_inward SET paid_amt = ?, pay_status = ? WHERE inward_id = ?");
                    $stmtUpdateBill->bind_param("dsi", $total_advance_settled, $final_status, $inward_id);
                    $stmtUpdateBill->execute();
                    $stmtUpdateBill->close();
                }
            }

            // 6. Process Items and Stock Ledgers
            $stmt_update_item = $this->con->prepare("UPDATE order_details SET approved_qty = ?, batch_id = ?, rate = ?, discount = ?, gst = ?, amt = ?, net_total = ? WHERE detail_id = ?");
            $stmt_insert_item = $this->con->prepare("INSERT INTO order_details (order_id, product_id, batch_id, qty, approved_qty, rate, discount, gst, amt, net_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_inward_det  = $this->con->prepare("
                INSERT INTO stock_inward_details 
                (inward_id, p_id, batch_id, mrp, qty, rate, discount_percent, amt, gst_percent, gst_amount, net_total) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt_ledger_out = $this->con->prepare("INSERT INTO stock_ledger (trans_date, trans_datetime, stockist_type, stockist_id, admin_id, p_id, batch_id, trans_type, qty_out, qty, rate, amount, reference_table, reference_id) VALUES (?, ?, 'Super-Stockist', ?, ?, ?, ?, 'SALE', ?, ?, ?, ?, 'stock_inward', ?)");
            $stmt_ledger_in  = $this->con->prepare("INSERT INTO stock_ledger (trans_date, trans_datetime, stockist_type, stockist_id, admin_id, p_id, batch_id, trans_type, qty_in, qty, rate, amount, reference_table, reference_id) VALUES (?, ?, 'STOCKIST', ?, ?, ?, ?, 'INWARD', ?, ?, ?, ?, 'stock_inward', ?)");

            if (!empty($approved_qtys)) {
                foreach ($approved_qtys as $key => $raw_qty) {
                    $qty = (int)$raw_qty;
                    if ($qty <= 0) continue; 

                    $detail_id  = !empty($detail_ids[$key]) ? (int)$detail_ids[$key] : null;
                    $product_id = (int)($product_ids[$key] ?? 0);
                    $batch_id   = (int)($batch_ids[$key] ?? 0);
                    $batch_str  = (string)($batches[$key] ?? ''); 
                    
                    $mrp              = (float)($mrps[$key] ?? 0.00);
                    $rate             = (float)($rates[$key] ?? 0.00);
                    $discount_percent = (float)($discs[$key] ?? 0.00);
                    $gst_percent      = (float)($taxes[$key] ?? 0.00);

                    $base_amount      = $qty * $rate;
                    $disc_amount      = $base_amount * ($discount_percent / 100);
                    $after_first_disc = $base_amount - $disc_amount;
                    
                    $cd_disc_amount   = $after_first_disc * ($cd_percent / 100);
                    $amt              = $after_first_disc - $cd_disc_amount;
                    
                    $gst_amount_item  = $amt * ($gst_percent / 100); 
                    $net_total        = $amt + $gst_amount_item;
                    $qty_float        = (float)$qty; 

                    if (!empty($detail_id)) {
                        $stmt_update_item->bind_param("iidddddi", $qty, $batch_id, $rate, $discount_percent, $gst_percent, $amt, $net_total, $detail_id);
                        $stmt_update_item->execute();
                    } else {
                        $stmt_insert_item->bind_param("iiiiiddddd", $order_id, $product_id, $batch_id, $qty, $qty, $rate, $discount_percent, $gst_percent, $amt, $net_total);
                        $stmt_insert_item->execute();
                    }

                    $stmt_inward_det->bind_param("iisdidddddd", $inward_id, $product_id, $batch_str, $mrp, $qty, $rate, $discount_percent, $amt, $gst_percent, $gst_amount_item, $net_total);
                    $stmt_inward_det->execute();

                    $stmt_ledger_out->bind_param("ssiiiiddddi", $current_date, $current_datetime, $super_stockist_id, $admin_id, $product_id, $batch_id, $qty_float, $qty_float, $rate, $net_total, $inward_id);
                    $stmt_ledger_out->execute();

                    $stmt_ledger_in->bind_param("ssiiiiddddi", $current_date, $current_datetime, $stockist_id, $admin_id, $product_id, $batch_id, $qty_float, $qty_float, $rate, $net_total, $inward_id);
                    $stmt_ledger_in->execute();
                }
            }

            $stmt_update_item->close();
            $stmt_insert_item->close();
            $stmt_inward_det->close();
            $stmt_ledger_out->close();
            $stmt_ledger_in->close();

            $this->con->commit();
            return [
                'success'          => true, 
                'msg'              => 'Order approved and stock updated successfully.', 
                'inward_no'        => $inward_no,
                'advance_deducted' => $total_advance_settled
            ];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function updateApprovedOrder($data)
    {
        try {
            $this->con->begin_transaction();

            $current_date     = date('Y-m-d');
            $current_datetime = date('Y-m-d H:i:s');

            $order_id          = (int)$data['order_id'];
            $stockist_id       = (int)$data['stockist_id'];
            $super_stockist_id = (int)$data['super_stockist_id'];
            
            $exact_grand_total  = (float)$data['grand_total'];
            $rounded_net_amount = round($exact_grand_total); 
            $round_off          = (float)($data['round_off'] ?? 0); 
            $admin_id           = 1;

            $approved_qtys = $data['approved_qty'] ?? [];
            $product_ids   = $data['product_id'] ?? [];
            $batch_ids     = $data['batch_id'] ?? [];
            $batches       = $data['batch'] ?? [];
            $mrps          = $data['mrp'] ?? [];
            $rates         = $data['rate'] ?? [];
            $taxes         = $data['tax'] ?? [];
            $discs         = $data['disc'] ?? [];
            $amounts       = $data['amount'] ?? [];
            $detail_ids    = $data['detail_id'] ?? [];

            $lr_no           = $data['lr_no'] ?? '';
            $eway_bill_no    = $data['eway_bill_no'] ?? ''; 
            $vehicle_no      = $data['vehicle_no'] ?? '';
            $transport_name  = $data['transport_name'] ?? '';
            $credit_days     = (int)($data['credit_days'] ?? 0);
            $total_qty       = (float)($data['total_qty'] ?? 0);
            $cd_percent      = (float)($data['cd_percent'] ?? 0);
            $header_discount = (float)($data['header_discount'] ?? 0);
            $gst_amt         = (float)($data['gst_amt'] ?? 0);
            $other_charges   = (float)($data['other_charges'] ?? 0);
            $cgst_amount     = (float)($data['cgst'] ?? 0);
            $sgst_amount     = (float)($data['sgst'] ?? 0);
            $igst_amount     = (float)($data['igst'] ?? 0);
            $remarks         = $data['remarks'] ?? '';

            $inward_id = 0;
            $stmt_inw = $this->con->prepare("SELECT inward_id FROM stock_inward WHERE order_id = ?");
            $stmt_inw->bind_param("i", $order_id);
            $stmt_inw->execute();
            $res_inw = $stmt_inw->get_result();
            if ($row_inw = $res_inw->fetch_assoc()) {
                $inward_id = (int)$row_inw['inward_id'];
            }
            $stmt_inw->close();

            // 1. Delete previous stock ledger and detail records
            $stmt_del_ledger = $this->con->prepare("DELETE FROM stock_ledger WHERE reference_table = 'stock_inward' AND reference_id = ?");
            $stmt_del_ledger->bind_param("i", $inward_id);
            $stmt_del_ledger->execute();
            $stmt_del_ledger->close();

            $stmt_del_inw_det = $this->con->prepare("DELETE FROM stock_inward_details WHERE inward_id = ?");
            $stmt_del_inw_det->bind_param("i", $inward_id);
            $stmt_del_inw_det->execute();
            $stmt_del_inw_det->close();

            // 2. Clear previous advance allocations on this bill to release credit back
            if ($inward_id > 0) {
                $stmt_del_alloc = $this->con->prepare("DELETE FROM payment_allocations WHERE inward_id = ?");
                $stmt_del_alloc->bind_param("i", $inward_id);
                $stmt_del_alloc->execute();
                $stmt_del_alloc->close();
            }

            $kept_detail_ids = array_filter($detail_ids);
            if (!empty($kept_detail_ids)) {
                $id_list = implode(',', array_map('intval', $kept_detail_ids));
                $this->con->query("DELETE FROM order_details WHERE order_id = $order_id AND detail_id NOT IN ($id_list)");
            } else {
                $this->con->query("DELETE FROM order_details WHERE order_id = $order_id");
            }

            $sub_total            = 0;
            $total_business_value = 0; 
            
            if (!empty($approved_qtys)) {
                foreach ($approved_qtys as $key => $raw_qty) {
                    $qty = (int)$raw_qty;
                    if ($qty > 0) {
                        $rate       = (float)($rates[$key] ?? 0);
                        $disc       = (float)($discs[$key] ?? 0);
                        $base       = $qty * $rate;
                        $first_disc = $base - ($base * ($disc / 100));
                        
                        $total_business_value += $first_disc;
                        
                        $taxable    = $first_disc - ($first_disc * ($cd_percent / 100));
                        $sub_total += $taxable;
                    }
                }
            }

            $stmt1 = $this->con->prepare("UPDATE orders SET total_amt = ?, round_off = ? WHERE order_id = ?");
            $stmt1->bind_param("ddi", $rounded_net_amount, $round_off, $order_id);
            $stmt1->execute();
            $stmt1->close();

            // 3. Update stock_inward record
            $stmt2 = $this->con->prepare("
                UPDATE stock_inward SET 
                    lr_no=?, eway_bill_no=?, vehicle_no=?, transport_name=?, credit_days=?, 
                    total_qty=?, sub_total=?, discount=?, gst_amount=?, other_charges=?, 
                    grand_total=?, round_off=?, cgst_amount=?, sgst_amount=?, igst_amount=?, remarks=?, cd_percent=?, business_value=?
                WHERE order_id=?
            ");
            
            $stmt2->bind_param("ssssiddddddddddsddi", 
                $lr_no, $eway_bill_no, $vehicle_no, $transport_name, $credit_days,
                $total_qty, $sub_total, $header_discount, $gst_amt, $other_charges, 
                $rounded_net_amount, $round_off, $cgst_amount, $sgst_amount, $igst_amount, $remarks, $cd_percent, $total_business_value,
                $order_id
            );
            $stmt2->execute();
            $stmt2->close();

            // 4. Update the bill_added ledger entry
            if ($inward_id > 0) {
                $check_ledger = $this->con->prepare("SELECT id FROM payment_ledgers WHERE transaction_type = 'bill_added' AND reference_id = ? AND ledger_type = 'debt'");
                $check_ledger->bind_param("i", $inward_id);
                $check_ledger->execute();
                $res_ledger = $check_ledger->get_result();
                
                if ($row_ledger = $res_ledger->fetch_assoc()) {
                    $ledger_id  = $row_ledger['id'];
                    $upd_ledger = $this->con->prepare("UPDATE payment_ledgers SET amount = ? WHERE id = ?");
                    $upd_ledger->bind_param("di", $rounded_net_amount, $ledger_id);
                    $upd_ledger->execute();
                    $upd_ledger->close();
                } else {
                    $ins_ledger = $this->con->prepare("INSERT INTO payment_ledgers (stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action) VALUES (?, 'debt', 'bill_added', ?, ?, 'increase')");
                    $ins_ledger->bind_param("iid", $stockist_id, $inward_id, $rounded_net_amount);
                    $ins_ledger->execute();
                    $ins_ledger->close();
                }
                $check_ledger->close();

                // -------------------------------------------------------------
                // 5. RE-EVALUATE AND ALLOCATE ADVANCE MONEY (FIXED QUERY)
                // -------------------------------------------------------------
                $pending_on_bill       = (float)$rounded_net_amount;
                $total_advance_settled = 0.00;

                if ($pending_on_bill > 0) {
                    $stmtAdv = $this->con->prepare("
                        SELECT 
                            pl.id AS ledger_id,
                            pl.amount,
                            COALESCE(SUM(pa.amount_allocated), 0) AS used_amount,
                            (pl.amount - COALESCE(SUM(pa.amount_allocated), 0)) AS available_advance
                        FROM payment_ledgers pl
                        LEFT JOIN payment_allocations pa ON pa.ledger_id = pl.id
                        WHERE pl.stockist_id = ? 
                        AND (
                            -- 1. Credit Opening Balance
                            (pl.transaction_type = 'opening_balance' AND (pl.ledger_type = 'credit' OR pl.balance_action = 'decrease'))
                            -- 2. Real Payment Receipts (excludes automatic CD discounts)
                            OR (
                                pl.transaction_type = 'payment_made' 
                                AND (pl.ledger_type = 'credit' OR pl.balance_action = 'decrease')
                                AND (pl.notes IS NULL OR (pl.notes NOT LIKE '%CD on Invoice%' AND pl.notes NOT LIKE '%CD Reversed%'))
                            )
                        )
                        GROUP BY pl.id
                        HAVING available_advance > 0
                        ORDER BY pl.created_at ASC, pl.id ASC
                    ");
                    $stmtAdv->bind_param("i", $stockist_id);
                    $stmtAdv->execute();
                    $advRes = $stmtAdv->get_result();

                    while ($pending_on_bill > 0 && ($advRow = $advRes->fetch_assoc())) {
                        $adv_ledger_id     = (int)$advRow['ledger_id'];
                        $available_advance = (float)$advRow['available_advance'];

                        $settle_amount = min($available_advance, $pending_on_bill);

                        if ($settle_amount > 0) {
                            $stmtAlloc = $this->con->prepare("
                                INSERT INTO payment_allocations (ledger_id, inward_id, amount_allocated) 
                                VALUES (?, ?, ?)
                            ");
                            $stmtAlloc->bind_param("iid", $adv_ledger_id, $inward_id, $settle_amount);
                            $stmtAlloc->execute();
                            $stmtAlloc->close();

                            $total_advance_settled += $settle_amount;
                            $pending_on_bill       -= $settle_amount;
                        }
                    }
                    $stmtAdv->close();
                }

                $final_status = (round($total_advance_settled, 2) >= round($rounded_net_amount, 2) && $rounded_net_amount > 0) 
                                ? 'paid' 
                                : (($total_advance_settled > 0) ? 'partial' : 'unpaid');

                $stmtUpdateBill = $this->con->prepare("UPDATE stock_inward SET paid_amt = ?, pay_status = ? WHERE inward_id = ?");
                $stmtUpdateBill->bind_param("dsi", $total_advance_settled, $final_status, $inward_id);
                $stmtUpdateBill->execute();
                $stmtUpdateBill->close();
            }

            // 6. Update Items and Re-insert Stock Ledger entries
            $stmt_update_item = $this->con->prepare("UPDATE order_details SET approved_qty = ?, batch_id = ?, rate = ?, discount = ?, gst = ?, amt = ?, net_total = ? WHERE detail_id = ?");
            $stmt_insert_item = $this->con->prepare("INSERT INTO order_details (order_id, product_id, batch_id, qty, approved_qty, rate, discount, gst, amt, net_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_inward_det  = $this->con->prepare("
                INSERT INTO stock_inward_details 
                (inward_id, p_id, batch_id, mrp, qty, rate, discount_percent, amt, gst_percent, gst_amount, net_total) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt_ledger_out = $this->con->prepare("INSERT INTO stock_ledger (trans_date, trans_datetime, stockist_type, stockist_id, admin_id, p_id, batch_id, trans_type, qty_out, qty, rate, amount, reference_table, reference_id) VALUES (?, ?, 'Super-Stockist', ?, ?, ?, ?, 'SALE', ?, ?, ?, ?, 'stock_inward', ?)");
            $stmt_ledger_in  = $this->con->prepare("INSERT INTO stock_ledger (trans_date, trans_datetime, stockist_type, stockist_id, admin_id, p_id, batch_id, trans_type, qty_in, qty, rate, amount, reference_table, reference_id) VALUES (?, ?, 'STOCKIST', ?, ?, ?, ?, 'INWARD', ?, ?, ?, ?, 'stock_inward', ?)");

            if (!empty($approved_qtys)) {
                foreach ($approved_qtys as $key => $raw_qty) {
                    $qty = (int)$raw_qty;
                    if ($qty <= 0) continue; 

                    $detail_id  = !empty($detail_ids[$key]) ? (int)$detail_ids[$key] : null;
                    $product_id = (int)($product_ids[$key] ?? 0);
                    $batch_id   = (int)($batch_ids[$key] ?? 0);
                    $batch_str  = (string)($batches[$key] ?? '');
                    
                    $mrp              = (float)($mrps[$key] ?? 0.00);
                    $rate             = (float)($rates[$key] ?? 0.00);
                    $discount_percent = (float)($discs[$key] ?? 0.00);
                    $gst_percent      = (float)($taxes[$key] ?? 0.00);

                    $base_amount      = $qty * $rate;
                    $disc_amount      = $base_amount * ($discount_percent / 100);
                    $after_first_disc = $base_amount - $disc_amount;
                    
                    $cd_disc_amount   = $after_first_disc * ($cd_percent / 100);
                    $amt              = $after_first_disc - $cd_disc_amount;
                    
                    $gst_amount_item  = $amt * ($gst_percent / 100);
                    $net_total        = $amt + $gst_amount_item;
                    $qty_float        = (float)$qty; 

                    if (!empty($detail_id)) {
                        $stmt_update_item->bind_param("iidddddi", $qty, $batch_id, $rate, $discount_percent, $gst_percent, $amt, $net_total, $detail_id);
                        $stmt_update_item->execute();
                    } else {
                        $stmt_insert_item->bind_param("iiiiiddddd", $order_id, $product_id, $batch_id, $qty, $qty, $rate, $discount_percent, $gst_percent, $amt, $net_total);
                        $stmt_insert_item->execute();
                    }

                    $stmt_inward_det->bind_param("iisdidddddd", $inward_id, $product_id, $batch_str, $mrp, $qty, $rate, $discount_percent, $amt, $gst_percent, $gst_amount_item, $net_total);
                    $stmt_inward_det->execute();

                    $stmt_ledger_out->bind_param("ssiiiiddddi", $current_date, $current_datetime, $super_stockist_id, $admin_id, $product_id, $batch_id, $qty_float, $qty_float, $rate, $net_total, $inward_id);
                    $stmt_ledger_out->execute();

                    $stmt_ledger_in->bind_param("ssiiiiddddi", $current_date, $current_datetime, $stockist_id, $admin_id, $product_id, $batch_id, $qty_float, $qty_float, $rate, $net_total, $inward_id);
                    $stmt_ledger_in->execute();
                }
            }

            $stmt_update_item->close();
            $stmt_insert_item->close();
            $stmt_inward_det->close();
            $stmt_ledger_out->close();
            $stmt_ledger_in->close();

            $this->con->commit();
            return [
                'success'          => true, 
                'inward_id'        => $inward_id,
                'advance_deducted' => $total_advance_settled
            ];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    public function dispatchOrder($order_id)
    {
        // Get today's date
        $current_date = date('Y-m-d');

        // Update both status and dispatch_date at the same time
        $stmt = $this->con->prepare("UPDATE orders SET status = 'Processed', dispatch_date = ? WHERE order_id = ?");
        
        // Bind the string (date) and integer (order_id)
        $stmt->bind_param("si", $current_date, $order_id);
        
        if ($stmt->execute()) {
            return ['success' => true];
        }
        return ['success' => false];
    }

    public function rejectOrder($order_id)
    {
        $stmt = $this->con->prepare("UPDATE orders SET status = 'Rejected' WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        
        if ($stmt->execute()) {
            return ['success' => true];
        }
        return ['success' => false, 'msg' => 'Failed to reject the order.'];
    }

    public function getgst($ss_state,$s_state){
        $gst = "";
        if($ss_state == $s_state){
            $gst = "CGST_SGST";
        }elseif($ss_state == $s_state AND $ss_state = "Nepal" ){
            $gst = "VAT";
        }else{
            $gst = "IGST";
        }
        return $gst;
    }

    public function getHQbyAsm($asm_id)
    {
        return mysqli_query(
            $this->con,
            "SELECT * FROM headquarter where asm_id = '$asm_id'"
        );
    }

    public function getStockistsByHQ($hq_id)
    {
        // Prevent SQL Injection
        $hq_id = mysqli_real_escape_string($this->con, $hq_id);
        
        // Adjust 'stockist' table name and column names to match your actual database schema
        return mysqli_query(
            $this->con,
            "SELECT stockist_id,stockist_name 
            FROM stockists 
            WHERE hq_id = '$hq_id' 
            ORDER BY stockist_name ASC"
        );
    }

public function getOrdersByHQ($hq_id = 0, $stockist_id = 0,$from_date = '')
{
    $asm_id =$_SESSION['admin_id'];
    
    // Base SQL joining mr_user and restricting to the logged-in ASM's HQs
    $sql = "
        SELECT 
            o.order_id,
            o.order_date,
            o.total_amt,
            o.status,
            s.stockist_name,
            mr.mr_name,
            hq.hq_name,
            COALESCE(od.total_qty, 0) AS total_qty,
            CASE 
                WHEN o.status = 'approved' 
                    THEN COALESCE(si.grand_total, o.total_amt)
                ELSE o.total_amt
            END AS display_total
        FROM orders o
        INNER JOIN stockists s 
            ON s.stockist_id = o.stockist_id
        INNER JOIN `mr_users` mr 
            ON mr.m_id = o.mr_id
        INNER JOIN headquarter hq 
            ON mr.hq_id = hq.headquarter_id 
        LEFT JOIN (
            SELECT order_id, SUM(qty) AS total_qty
            FROM order_details
            GROUP BY order_id
        ) od 
            ON od.order_id = o.order_id
        LEFT JOIN (
            SELECT order_id, MAX(grand_total) AS grand_total
            FROM stock_inward
            GROUP BY order_id
        ) si 
            ON si.order_id = o.order_id
        WHERE mr.status = 1 
        AND hq.asm_id = ?  /* <--- STRICTLY LIMIT TO THIS ASM'S HQs */
    ";

    // Bind the ASM ID by default so they can never see other ASMs' orders
    $types = "i";
    $params = [$asm_id];

    // Filter by HQ ID if provided
    if (!empty($hq_id)) {$sql .= " AND mr.hq_id = ? "; 
        $types .= "i";
        $params[] =$hq_id;
    }

    // Filter by Stockist if provided
    if (!empty($stockist_id)) {$sql .= " AND o.stockist_id = ? ";
        $types .= "i";
        $params[] =$stockist_id;
    }

    // Filter by Exact Date if provided (Changed to = instead of >=)
    if (!empty($from_date)) {$sql .= " AND o.order_date = ? ";
        $types .= "s";
        $params[] =$from_date;
    }

    $sql .= " ORDER BY o.order_date DESC, o.order_id DESC ";

    $stmt = $this->con->prepare($sql);

    if (!$stmt) {
        return [];
    }

    // Bind parameters dynamically
    $stmt->bind_param($types, ...$params);

    $stmt->execute();$result = $stmt->get_result();$orders = [];

    while ($row = $result->fetch_assoc()) {$orders[] = [
            'order_id'      => (int)$row['order_id'],
            'order_no'      => 'O' . str_pad($row['order_id'], 3, '0', STR_PAD_LEFT),
            'order_date'    => date('d-m', strtotime($row['order_date'])),
            'stockist_name' => $row['stockist_name'],
            'total_qty'     => (int)$row['total_qty'],
            'total_amt'     => (float)$row['total_amt'],
            'grand_total'   => (float)$row['display_total'],
            'status'        => $row['status'] ?? 'Pending',
            'hq_name'       => $row['hq_name'],
        ];
    }

    $stmt->close();
    return $orders;
}

 public function getOrderById_asm($order_id)
    {
        $order_id = (int)$order_id;
        

        $stmt = $this->con->prepare("SELECT o.*, s.stockist_name,si.inward_no,si.inward_date FROM orders o 
                                        LEFT JOIN `stock_inward` si ON si.order_id = o.order_id
                                        LEFT JOIN stockists s ON o.stockist_id = s.stockist_id
                                         WHERE o.order_id = ? ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order) return null;

        // UPDATED: Added approved_qty to the select statement
        $stmt = $this->con->prepare("
            SELECT 
                od.product_id,
                p.product_name AS name,
                SUM(od.qty) AS qty,
                SUM(od.approved_qty) AS approved_qty,
                MAX(od.rate) AS pts,
                MAX(od.gst) AS tax,
                MAX(od.discount) AS discount,
                SUM(od.amt) AS amt,
                SUM(od.net_total) AS net_total
            FROM order_details od
            INNER JOIN products p ON p.p_id = od.product_id
            WHERE od.order_id = ?
            GROUP BY od.product_id
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'id'           => (int)$row['product_id'],
                'product_id'   => (int)$row['product_id'],
                'name'         => $row['name'],
                'qty'          => (int)$row['qty'],
                'approved_qty' => $row['approved_qty'] !== null ? (int)$row['approved_qty'] : null, // Null means not reviewed yet
                'pts'          => (float)$row['pts'],
                'tax'          => (float)$row['tax'],
                'discount'     => (float)$row['discount'],
                'amt'          => (float)$row['amt'],
                'net_total'    => (float)$row['net_total']
            ];
        }
        $stmt->close();

        $order['items'] = $items;
        return $order;
    }
    

public function getMrCreditLimitDetails($mr_id, $exclude_order_id = 0, $current_bill_amount = 0)
{
    $mr_id               = (int)$mr_id;
    $exclude_order_id    = (int)$exclude_order_id;
    $current_bill_amount = round((float)$current_bill_amount, 2);

    // 1. Fetch MR details, Headquarter ID, and configured credit limit
    $stmt_mr = $this->con->prepare("
        SELECT m_id, mr_name, hq_id, credit_limit 
        FROM mr_users 
        WHERE m_id = ? 
        LIMIT 1
    ");
    $stmt_mr->bind_param("i", $mr_id);
    $stmt_mr->execute();
    $mr = $stmt_mr->get_result()->fetch_assoc();
    $stmt_mr->close();

    if (!$mr) {
        return [
            'success' => false,
            'msg'     => 'MR not found'
        ];
    }

    $credit_limit = (float)($mr['credit_limit'] ?? 0.00);
    $hq_id        = (int)($mr['hq_id'] ?? 0);

    // ==============================================================
    // [+] STEP 2: Pending Inward Bills across MR's territory
    // ==============================================================
    $sql_bills = "
        SELECT 
            COUNT(inward_id) AS pending_bill_count,
            COALESCE(SUM(
                (COALESCE(grand_total, 0) + COALESCE(cd_penalty_amt, 0)) - 
                (COALESCE(paid_amt, 0) + COALESCE(cd_earned_amt, 0))
            ), 0.00) AS total_pending_amount
        FROM stock_inward
        WHERE mr_id = ? 
          AND pay_status != 'paid'
    ";

    if ($exclude_order_id > 0) {
        $sql_bills .= " AND order_id != ?";
        $stmt_bills = $this->con->prepare($sql_bills);
        $stmt_bills->bind_param("ii", $mr_id, $exclude_order_id);
    } else {
        $stmt_bills = $this->con->prepare($sql_bills);
        $stmt_bills->bind_param("i", $mr_id);
    }

    $stmt_bills->execute();
    $bills_data = $stmt_bills->get_result()->fetch_assoc();
    $stmt_bills->close();

    $bills_pending = round((float)$bills_data['total_pending_amount'], 2);
    $pending_count = (int)$bills_data['pending_bill_count'];

    // ==============================================================
    // [+] STEP 3: Pending Opening Balance DEBT in MR's HQ
    // ==============================================================
    $pending_ob_debt = 0.00;
    if ($hq_id > 0) {
        $stmt_ob_debt = $this->con->prepare("
            SELECT 
                COALESCE(SUM(
                    GREATEST(0, s.opening_balance - COALESCE(ob_paid.allocated_ob, 0))
                ), 0.00) AS total_pending_ob_debt
            FROM stockists s
            LEFT JOIN (
                SELECT 
                    pl.stockist_id,
                    SUM(pa.amount_allocated) AS allocated_ob
                FROM payment_allocations pa
                INNER JOIN payment_ledgers pl ON pl.id = pa.ledger_id
                WHERE (pa.inward_id IS NULL OR pa.inward_id = 0)
                GROUP BY pl.stockist_id
            ) ob_paid ON ob_paid.stockist_id = s.stockist_id
            WHERE s.hq_id = ? 
              AND LOWER(s.opening_balance_type) = 'debt'
              AND s.opening_balance > 0
        ");
        $stmt_ob_debt->bind_param("i", $hq_id);
        $stmt_ob_debt->execute();
        $ob_debt_row = $stmt_ob_debt->get_result()->fetch_assoc();
        $stmt_ob_debt->close();

        $pending_ob_debt = round((float)($ob_debt_row['total_pending_ob_debt'] ?? 0.00), 2);
    }

    // ==============================================================
    // [-] STEP 4: Unutilized Opening Balance CREDIT in MR's HQ
    // ==============================================================
    $unutilized_ob_advance = 0.00;
    if ($hq_id > 0) {
        $stmt_ob_credit = $this->con->prepare("
            SELECT 
                COALESCE(SUM(
                    GREATEST(0, s.opening_balance - COALESCE(ob_used.used_ob_credit, 0))
                ), 0.00) AS total_unutilized_ob_credit
            FROM stockists s
            LEFT JOIN (
                SELECT 
                    pl.stockist_id,
                    SUM(pa.amount_allocated) AS used_ob_credit
                FROM payment_allocations pa
                INNER JOIN payment_ledgers pl ON pl.id = pa.ledger_id
                WHERE pl.transaction_type = 'opening_balance'
                  AND (pl.ledger_type = 'credit' OR pl.balance_action = 'decrease')
                GROUP BY pl.stockist_id
            ) ob_used ON ob_used.stockist_id = s.stockist_id
            WHERE s.hq_id = ? 
              AND LOWER(s.opening_balance_type) = 'credit'
              AND s.opening_balance > 0
        ");
        $stmt_ob_credit->bind_param("i", $hq_id);
        $stmt_ob_credit->execute();
        $ob_credit_row = $stmt_ob_credit->get_result()->fetch_assoc();
        $stmt_ob_credit->close();

        $unutilized_ob_advance = round((float)($ob_credit_row['total_unutilized_ob_credit'] ?? 0.00), 2);
    }

    // ==============================================================
    // [-] STEP 5: Unallocated Advances from Approved Payments
    // ==============================================================
    $stmt_adv = $this->con->prepare("
        SELECT 
            COALESCE(
                (SELECT SUM(amount_paid) 
                 FROM payment_details 
                 WHERE mr_id = ? AND approval_status = 'approved'), 
                0
            ) - 
            COALESCE(
                (SELECT SUM(pa.amount_allocated) 
                 FROM payment_allocations pa 
                 INNER JOIN payment_ledgers pl ON pa.ledger_id = pl.id 
                 WHERE (pl.user_id = ? OR pl.reference_id IN (
                     SELECT id FROM payment_details WHERE mr_id = ? AND approval_status = 'approved'
                 ))), 
                0
            ) AS unallocated_payment_advance
    ");
    $stmt_adv->bind_param("iii", $mr_id, $mr_id, $mr_id);
    $stmt_adv->execute();
    $adv_row = $stmt_adv->get_result()->fetch_assoc();
    $stmt_adv->close();

    $unallocated_payment_advance = max(0, round((float)($adv_row['unallocated_payment_advance'] ?? 0.00), 2));

    // ==============================================================
    // [=] STEP 6: Core Plus / Minus Calculations
    // ==============================================================
    // Total gross debt across bills and opening balance
    $gross_pending = round($bills_pending + $pending_ob_debt, 2);

    // Total unallocated credits across payments and advance OB
    $total_advance = round($unallocated_payment_advance + $unutilized_ob_advance, 2);

    // Net actual debt liability currently outstanding
    $net_balance      = round($gross_pending - $total_advance, 2);
    $net_pending_debt = max(0, $net_balance);

    // Available limit remaining before applying this new bill
    // If debt (90,102) >= limit (90,000), available_to_bill = 0.00
    $available_to_bill = ($credit_limit > 0) ? round(max(0, $credit_limit - $net_pending_debt), 2) : 0.00;

    // Projected liability after adding the current bill
    $total_projected_debt = round($net_pending_debt + $current_bill_amount, 2);

    // Exceeded calculation
    $exceeded_amount = 0.00;
    $is_exceeded     = false;

    if ($credit_limit > 0) {
        if ($total_projected_debt > $credit_limit) {
            $is_exceeded     = true;
            // Exceeded amount = (Existing Debt + Current Bill) - Credit Limit
            // Example: (90,102 + 10,000) - 90,000 = 10,102
            $exceeded_amount = round($total_projected_debt - $credit_limit, 2);
        }
    }

    return [
        'success'              => true,
        'mr_id'                => $mr_id,
        'mr_name'              => $mr['mr_name'],
        'credit_limit'         => $credit_limit,
        'total_pending_bills'  => $pending_count,
        'bills_pending'        => $bills_pending,            // (+) Unpaid Inward Bills
        'pending_ob_debt'      => $pending_ob_debt,          // (+) Unpaid Opening Balance Debt
        'gross_pending'        => $gross_pending,            // Total Debt (Bills + OB Debt)
        'unallocated_advance'  => $total_advance,            // (-) Total Advances
        'pending_amount'       => $net_pending_debt,         // Net Current Debt
        'current_bill_amount'  => $current_bill_amount,      // (+) New Bill to be approved
        'total_projected_debt' => $total_projected_debt,     // Net Debt + New Bill
        'available_to_bill'    => $available_to_bill,        // Capacity before this bill
        'is_exceeded'          => $is_exceeded,              // True if breached
        'exceeded_amount'      => $exceeded_amount           // Exact breach amount (e.g. ₹10,102)
    ];
}
}