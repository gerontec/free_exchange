<?php
/**
 * Debug Storage Listings - Warum werden sie nicht angezeigt?
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

echo "<h2>Debug: Storage Listings</h2>";

// 1. Lagerraeume Tabelle prüfen
echo "<h3>1. Alle Lagerräume in lg_lagerraeume:</h3>";
$stmt = $pdo->query("SELECT * FROM lg_lagerraeume");
$lagerraeume = $stmt->fetchAll();
echo "<p>Gefunden: " . count($lagerraeume) . "</p>";
echo "<pre>";
print_r($lagerraeume);
echo "</pre>";

// 2. Adressen prüfen
echo "<h3>2. Alle Adressen in lg_adressen:</h3>";
$stmt = $pdo->query("SELECT * FROM lg_adressen");
$adressen = $stmt->fetchAll();
echo "<p>Gefunden: " . count($adressen) . "</p>";
echo "<pre>";
print_r($adressen);
echo "</pre>";

// 3. Users prüfen
echo "<h3>3. User mit ID 6:</h3>";
$stmt = $pdo->prepare("SELECT * FROM lg_users WHERE user_id = 6");
$stmt->execute();
$user = $stmt->fetch();
if ($user) {
    echo "<p style='color: green;'>✓ User gefunden</p>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>✗ User mit ID 6 nicht gefunden!</p>";
}

// 4. Exakte Query aus index.php ausführen
echo "<h3>4. Storage Query aus index.php:</h3>";
$sql = "SELECT l.*, a.strasse, a.hausnummer, a.plz, a.ort, a.land, u.name as anbieter_name,
               DATEDIFF(DATE_ADD(l.erstellt_am, INTERVAL 60 DAY), NOW()) as tage_verbleibend
        FROM lg_lagerraeume l
        JOIN lg_adressen a ON l.adresse_id = a.adresse_id
        JOIN lg_users u ON l.anbieter_id = u.user_id
        WHERE l.typ = 'angebot' AND l.aktiv = 1 AND u.aktiv = 1";

echo "<p><strong>SQL Query:</strong></p>";
echo "<pre>" . htmlspecialchars($sql) . "</pre>";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();

    echo "<p style='color: " . (count($results) > 0 ? 'green' : 'red') . ";'>";
    echo count($results) > 0 ? "✓" : "✗";
    echo " Ergebnisse: " . count($results) . "</p>";

    if (count($results) > 0) {
        echo "<pre>";
        print_r($results);
        echo "</pre>";
    } else {
        echo "<p style='color: orange;'>Keine Ergebnisse - prüfe WHERE-Bedingungen:</p>";

        // Query ohne WHERE für Debug
        $sql2 = "SELECT l.*, a.strasse, a.hausnummer, a.plz, a.ort, a.land, u.name as anbieter_name, u.aktiv as user_aktiv
                FROM lg_lagerraeume l
                JOIN lg_adressen a ON l.adresse_id = a.adresse_id
                JOIN lg_users u ON l.anbieter_id = u.user_id";

        $stmt2 = $pdo->query($sql2);
        $all_results = $stmt2->fetchAll();

        echo "<p>Query OHNE WHERE-Filter: " . count($all_results) . " Ergebnisse</p>";
        echo "<pre>";
        print_r($all_results);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ SQL Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='index.php'>← Zurück zur Startseite</a></p>";
?>
