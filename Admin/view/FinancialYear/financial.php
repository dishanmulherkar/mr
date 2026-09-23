<?php 
$pageTitle = "Financial Year";
include 'view/layout/header.php'; 
?>

<div id="container">
    <div class="detail">
        <a href="<?= BASE_URL ?>login/logout" style="float:right">
            <button type="button" class="btn btn-secondary btn-sm">Logout</button>
        </a>
    </div>
    <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">
    <h3>Financial </h3>

    <form method="post" action="<?= isset($ROW) ? BASE_URL.'financial/update/'.$ROW['fy_id'] : BASE_URL.'financial/store'; ?>">
        <div class="container border px-3 py-3">
            <div class="row">

                <!-- State -->
                <div class="col-lg-3">
                    <div class="form-group">
                        <label>Select State</label>
                        <select name="state" id="state_id" class="form-control" required>
                            <option value="">Select State</option>
                            <?php if($states && mysqli_num_rows($states) > 0): ?>
                                <?php while($srow = mysqli_fetch_assoc($states)): ?>
                                    <option value="<?= $srow['state_id']; ?>" <?= (isset($state['state']) && $state['state'] == $srow['state_id'])  ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($srow['state_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <!-- HQ -->
                <div class="col-lg-3">
                    <div class="form-group">
                        <label>Head Quarter</label>
                        <select name="hq_id" id="hq_id" class="form-control select2" required>
                            <option value="">Select Head Quarter</option>
                        </select>
                    </div>
                </div>

                <!-- MR (Populated via AJAX) -->
                <div class="col-lg-3">
                    <div class="form-group">
                        <label>MR</label>
                        <select name="mr_id" id="mr_id" class="form-control select2" required>
                            <option value="">Select MR</option>
                        </select>
                    </div>
                </div>

                <!-- FY Name -->
                <div class="col-lg-3">
                    <div class="form-group">
                        <label>Financial Year Name</label>
                        <input type="text" name="fy_name" class="form-control"
                               value="<?= isset($ROW['fy_name']) ? htmlspecialchars($ROW['fy_name']) : ''; ?>"
                               placeholder="Example : FY 2026-27" required>
                    </div>
                </div>

                <!-- Start Date -->
                <div class="col-lg-3 mt-3">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control"
                               value="<?= isset($ROW['start_date']) ? $ROW['start_date'] : ''; ?>" required>
                    </div>
                </div>

                <!-- End Date -->
                <div class="col-lg-3 mt-3">
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control"
                               value="<?= isset($ROW['end_date']) ? $ROW['end_date'] : ''; ?>" required>
                    </div>
                </div>

                <!-- Target -->
                <div class="col-lg-3 mt-3">
                    <div class="form-group">
                        <label>Target Amount (₹)</label>
                        <input type="number" step="0.01" min="0" name="target_amount" class="form-control"
                               value="<?= isset($ROW['target_amount']) ? $ROW['target_amount'] : ''; ?>"
                               placeholder="Enter Target Amount" required>
                    </div>
                </div>

                <!-- Status -->
                <div class="col-lg-3 mt-3">
                    <div class="form-group">
                        <label>Status</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" value="1" id="active"
                                <?= (!isset($ROW['status']) || $ROW['status']=='1') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="active">Active</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" value="0" id="inactive"
                                <?= (isset($ROW['status']) && $ROW['status']=='0') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="inactive">Inactive</label>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row mt-3">
                <div class="col-lg-12">
                    <button type="submit" name="save" class="btn btn-success btn-sm" style="width:200px;">
                        Save Financial Year
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="table_container table-responsive pt-4">
    <table class="table table-bordered table-hover" id="finanTable">
        <thead class="table-secondary">
            <tr>
                <th class="text-center">Sr. No</th>
                <th class="text-center">State</th>
                <th class="text-center">MR</th>
                <th class="text-center">Financial Year</th>
                <th class="text-center">Start Date</th>
                <th class="text-center">End Date</th>
                <th class="text-center">Target (₹)</th>
                <th class="text-center">Status</th>
                <th class="text-center">Action</th>
            </tr>
            <tr>
                <th></th>
                <th><input type="text" class="form-control form-control-sm" placeholder="Search State"></th>
                <th><input type="text" class="form-control form-control-sm" placeholder="Search MR"></th>
                <th><input type="text" class="form-control form-control-sm" placeholder="Search FY"></th>
                <th><input type="text" class="form-control form-control-sm" placeholder="Search Start"></th>
                <th><input type="text" class="form-control form-control-sm" placeholder="Search End"></th>
                <th><input type="text" class="form-control form-control-sm" placeholder="Search Target"></th>
                <th>
                    <select class="form-select form-select-sm">
                        <option value="">All</option>
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $key = 1;
            while($row = mysqli_fetch_assoc($query)) {
            ?>
            <tr>
                <td class="text-center"><?= $key; ?></td>
                <td class="text-center"><?= htmlspecialchars($row['state_name']); ?></td>
                <td class="text-center"><?= htmlspecialchars($row['mr_name']); ?></td>
                <td class="text-center"><?= htmlspecialchars($row['fy_name']); ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($row['start_date'])); ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($row['end_date'])); ?></td>
                <td class="text-center">₹ <?= number_format($row['target_amount'], 2); ?></td>
                <td class="text-center">
                    <?= ($row['status'] == '1') ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'; ?>
                </td>
                <td class="text-center">
                    <a href="<?= BASE_URL ?>financial/edit/<?= $row['fy_id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-pen"></i>
                    </a>
                </td>
            </tr>
            <?php 
                $key++; 
            } 
            ?>
        </tbody>
    </table>
</div>

<?php include 'view/layout/footer.php'; ?>

<script>
$(document).ready(function () {
    // 1. Initialize DataTable
    var table = $('#finanTable').DataTable({
        orderCellsTop: true,
        fixedHeader: true
    });

    $('#finanTable thead tr:eq(1) th').each(function (i) {
        $('input', this).on('keyup change', function () {
            if (table.column(i).search() !== this.value) {
                table.column(i).search(this.value).draw();
            }
        });
    });

    $('#finanTable thead select').on('change', function () {
        table.column(7).search($(this).val()).draw();
    });

    // Auto-hide alert banners
    setTimeout(function(){
        $('.alert').fadeOut('slow');
    }, 5000);

    // 2. Select2 Initialization
    $('.select2').select2({ theme: 'bootstrap-5' });

    // 3. Edit Mode Preload Variables (Read directly from PHP)
    var editStateId = "<?= !empty($state_id) ? $state_id : (!empty($ROW['state_id']) ? $ROW['state_id'] : '') ?>";
    var editHqId    = "<?= !empty($ROW['hq_id']) ? $ROW['hq_id'] : '' ?>";
    var editMrId    = "<?= !empty($ROW['mr_id']) ? $ROW['mr_id'] : '' ?>";

    // 4. AJAX: Load HQs by State with safe callback
    function loadHQ(state_id, selected_hq_id = '', callback = null) {
        if (!state_id) {
            $('#hq_id').html('<option value="">Select Head Quarter</option>').trigger('change.select2');
            $('#mr_id').html('<option value="">Select MR</option>').trigger('change.select2');
            return;
        }

        $.ajax({
            url: '<?= BASE_URL ?>customer/getHQs',
            type: 'POST',
            data: {
                state_id: state_id,
                selected_id: selected_hq_id
            },
            success: function(response) {
                $('#hq_id').html(response);
                if (selected_hq_id) {
                    $('#hq_id').val(selected_hq_id);
                }
                $('#hq_id').trigger('change.select2');

                if (typeof callback === 'function') {
                    callback();
                }
            }
        });
    }

    // 5. AJAX: Load MRs by HQ (FIX: sends selected_id to match controller)
    function loadMR(hq_id, selected_mr_id = '') {
        if (!hq_id) {
            $('#mr_id').html('<option value="">Select MR</option>').trigger('change.select2');
            return;
        }

        $.ajax({
            url: '<?= BASE_URL ?>financial/getMRs',
            type: 'POST',
            data: {
                hq_id: hq_id,
                selected_id: selected_mr_id // Matches $_POST['selected_id'] in getMRs()
            },
            success: function(response) {
                $('#mr_id').html(response);
                if (selected_mr_id) {
                    $('#mr_id').val(selected_mr_id);
                }
                $('#mr_id').trigger('change.select2');
            }
        });
    }

    // 6. Chained Preload on Edit Page
    if (editStateId) {
        $('#state_id').val(editStateId);
        loadHQ(editStateId, editHqId, function() {
            if (editHqId) {
                loadMR(editHqId, editMrId);
            }
        });
    }

    // 7. Events on User Selection Change
    $('#state_id').on('change', function () {
        var stateId = $(this).val();
        $('#mr_id').html('<option value="">Select MR</option>').trigger('change.select2');
        loadHQ(stateId);
    });

    // Native and Select2 change for HQ
    $('#hq_id').on('select2:select change', function (e) {
        // Only run when changed by user interaction (not programmatic load)
        if (e.originalEvent || e.params) {
            var hqId = $(this).val();
            loadMR(hqId);
        }
    });
});
</script>