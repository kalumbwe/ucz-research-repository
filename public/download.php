<?php
require_once __DIR__ . '/../app/includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Invalid request.');
}

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ? AND status = 'published' LIMIT 1");
$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    http_response_code(404);
    exit('File not found.');
}

$filePath = STORAGE_PATH . '/' . $report['file_name'];
if (!is_file($filePath)) {
    http_response_code(404);
    exit('The file for this record is missing. Please contact the repository administrator.');
}

try {
    $pdo->prepare('UPDATE reports SET downloads_count = downloads_count + 1 WHERE id = ?')->execute([$id]);
} catch (Throwable $e) {
    // non-critical
}

$downloadName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $report['original_file_name']);

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('X-Content-Type-Options: nosniff');
readfile($filePath);
exit;
