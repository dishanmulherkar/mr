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
        $query = "
            SELECT
                fy_id,
                target_amount,
                start_date,
                end_date
            FROM financial_year
            WHERE hq_id='$mr_id'
            AND status='1'
            LIMIT 1
        ";

        $result = mysqli_query($this->db, $query);

        return mysqli_fetch_assoc($result);
    }

    // Primary Sale
    public function getPrimarySale($mr_id, $start_date, $end_date)
    {
        $query = "
            SELECT COALESCE(SUM(sid.qty * sid.rate),0) AS primary_sale
            FROM stock_inward si
            INNER JOIN stock_inward_details sid
                ON si.inward_id = sid.inward_id
            INNER JOIN stockists st
                ON si.stockist_id = st.stockist_id
            WHERE st.hq_id='$mr_id'
            AND si.inward_date BETWEEN '$start_date' AND '$end_date'
        ";

        $result = mysqli_query($this->db, $query);

        return mysqli_fetch_assoc($result)['primary_sale'];
    }

    // Customer Count
    public function getTotalCustomers($mr_id)
    {
        $query = "
            SELECT COUNT(*) total_customers
            FROM customers
            WHERE hq_id='$mr_id'
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
            AND se.fy_id='$fy_id'
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