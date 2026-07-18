<?php
require_once __DIR__ . '/config/config.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT r.*, d.name AS dept_name FROM research_reports r
                        JOIN departments d ON d.id = r.department_id
                        WHERE r.id = ? AND r.status = 'published'");
$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    header('Location: ' . BASE_URL . '/browse.php');
    exit;
}

// increment view count (once per session per report)
if (empty($_SESSION['viewed_' . $id])) {
    $pdo->prepare("UPDATE research_reports SET views = views + 1 WHERE id = ?")->execute([$id]);
    $_SESSION['viewed_' . $id] = true;
    $report['views']++;
}

$page_title = $report['title'];

$related = $pdo->prepare("SELECT id, title, author_name FROM research_reports
                           WHERE department_id = ? AND id != ? AND status = 'published'
                           ORDER BY created_at DESC LIMIT 5");
$related->execute([$report['department_id'], $id]);
$related = $related->fetchAll();

require_once __DIR__ . '/includes/public_header.php';
?>
<div class="container my-5">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb small">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Home</a></li>
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/department.php?id=<?= $report['department_id'] ?>"><?= e($report['dept_name']) ?></a></li>
      <li class="breadcrumb-item active"><?= e($report['title']) ?></li>
    </ol>
  </nav>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card p-4 shadow-sm">
        <span class="badge badge-dept mb-2 align-self-start"><?= e($report['dept_name']) ?></span>
        <h2 class="brand-font"><?= e($report['title']) ?></h2>
        <p class="text-muted mb-3">
          By <strong><?= e($report['author_name']) ?></strong>
          <?php if ($report['co_authors']): ?> &middot; with <?= e($report['co_authors']) ?><?php endif; ?>
        </p>

        <h6 class="brand-font mt-3">Abstract</h6>
        <p><?= nl2br(e($report['abstract'])) ?></p>

        <?php if ($report['description']): ?>
          <h6 class="brand-font mt-3">Description</h6>
          <p><?= nl2br(e($report['description'])) ?></p>
        <?php endif; ?>

        <?php if ($report['keywords']): ?>
          <h6 class="brand-font mt-3">Keywords</h6>
          <p>
            <?php foreach (explode(',', $report['keywords']) as $kw): ?>
              <span class="badge bg-light text-dark border me-1"><?= e(trim($kw)) ?></span>
            <?php endforeach; ?>
          </p>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/download.php?id=<?= $report['id'] ?>" class="btn btn-ucz btn-lg mt-3">
          <i class="fa-solid fa-download me-2"></i>Download PDF (<?= format_file_size($report['file_size']) ?>)
        </a>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card p-3 shadow-sm mb-4">
        <h6 class="brand-font">Report Details</h6>
        <table class="table table-sm small mb-0">
          <tr><th>Department</th><td><?= e($report['dept_name']) ?></td></tr>
          <tr><th>Degree Type</th><td><?= e($report['degree_type']) ?></td></tr>
          <tr><th>Academic Year</th><td><?= e($report['academic_year']) ?></td></tr>
          <tr><th>Publication Date</th><td><?= format_date($report['publication_date']) ?></td></tr>
          <?php if ($report['supervisor']): ?><tr><th>Supervisor</th><td><?= e($report['supervisor']) ?></td></tr><?php endif; ?>
          <?php if ($report['pages']): ?><tr><th>Pages</th><td><?= (int)$report['pages'] ?></td></tr><?php endif; ?>
          <?php if ($report['isbn_issn']): ?><tr><th>ISBN/ISSN</th><td><?= e($report['isbn_issn']) ?></td></tr><?php endif; ?>
          <tr><th>Language</th><td><?= e($report['language']) ?></td></tr>
          <tr><th>Views</th><td><?= (int)$report['views'] ?></td></tr>
          <tr><th>Downloads</th><td><?= (int)$report['downloads'] ?></td></tr>
        </table>
      </div>

      <?php if ($related): ?>
      <div class="card p-3 shadow-sm">
        <h6 class="brand-font">More from <?= e($report['dept_name']) ?></h6>
        <ul class="list-unstyled small mb-0">
          <?php foreach ($related as $rel): ?>
            <li class="mb-2">
              <a href="<?= BASE_URL ?>/report.php?id=<?= $rel['id'] ?>" class="text-decoration-none">
                <i class="fa-solid fa-file-pdf report-icon me-1"></i><?= e($rel['title']) ?>
              </a>
              <div class="text-muted"><?= e($rel['author_name']) ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
