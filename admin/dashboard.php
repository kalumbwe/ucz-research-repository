<?php
$page_title = "Dashboard";
require_once __DIR__ . '/includes/admin_header.php';

$totalReports   = $pdo->query("SELECT COUNT(*) FROM research_reports")->fetchColumn();
$published      = $pdo->query("SELECT COUNT(*) FROM research_reports WHERE status='published'")->fetchColumn();
$drafts         = $pdo->query("SELECT COUNT(*) FROM research_reports WHERE status='draft'")->fetchColumn();
$totalDownloads = $pdo->query("SELECT SUM(downloads) FROM research_reports")->fetchColumn() ?: 0;
$totalDepts     = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();

$byDept = $pdo->query("SELECT d.name, COUNT(r.id) AS total FROM departments d
                        LEFT JOIN research_reports r ON r.department_id = d.id
                        GROUP BY d.id ORDER BY total DESC")->fetchAll();

$recentReports = $pdo->query("SELECT r.*, d.name AS dept_name FROM research_reports r
                               JOIN departments d ON d.id = r.department_id
                               ORDER BY r.created_at DESC LIMIT 8")->fetchAll();
?>

<div class="row">
  <div class="col-lg-3 col-6">
    <div class="small-box bg-info">
      <div class="inner"><h3><?= $totalReports ?></h3><p>Total Reports</p></div>
      <div class="icon"><i class="fas fa-file-pdf"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-success">
      <div class="inner"><h3><?= $published ?></h3><p>Published</p></div>
      <div class="icon"><i class="fas fa-check-circle"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-warning">
      <div class="inner"><h3><?= $drafts ?></h3><p>Drafts</p></div>
      <div class="icon"><i class="fas fa-pen"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-danger">
      <div class="inner"><h3><?= $totalDownloads ?></h3><p>Total Downloads</p></div>
      <div class="icon"><i class="fas fa-download"></i></div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Reports by Department</h3></div>
      <div class="card-body">
        <table class="table table-sm">
          <thead><tr><th>Department</th><th class="text-right">Reports</th></tr></thead>
          <tbody>
            <?php foreach ($byDept as $d): ?>
              <tr><td><?= e($d['name']) ?></td><td class="text-right"><?= $d['total'] ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Quick Actions</h3></div>
      <div class="card-body">
        <a href="report_add.php" class="btn btn-primary btn-block mb-2"><i class="fas fa-plus mr-1"></i>Upload New Research Report</a>
        <a href="departments.php" class="btn btn-outline-secondary btn-block mb-2"><i class="fas fa-sitemap mr-1"></i>Manage Departments</a>
        <a href="reports.php" class="btn btn-outline-secondary btn-block"><i class="fas fa-list mr-1"></i>View All Reports</a>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3 class="card-title">Recently Added Reports</h3></div>
  <div class="card-body table-responsive p-0">
    <table class="table table-hover">
      <thead><tr><th>Title</th><th>Department</th><th>Status</th><th>Uploaded</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($recentReports as $r): ?>
          <tr>
            <td><?= e($r['title']) ?></td>
            <td><?= e($r['dept_name']) ?></td>
            <td><span class="badge badge-<?= $r['status'] === 'published' ? 'success' : ($r['status'] === 'draft' ? 'warning' : 'secondary') ?>"><?= e($r['status']) ?></span></td>
            <td><?= format_date($r['created_at']) ?></td>
            <td><a href="report_edit.php?id=<?= $r['id'] ?>" class="btn btn-xs btn-outline-primary">Edit</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
