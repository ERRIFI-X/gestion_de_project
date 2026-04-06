<?php
require_once __DIR__ . '/../controllers/Auth.php';

$authController = new Auth();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;

    if ($action === 'register') {
        $result = $authController->register($data);
        if (!($result['success'] ?? false)) {
            http_response_code(400);
        }
        echo json_encode($result);
    } else if ($action === 'login') {
        $result = $authController->login($data);
        if (!($result['success'] ?? false)) {
            http_response_code(401);
        }
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid auth action.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}
