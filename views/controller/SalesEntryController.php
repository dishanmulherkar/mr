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

        $mr_id = isset($_GET['mr_id']) ? intval($_GET['mr_id']) : 0;
        $type  = isset($_GET['type']) ? trim($_GET['type']) : '';

        echo json_encode(
            $this->model->getCustomer($mr_id, $type)
        );
    }

    public function search_medicines()
    {
        header('Content-Type: application/json');


        $stockist_id = intval($_GET['stockist_id'] ?? 0);
        $q = trim($_GET['q'] ?? '');

        echo json_encode(
            $this->model->searchMedicine($stockist_id, $q)
        );
    }

   public function saveSale()
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
                'sale_date'   => $_POST['sale_date'] ?? date('Y-m-d'),
                'customer_id' => (int)($_POST['customer_id'] ?? 0),
                'stockist_id' => (int)($_POST['stockist_id'] ?? 0),
                'total_amt'   => (float)($_POST['total_amt'] ?? 0),
                'mrp_total'   => (float)($_POST['mrp_total'] ?? 0),
                'items'       => json_decode($_POST['items'] ?? '[]', true)

            ];

            echo json_encode(
                $this->model->saveSale($data)
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


}