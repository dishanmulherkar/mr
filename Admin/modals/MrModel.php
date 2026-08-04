<?php

class MrModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getAll()
    {
        if ($_SESSION['admin_role'] == 'Super Admin') {

            return mysqli_query(
                $this->con,
                "SELECT
                    u.*,
                    s.state_name,
                    h.hq_name
                FROM mr_users u
                LEFT JOIN state s
                    ON u.state = s.state_id
                LEFT JOIN headquarter h
                    ON u.hq_id = h.headquarter_id
                ORDER BY u.m_id DESC"
            );
        }

        $admin_id = (int)$_SESSION['admin_id'];

        return mysqli_query(
            $this->con,
            "SELECT
                u.*,
                s.state_name,
                h.hq_name
            FROM mr_users u
            INNER JOIN admin_state ast
                ON u.state = ast.state_id
            LEFT JOIN state s
                ON u.state = s.state_id
            LEFT JOIN headquarter h
                ON u.hq_id = h.headquarter_id
            WHERE ast.admin_id = $admin_id
            ORDER BY u.m_id DESC"
        );
    }


    public function getById($id)
    {
        return mysqli_fetch_assoc(
            mysqli_query(
                $this->con,
                "SELECT * FROM mr_users WHERE m_id='$id'"
            )
        );
    }

    public function delete($id)
    {
        return mysqli_query(
            $this->con,
            "DELETE FROM mr_users WHERE m_id='$id'"
        );
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

      public function getHQ()
    {
        // $state_id = mysqli_real_escape_string($this->con, $state_id);
        return mysqli_query(
            $this->con,
            "SELECT * FROM headquarter"
        );
    }


    public function getHqByState($state_id)
    {
        $state_id = (int)$state_id;
        return mysqli_query(
            $this->con,
            "SELECT headquarter_id, hq_name FROM headquarter WHERE state_id = '$state_id' ORDER BY hq_name ASC"
        );
    }
    
    public function insert($data)
    {
        $admin_id = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : "NULL";
        $hq_name  = mysqli_real_escape_string($this->con, $data['hq']);
        $mr_name  = mysqli_real_escape_string($this->con, $data['mr_name']);
        $mobile   = mysqli_real_escape_string($this->con, $data['mobile']);
        $email    = mysqli_real_escape_string($this->con, $data['email']);
        $state    = mysqli_real_escape_string($this->con, $data['state']);
        $district = mysqli_real_escape_string($this->con, $data['district']);
        $pincode  = mysqli_real_escape_string($this->con, $data['pincode']);
        $address  = mysqli_real_escape_string($this->con, $data['address']);
        $password = mysqli_real_escape_string($this->con, $data['password']);
        $status = mysqli_real_escape_string($this->con, $data['status']);

        return mysqli_query(
            $this->con,
            "INSERT INTO mr_users
            (admin_id,hq_id,mr_name,mobile,email,state,district,pincode,address,password,status)
            VALUES
            ('$admin_id','$hq_name','$mr_name','$mobile','$email','$state','$district','$pincode','$address','$password','$status')"
        );
    }

    public function update($id, $data)
    {
        $admin_id = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : "NULL";
        $hq_name  = mysqli_real_escape_string($this->con, $data['hq']);
        $mr_name  = mysqli_real_escape_string($this->con, $data['mr_name']);
        $mobile   = mysqli_real_escape_string($this->con, $data['mobile']);
        $email    = mysqli_real_escape_string($this->con, $data['email']);
        $state    = mysqli_real_escape_string($this->con, $data['state']);
        $district = mysqli_real_escape_string($this->con, $data['district']);
        $pincode  = mysqli_real_escape_string($this->con, $data['pincode']);
        $address  = mysqli_real_escape_string($this->con, $data['address']);
        $password = mysqli_real_escape_string($this->con, $data['password']);
        $status = mysqli_real_escape_string($this->con, $data['status']);

        return mysqli_query(
            $this->con,
            "UPDATE mr_users SET
                hq_id='$hq_name',
                mr_name='$mr_name',
                mobile='$mobile',
                email='$email',
                state='$state',
                district='$district',
                pincode='$pincode',
                address='$address',
                password='$password',
                status='$status',
                admin_id = '$admin_id'
            WHERE m_id='$id'"
        );
    }
}