<?php 
$pageTitle = "Manual Payment Entry";
include 'view/layout/header.php'; 
?>

<div id="container" class="container-fluid mt-3 mb-5">
    
    <!-- Dynamic Alert Container for JS Responses -->
    <div id="alertContainer"></div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-plus-circle"></i> Manual Payment Entry</h5>
        </div>
        
        <div class="card-body">
            <form id="paymentEntryForm">
                
                <!-- EDIT MODE WARNING & HIDDEN ID -->
                <?php $is_edit = !empty($edit_data); ?>
                <?php if($is_edit): ?>
                    <input type="hidden" name="payment_id" id="edit_payment_id" value="<?= htmlspecialchars($edit_data['id']) ?>">
                    <div class="alert alert-warning shadow-sm border-warning">
                        <strong><i class="fa fa-exclamation-triangle"></i> Edit Mode (Read-Only)</strong><br> 
                        You are viewing a processed entry. To make changes, you must reverse this entry and create a new one.
                    </div>
                <?php endif; ?>

                <!-- ROW 1: Location & Personnel -->
                <div class="row g-3 mb-4">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label class="fw-bold">Select State <span class="text-danger">*</span></label>
                            <select name="state_id" id="state_id" class="form-control select2" required>
                                <option value="">-- Select State --</option>
                                <?php if(isset($states) && is_object($states)): ?>
                                    <?php mysqli_data_seek($states, 0); while($srow = mysqli_fetch_assoc($states)): ?>
                                        <option value="<?= htmlspecialchars($srow['state_id']); ?>">
                                            <?= htmlspecialchars($srow['state_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-group">
                            <label class="fw-bold">Head Quarter <span class="text-danger">*</span></label>
                            <select name="hq_id" id="hq_id" class="form-control select2" required>
                                <option value="">-- Select State First --</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-group">
                            <label class="fw-bold">Select MR</label>
                            <select name="mr_id" id="mr_id" class="form-control select2">
                                <option value="">-- Select HQ First --</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- ROW 2: Stockist Selection & Settlement Date (Get to Pay) -->
                <div class="row g-3 mb-4 p-3 bg-light rounded border" id="stockistContainer">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label class="fw-bold text-danger">Select Stockist to Settle <span class="text-danger">*</span></label>
                            <select name="stockist_id" id="stockist_id" class="form-control select2" required>
                                <option value="">-- Select HQ First --</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-group">
                            <label class="fw-bold text-primary">Settlement Date (For CD Math) <span class="text-danger">*</span></label>
                            <input type="date" name="settlement_date" id="settlement_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            <small class="form-text text-muted">Change date to calculate Cash Discount as of that day.</small>
                        </div>
                    </div>

                    <div class="col-lg-4 d-flex align-items-center">
                        <div id="outstandingSummary" class="w-100 p-2 bg-white border rounded shadow-sm">
                            <span class="text-muted"><i class="fa fa-info-circle"></i> Select stockist to view balance.</span>
                        </div>
                    </div>

                    <!-- Detailed Bills Table -->
                    <div class="col-lg-12 mt-3" id="billsTableContainer" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-secondary mb-0"><i class="fa fa-file-invoice"></i> Pending Bills & Applicable CD</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnFillNetPayable">
                                <i class="fa fa-arrow-down"></i> Pay Net Amount
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover bg-white mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Bill No</th>
                                        <th>Date (Age)</th>
                                        <th class="text-end">Gross Amt</th>
                                        <th class="text-end">Paid Amt</th>
                                        <th class="text-end">Pending Amt</th>
                                        <th class="text-end text-success">New CD Earned</th>
                                        <th class="text-end text-danger">Penalty (CD Lost)</th>
                                        <th class="text-end fw-bold">Net Bill Payable</th>
                                    </tr>
                                </thead>
                                <tbody id="billsTableBody">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Payment Allocation History (For Edit/View Mode) -->
                    <div class="col-lg-12 mt-3" id="allocatedBillsContainer" style="display: none;">
                        <h6 class="fw-bold text-success mb-2"><i class="fa fa-history"></i> Payment Allocation History</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover bg-white mb-0">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Bill No</th>
                                        <th>Bill Date</th>
                                        <th class="text-end">Bill Total</th>
                                        <th class="text-end text-success">Cash Allocated</th>
                                        <th class="text-center">CD / Penalty</th>
                                    </tr>
                                </thead>
                                <tbody id="allocatedBillsBody">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ROW 3: Payment Details -->
                <div class="row g-3 mb-4">
                   

                    <div class="col-lg-3">
                        <div class="form-group">
                            <label class="fw-bold">Amount Paid (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="amount" class="form-control fw-bold text-success" step="0.01" min="0.01" placeholder="Enter amount" required>
                        </div>
                    </div>

                   
                </div>

                <!-- ROW 4: Notes -->
                <div class="row g-3 mb-3">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="fw-bold">Notes / Remarks (Optional)</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Enter additional notes or payment remarks..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="button" id="btnReset" class="btn btn-secondary me-2"><i class="fa fa-sync"></i> Reset Form</button>
                    <button type="submit" id="btnSubmitPayment" class="btn btn-success fw-bold"><i class="fa fa-save"></i> Submit Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const BASE_URL = "<?= BASE_URL ?>";
</script>

<?php include 'view/layout/footer.php'; ?>

<!-- Javascript Logic -->
<script>
$(document).ready(function() {
    
    // Initialize Select2
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

    const alertContainer = document.getElementById('alertContainer');
    let currentNetPayable = 0.00;

    function showAlert(msg, type = 'success') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${msg}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
        alertContainer.innerHTML = alertHtml;
        $('html, body').animate({ scrollTop: 0 }, 'fast');
        if(type === 'success') {
            setTimeout(() => { $('.alert-success').fadeOut('slow'); }, 5000);
        }
    }

    // 1. Load HQ based on State
    $('#state_id').change(function() {
        let stateId = $(this).val();$('#mr_id').html('<option value="">-- Select HQ First --</option>');
        $('#stockist_id').html('<option value="">-- Select HQ First --</option>');

        if (stateId) {
            $('#hq_id').html('<option value="">Loading...</option>');
            $.post(BASE_URL + 'headquarter/getHqByStateAjax', { state_id: stateId }, function(res) {
                $('#hq_id').html(res).trigger('change');
            });
        } else {
            $('#hq_id').html('<option value="">-- Select State First --</option>').trigger('change');
        }
    });

    // 2. Load MRs and Stockists based on HQ
    $('#hq_id').change(function() {
        let hqId = $(this).val();
        
        if (hqId) {
            $('#mr_id').html('<option value="">Loading...</option>');
            $('#stockist_id').html('<option value="">Loading...</option>');

            // Fetch MRs for this HQ
            $.get(BASE_URL + 'payment/get_mrs_by_hq', { hq_id: hqId }, function(res) {
                if (res.success && res.data) {
                    let mrOptions = '<option value="">-- Select MR --</option>';
                    res.data.forEach(mr => { mrOptions += `<option value="${mr.m_id}">${mr.mr_name}</option>`; });
                    $('#mr_id').html(mrOptions);
                } else {
                    $('#mr_id').html('<option value="">No MR found</option>');
                }
            }, 'json');

            // Fetch Stockists for this HQ
            $.get(BASE_URL + 'payment/get_stockists_by_hq', { hq_id: hqId }, function(res) {
                if (res.success && res.data) {
                    let stkOptions = '<option value="">-- Select Stockist --</option>';
                    res.data.forEach(stk => { stkOptions += `<option value="${stk.stockist_id}">${stk.stockist_name}</option>`; });
                    $('#stockist_id').html(stkOptions);
                } else {
                    $('#stockist_id').html('<option value="">No Stockists found</option>');
                }
            }, 'json');

        } else {
            $('#mr_id').html('<option value="">-- Select HQ First --</option>');
            $('#stockist_id').html('<option value="">-- Select HQ First --</option>');
        }
    });

    // 3. Central function to fetch Outstanding Bills & CD calculation
    function fetchOutstanding() {
        let stockistId     = $('#stockist_id').val();
        let settlementDate = $('#settlement_date').val();
        
        if(editData !== null) return; 

        if (stockistId && settlementDate) {
            $('#outstandingSummary').html('<div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Fetching bills & calculating CD...</div>');
            $('#billsTableContainer').hide();
            
            $.get(BASE_URL + 'payment/get_outstanding', { stockist_id: stockistId, date: settlementDate }, function(res) {
                if(res.success) {
                    let totalPending = parseFloat(res.total_outstanding ?? res.outstanding ?? 0);
                    let netPayable   = parseFloat(res.net_payable ?? 0);
                    let eligibleCd   = parseFloat(res.eligible_cd ?? 0);
                    let totalPenalty = parseFloat(res.total_penalty ?? 0);
                    let bills        = res.bill_details || res.bills || [];

                    currentNetPayable = netPayable > 0 ? netPayable : 0;

                    let netPayableColor = netPayable > 0 ? 'text-danger' : 'text-success';
                    let netPayableLabel = netPayable < 0 ? 'Advance Credit' : 'Net Payable';
                    let netPayableDisplay = Math.abs(netPayable).toFixed(2);

                    $('#outstandingSummary').html(`
                        <div class="row text-center g-1">
                            <div class="col-4 border-end">
                                <small class="text-muted d-block" style="font-size:11px;">Total Pending</small>
                                <strong class="text-dark fs-6">₹${totalPending.toFixed(2)}</strong>
                            </div>
                            <div class="col-4 border-end">
                                <small class="text-muted d-block" style="font-size:11px;">${netPayableLabel}</small>
                                <strong class="${netPayableColor} fs-6">₹${netPayableDisplay}</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block" style="font-size:11px;">CD Earned</small>
                                <strong class="text-success fs-6">₹${eligibleCd.toFixed(2)}</strong>
                                ${totalPenalty > 0 ? `<br><small class="text-danger" style="font-size:10px;">Penalty: ₹${totalPenalty.toFixed(2)}</small>` : ''}
                            </div>
                        </div>
                    `);

                    // Populate Bills Table
                    if (bills.length > 0) {
                        let rows = '';
                        bills.forEach(b => {
                            let cd4 = parseFloat(b.eligible_4_cd || 0);
                            let cd2 = parseFloat(b.eligible_2_cd || 0);
                            let cdEarned = cd4 + cd2;
                            let pen = parseFloat(b.penalty_amount || 0);
                            let pending = parseFloat(b.pending_amount || 0);
                            let gross = parseFloat(b.gross_amount || 0);
                            let paid = parseFloat(b.paid_amt || 0);
                            
                            let cdBadge = '';
                            if (cd4 > 0) cdBadge = '<span class="badge bg-success ms-1">4% CD</span>';
                            else if (cd2 > 0) cdBadge = '<span class="badge bg-info text-dark ms-1">2% CD</span>';

                            let isAdvance = (b.balance_type === 'credit' || pending < 0 || b.inward_no.includes('ADVANCE'));
                            let billNetPayable = isAdvance ? pending : (pending - cdEarned + pen);

                            rows += `
                                <tr class="${isAdvance ? 'table-success' : ''}">
                                    <td>
                                        <strong>${b.inward_no}</strong>
                                        ${cdBadge}
                                    </td>
                                    <td>
                                        ${b.inward_date}<br>
                                        <small class="text-muted">${b.bill_age_days ? b.bill_age_days + ' Days Old' : '-'}</small>
                                    </td>
                                    <td class="text-end">₹${gross.toFixed(2)}</td>
                                    <td class="text-end text-primary">₹${paid.toFixed(2)}</td>
                                    <td class="text-end fw-bold ${pending < 0 ? 'text-success' : 'text-danger'}">
                                        ₹${pending.toFixed(2)}
                                    </td>
                                    <td class="text-end text-success">${cdEarned > 0 ? '+ ₹' + cdEarned.toFixed(2) : '-'}</td>
                                    <td class="text-end text-danger">${pen > 0 ? '+ ₹' + pen.toFixed(2) : '-'}</td>
                                    <td class="text-end fw-bold text-dark">₹${billNetPayable.toFixed(2)}</td>
                                </tr>
                            `;
                        });

                        $('#billsTableBody').html(rows);
                        $('#billsTableContainer').fadeIn();
                    } else {
                        $('#billsTableContainer').hide();
                    }

                } else {
                    $('#outstandingSummary').html('<span class="text-danger"><i class="fa fa-times-circle"></i> ' + (res.msg || 'Error fetching data') + '</span>');
                    $('#billsTableContainer').hide();
                }
            }, 'json').fail(function() {
                $('#outstandingSummary').html('<span class="text-danger"><i class="fa fa-times-circle"></i> Failed to connect to server</span>');
                $('#billsTableContainer').hide();
            });
        } else {
            $('#outstandingSummary').html('<span class="text-muted"><i class="fa fa-info-circle"></i> Select stockist and date to view balance.</span>');
            $('#billsTableContainer').hide();
        }
    }

    // Trigger fetch on Stockist OR Date change
    $('#stockist_id, #settlement_date').change(fetchOutstanding);

    // Quick Button: Fill Net Payable Amount into Amount input
    $('#btnFillNetPayable').click(function() {
        if (currentNetPayable > 0) {
            $('#amount').val(currentNetPayable.toFixed(2));
        } else {
            showAlert('Stockist has no pending dues (or has advance credit).', 'info');
        }
    });

    // 4. Handle Form Submission
    $('#paymentEntryForm').on('submit', function(e) {
        e.preventDefault();

        let submitBtn = $('#btnSubmitPayment');
        submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);

        let formData = $(this).serialize();

        $.post(BASE_URL + 'payment/submit_manual_pay_entry', formData, function(res) {
            if (res.success) {
                showAlert(res.msg || 'Payment entered successfully.', 'success');
                $('#btnReset').trigger('click');
            } else {
                showAlert(res.msg || 'Failed to submit payment.', 'danger');
            }
        }, 'json').fail(function() {
            showAlert('Server error occurred while processing the payment.', 'danger');
        }).always(function() {
            submitBtn.html('<i class="fa fa-save"></i> Submit Payment').prop('disabled', false);
        });
    });

    // 5. Reset Form Logic
    $('#btnReset').click(function() {
        $('#paymentEntryForm')[0].reset();
        $('.select2').val('').trigger('change');$('#billsTableContainer').hide();
        $('#allocatedBillsContainer').hide();
        $('#outstandingSummary').html('<span class="text-muted"><i class="fa fa-info-circle"></i> Select stockist to view balance.</span>');
        currentNetPayable = 0.00;
    });

    // ==========================================
    // AUTO-FILL DATA IF IN EDIT (VIEW / REVERSE) MODE
    // ==========================================
    let editData = <?= $is_edit ? json_encode($edit_data) : 'null' ?>;
    
    if (editData !== null) {
        $('#amount').val(editData.amount_paid).prop('readonly', true);
        $('#payment_date').val(editData.payment_date).prop('readonly', true);
        $('#payment_method').val(editData.payment_method).prop('disabled', true);
        $('#bank_details').val(editData.bank_details).prop('readonly', true);
        $('#notes').val(editData.notes || editData.bank_details).prop('readonly', true);

        $('#state_id, #hq_id, #mr_id, #stockist_id, #settlement_date').prop('disabled', true);

        if (editData.state_id) {
            $('#state_id').val(editData.state_id).trigger('change');
            
            setTimeout(() => {
                $('#hq_id').val(editData.hq_id).trigger('change');
                
                setTimeout(() => {
                    if (editData.mr_id && editData.mr_id != 0) {
                        $('#mr_id').val(editData.mr_id).trigger('change');
                    }
                    if (editData.stockist_id && editData.stockist_id != 0) {
                        $('#stockist_id').val(editData.stockist_id).trigger('change');
                    }
                }, 800); 
            }, 800);
        }

        $('#btnSubmitPayment, #btnReset').hide();
        
        if (editData.approval_status !== 'reversed') {
            $('#btnSubmitPayment').parent().append(`
                <button type="button" class="btn btn-danger fw-bold" id="btnReversePayment">
                    <i class="fa fa-undo"></i> Reverse Payment
                </button>
            `);
        } else {
            $('#btnSubmitPayment').parent().append(`
                <span class="badge bg-danger p-2 fs-6"><i class="fa fa-ban"></i> Already Reversed</span>
            `);
        }

        // Fetch Historical Allocations for this Payment
        $('#allocatedBillsContainer').fadeIn();
        $('#allocatedBillsBody').html('<tr><td colspan="5" class="text-center py-3"><i class="fa fa-spinner fa-spin"></i> Fetching allocation history...</td></tr>');
        
        $.get(BASE_URL + 'payment/get_payment_allocations', { payment_id: editData.id }, function(res) {
            if (res.success && res.data && res.data.length > 0) {
                let rows = '';
                res.data.forEach(b => {
                    let allocated = parseFloat(b.amount_allocated || 0);
                    let cdEarned  = parseFloat(b.cd_earned || 0);
                    let cdRevoked = parseFloat(b.cd_revoked || 0);
                    let grandTotal = parseFloat(b.grand_total || 0);

                    let cdHtml = '';
                    if (cdEarned > 0) cdHtml += `<span class="badge bg-success text-white mb-1"><i class="fa fa-arrow-down"></i> ₹${cdEarned.toFixed(2)} CD</span><br>`;
                    if (cdRevoked > 0) cdHtml += `<span class="badge bg-danger text-white"><i class="fa fa-arrow-up"></i> ₹${cdRevoked.toFixed(2)} Penalty</span>`;
                    if (cdHtml === '') cdHtml = '<span class="text-muted small">None</span>';

                    rows += `
                        <tr>
                            <td class="fw-bold text-center">${b.inward_no}</td>
                            <td class="text-center">${b.inward_date || '-'}</td>
                            <td class="text-end fw-bold">₹${grandTotal.toFixed(2)}</td>
                            <td class="text-end fw-bold text-success">₹${allocated.toFixed(2)}</td>
                            <td class="text-center">${cdHtml}</td>
                        </tr>
                    `;
                });
                $('#allocatedBillsBody').html(rows);
            } else {
                $('#allocatedBillsBody').html('<tr><td colspan="5" class="text-center text-muted">No specific bill allocations recorded.</td></tr>');
            }
        }, 'json').fail(function() {
            $('#allocatedBillsBody').html('<tr><td colspan="5" class="text-center text-danger">Failed to load allocation details.</td></tr>');
        });
    }

    // ==========================================
    // REVERSE PAYMENT ACTION
    // ==========================================
    $(document).on('click', '#btnReversePayment', function() {
        if (!confirm('Are you sure you want to REVERSE this payment? This will restore unpaid bills and reverse all allocations.')) {
            return;
        }

        let btn = $(this);
        btn.html('<i class="fa fa-spinner fa-spin"></i> Reversing...').prop('disabled', true);
        
        let paymentId = $('#edit_payment_id').val();

        $.post(BASE_URL + 'payment/reverse_manual_entry', { payment_id: paymentId }, function(res) {
            if (res.success) {
                showAlert(res.msg, 'success');
                setTimeout(() => { window.location.href = BASE_URL + 'payment/payment_list'; }, 2000); 
            } else {
                showAlert(res.msg, 'danger');
                btn.html('<i class="fa fa-undo"></i> Reverse Payment').prop('disabled', false);
            }
        }, 'json').fail(function() {
            showAlert('Server error occurred during reversal.', 'danger');
            btn.html('<i class="fa fa-undo"></i> Reverse Payment').prop('disabled', false);
        });
    });

});
</script>