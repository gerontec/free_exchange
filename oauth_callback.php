<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/oauth_config.php';

if (empty($_GET['code'])) {
    die('Kein Authorization Code erhalten');
}

try {
    $token_data = getGoogleAccessToken($_GET['code']);
    
    if (!isset($token_data['access_token'])) {
        die('Fehler beim Abrufen des Access Tokens');
    }
    
    $user_info = getGoogleUserInfo($token_data['access_token']);
    
    if (!isset($user_info['id'])) {
        die('Fehler beim Abrufen der User-Info');
    }
    
    $oauth_id = $user_info['id'];
    $email = $user_info['email'];
    $name = $user_info['name'];
    $avatar = $user_info['picture'] ?? null;
    
    // User in DB suchen
    $stmt = $pdo->prepare("
        SELECT user_id FROM lg_users 
        WHERE oauth_provider = :provider AND oauth_id = :oauth_id
    ");
    $stmt->execute([
        ':provider' => 'google',
        ':oauth_id' => $oauth_id
    ]);
    $user = $stmt->fetch();
    
    if ($user) {
        $user_id = $user['user_id'];
        
        $stmt = $pdo->prepare("
            UPDATE lg_users 
            SET email = :email, name = :name, avatar_url = :avatar, last_login = NOW()
            WHERE user_id = :id
        ");
        $stmt->execute([
            ':email' => $email,
            ':name' => $name,
            ':avatar' => $avatar,
            ':id' => $user_id
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO lg_users (email, name, oauth_provider, oauth_id, avatar_url)
            VALUES (:email, :name, :provider, :oauth_id, :avatar)
        ");
        $stmt->execute([
            ':email' => $email,
            ':name' => $name,
            ':provider' => 'google',
            ':oauth_id' => $oauth_id,
            ':avatar' => $avatar
        ]);
        $user_id = $pdo->lastInsertId();
    }
    
    loginUser($user_id);
    
    $redirect = $_SESSION['redirect_after_login'] ?? 'meine_angebote.php';
    unset($_SESSION['redirect_after_login']);
    
    header('Location: ' . $redirect);
    exit;
    
} catch (Exception $e) {
    error_log('OAuth Error: ' . $e->getMessage());
    die('Login fehlgeschlagen. Bitte versuchen Sie es erneut.');
}
?>
