<?php
/**
 * TempInbox Unified Cron Script
 * 
 * Manages email fetching (polls IMAP on every execution) 
 * and database maintenance (purging expired aliases/old emails & running VACUUM hourly).
 * 
 * Configure a single cron job in cPanel to point here:
 * E.g.: * * * * * php /home/username/public_html/cron/cron.php
 */

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
    $parts = explode('\\', $relative_class);
    $className = array_pop($parts);
    $folders = array_map('strtolower', $parts);
    $subPath = !empty($folders) ? implode('/', $folders) . '/' : '';
    
    $file = $base_dir . $subPath . $className . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Services\ImapService;
use App\Services\Database;
use App\Models\Alias;
use App\Models\Message;
use App\Models\Setting;
use App\Models\Log;
use App\Services\MailParser;

// Ensure output is text/plain if run in browser
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=UTF-8');
}

echo "[" . date('Y-m-d H:i:s') . "] --- Unified Cron Execution Start ---\n";

// ==========================================
// Phase 1: Email Fetch Engine (Runs every minute)
// ==========================================
echo "\n[1/2] Starting Email Fetch Engine...\n";
try {
    $imap = new ImapService();
    $imap->connect();
    
    $uids = $imap->getMessageUids();
    $totalMailboxCount = count($uids);
    echo "   Total messages on IMAP server: $totalMailboxCount\n";

    if (!empty($uids)) {
        $config = require ROOT_DIR . '/config/config.php';
        $fetchLimit = $config['imap']['fetch_limit'] ?? 50;
        
        $processedCount = 0;
        $importedCount = 0;
        
        sort($uids);
        foreach ($uids as $uid) {
            if ($processedCount >= $fetchLimit) {
                echo "   Reached fetch limit of $fetchLimit. Stopping this iteration.\n";
                break;
            }

            if (Message::isDuplicate((string)$uid)) {
                continue;
            }

            $processedCount++;
            $email = $imap->fetchMessage($uid);
            if (!$email) continue;

            $recipients = $imap->detectRecipients($email['headers']);
            $matchedAlias = null;
            
            foreach ($recipients as $recipientEmail) {
                $target = Alias::findByEmail($recipientEmail);
                if ($target && Alias::isValid($target)) {
                    $matchedAlias = $target;
                    break;
                }
            }

            if ($matchedAlias) {
                $senderHeader = $email['headers']['from'] ?? '';
                $senderInfo = MailParser::parseEmailAddress($senderHeader);
                $receivedHeader = $email['headers']['date'] ?? '';
                $receivedTime = strtotime($receivedHeader);
                $receivedAt = $receivedTime ? date('Y-m-d H:i:s', $receivedTime) : date('Y-m-d H:i:s');
                $subject = MailParser::decodeHeader($email['headers']['subject'] ?? '');
                $messageId = $email['headers']['message-id'] ?? null;

                Message::create(
                    (int)$matchedAlias['id'],
                    (string)$uid,
                    $messageId,
                    $senderInfo['name'],
                    $senderInfo['email'],
                    $subject,
                    $email['plain'],
                    $email['html'],
                    $email['attachments'],
                    $receivedAt
                );
                
                echo "   [UID $uid] MATCHED & IMPORTED -> " . $matchedAlias['alias'] . "@" . $matchedAlias['domain'] . "\n";
                $importedCount++;
            }
        }
        echo "   Email Fetch finished. Processed: $processedCount, Imported: $importedCount\n";
    } else {
        echo "   No emails to process.\n";
    }
    
    $imap->disconnect();
} catch (Exception $e) {
    $errMsg = "Email Fetch Error: " . $e->getMessage();
    echo "   [ERROR] $errMsg\n";
    Log::error("Fetch Cron: " . $errMsg);
}

// ==========================================
// Phase 2: Database Maintenance Cleanup (Runs Hourly)
// ==========================================
echo "\n[2/2] Checking Database Maintenance Cleanups...\n";
try {
    $db = Database::getInstance();
    
    $lastCleanup = (int)Setting::get('last_cleanup_time', '0');
    $currentTime = time();
    $cleanupInterval = 3600; // 1 hour

    if (($currentTime - $lastCleanup) >= $cleanupInterval) {
        echo "   Cleanup is due (last run: " . ($lastCleanup ? date('Y-m-d H:i:s', $lastCleanup) : 'Never') . "). Running...\n";
        
        // 1. Delete Expired Aliases (Foreign key cascades delete messages)
        $expiredDeleted = Alias::deleteExpired();
        echo "   Deleted expired aliases: $expiredDeleted\n";

        // 2. Delete Old Messages based on retention settings
        $retentionDays = (int)Setting::get('email_retention_days', '7');
        $messagesDeleted = Message::deleteOld($retentionDays);
        echo "   Deleted messages older than $retentionDays days: $messagesDeleted\n";

        // 3. Optimize database file size
        echo "   Executing SQLite VACUUM to reclaim disk space...\n";
        $db->query("VACUUM");
        echo "   SQLite database optimized.\n";

        // Update last cleanup timestamp in settings
        Setting::set('last_cleanup_time', (string)$currentTime);

        $summary = "Database maintenance complete. Deleted $expiredDeleted aliases and $messagesDeleted old messages.";
        echo "   [SUCCESS] $summary\n";
        Log::info("Cleanup Cron: $summary");
    } else {
        $minutesLeft = round(($cleanupInterval - ($currentTime - $lastCleanup)) / 60);
        echo "   Cleanup skipped. Next run in $minutesLeft minutes.\n";
    }
} catch (Exception $e) {
    $errMsg = "Cleanup Maintenance Error: " . $e->getMessage();
    echo "   [ERROR] $errMsg\n";
    Log::error("Cleanup Cron: " . $errMsg);
}

echo "\n[" . date('Y-m-d H:i:s') . "] --- Unified Cron Execution Finished ---\n";
