<?php
require_once __DIR__ . '/../controllers/Dashboard.php';

$dashboardController = new Dashboard();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $dashboardController->getSummary();
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
}
?>
