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

<?php if (isset($_SESSION['success_msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        <i class="fa fa-check-circle"></i> <?= $_SESSION['success_msg']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success_msg']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_msg'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
        <i class="fa fa-exclamation-circle"></i> <?= $_SESSION['error_msg']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error_msg']); ?>
<?php endif; ?>

    <div class="page-header d-flex justify-content-between align-items-center mb-3">
        <h4>Payment Entry</h4>
        <a href="<?= BASE_URL ?>payment" style="padding: 6px 9px; background: #6c757d;" class="btn-submit">
            <i class="fa fa-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="form-container">
        <form action="<?= BASE_URL ?>payment/save" method="POST" enctype="multipart/form-data">
            
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
                
                <!-- Display Area for Detailed Bills -->
                <div class="row g-3 mb-2 mt-1">
                    <div class="col-lg-12">
                        <div id="outstandingDisplay"></div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="amount_paid">Payment Amount (₹) *</label>
                <!-- NOTE: ID is amount_paid -->
                <input type="number" name="amount_paid" id="amount_paid" class="form-control"  min="1" placeholder="Enter amount..." required>
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

            <div class="form-group">
                <label for="payment_to">Payment To *</label>
                <select name="bank_id" id="bank_id" class="form-control" required>
                    <option value="">Select Bank</option>
                    <?php 
                    if (!empty($data['LinkedBanks'])): 
                        foreach($data['LinkedBanks'] as $bank): 
                            // Optional: Retain selected bank if editing
                            $isSelected = (isset($ROW['bank_id']) && $ROW['bank_id'] == $bank['bank_id']) ? 'selected' : '';
                    ?>
                        <option value="<?= $bank['bank_id']; ?>" <?= $isSelected; ?>>
                            <?= htmlspecialchars($bank['bank_name']); ?>
                        </option>
                    <?php 
                        endforeach; 
                    else: 
                    ?>
                        <option value="" disabled>No banks assigned to this Super Stockist</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group" id="other_method_wrapper" style="display: none;">
                <label for="other_payment_method">Other Payment Method *</label>
                <input type="text" name="other_payment_method" id="other_payment_method" class="form-control" placeholder="Enter payment method...">
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


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
<?php include 'view/layout/footer.php'; ?>
<script>
$(document).ready(function() {
    
 $('#payment_method').on('change', function() {
    let method = $(this).val();

    // 1. Handle "Other" payment method
    if (method === 'Other') {
        $('#other_method_wrapper').slideDown();
        $('#other_payment_method').prop('required', true);
    } else {
        $('#other_method_wrapper').slideUp();
        $('#other_payment_method').prop('required', false).val('');
    }

    // 2. Handle "Cash" payment method (Hide Bank Dropdown & Label)
    if (method === 'Cash') {
        $('#bank_id').closest('.form-group').slideUp(); // Hides the whole wrapper including label
        $('#bank_id').prop('required', false).val('');  // Removes required attribute and clears selection
    } else {
        $('#bank_id').closest('.form-group').slideDown();
        $('#bank_id').prop('required', true);
    }
});

// Optional: Trigger the change event on page load to handle edit pages correctly
$('#payment_method').trigger('change');

    // 2. Fetch Outstanding Bills cleanly
    $('#stockist_id').change(function() {
        let stockistId = $(this).val();
        
        if (stockistId) {
            $('#outstandingDisplay').html('<div class="alert alert-light border py-2"><i class="fa fa-spinner fa-spin"></i> Fetching bills...</div>');
            
            $.get('<?= BASE_URL ?>payment/get_outstanding', { stockist_id: stockistId }, function(res) {
                if (res.success) {
                    let amount = parseFloat(res.outstanding) || 0;
                    
                    if (amount < 0) {
                        $('#outstandingDisplay').html(`<div class="alert alert-success py-2 mb-0"><i class="fa fa-info-circle"></i> <strong>Advance Credit: </strong> ₹${Math.abs(amount).toFixed(2)}</div>`);
                        $('#amount_paid').removeAttr('max').removeAttr('title');
                        return;
                    } else if (amount === 0) {
                        $('#outstandingDisplay').html(`<div class="alert alert-success py-2 mb-0"><i class="fa fa-check-circle"></i> <strong>Fully Settled: </strong> ₹0.00</div>`);
                        $('#amount_paid').removeAttr('max').removeAttr('title');
                        return;
                    }

                    let tableHtml = `
                        <div class="mt-2 table-responsive shadow-sm border rounded">
                            <table class="table table-sm table-hover table-striped mb-0" style="font-size: 0.8rem;">
                                <thead class="table-dark text-center">
                                    <tr>
                                        <th>Inv No</th>
                                        <th>Date</th>
                                        <th>CD Status</th>
                                        <th>Net Payable</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    if (res.bills && res.bills.length > 0) {
                        res.bills.forEach(b => {
                            let pending = parseFloat(b.pending_amount) || 0;
                            let sub_total = parseFloat(b.sub_total) || 0;
                            
                            let existingCdPercent = parseFloat(b.cd_percent) || 0;
                            
                            let eligible4 = parseFloat(b.eligible_4_cd) || 0;
                            let eligible2 = parseFloat(b.eligible_2_cd) || 0;
                            let newCdAmount = eligible4 + eligible2; 
                            
                            let penaltyAmount = parseFloat(b.penalty_amount) || 0;
                            
                            let already4 = parseFloat(b.already_4_cd) || 0;
                            let already2 = parseFloat(b.already_2_cd) || 0;
                            let alreadyCdAmount = already4 + already2; 
                            
                            // Net Payable includes subtracting NEW CDs, and ADDING BACK penalties
                            let net = pending - newCdAmount + penaltyAmount;
                            
                            let cdBadge = '';
                            let cdAmountDisplay = '';

                            if (penaltyAmount > 0) {
                                // 1. CD REVOKED (Late Payment Penalty)
                                cdAmountDisplay = `<span class="text-danger fw-bold">+₹${penaltyAmount.toFixed(2)}</span>`;
                                
                                // Determine if partial downgrade (4% to 2%) or total revocation
                                if (existingCdPercent == 4 && penaltyAmount < alreadyCdAmount) {
                                    cdBadge = `<br><small class="badge bg-warning text-dark mt-1" style="font-size: 0.65em;">Downgraded to 2% CD (Late)</small>`;
                                } else {
                                    cdBadge = `<br><small class="badge bg-danger text-white mt-1" style="font-size: 0.65em;">${existingCdPercent}% CD Revoked (Late)</small>`;
                                }

                            } else if (newCdAmount > 0) {
                                // 2. NEW CD APPLIED
                                let cdAppliedPercent = (eligible4 > 0) ? 4 : 2;
                                cdAmountDisplay = `<span class="text-success fw-bold">-₹${newCdAmount.toFixed(2)}</span>`;
                                cdBadge = `<br><small class="badge bg-success text-white mt-1" style="font-size: 0.65em;">${cdAppliedPercent}% CD Applied</small>`;
                            
                            } else if (existingCdPercent > 0) {
                                // 3. CD ALREADY GIVEN (Still valid)
                                cdAmountDisplay = `<span class="text-muted" style="font-style: italic;">Included (-₹${alreadyCdAmount.toFixed(2)})</span>`;
                                cdBadge = `<br><small class="badge bg-secondary text-white mt-1" style="font-size: 0.65em;">${existingCdPercent}% CD Already Given</small>`;
                            
                            } else {
                                // 4. NO CD 
                                cdAmountDisplay = `<span class="text-muted">-₹0.00</span>`;
                            }
                            
                            let dateParts = b.inward_date.split('-');
                            let shortDate = dateParts.length === 3 ? `${dateParts[2]}/${dateParts[1]}/${dateParts[0].substring(2)}` : b.inward_date;

                            tableHtml += `
                                <tr>
                                    <td class="fw-bold">${b.inward_no}</td>
                                    <td class="text-center">${shortDate}</td>
                                    <td class="text-end">
                                        ${cdAmountDisplay}
                                        ${cdBadge}
                                    </td>
                                   <td class="text-end fw-bold">₹${Math.round(net).toFixed(2)}</td>
                                </tr>
                            `;
                        });
                        
                        // Parse safely to prevent NaN errors in the footer
                        let finalTotalCd = parseFloat(res.eligible_cd) || 0;
                        let finalTotalPenalty = parseFloat(res.total_penalty) || 0;
                        
                        // Display penalty if it exists in the footer
                        let footerCdDisplay = `<span class="text-success">-₹${finalTotalCd.toFixed(2)}</span>`;
                        if (finalTotalPenalty > 0) {
                            footerCdDisplay += `<br><span class="text-danger">+₹${finalTotalPenalty.toFixed(2)}</span>`;
                        }

                        tableHtml += `
                                <tr class="table-secondary fw-bold">
                                    <td colspan="2" class="text-end">TOTAL:</td>
                                    <td class="text-end">${footerCdDisplay}</td>
                                    <td class="text-end text-primary">₹${Math.round(res.net_payable).toFixed(2)}</td>
                                </tr>
                        `;
                    } else {
                        tableHtml += `<tr><td colspan="5" class="text-center text-muted py-3">No pending bills found.</td></tr>`;
                    }
                    
                    tableHtml += `</tbody></table></div>`;
                    
                    $('#outstandingDisplay').html(tableHtml);
                    
                  let finalMax = parseFloat(res.net_payable) || 0;
                    // Round up to the nearest whole Rupee to allow rounded payments
                    let roundedMax = Math.ceil(finalMax); 

                    $('#amount_paid').attr('max', roundedMax);
                    $('#amount_paid').attr('title', `Maximum allowed is ₹${roundedMax}`);
                    
                } else {
                    $('#outstandingDisplay').html('<div class="alert alert-danger py-2 mb-0">Error fetching data: ' + (res.msg || '') + '</div>');
                }
            }, 'json').fail(function() {
                $('#outstandingDisplay').html('<div class="alert alert-danger py-2 mb-0">Server error fetching outstanding.</div>');
            });
        } else {
            $('#outstandingDisplay').html('');
            $('#amount_paid').removeAttr('max').removeAttr('title');
        }
    });

    if ($('#stockist_id').val() !== '') {
        $('#stockist_id').trigger('change');
    }
});
</script>