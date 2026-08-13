<?php

include 'modals/Order_mdl.php';

class OrderController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new OrderModel($con);
    }

   public function index()
    {
        // 1. Capture the filter data from the URL
        $filters = [
            'state_id'   => $_GET['state_id'] ?? '',
            'hq_id'      => $_GET['hq_id'] ?? '',
            'order_date' => $_GET['order_date'] ?? ''
        ];

        // 2. Fetch dropdown lists
        $states = $this->model->getStates();
        $hq  = $this->model->getHQ();
        $getStockist = $this->model->getStockist();
        
        // 3. Pass the filters INTO the getOrders method!
        $orders = $this->model->getOrders($filters);
        
        // Load the order list view
        include 'view/Order/index.php';
    }

       

    public function approve($order_id)
    {
        // Fetch master order data & associated stockist/MR info
        $ROW = $this->model->getOrderById($order_id);
        $Products = $this->model->getProductsBySuperStockist($ROW['super_stockist_id']);
        // Fetch order line items pre-populated
        $ROW_DETAILS = $this->model->getOrderDetails($order_id);

        include 'view/Order/approve.php';
    }

    public function Approved()
    {
        $order_id          = $_POST['order_id'] ?? null;
        $stockist_id       = $_POST['stockist_id'] ?? null;
        $super_stockist_id = $_POST['super_stockist_id'] ?? null;

        // Handle other charges safely
        $other_sign     = $_POST['other_charges_sign'] ?? '+';
        $other_val      = (float)($_POST['other_charges'] ?? 0);
        $other_charges  = ($other_sign === '-') ? -$other_val : $other_val;

        // ---> FIX: Fully mapped $data array <---
        $data = [
            'order_id'          => $order_id,
            'stockist_id'       => $stockist_id,
            'super_stockist_id' => $super_stockist_id,
            
            // 1. Array Data for Items (Now perfectly mapped to $_POST)
            'detail_id'         => $_POST['detail_id'] ?? [],
            'product_id'        => $_POST['product_id'] ?? [],
            'approved_qty'      => $_POST['approved_qty'] ?? [],
            'batch_id'          => $_POST['batch_id'] ?? [],
            'batch'             => $_POST['batch'] ?? [],
            'rate'              => $_POST['rate'] ?? [],
            'amount'            => $_POST['amount'] ?? [],
            'mrp'               => $_POST['mrp'] ?? [],    // Added missing MRP
            'tax'               => $_POST['tax'] ?? [],    // Added missing Tax
            'disc'              => $_POST['disc'] ?? [],   // Added missing Discount

            // 2. Transport Data
            'lr_no'             => $_POST['lr_no'] ?? '',
            'eway_bill_no'      => $_POST['eway_bill'] ?? '',
            'vehicle_no'        => $_POST['vehicle_no'] ?? '',
            'transport_name'    => $_POST['transport_name'] ?? '',
            'credit_days'       => (int)($_POST['credit_days'] ?? 0),
            'remarks'           => $_POST['remarks'] ?? '',

            // 3. Financial Data
            'total_qty'         => (float)($_POST['total_qty'] ?? 0),
            'header_discount'   => (float)($_POST['header_discount'] ?? 0),
            'gst_amt'           => (float)($_POST['gst_amt'] ?? 0),
            'cgst'              => (float)($_POST['cgst'] ?? 0),
            'sgst'              => (float)($_POST['sgst'] ?? 0),
            'igst'              => (float)($_POST['igst'] ?? 0),
            'other_charges'     => $other_charges,
            'grand_total'       => (float)($_POST['grand_total'] ?? 0),
            'cd_percent'        => (float)($_POST['cd_percent'] ?? 0)

        ];

        // ===============================================
        // SMART ROUTING: Auto-Detect Insert vs Update
        // ===============================================
        $orderInfo = $this->model->getOrderById($order_id);
        
        if ($orderInfo && ($orderInfo['status'] === 'Approved' || $orderInfo['status'] === 'Processed')) {
            // ORDER ALREADY EXISTS: Run the Update & Replace logic
            $result = $this->model->updateApprovedOrder($data);
            if ($result['success']) {
                header("Location: " . BASE_URL . "Order/approve/" . $order_id . "?update=1");
                exit;
            }
        } else {
            // BRAND NEW APPROVAL: Run the Insert logic
            $result = $this->model->processOrderApproval($data);
            if ($result['success']) {
                header("Location: " . BASE_URL . "Order?success=1");
                exit;
            }
        }

        // Output error if either fails
        header("Location: " . BASE_URL . "Order/approve/$order_id?error=" . urlencode($result['msg']));
        exit;
    }
  

    // 3. DISPATCH ORDER
    public function Dispatch($order_id)
    {
        // Update status to 'Processed' or 'Dispatched'
        $result = $this->model->dispatchOrder($order_id);
        
        if ($result['success']) {
            header("Location: " . BASE_URL . "Order?dispatch=1");
        } else {
            header("Location: " . BASE_URL . "Order/approve/$order_id?error=Failed to dispatch");
        }
    }

    
   public function getBatchesByProduct()
    {
        if (!isset($_POST['super_stockist_id']) || !isset($_POST['product_id'])) {
            echo json_encode([]);
            exit;
        }

        $super_stockist_id = (int)$_POST['super_stockist_id'];
        $product_id        = (int)$_POST['product_id'];
        // Capture the currently editing order ID (defaults to 0 if not set)
        $current_order_id  = isset($_POST['current_order_id']) ? (int)$_POST['current_order_id'] : 0;

        // Pass the third parameter
        $batches = $this->model->getBatchesByStockistAndProduct(
            $super_stockist_id,
            $product_id,
            $current_order_id
        );

        header('Content-Type: application/json');
        echo json_encode($batches);
        exit;
    }

    public function Reject($order_id)
    {
        if (empty($order_id)) {
            header("Location: " . BASE_URL . "Order?error=Invalid Order ID");
            exit;
        }

        $result = $this->model->rejectOrder($order_id);
        
        if ($result['success']) {
            // Redirect back to the list with a success message
            header("Location: " . BASE_URL . "Order?error=Order Rejected Successfully");
            exit;
        } else {
            header("Location: " . BASE_URL . "Order/approve/$order_id?error=" . urlencode($result['msg']));
            exit;
        }
    }

   

}