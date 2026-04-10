<?php
require_once __DIR__ . '/../controllers/Finance.php';

$financeController = new Finance();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $result = $financeController->getInvoice($id);
        echo json_encode($result);
    } else {
        $result = $financeController->getAllInvoices();
        echo json_encode($result);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $result = $financeController->createInvoice($data);
    if (!$result['success']) {
        http_response_code(400);
    }
    echo json_encode($result);
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    if (isset($_GET['id'])) {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $financeController->updateInvoice((int)$_GET['id'], $data);
        if (!$result['success']) http_response_code(400);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required for UPDATE']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (isset($_GET['id'])) {
        $result = $financeController->deleteInvoice((int)$_GET['id']);
        if (!$result['success']) http_response_code(400);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required for DELETE']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}
