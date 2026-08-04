<?php 
// 3. Include the bottom layout and scripts
include 'view/layout/header.php'; 
?>
<link rel="stylesheet" href="<?= BASE_URL ?>config/Addons/purchase.css">
<style>
    .detail {
        display: flex;
        justify-content: flex-end; 
        padding: 6px; 
    }
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type=number] {
        -moz-appearance: textfield;
    }
    .purchase-table {
        font-size: 13px;
    }
    .purchase-table th,
    .purchase-table td {
        padding: 3px 6px;
        vertical-align: middle;
    }
    .purchase-table .form-control,
    .purchase-table .form-select {
        height: 28px;
        padding: 2px 6px;
        font-size: 13px;
    }
    .purchase-table td .btn {
        height: 20px;
        min-width: 18px;
        padding: 1px 2px;
        font-size: 2px;
    }
    .purchase-table .btn i {
        font-size: 11px;
    }
</style>

<div id="container">
    <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Product saved successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-info">Product deleted successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">Something went wrong. Please try again.</div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>purchase/<?= isset($ROW['purchase_id']) ? 'update' : 'store' ?>" method="POST" id="purchaseForm">
        <?php if (isset($ROW['purchase_id'])): ?>
            <input type="hidden" name="purchase_id" value="<?= $ROW['purchase_id']; ?>">
        <?php endif; ?>

        <div class="purchase-card">
            <!-- Supplier Details -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa fa-shopping-cart"></i> 
                        <?= isset($is_view) && $is_view ? 'View Purchase Invoice' : (isset($ROW) ? 'Edit Purchase Entry' : 'Purchase Entry') ?>
                    </h5>
                    <a href="<?= BASE_URL ?>purchase/list" class="btn btn-light btn-sm fw-bold">
                        <i class="fa fa-list"></i> Purchase List
                    </a>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-3">
                            <label class="form-label fw-bold">Purchase No</label>
                            <input type="text" class="form-control" value="<?= $ROW['purchase_no'] ?? ($purchase_no ?? 'AUTO') ?>" readonly>
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label fw-bold">Purchase Date <span class="text-danger">*</span></label>
                            <input type="date" id="purchase_date" name="purchase_date" class="form-control" 
                                value="<?= $ROW['purchase_date'] ?? date('Y-m-d'); ?>" 
                                <?= isset($is_view) && $is_view ? 'disabled' : 'required' ?>>
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label fw-bold">Invoice Date <span class="text-danger">*</span></label>
                            <input type="date" name="invoice_date" class="form-control" 
                                value="<?= $ROW['invoice_date'] ?? date('Y-m-d'); ?>" 
                                <?= isset($is_view) && $is_view ? 'disabled' : 'required' ?>>
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label fw-bold">Invoice No</label>
                            <input type="text" name="invoice_no" class="form-control" placeholder="Enter Invoice No" 
                                value="<?= $ROW['invoice_no'] ?? ''; ?>" 
                                <?= isset($is_view) && $is_view ? 'disabled' : '' ?>>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label fw-bold">Super Stockist <span class="text-danger">*</span></label>
                            <select class="form-select supplier_id" name="supplier_id" <?= isset($is_view) && $is_view ? 'disabled' : 'required' ?>>
                                <option value="">Loading...</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchase Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle purchase-table">
                    <thead class="table-primary text-center">
                        <tr>
                            <th style="width:20%">Product</th>
                            <th style="width:8%">Batch No</th>
                            <th style="width:8%">Expiry</th>
                            <th style="width:10%">MRP</th>
                            <th style="width:8%">P. Rate</th>
                            <th style="width:8%">P.Tax %</th>
                            <th style="width:10%">S. Rate</th>
                            <th style="width:7%">S.Tax %</th>
                            <th style="width:8%">Qty</th>
                            <th style="width:4%">Free</th>
                            <th style="width:8%">Disc %</th>
                            <th style="width:12%">Amount</th>
                            <?php if (!isset($is_view) || !$is_view): ?>
                                <th style="width:8%">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dynamic Entry Row (Hidden in View Mode) -->
                        <?php if (!isset($is_view) || !$is_view): ?>
                            <tr class="entry-row">
                                <td>
                                    <select class="form-select form-select-sm product select2">
                                        <option value="">Select Product</option>
                                        <?php foreach($Products as $product){ ?>
                                            <option value="<?= $product['p_id']; ?>"><?= $product['product_name']; ?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                                <td><input type="text" class="form-control form-control-sm batch" placeholder="Batch"></td>
                                <td style="width:90px;">
                                    <input type="text" class="form-control form-control-sm expiry" placeholder="MM/YYYY" maxlength="7">
                                </td>
                                <td><input type="number" step="0.01" class="form-control form-control-sm mrp text-end" placeholder="0.00"></td>
                                <td><input type="number" step="0.01" class="form-control form-control-sm rate text-end" placeholder="0.00"></td>
                                <td><input type="number" step="0.01" class="form-control form-control-sm tax text-center" value="" min="0"></td>
                                <td><input type="number" step="0.01" class="form-control form-control-sm srate text-end" placeholder="0.00"></td>
                                <td><input type="number" step="0.01" class="form-control form-control-sm stax text-center" value="" min="0"></td>
                                <td><input type="number" class="form-control form-control-sm qty text-center" value="" min="1"></td>
                                <td><input type="number" class="form-control form-control-sm free_qty text-center" value="" min="0"></td>
                                <td><input type="number" step="0.01" class="form-control form-control-sm disc text-center" value="" min="0"></td>
                                <td><input type="text" class="form-control form-control-sm amount text-end" value="0.00" readonly></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-success btn-sm add-row"><i class="fa fa-plus"></i></button>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <!-- Existing Items Loop (Edit/View Mode) -->
                        <?php if (isset($ROW_DETAILS) && mysqli_num_rows($ROW_DETAILS) > 0): ?>
                            <?php while ($item = mysqli_fetch_assoc($ROW_DETAILS)): 
                                // Format Expiry from YYYY-MM-01 back to MM/YYYY for display
                                $formatted_expiry = '';
                                if (!empty($item['expiry_date']) && $item['expiry_date'] !== '0000-00-00') {
                                    $dt = DateTime::createFromFormat('Y-m-d', $item['expiry_date']);
                                    if ($dt) {
                                        $formatted_expiry = $dt->format('m/Y');
                                    }
                                }
                            ?>
                                <tr>
                                    <td>
                                        <?= $item['product_name'] ?? 'Product'; ?>
                                        <input type="hidden" name="product_id[]" value="<?= $item['product_id']; ?>">
                                    </td>
                                    <td>
                                        <?= $item['batch_no']; ?>
                                        <input type="hidden" name="batch[]" value="<?= $item['batch_no']; ?>">
                                    </td>
                                    <td>
                                        <?= $formatted_expiry; ?>
                                        <input type="hidden" name="expiry[]" value="<?= $formatted_expiry; ?>">
                                    </td>
                                    <td class="text-end">
                                        <?= $item['mrp']; ?>
                                        <input type="hidden" name="mrp[]" value="<?= $item['mrp']; ?>">
                                    </td>
                                    <td class="text-end">
                                        <?= $item['purchase_rate']; ?>
                                        <input type="hidden" name="rate[]" value="<?= $item['purchase_rate']; ?>">
                                    </td>
                                    <td class="text-center">
                                        <?= $item['purchase_tax']; ?>
                                        <input type="hidden" name="tax[]" value="<?= $item['purchase_tax']; ?>">
                                    </td>
                                    <td class="text-end">
                                        <?= $item['sale_rate'] ?? '0.00'; ?>
                                        <input type="hidden" name="srate[]" value="<?= $item['sale_rate'] ?? '0.00'; ?>">
                                    </td>
                                    <td class="text-center">
                                        <?= $item['sale_tax'] ?? '0'; ?>
                                        <input type="hidden" name="stax[]" value="<?= $item['sale_tax'] ?? '0'; ?>">
                                    </td>
                                    <td class="text-center">
                                        <?= $item['qty']; ?>
                                        <input type="hidden" name="qty[]" value="<?= $item['qty']; ?>">
                                    </td>
                                    <td class="text-center">
                                        <?= $item['free_qty']; ?>
                                        <input type="hidden" name="free_qty[]" value="<?= $item['free_qty']; ?>">
                                    </td>
                                    <td class="text-center">
                                        <?= $item['discount_amt']; ?>
                                        <input type="hidden" name="disc[]" value="<?= $item['discount_amt']; ?>">
                                    </td>
                                    <td class="amount text-end fw-bold">
                                        <?= $item['amount']; ?>
                                        <input type="hidden" name="amount[]" value="<?= $item['amount']; ?>">
                                    </td>
                                    <?php if (!isset($is_view) || !$is_view): ?>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-warning btn-sm edit-row"><i class="fa fa-edit"></i></button>
                                            <button type="button" class="btn btn-danger btn-sm delete-row"><i class="fa fa-trash"></i></button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="row mt-4">
                <!-- Left Side -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <strong>Purchase Information</strong>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label>LR No.</label>
                                    <input type="text" name="lr_no" class="form-control" value="<?= $ROW['lr_no'] ?? ''; ?>" <?= isset($is_view) && $is_view ? 'disabled' : '' ?> placeholder="LR Number">
                                </div>
                                <div class="col-md-3">
                                    <label>CD %</label>
                                    <input type="number" step="0.01" id="cd_percent" name="cd_no" class="form-control" value="<?= $ROW['cdper'] ?? ''; ?>" <?= isset($is_view) && $is_view ? 'disabled' : '' ?> placeholder="CD %">
                                </div>
                                <div class="col-md-3">
                                    <label>E-Way Bill</label>
                                    <input type="text" name="eway_bill" class="form-control" value="<?= $ROW['eway_bill_no'] ?? ''; ?>" <?= isset($is_view) && $is_view ? 'disabled' : '' ?> placeholder="E-Way Bill">
                                </div>
                                <div class="col-md-3">
                                    <label>Vehicle No.</label>
                                    <input type="text" name="vehicle_no" class="form-control" value="<?= $ROW['vehicle_no'] ?? ''; ?>" <?= isset($is_view) && $is_view ? 'disabled' : '' ?> placeholder="Vehicle Number">
                                </div>
                                <div class="col-md-3">
                                    <label>Transport Name</label>
                                    <input type="text" name="transport_name" class="form-control" value="<?= $ROW['transport_name'] ?? ''; ?>" <?= isset($is_view) && $is_view ? 'disabled' : '' ?>>
                                </div>
                                <div class="col-md-3">
                                    <label>Credit Days</label>
                                    <input type="number" name="credit_days" class="form-control" value="<?= $ROW['credit_days'] ?? ''; ?>" <?= isset($is_view) && $is_view ? 'disabled' : '' ?>>
                                </div>
                                <div class="col-md-3">
                                    <label>Discount</label>
                                    <input type="number" step="0.01" id="discount" name="discount" class="form-control" value="<?= $ROW['discount'] ?? ''; ?>" <?= isset($is_view) && $is_view ? 'disabled' : '' ?>>
                                </div>
                                <div class="col-md-3">
                                    <label>Other Charges</label>
                                    <div class="input-group">
                                        <?php 
                                            // Determine saved sign and absolute amount from database value
                                            $raw_charges = $ROW['other_charges'] ?? 0;
                                            $saved_sign = ($raw_charges < 0) ? '-' : '+';
                                            $saved_amt = ($raw_charges != 0) ? abs($raw_charges) : '';
                                        ?>
                                        <select id="other_charges_sign" name="other_charges_sign" class="form-select" style="max-width: 60px;" <?= isset($is_view) && $is_view ? 'disabled' : '' ?>>
                                            <option value="+" <?= $saved_sign === '+' ? 'selected' : '' ?>>+</option>
                                            <option value="-" <?= $saved_sign === '-' ? 'selected' : '' ?>>-</option>
                                        </select>
                                        <input type="number" step="0.01" id="other_charges" name="other_charges" class="form-control" value="<?= $saved_amt; ?>" <?= isset($is_view) && $is_view ? 'disabled' : '' ?>>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label>Remarks</label>
                                    <textarea class="form-control" rows="2" name="remarks" <?= isset($is_view) && $is_view ? 'disabled' : '' ?>><?= $ROW['remarks'] ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-primary">
                        <div class="card-header bg-primary text-white">
                            <strong>Bill Summary</strong>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm mb-0">
                                <tr><th>Total Qty</th><td class="text-end" id="show_total_qty">0</td></tr>
                                <tr><th>Taxable Amount</th><td class="text-end" id="show_taxable">0.00</td></tr>
                                <tr><th>CGST</th><td class="text-end" id="show_cgst">0.00</td></tr>
                                <tr><th>SGST</th><td class="text-end" id="show_sgst">0.00</td></tr>
                                <tr><th>IGST</th><td class="text-end" id="show_igst">0.00</td></tr>
                                <tr><th>VAT</th><td class="text-end" id="show_vat">0.00</td></tr>
                                <tr><th>GST Amount</th><td class="text-end" id="show_gst">0.00</td></tr>
                                <tr><th>Discount</th><td class="text-end" id="show_discount">0.00</td></tr>
                                <tr><th>CD Amount</th><td class="text-end" id="show_cd">0.00</td></tr>
                                <tr><th>Other Charges</th><td class="text-end" id="show_other">0.00</td></tr>
                                <tr class="table-primary">
                                    <th>Grand Total</th>
                                    <th class="text-end fs-5 text-success" id="show_grand_total">&#8377;0.00</th>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="total_qty" id="total_qty" value="0">
            <input type="hidden" name="grand_total" id="grand_total" value="0">
            <input type="hidden" name="gsttype" id="gst_type_input" value="<?= $ROW['gst_type'] ?? 'cgst'; ?>">
            <input type="hidden" name="gst_amt" id="input_gst_amt" value="<?= $ROW['gst_amount'] ?? '0'; ?>">
            <input type="hidden" name="cgst" id="input_cgst" value="<?= $ROW['cgst_amount'] ?? '0'; ?>">
            <input type="hidden" name="sgst" id="input_sgst" value="<?= $ROW['sgst_amount'] ?? '0'; ?>">
            <input type="hidden" name="igst" id="input_igst" value="<?= $ROW['igst_amount'] ?? '0'; ?>">
            <input type="hidden" name="vat" id="input_vat" value="<?= $ROW['vat_amount'] ?? '0'; ?>">
        </div>

        <?php if (!isset($is_view) || !$is_view): ?>
            <button type="submit" class="btn btn-success mt-3">
                <i class="fa fa-save"></i> <?= isset($ROW) ? 'Update Purchase' : 'Submit Purchase' ?>
            </button>
        <?php endif; ?>
    </form>
</div>

<?php include 'view/layout/footer.php'; ?>

<script>
$(document).ready(function(){

// Auto-format MM/YYYY or MM/YY for expiry input field
$(document).on('input', '.expiry', function (e) {
    let value = $(this).val();
    
    // Remove all non-digit characters
    value = value.replace(/\D/g, '');
    
    // Limit total digits to max 6 (MMYYYY) or 4 (MMYY)
    if (value.length > 6) {
        value = value.substring(0, 6);
    }
    
    // Automatically insert slash after 2 digits (Month)
    if (value.length > 2) {
        value = value.substring(0, 2) + '/' + value.substring(2);
    }
    
    $(this).val(value);
});

// Handle Backspace correctly so it removes the slash smoothly
$(document).on('keydown', '.expiry', function (e) {
    let value = $(this).val();
    
    if (e.key === 'Backspace' && value.length === 3 && value.endsWith('/')) {
        e.preventDefault();
        $(this).val(value.substring(0, 2));
    }
});
// 1. When a product is selected from Select2 dropdown -> focus on Batch input
$('.product').on('select2:select', function () {
    $(this).closest('.entry-row').find('.batch').focus();
});

// 2. Press Enter on Batch -> go to Expiry
$(document).on('keydown', '.batch', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('.entry-row').find('.expiry').focus().select();
    }
});

// 3. Press Enter on Expiry -> go to MRP
$(document).on('keydown', '.expiry', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('.entry-row').find('.mrp').focus().select();
    }
});

// 4. Press Enter on MRP -> go to Rate
$(document).on('keydown', '.mrp', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('.entry-row').find('.rate').focus().select();
    }
});

// 5. Press Enter on Rate -> go to Tax
$(document).on('keydown', '.rate', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('.entry-row').find('.tax').focus().select();
    }
});

// 6. Press Enter on Tax -> go to Sale Rate (srate)
$(document).on('keydown', '.tax', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('.entry-row').find('.srate').focus().select();
    }
});

// 7. Press Enter on Sale Rate -> go to Sale Tax (stax)
$(document).on('keydown', '.srate', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('.entry-row').find('.stax').focus().select();
    }
});

// 8. Press Enter on Sale Tax -> go to Qty
$(document).on('keydown', '.stax', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('.entry-row').find('.qty').focus().select();
    }
});

// 9. Press Enter on Qty -> go to Free Qty
$(document).on('keydown', '.qty', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('.entry-row').find('.free_qty').focus().select();
    }
});

// 10. Press Enter on Free Qty -> go to Discount (disc)
$(document).on('keydown', '.free_qty', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('.entry-row').find('.disc').focus().select();
    }
});

// 11. Press Enter on Discount -> Click the Add Row button
$(document).on('keydown', '.disc', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('.entry-row').find('.add-row').click();
    }
});

    let selectedStockist = "<?= $ROW['super_stockist_id'] ?? '' ?>";
    loadSuperStockist(selectedStockist);

    let gst_type = "<?= $ROW['gst_type'] ?? 'cgst' ?>";

    <?php if (isset($ROW)): ?>
        calculateTotal();
    <?php endif; ?>

    $('.supplier_id').on('change', function () {
        let state = $(this).find(':selected').data('state');
        let current_state = "Gujarat";
        state = (state || "").toLowerCase();
        current_state = current_state.toLowerCase();

        if (state === "nepal") {
            gst_type = "vat";
        } else if (state === current_state) {
            gst_type = "cgst";
        } else {
            gst_type = "igst";
        }
        calculateTotal();
    });

    let editRow = null;

    $(document).on('keyup change','.qty,.rate,.disc,.tax',function(){
        updateEntryRowAmount();
    });

    function updateEntryRowAmount(){
        let row = $('.entry-row');
        let qty  = parseFloat(row.find('.qty').val()) || 0;
        let rate = parseFloat(row.find('.rate').val()) || 0;
        let disc = parseFloat(row.find('.disc').val()) || 0;
        let tax  = parseFloat(row.find('.tax').val()) || 0;

        let base     = qty * rate;
        let discAmt  = base * (disc / 100);
        let taxable  = base - discAmt;
        let taxAmt   = taxable * (tax / 100);
        let amount   = taxable + taxAmt;

        row.find('.amount').val(amount.toFixed(2));
    }

    $(document).on('click','.add-row',function(){
        let row = $('.entry-row');
        let product     = row.find('.product').val();
        let productName = row.find('.product option:selected').text();
        let batch   = row.find('.batch').val();
        let expiry  = row.find('.expiry').val();
        let mrp     = row.find('.mrp').val();
        let qty     = row.find('.qty').val();
        let freeQty = row.find('.free_qty').val();
        let rate    = row.find('.rate').val();
        let srate   = row.find('.srate').val();
        let disc    = row.find('.disc').val() || 0;
        let tax     = row.find('.tax').val() || 0;
        let stax    = row.find('.stax').val() || 0;

        if(product=="") { alert("Please select product."); return; }
        if(batch=="") { alert("Enter Batch Number."); return; }
        if(expiry=="") { alert("Select Expiry."); return; }
        if(qty=="" || qty<=0) { alert("Enter Quantity."); return; }
        if(rate=="" || rate<=0) { alert("Enter Purchase Rate."); return; }

        let exists = false;
        $('.purchase-table tbody tr').not('.entry-row').each(function () {
            if (editRow && $(this)[0] === editRow[0]) return true;
            let oldProduct = $(this).find('input[name="product_id[]"]').val();
            let oldBatch   = $(this).find('input[name="batch[]"]').val();
            if (oldProduct == product && oldBatch == batch) {
                exists = true;
                return false;
            }
        });

        if (exists) {
            alert("This Product with the same Batch already exists.");
            return;
        }

        let base     = (parseFloat(qty) || 0) * (parseFloat(rate) || 0);
        let discAmt  = base * ((parseFloat(disc) || 0) / 100);
        let taxable  = base - discAmt;
        let taxAmt   = taxable * ((parseFloat(tax) || 0) / 100);
        let amount   = (taxable + taxAmt).toFixed(2);

        let html = `
        <tr>
            <td>${productName}<input type="hidden" name="product_id[]" value="${product}"></td>
            <td>${batch}<input type="hidden" name="batch[]" value="${batch}"></td>
            <td>${expiry}<input type="hidden" name="expiry[]" value="${expiry}"></td>
            <td class="text-end">${mrp}<input type="hidden" name="mrp[]" value="${mrp}"></td>
            <td class="text-end">${rate}<input type="hidden" name="rate[]" value="${rate}"></td>
            <td class="text-center">${tax}<input type="hidden" name="tax[]" value="${tax}"></td>
            <td class="text-end">${srate}<input type="hidden" name="srate[]" value="${srate}"></td>
            <td class="text-center">${stax}<input type="hidden" name="stax[]" value="${stax}"></td>
            <td class="text-center">${qty}<input type="hidden" name="qty[]" value="${qty}"></td>
            <td class="text-center">${freeQty}<input type="hidden" name="free_qty[]" value="${freeQty}"></td>
            <td class="text-center">${disc}<input type="hidden" name="disc[]" value="${disc}"></td>
            <td class="amount text-end fw-bold">${amount}<input type="hidden" name="amount[]" value="${amount}"></td>
            <td class="text-center">
                <button type="button" class="btn btn-warning btn-sm edit-row"><i class="fa fa-edit"></i></button>
                <button type="button" class="btn btn-danger btn-sm delete-row"><i class="fa fa-trash"></i></button>
            </td>
        </tr>`;

        if(editRow) {
            editRow.replaceWith(html);
            editRow = null;
            $('.add-row').html('<i class="fa fa-plus"></i>');
        } else {
            $('.purchase-table tbody').append(html);
        }

        row.find('.product').val('').trigger('change');
        row.find('.batch').val('');
        row.find('.expiry').val('');
        row.find('.mrp').val('');
        row.find('.qty').val('');
        row.find('.free_qty').val('');
        row.find('.rate').val('');
        row.find('.srate').val('');
        row.find('.disc').val('');
        row.find('.tax').val('');
        row.find('.amount').val('0.00');
        row.find('.stax').val('');

        row.find('.product').select2('open');

        calculateTotal();
    });

    $(document).on('click','.edit-row',function(){
        editRow = $(this).closest('tr');

        let prodId = editRow.find('input[name="product_id[]"]').val();
        $('.entry-row .product').val(prodId).trigger('change');
        $('.entry-row .batch').val(editRow.find('input[name="batch[]"]').val());
        $('.entry-row .expiry').val(editRow.find('input[name="expiry[]"]').val());
        $('.entry-row .mrp').val(editRow.find('input[name="mrp[]"]').val());
        $('.entry-row .qty').val(editRow.find('input[name="qty[]"]').val());
        $('.entry-row .free_qty').val(editRow.find('input[name="free_qty[]"]').val());
        $('.entry-row .rate').val(editRow.find('input[name="rate[]"]').val());
        $('.entry-row .srate').val(editRow.find('input[name="srate[]"]').val());
        $('.entry-row .disc').val(editRow.find('input[name="disc[]"]').val());
        $('.entry-row .tax').val(editRow.find('input[name="tax[]"]').val());
        $('.entry-row .stax').val(editRow.find('input[name="stax[]"]').val());

        updateEntryRowAmount();
        $('.add-row').html('<i class="fa fa-save"></i>');
    });

    $(document).on('click','.delete-row',function(){
        $(this).closest('tr').remove();
        calculateTotal();
    });

    $(document).on('keyup change', '#discount, #cd_percent, #other_charges, #other_charges_sign', function(){
        calculateTotal();
    });

    function calculateTotal() {
        let totalQty = 0;
        let totalBase = 0;
        let totalDisc = 0;
        let totalTax = 0;

        $('.purchase-table tbody tr').not('.entry-row').each(function(){
            let qty  = parseFloat($(this).find('input[name="qty[]"]').val()) || 0;
            let rate = parseFloat($(this).find('input[name="rate[]"]').val()) || 0;
            let disc = parseFloat($(this).find('input[name="disc[]"]').val()) || 0;
            let tax  = parseFloat($(this).find('input[name="tax[]"]').val()) || 0;

            let base    = qty * rate;
            let discAmt = base * (disc / 100);
            let taxable = base - discAmt;
            let taxAmt  = taxable * (tax / 100);

            totalQty  += qty;
            totalBase += base;
            totalDisc += discAmt;
            totalTax  += taxAmt;
        });

        let taxableAmount = totalBase - totalDisc;
        let cdPercent = parseFloat($('#cd_percent').val()) || 0;
        let cdAmount  = taxableAmount * (cdPercent / 100);
        let netTaxable = taxableAmount - cdAmount;

        let cdTaxAmount = totalTax * (cdPercent / 100);
        let netTax = totalTax - cdTaxAmount;
        
        let cgst = 0, sgst = 0, igst = 0, vat = 0;

        $('#show_cgst').closest('tr').hide();
        $('#show_sgst').closest('tr').hide();
        $('#show_igst').closest('tr').hide();
        $('#show_vat').closest('tr').hide();

        switch (gst_type) {
            case "cgst":
                cgst = netTax / 2;
                sgst = netTax / 2;
                $('#show_cgst').closest('tr').show();
                $('#show_sgst').closest('tr').show();
                break;
            case "igst":
                igst = netTax;
                $('#show_igst').closest('tr').show();
                break;
            case "vat":
                vat = netTax;
                $('#show_vat').closest('tr').show();
                break;
        }

        $('#show_cgst').text(cgst.toFixed(2));
        $('#show_sgst').text(sgst.toFixed(2));
        $('#show_igst').text(igst.toFixed(2));
        $('#show_vat').text(vat.toFixed(2));

        let headerDiscount = parseFloat($('#discount').val()) || 0;
        let otherCharges     = parseFloat($('#other_charges').val()) || 0;
        let otherChargesSign = $('#other_charges_sign').val() === '-' ? -1 : 1;
        let otherChargesSigned = otherCharges * otherChargesSign;

        let grandTotal = netTaxable + netTax - headerDiscount + otherChargesSigned;

        $('#show_total_qty').text(totalQty);
        $('#show_taxable').text(netTaxable.toFixed(2));
        $('#show_gst').text(netTax.toFixed(2));
        $('#show_discount').text(headerDiscount.toFixed(2));
        $('#show_cd').text(cdAmount.toFixed(2));
        $('#show_other').text((otherChargesSign < 0 ? '-' : '') + otherCharges.toFixed(2));
        $('#show_grand_total').html('&#8377;' + grandTotal.toFixed(2));

        $('#total_qty').val(totalQty);
        $('#grand_total').val(grandTotal.toFixed(2));
        $('#gst_type_input').val(gst_type);
        
        $('#input_gst_amt').val(netTax.toFixed(2));
        $('#input_cgst').val(cgst.toFixed(2));
        $('#input_sgst').val(sgst.toFixed(2));
        $('#input_igst').val(igst.toFixed(2));
        $('#input_vat').val(vat.toFixed(2));
    }

    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: "Select Product",
        allowClear: true
    });

    $('#purchaseForm').on('submit',function(e){
        if($('.purchase-table tbody tr:not(.entry-row)').length == 0) {
            alert("Please add at least one product");
            e.preventDefault();
            return false;
        }
    });
});

function loadSuperStockist(selected = '') {
    $.ajax({
        url: "<?= BASE_URL ?>purchase/getSuperStockist",
        type: "POST",
        data: { selected_stockist: selected },
        success: function(response) {
            $('.supplier_id').html(response);
        }
    });
}
</script>