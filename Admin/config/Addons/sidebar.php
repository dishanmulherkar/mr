<style>
    .img-thumbnail {
        padding: .25rem;
        background-color: var(--bs-body-bg);
        border: 0px;
        border-radius: var(--bs-border-radius);
        max-width: 70%;
        height: auto;
        justify-content: center;
        margin-left: 25px;
    }
    .rounded-circle {
        border-radius: 0px !important;
    }
    .sidebar-logo {
        padding: 1.15rem 1.5rem;
        background-color: #fff;
    }
    .toggle-icon {
        transition: transform .3s ease;
    }
    .toggle-icon.rotate {
        transform: rotate(180deg);
    }
    /* Active Link Styling */
    .sidebar-nav .nav-link.active-menu {
        background-color: #0d6efd; /* Bootstrap Primary Blue */
        color: #fff !important;
        border-radius: 5px;
        font-weight: bold;
    }
    .sidebar-nav .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 5px;
    }
</style>

<aside id="sidebar">
    <div class="h-100 bg-dark">
        <div class="sidebar-logo">
            <a href="<?= BASE_URL ?>">
                <img src="<?= BASE_URL ?>config/image/logo.jpg" class="img-thumbnail rounded-circle" alt="Logo" width="500" height="236">
            </a>
        </div>
        <hr class="sidebar-divider my-0 mb-3 text-secondary">

        <!-- Search Bar -->
        <div class="px-3 pb-3">
            <input type="text" class="form-control form-control-sm bg-dark text-light border-secondary" id="sidebarSearch" placeholder="Search menu...">
        </div>

        <!-- sidebar navigation -->
        <ul class="sidebar-nav nav nav-pills flex-column mb-5 pb-5" id="menuList">
            <?php  $admin_role =  $_SESSION['admin_role'] ?? 'Admin'; ?>

            <?php if($admin_role == 'Super Admin'){ ?>
            <!-- ================= ADMIN ================= -->
            <li class="nav-item searchable-folder">
                <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#adminMenu" aria-expanded="false" href="#">
                    <span>
                        <i class="fa-solid fa-user-tie" style="width: 20px;"></i>
                        <span class="ms-2">Admin</span>
                    </span>
                    <i class="fa-solid fa-angle-down toggle-icon"></i>
                </a>
                <div class="collapse" id="adminMenu">
                    <ul class="nav flex-column ms-4 mt-1">
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>admin" class="nav-link text-light py-1">
                                <i class="fa-solid fa-user-gear" style="width: 20px;"></i> Manage Admin
                            </a>
                        </li>
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>permision" class="nav-link text-light py-1">
                                <i class="fa-solid fa-user-lock" style="width: 20px;"></i> Permission
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php } ?>

            <!-- ================= SUPER STOCKIST ================= -->
            <li class="nav-item searchable-folder">
                <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#superStockistMenu" aria-expanded="false" href="#">
                    <span>
                        <i class="fas fa-building" style="width: 20px;"></i>
                        <span class="ms-2">Super Stockist</span>
                    </span>
                    <i class="fa-solid fa-angle-down toggle-icon"></i>
                </a>
                <div class="collapse" id="superStockistMenu">
                    <ul class="nav flex-column ms-4 mt-1">
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>supplier" class="nav-link text-light py-1">
                                <i class="fa-solid fa-users-gear" style="width: 20px;"></i> Manage Super Stockists
                            </a>
                        </li>
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>purchase" class="nav-link text-light py-1">
                                <i class="fa-solid fa-cart-shopping" style="width: 20px;"></i> Purchase Entry
                            </a>
                        </li>
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>purchase/list" class="nav-link text-light py-1">
                                <i class="fa-solid fa-list-check" style="width: 20px;"></i> Purchase List
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

             <!-- ================= Head Quarter /  ================= -->
            <li class="nav-item searchable-folder">
                <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#networkMenu" aria-expanded="false" href="#">
                    <span>
                        <i class="fa-solid fa-network-wired" style="width: 20px;"></i>
                        <span class="ms-2">Head Quarter</span>
                    </span>
                    <i class="fa-solid fa-angle-down toggle-icon"></i>
                </a>
                <div class="collapse" id="networkMenu">
                    <ul class="nav flex-column ms-4 mt-1">
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>headquarter" class="nav-link text-light py-1">
                                <i class="fa-solid fa-building-flag" style="width: 20px;"></i> Manage HeadQuarter 
                            </a>
                        </li>
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>mr" class="nav-link text-light py-1">
                                <i class="fa-solid fa-building-flag" style="width: 20px;"></i> MR
                            </a>
                        </li>
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>customer" class="nav-link text-light py-1">
                                <i class="fa-solid fa-users" style="width: 20px;"></i> Customers
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
             
            <!-- ================= STOCKIST ================= -->
            <li class="nav-item searchable-folder">
                <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#stockistMenu" aria-expanded="false" href="#">
                    <span>
                        <i class="fa-solid fa-boxes-stacked" style="width: 20px;"></i>
                        <span class="ms-2">Stockist</span>
                    </span>
                    <i class="fa-solid fa-angle-down toggle-icon"></i>
                </a>
                <div class="collapse" id="stockistMenu">
                    <ul class="nav flex-column ms-4 mt-1">
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>stockist" class="nav-link text-light py-1">
                                <i class="fa-solid fa-users-viewfinder" style="width: 20px;"></i> Manage Stockists
                            </a>
                        </li>
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>stock_inward" class="nav-link text-light py-1">
                                <i class="fa-solid fa-cubes-stacked" style="width: 20px;"></i> Stock Inward
                            </a>
                        </li>
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>stock_adjustment" class="nav-link text-light py-1">
                                <i class="fa-solid fa-plus-minus" style="width: 20px;"></i> Stock Adjustment
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

             <!-- ================= Order ================= -->
            <li class="nav-item searchable-folder">
                <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#orderMenu" aria-expanded="false" href="#">
                    <span>
                        <i class="fa-solid fa-boxes-stacked" style="width: 20px;"></i>
                        <span class="ms-2">Order</span>
                    </span>
                    <i class="fa-solid fa-angle-down toggle-icon"></i>
                </a>
                <div class="collapse" id="orderMenu">
                    <ul class="nav flex-column ms-4 mt-1">
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>Order" class="nav-link text-light py-1">
                                <i class="fa-solid fa-users-viewfinder" style="width: 20px;"></i> Manage Orders
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- ================= INVENTORY ================= -->
            <li class="nav-item searchable-folder">
                <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#inventoryMenu" aria-expanded="false" href="#">
                    <span>
                        <i class="fa-solid fa-warehouse" style="width: 20px;"></i>
                        <span class="ms-2">Inventory</span>
                    </span>
                    <i class="fa-solid fa-angle-down toggle-icon"></i>
                </a>
                <div class="collapse" id="inventoryMenu">
                    <ul class="nav flex-column ms-4 mt-1">
                        <?php if($admin_role == 'Super Admin'){ ?>
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>products" class="nav-link text-light py-1">
                                <i class="fa-solid fa-box" style="width: 20px;"></i> Product Master
                            </a>
                        </li>
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>product_batch" class="nav-link text-light py-1">
                                <i class="fa-solid fa-layer-group" style="width: 20px;"></i> Batch Wise Product
                            </a>
                        </li>
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>batch_status" class="nav-link text-light py-1">
                                <i class="fa-solid fa-calendar-check" style="width: 20px;"></i> Update Batch
                            </a>
                        </li>
                        <?php } ?>
                    </ul>
                </div>
            </li>

           

            <!-- ================= REPORTS ================= -->
            <li class="nav-item searchable-folder">
                <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#reportMenu" aria-expanded="false" href="#">
                    <span>
                        <i class="fa-solid fa-chart-line" style="width: 20px;"></i>
                        <span class="ms-2">Reports</span>
                    </span>
                    <i class="fa-solid fa-angle-down toggle-icon"></i>
                </a>
                <div class="collapse" id="reportMenu">
                    <ul class="nav flex-column ms-4 mt-1">
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>stock_and_sales_report" class="nav-link text-light py-1">
                                <i class="fa-solid fa-file-invoice-dollar" style="width: 20px;"></i> Stock & Sales
                            </a>
                        </li>
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>hq_customer_report" class="nav-link text-light py-1">
                                <i class="fa-solid fa-user-doctor" style="width: 20px;"></i> Customer Report
                            </a>
                        </li>
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>inward_report" class="nav-link text-light py-1">
                                <i class="fa-solid fa-file-arrow-down" style="width: 20px;"></i> Inward Report
                            </a>
                        </li>
                        <?php if($admin_role == 'Super Admin'){ ?>
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>State_wise_report" class="nav-link text-light py-1">
                                <i class="fa-solid fa-map-location-dot" style="width: 20px;"></i> State Wise Report
                            </a>
                        </li>
                        <?php } ?>
                    </ul>
                </div>
            </li>

            <!-- ================= LOCATION ================= -->
            <li class="nav-item searchable-folder">
                <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#locationMenu" aria-expanded="false" href="#">
                    <span>
                        <i class="fa-solid fa-location-dot" style="width: 20px;"></i>
                        <span class="ms-2">Location</span>
                    </span>
                    <i class="fa-solid fa-angle-down toggle-icon"></i>
                </a>
                <div class="collapse" id="locationMenu">
                    <ul class="nav flex-column ms-4 mt-1">
                        <?php if($admin_role == 'Super Admin'){ ?>
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>state" class="nav-link text-light py-1">
                                <i class="fa-solid fa-map" style="width: 20px;"></i> State
                            </a>
                        </li>
                        <?php } ?>
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>district" class="nav-link text-light py-1">
                                <i class="fa-solid fa-city" style="width: 20px;"></i> District
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- ================= SETTINGS / OTHER ================= -->
            <li class="nav-item searchable-folder">
                <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#settingsMenu" aria-expanded="false" href="#">
                    <span>
                        <i class="fa-solid fa-gears" style="width: 20px;"></i>
                        <span class="ms-2">Settings</span>
                    </span>
                    <i class="fa-solid fa-angle-down toggle-icon"></i>
                </a>
                <div class="collapse" id="settingsMenu">
                    <ul class="nav flex-column ms-4 mt-1">
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>notification" class="nav-link text-light py-1">
                                <i class="fa-solid fa-bell" style="width: 20px;"></i> Notification
                            </a>
                        </li>
                        <li class="nav-item searchable-item">
                            <a href="<?= BASE_URL ?>financial" class="nav-link text-light py-1">
                                <i class="fa-solid fa-calendar-days" style="width: 20px;"></i> Financial
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</aside>

<!-- Main component -->
<div class="main">
    <nav class="navbar px-3 bg-dark border-bottom navbar-fixed-top">
        <button class="btn btn-light" type="button" data-bs-theme="light">
            <span class="navbar-toggler-icon"></span>
        </button>
        <a href="<?= BASE_URL ?>" style="text-decoration: none;">
            <h5 class="admin-txt mb-0" style="color:#fff;"> 
                <?php if($admin_role == 'Super Admin'){ ?>
                    <?= $admin_role; ?>
                <?php } else { ?>
                    <?= $admin_role . " &nbsp;|&nbsp; " . ($_SESSION['admin_username'] ?? ''); ?>
                <?php } ?>
            </h5>
        </a>
    </nav>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // 1. Arrow Icon Toggle Logic
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(btn){
        const icon = btn.querySelector('.toggle-icon');
        const targetSelector = btn.getAttribute('data-bs-target');
        const target = document.querySelector(targetSelector);

        if (!icon || !target) return;

        target.addEventListener('show.bs.collapse', function () {
            icon.classList.remove('fa-angle-down');
            icon.classList.add('fa-angle-up');
        });

        target.addEventListener('hide.bs.collapse', function () {
            icon.classList.remove('fa-angle-up');
            icon.classList.add('fa-angle-down');
        });
    });

    // 2. Active State Logic (Auto-open current tab)
    // Get the current URL without any ?id= parameters
    const currentUrl = window.location.href.split('?')[0]; 
    
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
        if (link.href.split('?')[0] === currentUrl && !link.hasAttribute('data-bs-toggle')) {
            // Highlight the link
            link.classList.add('active-menu');
            link.classList.remove('text-light');
            
            // Find parent collapse container and open it
            let parentCollapse = link.closest('.collapse');
            if (parentCollapse) {
                parentCollapse.classList.add('show');
                
                // Flip the arrow icon on the parent folder
                let parentFolderBtn = document.querySelector('[data-bs-target="#' + parentCollapse.id + '"]');
                if (parentFolderBtn) {
                    parentFolderBtn.setAttribute('aria-expanded', 'true');
                    let icon = parentFolderBtn.querySelector('.toggle-icon');
                    if (icon) {
                        icon.classList.remove('fa-angle-down');
                        icon.classList.add('fa-angle-up');
                    }
                }
            }
        }
    });

    // 3. Sidebar Search Filter Logic
    const searchInput = document.getElementById('sidebarSearch');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            let folders = document.querySelectorAll('.searchable-folder');

            folders.forEach(folder => {
                let items = folder.querySelectorAll('.searchable-item');
                let folderMatches = false;

                items.forEach(item => {
                    let text = item.textContent.toLowerCase();
                    if (text.includes(filter)) {
                        item.style.display = '';
                        folderMatches = true;
                    } else {
                        item.style.display = 'none';
                    }
                });

                // If searching, force open folders that have matches. If cleared, revert.
                let collapseDiv = folder.querySelector('.collapse');
                
                if (filter !== '') {
                    if (folderMatches) {
                        folder.style.display = '';
                        if (collapseDiv) collapseDiv.classList.add('show');
                    } else {
                        folder.style.display = 'none';
                    }
                } else {
                    folder.style.display = '';
                    // Collapse all except the one containing the active menu
                    if (collapseDiv && !collapseDiv.querySelector('.active-menu')) {
                        collapseDiv.classList.remove('show');
                    }
                }
            });
        });
    }
});
</script>