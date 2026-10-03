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

  public function getPaymentsForAdmin($status = '', $state_id = '', $hq_id = '') 
    {
        // Join stockists and headquarter tables to enable state/hq filtering
        $query = "
            SELECT p.*, s.stockist_name 
            FROM payment_details p
            LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
            LEFT JOIN headquarter h ON s.hq_id = h.headquarter_id
            WHERE p.payment_method != 'Commission Adjustment' 
        ";
        // NOTE: We excluded 'Commission Adjustment' so Admin Manual Entries don't show in the MR Approval list.

        if (!empty($status)) {
            $query .= " AND p.approval_status = '" . $this->con->real_escape_string($status) . "'";
        }

        if (!empty($hq_id)) {
            $query .= " AND h.headquarter_id = '" . $this->con->real_escape_string($hq_id) . "'";
        } elseif (!empty($state_id)) {
            // If State is selected but HQ is not, show all HQs in that State
            $query .= " AND h.state_id = '" . $this->con->real_escape_string($state_id) . "'";
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
    
   public function processApproval($payment_id, $admin_id, $action_status) 
    {
        try {
            $this->con->begin_transaction();

            // ==========================================
            // STEP 1: Fetch Payment Details
            // ==========================================
            $stmt = $this->con->prepare("
                SELECT stockist_id, mr_id, amount_paid, payment_method 
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
            $stockist_id    = (int)($payment['stockist_id'] ?? 0);
            $mr_id          = (int)($payment['mr_id'] ?? 0);
            $amount_paid    = (float)($payment['amount_paid'] ?? 0);
            $payment_method = $payment['payment_method'] ?? 'Unknown';
            $stmt->close();

            // Fallback: If mr_id is 0, resolve to the current active MR for this stockist's HQ
            if ($mr_id <= 0 && $stockist_id > 0) {
                $stmt_mrfb = $this->con->prepare("
                    SELECT m.m_id 
                    FROM mr_users m
                    INNER JOIN stockists s ON s.hq_id = m.hq_id
                    WHERE s.stockist_id = ? AND m.status = '1'
                    LIMIT 1
                ");
                $stmt_mrfb->bind_param("i", $stockist_id);
                $stmt_mrfb->execute();
                $mr_row = $stmt_mrfb->get_result()->fetch_assoc();
                $mr_id  = $mr_row ? (int)$mr_row['m_id'] : 0;
                $stmt_mrfb->close();
            }

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
                $cd_4_days = 10;
                $cd_2_days = 30;
                
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
                    (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, ?, 'debt', 'payment_made', ?, ?, 'decrease', ?)
                ");
                $notes = "Payment Approved: " . $payment_method;
                $stmt->bind_param("iiids", $stockist_id, $mr_id, $payment_id, $amount_paid, $notes);
                $stmt->execute();
                $ledger_id = $this->con->insert_id; 
                $stmt->close();

                $remaining_payment = $amount_paid;

                // Prepare the reusable allocation statement
                $stmt_alloc = $this->con->prepare("
                    INSERT INTO payment_allocations (ledger_id, inward_id, amount_allocated) 
                    VALUES (?, ?, ?)
                ");
                $alloc_ledger_id = 0; 
                $alloc_inward_id = 0; 
                $alloc_amount = 0.00;
                $stmt_alloc->bind_param("iid", $alloc_ledger_id, $alloc_inward_id, $alloc_amount);

                // ==============================================================
                // STEP 5: Settle Opening Balance First (FIFO) using inward_id = 0
                // ==============================================================
                $stmt_stk = $this->con->prepare("
                    SELECT opening_balance, opening_balance_type 
                    FROM stockists 
                    WHERE stockist_id = ? 
                    LIMIT 1 
                    FOR UPDATE
                ");
                $stmt_stk->bind_param("i", $stockist_id);
                $stmt_stk->execute();
                $stk_info = $stmt_stk->get_result()->fetch_assoc();
                $stmt_stk->close();

                $ob_amount = (float)($stk_info['opening_balance'] ?? 0);
                $ob_type   = $stk_info['opening_balance_type'] ?? 'debt';

                if ($ob_amount > 0 && $ob_type === 'debt' && $remaining_payment > 0) {
                    // Check how much opening balance has already been settled
                    $stmt_ob_alloc = $this->con->prepare("
                        SELECT COALESCE(SUM(pa.amount_allocated), 0) AS total_ob_paid 
                        FROM payment_allocations pa
                        INNER JOIN payment_ledgers pl ON pl.id = pa.ledger_id
                        WHERE pl.stockist_id = ? 
                        AND pa.inward_id IS NULL
                    ");
                    $stmt_ob_alloc->bind_param("i", $stockist_id);
                    $stmt_ob_alloc->execute();
                    $ob_alloc_res = $stmt_ob_alloc->get_result()->fetch_assoc();
                    $stmt_ob_alloc->close();

                    $already_paid_ob = (float)($ob_alloc_res['total_ob_paid'] ?? 0);
                    $pending_ob = max(0, round($ob_amount - $already_paid_ob, 2));

                    if ($pending_ob > 0) {
                            $alloc_to_ob = min(round($remaining_payment, 2), $pending_ob);

                            // Insert NULL for inward_id so foreign key constraint is satisfied
                            $stmt_alloc_ob = $this->con->prepare("
                                INSERT INTO payment_allocations (ledger_id, inward_id, amount_allocated) 
                                VALUES (?, NULL, ?)
                            ");
                            $stmt_alloc_ob->bind_param("id", $ledger_id, $alloc_to_ob);
                            $stmt_alloc_ob->execute();
                            $stmt_alloc_ob->close();

                            $remaining_payment = round($remaining_payment - $alloc_to_ob, 2);
                        }
                }

                // ==========================================
                // STEP 6: Settle Invoices from stock_inward
                // ==========================================
                if ($remaining_payment > 0) {
                    $stmt_bills = $this->con->prepare("
                        SELECT inward_id, grand_total, paid_amt, inward_no, sub_total, 
                            COALESCE(gst_amount, 0) AS gst_amount, 
                            COALESCE(other_charges, 0) AS other_charges, 
                            COALESCE(discount, 0) AS discount, 
                            COALESCE(cd_percent, 0) AS cd_percent,
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
                        $stmt_update = $this->con->prepare("
                            UPDATE stock_inward 
                            SET paid_amt = ?, 
                                pay_status = ?, 
                                cd_percent = ?, 
                                cd_penalty_amt = cd_penalty_amt + ?, 
                                cd_earned_amt = cd_earned_amt + ? 
                            WHERE inward_id = ?
                        ");
                        $upd_paid_amt = 0; $upd_status = ""; $upd_cd_percent = 0; $upd_penalty = 0; $upd_earned = 0; $upd_inward_id = 0;
                        $stmt_update->bind_param("dsiddi", $upd_paid_amt, $upd_status, $upd_cd_percent, $upd_penalty, $upd_earned, $upd_inward_id);

                        $stmt_cd = $this->con->prepare("
                            INSERT INTO payment_ledgers (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                            VALUES (?, ?, 'debt', 'payment_made', ?, ?, 'decrease', ?)
                        ");
                        $cd_stockist_id = 0; $cd_inward_id = 0; $cd_amount = 0; $cd_notes = "";
                        $stmt_cd->bind_param("iiids", $cd_stockist_id, $mr_id, $cd_inward_id, $cd_amount, $cd_notes);
                        
                        $stmt_penalty = $this->con->prepare("
                            INSERT INTO payment_ledgers (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                            VALUES (?, ?, 'debt', 'bill_added', ?, ?, 'increase', ?)
                        ");
                        $pen_stockist_id = 0; $pen_inward_id = 0; $pen_amount = 0; $pen_notes = "";
                        $stmt_penalty->bind_param("iiids", $pen_stockist_id, $mr_id, $pen_inward_id, $pen_amount, $pen_notes);

                        while ($bill = $unpaid_bills->fetch_assoc()) {
                            if ($remaining_payment <= 0) break;

                            $inward_id          = (int)$bill['inward_id'];
                            $grand_total        = (float)$bill['grand_total'];
                            $paid_amt           = (float)$bill['paid_amt'];
                            $sub_total          = (float)$bill['sub_total'] <= 0 ? $grand_total : (float)$bill['sub_total'];
                            $gst_amount         = (float)$bill['gst_amount'];
                            $other_charges      = (float)$bill['other_charges'];
                            $discount           = (float)$bill['discount'];
                            $current_cd_percent = (int)$bill['cd_percent'];
                            $inward_no          = $bill['inward_no'];
                            $bill_age_days      = (int)$bill['age_days'];
                            
                            $applied_penalty    = 0.00;
                            $applied_earned_cd  = 0.00;

                            // A. PENALTIES FOR LATE PAYMENTS
                            if ($current_cd_percent == 4) {
                                if ($bill_age_days > $cd_4_days && $bill_age_days <= $cd_2_days) {
                                    $target_grand = round(($sub_total * (98/96)) + ($gst_amount * (98/96)) + $other_charges - $discount, 2);
                                    $applied_penalty = max(0, round($target_grand - $grand_total, 2));
                                    $current_cd_percent = 2; 
                                } elseif ($bill_age_days > $cd_2_days) {
                                    $target_grand = round(($sub_total * (100/96)) + ($gst_amount * (100/96)) + $other_charges - $discount, 2);
                                    $applied_penalty = max(0, round($target_grand - $grand_total, 2));
                                    $current_cd_percent = 0; 
                                }
                            } elseif ($current_cd_percent == 2) {
                                if ($bill_age_days > $cd_2_days) {
                                    $target_grand = round(($sub_total * (100/98)) + ($gst_amount * (100/98)) + $other_charges - $discount, 2);
                                    $applied_penalty = max(0, round($target_grand - $grand_total, 2));
                                    $current_cd_percent = 0;
                                }
                            }

                            if ($applied_penalty > 0) {
                                $remaining_payment = round(max(0, $remaining_payment - $applied_penalty), 2);
                                
                                $pen_stockist_id = $stockist_id;
                                $pen_inward_id   = $inward_id;
                                $pen_amount      = $applied_penalty;
                                $pen_notes       = "CD Reversed for " . $inward_no;
                                $stmt_penalty->execute();
                            }

                            // B. NEW CD FOR TIMELY PAYMENTS
                            if ($current_cd_percent == 0 && $applied_penalty == 0) {
                                $potential_cd  = 0.00;
                                $potential_pct = 0;

                                if ($bill_age_days <= $cd_4_days) {
                                    $target_grand  = round(($sub_total * 0.96) + ($gst_amount * 0.96) + $other_charges - $discount, 2);
                                    $potential_cd  = max(0, round($grand_total - $target_grand, 2)); 
                                    $potential_pct = 4;
                                } elseif ($bill_age_days <= $cd_2_days) {
                                    $target_grand  = round(($sub_total * 0.98) + ($gst_amount * 0.98) + $other_charges - $discount, 2);
                                    $potential_cd  = max(0, round($grand_total - $target_grand, 2)); 
                                    $potential_pct = 2;
                                }

                                $required_cash_to_clear = round($grand_total - $paid_amt - $potential_cd, 2);

                                if ($potential_cd > 0 && round($remaining_payment, 2) >= $required_cash_to_clear) {
                                    $applied_earned_cd  = $potential_cd;
                                    $current_cd_percent = $potential_pct;
                                    
                                    $cd_stockist_id = $stockist_id;
                                    $cd_inward_id   = $inward_id;
                                    $cd_amount      = $applied_earned_cd;
                                    $cd_notes       = "{$potential_pct}% CD on Invoice {$inward_no}: ₹" . number_format($applied_earned_cd, 2);
                                    $stmt_cd->execute();
                                }
                            }

                            // C. ALLOCATE CASH PAYMENT
                            $required_cash_balance = round(($grand_total + $applied_penalty) - $paid_amt - $applied_earned_cd, 2);

                            if ($required_cash_balance > 0) {
                                $allocate_amount = min(round($remaining_payment, 2), $required_cash_balance);
                                
                                $alloc_ledger_id = $ledger_id;
                                $alloc_inward_id = $inward_id;
                                $alloc_amount    = $allocate_amount;
                                $stmt_alloc->execute();

                                $new_paid_amt      = round($paid_amt + $allocate_amount, 2);
                                $remaining_payment = round($remaining_payment - $allocate_amount, 2);
                            } else {
                                $new_paid_amt = $paid_amt;
                            }

                            // D. UPDATE INVOICE RECORD
                            $total_cleared_value  = round($new_paid_amt + $applied_earned_cd, 2);
                            $total_bill_liability = round($grand_total + $applied_penalty, 2);
                            
                            $new_status = ($total_cleared_value >= $total_bill_liability) ? 'paid' : 'partial';

                            $upd_paid_amt   = $new_paid_amt;
                            $upd_status     = $new_status;
                            $upd_cd_percent = $current_cd_percent;
                            $upd_penalty    = $applied_penalty;
                            $upd_earned     = $applied_earned_cd;
                            $upd_inward_id  = $inward_id;
                            $stmt_update->execute();
                        }

                        $stmt_update->close();
                        $stmt_cd->close();
                        $stmt_penalty->close();
                    }
                }

                $stmt_alloc->close();
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
            
            // Extract the Entity ID (MR or ASM)
            $mr_id = !empty($data['mr_id']) ? (int)$data['mr_id'] : (!empty($data['asm_id']) ? (int)$data['asm_id'] : 0);

            $commission_type = strtoupper($data['commission_type'] ?? ''); 
            $payment_type = $data['payment_type'] ?? ''; 
            $amount = (float)($data['amount'] ?? 0);
            $notes = isset($data['notes']) ? trim($data['notes']) : '';
            
            // Capture the custom settlement date (defaults to today if missing)
            $settlement_date = !empty($data['settlement_date']) ? date('Y-m-d', strtotime($data['settlement_date'])) : date('Y-m-d');

            // Dynamically assign wallet and settlement types based on the form's commission_type
            $ledger_wallet_type = match($commission_type) {
                'MRC' => 'mrc_wallet',
                'ASM' => 'asm_wallet',
                default => 'drc_wallet'
            };
            
            $settlement_type = match($commission_type) {
                'MRC' => 'mrc_settlement',
                'ASM' => 'asm_settlement',
                default => 'drc_settlement'
            };
            
            $comm_short = strtolower($commission_type); // 'mrc', 'asm', or 'drc'

            $this->con->begin_transaction();

            // 1. Create receipt record in payment_details
            $payment_method = ($payment_type === 'account') ? ($data['payment_method'] ?? 'Bank Transfer') : 'Commission Adjustment';
            
            $stmt_pd = $this->con->prepare("
                INSERT INTO payment_details 
                (stockist_id, mr_id, amount_paid, payment_method, bank_details, approval_status, approved_by, approved_at, commission_type) 
                VALUES (?, ?, ?, ?, ?, 'approved', ?, NOW(), ?)
            ");
            $stmt_pd->bind_param("iidssis", $stockist_id, $mr_id, $amount, $payment_method, $notes, $admin_id, $comm_short);
            
            if (!$stmt_pd->execute()) {
                throw new Exception("Failed to record payment details: " . $stmt_pd->error);
            }
            $payment_id = $this->con->insert_id;
            $stmt_pd->close();

            // 2. Perform actions based on payment type
            if ($payment_type === 'old_bill') {
                if ($stockist_id <= 0) throw new Exception("Stockist ID is required to settle an old bill.");
                
                // Deduct from appropriate wallet (ASM, MRC, or DRC)
                $stmt_w = $this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, ?, ?, 'settled_to_bill', ?, ?, 'decrease', ?)
                ");
                $stmt_w->bind_param("iisids", $stockist_id, $mr_id, $ledger_wallet_type, $payment_id, $amount, $notes);
                $stmt_w->execute(); 
                $stmt_w->close();

                // Decrease debt
                $stmt_d = $this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, ?, 'debt', ?, ?, ?, 'decrease', ?)
                ");
                $stmt_d->bind_param("iisids", $stockist_id, $mr_id, $settlement_type, $payment_id, $amount, $notes);
                $stmt_d->execute(); 
                $ledger_id = $this->con->insert_id; 
                $stmt_d->close();

                // Prepare reusable allocation statement (used for both opening balance and bills)
                $stmt_alloc = $this->con->prepare("
                    INSERT INTO payment_allocations (ledger_id, inward_id, amount_allocated) 
                    VALUES (?, ?, ?)
                ");
                $alloc_ledger_id = 0; $alloc_inward_id = 0; $alloc_amount = 0.00;
                $stmt_alloc->bind_param("iid", $alloc_ledger_id, $alloc_inward_id, $alloc_amount);

                $remaining_payment = $amount;

                // ==========================================
                // STEP 3: Settle Opening Balance First (FIFO)
                // ==========================================
                $stmt_stk = $this->con->prepare("
                    SELECT opening_balance, opening_balance_type 
                    FROM stockists 
                    WHERE stockist_id = ? 
                    LIMIT 1 
                    FOR UPDATE
                ");
                $stmt_stk->bind_param("i", $stockist_id);
                $stmt_stk->execute();
                $stk_info = $stmt_stk->get_result()->fetch_assoc();
                $stmt_stk->close();

                $ob_amount = (float)($stk_info['opening_balance'] ?? 0);
                $ob_type   = $stk_info['opening_balance_type'] ?? 'debt';

                if ($ob_amount > 0 && $ob_type === 'debt' && $remaining_payment > 0) {
                    $stmt_ob_alloc = $this->con->prepare("
                            SELECT COALESCE(SUM(pa.amount_allocated), 0) AS total_ob_paid 
                            FROM payment_allocations pa
                            INNER JOIN payment_ledgers pl ON pl.id = pa.ledger_id
                            WHERE pl.stockist_id = ? 
                            AND pa.inward_id IS NULL
                        ");
                        $stmt_ob_alloc->bind_param("i", $stockist_id);
                        $stmt_ob_alloc->execute();
                        $ob_alloc_res = $stmt_ob_alloc->get_result()->fetch_assoc();
                        $stmt_ob_alloc->close();

                    $already_paid_ob = (float)($ob_alloc_res['total_ob_paid'] ?? 0);
                    $pending_ob = max(0, round($ob_amount - $already_paid_ob, 2));

                    if ($pending_ob > 0) {
                        $alloc_to_ob = min(round($remaining_payment, 2), $pending_ob);

                        // Insert NULL for inward_id so foreign key constraint is satisfied
                        $stmt_alloc_ob = $this->con->prepare("
                            INSERT INTO payment_allocations (ledger_id, inward_id, amount_allocated) 
                            VALUES (?, NULL, ?)
                        ");
                        $stmt_alloc_ob->bind_param("id", $ledger_id, $alloc_to_ob);
                        $stmt_alloc_ob->execute();
                        $stmt_alloc_ob->close();

                        $remaining_payment = round($remaining_payment - $alloc_to_ob, 2);
                    }
                }

                // ==========================================
                // STEP 4: Settle Pending Bills (stock_inward)
                // ==========================================
                if ($remaining_payment > 0) {
                    // CD Rules
                    $cd_4_days = 10;
                    $cd_2_days = 30;
                    
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

                    // Fetch All Unpaid Bills (Using Custom Date)
                    $stmt_bills = $this->con->prepare("
                        SELECT inward_id, grand_total, paid_amt, inward_no, sub_total, 
                            COALESCE(gst_amount, 0) as gst_amount, 
                            COALESCE(other_charges, 0) as other_charges, 
                            COALESCE(discount, 0) as discount,
                            COALESCE(cd_percent, 0) as cd_percent,
                            DATEDIFF(?, inward_date) AS age_days
                        FROM stock_inward 
                        WHERE stockist_id = ? AND pay_status != 'paid' 
                        ORDER BY inward_date ASC
                        FOR UPDATE
                    ");
                    $stmt_bills->bind_param("si", $settlement_date, $stockist_id);
                    $stmt_bills->execute();
                    $unpaid_bills = $stmt_bills->get_result(); 
                    $stmt_bills->close();

                    if ($unpaid_bills->num_rows > 0) {
                        $stmt_update = $this->con->prepare("
                            UPDATE stock_inward 
                            SET paid_amt = ?, 
                                pay_status = ?, 
                                cd_percent = ?, 
                                cd_penalty_amt = cd_penalty_amt + ?, 
                                cd_earned_amt = cd_earned_amt + ? 
                            WHERE inward_id = ?
                        ");
                        $upd_paid_amt = 0; $upd_status = ""; $upd_cd_percent = 0; $upd_penalty = 0; $upd_earned = 0; $upd_inward_id = 0;
                        $stmt_update->bind_param("dsiddi", $upd_paid_amt, $upd_status, $upd_cd_percent, $upd_penalty, $upd_earned, $upd_inward_id);

                        $stmt_cd = $this->con->prepare("
                            INSERT INTO payment_ledgers 
                            (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                            VALUES (?, ?, 'debt', 'payment_made', ?, ?, 'decrease', ?)
                        ");
                        $cd_stockist_id = 0; $cd_inward_id = 0; $cd_amount = 0; $cd_notes = "";
                        $stmt_cd->bind_param("iiids", $cd_stockist_id, $mr_id, $cd_inward_id, $cd_amount, $cd_notes);
                        
                        $stmt_penalty = $this->con->prepare("
                            INSERT INTO payment_ledgers 
                            (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                            VALUES (?, ?, 'debt', 'bill_added', ?, ?, 'increase', ?)
                        ");
                        $pen_stockist_id = 0; $pen_inward_id = 0; $pen_amount = 0; $pen_notes = "";
                        $stmt_penalty->bind_param("iiids", $pen_stockist_id, $mr_id, $pen_inward_id, $pen_amount, $pen_notes);

                        while ($bill = $unpaid_bills->fetch_assoc()) {
                            if ($remaining_payment <= 0) break; 
                            
                            $inward_id = (int)$bill['inward_id'];
                            $grand_total = (float)$bill['grand_total']; 
                            $paid_amt = (float)$bill['paid_amt'];
                            $sub_total = (float)$bill['sub_total'];
                            $gst_amount = (float)$bill['gst_amount'];
                            $other_charges = (float)$bill['other_charges'];
                            $discount = (float)$bill['discount'];
                            $current_cd_percent = (int)$bill['cd_percent'];
                            $inward_no = $bill['inward_no'];
                            $bill_age_days = (int)$bill['age_days'];
                            
                            if ($sub_total <= 0) { $sub_total = $grand_total; }
                            
                            $applied_penalty = 0;
                            $applied_earned_cd = 0;

                            // A. HANDLE PENALTIES FOR LATE PAYMENTS
                            if ($current_cd_percent == 4) {
                                if ($bill_age_days > $cd_4_days && $bill_age_days <= $cd_2_days) {
                                    $target_grand = round(($sub_total * (98/96)) + ($gst_amount * (98/96)) + $other_charges - $discount);
                                    $applied_penalty = max(0, $target_grand - $grand_total);
                                    $current_cd_percent = 2; 
                                } elseif ($bill_age_days > $cd_2_days) {
                                    $target_grand = round(($sub_total * (100/96)) + ($gst_amount * (100/96)) + $other_charges - $discount);
                                    $applied_penalty = max(0, $target_grand - $grand_total);
                                    $current_cd_percent = 0; 
                                }
                            } elseif ($current_cd_percent == 2) {
                                if ($bill_age_days > $cd_2_days) {
                                    $target_grand = round(($sub_total * (100/98)) + ($gst_amount * (100/98)) + $other_charges - $discount);
                                    $applied_penalty = max(0, $target_grand - $grand_total);
                                    $current_cd_percent = 0;
                                }
                            }

                            if ($applied_penalty > 0) {
                                $remaining_payment = round(max(0, $remaining_payment - $applied_penalty), 2);
                                
                                $pen_stockist_id = $stockist_id;
                                $pen_inward_id = $inward_id;
                                $pen_amount = $applied_penalty;
                                $pen_notes = "CD Reversed for " . $inward_no;
                                $stmt_penalty->execute();
                            }

                            // B. HANDLE NEW CD FOR TIMELY PAYMENTS
                            if ($current_cd_percent == 0 && $applied_penalty == 0) {
                                $potential_cd = 0;
                                $potential_pct = 0;

                                if ($bill_age_days <= $cd_4_days) {
                                    $target_grand = round(($sub_total * 0.96) + ($gst_amount * 0.96) + $other_charges - $discount);
                                    $potential_cd = max(0, $grand_total - $target_grand); 
                                    $potential_pct = 4;
                                } elseif ($bill_age_days <= $cd_2_days) {
                                    $target_grand = round(($sub_total * 0.98) + ($gst_amount * 0.98) + $other_charges - $discount);
                                    $potential_cd = max(0, $grand_total - $target_grand); 
                                    $potential_pct = 2;
                                }

                                $required_cash_to_clear = round($grand_total - $paid_amt - $potential_cd, 2);

                                if ($potential_cd > 0 && round($remaining_payment, 2) >= $required_cash_to_clear) {
                                    $applied_earned_cd = $potential_cd;
                                    $current_cd_percent = $potential_pct;
                                    
                                    $cd_stockist_id = $stockist_id;
                                    $cd_inward_id = $inward_id;
                                    $cd_amount = $applied_earned_cd;
                                    $cd_notes = "{$potential_pct}% CD on Invoice {$inward_no}: ₹" . number_format($applied_earned_cd, 2);
                                    $stmt_cd->execute();
                                }
                            }

                            // C. ALLOCATE CASH PAYMENT
                            $required_cash_balance = round(($grand_total + $applied_penalty) - $paid_amt - $applied_earned_cd, 2);

                            if ($required_cash_balance > 0) {
                                $allocate_amount = min(round($remaining_payment, 2), $required_cash_balance);
                                
                                $alloc_ledger_id = $ledger_id;
                                $alloc_inward_id = $inward_id;
                                $alloc_amount = $allocate_amount;
                                $stmt_alloc->execute();

                                $new_paid_amt = round($paid_amt + $allocate_amount, 2);
                                $remaining_payment = round($remaining_payment - $allocate_amount, 2);
                            } else {
                                $new_paid_amt = $paid_amt;
                            }

                            // D. UPDATE INVOICE RECORD
                            $total_cleared_value = round($new_paid_amt + $applied_earned_cd, 2);
                            $total_bill_liability = round($grand_total + $applied_penalty, 2);
                            
                            $new_status = ($total_cleared_value >= $total_bill_liability) ? 'paid' : 'partial';

                            $upd_paid_amt = $new_paid_amt;
                            $upd_status = $new_status;
                            $upd_cd_percent = $current_cd_percent;
                            $upd_penalty = $applied_penalty; 
                            $upd_earned = $applied_earned_cd; 
                            $upd_inward_id = $inward_id;
                            $stmt_update->execute();
                        }
                        
                        $stmt_update->close();
                        $stmt_cd->close();
                        $stmt_penalty->close();
                    }
                }

                $stmt_alloc->close(); 

            } else {
                // Direct Transfer (Not settling bills)
                $stmt_w = $this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, 'decrease', ?)
                ");
                $stmt_w->bind_param("iissids", $stockist_id, $mr_id, $ledger_wallet_type, $settlement_type, $payment_id, $amount, $notes);
                $stmt_w->execute(); 
                $stmt_w->close();
            }

            $this->con->commit();
            return ['success' => true, 'msg' => 'Entry processed successfully.'];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }
      // ==========================================
    // Calculate total available balance for an HQ
    // ==========================================
   public function getAvailableBalance($hq_id, $type) 
    {
        $hq_id = (int)$hq_id;

        // Define ledger type dynamically
        $ledger_type = match($type) {
            'MRC' => 'mrc_wallet',
            'ASM' => 'asm_wallet',
            default => 'drc_wallet'
        };
        
        if ($type === 'ASM') {
            // For ASM: The $hq_id passed is actually the Admin ID (asm_id).
            $stmt = $this->con->prepare("
                SELECT 
                    SUM(CASE WHEN pl.balance_action = 'increase' THEN pl.amount ELSE -pl.amount END) AS total_balance
                FROM payment_ledgers pl
                LEFT JOIN stockists s ON pl.stockist_id = s.stockist_id
                LEFT JOIN headquarter h ON s.hq_id = h.headquarter_id
                WHERE (h.asm_id = ? OR pl.user_id = ?) AND pl.ledger_type = ?
            ");
            
            $stmt->bind_param("iis", $hq_id, $hq_id, $ledger_type);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            return $res && $res['total_balance'] !== null ? round((float)$res['total_balance'], 2) : 0.00;

        } else {
            // 1. Fetch ONLY the CURRENT ACTIVE MR for this HQ
            $stmt_mr = $this->con->prepare("SELECT m_id FROM mr_users WHERE hq_id = ? AND status = '1' LIMIT 1");
            $stmt_mr->bind_param("i", $hq_id);
            $stmt_mr->execute();
            $res_mr = $stmt_mr->get_result()->fetch_assoc();
            $stmt_mr->close();
            
            $mr_id = $res_mr ? (int)$res_mr['m_id'] : 0;

            if ($mr_id <= 0) {
                return 0.00;
            }

            // 2. Strict User-Level calculation (No HQ/Stockist bleeding)
            $stmt = $this->con->prepare("
                SELECT 
                    SUM(CASE 
                        WHEN pl.balance_action = 'increase' THEN pl.amount 
                        ELSE -pl.amount 
                    END) AS total_balance
                FROM payment_ledgers pl
                WHERE pl.user_id = ? 
                AND pl.ledger_type = ?
            ");
            
            $stmt->bind_param("is", $mr_id, $ledger_type);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            return $res && $res['total_balance'] !== null ? round((float)$res['total_balance'], 2) : 0.00;
        }
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
    public function getManualPayments($filters = []) 
    {
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
    public function getPaymentById($id) 
    {
      $stmt = $this->con->prepare("
            SELECT 
                p.*, 
                s.stockist_name,
                s.hq_id AS specific_hq_id, 
                hq.hq_name,
                a.admin_name AS asm_name,
                
                -- If stockist exists, use its hq_id. Otherwise, use mr_users hq_id.
                COALESCE(s.hq_id, m.hq_id, '') AS hq_id, 
                
                -- Trace the state_id from the dynamically found headquarter, fallback to mr_users,
                -- or look up the state from the headquarter table if it is an ASM
                COALESCE(
                    hq.state_id, 
                    m.state, 
                    (SELECT state_id FROM headquarter WHERE asm_id = a.admin_id LIMIT 1), 
                    ''
                ) AS state_id 
                
            FROM payment_details p
            LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
            
            -- Join mr_users ONLY if it is an MRC or DRC payment
            LEFT JOIN mr_users m ON p.mr_id = m.m_id AND LOWER(p.commission_type) != 'asm'
            
            -- Join admins table ONLY if it is an ASM payment (since asm_id is stored in mr_id)
            LEFT JOIN admins a ON p.mr_id = a.admin_id AND LOWER(p.commission_type) = 'asm'
            
            -- Fetch Headquarter details from either the Stockist or the MR
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
  public function reverseManualEntry($payment_id, $admin_id) 
    {
        try {
            $this->con->begin_transaction();

            $payment_id = (int)$payment_id;
            $admin_id   = (int)$admin_id;

            // 1. Check if payment exists and can be reversed
            $stmt = $this->con->prepare("SELECT approval_status FROM payment_details WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            $payment = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$payment || $payment['approval_status'] === 'reversed') {
                throw new Exception("Payment not found or already reversed.");
            }

            // 2. Find all ledgers tied to this payment
            $stmt = $this->con->prepare("
                SELECT id 
                FROM payment_ledgers 
                WHERE reference_id = ? 
                  AND transaction_type IN ('paid_to_bank', 'settled_to_bill', 'mrc_settlement', 'drc_settlement', 'asm_settlement')
            ");
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            $ledgers = $stmt->get_result();
            $stmt->close();

            // Prepare update statement for real stock_inward invoices
            $stmt_upd = $this->con->prepare("
                UPDATE stock_inward 
                SET paid_amt = GREATEST(0, paid_amt - ?), 
                    pay_status = CASE WHEN (paid_amt - ?) <= 0.01 THEN 'unpaid' ELSE 'partial' END 
                WHERE inward_id = ?
            ");

            // 3. Rollback allocations and stock_inward paid amounts
            while ($ledger = $ledgers->fetch_assoc()) {
                $ledger_id = (int)$ledger['id'];

                $stmt_alloc = $this->con->prepare("SELECT inward_id, amount_allocated FROM payment_allocations WHERE ledger_id = ?");
                $stmt_alloc->bind_param("i", $ledger_id);
                $stmt_alloc->execute();
                $allocations = $stmt_alloc->get_result();
                $stmt_alloc->close();

                while ($alloc = $allocations->fetch_assoc()) {
                    $inward_id = !empty($alloc['inward_id']) ? (int)$alloc['inward_id'] : null;
                    $amount_alloc = (float)$alloc['amount_allocated'];

                    // ONLY update stock_inward if it's a real invoice!
                    // If $inward_id is NULL or 0, it was allocated to Opening Balance.
                    // Deleting the allocation row below automatically restores the pending Opening Balance.
                    if (!empty($inward_id) && $inward_id > 0) {
                        $stmt_upd->bind_param("ddi", $amount_alloc, $amount_alloc, $inward_id);
                        $stmt_upd->execute();
                    }
                }

                // Delete allocations for this ledger
                $stmt_del_alloc = $this->con->prepare("DELETE FROM payment_allocations WHERE ledger_id = ?");
                $stmt_del_alloc->bind_param("i", $ledger_id);
                $stmt_del_alloc->execute();
                $stmt_del_alloc->close();
            }
            $stmt_upd->close();

            // 4. Delete the ledger entries tied to this payment
            $stmt_del_pl = $this->con->prepare("
                DELETE FROM payment_ledgers 
                WHERE reference_id = ? 
                  AND transaction_type IN ('paid_to_bank', 'settled_to_bill', 'mrc_settlement', 'drc_settlement', 'asm_settlement')
            ");
            $stmt_del_pl->bind_param("i", $payment_id);
            $stmt_del_pl->execute();
            $stmt_del_pl->close();

            // 5. Mark payment as reversed
            $stmt_rev = $this->con->prepare("UPDATE payment_details SET approval_status = 'reversed', approved_by = ? WHERE id = ?");
            $stmt_rev->bind_param("ii", $admin_id, $payment_id);
            $stmt_rev->execute();
            $stmt_rev->close();

            $this->con->commit();
            return ['success' => true, 'msg' => 'Payment reversed and ledgers rolled back successfully.'];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }

    public function getStockistOutstandingWithCD($stockist_id, $settlement_date = null)
    {
        $stockist_id = (int)$stockist_id;
        
        // Default to today if no date is passed, otherwise ensure valid Y-m-d format
        if (empty($settlement_date)) {
            $safe_date = date('Y-m-d');
        } else {
            $safe_date = date('Y-m-d', strtotime($settlement_date));
        }

        /*
        * 1. Get Super Stockist ID and Opening Balance Info
        */
        $stmtSS = $this->con->prepare("
            SELECT 
                h.super_stockist_id,
                s.opening_balance,
                s.opening_balance_type,
                s.opening_balance_date
            FROM stockists s 
            INNER JOIN headquarter h ON h.headquarter_id = s.hq_id
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
        * 2. Calculate Pending Opening Balance
        */
        $ob_amount = (float)($stockistData['opening_balance'] ?? 0);
        $ob_type   = $stockistData['opening_balance_type'] ?? 'debt';
        $ob_date   = !empty($stockistData['opening_balance_date']) ? $stockistData['opening_balance_date'] : $safe_date;
        $pending_ob = 0.00;

        if ($ob_amount > 0 && $ob_type === 'debt') {
            // Fetch total already allocated against the opening balance (inward_id = 0)
            $stmt_ob_alloc = $this->con->prepare("
                SELECT COALESCE(SUM(pa.amount_allocated), 0) AS total_ob_paid 
                FROM payment_allocations pa
                INNER JOIN payment_ledgers pl ON pl.id = pa.ledger_id
                WHERE pl.stockist_id = ?  AND pa.inward_id IS NULL
            ");
            $stmt_ob_alloc->bind_param("i", $stockist_id);
            $stmt_ob_alloc->execute();
            $ob_res = $stmt_ob_alloc->get_result()->fetch_assoc();
            $stmt_ob_alloc->close();

            $already_paid_ob = (float)($ob_res['total_ob_paid'] ?? 0);
            $pending_ob = max(0, round($ob_amount - $already_paid_ob, 2));
        }

        /*
        * 3. Get CD rules
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

        $cd_4_days = isset($rules['cd_4_percent_days']) ? (int)$rules['cd_4_percent_days'] : 10;
        $cd_2_days = isset($rules['cd_2_percent_days']) ? (int)$rules['cd_2_percent_days'] : 30;

        /*
        * 4. Get unpaid bills and calculate Exact Penalties / CD
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
                DATEDIFF(?, inward_date) AS bill_age_days,

                -- ALREADY GIVEN 4% CD
                CASE 
                    WHEN COALESCE(cd_percent, 0) = 4 THEN 
                        ROUND((sub_total * 100/96) + (COALESCE(gst_amount, 0) * 100/96) + COALESCE(other_charges, 0) - COALESCE(discount, 0)) - grand_total
                    ELSE 0 
                END AS already_4_cd,
                
                -- ALREADY GIVEN 2% CD
                CASE 
                    WHEN COALESCE(cd_percent, 0) = 2 THEN 
                        ROUND((sub_total * 100/98) + (COALESCE(gst_amount, 0) * 100/98) + COALESCE(other_charges, 0) - COALESCE(discount, 0)) - grand_total
                    ELSE 0 
                END AS already_2_cd,

                -- NEW ELIGIBLE 4% CD
                CASE 
                    WHEN DATEDIFF(?, inward_date) <= ? AND COALESCE(cd_percent, 0) = 0 
                    THEN grand_total - ROUND((sub_total * 0.96) + (COALESCE(gst_amount, 0) * 0.96) + COALESCE(other_charges, 0) - COALESCE(discount, 0)) 
                    ELSE 0 
                END AS eligible_4_cd,

                -- NEW ELIGIBLE 2% CD
                CASE 
                    WHEN DATEDIFF(?, inward_date) > ? AND DATEDIFF(?, inward_date) <= ? AND COALESCE(cd_percent, 0) = 0 
                    THEN grand_total - ROUND((sub_total * 0.98) + (COALESCE(gst_amount, 0) * 0.98) + COALESCE(other_charges, 0) - COALESCE(discount, 0)) 
                    ELSE 0 
                END AS eligible_2_cd,

                -- REVOKED PENALTY
                CASE
                    WHEN COALESCE(cd_percent, 0) = 4 THEN
                        CASE 
                            WHEN DATEDIFF(?, inward_date) <= ? THEN 0 
                            WHEN DATEDIFF(?, inward_date) <= ? THEN 
                                ROUND((sub_total * 98/96) + (COALESCE(gst_amount, 0) * 98/96) + COALESCE(other_charges, 0) - COALESCE(discount, 0)) - grand_total
                            ELSE 
                                ROUND((sub_total * 100/96) + (COALESCE(gst_amount, 0) * 100/96) + COALESCE(other_charges, 0) - COALESCE(discount, 0)) - grand_total
                        END
                    WHEN COALESCE(cd_percent, 0) = 2 THEN
                        CASE
                            WHEN DATEDIFF(?, inward_date) <= ? THEN 0
                            ELSE 
                                ROUND((sub_total * 100/98) + (COALESCE(gst_amount, 0) * 100/98) + COALESCE(other_charges, 0) - COALESCE(discount, 0)) - grand_total
                        END
                    ELSE 0
                END AS penalty_amount

            FROM stock_inward
            WHERE stockist_id = ? AND pay_status != 'paid'
            ORDER BY inward_date ASC
        ");

        $stmt->bind_param(
            "sssissiisiiisi",
            $safe_date,                  // 1. bill_age_days
            $safe_date, $cd_4_days,      // 2, 3. eligible_4_cd
            $safe_date, $cd_4_days,      // 4, 5. eligible_2_cd (lower bound)
            $safe_date, $cd_2_days,      // 6, 7. eligible_2_cd (upper bound)
            $safe_date, $cd_4_days,      // 8, 9. penalty 4% (on time)
            $safe_date, $cd_2_days,      // 10, 11. penalty 4% (downgraded to 2%)
            $safe_date, $cd_2_days,      // 12, 13. penalty 2%
            $stockist_id                 // 14. WHERE stockist_id
        );

        $stmt->execute();
        $result = $stmt->get_result();

        $total_pending = 0.00;
        $total_eligible_4_cd = 0.00;
        $total_eligible_2_cd = 0.00;
        $total_penalty = 0.00;
        $bills = [];

        // Prepend Opening Balance item if active debt exists
        if ($pending_ob > 0) {
            $total_pending += $pending_ob;
            $bills[] = [
                'inward_id'          => 0,
                'inward_no'          => 'OPENING-BAL',
                'inward_date'        => $ob_date,
                'gross_amount'       => $ob_amount,
                'sub_total'          => $ob_amount,
                'cd_percent'         => 0,
                'paid_amt'           => round($ob_amount - $pending_ob, 2),
                'pending_amount'     => $pending_ob,
                'bill_age_days'      => 0,
                'already_4_cd'       => 0,
                'already_2_cd'       => 0,
                'eligible_4_cd'      => 0,
                'eligible_2_cd'      => 0,
                'penalty_amount'     => 0,
                'is_opening_balance' => 1
            ];
        }

        while ($row = $result->fetch_assoc()) {
            $pending = (float)$row['pending_amount'];

            if ($pending > 0) {
                $total_pending += $pending;
                $total_eligible_4_cd += (float)$row['eligible_4_cd'];
                $total_eligible_2_cd += (float)$row['eligible_2_cd'];
                $total_penalty += (float)$row['penalty_amount'];
            }
            $row['is_opening_balance'] = 0;
            $bills[] = $row;
        }
        $stmt->close();

        $total_cd = $total_eligible_4_cd + $total_eligible_2_cd;
        $net_payable = $total_pending - $total_cd + $total_penalty;

        return [
            'total_outstanding' => round($total_pending, 2),
            'eligible_cd'       => round($total_cd, 2),
            'total_penalty'     => round($total_penalty, 2),
            'net_payable'       => round($net_payable, 2),
            'bill_details'      => $bills
        ];
    }

public function getPaymentAllocations($payment_id) {
    // Find which bills this specific payment ID was allocated to by checking the master debt ledger.
    // We check ledger_type = 'debt' and balance_action = 'decrease' so it catches both 
    // standard 'payment_made' (from MR approvals) and 'mrc_settlement'/'drc_settlement' (from manual entries).
    
    $sql = "
        SELECT 
            si.inward_no, 
            si.inward_date, 
            pa.amount_allocated,
            si.cd_earned_amt AS cd_earned,
            si.cd_penalty_amt AS cd_revoked,
            si.grand_total,    -- Added for Manual Entry Edit View
            si.paid_amt        -- Added for Manual Entry Edit View
        FROM payment_allocations pa
        INNER JOIN payment_ledgers pl ON pa.ledger_id = pl.id
        INNER JOIN stock_inward si ON pa.inward_id = si.inward_id
        WHERE pl.reference_id = ? 
        AND pl.ledger_type = 'debt' 
        AND pl.balance_action = 'decrease'
    ";
    
    $stmt = $this->con->prepare($sql);
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    
    return $data;
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

        public function getHQByASM($asm_id) {
        $asm_id = (int)$asm_id;
        $stmt = $this->con->prepare("SELECT headquarter_id, hq_name FROM `headquarter` WHERE asm_id = ? ");
        $stmt->bind_param("i", $asm_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $data = [];
        while($row = $res->fetch_assoc()) { $data[] = $row; }
        $stmt->close();
        return $data;
    }


    public function getAsmManualPayments($filters = []) 
    {
        // Join admins table using p.mr_id (which stores the asm_id)
        $query = "
            SELECT p.*, s.stockist_name, a.admin_name as asm_name
            FROM payment_details p
            LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
            LEFT JOIN admins a ON p.mr_id = a.admin_id
            WHERE LOWER(p.commission_type) = 'asm'
        ";

        // Apply ASM Filter (Frontend passes ASM ID as 'hq_id')
        if (!empty($filters['hq_id'])) {
            $asm_id = (int)$filters['hq_id'];
            $query .= " AND p.mr_id = $asm_id";
        }

        // Apply State Filter directly on the admins table
        // if (!empty($filters['state_id'])) {
        //     $state_id = (int)$filters['state_id'];
        //     $query .= " AND a.state_id = $state_id";
        // }
        
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

    //   public function getHQbyAsm($asm_id)
    // {
    //     return mysqli_query(
    //         $this->con,
    //         "SELECT * FROM headquarter where asm_id = '$asm_id'"
    //     );
    // }

    // public function getStockistsByHQ($hq_id)
    // {
    //     // Prevent SQL Injection
    //     $hq_id = mysqli_real_escape_string($this->con, $hq_id);
        
    //     // Adjust 'stockist' table name and column names to match your actual database schema
    //     return mysqli_query(
    //         $this->con,
    //         "SELECT stockist_id,stockist_name 
    //         FROM stockists 
    //         WHERE hq_id = '$hq_id' 
    //         ORDER BY stockist_name ASC"
    //     );
    // }


   public function getReport($stockist_id, $from_date, $to_date)
    {
        $sql = "SELECT 
                    pl.*, 
                    si.inward_no, 
                    pd.id as pay_id, 
                    s.stockist_name, 
                    pd.payment_method, 
                    b.bank_name
                FROM payment_ledgers pl 
                LEFT JOIN stock_inward si 
                    ON si.inward_id = pl.reference_id 
                    AND pl.transaction_type = 'bill_added'
                LEFT JOIN payment_details pd 
                    ON pd.id = pl.reference_id 
                    AND pl.transaction_type = 'payment_made'
                LEFT JOIN stockists s 
                    ON pl.stockist_id = s.stockist_id
                LEFT JOIN banks b 
                    ON b.bank_id = pd.bank_id
                WHERE pl.stockist_id = ? 
                  AND pl.ledger_type = 'debt'
                  -- ADDED 'opening_balance' HERE
                  AND pl.transaction_type IN ('opening_balance', 'bill_added', 'payment_made', 'mrc_settlement', 'drc_settlement', 'settled_to_bill', 'asm_settlement')
                  AND DATE(pl.created_at) >= ? 
                  AND DATE(pl.created_at) <= ?
                ORDER BY pl.created_at ASC, pl.id ASC";
                
        // Secured with Prepared Statements
        $stmt = $this->con->prepare($sql);
        $stmt->bind_param("iss", $stockist_id, $from_date, $to_date);
        $stmt->execute();
        
        return $stmt->get_result();
    }

    // Opening balance calculation filtered for the same transaction types
    public function getOpeningBalance($stockist_id, $from_date)
    {
        // Computes net outstanding brought forward prior to $from_date
        $sql = "SELECT 
                    SUM(CASE WHEN balance_action = 'increase' THEN amount ELSE 0 END) as total_inc,
                    SUM(CASE WHEN balance_action = 'decrease' THEN amount ELSE 0 END) as total_dec
                FROM payment_ledgers 
                WHERE stockist_id = ? 
                  -- ADDED 'opening_balance' HERE
                  AND transaction_type IN ('opening_balance', 'bill_added', 'payment_made', 'mrc_settlement', 'drc_settlement', 'settled_to_bill', 'asm_settlement')
                  AND DATE(created_at) < ?";
        
        // Secured with Prepared Statements
        $stmt = $this->con->prepare($sql);
        $stmt->bind_param("is", $stockist_id, $from_date);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res && $row = $res->fetch_assoc()) {
            $inc = (float)$row['total_inc'];
            $dec = (float)$row['total_dec'];
            
            $stmt->close();
            return round($inc - $dec, 2); // Positive = Debt/Receivable, Negative = Credit/Advance
        }
        
        if (isset($stmt)) {
            $stmt->close();
        }
        
        return 0.00;
    }
}
?>