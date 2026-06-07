# Installation & Deployment Guide

This document details the step-by-step procedure to deploy **TempInbox** on a shared hosting server (cPanel) or local environment (XAMPP/LAMP).

---

## Step 1: Mailbox & Catch-All Configuration

To capture incoming emails sent to temporary aliases (e.g. `anything@yourdomain.com`), you must configure a wildcard/catch-all email forwarder.

### On cPanel Shared Hosting:
1. Log in to your cPanel dashboard.
2. Search for the **Email** section and select **Default Address**.
3. Under *Send all unrouted email for the following domain*, select your target domain.
4. Select **Forward to Email Address** and enter the full email address of your dedicated inbox (e.g. `catchall@yourdomain.com`).
5. Click **Change**.

*Now, any email sent to a non-existent user on your domain will automatically forward to `catchall@yourdomain.com` where TempInbox will poll and parse it.*

---

## Step 2: Upload Files to Server

You can deploy TempInbox in two layouts depending on your server setup permissions.

### Option A: Secure Layout (Recommended)
This layout isolates application code from public browser access.
1. Upload the entire project directory (except the `public/` folder) to a folder *outside* your web root (e.g. `/home/username/tempinbox_core/`).
2. Upload the contents of the `public/` folder directly to your domain web root (e.g. `/home/username/public_html/` or `/home/username/public_html/tempmail/`).
3. Open the public `index.php` file on your server and modify the core directory pointers if you renamed the core folder. (By default, it assumes the core files are in the parent directory).

### Option B: Simple Layout (Supported)
Ideal for standard hosting environments where you can only upload files inside `public_html`.
1. Upload the entire project directory directly into your public folder (e.g. `/home/username/public_html/` or a subfolder like `/home/username/public_html/tempinbox/`).
2. TempInbox includes a pre-configured `.htaccess` file inside the root and `/public` folder that automatically blocks browser access to SQLite databases, logs, app, and config subfolders.

---

## Step 3: Configure Settings

Open the file `config/config.php` on your server and customize the following settings:

```php
return [
    // Database configuration (Defaults to SQLite in storage folder)
    'db' => [
        'path' => STORAGE_DIR . '/database.sqlite',
    ],

    // App general settings
    'app' => [
        'name' => 'TempInbox',
        'url' => 'https://yourdomain.com', // Base URL of your app
        'allowed_domains' => [
            'yourdomain.com', // Set your primary temporary mail domain
        ],
        'default_expiration_hours' => 24,
        'timezone' => 'UTC',
    ],

    // IMAP Connection settings (Matches your catch-all inbox)
    'imap' => [
        'host' => 'mail.yourdomain.com', // IMAP server host
        'port' => 993,                    // 993 is standard secure IMAP port
        'encryption' => 'ssl',            // 'ssl', 'tls', or 'none'
        'validate_cert' => false,         // Set to false if using self-signed certificates
        'username' => 'catchall@yourdomain.com',
        'password' => 'your-email-password',
        'folder' => 'INBOX',
        'fetch_limit' => 50,
    ],
    // ...
];
```

---

## Step 4: First-Time Initialization

1. Point your browser to the URL where you uploaded the files (e.g. `https://yourdomain.com` or `https://yourdomain.com/public/`).
2. The application will detect that the SQLite database file does not exist, automatically generate it inside `/storage/`, and execute the `schema.sql` database structures!
3. **No manual table import or database setup is required.**

---

## Step 5: Admin Panel & Defaults

1. Navigate to the Admin area by clicking **Admin Area** or visiting `https://yourdomain.com/admin` (or `https://yourdomain.com/index.php?route=admin`).
2. Log in using the default setup credentials:
   - **Username**: `admin`
   - **Password**: `AdminTempInbox2026!`
3. **Important**: Go to the **Settings** tab immediately, change your administrator username/password, and click **Save Configurations**!

---

## Step 6: Verify Configuration

You can run diagnostics from the command line to ensure that both the SQLite database and IMAP configurations are working.

Run the following command via SSH inside the project root directory:
```bash
php tests/test_imap.php
```

If the diagnostics tool reports `[SUCCESS]` on both database and IMAP steps, your installation is fully functional!
You are now ready to set up automation using the [Cron Setup Manual (CRON_SETUP.md)](file:///c:/xampp/htdocs/TempInbox/CRON_SETUP.md).
