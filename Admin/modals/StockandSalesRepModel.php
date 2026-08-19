<?php

class StockandSalesRepModel
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

    public function getStockistName($stockist_id)
    {
        $stockist_id=(int)$stockist_id;

        $q=mysqli_query($this->con,"
            SELECT stockist_name
            FROM stockists
            WHERE stockist_id='$stockist_id'
            LIMIT 1
        ");

        return mysqli_fetch_assoc($q);
    }

    public function getReport($stockist_id,$from_date,$to_date)
    {
        $stockist_id=(int)$stockist_id;

        return mysqli_query($this->con,"
        SELECT
            p.p_id,
            pb.batch_id,
            pb.batch_no,
            pb.sale_rate,
            s.stockist_name,
            p.product_name,

            (
                SELECT discount_percent
                FROM stock_inward_details 
                WHERE batch_id = pb.batch_no 
                LIMIT 1
            ) AS disc,
            (
                COALESCE(SUM(
                    CASE
                        WHEN sl.trans_date < '$from_date'
                        AND sl.trans_type='INWARD'
                        THEN sl.qty
                        ELSE 0
                    END
                ),0)

                +

                COALESCE(SUM(
                    CASE
                        WHEN sl.trans_date < '$from_date'
                        AND sl.trans_type='ADJUSTMENT'
                        THEN sl.qty
                        ELSE 0
                    END
                ),0)

                -

                COALESCE(SUM(
                    CASE
                        WHEN sl.trans_date < '$from_date'
                        AND sl.trans_type='SALE'
                        THEN sl.qty
                        ELSE 0
                    END
                ),0)

            ) opening_stock,

            COALESCE(SUM(
                CASE
                    WHEN sl.trans_date BETWEEN '$from_date' AND '$to_date'
                    AND sl.trans_type='INWARD'
                    THEN sl.qty
                    ELSE 0
                END
            ),0) inward_qty,

            ABS(
                COALESCE(SUM(
                    CASE
                        WHEN sl.trans_date BETWEEN '$from_date' AND '$to_date'
                        AND sl.trans_type='ADJUSTMENT'
                        THEN sl.qty
                        ELSE 0
                    END
                ),0)
            ) adjustment_qty,

            COALESCE(SUM(
                CASE
                    WHEN sl.trans_date BETWEEN '$from_date' AND '$to_date'
                    AND sl.trans_type='SALE'
                    THEN sl.qty
                    ELSE 0
                END
            ),0) sales_qty,

            COALESCE(SUM(
                CASE
                    WHEN sl.trans_date BETWEEN '$from_date' AND '$to_date'
                    AND sl.trans_type='SALE'
                    THEN sl.amount
                    ELSE 0
                END
            ),0) total_amount,

            (
                COALESCE(SUM(
                    CASE
                        WHEN sl.trans_type='INWARD'
                        THEN sl.qty
                        ELSE 0
                    END
                ),0)

                +

                COALESCE(SUM(
                    CASE
                        WHEN sl.trans_type='ADJUSTMENT'
                        THEN sl.qty
                        ELSE 0
                    END
                ),0)

                -

                COALESCE(SUM(
                    CASE
                        WHEN sl.trans_type='SALE'
                        THEN sl.qty
                        ELSE 0
                    END
                ),0)

            ) closing_stock

        FROM stock_ledger sl

        INNER JOIN products p
            ON p.p_id=sl.p_id

        INNER JOIN product_batches pb
            ON pb.batch_id=sl.batch_id

        INNER JOIN stockists s
            ON s.stockist_id=sl.stockist_id

        WHERE sl.stockist_id='$stockist_id' AND sl.stockist_type = 'STOCKIST'

        GROUP BY
            sl.p_id,
            sl.batch_id

        HAVING
            opening_stock>0
            OR inward_qty>0
            OR sales_qty>0
            OR adjustment_qty>0

        ORDER BY
            p.product_name,
            pb.batch_id
        ");
    }
}