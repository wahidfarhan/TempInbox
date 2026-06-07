<?php

namespace App\Controllers;

use App\Models\Alias;
use App\Models\Setting;
use App\Services\RateLimiter;
use Exception;

/**
 * Alias Controller
 * 
 * Manages AJAX-based alias actions: generation, custom naming, and deletion.
 */
class AliasController extends BaseController
{
    /**
     * Create an email alias (AJAX endpoint)
     */
    public function create(): void
    {
        $this->verifyCsrf();

        // 1. Check Rate Limit
        if (!RateLimiter::check('create_alias')) {
            $this->json([
                'success' => false,
                'message' => 'Too many requests. Please wait a moment before creating another alias.'
            ], 429);
        }

        $type = $this->input('type', 'random'); // 'random' or 'custom'
        $domain = $this->input('domain');
        $expiryHours = (int)$this->input('expiry', 24);

        // 2. Validate domain
        $allowedDomains = json_decode(Setting::get('allowed_domains', '[]'), true);
        if (empty($allowedDomains)) {
            $config = require ROOT_DIR . '/config/config.php';
            $allowedDomains = $config['app']['allowed_domains'];
        }

        if (!in_array($domain, $allowedDomains)) {
            $this->json([
                'success' => false,
                'message' => 'Selected domain is not allowed.'
            ], 400);
        }

        // 3. Validate expiration hours limit
        $config = require ROOT_DIR . '/config/config.php';
        $maxHours = $config['app']['max_expiration_hours'] ?? 168;
        if ($expiryHours <= 0 || $expiryHours > $maxHours) {
            $expiryHours = $config['app']['default_expiration_hours'] ?? 24;
        }

        try {
            if ($type === 'custom') {
                $customName = $this->input('custom_name');
                if (empty($customName)) {
                    throw new Exception("Custom alias name cannot be empty.");
                }
                
                // Restrict length
                if (strlen($customName) < 3 || strlen($customName) > 30) {
                    throw new Exception("Alias name must be between 3 and 30 characters.");
                }

                $aliasData = Alias::create($customName, $domain, $expiryHours);
            } else {
                // Random alias
                $aliasData = Alias::createRandom($domain, $expiryHours);
            }

            $this->json([
                'success' => true,
                'email' => $aliasData['alias'] . '@' . $aliasData['domain'],
                'token' => $aliasData['token'],
                'expires_at' => $aliasData['expires_at'],
                'redirect_url' => '/inbox?token=' . $aliasData['token']
            ]);

        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete an alias by user token (AJAX endpoint)
     */
    public function delete(): void
    {
        $this->verifyCsrf();
        
        $token = $this->input('token');
        if (empty($token)) {
            $this->json(['success' => false, 'message' => 'Invalid token.'], 400);
        }

        $alias = Alias::findByToken($token);
        if (!$alias) {
            $this->json(['success' => false, 'message' => 'Alias not found.'], 404);
        }

        $success = Alias::delete((int)$alias['id']);
        
        if ($success) {
            $this->json(['success' => true, 'message' => 'Inbox deleted successfully.']);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to delete inbox.'], 500);
        }
    }
}
