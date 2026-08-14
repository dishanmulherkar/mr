<?php 
$pageTitle = "Sales List";
include 'view/layout/header.php'; 
?>
<link rel="stylesheet" href="<?= BASE_URL ?>config/config/salesentry.css">
<style>
   /* Card Container Layout */
    .sales-card-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px; /* Reduced gap between cards */
        margin-top: 10px;
    }
    
    @media (min-width: 768px) {
        .sales-card-container {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        }
    }

    /* Highly Compact Card Styles */
    .sale-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 8px 10px; /* Much smaller padding inside */
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        gap: 4px; /* Tiny gap between rows */
    }
    
    .card-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .customer-info {
        font-weight: 600;
        font-size: 14px;
        color: #222;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .badge-type {
        background: #e9ecef;
        color: #495057;
        padding: 2px 5px;
        border-radius: 3px;
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .text-amount {
        font-size: 14px;
        color: #198754;
        font-weight: bold;
    }

    .stockist-info {
        font-size: 12px;
        color: #666;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 60%;
    }

    .date-action-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sale-card-date {
        font-size: 11px;
        color: #777;
    }

    /* Tiny Download Icon Button */
    .btn-download-icon {
        background: #dc3545; 
        color: white; 
        padding: 4px 8px; 
        border-radius: 4px; 
        text-decoration: none; 
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .btn-download-icon:hover { background: #c82333; color: white; text-decoration: none; }
</style>

<div class="page-content">
    <div class="page-header d-flex justify-content-between align-items-center mb-3">
        <h4>Sales List</h4>
        <a href="<?= BASE_URL ?>SalesEntry" class="btn-submit" style="padding: 6px 12px; text-decoration: none;">
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

    <!-- Card Container (Replaces the Table) -->
    <div id="salesListContainer" class="sales-card-container">
        <div style="text-align:center; padding: 20px; width: 100%;">Loading...</div>
    </div>
</div>

<script>
    const mr_id = <?= isset($_SESSION['mr_id']) ? $_SESSION['mr_id'] : 0 ?>;
    const BASE_URL = "<?= BASE_URL ?>";
</script>

<?php include 'view/layout/footer.php'; ?>
<script src="<?= BASE_URL ?>config/config/saleslist.js"></script>