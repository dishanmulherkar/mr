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
            // Start Transaction
            $this->con->begin_transaction();

            // 1. Fetch and lock the pending payment record (prevents double-clicks/race conditions)
            $stmt = $this->con->prepare("SELECT stockist_id, amount_paid FROM payment_details WHERE id = ? AND approval_status = 'pending' FOR UPDATE");
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Payment not found or already processed.");
            }
            
            $payment = $result->fetch_assoc();
            $stockist_id = $payment['stockist_id'];
            $amount_paid = $payment['amount_paid'];
            $stmt->close();

            // 2. Update the payment_details table
            $stmt = $this->con->prepare("UPDATE payment_details SET approval_status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->bind_param("sii", $action_status, $admin_id, $payment_id);
            $stmt->execute();
            $stmt->close();

            // 3. ONLY if Approved, Insert into the payment_ledgers to decrease debt
            if ($action_status === 'approved') {
                $transaction_type = 'payment_made';
                $balance_action = 'decrease_debt';
                
                $stmt = $this->con->prepare("INSERT INTO payment_ledgers (stockist_id, transaction_type, reference_id, amount, balance_action) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isids", $stockist_id, $transaction_type, $payment_id, $amount_paid, $balance_action);
                $stmt->execute();
                $stmt->close();
            }

            // Commit the transaction
            $this->con->commit();
            return ['success' => true, 'msg' => 'Payment marked as ' . strtoupper($action_status) . ' successfully.'];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }
}
?>