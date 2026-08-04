<?php
require_once __DIR__ . '/../config/database.php';

/** Shorthand HTML-escape for use throughout views. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** Turns a title into a unique, URL-safe slug for the reports table. */
function make_slug(string $text): string
{
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : 'report';
}

function unique_report_slug(string $title, ?int $excludeId = null): string
{
    $base = make_slug($title);
    $slug = $base;
    $i = 1;
    $pdo = db();
    while (true) {
        if ($excludeId) {
            $stmt = $pdo->prepare('SELECT id FROM reports WHERE slug = ? AND id != ?');
            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM reports WHERE slug = ?');
            $stmt->execute([$slug]);
        }
        if (!$stmt->fetch()) {
            return $slug;
        }
        $i++;
        $slug = $base . '-' . $i;
    }
}

function format_bytes(int $bytes): string
{
    if ($bytes <= 0) {
        return '0 KB';
    }
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = (int) floor(log($bytes, 1024));
    $i = max(0, min($i, count($units) - 1));
    return round($bytes / (1024 ** $i), 1) . ' ' . $units[$i];
}

/** Session-based CSRF token helpers (works for both public and admin forms). */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $sent = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
}

/** One-shot flash messages stored in the session. */
function flash_set(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function old(string $key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

function stash_old(array $data): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['old'] = $data;
}

function clear_old(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION['old']);
}

/** Simple pagination helper. Returns [offset, totalPages, currentPage]. */
function paginate(int $totalRows, int $perPage = 12): array
{
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $totalPages = max(1, (int) ceil($totalRows / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    return [$offset, $totalPages, $page];
}

function query_string_with(array $overrides): string
{
    $params = array_merge($_GET, $overrides);
    foreach ($params as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        }
    }
    return '?' . http_build_query($params);
}

/** All departments, cached for the request. */
function all_departments(): array
{
    static $rows = null;
    if ($rows === null) {
        $rows = db()->query('SELECT * FROM departments ORDER BY name ASC')->fetchAll();
    }
    return $rows;
}

/** All categories (research types), cached for the request. */
function all_categories(): array
{
    static $rows = null;
    if ($rows === null) {
        $rows = db()->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();
    }
    return $rows;
}

/** Default site settings — used to seed the table and as a fallback if a key is ever missing. */
function default_settings(): array
{
    return [
        'hero_eyebrow'    => 'Est. digital archive · United Church of Zambia University',
        'hero_tagline'    => 'Knowledge for Service, catalogued for discovery.',
        'hero_subtext'    => 'The official repository of research reports, theses, dissertations and scholarly papers produced across every school of the University. Search the record, read the abstract, download the PDF.',
        'footer_about'    => 'The digital archive of research reports, theses and scholarly work produced across the United Church of Zambia University community.',
        'footer_tagline'  => 'Knowledge for Service and Fullness of Life',
        'contact_address' => 'Lusaka, Zambia',
        'contact_email'   => '',
        'contact_phone'   => '',
    ];
}

/**
 * All site settings as [key => value], cached for the request.
 * Self-provisions the site_settings table and seeds defaults on first
 * call — safe to run against an already-live database with no
 * separate migration step.
 */
function all_settings(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
        setting_key   VARCHAR(100) PRIMARY KEY,
        setting_value TEXT NOT NULL DEFAULT '',
        updated_at    TIMESTAMP NOT NULL DEFAULT NOW()
    )");

    $existing = [];
    foreach ($pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll() as $row) {
        $existing[$row['setting_key']] = $row['setting_value'];
    }

    $defaults = default_settings();
    $missing = array_diff_key($defaults, $existing);
    if (!empty($missing)) {
        $stmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT (setting_key) DO NOTHING');
        foreach ($missing as $k => $v) {
            $stmt->execute([$k, $v]);
        }
        $existing += $missing;
    }

    $cache = $existing + $defaults;
    return $cache;
}

/** Shorthand accessor for a single site setting. */
function setting(string $key, string $default = ''): string
{
    $all = all_settings();
    return $all[$key] !== '' ? $all[$key] : $default;
}

/** Saves one or more settings (key => value). Unknown keys are ignored. */
function save_settings(array $values): void
{
    $allowed = array_keys(default_settings());
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO site_settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW())
         ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value, updated_at = NOW()'
    );
    foreach ($values as $key => $value) {
        if (in_array($key, $allowed, true)) {
            $stmt->execute([$key, trim($value)]);
        }
    }
}

/**
 * Validates and moves an uploaded PDF from $_FILES[$field] into
 * STORAGE_PATH under a random, collision-proof name.
 * Returns ['file_name' => ..., 'original_file_name' => ..., 'size' => ...].
 * Throws RuntimeException with a user-facing message on any failure.
 */
function handle_pdf_upload(string $field): array
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Please choose a PDF file to upload.');
    }
    $file = $_FILES[$field];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The file failed to upload (error code ' . $file['error'] . ').');
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('The file is too large. Maximum allowed size is ' . format_bytes(MAX_UPLOAD_BYTES) . '.');
    }

    $originalName = $file['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        throw new RuntimeException('Only PDF files are accepted.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if ($mime !== 'application/pdf') {
        throw new RuntimeException('This file does not look like a valid PDF.');
    }

    $handle = fopen($file['tmp_name'], 'rb');
    $header = $handle ? fread($handle, 5) : '';
    if ($handle) fclose($handle);
    if ($header !== '%PDF-') {
        throw new RuntimeException('This file does not look like a valid PDF.');
    }

    if (!is_dir(STORAGE_PATH) && !mkdir(STORAGE_PATH, 0755, true)) {
        throw new RuntimeException('The upload storage directory is not writable.');
    }

    $storedName = bin2hex(random_bytes(16)) . '.pdf';
    $destination = STORAGE_PATH . '/' . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save the uploaded file to storage.');
    }

    return [
        'file_name' => $storedName,
        'original_file_name' => $originalName,
        'size' => filesize($destination) ?: $file['size'],
    ];
}
