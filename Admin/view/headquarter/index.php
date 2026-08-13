<?php 
$pageTitle = "Head Quarter";
// 3. Include the bottom layout and scripts
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

    <!-- Flash Messages -->
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">Head Quarter saved successfully!</div>
    <?php endif; ?>
    <?php if(isset($_GET['deleted'])): ?>
        <div class="alert alert-info">Head Quarter deleted successfully!</div>
    <?php endif; ?>
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger">Something went wrong. Please try again.</div>
    <?php endif; ?>
    <?php if(isset($_GET['duplicate'])): ?>
        <div class="alert alert-warning">Head Quarter name already exists!</div>
    <?php endif; ?>

    <h3><?php echo isset($ROW) ? 'Edit' : 'Add'; ?> Head Quarter</h3>

    <!-- ================== FORM ================== -->
    <form method="post"
          action="<?= isset($ROW)
              ? BASE_URL.'headquarter/update/'.$ROW['headquarter_id']
              : BASE_URL.'headquarter/store'; ?>">
        
        <div class="container border px-3 py-3">
            <div class="row">

                <!-- HQ Name -->
                <div class="col-lg-6">
                    <div class="form-group">
                        <label>Head Quarter Name</label>
                        <input type="text" name="hq_name" class="form-control"
                               placeholder="Enter Head Quarter Name"
                               value="<?php echo isset($ROW['hq_name']) ? htmlspecialchars($ROW['hq_name']) : ''; ?>"
                               required>
                    </div>
                </div>

                <!-- State -->
                <div class="col-lg-3">
                    <div class="form-group">
                        <label>Select State</label>
                        <select name="state_id" id="state_id" class="form-control" required>
                            <option value="">Select State</option>
                            <?php while($srow = mysqli_fetch_assoc($states)): ?>
                                <option value="<?php echo htmlspecialchars($srow['state_id']); ?>"
                                    <?php if(isset($ROW['state_id']) && $ROW['state_id'] == $srow['state_id']) echo 'selected'; ?>>
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

                <!-- Super Stockist -->
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label>Select Super Stockist </label>
                            <select name="sup_stockist" id="sup_stockist" class="form-control" required>
                                <option value="">Select Super Stockist </option>
                                <?php while($srow = mysqli_fetch_assoc($stockist)): ?>
                                    <option value="<?php echo htmlspecialchars($srow['super_stockist_id']); ?>"
                                        <?php if(isset($ROW['super_stockist_id']) && $ROW['super_stockist_id'] == $srow['super_stockist_id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($srow['ss_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                     <!-- Super Stockist -->
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label>Select ASM </label>
                            <select name="asm" id="asm" class="form-control" required>
                                <option value="">Select ASM </option>
                                <?php while($srow = mysqli_fetch_assoc($asm)): ?>
                                    <option value="<?php echo htmlspecialchars($srow['admin_id']); ?>"
                                        <?php if(isset($ROW['asm_id']) && $ROW['asm_id'] == $srow['admin_id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($srow['admin_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
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
                    <a href="<?= BASE_URL ?>headquarter"
                       class="btn btn-secondary btn-sm ms-2"
                       style="width:130px;">
                        Cancel
                    </a>
                <?php endif; ?>
            </div>
        </div>

    </form>
    <!-- ================= /FORM ================= -->

    <!-- ================= TABLE ================= -->
    <div class="table_container table-responsive pt-4">
        <table class="table" id="hqTable">
            <thead class="table-secondary">
                <tr>
                    <th class="text-center">Sr. No</th>
                    <th class="text-center">State</th>
                    <th class="text-center">District</th>
                    <th class="text-center">HQ Name</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $key = 1;
            while($row = mysqli_fetch_assoc($list)):
            ?>
                <tr>
                    <td class="text-center"><?= $key++; ?></td>

                    <td class="text-center">
                        <?= htmlspecialchars($row['state_name']); ?>
                    </td>

                    <td class="text-center">
                        <?= htmlspecialchars($row['district']); ?>
                    </td>

                    <td class="text-center">
                        <?= htmlspecialchars($row['hq_name']); ?>
                    </td>

                    <td class="text-center">
                        <!-- Edit -->
                        <a href="<?= BASE_URL ?>headquarter/edit/<?= $row['headquarter_id']; ?>"
                        class="btn btn-warning btn-sm">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        
                        <!-- Delete (Optional) -->
                        <a href="<?= BASE_URL ?>headquarter/delete/<?= $row['headquarter_id']; ?>"
                        class="btn btn-danger btn-sm ms-1"
                        onclick="return confirm('Are you sure you want to delete this headquarter?');">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <!-- ============== /TABLE ================= -->

</div>

<?php 
// 3. Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>

<script>
$(document).ready(function() {

    if ($('#hqTable').length) {
        let table = new DataTable('#hqTable');
    }

    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // 4. Load District Function
    function loadDistrict(state_id, district_id = '') {
        $.ajax({
            url: '<?= BASE_URL ?>district/getDistricts',
            type: 'POST',
            data: {
                state_id: state_id,
                selected_district: district_id
            },
            success: function(response) {
                $('#district').html(response);
                
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

    $('.select2').select2({
        theme: 'bootstrap-5'
    });

    // Edit mode: Trigger on page load if state and district already exist
    var initial_state = $('#state_id').val();
    var initial_district = '<?= isset($ROW['district']) ? htmlspecialchars($ROW['district']) : '' ?>';
    
    if(initial_state != '' && initial_district != '') {
        loadDistrict(initial_state, initial_district);
    }
});
</script>