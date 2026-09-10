<?php
// Include the required model
// include_once 'modals/Asm/DispatchDashboardModel.php';

class AsmDashboardCtl
{
    private $con;
    private $model;

    public function __construct($con)
    {
        $this->con = $con;
        // $this->model = new DispatchDashboardModel($con);
    }

    /**
     * Default action: Load the Dispatch Dashboard with Filters
     */
    public function index()
    {
        // 1. Get the Dispatch Manager's Super Stockist ID from their admin session
        $super_stockist_id = $_SESSION['stockist_id'] ?? 0;
        // 3. Fetch orders based on filters
        // $orders = $this->model->getFilteredOrders($super_stockist_id, $filter_status, $filter_date);

        // 4. Load the view
        include 'view/Asm/dashboard.php';
    }

 
}