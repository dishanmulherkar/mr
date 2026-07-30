<?php 
include 'modals/FinancialModel.php';
class FinancialController
{
   private $model;

    public function __construct($con)
    {
        $this->model = new FinancialModel($con);
    }

    public function index()
    {
        $ROW  = null;
       
        $query = $this->model->getAll();
        $states = $this->model->getStates();
        include 'view/FinancialYear/financial.php';
    }

     public function edit($id)
    {
        $ROW = $this->model->getById($id);

         $state_id = '';

        if($ROW)
        {
            $hq = $this->model->getStateByHQ($ROW['hq_id']);

            if($hq)
            {
                $state_id = $hq['state'];
            }
        }
        
        $states  = $this->model->getStates();
        $query = $this->model->getAll();

        include 'view/FinancialYear/financial.php';
    }

    public function store()
    {
        $result = $this->model->store($_POST);

        if($result === "duplicate")
        {
            header("Location: ".BASE_URL."financial?duplicate=1");
        }
        elseif($result)
        {
            header("Location: ".BASE_URL."financial?success=1");
        }
        else
        {
            header("Location: ".BASE_URL."financial?error=1");
        }

        exit;
    }
    
    public function update($id)
    {
        $result = $this->model->update($id,$_POST);

        if($result)
        {
            header("Location: ".BASE_URL."financial?updated=1");
        }
        else
        {
            header("Location: ".BASE_URL."financial?error=1");
        }

        exit;
    }

   
    
}