<?php
require_once 'includes/config.php';
require_once 'includes/lang.php';
$pageTitle = 'Nachfrage einstellen';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Benutzer erstellen/abrufen
        $stmt = $pdo->prepare("
            INSERT INTO lg_users (email, telefon, name) 
            VALUES (:email, :telefon, :name)
            ON DUPLICATE KEY UPDATE user_id=LAST_INSERT_ID(user_id)
        ");
        $stmt->execute([
            ':email' => $_POST['email'],
            ':telefon' => $_POST['telefon'],
            ':name' => $_POST['name']
        ]);
        $user_id = $pdo->lastInsertId();
        
        // Suchanfrage erstellen
        $stmt = $pdo->prepare("
            INSERT INTO lg_suchanfragen (
                suchender_id, land_wunsch, plz_von, plz_bis, umkreis_km, ort_wunsch,
                qm_min, qm_max, preis_max, anzahl_raeume_min,
                beheizt_gewuenscht, zugang_24_7_gewuenscht
            ) VALUES (
                :suchender_id, :land_wunsch, :plz_von, :plz_bis, :umkreis_km, :ort_wunsch,
                :qm_min, :qm_max, :preis_max, :anzahl_raeume_min,
                :beheizt_gewuenscht, :zugang_24_7_gewuenscht
            )
        ");
        $stmt->execute([
            ':suchender_id' => $user_id,
            ':land_wunsch' => $_POST['land_wunsch'] ?: 'DE',
            ':plz_von' => $_POST['plz_von'] ?: null,
            ':plz_bis' => $_POST['plz_bis'] ?: null,
            ':umkreis_km' => $_POST['umkreis_km'] ?: null,
            ':ort_wunsch' => $_POST['ort_wunsch'] ?: null,
            ':qm_min' => $_POST['qm_min'] ?: null,
            ':qm_max' => $_POST['qm_max'] ?: null,
            ':preis_max' => $_POST['preis_max'] ?: null,
            ':anzahl_raeume_min' => $_POST['anzahl_raeume_min'] ?: 1,
            ':beheizt_gewuenscht' => isset($_POST['beheizt_gewuenscht']) ? 1 : 0,
            ':zugang_24_7_gewuenscht' => isset($_POST['zugang_24_7_gewuenscht']) ? 1 : 0
        ]);
        
        $success = true;
    } catch (Exception $e) {
        $error = "Fehler: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<h2>🔍 Lagerraum-Nachfrage einstellen</h2>

<!-- Hinweis-Banner -->
<div class="alert" style="background: #fff3cd; color: #856404; border: 1px solid #ffc107; margin-bottom: 20px;">
    ⏰ <strong>Wichtiger Hinweis:</strong> Ihre Nachfrage wird nach <strong><?= AUTO_DELETE_DAYS ?> Tagen automatisch gelöscht</strong>.
    Sie können sie danach jederzeit kostenlos neu einstellen.
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        ✓ Ihre Nachfrage wurde erfolgreich veröffentlicht! 
        <a href="nachfragen.php">Zur Nachfragen-Übersicht</a>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST">
        <h3>Ihre Kontaktdaten</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>E-Mail *</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Telefon</label>
                <input type="text" name="telefon">
            </div>
        </div>
        
        <h3 style="margin-top: 30px;">Wunschregion</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>Land *</label>
                <select name="land_wunsch" id="land_select" required>
                    <?php foreach ($LAENDER as $code => $name): ?>
                        <option value="<?= $code ?>" <?= $code === 'DE' ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Ort</label>
                <input type="text" name="ort_wunsch" placeholder="z.B. Berlin, Wien, Zürich">
                <small>Oder PLZ-Bereich angeben:</small>
            </div>
            <div class="form-group">
                <label id="plz_von_label">PLZ von</label>
                <input type="text" name="plz_von" id="plz_von" placeholder="z.B. 10000">
            </div>
            <div class="form-group">
                <label id="plz_bis_label">PLZ bis</label>
                <input type="text" name="plz_bis" id="plz_bis" placeholder="z.B. 19999">
            </div>
        </div>
        <div class="form-group">
            <label>Umkreis (km)</label>
            <input type="number" name="umkreis_km" placeholder="z.B. 25" min="1" max="500">
        </div>
        
        <h3 style="margin-top: 30px;">Gewünschte Lagerraum-Eigenschaften</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>Min. Fläche (m²)</label>
                <input type="number" name="qm_min" step="0.01" placeholder="z.B. 50">
            </div>
            <div class="form-group">
                <label>Max. Fläche (m²)</label>
                <input type="number" name="qm_max" step="0.01" placeholder="z.B. 200">
            </div>
            <div class="form-group">
                <label>Max. Preis (€/m²)</label>
                <input type="number" name="preis_max" step="0.01" placeholder="z.B. 10.00">
            </div>
            <div class="form-group">
                <label>Min. Anzahl Räume</label>
                <input type="number" name="anzahl_raeume_min" value="1" min="1">
            </div>
        </div>
        
        <h3 style="margin-top: 30px;">Gewünschte Ausstattung</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
            <label><input type="checkbox" name="beheizt_gewuenscht"> 🔥 Beheizt</label>
            <label><input type="checkbox" name="zugang_24_7_gewuenscht"> 🕐 24/7 Zugang</label>
        </div>
        
        <div style="margin-top: 30px;">
            <button type="submit" class="btn btn-success">✓ Nachfrage veröffentlichen</button>
            <a href="nachfragen.php" class="btn">Abbrechen</a>
        </div>
    </form>
</div>

<script>
// Platzhalter für PLZ-Felder je nach Land anpassen
document.getElementById('land_select').addEventListener('change', function() {
    const land = this.value;
    const plzVon = document.getElementById('plz_von');
    const plzBis = document.getElementById('plz_bis');
    const plzVonLabel = document.getElementById('plz_von_label');
    const plzBisLabel = document.getElementById('plz_bis_label');
    
    const plzBeispiele = {
        'DE': {von: '10000', bis: '99999', label: 'PLZ'},
        'AT': {von: '1000', bis: '9999', label: 'PLZ'},
        'CH': {von: '1000', bis: '9999', label: 'PLZ'},
        'NL': {von: '1000', bis: '9999', label: 'Postcode'},
        'BE': {von: '1000', bis: '9999', label: 'Code postal'},
        'FR': {von: '01000', bis: '99999', label: 'Code postal'},
        'GB': {von: 'SW1A', bis: 'ZE3', label: 'Postcode'},
        'IT': {von: '00100', bis: '99999', label: 'CAP'},
        'ES': {von: '01000', bis: '52999', label: 'Código postal'},
        'PL': {von: '00-001', bis: '99-999', label: 'Kod pocztowy'},
    };
    
    const beispiel = plzBeispiele[land] || {von: '', bis: '', label: 'PLZ'};
    plzVon.placeholder = 'z.B. ' + beispiel.von;
    plzBis.placeholder = 'z.B. ' + beispiel.bis;
    plzVonLabel.textContent = beispiel.label + ' von';
    plzBisLabel.textContent = beispiel.label + ' bis';
});
</script>

<?php include 'includes/footer.php'; ?>
