<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| EkineraGo Core Config
|--------------------------------------------------------------------------
| Bu dosya projenin temel ayarlarını, path/url sabitlerini ve session
| başlangıcını yönetir.
*/

date_default_timezone_set('Europe/Istanbul');

$env = getenv('APP_ENV') ?: 'local';
defined('APP_ENV') || define('APP_ENV', $env);

defined('APP_DEBUG') || define('APP_DEBUG', APP_ENV === 'local');

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

defined('APP_NAME') || define('APP_NAME', 'EkineraGo');

defined('ROOT_PATH') || define('ROOT_PATH', dirname(__DIR__));
defined('APP_PATH') || define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'app');
defined('PUBLIC_PATH') || define('PUBLIC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'public');
defined('STORAGE_PATH') || define('STORAGE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'storage');
defined('UPLOAD_PATH') || define('UPLOAD_PATH', PUBLIC_PATH . DIRECTORY_SEPARATOR . 'uploads');

$protocol = (
    !empty($_SERVER['HTTPS']) &&
    $_SERVER['HTTPS'] !== 'off'
) ? 'https' : 'http';

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$publicPosition = strpos($scriptName, '/public');

if ($publicPosition !== false) {
    $basePath = substr($scriptName, 0, $publicPosition + strlen('/public'));
} else {
    // XAMPP varsayılan kullanım:
    // http://localhost/EkineraGo/public
    $basePath = '/EkineraGo/public';
}

$basePath = rtrim($basePath, '/');

defined('BASE_URL') || define('BASE_URL', $basePath);
defined('APP_URL') || define('APP_URL', $protocol . '://' . $host . BASE_URL);
defined('UPLOAD_URL') || define('UPLOAD_URL', BASE_URL . '/uploads');

/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = $protocol === 'https';

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    session_name('EKINERAGO_SESSION');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/*
|--------------------------------------------------------------------------
| Tiny URL Helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return BASE_URL . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path = ''): string
    {
        return BASE_URL . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('upload_url')) {
    function upload_url(string $path = ''): string
    {
        return UPLOAD_URL . '/' . ltrim($path, '/');
    }
}

require_once __DIR__ . '/constants.php';