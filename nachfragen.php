<?php
require_once 'includes/config.php';
$pageTitle = 'Lagerraum-Nachfragen';

// Filter-Parameter
$plz = $_GET['plz'] ?? '';
$ort = $_GET['ort'] ?? '';

// SQL Query bauen
$sql = "SELECT * FROM lg_v_nachfragen WHERE 1=1";
$params = [];

if ($plz) {
    $sql .= " AND (plz_von LIKE :plz OR ort_wunsch LIKE :ort)";
    $params[':plz'] = $plz . '%';
    $params[':ort'] = '%' . $plz . '%';
}

if ($ort) {
    $sql .= " AND ort_wunsch LIKE :ort2";
    $params[':ort2'] = '%' . $ort . '%';
}

$sql .= " ORDER BY erstellt_am DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$nachfragen = $stmt->fetchAll();

include 'includes/header.php';
?>

<h2>Lagerraum-Nachfragen</h2>

<!-- Filter -->
<div class="filter-box">
    <h3>🔍 Nachfragen filtern</h3>
    <form method="GET">
        <div class="filter-grid">
            <div class="form-group">
                <label>PLZ</label>
                <input type="text" name="plz" value="<?= htmlspecialchars($plz) ?>" placeholder="z.B. 10115">
            </div>
            <div class="form-group">
                <label>Ort</label>
                <input type="text" name="ort" value="<?= htmlspecialchars($ort) ?>" placeholder="z.B. Berlin">
            </div>
        </div>
        <button type="submit" class="btn">Suchen</button>
        <a href="nachfragen.php" class="btn">Filter zurücksetzen</a>
    </form>
</div>

<!-- Nachfragen -->
<p><?= count($nachfragen) ?> Nachfrage(n) gefunden</p>

<?php foreach ($nachfragen as $n): ?>
<div class="card">
    <h3>🔍 Suche Lagerraum in <?= htmlspecialchars($n['ort_wunsch'] ?: 'PLZ ' . $n['plz_von']) ?></h3>
    
    <div class="info">
        <div class="info-item">
            <strong>📍 Wunschregion:</strong>
            <?php if ($n['ort_wunsch']): ?>
                <?= htmlspecialchars($n['ort_wunsch']) ?>
            <?php else: ?>
                PLZ <?= htmlspecialchars($n['plz_von']) ?> - <?= htmlspecialchars($n['plz_bis']) ?>
            <?php endif; ?>
            <?php if ($n['umkreis_km']): ?>
                (Umkreis: <?= $n['umkreis_km'] ?> km)
            <?php endif; ?>
        </div>
        
        <?php if ($n['qm_min'] || $n['qm_max']): ?>
        <div class="info-item">
            <strong>📏 Gewünschte Fläche:</strong>
            <?php if ($n['qm_min']): ?>
                ab <?= number_format($n['qm_min'], 0, ',', '.') ?> m²
            <?php endif; ?>
            <?php if ($n['qm_max']): ?>
                bis <?= number_format($n['qm_max'], 0, ',', '.') ?> m²
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($n['preis_max']): ?>
        <div class="info-item">
            <strong>💰 Max. Preis:</strong>
            <?= number_format($n['preis_max'], 2, ',', '.') ?> €/m²
        </div>
        <?php endif; ?>
        
        <div class="info-item">
            <strong>📅 Gesucht seit:</strong>
            <?= date('d.m.Y', strtotime($n['erstellt_am'])) ?>
        </div>
    </div>
    
    <div style="margin: 15px 0;">
        <?php if ($n['beheizt_gewuenscht']): ?>
            <span class="badge badge-orange">🔥 Beheizt gewünscht</span>
        <?php endif; ?>
        <?php if ($n['zugang_24_7_gewuenscht']): ?>
            <span class="badge badge-blue">🕐 24/7 Zugang gewünscht</span>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 15px;">
        <strong>Kontakt:</strong><br>
        <?= htmlspecialchars($n['suchender_name']) ?><br>
        📧 <?= htmlspecialchars($n['email']) ?><br>
        <?php if ($n['telefon']): ?>
            📞 <?= htmlspecialchars($n['telefon']) ?><br>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<?php if (empty($nachfragen)): ?>
    <div class="card">
        <p>Keine Nachfragen gefunden.</p>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
