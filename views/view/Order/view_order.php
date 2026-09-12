<?php 
$pageTitle = "Order Details";
include 'view/layout/header.php';

if (!$order_data) {
    echo "<div class='page-content'><h4>Order not found or unauthorized.</h4></div>";
    include 'view/layout/footer.php';
    exit;
}
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
    .badge-dispatched { background: #0dcaf0; color: #000 !important; } /* Added missing class */

    .summary-card {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        border: 1px solid #e9ecef;
    }
    
    .qty-box {
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: bold;
    }
    .qty-match { color: #28a745; } 
    .qty-diff { color: #dc3545; background: #ffeeba; } 
    .qty-added { color: #fff; background: #17a2b8; font-size: 11px; padding: 2px 6px; border-radius: 4px; }
   
</style>
<link rel="stylesheet" href="<?= BASE_URL ?>config/config/salesentry.css">

<div class="page-content">

    <div class="page-header d-flex justify-content-between align-items-center mb-3">
        <h5>Invoice NO: <?= htmlspecialchars($order_data['inward_no'] ?? 'N/A') ?> </h5>
        <a href="<?= BASE_URL ?>OrderEntry/view" class="btn-submit" style="background: #6c757d; padding: 6px 12px;">
            <i class="fa fa-arrow-left"></i>
        </a>
    </div>

    <div class="summary-card d-flex justify-content-between">
        <div>
            <strong>Stockist:</strong> <?= htmlspecialchars($order_data['stockist_name'] ?? 'N/A') ?><br>
         <strong>Order Date:</strong> <?= date('d/n/y', strtotime($order_data['order_date'])) ?> <br>
<strong>Inv Date:</strong> <?= !empty($order_data['inward_date']) ? date('d/n/y', strtotime($order_data['inward_date'])) : 'N/A' ?>
        </div>
        <div style="text-align: right;">
            <strong>Status:</strong> 
            <?php 
                $raw_status = $order_data['status'] ?? 'Pending';
                
                // Determine badge class and what text to actually show the user
                if ($raw_status === 'Processed') {
                    $badge = 'badge-dispatched';
                    $display_text = 'Dispatched';
                } 
                elseif ($raw_status === 'Approved') {
                    $badge = 'badge-approved';
                    $display_text = 'Approved';
                } 
                elseif ($raw_status === 'Rejected') {
                    $badge = 'badge-rejected';
                    $display_text = 'Rejected';
                } 
                else {
                    $badge = 'badge-pending';
                    $display_text = 'Pending';
                }
            ?>
            <span class="status-badge <?= $badge ?>"><?= strtoupper($display_text) ?></span><br>
            <strong>Total Amount:</strong> <span style="font-size: 18px; color: #28a745; font-weight: bold;">₹<?= number_format($order_data['total_amt'], 2) ?></span>
        </div>
    </div>

    <div class="inv-table-wrap">
        <table class="inv-table">
            <thead>
            <tr>
                <th width="50">S.No</th>
                <th>Product Name</th>
                <th class="text-center">MR Ordered</th>
                <th class="text-center">Admin Approved</th>
            </tr>
            </thead>
            <tbody>
                <?php 
                $count = 1;
                foreach ($order_data['items'] as $item): 
                    
                    $mr_qty = $item['qty'];
                    $admin_qty = $item['approved_qty'];
                    
                    $qty_display = '-';
                    if ($raw_status == 'Pending' || $admin_qty === null) {
                        $qty_display = '<span style="color:gray;">Pending</span>';
                    } elseif ($mr_qty == 0 && $admin_qty > 0) {
                        $qty_display = '<span class="qty-added">Added by Admin: ' . $admin_qty . '</span>';
                    } elseif ($mr_qty != $admin_qty) {
                        $qty_display = '<span class="qty-box qty-diff">' . $admin_qty . '</span>';
                    } else {
                        $qty_display = '<span class="qty-box qty-match">' . $admin_qty . '</span>';
                    }
                ?>
                <tr>
                    <td><?= $count++ ?></td>
                    <td>
                        <strong><?= htmlspecialchars($item['name']) ?></strong>
                        <?php if($mr_qty == 0 && $admin_qty > 0): ?>
                            <span style="color: #17a2b8; font-size: 10px; margin-left: 5px;">(New Item)</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?= ($mr_qty > 0) ? $mr_qty : '-' ?>
                    </td>
                    <td class="text-center">
                        <?= $qty_display ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- UPDATED FOOTER WITH DISPATCH DATE -->
    <div class="table-footer d-flex justify-content-between align-items-center" style="padding: 10px 15px; background: #f8f9fa; border: 1px solid #e9ecef; border-top: none; border-radius: 0 0 6px 6px;">
        <div>
            Total Items: <strong><?= count($order_data['items']) ?></strong>
        </div>
        
        <?php if ($raw_status === 'Processed' && !empty($order_data['dispatch_date'])): ?>
            <div style="color: #0dcaf0; font-weight: bold; font-size: 15px;">
                <i class="fa fa-truck"></i> Dispatched On: <?= date('d M Y', strtotime($order_data['dispatch_date'])) ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'view/layout/footer.php'; ?>