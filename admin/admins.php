<?php
$page_title = "Admin Users";
require_once __DIR__ . '/includes/admin_header.php';

if (($_SESSION['admin_role'] ?? '') !== 'super_admin') {
    flash_set('danger', 'Only super admins can manage admin users.');
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $full_name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'admin';

        if ($full_name === '' || $username === '' || $email === '' || $password === '') {
            flash_set('danger', 'All fields are required to add an admin.');
        } elseif (strlen($password) < 8) {
            flash_set('danger', 'Password must be at least 8 characters.');
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            try {
                $stmt = $pdo->prepare("INSERT INTO admins (full_name, username, email, password, role) VALUES (?,?,?,?,?)");
                $stmt->execute([$full_name, $username, $email, $hash, $role]);
                flash_set('success', 'Admin user created.');
            } catch (PDOException $e) {
                flash_set('danger', 'Username or email already exists.');
            }
        }
    }

    if ($action === 'toggle_status') {
        $id = (int)$_POST['id'];
        if ($id !== (int)$_SESSION['admin_id']) {
            $stmt = $pdo->prepare("SELECT status FROM admins WHERE id = ?");
            $stmt->execute([$id]);
            $current = $stmt->fetchColumn();
            $new = $current === 'active' ? 'disabled' : 'active';
            $pdo->prepare("UPDATE admins SET status = ? WHERE id = ?")->execute([$new, $id]);
            flash_set('success', 'Admin status updated.');
        } else {
            flash_set('danger', 'You cannot disable your own account.');
        }
    }

    header('Location: admins.php');
    exit;
}

$admins = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll();
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title">Admin Users</h3>
    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addAdminModal"><i class="fas fa-plus mr-1"></i>Add Admin</button>
  </div>
  <div class="card-body table-responsive p-0">
    <table class="table table-hover">
      <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($admins as $a): ?>
          <tr>
            <td><?= e($a['full_name']) ?></td>
            <td><?= e($a['username']) ?></td>
            <td><?= e($a['email']) ?></td>
            <td><span class="badge badge-info"><?= e($a['role']) ?></span></td>
            <td><span class="badge badge-<?= $a['status']==='active'?'success':'secondary' ?>"><?= e($a['status']) ?></span></td>
            <td><?= $a['last_login'] ? date('d M Y, H:i', strtotime($a['last_login'])) : 'Never' ?></td>
            <td>
              <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                <button class="btn btn-xs btn-outline-secondary" <?= $a['id'] == $_SESSION['admin_id'] ? 'disabled' : '' ?>>
                  <?= $a['status']==='active' ? 'Disable' : 'Enable' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="addAdminModal">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Add Admin User</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="form-group"><label>Full Name</label><input type="text" name="full_name" class="form-control" required></div>
        <div class="form-group"><label>Username</label><input type="text" name="username" class="form-control" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" minlength="8" required></div>
        <div class="form-group"><label>Role</label>
          <select name="role" class="form-control">
            <option value="admin">Admin</option>
            <option value="super_admin">Super Admin</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Admin</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
