<?php
require_once __DIR__ . '/config.php';

/**
 * Builds the connection settings from the environment.
 * Prefers DATABASE_URL (Render's format) and falls back to
 * discrete DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASSWORD.
 *
 * @return array{dsn:string,user:string,pass:string,host:string,port:string,dbname:string,sslmode:string,source:string}
 */
function db_settings(): array
{
    $databaseUrl = env('DATABASE_URL');
    $sslmode = env('DB_SSLMODE', '');
    $source = 'DB_* variables';

    if ($databaseUrl) {
        $source = 'DATABASE_URL';
        $parts = parse_url($databaseUrl);
        if ($parts === false) {
            $parts = [];
        }
        $host = $parts['host'] ?? '127.0.0.1';
        $port = $parts['port'] ?? 5432;
        $dbname = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
        // parse_url leaves credentials percent-encoded; passwords from
        // managed providers regularly contain characters that get encoded.
        $user = isset($parts['user']) ? rawurldecode($parts['user']) : '';
        $pass = isset($parts['pass']) ? rawurldecode($parts['pass']) : '';
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
    // Fail fast instead of letting a request hang on an unreachable host.
    $dsn .= ';connect_timeout=10';

    return [
        'dsn' => $dsn,
        'user' => (string) $user,
        'pass' => (string) $pass,
        'host' => (string) $host,
        'port' => (string) $port,
        'dbname' => (string) $dbname,
        'sslmode' => (string) $sslmode,
        'source' => $source,
    ];
}

/**
 * Returns a shared PDO connection to PostgreSQL.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = db_settings();

    try {
        $pdo = new PDO($cfg['dsn'], $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        // Always record the real reason in the server log (Render streams
        // stderr into the service logs) — the browser only sees it outside
        // production, so the message never leaks to visitors.
        error_log(sprintf(
            '[db] connection failed (config from %s, host=%s port=%s dbname=%s user=%s sslmode=%s): %s',
            $cfg['source'],
            $cfg['host'] !== '' ? $cfg['host'] : '(empty)',
            $cfg['port'] !== '' ? $cfg['port'] : '(empty)',
            $cfg['dbname'] !== '' ? $cfg['dbname'] : '(empty)',
            $cfg['user'] !== '' ? $cfg['user'] : '(empty)',
            $cfg['sslmode'] !== '' ? $cfg['sslmode'] : '(unset)',
            $e->getMessage()
        ));

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
