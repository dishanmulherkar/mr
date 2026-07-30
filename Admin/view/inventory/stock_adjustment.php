 <?php 

$pageTitle = "Stock Adjustment";
// 3. Include the bottom layout and scripts
include 'view/layout/header.php'; 
?>
 
 <style>
        .detail {
            display: flex;
            justify-content: flex-end; 
            padding: 6px; 
        }
    </style> 
 
<div id="container">
                    <div class="detail">
                        <a href="<?= BASE_URL ?>login/logout"><button type="button" class="btn btn-secondary btn-sm">Logout</button></a>
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

                    <h3> Stock Adjustment </h3>
                    
                    <form method="post" action="<?= BASE_URL ?>stock_adjustment/store">
                        <div class="container border px-3 py-3 bg-white rounded">
                            <div class="row">
                                 <div class="col-lg-3">
                                    <div class="form-group">
                                        <label>Select State</label>
                                        <select name="state" id="state_id" class="form-control" required>
                                            <option value="">Select State</option>
                                            <?php if($states): while($srow = mysqli_fetch_assoc($states)): ?>
                                                <option value="<?php echo htmlspecialchars($srow['state_id']); ?>"
                                                    <?php if(isset($ROW['state']) && $ROW['state'] == $srow['state_id']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($srow['state_name']); ?>
                                                </option>
                                            <?php endwhile; endif; ?>
                                        </select>
                                    </div>
                                </div>
                               <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Head Quarter</label>
                                        <select name="hq_id" id="hq_id" class="form-control select2" required>
                                            <option value="">Select Head Quarter</option>
                                            </select>
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <label>Stockist</label>
                                        <select name="stockist_id" id="stockist_id" class="form-control" required>
                                            <option value="">Select Stockist</option>
                                        </select>
                                    </div>
                                </div>
                                
                                        <!-- ✅ Global Batch Selector -->
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <label>
                                                    Batch
                                                    <small class="text-muted">(applies to all rows)</small>
                                                </label>
                                                <select id="global_batch" class="form-control">
                                                    <option value="">Select Batch</option>
                                                </select>
                                            </div>
                                        </div>

                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label>Adj Date</label>
                                        <input type="date" name="inward_date" class="form-control" value="<?php echo isset($ROW['inward_date']) ? $ROW['inward_date'] : date('Y-m-d'); ?>" required>
                                    </div>
                                </div>

                                <div class="col-lg-8">
                                    <div class="form-group">
                                        <label>Remarks</label>
                                        <textarea name="remarks"
                                                class="form-control"
                                                rows="1"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table_container table-responsive pt-4">
                            <table class="table table-bordered bg-white">
                                <thead>
                                    <tr>
                                        <th width="10%">Sr No</th>
                                        <th width="30%">Product</th>
                                        <th width="20%">Batch</th>
                                        <th width="10%">Current</th>
                                        <th width="20%">Adj Qty ( - )</th>
                                    </tr>
                                </thead>
                               <tbody id="productTableBody">

</tbody>
                            </table>
                        </div>

                        <div class="row">
                            <div class="col-12 py-2">
                                <button type="submit" id="save" name="save" class="btn btn-success btn-sm" style="width:150px;">
                                    Save
                                </button>
                            </div>
                        </div>
                    </form> </div>
<?php 
include 'view/layout/footer.php'; 
?>

<script>

document.addEventListener('input', function(e){

    if(e.target.classList.contains('qty-input'))
    {
        let row = e.target.closest('tr');
        let qty = parseInt(e.target.value) || 0;

        let batch = row.querySelector('.batch-select');

        if(qty > 0)
        {
            batch.setAttribute('required', 'required');
        }
        else
        {
            batch.removeAttribute('required');
            batch.setCustomValidity('');
        }
    }
});

document.addEventListener('change', function(e){

    if(e.target.classList.contains('batch-select'))
    {
        e.target.setCustomValidity('');
    }
});


// Auto-hide messages 
setTimeout(function(){
    $('.alert').fadeOut('slow');
}, 5000);

$(document).ready(function(){

let isSubmitting = false;

$('form').on('submit', function(e) {

    let hasQty = false;

    $('.qty-input').each(function () {

        let qty = $(this).val().trim();

        // Remove leading zeros
        qty = qty.replace(/^0+/, '');

        if (qty !== '' && parseInt(qty) > 0) {
            hasQty = true;
            return false; // break loop
        }
    });

    if (!hasQty) {
        e.preventDefault();
        alert('Please enter quantity greater than 0.');
        return false;
    }

    if (isSubmitting) {
        e.preventDefault();
        return false;
    }

    isSubmitting = true;

    $('#save')
        .html('Saving... <i class="fa-solid fa-spinner fa-spin"></i>')
        .css({
            'pointer-events': 'none',
            'opacity': '0.7'
        });
});


function loadGlobalBatches(state_id)
{
    if(state_id == '')
    {
        $('#global_batch').html('<option value="">Select Batch</option>');
        return;
    }

    $.ajax({
        url:'<?= BASE_URL ?>stock_adjustment/getGlobalBatches',
        type:'POST',
        data:{state_id:state_id},
        success:function(res)
        {
            $('#global_batch').html(res);
        }
    });
}

function loadProducts(state_id)
{
    $.ajax({
        url:'<?= BASE_URL ?>stock_adjustment/getProductsByState',
        type:'POST',
        data:{state_id:state_id},
        success:function(res){
            $('#productTableBody').html(res);
        }
    });
}
    // Fetch current stock qty for a given row
function fetchCurrentQty(row)
{
    var stockist_id = $('#stockist_id').val();
    var p_id        = $(row).data('pid');
    var batch_id    = $(row).find('.batch-select').val();

    if(!stockist_id || !batch_id)
    {
        $(row).find('.current-qty').val(0);
        return;
    }
    console.log({
    stockist_id: stockist_id,
    p_id: p_id,
    batch_id: batch_id
});

    $.ajax({
        url: '<?= BASE_URL ?>stock_adjustment/getCurrentStock',
        type: 'POST',
        dataType: 'json',
        data:{
                p_id: p_id,
                stockist_id: stockist_id,
                batch_id: batch_id
            },
       success:function(res)
        {
            console.log(res);
            $(row).find('.current-qty').val(res.qty);
        },
        error:function(xhr)
        {
            console.log(xhr.responseText);
        }

    });
}

$(document).on('change', '.batch-select', function() {

    

    fetchCurrentQty($(this).closest('tr'));
});
// When stockist changes → refresh all rows that already have a batch selected
$('#stockist_id').on('change', function () {

    $('tr[data-pid]').each(function () {

        if($(this).find('.batch-select').val())
        {
            fetchCurrentQty(this);
        }
        else
        {
            $(this).find('.current-qty').val(0);
        }

    });

});

    // Dropdown structural listener
    $('#hq_id').change(function(){
        var hq_id = $(this).val();
        loadStockist(hq_id);
    });

    // Run on state restoration (Edit view)
    var current_hq = $('#hq_id').val();
    var current_stockist = "<?php echo isset($ROW['stockist_id']) ? $ROW['stockist_id'] : ''; ?>";

    if(current_hq != '') {
        loadStockist(current_hq, current_stockist);
    }


    // ✅ Global batch → auto-fill all product rows
$('#global_batch').change(function(){

    let batchNo = $(this).val();

    $('.batch-select').each(function(){

        let select = $(this);

        select.val('');

        select.find('option').each(function(){

            if($(this).data('batchno') == batchNo)
            {
                select.val($(this).val());
                select.trigger('change'); // fetch current qty
                return false;
            }

        });

    });

});

 function loadHQ(state_id, hq_id = '') {
        if (!state_id) {
            $('#hq_id').html('<option value="">Select Head Quarter</option>');
            if ($('#hq_id').hasClass('select2-hidden-accessible')) { $('#hq_id').select2('destroy'); }
            $('#hq_id').select2({ theme: 'bootstrap-5' });
            return; 
        }

        $.ajax({
            url: '<?= BASE_URL ?>customer/getHQs', 
            type: 'POST',
            data: { state_id: state_id, selected_id: hq_id },
            success: function(response) {
                if ($('#hq_id').hasClass('select2-hidden-accessible')) {
                    $('#hq_id').select2('destroy');
                }
                $('#hq_id').html(response);
                $('#hq_id').select2({ theme: 'bootstrap-5' });
            }
        });
    }
        console.log('<?= BASE_URL ?>stock_inward/getHqByState');
    // 2. Load Stockists (HQ -> Stockist)
    function loadStockist(hq_id, stockist_id = '') {
        if(!hq_id) {
            $('#stockist_id').html('<option value="">Select Stockist</option>');
            if ($('#stockist_id').hasClass('select2-hidden-accessible')) { $('#stockist_id').select2('destroy'); }
            $('#stockist_id').select2({ theme: 'bootstrap-5' });
            return;
        }

        $.ajax({
            url: '<?= BASE_URL ?>stock_inward/getStockists', 
            type: 'POST',
            data: { hq_id: hq_id, selected_id: stockist_id },
            success: function(response) {
                if ($('#stockist_id').hasClass('select2-hidden-accessible')) {
                    $('#stockist_id').select2('destroy');
                }
                $('#stockist_id').html(response);
                $('#stockist_id').select2({ theme: 'bootstrap-5' });
            }
        });
    }

    // 3. Event Listeners
    $('#state_id').change(function(){
        var state_id = $(this).val();
        loadHQ(state_id);
        loadStockist(''); // Wipe the stockist dropdown when State changes
        loadProducts(state_id); 
        loadGlobalBatches(state_id);
    });

    $('#hq_id').change(function(){
        var hq_id = $(this).val();
        loadStockist(hq_id);
    });

    // 4. Edit Mode (Page Load Restoration)
    var state_id    = $('#state_id').val();
    var hq_id       = "<?= isset($ROW['hq_id']) ? $ROW['hq_id'] : '' ?>";
    var stockist_id = "<?= isset($ROW['stockist_id']) ? $ROW['stockist_id'] : '' ?>";
    
    if(state_id != '') {
        loadHQ(state_id, hq_id);
    }
    
    if(hq_id != '') {
        loadStockist(hq_id, stockist_id);
    }
    
    // Initialize standard Select2
    $('.select2').select2({ theme: 'bootstrap-5' });


// Reset global batch + all rows when HQ changes
$('#hq_id').on('change', function () {
    $('#global_batch').val('');
    $('tr[data-pid]').each(function () {
        $(this).find('.batch-select').val('');
        $(this).find('.current-qty').val(0);
    });
});
});
</script>