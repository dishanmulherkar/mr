<?php

class PaymentApproval_mdl {
    private $con;

    public function __construct() {
        global $con;
        $this->con = $con;
    }

    // Fetch payments for the admin list
    public function getPaymentsForAdmin($status = '') {
        $query = "
            SELECT p.*, s.stockist_name 
            FROM payment_details p
            LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
            WHERE 1=1
        ";

        // Filter by status if provided (e.g., 'pending')
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

            $stmt = $this->con->prepare("SELECT stockist_id, amount_paid FROM payment_details WHERE id = ? AND approval_status = 'pending' FOR UPDATE");
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Payment not found or already processed.");
            }
            
            $payment = $result->fetch_assoc();
            $stockist_id = (int)$payment['stockist_id'];
            $amount_paid = (float)$payment['amount_paid'];
            $stmt->close();

            $stmt = $this->con->prepare("UPDATE payment_details SET approval_status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->bind_param("sii", $action_status, $admin_id, $payment_id);
            $stmt->execute();
            $stmt->close();

            if ($action_status === 'approved') {
                $transaction_type = 'payment_made';
                $balance_action = 'decrease_debt';
                
                $stmt = $this->con->prepare("INSERT INTO payment_ledgers (stockist_id, transaction_type, reference_id, amount, balance_action) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isids", $stockist_id, $transaction_type, $payment_id, $amount_paid, $balance_action);
                $stmt->execute();
                
                $ledger_id = $this->con->insert_id; 
                $stmt->close();

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
                        
                        $stmt_alloc->bind_param("iid", $ledger_id, $bill['inward_id'], $allocate_amount);
                        $stmt_alloc->execute();

                        $new_paid_amt = (float)$bill['paid_amt'] + $allocate_amount;
                        
                        // ==========================================
                        // THE FIX: Standard Round-Off Comparison
                        // ==========================================
                        // Round both the Grand Total and the Paid Amount to the nearest whole number (integer)
                        $rounded_grand_total = round((float)$bill['grand_total']);
                        $rounded_paid_amt = round($new_paid_amt);
                        
                        // Compare the rounded whole numbers
                        $new_status = ($rounded_paid_amt >= $rounded_grand_total) ? 'paid' : 'partial';

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
}
?>