<?php 
$pageTitle = "CD Rules";
// Include the top layout and scripts
include 'view/layout/header.php'; 
?> 
 
<div id="container">

    <!-- Logout -->
    <div class="detail">
        <a href="<?= BASE_URL ?>login/logout">
            <button type="button" class="btn btn-secondary btn-sm">Logout</button>
        </a>
    </div>
    <hr style="margin-top:10px; margin-bottom:10px; border-top:1px solid #333;">

    <!-- Flash Messages (Assuming you send these in URL like ?success=1) -->
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">CD Rules saved successfully!</div>
    <?php endif; ?>
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger">Something went wrong. Please try again.</div>
    <?php endif; ?>

    <h3><?= isset($ROW) ? 'Edit' : 'Add'; ?> CD Rules</h3>

    <!-- ================== FORM ================== -->
    <form action="<?= BASE_URL ?>cd_rules/store" method="POST">
        
        <div class="container border px-3 py-3">
            <div class="row">

                <!-- Super Stockist -->
                <div class="col-lg-4">
                    <div class="form-group">
                        <label>Super Stockist</label>
                        <!-- Added select2 class and readonly styling if editing -->
                        <select name="super_stockist_id" class="form-control select2" required <?= isset($ROW) ? 'readonly style="pointer-events: none; background-color:#e9ecef;"' : '' ?>>
                            <option value="">Select Stockist</option>
                            <?php while ($stockist = mysqli_fetch_assoc($super_stockists)): ?>
                                <option value="<?= htmlspecialchars($stockist['super_stockist_id']) ?>" 
                                    <?= (isset($ROW) && $ROW['super_stockist_id'] == $stockist['super_stockist_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($stockist['ss_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- 4% CD Days -->
                <div class="col-lg-4">
                    <div class="form-group">
                        <label>Days for 4% CD</label>
                        <input type="number" name="cd_4_percent_days" class="form-control" 
                               placeholder="e.g., 10"
                               value="<?= isset($ROW) ? htmlspecialchars($ROW['cd_4_percent_days']) : '10' ?>" required>
                    </div>
                </div>

                <!-- 2% CD Days -->
                <div class="col-lg-4">
                    <div class="form-group">
                        <label>Days for 2% CD</label>
                        <input type="number" name="cd_2_percent_days" class="form-control" 
                               placeholder="e.g., 30"
                               value="<?= isset($ROW) ? htmlspecialchars($ROW['cd_2_percent_days']) : '30' ?>" required>
                    </div>
                </div>

            </div><!-- /row -->
        </div><!-- /border -->

        <!-- Buttons -->
        <div class="row mt-3">
            <div class="py-2 px-3">
                <button type="submit" class="btn btn-success btn-sm" style="width:130px;">
                    <?= isset($ROW) ? 'Update' : 'Save'; ?>
                </button>

                <?php if(isset($ROW)): ?>
                    <a href="<?= BASE_URL ?>cd_rules" class="btn btn-secondary btn-sm ms-2" style="width:130px;">
                        Cancel
                    </a>
                <?php endif; ?>
            </div>
        </div>

    </form>
    <!-- ================= /FORM ================= -->

    <!-- ================= TABLE ================= -->
    <div class="table_container table-responsive pt-4">
        <table class="table" id="cdRulesTable">
            <thead class="table-secondary">
                <tr>
                    <th class="text-center">Sr. No</th>
                    <th class="text-center">Stockist Name</th>
                    <th class="text-center">4% CD Days</th>
                    <th class="text-center">2% CD Days</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($query && mysqli_num_rows($query) > 0): ?>
                    <?php 
                    $key = 1;
                    while ($row = mysqli_fetch_assoc($query)): 
                    ?>
                        <tr>
                            <td class="text-center"><?= $key++; ?></td>
                            <td class="text-center">
                                <?= htmlspecialchars($row['stockist_name'] ?? 'Unknown (' . $row['super_stockist_id'] . ')') ?>
                            </td>
                            <td class="text-center"><?= htmlspecialchars($row['cd_4_percent_days']) ?> Days</td>
                            <td class="text-center"><?= htmlspecialchars($row['cd_2_percent_days']) ?> Days</td>
                            <td class="text-center">
                                <!-- Edit Button (Matches your HQ view styling) -->
                                <a href="<?= BASE_URL ?>cd_rules/edit/<?= $row['super_stockist_id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- ============== /TABLE ================= -->

</div>

<?php 
// Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>

<script>
$(document).ready(function() {

    // Initialize DataTable
    if ($('#cdRulesTable').length) {
        let table = new DataTable('#cdRulesTable');
    }

    // Auto-hide flash messages after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Initialize Select2 if it exists
    if ($('.select2').length) {
        $('.select2').select2({
            theme: 'bootstrap-5'
        });
    }

});
</script>