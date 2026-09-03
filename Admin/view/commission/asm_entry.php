<?php 
$pageTitle = $isEditMode ? "Edit Commission" : "ASM Commissions";
include 'view/layout/header.php'; 
?>

<div id="container">
    <div class="detail d-flex justify-content-between align-items-center mb-2 mt-2">
        <h3><?= $isEditMode ? "Edit Commission (Payout #$payout_id)" : "Manage ASM Commissions" ?></h3>
        <div>
            <a href="<?= BASE_URL ?>asmcommision/asm_history" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-left"></i> Back to List</a>
        </div>
    </div>
    <hr style="margin-top:0px; margin-bottom:10px; border-top:1px solid #333;">

    <input type="hidden" id="is_edit_mode" value="<?= $isEditMode ? '1' : '0' ?>">
    <input type="hidden" id="edit_payout_id" value="<?= $isEditMode ? $payout_id : 0 ?>">
    <input type="hidden" id="edit_hq_id" value="<?= $isEditMode ? $edit_hq_id : 0 ?>">

    <!-- ================== SEARCH FILTERS ================== -->
    <?php if (!$isEditMode): ?>
    <div class="container border px-3 py-3 mb-4" id="searchFiltersBlock">
        <div class="row">
            <div class="col-lg-3">
                <div class="form-group">
                    <label>Select State</label>
                    <select name="state" id="state_id" class="form-control select2">
                        <option value="">-- Select State --</option>
                        <?php while($srow = mysqli_fetch_assoc($states)): ?>
                            <option value="<?= htmlspecialchars($srow['state_id']); ?>"><?= htmlspecialchars($srow['state_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label>ASM</label>
                    <select name="asm" id="asm" class="form-control select2">
                        <option value="">-- Select State First --</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="form-group">
                    <label>Select Month</label>
                    <input type="month" id="filter_month" class="form-control">
                </div>
            </div>
            <div class="col-lg-2 d-flex align-items-end">
                <button id="btnFetchBills" class="btn btn-success w-100">Get Bills</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ================== DATA TABLE ================== -->
    <div class="commission-container">
        <h5 class="text-primary mb-3">Eligible Bills for Commission</h5>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;"><input type="checkbox" id="selectAll"></th>
                    <th>Bill No</th><th>Date</th><th>Stockist</th><th>Status</th><th>Taxable Amt</th><th>Commission</th>
                </tr>
            </thead>
            <tbody id="commissionTableBody">
                <?php if ($isEditMode && !empty($editData['bills'])): ?>
                    <?php foreach ($editData['bills'] as $bill): 
                        $isChecked = ($bill['commission_payout_id'] == $payout_id) ? 'checked' : '';
                        $rowClass = $isChecked ? 'table-success' : '';
                        $badgeClass = ($bill['pay_status'] === 'PAID') ? 'bg-success' : 'bg-secondary';
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <td><input type="checkbox" class="bill-checkbox" value="<?= $bill['inward_id'] ?>" data-commission="<?= $bill['commission_amount'] ?>" <?= $isChecked ?>></td>
                            <td><?= htmlspecialchars($bill['inward_no']) ?></td>
                            <td><?= htmlspecialchars($bill['bill_date']) ?></td>
                            <td><?= htmlspecialchars($bill['stockist_name']) ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($bill['pay_status']) ?></span></td>
                            <td>₹<?= number_format($bill['taxable_amount'], 2) ?></td>
                            <td style="color: green; font-weight: bold;">₹<?= number_format($bill['commission_amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php elseif ($isEditMode): ?>
                    <tr><td colspan="7" class="text-center text-muted">No eligible bills found.</td></tr>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center text-muted">Select an HQ and click Get Bills</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ================== DYNAMIC ADJUSTMENTS ================== -->
        <div id="adjustmentsContainer" class="mt-4 mb-5" style="padding-bottom: 80px;">
            <h5 class="mb-3 text-primary">ADDITIONS/DEDUCTIONS</h5>
            <div id="adjustmentsList">
                <?php if ($isEditMode && !empty($editData['adjustments'])): ?>
                    <?php foreach ($editData['adjustments'] as $adj): ?>
                        <div class="row mb-2 adjustment-row">
                            <div class="col-md-4"><input type="text" class="form-control adj-desc" value="<?= htmlspecialchars($adj['description']) ?>" placeholder="Reason"></div>
                            <div class="col-md-2">
                                <select class="form-control adj-type">
                                    <option value="-" <?= $adj['adj_type'] == '-' ? 'selected' : '' ?>>- (Deduct)</option>
                                    <option value="+" <?= $adj['adj_type'] == '+' ? 'selected' : '' ?>>+ (Add)</option>
                                </select>
                            </div>
                            <div class="col-md-3"><input type="number" class="form-control adj-amt" value="<?= htmlspecialchars($adj['amount']) ?>" step="0.01" min="0"></div>
                            <div class="col-md-3"><button type="button" class="btn btn-danger btn-remove-adj"><i class="fa fa-trash"></i></button></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="row mb-2 adjustment-row">
                        <div class="col-md-4"><input type="text" class="form-control adj-desc" placeholder="Reason (Optional)"></div>
                        <div class="col-md-2"><select class="form-control adj-type"><option value="-">- (Deduct)</option><option value="+">+ (Add)</option></select></div>
                        <div class="col-md-3"><input type="number" class="form-control adj-amt" placeholder="Amount (₹)" step="0.01" min="0"></div>
                        <div class="col-md-3"><button type="button" class="btn btn-success btn-add-adj"><i class="fa fa-plus"></i></button></div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($isEditMode): ?>
                <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btnAddEmptyRow"><i class="fa fa-plus"></i> Add Another Adjustment</button>
            <?php endif; ?>
        </div>

        <!-- ================== FOOTER ================== -->
        <div class="commission-footer" style="position: sticky; bottom: 0; background: #f8f9fa; padding: 15px; border-top: 2px solid #ddd; display: flex; justify-content: space-between; align-items: center; z-index: 100;">
            <div><strong>Selected Bills: </strong> <span id="selectedCount">0</span></div>
            <div>
                <strong>Total Payout: </strong> 
                <span style="font-size: 1.5em; color: #28a745;">₹<span id="totalCommission">0.00</span></span>
                <!-- Exact unrounded amount note -->
                <small id="exactAmountNote" class="text-muted ms-2" style="font-size: 0.85em; font-weight: normal;"></small>
            </div>
            <div>
                <button id="btnSavePending" class="btn btn-warning text-dark fw-bold action-btn" data-status="Pending" disabled>
                    Save
                </button>
                <button id="btnMarkPaid" class="btn btn-success fw-bold action-btn" data-status="Paid" disabled>
                    Mark as Paid
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'view/layout/footer.php'; ?>

<script>
$(document).ready(function() {
    const isEditMode = $('#is_edit_mode').val() === '1';
    const editPayoutId = $('#edit_payout_id').val();
    const editHqId = $('#edit_hq_id').val();

    if (isEditMode) {
        calculateTotal();
    } else {
        let currentDate = new Date();
        let maxStr = `${currentDate.getFullYear()}-${String(currentDate.getMonth() + 1).padStart(2, '0')}`;
        $('#filter_month').attr('max', maxStr);
        currentDate.setMonth(currentDate.getMonth() - 1);
        $('#filter_month').val(`${currentDate.getFullYear()}-${String(currentDate.getMonth() + 1).padStart(2, '0')}`);
        
        $('.select2').select2({ theme: 'bootstrap-5' });

        $('#state_id').change(function() {
            $.post('<?= BASE_URL ?>asmcommision/getAsmByStateAjax', { state_id: $(this).val() }, function(res) {
                $('#asm').html(res).trigger('change');
            });
        });

        $('#btnFetchBills').click(function() {
            let hqId = $('#asm').val();
            if (!hqId) return alert('Please select an ASM first.');
            
            $('#commissionTableBody').html('<tr><td colspan="7" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');
            resetMath();

            $.get('<?= BASE_URL ?>asmcommision/fetch_mr_bills', { hqId: hqId, month: $('#filter_month').val() }, function(res) {
                if(res.success && res.data.length > 0) {
                    let rows = res.data.map(bill => `
                        <tr>
                            <td><input type="checkbox" class="bill-checkbox" value="${bill.inward_id}" data-commission="${bill.commission_amount}"></td>
                            <td>${bill.inward_no}</td><td>${bill.bill_date}</td><td>${bill.stockist_name}</td>
                            <td><span class="badge ${bill.pay_status === 'PAID' ? 'bg-success' : 'bg-secondary'}">${bill.pay_status}</span></td>
                            <td>₹${parseFloat(bill.taxable_amount).toFixed(2)}</td>
                            <td style="color: green; font-weight: bold;">₹${parseFloat(bill.commission_amount).toFixed(2)}</td>
                        </tr>
                    `).join('');
                    $('#commissionTableBody').html(rows);
                } else {
                    $('#commissionTableBody').html(`<tr><td colspan="7" class="text-center text-danger">${res.msg}</td></tr>`);
                }
            });
        });
    }

    function appendEmptyAdjustmentRow() {
        $('#adjustmentsList .btn-add-adj').replaceWith('<button type="button" class="btn btn-danger btn-remove-adj"><i class="fa fa-trash"></i></button>');
        
        let newRow = $(`
            <div class="row mb-2 adjustment-row">
                <div class="col-md-4"><input type="text" class="form-control adj-desc" placeholder="Reason (Optional)"></div>
                <div class="col-md-2">
                    <select class="form-control adj-type">
                        <option value="-">- (Deduct)</option>
                        <option value="+">+ (Add)</option>
                    </select>
                </div>
                <div class="col-md-3"><input type="number" class="form-control adj-amt" placeholder="Amount (₹)" step="0.01" min="0"></div>
                <div class="col-md-3"><button type="button" class="btn btn-success btn-add-adj"><i class="fa fa-plus"></i></button></div>
            </div>
        `);
        
        $('#adjustmentsList').append(newRow);
        newRow.find('.adj-type').val('-');
    }

    $(document).on('click', '.btn-add-adj', appendEmptyAdjustmentRow);
    $('#btnAddEmptyRow').click(appendEmptyAdjustmentRow);
    $(document).on('click', '.btn-remove-adj', function() { $(this).closest('.adjustment-row').remove(); calculateTotal(); });

    $(document).on('input', '.adj-amt', calculateTotal);
    $(document).on('change', '.adj-type, .bill-checkbox', calculateTotal);
    $('#selectAll').change(function() { $('.bill-checkbox').prop('checked', $(this).is(':checked')); calculateTotal(); });

    function calculateTotal() {
        let total = 0, count = 0;
        $('.bill-checkbox:checked').each(function() { total += parseFloat($(this).data('commission')); count++; });
        $('.adjustment-row').each(function() {
            let amt = parseFloat($(this).find('.adj-amt').val()) || 0;
            if (amt > 0) total = ($(this).find('.adj-type').val() === '+') ? (total + amt) : (total - amt);
        });
        
        // --- FIX APPLIED HERE: ROUND OFF TOTAL ---
        let roundedTotal = Math.round(total);
        
        $('#totalCommission').text(roundedTotal.toFixed(2));
        
        // Show exact unrounded amount if rounding changed the value
        if (total !== roundedTotal && count > 0) {
            $('#exactAmountNote').text(`(Exact: ₹${total.toFixed(2)})`);
        } else {
            $('#exactAmountNote').text('');
        }
        
        $('#selectedCount').text(count);
        $('.action-btn').prop('disabled', count === 0);
    }
    
    function resetMath() { 
        $('#totalCommission').text('0.00'); 
        $('#exactAmountNote').text('');
        $('#selectedCount').text('0'); 
        $('#selectAll').prop('checked', false); 
        $('.action-btn').prop('disabled', true); 
    }

    // Single Form Submission Handler for Both Buttons
    $('.action-btn').click(function() {
        let selectedStatus = $(this).data('status');
        let selectedBillIds = [];
        $('.bill-checkbox:checked').each(function() { selectedBillIds.push($(this).val()); });

        let extraAdjustments = [];
        $('.adjustment-row').each(function() {
            let amt = parseFloat($(this).find('.adj-amt').val()) || 0;
            if (amt > 0) extraAdjustments.push({ description: $(this).find('.adj-desc').val(), type: $(this).find('.adj-type').val(), amount: amt });
        });

        // This picks up the rounded value from the text display
        let finalTotal = $('#totalCommission').text(); 
        
        let confirmMsg = selectedStatus === 'Paid' 
            ? `Mark this commission as PAID? Final payout: ₹${finalTotal}. (This action locks the commission).` 
            : `Save this commission as PENDING? Final payout: ₹${finalTotal}`;

        if (!confirm(confirmMsg)) return;

        let btn1 = $('#btnSavePending'), btn2 = $('#btnMarkPaid');
        btn1.prop('disabled', true);
        btn2.prop('disabled', true);

        let payloadData = { 
            asm_id: isEditMode ? editHqId : $('#asm').val(), 
            bill_ids: JSON.stringify(selectedBillIds), 
            adjustments: JSON.stringify(extraAdjustments), 
            final_payout: finalTotal, // ONLY the rounded total is sent
            status: selectedStatus
        };
        
        if (isEditMode) payloadData.payout_id = editPayoutId;

        $.post(isEditMode ? '<?= BASE_URL ?>asmcommision/update_mrc' : '<?= BASE_URL ?>asmcommision/claim_mrc', payloadData, function(res) {
            if(res.success) {
                alert(res.msg || 'Success!');
                if (isEditMode) window.location.href = '<?= BASE_URL ?>asmcommision/asm_history';
                else { $('#adjustmentsList').empty(); appendEmptyAdjustmentRow(); $('#btnFetchBills').trigger('click'); }
            } else {
                alert('Error: ' + res.msg);
                calculateTotal(); 
            }
        }).fail(function() { 
            alert('Server error occurred.'); 
            calculateTotal(); 
        });
    });
});
</script>