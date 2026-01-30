<?php
/**
 * index.php - Multi-Exchange Platform (Lagerraum, Edelmetalle, Waffen)
 */
require_once 'includes/config.php';

// 1. Sprache festlegen (Default: de)
$current_lang = $_SESSION['lang'] ?? 'de';
$suffix = ($current_lang === 'en') ? '_en' : '_de';

// Exchange-Typ bestimmen
$exchange = $_GET['exchange'] ?? 'storage';

// ============================================================================
// EDELMETALL-BÖRSE (exchange=metals)
// ============================================================================
if ($exchange === 'metals') {
    // Filter-Parameter
    $metal_id = !empty($_GET['metal_id']) ? (int)$_GET['metal_id'] : null;
    $listing_type = $_GET['listing_type'] ?? '';
    $form = $_GET['form'] ?? '';
    $price_min = !empty($_GET['price_min']) ? (float)$_GET['price_min'] : null;
    $price_max = !empty($_GET['price_max']) ? (float)$_GET['price_max'] : null;

    $pageTitle = ($current_lang === 'en') ? 'Precious Metals Exchange' : 'Edelmetall-Börse';

    // Stammdaten laden
    $metals = $pdo->query("SELECT * FROM em_metals WHERE aktiv = 1 ORDER BY sort_order")->fetchAll();
    $units = $pdo->query("SELECT * FROM em_units WHERE aktiv = 1 ORDER BY sort_order")->fetchAll();

    // SQL Query für Edelmetall-Angebote
    $sql = "SELECT l.*,
                   m.symbol as metal_symbol, m.name_de as metal_name, m.name_en as metal_name_en,
                   u.code as unit_code, u.name_de as unit_name,
                   usr.name as seller_name,
                   a.plz, a.ort, a.land,
                   DATEDIFF(NOW(), l.erstellt_am) as tage_alt,
                   (SELECT COUNT(*) FROM em_images WHERE listing_id = l.listing_id) as image_count
            FROM em_listings l
            JOIN em_metals m ON l.metal_id = m.metal_id
            JOIN em_units u ON l.unit_id = u.unit_id
            JOIN lg_users usr ON l.user_id = usr.user_id
            LEFT JOIN lg_adressen a ON l.adresse_id = a.adresse_id
            WHERE l.aktiv = 1 AND l.sold = 0 AND usr.aktiv = 1";

    $params = [];

    if ($metal_id) {
        $sql .= " AND l.metal_id = :metal_id";
        $params[':metal_id'] = $metal_id;
    }
    if ($listing_type) {
        $sql .= " AND l.listing_type = :listing_type";
        $params[':listing_type'] = $listing_type;
    }
    if ($form) {
        $sql .= " AND l.form = :form";
        $params[':form'] = $form;
    }
    if ($price_min) {
        $sql .= " AND l.price_per_unit >= :price_min";
        $params[':price_min'] = $price_min;
    }
    if ($price_max) {
        $sql .= " AND l.price_per_unit <= :price_max";
        $params[':price_max'] = $price_max;
    }

    $sql .= " ORDER BY l.erstellt_am DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $listings = $stmt->fetchAll();

    include 'includes/header.php';
    ?>

<div class="container">
    <h2><?= ($current_lang === 'en') ? 'Precious Metals Exchange' : 'Edelmetall-Börse' ?></h2>

    <!-- Filter -->
    <div class="filter-box" style="background: #f4f4f4; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <form method="GET">
            <input type="hidden" name="exchange" value="metals">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                <select name="metal_id" class="form-select">
                    <option value=""><?= ($current_lang === 'en') ? 'All Metals' : 'Alle Metalle' ?></option>
                    <?php foreach ($metals as $m): ?>
                        <option value="<?= $m['metal_id'] ?>" <?= $metal_id == $m['metal_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['name_de']) ?> (<?= $m['symbol'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="listing_type" class="form-select">
                    <option value=""><?= ($current_lang === 'en') ? 'All Types' : 'Alle Typen' ?></option>
                    <option value="verkauf" <?= $listing_type === 'verkauf' ? 'selected' : '' ?>>Verkauf</option>
                    <option value="kauf" <?= $listing_type === 'kauf' ? 'selected' : '' ?>>Kaufgesuch</option>
                    <option value="tausch" <?= $listing_type === 'tausch' ? 'selected' : '' ?>>Tausch</option>
                </select>
                <select name="form" class="form-select">
                    <option value=""><?= ($current_lang === 'en') ? 'All Forms' : 'Alle Formen' ?></option>
                    <option value="barren" <?= $form === 'barren' ? 'selected' : '' ?>>Barren</option>
                    <option value="muenzen" <?= $form === 'muenzen' ? 'selected' : '' ?>>Münzen</option>
                    <option value="granulat" <?= $form === 'granulat' ? 'selected' : '' ?>>Granulat</option>
                    <option value="schmuck" <?= $form === 'schmuck' ? 'selected' : '' ?>>Schmuck</option>
                </select>
                <input type="number" name="price_min" placeholder="Min Preis" value="<?= $price_min ?>" class="form-control">
                <input type="number" name="price_max" placeholder="Max Preis" value="<?= $price_max ?>" class="form-control">
                <button type="submit" class="btn btn-primary"><?= ($current_lang === 'en') ? 'Search' : 'Suchen' ?></button>
            </div>
        </form>
    </div>

    <p><?= count($listings) ?> <?= ($current_lang === 'en') ? 'offers found' : 'Angebote gefunden' ?></p>

    <!-- Listings -->
    <div class="grid">
        <?php if (empty($listings)): ?>
            <div class="alert alert-info">
                <?= ($current_lang === 'en') ? 'No offers found.' : 'Keine Angebote gefunden.' ?>
            </div>
        <?php endif; ?>

        <?php foreach ($listings as $l): ?>
            <div class="card" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">
                <h3>
                    <?php
                        $metal_icons = ['XAU' => '🥇', 'XAG' => '🥈', 'XPT' => '⚪', 'XPD' => '🔘'];
                        echo $metal_icons[$l['metal_symbol']] ?? '💰';
                    ?>
                    <?= htmlspecialchars($l['title_de']) ?>
                </h3>

                <p>
                    <strong><?= htmlspecialchars($l['metal_name']) ?></strong> |
                    <?= number_format($l['quantity'], 2) ?> <?= $l['unit_code'] ?>
                    <?php if ($l['purity']): ?>
                        | Feinheit: <?= $l['purity'] ?>
                    <?php endif; ?>
                </p>

                <?php if ($l['description_de']): ?>
                <div class="description" style="font-style: italic; color: #555; margin: 10px 0;">
                    <?= nl2br(htmlspecialchars(substr($l['description_de'], 0, 200))) ?>
                    <?= strlen($l['description_de']) > 200 ? '...' : '' ?>
                </div>
                <?php endif; ?>

                <div class="details" style="margin-top: 10px;">
                    <?php
                        $forms = ['barren' => 'Barren', 'muenzen' => 'Münzen', 'granulat' => 'Granulat', 'schmuck' => 'Schmuck', 'other' => 'Sonstige'];
                        $types = ['verkauf' => 'Verkauf', 'kauf' => 'Kaufgesuch', 'tausch' => 'Tausch'];
                    ?>
                    <span class="badge bg-secondary"><?= $forms[$l['form']] ?? $l['form'] ?></span>
                    <span class="badge bg-info"><?= $types[$l['listing_type']] ?? $l['listing_type'] ?></span>
                    <?php if ($l['manufacturer']): ?>
                        <span class="badge bg-light text-dark"><?= htmlspecialchars($l['manufacturer']) ?></span>
                    <?php endif; ?>
                    <?php if ($l['price_negotiable']): ?>
                        <span class="badge bg-warning">VB</span>
                    <?php endif; ?>
                </div>

                <div class="price" style="font-weight: bold; font-size: 1.2em; margin-top: 10px; color: #2c3e50;">
                    <?= number_format($l['price_per_unit'], 2, ',', '.') ?> <?= $l['currency_code'] ?>/<?= $l['unit_code'] ?>
                    <small>(Total: <?= number_format($l['total_price'], 2, ',', '.') ?> <?= $l['currency_code'] ?>)</small>
                </div>

                <div class="footer" style="margin-top: 15px; font-size: 0.85em; border-top: 1px solid #eee; padding-top: 10px;">
                    👤 <?= htmlspecialchars($l['seller_name']) ?>
                    <?php if ($l['ort']): ?>
                        | 📍 <?= htmlspecialchars($l['plz'] . ' ' . $l['ort']) ?>
                    <?php endif; ?>
                    | ⏳ <?= $l['tage_alt'] ?> <?= ($current_lang === 'en') ? 'days ago' : 'Tage' ?>
                </div>

                <a href="em_kontakt.php?id=<?= $l['listing_id'] ?>" class="btn" style="display:inline-block; margin-top:10px; background:#f39c12; color:white; padding:5px 10px; text-decoration:none; border-radius:3px;">
                    <?= ($current_lang === 'en') ? 'Contact Seller' : 'Verkäufer kontaktieren' ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
    include 'includes/footer.php';
    exit; // Wichtig: Hier beenden für metals
}

// ============================================================================
// LAGERRAUM-BÖRSE (Standard / exchange=storage)
// ============================================================================

// 2. Filter-Parameter (Sanitized)
$plz = $_GET['plz'] ?? '';
$ort = $_GET['ort'] ?? '';
$land = $_GET['land'] ?? 'DE';
$qm_min = !empty($_GET['qm_min']) ? (float)$_GET['qm_min'] : null;
$qm_max = !empty($_GET['qm_max']) ? (float)$_GET['qm_max'] : null;
$preis_max = !empty($_GET['preis_max']) ? (float)$_GET['preis_max'] : null;

$pageTitle = ($current_lang === 'en') ? 'Storage Offers' : 'Lagerraum-Angebote';

// 3. SQL Query direkt auf die Tabellen
$sql = "SELECT l.*, a.strasse, a.hausnummer, a.plz, a.ort, a.land, u.name as anbieter_name,
               DATEDIFF(DATE_ADD(l.erstellt_am, INTERVAL 60 DAY), NOW()) as tage_verbleibend
        FROM lg_lagerraeume l
        JOIN lg_adressen a ON l.adresse_id = a.adresse_id
        JOIN lg_users u ON l.anbieter_id = u.user_id
        WHERE l.typ = 'angebot' AND l.aktiv = 1 AND u.aktiv = 1";

$params = [];

if ($plz) {
    $sql .= " AND a.plz LIKE :plz";
    $params[':plz'] = $plz . '%';
}
if ($ort) {
    $sql .= " AND a.ort LIKE :ort";
    $params[':ort'] = '%' . $ort . '%';
}
if ($land) {
    $sql .= " AND a.land = :land";
    $params[':land'] = $land;
}
if ($qm_min) {
    $sql .= " AND l.qm_gesamt >= :qm_min";
    $params[':qm_min'] = $qm_min;
}
if ($qm_max) {
    $sql .= " AND l.qm_gesamt <= :qm_max";
    $params[':qm_max'] = $qm_max;
}
if ($preis_max) {
    $sql .= " AND l.preis_pro_qm <= :preis_max";
    $params[':preis_max'] = $preis_max;
}

$sql .= " ORDER BY l.erstellt_am DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$angebote = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container">
    <h2><?= ($current_lang === 'en') ? 'Available Storage Units' : 'Verfügbare Lagerräume' ?></h2>

    <div class="filter-box" style="background: #f4f4f4; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <form method="GET">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                <input type="text" name="plz" placeholder="PLZ" value="<?= htmlspecialchars($plz) ?>">
                <input type="text" name="ort" placeholder="Ort" value="<?= htmlspecialchars($ort) ?>">
                <select name="land">
                    <option value="DE" <?= $land == 'DE' ? 'selected' : '' ?>>Deutschland</option>
                    <option value="AT" <?= $land == 'AT' ? 'selected' : '' ?>>Österreich</option>
                    <option value="CH" <?= $land == 'CH' ? 'selected' : '' ?>>Schweiz</option>
                </select>
                <input type="number" name="qm_min" placeholder="Min m²" value="<?= $qm_min ?>">
                <input type="number" name="preis_max" placeholder="Max €/m²" value="<?= $preis_max ?>">
                <button type="submit" class="btn"><?= ($current_lang === 'en') ? 'Search' : 'Suchen' ?></button>
            </div>
        </form>
    </div>

    <p><?= count($angebote) ?> <?= ($current_lang === 'en') ? 'offers found' : 'Angebote gefunden' ?></p>

    <div class="grid">
        <?php foreach ($angebote as $a): ?>
            <div class="card" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">
                <h3>📦 <?= htmlspecialchars($a['ort']) ?> - <?= number_format($a['qm_gesamt'], 1, ',', '.') ?> m²</h3>
                
                <p><strong>📍 <?= htmlspecialchars($a['plz'] . ' ' . $a['ort']) ?></strong> (<?= htmlspecialchars($a['land']) ?>)</p>
                
                <div class="description" style="font-style: italic; color: #555;">
                    <?php 
                        // Sprach-Logik: Falls englisch gewünscht aber leer, Fallback auf Deutsch
                        $desc = (!empty($a['bemerkung' . $suffix])) ? $a['bemerkung' . $suffix] : $a['bemerkung_de'];
                        echo nl2br(htmlspecialchars($desc));
                    ?>
                </div>

                <div class="details" style="margin-top: 10px;">
                    <span class="badge"><?= $a['beheizt'] ? '🔥 Beheizt' : '❄️ Unbeheizt' ?></span>
                    <span class="badge"><?= $a['zugang_24_7'] ? '🕐 24/7' : '🔒 Eingeschränkt' ?></span>
                </div>

                <div class="price" style="font-weight: bold; font-size: 1.2em; margin-top: 10px; color: #2c3e50;">
                    <?= number_format($a['preis_pro_qm'], 2, ',', '.') ?> €/m² 
                    <small>(Total: <?= number_format($a['preis_gesamt'], 2, ',', '.') ?> €)</small>
                </div>

                <div class="footer" style="margin-top: 15px; font-size: 0.85em; border-top: 1px solid #eee; padding-top: 10px;">
                    👤 <?= htmlspecialchars($a['anbieter_name']) ?> | 
                    ⏳ <?= ($current_lang === 'en') ? 'Expires in: ' : 'Läuft noch: ' ?> <?= $a['tage_verbleibend'] ?> <?= ($current_lang === 'en') ? 'days' : 'Tage' ?>
                </div>
                
                <a href="kontakt.php?id=<?= $a['lagerraum_id'] ?>" class="btn" style="display:inline-block; margin-top:10px; background:#3498db; color:white; padding:5px 10px; text-decoration:none;">
                    <?= ($current_lang === 'en') ? 'Contact' : 'Kontakt aufnehmen' ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
