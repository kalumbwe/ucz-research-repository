<?php
require_once __DIR__ . '/functions.php';

function start_admin_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => (APP_ENV === 'production'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function current_admin(): ?array
{
    start_admin_session();
    return $_SESSION['admin'] ?? null;
}

function require_login(): array
{
    start_admin_session();
    if (empty($_SESSION['admin'])) {
        redirect('/admin/login.php');
    }
    return $_SESSION['admin'];
}

/** Restrict a page to one or more roles, e.g. require_role('super_admin'). */
function require_role($roles): array
{
    $admin = require_login();
    if (!in_array($admin['role'], (array) $roles, true)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;max-width:520px;margin:80px auto;text-align:center">'
            . '<h2>Access denied</h2><p>Your admin account does not have permission to view this page.</p>'
            . '<a href="/admin/dashboard.php">&larr; Back to dashboard</a></div>');
    }
    return $admin;
}

function login_admin(array $adminRow): void
{
    start_admin_session();
    session_regenerate_id(true);
    $_SESSION['admin'] = [
        'id' => $adminRow['id'],
        'full_name' => $adminRow['full_name'],
        'email' => $adminRow['email'],
        'role' => $adminRow['role'],
    ];
}

function logout_admin(): void
{
    start_admin_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
