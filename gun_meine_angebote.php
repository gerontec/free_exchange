<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/lang.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = 'Meine Waffen-Angebote';

// Löschen
if (isset($_GET['delete']) && isset($_GET['confirm'])) {
    $listing_id = (int)$_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT seller_id FROM gun_listings WHERE listing_id = :id");
    $stmt->execute([':id' => $listing_id]);
    $listing = $stmt->fetch();
    
    if ($listing && $listing['seller_id'] == $user['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM gun_listings WHERE listing_id = :id");
        $stmt->execute([':id' => $listing_id]);
        $success_msg = "Angebot erfolgreich gelöscht.";
    }
}

// User-Angebote laden
$stmt = $pdo->prepare("
    SELECT l.*, c.name_de as category_name, m.name as manufacturer_name,
           DATEDIFF(NOW(), l.erstellt_am) as tage_alt,
           DATEDIFF(l.expires_at, NOW()) as tage_verbleibend
    FROM gun_listings l
    LEFT JOIN gun_categories c ON l.category_id = c.category_id
    LEFT JOIN gun_manufacturers m ON l.manufacturer_id = m.manufacturer_id
    WHERE l.seller_id = :user_id
    ORDER BY l.erstellt_am DESC
");
$stmt->execute([':user_id' => $user['user_id']]);
$listings = $stmt->fetchAll();

include 'includes/header.php';
?>

<h2><i class="bi bi-crosshair"></i> Meine Waffen-Angebote</h2>

<?php if (isset($success_msg)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?= $success_msg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card mb-4 bg-light">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <?php if ($user['avatar_url']): ?>
                <img src="<?= htmlspecialchars($user['avatar_url']) ?>" 
                     class="rounded-circle me-3" width="60" height="60">
            <?php endif; ?>
            <div class="flex-grow-1">
                <h5 class="mb-1"><?= htmlspecialchars($user['name']) ?></h5>
                <p class="mb-0 text-muted"><?= htmlspecialchars($user['email']) ?></p>
            </div>
            <a href="gun_angebot_erstellen.php" class="btn btn-danger">
                <i class="bi bi-plus-circle"></i> Neues Angebot
            </a>
        </div>
    </div>
</div>

<?php if (empty($listings)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
            <h4 class="mt-3">Noch keine Angebote</h4>
            <p class="text-muted">Erstellen Sie Ihr erstes Waffen-Angebot</p>
            <a href="gun_angebot_erstellen.php" class="btn btn-danger">
                <i class="bi bi-plus-circle"></i> Angebot erstellen
            </a>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($listings as $item): ?>
    <div class="card gun-card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h4><?= htmlspecialchars($item['title']) ?></h4>
                    
                    <div class="mb-2">
                        <span class="badge bg-secondary"><?= htmlspecialchars($item['category_name']) ?></span>
                        <?php if ($item['manufacturer_name']): ?>
                            <span class="badge bg-dark"><?= htmlspecialchars($item['manufacturer_name']) ?></span>
                        <?php endif; ?>
                        <?php if ($item['caliber']): ?>
                            <span class="badge caliber-badge"><?= htmlspecialchars($item['caliber']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="mb-2">
                        <strong>Erstellt:</strong> <?= date('d.m.Y', strtotime($item['erstellt_am'])) ?>
                        (vor <?= $item['tage_alt'] ?> Tagen)
                    </p>
                    
                    <p class="mb-2">
                        <strong>Läuft noch:</strong> <?= max(0, $item['tage_verbleibend']) ?> Tage
                        <?php if ($item['tage_verbleibend'] < 10): ?>
                            <span class="badge bg-warning">Läuft bald ab</span>
                        <?php endif; ?>
                    </p>
                    
                    <div class="mt-2">
                        <?php if ($item['aktiv'] && !$item['sold']): ?>
                            <span class="badge bg-success">Aktiv</span>
                        <?php elseif ($item['sold']): ?>
                            <span class="badge bg-dark">Verkauft</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inaktiv</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-4 text-end">
                    <div class="gun-price mb-3">
                        <?= number_format($item['price'], 2, ',', '.') ?> €
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="gun_angebot_bearbeiten.php?id=<?= $item['listing_id'] ?>" 
                           class="btn btn-primary btn-sm">
                            <i class="bi bi-pencil"></i> Bearbeiten
                        </a>
                        <a href="gun_detail.php?id=<?= $item['listing_id'] ?>" 
                           class="btn btn-outline-secondary btn-sm" target="_blank">
                            <i class="bi bi-eye"></i> Anzeigen
                        </a>
                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteModal<?= $item['listing_id'] ?>">
                            <i class="bi bi-trash"></i> Löschen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal<?= $item['listing_id'] ?>">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Angebot löschen?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Möchten Sie dieses Angebot wirklich löschen?</p>
                    <p class="text-muted mb-0">
                        <strong><?= htmlspecialchars($item['title']) ?></strong><br>
                        Diese Aktion kann nicht rückgängig gemacht werden.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <a href="gun_meine_angebote.php?delete=<?= $item['listing_id'] ?>&confirm=1" 
                       class="btn btn-danger">
                        <i class="bi bi-trash"></i> Ja, löschen
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
