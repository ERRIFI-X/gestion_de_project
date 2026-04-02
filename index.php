<?php

header('Content-Type: application/json');

$page = isset($_GET['page']) ? $_GET['page'] : '';

switch ($page) {
    case 'database':
        require_once './models/migration.php';
        echo createTables();
        break;
    case 'clients':
        require_once './routes/Clients.php';
        break;
    case 'projects':
        require_once './routes/Project.php';
        break;
    case 'tasks':
        require_once './routes/Tasks.php';
        break;
    case 'tache':
        require_once './routes/Tache.php';
        break;
    default:
        echo json_encode(['message' => 'Bienvenue sur l\'API de gestion de projet']);
        break;
}


?>