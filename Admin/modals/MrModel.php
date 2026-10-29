<?php

class MrModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getAll()
    {
        if ($_SESSION['admin_role'] == 'Super Admin') {

            return mysqli_query(
                $this->con,
                "SELECT
                    u.*,
                    s.state_name,
                    h.hq_name
                FROM mr_users u
                LEFT JOIN state s
                    ON u.state = s.state_id
                LEFT JOIN headquarter h
                    ON u.hq_id = h.headquarter_id
                ORDER BY u.m_id DESC"
            );
        }

        $admin_id = (int)$_SESSION['admin_id'];

        return mysqli_query(
            $this->con,
            "SELECT
                u.*,
                s.state_name,
                h.hq_name
            FROM mr_users u
            INNER JOIN admin_state ast
                ON u.state = ast.state_id
            LEFT JOIN state s
                ON u.state = s.state_id
            LEFT JOIN headquarter h
                ON u.hq_id = h.headquarter_id
            WHERE ast.admin_id = $admin_id
            ORDER BY u.m_id DESC"
        );
    }


    public function getById($id)
    {
        return mysqli_fetch_assoc(
            mysqli_query(
                $this->con,
                "SELECT * FROM mr_users WHERE m_id='$id'"
            )
        );
    }

    public function delete($id)
    {
        return mysqli_query(
            $this->con,
            "DELETE FROM mr_users WHERE m_id='$id'"
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

     public function getDistrictsByState($state_id)
    {
        $state_id = mysqli_real_escape_string($this->con, $state_id);
        return mysqli_query(
            $this->con,
            "SELECT * FROM district WHERE state_id='$state_id' AND district_status=1"
        );
    }

      public function getHQ()
    {
        // $state_id = mysqli_real_escape_string($this->con, $state_id);
        return mysqli_query(
            $this->con,
            "SELECT * FROM headquarter"
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
    
   public function insert($data)
    {
        // Safe check for admin_id to support raw NULL or int
        $admin_val = (!empty($_SESSION['admin_id'])) ? intval($_SESSION['admin_id']) : "NULL";

        $hq_name         = mysqli_real_escape_string($this->con, $data['hq'] ?? '');
        $mr_name         = mysqli_real_escape_string($this->con, $data['mr_name'] ?? '');
        $mobile          = mysqli_real_escape_string($this->con, $data['mobile'] ?? '');
        $email           = mysqli_real_escape_string($this->con, $data['email'] ?? '');
        $state           = mysqli_real_escape_string($this->con, $data['state'] ?? '');
        $district        = mysqli_real_escape_string($this->con, $data['district'] ?? '');
        $pincode         = mysqli_real_escape_string($this->con, $data['pincode'] ?? '');
        $address         = mysqli_real_escape_string($this->con, $data['address'] ?? '');
        $password        = mysqli_real_escape_string($this->con, $data['password'] ?? '');
        $status          = mysqli_real_escape_string($this->con, $data['status'] ?? '1');
        $credit_limit    = (float)($data['credit_limit'] ?? 0);
        $commission_rate = (float)($data['commission_rate'] ?? 0);
        $drc_op          = (float)($data['drc_op'] ?? 0);
        $primary_op      = (float)($data['primary_op'] ?? 0);
        $fy_primary_sale = mysqli_real_escape_string($this->con, $data['fy_primary_sale'] ?? '');

        $sql = "INSERT INTO mr_users
            (admin_id, hq_id, mr_name, mobile, email, state, district, pincode, address, password, status, credit_limit, commission_rate, drc_op, primary_op, fy_primary_sale)
            VALUES
            ($admin_val, '$hq_name', '$mr_name', '$mobile', '$email', '$state', '$district', '$pincode', '$address', '$password', '$status', '$credit_limit', '$commission_rate', '$drc_op', '$primary_op', '$fy_primary_sale')";

        $result = mysqli_query($this->con, $sql);

        if ($result) {
            $new_mr_id = mysqli_insert_id($this->con);

            // Automatically credit opening balance into payment_ledgers if provided
            if ($drc_op > 0) {
                $this->creditDrcOpeningBalanceOnce($new_mr_id, $drc_op);
            }

            return $new_mr_id;
        }

        return false;
    }

    public function update($id, $data)
    {
        $id = (int)$id;
        $admin_val = (!empty($_SESSION['admin_id'])) ? intval($_SESSION['admin_id']) : "NULL";

        $hq_name         = mysqli_real_escape_string($this->con, $data['hq'] ?? '');
        $mr_name         = mysqli_real_escape_string($this->con, $data['mr_name'] ?? '');
        $mobile          = mysqli_real_escape_string($this->con, $data['mobile'] ?? '');
        $email           = mysqli_real_escape_string($this->con, $data['email'] ?? '');
        $state           = mysqli_real_escape_string($this->con, $data['state'] ?? '');
        $district        = mysqli_real_escape_string($this->con, $data['district'] ?? '');
        $pincode         = mysqli_real_escape_string($this->con, $data['pincode'] ?? '');
        $address         = mysqli_real_escape_string($this->con, $data['address'] ?? '');
        $password        = mysqli_real_escape_string($this->con, $data['password'] ?? '');
        $status          = mysqli_real_escape_string($this->con, $data['status'] ?? '1');
        $credit_limit    = (float)($data['credit_limit'] ?? 0);
        $commission_rate = (float)($data['commission_rate'] ?? 0);
        $drc_op          = (float)($data['drc_op'] ?? 0);
        $primary_op      = (float)($data['primary_op'] ?? 0);
        $fy_primary_sale = mysqli_real_escape_string($this->con, $data['fy_primary_sale'] ?? '');

        $sql = "UPDATE mr_users SET
                admin_id = $admin_val,
                hq_id = '$hq_name',
                mr_name = '$mr_name',
                mobile = '$mobile',
                email = '$email',
                state = '$state',
                district = '$district',
                pincode = '$pincode',
                address = '$address',
                password = '$password',
                status = '$status',
                credit_limit = '$credit_limit',
                commission_rate = '$commission_rate',
                drc_op = '$drc_op',
                primary_op = '$primary_op',
                fy_primary_sale = '$fy_primary_sale'
            WHERE m_id = '$id'";

        $result = mysqli_query($this->con, $sql);

        if ($result && $drc_op > 0) {
            // creditDrcOpeningBalanceOnce checks for existing entries,
            // so this safely runs only if it was never credited before.
            $this->creditDrcOpeningBalanceOnce($id, $drc_op);
        }

        return $result;
    }

    public function creditDrcOpeningBalanceOnce($id, $amount) 
    {
        try {
            $id = (int)$id;
            $amount = (float)$amount;

            if ($amount <= 0) {
                return ['success' => true, 'msg' => 'No opening balance to credit.'];
            }

            // 1. Prevent duplicate entries directly from the ledger
            $stmt_check = $this->con->prepare("
                SELECT id FROM payment_ledgers 
                WHERE user_id = ? AND ledger_type = 'drc_wallet' AND transaction_type = 'opening_balance' 
                LIMIT 1
            ");
            $stmt_check->bind_param("i", $id);
            $stmt_check->execute();
            $exists = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();

            if ($exists) {
                return ['success' => false, 'msg' => 'Opening balance has already been credited.'];
            }

            // 2. Insert into payment_ledgers
            $stockist_id  = 0;
            $reference_id = 0;
            $action       = 'increase';
            $notes        = 'Initial DRC Opening Balance Credited';

            $stmt = $this->con->prepare("
                INSERT INTO payment_ledgers 
                (user_id, stockist_id, ledger_type, transaction_type, reference_id, amount, balance_action, notes) 
                VALUES (?, ?, 'drc_wallet', 'opening_balance', ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiidss", $id, $stockist_id, $reference_id, $amount, $action, $notes);

            if (!$stmt->execute()) {
                throw new Exception("Ledger insert error: " . $stmt->error);
            }
            $stmt->close();

            return ['success' => true, 'msg' => 'DRC Opening balance added to wallet.'];

        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }
}