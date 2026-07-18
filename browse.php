<?php
require_once __DIR__ . '/config/config.php';
$page_title = "Browse Repository";

$departments = get_departments($pdo);

// Filters
$deptFilter   = isset($_GET['department']) ? (int)$_GET['department'] : 0;
$degreeFilter = $_GET['degree'] ?? '';
$yearFilter   = $_GET['year'] ?? '';
$sort         = $_GET['sort'] ?? 'newest';

$where = ["r.status = 'published'"];
$params = [];

if ($deptFilter) {
    $where[] = "r.department_id = :dept";
    $params[':dept'] = $deptFilter;
}
if ($degreeFilter) {
    $where[] = "r.degree_type = :degree";
    $params[':degree'] = $degreeFilter;
}
if ($yearFilter) {
    $where[] = "r.academic_year = :year";
    $params[':year'] = $yearFilter;
}

$orderBy = "r.created_at DESC";
if ($sort === 'oldest') $orderBy = "r.created_at ASC";
if ($sort === 'title') $orderBy = "r.title ASC";
if ($sort === 'popular') $orderBy = "r.downloads DESC";

// Pagination
$perPage = (int)($settings['reports_per_page'] ?? 12) ?: 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM research_reports r WHERE $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$sql = "SELECT r.*, d.name AS dept_name FROM research_reports r
        JOIN departments d ON d.id = r.department_id
        WHERE $whereSql
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reports = $stmt->fetchAll();

$years = $pdo->query("SELECT DISTINCT academic_year FROM research_reports ORDER BY academic_year DESC")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/includes/public_header.php';
?>
<div class="container my-5">
  <h2 class="brand-font mb-4">Browse Repository</h2>

  <form method="GET" class="row g-2 mb-4 align-items-end bg-white p-3 rounded shadow-sm">
    <div class="col-md-3">
      <label class="form-label small mb-1">Department</label>
      <select name="department" class="form-select">
        <option value="">All Departments</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= $d['id'] ?>" <?= $deptFilter == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small mb-1">Degree Type</label>
      <select name="degree" class="form-select">
        <option value="">All Types</option>
        <?php foreach (['Undergraduate','Masters','PhD','Staff Research','Conference Paper','Journal Article'] as $dt): ?>
          <option value="<?= $dt ?>" <?= $degreeFilter === $dt ? 'selected' : '' ?>><?= $dt ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small mb-1">Year</label>
      <select name="year" class="form-select">
        <option value="">All Years</option>
        <?php foreach ($years as $y): ?>
          <option value="<?= e($y) ?>" <?= $yearFilter === $y ? 'selected' : '' ?>><?= e($y) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small mb-1">Sort By</label>
      <select name="sort" class="form-select">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
        <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Title (A-Z)</option>
        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most Downloaded</option>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-ucz w-100" type="submit"><i class="fa-solid fa-filter me-1"></i>Filter</button>
    </div>
  </form>

  <p class="text-muted"><?= $totalRows ?> report<?= $totalRows === 1 ? '' : 's' ?> found</p>

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
    <?php if (empty($reports)): ?>
      <p class="text-muted">No reports match these filters.</p>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <nav class="mt-5">
      <ul class="pagination justify-content-center">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <li class="page-item <?= $p == $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
