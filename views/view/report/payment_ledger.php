<?php 
$pageTitle = "Payment Ledgers";
include 'view/layout/header.php'; 
?>
<link rel="stylesheet" href="<?= BASE_URL ?>config/config/sales-report.css">
<style>
/* ─── Responsive ─────────────────────────────────── */

/* 1. Base / Desktop Layout (Everything in one row, flexible widths) */
.rpt-filter-bar { 
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px; 
}

.rpt-filter-bar label {
    font-size: 13px;
    white-space: nowrap;
}

.rpt-filter-bar select,
.rpt-filter-bar input[type="date"] {  
    font-size: 13px;
    flex: 1 1 auto; /* Allows inputs to grow and shrink smoothly */
    min-width: 130px; 
}

.btn-search { 
    width: auto;
    min-width: 120px;
    text-align: center; 
}

/* 2. Table Responsiveness */
.rpt-table-wrap { overflow-x: auto; }
.rpt-table-wrap table { min-width: 250px; }

/* 3. Mobile Layout (Stacks everything vertically) */
@media (max-width: 768px) {
    .rpt-filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .rpt-filter-bar label {
        margin-bottom: -5px; /* Pulls label closer to the input */
    }

    .rpt-filter-bar select,
    .rpt-filter-bar input[type="date"],
    .btn-search {
        width: 100% !important; /* Full width for easy thumb tapping */
        min-width: 100% !important;
    }
}

/* ─── Utilities (Kept from your original code) ─── */
@media (max-width: 480px) {
  .avatar_name {
    cursor: pointer;
    border: 1px solid #b1b5ca;
    border-radius: 9px;
    padding-right: 51px;
    padding-left: 44px;
    margin-left: 60px;
    font-size: small;
    color: #767b94;
  }

  .rpt-table-wrap tbody td {
    padding: 1px 9px !important;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
    color: var(--txt);
}

.rpt-table-wrap table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px !important;
}

.rpt-table-wrap thead th {
    padding: 5px 7px !important;
    background: var(--surface2);
    font-size: 10px !important;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--txt-muted);
    border-bottom: 1px solid var(--border)
    white-space: normal;
}
}

.text-right { text-align: right !important; }
.text-center { text-align: center !important; }
</style>

<div class="page-content">

    <!-- ── Filters ────────────────────────────────────── -->
    <div class="rpt-filter-bar">
        <label>Start Date</label>
        <input type="date" id="start_date" value="<?= htmlspecialchars($from_date) ?>">

        <label>End Date</label>
        <input type="date" id="end_date" value="<?= htmlspecialchars($to_date) ?>">

         <select id="stockist-select" class="filter-pill" >
            <option value="">— Select Stockist —</option>
            <?php 
            $selected_stockist = isset($_GET['stockist_id']) ? $_GET['stockist_id'] : (isset($order_data['stockist_id']) ? $order_data['stockist_id'] : '');
            if(!empty($stockists)):
                foreach ($stockists as $s): 
                    $selected = ($s['stockist_id'] == $selected_stockist) ? 'selected' : '';
            ?>
                <option value="<?= $s['stockist_id'] ?>" <?= $selected ?>><?= htmlspecialchars($s['stockist_name']) ?></option>
            <?php 
                endforeach; 
            endif;
            ?>
        </select>

        <button class="btn-search" onclick="loadReport()">Search</button>
    </div>

    <!-- ── Customer / Stockist row ──────────────────── -->
    <!-- <div class="filter-bar entry-row">
        <label for="stockist-select">Stockist</label>
       
       
    </div> -->

    <!-- <div class="rpt-filter-bar search" style="align-items: baseline;">
        <button class="btn-search" onclick="downloadPDFs()" style="width: 30%;">Download PDF</button>
    </div> -->

    <!-- ── Report Table ───────────────────────────────── -->
    <div class="rpt-table-wrap">
        <table>
           <thead>
                <tr>
                    <th>Date</th>
                    <th>Particulars</th>
                    <th>Vch Type</th>
                    <th>Ref / Bill No</th>
                    <th class="text-right">Debit (+) (₹)</th>
                    <th class="text-right">Credit (-) (₹)</th>
                </tr>
            </thead>
            
            <tbody id="rpt-tbody">
                <?php if (isset($query) && $query && mysqli_num_rows($query) > 0): ?>
                    <?php
                    $total_debit = 0;
                    $total_credit = 0;
                    
                    // Handle Opening Balance
                    if (isset($opening_balance) && $opening_balance > 0) {
                        $total_debit += $opening_balance;
                        echo "<tr>
                                <td>".date('d-M', strtotime($from_date))."</td>
                                <td><strong>To Opening Balance</strong></td>
                                <td></td>
                                <td></td>
                                <td class='text-right'><strong>".number_format($opening_balance, 2)."</strong></td>
                                <td class='text-right'></td>
                            </tr>";
                    } elseif (isset($opening_balance) && $opening_balance < 0) {
                        $total_credit += abs($opening_balance);
                        echo "<tr>
                                <td>".date('d-M', strtotime($from_date))."</td>
                                <td><strong>By Opening Balance</strong></td>
                                <td></td>
                                <td></td>
                                <td class='text-right'></td>
                                <td class='text-right'><strong>".number_format(abs($opening_balance), 2)."</strong></td>
                            </tr>";
                    }

                    while($row = mysqli_fetch_assoc($query)) {
                        $date = date('d-M', strtotime($row['created_at']));
                        
                        // Fetch basic variables used by both blocks
                        $raw_notes = !empty($row['notes']) ? htmlspecialchars($row['notes']) : "";
                        $vch_no = htmlspecialchars($row['inward_no'] ?? '');
                        
                        if ($row['transaction_type'] == 'bill_added') {
                            // 1. Check if this is a CD Reversal saved as a bill
                            if (stripos($raw_notes, 'CD Reversed') !== false) {
                                $particulars = $raw_notes; // Shows "CD Reversed for T-127"
                                $vch_type = "Adjustment";
                            } else {
                                // Normal Sales Bill
                                $particulars = "Sales Bill";
                                $vch_type = "Tax Invoice";
                            }
                            
                            $debit = round((float)$row['amount'], 2);
                            $credit = 0;
                            $total_debit += $debit;
                            
                        } else {
                            // 2. Credits (Payments & Commission Settlements)
                            $vch_no = htmlspecialchars($row['pay_id'] ?? '');
                            if (empty($raw_notes)) $raw_notes = "By Receipt";
                            
                            $bank_name = !empty($row['bank_name']) ? htmlspecialchars($row['bank_name']) : "";
                            $payment_method = !empty($row['payment_method']) ? htmlspecialchars($row['payment_method']) : "";
                            
                            $stockist_name = htmlspecialchars($row['stockist_name'] ?? 'Unknown');
                            $stockist_badge = "<br><button type='button' class='btn btn-sm btn-light' style='font-size: 10px; padding: 2px 6px; margin-top: 3px; border: 1px solid #dee2e6;'><i class='fa fa-user text-primary'></i> {$stockist_name}</button>";
                            
                            // Particulars Logic
                            if (stripos($raw_notes, '4% CD') !== false || stripos($raw_notes, '2% CD') !== false) {
                                if (preg_match('/((?:4%|2%) CD) on Invoice ([a-zA-Z]+-\d+)/i', $raw_notes, $matches)) {
                                    $particulars = $matches[1] . " on " . $matches[2];
                                } else {
                                    $particulars = explode(':', $raw_notes)[0]; 
                                }
                            } elseif (stripos($raw_notes, 'CD Reversed') !== false) {
                                // Failsafe in case transaction_type was somehow set to something else
                                $particulars = $raw_notes;
                            } elseif (stripos($raw_notes, 'CD on Invoice') !== false || stripos($raw_notes, 'CD Applied') !== false) {
                                $particulars = "CD Applied";
                            } elseif ($row['transaction_type'] === 'mrc_settlement') {
                                $particulars = "<span style='font-weight: 600;'>MRC Settlement</span>";
                            } elseif ($row['transaction_type'] === 'drc_settlement') {
                                $particulars = "<span style='font-weight: 600;'>DRC Settlement</span>";
                            } elseif ($row['transaction_type'] === 'settled_to_bill') {
                                $particulars = "<span style='font-weight: 600;'>Bill Adjusted</span>";
                            } else {
                                // Proper fallback for payments
                                if (!empty($payment_method) && !empty($bank_name)) {
                                    $particulars = "{$payment_method} - {$bank_name}";
                                } elseif (!empty($payment_method)) {
                                    $particulars = $payment_method;
                                } else {
                                    $particulars = $raw_notes;
                                }
                            }

                            // Differentiate Voucher Type
                            if (in_array($row['transaction_type'], ['mrc_settlement', 'drc_settlement', 'settled_to_bill'])) {
                                $vch_type = "Adjustment"; 
                            } else {
                                $vch_type = "Receipt";    
                            }

                            $raw_amount = (float)$row['amount'];
                            $debit = 0;
                            
                            // ROUND OFF CD AMOUNTS
                            if (stripos($raw_notes, 'CD') !== false) {
                                $credit = round($raw_amount); 
                            } else {
                                $credit = round($raw_amount, 2);
                            }
                            
                            $total_credit += $credit;
                        }
                    ?>
                        <tr>
                            <td><?= $date ?></td>
                            <td><?= $particulars ?></td>
                            <td><?= $vch_type ?></td>
                            <td><?= $vch_no ?></td>
                            <td class="text-right"><?= $debit > 0 ? number_format($debit, 2) : '' ?></td>
                            <td class="text-right" style="color: #5cb85c;"><?= $credit > 0 ? number_format($credit, 2) : '' ?></td>
                        </tr>
                    <?php
                    }
                    ?>
                    
                <?php elseif (isset($_GET['stockist_id']) && !empty($_GET['stockist_id'])): ?>
                    <tr>
                        <td colspan="6">
                            <div class="state-screen" style="padding: 30px; text-align: center;">
                                <div class="state-msg">No transactions found</div>
                                No data available for the selected dates.
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="state-screen" style="padding: 30px; text-align: center;">
                                <div class="state-icon" style="font-size: 30px;">📋</div>
                                <div class="state-msg">Select a stockist and date</div>
                                Choose filters above and hit Search to load the ledger report.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <!-- Balancing Footer -->
            <?php if (isset($query) && $query && mysqli_num_rows($query) > 0): ?>
            <?php
                // Explicitly Round to avoid floating point math errors
                $total_debit = round($total_debit, 2);
                $total_credit = round($total_credit, 2);
                
                $closing_balance = round($total_debit - $total_credit, 2);
                $grand_total = max($total_debit, $total_credit);
            ?>
            <tfoot id="rpt-tfoot">
                <tr class="tally-footer" style="background: #f8f9fa;">
                    <td colspan="4"></td>
                    <td class="text-right" style="border-top: 1px solid #ccc;"><strong><?= number_format($total_debit, 2) ?></strong></td>
                    <td class="text-right" style="border-top: 1px solid #ccc;"><strong><?= number_format($total_credit, 2) ?></strong></td>
                </tr>
                <tr style="background: #f8f9fa; border-top:none !important; border-bottom:none !important;">
                    <td colspan="4" class="text-right" style="border:none !important; padding-right: 15px;"><strong><?= $closing_balance > 0 ? 'By' : 'To' ?> Closing Balance</strong></td>
                    <td class="text-right text-danger" style="border:none !important;"><strong><?= $closing_balance < 0 ? number_format(abs($closing_balance), 2) : '' ?></strong></td>
                    <td class="text-right text-danger" style="border:none !important;"><strong><?= $closing_balance > 0 ? number_format($closing_balance, 2) : '' ?></strong></td>
                </tr>
                <tr class="tally-grand-total" style="background: #f8f9fa;">
                    <td colspan="4" class="text-right" style="padding-right: 15px;"><strong>Grand Total</strong></td>
                    <td class="text-right" style="border-top: 2px solid #333; border-bottom: 2px double #333;"><strong><?= number_format($grand_total, 2) ?></strong></td>
                    <td class="text-right" style="border-top: 2px solid #333; border-bottom: 2px double #333;"><strong><?= number_format($grand_total, 2) ?></strong></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>

</div><!-- /.page-content -->

<?php include 'view/layout/footer.php'; ?>

<script>
    const mr_id = <?= isset($mr_id) ? $mr_id : 0 ?>; 

    // Function to reload the page with URL parameters for the controller
    function loadReport() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        let stockistId = document.getElementById('stockist-select').value;
        
        // Fallback for hidden input if dropdown is disabled
        if (!stockistId) {
            const hiddenInput = document.getElementById('hidden-stockist');
            if(hiddenInput) stockistId = hiddenInput.value;
        }

        if (!stockistId) {
            alert("Please select a stockist first.");
            return;
        }

        // Build the URL using GET parameters to hit your controller logic
        const url = new URL(window.location.href);
        url.searchParams.set('stockist_id', stockistId);
        url.searchParams.set('start_date', startDate);
        url.searchParams.set('end_date', endDate);

        window.location.href = url.toString();
    }

    function downloadPDFs() {
        // Implement your PDF download logic here
        alert("PDF download triggered.");
    }
</script>