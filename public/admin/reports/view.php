<?php
require_once __DIR__ . '/../../../app/includes/auth.php';
$admin = require_login();
$pdo = db();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT r.*, d.name AS department_name, c.name AS category_name, a.full_name AS uploaded_by_name
     FROM reports r
     LEFT JOIN departments d ON d.id = r.department_id
     LEFT JOIN categories c ON c.id = r.category_id
     LEFT JOIN admin_users a ON a.id = r.uploaded_by
     WHERE r.id = ?"
);
$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    flash_set('error', 'Report not found.');
    redirect('/admin/reports/index.php');
}

$pageTitle = 'Report Record';
$activeMenu = 'reports';
$breadcrumbs = [['label' => 'Reports', 'url' => '/admin/reports/index.php'], ['label' => 'View']];
require __DIR__ . '/../../../app/includes/header_admin.php';
?>

<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><?= e($report['title']) ?></h3>
        <span class="badge badge-<?= $report['status'] === 'published' ? 'success' : 'secondary' ?>"><?= e($report['status']) ?></span>
      </div>
      <div class="card-body">
        <p class="text-muted mb-3"><?= e($report['authors']) ?> &middot; <?= e((string) $report['publication_year']) ?></p>
        <h6 class="text-uppercase text-muted" style="font-size:.75rem;letter-spacing:.05em">Abstract</h6>
        <p><?= nl2br(e($report['abstract'])) ?></p>
        <?php if (!empty($report['keywords'])): ?>
          <h6 class="text-uppercase text-muted" style="font-size:.75rem;letter-spacing:.05em">Keywords</h6>
          <p><?= e($report['keywords']) ?></p>
        <?php endif; ?>
      </div>
      <div class="card-footer">
        <a href="/admin/reports/edit.php?id=<?= (int) $report['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-pen mr-1"></i> Edit</a>
        <a href="/download.php?id=<?= (int) $report['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-download mr-1"></i> Download PDF</a>
        <a href="/report.php?slug=<?= urlencode($report['slug']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-up-right-from-square mr-1"></i> View Public Page</a>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card card-outline card-primary">
      <div class="card-header"><h3 class="card-title">Record Details</h3></div>
      <div class="card-body" style="font-size:.88rem">
        <p class="mb-2"><strong>Accession No.</strong><br><span class="accession-code">UCZ-<?= str_pad((string) $report['id'], 4, '0', STR_PAD_LEFT) ?></span></p>
        <p class="mb-2"><strong>School</strong><br><?= e($report['department_name'] ?? '—') ?></p>
        <p class="mb-2"><strong>Type</strong><br><?= e($report['category_name'] ?? '—') ?></p>
        <p class="mb-2"><strong>File</strong><br><?= e($report['original_file_name']) ?> (<?= format_bytes((int) $report['file_size_bytes']) ?>)</p>
        <p class="mb-2"><strong>Views</strong><br><?= number_format((int) $report['views_count']) ?></p>
        <p class="mb-2"><strong>Downloads</strong><br><?= number_format((int) $report['downloads_count']) ?></p>
        <p class="mb-2"><strong>Uploaded by</strong><br><?= e($report['uploaded_by_name'] ?? 'Unknown') ?></p>
        <p class="mb-0"><strong>Uploaded on</strong><br><?= date('d M Y, H:i', strtotime($report['created_at'])) ?></p>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../../app/includes/footer_admin.php'; ?>
