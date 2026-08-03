<?php
require_once __DIR__ . '/../../app/includes/auth.php';

start_admin_session();
if (!empty($_SESSION['admin'])) {
    redirect('/admin/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_installed()) {
        $errors[] = 'The application has not been installed yet.';
    } elseif (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = db()->prepare('SELECT * FROM admin_users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin || !$admin['is_active'] || !password_verify($password, $admin['password_hash'])) {
            $errors[] = 'Incorrect email or password.';
        } else {
            login_admin($admin);
            db()->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?')->execute([$admin['id']]);
            redirect('/admin/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Admin Login &middot; <?= e(APP_NAME) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 64 64%27%3E%3Ccircle cx=%2732%27 cy=%2732%27 r=%2730%27 fill=%27%2314213D%27/%3E%3Ccircle cx=%2732%27 cy=%2732%27 r=%2729%27 fill=%27none%27 stroke=%27%23B8892B%27 stroke-width=%272%27/%3E%3Ctext x=%2732%27 y=%2743%27 font-family=%27Georgia,serif%27 font-size=%2726%27 font-weight=%27700%27 fill=%27%23B8892B%27 text-anchor=%27middle%27%3EU%3C/text%3E%3C/svg%3E">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
<link rel="stylesheet" href="/assets/css/admin-custom.css">
<style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo">
    <b>UCZ</b> Research Repository
    <small>Administrator Access</small>
  </div>
  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Sign in to manage research reports</p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger py-2"><?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="input-group mb-3">
          <input type="email" name="email" class="form-control" placeholder="Email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
          <div class="input-group-append"><div class="input-group-text"><span class="fas fa-envelope"></span></div></div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Password" required>
          <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
      </form>

      <p class="mt-3 mb-0 text-center">
        <a href="/" class="text-muted" style="font-size:.85rem">&larr; Back to public site</a>
      </p>
    </div>
  </div>
</div>
</body>
</html>
