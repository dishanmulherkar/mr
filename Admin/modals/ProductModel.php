<?php

class ProductModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getAll()
    {
        return mysqli_query($this->con, "
            SELECT *
            FROM products
            ORDER BY product_code ASC
        ");
    }

    public function getById($id)
    {
        $id = intval($id);
        $res = mysqli_query($this->con, "SELECT * FROM products WHERE p_id = '$id'");
        return mysqli_fetch_assoc($res);
    }

    public function insert($data, $status)
    {
        $pn = mysqli_real_escape_string($this->con, trim($data['product_name']));
        $pk = mysqli_real_escape_string($this->con, trim($data['packing']));

        // Insert without product_code
        $insert = mysqli_query($this->con, "
            INSERT INTO products
            (product_name, packing, status)
            VALUES
            ('$pn','$pk','$status')
        ");

        if(!$insert)
        {
            return false;
        }

        // Get inserted ID
        $insert_id = mysqli_insert_id($this->con);

        // Generate next code
        $product_code = 'P' . str_pad($insert_id, 3, '0', STR_PAD_LEFT);

        // Update product_code
        mysqli_query($this->con,"
            UPDATE products
            SET product_code='$product_code'
            WHERE p_id='$insert_id'
        ");

        return true;
    }

    public function update($id, $data, $status)
    {
        $id  = intval($id);
        $pn  = mysqli_real_escape_string($this->con, trim($data['product_name']));
        $pc  = mysqli_real_escape_string($this->con, trim($data['product_code']));
        $pk  = mysqli_real_escape_string($this->con, trim($data['packing']));
        

        return mysqli_query($this->con, "
            UPDATE products SET 
                product_name='$pn', product_code='$pc', packing='$pk', status='$status' 
            WHERE p_id = '$id'
        ");
    }

    public function delete($id)
    {
        $id = intval($id);
        return mysqli_query($this->con, "DELETE FROM products WHERE p_id = '$id'");
    }

    public function checkDuplicate($name, $id = null)
    {
        $name = mysqli_real_escape_string($this->con, $name);
        $query = "SELECT p_id FROM products WHERE product_name = '$name'";
        if ($id) $query .= " AND p_id != '" . intval($id) . "'";
        return mysqli_query($this->con, $query);
    }

    public function insertProduct($pn, $pk,  $st)
    {
        $pn = mysqli_real_escape_string($this->con, $pn);
        $pc = mysqli_real_escape_string($this->con, $pc);
        $pk = mysqli_real_escape_string($this->con, $pk);
        return mysqli_query($this->con, "
            INSERT INTO products (product_name, product_code, packing, status) 
            VALUES ('$pn', '$pc', '$pk', '$st')
        ");
    }

    public function exists($product_name)
    {
        $pn = mysqli_real_escape_string($this->con, $product_name);
        $res = mysqli_query($this->con, "SELECT p_id FROM products WHERE product_name = '$pn'");
        return mysqli_num_rows($res) > 0;
    }
}