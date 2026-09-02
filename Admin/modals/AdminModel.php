<?php
class AdminModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }
    public function getHQ()
    {
        return mysqli_query($this->con,"
            SELECT m_id,hq_name
            FROM mr_users
            ORDER BY hq_name
        ");
    }

       public function getAll()
        {
            // Start session if not already started to access the role
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $admin_role = $_SESSION['admin_role'] ?? 'Admin';
            
            // Default: No filter (Super Admin sees everyone)
            $whereClause = "";
            
            // If the logged-in user is NOT a Super Admin, hide Super Admins and Admins from the list
            if ($admin_role !== 'Super Admin') {
                $whereClause = "WHERE a.role NOT IN ('Super Admin', 'Admin')";
            }

            $sql = "SELECT
                        a.admin_id,
                        a.admin_name,
                        a.username,
                        a.email,
                        a.mobile,
                        a.role,
                        a.status,
                        a.created_at,
                        a.last_login,
                        GROUP_CONCAT(s.state_name ORDER BY s.state_name SEPARATOR ', ') AS state_names
                    FROM admins a
                    LEFT JOIN admin_state ast ON a.admin_id = ast.admin_id
                    LEFT JOIN state s ON ast.state_id = s.state_id
                    $whereClause
                    GROUP BY a.admin_id
                    ORDER BY a.created_at DESC";

            return mysqli_query($this->con, $sql);
        }
        public function getStates()
        {
            return mysqli_query(
                $this->con,
                "SELECT * FROM state WHERE state_status=1"
            );
        }

        public function getById($id)
        {
            $id = (int)$id;

            $query = mysqli_query($this->con,"
                SELECT *
                FROM admins
                WHERE admin_id='$id'
                LIMIT 1
            ");

            return mysqli_fetch_assoc($query);
        }

        public function getAdminStates($admin_id)
        {
            $admin_id = (int)$admin_id;

            $query = mysqli_query($this->con,"
                SELECT state_id
                FROM admin_state
                WHERE admin_id='$admin_id'
            ");

            $states = [];

            while($row = mysqli_fetch_assoc($query))
            {
                $states[] = $row['state_id'];
            }

            return $states;
        }

        public function insertAdmin($data)
        {
            $stmt = $this->con->prepare("
                INSERT INTO admins
                (admin_name, username, email, mobile, password, role, status, stockist_id, commission_rate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // Corrected to 9 characters to match the 9 variables
            $stmt->bind_param(
                "sssssssid",
                $data['admin_name'],
                $data['user_name'],
                $data['email'],
                $data['mobile'],
                $data['password'],
                $data['role'],
                $data['status'],
                $data['super_stockist_id'],
                $data['commission_rate']
            );

            if ($stmt->execute()) {
                return $this->con->insert_id;
            }

            return false;
        }
        public function insertStates($admin_id,$states)
        {
            $stmt=$this->con->prepare("

            INSERT INTO admin_state(admin_id,state_id)

            VALUES(?,?)

            ");

            foreach($states as $state)
            {
                $stmt->bind_param("ii",$admin_id,$state);
                $stmt->execute();
            }
        }

       public function updateAdmin($id, $data)
        {
            $stmt = $this->con->prepare("
                UPDATE admins
                SET
                    admin_name=?,
                    username=?,
                    email=?,
                    mobile=?,
                    password=?,
                    role=?,
                    status=?,
                    stockist_id=?,
                    commission_rate=?
                WHERE admin_id=?
            ");

            // Corrected to 10 characters to match the 10 variables
            $stmt->bind_param(
                "sssssssidi",
                $data['admin_name'],
                $data['user_name'],
                $data['email'],
                $data['mobile'],
                $data['password'],
                $data['role'],
                $data['status'],
                $data['super_stockist_id'],
                $data['commission_rate'],
                $id
            );

            return $stmt->execute();
        }

        public function deleteStates($admin_id)
        {
            mysqli_query($this->con,"
                DELETE FROM admin_state
                WHERE admin_id='$admin_id'
            ");
        }
        
            public function getSuperStockist()
        {
            return mysqli_query($this->con, "
                SELECT *
                FROM super_stockist
                ORDER BY ss_name ASC
            ");
        }

         public function getAdminStockists($admin_id)
        {
            $admin_id = (int)$admin_id;

            $query = mysqli_query($this->con,"
                SELECT stockist_id
                FROM admin
                WHERE admin_id='$admin_id'
            ");

            $states = [];

            while($row = mysqli_fetch_assoc($query))
            {
                $states[] = $row['state_id'];
            }

            return $states;
        }

    

}