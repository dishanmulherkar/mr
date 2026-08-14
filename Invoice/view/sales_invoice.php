<?php

// ========== DATA EXTRACTION ==========
// Use the new sales variables returned from getSalesInvoice
$invoiceNo = $invoice['invoice_no'] ?? ('SALE-' . ($invoice['sale_id'] ?? ''));
$invoiceDate = !empty($invoice['sale_date']) ? date('d/m/Y', strtotime($invoice['sale_date'])) : '';

$orderNo = $invoice['order_no'] ?? '';
$orderDate = !empty($invoice['order_date']) ? date('d/m/Y', strtotime($invoice['order_date'])) : '';
$dueDate = !empty($invoice['sale_date']) ? date('d/m/Y', strtotime($invoice['sale_date'] . ' + ' . ($invoice['credit_days'] ?? 30) . ' days')) : '';

// 1. SELLER: Stockist Details (Now mapped to the Header)
$sellerName = $invoice['stockist_name'] ?? 'STOCKIST NAME';
$sellerAddress = $invoice['stockist_address'] ?? '';
$sellerGst = $invoice['gst_no'] ?? '';
$term_and_condition = $invoice['term_and_condition'] ?? '';

// 2. BUYER: Customer Details (Now mapped to the "To," section)
$customerName = $invoice['customer_name'] ?? '';
$customerAddress = $invoice['customer_address'] ?? ''; // Assuming you fetch this, otherwise blank
$customerPhone = $invoice['mobile'] ?? '';
$customerGst = $invoice['customer_gst'] ?? '';
$customerPan = $invoice['customer_pan'] ?? '';
$customerDl = $invoice['customer_dl'] ?? '';

$dispatchTo = $invoice['dispatch_to'] ?? '';
$transport = $invoice['transport_name'] ?? ($invoice['transport'] ?? '');
$stateName = $invoice['state_name'] ?? '';
$districtName = $invoice['district_name'] ?? '';
$pincode = $invoice['pincode'] ?? '';
$lrNo = $invoice['lr_no'] ?? '';

$items = $invoice['items'] ?? [];
$gstType = strtoupper($invoice['gst_type'] ?? 'CGST_SGST'); // Expected: CGST_SGST, IGST, or VAT

// Totals (Will recalculate below if missing from DB)
$dbTotalQty = (float)($invoice['total_qty'] ?? 0);
$dbSubTotal = (float)($invoice['sub_total'] ?? 0);
$dbHeaderDiscount = (float)($invoice['discount'] ?? 0);
$dbSgst = (float)($invoice['sgst_amount'] ?? 0);
$dbCgst = (float)($invoice['cgst_amount'] ?? 0);
$dbIgst = (float)($invoice['igst_amount'] ?? 0);
$dbVat = (float)($invoice['vat_amount'] ?? ($invoice['gst_amount'] ?? 0));
$dbOther = (float)($invoice['other_charges'] ?? 0);
$dbGrandTotal = (float)($invoice['grand_total'] ?? 0);

$additionalDiscount = (float)($invoice['additional_discount'] ?? 0);

// Calculate totals dynamically from items
$trueGrossAmount = 0;
$calcSubTotal = 0;
$calcGrandTotal = 0;
$totalRenderedQty = 0;

foreach ($items as $item) {
    $qty = (float)($item['qty'] ?? 0);
    $rate = (float)($item['rate'] ?? 0);
    $mrp = (float)($item['mrp'] ?? 0);
    
    // Amounts
    $rowBase = $qty * $rate;
    $discPerc = (float)($item['discount_percent'] ?? 0);
    
    // Apply Item Discount Only (CD Removed)
    $firstDiscAmount = $rowBase * ($discPerc / 100);
    $amt = $rowBase - $firstDiscAmount; // Taxable Value
    
    $taxRate = (float)($item['gst_percent'] ?? 0);
    $taxAmount = (float)($item['gst_amount'] ?? ($amt * ($taxRate / 100)));
    $net_total = (float)($item['net_total'] ?? ($amt + $taxAmount));

    $trueGrossAmount += $rowBase;
    $calcSubTotal += $amt;
    $calcGrandTotal += $net_total;
    $totalRenderedQty += $qty;
}

// Fallback to calculated totals if database totals are missing/0
if ($dbSubTotal == 0) $dbSubTotal = $calcSubTotal;
if ($dbGrandTotal == 0) $dbGrandTotal = $calcGrandTotal;

$netAmountRounded = round($dbGrandTotal);
$roundOff = $netAmountRounded - $dbGrandTotal;

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
        <td style="width: 33.33%; padding: 2px; text-align: left;"></td>
        <td style="width: 33.33%; padding: 2px; text-align: center;">TAX INVOICE</td>
        <td style="width: 33.33%; padding: 2px; text-align: right;">Original / Duplicate / Triplicate</td>
    </tr>
</table>

    <!-- Stockist (Seller) Header -->
    <div style="text-align: center; margin-bottom: 3px; border: 1px solid #000; ">
        <div style="font-weight: bold; font-size: 13pt; margin-bottom: 2px;">
            <?= htmlspecialchars($sellerName) ?>
        </div>
        <div style="font-size: 8pt; margin-bottom: 2px;">
            <?= htmlspecialchars($sellerAddress) ?>
        </div>
    </div>

    <!-- Main Info Table -->
    <table style="border: 1px solid #000; margin-bottom: 2px;">
        <tr>
            <!-- Left: To Section (Customer) -->
            <td style="width: 55%; border-right: 1px solid #000; padding: 3px 0; vertical-align: top;">
                <div style="font-weight: bold; text-decoration: underline; font-size: 8pt; margin-bottom: 2px;">To,</div>
                <div style="font-size: 8pt;  padding: 0px 3px; line-height: 1.3;">
                    <strong>M/S. <?= htmlspecialchars($customerName) ?></strong><br>
                    <?php 
                    $addressLines = array_filter([
                        $customerAddress ? 'AT:- ' . $customerAddress : null,
                        $districtName ? 'DIST.- ' . $districtName : null,
                        $pincode ? $pincode . ', ' . $stateName : $stateName
                    ]);
                    foreach ($addressLines as $line) {
                        echo htmlspecialchars($line) . '<br>';
                    }
                    if ($customerPhone) echo 'Phone No: ' . htmlspecialchars($customerPhone) . '<br>';  ?>
                </div>
                    <table style="width: 100%; border-collapse: collapse; ">
                        <tr>
                            <td style="border: none;  border-top: 1px solid #000; padding: 1px 3px; width: 50%; font-size: 8pt;">
                                <?php if ($customerGst) echo 'GST No: ' . htmlspecialchars($customerGst) . '<br>'; ?>
                            </td>
                            <td style="border: none;  border-top: 1px solid #000; padding: 1px 3px; width: 50%; font-size: 8pt;">
                                <?php if ($customerPan) echo 'PAN No: ' . htmlspecialchars(strtoupper($customerPan)) . '<br>'; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 1px 3px; width: 50%; font-size: 8pt;">
                                <?php if ($customerDl) echo 'DL No: ' . htmlspecialchars($customerDl) . '<br>'; ?>
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
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">:</td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">Due Date</td>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">:</td>
                        </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table style="border: 1px solid #000; margin-bottom: 2px;">
        <thead>
            <tr>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 3%;">Sr.</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 8%;">HSN Code</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: left; font-size: 7pt; font-weight: bold; width: 26%;">Description of Goods</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 10%;">Batch</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 6%;">Exp</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 7pt; font-weight: bold; width: 8%;">MRP</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 6%;">Qty</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 7pt; font-weight: bold; width: 8%;">Amount</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 8%;">&nbsp;</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 10%;">&nbsp;</th>                
            </tr>
        </thead>
        <tbody>
            <?php 
            $sr = 1;
            foreach ($items as $item): 
                $qty = (float)($item['qty'] ?? 0);
                $rate = (float)($item['rate'] ?? 0);
                $mrp = (float)($item['mrp'] ?? 0);
                $discPerc = (float)($item['discount_percent'] ?? 0);
                
                $rowBase = $qty * $rate;
                $firstDiscAmount = $rowBase * ($discPerc / 100);
                $amt = $rowBase - $firstDiscAmount;
                
                $taxRate = (float)($item['gst_percent'] ?? 0);
                $taxAmount = (float)($item['gst_amount'] ?? ($amt * ($taxRate / 100)));
                $net_total = (float)($item['net_total'] ?? ($amt + $taxAmount));
            ?>
            <tr>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;"><?= $sr++ ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;"><?= htmlspecialchars($item['hsn_code'] ?? '') ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: left; font-size: 8pt;"><?= htmlspecialchars($item['product_name'] ?? '') ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;"><?= htmlspecialchars($item['batch_no'] ?? '') ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;"><?= !empty($item['expiry_date']) ? date('m/y', strtotime($item['expiry_date'])) : '' ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt;"><?= number_format($rate, 2) ?></td>
                 <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;"><?= $qty ?></td>
                 <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt;"><?= number_format($net_total, 2) ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;">&nbsp;</td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;">&nbsp;</td>


                
            </tr>
            <?php endforeach; ?>

            <!-- EXTRA BLANK ROWS -->
            <?php
            $itemCount = count($items);
            $minimumRows = 9;
            $columnCount = 10; 

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
                <td colspan="6" style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;">Total</td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt; border-top: 2px solid #000;"><?= $dbTotalQty > 0 ? $dbTotalQty : $totalRenderedQty ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;"><?= number_format($dbGrandTotal, 2) ?></td>
                 <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;"></td>
                  <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;"></td>
            </tr>
        </tbody>
    </table>

    <!-- FOOTER SECTION -->
    <table style="border: 1px solid #000;">
        <tr style="vertical-align: top;">
            <!-- LEFT: GST Summary & Terms -->
            <td style="width: 45%; border-right: 1px solid #000;  padding: 0px 0; vertical-align: top;">
               
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 0px;">
                     <tr style="background: ;">
                        <td style="">
                            <div style="font-weight: bold; font-size: 8pt; margin-bottom: 2px; ">Stockist GSTin: <?= htmlspecialchars($sellerGst) ?></div>
                        </td>
                        <td style="">
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 0px;">
                                <tr style="background: #fff;">
                                    
                                </tr>
                                <tr>
                                   
                                </tr>
                            </table>
                        </td>
                      </tr>
                </table>
                 <div style="font-weight: bold; font-size: 8pt; margin-top: 10px;"><?= htmlspecialchars($message) ?></div>
            </td>
            
            <!-- RIGHT: Totals Column (Reduced as requested) -->
            <td style="width: 55%; ">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="border: none; padding: 3px 3px; font-size: 8pt; text-align: left;  width: 33%;">Gross Amount</td>
                        <td style="border: none; padding: 3px 3px; font-size: 8pt; text-align: right; width: 38%; border-right: 1px solid #000;"><?= number_format($trueGrossAmount, 2) ?></td>
                        <td style="border: none; padding: 3px 3px;  border-right: 1px solid #000;font-size: 8pt; text-align: right; width: 27%;"></td>
                        <td style="border: none; padding: 3px 3px; font-size: 8pt; text-align: right; width: 33%;">

                        </td>
                    </tr>
                    
                    <tr>
                        <td style="border: none; padding: 3px 3px; font-size: 8pt; text-align: left;">Round Off</td>
                        <td style="border: none; padding: 3px 3px; font-size: 8pt; text-align: right; border-right: 1px solid #000;"><?= number_format($roundOff, 2) ?></td>
                        <td style="border: none; padding: 3px 3px; border-right: 1px solid #000; font-size: 8pt; text-align: right;"></td>
                        <td style="border: none; padding: 3px 3px; font-size: 8pt; text-align: right;">
                        </td>
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
                    <tr style="font-weight: bold; border-left: 1px solid #161515;  ">
                        <td style="border: none; padding: 0px 0px; font-size: 8pt; text-align: left;">Net Amount</td>
                        <td style="border: none; padding: 0px 0px; font-size: 8pt; text-align: right;"><?= number_format($netAmountRounded, 2) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
         <tr>
            <td style="width: 75%; height: 10px; padding-bottom: 50px;">
            </td>
            <td style="width: 25%;  padding-bottom: 50px;"></td>
        </tr>
        <tr style="margin-top:25px;">
             <td style="width: 75%; margin-top:25px; "></td>
            <td style="width: 25%; ">
                 
            </td>
        </tr>
        <tr style = "border-top: 1px solid #161515;">
            <td style="width: 75%; height: 10px; padding-bottom: 50px;"><strong>Terms & Conditions:</strong>
                <div style="margin-left: 25px; margin-top: 0px;">
                    <?= $term_and_condition ?>
                </div>
            </td>
            <td style="width: 25%;  padding-bottom: 50px;">For, <?= htmlspecialchars($sellerName) ?></td>
        </tr>
        <tr style="margin-top:25px; border-top: 1px solid #161515;">
             <td style="width: 75%; margin-top:25px; "></td>
            <td style="width: 25%; ">
                 <div style="text-align: center; font-size: 7pt;   ">Authorised Signatory</div> 
            </td>
        </tr>
    </table>
</div>
</body>
</html>