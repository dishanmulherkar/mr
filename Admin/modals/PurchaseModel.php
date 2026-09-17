<?php

class PurchaseEntryModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getProducts()
    {
        return mysqli_query($this->con, "
            SELECT p_id, product_name
            FROM products
            ORDER BY product_name ASC
        ");
    }

    public function getStockist()
    {
        if ($_SESSION['admin_role'] == 'Super Admin') {
            return mysqli_query($this->con, "
                SELECT ss.super_stockist_id, ss.ss_name, s.state_name
                FROM super_stockist ss 
                INNER JOIN state s ON ss.state = s.state_id
                ORDER BY ss_name ASC
            ");
        }
        
        $admin_id = (int)$_SESSION['admin_id'];

        return mysqli_query($this->con, "
            SELECT ss.super_stockist_id, ss.ss_name, s.state_name
            FROM super_stockist ss 
            INNER JOIN state s ON ss.state = s.state_id
            INNER JOIN admins a ON ss.super_stockist_id = a.stockist_id
            WHERE a.admin_id = '$admin_id'
            ORDER BY ss_name ASC
        ");
    }

    public function getBatchesByProduct($product_id)
    {
        $product_id = (int)$product_id;
        return mysqli_query($this->con, "
            SELECT DISTINCT 
                batch_id, batch_no, mrp, purchase_rate, purchase_tax, 
                sale_rate, sale_tax, expiry_date, status
            FROM product_batches 
            WHERE product_id = '$product_id' AND status = 'Active'
            ORDER BY batch_no DESC
        ");
    }

    public function store($post)
    {
        // 1. Prepare Header Data
        $superstokist_id    = (int)($post['supplier_id'] ?? 0);
        $purchase_date  = mysqli_real_escape_string($this->con, $post['purchase_date'] ?? date('Y-m-d'));
        $invoice_date   = mysqli_real_escape_string($this->con, $post['invoice_date'] ?? date('Y-m-d'));
        $invoice_no     = mysqli_real_escape_string($this->con, $post['invoice_no'] ?? '');
        
        $lr_no          = mysqli_real_escape_string($this->con, $post['lr_no'] ?? '');
        $cd_percent     = (float)($post['cd_no'] ?? 0);
        $eway_bill      = mysqli_real_escape_string($this->con, $post['eway_bill'] ?? '');
        $vehicle_no     = mysqli_real_escape_string($this->con, $post['vehicle_no'] ?? '');
        $transport_name = mysqli_real_escape_string($this->con, $post['transport_name'] ?? '');
        $credit_days    = (int)($post['credit_days'] ?? 0);
        $discount       = (float)($post['discount'] ?? 0);
        
        // Handle Other Charges (+ or -)
        $other_charge_amt  = (float)($post['other_charges'] ?? 0);
        $other_charge_sign = $post['other_charges_sign'] ?? '+';
        $other_charges     = ($other_charge_sign === '-') ? -$other_charge_amt : $other_charge_amt;

        $remarks        = mysqli_real_escape_string($this->con, $post['remarks'] ?? '');
        $total_qty      = (float)($post['total_qty'] ?? 0);
        $grand_total    = (float)($post['grand_total'] ?? 0);

        $gsttype        = mysqli_real_escape_string($this->con, $post['gsttype'] ?? 'cgst');
        $gst_amt        = (float)($post['gst_amt'] ?? 0);
        $cgst           = (float)($post['cgst'] ?? 0);
        $sgst           = (float)($post['sgst'] ?? 0);
        $igst           = (float)($post['igst'] ?? 0);
        $vat            = (float)($post['vat'] ?? 0);

        $admin_id = $_SESSION['admin_id'] ?? 1; 

        // Get the last inserted ID to generate the next Purchase No
        $max_id_query = mysqli_query($this->con, "SELECT MAX(purchase_id) as max_id FROM purchase_entry");
        $max_id_row = mysqli_fetch_assoc($max_id_query);
        $next_id = ($max_id_row['max_id'] ?? 0) + 1;
        
        $purchase_no = 'PUR-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

        mysqli_begin_transaction($this->con);

        try {
            // 2. Insert Purchase Header
            $header_query = "
                INSERT INTO purchase_entry (
                    purchase_no, super_stockist_id, purchase_date, invoice_date, invoice_no, 
                    lr_no, cdper, eway_bill_no, vehicle_no, transport_name, 
                    credit_days, discount, other_charges, remarks, 
                    total_qty, grand_total, created_by, gst_type, gst_amount, cgst_amount, sgst_amount, igst_amount, vat_amount
                ) VALUES (
                    '$purchase_no','$superstokist_id', '$purchase_date', '$invoice_date', '$invoice_no', 
                    '$lr_no', '$cd_percent', '$eway_bill', '$vehicle_no', '$transport_name', 
                    '$credit_days', '$discount', '$other_charges', '$remarks', 
                    '$total_qty', '$grand_total', '$admin_id', '$gsttype', '$gst_amt', '$cgst', '$sgst', '$igst', '$vat'
                )
            ";

            if (!mysqli_query($this->con, $header_query)) {
                throw new Exception("Failed to insert purchase header: " . mysqli_error($this->con));
            }

            $purchase_id = mysqli_insert_id($this->con);

            // 3. Process Details
            if (isset($post['product_id']) && is_array($post['product_id'])) {
                
                foreach ($post['product_id'] as $key => $product_id) {
                    
                    $product_id = (int)$product_id;
                    $batch      = mysqli_real_escape_string($this->con, $post['batch'][$key] ?? '');
                    $expiry     = mysqli_real_escape_string($this->con, $post['expiry'][$key] ?? '');
                    
                    $mrp        = (float)($post['mrp'][$key] ?? 0);
                    $rate       = (float)($post['rate'][$key] ?? 0);
                    $tax        = (float)($post['tax'][$key] ?? 0);
                    $srate      = (float)($post['srate'][$key] ?? 0);
                    $stax       = (float)($post['stax'][$key] ?? 0);
                    
                    $qty        = (float)($post['qty'][$key] ?? 0);
                    $free_qty   = (float)($post['free_qty'][$key] ?? 0);
                    $disc       = (float)($post['disc'][$key] ?? 0);
                    $amount     = (float)($post['amount'][$key] ?? 0);

                    if ($qty <= 0) continue;

                    $total_stock_qty = $qty + $free_qty; 

                    // Format expiry for MySQL (MM/YYYY or MM/YY to YYYY-MM-DD)
                    $expiry_formatted = "NULL"; 
                    if (!empty($expiry) && strpos($expiry, '/') !== false) {
                        list($mm, $yyyy) = explode('/', $expiry);
                        if (strlen(trim($yyyy)) === 2) {
                            $yyyy = '20' . trim($yyyy);
                        }
                        $expiry_formatted = "'" . trim($yyyy) . "-" . trim($mm) . "-01'";
                    }
                    $expiry_date = $expiry_formatted;

                    // ==========================================
                    // STEP A: Handle Product Batches FIRST to get batch_id
                    // ==========================================
                    $actual_batch_id = 0;
                    
                    $check_batch = mysqli_query($this->con, "
                        SELECT batch_id FROM product_batches 
                        WHERE product_id='$product_id' AND batch_no='$batch' LIMIT 1
                    ");

                    if (mysqli_num_rows($check_batch) > 0) {
                        $row = mysqli_fetch_assoc($check_batch);
                        $actual_batch_id = $row['batch_id']; // Grab existing ID

                        $update_batch = "
                            UPDATE product_batches SET 
                                purchase_rate = '$rate',
                                purchase_tax = '$tax',
                                sale_rate = '$srate',
                                sale_tax = '$stax',
                                expiry_date = $expiry_date,
                                mrp  = $mrp,
                                disc = $disc,
                                status = 'Active'
                            WHERE batch_id = '$actual_batch_id'
                        ";
                        if (!mysqli_query($this->con, $update_batch)) {
                            throw new Exception("Failed to update product batches: " . mysqli_error($this->con));
                        }
                    } else {
                        $insert_batch = "
                            INSERT INTO product_batches (
                                product_id, batch_no, status, purchase_rate, purchase_tax, 
                                sale_rate, sale_tax, expiry_date, mrp, disc
                            ) VALUES (
                                '$product_id', '$batch', 'Active', '$rate', '$tax', 
                                '$srate', '$stax', $expiry_date, $mrp, $disc
                            )
                        ";
                        if (!mysqli_query($this->con, $insert_batch)) {
                            throw new Exception("Failed to insert product batches: " . mysqli_error($this->con));
                        }
                        $actual_batch_id = mysqli_insert_id($this->con); // Grab new ID
                    }


                    // ==========================================
                    // STEP B: Insert Details (Sending BOTH batch_id and batch_no)
                    // ==========================================
                    $detail_query = "
                        INSERT INTO purchase_entry_details (
                            purchase_id, product_id, batch_id, batch_no, expiry_date, 
                            mrp, purchase_rate, purchase_tax, qty, free_qty, 
                            discount_amt, amount
                        ) VALUES (
                            '$purchase_id', '$product_id', '$actual_batch_id', '$batch', $expiry_date, 
                            '$mrp', '$rate', '$tax', '$qty', '$free_qty', 
                            '$disc', '$amount'
                        )
                    ";

                    if (!mysqli_query($this->con, $detail_query)) {
                        throw new Exception("Failed to insert details: " . mysqli_error($this->con));
                    }


                    // ==========================================
                    // STEP C: Insert Ledger (Sending BOTH batch_id and batch_no)
                    // ==========================================
                    $ledger_query = "
                        INSERT INTO stock_ledger (
                            reference_id, p_id, batch_id, trans_type, 
                            qty_in, qty_out, amount, rate, trans_date, trans_datetime, admin_id, stockist_id, stockist_type
                        ) VALUES (
                            '$purchase_id', '$product_id', '$actual_batch_id', 'PURCHASE', 
                            '$total_stock_qty', 0, '$amount', '$rate', '$purchase_date', NOW(), '$admin_id', '$superstokist_id', 'Super-Stockist'
                        )
                    ";

                    if (!mysqli_query($this->con, $ledger_query)) {
                        throw new Exception("Failed to insert stock ledger: " . mysqli_error($this->con));
                    }
                }
            }

            mysqli_commit($this->con);
            return ['success' => true, 'message' => 'Purchase saved successfully!'];

        } catch (Exception $e) {
            mysqli_rollback($this->con);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getPurchaseList()
    {
        $query = "
            SELECT 
                p.purchase_id, 
                p.purchase_no, 
                p.purchase_date, 
                p.invoice_no, 
                p.total_qty, 
                p.grand_total, 
                s.ss_name 
            FROM purchase_entry p
            LEFT JOIN super_stockist s ON p.super_stockist_id = s.super_stockist_id
            ORDER BY p.purchase_date DESC, p.purchase_id DESC
        ";
        
        return mysqli_query($this->con, $query);
    }

    // Fetch single purchase header for View or Edit
    public function getPurchaseById($purchase_id)
    {
        $purchase_id = (int)$purchase_id;
        $query = mysqli_query($this->con, "
            SELECT p.*, s.ss_name 
            FROM purchase_entry p
            LEFT JOIN super_stockist s ON p.super_stockist_id = s.super_stockist_id
            WHERE p.purchase_id = '$purchase_id' LIMIT 1
        ");
        return mysqli_fetch_assoc($query);
    }

    // Fetch purchase items/details for View or Edit
   public function getPurchaseDetails($purchase_id)
    {
        $purchase_id = (int)$purchase_id;
        return mysqli_query($this->con, "
            SELECT 
                d.*, 
                pr.product_name, 
                pb.sale_rate, 
                pb.sale_tax 
            FROM purchase_entry_details d
            LEFT JOIN products pr ON d.product_id = pr.p_id
            LEFT JOIN product_batches pb ON d.product_id = pb.product_id AND d.batch_id = pb.batch_id
            WHERE d.purchase_id = '$purchase_id'
        ");
    }

    public function deletePurchase($purchase_id)
    {
        $purchase_id = (int)$purchase_id;

        mysqli_begin_transaction($this->con);

        try {
            // 1. Delete from stock_ledger
            if (!mysqli_query($this->con, "DELETE FROM stock_ledger WHERE reference_id = '$purchase_id' AND trans_type = 'PURCHASE'")) {
                throw new Exception("Failed to delete stock ledger entries.");
            }

            // 2. Delete from purchase_entry_details
            if (!mysqli_query($this->con, "DELETE FROM purchase_entry_details WHERE purchase_id = '$purchase_id'")) {
                throw new Exception("Failed to delete purchase entry details.");
            }

            // 3. Delete from purchase_entry (Header)
            if (!mysqli_query($this->con, "DELETE FROM purchase_entry WHERE purchase_id = '$purchase_id'")) {
                throw new Exception("Failed to delete purchase header.");
            }

            mysqli_commit($this->con);
            return ['success' => true, 'message' => 'Purchase deleted successfully!'];

        } catch (Exception $e) {
            mysqli_rollback($this->con);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function update($post)
    {
        $purchase_id = (int)($post['purchase_id'] ?? 0);
        if ($purchase_id <= 0) {
            return ['success' => false, 'message' => 'Invalid Purchase ID for update.'];
        }

        // 1. Prepare Header Data
        $superstokist_id    = (int)($post['supplier_id'] ?? 0);
        $purchase_date  = mysqli_real_escape_string($this->con, $post['purchase_date'] ?? date('Y-m-d'));
        $invoice_date   = mysqli_real_escape_string($this->con, $post['invoice_date'] ?? date('Y-m-d'));
        $invoice_no     = mysqli_real_escape_string($this->con, $post['invoice_no'] ?? '');
        
        $lr_no          = mysqli_real_escape_string($this->con, $post['lr_no'] ?? '');
        $cd_percent     = (float)($post['cd_no'] ?? 0);
        $eway_bill      = mysqli_real_escape_string($this->con, $post['eway_bill'] ?? '');
        $vehicle_no     = mysqli_real_escape_string($this->con, $post['vehicle_no'] ?? '');
        $transport_name = mysqli_real_escape_string($this->con, $post['transport_name'] ?? '');
        $credit_days    = (int)($post['credit_days'] ?? 0);
        $discount       = (float)($post['discount'] ?? 0);
        
        // Handle Other Charges (+ or -)
        $other_charge_amt  = (float)($post['other_charges'] ?? 0);
        $other_charge_sign = $post['other_charges_sign'] ?? '+';
        $other_charges     = ($other_charge_sign === '-') ? -$other_charge_amt : $other_charge_amt;

        $remarks        = mysqli_real_escape_string($this->con, $post['remarks'] ?? '');
        $total_qty      = (float)($post['total_qty'] ?? 0);
        $grand_total    = (float)($post['grand_total'] ?? 0);

        $gsttype        = mysqli_real_escape_string($this->con, $post['gsttype'] ?? 'cgst');
        $gst_amt        = (float)($post['gst_amt'] ?? 0);
        $cgst           = (float)($post['cgst'] ?? 0);
        $sgst           = (float)($post['sgst'] ?? 0);
        $igst           = (float)($post['igst'] ?? 0);
        $vat            = (float)($post['vat'] ?? 0);

        $admin_id = $_SESSION['admin_id'] ?? 1;

        mysqli_begin_transaction($this->con);

        try {
            // 2. Update Purchase Header
            $header_query = "
                UPDATE purchase_entry SET 
                    super_stockist_id = '$superstokist_id',
                    purchase_date = '$purchase_date',
                    invoice_date = '$invoice_date',
                    invoice_no = '$invoice_no',
                    lr_no = '$lr_no',
                    cdper = '$cd_percent',
                    eway_bill_no = '$eway_bill',
                    vehicle_no = '$vehicle_no',
                    transport_name = '$transport_name',
                    credit_days = '$credit_days',
                    discount = '$discount',
                    other_charges = '$other_charges',
                    remarks = '$remarks',
                    total_qty = '$total_qty',
                    grand_total = '$grand_total',
                    gst_type = '$gsttype',
                    gst_amount = '$gst_amt',
                    cgst_amount = '$cgst',
                    sgst_amount = '$sgst',
                    igst_amount = '$igst',
                    vat_amount = '$vat'
                WHERE purchase_id = '$purchase_id'
            ";

            if (!mysqli_query($this->con, $header_query)) {
                throw new Exception("Failed to update purchase header: " . mysqli_error($this->con));
            }

            // 3. Clear old child rows/ledger entries to overwrite cleanly with new inputs
            if (!mysqli_query($this->con, "DELETE FROM purchase_entry_details WHERE purchase_id = '$purchase_id'")) {
                throw new Exception("Failed to clear old details: " . mysqli_error($this->con));
            }
            if (!mysqli_query($this->con, "DELETE FROM stock_ledger WHERE reference_id = '$purchase_id' AND trans_type = 'PURCHASE'")) {
                throw new Exception("Failed to clear old stock ledger: " . mysqli_error($this->con));
            }

            // 4. Process Details (Matching store schema exactly)
            if (isset($post['product_id']) && is_array($post['product_id'])) {
                
                foreach ($post['product_id'] as $key => $product_id) {
                    
                    $product_id = (int)$product_id;
                    $batch      = mysqli_real_escape_string($this->con, $post['batch'][$key] ?? '');
                    $expiry     = mysqli_real_escape_string($this->con, $post['expiry'][$key] ?? '');
                    
                    $mrp        = (float)($post['mrp'][$key] ?? 0);
                    $rate       = (float)($post['rate'][$key] ?? 0);
                    $tax        = (float)($post['tax'][$key] ?? 0);
                    $srate      = (float)($post['srate'][$key] ?? 0);
                    $stax       = (float)($post['stax'][$key] ?? 0);
                    
                    $qty        = (float)($post['qty'][$key] ?? 0);
                    $free_qty   = (float)($post['free_qty'][$key] ?? 0);
                    $disc       = (float)($post['disc'][$key] ?? 0);
                    $amount     = (float)($post['amount'][$key] ?? 0);

                    if ($qty <= 0) continue;

                    $total_stock_qty = $qty + $free_qty; 

                    // Format expiry for MySQL (MM/YYYY or MM/YY to YYYY-MM-DD)
                    $expiry_formatted = "NULL"; 
                    if (!empty($expiry) && strpos($expiry, '/') !== false) {
                        list($mm, $yyyy) = explode('/', $expiry);
                        if (strlen(trim($yyyy)) === 2) {
                            $yyyy = '20' . trim($yyyy);
                        }
                        $expiry_formatted = "'" . trim($yyyy) . "-" . trim($mm) . "-01'";
                    }
                    $expiry_date = $expiry_formatted;

                    // ==========================================
                    // STEP A: Handle Product Batches FIRST to get batch_id
                    // ==========================================
                    $actual_batch_id = 0;
                    
                    $check_batch = mysqli_query($this->con, "
                        SELECT batch_id FROM product_batches 
                        WHERE product_id='$product_id' AND batch_no='$batch' LIMIT 1
                    ");

                    if (mysqli_num_rows($check_batch) > 0) {
                        $row = mysqli_fetch_assoc($check_batch);
                        $actual_batch_id = $row['batch_id']; 

                        $update_batch = "
                            UPDATE product_batches SET 
                                purchase_rate = '$rate',
                                purchase_tax = '$tax',
                                sale_rate = '$srate',
                                sale_tax = '$stax',
                                expiry_date = $expiry_date,
                                mrp  = $mrp,
                                disc = $disc,
                                status = 'Active'
                            WHERE batch_id = '$actual_batch_id'
                        ";
                        if (!mysqli_query($this->con, $update_batch)) {
                            throw new Exception("Failed to update product batches: " . mysqli_error($this->con));
                        }
                    } else {
                        $insert_batch = "
                            INSERT INTO product_batches (
                                product_id, batch_no, status, purchase_rate, purchase_tax, 
                                sale_rate, sale_tax, expiry_date, mrp, disc
                            ) VALUES (
                                '$product_id', '$batch', 'Active', '$rate', '$tax', 
                                '$srate', '$stax', $expiry_date, $mrp, $disc
                            )
                        ";
                        if (!mysqli_query($this->con, $insert_batch)) {
                            throw new Exception("Failed to insert product batches: " . mysqli_error($this->con));
                        }
                        $actual_batch_id = mysqli_insert_id($this->con); 
                    }

                    // ==========================================
                    // STEP B: Insert Details (Sending BOTH batch_id and batch_no)
                    // ==========================================
                    $detail_query = "
                        INSERT INTO purchase_entry_details (
                            purchase_id, product_id, batch_id, batch_no, expiry_date, 
                            mrp, purchase_rate, purchase_tax, qty, free_qty, 
                            discount_amt, amount
                        ) VALUES (
                            '$purchase_id', '$product_id', '$actual_batch_id', '$batch', $expiry_date, 
                            '$mrp', '$rate', '$tax', '$qty', '$free_qty', 
                            '$disc', '$amount'
                        )
                    ";

                    if (!mysqli_query($this->con, $detail_query)) {
                        throw new Exception("Failed to insert details: " . mysqli_error($this->con));
                    }

                    // ==========================================
                    // STEP C: Insert Ledger (Sending BOTH batch_id and batch_no)
                    // ==========================================
                    $ledger_query = "
                        INSERT INTO stock_ledger (
                            reference_id, p_id, batch_id, trans_type, 
                            qty_in, qty_out, amount, rate, trans_date, trans_datetime, admin_id, stockist_id, stockist_type
                        ) VALUES (
                            '$purchase_id', '$product_id', '$actual_batch_id', 'PURCHASE', 
                            '$total_stock_qty', 0, '$amount', '$rate', '$purchase_date', NOW(), '$admin_id', '$superstokist_id', 'Super-Stockist'
                        )
                    ";

                    if (!mysqli_query($this->con, $ledger_query)) {
                        throw new Exception("Failed to insert stock ledger: " . mysqli_error($this->con));
                    }
                }
            }

            mysqli_commit($this->con);
            return ['success' => true, 'message' => 'Purchase updated successfully!'];

        } catch (Exception $e) {
            mysqli_rollback($this->con);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getCurrentStock($product_id, $batch_id, $stockist_id)
    {
        $product_id = (int)$product_id;
        $batch_id = (int)$batch_id;
        $stockist_id = (int)$stockist_id;

        $query = mysqli_query($this->con, "
            SELECT (SUM(qty_in) - SUM(qty_out)) as available_stock 
            FROM stock_ledger 
            WHERE p_id = '$product_id' 
            AND batch_id = '$batch_id' 
            AND stockist_id = '$stockist_id'
            AND stockist_type = 'Super-Stockist'
        ");
        
        $result = mysqli_fetch_assoc($query);
        return $result['available_stock'] ?? 0;
    }

    public function getBatchesForAdjustment($product_id, $stockist_id)
{
    $product_id = (int)$product_id;
    // Returns batches that exist in the system for this product.
    // If you only want batches that CURRENTLY have stock for this stockist, join with stock_ledger.
    $query = mysqli_query($this->con, "
        SELECT batch_id, batch_no, expiry_date 
        FROM product_batches 
        WHERE product_id = '$product_id' AND status = 'Active'
    ");
    
    $html = '<option value="">Select Batch</option>';
    while ($row = mysqli_fetch_assoc($query)) {
        $html .= '<option value="'.$row['batch_id'].'">'.$row['batch_no'].' (Exp: '.$row['expiry_date'].')</option>';
    }
    return $html;
}

// ============================================================
    // DYNAMIC STOCK ADJUSTMENT DROPDOWNS (MODEL)
    // ============================================================

    // 1. Get Products only available in this Stockist's inventory
    public function getProductsByStockist($stockist_id)
    {
        $stockist_id = (int)$stockist_id;
        $query = mysqli_query($this->con, "
            SELECT p.p_id, p.product_name 
            FROM stock_ledger sl
            INNER JOIN products p ON sl.p_id = p.p_id
            WHERE sl.stockist_id = '$stockist_id' 
              AND sl.stockist_type = 'Super-Stockist'
            GROUP BY sl.p_id
            HAVING (SUM(sl.qty_in) - SUM(sl.qty_out)) > 0
            ORDER BY p.product_name ASC
        ");

        $html = '<option value="">Select Product</option>';
        while ($row = mysqli_fetch_assoc($query)) {
            $html .= '<option value="'.$row['p_id'].'">'.$row['product_name'].'</option>';
        }
        return $html;
    }

    // 2. Get Batches only available for this Product in this Stockist's inventory
    public function getBatchesByStockistProduct($product_id, $stockist_id)
    {
        $product_id = (int)$product_id;
        $stockist_id = (int)$stockist_id;

        $query = mysqli_query($this->con, "
            SELECT pb.batch_id, pb.batch_no, pb.expiry_date, 
                   (SUM(sl.qty_in) - SUM(sl.qty_out)) as available_stock
            FROM stock_ledger sl
            INNER JOIN product_batches pb ON sl.batch_id = pb.batch_id
            WHERE sl.p_id = '$product_id' 
              AND sl.stockist_id = '$stockist_id' 
              AND sl.stockist_type = 'Super-Stockist'
            GROUP BY sl.batch_id
            HAVING available_stock > 0
            ORDER BY pb.batch_no ASC
        ");

        $html = '<option value="">Select Batch</option>';
        while ($row = mysqli_fetch_assoc($query)) {
            // Optional: You can show the available stock inside the dropdown label for better UI
            $html .= '<option value="'.$row['batch_id'].'">'.$row['batch_no'].' (Stock: '.$row['available_stock'].')</option>';
        }
        return $html;
    }

public function adjustmentstore($post)
    {
        $stockist_id = (int)($post['stockist_id'] ?? 0);
        $adj_date = mysqli_real_escape_string($this->con, $post['adj_date'] ?? date('Y-m-d'));
        $general_remarks = mysqli_real_escape_string($this->con, $post['general_remarks'] ?? '');
        $admin_id = $_SESSION['admin_id'] ?? 1; 

        // Generate next Adjustment Number (e.g., ADJ-0001)
        $max_id_query = mysqli_query($this->con, "SELECT MAX(adj_id) as max_id FROM stock_adjustments");
        $max_id_row = mysqli_fetch_assoc($max_id_query);
        $next_id = ($max_id_row['max_id'] ?? 0) + 1;
        $adj_no = 'ADJ-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

        mysqli_begin_transaction($this->con);

        try {
            // 1. Insert Header
            $header_query = "
                INSERT INTO stock_adjustments (
                    adj_no, stockist_id, adj_date, remarks, created_by, created_at
                ) VALUES (
                    '$adj_no', '$stockist_id', '$adj_date', '$general_remarks', '$admin_id', NOW()
                )
            ";

            if (!mysqli_query($this->con, $header_query)) {
                throw new Exception("Failed to insert adjustment header: " . mysqli_error($this->con));
            }

            $adj_id = mysqli_insert_id($this->con);

            // 2. Loop through products
            if (isset($post['product_id']) && is_array($post['product_id'])) {
                foreach ($post['product_id'] as $key => $product_id) {
                    $product_id = (int)$product_id;
                    $batch_id = (int)($post['batch_id'][$key] ?? 0);
                    $adj_type = mysqli_real_escape_string($this->con, $post['adj_type'][$key] ?? 'ADD');
                    $qty = (float)($post['qty'][$key] ?? 0);
                    $line_remarks = mysqli_real_escape_string($this->con, $post['line_remarks'][$key] ?? '');

                    if ($qty <= 0) continue;

                    $qty_in = ($adj_type === 'ADD') ? $qty : 0;
                    $qty_out = ($adj_type === 'DEDUCT') ? $qty : 0;

                    // Insert Details
                    $detail_query = "
                        INSERT INTO stock_adjustment_details (
                            adj_id, product_id, batch_id, adj_type, qty, remarks
                        ) VALUES (
                            '$adj_id', '$product_id', '$batch_id', '$adj_type', '$qty', '$line_remarks'
                        )
                    ";

                    if (!mysqli_query($this->con, $detail_query)) {
                        throw new Exception("Failed to insert adjustment details: " . mysqli_error($this->con));
                    }

                    // Insert into Stock Ledger (UPDATED WITH REFERENCE_TABLE)
                    $ledger_query = "
                        INSERT INTO stock_ledger (
                            reference_table, reference_id, p_id, batch_id, trans_type, 
                            qty_in, qty_out, trans_date, trans_datetime, 
                            admin_id, stockist_id, stockist_type
                        ) VALUES (
                            'stock_adjustments', '$adj_id', '$product_id', '$batch_id', 'ADJUSTMENT', 
                            '$qty_in', '$qty_out', '$adj_date', NOW(), 
                            '$admin_id', '$stockist_id', 'Super-Stockist'
                        )
                    ";

                    if (!mysqli_query($this->con, $ledger_query)) {
                        throw new Exception("Failed to update stock ledger: " . mysqli_error($this->con));
                    }
                }
            } else {
                throw new Exception("No products provided for adjustment.");
            }

            mysqli_commit($this->con);
            return ['success' => true, 'message' => 'Adjustment saved successfully!'];

        } catch (Exception $e) {
            mysqli_rollback($this->con);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function adjustmentupdate($post)
    {
        $adj_id = (int)($post['adj_id'] ?? 0);
        
        if ($adj_id <= 0) {
            return ['success' => false, 'message' => 'Invalid Adjustment ID for update.'];
        }

        $stockist_id = (int)($post['stockist_id'] ?? 0);
        $adj_date = mysqli_real_escape_string($this->con, $post['adj_date'] ?? date('Y-m-d'));
        $general_remarks = mysqli_real_escape_string($this->con, $post['general_remarks'] ?? '');
        $admin_id = $_SESSION['admin_id'] ?? 1;

        mysqli_begin_transaction($this->con);

        try {
            // 1. Update Header
            $header_query = "
                UPDATE stock_adjustments SET 
                    stockist_id = '$stockist_id',
                    adj_date = '$adj_date',
                    remarks = '$general_remarks'
                WHERE adj_id = '$adj_id'
            ";

            if (!mysqli_query($this->con, $header_query)) {
                throw new Exception("Failed to update adjustment header: " . mysqli_error($this->con));
            }

            // 2. Clear old child rows & ledger entries to reverse cleanly
            if (!mysqli_query($this->con, "DELETE FROM stock_adjustment_details WHERE adj_id = '$adj_id'")) {
                throw new Exception("Failed to clear old adjustment details: " . mysqli_error($this->con));
            }
            
            // UPDATED: Added reference_table to the DELETE query for stricter safety
            if (!mysqli_query($this->con, "DELETE FROM stock_ledger WHERE reference_table = 'stock_adjustments' AND reference_id = '$adj_id' AND trans_type = 'ADJUSTMENT'")) {
                throw new Exception("Failed to clear old stock ledger: " . mysqli_error($this->con));
            }

            // 3. Process New Entries
            if (isset($post['product_id']) && is_array($post['product_id'])) {
                foreach ($post['product_id'] as $key => $product_id) {
                    
                    $product_id = (int)$product_id;
                    $batch_id = (int)($post['batch_id'][$key] ?? 0);
                    $adj_type = mysqli_real_escape_string($this->con, $post['adj_type'][$key] ?? 'ADD');
                    $qty = (float)($post['qty'][$key] ?? 0);
                    $line_remarks = mysqli_real_escape_string($this->con, $post['line_remarks'][$key] ?? '');

                    if ($qty <= 0) continue;

                    $qty_in = ($adj_type === 'ADD') ? $qty : 0;
                    $qty_out = ($adj_type === 'DEDUCT') ? $qty : 0;

                    // Insert Details
                    $detail_query = "
                        INSERT INTO stock_adjustment_details (
                            adj_id, product_id, batch_id, adj_type, qty, remarks
                        ) VALUES (
                            '$adj_id', '$product_id', '$batch_id', '$adj_type', '$qty', '$line_remarks'
                        )
                    ";

                    if (!mysqli_query($this->con, $detail_query)) {
                        throw new Exception("Failed to insert new adjustment details: " . mysqli_error($this->con));
                    }

                    // Insert into Stock Ledger (UPDATED WITH REFERENCE_TABLE)
                    $ledger_query = "
                        INSERT INTO stock_ledger (
                            reference_table, reference_id, p_id, batch_id, trans_type, 
                            qty_in, qty_out, trans_date, trans_datetime, 
                            admin_id, stockist_id, stockist_type
                        ) VALUES (
                            'stock_adjustments', '$adj_id', '$product_id', '$batch_id', 'ADJUSTMENT', 
                            '$qty_in', '$qty_out', '$adj_date', NOW(), 
                            '$admin_id', '$stockist_id', 'Super-Stockist'
                        )
                    ";

                    if (!mysqli_query($this->con, $ledger_query)) {
                        throw new Exception("Failed to insert new stock ledger: " . mysqli_error($this->con));
                    }
                }
            } else {
                throw new Exception("No products provided for adjustment.");
            }

            mysqli_commit($this->con);
            return ['success' => true, 'message' => 'Adjustment updated successfully!'];

        } catch (Exception $e) {
            mysqli_rollback($this->con);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Fetch all adjustments for the list page
    public function getAdjustmentList()
    {
        $query = "
            SELECT 
                a.adj_id, 
                a.adj_no, 
                a.adj_date, 
                a.remarks, 
                s.ss_name 
            FROM stock_adjustments a
            LEFT JOIN super_stockist s ON a.stockist_id = s.super_stockist_id
            ORDER BY a.adj_date DESC, a.adj_id DESC
        ";
        
        return mysqli_query($this->con, $query);
    }

    // ==========================================
    // FETCH ADJUSTMENT FOR EDIT (MODEL)
    // ==========================================

    // Fetch the main header record
    public function getAdjustmentById($adj_id)
    {
        $adj_id = (int)$adj_id;
        $query = mysqli_query($this->con, "
            SELECT * FROM stock_adjustments 
            WHERE adj_id = '$adj_id' LIMIT 1
        ");
        
        return mysqli_fetch_assoc($query);
    }

    // Fetch the child records and join with product/batch data
    public function getAdjustmentDetails($adj_id)
    {
        $adj_id = (int)$adj_id;
        return mysqli_query($this->con, "
            SELECT 
                d.*, 
                p.product_name, 
                pb.batch_no 
            FROM stock_adjustment_details d
            LEFT JOIN products p ON d.product_id = p.p_id
            LEFT JOIN product_batches pb ON d.batch_id = pb.batch_id
            WHERE d.adj_id = '$adj_id'
        ");
    }
}