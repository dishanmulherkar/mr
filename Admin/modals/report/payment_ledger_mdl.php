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
    // MR Commission (MRC) Report
    // ==========================================
public function getReportmrc($hq_id, $from_date, $to_date)
    {
        // 1. Joins 'stockists' to get stockist name
        // 2. Joins 'payment_details' & 'mr_users' to trace HQ for Bank Payouts (stockist_id = 0)
        // 3. Joins 'stock_inward' to fetch the Bill No if settled to a bill
        // 4. Runs a subquery to calculate the live 'debt' outstanding for that stockist
        
        $sql = "SELECT 
                    p.*, 
                    COALESCE(s.stockist_name, 'Bank Payout / General') AS stockist_name,
                    si.inward_no AS settled_bill_no,
                    
                    (
                        SELECT 
                            ROUND(COALESCE(SUM(CASE WHEN LOWER(balance_action) IN ('increase', 'increase_debt') OR LOWER(transaction_type) IN ('bill_added', 'opening_balance', 'debit_note') THEN amount ELSE 0 END), 0)) - 
                            ROUND(COALESCE(SUM(CASE WHEN LOWER(balance_action) IN ('decrease', 'decrease_debt') OR LOWER(transaction_type) IN ('payment_made', 'credit_note', 'discount', 'payment', 'mrc_settlement', 'drc_settlement', 'settled_to_bill') THEN amount ELSE 0 END), 0))
                        FROM payment_ledgers 
                        -- Ensure we only calculate outstanding debt if it's a real stockist (> 0)
                        WHERE stockist_id = p.stockist_id AND stockist_id > 0 AND ledger_type = 'debt'
                    ) AS stockist_outstanding

                FROM payment_ledgers p
                
                -- Changed to LEFT JOIN to prevent dropping records with stockist_id = 0
                LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
                
                -- Trace the HQ for Bank Payouts by joining payment_details and mr_users
                LEFT JOIN payment_details pd ON p.reference_id = pd.id AND p.stockist_id = 0
                LEFT JOIN mr_users m ON pd.mr_id = m.m_id
                
                -- Attempt to get the invoice number if reference_id matches an inward_id
                LEFT JOIN stock_inward si ON p.reference_id = si.inward_id AND p.transaction_type IN ('mrc_settlement', 'settled_to_bill')
                
                -- Match HQ from either the Stockist OR the MR User
                WHERE COALESCE(s.hq_id, m.hq_id) = '$hq_id' 
                AND p.ledger_type = 'mrc_wallet'
                AND DATE(p.created_at) >= '$from_date' 
                AND DATE(p.created_at) <= '$to_date'
                ORDER BY p.created_at ASC, p.id ASC";
                
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
                
                -- Changed to LEFT JOIN to prevent dropping records with stockist_id = 0
                LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
                
                -- Trace the HQ for Bank Payouts by joining payment_details and mr_users
                LEFT JOIN payment_details pd ON p.reference_id = pd.id AND p.stockist_id = 0
                LEFT JOIN mr_users m ON pd.mr_id = m.m_id
                
                -- Match HQ from either the Stockist OR the MR User
                WHERE COALESCE(s.hq_id, m.hq_id) = '$hq_id' 
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
    // DR Commission (DRC) Report
    // ==========================================
    public function getReportdrc($hq_id, $from_date, $to_date)
    {
        // 1. Joins 'stockists' to get stockist name (LEFT JOIN)
        // 2. Joins 'payment_details' & 'mr_users' to trace HQ for Bank Payouts (stockist_id = 0)
        // 3. Joins 'stock_inward' to fetch the Bill No if settled to a bill
        // 4. Runs a subquery to calculate the live 'debt' outstanding for that stockist
        
        $sql = "SELECT 
                    p.*, 
                    COALESCE(s.stockist_name, 'Bank Payout / General') AS stockist_name,
                    si.inward_no AS settled_bill_no,
                    
                    (
                        SELECT 
                            ROUND(COALESCE(SUM(CASE WHEN LOWER(balance_action) IN ('increase', 'increase_debt') OR LOWER(transaction_type) IN ('bill_added', 'opening_balance', 'debit_note') THEN amount ELSE 0 END), 0)) - 
                            ROUND(COALESCE(SUM(CASE WHEN LOWER(balance_action) IN ('decrease', 'decrease_debt') OR LOWER(transaction_type) IN ('payment_made', 'credit_note', 'discount', 'payment', 'mrc_settlement', 'drc_settlement', 'settled_to_bill') THEN amount ELSE 0 END), 0))
                        FROM payment_ledgers 
                        -- Ensure we only calculate outstanding debt if it's a real stockist (> 0)
                        WHERE stockist_id = p.stockist_id AND stockist_id > 0 AND ledger_type = 'debt'
                    ) AS stockist_outstanding

                FROM payment_ledgers p
                
                -- Changed to LEFT JOIN to prevent dropping records with stockist_id = 0
                LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
                
                -- Trace the HQ for Bank Payouts by joining payment_details and mr_users
                LEFT JOIN payment_details pd ON p.reference_id = pd.id AND p.stockist_id = 0
                LEFT JOIN mr_users m ON pd.mr_id = m.m_id
                
                -- Attempt to get the invoice number if reference_id matches an inward_id
                LEFT JOIN stock_inward si ON p.reference_id = si.inward_id AND p.transaction_type IN ('drc_settlement', 'settled_to_bill')
                
                -- Match HQ from either the Stockist OR the MR User
                WHERE COALESCE(s.hq_id, m.hq_id) = '$hq_id' 
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
        // Safely uses 'balance_action' to determine adding or subtracting from the wallet
        $sql = "SELECT 
                    SUM(CASE WHEN p.balance_action = 'increase' THEN p.amount ELSE 0 END) as total_inc,
                    SUM(CASE WHEN p.balance_action = 'decrease' THEN p.amount ELSE 0 END) as total_dec
                FROM payment_ledgers p
                
                -- Changed to LEFT JOIN to prevent dropping records with stockist_id = 0
                LEFT JOIN stockists s ON p.stockist_id = s.stockist_id
                
                -- Trace the HQ for Bank Payouts by joining payment_details and mr_users
                LEFT JOIN payment_details pd ON p.reference_id = pd.id AND p.stockist_id = 0
                LEFT JOIN mr_users m ON pd.mr_id = m.m_id
                
                -- Match HQ from either the Stockist OR the MR User
                WHERE COALESCE(s.hq_id, m.hq_id) = '$hq_id' 
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