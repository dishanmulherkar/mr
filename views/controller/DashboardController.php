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
        $mr_id = $_SESSION['mr_id'];

        // Target Details
        $target = $this->model->getTargetDetails($mr_id);

        $fy_id = 0;
        $target_amount = 0;
        $start_date = '';
        $end_date = '';

        if($target)
        {
            $fy_id = $target['fy_id'];
            $target_amount = $target['target_amount'];
            $start_date = $target['start_date'];
            $end_date = $target['end_date'];
        }

        // Dashboard Cards
        $primary_sale = $this->model->getPrimarySale(
            $mr_id,
            $start_date,
            $end_date
        );

        $total_customers = $this->model->getTotalCustomers($mr_id);

        $total_sales_amount = $this->model->getSecondarySale(
            $mr_id,
            $fy_id
        );

        // Notifications
        $notification_query = $this->model->getNotifications($_SESSION['mr_id'],$_SESSION['state_id']);

        include 'view/dashboard/index.php';
    }

    public function markNotificationsSeen()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            http_response_code(400);
            exit('Invalid Request');
        }

        $mr_id = $_SESSION['mr_id'] ?? 0;
        $ids   = $_POST['ids'] ?? '';

        if (!$mr_id || empty($ids)) {
            http_response_code(400);
            exit('Invalid Request');
        }

        if ($this->model->markNotificationsSeen($mr_id, $ids)) {
            echo json_encode([
                'status' => 'success'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'status' => 'error'
            ]);
        }

        exit;
    }
}