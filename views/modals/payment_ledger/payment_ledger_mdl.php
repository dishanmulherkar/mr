<?php
class payment_ledger_mdl
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }



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

    public function getStockistName($stockist_id)
    {
        $sql = "SELECT stockist_name FROM stockists WHERE stockist_id = '$stockist_id'";
        $res = mysqli_query($this->con, $sql);
        return $res ? mysqli_fetch_assoc($res) : null;
    }

// Filtered to show bill_added, payment_made, and commission settlements FOR DEBT ONLY
    public function getReport($stockist_id, $from_date, $to_date)
    {
        $sql = "SELECT pl.*, si.inward_no, pd.id as pay_id, s.stockist_name, pd.payment_method, b.bank_name
                FROM payment_ledgers pl 
                LEFT JOIN stock_inward si ON si.inward_id = pl.reference_id 
                LEFT JOIN payment_details pd ON pd.id = pl.reference_id 
                LEFT JOIN stockists s ON pl.stockist_id = s.stockist_id
                LEFT JOIN banks b ON b.bank_id = pd.bank_id
                WHERE pl.stockist_id = ? 
                AND pl.ledger_type = 'debt'  /* <-- THE FIX IS HERE */
                
                -- Kept 'settled_to_bill' for legacy data, and ensured 'asm_settlement' is included
                AND pl.transaction_type IN ('bill_added', 'payment_made', 'mrc_settlement', 'drc_settlement', 'settled_to_bill', 'asm_settlement')
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
        // FIX: Using 'balance_action' instead of hardcoding transaction types! 
        // This guarantees that any 'decrease' (payment) or 'increase' (bill) is perfectly calculated.
        $sql = "SELECT 
                    SUM(CASE WHEN balance_action = 'increase' THEN amount ELSE 0 END) as total_inc,
                    SUM(CASE WHEN balance_action = 'decrease' THEN amount ELSE 0 END) as total_dec
                FROM payment_ledgers 
                WHERE stockist_id = ? 
                AND ledger_type = 'debt' 
                
                -- FIX: Added 'asm_settlement' here so it matches the getReport query exactly!
                AND transaction_type IN ('bill_added', 'payment_made', 'mrc_settlement', 'drc_settlement', 'settled_to_bill', 'asm_settlement')
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
            return $inc - $dec; // Positive = Debt Balance, Negative = Credit Balance
        }
        
        if (isset($stmt)) {
            $stmt->close();
        }
        
        return 0.00;
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
                    IFNULL(MAX(s.stockist_name), 'Bank / General') AS stockist_name
                    
                FROM payment_ledgers p
                
                -- Only join the stockist table
                LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
                
                -- FIX: Check stockist HQ, OR check if user_id matches legacy HQ ID or new MR ID
                WHERE (s.hq_id = ? OR p.user_id IN (?, ?))
                  AND p.ledger_type = 'mrc_wallet'
                  AND DATE(p.created_at) >= ? 
                  AND DATE(p.created_at) <= ?
                
                GROUP BY 
                    DATE(p.created_at), 
                    p.reference_id, 
                    p.transaction_type, 
                    p.balance_action
                    
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
        // Safely uses 'balance_action' to determine adding or subtracting from the wallet
        $sql = "SELECT 
                    SUM(CASE WHEN p.balance_action = 'increase' THEN p.amount ELSE 0 END) as total_inc,
                    SUM(CASE WHEN p.balance_action = 'decrease' THEN p.amount ELSE 0 END) as total_dec
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
    // MR Commission (DRC) Report (Grouped by Ref ID)
    // ==========================================
    public function getReportdrc($hq_id, $from_date, $to_date)
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
                    
                    (
                        SELECT GROUP_CONCAT(DISTINCT si.inward_no SEPARATOR ', ')
                        FROM payment_ledgers pd
                        INNER JOIN payment_allocations pa ON pd.id = pa.ledger_id
                        INNER JOIN stock_inward si ON pa.inward_id = si.inward_id
                        WHERE pd.reference_id = p.reference_id AND pd.ledger_type = 'debt'
                    ) AS settled_bill_no,
                    
                    SUM(p.amount) AS amount,
                    IFNULL(MAX(s.stockist_name), 'Bank / General') AS stockist_name
                    
                FROM payment_ledgers p
                
                LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
                
                -- FIX: Check HQ from stockist, OR check if user_id matches legacy HQ ID or new MR ID
                WHERE (s.hq_id = ? OR p.user_id IN (?, ?))
                  AND p.ledger_type = 'drc_wallet'
                  AND DATE(p.created_at) >= ? 
                  AND DATE(p.created_at) <= ?
                
                GROUP BY 
                    DATE(p.created_at), 
                    p.reference_id, 
                    p.transaction_type, 
                    p.balance_action
                    
                ORDER BY MIN(p.created_at) ASC, MIN(p.id) ASC";
                
        $stmt = $this->con->prepare($sql);
        // Bind 5 parameters: HQ ID (for stockist), HQ ID (for legacy bank), MR ID (for new bank), From Date, To Date
        $stmt->bind_param("iiiss", $hq_id, $hq_id, $mr_id, $from_date, $to_date);
        $stmt->execute();
        
        return $stmt->get_result();
    }

// ==========================================
    // Opening balance calculation for DRC Wallet
    // ==========================================
    public function getOpeningBalancedrc($hq_id, $from_date)
    {
        // 1. Fetch the corresponding MR ID (m_id) for this HQ
        $stmt_mr = $this->con->prepare("SELECT m_id FROM mr_users WHERE hq_id = ? LIMIT 1");
        $stmt_mr->bind_param("i", $hq_id);
        $stmt_mr->execute();
        $res_mr = $stmt_mr->get_result()->fetch_assoc();
        $stmt_mr->close();
        
        $mr_id = $res_mr ? (int)$res_mr['m_id'] : 0;

        // 2. Calculate Opening Balance
        $sql = "SELECT 
                    SUM(CASE WHEN p.balance_action = 'increase' THEN p.amount ELSE 0 END) as total_inc,
                    SUM(CASE WHEN p.balance_action = 'decrease' THEN p.amount ELSE 0 END) as total_dec
                FROM payment_ledgers p
                
                LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
                
                -- FIX: Check HQ from stockist, OR check if user_id matches legacy HQ ID or new MR ID
                WHERE (s.hq_id = ? OR p.user_id IN (?, ?))
                AND p.ledger_type = 'drc_wallet'
                AND DATE(p.created_at) < ?";

        $stmt = $this->con->prepare($sql);
        // Bind 4 parameters: HQ ID (for stockist), HQ ID (for legacy bank), MR ID (for new bank), From Date
        $stmt->bind_param("iiis", $hq_id, $hq_id, $mr_id, $from_date);
        $stmt->execute();
        
        $res = $stmt->get_result();
        
        if ($res && $row = $res->fetch_assoc()) {
            $inc = (float)$row['total_inc'];
            $dec = (float)$row['total_dec'];
            
            $stmt->close();
            return $inc - $dec; 
        }
        
        if (isset($stmt)) {
            $stmt->close();
        }
        
        return 0.00;
    }

   
}
?>