<!-- <head><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head> -->

<nav class="sidebar" id="sidebar">
      <div class="sidebar-logo">
        
        <img src="<?= BASE_URL ?>config/img/logo.jpg" alt="rudradeo-logo" width="120" height="auto" />
      </div>

  <?php
$current_page = strtolower(explode('/', trim($_GET['url'] ?? 'dashboard', '/'))[0]);


// Get all segments of the URL
$url_segments = explode('/', trim($_GET['url'] ?? 'dashboard', '/'));

// First part (e.g., 'commission')
$current_controller = strtolower($url_segments[0]);

// Second part (e.g., 'dr_commision'). Defaults to empty string if it doesn't exist.
$current_action = isset($url_segments[1]) ? strtolower($url_segments[1]) : '';
?>

<div class="nav-section">Main</div>

<a href="<?= BASE_URL ?>dashboard"
   class="nav-item <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect></svg>
      Dashboard
</a>

<a href="<?= BASE_URL ?>customer"
   class="nav-item <?php echo ($current_page == 'customer') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
   Customers
</a>

<a href="<?= BASE_URL ?>SalesEntry/list"
   class="nav-item <?= ($current_page == 'salesentry') ? 'active' : ''; ?>">
     <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            </svg>
   Sales Entry
</a>

<a href="<?= BASE_URL ?>OrderEntry/view"
   class="nav-item <?= ($current_page == 'orderentry') ? 'active' : ''; ?>">
     <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
        <line x1="12" y1="11" x2="12" y2="17"></line>
        <line x1="9" y1="14" x2="15" y2="14"></line>
     </svg>
   Manage Order
</a>

<a href="<?= BASE_URL ?>payment"
  class="nav-item <?= ($current_page == 'payment') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
            <line x1="1" y1="10" x2="23" y2="10"></line>
        </svg>
    Manage Payment
</a>

<!-- Mr Commission -->
<a href="<?= BASE_URL ?>commission"
  class="nav-item <?= ($current_controller == 'commission' && ($current_action == '' || $current_action == 'index')) ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="10"></circle>
    <line x1="9" y1="15" x2="15" y2="9"></line>
    <circle cx="9.5" cy="9.5" r="1" fill="currentColor"></circle>
    <circle cx="14.5" cy="14.5" r="1" fill="currentColor"></circle>
    </svg>
    Mr Commission
</a>

<!-- Dr Commission -->
<a href="<?= BASE_URL ?>commission/dr_commision"
  class="nav-item <?= ($current_controller == 'commission' && $current_action == 'dr_commision') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="9" y1="15" x2="15" y2="9"></line>
        <circle cx="9.5" cy="9.5" r="1" fill="currentColor"></circle>
        <circle cx="14.5" cy="14.5" r="1" fill="currentColor"></circle>
    </svg>
    Dr Commission
</a>

<div class="nav-section">Reports</div>

<a href="<?= BASE_URL ?>SalesReport"
   class="nav-item <?php echo ($current_page == 'salesreport') ? 'active' : ''; ?>">
     <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
   Sales Report
</a>

<div class="nav-section">Settings</div>

<a href="<?= BASE_URL ?>account"
   class="nav-item <?php echo ($current_page == 'account') ? 'active' : ''; ?>">
     <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
   Account
</a>

<a href="<?= BASE_URL ?>login/logout" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        Log Out
</a>
      <div class="sidebar-footer">
        <a href="account.php" class="user-pill">
          <div class="avatar">MR</div>
          <div class="user-info">
            <div class="uname"><?php echo $_SESSION['mr_name']; ?></div>
            <div class="urole">Admin • Rudradeo</div>
          </div>
        </a>
      </div>
    </nav>