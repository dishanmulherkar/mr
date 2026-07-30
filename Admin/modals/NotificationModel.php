<?php
class NotificationModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }


    public function create($data)
    {
        $title    = mysqli_real_escape_string($this->con, $data['title']);
        $message  = mysqli_real_escape_string($this->con, $data['message']);
        $state   = intval($data['state']);
        $send_type = $data['send_type'];
        $status   = intval($data['status']);
        
        // Handle array of HQ IDs
        $hq_ids = ($send_type == 'selected' && isset($data['hq_ids'])) 
                  ? implode(',', $data['hq_ids']) 
                  : '';

        return mysqli_query($this->con, "
            INSERT INTO notifications (state_id, title, message, send_type, hq_ids, status) 
            VALUES ('$state', '$title', '$message', '$send_type', '$hq_ids', '$status')
        ");
    }

    public function update($id, $data)
    {
        $id       = intval($id);
        $title    = mysqli_real_escape_string($this->con, $data['title']);
        $message  = mysqli_real_escape_string($this->con, $data['message']);
        $state   = intval($data['state']);
        $send_type = $data['send_type'];
        $status   = intval($data['status']);
        
        $hq_ids = ($send_type == 'selected' && isset($data['hq_ids'])) 
                  ? implode(',', $data['hq_ids']) 
                  : '';

        return mysqli_query($this->con, "
            UPDATE notifications SET 
                state_id='$state', title='$title', message='$message', send_type='$send_type', 
                hq_ids='$hq_ids', status='$status' 
            WHERE notification_id = '$id'
        ");
    }

    public function getById($id)
    {
        $id = intval($id);
        return mysqli_fetch_assoc(mysqli_query($this->con, "SELECT * FROM notifications WHERE notification_id = '$id'"));
    }


    public function getHQ()
    {
        return mysqli_query($this->con,"
            SELECT m_id,hq_name
            FROM mr_users
            ORDER BY hq_name
        ");
    }

     public function getHQByState($state_id)
    {
        $state_id = intval($state_id);
        return mysqli_query($this->con, "
            SELECT m_id, hq_name 
            FROM mr_users 
            WHERE state = '$state_id' 
            AND status = '1' 
            ORDER BY hq_name ASC
        ");
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

    public function getAll()
    {
        if ($_SESSION['admin_role'] == 'Super Admin') {

            return mysqli_query($this->con, "
                SELECT n.*, s.state_name
                FROM notifications n
                LEFT JOIN state s
                    ON n.state_id = s.state_id
                ORDER BY n.notification_id DESC
            ");
        }

        $admin_id = (int)$_SESSION['admin_id'];

        return mysqli_query($this->con, "
            SELECT n.*, s.state_name
            FROM notifications n
            INNER JOIN admin_state ast
                ON n.state_id = ast.state_id
            LEFT JOIN state s
                ON n.state_id = s.state_id
            WHERE ast.admin_id = $admin_id
            ORDER BY n.notification_id DESC
        ");
    }

    // Helper to get HQ names based on a comma-separated string of IDs
    public function getHqNames($ids)
    {
        if (empty($ids)) return 'None';
        
        // Sanitize string to prevent SQL injection
        $ids_escaped = mysqli_real_escape_string($this->con, $ids);
        
        $res = mysqli_query($this->con, "
            SELECT GROUP_CONCAT(hq_name SEPARATOR ', ') AS hq_names
            FROM mr_users
            WHERE FIND_IN_SET(m_id, '$ids_escaped')
        ");
        
        $data = mysqli_fetch_assoc($res);
        return $data['hq_names'] ?? 'None';
    }
    
     public function delete($id)
    {
        return mysqli_query(
            $this->con,
            "DELETE FROM notifications WHERE notification_id='$id'"
        );
    }
    

}