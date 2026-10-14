<?php
/**
 * Application Core Configuration
 * Perpustakaan Digital "Layar Literasi"
 */

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting (can be turned off in production)
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Secure Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// Auto-detect Base URL
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Determine subdirectory if running in subfolder
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dirName = dirname($scriptName);
    
    // Normalize root folder
    if ($dirName === '/' || $dirName === '\\') {
        $subDir = '';
    } else {
        // If we are in subdirectories like /admin or /member or /api, strip them to get app root
        $parts = explode('/', trim($dirName, '/'));
        $filtered = [];
        foreach ($parts as $p) {
            if (in_array($p, ['admin', 'member', 'api', 'includes', 'config', 'assets', 'uploads'])) {
                break;
            }
            $filtered[] = $p;
        }
        $subDir = empty($filtered) ? '' : '/' . implode('/', $filtered);
    }
    
    define('BASE_URL', rtrim($protocol . $host . $subDir, '/'));
}

// App Constants
define('APP_NAME', 'Layar Literasi');
define('APP_TAGLINE', 'Perpustakaan Digital Modern Indonesia');
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');

// Load Database
require_once __DIR__ . '/database.php';
// Load CSRF helper
require_once __DIR__ . '/csrf.php';
// Load General helpers
require_once ROOT_PATH . '/includes/helpers.php';
// Load Auth Middleware
require_once ROOT_PATH . '/includes/auth_middleware.php';
