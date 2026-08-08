<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'config/db.php';


// include 'config/Addons';
$url = trim($_GET['url'] ?? 'dashboard', '/');

$parts = explode('/', $url);

$page   = $parts[0] ?? 'dashboard';
$action = $parts[1] ?? 'index';
$id     = $parts[2] ?? null;



/*
|--------------------------------------------------------------------------
| Authentication Check
|--------------------------------------------------------------------------
*/

$publicRoutes = ['login'];

// Not logged in → allow only login pages
if (!isset($_SESSION['admin_id']) && !in_array($page, $publicRoutes)) {
    header("Location: " . BASE_URL . "login");
    exit;
}

// Already logged in → don't allow login page, but allow logout
if (
    isset($_SESSION['admin_id']) &&
    $page == 'login' &&
    $action != 'logout'
) {
    header("Location: " . BASE_URL . "dashboard");
    exit;
}


switch($page)
{
    case 'login':
        include 'controller/LoginController.php';

        $controller =
            new LoginController($con);

         if (method_exists($controller, $action)) {
        $controller->$action($id);
            } else {
                $controller->index();
            }

        break;

    case 'admin':

        include 'controller/AdminController.php';

        $controller =
            new AdminController($con);

         if (method_exists($controller, $action)) {
        $controller->$action($id);
            } else {
                $controller->index();
            }

        break;

    case 'mr':

        include 'controller/MrController.php';

        $controller =
            new MrController($con);

         if (method_exists($controller, $action)) {
        $controller->$action($id);
            } else {
                $controller->index();
            }

        break;

    case 'stockist':

        include 'controller/StockistController.php';

        $controller =
            new StockistController($con);

         if (method_exists($controller, $action)) {
        $controller->$action($id);
            } else {
                $controller->index();
            }

        break;

    case 'stock_inward':

        include 'controller/StockInwardController.php';

        $controller =
            new StockInwardController($con);

         if (method_exists($controller, $action)) {
        $controller->$action($id);
            } else {
                $controller->index();
            }

        break;

    case 'stock_adjustment':

        include 'controller/StockAdjustmentController.php';

        $controller =
            new StockAdjustmentController($con);

         if (method_exists($controller, $action)) {
        $controller->$action($id);
            } else {
                $controller->index();
            }

        break;

    case 'customer':

        include 'controller/CustomerController.php';

        $controller =
            new CustomerController($con);

         if (method_exists($controller, $action)) {
        $controller->$action($id);
            } else {
                $controller->index();
            }

        break;

    case 'district':

        include 'controller/DistrictController.php';

        $controller = new DistrictController($con);

         if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }

        exit;

    case 'state':

        include 'controller/StateController.php';

        $controller = new StateController($con);

        // $controller->getDistricts();
         if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }

        exit;

    case 'stock_and_sales_report':
        include 'controller/StockandSalesRepController.php';
        $controller = new StockandSalesRepController($con);
        if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }
        break;  
        
    case 'hq_customer_report':
        include 'controller/HqCustomerReportController.php';
        $controller = new HqCustomerReportController($con);
        if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }
        break;  


    case 'inward_report':
        include 'controller/InwardReportController.php';
        $controller = new InwardReportController($con);
        
        if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }
        break;   
        
    case 'State_wise_report':
        include 'controller/StateWiseReportController.php';
        $controller = new StateWiseReportController($con);
        
        if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }
        break;  


    case 'products':
        include 'controller/ProductController.php';
        $controller = new ProductController($con);
        if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }
        break;

    case 'product_batch':
        include 'controller/ProductBatchController.php';
        $controller = new BatchProductController($con);
        if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }
        break;

    case 'batch_status':
        include 'controller/BatchStatusController.php';
        $controller = new BatchStatusController($con);
        if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }
        break;    

    case 'notification':
        include 'controller/NotificationController.php';
        $controller = new NotificationController($con);
        if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }
        break;

    case 'financial':
        include 'controller/FinancialController.php';
        $controller = new FinancialController($con);
        if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }
        break;

    case 'supplier':

        include 'controller/SuperStockist_ctl.php';

        $controller = new SupplierController($con);

         if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }

        exit;    

    case 'purchase':

        include 'controller/PurchaseController.php';

        $controller = new PurchaseController($con);

         if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }

        exit;  

    case 'headquarter':

        include 'controller/HQMaster_ctl.php';

        $controller = new HeadquarterController($con);

         if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }

        exit;  

    case 'Order':

        include 'controller/Order_ctl.php';

        $controller = new OrderController($con);

         if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }

        exit;  

    case 'invoice':

        include '../Invoice/controller/invoice_ctl.php';

        $controller = new InvoiceController($con);

        if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }

        break;

    case 'dashboard':
    default:
        // Fixed folder name from 'controllers' to 'controller'
        include 'controller/DashboardController.php'; 
        
        // Passing $con so your dashboard can fetch database stats
        $controller = new DashboardController($con); 
        
        if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }
        break;
}