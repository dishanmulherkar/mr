<?php 
$pageTitle = "Order List";
include 'view/layout/header.php';
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
</style>
<link rel="stylesheet" href="<?= BASE_URL ?>config/config/salesentry.css">
<div class="page-content">

    <div class="page-header d-flex justify-content-between align-items-center mb-3">
        <h4></h4>
        <a href="<?= BASE_URL ?>OrderEntry" style= "padding: 6px 9px;" class="btn-submit">
            <i class="fa fa-plus"></i> Add Order
        </a>
    </div>

    <div class="filter-bar">

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
                <th>Order No</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Status</th>
                <th width="50">Action</th>
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
    const mr_id   = <?= $mr_id ?>;
    const BASE_URL = "<?= BASE_URL ?>";
</script>

<?php include 'view/layout/footer.php'; ?>
<script src="<?= BASE_URL ?>config/config/orderlist.js"></script>