<?php
// index.php - Front Controller / Router

// Composer autoloader (dompdf and future packages)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/app/middleware/AuthMiddleware.php';

// Parse Request URI — decode %20 etc. to handle any URL encoding
$requestUri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$basePath = BASE_URL;

// Remove base path prefix from URI if present
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

$uri = '/' . trim($requestUri, '/');

// Simple Routing Table
switch ($uri) {
    case '/':
    case '/login':
        require_once __DIR__ . '/app/controllers/AuthController.php';
        $controller = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->login();
        } else {
            $controller->showLogin();
        }
        break;

    case '/logout':
        require_once __DIR__ . '/app/controllers/AuthController.php';
        (new AuthController())->logout();
        break;

    case '/api':
        AuthMiddleware::checkLoggedIn();
        require_once __DIR__ . '/app/controllers/AjaxController.php';
        (new AjaxController())->handle();
        break;

    case '/dashboard':
        AuthMiddleware::checkLoggedIn();
        require_once __DIR__ . '/app/controllers/DashboardController.php';
        (new DashboardController())->index();
        break;

    case '/customers':
        AuthMiddleware::checkLoggedIn();
        require_once __DIR__ . '/app/controllers/CustomerController.php';
        $controller = new CustomerController();
        if (isset($_GET['action']) && $_GET['action'] === 'view') {
            $controller->profile();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'create') {
            $controller->create();
        } else {
            $controller->index();
        }
        break;

    case '/meters':
        AuthMiddleware::checkRole(['Administrator', 'Billing Staff']);
        require_once __DIR__ . '/app/controllers/MeterController.php';
        $controller = new MeterController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->store();
        } else {
            $controller->index();
        }
        break;

    case '/billing':
        AuthMiddleware::checkRole(['Administrator', 'Billing Staff']);
        require_once __DIR__ . '/app/controllers/BillingController.php';
        $controller = new BillingController();
        if (isset($_GET['action']) && $_GET['action'] === 'generate') {
            $controller->generateBatch();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'view') {
            $controller->viewBill();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'apply-penalties') {
            $controller->applyPenalties();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'download-pdf') {
            $controller->downloadPdf();
        } else {
            $controller->index();
        }
        break;

    case '/delinquents/notice':
        AuthMiddleware::checkRole(['Administrator', 'Billing Staff', 'Manager']);
        require_once __DIR__ . '/app/controllers/DelinquentController.php';
        (new DelinquentController())->notice();
        break;

    case '/payments':
        AuthMiddleware::checkRole(['Administrator', 'Cashier']);
        require_once __DIR__ . '/app/controllers/PaymentController.php';
        $controller = new PaymentController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->processPayment();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'receipt') {
            $controller->viewReceipt();
        } else {
            $controller->index();
        }
        break;

    case '/delinquents':
        AuthMiddleware::checkRole(['Administrator', 'Billing Staff', 'Manager']);
        require_once __DIR__ . '/app/controllers/DelinquentController.php';
        (new DelinquentController())->index();
        break;

    case '/reports':
        AuthMiddleware::checkRole(['Administrator', 'Manager']);
        require_once __DIR__ . '/app/controllers/ReportController.php';
        (new ReportController())->index();
        break;

    case '/employees':
        AuthMiddleware::checkRole(['Administrator']);
        require_once __DIR__ . '/app/controllers/EmployeeController.php';
        $controller = new EmployeeController();
        $action = $_GET['action'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            match ($action) {
                'toggle'         => $controller->toggleStatus(),
                'reset-password' => $controller->resetPassword(),
                'delete'         => $controller->delete(),
                default          => $controller->store(),
            };
        } else {
            $controller->index();
        }
        break;

    case '/profile':
        AuthMiddleware::checkLoggedIn();
        require_once __DIR__ . '/app/controllers/ProfileController.php';
        $controller = new ProfileController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->changePassword();
        } else {
            $controller->index();
        }
        break;

    case '/customers/toggle':
        AuthMiddleware::checkRole(['Administrator', 'Billing Staff']);
        require_once __DIR__ . '/app/controllers/CustomerController.php';
        (new CustomerController())->toggleStatus();
        break;

    case '/documents/upload':
        AuthMiddleware::checkRole(['Administrator', 'Billing Staff']);
        require_once __DIR__ . '/app/controllers/DocumentController.php';
        (new DocumentController())->upload();
        break;

    case '/documents/download':
        AuthMiddleware::checkLoggedIn();
        require_once __DIR__ . '/app/controllers/DocumentController.php';
        (new DocumentController())->download();
        break;

    case '/documents/delete':
        AuthMiddleware::checkRole(['Administrator', 'Billing Staff']);
        require_once __DIR__ . '/app/controllers/DocumentController.php';
        (new DocumentController())->delete();
        break;

    case '/audit-logs':
        AuthMiddleware::checkRole(['Administrator']);
        require_once __DIR__ . '/app/controllers/AuditLogController.php';
        (new AuditLogController())->index();
        break;

    case '/settings/backup':
        AuthMiddleware::checkRole(['Administrator']);
        require_once __DIR__ . '/app/controllers/SettingController.php';
        (new SettingController())->backup();
        break;

    case '/settings/restore':
        AuthMiddleware::checkRole(['Administrator']);
        require_once __DIR__ . '/app/controllers/SettingController.php';
        (new SettingController())->restore();
        break;

    case '/settings':
        AuthMiddleware::checkRole(['Administrator']);
        require_once __DIR__ . '/app/controllers/SettingController.php';
        $controller = new SettingController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->update();
        } else {
            $controller->index();
        }
        break;

    default:
        http_response_code(404);
        $pageTitle = '404 – Page Not Found';
        require_once __DIR__ . '/views/errors/404.php';
        break;
}
