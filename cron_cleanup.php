<?php
/**
 * Cron-Job: Löscht Angebote und Nachfragen, die älter als 60 Tage sind
 * Ausführen: 0 3 * * * /usr/bin/php /var/www/web2/cron_cleanup.php
 */

require_once __DIR__ . '/includes/config.php';

$deleted_angebote = 0;
$deleted_nachfragen = 0;

try {
    // Lagerräume älter als 60 Tage deaktivieren
    $stmt = $pdo->prepare("
        UPDATE lg_lagerraeume 
        SET aktiv = 0 
        WHERE aktiv = 1 
        AND erstellt_am < DATE_SUB(NOW(), INTERVAL :days DAY)
    ");
    $stmt->execute([':days' => AUTO_DELETE_DAYS]);
    $deleted_angebote = $stmt->rowCount();
    
    // Suchanfragen älter als 60 Tage deaktivieren
    $stmt = $pdo->prepare("
        UPDATE lg_suchanfragen 
        SET aktiv = 0 
        WHERE aktiv = 1 
        AND erstellt_am < DATE_SUB(NOW(), INTERVAL :days DAY)
    ");
    $stmt->execute([':days' => AUTO_DELETE_DAYS]);
    $deleted_nachfragen = $stmt->rowCount();
    
    // Log-Eintrag
    $log_msg = sprintf(
        "[%s] Cleanup: %d Angebote, %d Nachfragen deaktiviert (älter als %d Tage)\n",
        date('Y-m-d H:i:s'),
        $deleted_angebote,
        $deleted_nachfragen,
        AUTO_DELETE_DAYS
    );
    
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    file_put_contents(__DIR__ . '/logs/cleanup.log', $log_msg, FILE_APPEND);
    
    echo $log_msg;
    
} catch (Exception $e) {
    $error_msg = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/logs/cleanup.log', $error_msg, FILE_APPEND);
    echo $error_msg;
}
?>
