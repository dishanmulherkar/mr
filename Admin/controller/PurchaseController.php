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
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $result = $this->model->store($_POST);

            if ($result === true)
            {
                header('Location: ' . BASE_URL . 'Purchase_inward?success=1');
                exit;
            }
            else
            {
                header('Location: ' . BASE_URL . 'Purchase_inward?error=1&msg=' . urlencode($result));
                exit;
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
}