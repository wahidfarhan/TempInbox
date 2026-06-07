<?php

namespace App\Controllers;

use App\Models\Alias;
use App\Models\Message;
use App\Models\Setting;
use App\Models\Log;
use App\Services\Database;

/**
 * Admin Controller
 * 
 * Manages admin authentication, system settings modification, 
 * usage statistics, and manual database cleanups.
 */
class AdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Verify if admin session is active, else redirect
     */
    private function checkAuth(): void
    {
        if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            $this->redirect('/admin/login');
        }
    }

    /**
     * Admin login view & POST processing
     */
    public function login(): void
    {
        if (!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            $this->redirect('/admin');
        }

        $error = null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            
            $username = $this->input('username');
            $password = $this->input('password');

            $dbUsername = Setting::get('admin_username', 'admin');
            $dbPasswordHash = Setting::get('admin_password');

            if ($username === $dbUsername && password_verify($password, $dbPasswordHash)) {
                // Login successful
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $username;
                $_SESSION['login_time'] = time();
                
                Log::info("Admin user logged in from IP " . \App\Services\RateLimiter::getClientIp());
                $this->redirect('/admin');
            } else {
                $error = "Invalid username or password.";
                Log::warning("Failed admin login attempt from IP " . \App\Services\RateLimiter::getClientIp());
            }
        }

        $this->render('admin/login', [
            'title' => 'Admin Login - TempInbox',
            'error' => $error,
            'csrf_field' => $this->csrfInput()
        ], 'main');
    }

    /**
     * Logout admin
     */
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        
        $this->redirect('/admin/login');
    }

    /**
     * Admin dashboard index
     */
    public function dashboard(): void
    {
        $this->checkAuth();

        // Stats calculation
        $totalAliases = Alias::getTotalCount();
        $activeAliases = Alias::getActiveCount();
        $totalMessages = Message::getTotalCount();
        
        $storage = Message::getStorageStats();
        
        // Settings details
        $domains = json_decode(Setting::get('allowed_domains', '[]'), true);
        $domainsText = implode(', ', $domains);
        $defaultExpiry = Setting::get('default_expiration_hours', '24');
        $emailRetentionDays = Setting::get('email_retention_days', '7');
        
        $smtpHost = Setting::get('smtp_host', '');
        $smtpPort = Setting::get('smtp_port', '587');
        $smtpEncryption = Setting::get('smtp_encryption', 'tls');
        $smtpUsername = Setting::get('smtp_username', '');
        $smtpPassword = Setting::get('smtp_password', '');

        // Alias Listing
        $page = (int)$this->input('page', 1);
        if ($page < 1) $page = 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $aliases = Alias::getAll($limit, $offset);
        $totalPages = ceil($totalAliases / $limit);
        if ($totalPages < 1) $totalPages = 1;

        // System logs
        $logs = Log::getLatest(25);

        // Fetch session flash messages
        $success = $_SESSION['flash_success'] ?? null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $this->render('admin/dashboard', [
            'title' => 'Admin Dashboard - TempInbox',
            'total_aliases' => $totalAliases,
            'active_aliases' => $activeAliases,
            'total_messages' => $totalMessages,
            'db_size' => $storage['database_size'],
            'domains_text' => $domainsText,
            'default_expiry' => $defaultExpiry,
            'email_retention' => $emailRetentionDays,
            'smtp_host' => $smtpHost,
            'smtp_port' => $smtpPort,
            'smtp_encryption' => $smtpEncryption,
            'smtp_username' => $smtpUsername,
            'smtp_password' => $smtpPassword,
            'aliases' => $aliases,
            'logs' => $logs,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'success' => $success,
            'error' => $error,
            'csrf_field' => $this->csrfInput()
        ], 'main');
    }

    /**
     * Save application settings
     */
    public function saveSettings(): void
    {
        $this->checkAuth();
        $this->verifyCsrf();

        // Get inputs
        $domainsText = $this->input('domains');
        $defaultExpiry = (int)$this->input('default_expiry', 24);
        $emailRetention = (int)$this->input('email_retention', 7);
        
        $smtpHost = trim($this->input('smtp_host', ''));
        $smtpPort = (int)$this->input('smtp_port', 587);
        $smtpEncryption = trim($this->input('smtp_encryption', 'tls'));
        $smtpUsername = trim($this->input('smtp_username', ''));
        $smtpPassword = $this->input('smtp_password', '');
        
        $adminUsername = trim($this->input('admin_username', 'admin'));
        $newPassword = $this->input('new_password');
        $confirmPassword = $this->input('confirm_password');

        // Parse domains
        $domains = array_unique(array_filter(array_map('trim', explode(',', $domainsText))));
        
        if (empty($domains)) {
            $_SESSION['flash_error'] = "You must provide at least one allowed domain.";
            $this->redirect('/admin');
        }

        if ($defaultExpiry <= 0) {
            $_SESSION['flash_error'] = "Default expiration hours must be positive.";
            $this->redirect('/admin');
        }

        // Save base settings
        Setting::set('allowed_domains', json_encode(array_values($domains)));
        Setting::set('default_expiration_hours', (string)$defaultExpiry);
        Setting::set('email_retention_days', (string)$emailRetention);
        
        // Save SMTP settings
        Setting::set('smtp_host', $smtpHost);
        Setting::set('smtp_port', (string)$smtpPort);
        Setting::set('smtp_encryption', $smtpEncryption);
        Setting::set('smtp_username', $smtpUsername);
        if (!empty($smtpPassword) || empty($smtpHost)) {
            Setting::set('smtp_password', $smtpPassword);
        }
        
        if (!empty($adminUsername)) {
            Setting::set('admin_username', $adminUsername);
            $_SESSION['admin_user'] = $adminUsername;
        }

        // Change password if filled
        if (!empty($newPassword)) {
            if ($newPassword !== $confirmPassword) {
                $_SESSION['flash_error'] = "Passwords do not match.";
                $this->redirect('/admin');
            }
            if (strlen($newPassword) < 8) {
                $_SESSION['flash_error'] = "New password must be at least 8 characters.";
                $this->redirect('/admin');
            }
            
            $newHash = password_hash($newPassword, PASSWORD_ARGON2ID);
            Setting::set('admin_password', $newHash);
            Log::info("Admin password updated successfully.");
        }

        Log::info("System settings updated by administrator.");
        $_SESSION['flash_success'] = "Settings saved successfully.";
        $this->redirect('/admin');
    }

    /**
     * Manually trigger cleanup operations
     */
    public function cleanup(): void
    {
        $this->checkAuth();
        $this->verifyCsrf();

        $action = $this->input('action');
        
        if ($action === 'expired_aliases') {
            $deleted = Alias::deleteExpired();
            Log::info("Manual cleanup: Deleted $deleted expired aliases.");
            $_SESSION['flash_success'] = "Successfully removed $deleted expired aliases and associated messages.";
        } elseif ($action === 'old_emails') {
            $days = (int)Setting::get('email_retention_days', '7');
            $deleted = Message::deleteOld($days);
            Log::info("Manual cleanup: Deleted $deleted emails older than $days days.");
            $_SESSION['flash_success'] = "Successfully removed $deleted emails older than $days days.";
        } elseif ($action === 'clear_logs') {
            Log::clear();
            Log::info("Manual cleanup: System logs cleared.");
            $_SESSION['flash_success'] = "System logs cleared successfully.";
        } else {
            $_SESSION['flash_error'] = "Invalid cleanup action.";
        }

        $this->redirect('/admin');
    }

    /**
     * Manually delete an alias from the dashboard table
     */
    public function deleteAlias(): void
    {
        $this->checkAuth();
        $this->verifyCsrf();

        $aliasId = (int)$this->input('alias_id');
        if ($aliasId <= 0) {
            $_SESSION['flash_error'] = "Invalid alias ID.";
            $this->redirect('/admin');
        }

        $success = Alias::delete($aliasId);
        
        if ($success) {
            Log::info("Administrator deleted alias ID $aliasId.");
            $_SESSION['flash_success'] = "Alias successfully deleted.";
        } else {
            $_SESSION['flash_error'] = "Failed to delete alias. It may have already been removed.";
        }

        $this->redirect('/admin');
    }
}
