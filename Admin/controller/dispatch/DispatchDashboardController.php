<?php
// Include the required model
include_once 'modals/dispatch/DispatchDashboardModel.php';

class DispatchDashboardController
{
    private $con;
    private $model;

    public function __construct($con)
    {
        $this->con = $con;
        $this->model = new DispatchDashboardModel($con);
    }

    /**
     * Default action: Load the Dispatch Dashboard with Filters
     */
    public function index()
    {
        // 1. Get the Dispatch Manager's Super Stockist ID from their admin session
        $super_stockist_id = $_SESSION['stockist_id'] ?? 0;

        // 2. Capture GET filters (Default to 'Approved' which means Pending Dispatch)
        $filter_status = $_GET['filter_status'] ?? 'Approved'; 
        $filter_date   = $_GET['filter_date'] ?? '';

        // 3. Fetch orders based on filters
        $orders = $this->model->getFilteredOrders($super_stockist_id, $filter_status, $filter_date);

        // 4. Load the view
        include 'view/dispatch/dashboard.php';
    }

    /**
     * AJAX Endpoint: Fetch order details for the Modal
     */
    public function getDetails($order_id)
    {
        header('Content-Type: application/json');
        
        $data = $this->model->getOrderDetails($order_id);
        
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'No details found']);
        }
        exit;
    }

    /**
     * AJAX Endpoint: Update status to Dispatch
     */
    public function markDispatched()
    {
        header('Content-Type: application/json');
        
        $order_id = $_POST['order_id'] ?? 0;
        
        if ($this->model->updateOrderStatus($order_id, 'Processed')) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Database error while updating status.']);
        }
        exit;
    }
}