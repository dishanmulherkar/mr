<?php 
$pageTitle = "Payments list";
include 'view/layout/header.php';
?>
<style>
    .status-badge {
        color: #fff !important;
        font-size: 12px;
        padding: 4px 8px;
        border-radius: 12px;
        font-weight: 600;
        display: inline-block;
        min-width: 48px;
        text-align: center;
    }
    .badge-pending { background: #ffc107; color: #212529 !important; }
    .badge-approved { background: #28a745; color: #fff !important; }
    .badge-rejected { background: #dc3545; color: #fff !important; }
    
    /* Style for the Header Dropdown */
    .title-dropdown {
        font-size: 1.25rem;
        font-weight: 600;
        border: none;
        background: transparent;
        color: #333;
        cursor: pointer;
        outline: none;
        padding-right: 20px;
    }
    .title-dropdown:focus { box-shadow: none; }
</style>
<link rel="stylesheet" href="<?= BASE_URL ?>config/config/salesentry.css">
<div class="page-content">

    <div class="page-header d-flex justify-content-between align-items-center mb-3">
        <!-- View Toggle Dropdown -->
        <select id="viewToggle" class="title-dropdown form-select-lg">
            <option value="bills">Bill List</option>
            <option value="payments"> Payment List</option>
        </select>

        <a href="<?= BASE_URL ?>payment/entry" style="padding: 6px 9px;" class="btn-submit">
            <i class="fa fa-plus"></i> Make Payment
        </a>
    </div>

    <div class="filter-bar">
        <select id="stockist" class="filter-pill">
            <option value="">All Stockists</option>
            <?php if(!empty($stockists)): ?>
                <?php foreach ($stockists as $s): ?>
                    <option value="<?= $s['stockist_id'] ?>"><?= htmlspecialchars($s['stockist_name']) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>

        <!-- Status Filter -->
        <select id="statusFilter" class="filter-pill">
            <!-- Options will be populated by JS based on the view -->
        </select>

        <input type="date" id="from_date" class="filter-pill">

        <button class="btn-ok" id="btnSearch">Search</button>
        <button class="btn-ok" id="btnReset">Reset</button>
    </div>

    <div class="inv-table-wrap">
        <table class="inv-table">
            <!-- Headers for Bill List -->
            <thead id="billHeaders">
            <tr>
                <th>Date</th>
                <th>Bill No</th>
                <th>Stockist Name</th>
                <th>Bill Amount</th>
                <th>Pending Balance</th>
                <th>Bill Status</th>
            </tr>
            </thead>
            
            <!-- Headers for Payment List -->
            <thead id="paymentHeaders" style="display: none;">
            <tr>
                <th>Pay Date</th>
                <th>Reference No</th>
                <th>Stockist Name</th>
                <th>Amount Paid</th>
                <th>Pay Mode</th>
                <th>Approval Status</th>
                <th width="100">Action</th>
            </tr>
            </thead>

            <tbody id="paymentTable"></tbody>
        </table>
    </div>

    <div class="table-footer">
        Total Records : <span id="paymentCount">0</span>
    </div>
</div>

<script>
    const mr_id = <?= isset($mr_id) ? $mr_id : (isset($_SESSION['mr_id']) ? $_SESSION['mr_id'] : 0) ?>; 
    const BASE_URL = "<?= BASE_URL ?>";
</script>

<?php include 'view/layout/footer.php'; ?>
<script src="<?= BASE_URL ?>config/config/paymentlist.js"></script>