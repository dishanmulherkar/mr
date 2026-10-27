<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Set the dynamic title for the header
$pageTitle = $mr_data['hq_name']; 

// 2. Include the top layout
include 'view/layout/header.php'; 
?>

<style>
    .btn-cancel {
        flex: 0 0 auto;
        background: var(--violet);
        color: #fff;
        border: none;
        padding: 10px 22px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: opacity .2s;
        white-space: nowrap;
    }

    /* Activity / Notification Feed Section */
    .dashboard-section {
        margin-top: 28px;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        border: 1px solid #edf2f7;
        overflow: hidden;
    }

    .section-header {
        padding: 18px 24px;
        border-bottom: 1px solid #edf2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .section-header h3 {
        margin: 0;
        font-size: 17px;
        font-weight: 700;
        color: #1a202c;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .feed-list {
        display: flex;
        flex-direction: column;
    }

    .feed-item {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        padding: 6px 18px;
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.15s ease;
    }

    .feed-item:last-child {
        border-bottom: none;
    }

    .feed-item:hover {
        background-color: #f8fafc;
    }

    .feed-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .feed-icon svg {
        width: 20px;
        height: 20px;
    }

    /* Dynamic badge & icon color styles */
    .theme-drc { background: #ede9fe; color: #7c3aed; }
    .theme-commission { background: #dcfce7; color: #16a34a; }
    .theme-approved { background: #e0f2fe; color: #0284c7; }
    .theme-rejected { background: #fee2e2; color: #dc2626; }
    .theme-default { background: #f3f4f6; color: #4b5563; }

    .feed-content {
        flex: 1;
    }

    .feed-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 4px;
    }

    .feed-title {
        font-size: 15px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .feed-tag {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 3px 8px;
        border-radius: 6px;
    }

    .feed-desc {
        font-size: 12px;
        color: #64748b;
        margin: 0 0 0px 0;
        line-height: 1.45;
    }

    .feed-time {
        font-size: 12px;
        color: #94a3b8;
    }

    .feed-empty {
        padding: 36px;
        text-align: center;
        color: #94a3b8;
        font-size: 14px;
    }

    /* PDF Download Button in Corner */
.feed-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}

.btn-pdf-download {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 6px;
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-pdf-download:hover {
    background: #dc2626;
    color: #ffffff;
    border-color: #dc2626;
}

.btn-pdf-download svg {
    width: 14px;
    height: 14px;
}

.feed-actions {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

/* PDF Download Button */
.btn-pdf-download {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
    transition: all 0.2s ease;
    text-decoration: none;
}
.btn-pdf-download:hover {
    background: #dc2626;
    color: #ffffff;
}
.btn-pdf-download svg {
    width: 14px;
    height: 14px;
}

/* Informative 'View' button */
.btn-view-details {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 6px;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    transition: all 0.2s ease;
}
.btn-view-details:hover {
    background: #0284c7;
    color: #ffffff;
    border-color: #0284c7;
}

/* Vanish Animation */
.feed-item.vanish-out {
    opacity: 0;
    transform: translateX(30px);
    max-height: 0;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    border: none !important;
    transition: opacity 0.3s ease, transform 0.3s ease, max-height 0.35s ease, padding 0.35s ease;
}
</style>

<div class="page-content">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat" style="display: flex; align-items: center; gap: 8px;">
                <div class="stat-icon violet">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <circle cx="12" cy="12" r="6"/>
                        <circle cx="12" cy="12" r="2"/>
                    </svg>
                </div>
                <div class="stat-label">Target</div>
            </div>
            <div class="stat-val"><?= number_format($target_amount ?? 0, 2); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat" style="display: flex; align-items: center; gap: 8px;">
                <div class="stat-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="stat-label">Primary Sales</div>
            </div>
            <div class="stat-val"><?= number_format($primary_sale ?? 0, 2); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat" style="display: flex; align-items: center; gap: 8px;">
                <div class="stat-icon orange">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="stat-label">Customers</div>
            </div>
            <div class="stat-val"><?= $total_customers ?? 0; ?></div>
        </div>

        <div class="stat-card">
            <div class="stat" style="display: flex; align-items: center; gap: 8px;">
                <div class="stat-icon green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
                        <polyline points="16 7 22 7 22 13"/>
                    </svg>
                </div>
                <div class="stat-label">Secondary Sales</div>
            </div>
            <div class="stat-val"><?= number_format($total_sales_amount ?? 0, 2); ?></div>
        </div>
    </div> 

    <!-- ================= NOTIFICATION & ACTIVITY SECTION ================= -->
    <div class="dashboard-section">
        <div class="section-header">
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                Recent Updates & Notifications
            </h3>
        </div>

            <div class="feed-list">
                <?php 
                    $items = [];

                    if (isset($activity_query) && $activity_query instanceof mysqli_result) {
                        while ($row = mysqli_fetch_assoc($activity_query)) {
                            $items[] = $row;
                        }
                    } elseif (!empty($recent_activities) && is_array($recent_activities)) {
                        $items = $recent_activities;
                    }

                    if (!empty($items)) {
                        foreach ($items as $item) {
                            $logId = (int)($item['id'] ?? $item['log_id'] ?? 0);
                            $type  = strtoupper((string)($item['activity_type'] ?? $item['type'] ?? ''));
                            $title = strtolower((string)($item['title'] ?? ''));
                            $msg   = strtolower((string)($item['message'] ?? ''));
                            $refId = (int)($item['reference_id'] ?? $item['order_id'] ?? 0);
                            $url   = (string)($item['url'] ?? '');

                            // Defaults
                            $themeClass  = 'theme-default';
                            $badgeText   = 'Activity';
                            $iconSvg     = '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>';
                            $isOrderFlow = false;

                            // 1. ORDER UNAPPROVED (Must be checked BEFORE approved)
                            if ($type === 'ORDER_UNAPPROVED' || str_contains($title, 'unapproved') || str_contains($type, 'UNAPPROVED')) {
                                $themeClass  = 'theme-rejected';
                                $badgeText   = 'Order Unapproved';
                                $iconSvg     = '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>';
                                $isOrderFlow = true;

                            // 2. ORDER APPROVED
                            } elseif ($type === 'ORDER_APPROVED' || (str_contains($title, 'order') && str_contains($title, 'approved') && !str_contains($title, 'unapproved'))) {
                                $themeClass  = 'theme-approved';
                                $badgeText   = 'Order Approved';
                                $iconSvg     = '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>';
                                $isOrderFlow = true;

                            // 3. ORDER UPDATED
                            } elseif ($type === 'ORDER_UPDATED' || str_contains($title, 'updated')) {
                                $themeClass  = 'theme-drc';
                                $badgeText   = 'Order Updated';
                                $iconSvg     = '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>';
                                $isOrderFlow = true;

                            // 4. GENERAL REJECTIONS
                            } elseif (str_contains($type, 'REJECT') || str_contains($title, 'reject') || str_contains($msg, 'reject')) {
                                $themeClass = 'theme-rejected';
                                $iconSvg    = '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>';

                                if (str_contains($type, 'ORDER') || str_contains($title, 'order')) {
                                    $badgeText   = 'Order Rejected';
                                    $isOrderFlow = true;
                                } elseif (str_contains($type, 'PAYMENT') || str_contains($title, 'payment')) {
                                    $badgeText = 'Payment Rejected';
                                } elseif (str_contains($type, 'COMMISSION') || str_contains($title, 'commission')) {
                                    $badgeText = 'Comm. Rejected';
                                } else {
                                    $badgeText = 'Rejected';
                                }

                            // 5. PAYMENT APPROVED
                            } elseif ($type === 'PAYMENT_APPROVED' || (str_contains($title, 'payment') && str_contains($title, 'approved') && !str_contains($title, 'unapproved'))) {
                                $themeClass = 'theme-approved';
                                $badgeText  = 'Payment Approved';
                                $iconSvg    = '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>';

                            // 6. MR COMMISSION APPROVED
                            } elseif ($type === 'MR_COMMISSION_APPROVED' || (str_contains($title, 'commission') && str_contains($title, 'mr'))) {
                                $themeClass = 'theme-commission';
                                $badgeText  = 'MR Commission';
                                $iconSvg    = '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>';

                            // 7. DOCTOR COMMISSION / DRC
                            } elseif ($type === 'DR_COMMISSION_APPROVED' || $type === 'DRC' || str_contains($title, 'drc') || str_contains($title, 'doctor')) {
                                $themeClass = 'theme-drc';
                                $badgeText  = 'Dr Commission';
                                $iconSvg    = '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>';
                            }

                            $timestamp = !empty($item['created_at']) ? strtotime($item['created_at']) : false;
                            $formattedDate = $timestamp ? date('d M Y, h:i A', $timestamp) : '';
                    ?>
                            <div class="feed-item" id="notif-item-<?= $logId; ?>">
                                <div class="feed-icon <?= htmlspecialchars($themeClass); ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <?= $iconSvg; ?>
                                    </svg>
                                </div>
                                <div class="feed-content">
                                    <div class="feed-top">
                                        <span class="feed-title"><?= htmlspecialchars($item['title'] ?? 'Notification'); ?></span>
                                        <div class="feed-actions">
                                            <span class="feed-tag <?= htmlspecialchars($themeClass); ?>"><?= htmlspecialchars($badgeText); ?></span>

                                            <?php if ($isOrderFlow && $refId > 0 && $themeClass !== 'theme-rejected'): ?>
                                                <!-- PDF Download (Only for valid/approved order events) -->
                                                <a href="<?= htmlspecialchars(BASE_URL . 'invoice/pdf/' . $refId); ?>" 
                                                target="_blank" 
                                                rel="noopener noreferrer"
                                                class="btn-pdf-download" 
                                                title="Download Invoice"
                                                onclick="dismissNotification(event, <?= $logId; ?>, true)">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                        <polyline points="7 10 12 15 17 10"></polyline>
                                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                                    </svg>
                                                </a>
                                            <?php else: ?>
                                                <!-- View Details -->
                                                <button type="button" 
                                                        class="btn-view-details" 
                                                        title="View Details"
                                                        onclick="dismissNotification(event, <?= $logId; ?>, false, <?= htmlspecialchars(json_encode($url), ENT_QUOTES, 'UTF-8'); ?>)">
                                                    View
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="feed-desc"><?= nl2br(htmlspecialchars($item['message'] ?? '')); ?></p>
                                    <?php if ($formattedDate): ?>
                                        <span class="feed-time"><?= $formattedDate; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                    <?php 
                        } 
                    } else { ?>
                        <div class="feed-empty">
                            No new activities or alerts found.
                        </div>
                    <?php } ?>
    </div>
    </div>
    <!-- ================= END NOTIFICATION SECTION ================= -->

</div>

<!-- Financial Year Expired -->
<?php
$today = date('Y-m-d');
if (!empty($end_date) && $today > $end_date) {
?>
<div id="fyPopup">
    <div class="popup-header">
        Financial Year Expired
        <span class="close-btn" onclick="document.getElementById('fyPopup').style.display='none'">&times;</span>
    </div>
    <div class="popup-body">
        <p>The financial year ended on <strong><?= date('d-m-Y', strtotime($end_date)); ?></strong>.</p>
        <p>activate a new financial year.</p>
    </div>
</div>
<?php } ?>

<!-- Notification POPUp -->
<?php
if (isset($notification_query) && mysqli_num_rows($notification_query) > 0) { 
    $seen_notification_ids = [];
?>
<div id="notificationModal" class="notification-modal">
    <div class="notification-box">
        <div class="notification-header">🔔 Notifications</div>
        <div class="notification-body">
            <?php 
            mysqli_data_seek($notification_query, 0); // Reset pointer if query was reused
            while ($n = mysqli_fetch_assoc($notification_query)) { 
                $seen_notification_ids[] = $n['notification_id']; 
            ?>
                <div class="notification-item">
                    <h5><?= htmlspecialchars($n['title']); ?></h5>
                    <p><?= nl2br(htmlspecialchars($n['message'])); ?></p>
                    <small><?= date('d M Y', strtotime($n['created_at'])); ?></small>
                </div>
            <?php } ?>
        </div>
        <div class="notification-footer">
            <input type="hidden" id="shownNotificationIds" value="<?= implode(',', $seen_notification_ids); ?>">
            <button id="closeNotification" class="btn-cancel">Close</button>
        </div>
    </div>
</div>
<?php } ?>

<?php include 'view/layout/footer.php'; ?>

<script>

    function dismissNotification(event, logId, isDownload = false, targetUrl = '') {
    if (!logId) return;

    // 1. Immediately animate card out of the list
    const card = document.getElementById('notif-item-' + logId);
    if (card) {
        card.classList.add('vanish-out');
        setTimeout(() => card.remove(), 350);
    }

    // 2. Inform the backend to mark it as read
    const payload = new FormData();
    payload.append('id', logId);

    if (navigator.sendBeacon) {
        navigator.sendBeacon('<?= BASE_URL ?>dashboard/markNotificationsSeen', payload);
    } else {
        fetch('<?= BASE_URL ?>dashboard/markNotificationsSeen', {
            method: 'POST',
            body: payload
        });
    }

    // If it's a redirect button with an actual URL, navigate after triggering dismissal
    if (!isDownload && targetUrl && targetUrl !== '') {
        setTimeout(() => {
            window.location.href = targetUrl;
        }, 150);
    }
}
document.addEventListener("DOMContentLoaded", function () {
    const btn = document.getElementById("closeNotification");
    if (btn) {
        btn.addEventListener("click", function () {
            let ids = document.getElementById("shownNotificationIds")?.value;
            fetch("<?= defined('BASE_URL') ? BASE_URL : '' ?>dashboard/markNotificationsSeen", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "ids=" + encodeURIComponent(ids)
            })
            .then(res => res.text())
            .then(() => {
                document.getElementById("notificationModal").style.display = "none";
            })
            .catch(err => console.error(err));
        });
    }
});
function dismissNotification(event, logId, isDownload = false, targetUrl = '') {
    if (!logId) return;

    // Slide and remove immediately from screen
    const card = document.getElementById('notif-item-' + logId);
    if (card) {
        card.classList.add('vanish-out');
        setTimeout(() => card.remove(), 350);
    }

    // Post to controller
    const payload = new FormData();
    payload.append('id', logId);

    if (navigator.sendBeacon) {
        navigator.sendBeacon('dashboard/markNotificationsSeen', payload);
    } else {
        fetch('dashboard/markNotificationsSeen', {
            method: 'POST',
            body: payload
        });
    }

    if (!isDownload && targetUrl && targetUrl !== '') {
        setTimeout(() => {
            window.location.href = targetUrl;
        }, 150);
    }
}
</script>