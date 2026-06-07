# Cron Jobs Setup Manual

To automate email fetching and database maintenance, you must register cron jobs on your cPanel server.

---

## Required Cron Jobs (Unified Setup - Recommended)

Instead of setting up multiple cron jobs, you can now use our **Unified Cron Script** (`cron/cron.php`). This combines email fetching and database maintenance into a single execution:
- **Email Fetching**: Executed on every run (e.g. every minute).
- **Database Cleanup**: Executed automatically once per hour (tracked dynamically via SQLite settings).

| Script | Purpose | Recommended Interval |
|---|---|---|
| `cron/cron.php` | Connects to IMAP, pulls new emails, AND executes database cleanup/optimizations hourly. | Every 1 or 2 minutes |

*(Note: The legacy individual scripts `cron/fetch.php` and `cron/cleanup.php` are still available if you prefer independent scheduling).*

---

## Step-by-Step cPanel Configuration

1. Log in to your **cPanel Dashboard**.
2. Search for the **Advanced** section and select **Cron Jobs**.
3. Under **Add New Cron Job**, configure the scheduler:
   - **Common Settings**: Select *Once Per Minute* (`* * * * *`) or *Every 2 Minutes* (`*/2 * * * *`).
   - **Command**: Enter the path to your server PHP CLI binary followed by the absolute path to the unified script:
     ```bash
     /usr/local/bin/ea-php83 /home/username/public_html/cron/cron.php >/dev/null 2>&1
     ```
   - Click **Add New Cron Job**.

---

## Finding Your Server's PHP CLI Binary Path

On shared hosting, the default `php` command in cron jobs might execute an older version of PHP (e.g. PHP 7.4). You must specify the path to the **PHP 8.3** binary.

Common PHP binary paths on cPanel servers:
- **EasyApache 4 (Standard cPanel)**: `/usr/local/bin/ea-php83` or `/usr/local/bin/ea-php84`
- **CloudLinux Alt-PHP**: `/opt/alt/php83/usr/bin/php`
- **Standard Linux binary fallback**: `/usr/bin/php` or `/usr/local/bin/php`

*If you are unsure of the path, check the **Select PHP Version** section of your cPanel, or contact your hosting provider's support team.*

---

## Debugging and Testing

### 1. Enable Cron Email Notifications
Before adding `>/dev/null 2>&1` to your cron commands, enter your personal email address in the **Cron Email** settings at the top of the cPanel page. The cron daemon will mail you any script outputs or error messages so you can verify that the script is executing successfully. Once verified, add the redirect `>/dev/null 2>&1` to suppress empty notifications.

### 2. Manual CLI Testing
If you have SSH access to your hosting account, log in and run the scripts manually to check for errors:
```bash
/usr/local/bin/ea-php83 /home/username/tempinbox/cron/fetch.php
```
You should see output detailing the connection steps, total messages processed, and match results.
