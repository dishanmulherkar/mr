<?php
class DashboardModel {

private $db;
    public function __construct($con)
    {
        $this->db = $con;
    }

    // Financial Year / Target
   public function getTargetDetails($mr_id)
    {
        $mr_id = (int)$mr_id;

        // Strict MR isolation: fetch active FY directly by mr_id
        $query = "
            SELECT
                fy.fy_id,
                fy.target_amount,
                fy.start_date,
                fy.end_date
            FROM financial_year fy
            WHERE fy.mr_id = '$mr_id'
            AND fy.status = '1'
            ORDER BY fy.fy_id DESC
            LIMIT 1
        ";

        $result = mysqli_query($this->db, $query);

        if (!$result) {
            die(mysqli_error($this->db));
        }

        return mysqli_fetch_assoc($result);
    }
// Primary Sale
    public function getPrimarySale($mr_id, $start_date = '', $end_date = '', $hq_id = 0)
    {
        $mr_id = (int)$mr_id;

        $date_condition = "";
        if (!empty($start_date) && !empty($end_date)) {
            $start = mysqli_real_escape_string($this->db, date('Y-m-d', strtotime($start_date)));
            $end   = mysqli_real_escape_string($this->db, date('Y-m-d', strtotime($end_date)));
            $date_condition = " AND DATE(si.inward_date) BETWEEN '$start' AND '$end'";
        }

        // Query from mr_users first so primary_op is always fetched even if 0 bills exist
        $query = "
            SELECT 
                COALESCE(mr.primary_op, 0) AS opening_sale,
                COALESCE(SUM(si.business_value), 0) AS inward_sale,
                (COALESCE(mr.primary_op, 0) + COALESCE(SUM(si.business_value), 0)) AS total_primary_sale
            FROM mr_users mr
            LEFT JOIN stock_inward si ON mr.m_id = si.mr_id $date_condition
            WHERE mr.m_id = '$mr_id'
            GROUP BY mr.m_id, mr.primary_op
        ";

        $result = mysqli_query($this->db, $query);

        if (!$result) {
            die(mysqli_error($this->db));
        }

        $row = mysqli_fetch_assoc($result);

        return $row ? round((float)$row['total_primary_sale'], 2) : 0.00;
    }
    // Customer Count
    public function getTotalCustomers($hq_id)
    {
        $query = "
            SELECT COUNT(*) total_customers
            FROM customers
            WHERE hq_id='$hq_id'
        ";

        $result = mysqli_query($this->db, $query);

        return mysqli_fetch_assoc($result)['total_customers'];
    }

    // Secondary Sale
    public function getSecondarySale($mr_id, $fy_id)
    {
        $query = "
            SELECT COALESCE(SUM(sd.amount),0) total_sales_amount
            FROM sales_entries se
            INNER JOIN sales_details sd
                ON se.s_id=sd.s_id
            WHERE se.m_id='$mr_id'
            
        ";

        $result = mysqli_query($this->db, $query);

        return mysqli_fetch_assoc($result)['total_sales_amount'];
    }

    // Notifications
    public function getNotifications($mr_id, $state_id)
    {
        $query = "
            SELECT n.*
            FROM notifications n
            LEFT JOIN notific_seen ns
                ON ns.notification_id = n.notification_id
                AND ns.hq_Id = '$mr_id'
            WHERE
                n.status='1'
                AND (
                    n.send_type='all'
                    OR FIND_IN_SET('$mr_id', n.hq_ids)
                )
                AND (
                    n.state_id = 0
                    OR n.state_id = '$state_id'
                )
                AND ns.notification_id IS NULL
            ORDER BY n.notification_id DESC
            LIMIT 5
        ";

        return mysqli_query($this->db, $query);
    }

   public function markNotificationsSeen($mr_id, $ids)
    {
        $ids = array_filter(array_map('intval', explode(',', $ids)));

        if (empty($ids)) {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT IGNORE INTO notific_seen (hq_Id, notification_id)
            VALUES (?, ?)
        ");

        if (!$stmt) {
            return false;
        }

        foreach ($ids as $notification_id) {
            $stmt->bind_param("ii", $mr_id, $notification_id);
            $stmt->execute();
        }

        $stmt->close();

        return true;
    }

     public function getMrDetails($mr_id)
    {
        $query = "
            SELECT hq.hq_name, mr.mr_name 
            FROM mr_users mr 
            INNER JOIN headquarter hq ON mr.hq_id = hq.headquarter_id

            WHERE m_id='$mr_id'
        ";

        $result = mysqli_query($this->db, $query);

        return mysqli_fetch_assoc($result);
    }

    public function getFilteredDashboardActivities($user_id)
{
    $user_id = (int)$user_id;

    $sql = "
        SELECT 
            id,
            activity_type,
            title,
            message,
            reference_id,
            created_at,
            priority
        FROM activity_logs
        WHERE is_read = 0
          AND (user_id = ? OR user_id IS NULL)
          AND (
              -- Rule A: High Priority / Rejections stay until explicitly clicked
              priority = 'high' 
              OR activity_type LIKE '%REJECTED%'
              OR title LIKE '%rejected%'
              
              -- Rule B: Normal alerts show ONLY today; tomorrow they vanish automatically
              OR DATE(created_at) = CURRENT_DATE()
          )
        ORDER BY created_at DESC 
        LIMIT 25
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Fetch unread activities:
 * - High priority (rejections) stay until clicked.
 * - Standard activities show only today (disappear tomorrow even if unclicked).
 */
public function getRecentActivities($mr_id, $limit = 10)
{
    $mr_id = (int)$mr_id;
    $limit = (int)$limit;

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
        WHERE (al.user_id = ? OR al.user_id IS NULL OR al.user_id = 0)
          AND COALESCE(al.is_read, 0) = 0
          AND (
              al.priority = 'high' 
              OR al.activity_type LIKE '%REJECTED%'
              OR al.title LIKE '%rejected%'
              OR DATE(al.created_at) = CURRENT_DATE()
          )
        ORDER BY al.created_at DESC 
        LIMIT ?
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("ii", $mr_id, $limit);
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
public function markActivitiesAsRead($mr_id, array $activity_ids)
{
    $activity_ids = array_filter(array_map('intval', $activity_ids));
    if (empty($activity_ids)) {
        return false;
    }

    $in_clause = implode(',', $activity_ids);

    // If using the activity_logs table is_read column:
    $sql = "UPDATE activity_logs 
            SET is_read = 1, read_at = NOW() 
            WHERE id IN ({$in_clause})";

    return (bool)$this->db->query($sql);
}
}
?>