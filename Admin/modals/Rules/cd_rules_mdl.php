<?php
class CDRulesModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getAll()
    {
        // Join starts from super_stockist to get sequence data even if CD rules don't exist yet
        $sql = "SELECT s.super_stockist_id, s.ss_name as stockist_name, 
                       s.order_prefix, s.tally_start_no, s.fy_start_month, s.order_sequence,
                       r.cd_4_percent_days, r.cd_2_percent_days
                FROM super_stockist s 
                LEFT JOIN super_stockist_cd_rules r ON s.super_stockist_id = r.super_stockist_id
                WHERE s.ss_name IS NOT NULL";
                
        return mysqli_query($this->con, $sql);
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

    public function getByStockistId($id)
    {
        $sql = "SELECT s.super_stockist_id, s.order_prefix, s.tally_start_no, s.fy_start_month, s.order_sequence, 
                       r.cd_4_percent_days, r.cd_2_percent_days
                FROM super_stockist s 
                LEFT JOIN super_stockist_cd_rules r ON s.super_stockist_id = r.super_stockist_id 
                WHERE s.super_stockist_id = ?";
                
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }

    public function saveRule($data)
    {
        mysqli_begin_transaction($this->con);

        try {
            // 1. Upsert CD Rules
            $sql1 = "INSERT INTO super_stockist_cd_rules (super_stockist_id, cd_4_percent_days, cd_2_percent_days) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE 
                     cd_4_percent_days = VALUES(cd_4_percent_days), 
                     cd_2_percent_days = VALUES(cd_2_percent_days)";
                     
            $stmt1 = mysqli_prepare($this->con, $sql1);
            mysqli_stmt_bind_param($stmt1, "iii", $data['super_stockist_id'], $data['cd_4_percent_days'], $data['cd_2_percent_days']);
            mysqli_stmt_execute($stmt1);

            // 2. Update Billing Settings in super_stockist table
            $sql2 = "UPDATE super_stockist 
                     SET order_prefix = ?, 
                         tally_start_no = ?, 
                         fy_start_month = ?, 
                         order_sequence = ? 
                     WHERE super_stockist_id = ?";
                     
            $stmt2 = mysqli_prepare($this->con, $sql2);
            mysqli_stmt_bind_param($stmt2, "siiii", 
                $data['order_prefix'], 
                $data['tally_start_no'], 
                $data['fy_start_month'], 
                $data['order_sequence'], 
                $data['super_stockist_id']
            );
            mysqli_stmt_execute($stmt2);

            mysqli_commit($this->con);
            return true;

        } catch (Exception $e) {
            mysqli_rollback($this->con);
            return false;
        }
    }
}
?>