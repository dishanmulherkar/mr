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
                        
                        <a href="logout.php" style="float:right"><button type="button" class="btn btn-secondary btn-sm">Logout</button></a>
                        </div>
                        <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">
                     <h3>Add Notification</h3>

                    <form method="post" action="<?= isset($ROW['notification_id']) ? BASE_URL.'notification/update/'.$ROW['notification_id'] : BASE_URL.'notification/store' ?>">

                        <div class="container border rounded p-3">

                            <div class="row">
                                <!-- Notification Title -->
                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">Notification Title</label>
                                    <input type="text"
                                        name="title"
                                        class="form-control"
                                        placeholder="Enter Notification Title"
                                        value="<?php echo isset($ROW['title']) ? $ROW['title'] : ''; ?>"
                                        required>
                                </div>
                            </div>

                            <div class="row">

                                <div class="col-lg-6 mb-3">
                                    <label>Send To</label>
                                    <select name="send_type" id="send_type" class="form-control">
                                        <option value="all"
                                            <?= (isset($ROW['send_type']) && $ROW['send_type']=='all') ? 'selected' : ''; ?>>
                                            All HQ
                                        </option>

                                        <option value="selected"
                                            <?= (isset($ROW['send_type']) && $ROW['send_type']=='selected') ? 'selected' : ''; ?>>
                                            Selected HQ(s)
                                        </option>
                                    </select>
                                </div>


                                <?php
                            $selected_hq = [];

                            if(isset($ROW['hq_ids']) && $ROW['hq_ids'] != '')
                            {
                                $selected_hq = explode(',', $ROW['hq_ids']);
                            }
                            ?>
                                <div class="col-lg-6 mb-3" id="hq_div" style="display:none;">
                                    <label>Select HQ(s)</label>

                                    <select name="hq_ids[]" id="hq_ids" class="form-control select2" multiple>

                                    <?php
                                    // $hq = mysqli_query($con,"SELECT m_id,hq_name FROM mr_users WHERE status='Active' ORDER BY hq_name");

                                    while($row = mysqli_fetch_assoc($hq))
                                    {
                                    ?>
                                        <option value="<?= $row['m_id']; ?>"
                                            <?= in_array($row['m_id'], $selected_hq) ? 'selected' : ''; ?>>
                                            <?= $row['hq_name']; ?>
                                        </option>
                                    <?php
                                    }
                                    ?>

                                </select>
                                    </div>

                            </div>

                            <div class="row">

                                <!-- Notification Message -->
                                <div class="col-lg-12 mb-3">
                                    <label class="form-label">Notification Message</label>
                                    <textarea
                                        name="message"
                                        rows="5"
                                        class="form-control"
                                        placeholder="Enter Notification Message"><?php echo isset($ROW['message']) ? $ROW['message'] : ''; ?></textarea>
                                </div>
                            </div>

                            <div class="row">

                                <!-- Status -->
                                <div class="col-lg-6 mb-3">
                                    <label class="form-label d-block">Status</label>

                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input"
                                            type="radio"
                                            name="status"
                                            value="1"
                                            <?php if(isset($ROW['status']) && $ROW['status']=='1') echo 'checked'; else echo 'checked'; ?>>

                                        <label class="form-check-label">
                                            Active
                                        </label>
                                    </div>

                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input"
                                            type="radio"
                                            name="status"
                                            value="0"
                                            <?php if(isset($ROW['status']) && $ROW['status']=='0') echo 'checked'; ?>>

                                        <label class="form-check-label">
                                            Inactive
                                        </label>
                                    </div>
                                </div>

                            </div>

                            <div class="row">
                                <div class="col-lg-12">
                                    <button type="submit"
                                        name="save"
                                        class="btn btn-success">
                                        Save Notification
                                    </button>

                                <a href="notification" class="btn btn-secondary">
                                    Reset
                                        </a>
                                </div>
                            </div>

                        </div>

                    </form>
                       
                </div>
                <div class="table_container table-responsive pt-4">
                        <table class="table table-bordered table-hover" id="notificationTable">
                            <thead class="table-secondary">
                                <tr>
                                    <th class="text-center">Sr. No</th>
                                    <th class="text-center">Title</th>
                                    <th class="text-center">Message</th>
                                    <th class="text-center">Send To</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Created At</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $key = 1;
                                while($row = mysqli_fetch_assoc($query)):
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $key++; ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars(substr($row['message'], 0, 60)); ?>
                                        <?php if(strlen($row['message']) > 60) echo "..."; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                            echo ($row['send_type'] == 'all') 
                                                ? "All HQ" 
                                                : htmlspecialchars($controller->getNames($row['hq_ids'])); 
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo ($row['status'] == 1) 
                                            ? '<span class="badge bg-success">Active</span>' 
                                            : '<span class="badge bg-danger">Inactive</span>'; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo date('d-m-Y H:i', strtotime($row['created_at'])); ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= BASE_URL ?>notification/edit/<?php echo $row['notification_id']; ?>" 
                                        class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
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
$(document).ready(function () {

    $('#hq_ids').select2({
        placeholder: "Select HQ(s)",
        width: '100%'
    });

    function toggleHQ() {

        if($('#send_type').val() == 'selected'){
            $('#hq_div').show();
        }else{
            $('#hq_div').hide();
        }

    }

    toggleHQ(); // For edit mode

    $('#send_type').change(function(){
        toggleHQ();
    });

});
    </script>

