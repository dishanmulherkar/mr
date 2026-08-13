<?php

class DispatchDashboardModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    /**
     * Fetch orders filtered by Super Stockist, Status, and Date
     */
    public function getFilteredOrders($super_stockist_id, $status, $date)
    {
        $super_stockist_id = (int)$super_stockist_id;
        $status = mysqli_real_escape_string($this->con, $status);
        
        // We join stock_inward to securely match the super_stockist_id
        $sql = "
            SELECT 
                o.order_id,
                o.order_date, 
                o.total_amt, 
                o.status,
                s.stockist_name 
            FROM orders o
            JOIN stock_inward si ON o.order_id = si.order_id
            LEFT JOIN stockists s ON o.stockist_id = s.stockist_id
            WHERE o.status = '$status'
            AND si.super_stockist_id = $super_stockist_id
        ";

        // Add date filter if a date is selected
        if (!empty($date)) {
            $safe_date = mysqli_real_escape_string($this->con, $date);
            $sql .= " AND DATE(o.order_date) = '$safe_date' ";
        }

        $sql .= " ORDER BY o.order_date DESC, o.order_id DESC";

        $result = mysqli_query($this->con, $sql);

        if (!$result) {
            die("Database Error: " . mysqli_error($this->con));
        }

        $orders = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $orders[] = $row;
        }

        return $orders;
    }

    /**
     * Fetch Products, Qty, MRP, and Invoice Number for a specific order
     */
    public function getOrderDetails($order_id)
    {
        $order_id = (int)$order_id;
        
        $sql = "
            SELECT 
                si.inward_no, 
                p.product_name, 
                sid.qty, 
                sid.mrp 
            FROM stock_inward si
            JOIN stock_inward_details sid ON si.inward_id = sid.inward_id
            JOIN products p ON sid.p_id = p.p_id
            WHERE si.order_id = $order_id
        ";
        
        $result = mysqli_query($this->con, $sql);
        
        $data = ['inward_no' => 'N/A', 'items' => []];
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data['inward_no'] = $row['inward_no'];
                $data['items'][] = [
                    'product_name' => $row['product_name'],
                    'qty'          => $row['qty'],
                    'mrp'          => $row['mrp']
                ];
            }
            return $data;
        }
        return false;
    }

    /**
     * Update the status of the order to Dispatched
     */
    public function updateOrderStatus($order_id, $status)
    {
        $order_id = (int)$order_id;
        $status = mysqli_real_escape_string($this->con, $status);
        
        $sql = "UPDATE orders SET status = '$status' WHERE order_id = $order_id";
        
        return mysqli_query($this->con, $sql);
    }
}