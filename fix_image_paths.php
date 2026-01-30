<?php
/**
 * Fix falsche Bildpfade in der Datenbank
 */
require_once 'includes/config.php';

echo "<h2>Bildpfade korrigieren</h2>";

// Alle Bilder mit falschen Pfaden finden
$stmt = $pdo->query("SELECT * FROM em_images WHERE filepath LIKE 'uploads/uploads/%'");
$images = $stmt->fetchAll();

if (empty($images)) {
    echo "<p style='color: green;'>✓ Keine fehlerhaften Pfade gefunden!</p>";
} else {
    echo "<p style='color: orange;'>Gefundene fehlerhafte Pfade: " . count($images) . "</p>";

    $fixed = 0;
    foreach ($images as $img) {
        $old_path = $img['filepath'];
        // uploads/uploads/filename.jpg -> uploads/metals/filename.jpg
        $new_path = str_replace('uploads/uploads/', 'uploads/metals/', $old_path);

        // Update in Datenbank
        $update_stmt = $pdo->prepare("UPDATE em_images SET filepath = :new_path WHERE image_id = :id");
        $update_stmt->execute([
            ':new_path' => $new_path,
            ':id' => $img['image_id']
        ]);

        echo "<p>✓ Korrigiert: Image ID {$img['image_id']}<br>";
        echo "&nbsp;&nbsp;Alt: <code>{$old_path}</code><br>";
        echo "&nbsp;&nbsp;Neu: <code>{$new_path}</code></p>";

        $fixed++;
    }

    echo "<p style='color: green; font-weight: bold;'>✓ {$fixed} Pfade korrigiert!</p>";
}

// Alle Bilder auflisten
echo "<h3>Aktuelle Bilder in der Datenbank:</h3>";
$all_stmt = $pdo->query("SELECT i.*, l.title_de FROM em_images i JOIN em_listings l ON i.listing_id = l.listing_id ORDER BY i.erstellt_am DESC");
$all_images = $all_stmt->fetchAll();

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Listing</th><th>Filepath</th><th>Datei existiert?</th></tr>";

foreach ($all_images as $img) {
    $file_path = __DIR__ . '/' . $img['filepath'];
    $exists = file_exists($file_path);

    echo "<tr>";
    echo "<td>{$img['image_id']}</td>";
    echo "<td>" . htmlspecialchars($img['title_de']) . "</td>";
    echo "<td><code>{$img['filepath']}</code></td>";
    echo "<td style='color: " . ($exists ? 'green' : 'red') . ";'>";
    echo $exists ? "✓ Ja" : "✗ Nein ($file_path)";
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='index.php?exchange=metals'>← Zurück zur Edelmetall-Börse</a></p>";
?>
