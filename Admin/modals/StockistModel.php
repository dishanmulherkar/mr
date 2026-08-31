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
            LEFT JOIN headquarter hq
                ON hq.headquarter_id = stockists.hq_id
            LEFT JOIN state
                ON state.state_id = stockists.state
            WHERE stockists.admin_id = $admin_id
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
        $hq_id = intval($data['hq_id']);
         $admin_id = (int)$_SESSION['admin_id'];

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


            if ($stockist_state == 'Nepal') {
                $gst_type = 'VAT';
            } elseif ($super_state == $stockist_state) {
                $gst_type = 'CGST_SGST';
            } else {
                $gst_type = 'IGST';
            }

        return mysqli_query($this->con, "
            INSERT INTO stockists
            (
                stockist_name,
                number,
                gst_no,
                gst_type,
                dispatch_to,
                transport,
                status,
                state,
                district,
                pincode,
                hq_id,
                address,
                stockist_image,
                admin_id,
                pan_no,
                dl_no
            )
            VALUES
            (
                '".$data['stockist_name']."',
                '".$data['number']."',
                '".$data['gst_no']."',
                '".$gst_type."',
                '".$data['dispatch_to']."',
                '".$data['transport']."',
                '".$data['status']."',
                '".$data['state']."',
                '".$data['district']."',
                '".$data['pincode']."',
                '".$data['hq_id']."',
                '".$data['address']."',
                '$image',
                '$admin_id',
                '".$data['pan_no']."',
                '".$data['dl_no']."'

            )
        ");
    }

    public function update($id, $data, $image)
    {

    $admin_id = (int)$_SESSION['admin_id'];
    $hq_id = (int)$data['hq_id'];

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

    // echo "<pre>";
    // print_r("Super State: " . $super_state);
    // print_r("\nStockist State: " . $stockist_state);
    // echo "</pre>";
    // exit;

            if ($stockist_state == 'Nepal' AND $super_state == 'Nepal') {
                $gst_type = 'VAT';
            } elseif ($super_state == $stockist_state) {
                $gst_type = 'CGST_SGST';
            } else {
                $gst_type = 'IGST';
            }
        return mysqli_query($this->con, "
            UPDATE stockists SET
                stockist_name='".$data['stockist_name']."',
                number='".$data['number']."',
                gst_no='".$data['gst_no']."',
                gst_type='".$gst_type."',
                dispatch_to='".$data['dispatch_to']."',
                transport='".$data['transport']."',
                status='".$data['status']."',
                state='".$data['state']."',
                district='".$data['district']."',
                pincode='".$data['pincode']."',
                hq_id='".$data['hq_id']."',
                admin_id='$admin_id',
                address='".$data['address']."',
                stockist_image='$image',
                 pan_no ='".$data['pan_no']."',
                dl_no = '".$data['dl_no']."'
            WHERE stockist_id='$id'
        ");
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