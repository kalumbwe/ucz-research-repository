<?php
require_once __DIR__ . '/config/config.php';
$page_title = "Home";

$totalReports   = $pdo->query("SELECT COUNT(*) FROM research_reports WHERE status='published'")->fetchColumn();
$totalDownloads = $pdo->query("SELECT SUM(downloads) FROM research_reports WHERE status='published'")->fetchColumn();
$totalDepts     = $pdo->query("SELECT COUNT(*) FROM departments WHERE status='active'")->fetchColumn();

$departments = get_departments($pdo);

$recent = $pdo->query("SELECT r.*, d.name AS dept_name FROM research_reports r
                        JOIN departments d ON d.id = r.department_id
                        WHERE r.status = 'published'
                        ORDER BY r.created_at DESC LIMIT 6")->fetchAll();

require_once __DIR__ . '/includes/public_header.php';
?>

<section class="hero-banner text-center">
  <div class="container">
    <h1 class="mb-3">UCZ University Research Repository</h1>
    <p class="lead mb-4"><?= e($settings['site_tagline']) ?></p>
    <form action="<?= BASE_URL ?>/search.php" method="GET" class="hero-search">
      <div class="input-group input-group-lg">
        <input type="search" name="q" class="form-control" placeholder="Search by title, author, or keyword...">
        <button class="btn btn-warning fw-bold" type="submit"><i class="fa-solid fa-search me-1"></i>Search</button>
      </div>
    </form>
  </div>
</section>

<div class="container my-5">
  <div class="row g-3 mb-5">
    <div class="col-md-4">
      <div class="card stat-card p-3 text-center">
        <h2 class="brand-font mb-0"><?= (int)$totalReports ?></h2>
        <p class="text-muted mb-0">Published Reports</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card stat-card p-3 text-center">
        <h2 class="brand-font mb-0"><?= (int)$totalDownloads ?></h2>
        <p class="text-muted mb-0">Total Downloads</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card stat-card p-3 text-center">
        <h2 class="brand-font mb-0"><?= (int)$totalDepts ?></h2>
        <p class="text-muted mb-0">Departments</p>
      </div>
    </div>
  </div>

  <h4 class="brand-font mb-3">Browse by Department</h4>
  <div class="mb-5">
    <?php foreach ($departments as $d): ?>
      <a class="department-pill" href="<?= BASE_URL ?>/department.php?id=<?= $d['id'] ?>"><?= e($d['name']) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="brand-font mb-0">Recently Added</h4>
    <a href="<?= BASE_URL ?>/browse.php" class="btn btn-sm btn-ucz">View All <i class="fa-solid fa-arrow-right ms-1"></i></a>
  </div>

  <div class="row g-4">
    <?php if (empty($recent)): ?>
      <p class="text-muted">No research reports have been published yet.</p>
    <?php endif; ?>
    <?php foreach ($recent as $r): ?>
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
  </div>
</div>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
