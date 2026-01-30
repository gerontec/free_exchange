<?php
/**
 * Debug Script - Warum funktioniert t() nicht?
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug: t() Funktion Problem</h2>";

echo "<h3>1. Datei-Existenz prüfen</h3>";
$files_to_check = [
    'angebot_erstellen.php',
    'includes/lang.php',
    'includes/config.php',
    'includes/auth.php',
    'includes/image_upload.php'
];

foreach ($files_to_check as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $color = $exists ? 'green' : 'red';
    echo "<p style='color: $color;'>";
    echo $exists ? "✓" : "✗";
    echo " $file: " . ($exists ? "Existiert" : "NICHT GEFUNDEN");
    if ($exists) {
        echo " (" . filesize(__DIR__ . '/' . $file) . " bytes, modified: " . date("Y-m-d H:i:s", filemtime(__DIR__ . '/' . $file)) . ")";
    }
    echo "</p>";
}

echo "<h3>2. Erste 30 Zeilen von angebot_erstellen.php</h3>";
if (file_exists(__DIR__ . '/angebot_erstellen.php')) {
    $lines = file(__DIR__ . '/angebot_erstellen.php');
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>";
    for ($i = 0; $i < min(30, count($lines)); $i++) {
        $line_no = str_pad($i + 1, 3, ' ', STR_PAD_LEFT);
        echo htmlspecialchars($line_no . ': ' . $lines[$i]);
    }
    echo "</pre>";
} else {
    echo "<p style='color: red;'>Datei nicht gefunden!</p>";
}

echo "<h3>3. includes/lang.php laden testen</h3>";
try {
    if (file_exists(__DIR__ . '/includes/lang.php')) {
        require_once __DIR__ . '/includes/lang.php';
        echo "<p style='color: green;'>✓ includes/lang.php erfolgreich geladen</p>";

        if (function_exists('t')) {
            echo "<p style='color: green;'>✓ Funktion t() ist definiert</p>";
            echo "<p>Test: t('btn_cancel') = <strong>" . htmlspecialchars(t('btn_cancel')) . "</strong></p>";
        } else {
            echo "<p style='color: red;'>✗ Funktion t() ist NICHT definiert</p>";
        }

        if (isset($LANG)) {
            echo "<p>Aktuelle Sprache: <strong>$LANG</strong></p>";
        }
    } else {
        echo "<p style='color: red;'>✗ includes/lang.php nicht gefunden</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Fehler beim Laden: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h3>4. Git Status</h3>";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>";
chdir(__DIR__);
echo "Current directory: " . getcwd() . "\n\n";
echo "Git branch:\n";
system('git branch 2>&1');
echo "\nGit status:\n";
system('git status 2>&1');
echo "\nLast 3 commits:\n";
system('git log --oneline -3 2>&1');
echo "</pre>";

echo "<h3>5. Datei-Hashes vergleichen</h3>";
$critical_files = [
    'angebot_erstellen.php',
    'includes/lang.php'
];

foreach ($critical_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $hash = md5_file(__DIR__ . '/' . $file);
        echo "<p><code>$file</code>: <strong>$hash</strong></p>";
    }
}

echo "<p><a href='index.php'>← Zurück zur Startseite</a></p>";
?>
