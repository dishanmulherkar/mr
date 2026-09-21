<?php 
$pageTitle = "Primary Sale Report";
include 'view/layout/header-asm.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>config/Addons/asm-view.css">
<style>
/* ─── Desktop Baseline ────────────────────────────── */
.rpt-filter-bar { 
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px; 
    background: #fff;
    padding: 6px 10px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    margin-bottom: 10px;
}

.date-group {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.rpt-filter-bar label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    white-space: nowrap;
    margin: 0;
}

.rpt-filter-bar select,
.rpt-filter-bar input[type="date"] {  
    font-size: 12px;
    height: 30px;
    padding: 2px 8px;
    border-radius: 5px;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #1e293b;
    flex: 1 1 auto;
    min-width: 110px; 
}

.btn-search { 
    height: 30px;
    padding: 0 14px;
    font-size: 12px;
    font-weight: 600;
    background: #0284c7;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.btn-search:hover { background: #0369a1; }

.rpt-table-wrap { overflow-x: auto; }
.rpt-table-wrap table { min-width: 650px; background: #fff; }

.badge-pill {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.3px;
    background: #f1f5f9;
    color: #475569;
}

.text-right { text-align: right !important; }
.text-center { text-align: center !important; }

/* ─── Modern Mobile Card UI (<= 768px) ─────────────── */
@media (max-width: 768px) {
    .page-content {
        padding: 10px;
    }
    
    .rpt-filter-bar {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        padding: 12px;
    }
    .rpt-filter-bar label {
        margin-bottom: -4px;
        font-size: 12px;
    }
    .rpt-filter-bar select,
    .rpt-filter-bar input[type="date"],
    .btn-search {
        width: 100% !important;
        min-width: 92% !important;
    }

    /* Remove regular table elements */
    .rpt-table-wrap {
        overflow: visible !important;
    }
    .rpt-table-wrap table {
        min-width: 100% !important;
        background: transparent !important;
        border: none !important;
    }
    .rpt-table-wrap thead {
        display: none !important;
    }
    .rpt-table-wrap tbody {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    /* Card styling for each row */
    .rpt-table-wrap tbody tr {
        display: grid !important;
        grid-template-columns: 1fr auto;
        grid-template-areas:
            "party badge"
            "date amount";
        row-gap: 6px;
        align-items: center;
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        padding: 12px 14px !important;
        box-shadow: 0 2px 4px rgba(15, 23, 42, 0.04);
    }

    .rpt-table-wrap tbody td {
        display: block !important;
        padding: 0 !important;
        border: none !important;
        font-size: 13px !important;
    }

    /* Card Layout Specifics */
    .td-party {
        grid-area: party;
        font-size: 15px !important;
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .td-inv {
        grid-area: badge;
        text-align: right;
    }

    .td-date {
        grid-area: date;
        font-size: 12px !important;
        color: #94a3b8;
        font-weight: 500;
    }

    .td-amount {
        grid-area: amount;
        text-align: right !important;
        font-size: 16px !important;
        font-weight: 700 !important;
        color: #0f172a !important;
    }
    .td-amount::before {
        content: "₹ ";
        font-size: 13px;
        color: #64748b;
    }

    /* Empty state message */
    .rpt-table-wrap tbody tr.empty-row {
        display: block !important;
        background: #fff !important;
        border: 1px dashed #cbd5e1 !important;
        padding: 24px 12px !important;
    }
    .rpt-table-wrap tbody tr.empty-row td {
        text-align: center !important;
    }

    /* Mobile Sticky Total Card */
    .rpt-table-wrap tfoot {
        display: block !important;
        position: sticky;
        bottom: 12px;
        margin-top: 14px;
        z-index: 10;
    }
    .rpt-table-wrap tfoot tr {
        display: flex !important;
        justify-content: space-between;
        align-items: center;
        /* background: #1e293b !important; */
        color: #ffffff;
        border-radius: 10px;
        padding: 12px 16px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
    }
    .rpt-table-wrap tfoot td {
        display: inline-block !important;
        border: none !important;
        padding: 0 !important;
        font-size: 14px !important;
    }
    .rpt-table-wrap tfoot td.total-val {
        font-size: 17px !important;
        font-weight: 700;
        color: #38bdf8 !important;
    }
}
/* Add clearing space at the bottom for fixed/floating mobile navbars */
@media (max-width: 768px) {
    .page-content {
        padding: 10px 10px 55px 10px !important; /* 90px bottom buffer */
    }

    /* Elevate sticky footer slightly above the bottom bar */
    .rpt-table-wrap tfoot {
        bottom: 8px !important; /* Adjust if your bottom bar height is ~60px */
    }
}
</style>

<div class="page-content">

    <!-- Filters -->
    <div class="rpt-filter-bar">
        <div class="date-group">
            <label>From:</label>
            <input type="date" id="start_date" value="<?= htmlspecialchars($from_date) ?>">
        </div>

        <div class="date-group">
            <label>To:</label>
            <input type="date" id="end_date" value="<?= htmlspecialchars($to_date) ?>">
        </div>

        <select id="hq" class="filter-pill">
            <option value="">HeadQuaters</option>
            <?php foreach ($hq as$h): ?>
                <option value="<?= $h['headquarter_id'] ?>" <?= ($hq_id ==$h['headquarter_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($h['hq_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="stockist" class="filter-pill">
            <option value="">All Stockists</option>
            <?php if (!empty($stockists)): ?>
                <?php foreach ($stockists as$s): ?>
                    <option value="<?= $s['stockist_id'] ?>" <?= ($stockist_id ==$s['stockist_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['stockist_name']) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>

        <button class="btn-search" onclick="loadReport()">Search</button>
    </div>

    <!-- Report Table / Card View on Mobile -->
    <div class="rpt-table-wrap">
        <table class="table table-bordered ">
            <thead>
                <tr>
                    <th style="width: 15%;">Inv No</th>
                    <th style="width: 15%;">Date</th>
                    <th>Party Name</th>
                    <th class="text-right" style="width: 20%;">Amt</th>
                </tr>
            </thead>
            
            <tbody id="rpt-tbody">
                <?php if (isset($query) && $query && mysqli_num_rows($query) > 0): ?>
                    <?php
                    $total_amount = 0;
                    while ($row = mysqli_fetch_assoc($query)):$inv_no     = htmlspecialchars($row['inward_no'] ?? '-');$date       = !empty($row['inward_date']) ? date('d-M-Y', strtotime($row['inward_date'])) : '-';
                        $party_name = htmlspecialchars($row['stockist_name'] ?? '-');
                        $amount     = (float)($row['grand_total'] ?? 0);
                        
                        $total_amount +=$amount;
                    ?>
                        <tr>
                            <td class="td-party"><strong><?= $party_name ?></strong></td>
                            <td class="td-inv">
                                <span class="badge-pill">
                                    <?= $inv_no ?>
                                </span>
                            </td>
                            <td class="td-date"><?= $date ?></td>
                            <td class="td-amount text-right"><?= number_format($amount, 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    
                <?php elseif ($hq_id > 0): ?>
                    <tr class="empty-row">
                        <td colspan="4">
                            <div class="state-screen" style="padding: 20px; text-align: center;">
                                <div class="state-msg" style="font-weight: 600; color: #475569;">No primary sales found</div>
                                <span style="font-size: 13px; color: #94a3b8;">No records match your selected dates or filters.</span>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <tr class="empty-row">
                        <td colspan="4">
                            <div class="state-screen" style="padding: 20px; text-align: center;">
                                <div style="font-size: 28px; margin-bottom: 6px;">📋</div>
                                <div class="state-msg" style="font-weight: 600; color: #475569;">Select filters</div>
                                <span style="font-size: 13px; color: #94a3b8;">Choose HQ and Date range, then click Search.</span>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>

            <?php if (isset($query) && $query && mysqli_num_rows($query) > 0): ?>
            <tfoot id="rpt-tfoot">
                <tr>
                    <td colspan="3" class="text-right desktop-label">Total Sales:</td>
                    <td class="text-right total-val">₹ <?= number_format($total_amount, 2) ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>

</div>

<?php include 'view/layout/footer-asm.php'; ?>

<script>
const BASE_URL = "<?= BASE_URL ?>";
const preSelectedStockistId = "<?= (int)($stockist_id ?? $stockist_ids ?? 0) ?>";

function fetchStockists(hq_id, selectedStockistId = "") {
    const stockistDropdown = $('#stockist');
    stockistDropdown.html('<option value="">All Stockists</option>');

    if (!hq_id) return;

    $.ajax({
        type: "POST",
        url: BASE_URL + "Order/get_stockists_by_hq",
        data: { hq_id: hq_id },
        dataType: "json",
        success: function (response) {
            if (response.success && response.data.length > 0) {
                response.data.forEach(function (s) {
                    const isSelected = (String(s.stockist_id) === String(selectedStockistId)) ? 'selected' : '';
                    stockistDropdown.append(`<option value="${s.stockist_id}" ${isSelected}>${s.stockist_name}</option>`);
                });
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", error);
        }
    });
}

$(document).ready(function () {$('#hq').on('change', function () {
        fetchStockists($(this).val(), "");
    });

    const currentHqId = $('#hq').val();
    if (currentHqId) {
        fetchStockists(currentHqId, preSelectedStockistId);
    }
});

function loadReport() {
    const hqId = document.getElementById('hq').value;
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const stockistId = document.getElementById('stockist').value;

    if (!hqId) {
        alert("Please select a HeadQuarter first.");
        return;
    }

    const url = new URL(window.location.href);
    url.searchParams.set('hq_id', hqId);
    url.searchParams.set('stockist_id', stockistId);
    url.searchParams.set('start_date', startDate);
    url.searchParams.set('end_date', endDate);

    window.location.href = url.toString();
}

function downloadPDFs() {
    alert("PDF download triggered.");
}
</script>