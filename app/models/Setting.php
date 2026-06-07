<?php

namespace App\Models;

use App\Services\Database;

/**
 * Setting Model
 * 
 * Manages key-value settings stored in the database.
 */
class Setting
{
    /**
     * Retrieve a setting by its key
     */
    public static function get(string $key, $default = null): ?string
    {
        $db = Database::getInstance();
        $result = $db->fetch("SELECT value FROM settings WHERE key = ?", [$key]);
        return $result ? $result['value'] : $default;
    }

    /**
     * Save/Update a setting
     */
    public static function set(string $key, string $value): bool
    {
        $db = Database::getInstance();
        
        // Use SQLite INSERT OR REPLACE
        $sql = "INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)";
        $db->query($sql, [$key, $value]);
        
        return true;
    }

    /**
     * Get all settings as an associative array
     */
    public static function getAll(): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT * FROM settings");
        
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }
        
        return $settings;
    }
}
