<?php
require_once __DIR__ . '/../../app/includes/auth.php';
$admin = require_login();
$pdo = db();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($fullName === '') $errors[] = 'Full name is required.';

        $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE id = ?');
        $stmt->execute([$admin['id']]);
        $row = $stmt->fetch();

        $wantsPasswordChange = $newPassword !== '' || $confirmPassword !== '';
        if ($wantsPasswordChange) {
            if (!password_verify($currentPassword, $row['password_hash'])) {
                $errors[] = 'Current password is incorrect.';
            }
            if (strlen($newPassword) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            }
            if ($newPassword !== $confirmPassword) {
                $errors[] = 'New password and confirmation do not match.';
            }
        }

        if (empty($errors)) {
            if ($wantsPasswordChange) {
                $pdo->prepare('UPDATE admin_users SET full_name = ?, password_hash = ?, updated_at = NOW() WHERE id = ?')
                    ->execute([$fullName, password_hash($newPassword, PASSWORD_DEFAULT), $admin['id']]);
            } else {
                $pdo->prepare('UPDATE admin_users SET full_name = ?, updated_at = NOW() WHERE id = ?')
                    ->execute([$fullName, $admin['id']]);
            }
            $_SESSION['admin']['full_name'] = $fullName;
            flash_set('success', 'Your account was updated.');
            redirect('/admin/profile.php');
        }
    }
}

$stmt = $pdo->prepare('SELECT * FROM admin_users WHERE id = ?');
$stmt->execute([$admin['id']]);
$me = $stmt->fetch();

$pageTitle = 'My Account';
$activeMenu = 'profile';
$breadcrumbs = [['label' => 'My Account']];
require __DIR__ . '/../../app/includes/header_admin.php';
?>

<div class="row">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Account Settings</h3></div>
      <div class="card-body">
        <?php foreach ($errors as $err): ?>
          <div class="alert alert-danger py-2"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="post">
          <?= csrf_field() ?>
          <div class="form-group">
            <label>Full name</label>
            <input type="text" name="full_name" class="form-control" value="<?= e($me['full_name']) ?>" required>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control" value="<?= e($me['email']) ?>" disabled>
            <small class="form-text text-muted">Contact a super admin to change your email address.</small>
          </div>

          <hr>
          <h6 class="text-uppercase text-muted" style="font-size:.78rem;letter-spacing:.05em">Change Password (optional)</h6>
          <div class="form-group">
            <label>Current password</label>
            <input type="password" name="current_password" class="form-control">
          </div>
          <div class="form-group">
            <label>New password</label>
            <input type="password" name="new_password" class="form-control" minlength="8">
          </div>
          <div class="form-group">
            <label>Confirm new password</label>
            <input type="password" name="confirm_password" class="form-control" minlength="8">
          </div>

          <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer_admin.php'; ?>
