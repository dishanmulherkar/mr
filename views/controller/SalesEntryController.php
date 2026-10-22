<?php

require_once "modals/SalesEntryModel.php";

class SalesEntryController
{
    private $model;

    public function __construct()
    {
        $this->model = new SalesModel();
    }

    public function index()
    {

        if (!isset($_SESSION['mr_id'])) {
            header('Location: index');
            exit;
        }

        $mr_id = $_SESSION['mr_id'];
        $stockists = $this->model->getStockists($mr_id);

        include 'view/SalesEntry/index.php';
    }

    public function get_customer()
    {
        header('Content-Type: application/json');
         $hq_id = $_SESSION['hq_id'];

        $mr_id = isset($_GET['mr_id']) ? intval($_GET['mr_id']) : 0;
        $type  = isset($_GET['type']) ? trim($_GET['type']) : '';

        echo json_encode(
            $this->model->getCustomer($hq_id, $type)
        );
    }

public function search_medicines()
{
    header('Content-Type: application/json; charset=utf-8');

    $stockist_id = (int)($_GET['stockist_id'] ?? 0);
    $q           = trim($_GET['q'] ?? '');

    $data = $this->model->searchMedicine($stockist_id, $q);

    if (isset($data['error'])) {
        http_response_code(500);
    }

    echo json_encode($data);
    exit;
}

  public function saveSale()
    {
        header('Content-Type: application/json');

        try {
            if (!isset($_SESSION['mr_id'])) {
                throw new Exception("Unauthorized");
            }

            $total_amt        = (float)($_POST['total_amt'] ?? 0);
            $discount_percent = (float)($_POST['discount_percent'] ?? 0);
            $discount_amt     = (float)($_POST['discount_amt'] ?? 0);
            $net_amt          = (float)($_POST['net_amt'] ?? ($total_amt - $discount_amt));

            $data = [
                'mr_id'            => $_SESSION['mr_id'],
                'sale_date'        => $_POST['sale_date'] ?? date('Y-m-d'),
                'customer_id'      => (int)($_POST['customer_id'] ?? 0),
                'stockist_id'      => (int)($_POST['stockist_id'] ?? 0),
                'total_amt'        => $total_amt,
                'discount_percent' => $discount_percent,
                'discount_amt'     => $discount_amt,
                'net_amt'          => $net_amt,
                'mrp_total'        => (float)($_POST['mrp_total'] ?? 0),
                'items'            => json_decode($_POST['items'] ?? '[]', true)
            ];

            echo json_encode(
                $this->model->saveSale($data)
            );
        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                'success' => false,
                'msg'     => $e->getMessage()
            ]);
        }
    }

    public function list() {
        if (!isset($_SESSION['mr_id'])) { header('Location: ' . BASE_URL); exit; }
        $mr_id = $_SESSION['mr_id'];
        
        // Assuming your model has a function to get stockists
        $stockists = $this->model->getStockists($mr_id); 
        
        include 'view/SalesEntry/saleslist.php';
    }

    // 2. AJAX Fetch List Data
    public function fetch_list() {
        header('Content-Type: application/json');
        
        $mr_id = isset($_GET['mr_id']) ? (int)$_GET['mr_id'] : 0;
        $stockist_id = isset($_GET['stockist_id']) ? (int)$_GET['stockist_id'] : 0;
        $sale_type = isset($_GET['sale_type']) ? $_GET['sale_type'] : '';
        $sale_date = isset($_GET['sale_date']) ? $_GET['sale_date'] : '';

        // Call the model to get the data
        $sales = $this->model->getFilteredSales($mr_id, $stockist_id, $sale_type, $sale_date);

        echo json_encode(['success' => true, 'sales' => $sales]);
        exit;
    }

// ----------------------------------------------------
// 1. DELETE SALE (AJAX POST)
// ----------------------------------------------------
public function delete($id = null)
{
    header('Content-Type: application/json');

    try {
        if (!isset($_SESSION['mr_id'])) {
            http_response_code(401);
            throw new Exception("Unauthorized");
        }

        $sale_id = (int)($id ?? $_POST['id'] ?? 0);
        if ($sale_id <= 0) {
            http_response_code(400);
            throw new Exception("Invalid sale entry ID.");
        }

        $result = $this->model->deleteSale($sale_id, (int)$_SESSION['mr_id']);
        echo json_encode($result);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'msg'     => $e->getMessage()
        ]);
    }
}

// ----------------------------------------------------
// 2. LOAD EDIT VIEW OR FETCH DATA (GET)
// ----------------------------------------------------
public function edit($id = null)
{
    if (!isset($_SESSION['mr_id'])) {
        header('Location: ' . BASE_URL . 'index');
        exit;
    }

    $mr_id = (int)$_SESSION['mr_id'];
    $sale_id = (int)($id ?? 0);

    if ($sale_id <= 0) {
        header('Location: ' . BASE_URL . 'SalesEntry');
        exit;
    }

    // Retrieve entry details to populate the edit form
    $sale = $this->model->getSaleForEdit($sale_id, $mr_id);
    if (!$sale) {
        header('Location: ' . BASE_URL . 'SalesEntry');
        exit;
    }

    // Load stockists for the stockist dropdown (same as index)
    $stockists = $this->model->getStockists($mr_id);

    // Render the unified view
    include 'view/SalesEntry/index.php';
}
// ----------------------------------------------------
// 3. UPDATE SALE (AJAX POST)
// ----------------------------------------------------
public function updateSale()
    {
        header('Content-Type: application/json');

        try {
            if (!isset($_SESSION['mr_id'])) {
                throw new Exception("Unauthorized");
            }

            $total_amt        = (float)($_POST['total_amt'] ?? 0);
            $discount_percent = (float)($_POST['discount_percent'] ?? 0);
            $discount_amt     = (float)($_POST['discount_amt'] ?? 0);
            $net_amt          = (float)($_POST['net_amt'] ?? ($total_amt - $discount_amt));

            $data = [
                's_id'             => (int)($_POST['s_id'] ?? 0),
                'mr_id'            => $_SESSION['mr_id'],
                'sale_date'        => $_POST['sale_date'] ?? date('Y-m-d'),
                'customer_id'      => (int)($_POST['customer_id'] ?? 0),
                'stockist_id'      => (int)($_POST['stockist_id'] ?? 0),
                'total_amt'        => $total_amt,
                'discount_percent' => $discount_percent,
                'discount_amt'     => $discount_amt,
                'net_amt'          => $net_amt,
                'mrp_total'        => (float)($_POST['mrp_total'] ?? 0),
                'items'            => json_decode($_POST['items'] ?? '[]', true)
            ];

            echo json_encode(
                $this->model->updateSale($data)
            );
        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                'success' => false,
                'msg'     => $e->getMessage()
            ]);
        }
    }

}