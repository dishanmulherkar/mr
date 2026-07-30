<?php
include 'modals/SuperStockist_mdl.php';

class SupplierController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new SupplierModel($con);
    }

    public function index()
    {
        $ROW  = null;
        $list = $this->model->getAll();
        $states = $this->model->getStates();
        include 'view/superstockist/superstockist.php';
    }

    public function edit($id)
    {
        $ROW  = $this->model->getById($id);
        $list = $this->model->getAll();
        $states = $this->model->getStates();
         $districts = null;
        if (isset($ROW['state']) && !empty($ROW['state'])) {
            $districts = $this->model->getDistrictsByState($ROW['state']);
        }
        include 'view/superstockist/superstockist.php';
    }

    public function store()
    {
        if (mysqli_num_rows($this->model->checkDuplicate($_POST['ss_name'])) > 0) {
            header("Location: " . BASE_URL . "supplier?duplicate=1");
            exit;
        }
        $status = in_array($_POST['status'], ['1', '0']) ? $_POST['status'] : '1';      
         $districts = null;
        if (isset($ROW['state']) && !empty($ROW['state'])) {
            $districts = $this->model->getDistrictsByState($ROW['state']);
        }

        $success = $this->model->insert($_POST, $status);
        header("Location: " . BASE_URL . "supplier?success=created");
        exit;
    }

    public function update($id)
    {
        $status = in_array($_POST['status'], ['1', '0']) ? $_POST['status'] : '1';
        $success = $this->model->update($id, $_POST, $status);

        header("Location: " . BASE_URL . "supplier?success=updated");
        exit;
    }

    public function delete($id)
    {
        $this->model->delete($id);
        header("Location: " . BASE_URL . "supplier?deleted=1");
        exit;
    }
   
}