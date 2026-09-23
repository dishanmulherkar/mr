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

        if ($ROW && !empty($ROW['hq_id'])) {
            $hqData = $this->model->getStateByHQ($ROW['hq_id']);

            if ($hqData) {
                $state_id = !empty($hqData['state_id']) ? $hqData['state_id'] : (!empty($hqData['state']) ? $hqData['state'] : '');
                $ROW['state_id'] = $state_id;
            }
        }
        
        $states = $this->model->getStates();
        $query  = $this->model->getAll();

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

    public function getMRs()
{
    $hq_id = isset($_POST['hq_id']) ? intval($_POST['hq_id']) : 0;
    $selected_id = isset($_POST['selected_id']) ? $_POST['selected_id'] : '';

    $mrs = $this->model->getMrByHq($hq_id);

    if (ob_get_length()) {
        ob_clean();
    }

    echo '<option value="">Select MR</option>';
    while ($row = mysqli_fetch_assoc($mrs)) 
    {
        $selected = ($row['m_id'] == $selected_id) ? 'selected' : '';
        echo '<option value="'.$row['m_id'].'" '.$selected.'>';
        echo htmlspecialchars($row['mr_name']);
        echo '</option>';
    }
    
    exit;
}
   
    
}