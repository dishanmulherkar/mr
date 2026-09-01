<?php 
$pageTitle = "Ledger Account";
include 'view/layout/header.php'; 
?>
 <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<style>
    .detail { display: flex; justify-content: flex-end; padding: 6px; }
    .dt-search { float: right; }
    .btn-primary { margin-top: 1.5rem !important; }
    
    /* Clean Bordered UI Style */
    .tally-table { border-collapse: collapse; }
    .tally-table th { 
        border: 1px solid #dee2e6 !important; 
        border-bottom: 2px solid #333 !important; 
        border-top: 2px solid #333 !important; 
        background-color: #f8f9fa;
        vertical-align: middle; 
    }
    .tally-table td { 
        border: 1px solid #dee2e6 !important; /* Added clear inner borders */
        padding: 8px 10px; 
        vertical-align: middle; 
    }
    .tally-footer td { 
        border-top: 2px solid #333 !important; 
        border-bottom: 1px solid #333 !important; 
        font-weight: bold; 
        background-color: #fcfcfc;
    }
    .tally-grand-total td { 
        border-bottom: 2px solid #333 !important; 
        font-weight: bold; 
        background-color: #f8f9fa;
    }
</style>
 
<div id="container">
    <div class="detail">
        <a href="<?= BASE_URL ?>login/logout">
            <button type="button" class="btn btn-secondary btn-sm">Logout</button>
        </a>
    </div>
    
    <a href="javascript:history.back()" class="btn btn-secondary mb-3">
        <i class="fa fa-arrow-left"></i> Back
    </a>
    
    <!-- Filter Section -->
    <div class="container border px-3 py-3 mb-4 print-hide">
        <form method="GET" class="row mb-0">
            <div class="col-lg-3">
                <label>Select State</label>
                <select name="state" id="state_id" class="form-control" required>
                    <option value="">Select State</option>
                    <?php if($states): while($srow = mysqli_fetch_assoc($states)): ?>
                        <option value="<?= $srow['state_id']; ?>" <?= ($state_id == $srow['state_id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($srow['state_name']); ?>
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <label>Head Quarter</label>
                <select name="hq_id" id="hq_id" class="form-control select2" required>
                    <option value="">Select HQ</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label>Stockist</label>
                <select name="stockist_id" id="stockist_id" class="form-control select2" required>
                    <option value="">Select Stockist</option>
                </select>
            </div>
            <div class="col-md-2">
                <label>From Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
            </div>
            <div class="col-md-2">
                <label>To Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
            </div>
            <div class="col-md-12 text-end mt-3">
                <button type="submit" class="btn btn-primary px-4">Search</button>
            </div>
        </form>
    </div>

    <!-- Ledger Report Section -->
    <?php if($stockist_id > 0): ?>
    <div class="bg-white p-4 border" id="print-area">
        
        <div class="table-responsive">
            <table class="table table-striped table-hover tally-table w-100" id="ledgerReportTable">
                <thead>
                    <tr>
                        <th style="width: 12%">Date</th>
                        <th style="width: 38%">Particulars</th>
                        <th style="width: 15%">Vch Type</th>
                        <th style="width: 10%">Vch No.</th>
                        <th style="width: 12.5%" class="text-end">Debit</th>
                        <th style="width: 12.5%" class="text-end">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_debit = 0;
                    $total_credit = 0;
                    
                    // Handle Opening Balance
                    if (isset($opening_balance) && $opening_balance > 0) {
                        $total_debit += $opening_balance;
                        echo "<tr>
                                <td>".date('d-M-y', strtotime($from_date))."</td>
                                <td><strong>To Opening Balance</strong></td>
                                <td></td><td></td>
                                <td class='text-end'><strong>".number_format($opening_balance, 2)."</strong></td>
                                <td class='text-end'></td>
                            </tr>";
                    } elseif (isset($opening_balance) && $opening_balance < 0) {
                        $total_credit += abs($opening_balance);
                        echo "<tr>
                                <td>".date('d-M-y', strtotime($from_date))."</td>
                                <td><strong>By Opening Balance</strong></td>
                                <td></td><td></td>
                                <td class='text-end'></td>
                                <td class='text-end'><strong>".number_format(abs($opening_balance), 2)."</strong></td>
                            </tr>";
                    }

                    // Process Rows
                    if($query && mysqli_num_rows($query) > 0) {
                        while($row = mysqli_fetch_assoc($query)) {
                            $date = date('d-M-y', strtotime($row['created_at']));
                            
                            if ($row['transaction_type'] == 'bill_added') {
                                // 1. Bill Added (Debit)
                                $particulars = "To Sales Bill";
                                $vch_type = "Tax Invoice";
                                $vch_no = htmlspecialchars($row['inward_no'] ?? '');
                                $debit = round((float)$row['amount'], 2);
                                $credit = 0;
                                $total_debit += $debit;
                                
                            } else {
                                // 2. Credits (Payments & Commission Settlements)
                                $vch_no = htmlspecialchars($row['pay_id'] ?? '');
                                
                                // Fetch variables for Particulars processing
                                $raw_notes = !empty($row['notes']) ? htmlspecialchars($row['notes']) : "By Receipt";
                                $bank_name = !empty($row['bank_name']) ? htmlspecialchars($row['bank_name']) : "";
                                $payment_method = !empty($row['payment_method']) ? htmlspecialchars($row['payment_method']) : "";
                                
                                // Apply Particulars Logic (Regex for CD, Fallbacks for Banks)
                                if (stripos($raw_notes, '4% CD') !== false || stripos($raw_notes, '2% CD') !== false) {
                                    if (preg_match('/((?:4%|2%) CD) on Invoice ([a-zA-Z]+-\d+)/i', $raw_notes, $matches)) {
                                        $particulars = $matches[1] . " on " . $matches[2];
                                    } else {
                                        $particulars = explode(':', $raw_notes)[0]; 
                                    }
                                } else {
                                    if (!empty($payment_method) && !empty($bank_name)) {
                                        $particulars = "{$payment_method} - {$bank_name}";
                                    } elseif (!empty($payment_method)) {
                                        $particulars = $payment_method;
                                    } else {
                                        $particulars = $raw_notes;
                                    }
                                }

                                $raw_amount = (float)$row['amount'];
                                $debit = 0;
                                
                                // ==============================================
                                // FIX: ROUND OFF CD AMOUNTS TO NEAREST INTEGER
                                // ==============================================
                                if (stripos($raw_notes, 'CD') !== false) {
                                    $credit = round($raw_amount); // Rounds 177.23 -> 177.00
                                } else {
                                    $credit = round($raw_amount, 2);
                                }
                                
                                $total_credit += $credit;

                                // Differentiate Voucher Type based on the transaction flag
                                if (in_array($row['transaction_type'], ['mrc_settlement', 'drc_settlement', 'settled_to_bill'])) {
                                    $vch_type = "Adjustment"; 
                                } else {
                                    $vch_type = "Receipt";    
                                }
                            }
                    ?>
                            <tr>
                                <td><?= $date ?></td>
                                <td><?= $particulars ?></td>
                                <td><?= $vch_type ?></td>
                                <td><?= $vch_no ?></td>
                                <td class="text-end"><?= $debit > 0 ? number_format($debit, 2) : '' ?></td>
                                <td class="text-end"><?= $credit > 0 ? number_format($credit, 2) : '' ?></td>
                            </tr>
                    <?php
                        }
                    }
                    ?>
                </tbody>

                <!-- Balancing Footer -->
                <?php
                // Explicitly Round to avoid floating point math errors
                $total_debit = round($total_debit, 2);
                $total_credit = round($total_credit, 2);
                
                $closing_balance = round($total_debit - $total_credit, 2);
                $grand_total = max($total_debit, $total_credit);
                ?>
                <tr class="tally-footer">
                    <td colspan="4"></td>
                    <td class="text-end"><?= number_format($total_debit, 2) ?></td>
                    <td class="text-end"><?= number_format($total_credit, 2) ?></td>
                </tr>
                <tr style="border-top:none !important; border-bottom:none !important;">
                    <td colspan="4" class="text-end pe-4" style="border:none !important;"><strong><?= $closing_balance > 0 ? 'By' : 'To' ?> Closing Balance</strong></td>
                    <td class="text-end text-danger" style="border:none !important; border-left:1px solid #dee2e6 !important;"><strong><?= $closing_balance < 0 ? number_format(abs($closing_balance), 2) : '' ?></strong></td>
                    <td class="text-end text-danger" style="border:none !important; border-left:1px solid #dee2e6 !important; border-right:1px solid #dee2e6 !important;"><strong><?= $closing_balance > 0 ? number_format($closing_balance, 2) : '' ?></strong></td>
                </tr>
                <tr class="tally-grand-total">
                    <td colspan="4"></td>
                    <td class="text-end"><?= number_format($grand_total, 2) ?></td>
                    <td class="text-end"><?= number_format($grand_total, 2) ?></td>
                </tr>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'view/layout/footer.php'; ?>

<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<!-- Include PDF/Print Scripts in Footer -->
<script>
$(document).ready(function () {
    var state_id = <?= (int)$state_id ?>;
    var hq_id = <?= (int)$hq_id ?>;
    var stockist_id = <?= (int)$stockist_id ?>;

    $('#ledgerReportTable').DataTable({
        destroy: true,
        ordering: false, 
        paging: false,
        info: false,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'pdfHtml5',
                text: '<i class="fa fa-file-pdf"></i> Export PDF',
                className: 'btn btn-danger',
                title: '', 
                customize: function (doc) {
                    
                    var customHeader = {
                        columns: [
                            {
                                width: '33%',
                                text: [
                                    { text: 'RUDRADEO INCORPORATES - (from 1-Apr-2019)\n', bold: true },
                                    'Railway Co-Operative Colony,\n',
                                    'Digha Ghat,\n',
                                    'Patna,\n',
                                    'Bihar.'
                                ],
                                fontSize: 9,
                                alignment: 'left'
                            },
                            {
                                width: '34%',
                                text: [
                                    { text: '<?= addslashes($stockist_name ?? "Ledger Report") ?>\n', fontSize: 13, bold: true },
                                    { text: 'Ledger Account\n', fontSize: 11 },
                                    { text: '<?= date("j-M-y", strtotime($from_date)) ?> to <?= date("j-M-y", strtotime($to_date)) ?>', fontSize: 9 }
                                ],
                                alignment: 'center',
                                margin: [0, 5, 0, 0]
                            },
                            {
                                width: '33%',
                                text: [
                                    'Registry Office\n',
                                    'Cinema Road\n',
                                    'Sheohar 843329, Bihar'
                                ],
                                fontSize: 9,
                                alignment: 'right'
                            }
                        ],
                        margin: [0, 0, 0, 15] 
                    };

                    doc.content.unshift(customHeader);

                    doc.content[1].table.widths = ['12%', '38%', '15%', '10%', '12.5%', '12.5%'];
                    doc.styles.tableHeader.alignment = 'left';
                    
                    var rowCount = doc.content[1].table.body.length;
                    for (i = 1; i < rowCount; i++) {
                        doc.content[1].table.body[i][4].alignment = 'right';
                        doc.content[1].table.body[i][5].alignment = 'right';
                    }

                    // Apply UI Borders to the PDF export to match the HTML Table
                    doc.content[1].layout = {
                        hLineWidth: function (i, node) { return 1; },
                        vLineWidth: function (i, node) { return 1; },
                        hLineColor: function (i) { return '#dee2e6'; },
                        vLineColor: function (i) { return '#dee2e6'; },
                        paddingTop: function(i, node) { return 5; },
                        paddingBottom: function(i, node) { return 5; }
                    };
                }
            }
        ]
    });

    $('.select2').select2({ theme: 'bootstrap-5' });

    function loadHQ(state, selected) {
        if(!state) return;
        $.post('<?= BASE_URL ?>customer/getHQs', {state_id: state, selected_id: selected}, function(res){
            $('#hq_id').html(res);
            if(selected) loadStockist(selected, stockist_id);
        });
    }

    function loadStockist(hq, selected) {
        if(!hq) return;
        $.post('<?= BASE_URL ?>stock_inward/getStockists', {hq_id: hq, selected_id: selected}, function(res){
            $('#stockist_id').html(res);
        });
    }

    $('#state_id').change(function(){ loadHQ($(this).val(), ''); });
    $('#hq_id').change(function(){ loadStockist($(this).val(), ''); });

    if(state_id > 0) loadHQ(state_id, hq_id);
});
</script>