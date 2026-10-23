<?php 
$pageTitle = "Out standing Report";
include 'view/layout/header.php'; ?>

<style>
    .detail { display: flex; justify-content: flex-end; padding: 6px; }
    .dt-search { float: right; }
    .btn-primary { margin-top: 1.5rem !important; }
    .dt-paging { float: right; margin-top: -20px; }
</style>

<div id="container">
    <div class="detail">
        <a href="<?= BASE_URL ?>login/logout">
            <button type="button" class="btn btn-secondary btn-sm">Logout</button>
        </a>
    </div>

    <a href="javascript:history.back()" class="btn btn-secondary">
        <i class="fa fa-arrow-left"></i> Back
    </a>

    <hr style="margin: 10px 0; border-top: 1px solid #333;">
    <h3>Stock And Sales Super Stockist</h3>

    <div class="container border px-3 py-3">
        <form method="GET" class="row mb-3">
            <div class="col-lg-3">
                <div class="form-group">
                    <label>Select Super Stockist</label>
                    <select name="sup_stockist" id="sup_stockist" class="form-control" required>
                        <option value="">Select Super Stockist</option>
                        <?php while ($srow = mysqli_fetch_assoc($stockist)): ?>
                            <option value="<?= htmlspecialchars($srow['super_stockist_id']) ?>"
                                <?= (isset($super_stockist_id) && $super_stockist_id == $srow['super_stockist_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($srow['ss_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-block">Search</button>
            </div>
        </form>
    </div>

 <!-- ===================== OUTSTANDING BY STOCKIST ===================== -->
<?php
$out_rows      = $outstanding['stockists'] ?? [];
$total_due     = $outstanding['total_due'] ?? 0;
$total_advance = $outstanding['total_advance'] ?? 0;
$net_total     = $outstanding['net_outstanding'] ?? 0;
$target_date   = $to_date ?? $as_on_date ?? date('Y-m-d');
?>
<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white py-3">
        <div class="row align-items-center g-2">
            <div class="col-md-6">
                <h4 class="card-title mb-0">Stockist Balance Summary</h4>
                <small class="text-muted">As on: <strong><?= date('d-M-Y', strtotime($target_date)) ?></strong></small>
            </div>
            <div class="col-md-6 text-md-end d-flex gap-2 justify-content-md-end">
                <input type="text" id="tableQuickSearch" class="form-control form-control-sm w-auto"
                       placeholder="Search stockist or HQ...">
               
                <button type="button" class="btn btn-sm btn-outline-success"
                        onclick="exportOutstandingToCSV('outstandingTable', 'stockist_balance_summary')">
                    <i class="fa fa-file-csv"></i> Export CSV
                </button>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <?php if (isset($outstanding['error'])): ?>
            <div class="alert alert-danger m-3"><?= htmlspecialchars($outstanding['error']) ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0" id="outstandingTable">
                <thead class="table-secondary">
                    <tr>
                        <th>Stockist Name</th>
                        <th>Headquarter</th>
                        <th class="text-center" style="width:110px;">Status</th>
                        <th class="text-end" style="width:150px;">Outstanding (Dr)</th>
                        <th class="text-end" style="width:150px;">Advance (Cr)</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($out_rows as $r):
                    $due     = (float)($r['due_amount'] ?? 0);
                    $advance = (float)($r['advance_amount'] ?? 0);
                    $status  = $r['type'] ?? ($due > 0 ? 'Due' : ($advance > 0 ? 'Advance' : 'Clear'));
                ?>
                    <tr>
                        <td>
                            <!-- TODO: replace with your real ledger route -->
                            <a href="<?= BASE_URL ?>YOUR_CONTROLLER/payment_ledger?stockist_id=<?= (int)$r['stockist_id'] ?>&to_date=<?= urlencode($target_date) ?>"
                               class="text-decoration-none fw-semibold text-primary">
                                <?= htmlspecialchars($r['stockist_name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($r['hq_name']) ?></td>
                        <td class="text-center">
                            <?php if ($status === 'Due'): ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger">Due</span>
                            <?php elseif ($status === 'Advance'): ?>
                                <span class="badge bg-success-subtle text-success border border-success">Advance</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary">Settled</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end <?= $due > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                            <?= $due > 0 ? number_format($due, 2) : '-' ?>
                        </td>
                        <td class="text-end <?= $advance > 0 ? 'text-success fw-bold' : 'text-muted' ?>">
                            <?= $advance > 0 ? number_format($advance, 2) : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($out_rows)): ?>
                    <tr class="no-data">
                        <td colspan="5" class="text-center text-muted py-4">No stockist balance records found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr class="table-secondary">
                        <td colspan="3" class="text-end">Total</td>
                        <td class="text-end text-danger"><?= number_format($total_due, 2) ?></td>
                        <td class="text-end text-success"><?= number_format($total_advance, 2) ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end">
                            Net Balance (<?= $net_total >= 0 ? 'Net Receivable' : 'Net Advance' ?>)
                        </td>
                        <td colspan="2" class="text-end fs-6 <?= $net_total >= 0 ? 'text-danger' : 'text-success' ?>">
                            <?= number_format(abs($net_total), 2) ?> <?= $net_total >= 0 ? 'Dr' : 'Cr' ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Client-side Quick Search & CSV Exporter -->
<script>
document.getElementById('tableQuickSearch')?.addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#outstandingTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

function exportOutstandingToCSV(tableId, filename = 'report') {
    const table = document.getElementById(tableId);
    if (!table) return;

    const csv = [];

    table.querySelectorAll('tr').forEach(row => {
        if (row.style.display === 'none') return;      // skip rows hidden by the search box
        if (row.classList.contains('no-data')) return; // skip the "No records" row

        const rowData = [];

        row.querySelectorAll('th, td').forEach(cell => {
            let data = cell.innerText
                .replace(/(\r\n|\n|\r)/gm, ' ')
                .replace(/\s\s+/g, ' ')
                .trim()
                .replace(/"/g, '""');

            rowData.push('"' + data + '"');

            // Pad the columns this cell spans, so the footer lines up with the header
            const span = parseInt(cell.getAttribute('colspan') || '1', 10);
            for (let i = 1; i < span; i++) {
                rowData.push('""');
            }
        });

        csv.push(rowData.join(','));
    });

    // \ufeff (BOM) lets Excel read UTF-8 correctly
    const blob = new Blob(['\ufeff' + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename + '_' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
    URL.revokeObjectURL(link.href);
}
</script>

<?php include 'view/layout/footer.php'; ?>

<script>
$(document).ready(function () {

    setTimeout(function () {
        document.querySelectorAll('.alert').forEach(function (a) { a.style.display = 'none'; });
    }, 5000);

    

    $('#stockReportTable').DataTable({
        destroy: true,
        responsive: true,
        pageLength: 50,
        dom: 'Bfrtip',
        language: { emptyTable: "No records found" },
        buttons: [{
            extend: 'excelHtml5',
            text: '<i class="fa fa-file-excel"></i> Export Excel',
            className: 'btn btn-success',
            filename: 'Stock_Sales_Report',
            title: 'Stock And Sales Report',
            messageTop: 'From Date : ' +  + ' | To Date : ' + ,
            footer: true,
            exportOptions: { columns: ':visible' }
        }]
    });

    $('#outstandingTable').DataTable({
        destroy: true,
        responsive: true,
        pageLength: 50,
        order: [],                      // keep the server order (highest outstanding first)
        dom: 'Bfrtip',
        language: { emptyTable: "No stockists found" },
        buttons: [{
            extend: 'excelHtml5',
            text: '<i class="fa fa-file-excel"></i> Export Outstanding',
            className: 'btn btn-success',
            filename: 'Super_Stockist_Outstanding',
            title: 'Outstanding by Stockist',
            messageTop: 'As on : ' + ,
            footer: true
        }]
    });
});
</script>