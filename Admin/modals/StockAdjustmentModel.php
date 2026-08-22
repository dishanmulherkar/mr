<?php

class StockAdjustmentModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getProducts()
    {
        return mysqli_query($this->con,"
            SELECT p_id, product_name
            FROM products
            ORDER BY product_name ASC
        ");
    }

    public function getProductBatches()
    {
        $result = mysqli_query($this->con,"
            SELECT
                batch_id,
                product_id,
                batch_no,
                sale_rate
            FROM product_batches
            WHERE status='1'
            ORDER BY batch_no ASC
        ");

        $batches = [];

        while($row = mysqli_fetch_assoc($result))
        {
            $batches[$row['product_id']][] = $row;
        }

        return $batches;
    }

    public function getHQ()
    {
        return mysqli_query($this->con,"
            SELECT m_id,hq_name
            FROM mr_users
            ORDER BY hq_name
        ");
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

    public function getStockistsByHq($hq_id)
    {
        $hq_id = intval($hq_id);
        return mysqli_query($this->con, "
            SELECT stockist_id, stockist_name 
            FROM stockists
            WHERE hq_id = '$hq_id' 
            AND status = '1' 
            ORDER BY stockist_name ASC
        ");
    }

    public function getBatchNumbers()
    {
        $result = mysqli_query($this->con,"
            SELECT DISTINCT batch_no
            FROM product_batches
            WHERE status='1'
            ORDER BY batch_no ASC
        ");

        $batchNos = [];

        while($row = mysqli_fetch_assoc($result))
        {
            $batchNos[] = $row['batch_no'];
        }

        return $batchNos;
    }

    public function store($post)
    {
        $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
        $stockist_id = mysqli_real_escape_string($this->con,$post['stockist_id']);
        $inward_date = mysqli_real_escape_string($this->con,$post['inward_date']);
        $remarks     = mysqli_real_escape_string($this->con,$post['remarks']);

        mysqli_begin_transaction($this->con);

        try
        {
            // Assuming this ID is fetched/created before adjusting stock. 
            // Make sure your application generates this inward_id appropriately.
            $inward_id      = mysqli_insert_id($this->con); 
            $trans_date     = $inward_date;
            $trans_datetime = $inward_date.' '.date('H:i:s');
            $warning = false;

            foreach($post['product'] as $key => $p_id)
            {
                $p_id     = (int)$p_id;
                $qty      = (int)$post['qty'][$key];
                $batch_id = (int)$post['batch_id'][$key];

                if($qty <= 0 || $batch_id <= 0)
                {
                    continue;
                }

                // 1. Get Live Stock directly from Ledger
                $stock_data = $this->getCurrentStock($stockist_id, $p_id, $batch_id);
                $current_qty = (int)$stock_data['current_qty'];

                if($current_qty < $qty)
                {
                    $warning = true;
                    continue;
                }

                // 2. Get Product Rate
                $rate_q = mysqli_query($this->con,"
                    SELECT pts
                    FROM product_batches
                    WHERE batch_id='$batch_id'
                    AND product_id='$p_id'
                    LIMIT 1
                ");

                $rate = 0;

                if(mysqli_num_rows($rate_q))
                {
                    $rate = (float)mysqli_fetch_assoc($rate_q)['pts'];
                }

                $amount = $qty * $rate;

                // 3. Insert into Ledger (Based strictly on image_9666c4.png schema)
                mysqli_query($this->con,"
                    INSERT INTO stock_ledger
                    (
                        trans_date,
                        trans_datetime,
                        stockist_id,
                        p_id,
                        batch_id,
                        trans_type,
                        qty_out,
                        qty,
                        rate,
                        amount,
                        reference_table,
                        reference_id,
                        remarks,
                        admin_id
                    )
                    VALUES
                    (
                        '$trans_date',
                        '$trans_datetime',
                        '$stockist_id',
                        '$p_id',
                        '$batch_id',
                        'ADJUSTMENT',
                        '$qty',
                        '-$qty',
                        '$rate',
                        '-$amount',
                        'stock_inward',
                        '$inward_id',
                        '$remarks',
                        '$admin_id'
                    )
                ");
            }

            mysqli_commit($this->con);

            return [
                'status' => true,
                'warning' => $warning
            ];
        }
        catch(Exception $e)
        {
            mysqli_rollback($this->con);

            return [
                'status' => false,
                'warning' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function getCurrentStock($stockist_id, $p_id, $batch_id)
    {
        // Sanitize inputs to prevent SQL Injection
        $stockist_id = mysqli_real_escape_string($this->con, $stockist_id);
        $p_id = mysqli_real_escape_string($this->con, $p_id);
        $batch_id = mysqli_real_escape_string($this->con, $batch_id);

        // Calculate accurate stock (IN - OUT) directly from the ledger
        $query = mysqli_query($this->con, "
            SELECT COALESCE(SUM(COALESCE(qty_in, 0) - COALESCE(qty_out, 0)), 0) AS current_qty
            FROM stock_ledger
            WHERE stockist_id='$stockist_id'
            AND p_id='$p_id'
            AND batch_id='$batch_id'
        ");

        if ($query && $row = mysqli_fetch_assoc($query)) {
            return $row;
        }

        return ['current_qty' => 0];
    }

    public function getProductsByState()
    {
        $sql = "
            SELECT
                p.p_id,
                p.product_code,
                p.product_name,
                pb.batch_id,
                pb.batch_no,
                pb.sale_rate
            FROM product_batches pb
            INNER JOIN products p
                ON p.p_id = pb.product_id
            ORDER BY p.product_code,pb.batch_no
        ";

        return mysqli_query($this->con,$sql);
    }

    public function getBatchNosByState($state_id)
    {
        $state_id = intval($state_id);

        return mysqli_query($this->con,"
            SELECT DISTINCT batch_no
            FROM product_batches
            WHERE state_id='$state_id'
            ORDER BY batch_no
        ");
    }
}