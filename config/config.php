<?php
/**
 * General application configuration
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL - update this to match where you place the folder inside htdocs
// e.g. http://localhost/ucz_research_repository
define('BASE_URL', 'http://localhost/ucz_research_repository');

define('UPLOAD_DIR_REPORTS', __DIR__ . '/../uploads/reports/');
define('UPLOAD_DIR_COVERS', __DIR__ . '/../uploads/covers/');
define('UPLOAD_URL_REPORTS', BASE_URL . '/uploads/reports/');
define('UPLOAD_URL_COVERS', BASE_URL . '/uploads/covers/');

define('MAX_FILE_SIZE_MB', 25); // maximum PDF upload size in MB

date_default_timezone_set('Africa/Lusaka');

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
