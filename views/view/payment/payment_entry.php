<?php 
$pageTitle = "Make Payment";
include 'view/layout/header.php';

// Auto-select stockist if passed from the "Pay Now" button
$selected_stockist_id = isset($_GET['stockist_id']) ? (int)$_GET['stockist_id'] : '';
?>
<style>
    /* Styling for the form to match your theme */
    .form-container {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        max-width: 600px;
        margin: 0 auto;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 5px;
        color: #333;
    }
    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 14px;
    }
    .form-control:focus {
        border-color: #007bff;
        outline: none;
    }
    .btn-submit-form {
        background: #28a745;
        color: #fff;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        cursor: pointer;
        width: 100%;
        font-weight: 600;
    }
    .btn-submit-form:hover {
        background: #218838;
    }
</style>
<link rel="stylesheet" href="<?= BASE_URL ?>config/config/salesentry.css">

<div class="page-content">
<!-- Place this inside your <div id="container"> or main content area -->

<?php if (isset($_SESSION['success_msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        <i class="fa fa-check-circle"></i> <?= $_SESSION['success_msg']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success_msg']); // Clear the message after displaying ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_msg'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
        <i class="fa fa-exclamation-circle"></i> <?= $_SESSION['error_msg']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error_msg']); // Clear the message after displaying ?>
<?php endif; ?>
    <div class="page-header d-flex justify-content-between align-items-center mb-3">
        <h4>Payment Entry</h4>
        <!-- Back button to return to the list -->
        <a href="<?= BASE_URL ?>payment" style="padding: 6px 9px; background: #6c757d;" class="btn-submit">
            <i class="fa fa-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="form-container">
        <!-- The form submits via POST and requires enctype for file uploads -->
        <form action="<?= BASE_URL ?>payment/save" method="POST" enctype="multipart/form-data">
            
            <!-- Inside your form-container, update the stockist form-group -->
            <div class="form-group">
                <label for="stockist_id">Stockist *</label>
                <select name="stockist_id" id="stockist_id" class="form-control" required>
                    <option value="">-- Select Stockist --</option>
                    <?php if(!empty($stockists)): ?>
                        <?php foreach ($stockists as $s): ?>
                            <option value="<?= $s['stockist_id'] ?>" <?= ($s['stockist_id'] == $selected_stockist_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['stockist_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                
                <!-- NEW: Outstanding Balance Display Box -->
                <div id="outstanding_wrapper" style="display: none; margin-top: 8px; font-size: 14px; padding: 8px 12px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; color: #856404;">
                    <i class="fa fa-info-circle"></i> <strong>Total Outstanding: </strong> ₹<span id="outstanding_amount">0.00</span>
                </div>
            </div>
            <div class="form-group">
                <label for="amount_paid">Payment Amount (₹) *</label>
                <input type="number" name="amount_paid" id="amount_paid" class="form-control" step="0.01" min="1" placeholder="Enter amount..." required>
            </div>

            <div class="form-group">
                <label for="payment_method">Payment Method *</label>
                <select name="payment_method" id="payment_method" class="form-control" required>
                    <option value="">-- Select Method --</option>
                    <option value="UPI">UPI</option>
                    <option value="Bank Transfer (NEFT/RTGS)">Bank Transfer (NEFT/RTGS)</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Cash">Cash</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group" id="other_method_wrapper" style="display: none;">
                <label for="other_payment_method">Other Payment Method *</label>
                <input 
                    type="text" 
                    name="other_payment_method" 
                    id="other_payment_method" 
                    class="form-control" 
                    placeholder="Enter payment method..."
                >
            </div>

            <div class="form-group">
                <label for="bank_details">Reference No / Bank Details</label>
                <input type="text" name="bank_details" id="bank_details" class="form-control" placeholder="UTR, Cheque No, or Transaction ID">
            </div>

            <div class="form-group">
                <label for="screenshot">Payment Proof (Screenshot)</label>
                <input type="file" name="screenshot" id="screenshot" class="form-control" accept="image/png, image/jpeg, image/jpg, application/pdf">
                <small style="color: #666; margin-top: 4px; display: block;">Upload clear screenshot of the transaction (JPG, PNG, PDF max 2MB).</small>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <button type="submit" class="btn-submit-form">
                    <i class="fa fa-save"></i> Submit Payment
                </button>
            </div>

        </form>
    </div>

</div>

<?php include 'view/layout/footer.php'; ?>
<!-- Ensure jQuery is loaded before this script -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
<script>
$(document).ready(function() {
    
    // Function to fetch and display outstanding amount
    function fetchOutstanding(stockistId) {
        if (!stockistId) {
            $('#outstanding_wrapper').slideUp();
            return;
        }
        
        // Add a temporary loading state
        $('#outstanding_wrapper')
            .css({'background': '#e2e3e5', 'color': '#383d41', 'border-color': '#d6d8db'})
            .html('<i class="fa fa-spinner fa-spin"></i> <strong>Loading...</strong>')
            .slideDown();

        $.ajax({
            url: '<?= BASE_URL ?>payment/get_outstanding/' + stockistId,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let amount = parseFloat(response.outstanding);
                    
                    if (amount < 0) {
                        // Stockist has paid in advance (Credit Balance)
                        let advance = Math.abs(amount).toFixed(2);
                        $('#outstanding_wrapper')
                            .css({'background': '#d4edda', 'color': '#155724', 'border-color': '#c3e6cb'})
                            .html('<i class="fa fa-info-circle"></i> <strong>Advance Credit: </strong> ₹' + advance);
                    } else if (amount === 0) {
                        // Fully settled
                        $('#outstanding_wrapper')
                            .css({'background': '#d4edda', 'color': '#155724', 'border-color': '#c3e6cb'})
                            .html('<i class="fa fa-check-circle"></i> <strong>Fully Settled: </strong> ₹0.00');
                    } else {
                        // Stockist owes money
                        $('#outstanding_wrapper')
                            .css({'background': '#fff3cd', 'color': '#856404', 'border-color': '#ffeeba'})
                            .html('<i class="fa fa-exclamation-circle"></i> <strong>Total Outstanding: </strong> ₹' + amount.toFixed(2));
                    }
                } else {
                    $('#outstanding_wrapper')
                        .css({'background': '#f8d7da', 'color': '#721c24', 'border-color': '#f5c6cb'})
                        .html('<strong>Error:</strong> ' + (response.msg || 'Fetching data'));
                }
            },
            error: function(xhr) {
                console.error("AJAX Error: ", xhr.responseText);
                $('#outstanding_wrapper')
                    .css({'background': '#f8d7da', 'color': '#721c24', 'border-color': '#f5c6cb'})
                    .html('<strong>Error:</strong> Route not found');
            }
        });
    }

    // 1. Trigger when the user changes the dropdown
    $('#stockist_id').on('change', function() {
        fetchOutstanding($(this).val());
    });

    // 2. Trigger on page load if a stockist is already selected
    if ($('#stockist_id').val() !== '') {
        fetchOutstanding($('#stockist_id').val());
    }

    // Show/hide Other Payment Method field
$('#payment_method').on('change', function() {
    if ($(this).val() === 'Other') {
        $('#other_method_wrapper').slideDown();
        $('#other_payment_method').prop('required', true);
    } else {
        $('#other_method_wrapper').slideUp();
        $('#other_payment_method')
            .prop('required', false)
            .val('');
    }
});
});
</script>