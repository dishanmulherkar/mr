<?php

include 'modals/CustomerModel.php';

class CustomerController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new CustomerModel($con);
    }

    public function index()
    {
        $ROW      = null;
        $states   = $this->model->getStates(); // Fetch states
        // $hq_list  = $this->model->getHqList();
        $list     = $this->model->getAll(); 
        $districts = null;

        include 'view/customer/index.php';
    }

    public function edit($id)
    {
        $ROW       = $this->model->getById($id);
        $states    = $this->model->getStates(); // Fetch states
        // $hq_list   = $this->model->getHqList();
        $list      = $this->model->getAll();
        
        // Fetch districts for the selected state if editing
        $districts = null;
        if (isset($ROW['state']) && !empty($ROW['state'])) {
            $districts = $this->model->getDistrictsByState($ROW['state']);
        }

        include 'view/customer/index.php';
    }

    public function delete($id)
    {
        // Remove image file if it exists before deleting the record
        $drow = $this->model->getById($id);
        if (!empty($drow['customer_img'])) {
            $img_path = __DIR__ . "/uploads/customers/" . $drow['customer_img'];
            if (file_exists($img_path)) unlink($img_path);
        }

        $this->model->delete($id);

        header("Location: /mr/customer");
        exit;
    }

    public function store()
    {
        // Check for duplicates
        if ($this->model->isDuplicate($_POST['customer_name'], $_POST['mobile'])) {
            header("Location: ".BASE_URL."customer?duplicate=1");
            exit;
        }

        // Handle Image Upload
        $image_name = '';
        if (isset($_FILES['customer_img']) && $_FILES['customer_img']['error'] == 0) {
            $image_name = $this->uploadImage($_FILES['customer_img']);
        }

        $this->model->insert($_POST, $image_name);

        header("Location: ".BASE_URL."customer?success=1");
        exit;
    }

    public function update($id)
    {
        // Check for duplicates (excluding current record)
        if ($this->model->isDuplicate($_POST['customer_name'], $_POST['mobile'], $id)) {
            header("Location: ".BASE_URL."customer?duplicate=1");
            exit;
        }

        // Handle Image Upload
        $image_name = isset($_POST['existing_image']) ? $_POST['existing_image'] : '';
        if (isset($_FILES['customer_img']) && $_FILES['customer_img']['error'] == 0) {
            $image_name = $this->uploadImage($_FILES['customer_img']);
        }

        $this->model->update($id, $_POST, $image_name);

        header("Location: ".BASE_URL."customer?success=1");
        exit;
    }

    // Helper method to keep upload logic clean
    private function uploadImage($file)
    {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
           $upload_dir = __DIR__ . "/../uploads/customers/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $image_name = time() . '_' . basename($file['name']);
            move_uploaded_file($file['tmp_name'], $upload_dir . $image_name);
            return $image_name;
        }
        return '';
    }
 public function getHQs()
    {
        $state_id = isset($_POST['state_id']) ? intval($_POST['state_id']) : 0;
        $selected_id = isset($_POST['selected_id']) ? $_POST['selected_id'] : '';

        $hqs = $this->model->getHqByState($state_id);

        if (ob_get_length()) {
            ob_clean();
        }

        echo '<option value="">Select Head Quarter</option>';
        while($row = mysqli_fetch_assoc($hqs))
        {
            $selected = ($row['headquarter_id'] == $selected_id) ? 'selected' : '';
            echo '<option value="'.$row['headquarter_id'].'" '.$selected.'>';
            echo htmlspecialchars($row['hq_name']);
            echo '</option>';
        }
        
        // REMOVED print_r($hqs);
        exit;
    }
    public function downloadImages()
    {
        $this->model->downloadImages();
    }
}
?>