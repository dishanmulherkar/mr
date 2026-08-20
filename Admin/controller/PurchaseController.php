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

    public function getBatches()
    {
        if (!isset($_POST['product_id'])) {
            die('Invalid request');
        }

        $product_id = (int)$_POST['product_id'];
        $batches = $this->model->getBatchesByProduct($product_id);
        
        $html = '<option value="">Select Batch</option>';
        $html .= '<option value="__new__" data-action="new"><strong>+ Add New Batch</strong></option>';

        if (mysqli_num_rows($batches) > 0) {
            while ($row = mysqli_fetch_assoc($batches)) {
                // Format expiry for display
                $expiry_display = '';
                if (!empty($row['expiry_date']) && $row['expiry_date'] !== '0000-00-00') {
                    $dt = DateTime::createFromFormat('Y-m-d', $row['expiry_date']);
                    if ($dt) {
                        $expiry_display = $dt->format('m/Y');
                    }
                }

                $html .= '<option 
                    value="' . $row['batch_no'] . '"
                    data-batch-id="' . $row['batch_id'] . '"
                    data-mrp="' . $row['mrp'] . '"
                    data-prate="' . $row['purchase_rate'] . '"
                    data-ptax="' . $row['purchase_tax'] . '"
                    data-srate="' . $row['sale_rate'] . '"
                    data-stax="' . $row['sale_tax'] . '"
                    data-expiry="' . $expiry_display . '"
                >' . $row['batch_no'] . ' (Exp: ' . $expiry_display . ')' . '</option>';
            }
        }

        echo $html;
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

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($_POST['product_id']) || empty($_POST['purchase_id'])) {
                header("Location: " . BASE_URL . "purchase/list?error=1");
                exit;
            }

            $result = $this->model->update($_POST);

            if ($result['success'] === true) {
                header("Location: " . BASE_URL . "purchase/list?success=1");
                exit;
            } else {
                echo "<h3>Debug Error:</h3>";
                die("<pre>" . $result['message'] . "</pre>"); 
            }
        }
    }
}