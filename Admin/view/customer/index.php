<?php 
$pageTitle = "Customer";
// 3. Include the bottom layout and scripts
include 'view/layout/header.php'; 
?>


 <style>
        .detail          { display:flex; justify-content:flex-end; padding:6px; }
        .badge-active    { background:#28a745; color:#fff; padding:3px 10px; border-radius:4px; font-size:12px; }
        .badge-inactive  { background:#dc3545; color:#fff; padding:3px 10px; border-radius:4px; font-size:12px; }
        .badge-doctor    { background:#007bff; color:#fff; padding:3px 10px; border-radius:4px; font-size:12px; }
        .badge-chemist   { background:#fd7e14; color:#fff; padding:3px 10px; border-radius:4px; font-size:12px; }

      
        .thumb         { width:45px; height:45px; object-fit:cover; border-radius:5px; border:1px solid #ddd; }
        .preview-box   { margin-top:8px; }
        .preview-box img { width:80px; height:80px; object-fit:cover; border-radius:6px; border:1px solid #ccc; }

        .image-modal{
    display:none;
    position:fixed;
    z-index:9999;
    left:0;
    top:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,.9);
}

.modal-content1{
    display:block;
    margin:auto;
    width:40% !important;
    height:auto !important;
    margin-top:30px;
}

.close-modal{
    position:absolute;
    top:15px;
    right:25px;
    color:#fff;
    font-size:40px;
    cursor:pointer;
}

#imgPreview{
    max-width:150px;
    cursor:pointer;
    border-radius:8px;
}


.detail {
    display: flex;
    justify-content: flex-end;
    padding: 6px;
}
.dt-search{
    float:right;
}
.btn-primary{
    margin-top: 1.5rem !important;
}
.dt-paging{
    float: right;
    margin-top: -20px;
}
.btn-downoload{
    float: right;
    margin-top: -30px;
}
</style> 

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
                        <div class="alert alert-success">Customer saved successfully!</div>
                    <?php endif; ?>
                    <?php if(isset($_GET['deleted'])): ?>
                        <div class="alert alert-info">Customer deleted successfully!</div>
                    <?php endif; ?>
                    <?php if(isset($_GET['error'])): ?>
                        <div class="alert alert-danger">Something went wrong. Please try again.</div>
                    <?php endif; ?>
                    <?php if(isset($_GET['duplicate'])): ?>
                        <div class="alert alert-warning">Customer with same name and mobile already exists!</div>
                    <?php endif; ?>

                    <h3><?php echo isset($ROW) ? 'Edit' : 'Add'; ?> Customer</h3>

                    <!-- ==================  FORM  ================== -->
         <form method="post" enctype="multipart/form-data"
      action="<?= isset($ROW) ? BASE_URL.'customer/update/'.$ROW['c_id'] 
                              : BASE_URL.'customer/store'; ?>">

    <?php if(isset($ROW['customer_img'])): ?>
        <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($ROW['customer_img']); ?>">
    <?php endif; ?>

    <div class="container border px-3 py-3">
        <div class="row">

            <div class="col-lg-6">
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" name="customer_name" class="form-control"
                        placeholder="Enter Customer Name"
                        value="<?php echo isset($ROW['customer_name']) ? htmlspecialchars($ROW['customer_name']) : ''; ?>"
                        required>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="form-group">
                    <label>Customer Type</label>
                    <select name="customer_type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="Doctor"  <?php if(isset($ROW['customer_type']) && $ROW['customer_type'] == 'Doctor')  echo 'selected'; ?>>Doctor</option>
                        <option value="Chemist" <?php if(isset($ROW['customer_type']) && $ROW['customer_type'] == 'Chemist') echo 'selected'; ?>>Chemist</option>
                    </select>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control" required>
                        <option value="">Select Status</option>
                        <option value="1"   <?php if(isset($ROW['status']) && $ROW['status'] == '1')   echo 'selected'; ?>>Active</option>
                        <option value="0" <?php if(isset($ROW['status']) && $ROW['status'] == '0') echo 'selected'; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="form-group">
                    <label>Qualification</label>
                    <input type="text" name="qualification" class="form-control"
                        placeholder="Enter Qualification"
                        value="<?php echo isset($ROW['qualification']) ? htmlspecialchars($ROW['qualification']) : ''; ?>">
                </div>
            </div>

            <div class="col-lg-3">
                <div class="form-group">
                    <label>Mobile Number</label>
                    <input type="text" name="mobile" class="form-control"
                        placeholder="Enter Mobile Number"
                        maxlength="15"
                        value="<?php echo isset($ROW['mobile']) ? htmlspecialchars($ROW['mobile']) : ''; ?>"
                        required>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control"
                        placeholder="Enter Email Address"
                        value="<?php echo isset($ROW['email']) ? htmlspecialchars($ROW['email']) : ''; ?>">
                </div>
            </div>


            <div class="col-lg-4">
                <div class="form-group">
                    <label>Select State</label>
                    <select name="state" id="state_id" class="form-control" required>
                        <option value="">Select State</option>
                        <?php if($states): while($srow = mysqli_fetch_assoc($states)): ?>
                            <option value="<?php echo htmlspecialchars($srow['state_id']); ?>"
                                <?php if(isset($ROW['state']) && $ROW['state'] == $srow['state_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($srow['state_name']); ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
            </div>

           <div class="col-lg-4">
            <div class="form-group">
                <label>District</label>
                <select name="district" id="district" class="form-control" required>
                    <option value="">Select District</option>
                    
                    <?php if(isset($districts) && $districts): ?>
                        <?php while($drow = mysqli_fetch_assoc($districts)):  ?>
                            <option value="<?= htmlspecialchars($drow['district_id']) ?>" 
                                <?= (isset($ROW['district']) && $ROW['district'] == $drow['district_name']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($drow['district_name']) ?>
                            </option>
                            
                        <?php endwhile; ?>
                    <?php endif; ?>
                    
                </select>
            </div>
        </div>
            
            <div class="col-lg-4">
                <div class="form-group">
                    <label>Pincode</label>
                    <input type="text" name="pincode" class="form-control"
                        placeholder="Enter Pincode" maxlength="10"
                        value="<?php echo isset($ROW['pincode']) ? htmlspecialchars($ROW['pincode']) : ''; ?>">
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="form-group">
                    <label>Head Quarter</label>
                    <select name="hq_id" id="hq_id" class="form-control select2" required>
                        <option value="">Select Head Quarter</option>
                        </select>
                </div>
            </div>
            
             <div class="col-lg-6">
                <div class="form-group">
                    <label>Upload Image</label>
                    <input type="file" name="customer_img" class="form-control"
                           accept="image/*" onchange="previewImage(this)">
                    <div class="preview-box">
                        <?php if(isset($ROW['customer_img']) && !empty($ROW['customer_img'])): ?>
                            <img id="imgPreview"
                                 src="<?= BASE_URL ?>uploads/customers/<?php echo htmlspecialchars($ROW['customer_img']); ?>"
                                 alt="Current Image"
                                 onclick="openImage(this.src)" style="max-height: 100px;">
                            <br><small class="text-muted">Upload new image to replace</small>
                        <?php else: ?>
                            <img id="imgPreview" src="#" alt="" style="display:none; max-height: 100px;">
                        <?php endif; ?>
                    </div>
                </div>
            </div>


            <div class="col-lg-12">
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3" class="form-control"
                        placeholder="Enter Address"><?php echo isset($ROW['address']) ? htmlspecialchars($ROW['address']) : ''; ?></textarea>
                </div>
            </div>

        </div></div><div class="row">
        <div class="py-2 px-3">
            <button type="submit" name="save" class="btn btn-success btn-sm" style="width:130px;">
                <?php echo isset($ROW) ? 'Update' : 'Save'; ?>
            </button>
            <?php if(isset($ROW)): ?>
                <a href="<?= BASE_URL ?>customer" class="btn btn-secondary btn-sm ml-2" style="width:130px;">Cancel</a>
            <?php endif; ?>
        </div>
    </div>

</form>
                    <!-- ================  /FORM  ================ -->

                    <div class="mb-3">
                        <a href="<?= BASE_URL ?>customer/downloadImages" class="btn btn-success btn-downoload">
                            <i class="fa fa-download"></i> Download Bulk Images
                        </a>
                    </div>
                    <!-- ================  TABLE  ================ -->
                    <div class="table_container table-responsive pt-4">
                        <table class="table" id="customerTable">
                            <thead class="table-secondary">
                                <tr>
                                    <th class="text-center">Sr. No</th>
                                    <th class="text-center">Customer Name</th>
                                    <th class="text-center">Type</th>
                                    <th class="text-center">Qualification</th>
                                    <th class="text-center">Mobile</th>
                                    <th style="display:none;">Email</th>
                                    <th class="text-center">Head Quarter</th>
                                    <th class="text-center">State</th>
                                    <th class="text-center">District</th>
                                    <th class="text-center">Pincode</th>
                                    <th style="display:none;">Address</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $key  = 1;
                                
                                while($row = mysqli_fetch_assoc($list)):
                                    // Fetch state name
                                   
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $key; ?></td>

                                    <td class="text-center"><?php echo htmlspecialchars($row['customer_name']); ?></td>

                                    <!-- Type badge -->
                                    <td class="text-center">
                                        <?php if($row['customer_type'] == 'Doctor'): ?>
                                            <span class="badge-doctor">Doctor</span>
                                        <?php elseif($row['customer_type'] == 'Chemist'): ?>
                                            <span class="badge-chemist">Chemist</span>
                                        <?php else: ?>
                                            <span>—</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center"><?php echo htmlspecialchars($row['qualification'] ?? '—'); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['mobile']); ?></td>
                                    <td style="display:none;"><?= htmlspecialchars($row['email']) ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['hq_name'] ?? '—'); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['state_name'] ?? '—'); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['district'] ?? '—'); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['pincode'] ?? '—'); ?></td>
                                    <td style="display:none;"><?= htmlspecialchars($row['address']) ?></td>
                                    <!-- Status badge -->
                                    <td class="text-center">
                                        <?php if($row['status'] == '1'): ?>
                                            <span class="badge-active">Active</span>
                                        <?php else: ?>
                                            <span class="badge-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Actions -->
                                    <td class="text-center">
                                        <a href="<?= BASE_URL ?>customer/edit/<?php echo $row['c_id']; ?>"
                                        class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php $key++; endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- ==============  /TABLE  ================ -->

                </div>

<!-- image popup  -->
<div id="imageModal" class="image-modal">
    <span class="close-modal">&times;</span>
    <img class="modal-content1" id="modalImg">
</div>


                            <?php 
// 3. Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>
                <script>
    function openImage(src)
{
    document.getElementById('imageModal').style.display = 'block';
    document.getElementById('modalImg').src = src;
}

document.querySelector('.close-modal').onclick = function()
{
    document.getElementById('imageModal').style.display = 'none';
};

document.getElementById('imageModal').onclick = function(e)
{
    if(e.target === this)
    {
        this.style.display = 'none';
    }
};

$('#customerTable').DataTable({
    dom: 'Bfrtip',
    buttons: [{
        extend: 'excelHtml5',
        exportOptions: {
            columns: [0,1,2,3,4,5,6,7,8,9,10,11]
        }
    }],
    columnDefs: [
        {
            targets: [5,10], // Email and Address
            visible: false
        }
    ]
});

    // Auto-hide alerts after 5 seconds
    setTimeout(function(){
        document.querySelectorAll('.alert').forEach(function(el){
            el.style.display = 'none';
        });
    }, 5000);

    // State → District AJAX (same as stockist)
// State → District AJAX
$(document).ready(function(){

    // 1. Function to Load Districts
   // 1. Function to Load Districts
    function loadDistrict(state_id, district_id = '') {
        $.ajax({
            url: '<?= BASE_URL ?>district/getDistricts',
            type: 'POST',
            data: { state_id: state_id, selected_district: district_id },
            success: function(response) {
                // STEP 1: Destroy Select2 FIRST (if it exists)
                if ($('#district').hasClass('select2-hidden-accessible')) {
                    $('#district').select2('destroy');
                }
                
                // STEP 2: Update the HTML SECOND
                $('#district').html(response);
                
                // STEP 3: Re-initialize THIRD
                $('#district').select2({ theme: 'bootstrap-5' });
            }
        });
    }

    // 2. Function to Load Head Quarters
    function loadHQ(state_id, hq_id = '') {
        console.log("Loading HQ for State ID: " + state_id);

        if (!state_id) {
            console.error("Error: state_id is empty!");
            return; 
        }

        $.ajax({
            url: '<?= BASE_URL ?>customer/getHQs', 
            type: 'POST',
            data: { state_id: state_id, selected_id: hq_id },
            success: function(response) {
                // STEP 1: Destroy Select2 FIRST
                if ($('#hq_id').hasClass('select2-hidden-accessible')) {
                    $('#hq_id').select2('destroy');
                }
                
                // STEP 2: Update the HTML SECOND
                $('#hq_id').html(response);
                
                // STEP 3: Re-initialize THIRD
                $('#hq_id').select2({ theme: 'bootstrap-5' });
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
            }
        });
    }
    // 3. Trigger both on State change
    $('#state_id').change(function(){
        var state_id = $(this).val();
        loadDistrict(state_id);
        loadHQ(state_id);
    });

    // 4. Edit mode: Trigger only if state is already set
    var state_id = $('#state_id').val();
    
    // PHP variables from the server
    var district = "<?= isset($ROW['district']) ? $ROW['district'] : '' ?>";
    var hq_id    = "<?= isset($ROW['hq_id']) ? $ROW['hq_id'] : '' ?>";
    
    if(state_id != '') {
        loadDistrict(state_id, district);
        loadHQ(state_id, hq_id);
    }
    
    // Initialize Select2 on page load for any already-populated dropdowns
    $('.select2').select2({ theme: 'bootstrap-5' });
});


      // Live image preview
  function previewImage(input) {
    const preview = document.getElementById('imgPreview');

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };

        reader.readAsDataURL(input.files[0]);
    }
}



    

</script>

