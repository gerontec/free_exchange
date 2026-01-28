<?php
require_once 'includes/config.php';
$pageTitle = 'Lagerraum-Angebote';

// Filter-Parameter
$plz = $_GET['plz'] ?? '';
$qm_min = $_GET['qm_min'] ?? '';
$qm_max = $_GET['qm_max'] ?? '';
$preis_max = $_GET['preis_max'] ?? '';
$ort = $_GET['ort'] ?? '';
$land = $_GET['land'] ?? '';

// SQL Query bauen
$sql = "SELECT * FROM lg_v_angebote WHERE 1=1";
$params = [];

if ($land && $land !== 'DE') {
    $sql .= " AND land = :land";
    $params[':land'] = $land;
}

if ($plz) {
    $sql .= " AND plz LIKE :plz";
    $params[':plz'] = $plz . '%';
}

if ($ort) {
    $sql .= " AND ort LIKE :ort";
    $params[':ort'] = '%' . $ort . '%';
}

if ($qm_min) {
    $sql .= " AND qm_gesamt >= :qm_min";
    $params[':qm_min'] = $qm_min;
}

if ($qm_max) {
    $sql .= " AND qm_gesamt <= :qm_max";
    $params[':qm_max'] = $qm_max;
}

if ($preis_max) {
    $sql .= " AND preis_pro_qm <= :preis_max";
    $params[':preis_max'] = $preis_max;
}

$sql .= " ORDER BY erstellt_am DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$angebote = $stmt->fetchAll();

include 'includes/header.php';
?>

<h2>Lagerraum-Angebote</h2>

<!-- Filter -->
<div class="filter-box">
    <h3>🔍 Suche filtern</h3>
    <form method="GET" id="filterForm">
        <div class="filter-grid">
            <div class="form-group">
                <label>Land</label>
                <select name="land">
                    <option value="">Alle Länder</option>
                    <?php foreach ($LAENDER as $code => $name): ?>
                        <option value="<?= $code ?>" <?= $land === $code ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>PLZ</label>
                <input type="text" name="plz" value="<?= htmlspecialchars($plz) ?>" placeholder="z.B. 10115">
            </div>
            <div class="form-group">
                <label>Ort</label>
                <input type="text" name="ort" value="<?= htmlspecialchars($ort) ?>" placeholder="z.B. Berlin">
            </div>
            <div class="form-group">
                <label>Min. m²</label>
                <input type="number" name="qm_min" value="<?= htmlspecialchars($qm_min) ?>" placeholder="z.B. 50">
            </div>
            <div class="form-group">
                <label>Max. m²</label>
                <input type="number" name="qm_max" value="<?= htmlspecialchars($qm_max) ?>" placeholder="z.B. 200">
            </div>
            <div class="form-group">
                <label>Max. €/m²</label>
                <input type="number" name="preis_max" value="<?= htmlspecialchars($preis_max) ?>" step="0.01" placeholder="z.B. 10">
            </div>
        </div>
        <button type="submit" class="btn">Suchen</button>
        <a href="index.php" class="btn">Filter zurücksetzen</a>
    </form>
</div>

<!-- Angebote -->
<p><?= count($angebote) ?> Angebot(e) gefunden</p>

<?php foreach ($angebote as $a): ?>
<div class="card">
    <h3>📦 <?= htmlspecialchars($a['ort']) ?> (<?= htmlspecialchars($LAENDER[$a['land']] ?? $a['land']) ?>) - <?= number_format($a['qm_gesamt'], 0, ',', '.') ?> m²</h3>
    
    <div class="info">
        <div class="info-item">
            <strong>📍 Adresse:</strong>
            <?= htmlspecialchars($a['strasse']) ?> <?= htmlspecialchars($a['hausnummer']) ?>, 
            <?= htmlspecialchars($a['plz']) ?> <?= htmlspecialchars($a['ort']) ?>, 
            <?= htmlspecialchars($LAENDER[$a['land']] ?? $a['land']) ?>
        </div>
        <div class="info-item">
            <strong>📏 Fläche:</strong>
            <?= number_format($a['qm_gesamt'], 2, ',', '.') ?> m²
        </div>
        <div class="info-item">
            <strong>🏠 Anzahl Räume:</strong>
            <?= $a['anzahl_raeume'] ?>
        </div>
        <div class="info-item">
            <strong>📅 Verfügbar ab:</strong>
            <?= $a['verfuegbar_ab'] ? date('d.m.Y', strtotime($a['verfuegbar_ab'])) : 'Sofort' ?>
        </div>
        <?php if (isset($a['tage_verbleibend'])): ?>
        <div class="info-item">
            <strong>⏰ Anzeige läuft noch:</strong>
            <?= max(0, $a['tage_verbleibend']) ?> Tage
        </div>
        <?php endif; ?>
    </div>
    
    <div style="margin: 15px 0;">
        <?php if ($a['beheizt']): ?>
            <span class="badge badge-orange">🔥 Beheizt</span>
        <?php endif; ?>
        <?php if ($a['zugang_24_7']): ?>
            <span class="badge badge-blue">🕐 24/7 Zugang</span>
        <?php endif; ?>
        <?php if ($a['klimatisiert']): ?>
            <span class="badge badge-blue">❄️ Klimatisiert</span>
        <?php endif; ?>
        <?php if ($a['alarm_vorhanden']): ?>
            <span class="badge badge-green">🔒 Alarmgesichert</span>
        <?php endif; ?>
    </div>
    
    <div class="price">
        <?= number_format($a['preis_pro_qm'], 2, ',', '.') ?> €/m² · 
        Gesamt: <?= number_format($a['preis_gesamt'], 2, ',', '.') ?> €/Monat
    </div>
    
    <?php if ($a['bemerkung']): ?>
        <p style="margin-top: 10px; color: #7f8c8d;"><?= nl2br(htmlspecialchars($a['bemerkung'])) ?></p>
    <?php endif; ?>
    
    <div style="margin-top: 15px;">
        <a href="kontakt.php?id=<?= $a['lagerraum_id'] ?>" class="btn btn-contact">📧 Kontakt aufnehmen</a>
    </div>
</div>
<?php endforeach; ?>

<?php if (empty($angebote)): ?>
    <div class="card">
        <p>Keine Angebote gefunden. Versuchen Sie andere Filterkriterien.</p>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
