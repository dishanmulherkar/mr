<?php
/**
 * RUDRADEO PHARMACEUTICAL INVOICE
 * Exact PDF Layout Match for Dompdf
 * Matches: T-381 Jeevak Ayurved Format
 */

// ========== DATA EXTRACTION ==========
$invoiceNo = $invoice['inward_no'] ?? ('T-' . ($invoice['order_id'] ?? ''));
$invoiceDate = !empty($invoice['inward_date']) ? date('d/m/Y', strtotime($invoice['inward_date'])) : '';
$orderNo = $invoice['order_no'] ?? '';
$orderDate = !empty($invoice['order_date']) ? date('d/m/Y', strtotime($invoice['order_date'])) : '';
$dueDate = !empty($invoice['inward_date']) ? date('d/m/Y', strtotime($invoice['inward_date'] . ' + ' . ($invoice['credit_days'] ?? 30) . ' days')) : '';

// Stockist Details
$stockistName = $invoice['stockist_name'] ?? '';
$stockistAddress = $invoice['stockist_address'] ?? '';
$stockistGst = $invoice['gst_no'] ?? '';
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
$companyAddress = trim(implode(', ', array_filter([
    $invoice['company_address'] ?? null,
    $invoice['company_district'] ?? null,
    $invoice['company_state'] ?? null,
    $invoice['company_pincode'] ?? null
])));

$items = $invoice['items'] ?? [];
$gstType = $invoice['gst_type'] ?? 'CGST_SGST';

// Totals
$dbTotalQty = (float)($invoice['total_qty'] ?? 0);
$dbSubTotal = (float)($invoice['sub_total'] ?? 0);
$dbHeaderDiscount = (float)($invoice['discount'] ?? 0);
$dbSgst = (float)($invoice['sgst_amount'] ?? 0);
$dbCgst = (float)($invoice['cgst_amount'] ?? 0);
$dbIgst = (float)($invoice['igst_amount'] ?? 0);
$dbOther = (float)($invoice['other_charges'] ?? 0);
$dbGrandTotal = (float)($invoice['grand_total'] ?? 0);

// Calculate totals
$trueGrossAmount = 0;
$trueItemDiscount = 0;
foreach ($items as $item) {
    $qty = (float)($item['qty'] ?? 0);
    $rate = (float)($item['rate'] ?? 0);
    $amt = (float)($item['amt'] ?? ($qty * $rate));
    $rowBase = $qty * $rate;
    $rowDiscount = $rowBase - $amt;
    $trueGrossAmount += $rowBase;
    $trueItemDiscount += $rowDiscount;
}

$totalDiscount = $trueItemDiscount + $dbHeaderDiscount;
$netAmountRounded = round($dbGrandTotal);
$roundOff = $netAmountRounded - $dbGrandTotal;

$taxPercentVal = !empty($items) && isset($items[0]['gst_percent']) ? $items[0]['gst_percent'] : '0';

// Message
$message = $invoice['message'] ?? 'Message :';
if (strpos($message, ':') === false) {
    $message = 'Message : ' . $message;
}
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
    <!-- Header: CREDIT TAX INVOICE -->
    <div style="text-align: right; font-weight: bold; font-size: 8pt; margin-bottom: 3px; border: 1px solid #000; padding: 2px;">
        CREDIT TAX INVOICE Original / Duplicate / Triplicate
    </div>

    <!-- Company Header -->
    <div style="text-align: center; margin-bottom: 3px;">
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
                    <strong>M/S. <?= htmlspecialchars($stockistName) ?></strong><br>
                    <?php 
                    $addressLines = array_filter([
                        $stockistAddress ? 'AT:- ' . $stockistAddress : null,
                        $districtName ? 'DIST.- ' . $districtName : null,
                        // $stockistAddress ? $stockistAddress : null,
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
                            <td style="border: none; padding: 1px 3px; width: 50%; font-size: 8pt;">
                                <!-- Empty -->
                            </td>
                        </tr>
                    </table>
                
            </td>
            
            <!-- Right: Invoice Details -->
            <td style="width: 45%; padding: 3px 0; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse; ">
                   <!-- Invoice / Date -->
                        <tr>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">
                                Invoice No
                            </td>

                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-weight: bold; font-size: 8pt;">
                                : <?= htmlspecialchars($invoiceNo) ?>
                            </td>

                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">
                                Inv Date
                            </td>

                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">
                                : <?= htmlspecialchars($invoiceDate) ?>
                            </td>
                        </tr>

                        <!-- Order / Order Date -->
                        <tr>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">
                                Order No
                            </td>

                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">
                                : <?= htmlspecialchars($orderNo) ?>
                            </td>

                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">
                                Ord. Date
                            </td>

                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">
                                : <?= htmlspecialchars($orderDate) ?>
                            </td>
                        </tr>

                        <!-- Transport -->
                        <tr>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">
                                Transport
                            </td>

                            <td colspan="3" style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 75%; font-size: 8pt;">
                                : <?= htmlspecialchars($transport) ?>
                            </td>
                        </tr>

                        <!-- LR No / LR Date -->
                        <tr>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">
                                L.R. No
                            </td>

                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">
                                : <?= htmlspecialchars($lrNo) ?>
                            </td>

                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">
                                LR Date
                            </td>

                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">
                                : <?= htmlspecialchars($lrDate ?? '') ?>
                            </td>
                        </tr>

                        <!-- Despatch To -->
                        <tr>
                            <td style="border: none;  border-bottom: 1px solid #000; padding: 1px 3px; width: 30%; font-size: 8pt;">
                                Despatch to
                            </td>

                            <td colspan="3" style="border: none;  border-bottom: 1px solid #000; padding: 1px 3px; width: 70%; font-size: 8pt;">
                                : <?= htmlspecialchars($dispatchTo) ?>
                            </td>
                        </tr>

                        <!-- Terms / Due Date - BOTTOM BORDER -->
                        <tr>
                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">
                                Terms
                            </td>

                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">
                                : <?= htmlspecialchars($invoice['credit_days'] ?? '30') ?> Days
                            </td>

                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">
                                Due Date
                            </td>

                            <td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px; width: 25%; font-size: 8pt;">
                                : <?= htmlspecialchars($dueDate) ?>
                            </td>
                        </tr>

                        <!-- Empty Space -->
                        <tr>
                            <td style="border: none; padding: 3px; height: 5px;"></td>
                            <td style="border: none; padding: 3px;"></td>
                            <td style="border: none; padding: 3px;"></td>
                            <td style="border: none; padding: 3px;"></td>
                        </tr>

                        <!-- Empty Space -->
                        <tr>
                            <td style="border: none; padding: 3px;"></td>
                            <td style="border: none; padding: 3px;"></td>
                            <td style="border: none; padding: 3px;"></td>
                            <td style="border: none; padding: 3px;"></td>
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
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 5%;">Qty/Fr</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 4%;">Disc<br>(%)</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 7pt; font-weight: bold; width: 6%;">Taxable<br>Value</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 4%;">SGST<br>Rate</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 7pt; font-weight: bold; width: 5%;">SGST<br>Value</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; width: 4%;">CGST<br>Rate</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 7pt; font-weight: bold; width: 5%;">CGST<br>Value</th>
                <th style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 7pt; font-weight: bold; width: 6%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sr = 1;
            $totalRenderedQty = 0;

            foreach ($items as $item): 
                $qty = (float)($item['qty'] ?? 0);
                $totalRenderedQty += $qty;
                
                $rate = (float)($item['rate'] ?? 0);
                $amt = (float)($item['amt'] ?? ($qty * $rate));
                $net_total = (float)($item['net_total'] ?? $amt);
                
                $mrp = (float)($item['mrp'] ?? 0);
                $discPerc = (float)($item['discount_percent'] ?? 0);
                $taxRate = (float)($item['gst_percent'] ?? 0);
                
                $taxAmount = $net_total - $amt;
                $sgstRate = $taxRate / 2;
                $cgstRate = $taxRate / 2;
                
                $sgstVal = 0;
                $cgstVal = 0;
                if ($gstType === 'CGST_SGST') {
                    $sgstVal = $taxAmount / 2;
                    $cgstVal = $taxAmount / 2;
                }
            ?>
            <tr>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;">
                    <?= $sr++ ?>
                </td>

                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;">
                    <?= htmlspecialchars($item['hsn_code'] ?? '') ?>
                </td>

                <td style="border: 1px solid #000; padding: 2px; text-align: left; font-size: 8pt;">
                    <?= htmlspecialchars($item['product_name'] ?? '') ?>
                </td>

                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;">
                    <?= htmlspecialchars($item['batch_no'] ?? '') ?>
                </td>

                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;">
                    <?= !empty($item['expiry_date'])
                        ? date('m/y', strtotime($item['expiry_date']))
                        : '' ?>
                </td>

                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt;">
                    <?= number_format($mrp, 2) ?>
                </td>

                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;">
                    <?= number_format($rate, 2) ?>
                </td>

                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;">
                    <?= $qty ?>
                </td>

                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;">
                    <?= number_format($discPerc, 2) ?>
                </td>

                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt;">
                    <?= number_format($amt, 2) ?>
                </td>

                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;">
                    <?= number_format($sgstRate, 2) ?>
                </td>

                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt;">
                    <?= number_format($sgstVal, 2) ?>
                </td>

                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt;">
                    <?= number_format($cgstRate, 2) ?>
                </td>

                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt;">
                    <?= number_format($cgstVal, 2) ?>
                </td>

                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt;">
                    <?= number_format($net_total, 2) ?>
                </td>
            </tr>

            <?php endforeach; ?>


            <!-- EXTRA BLANK ROWS -->
            <?php
            $itemCount = count($invoice['items']);
            $minimumRows = 10;

            for ($i = $itemCount; $i < $minimumRows; $i++):
            ?>

            <tr style="height: 22px;">
                <?php for ($col = 0; $col < 15; $col++): ?>
                    <td style="
                        border-right: 1px solid #000;
                        padding: 2px 0;
                        height: 22px;
                        font-size: 8pt;
                    ">&nbsp;</td>
                <?php endfor; ?>
            </tr>

            <?php endfor; ?>

            <!-- TOTALS ROW -->
            <tr style="font-weight: bold;">
                <td colspan="6" style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;">Total</td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt; border-top: 2px solid #000;"></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt; border-top: 2px solid #000;"><?= $dbTotalQty > 0 ? $dbTotalQty : $totalRenderedQty ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt; border-top: 2px solid #000;"></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;"><?= number_format($dbSubTotal, 2) ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;"></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt; border-top: 2px solid #000;"><?= number_format($dbSgst, 2) ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;"></td>
                <!-- <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 8pt; border-top: 2px solid #000;"></td> -->
                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;"><?= number_format($dbCgst, 2) ?></td>
                <td style="border: 1px solid #000; padding: 2px; text-align: right; font-size: 8pt; border-top: 2px solid #000;"><?= number_format($dbGrandTotal, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- FOOTER SECTION -->
    <table style="border: 1px solid #000;">
        <tr style="vertical-align: top;">
            <!-- LEFT: GST Summary & Terms -->
            <td style="width: 75%; border-right: 1px solid #000;  padding: 3px 0; vertical-align: top;">
                <div style="font-weight: bold; font-size: 8pt; margin-bottom: 2px;"><?= htmlspecialchars($message) ?></div>
                

                <table style="width: 100%; border-collapse: collapse; margin-bottom: 3px;">
                     <tr style="background: #fff;">
                        <td style="border-top: 1px solid #000;">
                            <div style="font-weight: bold; font-size: 8pt; margin-bottom: 2px; ">Company GSTin: <?= htmlspecialchars($companyGst) ?></div>
                        </td>
                        <td style="border-top: 1px solid #000;">
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 3px;">
                                <tr style="background: #fff;">
                                    <th style=" padding: 1px 2px; text-align: center; font-size: 7pt; font-weight: bold;">GST %</th>
                                    <th style=" padding: 1px 2px; text-align: center; font-size: 7pt; font-weight: bold;">Taxable</th>
                                    <th style=" padding: 1px 2px; text-align: center; font-size: 7pt; font-weight: bold;">SGST</th>
                                    <th style=" padding: 1px 2px; text-align: center; font-size: 7pt; font-weight: bold;">CGST</th>
                                    <th style=" padding: 1px 2px; text-align: center; font-size: 7pt; font-weight: bold;">IGST</th>
                                </tr>
                                <tr>
                                    <td style=" padding: 1px 2px; text-align: center; font-size: 8pt;"><?= number_format((float)$taxPercentVal, 2) ?>%</td>
                                    <td style=" padding: 1px 2px; text-align: center; font-size: 8pt;"><?= number_format($dbSubTotal, 2) ?></td>
                                    <td style=" padding: 1px 2px; text-align: center; font-size: 8pt;"><?= number_format($dbSgst, 2) ?></td>
                                    <td style=" padding: 1px 2px; text-align: center; font-size: 8pt;"><?= number_format($dbCgst, 2) ?></td>
                                    <td style=" padding: 1px 2px; text-align: center; font-size: 8pt;"><?= number_format($dbIgst, 2) ?></td>
                                </tr>
                            </table>
                        </td>
                      </tr>
                
                </table>
                
                
                
                <div style="font-weight: bold; font-size: 8pt; margin-bottom: 2px;">Amount in Words:</div>
                <div style="font-size: 8pt; margin-bottom: 3px;"><?= htmlspecialchars($invoice['amount_words'] ?? 'Total Amount In Words') ?></div>
                
                <div style="font-weight: bold; font-size: 8pt; margin-bottom: 2px;">Terms & Conditions</div>
                <div style="font-size: 7pt; line-height: 1.2; margin-bottom: 2px;">
                    <?= nl2br(htmlspecialchars($invoice['terms'] ?? "E. & O. E.\nSubject to jurisdiction.")) ?>
                </div>
                
                <!-- <div style="font-size: 6pt; color: #555; margin-top: 3px;">
                    .Software by VISUAL INFOSOFT PVT. LTD. : Customer Care No: 079 3520 7999 (1)
                </div> -->
            </td>
            
            <!-- RIGHT: Totals Column -->
            <td style="width: 25%; padding: 3px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Gross Amount</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;"><?= number_format($trueGrossAmount, 2) ?></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Discount</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;"><?= number_format($totalDiscount, 2) ?></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Add : SGST</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;"><?= number_format($dbSgst, 2) ?></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Add : CGST</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;"><?= number_format($dbCgst, 2) ?></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Add : IGST</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;"><?= number_format($dbIgst, 2) ?></td>
                    </tr>
                    <?php if ($dbOther != 0): ?>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Other +/-</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;"><?= number_format($dbOther, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Freight</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;">0.00</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Credit Note</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;">0.00</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: left;">Round Off</td>
                        <td style="border: none; padding: 1px 3px; font-size: 8pt; text-align: right;"><?= number_format($roundOff, 2) ?></td>
                    </tr>
                    <tr style="font-weight: bold; border-top: 1px solid #000;">
                        <td style="border: none; padding: 1px 3px; font-size: 9pt; text-align: left;">Net Amount</td>
                        <td style="border: none; padding: 1px 3px; font-size: 9pt; text-align: right;"><?= number_format($netAmountRounded, 2) ?></td>
                    </tr>
                </table>
                
                <div style="text-align: center; font-weight: bold; font-size: 8pt; margin-top: 25px; margin-bottom: 25px;">
                    For, <?= htmlspecialchars($companyName) ?>
                </div>
                
                <div style="text-align: center; font-size: 7pt; border-top: 1px solid #000; padding-top: 2px;">
                    Authorised Signatory
                </div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>