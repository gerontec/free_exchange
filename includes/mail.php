<?php
/**
 * mail.php - E-Mail-Funktionen
 *
 * Diese Datei enthält Hilfsfunktionen zum Versenden von E-Mails.
 * Muss nach config.php eingebunden werden.
 */

// Sicherstellen, dass config.php geladen wurde
if (!defined('MAIL_FROM')) {
    die('Error: config.php must be loaded before mail.php');
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
    $baseUrl = defined('BASE_URL') ? BASE_URL : (defined('SITE_URL') ? SITE_URL : 'https://heissa.de/web2');
    $siteName = defined('SITE_NAME') ? SITE_NAME : 'FreeExchange';

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
            <h1>' . htmlspecialchars($siteName) . '</h1>
        </div>
        <div class="content">
            ' . $content . '
        </div>
        <div class="footer">
            <p>© ' . date('Y') . ' ' . htmlspecialchars($siteName) . ' | <a href="' . htmlspecialchars($baseUrl) . '">heissa.de</a></p>
        </div>
    </div>
</body>
</html>';
}
?>
