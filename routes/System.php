<?php
require_once __DIR__ . '/../controllers/System.php';

$systemController = new System();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'logs';
    
    if ($action === 'logs') {
        echo json_encode($systemController->getLogs());
    } else if ($action === 'notifications') {
        $userId = (int)($_GET['user_id'] ?? 0);
        echo json_encode($systemController->getNotifications($userId));
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid system action.']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    if ($action === 'read_notification') {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = (int)($data['id'] ?? 0);
        echo json_encode(['success' => $systemController->markAsRead($id)]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid system post action.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}
