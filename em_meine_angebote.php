<?php
/**
 * em_meine_angebote.php - Meine Edelmetall-Angebote (Liste, Bearbeiten, Löschen)
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/lang.php';

requireLogin();

$user = getCurrentUser();
if (!$user) {
    die('Benutzer nicht gefunden');
}

$pageTitle = t('my_metal_offers') ?? 'Meine Edelmetall-Angebote';

// Löschen-Funktion
if (isset($_GET['delete']) && isset($_GET['confirm'])) {
    $listing_id = (int)$_GET['delete'];

    $stmt = $pdo->prepare("SELECT user_id FROM em_listings WHERE listing_id = :id");
    $stmt->execute([':id' => $listing_id]);
    $listing = $stmt->fetch();

    if ($listing && $listing['user_id'] == $user['user_id']) {
        // Zuerst Bilder löschen
        $stmt = $pdo->prepare("DELETE FROM em_images WHERE listing_id = :id");
        $stmt->execute([':id' => $listing_id]);

        // Dann das Listing
        $stmt = $pdo->prepare("DELETE FROM em_listings WHERE listing_id = :id");
        $stmt->execute([':id' => $listing_id]);
        $success_msg = t('deleted_success') ?? 'Erfolgreich gelöscht';
    }
}

// Stammdaten laden
try {
    $metals = $pdo->query("SELECT * FROM em_metals WHERE aktiv = 1 ORDER BY sort_order")->fetchAll();
    $units = $pdo->query("SELECT * FROM em_units WHERE aktiv = 1 ORDER BY sort_order")->fetchAll();
} catch (Exception $e) {
    die("Fehler beim Laden der Stammdaten: " . $e->getMessage());
}

// User-Angebote laden
try {
    $stmt = $pdo->prepare("
        SELECT l.*,
               m.symbol as metal_symbol, m.name_de as metal_name,
               u.code as unit_code, u.name_de as unit_name,
               DATEDIFF(NOW(), l.erstellt_am) as tage_alt,
               (SELECT COUNT(*) FROM em_images WHERE listing_id = l.listing_id) as image_count
        FROM em_listings l
        JOIN em_metals m ON l.metal_id = m.metal_id
        JOIN em_units u ON l.unit_id = u.unit_id
        WHERE l.user_id = :user_id
        ORDER BY l.erstellt_am DESC
    ");
    $stmt->execute([':user_id' => $user['user_id']]);
    $listings = $stmt->fetchAll();
} catch (Exception $e) {
    die("Fehler beim Laden der Angebote: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-gem"></i> <?= t('my_metal_offers') ?? 'Meine Edelmetall-Angebote' ?></h2>
</div>

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
                     class="rounded-circle me-3"
                     width="60" height="60" alt="Avatar">
            <?php endif; ?>
            <div class="flex-grow-1">
                <h5 class="mb-1"><?= htmlspecialchars($user['name']) ?></h5>
                <p class="mb-0 text-muted"><?= htmlspecialchars($user['email']) ?></p>
            </div>
            <a href="em_angebot_erstellen.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> <?= t('new_metal_offer') ?? 'Neues Angebot' ?>
            </a>
        </div>
    </div>
</div>

<?php if (empty($listings)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-gem" style="font-size: 4rem; color: #ccc;"></i>
            <h4 class="mt-3"><?= t('no_metal_offers_yet') ?? 'Noch keine Angebote' ?></h4>
            <p class="text-muted"><?= t('create_first_metal') ?? 'Erstellen Sie Ihr erstes Edelmetall-Angebot' ?></p>
            <a href="em_angebot_erstellen.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> <?= t('create_metal_offer') ?? 'Angebot erstellen' ?>
            </a>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($listings as $l): ?>
    <?php
    // Bilder laden
    $images_stmt = $pdo->prepare("SELECT * FROM em_images WHERE listing_id = :id ORDER BY sort_order LIMIT 1");
    $images_stmt->execute([':id' => $l['listing_id']]);
    $main_image = $images_stmt->fetch();
    ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <?php if ($main_image): ?>
                <div class="col-md-3 mb-3">
                    <img src="<?= htmlspecialchars($main_image['filepath']) ?>"
                         class="img-fluid rounded"
                         alt="<?= htmlspecialchars($l['title_de']) ?>"
                         style="object-fit: cover; width: 100%; height: 200px;">
                </div>
                <?php endif; ?>
                <div class="col-md-<?= $main_image ? '5' : '8' ?>">
                    <h4 class="card-title">
                        <?php
                            $metal_icons = ['XAU' => '🥇', 'XAG' => '🥈', 'XPT' => '⚪', 'XPD' => '🔘'];
                            echo $metal_icons[$l['metal_symbol']] ?? '💰';
                        ?>
                        <?= htmlspecialchars($l['title_de']) ?>
                    </h4>

                    <p class="mb-2">
                        <strong><?= t('metal') ?? 'Metall' ?>:</strong>
                        <?= htmlspecialchars($l['metal_name']) ?> (<?= $l['metal_symbol'] ?>)
                    </p>

                    <p class="mb-2">
                        <strong><?= t('quantity') ?? 'Menge' ?>:</strong>
                        <?= number_format($l['quantity'], 2) ?> <?= $l['unit_code'] ?>
                        <?php if ($l['purity']): ?>
                            | <strong><?= t('purity') ?? 'Feinheit' ?>:</strong> <?= $l['purity'] ?>
                        <?php endif; ?>
                    </p>

                    <p class="mb-2">
                        <strong><?= t('form') ?? 'Form' ?>:</strong>
                        <?php
                            $forms = [
                                'barren' => 'Barren',
                                'muenzen' => 'Münzen',
                                'granulat' => 'Granulat',
                                'schmuck' => 'Schmuck',
                                'other' => 'Sonstige'
                            ];
                            echo $forms[$l['form']] ?? $l['form'];
                        ?>
                        <?php if ($l['manufacturer']): ?>
                            | <strong><?= t('manufacturer') ?? 'Hersteller' ?>:</strong> <?= htmlspecialchars($l['manufacturer']) ?>
                        <?php endif; ?>
                    </p>

                    <p class="mb-2">
                        <strong><i class="bi bi-calendar"></i> <?= t('created') ?? 'Erstellt' ?>:</strong>
                        <?= date('d.m.Y', strtotime($l['erstellt_am'])) ?>
                        (<?= $l['tage_alt'] ?> <?= t('days') ?? 'Tage' ?> <?= t('ago') ?? 'her' ?>)
                    </p>

                    <div class="mt-2">
                        <?php if ($l['aktiv'] && !$l['sold']): ?>
                            <span class="badge bg-success"><?= t('status_active') ?? 'Aktiv' ?></span>
                        <?php elseif ($l['sold']): ?>
                            <span class="badge bg-warning"><?= t('status_sold') ?? 'Verkauft' ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= t('status_inactive') ?? 'Inaktiv' ?></span>
                        <?php endif; ?>

                        <span class="badge bg-info">
                            <?php
                                $types = ['verkauf' => 'Verkauf', 'kauf' => 'Kaufgesuch', 'tausch' => 'Tausch'];
                                echo $types[$l['listing_type']] ?? $l['listing_type'];
                            ?>
                        </span>

                        <?php if ($l['image_count'] > 0): ?>
                            <span class="badge bg-secondary">
                                <i class="bi bi-camera"></i> <?= $l['image_count'] ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-4 text-end">
                    <div class="mb-3">
                        <div class="h3 text-success mb-0">
                            <?= number_format($l['price_per_unit'], 2) ?> <?= $l['currency_code'] ?>/<?= $l['unit_code'] ?>
                        </div>
                        <small class="text-muted">
                            <?= t('total') ?? 'Gesamt' ?>: <?= number_format($l['total_price'], 2) ?> <?= $l['currency_code'] ?>
                        </small>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="em_angebot_bearbeiten.php?id=<?= $l['listing_id'] ?>"
                           class="btn btn-primary">
                            <i class="bi bi-pencil"></i> <?= t('btn_edit') ?? 'Bearbeiten' ?>
                        </a>
                        <a href="index.php?exchange=metals&id=<?= $l['listing_id'] ?>"
                           class="btn btn-outline-secondary"
                           target="_blank">
                            <i class="bi bi-eye"></i> <?= t('btn_view') ?? 'Ansehen' ?>
                        </a>
                        <button type="button"
                                class="btn btn-outline-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteModal<?= $l['listing_id'] ?>">
                            <i class="bi bi-trash"></i> <?= t('btn_delete') ?? 'Löschen' ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal<?= $l['listing_id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= t('delete_confirm_title') ?? 'Löschen bestätigen' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><?= t('delete_confirm_text') ?? 'Möchten Sie dieses Angebot wirklich löschen?' ?></p>
                    <p class="text-muted mb-0">
                        <strong><?= htmlspecialchars($l['title_de']) ?></strong><br>
                        <?= number_format($l['quantity'], 2) ?> <?= $l['unit_code'] ?> <?= $l['metal_name'] ?><br>
                        <?= t('delete_warning') ?? 'Diese Aktion kann nicht rückgängig gemacht werden.' ?>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= t('btn_cancel') ?? 'Abbrechen' ?></button>
                    <a href="em_meine_angebote.php?delete=<?= $l['listing_id'] ?>&confirm=1"
                       class="btn btn-danger">
                        <i class="bi bi-trash"></i> <?= t('btn_yes_delete') ?? 'Ja, löschen' ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
