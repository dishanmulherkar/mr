<?php
// ========== DATA EXTRACTION ==========
$invoiceNo = $invoice['invoice_no'] ?? ('SALE-' . ($invoice['sale_id'] ?? ''));
$invoiceDate = !empty($invoice['sale_date']) ? date('d/m/Y', strtotime($invoice['sale_date'])) : '';

$sellerName = $invoice['stockist_name'] ?? 'STOCKIST NAME';
$sellerAddress = $invoice['stockist_address'] ?? '';
$sellerGst = $invoice['gst_no'] ?? '';
$term_and_condition = $invoice['term_and_condition'] ?? '';

$customerName = $invoice['customer_name'] ?? '';
$customerAddress = $invoice['customer_address'] ?? ''; 
$customerPhone = $invoice['customer_phone'] ?? '';
$customerGst = $invoice['customer_gst'] ?? '';

$items = $invoice['items'] ?? [];

// Calculate totals
$dbTotalQty = (float)($invoice['total_qty'] ?? 0);
$dbGrandTotal = (float)($invoice['grand_total'] ?? 0);
$calcGrandTotal = 0;
$totalRenderedQty = 0;

foreach ($items as $item) {
    $qty = (float)($item['qty'] ?? 0);
    $rate = (float)($item['rate'] ?? 0);
    $discPerc = (float)($item['discount_percent'] ?? 0);
    
    $rowBase = $qty * $rate;
    $amt = $rowBase - ($rowBase * ($discPerc / 100));
    $taxAmount = $amt * ((float)($item['gst_percent'] ?? 0) / 100);
    $calcGrandTotal += ($item['net_total'] ?? ($amt + $taxAmount));
    $totalRenderedQty += $qty;
}

$netAmountRounded = round($dbGrandTotal > 0 ? $dbGrandTotal : $calcGrandTotal);
?>
<table border="1" style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px; width: 100%;">
    <!-- TOP HEADER -->
    <tr>
        <td colspan="11" align="center" style="font-size: 14px; font-weight: bold;">TAX INVOICE</td>
    </tr>
    <tr>
        <td colspan="11" align="center" style="font-size: 18px; font-weight: bold;">
            <?= htmlspecialchars($sellerName) ?>
        </td>
    </tr>
    <tr>
        <td colspan="11" align="center">
            <?= htmlspecialchars($sellerAddress) ?><br>
            <b>GSTIN:</b> <?= htmlspecialchars($sellerGst) ?>
        </td>
    </tr>
    
    <!-- CUSTOMER & INVOICE DETAILS -->
    <tr>
        <td colspan="6" valign="top">
            <b>To,</b><br>
            <b>M/S. <?= htmlspecialchars($customerName) ?></b><br>
            <?= htmlspecialchars($customerAddress) ?><br>
            <?php if ($customerPhone) echo "<b>Phone:</b> " . htmlspecialchars($customerPhone) . "<br>"; ?>
            <?php if ($customerGst) echo "<b>GST No:</b> " . htmlspecialchars($customerGst); ?>
        </td>
        <td colspan="5" valign="top">
            <b>Invoice No:</b> <?= htmlspecialchars($invoiceNo) ?><br>
            <b>Inv Date:</b> <?= htmlspecialchars($invoiceDate) ?><br>
            <b>Terms:</b> <?= htmlspecialchars($invoice['credit_days'] ?? '30') ?> Days
        </td>
    </tr>

    <!-- ITEMS TABLE HEADERS (11 Columns) -->
    <tr style="font-weight: bold; background-color: #f2f2f2;">
        <td align="center">Sr.</td>
        <td align="center">HSN Code</td>
        <td align="left" style="width:250px;">Description of Goods</td>
        <td align="center">Batch</td>
        <td align="center">Exp</td>
        <td align="right">MRP</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">Rate</td>
        <td align="center">Qty</td>
        <td align="right">Amount</td>
    </tr>

    <!-- ITEMS LOOP -->
    <?php 
    $sr = 1;
    foreach ($items as $item): 
        $qty = (float)($item['qty'] ?? 0);
        $rate = (float)($item['rate'] ?? 0);
        $mrp = (float)($item['mrp'] ?? 0);
        $discPerc = (float)($item['discount_percent'] ?? 0);
        $amt = ($qty * $rate) - (($qty * $rate) * ($discPerc / 100));
        $taxAmount = $amt * ((float)($item['gst_percent'] ?? 0) / 100);
        $net_total = $item['net_total'] ?? ($amt + $taxAmount);
    ?>
    <tr>
        <td align="center"><?= $sr++ ?></td>
        <td align="center"><?= htmlspecialchars($item['hsn_code'] ?? '') ?></td>
        <td align="left"><?= htmlspecialchars($item['product_name'] ?? '') ?></td>
        <td align="center"><?= htmlspecialchars($item['batch_no'] ?? '') ?></td>
        <td align="center"><?= !empty($item['expiry_date']) ? date('m/y', strtotime($item['expiry_date'])) : '' ?></td>
        <td align="right"><?= number_format($mrp, 2) ?></td>
        <td></td>
        <td></td>
        <td align="center"><?= number_format($rate, 2) ?></td>
        <td align="center"><?= $qty ?></td>
        <td align="right"><?= number_format($net_total, 2) ?></td>
    </tr>
    <?php endforeach; ?>

    <!-- TOTALS ROW -->
    <tr style="font-weight: bold;">
        <td colspan="9" align="right">Total</td>
        <td align="center"><?= $dbTotalQty > 0 ? $dbTotalQty : $totalRenderedQty ?></td>
        <td align="right"><?= number_format($netAmountRounded, 2) ?></td>
    </tr>

    <!-- FOOTER SECTION -->
    <tr>
        <td colspan="9" valign="top">
            <b>Terms & Conditions:</b><br>
            <?= $term_and_condition ?>
        </td>
        <td colspan="2" align="center" valign="bottom">
            <br><br><br>
            <b>For, <?= htmlspecialchars($sellerName) ?></b><br>
            Authorised Signatory
        </td>
    </tr>
</table>