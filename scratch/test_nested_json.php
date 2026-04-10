<?php
require_once __DIR__ . '/../routes/config.php';
require_once __DIR__ . '/../controllers/Project.php';

$p = new Project();
$res = $p->show(1);
echo json_encode($res, JSON_PRETTY_PRINT);
