<?php
class asm_com_mdl {
    private $con;

    public function __construct() {
        global $con;
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

public function getAdminMrBills($hqId, $month = '') 
{
    $hqId = (int)$hqId;
    
    // Fetch from admins table as per your updated schema
    $stmt = $this->con->prepare("SELECT admin_id, commission_rate FROM admins WHERE admin_id = ?");
    $stmt->bind_param("i", $hqId);
    $stmt->execute();
    $mr_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Fix: Return the correct associative array structure if no admin is found
    if (!$mr_data) {
        return [
            'bills' => [],
            'admin_id' => $hqId
        ]; 
    }
    
    $admin_id = $mr_data['admin_id'];
    $rate = (float)$mr_data['commission_rate'];

    $month_sql = "";
    if (!empty($month)) {
        $safe_month = mysqli_real_escape_string($this->con, $month);
        $month_sql = " AND DATE_FORMAT(si.created_at, '%Y-%m') = '$safe_month' ";
    }

    // Fix: Removed inline SQL comments to prevent accidental truncation of the query
    $query = "
        SELECT 
            si.inward_id,
            si.inward_no,
            DATE(si.created_at) as bill_date,
            s.stockist_name,
            si.sub_total AS taxable_amount,
            si.grand_total,
            si.paid_amt,
            UPPER(si.pay_status) AS pay_status,
            $rate AS commission_percent,
            ROUND((si.sub_total * ($rate / 100)), 2) AS commission_amount
        FROM stock_inward si
        INNER JOIN stockists s ON si.stockist_id = s.stockist_id
        INNER JOIN headquarter h ON s.hq_id = h.headquarter_id
        WHERE h.asm_id = $admin_id
        AND si.pay_status = 'paid' 
        AND si.asm_com = 0 
        $month_sql
        ORDER BY si.created_at ASC
    ";

    $result = $this->con->query($query);
    $bills = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $bills[] = $row;
        }
    }
    
    // Return as an associative array
    return [
        'bills' => $bills,
        'admin_id' => $admin_id
    ];
}
   public function claimAsmCommission($asm_id, $bill_ids_json, $adjustments_json, $final_payout, $status = 'Pending') 
    {
        try {
            $asm_id = (int)$asm_id; 
            
            $bill_ids = json_decode($bill_ids_json, true);
            $adjustments = json_decode($adjustments_json, true);

            if (empty($bill_ids) || !is_array($bill_ids)) {
                throw new Exception("No bills selected.");
            }

            $clean_ids = array_map('intval', $bill_ids);
            $id_string = implode(',', $clean_ids);

            $this->con->begin_transaction();

            // 1. Insert the Master Payout Record 
            // Note: Reusing 'hq_id' column to store the asm_id (admin_id) receiving the payout
            $stmt_payout = $this->con->prepare("INSERT INTO commission_payouts (hq_id, commission_type, total_payout, status) VALUES (?, 'ASM', ?, ?)");
            $stmt_payout->bind_param("ids", $asm_id, $final_payout, $status);
            
            if (!$stmt_payout->execute()) {
                throw new Exception("Failed to create payout record.");
            }
            
            $payout_id = $this->con->insert_id;
            $stmt_payout->close();

            // 2. Update stock_inward 
            // FIX: Added headquarter JOIN so we can verify the asm_id correctly
            $update_query = "
                UPDATE stock_inward si
                INNER JOIN stockists s ON si.stockist_id = s.stockist_id
                INNER JOIN headquarter h ON s.hq_id = h.headquarter_id
                SET si.asm_com = 1, si.commission_payout_id = $payout_id
                WHERE si.inward_id IN ($id_string)
                AND h.asm_id = $asm_id
                AND si.asm_com = 0
            ";

            if (!$this->con->query($update_query)) {
                throw new Exception("Database update failed for selected bills.");
            }

            // 3. Insert Multiple Adjustments
            if (!empty($adjustments) && is_array($adjustments)) {
                $stmt_adj = $this->con->prepare("INSERT INTO commission_adjustments (payout_id, description, adj_type, amount) VALUES (?, ?, ?, ?)");
                
                foreach ($adjustments as $adj) {
                    $desc = $adj['description'];
                    $type = $adj['type']; 
                    $amt  = (float)$adj['amount'];
                    
                    $stmt_adj->bind_param("issd", $payout_id, $desc, $type, $amt);
                    if (!$stmt_adj->execute()) {
                        throw new Exception("Failed to save adjustments.");
                    }
                }
                $stmt_adj->close();
            }

            // 4. LEDGER UPDATE: Split commission and include adjustments
            if ($status === 'Paid') {
                
                // FIX: Fetch ASM rate from admins table instead of mr_users
                $stmt_asm = $this->con->prepare("SELECT commission_rate FROM admins WHERE admin_id = ?");
                $stmt_asm->bind_param("i", $asm_id);
                $stmt_asm->execute();
                $asm_data = $stmt_asm->get_result()->fetch_assoc();
                $stmt_asm->close();
                $rate = $asm_data ? (float)$asm_data['commission_rate'] : 0;

                $stmt_dist = $this->con->prepare("
                    SELECT stockist_id, SUM(ROUND((sub_total * (? / 100)), 2)) as total_stk_comm
                    FROM stock_inward 
                    WHERE commission_payout_id = ?
                    GROUP BY stockist_id
                ");
                $stmt_dist->bind_param("di", $rate, $payout_id);
                $stmt_dist->execute();
                $dist_res = $stmt_dist->get_result();
                
                // Using the new 'asm_wallet'
                $stmt_ledger = $this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, 'asm_wallet', 'commission_earned', ?, ?, ?, ?)
                ");

                $calculated_bill_total = 0;
                $last_stockist_id = 0;

                // A. Insert base commission per stockist
                while ($stk_row = $dist_res->fetch_assoc()) {
                    $stockist_id = (int)$stk_row['stockist_id'];
                    $stk_comm = (float)$stk_row['total_stk_comm'];
                    
                    if ($stk_comm > 0) {
                        $last_stockist_id = $stockist_id;
                        $calculated_bill_total += $stk_comm;
                        
                        $notes = "ASM Commission Earned (Payout #$payout_id)";
                        $action = 'increase';
                        
                        $stmt_ledger->bind_param("iidss", $stockist_id, $payout_id, $stk_comm, $action, $notes);
                        if (!$stmt_ledger->execute()) {
                            throw new Exception("Failed to update ledger for stockist $stockist_id");
                        }
                    }
                }
                
                // B. Insert difference (Adjustments/Deductions) to balance the final payout
                $net_adjustment = round((float)$final_payout - $calculated_bill_total, 2);
                
                if ($net_adjustment != 0 && $last_stockist_id > 0) {
                    $adj_action = ($net_adjustment > 0) ? 'increase' : 'decrease';
                    $adj_amount = abs($net_adjustment);
                    
                    // FIX: Renamed note for clarity
                    $adj_notes = "ASM Commission Adjustment (Payout #$payout_id)"; 
                    
                    $stmt_ledger->bind_param("iidss", $last_stockist_id, $payout_id, $adj_amount, $adj_action, $adj_notes);
                    if (!$stmt_ledger->execute()) {
                        throw new Exception("Failed to apply commission adjustments to ledger.");
                    }
                }

                $stmt_ledger->close();
                $stmt_dist->close();
            }

            $this->con->commit();
            return ['success' => true, 'msg' => 'ASM Commission claimed successfully.'];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    public function getAsmCommissionHistory($hq_id, $month) {
        // ADDED: AND cp.commission_type = 'MR'
        $sql = "
            SELECT 
                cp.payout_id, 
                cp.total_payout,
                cp.status, 
                DATE_FORMAT(cp.created_at, '%d %b %Y, %h:%i %p') AS date_paid,
                (SELECT admin_name FROM admins WHERE admin_id = cp.hq_id LIMIT 1) AS hq_name 
            FROM commission_payouts cp
            WHERE cp.hq_id = ? AND cp.commission_type = 'ASM'
        ";
        
        $params = [$hq_id];
        $types = "i";

        if (!empty($month)) {
            $parts = explode('-', $month); 
            if (count($parts) === 2) {
                $sql .= " AND YEAR(cp.created_at) = ? AND MONTH(cp.created_at) = ?";
                $params[] = (int)$parts[0];
                $params[] = (int)$parts[1];
                $types .= "ii";
            }
        }
        
        $sql .= " ORDER BY cp.created_at DESC";

        $stmt = $this->con->prepare($sql);
        $stmt->bind_param($types, ...$params); 
        $stmt->execute();
        $res = $stmt->get_result();
        
        $data = [];
        while($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
        
        $stmt->close();
        return $data;
    }

    public function getPayoutDetails($payout_id) {
        $details = ['bills' => [], 'adjustments' => []];

        $sql1 = "
            SELECT si.inward_no, si.inward_date, s.stockist_name, si.sub_total, si.commission_amount 
            FROM stock_inward si 
            LEFT JOIN stockists s ON si.stockist_id = s.stockist_id 
            WHERE si.commission_payout_id = ?
        ";
        $stmt1 = $this->con->prepare($sql1);
        $stmt1->bind_param("i", $payout_id);
        $stmt1->execute();
        $res1 = $stmt1->get_result();
        
        while($row = $res1->fetch_assoc()) {
            $details['bills'][] = $row;
        }
        $stmt1->close();

        $sql2 = "
            SELECT description, adj_type, amount 
            FROM commission_adjustments 
            WHERE payout_id = ?
        ";
        $stmt2 = $this->con->prepare($sql2);
        $stmt2->bind_param("i", $payout_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        
        while($row = $res2->fetch_assoc()) {
            $details['adjustments'][] = $row;
        }
        $stmt2->close();

        return $details;
    }

    public function getEditData($payout_id) {
        $data = ['bills' => [], 'adjustments' => [], 'hq_id' => 0];

        // ADDED: AND commission_type = 'ASM'
        $stmt_hq = $this->con->prepare("SELECT hq_id FROM commission_payouts WHERE payout_id = ? AND commission_type = 'ASM'");
        $stmt_hq->bind_param("i", $payout_id);
        $stmt_hq->execute();
        $hq_result = $stmt_hq->get_result()->fetch_assoc();
        $stmt_hq->close();

        if (!$hq_result) {
            return false; 
        }
        
        $admin_id = (int)$hq_result['hq_id'];
        $data['hq_id'] = $admin_id; // Keeping key as hq_id for backward compatibility in your data array

        $stmt_mr = $this->con->prepare("SELECT commission_rate FROM admins WHERE admin_id = ?");
        $stmt_mr->bind_param("i", $admin_id);
        $stmt_mr->execute();
        $mr_data = $stmt_mr->get_result()->fetch_assoc();
        $stmt_mr->close();
        
        $rate = $mr_data ? (float)$mr_data['commission_rate'] : 0;

        // Updated Query: Included headquarter join, grand_total, paid_amt, pay_status check, and asm_com = 0
        $sql1 = "
            SELECT 
                si.inward_id,
                si.inward_no,
                DATE(si.created_at) as bill_date,
                s.stockist_name,
                si.sub_total AS taxable_amount,
                si.grand_total,
                si.paid_amt,
                UPPER(si.pay_status) AS pay_status,
                $rate AS commission_percent,
                ROUND((si.sub_total * ($rate / 100)), 2) AS commission_amount,
                si.commission_payout_id
            FROM stock_inward si
            INNER JOIN stockists s ON si.stockist_id = s.stockist_id
            INNER JOIN headquarter h ON s.hq_id = h.headquarter_id
            WHERE (h.asm_id = ? AND si.pay_status = 'paid' AND si.asm_com = 0) 
               OR (si.commission_payout_id = ?)
            ORDER BY si.created_at DESC
        ";
        
        $stmt1 = $this->con->prepare($sql1);
        $stmt1->bind_param("ii", $admin_id, $payout_id);
        $stmt1->execute();
        $res1 = $stmt1->get_result();
        
        while ($row = $res1->fetch_assoc()) {
            $data['bills'][] = $row;
        }
        $stmt1->close();

        $sql2 = "SELECT description, adj_type, amount FROM commission_adjustments WHERE payout_id = ?";
        $stmt2 = $this->con->prepare($sql2);
        $stmt2->bind_param("i", $payout_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        
        while ($row = $res2->fetch_assoc()) {
            $data['adjustments'][] = $row;
        }
        $stmt2->close();

        return $data;
    }

    public function updateAsmCommission($payout_id, $asm_id, $bill_ids_json, $adjustments_json, $final_payout, $status = 'Pending') 
    {
        try {
            $asm_id = (int)$asm_id;
            
            $stmt_check = $this->con->prepare("SELECT status FROM commission_payouts WHERE payout_id = ? AND commission_type = 'ASM'");
            $stmt_check->bind_param("i", $payout_id);
            $stmt_check->execute();
            $payout_status = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();
            
            if (!$payout_status) {
                throw new Exception("ASM Payout not found.");
            }
            if ($payout_status['status'] === 'Paid') {
                throw new Exception("This payout has already been marked as Paid and cannot be edited.");
            }

            $bill_ids = json_decode($bill_ids_json, true);
            $adjustments = json_decode($adjustments_json, true);

            if (empty($bill_ids) || !is_array($bill_ids)) {
                throw new Exception("You must select at least one bill for this payout.");
            }

            $clean_ids = array_map('intval', $bill_ids);
            $id_string = implode(',', $clean_ids);

            $this->con->begin_transaction();

            // 1. Reset old linked bills for this payout (Changed mrc to asm_com)
            $reset_bills = "UPDATE stock_inward SET asm_com = 0, commission_payout_id = NULL WHERE commission_payout_id = ?";
            $stmt_reset = $this->con->prepare($reset_bills);
            $stmt_reset->bind_param("i", $payout_id);
            $stmt_reset->execute();
            $stmt_reset->close();

            // 2. Delete old adjustments
            $delete_adj = "DELETE FROM commission_adjustments WHERE payout_id = ?";
            $stmt_del = $this->con->prepare($delete_adj);
            $stmt_del->bind_param("i", $payout_id);
            $stmt_del->execute();
            $stmt_del->close();

            // 3. Update Master Payout Record
            $update_payout = "UPDATE commission_payouts SET total_payout = ?, status = ? WHERE payout_id = ? AND commission_type = 'ASM'";
            $stmt_payout = $this->con->prepare($update_payout);
            $stmt_payout->bind_param("dsi", $final_payout, $status, $payout_id);
            if (!$stmt_payout->execute()) {
                throw new Exception("Failed to update master payout record.");
            }
            $stmt_payout->close();

            // 4. Link new bills to this payout with headquarter JOIN
            $update_query = "
                UPDATE stock_inward si
                INNER JOIN stockists s ON si.stockist_id = s.stockist_id
                INNER JOIN headquarter h ON s.hq_id = h.headquarter_id
                SET si.asm_com = 1, si.commission_payout_id = $payout_id
                WHERE si.inward_id IN ($id_string)
                AND h.asm_id = $asm_id
            ";
            if (!$this->con->query($update_query)) {
                throw new Exception("Failed to link new bills to this payout.");
            }

            // 5. Insert new adjustments
            if (!empty($adjustments) && is_array($adjustments)) {
                $stmt_adj = $this->con->prepare("INSERT INTO commission_adjustments (payout_id, description, adj_type, amount) VALUES (?, ?, ?, ?)");
                foreach ($adjustments as $adj) {
                    $desc = $adj['description'];
                    $type = $adj['type']; 
                    $amt  = (float)$adj['amount'];
                    
                    $stmt_adj->bind_param("issd", $payout_id, $desc, $type, $amt);
                    if (!$stmt_adj->execute()) {
                        throw new Exception("Failed to save new adjustments.");
                    }
                }
                $stmt_adj->close();
            }

            // 6. LEDGER UPDATE: Split commission and include adjustments
            if ($status === 'Paid') {
                
                // Fetch ASM rate from admins table
                $stmt_asm = $this->con->prepare("SELECT commission_rate FROM admins WHERE admin_id = ?");
                $stmt_asm->bind_param("i", $asm_id);
                $stmt_asm->execute();
                $asm_data = $stmt_asm->get_result()->fetch_assoc();
                $stmt_asm->close();
                $rate = $asm_data ? (float)$asm_data['commission_rate'] : 0;

                $stmt_dist = $this->con->prepare("
                    SELECT stockist_id, SUM(ROUND((sub_total * (? / 100)), 2)) as total_stk_comm
                    FROM stock_inward 
                    WHERE commission_payout_id = ?
                    GROUP BY stockist_id
                ");
                $stmt_dist->bind_param("di", $rate, $payout_id);
                $stmt_dist->execute();
                $dist_res = $stmt_dist->get_result();
                
                // Using asm_wallet
                $stmt_ledger = $this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, 'asm_wallet', 'commission_earned', ?, ?, ?, ?)
                ");

                $calculated_bill_total = 0;
                $last_stockist_id = 0;

                // A. Insert base commission per stockist
                while ($stk_row = $dist_res->fetch_assoc()) {
                    $stockist_id = (int)$stk_row['stockist_id'];
                    $stk_comm = (float)$stk_row['total_stk_comm'];
                    
                    if ($stk_comm > 0) {
                        $last_stockist_id = $stockist_id;
                        $calculated_bill_total += $stk_comm;
                        
                        $notes = "ASM Commission Earned (Payout #$payout_id)";
                        $action = 'increase';
                        
                        $stmt_ledger->bind_param("iidss", $stockist_id, $payout_id, $stk_comm, $action, $notes);
                        if (!$stmt_ledger->execute()) {
                            throw new Exception("Failed to update ledger for stockist $stockist_id");
                        }
                    }
                }
                
                // B. Insert difference (Adjustments/Deductions) to balance the final payout
                $net_adjustment = round((float)$final_payout - $calculated_bill_total, 2);
                
                if ($net_adjustment != 0 && $last_stockist_id > 0) {
                    $adj_action = ($net_adjustment > 0) ? 'increase' : 'decrease';
                    $adj_amount = abs($net_adjustment);
                    $adj_notes = "ASM Commission Adjustment (Payout #$payout_id)"; 
                    
                    $stmt_ledger->bind_param("iidss", $last_stockist_id, $payout_id, $adj_amount, $adj_action, $adj_notes);
                    if (!$stmt_ledger->execute()) {
                        throw new Exception("Failed to apply commission adjustments to ledger.");
                    }
                }

                $stmt_ledger->close();
                $stmt_dist->close();
            }

            $this->con->commit();
            return ['success' => true, 'msg' => 'ASM Payout updated successfully.'];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

   // ==========================================================
    // NEW METHOD: Flip the status between Pending and Paid (WITH STRICT ERROR CHECKING)
    // ==========================================================
  public function updatePayoutStatus($payout_id, $status = 'Paid') {
        try {
            $this->con->begin_transaction();

            // 1. Get current payout data before updating (Fetch hq_id which stores asm_id)
            $stmt_info = $this->con->prepare("SELECT hq_id, total_payout, status FROM commission_payouts WHERE payout_id = ? AND commission_type = 'ASM' FOR UPDATE");
            $stmt_info->bind_param("i", $payout_id);
            $stmt_info->execute();
            $payout_data = $stmt_info->get_result()->fetch_assoc();
            $stmt_info->close();

            if (!$payout_data) {
                throw new Exception("ASM Payout not found.");
            }

            $current_status = trim($payout_data['status']);
            $total_payout = (float)$payout_data['total_payout'];
            $asm_id = (int)$payout_data['hq_id']; 

            // 2. Update the payout status
            $stmt = $this->con->prepare("UPDATE commission_payouts SET status = ? WHERE payout_id = ? AND commission_type = 'ASM'");
            $stmt->bind_param("si", $status, $payout_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update payout status: " . $stmt->error);
            }
            $stmt->close();

            // 3. Update the payment_ledgers based on status change
            if ($status === 'Paid' && $current_status !== 'Paid') {
                
                // Fetch ASM rate from admins table
                $stmt_asm = $this->con->prepare("SELECT commission_rate FROM admins WHERE admin_id = ?");
                $stmt_asm->bind_param("i", $asm_id);
                $stmt_asm->execute();
                $asm_data = $stmt_asm->get_result()->fetch_assoc();
                $stmt_asm->close();
                $rate = $asm_data ? (float)$asm_data['commission_rate'] : 0;

                // Accurately split the commission per stockist like in the claim/update methods
                $stmt_dist = $this->con->prepare("
                    SELECT stockist_id, SUM(ROUND((sub_total * (? / 100)), 2)) as total_stk_comm
                    FROM stock_inward 
                    WHERE commission_payout_id = ?
                    GROUP BY stockist_id
                ");
                $stmt_dist->bind_param("di", $rate, $payout_id);
                $stmt_dist->execute();
                $dist_res = $stmt_dist->get_result();
                
                $stmt_ledger = $this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, 'asm_wallet', 'commission_earned', ?, ?, ?, ?)
                ");

                if (!$stmt_ledger) {
                    throw new Exception("SQL Error in payment_ledgers: " . $this->con->error);
                }

                $calculated_bill_total = 0;
                $last_stockist_id = 0;

                while ($stk_row = $dist_res->fetch_assoc()) {
                    $stockist_id = (int)$stk_row['stockist_id'];
                    $stk_comm = (float)$stk_row['total_stk_comm'];
                    
                    if ($stk_comm > 0) {
                        $last_stockist_id = $stockist_id;
                        $calculated_bill_total += $stk_comm;
                        
                        $notes = "ASM Commission Earned (Payout #$payout_id)";
                        $action = 'increase';
                        
                        $stmt_ledger->bind_param("iidss", $stockist_id, $payout_id, $stk_comm, $action, $notes);
                        if (!$stmt_ledger->execute()) {
                            throw new Exception("Failed to update ledger for stockist $stockist_id");
                        }
                    }
                }
                
                // Handle Adjustments
                $net_adjustment = round((float)$total_payout - $calculated_bill_total, 2);
                
                if ($net_adjustment != 0 && $last_stockist_id > 0) {
                    $adj_action = ($net_adjustment > 0) ? 'increase' : 'decrease';
                    $adj_amount = abs($net_adjustment);
                    $adj_notes = "ASM Commission Adjustment (Payout #$payout_id)"; 
                    
                    $stmt_ledger->bind_param("iidss", $last_stockist_id, $payout_id, $adj_amount, $adj_action, $adj_notes);
                    if (!$stmt_ledger->execute()) {
                        throw new Exception("Failed to apply commission adjustments to ledger.");
                    }
                }

                $stmt_ledger->close();
                $stmt_dist->close();

            } elseif ($status !== 'Paid' && $current_status === 'Paid') {
                // If it was reverted to Pending/Rejected, remove the wallet credit
                $stmt_rev = $this->con->prepare("
                    DELETE FROM payment_ledgers 
                    WHERE ledger_type = 'asm_wallet' 
                    AND transaction_type = 'commission_earned' 
                    AND reference_id = ?
                ");
                $stmt_rev->bind_param("i", $payout_id);
                $stmt_rev->execute();
                $stmt_rev->close();
            }

            $this->con->commit();
            return ['success' => true, 'msg' => 'Status updated to ' . $status];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    // ==========================================================
    // DELETE ASM COMMISSION 
    // ==========================================================
    public function deleteAsmCommission($payout_id)
    {
        try {
            $payout_id = (int)$payout_id;
            $this->con->begin_transaction();

            // 1. Remove ledger entry if it was marked as paid
            $stmt_ledger = $this->con->prepare("DELETE FROM payment_ledgers WHERE ledger_type = 'asm_wallet' AND transaction_type = 'commission_earned' AND reference_id = ?");
            $stmt_ledger->bind_param("i", $payout_id);
            $stmt_ledger->execute();
            $stmt_ledger->close();

            // 2. Unlink all bills (reset asm_com to 0)
            $stmt_reset = $this->con->prepare("UPDATE stock_inward SET asm_com = 0, commission_payout_id = NULL WHERE commission_payout_id = ?");
            $stmt_reset->bind_param("i", $payout_id);
            $stmt_reset->execute();
            $stmt_reset->close();

            // 3. Delete any manual adjustments tied to this payout
            $stmt_adj = $this->con->prepare("DELETE FROM commission_adjustments WHERE payout_id = ?");
            $stmt_adj->bind_param("i", $payout_id);
            $stmt_adj->execute();
            $stmt_adj->close();

            // 4. Delete the payout record itself (check ASM type)
            $stmt_payout = $this->con->prepare("DELETE FROM commission_payouts WHERE payout_id = ? AND commission_type = 'ASM'");
            $stmt_payout->bind_param("i", $payout_id);
            
            if (!$stmt_payout->execute()) {
                throw new Exception("Failed to delete the payout record.");
            }
            $stmt_payout->close();

            $this->con->commit();
            return ['success' => true, 'msg' => 'ASM Commission deleted and bills reset successfully.'];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }
    
    public function getAsmByState($state_id)
    {
        $state_id = (int)$state_id;
        return mysqli_query(
            $this->con,
            "SELECT a.admin_id, a.admin_name FROM admins a 
            inner join admin_state s on a.admin_id = s.admin_id
             WHERE s.state_id = '$state_id' AND a.role = 'ASM' ORDER BY a.admin_name ASC"
        );
    }
}
?>