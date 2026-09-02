<?php
require_once "modals/payment/Payment_mdl.php";

class PaymentApproval_ctl {
    private $model;

    public function __construct() {
        $this->model = new PaymentApproval_mdl();
    }

    // Load the View HTML
    public function index() {
        if (!isset($_SESSION['admin_id'])) {
            header('Location: index');
            exit;
        }
        $states = $this->model->getStates();
        $current_page = 'PaymentApprovals';
        include 'view/payment/Approvel_list.php';
    }

    // Load Payment Entry View
   // Load Payment Entry View (UPDATED to handle Edit Mode)
    public function entry() {
        if (!isset($_SESSION['admin_id'])) {
            header('Location: index');
            exit;
        }
        $states = $this->model->getStates();
        $current_page = 'Payment Entry';

        // Check if we are in Edit/Reverse mode
        $edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
        $edit_data = null;
        if ($edit_id > 0) {
            $edit_data = $this->model->getPaymentById($edit_id);
        }

        include 'view/payment/payment_entry.php';
    }

    // ==========================================
    // NEW AJAX Endpoint: Reverse Manual Entry
    // ==========================================
    public function reverse_manual_entry() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
            exit;
        }

        $payment_id = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
        if ($payment_id <= 0) {
            echo json_encode(['success' => false, 'msg' => 'Invalid Payment ID']);
            exit;
        }

        $result = $this->model->reverseManualEntry($payment_id, $_SESSION['admin_id']);
        echo json_encode($result);
        exit;
    }

    // AJAX Endpoint: Fetch the list of payments
   public function fetch_list() {
    header('Content-Type: application/json');
    
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $state_id = isset($_GET['state_id']) ? $_GET['state_id'] : '';
    $hq_id = isset($_GET['hq_id']) ? $_GET['hq_id'] : '';

    $data = $this->model->getPaymentsForAdmin($status, $state_id, $hq_id);
    
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

    // AJAX Endpoint: Handle Approve/Reject action
    public function process() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
            exit;
        }

        $admin_id = $_SESSION['admin_id'];
        $payment_id = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
        $action = isset($_POST['action']) ? $_POST['action'] : ''; 

        if ($payment_id <= 0 || !in_array($action, ['approved', 'rejected'])) {
            echo json_encode(['success' => false, 'msg' => 'Invalid request data.']);
            exit;
        }

        $result = $this->model->processApproval($payment_id, $admin_id, $action);
        echo json_encode($result);
        exit;
    }

    // ==========================================
    // NEW AJAX ENDPOINTS FOR PAYMENT ENTRY
    // ==========================================

    public function get_mrs_by_hq() {
        header('Content-Type: application/json');
        $hq_id = isset($_GET['hq_id']) ? (int)$_GET['hq_id'] : 0;
        
        $mrs = $this->model->getMrsByHq($hq_id);
        if (empty($mrs)) {
            echo json_encode(['success' => false, 'msg' => 'No MR found']);
        } else {
            echo json_encode(['success' => true, 'data' => $mrs]);
        }
        exit;
    }

    public function get_stockists_by_hq() {
        header('Content-Type: application/json');
        $hq_id = isset($_GET['hq_id']) ? (int)$_GET['hq_id'] : 0;
        
        $stockists = $this->model->getStockistsByHq($hq_id);
        if (empty($stockists)) {
            echo json_encode(['success' => false, 'msg' => 'No Stockists found']);
        } else {
            echo json_encode(['success' => true, 'data' => $stockists]);
        }
        exit;
    }

    public function submit_manual_entry() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
            exit;
        }

        $admin_id = $_SESSION['admin_id'];
        $data = $_POST;
        
        // Basic validation
        if (empty($data['hq_id']) || empty($data['commission_type']) || empty($data['payment_type']) || empty($data['amount'])) {
            echo json_encode(['success' => false, 'msg' => 'Please fill in all required fields.']);
            exit;
        }

        $result = $this->model->submitManualEntry($data, $admin_id);
        echo json_encode($result);
        exit;
    }

    // ==========================================
    // NEW: AJAX Endpoint to fetch MR/HQ Balance
    // ==========================================
    public function get_balance() {
        header('Content-Type: application/json');
        $hq_id = isset($_GET['hq_id']) ? (int)$_GET['hq_id'] : 0;
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        
        if ($hq_id > 0 && in_array($type, ['MRC', 'DRC'])) {
            $balance = $this->model->getAvailableBalance($hq_id, $type);
            echo json_encode(['success' => true, 'balance' => $balance]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Invalid parameters']);
        }
        exit;
    }

   // ==========================================
    // NEW: Load Manual Payment History Page
    // ==========================================
    public function payment_list() {
        if (!isset($_SESSION['admin_id'])) {
            header('Location: index');
            exit;
        }
        $states = $this->model->getStates();
        $current_page = 'Manual Payment History';
        // Loads the new view file we are about to create
        include 'view/payment/payment_entry_list.php';
    }

    // AJAX Endpoint: Fetch the list of payments (UPDATED WITH DATE FILTERS)
    public function fetch_list_pay_entry() {
        header('Content-Type: application/json');
        
        $status_filter = $_GET['status'] ?? ''; 
        $type_filter = $_GET['type'] ?? ''; 
        
        if ($type_filter === 'manual') {
            // Grab all filters for manual list including dates
            $filters = [
                'state_id'   => $_GET['state_id'] ?? '',
                'hq_id'      => $_GET['hq_id'] ?? '',
                'comm_type'  => $_GET['comm_type'] ?? '',
                'start_date' => $_GET['start_date'] ?? '',
                'end_date'   => $_GET['end_date'] ?? ''
            ];
            $payments = $this->model->getManualPayments($filters);
        } else {
            // Standard approvals
            $payments = $this->model->getPaymentsForAdmin($status_filter);
        }
        
        echo json_encode(['success' => true, 'data' => $payments]);
        exit;
    }

      public function get_outstanding() {
    // Ensure clean JSON output without any PHP warnings breaking it
    ob_clean(); 
    header('Content-Type: application/json');
    
    $stockist_id = isset($_GET['stockist_id']) ? (int)$_GET['stockist_id'] : 0;
    
    // Capture the custom settlement date, default to today if missing
    $settlement_date = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : date('Y-m-d'); 
    
    if ($stockist_id > 0) {
        // Pass BOTH stockist_id and settlement_date to the model
        $data = $this->model->getStockistOutstandingWithCD($stockist_id, $settlement_date);
        
        // Encode and send all required keys to the frontend
        echo json_encode([
            'success'       => true, 
            'outstanding'   => $data['total_outstanding'],
            'eligible_cd'   => $data['eligible_cd'],      // Required by JS
            'total_penalty' => $data['total_penalty'],    // Required by JS (The CD Revocation)
            'net_payable'   => $data['net_payable'],
            'bills'         => $data['bill_details']      // The array of bills
        ]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Invalid Stockist ID']);
    }
    exit;
}

public function get_payment_allocations() {
        header('Content-Type: application/json');
        
        $payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
        
        if ($payment_id > 0) {
            $data = $this->model->getPaymentAllocations($payment_id);
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Invalid Payment ID']);
        }
        exit;
    }
}
?>