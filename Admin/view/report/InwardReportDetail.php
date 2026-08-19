 <?php 
include 'view/layout/header.php'; 
?>

                    <div id="container">
                        <a href="javascript:history.back()" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                        <div class="detail">
                        
                        <a href="<?= BASE_URL ?>login/logout" style="float:right"><button type="button" class="btn btn-secondary btn-sm">Logout</button></a>
                        </div>
                        <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h3 class="mb-0 text-center">Inward Stock Details</h3>
                                </div>

                                <div class="card-body">
                                    <div class="row">

                                        <div class="col-md-6 mb-2">
                                            <strong>Stockist Name :</strong>
                                            <?= htmlspecialchars($row1['stockist_name']) ?>
                                        </div>

                                        <div class="col-md-6 mb-2 text-md-right">
                                            <strong>Inward Date :</strong>
                                            <?= date('d-m-Y', strtotime($row1['inward_date'])) ?>
                                        </div>

                                    </div>
                                </div>
                            </div>
              
                       
                        </div>
                                <div class="table_container table-responsive pt-4">
                                        <table class="table table-bordered">
                                                <thead>
                                                <tr>
                                                        <th>Sr No</th>
                                                        <th>Product Name</th>
                                                        <th>Batch No</th>
                                                        <th>Qty</th>
                                                        <th>Rate</th>
                                                        <th>Amount</th>
                                                    </tr>
                                                </thead>

                                                <tbody>

                                                <?php
                                                $i=1;
                                                $grand_total=0;
                                            
                                                while($row = mysqli_fetch_assoc($query))
                                                {
                                                    $qty  = (int)$row['qty'];
                                                    $rate = (float)$row['rate'];
                                                    $amt  = 	(float)$row['net_total'];

                                                    $grand_total += $amt;
                                                ?>
                                                <tr>
                                                    <td><?= $i++ ?></td>
                                                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                                                    <td><?= htmlspecialchars($row['batch_no']) ?></td>
                                                    <td><?= $qty ?></td>
                                                    <td><?= number_format($rate, 2) ?></td>
                                                    <td><?= number_format($amt, 2) ?></td>
                                                </tr>
                                                <?php } ?>

                                                <tr>
                                                    <th colspan="5" class="text-right">Grand Total</th>
                                                    <th><?= number_format($grand_total,2) ?></th>
                                                </tr>

                                                </tbody>
                                            </table>
                                </div>
                      </div>
<?php 
// 3. Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>