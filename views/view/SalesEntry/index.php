<?php 

$pageTitle = "Sales Entry";
include 'view/layout/header.php'; 
?>
<link rel="stylesheet" href="<?= BASE_URL ?>config/config/salesentry.css">
<div class="page-content">

        <!-- ── Customer / Stockist row ──────────────────── -->
        <div class="filter-bar entry-row">
          <select id="customer-select" class="form-control" >
                <option value="">Select Customer</option>
            </select>

          <select id="stockist-select" class="filter-pill">
            <option value="">— Stockist —</option>
            <?php foreach ($stockists as $s): ?>
              <option value="<?= $s['stockist_id'] ?>"><?= htmlspecialchars($s['stockist_name']) ?></option>
            <?php endforeach; ?>
          </select>

          <div class="date-range-wrap">
            <label style="font-size: 13px; color: var(--txt-muted);">Date:</label>
            <input type="date" id="date-from" name="sale_date"
                style="padding: 8px 3px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px;">
        </div>

          <div class="checkbox-group">
            <label><input type="radio" name="sale_type" value="chemist" checked> Chem</label>
            <label><input type="radio" name="sale_type" value="doctor"> Doc</label>
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
                <th></th>
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
          Total Amount : <span id="total-amount">₹ 0.00</span>
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

<script src="<?= BASE_URL ?>config/config/salesentry.js"></script>