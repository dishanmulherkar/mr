<?php
class StateModel
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
             WHERE state_id='$state_id'"
        );
    }

    public function edit($id)
    {
        return mysqli_query(
            $this->con,
            "SELECT * FROM state WHERE state_id='$id'"
        );
    }

    public function getAll()
    {
        return mysqli_query($this->con, "SELECT * FROM `state`");
    }

    public function insert($data, $status)
    {
        $pn  = mysqli_real_escape_string($this->con, trim($data['state_name']));
        $pk  = mysqli_real_escape_string($this->con, intval($data['state_id']));
       $status = mysqli_real_escape_string($this->con, $data['status']);

        return mysqli_query($this->con, "
            INSERT INTO state (state_name, state_id, state_status) 
            VALUES ('$pn', '$pk', '$status')
        ");
    }

    public function update($id, $data)
    {
        $id = (int)$id;
        $state_name = mysqli_real_escape_string($this->con, trim($data['state_name']));
        $status = mysqli_real_escape_string($this->con, $data['status']);

        return mysqli_query($this->con, "
            UPDATE state
            SET
                state_name='$state_name',
                state_status='$status'
            WHERE state_id='$id'
        ");
    }
    public function delete($id)
    {
        $id = intval($id);
        return mysqli_query($this->con, "DELETE FROM state WHERE state_id = '$id'");
    }

}