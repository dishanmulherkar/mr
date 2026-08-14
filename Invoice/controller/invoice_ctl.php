<?php

require_once __DIR__ . '/../model/Invoice_mdl.php';

class InvoiceController
{
    private $con;
    private $model;

    public function __construct($con)
    {
        $this->con = $con;
        $this->model = new InvoiceModel($con);
    }

    public function index($id = null)
    {
        if (!$id) {
            die('Invoice ID is required.');
        }

        // $this->pdf($id);
    }

    public function pdf($id)
    {
        $order_id = (int)$id;

        if (!$order_id) {
            die('Invalid order ID.');
        }

        $invoice = $this->model->getInvoice($order_id);

        if (!$invoice) {
            die('Invoice not found.');
        }

        /*
         * Dompdf
         */
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

        $options = new \Dompdf\Options();

        $options->set(
            'defaultFont',
            'DejaVu Sans'
        );

        $options->set(
            'isRemoteEnabled',
            true
        );

        $dompdf = new \Dompdf\Dompdf($options);

        /*
         * Load invoice HTML
         */
        ob_start();

        require __DIR__ . '/../view/invoice.php';

        $html = ob_get_clean();

        /*
         * Generate PDF
         */
       $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        /*
         * Display in browser
         */
        $invoice_no = $invoice['invoice_no']
            ?? ('INV-' . $order_id);

        $dompdf->stream(
            'Invoice-' . $invoice_no . '.pdf',
            [
                'Attachment' => false
            ]
        );
    }

    public function sales_pdf($id)
    {
        $sale_id = (int)$id;

        if (!$sale_id) {
            die('Invalid sale ID.');
        }

        // 1. Fetch Sales Invoice Data (You need to create this method in your Model)
        $invoice = $this->model->getSalesInvoice($sale_id);

        if (!$invoice) {
            die('Sales Invoice not found.');
        }

        /*
         * Dompdf Setup
         */
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);

        /*
         * Load sales invoice HTML
         */
        ob_start();
        
        // 2. Point to a new view specifically designed for Sales
        require __DIR__ . '/../view/sales_invoice.php';

        $html = ob_get_clean();

        /*
         * Generate PDF
         */
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        /*
         * Display in browser
         */
        $invoice_no = $invoice['invoice_no'] ?? ('SALE-' . $sale_id);

        $dompdf->stream(
            'Sales-Invoice-' . $invoice_no . '.pdf',
            [
                'Attachment' => false // Set to true if you want to force download instead of viewing
            ]
        );
    }

    public function sales_excel($id)
    {
        $sale_id = (int)$id;

        if (!$sale_id) {
            die('Invalid sale ID.');
        }

        // Fetch Sales Invoice Data
        $invoice = $this->model->getSalesInvoice($sale_id);

        if (!$invoice) {
            die('Sales Invoice not found.');
        }

        $invoice_no = $invoice['invoice_no'] ?? ('SALE-' . $sale_id);

        // Clean output buffer
        if (ob_get_length()) {
            ob_end_clean();
        }

        // Set headers to force download as an Excel file
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"Sales-Invoice-{$invoice_no}.xls\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        // Load the Excel-friendly HTML View
        require __DIR__ . '/../view/sales_invoice_excel.php';
        exit;
    }
}