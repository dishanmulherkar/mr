<?php 
$isEdit = isset($order_data) && $order_data;
$pageTitle = $isEdit ? "Edit Order (ORD" . str_pad($order_data['order_id'], 4, '0', STR_PAD_LEFT) . ")" : "Order Entry";

include 'view/layout/header.php'; 
?>
<link rel="stylesheet" href="<?= BASE_URL ?>config/config/salesentry.css">

<style>
    /* Mobile-optimized single limit strip */
    .mr-limit-strip {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 14px;
        margin: 10px 0;
        font-size: 14px;
    }
    .mr-limit-strip .limit-val {
        font-size: 16px;
        font-weight: 700;
        color: #16a34a; /* Default safe green */
    }
    .mr-limit-strip .limit-val.exceeded {
        color: #dc2626 !important; /* Exceeded red */
    }
</style>

<div class="page-content">

    <!-- Hidden fields for Edit Mode -->
    <input type="hidden" id="order_id" value="<?= $isEdit ? $order_data['order_id'] : 0 ?>">
    <input type="hidden" id="order_date" value="<?= $isEdit ? $order_data['order_date'] : date('Y-m-d') ?>">

    <!-- ── Customer / Stockist row ──────────────────── -->
    <div class="filter-bar entry-row">
        <label for="stockist-select">Stockist</label>
        <select id="stockist-select" class="filter-pill" <?= $isEdit ? 'disabled' : '' ?>>
            <option value="">— Stockist —</option>
            <?php 
            $selected_stockist = $isEdit ? $order_data['stockist_id'] : '';
            foreach ($stockists as $s): 
                $selected = ($s['stockist_id'] == $selected_stockist) ? 'selected' : '';
            ?>
                <option value="<?= $s['stockist_id'] ?>" <?= $selected ?>><?= htmlspecialchars($s['stockist_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if($isEdit): ?>
            <input type="hidden" id="hidden-stockist" value="<?= $order_data['stockist_id'] ?>">
        <?php endif; ?>
    </div>

    <!-- ── Mobile Single Available Limit Display ────────── -->
    <div class="mr-limit-strip">
        <span class="text-muted fw-bold">Avail Limit</span>
        <span id="mr-live-limit" class="limit-val">₹ <?= number_format($base_avail_limit ?? 0, 2); ?></span>
    </div>

    <!-- ── Medicine search row ──────────────────────── -->
    <div class="entry-row">
        <div class="col-lg-8">
            <div class="ac-wrap">
                <input type="text" id="medicine-search" class="ac-input"
                    placeholder="Select a stockist first…" autocomplete="off" <?= !$isEdit ? 'disabled' : '' ?>>
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
                    <th>Amt</th>
                    <th></th>
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
    <div class="footer-total">
        <div class="cart-summary">
            <div>Subtotal: <span id="sub-total">₹ 0.00</span></div>
            <div>Total GST: <span id="gst-amount">₹ 0.00</span></div>
            <div><strong>Grand Total: <span id="total-amount">₹ 0.00</span></strong></div>
        </div>
    </div>

   <!-- ── Submit ─────────────────────────────────────── -->
    <div class="submit-container">
        <?php if ($isEdit): ?>
            <button type="button" id="btn-cancel" class="btn-cancel" onclick="window.history.back()" style="background: #e2e8f0; color: #475569; margin-right: 10px;">
                Cancel
            </button>
        <?php endif; ?>

        <button id="btn-submit" class="btn-submit" <?= !$isEdit ? 'disabled' : '' ?>>
            <?= $isEdit ? 'Update Order' : 'Submit' ?>
        </button>
    </div>
                
    <div id="toast"></div>
</div><!-- /.page-content -->

<script>
    const mr_id = <?= (int)$mr_id ?>;
    const baseAvailLimit = <?= (float)($base_avail_limit ?? 0) ?>;
    const existingOrderData = <?= $isEdit ? json_encode($order_data) : 'null' ?>;
</script>

<?php include 'view/layout/footer.php'; ?>
<script src="<?= BASE_URL ?>config/config/orderentry.js"></script>