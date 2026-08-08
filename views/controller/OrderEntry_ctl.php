<?php

require_once "modals/OrderEntry_mdl.php";

class orderentry_ctl
{
    private $model;
    public function __construct()
    {
        $this->model = new orderentry_mdl();
    }

    public function index()
    {

        if (!isset($_SESSION['mr_id'])) {
            header('Location: index');
            exit;
        }

        $mr_id = $_SESSION['mr_id'];
        $stockists = $this->model->getStockists($mr_id);

        include 'view/Order/index.php';
    }

    public function edit($order_id = 0)
    {
        if (!isset($_SESSION['mr_id'])) {
            header('Location: index');
            exit;
        }

        $mr_id = $_SESSION['mr_id'];
        $stockists = $this->model->getStockists($mr_id);
        
        // Fetch existing order details to populate the edit form
        // (You will need to ensure getOrderById exists in your model)
        $order_data = $this->model->getOrderById($order_id, $mr_id);

        include 'view/Order/index.php';
    }



    public function search_medicines() 
    {
        // Ensure response type is always JSON
        header('Content-Type: application/json');

        $mr_id = $_SESSION['mr_id'] ?? 0;
        
        // Get super stockist ID from the model
        $super_stockist_id = $this->model->getSuperStockistIdByMr($mr_id);
       
        $q = $_GET['q'] ?? '';
        // Fetch medicines from model
        $medicines = $this->model->searchMedicine($super_stockist_id, $q);

        // Return JSON data
        echo json_encode($medicines);
        exit;
    }

   public function saveOrder()
    {
        header('Content-Type: application/json');

        try
        {
            if (!isset($_SESSION['mr_id']))
            {
                throw new Exception("Unauthorized");
            }

            $data = [
                'mr_id'       => $_SESSION['mr_id'],
                'order_date'  => $_POST['sale_date'] ?? date('Y-m-d'),
                'stockist_id' => (int)($_POST['stockist_id'] ?? 0),
                'total_amt'   => (float)($_POST['total_amt'] ?? 0),
                'items'       => json_decode($_POST['items'] ?? '[]', true)
            ];

            echo json_encode(
                $this->model->saveOrderRecord($data)
            );
        }
        catch (Exception $e)
        {
            http_response_code(500);

            echo json_encode([
                'success' => false,
                'msg' => $e->getMessage()
            ]);
        }
    }

    public function updateOrder()
{
    header('Content-Type: application/json');

    try {
        if (!isset($_SESSION['mr_id'])) {
            throw new Exception("Unauthorized");
        }

        $order_id = (int)($_POST['order_id'] ?? 0);
        if ($order_id <= 0) {
            throw new Exception("Missing order_id for update.");
        }

        $data = [
            'order_id'    => $order_id,
            'mr_id'       => $_SESSION['mr_id'],
            'order_date'  => $_POST['sale_date'] ?? date('Y-m-d'),
            'stockist_id' => (int)($_POST['stockist_id'] ?? 0),
            'total_amt'   => (float)($_POST['total_amt'] ?? 0),
            'items'       => json_decode($_POST['items'] ?? '[]', true)
        ];

        echo json_encode(
            $this->model->updateOrderRecord($data)
        );
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
}


public function view($id = 0)
{
    if (!isset($_SESSION['mr_id'])) {
        header('Location: index');
        exit;
    }

    $mr_id     = $_SESSION['mr_id'];
    $stockists = $this->model->getStockists($mr_id);

    include 'view/Order/order_list.php';
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

public function previewAllocation()
{
    // 1. Get POST variables
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $qty        = isset($_POST['qty']) ? (int)$_POST['qty'] : 0;
    
    // 2. Get mr_id from session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Make sure 'user_id' is the exact name of your session variable!
     $mr_id = $_SESSION['mr_id'] ?? 0;
    
    // 3. Get Super Stockist ID
    $super_stockist_id = $this->model->getSuperStockistIdByMr($mr_id);

    // Safety Check: If this is 0, the query will fail and return 0 stock. 
    if (empty($super_stockist_id)) {
        echo json_encode(['success' => false, 'msg' => 'Error: Could not find Super Stockist. Your session may have expired or MR ID is invalid.']);
        exit;
    }

    try {
        if ($product_id <= 0 || $qty <= 0) {
            throw new Exception("Invalid product or quantity.");
        }

        // Pass false for preview mode (so it doesn't lock rows in the DB)
        $data = $this->model->calculateBatchAllocation($super_stockist_id, $product_id, $qty, false);
        
        // Output clean JSON
        echo json_encode(['success' => true, 'data' => $data]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    
    // Stop further execution so HTML doesn't accidentally get appended
    exit; 
}
}