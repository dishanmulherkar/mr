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
    <?php  ?>
                <div class="detail">
                    <a href="<?= BASE_URL ?>login/logout" style="float:right">
                        <button type="button" class="btn btn-secondary btn-sm">Logout</button>
                    </a>
                </div>
<a href="javascript:history.back()" class="btn btn-secondary">
    <i class="fa fa-arrow-left"></i> Back
</a>
                <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">
                <h3> Stock And Sales Super Stockist</h3>

                <div class="container border px-3 py-3">
                    <form method="GET" class="row mb-3">
                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <label>Select Super Stockist</label>
                                         <select name="sup_stockist" id="sup_stockist" class="form-control" required>
                                            <option value="">Select Super Stockist </option>
                                            <?php while($srow = mysqli_fetch_assoc($stockist)): ?>
                                                <option value="<?php echo htmlspecialchars($srow['super_stockist_id']); ?>"
                                                    <?php if(isset($stockist_id) && $stockist_id == $srow['super_stockist_id']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($srow['ss_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
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
            <!-- <th>Batch</th>
            <th>Rate</th> -->
            <!-- <th>PTS</th> -->
            <th>Opening</th>
            <th>Inward</th>
            <th>Adj (−)</th>        <!-- NEW -->
            <th>Sales</th>
            <th>SL AMT</th>
            <!-- <th>SL AMT (PTS)</th> -->
            <th>Closing</th>
            <!-- <th>CL AMT</th> -->
            <!-- <th>CL AMT (PTS)</th> -->
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
        // $sale_rate = $row['sale_rate'] ;
        // $pts = $sale_rate - (($sale_rate * $discount) / 100);

        // $closing_amount = $closing * (float)$row['sale_rate'];
        // $cl_amt_pts = $closing * (float)$pts;

        $total_opening     += $row['opening_stock'];
        $total_inward      += $row['inward_qty'];
        $total_adjustment  += $row['adjustment_qty'];
        $total_sales       += $row['sales_qty'];
        $total_sale_amt    += $row['total_amount'];
        $total_closing     += $closing;
        // $total_closing_amt += $closing_amount;
        // $total_closing_amt_pts += $cl_amt_pts;
?>
        <tr>
            <td><?= $i ?></td>
            <td><?= htmlspecialchars($row['product_name']) ?></td>
            <!-- <td><?= htmlspecialchars($row['batch_no']) ?></td>
            <td><?= number_format((float)$row['sale_rate'], 2) ?></td> -->
            <!-- <td><?= number_format((float)$pts, 2) ?></td> -->
            <td><?= $row['opening_stock'] ?></td>
            <td><?= $row['inward_qty'] ?></td>
            <td class="text-danger">
                <?= $row['adjustment_qty'] > 0 ? '- '.$row['adjustment_qty'] : '—' ?>
            </td>
            <td><?= $row['sales_qty'] ?></td>
            <td><?= number_format($row['total_amount'], 2) ?></td>
            <td><?= $closing ?></td>
            <!-- <td><?= number_format($closing_amount, 2) ?></td> -->
            <!-- <td><?= number_format($cl_amt_pts, 2) ?></td> -->
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
    <td colspan="2" class="text-end">Total</td>

    <td><?= $total_opening ?></td>

    <td><?= $total_inward ?></td>

    <td class="text-danger">
        <?= $total_adjustment ?>
    </td>

    <td><?= $total_sales ?></td>

    <td><?= number_format($total_sale_amt, 2) ?></td>

    <td><?= $total_closing ?></td>

    <!-- <td><?= number_format($total_closing_amt, 2) ?></td> -->
    <!-- <td><?= number_format($total_closing_amt_pts, 2) ?></td> -->
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

 

});
</script>

