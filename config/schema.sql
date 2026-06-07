-- TempInbox SQLite Schema

PRAGMA foreign_keys = ON;

-- 1. Aliases Table
CREATE TABLE IF NOT EXISTS aliases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    alias TEXT UNIQUE NOT NULL,                -- e.g., 'johndoe'
    domain TEXT NOT NULL,                      -- e.g., 'tempinbox.local'
    token TEXT UNIQUE NOT NULL,                -- Unique access token for this inbox
    is_active INTEGER DEFAULT 1,               -- 1 = Active, 0 = Inactive
    created_at TEXT NOT NULL,                  -- ISO8601 string (YYYY-MM-DD HH:MM:SS)
    expires_at TEXT NOT NULL                   -- ISO8601 string (YYYY-MM-DD HH:MM:SS)
);

CREATE INDEX IF NOT EXISTS idx_aliases_token ON aliases(token);
CREATE INDEX IF NOT EXISTS idx_aliases_expires ON aliases(expires_at);

-- 2. Messages Table
CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    alias_id INTEGER NOT NULL,
    message_uid TEXT UNIQUE NOT NULL,          -- IMAP Message UID (to prevent duplicates)
    message_id TEXT,                           -- Message-ID Header (for replies/threading)
    sender_name TEXT,                          -- Sender's Display Name
    sender_email TEXT,                         -- Sender's Email Address
    subject TEXT,                              -- Email Subject
    body_plain TEXT,                           -- Plain Text Email Body
    body_html TEXT,                            -- HTML Email Body
    attachments TEXT,                          -- JSON array of attachment metadata: [{name, size, mime}]
    received_at TEXT,                          -- Email Date header (ISO8601 formatted)
    created_at TEXT NOT NULL,                  -- Time imported into local database
    FOREIGN KEY (alias_id) REFERENCES aliases(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_messages_alias_id ON messages(alias_id);
CREATE INDEX IF NOT EXISTS idx_messages_message_uid ON messages(message_uid);

-- 3. Settings Table
CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT
);

-- 4. Logs Table
CREATE TABLE IF NOT EXISTS logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    level TEXT NOT NULL,                       -- INFO, WARNING, ERROR, DEBUG
    message TEXT NOT NULL,                     -- Log message
    created_at TEXT NOT NULL                   -- Timestamp
);

-- 5. Rate Limits Table
CREATE TABLE IF NOT EXISTS rate_limits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL,
    endpoint TEXT NOT NULL,
    hit_time INTEGER NOT NULL                  -- Unix timestamp
);

CREATE INDEX IF NOT EXISTS idx_rate_limits_ip ON rate_limits(ip_address, endpoint);
CREATE INDEX IF NOT EXISTS idx_rate_limits_time ON rate_limits(hit_time);
