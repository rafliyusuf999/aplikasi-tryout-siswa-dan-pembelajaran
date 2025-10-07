<?php

function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $database_url = getenv('DATABASE_URL');
            
            if ($database_url && trim($database_url) !== '') {
                $url_parts = parse_url($database_url);
                
                $host = $url_parts['host'] ?? 'localhost';
                $port = $url_parts['port'] ?? 5432;
                $user = $url_parts['user'] ?? '';
                $pass = $url_parts['pass'] ?? '';
                $dbname = ltrim($url_parts['path'] ?? '', '/');
                
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
                if (isset($url_parts['query'])) {
                    $params = [];
                    parse_str($url_parts['query'], $params);
                    if (isset($params['sslmode'])) {
                        $dsn .= ";sslmode={$params['sslmode']}";
                    }
                }
                
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } else {
                $host = getenv('PGHOST');
                $user = getenv('PGUSER');
                $pass = getenv('PGPASSWORD');
                $name = getenv('PGDATABASE');
                $port = getenv('PGPORT');
                
                if (!$host || trim($host) === '') $host = 'localhost';
                if (!$user || trim($user) === '') $user = 'postgres';
                if (!$pass || trim($pass) === '') $pass = '';
                if (!$name || trim($name) === '') $name = 'inspiranet_db';
                if (!$port || trim($port) === '') $port = '5432';
                
                $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            }
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}
