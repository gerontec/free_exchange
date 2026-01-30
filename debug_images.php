<?php
/**
 * Debug script to check images in database
 */
require_once 'includes/config.php';

echo "<h2>Debug: Bilder in der Datenbank</h2>";

// Alle Bilder aus der Datenbank abrufen
$stmt = $pdo->query("
    SELECT i.*, l.title_de, l.listing_id
    FROM em_images i
    JOIN em_listings l ON i.listing_id = l.listing_id
    ORDER BY i.erstellt_am DESC
    LIMIT 20
");
$images = $stmt->fetchAll();

if (empty($images)) {
    echo "<p style='color: red;'>Keine Bilder in der Datenbank gefunden!</p>";
} else {
    echo "<p style='color: green;'>Gefundene Bilder: " . count($images) . "</p>";

    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr>
            <th>Image ID</th>
            <th>Listing ID</th>
            <th>Titel</th>
            <th>Filename</th>
            <th>Filepath</th>
            <th>Image Type</th>
            <th>Erstellt am</th>
            <th>Datei existiert?</th>
            <th>Vorschau</th>
          </tr>";

    foreach ($images as $img) {
        $file_exists = file_exists($img['filepath']);
        $full_path = __DIR__ . '/' . $img['filepath'];
        $full_file_exists = file_exists($full_path);

        echo "<tr>";
        echo "<td>" . $img['image_id'] . "</td>";
        echo "<td>" . $img['listing_id'] . "</td>";
        echo "<td>" . htmlspecialchars($img['title_de']) . "</td>";
        echo "<td>" . htmlspecialchars($img['filename']) . "</td>";
        echo "<td>" . htmlspecialchars($img['filepath']) . "</td>";
        echo "<td>" . htmlspecialchars($img['image_type']) . "</td>";
        echo "<td>" . $img['erstellt_am'] . "</td>";
        echo "<td style='color: " . ($full_file_exists ? 'green' : 'red') . ";'>";
        echo $full_file_exists ? "✓ Ja" : "✗ Nein";
        echo "<br>Pfad: " . $full_path;
        echo "</td>";
        echo "<td>";
        if ($full_file_exists) {
            echo "<img src='" . htmlspecialchars($img['filepath']) . "' style='max-width: 100px; max-height: 100px;'>";
        } else {
            echo "❌";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Upload-Verzeichnisse prüfen
echo "<h3>Upload-Verzeichnisse:</h3>";
$dirs = ['uploads/metals', 'uploads/storage', 'uploads/guns'];
foreach ($dirs as $dir) {
    $full_dir = __DIR__ . '/' . $dir;
    $exists = is_dir($full_dir);
    $writable = is_writable($full_dir);

    echo "<p>";
    echo "<strong>$dir:</strong> ";
    echo $exists ? "✓ Existiert" : "✗ Existiert nicht";
    if ($exists) {
        echo " | " . ($writable ? "✓ Beschreibbar" : "✗ Nicht beschreibbar");

        // Dateien im Verzeichnis zählen
        $files = glob($full_dir . '/*');
        echo " | " . count($files) . " Dateien";
    }
    echo "<br>Vollständiger Pfad: $full_dir";
    echo "</p>";
}

?>
