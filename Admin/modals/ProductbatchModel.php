<?php

class BatchProductModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    // Helper: Get Product ID by Name
    public function getProductIdByCode($code)
    {
        $code = mysqli_real_escape_string($this->con, trim($code));

        $res = mysqli_query($this->con, "
            SELECT p_id
            FROM products
            WHERE product_code='$code'
            LIMIT 1
        ");

        $row = mysqli_fetch_assoc($res);

        return $row ? $row['p_id'] : null;
    }

        public function getStateIdByName($state)
    {
        $state = mysqli_real_escape_string($this->con, trim($state));

        $res = mysqli_query($this->con, "
            SELECT state_id
            FROM state
            WHERE state_name='$state'
            LIMIT 1
        ");

        $row = mysqli_fetch_assoc($res);

        return $row ? $row['state_id'] : null;
    }


    public function isBatchDuplicate($product_id, $state_id, $batch_no)
    {
        $product_id = intval($product_id);
        $state_id   = intval($state_id);
        $batch_no   = mysqli_real_escape_string($this->con, $batch_no);

        $res = mysqli_query($this->con, "
            SELECT batch_id
            FROM product_batches
            WHERE product_id='$product_id'
            AND state_id='$state_id'
            AND batch_no='$batch_no'
        ");

        return mysqli_num_rows($res) > 0;
    }

    // Insert for Import
    public function insertBatch($product_id, $state_id, $batch_no, $pts, $status)
    {
        $product_id = intval($product_id);
        $state_id   = intval($state_id);
        $batch_no   = mysqli_real_escape_string($this->con, $batch_no);
        $pts        = floatval($pts);
        $status     = mysqli_real_escape_string($this->con, $status);

        return mysqli_query($this->con,"
            INSERT INTO product_batches
            (product_id,state_id,batch_no,pts,status)
            VALUES
            ('$product_id','$state_id','$batch_no','$pts','$status')
        ");
    }

    public function getAll()
    {
        return mysqli_query($this->con,"
            SELECT
                pb.*,
                p.product_code,
                p.product_name,
                s.state_name
            FROM product_batches pb
            INNER JOIN products p
                ON p.p_id = pb.product_id
            INNER JOIN state s
                ON s.state_id = pb.state_id
            ORDER BY p.product_code ASC, pb.batch_no ASC
        ");
    }

    public function getById($id)
    {
        $id = intval($id);
        return mysqli_fetch_assoc(mysqli_query($this->con, "SELECT * FROM product_batches WHERE batch_id = '$id'"));
    }

    public function insert($data)
    {
        $pid = intval($data['product_id']);
        $bn  = mysqli_real_escape_string($this->con, trim($data['batch_no']));
        $pts = floatval($data['pts']);
        $st  = mysqli_real_escape_string($this->con, $data['status']);

        return mysqli_query($this->con, "
            INSERT INTO product_batches (product_id, batch_no, pts, status) 
            VALUES ('$pid', '$bn', '$pts', '$st')
        ");
    }

    public function update($id, $data)
    {
        $id  = intval($id);
        $pid = intval($data['product_id']);
        $bn  = mysqli_real_escape_string($this->con, trim($data['batch_no']));
        $pts = floatval($data['pts']);
        $st  = mysqli_real_escape_string($this->con, $data['status']);

        return mysqli_query($this->con, "
            UPDATE product_batches SET 
                product_id='$pid', batch_no='$bn', pts='$pts', status='$st' 
            WHERE batch_id = '$id'
        ");
    }

    public function toggleStatus($id)
    {
        $id = intval($id);
        $cur = mysqli_fetch_assoc(mysqli_query($this->con, "SELECT status FROM product_batches WHERE batch_id = '$id'"));
        $new_status = ($cur['status'] === 'Active') ? 'Inactive' : 'Active';
        return mysqli_query($this->con, "UPDATE product_batches SET status = '$new_status' WHERE batch_id = '$id'");
    }

    public function delete($id)
    {
        return mysqli_query($this->con, "DELETE FROM product_batches WHERE batch_id = '" . intval($id) . "'");
    }

    public function getProductList()
    {
        return mysqli_query($this->con, "SELECT p_id, product_name FROM products ORDER BY product_name ASC");
    }
}