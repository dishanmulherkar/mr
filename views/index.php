<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'config/config/config.php';

require_once 'modals/AuthModel.php';
$authModel = new AuthModel($con);
// include 'config/Addons';
$url = trim($_GET['url'] ?? 'dashboard', '/');

$parts = explode('/', $url);
$current_page = strtolower(explode('/', trim($_GET['url'] ?? 'dashboard', '/'))[0]);
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
if (!isset($_SESSION['mr_id']) && !in_array($page, $publicRoutes)) {
    header("Location: " . BASE_URL . "login");
    exit;
}

// Already logged in → don't allow login page, but allow logout
if (
    isset($_SESSION['mr_id']) &&
    $page == 'login' &&
    $action != 'logout'
) {
    header("Location: " . BASE_URL . "dashboard");
    exit;
}


if (isset($_SESSION['mr_id']) && !$authModel->isUserActive($_SESSION['mr_id'])) {
    session_destroy();
    header("Location: " . BASE_URL . "login");
    exit;
}

switch($page)
{
    case 'login':

    include 'controller/LoginController.php';

    $controller = new LoginController($con);

    if (method_exists($controller, $action)) {
        $controller->$action();
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

    case 'AddCustomer':

        include 'controller/AddCustomerController.php';

        $controller =
            new AddCustomerController($con);

         if (method_exists($controller, $action)) {
        $controller->$action($id);
            } else {
                $controller->index();
            }

        break;    
    case 'SalesEntry':

        include 'controller/SalesEntryController.php';

        $controller = new SalesEntryController($con);

         if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }

        exit;

    case 'SalesReport':

        include 'controller/SalesReportController.php';

        $controller = new SalesReportController($con);

         if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }

        exit;

    case 'OrderEntry':

        include 'controller/OrderEntry_ctl.php';

        $controller = new orderentry_ctl($con);

            if (method_exists($controller, $action)) {
            $controller->$action($id);
        } else {
            $controller->index();
        }

        exit;

    case 'payment':

        include 'controller/Payment_manage_ctl.php';

        $controller = new payment_manage_ctl($con);

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