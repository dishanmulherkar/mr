<?php
// Include the model file
require_once 'modals/DashboardModel.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
class DashboardController {
    private $db;
    private $model;

    public function __construct($con) {
        $this->db = $con;
        $this->model = new DashboardModel($this->db);
    }

    public function index() {
        $TotalStockists = $this->model->TotalStockists();
        $TotalCustomers = $this->model->TotalCustomers();
        $TotalStates = $this->model->TotalStates();
        $TotalProducts = $this->model->TotalProducts();
        $TotalHeadQuarters = $this->model->TotalHeadQuarters();
        // print_r($TotalProducts);
        // exit;

       
        include 'view/dashboard/index.php'; 
    }
        
}
?>