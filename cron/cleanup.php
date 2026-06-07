<?php
/**
 * TempInbox Cleanup Script
 * 
 * Automatically deletes expired email aliases, removes old messages based on 
 * system retention limits, and executes a SQLite database VACUUM operation 
 * to reclaim disk space.
 */

// Define directory constants
define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', ROOT_DIR . '/app');

// Class Autoloader (PSR-4 Compliant, Case-insensitive folder matching for Linux)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = ROOT_DIR . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    
    // Split namespace parts
    $parts = explode('\\', $relative_class);
    $className = array_pop($parts); // Keep class name as-is (e.g. HomeController)
    
    // Lowercase all directories (e.g. Controllers -> controllers)
    $folders = array_map('strtolower', $parts);
    $subPath = !empty($folders) ? implode('/', $folders) . '/' : '';
    
    $file = $base_dir . $subPath . $className . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Models\Alias;
use App\Models\Message;
use App\Models\Setting;
use App\Models\Log;
use App\Services\Database;

// Ensure output is text/plain if run in browser
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=UTF-8');
}

echo "[" . date('Y-m-d H:i:s') . "] Starting Database Maintenance Cleanup...\n";

try {
    $db = Database::getInstance();
    
    // 1. Delete Expired Aliases
    // NOTE: Foreign keys cascade delete all associated messages
    echo "Cleaning up expired aliases...\n";
    $expiredDeleted = Alias::deleteExpired();
    echo "Deleted expired aliases: $expiredDeleted\n";

    // 2. Delete Old Messages based on retention settings
    $retentionDays = (int)Setting::get('email_retention_days', '7');
    echo "Cleaning up messages older than $retentionDays days...\n";
    $messagesDeleted = Message::deleteOld($retentionDays);
    echo "Deleted old messages: $messagesDeleted\n";

    // 3. Optimize database file size
    echo "Executing SQLite VACUUM to reclaim disk space...\n";
    $db->query("VACUUM");
    echo "SQLite database optimized.\n";

    // Log the maintenance run
    $summary = "Maintenance run finished. Deleted $expiredDeleted aliases and $messagesDeleted old messages. Database size optimized.";
    echo "[$summary]\n";
    
    Log::info("Cleanup Cron: $summary");

} catch (Exception $e) {
    $errMsg = "Cleanup Cron Error: " . $e->getMessage();
    echo "[$errMsg]\n";
    Log::error($errMsg);
}
