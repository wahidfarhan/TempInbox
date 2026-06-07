<?php

namespace App\Services;

/**
 * Mail Parser Service
 * 
 * Helper class to parse email headers, decode MIME-encoded values, 
 * extract text/HTML bodies recursively from IMAP structures, and sanitize HTML.
 */
class MailParser
{
    /**
     * Decode MIME encoded headers (e.g., UTF-8 Base64/Quoted-Printable subjects)
     */
    public static function decodeHeader(string $str): string
    {
        if (empty($str)) {
            return '';
        }
        
        // Use PHP's built-in mb_decode_mimeheader or iconv_mime_decode
        if (function_exists('mb_decode_mimeheader')) {
            // mb_decode_mimeheader might require setting internal encoding
            $decoded = mb_decode_mimeheader($str);
            if ($decoded !== $str) {
                return $decoded;
            }
        }
        
        if (function_exists('iconv_mime_decode')) {
            $decoded = iconv_mime_decode($str, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if ($decoded !== false) {
                return $decoded;
            }
        }

        // Fallback custom decoder for common cases
        return imap_utf8($str);
    }

    /**
     * Parses a string like 'John Doe <john@example.com>' into Name and Email parts.
     */
    public static function parseEmailAddress(string $str): array
    {
        $name = '';
        $email = '';
        
        $str = trim($str);
        
        if (preg_match('/^(.*?)\s*<(.*?)>$/', $str, $matches)) {
            $name = trim($matches[1], ' "\'');
            $email = trim($matches[2]);
        } else {
            $email = $str;
        }
        
        $name = self::decodeHeader($name);
        
        // If name is empty, make email the name
        if (empty($name)) {
            $name = explode('@', $email)[0];
        }
        
        return [
            'name' => $name,
            'email' => strtolower($email)
        ];
    }

    /**
     * Recursively traverses IMAP message structure to fetch plain text, HTML, and attachment info.
     */
    public static function parseStructure($imapStream, int $msgUid, $structure = null, string $partNum = ''): array
    {
        if ($structure === null) {
            $structure = imap_fetchstructure($imapStream, $msgUid, FT_UID);
        }

        $data = [
            'plain' => '',
            'html' => '',
            'attachments' => []
        ];

        if (!$structure) {
            return $data;
        }

        // Determine if this is a primary part or a subpart
        $currentPartNum = $partNum === '' ? '1' : $partNum;

        // Check if there are subparts (multipart message)
        if (isset($structure->parts) && count($structure->parts) > 0) {
            foreach ($structure->parts as $index => $subStructure) {
                // Determine part number (e.g. "1.1", "1.2", "2.1")
                $subPartNum = $partNum === '' ? ($index + 1) : $partNum . '.' . ($index + 1);
                $subData = self::parseStructure($imapStream, $msgUid, $subStructure, $subPartNum);
                
                if (!empty($subData['plain'])) {
                    $data['plain'] .= $subData['plain'];
                }
                if (!empty($subData['html'])) {
                    $data['html'] .= $subData['html'];
                }
                if (!empty($subData['attachments'])) {
                    $data['attachments'] = array_merge($data['attachments'], $subData['attachments']);
                }
            }
            return $data;
        }

        // Single part processing
        $type = $structure->type;
        $subtype = strtoupper($structure->subtype ?? '');
        
        // Check for attachment
        $isAttachment = false;
        
        // Check content disposition
        if (isset($structure->disposition)) {
            $disposition = strtoupper($structure->disposition);
            if ($disposition === 'ATTACHMENT' || $disposition === 'INLINE') {
                // Inline text or images can be treated as attachments if they have a filename
                $isAttachment = self::hasFilename($structure);
            }
        } else {
            $isAttachment = self::hasFilename($structure);
        }

        if ($isAttachment) {
            $filename = self::getFilename($structure);
            $filename = self::decodeHeader($filename);
            
            $data['attachments'][] = [
                'name' => $filename ?: 'unnamed_attachment',
                'size' => $structure->bytes ?? 0,
                'mime' => self::getMimeType($structure)
            ];
            return $data;
        }

        // Fetch structure body part
        // Note: For a singlepart email, partNum should be empty for imap_fetchbody
        $fetchPartNum = $partNum === '' ? '1' : $partNum;
        $rawBody = imap_fetchbody($imapStream, $msgUid, $fetchPartNum, FT_UID);
        
        if ($rawBody === false || $rawBody === '') {
            return $data;
        }

        // Decode transfer encoding
        $decodedBody = self::decodeBody($rawBody, $structure->encoding ?? 0);

        // Handle character set encoding conversions to UTF-8
        $charset = 'UTF-8';
        if (isset($structure->parameters)) {
            foreach ($structure->parameters as $param) {
                if (strtoupper($param->attribute) === 'CHARSET') {
                    $charset = strtoupper($param->value);
                    break;
                }
            }
        }
        
        if ($charset !== 'UTF-8' && !empty($decodedBody)) {
            if (function_exists('mb_convert_encoding')) {
                $converted = @mb_convert_encoding($decodedBody, 'UTF-8', $charset);
                if ($converted !== false) {
                    $decodedBody = $converted;
                }
            } elseif (function_exists('iconv')) {
                $converted = @iconv($charset, 'UTF-8//IGNORE', $decodedBody);
                if ($converted !== false) {
                    $decodedBody = $converted;
                }
            }
        }

        // Map text and HTML parts
        if ($type === 0) { // Primary body type TEXT
            if ($subtype === 'PLAIN') {
                $data['plain'] = $decodedBody;
            } elseif ($subtype === 'HTML') {
                $data['html'] = $decodedBody;
            }
        }

        return $data;
    }

    /**
     * Decode message body based on encoding type
     */
    private static function decodeBody(string $body, int $encoding): string
    {
        switch ($encoding) {
            case 3: // BASE64
                return imap_base64($body);
            case 4: // QUOTED-PRINTABLE
                return quoted_printable_decode($body);
            case 0: // 7BIT
            case 1: // 8BIT
            case 2: // BINARY
            default:
                return $body;
        }
    }

    /**
     * Determine mime type from structure code
     */
    private static function getMimeType($structure): string
    {
        $primaryTypes = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];
        $typeId = $structure->type ?? 7;
        $primary = $primaryTypes[$typeId] ?? "UNKNOWN";
        $sub = $structure->subtype ?? "OCTET-STREAM";
        return strtolower($primary . '/' . $sub);
    }

    /**
     * Check if a structure has a filename parameter
     */
    private static function hasFilename($structure): bool
    {
        if (isset($structure->dparameters)) {
            foreach ($structure->dparameters as $param) {
                if (in_array(strtoupper($param->attribute), ['FILENAME', 'NAME'])) {
                    return true;
                }
            }
        }
        if (isset($structure->parameters)) {
            foreach ($structure->parameters as $param) {
                if (in_array(strtoupper($param->attribute), ['NAME', 'FILENAME'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Extract filename from structure parameters
     */
    private static function getFilename($structure): string
    {
        if (isset($structure->dparameters)) {
            foreach ($structure->dparameters as $param) {
                if (in_array(strtoupper($param->attribute), ['FILENAME', 'NAME'])) {
                    return $param->value;
                }
            }
        }
        if (isset($structure->parameters)) {
            foreach ($structure->parameters as $param) {
                if (in_array(strtoupper($param->attribute), ['NAME', 'FILENAME'])) {
                    return $param->value;
                }
            }
        }
        return '';
    }

    /**
     * Clean and sanitize HTML body to prevent XSS.
     * Served content will also be inside a sandboxed iframe.
     */
    public static function sanitizeHtml(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Basic script and dangerous tags removal via regex
        // (Since this is displayed in a sandbox="allow-popups" iframe, this provides dual-layer security)
        $dangerousTags = [
            '/<script[^>]*?>.*?<\/script>/is',
            '/<iframe[^>]*?>.*?<\/iframe>/is',
            '/<object[^>]*?>.*?<\/object>/is',
            '/<embed[^>]*?>.*?<\/embed>/is',
            '/<applet[^>]*?>.*?<\/applet>/is',
            '/<meta[^>]*?>/is',
            '/<link[^>]*?>/is',
            '/<base[^>]*?>/is'
        ];

        $html = preg_replace($dangerousTags, '', $html);

        // Strip inline javascript handlers (onmouseover, onload, onerror, etc.)
        $html = preg_replace('/\s+on[a-z]+\s*=\s*([\'"].*?[\'"]|[^ >]+)/is', '', $html);

        // Strip javascript: links
        $html = preg_replace('/href\s*=\s*[\'"]\s*javascript:[^\'"]*[\'"]/is', 'href="#"', $html);

        // Add target="_blank" to all links
        $html = preg_replace('/<a\s+(.*?)/i', '<a target="_blank" $1', $html);

        return $html;
    }
}
