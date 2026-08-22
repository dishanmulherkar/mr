<?php 
$pageTitle = "Manual Payment History";
include 'view/layout/header.php'; 
?>

<div id="container" class="container-fluid mt-3 mb-5">
    
    <div id="alertContainer"></div>

    <!-- LIST SECTION WITH FILTERS -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-history"></i> Manual Payment History</h5>
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

                <div class="col-md-2">
                    <label class="small fw-bold text-muted">State</label>
                    <select id="filter_state_id" class="form-select form-select-sm select2">
                        <option value="">All States</option>
                        <?php mysqli_data_seek($states, 0); while($srow = mysqli_fetch_assoc($states)): ?>
                            <option value="<?= htmlspecialchars($srow['state_id']); ?>"><?= htmlspecialchars($srow['state_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="small fw-bold text-muted">HQ</label>
                    <select id="filter_hq_id" class="form-select form-select-sm select2">
                        <option value="">All HQs</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Commission</label>
                    <select id="filter_comm_type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="mrc">MRC</option>
                        <option value="drc">DRC</option>
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
                    <thead class="table-dark text-center">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 10%;">Date</th>
                            <th style="width: 10%;">Type</th>
                            <th style="width: 20%;">Stockist Name</th>
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

<!-- EDIT MODAL -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-dark"><i class="fa fa-edit"></i> Edit Manual Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPaymentForm">
                <div class="modal-body">
                    <input type="hidden" name="payment_id" id="edit_payment_id">
                    
                    <div class="mb-3">
                        <label class="fw-bold">Commission Type</label>
                        <select name="commission_type" id="edit_commission_type" class="form-control" required>
                            <option value="mrc">MR Commission (MRC)</option>
                            <option value="drc">Dr Commission (DRC)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="fw-bold">Amount (₹)</label>
                        <input type="number" name="amount" id="edit_amount" class="form-control" step="0.01" required>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold">Notes / Reference</label>
                        <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning fw-bold" id="btnUpdatePayment">Update Entry</button>
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

    // Cascading dropdown for State -> HQ Filter
    $('#filter_state_id').change(function() {
        let stateId = $(this).val();
        if (stateId) {
            $('#filter_hq_id').html('<option value="">Loading...</option>');
            $.post(BASE_URL + 'headquarter/getHqByStateAjax', { state_id: stateId }, function(res) {
                $('#filter_hq_id').html('<option value="">All HQs</option>' + res).trigger('change');
            });
        } else {
            $('#filter_hq_id').html('<option value="">All HQs</option>').trigger('change');
        }
    });

    window.resetFilters = function() {
        $('#filter_state_id').val('').trigger('change');
        $('#filter_hq_id').html('<option value="">All HQs</option>');
        $('#filter_comm_type').val('');
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

        // Gather all filter values
        let params = new URLSearchParams({
            type: 'manual',
            state_id: $('#filter_state_id').val(),
            hq_id: $('#filter_hq_id').val(),
            comm_type: $('#filter_comm_type').val(),
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
                    let statusClass = p.approval_status === 'approved' ? 'bg-success' : (p.approval_status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark');
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
                                    <a href="${BASE_URL}payment/entry?edit_id=${p.id}" class="btn btn-sm btn-info text-white" title="View & Reverse">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                </td>
                        </tr>`;
                }).join('');

                $('#manualPaymentTable').DataTable({ "order": [[ 0, "desc" ]], "pageLength": 25, "destroy": true });
            });
    }

    // Edit Modal Trigger
    $(document).on('click', '.edit-btn', function() {
        $('#edit_payment_id').val($(this).data('id'));
        $('#edit_commission_type').val($(this).data('comm').toLowerCase());
        $('#edit_amount').val($(this).data('amount'));
        $('#edit_notes').val($(this).data('notes'));
        
        let editModal = new bootstrap.Modal(document.getElementById('editPaymentModal'));
        editModal.show();
    });

    // Handle Edit Form Submission
    $('#editPaymentForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnUpdatePayment');
        btn.html('<i class="fa fa-spinner fa-spin"></i> Updating...').prop('disabled', true);

        $.post(BASE_URL + 'payment/update_manual_entry', $(this).serialize(), function(res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('editPaymentModal')).hide();
                showAlert(res.msg, 'success');
                loadManualPayments();
            } else {
                alert(res.msg);
            }
        }, 'json').always(() => { btn.html('Update Entry').prop('disabled', false); });
    });

    // Load data initially
    loadManualPayments();
});
</script>