<?php

include 'modals/InwardReportModel.php';

class InwardReportController
{
    private $model;
    private $uploadDir;

    public function __construct($con)
    {
        $this->model = new InwardReportModel($con);
    }

    public function index() 
    {
        // 1. Capture inputs safely
        $state_id    = isset($_GET['state']) ? (int) $_GET['state'] : (isset($_GET['state_id']) ? (int) $_GET['state_id'] : 0);
        $hq_id       = isset($_GET['hq_id']) ? (int) $_GET['hq_id'] : 0;
        $stockist_id = isset($_GET['stockist_id']) ? (int) $_GET['stockist_id'] : 0;

        // 2. Fetch Base Dropdown Data
        $states    = $this->model->getStates();
        $hqs       = false;
        $stockists = false;

        // Populate cascading dropdowns if values exist (prevents dropdowns resetting on form submit)
        if ($state_id > 0) {
            $hqs = $this->model->getHQsByState($state_id);
        }
        if ($hq_id > 0) {
            $stockists = $this->model->getStockistsByHq($hq_id);
        }

        // 3. Fetch specific names (Optional, based on your original logic)
        $stockist_name = $this->model->getStockistName($stockist_id);
        $hq_name       = $this->model->getHqName($hq_id);

        // 4. Fetch the main report query
        $from_date = $_GET['from_date'] ?? '';
$to_date   = $_GET['to_date'] ?? '';

$query = $this->model->getReportData(
                        $stockist_id,
                        $from_date,
                        $to_date
                    );
        
        include 'view/report/InwardReport.php'; // Update with your actual view file path
    }

  public function details($inward_id)
{
    $inward_id = (int)$inward_id;

    if ($inward_id <= 0) {
        die("Invalid Inward ID provided.");
    }

    $row1 = $this->model->getInwardHeader($inward_id);
    $query = $this->model->getInwardProducts($inward_id);

    if (!$row1) {
        die("Record not found.");
    }

    include 'view/report/InwardReportDetail.php';
}
   
    
}