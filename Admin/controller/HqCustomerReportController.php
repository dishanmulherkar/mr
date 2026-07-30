<?php

include 'modals/HqCustomerReportModel.php';

class HqCustomerReportController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new HqCustomerReportModel($con);
    }

    public function index()
    {
        $state_id = isset($_GET['state']) ? (int)$_GET['state'] : 0;
        $hq_id    = isset($_GET['hq_id']) ? (int)$_GET['hq_id'] : 0;

        $start_date = $_GET['start_date'] ?? '';
        $end_date   = $_GET['end_date'] ?? '';

        $from_date = $start_date ?: date('Y-m-01');
        $to_date   = $end_date ?: date('Y-m-d');

        $states = $this->model->getStates();

        $hqs = null;
        if ($state_id > 0) {
            $hqs = $this->model->getHqByState($state_id);
        }

        $hq_name = '';
        if ($hq_id > 0) {
            $hq = $this->model->getHQName($hq_id);
            if ($hq) {
                $hq_name = $hq['hq_name'];
            }
        }

        $query = null;
        if ($hq_id > 0) {
            $query = $this->model->getReport($hq_id, $from_date, $to_date);
        }

        include 'view/report/hq_customer_report.php';
    }

    // Customer Detail Report
    public function customerReport()
    {
        $c_id = isset($_GET['c_id']) ? (int)$_GET['c_id'] : 0;

        $from_date = !empty($_GET['from_date'])
            ? $_GET['from_date']
            : date('Y-m-01');

        $to_date = !empty($_GET['to_date'])
            ? $_GET['to_date']
            : date('Y-m-d');

        $query = $this->model->getCustomerReport($c_id, $from_date, $to_date);

        $customer = $this->model->getCustomer($c_id);

        include 'view/report/hq_customer_det_rep.php';
    }
}
?>