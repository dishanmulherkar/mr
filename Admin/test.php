<?php
require_once 'config/db.php'; 

global $con; 

echo "<h2>Starting Old Payments Migration (Using Grand Total)...</h2>";

$stockists_query = $con->query("SELECT DISTINCT stockist_id FROM stock_inward");

while ($stockist = $stockists_query->fetch_assoc()) {
    $st_id = $stockist['stockist_id'];
    
    $credit_query = $con->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total_paid
        FROM payment_ledgers 
        WHERE stockist_id = ? 
        AND (LOWER(balance_action) = 'decrease_debt' OR LOWER(transaction_type) IN ('payment_made', 'credit_note', 'discount', 'payment'))
    ");
    $credit_query->bind_param("i", $st_id);
    $credit_query->execute();
    $credit_result = $credit_query->get_result()->fetch_assoc();
    
    $historical_credit = (float)$credit_result['total_paid'];
    $credit_query->close();

    // Changed to fetch grand_total
    $bills_query = $con->prepare("
        SELECT inward_id, grand_total 
        FROM stock_inward 
        WHERE stockist_id = ? 
        ORDER BY inward_id ASC
    ");
    $bills_query->bind_param("i", $st_id);
    $bills_query->execute();
    $bills_result = $bills_query->get_result();
    
    $update_stmt = $con->prepare("UPDATE stock_inward SET paid_amt = ?, pay_status = ? WHERE inward_id = ?");
    
    $bills_processed = 0;

    while ($bill = $bills_result->fetch_assoc()) {
        // Changed to use grand_total
        $bill_total = (float)$bill['grand_total'];
        
        if ($historical_credit >= $bill_total) {
            $paid_amt = $bill_total;
            $status = 'paid';
            $historical_credit -= $bill_total;
        } else if ($historical_credit > 0) {
            $paid_amt = $historical_credit;
            $status = 'partial';
            $historical_credit = 0;
        } else {
            $paid_amt = 0;
            $status = 'unpaid';
        }
        
        $update_stmt->bind_param("dsi", $paid_amt, $status, $bill['inward_id']);
        $update_stmt->execute();
        $bills_processed++;
    }
    
    $bills_query->close();
    $update_stmt->close();
    
    echo "Updated Stockist ID {$st_id}: Processed {$bills_processed} bills.<br>";
}

echo "<h2 style='color:green;'>Migration Complete! You can now delete this file.</h2>";
?>