<?php 
// Include the header
include 'view/layout/header.php'; 
?>
<link rel="stylesheet" href="<?= BASE_URL ?>config/Addons/purchase.css">
<style>
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type=number] {
        -moz-appearance: textfield;
    }
    .purchase-table { font-size: 13px; }
    .purchase-table th, .purchase-table td {
        padding: 3px 6px; vertical-align: middle;
    }
    .purchase-table .form-control, .purchase-table .form-select {
        height: 28px; padding: 2px 6px; font-size: 13px;
    }
    .purchase-table td .btn {
        height: 20px; min-width: 18px; padding: 1px 2px; font-size: 11px;
    }
</style>

<div id="container">
    <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">
    
    <!-- Dynamically switch action based on whether we are editing or creating -->
    <form action="<?= BASE_URL ?>purchase/<?= isset($ROW['adj_id']) ? 'adjustmentupdate' : 'adjustmentstore' ?>" method="POST" id="adjustmentForm">
        
        <!-- Hidden ID for Edit Mode -->
        <?php if (isset($ROW['adj_id'])): ?>
            <input type="hidden" name="adj_id" value="<?= $ROW['adj_id']; ?>">
        <?php endif; ?>

        <div class="purchase-card">
            <!-- Header Details -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa fa-sliders-h"></i> 
                        <?= isset($ROW['adj_id']) ? 'Edit Stock Adjustment' : 'Stock Adjustment Entry' ?>
                    </h5>
                    <a href="<?= BASE_URL ?>purchase/adjlist" class="btn btn-light btn-sm fw-bold">
                        <i class="fa fa-list"></i> Adjustment List
                    </a>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-3">
                            <label class="form-label fw-bold">Adjustment No</label>
                            <input type="text" class="form-control" name="adj_no" value="<?= $ROW['adj_no'] ?? 'AUTO'; ?>" readonly>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="adj_date" class="form-control" value="<?= $ROW['adj_date'] ?? date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label fw-bold">Super Stockist <span class="text-danger">*</span></label>
                            
                            <!-- Add disabled if in Edit Mode -->
                            <select class="form-select stockist_id" <?= isset($ROW['adj_id']) ? '' : 'name="stockist_id"' ?> id="stockist_id" <?= isset($ROW['adj_id']) ? 'disabled' : 'required' ?>>
                                <option value="">Select Stockist First...</option>
                                <!-- Populated via AJAX -->
                            </select>

                            <!-- Hidden input ensures the stockist_id is still submitted during an update -->
                            <?php if (isset($ROW['adj_id'])): ?>
                                <input type="hidden" name="stockist_id" value="<?= $ROW['stockist_id']; ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Adjustment Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle purchase-table">
                    <thead class="table-primary text-center">
                        <tr>
                            <th style="width:25%">Product</th>
                            <th style="width:15%">Batch No</th>
                            <th style="width:10%">Current Stock</th>
                            <th style="width:15%">Adj. Type</th>
                            <th style="width:10%">Adj. Qty</th>
                            <th style="width:20%">Remarks</th>
                            <th style="width:5%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <!-- Load Existing Items if in Edit Mode -->
                        <?php if (isset($ROW_DETAILS) && mysqli_num_rows($ROW_DETAILS) > 0): ?>
                            <?php while ($item = mysqli_fetch_assoc($ROW_DETAILS)): ?>
                                <tr>
                                    <td>
                                        <?= $item['product_name'] ?>
                                        <input type="hidden" name="product_id[]" value="<?= $item['product_id'] ?>">
                                    </td>
                                    <td>
                                        <?= $item['batch_no'] ?>
                                        <input type="hidden" name="batch_id[]" value="<?= $item['batch_id'] ?>">
                                    </td>
                                    <td class="text-center text-muted">
                                        N/A
                                    </td>
                                    <td class="text-center fw-bold <?= $item['adj_type'] == 'ADD' ? 'text-success' : 'text-danger' ?>">
                                        <?= $item['adj_type'] == 'ADD' ? 'Add Stock (+)' : 'Deduct Stock (-)' ?>
                                        <input type="hidden" name="adj_type[]" value="<?= $item['adj_type'] ?>">
                                    </td>
                                    <td class="text-center fw-bold">
                                        <?= $item['qty'] ?>
                                        <input type="hidden" name="qty[]" value="<?= $item['qty'] ?>">
                                    </td>
                                    <td>
                                        <?= $item['remarks'] ?>
                                        <input type="hidden" name="line_remarks[]" value="<?= $item['remarks'] ?>">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm delete-row"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>

                        <!-- Dynamic Entry Row -->
                        <tr class="entry-row">
                            <td>
                                <select class="form-select form-select-sm product select2" disabled>
                                    <option value="">Select Stockist First</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-sm batch">
                                    <option value="">Select Batch</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm current_stock text-center fw-bold" readonly placeholder="0">
                            </td>
                            <td>
                                <select class="form-select form-select-sm adj_type">
                                    <option value="ADD">Add Stock (+)</option>
                                    <option value="DEDUCT">Deduct Stock (-)</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm qty text-center" min="1" placeholder="Qty">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm line_remarks" placeholder="Reason...">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-success btn-sm add-row"><i class="fa fa-plus"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- General Remarks & Submit -->
            <div class="card shadow-sm mt-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-10">
                            <label>General Remarks</label>
                            <textarea class="form-control" rows="2" name="general_remarks" placeholder="Overall adjustment reason..."><?= $ROW['remarks'] ?? ''; ?></textarea>
                        </div>
                        <div class="col-md-2 d-flex align-items-end justify-content-end">
                            <button type="submit" class="btn <?= isset($ROW['adj_id']) ? 'btn-warning' : 'btn-primary' ?> w-100 fw-bold">
                                <i class="fa <?= isset($ROW['adj_id']) ? 'fa-edit' : 'fa-save' ?>"></i> 
                                <?= isset($ROW['adj_id']) ? 'Update' : 'Submit' ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include 'view/layout/footer.php'; ?>

<script>
$(document).ready(function(){
    // Initialize Select2
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%', allowClear: true });

    let existingStockist = "<?= $ROW['stockist_id'] ?? '' ?>";
    let isInitialLoad = true;

    // Load Stockist and pass the existing ID if in edit mode
    loadSuperStockist(existingStockist);

    // 1. When Stockist is selected -> Fetch Products
    $('#stockist_id').on('change', function() {
        let stockistId = $(this).val();
        
        // Only clear the table if it's NOT the initial page load (prevents wiping edit data)
        if (!isInitialLoad) {
            $('.purchase-table tbody tr:not(.entry-row)').remove(); 
        }
        
        let productSelect = $('.entry-row .product');
        let batchSelect = $('.entry-row .batch');
        
        productSelect.html('<option value="">Loading...</option>').prop('disabled', true);
        batchSelect.html('<option value="">Select Batch</option>');
        $('.entry-row .current_stock, .entry-row .qty').val('');

        if(stockistId) {
            $.ajax({
                url: "<?= BASE_URL ?>purchase/getAdjustmentProducts",
                type: "POST",
                data: { stockist_id: stockistId },
                success: function(response) {
                    productSelect.html(response).prop('disabled', false).trigger('change');
                    isInitialLoad = false; // Mark initial load as complete
                }
            });
        } else {
            isInitialLoad = false;
        }
    });

    // 2. When Product is selected -> Fetch Batches
    $('.product').on('select2:select', function () {
        let productId = $(this).val();
        let stockistId = $('#stockist_id').val();
        let row = $(this).closest('.entry-row');
        let batchSelect = row.find('.batch');

        row.find('.current_stock, .qty, .line_remarks').val('');
        batchSelect.html('<option value="">Loading...</option>');

        if (!productId || !stockistId) return;

        $.ajax({
            url: "<?= BASE_URL ?>purchase/getAdjustmentBatches",
            type: "POST",
            data: { product_id: productId, stockist_id: stockistId },
            success: function(response) {
                batchSelect.html(response).focus();
            }
        });
    });

    // 3. When Batch is selected -> Fetch exact Current Stock
    $(document).on('change', '.batch', function() {
        let batchId = $(this).val();
        let productId = $(this).closest('.entry-row').find('.product').val();
        let stockistId = $('#stockist_id').val();
        let row = $(this).closest('.entry-row');

        if (!batchId) {
            row.find('.current_stock').val('');
            return;
        }

        $.ajax({
            url: "<?= BASE_URL ?>purchase/getCurrentStock",
            type: "POST",
            data: { product_id: productId, batch_id: batchId, stockist_id: stockistId },
            success: function(response) {
                row.find('.current_stock').val(response);
                row.find('.qty').focus();
            }
        });
    });

    // 4. Add Row Logic
    $(document).on('click', '.add-row', function(){
        let row = $('.entry-row');
        let product = row.find('.product').val();
        let productName = row.find('.product option:selected').text();
        let batch = row.find('.batch').val();
        
        // Extract just the batch number (remove the stock text if it's there)
        let batchTextFull = row.find('.batch option:selected').text();
        let batchText = batchTextFull.split(' (')[0]; 
        
        let currentStock = parseFloat(row.find('.current_stock').val()) || 0;
        let adjType = row.find('.adj_type').val();
        let adjTypeText = row.find('.adj_type option:selected').text();
        let qty = parseFloat(row.find('.qty').val()) || 0;
        let remarks = row.find('.line_remarks').val();

        // Validations
        if(!product) { alert("Select a product."); return; }
        if(!batch) { alert("Select a batch."); return; }
        if(qty <= 0) { alert("Enter a valid quantity."); return; }
        if(adjType === 'DEDUCT' && qty > currentStock) {
            alert("Deduction quantity cannot exceed current stock!"); 
            return; 
        }

        // Check duplicates
        let exists = false;
        $('.purchase-table tbody tr').not('.entry-row').each(function () {
            if ($(this).find('input[name="product_id[]"]').val() == product && $(this).find('input[name="batch_id[]"]').val() == batch) {
                exists = true;
                return false;
            }
        });
        if (exists) { alert("This Product and Batch is already added to the list."); return; }

        let html = `
        <tr>
            <td>${productName}<input type="hidden" name="product_id[]" value="${product}"></td>
            <td>${batchText}<input type="hidden" name="batch_id[]" value="${batch}"></td>
            <td class="text-center">${currentStock}</td>
            <td class="text-center fw-bold ${adjType == 'ADD' ? 'text-success' : 'text-danger'}">
                ${adjTypeText}<input type="hidden" name="adj_type[]" value="${adjType}">
            </td>
            <td class="text-center fw-bold">${qty}<input type="hidden" name="qty[]" value="${qty}"></td>
            <td>${remarks}<input type="hidden" name="line_remarks[]" value="${remarks}"></td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm delete-row"><i class="fa fa-trash"></i></button>
            </td>
        </tr>`;

        $(html).insertBefore('.entry-row');

        // Reset Entry Row
        row.find('.product').val('').trigger('change');
        row.find('.batch').html('<option value="">Select Batch</option>');
        row.find('.current_stock, .qty, .line_remarks').val('');
        row.find('.adj_type').val('ADD');
        row.find('.product').select2('open');
    });

    // 5. Delete Row Logic
    $(document).on('click', '.delete-row', function(){
        $(this).closest('tr').remove();
    });

    // Load Stockist Function
    function loadSuperStockist(selected = '') {
        $.ajax({
            url: "<?= BASE_URL ?>purchase/getSuperStockist",
            type: "POST",
            data: { selected_stockist: selected }, // Pass the selected stockist for Edit mode
            success: function(response) {
                $('#stockist_id').html(response);
                
                // If editing, trigger the change event automatically to load products
                if (selected !== '') {
                    $('#stockist_id').trigger('change');
                }
            }
        });
    }
    
    // Prevent submitting empty adjustments
    $('#adjustmentForm').on('submit', function(e){
        if($('.purchase-table tbody tr').not('.entry-row').length === 0) {
            alert("Please add at least one adjustment entry.");
            e.preventDefault();
        }
    });
});
</script>