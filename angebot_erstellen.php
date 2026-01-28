<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/image_upload.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = t('create_offer_title');

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Adresse erstellen
        $stmt = $pdo->prepare("
            INSERT INTO lg_adressen (strasse, hausnummer, plz, ort, land) 
            VALUES (:strasse, :hausnummer, :plz, :ort, :land)
        ");
        $stmt->execute([
            ':strasse' => $_POST['strasse'],
            ':hausnummer' => $_POST['hausnummer'],
            ':plz' => $_POST['plz'],
            ':ort' => $_POST['ort'],
            ':land' => $_POST['land'] ?: 'DE'
        ]);
        $adresse_id = $pdo->lastInsertId();
        
        // Lagerraum erstellen
        $stmt = $pdo->prepare("
            INSERT INTO lg_lagerraeume (
                anbieter_id, adresse_id, anzahl_raeume, qm_gesamt, 
                preis_pro_qm, beheizt, klimatisiert, zugang_24_7, 
                alarm_vorhanden, rolltor, verfuegbar_ab, bemerkung, typ
            ) VALUES (
                :anbieter_id, :adresse_id, :anzahl_raeume, :qm_gesamt,
                :preis_pro_qm, :beheizt, :klimatisiert, :zugang_24_7,
                :alarm_vorhanden, :rolltor, :verfuegbar_ab, :bemerkung, 'angebot'
            )
        ");
        $stmt->execute([
            ':anbieter_id' => $user['user_id'],
            ':adresse_id' => $adresse_id,
            ':anzahl_raeume' => $_POST['anzahl_raeume'],
            ':qm_gesamt' => $_POST['qm_gesamt'],
            ':preis_pro_qm' => $_POST['preis_pro_qm'],
            ':beheizt' => isset($_POST['beheizt']) ? 1 : 0,
            ':klimatisiert' => isset($_POST['klimatisiert']) ? 1 : 0,
            ':zugang_24_7' => isset($_POST['zugang_24_7']) ? 1 : 0,
            ':alarm_vorhanden' => isset($_POST['alarm_vorhanden']) ? 1 : 0,
            ':rolltor' => isset($_POST['rolltor']) ? 1 : 0,
            ':verfuegbar_ab' => $_POST['verfuegbar_ab'] ?: null,
            ':bemerkung' => $_POST['bemerkung']
        ]);
        $lagerraum_id = $pdo->lastInsertId();
        
        // Bilder hochladen
        if (!empty($_FILES['images']['name'][0])) {
            $uploader = new ImageUpload('storage');
            $uploaded_images = $uploader->uploadMultiple($_FILES['images'], 'storage_' . $lagerraum_id);
            
            foreach ($uploaded_images as $index => $img) {
                $stmt = $pdo->prepare("
                    INSERT INTO lg_bilder (lagerraum_id, filename, filepath, filesize, is_main, sort_order)
                    VALUES (:lagerraum_id, :filename, :filepath, :filesize, :is_main, :sort_order)
                ");
                $stmt->execute([
                    ':lagerraum_id' => $lagerraum_id,
                    ':filename' => $img['filename'],
                    ':filepath' => $img['filepath'],
                    ':filesize' => $img['filesize'],
                    ':is_main' => $index === 0 ? 1 : 0,
                    ':sort_order' => $index
                ]);
            }
        }
        
        $success = true;
    } catch (Exception $e) {
        $error = "Fehler: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<h2>📦 <?= t('create_offer_title') ?></h2>

<div class="alert alert-warning mb-4">
    ⏰ <strong><?= t('auto_delete_notice') ?></strong>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        ✓ <?= t('offer_published') ?>
        <a href="meine_angebote.php" class="alert-link"><?= t('back_to_offers') ?></a>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="alert alert-info">
                ✓ <?= t('logged_in_as') ?>: <strong><?= htmlspecialchars($user['name']) ?></strong>
            </div>
            
            <h4 class="mb-3"><?= t('offer_address') ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label"><?= t('country') ?> *</label>
                    <select name="land" class="form-select" required>
                        <?php foreach ($LAENDER as $code => $name): ?>
                            <option value="<?= $code ?>" <?= $code === 'DE' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= t('street') ?> *</label>
                    <input type="text" name="strasse" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('house_number') ?> *</label>
                    <input type="text" name="hausnummer" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('zip') ?> *</label>
                    <input type="text" name="plz" class="form-control" required>
                </div>
                <div class="col-md-9">
                    <label class="form-label"><?= t('city') ?> *</label>
                    <input type="text" name="ort" class="form-control" required>
                </div>
            </div>
            
            <h4 class="mb-3"><?= t('details') ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Anzahl Räume *</label>
                    <input type="number" name="anzahl_raeume" class="form-control" value="1" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gesamtfläche (m²) *</label>
                    <input type="number" name="qm_gesamt" class="form-control" step="0.01" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Preis pro m² (€) *</label>
                    <input type="number" name="preis_pro_qm" class="form-control" step="0.01" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Verfügbar ab</label>
                    <input type="date" name="verfuegbar_ab" class="form-control">
                </div>
            </div>
            
            <h4 class="mb-3"><?= t('features') ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" name="beheizt" class="form-check-input" id="beheizt">
                        <label class="form-check-label" for="beheizt">🔥 Beheizt</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" name="klimatisiert" class="form-check-input" id="klimatisiert">
                        <label class="form-check-label" for="klimatisiert">❄️ Klimatisiert</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" name="zugang_24_7" class="form-check-input" id="zugang_24_7">
                        <label class="form-check-label" for="zugang_24_7">🕐 24/7 Zugang</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" name="alarm_vorhanden" class="form-check-input" id="alarm">
                        <label class="form-check-label" for="alarm">🔒 Alarmgesichert</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" name="rolltor" class="form-check-input" id="rolltor">
                        <label class="form-check-label" for="rolltor">🚪 Rolltor</label>
                    </div>
                </div>
            </div>
            
            <h4 class="mb-3">📸 Bilder hochladen</h4>
            <div class="mb-4">
                <label class="form-label">Fotos (max. 5MB pro Bild, JPG/PNG/GIF/WEBP)</label>
                <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                <small class="text-muted">Sie können mehrere Bilder auswählen. Erstes Bild = Hauptbild</small>
            </div>
            
            <div class="mb-4">
                <label class="form-label"><?= t('remarks') ?></label>
                <textarea name="bemerkung" class="form-control" rows="4"></textarea>
            </div>
            
            <button type="submit" class="btn btn-success btn-lg">
                <i class="bi bi-check-circle"></i> <?= t('btn_publish') ?>
            </button>
            <a href="index.php" class="btn btn-outline-secondary btn-lg"><?= t('btn_cancel') ?></a>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
