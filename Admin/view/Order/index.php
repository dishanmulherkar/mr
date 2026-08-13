<?php 
include 'view/layout/header.php'; 
?>

<div id="container" class="container-fluid mt-3">
    
   <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i> Order approved successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['update'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i> Order Updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['dispatch'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i> Order Dispatched successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle"></i> <?= $_GET['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-list"></i> Order List</h5>
        </div>
        
        <!-- FILTER SECTION -->
        <div class="card shadow-sm border-0 mb-3 border-bottom">
            <div class="card-body">
                <form method="GET" action="<?= BASE_URL ?>Order/index" class="row g-3 align-items-end">
                    
                    <!-- 1. State Dropdown -->
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">State</label>
                        <select name="state_id" id="state_id" class="form-select form-select-sm select2">
                            <option value="">Select State</option>
                            <?php foreach ($states as $srow): ?>
                                <option value="<?php echo htmlspecialchars($srow['state_id']); ?>"
                                    <?php if(isset($_GET['state_id']) && $_GET['state_id'] == $srow['state_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($srow['state_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 2. Headquarter Dropdown (name changed to hq_id) -->
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Headquarter</label>
                        <select name="hq_id" id="hq" class="form-select form-select-sm select2">
                           <option value="">Select State First</option>
                        </select>
                    </div>

                    <!-- 3. Order Date Filter -->
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Order Date</label>
                        <input type="date" name="order_date" class="form-control form-control-sm" value="<?= $_GET['order_date'] ?? '' ?>">
                    </div>

                    <!-- 4. Action Buttons -->
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-sm me-2"><i class="fa fa-filter"></i> Filter</button>
                        <a href="<?= BASE_URL ?>Order/index" class="btn btn-secondary btn-sm"><i class="fa fa-sync"></i> Reset</a>
                    </div>
                    
                </form>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle table-striped" id="orderTable">
                    <thead class="table-dark text-center">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 15%;">Order ID</th>
                            <th style="width: 15%;">Date</th>
                            <th style="width: 22%;">Stockist</th>
                            <th style="width: 13%;">Status</th>
                            <th style="width: 15%;">Grand Total</th>
                            <th style="width: 15%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (!empty($orders) && mysqli_num_rows($orders) > 0) {
                            $count = 1;
                            while ($row = mysqli_fetch_assoc($orders)) { 
                                // Status badge styling logic
                                $statusClass = 'bg-secondary';
                                if ($row['status'] == 'Approved') $statusClass = 'bg-success';
                                elseif ($row['status'] == 'Pending') $statusClass = 'bg-warning text-dark';
                                elseif ($row['status'] == 'Rejected') $statusClass = 'bg-danger';
                                elseif ($row['status'] == 'Processed') $statusClass = 'bg-info text-dark';
                        ?>
                                <tr>
                                    <td class="text-center"><?= $count++; ?></td>
                                    <td class="fw-bold text-primary">#ORD-<?= $row['order_id']; ?></td>
                                    <td class="text-center"><?= date('d-m-Y', strtotime($row['order_date'])); ?></td>
                                    <td><?= $row['ss_name'] ?? '<span class="text-muted">Unknown</span>'; ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $statusClass; ?>"><?= $row['status']; ?></span>
                                    </td>
                                    <td class="text-end fw-bold text-success">&#8377;<?= number_format($row['total_amt'], 2); ?></td>
                                    
                                   <td class="text-center">
                                        <div class="dropdown">
                                            <!-- Trigger Button -->
                                            <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="actionDropdown<?= $row['order_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa fa-cog"></i> Actions
                                            </button>
                                            
                                            <!-- Dropdown Menu Options -->
                                            <ul class="dropdown-menu shadow" aria-labelledby="actionDropdown<?= $row['order_id']; ?>">
                                                
                                                <!-- 1. Approve / Update -->
                                                <?php if ($row['status'] == 'Pending') { ?>
                                                    <li>
                                                        <a class="dropdown-item" href="<?= BASE_URL ?>Order/approve/<?= $row['order_id']; ?>" title="Review & Approve Order">
                                                            <i class="fa fa-check-circle text-primary me-2"></i> View
                                                        </a>
                                                    </li>
                                                <?php } elseif($row['status'] == 'Approved') { ?>
                                                    <li>
                                                        <a class="dropdown-item" href="<?= BASE_URL ?>Order/approve/<?= $row['order_id']; ?>" title="Review & Update Order">
                                                            <i class="fa fa-check-circle text-primary me-2"></i> Update
                                                        </a>
                                                    </li>
                                                    <!-- 2. Invoice -->
                                                    <li>
                                                        <a class="dropdown-item" href="<?= BASE_URL ?>invoice/pdf/<?= $row['order_id']; ?>" target="_blank">
                                                            <i class="fa fa-file-text-o text-info me-2"></i> Invoice
                                                        </a>
                                                    </li>
                                                <?php } elseif($row['status'] == 'Processed') { ?>
                                                    <li>
                                                        <a class="dropdown-item" href="<?= BASE_URL ?>Order/approve/<?= $row['order_id']; ?>" title="Review & Update Order">
                                                            <i class="fa fa-check-circle text-primary me-2"></i> View
                                                        </a>
                                                    </li>
                                                    <!-- 2. Invoice -->
                                                    <li>
                                                        <a class="dropdown-item" href="<?= BASE_URL ?>invoice/pdf/<?= $row['order_id']; ?>" target="_blank">
                                                            <i class="fa fa-file-text-o text-info me-2"></i> Invoice
                                                        </a>
                                                    </li>
                                                <?php } ?>

                                                

                                                <!-- Divider -->
                                                <li><hr class="dropdown-divider"></li>

                                                <!-- 3. Delete -->
                                                <li>
                                                    <a class="dropdown-item text-danger" href="<?= BASE_URL ?>order/delete/<?= $row['order_id']; ?>" 
                                                    onclick="return confirm('Are you sure you want to delete this order?');" title="Delete Order">
                                                        <i class="fa fa-trash me-2"></i> Delete
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                        <?php 
                            }
                        } else {
                        ?>
                           
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
include 'view/layout/footer.php'; 
?>

<!-- Initialize DataTable and AJAX -->
<script>
// Auto-hide messages
// Auto-hide ONLY success messages
setTimeout(function(){
    // Only fade out the green success alerts (leave red error alerts visible)
    $('.alert-success').fadeOut('slow');
    
    let url = new URL(window.location.href);
    
    // Delete only the notification parameters
    url.searchParams.delete('success');
    url.searchParams.delete('update');
    url.searchParams.delete('dispatch');
    // Notice we DO NOT delete 'error', so if there's an error, it stays in the URL
    
    // FIXED: Use url.toString() instead of url.pathname so it KEEPS your State & HQ filters!
    window.history.replaceState({}, document.title, url.toString());

}, 5000);
$(document).ready(function() {
    
    if ($.fn.DataTable) {
        $('#orderTable').DataTable({
            "order": [[ 0, "desc" ]], // Order by the latest entry by default
            "pageLength": 25
        });
    }

    if ($.fn.select2) {
        $('.select2').select2({ width: '100%' });
    }

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