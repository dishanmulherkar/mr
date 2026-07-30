<?php
class FinancialModel
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
        if ($_SESSION['admin_role'] == 'Super Admin') {

            return mysqli_query(
                $this->con,
                "SELECT
                    fy.*,
                    h.hq_name,
                    s.state_name
                FROM financial_year fy
                INNER JOIN mr_users h
                    ON fy.hq_id = h.m_id
                INNER JOIN state s
                    ON h.state = s.state_id
                ORDER BY fy.created_at DESC"
            );
        }

        $admin_id = (int)$_SESSION['admin_id'];

        return mysqli_query(
            $this->con,
            "SELECT
                fy.*,
                h.hq_name,
                s.state_name
            FROM financial_year fy
            INNER JOIN mr_users h
                ON fy.hq_id = h.m_id
            INNER JOIN state s
                ON h.state = s.state_id
            INNER JOIN admin_state ast
                ON h.state = ast.state_id
            WHERE ast.admin_id = $admin_id
            ORDER BY fy.created_at DESC"
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

        public function getStateByHQ($hq_id)
        {
            $hq_id = (int)$hq_id;

            $query = mysqli_query($this->con,"
                SELECT state
                FROM mr_users
                WHERE m_id='$hq_id'
                LIMIT 1
            ");

            return mysqli_fetch_assoc($query);
        }

        public function getById($id)
        {
            $id = (int)$id;

            $query = mysqli_query($this->con,"
                SELECT *
                FROM financial_year
                WHERE fy_id='$id'
                LIMIT 1
            ");

            return mysqli_fetch_assoc($query);
        }
        public function store($post)
        {
            $hq_id         = (int)$post['hq_id'];
            $fy_name       = mysqli_real_escape_string($this->con,$post['fy_name']);
            $start_date    = $post['start_date'];
            $end_date      = $post['end_date'];
            $target_amount = $post['target_amount'];
            $status        = (int)$post['status'];

            // Duplicate Check
            $check = mysqli_query($this->con,"
                SELECT fy_id
                FROM financial_year
                WHERE hq_id='$hq_id'
                AND fy_name='$fy_name'
            ");

            if(mysqli_num_rows($check))
            {
                return "duplicate";
            }

            if($status == 1)
            {
                mysqli_query($this->con,"
                    UPDATE financial_year
                    SET status='0'
                    WHERE hq_id='$hq_id'
                ");
            }

            $insert = mysqli_query($this->con,"
                INSERT INTO financial_year
                (
                    hq_id,
                    fy_name,
                    start_date,
                    end_date,
                    target_amount,
                    status
                )
                VALUES
                (
                    '$hq_id',
                    '$fy_name',
                    '$start_date',
                    '$end_date',
                    '$target_amount',
                    '$status'
                )
            ");

            return $insert;
        }

        public function update($id,$post)
        {
            $id             = (int)$id;
            $hq_id          = (int)$post['hq_id'];
            $fy_name        = mysqli_real_escape_string($this->con,$post['fy_name']);
            $start_date     = $post['start_date'];
            $end_date       = $post['end_date'];
            $target_amount  = $post['target_amount'];
            $status         = (int)$post['status'];

            if($status == 1)
            {
                mysqli_query($this->con,"
                    UPDATE financial_year
                    SET status='0'
                    WHERE hq_id='$hq_id'
                    AND fy_id!='$id'
                ");
            }

            return mysqli_query($this->con,"
                UPDATE financial_year
                SET
                    hq_id='$hq_id',
                    fy_name='$fy_name',
                    start_date='$start_date',
                    end_date='$end_date',
                    target_amount='$target_amount',
                    status='$status'
                WHERE fy_id='$id'
            ");
        }
    

}