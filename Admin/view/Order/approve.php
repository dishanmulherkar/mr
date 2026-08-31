<?php 
include 'view/layout/header.php'; 
?>
<link rel="stylesheet" href="<?= BASE_URL ?>config/Addons/purchase.css">
<style>
    .detail { display: flex; justify-content: flex-end; padding: 6px; }
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    input[type=number] { -moz-appearance: textfield; }
    
    .order-table { font-size: 13px; }
    .order-table th, .order-table td { padding: 8px; vertical-align: middle; }
    .order-table .form-control, .order-table .form-select { height: 32px; padding: 4px 8px; font-size: 13px; }
    
    /* Batch dropdown enhancement */
    .batch-select { font-weight: 600; color: #0d47a1; }
    .batch-info { font-size: 12px; color: #666; margin-top: 2px; }
    
    /* Batch availability badge */
    .batch-badge { display: inline-block; padding: 2px 6px; background: #e8f5e9; color: #2e7d32; border-radius: 4px; font-size: 11px; font-weight: 600; }
    .batch-badge.low-stock { background: #fff3e0; color: #e65100; }
    .batch-badge.expired { background: #ffebee; color: #c62828; }
    
    .product-name { font-weight: 600; color: #1a237e; }
    
    .summary-card { background: linear-gradient(135deg, #8f9cd5 0%, #b9aac8 100%); color: white; border-radius: 8px; padding: 20px; }
    .summary-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.2); }
    .summary-row:last-child { border-bottom: none; }
    .summary-label { font-size: 13px; opacity: 0.9; }
    .summary-value { font-weight: 700; font-size: 14px; }
    
    .approval-header { background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%); }
    
    .batch-change-alert { background: #fff8e1; border-left: 4px solid #fbc02d; padding: 12px; margin-bottom: 16px; border-radius: 4px; font-size: 13px; }
    
    /* Batch comparison (old vs new)  */
    .batch-change-indicator { background: #c8e6c9; color: #2e7d32; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
    
    /* Action buttons */
    .btn-approve { background: linear-gradient(135deg, #66bb6a 0%, #43a047 100%); border: none; color: white; font-weight: 600; padding: 12px 32px; }
    .btn-approve:hover { background: linear-gradient(135deg, #43a047 0%, #2e7d32 100%); color: white; }
    
    /* Table row hover effect  */
    .order-table tbody tr { transition: background-color 0.2s; }
    .order-table tbody tr:hover { background-color: #f5f5f5; }
    
    /* Approved quantity highlight  */
    .approved_qty { border: 2px solid #4caf50; }
    .approved_qty:focus { box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25); }
</style>

<div id="container" class="container-fluid mt-3">
    <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i> Order approved successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle"></i> <?= $_GET['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

   <form action="<?= BASE_URL ?>Order/Approved" method="POST" id="orderApprovalForm">
        <input type="hidden" name="order_id" value="<?= $ROW['order_id']; ?>">
        <input type="hidden" name="stockist_id" value="<?= $ROW['stockist_id']; ?>">
        <input type="hidden" name="super_stockist_id" id="super_stockist_id" value="<?= $ROW['super_stockist_id'] ?? ''; ?>">

        <div class="purchase-card">
            <!-- Order Header Details -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header approval-header text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa fa-check-square"></i> Review & Approve Order #ORD-<?= $ROW['order_id']; ?>
                    </h5>
                    <a href="<?= BASE_URL ?>Order/list" class="btn btn-light btn-sm fw-bold">
                        <i class="fa fa-list"></i> Order List
                    </a>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Order ID</label>
                            <input type="text" class="form-control" value="#ORD-<?= $ROW['order_id']; ?>" readonly>
                        </div>

                       <div class="col-lg-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Order Date</label>
                            <input type="date" class="form-control" value="<?= date('Y-m-d', strtotime($ROW['order_date'])); ?>" disabled>
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">MR Name</label>
                            <input type="text" class="form-control" value="<?= $ROW['mr_name'] ?? 'N/A'; ?>" disabled>
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Stockist</label>
                            <input type="text" class="form-control fw-bold text-primary" value="<?= $ROW['ss_name'] ?? 'N/A'; ?>" disabled>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Batch Change Alert -->
            <div class="batch-change-alert">
                <i class="fa fa-info-circle"></i> <strong>Batch Selection:</strong> You can change batches below if inventory has changed. All batches shown are from the MR's Super Stockist stock. New batch selections will be reflected in calculations.
            </div>

            <!-- Order Items Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle order-table">
                    <thead class="table-primary text-center">
                        <tr>
                            <th style="width:15%">Product</th>
                            <th style="width:16%">Batch <span class="text-danger">*</span></th>
                            <th style="width:8%">Expiry</th>
                            <th style="width:8%">MRP</th>
                            <th style="width:8%">Sale Rate</th>
                            <th style="width:6%">Tax %</th>
                            <th style="width:8%">Ordered Qty</th>
                            <th style="width:11%" class="bg-success bg-opacity-10 text-dark">Approved Qty <span class="text-danger">*</span></th>
                            <th style="width:7%">Disc %</th>
                            <th style="width:10%">Amount</th>
                            <th style="width:5%">Action</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php if (!empty($ROW_DETAILS)): ?>
                        <?php foreach ($ROW_DETAILS as $index => $item): ?>
                            <tr data-product-id="<?= $item['product_id']; ?>" data-original-batch="<?= $item['batch_id']; ?>">
                                <td>
                                    <strong class="product-name"><?= $item['product_name']; ?></strong>
                                    <input type="hidden" name="product_id[]" value="<?= $item['product_id']; ?>">
                                    <input type="hidden" name="detail_id[]" value="<?= $item['detail_id']; ?>">
                                </td>
                                <td>
                                    <select name="batch_id[]" class="form-select form-select-sm batch-select" required data-product-id="<?= $item['product_id']; ?>">
                                        <option value="">Loading batches...</option>
                                    </select>
                                    <div class="batch-info" style="margin-top: 4px;">
                                        <span class="batch-badge" id="badge-<?= $index; ?>">
                                            Batch: <?= $item['batch_no']; ?>
                                        </span>
                                    </div>
                                    <input type="hidden" name="batch[]" class="batch-no-input" value="<?= $item['batch_no']; ?>">
                                </td>
                                <td class="text-center">
                                    <input type="text" name="expiry[]" class="form-control form-control-sm expiry" value="<?= $item['expiry_formatted'] ?? ''; ?>" placeholder="MM/YYYY" readonly style="background: #f5f5f5;">
                                </td>
                                <td class="text-end">
                                    <input type="number" step="0.01" name="mrp[]" class="form-control form-control-sm mrp text-end" value="<?= number_format($item['mrp'] ?? 0, 2, '.', ''); ?>" readonly style="background: #f5f5f5;">
                                </td>
                                <td class="text-end">
                                    <input type="number" step="0.01" name="rate[]" class="form-control form-control-sm rate text-end" value="<?= number_format($item['rate'], 2, '.', ''); ?>" readonly style="background: #f5f5f5;">
                                </td>
                                <td class="text-center">
                                    <input type="number" step="0.01" name="tax[]" class="form-control form-control-sm tax text-center" value="<?= number_format($item['gst'], 2, '.', ''); ?>" readonly style="background: #f5f5f5;">
                                </td>
                                <td class="text-center fw-bold text-primary">
                                    <span class="original-qty"><?= $item['qty']; ?></span>
                                </td>
                                <td>
                                    <input type="number" name="approved_qty[]" class="form-control form-control-sm approved_qty text-center fw-bold" value="<?= $item['approved_qty'] !== null ? $item['approved_qty'] : $item['qty']; ?>" min="0" step="1" required>
                                </td>
                                <td class="text-center">
                                    <input type="number" step="0.01" name="disc[]" class="form-control form-control-sm disc text-center" value="<?= number_format($item['discount'], 2, '.', ''); ?>" min="0" max="100">
                                </td>
                                <td class="amount text-end fw-bold">
                                    ₹<?= number_format($item['net_total'], 2); ?>
                                    <input type="hidden" name="amount[]" value="<?= number_format($item['net_total'], 2, '.', ''); ?>">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm remove-row" title="Remove item">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Add Product Entry Row -->
                    <tr class="entry-row" style="background:#fafafa;">
                        <td>
                            <select class="form-select form-select-sm product select2" id="new-product-select" style="width:100%;">
                                <option value="">Select Product</option>
                                <?php foreach ($Products as $product): ?>
                                    <option value="<?= $product['p_id']; ?>"><?= $product['product_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm batch" id="new-batch-select" disabled>
                                <option value="">Select product first</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <input type="text" class="form-control form-control-sm expiry" id="new-expiry" readonly style="background:#f0f0f0;">
                        </td>
                        <td class="text-end">
                            <input type="number" step="0.01" class="form-control form-control-sm mrp text-end" id="new-mrp" readonly style="background:#f0f0f0;">
                        </td>
                        <td class="text-end">
                            <input type="number" step="0.01" class="form-control form-control-sm rate text-end" id="new-rate" readonly style="background:#f0f0f0;">
                        </td>
                        <td class="text-center">
                            <input type="number" step="0.01" class="form-control form-control-sm tax text-center" id="new-tax" readonly style="background:#f0f0f0;">
                        </td>
                        <td class="text-center text-muted">—</td>
                        <td>
                            <input type="number" class="form-control form-control-sm approved_qty text-center fw-bold" id="new-qty" value="" placeholder="Qty" min="1" step="1">
                        </td>
                        <td class="text-center">
                            <input type="number" step="0.01" class="form-control form-control-sm disc text-center" id="new-disc" value="16.66" min="0" max="100">
                        </td>
                        <td class="text-end">
                            <input type="text" class="form-control form-control-sm amount text-end" id="new-amount" value="0.00" readonly>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-success btn-sm" id="btn-add-new-row" title="Add this product to order">
                                <i class="fa fa-plus"></i>
                            </button>
                        </td>
                    </tr>

                    </tbody>
                </table>
            </div>

            <!-- Transport & Financial Information Section -->
            <div class="row mt-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light border-bottom">
                            <strong><i class="fa fa-truck"></i> Dispatch & Financial Details</strong>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">LR No.</label>
                                    <input type="text" name="lr_no" class="form-control" value="<?= $ROW['lr_no'] ?? ''; ?>" placeholder="LR Number">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">CD %</label>
                                    <input type="number" step="0.01" id="cd_percent" name="cd_percent" class="form-control" value="<?= $ROW['cd_percent'] ?? ''; ?>" placeholder="0.00" min="0" max="100">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">E-Way Bill</label>
                                    <input type="text" name="eway_bill" class="form-control" value="<?= $ROW['eway_bill'] ?? ''; ?>" placeholder="E-Way Bill No.">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Vehicle No.</label>
                                    <input type="text" name="vehicle_no" class="form-control" value="<?= $ROW['vehicle_no'] ?? ''; ?>" placeholder="Vehicle Number">
                                </div>
                              <div class="col-md-3">
                                        <label class="form-label fw-bold small">Transport Name</label>

                                        <input
                                            type="text"
                                            name="transport_name"
                                            id="transport_name"
                                            class="form-control"
                                            value="<?= htmlspecialchars(
                                                $ROW['transport_name']
                                                ?? $ROW['stockist_transport']
                                                ?? 'Hand Delivery'
                                            ); ?>"
                                            placeholder="Transport Company"
                                            list="transportSuggestions"
                                        >

                                        <datalist id="transportSuggestions">

                                            <?php if (!empty($ROW['stockist_transport'])): ?>

                                                <option value="<?= htmlspecialchars($ROW['stockist_transport']); ?>">

                                            <?php endif; ?>

                                            <option value="Hand Delivery">

                                        </datalist>
                                    </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Credit Days</label>
                                    <input type="number" name="credit_days" class="form-control" value="<?= $ROW['credit_days'] ?? ''; ?>" placeholder="0" min="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Header Discount (₹)</label>
                                    <input type="number" step="0.01" id="discount" name="header_discount" class="form-control" value="<?= $ROW['header_discount'] ?? ''; ?>" placeholder="0.00" min="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Other Charges</label>
                                    <div class="input-group">
                                        <?php 
                                            $raw_charges = $ROW['other_charges'] ?? 0;
                                            $saved_sign = ($raw_charges < 0) ? '-' : '+';
                                            $saved_amt = ($raw_charges != 0) ? abs($raw_charges) : '';
                                        ?>
                                        <select id="other_charges_sign" name="other_charges_sign" class="form-select form-select-sm" style="max-width: 60px;">
                                            <option value="+" <?= $saved_sign === '+' ? 'selected' : '' ?>>+</option>
                                            <option value="-" <?= $saved_sign === '-' ? 'selected' : '' ?>>-</option>
                                        </select>
                                        <input type="number" step="0.01" id="other_charges" name="other_charges" class="form-control form-control-sm" value="<?= $saved_amt; ?>" placeholder="0.00" min="0">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small">Remarks</label>
                                    <textarea class="form-control" rows="3" name="remarks" placeholder="Any special notes or instructions..."><?= $ROW['remarks'] ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side Summary Card -->
                <div class="col-lg-4">
                    <div class="summary-card">
                        <h6 class="mb-3" style="font-size: 15px; font-weight: 700; border-bottom: 2px solid rgba(255,255,255,0.3); padding-bottom: 10px;">
                            <i class="fa fa-calculator"></i> Approval Summary
                        </h6>
                        <div class="summary-row">
                            <span class="summary-label">Total Approved Qty</span>
                            <span class="summary-value" id="show_total_qty">0</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Taxable Amount</span>
                            <span class="summary-value" id="show_taxable">₹0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">CD Deduction</span>
                            <span class="summary-value" id="show_cd">₹0.00</span>
                        </div>
                        <?php if ($gst === 'CGST_SGST') { ?>
                        <div class="summary-row" id="row_cgst">
                            <span class="summary-label">CGST (Half Tax)</span>
                            <span class="summary-value" id="show_cgst">₹0.00</span>
                        </div>
                        <div class="summary-row" id="row_sgst">
                            <span class="summary-label">SGST (Half Tax)</span>
                            <span class="summary-value" id="show_sgst">₹0.00</span>
                        </div>
                        <?php } elseif ($gst == 'IGST') { ?>
                        <div class="summary-row" id="row_igst" style="display:none;">
                            <span class="summary-label">IGST</span>
                            <span class="summary-value" id="show_igst">₹0.00</span>
                        </div>
                        <?php } elseif ($gst === 'VAT') { ?>
                        <div class="summary-row" id="row_vat" style="display:none;">
                            <span class="summary-label">VAT</span>
                            <span class="summary-value" id="show_vat">₹0.00</span>
                        </div>
                        <?php } ?>
                        <div class="summary-row">
                            <span class="summary-label">Total Tax</span>
                            <span class="summary-value" id="show_gst">₹0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Header Discount</span>
                            <span class="summary-value" id="show_discount">₹0.00</span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Other Charges</span>
                            <span class="summary-value" id="show_other">₹0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Round Off</span>
                            <span class="summary-value" id="show_round_off">₹0.00</span>
                        </div>
                        <div class="summary-row" style="font-size: 16px; font-weight: 800; border-top: 2px solid rgba(255,255,255,0.5); padding-top: 12px; margin-top: 12px;">
                            <span class="summary-label">Grand Total</span>
                            <span class="summary-value" id="show_grand_total">₹0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden Fields for Database Sync -->
            <input type="hidden" name="total_qty" id="total_qty" value="0">
            <input type="hidden" name="grand_total" id="grand_total" value="0">
            <input type="hidden" name="round_off" id="round_off" value="0">
            <input type="hidden" name="gsttype" id="gst_type_input" value="<?= $gst ?? ''; ?>">
            <input type="hidden" name="gst_amt" id="input_gst_amt" value="0">
            <input type="hidden" name="cgst" id="input_cgst" value="0">
            <input type="hidden" name="sgst" id="input_sgst" value="0">
            <input type="hidden" name="igst" id="input_igst" value="0">
            <input type="hidden" name="vat" id="input_vat" value="0">
        </div>

        <!-- Approval Button -->
        
            <!-- Dynamic Action Buttons -->
        <div class="mt-4 text-end">
            <a href="<?= BASE_URL ?>Order/list" class="btn btn-secondary me-2">
                <i class="fa fa-times"></i> Cancel
            </a>

            <?php if ($ROW['status'] === 'Approved'): ?>
                <!-- If already approved, show Update & Dispatch -->
               <button type="button" class="btn btn-info text-white me-2" id="btn-dispatch" data-url="<?= BASE_URL ?>Order/Dispatch/<?= $ROW['order_id']; ?>">
                    <i class="fa fa-truck"></i> Dispatch Order
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Update Order
                </button>
            <?php elseif($ROW['status'] === 'Pending'): ?>
                <!-- If pending, show Approve -->
                
                <button type="submit" class="btn btn-approve">
                    <i class="fa fa-check-circle"></i> Approve & Process Order
                </button>


                 <button type="button" class="btn btn-danger me-2" 
                        onclick="if(confirm('Are you sure you want to completely reject this order?')) { window.location.href='<?= BASE_URL ?>Order/Reject/<?= $ROW['order_id']; ?>'; }">
                    <i class="fa fa-times-circle"></i> Reject Order
                </button>
            <?php endif; ?>
        
        </div>
    </form>
</div>

<?php include 'view/layout/footer.php'; ?>

<script>
$(document).ready(function(){

    /* ============================================================
    ADD PRODUCT — entry row (Product → Batch → auto-fill → Amount)
    ============================================================ */
    $('#new-product-select').select2({ width: '100%' });

    const $newBatch  = $('#new-batch-select');
    const $newExpiry = $('#new-expiry');
    const $newMrp    = $('#new-mrp');
    const $newRate   = $('#new-rate');
    const $newTax    = $('#new-tax');
    const $newQty    = $('#new-qty');
    const $newDisc   = $('#new-disc');
    const $newAmount = $('#new-amount');

    // Product picked -> fetch batches from Super Stockist stock
    $('#new-product-select').on('change', function () {
        const productId = $(this).val();

        $newBatch.prop('disabled', true).html('<option value="">Loading…</option>');
        $newExpiry.val(''); $newMrp.val(''); $newRate.val(''); $newTax.val('');
        $newAmount.val('0.00');

        if (!productId) {
            $newBatch.html('<option value="">Select product first</option>');
            return;
        }

        $.ajax({
            url: "<?= BASE_URL ?>Order/getBatchesByProduct",
            type: "POST",
            data: { super_stockist_id: superStockistId, product_id: productId },
            dataType: "json",
            success: function (batches) {
                if (!batches || !batches.length) {
                    $newBatch.html('<option value="">No stock available</option>');
                    return;
                }
                let options = '<option value="">Select Batch</option>';
                batches.forEach(b => {
                    options += `<option value="${b.batch_id}"
                                        data-rate="${b.sale_rate}"
                                        data-tax="${b.sale_tax}"
                                        data-mrp="${b.mrp ?? '0.00'}"
                                        data-expiry="${b.expiry_formatted}"
                                        data-batch-no="${b.batch_no}"
                                        data-stock="${b.current_qty}">
                                    ${b.batch_no} (Stock: ${b.current_qty} | Exp: ${b.expiry_formatted})
                                </option>`;
                });
                $newBatch.prop('disabled', false).html(options);
            },
            error: function () {
                $newBatch.html('<option value="">Error loading batches</option>');
            }
        });
    });

    // Batch picked -> auto-fill MRP / Sale Rate / Tax% / Expiry
    $newBatch.on('change', function () {
        const opt = $(this).find(':selected');
        $newExpiry.val(opt.data('expiry') || '');
        $newMrp.val(opt.data('mrp')  ? parseFloat(opt.data('mrp')).toFixed(2)  : '0.00');
        $newRate.val(opt.data('rate') ? parseFloat(opt.data('rate')).toFixed(2) : '0.00');
        $newTax.val(opt.data('tax')  ? parseFloat(opt.data('tax')).toFixed(2)  : '0.00');
        recalcNewRowAmount();
    });

    // Qty / Disc change -> recalc amount live
    $newQty.add($newDisc).on('keyup change', recalcNewRowAmount);

    function recalcNewRowAmount() {
        const qty  = parseFloat($newQty.val())  || 0;
        const rate = parseFloat($newRate.val()) || 0;
        const disc = parseFloat($newDisc.val()) || 0;
        const tax  = parseFloat($newTax.val())  || 0;

        const base    = qty * rate;
        const discAmt = base * (disc / 100);
        const taxable = base - discAmt;
        const taxAmt  = taxable * (tax / 100);
        const amount  = taxable + taxAmt;

        $newAmount.val(amount.toFixed(2));
    }

    // Commit entry row as a real order line
    $('#btn-add-new-row').on('click', function () {
        const productId   = $('#new-product-select').val();
        const productName = $('#new-product-select').find(':selected').text();
        const batchOpt     = $newBatch.find(':selected');
        const batchId      = $newBatch.val();

        if (!productId) { alert('Select a product first.'); return; }
        if (!batchId)   { alert('Select a batch first.'); return; }

        const qty  = parseFloat($newQty.val())  || 0;
        const rate = parseFloat($newRate.val()) || 0;
        const tax  = parseFloat($newTax.val())  || 0;
        const mrp  = parseFloat($newMrp.val())  || 0;
        const disc = parseFloat($newDisc.val()) || 0;
        if (qty <= 0) { alert('Enter a valid quantity.'); return; }

        const stock = parseFloat(batchOpt.data('stock')) || 0;
        
        // GLOBAL BATCH STOCK CHECK FOR NEW ROWS
        let consumedElsewhere = 0;
        $('.order-table tbody tr:not(.entry-row)').each(function() {
            let thisBatchId = $(this).find('.batch-select').val();
            if (thisBatchId == batchId) {
                consumedElsewhere += parseFloat($(this).find('.approved_qty').val()) || 0;
            }
        });

        const available = stock - consumedElsewhere;
        if (qty > available) {
            alert(`Not enough stock available!\nTotal Batch Stock: ${stock}\nReserved in other rows: ${consumedElsewhere}\nAvailable to add: ${available}`);
            $newQty.val(available > 0 ? available : '');
            recalcNewRowAmount();
            return;
        }

        const base    = qty * rate;
        const discAmt = base * (disc / 100);
        const taxable = base - discAmt;
        const taxAmt  = taxable * (tax / 100);
        const amount  = taxable + taxAmt;

        const rowHtml = `
            <tr data-product-id="${productId}" data-original-batch="0" data-new-row="1">
                <td>
                    <strong class="product-name">${productName}</strong>
                    <span class="badge bg-info text-dark ms-1" style="font-size:10px;">NEW</span>
                    <input type="hidden" name="product_id[]" value="${productId}">
                    <input type="hidden" name="detail_id[]" value=""> 
                </td>
                <td>
                    <select name="batch_id[]" class="form-select form-select-sm batch-select" required data-product-id="${productId}">
                        <option value="${batchId}" selected data-stock="${stock}" data-batch-no="${batchOpt.data('batch-no')}" data-rate="${rate}" data-tax="${tax}" data-mrp="${mrp}" data-expiry="${batchOpt.data('expiry')}">${batchOpt.data('batch-no')} (Stock: ${stock})</option>
                    </select>
                    <div class="batch-info" style="margin-top:4px;">
                        <span class="batch-badge">Batch: ${batchOpt.data('batch-no')}</span>
                    </div>
                    <input type="hidden" name="batch[]" class="batch-no-input" value="${batchOpt.data('batch-no')}">
                </td>
                <td class="text-center">
                    <input type="text" name="expiry[]" class="form-control form-control-sm expiry" value="${batchOpt.data('expiry')}" readonly style="background:#f5f5f5;">
                </td>
                <td class="text-end">
                    <input type="number" step="0.01" name="mrp[]" class="form-control form-control-sm mrp text-end" value="${mrp.toFixed(2)}" readonly style="background:#f5f5f5;">
                </td>
                <td class="text-end">
                    <input type="number" step="0.01" name="rate[]" class="form-control form-control-sm rate text-end" value="${rate.toFixed(2)}" readonly style="background:#f5f5f5;">
                </td>
                <td class="text-center">
                    <input type="number" step="0.01" name="tax[]" class="form-control form-control-sm tax text-center" value="${tax.toFixed(2)}" readonly style="background:#f5f5f5;">
                </td>
                <td class="text-center fw-bold text-primary">
                    <span class="original-qty">—</span>
                </td>
                <td>
                    <input type="number" name="approved_qty[]" class="form-control form-control-sm approved_qty text-center fw-bold" value="${qty}" min="0" step="1" required>
                </td>
                <td class="text-center">
                    <input type="number" step="0.01" name="disc[]" class="form-control form-control-sm disc text-center" value="${disc.toFixed(2)}" min="0" max="100">
                </td>
                <td class="amount text-end fw-bold">
                    ₹${amount.toFixed(2)}
                    <input type="hidden" name="amount[]" value="${amount.toFixed(2)}">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm remove-row" title="Remove item">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>`;

        $('.order-table tbody .entry-row').before(rowHtml); 

        // FIXED: Reset quantity to blank so it doesn't automatically insert '1' on next use
        $('#new-product-select').val(null).trigger('change');
        $newBatch.prop('disabled', true).html('<option value="">Select product first</option>');
        $newExpiry.val(''); $newMrp.val(''); $newRate.val(''); $newTax.val('');
        $newQty.val(''); $newDisc.val(16.66); $newAmount.val('0.00'); 

        calculateTotals();
    });

        let superStockistId = $('#super_stockist_id').val();
        let gst_type = $('#gst_type_input').val();

    // Load available batches for each existing item row
    $('.order-table tbody tr:not(.entry-row)').each(function(rowIndex){
        let row = $(this);
        let productId = row.data('product-id');
        let selectedBatchId = row.data('original-batch');
        loadBatchesForProduct(row, productId, selectedBatchId, rowIndex);
    });

    function loadBatchesForProduct(row, productId, selectedBatchId, rowIndex) {
        // Grab the order ID from the hidden form input
        let currentOrderId = $('input[name="order_id"]').val() || 0;

        $.ajax({
            url: "<?= BASE_URL ?>Order/getBatchesByProduct",
            type: "POST",
            data: { 
                super_stockist_id: superStockistId, 
                product_id: productId,
                current_order_id: currentOrderId // Send this to the backend
            },
            dataType: "json",
            success: function(batches) {
                let options = '';
                if(batches && batches.length > 0) {
                    batches.forEach((b) => {
                        let isSelected = (b.batch_id == selectedBatchId) ? 'selected' : '';
                        let stockStatus = '';
                        if(b.current_qty <= 10) {
                            stockStatus = '<span class="batch-badge low-stock">Low</span> ';
                        }
                        options += `<option value="${b.batch_id}" 
                                            data-rate="${b.sale_rate}" 
                                            data-tax="${b.sale_tax}" 
                                            data-mrp="${b.mrp ?? '0.00'}" 
                                            data-expiry="${b.expiry_formatted}"
                                            data-batch-no="${b.batch_no}"
                                            data-stock="${b.current_qty}"
                                            ${isSelected}>
                                        ${b.batch_no} (Stock: ${b.current_qty} units | Exp: ${b.expiry_formatted})
                                    </option>`;
                    });
                } else {
                    options = `<option value="">No batches found for this product</option>`;
                }
                
                row.find('.batch-select').html(options);
                
                let selectedOption = row.find('.batch-select').find(':selected');
                if(selectedOption.val()) {
                    updateBatchInfo(row, selectedOption);
                }
            },
            error: function(xhr) {
                console.error('Error loading batches:', xhr);
                row.find('.batch-select').html('<option value="">Error loading batches</option>');
            }
        });
    }

    $(document).on('change', '.batch-select', function(){
        let selectedOption = $(this).find(':selected');
        let row = $(this).closest('tr');
        updateBatchInfo(row, selectedOption);
    });

    function updateBatchInfo(row, selectedOption) {
        let rate = selectedOption.data('rate') || 0;
        let tax = selectedOption.data('tax') || 0;
        let mrp = selectedOption.data('mrp') || 0;
        let expiry = selectedOption.data('expiry') || '';
        let batchNo = selectedOption.data('batch-no') || '';

        row.find('.rate').val(parseFloat(rate).toFixed(2));
        row.find('.tax').val(parseFloat(tax).toFixed(2));
        row.find('.mrp').val(parseFloat(mrp).toFixed(2));
        row.find('.expiry').val(expiry);
        row.find('.batch-no-input').val(batchNo);

        let originalBatch = row.data('original-batch');
        let currentBatchId = row.find('.batch-select').val();
        
        // Skip badge logic if this is a newly inserted row
        if(row.data('new-row') !== 1) {
            if(originalBatch != currentBatchId) {
                row.find('.batch-badge').addClass('batch-change-indicator').text('✓ Changed: ' + batchNo);
            } else {
                row.find('.batch-badge').removeClass('batch-change-indicator').text('Batch: ' + batchNo);
            }
        }

        // Trigger input validation dynamically to ensure this new batch isn't already over-allocated
        row.find('.approved_qty').trigger('input'); 
    }

    /* ============================================================
       GLOBAL BATCH QUANTITY VALIDATION (Cross-row tracking)
       ============================================================ */
    $(document).on('input', '.order-table tbody tr:not(.entry-row) .approved_qty', function(){
        let row = $(this).closest('tr');
        let batchSelect = row.find('.batch-select');
        let batchId = batchSelect.val();
        
        if (batchId) {
            let batchOpt = batchSelect.find('option:selected');
            let maxStock = parseFloat(batchOpt.data('stock'));
            
            if(!isNaN(maxStock)) {
                let consumedElsewhere = 0;
                // Calculate stock used by THIS batch in OTHER rows
                $('.order-table tbody tr:not(.entry-row)').each(function() {
                    if (this === row[0]) return; // Skip checking against itself
                    let thisBatchId = $(this).find('.batch-select').val();
                    if (thisBatchId == batchId) {
                        consumedElsewhere += parseFloat($(this).find('.approved_qty').val()) || 0;
                    }
                });

                let currentVal = parseFloat($(this).val()) || 0;
                let availableForThisRow = maxStock - consumedElsewhere;

                if (currentVal > availableForThisRow) {
                    alert(`Not enough stock in this batch!\nTotal Stock: ${maxStock}\nUsed in other rows: ${consumedElsewhere}\nAvailable for this row: ${availableForThisRow}`);
                    $(this).val(availableForThisRow > 0 ? availableForThisRow : 0);
                } else if (currentVal < 0) {
                    $(this).val(0);
                }
            }
        }
        
        updateRowAmount(row);
        calculateTotals();
    });

    $(document).on('keyup change', '.disc, #cd_percent, #discount, #other_charges, #other_charges_sign', function(){
        if($(this).closest('tr').length && !$(this).closest('tr').hasClass('entry-row')) {
            updateRowAmount($(this).closest('tr'));
        }
        calculateTotals();
    });

    $(document).on('click', '.remove-row', function(){
        if(confirm('Remove this item from the order?')) {
            let removedBatchId = $(this).closest('tr').find('.batch-select').val();
            $(this).closest('tr').remove();
            
            // Re-validate remaining rows that might share the same batch to free up stock
            if(removedBatchId) {
                 $('.order-table tbody tr:not(.entry-row)').each(function() {
                     if($(this).find('.batch-select').val() == removedBatchId) {
                         $(this).find('.approved_qty').trigger('input');
                     }
                 });
            }
            calculateTotals();
        }
    });

    function updateRowAmount(row) {
        let qty  = parseFloat(row.find('.approved_qty').val()) || 0;
        let rate = parseFloat(row.find('.rate').val()) || 0;
        let disc = parseFloat(row.find('.disc').val()) || 0;
        let tax  = parseFloat(row.find('.tax').val()) || 0;

        let base     = qty * rate;
        let discAmt  = base * (disc / 100);
        let taxable  = base - discAmt;
        let taxAmt   = taxable * (tax / 100);
        let amount   = taxable + taxAmt;

        row.find('.amount').text('₹' + amount.toFixed(2));
        row.find('input[name="amount[]"]').val(amount.toFixed(2));
    }

    function calculateTotals() {
        let totalQty = 0;
        let totalBase = 0;
        let totalDisc = 0;
        let totalTax = 0;

        $('.order-table tbody tr:not(.entry-row)').each(function(){
            let qty  = parseFloat($(this).find('.approved_qty').val()) || 0;
            let rate = parseFloat($(this).find('.rate').val()) || 0;
            let disc = parseFloat($(this).find('.disc').val()) || 0;
            let tax  = parseFloat($(this).find('.tax').val()) || 0;

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

        $('#row_cgst').hide();
        $('#row_sgst').hide();
        $('#row_igst').hide();
        $('#row_vat').hide();

        switch (gst_type) {
            case "CGST_SGST":
                cgst = netTax / 2;
                sgst = netTax / 2;
                $('#row_cgst').show();
                $('#row_sgst').show();
                break;
            case "IGST":
                igst = netTax;
                $('#row_igst').show();
                break;
            case "VAT":
                vat = netTax;
                $('#row_vat').show();
                break;
        }

        $('#show_cgst').text('₹' + cgst.toFixed(2));
        $('#show_sgst').text('₹' + sgst.toFixed(2));
        $('#show_igst').text('₹' + igst.toFixed(2));
        $('#show_vat').text('₹' + vat.toFixed(2));

        let headerDiscount = parseFloat($('#discount').val()) || 0;
        let otherCharges     = parseFloat($('#other_charges').val()) || 0;
        let otherChargesSign = $('#other_charges_sign').val() === '-' ? -1 : 1;
        let otherChargesSigned = otherCharges * otherChargesSign;

        // 1. Calculate Exact Total
        let exactTotal = netTaxable + netTax - headerDiscount + otherChargesSigned;
        
        // 2. Round off the Grand Total
        let grandTotal = Math.round(exactTotal);
        
        // 3. Calculate the difference for transparency
        let roundOff = grandTotal - exactTotal;

        $('#show_total_qty').text(totalQty);
        $('#show_taxable').text('₹' + netTaxable.toFixed(2));
        $('#show_gst').text('₹' + netTax.toFixed(2));
        $('#show_discount').text('₹' + headerDiscount.toFixed(2));
        $('#show_cd').text('₹' + cdAmount.toFixed(2));
        $('#show_other').text('₹' + (otherChargesSign < 0 ? '-' : '') + otherCharges.toFixed(2));
        
        // Display round off and rounded grand total
        $('#show_round_off').text((roundOff < 0 ? '- ₹' : '+ ₹') + Math.abs(roundOff).toFixed(2));
        $('#show_grand_total').text('₹' + grandTotal.toFixed(2));

        $('#total_qty').val(totalQty);
        
        // Update hidden inputs for DB Insertion
        $('#grand_total').val(grandTotal.toFixed(2)); 
        $('#round_off').val(roundOff.toFixed(2));
        
        $('#gst_type_input').val(gst_type);
        
        $('#input_gst_amt').val(netTax.toFixed(2));
        $('#input_cgst').val(cgst.toFixed(2));
        $('#input_sgst').val(sgst.toFixed(2));
        $('#input_igst').val(igst.toFixed(2));
        $('#input_vat').val(vat.toFixed(2));
    }

    calculateTotals();
    /* ============================================================
       UNSAVED CHANGES TRACKER (Protects Dispatch Button)
       ============================================================ */
    let hasUnsavedChanges = false;

   
   // 1. Detect if any input, select, or textarea is changed manually by a REAL USER
    $('#orderApprovalForm').on('change input', 'input, select, textarea', function(e) {
        // e.originalEvent is ONLY present if a real human pressed a key or clicked a mouse.
        // It ignores programmatic triggers like .trigger('input') that happen on page load.
        if (e.originalEvent !== undefined && !$(this).hasClass('select2-search__field')) { 
            hasUnsavedChanges = true;
        }
    });

    // 2. Detect if a new row is added (Add this inside your existing $('#btn-add-new-row').on('click') function)
    $('#btn-add-new-row').on('click', function () {
        // ... your existing code ...
        $('.order-table tbody .entry-row').before(rowHtml); 
        
        hasUnsavedChanges = true; // <--- ADD THIS LINE
        
        // ... rest of your existing code ...
    });

    // 3. Detect if a row is removed (Add this inside your existing remove-row click function)
    $(document).on('click', '.remove-row', function(){
        if(confirm('Remove this item from the order?')) {
            let removedBatchId = $(this).closest('tr').find('.batch-select').val();
            $(this).closest('tr').remove();
            
            hasUnsavedChanges = true; // <--- ADD THIS LINE
            
            // ... rest of your existing code ...
        }
    });

    // 4. The Dispatch Button Logic
    $('#btn-dispatch').on('click', function() {
        if (hasUnsavedChanges) {
            alert("⚠️ YOU HAVE UNSAVED CHANGES!\n\nYou added a product, changed a quantity, or updated form details. Please click the blue 'Update Order' button to save your changes before dispatching.");
            
            // Optionally blink the Update button to guide their eye
            $('button[type="submit"].btn-primary').fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100);
            return false;
        }

        if (confirm("Are you sure you want to dispatch this order? This action finalizes the shipment.")) {
            window.location.href = $(this).data('url');
        }
    });
});
</script>