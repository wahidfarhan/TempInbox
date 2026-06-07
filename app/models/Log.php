<?php

namespace App\Models;

use App\Services\Database;

/**
 * Log Model
 * 
 * Records application events and errors in SQLite logs table.
 */
class Log
{
    /**
     * Write a log message to the database
     */
    public static function write(string $level, string $message): void
    {
        try {
            $db = Database::getInstance();
            $createdAt = date('Y-m-d H:i:s');
            
            $db->query(
                "INSERT INTO logs (level, message, created_at) VALUES (?, ?, ?)",
                [strtoupper($level), $message, $createdAt]
            );
        } catch (\Throwable $e) {
            // Fallback: log to PHP error log if DB write fails
            error_log("TempInbox Log Failure: " . $message);
        }
    }

    /**
     * Convenience log methods
     */
    public static function info(string $message): void { self::write('INFO', $message); }
    public static function warning(string $message): void { self::write('WARNING', $message); }
    public static function error(string $message): void { self::write('ERROR', $message); }
    public static function debug(string $message): void { self::write('DEBUG', $message); }

    /**
     * Retrieve latest logs
     */
    public static function getLatest(int $limit = 100): array
    {
        $db = Database::getInstance();
        return $db->fetchAll("SELECT * FROM logs ORDER BY created_at DESC, id DESC LIMIT ?", [$limit]);
    }

    /**
     * Clear all logs
     */
    public static function clear(): bool
    {
        $db = Database::getInstance();
        $db->query("DELETE FROM logs");
        return true;
    }
}
