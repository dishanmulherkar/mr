<?php 
$pageTitle = "Customer";
include 'view/layout/header.php'; 
?>

<style>
    /* Styling for the 3-column grid layout from your image */
    .form-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px; }
    .form-grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px; }
    
    @media (max-width: 900px) { .form-grid-3 { grid-template-columns: 1fr; } }
    @media (max-width: 600px) { .form-grid-2 { grid-template-columns: 1fr; } }
    @media (max-width: 480px) {
  .topbar {
    padding: 12px 14px;
    gap: 11px !important;
  }
}

.form-field textarea{
    width:100%;
    min-height:100px;
    padding:12px 14px;
    border:1px solid #dcdcdc;
    border-radius:8px;
    resize:vertical;
    font-size:14px;
    font-family:inherit;
    outline:none;
    box-sizing:border-box;
}

.form-field textarea:focus{
    border-color:#4f46e5;
}
@media (max-width: 480px) {
  .avatar_name {
    cursor: pointer;
    border: 1px solid #b1b5ca;
    border-radius: 9px;
    padding-right: 51px;
    padding-left: 44px;
    margin-left: 60px;
    font-size: small;
    color: #767b94;
  }
}

</style>


<div class="page-content">
<div style="display:flex;align-items:center;margin-bottom:16px;">
            <button type="button" class="btn-back" onclick="history.back()">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                    stroke="currentColor" stroke-width="2.2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
                <span>Back</span>
            </button>
        </div>

        <div class="settings-card">
          <form action="<?= isset($customer) ? BASE_URL.'AddCustomer/update/'.$customer['c_id'] 
                              : BASE_URL.'AddCustomer/store'; ?>" method="POST" enctype="multipart/form-data"  onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            <div class="settings-section">
                <div class="form-grid-3">
                 <?php if (!empty($customer['c_id'])): ?>
                    <input type="hidden" name="customer_id" value="<?= $customer['c_id']; ?>">
                <?php endif; ?>
                    <div class="form-field"><label>Customer Name</label>
                        <input type="text"
                        name="customer_name"
                        value="<?= htmlspecialchars($customer['customer_name'] ?? '') ?>"
                        placeholder="Enter Customer Name"
                        required>
                    </div>
                    <div class="form-field"><label>Customer Type</label>

                       <select name="customer_type" required>
                            <option value="Doctor"
                                <?= (($customer['customer_type'] ?? '') == 'Doctor') ? 'selected' : ''; ?>>
                                Doctor
                            </option>

                            <option value="Chemist"
                                <?= (($customer['customer_type'] ?? '') == 'Chemist') ? 'selected' : ''; ?>>
                                Chemist
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-grid-3">
                    <div class="form-field"><label>Qualification</label><input type="text" name="qualification" value="<?= htmlspecialchars($customer['qualification'] ?? '') ?>" placeholder="Enter Qualification" required /></div>
                    <div class="form-field"><label>Mobile Number</label><input type="tel" name="mobile" value="<?= htmlspecialchars($customer['mobile'] ?? '') ?>" placeholder="Enter Mobile Number"  maxlength="10"
           pattern="[6-9]{1}[0-9]{9}" required /></div>
                    <div class="form-field"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($customer['email'] ?? '') ?>" placeholder="Enter Email Address"/></div>
                </div>

                <div class="form-grid-3">
           
                    <div class="form-field"><label>Select State</label><select name="state" id="state_id" class="form-control" required>
                                            <option value="">Select State</option>
                                            <?php while($srow = mysqli_fetch_assoc($states)): ?>
                                                <option value="<?php echo htmlspecialchars($srow['state_id']); ?>"
                                                    <?php if(isset($customer['state']) && $customer['state'] == $srow['state_id']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($srow['state_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select></div>
                    <div class="form-field">
                      <label>District</label>
                            <select name="district" id="district" class="form-control" required>
                                            <option value="">Select District</option>
                                            <?php if (!empty($districts)): ?>
                                                <?php while($drow = mysqli_fetch_assoc($districts)): ?>
                                                    <option value="<?= $drow['district_name']; ?>"
                                                        <?= (($customer['district'] ?? '') == $drow['district_name']) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($drow['district_name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                            </select>
                        </div>
                    <div class="form-field"><label>Pincode</label><input type="text" name="pincode" placeholder="Enter Pincode" value="<?= htmlspecialchars($customer['pincode'] ?? '') ?>" maxlength="6"
           pattern="[0-9]{6}" required /></div>
                </div>

                <div class="form-grid-2">

            <div class="form-field">
                <label>Upload Image</label>
                <?php
                $img = !empty($customer['customer_img'])
                    ? BASE_URL . "../Admin/uploads/customers/" . $customer['customer_img']
                    : BASE_URL . "../Admin/uploads/customers/no-image.png";
                ?>
                <div style="display:flex;align-items:center;gap:15px;flex-wrap:wrap;">
                    <img
                        id="previewImage"
                        src="<?= !empty($img) ? htmlspecialchars($img) : '../admin/uploads/customers/no-image.png'; ?>"
                        alt="Customer Image"
                        style="width:100px;height:100px;border-radius:8px;border:1px solid #ddd;object-fit:cover;">

                    <div style="flex:1;">
                        <input
                            type="file"
                            id="customer_img"
                            name="customer_img"
                            accept="image/jpeg,image/png,image/jpg,image/webp">
                    </div>
                </div>
            </div>
        </div>

              <div class="form-field">
                    <label>Address</label>
                    <textarea
                        name="address"
                        rows="4"
                        placeholder="Enter Address"
                        required><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn-save">
    <?= isset($customer['c_id']) ? 'Update Customer' : 'Save Customer'; ?>
</button>      
            </div>
          </form>
        </div>
    </div>
    
    

 <?php 
// 3. Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>   


 <script>

   const customerImg = document.getElementById('customer_img');

if (customerImg) {

    customerImg.addEventListener('change', function () {

        const file = this.files[0];

        if (!file) return;

        const reader = new FileReader();

        reader.onload = function (e) {

            document.getElementById('previewImage').src = e.target.result;

        };

        reader.readAsDataURL(file);

    });

}
  // State → District AJAX (same as stockist)
    $(document).ready(function(){
        function loadDistrict(state_id, district = '')
    {
        $.ajax({
             url: '<?= BASE_URL ?>AddCustomer/getDistricts',
            type:'POST',
            data:{
                state_id:state_id,
                selected_district:district
            },
            success:function(response)
            {
                $('#district').html(response);
            }
        });
    }

      // On state change
    $('#state_id').change(function(){
        var state_id = $(this).val();
        loadDistrict(state_id);
    });

    // Edit mode
    var state_id = $('#state_id').val();
  const district = "<?= isset($customer['district']) ? $customer['district'] : ''; ?>";

    if(state_id != '')
    {
        loadDistrict(state_id, district);
    }
    });
</script>       