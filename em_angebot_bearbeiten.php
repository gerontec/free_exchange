<?php
/**
 * em_angebot_bearbeiten.php - Edelmetall-Angebot bearbeiten
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/image_upload.php';
require_once 'includes/lang.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = t('edit_metal_offer') ?? 'Edelmetall-Angebot bearbeiten';

$listing_id = (int)($_GET['id'] ?? 0);

// Listing laden mit Adresse
$stmt = $pdo->prepare("
    SELECT l.*, a.strasse, a.hausnummer, a.plz, a.ort, a.land
    FROM em_listings l
    LEFT JOIN lg_adressen a ON l.adresse_id = a.adresse_id
    WHERE l.listing_id = :id AND l.user_id = :user_id
");
$stmt->execute([
    ':id' => $listing_id,
    ':user_id' => $user['user_id']
]);
$listing = $stmt->fetch();

if (!$listing) {
    header('Location: em_meine_angebote.php');
    exit;
}

// Stammdaten laden
$metals = $pdo->query("SELECT * FROM em_metals WHERE aktiv = 1 ORDER BY sort_order")->fetchAll();
$units = $pdo->query("SELECT * FROM em_units WHERE aktiv = 1 ORDER BY sort_order")->fetchAll();
$markets = $pdo->query("SELECT * FROM em_markets WHERE aktiv = 1 ORDER BY code")->fetchAll();

// Bilder für dieses Listing laden
$stmt = $pdo->prepare("SELECT * FROM em_images WHERE listing_id = :id ORDER BY sort_order");
$stmt->execute([':id' => $listing_id]);
$images = $stmt->fetchAll();

$success = false;
$error = '';

// Bild löschen
if (isset($_POST['delete_image'])) {
    $image_id = (int)$_POST['delete_image'];

    // Bild aus DB holen
    $stmt = $pdo->prepare("SELECT * FROM em_images WHERE image_id = :id AND listing_id = :listing_id");
    $stmt->execute([':id' => $image_id, ':listing_id' => $listing_id]);
    $image = $stmt->fetch();

    if ($image) {
        // Datei löschen
        $imageUpload = new ImageUpload('metals');
        $imageUpload->delete($image['filename']);

        // Aus DB löschen
        $stmt = $pdo->prepare("DELETE FROM em_images WHERE image_id = :id");
        $stmt->execute([':id' => $image_id]);

        header('Location: em_angebot_bearbeiten.php?id=' . $listing_id);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_image'])) {
    try {
        $pdo->beginTransaction();

        // Adresse aktualisieren oder erstellen
        if ($listing['adresse_id']) {
            $stmt = $pdo->prepare("
                UPDATE lg_adressen
                SET strasse = :strasse, hausnummer = :hausnummer,
                    plz = :plz, ort = :ort, land = :land
                WHERE adresse_id = :id
            ");
            $stmt->execute([
                ':strasse' => $_POST['strasse'] ?? '',
                ':hausnummer' => $_POST['hausnummer'] ?? '',
                ':plz' => $_POST['plz'] ?? '',
                ':ort' => $_POST['ort'] ?? '',
                ':land' => $_POST['land'] ?? 'DE',
                ':id' => $listing['adresse_id']
            ]);
            $adresse_id = $listing['adresse_id'];
        } elseif (!empty($_POST['plz']) && !empty($_POST['ort'])) {
            $stmt = $pdo->prepare("
                INSERT INTO lg_adressen (strasse, hausnummer, plz, ort, land)
                VALUES (:strasse, :hausnummer, :plz, :ort, :land)
            ");
            $stmt->execute([
                ':strasse' => $_POST['strasse'] ?? '',
                ':hausnummer' => $_POST['hausnummer'] ?? '',
                ':plz' => $_POST['plz'],
                ':ort' => $_POST['ort'],
                ':land' => $_POST['land'] ?? 'DE'
            ]);
            $adresse_id = $pdo->lastInsertId();
        } else {
            $adresse_id = null;
        }

        // Listing aktualisieren
        $stmt = $pdo->prepare("
            UPDATE em_listings SET
                metal_id = :metal_id,
                listing_type = :listing_type,
                quantity = :quantity,
                unit_id = :unit_id,
                price_per_unit = :price_per_unit,
                currency_code = :currency_code,
                purity = :purity,
                form = :form,
                manufacturer = :manufacturer,
                certification = :certification,
                title_de = :title_de,
                title_en = :title_en,
                description_de = :description_de,
                description_en = :description_en,
                adresse_id = :adresse_id,
                shipping_possible = :shipping_possible,
                shipping_cost = :shipping_cost,
                pickup_only = :pickup_only,
                price_negotiable = :price_negotiable,
                market_price_reference_id = :market_id,
                premium_over_spot = :premium_over_spot,
                aktiv = :aktiv,
                sold = :sold
            WHERE listing_id = :id AND user_id = :user_id
        ");

        $stmt->execute([
            ':metal_id' => (int)$_POST['metal_id'],
            ':listing_type' => $_POST['listing_type'] ?? 'verkauf',
            ':quantity' => (float)$_POST['quantity'],
            ':unit_id' => (int)$_POST['unit_id'],
            ':price_per_unit' => (float)$_POST['price_per_unit'],
            ':currency_code' => $_POST['currency_code'] ?? 'EUR',
            ':purity' => !empty($_POST['purity']) ? (float)$_POST['purity'] : 999.9,
            ':form' => $_POST['form'] ?? 'barren',
            ':manufacturer' => $_POST['manufacturer'] ?? null,
            ':certification' => $_POST['certification'] ?? null,
            ':title_de' => $_POST['title_de'],
            ':title_en' => $_POST['title_en'] ?? null,
            ':description_de' => $_POST['description_de'] ?? null,
            ':description_en' => $_POST['description_en'] ?? null,
            ':adresse_id' => $adresse_id,
            ':shipping_possible' => isset($_POST['shipping_possible']) ? 1 : 0,
            ':shipping_cost' => !empty($_POST['shipping_cost']) ? (float)$_POST['shipping_cost'] : null,
            ':pickup_only' => isset($_POST['pickup_only']) ? 1 : 0,
            ':price_negotiable' => isset($_POST['price_negotiable']) ? 1 : 0,
            ':market_id' => !empty($_POST['market_id']) ? (int)$_POST['market_id'] : null,
            ':premium_over_spot' => !empty($_POST['premium_over_spot']) ? (float)$_POST['premium_over_spot'] : null,
            ':aktiv' => isset($_POST['aktiv']) ? 1 : 0,
            ':sold' => isset($_POST['sold']) ? 1 : 0,
            ':id' => $listing_id,
            ':user_id' => $user['user_id']
        ]);

        // Neue Bilder hochladen
        if (isset($_FILES['images']) && $_FILES['images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            try {
                $imageUpload = new ImageUpload('metals');
                $uploaded_images = $imageUpload->uploadMultiple($_FILES['images'], 'em_' . $listing_id);

                // Höchste Sort-Order ermitteln
                $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM em_images WHERE listing_id = :id");
                $stmt->execute([':id' => $listing_id]);
                $max_order = $stmt->fetch()['max_order'] ?? -1;

                foreach ($uploaded_images as $index => $img) {
                    $stmt = $pdo->prepare("
                        INSERT INTO em_images (listing_id, filename, filepath, image_type, sort_order)
                        VALUES (:listing_id, :filename, :filepath, :image_type, :sort_order)
                    ");
                    $stmt->execute([
                        ':listing_id' => $listing_id,
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

        // Listing und Bilder neu laden
        $stmt = $pdo->prepare("
            SELECT l.*, a.strasse, a.hausnummer, a.plz, a.ort, a.land
            FROM em_listings l
            LEFT JOIN lg_adressen a ON l.adresse_id = a.adresse_id
            WHERE l.listing_id = :id
        ");
        $stmt->execute([':id' => $listing_id]);
        $listing = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT * FROM em_images WHERE listing_id = :id ORDER BY sort_order");
        $stmt->execute([':id' => $listing_id]);
        $images = $stmt->fetchAll();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Fehler: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<h2><i class="bi bi-pencil"></i> <?= t('edit_metal_offer') ?? 'Edelmetall-Angebot bearbeiten' ?></h2>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?= t('saved_success') ?? 'Erfolgreich gespeichert!' ?>
        <a href="em_meine_angebote.php" class="alert-link"><?= t('back_to_offers') ?? 'Zurück zu meinen Angeboten' ?></a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <!-- Metall & Typ -->
            <h4 class="mb-3"><i class="bi bi-gem"></i> <?= t('metal_details') ?? 'Metall-Details' ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label"><?= t('metal') ?? 'Metall' ?> *</label>
                    <select name="metal_id" class="form-select" required>
                        <?php foreach ($metals as $m): ?>
                            <option value="<?= $m['metal_id'] ?>" <?= $listing['metal_id'] == $m['metal_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['name_de']) ?> (<?= $m['symbol'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= t('listing_type') ?? 'Angebotstyp' ?> *</label>
                    <select name="listing_type" class="form-select" required>
                        <option value="verkauf" <?= $listing['listing_type'] == 'verkauf' ? 'selected' : '' ?>>Verkauf</option>
                        <option value="kauf" <?= $listing['listing_type'] == 'kauf' ? 'selected' : '' ?>>Kaufgesuch</option>
                        <option value="tausch" <?= $listing['listing_type'] == 'tausch' ? 'selected' : '' ?>>Tausch</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= t('form') ?? 'Form' ?> *</label>
                    <select name="form" class="form-select" required>
                        <option value="barren" <?= $listing['form'] == 'barren' ? 'selected' : '' ?>>Barren</option>
                        <option value="muenzen" <?= $listing['form'] == 'muenzen' ? 'selected' : '' ?>>Münzen</option>
                        <option value="granulat" <?= $listing['form'] == 'granulat' ? 'selected' : '' ?>>Granulat</option>
                        <option value="schmuck" <?= $listing['form'] == 'schmuck' ? 'selected' : '' ?>>Schmuck</option>
                        <option value="other" <?= $listing['form'] == 'other' ? 'selected' : '' ?>>Sonstige</option>
                    </select>
                </div>
            </div>

            <!-- Menge & Preis -->
            <h4 class="mb-3"><i class="bi bi-cash-coin"></i> <?= t('quantity_price') ?? 'Menge & Preis' ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label"><?= t('quantity') ?? 'Menge' ?> *</label>
                    <input type="number" name="quantity" class="form-control" step="0.000001" min="0" required
                           value="<?= htmlspecialchars($listing['quantity']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('unit') ?? 'Einheit' ?> *</label>
                    <select name="unit_id" class="form-select" required>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['unit_id'] ?>" <?= $listing['unit_id'] == $u['unit_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['name_de']) ?> (<?= $u['code'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('price_per_unit') ?? 'Preis pro Einheit' ?> *</label>
                    <input type="number" name="price_per_unit" class="form-control" step="0.01" min="0" required
                           value="<?= htmlspecialchars($listing['price_per_unit']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('currency') ?? 'Währung' ?></label>
                    <select name="currency_code" class="form-select">
                        <option value="EUR" <?= $listing['currency_code'] == 'EUR' ? 'selected' : '' ?>>EUR</option>
                        <option value="USD" <?= $listing['currency_code'] == 'USD' ? 'selected' : '' ?>>USD</option>
                        <option value="CHF" <?= $listing['currency_code'] == 'CHF' ? 'selected' : '' ?>>CHF</option>
                    </select>
                </div>
            </div>

            <!-- Qualität -->
            <h4 class="mb-3"><i class="bi bi-award"></i> <?= t('quality') ?? 'Qualität' ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label"><?= t('purity') ?? 'Feinheit' ?></label>
                    <input type="number" name="purity" class="form-control" step="0.001"
                           value="<?= htmlspecialchars($listing['purity']) ?>"
                           placeholder="z.B. 999.9 für 24k">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('manufacturer') ?? 'Hersteller/Prägestätte' ?></label>
                    <input type="text" name="manufacturer" class="form-control"
                           value="<?= htmlspecialchars($listing['manufacturer'] ?? '') ?>"
                           placeholder="z.B. Heraeus, PAMP">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('certification') ?? 'Zertifizierung' ?></label>
                    <input type="text" name="certification" class="form-control"
                           value="<?= htmlspecialchars($listing['certification'] ?? '') ?>"
                           placeholder="z.B. LBMA, SGE">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('market_reference') ?? 'Markt-Referenz' ?></label>
                    <select name="market_id" class="form-select">
                        <option value="">-- Kein Referenzmarkt --</option>
                        <?php foreach ($markets as $mk): ?>
                            <option value="<?= $mk['market_id'] ?>" <?= $listing['market_price_reference_id'] == $mk['market_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mk['name']) ?> (<?= $mk['code'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Titel & Beschreibung -->
            <h4 class="mb-3"><i class="bi bi-card-text"></i> <?= t('title_description') ?? 'Titel & Beschreibung' ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label"><?= t('title_de') ?? 'Titel (Deutsch)' ?> *</label>
                    <input type="text" name="title_de" class="form-control" required
                           value="<?= htmlspecialchars($listing['title_de']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= t('title_en') ?? 'Titel (English)' ?></label>
                    <input type="text" name="title_en" class="form-control"
                           value="<?= htmlspecialchars($listing['title_en'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= t('description_de') ?? 'Beschreibung (Deutsch)' ?></label>
                    <textarea name="description_de" class="form-control" rows="4"><?= htmlspecialchars($listing['description_de'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= t('description_en') ?? 'Beschreibung (English)' ?></label>
                    <textarea name="description_en" class="form-control" rows="4"><?= htmlspecialchars($listing['description_en'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Standort -->
            <h4 class="mb-3"><i class="bi bi-geo-alt"></i> <?= t('location') ?? 'Standort' ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <label class="form-label"><?= t('country') ?? 'Land' ?></label>
                    <select name="land" class="form-select">
                        <option value="DE" <?= ($listing['land'] ?? 'DE') == 'DE' ? 'selected' : '' ?>>Deutschland</option>
                        <option value="AT" <?= ($listing['land'] ?? '') == 'AT' ? 'selected' : '' ?>>Österreich</option>
                        <option value="CH" <?= ($listing['land'] ?? '') == 'CH' ? 'selected' : '' ?>>Schweiz</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?= t('zip') ?? 'PLZ' ?></label>
                    <input type="text" name="plz" class="form-control" value="<?= htmlspecialchars($listing['plz'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= t('city') ?? 'Ort' ?></label>
                    <input type="text" name="ort" class="form-control" value="<?= htmlspecialchars($listing['ort'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= t('street') ?? 'Straße' ?></label>
                    <input type="text" name="strasse" class="form-control" value="<?= htmlspecialchars($listing['strasse'] ?? '') ?>">
                </div>
            </div>

            <!-- Versand & Optionen -->
            <h4 class="mb-3"><i class="bi bi-truck"></i> <?= t('shipping_options') ?? 'Versand & Optionen' ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="form-check">
                        <input type="checkbox" name="shipping_possible" class="form-check-input" id="shipping_possible"
                               <?= $listing['shipping_possible'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="shipping_possible">
                            <?= t('shipping_possible') ?? 'Versand möglich' ?>
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('shipping_cost') ?? 'Versandkosten' ?></label>
                    <input type="number" name="shipping_cost" class="form-control" step="0.01"
                           value="<?= htmlspecialchars($listing['shipping_cost'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="pickup_only" class="form-check-input" id="pickup_only"
                               <?= $listing['pickup_only'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pickup_only">
                            <?= t('pickup_only') ?? 'Nur Abholung' ?>
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="price_negotiable" class="form-check-input" id="price_negotiable"
                               <?= $listing['price_negotiable'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="price_negotiable">
                            <?= t('price_negotiable') ?? 'Preis verhandelbar' ?>
                        </label>
                    </div>
                </div>
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
                             alt="<?= htmlspecialchars($listing['title_de']) ?>"
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

            <!-- Status -->
            <h4 class="mb-3"><i class="bi bi-toggle-on"></i> <?= t('status') ?? 'Status' ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="aktiv" class="form-check-input" id="aktiv"
                               <?= $listing['aktiv'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="aktiv">
                            <strong><?= t('offer_active') ?? 'Angebot aktiv' ?></strong>
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="sold" class="form-check-input" id="sold"
                               <?= $listing['sold'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="sold">
                            <strong><?= t('mark_as_sold') ?? 'Als verkauft markieren' ?></strong>
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> <?= t('btn_save') ?? 'Speichern' ?>
                </button>
                <a href="em_meine_angebote.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> <?= t('btn_cancel') ?? 'Abbrechen' ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
