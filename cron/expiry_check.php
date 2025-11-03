<?php
/**
 * Cron Job Script - Certificate Expiry Check
 * 
 * Run this script daily via cron or scheduled task
 * 
 * Cron setup (Linux):
 * 0 0 * * * /usr/bin/php /path/to/SYSTEMINTEG/cron/expiry_check.php
 * 
 * Windows Task Scheduler:
 * Create a scheduled task to run: php.exe C:\xampp\htdocs\SYSTEMINTEG\cron\expiry_check.php
 */

// Set script execution time limit
set_time_limit(300);

// Load configuration
require_once __DIR__ . '/../config.php';

echo "Starting certificate expiry check...\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $result = ExpiryManager::updateExpiredCertificates();
    
    echo "Expired certificates updated: " . $result['updated'] . "\n";
    
    if ($result['success']) {
        echo "✓ Expiry check completed successfully\n";
    } else {
        echo "✗ Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
    // Send expiry notifications (for certificates expiring in 7 days)
    $notified = ExpiryManager::sendExpiryNotifications(7);
    echo "Expiry notifications sent: {$notified}\n";
    
} catch (Exception $e) {
    echo "✗ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";

?>

