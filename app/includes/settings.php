<?php
/**
 * Site Settings — helper functions for reading / writing key-value settings.
 * Auto-creates the site_settings table on first access.
 */

require_once __DIR__ . '/database.php';

/**
 * Ensure the site_settings table exists (idempotent).
 */
function ensure_settings_table(): void
{
    db()->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            setting_key    VARCHAR(100)  PRIMARY KEY,
            setting_value  TEXT          NOT NULL DEFAULT '',
            updated_at     TIMESTAMP     NOT NULL DEFAULT NOW()
        )
    ");
}

/**
 * Seed default settings if the table is empty.
 */
function seed_default_settings(): void
{
    $count = (int) db()->query("SELECT COUNT(*) FROM site_settings")->fetchColumn();
    if ($count > 0) {
        return;
    }

    $year = date('Y');
    $defaults = [
        'site_name'       => 'UCZ University Research Repository',
        'site_tagline'    => 'A digital archive for research reports, theses, dissertations and scholarly papers',
        'contact_email'   => 'research@ucz.ac.zm',
        'contact_phone'   => '+260 211 XXX XXX',
        'contact_address' => 'University of Zambia, Lusaka, Zambia',
        'footer_text'     => "© {$year} UCZ University. All rights reserved.",
    ];

    $stmt = db()->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v) ON CONFLICT (setting_key) DO NOTHING");
    foreach ($defaults as $key => $value) {
        $stmt->execute([':k' => $key, ':v' => $value]);
    }
}

/**
 * Get a single setting value.
 */
function get_setting(string $key, string $default = ''): string
{
    static $cache = [];

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    ensure_settings_table();

    try {
        $stmt = db()->prepare("SELECT setting_value FROM site_settings WHERE setting_key = :k");
        $stmt->execute([':k' => $key]);
        $row = $stmt->fetch();
        $value = $row ? $row['setting_value'] : $default;
    } catch (Throwable $e) {
        $value = $default;
    }

    $cache[$key] = $value;
    return $value;
}

/**
 * Get ALL settings as a key => value associative array.
 */
function get_all_settings(): array
{
    ensure_settings_table();
    seed_default_settings();

    $rows = db()->query("SELECT setting_key, setting_value FROM site_settings ORDER BY setting_key")->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

/**
 * Save (insert or update) a single setting.
 */
function set_setting(string $key, string $value): void
{
    ensure_settings_table();

    $stmt = db()->prepare("
        INSERT INTO site_settings (setting_key, setting_value, updated_at)
        VALUES (:k, :v, NOW())
        ON CONFLICT (setting_key)
        DO UPDATE SET setting_value = :v2, updated_at = NOW()
    ");
    $stmt->execute([':k' => $key, ':v' => $value, ':v2' => $value]);
}

/**
 * Save multiple settings at once.
 */
function save_settings(array $keyValuePairs): void
{
    ensure_settings_table();

    $stmt = db()->prepare("
        INSERT INTO site_settings (setting_key, setting_value, updated_at)
        VALUES (:k, :v, NOW())
        ON CONFLICT (setting_key)
        DO UPDATE SET setting_value = :v2, updated_at = NOW()
    ");

    foreach ($keyValuePairs as $key => $value) {
        $stmt->execute([':k' => $key, ':v' => $value, ':v2' => $value]);
    }
}