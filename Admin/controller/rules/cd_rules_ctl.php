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
        $query = $this->model->getAll();
        $super_stockists = $this->model->getSuperStockist();
        include 'view/Rules/cd_views.php';
    }

    public function edit($id)
    {
        $ROW = $this->model->getByStockistId($id);
        $query = $this->model->getAll();
        $super_stockists = $this->model->getSuperStockist();
        include 'view/Rules/cd_views.php';
    }

    public function store()
    {
        $data = [
            'super_stockist_id' => (int)$_POST['super_stockist_id'],
            'cd_4_percent_days' => (int)$_POST['cd_4_percent_days'],
            'cd_2_percent_days' => (int)$_POST['cd_2_percent_days'],
            'order_prefix'      => $_POST['order_prefix'],
            'tally_start_no'    => (int)$_POST['tally_start_no'],
            'fy_start_month'    => (int)$_POST['fy_start_month'],
            'order_sequence'    => (int)$_POST['order_sequence']
        ];

        $result = $this->model->saveRule($data);

        if ($result) {
            header("Location: " . BASE_URL . "cd_rules?success=1");
        } else {
            header("Location: " . BASE_URL . "cd_rules?error=1");
        }
        exit;
    }

    // Optional: AJAX endpoint for auto-filling the form when changing the dropdown on "Add" page
    public function get_settings_ajax()
    {
        $id = (int)$_GET['id'];
        $data = $this->model->getByStockistId($id);
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
}
?>