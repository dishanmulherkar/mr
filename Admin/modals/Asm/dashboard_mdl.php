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
        // 1. Escape the variable to prevent SQL injection
        $asm_id = mysqli_real_escape_string($this->db, $asm_id);

        // 2. Added aliases (fy.) to the WHERE clause to fix ambiguous columns
        $query = "
            SELECT
                fy.fy_id,
                fy.target_amount,
                fy.start_date,
                fy.end_date
            FROM financial_year fy
            INNER JOIN mr_users h
                ON h.m_id ='$asm_id'
            INNER JOIN headquarter hq
                ON hq.headquarter_id =  h.hq_id
            WHERE fy.hq_id =  h.hq_id
            AND fy.status = '1'
            LIMIT 1
        ";

        $result = mysqli_query($this->db, $query);

        // Optional: Error handling to easily catch DB issues
        if (!$result) {
            die(mysqli_error($this->db));
        }

        return mysqli_fetch_assoc($result);
    }
// Primary Sale
    public function getPrimarySale($asm_id)
    {
        // 1. Base query without the date filter
           $query = "
           SELECT COALESCE(SUM(si.business_value), 0) AS primary_sale
        FROM stock_inward si
        INNER JOIN stockists st
            ON si.stockist_id = st.stockist_id
            INNER JOIN `headquarter` h ON h.headquarter_id = st.hq_id 
        WHERE h.asm_id = '$asm_id'
        ";

        // 2. Append the date filter ONLY if dates are passed in
        if (!empty($start_date) && !empty($end_date)) {
            // Escape the variables to prevent SQL injection
            $start = mysqli_real_escape_string($this->db, $start_date);
            $end = mysqli_real_escape_string($this->db, $end_date);
            
            // Using DATE() ensures it matches properly even if inward_date has a timestamp (e.g. 2026-08-14 15:30:00)
            $query .= " AND DATE(si.inward_date) BETWEEN '$start' AND '$end'";
        }

        // 3. Execute query
        $result = mysqli_query($this->db, $query);

        // Optional: Error handling if the query fails
        if (!$result) {
            die(mysqli_error($this->db));
        }

        return mysqli_fetch_assoc($result)['primary_sale'];
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