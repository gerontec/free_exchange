<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/image_upload.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = t('edit_offer');

$lagerraum_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT l.*, a.*
    FROM lg_lagerraeume l
    JOIN lg_adressen a ON l.adresse_id = a.adresse_id
    WHERE l.lagerraum_id = :id AND l.anbieter_id = :user_id
");
$stmt->execute([
    ':id' => $lagerraum_id,
    ':user_id' => $user['user_id']
]);
$angebot = $stmt->fetch();

if (!$angebot) {
    header('Location: meine_angebote.php');
    exit;
}

// Bilder für diesen Lagerraum laden
$stmt = $pdo->prepare("SELECT * FROM lg_images WHERE lagerraum_id = :id ORDER BY sort_order");
$stmt->execute([':id' => $lagerraum_id]);
$images = $stmt->fetchAll();

$success = false;
$error = '';

// Bild löschen
if (isset($_POST['delete_image'])) {
    $image_id = (int)$_POST['delete_image'];

    // Bild aus DB holen
    $stmt = $pdo->prepare("SELECT * FROM lg_images WHERE image_id = :id AND lagerraum_id = :lagerraum_id");
    $stmt->execute([':id' => $image_id, ':lagerraum_id' => $lagerraum_id]);
    $image = $stmt->fetch();

    if ($image) {
        // Datei löschen
        $imageUpload = new ImageUpload('storage');
        $imageUpload->delete($image['filename']);

        // Aus DB löschen
        $stmt = $pdo->prepare("DELETE FROM lg_images WHERE image_id = :id");
        $stmt->execute([':id' => $image_id]);

        header('Location: angebot_bearbeiten.php?id=' . $lagerraum_id);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_image'])) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            UPDATE lg_adressen 
            SET strasse = :strasse, hausnummer = :hausnummer, 
                plz = :plz, ort = :ort, land = :land
            WHERE adresse_id = :id
        ");
        $stmt->execute([
            ':strasse' => $_POST['strasse'],
            ':hausnummer' => $_POST['hausnummer'],
            ':plz' => $_POST['plz'],
            ':ort' => $_POST['ort'],
            ':land' => $_POST['land'],
            ':id' => $angebot['adresse_id']
        ]);
        
        $stmt = $pdo->prepare("
            UPDATE lg_lagerraeume 
            SET anzahl_raeume = :anzahl_raeume, qm_gesamt = :qm_gesamt,
                preis_pro_qm = :preis_pro_qm, beheizt = :beheizt,
                klimatisiert = :klimatisiert, zugang_24_7 = :zugang_24_7,
                alarm_vorhanden = :alarm_vorhanden, rolltor = :rolltor,
                verfuegbar_ab = :verfuegbar_ab, bemerkung = :bemerkung,
                aktiv = :aktiv
            WHERE lagerraum_id = :id
        ");
        $stmt->execute([
            ':anzahl_raeume' => $_POST['anzahl_raeume'],
            ':qm_gesamt' => $_POST['qm_gesamt'],
            ':preis_pro_qm' => $_POST['preis_pro_qm'],
            ':beheizt' => isset($_POST['beheizt']) ? 1 : 0,
            ':klimatisiert' => isset($_POST['klimatisiert']) ? 1 : 0,
            ':zugang_24_7' => isset($_POST['zugang_24_7']) ? 1 : 0,
            ':alarm_vorhanden' => isset($_POST['alarm_vorhanden']) ? 1 : 0,
            ':rolltor' => isset($_POST['rolltor']) ? 1 : 0,
            ':verfuegbar_ab' => $_POST['verfuegbar_ab'] ?: null,
            ':bemerkung' => $_POST['bemerkung'],
            ':aktiv' => isset($_POST['aktiv']) ? 1 : 0,
            ':id' => $lagerraum_id
        ]);

        // Neue Bilder hochladen
        if (isset($_FILES['images']) && $_FILES['images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            try {
                $imageUpload = new ImageUpload('storage');
                $uploaded_images = $imageUpload->uploadMultiple($_FILES['images'], 'lg_' . $lagerraum_id);

                // Höchste Sort-Order ermitteln
                $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM lg_images WHERE lagerraum_id = :id");
                $stmt->execute([':id' => $lagerraum_id]);
                $max_order = $stmt->fetch()['max_order'] ?? -1;

                foreach ($uploaded_images as $index => $img) {
                    $stmt = $pdo->prepare("
                        INSERT INTO lg_images (lagerraum_id, filename, filepath, image_type, sort_order)
                        VALUES (:lagerraum_id, :filename, :filepath, :image_type, :sort_order)
                    ");
                    $stmt->execute([
                        ':lagerraum_id' => $lagerraum_id,
                        ':filename' => $img['filename'],
                        ':filepath' => $img['filepath'],
                        ':image_type' => 'detail',
                        ':sort_order' => $max_order + $index + 1
                    ]);
                }
            } catch (Exception $e) {
                // Fehler beim Upload, aber Angebot wurde aktualisiert
                $error = "Angebot aktualisiert, aber Fehler beim Bild-Upload: " . $e->getMessage();
            }
        }

        $pdo->commit();
        $success = true;

        // Lagerraum und Bilder neu laden
        $stmt = $pdo->prepare("
            SELECT l.*, a.*
            FROM lg_lagerraeume l
            JOIN lg_adressen a ON l.adresse_id = a.adresse_id
            WHERE l.lagerraum_id = :id
        ");
        $stmt->execute([':id' => $lagerraum_id]);
        $angebot = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT * FROM lg_images WHERE lagerraum_id = :id ORDER BY sort_order");
        $stmt->execute([':id' => $lagerraum_id]);
        $images = $stmt->fetchAll();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<h2><i class="bi bi-pencil"></i> <?= t('edit_offer') ?></h2>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?= t('saved_success') ?>
        <a href="meine_angebote.php" class="alert-link"><?= t('back_to_offers') ?></a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <h4 class="mb-3"><?= t('offer_address') ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label"><?= t('country') ?> *</label>
                    <select name="land" class="form-select" required>
                        <?php foreach ($LAENDER as $code => $name): ?>
                            <option value="<?= $code ?>" <?= $angebot['land'] === $code ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= t('street') ?> *</label>
                    <input type="text" name="strasse" class="form-control" 
                           value="<?= htmlspecialchars($angebot['strasse']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('house_number') ?> *</label>
                    <input type="text" name="hausnummer" class="form-control" 
                           value="<?= htmlspecialchars($angebot['hausnummer']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('zip') ?> *</label>
                    <input type="text" name="plz" class="form-control" 
                           value="<?= htmlspecialchars($angebot['plz']) ?>" required>
                </div>
                <div class="col-md-9">
                    <label class="form-label"><?= t('city') ?> *</label>
                    <input type="text" name="ort" class="form-control" 
                           value="<?= htmlspecialchars($angebot['ort']) ?>" required>
                </div>
            </div>
            
            <h4 class="mb-3"><?= t('details') ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label"><?= t('num_rooms') ?> *</label>
                    <input type="number" name="anzahl_raeume" class="form-control" 
                           value="<?= $angebot['anzahl_raeume'] ?>" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('total_area') ?> *</label>
                    <input type="number" name="qm_gesamt" class="form-control" 
                           value="<?= $angebot['qm_gesamt'] ?>" step="0.01" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('price_per_sqm') ?> *</label>
                    <input type="number" name="preis_pro_qm" class="form-control" 
                           value="<?= $angebot['preis_pro_qm'] ?>" step="0.01" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('available_from') ?></label>
                    <input type="date" name="verfuegbar_ab" class="form-control" 
                           value="<?= $angebot['verfuegbar_ab'] ?>">
                </div>
            </div>
            
            <h4 class="mb-3"><?= t('features') ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" name="beheizt" class="form-check-input" 
                               id="beheizt" <?= $angebot['beheizt'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="beheizt">🔥 <?= t('heated') ?></label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" name="klimatisiert" class="form-check-input" 
                               id="klimatisiert" <?= $angebot['klimatisiert'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="klimatisiert">❄️ <?= t('air_conditioned') ?></label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" name="zugang_24_7" class="form-check-input" 
                               id="zugang_24_7" <?= $angebot['zugang_24_7'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="zugang_24_7">🕐 <?= t('access_24_7') ?></label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" name="alarm_vorhanden" class="form-check-input" 
                               id="alarm_vorhanden" <?= $angebot['alarm_vorhanden'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="alarm_vorhanden">🔒 <?= t('alarm') ?></label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" name="rolltor" class="form-check-input" 
                               id="rolltor" <?= $angebot['rolltor'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="rolltor">🚪 <?= t('roll_door') ?></label>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label"><?= t('remarks') ?></label>
                <textarea name="bemerkung" class="form-control" rows="4"
                          placeholder="<?= t('remarks_placeholder') ?>"><?= htmlspecialchars($angebot['bemerkung']) ?></textarea>
            </div>

            <!-- Bilder verwalten -->
            <h4 class="mb-3 mt-4"><i class="bi bi-camera"></i> <?= t('photos') ?? 'Fotos' ?></h4>

            <!-- Vorhandene Bilder -->
            <?php if (!empty($images)): ?>
            <div class="row g-3 mb-3">
                <?php foreach ($images as $img): ?>
                <div class="col-md-3">
                    <div class="card" style="position: relative;">
                        <img src="<?= htmlspecialchars($img['filepath']) ?>"
                             class="card-img-top"
                             alt="Lagerraum"
                             style="height: 200px; object-fit: cover;">
                        <div class="card-body p-2">
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="delete_image" value="<?= $img['image_id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm w-100"
                                        onclick="return confirm('<?= t('confirm_delete_image') ?? 'Bild wirklich löschen?' ?>')">
                                    <i class="bi bi-trash"></i> <?= t('btn_delete') ?? 'Löschen' ?>
                                </button>
                            </form>
                        </div>
                        <?php if ($img['image_type'] === 'main'): ?>
                        <div class="badge bg-primary" style="position: absolute; top: 5px; right: 5px;">
                            <?= t('main_image') ?? 'Hauptbild' ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> <?= t('no_images_yet') ?? 'Noch keine Bilder vorhanden.' ?>
            </div>
            <?php endif; ?>

            <!-- Neue Bilder hochladen -->
            <div class="mb-4">
                <label class="form-label"><?= t('upload_new_photos') ?? 'Neue Fotos hochladen' ?> (max. 5 Bilder, je max. 5MB)</label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                <small class="text-muted">
                    <?= t('photo_hint') ?? 'Erlaubt: JPG, PNG, GIF, WEBP. Die Bilder werden zusätzlich zu den vorhandenen Bildern hinzugefügt.' ?>
                </small>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input type="checkbox" name="aktiv" class="form-check-input" 
                           id="aktiv" <?= $angebot['aktiv'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="aktiv">
                        <strong><?= t('offer_active') ?></strong> (<?= t('deactivate_hint') ?>)
                    </label>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> <?= t('btn_save') ?>
                </button>
                <a href="meine_angebote.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> <?= t('btn_cancel') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
