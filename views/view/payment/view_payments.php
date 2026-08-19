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

    /* =========================================
       NEW CARD DESIGN FOR MOBILE RESPONSIVENESS
       ========================================= */
    .card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 5px;
        margin-top: 10px;
    }
    .payment-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid #eef0f2;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .payment-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.08);
    }
    .payment-card .card-header {
        background: #fdfdfd;
        padding: 9px 16px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .payment-card .card-header strong {
        color: #333;
        font-size: 15px;
    }
    .payment-card .card-body {
        padding: 10px;
        flex: 1;
    }
    .payment-card .card-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 10px;
        color: #444;
    }
    .payment-card .card-row:last-child {
        margin-bottom: 0;
    }
    .payment-card .card-row span:first-child {
        color: #777;
        font-weight: 500;
    }
    .payment-card .amount-highlight {
        margin-top: 5px;
        padding-top: 2px;
        border-top: 1px dashed #e0e0e0;
        font-size: 14px;
        align-items: center;
    }
    .payment-card .card-footer {
        padding: 3px 9px;
        background: #fbfbfb;
        border-top: 1px solid #f0f0f0;
        text-align: right;
    }
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 40px 20px;
        background: #fff;
        border-radius: 10px;
        border: 1px dashed #ccc;
        color: #888;
        font-size: 15px;
    }

    /* =========================================
       POPUP MODAL STYLES FOR PAYMENT PROOF
       ========================================= */
    .proof-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.6);
        backdrop-filter: blur(3px);
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .proof-modal-content {
        background: #fff;
        border-radius: 12px;
        max-width: 500px;
        width: 100%;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        animation: modalScale 0.25s ease-in-out;
    }
    @keyframes modalScale {
        from { transform: scale(0.9); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .proof-modal-header {
        padding: 14px 18px;
        background: #f8f9fa;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .proof-modal-header h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #333;
    }
    .proof-close-btn {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #666;
    }
  .proof-modal-body {
        padding: 20px;
        background: #f4f6f8;
        /* Using Flexbox to perfectly center everything inside */
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 200px; /* Prevents it from collapsing while the image loads */
    }
    
    .proof-modal-body img {
        max-width: 100%;
        max-height: 70vh;
        border-radius: 8px;
        object-fit: contain;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        /* Force block display and auto margins to guarantee horizontal centering */
        display: block; 
        margin: 0 auto; 
    }
    .proof-modal-body img {
        max-width: 100%;
        max-height: 70vh;
        border-radius: 8px;
        object-fit: contain;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
</style>

<link rel="stylesheet" href="<?= BASE_URL ?>config/config/salesentry.css">
<div class="page-content">

<!-- Place this inside your <div id="container"> or main content area -->

<?php if (isset($_SESSION['success_msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        <i class="fa fa-check-circle"></i> <?= $_SESSION['success_msg']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success_msg']); // Clear the message after displaying ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_msg'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
        <i class="fa fa-exclamation-circle"></i> <?= $_SESSION['error_msg']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error_msg']); // Clear the message after displaying ?>
<?php endif; ?>

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

    <!-- Replaced Table with Card Grid Container -->
    <div id="paymentCardContainer" class="card-grid"></div>

    <div class="table-footer" style="margin-top: 20px;">
        Total Records : <span id="paymentCount">0</span>
    </div>
</div>


<!-- =========================================
     POPUP MODAL HTML CONTAINER
     ========================================= -->
<div id="proofModal" class="proof-modal">
    <div class="proof-modal-content">
        <div class="proof-modal-header">
            <h5>Payment Proof Preview</h5>
            <button class="proof-close-btn" id="closeProofModal">&times;</button>
        </div>
        <div class="proof-modal-body">
            <img id="modalProofImage" src="" alt="Payment Proof">
        </div>
    </div>
</div>


<script>
    const mr_id = <?= isset($mr_id) ? $mr_id : (isset($_SESSION['mr_id']) ? $_SESSION['mr_id'] : 0) ?>; 
    const BASE_URL = "<?= BASE_URL ?>";
</script>

<?php include 'view/layout/footer.php'; ?>
<script src="<?= BASE_URL ?>config/config/paymentlist.js"></script>