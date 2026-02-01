<?php
require_once 'includes/config.php';
require_once 'includes/lang.php';
$pageTitle = 'Kontakt aufnehmen';

$lagerraum_id = $_GET['id'] ?? null;

if (!$lagerraum_id) {
    header('Location: index.php');
    exit;
}

// Lagerraum-Details laden
$stmt = $pdo->prepare("SELECT * FROM lg_v_angebote WHERE lagerraum_id = :id");
$stmt->execute([':id' => $lagerraum_id]);
$lagerraum = $stmt->fetch();

if (!$lagerraum) {
    header('Location: index.php');
    exit;
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Interessent erstellen/abrufen
        $stmt = $pdo->prepare("
            INSERT INTO lg_users (email, telefon, name) 
            VALUES (:email, :telefon, :name)
            ON DUPLICATE KEY UPDATE user_id=LAST_INSERT_ID(user_id)
        ");
        $stmt->execute([
            ':email' => $_POST['email'],
            ':telefon' => $_POST['telefon'] ?: null,
            ':name' => $_POST['name']
        ]);
        $interessent_id = $pdo->lastInsertId();
        
        // Kontaktanfrage speichern
        $stmt = $pdo->prepare("
            INSERT INTO lg_kontaktanfragen (lagerraum_id, interessent_id, nachricht)
            VALUES (:lagerraum_id, :interessent_id, :nachricht)
        ");
        $stmt->execute([
            ':lagerraum_id' => $lagerraum_id,
            ':interessent_id' => $interessent_id,
            ':nachricht' => $_POST['nachricht']
        ]);
        
        $success = true;
    } catch (Exception $e) {
        $error = "Fehler: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<h2>📧 Kontakt aufnehmen</h2>

<?php if ($success): ?>
    <div class="alert alert-success">
        ✓ Ihre Nachricht wurde erfolgreich versendet! Der Anbieter wird sich bei Ihnen melden.
        <a href="index.php">Zurück zur Übersicht</a>
    </div>
<?php else: ?>

<div class="card">
    <h3>Lagerraum-Details</h3>
    <p><strong>Adresse:</strong> <?= htmlspecialchars($lagerraum['strasse']) ?> <?= htmlspecialchars($lagerraum['hausnummer']) ?>, <?= htmlspecialchars($lagerraum['plz']) ?> <?= htmlspecialchars($lagerraum['ort']) ?></p>
    <p><strong>Fläche:</strong> <?= number_format($lagerraum['qm_gesamt'], 2, ',', '.') ?> m²</p>
    <p><strong>Preis:</strong> <?= number_format($lagerraum['preis_pro_qm'], 2, ',', '.') ?> €/m² (Gesamt: <?= number_format($lagerraum['preis_gesamt'], 2, ',', '.') ?> €/Monat)</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <h3>Ihre Kontaktanfrage</h3>
    <form method="POST">
        <div class="form-group">
            <label>Ihr Name *</label>
            <input type="text" name="name" required>
        </div>
        
        <div class="form-group">
            <label>Ihre E-Mail *</label>
            <input type="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label>Ihre Telefonnummer</label>
            <input type="text" name="telefon">
        </div>
        
        <div class="form-group">
            <label>Ihre Nachricht *</label>
            <textarea name="nachricht" required placeholder="Beschreiben Sie Ihr Interesse am Lagerraum..."></textarea>
        </div>
        
        <button type="submit" class="btn btn-contact">📧 Nachricht senden</button>
        <a href="index.php" class="btn">Abbrechen</a>
    </form>
</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
