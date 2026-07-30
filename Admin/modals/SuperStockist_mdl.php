<?php

class SupplierModel
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
            FROM super_stockist
            ORDER BY ss_name ASC
        ");
    }

    public function getById($id)
    {
        $id = intval($id);
        $res = mysqli_query($this->con, "SELECT * FROM super_stockist WHERE super_stockist_id = '$id'");
        return mysqli_fetch_assoc($res);
    }

    public function insert($data)
    {
         $stockist_name  = mysqli_real_escape_string($this->con, trim($data['ss_name']));
        $person_name  = mysqli_real_escape_string($this->con, trim($data['person_name']));
        $country  = mysqli_real_escape_string($this->con, trim($data['country']));
        $state_id  = mysqli_real_escape_string($this->con, intval($data['state']));
        $district  = mysqli_real_escape_string($this->con, trim($data['district']));
        $pincode  = mysqli_real_escape_string($this->con, trim($data['pincode']));
        $status = mysqli_real_escape_string($this->con, intval($data['status']));

        // Insert without party_code
        $insert = mysqli_query($this->con, "
            INSERT INTO super_stockist
            (ss_name,person_name,country,state,district,pincode, status)
            VALUES
            ('$stockist_name','$person_name','$country','$state_id','$district','$pincode', '$status')
        ");

        if(!$insert)
        {
            return false;
        }

        return true;
    }

    public function update($id, $data)
    {
        $id  = intval($id);
        $stockist_name  = mysqli_real_escape_string($this->con, trim($data['ss_name']));
        $person_name  = mysqli_real_escape_string($this->con, trim($data['person_name']));
        $country  = mysqli_real_escape_string($this->con, trim($data['country']));
        $state_id  = mysqli_real_escape_string($this->con, intval($data['state']));
        $district  = mysqli_real_escape_string($this->con, trim($data['district']));
        $pincode  = mysqli_real_escape_string($this->con, trim($data['pincode']));
        $status = mysqli_real_escape_string($this->con, intval($data['status']));

        return mysqli_query($this->con,
        "UPDATE super_stockist SET 
                ss_name='$stockist_name',person_name='$person_name',country='$country',state='$state_id',district='$district',pincode='$pincode', status='$status' 
            WHERE super_stockist_id = '$id' ");
    }

    public function delete($id)
    {
        $id = intval($id);
        return mysqli_query($this->con, "DELETE FROM super_stockist WHERE super_stockist_id = '$id'");
    }

    public function checkDuplicate($name, $id = null)
    {
        $name = mysqli_real_escape_string($this->con, $name);
        $query = "SELECT super_stcokist_id FROM super_stockist WHERE ss_name = '$name'";
        if ($id) $query .= " AND super_stockist_id != '" . intval($id) . "'";
        return mysqli_query($this->con, $query);
    }


    public function exists($party_name)
    {
        $pn = mysqli_real_escape_string($this->con, $party_name);
        $res = mysqli_query($this->con, "SELECT p_id FROM parties WHERE party_name = '$pn'");
        return mysqli_num_rows($res) > 0;
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

      public function getDistrictsByState($state_id)
    {
        $state_id = mysqli_real_escape_string($this->con, $state_id);
        return mysqli_query(
            $this->con,
            "SELECT * FROM district WHERE state_id='$state_id' AND district_status=1"
        );
    }
}