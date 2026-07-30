<?php

class AccountModel
{
    private $db;

    public function __construct($con)
    {
        $this->db = $con;
    }

    public function getMR($mr_id)
    {
        $mr_id = (int)$mr_id;

        $result = mysqli_query($this->db,"
            SELECT *
            FROM mr_users
            WHERE m_id='$mr_id'
            LIMIT 1
        ");

        return mysqli_fetch_assoc($result);
    }

     public function totalStockist($mr_id)
    {
        $mr_id = (int)$mr_id;

        $result = mysqli_query($this->db,"
                SELECT COUNT(*) AS total_stockists
            FROM stockists
            WHERE hq_id = '$mr_id'
                ");

        return mysqli_fetch_assoc($result);
    }


    public function totalCustomer($mr_id)
    {
        $mr_id = (int)$mr_id;

        $result = mysqli_query($this->db,"
                SELECT COUNT(*) AS total_customers
                FROM customers
                WHERE hq_id = '$mr_id'
                ");

        return mysqli_fetch_assoc($result);
    }


    public function getDistricts($state_id)
    {
        $state_id = (int)$state_id;

        return mysqli_query($this->db,"
            SELECT district_id,district_name
            FROM district
            WHERE state_id='$state_id'
            ORDER BY district_name
        ");
    }

    public function updateProfile($mr_id,$data)
    {
        $mr_id=(int)$mr_id;

        $mr_name=mysqli_real_escape_string($this->db,$data['mr_name']);
        $mobile=mysqli_real_escape_string($this->db,$data['mobile']);
        $email=mysqli_real_escape_string($this->db,$data['email']);
        $district=$data['district'];
        $pincode=mysqli_real_escape_string($this->db,$data['pincode']);
        $address=mysqli_real_escape_string($this->db,$data['address']);

        return mysqli_query($this->db,"
            UPDATE mr_users
            SET
                mr_name='$mr_name',
                mobile='$mobile',
                email='$email',
                district='$district',
                pincode='$pincode',
                address='$address'
            WHERE m_id='$mr_id'
        ");
    }

public function changePassword($mr_id, $newPassword)
{
    $mr_id = (int)$mr_id;

    $password = mysqli_real_escape_string($this->db, $newPassword);

    return mysqli_query($this->db, "
        UPDATE mr_users
        SET password = '$password'
        WHERE m_id = '$mr_id'
    ");
}

    public function verifyPassword($mr_id,$password)
    {
        $mr=$this->getMR($mr_id);

        if(!$mr){
            return false;
        }

        return ($password === $mr['password']);
    }
}