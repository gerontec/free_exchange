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

    <!-- Price Ticker from London & Shanghai -->
    <?php
    // Fetch current prices from LBMA (London) and SGE (Shanghai)
    $ticker_sql = "SELECT
                    cp.price, cp.currency_code, cp.bid, cp.ask,
                    cp.change_24h, cp.change_percent_24h,
                    cp.high_24h, cp.low_24h, cp.updated_at,
                    m.symbol, m.name_de, m.name_en,
                    mk.code as market_code, mk.name as market_name, mk.city,
                    u.code as unit_code
                FROM em_current_prices cp
                JOIN em_metals m ON cp.metal_id = m.metal_id
                JOIN em_markets mk ON cp.market_id = mk.market_id
                JOIN em_units u ON cp.unit_id = u.unit_id
                WHERE mk.code IN ('LBMA', 'SGE')
                    AND cp.price_type = 'realtime'
                    AND m.aktiv = 1
                ORDER BY mk.code, m.sort_order";
    $ticker_prices = $pdo->query($ticker_sql)->fetchAll();

    if (!empty($ticker_prices)):
    ?>
    <div class="ticker-container" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h4 style="margin-top: 0; margin-bottom: 15px; font-size: 1.1em;">
            <i class="bi bi-graph-up"></i> <?= ($current_lang === 'en') ? 'Live Prices - London & Shanghai' : 'Live-Preise - London & Shanghai' ?>
        </h4>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            <?php
            $metal_icons = ['XAU' => '🥇', 'XAG' => '🥈', 'XPT' => '⚪', 'XPD' => '🔘'];
            foreach ($ticker_prices as $tp):
                $change_color = ($tp['change_percent_24h'] ?? 0) >= 0 ? '#4ade80' : '#f87171';
                $change_icon = ($tp['change_percent_24h'] ?? 0) >= 0 ? '▲' : '▼';
                $metal_name = ($current_lang === 'en') ? $tp['name_en'] : $tp['name_de'];
            ?>
            <div style="background: rgba(255,255,255,0.15); padding: 15px; border-radius: 6px; backdrop-filter: blur(10px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <div style="font-size: 1.1em; font-weight: bold;">
                        <?= $metal_icons[$tp['symbol']] ?? '💰' ?> <?= htmlspecialchars($metal_name) ?>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); padding: 3px 8px; border-radius: 12px; font-size: 0.8em;">
                        <?= htmlspecialchars($tp['city']) ?>
                    </div>
                </div>

                <div style="font-size: 1.8em; font-weight: bold; margin-bottom: 5px;">
                    <?= number_format($tp['price'], 2, ',', '.') ?> <?= htmlspecialchars($tp['currency_code']) ?>
                    <span style="font-size: 0.5em; opacity: 0.8;">/<?= htmlspecialchars($tp['unit_code']) ?></span>
                </div>

                <?php if ($tp['change_percent_24h'] !== null): ?>
                <div style="display: flex; gap: 10px; font-size: 0.9em; margin-bottom: 8px;">
                    <span style="color: <?= $change_color ?>; font-weight: bold;">
                        <?= $change_icon ?> <?= number_format(abs($tp['change_percent_24h']), 2) ?>%
                    </span>
                    <span style="opacity: 0.9;">
                        (<?= ($tp['change_24h'] >= 0 ? '+' : '') ?><?= number_format($tp['change_24h'], 2) ?> <?= htmlspecialchars($tp['currency_code']) ?>)
                    </span>
                </div>
                <?php endif; ?>

                <?php if ($tp['bid'] && $tp['ask']): ?>
                <div style="font-size: 0.8em; opacity: 0.9; display: flex; gap: 15px;">
                    <span><?= ($current_lang === 'en') ? 'Bid' : 'Geld' ?>: <?= number_format($tp['bid'], 2) ?></span>
                    <span><?= ($current_lang === 'en') ? 'Ask' : 'Brief' ?>: <?= number_format($tp['ask'], 2) ?></span>
                </div>
                <?php endif; ?>

                <div style="font-size: 0.7em; opacity: 0.7; margin-top: 8px;">
                    <?= ($current_lang === 'en') ? 'Updated' : 'Aktualisiert' ?>: <?= date('H:i', strtotime($tp['updated_at'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 15px; font-size: 0.85em; opacity: 0.8; text-align: center;">
            <i class="bi bi-info-circle"></i> <?= ($current_lang === 'en') ? 'Prices are for reference only. Actual trading prices may vary.' : 'Preise dienen nur zur Orientierung. Tatsächliche Handelspreise können abweichen.' ?>
        </div>
    </div>
    <?php endif; ?>

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

                <?php
                // Prüfe ob eingeloggt und ob eigenes Angebot
                $current_user = getCurrentUser();
                $is_own_listing = $current_user && ($l['user_id'] == $current_user['user_id']);

                if ($is_own_listing):
                ?>
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <a href="em_angebot_bearbeiten.php?id=<?= $l['listing_id'] ?>" class="btn btn-primary" style="text-decoration: none;">
                            <i class="bi bi-pencil"></i> <?= ($current_lang === 'en') ? 'Edit' : 'Bearbeiten' ?>
                        </a>
                        <a href="em_meine_angebote.php" class="btn btn-secondary" style="text-decoration: none;">
                            <i class="bi bi-list"></i> <?= ($current_lang === 'en') ? 'My Offers' : 'Meine Angebote' ?>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="em_kontakt.php?id=<?= $l['listing_id'] ?>" class="btn" style="display:inline-block; margin-top:10px; background:#f39c12; color:white; padding:5px 10px; text-decoration:none; border-radius:3px;">
                        <?= ($current_lang === 'en') ? 'Contact Seller' : 'Verkäufer kontaktieren' ?>
                    </a>
                <?php endif; ?>
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
