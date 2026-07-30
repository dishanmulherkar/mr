<?php

class SalesReportModel
{
    private $con;

    public function __construct()
    {
        global $con;
        $this->con = $con;
    }

    public function getStockists($mr_id)
    {
        $stmt = $this->con->prepare("
            SELECT stockist_id, stockist_name
            FROM stockists
            WHERE hq_id=?
            ORDER BY stockist_name
        ");

        $stmt->bind_param("i",$mr_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getSalesReport($mr_id, $start_date, $end_date)
    {
        $sql = "
            SELECT
                c.c_id,
                c.customer_name,
                COUNT(DISTINCT s.s_id) AS total_sales,
                COALESCE(SUM(sd.qty),0) AS total_qty,
                COALESCE(SUM(sd.amount),0) AS total_amt
            FROM sales_entries s
            INNER JOIN customers c
                ON c.c_id = s.c_id
            INNER JOIN sales_details sd
                ON sd.s_id = s.s_id
            WHERE s.m_id = ?
            AND DATE(s.sale_date) BETWEEN ? AND ?
            GROUP BY c.c_id, c.customer_name
            ORDER BY c.customer_name
        ";

        $stmt = $this->con->prepare($sql);

        if (!$stmt)
        {
            return [
                'success' => false,
                'message' => $this->con->error
            ];
        }

        $stmt->bind_param(
            "iss",
            $mr_id,
            $start_date,
            $end_date
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $data = [];

        $grand_qty = 0;
        $grand_amt = 0;

        while ($row = $result->fetch_assoc())
        {
            $qty = (float)$row['total_qty'];
            $amt = (float)$row['total_amt'];

            $grand_qty += $qty;
            $grand_amt += $amt;

            $data[] = [

                'customer_id'   => (int)$row['c_id'],
                'customer_name' => $row['customer_name'],
                'total_sales'   => (int)$row['total_sales'],
                'total_qty'     => $qty,
                'total_amt'     => round($amt,2)

            ];
        }

        $stmt->close();

        return [

            'success'    => true,
            'mr_id'      => $mr_id,
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'grand_qty'  => $grand_qty,
            'grand_amt'  => round($grand_amt,2),
            'count'      => count($data),
            'data'       => $data

        ];
    }

    public function getMR($mr_id)
    {
        $stmt = $this->con->prepare("
            SELECT mr_name,hq_name
            FROM mr_users
            WHERE m_id=?
        ");

        $stmt->bind_param("i",$mr_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function getSalesReportPDF($mr_id,$start_date,$end_date,$customer='')
    {
        $search = "%".$customer."%";

        $sql = "

        SELECT
            c.customer_name,
            COUNT(DISTINCT s.s_id) total_sales,
            SUM(sd.amount) total_amt

        FROM sales_entries s

        INNER JOIN customers c
            ON c.c_id=s.c_id

        INNER JOIN sales_details sd
            ON sd.s_id=s.s_id

        WHERE s.m_id=?
        AND DATE(s.sale_date) BETWEEN ? AND ?
        AND c.customer_name LIKE ?

        GROUP BY c.c_id

        ORDER BY c.customer_name
        ";

        $stmt = $this->con->prepare($sql);

        $stmt->bind_param(
            "isss",
            $mr_id,
            $start_date,
            $end_date,
            $search
        );

        $stmt->execute();

        return $stmt->get_result();
    }
}