<?php

require_once __DIR__ . '/../controllers/Project.php';

$projectController = new Project();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $id = htmlspecialchars(trim($_GET['id']));
        $result = $projectController->show($id);
        echo json_encode($result);
    } else {
        $result = $projectController->getAll();
        echo json_encode($result);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    if (isset($data['name']) && isset($data['description']) && isset($data['start_date']) && isset($data['end_date']) && isset($data['client_id'])) {
        $name = htmlspecialchars(trim($data['name']));
        $description = htmlspecialchars(trim($data['description']));
        $start_date = htmlspecialchars(trim($data['start_date']));
        $end_date = htmlspecialchars(trim($data['end_date']));
        $client_id = htmlspecialchars(trim($data['client_id']));
        $result = $projectController->store($name, $description, $start_date, $end_date, $client_id);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Données manquantes Data']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents("php://input"), $putData);
    if (isset($putData['id']) && isset($putData['name']) && isset($putData['description']) && isset($putData['start_date']) && isset($putData['end_date']) && isset($putData['client_id'])) {
        $id = htmlspecialchars(trim($putData['id']));
        $name = htmlspecialchars(trim($putData['name']));
        $description = htmlspecialchars(trim($putData['description']));
        $start_date = htmlspecialchars(trim($putData['start_date']));
        $end_date = htmlspecialchars(trim($putData['end_date']));
        $client_id = htmlspecialchars(trim($putData['client_id']));
        $result = $projectController->update($id, $name, $description, $start_date, $end_date, $client_id);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode
        (['error' => 'Données manquantes Data']);
    }  

} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $deleteData);       
    if (isset($deleteData['id'])) {
        $id = htmlspecialchars(trim($deleteData['id']));
        $result = $projectController->delete($id);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'ID manquant']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
}










?>