<?php
require_once __DIR__ . '/config/config.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
$stmt->execute([$id]);
$department = $stmt->fetch();

if (!$department) {
    header('Location: ' . BASE_URL . '/browse.php');
    exit;
}

$page_title = $department['name'];

$stmt = $pdo->prepare("SELECT r.*, d.name AS dept_name FROM research_reports r
                        JOIN departments d ON d.id = r.department_id
                        WHERE r.department_id = ? AND r.status = 'published'
                        ORDER BY r.created_at DESC");
$stmt->execute([$id]);
$reports = $stmt->fetchAll();

require_once __DIR__ . '/includes/public_header.php';
?>
<div class="container my-5">
  <h2 class="brand-font mb-1"><?= e($department['name']) ?></h2>
  <p class="text-muted mb-4"><?= e($department['description']) ?></p>
  <p class="text-muted"><?= count($reports) ?> report<?= count($reports) === 1 ? '' : 's' ?> in this department</p>

  <div class="row g-4">
    <?php foreach ($reports as $r): ?>
      <div class="col-md-4">
        <div class="card report-card p-3">
          <i class="fa-solid fa-file-pdf fa-2x report-icon mb-2"></i>
          <h6 class="brand-font"><a href="<?= BASE_URL ?>/report.php?id=<?= $r['id'] ?>" class="text-decoration-none text-dark"><?= e($r['title']) ?></a></h6>
          <p class="small text-muted mb-2"><?= e($r['author_name']) ?> &middot; <?= e($r['academic_year']) ?></p>
          <p class="small mb-3"><?= e(mb_strimwidth($r['abstract'], 0, 110, '...')) ?></p>
          <span class="badge badge-degree"><?= e($r['degree_type']) ?></span>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($reports)): ?>
      <p class="text-muted">No published reports yet for this department.</p>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
