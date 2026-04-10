<?php
require_once __DIR__ . '/../controllers/Notifications.php';

$controller = new NotificationsController();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['count']) && isset($_GET['user_id'])) {
        echo json_encode($controller->getUnreadCount((int)$_GET['user_id']));
    } else if (isset($_GET['user_id'])) {
        echo json_encode($controller->getByUser((int)$_GET['user_id']));
    } else {
        echo json_encode($controller->getAll());
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $result = $controller->create($data);
    if (!$result['success']) http_response_code(400);
    echo json_encode($result);
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    if (isset($_GET['id'])) {
        $result = $controller->markAsRead((int)$_GET['id']);
        echo json_encode($result);
    } else if (isset($_GET['mark_all']) && isset($_GET['user_id'])) {
        $result = $controller->markAllAsRead((int)$_GET['user_id']);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'ID or user_id is required']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (isset($_GET['all']) && isset($_GET['user_id'])) {
        $result = $controller->deleteAll((int)$_GET['user_id']);
        echo json_encode($result);
    } else if (isset($_GET['id'])) {
        $result = $controller->delete((int)$_GET['id']);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}
