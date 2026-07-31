<?php

include 'modals/PurchaseModel.php';

class PurchaseController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new PurchaseEntryModel($con);
    }

    public function index()
    {
        $ROW = null;

        $Products = $this->model->getProducts();
        $getStockist  = $this->model->getStockist();

        include 'view/purchase/purchase.php';
    }

    public function store() 
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Check if any products were added
            if (empty($_POST['product_id'])) {
                header("Location: " . BASE_URL . "purchase?error=1");
                exit;
            }

            // Call the model's store function
           $result = $this->model->store($_POST);

            if ($result['success'] === true) {
                header("Location: " . BASE_URL . "purchase?success=1");
                exit;
            } else {
                // Redirect back with error message (you can log $result['message'] if needed)

                echo "<h3>Debug Error:</h3>";
                die("<pre>" . $result['message'] . "</pre>");
                // header("Location: " . BASE_URL . "purchase?error=1");
                // exit;
            }
        }
    }

    public function getSuperStockist()
    {
        $selected_stockist = isset($_POST['selected_stockist']) ? $_POST['selected_stockist'] : '';
        $stockists = $this->model->getStockist();
        echo '<option value="">Select Super Stockist</option>';
        while ($row = mysqli_fetch_assoc($stockists))
        {
            $selected = ($row['super_stockist_id'] == $selected_stockist) ? 'selected' : '';
            echo '<option 
                value="'.$row['super_stockist_id'].'"
                data-state="'.$row['state_name'].'"
                '.$selected.'>
                '.$row['ss_name'].'
              </option>';
        }
    }

    public function list()
    {
        // Fetch the list of purchases from the model
        $purchases = $this->model->getPurchaseList();
        
        // Load the view file
        include 'view/purchase/purchase_list.php';
    }

    public function view($id)
    {
        $ROW = $this->model->getPurchaseById($id);
        if (!$ROW) {
            die("Purchase record not found.");
        }

        $ROW_DETAILS = $this->model->getPurchaseDetails($id);
        $Products = $this->model->getProducts();
        $getStockist = $this->model->getStockist();
        
        $is_view = true; // Locks inputs and hides submit button

        include 'view/purchase/purchase.php';
    }

    public function edit($id)
    {
        $ROW = $this->model->getPurchaseById($id);
        if (!$ROW) {
            die("Purchase record not found.");
        }

        $ROW_DETAILS = $this->model->getPurchaseDetails($id);
        $Products = $this->model->getProducts();
        $getStockist = $this->model->getStockist();
        
        $is_view = false; // Editable mode

        include 'view/purchase/purchase.php';
    }

    public function delete($id)
    {
        if (empty($id)) {
            header("Location: " . BASE_URL . "purchase/list?error=1");
            exit;
        }

        $result = $this->model->deletePurchase($id);

        if ($result['success'] === true) {
            header("Location: " . BASE_URL . "purchase/list?deleted=1");
            exit;
        } else {
            die("Error deleting purchase: " . $result['message']);
        }
    }
}