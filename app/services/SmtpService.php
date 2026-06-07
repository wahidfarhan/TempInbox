<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Log;

/**
 * SMTP Client Service
 * 
 * Implements standard socket-based SMTP protocol to transmit outbound emails
 * directly through external SMTP servers without Composer dependencies.
 */
class SmtpService
{
    private string $host;
    private int $port;
    private string $encryption;
    private string $username;
    private string $password;
    private int $timeout = 15;

    public function __construct()
    {
        // Load default config
        $config = require ROOT_DIR . '/config/config.php';

        // Retrieve from database settings, falling back to config.php or IMAP settings if same
        $this->host = Setting::get('smtp_host', $config['smtp']['host'] ?? $config['imap']['host'] ?? '');
        $this->port = (int)Setting::get('smtp_port', $config['smtp']['port'] ?? 587);
        $this->encryption = Setting::get('smtp_encryption', $config['smtp']['encryption'] ?? 'tls');
        
        $this->username = Setting::get('smtp_username', $config['smtp']['username'] ?? $config['imap']['username'] ?? '');
        $this->password = Setting::get('smtp_password', $config['smtp']['password'] ?? $config['imap']['password'] ?? '');
    }

    /**
     * Send email through socket connection
     * 
     * @throws \Exception on connection or protocol failure
     */
    public function send(
        string $fromEmail,
        string $fromName,
        string $to,
        string $subject,
        string $bodyHtml,
        string $bodyPlain = '',
        string $inReplyTo = ''
    ): bool {
        // Build plain text fallback if empty
        if (empty($bodyPlain)) {
            $bodyPlain = strip_tags($bodyHtml);
        }

        $remote = $this->host;
        if (strtolower($this->encryption) === 'ssl') {
            $remote = 'ssl://' . $this->host;
        }

        // Connect to stream
        $socket = @stream_socket_client(
            $remote . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            Log::error("SMTP Socket connection failed: $errstr ($errno)");
            throw new \Exception("Could not connect to SMTP server: $errstr");
        }

        try {
            // Read initial welcome code (220)
            $this->expect($socket, '220');

            // Send EHLO
            $localHost = $_SERVER['SERVER_NAME'] ?? 'localhost';
            $this->sendCmd($socket, "EHLO " . $localHost, '250');

            // Handle STARTTLS for explicit TLS (port 587)
            if (strtolower($this->encryption) === 'tls') {
                $this->sendCmd($socket, "STARTTLS", '220');
                
                // Enable cryptography secure handshake
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                
                // Support modern TLS protocols in newer PHP versions
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                }

                if (!@stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                    throw new \Exception("STARTTLS cryptographic handshake failed.");
                }

                // Resend EHLO over secure socket
                $this->sendCmd($socket, "EHLO " . $localHost, '250');
            }

            // Authentication login
            if (!empty($this->username)) {
                $this->sendCmd($socket, "AUTH LOGIN", '334');
                $this->sendCmd($socket, base64_encode($this->username), '334');
                $this->sendCmd($socket, base64_encode($this->password), '235');
            }

            // Sender address
            $this->sendCmd($socket, "MAIL FROM:<$fromEmail>", '250');

            // Recipient address
            $this->sendCmd($socket, "RCPT TO:<$to>", '250');

            // DATA trigger
            $this->sendCmd($socket, "DATA", '354');

            // Build Boundaries and unique message-id
            $boundary = 'bnd_' . md5(uniqid(microtime(), true));
            $domain = explode('@', $fromEmail)[1] ?? 'localhost';
            $msgId = '<' . md5(uniqid('', true)) . '@' . $domain . '>';

            $headers = [
                "From: " . $this->encodeHeaderName($fromName) . " <$fromEmail>",
                "Reply-To: <$fromEmail>",
                "To: <$to>",
                "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
                "Date: " . date('r'),
                "Message-ID: $msgId",
                "MIME-Version: 1.0",
                "Content-Type: multipart/alternative; boundary=\"$boundary\"",
                "X-Mailer: PHP/Mailer"
            ];

            // Add thread grouping headers if it is a reply
            if (!empty($inReplyTo)) {
                $headers[] = "In-Reply-To: $inReplyTo";
                $headers[] = "References: $inReplyTo";
            }

            // Compile MIME headers and body payloads
            $emailBody = implode("\r\n", $headers) . "\r\n\r\n";
            
            // Plain text body section
            $emailBody .= "--$boundary\r\n";
            $emailBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $emailBody .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $emailBody .= chunk_split(base64_encode($bodyPlain)) . "\r\n";
            
            // HTML body section
            $emailBody .= "--$boundary\r\n";
            $emailBody .= "Content-Type: text/html; charset=UTF-8\r\n";
            $emailBody .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $emailBody .= chunk_split(base64_encode($bodyHtml)) . "\r\n";
            
            // End MIME boundary and close transmission with dot (.)
            $emailBody .= "--$boundary--\r\n.\r\n";

            // Transmit email packet
            fwrite($socket, $emailBody);
            $this->expect($socket, '250');

            // QUIT SMTP session
            $this->sendCmd($socket, "QUIT", '221');
            
            fclose($socket);
            return true;
        } catch (\Exception $e) {
            @fclose($socket);
            Log::error("SMTP Client error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send command and verify return code
     */
    private function sendCmd($socket, string $cmd, string $expectedCode): void
    {
        fwrite($socket, $cmd . "\r\n");
        $this->expect($socket, $expectedCode);
    }

    /**
     * Listen to socket stream response
     */
    private function expect($socket, string $code): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            // Check for SMTP multi-line format indicator (hyphen on 4th char is multiline, space is final line)
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        
        $serverCode = substr($response, 0, 3);
        if ($serverCode !== $code) {
            throw new \Exception("SMTP Connection Error: Expected $code, got response: " . trim($response));
        }
        return $response;
    }

    /**
     * Encodes non-ASCII characters in header names to avoid email header corruption
     */
    private function encodeHeaderName(string $str): string
    {
        if (preg_match('/[^\x20-\x7e]/', $str)) {
            return "=?UTF-8?B?" . base64_encode($str) . "?=";
        }
        return '"' . addslashes($str) . '"';
    }
}
