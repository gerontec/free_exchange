<?php
/**
 * Migration: Add lg_images table for storage listing photos
 * Run this once to create the table
 */
require_once 'includes/config.php';

try {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'lg_images'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Table lg_images already exists.\n";
        exit(0);
    }

    // Create lg_images table
    $sql = "
    CREATE TABLE `lg_images` (
      `image_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `lagerraum_id` int(10) unsigned NOT NULL,
      `filename` varchar(255) NOT NULL,
      `filepath` varchar(500) NOT NULL,
      `image_type` enum('main','detail','other') DEFAULT 'detail',
      `sort_order` tinyint(3) unsigned DEFAULT 0,
      `caption` varchar(255) DEFAULT NULL,
      `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`image_id`),
      KEY `idx_lagerraum` (`lagerraum_id`),
      KEY `idx_type` (`image_type`),
      KEY `idx_sort` (`sort_order`),
      CONSTRAINT `lg_images_ibfk_1` FOREIGN KEY (`lagerraum_id`) REFERENCES `lg_lagerraeume` (`lagerraum_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";

    $pdo->exec($sql);
    echo "✓ Table lg_images created successfully!\n";

    // Check for old lg_bilder table
    $stmt = $pdo->query("SHOW TABLES LIKE 'lg_bilder'");
    if ($stmt->rowCount() > 0) {
        echo "\n⚠ Warning: Old table 'lg_bilder' exists. Consider migrating data:\n";
        echo "   INSERT INTO lg_images (lagerraum_id, filename, filepath, image_type, sort_order)\n";
        echo "   SELECT lagerraum_id, filename, filepath, IF(is_main=1,'main','detail'), sort_order\n";
        echo "   FROM lg_bilder;\n";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
