<?php
require_once 'includes/config.php';

$lagerraum_id = 1;

// Test 1: Check if table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'lg_lagerraeume'");
    $table_exists = $stmt->rowCount() > 0;
    echo "lg_lagerraeume table exists: " . ($table_exists ? "YES" : "NO") . "<br>";
} catch (Exception $e) {
    echo "Error checking table: " . $e->getMessage() . "<br>";
}

// Test 2: Check if view exists
try {
    $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_wagodb LIKE 'lg_%'");
    echo "<br>Views found:<br>";
    while ($row = $stmt->fetch()) {
        echo " - " . $row[0] . "<br>";
    }
} catch (Exception $e) {
    echo "Error checking views: " . $e->getMessage() . "<br>";
}

// Test 3: Try to query lg_v_angebote
try {
    $stmt = $pdo->prepare("SELECT * FROM lg_v_angebote WHERE lagerraum_id = :id LIMIT 1");
    $stmt->execute([':id' => $lagerraum_id]);
    $data = $stmt->fetch();
    echo "<br>lg_v_angebote query result:<br>";
    if ($data) {
        echo "Columns: " . implode(", ", array_keys($data)) . "<br>";
    } else {
        echo "No data found for lagerraum_id = " . $lagerraum_id . "<br>";
    }
} catch (Exception $e) {
    echo "<br>Error querying lg_v_angebote: " . $e->getMessage() . "<br>";
}

// Test 4: Try querying lg_lagerraeume directly
try {
    $stmt = $pdo->prepare("SELECT * FROM lg_lagerraeume WHERE lagerraum_id = :id LIMIT 1");
    $stmt->execute([':id' => $lagerraum_id]);
    $data = $stmt->fetch();
    echo "<br>lg_lagerraeume query result:<br>";
    if ($data) {
        echo "Columns: " . implode(", ", array_keys($data)) . "<br>";
    } else {
        echo "No data found for lagerraum_id = " . $lagerraum_id . "<br>";
    }
} catch (Exception $e) {
    echo "<br>Error querying lg_lagerraeume: " . $e->getMessage() . "<br>";
}
?>
