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
        // $state_id = mysqli_real_escape_string($this->con, $state_id);
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
        // Added LEFT JOIN to headquarter so we can filter by state_id
        $sql = "SELECT o.*, s.stockist_name AS ss_name, m.mr_name 
                FROM orders o
                LEFT JOIN stockists s ON s.stockist_id = o.stockist_id
                LEFT JOIN mr_users m ON m.m_id = o.mr_id
                LEFT JOIN headquarter h ON h.headquarter_id = m.hq_id
                WHERE 1=1"; 
        
        $params = [];
        $types = "";

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
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }

    // Optional: Keep other helper getters if needed by your index view
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
                m.mr_name AS mr_name, 
                s.stockist_id, 
                h.super_stockist_id,
                si.lr_no,
                si.eway_bill_no AS eway_bill,
                si.vehicle_no,
                si.transport_name,
                si.credit_days,
                si.discount AS header_discount,
                si.other_charges,
                si.remarks
            FROM orders o
            LEFT JOIN mr_users m ON m.m_id = o.mr_id
            INNER JOIN headquarter h ON h.headquarter_id = m.hq_id
            LEFT JOIN stockists s ON s.stockist_id = o.stockist_id
            LEFT JOIN stock_inward si ON si.order_id = o.order_id
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

    public function processOrderApproval($data)
    {
        try {
            // 1. Start Transaction
            $this->con->begin_transaction();

            // Cast primary keys
            $order_id = (int)$data['order_id'];
            $stockist_id = (int)$data['stockist_id'];
            $super_stockist_id = (int)$data['super_stockist_id'];
            $grand_total = (float)$data['grand_total']; 

            // Calculate Sub-Total (Taxable Base) dynamically
            $sub_total = 0;
            if (!empty($data['approved_qtys'])) {
                foreach ($data['approved_qtys'] as $key => $raw_qty) {
                    $qty = (int)$raw_qty;
                    if ($qty > 0) {
                        $sub_total += $qty * (float)($data['rates'][$key] ?? 0);
                    }
                }
            }

            // Fetch MR ID from original order to store in stock_inward
            $mr_id = 0;
            $mr_query = $this->con->prepare("SELECT mr_id FROM orders WHERE order_id = ?");
            $mr_query->bind_param("i", $order_id);
            $mr_query->execute();
            $mr_res = $mr_query->get_result();
            if ($mr_row = $mr_res->fetch_assoc()) {
                $mr_id = (int)$mr_row['mr_id'];
            }

            // 2. Update Orders Table 
            $stmt1 = $this->con->prepare("UPDATE orders SET status = 'Approved', total_amt = ? WHERE order_id = ?");
            $stmt1->bind_param("di", $grand_total, $order_id);
            $stmt1->execute();

            // 3. Insert into Stock Inward (Using all table fields)
            $inward_no = 'INW-' . $order_id . '-' . time();
            $admin_id = 1; // Default admin ID
            $fy_id = 1;    // Default financial year

            $stmt2 = $this->con->prepare("
                INSERT INTO stock_inward (
                    inward_no, stockist_id, mr_id, order_id, 
                    lr_no, eway_bill_no, vehicle_no, transport_name, credit_days, 
                    admin_id, fy_id, inward_date, 
                    total_qty, sub_total, discount, gst_amount, other_charges, grand_total, 
                    cgst_amount, sgst_amount, igst_amount, remarks
                ) VALUES (
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, 
                    ?, ?, CURDATE(), 
                    ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?
                )
            ");
            
            // The string has been fixed to exactly 21 characters: siiissssiiiddddddddds
            $stmt2->bind_param(
                "siiissssiiiddddddddds", 
                $inward_no, $stockist_id, $mr_id, $order_id, 
                $data['lr_no'], $data['eway_bill_no'], $data['vehicle_no'], $data['transport_name'], $data['credit_days'],
                $admin_id, $fy_id, 
                $data['total_qty'], $sub_total, $data['discount'], $data['gst_amount'], $data['other_charges'], $grand_total,
                $data['cgst_amount'], $data['sgst_amount'], $data['igst_amount'], $data['remarks']
            );

            // s = string, i = int, d = decimal
            $stmt2->execute();
            
            $inward_id = $this->con->insert_id;

            // Prepare statements for Loop
            $stmt_update_item = $this->con->prepare("UPDATE order_details SET approved_qty = ?, batch_id = ?, rate = ?, amt = ?, net_total = ? WHERE detail_id = ?");
            $stmt_insert_item = $this->con->prepare("INSERT INTO order_details (order_id, product_id, batch_id, qty, approved_qty, rate, amt, net_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_inward_det  = $this->con->prepare("INSERT INTO stock_inward_details (inward_id, p_id, batch_id, qty, rate, amt, net_total) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $stmt_ledger_out  = $this->con->prepare("INSERT INTO stock_ledger (trans_date, trans_datetime, stockist_type, stockist_id, admin_id, p_id, batch_id, trans_type, qty_out, qty, rate, reference_table, reference_id) VALUES (CURDATE(), NOW(), 'Super-Stockist', ?, ?, ?, ?, 'SALE', ?, ?, ?, 'orders', ?)");
            $stmt_ledger_in   = $this->con->prepare("INSERT INTO stock_ledger (trans_date, trans_datetime, stockist_type, stockist_id, admin_id, p_id, batch_id, trans_type, qty_in, qty, rate, reference_table, reference_id) VALUES (CURDATE(), NOW(), 'STOCKIST', ?, ?, ?, ?, 'INWARD', ?, ?, ?, 'orders', ?)");

            // 4. Loop through items safely
            if (!empty($data['approved_qtys'])) {
                foreach ($data['approved_qtys'] as $key => $raw_qty) {
                    
                    $qty = (int)$raw_qty;
                    if ($qty <= 0) continue; 

                    $detail_id  = !empty($data['detail_ids'][$key]) ? (int)$data['detail_ids'][$key] : null;
                    $product_id = (int)($data['product_ids'][$key] ?? 0);
                    $batch_id   = (int)($data['batches'][$key] ?? 0);
                    $batch_str  = (string)($data['batches'][$key] ?? ''); // For varchar tables
                    $rate       = (float)($data['rates'][$key] ?? 0.00);
                    $net_total  = (float)($data['amounts'][$key] ?? 0.00); 
                    $amt        = $qty * $rate; // Taxable base amount
                    $qty_float  = (float)$qty; 

                    // --- A. UPDATE OR INSERT ORDER DETAILS ---
                    if (!empty($detail_id)) {
                        $stmt_update_item->bind_param("iidddi", $qty, $batch_id, $rate, $amt, $net_total, $detail_id);
                        $stmt_update_item->execute();
                    } else {
                        $stmt_insert_item->bind_param("iiiiiidd", $order_id, $product_id, $batch_id, $qty, $qty, $rate, $amt, $net_total);
                        $stmt_insert_item->execute();
                    }

                    // --- B. INSERT INTO STOCK INWARD DETAILS ---
                    $stmt_inward_det->bind_param("iisiddd", $inward_id, $product_id, $batch_str, $qty, $rate, $amt, $net_total);
                    $stmt_inward_det->execute();

                    // --- C. INSERT DUAL ENTRIES INTO STOCK LEDGER ---
                    // OUT Entry (Super Stockist)
                    $stmt_ledger_out->bind_param("iiiidddi", $super_stockist_id, $admin_id, $product_id, $batch_id, $qty_float, $qty_float, $rate, $order_id);
                    $stmt_ledger_out->execute();

                    // IN Entry (Stockist)
                    $stmt_ledger_in->bind_param("iiiidddi", $stockist_id, $admin_id, $product_id, $batch_id, $qty_float, $qty_float, $rate, $order_id);
                    $stmt_ledger_in->execute();
                }
            }

            // 5. Commit Transaction
            $this->con->commit();
            return ['success' => true, 'msg' => 'Order approved and stock updated successfully.'];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function getBatchesByStockistAndProduct($stockist_id, $product_id, $current_order_id = 0)
    {
        $stmt = $this->con->prepare("
            SELECT
                pb.batch_id,
                pb.batch_no,
                pb.expiry_date,
                pb.mrp,
                pb.sale_rate,
                pb.sale_tax,
                ROUND(
                    SUM(COALESCE(sl.qty_in, 0)) - 
                    SUM(IF(sl.reference_table = 'orders' AND sl.reference_id = ?, 0, COALESCE(sl.qty_out, 0)))
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

        // "iii" stands for 3 integers: current_order_id, stockist_id, product_id
        $stmt->bind_param("iii", $current_order_id, $stockist_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $batches = [];

        while($row = $result->fetch_assoc())
        {
            if($row['expiry_date'] && $row['expiry_date']!='0000-00-00')
            {
                $row['expiry_formatted']=date('m/Y',strtotime($row['expiry_date']));
            }
            else
            {
                $row['expiry_formatted']='';
            }

            $batches[]=$row;
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

    // ADD THIS TO YOUR OrderModel CLASS
    public function updateApprovedOrder($data)
    {
        try {
            $this->con->begin_transaction();

            $order_id = (int)$data['order_id'];
            $stockist_id = (int)$data['stockist_id'];
            $super_stockist_id = (int)$data['super_stockist_id'];
            $grand_total = (float)$data['grand_total'];
            $admin_id = 1;

            // 1. Get the existing Inward ID for this order
            $inward_id = 0;
            $stmt_inw = $this->con->prepare("SELECT inward_id FROM stock_inward WHERE order_id = ?");
            $stmt_inw->bind_param("i", $order_id);
            $stmt_inw->execute();
            $res_inw = $stmt_inw->get_result();
            if ($row_inw = $res_inw->fetch_assoc()) {
                $inward_id = $row_inw['inward_id'];
            }

            // 2. CLEANUP: Delete old ledger & inward details so we can insert fresh updated ones
            $stmt_del_ledger = $this->con->prepare("DELETE FROM stock_ledger WHERE reference_table = 'orders' AND reference_id = ?");
            $stmt_del_ledger->bind_param("i", $order_id);
            $stmt_del_ledger->execute();

            $stmt_del_inw_det = $this->con->prepare("DELETE FROM stock_inward_details WHERE inward_id = ?");
            $stmt_del_inw_det->bind_param("i", $inward_id);
            $stmt_del_inw_det->execute();

            // 3. Remove items from order_details that were deleted by the user in JS
            $kept_detail_ids = array_filter($data['detail_ids']);
            if (!empty($kept_detail_ids)) {
                $id_list = implode(',', array_map('intval', $kept_detail_ids));
                $this->con->query("DELETE FROM order_details WHERE order_id = $order_id AND detail_id NOT IN ($id_list)");
            } else {
                $this->con->query("DELETE FROM order_details WHERE order_id = $order_id");
            }

            // Calculate Sub-Total
            $sub_total = 0;
            if (!empty($data['approved_qtys'])) {
                foreach ($data['approved_qtys'] as $key => $raw_qty) {
                    if ((int)$raw_qty > 0) $sub_total += ((int)$raw_qty * (float)($data['rates'][$key] ?? 0));
                }
            }

            // 4. Update Orders Master
            $stmt1 = $this->con->prepare("UPDATE orders SET total_amt = ? WHERE order_id = ?");
            $stmt1->bind_param("di", $grand_total, $order_id);
            $stmt1->execute();

            // 5. Update Stock Inward Master
            $stmt2 = $this->con->prepare("
                UPDATE stock_inward SET 
                lr_no=?, eway_bill_no=?, vehicle_no=?, transport_name=?, credit_days=?, 
                total_qty=?, sub_total=?, discount=?, gst_amount=?, other_charges=?, 
                grand_total=?, cgst_amount=?, sgst_amount=?, igst_amount=?, remarks=?
                WHERE order_id=?
            ");
            $stmt2->bind_param("ssssidddddddddsi", 
                $data['lr_no'], $data['eway_bill_no'], $data['vehicle_no'], $data['transport_name'], $data['credit_days'],
                $data['total_qty'], $sub_total, $data['discount'], $data['gst_amount'], $data['other_charges'], 
                $grand_total, $data['cgst_amount'], $data['sgst_amount'], $data['igst_amount'], $data['remarks'],
                $order_id
            );
            $stmt2->execute();

            // Prepare Statements for loop (FIXED: case string matches perfectly to 'Super-Stockist')
            $stmt_update_item = $this->con->prepare("UPDATE order_details SET approved_qty = ?, batch_id = ?, rate = ?, amt = ?, net_total = ? WHERE detail_id = ?");
            $stmt_insert_item = $this->con->prepare("INSERT INTO order_details (order_id, product_id, batch_id, qty, approved_qty, rate, amt, net_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_inward_det  = $this->con->prepare("INSERT INTO stock_inward_details (inward_id, p_id, batch_id, qty, rate, amt, net_total) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $stmt_ledger_out  = $this->con->prepare("INSERT INTO stock_ledger (trans_date, trans_datetime, stockist_type, stockist_id, admin_id, p_id, batch_id, trans_type, qty_out, qty, rate, reference_table, reference_id) VALUES (CURDATE(), NOW(), 'Super-Stockist', ?, ?, ?, ?, 'SALE', ?, ?, ?, 'orders', ?)");
            $stmt_ledger_in   = $this->con->prepare("INSERT INTO stock_ledger (trans_date, trans_datetime, stockist_type, stockist_id, admin_id, p_id, batch_id, trans_type, qty_in, qty, rate, reference_table, reference_id) VALUES (CURDATE(), NOW(), 'STOCKIST', ?, ?, ?, ?, 'INWARD', ?, ?, ?, 'orders', ?)");

            // 6. Loop Items (Re-insert ledger and inward details)
            if (!empty($data['approved_qtys'])) {
                foreach ($data['approved_qtys'] as $key => $raw_qty) {
                    $qty = (int)$raw_qty;
                    if ($qty <= 0) continue; 

                    $detail_id  = !empty($data['detail_ids'][$key]) ? (int)$data['detail_ids'][$key] : null;
                    $product_id = (int)($data['product_ids'][$key] ?? 0);
                    $batch_id   = (int)($data['batches'][$key] ?? 0);
                    $batch_str  = (string)($data['batches'][$key] ?? '');
                    $rate       = (float)($data['rates'][$key] ?? 0.00);
                    $net_total  = (float)($data['amounts'][$key] ?? 0.00); 
                    $amt        = $qty * $rate;
                    $qty_float  = (float)$qty; 

                    // Update or Insert Order Details
                    if (!empty($detail_id)) {
                        $stmt_update_item->bind_param("iidddi", $qty, $batch_id, $rate, $amt, $net_total, $detail_id);
                        $stmt_update_item->execute();
                    } else {
                        $stmt_insert_item->bind_param("iiiiiidd", $order_id, $product_id, $batch_id, $qty, $qty, $rate, $amt, $net_total);
                        $stmt_insert_item->execute();
                    }

                    // Re-insert Inward Details
                    $stmt_inward_det->bind_param("iisiddd", $inward_id, $product_id, $batch_str, $qty, $rate, $amt, $net_total);
                    $stmt_inward_det->execute();

                    // Re-insert Ledger OUT & IN
                    $stmt_ledger_out->bind_param("iiiidddi", $super_stockist_id, $admin_id, $product_id, $batch_id, $qty_float, $qty_float, $rate, $order_id);
                    $stmt_ledger_out->execute();

                    $stmt_ledger_in->bind_param("iiiidddi", $stockist_id, $admin_id, $product_id, $batch_id, $qty_float, $qty_float, $rate, $order_id);
                    $stmt_ledger_in->execute();
                }
            }

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
}