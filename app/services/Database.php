<?php

namespace App\Services;

use PDO;
use PDOException;

/**
 * Database Service Layer
 * 
 * Handles connections and queries for the SQLite database.
 * Automatically runs migrations on first connect if the database file is missing.
 */
class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    private function __construct()
    {
        $config = require ROOT_DIR . '/config/config.php';
        $dbPath = $config['db']['path'];
        $dbDir = dirname($dbPath);

        // Ensure storage directory exists
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $dbExists = file_exists($dbPath);

        try {
            // Connect using PDO SQLite
            $this->pdo = new PDO("sqlite:" . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->exec("PRAGMA foreign_keys = ON;");

            // Auto-initialize if database is new
            if (!$dbExists || filesize($dbPath) === 0) {
                $this->runMigrations($config);
            } else {
                // Auto-migration: Check if messages table has message_id column
                $columns = $this->fetchAll("PRAGMA table_info(messages)");
                $hasMessageId = false;
                foreach ($columns as $col) {
                    if ($col['name'] === 'message_id') {
                        $hasMessageId = true;
                        break;
                    }
                }
                if (!$hasMessageId) {
                    $this->pdo->exec("ALTER TABLE messages ADD COLUMN message_id TEXT;");
                    $stmtLog = $this->pdo->prepare("INSERT INTO logs (level, message, created_at) VALUES (?, ?, ?)");
                    $stmtLog->execute([
                        'INFO', 
                        'Migration: Added message_id column to messages table.', 
                        date('Y-m-d H:i:s')
                    ]);
                }
            }
        } catch (PDOException $e) {
            // Write directly to a text log file as SQLite connection failed
            $errorLogFile = $dbDir . '/db_error.log';
            file_put_contents($errorLogFile, "[" . date('Y-m-d H:i:s') . "] DB Connection Error: " . $e->getMessage() . "\n", FILE_APPEND);
            die("Database configuration error. Please check your storage folder permissions.");
        }
    }

    /**
     * Singleton Instance
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Get the active PDO connection
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Run migrations from schema.sql
     */
    private function runMigrations(array $config): void
    {
        $schemaFile = ROOT_DIR . '/config/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception("Schema file schema.sql not found in config directory.");
        }

        $sql = file_get_contents($schemaFile);
        
        // Execute the entire SQL script
        $this->pdo->exec($sql);

        // Seed default admin password & settings
        $defaultPassword = password_hash($config['security']['admin_password_default'], PASSWORD_ARGON2ID);
        
        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)");
        $stmt->execute(['admin_username', $config['security']['admin_username']]);
        $stmt->execute(['admin_password', $defaultPassword]);
        
        // Allowed domains (JSON stored)
        $stmt->execute(['allowed_domains', json_encode($config['app']['allowed_domains'])]);

        // Default expiration hours
        $stmt->execute(['default_expiration_hours', (string)$config['app']['default_expiration_hours']]);

        // Log the installation
        $stmtLog = $this->pdo->prepare("INSERT INTO logs (level, message, created_at) VALUES (?, ?, ?)");
        $stmtLog->execute([
            'INFO', 
            'Database successfully initialized and seeded with default settings.', 
            date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Execute a query with parameters
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Helper to fetch a single row
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Helper to fetch all rows
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}
