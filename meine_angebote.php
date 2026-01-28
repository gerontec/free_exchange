<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = t('my_offers');

// Löschen-Funktion
if (isset($_GET['delete']) && isset($_GET['confirm'])) {
    $lagerraum_id = (int)$_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT anbieter_id FROM lg_lagerraeume WHERE lagerraum_id = :id");
    $stmt->execute([':id' => $lagerraum_id]);
    $angebot = $stmt->fetch();
    
    if ($angebot && $angebot['anbieter_id'] == $user['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM lg_lagerraeume WHERE lagerraum_id = :id");
        $stmt->execute([':id' => $lagerraum_id]);
        $success_msg = t('deleted_success');
    }
}

// User-Angebote laden
$stmt = $pdo->prepare("
    SELECT l.*, a.strasse, a.hausnummer, a.plz, a.ort, a.land,
           DATEDIFF(NOW(), l.erstellt_am) as tage_alt,
           60 - DATEDIFF(NOW(), l.erstellt_am) as tage_verbleibend
    FROM lg_lagerraeume l
    JOIN lg_adressen a ON l.adresse_id = a.adresse_id
    WHERE l.anbieter_id = :user_id
    ORDER BY l.erstellt_am DESC
");
$stmt->execute([':user_id' => $user['user_id']]);
$angebote = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-house-door"></i> <?= t('my_offers') ?></h2>
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
            <a href="angebot_erstellen.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> <?= t('new_offer') ?>
            </a>
        </div>
    </div>
</div>

<?php if (empty($angebote)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
            <h4 class="mt-3"><?= t('no_offers_yet') ?></h4>
            <p class="text-muted"><?= t('create_first') ?></p>
            <a href="angebot_erstellen.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> <?= t('create_offer') ?>
            </a>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($angebote as $a): ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h4 class="card-title">
                        📦 <?= htmlspecialchars($a['ort']) ?> - <?= number_format($a['qm_gesamt'], 0) ?> m²
                    </h4>
                    
                    <p class="mb-2">
                        <strong><i class="bi bi-geo-alt"></i> <?= t('address') ?>:</strong>
                        <?= htmlspecialchars($a['strasse']) ?> <?= htmlspecialchars($a['hausnummer']) ?>, 
                        <?= htmlspecialchars($a['plz']) ?> <?= htmlspecialchars($a['ort']) ?>
                        <?php if (isset($LAENDER[$a['land']])): ?>
                            (<?= htmlspecialchars($LAENDER[$a['land']]) ?>)
                        <?php endif; ?>
                    </p>
                    
                    <p class="mb-2">
                        <strong><i class="bi bi-calendar"></i> <?= t('created') ?>:</strong>
                        <?= date('d.m.Y', strtotime($a['erstellt_am'])) ?>
                        (<?= $a['tage_alt'] ?> <?= t('days') ?> <?= t('ago') ?>)
                    </p>
                    
                    <p class="mb-2">
                        <strong><i class="bi bi-clock"></i> <?= t('expires_in') ?>:</strong>
                        <?= max(0, $a['tage_verbleibend']) ?> <?= t('days') ?>
                        <?php if ($a['tage_verbleibend'] < 10): ?>
                            <span class="badge bg-warning"><?= t('expires_soon') ?></span>
                        <?php endif; ?>
                    </p>
                    
                    <div class="mt-2">
                        <?php if ($a['aktiv']): ?>
                            <span class="badge bg-success"><?= t('status_active') ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= t('status_inactive') ?></span>
                        <?php endif; ?>
                        
                        <?php if ($a['beheizt']): ?>
                            <span class="badge bg-info">🔥 <?= t('heated') ?></span>
                        <?php endif; ?>
                        <?php if ($a['zugang_24_7']): ?>
                            <span class="badge bg-info">🕐 <?= t('access_24_7') ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-4 text-end">
                    <div class="mb-3">
                        <div class="h3 text-success mb-0">
                            <?= number_format($a['preis_pro_qm'], 2) ?> €/m²
                        </div>
                        <small class="text-muted">
                            <?= t('total') ?>: <?= number_format($a['preis_gesamt'], 2) ?> <?= t('per_month') ?>
                        </small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="angebot_bearbeiten.php?id=<?= $a['lagerraum_id'] ?>" 
                           class="btn btn-primary">
                            <i class="bi bi-pencil"></i> <?= t('btn_edit') ?>
                        </a>
                        <a href="index.php?id=<?= $a['lagerraum_id'] ?>" 
                           class="btn btn-outline-secondary" 
                           target="_blank">
                            <i class="bi bi-eye"></i> <?= t('btn_view') ?>
                        </a>
                        <button type="button" 
                                class="btn btn-outline-danger" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteModal<?= $a['lagerraum_id'] ?>">
                            <i class="bi bi-trash"></i> <?= t('btn_delete') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal<?= $a['lagerraum_id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= t('delete_confirm_title') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><?= t('delete_confirm_text') ?></p>
                    <p class="text-muted mb-0">
                        <strong><?= htmlspecialchars($a['ort']) ?> - <?= number_format($a['qm_gesamt'], 0) ?> m²</strong><br>
                        <?= t('delete_warning') ?>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= t('btn_cancel') ?></button>
                    <a href="meine_angebote.php?delete=<?= $a['lagerraum_id'] ?>&confirm=1" 
                       class="btn btn-danger">
                        <i class="bi bi-trash"></i> <?= t('btn_yes_delete') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
