<?php
/**
 * TempInbox Configuration File
 *
 * HOW TO CONFIGURE:
 *   1. Copy this file to config.php  (it is already named config.php)
 *   2. Fill in your server-specific values below
 *   3. Never commit config.php with real credentials to a public Git repository!
 *      Add config.php to your .gitignore, or replace credentials before committing.
 */

// Define directory constants
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}
if (!defined('APP_DIR')) {
    define('APP_DIR', ROOT_DIR . '/app');
}
if (!defined('STORAGE_DIR')) {
    define('STORAGE_DIR', ROOT_DIR . '/storage');
}
if (!defined('CONFIG_DIR')) {
    define('CONFIG_DIR', ROOT_DIR . '/config');
}

return [
    // ─── Database ────────────────────────────────────────────────────────────
    'db' => [
        'path' => STORAGE_DIR . '/database.sqlite',
    ],

    // ─── Application ─────────────────────────────────────────────────────────
    'app' => [
        'name'                     => 'TempInbox',
        'url'                      => 'https://yourdomain.com/TempInbox/public', // No trailing slash
        'allowed_domains'          => [
            'yourdomain.com', // Domain used for temporary email aliases
            // 'mail2.yourdomain.com', // Add more domains if needed
        ],
        'default_expiration_hours' => 24,   // Default alias lifetime (hours)
        'max_expiration_hours'     => 168,  // Maximum alias lifetime (1 week)
        'timezone'                 => 'UTC', // e.g. 'Asia/Dhaka', 'America/New_York'
    ],

    // ─── IMAP (Incoming Mail) ─────────────────────────────────────────────────
    // This account must have a catch-all forwarder set up in cPanel.
    // All emails to *@yourdomain.com should arrive here.
    'imap' => [
        'host'          => 'mail.yourdomain.com', // Your mail server hostname
        'port'          => 993,                    // 993 = SSL  |  143 = TLS
        'encryption'    => 'ssl',                  // 'ssl', 'tls', or 'none'
        'validate_cert' => false,                  // Set true if you have a valid SSL cert
        'username'      => 'catchall@yourdomain.com',
        'password'      => 'YOUR-EMAIL-PASSWORD',
        'folder'        => 'INBOX',
        'fetch_limit'   => 50,                     // Max emails per cron fetch run
    ],

    // ─── SMTP (Outgoing Mail) ─────────────────────────────────────────────────
    // Used for sending replies and new emails from aliases.
    // Leave host empty to automatically fall back to IMAP credentials above.
    'smtp' => [
        'host'       => 'mail.yourdomain.com', // ← hostname only, NOT an email address
        'port'       => 587,                   // 587 = TLS/STARTTLS  |  465 = SSL
        'encryption' => 'tls',                 // 'tls' or 'ssl'
        'username'   => 'catchall@yourdomain.com',
        'password'   => 'YOUR-EMAIL-PASSWORD',
    ],

    // ─── Security ─────────────────────────────────────────────────────────────
    'security' => [
        'admin_username'         => 'admin',
        'admin_password_default' => 'AdminTempInbox2026!', // Change immediately on first login!
        'rate_limit' => [
            'enabled'        => true,
            'max_requests'   => 10, // Max alias creations per IP
            'window_seconds' => 60, // Per time window (10 per minute)
        ],
        'session_lifetime' => 86400, // Admin session duration (24 hours)
    ],
];
