<?php
/**
 * Shared helper functions used across the public site and admin panel
 */

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    return isset($_SESSION['admin_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

function format_file_size($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

function format_date($date) {
    return date('d M Y', strtotime($date));
}

function get_departments($pdo, $activeOnly = true) {
    $sql = "SELECT * FROM departments";
    if ($activeOnly) $sql .= " WHERE status = 'active'";
    $sql .= " ORDER BY name ASC";
    return $pdo->query($sql)->fetchAll();
}

function get_settings($pdo) {
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch();
    
    // If the table is empty, return default values so the site doesn't crash
    return $settings ?: [
        'site_name' => 'UCZ University Research Repository',
        'site_tagline' => 'Advancing Knowledge in Faith and Scholarship',
        'contact_email' => 'research@uczuniversity.ac.zm',
        'reports_per_page' => 12,
        'logo_path' => null
    ];
}

function generate_safe_filename($originalName) {
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid('report_', true) . '.' . strtolower($ext);
}

function flash_set($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
