<?php
$page_title = "Departments";
require_once __DIR__ . '/includes/admin_header.php';

// Handle add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if ($name === '') {
            flash_set('danger', 'Department name is required.');
        } else if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO departments (name, code, description, status) VALUES (?,?,?,?)");
            $stmt->execute([$name, $code, $description, $status]);
            flash_set('success', 'Department added successfully.');
        } else {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE departments SET name=?, code=?, description=?, status=? WHERE id=?");
            $stmt->execute([$name, $code, $description, $status, $id]);
            flash_set('success', 'Department updated successfully.');
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $count = $pdo->prepare("SELECT COUNT(*) FROM research_reports WHERE department_id = ?");
        $count->execute([$id]);
        if ($count->fetchColumn() > 0) {
            flash_set('danger', 'Cannot delete: this department has research reports linked to it.');
        } else {
            $pdo->prepare("DELETE FROM departments WHERE id = ?")->execute([$id]);
            flash_set('success', 'Department deleted.');
        }
    }

    header('Location: departments.php');
    exit;
}

$departments = $pdo->query("SELECT d.*, COUNT(r.id) AS report_count FROM departments d
                             LEFT JOIN research_reports r ON r.department_id = d.id
                             GROUP BY d.id ORDER BY d.name ASC")->fetchAll();
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title">All Departments</h3>
    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addModal"><i class="fas fa-plus mr-1"></i>Add Department</button>
  </div>
  <div class="card-body table-responsive p-0">
    <table class="table table-hover">
      <thead><tr><th>Name</th><th>Code</th><th>Reports</th><th>Status</th><th style="width:140px">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($departments as $d): ?>
          <tr>
            <td><?= e($d['name']) ?></td>
            <td><?= e($d['code']) ?></td>
            <td><?= $d['report_count'] ?></td>
            <td><span class="badge badge-<?= $d['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e($d['status']) ?></span></td>
            <td>
              <button class="btn btn-xs btn-outline-primary" data-toggle="modal" data-target="#editModal<?= $d['id'] ?>">Edit</button>
              <form method="POST" class="d-inline" onsubmit="return confirm('Delete this department?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                <button class="btn btn-xs btn-outline-danger">Delete</button>
              </form>
            </td>
          </tr>

          <!-- Edit Modal -->
          <div class="modal fade" id="editModal<?= $d['id'] ?>">
            <div class="modal-dialog">
              <form method="POST" class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Edit Department</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                  <input type="hidden" name="action" value="edit">
                  <input type="hidden" name="id" value="<?= $d['id'] ?>">
                  <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="<?= e($d['name']) ?>" required></div>
                  <div class="form-group"><label>Code</label><input type="text" name="code" class="form-control" value="<?= e($d['code']) ?>"></div>
                  <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"><?= e($d['description']) ?></textarea></div>
                  <div class="form-group"><label>Status</label>
                    <select name="status" class="form-control">
                      <option value="active" <?= $d['status']==='active'?'selected':'' ?>>Active</option>
                      <option value="inactive" <?= $d['status']==='inactive'?'selected':'' ?>>Inactive</option>
                    </select>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Add Department</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" required></div>
        <div class="form-group"><label>Code</label><input type="text" name="code" class="form-control" placeholder="e.g. THEO"></div>
        <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
        <div class="form-group"><label>Status</label>
          <select name="status" class="form-control">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Department</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
