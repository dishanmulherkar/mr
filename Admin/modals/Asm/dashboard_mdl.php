<?php
class DashboardModel {

private $db;
    public function __construct($con)
    {
        $this->db = $con;
    }

    // Financial Year / Target
  public function getTargetDetails($asm_id)
    {
        $asm_id = (int)$asm_id;

        $query = "
            SELECT COALESCE(target, 0) AS total_target
            FROM admins
            WHERE admin_id = '$asm_id'
            LIMIT 1
        ";

        $result = mysqli_query($this->db, $query);

        if (!$result) {
            die(mysqli_error($this->db));
        }

        return mysqli_fetch_assoc($result);
    }
// Primary Sale
    public function getPrimarySale($asm_id,$fy_name = null)
    {
        $asm_id = (int)$asm_id;

        // Optional filter by specific Financial Year name (e.g., 'FY 2026-27')
        $fy_filter = "";
        if (!empty($fy_name)) {
            $fy_name_clean = mysqli_real_escape_string($this->db, trim($fy_name));$fy_filter = "AND fy.fy_name = '$fy_name_clean'";
        }

        $query = "
            SELECT COALESCE(SUM(si.business_value), 0) AS primary_sale
            FROM `headquarter` h
            -- 1. Connect active financial year for each HQ under this ASM
            INNER JOIN `financial_year` fy 
                ON fy.hq_id = h.headquarter_id 
                AND fy.status = '1'
                $fy_filter
            -- 2. Link stockists belonging to the HQ
            INNER JOIN `stockists` st 
                ON st.hq_id = h.headquarter_id
            -- 3. Link stock_inward matching that HQ's active FY date window
            INNER JOIN `stock_inward` si 
                ON si.stockist_id = st.stockist_id
                AND DATE(si.inward_date) BETWEEN fy.start_date AND fy.end_date
            WHERE h.asm_id = '$asm_id'
        ";

        $result = mysqli_query($this->db,$query);

        if (!$result) {
            die(mysqli_error($this->db));
        }

        $row = mysqli_fetch_assoc($result);
        return $row && $row['primary_sale'] !== null ? round((float)$row['primary_sale'], 2) : 0.00;
    }

    // Customer Count
    public function getTotalHqs($asm_id)
    {
        $query = "
            SELECT COUNT(*) total_hq
            FROM headquarter
            WHERE asm_id='$asm_id'
        ";

        $result = mysqli_query($this->db, $query);

        return mysqli_fetch_assoc($result)['total_hq'];
    }

}
?>