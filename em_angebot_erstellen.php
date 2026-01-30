<?php
/**
 * em_angebot_erstellen.php - Neues Edelmetall-Angebot erstellen
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/image_upload.php';
require_once 'includes/lang.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = t('create_metal_offer') ?? 'Edelmetall-Angebot erstellen';

// Stammdaten laden
$metals = $pdo->query("SELECT * FROM em_metals WHERE aktiv = 1 ORDER BY sort_order")->fetchAll();
$units = $pdo->query("SELECT * FROM em_units WHERE aktiv = 1 ORDER BY sort_order")->fetchAll();
$markets = $pdo->query("SELECT * FROM em_markets WHERE aktiv = 1 ORDER BY code")->fetchAll();

$success = false;
$error = '';
$new_listing_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Optional: Adresse erstellen
        $adresse_id = null;
        if (!empty($_POST['plz']) && !empty($_POST['ort'])) {
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
        }

        // Listing erstellen
        $stmt = $pdo->prepare("
            INSERT INTO em_listings (
                user_id, metal_id, listing_type, quantity, unit_id,
                price_per_unit, currency_code, purity, form, manufacturer,
                certification, title_de, title_en, description_de, description_en,
                adresse_id, shipping_possible, shipping_cost, pickup_only,
                price_negotiable, market_price_reference_id, premium_over_spot, aktiv
            ) VALUES (
                :user_id, :metal_id, :listing_type, :quantity, :unit_id,
                :price_per_unit, :currency_code, :purity, :form, :manufacturer,
                :certification, :title_de, :title_en, :description_de, :description_en,
                :adresse_id, :shipping_possible, :shipping_cost, :pickup_only,
                :price_negotiable, :market_id, :premium_over_spot, 1
            )
        ");

        $stmt->execute([
            ':user_id' => $user['user_id'],
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
            ':premium_over_spot' => !empty($_POST['premium_over_spot']) ? (float)$_POST['premium_over_spot'] : null
        ]);

        $new_listing_id = $pdo->lastInsertId();

        // Bilder hochladen
        if (isset($_FILES['images']) && $_FILES['images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            try {
                $imageUpload = new ImageUpload('metals');
                $uploaded_images = $imageUpload->uploadMultiple($_FILES['images'], 'em_' . $new_listing_id);

                foreach ($uploaded_images as $index => $img) {
                    $stmt = $pdo->prepare("
                        INSERT INTO em_images (listing_id, filename, filepath, image_type, sort_order)
                        VALUES (:listing_id, :filename, :filepath, :image_type, :sort_order)
                    ");
                    $stmt->execute([
                        ':listing_id' => $new_listing_id,
                        ':filename' => $img['filename'],
                        ':filepath' => $img['filepath'],
                        ':image_type' => $index === 0 ? 'main' : 'detail',
                        ':sort_order' => $index
                    ]);
                }
            } catch (Exception $e) {
                // Fehler beim Upload, aber Angebot wurde erstellt
                $error = "Angebot erstellt, aber Fehler beim Bild-Upload: " . $e->getMessage();
            }
        }

        $pdo->commit();
        $success = true;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Fehler: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<h2><i class="bi bi-gem"></i> <?= t('create_metal_offer') ?? 'Edelmetall-Angebot erstellen' ?></h2>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i> <?= t('created_success') ?? 'Angebot erfolgreich erstellt!' ?>
        <a href="em_meine_angebote.php" class="alert-link"><?= t('back_to_offers') ?? 'Zurück zu meinen Angeboten' ?></a>
        |
        <a href="em_angebot_bearbeiten.php?id=<?= $new_listing_id ?>" class="alert-link"><?= t('edit_offer') ?? 'Angebot bearbeiten' ?></a>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!$success): ?>
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
                            <option value="<?= $m['metal_id'] ?>">
                                <?= htmlspecialchars($m['name_de']) ?> (<?= $m['symbol'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= t('listing_type') ?? 'Angebotstyp' ?> *</label>
                    <select name="listing_type" class="form-select" required>
                        <option value="verkauf">Verkauf</option>
                        <option value="kauf">Kaufgesuch</option>
                        <option value="tausch">Tausch</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= t('form') ?? 'Form' ?> *</label>
                    <select name="form" class="form-select" required>
                        <option value="barren">Barren</option>
                        <option value="muenzen">Münzen</option>
                        <option value="granulat">Granulat</option>
                        <option value="schmuck">Schmuck</option>
                        <option value="other">Sonstige</option>
                    </select>
                </div>
            </div>

            <!-- Menge & Preis -->
            <h4 class="mb-3"><i class="bi bi-cash-coin"></i> <?= t('quantity_price') ?? 'Menge & Preis' ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label"><?= t('quantity') ?? 'Menge' ?> *</label>
                    <input type="number" name="quantity" class="form-control" step="0.000001" min="0" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('unit') ?? 'Einheit' ?> *</label>
                    <select name="unit_id" class="form-select" required>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['unit_id'] ?>" <?= $u['code'] === 'oz' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['name_de']) ?> (<?= $u['code'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('price_per_unit') ?? 'Preis pro Einheit' ?> *</label>
                    <input type="number" name="price_per_unit" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('currency') ?? 'Währung' ?></label>
                    <select name="currency_code" class="form-select">
                        <option value="EUR">EUR</option>
                        <option value="USD">USD</option>
                        <option value="CHF">CHF</option>
                    </select>
                </div>
            </div>

            <!-- Qualität -->
            <h4 class="mb-3"><i class="bi bi-award"></i> <?= t('quality') ?? 'Qualität' ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label"><?= t('purity') ?? 'Feinheit' ?></label>
                    <input type="number" name="purity" class="form-control" step="0.001" value="999.9"
                           placeholder="z.B. 999.9 für 24k">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('manufacturer') ?? 'Hersteller/Prägestätte' ?></label>
                    <input type="text" name="manufacturer" class="form-control" placeholder="z.B. Heraeus, PAMP">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('certification') ?? 'Zertifizierung' ?></label>
                    <input type="text" name="certification" class="form-control" placeholder="z.B. LBMA, SGE">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('market_reference') ?? 'Markt-Referenz' ?></label>
                    <select name="market_id" class="form-select">
                        <option value="">-- Kein Referenzmarkt --</option>
                        <?php foreach ($markets as $mk): ?>
                            <option value="<?= $mk['market_id'] ?>">
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
                           placeholder="z.B. 1 oz Krügerrand Gold 2024">
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= t('title_en') ?? 'Titel (English)' ?></label>
                    <input type="text" name="title_en" class="form-control"
                           placeholder="z.B. 1 oz Krugerrand Gold 2024">
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= t('description_de') ?? 'Beschreibung (Deutsch)' ?></label>
                    <textarea name="description_de" class="form-control" rows="4"
                              placeholder="Detaillierte Beschreibung des Angebots..."></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= t('description_en') ?? 'Beschreibung (English)' ?></label>
                    <textarea name="description_en" class="form-control" rows="4"
                              placeholder="Detailed description of the offer..."></textarea>
                </div>
            </div>

            <!-- Standort (Optional) -->
            <h4 class="mb-3"><i class="bi bi-geo-alt"></i> <?= t('location') ?? 'Standort (Optional)' ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <label class="form-label"><?= t('country') ?? 'Land' ?></label>
                    <select name="land" class="form-select">
                        <option value="DE">Deutschland</option>
                        <option value="AT">Österreich</option>
                        <option value="CH">Schweiz</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?= t('zip') ?? 'PLZ' ?></label>
                    <input type="text" name="plz" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= t('city') ?? 'Ort' ?></label>
                    <input type="text" name="ort" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= t('street') ?? 'Straße' ?></label>
                    <input type="text" name="strasse" class="form-control">
                </div>
            </div>

            <!-- Versand & Optionen -->
            <h4 class="mb-3"><i class="bi bi-truck"></i> <?= t('shipping_options') ?? 'Versand & Optionen' ?></h4>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="form-check">
                        <input type="checkbox" name="shipping_possible" class="form-check-input" id="shipping_possible">
                        <label class="form-check-label" for="shipping_possible">
                            <?= t('shipping_possible') ?? 'Versand möglich' ?>
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= t('shipping_cost') ?? 'Versandkosten' ?></label>
                    <input type="number" name="shipping_cost" class="form-control" step="0.01">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="pickup_only" class="form-check-input" id="pickup_only" checked>
                        <label class="form-check-label" for="pickup_only">
                            <?= t('pickup_only') ?? 'Nur Abholung' ?>
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="price_negotiable" class="form-check-input" id="price_negotiable">
                        <label class="form-check-label" for="price_negotiable">
                            <?= t('price_negotiable') ?? 'Preis verhandelbar' ?>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Bilder hochladen -->
            <h4 class="mb-3 mt-4"><i class="bi bi-camera"></i> <?= t('photos') ?? 'Fotos' ?></h4>
            <div class="mb-4">
                <label class="form-label"><?= t('upload_photos') ?? 'Fotos hochladen' ?> (max. 5 Bilder, je max. 5MB)</label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                <small class="text-muted">
                    <?= t('photo_hint') ?? 'Erlaubt: JPG, PNG, GIF, WEBP. Das erste Bild wird als Hauptbild verwendet.' ?>
                </small>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> <?= t('btn_create') ?? 'Angebot erstellen' ?>
                </button>
                <a href="em_meine_angebote.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> <?= t('btn_cancel') ?? 'Abbrechen' ?>
                </a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
