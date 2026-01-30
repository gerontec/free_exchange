<?php
/**
 * Debug Index - Warum werden keine Storage-Angebote angezeigt?
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

echo "<h2>Debug: Storage Listings Display</h2>";

// Test 1: Exakte Query aus index.php
echo "<h3>1. SQL Query aus index.php (Zeile 280-314):</h3>";

$plz = '';
$ort = '';
$land = 'DE';
$qm_min = null;
$qm_max = null;
$preis_max = null;

$sql = "SELECT l.*, a.strasse, a.hausnummer, a.plz, a.ort, a.land, u.name as anbieter_name,
               DATEDIFF(DATE_ADD(l.erstellt_am, INTERVAL 60 DAY), NOW()) as tage_verbleibend
        FROM lg_lagerraeume l
        JOIN lg_adressen a ON l.adresse_id = a.adresse_id
        JOIN lg_users u ON l.anbieter_id = u.user_id
        WHERE l.typ = 'angebot' AND l.aktiv = 1 AND u.aktiv = 1";

$params = [];

if ($plz) {
    $sql .= " AND a.plz LIKE :plz";
    $params[':plz'] = $plz . '%';
}
if ($ort) {
    $sql .= " AND a.ort LIKE :ort";
    $params[':ort'] = '%' . $ort . '%';
}
if ($land) {
    $sql .= " AND a.land = :land";
    $params[':land'] = $land;
}
if ($qm_min) {
    $sql .= " AND l.qm_gesamt >= :qm_min";
    $params[':qm_min'] = $qm_min;
}
if ($qm_max) {
    $sql .= " AND l.qm_gesamt <= :qm_max";
    $params[':qm_max'] = $qm_max;
}
if ($preis_max) {
    $sql .= " AND l.preis_pro_qm <= :preis_max";
    $params[':preis_max'] = $preis_max;
}

$sql .= " ORDER BY l.erstellt_am DESC";

echo "<p><strong>SQL:</strong></p>";
echo "<pre>" . htmlspecialchars($sql) . "</pre>";
echo "<p><strong>Parameters:</strong></p>";
echo "<pre>";
print_r($params);
echo "</pre>";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $angebote = $stmt->fetchAll();

    echo "<p style='color: " . (count($angebote) > 0 ? 'green' : 'red') . "; font-size: 20px; font-weight: bold;'>";
    echo count($angebote) . " Angebote gefunden";
    echo "</p>";

    if (count($angebote) > 0) {
        echo "<h3>2. Gefundene Angebote:</h3>";
        foreach ($angebote as $a) {
            echo "<div style='background: #f5f5f5; padding: 15px; margin: 10px 0; border: 1px solid #ddd;'>";
            echo "<h4>📦 " . htmlspecialchars($a['ort']) . " - " . number_format($a['qm_gesamt'], 1) . " m²</h4>";
            echo "<p><strong>ID:</strong> " . $a['lagerraum_id'] . "</p>";
            echo "<p><strong>Adresse:</strong> " . htmlspecialchars($a['strasse'] . ' ' . $a['hausnummer'] . ', ' . $a['plz'] . ' ' . $a['ort']) . "</p>";
            echo "<p><strong>Preis:</strong> " . number_format($a['preis_pro_qm'], 2) . " €/m²</p>";
            echo "<p><strong>Total:</strong> " . number_format($a['preis_pro_qm'] * $a['qm_gesamt'], 2) . " €</p>";
            echo "<p><strong>Anbieter:</strong> " . htmlspecialchars($a['anbieter_name']) . "</p>";
            echo "<p><strong>Beheizt:</strong> " . ($a['beheizt'] ? 'Ja' : 'Nein') . "</p>";
            echo "<p><strong>24/7:</strong> " . ($a['zugang_24_7'] ? 'Ja' : 'Nein') . "</p>";
            echo "<p><strong>Tage verbleibend:</strong> " . $a['tage_verbleibend'] . "</p>";

            // Test Bildabfrage
            $img_stmt = $pdo->prepare("SELECT * FROM lg_images WHERE lagerraum_id = :id ORDER BY sort_order LIMIT 1");
            $img_stmt->execute([':id' => $a['lagerraum_id']]);
            $storage_image = $img_stmt->fetch();

            if ($storage_image) {
                echo "<p><strong>Bild:</strong> " . htmlspecialchars($storage_image['filepath']) . "</p>";
                if (file_exists(__DIR__ . '/' . $storage_image['filepath'])) {
                    echo "<img src='" . htmlspecialchars($storage_image['filepath']) . "' style='max-width: 200px;'>";
                } else {
                    echo "<p style='color: red;'>Bilddatei existiert nicht: " . htmlspecialchars($storage_image['filepath']) . "</p>";
                }
            } else {
                echo "<p><strong>Bild:</strong> Kein Bild vorhanden</p>";
            }

            echo "</div>";
        }
    } else {
        echo "<p style='color: red;'>Keine Angebote gefunden - prüfe WHERE-Bedingungen</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red; font-size: 18px;'><strong>SQL FEHLER:</strong><br>";
    echo htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<h3>3. Check: index.php Dateiinhalt (Zeile 367-373):</h3>";
$index_content = file_get_contents(__DIR__ . '/index.php');
$lines = explode("\n", $index_content);
echo "<pre>";
for ($i = 366; $i < 380 && $i < count($lines); $i++) {
    echo str_pad($i + 1, 4, ' ', STR_PAD_LEFT) . ': ' . htmlspecialchars($lines[$i]) . "\n";
}
echo "</pre>";

echo "<p><a href='index.php'>← Zur echten index.php</a></p>";
?>
