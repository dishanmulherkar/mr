<?php
include 'modals/report/payment_ledger_mdl.php';

class payment_ledger_ctl
{
    private $model;

    public function __construct($con)
    {
        $this->model = new payment_ledger_mdl($con);
    }

    public function index()
    {
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
        // Report Data & Opening Balance
        $query = null;
        $opening_balance = 0;

        if($stockist_id > 0)
        {
            // Get opening balance calculated before the start date
            $opening_balance = $this->model->getOpeningBalance($stockist_id, $from_date);
            
            // Get the actual ledger rows for the date range
            $query = $this->model->getReport(
                $stockist_id,
                $from_date,
                $to_date
            );
        }
        include 'view/report/payment_ledger.php';
    }


        public function mrc_ledger()
        {
            $state_id     = isset($_GET['state']) ? (int)$_GET['state'] : 0;
            $hq_id        = isset($_GET['hq_id']) ? (int)$_GET['hq_id'] : 0;

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
            // Report Data & Opening Balance
            $query = null;
            $opening_balance = 0;
            // Get opening balance calculated before the start date
            $opening_balance = $this->model->getOpeningBalancemrc($hq_id, $from_date);
            
            // Get the actual ledger rows for the date range
            $query = $this->model->getReportmrc(
                $hq_id,
                $from_date,
                $to_date
            );
            include 'view/report/mrc_ledger_report.php';
        }

         public function drc_ledger()
        {
            $state_id     = isset($_GET['state']) ? (int)$_GET['state'] : 0;
            $hq_id        = isset($_GET['hq_id']) ? (int)$_GET['hq_id'] : 0;

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
            // Report Data & Opening Balance
            $query = null;
            $opening_balance = 0;
            // Get opening balance calculated before the start date
            $opening_balance = $this->model->getOpeningBalancedrc($hq_id, $from_date);
            
            // Get the actual ledger rows for the date range
            $query = $this->model->getReportdrc(
                $hq_id,
                $from_date,
                $to_date
            );
            include 'view/report/drc_ledger_report.php';
        }
}
?>