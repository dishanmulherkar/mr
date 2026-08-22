<?php

require_once "modals/commision/commission_mdl.php";

class commission_ctl
{
    private $model;
    
    public function __construct()
    {
        $this->model = new commission_mdl();
    }

    public function index()
    {
        if (!isset($_SESSION['mr_id'])) {
            header('Location: index');
            exit;
        }

        $mr_id = $_SESSION['mr_id'];
        $balances = $this->model->getWalletBalances($mr_id);
        $mrc_balance = $balances['mrc_balance'];
        $stockists = $this->model->getStockists($mr_id);

        include 'view/commission/commision.php';
    }

    public function mr_commision($id = 0)
    {
        if (!isset($_SESSION['mr_id'])) {
            header('Location: index');
            exit;
        }
        $mr_id = $_SESSION['mr_id']; // Get logged-in MR's ID
$balances = $this->model->getWalletBalances($mr_id);

// Extract both balances to pass to the view
$mrc_balance = $balances['mrc_balance'];
        
        $stockists = $this->model->getStockists($mr_id);

        include 'view/commission/commision.php';
    }

    // ========================================================
    // NEW: AJAX Endpoint to fetch the MR's Payouts
    // ========================================================
    public function get_com_data()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['mr_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
            exit;
        }

        $mr_id       = $_SESSION['mr_id'];
        $stockist_id = isset($_GET['stockist_id']) ? (int)$_GET['stockist_id'] : 0;
        $from_date   = $_GET['from_date'] ?? '';

        $commissions = $this->model->getMrCommissionsList($mr_id, $stockist_id, $from_date);

        if ($commissions) {
            echo json_encode(['success' => true, 'data' => $commissions]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'No commissions found based on your filters.']);
        }
        exit;
    }

    public function list_orders()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['mr_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
            exit;
        }

        $mr_id       = $_SESSION['mr_id'];
        $stockist_id = isset($_GET['stockist_id']) ? (int)$_GET['stockist_id'] : 0;
        $from_date   = $_GET['from_date'] ?? '';
        $to_date     = $_GET['to_date'] ?? '';

        $orders = $this->model->getOrdersByMr($mr_id, $stockist_id, $from_date, $to_date);

        echo json_encode(['success' => true, 'data' => $orders]);
        exit;
    }

    // ========================================================
    // View Single Commission Details
    // ========================================================
    public function view($payout_id = 0) {
        if (!isset($_SESSION['mr_id'])) {
            header('Location: ' . BASE_URL);
            exit;
        }

        $mr_id = $_SESSION['mr_id'];
        $payout_id = (int)$payout_id;

        if ($payout_id <= 0) {
            echo "<div class='alert alert-danger'>Invalid Payout ID.</div>";
            exit;
        }

        // Fetch data from Model
        $viewData = $this->model->getCommissionViewData($payout_id, $mr_id);

        if (!$viewData) {
            echo "<div class='alert alert-danger text-center mt-5'>Commission record not found or access denied.</div>";
            exit;
        }

        $payout = $viewData['payout'];
        $bills = $viewData['bills'];
        $adjustments = $viewData['adjustments'];

        $pageTitle = "View Commission Details";
        include 'view/commission/view_com.php';
    }

     public function dr_commision($id = 0)
    {
        if (!isset($_SESSION['mr_id'])) {
            header('Location: index');
            exit;
        }
        $mr_id     = $_SESSION['mr_id'];
        $balances = $this->model->getWalletBalances($mr_id);
        $mrc_balance = $balances['mrc_balance'];
        $drc_balance = $balances['drc_balance'];
        
        $stockists = $this->model->getStockists($mr_id);

        include 'view/commission/drcommision.php';
    }

     // ========================================================
    // View Single drCommission Details
    // ========================================================
    public function drview($payout_id = 0) {
        if (!isset($_SESSION['mr_id'])) {
            header('Location: ' . BASE_URL);
            exit;
        }

        $mr_id = $_SESSION['mr_id'];
        $payout_id = (int)$payout_id;

        if ($payout_id <= 0) {
            echo "<div class='alert alert-danger'>Invalid Payout ID.</div>";
            exit;
        }

        // Fetch data from Model
        $viewData = $this->model->getCommissionViewData($payout_id, $mr_id);

        if (!$viewData) {
            echo "<div class='alert alert-danger text-center mt-5'>Commission record not found or access denied.</div>";
            exit;
        }

        $payout = $viewData['payout'];
        $bills = $viewData['bills'];
        $adjustments = $viewData['adjustments'];

        $pageTitle = "View Commission Details";
        include 'view/commission/view_drc.php';
    }

     // ========================================================
    // NEW: AJAX Endpoint to fetch the MR's Payouts
    // ========================================================
    public function get_drcom_data()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['mr_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
            exit;
        }

        $mr_id       = $_SESSION['mr_id'];
        $stockist_id = isset($_GET['stockist_id']) ? (int)$_GET['stockist_id'] : 0;
        $from_date   = $_GET['from_date'] ?? '';

        $commissions = $this->model->getDrCommissionsList($mr_id, $stockist_id, $from_date);

        if ($commissions) {
            echo json_encode(['success' => true, 'data' => $commissions]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'No commissions found based on your filters.']);
        }
        exit;
    }
}