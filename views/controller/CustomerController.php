<?php

require_once 'modals/CustomerModel.php';

class CustomerController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new CustomerModel($con);
    }

    public function index()
    {
        $mr_id = $_SESSION['mr_id'];
         $customers = $this->model->getCustomers($mr_id);
        include 'view/customer/index.php';
    }

    public function popup($customer_id)
    {
        $customer = $this->model->getCustomerById($customer_id);

        header('Content-Type: application/json');

        if($customer){
            echo json_encode([
                'status' => true,
                'data' => $customer
            ]);
        }else{
            echo json_encode([
                'status' => false,
                'message' => 'Customer not found'
            ]);
        }
    }
}