<?php
/**
 * TempInbox Front Controller & Router
 */

// Check PHP Version
if (PHP_VERSION_ID < 80300) {
    die("TempInbox requires PHP 8.3 or higher. Current version: " . PHP_VERSION);
}

// Check required PHP extensions
$requiredExtensions = ['imap', 'pdo_sqlite', 'openssl', 'mbstring'];
$missingExtensions = [];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}
if (!empty($missingExtensions)) {
    die("Missing required PHP extensions: " . implode(', ', $missingExtensions) . 
        "\nPlease install them and restart your web server.");
}

// Define execution constants
define('PUBLIC_DIR', __DIR__);

// Load config
$config = require dirname(__DIR__) . '/config/config.php';

// Set timezone
date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

// Class Autoloader (PSR-4 Compliant, Case-insensitive folder matching for Linux)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    
    // Split namespace parts
    $parts = explode('\\', $relative_class);
    $className = array_pop($parts); // Keep class name as-is (e.g. HomeController)
    
    // Lowercase all directories (e.g. Controllers -> controllers)
    $folders = array_map('strtolower', $parts);
    $subPath = !empty($folders) ? implode('/', $folders) . '/' : '';
    
    $file = $base_dir . $subPath . $className . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Simple Router implementation
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Parse query string out of Request URI
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'] ?? '/';

// Determine the base path of the application relative to server root
// E.g. if application is located at http://localhost/TempInbox/public/index.php,
// the path might be /TempInbox/public/inbox. We want to strip the subfolder path.
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = dirname($scriptName);

// Clean up paths for comparison
$basePath = rtrim(str_replace('\\', '/', $basePath), '/');
$path = '/' . ltrim(substr($path, strlen($basePath)), '/');

// Normalize direct index.php hits to root "/"
if ($path === '/index.php') {
    $path = '/';
}

// Router Mapping Table
$routes = [
    'GET' => [
        '/' => 'App\\Controllers\\HomeController@index',
        '/inbox' => 'App\\Controllers\\InboxController@index',
        '/inbox/message' => 'App\\Controllers\\InboxController@message',
        '/inbox/html-body' => 'App\\Controllers\\InboxController@htmlBody',
        '/admin/login' => 'App\\Controllers\\AdminController@login',
        '/admin/logout' => 'App\\Controllers\\AdminController@logout',
        '/admin' => 'App\\Controllers\\AdminController@dashboard',
    ],
    'POST' => [
        '/alias/create' => 'App\\Controllers\\AliasController@create',
        '/alias/delete' => 'App\\Controllers\\AliasController@delete',
        '/inbox/refresh' => 'App\\Controllers\\InboxController@refresh',
        '/inbox/send' => 'App\\Controllers\\InboxController@send',
        '/admin/login' => 'App\\Controllers\\AdminController@login',
        '/admin/settings' => 'App\\Controllers\\AdminController@saveSettings',
        '/admin/cleanup' => 'App\\Controllers\\AdminController@cleanup',
        '/admin/alias/delete' => 'App\\Controllers\\AdminController@deleteAlias',
    ]
];

// Check if route exists
$handler = $routes[$requestMethod][$path] ?? null;

// Support basic routing fallback or query parameter routing for cPanel servers that do not support URL rewriting
if ($handler === null && isset($_GET['route'])) {
    $queryRoute = '/' . ltrim($_GET['route'], '/');
    $handler = $routes[$requestMethod][$queryRoute] ?? null;
}

if ($handler === null) {
    // Route not found
    header("HTTP/1.0 404 Not Found");
    $title = "404 Page Not Found";
    
    // Quick inline 404 styling (respects server's cdnjs-only CSP)
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Page Not Found</title>
        <link href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css' rel='stylesheet'>
        <style>
            body { background: #0f0c1b; color: #fff; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
            .card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; padding: 40px; text-align: center; }
            h1 { color: #ff3366; font-weight: 800; font-size: 3rem; margin-bottom: 20px; }
            p { color: #a0aec0; }
            .btn-home { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 10px 24px; border-radius: 8px; color: #white; text-decoration: none; display: inline-block; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='card'>
            <h1>404</h1>
            <p>The page you are looking for does not exist or has been moved.</p>
            <a href='index.php' class='btn-home btn btn-primary text-white'>Go back home</a>
        </div>
    </body>
    </html>";
    exit;
}

// Dispatch request to controller
list($controllerClass, $method) = explode('@', $handler);

try {
    if (!class_exists($controllerClass)) {
        throw new Exception("Controller class $controllerClass does not exist.");
    }
    
    $controller = new $controllerClass();
    
    if (!method_exists($controller, $method)) {
        throw new Exception("Method $method in controller $controllerClass does not exist.");
    }
    
    $controller->$method();
} catch (Exception $e) {
    header("HTTP/1.0 500 Internal Server Error");
    echo "<h1>500 Internal Server Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
