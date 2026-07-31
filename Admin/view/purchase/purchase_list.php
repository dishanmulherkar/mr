<?php 
include 'view/layout/header.php'; 
?>

<div id="container" class="container-fluid mt-3">
    
    <!-- Flash Messages (Optional, if redirected from edit/delete) -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Action completed successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-list"></i> Purchase List</h5>
            <!-- Button to go to the Create Purchase Entry form -->
            <a href="<?= BASE_URL ?>purchase" class="btn btn-light btn-sm fw-bold">
                <i class="fa fa-plus"></i> New Purchase
            </a>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle table-striped" id="purchaseTable">
                    <thead class="table-dark text-center">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 15%;">Purchase No</th>
                            <th style="width: 12%;">Date</th>
                            <th style="width: 15%;">Invoice No</th>
                            <th style="width: 20%;">Super Stockist</th>
                            <th style="width: 8%;">Total Qty</th>
                            <th style="width: 12%;">Grand Total</th>
                            <th style="width: 13%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($purchases) > 0) {
                            $count = 1;
                            while ($row = mysqli_fetch_assoc($purchases)) { 
                        ?>
                                <tr>
                                    <td class="text-center"><?= $count++; ?></td>
                                    <td class="fw-bold text-primary"><?= $row['purchase_no']; ?></td>
                                    <td class="text-center"><?= date('d-m-Y', strtotime($row['purchase_date'])); ?></td>
                                    <td class="text-center"><?= $row['invoice_no']; ?></td>
                                    <td><?= $row['ss_name'] ?? '<span class="text-muted">Unknown</span>'; ?></td>
                                    <td class="text-center fw-bold"><?= $row['total_qty']; ?></td>
                                    <td class="text-end fw-bold text-success">&#8377;<?= number_format($row['grand_total'], 2); ?></td>
                                    
                                    <td class="text-center">
                                        <!-- View Button -->
                                        <a href="<?= BASE_URL ?>purchase/view/<?= $row['purchase_id']; ?>" 
                                           class="btn btn-info btn-sm text-white" 
                                           title="View Invoice">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        
                                        <!-- Edit Button -->
                                        <a href="<?= BASE_URL ?>purchase/edit/<?= $row['purchase_id']; ?>" 
                                           class="btn btn-warning btn-sm text-white" 
                                           title="Edit Invoice">
                                            <i class="fa fa-edit"></i>
                                        </a>

                                        <!-- Delete Button (Optional) -->
                                        <a href="<?= BASE_URL ?>purchase/delete/<?= $row['purchase_id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           title="Delete Invoice"
                                           onclick="return confirm('Are you sure you want to delete this purchase entry? This will also remove the stock.');">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                        <?php 
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">No purchase records found.</td>
                            </tr>
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

<!-- Optional: Initialize DataTable if you are using the DataTables jQuery plugin -->
<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#purchaseTable').DataTable({
            "order": [[ 0, "asc" ]], // Order by the '#' column by default
            "pageLength": 25
        });
    }
});
</script>