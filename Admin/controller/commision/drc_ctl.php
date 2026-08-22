<?php
include 'modals/commision/drc_mdl.php'; 

class drc_ctl {
    private $commissionModel;

    public function __construct() {
        $this->commissionModel = new drc_mdl();
    }

    // ==========================================
    // 1. CREATE MODE: Load blank page
    // ==========================================
    public function index() {
        if (!isset($_SESSION['admin_id'])) {
            header('Location: index');
            exit;
        }
        
        $states = $this->commissionModel->getStates();
        $isEditMode = false;
        
        include 'view/commission/drc_entry.php'; // Update path if needed
    }

    // ==========================================
    // 2. EDIT MODE: Load page with existing data
    // ==========================================
    public function edit($payout_id = 0) {
        if (!isset($_SESSION['admin_id'])) {
            header('Location: index');
            exit;
        }

        $payout_id = (int)$payout_id;

        if ($payout_id <= 0) {
            echo "<div class='alert alert-danger'>Invalid Payout ID.</div>";
            exit;
        }

        // Fetch the data directly from the model
        $editData = $this->commissionModel->getEditData($payout_id);
        
        if (!$editData) {
            echo "<div class='alert alert-danger'>Payout record not found.</div>";
            exit;
        }

        $edit_hq_id = $editData['hq_id']; 
        $isEditMode = true;
        
        $states = $this->commissionModel->getStates(); 
        
        include 'view/commission/drc_entry.php';
    }

    // ==========================================
    // 3. AJAX: Fetch Bills (Used in Create Mode)
    // ==========================================
    public function fetch_mr_bills() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['admin_id'])) exit;

        $hqId = isset($_GET['hqId']) ? (int)$_GET['hqId'] : 0;
        $month = isset($_GET['month']) ? $_GET['month'] : ''; 

        if ($hqId <= 0) {
            echo json_encode(['success' => false, 'msg' => 'HQ ID missing.']);
            exit;
        }

        try {
            $bills = $this->commissionModel->getAdminMrBills($hqId, $month);
            if (empty($bills)) {
                echo json_encode(['success' => false, 'msg' => 'No bills found.']);
                exit;
            }
            echo json_encode(['success' => true, 'data' => $bills]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
    }

    // ==========================================
    // 4. AJAX: Save New Payout
    // ==========================================
    public function claim_drc() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['admin_id'])) exit;

        $hq_id = isset($_POST['hq_id']) ? (int)$_POST['hq_id'] : 0;
        $bill_ids = isset($_POST['bill_ids']) ? $_POST['bill_ids'] : '[]';
        $adjustments = isset($_POST['adjustments']) ? $_POST['adjustments'] : '[]';
        $final_payout = isset($_POST['final_payout']) ? (float)$_POST['final_payout'] : 0.00;
        
        // Capture the status (defaults to 'Pending' if not set)
        $status = isset($_POST['status']) ? $_POST['status'] : 'Pending';

        $result = $this->commissionModel->claimMrCommission($hq_id, $bill_ids, $adjustments, $final_payout, $status);
        echo json_encode($result);
    }

    // ==========================================
    // 5. AJAX: Update Existing Payout
    // ==========================================
    public function update_drc() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['admin_id'])) exit;

        $payout_id = isset($_POST['payout_id']) ? (int)$_POST['payout_id'] : 0;
        $hq_id = isset($_POST['hq_id']) ? (int)$_POST['hq_id'] : 0;
        $bill_ids = isset($_POST['bill_ids']) ? $_POST['bill_ids'] : '[]';
        $adjustments = isset($_POST['adjustments']) ? $_POST['adjustments'] : '[]';
        $final_payout = isset($_POST['final_payout']) ? (float)$_POST['final_payout'] : 0.00;
        
        // Capture the status
        $status = isset($_POST['status']) ? $_POST['status'] : 'Pending';

        $result = $this->commissionModel->updateMrCommission($payout_id, $hq_id, $bill_ids, $adjustments, $final_payout, $status);
        echo json_encode($result);
    }

    // ==========================================
    // 6. AJAX: Delete Payout (NEW)
    // ==========================================
    public function delete_drc() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['admin_id'])) exit;

        $payout_id = isset($_POST['payout_id']) ? (int)$_POST['payout_id'] : 0;

        if ($payout_id <= 0) {
            echo json_encode(['success' => false, 'msg' => 'Invalid Payout ID.']);
            exit;
        }

        $result = $this->commissionModel->deleteMrCommission($payout_id);
        echo json_encode($result);
    }

    // ==========================================
    // 7. HISTORY VIEWS (Datatable)
    // ==========================================
    public function drc_history() {
        if (!isset($_SESSION['admin_id'])) { header('Location: index'); exit; }
        $states = $this->commissionModel->getStates();
        include 'view/commission/drc_list.php'; // Update path if needed
    }

    public function get_drc_history() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['admin_id'])) exit;

        $hq_id = isset($_GET['hqId']) ? (int)$_GET['hqId'] : 0;
        $month = isset($_GET['month']) ? trim($_GET['month']) : '';

        $data = $this->commissionModel->getDrCommissionHistory($hq_id, $month);
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'No payouts found.']);
        }
    }

    public function save_filters_session() 
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_POST['state_id'])) {
            $_SESSION['filter_state'] = $_POST['state_id'];
        }
        if (isset($_POST['hq_id'])) {
            $_SESSION['filter_hq'] = $_POST['hq_id'];
        }
        if (isset($_POST['month'])) {
            $_SESSION['filter_month'] = $_POST['month'];
        }
        
        echo json_encode(['status' => 'success']);
        exit;
    }
}
?>