<?php
$page_title = "Research Reports";
require_once __DIR__ . '/includes/admin_header.php';

// Handle quick status change / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $stmt = $pdo->prepare("SELECT file_name, cover_image FROM research_reports WHERE id = ?");
        $stmt->execute([$id]);
        $file = $stmt->fetch();
        if ($file) {
            $path = UPLOAD_DIR_REPORTS . $file['file_name'];
            if (file_exists($path)) unlink($path);
            if ($file['cover_image'] && file_exists(UPLOAD_DIR_COVERS . $file['cover_image'])) {
                unlink(UPLOAD_DIR_COVERS . $file['cover_image']);
            }
        }
        $pdo->prepare("DELETE FROM research_reports WHERE id = ?")->execute([$id]);
        flash_set('success', 'Report deleted.');
    }

    if ($action === 'status') {
        $status = $_POST['status'];
        $pdo->prepare("UPDATE research_reports SET status = ? WHERE id = ?")->execute([$status, $id]);
        flash_set('success', 'Status updated.');
    }

    header('Location: reports.php');
    exit;
}

$search = trim($_GET['q'] ?? '');
$deptFilter = (int)($_GET['department'] ?? 0);
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(r.title LIKE :s OR r.author_name LIKE :s2)";
    $params[':s'] = "%$search%";
    $params[':s2'] = "%$search%";
}
if ($deptFilter) {
    $where[] = "r.department_id = :d";
    $params[':d'] = $deptFilter;
}
if ($statusFilter) {
    $where[] = "r.status = :st";
    $params[':st'] = $statusFilter;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT r.*, d.name AS dept_name FROM research_reports r
        JOIN departments d ON d.id = r.department_id
        $whereSql ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$departments = get_departments($pdo, false);
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title">All Research Reports (<?= count($reports) ?>)</h3>
    <a href="report_add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>Upload Report</a>
  </div>
  <div class="card-body">
    <form method="GET" class="form-row mb-3">
      <div class="col-md-4 mb-2"><input type="text" name="q" class="form-control" placeholder="Search title or author..." value="<?= e($search) ?>"></div>
      <div class="col-md-3 mb-2">
        <select name="department" class="form-control">
          <option value="">All Departments</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $deptFilter == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 mb-2">
        <select name="status" class="form-control">
          <option value="">All Status</option>
          <option value="published" <?= $statusFilter==='published'?'selected':'' ?>>Published</option>
          <option value="draft" <?= $statusFilter==='draft'?'selected':'' ?>>Draft</option>
          <option value="archived" <?= $statusFilter==='archived'?'selected':'' ?>>Archived</option>
        </select>
      </div>
      <div class="col-md-2 mb-2"><button class="btn btn-outline-secondary btn-block">Filter</button></div>
    </form>
  </div>
  <div class="card-body table-responsive p-0">
    <table class="table table-hover">
      <thead>
        <tr><th>Title</th><th>Author</th><th>Department</th><th>Year</th><th>Status</th><th>Views</th><th>Downloads</th><th style="width:160px">Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($reports as $r): ?>
          <tr>
            <td><?= e($r['title']) ?></td>
            <td><?= e($r['author_name']) ?></td>
            <td><?= e($r['dept_name']) ?></td>
            <td><?= e($r['academic_year']) ?></td>
            <td>
              <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <select name="status" class="form-control form-control-sm d-inline w-auto" onchange="this.form.submit()">
                  <option value="published" <?= $r['status']==='published'?'selected':'' ?>>Published</option>
                  <option value="draft" <?= $r['status']==='draft'?'selected':'' ?>>Draft</option>
                  <option value="archived" <?= $r['status']==='archived'?'selected':'' ?>>Archived</option>
                </select>
              </form>
            </td>
            <td><?= (int)$r['views'] ?></td>
            <td><?= (int)$r['downloads'] ?></td>
            <td>
              <a href="report_edit.php?id=<?= $r['id'] ?>" class="btn btn-xs btn-outline-primary">Edit</a>
              <a href="<?= BASE_URL ?>/report.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-xs btn-outline-secondary">View</a>
              <form method="POST" class="d-inline" onsubmit="return confirm('Delete this report and its PDF file permanently?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button class="btn btn-xs btn-outline-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($reports)): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No reports found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
