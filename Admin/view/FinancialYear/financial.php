<?php 
$pageTitle = "Financial Year";
// 3. Include the bottom layout and scripts
include 'view/layout/header.php'; 
?>

<div id="container">
                        <div class="detail">
                        <a href="<?= BASE_URL ?>login/logout" style="float:right"><button type="button" class="btn btn-secondary btn-sm">Logout</button></a>
                        </div>
                        <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">
                         <h3>Financial</h3>
                       <form method="post"
                        action="<?= isset($ROW)
                            ? BASE_URL.'financial/update/'.$ROW['fy_id']
                            : BASE_URL.'financial/store'; ?>">

            <div class="container border px-3 py-3">

                <div class="row">
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label>Select State</label>
                           <select name="state" id="state_id" class="form-control" required>
                                <option value="">Select State</option>

                               <?php if($states && mysqli_num_rows($states) > 0): ?>
                                    <?php while($srow = mysqli_fetch_assoc($states)): ?>
                                        <option value="<?= $srow['state_id']; ?>" <?= (isset($state_id) && $state_id == $srow['state_id']) ? 'selected' : ''; ?>> <?= htmlspecialchars($srow['state_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>

                            </select>
                        </div>
                    </div>
                            <!-- HQ -->
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label>Head Quarter</label>
                                    <select name="hq_id" id="hq_id" class="form-control select2" required>
                                        <option value="">Select Head Quarter</option>
                                        </select>
                                </div>
                            </div>

                            <!-- FY Name -->
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Financial Year Name</label>
                                    <input type="text"
                                            name="fy_name"
                                            class="form-control"
                                            value="<?= isset($ROW['fy_name']) ? $ROW['fy_name'] : ''; ?>"
                                            placeholder="Example : FY 2026-27"
                                            required>
                                </div>
                            </div>

                            <!-- Start Date -->
                            <div class="col-lg-3 mt-3">
                                <div class="form-group">
                                    <label>Start Date</label>
                                    <input type="date"
                                        name="start_date"
                                        class="form-control"
                                        value="<?= isset($ROW['start_date']) ? $ROW['start_date'] : ''; ?>"
                                        required>
                                </div>
                            </div>

                            <!-- End Date -->
                            <div class="col-lg-3 mt-3">
                                <div class="form-group">
                                    <label>End Date</label>
                                    <input type="date"
                                        name="end_date"
                                        class="form-control"
                                        value="<?= isset($ROW['end_date']) ? $ROW['end_date'] : ''; ?>"
                                        required>
                                </div>
                            </div>

                            <!-- Target -->
                            <div class="col-lg-6 mt-3">
                                <div class="form-group">
                                    <label>Target Amount (₹)</label>
                                    <input type="number"
                                        step="0.01"
                                        min="0"
                                        name="target_amount"
                                        class="form-control"
                                        value="<?= isset($ROW['target_amount']) ? $ROW['target_amount'] : ''; ?>"
                                        placeholder="Enter Target Amount"
                                        required>
                                </div>
                            </div>

                        <!-- Status -->
                        <div class="col-lg-6 mt-3">
                            <div class="form-group">
                                <label>Status</label><br>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="status"
                                        value="1"
                                        id="active"
                                        <?= (!isset($ROW['status']) || $ROW['status']=='1') ? 'checked' : ''; ?>>

                                    <label class="form-check-label" for="active">
                                        Active
                                    </label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="status"
                                        value="0"
                                        id="inactive"
                                        <?= (isset($ROW['status']) && $ROW['status']=='0') ? 'checked' : ''; ?>>

                                    <label class="form-check-label" for="inactive">
                                        Inactive
                                    </label>
                                </div>

                            </div>
                        </div>

                    </div>

                    <div class="row mt-3">
                        <div class="col-lg-12">
                            <button type="submit"
                                    name="save"
                                    class="btn btn-success btn-sm"
                                    style="width:200px;">
                                Save Financial Year
                            </button>
                        </div>
                    </div>

    </div>

</form>
                       
                </div>
                <div class="table_container table-responsive pt-4">
                        <table class="table table-bordered table-hover" id="finanTable">
    <thead class="table-secondary">

        <!-- Heading -->
        <tr>
            <th class="text-center">Sr. No</th>
            <th class="text-center">State</th>
            <th class="text-center">MR</th>
            <th class="text-center">Financial Year</th>
            <th class="text-center">Start Date</th>
            <th class="text-center">End Date</th>
            <th class="text-center">Target (₹)</th>
            <th class="text-center">Status</th>
            <th class="text-center">Action</th>
        </tr>

        <!-- Column Search -->
        <tr>
            <th></th>

            <th>
                <input type="text" class="form-control form-control-sm" placeholder="Search State">
            </th>

            <th>
                <input type="text" class="form-control form-control-sm" placeholder="Search HQ">
            </th>

            <th>
                <input type="text" class="form-control form-control-sm" placeholder="Search FY">
            </th>

            <th>
                <input type="text" class="form-control form-control-sm" placeholder="Search Start">
            </th>

            <th>
                <input type="text" class="form-control form-control-sm" placeholder="Search End">
            </th>

            <th>
                <input type="text" class="form-control form-control-sm" placeholder="Search Target">
            </th>

            <th>
                <select class="form-select form-select-sm">
                    <option value="">All</option>
                    <option>Active</option>
                    <option>Inactive</option>
                </select>
            </th>

            <th></th>
        </tr>

    </thead>

    <tbody>

            <?php
            $key = 1;

          

            while($row = mysqli_fetch_assoc($query))
            {
            ?>

        <tr>

            <td class="text-center"><?php echo $key; ?></td>

            <td class="text-center">
                <?php echo $row['state_name']; ?>
            </td>

            <td class="text-center">
                <?php echo $row['mr_name']; ?>
            </td>

            <td class="text-center">
                <?php echo $row['fy_name']; ?>
            </td>

            <td class="text-center">
                <?php echo date('d-m-Y',strtotime($row['start_date'])); ?>
            </td>

            <td class="text-center">
                <?php echo date('d-m-Y',strtotime($row['end_date'])); ?>
            </td>

            <td class="text-center">
                ₹ <?php echo number_format($row['target_amount'],2); ?>
            </td>

            <td class="text-center">
                <?php
                if($row['status']=='1')
                {
                    echo '<span class="badge bg-success">Active</span>';
                }
                else
                {
                    echo '<span class="badge bg-danger">Inactive</span>';
                }
                ?>
            </td>

            <td class="text-center">
                <a href="<?= BASE_URL ?>financial/edit/<?= $row['fy_id']; ?>" class="btn btn-sm btn-primary">
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

    $(document).ready(function () {

    var table = $('#finanTable').DataTable({
        orderCellsTop: true,
        fixedHeader: true
    });

    // Textbox Search
    $('#finanTable thead tr:eq(1) th').each(function (i) {

        $('input', this).on('keyup change', function () {

            if (table.column(i).search() !== this.value) {
                table
                    .column(i)
                    .search(this.value)
                    .draw();
            }

        });

    });

    // Status Dropdown Search
    $('#finanTable thead select').on('change', function () {
        table
            .column(7)
            .search($(this).val())
            .draw();
    });


setTimeout(function(){
    let alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert){
        alert.style.display = 'none';
    });
}, 5000);

var state_id = $('#state_id').val();
var hq_id = "<?= isset($ROW['hq_id']) ? $ROW['hq_id'] : '' ?>";
   // 2. Function to Load Head Quarters
    function loadHQ(state_id, hq_id = '')
{
    console.log("State:", state_id);

    $.ajax({
        url: '<?= BASE_URL ?>customer/getHQs',
        type: 'POST',
        data: {
            state_id: state_id,
            selected_id: hq_id
        },
        success: function(response)
        {
            console.log(response);

            $('#hq_id').html(response);

            $('#hq_id').select2({
                theme: 'bootstrap-5'
            });
        }
    });
}

if (state_id) {
        loadHQ(state_id, hq_id);
    }
    // 3. Trigger both on State change
    $('#state_id').change(function(){
        var state_id = $(this).val();
       
        loadHQ(state_id);
    });
});
</script>
   
