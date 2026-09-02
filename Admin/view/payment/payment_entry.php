<?php 
$pageTitle = "Manual Payment Entry";
include 'view/layout/header.php'; 
?>

<div id="container" class="container-fluid mt-3 mb-5">
    
    <!-- Dynamic Alert Container for JS Responses -->
    <div id="alertContainer"></div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-plus-circle"></i> Manual Payment & Settlement Entry</h5>
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
                            <label class="fw-bold">Select State</label>
                            <select name="state_id" id="state_id" class="form-control select2" required>
                                <option value="">-- Select State --</option>
                                <?php mysqli_data_seek($states, 0); while($srow = mysqli_fetch_assoc($states)): ?>
                                    <option value="<?= htmlspecialchars($srow['state_id']); ?>">
                                        <?= htmlspecialchars($srow['state_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-group">
                            <label class="fw-bold">Head Quarter</label>
                            <select name="hq_id" id="hq_id" class="form-control select2" required>
                                <option value="">-- Select State First --</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-group">
                            <label class="fw-bold">Select MR</label>
                            <select name="mr_id" id="mr_id" class="form-control select2" required>
                                <option value="">-- Select HQ First --</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- ROW 2: Payment Details -->
                <div class="row g-3 mb-3 mt-2">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label class="fw-bold">Commission Type</label>
                            <select name="commission_type" id="commission_type" class="form-control" required>
                                <option value="">-- Select Type --</option>
                                <option value="MRC">MR Commission (MRC)</option>
                                <option value="DRC">Dr Commission (DRC)</option>
                            </select>
                            <small id="balanceDisplay" class="form-text text-muted mt-1 d-block"></small>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-group">
                            <label class="fw-bold">Payment Action</label>
                            <select name="payment_type" id="payment_type" class="form-control" required>
                                <option value="">-- Select Action --</option>
                                <option value="account">Transfer to Bank Account</option>
                                <option value="old_bill">Settle Old Outstanding Bills</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-group">
                            <label class="fw-bold">Amount (₹)</label>
                            <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="1" placeholder="Enter amount" required>
                        </div>
                    </div>
                </div>

                <!-- ROW 3: Stockist Settlement Details (Hidden by Default) -->
                <div class="row g-3 mb-4 p-3 bg-light rounded border" id="stockistContainer" style="display: none;">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label class="fw-bold text-danger">Select Stockist to Settle</label>
                            <select name="stockist_id" id="stockist_id" class="form-control select2">
                                <option value="">-- Select HQ First --</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-group">
                            <label class="fw-bold text-primary">Settlement Date (For CD Math)</label>
                            <input type="date" name="settlement_date" id="settlement_date" class="form-control" value="<?= date('Y-m-d') ?>">
                            <small class="form-text text-muted">Change date to retroactively apply Cash Discount.</small>
                        </div>
                    </div>

                    <div class="col-lg-4 d-flex align-items-center">
                        <div id="outstandingSummary" class="w-100 p-2 bg-white border rounded shadow-sm">
                            <span class="text-muted"><i class="fa fa-info-circle"></i> Select stockist to view balance.</span>
                        </div>
                    </div>

                    <!-- Detailed Bills Table -->
                    <div class="col-lg-12 mt-3" id="billsTableContainer" style="display: none;">
                        <h6 class="fw-bold text-secondary mb-2">Pending Bills & Applicable CD</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover bg-white">
                               <thead>
                                    <tr>
                                        <th>Bill No</th>
                                        <th>Date (Age)</th>
                                        <th class="text-end">Gross Amt</th>
                                        <th class="text-end">Paid Amt</th>
                                        <th class="text-end">Pending Amt</th>
                                        <th class="text-end">CD Already Given</th>
                                        <th class="text-end">New CD Earned</th>
                                        <th class="text-end">Penalty (CD Lost)</th>
                                        <th class="text-end">Net Bill Payable</th>
                                    </tr>
                                </thead>
                                <tbody id="billsTableBody">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- NEW: Payment Allocation History (For Edit/View Mode) -->
                    <div class="col-lg-12 mt-3" id="allocatedBillsContainer" style="display: none;">
                        <h6 class="fw-bold text-success mb-2"><i class="fa fa-history"></i> Payment Allocation History</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover bg-white">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Bill No</th>
                                        <th>Bill Date</th>
                                        <th class="text-end">Bill Total</th>
                                        <th class="text-end text-success">Cash Allocated (This Payment)</th>
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
                
                <div class="row g-3 mb-2 mt-2">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="fw-bold">Notes / Reference (Optional)</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Enter bank reference number or adjustment details..."></textarea>
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
        let stateId = $(this).val();
        
        $('#mr_id').html('<option value="">-- Select HQ First --</option>');
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
                if (res.success) {
                    let mrOptions = '<option value="">-- Select MR --</option>';
                    res.data.forEach(mr => { mrOptions += `<option value="${mr.m_id}">${mr.mr_name}</option>`; });
                    $('#mr_id').html(mrOptions);
                } else {
                    $('#mr_id').html('<option value="">No MR found</option>');
                }
            }, 'json');

            // Fetch Stockists for this HQ
            $.get(BASE_URL + 'payment/get_stockists_by_hq', { hq_id: hqId }, function(res) {
                if (res.success) {
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

    // 3. Toggle Stockist Container based on Payment Type
    $('#payment_type').change(function() {
        let type = $(this).val();
        if (type === 'old_bill') {
            $('#stockistContainer').fadeIn();
            $('#stockist_id').prop('required', true);
            $('#settlement_date').prop('required', true);
            fetchOutstanding(); // Attempt fetch if values already exist
        } else {
            $('#stockistContainer').hide();
            $('#stockist_id').prop('required', false).val('').trigger('change');
            $('#settlement_date').prop('required', false);
        }
    });

    // Fetch Balance when Commission Type or HQ changes
    $('#commission_type, #hq_id').change(function() {
        let type = $('#commission_type').val();
        let hqId = $('#hq_id').val();
        
        if(editData !== null) return; 

        if(type && hqId) {
            $('#balanceDisplay').html('<i class="fa fa-spinner fa-spin"></i> Fetching balance...');
            
            $.get(BASE_URL + 'payment/get_balance', { hq_id: hqId, type: type }, function(res) {
                if(res.success) {
                    $('#balanceDisplay').html(`Available Balance: <span class="text-success fw-bold">₹${res.balance.toFixed(2)}</span>`);
                    $('#amount').attr('max', res.balance);
                } else {
                    $('#balanceDisplay').html('<span class="text-danger">Error fetching balance</span>');
                }
            }, 'json');
        } else {
            $('#balanceDisplay').html('');
            $('#amount').removeAttr('max');
        }
    });

    // Central function to fetch bills based on Stockist & Selected Date
   function fetchOutstanding() 
   {
        let stockistId = $('#stockist_id').val();
        let paymentType = $('#payment_type').val();
        let settlementDate = $('#settlement_date').val();
        
        if(editData !== null) return; 

        if(stockistId && paymentType === 'old_bill' && settlementDate) {
            $('#outstandingSummary').html('<i class="fa fa-spinner fa-spin"></i> Fetching bills & calculating CD...');
            $('#billsTableContainer').hide();
            
            // Pass the custom date to backend for DATEDIFF calculations
            $.get(BASE_URL + 'payment/get_outstanding', { stockist_id: stockistId, date: settlementDate }, function(res) {
                if(res.success) {
                    // Update Summary
                    $('#outstandingSummary').html(`
                        <div class="row text-center">
                            <div class="col-4 border-end">
                                <small class="text-muted d-block">Total Pending</small>
                                <strong class="text-dark fs-5">₹${res.outstanding.toFixed(2)}</strong>
                            </div>
                            <div class="col-4 border-end">
                                <small class="text-muted d-block">Net Payable </small>
                                <strong class="text-dark fs-5">₹${res.net_payable.toFixed(2)}</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">New CD Earned</small>
                                <strong class="text-success fs-5">₹${res.eligible_cd.toFixed(2)}</strong>
                                ${res.total_penalty > 0 ? `<br><small class="text-danger">Penalty: ₹${res.total_penalty.toFixed(2)}</small>` : ''}
                            </div>
                        </div>
                    `);
                    
                    let walletMax = parseFloat($('#amount').attr('max')) || 0;
                    let finalMax = (walletMax > 0 && walletMax < res.net_payable) ? walletMax : res.net_payable;
                    
                    $('#amount').attr('max', finalMax);
                    $('#amount').attr('title', `Maximum allowed is ₹${finalMax.toFixed(2)}`);

                   // Populate Bills Table with ALL details
                    if (res.bills && res.bills.length > 0) {
                        let rows = '';
                        res.bills.forEach(b => {
                            let cdEarned = parseFloat(b.eligible_4_cd) + parseFloat(b.eligible_2_cd);
                            let cdGiven = parseFloat(b.already_4_cd) + parseFloat(b.already_2_cd);
                            let pen = parseFloat(b.penalty_amount);
                            let pending = parseFloat(b.pending_amount);
                            let cdperc = "";
                            if (parseFloat(b.eligible_4_cd) > 0){
                                cdperc += '4%';
                            }else if (parseFloat(b.eligible_2_cd) > 0){
                                cdperc += '2%';
                            }
                            
                            // Per-bill net payable = Pending Amount - New CD + Revoked CD Penalty
                            let billNetPayable = pending - cdEarned + pen;
                            
                            rows += `<tr>
                                <td>
                                    <strong>${b.inward_no}</strong><br>
                                    <span class="badge bg-secondary">${cdperc}</span>
                                </td>
                                <td>
                                    ${b.inward_date}<br>
                                    <small class="text-danger">${b.bill_age_days} Days Old</small>
                                </td>
                                <td class="text-end">₹${parseFloat(b.gross_amount).toFixed(2)}</td>
                                <td class="text-end text-primary">₹${parseFloat(b.paid_amt).toFixed(2)}</td>
                                <td class="text-end fw-bold">₹${pending.toFixed(2)}</td>
                                <td class="text-end text-info">${cdGiven > 0 ? '₹'+cdGiven.toFixed(2) : '-'}</td>
                                <td class="text-end text-success">${cdEarned > 0 ? '+ ₹'+cdEarned.toFixed(2) : '-'}</td>
                                <td class="text-end text-danger">${pen > 0 ? '- ₹'+pen.toFixed(2) : '-'}</td>
                                <td class="text-end fw-bold text-dark">₹${billNetPayable.toFixed(2)}</td>
                            </tr>`;
                        });
                        $('#billsTableBody').html(rows);
                        $('#billsTableContainer').fadeIn();
                    } else {
                        $('#billsTableContainer').hide();
                    }

                } else {
                    $('#outstandingSummary').html('<span class="text-danger"><i class="fa fa-times-circle"></i> Error fetching data</span>');
                    $('#billsTableContainer').hide();
                }
            }, 'json');
        } else {
            $('#outstandingSummary').html('<span class="text-muted"><i class="fa fa-info-circle"></i> Select stockist and date to view balance.</span>');
            $('#billsTableContainer').hide();
            
            let walletMax = $('#amount').attr('data-wallet-max');
            if (walletMax) $('#amount').attr('max', walletMax);
        }
    }

    // Trigger fetch on Stockist OR Date change
    $('#stockist_id, #settlement_date').change(fetchOutstanding);

    // 4. Handle Form Submission
    $('#paymentEntryForm').on('submit', function(e) {
        e.preventDefault();

        let submitBtn = $('#btnSubmitPayment');
        submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);

        // Serialize form data
        let formData = $(this).serialize();

        $.post(BASE_URL + 'payment/submit_manual_entry', formData, function(res) {
            if (res.success) {
                showAlert(res.msg, 'success');
                $('#btnReset').trigger('click'); // Clear form on success
            } else {
                showAlert(res.msg, 'danger');
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
        $('.select2').val('').trigger('change');
        $('#stockistContainer').hide();
        $('#billsTableContainer').hide();
        $('#allocatedBillsContainer').hide();
        $('#outstandingSummary').html('<span class="text-muted"><i class="fa fa-info-circle"></i> Select stockist to view balance.</span>');
    });

    // ==========================================
    // AUTO-FILL DATA IF IN EDIT (REVERSE) MODE
    // ==========================================
    let editData = <?= $is_edit ? json_encode($edit_data) : 'null' ?>;
    
    if (editData !== null) {
        // Pre-fill fields and disable them
        $('#amount').val(editData.amount_paid).prop('readonly', true);
        
        let commTypeStr = editData.commission_type ? editData.commission_type.toUpperCase() : '';
        $('#commission_type').val(commTypeStr).prop('disabled', true);
        
        $('#notes').val(editData.bank_details).prop('readonly', true);
        
        let paymentAction = editData.payment_method === 'Commission Adjustment' ? 'old_bill' : 'account';
        $('#payment_type').val(paymentAction).prop('disabled', true).trigger('change');

        $('#state_id, #hq_id, #mr_id, #stockist_id, #settlement_date').prop('disabled', true);

       // Cascade selections automatically with delays to allow AJAX to load dropdown values
        if (editData.state_id && editData.state_id !== '') {
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

        // Hide normal buttons and inject the Reverse button
      $('#btnSubmitPayment, #btnReset').hide();
        
        if (editData.approval_status !== 'reversed') {
            // Updated selector: Target the parent div of the submit button specifically
            $('#btnSubmitPayment').parent().append(`<button type="button" class="btn btn-danger fw-bold" id="btnReversePayment"><i class="fa fa-undo"></i> Reverse Payment</button>`);
        } else {
            // Updated selector
            $('#btnSubmitPayment').parent().append(`<span class="badge bg-danger p-2 fs-6"><i class="fa fa-ban"></i> Already Reversed</span>`);
        }

        // NEW: Fetch and display historical allocations if it was a bill settlement
        if (paymentAction === 'old_bill') {
            $('#allocatedBillsContainer').fadeIn();
            $('#allocatedBillsBody').html('<tr><td colspan="6" class="text-center py-3"><i class="fa fa-spinner fa-spin"></i> Fetching allocation history...</td></tr>');
            
            $.get(BASE_URL + 'payment/get_payment_allocations', { payment_id: editData.id }, function(res) {
                if (res.success && res.data.length > 0) {
                    let rows = '';
                    res.data.forEach(b => {
                        let allocated = parseFloat(b.amount_allocated) || 0;
                        let cdEarned = parseFloat(b.cd_earned) || 0;
                        let cdRevoked = parseFloat(b.cd_revoked) || 0;
                        
                        let grandTotal = parseFloat(b.grand_total) || 0;
                        let currentPaid = parseFloat(b.paid_amt) || 0;
                        
                        // Remaining balance calculation
                        let remaining = grandTotal - currentPaid;
                        if (remaining < 0) remaining = 0;

                        let cdHtml = '';
                        if (cdEarned > 0) cdHtml += `<span class="badge bg-success text-white mb-1"><i class="fa fa-arrow-down"></i> ₹${cdEarned.toFixed(2)} CD</span><br>`;
                        if (cdRevoked > 0) cdHtml += `<span class="badge bg-danger text-white"><i class="fa fa-arrow-up"></i> ₹${cdRevoked.toFixed(2)} Penalty</span>`;
                        if (cdHtml === '') cdHtml = '<span class="text-muted small">None</span>';

                        rows += `<tr>
                            <td class="fw-bold text-center">${b.inward_no}</td>
                            <td class="text-center">${b.inward_date}</td>
                            <td class="text-end fw-bold">₹${grandTotal.toFixed(2)}</td>
                            <td class="text-end fw-bold text-success">₹${allocated.toFixed(2)}</td>
                            <td class="text-center">${cdHtml}</td>
                        </tr>`;
                    });
                    $('#allocatedBillsBody').html(rows);
                } else {
                    $('#allocatedBillsBody').html('<tr><td colspan="6" class="text-center text-muted">No specific bill allocations found.</td></tr>');
                }
            }, 'json').fail(function() {
                $('#allocatedBillsBody').html('<tr><td colspan="6" class="text-center text-danger">Failed to load allocation details.</td></tr>');
            });
        }
    }

    // ==========================================
    // REVERSE PAYMENT AJAX ACTION
    // ==========================================
    $(document).on('click', '#btnReversePayment', function() {
        if (!confirm('Are you sure you want to REVERSE this payment? This will restore unpaid bills and remove wallet transactions.')) {
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