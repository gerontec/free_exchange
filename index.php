<?php
/**
 * index.php - Dreisprachige Multi-Börse (Lagerraum, Waffen, Edelmetalle)
 */
require_once 'includes/config.php';

// 1. Sprache festlegen (Default: de)
$current_lang = $_SESSION['lang'] ?? 'de';
$suffix = ($current_lang === 'en') ? '_en' : '_de';

// 2. Aktuelle Börse ermitteln (Default: storage)
$active_exchange = $_GET['exchange'] ?? 'storage';
if (!in_array($active_exchange, ['storage', 'guns', 'metals'])) {
    $active_exchange = 'storage';
}

// 3. Filter-Parameter je nach Börse
$filters = [];

// =============================================================================
// LAGERRAUM-BÖRSE
// =============================================================================
if ($active_exchange === 'storage') {
    $pageTitle = ($current_lang === 'en') ? 'Storage Offers' : 'Lagerraum-Angebote';

    $plz = $_GET['plz'] ?? '';
    $ort = $_GET['ort'] ?? '';
    $land = $_GET['land'] ?? 'DE';
    $qm_min = !empty($_GET['qm_min']) ? (float)$_GET['qm_min'] : null;
    $qm_max = !empty($_GET['qm_max']) ? (float)$_GET['qm_max'] : null;
    $preis_max = !empty($_GET['preis_max']) ? (float)$_GET['preis_max'] : null;

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
}

// =============================================================================
// WAFFEN-BÖRSE
// =============================================================================
elseif ($active_exchange === 'guns') {
    $pageTitle = ($current_lang === 'en') ? 'Gun Offers' : 'Waffen-Angebote';

    $category_id = $_GET['category'] ?? '';
    $manufacturer_id = $_GET['manufacturer'] ?? '';
    $caliber = $_GET['caliber'] ?? '';
    $price_max = $_GET['price_max'] ?? '';
    $condition = $_GET['condition'] ?? '';

    $sql = "SELECT * FROM gun_v_active_listings WHERE 1=1";
    $params = [];

    if ($category_id) {
        $sql .= " AND category_id = :category_id";
        $params[':category_id'] = $category_id;
    }
    if ($manufacturer_id) {
        $sql .= " AND manufacturer_id = :manufacturer_id";
        $params[':manufacturer_id'] = $manufacturer_id;
    }
    if ($caliber) {
        $sql .= " AND caliber LIKE :caliber";
        $params[':caliber'] = '%' . $caliber . '%';
    }
    if ($price_max) {
        $sql .= " AND price <= :price_max";
        $params[':price_max'] = $price_max;
    }
    if ($condition) {
        $sql .= " AND condition_rating = :condition";
        $params[':condition'] = $condition;
    }

    $sql .= " ORDER BY erstellt_am DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $angebote = $stmt->fetchAll();

    // Kategorien und Hersteller für Filter
    $categories = $pdo->query("SELECT * FROM gun_categories WHERE aktiv = 1 ORDER BY sort_order")->fetchAll();
    $manufacturers = $pdo->query("SELECT * FROM gun_manufacturers WHERE aktiv = 1 ORDER BY name")->fetchAll();
}

// =============================================================================
// EDELMETALL-BÖRSE
// =============================================================================
elseif ($active_exchange === 'metals') {
    $pageTitle = ($current_lang === 'en') ? 'Precious Metals Offers' : 'Edelmetall-Angebote';

    $metal_id = $_GET['metal'] ?? '';
    $listing_type = $_GET['listing_type'] ?? 'verkauf';
    $form = $_GET['form'] ?? '';
    $price_max = $_GET['price_max'] ?? '';
    $purity_min = $_GET['purity_min'] ?? '';

    $sql = "SELECT l.*,
                   m.symbol as metal_symbol, m.name_de as metal_name, m.name_en as metal_name_en,
                   u.code as unit_code, u.name_de as unit_name, u.name_en as unit_name_en,
                   seller.name as seller_name, seller.email as seller_email,
                   a.plz, a.ort, a.land,
                   DATEDIFF(NOW(), l.erstellt_am) as tage_alt
            FROM em_listings l
            JOIN em_metals m ON l.metal_id = m.metal_id
            JOIN em_units u ON l.unit_id = u.unit_id
            JOIN lg_users seller ON l.user_id = seller.user_id
            LEFT JOIN lg_adressen a ON l.adresse_id = a.adresse_id
            WHERE l.aktiv = 1 AND l.sold = 0 AND seller.aktiv = 1";

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
    if ($price_max) {
        $sql .= " AND l.price_per_unit <= :price_max";
        $params[':price_max'] = $price_max;
    }
    if ($purity_min) {
        $sql .= " AND l.purity >= :purity_min";
        $params[':purity_min'] = $purity_min;
    }

    $sql .= " ORDER BY l.erstellt_am DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $angebote = $stmt->fetchAll();

    // Metalle für Filter
    $metals = $pdo->query("SELECT * FROM em_metals WHERE aktiv = 1 ORDER BY sort_order")->fetchAll();
}

include 'includes/header.php';
?>

<div class="container">

    <?php if ($active_exchange === 'storage'): ?>
        <!-- ============================================================= -->
        <!-- LAGERRAUM-BÖRSE -->
        <!-- ============================================================= -->
        <h2><?= ($current_lang === 'en') ? 'Available Storage Units' : 'Verfügbare Lagerräume' ?></h2>

        <div class="filter-box" style="background: #f4f4f4; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <form method="GET">
                <input type="hidden" name="exchange" value="storage">
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

    <?php elseif ($active_exchange === 'guns'): ?>
        <!-- ============================================================= -->
        <!-- WAFFEN-BÖRSE -->
        <!-- ============================================================= -->
        <h2>
            <i class="bi bi-crosshair"></i> <?= ($current_lang === 'en') ? 'Gun Offers' : 'Waffen-Angebote' ?>
            <span class="badge bg-danger"><?= count($angebote) ?></span>
        </h2>

        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET">
                            <input type="hidden" name="exchange" value="guns">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <select name="category" class="form-select form-select-sm">
                                        <option value="">Alle Kategorien</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['category_id'] ?>" <?= $category_id == $cat['category_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name_de']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="manufacturer" class="form-select form-select-sm">
                                        <option value="">Alle Hersteller</option>
                                        <?php foreach ($manufacturers as $man): ?>
                                            <option value="<?= $man['manufacturer_id'] ?>" <?= $manufacturer_id == $man['manufacturer_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($man['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="caliber" class="form-control form-control-sm"
                                           value="<?= htmlspecialchars($caliber) ?>" placeholder="Kaliber">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" name="price_max" class="form-control form-control-sm"
                                           value="<?= htmlspecialchars($price_max) ?>" placeholder="Max €">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-danger btn-sm w-100">
                                        <i class="bi bi-search"></i> Suchen
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <p><?= count($angebote) ?> <?= ($current_lang === 'en') ? 'offers found' : 'Angebote gefunden' ?></p>

        <?php foreach ($angebote as $item): ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="card-title mb-2">
                            <?= htmlspecialchars($item['title']) ?>
                            <?php if ($item['model']): ?>
                                <small class="text-muted">- <?= htmlspecialchars($item['model']) ?></small>
                            <?php endif; ?>
                        </h4>

                        <div class="mb-3">
                            <span class="badge bg-secondary"><?= htmlspecialchars($item['category_name']) ?></span>
                            <?php if ($item['manufacturer_name']): ?>
                                <span class="badge bg-dark"><?= htmlspecialchars($item['manufacturer_name']) ?></span>
                            <?php endif; ?>
                            <?php if ($item['caliber']): ?>
                                <span class="badge bg-info"><?= htmlspecialchars($item['caliber']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="small text-muted">
                            <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($item['plz'] . ' ' . $item['ort']) ?>
                        </div>
                    </div>

                    <div class="col-md-4 text-end">
                        <div style="font-weight: bold; font-size: 1.5em; color: #dc3545;">
                            <?= number_format($item['price'], 2, ',', '.') ?> €
                        </div>
                        <a href="gun_detail.php?id=<?= $item['listing_id'] ?>" class="btn btn-danger mt-2">
                            <i class="bi bi-eye"></i> Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    <?php elseif ($active_exchange === 'metals'): ?>
        <!-- ============================================================= -->
        <!-- EDELMETALL-BÖRSE -->
        <!-- ============================================================= -->
        <h2>
            <i class="bi bi-gem"></i> <?= ($current_lang === 'en') ? 'Precious Metals Offers' : 'Edelmetall-Angebote' ?>
            <span class="badge bg-warning text-dark"><?= count($angebote) ?></span>
        </h2>

        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET">
                            <input type="hidden" name="exchange" value="metals">
                            <div class="row g-2">
                                <div class="col-md-2">
                                    <select name="metal" class="form-select form-select-sm">
                                        <option value="">Alle Metalle</option>
                                        <?php foreach ($metals as $met): ?>
                                            <option value="<?= $met['metal_id'] ?>" <?= $metal_id == $met['metal_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($met['symbol']) ?> - <?= htmlspecialchars($current_lang === 'en' ? $met['name_en'] : $met['name_de']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="listing_type" class="form-select form-select-sm">
                                        <option value="verkauf" <?= $listing_type === 'verkauf' ? 'selected' : '' ?>>Verkauf</option>
                                        <option value="kauf" <?= $listing_type === 'kauf' ? 'selected' : '' ?>>Kauf</option>
                                        <option value="tausch" <?= $listing_type === 'tausch' ? 'selected' : '' ?>>Tausch</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="form" class="form-select form-select-sm">
                                        <option value="">Alle Formen</option>
                                        <option value="barren" <?= $form === 'barren' ? 'selected' : '' ?>>Barren</option>
                                        <option value="muenzen" <?= $form === 'muenzen' ? 'selected' : '' ?>>Münzen</option>
                                        <option value="granulat" <?= $form === 'granulat' ? 'selected' : '' ?>>Granulat</option>
                                        <option value="schmuck" <?= $form === 'schmuck' ? 'selected' : '' ?>>Schmuck</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" name="price_max" class="form-control form-control-sm"
                                           value="<?= htmlspecialchars($price_max) ?>" placeholder="Max €/Einheit">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" name="purity_min" step="0.001" class="form-control form-control-sm"
                                           value="<?= htmlspecialchars($purity_min) ?>" placeholder="Min. Feingehalt">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-warning btn-sm w-100">
                                        <i class="bi bi-search"></i> Suchen
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <p><?= count($angebote) ?> <?= ($current_lang === 'en') ? 'offers found' : 'Angebote gefunden' ?></p>

        <?php foreach ($angebote as $item): ?>
        <div class="card mb-3" style="border-left: 4px solid #ffc107;">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="card-title mb-2">
                            <i class="bi bi-gem text-warning"></i>
                            <?= htmlspecialchars($current_lang === 'en' ? $item['title_en'] ?? $item['title_de'] : $item['title_de']) ?>
                        </h4>

                        <div class="mb-3">
                            <span class="badge bg-warning text-dark">
                                <?= htmlspecialchars($item['metal_symbol']) ?> - <?= htmlspecialchars($current_lang === 'en' ? $item['metal_name_en'] : $item['metal_name']) ?>
                            </span>
                            <span class="badge bg-secondary">
                                <?= number_format($item['quantity'], 3, ',', '.') ?> <?= htmlspecialchars($item['unit_code']) ?>
                            </span>
                            <?php
                            $form_labels = [
                                'barren' => '🟨 Barren',
                                'muenzen' => '🪙 Münzen',
                                'granulat' => '⚫ Granulat',
                                'schmuck' => '💍 Schmuck',
                                'other' => '📦 Sonstiges'
                            ];
                            ?>
                            <span class="badge bg-info">
                                <?= $form_labels[$item['form']] ?? $item['form'] ?>
                            </span>
                            <span class="badge bg-success">
                                ⭐ Feingehalt: <?= number_format($item['purity'], 1, ',', '.') ?>
                            </span>
                            <?php if ($item['manufacturer']): ?>
                                <span class="badge bg-dark">
                                    🏭 <?= htmlspecialchars($item['manufacturer']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="description text-muted small mb-2">
                            <?php
                                $desc = $current_lang === 'en' ? ($item['description_en'] ?? $item['description_de']) : $item['description_de'];
                                if ($desc) {
                                    echo nl2br(htmlspecialchars(substr($desc, 0, 200)));
                                    echo strlen($desc) > 200 ? '...' : '';
                                }
                            ?>
                        </div>

                        <div class="small text-muted">
                            <?php if ($item['plz'] && $item['ort']): ?>
                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($item['plz'] . ' ' . $item['ort']) ?>
                            <?php endif; ?>
                            <?php if ($item['shipping_possible']): ?>
                                | <i class="bi bi-truck"></i> <?= $current_lang === 'en' ? 'Shipping available' : 'Versand möglich' ?>
                            <?php endif; ?>
                            <?php if ($item['pickup_only']): ?>
                                | <i class="bi bi-hand-index"></i> <?= $current_lang === 'en' ? 'Pickup only' : 'Nur Abholung' ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-4 text-end">
                        <div style="font-weight: bold; font-size: 1.3em; color: #ffc107;">
                            <?= number_format($item['price_per_unit'], 2, ',', '.') ?> <?= htmlspecialchars($item['currency_code']) ?>/<?= htmlspecialchars($item['unit_code']) ?>
                        </div>
                        <div class="text-muted small">
                            <?= $current_lang === 'en' ? 'Total:' : 'Gesamt:' ?>
                            <strong><?= number_format($item['total_price'], 2, ',', '.') ?> <?= htmlspecialchars($item['currency_code']) ?></strong>
                        </div>
                        <?php if ($item['price_negotiable']): ?>
                            <small class="text-muted">VB</small>
                        <?php endif; ?>

                        <div class="mt-3">
                            <a href="em_detail.php?id=<?= $item['listing_id'] ?>" class="btn btn-warning">
                                <i class="bi bi-eye"></i> <?= $current_lang === 'en' ? 'Details' : 'Details ansehen' ?>
                            </a>
                        </div>

                        <div class="small text-muted mt-2">
                            👤 <?= htmlspecialchars($item['seller_name']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
