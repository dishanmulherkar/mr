<?php

include 'modals/StockInwardModel.php';

class StockInwardController
{
    private $model;
    private $uploadDir;

    public function __construct($con)
    {
        $this->model = new StockInwardModel($con);
        
        // Centralize the upload directory path using an absolute path
        $this->uploadDir = __DIR__ . "/../uploads/stock/";
    }

    public function index()
    {
        $ROW = null;

        $hq_rows = $this->model->getHQ();
        $states  = $this->model->getStates();
         $products = $this->model->getProducts();
        $batches  = $this->model->getProductBatches();
        $batch_nos  = $this->model->getBatchNumbers();

        include 'view/inventory/stock_inward.php';
    }


        public function getHqByState()
        {
            $state_id = isset($_POST['state_id']) ? (int)$_POST['state_id'] : 0;

            $selected_ids = [];

            // Multiple HQs (Notification)
            if (!empty($_POST['selected_ids'])) {

                $selected_ids = array_map('trim', explode(',', $_POST['selected_ids']));
            }

            // Single HQ (Stock Inward)
            elseif (!empty($_POST['selected_id'])) {

                $selected_ids[] = trim($_POST['selected_id']);
            }

            $hq = $this->model->getHqByState($state_id);

            if (ob_get_length()) {
                ob_clean();
            }

            while ($row = mysqli_fetch_assoc($hq))
            {
                $selected = in_array((string)$row['m_id'], $selected_ids, true)
                    ? 'selected'
                    : '';

                echo '<option value="'.$row['m_id'].'" '.$selected.'>';
                echo htmlspecialchars($row['hq_name']);
                echo '</option>';
            }

            exit;
        }
   
   
    public function getStockists()
    {
        $hq_id = isset($_POST['hq_id']) ? intval($_POST['hq_id']) : 0;
        $selected_id = isset($_POST['selected_id']) ? $_POST['selected_id'] : '';

        // Fetch data from Model
        $stockists = $this->model->getStockistsByHq($hq_id);

        // Clear Output Buffer
        if (ob_get_length()) {
            ob_clean();
        }

        // Output HTML Options
        echo '<option value="">Select Stockist</option>';
        while($row = mysqli_fetch_assoc($stockists))
        {
            $selected = ($row['stockist_id'] == $selected_id) ? 'selected' : '';
            echo '<option value="'.$row['stockist_id'].'" '.$selected.'>';
            echo htmlspecialchars($row['stockist_name']);
            echo '</option>';
        }
        
        // Halt Execution
        exit;
    }
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $result = $this->model->store($_POST);

            if($result)
            {
                header("Location: " . BASE_URL . "stock_inward?success=1");
                exit;
            }
            else
            {
                header("Location: " . BASE_URL . "stock_inward?error=1");
                // exit;
                echo $result;
            }
        }
    }

        public function getProductsByState()
    {
        $state_id = intval($_POST['state_id']);

        $products = $this->model->getProductsByState($state_id);

         include 'view/inventory/product_rows.php';
    }

    public function getStateBatches()
    {
        $state_id = intval($_POST['state_id']);

        $result = $this->model->getStateBatches($state_id);

        echo '<option value="">Select Batch</option>';

        while($row = mysqli_fetch_assoc($result))
        {
            echo '<option value="'.$row['batch_no'].'">'.$row['batch_no'].'</option>';
        }

        exit;
    }
}