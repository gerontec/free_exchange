<?php
/**
 * index.php - Zweisprachige Lagerraum-Börse
 */
require_once 'includes/config.php';

// 1. Sprache festlegen (Default: de)
$current_lang = $_SESSION['lang'] ?? 'de';
$suffix = ($current_lang === 'en') ? '_en' : '_de';

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
