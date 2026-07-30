<?php
class CustomerModel {

private $db;
    public function __construct($con)
    {
        $this->db = $con;
    }

    // Financial Year / Target
    public function getCustomers($mr_id)
    {
        $query = "
           SELECT c.*,s.state_name FROM customers c LEFT JOIN state s ON c.state = s.state_id WHERE c.hq_id = '$mr_id' ORDER BY created_at DESC
        ";

        $result = mysqli_query($this->db, $query);

       return $result;
    }

    public function getCustomerById($customer_id)
    {
        $customer_id = (int)$customer_id;

        $sql = "SELECT
            c.customer_name,
            c.qualification,
            c.district,
            c.address,
            c.email,
            c.mobile,
            s.state_name
        FROM customers c
        LEFT JOIN state s ON c.state = s.state_id
        WHERE c.c_id = $customer_id";

        $result = mysqli_query($this->db, $sql);

        return mysqli_fetch_assoc($result);
    }

}
?>