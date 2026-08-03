<?php
require_once __DIR__ . '/../../../app/includes/auth.php';
$admin = require_login();
$pdo = db();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect('/admin/departments/index.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($name === '') {
            $errors[] = 'Name is required.';
        }
        if (empty($errors)) {
            $slug = make_slug($name);
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO departments (name, slug, description) VALUES (?,?,?) ON CONFLICT (slug) DO NOTHING');
                $stmt->execute([$name, $slug, $description ?: null]);
                flash_set('success', 'School "' . $name . '" added.');
            } else {
                $id = (int) ($_POST['id'] ?? 0);
                $stmt = $pdo->prepare('UPDATE departments SET name = ?, slug = ?, description = ? WHERE id = ?');
                $stmt->execute([$name, $slug, $description ?: null, $id]);
                flash_set('success', 'School updated.');
            }
            redirect('/admin/departments/index.php');
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM departments WHERE id = ?')->execute([$id]);
        flash_set('success', 'School removed. Reports in that school are now unassigned.');
        redirect('/admin/departments/index.php');
    }
}

$departments = $pdo->query(
    "SELECT d.*, COUNT(r.id) AS report_count
     FROM departments d LEFT JOIN reports r ON r.department_id = d.id
     GROUP BY d.id ORDER BY d.name ASC"
)->fetchAll();

$pageTitle = 'Schools / Departments';
$activeMenu = 'departments';
$breadcrumbs = [['label' => 'Schools / Departments']];
require __DIR__ . '/../../../app/includes/header_admin.php';
?>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title mb-0">Schools / Departments</h3>
    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createModal"><i class="fas fa-plus mr-1"></i> Add School</button>
  </div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead><tr><th>Name</th><th>Slug</th><th>Reports</th><th style="width:120px">Actions</th></tr></thead>
      <tbody>
      <?php if (empty($departments)): ?>
        <tr><td colspan="4" class="text-center text-muted py-4">No schools yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($departments as $d): ?>
        <tr>
          <td><?= e($d['name']) ?></td>
          <td class="accession-code"><?= e($d['slug']) ?></td>
          <td><?= (int) $d['report_count'] ?></td>
          <td>
            <button class="btn btn-xs btn-outline-primary" data-toggle="modal" data-target="#editModal<?= (int) $d['id'] ?>"><i class="fas fa-pen"></i></button>
            <form action="/admin/departments/index.php" method="post" class="d-inline" data-confirm="Delete '<?= e($d['name']) ?>'? Reports in this school will become unassigned, not deleted.">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
              <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>

        <div class="modal fade" id="editModal<?= (int) $d['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                <div class="modal-header"><h5 class="modal-title">Edit School</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                  <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="<?= e($d['name']) ?>" required></div>
                  <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"><?= e($d['description'] ?? '') ?></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Changes</button></div>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <div class="modal-header"><h5 class="modal-title">Add School</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
          <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" required placeholder="e.g. School of Law"></div>
          <div class="form-group"><label>Description (optional)</label><textarea name="description" class="form-control" rows="3"></textarea></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Add School</button></div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../../app/includes/footer_admin.php'; ?>
