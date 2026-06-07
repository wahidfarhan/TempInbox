<?php
/**
 * TempInbox Email Fetch Engine
 * 
 * Connects to the catch-all IMAP mailbox, reads unread or unimported emails,
 * extracts intended alias recipients, stores valid messages in SQLite,
 * and skips duplicates based on IMAP UIDs.
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

use App\Services\ImapService;
use App\Services\Database;
use App\Models\Alias;
use App\Models\Message;
use App\Models\Log;
use App\Services\MailParser;

// Ensure output is text/plain if run in browser
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=UTF-8');
}

echo "[" . date('Y-m-d H:i:s') . "] Starting Email Fetch Engine...\n";

try {
    $imap = new ImapService();
    
    echo "Connecting to IMAP mail server...\n";
    $imap->connect();
    echo "Successfully connected.\n";

    echo "Fetching email list...\n";
    $uids = $imap->getMessageUids();
    $totalMailboxCount = count($uids);
    echo "Total messages on mail server: $totalMailboxCount\n";

    if (empty($uids)) {
        echo "No emails to process.\n";
        $imap->disconnect();
        exit;
    }

    $config = require ROOT_DIR . '/config/config.php';
    $fetchLimit = $config['imap']['fetch_limit'] ?? 50;

    $processedCount = 0;
    $importedCount = 0;
    $skippedCount = 0;

    // We process emails starting from the newest ones or oldest?
    // Processing oldest first matches standard pipeline logic (ascending order)
    sort($uids);

    foreach ($uids as $uid) {
        // Enforce execution limits per run to prevent timeout on shared hosting
        if ($processedCount >= $fetchLimit) {
            echo "Reached fetch limit of $fetchLimit. Stopping this iteration.\n";
            break;
        }

        // 1. Check if already imported
        if (Message::isDuplicate((string)$uid)) {
            $skippedCount++;
            continue;
        }

        $processedCount++;
        echo "Processing Email UID: $uid...\n";

        // 2. Fetch full email message
        $email = $imap->fetchMessage($uid);
        if (!$email) {
            echo "   Failed to fetch message details for UID: $uid\n";
            continue;
        }

        // 3. Scan headers for recipients
        $recipients = $imap->detectRecipients($email['headers']);
        echo "   Detected recipients: " . implode(', ', $recipients) . "\n";

        $matchedAlias = null;

        // 4. Match recipients to our active aliases
        foreach ($recipients as $recipientEmail) {
            $alias = Alias::findByEmail($recipientEmail);
            if ($alias && Alias::isValid($alias)) {
                $matchedAlias = $alias;
                break; // Found matching active alias
            }
        }

        if ($matchedAlias) {
            // 5. Extract sender address info
            $senderHeader = $email['headers']['from'] ?? '';
            $senderInfo = MailParser::parseEmailAddress($senderHeader);

            // Parse Date header or fallback to current time
            $receivedHeader = $email['headers']['date'] ?? '';
            $receivedTime = strtotime($receivedHeader);
            $receivedAt = $receivedTime ? date('Y-m-d H:i:s', $receivedTime) : date('Y-m-d H:i:s');

            // Subject parsing
            $subject = MailParser::decodeHeader($email['headers']['subject'] ?? '');
            $messageId = $email['headers']['message-id'] ?? null;

            // Import email message
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

            echo "   -> MATCHED & IMPORTED for alias: " . $matchedAlias['alias'] . "@" . $matchedAlias['domain'] . "\n";
            $importedCount++;
        } else {
            echo "   -> No active alias matched. Skipping storing.\n";
        }
    }

    $imap->disconnect();
    
    $summary = "Fetch completed. Scanned: $processedCount, Imported: $importedCount, Skipped/Existing: $skippedCount.";
    echo "[$summary]\n";
    
    if ($importedCount > 0) {
        Log::info("Email Fetch Engine: $summary");
    }

} catch (Exception $e) {
    $errMsg = "Fetch Engine Error: " . $e->getMessage();
    echo "[$errMsg]\n";
    Log::error($errMsg);
}
