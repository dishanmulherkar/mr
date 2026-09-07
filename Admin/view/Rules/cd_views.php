<?php 
$pageTitle = "Super Stockist Settings & CD Rules";
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
        <div class="alert alert-success">Settings saved successfully!</div>
    <?php endif; ?>
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger">Something went wrong. Please try again.</div>
    <?php endif; ?>

    <h3>Manage Super Stockist Settings</h3>

    <!-- ================== FORM ================== -->
    <form action="<?= BASE_URL ?>cd_rules/store" method="POST" id="settingsForm">
        
        <div class="container border px-3 py-3 mb-4 bg-white shadow-sm">
            
            <h5 class="text-primary mb-3">1. Select Stockist</h5>
            <div class="row">
                <!-- Super Stockist Dropdown -->
                <div class="col-lg-4 mb-3">
                    <label class="fw-bold">Super Stockist</label>
                    <select id="stockist_id" name="super_stockist_id" class="form-control select2" required>
                        <option value="">-- Select Stockist --</option>
                        <?php while ($stockist = mysqli_fetch_assoc($super_stockists)): ?>
                            <option value="<?= htmlspecialchars($stockist['super_stockist_id']) ?>">
                                <?= htmlspecialchars($stockist['ss_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <hr>

            <h5 class="text-primary mb-3">2. CD Rules Configuration</h5>
            <div class="row">
                <!-- 4% CD Days -->
                <div class="col-lg-4 mb-3">
                    <label class="fw-bold">Days for 4% CD</label>
                    <input type="number" id="cd_4_percent_days" name="cd_4_percent_days" class="form-control" 
                           placeholder="e.g., 10" value="10" required>
                </div>

                <!-- 2% CD Days -->
                <div class="col-lg-4 mb-3">
                    <label class="fw-bold">Days for 2% CD</label>
                    <input type="number" id="cd_2_percent_days" name="cd_2_percent_days" class="form-control" 
                           placeholder="e.g., 30" value="30" required>
                </div>
            </div>

            <hr>

            <h5 class="text-primary mb-3">3. Billing & Sequence Settings</h5>
            <div class="row">
                <!-- Order Prefix -->
                <div class="col-lg-3 mb-3">
                    <label class="fw-bold">Order Prefix</label>
                    <input type="text" id="order_prefix" name="order_prefix" class="form-control" 
                           placeholder="e.g., T-" value="T-" required>
                </div>

                <!-- Tally Start Number -->
                <div class="col-lg-3 mb-3">
                    <label class="fw-bold">Tally Start No.</label>
                    <input type="number" id="tally_start_no" name="tally_start_no" class="form-control" 
                           placeholder="e.g., 459" value="1" >
                </div>

                <!-- FY Start Month -->
                <div class="col-lg-3 mb-3">
                    <label class="fw-bold">FY Start Month</label>
                    <select id="fy_start_month" name="fy_start_month" class="form-select" required>
                        <option value="1">January</option>
                        <option value="2">February</option>
                        <option value="3">March</option>
                        <option value="4" selected>April</option>
                        <option value="5">May</option>
                        <option value="6">June</option>
                        <option value="7">July</option>
                        <option value="8">August</option>
                        <option value="9">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                </div>

                <!-- Current Sequence -->
                <div class="col-lg-3 mb-3">
                    <label class="fw-bold text-danger">Current Sequence Tracker</label>
                    <input type="number" id="order_sequence" name="order_sequence" class="form-control border-danger" 
                           value="0" readonly>
                    <small class="text-danger" style="font-size: 11px;">Highest bill number currently generated.</small>
                </div>
            </div>

            <!-- Buttons -->
            <div class="row mt-2">
                <div class="col-12">
                    <button type="submit" class="btn btn-success" style="width:150px;">
                        Save Settings
                    </button>
                    <button type="reset" class="btn btn-secondary ms-2" style="width:130px;" id="resetBtn">
                        Clear
                    </button>
                </div>
            </div>

        </div>
    </form>
    <!-- ================= /FORM ================= -->

</div>

<?php include 'view/layout/footer.php'; ?>

<script>
$(document).ready(function() {
    
    // Auto-hide flash messages after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Initialize Select2
    if ($('.select2').length) {
        $('.select2').select2({ theme: 'bootstrap-5' });
    }

    // Fetch and populate data when a Stockist is selected from the dropdown
    $('#stockist_id').change(function() {
        let stockistId = $(this).val();
        
        if (stockistId) {
            $.ajax({
                url: '<?= BASE_URL ?>cd_rules/get_settings_ajax',
                type: 'GET',
                data: { id: stockistId },
                dataType: 'json',
                success: function(res) {
                    if(res.success && res.data) {
                        // Populate CD Rules (Fallback to defaults if null)
                        $('#cd_4_percent_days').val(res.data.cd_4_percent_days !== null ? res.data.cd_4_percent_days : 10);
                        $('#cd_2_percent_days').val(res.data.cd_2_percent_days !== null ? res.data.cd_2_percent_days : 30);
                        
                        // Populate Billing Settings (Fallback to defaults if null)
                        $('#order_prefix').val(res.data.order_prefix !== null ? res.data.order_prefix : 'T-');
                        $('#tally_start_no').val(res.data.tally_start_no !== null ? res.data.tally_start_no : 1);
                        $('#fy_start_month').val(res.data.fy_start_month !== null ? res.data.fy_start_month : 4);
                        $('#order_sequence').val(res.data.order_sequence !== null ? res.data.order_sequence : 0);
                    }
                },
                error: function() {
                    alert("Failed to fetch data for the selected stockist.");
                }
            });
        } else {
            // Reset fields to default if dropdown is cleared
            $('#settingsForm')[0].reset();
            $('#stockist_id').val('').trigger('change.select2');
        }
    });

    // Handle form reset to also reset Select2 visual state
    $('#resetBtn').click(function() {
        setTimeout(() => {
            $('#stockist_id').val('').trigger('change.select2');
        }, 10);
    });

});
</script>