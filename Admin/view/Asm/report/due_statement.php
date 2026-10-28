<?php 
$pageTitle = "Due Statement";
include 'view/layout/header-asm.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>config/Addons/asm-view.css">
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
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<div class="page-content">

    <!-- ── Filters ────────────────────────────────────── -->
  <div class="rpt-filter-bar" style= "margin-bottom: 0px;">

        <select id="hq" class="filter-pill">
            <option value="">HeadQuaters</option>
            <?php foreach ($hq as $h): ?>
                <!-- ADDED THE SELECTED LOGIC HERE -->
                <option value="<?= $h['headquarter_id'] ?>" <?= ($hq_id == $h['headquarter_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($h['hq_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="stockist" class="filter-pill">
            <option value="">All Stockists</option>
            <?php if (!empty($stockists)): ?>
                <?php foreach ($stockists as $s): ?>
                    <!-- ADDED THE SELECTED LOGIC HERE -->
                    <option value="<?= $s['stockist_id'] ?>" <?= ($stockist_id == $s['stockist_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['stockist_name']) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>

        <button class="btn-search" onclick="loadReport()">Search</button>
    </div>
  
<div style="display: flex; justify-content: flex-end; margin-bottom: 5px;">
    <button type="button" 
            onclick="exportTableToExcel('rpt-table', 'Outstanding_Bills_Report')" 
            class="btn btn-success" 
            style="background-color: #54557c; color: #fff; border: none; padding: 5px 10px; border-radius: 6px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
     Excel
    </button>
</div>
    <!-- ── Report Table ───────────────────────────────── -->
    <div  id="rpt-table" class="rpt-table-wrap">
       <table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Date</th>
            <th>Inv No</th>
            <th>Stockist</th>
            <th class="text-right">Amt</th>
            <th class="text-right">Balance Due</th>
        </tr>
    </thead>
    
    <tbody id="rpt-tbody">
        <?php 
        $total_inv_amt = 0;
        $total_balance_due = 0;
        $has_rows = false;

        if (isset($query) && $query instanceof mysqli_result && mysqli_num_rows($query) > 0): 
            $has_rows = true;
            while ($row = mysqli_fetch_assoc($query)): 
                $date        = !empty($row['inward_date']) ? date('d-m-Y', strtotime($row['inward_date'])) : '-';
                $inv_no      = htmlspecialchars($row['inward_no'] ?? '-');
                $stockist    = htmlspecialchars($row['stockist_name'] ?? '-');
                $amount      = (float)($row['invoice_amount'] ?? 0);
                $balance_due = (float)($row['balance_amount'] ?? 0);

                // Accumulate totals
                $total_inv_amt     += $amount;
                $total_balance_due += $balance_due;
        ?>
                <tr>
                    <td><?= $date; ?></td>
                    <td><strong><?= $inv_no; ?></strong></td>
                    <td><?= $stockist; ?></td>
                    <td class="text-right">
                        <?= number_format($amount, 2); ?>
                    </td>
                    <td class="text-right" style="color: #dc2626; font-weight: 600;">
                        <?= number_format($balance_due, 2); ?>
                    </td>
                </tr>
        <?php 
            endwhile; 
        else: 
        ?>
            <tr>
                <td colspan="5">
                    <div class="state-screen" style="padding: 30px; text-align: center; color: #6b7280;">
                        <div class="state-msg" style="font-weight: 600; font-size: 15px; margin-bottom: 5px;">No outstanding bills found</div>
                        All invoices are cleared or no records match the selected criteria.
                    </div>
                </td>
            </tr>
        <?php endif; ?>
    </tbody>

    <?php if ($has_rows): ?>
        <tfoot id="rpt-tfoot">
            <tr class="tally-footer" style="background: #f8f9fa; font-weight: bold;">
                <td colspan="3" class="text-right" style="padding-right: 15px;">Total Outstanding</td>
                <td class="text-right" style="border-top: 2px solid #333; border-bottom: 2px double #333;">
                    <?= number_format($total_inv_amt, 2); ?>
                </td>
                <td class="text-right" style="border-top: 2px solid #333; border-bottom: 2px double #333; color: #dc2626;">
                    <?= number_format($total_balance_due, 2); ?>
                </td>
            </tr>
        </tfoot>
    <?php endif; ?>
</table>
    </div>

</div><!-- /.page-content -->

<?php include 'view/layout/footer-asm.php'; ?>




<script>
const BASE_URL = "<?= BASE_URL ?>";
    $(document).ready(function () {$('#hq').change(function () {
        const hq_id = $(this).val();
        const stockistDropdown = $('#stockist');

        // Reset the dropdown
        stockistDropdown.html('<option value="">All Stockists</option>');

        if (hq_id) {
            $.ajax({
                type: "POST",
                url: BASE_URL + "Order/get_stockists_by_hq",
                data: { hq_id: hq_id },
                dataType: "json",
                success: function (response) {
                    if (response.success && response.data.length > 0) {
                        response.data.forEach(function (s) {
                            stockistDropdown.append(`<option value="${s.stockist_id}">${s.stockist_name}</option>`);
                        });
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error:", error);
                }
            });
        }
    });
});
    const mr_id = <?= isset($mr_id) ? $mr_id : 0 ?>; 

    // Function to reload the page with URL parameters for the controller
    function loadReport() {
        let hqId = document.getElementById('hq').value;
      
        let stockistId = document.getElementById('stockist').value;
        
        
        // Fallback for hidden input if dropdown is disabled
        if (!stockistId) {
            const hiddenInput = document.getElementById('hidden-stockist');
            if(hiddenInput) stockistId = hiddenInput.value;
        }

        // if (!stockistId) {
        //     alert("Please select a stockist first.");
        //     return;
        // }

        // Build the URL using GET parameters to hit your controller logic
        const url = new URL(window.location.href);
        url.searchParams.set('hq_id', hqId);
        url.searchParams.set('stockist_id', stockistId);
       
        window.location.href = url.toString();
    }

   
function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;

    // Check if there are data rows (prevents exporting empty state)
    const rows = table.querySelectorAll('tbody tr');
    if (!rows.length || table.querySelector('.state-screen')) {
        alert('No data available to export.');
        return;
    }

    const wb = XLSX.utils.table_to_book(table, { sheet: "Outstanding" });
    const dateStr = new Date().toISOString().split('T')[0];
    XLSX.writeFile(wb, `${filename}_${dateStr}.xlsx`);
}
</script>