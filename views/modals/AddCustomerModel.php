<?php
class AddCustomerModel {

private $db;
    public function __construct($con)
    {
        $this->db = $con;
    }

    // Financial Year / Target
    public function getCustomers($mr_id)
    {
        $query = "
           SELECT * FROM customers WHERE hq_id = '$mr_id' ORDER BY created_at DESC
        ";

        $result = mysqli_query($this->db, $query);

       return $result;
    }


    public function getStates()
    {
        $state_id = $_SESSION['state_id'];
        $sql = "SELECT * FROM state WHERE state_id = ".$state_id." ORDER BY state_name";
         $result = mysqli_query($this->db, $sql);
        return $result;
    }

    public function getByState($state_id)
    {
        return mysqli_query(
            $this->db,
            "SELECT * FROM district
             WHERE state_id='$state_id' AND district_status=1 ORDER BY district_name ASC"
        );
    }

  public function getCustomerById($id)
{
    $id = (int)$id;

    $query = mysqli_query($this->db,"
        SELECT *
        FROM customers
        WHERE c_id = '$id'
        LIMIT 1
    ");

    return mysqli_fetch_assoc($query);
}

    public function store($data)
    {
        $stmt = mysqli_prepare($this->db, "
            INSERT INTO customers
            (
                customer_name,
                customer_type,
                qualification,
                mobile,
                email,
                address,
                district,
                state,
                pincode,
                customer_img,
                hq_id
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "sssssssissi",
            $data['customer_name'],
            $data['customer_type'],
            $data['qualification'],
            $data['mobile'],
            $data['email'],
            $data['address'],
            $data['district'],
            $data['state'],
            $data['pincode'],
            $data['customer_img'],
            $data['mr_id']
        );

        return mysqli_stmt_execute($stmt);
    }

    public function update($id, $data)
    {
        $id = (int)$id;

        $customer_name = mysqli_real_escape_string($this->db,$data['customer_name']);
        $customer_type = mysqli_real_escape_string($this->db,$data['customer_type']);
        $qualification = mysqli_real_escape_string($this->db,$data['qualification']);
        $mobile        = mysqli_real_escape_string($this->db,$data['mobile']);
        $email         = mysqli_real_escape_string($this->db,$data['email']);
        $address       = mysqli_real_escape_string($this->db,$data['address']);
        $state         = (int)$data['state'];
        $district      = $data['district'];
        $pincode       = mysqli_real_escape_string($this->db,$data['pincode']);

        $sql = "
            UPDATE customers SET
                customer_name='$customer_name',
                customer_type='$customer_type',
                qualification='$qualification',
                mobile='$mobile',
                email='$email',
                address='$address',
                state='$state',
                district='$district',
                pincode='$pincode'
        ";

        if(!empty($data['customer_img']))
        {
            $customer_img = mysqli_real_escape_string($this->db,$data['customer_img']);

            $sql .= ", customer_img='$customer_img'";
        }

        $sql .= " WHERE c_id='$id'";

        return mysqli_query($this->db,$sql);
    }

}
?>