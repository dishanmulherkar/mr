<?php
class primary_sale_mdl
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

            public function getHQByASM($asm_id) {
        $asm_id = (int)$asm_id;
        $stmt = $this->con->prepare("SELECT headquarter_id, hq_name FROM `headquarter` WHERE asm_id = ? ");
        $stmt->bind_param("i", $asm_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $data = [];
        while($row = $res->fetch_assoc()) { $data[] = $row; }
        $stmt->close();
        return $data;
    }

// Filtered to show bill_added, payment_made, and commission settlements FOR DEBT ONLY
  public function getPrimaryReport($hq_id,$from_date, $to_date,$stockist_id = '')
{
    $hq_id       = mysqli_real_escape_string($this->con, trim($hq_id));$from_date   = mysqli_real_escape_string($this->con, trim($from_date));
    $to_date     = mysqli_real_escape_string($this->con, trim($to_date));$stockist_id = mysqli_real_escape_string($this->con, trim($stockist_id));

    $stockist_condition = !empty($stockist_id) ? "AND si.stockist_id = '$stockist_id'" : "";

    $sql = "SELECT si.inward_date, si.inward_no, si.grand_total, s.stockist_name
            FROM stock_inward si      
            INNER JOIN `stockists` s ON s.stockist_id = si.stockist_id 
            INNER JOIN `mr_users` m  ON m.m_id = si.mr_id
            WHERE m.hq_id = '$hq_id'
              AND DATE(si.inward_date) >= '$from_date' 
              AND DATE(si.inward_date) <= '$to_date'$stockist_condition
              AND  m.status = '1'
            ORDER BY si.inward_date ASC, si.inward_id ASC";

    return mysqli_query($this->con,$sql);
}

public function getSecondaryReport($hq_id, $from_date, $to_date, $stockist_id = '', $sale_type = '')
{
    $hq_id       = mysqli_real_escape_string($this->con, trim($hq_id));
    $from_date   = mysqli_real_escape_string($this->con, trim($from_date));
    $to_date     = mysqli_real_escape_string($this->con, trim($to_date));
    $stockist_id = mysqli_real_escape_string($this->con, trim($stockist_id));
    $sale_type   = mysqli_real_escape_string($this->con, trim($sale_type));

    $stockist_cond = !empty($stockist_id) ? "AND s.stockist_id = '$stockist_id'" : "";
    $type_cond     = !empty($sale_type) ? "AND c.customer_type = '$sale_type'" : "";

    $sql = "SELECT s.*, 
                   st.stockist_name, 
                   c.customer_name, 
                   c.customer_type, 
                   m.mr_name AS mr_name
            FROM sales_entries s
            INNER JOIN mr_users m ON m.m_id = s.m_id
            LEFT JOIN stockists st ON s.stockist_id = st.stockist_id
            LEFT JOIN customers c ON s.c_id = c.c_id
            WHERE m.hq_id = '$hq_id'
              AND DATE(s.sale_date) >= '$from_date'
              AND DATE(s.sale_date) <= '$to_date'
              $stockist_cond
              $type_cond
            ORDER BY s.sale_date DESC, s.s_id DESC";

    return mysqli_query($this->con, $sql);
}
}
?>