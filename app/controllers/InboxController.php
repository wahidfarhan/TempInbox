<?php

namespace App\Controllers;

use App\Models\Alias;
use App\Models\Message;
use App\Services\MailParser;

use App\Models\Setting;
use App\Services\RateLimiter;

/**
 * Inbox Controller
 * 
 * Manages the user inbox interface, message details APIs, 
 * and sandboxed HTML rendering.
 */
class InboxController extends BaseController
{
    /**
     * View Inbox and list messages (supports pagination)
     */
    public function index(): void
    {
        $token = $this->input('token');

        if (empty($token)) {
            $this->redirect('/');
        }

        $alias = Alias::findByToken($token);

        if (!$alias || !Alias::isValid($alias)) {
            // Expired or invalid alias
            $this->render('home/index', [
                'title' => 'TempInbox - Free Temporary Email System',
                'error' => 'This inbox has expired or does not exist. Please create a new one.',
                'domains' => json_decode(Setting::get('allowed_domains', '[]'), true) ?: (require ROOT_DIR . '/config/config.php')['app']['allowed_domains'],
                'default_expiry' => Setting::get('default_expiration_hours', '24')
            ]);
            return;
        }

        // Pagination setup
        $page = (int)$this->input('page', 1);
        if ($page < 1) $page = 1;
        
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $totalMessages = Message::getCountByAlias((int)$alias['id']);
        $totalPages = ceil($totalMessages / $limit);
        if ($totalPages < 1) $totalPages = 1;

        $messages = Message::getByAlias((int)$alias['id'], $limit, $offset);

        // Calculate time left in seconds
        $timeLeft = strtotime($alias['expires_at']) - time();

        // Check if it's an AJAX call for message lists updates (for auto-refresh)
        if ($this->input('ajax') === '1') {
            $this->json([
                'success' => true,
                'messages' => $messages,
                'total_count' => $totalMessages
            ]);
        }

        $this->render('inbox/view', [
            'title' => 'Inbox for ' . $alias['alias'] . '@' . $alias['domain'],
            'alias' => $alias,
            'messages' => $messages,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'time_left' => $timeLeft,
            'total_messages' => $totalMessages
        ]);
    }

    /**
     * Retrieve a specific message metadata and plain body (AJAX endpoint)
     */
    public function message(): void
    {
        $token = $this->input('token');
        $msgId = (int)$this->input('id');

        if (empty($token) || $msgId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid parameters.'], 400);
        }

        $alias = Alias::findByToken($token);
        if (!$alias || !Alias::isValid($alias)) {
            $this->json(['success' => false, 'message' => 'Unauthorized or expired session.'], 403);
        }

        $message = Message::findByIdAndAlias($msgId, (int)$alias['id']);
        if (!$message) {
            $this->json(['success' => false, 'message' => 'Message not found.'], 404);
        }

        // Prepare response
        $this->json([
            'success' => true,
            'id' => $message['id'],
            'sender_name' => htmlspecialchars($message['sender_name'] ?? 'Unknown Sender'),
            'sender_email' => htmlspecialchars($message['sender_email'] ?? ''),
            'subject' => htmlspecialchars($message['subject'] ?? '(No Subject)'),
            'received_at' => $message['received_at'],
            'body_plain' => nl2br(htmlspecialchars($message['body_plain'] ?? '')),
            'has_html' => !empty($message['body_html']),
            'attachments' => $message['attachments']
        ]);
    }

    /**
     * Renders email HTML body securely within a sandboxed iframe
     */
    public function htmlBody(): void
    {
        $token = $this->input('token');
        $msgId = (int)$this->input('id');

        if (empty($token) || $msgId <= 0) {
            die("Access denied. Invalid parameters.");
        }

        $alias = Alias::findByToken($token);
        if (!$alias || !Alias::isValid($alias)) {
            die("Access denied. Expired or invalid token.");
        }

        $message = Message::findByIdAndAlias($msgId, (int)$alias['id']);
        if (!$message || empty($message['body_html'])) {
            die("Message HTML body not available.");
        }

        // Sanitize html body
        $sanitizedHtml = MailParser::sanitizeHtml($message['body_html']);

        // Serve inline output with appropriate UTF-8 encoding
        header("Content-Type: text/html; charset=UTF-8");
        // Force sandboxing in browser header for extra defense-in-depth
        header("Content-Security-Policy: default-src 'self'; style-src 'unsafe-inline' *; img-src *; media-src *; script-src 'none'; frame-src 'none';");
        
        echo $sanitizedHtml;
        exit;
    }

    /**
     * Manually trigger IMAP check and sync (AJAX endpoint)
     */
    public function refresh(): void
    {
        $token = $this->input('token');

        if (empty($token)) {
            $this->json(['success' => false, 'message' => 'Invalid parameters.'], 400);
        }

        $alias = Alias::findByToken($token);
        if (!$alias || !Alias::isValid($alias)) {
            $this->json(['success' => false, 'message' => 'Inbox has expired or does not exist.'], 403);
        }

        // 1. Rate Limit: check manual refresh at most once every 5 seconds per client
        if (!RateLimiter::check('manual_refresh')) {
            $this->json([
                'success' => false, 
                'message' => 'Please wait a few seconds before checking for new messages again.'
            ], 429);
        }

        try {
            $imap = new \App\Services\ImapService();
            $imap->connect();
            $uids = $imap->getMessageUids();

            $importedCount = 0;
            if (!empty($uids)) {
                sort($uids);
                foreach ($uids as $uid) {
                    if (Message::isDuplicate((string)$uid)) {
                        continue;
                    }

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
                        $importedCount++;
                    }
                }
            }
            $imap->disconnect();

            // Fetch latest messages (first page)
            $messages = Message::getByAlias((int)$alias['id'], 15, 0);
            $totalCount = Message::getCountByAlias((int)$alias['id']);

            $this->json([
                'success' => true,
                'imported_count' => $importedCount,
                'messages' => $messages,
                'total_count' => $totalCount
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Connection Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send email outbound API (AJAX endpoint)
     */
    public function send(): void
    {
        // 1. Verify Request and CSRF
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        }

        $this->verifyCsrf();

        // 2. Validate token and parameters
        $token = $this->input('token');
        $to = trim($this->input('to', ''));
        $subject = trim($this->input('subject', ''));
        $body = trim($this->input('body', ''));
        $inReplyToId = (int)$this->input('in_reply_to_id', 0);

        if (empty($token) || empty($to) || empty($body)) {
            $this->json(['success' => false, 'message' => 'To, subject, and body fields are required.'], 400);
        }

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Invalid recipient email address.'], 400);
        }

        // 3. Find Alias
        $alias = Alias::findByToken($token);
        if (!$alias || !Alias::isValid($alias)) {
            $this->json(['success' => false, 'message' => 'Inbox has expired or does not exist.'], 403);
        }

        // 4. Rate Limiting: max outbound limit checks
        if (!RateLimiter::check('send_email')) {
            $this->json(['success' => false, 'message' => 'Rate limit exceeded. Please wait a moment before sending another email.'], 429);
        }

        try {
            // 5. Determine threading headers if it is a reply
            $inReplyToHeader = '';
            if ($inReplyToId > 0) {
                $originalMsg = Message::findByIdAndAlias($inReplyToId, (int)$alias['id']);
                if ($originalMsg) {
                    $inReplyToHeader = $originalMsg['message_id'] ?? '';
                    // Set default subject prefix if missing
                    if (empty($subject)) {
                        $subject = $originalMsg['subject'] ?? '';
                    }
                    if (!empty($subject) && !str_starts_with(strtolower($subject), 're:')) {
                        $subject = 'Re: ' . $subject;
                    }
                }
            }

            if (empty($subject)) {
                $subject = '(No Subject)';
            }

            // 6. Send email using SmtpService
            $smtp = new \App\Services\SmtpService();
            
            $fromEmail = $alias['alias'] . '@' . $alias['domain'];
            $fromName = $alias['alias']; // Use alias prefix as display name

            // Convert raw plain body text into nice HTML wrapping
            $bodyHtml = nl2br(htmlspecialchars($body));
            $bodyHtml = "<div style='font-family: sans-serif; font-size: 14px; color: #333;'>" . $bodyHtml . "</div>";

            $success = $smtp->send(
                $fromEmail,
                $fromName,
                $to,
                $subject,
                $bodyHtml,
                $body,
                $inReplyToHeader
            );

            if ($success) {
                \App\Models\Log::info("Email sent from $fromEmail to $to. Subject: $subject");
                $this->json(['success' => true, 'message' => 'Email sent successfully.']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to send email.']);
            }

        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'SMTP Transmission Error: ' . $e->getMessage()], 500);
        }
    }
}
