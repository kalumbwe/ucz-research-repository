<?php
require_once __DIR__ . '/../../../app/includes/auth.php';
$admin = require_role('super_admin');
$pdo = db();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect('/admin/users/index.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = ($_POST['role'] ?? 'editor') === 'super_admin' ? 'super_admin' : 'editor';

        if ($fullName === '') $errors[] = 'Full name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

        if (empty($errors)) {
            $exists = $pdo->prepare('SELECT id FROM admin_users WHERE email = ?');
            $exists->execute([$email]);
            if ($exists->fetch()) {
                $errors[] = 'An account with that email already exists.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO admin_users (full_name, email, password_hash, role) VALUES (?,?,?,?)');
                $stmt->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                flash_set('success', 'Admin account for ' . $fullName . ' created.');
                redirect('/admin/users/index.php');
            }
        }
    } elseif ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = ($_POST['role'] ?? 'editor') === 'super_admin' ? 'super_admin' : 'editor';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $newPassword = $_POST['password'] ?? '';

        if ($fullName === '') $errors[] = 'Full name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';

        // Guard: don't allow demoting/deactivating the last super_admin.
        if (empty($errors) && ($role !== 'super_admin' || !$isActive)) {
            $superAdminCount = (int) $pdo->query("SELECT COUNT(*) c FROM admin_users WHERE role='super_admin' AND is_active = TRUE")->fetch()['c'];
            $current = $pdo->prepare('SELECT role, is_active FROM admin_users WHERE id = ?');
            $current->execute([$id]);
            $currentRow = $current->fetch();
            if ($currentRow && $currentRow['role'] === 'super_admin' && $currentRow['is_active'] && $superAdminCount <= 1) {
                $errors[] = 'You cannot remove the last active super admin.';
            }
        }

        if (empty($errors)) {
            if ($newPassword !== '') {
                if (strlen($newPassword) < 8) {
                    $errors[] = 'New password must be at least 8 characters.';
                } else {
                    $stmt = $pdo->prepare('UPDATE admin_users SET full_name=?, email=?, role=?, is_active=?, password_hash=?, updated_at=NOW() WHERE id=?');
                    $stmt->execute([$fullName, $email, $role, $isActive, password_hash($newPassword, PASSWORD_DEFAULT), $id]);
                }
            } else {
                $stmt = $pdo->prepare('UPDATE admin_users SET full_name=?, email=?, role=?, is_active=?, updated_at=NOW() WHERE id=?');
                $stmt->execute([$fullName, $email, $role, $isActive, $id]);
            }
            if (empty($errors)) {
                flash_set('success', 'Account updated.');
                redirect('/admin/users/index.php');
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) $admin['id']) {
            flash_set('error', 'You cannot delete your own account while signed in.');
        } else {
            $current = $pdo->prepare('SELECT role FROM admin_users WHERE id = ?');
            $current->execute([$id]);
            $row = $current->fetch();
            $superAdminCount = (int) $pdo->query("SELECT COUNT(*) c FROM admin_users WHERE role='super_admin'")->fetch()['c'];
            if ($row && $row['role'] === 'super_admin' && $superAdminCount <= 1) {
                flash_set('error', 'You cannot delete the last super admin.');
            } else {
                $pdo->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$id]);
                flash_set('success', 'Account deleted.');
            }
        }
        redirect('/admin/users/index.php');
    }
}

$users = $pdo->query('SELECT * FROM admin_users ORDER BY created_at ASC')->fetchAll();

$pageTitle = 'Admin Users';
$activeMenu = 'users';
$breadcrumbs = [['label' => 'Admin Users']];
require __DIR__ . '/../../../app/includes/header_admin.php';
?>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title mb-0">Admin Users</h3>
    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createModal"><i class="fas fa-user-plus mr-1"></i> Add Admin</button>
  </div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th style="width:120px">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= e($u['full_name']) ?> <?= $u['id'] == $admin['id'] ? '<span class="badge badge-info ml-1">You</span>' : '' ?></td>
          <td><?= e($u['email']) ?></td>
          <td><span class="badge badge-<?= $u['role'] === 'super_admin' ? 'warning' : 'secondary' ?>"><?= e(str_replace('_', ' ', $u['role'])) ?></span></td>
          <td><span class="badge badge-<?= $u['is_active'] ? 'success' : 'secondary' ?>"><?= $u['is_active'] ? 'Active' : 'Disabled' ?></span></td>
          <td><?= $u['last_login_at'] ? date('d M Y, H:i', strtotime($u['last_login_at'])) : '—' ?></td>
          <td>
            <button class="btn btn-xs btn-outline-primary" data-toggle="modal" data-target="#editModal<?= (int) $u['id'] ?>"><i class="fas fa-pen"></i></button>
            <?php if ($u['id'] != $admin['id']): ?>
              <form action="/admin/users/index.php" method="post" class="d-inline" data-confirm="Delete the account for <?= e($u['full_name']) ?>?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>

        <div class="modal fade" id="editModal<?= (int) $u['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                <div class="modal-header"><h5 class="modal-title">Edit Admin</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                  <div class="form-group"><label>Full name</label><input type="text" name="full_name" class="form-control" value="<?= e($u['full_name']) ?>" required></div>
                  <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= e($u['email']) ?>" required></div>
                  <div class="form-group"><label>Role</label>
                    <select name="role" class="form-control">
                      <option value="editor" <?= $u['role'] === 'editor' ? 'selected' : '' ?>>Editor (manage reports)</option>
                      <option value="super_admin" <?= $u['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin (full access)</option>
                    </select>
                  </div>
                  <div class="form-group form-check">
                    <input type="checkbox" name="is_active" id="active<?= (int) $u['id'] ?>" class="form-check-input" <?= $u['is_active'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="active<?= (int) $u['id'] ?>">Account active</label>
                  </div>
                  <div class="form-group"><label>Reset password (optional)</label><input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password"></div>
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
        <div class="modal-header"><h5 class="modal-title">Add Admin</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
          <div class="form-group"><label>Full name</label><input type="text" name="full_name" class="form-control" required></div>
          <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
          <div class="form-group"><label>Role</label>
            <select name="role" class="form-control">
              <option value="editor">Editor (manage reports)</option>
              <option value="super_admin">Super Admin (full access)</option>
            </select>
          </div>
          <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" minlength="8" required></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Add Admin</button></div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../../app/includes/footer_admin.php'; ?>
