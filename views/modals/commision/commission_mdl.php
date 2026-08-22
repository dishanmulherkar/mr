<?php

class commission_mdl
{
    private $con;

    public function __construct()
    {
        global $con;
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

    // ========================================================
    // NEW: Fetch Commission Payouts for the Logged In MR
    // ========================================================
   public function getMrCommissionsList($mr_id, $stockist_id = 0, $from_date = '')
    {
        // 1. SELECT AND FROM
        $sql = "
            SELECT DISTINCT
                cp.payout_id, 
                cp.total_payout, 
                cp.status, 
                DATE_FORMAT(cp.created_at, '%d %b %Y') AS date_paid,
                cp.created_at
            FROM commission_payouts cp
            INNER JOIN mr_users m ON m.hq_id = cp.hq_id 
        ";
        
        // 2. ALL JOINS MUST HAPPEN BEFORE THE 'WHERE' CLAUSE
        if ($stockist_id > 0) {
            $sql .= " INNER JOIN stock_inward si ON si.commission_payout_id = cp.payout_id ";
        }

        // 3. START THE SINGLE 'WHERE' CLAUSE
        $sql .= " WHERE cp.commission_type = 'MR' AND m.m_id = ?";
        
        $params = [$mr_id];
        $types = "i";

        // 4. APPEND ADDITIONAL CONDITIONS WITH 'AND'
        if ($stockist_id > 0) {
            $sql .= " AND si.stockist_id = ?";
            $params[] = $stockist_id;
            $types .= "i";
        }

        if (!empty($from_date)) {
            $sql .= " AND DATE(cp.created_at) = ?";
            $params[] = $from_date;
            $types .= "s";
        }
        
        $sql .= " ORDER BY cp.created_at DESC";

        $stmt = $this->con->prepare($sql);
        
        // Dynamic binding
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        $stmt->close();
        return $data;
    }

   // ========================================================
    // Fetch Detailed View Data for a specific Payout
    // ========================================================
    public function getCommissionViewData($payout_id, $mr_id) {
        $payout_id = (int)$payout_id;
        $mr_id = (int)$mr_id;
        
        // 1. Get Master Payout Data
        $stmt = $this->con->prepare("
            SELECT cp.*, DATE_FORMAT(cp.created_at, '%d %b %Y, %h:%i %p') as payout_date
            FROM commission_payouts cp
            INNER JOIN mr_users m ON m.hq_id = cp.hq_id
            WHERE cp.payout_id = ? AND m.m_id = ?
        ");
        $stmt->bind_param("ii", $payout_id, $mr_id);
        $stmt->execute();
        $master = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$master) return false;

        $data = ['payout' => $master, 'bills' => [], 'adjustments' => []];

        // 2. Get Linked Bills & calculate PTS dynamically
        $stmt_bills = $this->con->prepare("
            SELECT si.inward_no, DATE_FORMAT(si.created_at, '%d %b %Y') as bill_date, 
                   s.stockist_name, si.sub_total as taxable_amount,
                   m.commission_rate as pts,
                   ROUND((si.sub_total * (m.commission_rate / 100)), 2) as commission_amount
            FROM stock_inward si
            INNER JOIN stockists s ON si.stockist_id = s.stockist_id
            INNER JOIN mr_users m ON m.hq_id = s.hq_id
            WHERE si.commission_payout_id = ?
        ");
        $stmt_bills->bind_param("i", $payout_id);
        $stmt_bills->execute();
        $res_bills = $stmt_bills->get_result();
        
        $bill_total = 0;
        while($row = $res_bills->fetch_assoc()) {
            $data['bills'][] = $row;
            $bill_total += (float)$row['commission_amount'];
        }
        $data['payout']['bill_total'] = $bill_total;
        $stmt_bills->close();

        // 3. Get Adjustments
        $stmt_adj = $this->con->prepare("
            SELECT description, adj_type, amount
            FROM commission_adjustments
            WHERE payout_id = ?
        ");
        $stmt_adj->bind_param("i", $payout_id);
        $stmt_adj->execute();
        $res_adj = $stmt_adj->get_result();
        
        $adj_total = 0;
        while($row = $res_adj->fetch_assoc()) {
            $data['adjustments'][] = $row;
            if ($row['adj_type'] === '+') {
                $adj_total += (float)$row['amount'];
            } else {
                $adj_total -= (float)$row['amount'];
            }
        }
        $data['payout']['adj_total'] = $adj_total;
        $stmt_adj->close();

        return $data;
    }


     // ========================================================
    // NEW: Fetch Commission Payouts for the Logged In MR
    // ========================================================
   public function getDrCommissionsList($mr_id, $stockist_id = 0, $from_date = '')
    {
        // 1. SELECT AND FROM (No WHERE clause here)
        $sql = "
            SELECT DISTINCT
                cp.payout_id, 
                cp.total_payout, 
                cp.status, 
                DATE_FORMAT(cp.created_at, '%d %b %Y') AS date_paid,
                cp.created_at
            FROM commission_payouts cp
            INNER JOIN mr_users m ON m.hq_id = cp.hq_id 
        ";
        
        // 2. ALL JOINS MUST HAPPEN BEFORE THE 'WHERE' CLAUSE
        if ($stockist_id > 0) {
            $sql .= " INNER JOIN stock_inward si ON si.commission_payout_id = cp.payout_id ";
        }

        // 3. START THE SINGLE 'WHERE' CLAUSE
        $sql .= " WHERE cp.commission_type = 'DRC' AND m.m_id = ?";
        
        $params = [$mr_id];
        $types = "i";

        // 4. APPEND ADDITIONAL CONDITIONS WITH 'AND'
        if ($stockist_id > 0) {
            $sql .= " AND si.stockist_id = ?";
            $params[] = $stockist_id;
            $types .= "i";
        }

        if (!empty($from_date)) {
            // Using DATE() to match only the specific day
            $sql .= " AND DATE(cp.created_at) = ?";
            $params[] = $from_date;
            $types .= "s";
        }
        
        $sql .= " ORDER BY cp.created_at DESC";

        $stmt = $this->con->prepare($sql);
        
        // Dynamic binding
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        $stmt->close();
        return $data;
    }

   // ========================================================
    // Fetch Live Wallet Balances (Using Trusted Query Format)
    // ========================================================
    public function getWalletBalances($mr_id)
    {
        $mr_id = (int)$mr_id;
        
        // 1. Get the HQ ID for this MR
        $stmt_hq = $this->con->prepare("SELECT hq_id FROM mr_users WHERE m_id = ?");
        $stmt_hq->bind_param("i", $mr_id);
        $stmt_hq->execute();
        $res_hq = $stmt_hq->get_result()->fetch_assoc();
        $stmt_hq->close();
        
        $hq_id = $res_hq ? (int)$res_hq['hq_id'] : 0;
        
        $mrc_balance = 0.00;
        $drc_balance = 0.00;
        
        if ($hq_id > 0) {
            // 2. Get MRC Balance using your exact query structure
            $stmt_mrc = $this->con->prepare("
                SELECT SUM(CASE WHEN pl.balance_action = 'increase' THEN pl.amount ELSE -pl.amount END) as total_balance
                FROM payment_ledgers pl
                INNER JOIN stockists s ON pl.stockist_id = s.stockist_id
                WHERE s.hq_id = ? AND pl.ledger_type = 'mrc_wallet'
            ");
            $stmt_mrc->bind_param("i", $hq_id);
            $stmt_mrc->execute();
            $res_mrc = $stmt_mrc->get_result()->fetch_assoc();
            $mrc_balance = $res_mrc['total_balance'] ? (float)$res_mrc['total_balance'] : 0.00;
            $stmt_mrc->close();
            
            // 3. Get DRC Balance using your exact query structure
            $stmt_drc = $this->con->prepare("
                SELECT SUM(CASE WHEN pl.balance_action = 'increase' THEN pl.amount ELSE -pl.amount END) as total_balance
                FROM payment_ledgers pl
                INNER JOIN stockists s ON pl.stockist_id = s.stockist_id
                WHERE s.hq_id = ? AND pl.ledger_type = 'drc_wallet'
            ");
            $stmt_drc->bind_param("i", $hq_id);
            $stmt_drc->execute();
            $res_drc = $stmt_drc->get_result()->fetch_assoc();
            $drc_balance = $res_drc['total_balance'] ? (float)$res_drc['total_balance'] : 0.00;
            $stmt_drc->close();
        }
        
        return [
            'mrc_balance' => $mrc_balance,
            'drc_balance' => $drc_balance
        ];
    }
}