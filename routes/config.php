<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');      
define('DB_NAME', 'Gestion_de_project');


function getPDOConnectionDB($withDb = true) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ($withDb ? ";dbname=" . DB_NAME : "");
        $conn = new PDO($dsn, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    }
}


?>