<?php
/**
 * config.example.php - Beispiel-Konfigurationsdatei
 *
 * ANLEITUNG:
 * 1. Kopiere diese Datei nach config.php
 * 2. Passe die Datenbankzugangsdaten an
 * 3. Passe die E-Mail-Einstellungen an
 */

// Session starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Datenbank-Konfiguration
define('DB_HOST', 'localhost');
define('DB_NAME', 'free_exchange');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// SMTP/Mail-Konfiguration
define('MAIL_FROM', 'noreply@heissa.de');
define('MAIL_FROM_NAME', 'FreeExchange');
define('MAIL_SMTP_HOST', 'localhost');
define('MAIL_SMTP_PORT', 25);
define('MAIL_ADMIN', 'admin@heissa.de'); // Admin-E-Mail für Fehlermeldungen

// Basis-URL
define('BASE_URL', 'https://heissa.de/web2/');

// Datenbankverbindung herstellen
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Datenbankverbindung fehlgeschlagen. Bitte kontaktieren Sie den Administrator.");
}

/**
 * E-Mail versenden
 *
 * @param string $to Empfänger-E-Mail
 * @param string $subject Betreff
 * @param string $message Nachricht (HTML möglich)
 * @param string|null $from Absender (optional, nutzt MAIL_FROM wenn null)
 * @param string|null $replyTo Reply-To-Adresse (optional)
 * @return bool Erfolgsstatus
 */
function sendEmail($to, $subject, $message, $from = null, $replyTo = null) {
    if (empty($to) || empty($subject) || empty($message)) {
        error_log("sendEmail: Missing required parameters");
        return false;
    }

    // Absender festlegen
    if ($from === null) {
        $from = MAIL_FROM;
    }

    // Headers aufbauen
    $headers = [];
    $headers[] = "From: " . MAIL_FROM_NAME . " <" . $from . ">";
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/html; charset=UTF-8";
    $headers[] = "X-Mailer: PHP/" . phpversion();

    if ($replyTo !== null) {
        $headers[] = "Reply-To: " . $replyTo;
    }

    // E-Mail versenden
    $success = mail($to, $subject, $message, implode("\r\n", $headers));

    // Fehler loggen
    if (!$success) {
        error_log("Failed to send email to: $to, subject: $subject");
    }

    return $success;
}

/**
 * HTML-E-Mail-Template
 *
 * @param string $content Inhalt der E-Mail
 * @return string HTML-formatierte E-Mail
 */
function getEmailTemplate($content) {
    return '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>FreeExchange</h1>
        </div>
        <div class="content">
            ' . $content . '
        </div>
        <div class="footer">
            <p>© ' . date('Y') . ' FreeExchange | <a href="' . BASE_URL . '">heissa.de</a></p>
        </div>
    </div>
</body>
</html>';
}
?>
