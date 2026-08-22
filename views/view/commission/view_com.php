<?php include 'view/layout/header.php'; ?>

<style>
    .status-badge {
        color: #fff !important;
        font-size: 14px;
        padding: 6px 12px;
        border-radius: 12px;
        font-weight: 600;
        display: inline-block;
    }
    .badge-pending { background: #ffc107; color: #212529 !important; }
    .badge-approved { background: #28a745; color: #fff !important; }
    
    .invoice-wrapper {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }
    
    .table-custom th {
        background-color: #f8f9fa;
        color: #495057;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }
    .table-custom td {
        vertical-align: middle;
    }
    .row-total {
        background-color: #f8f9fa;
        font-weight: 700;
        font-size: 1.1rem;
    }
    .text-success { color: #28a745 !important; }
    .text-danger { color: #dc3545 !important; }
</style>

<div class="page-content container mt-3 mb-5">
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Payout Details #<?= $payout['payout_id'] ?></h4>
        <a href="<?= BASE_URL ?>commission/mr_commision" class="btn btn-secondary btn-sm">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="invoice-wrapper">
        <!-- Status & Meta Info -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div>
                <h5 class="text-primary mb-1">Commission Statement</h5>
                <span class="text-muted" style="font-size: 13px;">Date: <?= $payout['payout_date'] ?></span>
            </div>
            <div>
                <?php $badgeClass = ($payout['status'] === 'Paid') ? 'badge-approved' : 'badge-pending'; ?>
                <span class="status-badge <?= $badgeClass ?>"><?= $payout['status'] ?></span>
            </div>
        </div>

        <!-- Single Table for Bills and Adjustments -->
        <div class="table-responsive">
            <table class="table table-bordered table-custom">
                <thead>
                    <tr>
                        <th>Inv No / Description</th>
                        <th>Date</th>
                        <th class="text-end">Taxable Amt</th>
                        <!-- <th class="text-center">Commision (%)</th> -->
                        <th class="text-end">Commision (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Bills Section -->
                    <?php if (!empty($bills)): ?>
                        <?php foreach ($bills as $bill): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($bill['inward_no']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($bill['stockist_name']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($bill['bill_date']) ?></td>
                                <td class="text-end"><?= number_format($bill['taxable_amount'], 2) ?></td>
                                <!-- <td class="text-center"><?= floatval($bill['pts']) ?>%</td> -->
                                <td class="text-end text-success">+ <?= number_format($bill['commission_amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted">No bills attached.</td></tr>
                    <?php endif; ?>

                    <!-- Bills Subtotal -->
                    <tr style="background-color: #fafafa;">
                        <td colspan="3" class="text-end text-muted"><strong>Bills Subtotal:</strong></td>
                        <td class="text-end"><strong>₹<?= number_format($payout['bill_total'], 2) ?></strong></td>
                    </tr>

                    <!-- Adjustments Section -->
                    <?php if (!empty($adjustments)): ?>
                        <tr><td colspan="4" class="text-muted" style="background:#f1f3f5;"><strong>Adjustments</strong></td></tr>
                        <?php foreach ($adjustments as $adj): ?>
                            <tr>
                                <td colspan="3"><?= htmlspecialchars($adj['description'] ?: 'Manual Adjustment') ?></td>
                                <!-- <td class="text-center text-muted">-</td> -->
                                <td class="text-end <?= $adj['adj_type'] === '+' ? 'text-success' : 'text-danger' ?>">
                                    <?= $adj['adj_type'] ?> <?= number_format($adj['amount'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Grand Total -->
                    <tr class="row-total">
                        <td colspan="3" class="text-end">GRAND TOTAL:</td>
                        <td class="text-end text-success" style="font-size: 1.25rem;">₹<?= number_format($payout['total_payout'], 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php include 'view/layout/footer.php'; ?>