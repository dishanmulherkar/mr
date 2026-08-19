<?php
include 'modals/commision/Commission_mdl.php'; 

class CommissionController {
    private $commissionModel;

    public function __construct() {
        $this->commissionModel = new Commission_mdl();
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
        
        include 'view/commission/mr_com.php'; // Update path if needed
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

        // Fetch the data directly from the model (Model will find the HQ automatically)
        $editData = $this->commissionModel->getEditData($payout_id);
        
        if (!$editData) {
            echo "<div class='alert alert-danger'>Payout record not found.</div>";
            exit;
        }

        // Extract the HQ ID from the retrieved data for the View to use
        $edit_hq_id = $editData['hq_id']; 
        $isEditMode = true;
        
        // We still need states for the view header
        $states = $this->commissionModel->getStates(); 
        
        include 'view/commission/mr_com.php';
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
    public function claim_mrc() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['admin_id'])) exit;

        $hq_id = isset($_POST['hq_id']) ? (int)$_POST['hq_id'] : 0;
        $bill_ids = isset($_POST['bill_ids']) ? $_POST['bill_ids'] : '[]';
        $adjustments = isset($_POST['adjustments']) ? $_POST['adjustments'] : '[]';
        $final_payout = isset($_POST['final_payout']) ? (float)$_POST['final_payout'] : 0.00;

        $result = $this->commissionModel->claimMrCommission($hq_id, $bill_ids, $adjustments, $final_payout);
        echo json_encode($result);
    }

    // ==========================================
    // 5. AJAX: Update Existing Payout
    // ==========================================
    public function update_mrc() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['admin_id'])) exit;

        $payout_id = isset($_POST['payout_id']) ? (int)$_POST['payout_id'] : 0;
        $hq_id = isset($_POST['hq_id']) ? (int)$_POST['hq_id'] : 0;
        $bill_ids = isset($_POST['bill_ids']) ? $_POST['bill_ids'] : '[]';
        $adjustments = isset($_POST['adjustments']) ? $_POST['adjustments'] : '[]';
        $final_payout = isset($_POST['final_payout']) ? (float)$_POST['final_payout'] : 0.00;

        $result = $this->commissionModel->updateMrCommission($payout_id, $hq_id, $bill_ids, $adjustments, $final_payout);
        echo json_encode($result);
    }

    // ==========================================
    // 6. HISTORY VIEWS (Datatable)
    // ==========================================
    public function mrc_history() {
        if (!isset($_SESSION['admin_id'])) { header('Location: index'); exit; }
        $states = $this->commissionModel->getStates();
        include 'view/commission/mr_com_list.php'; // Update path if needed
    }

    public function get_mrc_history() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['admin_id'])) exit;

        $hq_id = isset($_GET['hqId']) ? (int)$_GET['hqId'] : 0;
        $month = isset($_GET['month']) ? trim($_GET['month']) : '';

        $data = $this->commissionModel->getMrCommissionHistory($hq_id, $month);
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'No payouts found.']);
        }
    }
}
?>