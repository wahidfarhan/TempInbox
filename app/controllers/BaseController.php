<?php

namespace App\Controllers;

/**
 * Base Controller
 * 
 * Provides layout rendering, security utilities (CSRF, headers), 
 * and session state helpers.
 */
abstract class BaseController
{
    protected array $viewData = [];

    public function __construct()
    {
        // Start session with security configuration
        if (session_status() === PHP_SESSION_NONE) {
            // Configure session security
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                        (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
            
            $sessionConfig = [
                'lifetime' => 86400, // 24 hours
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,        // Only send over HTTPS
                'httponly' => true,           // Prevent JavaScript access
                'samesite' => 'Strict'        // CSRF protection
            ];
            session_set_cookie_params($sessionConfig);
            session_start();
            
            // Regenerate session ID periodically (every 1 hour)
            if (empty($_SESSION['created_at'])) {
                $_SESSION['created_at'] = time();
            } elseif (time() - $_SESSION['created_at'] > 3600) {
                session_regenerate_id(true);
                $_SESSION['created_at'] = time();
            }
        }

        // Initialize CSRF Token
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Apply security headers
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: same-origin");
        header("X-Frame-Options: SAMEORIGIN");
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");

        // Resolve dynamic base URL to ensure zero configuration works out-of-the-box
        $config = require ROOT_DIR . '/config/config.php';
        $baseUrl = $config['app']['url'];
        if ((str_contains($baseUrl, 'localhost') || str_contains($baseUrl, '127.0.0.1')) && isset($_SERVER['HTTP_HOST'])) {
            $protocol = "http";
            if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) {
                $protocol = "https";
            }
            
            // Mathematical relative path detection relative to server public document root
            $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
            $publicDir = rtrim(str_replace('\\', '/', ROOT_DIR . '/public'), '/');
            
            $relativePath = '';
            if (!empty($docRoot) && str_starts_with($publicDir, $docRoot)) {
                $relativePath = substr($publicDir, strlen($docRoot));
            } else {
                // Fallback to request URI parsing if document root doesn't match
                $requestUri = $_SERVER['REQUEST_URI'] ?? '';
                $pos = strpos($requestUri, '/public');
                if ($pos !== false) {
                    $relativePath = substr($requestUri, 0, $pos + 7);
                } else {
                    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
                    $relativePath = dirname($scriptName);
                }
            }
            
            $relativePath = rtrim(str_replace('\\', '/', $relativePath), '/');
            $baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . $relativePath;
        }
        $this->viewData['baseUrl'] = $baseUrl;
    }

    /**
     * Render a view within a layout
     */
    protected function render(string $viewPath, array $data = [], string $layout = 'main'): void
    {
        // Merge data passed directly with class-level viewData
        $data = array_merge($this->viewData, $data);
        
        // Extract variables for the view files
        extract($data);

        // Capture view content
        $viewFile = APP_DIR . "/views/$viewPath.php";
        if (!file_exists($viewFile)) {
            die("View file not found: $viewPath");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Render layout
        $layoutFile = APP_DIR . "/views/layouts/$layout.php";
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content; // Output raw content if layout doesn't exist
        }
    }

    /**
     * Send JSON response
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * Verify CSRF token
     */
    protected function verifyCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
            $this->json(['error' => 'CSRF verification failed.'], 403);
        }
    }

    /**
     * Get CSRF input field HTML
     */
    protected function csrfInput(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
    }

    /**
     * Get input values safely
     */
    protected function input(string $key, $default = null)
    {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
        return $default;
    }

    /**
     * Redirect helper
     */
    protected function redirect(string $path): void
    {
        header("Location: " . $this->viewData['baseUrl'] . $path);
        exit;
    }
}
