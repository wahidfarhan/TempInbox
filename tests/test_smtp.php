<?php
/**
 * TempInbox SMTP Integration & Diagnostics Test Script
 * 
 * Run this script from CLI to verify database and SMTP setup.
 * Usage: php tests/test_smtp.php [test_recipient_email]
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
    $className = array_pop($parts);
    
    // Lowercase directories
    $folders = array_map('strtolower', $parts);
    $subPath = !empty($folders) ? implode('/', $folders) . '/' : '';
    
    $file = $base_dir . $subPath . $className . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Services\Database;
use App\Services\SmtpService;
use App\Models\Setting;

echo "==================================================\n";
echo "       TempInbox SMTP Diagnostics & Test Tool\n";
echo "==================================================\n\n";

// 1. Test Database Settings Load
echo "Step 1: Loading SMTP configuration...\n";
try {
    $db = Database::getInstance(); // Ensure DB is initialized
    $config = require ROOT_DIR . '/config/config.php';
    
    $host = Setting::get('smtp_host', $config['smtp']['host'] ?? '');
    $port = Setting::get('smtp_port', $config['smtp']['port'] ?? 587);
    $encryption = Setting::get('smtp_encryption', $config['smtp']['encryption'] ?? 'tls');
    $username = Setting::get('smtp_username', $config['smtp']['username'] ?? '');
    
    echo "   [INFO] SMTP Host: $host\n";
    echo "   [INFO] SMTP Port: $port\n";
    echo "   [INFO] Encryption: $encryption\n";
    echo "   [INFO] SMTP User: " . ($username ?: '(None)') . "\n";
    
    if (empty($host)) {
        echo "   [WARNING] SMTP host is empty. Falling back to IMAP configuration: " . ($config['imap']['host'] ?? 'none') . "\n";
    }
} catch (Exception $e) {
    echo "   [FAIL] Could not load database configurations: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// 2. Test SMTP Send
echo "Step 2: Testing SMTP client transmission...\n";
try {
    $recipient = $argv[1] ?? null;

    if (empty($recipient)) {
        echo "   [INFO] No test recipient address specified. Skipping actual send attempt.\n";
        echo "   [TIP] Run: php tests/test_smtp.php recipient@example.com to send a live test message.\n";
    } elseif ($username === 'catchall@example.com' || $config['smtp']['password'] === 'your-secure-password') {
        echo "   [WARNING] Default credentials detected. Skipping live sending to avoid test failure.\n";
        echo "   Please update your configurations first.\n";
    } else {
        echo "   Sending test email to <$recipient>...\n";
        
        $smtp = new SmtpService();
        $senderEmail = 'test-diagnostics@' . ($config['app']['allowed_domains'][0] ?? 'localhost');
        $senderName = 'TempInbox Diagnostics';
        $subject = 'TempInbox Outbound Test - ' . date('Y-m-d H:i:s');
        
        $htmlBody = "<h1>TempInbox Outbound Success!</h1><p>This message confirms that your SMTP socket-client is fully configured and working. Sent at: <strong>" . date('Y-m-d H:i:s') . "</strong></p>";
        $plainBody = "TempInbox Outbound Success!\nThis message confirms that your SMTP socket-client is fully configured and working. Sent at: " . date('Y-m-d H:i:s');
        
        $success = $smtp->send(
            $senderEmail,
            $senderName,
            $recipient,
            $subject,
            $htmlBody,
            $plainBody
        );
        
        if ($success) {
            echo "   [SUCCESS] Test email sent successfully to $recipient!\n";
            echo "   Please check the recipient mailbox (and spam folder) to verify.\n";
        } else {
            echo "   [FAIL] SMTP reported failure during transmission.\n";
        }
    }
} catch (Exception $e) {
    echo "   [FAIL] SMTP Client failed: " . $e->getMessage() . "\n";
    echo "   Troubleshooting tips:\n";
    echo "   - Ensure outbound connections to port $port are not blocked by firewall (e.g. CSF/CPHulk).\n";
    echo "   - Double check SMTP server name, port, authentication credentials, and TLS/SSL settings.\n";
}

echo "\n==================================================\n";
echo "Diagnostics complete.\n";
echo "==================================================\n";
