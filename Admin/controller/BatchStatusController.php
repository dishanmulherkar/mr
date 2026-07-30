<?php
include 'modals/batchStatusModel.php';

class BatchStatusController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new BatchStatusModel($con);
    }

    // --- Standard Controller Methods ---//

    public function index()
    {
        $ROW = null;
        $list = $this->model->getAll();
        $batchNumbers = $this->model->getBatchNumbers();
        include 'view/inventory/batch_status.php';
    }

   
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $result = $this->model->store($_POST);

            if($result)
            {
                header("Location: " . BASE_URL . "batch_status?success=1");
            }
            else
            {
                header("Location: " . BASE_URL . "batch_status?error=1");
            }

            exit;
        }
    }

   
}