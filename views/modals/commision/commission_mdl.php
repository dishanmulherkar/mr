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
    $mr_id = (int)$mr_id;

    // 1. Query directly from commission_payouts (no HQ join)
    $sql = "
        SELECT DISTINCT
            cp.payout_id, 
            cp.total_payout, 
            cp.status, 
            DATE_FORMAT(cp.created_at, '%d %b %Y') AS date_paid,
            cp.created_at
        FROM commission_payouts cp
    ";
    
    // 2. Stockist filter join
    if ($stockist_id > 0) {
        $sql .= " INNER JOIN stock_inward si ON si.commission_payout_id = cp.payout_id ";
    }

    // 3. Strict MR-level security filter
    $sql .= " WHERE cp.commission_type = 'MRC' AND cp.mr_id = ?";
    
    $params = [$mr_id];
    $types  = "i";

    // 4. Optional filters
    if ($stockist_id > 0) {
        $sql .= " AND si.stockist_id = ?";
        $params[] = (int)$stockist_id;
        $types   .= "i";
    }

    if (!empty($from_date)) {
        $sql .= " AND DATE(cp.created_at) = ?";
        $params[] = $from_date;
        $types   .= "s";
    }
    
    $sql .= " ORDER BY cp.created_at DESC";

    $stmt = $this->con->prepare($sql);
    
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
    // Fetch Detailed View Data for a specific Payout (MR/DRC)
    // ========================================================
    public function getCommissionViewData($payout_id, $mr_id) {
        $payout_id = (int)$payout_id;
        $mr_id = (int)$mr_id;
        
        // 1. Get Master Payout Data (Now includes the locked-in commission_rate)
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

        // --- DYNAMICALLY CHOOSE THE COLUMN BASED ON PAYOUT TYPE ---
        $is_drc = (isset($master['commission_type']) && $master['commission_type'] === 'DRC');
        $payout_column = $is_drc ? 'commission_drc_payout_id' : 'commission_payout_id';
        
        // Extract the locked-in historical rate
        $historical_rate = isset($master['commission_rate']) ? (float)$master['commission_rate'] : 0.00;
        
        // Legacy fallback: If it is an old DRC record without a saved rate, default to 20%
        if ($is_drc && $historical_rate == 0.00) {
            $historical_rate = 20.00;
        }

        // 2. Get Linked Bills & calculate PTS dynamically using the HISTORICAL rate
        // (Removed the INNER JOIN to mr_users here since we don't need the live rate anymore)
        $sql = "
            SELECT si.inward_no, DATE_FORMAT(si.created_at, '%d %b %Y') as bill_date, 
                   s.stockist_name, si.sub_total as taxable_amount,
                   ? as pts,
                   ROUND((si.sub_total * (? / 100)), 2) as commission_amount
            FROM stock_inward si
            INNER JOIN stockists s ON si.stockist_id = s.stockist_id
            WHERE si.{$payout_column} = ?
        ";
        
        $stmt_bills = $this->con->prepare($sql);
        
        // Bind the historical rate twice (once for display, once for math), then the ID
        $stmt_bills->bind_param("ddi", $historical_rate, $historical_rate, $payout_id);
        
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
    $mr_id = (int)$mr_id;

    $sql = "
        SELECT DISTINCT
            cp.payout_id, 
            cp.total_payout, 
            cp.status, 
            DATE_FORMAT(cp.created_at, '%d %b %Y') AS date_paid,
            cp.created_at
        FROM commission_payouts cp
        INNER JOIN mr_users m ON m.m_id = cp.mr_id
    ";
    
    if ($stockist_id > 0) {
        $sql .= " INNER JOIN stock_inward si ON si.commission_payout_id = cp.payout_id ";
    }

    $sql .= " WHERE cp.commission_type = 'DRC' AND m.m_id = ?";
    
    $params = [$mr_id];
    $types  = "i";

    if ($stockist_id > 0) {
        $sql .= " AND si.stockist_id = ?";
        $params[] = (int)$stockist_id;
        $types   .= "i";
    }

    if (!empty($from_date)) {
        $sql .= " AND DATE(cp.created_at) = ?";
        $params[] = $from_date;
        $types   .= "s";
    }
    
    $sql .= " ORDER BY cp.created_at DESC";

    $stmt = $this->con->prepare($sql);
    
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

    $query = "
        SELECT 
            SUM(CASE 
                WHEN pl.ledger_type = 'mrc_wallet' AND pl.balance_action = 'increase' THEN pl.amount 
                WHEN pl.ledger_type = 'mrc_wallet' AND pl.balance_action != 'increase' THEN -pl.amount 
                ELSE 0 
            END) AS mrc_total,
            
            SUM(CASE 
                WHEN pl.ledger_type = 'drc_wallet' AND pl.balance_action = 'increase' THEN pl.amount 
                WHEN pl.ledger_type = 'drc_wallet' AND pl.balance_action != 'increase' THEN -pl.amount 
                ELSE 0 
            END) AS drc_total
        FROM payment_ledgers pl
        WHERE pl.user_id = ?
          AND pl.ledger_type IN ('mrc_wallet', 'drc_wallet')
    ";

    $stmt = $this->con->prepare($query);
    $stmt->bind_param("i", $mr_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return [
        'mrc_balance' => $res && $res['mrc_total'] !== null ? round((float)$res['mrc_total'], 2) : 0.00,
        'drc_balance' => $res && $res['drc_total'] !== null ? round((float)$res['drc_total'], 2) : 0.00
    ];
}
}