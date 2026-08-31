<?php 
$pageTitle = "MR Commission Ledger";
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
    padding: 15px 0;
}

.rpt-filter-bar label {
    font-size: 13px;
    white-space: nowrap;
    font-weight: 600;
}

.rpt-filter-bar input[type="date"] {  
    font-size: 13px;
    flex: 1 1 auto; 
    min-width: 130px; 
    padding: 9px 12px;
    border: 1px solid #ced4da;
    border-radius: 6px;
}

.btn-search { 
    width: auto;
    min-width: 120px;
    text-align: center; 
    padding: 9px 20px;
    background-color: #0d6efd;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
}

.btn-search:hover { background-color: #0b5ed7; }

/* 2. Table Responsiveness */
.rpt-table-wrap { overflow-x: auto; }
.rpt-table-wrap table { min-width: 250px; width: 100%; border-collapse: collapse; }

/* 3. Mobile Layout (Stacks everything vertically) */
@media (max-width: 768px) {
    .rpt-filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .rpt-filter-bar label {
        margin-bottom: -5px; 
    }

    .rpt-filter-bar input[type="date"],
    .btn-search {
        width: 100% !important; 
        min-width: 100% !important;
    }
}

/* 4. Utilities for Small Screens (480px) */
@media (max-width: 480px) {
    .rpt-table-wrap tbody td {
        padding: 6px 9px !important;
        border-bottom: 1px solid #dee2e6;
        vertical-align: middle;
    }

    .rpt-table-wrap table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11.5px !important;
    }

    .rpt-table-wrap thead th {
        padding: 7px 7px !important;
        background: #f1f5f9;
        font-size: 10px !important;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #6c757d;
        border-bottom: 1px solid #dee2e6;
        white-space: nowrap;
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

        <!-- Note: No Stockist Dropdown needed for MRC Ledger -->

        <button class="btn-search" onclick="loadReport()">Search</button>
    </div>

    <!-- ── Report Table ───────────────────────────────── -->
    <div class="rpt-table-wrap">
        <table class="table table-bordered table-striped">
            <thead>
                <tr style="background: #f1f5f9;">
                    <th style="width: 15%; white-space: nowrap;">Date</th>
                    <th style="width: 45%;">Type / Particulars</th>
                    <th class="text-right" style="width: 20%; white-space: nowrap;">Earned (+)</th>
                    <th class="text-right" style="width: 20%; white-space: nowrap;">Settled (-)</th>
                </tr>
            </thead>
            
            <tbody id="rpt-tbody">
                <?php if (isset($query) && $query && mysqli_num_rows($query) > 0): ?>
                    <?php
                    $total_earned = 0;
                    $total_settled = 0;
                    
                    // Handle Opening Balance
                    if (isset($opening_balance) && $opening_balance > 0) {
                        $total_earned += $opening_balance;
                        echo "<tr>
                                <td style='white-space: nowrap;'>".date('d-M', strtotime($from_date))."</td>
                                <td><strong>To Opening Balance</strong></td>
                                <td class='text-right' style='white-space: nowrap;'><strong>".number_format($opening_balance, 2)."</strong></td>
                                <td class='text-right'></td>
                              </tr>";
                    } elseif (isset($opening_balance) && $opening_balance < 0) {
                        $total_settled += abs($opening_balance);
                        echo "<tr>
                                <td style='white-space: nowrap;'>".date('d-M', strtotime($from_date))."</td>
                                <td><strong>By Opening Balance</strong></td>
                                <td class='text-right'></td>
                                <td class='text-right' style='white-space: nowrap;'><strong>".number_format(abs($opening_balance), 2)."</strong></td>
                              </tr>";
                    }

                    // Process Rows
                    while($row = mysqli_fetch_assoc($query)) {
                        $date = date('d-M', strtotime($row['created_at']));
                        
                        // Define short names for long transaction types
                        $short_types = [
                            'commission_earned' => 'Commission',
                            'mrc_settlement'    => 'Settlement',
                            'settled_to_bill'   => 'Bill Adjusted'
                        ];

                        // If it's in the array, use the short name. Otherwise, format the original text.
                        $raw_type = strtolower($row['transaction_type']);
                        $vch_type = $short_types[$raw_type] ?? ucwords(str_replace('_', ' ', $raw_type));
                        $vch_no = htmlspecialchars($row['settled_bill_no'] ?? $row['reference_id'] ?? '-');

                        $earned = 0;
                        $settled = 0;

                        // Check Model's balance_action
                        if (strtolower($row['balance_action']) === 'increase') {
                            $earned = (float)$row['amount'];
                            $total_earned += $earned;
                        } elseif (strtolower($row['balance_action']) === 'decrease') {
                            $settled = (float)$row['amount'];
                            $total_settled += $settled;
                        }
                ?>
                        <tr>
                            <td style="white-space: nowrap;"><?= $date ?></td>
                            <td>
                                <?php if ($vch_type == 'Commission'){  ?>
                                <a href="<?= BASE_URL ?>commission/view/<?= $vch_no ?>" 
                                   style="color: #0d6efd; text-decoration: none; font-weight: 600;"
                                   onmouseover="this.style.textDecoration='underline'" 
                                   onmouseout="this.style.textDecoration='none'">
                                    <?= $vch_type ?> 
                                    <?= $vch_no !== '-' ? '<br><small class="text-muted" style="color: #6c757d;">(#'.$vch_no.')</small>' : '' ?>
                                </a>
                               <? } else {?>
                               <!-- <a 
                                   style="color: #0d6efd; text-decoration: none; font-weight: 600;"
                                   onmouseover="this.style.textDecoration='underline'" 
                                   onmouseout="this.style.textDecoration='none'">
                                    <?= $vch_type ?> 
                                    <?= $vch_no !== '-' ? '<br><small class="text-muted" style="color: #6c757d;">(#'.$vch_no.')</small>' : '' ?>
                                </a> -->
                                <?php } ?>
                            </td>
                            <td class="text-right" style="color: #5cb85c; font-weight: 500; white-space: nowrap;"><?= $earned > 0 ? number_format($earned, 2) : '' ?></td>
                            <td class="text-right" style="color: #d9534f; font-weight: 500; white-space: nowrap;"><?= $settled > 0 ? number_format($settled, 2) : '' ?></td>
                        </tr>
                <?php
                    }
                ?>
                
                <?php else: ?>
                    <tr>
                        <td colspan="4">
                            <div class="state-screen" style="padding: 40px; text-align: center; color: #6c757d;">
                                <div style="font-size: 30px; margin-bottom: 10px;">📋</div>
                                <div>No commission data found for the selected dates.</div>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>

            <!-- Balancing Footer -->
            <?php if (isset($query) && $query && mysqli_num_rows($query) > 0): ?>
            <?php
                // Explicitly Round to avoid floating point math errors
                $total_earned = round($total_earned, 2);
                $total_settled = round($total_settled, 2);
                
                $closing_balance = round($total_earned - $total_settled, 2);
                $grand_total = max($total_earned, $total_settled);
            ?>
            <tfoot id="rpt-tfoot">
                <tr style="background: #f8f9fa;">
                    <td colspan="2"></td>
                    <td class="text-right" style="border-top: 1px solid #ccc; white-space: nowrap;"><strong><?= number_format($total_earned, 2) ?></strong></td>
                    <td class="text-right" style="border-top: 1px solid #ccc; white-space: nowrap;"><strong><?= number_format($total_settled, 2) ?></strong></td>
                </tr>
                <tr style="background: #f8f9fa;">
                    <td colspan="2" class="text-right" style="border:none !important; padding-right: 15px;">
                        <strong><?= $closing_balance > 0 ? 'By' : 'To' ?> Closing Balance</strong>
                    </td>
                    <td class="text-right text-danger" style="border:none !important; white-space: nowrap;">
                        <strong><?= $closing_balance < 0 ? number_format(abs($closing_balance), 2) : '' ?></strong>
                    </td>
                    <td class="text-right text-danger" style="border:none !important; white-space: nowrap;">
                        <strong><?= $closing_balance > 0 ? number_format($closing_balance, 2) : '' ?></strong>
                    </td>
                </tr>
                <tr style="background: #e9ecef;">
                    <td colspan="2" class="text-right" style="padding-right: 15px;"><strong>Grand Total</strong></td>
                    <td class="text-right" style="border-top: 2px solid #333; border-bottom: 2px double #333; white-space: nowrap;"><strong><?= number_format($grand_total, 2) ?></strong></td>
                    <td class="text-right" style="border-top: 2px solid #333; border-bottom: 2px double #333; white-space: nowrap;"><strong><?= number_format($grand_total, 2) ?></strong></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>

</div><!-- /.page-content -->

<?php include 'view/layout/footer.php'; ?>

<script>
    function loadReport() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        // Build the URL using GET parameters to hit your controller logic
        const url = new URL(window.location.href);
        url.searchParams.set('start_date', startDate);
        url.searchParams.set('end_date', endDate);

        window.location.href = url.toString();
    }
</script>