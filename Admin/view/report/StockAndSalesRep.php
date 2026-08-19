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
                <h3> Stock And Sales Report</h3>

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
                                                if(isset($hqs) && $hqs)
                                                {
                                                    while($hq = mysqli_fetch_assoc($hqs))
                                                    {
                                                ?>
                                                    <option value="<?= $hq['m_id']; ?>"
                                                        <?= ($hq_id == $hq['m_id']) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($hq['hq_name']); ?>
                                                    </option>
                                                <?php
                                                    }
                                                }
                                                ?>
                                            </select>
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <label>Stockist</label>
                                        <select name="stockist_id" id="stockist_id" class="form-control" required>
                                            <option value="">Select Stockist</option>
                                            <?php
                                            if(isset($stockists) && $stockists)
                                            {
                                                while($stock = mysqli_fetch_assoc($stockists))
                                                {
                                            ?>
                                                <option value="<?= $stock['stockist_id']; ?>"
                                                    <?= ($stockist_id == $stock['stockist_id']) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($stock['stockist_name']); ?>
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
               <table class="table" id="stockReportTable">
    <thead class="table-secondary">
        <tr>
            <th>Sr No</th>
            <th>Product</th>
            <th>Batch</th>
            <th>Rate</th>
            <th>PTS</th>
            <th>Opening</th>
            <th>Inward</th>
            <th>Adj (−)</th>        <!-- NEW -->
            <th>Sales</th>
            <th>SL AMT</th>
            <!-- <th>SL AMT (PTS)</th> -->
            <th>Closing</th>
            <th>CL AMT</th>
            <th>CL AMT (PTS)</th>
        </tr>
    </thead>
    <tbody>
       <?php
$i = 0;

$total_opening     = 0;
$total_inward      = 0;
$total_adjustment  = 0;
$total_sales       = 0;
$total_sale_amt    = 0;
$total_closing     = 0;
$total_closing_amt = 0;
$total_closing_amt_pts = 0;

if($query && mysqli_num_rows($query) > 0)
{
    while($row = mysqli_fetch_assoc($query))
    {
        $i++;

        // Closing = Opening + Inward - Adjustment - Sales
        $closing = $row['opening_stock']
                 + $row['inward_qty']
                 - $row['adjustment_qty']
                 - $row['sales_qty'];
        $discount = $row['disc'] ;
        $sale_rate = $row['sale_rate'] ;
        $pts = $sale_rate - (($sale_rate * $discount) / 100);

        $closing_amount = $closing * (float)$row['sale_rate'];
        $cl_amt_pts = $closing * (float)$pts;

        $total_opening     += $row['opening_stock'];
        $total_inward      += $row['inward_qty'];
        $total_adjustment  += $row['adjustment_qty'];
        $total_sales       += $row['sales_qty'];
        $total_sale_amt    += $row['total_amount'];
        $total_closing     += $closing;
        $total_closing_amt += $closing_amount;
        $total_closing_amt_pts += $cl_amt_pts;
?>
        <tr>
            <td><?= $i ?></td>
            <td><?= htmlspecialchars($row['product_name']) ?></td>
            <td><?= htmlspecialchars($row['batch_no']) ?></td>
            <td><?= number_format((float)$row['sale_rate'], 2) ?></td>
            <td><?= number_format((float)$pts, 2) ?></td>
            <td><?= $row['opening_stock'] ?></td>
            <td><?= $row['inward_qty'] ?></td>
            <td class="text-danger">
                <?= $row['adjustment_qty'] > 0 ? '- '.$row['adjustment_qty'] : '—' ?>
            </td>
            <td><?= $row['sales_qty'] ?></td>
            <td><?= number_format($row['total_amount'], 2) ?></td>
            <td><?= $closing ?></td>
            <td><?= number_format($closing_amount, 2) ?></td>
            <td><?= number_format($cl_amt_pts, 2) ?></td>
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
<tfoot class="table-secondary fw-bold">
<tr>
    <td colspan="5" class="text-end">Total</td>

    <td><?= $total_opening ?></td>

    <td><?= $total_inward ?></td>

    <td class="text-danger">
        <?= $total_adjustment ?>
    </td>

    <td><?= $total_sales ?></td>

    <td><?= number_format($total_sale_amt, 2) ?></td>

    <td><?= $total_closing ?></td>

    <td><?= number_format($total_closing_amt, 2) ?></td>
    <td><?= number_format($total_closing_amt_pts, 2) ?></td>
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


$(document).ready(function () {


    setTimeout(function () {
    document.querySelectorAll('.alert').forEach(function (alert) {
        alert.style.display = 'none';
    });
}, 5000);

$(document).ready(function () {

    $('#stockReportTable').DataTable({
        destroy: true,
        responsive: true,
        pageLength: 50,
        dom: 'Bfrtip',

        language: {
            emptyTable: "No records found"
        },

        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fa fa-file-excel"></i> Export Excel',
                className: 'btn btn-success',

                filename: 'Stock_Sales_Report',
                title: 'Stock And Sales Report',

                messageTop:
                    'HQ : <?= addslashes($hq_name) ?> | ' +
                    'Stockist : <?= addslashes($stockist_name) ?> | ' +
                    'From Date : <?= date("d-m-Y", strtotime($from_date)) ?> | ' +
                    'To Date : <?= date("d-m-Y", strtotime($to_date)) ?>',

                footer: true,

                exportOptions: {
                    columns: ':visible'
                    // or use:
                    // columns: [0,1,2,3,4,5,6,7,8,9,10]
                }
            }
        ]
    });

});

 // Selected values from PHP (GET)
    var state_id     = <?= (int)$state_id ?>;
    var hq_id        = <?= (int)$hq_id ?>;
    var stockist_id  = <?= (int)$stockist_id ?>;

    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5'
    });

    // ============================
    // Load HQ
    // ============================
    function loadHQ(state_id, selected_hq = '')
    {
        if (!state_id)
        {
            $('#hq_id').html('<option value="">Select Head Quarter</option>');
            $('#stockist_id').html('<option value="">Select Stockist</option>');
            return;
        }

        $.ajax({
            url: '<?= BASE_URL ?>customer/getHQs',
            type: 'POST',
            data: {
                state_id: state_id,
                selected_id: selected_hq
            },
            success: function(response)
            {
                $('#hq_id').html(response);

                // If page loaded with HQ selected, load stockists
                if(selected_hq){
                    loadStockist(selected_hq, stockist_id);
                }
            }
        });
    }

    // ============================
    // Load Stockists
    // ============================
    function loadStockist(hq_id, selected_stockist = '')
    {
        if (!hq_id)
        {
            $('#stockist_id').html('<option value="">Select Stockist</option>');
            return;
        }

        $.ajax({
            url: '<?= BASE_URL ?>stock_inward/getStockists',
            type: 'POST',
            data: {
                hq_id: hq_id,
                selected_id: selected_stockist
            },
            success: function(response)
            {
                $('#stockist_id').html(response);
            }
        });
    }

    // ============================
    // State Change
    // ============================
    $('#state_id').on('change', function(){

        var state = $(this).val();

        $('#hq_id').html('<option value="">Select Head Quarter</option>');
        $('#stockist_id').html('<option value="">Select Stockist</option>');

        loadHQ(state);

    });

    // ============================
    // HQ Change
    // ============================
    $('#hq_id').on('change', function(){

        var hq = $(this).val();

        $('#stockist_id').html('<option value="">Select Stockist</option>');

        loadStockist(hq);

    });

    // ============================
    // Page Load (Search/Edit)
    // ============================
    if(state_id > 0)
    {
        loadHQ(state_id, hq_id);
    }

});
</script>

