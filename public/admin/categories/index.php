<?php
require_once __DIR__ . '/../../../app/includes/auth.php';
$admin = require_login();
$pdo = db();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect('/admin/categories/index.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $errors[] = 'Name is required.';
        }
        if (empty($errors)) {
            $slug = make_slug($name);
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?,?) ON CONFLICT (slug) DO NOTHING');
                $stmt->execute([$name, $slug]);
                flash_set('success', 'Category "' . $name . '" added.');
            } else {
                $id = (int) ($_POST['id'] ?? 0);
                $stmt = $pdo->prepare('UPDATE categories SET name = ?, slug = ? WHERE id = ?');
                $stmt->execute([$name, $slug, $id]);
                flash_set('success', 'Category updated.');
            }
            redirect('/admin/categories/index.php');
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        flash_set('success', 'Category removed. Reports in that category are now unassigned.');
        redirect('/admin/categories/index.php');
    }
}

$categories = $pdo->query(
    "SELECT c.*, COUNT(r.id) AS report_count
     FROM categories c LEFT JOIN reports r ON r.category_id = c.id
     GROUP BY c.id ORDER BY c.name ASC"
)->fetchAll();

$pageTitle = 'Categories';
$activeMenu = 'categories';
$breadcrumbs = [['label' => 'Categories']];
require __DIR__ . '/../../../app/includes/header_admin.php';
?>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title mb-0">Categories</h3>
    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createModal"><i class="fas fa-plus mr-1"></i> Add Category</button>
  </div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead><tr><th>Name</th><th>Slug</th><th>Reports</th><th style="width:120px">Actions</th></tr></thead>
      <tbody>
      <?php if (empty($categories)): ?>
        <tr><td colspan="4" class="text-center text-muted py-4">No categories yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($categories as $c): ?>
        <tr>
          <td><?= e($c['name']) ?></td>
          <td class="accession-code"><?= e($c['slug']) ?></td>
          <td><?= (int) $c['report_count'] ?></td>
          <td>
            <button class="btn btn-xs btn-outline-primary" data-toggle="modal" data-target="#editModal<?= (int) $c['id'] ?>"><i class="fas fa-pen"></i></button>
            <form action="/admin/categories/index.php" method="post" class="d-inline" data-confirm="Delete '<?= e($c['name']) ?>'? Reports in this category will become unassigned, not deleted.">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>

        <div class="modal fade" id="editModal<?= (int) $c['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <div class="modal-header"><h5 class="modal-title">Edit Category</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                  <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="<?= e($c['name']) ?>" required></div>
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
        <div class="modal-header"><h5 class="modal-title">Add Category</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
          <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" required placeholder="e.g. Policy Brief"></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Add Category</button></div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../../app/includes/footer_admin.php'; ?>
