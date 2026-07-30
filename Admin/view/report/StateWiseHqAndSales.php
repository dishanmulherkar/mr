<?php 
// 3. Include the bottom layout and scripts
include 'view/layout/header.php'; 
?> 
 
<style>
        .detail {
            display: inline;
            justify-content: flex-end;
            padding: 6px;
        }
        .dt-search{
            float:right;
        }
        .btn-primary{
            margin-top: 0px;
        }
        .dt-paging{
            float: right;
            margin-top: -20px;
        }
</style> 
 
 <div id="container">
                        <a href="javascript:history.back()" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                        <div class="detail">
                        
                        <a href="<?= BASE_URL ?>login/logout" style="float:right"><button type="button" class="btn btn-secondary btn-sm">Logout</button></a>
                        </div>
                        <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">
                         <h3> State Wise HQ And Sales Report </h3>
                       

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
                                    

                                    <!-- Month -->
                                   <div class="col-md-2">
                                <label>From Date</label>
                            <input type="date"
                                   name="from_date"
                                   class="form-control"
                                   value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label>To Date</label>
                            <input type="date"
                                   name="to_date"
                                   class="form-control"
                                   value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>">
                            </div>

                                    <div class="col-md-1">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-primary btn-block">
                                            Search
                                        </button>
                                    </div>

                                </form>

                            </div>

                       
                </div>
                <div class="table_container table-responsive pt-4">
                        <table class="table" id= "stockReportTable">
                            <thead class="table-secondary ">
                                <tr>
                                    <th class="text-center">Sr. No</th>
                                    <th class="text-center">HQ Name</th>
                                    <th class="text-center">Inward</th>
                                    <th class="text-center">Secondary Sales</th>
                                    <!-- <th class="text-center">Action</th> -->
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            if($query)
                            {
                                $key = 1;

                                while($row = mysqli_fetch_assoc($query))
                                {
                            ?>
                                    <tr>
                                        <td class="text-center"><?= $key ?></td>
                                        <td class="text-center"><?= $row['hq_name'] ?></td>
                                        <td class="text-center"><?= $row['inward_amount'] ?></td>
                                        <td class="text-center"><?= $row['sales_amount'] ?></td>
                                    </tr>
                            <?php
                                    $key++;
                                }
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
            </div>
    </div>

    <?php 
// 3. Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>    

<script>
setTimeout(function(){
    let alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert){
        alert.style.display = 'none';
    });
}, 5000);
</script>
  
    <script>
       

    $(document).ready(function(){

     $('#stockReportTable').DataTable({
        dom: 'Bfrtip',
        pageLength: 25,
        buttons: [{
            extend: 'excelHtml5',
            text: '<i class="fa fa-file-excel"></i> Export Excel',
            className: 'btn btn-success',
            title: 'State Wise HQ And Sales Report',
            messageTop:
                'State : <?= addslashes($state_name) ?> | ' +
                'From Date : <?= date("d-m-Y", strtotime($from_date)) ?> | ' +
                'To Date : <?= date("d-m-Y", strtotime($to_date)) ?>',
            filename: 'State_HQ_Report_<?= date("Ymd") ?>',
            footer: true,
            exportOptions: {
                columns: [0,1,2,3]
            }
        }]
    });

    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5'
    });
    

});
    </script>