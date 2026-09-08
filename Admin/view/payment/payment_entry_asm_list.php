<?php 
$pageTitle = "ASM Settlement History";
include 'view/layout/header.php'; 
?>

<div id="container" class="container-fluid mt-3 mb-5">
    
    <div id="alertContainer"></div>

    <!-- LIST SECTION WITH FILTERS -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-history"></i> ASM Payment & Settlement History</h5>
        </div>
        
        <!-- FILTER SECTION -->
        <div class="card-body border-bottom bg-light">
            <div class="row g-3 align-items-end">
                
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Start Date</label>
                    <input type="date" id="filter_start_date" class="form-control form-control-sm">
                </div>
                
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">End Date</label>
                    <input type="date" id="filter_end_date" class="form-control form-control-sm">
                </div>

                <div class="col-md-3">
                    <label class="small fw-bold text-muted">State</label>
                    <select id="filter_state_id" class="form-select form-select-sm select2">
                        <option value="">All States</option>
                        <?php mysqli_data_seek($states, 0); while($srow = mysqli_fetch_assoc($states)): ?>
                            <option value="<?= htmlspecialchars($srow['state_id']); ?>"><?= htmlspecialchars($srow['state_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="small fw-bold text-muted">ASM</label>
                    <select id="filter_hq_id" class="form-select form-select-sm select2">
                        <option value=""></option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button class="btn btn-sm btn-primary w-100 mb-1" onclick="loadManualPayments()"><i class="fa fa-filter"></i> Filter</button>
                    <button class="btn btn-sm btn-secondary w-100" onclick="resetFilters()"><i class="fa fa-sync"></i> Reset</button>
                </div>
            </div>
        </div>

        <!-- TABLE SECTION -->
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle table-striped" id="manualPaymentTable">
                    <thead class="table-dark text-center align-middle">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 10%;">Date</th>
                            <th style="width: 10%;">Type</th>
                            <th style="width: 20%;">Stockist / Entity</th>
                            <th style="width: 15%;">Payment Action</th>
                            <th style="width: 15%;">Amount</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 10%;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="manualPaymentTableBody">
                        <!-- Javascript will populate this -->
                    </tbody>
                </table>
            </div>
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
        if(type === 'success') { setTimeout(() => { $('.alert-success').fadeOut('slow'); }, 5000); }
    }

    // Cascading dropdown for State -> ASM Filter
    $('#filter_state_id').change(function() {
        let stateId = $(this).val();
        if (stateId) {
            $('#filter_hq_id').html('<option value="">Loading...</option>');
            $.post(BASE_URL + 'payment/getAsmByStateAjax', { state_id: stateId }, function(res) {
                $('#filter_hq_id').html('<option value=""></option>' + res).trigger('change');
            });
        } else {
            $('#filter_hq_id').html('<option value=""></option>').trigger('change');
        }
    });

    window.resetFilters = function() {
        $('#filter_state_id').val('').trigger('change');
        $('#filter_hq_id').html('<option value=""></option>');
        $('#filter_start_date').val('');
        $('#filter_end_date').val('');
        loadManualPayments();
    }

    // Load Data into Table
    window.loadManualPayments = function() {
        const tableBody = document.getElementById('manualPaymentTableBody');
        if ($.fn.DataTable.isDataTable('#manualPaymentTable')) {
            $('#manualPaymentTable').DataTable().destroy();
        }
        tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>';

        // Gather all filter values (Hardcoding comm_type to 'asm')
        let params = new URLSearchParams({
            type: 'manual',
            comm_type: 'asm', 
            state_id: $('#filter_state_id').val(),
            hq_id: $('#filter_hq_id').val(),
            start_date: $('#filter_start_date').val(),
            end_date: $('#filter_end_date').val()
        });

        fetch(`${BASE_URL}payment/fetch_list_pay_entry?${params.toString()}`) 
            .then(res => res.json())
            .then(res => {
                if (!res.success || !res.data || !res.data.length) {
                    tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No entries found.</td></tr>';
                    return;
                }

                let count = 1;
                tableBody.innerHTML = res.data.map(p => {
                    let statusClass = p.approval_status === 'approved' ? 'bg-success' : (p.approval_status === 'reversed' ? 'bg-danger' : 'bg-warning text-dark');
                    let displayStatus = p.approval_status.charAt(0).toUpperCase() + p.approval_status.slice(1);
                    let paymentActionText = p.payment_method === 'Commission Adjustment' ? 'Settled Old Bill' : 'Bank Transfer';

                    return `
                        <tr>
                            <td class="text-center">${count++}</td>
                            <td class="text-center">${p.created_at ? p.created_at.split(' ')[0] : '-'}</td>
                            <td class="text-center"><span class="badge bg-secondary text-uppercase">${p.commission_type || '-'}</span></td>
                            <td>${p.stockist_name || 'N/A'}</td>
                            <td>${paymentActionText}</td>
                            <td class="text-end fw-bold text-success">₹${parseFloat(p.amount_paid).toFixed(2)}</td>
                            <td class="text-center"><span class="badge ${statusClass}">${displayStatus}</span></td>
                            <td class="text-center">
                                <!-- Pointing exactly to your asm_satlement page -->
                                <a href="${BASE_URL}payment/asm_satlement?edit_id=${p.id}" class="btn btn-sm btn-info text-white" title="View & Reverse">
                                    <i class="fa fa-eye"></i> View
                                </a>
                            </td>
                        </tr>`;
                }).join('');

                $('#manualPaymentTable').DataTable({ "order": [[ 0, "desc" ]], "pageLength": 25, "destroy": true });
            })
            .catch(() => {
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger"><i class="fa fa-exclamation-triangle"></i> Failed to load data.</td></tr>';
            });
    }

    // Load data initially
    loadManualPayments();
});
</script>