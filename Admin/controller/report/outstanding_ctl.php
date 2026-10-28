<?php
include 'modals/report/outstanding_mdl.php';

class outstanding_ctl
{
    private $model;

    public function __construct($con)
    {
        $this->model = new outstanding_mdl($con);
    }

   public function index()
{
    // 1. Fetch Super Stockist list for dropdown filter
    $stockist = $this->model->getSuperStockist();

    // 2. Read GET parameters
    $super_stockist_id = isset($_GET['sup_stockist']) ? (int)$_GET['sup_stockist'] : 0;
    
    // Support either 'as_on_date' or 'to_date' from the filter form
    $to_date = !empty($_GET['to_date']) 
        ? trim($_GET['to_date']) 
        : (!empty($_GET['as_on_date']) ? trim($_GET['as_on_date']) : date('Y-m-d'));

    // Validate date format (Y-m-d); fallback to current date if invalid
    if (!strtotime($to_date)) {
        $to_date = date('Y-m-d');
    }

    // 3. Fetch Outstanding Data if Super Stockist is selected
    $outstanding = [
        'stockists'       => [],
        'total_due'       => 0.0,
        'total_advance'   => 0.0,
        'net_outstanding' => 0.0,
    ];

    if ($super_stockist_id > 0) {
        $outstanding = $this->model->getSuperStockistOutstanding($super_stockist_id, $to_date);
    }

    // 4. Load View
    include 'view/report/outstanding.php';
}

public function asm_due_state(){

            $asm_id = $_SESSION['admin_id'];
            $hq = $this->model->getHQbyAsm($asm_id);
            $hq_id       = isset($_GET['hq_id']) ? (int)$_GET['hq_id'] : 0;
            $to_date = !empty($_GET['to_date'])  ? trim($_GET['to_date']) : (!empty($_GET['as_on_date']) ? trim($_GET['as_on_date']) : date('Y-m-d'));
            $stockist_id  = isset($_GET['stockist_id']) ? (int)$_GET['stockist_id'] : 0;

              // REQUIRED: Fetch the stockists for the selected HQ so the dropdown populates
        if ($hq_id > 0) {
            // NOTE: Replace 'getStockistsByHq' with your actual model method for fetching stockists by HQ ID
            $stockists = $this->model->getStockistsByHq($hq_id); 
        }

        $query = $this->model->getoutstanding_billwise(
                $hq_id,
                $stockist_id,
                $to_date
            ); 
            include 'view/Asm/report/due_statement.php';
}
}
?>