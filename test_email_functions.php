<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test: E-Mail-Funktionen Debug</h1>";
echo "<pre>";

// Finde die richtige config.php
$possible_paths = [
    __DIR__ . '/../web2/includes/config.php',
    '/web2/includes/config.php',
    '/var/www/web2/includes/config.php',
    '/home/gh/web2/includes/config.php',
];

$config_path = null;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $config_path = $path;
        break;
    }
}

if (!$config_path) {
    echo "ERROR: config.php nicht gefunden in:\n";
    foreach ($possible_paths as $path) {
        echo "  - $path\n";
    }
    exit(1);
}

echo "✓ config.php gefunden: $config_path\n\n";

echo "=== LOADING config.php ===\n";
try {
    require_once $config_path;
    echo "✓ config.php erfolgreich geladen\n\n";
} catch (Exception $e) {
    echo "✗ FEHLER: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "=== CHECKING DATABASE ===\n";
if (isset($pdo)) {
    echo "✓ \$pdo existiert\n";
    try {
        $stmt = $pdo->query("SELECT VERSION()");
        $version = $stmt->fetchColumn();
        echo "✓ Datenbank verbunden: MySQL $version\n";
    } catch (PDOException $e) {
        echo "✗ Datenbankfehler: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ \$pdo nicht definiert\n";
}

echo "\n=== CHECKING FUNCTIONS ===\n";
if (function_exists('sendEmail')) {
    echo "✓ sendEmail() existiert\n";
} else {
    echo "✗ sendEmail() FEHLT\n";
}

if (function_exists('getEmailTemplate')) {
    echo "✓ getEmailTemplate() existiert\n";
} else {
    echo "✗ getEmailTemplate() FEHLT\n";
}

echo "\n=== CHECKING CONSTANTS ===\n";
$constants = ['DB_HOST', 'DB_NAME', 'MAIL_FROM', 'BASE_URL', 'SITE_URL'];
foreach ($constants as $const) {
    if (defined($const)) {
        echo "✓ $const = " . constant($const) . "\n";
    } else {
        echo "✗ $const NICHT definiert\n";
    }
}

echo "\n=== TEST: sendEmail Funktion ===\n";
try {
    $testContent = "<h2>Test-E-Mail</h2><p>Dies ist ein Test.</p>";
    $testHtml = getEmailTemplate($testContent);
    echo "✓ getEmailTemplate() funktioniert\n";
    echo "HTML Länge: " . strlen($testHtml) . " Zeichen\n";
} catch (Exception $e) {
    echo "✗ getEmailTemplate() Fehler: " . $e->getMessage() . "\n";
}

echo "\n=== TESTING kontakt.php LOADING ===\n";
$kontakt_path = dirname($config_path, 2) . '/kontakt.php';
if (file_exists($kontakt_path)) {
    echo "✓ kontakt.php gefunden: $kontakt_path\n";

    // Syntax-Check
    $output = shell_exec("php -l " . escapeshellarg($kontakt_path) . " 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✓ kontakt.php: Keine Syntax-Fehler\n";
    } else {
        echo "✗ kontakt.php Syntax-Fehler:\n$output\n";
    }
} else {
    echo "✗ kontakt.php nicht gefunden: $kontakt_path\n";
}

echo "\n=== SUMMARY ===\n";
echo "Wenn alle Tests ✓ sind, sollte die Website funktionieren.\n";
echo "Falls HTTP 500 Fehler bestehen, prüfen Sie:\n";
echo "1. Apache/PHP error logs\n";
echo "2. PHP Version und Extensions\n";
echo "3. Dateiberechtigungen\n";

echo "</pre>";
?>
