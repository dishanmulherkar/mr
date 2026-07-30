<?php

class PurchaseEntryModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getProducts()
    {
        return mysqli_query($this->con, "
            SELECT p_id, product_name
            FROM products
            ORDER BY product_name ASC
        ");
    }

    public function getStockist()
    {
        return mysqli_query($this->con, "
            SELECT ss.super_stockist_id, ss.ss_name,s.state_name
            FROM super_stockist ss 
            INNER JOIN state s ON
            ss.state=s.state_id
            ORDER BY ss_name ASC
        ");
    }

    public function store($post)
    {
        $supplier_id   = (int)$post['supplier_id'];
        $purchase_date = mysqli_real_escape_string($this->con, $post['purchase_date']);
        $remarks       = mysqli_real_escape_string($this->con, $post['remarks'] ?? '');

        $admin_id = isset($_SESSION['admin_id'])
            ? (int)$_SESSION['admin_id']
            : null;

        // Generate batch number: DDMMYY
        $batch_no = date('dmy', strtotime($purchase_date));

        $total_qty    = 0;
        $total_amount = 0;

        mysqli_begin_transaction($this->con);

        try {

            // Calculate totals
            foreach ($post['product_id'] as $key => $product_id)
            {
                $qty    = (float)$post['qty'][$key];
                $rate   = (float)$post['rate'][$key];
                $amount = $qty * $rate;

                $total_qty    += $qty;
                $total_amount += $amount;
            }

            // Insert purchase header
            $header = mysqli_query($this->con, "
                INSERT INTO purchase_entry
                (
                    batch_no,
                    supplier_id,
                    purchase_date,
                    total_qty,
                    total_amount
                )
                VALUES
                (
                    '$batch_no',
                    '$supplier_id',
                    '$purchase_date',
                    '$total_qty',
                    '$total_amount'
                )
            ");

            if (!$header)
            {
                throw new Exception(mysqli_error($this->con));
            }

            $purchase_id = mysqli_insert_id($this->con);

            // Insert details and stock ledger
            foreach ($post['product_id'] as $key => $product_id)
            {
                $product_id = (int)$product_id;
              
                $qty        = (float)$post['qty'][$key];
                $rate       = (float)$post['rate'][$key];
                $amount     = $qty * $rate;

                if ($qty <= 0)
                {
                    continue;
                }

                // Purchase Details
                $detail = mysqli_query($this->con,"
                    INSERT INTO purchase_entry_details
                    (
                        purchase_id,
                        product_id,
                        qty,
                        purchase_rate,
                        amount
                    )
                    VALUES
                    (
                        '$purchase_id',
                        '$product_id',
                        '$qty',
                        '$rate',
                        '$amount'
                    )
                ");

                if(!$detail)
                {
                    throw new Exception(mysqli_error($this->con));
                }

                // Stock Ledger
                $ledger = mysqli_query($this->con,"
                    INSERT INTO stock_ledger
                    (
                        ref_id,
                        product_id,
                        batch_no,
                        transaction_type,
                        stock_in,
                        stock_out,
                        amount,
                        rate
                    )
                    VALUES
                    (
                        '$purchase_id',
                        '$product_id',
                        '$batch_no',
                        'PURCHASE',
                        '$qty',
                        0,
                        '$amount',
                        '$rate'
                    )
                ");

                if(!$ledger)
                {
                    throw new Exception(mysqli_error($this->con));
                }

                // Check Stock
                $check = mysqli_query($this->con,"
                    SELECT stock_id
                    FROM stock
                    WHERE product_id='$product_id'
                    AND batch_no='$batch_no'
                    LIMIT 1
                ");

                if(mysqli_num_rows($check) > 0)
                {
                    // Increase Existing Stock
                    $update = mysqli_query($this->con,"
                        UPDATE stock
                        SET
                            qty = qty + '$qty',
                            purchase_rate = '$rate'
                        WHERE
                            product_id='$product_id'
                            AND batch_no='$batch_no'
                    ");

                    if(!$update)
                    {
                        throw new Exception(mysqli_error($this->con));
                    }
                }
                else
                {
                    // Create New Stock Record
                    $insert = mysqli_query($this->con,"
                        INSERT INTO stock
                        (
                            product_id,
                            batch_no,
                            qty,
                            purchase_rate
                        )
                        VALUES
                        (
                            '$product_id',
                            '$batch_no',
                            '$qty',
                            '$rate'
                        )
                    ");

                    if(!$insert)
                    {
                        throw new Exception(mysqli_error($this->con));
                    }
                }
            }

            mysqli_commit($this->con);
            return true;
        } catch (Exception $e) {

            mysqli_rollback($this->con);

            return $e->getMessage();
        }
    }
}