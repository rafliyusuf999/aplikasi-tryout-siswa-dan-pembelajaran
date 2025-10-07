<?php

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_token']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function login($user) {
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_token'] = bin2hex(random_bytes(32));
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
}

function logout() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['flash_message'] = 'Silakan login terlebih dahulu!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /login.php');
        exit;
    }
    
    if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
        logout();
        header('Location: /login.php');
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    $user = getCurrentUser();
    if ($user['role'] !== $role) {
        $_SESSION['flash_message'] = 'Akses ditolak!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /index.php');
        exit;
    }
}

function requireAnyRole($roles) {
    requireLogin();
    $user = getCurrentUser();
    if (!in_array($user['role'], $roles)) {
        $_SESSION['flash_message'] = 'Akses ditolak!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /index.php');
        exit;
    }
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
