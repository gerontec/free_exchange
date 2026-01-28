<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

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

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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
        
        $success = true;
        
        $stmt = $pdo->prepare("
            SELECT l.*, a.*
            FROM lg_lagerraeume l
            JOIN lg_adressen a ON l.adresse_id = a.adresse_id
            WHERE l.lagerraum_id = :id
        ");
        $stmt->execute([':id' => $lagerraum_id]);
        $angebot = $stmt->fetch();
        
    } catch (Exception $e) {
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
        <form method="POST">
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
