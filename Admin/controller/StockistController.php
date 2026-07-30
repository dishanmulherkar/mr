<?php

include 'modals/StockistModel.php';

class StockistController
{
    private $model;
    private $uploadDir;

    public function __construct($con)
    {
        $this->model = new StockistModel($con);
        
        // Centralize the upload directory path using an absolute path
        $this->uploadDir = __DIR__ . "/../uploads/stockist/";
    }

    public function index()
    {
        $ROW = null;

        $hq_rows = $this->model->getHQ();
        $states  = $this->model->getStates();
        $list    = $this->model->getAll();

        include 'view/stockist/stockist.php';
    }

    public function edit($id)
    {
        $ROW = $this->model->getById($id);

        $hq_rows = $this->model->getHQ();
        $states  = $this->model->getStates();
        $list    = $this->model->getAll();
        
        $districts = null;
        if (isset($ROW['state']) && !empty($ROW['state'])) {
            $districts = $this->model->getDistrictsByState($ROW['state']);
        }
        
        include 'view/stockist/stockist.php';
    }

    public function store()
    {
        $image = "";

        if (isset($_FILES['stockist_image']) && $_FILES['stockist_image']['error'] == 0) {
            
            // Check if the directory exists, if not, create it
            if (!is_dir($this->uploadDir)) {
                mkdir($this->uploadDir, 0755, true);
            }

            $image = time() . "_" . basename($_FILES['stockist_image']['name']);

            move_uploaded_file(
                $_FILES['stockist_image']['tmp_name'],
                $this->uploadDir . $image
            );
        }

        $check = $this->model->checkDuplicate($_POST['stockist_name']);

        if (mysqli_num_rows($check) > 0) {
            header("Location:" . BASE_URL . "stockist");
            exit;
        }

        $this->model->insert($_POST, $image);

        header("Location: " . BASE_URL . "stockist?success=1");
        exit;
    }

    public function update($id)
    {
        $ROW = $this->model->getById($id);

        $image = $ROW['stockist_image'];

        if (isset($_FILES['stockist_image']) && $_FILES['stockist_image']['error'] == 0) {
            
            // Check if the directory exists, if not, create it
            if (!is_dir($this->uploadDir)) {
                mkdir($this->uploadDir, 0755, true);
            }

            $image = time() . "_" . basename($_FILES['stockist_image']['name']);

            move_uploaded_file(
                $_FILES['stockist_image']['tmp_name'],
                $this->uploadDir . $image
            );
        }

        $check = $this->model->checkDuplicate($_POST['stockist_name'], $id);

        if (mysqli_num_rows($check) > 0) {
            header("Location:" . BASE_URL . "stockist/edit/" . $id);
            exit;
        }

        $this->model->update($id, $_POST, $image);

        header("Location: " . BASE_URL . "stockist?success=1");
        exit;
    }

    public function delete($id)
    {
        $image = $this->model->getImage($id);

        if (!empty($image['stockist_image'])) {
            // Use the centralized absolute path instead of a relative string
            $file = $this->uploadDir . $image['stockist_image'];

            if (file_exists($file)) {
                unlink($file);
            }
        }

        $this->model->delete($id);

        header("Location:" . BASE_URL . "stockist");
        exit;
    }
    public function downloadImages()
        {
            $this->model->downloadImages();
        }
}