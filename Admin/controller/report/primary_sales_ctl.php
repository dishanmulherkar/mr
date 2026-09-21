<?php

include 'modals/report/primary_sale_mdl.php';

class primary_sale_ctl
{
    private $model;

    public function __construct($con)
    {
        $this->model = new primary_sale_mdl($con);
    }


    public function get_hqs_by_asm() {
        header('Content-Type: application/json');
        $asm_id = isset($_GET['asm_id']) ? (int)$_GET['asm_id'] : 0;
        
        $hqs = $this->model->getHQByASM($asm_id);
        if (empty($hqs)) {
            echo json_encode(['success' => false, 'msg' => 'No HQs found']);
        } else {
            echo json_encode(['success' => true, 'data' => $hqs]);
        }
        exit;
    }

   public function asm_primary_sale()
    {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
         $asm_id = $_SESSION['admin_id'];
         $hq = $this->model->getHQbyAsm($asm_id);
         $hq_id       = isset($_GET['hq_id']) ? (int)$_GET['hq_id'] : 0;
         $stockist_ids = isset($_GET['stockist_id']) ? (int)$_GET['stockist_id'] : 0;

        // Get filter inputs
        $start_date  = $_GET['start_date'] ?? '';
        $end_date    = $_GET['end_date'] ?? '';

        $from_date = !empty($start_date) ? $start_date : date('Y-m-01');
        $to_date   = !empty($end_date) ? $end_date : date('Y-m-d');

        // $opening_balance = 0;
        $query = null;

        // Fetch data if HQ ID exists
        if ($hq_id > 0) {
            $query = $this->model->getPrimaryReport($hq_id, $from_date, $to_date,$stockist_id = 0);

        }

        // Load the view
        include 'view/Asm/report/primary_sale_report.php';
    }


    //    public function asm_secondary_sale()
    // {
    //     // Start session if not already started
    //     if (session_status() == PHP_SESSION_NONE) {
    //         session_start();
    //     }
    //      $asm_id = $_SESSION['admin_id'];
    //      $hq = $this->model->getHQbyAsm($asm_id);
    //      $hq_id       = isset($_GET['hq_id']) ? (int)$_GET['hq_id'] : 0;
    //      $stockist_ids = isset($_GET['stockist_id']) ? (int)$_GET['stockist_id'] : 0;

    //     // Get filter inputs
    //     $start_date  = $_GET['start_date'] ?? '';
    //     $end_date    = $_GET['end_date'] ?? '';

    //     $from_date = !empty($start_date) ? $start_date : date('Y-m-01');
    //     $to_date   = !empty($end_date) ? $end_date : date('Y-m-d');

    //     // $opening_balance = 0;
    //     $query = null;

    //     // Fetch data if HQ ID exists
    //     if ($hq_id > 0) {
    //         $query = $this->model->getPrimaryReport($hq_id, $from_date, $to_date,$stockist_id = 0);

    //     }

    //     // Load the view
    //     include 'view/Asm/report/primary_sale_report.php';
    // }

    public function asm_secondary_sale()
{
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $asm_id = $_SESSION['admin_id'] ?? 0;
    $hq     = $this->model->getHQbyAsm($asm_id);

    // Filter inputs from URL
    $hq_id       = isset($_GET['hq_id']) ? (int)$_GET['hq_id'] : 0;
    $stockist_id = isset($_GET['stockist_id']) ? (int)$_GET['stockist_id'] : 0;
    $sale_type   = trim($_GET['sale_type'] ?? '');
    $start_date  = $_GET['start_date'] ?? '';
    $end_date    = $_GET['end_date'] ?? '';

    // Date range defaults: 1st of current month to current date
    $from_date = !empty($start_date) ? $start_date : date('Y-m-01');
    $to_date   = !empty($end_date) ? $end_date : date('Y-m-d');

    $query     = null;
    $stockists = [];

    // Fetch data if HQ is selected
    if ($hq_id > 0) {
        // Fetch stockists for the selected HQ so the dropdown options render on page load
        if (method_exists($this->model, 'getStockistsByHq')) {
            $stockists = $this->model->getStockistsByHq($hq_id);
        }

        // Fetch secondary sale report data using actual filtered values
        $query = $this->model->getSecondaryReport($hq_id, $from_date, $to_date, $stockist_id, $sale_type);
    }

    // Load the view
    include 'view/Asm/report/secondary_sale.php';
}
    

 
}