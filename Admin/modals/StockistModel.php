<?php

class StockistModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

     public function getAll()
    {
        if ($_SESSION['admin_role'] == 'Super Admin') {
            return mysqli_query($this->con, "
                SELECT
                    stockists.*,
                    hq.hq_name,
                    state.state_name
                FROM stockists
                LEFT JOIN headquarter hq
                    ON hq.headquarter_id = stockists.hq_id
                LEFT JOIN state
                    ON state.state_id = stockists.state
                ORDER BY stockists.stockist_id DESC
            ");
        }

        $admin_id = (int)$_SESSION['admin_id'];

        return mysqli_query($this->con, "
            SELECT
                stockists.*,
                hq.hq_name,
                state.state_name
            FROM stockists
            INNER JOIN admin_state ast
                ON stockists.state = ast.state_id
            LEFT JOIN headquarter hq
                ON hq.headquarter_id = stockists.hq_id
            LEFT JOIN state
                ON state.state_id = stockists.state
            WHERE ast.admin_id = $admin_id
            ORDER BY stockists.stockist_id DESC
        ");
    }
    public function getById($id)
    {
        $query=mysqli_query($this->con,"
            SELECT * FROM stockists
            WHERE stockist_id='$id'
        ");

        return mysqli_fetch_assoc($query);
    }

    public function getHQ()
    {
        return mysqli_query($this->con,"
            SELECT m_id,hq_name
            FROM mr_users
            ORDER BY hq_name
        ");
    }

    public function getStateById($id)
    {
        $id = (int)$id;
        
        $result = mysqli_query($this->con, "
            SELECT state_name
            FROM state 
            WHERE state_id = '$id'
            LIMIT 1
        ");

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return $row['state_name']; // Returns just the string (e.g., "Nepal")
        }

        return ''; // Returns empty string if not found
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


    public function checkDuplicate($name,$id=null)
    {
        if($id)
        {
            return mysqli_query($this->con,"
                SELECT *
                FROM stockists
                WHERE stockist_name='$name'
                AND stockist_id!='$id'
            ");
        }

        return mysqli_query($this->con,"
            SELECT *
            FROM stockists
            WHERE stockist_name='$name'
        ");
    }

public function insert($data, $image)
    {
        $this->con->begin_transaction();

        try {
            $hq_id = intval($data['hq_id']);
            $admin_id = (int)$_SESSION['admin_id'];
            $credit_days = isset($data['credit_days']) ? (int)$data['credit_days'] : 0;
            
            // Opening balance inputs
            $opening_balance = isset($data['opening_balance']) ? (float)$data['opening_balance'] : 0.00;
            $opening_balance_type = (!empty($data['opening_balance_type']) && $data['opening_balance_type'] === 'credit') ? 'credit' : 'debt';
            $opening_balance_date = !empty($data['opening_balance_date']) ? $data['opening_balance_date'] : date('Y-m-d');
            $balance_action = ($opening_balance_type === 'debt') ? 'increase' : 'decrease';

            // Fetch Super Stockist State
            $result = mysqli_query($this->con, "
                SELECT ss.state
                FROM headquarter h
                INNER JOIN super_stockist ss
                    ON h.super_stockist_id = ss.super_stockist_id
                WHERE h.headquarter_id = '$hq_id'
                LIMIT 1
            ");

            $super_state = '';
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $super_state = $this->getStateById($row['state'] ?? '');
            }

            $stockist_state = $this->getStateById($data['state']);

            // GST Type Logic
            if ($stockist_state == 'Nepal') {
                $gst_type = 'VAT';
            } elseif ($super_state == $stockist_state) {
                $gst_type = 'CGST_SGST';
            } else {
                $gst_type = 'IGST';
            }

            // 1. Insert into stockists table
            $stockist_stmt = $this->con->prepare("
                INSERT INTO stockists (
                    stockist_name, number, gst_no, gst_type, dispatch_to, 
                    transport, status, state, district, pincode, 
                    hq_id, address, stockist_image, admin_id, pan_no, 
                    dl_no, credit_days, opening_balance, opening_balance_type, opening_balance_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stockist_stmt->bind_param(
                "ssssssssssissiissdss",
                $data['stockist_name'],
                $data['number'],
                $data['gst_no'],
                $gst_type,
                $data['dispatch_to'],
                $data['transport'],
                $data['status'],
                $data['state'],
                $data['district'],
                $data['pincode'],
                $data['hq_id'],
                $data['address'],
                $image,
                $admin_id,
                $data['pan_no'],
                $data['dl_no'],
                $credit_days,
                $opening_balance,
                $opening_balance_type,
                $opening_balance_date
            );

            if (!$stockist_stmt->execute()) {
                throw new Exception("Error inserting stockist: " . $stockist_stmt->error);
            }

            $stockist_id = $this->con->insert_id;
            $stockist_stmt->close();

            // 2. Insert into payment_ledgers if opening balance > 0
            if ($opening_balance > 0) {
                $ledger_stmt = $this->con->prepare("
                    INSERT INTO payment_ledgers (
                        stockist_id, 
                        ledger_type, 
                        transaction_type, 
                        reference_id, 
                        amount, 
                        balance_action
                    ) VALUES (?, ?, 'opening_balance', 0, ?, ?)
                ");
                $ledger_stmt->bind_param("isds", $stockist_id, $opening_balance_type, $opening_balance, $balance_action);

                if (!$ledger_stmt->execute()) {
                    throw new Exception("Error creating ledger entry: " . $ledger_stmt->error);
                }
                $ledger_stmt->close();
            }

            $this->con->commit();
            return true;

        } catch (Exception $e) {
            $this->con->rollback();
            return false;
        }
    }

    public function update($id, $data, $image)
    {
        $this->con->begin_transaction();

        try {
            $id = (int)$id;
            $admin_id = (int)$_SESSION['admin_id'];
            $hq_id = (int)$data['hq_id'];
            $credit_days = isset($data['credit_days']) ? (int)$data['credit_days'] : 0;

            // Opening balance inputs
            $opening_balance = isset($data['opening_balance']) ? (float)$data['opening_balance'] : 0.00;
            $opening_balance_type = (!empty($data['opening_balance_type']) && $data['opening_balance_type'] === 'credit') ? 'credit' : 'debt';
            $opening_balance_date = !empty($data['opening_balance_date']) ? $data['opening_balance_date'] : date('Y-m-d');
            $balance_action = ($opening_balance_type === 'debt') ? 'increase' : 'decrease';

            // Fetch Super Stockist State
            $result = mysqli_query($this->con, "
                SELECT ss.state
                FROM headquarter h
                INNER JOIN super_stockist ss
                    ON h.super_stockist_id = ss.super_stockist_id
                WHERE h.headquarter_id = '$hq_id'
                LIMIT 1
            ");

            $super_state = '';
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $super_state = $this->getStateById($row['state'] ?? '');
            }

            $stockist_state = $this->getStateById($data['state']);

            // GST Type Logic
            if ($stockist_state == 'Nepal' && $super_state == 'Nepal') {
                $gst_type = 'VAT';
            } elseif ($super_state == $stockist_state) {
                $gst_type = 'CGST_SGST';
            } else {
                $gst_type = 'IGST';
            }

            // 1. Update stockists table
            $stockist_stmt = $this->con->prepare("
                UPDATE stockists SET
                    stockist_name = ?,
                    number = ?,
                    gst_no = ?,
                    gst_type = ?,
                    dispatch_to = ?,
                    transport = ?,
                    status = ?,
                    state = ?,
                    district = ?,
                    pincode = ?,
                    hq_id = ?,
                    admin_id = ?,
                    address = ?,
                    stockist_image = ?,
                    pan_no = ?,
                    dl_no = ?,
                    credit_days = ?,
                    opening_balance = ?,
                    opening_balance_type = ?,
                    opening_balance_date = ?
                WHERE stockist_id = ?
            ");

            $stockist_stmt->bind_param(
                "ssssssssssiisssiidssi",
                $data['stockist_name'],
                $data['number'],
                $data['gst_no'],
                $gst_type,
                $data['dispatch_to'],
                $data['transport'],
                $data['status'],
                $data['state'],
                $data['district'],
                $data['pincode'],
                $data['hq_id'],
                $admin_id,
                $data['address'],
                $image,
                $data['pan_no'],
                $data['dl_no'],
                $credit_days,
                $opening_balance,
                $opening_balance_type,
                $opening_balance_date,
                $id
            );

            if (!$stockist_stmt->execute()) {
                throw new Exception("Error updating stockist: " . $stockist_stmt->error);
            }
            $stockist_stmt->close();

            // 2. Check and sync payment_ledgers entry
            $check_ledger = $this->con->prepare("
                SELECT id FROM payment_ledgers 
                WHERE stockist_id = ? AND transaction_type = 'opening_balance' 
                LIMIT 1
            ");
            $check_ledger->bind_param("i", $id);
            $check_ledger->execute();
            $ledger_res = $check_ledger->get_result();

            if ($row = $ledger_res->fetch_assoc()) {
                $ledger_id = (int)$row['id'];
                $upd_ledger = $this->con->prepare("
                    UPDATE payment_ledgers 
                    SET amount = ?, 
                        ledger_type = ?, 
                        balance_action = ? 
                    WHERE id = ?
                ");
                $upd_ledger->bind_param("dssi", $opening_balance, $opening_balance_type, $balance_action, $ledger_id);
                $upd_ledger->execute();
                $upd_ledger->close();
            } elseif ($opening_balance > 0) {
                $ins_ledger = $this->con->prepare("
                    INSERT INTO payment_ledgers (
                        stockist_id, 
                        ledger_type, 
                        transaction_type, 
                        reference_id, 
                        amount, 
                        balance_action
                    ) VALUES (?, ?, 'opening_balance', 0, ?, ?)
                ");
                $ins_ledger->bind_param("isds", $id, $opening_balance_type, $opening_balance, $balance_action);
                $ins_ledger->execute();
                $ins_ledger->close();
            }
            $check_ledger->close();

            $this->con->commit();
            return true;

        } catch (Exception $e) {
            $this->con->rollback();
            return false;
        }
    }

    public function delete($id)
    {
        return mysqli_query($this->con,"
            DELETE FROM stockists
            WHERE stockist_id='$id'
        ");
    }

    public function getImage($id)
    {
        $query=mysqli_query($this->con,"
            SELECT stockist_image
            FROM stockists
            WHERE stockist_id='$id'
        ");

        return mysqli_fetch_assoc($query);
    }

    public function downloadImages()
    {
        if ($_SESSION['admin_role'] != 'Super Admin') {

            $admin_id = (int)$_SESSION['admin_id'];

            $sql = "SELECT s.stockist_id,
                        s.stockist_name,
                        s.stockist_image,
                        st.state_name
                    FROM stockists s
                    INNER JOIN admin_state ast ON ast.state_id = s.state
                    INNER JOIN state st ON st.state_id = s.state
                    WHERE ast.admin_id = $admin_id";

        } else {

            $sql = "SELECT s.stockist_id,
                        s.stockist_name,
                        s.stockist_image,
                        st.state_name
                    FROM stockists s
                    INNER JOIN state st ON st.state_id = s.state";
        }

        $result = mysqli_query($this->con, $sql);

        $zip = new ZipArchive();

        $zipName = "Stockist_Images_" . date("YmdHis") . ".zip";

        if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {

            while ($row = mysqli_fetch_assoc($result)) {

                if (empty($row['stockist_image'])) {
                    continue;
                }

                $path = "uploads/stockist/" . $row['stockist_image'];

                if (!file_exists($path)) {
                    continue;
                }

                $ext = pathinfo($path, PATHINFO_EXTENSION);

                $stockist = preg_replace('/[^A-Za-z0-9 _-]/', '', $row['stockist_name']);
                $state = preg_replace('/[^A-Za-z0-9 _-]/', '', $row['state_name']);

                // Store inside State folder
                $zipPath = $state . "/" . $stockist . "_" . $row['stockist_id'] . "." . $ext;

                $zip->addFile($path, $zipPath);
            }

            $zip->close();

            header("Content-Type: application/zip");
            header("Content-Disposition: attachment; filename=\"$zipName\"");
            header("Content-Length: " . filesize($zipName));

            readfile($zipName);

            unlink($zipName);
            exit;
        }

        echo "Unable to create ZIP file.";
    }
}