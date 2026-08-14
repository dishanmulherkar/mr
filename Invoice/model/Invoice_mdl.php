<?php

class InvoiceModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getInvoice($order_id)
    {
        $order_id = (int)$order_id;

        /*
         * 1. Fetch Main Stock Inward + Stockist + Super Stockist details
         */
        $sql = "
            SELECT
                si.*,
                si.inward_date AS inward_date, 
                si.inward_no AS invoice_no,
                o.order_date AS order_date,
                s.stockist_name,
                s.number AS stockist_phone,
                s.gst_no,
                s.pan_no,
                s.dl_no,
                s.address AS stockist_address,
                s.dispatch_to,
                s.transport,
                s.pincode,
                s.gst_type,

                st.state_name,
                d.district_name,
                
                ss.ss_name AS company_name,
                ss.address AS company_address,
                ss.gst_no AS company_gst,
                ss.district AS company_district,
                ss.state AS company_state,
                ss.pincode AS company_pincode,
                ss.term_and_condition

            FROM stock_inward si

            LEFT JOIN stockists s
                ON s.stockist_id = si.stockist_id

            LEFT JOIN state st
                ON st.state_id = s.state

            LEFT JOIN district d
                ON d.district_id = s.district
                
            LEFT JOIN super_stockist ss 
                ON ss.super_stockist_id = si.super_stockist_id
                
            INNER JOIN orders o
                ON o.order_id = si.order_id
                
            WHERE o.order_id = '$order_id'
            LIMIT 1
        ";

        $result = mysqli_query($this->con, $sql);

        if (!$result) {
            die(mysqli_error($this->con));
        }

        $invoice = mysqli_fetch_assoc($result);

        if (!$invoice) {
            return null;
        }

        $inward_id = (int)$invoice['inward_id'];

        /*
         * 2. Fetch Stock Inward details (Preserving the locked-in sid.mrp)
         */
        $detailsSql = "
            SELECT
                sid.*,

                p.product_name,
                p.hsn_code,

                pb.batch_no,
                pb.expiry_date
                -- Note: pb.mrp is intentionally omitted here to prevent overwriting sid.mrp

            FROM stock_inward_details sid

            LEFT JOIN products p
                ON p.p_id = sid.p_id

            LEFT JOIN product_batches pb
                ON pb.batch_no = sid.batch_id OR pb.batch_id = sid.batch_id 

            WHERE sid.inward_id = '$inward_id'

            ORDER BY sid.detail_id ASC 
        ";

        $detailsResult = mysqli_query($this->con, $detailsSql);

        if (!$detailsResult) {
            die(mysqli_error($this->con));
        }

        $invoice['items'] = [];

        while ($row = mysqli_fetch_assoc($detailsResult)) {
            $invoice['items'][] = $row;
        }

        return $invoice;
    }

    public function getSalesInvoice($sale_id)
    {
        $sale_id = (int)$sale_id;

        /*
         * 1. Fetch Main Sales Entry + Stockist details
         */
        $sql = "
            SELECT
                se.*,
                se.s_id AS invoice_no, 
                se.sale_date,
                s.stockist_name,
                s.number AS stockist_phone,
                s.address AS stockist_address,
                s.gst_no,
                c.customer_name,
                c.customer_type,
                c.address as customer_address,
                c.pincode,
                c.mobile
                FROM sales_entries se
                INNER JOIN customers c 
                    ON c.c_id = se.c_id
                LEFT JOIN stockists s 
                    ON s.stockist_id = se.stockist_id
                WHERE se.s_id = '$sale_id'
                LIMIT 1
        ";

        $result = mysqli_query($this->con, $sql);

        if (!$result) {
            die(mysqli_error($this->con));
        }

        $invoice = mysqli_fetch_assoc($result);

        if (!$invoice) {
            return null;
        }

        /*
         * 2. Fetch Sales Details (Products, HSN, Batches, Rates, Qty, Amount)
         */
        $detailsSql = "
            SELECT
                sd.*, 
                p.product_name,
                p.hsn_code,
                pb.batch_no,
                pb.expiry_date
            FROM sales_details sd
            LEFT JOIN products p 
                ON p.p_id = sd.p_id
            LEFT JOIN product_batches pb 
                ON pb.batch_no = sd.batch_id OR pb.batch_id = sd.batch_id
            WHERE sd.s_id = '$sale_id'
            ORDER BY sd.sale_detail_id  ASC
        ";

        $detailsResult = mysqli_query($this->con, $detailsSql);

        if (!$detailsResult) {
            die(mysqli_error($this->con));
        }

        $invoice['items'] = [];

        while ($row = mysqli_fetch_assoc($detailsResult)) {
            $invoice['items'][] = $row;
        }

        return $invoice;
    }

    public function getSalesExcelData($sale_id)
    {
        // Fetch raw data using your existing method
        $invoice = $this->getSalesInvoice($sale_id);

        if (!$invoice) {
            return null;
        }

        // 1. Prepare Header Info
        $invoice_no = $invoice['invoice_no'] ?? ('SALE-' . $sale_id);
        $invoiceDate = !empty($invoice['sale_date']) ? date('d/m/Y', strtotime($invoice['sale_date'])) : '';
        $sellerName = $invoice['stockist_name'] ?? 'STOCKIST NAME';
        $customerName = $invoice['customer_name'] ?? '';

        $excelData = [
            'filename' => 'Sales-Invoice-' . $invoice_no . '.csv',
            'header' => [
                ['Invoice No:', $invoice_no, '', 'Invoice Date:', $invoiceDate],
                ['Seller:', $sellerName, '', 'Customer:', $customerName],
                [] // Blank row for spacing
            ],
            'columns' => ['Sr.', 'HSN Code', 'Description of Goods', 'Batch', 'Exp', 'MRP', 'Rate', 'Qty', 'Amount'],
            'rows' => [],
            'footer' => []
        ];

        // 2. Prepare Items Data & Calculate Totals
        $items = $invoice['items'] ?? [];
        $sr = 1;
        $totalRenderedQty = 0;
        $calcGrandTotal = 0;

        foreach ($items as $item) {
            $qty = (float)($item['qty'] ?? 0);
            $rate = (float)($item['rate'] ?? 0);
            $mrp = (float)($item['mrp'] ?? 0);
            $discPerc = (float)($item['discount_percent'] ?? 0);
            
            $rowBase = $qty * $rate;
            $firstDiscAmount = $rowBase * ($discPerc / 100);
            $amt = $rowBase - $firstDiscAmount;
            
            $taxRate = (float)($item['gst_percent'] ?? 0);
            $taxAmount = (float)($item['gst_amount'] ?? ($amt * ($taxRate / 100)));
            $net_total = (float)($item['net_total'] ?? ($amt + $taxAmount));

            $totalRenderedQty += $qty;
            $calcGrandTotal += $net_total;

            $excelData['rows'][] = [
                $sr++,
                $item['hsn_code'] ?? '',
                $item['product_name'] ?? '',
                $item['batch_no'] ?? '',
                !empty($item['expiry_date']) ? date('m/y', strtotime($item['expiry_date'])) : '',
                number_format($mrp, 2, '.', ''),
                number_format($rate, 2, '.', ''),
                $qty,
                number_format($net_total, 2, '.', '')
            ];
        }

        // 3. Prepare Footer Totals
        $dbTotalQty = (float)($invoice['total_qty'] ?? 0);
        $dbGrandTotal = (float)($invoice['grand_total'] ?? 0);
        
        $finalQty = $dbTotalQty > 0 ? $dbTotalQty : $totalRenderedQty;
        $finalAmount = $dbGrandTotal > 0 ? $dbGrandTotal : $calcGrandTotal;

        $excelData['footer'] = [
            [], // Blank Row
            ['Total', '', '', '', '', '', '', $finalQty, number_format($finalAmount, 2, '.', '')]
        ];

        return $excelData;
    }
}