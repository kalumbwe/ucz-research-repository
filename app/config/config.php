<?php
/**
 * Core configuration bootstrap.
 * Loads .env (local dev) if present — on Render, real environment
 * variables set in the dashboard take precedence and .env is not used.
 */

if (!function_exists('load_env')) {
    function load_env(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim(trim($value), "\"'");
            if (getenv($key) === false) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }
        return $value;
    }
}

load_env(__DIR__ . '/../../.env');

define('APP_ROOT', dirname(__DIR__, 2));
define('APP_NAME', env('APP_NAME', 'UCZ University Research Repository'));
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_URL', rtrim(env('APP_URL', ''), '/'));
define('STORAGE_PATH', rtrim(env('STORAGE_PATH', APP_ROOT . '/storage/uploads'), '/'));
define('MAX_UPLOAD_BYTES', (int) env('MAX_UPLOAD_MB', 25) * 1024 * 1024);
define('INSTALL_KEY', env('INSTALL_KEY', ''));
define('SESSION_NAME', 'ucz_research_admin');

if (!is_dir(STORAGE_PATH)) {
    @mkdir(STORAGE_PATH, 0755, true);
}

if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

date_default_timezone_set('Africa/Lusaka');
