<?php
require_once __DIR__ . '/../app/includes/functions.php';

if (!is_installed()) {
    header('Location: /install.php');
    exit;
}

$pdo = db();
$slug = trim($_GET['slug'] ?? '');

$stmt = $pdo->prepare(
    "SELECT r.*, d.name AS department_name, c.name AS category_name
     FROM reports r
     LEFT JOIN departments d ON d.id = r.department_id
     LEFT JOIN categories c ON c.id = r.category_id
     WHERE r.slug = ? AND r.status = 'published'
     LIMIT 1"
);
$stmt->execute([$slug]);
$report = $stmt->fetch();

if (!$report) {
    http_response_code(404);
    $pageTitle = 'Record not found — ' . APP_NAME;
    require __DIR__ . '/../app/includes/header_public.php';
    echo '<div class="container section"><div class="empty-state"><h3>Record not found</h3><p>This report may have been unpublished or the link is incorrect.</p><a class="btn btn-ink" href="/reports.php">&larr; Back to catalogue</a></div></div>';
    require __DIR__ . '/../app/includes/footer_public.php';
    exit;
}

// count a view (best-effort; not critical if it fails)
try {
    $pdo->prepare('UPDATE reports SET views_count = views_count + 1 WHERE id = ?')->execute([$report['id']]);
} catch (Throwable $e) {
    // ignore
}

$keywords = array_filter(array_map('trim', explode(',', (string) $report['keywords'])));

$pageTitle = $report['title'] . ' — ' . APP_NAME;
$metaDescription = mb_strimwidth($report['abstract'], 0, 155, '…');
require __DIR__ . '/../app/includes/header_public.php';
?>

<section class="section" style="padding-top:40px">
  <div class="container">
    <p class="mono" style="font-size:.8rem;color:var(--muted);margin-bottom:20px">
      <a href="/reports.php">Catalogue</a> &rsaquo; <?= e($report['department_name'] ?? 'Report') ?>
    </p>

    <div class="report-detail">
      <div class="report-main">
        <span class="catalog-type"><?= e($report['category_name'] ?? 'Report') ?></span>
        <h1><?= e($report['title']) ?></h1>
        <p class="report-authors"><?= e($report['authors']) ?> &middot; <?= e((string) $report['publication_year']) ?></p>

        <div class="abstract-box">
          <h4>Abstract</h4>
          <p style="margin:0"><?= nl2br(e($report['abstract'])) ?></p>
        </div>

        <?php if (!empty($keywords)): ?>
          <div class="keywords">
            <?php foreach ($keywords as $kw): ?>
              <span class="keyword-tag"><?= e($kw) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <aside class="record-card">
        <h4>Record Details</h4>
        <div class="record-row"><span class="k">Accession No.</span><span class="v mono">UCZ-<?= str_pad((string) $report['id'], 4, '0', STR_PAD_LEFT) ?></span></div>
        <div class="record-row"><span class="k">School</span><span class="v"><?= e($report['department_name'] ?? '—') ?></span></div>
        <div class="record-row"><span class="k">Type</span><span class="v"><?= e($report['category_name'] ?? '—') ?></span></div>
        <div class="record-row"><span class="k">Year</span><span class="v"><?= e((string) $report['publication_year']) ?></span></div>
        <div class="record-row"><span class="k">File size</span><span class="v"><?= format_bytes((int) $report['file_size_bytes']) ?></span></div>
        <div class="record-row"><span class="k">Downloads</span><span class="v mono"><?= number_format((int) $report['downloads_count']) ?></span></div>
        <a class="btn btn-gold" href="/download.php?id=<?= (int) $report['id'] ?>">Download PDF &darr;</a>
      </aside>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../app/includes/footer_public.php'; ?>
