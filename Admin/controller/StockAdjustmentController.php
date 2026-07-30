<?php

include 'modals/StockAdjustmentModel.php';

class StockAdjustmentController
{
    private $model;
    private $uploadDir;

    public function __construct($con)
    {
        $this->model = new StockAdjustmentModel($con);
        
        // Centralize the upload directory path using an absolute path
        $this->uploadDir = __DIR__ . "/../uploads/stock/";
    }

    public function index()
    {
        $ROW = null;

        
        $states  = $this->model->getStates();
         $products = $this->model->getProducts();
        $batches  = $this->model->getProductBatches();
        $batch_nos  = $this->model->getBatchNumbers();
        include 'view/inventory/stock_adjustment.php';
    }

    public function store()
    {
        if($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $result = $this->model->store($_POST);

            if($result['status'])
            {
                if($result['warning'])
                {
                    header("Location: ".BASE_URL."stock_adjustment?success=1&warning=1");
                }
                else
                {
                    header("Location: ".BASE_URL."stock_adjustment?success=1");
                }
            }
            else
            {
                header("Location: ".BASE_URL."stock_adjustment?error=1");
            }

            exit;
        }
    }
 
    public function getCurrentStock()
    {
        $stockist_id = isset($_POST['stockist_id']) ? (int)$_POST['stockist_id'] : 0;
        $p_id        = isset($_POST['p_id']) ? (int)$_POST['p_id'] : 0;
        $batch_id    = isset($_POST['batch_id']) ? (int)$_POST['batch_id'] : 0;

        $stock = $this->model->getCurrentStock($stockist_id, $p_id, $batch_id);

        header('Content-Type: application/json');

        echo json_encode([
            'qty' => $stock['current_qty'] ?? 0
        ]);

        exit;
    }
    public function getProductsByState()
    {
        $state_id = intval($_POST['state_id']);

        $products = $this->model->getProductsByState($state_id);

        include 'view/inventory/adj_product_rows.php';
    }

    public function getGlobalBatches()
{
    $state_id = intval($_POST['state_id']);

    $result = $this->model->getBatchNosByState($state_id);

    echo '<option value="">Select Batch</option>';

    while($row = mysqli_fetch_assoc($result))
    {
        echo '<option value="'.$row['batch_no'].'">'.$row['batch_no'].'</option>';
    }

    exit;
}
}