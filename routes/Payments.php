<?php
require_once __DIR__ . '/../controllers/Finance.php';

$financeController = new Finance();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $financeController->getAllPayments();
    echo json_encode($result);
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $result = $financeController->recordPayment($data);
    if (!$result['success']) {
        http_response_code(400);
    }
    echo json_encode($result);
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $result = $financeController->deletePayment($id);
        if (!$result['success']) {
            http_response_code(400);
        }
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID is required.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}
