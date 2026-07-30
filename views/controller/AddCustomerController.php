<?php

require_once 'modals/AddCustomerModel.php';

class AddCustomerController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new AddCustomerModel($con);
    }

    public function index()
    {
        $mr_id = $_SESSION['mr_id'];

        $customers = $this->model->getCustomers($mr_id);
        $states    = $this->model->getStates();

        $districts = null;

    if (!empty($customer['state'])) {
        $districts = $this->model->getByState($customer['state']);
    }


        include 'view/customer/AddCustomer.php';
    }

    public function edit($id)
    {
        $mr_id = $_SESSION['mr_id'];

        $customer = $this->model->getCustomerById($id, $mr_id);

        if (!$customer) {
            header("Location: " . BASE_URL . "AddCustomer");
            exit;
        }

        $states = $this->model->getStates();
        $districts = $this->model->getByState($customer['state']);

        include 'view/customer/AddCustomer.php';
    }

    public function store()
    {
        $data = [];

        // $district      = mysqli_real_escape_string($this->con, $data['district']);

        $data['mr_id']           = $_SESSION['mr_id'];
        $data['customer_name']   = trim($_POST['customer_name']);
        $data['customer_type']   = trim($_POST['customer_type']);
        $data['qualification']   = trim($_POST['qualification']);
        $data['mobile']          = trim($_POST['mobile']);
        $data['email']           = trim($_POST['email']);
        $data['address']         = trim($_POST['address']);
        $data['state']           = (int)$_POST['state'];
        $data['district']        = trim($_POST['district']);
        $data['pincode']         = trim($_POST['pincode']);

        $image = "";

        if (!empty($_FILES['customer_img']['name'])) {

            $ext = strtolower(pathinfo($_FILES['customer_img']['name'], PATHINFO_EXTENSION));

            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowed)) {

                $image = time() . "_" . rand(1000,9999) . "." . $ext;

                $upload_dir = "../Admin/uploads/customers/";

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                move_uploaded_file(
                    $_FILES['customer_img']['tmp_name'],
                    $upload_dir . $image
                );
            }
        }


        $data['customer_img'] = $image;

        if ($this->model->store($data)) {

            $_SESSION['success'] = "Customer added successfully.";

        } else {

            $_SESSION['error'] = "Failed to add customer.";

        }

        header("Location: " . BASE_URL . "customer");
        exit;
    }

    public function getDistricts()
    {
        // Receive state_id and the district name to be selected (if in edit mode)
        $state_id = $_POST['state_id'];
        $selected_district = isset($_POST['selected_district']) ? $_POST['selected_district'] : '';

        $districts = $this->model->getByState($state_id);

        echo '<option value="">Select District</option>';

        while($row = mysqli_fetch_assoc($districts))
        {
            // Check if this row matches the saved district name
            $selected = ($row['district_name'] == $selected_district) ? 'selected' : '';

            echo '<option value="'.$row['district_name'].'" '.$selected.'>';
            echo $row['district_name'];
            echo '</option>';
        }
    }

        public function update($id)
    {
        $customer = $this->model->getCustomerById($id);

        if(!$customer)
        {
            header("Location: ".BASE_URL."Customer");
            exit;
        }

        $data = [];

        $data['customer_name'] = trim($_POST['customer_name']);
        $data['customer_type'] = trim($_POST['customer_type']);
        $data['qualification'] = trim($_POST['qualification']);
        $data['mobile']        = trim($_POST['mobile']);
        $data['email']         = trim($_POST['email']);
        $data['address']       = trim($_POST['address']);
        $data['state']         = (int)$_POST['state'];
        $data['district']      = trim($_POST['district']);
        $data['pincode']       = trim($_POST['pincode']);

        $data['customer_img'] = "";

        if(isset($_FILES['customer_img']) && $_FILES['customer_img']['error']==0)
        {
            $allowed = ['jpg','jpeg','png','webp'];

            $ext = strtolower(pathinfo($_FILES['customer_img']['name'],PATHINFO_EXTENSION));

            if(in_array($ext,$allowed))
            {
                $filename = time()."_".uniqid().".".$ext;

                $uploadDir = "../Admin/uploads/customers/";

                if(!is_dir($uploadDir))
                {
                    mkdir($uploadDir,0777,true);
                }

                if(move_uploaded_file($_FILES['customer_img']['tmp_name'],$uploadDir.$filename))
                {
                    if(!empty($customer['customer_img']))
                    {
                        $old = $uploadDir.$customer['customer_img'];

                        if(file_exists($old))
                        {
                            unlink($old);
                        }
                    }

                    $data['customer_img'] = $filename;
                }
            }
        }

        if ($this->model->update($id, $data)) {

            $_SESSION['success'] = "Customer updated successfully.";

            header("Location: " . BASE_URL . "customer");
            exit;
        }


        header("Location: ".BASE_URL."AddCustomer/edit/".$id."?error=1");
        exit;
    }
}