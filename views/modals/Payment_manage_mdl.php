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
            
            // Capture mr_id
            $mr_id = !empty($_SESSION['mr_id']) ? (int)$_SESSION['mr_id'] : (!empty($data['hq_id']) ? (int)$data['hq_id'] : 0);
            
            $amount_paid = (float)($data['amount_paid'] ?? 0);

            // Sanitize and validate payment_date (fallback to current date if missing or invalid)
            $payment_date = !empty($data['payment_date']) ? trim($data['payment_date']) : date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $payment_date)) {
                $payment_date = date('Y-m-d');
            }

            $payment_method = trim($data['payment_method'] ?? '');
            $bank_id = (int)($data['bank_id'] ?? 0);

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

            // Insert payment with payment_date
            $stmt = $this->con->prepare("
                INSERT INTO payment_details 
                (
                    stockist_id,
                    mr_id,
                    amount_paid,
                    payment_date,
                    payment_method,
                    bank_details,
                    screenshot_path,
                    approval_status,
                    bank_id
                ) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->con->error);
            }

            // Bind parameters:
            // i -> stockist_id (int)
            // i -> mr_id (int)
            // d -> amount_paid (double/float)
            // s -> payment_date (string)
            // s -> payment_method (string)
            // s -> bank_details (string)
            // s -> screenshot_path (string)
            // s -> approval_status (string)
            // i -> bank_id (int)
           // Old (incorrect):
            // $stmt->bind_param("iiddssssi", ...);

            // New (correct):
            $stmt->bind_param(
                "iidsssssi",
                $stockist_id,       // i (integer)
                $mr_id,             // i (integer)
                $amount_paid,       // d (double / decimal)
                $payment_date,      // s (string - 'YYYY-MM-DD')
                $payment_method,    // s (string)
                $bank_details,      // s (string)
                $screenshot_path,   // s (string)
                $approval_status,   // s (string)
                $bank_id            // i (integer)
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
                si.inward_id,
                si.inward_id AS record_id,
                si.created_at,
                s.stockist_name,
                si.stockist_id,
                si.order_id,
                si.inward_no AS reference_no,
                si.inward_no,
                ROUND(si.grand_total, 2) AS original_amount,
                GREATEST(
                    0, 
                    ROUND(
                        (COALESCE(si.grand_total, 0) + COALESCE(si.cd_penalty_amt, 0)) - 
                        (COALESCE(si.paid_amt, 0) + COALESCE(si.cd_earned_amt, 0)), 
                        2
                    )
                ) AS pending_amount,
                UPPER(si.pay_status) AS status
            FROM stock_inward si
            INNER JOIN stockists s ON si.stockist_id = s.stockist_id
            WHERE $cond
            ORDER BY si.created_at ASC, si.inward_id ASC
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
                    ROUND(
                        COALESCE(SUM(
                            CASE 
                                WHEN LOWER(balance_action) IN ('increase', 'increase_debt') THEN amount
                                WHEN (balance_action IS NULL OR balance_action = '') AND LOWER(transaction_type) IN ('bill_added', 'debit_note') THEN amount
                                ELSE 0 
                            END
                        ), 0)
                        -
                        COALESCE(SUM(
                            CASE 
                                WHEN LOWER(balance_action) IN ('decrease', 'decrease_debt') THEN amount
                                WHEN (balance_action IS NULL OR balance_action = '') AND LOWER(transaction_type) IN ('payment_made', 'credit_note', 'discount', 'payment', 'mrc_settlement', 'drc_settlement', 'asm_settlement', 'settled_to_bill') THEN amount
                                ELSE 0 
                            END
                        ), 0)
                    , 2) AS total_outstanding
                FROM payment_ledgers 
                WHERE stockist_id = ? 
                AND ledger_type IN ('debt', 'credit')
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
        * 1. Get Super Stockist ID, Opening Balance details
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
                'advance_amount'    => 0.00,
                'bill_details'      => []
            ];
        }

        $super_stockist_id = (int)$stockistData['super_stockist_id'];

        /*
        * 2. Calculate Pending Opening Balance (Debit vs Credit)
        */
        $ob_amount = (float)($stockistData['opening_balance'] ?? 0);
        $ob_type   = strtolower($stockistData['opening_balance_type'] ?? 'debt');
        $ob_date   = !empty($stockistData['opening_balance_date']) ? $stockistData['opening_balance_date'] : date('Y-m-d');
        
        $pending_ob_debt    = 0.00;
        $remaining_advance  = 0.00;
        $total_used_advance = 0.00;
        $total_orig_advance = 0.00;

        // CASE A: Stockist has Pending DEBT Opening Balance
        if ($ob_amount > 0 && $ob_type === 'debt') {
            $stmtOBPaid = $this->con->prepare("
                SELECT COALESCE(SUM(pa.amount_allocated), 0) as allocated_ob 
                FROM payment_allocations pa
                INNER JOIN payment_ledgers pl ON pl.id = pa.ledger_id
                WHERE pl.stockist_id = ? 
                AND (pa.inward_id IS NULL OR pa.inward_id = 0)
            ");
            $stmtOBPaid->bind_param("i", $stockist_id);
            $stmtOBPaid->execute();
            $obPaidRes = $stmtOBPaid->get_result()->fetch_assoc();
            $stmtOBPaid->close();

            $allocated_ob    = (float)($obPaidRes['allocated_ob'] ?? 0);
            $pending_ob_debt = max(0, round($ob_amount - $allocated_ob, 2));
        }

        // CASE B: Stockist has ADVANCE Credit (Payments & Credit Opening Balance)
        $stmtAdv = $this->con->prepare("
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
                    -- 1. Credit Opening Balance
                    (pl.transaction_type = 'opening_balance' AND (pl.ledger_type = 'credit' OR pl.balance_action = 'decrease'))
                    -- 2. Real Payments (excludes automatic CD discounts)
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
        $stmtAdv->execute();
        $advData = $stmtAdv->get_result()->fetch_assoc();
        $stmtAdv->close();

        $remaining_advance  = (float)($advData['total_remaining_advance'] ?? 0.00);
        $total_used_advance = (float)($advData['total_used_advance'] ?? 0.00);
        $total_orig_advance = (float)($advData['total_original_advance'] ?? 0.00);

        // Fallback if stockists table has credit opening balance not recorded in payment_ledgers
        if ($total_orig_advance <= 0 && $ob_type === 'credit' && $ob_amount > 0) {
            $total_orig_advance = $ob_amount;
            $remaining_advance  = $ob_amount;
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
        * 4. Get unpaid / partially paid bills
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

                -- NEW ELIGIBLE 4% CD
                CASE 
                    WHEN DATEDIFF(CURDATE(), inward_date) <= ? AND COALESCE(cd_percent, 0) = 0 
                    THEN grand_total - ROUND((sub_total * 0.96) + (COALESCE(gst_amount, 0) * 0.96) + COALESCE(other_charges, 0) - COALESCE(discount, 0)) 
                    ELSE 0 
                END AS eligible_4_cd,

                -- NEW ELIGIBLE 2% CD
                CASE 
                    WHEN DATEDIFF(CURDATE(), inward_date) > ? AND DATEDIFF(CURDATE(), inward_date) <= ? AND COALESCE(cd_percent, 0) = 0 
                    THEN grand_total - ROUND((sub_total * 0.98) + (COALESCE(gst_amount, 0) * 0.98) + COALESCE(other_charges, 0) - COALESCE(discount, 0)) 
                    ELSE 0 
                END AS eligible_2_cd,

                -- REVOKED PENALTY
                CASE
                    WHEN COALESCE(cd_percent, 0) = 4 THEN
                        CASE 
                            WHEN DATEDIFF(CURDATE(), inward_date) <= ? THEN 0 
                            WHEN DATEDIFF(CURDATE(), inward_date) <= ? THEN 
                                ROUND((sub_total * 98/96) + (COALESCE(gst_amount, 0) * 98/96) + COALESCE(other_charges, 0) - COALESCE(discount, 0)) - grand_total
                            ELSE 
                                ROUND((sub_total * 100/96) + (COALESCE(gst_amount, 0) * 100/96) + COALESCE(other_charges, 0) - COALESCE(discount, 0)) - grand_total
                        END
                    WHEN COALESCE(cd_percent, 0) = 2 THEN
                        CASE
                            WHEN DATEDIFF(CURDATE(), inward_date) <= ? THEN 0
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
            "iiiiiii",
            $cd_4_days,
            $cd_4_days, $cd_2_days,
            $cd_4_days, $cd_2_days,
            $cd_2_days,
            $stockist_id
        );

        $stmt->execute();
        $result = $stmt->get_result();

        $total_pending       = 0.00;
        $total_eligible_4_cd = 0.00;
        $total_eligible_2_cd = 0.00;
        $total_penalty       = 0.00;
        $bills               = [];

        // Prepend Opening Balance if active debt exists
        if ($pending_ob_debt > 0) {
            $total_pending += $pending_ob_debt;
            $bills[] = [
                'inward_id'          => 0,
                'inward_no'          => 'OPENING-BAL (DEBT)',
                'inward_date'        => $ob_date,
                'gross_amount'       => $ob_amount,
                'sub_total'          => $ob_amount,
                'cd_percent'         => 0,
                'paid_amt'           => ($ob_amount - $pending_ob_debt),
                'pending_amount'     => $pending_ob_debt,
                'bill_age_days'      => 0,
                'eligible_4_cd'      => 0,
                'eligible_2_cd'      => 0,
                'penalty_amount'     => 0,
                'is_opening_balance' => 1,
                'balance_type'       => 'debt'
            ];
        }

        // Prepend Advance Credit (Surplus from payment or opening balance credit)
        if ($remaining_advance > 0) {
            $total_pending -= $remaining_advance;
            $bills[] = [
                'inward_id'          => 0,
                'inward_no'          => 'ADVANCE CREDIT',
                'inward_date'        => $ob_date,
                'gross_amount'       => $total_orig_advance,
                'sub_total'          => $total_orig_advance,
                'cd_percent'         => 0,
                'paid_amt'           => $total_used_advance,
                'pending_amount'     => -$remaining_advance, // Negative amount denotes credit
                'bill_age_days'      => 0,
                'eligible_4_cd'      => 0,
                'eligible_2_cd'      => 0,
                'penalty_amount'     => 0,
                'is_opening_balance' => ($ob_type === 'credit' && $total_orig_advance == $ob_amount) ? 1 : 0,
                'balance_type'       => 'credit'
            ];
        }

        while ($row = $result->fetch_assoc()) {
            $pending = (float)$row['pending_amount'];

            if ($pending > 0) {
                $total_pending       += $pending;
                $total_eligible_4_cd += (float)$row['eligible_4_cd'];
                $total_eligible_2_cd += (float)$row['eligible_2_cd'];
                $total_penalty       += (float)$row['penalty_amount'];
            }
            $row['is_opening_balance'] = 0;
            $row['balance_type']       = 'debt';
            $bills[] = $row;
        }
        $stmt->close();

        $total_cd    = $total_eligible_4_cd + $total_eligible_2_cd;
        $net_payable = $total_pending - $total_cd + $total_penalty;

        return [
            'total_outstanding' => round($total_pending, 2),
            'eligible_cd'       => round($total_cd, 2),
            'total_penalty'     => round($total_penalty, 2),
            'net_payable'       => round($net_payable, 2),
            'advance_amount'    => round($remaining_advance, 2), // Total unutilized advance
            'bill_details'      => $bills
        ];
    }
    
     public function getSuperStockistIdByMr($mr_id)
    {
        $mr_id = (int)$mr_id;
        $stmt = $this->con->prepare("
            SELECT h.super_stockist_id 
            FROM mr_users u
            INNER JOIN headquarter h ON u.hq_id = h.headquarter_id
            WHERE u.m_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $mr_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return (int)($row['super_stockist_id'] ?? 0);
        }
        $stmt->close();
        return 0;
    }

    public function getBanksBySuperStockist($super_stockist_id)
    {
        $super_stockist_id = (int)$super_stockist_id;
        
        $sql = "SELECT b.bank_id, b.bank_name 
                FROM banks b
                INNER JOIN super_stockist_banks ssb ON b.bank_id = ssb.bank_id
                WHERE ssb.super_stockist_id = ? AND b.is_active = 1
                ORDER BY b.bank_name ASC";
        
        $stmt = $this->con->prepare($sql);
        $stmt->bind_param("i", $super_stockist_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $banks = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $banks[] = $row;
            }
        }
        $stmt->close();
        
        return $banks;
    }

    public function getBillPaymentBreakdown($inward_id)
    {
        $inward_id = (int)$inward_id;

        // 1. Fetch Inward Bill Details
        $stmt_bill = $this->con->prepare("
            SELECT 
                inward_id, inward_no, inward_date, grand_total, paid_amt, 
                cd_earned_amt, cd_penalty_amt, pay_status
            FROM stock_inward 
            WHERE inward_id = ?
            LIMIT 1
        ");
        $stmt_bill->bind_param("i", $inward_id);
        $stmt_bill->execute();
        $bill = $stmt_bill->get_result()->fetch_assoc();
        $stmt_bill->close();

        if (!$bill) {
            return ['success' => false, 'msg' => 'Bill not found'];
        }

        // 2. Fetch Direct Cash Allocations (payment_allocations)
        $stmt_alloc = $this->con->prepare("
            SELECT 
                pa.amount_allocated,
                pl.created_at,
                pl.transaction_type,
                pl.notes,
                pd.payment_method,
                b.bank_name
            FROM payment_allocations pa
            INNER JOIN payment_ledgers pl ON pa.ledger_id = pl.id
            LEFT JOIN payment_details pd ON pd.id = pl.reference_id
            LEFT JOIN banks b ON b.bank_id = pd.bank_id
            WHERE pa.inward_id = ?
            ORDER BY pl.created_at ASC
        ");
        $stmt_alloc->bind_param("i", $inward_id);
        $stmt_alloc->execute();
        $alloc_res = $stmt_alloc->get_result();
        
        $allocations = [];
        while ($row = $alloc_res->fetch_assoc()) {
            $allocations[] = $row;
        }
        $stmt_alloc->close();

        // 3. Fetch CD and Commission Adjustments (DRC, MRC, ASM)
        // 3. Fetch CD and Commission Adjustments (DRC, MRC, ASM, Settlements)
            $stmt_adj = $this->con->prepare("
                SELECT 
                    amount,
                    created_at,
                    transaction_type,
                    notes
                FROM payment_ledgers
                WHERE reference_id = ? 
                AND transaction_type IN ('payment_made', 'mrc_settlement', 'drc_settlement', 'asm_settlement', 'settled_to_bill')
                AND balance_action = 'decrease'
                ORDER BY created_at ASC
            ");
            $stmt_adj->bind_param("i", $inward_id);
            $stmt_adj->execute();
            $adj_res = $stmt_adj->get_result();

            $adjustments = [];
            while ($row = $adj_res->fetch_assoc()) {
                $adjustments[] = $row;
            }
            $stmt_adj->close();

        return [
            'success'     => true,
            'bill'        => $bill,
            'allocations' => $allocations,
            'adjustments' => $adjustments
        ];
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
}
?>