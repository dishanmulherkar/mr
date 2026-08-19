<?php

// Include the model
require_once 'modals/Payment_manage_mdl.php';

class payment_manage_ctl {
    private $con;
    private $model;

    public function __construct($con) {
        $this->con = $con;
        // Initialize the model
        $this->model = new Payment_model($this->con);
    }

    // Default action: Show the payment list
    public function index($id = null) {
        // Fetch all payments from the model
        if (!isset($_SESSION['mr_id'])) {
            header('Location: index');
            exit;
        }

        $mr_id = $_SESSION['mr_id'];
        $payments = $this->model->getAllPayments();
        $stockists = $this->model->getStockists($mr_id);
        // Define active menu for sidebar highlighting
        $current_page = 'view_payments';

        // Load the view (adjust the path to match your actual view folder structure)
        include 'view/payment/view_payments.php';
    }

    // Action: Show the payment entry form
    public function entry($id = null) {
         if (!isset($_SESSION['mr_id'])) {
            header('Location: index');
            exit;
        }

        $mr_id = $_SESSION['mr_id'];
        $payments = $this->model->getAllPayments();
        $stockists = $this->model->getStockists($mr_id);
        
        // Define active menu
        $current_page = 'payment_entry';

        // Load the view
        include 'view/payment/payment_entry.php';
    }

    // Action: Handle form submission for a new payment
    public function save($id = null) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Pass $_POST data and $_FILES to the model
            $result = $this->model->addPayment($_POST, $_FILES);

            if ($result['success']) {
                // Set the success message in the session
                $_SESSION['success_msg'] = "Payment submitted successfully. It will reflect in your ledger once the Admin approves it.";
                header("Location: " . BASE_URL . "payment");
                exit;
            } else {
                // Set the error message in the session (using the message from your model)
                $_SESSION['error_msg'] = $result['msg'];
                // Redirect back to the entry form
                header("Location: " . BASE_URL . "payment/entry");
                exit;
            }
        } else {
            // Direct access not allowed, push back to index
            header("Location: " . BASE_URL . "payment");
            exit;
        }
    }

    // Action: Return JSON data for the AJAX table
    public function fetch_list() {
        header('Content-Type: application/json');
        
        try {
            // Get filters from the AJAX request
            $mr_id = isset($_GET['mr_id']) ? (int)$_GET['mr_id'] : 0;
            $stockist_id = isset($_GET['stockist_id']) ? (int)$_GET['stockist_id'] : 0;
            $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
            
            // NEW: Capture view_mode (Defaults to 'bills')
            $view_mode = isset($_GET['view_mode']) ? $_GET['view_mode'] : 'bills';

            // Fetch data based on the view mode
            if ($view_mode === 'payments') {
                // Fetch submitted payments waiting for approval
                $payments = $this->model->getSubmittedPayments($mr_id, $stockist_id, $from_date);
            } else {
                // Fetch the ledger/bill list
                $payments = $this->model->getFilteredPayments($mr_id, $stockist_id, $from_date);
            }

            // Output success JSON
            echo json_encode(['success' => true, 'payments' => $payments]);
            exit;

        } catch (Exception $e) {
            // Output error JSON
            echo json_encode(['success' => false, 'msg' => 'Error: ' . $e->getMessage()]);
            exit;
        }
    }

    public function get_outstanding() 
    {
        // 1. Clear any HTML output
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json');
        
        // 2. Extract the ID based on your router's logic ($parts[2])
        $url = trim($_GET['url'] ?? '', '/');
        $parts = explode('/', $url);
        $stockist_id = isset($parts[2]) ? (int)$parts[2] : 0;
        
        if ($stockist_id > 0) {
            // NOTE: Make sure your controller has access to the model here!
            // Example: $outstanding = $this->paymentModel->getOutstandingBalance($stockist_id);
            
            // Assuming your controller automatically loads its model as $this->model:
            $outstanding = $this->model->getOutstandingBalance($stockist_id);
            
            echo json_encode([
                'success' => true,
                'outstanding' => $outstanding
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'msg' => 'Invalid Stockist ID'
            ]);
        }
        
        // 3. Stop the script so no footer/header HTML loads
        exit; 
    }
}
?>