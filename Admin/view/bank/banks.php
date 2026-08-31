<?php 
// 3. Include the bottom layout and scripts
include 'view/layout/header.php'; 
?>
<div id="container">
                        <div class="detail">
                        
                        <a href="<?= BASE_URL ?>login/logout" style="float:right"><button type="button" class="btn btn-secondary btn-sm">Logout</button></a>
                        </div>
                        <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">
                         <h3>Add Bank </h3>
                       <form method="post" action="<?php echo isset($ROW['bank_id']) ? BASE_URL . 'bank/update/' . $ROW['bank_id'] : BASE_URL . 'bank/store'; ?>">

                            <div class="container border px-3 py-3">

                                <div class="row">

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>Bank Name</label>
                                            <input type="text" name="bank_name" id="bank_name"
                                                class="form-control"  value="<?php echo isset($ROW['bank_name']) ? $ROW['bank_name'] : ''; ?>"
                                                placeholder="Enter State Name">
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>Status</label><br>

                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio"
                                                    name="status" id="active" value="1" <?php if(isset($ROW['is_active']) && $ROW['is_active']=='1') echo 'checked'; ?> checked >
                                                <label class="form-check-label" for="active">
                                                    Active
                                                </label>
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio"
                                                    name="status" id="inactive" value="0" <?php if(isset($ROW['is_active']) && $ROW['is_active']=='0') echo 'checked'; ?>>
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
                                    <th class="text-center">State</th>
                                    <th class="text-center">Status</th>
                                    
                                    
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                              <?php
                               $key=1;
                               while($row=mysqli_fetch_array($list)){
                                ?>
                                <tr>
                                <td class="text-center"><?php echo $key;?></td>
                                <td class="text-center"><?php echo $row['bank_name']?></td>
                                <td class="text-center"><?php if ($row['is_active'] == 1){echo 'Active'; } else {Echo 'Inactive';} ?></td>
                                <td class="text-center">
                                    <a href="<?= BASE_URL ?>bank/edit/<?php echo $row['bank_id']; ?>"
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
 
