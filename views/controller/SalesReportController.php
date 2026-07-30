<?php
require_once "modals/SalesReportModel.php";
class SalesReportController
{
    private $model;

    public function __construct()
    {
        $this->model = new SalesReportModel();
    }

    public function index()
    {
        $mr_id = $_SESSION['mr_id'];

        $data['stockists'] = $this->model->getStockists($mr_id);

        include 'view/report/SalesReport.php';
    }

    public function getReport()
    {
        header('Content-Type: application/json');

        $mr_id       = $_SESSION['mr_id'];
        $stockist_id = intval($_GET['stockist_id'] ?? 0);
        $customer_id = intval($_GET['customer_id'] ?? 0);
        $from_date   = $_GET['from_date'] ?? '';
        $to_date     = $_GET['to_date'] ?? '';

        echo json_encode(
            $this->model->getReport(
                $mr_id,
                $stockist_id,
                $customer_id,
                $from_date,
                $to_date
            )
        );
    }

    public function getSalesReport()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['mr_id']))
        {
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized Access'
            ]);
            exit;
        }

        $mr_id      = (int)$_SESSION['mr_id'];
        $start_date = trim($_GET['start_date'] ?? '');
        $end_date   = trim($_GET['end_date'] ?? '');

        if (empty($start_date) || empty($end_date))
        {
            echo json_encode([
                'success' => false,
                'message' => 'Date range required'
            ]);
            exit;
        }

        echo json_encode(
            $this->model->getSalesReport(
                $mr_id,
                $start_date,
                $end_date
            )
        );
    }

    public function pdfSalesReport()
    {
        if (!isset($_SESSION['mr_id'])) {
            exit("Unauthorized");
        }

        $mr_id      = (int)$_SESSION['mr_id'];
        $start_date = $_GET['start_date'] ?? '';
        $end_date   = $_GET['end_date'] ?? '';
        $customer   = trim($_GET['customer'] ?? '');

        if ($start_date == '' || $end_date == '') {
            exit("Invalid Date Range");
        }

        require_once __DIR__ . '/../config/fpdf/fpdf.php';

        $mr     = $this->model->getMR($mr_id);
        $result = $this->model->getSalesReportPDF(
            $mr_id,
            $start_date,
            $end_date,
            $customer
        );

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();

        $logo = __DIR__ . '/../config/img/logo.jpg';

        if (file_exists($logo)) {
            $pdf->Image($logo,165,8,30);
        }

        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(190,10,'SALES REPORT',0,1,'C');

        $pdf->Ln(5);

        $pdf->SetFont('Arial','',11);

        $pdf->Cell(40,8,'HQ Name');
        $pdf->Cell(80,8,': '.$mr['hq_name']);
        $pdf->Ln();

        $pdf->Cell(40,8,'MR Name');
        $pdf->Cell(80,8,': '.$mr['mr_name']);
        $pdf->Ln();

        $pdf->Cell(40,8,'Start Date');
        $pdf->Cell(80,8,': '.date('d-m-Y',strtotime($start_date)));
        $pdf->Ln();

        $pdf->Cell(40,8,'End Date');
        $pdf->Cell(80,8,': '.date('d-m-Y',strtotime($end_date)));
        $pdf->Ln(10);

        $pdf->SetFont('Arial','B',11);

        $pdf->Cell(15,8,'#',1,0,'C');
        $pdf->Cell(95,8,'Customer',1,0,'C');
        $pdf->Cell(35,8,'Sales',1,0,'C');
        $pdf->Cell(45,8,'Amount',1,1,'C');

        $pdf->SetFont('Arial','',10);

        $i = 1;
        $grand = 0;

        while($row = mysqli_fetch_assoc($result))
        {
            $grand += $row['total_amt'];

            $pdf->Cell(15,8,$i++,1,0,'C');
            $pdf->Cell(95,8,$row['customer_name'],1);
            $pdf->Cell(35,8,$row['total_sales'],1,0,'C');
            $pdf->Cell(45,8,number_format($row['total_amt'],2),1,1,'R');
        }

        $pdf->SetFont('Arial','B',11);

        $pdf->Cell(145,9,'Grand Total',1,0,'R');
        $pdf->Cell(45,9,number_format($grand,2),1,1,'R');

        $pdf->Output('D','Sales_Report.pdf');
        exit;
    }
}