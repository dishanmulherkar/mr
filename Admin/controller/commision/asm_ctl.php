<?php
include 'modals/commision/asm_mdl.php'; 

class asm_ctl {
    private $commissionModel;

    public function __construct() {
        $this->commissionModel = new asm_com_mdl();
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
        
        include 'view/commission/asm_entry.php'; // Update path if needed
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
        
        include 'view/commission/asm_entry.php';
    }

    // ==========================================
    // 3. AJAX: Fetch Bills (Used in Create Mode)
    // ==========================================
   public function fetch_mr_bills() 
{
    header('Content-Type: application/json');
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
        exit;
    }

    $hqId = isset($_GET['hqId']) ? (int)$_GET['hqId'] : 0;
    $month = isset($_GET['month']) ? $_GET['month'] : ''; 

    if ($hqId <= 0) {
        echo json_encode(['success' => false, 'msg' => 'ASM ID missing.']);
        exit;
    }
   
    try {
        $result = $this->commissionModel->getAdminMrBills($hqId, $month);
        
        // Check if the model returned an empty array or if the bills array inside it is empty
        if (empty($result) || empty($result['bills'])) {
            echo json_encode(['success' => false, 'msg' => 'No bills found.']);
            exit;
        }
        
        // Return both the bills data and the admin_id
        echo json_encode([
            'success' => true, 
            'admin_id' => $result['admin_id'],
            'data' => $result['bills']
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        exit;
    }
}

    // ==========================================
    // 4. AJAX: Save New Payout
    // ==========================================
    public function claim_mrc() 
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['admin_id'])) exit;

        $asm_id = isset($_POST['asm_id']) ? (int)$_POST['asm_id'] : 0;
        $bill_ids = isset($_POST['bill_ids']) ? $_POST['bill_ids'] : '[]';
        $adjustments = isset($_POST['adjustments']) ? $_POST['adjustments'] : '[]';
        $final_payout = isset($_POST['final_payout']) ? (float)$_POST['final_payout'] : 0.00;
        
        // Capture the status (defaults to 'Pending' if not set)
        $status = isset($_POST['status']) ? $_POST['status'] : 'Pending';

        $result = $this->commissionModel->claimAsmCommission($asm_id, $bill_ids, $adjustments, $final_payout, $status);
        echo json_encode($result);
    }

    // ==========================================
    // 5. AJAX: Update Existing Payout
    // ==========================================
    public function update_mrc() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['admin_id'])) exit;

        $payout_id = isset($_POST['payout_id']) ? (int)$_POST['payout_id'] : 0;
        $asm_id = isset($_POST['asm_id']) ? (int)$_POST['asm_id'] : 0;
        $bill_ids = isset($_POST['bill_ids']) ? $_POST['bill_ids'] : '[]';
        $adjustments = isset($_POST['adjustments']) ? $_POST['adjustments'] : '[]';
        $final_payout = isset($_POST['final_payout']) ? (float)$_POST['final_payout'] : 0.00;
        
        // Capture the status
        $status = isset($_POST['status']) ? $_POST['status'] : 'Pending';

        $result = $this->commissionModel->updateAsmCommission($payout_id, $asm_id, $bill_ids, $adjustments, $final_payout, $status);
        echo json_encode($result);
    }

    // ==========================================
    // 6. AJAX: Delete Payout (NEW)
    // ==========================================
    public function delete_mrc() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['admin_id'])) exit;

        $payout_id = isset($_POST['payout_id']) ? (int)$_POST['payout_id'] : 0;

        if ($payout_id <= 0) {
            echo json_encode(['success' => false, 'msg' => 'Invalid Payout ID.']);
            exit;
        }

        $result = $this->commissionModel->deleteAsmCommission($payout_id);
        echo json_encode($result);
    }

    // ==========================================
    // 7. HISTORY VIEWS (Datatable)
    // ==========================================
    public function asm_history() {
        if (!isset($_SESSION['admin_id'])) { header('Location: index'); exit; }
        $states = $this->commissionModel->getStates();
        include 'view/commission/asm_list.php'; // Update path if needed
    }

    public function get_asm_history() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['admin_id'])) exit;

        $asm_id = isset($_GET['asmId']) ? (int)$_GET['asmId'] : 0;
        $month = isset($_GET['month']) ? trim($_GET['month']) : '';

        $data = $this->commissionModel->getAsmCommissionHistory($asm_id, $month);
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
            
            // Note: Using 'mrc_' prefix so it doesn't conflict with the 'drc' page filters
            if (isset($_POST['state_id'])) {
                $_SESSION['asm_filter_state'] = $_POST['state_id'];
            }
            if (isset($_POST['asm_id'])) {
                $_SESSION['asm_filter_asm'] = $_POST['asm_id'];
            }
            if (isset($_POST['month'])) {
                $_SESSION['asm_filter_month'] = $_POST['month'];
            }
            
            echo json_encode(['status' => 'success']);
            exit;
    }

    public function getAsmByStateAjax()
    {
        if (isset($_POST['state_id'])) {
            $state_id = (int)$_POST['state_id'];
            $selected_hq = $_POST['selected_hq'] ?? '';

            $hq_result = $this->commissionModel->getAsmByState($state_id);

            echo '<option value="">Select ASM</option>';
            while ($row = mysqli_fetch_assoc($hq_result)) {
                $selected = ($row['admin_id'] == $selected_hq) ? 'selected' : '';
                echo '<option value="' . $row['admin_id'] . '" ' . $selected . '>' . htmlspecialchars($row['admin_name']) . '</option>';
            }
        }
    }
}
?>