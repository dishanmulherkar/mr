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

    // Direct aggregation on stock_inward without extra joins
    $query = "
        SELECT COALESCE(SUM(si.business_value), 0) AS primary_sale
        FROM stock_inward si
        WHERE si.mr_id = '$mr_id'
    ";

    // Append the date filter if dates are provided
    if (!empty($start_date) && !empty($end_date)) {
        $start = mysqli_real_escape_string($this->db, $start_date);
        $end   = mysqli_real_escape_string($this->db, $end_date);
        
        $query .= " AND DATE(si.inward_date) BETWEEN '$start' AND '$end'";
    }

    $result = mysqli_query($this->db, $query);

    if (!$result) {
        die(mysqli_error($this->db));
    }

    $row = mysqli_fetch_assoc($result);
    return $row && $row['primary_sale'] !== null ? round((float)$row['primary_sale'], 2) : 0.00;
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
}
?>