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
        $sql = "SELECT r.*, s.ss_name as stockist_name 
                FROM super_stockist_cd_rules r 
                LEFT JOIN `super_stockist` s ON r.super_stockist_id = s.super_stockist_id";
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
        $sql = "SELECT * FROM super_stockist_cd_rules WHERE super_stockist_id = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }

    public function saveRule($data)
    {
        // Upsert logic: Inserts if new, Updates if super_stockist_id already exists
        $sql = "INSERT INTO super_stockist_cd_rules (super_stockist_id, cd_4_percent_days, cd_2_percent_days) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                cd_4_percent_days = VALUES(cd_4_percent_days), 
                cd_2_percent_days = VALUES(cd_2_percent_days)";

        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param(
            $stmt, 
            "iii", 
            $data['super_stockist_id'], 
            $data['cd_4_percent_days'], 
            $data['cd_2_percent_days']
        );
        
        return mysqli_stmt_execute($stmt);
    }
}
?>