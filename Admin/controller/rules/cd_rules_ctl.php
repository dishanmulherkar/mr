<?php 
include 'modals/Rules/cd_rules_mdl.php';

class cdrules_ctl
{
    private $model;

    public function __construct($con)
    {
        $this->model = new CDRulesModel($con);
    }

    public function index()
    {
        $ROW = null;
        
        // Get existing rules and list of stockists for the form
        $query = $this->model->getAll();
        $super_stockists = $this->model->getSuperStockist();
        
        include 'view/Rules/cd_views.php';
    }

    public function edit($id)
    {
        // Fetch specific rule by stockist ID
        $ROW = $this->model->getByStockistId($id);
        
        $query = $this->model->getAll();
        $super_stockists = $this->model->getSuperStockist();

        include 'view/Rules/cd_views.php';
    }

    public function store()
    {
        $data = [
            'super_stockist_id' => $_POST['super_stockist_id'],
            'cd_4_percent_days' => $_POST['cd_4_percent_days'],
            'cd_2_percent_days' => $_POST['cd_2_percent_days']
        ];

        // This handles both Insert and Update dynamically
        $this->model->saveRule($data);

        // Assuming BASE_URL is defined in your config
        header("Location: " . BASE_URL . "cd_rules");
        exit;
    }
}
?>