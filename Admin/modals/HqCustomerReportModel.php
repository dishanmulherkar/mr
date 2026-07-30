<?php

class HqCustomerReportModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getStates()
    {
        // Super Admin can see all states
        if ($_SESSION['admin_role'] == 'Super Admin') {

            return mysqli_query(
                $this->con,
                "SELECT *
                FROM state
                WHERE state_status = 1
                ORDER BY state_name"
            );
        }

        // Admin can see only assigned states
        $admin_id = (int)$_SESSION['admin_id'];

        return mysqli_query(
            $this->con,
            "SELECT
                s.*
            FROM state s
            INNER JOIN admin_state ast
                ON s.state_id = ast.state_id
            WHERE ast.admin_id = '$admin_id'
            AND s.state_status = 1
            ORDER BY s.state_name"
        );
    }

    public function getHQName($hq_id)
    {
        $hq_id=(int)$hq_id;

        $q=mysqli_query($this->con,"
            SELECT hq_name
            FROM mr_users
            WHERE m_id='$hq_id'
            LIMIT 1
        ");

        return mysqli_fetch_assoc($q);
    }
    public function getHqByState($state_id)
{
    $state_id = (int)$state_id;

    return mysqli_query($this->con,"
        SELECT
            m_id,
            hq_name
        FROM mr_users
        WHERE state='$state_id'
        AND status='Active'
        ORDER BY hq_name
    ");
}


    public function getReport($hq_id,$from_date,$to_date)
        {
            $hq_id=(int)$hq_id;

                return mysqli_query($this->con,"
                SELECT
            c.c_id,
            hq.hq_name,
            c.customer_name,
            COUNT(se.s_id) AS total_sales,
            COALESCE(SUM(se.total_amt),0) AS total_amount
        FROM customers c
        INNER JOIN mr_users hq
            ON hq.m_id = c.hq_id
        INNER JOIN  sales_entries se
            ON se.c_id = c.c_id
            AND se.sale_date BETWEEN '$from_date' AND '$to_date'
        WHERE c.hq_id = '$hq_id'
        GROUP BY c.c_id
        ORDER BY c.customer_name
                ");
        }


        public function getCustomerReport($c_id, $from_date, $to_date)
        {
            $sql = "SELECT
                        p.p_id,
                        p.product_name,
                        pb.batch_no,
                        pb.pts AS rate,
                        SUM(sd.qty) AS total_qty,
                        SUM(sd.amount) AS total_amount
                    FROM sales_entries se

                    INNER JOIN sales_details sd
                        ON sd.s_id = se.s_id

                    INNER JOIN products p
                        ON p.p_id = sd.p_id

                    INNER JOIN product_batches pb
                        ON pb.batch_id = sd.batch_id

                    WHERE se.c_id=?
                    AND DATE(se.sale_date) BETWEEN ? AND ?

                    GROUP BY
                        p.p_id,
                        pb.batch_id,
                        pb.batch_no,
                        pb.pts

                    ORDER BY
                        p.product_name,
                        pb.batch_no";

            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("iss", $c_id, $from_date, $to_date);
            $stmt->execute();

            return $stmt->get_result();
        }

        public function getCustomer($c_id)
        {
            $stmt = $this->con->prepare("SELECT customer_name FROM customers WHERE c_id=?");
            $stmt->bind_param("i", $c_id);
            $stmt->execute();

            return $stmt->get_result()->fetch_assoc();
        }
}