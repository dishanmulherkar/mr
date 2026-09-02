<?php

// ========== DATA EXTRACTION ==========
$invoiceNo = $invoice['inward_no'] ?? ('T-' . ($invoice['inward_no'] ?? ''));
$invoiceDate = !empty($invoice['inward_date']) ? date('d/m/Y', strtotime($invoice['inward_date'])) : '';
$orderNo = $invoice['order_no'] ?? '';
$orderDate = !empty($invoice['order_date']) ? date('d/m/Y', strtotime($invoice['order_date'])) : '';
$dueDate = !empty($invoice['inward_date']) ? date('d/m/Y', strtotime($invoice['inward_date'] . ' + ' . ($invoice['credit_days'] ?? 30) . ' days')) : '';

// Stockist Details
$stockistName = $invoice['stockist_name'] ?? '';
$stockistAddress = $invoice['stockist_address'] ?? '';
$stockistGst = $invoice['gst_no'] ?? '';
$stockistGsttype = $invoice['gst_type'] ?? '';
$stockistPan = $invoice['pan_no'] ?? '';
$stockistDl = $invoice['dl_no'] ?? '';
$stockistPhone = $invoice['stockist_phone'] ?? '';
$dispatchTo = $invoice['dispatch_to'] ?? '';
$transport = $invoice['transport_name'] ?? ($invoice['transport'] ?? '');
$stateName = $invoice['state_name'] ?? '';
$districtName = $invoice['district_name'] ?? '';
$pincode = $invoice['pincode'] ?? '';
$lrNo = $invoice['lr_no'] ?? '';

// Company Details
$companyName = $invoice['company_name'] ?? 'RUDRADEO INCORPORATES';
$companyGst = $invoice['company_gst'] ?? '';
$term_and_condition = $invoice['term_and_condition'] ?? '';

$companyAddress = trim(implode(', ', array_filter([
    $invoice['company_address'] ?? null,
    $invoice['company_district'] ?? null,
    $invoice['company_state'] ?? null,
    $invoice['company_pincode'] ?? null
])));
IF ($stateName == $invoice['company_state']) {
    $gstType =  'CGST_SGST';
}elseif($stateName == 'NEPAL'){
    $gstType =  'VAT';
}else{
    $gstType =  'IGST';
}
$items = $invoice['items'] ?? [];
// $gstType = strtoupper($invoice['gst_type'] ?? 'CGST_SGST');  Expected: CGST_SGST, IGST, or VAT

// Totals
$dbTotalQty = (float)($invoice['total_qty'] ?? 0);
$dbSubTotal = (float)($invoice['sub_total'] ?? 0);
$dbSgst = (float)($invoice['sgst_amount'] ?? 0);
$dbCgst = (float)($invoice['cgst_amount'] ?? 0);
$Igst = (float)($invoice['igst_amount'] ?? 0);
$dbVat = (float)($invoice['vat_amount'] ?? ($invoice['gst_amount'] ?? 0));
$dbgst = ($dbSgst + $dbCgst + $Igst);
$dbIgst = (float)($dbgst);

$dbOther = (float)($invoice['other_charges'] ?? 0);
$dbGrandTotal = (float)($invoice['grand_total'] ?? 0);

// Additional Discount & CD variables
$additionalDiscount = (float)($invoice['discount'] ?? 0);
$cdPercent = (float)($invoice['cd_percent'] ?? 0);
$cdAmount = (float)($invoice['cd_amount'] ?? 0);
if ($cdAmount == 0 && $cdPercent > 0) {
    $cdAmount = $dbSubTotal * ($cdPercent / 100);
}

// ========== CORRECTED: Calculate totals with proper discount separation ==========
$trueGrossAmount = 0;
$trueItemDiscount = 0;      // Line-item discounts only
$trueCdDiscount = 0;        // Cash discounts only

foreach ($items as $item) {
    $qty = (float)($item['qty'] ?? 0);
    $rate = (float)($item['rate'] ?? 0);
    $amt = (float)($item['amt'] ?? ($qty * $rate));
    $rowBase = $qty * $rate;
    
    // CORRECTED: Calculate line-item discount
    $discPerc = (float)($item['discount_percent'] ?? 0);
    $rowDiscount = $rowBase * ($discPerc / 100);
    $trueItemDiscount += $rowDiscount;
    $trueGrossAmount += $rowBase;
    
    // CORRECTED: Calculate CD discount (applied after line discount)
    $afterFirstDisc = $rowBase - $rowDiscount;
    $itemCdPerc = (float)($item['cd_percent'] ?? ($cdPercent ?? 0));
    $rowCdDiscount = $afterFirstDisc * ($itemCdPerc / 100);
    $trueCdDiscount += $rowCdDiscount;
}

// CORRECTED: Total of all discounts
$totalLineItemDiscount = $trueItemDiscount;
$totalCdDiscount = $trueCdDiscount;
$totalAllDiscounts = $trueItemDiscount + $trueCdDiscount + $additionalDiscount;

$netAmountRounded = round($dbGrandTotal);
$roundOff = ($invoice['round_off'] ?? 0);

$taxPercentVal = !empty($items) && isset($items[0]['gst_percent']) ? $items[0]['gst_percent'] : '0';

// Message
$message = $invoice['remarks'] ?? 'Message :';
if (strpos($message, ':') === false) {
    $message = 'Message : ' . $message;
}

function convertNumberToWords($number) {
    $no = floor($number);
    $point = round($number - $no, 2) * 100;
    $hundred = null;
    $digits_1 = strlen($no);
    $i = 0;
    $str = [];
    $words = [
        '0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four',
        '5' => 'Five', '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',
        '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen',
        '14' => 'Fourteen', '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen',
        '18' => 'Eighteen', '19' => 'Nineteen', '20' => 'Twenty', '30' => 'Thirty',
        '40' => 'Forty', '50' => 'Fifty', '60' => 'Sixty', '70' => 'Seventy',
        '80' => 'Eighty', '90' => 'Ninety'
    ];
    $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];
    while ($i < $digits_1) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number] . " " . $digits[$counter] . $plural . " " . $hundred
                : $words[floor($number / 10) * 10] . " " . $words[$number % 10] . " " . $digits[$counter] . $plural . " " . $hundred;
        } else $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    $points = ($point) ? " and " . ($words[$point / 10] . " " . $words[$point % 10]) . " Paise" : '';
    return ($result ? '' . $result . 'Rupees Only' : '');
}

// Generate words dynamically using your rounded net amount variable
$amountInWords = convertNumberToWords($netAmountRounded);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Invoice <?= htmlspecialchars($invoiceNo) ?></title>
<style>
  .invoice-page {
        margin: 12mm 12mm 12mm 12mm;
    }
* { margin: 0; padding: 0; }
body {
    font-family: Arial, sans-serif;
    font-size: 9pt;
    line-height: 1.2;
    color: #000;
}
table { border-collapse: collapse; width: 100%; }
.section-space { height: 3px; }
</style>
</head>
<body>
<div class="invoice-page">
<table style="
    width: 100%;
    border: 1px solid #000;
    border-collapse: collapse;
    margin-bottom: 3px;
    font-weight: bold;
    font-size: 8pt;
">
    <tr>
        <td style="width: 33.33%; padding: 2px; text-align: left;">CREDIT </td>
        <td style="width: 33.33%; padding: 2px; text-align: center;">TAX INVOICE</td>
        <td style="width: 33.33%; padding: 2px; text-align: right;">Original / Duplicate / Triplicate</td>
    </tr>
</table>

    <!-- Company Header -->
    <div style="text-align: center; margin-bottom: 3px; border: 1px solid #000; ">
        <div style="font-weight: bold; font-size: 13pt; margin-bottom: 2px;">
            <?= htmlspecialchars($companyName) ?>
        </div>
        <div style="font-size: 8pt; margin-bottom: 2px;">
            <?= htmlspecialchars($companyAddress) ?>
        </div>
    </div>

    <!-- Main Info Table -->
    <table style="border: 1px solid #000; margin-bottom: 2px;">
        <tr>
            <!-- Left: To Section -->
            <td style="width: 55%; border-right: 1px solid #000; padding: 3px 0; vertical-align: top;">
                <div style="font-weight: bold; text-decoration: underline; font-size: 8pt; margin-bottom: 2px;">To,</div>
                <div style="font-size: 8pt;  padding: 0px 3px; line-height: 1.3;">
                    <strong>M/S.  <?= htmlspecialchars($stockistName) ?></strong><br>
                    <?php 
                    $addressLines = array_filter([
                        $stockistAddress ? 'AT:- ' . $stockistAddress : null,
                        $districtName ? 'DIST.- ' . $districtName : null,
                        $pincode ? $pincode . ', ' . $stateName : $stateName
                    ]);
                    foreach ($addressLines as $line) {
                        echo htmlspecialchars($line) . '<br>';
                    }
                    if ($stockistPhone) echo 'Phone No: ' . htmlspecialchars($stockistPhone) . '<br>';  ?>
                </div>
                    <table style="width: 100%; border-collapse: collapse; ">
                        <tr>
                            <td style="border: none;  border-top: 1px solid #000; padding: 1px 3px; width: 50%; font-size: 8pt;">
                                <?php if ($stockistGst) echo 'GST No: ' . htmlspecialchars($stockistGst) . '<br>'; ?>
                            </td>
                            <td style="border: none;  border-top: 1px solid #000; padding: 1px 3px; width: 50%; font-size: 8pt;">
                                <?php if ($stockistPan) echo 'PAN No: ' . htmlspecialchars(strtoupper($stockistPan)) . '<br>'; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 1px 3px; width: 50%; font-size: 8pt;">
                                <?php if ($stockistDl) echo 'DL No: ' . htmlspecialchars($stockistDl) . '<br>'; ?>
                            </td>
                            <td style="border: none; padding: 1px 3px; width: 50%; font-size: 8pt;"></td>
                        </tr>
                    </table>
            </td>
            
            <!-- Right: Invoice Details -->
            <td style="width: 45%; padding: 3px 0; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse; ">
                        <tr>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">Invoice No</td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-weight: bold; font-size: 8pt;">: <?= htmlspecialchars($invoiceNo) ?></td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">Inv Date</td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">: <?= htmlspecialchars($invoiceDate) ?></td>
                        </tr>
                        <tr>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">Order No</td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">: <?= htmlspecialchars($orderNo) ?></td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">Ord. Date</td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">: <?= htmlspecialchars($orderDate) ?></td>
                        </tr>
                        <tr>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">Transport</td>
                            <td colspan="3" style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 75%; font-size: 8pt;">: <?= htmlspecialchars($transport) ?></td>
                        </tr>
                        <tr>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">L.R. No</td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">: <?= htmlspecialchars($lrNo) ?></td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">LR Date</td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">: <?= htmlspecialchars($lrDate ?? '') ?></td>
                        </tr>
                        <tr>
                            <td style="border: none;  border-bottom: 1px solid #000; padding: 1px 3px; width: 30%; font-size: 8pt;">Despatch to</td>
                            <td colspan="3" style="border: none;  border-bottom: 1px solid #000; padding: 1px 3px; width: 70%; font-size: 8pt;">: <?= htmlspecialchars($dispatchTo) ?></td>
                        </tr>
                        <tr>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">Terms</td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">: <?= htmlspecialchars($invoice['credit_days'] ?? '30') ?> Days</td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">Due Date</td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">: <?= htmlspecialchars($dueDate) ?></td>
                        </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table style="border: 1px solid #000; margin-bottom: 2px;">
        <thead>
            <tr>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 2.5%;">Sr.</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 5%;">HSN Code</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: left; font-size: 7pt; font-weight: bold; width: 18%;">Description of Goods</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 6%;">Batch</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 4%;">Exp</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 7pt; font-weight: bold; width: 5%;">MRP</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 5%;">Rate</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 4%;">Qty/Fr</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 4%;">Disc<br>(%)</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 7pt; font-weight: bold; width: 6%;">Taxable<br>Value</th>
                
                <?php if ($gstType === 'IGST'): ?>
                    <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 4%;">IGST<br>Rate</th>
                    <th style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 7pt; font-weight: bold; width: 5%;">IGST<br>Value</th>
                <?php elseif ($gstType === 'VAT'): ?>
                    <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 4%;">VAT<br>Rate</th>
                    <th style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 7pt; font-weight: bold; width: 5%;">VAT<br>Value</th>
                <?php else: ?>
                    <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 4%;">SGST<br>Rate</th>
                    <th style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 7pt; font-weight: bold; width: 5%;">SGST<br>Value</th>
                    <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 4%;">CGST<br>Rate</th>
                    <th style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 7pt; font-weight: bold; width: 5%;">CGST<br>Value</th>
                <?php endif; ?>

                <th style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 7pt; font-weight: bold; width: 6%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sr = 1;
            $totalRenderedQty = 0;
            $calculatedGrandTotal = 0;
           foreach ($items as $item): 
                $qty = (float)($item['qty'] ?? 0);
                $totalRenderedQty += $qty;
                
                $rate = (float)($item['rate'] ?? 0);
                $mrp = (float)($item['mrp'] ?? 0);
                $discPerc = (float)($item['discount_percent'] ?? 0);
                
                // Fetch line CD percent (fallback to invoice global cd_percent if item-specific isn't set)
                $itemCdPerc = (float)($item['cd_percent'] ?? ($cdPercent ?? 0));
                
                // 1. Calculate Base Gross Amount for the row
                $rowBase = $qty * $rate;
                
                
                // 2. Apply First Discount (Disc %)
                $firstDiscAmount = $rowBase * ($discPerc / 100);
                $afterFirstDisc = $rowBase - $firstDiscAmount;
                
                // 3. Apply Second Discount (CD %) on the remaining balance
                $cdDiscountAmount = $afterFirstDisc * ($itemCdPerc / 100);
                
                // 4. Final Taxable Amount ($amt) after both discounts
                $amt = $afterFirstDisc - $cdDiscountAmount;
                
                // Net total including tax
                $net_total = (float)($item['net_total'] ?? ($amt * (1 + ((float)($item['gst_percent'] ?? 0) / 100))));
                $calculatedGrandTotal += $net_total; // Accumulate Grand Total
                $taxRate = (float)($item['gst_percent'] ?? 0);
                $taxAmount = (float)($item['gst_amount'] ?? ($amt * ($taxRate / 100)));
                
                $sgstVal = 0; $cgstVal = 0; $igstVal = 0; $vatVal = 0;
                $sgstRate = 0; $cgstRate = 0; $igstRate = 0; $vatRate = 0;

                if ($gstType === 'IGST') {
                    $igstRate = $taxRate;
                    $igstVal = $taxAmount;
                } elseif ($gstType === 'VAT') {
                    $vatRate = $taxRate;
                    $vatVal = $taxAmount;
                } else {
                    $sgstRate = $taxRate / 2;
                    $cgstRate = $taxRate / 2;
                    $sgstVal = $taxAmount / 2;
                    $cgstVal = $taxAmount / 2;
                }
            ?>
            <tr>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;"><?= $sr++ ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;"><?= htmlspecialchars($item['hsn_code'] ?? '') ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: left; font-size: 8pt;"><?= htmlspecialchars($item['product_name'] ?? '') ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;"><?= htmlspecialchars($item['batch_no'] ?? '') ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;"><?= !empty($item['expiry_date']) ? date('m/y', strtotime($item['expiry_date'])) : '' ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt;"><?= number_format($mrp, 2) ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;"><?= number_format($rate, 2) ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;"><?= $qty ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;"><?= number_format($discPerc, 2) . (!empty($cdPercent) ? "+" . number_format($cdPercent, 2) : "") ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt;"><?= number_format($amt, 2) ?></td>

                <?php if ($gstType === 'IGST'): ?>
                    <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;"><?= number_format($igstRate, 2) ?></td>
                    <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt;"><?= number_format($igstVal, 2) ?></td>
                <?php elseif ($gstType === 'VAT'): ?>
                    <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;"><?= number_format($vatRate, 2) ?></td>
                    <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt;"><?= number_format($vatVal, 2) ?></td>
                <?php else: ?>
                    <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;"><?= number_format($sgstRate, 2) ?></td>
                    <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt;"><?= number_format($sgstVal, 2) ?></td>
                    <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;"><?= number_format($cgstRate, 2) ?></td>
                    <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt;"><?= number_format($cgstVal, 2) ?></td>
                <?php endif; ?>

                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt;"><?= number_format($net_total, 2) ?></td>
            </tr>
            <?php endforeach; ?>

            <!-- EXTRA BLANK ROWS -->
            <?php
            $itemCount = count($items);
            $minimumRows = 10;
            $columnCount = ($gstType === 'IGST' || $gstType === 'VAT') ? 13 : 15;

            for ($i = $itemCount; $i < $minimumRows; $i++):
            ?>
            <tr style="height: 22px;">
                <?php for ($col = 0; $col < $columnCount; $col++): ?>
                    <td style="border-right: 1px solid #000; padding: 2px 0; height: 22px; font-size: 8pt;">&nbsp;</td>
                <?php endfor; ?>
            </tr>
            <?php endfor; ?>

            <!-- TOTALS ROW -->
            <tr style="font-weight: bold;">
                <td colspan="7" style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;">Total</td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt; border-top: 2px solid #000;"><?= $dbTotalQty > 0 ? $dbTotalQty : $totalRenderedQty ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt; border-top: 2px solid #000;"></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;"><?= number_format($dbSubTotal, 2) ?></td>
                
                <?php if ($gstType === 'IGST'): ?>
                    <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt; border-top: 2px solid #000;"></td>
                    <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;"><?= number_format($dbIgst, 2) ?></td>
                <?php elseif ($gstType === 'VAT'): ?>
                    <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt; border-top: 2px solid #000;"></td>
                    <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;"><?= number_format($dbVat, 2) ?></td>
                <?php else: ?>
                    <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt; border-top: 2px solid #000;"></td>
                    <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;"><?= number_format($dbSgst, 2) ?></td>
                    <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt; border-top: 2px solid #000;"></td>
                    <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;"><?= number_format($dbCgst, 2) ?></td>
                <?php endif; ?>

                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;"><?= number_format($calculatedGrandTotal, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- FOOTER SECTION -->
    <table style="border: 1px solid #000;">
        <tr style="vertical-align: top;">
            <!-- LEFT: GST Summary & Terms -->
            <td style="width: 75%; border-right: 1px solid #000;  padding: 0px 0; vertical-align: top;">
               
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 0px;">
                     <tr style="">
                        <td style=" border-left: 1px  #000;">
                            <div style="font-weight: bold; font-size: 8pt; margin-bottom: 2px; ">Company GSTin: <?= htmlspecialchars($companyGst) ?></div>
                        </td>
                        <td style="">
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 0px;">
                                <tr style="background: #fff;">
                                    <th style=" padding: 1px 2px; text-align: center; font-size: 7pt; font-weight: bold;">Tax %</th>
                                    <th style=" padding: 1px 2px; text-align: center; font-size: 7pt; font-weight: bold;">Taxable</th>
                                    <?php if ($gstType === 'IGST'): ?>
                                        <th style=" padding: 1px 2px; text-align: center; font-size: 7pt; font-weight: bold;">IGST</th>
                                    <?php elseif ($gstType === 'VAT'): ?>
                                        <th style=" padding: 1px 2px; text-align: center; font-size: 7pt; font-weight: bold;">VAT</th>
                                    <?php else: ?>
                                        <th style=" padding: 1px 2px; text-align: center; font-size: 7pt; font-weight: bold;">SGST</th>
                                        <th style=" padding: 1px 2px; text-align: center; font-size: 7pt; font-weight: bold;">CGST</th>
                                    <?php endif; ?>
                                </tr>
                                <tr>
                                    <td style=" padding: 1px 2px; text-align: center; font-size: 8pt;"><?= number_format((float)$taxPercentVal, 2) ?>%</td>
                                    <td style=" padding: 1px 2px; text-align: center; font-size: 8pt;"><?= number_format($dbSubTotal, 2) ?></td>
                                    <?php if ($gstType === 'IGST'): ?>
                                        <td style=" padding: 1px 2px; text-align: center; font-size: 8pt;"><?= number_format($dbIgst, 2) ?></td>
                                    <?php elseif ($gstType === 'VAT'): ?>
                                        <td style=" padding: 1px 2px; text-align: center; font-size: 8pt;"><?= number_format($dbVat, 2) ?></td>
                                    <?php else: ?>
                                        <td style=" padding: 1px 2px; text-align: center; font-size: 8pt;"><?= number_format($dbSgst, 2) ?></td>
                                        <td style=" padding: 1px 2px; text-align: center; font-size: 8pt;"><?= number_format($dbCgst, 2) ?></td>
                                    <?php endif; ?>
                                </tr>
                            </table>
                        </td>
                      </tr>
                </table>
                 <div style="font-weight: bold; font-size: 8pt; margin-top: 10px;"><?= htmlspecialchars($message) ?></div>
            </td>
            
            <!-- RIGHT: Totals Column (CORRECTED) -->
            <td style="width: 25%; padding: 3px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;"><strong>Gross Amount</strong></td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;"><strong><?= number_format($trueGrossAmount, 2) ?></strong></td>
                    </tr>
                    
                    <!-- Line-Item Discount (First Discount %) -->
                    <?php if ($totalLineItemDiscount > 0): ?>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Disc</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;">-<?= number_format($totalLineItemDiscount, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <!-- Cash Discount (CD %) - CORRECTED -->
                    <?php if ($totalCdDiscount > 0): ?>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Cash Disc(<?= number_format($cdPercent, 2) ?>%)</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;">-<?= number_format($totalCdDiscount, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <!-- Additional Discount (Invoice Level) -->
                    <?php if ($additionalDiscount > 0): ?>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Add.Discount</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;">-<?= number_format($additionalDiscount, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <!-- Subtotal After All Discounts -->
                    <?php if ($totalAllDiscounts > 0): ?>
                    <tr style="border-top: 1px solid #000; font-weight: bold;">
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Subtotal</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;"><?= number_format($trueGrossAmount - $totalAllDiscounts, 2) ?></td>
                    </tr>
                    <?php else: ?>
                    <tr style="border-top: 1px solid #000; font-weight: bold;">
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Taxable Amount</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;"><?= number_format($dbSubTotal, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <!-- GST/Tax Components -->
                    <?php if ($gstType === 'CGST_SGST'): ?>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Add : SGST</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;">+<?= number_format($dbSgst, 2) ?></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Add : CGST</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;">+<?= number_format($dbCgst, 2) ?></td>
                    </tr>
                    <?php elseif ($gstType === 'IGST'): ?>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Add : IGST</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;">+<?= number_format($dbIgst, 2) ?></td>
                    </tr>
                    <?php elseif ($gstType === 'VAT'): ?>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Add : VAT</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;">+<?= number_format($dbVat, 2) ?></td>
                    </tr>
                    <?php endif; ?>

                    <!-- Other Charges -->
                    <?php if ($dbOther != 0): ?>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Other Charges</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;"><?= $dbOther > 0 ? '+' : '' ?> <?= number_format($dbOther, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <!-- <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Freight</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;">+ 0.00</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Credit Note</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;">- 0.00</td>
                    </tr> -->
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Round Off</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;"><?= $roundOff > 0 ? '+' : '' ?> <?= number_format($roundOff, 2) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr style="border: 1px solid #000;">
            <td style="width: 75%;">
                <table>
                    <tr style="font-weight: bold; ">
                        <td style="border: none;  font-size: 8pt; ">
                            <div>Amount in Words: <span> </span></div>
                        </td>
                        <td style="border: none; padding: 0px 0px; font-size: 8pt; "> <?= htmlspecialchars($amountInWords ?? 'Total Amount In Words') ?></td>
                    </tr>
                </table>
            </td>
            <td style="width: 25%; ">
                <table>
                    <tr style="font-weight: bold; border-left: 1px solid #000;  ">
                        <td style="border: none; padding: 0px 0px; font-size: 8pt; text-align: left;">Net Amount</td>
                        <td style="border: none; padding: 0px 0px; font-size: 8pt; text-align: right;"><?= number_format($netAmountRounded, 2) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="width: 75%; height: 10px; padding-bottom: 50px;"><strong>Terms & Conditions:</strong>
                <div style="margin-left: 25px; margin-top: 0px;">
                    <?= $term_and_condition ?>
                </div>
            </td>
            <td style="width: 25%;  padding-bottom: 50px;">For, <?= htmlspecialchars($companyName) ?></td>
        </tr>
        <tr style="margin-top:25px;">
             <td style="width: 75%; margin-top:25px; "></td>
            <td style="width: 25%; ">
                 <div style="text-align: center; font-size: 7pt;   ">Authorised Signatory</div> 
            </td>
        </tr>
    </table>
</div>
</body>
</html>