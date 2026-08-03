<?php
require_once __DIR__ . '/../../app/includes/auth.php';
$admin = require_login();
$pdo = db();

$totalReports = (int) $pdo->query("SELECT COUNT(*) c FROM reports")->fetch()['c'];
$totalPublished = (int) $pdo->query("SELECT COUNT(*) c FROM reports WHERE status='published'")->fetch()['c'];
$totalDrafts = $totalReports - $totalPublished;
$totalDownloads = (int) $pdo->query("SELECT COALESCE(SUM(downloads_count),0) c FROM reports")->fetch()['c'];
$totalViews = (int) $pdo->query("SELECT COALESCE(SUM(views_count),0) c FROM reports")->fetch()['c'];
$totalDepartments = (int) $pdo->query("SELECT COUNT(*) c FROM departments")->fetch()['c'];

$byDept = $pdo->query(
    "SELECT d.name, COUNT(r.id) cnt FROM departments d
     LEFT JOIN reports r ON r.department_id = d.id
     GROUP BY d.name ORDER BY d.name ASC"
)->fetchAll();

$recent = $pdo->query(
    "SELECT r.id, r.title, r.slug, r.status, r.publication_year, r.created_at, d.name AS department_name
     FROM reports r LEFT JOIN departments d ON d.id = r.department_id
     ORDER BY r.created_at DESC LIMIT 8"
)->fetchAll();

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
require __DIR__ . '/../../app/includes/header_admin.php';
?>

<div class="row">
  <div class="col-lg-3 col-6">
    <div class="small-box bg-ink">
      <div class="inner"><h3><?= number_format($totalReports) ?></h3><p>Total Reports</p></div>
      <div class="icon"><i class="fas fa-file-pdf"></i></div>
      <a href="/admin/reports/index.php" class="small-box-footer">View all <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-gold">
      <div class="inner"><h3><?= number_format($totalDownloads) ?></h3><p>Total Downloads</p></div>
      <div class="icon"><i class="fas fa-download"></i></div>
      <span class="small-box-footer">&nbsp;</span>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-success">
      <div class="inner"><h3><?= number_format($totalViews) ?></h3><p>Total Views</p></div>
      <div class="icon"><i class="fas fa-eye"></i></div>
      <span class="small-box-footer">&nbsp;</span>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-secondary">
      <div class="inner"><h3><?= number_format($totalDepartments) ?></h3><p>Schools / Departments</p></div>
      <div class="icon"><i class="fas fa-building-columns"></i></div>
      <a href="/admin/departments/index.php" class="small-box-footer">Manage <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-4">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Quick Actions</h3></div>
      <div class="card-body d-grid gap-2">
        <a href="/admin/reports/create.php" class="btn btn-primary btn-block mb-2"><i class="fas fa-upload mr-1"></i> Upload New Report</a>
        <a href="/admin/departments/index.php" class="btn btn-outline-primary btn-block mb-2"><i class="fas fa-building-columns mr-1"></i> Manage Schools</a>
        <a href="/admin/categories/index.php" class="btn btn-outline-primary btn-block"><i class="fas fa-tags mr-1"></i> Manage Categories</a>
      </div>
      <div class="card-footer text-muted" style="font-size:.82rem">
        <?= number_format($totalPublished) ?> published &middot; <?= number_format($totalDrafts) ?> draft
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Reports by School</h3></div>
      <div class="card-body"><canvas id="deptChart" style="min-height:220px"></canvas></div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Recently Uploaded</h3>
    <div class="card-tools"><a href="/admin/reports/index.php" class="btn btn-sm btn-outline-primary">View all</a></div>
  </div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead><tr><th>Title</th><th>School</th><th>Year</th><th>Status</th><th>Uploaded</th><th></th></tr></thead>
      <tbody>
      <?php if (empty($recent)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No reports uploaded yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($recent as $r): ?>
        <tr>
          <td><?= e($r['title']) ?></td>
          <td><?= e($r['department_name'] ?? '—') ?></td>
          <td><?= e((string) $r['publication_year']) ?></td>
          <td><span class="badge badge-<?= $r['status'] === 'published' ? 'success' : 'secondary' ?>"><?= e($r['status']) ?></span></td>
          <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
          <td><a href="/admin/reports/edit.php?id=<?= (int) $r['id'] ?>" class="btn btn-xs btn-outline-primary">Edit</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('deptChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($byDept, 'name')) ?>,
    datasets: [{
      label: 'Reports',
      data: <?= json_encode(array_map('intval', array_column($byDept, 'cnt'))) ?>,
      backgroundColor: '#14213D',
      borderRadius: 3,
      maxBarThickness: 40
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});
</script>

<?php require __DIR__ . '/../../app/includes/footer_admin.php'; ?>
