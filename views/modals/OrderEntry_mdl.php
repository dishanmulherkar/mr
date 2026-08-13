<?php

class orderentry_mdl
{
    private $con;

    public function __construct()
    {
        global $con;
        $this->con = $con;
    }

    public function getStockists($mr_id)
    {
        $status = 1;
        $stmt = $this->con->prepare("
            SELECT 
                s.stockist_id,
                s.stockist_name,
                h.hq_name
            FROM stockists s
            INNER JOIN mr_users m ON m.hq_id = s.hq_id
            LEFT JOIN headquarter h ON h.headquarter_id = s.hq_id
            WHERE m.m_id = ? AND s.status = ?
            ORDER BY s.stockist_name ASC
        ");

        $stmt->bind_param("ii", $mr_id, $status);
        $stmt->execute();

        $result = $stmt->get_result();

        $stockists = [];

        while ($row = $result->fetch_assoc()) {
            $stockists[] = $row;
        }

        $stmt->close();

        return $stockists;
    }

    public function getSuperStockistIdByMr($mr_id)
    {
        $mr_id = (int)$mr_id;
        $stmt = $this->con->prepare("
            SELECT h.super_stockist_id 
            FROM mr_users u
            INNER JOIN headquarter h ON u.hq_id = h.headquarter_id
            WHERE u.m_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $mr_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return (int)($row['super_stockist_id'] ?? 0);
        }
        $stmt->close();
        return 0;
    }

    public function searchMedicine($super_stockist_id, $q = '')
    {
        if (!$super_stockist_id) {
            return [];
        }

        $stmt = $this->con->prepare("
            SELECT 
                p.p_id,
                p.product_name,
                MAX(pb.sale_rate) AS pts,
                MAX(pb.sale_tax) AS sale_tax,
                SUM(COALESCE(sl.qty_in, 0) - COALESCE(sl.qty_out, 0)) AS total_stock
            FROM stock_ledger sl
            INNER JOIN products p ON p.p_id = sl.p_id
            INNER JOIN product_batches pb ON pb.batch_id = sl.batch_id
            WHERE sl.stockist_id = ? 
            AND sl.stockist_type = 'Super-Stockist'
            AND p.product_name LIKE ?
            GROUP BY p.p_id, p.product_name
            HAVING total_stock > 0
            ORDER BY p.product_name ASC
            LIMIT 50
        ");

        if (!$stmt) {
            return [];
        }

        $like = "%{$q}%";
        $stmt->bind_param("is", $super_stockist_id, $like);
        $stmt->execute();

        $result = $stmt->get_result();
        $products = [];

        while ($row = $result->fetch_assoc()) {
            $products[] = [
                'id'       => (int)$row['p_id'],
                'name'     => $row['product_name'],
                'pts'      => (float)$row['pts'],
                'tax'      => (float)($row['sale_tax'] ?? 0),
                'discount' => 16.66,
                'stock'    => (int)$row['total_stock']
            ];
        }

        $stmt->close();
        return $products;
    }

   public function getOrderById($order_id, $mr_id)
    {
        $order_id = (int)$order_id;
        $mr_id    = (int)$mr_id;

        $stmt = $this->con->prepare("SELECT o.*, s.stockist_name FROM orders o LEFT JOIN stockists s ON o.stockist_id = s.stockist_id WHERE o.order_id = ? AND o.mr_id = ?");
        $stmt->bind_param("ii", $order_id, $mr_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order) return null;

        // UPDATED: Added approved_qty to the select statement
        $stmt = $this->con->prepare("
            SELECT 
                od.product_id,
                p.product_name AS name,
                SUM(od.qty) AS qty,
                SUM(od.approved_qty) AS approved_qty,
                MAX(od.rate) AS pts,
                MAX(od.gst) AS tax,
                MAX(od.discount) AS discount,
                SUM(od.amt) AS amt,
                SUM(od.net_total) AS net_total
            FROM order_details od
            INNER JOIN products p ON p.p_id = od.product_id
            WHERE od.order_id = ?
            GROUP BY od.product_id
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'id'           => (int)$row['product_id'],
                'product_id'   => (int)$row['product_id'],
                'name'         => $row['name'],
                'qty'          => (int)$row['qty'],
                'approved_qty' => $row['approved_qty'] !== null ? (int)$row['approved_qty'] : null, // Null means not reviewed yet
                'pts'          => (float)$row['pts'],
                'tax'          => (float)$row['tax'],
                'discount'     => (float)$row['discount'],
                'amt'          => (float)$row['amt'],
                'net_total'    => (float)$row['net_total']
            ];
        }
        $stmt->close();

        $order['items'] = $items;
        return $order;
    }

    public function saveOrderRecord($data)
    {
        $mr_id       = $data['mr_id'];
        $order_date  = $data['order_date'];
        $stockist_id = $data['stockist_id'];
        $total_amt   = $data['total_amt'];
        $items       = $data['items'];

        if (!$stockist_id || empty($items)) {
            return ['success' => false, 'msg' => 'Missing required fields or empty cart.'];
        }

        try {
            $this->con->begin_transaction();

            $stmt = $this->con->prepare("
                INSERT INTO orders (stockist_id, mr_id, total_amt, order_date)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iids", $stockist_id, $mr_id, $total_amt, $order_date);
            $stmt->execute();
            $order_id = $this->con->insert_id;
            $stmt->close();

            if (!$order_id) {
                throw new Exception("Failed to create order header.");
            }

            $this->allocateAndInsertOrderItems($order_id, $mr_id, $items);

            $this->con->commit();

            return [
                'success'  => true,
                'msg'      => 'Order Saved Successfully.',
                'entry_id' => $order_id
            ];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    public function updateOrderRecord($data)
    {
        $order_id    = (int)$data['order_id'];
        $mr_id       = (int)$data['mr_id'];
        $order_date  = $data['order_date'];
        $stockist_id = (int)$data['stockist_id'];
        $total_amt   = (float)$data['total_amt'];
        $items       = $data['items'];

        if (!$order_id || !$stockist_id || empty($items)) {
            return ['success' => false, 'msg' => 'Missing required fields or empty cart.'];
        }

        try {
            $this->con->begin_transaction();

            $stmt = $this->con->prepare("
                UPDATE orders 
                SET stockist_id = ?, total_amt = ?, order_date = ? 
                WHERE order_id = ? AND mr_id = ?
            ");
            $stmt->bind_param("idsii", $stockist_id, $total_amt, $order_date, $order_id, $mr_id);
            $stmt->execute();
            $stmt->close();

            $delStmt = $this->con->prepare("DELETE FROM order_details WHERE order_id = ?");
            $delStmt->bind_param("i", $order_id);
            $delStmt->execute();
            $delStmt->close();

            $this->allocateAndInsertOrderItems($order_id, $mr_id, $items);

            $this->con->commit();

            return [
                'success'  => true,
                'msg'      => 'Order Updated Successfully.',
                'entry_id' => $order_id
            ];

        } catch (Exception $e) {
            $this->con->rollback();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * NEW PUBLIC METHOD: Call this for Previewing OR Saving.
     * Use $is_save = false for preview endpoints (does not lock database rows).
     * Use $is_save = true when actually inserting orders (locks rows FOR UPDATE).
     */
    public function calculateBatchAllocation($super_stockist_id, $product_id, $ordered_qty, $is_save = false)
    {
        $sql = "
            SELECT 
                pb.batch_id,
                pb.sale_rate AS rate,
                pb.sale_tax AS gst,
                pb.disc,
                (SUM(COALESCE(sl.qty_in, 0)) - SUM(COALESCE(sl.qty_out, 0))) AS current_qty
            FROM product_batches pb
            INNER JOIN stock_ledger sl ON sl.batch_id = pb.batch_id
            WHERE sl.stockist_id = ? 
            AND sl.stockist_type = 'Super-Stockist'
            AND pb.product_id = ?
            GROUP BY pb.batch_id
            HAVING current_qty > 0
            ORDER BY pb.expiry_date ASC, pb.batch_id ASC
        ";

        // Only lock rows if this is a live save transaction
        if ($is_save) {
            $sql .= " FOR UPDATE";
        }

        $batchStmt = $this->con->prepare($sql);
        $batchStmt->bind_param("ii", $super_stockist_id, $product_id);
        $batchStmt->execute();
        $batches = $batchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $batchStmt->close();

        $total_available = array_sum(array_column($batches, 'current_qty'));
        if ($total_available < $ordered_qty) {
            throw new Exception("Insufficient stock for product ID {$product_id}: requested {$ordered_qty}, only {$total_available} available.");
        }

        $remaining_qty = $ordered_qty;
        $allocated_batches = [];

        foreach ($batches as $row) {
            if ($remaining_qty <= 0) break;

            $batch_id  = (int)$row['batch_id'];
            $available = (int)$row['current_qty'];

            $alloc_qty = min($remaining_qty, $available);
            if ($alloc_qty <= 0) continue;

            $rate      = (float)$row['rate'];
            $gst       = (float)($row['gst'] ?? 0);
            $discount  = (float)($row['disc'] ?? 0); // Fixed discount percentage

            $net_pts   = $rate * (1 - ($discount / 100));
            $amt       = $alloc_qty * $net_pts;
            $tax_amt   = $amt * ($gst / 100);
            $net_total = $amt + $tax_amt;

            $allocated_batches[] = [
                'batch_id'  => $batch_id,
                'alloc_qty' => $alloc_qty,
                'rate'      => $rate,
                'discount'  => $discount,
                'gst'       => $gst,
                'amt'       => $amt,
                'net_total' => $net_total
            ];

            $remaining_qty -= $alloc_qty;
        }

        if ($remaining_qty > 0) {
            throw new Exception("Batch allocation mismatch for product ID {$product_id}: {$remaining_qty} units left unallocated.");
        }

        return [
            'success'           => true,
            'product_id'        => $product_id,
            'requested_qty'     => $ordered_qty,
            'allocated_batches' => $allocated_batches
        ];
    }

    /**
     * UPDATED: Now delegates math to calculateBatchAllocation()
     */
    private function allocateAndInsertOrderItems($order_id, $mr_id, $items)
    {
        $super_stockist_id = $this->getSuperStockistIdByMr($mr_id);

        foreach ($items as $item) {
            $product_id  = (int)($item['product_id'] ?? $item['id'] ?? 0);
            $ordered_qty = (int)($item['qty'] ?? 0);

            if ($product_id <= 0 || $ordered_qty <= 0) continue;

            // $is_save is true, meaning "FOR UPDATE" row locking will apply
            $allocation = $this->calculateBatchAllocation($super_stockist_id, $product_id, $ordered_qty, true);

            foreach ($allocation['allocated_batches'] as $alloc) {
                $detailStmt = $this->con->prepare("
                    INSERT INTO order_details
                    (order_id, product_id, batch_id, qty, approved_qty, rate, discount, gst, amt, net_total)
                    VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, ?)
                ");
                
                $detailStmt->bind_param(
                    "iiiiiddid",
                    $order_id,
                    $product_id,
                    $alloc['batch_id'],
                    $alloc['alloc_qty'],
                    $alloc['rate'],
                    $alloc['discount'],
                    $alloc['gst'],
                    $alloc['amt'],
                    $alloc['net_total']
                );

                if (!$detailStmt->execute()) {
                    throw new Exception("Failed to insert order details (batch {$alloc['batch_id']}): " . $detailStmt->error);
                }
                $detailStmt->close();
            }
        }
    }

    public function getOrdersByMr($mr_id, $stockist_id = 0, $from_date = '', $to_date = '')
    {
        $sql = "
            SELECT 
                o.order_id,
                o.order_date,
                o.total_amt,
                o.status,
                s.stockist_name,
                COALESCE(SUM(od.qty), 0) AS total_qty
            FROM orders o
            INNER JOIN stockists s ON s.stockist_id = o.stockist_id
            LEFT JOIN order_details od ON od.order_id = o.order_id
            WHERE o.mr_id = ?
        ";

        $types  = "i";
        $params = [$mr_id];

        if (!empty($stockist_id)) {
            $sql .= " AND o.stockist_id = ? ";
            $types .= "i";
            $params[] = $stockist_id;
        }
        if (!empty($from_date)) {
            $sql .= " AND o.order_date >= ? ";
            $types .= "s";
            $params[] = $from_date;
        }
        if (!empty($to_date)) {
            $sql .= " AND o.order_date <= ? ";
            $types .= "s";
            $params[] = $to_date;
        }

        $sql .= " GROUP BY o.order_id ORDER BY o.order_date DESC, o.order_id DESC ";

        $stmt = $this->con->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = [
                'order_id'      => (int)$row['order_id'],
                'order_no'      => 'ORD' . str_pad($row['order_id'], 4, '0', STR_PAD_LEFT),
                'order_date'    => date('d-m', strtotime($row['order_date'])),
                'stockist_name' => $row['stockist_name'],
                'total_qty'     => (int)$row['total_qty'],
                'total_amt'     => (float)$row['total_amt'],
                'status'        => $row['status'] ?? 'Pending',
            ];
        }
        $stmt->close();
        return $orders;
    }


}