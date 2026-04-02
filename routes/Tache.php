<?php

require_once __DIR__ . '/../controllers/Tache.php';

$tachController = new Tache();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $result = $tachController->show($id);
        echo json_encode($result);
    } else {
        $result = $tachController->getAll();
        echo json_encode($result);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['name'], $data['description'], $data['status'], $data['task_id'])) {
        $name = $data['name'];
        $description = $data['description'];
        $status = $data['status'];
        $task_id = $data['task_id'];
        $result = $tachController->store($name, $description, $status, $task_id);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Données manquantes (name, description, status, task_id requis)']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['name'], $data['description'], $data['status'], $data['task_id'])) {
            $name = $data['name'];
            $description = $data['description'];
            $status = $data['status'];
            $task_id = $data['task_id'];
            $result = $tachController->update($id, $name, $description, $status, $task_id);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Données manquantes (name, description, status, task_id requis)']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'ID de la tâche manquant dans l\'URL']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $result = $tachController->delete($id);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['error' =>
            'ID de la tâche manquant dans l\'URL']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
}










?>