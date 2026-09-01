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
        $sql = "SELECT pl.*, si.inward_no, pd.id as pay_id ,s.stockist_name,pd.payment_method ,b.bank_name
                FROM payment_ledgers pl 
                LEFT JOIN stock_inward si ON si.inward_id = pl.reference_id 
                LEFT JOIN payment_details pd ON pd.id = pl.reference_id 
                LEFT JOIN stockists s ON pl.stockist_id = s.stockist_id
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
        $sql = "SELECT 
                    MIN(p.id) AS id,
                    MIN(p.created_at) AS created_at,
                    p.reference_id,
                    p.transaction_type,
                    p.balance_action,
                    MAX(p.notes) AS notes,
                    MAX(si.inward_no) AS settled_bill_no,
                    SUM(p.amount) AS amount,
                    -- Provide a fallback name if stockist_id is 0
                    IFNULL(MAX(s.stockist_name), 'Bank / General') AS stockist_name
                FROM payment_ledgers p
                
                -- Changed to LEFT JOIN so stockist_id = 0 doesn't get dropped
                LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
                LEFT JOIN stock_inward si ON p.reference_id = si.inward_id 
                
                -- Allow the specific HQ OR rows where no stockist is attached (Bank Payments)
                WHERE (s.hq_id = '$hq_id' OR p.stockist_id = 0)
                AND p.ledger_type = 'mrc_wallet'
                AND DATE(p.created_at) >= '$from_date' 
                AND DATE(p.created_at) <= '$to_date'
                
                GROUP BY 
                    DATE(p.created_at), 
                    p.reference_id, 
                    p.transaction_type, 
                    p.balance_action
                    
                ORDER BY created_at ASC, id ASC";
                
        return mysqli_query($this->con, $sql);
    }

    // ==========================================
    // Opening balance calculation for MRC Wallet
    // ==========================================
    public function getOpeningBalancemrc($hq_id, $from_date)
    {
        // Safely uses 'balance_action' to determine adding or subtracting from the wallet
        $sql = "SELECT 
                    SUM(CASE WHEN p.balance_action = 'increase' THEN p.amount ELSE 0 END) as total_inc,
                    SUM(CASE WHEN p.balance_action = 'decrease' THEN p.amount ELSE 0 END) as total_dec
                FROM payment_ledgers p
                INNER JOIN stockists s ON p.stockist_id = s.stockist_id
                WHERE s.hq_id = '$hq_id' 
                AND p.ledger_type = 'mrc_wallet'
                AND DATE(p.created_at) < '$from_date'";

        $res = mysqli_query($this->con, $sql);
        
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $inc = (float)$row['total_inc'];
            $dec = (float)$row['total_dec'];
            
            // For a wallet, Increase (Earned) - Decrease (Settled) = Current Balance
            return $inc - $dec; 
        }
        
        return 0;
    }

      // ==========================================
    // MR Commission (DRC) Report (Grouped by Ref ID)
    // ==========================================
    public function getReportdrc($hq_id, $from_date, $to_date)
    {
        $sql = "SELECT 
                    MIN(p.id) AS id,
                    MIN(p.created_at) AS created_at,
                    p.reference_id,
                    p.transaction_type,
                    p.balance_action,
                    MAX(p.notes) AS notes,
                    MAX(si.inward_no) AS settled_bill_no,
                    SUM(p.amount) AS amount,
                    -- Provide a fallback name if stockist_id is 0
                    IFNULL(MAX(s.stockist_name), 'Bank / General') AS stockist_name
                FROM payment_ledgers p
                
                -- Changed to LEFT JOIN so stockist_id = 0 doesn't get dropped
                LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
                LEFT JOIN stock_inward si ON p.reference_id = si.inward_id 
                
                -- Allow the specific HQ OR rows where no stockist is attached (Bank Payments)
                WHERE (s.hq_id = '$hq_id' OR p.stockist_id = 0)
                AND p.ledger_type = 'drc_wallet'
                AND DATE(p.created_at) >= '$from_date' 
                AND DATE(p.created_at) <= '$to_date'
                
                GROUP BY 
                    DATE(p.created_at), 
                    p.reference_id, 
                    p.transaction_type, 
                    p.balance_action
                    
                ORDER BY created_at ASC, id ASC";
                
        return mysqli_query($this->con, $sql);
    }

    // ==========================================
    // Opening balance calculation for DRC Wallet
    // ==========================================
    public function getOpeningBalancedrc($hq_id, $from_date)
    {
        // Safely uses 'balance_action' to determine adding or subtracting from the wallet
        $sql = "SELECT 
                    SUM(CASE WHEN p.balance_action = 'increase' THEN p.amount ELSE 0 END) as total_inc,
                    SUM(CASE WHEN p.balance_action = 'decrease' THEN p.amount ELSE 0 END) as total_dec
                FROM payment_ledgers p
                INNER JOIN stockists s ON p.stockist_id = s.stockist_id
                WHERE s.hq_id = '$hq_id' 
                AND p.ledger_type = 'drc_wallet'
                AND DATE(p.created_at) < '$from_date'";

        $res = mysqli_query($this->con, $sql);
        
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $inc = (float)$row['total_inc'];
            $dec = (float)$row['total_dec'];
            
            // For a wallet, Increase (Earned) - Decrease (Settled) = Current Balance
            return $inc - $dec; 
        }
        
        return 0;
    }

   
}
?>