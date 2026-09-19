<!-- <head><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head> -->

<nav class="sidebar" id="sidebar">
      <div class="sidebar-logo">
        
        <img src="<?= BASE_URL ?>config/image/logo.jpg" alt="rudradeo-logo" width="120" height="auto" />
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




<a href="<?= BASE_URL ?>Order/asm_order_list"
   class="nav-item <?= ($current_page == 'order') ? 'active' : ''; ?>">
     <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
        <line x1="12" y1="11" x2="12" y2="17"></line>
        <line x1="9" y1="14" x2="15" y2="14"></line>
     </svg>
   Order 
</a>
<a href="<?= BASE_URL ?>asmcommision/dr_commision"
   class="nav-item <?= ($current_page == 'asmcommision') ? 'active' : ''; ?>">
     <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
        <line x1="12" y1="11" x2="12" y2="17"></line>
        <line x1="9" y1="14" x2="15" y2="14"></line>
     </svg>
   Asm Commision
</a>







<div class="nav-section">Reports</div>

<a href="#"
   class="nav-item <?php echo ($current_page == 'salesreport') ? 'active' : ''; ?>">
     <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
   -----
</a>

<a href="<?= BASE_URL ?>payment/asm_pay_ledger"
   class="nav-item <?= ($current_controller == 'payment' && ($current_action == 'asm_pay_ledger' || $current_action == 'asm_pay_ledger')) ? 'active' : ''; ?>">
     <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
   Payment Ledger
</a>
<a href="<?= BASE_URL ?>payment_ledger/asm_ledger"
   class="nav-item <?= ($current_controller == 'payment_ledger' && $current_action == 'asm_ledger') ? 'active' : ''; ?>">
     <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
   Asm commission
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
          <div class="avatar">Asm</div>
          <div class="user-info">
            <div class="uname"><?php echo $_SESSION['admin_name']; ?></div>
            <div class="urole">Asm • Rudradeo</div>
          </div>
        </a>
      </div>
    </nav>