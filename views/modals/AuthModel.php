<?php

class AuthModel
{
    private $db;

    public function __construct($con)
    {
        $this->db = $con;
    }

    public function isUserActive($mr_id)
    {
        $stmt = $this->db->prepare("SELECT status FROM mr_users WHERE m_id = ?");
        $stmt->bind_param("i", $mr_id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            return false;
        }

        $row = $result->fetch_assoc();

        return ($row['status'] == 1);
    }
    
}