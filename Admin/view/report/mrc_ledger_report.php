<?php 
$pageTitle = "MRC Ledger Account";
include 'view/layout/header.php'; 
?>
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<style>
    .detail { display: flex; justify-content: flex-end; padding: 6px; }
    .dt-search { float: right; }
    .btn-primary { margin-top: 1.5rem !important; }
    /* Tally style HTML table */
    .tally-table th { border-bottom: 2px solid #333; border-top: 2px solid #333; }
    .tally-table td { border: none !important; padding: 8px 10px; }
    .tally-footer td { border-top: 1px solid #333; border-bottom: 2px solid #333; font-weight: bold; }
    .tally-grand-total td { border-bottom: 2px solid #333; font-weight: bold; }
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
    <div class="bg-white p-4 border" id="print-area">
        <div class="table-responsive">
            <table class="table tally-table w-100" id="ledgerReportTable">
                <thead>
                    <tr>
                        <th style="width: 10%">Date</th>
                        <th style="width: 25%">Particulars</th>
                        <th style="width: 15%">Vch Type</th>
                        <th style="width: 12.5%" class="text-end">Debit (Settled)</th>
                        <th style="width: 12.5%" class="text-end">Credit (Earned)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_debit = 0;
                    $total_credit = 0;
                    
                    // Handle Opening Balance for Wallet (Positive = Credit/Available, Negative = Debit/Overpaid)
                    if (isset($opening_balance) && $opening_balance > 0) {
                        $total_credit += $opening_balance;
                        echo "<tr>
                                <td>".date('d-M-y', strtotime($from_date))."</td>
                                <td></td>
                                <td><strong>By Opening Balance</strong></td>
                                <td></td>
                                <td class='text-end'></td>
                                <td class='text-end'><strong>".number_format($opening_balance, 2)."</strong></td>
                              </tr>";
                    } elseif (isset($opening_balance) && $opening_balance < 0) {
                        $total_debit += abs($opening_balance);
                        echo "<tr>
                                <td>".date('d-M-y', strtotime($from_date))."</td>
                                <td></td>
                                <td><strong>To Opening Balance</strong></td>
                                <td></td>
                                <td class='text-end'><strong>".number_format(abs($opening_balance), 2)."</strong></td>
                                <td class='text-end'></td>
                              </tr>";
                    }

                    // Process Rows
                    if($query && mysqli_num_rows($query) > 0) {
                        while($row = mysqli_fetch_assoc($query)) {
                            $date = date('d-M-y', strtotime($row['created_at']));
                            $stockist_name = htmlspecialchars($row['stockist_name']);
                            
                            $debit = 0;
                            $credit = 0;
                            $particulars = "";
                            $vch_type = "";

                            // Determine if money was earned (increase) or settled/paid out (decrease)
                            if ($row['balance_action'] == 'increase') {
                                // Earned Commission (Credit)
                                $particulars = "By Commission Earned";
                                $vch_type = "Commission";
                                $credit = (float)$row['amount'];
                                $total_credit += $credit;
                                
                            } else if ($row['balance_action'] == 'decrease') {
                                // Settled or Paid Out (Debit)
                                $debit = (float)$row['amount'];
                                $total_debit += $debit;
                                
                                // UPDATE: Use the transaction_type from your database directly
                                if ($row['transaction_type'] === 'settled_to_bill' || !empty($row['settled_bill_no'])) {
                                    $bill_badge = !empty($row['settled_bill_no']) ? "<span class='badge bg-info text-dark ms-1'>" . $row['settled_bill_no'] . "</span>" : "";
                                    $particulars =  $row['stockist_name'];
                                    $vch_type = "Adjustment";
                                } else {
                                    $particulars = "To Bank Transfer";
                                    $vch_type = "Payment";
                                }
                            }
                    ?>
                            <tr>
                                <td><?= $date ?></td>
                                <td>
                                    <?= $particulars ?><br>
                                    <small class="text-muted" style="font-size:0.75em;"><?= htmlspecialchars($row['notes']) ?></small>
                                </td>
                                <td><?= $vch_type ?></td>
                                <td class="text-end text-danger"><?= $debit > 0 ? number_format($debit, 2) : '' ?></td>
                                <td class="text-end text-success"><?= $credit > 0 ? number_format($credit, 2) : '' ?></td>
                            </tr>
                    <?php
                        }
                    }
                    ?>
                </tbody>

                <!-- Balancing Footer -->
                <?php
                // For a Wallet: Closing Balance = Credit (Earned) - Debit (Paid Out)
                $closing_balance = $total_credit - $total_debit;
                $grand_total = max($total_debit, $total_credit);
                ?>
                <tr class="tally-footer">
                    <td colspan="3"></td>
                    <td class="text-end"><?= number_format($total_debit, 2) ?></td>
                    <td class="text-end"><?= number_format($total_credit, 2) ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end pe-4"><strong><?= $closing_balance > 0 ? 'To' : 'By' ?> Closing Balance</strong></td>
                    <td class="text-end"><strong><?= $closing_balance > 0 ? number_format($closing_balance, 2) : '' ?></strong></td>
                    <td class="text-end"><strong><?= $closing_balance < 0 ? number_format(abs($closing_balance), 2) : '' ?></strong></td>
                </tr>
                <tr class="tally-grand-total">
                    <td colspan="3"></td>
                    <td class="text-end"><?= number_format($grand_total, 2) ?></td>
                    <td class="text-end"><?= number_format($grand_total, 2) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?php include 'view/layout/footer.php'; ?>

<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function () {
    var state_id = <?= (int)$state_id ?>;
    var hq_id = <?= (int)$hq_id ?>;
    var hq_name = "<?= htmlspecialchars($hq_name ?? 'Select HQ') ?>";

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
                                    { text: hq_name + '\n', fontSize: 13, bold: true },
                                    { text: 'MR Commission Ledger\n', fontSize: 11 },
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

                    // Formats columns for PDF Export
                    doc.content[1].table.widths = ['12%', '25%', '23%', '15%', '12.5%', '12.5%'];
                    doc.styles.tableHeader.alignment = 'left';
                    
                    var rowCount = doc.content[1].table.body.length;
                    for (i = 1; i < rowCount; i++) {
                        doc.content[1].table.body[i][4].alignment = 'right';
                        doc.content[1].table.body[i][5].alignment = 'right';
                    }

                    doc.content[1].layout = {
                        hLineWidth: function (i, node) {
                            if (i === 0 || i === 1 || i === node.table.body.length - 3 || i === node.table.body.length - 1 || i === node.table.body.length) {
                                return 1;
                            }
                            return 0;
                        },
                        vLineWidth: function (i, node) { return 0; },
                        hLineColor: function (i) { return '#000000'; },
                        paddingTop: function(i, node) { return 4; },
                        paddingBottom: function(i, node) { return 4; }
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
        });
    }

    $('#state_id').change(function(){ loadHQ($(this).val(), ''); });

    if(state_id > 0) loadHQ(state_id, hq_id);
});
</script>