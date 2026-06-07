<?php

namespace App\Services;

/**
 * Rate Limiter Service
 * 
 * IP-based rate limiting using SQLite for storage.
 * Helps prevent denial-of-service and alias-creation spam.
 */
class RateLimiter
{
    /**
     * Check if a request from the current client IP should be allowed
     * 
     * @param string $endpoint The endpoint identifier (e.g. 'create_alias')
     * @return bool True if allowed, false if rate limited
     */
    public static function check(string $endpoint): bool
    {
        $config = require ROOT_DIR . '/config/config.php';
        $limitConfig = $config['security']['rate_limit'];

        if (!$limitConfig['enabled']) {
            return true;
        }

        $db = Database::getInstance();
        $ip = self::getClientIp();
        $currentTime = time();
        $windowStart = $currentTime - $limitConfig['window_seconds'];

        // Clean up expired rate limits (older than window start) periodically (1 in 100 chance to garbage collect)
        if (random_int(1, 100) === 1) {
            $db->query("DELETE FROM rate_limits WHERE hit_time < ?", [$windowStart]);
        }

        // Count requests from this IP in the current window
        $sql = "SELECT COUNT(*) as hit_count FROM rate_limits 
                WHERE ip_address = ? AND endpoint = ? AND hit_time >= ?";
        $result = $db->fetch($sql, [$ip, $endpoint, $windowStart]);
        $hitCount = $result['hit_count'] ?? 0;

        if ($hitCount >= $limitConfig['max_requests']) {
            return false;
        }

        // Log the current hit
        $db->query(
            "INSERT INTO rate_limits (ip_address, endpoint, hit_time) VALUES (?, ?, ?)",
            [$ip, $endpoint, $currentTime]
        );

        return true;
    }

    /**
     * Retrieve current client IP address safely
     */
    public static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ipList[0]);
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
