<?php

class Payment_model {
    private $con;

    public function __construct($con) {
        $this->con = $con;
    }

    // Get all stockists for the dropdown in the entry form
    public function getStockists($mr_id)
    {
        $status = 1;
        $stmt = $this->con->prepare("
            SELECT 
                s.stockist_id,
                s.stockist_name,
                h.hq_name
            FROM stockists s
            INNER JOIN mr_users m ON m.hq_id = s.hq_id
            LEFT JOIN headquarter h ON h.headquarter_id = s.hq_id
            WHERE m.m_id = ? AND s.status = ?
            ORDER BY s.stockist_name ASC
        ");

        $stmt->bind_param("ii", $mr_id, $status);
        $stmt->execute();

        $result = $stmt->get_result();

        $stockists = [];

        while ($row = $result->fetch_assoc()) {
            $stockists[] = $row;
        }

        $stmt->close();

        return $stockists;
    }

    // Get all payments for the list page (Secured to HQ)
    public function getAllPayments($mr_id = 0) {
        $mr_id = (int)$mr_id;
        $cond = "1=1";
        
        if ($mr_id > 0) {
            $cond .= " AND s.hq_id IN (SELECT hq_id FROM mr_users WHERE m_id = $mr_id)";
        }

        $query = "
            SELECT p.*, s.stockist_name 
            FROM payment_details p
            LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
            WHERE $cond
            ORDER BY p.created_at DESC
        ";
        
        $result = $this->con->query($query);
        $payments = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $payments[] = $row;
            }
        }
        return $payments;
    }

    // Save a new pending payment entry
    public function addPayment($data, $file) {
        try {
            $stockist_id = (int)$data['stockist_id'];
            $amount_paid = (float)$data['amount_paid'];
            $payment_method = $data['payment_method'];
            $bank_details = $data['bank_details'] ?? '';
            $approval_status = 'pending';

            $screenshot_path = null;
            if (isset($file['screenshot']) && $file['screenshot']['error'] === UPLOAD_ERR_OK) {
                $physical_upload_dir = '../uploads/payments/';
                
                if (!is_dir($physical_upload_dir)) {
                    mkdir($physical_upload_dir, 0777, true);
                }

                $file_extension = pathinfo($file['screenshot']['name'], PATHINFO_EXTENSION);
                $new_filename = 'pay_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                $target_file = $physical_upload_dir . $new_filename;

                if (move_uploaded_file($file['screenshot']['tmp_name'], $target_file)) {
                    $screenshot_path = 'uploads/payments/' . $new_filename;
                } else {
                    throw new Exception("Failed to upload the screenshot.");
                }
            }

            $stmt = $this->con->prepare("
                INSERT INTO payment_details 
                (stockist_id, amount_paid, payment_method, bank_details, screenshot_path, approval_status) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param("idssss", $stockist_id, $amount_paid, $payment_method, $bank_details, $screenshot_path, $approval_status);
            
            if ($stmt->execute()) {
                $stmt->close();
                return ['success' => true, 'msg' => 'Payment entry submitted successfully. Pending approval.'];
            } else {
                throw new Exception($stmt->error);
            }

        } catch (Exception $e) {
            return ['success' => false, 'msg' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Action: Fetch filtered payments and ledger history from the database (SECURED TO HQ)
    public function getFilteredPayments($mr_id, $stockist_id, $from_date) {
        $mr_id = (int)$mr_id;
        $stockist_id = (int)$stockist_id;
        
        $cond = "1=1";
        if ($stockist_id > 0) {
            $cond .= " AND s.stockist_id = " . $stockist_id;
        }

        // FIX: Added the HQ Security Filter so they can't see other HQs' bills
        if ($mr_id > 0) {
            $cond .= " AND s.hq_id IN (SELECT hq_id FROM mr_users WHERE m_id = $mr_id)";
        }

        // We use the EXACT SAME conditions as the getOutstandingBalance query
        // so PHP and SQL never disagree on what is a debit or credit.
        $query = "
            SELECT 
                l.id as ledger_id,
                l.stockist_id,
                s.stockist_name,
                l.transaction_type,
                l.balance_action,
                l.reference_id,
                l.amount,
                l.created_at,
                si.inward_no,
                si.order_id,
                (CASE WHEN LOWER(l.balance_action) = 'increase_debt' OR LOWER(l.transaction_type) IN ('bill_added', 'opening_balance', 'debit_note') THEN 1 ELSE 0 END) AS is_debit,
                (CASE WHEN LOWER(l.balance_action) = 'decrease_debt' OR LOWER(l.transaction_type) IN ('payment_made', 'credit_note', 'discount', 'payment') THEN 1 ELSE 0 END) AS is_credit
            FROM payment_ledgers l
            INNER JOIN stockists s ON l.stockist_id = s.stockist_id
            LEFT JOIN stock_inward si ON l.transaction_type = 'bill_added' AND l.reference_id = si.inward_id
            WHERE $cond
            ORDER BY l.stockist_id ASC, l.created_at ASC, l.id ASC
        ";

        $result = $this->con->query($query);
        $raw_ledger = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $raw_ledger[] = $row;
            }
        }

        $stockist_ledgers = [];
        foreach ($raw_ledger as $row) {
            $st_id = $row['stockist_id'];
            if (!isset($stockist_ledgers[$st_id])) {
                $stockist_ledgers[$st_id] = [
                    'bills' => [],
                    'credits' => 0
                ];
            }

            // Rely perfectly on SQL's classification to avoid typo/casing mismatches
            $is_debit = (int)$row['is_debit'] === 1;
            $is_credit = (int)$row['is_credit'] === 1;

            if ($is_debit) {
                $ref_text = $row['inward_no'];
                if (!$ref_text) {
                    // Fallback to formatted transaction name (e.g., "Opening Balance")
                    $ref_text = ucwords(str_replace('_', ' ', $row['transaction_type'])); 
                }

                $stockist_ledgers[$st_id]['bills'][] = [
                    'record_id' => $row['ledger_id'],
                    'created_at' => $row['created_at'],
                    'stockist_name' => $row['stockist_name'],
                    'stockist_id' => $st_id,
                    'order_id' => $row['order_id'],
                    'reference_no' => $ref_text,
                    'original_amount' => (float)$row['amount'],
                    'pending_amount' => (float)$row['amount'],
                    'status' => 'UNPAID'
                ];
            } else if ($is_credit) {
                $stockist_ledgers[$st_id]['credits'] += (float)$row['amount'];
            }
        }

        $processed_rows = [];
        foreach ($stockist_ledgers as $st_id => $data) {
            $available_credit = $data['credits'];

            foreach ($data['bills'] as &$bill) {
                if ($available_credit >= $bill['pending_amount']) {
                    $available_credit -= $bill['pending_amount'];
                    $bill['pending_amount'] = 0;
                    $bill['status'] = 'PAID';
                } else if ($available_credit > 0) {
                    $bill['pending_amount'] -= $available_credit;
                    $available_credit = 0;
                    $bill['status'] = 'PARTIAL';
                }
                
                // Only push to array if it matches date filter (if provided)
                if (!empty($from_date)) {
                    if (date('Y-m-d', strtotime($bill['created_at'])) !== $from_date) {
                        continue;
                    }
                }
                $processed_rows[] = $bill;
            }
        }

        // Sort newest first
        usort($processed_rows, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $processed_rows;
    }

    public function getOutstandingBalance($stockist_id) 
    {
        $stockist_id = (int)$stockist_id;
        
        // Exact same logic used in the SELECT CASE statements above
        $stmt = $this->con->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN LOWER(balance_action) = 'increase_debt' OR LOWER(transaction_type) IN ('bill_added', 'opening_balance', 'debit_note') THEN amount ELSE 0 END), 0) - 
                COALESCE(SUM(CASE WHEN LOWER(balance_action) = 'decrease_debt' OR LOWER(transaction_type) IN ('payment_made', 'credit_note', 'discount', 'payment') THEN amount ELSE 0 END), 0) AS total_outstanding
            FROM payment_ledgers 
            WHERE stockist_id = ?
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

    public function getSubmittedPayments($mr_id, $stockist_id, $from_date) {
        $mr_id = (int)$mr_id;
        $stockist_id = (int)$stockist_id;
        
        $cond = "1=1";
        
        // Filter by specific stockist if selected
        if ($stockist_id > 0) {
            $cond .= " AND p.stockist_id = $stockist_id";
        }

        // Filter to only show stockists assigned to this MR (Security)
        if ($mr_id > 0) {
            $cond .= " AND s.hq_id IN (SELECT hq_id FROM mr_users WHERE m_id = $mr_id)";
        }

        // Filter by Date if selected
        if (!empty($from_date)) {
            $safe_date = mysqli_real_escape_string($this->con, $from_date);
            $cond .= " AND DATE(p.created_at) = '$safe_date'";
        }

        // We alias the columns to match exactly what the JavaScript expects
        $query = "
            SELECT 
                p.id as reference_no,
                DATE(p.created_at) as payment_date,
                s.stockist_name,
                p.stockist_id,
                p.amount_paid as amount,
                p.payment_method as payment_mode,
                p.approval_status as status,
                p.screenshot_path as proof_image
            FROM payment_details p
            JOIN stockists s ON p.stockist_id = s.stockist_id
            WHERE $cond
            ORDER BY p.created_at DESC
        ";
        
        $result = $this->con->query($query);
        $payments = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Keep the status exactly as it is in the DB enum (lowercase)
                $row['status'] = strtolower($row['status']); 
                $payments[] = $row;
            }
        }
        
        return $payments;
    }
}
?>