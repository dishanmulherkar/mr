<?php 

$pageTitle = "Order Entry";
include 'view/layout/header.php'; 
?>
<link rel="stylesheet" href="<?= BASE_URL ?>config/config/salesentry.css">
<div class="page-content">

        <!-- ── Customer / Stockist row ──────────────────── -->
        <div class="filter-bar entry-row">
            <label for="">Stockist</label>
          <select id="stockist-select" class="filter-pill">
            <option value="">— Stockist —</option>
            <?php foreach ($stockists as $s): ?>
              <option value="<?= $s['stockist_id'] ?>"><?= htmlspecialchars($s['stockist_name']) ?></option>
            <?php endforeach; ?>
          </select>
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
                <th>Amt</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="cart-tbody">
              <tr id="empty-row">
                <td colspan="6" style="text-align:center;color:var(--txt-muted);padding:22px 0;">
                  No items added yet
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ── Total ─────────────────────────────────────── -->
        <div class="footer-total">
                <!-- ── Total ─────────────────────────────────────── -->
        <div class="cart-summary">
    <div>Subtotal: <span id="sub-total">₹ 0.00</span></div>
    <div>Total GST: <span id="gst-amount">₹ 0.00</span></div>
    <div><strong>Grand Total: <span id="total-amount">₹ 0.00</span></strong></div>
</div>
        </div>

        <!-- ── Submit ─────────────────────────────────────── -->
        <div class="submit-container">
          <button id="btn-submit" class="btn-submit" disabled>Submit</button>
        </div>
                
<div id="toast"></div>
      </div><!-- /.page-content -->
<script>
    const mr_id = <?= $mr_id ?>;
</script>

           <?php 
// 3. Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>

<script src="<?= BASE_URL ?>config/config/orderentry.js"></script>