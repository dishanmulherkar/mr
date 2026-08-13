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
}