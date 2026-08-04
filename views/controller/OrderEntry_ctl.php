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
}