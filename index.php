<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/controllers/Middleware.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// Handle preflight requests for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$page = isset($_GET['page']) ? $_GET['page'] : '';

// 1. Unprotected / Public Routes
if ($page === 'database') {
    require_once './models/migration.php';
    $result = createTables();
    if (php_sapi_name() === 'cli') {
        echo $result;
    } else {
        // Change header for HTML output
        header('Content-Type: text/html');
        echo "<pre>" . $result . "</pre>";
    }
    exit;
}

if ($page === 'auth') {
    require_once './routes/Auth.php';
    exit;
}

// 2. Protected Routes (Require JWT)
$userData = Middleware::authenticate();

switch ($page) {
    case 'clients':
        require_once './routes/Clients.php';
        break;
    case 'projects':
        require_once './routes/Project.php';
        break;
    case 'tasks':
        require_once './routes/Tasks.php';
        break;
    case 'dashboard':
        require_once './routes/Dashboard.php';
        break;
    case 'payments':
        require_once './routes/Payments.php';
        break;
    case 'invoices':
        require_once './routes/invoices.php';
        break;
    case 'notifications':
        require_once './routes/Notifications.php';
        break;
    case 'servers':
        require_once './routes/Servers.php';
        break;
    case 'system':
        require_once './routes/System.php';
        break;
    default:
        echo json_encode([
            'success' => true,
            'message' => 'Project Management API',
            'user' => $userData['username'] ?? 'Anonymous'
        ]);
        break;
}