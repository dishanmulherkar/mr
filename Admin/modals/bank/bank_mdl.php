<?php
class bank_mdl
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getByBank($bank_id)
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
            "SELECT * FROM banks WHERE bank_id='$id'"
        );
    }

    public function getAll()
    {
        return mysqli_query($this->con, "SELECT * FROM `banks`");
    }

    public function insert($data, $status)
    {
        $pn  = mysqli_real_escape_string($this->con, trim($data['bank_name']));
        $pk  = mysqli_real_escape_string($this->con, intval($data['bank_id']));
       $status = mysqli_real_escape_string($this->con, $data['status']);

        return mysqli_query($this->con, "
            INSERT INTO banks (bank_name, bank_id, is_active) 
            VALUES ('$pn', '$pk', '$status')
        ");
    }

    public function update($id, $data)
    {
        $id = (int)$id;
        $bank_name = mysqli_real_escape_string($this->con, trim($data['bank_name']));
        $status = mysqli_real_escape_string($this->con, $data['status']);

        return mysqli_query($this->con, "
            UPDATE banks
            SET
                bank_name='$bank_name',
                is_active='$status'
            WHERE bank_id='$id'
        ");
    }
    public function delete($id)
    {
        $id = intval($id);
        return mysqli_query($this->con, "DELETE FROM banks WHERE bank_id = '$id'");
    }

}