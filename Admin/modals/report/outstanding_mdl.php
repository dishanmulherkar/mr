<?php

class outstanding_mdl
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }
   

    public function getSuperStockist()
    {
         if ($_SESSION['admin_role'] == 'Super Admin') {
            return mysqli_query($this->con, "
                SELECT ss.super_stockist_id, ss.ss_name, s.state_name
                FROM super_stockist ss 
                INNER JOIN state s ON ss.state = s.state_id
                ORDER BY ss_name ASC
            ");
        }
        
        $admin_id = (int)$_SESSION['admin_id'];

        return mysqli_query($this->con, "
            SELECT ss.super_stockist_id, ss.ss_name, s.state_name
            FROM super_stockist ss 
            INNER JOIN state s ON ss.state = s.state_id
            INNER JOIN admins a ON ss.super_stockist_id = a.stockist_id
            WHERE a.admin_id = '$admin_id'
            ORDER BY ss_name ASC
        ");
    }

  // Opening balance calculation including advance credits prior to $from_date
public function getOpeningBalance($stockist_id, $from_date)
{
    $stockist_id = (int)$stockist_id;
    $from_date   = mysqli_real_escape_string($this->con, $from_date);

    $sql = "SELECT 
                -- All Debits before $from_date (plus static opening_balance debit)
                ROUND(COALESCE(SUM(CASE 
                    WHEN transaction_type = 'opening_balance' AND (balance_action = 'increase' OR ledger_type IN ('debt', 'debit')) 
                        THEN amount
                    WHEN transaction_type = 'bill_added' 
                         AND DATE(created_at) < '$from_date'
                        THEN amount
                    WHEN notes LIKE '%CD Reversed%' 
                         AND DATE(created_at) < '$from_date'
                        THEN amount
                    ELSE 0 
                END), 0), 2) AS total_inc,

                -- All Credits before $from_date (plus static opening_balance credit)
                ROUND(COALESCE(SUM(CASE 
                    WHEN transaction_type = 'opening_balance' AND (balance_action = 'decrease' OR ledger_type = 'credit') 
                        THEN amount
                    WHEN (LOWER(notes) LIKE '%cd%' OR LOWER(notes) LIKE '%cash discount%')
                         AND notes NOT LIKE '%CD Reversed%'
                         AND DATE(created_at) < '$from_date'
                        THEN ROUND(amount)
                    WHEN transaction_type IN ('payment_made', 'mrc_settlement', 'drc_settlement', 'asm_settlement', 'settled_to_bill')
                         AND notes NOT LIKE '%CD Reversed%'
                         AND DATE(created_at) < '$from_date'
                        THEN amount
                    WHEN ledger_type = 'credit' AND transaction_type != 'bill_added'
                         AND DATE(created_at) < '$from_date'
                        THEN amount
                    ELSE 0 
                END), 0), 2) AS total_dec

            FROM payment_ledgers 
            WHERE stockist_id = '$stockist_id' 
              AND ledger_type IN ('debt', 'credit')
              AND transaction_type IN (
                  'opening_balance', 
                  'bill_added', 
                  'payment_made', 
                  'mrc_settlement', 
                  'drc_settlement', 
                  'asm_settlement', 
                  'settled_to_bill'
              )
              -- Include opening_balance record regardless of its insertion timestamp,
              -- and include all operational records strictly prior to $from_date
              AND (
                  transaction_type = 'opening_balance'
                  OR DATE(created_at) < '$from_date'
              )";

    $res = mysqli_query($this->con, $sql);
    if ($res && $row = mysqli_fetch_assoc($res)) {
        $inc = (float)$row['total_inc'];
        $dec = (float)$row['total_dec'];
        
        // Positive = Due/Receivable (Dr), Negative = Advance (Cr)
        return round($inc - $dec, 2);
    }
    return 0.00;
}
public function getSuperStockistOutstanding($super_stockist_id, $as_on_date = null, $from_date = null)
{
    $super_stockist_id = (int)$super_stockist_id;
    if ($super_stockist_id <= 0) {
        return ['stockists' => [], 'total_due' => 0.0, 'total_advance' => 0.0, 'net_outstanding' => 0.0];
    }

    // Accept only valid Y-m-d dates (safe to embed after this check)
    $valid = function ($v) {
        $d = DateTime::createFromFormat('Y-m-d', (string)$v);
        return ($d && $d->format('Y-m-d') === $v) ? $v : null;
    };
    $as_on_date = $valid($as_on_date) ?? date('Y-m-d');
    $from_date  = $valid($from_date);

    // Rows before the From Date follow the opening-balance rules
    $pre = $from_date
        ? "(pl.transaction_type <> 'opening_balance' AND DATE(pl.created_at) < '{$from_date}')"
        : "(1 = 0)";

    $sql = "
        SELECT
            s.stockist_id,
            s.stockist_name,
            h.headquarter_id,
            h.hq_name,

            ROUND(COALESCE(SUM(
                CASE
                    WHEN {$pre} THEN
                        CASE
                            WHEN pl.balance_action = 'increase' THEN pl.amount
                            WHEN pl.balance_action IS NULL AND pl.transaction_type = 'bill_added' THEN pl.amount
                            ELSE 0
                        END
                    WHEN pl.transaction_type = 'opening_balance'
                         AND (pl.balance_action = 'increase' OR pl.ledger_type IN ('debt', 'debit'))
                        THEN pl.amount
                    WHEN pl.transaction_type = 'bill_added'
                        THEN pl.amount
                    WHEN COALESCE(pl.notes, '') LIKE '%CD Reversed%'
                        THEN pl.amount
                    ELSE 0
                END
            ), 0), 2) AS total_debit,

            ROUND(COALESCE(SUM(
                CASE
                    WHEN {$pre} THEN
                        CASE
                            WHEN pl.balance_action = 'decrease' THEN pl.amount
                            WHEN pl.balance_action IS NULL
                                 AND pl.transaction_type IN ('payment_made','mrc_settlement','drc_settlement','asm_settlement','settled_to_bill')
                                THEN pl.amount
                            ELSE 0
                        END
                    WHEN pl.transaction_type = 'opening_balance'
                         AND (pl.balance_action = 'decrease' OR pl.ledger_type = 'credit')
                        THEN pl.amount
                    WHEN (LOWER(COALESCE(pl.notes, '')) LIKE '%cd%' OR LOWER(COALESCE(pl.notes, '')) LIKE '%cash discount%')
                         AND COALESCE(pl.notes, '') NOT LIKE '%CD Reversed%'
                         AND pl.transaction_type != 'bill_added'
                        THEN ROUND(pl.amount)
                    WHEN pl.transaction_type IN ('payment_made','mrc_settlement','drc_settlement','asm_settlement','settled_to_bill')
                         AND COALESCE(pl.notes, '') NOT LIKE '%CD Reversed%'
                        THEN pl.amount
                    WHEN pl.ledger_type = 'credit' AND pl.transaction_type != 'bill_added'
                        THEN pl.amount
                    ELSE 0
                END
            ), 0), 2) AS total_credit

        FROM stockists s
        INNER JOIN headquarter h
                ON h.headquarter_id = s.hq_id
        LEFT JOIN payment_ledgers pl
               ON pl.stockist_id = s.stockist_id
              AND pl.ledger_type IN ('debt', 'credit')
              AND pl.transaction_type IN (
                    'opening_balance', 'bill_added', 'payment_made',
                    'mrc_settlement', 'drc_settlement', 'asm_settlement', 'settled_to_bill'
              )
              AND (
                    pl.transaction_type = 'opening_balance'
                    OR DATE(pl.created_at) <= ?
              )
        WHERE h.super_stockist_id = ?
        GROUP BY s.stockist_id, s.stockist_name, h.headquarter_id, h.hq_name
    ";

    $stmt = $this->con->prepare($sql);
    if (!$stmt) {
        return ['error' => 'Prepare failed: ' . $this->con->error];
    }

    $stmt->bind_param('si', $as_on_date, $super_stockist_id);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        return ['error' => 'Execute failed: ' . $err];
    }

    $result        = $stmt->get_result();
    $rows          = [];
    $total_due     = 0.0;
    $total_advance = 0.0;

    while ($row = $result->fetch_assoc()) {
        $balance = round((float)$row['total_debit'] - (float)$row['total_credit'], 2);

        $row['closing_balance'] = $balance;
        $row['due_amount']      = $balance > 0 ? $balance : 0.0;
        $row['advance_amount']  = $balance < 0 ? abs($balance) : 0.0;
        $row['type']            = $balance > 0 ? 'Due' : ($balance < 0 ? 'Advance' : 'Clear');

        $total_due     += $row['due_amount'];
        $total_advance += $row['advance_amount'];

        $rows[] = $row;
    }

    $stmt->close();

    usort($rows, function ($a, $b) {
        return $b['closing_balance'] <=> $a['closing_balance'];
    });

    return [
        'stockists'       => $rows,
        'total_due'       => round($total_due, 2),
        'total_advance'   => round($total_advance, 2),
        'net_outstanding' => round($total_due - $total_advance, 2),
    ];
}

}