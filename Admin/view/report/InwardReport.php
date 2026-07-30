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
                         <h3> Inward Stock Report </h3>
                       

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
                        <table class="table">
                            <thead class="table-secondary ">
                                <tr>
                                    <th class="text-center">Sr. No</th>
                                    <th class="text-center">Date</th>
                                    <th class="text-center">Stockist Name</th>
                                    <th class="text-center">Remark</th>
                                    
                                    
                                    <th class="text-center">Action</th>
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
                                        <td class="text-center"><?= date('d-m-Y',strtotime($row['inward_date'])) ?></td>
                                        <td class="text-center"><?= $row['stockist_name'] ?></td>
                                        <td class="text-center"><?= $row['remarks'] ?></td>
                                        <td class="text-center">
                                            <a href="inward_report/details/<?= $row['inward_id'] ?>"
                                            class="btn btn-info btn-sm">
                                                View
                                            </a>
                                        </td>
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

    // State -> HQ

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
    

