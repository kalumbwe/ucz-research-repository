<?php
require_once __DIR__ . '/../../../app/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    flash_set('error', 'Invalid request.');
    redirect('/admin/reports/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$pdo = db();

$stmt = $pdo->prepare('SELECT * FROM reports WHERE id = ?');
$stmt->execute([$id]);
$report = $stmt->fetch();

if ($report) {
    $pdo->prepare('DELETE FROM reports WHERE id = ?')->execute([$id]);
    $path = STORAGE_PATH . '/' . $report['file_name'];
    if (is_file($path)) {
        @unlink($path);
    }
    flash_set('success', 'Report "' . $report['title'] . '" was deleted.');
} else {
    flash_set('error', 'Report not found.');
}

redirect('/admin/reports/index.php');
