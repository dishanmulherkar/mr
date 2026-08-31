<?php 
$pageTitle = "Payment Approvals";
include 'view/layout/header.php'; 
?>

<div id="container" class="container-fluid mt-3">
    
    <!-- Dynamic Alert Container for JS Responses -->
    <div id="alertContainer"></div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-check-circle"></i> Payment Approvals</h5>
        </div>
        
        <!-- FILTER SECTION -->
        <div class="card shadow-sm border-0 mb-3 border-bottom">
            <div class="card-body">
                <div class="row g-3 align-items-end">

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
                        <label>Head Quarter</label>
                        <select name="hq" id="hq" class="form-control select2">
                            <option value="">-- Select State First --</option>
                        </select>
                    </div>
                </div>
                    
                    <!-- 1. Status Dropdown -->
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Approval Status</label>
                        <select id="statusFilter" class="form-select form-select-sm">
                            <option value="pending" selected>Pending Only</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="">All Payments</option>
                        </select>
                    </div>

                    <!-- 2. Action Buttons -->
                    <div class="col-md-3">
                        <button type="button" id="btnSearch" class="btn btn-primary btn-sm me-2">
                            <i class="fa fa-filter"></i> Filter
                        </button>
                        <button type="button" id="btnReset" class="btn btn-secondary btn-sm">
                            <i class="fa fa-sync"></i> Reset
                        </button>
                    </div>
                    
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive" style="overflow-x: visible !important;">
                <table class="table table-bordered table-hover align-middle table-striped" id="approvalTableElement">
                    <thead class="table-dark text-center">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 10%;">Date</th>
                            <th style="width: 20%;">Stockist</th>
                            <th style="width: 15%;">Amount</th>
                            <th style="width: 20%;">Method & Ref</th>
                            <th style="width: 10%;">Screenshot</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 10%;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="approvalTableBody">
                        <!-- Javascript will populate this -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card-footer text-muted small">
            Total Records: <span id="paymentCount" class="fw-bold text-primary">0</span>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- REVIEW & APPROVE MODAL                     -->
<!-- ========================================== -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fa fa-search"></i> Review Payment Allocation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    
                    <!-- Left Column: Payment Proof -->
                    <div class="col-md-4 border-end">
                        <h6 class="text-primary fw-bold mb-3">Payment Details</h6>
                        <table class="table table-sm table-borderless">
                            <tr><th style="width: 40%">Stockist:</th><td id="revStockist" class="fw-bold"></td></tr>
                            <tr><th>Amount Paid:</th><td id="revAmount" class="text-success fw-bold fs-5"></td></tr>
                            <tr><th>Method:</th><td id="revMethod"></td></tr>
                            <tr><th>Reference:</th><td id="revRef"></td></tr>
                        </table>
                        <hr>
                        <h6 class="text-muted small fw-bold">Payment Proof</h6>
                        <div id="revImg" class="text-center mt-2"></div>
                    </div>

                    <!-- Right Column: Bill Revisions & Allocation -->
                    <div class="col-md-8">
                        <h6 class="text-primary fw-bold mb-3">Pending Bills & Automatic Revisions</h6>
                        <div class="alert alert-info small py-2 mb-2">
                            <i class="fa fa-info-circle"></i> If a bill's 4% CD time limit has expired, the system will automatically revoke the CD and revise the grand total upon approval.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped" style="font-size: 0.85rem;">
                                <thead class="table-secondary text-center">
                                    <tr>
                                        <th>Invoice No</th>
                                        <th>Date</th>
                                        <th>Original Balance</th>
                                        <th>CD Adjustments</th>
                                        <th>Revised Payable</th>
                                    </tr>
                                </thead>
                                <tbody id="revBillsTable">
                                    <!-- Populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between">
                <input type="hidden" id="revPaymentId">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmApproveBtn" class="btn btn-success fw-bold px-4">
                    <i class="fa fa-check-circle"></i> Confirm & Approve Payment
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = "<?= BASE_URL ?>";
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
<?php include 'view/layout/footer.php'; ?>

<!-- Javascript Logic -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const approvalTableBody = document.getElementById('approvalTableBody');
    const statusFilter = document.getElementById('statusFilter');
    const btnSearch = document.getElementById('btnSearch');
    const btnReset = document.getElementById('btnReset');
    const paymentCount = document.getElementById('paymentCount');
    const alertContainer = document.getElementById('alertContainer');

    function showAlert(msg, type = 'success') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fa ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${msg}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
        alertContainer.innerHTML = alertHtml;
        window.scrollTo(0, 0);
        setTimeout(() => { $('.alert-success').fadeOut('slow'); }, 5000);
    }

    function loadPayments() {
        if ($.fn.DataTable.isDataTable('#approvalTableElement')) {
            $('#approvalTableElement').DataTable().destroy();
        }

        approvalTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Loading records...</td></tr>';

        const params = new URLSearchParams({ status: statusFilter.value });

        fetch(BASE_URL + 'payment/fetch_list?' + params.toString())
            .then(res => res.json())
            .then(res => {
                if (!res.success || !res.data.length) {
                    approvalTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No records found.</td></tr>';
                    paymentCount.textContent = 0;
                    return;
                }

                let count = 1;
                approvalTableBody.innerHTML = res.data.map(p => {
                    let statusClass = 'bg-warning text-dark';
                    let displayStatus = 'Pending';
                    if (p.approval_status === 'approved') { statusClass = 'bg-success'; displayStatus = 'Approved'; }
                    if (p.approval_status === 'rejected') { statusClass = 'bg-danger'; displayStatus = 'Rejected'; }

                    let proofHTML = p.screenshot_path 
                                ? `<a href="${BASE_URL}../${p.screenshot_path}" target="_blank" class="btn btn-sm btn-info text-white"><i class="fa fa-image"></i> View</a>` 
                                : '<span class="text-muted small">N/A</span>';
                    
                    let actions = '<span class="text-muted small">Processed</span>';
                    if (p.approval_status === 'pending') {
                        actions = `
                            <div class="dropdown">
                                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fa fa-cog"></i> Actions
                                </button>
                                <ul class="dropdown-menu shadow">
                                    <li>
                                        <!-- Opens the Review Modal -->
                                        <a class="dropdown-item text-primary btn-review" href="#" 
                                            data-id="${p.id}" 
                                            data-stockist="${p.stockist_id}" 
                                            data-name="${p.stockist_name}"
                                            data-amount="${p.amount_paid}"
                                            data-method="${p.payment_method}"
                                            data-ref="${p.bank_details}"
                                            data-img="${p.screenshot_path}">
                                            <i class="fa fa-search me-2 pointer-events-none"></i> Review & Approve
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <!-- Direct Reject Action -->
                                        <a class="dropdown-item text-danger action-btn" href="#" data-id="${p.id}" data-act="rejected">
                                            <i class="fa fa-times-circle me-2 pointer-events-none"></i> Reject
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        `;
                    }

                    return `
                        <tr>
                            <td class="text-center">${count++}</td>
                            <td class="text-center">${p.created_at.split(' ')[0]}</td>
                            <td>${p.stockist_name || '<span class="text-muted">Unknown</span>'}</td>
                            <td class="text-end fw-bold text-success">₹${parseFloat(p.amount_paid).toFixed(2)}</td>
                            <td>${p.payment_method}<br><small class="text-muted">${p.bank_details || 'No Ref Provided'}</small></td>
                            <td class="text-center">${proofHTML}</td>
                            <td class="text-center"><span class="badge ${statusClass}">${displayStatus}</span></td>
                            <td class="text-center">${actions}</td>
                        </tr>
                    `;
                }).join('');

                paymentCount.textContent = res.data.length;

                if ($.fn.DataTable) {
                    $('#approvalTableElement').DataTable({
                        "order": [[ 0, "asc" ]], 
                        "pageLength": 25,
                        "destroy": true
                    });
                }
            })
            .catch(() => {
                approvalTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Failed to load data.</td></tr>';
            });
    }

    // ==========================================
    // 1. OPEN REVIEW MODAL & FETCH BILLS
    // ==========================================
    $(document).on('click', '.btn-review', function(e) {
        e.preventDefault();
        let btn = $(this);
        
        $('#revPaymentId').val(btn.data('id'));
        $('#revStockist').text(btn.data('name'));
        $('#revAmount').text('₹' + parseFloat(btn.data('amount')).toFixed(2));
        $('#revMethod').text(btn.data('method'));
        $('#revRef').text(btn.data('ref') || 'N/A');
        
        if (btn.data('img') && btn.data('img') !== 'null') {
            $('#revImg').html(`<img src="${BASE_URL}../${btn.data('img')}" class="img-fluid rounded border" style="max-width:100%; max-height:400px;">`);
        } else {
            $('#revImg').html('<div class="alert alert-secondary">No Proof Uploaded</div>');
        }

        $('#reviewModal').modal('show');
        $('#revBillsTable').html('<tr><td colspan="5" class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><br>Fetching stockist ledger...</td></tr>');
        
        // Fetch bills logic synchronized exactly with the MR Make Payment View
        $.get(BASE_URL + 'payment/get_outstanding', { stockist_id: btn.data('stockist') }, function(res) {
            if (res.success && res.bills && res.bills.length > 0) {
                let html = '';
                res.bills.forEach(b => {
                    let pending = parseFloat(b.pending_amount) || 0;
                    let sub_total = parseFloat(b.sub_total) || 0;
                    
                    let existingCdPercent = parseFloat(b.cd_percent) || 0;
                    
                    // Calculate newly applied CD
                    let eligible4 = parseFloat(b.eligible_4_cd) || 0;
                    let eligible2 = parseFloat(b.eligible_2_cd) || 0;
                    let newCdAmount = eligible4 + eligible2; 
                    
                    // Check for late payment penalties
                    let penaltyAmount = parseFloat(b.penalty_amount) || 0;
                    
                    // Calculate previously given CD
                    let already4 = parseFloat(b.already_4_cd) || 0;
                    let already2 = parseFloat(b.already_2_cd) || 0;
                    let alreadyCdAmount = already4 + already2; 
                    
                    // Net Payable = Pending Balance - New Discounts + Late Penalties
                    let net = pending - newCdAmount + penaltyAmount;
                    
                    let cdBadge = '';
                    let cdAmountDisplay = '';

                    // 1. CD REVOKED (Late Payment Penalty)
                    if (penaltyAmount > 0) {
                        cdAmountDisplay = `<span class="text-danger fw-bold">+₹${penaltyAmount.toFixed(2)}</span>`;
                        
                        if (existingCdPercent == 4 && penaltyAmount < alreadyCdAmount) {
                            cdBadge = `<br><small class="badge bg-warning text-dark mt-1" style="font-size: 0.65em;">Downgraded to 2% CD (Late)</small>`;
                        } else {
                            cdBadge = `<br><small class="badge bg-danger text-white mt-1" style="font-size: 0.65em;">${existingCdPercent}% CD Revoked (Late)</small>`;
                        }

                    // 2. NEW CD APPLIED
                    } else if (newCdAmount > 0) {
                        let cdAppliedPercent = (eligible4 > 0) ? 4 : 2;
                        cdAmountDisplay = `<span class="text-success fw-bold">-₹${newCdAmount.toFixed(2)}</span>`;
                        cdBadge = `<br><small class="badge bg-success text-white mt-1" style="font-size: 0.65em;">${cdAppliedPercent}% CD Applied</small>`;
                    
                    // 3. CD ALREADY GIVEN (Still valid, paid on time)
                    } else if (existingCdPercent > 0) {
                        cdAmountDisplay = `<span class="text-muted" style="font-style: italic;">Included (-₹${alreadyCdAmount.toFixed(2)})</span>`;
                        cdBadge = `<br><small class="badge bg-secondary text-white mt-1" style="font-size: 0.65em;">${existingCdPercent}% CD Already Given</small>`;
                    
                    // 4. NO CD 
                    } else {
                        cdAmountDisplay = `<span class="text-muted">-₹0.00</span>`;
                    }

                    // Combine for output
                    let cdStatusHtml = cdAmountDisplay + cdBadge;

                    let dateParts = b.inward_date.split('-');
                    let shortDate = dateParts.length === 3 ? `${dateParts[2]}/${dateParts[1]}/${dateParts[0].substring(2)}` : b.inward_date;

                    html += `
                        <tr>
                            <td class="fw-bold">${b.inward_no}</td>
                            <td class="text-center">${shortDate}</td>
                            <td class="text-end">₹${pending.toFixed(2)}</td>
                            <td class="text-end">${cdStatusHtml}</td>
                            <td class="text-end fw-bold text-primary">₹${Math.round(net).toFixed(2)}</td>
                        </tr>
                    `;
                });
                
                let footTotalCd = parseFloat(res.eligible_cd) || 0;
                let footTotalPen = parseFloat(res.total_penalty) || 0;
                let footCdHtml = `<span class="text-success">-₹${footTotalCd.toFixed(2)}</span>`;
                if (footTotalPen > 0) {
                    footCdHtml += `<br><span class="text-danger fw-bold">+₹${footTotalPen.toFixed(2)} (Penalties)</span>`;
                }

                html += `
                    <tr class="table-dark">
                        <td colspan="3" class="text-end">FINAL ADJUSTED TOTALS:</td>
                        <td class="text-end">${footCdHtml}</td>
                        <td class="text-end fs-6 text-warning">₹${Math.round(res.net_payable).toFixed(2)}</td>
                    </tr>
                `;
                $('#revBillsTable').html(html);
            } else {
                $('#revBillsTable').html('<tr><td colspan="5" class="text-center text-success py-3"><i class="fa fa-check-circle"></i> No pending bills. Payment will be added as advance.</td></tr>');
            }
        }, 'json').fail(function() {
            $('#revBillsTable').html('<tr><td colspan="5" class="text-center text-danger">Failed to load bills.</td></tr>');
        });
    });

    // ==========================================
    // 2. PROCESS ACTION (APPROVE VIA MODAL OR REJECT VIA DROPDOWN)
    // ==========================================
    function processPaymentAction(paymentId, action) {
        let actionText = action === 'approved' ? 'Approve' : 'Reject';
        if (!confirm(`Are you sure you want to ${actionText} this payment?`)) return;

        let formData = new FormData();
        formData.append('payment_id', paymentId);
        formData.append('action', action);

        // Hide modal if open
        $('#reviewModal').modal('hide');

        fetch(BASE_URL + 'payment/process', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert(data.msg, 'success');
                loadPayments(); 
            } else {
                showAlert(data.msg, 'danger');
            }
        })
        .catch(err => {
            showAlert("Failed to process request.", 'danger');
        });
    }

    // Confirm Approve Button (Inside Modal)
    $('#confirmApproveBtn').click(function() {
        processPaymentAction($('#revPaymentId').val(), 'approved');
    });

    // Reject Button (Directly from table dropdown)
    $(document).on('click', '.action-btn', function(e) {
        e.preventDefault();
        processPaymentAction($(this).data('id'), $(this).data('act'));
    });

    // Filter Buttons & Dependencies
    btnSearch.addEventListener('click', loadPayments);
    btnReset.addEventListener('click', () => { statusFilter.value = 'pending'; loadPayments(); });
    loadPayments();

    function loadHeadquarters(state_id, hq_id = '') {
        $('#hq').html('<option value="">Loading...</option>'); 
        $.ajax({
            url: '<?= BASE_URL ?>headquarter/getHqByStateAjax',
            type: 'POST',
            data: { state_id: state_id, selected_hq: hq_id },
            success: function(response) {
                $('#hq').html(response);
                if ($('#hq').hasClass('select2-hidden-accessible')) { $('#hq').select2('destroy'); }
                $('#hq').select2({ width: '100%' }); 
            }
        });
    }

    let selectedStateId = "<?= $_GET['state_id'] ?? '' ?>";
    let selectedHqId = "<?= $_GET['hq_id'] ?? '' ?>";
    
    if (selectedStateId) loadHeadquarters(selectedStateId, selectedHqId);

    $('#state_id').change(function() {
        let stateId = $(this).val();
        if (stateId) loadHeadquarters(stateId, '');
        else $('#hq').html('<option value="">Select State First</option>');
    });
});
</script>