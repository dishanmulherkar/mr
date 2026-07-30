<?php

class InwardReportModel
{
    private $con;

    public function __construct($con) 
    {
        $this->con = $con;
    }

    // Fetch all states for the first dropdown
    public function getStates()
    {
        // Super Admin can see all states
        if ($_SESSION['admin_role'] == 'Super Admin') {

            return mysqli_query(
                $this->con ,
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

    // Fetch HQs if a state is selected (for page reload retention)
    public function getHQsByState(int $state_id) 
    {
        $stmt = $this->con->prepare("SELECT m_id, hq_name FROM mr_users WHERE state = ?");
        $stmt->bind_param("i", $state_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Fetch Stockists if an HQ is selected (for page reload retention)
    public function getStockistsByHq(int $hq_id) 
    {
        $stmt = $this->con->prepare("SELECT stockist_id, stockist_name FROM stockists WHERE hq_id = ?");
        $stmt->bind_param("i", $hq_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Fetch a single Stockist's name
    public function getStockistName(int $stockist_id): string 
    {
        if ($stockist_id <= 0) return '';
        
        $stmt = $this->con->prepare("SELECT stockist_name FROM stockists WHERE stockist_id = ?");
        $stmt->bind_param("i", $stockist_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row ? $row['stockist_name'] : '';
    }

    // Fetch a single HQ's name
    public function getHqName(int $hq_id): string 
    {
        if ($hq_id <= 0) return '';
        
        $stmt = $this->con->prepare("SELECT hq_name FROM mr_users WHERE m_id = ?");
        $stmt->bind_param("i", $hq_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row ? $row['hq_name'] : '';
    }

    // Fetch the main table report data
    public function getReportData(int $stockist_id, string $from_date = '', string $to_date = '')
    {
        if ($stockist_id <= 0) {
            return false;
        }
    
        $query = "SELECT
                        si.*,
                        s.stockist_name
                  FROM stock_inward si
                  LEFT JOIN stockists s
                        ON s.stockist_id = si.stockist_id
                  WHERE si.stockist_id = ?";
    
        if (!empty($from_date) && !empty($to_date)) {
            $query .= " AND DATE(si.inward_date) BETWEEN ? AND ?";
        }
    
        $query .= " ORDER BY si.inward_date DESC";
    
        $stmt = $this->con->prepare($query);
    
        if (!$stmt) {
            die("Prepare Failed : " . $this->con->error);
        }
    
        if (!empty($from_date) && !empty($to_date)) {
            $stmt->bind_param("iss", $stockist_id, $from_date, $to_date);
        } else {
            $stmt->bind_param("i", $stockist_id);
        }
    
        if (!$stmt->execute()) {
            die("Execute Failed : " . $stmt->error);
        }
    
        return $stmt->get_result();
    }

    // Note: The following methods are added to fetch the header and product details for a specific inward record.
    public function getInwardHeader(int $inward_id) 
    {
        if ($inward_id <= 0) return false;

        $stmt = $this->con->prepare("
            SELECT s.stockist_name, si.inward_date
            FROM stock_inward si
            INNER JOIN stockists s ON s.stockist_id = si.stockist_id
            WHERE si.inward_id = ?
        ");
        $stmt->bind_param("i", $inward_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    // Fetch the detailed list of products for this inward record
    public function getInwardProducts(int $inward_id) 
    {
        if ($inward_id <= 0) return false;

        $stmt = $this->con->prepare("
            SELECT 
                p.product_name, 
                pb.batch_no, 
                pb.pts, 
                d.qty, 
                (d.qty * pb.pts) AS amount, 
                si.stockist_id
            FROM stock_inward si
            INNER JOIN stock_inward_details d ON si.inward_id = d.inward_id
            INNER JOIN products p ON p.p_id = d.p_id
            INNER JOIN product_batches pb ON pb.batch_id = d.batch_id
            WHERE si.inward_id = ?
            ORDER BY p.product_name, pb.batch_no
        ");
        $stmt->bind_param("i", $inward_id);
        $stmt->execute();
        
        return $stmt->get_result(); // Returns the result set to be looped in the view
    }
}