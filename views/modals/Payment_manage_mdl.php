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
    public function addPayment($data, $file) 
    {
        try {
            $stockist_id = (int)($data['stockist_id'] ?? 0);
            $amount_paid = (float)($data['amount_paid'] ?? 0);

            $payment_method = trim($data['payment_method'] ?? '');

            // If "Other" is selected, use the custom payment method
            if ($payment_method === 'Other') {
                $other_payment_method = trim($data['other_payment_method'] ?? '');

                if ($other_payment_method === '') {
                    throw new Exception("Please enter the other payment method.");
                }

                $payment_method = $other_payment_method;
            }

            $bank_details = trim($data['bank_details'] ?? '');
            $approval_status = 'pending';
            $screenshot_path = null;

            // Upload payment proof
            if (isset($file['screenshot']) && $file['screenshot']['error'] === UPLOAD_ERR_OK) {

                $physical_upload_dir = '../uploads/payments/';

                if (!is_dir($physical_upload_dir)) {
                    mkdir($physical_upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($file['screenshot']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];

                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception("Invalid file type. Only JPG, JPEG, PNG and PDF are allowed.");
                }

                // Check file size - 2MB
                if ($file['screenshot']['size'] > 2 * 1024 * 1024) {
                    throw new Exception("Payment proof file must be less than 2MB.");
                }

                $new_filename = 'pay_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                $target_file = $physical_upload_dir . $new_filename;

                if (move_uploaded_file($file['screenshot']['tmp_name'], $target_file)) {
                    $screenshot_path = 'uploads/payments/' . $new_filename;
                } else {
                    throw new Exception("Failed to upload the payment proof.");
                }
            }

            // Insert payment (Note: commission_type defaults to 'none' in the DB schema for standard MR cash payments)
            $stmt = $this->con->prepare("
                INSERT INTO payment_details 
                (
                    stockist_id,
                    amount_paid,
                    payment_method,
                    bank_details,
                    screenshot_path,
                    approval_status
                ) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->con->error);
            }

            $stmt->bind_param(
                "idssss",
                $stockist_id,
                $amount_paid,
                $payment_method,
                $bank_details,
                $screenshot_path,
                $approval_status
            );

            if ($stmt->execute()) {
                $stmt->close();
                return [
                    'success' => true,
                    'msg' => 'Payment entry submitted successfully. Pending approval.'
                ];
            } else {
                throw new Exception($stmt->error);
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'msg' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    public function getFilteredPayments($mr_id, $stockist_id, $from_date) {
        $mr_id = (int)$mr_id;
        $stockist_id = (int)$stockist_id;
        
        $cond = "1=1";
        
        if ($stockist_id > 0) {
            $cond .= " AND s.stockist_id = " . $stockist_id;
        }

        if ($mr_id > 0) {
            $cond .= " AND s.hq_id IN (SELECT hq_id FROM mr_users WHERE m_id = $mr_id)";
        }

        if (!empty($from_date)) {
            $safe_date = mysqli_real_escape_string($this->con, $from_date);
            $cond .= " AND DATE(si.created_at) = '$safe_date'";
        }

        $query = "
            SELECT 
                si.inward_id AS record_id,
                si.created_at,
                s.stockist_name,
                si.stockist_id,
                si.order_id,
                si.inward_no AS reference_no,
                ROUND(si.grand_total) AS original_amount,
                GREATEST(0, ROUND(si.grand_total) - ROUND(si.paid_amt)) AS pending_amount,
                UPPER(si.pay_status) AS status,
                si.inward_no
            FROM stock_inward si
            INNER JOIN stockists s ON si.stockist_id = s.stockist_id
            WHERE $cond
            ORDER BY si.created_at DESC, si.inward_id DESC
        ";

        $result = $this->con->query($query);
        $processed_rows = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['original_amount'] = (float)$row['original_amount'];
                $row['pending_amount']  = (float)$row['pending_amount'];
                
                if (empty($row['reference_no'])) {
                    $row['reference_no'] = 'Bill ' . $row['record_id'];
                }

                $processed_rows[] = $row;
            }
        }

        return $processed_rows;
    }

  

    public function getOutstandingBalance($stockist_id) 
    {
        $stockist_id = (int)$stockist_id;
        
        // FIX: Added ROUND() around the SUM functions, included settlement types, and restricted to the 'debt' ledger
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


    public function getSubmittedPayments($mr_id, $stockist_id, $from_date) {
        $mr_id = (int)$mr_id;
        $stockist_id = (int)$stockist_id;
        
        $cond = "1=1";
        
        if ($stockist_id > 0) {
            $cond .= " AND p.stockist_id = $stockist_id";
        }

        if ($mr_id > 0) {
            $cond .= " AND s.hq_id IN (SELECT hq_id FROM mr_users WHERE m_id = $mr_id)";
        }

        if (!empty($from_date)) {
            $safe_date = mysqli_real_escape_string($this->con, $from_date);
            $cond .= " AND DATE(p.created_at) = '$safe_date'";
        }

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
                $row['status'] = strtolower($row['status']); 
                $payments[] = $row;
            }
        }
        
        return $payments;
    }
    

    // ==========================================
    // Fetch Outstanding and Eligible Cash Discount
    // ==========================================
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