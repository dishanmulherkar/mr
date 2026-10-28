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
            SELECT a.*, h.hq_name
            FROM admins a
            INNER JOIN headquarter h ON h.asm_id = a.admin_id 
            WHERE admin_id='$mr_id'
            LIMIT 1
        ");

        return mysqli_fetch_assoc($result);
    }


    public function totalhq($mr_id)
    {
        $mr_id = (int)$mr_id;

        $result = mysqli_query($this->db,"
                SELECT COUNT(*) AS total_hq
                FROM headquarter
                WHERE asm_id = '$mr_id'
                ");

        return mysqli_fetch_assoc($result);
    }


   
    public function updateProfile($mr_id,$data)
    {
        $mr_id=(int)$mr_id;

        $admin_name=mysqli_real_escape_string($this->db,$data['admin_name']);
        $mobile=mysqli_real_escape_string($this->db,$data['mobile']);
        $email=mysqli_real_escape_string($this->db,$data['email']);
       
        return mysqli_query($this->db,"
            UPDATE admins
            SET
                admin_name='$admin_name',
                mobile='$mobile',
                email='$email'
            WHERE admin_id='$mr_id'
        ");
    }

public function changePassword($mr_id, $newPassword)
{
    $mr_id = (int)$mr_id;

    $password = mysqli_real_escape_string($this->db, $newPassword);

    return mysqli_query($this->db, "
        UPDATE admins
        SET password = '$password'
        WHERE admin_id = '$mr_id'
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