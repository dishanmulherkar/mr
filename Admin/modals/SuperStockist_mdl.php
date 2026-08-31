<?php

class SupplierModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getAll()
    {
        return mysqli_query($this->con, "
            SELECT *
            FROM super_stockist
            ORDER BY ss_name ASC
        ");
    }

    public function getById($id)
    {
        $id = intval($id);
        $res = mysqli_query($this->con, "SELECT * FROM super_stockist WHERE super_stockist_id = '$id'");
        return mysqli_fetch_assoc($res);
    }

    public function insert($data)
    {
        $stockist_name  = mysqli_real_escape_string($this->con, trim($data['ss_name']));
        $person_name    = mysqli_real_escape_string($this->con, trim($data['person_name']));
        $country        = mysqli_real_escape_string($this->con, trim($data['country']));
        $state_id       = mysqli_real_escape_string($this->con, intval($data['state']));
        $district       = mysqli_real_escape_string($this->con, trim($data['district']));
        $pincode        = mysqli_real_escape_string($this->con, trim($data['pincode']));
        $status         = mysqli_real_escape_string($this->con, intval($data['status']));
        
        // FIX: Removed intval() from address and gst_no so text doesn't get destroyed
        $address        = mysqli_real_escape_string($this->con, trim($data['address'] ?? ''));
        $gst_no         = mysqli_real_escape_string($this->con, trim($data['gst_no'] ?? ''));
        $terms          = mysqli_real_escape_string($this->con, $data['terms_and_conditions'] ?? '');

        // NEW: Get the bank_ids array from the form submission
        $bank_ids       = $data['bank_ids'] ?? [];

        // Insert without party_code
        $insert = mysqli_query($this->con, "
            INSERT INTO super_stockist
            (ss_name, person_name, country, state, district, pincode, status, address, term_and_condition, gst_no)
            VALUES
            ('$stockist_name', '$person_name', '$country', '$state_id', '$district', '$pincode', '$status', '$address', '$terms', '$gst_no')
        ");

        if (!$insert) {
            return false;
        }

        // NEW: Get the inserted Super Stockist ID and sync the selected banks
        $super_stockist_id = mysqli_insert_id($this->con);
        $this->syncSuperStockistBanks($super_stockist_id, $bank_ids);

        return true;
    }

    public function update($id, $data)
    {
        $id             = intval($id);
        $stockist_name  = mysqli_real_escape_string($this->con, trim($data['ss_name']));
        $person_name    = mysqli_real_escape_string($this->con, trim($data['person_name']));
        $country        = mysqli_real_escape_string($this->con, trim($data['country']));
        $state_id       = mysqli_real_escape_string($this->con, intval($data['state']));
        $district       = mysqli_real_escape_string($this->con, trim($data['district']));
        $pincode        = mysqli_real_escape_string($this->con, trim($data['pincode']));
        $status         = mysqli_real_escape_string($this->con, intval($data['status']));
        
        // FIX: Removed intval() from gst_no
        $address        = mysqli_real_escape_string($this->con, trim($data['address'] ?? ''));
        $gst_no         = mysqli_real_escape_string($this->con, trim($data['gst_no'] ?? ''));
        $terms          = mysqli_real_escape_string($this->con, $data['terms_and_conditions'] ?? '');

        // NEW: Get the bank_ids array from the form submission
        $bank_ids       = $data['bank_ids'] ?? [];

        $update = mysqli_query($this->con, "
            UPDATE super_stockist SET 
                ss_name='$stockist_name', person_name='$person_name', country='$country', 
                state='$state_id', district='$district', pincode='$pincode', status='$status', 
                address='$address', gst_no='$gst_no', term_and_condition='$terms'
            WHERE super_stockist_id = '$id' 
        ");

        if ($update) {
            // NEW: Sync the banks for this stockist
            $this->syncSuperStockistBanks($id, $bank_ids);
            return true;
        }
        
        return false;
    }

    public function syncSuperStockistBanks($super_stockist_id, $bank_ids)
    {
        // 1. Delete existing banks for this stockist to start fresh
        $del_sql = "DELETE FROM super_stockist_banks WHERE super_stockist_id = ?";
        $stmt_del = $this->con->prepare($del_sql);
        $stmt_del->bind_param("i", $super_stockist_id);
        $stmt_del->execute();
        $stmt_del->close();

        // 2. Insert the newly selected banks
        if (!empty($bank_ids)) {
            $ins_sql = "INSERT INTO super_stockist_banks (super_stockist_id, bank_id) VALUES (?, ?)";
            $stmt_ins = $this->con->prepare($ins_sql);
            
            foreach ($bank_ids as $b_id) {
                $b_id = (int)$b_id;
                $stmt_ins->bind_param("ii", $super_stockist_id, $b_id);
                $stmt_ins->execute();
            }
            $stmt_ins->close();
        }
        
        return true;
    }
    public function delete($id)
    {
        $id = intval($id);
        return mysqli_query($this->con, "DELETE FROM super_stockist WHERE super_stockist_id = '$id'");
    }

    public function checkDuplicate($name, $id = null)
    {
        $name = mysqli_real_escape_string($this->con, $name);
        $query = "SELECT super_stcokist_id FROM super_stockist WHERE ss_name = '$name'";
        if ($id) $query .= " AND super_stockist_id != '" . intval($id) . "'";
        return mysqli_query($this->con, $query);
    }


    public function exists($party_name)
    {
        $pn = mysqli_real_escape_string($this->con, $party_name);
        $res = mysqli_query($this->con, "SELECT p_id FROM parties WHERE party_name = '$pn'");
        return mysqli_num_rows($res) > 0;
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

      public function getDistrictsByState($state_id)
    {
        $state_id = mysqli_real_escape_string($this->con, $state_id);
        return mysqli_query(
            $this->con,
            "SELECT * FROM district WHERE state_id='$state_id' AND district_status=1"
        );
    }

    public function getAllBanks()
    {
        $sql = "SELECT bank_id, bank_name FROM banks WHERE is_active = 1 ORDER BY bank_name ASC";
        $result = $this->con->query($sql);
        $banks = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $banks[] = $row;
            }
        }
        return $banks;
    }

    /**
     * Get an array of bank_ids currently assigned to a Super Stockist (for editing)
     */
    public function getSuperStockistBanks($super_stockist_id)
    {
        $sql = "SELECT bank_id FROM super_stockist_banks WHERE super_stockist_id = ?";
        $stmt = $this->con->prepare($sql);
        $stmt->bind_param("i", $super_stockist_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $selected = [];
        while ($row = $result->fetch_assoc()) {
            $selected[] = $row['bank_id'];
        }
        $stmt->close();
        
        return $selected;
    }
}