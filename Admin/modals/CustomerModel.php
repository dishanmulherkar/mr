<?php

class CustomerModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getAll()
    {
        if ($_SESSION['admin_role'] == 'Super Admin') {

            $query = "
                SELECT 
                    c.*, 
                    s.state_name, 
                    d.district_name,
                    h.hq_name
                FROM customers c
                LEFT JOIN state s
                    ON c.state = s.state_id
                LEFT JOIN district d
                    ON c.district = d.district_id
                LEFT JOIN headquarter h
                    ON c.hq_id = h.headquarter_id
                ORDER BY c.c_id DESC
            ";

            return mysqli_query($this->con, $query);
        }

        $admin_id = (int)$_SESSION['admin_id'];

        $query = "
            SELECT 
                c.*, 
                s.state_name, 
                d.district_name,
                h.hq_name
            FROM customers c
            INNER JOIN admin_state ast
                ON c.state = ast.state_id
            LEFT JOIN state s
                ON c.state = s.state_id
            LEFT JOIN district d
                ON c.district = d.district_id
            LEFT JOIN headquarter h
                ON c.hq_id = h.headquarter_id
            WHERE ast.admin_id = $admin_id
            ORDER BY c.c_id DESC
        ";

        return mysqli_query($this->con, $query);
    }

    public function getById($id)
    {
        return mysqli_fetch_assoc(
            mysqli_query(
                $this->con,
                "SELECT * FROM customers WHERE c_id='$id'"
            )
        );
    }

    // Add this method to get states
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

    // Add this method to get districts based on state ID
    public function getDistrictsByState($state_id)
    {
        $state_id = mysqli_real_escape_string($this->con, $state_id);
        return mysqli_query(
            $this->con,
            "SELECT * FROM district WHERE state_id='$state_id' AND district_status=1"
        );
    }

    public function delete($id)
    {
        return mysqli_query(
            $this->con,
            "DELETE FROM customers WHERE c_id='$id'"
        );
    }

    public function getHqByState($state_id)
    {
        $state_id = intval($state_id);
        return mysqli_query($this->con, "SELECT headquarter_id, hq_name FROM headquarter WHERE state_id = '$state_id' ORDER BY hq_name ASC");
    }

    public function isDuplicate($name, $mobile, $exclude_id = null)
    {
        $name_esc   = mysqli_real_escape_string($this->con, $name);
        $mobile_esc = mysqli_real_escape_string($this->con, $mobile);

        $query = "SELECT * FROM customers WHERE customer_name='$name_esc' AND mobile='$mobile_esc'";
        
        if ($exclude_id) {
            $query .= " AND c_id != '$exclude_id'";
        }

        $check = mysqli_query($this->con, $query);
        return mysqli_num_rows($check) > 0;
    }

    public function insert($data, $image_name)
    {
         $admin_id = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : "NULL";
        $customer_name = mysqli_real_escape_string($this->con, $data['customer_name']);
        $customer_type = mysqli_real_escape_string($this->con, $data['customer_type']);
        $qualification = mysqli_real_escape_string($this->con, $data['qualification']);
        $mobile        = mysqli_real_escape_string($this->con, $data['mobile']);
        $email         = mysqli_real_escape_string($this->con, $data['email']);
        $address       = mysqli_real_escape_string($this->con, $data['address']);
        $district      = mysqli_real_escape_string($this->con, $data['district']);
        $state         = mysqli_real_escape_string($this->con, $data['state']);
        $hq_id         = intval($data['hq_id']);
        $pincode       = mysqli_real_escape_string($this->con, $data['pincode']);
        $status        = intval($data['status']);
        $created_by    = "mr";

        return mysqli_query(
            $this->con,
            "INSERT INTO customers
            (admin_id,customer_name, customer_type, qualification, customer_img, mobile, email, address, district, state, hq_id, pincode, status, created_by)
            VALUES
            ('$admin_id','$customer_name','$customer_type','$qualification','$image_name','$mobile','$email','$address','$district','$state','$hq_id','$pincode','$status','$created_by')"
        );
    }

    public function update($id, $data, $image_name)
    {
        $customer_name = mysqli_real_escape_string($this->con, $data['customer_name']);
        $customer_type = mysqli_real_escape_string($this->con, $data['customer_type']);
        $qualification = mysqli_real_escape_string($this->con, $data['qualification']);
        $mobile        = mysqli_real_escape_string($this->con, $data['mobile']);
        $email         = mysqli_real_escape_string($this->con, $data['email']);
        $address       = mysqli_real_escape_string($this->con, $data['address']);
        $district      = mysqli_real_escape_string($this->con, $data['district']);
        $state         = mysqli_real_escape_string($this->con, $data['state']);
        $hq_id         = intval($data['hq_id']);
        $pincode       = mysqli_real_escape_string($this->con, $data['pincode']);
         $status        = intval($data['status']);
        $created_by    = "mr";

        return mysqli_query(
            $this->con,
            "UPDATE customers SET
                customer_name  = '$customer_name',
                customer_type  = '$customer_type',
                qualification  = '$qualification',
                customer_img   = '$image_name',
                mobile         = '$mobile',
                email          = '$email',
                address        = '$address',
                district       = '$district',
                state          = '$state',
                hq_id          = '$hq_id',
                pincode        = '$pincode',
                status         = '$status',
                created_by     = '$created_by'
            WHERE c_id = '$id'"
        );
    }

    public function downloadImages()
{
    if ($_SESSION['admin_role'] != 'Super Admin') {

        $admin_id = (int)$_SESSION['admin_id'];

        $sql = "SELECT c.c_id,
                       c.customer_name,
                       c.customer_img,
                       s.state_name
                FROM customers c
                INNER JOIN admin_state ast ON ast.state_id = c.state
                INNER JOIN state s ON s.state_id = c.state
                WHERE ast.admin_id = $admin_id";

    } else {

        $sql = "SELECT c.c_id,
                       c.customer_name,
                       c.customer_img,
                       s.state_name
                FROM customers c
                INNER JOIN state s ON s.state_id = c.state";
    }

    $result = mysqli_query($this->con, $sql);

    $zip = new ZipArchive();

    $zipName = "Customer_Images_" . date("YmdHis") . ".zip";

    if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {

        while ($row = mysqli_fetch_assoc($result)) {

            if (empty($row['customer_img'])) {
                continue;
            }

            $path = "uploads/customers/" . $row['customer_img'];

            if (!file_exists($path)) {
                continue;
            }

            $ext = pathinfo($path, PATHINFO_EXTENSION);

            $customer = preg_replace('/[^A-Za-z0-9 _-]/', '', $row['customer_name']);
            $state = preg_replace('/[^A-Za-z0-9 _-]/', '', $row['state_name']);

            // Folder name = State Name
            $zipPath = $state . "/" . $customer . "_" . $row['c_id'] . "." . $ext;

            $zip->addFile($path, $zipPath);
        }

        $zip->close();

        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=\"$zipName\"");
        header("Content-Length: " . filesize($zipName));

        readfile($zipName);
        unlink($zipName);
        exit;
    }

    echo "Unable to create ZIP file.";
}
}
?>