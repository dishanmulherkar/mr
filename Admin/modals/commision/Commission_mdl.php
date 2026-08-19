<?php
class Commission_mdl {
    private $con;

    public function __construct() {
        global $con;
        $this->con = $con;
    }

    public function getStates()
    {
        return mysqli_query(
            $this->con,
            "SELECT * FROM state WHERE state_status = 1 ORDER BY state_name"
        );
    }

    public function getAdminMrBills($hqId, $month = '') {
        $hqId = (int)$hqId;
        
        $stmt = $this->con->prepare("SELECT hq_id, commission_rate FROM mr_users WHERE hq_id = ? AND status = '1'");
        $stmt->bind_param("i", $hqId);
        $stmt->execute();
        $mr_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$mr_data) return []; 
        
        $hq_id = $mr_data['hq_id'];
        $rate = (float)$mr_data['commission_rate'];

        $month_sql = "";
        if (!empty($month)) {
            $safe_month = mysqli_real_escape_string($this->con, $month);
            $month_sql = " AND DATE_FORMAT(si.created_at, '%Y-%m') = '$safe_month' ";
        }

        $query = "
            SELECT 
                si.inward_id,
                si.inward_no,
                DATE(si.created_at) as bill_date,
                s.stockist_name,
                si.sub_total AS taxable_amount,
                si.grand_total,
                si.paid_amt,
                UPPER(si.pay_status) AS pay_status,
                $rate AS commission_percent,
                ROUND((si.sub_total * ($rate / 100)), 2) AS commission_amount
            FROM stock_inward si
            INNER JOIN stockists s ON si.stockist_id = s.stockist_id
            WHERE s.hq_id = $hq_id 
            AND si.mrc = 0 
            $month_sql
            ORDER BY si.created_at ASC
        ";

        $result = $this->con->query($query);
        $bills = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $bills[] = $row;
            }
        }
        return $bills;
    }

    // ==========================================================
    // FIX APPLIED HERE: parameter is now $hq_id directly
    // ==========================================================
    public function claimMrCommission($hq_id, $bill_ids_json, $adjustments_json, $final_payout) 
    {
        try {
            $hq_id = (int)$hq_id; // Use HQ ID directly!
            
            $bill_ids = json_decode($bill_ids_json, true);
            $adjustments = json_decode($adjustments_json, true);

            if (empty($bill_ids) || !is_array($bill_ids)) {
                throw new Exception("No bills selected.");
            }

            $clean_ids = array_map('intval', $bill_ids);
            $id_string = implode(',', $clean_ids);

            $this->con->begin_transaction();

            // 1. Insert the Master Payout Record
            $stmt_payout = $this->con->prepare("INSERT INTO commission_payouts (hq_id, total_payout) VALUES (?, ?)");
            $stmt_payout->bind_param("id", $hq_id, $final_payout);
            
            if (!$stmt_payout->execute()) {
                throw new Exception("Failed to create payout record.");
            }
            
            $payout_id = $this->con->insert_id;
            $stmt_payout->close();

            // 2. Update stock_inward 
            $update_query = "
                UPDATE stock_inward si
                INNER JOIN stockists s ON si.stockist_id = s.stockist_id
                SET si.mrc = 1, si.commission_payout_id = $payout_id
                WHERE si.inward_id IN ($id_string)
                AND s.hq_id = $hq_id
                AND si.mrc = 0
            ";

            if (!$this->con->query($update_query)) {
                throw new Exception("Database update failed for selected bills.");
            }

            // 3. Insert Multiple Adjustments
            if (!empty($adjustments) && is_array($adjustments)) {
                $stmt_adj = $this->con->prepare("INSERT INTO commission_adjustments (payout_id, description, adj_type, amount) VALUES (?, ?, ?, ?)");
                
                foreach ($adjustments as $adj) {
                    $desc = $adj['description'];
                    $type = $adj['type']; 
                    $amt  = (float)$adj['amount'];
                    
                    $stmt_adj->bind_param("issd", $payout_id, $desc, $type, $amt);
                    
                    if (!$stmt_adj->execute()) {
                        throw new Exception("Failed to save adjustments.");
                    }
                }
                $stmt_adj->close();
            }

            $this->con->commit();

            return ['success' => true, 'msg' => 'Commission and adjustments claimed successfully.'];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    // ==========================================================
    // FIX APPLIED HERE: Re-applied the Collation Fix (YEAR & MONTH)
    // ==========================================================
    public function getMrCommissionHistory($hq_id, $month) {
        $sql = "
            SELECT 
                cp.payout_id, 
                cp.total_payout, 
                DATE_FORMAT(cp.created_at, '%d %b %Y, %h:%i %p') AS date_paid,
                (SELECT hq_name FROM headquarter WHERE hq_id = cp.hq_id LIMIT 1) AS hq_name 
            FROM commission_payouts cp
            WHERE cp.hq_id = ?
        ";
        
        $params = [$hq_id];
        $types = "i";

        if (!empty($month)) {
            $parts = explode('-', $month); // Split YYYY-MM
            if (count($parts) === 2) {
                $sql .= " AND YEAR(cp.created_at) = ? AND MONTH(cp.created_at) = ?";
                $params[] = (int)$parts[0];
                $params[] = (int)$parts[1];
                $types .= "ii";
            }
        }
        
        $sql .= " ORDER BY cp.created_at DESC";

        $stmt = $this->con->prepare($sql);
        $stmt->bind_param($types, ...$params); // Spread operator to bind dynamically
        $stmt->execute();
        $res = $stmt->get_result();
        
        $data = [];
        while($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
        
        $stmt->close();
        return $data;
    }

    public function getPayoutDetails($payout_id) {
        $details = ['bills' => [], 'adjustments' => []];

        $sql1 = "
            SELECT si.inward_no, si.inward_date, s.stockist_name, si.sub_total, si.commission_amount 
            FROM stock_inward si 
            LEFT JOIN stockists s ON si.stockist_id = s.stockist_id 
            WHERE si.commission_payout_id = ?
        ";
        $stmt1 = $this->con->prepare($sql1);
        $stmt1->bind_param("i", $payout_id);
        $stmt1->execute();
        $res1 = $stmt1->get_result();
        
        while($row = $res1->fetch_assoc()) {
            $details['bills'][] = $row;
        }
        $stmt1->close();

        $sql2 = "
            SELECT description, adj_type, amount 
            FROM commission_adjustments 
            WHERE payout_id = ?
        ";
        $stmt2 = $this->con->prepare($sql2);
        $stmt2->bind_param("i", $payout_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        
        while($row = $res2->fetch_assoc()) {
            $details['adjustments'][] = $row;
        }
        $stmt2->close();

        return $details;
    }

    public function getEditData($payout_id) {
        $data = ['bills' => [], 'adjustments' => [], 'hq_id' => 0];

        $stmt_hq = $this->con->prepare("SELECT hq_id FROM commission_payouts WHERE payout_id = ?");
        $stmt_hq->bind_param("i", $payout_id);
        $stmt_hq->execute();
        $hq_result = $stmt_hq->get_result()->fetch_assoc();
        $stmt_hq->close();

        if (!$hq_result) {
            return false; 
        }
        
        $hq_id = (int)$hq_result['hq_id'];
        $data['hq_id'] = $hq_id;

        $stmt_mr = $this->con->prepare("SELECT commission_rate FROM mr_users WHERE hq_id = ? AND status = '1'");
        $stmt_mr->bind_param("i", $hq_id);
        $stmt_mr->execute();
        $mr_data = $stmt_mr->get_result()->fetch_assoc();
        $stmt_mr->close();
        
        $rate = $mr_data ? (float)$mr_data['commission_rate'] : 0;

        $sql1 = "
            SELECT 
                si.inward_id,
                si.inward_no,
                DATE(si.created_at) as bill_date,
                s.stockist_name,
                si.sub_total AS taxable_amount,
                UPPER(si.pay_status) AS pay_status,
                $rate AS commission_percent,
                ROUND((si.sub_total * ($rate / 100)), 2) AS commission_amount,
                si.commission_payout_id
            FROM stock_inward si
            INNER JOIN stockists s ON si.stockist_id = s.stockist_id
            WHERE (s.hq_id = ? AND si.mrc = 0) 
               OR (si.commission_payout_id = ?)
            ORDER BY si.created_at DESC
        ";
        
        $stmt1 = $this->con->prepare($sql1);
        $stmt1->bind_param("ii", $hq_id, $payout_id);
        $stmt1->execute();
        $res1 = $stmt1->get_result();
        
        while ($row = $res1->fetch_assoc()) {
            $data['bills'][] = $row;
        }
        $stmt1->close();

        $sql2 = "SELECT description, adj_type, amount FROM commission_adjustments WHERE payout_id = ?";
        $stmt2 = $this->con->prepare($sql2);
        $stmt2->bind_param("i", $payout_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        
        while ($row = $res2->fetch_assoc()) {
            $data['adjustments'][] = $row;
        }
        $stmt2->close();

        return $data;
    }

    public function updateMrCommission($payout_id, $hq_id, $bill_ids_json, $adjustments_json, $final_payout) {
        try {
            $bill_ids = json_decode($bill_ids_json, true);
            $adjustments = json_decode($adjustments_json, true);

            if (empty($bill_ids) || !is_array($bill_ids)) {
                throw new Exception("You must select at least one bill for this payout.");
            }

            $clean_ids = array_map('intval', $bill_ids);
            $id_string = implode(',', $clean_ids);

            $this->con->begin_transaction();

            $reset_bills = "UPDATE stock_inward SET mrc = 0, commission_payout_id = NULL WHERE commission_payout_id = ?";
            $stmt_reset = $this->con->prepare($reset_bills);
            $stmt_reset->bind_param("i", $payout_id);
            $stmt_reset->execute();
            $stmt_reset->close();

            $delete_adj = "DELETE FROM commission_adjustments WHERE payout_id = ?";
            $stmt_del = $this->con->prepare($delete_adj);
            $stmt_del->bind_param("i", $payout_id);
            $stmt_del->execute();
            $stmt_del->close();

            $update_payout = "UPDATE commission_payouts SET total_payout = ? WHERE payout_id = ?";
            $stmt_payout = $this->con->prepare($update_payout);
            $stmt_payout->bind_param("di", $final_payout, $payout_id);
            if (!$stmt_payout->execute()) {
                throw new Exception("Failed to update master payout record.");
            }
            $stmt_payout->close();

            $update_query = "
                UPDATE stock_inward si
                INNER JOIN stockists s ON si.stockist_id = s.stockist_id
                SET si.mrc = 1, si.commission_payout_id = $payout_id
                WHERE si.inward_id IN ($id_string)
                AND s.hq_id = $hq_id
            ";
            if (!$this->con->query($update_query)) {
                throw new Exception("Failed to link new bills to this payout.");
            }

            if (!empty($adjustments) && is_array($adjustments)) {
                $stmt_adj = $this->con->prepare("INSERT INTO commission_adjustments (payout_id, description, adj_type, amount) VALUES (?, ?, ?, ?)");
                foreach ($adjustments as $adj) {
                    $desc = $adj['description'];
                    $type = $adj['type']; 
                    $amt  = (float)$adj['amount'];
                    
                    $stmt_adj->bind_param("issd", $payout_id, $desc, $type, $amt);
                    if (!$stmt_adj->execute()) {
                        throw new Exception("Failed to save new adjustments.");
                    }
                }
                $stmt_adj->close();
            }

            $this->con->commit();

            return ['success' => true, 'msg' => 'Payout updated successfully.'];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }
}
?>