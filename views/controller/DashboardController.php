<?php
require_once 'modals/DashboardModel.php';

class DashboardController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new DashboardModel($con);
    }

    public function index()
    {
        $mr_id = $_SESSION['mr_id'] ?? 0;
        $hq_id = $_SESSION['hq_id'] ?? 0;

        // Target Details
        $target = $this->model->getTargetDetails($mr_id);

        $fy_id = 0;
        $target_amount = 0;
        $start_date = '';
        $end_date = '';

        if ($target) {
            $fy_id         = $target['fy_id'];
            $target_amount = $target['target_amount'];
            $start_date    = $target['start_date'];
            $end_date      = $target['end_date'];
        }

        // Dashboard Cards
        $primary_sale = $this->model->getPrimarySale(
            $mr_id,
            $start_date,
            $end_date,
            $hq_id
        );

        $total_customers = $this->model->getTotalCustomers($hq_id);

        $total_sales_amount = $this->model->getSecondarySale(
            $mr_id,
            $fy_id
        );

        $mr_data = $this->model->getMrDetails($mr_id);

        // Fetch unread recent activities with date/priority filter
        $recent_activities = $this->model->getRecentActivities($mr_id, 10);

        // Notifications
        $notification_query = $this->model->getNotifications($_SESSION['mr_id'] ?? 0, $_SESSION['state_id'] ?? 0);

        include 'view/dashboard/index.php';
    }

    /**
     * AJAX Action: Dismiss single or multiple activities/notifications
     * Triggered by Download Invoice click, View button click, or sendBeacon
     */
    public function markActivityRead()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
            exit;
        }

        $mr_id = (int)($_SESSION['mr_id'] ?? 0);
        $id    = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $ids   = $_POST['ids'] ?? null;

        if (!$mr_id || (!$id && empty($ids))) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
            exit;
        }

        // Normalize to array of IDs
        $target_ids = [];
        if ($id > 0) {
            $target_ids[] = $id;
        } elseif (!empty($ids)) {
            $target_ids = is_array($ids) ? array_map('intval', $ids) : array_map('intval', explode(',', $ids));
        }

        $result = $this->model->markActivitiesAsRead($mr_id, $target_ids);

        if ($result) {
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
        }
        exit;
    }

    /**
     * Legacy/Batch alias keeping existing markNotificationsSeen working
     */
    public function markNotificationsSeen()
    {
        $this->markActivityRead();
    }
}