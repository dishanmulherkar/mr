 <?php 
// 3. Include the bottom layout and scripts
include 'view/layout/header.php'; 
?> 
 
 
 <div id="container">
                <div class="detail">
                            <a href="logout.php" style="float:right">
                                <button type="button" class="btn btn-secondary btn-sm">Logout</button>
                            </a>
                        </div>
                            <a href="javascript:history.back()" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                        <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">
                        <h3> Customer Monthly Report </h3>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h4 class="text-center"></h4>

                                    <div class="row">

                                        <div class="col-md-3">
                                            <strong>Customer Name :</strong> <?= htmlspecialchars($customer['customer_name']) ?>
                                        </div>


                                        <div class="col-md-3">
                                            <strong>From :</strong> <?= date("d-m-y", strtotime($from_date)) ?>
                                        </div>

                                        <div class="col-md-3">
                                            <strong>To :</strong> <?= date("d-m-y", strtotime($to_date)) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>   
                    </div>
            <div class="table_container table-responsive pt-4">
                <table class="table" id="CustomerReportTable">
                    <thead class="table-secondary">
                        <tr>
                           <th>Sr No</th>
                            <th>Product Name</th>
                            <th>Batch</th>
                            <th>Rate</th>
                            <th>Total Qty</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                   <tbody>  
                    <?php 
                    $sr = 1;
                    $grand_total = 0;
                    while($row=mysqli_fetch_assoc($query)){ ?>

                    <?php $grand_total += $row['total_amount']; ?>

                   <tr>
                        <td><?= $sr++ ?></td>
                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                        <td><?= htmlspecialchars($row['batch_no']) ?></td>
                        <td><?= number_format($row['rate'], 2) ?></td>
                        <td><?= $row['total_qty'] ?></td>
                        <td><?= number_format($row['total_amount'], 2) ?></td>
                    </tr>

                    <?php } ?>
                   
                    </tbody>
                    <tfoot>
                         <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>

                        <th  class="text-right">
                            Total Amount
                        </th>
                        <th>
                            <?= number_format($grand_total,2) ?>
                        </th>
                    </tr>
                    </tfoot>
                    </table>
                </div>

                    <?php 
// 3. Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>    
