<?php

define('DB_HOST', getenv('PGHOST') ?: 'localhost');
define('DB_USER', getenv('PGUSER') ?: 'root');
define('DB_PASS', getenv('PGPASSWORD') ?: '');
define('DB_NAME', getenv('PGDATABASE') ?: 'inspiranet_db');
define('DB_PORT', getenv('PGPORT') ?: '5432');

function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}
