<?php
require_once __DIR__ . '/../app/includes/functions.php';

$lockFile = APP_ROOT . '/storage/installed.lock';
$errors = [];
$success = false;

$alreadyLocked = file_exists($lockFile);
$adminCount = 0;
if (is_installed()) {
    try {
        $adminCount = (int) db()->query('SELECT COUNT(*) AS c FROM admin_users')->fetch()['c'];
    } catch (Throwable $e) {
        $adminCount = 0;
    }
}
$canInstall = !$alreadyLocked && $adminCount === 0;

if ($canInstall && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    }

    $key = $_POST['install_key'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (INSTALL_KEY === '' || INSTALL_KEY === 'change-this-before-you-deploy') {
        $errors[] = 'INSTALL_KEY is not set to a private value yet. Set it as an environment variable on Render (or in .env locally) before installing.';
    } elseif (!hash_equals(INSTALL_KEY, $key)) {
        $errors[] = 'The install key you entered does not match.';
    }
    if ($fullName === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Password and confirmation do not match.';

    if (empty($errors)) {
        try {
            $pdo = db();
            $schema = file_get_contents(APP_ROOT . '/database/schema.sql');
            $pdo->exec($schema);

            $stmt = $pdo->prepare('INSERT INTO admin_users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT), 'super_admin']);

            @file_put_contents($lockFile, 'Installed at ' . date('c') . "\n");

            $success = true;
        } catch (Throwable $e) {
            $errors[] = 'Installation failed: ' . (APP_ENV !== 'production' ? $e->getMessage() : 'please check your database configuration and try again.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Install — <?= e(APP_NAME) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 64 64%27%3E%3Ccircle cx=%2732%27 cy=%2732%27 r=%2730%27 fill=%27%2314213D%27/%3E%3Ccircle cx=%2732%27 cy=%2732%27 r=%2729%27 fill=%27none%27 stroke=%27%23B8892B%27 stroke-width=%272%27/%3E%3Ctext x=%2732%27 y=%2743%27 font-family=%27Georgia,serif%27 font-size=%2726%27 font-weight=%27700%27 fill=%27%23B8892B%27 text-anchor=%27middle%27%3EU%3C/text%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<style>
  :root{--ink:#14213D;--gold:#B8892B;--paper:#EEF0F2;--charcoal:#1B1F27;}
  *{box-sizing:border-box}
  body{margin:0;font-family:'Inter',sans-serif;background:var(--paper);color:var(--charcoal);display:flex;min-height:100vh;align-items:center;justify-content:center;padding:24px}
  .card{background:#fff;max-width:520px;width:100%;border:1px solid #d9dce1;border-radius:6px;padding:36px 40px;box-shadow:0 1px 3px rgba(20,33,61,.08)}
  h1{font-family:'Fraunces',serif;font-weight:600;font-size:1.5rem;margin:0 0 4px;color:var(--ink)}
  .tag{font-family:'IBM Plex Mono',monospace;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:var(--gold);margin-bottom:20px;display:block}
  label{display:block;font-size:.85rem;font-weight:600;margin:14px 0 4px}
  input{width:100%;padding:10px 12px;border:1px solid #c7cbd1;border-radius:4px;font-size:.95rem;font-family:inherit}
  input:focus{outline:2px solid var(--ink);outline-offset:1px}
  button{margin-top:22px;width:100%;padding:12px;background:var(--ink);color:#fff;border:none;border-radius:4px;font-weight:600;font-size:.95rem;cursor:pointer}
  button:hover{background:#1c2c52}
  .err{background:#fbeaea;border:1px solid #e3b8b8;color:#8a2f2f;padding:10px 14px;border-radius:4px;font-size:.85rem;margin-top:16px}
  .ok{background:#eaf3ec;border:1px solid #b9d8bf;color:#2f5d3a;padding:14px;border-radius:4px;font-size:.9rem}
  .hint{font-size:.78rem;color:#666;margin-top:6px}
  a{color:var(--ink)}
</style>
</head>
<body>
<div class="card">
<?php if ($success): ?>
  <h1>Installation complete</h1>
  <p class="ok">The database schema was applied and your super admin account was created. For security, this page will now refuse to install again.</p>
  <p><a href="/admin/login.php">&rarr; Go to admin login</a> &nbsp;|&nbsp; <a href="/">View public site</a></p>
<?php elseif (!$canInstall): ?>
  <h1>Already installed</h1>
  <p>An admin account already exists, so this installer is locked. If you need to reset access, do so from the database directly or contact whoever manages this deployment.</p>
  <p><a href="/admin/login.php">&rarr; Go to admin login</a></p>
<?php else: ?>
  <span class="tag">First-time setup</span>
  <h1><?= e(APP_NAME) ?></h1>
  <p style="margin-top:0;color:#555;font-size:.92rem">This applies the database schema and creates your first administrator account. It can only be run once.</p>
  <?php foreach ($errors as $err): ?>
    <div class="err"><?= e($err) ?></div>
  <?php endforeach; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <label for="install_key">Install key</label>
    <input type="password" id="install_key" name="install_key" required>
    <div class="hint">The INSTALL_KEY value you set as an environment variable.</div>

    <label for="full_name">Full name</label>
    <input type="text" id="full_name" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required>

    <label for="email">Email address</label>
    <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" minlength="8" required>

    <label for="confirm_password">Confirm password</label>
    <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>

    <button type="submit">Install &amp; create admin account</button>
  </form>
<?php endif; ?>
</div>
</body>
</html>
