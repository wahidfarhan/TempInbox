<?php

namespace App\Models;

use App\Services\Database;

/**
 * Message Model
 * 
 * Manages database records for emails matching active aliases.
 */
class Message
{
    /**
     * Store a fetched message in the database
     */
    public static function create(
        int $aliasId,
        string $messageUid,
        ?string $messageId,
        ?string $senderName,
        ?string $senderEmail,
        ?string $subject,
        ?string $bodyPlain,
        ?string $bodyHtml,
        array $attachments,
        ?string $receivedAt
    ): bool {
        $db = Database::getInstance();
        $createdAt = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO messages (
                    alias_id, message_uid, message_id, sender_name, sender_email, 
                    subject, body_plain, body_html, attachments, 
                    received_at, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
        $params = [
            $aliasId,
            $messageUid,
            $messageId,
            $senderName,
            $senderEmail,
            $subject,
            $bodyPlain,
            $bodyHtml,
            json_encode($attachments),
            $receivedAt,
            $createdAt
        ];

        $db->query($sql, $params);
        return true;
    }

    /**
     * Check if a message UID has already been imported
     */
    public static function isDuplicate(string $messageUid): bool
    {
        $db = Database::getInstance();
        $result = $db->fetch("SELECT id FROM messages WHERE message_uid = ?", [$messageUid]);
        return $result !== null;
    }

    /**
     * Fetch paginated messages for a given alias
     */
    public static function getByAlias(int $aliasId, int $limit = 20, int $offset = 0): array
    {
        $db = Database::getInstance();
        $sql = "SELECT id, sender_name, sender_email, subject, received_at, created_at, attachments 
                FROM messages 
                WHERE alias_id = ? 
                ORDER BY received_at DESC, id DESC 
                LIMIT ? OFFSET ?";
        
        $messages = $db->fetchAll($sql, [$aliasId, $limit, $offset]);
        
        // Decode attachments metadata
        foreach ($messages as &$msg) {
            $msg['attachments'] = json_decode($msg['attachments'] ?? '[]', true);
        }
        
        return $messages;
    }

    /**
     * Get total messages count for an alias
     */
    public static function getCountByAlias(int $aliasId): int
    {
        $db = Database::getInstance();
        $result = $db->fetch("SELECT COUNT(*) as msg_count FROM messages WHERE alias_id = ?", [$aliasId]);
        return $result['msg_count'] ?? 0;
    }

    /**
     * Retrieve a specific message details
     */
    public static function findByIdAndAlias(int $id, int $aliasId): ?array
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM messages WHERE id = ? AND alias_id = ?";
        $message = $db->fetch($sql, [$id, $aliasId]);
        
        if ($message) {
            $message['attachments'] = json_decode($message['attachments'] ?? '[]', true);
        }
        
        return $message ?: null;
    }

    /**
     * Fetch total messages count in the system
     */
    public static function getTotalCount(): int
    {
        $db = Database::getInstance();
        $result = $db->fetch("SELECT COUNT(*) as total_count FROM messages");
        return $result['total_count'] ?? 0;
    }

    /**
     * Get storage statistics
     */
    public static function getStorageStats(): array
    {
        $db = Database::getInstance();
        
        // Sum total lengths of body_plain and body_html as an indicator
        $result = $db->fetch("SELECT SUM(LENGTH(body_plain) + LENGTH(body_html)) as text_bytes FROM messages");
        
        $dbPath = (require ROOT_DIR . '/config/config.php')['db']['path'];
        $dbSize = file_exists($dbPath) ? filesize($dbPath) : 0;

        return [
            'database_size' => $dbSize,
            'message_data_size' => $result['text_bytes'] ?? 0
        ];
    }

    /**
     * Delete messages older than X days
     */
    public static function deleteOld(int $days): int
    {
        $db = Database::getInstance();
        $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));
        $stmt = $db->query("DELETE FROM messages WHERE received_at < ? OR created_at < ?", [$cutoff, $cutoff]);
        return $stmt->rowCount();
    }
}
