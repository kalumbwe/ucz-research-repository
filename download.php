<?php
require_once __DIR__ . '/config/config.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM research_reports WHERE id = ? AND status = 'published'");
$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    http_response_code(404);
    die('Report not found.');
}

$filePath = UPLOAD_DIR_REPORTS . $report['file_name'];
if (!file_exists($filePath)) {
    http_response_code(404);
    die('File is missing from the server. Please contact the repository administrator.');
}

// Log the download
$pdo->prepare("INSERT INTO download_logs (report_id, ip_address, user_agent) VALUES (?, ?, ?)")
    ->execute([$id, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

// Increment counter
$pdo->prepare("UPDATE research_reports SET downloads = downloads + 1 WHERE id = ?")->execute([$id]);

// Stream the file
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($report['original_file_name']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: must-revalidate');
header('Pragma: public');
readfile($filePath);
exit;
