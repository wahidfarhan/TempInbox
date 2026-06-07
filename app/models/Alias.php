<?php

namespace App\Models;

use App\Services\Database;
use Exception;

/**
 * Alias Model
 * 
 * Manages database records for temporary email aliases.
 */
class Alias
{
    /**
     * Create a new temporary email alias
     */
    public static function create(string $aliasName, string $domain, int $durationHours): array
    {
        $aliasName = strtolower(trim($aliasName));
        $domain = strtolower(trim($domain));

        // Validate format: alphanumeric, dots, and hyphens only
        if (!preg_match('/^[a-z0-9.-]+$/', $aliasName)) {
            throw new Exception("Alias contains invalid characters. Use letters, numbers, dots, and hyphens.");
        }

        $email = $aliasName . '@' . $domain;
        $db = Database::getInstance();

        // Check if alias already exists and is active
        $existing = $db->fetch("SELECT * FROM aliases WHERE alias = ? AND domain = ?", [$aliasName, $domain]);
        if ($existing) {
            $now = time();
            $expiresAt = strtotime($existing['expires_at']);
            if ($existing['is_active'] && $expiresAt > $now) {
                throw new Exception("This email alias is already active.");
            }
            // If expired or inactive, we delete or update it. For simplicity, we delete and recreate.
            $db->query("DELETE FROM aliases WHERE id = ?", [$existing['id']]);
        }

        // Generate a secure access token
        $token = bin2hex(random_bytes(32));
        
        $createdAt = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + ($durationHours * 3600));

        $sql = "INSERT INTO aliases (alias, domain, token, is_active, created_at, expires_at) 
                VALUES (?, ?, ?, 1, ?, ?)";
        
        $db->query($sql, [$aliasName, $domain, $token, $createdAt, $expiresAt]);
        $id = $db->lastInsertId();

        return [
            'id' => $id,
            'alias' => $aliasName,
            'domain' => $domain,
            'email' => $email,
            'token' => $token,
            'created_at' => $createdAt,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * Generate a unique random alias
     */
    public static function createRandom(string $domain, int $durationHours): array
    {
        $db = Database::getInstance();
        
        // Loop to ensure uniqueness
        do {
            $prefix = 'tmp.' . bin2hex(random_bytes(4)); // e.g. tmp.a3f9c2d1
            $existing = $db->fetch("SELECT id FROM aliases WHERE alias = ? AND domain = ?", [$prefix, $domain]);
        } while ($existing);

        return self::create($prefix, $domain, $durationHours);
    }

    /**
     * Find alias by unique access token
     */
    public static function findByToken(string $token): ?array
    {
        $db = Database::getInstance();
        $alias = $db->fetch("SELECT * FROM aliases WHERE token = ?", [$token]);
        return $alias ?: null;
    }

    /**
     * Find alias by full email address
     */
    public static function findByEmail(string $email): ?array
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return null;
        }

        $db = Database::getInstance();
        $alias = $db->fetch("SELECT * FROM aliases WHERE alias = ? AND domain = ?", [$parts[0], $parts[1]]);
        return $alias ?: null;
    }

    /**
     * Verify if an alias is active and not expired
     */
    public static function isValid(array $alias): bool
    {
        if (!$alias['is_active']) {
            return false;
        }
        
        $expiresAt = strtotime($alias['expires_at']);
        return $expiresAt > time();
    }

    /**
     * Fetch count of active aliases
     */
    public static function getActiveCount(): int
    {
        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');
        $result = $db->fetch("SELECT COUNT(*) as active_count FROM aliases WHERE is_active = 1 AND expires_at > ?", [$now]);
        return $result['active_count'] ?? 0;
    }

    /**
     * Fetch total aliases count
     */
    public static function getTotalCount(): int
    {
        $db = Database::getInstance();
        $result = $db->fetch("SELECT COUNT(*) as total_count FROM aliases");
        return $result['total_count'] ?? 0;
    }

    /**
     * Clean up expired aliases
     */
    public static function deleteExpired(): int
    {
        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');
        // Delete expired aliases. Cascading deletes messages automatically.
        $stmt = $db->query("DELETE FROM aliases WHERE expires_at <= ?", [$now]);
        return $stmt->rowCount();
    }

    /**
     * Fetch all aliases (for admin)
     */
    public static function getAll(int $limit = 50, int $offset = 0): array
    {
        $db = Database::getInstance();
        return $db->fetchAll("SELECT * FROM aliases ORDER BY created_at DESC LIMIT ? OFFSET ?", [$limit, $offset]);
    }

    /**
     * Delete an alias by ID
     */
    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        $stmt = $db->query("DELETE FROM aliases WHERE id = ?", [$id]);
        return $stmt->rowCount() > 0;
    }
}
