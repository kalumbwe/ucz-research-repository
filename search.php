<?php
require_once __DIR__ . '/config/config.php';
$page_title = "Search Results";

$q = trim($_GET['q'] ?? '');
$reports = [];

if ($q !== '') {
    $stmt = $pdo->prepare("SELECT r.*, d.name AS dept_name,
                            MATCH(r.title, r.abstract, r.keywords, r.author_name) AGAINST (:q IN NATURAL LANGUAGE MODE) AS relevance
                            FROM research_reports r
                            JOIN departments d ON d.id = r.department_id
                            WHERE r.status = 'published'
                            AND (MATCH(r.title, r.abstract, r.keywords, r.author_name) AGAINST (:q2 IN NATURAL LANGUAGE MODE)
                                 OR r.title LIKE :like OR r.author_name LIKE :like2 OR r.keywords LIKE :like3)
                            ORDER BY relevance DESC, r.created_at DESC");
    $like = "%$q%";
    $stmt->execute([':q' => $q, ':q2' => $q, ':like' => $like, ':like2' => $like, ':like3' => $like]);
    $reports = $stmt->fetchAll();
}

require_once __DIR__ . '/includes/public_header.php';
?>
<div class="container my-5">
  <h2 class="brand-font mb-1">Search Results</h2>
  <p class="text-muted mb-4">
    <?php if ($q !== ''): ?>
      <?= count($reports) ?> result<?= count($reports) === 1 ? '' : 's' ?> for "<?= e($q) ?>"
    <?php else: ?>
      Enter a search term above to find research reports.
    <?php endif; ?>
  </p>

  <div class="row g-4">
    <?php foreach ($reports as $r): ?>
      <div class="col-md-4">
        <div class="card report-card p-3">
          <i class="fa-solid fa-file-pdf fa-2x report-icon mb-2"></i>
          <h6 class="brand-font"><a href="<?= BASE_URL ?>/report.php?id=<?= $r['id'] ?>" class="text-decoration-none text-dark"><?= e($r['title']) ?></a></h6>
          <p class="small text-muted mb-2"><?= e($r['author_name']) ?> &middot; <?= e($r['academic_year']) ?></p>
          <p class="small mb-3"><?= e(mb_strimwidth($r['abstract'], 0, 110, '...')) ?></p>
          <div>
            <span class="badge badge-dept mb-1"><?= e($r['dept_name']) ?></span>
            <span class="badge badge-degree mb-1"><?= e($r['degree_type']) ?></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if ($q !== '' && empty($reports)): ?>
      <p class="text-muted">No reports matched your search. Try different keywords.</p>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
