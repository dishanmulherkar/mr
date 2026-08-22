<?php 
$pageTitle = "Commission List";
include 'view/layout/header.php';
?>
<style>
    .status-badge {
        color: #fff !important;
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 12px;
        font-weight: 600;
        display: inline-block;
        min-width: 60px;
        text-align: center;
    }
    .badge-pending {
        background: #ffc107;
        color: #212529 !important;
    }
    .badge-approved {
        background: #28a745;
        color: #fff !important;
    }
    
    /* --- Mobile Card View Styles --- */
    .commission-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .commission-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .card-header-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        border-bottom: 1px solid #f0f0f0;
        padding-bottom: 10px;
    }
    .card-date {
        color: #6c757d;
        font-size: 14px;
        font-weight: 500;
    }
    .card-body-flex {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }
    .card-amount-label {
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 2px;
    }
    .card-amount {
        color: #28a745;
        font-weight: bold;
        font-size: 1.25rem;
        margin: 0;
    }
    .list-wrapper {
        min-height: 250px;
        margin-top: 20px;
    }
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        background: #f8f9fa;
        border-radius: 8px;
        color: #6c757d;
    }
</style>
<link rel="stylesheet" href="<?= BASE_URL ?>config/config/salesentry.css">

<div class="page-content">
    <div class="page-header d-flex justify-content-between align-items-center mb-3">
        <h3>My drc</h3> Current Balance <?php echo $drc_balance;   ?>
    </div>

    <div class="filter-bar">
        <!-- <select id="stockist" class="filter-pill">
            <option value="">All Stockists</option>
            <?php foreach ($stockists as $s): ?>
                <option value="<?= $s['stockist_id'] ?>"><?= htmlspecialchars($s['stockist_name']) ?></option>
            <?php endforeach; ?>
        </select> -->

        <input type="date" id="from_date" class="filter-pill">

        <button class="btn-ok" id="btnSearch">Search</button>
        <button class="btn-ok" id="btnReset" style="background:#dc3545;">Reset</button>
    </div>

    <!-- Replaced Table with a Grid/List Wrapper -->
    <div class="list-wrapper">
        <div class="row" id="commissionList">
            <!-- Cards will be injected here by AJAX -->
            <div class="col-12">
                <div class="empty-state">
                    <i class="fa fa-spinner fa-spin fa-2x mb-2"></i>
                    <p>Loading commissions...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const mr_id   = <?= $mr_id ?>;
    const BASE_URL = "<?= BASE_URL ?>";
</script>

<?php include 'view/layout/footer.php'; ?>

<script>
$(document).ready(function () {
    
    function loadCommissions() {
        let stockistId = $('#stockist').val();
        let fromDate = $('#from_date').val();

        let loadingHtml = `
            <div class="col-12">
                <div class="empty-state">
                    <i class="fa fa-spinner fa-spin fa-2x mb-2"></i>
                    <p>Loading commissions...</p>
                </div>
            </div>`;
        $('#commissionList').html(loadingHtml);

        $.ajax({
            type: "GET",
            url: BASE_URL + "commission/get_drcom_data",
            data: {
                stockist_id: stockistId,
                from_date: fromDate
            },
            dataType: "json",
            success: function (res) {
                if(res.success && res.data.length > 0) {
                    let cards = '';
                    res.data.forEach(item => {
                        let badgeClass = item.status === 'Paid' ? 'badge-approved' : 'badge-pending';
                        
                        // Generating Card HTML instead of Table Rows
                        cards += `
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="commission-card">
                                    <div class="card-header-flex">
                                        <span class="card-date"><i class="fa fa-calendar-alt me-1"></i> ${item.date_paid}</span>
                                        <span class="status-badge ${badgeClass}">${item.status}</span>
                                    </div>
                                    <div class="card-body-flex">
                                        <div>
                                            <div class="card-amount-label">Total Payout</div>
                                            <div class="card-amount">₹${parseFloat(item.total_payout).toFixed(2)}</div>
                                        </div>
                                        <div>
                                            <a href="${BASE_URL}commission/drview/${item.payout_id}" class="btn btn-sm btn-info text-white">
                                                <i class="fa fa-eye"></i> View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#commissionList').html(cards);
                } else {
                    let emptyHtml = `
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="fa fa-folder-open fa-2x mb-2 text-muted"></i>
                                <p class="mb-0">${res.msg || "No commissions found."}</p>
                            </div>
                        </div>`;
                    $('#commissionList').html(emptyHtml);
                }
            },
            error: function() {
                let errorHtml = `
                    <div class="col-12">
                        <div class="empty-state text-danger">
                            <i class="fa fa-exclamation-triangle fa-2x mb-2"></i>
                            <p class="mb-0">Server error fetching data.</p>
                        </div>
                    </div>`;
                $('#commissionList').html(errorHtml);
            }
        });
    }

    // Trigger searches
    $('#btnSearch').click(loadCommissions);
    
    $('#btnReset').click(function() {
        $('#stockist').val('');
        $('#from_date').val('');
        loadCommissions();
    });

    // Initial Load
    loadCommissions();
});
</script>