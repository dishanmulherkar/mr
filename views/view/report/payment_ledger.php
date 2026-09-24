<?php 
$pageTitle = "Payment Ledgers";
include 'view/layout/header.php'; 
?>
<link rel="stylesheet" href="<?= BASE_URL ?>config/config/sales-report.css">
<style>
/* ─── Responsive ─────────────────────────────────── */

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
    flex: 1 1 auto;
    min-width: 130px; 
}

.btn-search { 
    width: auto;
    min-width: 120px;
    text-align: center; 
}

.rpt-table-wrap { overflow-x: auto; }
.rpt-table-wrap table { min-width: 250px; }

/* ─── Paid Bill Color Highlights ─────────────────── */
.bill-paid-row {
    background-color: #f0fdf4 !important; /* Soft green tint */
}
.badge-bill-paid {
    display: inline-block;
    padding: 2px 6px;
    font-size: 10px;
    font-weight: 700;
    color: #166534;
    background-color: #bbf7d0;
    border-radius: 4px;
    margin-left: 5px;
}
.badge-bill-partial {
    display: inline-block;
    padding: 2px 6px;
    font-size: 10px;
    font-weight: 700;
    color: #854d0e;
    background-color: #fef08a;
    border-radius: 4px;
    margin-left: 5px;
}

@media (max-width: 768px) {
    .rpt-filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .rpt-filter-bar label {
        margin-bottom: -5px;
    }

    .rpt-filter-bar select,
    .rpt-filter-bar input[type="date"],
    .btn-search {
        width: 100% !important;
        min-width: 100% !important;
    }
}

@media (max-width: 480px) {
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
    border-bottom: 1px solid var(--border);
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

        <select id="stockist-select" class="filter-pill">
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
                        $raw_notes = !empty($row['notes']) ? htmlspecialchars($row['notes']) : "";
                        $vch_no = htmlspecialchars($row['inward_no'] ?? '');
                        
                        $row_class = "";
                        $status_badge = "";

                        if ($row['transaction_type'] == 'bill_added') {
                            if (stripos($raw_notes, 'CD Reversed') !== false) {
                                $particulars = $raw_notes;
                                $vch_type = "Adjustment";
                            } else {
                                $particulars = "Sales Bill";
                                $vch_type = "Tax Invoice";

                                // Check payment status from stock_inward
                                $pay_status = strtolower($row['pay_status'] ?? '');
                                if ($pay_status === 'paid') {
                                    $row_class = "bill-paid-row";
                                    $status_badge = "<span class='badge-bill-paid'>✓ PAID</span>";
                                } elseif ($pay_status === 'partial') {
                                    $status_badge = "<span class='badge-bill-partial'>PARTIAL</span>";
                                }
                            }
                            
                            $debit = round((float)$row['amount'], 2);
                            $credit = 0;
                            $total_debit += $debit;
                            
                        } else {
                            // Credits (Payments & Settlements)
                            $vch_no = htmlspecialchars($row['pay_id'] ?? '');
                            if (empty($raw_notes)) $raw_notes = "By Receipt";
                            
                            $bank_name = !empty($row['bank_name']) ? htmlspecialchars($row['bank_name']) : "";
                            $payment_method = !empty($row['payment_method']) ? htmlspecialchars($row['payment_method']) : "";
                            
                            if (stripos($raw_notes, '4% CD') !== false || stripos($raw_notes, '2% CD') !== false) {
                                if (preg_match('/((?:4%|2%) CD) on Invoice ([a-zA-Z]+-\d+)/i', $raw_notes, $matches)) {
                                    $particulars = $matches[1] . " on " . $matches[2];
                                } else {
                                    $particulars = explode(':', $raw_notes)[0]; 
                                }
                            } elseif (stripos($raw_notes, 'CD Reversed') !== false) {
                                $particulars = $raw_notes;
                            } elseif (stripos($raw_notes, 'CD on Invoice') !== false || stripos($raw_notes, 'CD Applied') !== false) {
                                $particulars = "CD Applied";
                            } elseif ($row['transaction_type'] === 'mrc_settlement') {
                                $particulars = "<span style='font-weight: 600;'>MRC Settlement</span>";
                            } elseif ($row['transaction_type'] === 'drc_settlement') {
                                $particulars = "<span style='font-weight: 600;'>DRC Settlement</span>";
                            } elseif ($row['transaction_type'] === 'settled_to_bill') {
                                $particulars = "<span style='font-weight: 600;'>Bill Adjusted</span>";
                            } elseif ($row['transaction_type'] === 'asm_settlement') {
                                $particulars = "<span style='font-weight: 600;'>ASM Settlement</span>";
                            } else {
                                if (!empty($payment_method) && !empty($bank_name)) {
                                    $particulars = "{$payment_method} - {$bank_name}";
                                } elseif (!empty($payment_method)) {
                                    $particulars = $payment_method;
                                } else {
                                    $particulars = $raw_notes;
                                }
                            }

                            if (in_array($row['transaction_type'], ['mrc_settlement', 'drc_settlement', 'settled_to_bill', 'asm_settlement'])) {
                                $vch_type = "Adjustment"; 
                            } else {
                                $vch_type = "Receipt";    
                            }

                            $raw_amount = (float)$row['amount'];
                            $debit = 0;
                            
                            if (stripos($raw_notes, 'CD') !== false) {
                                $credit = round($raw_amount); 
                            } else {
                                $credit = round($raw_amount, 2);
                            }
                            
                            $total_credit += $credit;
                        }
                    ?>
                        <tr class="<?= $row_class ?>">
                            <td><?= $date ?></td>
                            <td><?= $particulars ?></td>
                            <td><?= $vch_type ?></td>
                            <td>
                                <?= $vch_no ?>
                                <?= $status_badge ?>
                            </td>
                            <td class="text-right" <?= ($row_class ? 'style="color: #166534; font-weight: 600;"' : '') ?>>
                                <?= $debit > 0 ? number_format($debit, 2) : '' ?>
                            </td>
                            <td class="text-right" style="color: #5cb85c;">
                                <?= $credit > 0 ? number_format($credit, 2) : '' ?>
                            </td>
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

    function loadReport() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        let stockistId = document.getElementById('stockist-select').value;
        
        if (!stockistId) {
            const hiddenInput = document.getElementById('hidden-stockist');
            if(hiddenInput) stockistId = hiddenInput.value;
        }

        if (!stockistId) {
            alert("Please select a stockist first.");
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('stockist_id', stockistId);
        url.searchParams.set('start_date', startDate);
        url.searchParams.set('end_date', endDate);

        window.location.href = url.toString();
    }
</script>