<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Debug HTTP 500</title></head><body>";
echo "<h1>🔍 Debug: HTTP 500 Error - kontakt.php</h1>";
echo "<pre>";

echo "=== STEP 1: Loading config.php ===\n";
try {
    require_once 'includes/config.php';
    echo "✓ config.php loaded\n\n";
} catch (Exception $e) {
    echo "✗ ERROR loading config.php: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit;
}

echo "=== STEP 2: Check functions ===\n";
echo "sendEmail: " . (function_exists('sendEmail') ? '✓' : '✗') . "\n";
echo "getEmailTemplate: " . (function_exists('getEmailTemplate') ? '✓' : '✗') . "\n";
echo "getCurrentUser: " . (function_exists('getCurrentUser') ? '✓' : '✗') . "\n\n";

echo "=== STEP 3: Loading lang.php ===\n";
try {
    require_once 'includes/lang.php';
    echo "✓ lang.php loaded\n\n";
} catch (Exception $e) {
    echo "✗ ERROR loading lang.php: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit;
}

echo "=== STEP 4: Test database query (like kontakt.php) ===\n";
$lagerraum_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
echo "Testing with lagerraum_id = $lagerraum_id\n";

try {
    $stmt = $pdo->prepare("
        SELECT lr.*, u.email as anbieter_email, u.name as anbieter_name
        FROM lg_v_angebote lr
        JOIN lg_users u ON lr.anbieter_id = u.user_id
        WHERE lr.lagerraum_id = :id
    ");
    $stmt->execute([':id' => $lagerraum_id]);
    $lagerraum = $stmt->fetch();

    if ($lagerraum) {
        echo "✓ Lagerraum found: " . $lagerraum['strasse'] . " in " . $lagerraum['ort'] . "\n";
        echo "✓ Anbieter email: " . $lagerraum['anbieter_email'] . "\n\n";
    } else {
        echo "✗ No lagerraum found with ID $lagerraum_id\n\n";
    }
} catch (PDOException $e) {
    echo "✗ DATABASE ERROR: " . $e->getMessage() . "\n\n";
}

echo "=== STEP 5: Test email functions ===\n";
try {
    $testHtml = getEmailTemplate("<p>Test content</p>");
    echo "✓ getEmailTemplate() works (generated " . strlen($testHtml) . " bytes)\n\n";
} catch (Exception $e) {
    echo "✗ getEmailTemplate() error: " . $e->getMessage() . "\n\n";
}

echo "=== STEP 6: Loading header.php ===\n";
try {
    require_once 'includes/header.php';
    echo "✓ header.php loaded\n\n";
} catch (Exception $e) {
    echo "✗ ERROR loading header.php: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "=== ALL TESTS PASSED ===\n";
echo "If you see this, kontakt.php should work.\n";
echo "If kontakt.php still gives 500, check:\n";
echo "1. Browser console for JS errors\n";
echo "2. Different error not caught here\n";
echo "\n<a href='kontakt.php?id=1'>→ Try kontakt.php?id=1</a>\n";

echo "</pre></body></html>";
?>
