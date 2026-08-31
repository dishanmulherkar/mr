<?php
require_once "modals/payment_ledger/payment_ledger_mdl.php";

class payment_ledger_ctl
{
    private $model;

    public function __construct()
    {
        // Bring in your database connection. 
        // Adjust this depending on how your framework/core sets up the DB connection.
        global $con; 
        $this->model = new payment_ledger_mdl($con);
    }

    public function index()
    {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Retrieve variables from session as mentioned in your prompt
        $mr_id = $_SESSION['mr_id'] ?? 0;
        $hq_id = $_SESSION['hq_id'] ?? 0;

       
        // Prevent undefined variable errors in the view
        $isEdit = false;
        $order_data = ['stockist_id' => ''];

        // Get filter inputs
        $stockist_id = isset($_GET['stockist_id']) ? (int)$_GET['stockist_id'] : 0;
        $start_date  = $_GET['start_date'] ?? '';
        $end_date    = $_GET['end_date'] ?? '';

        $from_date = !empty($start_date) ? $start_date : date('Y-m-01');
        $to_date   = !empty($end_date) ? $end_date : date('Y-m-d');

        // Fetch stockists for the MR dropdown
        $stockists = $this->model->getStockists($mr_id);

        // Optional: Fetch HQ name if needed somewhere
        $hq_name = '';
        // Assuming you add getHQName in your model if you need it. 
        // If not, you can safely ignore this part.

        // Variables for the report
        $stockist_name = '';
        $query = null;
        $opening_balance = 0;

        if ($stockist_id > 0) {
            // Get Stockist Name
            $stockist = $this->model->getStockistName($stockist_id);
            if ($stockist) {
                $stockist_name = $stockist['stockist_name'];
            }

            // Get opening balance calculated before the start date
            $opening_balance = $this->model->getOpeningBalance($stockist_id, $from_date);
            
            // Get the actual ledger rows for the date range
            $query = $this->model->getReport(
                $stockist_id,
                $from_date,
                $to_date
            );
        }

        // Load the view
        include 'view/report/payment_ledger.php';
    }

    // ==========================================
    // MR Commission (MRC) Ledger Controller
    // ==========================================
    public function mrc_ledger()
    {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $hq_id = $_SESSION['hq_id'] ?? 0;

        // Get filter inputs
        $start_date  = $_GET['start_date'] ?? '';
        $end_date    = $_GET['end_date'] ?? '';

        $from_date = !empty($start_date) ? $start_date : date('Y-m-01');
        $to_date   = !empty($end_date) ? $end_date : date('Y-m-d');

        $opening_balance = 0;
        $query = null;

        // Fetch data if HQ ID exists
        if ($hq_id > 0) {
            $opening_balance = $this->model->getOpeningBalancemrc($hq_id, $from_date);
            $query = $this->model->getReportmrc($hq_id, $from_date, $to_date);
        }

        // Load the view
        include 'view/report/mrc_ledger.php';
    }
}
?>