<?php

namespace App\Services;

use Exception;

/**
 * IMAP Service Layer
 * 
 * Manages connections to the mail server, polls emails, parses headers,
 * detects target aliases, and imports them.
 */
class ImapService
{
    private $stream = null;
    private array $config;

    public function __construct()
    {
        $allConfig = require ROOT_DIR . '/config/config.php';
        $this->config = $allConfig['imap'];
    }

    /**
     * Establish IMAP Connection
     */
    public function connect(): bool
    {
        if ($this->stream !== null) {
            return true;
        }

        $connectionString = $this->getConnectionString();
        $username = $this->config['username'];
        $password = $this->config['password'];

        // Suppress warnings because imap_open throws notice/warnings on connection failures
        $this->stream = @imap_open($connectionString, $username, $password);

        if (!$this->stream) {
            $error = imap_last_error();
            throw new Exception("IMAP Connection Failed: " . ($error ?: 'Unknown error'));
        }

        return true;
    }

    /**
     * Generate the mailbox connection string
     */
    private function getConnectionString(): string
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $folder = $this->config['folder'] ?? 'INBOX';
        
        $flags = ['imap'];

        if (strtolower($this->config['encryption']) === 'ssl') {
            $flags[] = 'ssl';
        } elseif (strtolower($this->config['encryption']) === 'tls') {
            $flags[] = 'tls';
        }

        if (!$this->config['validate_cert']) {
            $flags[] = 'novalidate-cert';
        }

        $flagString = implode('/', $flags);
        return "{" . "$host:$port/$flagString" . "}$folder";
    }

    /**
     * Fetch list of all UIDs from current inbox folder
     */
    public function getMessageUids(): array
    {
        $this->connect();
        
        // Search for ALL messages
        $emails = imap_search($this->stream, 'ALL', SE_UID);
        
        return $emails ? $emails : [];
    }

    /**
     * Fetch and parse a specific email by UID
     */
    public function fetchMessage(int $uid): ?array
    {
        $this->connect();

        // Get headers first
        $headerRaw = imap_fetchheader($this->stream, $uid, FT_UID);
        if (!$headerRaw) {
            return null;
        }

        $headers = $this->parseHeaders($headerRaw);
        
        // Parse email structure for HTML and Plain body parts
        $bodyData = MailParser::parseStructure($this->stream, $uid);

        return [
            'uid' => $uid,
            'headers' => $headers,
            'plain' => $bodyData['plain'],
            'html' => $bodyData['html'],
            'attachments' => $bodyData['attachments']
        ];
    }

    /**
     * Parse raw email header string into key-value pairs
     */
    private function parseHeaders(string $headerRaw): array
    {
        $headers = [];
        // Unfold wrapped header lines
        $headerRaw = preg_replace('/\r?\n\s+/', ' ', $headerRaw);
        $lines = explode("\n", $headerRaw);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $key = strtolower(trim($parts[0]));
                $val = trim($parts[1]);
                
                // Keep values in arrays if there are duplicates (like Received)
                if (isset($headers[$key])) {
                    if (is_array($headers[$key])) {
                        $headers[$key][] = $val;
                    } else {
                        $headers[$key] = [$headers[$key], $val];
                    }
                } else {
                    $headers[$key] = $val;
                }
            }
        }

        return $headers;
    }

    /**
     * Scans headers to find potential recipient email addresses.
     * Looks at To, Cc, Delivered-To, X-Original-To, Envelope-To.
     */
    public function detectRecipients(array $headers): array
    {
        $recipients = [];
        $headerKeys = ['to', 'cc', 'delivered-to', 'x-original-to', 'envelope-to', 'x-delivered-to'];

        foreach ($headerKeys as $key) {
            if (!isset($headers[$key])) {
                continue;
            }

            $values = $headers[$key];
            if (!is_array($values)) {
                $values = [$values];
            }

            foreach ($values as $value) {
                // Find all emails using regex
                if (preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $value, $matches)) {
                    foreach ($matches[0] as $email) {
                        $recipients[] = strtolower(trim($email));
                    }
                }
            }
        }

        return array_unique($recipients);
    }

    /**
     * Mark email as deleted on the server
     */
    public function deleteMessage(int $uid): bool
    {
        $this->connect();
        return imap_delete($this->stream, $uid, FT_UID);
    }

    /**
     * Expunge deleted messages (run after deleting)
     */
    public function expunge(): bool
    {
        $this->connect();
        return imap_expunge($this->stream);
    }

    /**
     * Close connection
     */
    public function disconnect(): void
    {
        if ($this->stream !== null) {
            @imap_close($this->stream);
            $this->stream = null;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
