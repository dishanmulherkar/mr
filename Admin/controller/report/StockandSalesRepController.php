<?php

include 'modals/StockandSalesRepModel.php';

class StockandSalesRepController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new StockandSalesRepModel($con);
    }

   public function index()
{
    // Filters
    $state_id     = isset($_GET['state']) ? (int)$_GET['state'] : 0;
    $hq_id        = isset($_GET['hq_id']) ? (int)$_GET['hq_id'] : 0;
    $stockist_id  = isset($_GET['stockist_id']) ? (int)$_GET['stockist_id'] : 0;

    $start_date = $_GET['start_date'] ?? '';
    $end_date   = $_GET['end_date'] ?? '';

    $from_date = !empty($start_date) ? $start_date : date('Y-m-01');
    $to_date   = !empty($end_date) ? $end_date : date('Y-m-d');

    // Dropdowns
    $states = $this->model->getStates();

    // HQ Name
    $hq_name = '';
    if($hq_id > 0)
    {
        $hq = $this->model->getHQName($hq_id);
        if($hq){
            $hq_name = $hq['hq_name'];
        }
    }

    // Stockist Name
    $stockist_name = '';
    if($stockist_id > 0)
    {
        $stockist = $this->model->getStockistName($stockist_id);
        if($stockist){
            $stockist_name = $stockist['stockist_name'];
        }
    }

    // Report
    $query = null;

    if($stockist_id > 0)
    {
        $query = $this->model->getReport(
            $stockist_id,
            $from_date,
            $to_date
        );
    }

    // Pass selected values back to the view
    include 'view/report/StockAndSalesRep.php';
}
}