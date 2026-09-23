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
                        h.mr_name,
                        s.state_name
                    FROM financial_year fy
                    INNER JOIN mr_users h 
                        ON fy.mr_id = h.m_id
                    INNER JOIN headquarter hq 
                        ON hq.headquarter_id = fy.hq_id
                    INNER JOIN state s 
                        ON hq.state_id = s.state_id
                    ORDER BY fy.created_at DESC"
                );
            }

            $admin_id = (int)$_SESSION['admin_id'];

            return mysqli_query(
                $this->con,
                "SELECT
                    fy.*,
                    h.mr_name,
                    s.state_name
                FROM financial_year fy
                INNER JOIN mr_users h 
                    ON fy.mr_id = h.m_id
                INNER JOIN headquarter hq 
                    ON hq.headquarter_id = fy.hq_id
                INNER JOIN state s 
                    ON hq.state_id = s.state_id
                INNER JOIN admin_state ast 
                    ON s.state_id = ast.state_id
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

            $query = mysqli_query($this->con, "
                SELECT state_id
                FROM headquarter
                WHERE headquarter_id = '$hq_id'
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
            $mr_id         = (int)$post['mr_id'];
            $fy_name       = mysqli_real_escape_string($this->con, trim($post['fy_name']));
            $start_date    = mysqli_real_escape_string($this->con, $post['start_date']);
            $end_date      = mysqli_real_escape_string($this->con, $post['end_date']);
            $target_amount = (float)$post['target_amount'];
            $status        = (int)$post['status'];

            // If setting this FY to Active (1), deactivate only the previous FYs for this specific MR
            if ($status == 1) {
                mysqli_query($this->con, "
                    UPDATE financial_year
                    SET status = '0'
                    WHERE mr_id = '$mr_id'
                ");
            }

            // Direct insert without blocking duplicate names
            $insert = mysqli_query($this->con, "
                INSERT INTO financial_year
                (
                    hq_id,
                    mr_id,
                    fy_name,
                    start_date,
                    end_date,
                    target_amount,
                    status
                )
                VALUES
                (
                    '$hq_id',
                    '$mr_id',
                    '$fy_name',
                    '$start_date',
                    '$end_date',
                    '$target_amount',
                    '$status'
                )
            ");

            return $insert;
        }

        public function update($id, $post)
        {
            $id            = (int)$id;
            $hq_id         = (int)$post['hq_id'];
            $mr_id         = (int)$post['mr_id'];
            $fy_name       = mysqli_real_escape_string($this->con, trim($post['fy_name']));
            $start_date    = mysqli_real_escape_string($this->con, $post['start_date']);
            $end_date      = mysqli_real_escape_string($this->con, $post['end_date']);
            $target_amount = (float)$post['target_amount'];
            $status        = (int)$post['status'];

            // If setting this FY to Active (1), deactivate other FYs for this MR
            if ($status == 1) {
                mysqli_query($this->con, "
                    UPDATE financial_year
                    SET status = '0'
                    WHERE mr_id = '$mr_id'
                    AND fy_id != '$id'
                ");
            }

            // Direct update
            return mysqli_query($this->con, "
                UPDATE financial_year
                SET
                    hq_id         = '$hq_id',
                    mr_id         = '$mr_id',
                    fy_name       = '$fy_name',
                    start_date    = '$start_date',
                    end_date      = '$end_date',
                    target_amount = '$target_amount',
                    status        = '$status'
                WHERE fy_id       = '$id'
            ");
        }

        public function getMrByHq($hq_id)
        {
            $hq_id = intval($hq_id);
            return mysqli_query(
                $this->con, 
                "SELECT m_id, mr_name FROM mr_users WHERE hq_id = '$hq_id' AND status = '1' ORDER BY mr_name ASC"
            );
        }

}