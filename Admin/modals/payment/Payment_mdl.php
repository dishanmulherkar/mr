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
                SELECT p.*, s.stockist_name, b.bank_name, h.hq_name
                FROM payment_details p
                LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
                LEFT JOIN headquarter h ON s.hq_id = h.headquarter_id
                LEFT JOIN banks b ON p.bank_id = b.bank_id
                WHERE p.payment_method NOT IN ('Commission Adjustment', 'Manual Adjustment')
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

        $query .= " ORDER BY p.payment_date DESC";

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
    
   public function processApproval($payment_id,$admin_id, $action_status,$remarks = null) 
    {
        try {
            $this->con->begin_transaction();

            // ==========================================
            // STEP 1: Fetch Payment Details
            // ==========================================
            $stmt =$this->con->prepare("
                SELECT stockist_id, mr_id, amount_paid, payment_method, payment_date
                FROM payment_details 
                WHERE id = ? AND approval_status = 'pending' 
                FOR UPDATE
            ");
            $stmt->bind_param("i", $payment_id);$stmt->execute();
            $result =$stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Payment not found or already processed.");
            }
            
            $payment        =$result->fetch_assoc();
            $stockist_id    = (int)($payment['stockist_id'] ?? 0);
            $mr_id          = (int)($payment['mr_id'] ?? 0);
            $amount_paid    = (float)($payment['amount_paid'] ?? 0);
            $payment_method =$payment['payment_method'] ?? 'Unknown';
            $payment_date   = !empty($payment['payment_date']) ? date('Y-m-d', strtotime($payment['payment_date'])) : date('Y-m-d');$stmt->close();

            // Fallback: If mr_id is 0, resolve to the current active MR for this stockist's HQ
            if ($mr_id <= 0 &&$stockist_id > 0) {
                $stmt_mrfb =$this->con->prepare("
                    SELECT m.m_id 
                    FROM mr_users m
                    INNER JOIN stockists s ON s.hq_id = m.hq_id
                    WHERE s.stockist_id = ? AND m.status = '1'
                    LIMIT 1
                ");
                $stmt_mrfb->bind_param("i", $stockist_id);
                $stmt_mrfb->execute();$mr_row = $stmt_mrfb->get_result()->fetch_assoc();$mr_id  = $mr_row ? (int)$mr_row['m_id'] : 0;
                $stmt_mrfb->close();
            }

            // ==========================================
            // STEP 2: Update Payment Status & Save Remarks
            // ==========================================
            $stmt =$this->con->prepare("
                UPDATE payment_details 
                SET approval_status = ?, 
                    approved_by = ?, 
                    approved_at = NOW(),
                    remarks = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sisi", $action_status,$admin_id, $remarks,$payment_id);
            $stmt->execute();$stmt->close();

            // ==========================================
            // STEP 3: Log to Activity Logs (Runs for BOTH Approved & Rejected)
            // ==========================================
            $is_approved   = ($action_status === 'approved');
            $act_type      =$is_approved ? 'PAYMENT_APPROVED' : 'PAYMENT_REJECTED';
            $act_title     =$is_approved ? 'Payment Approved' : 'Payment Rejected';
            $act_priority  =$is_approved ? 'normal' : 'high'; // Rejections stay until clicked

            // Retrieve stockist name for cleaner notification text
            $stk_res       =$this->con->query("SELECT stockist_name FROM stockists WHERE stockist_id = " . (int)$stockist_id);$stk_row       = $stk_res ? $stk_res->fetch_assoc() : null;
            $stockist_name = !empty($stk_row['stockist_name']) ?$stk_row['stockist_name'] : 'Stockist';

            $formatted_amt = number_format($amount_paid, 2);$act_message   = "Payment of ₹{$formatted_amt} for {$stockist_name} is " . ($is_approved ? "approved." : "rejected.");
            if (!empty($remarks)) {
                $act_message .= " Remarks: " . $remarks;
            }

            $target_user   = ($mr_id > 0) ? (int)$mr_id : null;

            $stmt_act =$this->con->prepare("
                INSERT INTO activity_logs (user_id, activity_type, title, message, reference_id, amount, priority, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())
            ");
            $stmt_act->bind_param("isssids", $target_user, $act_type,$act_title, $act_message,$payment_id, $amount_paid,$act_priority);
            $stmt_act->execute();$stmt_act->close();

            // ==========================================
            // STEP 4: Financial Settlements (ONLY when Approved)
            // ==========================================
            if ($action_status === 'approved') {
                
                // 4.1 Create Main Payment Ledger Entry
                $stmt =$this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, ?, 'debt', 'payment_made', ?, ?, 'decrease', ?)
                ");
                
                $notes = "Payment Approved: " . $payment_method;
                if (!empty($remarks)) {
                    $notes .= " | Remarks: " . $remarks;
                }

                $stmt->bind_param("iiids", $stockist_id,$mr_id, $payment_id,$amount_paid, $notes);
                $stmt->execute();
                $ledger_id =$this->con->insert_id; 
                $stmt->close();

                $remaining_payment =$amount_paid;

                // 4.2 Settle Opening Balance Debt FIRST
                if ($remaining_payment > 0) {
                    $stmt_stk =$this->con->prepare("
                        SELECT opening_balance, opening_balance_type 
                        FROM stockists 
                        WHERE stockist_id = ? 
                        LIMIT 1 
                        FOR UPDATE
                    ");
                    $stmt_stk->bind_param("i", $stockist_id);
                    $stmt_stk->execute();$stk_info = $stmt_stk->get_result()->fetch_assoc();$stmt_stk->close();

                    $ob_amount = (float)($stk_info['opening_balance'] ?? 0);
                    $ob_type   = strtolower($stk_info['opening_balance_type'] ?? 'debt');

                    if ($ob_amount > 0 &&$ob_type === 'debt') {
                        $stmt_ob_alloc =$this->con->prepare("
                            SELECT COALESCE(SUM(pa.amount_allocated), 0) AS total_ob_paid 
                            FROM payment_allocations pa
                            INNER JOIN payment_ledgers pl ON pl.id = pa.ledger_id
                            WHERE pl.stockist_id = ? AND (pa.inward_id IS NULL OR pa.inward_id = 0)
                        ");
                        $stmt_ob_alloc->bind_param("i", $stockist_id);
                        $stmt_ob_alloc->execute();$ob_alloc_res = $stmt_ob_alloc->get_result()->fetch_assoc();$stmt_ob_alloc->close();

                        $already_paid_ob = (float)($ob_alloc_res['total_ob_paid'] ?? 0);$pending_ob      = max(0.00, round($ob_amount -$already_paid_ob, 2));

                        if ($pending_ob > 0) {$alloc_to_ob = min(round($remaining_payment, 2),$pending_ob);

                            $stmt_alloc_ob =$this->con->prepare("
                                INSERT INTO payment_allocations (ledger_id, inward_id, amount_allocated) 
                                VALUES (?, NULL, ?)
                            ");
                            $stmt_alloc_ob->bind_param("id", $ledger_id,$alloc_to_ob);
                            $stmt_alloc_ob->execute();$stmt_alloc_ob->close();

                            $remaining_payment = round($remaining_payment -$alloc_to_ob, 2);
                        }
                    }
                }

                // 4.3 Settle Inward Bills (Only If Cash Remains)
                if ($remaining_payment > 0) { // Fetch CD Rules$cd_4_days = 10;
                    $cd_2_days = 30;
                    
                    $stmt_rules =$this->con->prepare("
                        SELECT r.cd_4_percent_days, r.cd_2_percent_days
                        FROM stockists s 
                        INNER JOIN headquarter h ON h.headquarter_id = s.hq_id
                        INNER JOIN super_stockist_cd_rules r ON r.super_stockist_id = h.super_stockist_id
                        WHERE s.stockist_id = ?
                        LIMIT 1
                    ");
                    $stmt_rules->bind_param("i", $stockist_id);$stmt_rules->execute();
                    $result_rules =$stmt_rules->get_result();
                    
                    if ($result_rules->num_rows > 0) {$cd_rules  = $result_rules->fetch_assoc();$cd_4_days = (int)($cd_rules['cd_4_percent_days'] ?? 10);$cd_2_days = (int)($cd_rules['cd_2_percent_days'] ?? 30); }$stmt_rules->close();

                    // Fetch Unpaid Bills with order dispatch fallback
                    $stmt_bills =$this->con->prepare("
                        SELECT 
                            si.inward_id, 
                            si.grand_total, 
                            si.paid_amt, 
                            si.inward_no, 
                            si.sub_total, 
                            COALESCE(si.business_value, si.sub_total, 0) AS business_value,
                            COALESCE(si.cd_percent, 0) AS cd_percent,
                            o.dispatch_date,
                            DATEDIFF(?, COALESCE(o.dispatch_date, si.inward_date)) AS age_days
                        FROM stock_inward si
                        LEFT JOIN `orders` o ON si.order_id = o.order_id
                        WHERE si.stockist_id = ? AND LOWER(si.pay_status) != 'paid' 
                        ORDER BY COALESCE(o.dispatch_date, si.inward_date) ASC
                        FOR UPDATE
                    ");
                    $stmt_bills->bind_param("si", $payment_date,$stockist_id);
                    $stmt_bills->execute();$unpaid_bills = $stmt_bills->get_result();$stmt_bills->close();

                    if ($unpaid_bills->num_rows > 0) {
                        $stmt_alloc =$this->con->prepare("
                            INSERT INTO payment_allocations (ledger_id, inward_id, amount_allocated) 
                            VALUES (?, ?, ?)
                        ");
                        $alloc_ledger_id =$ledger_id; 
                        $alloc_inward_id = 0; 
                        $alloc_amount    = 0.00;
                        $stmt_alloc->bind_param("iid", $alloc_ledger_id, $alloc_inward_id,$alloc_amount);

                        $stmt_update =$this->con->prepare("
                            UPDATE stock_inward 
                            SET paid_amt = ?, 
                                pay_status = ?, 
                                cd_percent = ?, 
                                cd_penalty_amt = cd_penalty_amt + ?, 
                                cd_earned_amt = cd_earned_amt + ? 
                            WHERE inward_id = ?
                        ");
                        $upd_paid_amt = 0; $upd_status = ""; $upd_cd_percent = 0; $upd_penalty = 0; $upd_earned = 0; $upd_inward_id = 0;
                        $stmt_update->bind_param("dsiddi", $upd_paid_amt,$upd_status, $upd_cd_percent,$upd_penalty, $upd_earned,$upd_inward_id);

                        $stmt_cd =$this->con->prepare("
                            INSERT INTO payment_ledgers (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                            VALUES (?, ?, 'debt', 'payment_made', ?, ?, 'decrease', ?)
                        ");
                        $cd_stockist_id = 0; $cd_inward_id = 0; $cd_amount = 0; $cd_notes = "";
                        $stmt_cd->bind_param("iiids", $cd_stockist_id, $mr_id,$cd_inward_id, $cd_amount,$cd_notes);
                        
                        $stmt_penalty =$this->con->prepare("
                            INSERT INTO payment_ledgers (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                            VALUES (?, ?, 'debt', 'bill_added', ?, ?, 'increase', ?)
                        ");
                        $pen_stockist_id = 0; $pen_inward_id = 0; $pen_amount = 0; $pen_notes = "";
                        $stmt_penalty->bind_param("iiids", $pen_stockist_id, $mr_id,$pen_inward_id, $pen_amount,$pen_notes);

                        while ($bill =$unpaid_bills->fetch_assoc()) {
                            if ($remaining_payment <= 0) break;

                            $inward_id   = (int)$bill['inward_id'];
                            $grand_total = (float)$bill['grand_total'];
                            $paid_amt    = (float)$bill['paid_amt'];
                            
                            $biz_value = (float)($bill['business_value'] ?? 0);
                            if ($biz_value <= 0) {
                                $biz_value = (float)($bill['sub_total'] ?? 0) > 0 ? (float)$bill['sub_total'] :$grand_total;
                            }

                            $current_cd_percent = (int)$bill['cd_percent'];$inward_no          = $bill['inward_no'];$bill_age_days      = (int)$bill['age_days'];$applied_penalty    = 0.00;
                            $applied_earned_cd  = 0.00;

                            // Late CD Penalty
                            if ($current_cd_percent == 4) {
                                if ($bill_age_days > $cd_4_days &&$bill_age_days <= $cd_2_days) {$applied_penalty    = round($biz_value * 0.02, 2);$current_cd_percent = 2; 
                                } elseif ($bill_age_days >$cd_2_days) {
                                    $applied_penalty    = round($biz_value * 0.04, 2);
                                    $current_cd_percent = 0; } } elseif ($current_cd_percent == 2) {
                                if ($bill_age_days > $cd_2_days) {$applied_penalty    = round($biz_value * 0.02, 2);$current_cd_percent = 0;
                                }
                            }

                            if ($applied_penalty > 0) {$remaining_payment = round(max(0, $remaining_payment -$applied_penalty), 2);
                                
                                $pen_stockist_id =$stockist_id;
                                $pen_inward_id   =$inward_id;
                                $pen_amount      =$applied_penalty;
                                $pen_notes       = "CD Reversed for {$inward_no} [PID:{$payment_id}]";
                                $stmt_penalty->execute();
                            }

                            // Earned Cash Discount (Allows 1.00 tolerance)
                            if ($current_cd_percent == 0 && $applied_penalty == 0) {$potential_cd  = 0.00;
                                $potential_pct = 0;

                                if ($bill_age_days <=$cd_4_days) {
                                    $potential_cd  = round($biz_value * 0.04, 2);
                                    $potential_pct = 4; } elseif ($bill_age_days <= $cd_2_days) {$potential_cd  = round($biz_value * 0.02, 2);$potential_pct = 2;
                                }

                                $required_cash_to_clear = round($grand_total - $paid_amt -$potential_cd, 2);

                                if ($potential_cd > 0 && ($remaining_payment >= ($required_cash_to_clear - 1.00))) {
                                    $applied_earned_cd  =$potential_cd;
                                    $current_cd_percent =$potential_pct;
                                    
                                    $cd_stockist_id =$stockist_id;
                                    $cd_inward_id   =$inward_id;
                                    $cd_amount      =$applied_earned_cd;
                                    $cd_notes       = "{$potential_pct}% CD on Invoice {$inward_no} [PID:{$payment_id}]: ₹" . number_format($applied_earned_cd, 2);$stmt_cd->execute();
                                }
                            }

                            // Allocate Cash Payment
                            $required_cash_balance = round(($grand_total +$applied_penalty) - $paid_amt -$applied_earned_cd, 2);

                            if ($required_cash_balance > 0) {
                                if ($remaining_payment >= ($required_cash_balance - 1.00)) {$allocate_amount = min(round($remaining_payment, 2),$required_cash_balance);
                                } else {
                                    $allocate_amount = min(round($remaining_payment, 2),$required_cash_balance);
                                }
                                
                                $alloc_inward_id =$inward_id;
                                $alloc_amount    =$allocate_amount;
                                $stmt_alloc->execute();

                                $new_paid_amt      = round($paid_amt + $allocate_amount, 2);$remaining_payment = round($remaining_payment -$allocate_amount, 2);
                            } else {
                                $new_paid_amt =$paid_amt;
                            }

                            // Update Invoice Record (Force close on fractional difference)
                            $total_cleared_value  = round($new_paid_amt +$applied_earned_cd, 2);
                            $total_bill_liability = round($grand_total + $applied_penalty, 2);$diff_remaining       = round($total_bill_liability -$total_cleared_value, 2);

                            if ($diff_remaining <= 1.00 &&$diff_remaining >= 0.00 && $total_cleared_value > 0) {$new_status   = 'paid';
                                $new_paid_amt = round($total_bill_liability - $applied_earned_cd, 2); } else {$new_status = ($total_cleared_value >=$total_bill_liability) ? 'paid' : 'partial';
                            }

                            $upd_paid_amt   =$new_paid_amt;
                            $upd_status     =$new_status;
                            $upd_cd_percent =$current_cd_percent;
                            $upd_penalty    =$applied_penalty;
                            $upd_earned     =$applied_earned_cd;
                            $upd_inward_id  =$inward_id;
                            $stmt_update->execute();
                        }

                        $stmt_alloc->close();$stmt_update->close();
                        $stmt_cd->close();$stmt_penalty->close();
                    }
                }
            }

            $this->con->commit();
            return [
                'success' => true, 
                'msg'     => 'Payment marked as ' . strtoupper($action_status) . ' successfully.' . (isset($remaining_payment) && $remaining_payment > 0 ? " (₹" . round($remaining_payment, 2) . " unallocated advance)" : '')
            ];

        } catch (Exception $e) {$this->con->rollback();
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

  public function submitManualEntry($data,$admin_id) 
    {
        try {
            $stockist_id = isset($data['stockist_id']) ? (int)$data['stockist_id'] : 0;  // Extract the Entity ID (MR or ASM)$mr_id = !empty($data['mr_id']) ? (int)$data['mr_id'] : (!empty($data['asm_id']) ? (int)$data['asm_id'] : 0);

            $commission_type = strtoupper($data['commission_type'] ?? ''); 
            $payment_type    =$data['payment_type'] ?? ''; 
            $amount          = (float)($data['amount'] ?? 0);
            $notes           = isset($data['notes']) ? trim($data['notes']) : '';
            
            // Capture custom settlement date (defaults to today if missing)
            $settlement_date = !empty($data['settlement_date']) ? date('Y-m-d', strtotime($data['settlement_date'])) : date('Y-m-d');

            if ($stockist_id <= 0 || $amount <= 0) {
                    throw new Exception("Stockist ID and a valid positive amount are required.");
                }

              $mr_id = !empty($data['mr_id']) ? (int)$data['mr_id'] : (!empty($data['asm_id']) ? (int)$data['asm_id'] : 0);
            // Dynamically assign wallet and settlement types
            $ledger_wallet_type = match($commission_type) {
                'MRC'   => 'mrc_wallet',
                'ASM'   => 'asm_wallet',
                default => 'drc_wallet'
            };
            
            $settlement_type = match($commission_type) {
                'MRC'   => 'mrc_settlement',
                'ASM'   => 'asm_settlement',
                default => 'drc_settlement'
            };
            
            $comm_short = strtolower($commission_type); // 'mrc', 'asm', or 'drc'

            $this->con->begin_transaction();

            // 1. Resolve active MR if not provided
            if ($mr_id <= 0) {
                $stmt_mrfb =$this->con->prepare("
                    SELECT m.m_id 
                    FROM mr_users m
                    INNER JOIN stockists s ON s.hq_id = m.hq_id
                    WHERE s.stockist_id = ? AND m.status = '1'
                    LIMIT 1
                ");
                $stmt_mrfb->bind_param("i", $stockist_id);
                $stmt_mrfb->execute();$mr_row = $stmt_mrfb->get_result()->fetch_assoc();$mr_id  = $mr_row ? (int)$mr_row['m_id'] : 0;
                $stmt_mrfb->close();
            }

            // 2. Create receipt record in payment_details with payment_date set to settlement_date
            $payment_method = ($payment_type === 'account') ? ($data['payment_method'] ?? 'Bank Transfer') : 'Commission Adjustment';
            
            $stmt_pd =$this->con->prepare("
                INSERT INTO payment_details 
                (stockist_id, mr_id, amount_paid, payment_date, payment_method, bank_details, approval_status, approved_by, approved_at, commission_type) 
                VALUES (?, ?, ?, ?, ?, ?, 'approved', ?, NOW(), ?)
            ");
            $stmt_pd->bind_param("iidsssis", $stockist_id, $mr_id,$amount, $settlement_date,$payment_method, $notes, $admin_id,$comm_short);
            
            if (!$stmt_pd->execute()) {
                throw new Exception("Failed to record payment details: " . $stmt_pd->error);
            }
            $payment_id =$this->con->insert_id;
            $stmt_pd->close();

            $remaining_payment =$amount;

            // 3. Process according to payment_type
            if ($payment_type === 'old_bill') {
                // Deduct from commission wallet
                $stmt_w =$this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, ?, ?, 'settled_to_bill', ?, ?, 'decrease', ?)
                ");
                $stmt_w->bind_param("iisids", $stockist_id, $mr_id,$ledger_wallet_type, $payment_id,$amount, $notes);
                $stmt_w->execute();$stmt_w->close();

                // Decrease debt
                $stmt_d =$this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, ?, 'debt', ?, ?, ?, 'decrease', ?)
                ");
                $stmt_d->bind_param("iisids", $stockist_id, $mr_id,$settlement_type, $payment_id,$amount, $notes);
                $stmt_d->execute(); 
                $ledger_id =$this->con->insert_id; 
                $stmt_d->close();

                // ========================================================
                // PRIORITY 1: SETTLE OPENING BALANCE DEBT FIRST
                // ========================================================
                if ($remaining_payment > 0) {
                    $stmt_stk =$this->con->prepare("
                        SELECT opening_balance, opening_balance_type 
                        FROM stockists 
                        WHERE stockist_id = ? 
                        LIMIT 1 
                        FOR UPDATE
                    ");
                    $stmt_stk->bind_param("i", $stockist_id);
                    $stmt_stk->execute();$stk_info = $stmt_stk->get_result()->fetch_assoc();$stmt_stk->close();

                    $ob_amount = (float)($stk_info['opening_balance'] ?? 0);
                    $ob_type   = strtolower($stk_info['opening_balance_type'] ?? 'debt');

                    if ($ob_amount > 0 &&$ob_type === 'debt') {
                        $stmt_ob_alloc =$this->con->prepare("
                            SELECT COALESCE(SUM(pa.amount_allocated), 0) AS total_ob_paid 
                            FROM payment_allocations pa
                            INNER JOIN payment_ledgers pl ON pl.id = pa.ledger_id
                            WHERE pl.stockist_id = ? AND (pa.inward_id IS NULL OR pa.inward_id = 0)
                        ");
                        $stmt_ob_alloc->bind_param("i", $stockist_id);
                        $stmt_ob_alloc->execute();$ob_alloc_res = $stmt_ob_alloc->get_result()->fetch_assoc();$stmt_ob_alloc->close();

                        $already_paid_ob = (float)($ob_alloc_res['total_ob_paid'] ?? 0);$pending_ob      = max(0.00, round($ob_amount -$already_paid_ob, 2));

                        if ($pending_ob > 0) {$alloc_to_ob = min(round($remaining_payment, 2),$pending_ob);

                            $stmt_alloc_ob =$this->con->prepare("
                                INSERT INTO payment_allocations (ledger_id, inward_id, amount_allocated) 
                                VALUES (?, NULL, ?)
                            ");
                            $stmt_alloc_ob->bind_param("id", $ledger_id,$alloc_to_ob);
                            $stmt_alloc_ob->execute();$stmt_alloc_ob->close();

                            $remaining_payment = round($remaining_payment -$alloc_to_ob, 2);
                        }
                    }
                }

                // ========================================================
                // PRIORITY 2: SETTLE INWARD BILLS (FIFO by Dispatch / Inward Date)
                // ========================================================
                if ($remaining_payment > 0) {
                    $stmt_alloc =$this->con->prepare("
                        INSERT INTO payment_allocations (ledger_id, inward_id, amount_allocated) 
                        VALUES (?, ?, ?)
                    ");
                    $alloc_ledger_id =$ledger_id; 
                    $alloc_inward_id = 0; 
                    $alloc_amount    = 0.00;
                    $stmt_alloc->bind_param("iid", $alloc_ledger_id, $alloc_inward_id,$alloc_amount);

                    // Fetch CD Rules
                    $cd_4_days = 10;
                    $cd_2_days = 30;
                    $stmt_rules =$this->con->prepare("
                        SELECT r.cd_4_percent_days, r.cd_2_percent_days
                        FROM stockists s 
                        INNER JOIN headquarter h ON h.headquarter_id = s.hq_id
                        INNER JOIN super_stockist_cd_rules r ON r.super_stockist_id = h.super_stockist_id
                        WHERE s.stockist_id = ?
                        LIMIT 1
                    ");
                    $stmt_rules->bind_param("i", $stockist_id);$stmt_rules->execute();
                    $result_rules =$stmt_rules->get_result();
                    if ($row_rules = $result_rules->fetch_assoc()) {$cd_4_days = (int)($row_rules['cd_4_percent_days'] ?? 10);$cd_2_days = (int)($row_rules['cd_2_percent_days'] ?? 30); }$stmt_rules->close();

                    // Fetch Unpaid Bills with order dispatch fallback
                    $stmt_bills =$this->con->prepare("
                        SELECT 
                            si.inward_id, 
                            si.grand_total, 
                            si.paid_amt, 
                            si.inward_no, 
                            si.sub_total, 
                            COALESCE(si.business_value, si.sub_total, 0) AS business_value,
                            COALESCE(si.cd_percent, 0) AS cd_percent,
                            DATEDIFF(?, COALESCE(o.dispatch_date, si.inward_date)) AS age_days
                        FROM stock_inward si
                        LEFT JOIN `orders` o ON si.order_id = o.order_id
                        WHERE si.stockist_id = ? AND LOWER(si.pay_status) != 'paid' 
                        ORDER BY COALESCE(o.dispatch_date, si.inward_date) ASC
                        FOR UPDATE
                    ");
                    $stmt_bills->bind_param("si", $settlement_date,$stockist_id);
                    $stmt_bills->execute();$unpaid_bills = $stmt_bills->get_result();$stmt_bills->close();

                    if ($unpaid_bills->num_rows > 0) {
                        $stmt_update =$this->con->prepare("
                            UPDATE stock_inward 
                            SET paid_amt = ?, 
                                pay_status = ?, 
                                cd_percent = ?, 
                                cd_penalty_amt = cd_penalty_amt + ?, 
                                cd_earned_amt = cd_earned_amt + ? 
                            WHERE inward_id = ?
                        ");
                        $upd_paid_amt = 0; $upd_status = ""; $upd_cd_percent = 0; $upd_penalty = 0; $upd_earned = 0; $upd_inward_id = 0;
                        $stmt_update->bind_param("dsiddi", $upd_paid_amt,$upd_status, $upd_cd_percent,$upd_penalty, $upd_earned,$upd_inward_id);

                        $stmt_cd =$this->con->prepare("
                            INSERT INTO payment_ledgers 
                            (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                            VALUES (?, ?, 'debt', 'payment_made', ?, ?, 'decrease', ?)
                        ");
                        $cd_stockist_id = 0; $cd_inward_id = 0; $cd_amount = 0; $cd_notes = "";
                        $stmt_cd->bind_param("iiids", $cd_stockist_id, $mr_id,$cd_inward_id, $cd_amount,$cd_notes);

                        $stmt_penalty =$this->con->prepare("
                            INSERT INTO payment_ledgers 
                            (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                            VALUES (?, ?, 'debt', 'bill_added', ?, ?, 'increase', ?)
                        ");
                        $pen_stockist_id = 0; $pen_inward_id = 0; $pen_amount = 0; $pen_notes = "";
                        $stmt_penalty->bind_param("iiids", $pen_stockist_id, $mr_id,$pen_inward_id, $pen_amount,$pen_notes);

                        while ($bill =$unpaid_bills->fetch_assoc()) {
                            if ($remaining_payment <= 0) break; 

                            $inward_id   = (int)$bill['inward_id'];
                            $grand_total = (float)$bill['grand_total']; 
                            $paid_amt    = (float)$bill['paid_amt'];
                            $biz_value   = (float)$bill['business_value'];
                            if ($biz_value <= 0) {
                                $biz_value = (float)($bill['sub_total'] ?? 0) > 0 ? (float)$bill['sub_total'] :$grand_total;
                            }

                            $current_cd_percent = (int)$bill['cd_percent'];
                            $inward_no          =$bill['inward_no'];
                            $bill_age_days      = (int)$bill['age_days'];

                            $applied_penalty   = 0.00;
                            $applied_earned_cd = 0.00;

                            // A. Revoked CD Penalties
                            if ($current_cd_percent == 4) {
                                if ($bill_age_days > $cd_4_days &&$bill_age_days <= $cd_2_days) {$applied_penalty    = round($biz_value * 0.02, 2);$current_cd_percent = 2; 
                                } elseif ($bill_age_days >$cd_2_days) {
                                    $applied_penalty    = round($biz_value * 0.04, 2);
                                    $current_cd_percent = 0; } } elseif ($current_cd_percent == 2) {
                                if ($bill_age_days > $cd_2_days) {$applied_penalty    = round($biz_value * 0.02, 2);$current_cd_percent = 0;
                                }
                            }

                            if ($applied_penalty > 0) {$remaining_payment = round(max(0, $remaining_payment -$applied_penalty), 2);
                                
                                $pen_stockist_id =$stockist_id;
                                $pen_inward_id   =$inward_id;
                                $pen_amount      =$applied_penalty;
                                $pen_notes       = "CD Reversed for {$inward_no} [PID:{$payment_id}]";
                                $stmt_penalty->execute();
                            }

                            // B. Earned Cash Discount (Allows 0.50 / 0.49 fraction gap with 1.00 tolerance)
                            if ($applied_penalty == 0 && $current_cd_percent == 0) {$potential_cd  = 0.00;
                                $potential_pct = 0;

                                if ($bill_age_days <=$cd_4_days) {
                                    $potential_cd  = round($biz_value * 0.04, 2);
                                    $potential_pct = 4; } elseif ($bill_age_days <= $cd_2_days) {$potential_cd  = round($biz_value * 0.02, 2);$potential_pct = 2;
                                }

                                $required_cash_to_clear = round($grand_total - $paid_amt -$potential_cd, 2);

                                // If remaining payment is within 1.00 rupee of clearing, give CD
                                if ($potential_cd > 0 && ($remaining_payment >= ($required_cash_to_clear - 1.00))) {
                                    $applied_earned_cd  =$potential_cd;
                                    $current_cd_percent =$potential_pct;
                                    
                                    $cd_stockist_id =$stockist_id;
                                    $cd_inward_id   =$inward_id;
                                    $cd_amount      =$applied_earned_cd;
                                    $cd_notes       = "{$potential_pct}% CD on Invoice {$inward_no} [PID:{$payment_id}]: ₹" . number_format($applied_earned_cd, 2);$stmt_cd->execute();
                                }
                            }

                            // C. Allocate Cash Payment
                            $required_cash_balance = round(($grand_total +$applied_penalty) - $paid_amt -$applied_earned_cd, 2);

                            if ($required_cash_balance > 0) {$allocate_amount = min(round($remaining_payment, 2),$required_cash_balance);
                                
                                $alloc_ledger_id =$ledger_id;
                                $alloc_inward_id =$inward_id;
                                $alloc_amount    =$allocate_amount;
                                $stmt_alloc->execute();

                                $new_paid_amt      = round($paid_amt + $allocate_amount, 2);$remaining_payment = round($remaining_payment -$allocate_amount, 2);
                            } else {
                                $new_paid_amt =$paid_amt;
                            }

                            // D. Update Inward Record & Force Close on Fraction Differences
                            $total_cleared_value  = round($new_paid_amt +$applied_earned_cd, 2);
                            $total_bill_liability = round($grand_total + $applied_penalty, 2);$diff_remaining       = round($total_bill_liability -$total_cleared_value, 2);

                            // If remaining deficit is 1.00 rupee or less, force bill status to 'paid' and absorb difference
                            if ($diff_remaining <= 1.00 &&$diff_remaining >= 0.00 && $total_cleared_value > 0) {$new_status   = 'paid';
                                $new_paid_amt = round($total_bill_liability - $applied_earned_cd, 2); } else {$new_status = ($total_cleared_value >=$total_bill_liability) ? 'paid' : 'partial';
                            }

                            $upd_paid_amt   =$new_paid_amt;
                            $upd_status     =$new_status;
                            $upd_cd_percent =$current_cd_percent;
                            $upd_penalty    =$applied_penalty; 
                            $upd_earned     =$applied_earned_cd; 
                            $upd_inward_id  =$inward_id;
                            $stmt_update->execute();
                        }
                        
                        $stmt_update->close();
                        $stmt_cd->close();$stmt_penalty->close();
                    }

                    $stmt_alloc->close();
                }

            } else {
                // Direct Transfer (Commission withdrawal / payout, not settling bills)
                $stmt_w =$this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, 'decrease', ?)
                ");
                $stmt_w->bind_param("iissids", $stockist_id,$mr_id, $ledger_wallet_type,$settlement_type, $payment_id,$amount, $notes);
                $stmt_w->execute();$stmt_w->close();
            }

            $this->con->commit();
            return [
                'success' => true, 
                'msg'     => 'Entry processed successfully.' . ($payment_type === 'old_bill' && $remaining_payment > 0 ? " (₹" . round($remaining_payment, 2) . " saved as unallocated advance)" : '')
            ];

        } catch (Exception $e) {$this->con->rollback();
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
        $stmt = $this->con->prepare("
            SELECT stockist_id, approval_status, created_at 
            FROM payment_details 
            WHERE id = ? 
            FOR UPDATE
        ");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$payment || $payment['approval_status'] === 'reversed') {
            throw new Exception("Payment not found or already reversed.");
        }

        $stockist_id = (int)$payment['stockist_id'];
        $created_at  = $payment['created_at'];

        // 2. Find all main ledger entries tied to this payment
        $stmt = $this->con->prepare("
            SELECT id 
            FROM payment_ledgers 
            WHERE reference_id = ? 
              AND transaction_type IN ('paid_to_bank', 'settled_to_bill', 'mrc_settlement', 'drc_settlement', 'asm_settlement', 'payment_made')
        ");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $ledgers_res = $stmt->get_result();

        $main_ledger_ids = [];
        while ($l = $ledgers_res->fetch_assoc()) {
            $main_ledger_ids[] = (int)$l['id'];
        }
        $stmt->close();

        $affected_inward_ids = [];

        // 3. Rollback Cash Allocations on stock_inward
        if (!empty($main_ledger_ids)) {
            $ledger_in = implode(',', $main_ledger_ids);

            $stmt_alloc = $this->con->prepare("
                SELECT inward_id, amount_allocated 
                FROM payment_allocations 
                WHERE ledger_id IN ({$ledger_in}) 
                  AND inward_id IS NOT NULL 
                  AND inward_id > 0
            ");
            $stmt_alloc->execute();
            $allocations = $stmt_alloc->get_result();
            $stmt_alloc->close();

            $stmt_revert_cash = $this->con->prepare("
                UPDATE stock_inward 
                SET paid_amt = GREATEST(0, ROUND(paid_amt - ?, 2))
                WHERE inward_id = ?
            ");

            while ($alloc = $allocations->fetch_assoc()) {
                $inward_id    = (int)$alloc['inward_id'];
                $amount_alloc = (float)$alloc['amount_allocated'];
                $affected_inward_ids[$inward_id] = $inward_id;

                $stmt_revert_cash->bind_param("di", $amount_alloc, $inward_id);
                $stmt_revert_cash->execute();
            }
            $stmt_revert_cash->close();

            // Delete allocations tied to this payment's ledgers
            $this->con->query("DELETE FROM payment_allocations WHERE ledger_id IN ({$ledger_in})");
        }

        // 4. Reverse Earned CD from stock_inward & collect their ledger IDs
        // Matches either the [PID:X] tag in notes, or notes containing CD on Invoice around that transaction
        $pid_tag = "%[PID:{$payment_id}]%";
        $stmt_cd = $this->con->prepare("
            SELECT id, reference_id AS inward_id, amount 
            FROM payment_ledgers 
            WHERE stockist_id = ? 
              AND ledger_type = 'debt' 
              AND transaction_type = 'payment_made' 
              AND (notes LIKE ? OR (notes LIKE '%CD on Invoice%' AND created_at >= ?))
        ");
        $stmt_cd->bind_param("iss", $stockist_id, $pid_tag, $created_at);
        $stmt_cd->execute();
        $cd_result = $stmt_cd->get_result();

        $cd_ledger_ids = [];
        $stmt_revert_cd = $this->con->prepare("
            UPDATE stock_inward 
            SET cd_earned_amt = GREATEST(0, ROUND(cd_earned_amt - ?, 2)),
                cd_percent = 0
            WHERE inward_id = ?
        ");

        while ($cd_row = $cd_result->fetch_assoc()) {
            $cd_ledger_ids[] = (int)$cd_row['id'];
            $cd_amt          = (float)$cd_row['amount'];
            $inward_id       = (int)$cd_row['inward_id'];
            $affected_inward_ids[$inward_id] = $inward_id;

            $stmt_revert_cd->bind_param("di", $cd_amt, $inward_id);
            $stmt_revert_cd->execute();
        }
        $stmt_revert_cd->close();
        $stmt_cd->close();

        // 5. Reverse CD Penalties from stock_inward & collect their ledger IDs
        $stmt_pen = $this->con->prepare("
            SELECT id, reference_id AS inward_id, amount 
            FROM payment_ledgers 
            WHERE stockist_id = ? 
              AND ledger_type = 'debt' 
              AND transaction_type = 'bill_added' 
              AND (notes LIKE ? OR (notes LIKE 'CD Reversed for%' AND created_at >= ?))
        ");
        $stmt_pen->bind_param("iss", $stockist_id, $pid_tag, $created_at);
        $stmt_pen->execute();
        $pen_result = $stmt_pen->get_result();

        $pen_ledger_ids = [];
        $stmt_revert_pen = $this->con->prepare("
            UPDATE stock_inward 
            SET cd_penalty_amt = GREATEST(0, ROUND(cd_penalty_amt - ?, 2))
            WHERE inward_id = ?
        ");

        while ($pen_row = $pen_result->fetch_assoc()) {
            $pen_ledger_ids[] = (int)$pen_row['id'];
            $pen_amt          = (float)$pen_row['amount'];
            $inward_id        = (int)$pen_row['inward_id'];
            $affected_inward_ids[$inward_id] = $inward_id;

            $stmt_revert_pen->bind_param("di", $pen_amt, $inward_id);
            $stmt_revert_pen->execute();
        }
        $stmt_revert_pen->close();
        $stmt_pen->close();

        // 6. Recalculate bill pay_status accurately based on remaining paid_amt and cleared CD
        if (!empty($affected_inward_ids)) {
            $inward_in = implode(',', array_map('intval', $affected_inward_ids));
            $this->con->query("
                UPDATE stock_inward 
                SET pay_status = CASE 
                    WHEN ROUND(paid_amt + cd_earned_amt, 2) >= ROUND(grand_total + cd_penalty_amt - 1.00, 2) THEN 'paid'
                    WHEN paid_amt > 0.01 THEN 'partial'
                    ELSE 'unpaid'
                END 
                WHERE inward_id IN ({$inward_in})
            ");
        }

        // 7. Delete ALL related ledger entries (Main Payment, Wallet deduction, CD discounts, and Penalties)
        $all_ledger_ids = array_unique(array_merge($main_ledger_ids, $cd_ledger_ids, $pen_ledger_ids));
        if (!empty($all_ledger_ids)) {
            $id_list = implode(',', $all_ledger_ids);
            $this->con->query("DELETE FROM payment_ledgers WHERE id IN ({$id_list})");
        }

        // Safety fallback: Delete any lingering row tied to this payment_id as reference
        $stmt_safe_del = $this->con->prepare("DELETE FROM payment_ledgers WHERE reference_id = ? AND stockist_id = ?");
        $stmt_safe_del->bind_param("ii", $payment_id, $stockist_id);
        $stmt_safe_del->execute();
        $stmt_safe_del->close();

        // 8. Mark payment as reversed
        $stmt_rev = $this->con->prepare("
            UPDATE payment_details 
            SET approval_status = 'reversed', 
                approved_by = ?,
                bank_details = CONCAT(COALESCE(bank_details, ''), ' [REVERSED on ', NOW(), ']')
            WHERE id = ?
        ");
        $stmt_rev->bind_param("ii", $admin_id, $payment_id);
        $stmt_rev->execute();
        $stmt_rev->close();

        $this->con->commit();
        return ['success' => true, 'msg' => 'Payment, allocations, and cash discounts were reversed successfully.'];

    } catch (Exception $e) {
        $this->con->rollback();
        return ['success' => false, 'msg' => 'Error: ' . $e->getMessage()];
    }
}

public function getStockistOutstandingWithCD($stockist_id,$settlement_date = null)
{
    $stockist_id = (int)$stockist_id;

    // Validate and sanitize date
    if (empty($settlement_date) || !strtotime($settlement_date)) {$settlement_date = date('Y-m-d');
    } else {
        $settlement_date = date('Y-m-d', strtotime($settlement_date));
    }
    $safe_payment_date = $this->con->real_escape_string($settlement_date);

    /*
     * 1. Get Super Stockist ID, Opening Balance details
     */
    $stmtSS =$this->con->prepare("
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
    $stmtSS->execute();$resultSS = $stmtSS->get_result();$stockistData = $resultSS->fetch_assoc();$stmtSS->close();

    if (!$stockistData) {
        return [
            'total_outstanding' => 0.00,
            'eligible_cd'       => 0.00,
            'total_penalty'     => 0.00,
            'net_payable'       => 0,
            'advance_amount'    => 0.00,
            'bills'             => [],
            'bill_details'      => []
        ];
    }

    $super_stockist_id = (int)$stockistData['super_stockist_id'];

    /*
     * 2. Calculate Pending Opening Balance (Debit vs Credit) & Advance Credit
     */
    $ob_amount = (float)($stockistData['opening_balance'] ?? 0);
    $ob_type   = strtolower($stockistData['opening_balance_type'] ?? 'debt');
    $ob_date   = !empty($stockistData['opening_balance_date']) ? $stockistData['opening_balance_date'] :$settlement_date;

    $pending_ob_debt    = 0.00;
    $remaining_advance  = 0.00;
    $total_used_advance = 0.00;
    $total_orig_advance = 0.00;

    // CASE A: Stockist has Pending DEBT Opening Balance
    if ($ob_amount > 0 &&$ob_type === 'debt') {
        $stmtOBPaid =$this->con->prepare("
            SELECT COALESCE(SUM(pa.amount_allocated), 0) AS allocated_ob 
            FROM payment_allocations pa
            INNER JOIN payment_ledgers pl ON pl.id = pa.ledger_id
            WHERE pl.stockist_id = ? 
            AND (pa.inward_id IS NULL OR pa.inward_id = 0)
        ");
        $stmtOBPaid->bind_param("i", $stockist_id);
        $stmtOBPaid->execute();$obPaidRes = $stmtOBPaid->get_result()->fetch_assoc();$stmtOBPaid->close();

        $allocated_ob    = (float)($obPaidRes['allocated_ob'] ?? 0);$pending_ob_debt = max(0.00, round($ob_amount -$allocated_ob, 2));
    }

    // CASE B: Stockist has ADVANCE Credit (Payments & Credit Opening Balance)
    $stmtAdv =$this->con->prepare("
        SELECT 
            COALESCE(SUM(adv.available_advance), 0) AS total_remaining_advance,
            COALESCE(SUM(adv.amount), 0) AS total_original_advance,
            COALESCE(SUM(adv.used_amount), 0) AS total_used_advance
        FROM (
            SELECT 
                pl.id,
                pl.amount,
                COALESCE(SUM(pa.amount_allocated), 0) AS used_amount,
                CASE 
                    WHEN pl.amount > COALESCE(SUM(pa.amount_allocated), 0) 
                    THEN (pl.amount - COALESCE(SUM(pa.amount_allocated), 0)) 
                    ELSE 0 
                END AS available_advance
            FROM payment_ledgers pl
            LEFT JOIN payment_allocations pa ON pa.ledger_id = pl.id
            WHERE pl.stockist_id = ? 
            AND (
                (pl.transaction_type = 'opening_balance' AND (pl.ledger_type = 'credit' OR pl.balance_action = 'decrease'))
                OR (
                    pl.transaction_type = 'payment_made' 
                    AND (pl.ledger_type = 'credit' OR pl.balance_action = 'decrease')
                    AND (pl.notes IS NULL OR (pl.notes NOT LIKE '%CD on Invoice%' AND pl.notes NOT LIKE '%CD Reversed%'))
                )
            )
            GROUP BY pl.id
        ) AS adv
    ");
    $stmtAdv->bind_param("i", $stockist_id);
    $stmtAdv->execute();$advData = $stmtAdv->get_result()->fetch_assoc();$stmtAdv->close();

    $remaining_advance  = (float)($advData['total_remaining_advance'] ?? 0.00);
    $total_used_advance = (float)($advData['total_used_advance'] ?? 0.00);
    $total_orig_advance = (float)($advData['total_original_advance'] ?? 0.00);

    if ($total_orig_advance <= 0 && $ob_type === 'credit' &&$ob_amount > 0) {
        $total_orig_advance =$ob_amount;
        $remaining_advance  =$ob_amount;
    }

    /*
     * 3. Get CD rules
     */
    $stmtRule =$this->con->prepare("
        SELECT cd_4_percent_days, cd_2_percent_days
        FROM super_stockist_cd_rules
        WHERE super_stockist_id = ?
        LIMIT 1
    ");

    $stmtRule->bind_param("i", $super_stockist_id);
    $stmtRule->execute();$resultRule = $stmtRule->get_result();$rules = $resultRule->fetch_assoc();$stmtRule->close();

    $cd_4_days = isset($rules['cd_4_percent_days']) ? (int)$rules['cd_4_percent_days'] : 10;
    $cd_2_days = isset($rules['cd_2_percent_days']) ? (int)$rules['cd_2_percent_days'] : 30;

    /*
     * 4. Get unpaid / partially paid bills with exact float CD & Penalties
     */
    $stmt =$this->con->prepare("
        SELECT 
            si.inward_id,
            si.inward_no,
            si.inward_date,
            o.dispatch_date,
            si.grand_total AS gross_amount,
            si.sub_total,
            COALESCE(si.business_value, si.sub_total, 0) AS business_value,
            COALESCE(si.cd_percent, 0) AS cd_percent,
            si.paid_amt,
            (si.grand_total - si.paid_amt) AS pending_amount,
            DATEDIFF('{$safe_payment_date}', COALESCE(o.dispatch_date, si.inward_date)) AS bill_age_days,

            -- NEW ELIGIBLE 4% CD (Precision float)
            CASE 
                WHEN DATEDIFF('{$safe_payment_date}', COALESCE(o.dispatch_date, si.inward_date)) <= ? 
                    AND COALESCE(si.cd_percent, 0) = 0 
                THEN ROUND(COALESCE(si.business_value, si.sub_total, 0) * 0.04, 2)
                ELSE 0 
            END AS eligible_4_cd,

            -- NEW ELIGIBLE 2% CD (Precision float)
            CASE 
                WHEN DATEDIFF('{$safe_payment_date}', COALESCE(o.dispatch_date, si.inward_date)) > ? 
                    AND DATEDIFF('{$safe_payment_date}', COALESCE(o.dispatch_date, si.inward_date)) <= ? 
                    AND COALESCE(si.cd_percent, 0) = 0 
                THEN ROUND(COALESCE(si.business_value, si.sub_total, 0) * 0.02, 2)
                ELSE 0 
            END AS eligible_2_cd,

            -- REVOKED PENALTY (Precision float)
            CASE 
                WHEN COALESCE(si.cd_percent, 0) = 4 THEN
                    CASE 
                        WHEN DATEDIFF('{$safe_payment_date}', COALESCE(o.dispatch_date, si.inward_date)) <= ? THEN 0 
                        WHEN DATEDIFF('{$safe_payment_date}', COALESCE(o.dispatch_date, si.inward_date)) <= ? THEN 
                            ROUND(COALESCE(si.business_value, si.sub_total, 0) * 0.02, 2)
                        ELSE 
                            ROUND(COALESCE(si.business_value, si.sub_total, 0) * 0.04, 2)
                    END
                WHEN COALESCE(si.cd_percent, 0) = 2 THEN
                    CASE 
                        WHEN DATEDIFF('{$safe_payment_date}', COALESCE(o.dispatch_date, si.inward_date)) <= ? THEN 0 
                        ELSE 
                            ROUND(COALESCE(si.business_value, si.sub_total, 0) * 0.02, 2)
                    END
                ELSE 0 
            END AS penalty_amount

        FROM stock_inward si
        LEFT JOIN `orders` o ON si.order_id = o.order_id
        WHERE si.stockist_id = ? AND LOWER(si.pay_status) != 'paid'
        ORDER BY COALESCE(o.dispatch_date, si.inward_date) ASC
    ");

    $stmt->bind_param(
        "iiiiiii",
        $cd_4_days,
        $cd_4_days,$cd_2_days,
        $cd_4_days,$cd_2_days,
        $cd_2_days,$stockist_id
    );

    $stmt->execute();
    $result =$stmt->get_result();

    $total_pending       = 0.00;
    $total_eligible_4_cd = 0.00;
    $total_eligible_2_cd = 0.00;
    $total_penalty       = 0.00;
    $bills               = [];

    // Prepend Opening Balance if active debt exists
    if ($pending_ob_debt > 0) {
        $total_pending +=$pending_ob_debt;
        $bills[] = [
            'inward_id'          => 0,
            'inward_no'          => 'OPENING-BAL (DEBT)',
            'inward_date'        => $ob_date,
            'gross_amount'       => round($ob_amount, 0), 'sub_total'          =>$ob_amount,
            'cd_percent'         => 0,
            'paid_amt'           => round($ob_amount - $pending_ob_debt, 2), 'pending_amount'     =>$pending_ob_debt,
            'bill_age_days'      => 0,
            'eligible_4_cd'      => 0.00,
            'eligible_2_cd'      => 0.00,
            'penalty_amount'     => 0.00,
            'is_opening_balance' => 1,
            'balance_type'       => 'debt'
        ];
    }

    // Prepend Advance Credit if stockist has unused advance
    if ($remaining_advance > 0) {
        $total_pending -=$remaining_advance;
        $bills[] = [
            'inward_id'          => 0,
            'inward_no'          => 'ADVANCE CREDIT',
            'inward_date'        => $ob_date,
            'gross_amount'       => round($total_orig_advance, 0), 'sub_total'          =>$total_orig_advance,
            'cd_percent'         => 0,
            'paid_amt'           => $total_used_advance,
            'pending_amount'     => -$remaining_advance,
            'bill_age_days'      => 0,
            'eligible_4_cd'      => 0.00,
            'eligible_2_cd'      => 0.00,
            'penalty_amount'     => 0.00,
            'is_opening_balance' => ($ob_type === 'credit' && $total_orig_advance ==$ob_amount) ? 1 : 0,
            'balance_type'       => 'credit'
        ];
    }

    while ($row =$result->fetch_assoc()) {
        $pending = (float)$row['pending_amount'];

        // Gross in round
        $row['gross_amount']   = round((float)$row['gross_amount'], 0);

        // CD & Penalty kept in float
        $row['eligible_4_cd']  = round((float)$row['eligible_4_cd'], 2);
        $row['eligible_2_cd']  = round((float)$row['eligible_2_cd'], 2);
        $row['penalty_amount'] = round((float)$row['penalty_amount'], 2);

        if ($pending > 0) {
            $total_pending       +=$pending;
            $total_eligible_4_cd +=$row['eligible_4_cd'];
            $total_eligible_2_cd +=$row['eligible_2_cd'];
            $total_penalty       +=$row['penalty_amount'];
        }
        $row['is_opening_balance'] = 0;
        $row['balance_type']       = 'debt';
        $bills[] =$row;
    }
    $stmt->close();

    $total_cd    = round($total_eligible_4_cd +$total_eligible_2_cd, 2);
    $total_pen   = round($total_penalty, 2);

    // Final Net Payable always rounded to nearest whole number
    $net_payable = (int)round($total_pending - $total_cd +$total_pen, 0);

    return [
        'total_outstanding' => round($total_pending, 2), 'eligible_cd'       =>$total_cd,
        'total_penalty'     => $total_pen,
        'net_payable'       => $net_payable,
        'advance_amount'    => round($remaining_advance, 2), 'bills'             =>$bills,
        'bill_details'      => $bills
    ];
}
    
public function getPaymentAllocations($payment_id) {
    $sql = "
        SELECT 
            COALESCE(si.inward_no, 'Opening Balance') AS inward_no, 
            si.inward_date, 
            pa.amount_allocated,
            COALESCE((
                SELECT pl_cd.amount 
                FROM payment_ledgers pl_cd 
                WHERE pl_cd.reference_id = pa.inward_id 
                  AND pl_cd.stockist_id = pl.stockist_id
                  AND pl_cd.ledger_type = 'debt'
                  AND pl_cd.transaction_type = 'payment_made' 
                  AND pl_cd.notes LIKE '%CD on Invoice%'
                  AND pl_cd.created_at >= (SELECT created_at FROM payment_details WHERE id = ?)
                ORDER BY pl_cd.id DESC LIMIT 1
            ), 0.00) AS cd_earned,
            COALESCE((
                SELECT pl_pen.amount 
                FROM payment_ledgers pl_pen 
                WHERE pl_pen.reference_id = pa.inward_id 
                  AND pl_pen.stockist_id = pl.stockist_id
                  AND pl_pen.ledger_type = 'debt'
                  AND pl_pen.transaction_type = 'bill_added' 
                  AND pl_pen.notes LIKE 'CD Reversed for%'
                  AND pl_pen.created_at >= (SELECT created_at FROM payment_details WHERE id = ?)
                ORDER BY pl_pen.id DESC LIMIT 1
            ), 0.00) AS cd_revoked,
            COALESCE(si.grand_total, pa.amount_allocated) AS grand_total,
            COALESCE(si.paid_amt, pa.amount_allocated) AS paid_amt
        FROM payment_allocations pa
        INNER JOIN payment_ledgers pl ON pa.ledger_id = pl.id
        LEFT JOIN stock_inward si ON pa.inward_id = si.inward_id
        WHERE pl.reference_id = ? 
          AND pl.ledger_type = 'debt' 
          AND pl.balance_action = 'decrease'
    ";
    
    $stmt = $this->con->prepare($sql);
    // Bind payment_id 3 times: for CD subquery, Penalty subquery, and main WHERE clause
    $stmt->bind_param("iii", $payment_id, $payment_id, $payment_id);
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

    public function submitManualPayEntry($data,$admin_id) 
    {
        try {
            $stockist_id     = isset($data['stockist_id']) ? (int)$data['stockist_id'] : 0;
            $mr_id           = !empty($data['mr_id']) ? (int)$data['mr_id'] : 0;
            $amount          = (float)($data['amount'] ?? 0);
            $notes           = isset($data['notes']) ? trim($data['notes']) : '';
            $payment_method  = !empty($data['payment_method']) ? trim($data['payment_method']) : 'Manual Adjustment';$settlement_date = !empty($data['settlement_date']) ? date('Y-m-d', strtotime($data['settlement_date'])) : date('Y-m-d');

         if ($stockist_id <= 0 || $amount <= 0) {
                throw new Exception("Stockist ID and valid amount are required.");
            }

            $this->con->begin_transaction();

            // Resolve active MR if not provided
            if ($mr_id <= 0) {
                $stmt_mrfb =$this->con->prepare("
                    SELECT m.m_id 
                    FROM mr_users m
                    INNER JOIN stockists s ON s.hq_id = m.hq_id
                    WHERE s.stockist_id = ? AND m.status = '1'
                    LIMIT 1
                ");
                $stmt_mrfb->bind_param("i", $stockist_id);
                $stmt_mrfb->execute();$mr_row = $stmt_mrfb->get_result()->fetch_assoc();$mr_id  = $mr_row ? (int)$mr_row['m_id'] : 0;
                $stmt_mrfb->close();
            }

            // 1. Record in payment_details as pre-approved
            $stmt_pd =$this->con->prepare("
                INSERT INTO payment_details 
                (stockist_id, mr_id, amount_paid, payment_date, payment_method, bank_details, approval_status, approved_by, approved_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'approved', ?, NOW())
            ");
            $stmt_pd->bind_param("iidsssi", $stockist_id,$mr_id, $amount,$settlement_date, $payment_method, $notes, $admin_id);
            
            if (!$stmt_pd->execute()) {
                throw new Exception("Failed to record payment details: " . $stmt_pd->error);
            }
            $payment_id =$this->con->insert_id;
            $stmt_pd->close();

            // 2. Direct credit to stockist ledger (debt decrease)
            $stmt_d =$this->con->prepare("
                INSERT INTO payment_ledgers 
                (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                VALUES (?, ?, 'debt', 'payment_made', ?, ?, 'decrease', ?)
            ");
            $stmt_d->bind_param("iiids", $stockist_id,$mr_id, $payment_id,$amount, $notes);
            $stmt_d->execute(); 
            $ledger_id =$this->con->insert_id; 
            $stmt_d->close();

            $remaining_payment =$amount;

            // ========================================================
            // 3. SETTLE OPENING BALANCE DEBT FIRST
            // ========================================================
            if ($remaining_payment > 0) {
                $stmt_stk =$this->con->prepare("
                    SELECT opening_balance, opening_balance_type 
                    FROM stockists 
                    WHERE stockist_id = ? 
                    LIMIT 1 
                    FOR UPDATE
                ");
                $stmt_stk->bind_param("i", $stockist_id);
                $stmt_stk->execute();$stk_info = $stmt_stk->get_result()->fetch_assoc();$stmt_stk->close();

                $ob_amount = (float)($stk_info['opening_balance'] ?? 0);
                $ob_type   = strtolower($stk_info['opening_balance_type'] ?? 'debt');

                if ($ob_amount > 0 &&$ob_type === 'debt') {
                    $stmt_ob_alloc =$this->con->prepare("
                        SELECT COALESCE(SUM(pa.amount_allocated), 0) AS total_ob_paid 
                        FROM payment_allocations pa
                        INNER JOIN payment_ledgers pl ON pl.id = pa.ledger_id
                        WHERE pl.stockist_id = ? AND (pa.inward_id IS NULL OR pa.inward_id = 0)
                    ");
                    $stmt_ob_alloc->bind_param("i", $stockist_id);
                    $stmt_ob_alloc->execute();$ob_alloc_res = $stmt_ob_alloc->get_result()->fetch_assoc();$stmt_ob_alloc->close();

                    $already_paid_ob = (float)($ob_alloc_res['total_ob_paid'] ?? 0);$pending_ob      = max(0.00, round($ob_amount -$already_paid_ob, 2));

                    if ($pending_ob > 0) {$alloc_to_ob = min(round($remaining_payment, 2),$pending_ob);

                        $stmt_alloc_ob =$this->con->prepare("
                            INSERT INTO payment_allocations (ledger_id, inward_id, amount_allocated) 
                            VALUES (?, NULL, ?)
                        ");
                        $stmt_alloc_ob->bind_param("id", $ledger_id,$alloc_to_ob);
                        $stmt_alloc_ob->execute();$stmt_alloc_ob->close();

                        $remaining_payment = round($remaining_payment -$alloc_to_ob, 2);
                    }
                }
            }

            // ========================================================
            // 4. SETTLE INWARD BILLS
            // ========================================================
            if ($remaining_payment > 0) {
                $stmt_alloc =$this->con->prepare("
                    INSERT INTO payment_allocations (ledger_id, inward_id, amount_allocated) 
                    VALUES (?, ?, ?)
                ");
                $alloc_ledger_id =$ledger_id; 
                $alloc_inward_id = 0; 
                $alloc_amount    = 0.00;
                $stmt_alloc->bind_param("iid", $alloc_ledger_id, $alloc_inward_id,$alloc_amount);

                // Fetch CD Rules
                $cd_4_days = 10;
                $cd_2_days = 30;
                $stmt_rules =$this->con->prepare("
                    SELECT r.cd_4_percent_days, r.cd_2_percent_days
                    FROM stockists s 
                    INNER JOIN headquarter h ON h.headquarter_id = s.hq_id
                    INNER JOIN super_stockist_cd_rules r ON r.super_stockist_id = h.super_stockist_id
                    WHERE s.stockist_id = ?
                    LIMIT 1
                ");
                $stmt_rules->bind_param("i", $stockist_id);$stmt_rules->execute();
                $result_rules =$stmt_rules->get_result();
                if ($row_rules = $result_rules->fetch_assoc()) {$cd_4_days = (int)($row_rules['cd_4_percent_days'] ?? 10);$cd_2_days = (int)($row_rules['cd_2_percent_days'] ?? 30); }$stmt_rules->close();

                // Fetch Unpaid Bills with order dispatch fallback
                $stmt_bills =$this->con->prepare("
                    SELECT 
                        si.inward_id, 
                        si.grand_total, 
                        si.paid_amt, 
                        si.inward_no, 
                        si.sub_total, 
                        COALESCE(si.business_value, si.sub_total, 0) AS business_value,
                        COALESCE(si.cd_percent, 0) AS cd_percent,
                        DATEDIFF(?, COALESCE(o.dispatch_date, si.inward_date)) AS age_days
                    FROM stock_inward si
                    LEFT JOIN `orders` o ON si.order_id = o.order_id
                    WHERE si.stockist_id = ? AND LOWER(si.pay_status) != 'paid' 
                    ORDER BY COALESCE(o.dispatch_date, si.inward_date) ASC
                    FOR UPDATE
                ");
                $stmt_bills->bind_param("si", $settlement_date,$stockist_id);
                $stmt_bills->execute();$unpaid_bills = $stmt_bills->get_result();$stmt_bills->close();

                if ($unpaid_bills->num_rows > 0) {
                    $stmt_update =$this->con->prepare("
                        UPDATE stock_inward 
                        SET paid_amt = ?, 
                            pay_status = ?, 
                            cd_percent = ?, 
                            cd_penalty_amt = cd_penalty_amt + ?, 
                            cd_earned_amt = cd_earned_amt + ? 
                        WHERE inward_id = ?
                    ");
                    $upd_paid_amt = 0; $upd_status = ""; $upd_cd_percent = 0; $upd_penalty = 0; $upd_earned = 0; $upd_inward_id = 0;
                    $stmt_update->bind_param("dsiddi", $upd_paid_amt,$upd_status, $upd_cd_percent,$upd_penalty, $upd_earned,$upd_inward_id);

                    $stmt_cd =$this->con->prepare("
                        INSERT INTO payment_ledgers 
                        (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                        VALUES (?, ?, 'debt', 'payment_made', ?, ?, 'decrease', ?)
                    ");
                    $cd_stockist_id = 0; $cd_inward_id = 0; $cd_amount = 0; $cd_notes = "";
                    $stmt_cd->bind_param("iiids", $cd_stockist_id, $mr_id,$cd_inward_id, $cd_amount,$cd_notes);

                    $stmt_penalty =$this->con->prepare("
                        INSERT INTO payment_ledgers 
                        (stockist_id, user_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                        VALUES (?, ?, 'debt', 'bill_added', ?, ?, 'increase', ?)
                    ");
                    $pen_stockist_id = 0; $pen_inward_id = 0; $pen_amount = 0; $pen_notes = "";
                    $stmt_penalty->bind_param("iiids", $pen_stockist_id, $mr_id,$pen_inward_id, $pen_amount,$pen_notes);

                    while ($bill =$unpaid_bills->fetch_assoc()) {
                        if ($remaining_payment <= 0) break; 

                        $inward_id          = (int)$bill['inward_id'];
                        $grand_total        = (float)$bill['grand_total']; 
                        $paid_amt           = (float)$bill['paid_amt'];
                        $biz_value          = (float)$bill['business_value'];
                        if ($biz_value <= 0) {
                            $biz_value = (float)($bill['sub_total'] ?? 0) > 0 ? (float)$bill['sub_total'] :$grand_total;
                        }

                        $current_cd_percent = (int)$bill['cd_percent'];
                        $inward_no          =$bill['inward_no'];
                        $bill_age_days      = (int)$bill['age_days'];

                        $applied_penalty   = 0.00;
                        $applied_earned_cd = 0.00;

                        // A. Revoked CD Penalties
                        if ($current_cd_percent == 4) {
                            if ($bill_age_days > $cd_4_days &&$bill_age_days <= $cd_2_days) {$applied_penalty    = round($biz_value * 0.02, 2);$current_cd_percent = 2; 
                            } elseif ($bill_age_days >$cd_2_days) {
                                $applied_penalty    = round($biz_value * 0.04, 2);
                                $current_cd_percent = 0; } } elseif ($current_cd_percent == 2) {
                            if ($bill_age_days > $cd_2_days) {$applied_penalty    = round($biz_value * 0.02, 2);$current_cd_percent = 0;
                            }
                        }

                        if ($applied_penalty > 0) {$remaining_payment = round(max(0, $remaining_payment -$applied_penalty), 2);
                            
                            $pen_stockist_id =$stockist_id;
                            $pen_inward_id   =$inward_id;
                            $pen_amount      =$applied_penalty;
                            $pen_notes       = "CD Reversed for " . $inward_no;
                            $stmt_penalty->execute();
                        }

                        // B. Earned Cash Discount (Allows 0.50 / 0.49 fraction gap with 1.00 tolerance)
                        if ($applied_penalty == 0 && $current_cd_percent == 0) {$potential_cd  = 0.00;
                            $potential_pct = 0;

                            if ($bill_age_days <=$cd_4_days) {
                                $potential_cd  = round($biz_value * 0.04, 2);
                                $potential_pct = 4; } elseif ($bill_age_days <= $cd_2_days) {$potential_cd  = round($biz_value * 0.02, 2);$potential_pct = 2;
                            }

                            $required_cash_to_clear = round($grand_total - $paid_amt -$potential_cd, 2);

                            // If remaining payment is within 1.00 rupee of clearing, give CD
                            if ($potential_cd > 0 && ($remaining_payment >= ($required_cash_to_clear - 1.00))) {
                                $applied_earned_cd  =$potential_cd;
                                $current_cd_percent =$potential_pct;
                                
                                $cd_stockist_id =$stockist_id;
                                $cd_inward_id   =$inward_id;
                                $cd_amount      =$applied_earned_cd;
                                $cd_notes       = "{$potential_pct}\% CD on Invoice {$inward_no}: ₹" . number_format($applied_earned_cd, 2);$stmt_cd->execute();
                            }
                        }

                        // C. Allocate Cash Payment
                        $required_cash_balance = round(($grand_total +$applied_penalty) - $paid_amt -$applied_earned_cd, 2);

                        if ($required_cash_balance > 0) {
                            // Settle fraction difference (0.49 / 0.50 paise) when CD is applied or payment is round
                            if ($remaining_payment >= ($required_cash_balance - 1.00)) {$allocate_amount = min(round($remaining_payment, 2),$required_cash_balance);
                            } else {
                                $allocate_amount = min(round($remaining_payment, 2),$required_cash_balance);
                            }
                            
                            $alloc_ledger_id =$ledger_id;
                            $alloc_inward_id =$inward_id;
                            $alloc_amount    =$allocate_amount;
                            $stmt_alloc->execute();

                            $new_paid_amt      = round($paid_amt + $allocate_amount, 2);$remaining_payment = round($remaining_payment -$allocate_amount, 2);
                        } else {
                            $new_paid_amt =$paid_amt;
                        }

                        // D. Update Inward Record & Force Close on Fraction Differences
                        $total_cleared_value  = round($new_paid_amt +$applied_earned_cd, 2);
                        $total_bill_liability = round($grand_total + $applied_penalty, 2);$diff_remaining       = round($total_bill_liability -$total_cleared_value, 2);

                        // If remaining deficit is 1.00 rupee or less, force bill status to 'paid' and absorb difference
                        if ($diff_remaining <= 1.00 &&$diff_remaining >= 0.00 && $total_cleared_value > 0) {$new_status   = 'paid';
                            $new_paid_amt = round($total_bill_liability - $applied_earned_cd, 2); } else {$new_status = ($total_cleared_value >=$total_bill_liability) ? 'paid' : 'partial';
                        }

                        $upd_paid_amt   =$new_paid_amt;
                        $upd_status     =$new_status;
                        $upd_cd_percent =$current_cd_percent;
                        $upd_penalty    =$applied_penalty; 
                        $upd_earned     =$applied_earned_cd; 
                        $upd_inward_id  =$inward_id;
                        $stmt_update->execute();
                    }
                    
                    $stmt_update->close();
                    $stmt_cd->close();$stmt_penalty->close();
                }

                $stmt_alloc->close();
            }

            $this->con->commit();
            return [
                'success' => true, 
                'msg'     => 'Manual payment entry recorded successfully.' . ($remaining_payment > 0 ? " (₹" . round($remaining_payment, 2) . " saved as unallocated advance)" : '')
            ];

        } catch (Exception $e) {$this->con->rollback();
            return ['success' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }


   public function deletePendingPayment($payment_id)
    {
        $payment_id = (int)$payment_id;

        if ($payment_id <= 0) {
            return ['success' => false, 'msg' => 'Invalid payment ID.'];
        }

        // 1. Fetch record using the correct column: screenshot_path
        $stmt = $this->con->prepare("
            SELECT id, screenshot_path, approval_status 
            FROM payment_details 
            WHERE id = ? 
            LIMIT 1
        ");
        
        if (!$stmt) {
            return ['success' => false, 'msg' => 'Prepare failed: ' . $this->con->error];
        }

        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $payment = $res->fetch_assoc();
        $stmt->close();

        if (!$payment) {
            return ['success' => false, 'msg' => 'Payment record not found.'];
        }

        // 2. Ensure only pending payments can be deleted
        if (trim(strtolower($payment['approval_status'])) !== 'pending') {
            return ['success' => false, 'msg' => 'Only pending payments can be deleted.'];
        }

        // 3. Delete from payment_details
        $del_stmt = $this->con->prepare("DELETE FROM payment_details WHERE id = ? AND approval_status = 'pending'");
        if (!$del_stmt) {
            return ['success' => false, 'msg' => 'Delete prepare failed: ' . $this->con->error];
        }

        $del_stmt->bind_param("i", $payment_id);
        
        if ($del_stmt->execute()) {
            $del_stmt->close();

            // 4. Safely delete physical screenshot file if it exists on disk
            if (!empty($payment['screenshot_path'])) {
                $file_path = __DIR__ . '/../../' . ltrim($payment['screenshot_path'], '/\\');
                if (file_exists($file_path) && is_file($file_path)) {
                    @unlink($file_path);
                }
            }

            return ['success' => true, 'msg' => 'Pending payment deleted successfully.'];
        }

        $error_msg = $del_stmt->error;
        $del_stmt->close();
        return ['success' => false, 'msg' => 'Database error: ' . $error_msg];
    }

    public function getManualPaymentList($filters = []) 
    {
        try {
            $where = ["pd.payment_method = 'Manual Adjustment'"];
            $params = [];
            $types = "";

            if (!empty($filters['start_date'])) {
                $where[] = "pd.payment_date >= ?";
                $params[] = $filters['start_date'];
                $types .= "s";
            }

            if (!empty($filters['end_date'])) {
                $where[] = "pd.payment_date <= ?";
                $params[] = $filters['end_date'];
                $types .= "s";
            }


            if (!empty($filters['hq_id'])) {
                $where[] = "s.hq_id = ?";
                $params[] = (int)$filters['hq_id'];
                $types .= "i";
            }

            $whereClause = implode(" AND ", $where);

            $sql = "
                SELECT 
                    pd.id,
                    pd.stockist_id,
                    pd.amount_paid,
                    pd.payment_date,
                    pd.payment_method,
                    pd.bank_details AS notes,
                    pd.approval_status,
                    s.stockist_name,
                    hq.hq_name
                FROM payment_details pd
                INNER JOIN stockists s ON s.stockist_id = pd.stockist_id
                LEFT JOIN headquarter hq ON hq.headquarter_id = s.hq_id
                WHERE {$whereClause}
                ORDER BY pd.id DESC
            ";

            $stmt = $this->con->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return ['success' => true, 'data' => $result];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage(), 'data' => []];
        }
    }

    public function getManualPaymentById($payment_id) {
    $stmt = $this->con->prepare("
        SELECT 
            pd.id,
            pd.stockist_id,
            pd.mr_id,
            pd.amount_paid,
            pd.payment_date,
            pd.payment_method,
            pd.bank_details,
            pd.approval_status,
            s.hq_id,
            hq.state_id,
            s.stockist_name
        FROM payment_details pd
        INNER JOIN stockists s ON s.stockist_id = pd.stockist_id
        LEFT JOIN headquarter hq ON hq.headquarter_id = s.hq_id
        WHERE pd.id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $data;
}



public function reverseManualPayment($payment_id, $admin_id) {
    try {
        $payment_id = (int)$payment_id;
        $admin_id   = (int)$admin_id;

        if ($payment_id <= 0) {
            throw new Exception("Invalid payment ID.");
        }

        $this->con->begin_transaction();

        // 1. Fetch & lock payment details
        $stmt_pd = $this->con->prepare("
            SELECT id, stockist_id, approval_status, created_at 
            FROM payment_details 
            WHERE id = ? 
            FOR UPDATE
        ");
        $stmt_pd->bind_param("i", $payment_id);
        $stmt_pd->execute();
        $pay_row = $stmt_pd->get_result()->fetch_assoc();
        $stmt_pd->close();

        if (!$pay_row) {
            throw new Exception("Payment record not found.");
        }
        if ($pay_row['approval_status'] === 'reversed') {
            throw new Exception("This payment has already been reversed.");
        }

        $stockist_id = (int)$pay_row['stockist_id'];
        $created_at  = $pay_row['created_at'];

        // 2. Fetch ALL primary payment ledger IDs linked to this payment_id
        $stmt_pl = $this->con->prepare("
            SELECT id 
            FROM payment_ledgers 
            WHERE reference_id = ? 
              AND ledger_type = 'debt'
        ");
        $stmt_pl->bind_param("i", $payment_id);
        $stmt_pl->execute();
        $pl_res = $stmt_pl->get_result();

        $main_ledger_ids = [];
        while ($r = $pl_res->fetch_assoc()) {
            $main_ledger_ids[] = (int)$r['id'];
        }
        $stmt_pl->close();

        $affected_inward_ids = [];

        // 3. Reverse Cash Allocations from Inward Bills
        if (!empty($main_ledger_ids)) {
            $ledger_in = implode(',', $main_ledger_ids);

            $alloc_res = $this->con->query("
                SELECT inward_id, amount_allocated 
                FROM payment_allocations 
                WHERE ledger_id IN ({$ledger_in}) 
                  AND inward_id IS NOT NULL 
                  AND inward_id > 0
            ");

            $stmt_revert_cash = $this->con->prepare("
                UPDATE stock_inward 
                SET paid_amt = GREATEST(0, ROUND(paid_amt - ?, 2))
                WHERE inward_id = ?
            ");

            while ($alloc = $alloc_res->fetch_assoc()) {
                $cash_amt  = (float)$alloc['amount_allocated'];
                $inward_id = (int)$alloc['inward_id'];
                $affected_inward_ids[$inward_id] = $inward_id;

                $stmt_revert_cash->bind_param("di", $cash_amt, $inward_id);
                $stmt_revert_cash->execute();
            }
            $stmt_revert_cash->close();

            // Delete allocations tied to these ledger IDs
            $this->con->query("DELETE FROM payment_allocations WHERE ledger_id IN ({$ledger_in})");
        }

        // 4. Reverse CD Earned from Bills & track their ledger rows
        $stmt_cd = $this->con->prepare("
            SELECT id, reference_id AS inward_id, amount 
            FROM payment_ledgers 
            WHERE stockist_id = ? 
              AND ledger_type = 'debt' 
              AND transaction_type = 'payment_made' 
              AND notes LIKE '%CD on Invoice%' 
              AND created_at >= ?
        ");
        $stmt_cd->bind_param("is", $stockist_id, $created_at);
        $stmt_cd->execute();
        $cd_result = $stmt_cd->get_result();

        $cd_ledger_ids = [];
        $stmt_revert_cd = $this->con->prepare("
            UPDATE stock_inward 
            SET cd_earned_amt = GREATEST(0, ROUND(cd_earned_amt - ?, 2)),
                cd_percent = 0
            WHERE inward_id = ?
        ");

        while ($cd_row = $cd_result->fetch_assoc()) {
            $cd_ledger_ids[] = (int)$cd_row['id'];
            $cd_amt    = (float)$cd_row['amount'];
            $inward_id = (int)$cd_row['inward_id'];
            $affected_inward_ids[$inward_id] = $inward_id;

            $stmt_revert_cd->bind_param("di", $cd_amt, $inward_id);
            $stmt_revert_cd->execute();
        }
        $stmt_revert_cd->close();
        $stmt_cd->close();

        // 5. Reverse CD Penalties from Bills & track their ledger rows
        $stmt_pen = $this->con->prepare("
            SELECT id, reference_id AS inward_id, amount 
            FROM payment_ledgers 
            WHERE stockist_id = ? 
              AND ledger_type = 'debt' 
              AND transaction_type = 'bill_added' 
              AND notes LIKE 'CD Reversed for%' 
              AND created_at >= ?
        ");
        $stmt_pen->bind_param("is", $stockist_id, $created_at);
        $stmt_pen->execute();
        $pen_result = $stmt_pen->get_result();

        $pen_ledger_ids = [];
        $stmt_revert_pen = $this->con->prepare("
            UPDATE stock_inward 
            SET cd_penalty_amt = GREATEST(0, ROUND(cd_penalty_amt - ?, 2))
            WHERE inward_id = ?
        ");

        while ($pen_row = $pen_result->fetch_assoc()) {
            $pen_ledger_ids[] = (int)$pen_row['id'];
            $pen_amt   = (float)$pen_row['amount'];
            $inward_id = (int)$pen_row['inward_id'];
            $affected_inward_ids[$inward_id] = $inward_id;

            $stmt_revert_pen->bind_param("di", $pen_amt, $inward_id);
            $stmt_revert_pen->execute();
        }
        $stmt_revert_pen->close();
        $stmt_pen->close();

        // 6. Recalculate bill pay_status for all touched inward bills
        if (!empty($affected_inward_ids)) {
            $inward_in = implode(',', array_map('intval', $affected_inward_ids));
            $this->con->query("
                UPDATE stock_inward 
                SET pay_status = CASE 
                    WHEN ROUND(paid_amt + cd_earned_amt, 2) >= ROUND(grand_total + cd_penalty_amt, 2) THEN 'paid'
                    WHEN paid_amt > 0 THEN 'partial'
                    ELSE 'unpaid'
                END
                WHERE inward_id IN ({$inward_in})
            ");
        }

        // 7. DIRECT PURGE FROM PAYMENT LEDGERS:
        // A) Delete by explicit IDs collected above
        $all_ledger_ids = array_unique(array_merge($main_ledger_ids, $cd_ledger_ids, $pen_ledger_ids));
        if (!empty($all_ledger_ids)) {
            $id_list = implode(',', $all_ledger_ids);
            $this->con->query("DELETE FROM payment_ledgers WHERE id IN ({$id_list})");
        }

        // B) Safety wipe: Delete any remaining ledger row with reference_id = payment_id directly
        $stmt_wipe = $this->con->prepare("
            DELETE FROM payment_ledgers 
            WHERE reference_id = ? 
              AND stockist_id = ?
        ");
        $stmt_wipe->bind_param("ii", $payment_id, $stockist_id);
        $stmt_wipe->execute();
        $stmt_wipe->close();

        // 8. Update payment_details status
        $stmt_upd = $this->con->prepare("
            UPDATE payment_details 
            SET approval_status = 'reversed', 
                approved_by = ?,
                bank_details = CONCAT(COALESCE(bank_details, ''), ' [REVERSED on ', NOW(), ']')
            WHERE id = ?
        ");
        $stmt_upd->bind_param("ii", $admin_id, $payment_id);
        $stmt_upd->execute();
        $stmt_upd->close();

        $this->con->commit();
        return ['success' => true, 'msg' => 'Payment reversed and cleared from payment ledger successfully.'];

    } catch (Exception $e) {
        $this->con->rollback();
        return ['success' => false, 'msg' => 'Reversal failed: ' . $e->getMessage()];
    }
}
   
}
?>