<?php
require_once __DIR__ . '/../controllers/Clients.php';

$clientController = new Clients();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $id = htmlspecialchars(trim($_GET['id']));
        $result = $clientController->show($id);
        echo json_encode($result);
    } else {
        $result = $clientController->getAll();
        echo json_encode($result);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    if (isset($data['name']) && isset($data['phone']) && isset($data['email'])) {
        $name = htmlspecialchars(trim($data['name']));
        $phone = htmlspecialchars(trim($data['phone']));
        $email = htmlspecialchars(trim($data['email']));
        $result = $clientController->store($name, $phone, $email);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Données manquantes (name, phone, email requis)']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents("php://input"), $putData);
    if (isset($putData['id']) && isset($putData['name']) && isset($putData['phone']) && isset($putData['email'])) {
        $id = htmlspecialchars(trim($putData['id']));
        $name = htmlspecialchars(trim($putData['name']));
        $phone = htmlspecialchars(trim($putData['phone']));
        $email = htmlspecialchars(trim($putData['email']));
        $result = $clientController->update($id, $name, $phone, $email);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Données manquantes (id, name, phone, email requis)']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $deleteData);
    if (isset($deleteData['id'])) {
        $id = htmlspecialchars(trim($deleteData['id']));
        $result = $clientController->delete($id);
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




