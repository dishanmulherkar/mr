<?php 
$pageTitle = "Sales List";
include 'view/layout/header.php'; 
?>
<link rel="stylesheet" href="<?= BASE_URL ?>config/config/salesentry.css">
<style>
    .btn-download {
        background: #dc3545; color: white; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 12px;
    }
    .btn-download:hover { background: #c82333; color: white; }
</style>

<div class="page-content">
    <div class="page-header d-flex justify-content-between align-items-center mb-3">
        <h4>Sales List</h4>
        <!-- Button to go to the Sales Entry Page -->
        <a href="<?= BASE_URL ?>sales/entry" class="btn-submit" style="padding: 6px 12px; text-decoration: none;">
            <i class="fa fa-plus"></i> New Sale
        </a>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
        <select id="stockistFilter" class="filter-pill">
            <option value="">All Stockists</option>
            <?php foreach ($stockists as $s): ?>
                <option value="<?= $s['stockist_id'] ?>"><?= htmlspecialchars($s['stockist_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <select id="typeFilter" class="filter-pill">
            <option value="">All Types</option>
            <option value="Chemist">Chemist</option>
            <option value="Doctor">Doctor</option>
        </select>

        <input type="date" id="dateFilter" class="filter-pill">
        
        <button id="btnSearch" class="btn-ok">Search</button>
        <button id="btnReset" class="btn-ok" style="background:#6c757d;">Reset</button>
    </div>

    <!-- Sales Table -->
    <div class="inv-table-wrap">
        <table class="inv-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Stockist</th>
                    <th>Customer Name</th>
                    <th>Type</th>
                    <th>Total Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="salesTableBody">
                <tr><td colspan="7" style="text-align:center;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    const mr_id = <?= isset($_SESSION['mr_id']) ? $_SESSION['mr_id'] : 0 ?>;
    const BASE_URL = "<?= BASE_URL ?>";
</script>

<?php include 'view/layout/footer.php'; ?>
<script src="<?= BASE_URL ?>config/config/saleslist.js"></script>