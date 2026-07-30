 <?php 
// 3. Include the bottom layout and scripts
include 'view/layout/header.php'; 
?>
 
 
 <div id="container">
                        <div class="detail">
                        
                        <a href="<?= BASE_URL ?>login/logout" style="float:right"><button type="button" class="btn btn-secondary btn-sm">Logout</button></a>
                        </div>
                            <button type="button"
        class="btn btn-success"
        data-bs-toggle="modal"
        data-bs-target="#importDistrictModal" style="float:right; margin-top:20px;">
    Import
</button>
                        <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">
                        <h3><?php echo isset($ROW['district_id']) ? 'Edit' : 'Add'; ?> District</h3>
                      

                     
                       <form method="post" action="<?= isset($ROW['district_id']) ? BASE_URL . 'district/update/' . $ROW['district_id'] : BASE_URL . 'district/store'; ?>">
                            <div class="container border px-3 py-3">

                                <div class="row">

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>District Name</label>
                                            <input type="text" name="district_name" id="district_name"
                                                class="form-control"  value="<?php echo isset($ROW['district_name']) ? $ROW['district_name'] : ''; ?>"
                                                placeholder="Enter District Name">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                                 <label>Select State</label>
                                                    <select name="state_id" id="state_id" class="form-control" required>

                                                        <option value="">Select State</option>

                                                        <?php 
                                                        while ($row = mysqli_fetch_assoc($states))
                                                        {  
                                                        ?>
                                                            <option value="<?php echo $row['state_id']; ?>"
                                                                <?php 
                                                                    if(isset($ROW['state_id']) && $ROW['state_id'] == $row['state_id']) 
                                                                    { 
                                                                        echo "selected"; 
                                                                    } 
                                                                ?>>
                                                                
                                                                <?php echo $row['state_name']; ?>
                                                            
                                                            </option>
                                                        <?php 
                                                        } 
                                                        ?>

                                                    </select>
                                        </div>

                                    </div>


                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>Status</label><br>

                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio"
                                                    name="status" id="active" value="1" <?php if(isset($ROW['district_status']) && $ROW['district_status']=='1') echo 'checked'; ?> checked >
                                                <label class="form-check-label" for="active">
                                                    Active
                                                </label>
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio"
                                                    name="status" id="inactive" value="0" <?php if(isset($ROW['district_status']) && $ROW['district_status']=='0') echo 'checked'; ?>>
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
                                           <button type="submit" class="btn btn-primary">
            <?php echo isset($ROW['district_id']) ? 'Update' : 'Save'; ?>
        </button>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </form>
                       
                </div>
                <div class="table_container table-responsive pt-4">
                        <table class="table" id = "district_tab">
                            <thead class="table-secondary ">
                                <tr>
                                    <th class="text-center">Sr. No</th>
                                    <th class="text-center">State</th>
                                    <th class="text-center">District</th>
                                    <th class="text-center">Status</th>
                                    
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                              <?php
                               $key=1;

                            //    $query = mysqli_query($con,"SELECT * FROM `district` INNER JOIN `state`  ON  district.state_id= state.state_id where state_status = 1");
                               while($row=mysqli_fetch_array($list)){


                                ?>
                                <tr>
                                <td class="text-center"><?php echo $key;?></td>
                                <td class="text-center"><?php echo $row['state_name']?></td>
                                <td class="text-center"><?php echo $row['district_name']?></td>
                                <td class="text-center"><?php if ($row['district_status'] == 1){echo 'Active'; } else {Echo 'Inactive';} ?></td>
                                <td class="text-center">
                                    <a href="<?= BASE_URL ?>district/edit/<?php echo $row['district_id']; ?>"
                                    class="btn btn-sm btn-primary">
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
            </div>
    </div>


    <!-- POp for Import   -->
<div class="modal fade" id="importDistrictModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <form action="<?= BASE_URL ?>district/import" method="POST" enctype="multipart/form-data">

                <div class="modal-header">
                    <h5 class="modal-title">Import District Excel</h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="form-group">
                        <label>Select State <span class="text-danger">*</span></label>

                        <select name="state_id" class="form-control" required>

                            <option value="">Select State</option>

                            <?php
                            mysqli_data_seek($states,0);

                            while($state = mysqli_fetch_assoc($states))
                            {
                            ?>
                                <option value="<?= $state['state_id']; ?>">
                                    <?= $state['state_name']; ?>
                                </option>
                            <?php
                            }
                            ?>

                        </select>

                    </div>

                    <div class="form-group">
                        <label>Select Excel</label>

                        <input type="file"
                               name="excel"
                               class="form-control"
                               accept=".xls,.xlsx"
                               required>
                    </div>

                    <small class="text-danger">
                        Excel Format :
                    </small>

                    <table class="table table-bordered mt-2">
                        <tr>
                            <th>District Name</th>
                        </tr>
                        <tr>
                            <td>Ahmedabad</td>
                        </tr>
                        <tr>
                            <td>Surat</td>
                        </tr>
                        <tr>
                            <td>Vadodara</td>
                        </tr>
                    </table>

                </div>

                <div class="modal-footer">

                    <button class="btn btn-primary">
                        Import
                    </button>

                </div>

            </form>

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
        let table = new DataTable('#district_tab');
    </script>
    
