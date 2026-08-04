<?php

include 'modals/HQMaster_mdl.php';

class HeadquarterController
{
    private $model;
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
        $this->model = new HeadquarterModel($con);
    }

    // Display list and add form
    public function index()
    {
        $states = $this->model->getStates();
        $list = $this->model->getAllHeadquarters();
        $stockist = $this->model->getSuperStockist();
        $ROW = null; 

        include 'view/headquarter/index.php';
    }

    // Handle storing new record
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->model->store($_POST);

            if ($result['success'] === true) {
                header("Location: " . BASE_URL . "headquarter?success=1");
                exit;
            } else if (isset($result['duplicate']) && $result['duplicate'] === true) {
                header("Location: " . BASE_URL . "headquarter?duplicate=1");
                exit;
            } else {
                header("Location: " . BASE_URL . "headquarter?error=1");

                echo $result;
                print_r($_POST);
            
            }
        }
    }

    // Display edit form with preloaded data
    public function edit($id)
    {
        $states = $this->model->getStates();
        $list = $this->model->getAllHeadquarters();
        $ROW = $this->model->getHeadquarterById($id);
        $stockist = $this->model->getSuperStockist();


        if (!$ROW) {
            header("Location: " . BASE_URL . "headquarter?error=1");
            exit;
        }

        // Use $districts (plural) to match your working MR page code
        $districts = null;
        if (isset($ROW['state_id']) && !empty($ROW['state_id'])) {
            $districts = $this->model->getDistrictsByState($ROW['state_id']);
        }

        include 'view/headquarter/index.php';
    }
    // Handle update operation
    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST['headquarter_id'] = $id;
            $result = $this->model->update($_POST);

            if ($result['success'] === true) {
                header("Location: " . BASE_URL . "headquarter?success=1");
                exit;
            } else {
                header("Location: " . BASE_URL . "headquarter?error=1");
                exit;
            }
        }
    }

    // Handle delete operation
    public function delete($id)
    {
        if (!empty($id)) {
            $this->model->delete($id);
            header("Location: " . BASE_URL . "headquarter?deleted=1");
            exit;
        } else {
            header("Location: " . BASE_URL . "headquarter?error=1");
            exit;
        }
    }

    public function getHqByStateAjax()
    {
        if (isset($_POST['state_id'])) {
            $state_id = (int)$_POST['state_id'];
            $selected_hq = $_POST['selected_hq'] ?? '';

            $hq_result = $this->model->getHqByState($state_id);

            echo '<option value="">Select Head Quarter</option>';
            while ($row = mysqli_fetch_assoc($hq_result)) {
                $isSelected = ($selected_hq == $row['headquarter_id']) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($row['headquarter_id']) . '" ' . $isSelected . '>' . htmlspecialchars($row['hq_name']) . '</option>';
            }
        }
    }
}