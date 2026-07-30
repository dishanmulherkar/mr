<?php

include 'modals/StateWiseReportModel.php';

class StateWiseReportController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new StateWiseReportModel($con);
    }

   public function index()
{
    // Filters
    $state_id     = isset($_GET['state']) ? (int)$_GET['state'] : 0;
   
    $start_date = $_GET['from_date'] ?? '';
    $end_date   = $_GET['to_date'] ?? '';

    $from_date = !empty($start_date) ? $start_date : date('Y-m-01');
    $to_date   = !empty($end_date) ? $end_date : date('Y-m-d');

    // Dropdowns
    $states = $this->model->getStates();

    $state_name = '';
    // Report
    $query = null;
         $state_name = $this->model->getStateName($state_id);
   
        $query = $this->model->getReport(
            $state_id,
            $from_date,
            $to_date
        );
    

    // Pass selected values back to the view
    include 'view/report/StateWiseHqAndSales.php';
}
}