<?php
class payment_ledger_mdl
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getStates()
    {
      // Super Admin can see all states
        if ($_SESSION['admin_role'] == 'Super Admin') {

            return mysqli_query(
                $this->con,
                "SELECT *
                FROM state
                WHERE state_status = 1
                ORDER BY state_name"
            );
        }

        // Admin can see only assigned states
        $admin_id = (int)$_SESSION['admin_id'];

        return mysqli_query(
            $this->con,
            "SELECT
                s.*
            FROM state s
            INNER JOIN admin_state ast
                ON s.state_id = ast.state_id
            WHERE ast.admin_id = '$admin_id'
            AND s.state_status = 1
            ORDER BY s.state_name"
        );
    }

    public function getHQName($hq_id)
    {
        $sql = "SELECT hq_name FROM headquarter WHERE headquarter_id = '$hq_id'";
        $res = mysqli_query($this->con, $sql);
        return $res ? mysqli_fetch_assoc($res) : null;
    }

    public function getStockistName($stockist_id)
    {
        $sql = "SELECT stockist_name FROM stockists WHERE stockist_id = '$stockist_id'";
        $res = mysqli_query($this->con, $sql);
        return $res ? mysqli_fetch_assoc($res) : null;
    }

// Filtered to show bill_added, payment_made, and commission settlements FOR DEBT ONLY
    public function getReport($stockist_id, $from_date, $to_date)
    {
        $sql = "SELECT pl.*, si.inward_no, pd.id as pay_id ,pd.payment_method ,b.bank_name
                FROM payment_ledgers pl 
                LEFT JOIN stock_inward si ON si.inward_id = pl.reference_id 
                LEFT JOIN payment_details pd ON pd.id = pl.reference_id 
                LEFT JOIN banks b ON b.bank_id = pd.bank_id
                WHERE pl.stockist_id = '$stockist_id' 
                AND pl.ledger_type = 'debt'  /* <-- THE FIX IS HERE */
                AND pl.transaction_type IN ('bill_added', 'payment_made', 'mrc_settlement', 'drc_settlement', 'settled_to_bill')
                AND DATE(pl.created_at) >= '$from_date' 
                AND DATE(pl.created_at) <= '$to_date'
                ORDER BY pl.created_at ASC, pl.id ASC";
                
        return mysqli_query($this->con, $sql);
    }

    // Opening balance calculation filtered for the same transaction types
    public function getOpeningBalance($stockist_id, $from_date)
    {
        $sql = "SELECT 
                    SUM(CASE WHEN transaction_type = 'bill_added' THEN amount ELSE 0 END) as total_inc,
                    SUM(CASE WHEN transaction_type IN ('payment_made', 'mrc_settlement', 'drc_settlement', 'settled_to_bill') THEN amount ELSE 0 END) as total_dec
                FROM payment_ledgers 
                WHERE stockist_id = '$stockist_id' 
                AND ledger_type = 'debt'  /* <-- THE FIX IS HERE */
                AND transaction_type IN ('bill_added', 'payment_made', 'mrc_settlement', 'drc_settlement', 'settled_to_bill')
                AND DATE(created_at) < '$from_date'";
        
        $res = mysqli_query($this->con, $sql);
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $inc = (float)$row['total_inc'];
            $dec = (float)$row['total_dec'];
            return $inc - $dec; // Positive = Debit Balance, Negative = Credit Balance
        }
        return 0;
    }
   // ==========================================
    // MR Commission (MRC) Report (Grouped by Ref ID)
    // ==========================================
    public function getReportmrc($hq_id, $from_date, $to_date)
    {
        // 1. Fetch the corresponding MR ID (m_id) for this HQ
        $stmt_mr = $this->con->prepare("SELECT m_id FROM mr_users WHERE hq_id = ? LIMIT 1");
        $stmt_mr->bind_param("i", $hq_id);
        $stmt_mr->execute();
        $res_mr = $stmt_mr->get_result()->fetch_assoc();
        $stmt_mr->close();
        
        $mr_id = $res_mr ? (int)$res_mr['m_id'] : 0;

        // 2. Run the main report query
        $sql = "SELECT 
                    MIN(p.id) AS id,
                    MIN(p.created_at) AS created_at,
                    p.reference_id,
                    p.transaction_type,
                    p.balance_action,
                    MAX(p.notes) AS notes,
                    
                    -- Safely fetch ALL settled bill numbers for this transaction
                    (
                        SELECT GROUP_CONCAT(DISTINCT si.inward_no SEPARATOR ', ')
                        FROM payment_ledgers pd
                        INNER JOIN payment_allocations pa ON pd.id = pa.ledger_id
                        INNER JOIN stock_inward si ON pa.inward_id = si.inward_id
                        WHERE pd.reference_id = p.reference_id AND pd.ledger_type = 'debt'
                    ) AS settled_bill_no,
                    
                    SUM(p.amount) AS amount,
                    IFNULL(MAX(s.stockist_name), 'Bank / General') AS stockist_name,
                    
                    -- Fetch Outstanding balance efficiently from the joined table
                    COALESCE(MAX(debt_calc.outstanding_amount), 0) AS stockist_outstanding
                    
                FROM payment_ledgers p
                
                -- Only join the stockist table
                LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
                
                -- Join the aggregated debt calculation exactly once per stockist
                LEFT JOIN (
                    SELECT 
                        stockist_id,
                        ROUND(COALESCE(SUM(CASE WHEN LOWER(balance_action) IN ('increase', 'increase_debt') OR LOWER(transaction_type) IN ('bill_added', 'opening_balance', 'debit_note') THEN amount ELSE 0 END), 0)) - 
                        ROUND(COALESCE(SUM(CASE WHEN LOWER(balance_action) IN ('decrease', 'decrease_debt') OR LOWER(transaction_type) IN ('payment_made', 'credit_note', 'discount', 'payment', 'mrc_settlement', 'drc_settlement') THEN amount ELSE 0 END), 0)) AS outstanding_amount
                    FROM payment_ledgers 
                    WHERE stockist_id > 0 AND ledger_type = 'debt'
                    GROUP BY stockist_id
                ) debt_calc ON p.stockist_id = debt_calc.stockist_id
                
                -- FIX: Check stockist HQ, OR check if user_id matches legacy HQ ID or new MR ID
                WHERE (s.hq_id = ? OR p.user_id IN (?, ?))
                  AND p.ledger_type = 'mrc_wallet'
                  AND DATE(p.created_at) >= ? 
                  AND DATE(p.created_at) <= ?
                
                GROUP BY 
                    DATE(p.created_at), 
                    p.reference_id, 
                    p.transaction_type, 
                    p.balance_action,
                    p.stockist_id
                    
                ORDER BY MIN(p.created_at) ASC, MIN(p.id) ASC";
                
        $stmt = $this->con->prepare($sql);
        // Bind 5 parameters: HQ ID (for stockist), HQ ID (for legacy bank), MR ID (for new bank), From Date, To Date
        $stmt->bind_param("iiiss", $hq_id, $hq_id, $mr_id, $from_date, $to_date);
        $stmt->execute();
        
        return $stmt->get_result();
    }

   // ==========================================
    // Opening balance calculation for MRC Wallet
    // ==========================================
    public function getOpeningBalancemrc($hq_id, $from_date)
    {
        // 1. Fetch the corresponding MR ID (m_id) for this HQ
        $stmt_mr = $this->con->prepare("SELECT m_id FROM mr_users WHERE hq_id = ? LIMIT 1");
        $stmt_mr->bind_param("i", $hq_id);
        $stmt_mr->execute();
        $res_mr = $stmt_mr->get_result()->fetch_assoc();
        $stmt_mr->close();
        
        $mr_id = $res_mr ? (int)$res_mr['m_id'] : 0;

        // 2. Calculate Opening Balance
        // Added LOWER() to ensure case-insensitivity on balance_action so no commissions are dropped
        $sql = "SELECT 
                    SUM(CASE WHEN LOWER(p.balance_action) = 'increase' THEN p.amount ELSE 0 END) as total_inc,
                    SUM(CASE WHEN LOWER(p.balance_action) = 'decrease' THEN p.amount ELSE 0 END) as total_dec
                FROM payment_ledgers p
                
                -- LEFT JOIN so we don't lose 'Bank / General' transactions (0)
                LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
                
                -- FIX: Check stockist HQ, OR check if user_id matches legacy HQ ID or new MR ID
                WHERE (s.hq_id = ? OR p.user_id IN (?, ?))
                AND p.ledger_type = 'mrc_wallet'
                AND DATE(p.created_at) < ?";

        // Use prepared statements for maximum security
        $stmt = $this->con->prepare($sql);
        
        // Bind 4 parameters: HQ ID (for stockist), HQ ID (for legacy bank), MR ID (for new bank), From Date
        $stmt->bind_param("iiis", $hq_id, $hq_id, $mr_id, $from_date);
        $stmt->execute();
        
        $res = $stmt->get_result();
        
        if ($res && $row = $res->fetch_assoc()) {
            $inc = (float)$row['total_inc'];
            $dec = (float)$row['total_dec'];
            
            $stmt->close();
            
            // For a wallet, Increase (Earned) - Decrease (Settled) = Current Balance
            return $inc - $dec; 
        }
        
        if (isset($stmt)) {
            $stmt->close();
        }
        
        return 0.00;
    }
   // ==========================================
    // DR Commission (DRC) Report
    // ==========================================
  // ==========================================
    // DR Commission (DRC) Report
    // ==========================================
    public function getReportdrc($hq_id, $from_date, $to_date)
    {
        // 1. Fetch the corresponding MR ID (m_id) to ensure we don't drop direct wallet earnings
        $mr_id = 0;
        $mr_query = mysqli_query($this->con, "SELECT m_id FROM mr_users WHERE hq_id = '$hq_id' LIMIT 1");
        if ($mr_query && $mr_row = mysqli_fetch_assoc($mr_query)) {
            $mr_id = (int)$mr_row['m_id'];
        }

        $sql = "SELECT 
                    p.*, 
                    COALESCE(s.stockist_name, 'Bank Payout / General') AS stockist_name,
                    si.inward_no AS settled_bill_no,
                    COALESCE(debt_calc.outstanding_amount, 0) AS stockist_outstanding

                FROM payment_ledgers p
                
                -- Changed to LEFT JOIN to prevent dropping records with stockist_id = 0
                LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
                
                -- Trace the HQ for Bank Payouts by joining payment_details and mr_users
                LEFT JOIN payment_details pd ON p.reference_id = pd.id AND p.stockist_id = 0
                LEFT JOIN mr_users m ON pd.mr_id = m.m_id
                
                LEFT JOIN payment_allocations pa ON p.id = pa.ledger_id AND p.transaction_type IN ('drc_settlement', 'settled_to_bill')
                LEFT JOIN stock_inward si ON pa.inward_id = si.inward_id
                
                -- JOIN the aggregated debt calculation
                LEFT JOIN (
                    SELECT 
                        stockist_id,
                        ROUND(COALESCE(SUM(CASE WHEN LOWER(balance_action) IN ('increase', 'increase_debt') OR LOWER(transaction_type) IN ('bill_added', 'opening_balance', 'debit_note') THEN amount ELSE 0 END), 0)) - 
                        ROUND(COALESCE(SUM(CASE WHEN LOWER(balance_action) IN ('decrease', 'decrease_debt') OR LOWER(transaction_type) IN ('payment_made', 'credit_note', 'discount', 'payment', 'mrc_settlement', 'drc_settlement', 'settled_to_bill') THEN amount ELSE 0 END), 0)) AS outstanding_amount
                    FROM payment_ledgers 
                    WHERE stockist_id > 0 AND ledger_type = 'debt'
                    GROUP BY stockist_id
                ) debt_calc ON p.stockist_id = debt_calc.stockist_id

                -- FIX: Catch earnings by checking user_id as well as stockist/MR HQ
                WHERE (s.hq_id = '$hq_id' OR p.user_id IN ('$hq_id', '$mr_id'))
                AND p.ledger_type = 'drc_wallet'
                AND DATE(p.created_at) >= '$from_date' 
                AND DATE(p.created_at) <= '$to_date'
                ORDER BY p.created_at ASC, p.id ASC";
                
        return mysqli_query($this->con, $sql);
    }

    // ==========================================
    // Opening balance calculation for DRC Wallet
    // ==========================================
    public function getOpeningBalancedrc($hq_id, $from_date)
    {
        // Fetch MR ID to ensure we don't drop direct wallet earnings in the opening balance
        $mr_id = 0;
        $mr_query = mysqli_query($this->con, "SELECT m_id FROM mr_users WHERE hq_id = '$hq_id' LIMIT 1");
        if ($mr_query && $mr_row = mysqli_fetch_assoc($mr_query)) {
            $mr_id = (int)$mr_row['m_id'];
        }

        $sql = "SELECT 
                    SUM(CASE WHEN LOWER(p.balance_action) = 'increase' THEN p.amount ELSE 0 END) as total_inc,
                    SUM(CASE WHEN LOWER(p.balance_action) = 'decrease' THEN p.amount ELSE 0 END) as total_dec
                FROM payment_ledgers p
                
                LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
                
                LEFT JOIN payment_details pd ON p.reference_id = pd.id AND p.stockist_id = 0
                LEFT JOIN mr_users m ON pd.mr_id = m.m_id
                
                -- FIX: Catch earnings by checking user_id as well as stockist/MR HQ
                WHERE (s.hq_id = '$hq_id' OR p.user_id IN ('$hq_id', '$mr_id'))
                AND p.ledger_type = 'drc_wallet'
                AND DATE(p.created_at) < '$from_date'";

        $res = mysqli_query($this->con, $sql);
        
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $inc = (float)$row['total_inc'];
            $dec = (float)$row['total_dec'];
            
            return $inc - $dec; 
        }
        
        return 0;
    }
   // ==========================================
    // ASM Commission (ASM) Report
    // ==========================================
 public function getReportasm($hq_id, $from_date, $to_date)
    {
        $sql = "SELECT 
                    MIN(p.id) AS id,
                    p.user_id,
                    IF(COUNT(p.id) > 1, 0, MAX(p.stockist_id)) AS stockist_id,
                    p.ledger_type,
                    p.transaction_type,
                    p.reference_id,
                    SUM(p.amount) AS amount,
                    p.balance_action,
                    
                    -- If multiple rows are merged, rename the note to indicate it's a combined payout
                    IF(COUNT(p.id) > 1, CONCAT('ASM Commission Earned (Payout #', p.reference_id, ')'), MAX(p.notes)) AS notes,
                    
                    MIN(p.created_at) AS created_at,
                    MAX(p.updated_at) AS updated_at,
                    
                    -- Fallback the stockist name to 'Combined Payout' if old rows are merged
                    IF(COUNT(p.id) > 1, 'Combined Payout / General', COALESCE(MAX(s.stockist_name), 'Combined Payout / General')) AS stockist_name,
                    MAX(si.inward_no) AS settled_bill_no,
                    
                    -- Set outstanding debt to 0 if it's a combined row, otherwise run the subquery
                    IF(COUNT(p.id) > 1, 0, MAX((
                        SELECT 
                            ROUND(COALESCE(SUM(CASE WHEN LOWER(balance_action) IN ('increase', 'increase_debt') OR LOWER(transaction_type) IN ('bill_added', 'opening_balance', 'debit_note') THEN amount ELSE 0 END), 0)) - 
                            ROUND(COALESCE(SUM(CASE WHEN LOWER(balance_action) IN ('decrease', 'decrease_debt') OR LOWER(transaction_type) IN ('payment_made', 'credit_note', 'discount', 'payment', 'mrc_settlement', 'drc_settlement', 'settled_to_bill') THEN amount ELSE 0 END), 0))
                        FROM payment_ledgers 
                        WHERE stockist_id = p.stockist_id AND stockist_id > 0 AND ledger_type = 'debt'
                    ))) AS stockist_outstanding

                FROM payment_ledgers p
                LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
                LEFT JOIN stock_inward si ON p.reference_id = si.inward_id AND p.transaction_type IN ('mrc_settlement', 'settled_to_bill')
                
                WHERE p.user_id = ? 
                AND p.ledger_type = 'asm_wallet'
                AND DATE(p.created_at) >= ? 
                AND DATE(p.created_at) <= ?
                
                -- Grouping by these columns merges old split records while keeping math accurate
                GROUP BY p.user_id, p.ledger_type, p.transaction_type, p.reference_id, p.balance_action, DATE(p.created_at)
                ORDER BY created_at ASC, id ASC";
                
        $stmt = $this->con->prepare($sql);
        $stmt->bind_param("iss", $hq_id, $from_date, $to_date);
        $stmt->execute();
        return $stmt->get_result();
    }

   // ==========================================
    // Opening balance calculation for ASM Wallet
    // ==========================================
    public function getOpeningBalanceAsm($hq_id, $from_date)
    {
        $sql = "SELECT 
                    SUM(CASE WHEN balance_action = 'increase' THEN amount ELSE 0 END) as total_inc,
                    SUM(CASE WHEN balance_action = 'decrease' THEN amount ELSE 0 END) as total_dec
                FROM payment_ledgers 
                WHERE user_id = ? 
                AND ledger_type = 'asm_wallet'
                AND DATE(created_at) < ?";

        $stmt = $this->con->prepare($sql);
        $stmt->bind_param("is", $hq_id, $from_date);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res && $row = $res->fetch_assoc()) {
            $inc = (float)$row['total_inc'];
            $dec = (float)$row['total_dec'];
            
            // For a wallet, Increase (Earned) - Decrease (Settled) = Current Balance
            return $inc - $dec; 
        }
        
        return 0;
    }
}
?>