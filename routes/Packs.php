<?php

require_once __DIR__ . '/../controllers/Pack.php';

$packController = new Pack();

header('Content-Type: application/json');

/**
 * Routes:
 *
 * PACKS:
 *   GET    ?page=packs                          → all packs
 *   GET    ?page=packs&id=1                     → single pack (with services)
 *   POST   ?page=packs                          → create pack (+ optional services[])
 *   PUT    ?page=packs&id=1                     → update pack
 *   DELETE ?page=packs&id=1                     → delete pack
 *
 * PACK SERVICES:
 *   GET    ?page=packs&pack_id=1&services=1     → get services of a pack
 *   GET    ?page=packs&service_id=1             → single service
 *   POST   ?page=packs&pack_id=1&services=1     → add service to pack
 *   PUT    ?page=packs&service_id=1             → update service
 *   DELETE ?page=packs&service_id=1             → delete service
 */

$isServices = isset($_GET['services']);
$serviceId  = isset($_GET['service_id']) ? (int)$_GET['service_id'] : null;
$packId     = isset($_GET['pack_id'])    ? (int)$_GET['pack_id']    : null;
$packIdGet  = isset($_GET['id'])         ? (int)$_GET['id']         : null;

$method = $_SERVER['REQUEST_METHOD'];

// ──────────────────────────────────────────
// SERVICE SUB-RESOURCE routes
// ──────────────────────────────────────────
if ($isServices || $serviceId) {

    if ($method === 'GET') {
        if ($serviceId) {
            // single service
            echo json_encode($packController->showService($serviceId));
        } elseif ($packId) {
            // all services for a pack
            echo json_encode($packController->getServicesByPack($packId));
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'pack_id or service_id required.']);
        }

    } elseif ($method === 'POST') {
        if (!$packId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'pack_id required.']);
        } else {
            $data   = json_decode(file_get_contents("php://input"), true) ?? $_POST;
            $result = $packController->storeService($packId, $data);
            if (!$result['success']) http_response_code(400);
            echo json_encode($result);
        }

    } elseif ($method === 'PUT') {
        if (!$serviceId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'service_id required.']);
        } else {
            $data   = json_decode(file_get_contents("php://input"), true);
            $result = $packController->updateService($serviceId, $data);
            if (!$result['success']) http_response_code(400);
            echo json_encode($result);
        }

    } elseif ($method === 'DELETE') {
        if (!$serviceId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'service_id required.']);
        } else {
            $result = $packController->deleteService($serviceId);
            if (!$result['success']) http_response_code(400);
            echo json_encode($result);
        }

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
    }

    exit;
}

// ──────────────────────────────────────────
// PACKS main resource routes
// ──────────────────────────────────────────
if ($method === 'GET') {
    if ($packIdGet) {
        echo json_encode($packController->show($packIdGet));
    } else {
        echo json_encode($packController->getAll());
    }

} elseif ($method === 'POST') {
    $data   = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $result = $packController->store($data);
    if (!$result['success']) http_response_code(400);
    echo json_encode($result);

} elseif ($method === 'PUT') {
    if (!$packIdGet) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID required for update.']);
    } else {
        $data   = json_decode(file_get_contents("php://input"), true);
        $result = $packController->update($packIdGet, $data);
        if (!$result['success']) http_response_code(400);
        echo json_encode($result);
    }

} elseif ($method === 'DELETE') {
    if (!$packIdGet) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID required for delete.']);
    } else {
        $result = $packController->delete($packIdGet);
        if (!$result['success']) http_response_code(400);
        echo json_encode($result);
    }

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}
?>
