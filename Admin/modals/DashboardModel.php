<?php
class DashboardModel {
    private $db;

    public function __construct($con) {
        $this->db = $con;
    }

    public function TotalStockists()
    {
        if ($_SESSION['admin_role'] == 'Super Admin') {

            $sql = "SELECT COUNT(*) AS total_stockists
                    FROM stockists";

        } else {

            $adminId = (int)$_SESSION['admin_id'];

            $sql = "SELECT COUNT(*) AS total_stockists
                    FROM stockists s
                    INNER JOIN admin_state ast
                        ON s.state = ast.state_id
                    WHERE ast.admin_id = '$adminId'";
        }

        $result = mysqli_query($this->db, $sql);

        return mysqli_fetch_assoc($result);
    }



   public function TotalCustomers()
    {
        if ($_SESSION['admin_role'] == 'Super Admin') {

            $sql = "SELECT COUNT(*) AS total_customers
                    FROM customers";

        } else {

            $adminId = (int)$_SESSION['admin_id'];

            $sql = "SELECT COUNT(*) AS total_customers
                    FROM customers c
                    INNER JOIN admin_state ast
                        ON c.state = ast.state_id
                    WHERE ast.admin_id = '$adminId'";
        }

        $result = mysqli_query($this->db, $sql);

        return mysqli_fetch_assoc($result);
    }


    public function TotalStates()
    {
        if ($_SESSION['admin_role'] == 'Super Admin') {

            $sql = "SELECT COUNT(*) AS total_states
                    FROM state
                    WHERE state_status = 1";

        } else {

            $adminId = (int)$_SESSION['admin_id'];

            $sql = "SELECT COUNT(*) AS total_states
                    FROM admin_state
                    WHERE admin_id = '$adminId'";
        }

        $result = mysqli_query($this->db, $sql);

        return mysqli_fetch_assoc($result);
    }

    public function TotalProducts()
    {
        if ($_SESSION['admin_role'] == 'Super Admin') {

            $sql = "SELECT COUNT(DISTINCT pb.product_id) AS total_products
                    FROM product_batches pb
                    INNER JOIN products p
                        ON pb.product_id = p.p_id";

        } else {

            $adminId = (int)$_SESSION['admin_id'];

            $sql = "SELECT COUNT(DISTINCT pb.product_id) AS total_products
                    FROM product_batches pb
                    INNER JOIN products p
                        ON pb.product_id = p.p_id
                    INNER JOIN admin_state ast
                        ON pb.state_id = ast.state_id
                    WHERE ast.admin_id = '$adminId'";
        }

        $result = mysqli_query($this->db, $sql);

        return mysqli_fetch_assoc($result);
    }
    
     public function TotalHeadQuarters()
    {
        if ($_SESSION['admin_role'] == 'Super Admin') {

            $sql = "SELECT COUNT(*) AS total_headquarters
                    FROM mr_users";

        } else {

            $adminId = (int)$_SESSION['admin_id'];

            $sql = "SELECT COUNT(*) AS total_headquarters
                    FROM mr_users h
                    INNER JOIN admin_state ast
                        ON h.state = ast.state_id
                    WHERE ast.admin_id = '$adminId'";
        }

        $result = mysqli_query($this->db, $sql);

        return mysqli_fetch_assoc($result);
    }
    

}
?>