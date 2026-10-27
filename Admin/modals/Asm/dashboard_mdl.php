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

/**
 * Fetch unread activities:
 * - High priority (rejections) stay until clicked.
 * - Standard activities show only today (disappear tomorrow even if unclicked).
 */
    public function getRecentActivities($asm_id, $limit = 10)
    {
        $asm_id = (int)$asm_id;
        $limit  = (int)$limit;

        $sql = "
            SELECT 
                al.id,
                al.activity_type,
                al.title,
                al.message,
                al.reference_id,
                al.created_at,
                al.priority
            FROM activity_logs al
            INNER JOIN mr_users mr ON al.user_id = mr.m_id
            INNER JOIN headquarter hq ON mr.hq_id = hq.headquarter_id
            WHERE hq.asm_id = ?
            AND COALESCE(al.is_read_asm, 0) = 0
            AND (
                al.priority = 'high' 
                OR al.activity_type LIKE '%REJECTED%'
                OR al.title LIKE '%rejected%'
                OR al.created_at >= CURRENT_DATE()
            )
            ORDER BY al.created_at DESC 
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $asm_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $activities = [];
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
        $stmt->close();

        return $activities;
    }
/**
 * Marks specified activity IDs as read
 */
    public function markActivitiesAsRead($asm_id, array $activity_ids)
    {
        $activity_ids = array_filter(array_map('intval', $activity_ids));
        if (empty($activity_ids)) {
            return false;
        }

        $in_clause = implode(',', $activity_ids);

        // If using the activity_logs table is_read column:
        $sql = "UPDATE activity_logs 
                SET is_read_asm = 1, read_at = NOW() 
                WHERE id IN ({$in_clause})";

        return (bool)$this->db->query($sql);
    }
}
?>