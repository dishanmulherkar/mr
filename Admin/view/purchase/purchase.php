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
        /* Remove number input arrows */
            input[type=number]::-webkit-inner-spin-button,
            input[type=number]::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
/* Firefox */
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
        <!-- ===  Flash Messages  === -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Product saved successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-info">Product deleted successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">Something went wrong. Please try again.</div>
        <?php endif; ?>
        <?php if (isset($_GET['duplicate'])): ?>
            <div class="alert alert-warning">Product name already exists!</div>
        <?php endif; ?>
        <?php if (isset($_GET['toggled'])): ?>
            <div class="alert alert-info">Product status updated.</div>
        <?php endif; ?>

        <!--
            IMPORTANT FIX: previously the <form> closed right after the item table,
            which meant the "Purchase Information" fields (LR No, CD %, E-Way Bill,
            Vehicle No, Transport Name, Credit Days, Discount, Other Charges,
            Remarks) and the Submit button were all OUTSIDE the form and never
            got posted to the server. The form now wraps the entire page content
            and closes right before the closing tag of #container.
        -->
            
        <form action="<?= BASE_URL ?>purchase" method="POST" id="purchaseForm">

            <div class="purchase-card">

                    <!-- Supplier Details -->

                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fa fa-shopping-cart"></i> Purchase Entry
                            </h5>
                        </div>

                        <div class="card-body">

                            <div class="row g-3">
                                <!-- Purchase No -->
                                <div class="col-lg-3">
                                    <label class="form-label fw-bold">Purchase No</label>
                                    <input type="text"
                                        class="form-control"
                                        value="<?= $purchase_no ?? 'AUTO' ?>"
                                        readonly>
                                </div>

                                <!-- Purchase Date -->
                                <div class="col-lg-3">
                                    <label class="form-label fw-bold">
                                        Purchase Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date"
                                        id="purchase_date"
                                        name="purchase_date"
                                        class="form-control"
                                        value="<?= date('Y-m-d'); ?>"
                                        required>
                                </div>

                                <!-- Invoice Date -->
                                <div class="col-lg-3">
                                    <label class="form-label fw-bold">
                                        Invoice Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date"
                                        name="invoice_date"
                                        class="form-control"
                                        value="<?= date('Y-m-d'); ?>"
                                        required>
                                </div>

                                <!-- Invoice No -->
                                <div class="col-lg-3">
                                    <label class="form-label fw-bold">Invoice No</label>
                                    <input type="text"
                                        name="invoice_no"
                                        class="form-control"
                                        placeholder="Enter Invoice No">
                                </div>

                                <!-- super Stockist -->
                                <div class="col-lg-6">
                                    <label class="form-label fw-bold">
                                        Super Stockist <span class="text-danger">*</span>
                                    </label>

                                    <select class="form-select supplier_id" name="supplier_id" required>
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
                                    <th style="width:10%">P. Rate</th>
                                    <th style="width:8%">P.Tax %</th>
                                    <th style="width:10%">S. Rate</th>
                                    <th style="width:8%">S.Tax %</th>
                                    <th style="width:8%">Qty</th>
                                    <th style="width:8%">Free</th>
                                    <th style="width:8%">Disc %</th>
                                    
                                    <th style="width:12%">Amount</th>
                                    <th style="width:8%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="entry-row">
                                    <!-- Product -->
                                    <td>
                                        <select class="form-select form-select-sm product select2">
                                            <option value="">Select Product</option>
                                            <?php foreach($Products as $product){ ?>
                                                <option value="<?= $product['p_id']; ?>">
                                                    <?= $product['product_name']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </td>

                                    <!-- Batch -->
                                    <td>
                                        <input type="text" class="form-control form-control-sm batch" placeholder="Batch">
                                    </td>

                                    <!-- Expiry -->
                                    <td style="width:90px;">
                                        <input type="text"
                                            name="expiry[]"
                                            class="form-control form-control-sm expiry"
                                            placeholder="MM/YYYY"
                                            maxlength="7">
                                            <div class="invalid-feedback">
                                                    Enter expiry in MM/YYYY format.
                                                </div>
                                    </td>
                                    <!-- MRP -->
                                    <td>
                                        <input type="number" step="0.01" class="form-control form-control-sm mrp text-end" placeholder="0.00">
                                    </td>

                                    <!-- Purchase Rate -->
                                    <td>
                                        <input type="number" step="0.01" class="form-control form-control-sm rate text-end" placeholder="0.00">
                                    </td>

                                        <!-- Tax % (fixed: class renamed from "tax%" to "tax" -
                                            "%" is not a valid character in a jQuery/CSS class
                                            selector, so $('.tax%') was silently failing) -->
                                    <td>
                                        <input type="number"
                                            step="0.01"
                                            class="form-control form-control-sm tax text-center"
                                            value=""
                                            min="0">
                                    </td>

                                    <!-- Sales Rate -->
                                    <td>
                                        <input type="number"
                                            step="0.01"
                                            class="form-control form-control-sm srate text-end"
                                            placeholder="0.00">
                                    </td>

                                       <!-- Tax % (fixed: class renamed from "tax%" to "tax" -
                                            "%" is not a valid character in a jQuery/CSS class
                                            selector, so $('.tax%') was silently failing) -->
                                    <td>
                                        <input type="number"
                                            step="0.01"
                                            class="form-control form-control-sm stax text-center"
                                            value=""
                                            min="0">
                                    </td>

                                    <!-- Qty -->
                                    <td>
                                        <input type="number"
                                            class="form-control form-control-sm qty text-center"
                                            value=""
                                            min="1">
                                    </td>

                                    <!-- Free Qty -->
                                    <td>
                                        <input type="number"
                                            class="form-control form-control-sm free_qty text-center"
                                            value=""
                                            min="0">
                                    </td>

                                    <!-- Discount % -->
                                    <td>
                                        <input type="number"
                                            step="0.01"
                                            class="form-control form-control-sm disc text-center"
                                            value=""
                                            min="0">
                                    </td>

                                   

                                    <!-- Amount -->
                                    <td>
                                        <input type="text"
                                            class="form-control form-control-sm amount text-end"
                                            value="0.00"
                                            readonly>
                                    </td>

                                    <!-- Action -->
                                    <td class="text-center">

                                        <button type="button"
                                                class="btn btn-success btn-sm add-row">

                                            <i class="fa fa-plus"></i>

                                        </button>

                                    </td>

                                </tr>

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
                                    <input type="text"
                                           name="lr_no"
                                           class="form-control"
                                           placeholder="LR Number">
                                </div>

                                <div class="col-md-3">
                                    <label>CD %</label>
                                    <input type="number"
                                           step="0.01"
                                           id="cd_percent"
                                           name="cd_no"
                                           class="form-control"
                                           placeholder="CD %"
                                           value="">
                                </div>

                                <div class="col-md-3">
                                    <label>E-Way Bill</label>
                                    <input type="text"
                                           name="eway_bill"
                                           class="form-control"
                                           placeholder="E-Way Bill">
                                </div>

                                <div class="col-md-3">
                                    <label>Vehicle No.</label>
                                    <input type="text"
                                           name="vehicle_no"
                                           class="form-control"
                                           placeholder="Vehicle Number">
                                </div>

                                <div class="col-md-3">
                                    <label>Transport Name</label>
                                    <input type="text"
                                           name="transport_name"
                                           class="form-control">
                                </div>

                                <div class="col-md-3">
                                    <label>Credit Days</label>
                                    <input type="number"
                                           name="credit_days"
                                           class="form-control"
                                           value="">
                                </div>

                                <div class="col-md-3">
                                    <label>Discount</label>
                                    <input type="number"
                                           step="0.01"
                                           id="discount"
                                           name="discount"
                                           class="form-control"
                                           value="">
                                </div>

                                <div class="col-md-3">
                                    <label>Other Charges</label>
                                    <div class="input-group">
                                        <select id="other_charges_sign" name="other_charges_sign" class="form-select" style="max-width: 60px; ">
                                            <option value="+" selected>+</option>
                                            <option value="-">-</option>
                                        </select>
                                        <input type="number"
                                               step="0.01"
                                               id="other_charges"
                                               name="other_charges"
                                               class="form-control"
                                               value="">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label>Remarks</label>
                                    <textarea class="form-control"
                                              rows="2"
                                              name="remarks"></textarea>
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
                                <tr>
                                    <th>Total Qty</th>
                                    <td class="text-end" id="show_total_qty">0</td>
                                </tr>

                                <tr>
                                    <th>Taxable Amount</th>
                                    <td class="text-end" id="show_taxable">0.00</td>
                                </tr>

                                <tr>
                                    <th>CGST</th>
                                    <td class="text-end" id="show_cgst">0.00</td>
                                </tr>

                                <tr>
                                    <th>SGST</th>
                                    <td class="text-end" id="show_sgst">0.00</td>
                                </tr>

                                <tr>
                                    <th>IGST</th>
                                    <td class="text-end" id="show_igst">0.00</td>
                                </tr>

                                 <tr>
                                    <th>VAT</th>
                                    <td class="text-end" id="show_vat">0.00</td>
                                </tr>

                                <tr>
                                    <th>GST Amount</th>
                                    <td class="text-end" id="show_gst">0.00</td>
                                </tr>

                                <tr>
                                    <th>Discount</th>
                                    <td class="text-end" id="show_discount">0.00</td>
                                </tr>

                                <tr>
                                    <th>CD Amount</th>
                                    <td class="text-end" id="show_cd">0.00</td>
                                </tr>

                                <tr>
                                    <th>Other Charges</th>
                                    <td class="text-end" id="show_other">0.00</td>
                                </tr>

                                <tr class="table-primary">

                                    <th>Grand Total</th>

                                    <th class="text-end fs-5 text-success"
                                        id="show_grand_total">

                                        &#8377;0.00

                                    </th>

                                </tr>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Hidden totals - moved out of the <tr class="entry-row"> where they were
                 invalid markup (bare <input> as a direct child of <tr>, outside any <td>).
                 Browsers silently hoist stray table children out of the table, so these
                 values were never reliably posted. They now live directly in the form. -->
            <input type="hidden" name="total_qty" id="total_qty" value="0">
            <input type="hidden" name="grand_total" id="grand_total" value="0">

            </div>

            <button type="submit" class="btn btn-success">
                <i class="fa fa-save"></i> Submit Purchase
            </button>

        </form>
 </div>
<?php 
include 'view/layout/footer.php'; 
?>
<script>
// add auto focus code 

// Product selected → Batch
$(document).on('select2:select', '.product', function () {
    $(this).closest('tr').find('.batch').focus();
});

// Batch → Expiry
$(document).on('keydown', '.batch', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('tr').find('.expiry').focus();
    }
});

// Expiry → MRP
$(document).on('keydown', '.expiry', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('tr').find('.mrp').focus();
    }
});

// MRP → Purchase Rate
$(document).on('keydown', '.mrp', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('tr').find('.rate').focus();
    }
});

// Purchase Rate → Sales Rate
$(document).on('keydown', '.rate', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('tr').find('.srate').focus();
    }
});

// Sales Rate → Qty
$(document).on('keydown', '.srate', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('tr').find('.qty').focus();
    }
});

// Qty → Free Qty
$(document).on('keydown', '.qty', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('tr').find('.free_qty').focus();
    }
});

// Free Qty → Discount
$(document).on('keydown', '.free_qty', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('tr').find('.disc').focus();
    }
});

// Discount → Tax
$(document).on('keydown', '.disc', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('tr').find('.tax').focus();
    }
});

// Tax → Add Button
$(document).on('keydown', '.tax', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('tr').find('.add-row').click();
    }
});

// Auto-format MM/YYYY
$(document).on('input', '.expiry', function () {

    let value = $(this).val().replace(/\D/g, '');

    if (value.length > 2) {
        value = value.substring(0,2) + '/' + value.substring(2,6);
    }

    $(this).val(value);
});

    $(document).on('blur', '.expiry', function () {

        let value = $(this).val().trim();

        if (value === '') return;

        let parts = value.split('/');

        if (parts.length !== 2) {
            $(this).val('').focus();
            return;
        }

        let month = parseInt(parts[0], 10);
        let year  = parseInt(parts[1], 10);

        if (month < 1 || month > 12) {
    $(this).addClass('is-invalid');
    return;
} else {
    $(this).removeClass('is-invalid');
}
    });
 
    function loadSuperStockist(selected = '')
    {
        $.ajax({
            url: "<?= BASE_URL ?>purchase/getSuperStockist",
            type: "POST",
            data: {
                selected_stockist: selected
            },
            success: function(response) {
                $('.supplier_id').html(response);
            }
        });
    }


// Auto-hide messages 
setTimeout(function(){

    $('.alert').fadeOut('slow');

    let url = new URL(window.location.href);
    url.searchParams.delete('success');
    url.searchParams.delete('error');

    window.history.replaceState({}, document.title, url.pathname);

},5000);

$(document).ready(function(){
let gst_type = "cgst"; // Default
 $('.supplier_id').on('change', function () {

    let stockistId = $(this).val();
    let state = $(this).find(':selected').data('state');
    
    let current_state = "Gujarat";

    // Convert to lowercase for case-insensitive comparison
    state = (state || "").toLowerCase();
    current_state = current_state.toLowerCase();

    console.log(stockistId);
    console.log(state);

    if (state === "nepal") {
        gst_type = "vat";
    } else if (state === current_state) {
        gst_type = "cgst";
    } else {
        gst_type = "igst";
    }
              calculateTotal();
    console.log(gst_type);
});

   loadSuperStockist();
let editRow = null;

    // NOTE ON TAX SPLIT: with no supplier-state-vs-company-state comparison
    // available yet, every item's tax % is assumed intra-state and is split
    // evenly into CGST + SGST (IGST is left at 0). If interstate purchases
    // need to be supported, this is the place to branch on state and route
    // the full tax % into IGST instead.

    // Live preview of the entry-row amount as the user types
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

    // Add Product Row
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
         let stax     = row.find('.stax').val() || 0;

        if(product=="")
        {
            alert("Please select product.");
            return;
        }
        if(batch=="")
        {
            alert("Enter Batch Number.");
            return;
        }
        if(expiry=="")
        {
            alert("Select Expiry.");
            return;
        }

        if(qty=="" || qty<=0)
        {
            alert("Enter Quantity.");
            return;
        }

        if(rate=="" || rate<=0)
        {
            alert("Enter Purchase Rate.");
            return;
        }

        setTimeout(function () {
            $('.entry-row .product').select2('open');
        }, 100);

        // Duplicate check Product + Batch

        let exists = false;

        $('.purchase-table tbody tr').not('.entry-row').each(function () {

            // Skip the row currently being edited
            if (editRow && $(this)[0] === editRow[0]) {
                return true; // continue
            }

            let oldProduct = $(this).find('input[name="product_id[]"]').val();
            let oldBatch   = $(this).find('input[name="batch[]"]').val();

            if (oldProduct == product && oldBatch == batch) {
                exists = true;
                return false; // stop loop
            }

        });

        if (exists) {
            alert("This Product with the same Batch already exists.");
            return;
        }

        // Amount = (qty * rate) - discount% + tax%  (see updateEntryRowAmount above)
        let base     = (parseFloat(qty) || 0) * (parseFloat(rate) || 0);
        let discAmt  = base * ((parseFloat(disc) || 0) / 100);
        let taxable  = base - discAmt;
        let taxAmt   = taxable * ((parseFloat(tax) || 0) / 100);
        let amount   = (taxable + taxAmt).toFixed(2);

        let html = `
    <tr>

    <td>
    ${productName}
    <input type="hidden" name="product_id[]" value="${product}">
    </td>

    <td>
    ${batch}
    <input type="hidden" name="batch[]" value="${batch}">
    </td>

    <td>
    ${expiry}
    <input type="hidden" name="expiry[]" value="${expiry}">
    </td>

    <td class="text-end">
    ${mrp}
    <input type="hidden" name="mrp[]" value="${mrp}">
    </td>

    <td class="text-end">
    ${rate}
    <input type="hidden" name="rate[]" value="${rate}">
    </td>

     <td class="text-center">
    ${tax}
    <input type="hidden" name="tax[]" value="${tax}">
    </td>


    <td class="text-end">
    ${srate}
    <input type="hidden" name="srate[]" value="${srate}">
    </td>

    <td class="text-center">
    ${stax}
    <input type="hidden" name="stax[]" value="${stax}">
    </td>

    <td class="text-center">
    ${qty}
    <input type="hidden" name="qty[]" value="${qty}">
    </td>

    <td class="text-center">
    ${freeQty}
    <input type="hidden" name="free_qty[]" value="${freeQty}">
    </td>

    <td class="text-center">
    ${disc}
    <input type="hidden" name="disc[]" value="${disc}">
    </td>

    <td class="amount-cell text-end fw-bold">
    ${amount}
    <input type="hidden" name="amount[]" value="${amount}">
    </td>

    <td class="text-center">

    <button type="button" class="btn btn-warning btn-sm edit-row">
    <i class="fa fa-edit"></i>
    </button>

    <button type="button" class="btn btn-danger btn-sm delete-row">
    <i class="fa fa-trash"></i>
    </button>

    </td>

    </tr>
    `;

        if(editRow)
        {
            editRow.replaceWith(html);
            editRow = null;
            $('.add-row').html('<i class="fa fa-plus"></i>');
        }
        else
        {
            $('.purchase-table tbody').append(html);
        }

        // Clear Entry Row

        row.find('.product').val('').trigger('change');
        row.find('.batch').val('');
        row.find('.expiry').val('');
        row.find('.mrp').val('');
        row.find('.qty').val(1);
        row.find('.free_qty').val(0);
        row.find('.rate').val('');
        row.find('.srate').val('');
        row.find('.disc').val(0);
        row.find('.tax').val(0);
        row.find('.amount').val('0.00');
        row.find('.stax').val(0);

        calculateTotal();

    });

    // Edit Row

    $(document).on('click','.edit-row',function(){

        editRow = $(this).closest('tr');

        $('.product').val(editRow.find('input[name="product_id[]"]').val()).trigger('change');
        $('.batch').val(editRow.find('input[name="batch[]"]').val());
        $('.expiry').val(editRow.find('input[name="expiry[]"]').val());
        $('.mrp').val(editRow.find('input[name="mrp[]"]').val());
        $('.qty').val(editRow.find('input[name="qty[]"]').val());
        $('.free_qty').val(editRow.find('input[name="free_qty[]"]').val());
        $('.rate').val(editRow.find('input[name="rate[]"]').val());
        $('.srate').val(editRow.find('input[name="srate[]"]').val());
        $('.disc').val(editRow.find('input[name="disc[]"]').val());
        $('.tax').val(editRow.find('input[name="tax[]"]').val());
         $('.stax').val(editRow.find('input[name="stax[]"]').val());

        updateEntryRowAmount();

        $('.add-row').html('<i class="fa fa-save"></i>');
    });


    // Delete Row
    $(document).on('click','.delete-row',function(){
        $(this).closest('tr').remove();
        calculateTotal();
    });

    // Recalculate whenever header-level Discount / CD % / Other Charges (or its +/- sign) change
    $(document).on('keyup change', '#discount, #cd_percent, #other_charges, #other_charges_sign', function(){
        calculateTotal();
    });

    // Total Calculation - now writes to the Bill Summary ids that actually
    // exist in the markup (#show_total_qty, #show_taxable, #show_cgst, ...).
    // The old code updated ".purchase-footer h3:eq(0/1)", which doesn't exist
    // anywhere in this page, so the summary card never updated.
    function calculateTotal()
    {
        let totalQty    = 0;
        let totalBase   = 0;   // sum of (qty * rate) before discount
        let totalDisc   = 0;   // sum of per-row discount amounts
        let totalTax    = 0;   // sum of per-row tax amounts

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

        // CD % (cash discount) reduces the taxable value itself, before GST/grand total
        let cdPercent = parseFloat($('#cd_percent').val()) || 0;
        let cdAmount  = taxableAmount * (cdPercent / 100);
        let netTaxable = taxableAmount - cdAmount;

         let cgst = 0;
        let sgst = 0;
        let igst = 0;
        let vat = 0;
        // Intra-state assumption: split total tax evenly into CGST + SGST.
       $('#show_cgst').closest('tr').hide();
        $('#show_sgst').closest('tr').hide();
        $('#show_igst').closest('tr').hide();
        $('#show_vat').closest('tr').hide();

        switch (gst_type) {

            case "cgst":

                 cgst = totalTax / 2;
                 sgst = totalTax / 2;

                $('#show_cgst').closest('tr').show();
                $('#show_sgst').closest('tr').show();
                break;

            case "igst":
                 igst = totalTax;
                $('#show_igst').closest('tr').show();
                break;

            case "vat":
                  vat = totalTax;
                $('#show_vat').closest('tr').show();
                break;
        }

           $('#show_cgst').text(cgst.toFixed(2));
            $('#show_sgst').text(sgst.toFixed(2));
            $('#show_igst').text(igst.toFixed(2));
            $('#show_vat').text(vat.toFixed(2));

        let headerDiscount = parseFloat($('#discount').val()) || 0;

        // Other Charges can add to or subtract from the total depending on the +/- selector
        let otherCharges     = parseFloat($('#other_charges').val()) || 0;
        let otherChargesSign = $('#other_charges_sign').val() === '-' ? -1 : 1;
        let otherChargesSigned = otherCharges * otherChargesSign;

        let grandTotal = netTaxable + totalTax - headerDiscount + otherChargesSigned;

        $('#show_total_qty').text(totalQty);
        $('#show_taxable').text(netTaxable.toFixed(2));
        $('#show_gst').text(totalTax.toFixed(2));
        $('#show_discount').text(headerDiscount.toFixed(2));
        $('#show_cd').text(cdAmount.toFixed(2));
        $('#show_other').text((otherChargesSign < 0 ? '-' : '') + otherCharges.toFixed(2));
        $('#show_grand_total').html('&#8377;' + grandTotal.toFixed(2));

        $('#total_qty').val(totalQty);
        $('#grand_total').val(grandTotal.toFixed(2));
    }

    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: "Select Product",
        allowClear: true
    });


    $('#purchaseForm').on('submit',function(e){

        if($('.purchase-table tbody tr:not(.entry-row)').length == 0)
        {
            alert("Please add at least one product");
            e.preventDefault();
            return false;
        }

    });
});
</script>