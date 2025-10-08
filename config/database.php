<?php

function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $db_file = __DIR__ . '/../storage/database.sqlite';
            $db_dir = dirname($db_file);
            
            if (!file_exists($db_dir)) {
                mkdir($db_dir, 0755, true);
            }
            
            $is_new_db = !file_exists($db_file);
            
            $pdo = new PDO("sqlite:{$db_file}", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            $pdo->exec('PRAGMA foreign_keys = ON;');
            $pdo->exec('PRAGMA journal_mode = WAL;');
            
            if ($is_new_db) {
                initializeDatabase($pdo);
            }
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

function initializeDatabase($pdo) {
    $schema = file_get_contents(__DIR__ . '/schema_sqlite.sql');
    $pdo->exec($schema);
}
