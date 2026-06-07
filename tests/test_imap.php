<?php
/**
 * TempInbox Integration & Diagnostics Test Script
 * 
 * Run this script from CLI to verify database and IMAP setup.
 * Usage: php tests/test_imap.php
 */

define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', ROOT_DIR . '/app');

// Autoloader (PSR-4 Compliant, Case-insensitive folder matching for Linux)
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

use App\Services\Database;
use App\Services\ImapService;
use App\Models\Setting;

echo "==================================================\n";
echo "       TempInbox Diagnostics & Test Tool\n";
echo "==================================================\n\n";

// 1. Test Database Connectivity
echo "Step 1: Testing SQLite database integration...\n";
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "   [SUCCESS] SQLite Connection established.\n";
    
    $config = require ROOT_DIR . '/config/config.php';
    echo "   [INFO] Database File Location: " . $config['db']['path'] . "\n";
    echo "   [INFO] Database File Size: " . number_format(filesize($config['db']['path']) / 1024, 2) . " KB\n";

    // Query tables count
    $tables = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table'");
    echo "   [INFO] Active tables in schema: " . implode(', ', array_column($tables, 'name')) . "\n";
    
    // Check settings
    $adminUser = Setting::get('admin_username');
    echo "   [INFO] Admin Username: $adminUser\n";
    
} catch (Exception $e) {
    echo "   [FAIL] Database testing failed: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Test IMAP Connection
echo "Step 2: Testing IMAP Connection settings...\n";
try {
    $config = require ROOT_DIR . '/config/config.php';
    $imapConfig = $config['imap'];
    
    echo "   [INFO] Server: " . $imapConfig['host'] . ":" . $imapConfig['port'] . "\n";
    echo "   [INFO] User: " . $imapConfig['username'] . "\n";
    echo "   [INFO] Encryption: " . $imapConfig['encryption'] . "\n";
    
    if ($imapConfig['username'] === 'catchall@example.com' || $imapConfig['password'] === 'your-secure-password') {
        echo "   [WARNING] Default credentials detected. Skipping IMAP link attempt.\n";
        echo "   Please update your configuration in config/config.php first.\n";
    } else {
        $imap = new ImapService();
        echo "   Connecting to server...\n";
        $imap->connect();
        echo "   [SUCCESS] Successfully authenticated and linked to IMAP.\n";
        
        $uids = $imap->getMessageUids();
        echo "   [INFO] Found " . count($uids) . " messages in folder: " . ($imapConfig['folder'] ?? 'INBOX') . "\n";
        
        $imap->disconnect();
    }
} catch (Exception $e) {
    echo "   [FAIL] IMAP Connectivity failed: " . $e->getMessage() . "\n";
    echo "   Troubleshooting tip:\n";
    echo "   - Ensure PHP's IMAP extension is enabled (check php.ini or phpinfo())\n";
    echo "   - Double check your username, password, hostname, and port.\n";
    echo "   - Try toggling secure SSL validation 'validate_cert' to false in config/config.php.\n";
}

echo "\n==================================================\n";
echo "Diagnostics complete.\n";
echo "==================================================\n";
