<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/lang.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = 'Waffen-Angebot einstellen';

$success = false;
$error = '';

// Kategorien und Hersteller laden
$categories = $pdo->query("SELECT * FROM gun_categories WHERE aktiv = 1 ORDER BY sort_order")->fetchAll();
$manufacturers = $pdo->query("SELECT * FROM gun_manufacturers WHERE aktiv = 1 ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Optional: Adresse erstellen falls angegeben
        $adresse_id = null;
        if (!empty($_POST['plz']) && !empty($_POST['ort'])) {
            $stmt = $pdo->prepare("
                INSERT INTO lg_adressen (strasse, hausnummer, plz, ort, land)
                VALUES (:strasse, :hausnummer, :plz, :ort, :land)
            ");
            $stmt->execute([
                ':strasse' => $_POST['strasse'] ?: '',
                ':hausnummer' => $_POST['hausnummer'] ?: '',
                ':plz' => $_POST['plz'],
                ':ort' => $_POST['ort'],
                ':land' => $_POST['land'] ?: 'DE'
            ]);
            $adresse_id = $pdo->lastInsertId();
        }

        // Waffen-Angebot erstellen (ohne description Spalte)
        $stmt = $pdo->prepare("
            INSERT INTO gun_listings (
                seller_id, category_id, manufacturer_id, adresse_id,
                title, model, caliber, barrel_length_cm, weight_kg, capacity,
                year_manufactured, serial_number, condition_rating, rounds_fired,
                price, price_negotiable, includes_case, includes_magazines,
                includes_accessories, has_wbk, wbk_transferable, proof_marks,
                shipping_possible, shipping_cost, pickup_only,
                listing_type, expires_at
            ) VALUES (
                :seller_id, :category_id, :manufacturer_id, :adresse_id,
                :title, :model, :caliber, :barrel_length_cm, :weight_kg, :capacity,
                :year_manufactured, :serial_number, :condition_rating, :rounds_fired,
                :price, :price_negotiable, :includes_case, :includes_magazines,
                :includes_accessories, :has_wbk, :wbk_transferable, :proof_marks,
                :shipping_possible, :shipping_cost, :pickup_only,
                :listing_type, DATE_ADD(NOW(), INTERVAL 60 DAY)
            )
        ");
        $stmt->execute([
            ':seller_id' => $user['user_id'],
            ':category_id' => $_POST['category_id'],
            ':manufacturer_id' => $_POST['manufacturer_id'] ?: null,
            ':adresse_id' => $adresse_id,
            ':title' => $_POST['title'],
            ':model' => $_POST['model'] ?: null,
            ':caliber' => $_POST['caliber'] ?: null,
            ':barrel_length_cm' => $_POST['barrel_length_cm'] ?: null,
            ':weight_kg' => $_POST['weight_kg'] ?: null,
            ':capacity' => $_POST['capacity'] ?: null,
            ':year_manufactured' => $_POST['year_manufactured'] ?: null,
            ':serial_number' => $_POST['serial_number'] ?: null,
            ':condition_rating' => $_POST['condition_rating'],
            ':rounds_fired' => $_POST['rounds_fired'] ?: null,
            ':price' => $_POST['price'],
            ':price_negotiable' => isset($_POST['price_negotiable']) ? 1 : 0,
            ':includes_case' => isset($_POST['includes_case']) ? 1 : 0,
            ':includes_magazines' => $_POST['includes_magazines'] ?: 0,
            ':includes_accessories' => $_POST['includes_accessories'] ?: null,
            ':has_wbk' => isset($_POST['has_wbk']) ? 1 : 0,
            ':wbk_transferable' => isset($_POST['wbk_transferable']) ? 1 : 0,
            ':proof_marks' => $_POST['proof_marks'] ?: null,
            ':shipping_possible' => isset($_POST['shipping_possible']) ? 1 : 0,
            ':shipping_cost' => $_POST['shipping_cost'] ?: null,
            ':pickup_only' => isset($_POST['pickup_only']) ? 1 : 0,
            ':listing_type' => $_POST['listing_type'] ?: 'verkauf'
        ]);

        $pdo->commit();
        $success = true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Fehler: " . $e->getMessage();
        error_log("gun_angebot_erstellen.php error: " . $e->getMessage());
    }
}

include 'includes/header.php';
?>

<h2><i class="bi bi-crosshair"></i> Waffen-Angebot einstellen</h2>

<div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle"></i> <strong>Wichtiger Hinweis:</strong>
    <ul class="mb-0 mt-2">
        <li>Waffen dürfen nur zwischen berechtigten Personen gehandelt werden</li>
        <li>Beachten Sie die gesetzlichen Bestimmungen des Waffengesetzes (WaffG)</li>
        <li>Angebote werden nach 60 Tagen automatisch deaktiviert</li>
        <li>Persönliche Übergabe und Prüfung der Dokumente wird empfohlen</li>
    </ul>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i> Ihr Angebot wurde erfolgreich veröffentlicht!
        <a href="gun_meine_angebote.php" class="alert-link">Zu meinen Angeboten</a>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <!-- Grunddaten -->
            <h4 class="mb-3">Grunddaten</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <label class="form-label">Titel / Bezeichnung *</label>
                    <input type="text" name="title" class="form-control" 
                           placeholder="z.B. Glock 17 Gen5" required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Kategorie *</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Bitte wählen...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>">
                                <?= htmlspecialchars($cat['name_de']) ?>
                                <?php if ($cat['license_required'] !== 'none'): ?>
                                    (<?= $cat['license_required'] ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Hersteller</label>
                    <select name="manufacturer_id" class="form-select">
                        <option value="">Bitte wählen...</option>
                        <?php foreach ($manufacturers as $man): ?>
                            <option value="<?= $man['manufacturer_id'] ?>">
                                <?= htmlspecialchars($man['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Modell</label>
                    <input type="text" name="model" class="form-control" placeholder="z.B. Gen5">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Kaliber *</label>
                    <input type="text" name="caliber" class="form-control" 
                           placeholder="z.B. 9mm Luger" required>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Lauflänge (cm)</label>
                    <input type="number" name="barrel_length_cm" class="form-control" 
                           step="0.1" placeholder="z.B. 11.4">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Gewicht (kg)</label>
                    <input type="number" name="weight_kg" class="form-control" 
                           step="0.001" placeholder="z.B. 0.645">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Kapazität (Schuss)</label>
                    <input type="number" name="capacity" class="form-control" 
                           placeholder="z.B. 17">
                </div>
            </div>
            
            <!-- Zustand -->
            <h4 class="mb-3">Zustand</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Zustand *</label>
                    <select name="condition_rating" class="form-select" required>
                        <option value="neu">Neu / Unbenutzt</option>
                        <option value="wie_neu">Wie neu</option>
                        <option value="sehr_gut" selected>Sehr gut</option>
                        <option value="gut">Gut</option>
                        <option value="gebraucht">Gebraucht</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Baujahr</label>
                    <input type="number" name="year_manufactured" class="form-control" 
                           min="1800" max="2026" placeholder="z.B. 2020">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Verschossene Patronen (ca.)</label>
                    <input type="number" name="rounds_fired" class="form-control" 
                           placeholder="z.B. 500">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Seriennummer</label>
                    <input type="text" name="serial_number" class="form-control" 
                           placeholder="Seriennummer (wird nicht öffentlich angezeigt)">
                    <small class="text-muted">Wird nur bei ernsthaftem Interesse mitgeteilt</small>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Beschusszeichen</label>
                    <input type="text" name="proof_marks" class="form-control" 
                           placeholder="z.B. Ulm 2020">
                </div>
            </div>
            
            <!-- Preis & Lieferumfang -->
            <h4 class="mb-3">Preis & Lieferumfang</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Preis (€) *</label>
                    <input type="number" name="price" class="form-control" 
                           step="0.01" placeholder="z.B. 599.00" required>
                </div>
                
                <div class="col-md-4">
                    <div class="form-check mt-4 pt-2">
                        <input type="checkbox" name="price_negotiable" class="form-check-input" 
                               id="price_negotiable">
                        <label class="form-check-label" for="price_negotiable">
                            Verhandlungsbasis (VB)
                        </label>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Anzahl Magazine</label>
                    <input type="number" name="includes_magazines" class="form-control" 
                           min="0" value="0">
                </div>
                
                <div class="col-md-12">
                    <div class="form-check">
                        <input type="checkbox" name="includes_case" class="form-check-input" 
                               id="includes_case">
                        <label class="form-check-label" for="includes_case">
                            Inkl. Koffer/Tasche
                        </label>
                    </div>
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">Weiteres Zubehör</label>
                    <textarea name="includes_accessories" class="form-control" rows="2"
                              placeholder="z.B. Holster, Reinigungsset, Wechsellauf..."></textarea>
                </div>
            </div>
            
            <!-- Dokumente & Versand -->
            <h4 class="mb-3">Dokumente & Versand</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <div class="form-check">
                        <input type="checkbox" name="has_wbk" class="form-check-input" id="has_wbk">
                        <label class="form-check-label" for="has_wbk">
                            Waffe ist auf WBK eingetragen
                        </label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="wbk_transferable" class="form-check-input" 
                               id="wbk_transferable">
                        <label class="form-check-label" for="wbk_transferable">
                            WBK-Übertragung ist vorbereitet
                        </label>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" name="pickup_only" class="form-check-input" 
                               id="pickup_only" checked>
                        <label class="form-check-label" for="pickup_only">
                            Nur Abholung
                        </label>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" name="shipping_possible" class="form-check-input" 
                               id="shipping_possible">
                        <label class="form-check-label" for="shipping_possible">
                            Versand möglich
                        </label>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Versandkosten (€)</label>
                    <input type="number" name="shipping_cost" class="form-control" 
                           step="0.01" placeholder="z.B. 15.00">
                </div>
            </div>
            
            <!-- Standort -->
            <h4 class="mb-3">Standort (optional)</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <label class="form-label">Land</label>
                    <select name="land" class="form-select">
                        <?php foreach ($LAENDER as $code => $name): ?>
                            <option value="<?= $code ?>" <?= $code === 'DE' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">PLZ</label>
                    <input type="text" name="plz" class="form-control">
                </div>
                <div class="col-md-7">
                    <label class="form-label">Ort</label>
                    <input type="text" name="ort" class="form-control">
                </div>
            </div>
            
            <!-- Beschreibung -->
            <h4 class="mb-3">Beschreibung (optional)</h4>
            <div class="mb-4">
                <textarea name="description" class="form-control" rows="6"
                          placeholder="Detaillierte Beschreibung der Waffe, Zustand, Besonderheiten, etc."></textarea>
                <small class="text-muted">Hinweis: Beschreibungsfeld wird derzeit nicht gespeichert (in Entwicklung)</small>
            </div>
            
            <!-- Angebotstyp -->
            <div class="mb-4">
                <label class="form-label">Angebotstyp</label>
                <div class="form-check">
                    <input type="radio" name="listing_type" value="verkauf" 
                           class="form-check-input" id="type_verkauf" checked>
                    <label class="form-check-label" for="type_verkauf">Verkauf</label>
                </div>
                <div class="form-check">
                    <input type="radio" name="listing_type" value="tausch" 
                           class="form-check-input" id="type_tausch">
                    <label class="form-check-label" for="type_tausch">Tausch</label>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Angemeldet als: <strong><?= htmlspecialchars($user['name']) ?></strong>
            </div>
            
            <button type="submit" class="btn btn-danger btn-lg">
                <i class="bi bi-check-circle"></i> Angebot veröffentlichen
            </button>
            <a href="gun_index.php" class="btn btn-outline-secondary btn-lg">Abbrechen</a>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
