<?php
/**
 * em_kontakt.php - Kontakt aufnehmen für Edelmetall-Angebote
 */
require_once 'includes/config.php';
require_once 'includes/lang.php';

// Sprache festlegen
$current_lang = $_SESSION['lang'] ?? 'de';

$pageTitle = ($current_lang === 'en') ? 'Contact Seller' : 'Verkäufer kontaktieren';

$listing_id = $_GET['id'] ?? null;

if (!$listing_id) {
    header('Location: index.php?exchange=metals');
    exit;
}

// Angebot laden
$stmt = $pdo->prepare("
    SELECT l.*,
           m.symbol as metal_symbol, m.name_de as metal_name, m.name_en as metal_name_en,
           u.code as unit_code, u.name_de as unit_name,
           usr.name as seller_name, usr.email as seller_email, usr.user_id as seller_id,
           a.plz, a.ort, a.land
    FROM em_listings l
    JOIN em_metals m ON l.metal_id = m.metal_id
    JOIN em_units u ON l.unit_id = u.unit_id
    JOIN lg_users usr ON l.user_id = usr.user_id
    LEFT JOIN lg_adressen a ON l.adresse_id = a.adresse_id
    WHERE l.listing_id = :id AND l.aktiv = 1 AND l.sold = 0
");
$stmt->execute([':id' => $listing_id]);
$listing = $stmt->fetch();

if (!$listing) {
    header('Location: index.php?exchange=metals');
    exit;
}

$success = false;
$error = '';
$current_user = getCurrentUser();

// Prüfen ob Benutzer eingeloggt ist
$is_logged_in = !empty($current_user);

// Prüfen ob es das eigene Angebot ist
if ($is_logged_in && $current_user['user_id'] == $listing['seller_id']) {
    $error = ($current_lang === 'en')
        ? 'You cannot contact yourself about your own listing.'
        : 'Sie können sich nicht selbst bezüglich Ihres eigenen Angebots kontaktieren.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    try {
        $sender_id = null;

        // Wenn nicht eingeloggt, Benutzer erstellen oder abrufen
        if (!$is_logged_in) {
            $stmt = $pdo->prepare("
                INSERT INTO lg_users (email, telefon, name, aktiv)
                VALUES (:email, :telefon, :name, 1)
                ON DUPLICATE KEY UPDATE user_id=LAST_INSERT_ID(user_id)
            ");
            $stmt->execute([
                ':email' => $_POST['email'],
                ':telefon' => $_POST['telefon'] ?: null,
                ':name' => $_POST['name']
            ]);
            $sender_id = $pdo->lastInsertId();
        } else {
            $sender_id = $current_user['user_id'];
        }

        // Nachricht in em_messages speichern
        $stmt = $pdo->prepare("
            INSERT INTO em_messages (listing_id, sender_id, receiver_id, message, erstellt_am)
            VALUES (:listing_id, :sender_id, :receiver_id, :message, NOW())
        ");
        $stmt->execute([
            ':listing_id' => $listing_id,
            ':sender_id' => $sender_id,
            ':receiver_id' => $listing['seller_id'],
            ':message' => $_POST['nachricht']
        ]);

        // E-Mail-Benachrichtigung an Verkäufer senden
        if (!empty($listing['seller_email'])) {
            $sellerName = htmlspecialchars($listing['seller_name'] ?? 'Verkäufer');
            $senderName = $is_logged_in ? htmlspecialchars($current_user['name']) : htmlspecialchars($_POST['name']);
            $senderEmail = $is_logged_in ? htmlspecialchars($current_user['email']) : htmlspecialchars($_POST['email']);
            $senderTelefon = $is_logged_in ? htmlspecialchars($current_user['telefon'] ?? '-') : htmlspecialchars($_POST['telefon'] ?? '-');
            $nachricht = nl2br(htmlspecialchars($_POST['nachricht']));

            $listingTitle = htmlspecialchars($current_lang === 'en' ? ($listing['title_en'] ?: $listing['title_de']) : $listing['title_de']);
            $metalName = htmlspecialchars($current_lang === 'en' ? ($listing['metal_name_en'] ?? $listing['metal_name']) : $listing['metal_name']);
            $quantity = number_format($listing['quantity'], 2);
            $unitCode = htmlspecialchars($listing['unit_code']);
            $pricePerUnit = number_format($listing['price_per_unit'], 2, ',', '.');
            $totalPrice = number_format($listing['total_price'], 2, ',', '.');
            $currencyCode = htmlspecialchars($listing['currency_code']);
            $purity = $listing['purity'] ? htmlspecialchars($listing['purity']) : '-';

            $metal_icons = ['XAU' => '🥇', 'XAG' => '🥈', 'XPT' => '⚪', 'XPD' => '🔘'];
            $metalIcon = $metal_icons[$listing['metal_symbol']] ?? '💰';

            $location = '';
            if ($listing['ort']) {
                $location = "📍 " . htmlspecialchars($listing['plz'] . ' ' . $listing['ort']);
                if ($listing['land']) {
                    $location .= " (" . htmlspecialchars($listing['land']) . ")";
                }
            }

            $emailContent = "
                <h2>" . ($current_lang === 'en' ? 'New Contact Request for Your Listing' : 'Neue Kontaktanfrage für Ihr Angebot') . "</h2>

                <h3>" . ($current_lang === 'en' ? 'Listing Details:' : 'Angebot-Details:') . "</h3>
                <p>
                    <strong>" . ($current_lang === 'en' ? 'Title:' : 'Titel:') . "</strong> {$listingTitle}<br>
                    <strong>" . ($current_lang === 'en' ? 'Metal:' : 'Metall:') . "</strong> {$metalIcon} {$metalName}<br>
                    <strong>" . ($current_lang === 'en' ? 'Quantity:' : 'Menge:') . "</strong> {$quantity} {$unitCode}<br>
                    <strong>" . ($current_lang === 'en' ? 'Purity:' : 'Feinheit:') . "</strong> {$purity}<br>
                    <strong>" . ($current_lang === 'en' ? 'Price:' : 'Preis:') . "</strong> {$pricePerUnit} {$currencyCode}/{$unitCode}
                    (" . ($current_lang === 'en' ? 'Total:' : 'Gesamt:') . " {$totalPrice} {$currencyCode})";

            if ($location) {
                $emailContent .= "<br><strong>" . ($current_lang === 'en' ? 'Location:' : 'Standort:') . "</strong> {$location}";
            }

            $emailContent .= "
                </p>

                <h3>" . ($current_lang === 'en' ? 'Contact Details:' : 'Kontaktdaten:') . "</h3>
                <p>
                    <strong>" . ($current_lang === 'en' ? 'Name:' : 'Name:') . "</strong> {$senderName}<br>
                    <strong>" . ($current_lang === 'en' ? 'Email:' : 'E-Mail:') . "</strong> <a href='mailto:{$senderEmail}'>{$senderEmail}</a><br>
                    <strong>" . ($current_lang === 'en' ? 'Phone:' : 'Telefon:') . "</strong> {$senderTelefon}
                </p>

                <h3>" . ($current_lang === 'en' ? 'Message:' : 'Nachricht:') . "</h3>
                <p>{$nachricht}</p>

                <p style='margin-top: 30px;'>
                    <a href='" . BASE_URL . "em_meine_angebote.php' class='btn'>" .
                    ($current_lang === 'en' ? 'Manage My Listings' : 'Meine Angebote verwalten') . "</a>
                </p>
            ";

            $emailSubject = ($current_lang === 'en' ? 'New inquiry about your ' : 'Neue Anfrage zu Ihrem ') . $metalName . ($current_lang === 'en' ? ' listing' : '-Angebot');
            sendEmail($listing['seller_email'], $emailSubject, getEmailTemplate($emailContent), MAIL_FROM, $senderEmail);
        }

        $success = true;
    } catch (Exception $e) {
        $error = ($current_lang === 'en')
            ? 'Error: ' . $e->getMessage()
            : 'Fehler: ' . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="container">
    <h2>📧 <?= ($current_lang === 'en') ? 'Contact Seller' : 'Verkäufer kontaktieren' ?></h2>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php if ($current_lang === 'en'): ?>
                ✓ Your message has been sent successfully! The seller will contact you soon.
            <?php else: ?>
                ✓ Ihre Nachricht wurde erfolgreich versendet! Der Verkäufer wird sich bei Ihnen melden.
            <?php endif; ?>
            <br><br>
            <a href="index.php?exchange=metals" class="btn"><?= ($current_lang === 'en') ? 'Back to Listings' : 'Zurück zur Übersicht' ?></a>
        </div>
    <?php else: ?>

    <div class="card" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
        <h3><?= ($current_lang === 'en') ? 'Listing Details' : 'Angebot-Details' ?></h3>
        <p>
            <strong><?= htmlspecialchars($current_lang === 'en' ? ($listing['title_en'] ?: $listing['title_de']) : $listing['title_de']) ?></strong>
        </p>
        <p>
            <?php
                $metal_icons = ['XAU' => '🥇', 'XAG' => '🥈', 'XPT' => '⚪', 'XPD' => '🔘'];
                echo $metal_icons[$listing['metal_symbol']] ?? '💰';
            ?>
            <strong><?= htmlspecialchars($listing['metal_name']) ?></strong> |
            <?= number_format($listing['quantity'], 2) ?> <?= $listing['unit_code'] ?>
            <?php if ($listing['purity']): ?>
                | <?= ($current_lang === 'en') ? 'Purity' : 'Feinheit' ?>: <?= $listing['purity'] ?>
            <?php endif; ?>
        </p>
        <p class="price" style="font-weight: bold; font-size: 1.2em; color: #2c3e50;">
            <?= number_format($listing['price_per_unit'], 2, ',', '.') ?> <?= $listing['currency_code'] ?>/<?= $listing['unit_code'] ?>
            <small>(Total: <?= number_format($listing['total_price'], 2, ',', '.') ?> <?= $listing['currency_code'] ?>)</small>
        </p>
        <?php if ($listing['ort']): ?>
        <p>
            📍 <?= htmlspecialchars($listing['plz'] . ' ' . $listing['ort']) ?>
            <?php if ($listing['land']): ?>
                (<?= htmlspecialchars($listing['land']) ?>)
            <?php endif; ?>
        </p>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error" style="background: #fee; border: 1px solid #fcc; padding: 15px; margin-bottom: 20px; border-radius: 5px; color: #c00;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($error) || strpos($error, 'eigenen') === false): ?>
    <div class="card" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
        <h3><?= ($current_lang === 'en') ? 'Your Contact Request' : 'Ihre Kontaktanfrage' ?></h3>
        <form method="POST">
            <?php if (!$is_logged_in): ?>
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">
                    <?= ($current_lang === 'en') ? 'Your Name *' : 'Ihr Name *' ?>
                </label>
                <input type="text" name="name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">
                    <?= ($current_lang === 'en') ? 'Your Email *' : 'Ihre E-Mail *' ?>
                </label>
                <input type="email" name="email" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">
                    <?= ($current_lang === 'en') ? 'Your Phone Number' : 'Ihre Telefonnummer' ?>
                </label>
                <input type="text" name="telefon" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <?php else: ?>
            <p style="background: #e7f3ff; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                <?= ($current_lang === 'en') ? 'Logged in as' : 'Angemeldet als' ?>: <strong><?= htmlspecialchars($current_user['name']) ?></strong>
                (<?= htmlspecialchars($current_user['email']) ?>)
            </p>
            <?php endif; ?>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">
                    <?= ($current_lang === 'en') ? 'Your Message *' : 'Ihre Nachricht *' ?>
                </label>
                <textarea name="nachricht" required
                          placeholder="<?= ($current_lang === 'en') ? 'Describe your interest in this precious metal offer...' : 'Beschreiben Sie Ihr Interesse an diesem Edelmetall-Angebot...' ?>"
                          style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; min-height: 120px;"></textarea>
            </div>

            <button type="submit" class="btn" style="background: #f39c12; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-right: 10px;">
                📧 <?= ($current_lang === 'en') ? 'Send Message' : 'Nachricht senden' ?>
            </button>
            <a href="index.php?exchange=metals" class="btn" style="display: inline-block; background: #95a5a6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                <?= ($current_lang === 'en') ? 'Cancel' : 'Abbrechen' ?>
            </a>
        </form>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
