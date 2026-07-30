<?php 
// 3. Include the bottom layout and scripts
include 'view/layout/header.php'; 
?>

<style>
            .detail {
    display: flex;
    justify-content: flex-end; /* Aligns the content to the right */
    padding: 6px; /* Optional: Adds some padding around the container */
}
        </style>

                    <div id="container">
                        <div class="detail">
                        
                        <a href="<?= BASE_URL ?>login/logout" style="float:right"><button type="button" class="btn btn-secondary btn-sm">Logout</button></a>
                        </div>
                        <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">
                        
                    <!-- ===  Flash Messages  === -->
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">Product saved successfully!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['deleted'])): ?>
                        <div class="alert alert-info">Product deleted successfully!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger">Something went wrong. Please try again.</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['duplicate'])): ?>
                        <div class="alert alert-warning">Product name already exists!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['toggled'])): ?>
                        <div class="alert alert-info">Product status updated.</div>
                    <?php endif; ?>

                         <h3>Batches Update  </h3>
                       <form method="post" action="<?= BASE_URL ?>batch_status/store">

                            <div class="container border px-3 py-3">

                                <div class="row">

                                  
                                    <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Select Batch</label>

                                    <select name="batch_no" class="form-control" required>
                                    <option value="">Select Batch</option>

                                   <?php foreach($batchNumbers as $bn){ ?>
                                                        <option value="<?= $bn ?>">
                                                            <?= $bn ?>
                                                        </option>
                                                        <?php } ?>
                                </select>

                                    </div>
                                </div>


                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>Status</label><br>

                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio"
                                                    name="status" id="active" value="1" <?php if(isset($ROW['status']) && $ROW['status']=='1') echo 'checked'; ?> checked >
                                                <label class="form-check-label" for="active">
                                                    Active
                                                </label>
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio"
                                                    name="status" id="inactive" value="0" <?php if(isset($ROW['status']) && $ROW['status']=='0') echo 'checked'; ?>>
                                                <label class="form-check-label" for="inactive">
                                                    Inactive
                                                </label>
                                            </div>

                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="py-2">
                                        <div class="form-group">
                                            <button type="submit" id="save" name="save"
                                                class="btn btn-success btn-sm" style="width:30%">
                                                Save
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </form>
                       
                </div>
                <div class="table_container table-responsive pt-4">
                        <table class="table">
                            <thead class="table-secondary ">
                                <tr>
                                    <th class="text-center">Sr. No</th>
                                    <th class="text-center">Batches</th>
                                    <th class="text-center">State</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                              <?php
                               $key=1;  
                               while($row=mysqli_fetch_array($list)){
                                ?>
                                <tr>
                                <td class="text-center"><?php echo $key;?></td>
                                <td class="text-center"><?php echo $row['batch_no']?></td>
                                 <td class="text-center"><?php echo $row['state_name']?></td>
                                <td class="text-center"><?php if ($row['status'] == '1'){echo 'Active'; } else {Echo 'Inactive';} ?></td>
                                </tr>
                                <?php
                                 $key++;
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
  
   

