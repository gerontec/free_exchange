<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Check Database Tables</title></head><body>";
echo "<h1>Database Tables Check</h1>";
echo "<pre>";

require_once 'includes/config.php';

echo "=== Tables starting with 'lg_' ===\n";
$tables = $pdo->query("SHOW TABLES LIKE 'lg_%'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "  - $table\n";
}

echo "\n=== Views starting with 'lg_' ===\n";
$views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_wagodb LIKE 'lg_%'")->fetchAll(PDO::FETCH_ASSOC);
if (count($views) > 0) {
    foreach ($views as $view) {
        echo "  - " . $view['Tables_in_wagodb'] . "\n";
    }
} else {
    echo "  (No views found)\n";
}

echo "\n=== Search for alternative table names ===\n";
$alternatives = $pdo->query("SHOW TABLES LIKE '%lager%'")->fetchAll(PDO::FETCH_COLUMN);
if (count($alternatives) > 0) {
    echo "Tables containing 'lager':\n";
    foreach ($alternatives as $table) {
        echo "  - $table\n";
    }
} else {
    echo "  (No tables found)\n";
}

echo "\n=== Check if lg_lagerraeume exists ===\n";
try {
    $result = $pdo->query("SELECT COUNT(*) FROM lg_lagerraeume")->fetchColumn();
    echo "✓ lg_lagerraeume exists with $result records\n";

    $sample = $pdo->query("SELECT * FROM lg_lagerraeume LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($sample) {
        echo "\nColumns in lg_lagerraeume:\n";
        foreach (array_keys($sample) as $col) {
            echo "  - $col\n";
        }
    }
} catch (PDOException $e) {
    echo "✗ lg_lagerraeume error: " . $e->getMessage() . "\n";
}

echo "\n=== Check if lg_users exists ===\n";
try {
    $result = $pdo->query("SELECT COUNT(*) FROM lg_users")->fetchColumn();
    echo "✓ lg_users exists with $result records\n";
} catch (PDOException $e) {
    echo "✗ lg_users error: " . $e->getMessage() . "\n";
}

echo "</pre></body></html>";
?>
