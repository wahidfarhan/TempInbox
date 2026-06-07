<?php

namespace App\Controllers;

use App\Models\Setting;

/**
 * Home Controller
 * 
 * Manages the rendering of the public homepage for creating temp mail.
 */
class HomeController extends BaseController
{
    /**
     * Display the index homepage
     */
    public function index(): void
    {
        // Get allowed domains list
        $domainsJson = Setting::get('allowed_domains');
        $domains = [];
        
        if ($domainsJson) {
            $domains = json_decode($domainsJson, true);
        }

        // Fallback to config if DB settings are empty or invalid
        if (empty($domains)) {
            $config = require ROOT_DIR . '/config/config.php';
            $domains = $config['app']['allowed_domains'];
        }

        // Get default and max expiration config
        $defaultExpiry = Setting::get('default_expiration_hours', '24');

        $this->render('home/index', [
            'title' => 'TempInbox - Free Temporary Email System',
            'domains' => $domains,
            'default_expiry' => $defaultExpiry
        ]);
    }
}
