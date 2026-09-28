<?php 
include 'view/layout/header.php'; 
?>

<style>
    .detail {
        display: flex;
        justify-content: flex-end;
        padding: 6px;
    }
    .dt-search {
        float: right;
    }
    .btn-search {
        margin-top: 1.8rem !important;
    }
    .dt-paging {
        float: right;
        margin-top: -20px;
    }
</style>

<div id="container">
    <div class="detail">
        <a href="<?= BASE_URL ?>login/logout" style="float:right">
            <button type="button" class="btn btn-secondary btn-sm">Logout</button>
        </a>
    </div>

    <a href="javascript:history.back()" class="btn btn-secondary btn-sm mb-2">
        <i class="fa fa-arrow-left"></i> Back
    </a>

    <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">
    <h3>Primary Sale Report</h3>

    <div class="container border px-3 py-3 mb-4">
        <form method="GET" action="" class="row g-2">
            <!-- State Dropdown -->
            <div class="col-lg-3">
                <div class="form-group">
                    <label><strong>Select State</strong></label>
                    <select name="state" id="state_id" class="form-control select2" required>
                        <option value="">Select State</option>
                        <?php if ($states): ?>
                            <?php while ($srow = mysqli_fetch_assoc($states)): ?>
                                <option value="<?= $srow['state_id']; ?>" <?= ($state_id == $srow['state_id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($srow['state_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- Headquarter Dropdown -->
            <div class="col-lg-3">
                <div class="form-group">
                    <label><strong>Head Quarter</strong></label>
                    <select name="hq_id" id="hq_id" class="form-control select2" required>
                        <option value="">Select Head Quarter</option>
                        <?php if ($hqs): ?>
                            <?php while ($hq = mysqli_fetch_assoc($hqs)): ?>
                                <?php $h_id = $hq['headquarter_id'] ?? $hq['m_id']; ?>
                                <option value="<?= $h_id; ?>" <?= ($hq_id == $h_id) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($hq['hq_name'] ?? $hq['headquarter_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- Stockist Dropdown -->
            <div class="col-lg-2">
                <div class="form-group">
                    <label><strong>Stockist</strong></label>
                    <select name="stockist_id" id="stockist_id" class="form-control select2">
                        <option value="">All Stockists</option>
                        <?php if ($stockists): ?>
                            <?php while ($stock = mysqli_fetch_assoc($stockists)): ?>
                                <option value="<?= $stock['stockist_id']; ?>" <?= ($stockist_id == $stock['stockist_id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($stock['stockist_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- Start Date -->
            <div class="col-lg-2">
                <div class="form-group">
                    <label><strong>Start Date</strong></label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                </div>
            </div>

            <!-- End Date -->
            <div class="col-lg-2">
                <div class="form-group">
                    <label><strong>End Date</strong></label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
                </div>
            </div>

            <div class="col-lg-12 text-end mt-3">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fa fa-search"></i> Search
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table_container table-responsive pt-2">
        <table class="table table-bordered table-striped table-hover" id="stockReportTable">
            <thead class="table-secondary">
                <tr>
                    <th class="text-center">Sr. No</th>
                    <th>Invoice No</th>
                    <th class="text-center">Date</th>
                    <th>Stockist Name</th>
                    <th class="text-end">Amount (₹)</th>
                    <th class="text-end">Business Value (PTS)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sr = 1;
                $grand_total_amount = 0;
                $grand_total_pts = 0;

                if ($query && mysqli_num_rows($query) > 0) {
                    while ($row = mysqli_fetch_assoc($query)) {
                        $inv_amt = (float)($row['grand_total'] ?? $row['total_amount'] ?? 0);
                        $pts_val = (float)($row['business_value'] ?? $row['total_sales'] ?? 0);

                        $grand_total_amount += $inv_amt;
                        $grand_total_pts    += $pts_val;
                ?>
                    <tr>
                        <td class="text-center"><?= $sr++ ?></td>
                        <td><?= htmlspecialchars($row['inward_no'] ?? $row['inv_no'] ?? '-') ?></td>
                        <td class="text-center"><?= !empty($row['inward_date']) ? date('d-m-Y', strtotime($row['inward_date'])) : '-' ?></td>
                        <td><?= htmlspecialchars($row['stockist_name'] ?? $row['customer_name'] ?? '-') ?></td>
                        <td class="text-end"><?= number_format($inv_amt, 2) ?></td>
                        <td class="text-end"><?= number_format($pts_val, 2) ?></td>
                    </tr>
                <?php
                    }
                } else {
                ?>
                  
                <?php
                }
                ?>
            </tbody>
            <tfoot class="table-dark">
                <tr>
                    <th colspan="4" class="text-end">Grand Total</th>
                    <th class="text-end">₹ <?= number_format($grand_total_amount, 2) ?></th>
                    <th class="text-end">₹ <?= number_format($grand_total_pts, 2) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php include 'view/layout/footer.php'; ?>

<script>
$(document).ready(function () {
    // 1. Initialize Select2
    $('.select2').select2({ theme: 'bootstrap-5' });

    // 2. Initialize DataTable
    if ($('#stockReportTable').length) {
        $('#stockReportTable').DataTable({
            dom: 'Bfrtip',
            pageLength: 25,
            buttons: [{
                extend: 'excelHtml5',
                text: '<i class="fa fa-file-excel"></i> Export Excel',
                className: 'btn btn-success',
                title: 'Primary Sale Report',
                filename: 'Primary_Sale_Report_<?= date("Ymd") ?>',
                footer: true,
                exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
            }]
        });
    }

    // 3. Read PHP values from GET request
    var state_id    = <?= (int)$state_id ?>;
    var hq_id       = <?= (int)$hq_id ?>;
    var stockist_id = <?= (int)$stockist_id ?>;

    // Load HQs via AJAX
    function loadHQ(stateId, selectedHq = '', callback = null) {
        if (!stateId) {
            $('#hq_id').html('<option value="">Select Head Quarter</option>').trigger('change.select2');
            $('#stockist_id').html('<option value="">All Stockists</option>').trigger('change.select2');
            return;
        }

        $.ajax({
            url: '<?= BASE_URL ?>customer/getHQs',
            type: 'POST',
            data: { 
                state_id: stateId, 
                selected_id: selectedHq 
            },
            success: function (response) {
                $('#hq_id').html(response);
                if (selectedHq) {
                    $('#hq_id').val(selectedHq);
                }
                $('#hq_id').trigger('change.select2');

                if (typeof callback === 'function') {
                    callback();
                }
            }
        });
    }

    // Load Stockists via AJAX
    function loadStockist(hqId, selectedStockist = '') {
        if (!hqId) {
            $('#stockist_id').html('<option value="">All Stockists</option>').trigger('change.select2');
            return;
        }

        $.ajax({
            url: '<?= BASE_URL ?>stock_inward/getStockists',
            type: 'POST',
            data: { 
                hq_id: hqId, 
                selected_id: selectedStockist 
            },
            success: function (response) {
                $('#stockist_id').html(response);
                if (selectedStockist) {
                    $('#stockist_id').val(selectedStockist);
                }
                $('#stockist_id').trigger('change.select2');
            }
        });
    }

    // 4. Initial Trigger on Page Load (When URL has parameters)
    if (state_id > 0) {
        loadHQ(state_id, hq_id, function () {
            if (hq_id > 0) {
                loadStockist(hq_id, stockist_id);
            }
        });
    }

    // 5. Change Events for User Interaction
    $('#state_id').on('select2:select change', function (e) {
        if (e.originalEvent || e.params) {
            var selectedState = $(this).val();
            $('#stockist_id').html('<option value="">All Stockists</option>').trigger('change.select2');
            loadHQ(selectedState);
        }
    });

    $('#hq_id').on('select2:select change', function (e) {
        if (e.originalEvent || e.params) {
            var selectedHq = $(this).val();
            loadStockist(selectedHq);
        }
    });
});
</script>