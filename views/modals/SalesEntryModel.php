<?php

class SalesModel
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
                stockist_id,
                stockist_name
            FROM stockists
            WHERE hq_id = ? AND status = ? 
            ORDER BY stockist_name
        ");

        $stmt->bind_param("ii", $mr_id,$status);
        $stmt->execute();

        $result = $stmt->get_result();

        $stockists = [];

        while ($row = $result->fetch_assoc())
        {
            $stockists[] = $row;
        }

        $stmt->close();

        return $stockists;
    }

    public function getCustomer($mr_id, $type = '')
    {
        $status = 1;

        if (!empty($type))
        {
            $stmt = $this->con->prepare("
                SELECT
                    c_id,
                    customer_name
                FROM customers
                WHERE hq_id = ?
                AND customer_type = ?
                AND status = ?
                ORDER BY customer_name
            ");

            $stmt->bind_param("isi", $mr_id, $type, $status);
        }
        else
        {
            $stmt = $this->con->prepare("
                SELECT
                    c_id,
                    customer_name
                FROM customers
                WHERE hq_id = ?
                AND status = ?
                ORDER BY customer_name
            ");

            $stmt->bind_param("ii", $mr_id, $status);
        }

        $stmt->execute();

        $result = $stmt->get_result();

        $customers = [];

        while ($row = $result->fetch_assoc())
        {
            $customers[] = $row;
        }

        $stmt->close();

        return $customers;
    }

public function searchMedicine($stockist_id, $q = '')
{
    if (!$stockist_id)
    {
        return [];
    }

    // -------------------------------------------------
    // Find latest batch series for this stockist
    // Example: A004 => 4
    // -------------------------------------------------

    $latestBatchSeries = 0;

    $latest = $this->con->prepare("
        SELECT MAX(CAST(SUBSTRING(pb.batch_no,2) AS UNSIGNED)) AS latest_batch
        FROM stockist_stock ss
        INNER JOIN product_batches pb
            ON pb.batch_id = ss.batch_id
        WHERE ss.stockist_id = ?
          AND ss.current_qty > 0
    ");

    $latest->bind_param("i", $stockist_id);
    $latest->execute();

    $latest->bind_result($latestBatchSeries);
    $latest->fetch();

    $latest->close();

    // -------------------------------------------------
    // Fetch Medicines
    // -------------------------------------------------

    $stmt = $this->con->prepare("
        SELECT
            p.p_id,
            p.product_name,
            pb.batch_id,
            pb.batch_no,
            pb.pts,
            ss.current_qty,
            CAST(SUBSTRING(pb.batch_no,2) AS UNSIGNED) AS batch_series
        FROM stockist_stock ss
        INNER JOIN products p
            ON p.p_id = ss.p_id
        INNER JOIN product_batches pb
            ON pb.batch_id = ss.batch_id
        WHERE ss.stockist_id = ?
          AND ss.current_qty > 0
          AND p.product_name LIKE ?
        ORDER BY
            p.product_name,
            batch_series,
            pb.batch_id
        LIMIT 50
    ");

    $like = "%{$q}%";

    $stmt->bind_param("is", $stockist_id, $like);
    $stmt->execute();

    $result = $stmt->get_result();

    $products = [];

    while ($row = $result->fetch_assoc())
    {
        // Show labels only if stockist has more than one batch series
        if ($latestBatchSeries > 0)
        {
            $batch_label =
                ((int)$row['batch_series'] === (int)$latestBatchSeries)
                ? 'New'
                : 'Old';
        }
        else
        {
            $batch_label = '';
        }

        $products[] = [
            'id'          => (int)$row['p_id'],
            'batch_id'    => (int)$row['batch_id'],
            'name'        => $row['product_name'],
            'batch_no'    => $row['batch_no'],
            'batch_label' => $batch_label,
            'pts'         => (float)$row['pts'],
            'stock'       => (int)$row['current_qty']
        ];
    }

    $stmt->close();

    return $products;
}
    public function saveSale($data)
    {
        $mr_id       = $data['mr_id'];
        $sale_date   = $data['sale_date'];
        $customer_id = $data['customer_id'];
        $stockist_id = $data['stockist_id'];
        $total_amt   = $data['total_amt'];
        $items       = $data['items'];

        if (
            !$customer_id ||
            !$stockist_id ||
            empty($items)
        )
        {
            return [
                'success'=>false,
                'msg'=>'Missing required fields.'
            ];
        }

        try
        {

            //=========================
            // Financial Year
            //=========================

            $fy = $this->con->prepare("
                SELECT fy_id
                FROM financial_year
                WHERE hq_id=?
                AND status=1
                AND ? BETWEEN start_date AND end_date
                LIMIT 1
            ");

            $fy->bind_param("is",$mr_id,$sale_date);

            $fy->execute();

            $result=$fy->get_result();

            if($result->num_rows==0){

                throw new Exception("No active Financial Year found.");

            }

            $fy_id=$result->fetch_assoc()['fy_id'];

            $this->con->begin_transaction();

            //-----------------------------------
            // Insert Sales Master
            //-----------------------------------

            $stmt=$this->con->prepare("
                INSERT INTO sales_entries
                (
                    m_id,
                    fy_id,
                    c_id,
                    stockist_id,
                    total_amt,
                    sale_date
                )
                VALUES(?,?,?,?,?,?)
            ");

            $stmt->bind_param(
                "iiiids",
                $mr_id,
                $fy_id,
                $customer_id,
                $stockist_id,
                $total_amt,
                $sale_date
            );

            $stmt->execute();

            $entry_id=$this->con->insert_id;

            $stmt->close();

            //-----------------------------------
            // Items Loop
            //-----------------------------------

            foreach($items as $item){

                $this->saveSaleItem(
                    $entry_id,
                    $stockist_id,
                    $sale_date,
                    $item
                );

            }

            $this->con->commit();

            return [

                'success'=>true,
                'msg'=>'Sale Saved Successfully.',
                'entry_id'=>$entry_id

            ];

        }
        catch(Exception $e){

            $this->con->rollback();

            return [

                'success'=>false,
                'msg'=>$e->getMessage()

            ];

        }

    }

    //----------------------------------------------------
    // Save One Item
    //----------------------------------------------------

private function saveSaleItem($entry_id, $stockist_id, $sale_date, $item)
{
    $product_id = intval($item['product_id'] ?? 0);
    $batch_id   = intval($item['batch_id'] ?? 0);
    $qty        = intval($item['qty'] ?? 0);
    $pts        = floatval($item['pts'] ?? 0);

    $amount = round($qty * $pts, 2);

    if (
        !$product_id ||
        !$batch_id ||
        $qty <= 0
    )
    {
        throw new Exception("Invalid item data.");
    }

    //----------------------------------------------------
    // Check Stock
    //----------------------------------------------------

    $chk = $this->con->prepare("
        SELECT current_qty
        FROM stockist_stock
        WHERE stockist_id = ?
        AND p_id = ?
        AND batch_id = ?
        FOR UPDATE
    ");

    $chk->bind_param(
        "iii",
        $stockist_id,
        $product_id,
        $batch_id
    );

    $chk->execute();

    $chk->bind_result($current_qty);

    $chk->fetch();

    $chk->close();

    if ($current_qty === null)
    {
        throw new Exception(
            "Stock not found for Product ID {$product_id}"
        );
    }

    if ($qty > $current_qty)
    {
        throw new Exception(
            "Insufficient stock. Available : {$current_qty}"
        );
    }

    //----------------------------------------------------
    // Insert Sales Detail
    //----------------------------------------------------

    $ins = $this->con->prepare("
        INSERT INTO sales_details
        (
            s_id,
            p_id,
            batch_id,
            qty,
            rate,
            amount
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?
        )
    ");

    $ins->bind_param(
        "iiiidd",
        $entry_id,
        $product_id,
        $batch_id,
        $qty,
        $pts,
        $amount
    );

    $ins->execute();

    $ins->close();

    //----------------------------------------------------
    // Update Stock
    //----------------------------------------------------

    $upd = $this->con->prepare("
        UPDATE stockist_stock
        SET current_qty = current_qty - ?
        WHERE stockist_id = ?
        AND p_id = ?
        AND batch_id = ?
    ");

    $upd->bind_param(
        "iiii",
        $qty,
        $stockist_id,
        $product_id,
        $batch_id
    );

    $upd->execute();

    $upd->close();

    //----------------------------------------------------
    // Stock Ledger
    //----------------------------------------------------

    $trans_datetime = date('Y-m-d H:i:s');

    $ledger = $this->con->prepare("
        INSERT INTO stock_ledger
        (
            trans_date,
            trans_datetime,
            stockist_id,
            p_id,
            batch_id,
            trans_type,
            qty,
            amount,
            reference_id,
            remarks
        )
        VALUES
        (
            ?, ?, ?, ?, ?, 'SALE', ?, ?, ?, ''
        )
    ");

    $ledger->bind_param(
        "ssiiiidi",
        $sale_date,
        $trans_datetime,
        $stockist_id,
        $product_id,
        $batch_id,
        $qty,
        $amount,
        $entry_id
    );

    $ledger->execute();

    $ledger->close();
}

}