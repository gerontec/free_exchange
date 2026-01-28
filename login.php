<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/oauth_config.php';

$pageTitle = 'Anmelden';

if (isLoggedIn()) {
    header('Location: meine_angebote.php');
    exit;
}

$googleAuthUrl = getGoogleAuthUrl();

include 'includes/header.php';
?>

<div style="max-width: 500px; margin: 50px auto;">
    <div class="card">
        <h2 style="text-align: center; margin-bottom: 30px;">🔐 Anmelden</h2>
        
        <p style="text-align: center; color: #7f8c8d; margin-bottom: 30px;">
            Melden Sie sich an, um Ihre Angebote und Nachfragen zu verwalten.
        </p>
        
        <a href="<?= htmlspecialchars($googleAuthUrl) ?>" 
           class="btn btn-success" 
           style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 16px; padding: 15px;">
            <svg width="20" height="20" viewBox="0 0 24 24">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Mit Google anmelden
        </a>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #7f8c8d;">
            <p>Ihre Daten werden sicher über OAuth 2.0 übertragen.<br>
            Wir speichern nur Name und E-Mail-Adresse.</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
