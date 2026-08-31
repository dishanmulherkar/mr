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


   public function processOrderApproval($data)
    {
        try {
            // 1. Start Transaction
            $this->con->begin_transaction();

            // Cast primary keys
            $order_id = (int)$data['order_id'];
            $stockist_id = (int)$data['stockist_id'];
            $super_stockist_id = (int)$data['super_stockist_id'];
            
            $exact_grand_total = (float)$data['grand_total']; 
            $rounded_net_amount = round($exact_grand_total);
            $round_off = (float)($data['round_off'] ?? 0); // <-- Added Round Off variable
            
            // Map form array names to variables
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

            // Extract variables cleanly
            $lr_no = $data['lr_no'] ?? '';
            $eway_bill_no = $data['eway_bill_no'] ?? '';
            $vehicle_no = $data['vehicle_no'] ?? '';
            $transport_name = $data['transport_name'] ?? '';
            $credit_days = (int)($data['credit_days'] ?? 0);
            $total_qty = (float)($data['total_qty'] ?? 0);
            $cd_percent = (float)($data['cd_percent'] ?? 0);
            $header_discount = (float)($data['header_discount'] ?? 0);
            $gst_amt = (float)($data['gst_amt'] ?? 0);
            $other_charges = (float)($data['other_charges'] ?? 0);
            $cgst = (float)($data['cgst'] ?? 0);
            $sgst = (float)($data['sgst'] ?? 0);
            $igst = (float)($data['igst'] ?? 0);
            $remarks = $data['remarks'] ?? '';

            // Calculate Sub-Total (Taxable Base with CD applied row-wise)
            $sub_total = 0;
            if (!empty($approved_qtys)) {
                foreach ($approved_qtys as $key => $raw_qty) {
                    $qty = (int)$raw_qty;
                    if ($qty > 0) {
                        $rate = (float)($rates[$key] ?? 0);
                        $disc = (float)($discs[$key] ?? 0);
                        $base = $qty * $rate;
                        $first_disc = $base - ($base * ($disc / 100));
                        $taxable = $first_disc - ($first_disc * ($cd_percent / 100)); // CD % applied
                        $sub_total += $taxable;
                    }
                }
            }

            // Fetch MR ID
            $mr_id = 0;
            $mr_query = $this->con->prepare("SELECT mr_id FROM orders WHERE order_id = ?");
            $mr_query->bind_param("i", $order_id);
            $mr_query->execute();
            $mr_res = $mr_query->get_result();
            if ($mr_row = $mr_res->fetch_assoc()) {
                $mr_id = (int)$mr_row['mr_id'];
            }
            $mr_query->close();

            // Fetch Stockist Name & GST No
            $stockist_name = '';
            $gst_no = '';
            $st_query = $this->con->prepare("SELECT stockist_name, gst_no FROM stockists WHERE stockist_id = ?"); 
            $st_query->bind_param("i", $stockist_id);
            $st_query->execute();
            $st_res = $st_query->get_result();
            if ($st_row = $st_res->fetch_assoc()) {
                $stockist_name = $st_row['stockist_name'] ?? '';
                $gst_no = $st_row['gst_no'] ?? '';
            }
            $st_query->close();

            // <-- UPDATED: Store rounded net amount and round off difference -->
            $stmt1 = $this->con->prepare("UPDATE orders SET status = 'Approved', total_amt = ?, round_off = ? WHERE order_id = ?");
            $stmt1->bind_param("ddi", $rounded_net_amount, $round_off, $order_id);
            $stmt1->execute();
            $stmt1->close();

            // Insert into Stock Inward
            $inward_no = 'T-'. $order_id ;
            $admin_id = 1; 
            $fy_id = 1;    

            // <-- UPDATED: Added round_off to query and values -->
            $stmt2 = $this->con->prepare("
                INSERT INTO stock_inward (
                    inward_no, super_stockist_id, stockist_id, stockist_name, gst_no, mr_id, order_id, 
                    lr_no, eway_bill_no, vehicle_no, transport_name, credit_days, 
                    admin_id, fy_id, inward_date, 
                    total_qty, sub_total, discount, gst_amount, other_charges, grand_total, round_off, 
                    cgst_amount, sgst_amount, igst_amount, remarks, cd_percent
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, 
                    ?, ?, CURDATE(), 
                    ?, ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?
                )
            ");
            
            // <-- UPDATED: adjusted bindings string length and variables (added $round_off) -->
            $stmt2->bind_param(
                "siissiissssiiiddddddddddsd", 
                $inward_no, $super_stockist_id, $stockist_id, $stockist_name, $gst_no, $mr_id, $order_id, 
                $lr_no, $eway_bill_no, $vehicle_no, $transport_name, $credit_days,
                $admin_id, $fy_id, 
                $total_qty, $sub_total, $header_discount, $gst_amt, $other_charges, $rounded_net_amount, $round_off,
                $cgst, $sgst, $igst, $remarks, $cd_percent
            );

            $stmt2->execute();
            $inward_id = $this->con->insert_id;
            $stmt2->close();

            // ==========================================
            // FIX: Payment Ledger Entry
            // ==========================================
            $check_ledger = $this->con->prepare("SELECT id FROM payment_ledgers WHERE transaction_type = 'bill_added' AND reference_id = ? AND ledger_type = 'debt'");
            $check_ledger->bind_param("i", $inward_id);
            $check_ledger->execute();
            $res_ledger = $check_ledger->get_result();
            
            if ($row_ledger = $res_ledger->fetch_assoc()) {
                $ledger_id = $row_ledger['id'];
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

            // Prepare statements for Loop
            $stmt_update_item = $this->con->prepare("UPDATE order_details SET approved_qty = ?, batch_id = ?, rate = ?, amt = ?, net_total = ? WHERE detail_id = ?");
            $stmt_insert_item = $this->con->prepare("INSERT INTO order_details (order_id, product_id, batch_id, qty, approved_qty, rate, amt, net_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_inward_det  = $this->con->prepare("
                INSERT INTO stock_inward_details 
                (inward_id, p_id, batch_id, mrp, qty, rate, discount_percent, amt, gst_percent, gst_amount, net_total) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
           $stmt_ledger_out  = $this->con->prepare("INSERT INTO stock_ledger (trans_date, trans_datetime, stockist_type, stockist_id, admin_id, p_id, batch_id, trans_type, qty_out, qty, rate, amount, reference_table, reference_id) VALUES (CURDATE(), NOW(), 'Super-Stockist', ?, ?, ?, ?, 'SALE', ?, ?, ?, ?, 'stock_inward', ?)");
            $stmt_ledger_in   = $this->con->prepare("INSERT INTO stock_ledger (trans_date, trans_datetime, stockist_type, stockist_id, admin_id, p_id, batch_id, trans_type, qty_in, qty, rate, amount, reference_table, reference_id) VALUES (CURDATE(), NOW(), 'STOCKIST', ?, ?, ?, ?, 'INWARD', ?, ?, ?, ?, 'stock_inward', ?)");

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
                        $stmt_update_item->bind_param("iidddi", $qty, $batch_id, $rate, $amt, $net_total, $detail_id);
                        $stmt_update_item->execute();
                    } else {
                        $stmt_insert_item->bind_param("iiiiiidd", $order_id, $product_id, $batch_id, $qty, $qty, $rate, $amt, $net_total);
                        $stmt_insert_item->execute();
                    }

                    $stmt_inward_det->bind_param("iisdidddddd", $inward_id, $product_id, $batch_str, $mrp, $qty, $rate, $discount_percent, $amt, $gst_percent, $gst_amount_item, $net_total);
                    $stmt_inward_det->execute();

                    $stmt_ledger_out->bind_param("iiiiddddi", $super_stockist_id, $admin_id, $product_id, $batch_id, $qty_float, $qty_float, $rate, $net_total, $inward_id);
                    $stmt_ledger_out->execute();

                    $stmt_ledger_in->bind_param("iiiiddddi", $stockist_id, $admin_id, $product_id, $batch_id, $qty_float, $qty_float, $rate, $net_total, $inward_id);
                    $stmt_ledger_in->execute();
                }
            }

            $stmt_update_item->close();
            $stmt_insert_item->close();
            $stmt_inward_det->close();
            $stmt_ledger_out->close();
            $stmt_ledger_in->close();

            $this->con->commit();
            return ['success' => true, 'msg' => 'Order approved and stock updated successfully.'];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    public function updateApprovedOrder($data)
    {
        try {
            $this->con->begin_transaction();

            $order_id = (int)$data['order_id'];
            $stockist_id = (int)$data['stockist_id'];
            $super_stockist_id = (int)$data['super_stockist_id'];
            
            $exact_grand_total = (float)$data['grand_total'];
            $rounded_net_amount = round($exact_grand_total); 
            $round_off = (float)($data['round_off'] ?? 0); // <-- Added Round Off variable
            $admin_id = 1;

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

            $lr_no = $data['lr_no'] ?? '';
            $eway_bill_no = $data['eway_bill_no'] ?? ''; 
            $vehicle_no = $data['vehicle_no'] ?? '';
            $transport_name = $data['transport_name'] ?? '';
            $credit_days = (int)($data['credit_days'] ?? 0);
            $total_qty = (float)($data['total_qty'] ?? 0);
            $cd_percent = (float)($data['cd_percent'] ?? 0);
            $header_discount = (float)($data['header_discount'] ?? 0);
            $gst_amt = (float)($data['gst_amt'] ?? 0);
            $other_charges = (float)($data['other_charges'] ?? 0);
            $cgst_amount = (float)($data['cgst'] ?? 0);
            $sgst_amount = (float)($data['sgst'] ?? 0);
            $igst_amount = (float)($data['igst'] ?? 0);
            $remarks = $data['remarks'] ?? '';

            // 1. Get existing Inward ID
            $inward_id = 0;
            $stmt_inw = $this->con->prepare("SELECT inward_id FROM stock_inward WHERE order_id = ?");
            $stmt_inw->bind_param("i", $order_id);
            $stmt_inw->execute();
            $res_inw = $stmt_inw->get_result();
            if ($row_inw = $res_inw->fetch_assoc()) {
                $inward_id = (int)$row_inw['inward_id'];
            }
            $stmt_inw->close();

            // 2. CLEANUP
            $stmt_del_ledger = $this->con->prepare("DELETE FROM stock_ledger WHERE reference_table = 'stock_inward' AND reference_id = ?");
            $stmt_del_ledger->bind_param("i", $inward_id);
            $stmt_del_ledger->execute();
            $stmt_del_ledger->close();

            $stmt_del_inw_det = $this->con->prepare("DELETE FROM stock_inward_details WHERE inward_id = ?");
            $stmt_del_inw_det->bind_param("i", $inward_id);
            $stmt_del_inw_det->execute();
            $stmt_del_inw_det->close();

            $kept_detail_ids = array_filter($detail_ids);
            if (!empty($kept_detail_ids)) {
                $id_list = implode(',', array_map('intval', $kept_detail_ids));
                $this->con->query("DELETE FROM order_details WHERE order_id = $order_id AND detail_id NOT IN ($id_list)");
            } else {
                $this->con->query("DELETE FROM order_details WHERE order_id = $order_id");
            }

            // Calculate Sub-Total with CD
            $sub_total = 0;
            if (!empty($approved_qtys)) {
                foreach ($approved_qtys as $key => $raw_qty) {
                    $qty = (int)$raw_qty;
                    if ($qty > 0) {
                        $rate = (float)($rates[$key] ?? 0);
                        $disc = (float)($discs[$key] ?? 0);
                        $base = $qty * $rate;
                        $first_disc = $base - ($base * ($disc / 100));
                        $taxable = $first_disc - ($first_disc * ($cd_percent / 100));
                        $sub_total += $taxable;
                    }
                }
            }

            // <-- UPDATED: Save rounded net amount and round off to Orders -->
            $stmt1 = $this->con->prepare("UPDATE orders SET total_amt = ?, round_off = ? WHERE order_id = ?");
            $stmt1->bind_param("ddi", $rounded_net_amount, $round_off, $order_id);
            $stmt1->execute();
            $stmt1->close();

            // <-- UPDATED: Save rounded net amount and round off to Stock Inwards -->
            $stmt2 = $this->con->prepare("
                UPDATE stock_inward SET 
                lr_no=?, eway_bill_no=?, vehicle_no=?, transport_name=?, credit_days=?, 
                total_qty=?, sub_total=?, discount=?, gst_amount=?, other_charges=?, 
                grand_total=?, round_off=?, cgst_amount=?, sgst_amount=?, igst_amount=?, remarks=?, cd_percent=?
                WHERE order_id=?
            ");
            
            // <-- UPDATED: adjusted bindings string length and variables -->
            $stmt2->bind_param("ssssiddddddddddsdi", 
                $lr_no, $eway_bill_no, $vehicle_no, $transport_name, $credit_days,
                $total_qty, $sub_total, $header_discount, $gst_amt, $other_charges, 
                $rounded_net_amount, $round_off, $cgst_amount, $sgst_amount, $igst_amount, $remarks, $cd_percent,
                $order_id
            );
            $stmt2->execute();
            $stmt2->close();

            // ==========================================
            // FIX: Update Ledger Amount
            // ==========================================
            if ($inward_id > 0) {
                $check_ledger = $this->con->prepare("SELECT id FROM payment_ledgers WHERE transaction_type = 'bill_added' AND reference_id = ? AND ledger_type = 'debt'");
                $check_ledger->bind_param("i", $inward_id);
                $check_ledger->execute();
                $res_ledger = $check_ledger->get_result();
                
                if ($row_ledger = $res_ledger->fetch_assoc()) {
                    $ledger_id = $row_ledger['id'];
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
            }

            // Loop items
            $stmt_update_item = $this->con->prepare("UPDATE order_details SET approved_qty = ?, batch_id = ?, rate = ?, amt = ?, net_total = ? WHERE detail_id = ?");
            $stmt_insert_item = $this->con->prepare("INSERT INTO order_details (order_id, product_id, batch_id, qty, approved_qty, rate, amt, net_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_inward_det  = $this->con->prepare("
                INSERT INTO stock_inward_details 
                (inward_id, p_id, batch_id, mrp, qty, rate, discount_percent, amt, gst_percent, gst_amount, net_total) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_ledger_out  = $this->con->prepare("INSERT INTO stock_ledger (trans_date, trans_datetime, stockist_type, stockist_id, admin_id, p_id, batch_id, trans_type, qty_out, qty, rate, amount, reference_table, reference_id) VALUES (CURDATE(), NOW(), 'Super-Stockist', ?, ?, ?, ?, 'SALE', ?, ?, ?, ?, 'stock_inward', ?)");
            $stmt_ledger_in   = $this->con->prepare("INSERT INTO stock_ledger (trans_date, trans_datetime, stockist_type, stockist_id, admin_id, p_id, batch_id, trans_type, qty_in, qty, rate, amount, reference_table, reference_id) VALUES (CURDATE(), NOW(), 'STOCKIST', ?, ?, ?, ?, 'INWARD', ?, ?, ?, ?, 'stock_inward', ?)");

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
                        $stmt_update_item->bind_param("iidddi", $qty, $batch_id, $rate, $amt, $net_total, $detail_id);
                        $stmt_update_item->execute();
                    } else {
                        $stmt_insert_item->bind_param("iiiiiidd", $order_id, $product_id, $batch_id, $qty, $qty, $rate, $amt, $net_total);
                        $stmt_insert_item->execute();
                    }

                    $stmt_inward_det->bind_param("iisdidddddd", $inward_id, $product_id, $batch_str, $mrp, $qty, $rate, $discount_percent, $amt, $gst_percent, $gst_amount_item, $net_total);
                    $stmt_inward_det->execute();

                    $stmt_ledger_out->bind_param("iiiiddddi", $super_stockist_id, $admin_id, $product_id, $batch_id, $qty_float, $qty_float, $rate, $net_total, $inward_id);
                    $stmt_ledger_out->execute();

                    $stmt_ledger_in->bind_param("iiiiddddi", $stockist_id, $admin_id, $product_id, $batch_id, $qty_float, $qty_float, $rate, $net_total, $inward_id);
                    $stmt_ledger_in->execute();
                }
            }

            $stmt_update_item->close();
            $stmt_insert_item->close();
            $stmt_inward_det->close();
            $stmt_ledger_out->close();
            $stmt_ledger_in->close();

            $this->con->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    public function dispatchOrder($order_id)
    {
        $stmt = $this->con->prepare("UPDATE orders SET status = 'Processed' WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
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
}