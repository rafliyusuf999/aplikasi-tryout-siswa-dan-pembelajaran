<?php

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.cookie_samesite', 'Lax');

session_start();

define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/public/storage/uploads/');
define('MAX_FILE_SIZE', 16 * 1024 * 1024);

define('BRANCHES', [
    'Inspiranet_Cakrawala 1',
    'Inspiranet_Cakrawala 2',
    'Inspiranet_Cakrawala 3',
    'Inspiranet_Cakrawala 4'
]);

define('CLASS_LEVELS', ['10', '11', '12', 'Alumni']);

date_default_timezone_set('Asia/Jakarta');

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/auth.php';
require_once BASE_PATH . '/config/helpers.php';
