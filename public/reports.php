<?php
require_once __DIR__ . '/../app/includes/functions.php';

if (!is_installed()) {
    header('Location: /install.php');
    exit;
}

$pdo = db();

$q = trim($_GET['q'] ?? '');
$departmentSlug = trim($_GET['department'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$year = trim($_GET['year'] ?? '');

$where = ["r.status = 'published'"];
$params = [];

if ($q !== '') {
    $where[] = "(r.title ILIKE ? OR r.authors ILIKE ? OR r.keywords ILIKE ? OR r.abstract ILIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($departmentSlug !== '') {
    $where[] = "d.slug = ?";
    $params[] = $departmentSlug;
}
if ($categorySlug !== '') {
    $where[] = "c.slug = ?";
    $params[] = $categorySlug;
}
if ($year !== '' && ctype_digit($year)) {
    $where[] = "r.publication_year = ?";
    $params[] = (int) $year;
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare(
    "SELECT COUNT(*) c FROM reports r
     LEFT JOIN departments d ON d.id = r.department_id
     LEFT JOIN categories c ON c.id = r.category_id
     WHERE {$whereSql}"
);
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['c'];

$perPage = 9;
[$offset, $totalPages, $page] = paginate($total, $perPage);

$stmt = $pdo->prepare(
    "SELECT r.*, d.name AS department_name, c.name AS category_name
     FROM reports r
     LEFT JOIN departments d ON d.id = r.department_id
     LEFT JOIN categories c ON c.id = r.category_id
     WHERE {$whereSql}
     ORDER BY r.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$years = $pdo->query("SELECT DISTINCT publication_year FROM reports WHERE status='published' ORDER BY publication_year DESC")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Browse Research — ' . APP_NAME;
require __DIR__ . '/../app/includes/header_public.php';
?>

<section class="page-hero">
  <div class="container">
    <h1>Browse Research</h1>
    <p>Search and filter the full catalogue of published reports.</p>
  </div>
</section>

<section class="section" style="padding-top:36px">
  <div class="container">
    <div class="filter-bar">
      <form method="get">
        <div class="filter-field" style="flex:2;min-width:220px">
          <label for="q">Keyword</label>
          <input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="Title, author or keyword">
        </div>
        <div class="filter-field">
          <label for="department">School</label>
          <select id="department" name="department" data-autosubmit>
            <option value="">All Schools</option>
            <?php foreach (all_departments() as $d): ?>
              <option value="<?= e($d['slug']) ?>" <?= $departmentSlug === $d['slug'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-field">
          <label for="category">Type</label>
          <select id="category" name="category" data-autosubmit>
            <option value="">All Types</option>
            <?php foreach (all_categories() as $c): ?>
              <option value="<?= e($c['slug']) ?>" <?= $categorySlug === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-field">
          <label for="year">Year</label>
          <select id="year" name="year" data-autosubmit>
            <option value="">Any Year</option>
            <?php foreach ($years as $y): ?>
              <option value="<?= e((string) $y) ?>" <?= $year == (string) $y ? 'selected' : '' ?>><?= e((string) $y) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-ink btn-sm">Apply</button>
        <?php if ($q || $departmentSlug || $categorySlug || $year): ?>
          <a href="/reports.php" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
      </form>
    </div>

    <p class="result-count"><?= number_format($total) ?> result<?= $total === 1 ? '' : 's' ?><?= $q !== '' ? ' for "' . e($q) . '"' : '' ?></p>

    <?php if (empty($reports)): ?>
      <div class="empty-state">
        <h3>No matching records</h3>
        <p>Try a different keyword, or clear your filters to see the full catalogue.</p>
      </div>
    <?php else: ?>
      <div class="catalog-grid">
        <?php foreach ($reports as $r): ?>
          <article class="catalog-card">
            <span class="catalog-accession">ACC. <?= str_pad((string) $r['id'], 4, '0', STR_PAD_LEFT) ?></span>
            <span class="catalog-type"><?= e($r['category_name'] ?? 'Report') ?></span>
            <h3><a href="/report.php?slug=<?= urlencode($r['slug']) ?>"><?= e($r['title']) ?></a></h3>
            <div class="catalog-meta"><?= e($r['authors']) ?> &middot; <?= e((string) $r['publication_year']) ?><?= $r['department_name'] ? ' &middot; ' . e($r['department_name']) : '' ?></div>
            <p class="catalog-abstract"><?= e($r['abstract']) ?></p>
            <div class="catalog-foot">
              <span><?= format_bytes((int) $r['file_size_bytes']) ?> PDF</span>
              <a href="/report.php?slug=<?= urlencode($r['slug']) ?>">View record &rarr;</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Pagination">
          <?php if ($page > 1): ?><a href="<?= e(query_string_with(['page' => $page - 1])) ?>">&larr; Prev</a><?php endif; ?>
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $page): ?>
              <span class="active"><?= $i ?></span>
            <?php else: ?>
              <a href="<?= e(query_string_with(['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          <?php if ($page < $totalPages): ?><a href="<?= e(query_string_with(['page' => $page + 1])) ?>">Next &rarr;</a><?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../app/includes/footer_public.php'; ?>
