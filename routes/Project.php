<?php

require_once __DIR__ . '/../controllers/Project.php';

$projectController = new Project();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $result = $projectController->show($id);
        echo json_encode($result);
    } else {
        $result = $projectController->getAll();
        echo json_encode($result);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $result = $projectController->store($data);
    if (!$result['success']) {
        http_response_code(400);
    }
    echo json_encode($result);
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $result = $projectController->update($id, $data);
        if (!$result['success']) {
            http_response_code(400);
        }
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID is required for update.']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $result = $projectController->delete($id);
        if (!$result['success']) {
            http_response_code(400);
        }
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID is required for delete.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}






?>