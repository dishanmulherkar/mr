<?php

require_once __DIR__ . '/../model/invoice_mdl.php';

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

        $this->pdf($id);
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

        require __DIR__ . '/../View/invoice.php';

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
}