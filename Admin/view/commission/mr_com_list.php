<?php 
$pageTitle = "MR Commission List";
include 'view/layout/header.php'; 
?>

<div id="container">
    <div class="detail">
        <a href="<?= BASE_URL ?>commision">
            <button type="button" class="btn btn-secondary btn-sm">Add Commission</button>
        </a>
    </div>
    <hr style="margin-top:10px; margin-bottom:10px; border-top:1px solid #333;">

    <h3>MR Commission List</h3>

    <!-- ================== SEARCH FILTERS ================== -->
    <div class="container border px-3 py-3 mb-4">
        <div class="row">
            <!-- State -->
            <div class="col-lg-3">
                <div class="form-group">
                    <label>Select State</label>
                    <select name="state" id="state_id" class="form-control select2">
                        <option value="">-- Select State --</option>
                        <?php while($srow = mysqli_fetch_assoc($states)): ?>
                            <option value="<?= htmlspecialchars($srow['state_id']); ?>">
                                <?= htmlspecialchars($srow['state_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
                
            <!-- HQ Name -->
            <div class="col-lg-4">
                <div class="form-group">
                    <label>Head Quarter</label>
                    <select name="hq" id="hq" class="form-control select2">
                        <option value="">-- Select State First --</option>
                    </select>
                </div>
            </div>
            
            <!-- Month Filter -->
            <div class="col-lg-3">
                <div class="form-group">
                    <label>Select Month</label>
                    <input type="month" id="filter_month" class="form-control">
                </div>
            </div>

            <!-- Search Button -->
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
                    <th>Date Paid</th>
                    <th>HQ Name</th>
                    <th>Total Payout (₹)</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody id="historyTableBody">
                <tr><td colspan="5" class="text-center text-muted">Select filters and click Get History</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php include 'view/layout/footer.php'; ?>

<script>
$(document).ready(function() {
    // 1. Configure the Month Filter Default
    let currentDate = new Date();
    let currentYear = currentDate.getFullYear();
    let currentMonth = String(currentDate.getMonth() + 1).padStart(2, '0');
    $('#filter_month').val(`${currentYear}-${currentMonth}`);
    
    // Initialize Select2
    $('.select2').select2({ theme: 'bootstrap-5' });

    // 2. Load HQ when State Changes
    $('#state_id').change(function() {
        let stateId = $(this).val();
        $.ajax({
            url: '<?= BASE_URL ?>headquarter/getHqByStateAjax',
            type: 'POST',
            data: { state_id: stateId },
            success: function(response) {
                $('#hq').html(response).trigger('change');
            }
        });
    });

    // 3. Fetch Payout History
    $('#btnFetchHistory').click(function() {
        let hqId = $('#hq').val();
        let filterMonth = $('#filter_month').val();

        if (!hqId) {
            alert('Please select a Head Quarter first.');
            return;
        }

        let tbody = $('#historyTableBody');
        tbody.html('<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');

        $.ajax({
            url: '<?= BASE_URL ?>commision/get_mrc_history',
            type: 'GET',
            data: { hqId: hqId, month: filterMonth },
            success: function(res) {
                if(res.success && res.data.length > 0) {
                    let rows = '';
                    res.data.forEach(payout => {
                        // Redirects directly to the edit page with payout_id and hq_id in the URL
                        let editUrl = `<?= BASE_URL ?>commision/edit/${payout.payout_id}`;
                        
                        rows += `
                            <tr>
                                <td>#${payout.payout_id}</td>
                                <td>${payout.date_paid}</td>
                                <td>${payout.hq_name}</td>
                                <td style="color: green; font-weight: bold;">₹${parseFloat(payout.total_payout).toFixed(2)}</td>
                                <td class="text-center">
                                    <a href="${editUrl}" class="btn btn-sm btn-warning text-dark fw-bold">
                                        <i class="fa fa-edit"></i> Edit / View
                                    </a>
                                </td>
                            </tr>
                        `;
                    });
                    tbody.html(rows);
                } else {
                    tbody.html(`<tr><td colspan="5" class="text-center text-danger">${res.msg || "No payouts found for this month."}</td></tr>`);
                }   
            },
            error: function() {
                tbody.html('<tr><td colspan="5" class="text-center text-danger">Server Error. Could not fetch history.</td></tr>');
            }
        });
    });
});
</script>