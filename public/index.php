<?php
require_once __DIR__ . '/../app/includes/functions.php';

if (!is_installed()) {
    header('Location: /install.php');
    exit;
}

$pdo = db();

$totalReports = (int) $pdo->query("SELECT COUNT(*) c FROM reports WHERE status = 'published'")->fetch()['c'];
$totalDepartments = (int) $pdo->query("SELECT COUNT(*) c FROM departments")->fetch()['c'];
$lastUpdated = $pdo->query("SELECT MAX(created_at) m FROM reports WHERE status = 'published'")->fetch()['m'];

$recent = $pdo->query(
    "SELECT r.*, d.name AS department_name, c.name AS category_name
     FROM reports r
     LEFT JOIN departments d ON d.id = r.department_id
     LEFT JOIN categories c ON c.id = r.category_id
     WHERE r.status = 'published'
     ORDER BY r.created_at DESC
     LIMIT 6"
)->fetchAll();

$deptCounts = $pdo->query(
    "SELECT d.id, d.name, d.slug, COUNT(r.id) FILTER (WHERE r.status = 'published') AS cnt
     FROM departments d
     LEFT JOIN reports r ON r.department_id = d.id
     GROUP BY d.id
     ORDER BY d.name ASC"
)->fetchAll();

$pageTitle = APP_NAME . ' — Digital archive of academic research';
require __DIR__ . '/../app/includes/header_public.php';
?>

<section class="hero">
  <div class="container">
    <div class="hero-inner">
      <span class="eyebrow">Est. digital archive &middot; United Church of Zambia University</span>
      <h1>Knowledge for Service, catalogued for discovery.</h1>
      <p class="lede">The official repository of research reports, theses, dissertations and scholarly papers produced across every school of the University. Search the record, read the abstract, download the PDF.</p>
    </div>

    <div class="search-panel">
      <span class="search-panel-label">Search the repository</span>
      <form class="search-form" action="/reports.php" method="get">
        <input type="text" name="q" placeholder="Search by title, author or keyword&hellip;" aria-label="Search research reports">
        <select name="department" aria-label="Filter by school">
          <option value="">All Schools</option>
          <?php foreach (all_departments() as $d): ?>
            <option value="<?= e($d['slug']) ?>"><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-gold">Search</button>
      </form>
    </div>
  </div>
</section>

<div class="ledger">
  <div class="container">
    <span><b class="mono"><?= number_format($totalReports) ?></b> reports indexed</span>
    <span><b class="mono"><?= number_format($totalDepartments) ?></b> schools represented</span>
    <span>updated <b class="mono"><?= $lastUpdated ? date('M Y', strtotime($lastUpdated)) : date('M Y') ?></b></span>
    <span>open access &middot; PDF download</span>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>Recently catalogued</h2>
      <a href="/reports.php" class="see-all mono">Browse all &rarr;</a>
    </div>

    <?php if (empty($recent)): ?>
      <div class="empty-state">
        <h3>No research has been published yet</h3>
        <p>Once an administrator uploads and publishes a report, it will appear here.</p>
      </div>
    <?php else: ?>
      <div class="catalog-grid">
        <?php foreach ($recent as $r): ?>
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
    <?php endif; ?>
  </div>
</section>

<section class="section" style="background:var(--paper-2);border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
  <div class="container">
    <div class="section-head">
      <h2>Browse by school</h2>
    </div>
    <div class="dept-list">
      <?php foreach ($deptCounts as $d): ?>
        <a class="dept-chip" href="/reports.php?department=<?= urlencode($d['slug']) ?>">
          <?= e($d['name']) ?><span class="count">(<?= (int) $d['cnt'] ?>)</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../app/includes/footer_public.php'; ?>
