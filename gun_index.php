<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/lang.php';

<<<<<<< HEAD
$pageTitle = 'Waffenbörse - Angebote';
$error = '';
$listings = [];
=======
$pageTitle = t('gun_offers');
>>>>>>> origin/claude/fix-language-selection-j2XA8

try {
    // Filter-Parameter
    $category_id = $_GET['category'] ?? '';
    $manufacturer_id = $_GET['manufacturer'] ?? '';
    $caliber = $_GET['caliber'] ?? '';
    $price_max = $_GET['price_max'] ?? '';
    $condition = $_GET['condition'] ?? '';
    $license = $_GET['license'] ?? '';

    // SQL Query - direkte Tabellen statt View
    $sql = "SELECT l.*,
                   c.name_de as category_name, c.license_required,
                   m.name as manufacturer_name,
                   a.plz, a.ort,
                   u.name as seller_name
            FROM gun_listings l
            LEFT JOIN gun_categories c ON l.category_id = c.category_id
            LEFT JOIN gun_manufacturers m ON l.manufacturer_id = m.manufacturer_id
            LEFT JOIN lg_adressen a ON l.adresse_id = a.adresse_id
            LEFT JOIN lg_users u ON l.seller_id = u.user_id
            WHERE l.aktiv = 1 AND (l.expires_at IS NULL OR l.expires_at > NOW())";
    $params = [];

    if ($category_id) {
        $sql .= " AND l.category_id = :category_id";
        $params[':category_id'] = $category_id;
    }

    if ($manufacturer_id) {
        $sql .= " AND l.manufacturer_id = :manufacturer_id";
        $params[':manufacturer_id'] = $manufacturer_id;
    }

    if ($caliber) {
        $sql .= " AND l.caliber LIKE :caliber";
        $params[':caliber'] = '%' . $caliber . '%';
    }

    if ($price_max) {
        $sql .= " AND l.price <= :price_max";
        $params[':price_max'] = $price_max;
    }

    if ($condition) {
        $sql .= " AND l.condition_rating = :condition";
        $params[':condition'] = $condition;
    }

    if ($license) {
        $sql .= " AND c.license_required = :license";
        $params[':license'] = $license;
    }

    $sql .= " ORDER BY l.erstellt_am DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $listings = $stmt->fetchAll();

    // Kategorien für Filter
    $categories = $pdo->query("SELECT * FROM gun_categories WHERE aktiv = 1 ORDER BY sort_order")->fetchAll();

    // Hersteller für Filter
    $manufacturers = $pdo->query("SELECT * FROM gun_manufacturers WHERE aktiv = 1 ORDER BY name")->fetchAll();

} catch (Exception $e) {
    $error = "Fehler beim Laden der Angebote: " . $e->getMessage();
    error_log("gun_index.php error: " . $e->getMessage());
    $listings = [];
    $categories = [];
    $manufacturers = [];
}

<<<<<<< HEAD
=======
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

if ($license) {
    $sql .= " AND license_required = :license";
    $params[':license'] = $license;
}

$sql .= " ORDER BY erstellt_am DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

// Kategorien für Filter
$categories = $pdo->query("SELECT * FROM gun_categories WHERE aktiv = 1 ORDER BY sort_order")->fetchAll();

// Kategorien als assoziatives Array für schnellen Zugriff
$categories_map = [];
foreach ($categories as $cat) {
    $categories_map[$cat['category_id']] = $cat;
}

// Hersteller für Filter
$manufacturers = $pdo->query("SELECT * FROM gun_manufacturers WHERE aktiv = 1 ORDER BY name")->fetchAll();

>>>>>>> origin/claude/fix-language-selection-j2XA8
include 'includes/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-3">
        <!-- Filter Sidebar -->
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-funnel"></i> <?= t('gun_filter') ?></h5>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="mb-3">
                        <label class="form-label"><?= t('gun_category') ?></label>
                        <select name="category" class="form-select form-select-sm">
                            <option value=""><?= t('gun_all_categories') ?></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= $category_id == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(__t($cat, 'name')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= t('gun_manufacturer') ?></label>
                        <select name="manufacturer" class="form-select form-select-sm">
                            <option value=""><?= t('gun_all_manufacturers') ?></option>
                            <?php foreach ($manufacturers as $man): ?>
                                <option value="<?= $man['manufacturer_id'] ?>" <?= $manufacturer_id == $man['manufacturer_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($man['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= t('gun_caliber') ?></label>
                        <input type="text" name="caliber" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($caliber) ?>" placeholder="<?= t('gun_caliber_placeholder') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= t('gun_max_price') ?></label>
                        <input type="number" name="price_max" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($price_max) ?>" placeholder="<?= t('gun_price_placeholder') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= t('gun_condition') ?></label>
                        <select name="condition" class="form-select form-select-sm">
                            <option value=""><?= t('gun_all_conditions') ?></option>
                            <option value="neu" <?= $condition === 'neu' ? 'selected' : '' ?>><?= t('gun_condition_neu') ?></option>
                            <option value="wie_neu" <?= $condition === 'wie_neu' ? 'selected' : '' ?>><?= t('gun_condition_wie_neu') ?></option>
                            <option value="sehr_gut" <?= $condition === 'sehr_gut' ? 'selected' : '' ?>><?= t('gun_condition_sehr_gut') ?></option>
                            <option value="gut" <?= $condition === 'gut' ? 'selected' : '' ?>><?= t('gun_condition_gut') ?></option>
                            <option value="gebraucht" <?= $condition === 'gebraucht' ? 'selected' : '' ?>><?= t('gun_condition_gebraucht') ?></option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= t('gun_license') ?></label>
                        <select name="license" class="form-select form-select-sm">
                            <option value=""><?= t('gun_all_conditions') ?></option>
                            <option value="none" <?= $license === 'none' ? 'selected' : '' ?>><?= t('gun_license_none') ?></option>
                            <option value="kleiner_waffenschein" <?= $license === 'kleiner_waffenschein' ? 'selected' : '' ?>><?= t('gun_license_kleiner_waffenschein') ?></option>
                            <option value="wbk" <?= $license === 'wbk' ? 'selected' : '' ?>><?= t('gun_license_wbk') ?></option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-danger btn-sm w-100 mb-2">
                        <i class="bi bi-search"></i> <?= t('gun_search') ?>
                    </button>
                    <a href="gun_index.php" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-x-circle"></i> <?= t('gun_reset') ?>
                    </a>
                </form>
            </div>
        </div>
        
        <!-- Info Box -->
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="card-title"><?= t('gun_notes_title') ?></h6>
                <ul class="small mb-0">
                    <li><?= t('gun_notes_1') ?></li>
                    <li><?= t('gun_notes_2') ?></li>
                    <li><?= t('gun_notes_3') ?></li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <h2 class="mb-4">
            <i class="bi bi-crosshair"></i> <?= t('gun_offers') ?>
            <span class="badge bg-danger"><?= count($listings) ?></span>
        </h2>

        <?php if (empty($listings)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> <?= t('gun_no_offers') ?> <?= t('gun_try_other_filters') ?>
            </div>
        <?php else: ?>
            <?php foreach ($listings as $item): ?>
            <div class="card gun-card mb-3">
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
                                <span class="badge bg-secondary"><?= htmlspecialchars(__t($categories_map[$item['category_id']] ?? [], 'name')) ?></span>
                                <?php if ($item['manufacturer_name']): ?>
                                    <span class="badge bg-dark"><?= htmlspecialchars($item['manufacturer_name']) ?></span>
                                <?php endif; ?>
                                <?php if ($item['caliber']): ?>
                                    <span class="badge caliber-badge"><?= htmlspecialchars($item['caliber']) ?></span>
                                <?php endif; ?>
                                
                                <?php
                                $condition_class = [
                                    'neu' => 'success',
                                    'wie_neu' => 'success',
                                    'sehr_gut' => 'info',
                                    'gut' => 'primary',
                                    'gebraucht' => 'secondary'
                                ];
                                $condition_text = [
                                    'neu' => t('gun_condition_neu'),
                                    'wie_neu' => t('gun_condition_wie_neu'),
                                    'sehr_gut' => t('gun_condition_sehr_gut'),
                                    'gut' => t('gun_condition_gut'),
                                    'gebraucht' => t('gun_condition_gebraucht')
                                ];
                                ?>
                                <span class="badge bg-<?= $condition_class[$item['condition_rating']] ?? 'secondary' ?>">
                                    <?= $condition_text[$item['condition_rating']] ?? $item['condition_rating'] ?>
                                </span>

                                <?php
                                $license_icons = [
                                    'none' => '✓ ' . t('gun_license_none'),
                                    'kleiner_waffenschein' => '📋 ' . t('gun_license_kleiner_waffenschein'),
                                    'wbk' => '📜 ' . t('gun_license_wbk')
                                ];
                                ?>
                                <span class="badge license-badge bg-warning text-dark">
                                    <?= $license_icons[$item['license_required']] ?? $item['license_required'] ?>
                                </span>
                            </div>

                            <?php if (!empty($item['includes_accessories'])): ?>
                                <p class="card-text text-muted">
                                    <strong>Zubehör:</strong> <?= nl2br(htmlspecialchars(substr($item['includes_accessories'], 0, 200))) ?>
                                    <?= strlen($item['includes_accessories']) > 200 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>

                            <div class="small text-muted">
<<<<<<< HEAD
                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars(($item['plz'] ?? '') . ' ' . ($item['ort'] ?? 'Standort nicht angegeben')) ?>
                                <?php if (!empty($item['shipping_possible'])): ?>
                                    | <i class="bi bi-truck"></i> Versand möglich
=======
                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($item['plz'] . ' ' . $item['ort']) ?>
                                <?php if ($item['shipping_possible']): ?>
                                    | <i class="bi bi-truck"></i> <?= t('gun_shipping_possible') ?>
>>>>>>> origin/claude/fix-language-selection-j2XA8
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-4 text-end">
                            <div class="gun-price mb-3">
                                <?= number_format($item['price'], 2, ',', '.') ?> €
                                <?php if ($item['price_negotiable']): ?>
                                    <small class="text-muted d-block" style="font-size: 0.5em;"><?= t('gun_vb') ?></small>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="gun_detail.php?id=<?= $item['listing_id'] ?>" class="btn btn-danger">
                                    <i class="bi bi-eye"></i> <?= t('gun_details') ?>
                                </a>
                                <?php if (isLoggedIn()): ?>
                                    <button class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-heart"></i> <?= t('gun_save') ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
