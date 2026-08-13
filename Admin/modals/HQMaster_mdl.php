<?php

class HeadquarterModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    // Fetch all headquarters with joined state and district names for the table list
    public function getAllHeadquarters()
    {
        return mysqli_query($this->con, "
            SELECT 
                h.headquarter_id, 
                h.hq_name, 
                s.state_name, 
                h.district,
                h.state_id
            FROM headquarter h
            LEFT JOIN state s ON h.state_id = s.state_id
            ORDER BY h.headquarter_id DESC
        ");
    }

    // Fetch single headquarter by ID for editing
 public function getHeadquarterById($id)
    {
        $id = (int)$id;
        $query = mysqli_query($this->con, "
            SELECT 
                h.*, 
                ss.ss_name AS super_stockist_name ,
                ss.super_stockist_id
            FROM headquarter h
            LEFT JOIN super_stockist ss 
                ON h.super_stockist_id = ss.super_stockist_id
            WHERE h.headquarter_id = '$id' 
            LIMIT 1
        ");
        return mysqli_fetch_assoc($query);
    }
    // Fetch all states for dropdown
    public function getStates()
    {
        return mysqli_query($this->con, "SELECT state_id, state_name FROM state  WHERE state_status = 1 ORDER BY state_name ASC");
    }

    // Store new headquarter
    public function store($post)
    {
        $hq_name  = mysqli_real_escape_string($this->con, trim($post['hq_name'] ?? ''));
        $state_id = (int)($post['state_id'] ?? 0);
        $district = mysqli_real_escape_string($this->con, trim($post['district'] ?? '')); // Using district name string
        $super_stockist_id  =  (int)($post['sup_stockist'] ?? 0);
        $asm  =  (int)($post['asm'] ?? 0);

        if (empty($hq_name) || $state_id <= 0 || empty($district)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        // Optional: Check duplicate HQ name
        $check = mysqli_query($this->con, "SELECT headquarter_id FROM headquarter WHERE hq_name = '$hq_name' LIMIT 1");
        if (mysqli_num_rows($check) > 0) {
            return ['success' => false, 'duplicate' => true];
        }

        $query = "
            INSERT INTO headquarter (hq_name, state_id, district,super_stockist_id,asm_id) 
            VALUES ('$hq_name', '$state_id', '$district','$super_stockist_id','$asm')
        ";

        if (mysqli_query($this->con, $query)) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => mysqli_error($this->con)];
        }
    }

    // Update existing headquarter
    public function update($post)
    {
        $id       = (int)($post['headquarter_id'] ?? 0);
        $hq_name  = mysqli_real_escape_string($this->con, trim($post['hq_name'] ?? ''));
        $state_id = (int)($post['state_id'] ?? 0);
        $district = mysqli_real_escape_string($this->con, trim($post['district'] ?? ''));
        $super_stockist_id  =  (int)($post['sup_stockist'] ?? 0);
        $asm  =  (int)($post['asm'] ?? 0);

        if ($id <= 0 || empty($hq_name) || $state_id <= 0 || empty($district)) {
            return ['success' => false, 'message' => 'Invalid data provided.'];
        }

        $query = "
            UPDATE headquarter SET 
                hq_name = '$hq_name',
                state_id = '$state_id',
                district = '$district',
                super_stockist_id = '$super_stockist_id',
                asm_id = '$asm'
            WHERE headquarter_id = '$id'
        ";

        if (mysqli_query($this->con, $query)) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => mysqli_error($this->con)];
        }
    }

    // Delete headquarter
    public function delete($id)
    {
        $id = (int)$id;
        return mysqli_query($this->con, "DELETE FROM headquarter WHERE headquarter_id = '$id'");
    }

      public function getDistrictsByState($state_id)
    {
        $state_id = mysqli_real_escape_string($this->con, $state_id);
        return mysqli_query(
            $this->con,
            "SELECT * FROM district WHERE state_id='$state_id' AND district_status=1"
        );
    }

      public function getHqByState($state_id)
    {
        $state_id = (int)$state_id;
        return mysqli_query(
            $this->con,
            "SELECT headquarter_id, hq_name FROM headquarter WHERE state_id = '$state_id' ORDER BY hq_name ASC"
        );
    }

    public function getSuperStockist()
    {
        return mysqli_query($this->con, "
            SELECT *
            FROM super_stockist
            ORDER BY ss_name ASC
        ");
    }
     public function getASM()
    {
        return mysqli_query($this->con, "
            SELECT admin_id, admin_name
            FROM admins where role = 'ASM'
            ORDER BY admin_name ASC
        ");
    }
}