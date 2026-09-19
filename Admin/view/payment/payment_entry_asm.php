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
                            <label class="fw-bold">ASM</label>
                            <!-- Note: ID is hq_id but it actually holds the ASM ID based on your flow -->
                            <select name="asm_id" id="asm_id" class="form-control select2" required>
                                <option value="">-- Select State First --</option>
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
                                <option value="ASM">Commission</option>
                               
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
                <div class="row g-2 mb-3 p-2 bg-light rounded border shadow-sm" id="stockistContainer" style="display: none;">
                    
                    <!-- 1. Filters Row -->
                    <div class="col-lg-4 col-md-6">
                        <div class="form-group mb-0">
                            <label class="fw-bold text-info small mb-1">Filter by Headquarter</label>
                            <select name="filter_hq_id" id="filter_hq_id" class="form-control form-control-sm select2">
                                <option value="">-- Select ASM First --</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="form-group mb-0">
                            <label class="fw-bold text-danger small mb-1">Select Stockist to Settle</label>
                            <select name="stockist_id" id="stockist_id" class="form-control form-control-sm select2">
                                <option value="">-- Select ASM First --</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-12">
                        <div class="form-group mb-0">
                            <label class="fw-bold text-primary small mb-1">Settlement Date</label>
                            <div class="input-group input-group-sm">
                                <input type="date" name="settlement_date" id="settlement_date" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- 2. Summary Strip -->
                    <div class="col-12 mt-2">
                        <div id="outstandingSummary" class="w-100 p-2 bg-white border rounded">
                            <span class="text-muted small"><i class="fa fa-info-circle"></i> Select stockist to view balance.</span>
                        </div>
                    </div>

                    <!-- 3. Detailed Bills Table -->
                    <div class="col-12 mt-2" id="billsTableContainer" style="display: none;">
                        <h6 class="fw-bold text-secondary mb-1 small">Pending Bills & Applicable CD</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover bg-white mb-0" style="font-size: 0.85rem;">
                               <thead class="table-light align-middle text-center">
                                    <tr>
                                        <th>Bill No</th>
                                        <th>Date (Age)</th>
                                        <th class="text-end">Net Amt</th>
                                        <th class="text-end">Paid Amt</th>
                                        <th class="text-end">Pending Amt</th>
                                        <th class="text-end">CD Given</th>
                                        <th class="text-end">New CD</th>
                                        <th class="text-end">Penalty</th>
                                        <th class="text-end">Net Payable</th>
                                    </tr>
                                </thead>
                                <tbody id="billsTableBody" class="align-middle">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 4. Payment Allocation History -->
                    <div class="col-12 mt-2" id="allocatedBillsContainer" style="display: none;">
                        <h6 class="fw-bold text-success mb-1 small"><i class="fa fa-history"></i> Payment Allocation History</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover bg-white mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light text-center align-middle">
                                    <tr>
                                        <th>Bill No</th>
                                        <th>Bill Date</th>
                                        <th class="text-end">Bill Total</th>
                                        <th class="text-end text-success">Cash Allocated</th>
                                        <th class="text-center">CD / Penalty</th>
                                    </tr>
                                </thead>
                                <tbody id="allocatedBillsBody" class="align-middle">
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

    // 1. Load ASM based on State
    $('#state_id').change(function() {
        let stateId = $(this).val();
        
        $('#filter_hq_id').html('<option value="">-- Select ASM First --</option>');
        $('#stockist_id').html('<option value="">-- Select ASM First --</option>');

        if (stateId) {
            $('#asm_id').html('<option value="">Loading...</option>');
            $.post(BASE_URL + 'payment/getAsmByStateAjax', { state_id: stateId }, function(res) {
                $('#asm_id').html(res).trigger('change');
            });
        } else {
            $('#asm_id').html('<option value="">-- Select State First --</option>').trigger('change');
        }
    });

    // 2. Load Headquarters and Stockists based on ASM Selection
    $('#asm_id').change(function() {
        let asmId = $(this).val();
        
        if (asmId) {
            $('#filter_hq_id').html('<option value="">Loading...</option>');
            $('#stockist_id').html('<option value="">Loading...</option>');

            // Fetch Headquarters associated with this ASM
            $.get(BASE_URL + 'payment/get_hqs_by_asm', { asm_id: asmId }, function(res) {
                if (res.success) {
                    let hqOptions = '<option value="">-- All Headquarters --</option>';
                    res.data.forEach(hq => { hqOptions += `<option value="${hq.headquarter_id}">${hq.hq_name}</option>`; });
                    $('#filter_hq_id').html(hqOptions);
                } else {
                    $('#filter_hq_id').html('<option value="">No Headquarters found</option>');
                }
            }, 'json');

            // Fetch ALL Stockists for this ASM initially
            fetchStockists(asmId, null);

        } else {
            $('#filter_hq_id').html('<option value="">-- Select ASM First --</option>');
            $('#stockist_id').html('<option value="">-- Select ASM First --</option>');
        }
    });

    // 3. Filter Stockists when a specific Headquarter is selected
    $('#filter_hq_id').change(function() {
        let asmId = $('#asm_id').val();
        let specificHqId = $(this).val();
        fetchStockists(asmId, specificHqId);
    });

    function fetchStockists(asmId, specificHqId) {
        $('#stockist_id').html('<option value="">Loading...</option>');
        
        // Dynamic endpoint mapping depending on whether a specific HQ is chosen or all under the ASM
        let url = specificHqId ? (BASE_URL + 'payment/get_stockists_by_hq') : (BASE_URL + 'payment/get_stockists_by_hq');
        let payload = specificHqId ? { hq_id: specificHqId } : { asm_id: asmId }; // Ensure backend endpoints match these keys

        $.get(url, payload, function(res) {
            if (res.success) {
                let stkOptions = '<option value="">-- Select Stockist --</option>';
                res.data.forEach(stk => { stkOptions += `<option value="${stk.stockist_id}">${stk.stockist_name}</option>`; });
                $('#stockist_id').html(stkOptions);
            } else {
                $('#stockist_id').html('<option value="">No Stockists found</option>');
            }
        }, 'json');
    }

    // 4. Toggle Stockist Container based on Payment Type
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

    // Fetch Balance when Commission Type or ASM changes
    $('#commission_type, #asm_id').change(function() {
        let type = $('#commission_type').val();
        let asmId = $('#asm_id').val(); // hq_id dropdown holds ASM ID
        
        if(editData !== null) return; 

        if(type && asmId) {
            $('#balanceDisplay').html('<i class="fa fa-spinner fa-spin"></i> Fetching balance...');
            
            $.get(BASE_URL + 'payment/get_balance', { hq_id: asmId, type: type }, function(res) {
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

    // 5. Handle Form Submission
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

    // 6. Reset Form Logic
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
        // 1. Pre-fill basic fields
        $('#amount').val(editData.amount_paid).prop('readonly', true);
        
        let commTypeStr = editData.commission_type ? editData.commission_type.toUpperCase() : 'ASM';
        $('#commission_type').val(commTypeStr).prop('disabled', true);
        
        $('#notes').val(editData.bank_details).prop('readonly', true);
        
        let paymentAction = editData.payment_method === 'Commission Adjustment' ? 'old_bill' : 'account';
        $('#payment_type').val(paymentAction).prop('disabled', true).trigger('change');

        // Lock all dropdowns immediately
        $('#state_id, #asm_id, #filter_hq_id, #stockist_id, #settlement_date').prop('disabled', true);

        // 2. Force the ASM ID (Stored in mr_id column)
        let asmId = editData.mr_id; // ASM ID
        if (asmId && asmId != 0) {
            let asmName = editData.asm_name || ('Linked ASM'); 
            if ($('#asm_id').find("option[value='" + asmId + "']").length === 0) {
                $('#asm_id').append(new Option(asmName, asmId, true, true));
            }
            $('#asm_id').val(asmId).trigger('change');
        }

        // 3. Force the Headquarter ID (Retrieved from Stockist)
        setTimeout(() => {
            if (editData.specific_hq_id && editData.specific_hq_id != 0) {
                let hqName = editData.headquarter_name || 'Linked Headquarter';
                if ($('#filter_hq_id').find("option[value='" + editData.specific_hq_id + "']").length === 0) {
                    $('#filter_hq_id').append(new Option(hqName, editData.specific_hq_id, true, true));
                }
                $('#filter_hq_id').val(editData.specific_hq_id).trigger('change');
            }
        }, 300);

        // 4. Force the Stockist ID
        setTimeout(() => {
            if (editData.stockist_id && editData.stockist_id != 0) {
                let stockistName = editData.stockist_name || ('Linked Stockist');
                if ($('#stockist_id').find("option[value='" + editData.stockist_id + "']").length === 0) {
                    $('#stockist_id').append(new Option(stockistName, editData.stockist_id, true, true));
                }
                $('#stockist_id').val(editData.stockist_id).trigger('change');
            }
        }, 600); 

        // Hide normal buttons and inject the Reverse button
        $('#btnSubmitPayment, #btnReset').hide();
        
        if (editData.approval_status !== 'reversed') {
            $('#btnSubmitPayment').parent().append(`<button type="button" class="btn btn-danger fw-bold" id="btnReversePayment"><i class="fa fa-undo"></i> Reverse Payment</button>`);
        } else {
            $('#btnSubmitPayment').parent().append(`<span class="badge bg-danger p-2 fs-6"><i class="fa fa-ban"></i> Already Reversed</span>`);
        }

        // Fetch and display historical allocations if it was an old bill settlement
        if (paymentAction === 'old_bill') {
            $('#allocatedBillsContainer').fadeIn();
            $('#allocatedBillsBody').html('<tr><td colspan="5" class="text-center py-3"><i class="fa fa-spinner fa-spin"></i> Fetching allocation history...</td></tr>');
            
            $.get(BASE_URL + 'payment/get_payment_allocations', { payment_id: editData.id }, function(res) {
                if (res.success && res.data.length > 0) {
                    let rows = '';
                    res.data.forEach(b => {
                        let allocated = parseFloat(b.amount_allocated) || 0;
                        let cdEarned = parseFloat(b.cd_earned) || 0;
                        let cdRevoked = parseFloat(b.cd_revoked) || 0;
                        
                        let grandTotal = parseFloat(b.grand_total) || 0;
                        let currentPaid = parseFloat(b.paid_amt) || 0;
                        
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
                    $('#allocatedBillsBody').html('<tr><td colspan="5" class="text-center text-muted">No specific bill allocations found.</td></tr>');
                }
            }, 'json').fail(function() {
                $('#allocatedBillsBody').html('<tr><td colspan="5" class="text-center text-danger">Failed to load allocation details.</td></tr>');
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
                setTimeout(() => { window.location.href = BASE_URL + 'payment/asm_payment_list'; }, 2000); 
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