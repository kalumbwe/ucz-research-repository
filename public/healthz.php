<?php
/**
 * Liveness probe for Render's health check — always 200 so a database
 * outage does not take the whole service down.
 *
 * Add ?db=1 to also test the database. The plain result is "db: ok" or
 * "db: fail". Append &key=<INSTALL_KEY> to see the real driver error and
 * the connection settings in use (password never included).
 */
header('Content-Type: text/plain');
http_response_code(200);

if (!isset($_GET['db'])) {
    echo 'OK';
    exit;
}

// Only pull in the app bootstrap for the deeper check, so the plain
// liveness probe stays independent of application config.
require_once __DIR__ . '/../app/config/database.php';

$authorized = INSTALL_KEY !== ''
    && isset($_GET['key'])
    && hash_equals(INSTALL_KEY, (string) $_GET['key']);

$cfg = db_settings();

try {
    $pdo = new PDO($cfg['dsn'], $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $version = $pdo->query('SELECT version()')->fetchColumn();
    echo "OK\ndb: ok\n";
    if ($authorized) {
        echo "server: {$version}\n";
        echo "installed: " . (is_installed() ? 'yes' : 'no — visit /install.php') . "\n";
    }
} catch (Throwable $e) {
    echo "OK\ndb: fail\n";
    if ($authorized) {
        echo "config source: {$cfg['source']}\n";
        echo 'host: ' . ($cfg['host'] !== '' ? $cfg['host'] : '(empty)') . "\n";
        echo 'port: ' . ($cfg['port'] !== '' ? $cfg['port'] : '(empty)') . "\n";
        echo 'dbname: ' . ($cfg['dbname'] !== '' ? $cfg['dbname'] : '(empty)') . "\n";
        echo 'user: ' . ($cfg['user'] !== '' ? $cfg['user'] : '(empty)') . "\n";
        echo 'sslmode: ' . ($cfg['sslmode'] !== '' ? $cfg['sslmode'] : '(unset)') . "\n";
        echo 'password set: ' . ($cfg['pass'] !== '' ? 'yes' : 'NO') . "\n";
        echo "error: " . $e->getMessage() . "\n";
    }
}
