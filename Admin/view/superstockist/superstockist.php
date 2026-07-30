<?php 
$pageTitle = "Product Management";
include 'view/layout/header.php'; 
?>



<div id="container">

                    <!-- Logout -->
                    <div class="detail">
                        <!-- <a href="<?= BASE_URL ?>login/logout">
                            <button type="button" class="btn btn-secondary btn-sm">Logout</button>
                        </a> -->
                    </div>
                    <hr style="margin-top:10px; margin-bottom:10px; border-top:1px solid #333;">

                    <!-- ===  Flash Messages  === -->
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">Product saved successfully!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['deleted'])): ?>
                        <div class="alert alert-info">Product deleted successfully!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger">Something went wrong. Please try again.</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['duplicate'])): ?>
                        <div class="alert alert-warning">Product name already exists!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['toggled'])): ?>
                        <div class="alert alert-info">Product status updated.</div>
                    <?php endif; ?>

                    <!-- Section header with Import button -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h3 class="mb-0"><?php echo $ROW ? 'Edit' : 'Add'; ?> Super Stockist</h3>
                        
                    </div>

                    <!-- =====================  FORM  ===================== -->
                    <form method="post"
                          action="<?= isset($ROW)
                            ? BASE_URL.'supplier/update/'.$ROW['super_stockist_id']
                            : BASE_URL.'supplier/store'; ?>">

                        <div class="container border px-3 py-3">
                           <div class="row">

                                <!-- Super Stockist Name -->
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Super Stockist Name</label>
                                        <input type="text" name="ss_name" class="form-control"
                                            placeholder="Enter Super Stockist Name" required
                                            value="<?= $ROW ? htmlspecialchars($ROW['ss_name']) : ''; ?>">
                                    </div>
                                </div>

                                <!-- Person Name -->
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Person Name</label>
                                        <input type="text" name="person_name" class="form-control"
                                            placeholder="Enter Contact Person Name" required
                                            value="<?= htmlspecialchars($ROW['person_name'] ?? '') ?>">
                                    </div>
                                </div>

                                <!-- Country -->
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Country</label>
                                        <input type="text" name="country" class="form-control"
                                            placeholder="Enter Country"
                                            value="<?= $ROW ? htmlspecialchars($ROW['country']) : ''; ?>">
                                    </div>
                                </div>

                                <!-- State -->
                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <label>Select State</label>
                                         <select name="state" id ="state_id" class="form-control" required>
                                            <option value="">Select State</option>
                                            <?php while($srow = mysqli_fetch_assoc($states)): ?>
                                                <option value="<?php echo htmlspecialchars($srow['state_id']); ?>"
                                                    <?php if(isset($ROW['state']) && $ROW['state'] == $srow['state_id']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($srow['state_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>

                                 <!-- District -->
                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label>District</label>
                                                <select name="district" id="district" class="form-control" required>
                                                    <option value="">Select District</option>
                                                    
                                                    <?php if(isset($districts) && $districts): ?>
                                                        <?php while($drow = mysqli_fetch_assoc($districts)): ?>
                                                            
                                                            <option value="<?= htmlspecialchars($drow['district_name']) ?>" 
                                                                <?= (isset($ROW['district']) && $ROW['district'] == $drow['district_name']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($drow['district_name']) ?>
                                                            </option>
                                                            
                                                        <?php endwhile; ?>
                                                    <?php endif; ?>
                                                    
                                                </select>
                                            </div>
                                        </div>

                                <!-- Pincode -->
                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <label>Pincode</label>
                                        <input type="text" name="pincode" class="form-control"
                                            placeholder="Enter Pincode"
                                            value="<?= $ROW ? htmlspecialchars($ROW['pincode']) : ''; ?>">
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control" required>
                                            <option value="1" <?= $ROW && $ROW['status'] == '1' ? 'selected' : '' ?>>Active</option>
                                            <option value="0" <?= $ROW && $ROW['status'] == '0' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                    </div>
                                </div>

                            </div>
                           

                        <!-- Buttons -->
                        <div class="row">
                            <div class="py-2 px-3">
                                <button type="submit" name="save" class="btn btn-success btn-sm" style="width:130px;">
                                    <?php echo $ROW ? 'Update' : 'Save'; ?>
                                </button>
                                <?php if ($ROW): ?>
                                    <a href="party" class="btn btn-secondary btn-sm ml-2" style="width:130px;">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </div>

                    </form>
                    <!-- =================  /FORM  ================= -->

                    <!-- =================  TABLE  ================= -->
                    <div class="table_container table-responsive pt-4">
                        <table class="table table-hover" id="productTable">
                            <thead class="table-secondary">
                                <tr>
                                    <th class="text-center" style="width:50px;">Sr.</th>
                                    <th>Supplier Name</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $key  = 1;
                               
                                while ($row = mysqli_fetch_assoc($list)):
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $key; ?></td>
                                    <td><?php echo htmlspecialchars($row['ss_name']); ?></td>
                                    <td>
                                        <?php if ($row['status'] == '1'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    <td class="text-center" style="white-space:nowrap;">
                                        <!-- Edit -->
                                        <a href="<?= BASE_URL ?>supplier/edit/<?php echo $row['super_stockist_id']; ?>"
                                           class="btn btn-warning btn-sm ml-1" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <!-- Delete -->
                                    </td>
                                </tr>
                                <?php $key++; endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- ===============  /TABLE  =============== -->

                </div><!-- /#container -->



<?php 
// 3. Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>

<script>
    // DataTable init
    new DataTable('#productTable', {
        layout: {
            topStart: {
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="fa fa-file-excel"></i> Export Excel',
                        title: 'Party Master',
                        filename: 'Party_Master',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4] // Excludes Action column
                        }
                    }
                ]
            }
        },
        pageLength: 25
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function () {
        document.querySelectorAll('.alert').forEach(function (el) {
            el.style.display = 'none';
        });
    }, 5000);

       $(document).ready(function() {

     // 4. Load District Function
    function loadDistrict(state_id, district_id = '') {
        $.ajax({
            url: '<?= BASE_URL ?>district/getDistricts', // Removed double slash
            type: 'POST',
            data: {
                state_id: state_id,
                selected_district: district_id
            },
            success: function(response) {
                $('#district').html(response);
                
                // IMPORTANT: If 'district' is a Select2, you must refresh it 
                // after changing the HTML content
                if ($('#district').hasClass('select2-hidden-accessible')) {
                    $('#district').select2('destroy'); 
                }
                $('#district').select2(); 
            }
        });
    }

    // On state change
    $('#state_id').change(function() {
        loadDistrict($(this).val());
    });

        // Edit mode: Trigger only if state is already selected
    var initial_state = $('#state_id').val();
    var initial_district = $('#district').val(); // Get current value if it exists
    
    if(initial_state != '') {
        loadDistrict(initial_state, initial_district);
    }
});
</script>

