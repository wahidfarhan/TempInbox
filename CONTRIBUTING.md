# Contributing to TempInbox

Thank you for your interest in contributing to TempInbox! 🎉

We welcome all kinds of contributions — bug reports, feature suggestions, documentation improvements, and pull requests.

---

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Reporting Bugs](#reporting-bugs)
- [Suggesting Features](#suggesting-features)
- [Development Setup](#development-setup)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)

---

## Code of Conduct

Be kind, constructive, and respectful to all contributors. Harassment or discrimination of any kind will not be tolerated.

---

## Reporting Bugs

Before creating a bug report, please check that the issue hasn't already been reported.

When filing a bug report, please include:

- **PHP version** (`php -v`)
- **Web server** (Apache, Nginx, XAMPP)
- **Hosting environment** (shared hosting, VPS, local)
- **Steps to reproduce** the issue
- **Expected behavior** vs **actual behavior**
- **Error messages** (browser console + PHP error log if available)

---

## Suggesting Features

Open a GitHub Issue with the label `enhancement`. Describe:

- What problem the feature solves
- How you envision it working
- Any alternative approaches you considered

---

## Development Setup

### Requirements

- PHP 8.3+
- Apache with `mod_rewrite` enabled (or XAMPP)
- PHP extensions: `imap`, `pdo_sqlite`, `openssl`, `mbstring`

### Local Setup

```bash
# 1. Clone the repository
git clone https://github.com/yourusername/TempInbox.git
cd TempInbox

# 2. Copy and configure
cp config/config.php config/config.php
# Edit config/config.php with your local IMAP/SMTP details

# 3. Point your web server to the /public directory
# For XAMPP: Place in C:/xampp/htdocs/TempInbox/
# Access at: http://localhost/TempInbox/public/

# 4. Run diagnostics
php tests/test_imap.php
php tests/test_smtp.php
```

---

## Pull Request Process

1. **Fork** the repository and create a feature branch:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes** — keep commits focused and descriptive:
   ```bash
   git commit -m "feat: add support for X"
   git commit -m "fix: resolve Y when Z happens"
   ```

3. **Check for sensitive data** before pushing:
   - No real passwords or API keys in any file
   - No personal email addresses
   - `config/config.php` should only have placeholder values

4. **Push** your branch and open a Pull Request:
   ```bash
   git push origin feature/your-feature-name
   ```

5. In your PR description, explain:
   - What the change does
   - Why it is needed
   - How to test it

---

## Coding Standards

- Follow **PSR-4 naming conventions** for classes and files
- Use **PHP 8.3+ syntax** (readonly properties, match expressions, named arguments where appropriate)
- Keep controllers thin — business logic belongs in Services or Models
- Use **PDO prepared statements** for all database queries (no raw string interpolation)
- Add comments to non-obvious logic
- Views should contain presentation logic only — no database calls directly in view files

---

## Security Vulnerabilities

If you discover a security vulnerability, please **do NOT open a public issue**.
Instead, email the maintainer directly or create a private security advisory on GitHub.

---

Thank you for helping make TempInbox better! 🙏
