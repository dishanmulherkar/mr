<?php
// Include the required model
include_once 'modals/Asm/dashboard_mdl.php';

class AsmDashboardCtl
{
    private $con;
    private $model;

    public function __construct($con)
    {
        $this->con = $con;
        $this->model = new DashboardModel($con);
    }

    /**
     * Default action: Load the Dispatch Dashboard with Filters
     */
    public function index()
    {
        // 1. Get the Dispatch Manager's Super Stockist ID from their admin session
        $super_stockist_id = $_SESSION['stockist_id'] ?? 0;
         $asm_id = $_SESSION['admin_id'] ?? 0;

         $primary_sale = $this->model->getPrimarySale(
           $asm_id
        );
        $total_hq = $this->model->getTotalHqs($asm_id);
        $taget = $this->model->getTargetDetails($asm_id);

         $recent_activities = $this->model->getRecentActivities($asm_id, 10);

        // Notifications
        // $notification_query = $this->model->getNotifications($asm_id);

        // 4. Load the view
        include 'view/Asm/dashboard.php';
    }

    public function markActivityRead()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
            exit;
        }

        $asm_id = (int)($_SESSION['admin_id'] ?? 0);
        $id    = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $ids   = $_POST['ids'] ?? null;

        if (!$asm_id || (!$id && empty($ids))) {
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

        $result = $this->model->markActivitiesAsRead($asm_id, $target_ids);

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