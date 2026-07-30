 <?php 
// 3. Include the bottom layout and scripts
include 'view/layout/header.php'; 
?>

 <style>
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
    </style>
 
 
 <div id="container">
                <div class="detail">
                    <a href="<?= BASE_URL ?>login/logout" style="float:right">
                        <button type="button" class="btn btn-secondary btn-sm">Logout</button>
                    </a>
                </div>
<a href="javascript:history.back()" class="btn btn-secondary">
    <i class="fa fa-arrow-left"></i> Back
</a>
                <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">
                <h3> Head Quarter Wise Customer Report</h3>

                <div class="container border px-3 py-3">
                    <form method="GET" class="row mb-3">
                        <div class="col-lg-3">
                                    <div class="form-group">
                                        <label>Select State</label>
                                        <select name="state" id="state_id" class="form-control" required>
                                            <option value="">Select State</option>

                                            <?php if($states): ?>
                                                <?php while($srow = mysqli_fetch_assoc($states)): ?>
                                                    <option value="<?= $srow['state_id']; ?>"
                                                        <?= ($state_id == $srow['state_id']) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($srow['state_name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                               <div class="col-lg-3">
                                    <div class="form-group">
                                        <label>Head Quarter</label>
                                        <select name="hq_id" id="hq_id" class="form-control select2" required>
                                                <option value="">Select Head Quarter</option>
                                                <?php
                                                if($hqs){
                                                    while($hq = mysqli_fetch_assoc($hqs)){
                                                ?>
                                                    <option value="<?= $hq['m_id'] ?>"
                                                        <?= ($hq_id == $hq['m_id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($hq['hq_name']) ?>
                                                    </option>
                                                <?php
                                                    }
                                                }
                                                ?>
                                            </select>
                                    </div>
                                </div>

                        <div class="col-md-2">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                        </div>

                        <div class="col-md-2">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                        </div>

                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">Search</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table_container table-responsive pt-4">
                <div class="text-right mb-2">
</div>
                <table class="table" id="stockReportTable">
                    <thead class="table-secondary">
                        <tr>
                            <th>Sr No</th>
                            <th>Customer</th>
                            <th>Total Sales </th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                   <tbody>
<?php
$sr = 1;

$grand_sales = 0;
$grand_amount = 0;

if($query && mysqli_num_rows($query) > 0)
{
    while($row = mysqli_fetch_assoc($query))
    {
    $grand_sales += $row['total_sales'];
    $grand_amount += $row['total_amount'];
?>
    <tr>
        <td><?= $sr++ ?></td>

        <td><?= htmlspecialchars($row['customer_name']) ?></td>

        <td><?= number_format($row['total_sales']) ?></td>

        <td><?= number_format($row['total_amount'], 2) ?></td>

        <td>
             <a href="<?= BASE_URL ?>hq_customer_report/customerReport?c_id=<?= $row['c_id'] ?>&from_date=<?= $from_date ?>&to_date=<?= $to_date ?>" class="btn btn-info btn-sm">
                    View
                </a>
        </td>
    </tr>
<?php
    }
}
else
{
?>
    
<?php
}
?>
</tbody>

<tfoot class="table-dark">
    <tr>
        <th colspan="2" class="text-end">Grand Total</th>
        <th><?= number_format($grand_sales) ?></th>
        <th><?= number_format($grand_amount, 2) ?></th>
        <th></th>
    </tr>
</tfoot>
                </table>
            </div>
        </div>


<?php 
// 3. Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>

<script>
setTimeout(function () {
    document.querySelectorAll('.alert').forEach(function (alert) {
        alert.style.display = 'none';
    });
}, 5000);

$(document).ready(function () {

    $('#stockReportTable').DataTable({
        dom: 'Bfrtip',
        pageLength: 25,
        buttons: [{
            extend: 'excelHtml5',
            text: '<i class="fa fa-file-excel"></i> Export Excel',
            className: 'btn btn-success',
            title: 'Head Quarter Wise Customer Report',
            messageTop:
                'HQ : <?= addslashes($hq_name) ?> | ' +
                'From Date : <?= date("d-m-Y", strtotime($from_date)) ?> | ' +
                'To Date : <?= date("d-m-Y", strtotime($to_date)) ?>',
            filename: 'HQ_Customer_Report_<?= date("Ymd") ?>',
            footer: true,
            exportOptions: {
                columns: [0,1,2,3]
            }
        }]
    });

    // Values from PHP
    var state_id = $('#state_id').val();
    var hq_id    = <?= (int)$hq_id ?>;

    function loadHQ(state_id, hq_id = '')
    {
        if (!state_id) {

            $('#hq_id').html('<option value="">Select Head Quarter</option>');

            if ($('#hq_id').hasClass('select2-hidden-accessible')) {
                $('#hq_id').select2('destroy');
            }

            $('#hq_id').select2({
                theme: 'bootstrap-5'
            });

            return;
        }

        $.ajax({
            url: '<?= BASE_URL ?>customer/getHQs',
            type: 'POST',
            data: {
                state_id: state_id,
                selected_id: hq_id
            },
            success: function(response)
            {
                if ($('#hq_id').hasClass('select2-hidden-accessible')) {
                    $('#hq_id').select2('destroy');
                }

                $('#hq_id').html(response);

                $('#hq_id').select2({
                    theme: 'bootstrap-5'
                });
            }
        });
    }

    // Load HQ on page load
    if (state_id) {
        loadHQ(state_id, hq_id);
    }

    // Load HQ when state changes
    $('#state_id').on('change', function () {
        loadHQ($(this).val());
    });

});
</script>
