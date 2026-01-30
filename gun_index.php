<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/lang.php';

$pageTitle = 'Waffenbörse - Angebote';
$error = '';
$listings = [];

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
                <h5 class="mb-0"><i class="bi bi-funnel"></i> Filter</h5>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="mb-3">
                        <label class="form-label">Kategorie</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="">Alle Kategorien</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= $category_id == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name_de']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Hersteller</label>
                        <select name="manufacturer" class="form-select form-select-sm">
                            <option value="">Alle Hersteller</option>
                            <?php foreach ($manufacturers as $man): ?>
                                <option value="<?= $man['manufacturer_id'] ?>" <?= $manufacturer_id == $man['manufacturer_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($man['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Kaliber</label>
                        <input type="text" name="caliber" class="form-control form-control-sm" 
                               value="<?= htmlspecialchars($caliber) ?>" placeholder="z.B. 9mm">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Max. Preis (€)</label>
                        <input type="number" name="price_max" class="form-control form-control-sm" 
                               value="<?= htmlspecialchars($price_max) ?>" placeholder="z.B. 1000">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Zustand</label>
                        <select name="condition" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <option value="neu" <?= $condition === 'neu' ? 'selected' : '' ?>>Neu</option>
                            <option value="wie_neu" <?= $condition === 'wie_neu' ? 'selected' : '' ?>>Wie neu</option>
                            <option value="sehr_gut" <?= $condition === 'sehr_gut' ? 'selected' : '' ?>>Sehr gut</option>
                            <option value="gut" <?= $condition === 'gut' ? 'selected' : '' ?>>Gut</option>
                            <option value="gebraucht" <?= $condition === 'gebraucht' ? 'selected' : '' ?>>Gebraucht</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Waffenschein</label>
                        <select name="license" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <option value="none" <?= $license === 'none' ? 'selected' : '' ?>>Frei ab 18</option>
                            <option value="kleiner_waffenschein" <?= $license === 'kleiner_waffenschein' ? 'selected' : '' ?>>Kleiner WS</option>
                            <option value="wbk" <?= $license === 'wbk' ? 'selected' : '' ?>>WBK</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-danger btn-sm w-100 mb-2">
                        <i class="bi bi-search"></i> Suchen
                    </button>
                    <a href="gun_index.php" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-x-circle"></i> Zurücksetzen
                    </a>
                </form>
            </div>
        </div>
        
        <!-- Info Box -->
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="card-title">⚠️ Wichtige Hinweise</h6>
                <ul class="small mb-0">
                    <li>Waffenhandel nur zwischen Inhabern entsprechender Genehmigungen</li>
                    <li>Persönliche Übergabe empfohlen</li>
                    <li>Alle Angebote gemäß WaffG</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <h2 class="mb-4">
            <i class="bi bi-crosshair"></i> Waffen-Angebote
            <span class="badge bg-danger"><?= count($listings) ?></span>
        </h2>
        
        <?php if (empty($listings)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Keine Angebote gefunden. Versuchen Sie andere Filterkriterien.
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
                                <span class="badge bg-secondary"><?= htmlspecialchars($item['category_name']) ?></span>
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
                                    'neu' => 'Neu',
                                    'wie_neu' => 'Wie neu',
                                    'sehr_gut' => 'Sehr gut',
                                    'gut' => 'Gut',
                                    'gebraucht' => 'Gebraucht'
                                ];
                                ?>
                                <span class="badge bg-<?= $condition_class[$item['condition_rating']] ?? 'secondary' ?>">
                                    <?= $condition_text[$item['condition_rating']] ?? $item['condition_rating'] ?>
                                </span>
                                
                                <?php
                                $license_icons = [
                                    'none' => '✓ Frei ab 18',
                                    'kleiner_waffenschein' => '📋 Kleiner WS',
                                    'wbk' => '📜 WBK erforderlich'
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
                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars(($item['plz'] ?? '') . ' ' . ($item['ort'] ?? 'Standort nicht angegeben')) ?>
                                <?php if (!empty($item['shipping_possible'])): ?>
                                    | <i class="bi bi-truck"></i> Versand möglich
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-4 text-end">
                            <div class="gun-price mb-3">
                                <?= number_format($item['price'], 2, ',', '.') ?> €
                                <?php if ($item['price_negotiable']): ?>
                                    <small class="text-muted d-block" style="font-size: 0.5em;">VB</small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="gun_detail.php?id=<?= $item['listing_id'] ?>" class="btn btn-danger">
                                    <i class="bi bi-eye"></i> Details ansehen
                                </a>
                                <?php if (isLoggedIn()): ?>
                                    <button class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-heart"></i> Merken
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
