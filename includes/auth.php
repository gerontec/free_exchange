<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM lg_users WHERE user_id = :id AND aktiv = 1");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    return $stmt->fetch();
}

function loginUser($user_id) {
    global $pdo;
    
    $_SESSION['user_id'] = $user_id;
    $_SESSION['login_time'] = time();
    
    $session_id = session_id();
    $expires = date('Y-m-d H:i:s', time() + 86400 * 30);
    
    $stmt = $pdo->prepare("
        INSERT INTO lg_sessions (session_id, user_id, expires_at, ip_address, user_agent)
        VALUES (:sid, :uid, :exp, :ip, :ua)
        ON DUPLICATE KEY UPDATE expires_at = :exp2, user_id = :uid2
    ");
    $stmt->execute([
        ':sid' => $session_id,
        ':uid' => $user_id,
        ':exp' => $expires,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ':exp2' => $expires,
        ':uid2' => $user_id
    ]);
    
    $stmt = $pdo->prepare("UPDATE lg_users SET last_login = NOW() WHERE user_id = :id");
    $stmt->execute([':id' => $user_id]);
}

function logoutUser() {
    global $pdo;
    
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("DELETE FROM lg_sessions WHERE session_id = :sid");
        $stmt->execute([':sid' => session_id()]);
    }
    
    session_destroy();
    header('Location: index.php');
    exit;
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: login.php');
        exit;
    }
}
?>
