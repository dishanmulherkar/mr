<?php

class LoginModel
{
    private $db;

    public function __construct($con)
    {
        $this->db = $con;
    }

    public function getUserByEmail($email)
    {
        $stmt = $this->db->prepare("
            SELECT m_id,hq_name,email,password,status,state
            FROM mr_users
            WHERE email=?
        ");

        $stmt->bind_param("s",$email);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    
}