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
            <!-- Ensure overflow is visible so the dropdown menu doesn't get cut off -->
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

<script>
    const BASE_URL = "<?= BASE_URL ?>";
</script>

<?php include 'view/layout/footer.php'; ?>

<!-- Javascript Logic -->
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
        setTimeout(() => { $('.alert-success').fadeOut('slow'); }, 5000);
    }

    function loadPayments() {
        // Destroy existing DataTable before modifying HTML
        if ($.fn.DataTable.isDataTable('#approvalTableElement')) {
            $('#approvalTableElement').DataTable().destroy();
        }

        approvalTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Loading records...</td></tr>';

        // Build URL parameters for the GET request
        const params = new URLSearchParams({
            status: statusFilter.value
        });

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
                    
                    // Status Badge Styling
                    let statusClass = 'bg-warning text-dark';
                    let displayStatus = 'Pending';
                    if (p.approval_status === 'approved') { statusClass = 'bg-success'; displayStatus = 'Approved'; }
                    if (p.approval_status === 'rejected') { statusClass = 'bg-danger'; displayStatus = 'Rejected'; }

                    // ---> UPDATED PROOF BUTTON LOGIC <---
                    let proofHTML = p.screenshot_path 
                                ? `<a href="${BASE_URL}../${p.screenshot_path}" target="_blank" class="btn btn-sm btn-info text-white"><i class="fa fa-image"></i> View</a>` 
                                : '<span class="text-muted small">N/A</span>';
                    
                    // Dropdown Actions (Only available if Pending)
                    let actions = '<span class="text-muted small">Processed</span>';
                    if (p.approval_status === 'pending') {
                        actions = `
                            <div class="dropdown">
                                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="actionBtn${p.id}" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa fa-cog"></i> Actions
                                </button>
                                <ul class="dropdown-menu shadow" aria-labelledby="actionBtn${p.id}">
                                    <li>
                                        <a class="dropdown-item text-success action-btn" href="#" data-id="${p.id}" data-act="approved" title="Approve Payment">
                                            <i class="fa fa-check-circle me-2 pointer-events-none"></i> Approve
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger action-btn" href="#" data-id="${p.id}" data-act="rejected" title="Reject Payment">
                                            <i class="fa fa-times-circle me-2 pointer-events-none"></i> Reject
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        `;
                    }

                    let rowHtml = `
                        <tr>
                            <td class="text-center">${count++}</td>
                            <td class="text-center">${p.created_at.split(' ')[0]}</td>
                            <td>${p.stockist_name || '<span class="text-muted">Unknown</span>'}</td>
                            <td class="text-end fw-bold text-success">₹${parseFloat(p.amount_paid).toFixed(2)}</td>
                            <td>
                                ${p.payment_method}<br>
                                <small class="text-muted">${p.bank_details || 'No Ref Provided'}</small>
                            </td>
                            <td class="text-center">${proofHTML}</td>
                            <td class="text-center"><span class="badge ${statusClass}">${displayStatus}</span></td>
                            <td class="text-center">${actions}</td>
                        </tr>
                    `;
                    return rowHtml;
                }).join('');

                paymentCount.textContent = res.data.length;

                // Re-initialize DataTable after data is populated
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

    // Handle Approve/Reject Clicks
    approvalTableBody.addEventListener('click', function(e) {
        let btn = e.target.closest('.action-btn');
        if (!btn) return;

        e.preventDefault();

        let paymentId = btn.dataset.id;
        let action = btn.dataset.act; 
        let actionText = action === 'approved' ? 'Approve' : 'Reject';

        if (!confirm(`Are you sure you want to ${actionText} this payment?`)) return;

        let formData = new FormData();
        formData.append('payment_id', paymentId);
        formData.append('action', action);

        fetch(BASE_URL + 'payment/process', {
            method: 'POST',
            body: formData
        })
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
            console.error(err);
            showAlert("Failed to process request.", 'danger');
        });
    });

    // Filter Buttons
    btnSearch.addEventListener('click', loadPayments);
    btnReset.addEventListener('click', () => {
        statusFilter.value = 'pending';
        loadPayments();
    });

    // Initial load
    loadPayments();


     // Load Headquarter Function
    function loadHeadquarters(state_id, hq_id = '') {
        $('#hq').html('<option value="">Loading...</option>'); // Show loading status

        $.ajax({
            url: '<?= BASE_URL ?>headquarter/getHqByStateAjax',
            type: 'POST',
            data: {
                state_id: state_id,
                selected_hq: hq_id
            },
            success: function(response) {
                $('#hq').html(response);
                if ($('#hq').hasClass('select2-hidden-accessible')) {
                    $('#hq').select2('destroy'); 
                }
                $('#hq').select2({ width: '100%' }); 
            }
        });
    }

    // 1. ON PAGE LOAD: Check if state was already selected via URL parameters
    let selectedStateId = "<?= $_GET['state_id'] ?? '' ?>";
    let selectedHqId = "<?= $_GET['hq_id'] ?? '' ?>";
    
    if (selectedStateId) {
        // Automatically trigger AJAX to populate the HQ dropdown and retain the selection
        loadHeadquarters(selectedStateId, selectedHqId);
    }

    // 2. ON CHANGE: When user manually changes the state
    $('#state_id').change(function() {
        let stateId = $(this).val();
        if (stateId) {
            loadHeadquarters(stateId, '');
        } else {
            $('#hq').html('<option value="">Select State First</option>');
        }
    });

});
</script>