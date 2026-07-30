<?php

class StateWiseReportModel
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

    public function getStateName($state_id)
    {
        $state_id = (int)$state_id;

        $result = mysqli_query($this->con,"
            SELECT state_name
            FROM state
            WHERE state_id = $state_id
        ");

        if($row = mysqli_fetch_assoc($result))
        {
            return $row['state_name'];
        }

        return '';
    }

    public function getReport($state,$from_date,$to_date)
    {
        $state=(int)$state;
        return mysqli_query($this->con, "
            SELECT
                h.hq_name,

                SUM(
                    CASE
                        WHEN sl.trans_type='INWARD'
                        AND sl.trans_date BETWEEN '$from_date' AND '$to_date'
                        THEN sl.qty
                        ELSE 0
                    END
                ) AS inward_qty,

                SUM(
                    CASE
                        WHEN sl.trans_type='INWARD'
                        AND sl.trans_date BETWEEN '$from_date' AND '$to_date'
                        THEN sl.amount
                        ELSE 0
                    END
                ) AS inward_amount,

                SUM(
                    CASE
                        WHEN sl.trans_type='SALE'
                        AND sl.trans_date BETWEEN '$from_date' AND '$to_date'
                        THEN sl.qty
                        ELSE 0
                    END
                ) AS sales_qty,

                SUM(
                    CASE
                        WHEN sl.trans_type='SALE'
                        AND sl.trans_date BETWEEN '$from_date' AND '$to_date'
                        THEN sl.amount
                        ELSE 0
                    END
                ) AS sales_amount

            FROM stock_ledger sl

            INNER JOIN stockists st
                ON st.stockist_id = sl.stockist_id

            INNER JOIN mr_users h
                ON h.m_id = st.hq_id

            WHERE h.state = $state

            GROUP BY h.m_id, h.hq_name

            HAVING
                inward_qty > 0
                OR sales_qty > 0
                OR inward_amount > 0
                OR sales_amount > 0

            ORDER BY h.hq_name
            ");
    }
}