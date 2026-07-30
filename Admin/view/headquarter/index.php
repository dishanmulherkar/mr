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

                    <!-- ==================  FORM  ================== -->
                    <form method="post"
                          action="<?= isset($ROW)
            ? BASE_URL.'headquarter/update/'.$ROW['m_id']
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

                            </div><!-- /row -->

                            <!-- MR Data Box -->
                            <div class="box box-primary mt-3">
                                <div class="box-header with-border">
                                    <h5 class="box-title">MR Data</h5>
                                </div>
                                <div class="box-body">
                                    <div class="row">

                                        <!-- MR Name -->
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label>MR Name</label>
                                                <input type="text" name="mr_name" class="form-control"
                                                    placeholder="Enter MR Name" required
                                                    value="<?php echo isset($ROW['mr_name']) ? htmlspecialchars($ROW['mr_name']) : ''; ?>">
                                            </div>
                                        </div>

                                        <!-- Password -->
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label>Password</label>
                                                <input type="password" name="password" id="password"
                                                    class="form-control"
                                                    placeholder="Enter Password"
                                                    value="<?php echo isset($ROW['password']) ? htmlspecialchars($ROW['password']) : ''; ?>"
                                                    required>
                                                <div class="mt-2">
                                                    <input type="checkbox" id="showPassword">
                                                    <label for="showPassword">Show Password</label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Mobile -->
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label>Mobile Number</label>
                                                <input type="text" name="mobile" class="form-control"
                                                    placeholder="Enter Mobile Number"
                                                    maxlength="10" pattern="[0-9]{10}" required
                                                    value="<?php echo isset($ROW['mobile']) ? htmlspecialchars($ROW['mobile']) : ''; ?>">
                                            </div>
                                        </div>

                                        <!-- Email -->
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label>Email</label>
                                                <input type="email" name="email" class="form-control"
                                                    placeholder="Enter Email Address" required
                                                    value="<?php echo isset($ROW['email']) ? htmlspecialchars($ROW['email']) : ''; ?>">
                                            </div>
                                        </div>
                                        
                                        <!-- District -->
                                        <div class="col-lg-4">
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
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <label>Pincode</label>
                                                <input type="text" name="pincode" class="form-control"
                                                    placeholder="Enter Pincode" maxlength="10" required
                                                    value="<?php echo isset($ROW['pincode']) ? htmlspecialchars($ROW['pincode']) : ''; ?>">
                                            </div>
                                        </div>

                                        <!-- Address -->
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label>Address</label>
                                                <textarea name="address" rows="3" class="form-control"
                                                    placeholder="Enter Address"><?php echo isset($ROW['address']) ? htmlspecialchars($ROW['address']) : ''; ?></textarea>
                                            </div>
                                        </div>
                                        
                                         <div class="form-check form-check-inline">
                                        <input class="form-check-input"
                                            type="radio"
                                            name="status"
                                            id="status_active"
                                            value="1"
                                            <?php if(isset($ROW['status']) && $ROW['status']=='1') echo 'checked'; else echo 'checked'; ?>>

                                        <label class="form-check-label" name="status" for="status_active">
                                            Active
                                        </label>
                                    </div>

                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input"
                                            type="radio"
                                            name="status"
                                            id="status_inactive"
                                            value="0"
                                            <?php if(isset($ROW['status']) && $ROW['status']=='0') echo 'checked'; ?>>

                                        <label class="form-check-label" for="status_inactive">
                                            Inactive
                                        </label>
                                    </div>

                                    </div><!-- /row -->
                                </div><!-- /box-body -->
                            </div><!-- /box -->
                        </div><!-- /border -->

                        <!-- Buttons -->
                        <div class="row">
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
                    <!-- ================  /FORM  ================ -->

                    <!-- ================  TABLE  ================ -->
                    <div class="table_container table-responsive pt-4">
                        <table class="table" id="hqTable">
                            <thead class="table-secondary">
                                <tr>
                                    <th class="text-center">Sr. No</th>
                                    <th class="text-center">State</th>
                                    <th class="text-center">HQ Name</th>
                                    <th class="text-center">MR Name</th>
                                    <th class="text-center">Mobile</th>
                                    <th class="text-center">Email</th>
                                    <th class="text-center">Pincode</th>
                                    <th class="text-center">Address</th>
                                    <th class="text-center">Password</th>
                                    <th class="text-center">Action</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $key = 1;

                            while($row = mysqli_fetch_assoc($list)):
                                
                                if($row['status'] == 1){
                                    $status_name = "Active";
                                }else {
                                    $status_name = "Inactive";
                                }
                            ?>
                            <tr>
                                <td class="text-center"><?= $key++; ?></td>

                                <td class="text-center">
                                    <?= htmlspecialchars($row['state_name']); ?>
                                </td>

                                <td class="text-center">
                                    <?= htmlspecialchars($row['hq_name']); ?>
                                </td>

                                <td class="text-center">
                                    <?= htmlspecialchars($row['mr_name']); ?>
                                </td>

                                <td class="text-center">
                                    <?= htmlspecialchars($row['mobile']); ?>
                                </td>

                                <td class="text-center">
                                    <?= htmlspecialchars($row['email']); ?>
                                </td>

                                <td class="text-center">
                                    <?= htmlspecialchars($row['pincode']); ?>
                                </td>

                                <td class="text-center">
                                    <?= htmlspecialchars($row['address']); ?>
                                </td>

                                <td class="text-center">
                                    <?= htmlspecialchars($row['password']); ?>
                                </td>
                                
                                <td class="text-center">
                                    <?= htmlspecialchars($status_name); ?>
                                </td>

                                <td class="text-center">
                                    <!-- Edit -->
                                    <a href="<?= BASE_URL ?>headquarter/edit/<?= $row['m_id']; ?>"
                                    class="btn btn-warning btn-sm">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </td>
                            </tr>
                    <?php endwhile; ?>
                </tbody>
                        </table>
                    </div>
                    <!-- ==============  /TABLE  ================ -->

                </div>
            <!-- </div>
        </div>
    </main>
</div> -->
<?php 
// 3. Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>
<script>
   $(document).ready(function() {

    // 1. Safe DataTable Initialization
    // Only initialize if the element actually exists on this page
    if ($('#hqTable').length) {
        let table = new DataTable('#hqTable');
    }

    // 2. Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // 3. Password Toggle
    $('#showPassword').change(function() {
        $('#password').attr('type', $(this).is(':checked') ? 'text' : 'password');
    });

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

    // Initialize Select2 on page load for static dropdowns
    $('.select2').select2({
        theme: 'bootstrap-5'
    });

    // Edit mode: Trigger only if state is already selected
    var initial_state = $('#state_id').val();
    var initial_district = $('#district').val(); // Get current value if it exists
    
    if(initial_state != '') {
        loadDistrict(initial_state, initial_district);
    }
});
</script>
