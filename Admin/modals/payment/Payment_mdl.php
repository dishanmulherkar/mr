<?php

class PaymentApproval_mdl {
    private $con;

    public function __construct() {
        global $con;
        $this->con = $con;
    }

    public function getStates() {
        if ($_SESSION['admin_role'] == 'Super Admin') {
            return mysqli_query($this->con, "SELECT * FROM state WHERE state_status = 1 ORDER BY state_name");
        }
        $admin_id = (int)$_SESSION['admin_id'];
        return mysqli_query($this->con, "
            SELECT s.* FROM state s
            INNER JOIN admin_state ast ON s.state_id = ast.state_id
            WHERE ast.admin_id = '$admin_id' AND s.state_status = 1
            ORDER BY s.state_name
        ");
    }

    public function getPaymentsForAdmin($status = '') {
        $query = "
            SELECT p.*, s.stockist_name 
            FROM payment_details p
            LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
            WHERE 1=1
        ";
        if (!empty($status)) {
            $query .= " AND p.approval_status = '" . $this->con->real_escape_string($status) . "'";
        }
        $query .= " ORDER BY p.created_at DESC";

        $result = $this->con->query($query);
        $payments = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $payments[] = $row;
            }
        }
        return $payments;
    }

    // Secure Transaction to Approve or Reject a Payment
   // Secure Transaction to Approve or Reject a Payment
    
 public function processApproval($payment_id, $admin_id, $action_status) 
    {
        try {
            $this->con->begin_transaction();

            // ==========================================
            // STEP 1: Fetch Payment Details
            // ==========================================
            $stmt = $this->con->prepare("
                SELECT stockist_id, amount_paid, payment_method 
                FROM payment_details 
                WHERE id = ? AND approval_status = 'pending' 
                FOR UPDATE
            ");
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Payment not found or already processed.");
            }
            
            $payment = $result->fetch_assoc();
            $stockist_id = (int)($payment['stockist_id'] ?? 0);
            $amount_paid = (float)($payment['amount_paid'] ?? 0);
            $payment_method = $payment['payment_method'] ?? 'Unknown';
            $stmt->close();

            // ==========================================
            // STEP 2: Update Payment Status
            // ==========================================
            $stmt = $this->con->prepare("
                UPDATE payment_details 
                SET approval_status = ?, approved_by = ?, approved_at = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param("sii", $action_status, $admin_id, $payment_id);
            $stmt->execute();
            $stmt->close();

            if ($action_status === 'approved') {
                
                // ==========================================
                // STEP 3: Get CD Rules for This Stockist
                // ==========================================
                $cd_4_days = 10;  // Default
                $cd_2_days = 30;  // Default
                
                $stmt_rules = $this->con->prepare("
                    SELECT r.cd_4_percent_days, r.cd_2_percent_days
                    FROM stockists s 
                    INNER JOIN headquarter h ON h.headquarter_id = s.hq_id
                    INNER JOIN super_stockist_cd_rules r ON r.super_stockist_id = h.super_stockist_id
                    WHERE s.stockist_id = ?
                    LIMIT 1
                ");
                $stmt_rules->bind_param("i", $stockist_id);
                $stmt_rules->execute();
                $result_rules = $stmt_rules->get_result();
                
                if ($result_rules->num_rows > 0) {
                    $cd_rules = $result_rules->fetch_assoc();
                    $cd_4_days = (int)($cd_rules['cd_4_percent_days'] ?? 10);
                    $cd_2_days = (int)($cd_rules['cd_2_percent_days'] ?? 30);
                }
                $stmt_rules->close();

                // ==========================================
                // STEP 4: Create Main Payment Ledger Entry
                // ==========================================
                $stmt = $this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, 'debt', 'payment_made', ?, ?, 'decrease', ?)
                ");
                $notes = "Payment Approved: " . $payment_method;
                $stmt->bind_param("isds", $stockist_id, $payment_id, $amount_paid, $notes);
                $stmt->execute();
                $ledger_id = $this->con->insert_id; 
                $stmt->close();

                // ==========================================
                // STEP 5: Fetch All Unpaid Bills
                // ==========================================
                $remaining_payment = $amount_paid;
                
                $stmt_bills = $this->con->prepare("
                    SELECT inward_id, grand_total, paid_amt, inward_no, sub_total, COALESCE(cd_percent, 0) as cd_percent,
                           DATEDIFF(CURDATE(), inward_date) AS age_days
                    FROM stock_inward 
                    WHERE stockist_id = ? AND pay_status != 'paid' 
                    ORDER BY inward_date ASC
                    FOR UPDATE
                ");
                $stmt_bills->bind_param("i", $stockist_id);
                $stmt_bills->execute();
                $unpaid_bills = $stmt_bills->get_result();
                $stmt_bills->close();

                if ($unpaid_bills->num_rows > 0) {
                    
                    // ----------------------------------------------------
                    // SAFELY PREPARE AND BIND ONCE TO AVOID LOOP BUGS
                    // ----------------------------------------------------
                    $stmt_alloc = $this->con->prepare("INSERT INTO payment_allocations (ledger_id, inward_id, amount_allocated) VALUES (?, ?, ?)");
                    $alloc_ledger_id = 0; $alloc_inward_id = 0; $alloc_amount = 0;
                    $stmt_alloc->bind_param("iid", $alloc_ledger_id, $alloc_inward_id, $alloc_amount);

                    $stmt_update = $this->con->prepare("UPDATE stock_inward SET paid_amt = ?, pay_status = ?, grand_total = ?, cd_percent = ? WHERE inward_id = ?");
                    $upd_paid_amt = 0; $upd_status = ""; $upd_grand_total = 0; $upd_cd_percent = 0; $upd_inward_id = 0;
                    $stmt_update->bind_param("dsdii", $upd_paid_amt, $upd_status, $upd_grand_total, $upd_cd_percent, $upd_inward_id);

                    // FIXED: transaction_type is back to 'payment_made' to prevent truncation error
                    $stmt_cd = $this->con->prepare("INSERT INTO payment_ledgers (stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) VALUES (?, 'debt', 'payment_made', ?, ?, 'decrease', ?)");
                    $cd_stockist_id = 0; $cd_inward_id = 0; $cd_amount = 0; $cd_notes = "";
                    $stmt_cd->bind_param("iids", $cd_stockist_id, $cd_inward_id, $cd_amount, $cd_notes);
                    
                    $stmt_penalty = $this->con->prepare("INSERT INTO payment_ledgers (stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) VALUES (?, 'debt', 'bill_added', ?, ?, 'increase', ?)");
                    $pen_stockist_id = 0; $pen_inward_id = 0; $pen_amount = 0; $pen_notes = "";
                    $stmt_penalty->bind_param("iids", $pen_stockist_id, $pen_inward_id, $pen_amount, $pen_notes);

                    // ==========================================
                    // STEP 6: Loop through Pending Bills
                    // ==========================================
                    while ($bill = $unpaid_bills->fetch_assoc()) {
                        if ($remaining_payment <= 0) break;

                        $inward_id = (int)$bill['inward_id'];
                        $grand_total = (float)$bill['grand_total'];
                        $paid_amt = (float)$bill['paid_amt'];
                        $sub_total = (float)$bill['sub_total'];
                        $current_cd_percent = (int)$bill['cd_percent'];
                        $inward_no = $bill['inward_no'];
                        $bill_age_days = (int)$bill['age_days'];
                        
                        if ($sub_total <= 0) { $sub_total = $grand_total; }

                        // A. HANDLE PENALTIES FOR LATE PAYMENTS
                        $penalty_amount = 0;
                        if ($current_cd_percent == 4) {
                            if ($bill_age_days > $cd_4_days && $bill_age_days <= $cd_2_days) {
                                $penalty_amount = round($sub_total * (2 / 96), 2);
                                $current_cd_percent = 2; 
                            } elseif ($bill_age_days > $cd_2_days) {
                                $penalty_amount = round($sub_total * (4 / 96), 2);
                                $current_cd_percent = 0; 
                            }
                        } elseif ($current_cd_percent == 2) {
                            if ($bill_age_days > $cd_2_days) {
                                $penalty_amount = round($sub_total * (2 / 98), 2);
                                $current_cd_percent = 0;
                            }
                        }

                        if ($penalty_amount > 0) {
                            $grand_total = round($grand_total + $penalty_amount, 2);
                            
                            $pen_stockist_id = $stockist_id;
                            $pen_inward_id = $inward_id;
                            $pen_amount = $penalty_amount;
                            $pen_notes = "CD Revoked/Downgraded (Late Payment) for Inv: " . $inward_no;
                            $stmt_penalty->execute();
                        }

                        // B. HANDLE NEW CD FOR PARTIAL OR FULL PAYMENTS
                        if ($current_cd_percent == 0 && $penalty_amount == 0) {
                            $potential_cd = 0;
                            $potential_pct = 0;

                            if ($bill_age_days <= $cd_4_days) {
                                $potential_cd = round($sub_total * 0.04, 2); 
                                $potential_pct = 4;
                            } elseif ($bill_age_days <= $cd_2_days) {
                                $potential_cd = round($sub_total * 0.02, 2); 
                                $potential_pct = 2;
                            }

                            // Verify if remaining payment clears the bill WITH the discount
                            $required_to_clear = round($grand_total - $paid_amt - $potential_cd, 2);

                            if ($potential_cd > 0 && round($remaining_payment, 2) >= $required_to_clear) {
                                
                                $grand_total = round($grand_total - $potential_cd, 2); 
                                $current_cd_percent = $potential_pct;
                                
                                // INSERT CD LEDGER ENTRY WITH EXACT FORMATTING REQUESTED
                                $cd_stockist_id = $stockist_id;
                                $cd_inward_id = $inward_id;
                                $cd_amount = $potential_cd;
                                $cd_notes = "{$potential_pct}% CD on Invoice {$inward_no}: ₹" . number_format($potential_cd, 2);
                                $stmt_cd->execute();
                            }
                        }

                        // C. ALLOCATE PAYMENT
                        $bill_balance = round($grand_total - $paid_amt, 2);

                        if ($bill_balance > 0) {
                            $allocate_amount = min(round($remaining_payment, 2), $bill_balance);
                            
                            $alloc_ledger_id = $ledger_id;
                            $alloc_inward_id = $inward_id;
                            $alloc_amount = $allocate_amount;
                            $stmt_alloc->execute();

                            $new_paid_amt = round($paid_amt + $allocate_amount, 2);
                            $remaining_payment = round($remaining_payment - $allocate_amount, 2);
                        } else {
                            $new_paid_amt = $paid_amt;
                        }

                        // D. UPDATE BILL STATUS
                        $new_status = ($new_paid_amt >= $grand_total) ? 'paid' : 'partial';

                        $upd_paid_amt = $new_paid_amt;
                        $upd_status = $new_status;
                        $upd_grand_total = $grand_total;
                        $upd_cd_percent = $current_cd_percent;
                        $upd_inward_id = $inward_id;
                        $stmt_update->execute();
                    }

                    $stmt_alloc->close();
                    $stmt_update->close();
                    $stmt_cd->close();
                    $stmt_penalty->close();
                }
            }

            $this->con->commit();
            return ['success' => true, 'msg' => 'Payment marked as ' . strtoupper($action_status) . ' successfully.'];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }
    // ==========================================
    // NEW METHODS FOR PAYMENT ENTRY LOGIC
    // ==========================================

    public function getMrsByHq($hq_id) {
        $hq_id = (int)$hq_id;
        $stmt = $this->con->prepare("SELECT m_id, mr_name AS mr_name FROM mr_users WHERE hq_id = ? AND status = '1'");
        $stmt->bind_param("i", $hq_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $data = [];
        while($row = $res->fetch_assoc()) { $data[] = $row; }
        $stmt->close();
        return $data;
    }

    public function getStockistsByHq($hq_id) {
        $hq_id = (int)$hq_id;
        $stmt = $this->con->prepare("SELECT stockist_id, stockist_name FROM stockists WHERE hq_id = ?");
        $stmt->bind_param("i", $hq_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $data = [];
        while($row = $res->fetch_assoc()) { $data[] = $row; }
        $stmt->close();
        return $data;
    }

    public function submitManualEntry($data, $admin_id) 
    {
        try {
            $stockist_id = isset($data['stockist_id']) ? (int)$data['stockist_id'] : 0;
            $mr_id = isset($data['mr_id']) ? (int)$data['mr_id'] : 0;

            $commission_type = $data['commission_type']; 
            $payment_type = $data['payment_type']; 
            $amount = (float)$data['amount'];
            $notes = isset($data['notes']) ? trim($data['notes']) : '';

            $ledger_wallet_type = ($commission_type === 'MRC') ? 'mrc_wallet' : 'drc_wallet';
            $settlement_type = ($commission_type === 'MRC') ? 'mrc_settlement' : 'drc_settlement';
            $comm_short = ($commission_type === 'MRC') ? 'mrc' : 'drc';

            $this->con->begin_transaction();

            // 1. Create receipt record in payment_details (NOW INCLUDES mr_id)
            $payment_method = ($payment_type === 'account') ? ($data['payment_method'] ?? 'Bank Transfer') : 'Commission Adjustment';
            
            $stmt_pd = $this->con->prepare("INSERT INTO payment_details (stockist_id, mr_id, amount_paid, payment_method, bank_details, approval_status, approved_by, approved_at, commission_type) VALUES (?, ?, ?, ?, ?, 'approved', ?, NOW(), ?)");
            
            // bind_param updated to "iidssis" (int, int, double, string, string, int, string)
            $stmt_pd->bind_param("iidssis", $stockist_id, $mr_id, $amount, $payment_method, $notes, $admin_id, $comm_short);
            
            if (!$stmt_pd->execute()) {
                throw new Exception("Failed to record payment details: " . $stmt_pd->error);
            }
            $payment_id = $this->con->insert_id;
            $stmt_pd->close();

            // 2. Perform actions based on payment type
            if ($payment_type === 'old_bill') {
                if ($stockist_id <= 0) throw new Exception("Stockist ID is required to settle an old bill.");
                
                // Deduct from wallet
                $stmt_w = $this->con->prepare("INSERT INTO payment_ledgers (stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) VALUES (?, ?, 'settled_to_bill', ?, ?, 'decrease', ?)");
                $stmt_w->bind_param("isids", $stockist_id, $ledger_wallet_type, $payment_id, $amount, $notes);
                $stmt_w->execute(); $stmt_w->close();

                // Decrease debt 
                $stmt_d = $this->con->prepare("INSERT INTO payment_ledgers (stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) VALUES (?, 'debt', ?, ?, ?, 'decrease', ?)");
                $stmt_d->bind_param("isids", $stockist_id, $settlement_type, $payment_id, $amount, $notes);
                $stmt_d->execute(); $ledger_id = $this->con->insert_id; $stmt_d->close();

                // Allocate to unpaid bills
                $remaining_payment = $amount;
                $stmt_bills = $this->con->prepare("SELECT inward_id, grand_total, paid_amt FROM stock_inward WHERE stockist_id = ? AND pay_status != 'paid' ORDER BY inward_id ASC");
                $stmt_bills->bind_param("i", $stockist_id); $stmt_bills->execute();
                $unpaid_bills = $stmt_bills->get_result(); $stmt_bills->close();

                if ($unpaid_bills->num_rows > 0) {
                    $stmt_alloc = $this->con->prepare("INSERT INTO payment_allocations (ledger_id, inward_id, amount_allocated) VALUES (?, ?, ?)");
                    $stmt_update = $this->con->prepare("UPDATE stock_inward SET paid_amt = ?, pay_status = ? WHERE inward_id = ?");

                    while ($bill = $unpaid_bills->fetch_assoc()) {
                        if ($remaining_payment <= 0) break; 
                        $bill_balance = (float)$bill['grand_total'] - (float)$bill['paid_amt'];
                        $allocate_amount = min($remaining_payment, $bill_balance);
                        $stmt_alloc->bind_param("iid", $ledger_id, $bill['inward_id'], $allocate_amount); $stmt_alloc->execute();
                        $new_paid_amt = (float)$bill['paid_amt'] + $allocate_amount;
                        $new_status = (round($new_paid_amt) >= round((float)$bill['grand_total'])) ? 'paid' : 'partial';
                        $stmt_update->bind_param("dsi", $new_paid_amt, $new_status, $bill['inward_id']); $stmt_update->execute();
                        $remaining_payment -= $allocate_amount;
                    }
                    $stmt_alloc->close(); $stmt_update->close();
                }
            } else {
                $stmt_w = $this->con->prepare("INSERT INTO payment_ledgers (stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) VALUES (?, ?, ?, ?, ?, 'decrease', ?)");
                $stmt_w->bind_param("issids", $stockist_id, $ledger_wallet_type, $settlement_type, $payment_id, $amount, $notes);
                $stmt_w->execute(); $stmt_w->close();
            }

            $this->con->commit();
            return ['success' => true, 'msg' => 'Entry processed successfully.'];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }

    // ==========================================
    // NEW: Calculate total available balance for an HQ
    // ==========================================
   public function getAvailableBalance($hq_id, $type) {
        $ledger_type = ($type === 'MRC') ? 'mrc_wallet' : 'drc_wallet';
        
        // Sum up the wallets, ensuring we include Bank Payouts where stockist_id = 0
        $stmt = $this->con->prepare("
            SELECT 
                SUM(CASE WHEN pl.balance_action = 'increase' THEN pl.amount ELSE -pl.amount END) as total_balance
            FROM payment_ledgers pl
            
            -- Changed to LEFT JOIN to prevent dropping Bank Payouts (stockist_id = 0)
            LEFT JOIN stockists s ON pl.stockist_id = s.stockist_id
            
            -- Trace Bank Payouts to the MR/HQ via payment_details
            LEFT JOIN payment_details pd ON pl.reference_id = pd.id AND pl.stockist_id = 0
            LEFT JOIN mr_users m ON pd.mr_id = m.m_id
            
            -- Match HQ from either the Stockist OR the MR User
            WHERE COALESCE(s.hq_id, m.hq_id) = ? AND pl.ledger_type = ?
        ");
        
        $stmt->bind_param("is", $hq_id, $ledger_type);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $res['total_balance'] ? (float)$res['total_balance'] : 0.00;
    }

    // ==========================================
    // NEW: Calculate unpaid bill total for a stockist
    // ==========================================
   public function getStockistOutstanding($stockist_id) 
    {
        $stockist_id = (int)$stockist_id;
        
        // FIX: Replaced stock_inward query with payment_ledgers query to match the Single Source of Truth
        $stmt = $this->con->prepare("
            SELECT 
                ROUND(COALESCE(SUM(CASE WHEN LOWER(balance_action) IN ('increase', 'increase_debt') OR LOWER(transaction_type) IN ('bill_added', 'opening_balance', 'debit_note') THEN amount ELSE 0 END), 0)) - 
                ROUND(COALESCE(SUM(CASE WHEN LOWER(balance_action) IN ('decrease', 'decrease_debt') OR LOWER(transaction_type) IN ('payment_made', 'credit_note', 'discount', 'payment', 'mrc_settlement', 'drc_settlement', 'settled_to_bill') THEN amount ELSE 0 END), 0)) AS total_outstanding
            FROM payment_ledgers 
            WHERE stockist_id = ? AND ledger_type = 'debt'
        ");
        
        $stmt->bind_param("i", $stockist_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $outstanding = 0.00;
        if ($row = $result->fetch_assoc()) {
            $outstanding = (float)$row['total_outstanding'];
        }
        
        $stmt->close();
        return $outstanding;
    }

    // ==========================================
    // NEW: Fetch Recent Manual Payment Entries
    // ==========================================
    public function getManualPayments($filters = []) {
        $query = "
            SELECT p.*, s.stockist_name 
            FROM payment_details p
            LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
            LEFT JOIN headquarter hq ON s.hq_id = hq.headquarter_id
            WHERE p.commission_type IS NOT NULL 
              AND p.commission_type != '' 
              AND LOWER(p.commission_type) != 'none'
        ";

        // Apply Commission Type Filter (DRC / MRC)
        if (!empty($filters['comm_type'])) {
            $comm = $this->con->real_escape_string(strtolower($filters['comm_type']));
            $query .= " AND LOWER(p.commission_type) = '$comm'";
        }

        // Apply HQ Filter
        if (!empty($filters['hq_id'])) {
            $hq_id = (int)$filters['hq_id'];
            $query .= " AND s.hq_id = $hq_id";
        }

        // Apply State Filter
        if (!empty($filters['state_id'])) {
            $state_id = (int)$filters['state_id'];
            $query .= " AND hq.state_id = $state_id";
        }
        
        // Apply Date Filters
        if (!empty($filters['start_date'])) {
            $start_date = $this->con->real_escape_string($filters['start_date']);
            $query .= " AND DATE(p.created_at) >= '$start_date'";
        }
        if (!empty($filters['end_date'])) {
            $end_date = $this->con->real_escape_string($filters['end_date']);
            $query .= " AND DATE(p.created_at) <= '$end_date'";
        }

        $query .= " ORDER BY p.created_at DESC";

        $result = $this->con->query($query);
        $payments = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $payments[] = $row;
            }
        }
        
        return $payments;
    }

    // ==========================================
    // NEW: Get Payment Details for Edit Page
    // ==========================================
public function getPaymentById($id) {
        $stmt = $this->con->prepare("
            SELECT 
                p.*, 
                -- If stockist exists, use its hq_id. Otherwise, use mr_users hq_id.
                COALESCE(s.hq_id, m.hq_id, '') AS hq_id, 
                -- Trace the state_id from the dynamically found headquarter, fallback to mr_users.state
                COALESCE(hq.state_id, m.state, '') AS state_id 
            FROM payment_details p
            LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
            -- Join mr_users using m_id based on your schema
            LEFT JOIN mr_users m ON p.mr_id = m.m_id
            LEFT JOIN headquarter hq ON COALESCE(s.hq_id, m.hq_id) = hq.headquarter_id
            WHERE p.id = ?
        ");
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result ? $result : [];
    }
    // ==========================================
    // NEW: Reverse Manual Payment Entry Safely
    // ==========================================
    public function reverseManualEntry($payment_id, $admin_id) {
        try {
            $this->con->begin_transaction();

            // 1. Check if already reversed
            $stmt = $this->con->prepare("SELECT approval_status FROM payment_details WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            $payment = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$payment || $payment['approval_status'] === 'reversed') {
                throw new Exception("Payment not found or already reversed.");
            }

            // 2. Find ledgers tied to this payment
            $stmt = $this->con->prepare("SELECT id FROM payment_ledgers WHERE reference_id = ? AND transaction_type IN ('paid_to_bank', 'settled_to_bill', 'mrc_settlement', 'drc_settlement')");
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            $ledgers = $stmt->get_result();
            $stmt->close();

            // 3. Rollback allocations and stock_inward paid amounts
            while ($ledger = $ledgers->fetch_assoc()) {
                $ledger_id = $ledger['id'];

                $stmt_alloc = $this->con->prepare("SELECT inward_id, amount_allocated FROM payment_allocations WHERE ledger_id = ?");
                $stmt_alloc->bind_param("i", $ledger_id);
                $stmt_alloc->execute();
                $allocations = $stmt_alloc->get_result();
                $stmt_alloc->close();

                while ($alloc = $allocations->fetch_assoc()) {
                    $inward_id = $alloc['inward_id'];
                    $amount_alloc = (float)$alloc['amount_allocated'];

                    // Restore stock_inward bill to unpaid/partial
                    $stmt_upd = $this->con->prepare("
                        UPDATE stock_inward 
                        SET paid_amt = paid_amt - ?, 
                            pay_status = CASE WHEN (paid_amt - ?) <= 0.01 THEN 'unpaid' ELSE 'partial' END 
                        WHERE inward_id = ?
                    ");
                    $stmt_upd->bind_param("ddi", $amount_alloc, $amount_alloc, $inward_id);
                    $stmt_upd->execute();
                    $stmt_upd->close();
                }

                // Delete allocations for this ledger
                $this->con->query("DELETE FROM payment_allocations WHERE ledger_id = $ledger_id");
            }

            // 4. Delete the ledgers
            $this->con->query("DELETE FROM payment_ledgers WHERE reference_id = $payment_id AND transaction_type IN ('paid_to_bank', 'settled_to_bill', 'mrc_settlement', 'drc_settlement')");

            // 5. Mark payment as reversed
            $this->con->query("UPDATE payment_details SET approval_status = 'reversed', approved_by = $admin_id WHERE id = $payment_id");

            $this->con->commit();
            return ['success' => true, 'msg' => 'Payment reversed and ledgers rolled back successfully.'];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }

    public function getStockistOutstandingWithCD($stockist_id)
    {
        $stockist_id = (int)$stockist_id;

        /*
         * 1. Get Super Stockist ID
         */
        $stmtSS = $this->con->prepare("
            SELECT h.super_stockist_id
            FROM stockists s 
            INNER JOIN headquarter h ON h.headquarter_id =  s.hq_id
            WHERE s.stockist_id = ?
            LIMIT 1
        ");

        $stmtSS->bind_param("i", $stockist_id);
        $stmtSS->execute();
        $resultSS = $stmtSS->get_result();
        $stockistData = $resultSS->fetch_assoc();
        $stmtSS->close();

        if (!$stockistData) {
            return [
                'total_outstanding' => 0.00,
                'eligible_cd'       => 0.00,
                'total_penalty'     => 0.00,
                'net_payable'       => 0.00,
                'bill_details'      => []
            ];
        }

        $super_stockist_id = (int)$stockistData['super_stockist_id'];

        /*
         * 2. Get CD rules
         */
        $stmtRule = $this->con->prepare("
            SELECT cd_4_percent_days, cd_2_percent_days
            FROM super_stockist_cd_rules
            WHERE super_stockist_id = ?
            LIMIT 1
        ");

        $stmtRule->bind_param("i", $super_stockist_id);
        $stmtRule->execute();
        $resultRule = $stmtRule->get_result();
        $rules = $resultRule->fetch_assoc();
        $stmtRule->close();

        // Default CD rules
        $cd_4_days = isset($rules['cd_4_percent_days']) ? (int)$rules['cd_4_percent_days'] : 10;
        $cd_2_days = isset($rules['cd_2_percent_days']) ? (int)$rules['cd_2_percent_days'] : 30;

        /*
         * 3. Get unpaid bills and calculate Penalties / CD
         */
        $stmt = $this->con->prepare("
            SELECT 
                inward_id,
                inward_no,
                inward_date,
                grand_total AS gross_amount,
                sub_total,
                COALESCE(cd_percent, 0) AS cd_percent,
                paid_amt,
                (grand_total - paid_amt) AS pending_amount,
                DATEDIFF(CURDATE(), inward_date) AS bill_age_days,

                -- ALREADY GIVEN CD (Reverse calculate because sub_total is already discounted)
                CASE 
                    WHEN COALESCE(cd_percent, 0) = 4 THEN ROUND(sub_total * (4 / 96), 2) 
                    ELSE 0 
                END AS already_4_cd,
                
                CASE 
                    WHEN COALESCE(cd_percent, 0) = 2 THEN ROUND(sub_total * (2 / 98), 2) 
                    ELSE 0 
                END AS already_2_cd,

                -- NEW ELIGIBLE 4% CD (Direct calculate because sub_total is NOT discounted yet)
                CASE 
                    WHEN DATEDIFF(CURDATE(), inward_date) <= ? AND COALESCE(cd_percent, 0) = 0 
                    THEN ROUND(sub_total * 0.04, 2) ELSE 0 
                END AS eligible_4_cd,

                -- NEW ELIGIBLE 2% CD (Direct calculate because sub_total is NOT discounted yet)
                CASE 
                    WHEN DATEDIFF(CURDATE(), inward_date) > ? AND DATEDIFF(CURDATE(), inward_date) <= ? AND COALESCE(cd_percent, 0) = 0 
                    THEN ROUND(sub_total * 0.02, 2) ELSE 0 
                END AS eligible_2_cd,

                -- REVOKED PENALTY (Reverse calculating the exact amount they lose)
                CASE
                    WHEN COALESCE(cd_percent, 0) = 4 THEN
                        CASE 
                            WHEN DATEDIFF(CURDATE(), inward_date) <= ? THEN 0 
                            -- Downgrade from 4% to 2%. They lose 2%.
                            WHEN DATEDIFF(CURDATE(), inward_date) <= ? THEN ROUND(sub_total * (2 / 96), 2) 
                            -- Missed 30 days. Lose the full 4%.
                            ELSE ROUND(sub_total * (4 / 96), 2) 
                        END
                    WHEN COALESCE(cd_percent, 0) = 2 THEN
                        CASE
                            WHEN DATEDIFF(CURDATE(), inward_date) <= ? THEN 0
                            -- Missed 30 days. Lose the full 2%.
                            ELSE ROUND(sub_total * (2 / 98), 2)
                        END
                    ELSE 0
                END AS penalty_amount

            FROM stock_inward
            WHERE stockist_id = ? AND pay_status != 'paid'
            ORDER BY inward_date ASC
        ");

        // Bind the 7 parameters required for the CASE statements above
        $stmt->bind_param(
            "iiiiiii",
            $cd_4_days,                  // For eligible_4_cd
            $cd_4_days, $cd_2_days,      // For eligible_2_cd
            $cd_4_days, $cd_2_days,      // For penalty_amount (4% logic)
            $cd_2_days,                  // For penalty_amount (2% logic)
            $stockist_id                 // For WHERE clause
        );

        $stmt->execute();
        $result = $stmt->get_result();

        $total_pending = 0.00;
        $total_eligible_4_cd = 0.00;
        $total_eligible_2_cd = 0.00;
        $total_penalty = 0.00;
        $bills = [];

        while ($row = $result->fetch_assoc()) {
            $pending = (float)$row['pending_amount'];

            if ($pending > 0) {
                $total_pending += $pending;
                $total_eligible_4_cd += (float)$row['eligible_4_cd'];
                $total_eligible_2_cd += (float)$row['eligible_2_cd'];
                $total_penalty += (float)$row['penalty_amount'];
            }
            $bills[] = $row;
        }
        $stmt->close();

        $total_cd = $total_eligible_4_cd + $total_eligible_2_cd;
        
        // Net Payable increases if there is a penalty (revoked CD)
        $net_payable = $total_pending - $total_cd + $total_penalty;

        return [
            'total_outstanding' => round($total_pending, 2),
            'eligible_cd'       => round($total_cd, 2),
            'total_penalty'     => round($total_penalty, 2),
            'net_payable'       => round($net_payable, 2),
            'bill_details'      => $bills
        ];
    }
}
?>