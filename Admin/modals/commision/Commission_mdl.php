<?php
class Commission_mdl {
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

    public function getAdminMrBills($hqId, $month = '') {
        $hqId = (int)$hqId;
        
        $stmt = $this->con->prepare("SELECT hq_id, commission_rate FROM mr_users WHERE hq_id = ? AND status = '1'");
        $stmt->bind_param("i", $hqId);
        $stmt->execute();
        $mr_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$mr_data) return []; 
        
        $hq_id = $mr_data['hq_id'];
        $rate = (float)$mr_data['commission_rate'];

        $month_sql = "";
        if (!empty($month)) {
            $safe_month = mysqli_real_escape_string($this->con, $month);
            $month_sql = " AND DATE_FORMAT(si.created_at, '%Y-%m') = '$safe_month' ";
        }

        $query = "
            SELECT 
                si.inward_id,
                si.inward_no,
                DATE(si.created_at) as bill_date,
                s.stockist_name,
                si.business_value AS taxable_amount,
                si.grand_total,
                si.paid_amt,
                UPPER(si.pay_status) AS pay_status,
                $rate AS commission_percent,
                ROUND((si.business_value * ($rate / 100)), 2) AS commission_amount
            FROM stock_inward si
            INNER JOIN stockists s ON si.stockist_id = s.stockist_id
            WHERE s.hq_id = $hq_id 
            AND si.mrc = 0 
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
        return $bills;
    }

   public function claimMrCommission($hq_id, $bill_ids_json, $adjustments_json, $final_payout, $status = 'Pending') 
    {
        try {
            $hq_id = (int)$hq_id; 
            
            // 0. Fetch the CURRENT MR rate and m_id to lock it in for this payout
            $stmt_mr = $this->con->prepare("SELECT commission_rate, m_id FROM mr_users WHERE hq_id = ? AND status = '1'");
            $stmt_mr->bind_param("i", $hq_id);
            $stmt_mr->execute();
            $mr_data = $stmt_mr->get_result()->fetch_assoc();
            $stmt_mr->close();
            
            $rate = $mr_data ? (float)$mr_data['commission_rate'] : 0.00;
            
            // FIX: Capture the MR's actual user ID so we can save it to the ledger!
            $mr_id = $mr_data ? (int)$mr_data['m_id'] : 0; 

            $bill_ids = json_decode($bill_ids_json, true);
            $adjustments = json_decode($adjustments_json, true);

            if (empty($bill_ids) || !is_array($bill_ids)) {
                throw new Exception("No bills selected.");
            }

            $clean_ids = array_map('intval', $bill_ids);
            $id_string = implode(',', $clean_ids);

            $this->con->begin_transaction();

            // 1. Insert the Master Payout Record 
            $stmt_payout = $this->con->prepare("
                    INSERT INTO commission_payouts 
                    (mr_id, hq_id, commission_type, total_payout, commission_rate, status) 
                    VALUES (?, ?, 'MRC', ?, ?, ?)
                ");
                $stmt_payout->bind_param("iidss", $mr_id, $hq_id, $final_payout, $rate, $status);
                // Note: Use 'd' if commission_rate is decimal: "iiddds"
            
            if (!$stmt_payout->execute()) {
                throw new Exception("Failed to create payout record.");
            }
            
            $payout_id = $this->con->insert_id;
            $stmt_payout->close();

            // 2. Update stock_inward 
            $update_query = "
                UPDATE stock_inward si
                INNER JOIN stockists s ON si.stockist_id = s.stockist_id
                SET si.mrc = 1, si.commission_payout_id = $payout_id
                WHERE si.inward_id IN ($id_string)
                AND s.hq_id = $hq_id
                AND si.mrc = 0
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

            // 4. LEDGER UPDATE: Single entry (No more splitting!)
            if ($status === 'Paid') {
                
                $notes = "MR Commission Earned (Payout #$payout_id)";
                $action = 'increase';
                $stockist_id = 0; // 0 represents a combined master payout
                
                $stmt_ledger = $this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (user_id, stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, ?, 'mrc_wallet', 'commission_earned', ?, ?, ?, ?)
                ");

                // FIX: Replaced $hq_id with $mr_id so the ledger strictly points to the User's ID
                $stmt_ledger->bind_param("iiidss", $mr_id, $stockist_id, $payout_id, $final_payout, $action, $notes);
                
                if (!$stmt_ledger->execute()) {
                    throw new Exception("Failed to apply commission to MR ledger.");
                }

                $stmt_ledger->close();
            }

            $this->con->commit();
            return ['success' => true, 'msg' => 'Commission claimed successfully.'];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

   public function getMrCommissionHistory($hq_id, $month = '') 
    {
        $hq_id = (int)$hq_id;

        $sql = "
            SELECT  
                cp.payout_id, 
                cp.total_payout,
                cp.status, 
                DATE_FORMAT(cp.created_at, '%d %b %Y, %h:%i %p') AS date_paid,
                h.hq_name 
            FROM commission_payouts cp
            INNER JOIN mr_users m 
                ON cp.mr_id = m.m_id 
                AND m.hq_id = cp.hq_id 
                AND m.status = '1'
            LEFT JOIN headquarter h 
                ON cp.hq_id = h.headquarter_id
            WHERE cp.hq_id = ? 
            AND cp.commission_type = 'MRC'
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
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
        
        $stmt->close();
        return $data;
    }

    public function getPayoutDetails($payout_id) {
        $details = ['bills' => [], 'adjustments' => []];

        $sql1 = "
            SELECT si.inward_no, si.inward_date, s.stockist_name, si.business_value, si.commission_amount 
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

        // ADDED: AND commission_type = 'MR'
        $stmt_hq = $this->con->prepare("SELECT hq_id FROM commission_payouts WHERE payout_id = ? AND commission_type = 'MRC'");
        $stmt_hq->bind_param("i", $payout_id);
        $stmt_hq->execute();
        $hq_result = $stmt_hq->get_result()->fetch_assoc();
        $stmt_hq->close();

        if (!$hq_result) {
            return false; 
        }
        
        $hq_id = (int)$hq_result['hq_id'];
        $data['hq_id'] = $hq_id;

        $stmt_mr = $this->con->prepare("SELECT commission_rate FROM mr_users WHERE hq_id = ? AND status = '1'");
        $stmt_mr->bind_param("i", $hq_id);
        $stmt_mr->execute();
        $mr_data = $stmt_mr->get_result()->fetch_assoc();
        $stmt_mr->close();
        
        $rate = $mr_data ? (float)$mr_data['commission_rate'] : 0;

        $sql1 = "
            SELECT 
                si.inward_id,
                si.inward_no,
                DATE(si.created_at) as bill_date,
                s.stockist_name,
                si.business_value AS taxable_amount,
                UPPER(si.pay_status) AS pay_status,
                $rate AS commission_percent,
                ROUND((si.business_value * ($rate / 100)), 2) AS commission_amount,
                si.commission_payout_id
            FROM stock_inward si
            INNER JOIN stockists s ON si.stockist_id = s.stockist_id
            WHERE (s.hq_id = ? AND si.mrc = 0) 
               OR (si.commission_payout_id = ?)
            ORDER BY si.created_at DESC
        ";
        
        $stmt1 = $this->con->prepare($sql1);
        $stmt1->bind_param("ii", $hq_id, $payout_id);
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

   public function updateMrCommission($payout_id, $hq_id, $bill_ids_json, $adjustments_json, $final_payout, $status = 'Pending') 
    {
        try {
            $hq_id = (int)$hq_id;

            // Verify payout and preserve the original owner (mr_id)
            $stmt_check = $this->con->prepare("SELECT mr_id, status FROM commission_payouts WHERE payout_id = ? AND commission_type = 'MRC'");
            $stmt_check->bind_param("i", $payout_id);
            $stmt_check->execute();
            $payout_meta = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();
            
            if (!$payout_meta) {
                throw new Exception("MR Payout not found.");
            }
            if ($payout_meta['status'] === 'Paid') {
                throw new Exception("This payout has already been marked as Paid and cannot be edited.");
            }

            $mr_id = (int)$payout_meta['mr_id'];

            $stmt_mr = $this->con->prepare("SELECT commission_rate FROM mr_users WHERE m_id = ?");
            $stmt_mr->bind_param("i", $mr_id);
            $stmt_mr->execute();
            $mr_data = $stmt_mr->get_result()->fetch_assoc();
            $stmt_mr->close();
            
            $rate = $mr_data ? (float)$mr_data['commission_rate'] : 0.00;

            $bill_ids = json_decode($bill_ids_json, true);
            $adjustments = json_decode($adjustments_json, true);

            if (empty($bill_ids) || !is_array($bill_ids)) {
                throw new Exception("You must select at least one bill for this payout.");
            }

            $clean_ids = array_map('intval', $bill_ids);
            $id_string = implode(',', $clean_ids);

            $this->con->begin_transaction();

            $stmt_reset = $this->con->prepare("UPDATE stock_inward SET mrc = 0, commission_payout_id = NULL WHERE commission_payout_id = ?");
            $stmt_reset->bind_param("i", $payout_id);
            $stmt_reset->execute();
            $stmt_reset->close();

            $stmt_del = $this->con->prepare("DELETE FROM commission_adjustments WHERE payout_id = ?");
            $stmt_del->bind_param("i", $payout_id);
            $stmt_del->execute();
            $stmt_del->close();

            $update_payout = "UPDATE commission_payouts SET total_payout = ?, status = ?, commission_rate = ? WHERE payout_id = ? AND commission_type = 'MRC'";
            $stmt_payout = $this->con->prepare($update_payout);
            $stmt_payout->bind_param("dsdi", $final_payout, $status, $rate, $payout_id);
            if (!$stmt_payout->execute()) {
                throw new Exception("Failed to update master payout record.");
            }
            $stmt_payout->close();

            $update_query = "
                UPDATE stock_inward si
                INNER JOIN stockists s ON si.stockist_id = s.stockist_id
                SET si.mrc = 1, si.commission_payout_id = $payout_id
                WHERE si.inward_id IN ($id_string)
                AND s.hq_id = $hq_id
            ";
            if (!$this->con->query($update_query)) {
                throw new Exception("Failed to link new bills to this payout.");
            }

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

            if ($status === 'Paid') {
                $notes = "MR Commission Earned (Payout #$payout_id)";
                $action = 'increase';
                $stockist_id = 0;
                
                $stmt_ledger = $this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (user_id, stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, ?, 'mrc_wallet', 'commission_earned', ?, ?, ?, ?)
                ");
                $stmt_ledger->bind_param("iiidss", $mr_id, $stockist_id, $payout_id, $final_payout, $action, $notes);
                
                if (!$stmt_ledger->execute()) {
                    throw new Exception("Failed to update ledger for MR payout.");
                }
                $stmt_ledger->close();
            }

            $this->con->commit();
            return ['success' => true, 'msg' => 'Payout updated successfully.'];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    // ==========================================================
    // NEW METHOD: Flip the status between Pending and Paid (WITH STRICT ERROR CHECKING)
    // ==========================================================
    // public function updatePayoutStatus($payout_id, $status = 'Paid') 
    // {
    //     try {
    //         $this->con->begin_transaction();

    //         // 1. Get current payout data before updating
    //         $stmt_info = $this->con->prepare("SELECT hq_id, total_payout, status FROM commission_payouts WHERE payout_id = ? AND commission_type = 'MRC' FOR UPDATE");
    //         $stmt_info->bind_param("i", $payout_id);
    //         $stmt_info->execute();
    //         $payout_data = $stmt_info->get_result()->fetch_assoc();
    //         $stmt_info->close();

    //         if (!$payout_data) {
    //             throw new Exception("MR Payout not found.");
    //         }

    //         $current_status = trim($payout_data['status']);
    //         $total_payout = (float)$payout_data['total_payout'];
    //         $hq_id = (int)$payout_data['hq_id']; 
            
    //         // FIX: Fetch the MR ID (m_id) to ensure ledger consistency
    //         $stmt_mr = $this->con->prepare("SELECT m_id FROM mr_users WHERE hq_id = ? LIMIT 1");
    //         $stmt_mr->bind_param("i", $hq_id);
    //         $stmt_mr->execute();
    //         $res_mr = $stmt_mr->get_result()->fetch_assoc();
    //         $stmt_mr->close();
            
    //         $mr_id = $res_mr ? (int)$res_mr['m_id'] : 0; // Captured for the ledger

    //         // 2. Update the payout status
    //         $stmt = $this->con->prepare("UPDATE commission_payouts SET status = ? WHERE payout_id = ? AND commission_type = 'MRC'");
    //         $stmt->bind_param("si", $status, $payout_id);
            
    //         if (!$stmt->execute()) {
    //             throw new Exception("Failed to update payout status: " . $stmt->error);
    //         }
    //         $stmt->close();

    //         // 3. Update the payment_ledgers based on status change (Single Entry Logic)
    //         if ($status === 'Paid' && $current_status !== 'Paid') {
                
    //             $notes = "MR Commission Earned (Payout #$payout_id)";
    //             $action = 'increase';
    //             $stockist_id = 0; // 0 represents a combined master payout

    //             // Prepare the ledger insert 
    //             $stmt_ledger = $this->con->prepare("
    //                 INSERT INTO payment_ledgers 
    //                 (user_id, stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
    //                 VALUES (?, ?, 'mrc_wallet', 'commission_earned', ?, ?, ?, ?)
    //             ");
                
    //             if (!$stmt_ledger) {
    //                 throw new Exception("SQL Error in payment_ledgers: " . $this->con->error);
    //             }

    //             // FIX: Replaced $hq_id with $mr_id
    //             $stmt_ledger->bind_param("iiidss", $mr_id, $stockist_id, $payout_id, $total_payout, $action, $notes);
                
    //             if (!$stmt_ledger->execute()) {
    //                 throw new Exception("Failed to add amount to MR commission ledger: " . $stmt_ledger->error);
    //             }
    //             $stmt_ledger->close();

    //         } elseif ($status !== 'Paid' && $current_status === 'Paid') {
    //             // If it was reverted to Pending/Rejected, remove the wallet credit
    //             $stmt_rev = $this->con->prepare("
    //                 DELETE FROM payment_ledgers 
    //                 WHERE ledger_type = 'mrc_wallet' 
    //                 AND transaction_type = 'commission_earned' 
    //                 AND reference_id = ?
    //             ");
    //             $stmt_rev->bind_param("i", $payout_id);
    //             $stmt_rev->execute();
    //             $stmt_rev->close();
    //         }

    //         $this->con->commit();
    //         return ['success' => true, 'msg' => 'Status updated to ' . $status];

    //     } catch (Exception $e) {
    //         $this->con->rollback();
    //         return ['success' => false, 'msg' => $e->getMessage()];
    //     }
    // }
    // ==========================================================
    // DELETE MR COMMISSION (Protected against ledger corruption)
    // ==========================================================
    public function deleteMrCommission($payout_id)
    {
        try {
            $payout_id = (int)$payout_id;
            $this->con->begin_transaction();

            // 0. STRICT CHECK: Prevent deletion if the payout is already Paid
            $stmt_check = $this->con->prepare("SELECT status FROM commission_payouts WHERE payout_id = ? AND commission_type = 'MRC'");
            $stmt_check->bind_param("i", $payout_id);
            $stmt_check->execute();
            $payout_data = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();

            if (!$payout_data) {
                throw new Exception("MR Payout not found.");
            }
            if ($payout_data['status'] === 'Paid') {
                throw new Exception("Cannot delete this payout because it has already been Paid. To delete, you must first change the status back to 'Pending'.");
            }

            // 1. Remove ledger entry (Acts as a safety net for any orphaned pending entries)
            $stmt_ledger = $this->con->prepare("DELETE FROM payment_ledgers WHERE ledger_type = 'mrc_wallet' AND transaction_type = 'commission_earned' AND reference_id = ?");
            $stmt_ledger->bind_param("i", $payout_id);
            $stmt_ledger->execute();
            $stmt_ledger->close();

            // 2. Unlink all bills (reset mrc to 0)
            $stmt_reset = $this->con->prepare("UPDATE stock_inward SET mrc = 0, commission_payout_id = NULL WHERE commission_payout_id = ?");
            $stmt_reset->bind_param("i", $payout_id);
            $stmt_reset->execute();
            $stmt_reset->close();

            // 3. Delete any manual adjustments tied to this payout
            $stmt_adj = $this->con->prepare("DELETE FROM commission_adjustments WHERE payout_id = ?");
            $stmt_adj->bind_param("i", $payout_id);
            $stmt_adj->execute();
            $stmt_adj->close();

            // 4. Delete the payout record itself
            $stmt_payout = $this->con->prepare("DELETE FROM commission_payouts WHERE payout_id = ? AND commission_type = 'MRC'");
            $stmt_payout->bind_param("i", $payout_id);
            
            if (!$stmt_payout->execute()) {
                throw new Exception("Failed to delete the payout record.");
            }
            $stmt_payout->close();

            $this->con->commit();
            return ['success' => true, 'msg' => 'Commission deleted and bills reset successfully.'];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }
}
?>