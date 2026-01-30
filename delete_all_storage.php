<?php
/**
 * Delete all storage listings - FOR TESTING ONLY
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/image_upload.php';

requireLogin();
$user = getCurrentUser();

echo "<h2>Alle Lager-Angebote löschen</h2>";

try {
    // Alle Lagerangebote des Users holen
    $stmt = $pdo->prepare("
        SELECT l.lagerraum_id, l.adresse_id, a.strasse, a.ort
        FROM lg_lagerraeume l
        LEFT JOIN lg_adressen a ON l.adresse_id = a.adresse_id
        WHERE l.anbieter_id = :user_id
    ");
    $stmt->execute([':user_id' => $user['user_id']]);
    $lagerungen = $stmt->fetchAll();

    if (empty($lagerungen)) {
        echo "<p style='color: orange;'>Keine Lager-Angebote gefunden.</p>";
        echo "<p><a href='index.php'>← Zurück zur Startseite</a></p>";
        exit;
    }

    echo "<p>Gefundene Angebote: " . count($lagerungen) . "</p>";
    echo "<ul>";
    foreach ($lagerungen as $lg) {
        echo "<li>ID {$lg['lagerraum_id']}: {$lg['strasse']}, {$lg['ort']}</li>";
    }
    echo "</ul>";

    // Bilder löschen (Dateien)
    $imageUpload = new ImageUpload('storage');
    foreach ($lagerungen as $lg) {
        $stmt = $pdo->prepare("SELECT filename FROM lg_images WHERE lagerraum_id = :id");
        $stmt->execute([':id' => $lg['lagerraum_id']]);
        $images = $stmt->fetchAll();

        foreach ($images as $img) {
            $imageUpload->delete($img['filename']);
            echo "<p style='color: blue;'>✓ Bild gelöscht: {$img['filename']}</p>";
        }
    }

    // Lagerangebote löschen (CASCADE löscht automatisch lg_images Einträge aus DB)
    $stmt = $pdo->prepare("DELETE FROM lg_lagerraeume WHERE anbieter_id = :user_id");
    $stmt->execute([':user_id' => $user['user_id']]);

    $deleted = $stmt->rowCount();
    echo "<p style='color: green; font-weight: bold;'>✓ {$deleted} Lager-Angebote gelöscht!</p>";

    // Adressen löschen (optional - nur wenn nicht von anderen genutzt)
    foreach ($lagerungen as $lg) {
        if ($lg['adresse_id']) {
            // Prüfen ob Adresse noch von anderen genutzt wird
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as cnt
                FROM lg_lagerraeume
                WHERE adresse_id = :id
            ");
            $stmt->execute([':id' => $lg['adresse_id']]);
            $count = $stmt->fetch()['cnt'];

            if ($count == 0) {
                $stmt = $pdo->prepare("DELETE FROM lg_adressen WHERE adresse_id = :id");
                $stmt->execute([':id' => $lg['adresse_id']]);
                echo "<p style='color: gray;'>✓ Adresse {$lg['adresse_id']} gelöscht</p>";
            }
        }
    }

    echo "<p><a href='angebot_erstellen.php' class='btn btn-success'>→ Neues Angebot erstellen</a></p>";
    echo "<p><a href='index.php'>← Zurück zur Startseite</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
