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

            $stmt = $this->con->prepare("SELECT admin_id, commission_rate FROM admins WHERE admin_id = ?");
            $stmt->bind_param("i", $asm_id);
            $stmt->execute();
            $mr_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $rate = (float)$mr_data['commission_rate'];
            
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
            $stmt_payout = $this->con->prepare("INSERT INTO commission_payouts (hq_id, commission_type, total_payout, commission_rate, status) VALUES (?, 'ASM', ?, ?, ?)");
            
            // Fixed bind_param: added 'd' for double/float and passed the $rate variable
            $stmt_payout->bind_param("idds", $asm_id, $final_payout, $rate, $status);
            
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
                SET si.asm_com = 1, si.commission_asm_payout_id = $payout_id
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

            // 4. LEDGER UPDATE: Single entry for the whole payout
            if ($status === 'Paid') {
                
                $notes = "ASM Commission Earned (Payout #$payout_id)";
                $action = 'increase';
                $stockist_id = 0; // 0 because this is a combined master payout
                
                // Insert exactly ONE row containing the $final_payout amount
                $stmt_ledger = $this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (user_id, stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, ?, 'asm_wallet', 'commission_earned', ?, ?, ?, ?)
                ");

                $stmt_ledger->bind_param("iiidss", $asm_id, $stockist_id, $payout_id, $final_payout, $action, $notes);
                
                if (!$stmt_ledger->execute()) {
                    throw new Exception("Failed to update ledger for ASM payout.");
                }

                $stmt_ledger->close();
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
            WHERE si.commission_asm_payout_id = ?
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
                si.commission_asm_payout_id
            FROM stock_inward si
            INNER JOIN stockists s ON si.stockist_id = s.stockist_id
            INNER JOIN headquarter h ON s.hq_id = h.headquarter_id
            WHERE (h.asm_id = ? AND si.pay_status = 'paid' AND si.asm_com = 0) 
               OR (si.commission_asm_payout_id = ?)
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

            // Fetch the CURRENT ASM rate to lock it in for this update
            $stmt_asm = $this->con->prepare("SELECT commission_rate FROM admins WHERE admin_id = ?");
            $stmt_asm->bind_param("i", $asm_id);
            $stmt_asm->execute();
            $asm_data = $stmt_asm->get_result()->fetch_assoc();
            $stmt_asm->close();
            $rate = $asm_data ? (float)$asm_data['commission_rate'] : 0.00;

            $bill_ids = json_decode($bill_ids_json, true);
            $adjustments = json_decode($adjustments_json, true);

            if (empty($bill_ids) || !is_array($bill_ids)) {
                throw new Exception("You must select at least one bill for this payout.");
            }

            $clean_ids = array_map('intval', $bill_ids);
            $id_string = implode(',', $clean_ids);

            $this->con->begin_transaction();

            // 1. Reset old linked bills
            $reset_bills = "UPDATE stock_inward SET asm_com = 0, commission_asm_payout_id = NULL WHERE commission_asm_payout_id = ?";
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

            // 3. Update Master Payout Record (UPDATED: Added commission_rate)
            $update_payout = "UPDATE commission_payouts SET total_payout = ?, status = ?, commission_rate = ? WHERE payout_id = ? AND commission_type = 'ASM'";
            $stmt_payout = $this->con->prepare($update_payout);
            $stmt_payout->bind_param("dsdi", $final_payout, $status, $rate, $payout_id);
            if (!$stmt_payout->execute()) {
                throw new Exception("Failed to update master payout record.");
            }
            $stmt_payout->close();

            // 4. Link new bills
            $update_query = "
                UPDATE stock_inward si
                INNER JOIN stockists s ON si.stockist_id = s.stockist_id
                INNER JOIN headquarter h ON s.hq_id = h.headquarter_id
                SET si.asm_com = 1, si.commission_asm_payout_id = $payout_id
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

            // 6. LEDGER UPDATE: Single entry (No more splitting!)
            if ($status === 'Paid') {
                $notes = "ASM Commission Earned (Payout #$payout_id)";
                $action = 'increase';
                $stockist_id = 0; // 0 represents a combined master payout
                
                $stmt_ledger = $this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (user_id, stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, ?, 'asm_wallet', 'commission_earned', ?, ?, ?, ?)
                ");
                
                $stmt_ledger->bind_param("iiidss", $asm_id, $stockist_id, $payout_id, $final_payout, $action, $notes);
                
                if (!$stmt_ledger->execute()) {
                    throw new Exception("Failed to apply commission to ASM ledger.");
                }
                $stmt_ledger->close();
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

            // 1. Get current payout data before updating 
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

            // 3. Update the payment_ledgers based on status change (Single Entry Logic)
            if ($status === 'Paid' && $current_status !== 'Paid') {
                
                $notes = "ASM Commission Earned (Payout #$payout_id)";
                $action = 'increase';
                $stockist_id = 0; // 0 represents a combined master payout
                
                $stmt_ledger = $this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (user_id, stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, ?, 'asm_wallet', 'commission_earned', ?, ?, ?, ?)
                ");

                if (!$stmt_ledger) {
                    throw new Exception("SQL Error in payment_ledgers: " . $this->con->error);
                }

                $stmt_ledger->bind_param("iiidss", $asm_id, $stockist_id, $payout_id, $total_payout, $action, $notes);
                
                if (!$stmt_ledger->execute()) {
                    throw new Exception("Failed to add amount to ASM ledger.");
                }
                $stmt_ledger->close();

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
            $stmt_reset = $this->con->prepare("UPDATE stock_inward SET asm_com = 0, commission_asm_payout_id = NULL WHERE commission_asm_payout_id = ?");
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


 // ========================================================
    // Fetch Live Wallet Balances (Using Trusted Query Format)
    // ========================================================
    public function getWalletBalances($asm_id)
    {
        $asm_id = (int)$asm_id;
        $asm_balance = 0.00;
        
        // Ensure we have a valid ASM ID before querying
        if ($asm_id > 0) { 
            
            // Get ASM Balance directly from payment_ledgers without stockist/MR joins
            // UPDATED: Changed WHERE hq_id = ? to WHERE user_id = ?
            $stmt = $this->con->prepare("
                SELECT SUM(CASE WHEN balance_action = 'increase' THEN amount ELSE -amount END) as total_balance
                FROM payment_ledgers 
                WHERE user_id = ? AND ledger_type = 'asm_wallet'
            ");
            
            $stmt->bind_param("i", $asm_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            
            $asm_balance = $res['total_balance'] ? (float)$res['total_balance'] : 0.00;
            $stmt->close();
        }
        
        return [
            'asm_balance' => $asm_balance
        ];
    }


        // ========================================================
    // NEW: Fetch Commission Payouts for the Logged In asm
    // ========================================================
   public function getDrCommissionsList($mr_id, $stockist_id = 0, $from_date = '')
    {
        // 1. SELECT AND FROM (No WHERE clause here)
        $sql = "
            SELECT DISTINCT
                cp.payout_id, 
                cp.total_payout, 
                cp.status, 
                DATE_FORMAT(cp.created_at, '%d %b %Y') AS date_paid,
                cp.created_at
            FROM commission_payouts cp
            INNER JOIN admins a ON a.admin_id = cp.hq_id 
        ";
        
        // 2. ALL JOINS MUST HAPPEN BEFORE THE 'WHERE' CLAUSE
        if ($stockist_id > 0) {
            $sql .= " INNER JOIN stock_inward si ON si.commission_asm_payout_id = cp.payout_id ";
        }

        // 3. START THE SINGLE 'WHERE' CLAUSE
        $sql .= " WHERE cp.commission_type = 'ASM' AND a.admin_id = ?";
        
        $params = [$mr_id];
        $types = "i";

        // 4. APPEND ADDITIONAL CONDITIONS WITH 'AND'
        if ($stockist_id > 0) {
            $sql .= " AND si.stockist_id = ?";
            $params[] = $stockist_id;
            $types .= "i";
        }

        if (!empty($from_date)) {
            // Using DATE() to match only the specific day
            $sql .= " AND DATE(cp.created_at) = ?";
            $params[] = $from_date;
            $types .= "s";
        }
        
        $sql .= " ORDER BY cp.created_at DESC";

        $stmt = $this->con->prepare($sql);
        
        // Dynamic binding
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        $stmt->close();
        return $data;
    }


    // ========================================================
    // Fetch Detailed View Data for a specific Payout
    // ========================================================
 public function getAsmCommissionViewData($payout_id, $asm_id) {
        $payout_id = (int)$payout_id;
        $asm_id = (int)$asm_id;
        
        // 1. Get Master Payout Data 
        // (This now includes the frozen 'commission_rate' saved during payout creation)
        $stmt = $this->con->prepare("
            SELECT cp.*, DATE_FORMAT(cp.created_at, '%d %b %Y, %h:%i %p') as payout_date
            FROM commission_payouts cp
            INNER JOIN admins a ON a.admin_id = cp.hq_id
            WHERE cp.payout_id = ? AND a.admin_id = ? AND cp.commission_type = 'ASM'
        ");
        $stmt->bind_param("ii", $payout_id, $asm_id);
        $stmt->execute();
        $master = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$master) return false;

        $data = ['payout' => $master, 'bills' => [], 'adjustments' => []];

        // Extract the locked-in historical rate (Fallback to 0 if missing for old records)
        $historical_rate = isset($master['commission_rate']) ? (float)$master['commission_rate'] : 0.00;

        // 2. Get Linked Bills & calculate PTS using the HISTORICAL rate
        // (Removed the JOIN to admins since we no longer want the live rate)
        $sql = "
            SELECT si.inward_no, DATE_FORMAT(si.created_at, '%d %b %Y') as bill_date, 
                   s.stockist_name, si.sub_total as taxable_amount,
                   ? as pts,
                   ROUND((si.sub_total * (? / 100)), 2) as commission_amount
            FROM stock_inward si
            INNER JOIN stockists s ON si.stockist_id = s.stockist_id
            INNER JOIN headquarter h ON s.hq_id = h.headquarter_id
            WHERE si.commission_asm_payout_id = ?
        ";
        
        $stmt_bills = $this->con->prepare($sql);
        
        // Bind the historical rate twice (once for display 'pts', once for math), then the ID
        $stmt_bills->bind_param("ddi", $historical_rate, $historical_rate, $payout_id);
        
        $stmt_bills->execute();
        $res_bills = $stmt_bills->get_result();
        
        $bill_total = 0;
        while($row = $res_bills->fetch_assoc()) {
            $data['bills'][] = $row;
            $bill_total += (float)$row['commission_amount'];
        }
        $data['payout']['bill_total'] = $bill_total;
        $stmt_bills->close();

        // 3. Get Adjustments
        $stmt_adj = $this->con->prepare("
            SELECT description, adj_type, amount
            FROM commission_adjustments
            WHERE payout_id = ?
        ");
        $stmt_adj->bind_param("i", $payout_id);
        $stmt_adj->execute();
        $res_adj = $stmt_adj->get_result();
        
        $adj_total = 0;
        while($row = $res_adj->fetch_assoc()) {
            $data['adjustments'][] = $row;
            if ($row['adj_type'] === '+') {
                $adj_total += (float)$row['amount'];
            } else {
                $adj_total -= (float)$row['amount'];
            }
        }
        $data['payout']['adj_total'] = $adj_total;
        $stmt_adj->close();

        return $data;
    }

}
?>