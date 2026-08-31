<?php 
include 'modals/bank/bank_mdl.php';
class bank_ctl
{
   private $model;

    public function __construct($con)
    {
        $this->model = new bank_mdl($con);
    }

    public function index()
    {
        $ROW = null;
        $list   = $this->model->getAll();
        include 'view/bank/banks.php';
    }

    // New: Fetch data to populate the form
    public function edit($id)
    {
        $ROW = mysqli_fetch_assoc($this->model->edit($id)); // Fetch the specific row
        $list   = $this->model->getAll();
         include 'view/bank/banks.php';
    }

    // New: Handle the form update
    public function update($id)
    {
        // Pass empty string for status since district table doesn't use it
        $this->model->update($id, $_POST, ''); 
        header('Location: ' . BASE_URL . 'bank?update=1');
        exit;
    }

    public function store()
    {
        // Pass empty string for status
        $this->model->insert($_POST, '');
        header('Location: ' . BASE_URL . 'bank?success=1');
        exit;
    }

    
}