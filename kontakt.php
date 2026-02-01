<?php
require_once 'includes/config.php';
require_once 'includes/lang.php';
$pageTitle = 'Kontakt aufnehmen';

$lagerraum_id = $_GET['id'] ?? null;

if (!$lagerraum_id) {
    header('Location: index.php');
    exit;
}

// Lagerraum-Details laden (inkl. Anbieter-E-Mail und Adresse)
$stmt = $pdo->prepare("
    SELECT lr.*,
           a.strasse, a.hausnummer, a.plz, a.ort, a.land,
           u.email as anbieter_email, u.name as anbieter_name
    FROM lg_lagerraeume lr
    LEFT JOIN lg_adressen a ON lr.adresse_id = a.adresse_id
    JOIN lg_users u ON lr.anbieter_id = u.user_id
    WHERE lr.lagerraum_id = :id AND lr.aktiv = 1
");
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

        // E-Mail-Benachrichtigung an Anbieter senden
        if (!empty($lagerraum['anbieter_email'])) {
            $anbieterName = htmlspecialchars($lagerraum['anbieter_name'] ?? 'Anbieter');
            $interessentName = htmlspecialchars($_POST['name']);
            $interessentEmail = htmlspecialchars($_POST['email']);
            $interessentTelefon = htmlspecialchars($_POST['telefon'] ?? '-');
            $nachricht = nl2br(htmlspecialchars($_POST['nachricht']));

            $lagerraumAdresse = htmlspecialchars($lagerraum['strasse'] . ' ' . $lagerraum['hausnummer'] . ', ' .
                                                 $lagerraum['plz'] . ' ' . $lagerraum['ort']);
            $lagerraumQm = number_format($lagerraum['qm_gesamt'], 2, ',', '.');
            $lagerraumPreis = number_format($lagerraum['preis_gesamt'], 2, ',', '.');

            $emailContent = "
                <h2>Neue Kontaktanfrage für Ihren Lagerraum</h2>

                <h3>Lagerraum-Details:</h3>
                <p>
                    <strong>Adresse:</strong> {$lagerraumAdresse}<br>
                    <strong>Fläche:</strong> {$lagerraumQm} m²<br>
                    <strong>Preis:</strong> {$lagerraumPreis} €/Monat
                </p>

                <h3>Kontaktdaten des Interessenten:</h3>
                <p>
                    <strong>Name:</strong> {$interessentName}<br>
                    <strong>E-Mail:</strong> <a href='mailto:{$interessentEmail}'>{$interessentEmail}</a><br>
                    <strong>Telefon:</strong> {$interessentTelefon}
                </p>

                <h3>Nachricht:</h3>
                <p>{$nachricht}</p>

                <p style='margin-top: 30px;'>
                    <a href='" . BASE_URL . "meine_angebote.php' class='btn'>Meine Angebote verwalten</a>
                </p>
            ";

            $emailSubject = "Neue Kontaktanfrage für Lagerraum in " . $lagerraum['ort'];
            sendEmail($lagerraum['anbieter_email'], $emailSubject, getEmailTemplate($emailContent), MAIL_FROM, $interessentEmail);
        }

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
