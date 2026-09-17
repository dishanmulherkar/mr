<?php 
// Include the header
include 'view/layout/header.php'; 
?>

<div id="container">
    <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Action completed successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            Something went wrong. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-list"></i> Stock Adjustments List</h5>
            <!-- Make sure this URL points to your Adjustment 'add/index' page -->
            <a href="<?= BASE_URL ?>purchase/adjustment" class="btn btn-light btn-sm fw-bold">
                <i class="fa fa-plus"></i> New Adjustment
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle" id="adjustmentTable">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 15%;">Adj No.</th>
                            <th style="width: 15%;">Date</th>
                            <th style="width: 25%;">Super Stockist</th>
                            <th style="width: 30%;">Remarks</th>
                            <th style="width: 10%; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (isset($Adjustments) && mysqli_num_rows($Adjustments) > 0) {
                            $count = 1;
                            while ($row = mysqli_fetch_assoc($Adjustments)) {
                                // Format date nicely
                                $formatted_date = date('d-M-Y', strtotime($row['adj_date']));
                        ?>
                                <tr>
                                    <td><?= $count++; ?></td>
                                    <td class="fw-bold text-primary"><?= $row['adj_no']; ?></td>
                                    <td><?= $formatted_date; ?></td>
                                    <td><?= $row['ss_name'] ?? 'N/A'; ?></td>
                                    <td><?= !empty($row['remarks']) ? $row['remarks'] : '-'; ?></td>
                                    <td class="text-center">
                                        <!-- Edit Button (Requires an edit method in controller) -->
                                        <a href="<?= BASE_URL ?>purchase/edit_adj/<?= $row['adj_id']; ?>" class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        
                                        <!-- View Button -->
                                        <a href="<?= BASE_URL ?>purchase/view_adj/<?= $row['adj_id']; ?>" class="btn btn-info btn-sm text-white" title="View">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        
                                        <!-- Delete Button (Requires a delete method in controller) -->
                                        <!-- <a href="<?= BASE_URL ?>adjustment/delete/<?= $row['adj_id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this adjustment? This will reverse the stock!');">
                                            <i class="fa fa-trash"></i>
                                        </a> -->
                                    </td>
                                </tr>
                        <?php 
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No adjustments found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'view/layout/footer.php'; ?>

<!-- Include DataTables JS if you use it (optional but recommended for lists) -->
<script>
$(document).ready(function() {
    // If you are using DataTables plugin, initialize it here
    if ($.fn.DataTable) {
        $('#adjustmentTable').DataTable({
            "order": [[ 0, "desc" ]], // Order by first column descending (Newest first)
            "pageLength": 25,
            "language": {
                "search": "Filter Records:"
            }
        });
    }
});
</script>