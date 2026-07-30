<?php
class DistrictModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getByState($state_id)
    {
        return mysqli_query(
            $this->con,
            "SELECT * FROM district
             WHERE state_id='$state_id' AND district_status=1"
        );
    }

    public function edit($id)
    {
        return mysqli_query(
            $this->con,
            "SELECT * FROM district WHERE district_id='$id'"
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

    public function getAll()
    {
        if ($_SESSION['admin_role'] == 'Super Admin') {

            return mysqli_query($this->con, "
                SELECT
                    d.*,
                    s.state_name
                FROM district d
                INNER JOIN state s
                    ON d.state_id = s.state_id
                WHERE s.state_status = 1
                ORDER BY d.district_id DESC
            ");
        }

        $admin_id = (int)$_SESSION['admin_id'];

        return mysqli_query($this->con, "
            SELECT
                d.*,
                s.state_name
            FROM district d
            INNER JOIN state s
                ON d.state_id = s.state_id
            INNER JOIN admin_state ast
                ON d.state_id = ast.state_id
            WHERE s.state_status = 1
            AND ast.admin_id = $admin_id
            ORDER BY d.district_id DESC
        ");
    }


    public function insert($data, $status)
    {
        $admin_id = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : "NULL";
        $pn  = mysqli_real_escape_string($this->con, trim($data['district_name']));
        $pk  = mysqli_real_escape_string($this->con, trim($data['state_id']));
        $status = intval($this->con, trim($data['status']));
       

        return mysqli_query($this->con, "
            INSERT INTO district (district_name, state_id,admin_id, district_status) 
            VALUES ('$pn', '$pk','$admin_id', $status)
        ");
    }

    public function update($id, $data, $status)
    {
        $id  = intval($id);
        $pn  = mysqli_real_escape_string($this->con, trim($data['district_name']));
        $pk  = mysqli_real_escape_string($this->con, trim($data['state_id']));
        $status = intval($this->con, trim($data['status']));
        
        return mysqli_query($this->con, "
            UPDATE district SET 
                district_name='$pn', state_id='$pk', district_status=$status
            WHERE district_id = '$id'
        ");
    }

    public function delete($id)
    {
        $id = intval($id);
        return mysqli_query($this->con, "DELETE FROM district WHERE district_id = '$id'");
    }

    public function isDistrictDuplicate($district_name, $state_id)
    {
        $district_name = mysqli_real_escape_string($this->con, $district_name);
        $state_id = (int)$state_id;

        $q = mysqli_query($this->con, "
            SELECT district_id
            FROM district
            WHERE district_name = '$district_name'
            AND state_id = '$state_id'
        ");

        return mysqli_num_rows($q) > 0;
    }

    public function insertDistrict($district_name, $state_id)
    {
        $admin_id = $_SESSION['admin_id'];

        $district_name = mysqli_real_escape_string($this->con, $district_name);
        $state_id = (int)$state_id;

        return mysqli_query($this->con, "
            INSERT INTO district
            (
                district_name,
                state_id,
                admin_id,
                district_status
            )
            VALUES
            (
                '$district_name',
                '$state_id',
                '$admin_id',
                '1'
            )
        ");
    }

}