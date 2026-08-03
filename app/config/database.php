<?php
require_once __DIR__ . '/config.php';

/**
 * Returns a shared PDO connection to PostgreSQL.
 * Prefers DATABASE_URL (Render's format) and falls back to
 * discrete DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASSWORD.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databaseUrl = env('DATABASE_URL');
    $sslmode = env('DB_SSLMODE', '');

    if ($databaseUrl) {
        $parts = parse_url($databaseUrl);
        $host = $parts['host'] ?? '127.0.0.1';
        $port = $parts['port'] ?? 5432;
        $dbname = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
        $user = $parts['user'] ?? '';
        $pass = $parts['pass'] ?? '';
        if ($sslmode === '' && isset($parts['query'])) {
            parse_str($parts['query'], $q);
            $sslmode = $q['sslmode'] ?? '';
        }
    } else {
        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '5432');
        $dbname = env('DB_NAME', 'ucz_research');
        $user = env('DB_USER', 'postgres');
        $pass = env('DB_PASSWORD', '');
    }

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
    if ($sslmode !== '') {
        $dsn .= ";sslmode={$sslmode}";
    }

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        die(
            '<div style="font-family:sans-serif;max-width:640px;margin:80px auto;padding:24px;border:1px solid #ddd;border-radius:8px">'
            . '<h2 style="margin-top:0">Database connection failed</h2>'
            . '<p>The application could not reach the database. Please check the DATABASE_URL / DB_* environment variables.</p>'
            . (APP_ENV !== 'production' ? '<pre style="white-space:pre-wrap;color:#a00">' . htmlspecialchars($e->getMessage()) . '</pre>' : '')
            . '</div>'
        );
    }

    return $pdo;
}

/**
 * Returns true once the core schema exists (used by install.php
 * and as a safety guard elsewhere).
 */
function is_installed(): bool
{
    try {
        $stmt = db()->query("SELECT to_regclass('public.admin_users') AS t");
        $row = $stmt->fetch();
        return !empty($row['t']);
    } catch (Throwable $e) {
        return false;
    }
}
