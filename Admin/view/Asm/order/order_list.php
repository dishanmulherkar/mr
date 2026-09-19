<?php 
$pageTitle = "Order List";
include 'view/layout/header-asm.php';
?>
<style>
    .status-badge{
    color:#fff !important;
    font-size:12px;
    padding:4px 8px;
    border-radius:12px;
    font-weight:600;
    display:inline-block;
    min-width:48px;
    text-align:center;
}

.badge-pending{
    background:#ffc107;
    color:#212529 !important;
}

.badge-approved{
    background:#28a745;
    color:#fff !important;
}

.badge-dispatch,
.badge-processed{
    background:#17a2b8;
    color:#fff !important;
}

.badge-rejected{
    background:#dc3545;
    color:#fff !important;
}
 .inv-table-wrap {
    min-height: 250px; /* Adjust this value if your dropdown is taller */
}
@media (max-width: 480px) {
    .inv-table th {
        background: var(--bg);
        font-size: 9px;
        font-weight: 700;
        color: var(--txt-muted);
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 8px 9px;
        text-align: left;
        border-bottom: 1px solid var(--border);
    }

    .inv-table td {
        font-size: 10px;
        color: var(--txt-mid);
        padding: 9px 11px;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
    }

    .inv-table .dropdown-menu {
        min-width: 110px; /* Reduces Bootstrap's default wide menu */
        font-size: 11px; 
        padding: 4px 0;
    }
}
</style>
<link rel="stylesheet" href="<?= BASE_URL ?>config/config/salesentry.css">
<div class="page-content">

    <div class="page-header d-flex justify-content-between align-items-center mb-3">
        <h4></h4>
        
    </div>

    <div class="filter-bar">


    <select id="hq" class="filter-pill">
            <option value="">HeadQuaters</option>
            <?php foreach ($hq as $h): ?>
                <option value="<?= $h['headquarter_id'] ?>"><?= htmlspecialchars($h['hq_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="stockist" class="filter-pill">
            <option value="">All Stockists</option>
            <?php foreach ($stockists as $s): ?>
                <option value="<?= $s['stockist_id'] ?>"><?= htmlspecialchars($s['stockist_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="date" id="from_date" class="filter-pill">
        <!-- <input type="date" id="to_date" class="filter-pill"> -->

        <button class="btn-ok" id="btnSearch">Search</button>
        <button class="btn-ok" id="btnReset">Reset</button>

    </div>

    <div class="inv-table-wrap">
        <table class="inv-table">
            <thead>
            <tr>
                <th>SR No</th>
                <th>Date</th>
                <th>HQ Name</th>
                <th>Amount</th>
                <th>Status</th>
                <th width="25">Action</th>
            </tr>
            </thead>
            <tbody id="orderTable"></tbody>
        </table>
    </div>

    <div class="table-footer">
        Total Orders : <span id="orderCount">0</span>
    </div>

</div>
<script>

    // const BASE_URL = "<?= BASE_URL ?>";
</script>

<?php include 'view/layout/footer-asm.php'; ?>
<script>
const BASE_URL = "<?= BASE_URL ?>";

// 1. Handle HQ Change to get Stockists (jQuery)
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

// 2. Handle Order Table and Filters (Vanilla JS)
document.addEventListener('DOMContentLoaded', function () {
    const orderTableBody = document.getElementById('orderTable');
    
    if (orderTableBody) {
        const hqSel        = document.getElementById('hq');
        const stockistSel  = document.getElementById('stockist');
        const fromDate     = document.getElementById('from_date');
        const btnSearch    = document.getElementById('btnSearch');
        const btnReset     = document.getElementById('btnReset');
        const orderCount   = document.getElementById('orderCount');

        const statusClass = (s) => ({
            'Pending': 'badge-pending',
            'Approved': 'badge-approved',
            'Processed': 'badge-dispatch',
            'Dispatch': 'badge-dispatch',
            'Rejected': 'badge-rejected'
        }[s] || 'badge-pending');

        function actionIcons(o) {
            let menuItems = `
                <li>
                    <a class="dropdown-item" href="${BASE_URL}Order/details/${o.order_id}" title="View Order">
                        <i class="fa fa-eye text-primary me-2"></i> View
                    </a>
                </li>
            `;

            if (o.status === 'Approved' || o.status === 'Processed') {
                menuItems += `
                   <li>
                    <a class="dropdown-item" href="${BASE_URL}invoice/pdf/${o.order_id}" target="_blank" title="Download Invoice">
                        <i class="fa fa-download text-info me-2"></i> Invoice
                    </a>
                </li>
                `;
            }

            return `
                <div class="dropdown">
                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="actionBtn${o.order_id}" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa fa-cog"></i> <span class="d-none d-sm-inline ms-1">Actions</span>
                    </button>
                    <ul class="dropdown-menu shadow" aria-labelledby="actionBtn${o.order_id}">
                        ${menuItems}
                    </ul>
                </div>
            `;
        }

        function statusText(status) {
            switch (status) {
                case 'Pending': return '⏳ Pen';
                case 'Approved': return '✓ Appr';
                case 'Processed': 
                case 'Dispatch': return '🚚 Disp';
                case 'Rejected': return '✕ Rej';
                default: return status;
            }
        }

        function loadOrders() {
            const params = new URLSearchParams({
                hq_id: hqSel.value || '', 
                stockist_id: stockistSel.value || '',
                from_date: fromDate.value || ''
            });

            orderTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;">Loading…</td></tr>`;

            fetch(`${BASE_URL}Order/list_orders?${params.toString()}`)
                .then(res => res.json())
                .then(res => {
                    if (!res.success || !res.data.length) {
                        orderTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;">No orders found</td></tr>`;
                        orderCount.textContent = 0;
                        return;
                    }
                    
                    orderTableBody.innerHTML = res.data.map((o, index) => `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${o.order_date}</td>
                            <td>${o.hq_name}</td>
                            <td>₹${Math.round(Number(o.grand_total)).toLocaleString('en-IN')}</td>
                            <td><span class="badge ${statusClass(o.status)}">${statusText(o.status)}</span></td>
                            <td class="text-center">${actionIcons(o)}</td>
                        </tr>
                    `).join('');
                    
                    orderCount.textContent = res.data.length;
                })
                .catch(() => {
                    orderTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;">Failed to load orders</td></tr>`;
                });
        }

        // --- FIXED: ADDED EVENT LISTENERS BACK ---
        
        // Load orders when Search is clicked
        btnSearch.addEventListener('click', loadOrders);
        
        // Load orders when HQ changes
        hqSel.addEventListener('change', loadOrders);
        
        // (Optional) Load orders immediately when Stockist changes
        stockistSel.addEventListener('change', loadOrders);

        // Handle Reset Button
        btnReset.addEventListener('click', () => {
            hqSel.value = '';
            stockistSel.value = '';
            stockistSel.innerHTML = '<option value="">All Stockists</option>'; 
            fromDate.value = '';
            loadOrders();
        });

        // Load initially on page load
        loadOrders();
    }
});
</script>