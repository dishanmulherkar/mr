<?php 
// Ensure session is started (if not already handled globally)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "DR Commission List";
include 'view/layout/header.php'; 

// Fetch existing session values
$sess_state = $_SESSION['filter_state'] ?? '';
$sess_hq    = $_SESSION['filter_hq'] ?? '';
$sess_month = $_SESSION['filter_month'] ?? '';
?>

<div id="container">
    <div class="detail">
        <a href="<?= BASE_URL ?>drccommision">
            <button type="button" class="btn btn-secondary btn-sm">Add Dr Commission</button>
        </a>
    </div>
    <hr style="margin-top:10px; margin-bottom:10px; border-top:1px solid #333;">

    <h3>DR Commission List</h3>

    <!-- ================== SEARCH FILTERS ================== -->
    <div class="container border px-3 py-3 mb-4">
        <div class="row">
            <div class="col-lg-3">
                <div class="form-group">
                    <label>Select State</label>
                    <select name="state" id="state_id" class="form-control select2">
                        <option value="">-- Select State --</option>
                        <?php while($srow = mysqli_fetch_assoc($states)): ?>
                            <option value="<?= htmlspecialchars($srow['state_id']); ?>" 
                                <?= ($sess_state == $srow['state_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($srow['state_name']); ?>
                            </option>
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
            
            <div class="col-lg-3">
                <div class="form-group">
                    <label>Select Month</label>
                    <input type="month" id="filter_month" class="form-control">
                </div>
            </div>

            <div class="col-lg-2 d-flex align-items-end">
                <button id="btnFetchHistory" class="btn btn-primary w-100"><i class="fa fa-search"></i> Get</button>
            </div>
        </div>
    </div>

    <!-- ================== HISTORY DATA TABLE ================== -->
    <div class="commission-container mb-5">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Payout ID</th>
                    <th>Date</th>
                    <th>HQ Name</th>
                    <th>Status</th>
                    <th>Total Payout (₹)</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody id="historyTableBody">
                <tr><td colspan="6" class="text-center text-muted">Select filters and click Get History</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php include 'view/layout/footer.php'; ?>

<script>
$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap-5' });

    // Inject session variables into JS
    let sessState = "<?= $sess_state ?>";
    let sessHq    = "<?= $sess_hq ?>";
    let sessMonth = "<?= $sess_month ?>";
    let isInitialLoad = true;

    // Initialize Month Field
    if (sessMonth) {
        $('#filter_month').val(sessMonth);
    } else {
        let currentDate = new Date();
        let currentYear = currentDate.getFullYear();
        let currentMonth = String(currentDate.getMonth() + 1).padStart(2, '0');
        $('#filter_month').val(`${currentYear}-${currentMonth}`);
    }
    
    // Handle State Change to load HQs
    $('#state_id').change(function() {
        let stateId = $(this).val();
        
        if (!stateId) {
            $('#hq').html('<option value="">-- Select State First --</option>').trigger('change');
            return;
        }

        $.ajax({
            url: '<?= BASE_URL ?>headquarter/getHqByStateAjax',
            type: 'POST',
            data: { state_id: stateId },
            success: function(response) {
                $('#hq').html(response);

                // Auto-select HQ if restoring from session on page load
                if (isInitialLoad && sessHq) {
                    $('#hq').val(sessHq);
                }
                $('#hq').trigger('change');

                // Auto-fetch data if everything was present in session
                if (isInitialLoad && sessHq && sessState) {
                    $('#btnFetchHistory').trigger('click');
                }
                
                isInitialLoad = false; // Turn off initial load logic for future changes
            }
        });
    });

    // Trigger state change immediately if a state is set in session
    if (sessState) {
        $('#state_id').trigger('change');
    }

    // Fetch History Button Click
    $('#btnFetchHistory').click(function() {
        let stateId = $('#state_id').val();
        let hqId = $('#hq').val();
        let filterMonth = $('#filter_month').val();

        if (!hqId) {
            alert('Please select a Head Quarter first.');
            return;
        }

        // 1. Save current filters to session via AJAX
        $.post('<?= BASE_URL ?>drccommision/save_filters_session', {
            state_id: stateId,
            hq_id: hqId,
            month: filterMonth
        });

        // 2. Fetch the Data
        let tbody = $('#historyTableBody');
        tbody.html('<tr><td colspan="6" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');

        $.ajax({
            url: '<?= BASE_URL ?>drccommision/get_drc_history',
            type: 'GET',
            data: { hqId: hqId, month: filterMonth },
            success: function(res) {
                if(res.success && res.data.length > 0) {
                    let rows = '';
                    res.data.forEach(payout => {
                        let editUrl = `<?= BASE_URL ?>drccommision/edit/${payout.payout_id}`;
                        let statusBadge = payout.status === 'Paid' 
                            ? '<span class="badge bg-success">Paid</span>' 
                            : '<span class="badge bg-warning text-dark">Pending</span>';
                            
                        rows += `
                            <tr>
                                <td>#${payout.payout_id}</td>
                                <td>${payout.date_paid}</td>
                                <td>${payout.hq_name}</td>
                                <td>${statusBadge}</td>
                                <td style="color: green; font-weight: bold;">₹${parseFloat(payout.total_payout).toFixed(2)}</td>
                                <td class="text-center">
                                    <a href="${editUrl}" class="btn btn-sm btn-primary fw-bold">
                                        <i class="fa fa-edit"></i> Edit / View
                                    </a>
                                    <button class="btn btn-sm btn-danger fw-bold btn-delete-payout" data-id="${payout.payout_id}">
                                        <i class="fa fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    tbody.html(rows);
                } else {
                    tbody.html(`<tr><td colspan="6" class="text-center text-danger">${res.msg || "No payouts found for this month."}</td></tr>`);
                }   
            },
            error: function() {
                tbody.html('<tr><td colspan="6" class="text-center text-danger">Server Error. Could not fetch history.</td></tr>');
            }
        });
    });

    // Handle Delete Button Click
    $(document).on('click', '.btn-delete-payout', function() {
        let payoutId = $(this).data('id');
        
        if(confirm(`Are you sure you want to delete Payout #${payoutId}? All linked bills will be reset so they can be claimed again.`)) {
            let btn = $(this);
            btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);
            
            $.post('<?= BASE_URL ?>drccommision/delete_drc', { payout_id: payoutId }, function(res) {
                if(res.success) {
                    alert('Commission deleted successfully.');
                    $('#btnFetchHistory').trigger('click'); // Reload the table
                } else {
                    alert('Error: ' + res.msg);
                    btn.html('<i class="fa fa-trash"></i> Delete').prop('disabled', false);
                }
            }).fail(function() {
                alert('Server error occurred during deletion.');
                btn.html('<i class="fa fa-trash"></i> Delete').prop('disabled', false);
            });
        }
    });
});
</script>