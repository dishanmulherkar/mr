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
    public function processApproval($payment_id, $admin_id, $action_status) {
        try {
            $this->con->begin_transaction();

            // Fetch payment details
            $stmt = $this->con->prepare("SELECT stockist_id, amount_paid, payment_method FROM payment_details WHERE id = ? AND approval_status = 'pending' FOR UPDATE");
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Payment not found or already processed.");
            }
            
            $payment = $result->fetch_assoc();
            $stockist_id = (int)$payment['stockist_id'];
            $amount_paid = (float)$payment['amount_paid'];
            $payment_method = $payment['payment_method'];
            $stmt->close();

            // Update status to Approved/Rejected
            $stmt = $this->con->prepare("UPDATE payment_details SET approval_status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->bind_param("sii", $action_status, $admin_id, $payment_id);
            $stmt->execute();
            $stmt->close();

            if ($action_status === 'approved') {
                $transaction_type = 'payment_made';
                
                // Set notes based on payment method
                $notes = "Payment Approved: " . $payment_method;
                
                // ==========================================
                // FIX: Use the new ledger format (ledger_type = 'debt', balance_action = 'decrease')
                // ==========================================
                $stmt = $this->con->prepare("
                    INSERT INTO payment_ledgers 
                    (stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                    VALUES (?, 'debt', ?, ?, ?, 'decrease', ?)
                ");
                $stmt->bind_param("isids", $stockist_id, $transaction_type, $payment_id, $amount_paid, $notes);
                $stmt->execute();
                
                $ledger_id = $this->con->insert_id; 
                $stmt->close();

                // ==========================================
                // Allocate Payment to Unpaid Bills
                // ==========================================
                $remaining_payment = $amount_paid;

                $stmt_bills = $this->con->prepare("
                    SELECT inward_id, grand_total, paid_amt 
                    FROM stock_inward 
                    WHERE stockist_id = ? AND pay_status != 'paid' 
                    ORDER BY inward_id ASC
                ");
                $stmt_bills->bind_param("i", $stockist_id);
                $stmt_bills->execute();
                $unpaid_bills = $stmt_bills->get_result();
                $stmt_bills->close();

                if ($unpaid_bills->num_rows > 0) {
                    $stmt_alloc = $this->con->prepare("INSERT INTO payment_allocations (ledger_id, inward_id, amount_allocated) VALUES (?, ?, ?)");
                    $stmt_update = $this->con->prepare("UPDATE stock_inward SET paid_amt = ?, pay_status = ? WHERE inward_id = ?");

                    while ($bill = $unpaid_bills->fetch_assoc()) {
                        if ($remaining_payment <= 0) break; 

                        $bill_balance = (float)$bill['grand_total'] - (float)$bill['paid_amt'];
                        $allocate_amount = min($remaining_payment, $bill_balance);
                        
                        // Insert allocation record
                        $stmt_alloc->bind_param("iid", $ledger_id, $bill['inward_id'], $allocate_amount);
                        $stmt_alloc->execute();

                        // Calculate new paid amount and status
                        $new_paid_amt = (float)$bill['paid_amt'] + $allocate_amount;
                        $rounded_grand_total = round((float)$bill['grand_total']);
                        $rounded_paid_amt = round($new_paid_amt);
                        
                        $new_status = ($rounded_paid_amt >= $rounded_grand_total) ? 'paid' : 'partial';

                        // Update the bill
                        $stmt_update->bind_param("dsi", $new_paid_amt, $new_status, $bill['inward_id']);
                        $stmt_update->execute();

                        $remaining_payment -= $allocate_amount;
                    }
                    $stmt_alloc->close();
                    $stmt_update->close();
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

    public function submitManualEntry($data, $admin_id) {
        try {
            $commission_type = $data['commission_type']; // 'MRC' or 'DRC'
            $payment_type = $data['payment_type']; // 'account' or 'old_bill'
            $stockist_id = isset($data['stockist_id']) ? (int)$data['stockist_id'] : 0;
            $amount = (float)$data['amount'];
            $notes = isset($data['notes']) ? trim($data['notes']) : '';

            $ledger_wallet_type = ($commission_type === 'MRC') ? 'mrc_wallet' : 'drc_wallet';
            $settlement_type = ($commission_type === 'MRC') ? 'mrc_settlement' : 'drc_settlement';
            $comm_short = ($commission_type === 'MRC') ? 'mrc' : 'drc';

            $this->con->begin_transaction();

            // 1. Create receipt record in payment_details
            $payment_method = ($payment_type === 'account') ? 'Bank Transfer' : 'Commission Adjustment';
            $stmt_pd = $this->con->prepare("INSERT INTO payment_details (stockist_id, amount_paid, payment_method, bank_details, approval_status, approved_by, approved_at, commission_type) VALUES (?, ?, ?, ?, 'approved', ?, NOW(), ?)");
            $stmt_pd->bind_param("idssis", $stockist_id, $amount, $payment_method, $notes, $admin_id, $comm_short);
            
            if (!$stmt_pd->execute()) {
                throw new Exception("Failed to record payment details.");
            }
            $payment_id = $this->con->insert_id;
            $stmt_pd->close();

            // 2. Perform actions based on payment type
            if ($payment_type === 'old_bill') {
                
                // Deduct from wallet
                $stmt_w = $this->con->prepare("INSERT INTO payment_ledgers (stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) VALUES (?, ?, 'settled_to_bill', ?, ?, 'decrease', ?)");
                $stmt_w->bind_param("isids", $stockist_id, $ledger_wallet_type, $payment_id, $amount, $notes);
                $stmt_w->execute();
                $stmt_w->close();

                // Decrease debt (FIXED: Changed 'decrease_debt' to 'decrease')
                $stmt_d = $this->con->prepare("INSERT INTO payment_ledgers (stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) VALUES (?, 'debt', ?, ?, ?, 'decrease', ?)");
                $stmt_d->bind_param("isids", $stockist_id, $settlement_type, $payment_id, $amount, $notes);
                $stmt_d->execute();
                $ledger_id = $this->con->insert_id;
                $stmt_d->close();

                // Allocate to unpaid bills
                $remaining_payment = $amount;
                $stmt_bills = $this->con->prepare("SELECT inward_id, grand_total, paid_amt FROM stock_inward WHERE stockist_id = ? AND pay_status != 'paid' ORDER BY inward_id ASC");
                $stmt_bills->bind_param("i", $stockist_id);
                $stmt_bills->execute();
                $unpaid_bills = $stmt_bills->get_result();
                $stmt_bills->close();

                if ($unpaid_bills->num_rows > 0) {
                    $stmt_alloc = $this->con->prepare("INSERT INTO payment_allocations (ledger_id, inward_id, amount_allocated) VALUES (?, ?, ?)");
                    $stmt_update = $this->con->prepare("UPDATE stock_inward SET paid_amt = ?, pay_status = ? WHERE inward_id = ?");

                    while ($bill = $unpaid_bills->fetch_assoc()) {
                        if ($remaining_payment <= 0) break; 

                        $bill_balance = (float)$bill['grand_total'] - (float)$bill['paid_amt'];
                        $allocate_amount = min($remaining_payment, $bill_balance);
                        
                        $stmt_alloc->bind_param("iid", $ledger_id, $bill['inward_id'], $allocate_amount);
                        $stmt_alloc->execute();

                        $new_paid_amt = (float)$bill['paid_amt'] + $allocate_amount;
                        $rounded_grand_total = round((float)$bill['grand_total']);
                        $rounded_paid_amt = round($new_paid_amt);
                        $new_status = ($rounded_paid_amt >= $rounded_grand_total) ? 'paid' : 'partial';

                        $stmt_update->bind_param("dsi", $new_paid_amt, $new_status, $bill['inward_id']);
                        $stmt_update->execute();

                        $remaining_payment -= $allocate_amount;
                    }
                    $stmt_alloc->close();
                    $stmt_update->close();
                }
            } else {
                // Bank Account Payment (Wallet payout only)
                $stmt_w = $this->con->prepare("INSERT INTO payment_ledgers (stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) VALUES (?, ?, 'paid_to_bank', ?, ?, 'decrease', ?)");
                $stmt_w->bind_param("isids", $stockist_id, $ledger_wallet_type, $payment_id, $amount, $notes);
                $stmt_w->execute();
                $stmt_w->close();
            }

            $this->con->commit();
            return ['success' => true, 'msg' => 'Entry and adjustments processed successfully.'];

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
        
        // Sum up the wallets of all stockists assigned to this HQ
        $stmt = $this->con->prepare("
            SELECT SUM(CASE WHEN pl.balance_action = 'increase' THEN pl.amount ELSE -pl.amount END) as total_balance
            FROM payment_ledgers pl
            INNER JOIN stockists s ON pl.stockist_id = s.stockist_id
            WHERE s.hq_id = ? AND pl.ledger_type = ?
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
            SELECT p.*, s.hq_id, hq.state_id 
            FROM payment_details p
            LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
            LEFT JOIN headquarter hq ON s.hq_id = hq.headquarter_id
            WHERE p.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
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
}
?>