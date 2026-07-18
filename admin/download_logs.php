<?php
$page_title = "Download Logs";
require_once __DIR__ . '/includes/admin_header.php';

$logs = $pdo->query("SELECT l.*, r.title FROM download_logs l
                      JOIN research_reports r ON r.id = l.report_id
                      ORDER BY l.downloaded_at DESC LIMIT 300")->fetchAll();

$topDownloaded = $pdo->query("SELECT title, downloads FROM research_reports
                               ORDER BY downloads DESC LIMIT 10")->fetchAll();
?>

<div class="row">
  <div class="col-md-5">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Most Downloaded Reports</h3></div>
      <div class="card-body table-responsive p-0">
        <table class="table table-sm">
          <thead><tr><th>Title</th><th class="text-right">Downloads</th></tr></thead>
          <tbody>
            <?php foreach ($topDownloaded as $t): ?>
              <tr><td><?= e($t['title']) ?></td><td class="text-right"><?= $t['downloads'] ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Recent Download Activity</h3></div>
      <div class="card-body table-responsive p-0" style="max-height:500px; overflow-y:auto;">
        <table class="table table-sm">
          <thead><tr><th>Report</th><th>IP Address</th><th>Date/Time</th></tr></thead>
          <tbody>
            <?php foreach ($logs as $l): ?>
              <tr>
                <td><?= e($l['title']) ?></td>
                <td><?= e($l['ip_address']) ?></td>
                <td><?= date('d M Y, H:i', strtotime($l['downloaded_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
              <tr><td colspan="3" class="text-center text-muted py-3">No downloads recorded yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
