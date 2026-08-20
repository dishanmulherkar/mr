<?php
require_once "modals/Payment_mdl.php";

class PaymentApproval_ctl {
    private $model;

    public function __construct() {
        $this->model = new PaymentApproval_mdl();
    }

    // Load the View HTML
    public function index() {
        // Admin validation check here (ensure user is admin/sub-admin)
        if (!isset($_SESSION['admin_id'])) {
            header('Location: index');
            exit;
        }
         $states = $this->model->getStates();
        $current_page = 'PaymentApprovals';
        include 'view/payment/Approvel_list.php';
    }

    // AJAX Endpoint: Fetch the list of payments
    public function fetch_list() {
        header('Content-Type: application/json');
        
        $status_filter = $_GET['status'] ?? ''; // 'pending', 'approved', 'rejected', or empty for all
        $payments = $this->model->getPaymentsForAdmin($status_filter);
        
        echo json_encode(['success' => true, 'data' => $payments]);
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
        $action = isset($_POST['action']) ? $_POST['action'] : ''; // 'approved' or 'rejected'

        if ($payment_id <= 0 || !in_array($action, ['approved', 'rejected'])) {
            echo json_encode(['success' => false, 'msg' => 'Invalid request data.']);
            exit;
        }

        // Call the model to process
        $result = $this->model->processApproval($payment_id, $admin_id, $action);
        echo json_encode($result);
        exit;
    }
}
?>