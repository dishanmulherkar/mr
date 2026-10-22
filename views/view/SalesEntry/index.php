<?php 
$isEdit = !empty($sale);
$pageTitle = $isEdit ? "Edit Sales Entry" : "Sales Entry";
include 'view/layout/header.php'; 

$saleDate = $isEdit ? date('Y-m-d', strtotime($sale['sale_date'])) : date('Y-m-d');
$selectedStockistId = $isEdit ? ($sale['stockist_id'] ?? '') : '';
$selectedCustomerId = $isEdit ? ($sale['c_id'] ?? '') : '';
$saleType = $isEdit ? strtolower($sale['customer_type'] ?? 'Chemist') : 'Chemist';
?>
<style>
    .footer-total-container {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 14px;
    margin-top: 12px;
}

.footer-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px dashed #e2e8f0;
}

.footer-total-row:last-child {
    border-bottom: none;
}

/* 50% split for both sides */
.total-col {
    flex: 0 0 50%;
    max-width: 50%;
    display: flex;
    align-items: center;
    gap: 6px;
    box-sizing: border-box;
}

.total-col.text-end {
    justify-content: flex-end;
}

.label {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}

.val {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
}

/* Discount input styling */
.discount-input {
    width: 70px;
    padding: 3px 6px;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    text-align: right;
    outline: none;
}

.discount-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 1px #3b82f6;
}

.font-bold {
    font-weight: 700;
}

.text-success {
    color: #16a34a;
}
</style>
<link rel="stylesheet" href="<?= BASE_URL ?>config/config/salesentry.css">

<div class="page-content">

    <!-- Hidden ID to detect Edit vs Insert -->
    <input type="hidden" id="sale_id" value="<?= $isEdit ? (int)$sale['s_id'] : 0 ?>">

    <!-- ── Customer / Stockist row ──────────────────── -->
    <div class="filter-bar entry-row">
        <select id="customer-select" class="form-control">
            <option value="">Select Customer</option>
            <?php if ($isEdit && !empty($sale['c_id'])): ?>
                <option value="<?= $sale['c_id'] ?>" selected><?= htmlspecialchars($sale['customer_name'] ?? 'Selected Customer') ?></option>
            <?php endif; ?>
        </select>

        <select id="stockist-select" class="filter-pill">
            <option value="">— Stockist —</option>
            <?php foreach ($stockists as $s): ?>
                <option value="<?= $s['stockist_id'] ?>" <?= ($selectedStockistId == $s['stockist_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['stockist_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="date-range-wrap">
            <label style="font-size: 13px; color: var(--txt-muted);">Date:</label>
            <input type="date" id="date-from" name="sale_date" value="<?= $saleDate ?>"
                   style="padding: 8px 3px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px;">
        </div>

        <div class="checkbox-group">
            <label><input type="radio" name="sale_type" value="chemist" <?= ($saleType === 'chemist') ? 'checked' : '' ?>> Chem</label>
            <label><input type="radio" name="sale_type" value="doctor" <?= ($saleType === 'doctor') ? 'checked' : '' ?>> Doc</label>
        </div>
    </div>

    <!-- ── Medicine search row ──────────────────────── -->
    <div class="entry-row">
        <div class="col-lg-8">
            <div class="ac-wrap">
                <input type="text" id="medicine-search" class="ac-input"
                       placeholder="Select a stockist first…" autocomplete="off" disabled>
                <div id="medicine-dropdown" class="ac-dropdown"></div>
                <div id="ac-error" class="ac-error"></div>
            </div>
        </div>
        <div class="col-lg-2">
            <span class="qty-lbl">Qty</span>
            <input type="number" id="qty-input" class="qty-inp" value="1" min="1">
        </div>
        <div class="col-lg-2">
            <button id="btn-ok" class="btn-ok">OK</button>
        </div>
    </div>

    <!-- ── Cart table ────────────────────────────────── -->
    <div class="inv-table-wrap">
        <table class="inv-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Medicine</th>
                    <th>Qty</th>
                    <!-- <th>Rate</th> -->
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="cart-tbody">
                <tr id="empty-row">
                    <td colspan="5" style="text-align:center;color:var(--txt-muted);padding:22px 0;">
                        No items added yet
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Total ─────────────────────────────────────── -->
    <!-- ── Totals & Discount ─────────────────────────────── -->
    <div class="footer-total-container">
        <!-- Row 1: PTS Total (50%) & MRP Total (50%) -->
        <div class="footer-total-row">
            <div class="total-col">
                <span class="label">Total PTS:</span>
                <span class="val" id="total-amount">₹ 0.00</span>
            </div>
            <div class="total-col text-end">
                <span class="label">Total MRP:</span>
                <span class="val" id="total-amount-pts">₹ 0.00</span>
            </div>
        </div>

        <!-- Row 2: Discount % Input (50%) & Discount Amount (50%) -->
        <div class="footer-total-row">
            <div class="total-col discount-input-wrap">
                <label for="discount-percent" class="label">Discount (%):</label>
                <input 
                    type="number" 
                    id="discount-percent" 
                    class="discount-input" 
                    placeholder="0" 
                    min="0" 
                    max="100" 
                    step="0.01" 
                    value=""
                    oninput="renderCart()"
                />
            </div>
            <div class="total-col text-end">
                <span class="label">Discount Amt:</span>
                <span class="val" id="discount-amount">- ₹ 0.00</span>
            </div>
        </div>

        <!-- Optional Final Net Row -->
        <div class="footer-total-row net-row">
            <div class="total-col">
                <span class="label font-bold">Net Payable:</span>
            </div>
            <div class="total-col text-end">
                <span class="val font-bold text-success" id="net-amount">₹ 0.00</span>
            </div>
        </div>
    </div>

    <!-- ── Submit / Update ───────────────────────────── -->
    <div class="submit-container">
        <button id="btn-submit" class="btn-submit" disabled><?= $isEdit ? 'Update Sale' : 'Submit' ?></button>
    </div>
            
    <div id="toast"></div>
</div><!-- /.page-content -->


<script>
    const mr_id = <?= (int)$mr_id ?>;
    const BASE_URL = "<?= BASE_URL ?>";
    const editSaleData = {
        is_edit: <?= $isEdit ? 'true' : 'false' ?>,
        sale_id: <?= $isEdit ? (int)$sale['s_id'] : 0 ?>,
        customer_id: <?= $isEdit ? (int)($sale['c_id'] ?? 0) : 'null' ?>,
        stockist_id: <?= $isEdit ? (int)($sale['stockist_id'] ?? 0) : 'null' ?>,
        sale_date: <?= $isEdit ? json_encode($saleDate) : 'null' ?>,
        sale_type: <?= $isEdit ? json_encode($saleType) : 'null' ?>,
        items: <?= ($isEdit && !empty($sale['items'])) ? json_encode($sale['items']) : '[]' ?>,
        disc: <?= $isEdit ? (int)($sale['discount_percent'] ?? 0) : 'null' ?>
    };
</script>

<?php include 'view/layout/footer.php'; ?>
<script src="<?= BASE_URL ?>config/config/salesentry.js"></script>