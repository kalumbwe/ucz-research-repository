<?php
require_once __DIR__ . '/../../../app/includes/auth.php';
$admin = require_login();
$pdo = db();

$q = trim($_GET['q'] ?? '');
$departmentId = trim($_GET['department_id'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(r.title ILIKE ? OR r.authors ILIKE ?)';
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}
if ($departmentId !== '' && ctype_digit($departmentId)) {
    $where[] = 'r.department_id = ?';
    $params[] = (int) $departmentId;
}
if (in_array($status, ['published', 'draft'], true)) {
    $where[] = 'r.status = ?';
    $params[] = $status;
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) c FROM reports r WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['c'];

$perPage = 15;
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

$pageTitle = 'Research Reports';
$activeMenu = 'reports';
$breadcrumbs = [['label' => 'Reports']];
require __DIR__ . '/../../../app/includes/header_admin.php';
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
    <h3 class="card-title mb-0">All Reports (<?= number_format($total) ?>)</h3>
    <a href="/admin/reports/create.php" class="btn btn-primary btn-sm"><i class="fas fa-upload mr-1"></i> Upload New Report</a>
  </div>
  <div class="card-body border-bottom">
    <form method="get" class="form-row align-items-end">
      <div class="col-md-4 mb-2">
        <label class="small text-muted mb-1">Search</label>
        <input type="text" name="q" class="form-control form-control-sm" value="<?= e($q) ?>" placeholder="Title or author">
      </div>
      <div class="col-md-3 mb-2">
        <label class="small text-muted mb-1">School</label>
        <select name="department_id" class="form-control form-control-sm">
          <option value="">All Schools</option>
          <?php foreach (all_departments() as $d): ?>
            <option value="<?= (int) $d['id'] ?>" <?= $departmentId == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 mb-2">
        <label class="small text-muted mb-1">Status</label>
        <select name="status" class="form-control form-control-sm">
          <option value="">Any</option>
          <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
          <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
        </select>
      </div>
      <div class="col-md-3 mb-2">
        <button type="submit" class="btn btn-outline-primary btn-sm">Filter</button>
        <a href="/admin/reports/index.php" class="btn btn-outline-secondary btn-sm">Clear</a>
      </div>
    </form>
  </div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr><th>Acc.</th><th>Title</th><th>School</th><th>Type</th><th>Year</th><th>Status</th><th>Downloads</th><th style="width:150px">Actions</th></tr>
      </thead>
      <tbody>
      <?php if (empty($reports)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No reports match your filters.</td></tr>
      <?php endif; ?>
      <?php foreach ($reports as $r): ?>
        <tr>
          <td class="accession-code">UCZ-<?= str_pad((string) $r['id'], 4, '0', STR_PAD_LEFT) ?></td>
          <td><?= e($r['title']) ?></td>
          <td><?= e($r['department_name'] ?? '—') ?></td>
          <td><?= e($r['category_name'] ?? '—') ?></td>
          <td><?= e((string) $r['publication_year']) ?></td>
          <td><span class="badge badge-<?= $r['status'] === 'published' ? 'success' : 'secondary' ?>"><?= e($r['status']) ?></span></td>
          <td><?= number_format((int) $r['downloads_count']) ?></td>
          <td>
            <a href="/admin/reports/view.php?id=<?= (int) $r['id'] ?>" class="btn btn-xs btn-outline-secondary" title="View"><i class="fas fa-eye"></i></a>
            <a href="/admin/reports/edit.php?id=<?= (int) $r['id'] ?>" class="btn btn-xs btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></a>
            <?php $confirmMsg = 'Delete "' . $r['title'] . '"? This also removes the uploaded PDF and cannot be undone.'; ?>
            <form action="/admin/reports/delete.php" method="post" class="d-inline" data-confirm="<?= e($confirmMsg) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
  <div class="card-footer">
    <nav>
      <ul class="pagination pagination-sm mb-0 justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= e(query_string_with(['page' => $i])) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../../app/includes/footer_admin.php'; ?>
