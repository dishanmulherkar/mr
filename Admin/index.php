<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'config/db.php';

$url = trim($_GET['url'] ?? 'dashboard', '/');
$parts = explode('/', $url);

$page   = $parts[0] ?? 'dashboard';
$action = $parts[1] ?? 'index';
$id     = $parts[2] ?? null;

/*
|--------------------------------------------------------------------------
| Authentication & Role Setup
|--------------------------------------------------------------------------
*/

$publicRoutes = ['login'];

// Force the role to lowercase to prevent capitalization bugs
$userRole = strtolower($_SESSION['admin_role'] ?? ''); 

// 1. Not logged in → allow only public routes (login)
if (!isset($_SESSION['admin_id']) && !in_array($page, $publicRoutes)) {
    header("Location: " . BASE_URL . "login");
    exit;
}

// 2. Already logged in → redirect away from login page to their specific dashboard
if (isset($_SESSION['admin_id']) && $page == 'login' && $action != 'logout') {
    if ($userRole === 'dispatch') {
        header("Location: " . BASE_URL . "DispatchDashboard");
    } elseif ($userRole === 'asm') {
        header("Location: " . BASE_URL . "AsmDashboard");
    } else {
        header("Location: " . BASE_URL . "dashboard");
    }
    exit;
}

// 3. Auto-route generic "/dashboard" URL to correct mobile dashboards
if (isset($_SESSION['admin_id']) && $page == 'dashboard') {
    if ($userRole === 'dispatch') {
        $page = 'DispatchDashboard';
    } elseif ($userRole === 'asm') {
        $page = 'AsmDashboard';
    }
}

/*
|--------------------------------------------------------------------------
| Controller Routing
|--------------------------------------------------------------------------
*/

switch($page)
{
    case 'login':
        include 'controller/LoginController.php';
        $controller = new LoginController($con);
        break;

    // --- NEW MOBILE DASHBOARDS ---
    case 'AsmDashboard':
        include 'controller/AsmDashboardController.php';
        $controller = new AsmDashboardController($con);
        break;
    
    case 'Asm':
        include 'controller/AsmController.php';
        $controller = new AsmController($con);
        break;

    case 'DispatchDashboard':
        include 'controller/dispatch/DispatchDashboardController.php';
        $controller = new DispatchDashboardController($con);
        break;

    case 'admin':
        include 'controller/AdminController.php';
        $controller = new AdminController($con);
        break;

    case 'mr':
        include 'controller/MrController.php';
        $controller = new MrController($con);
        break;

    case 'stockist':
        include 'controller/StockistController.php';
        $controller = new StockistController($con);
        break;

    case 'stock_inward':
        include 'controller/StockInwardController.php';
        $controller = new StockInwardController($con);
        break;

    case 'stock_adjustment':
        include 'controller/StockAdjustmentController.php';
        $controller = new StockAdjustmentController($con);
        break;

    case 'customer':
        include 'controller/CustomerController.php';
        $controller = new CustomerController($con);
        break;

    case 'district':
        include 'controller/DistrictController.php';
        $controller = new DistrictController($con);
        break;

    case 'state':
        include 'controller/StateController.php';
        $controller = new StateController($con);
        break;

    case 'stock_and_sales_report':
        include 'controller/StockandSalesRepController.php';
        $controller = new StockandSalesRepController($con);
        break;  
        
    case 'hq_customer_report':
        include 'controller/HqCustomerReportController.php';
        $controller = new HqCustomerReportController($con);
        break;  

    case 'inward_report':
        include 'controller/InwardReportController.php';
        $controller = new InwardReportController($con);
        break;   
        
    case 'State_wise_report':
        include 'controller/StateWiseReportController.php';
        $controller = new StateWiseReportController($con);
        break;  

    case 'products':
        include 'controller/ProductController.php';
        $controller = new ProductController($con);
        break;

    case 'product_batch':
        include 'controller/ProductBatchController.php';
        $controller = new BatchProductController($con);
        break;

    case 'batch_status':
        include 'controller/BatchStatusController.php';
        $controller = new BatchStatusController($con);
        break;    

    case 'notification':
        include 'controller/NotificationController.php';
        $controller = new NotificationController($con);
        break;

    case 'financial':
        include 'controller/FinancialController.php';
        $controller = new FinancialController($con);
        break;

    case 'supplier':
        include 'controller/SuperStockist_ctl.php';
        $controller = new SupplierController($con);
        break;    

    case 'purchase':
        include 'controller/PurchaseController.php';
        $controller = new PurchaseController($con);
        break;  

    case 'headquarter':
        include 'controller/HQMaster_ctl.php';
        $controller = new HeadquarterController($con);
        break;  

    case 'Order':
        include 'controller/Order_ctl.php';
        $controller = new OrderController($con);
        break;  

    case 'invoice':
        include '../Invoice/controller/invoice_ctl.php';
        $controller = new InvoiceController($con);
        break;
    
    case 'payment':
        include 'controller/payment/Payment_ctl.php';
        $controller = new PaymentApproval_ctl($con);
        break;  
    
    case 'commision':
    include 'controller/commision/Commission_ctl.php';
    $controller = new CommissionController($con);
    break;  
    
    case 'drccommision':
    include 'controller/commision/drc_ctl.php';
    $controller = new drc_ctl($con);
    break;  

    case 'dashboard':
    default:
        include 'controller/DashboardController.php'; 
        $controller = new DashboardController($con); 
        break;
}

// Global Execution
if (method_exists($controller, $action)) {
    $controller->$action($id);
} else {
    $controller->index();
}