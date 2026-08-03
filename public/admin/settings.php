<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/includes/database.php';

 $admin = require_login();

// ---- Settings functions (inline, no separate file) ----

function ensureSettingsTable() {
    db()->exec("CREATE TABLE IF NOT EXISTS site_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT NOT NULL DEFAULT '', updated_at TIMESTAMP NOT NULL DEFAULT NOW())");
}

function seedDefaultSettings() {
    $count = (int) db()->query("SELECT COUNT(*) FROM site_settings")->fetchColumn();
    if ($count > 0) return;
    $year = date('Y');
    $d = array(
        'site_name' => 'UCZ University Research Repository',
        'site_tagline' => 'A digital archive for research reports, theses, dissertations and scholarly papers',
        'contact_email' => 'research@ucz.ac.zm',
        'contact_phone' => '+260 211 XXX XXX',
        'contact_address' => 'University of Zambia, Lusaka, Zambia',
        'footer_text' => $year . ' UCZ University. All rights reserved.'
    );
    $s = db()->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v) ON CONFLICT (setting_key) DO NOTHING");
    foreach ($d as $k => $v) { $s->execute(array(':k' => $k, ':v' => $v)); }
}

function getAllSettings() {
    ensureSettingsTable();
    seedDefaultSettings();
    $rows = db()->query("SELECT setting_key, setting_value FROM site_settings ORDER BY setting_key")->fetchAll();
    $r = array();
    foreach ($rows as $row) { $r[$row['setting_key']] = $row['setting_value']; }
    return $r;
}

function saveSettings($pairs) {
    ensureSettingsTable();
    $s = db()->prepare("INSERT INTO site_settings (setting_key, setting_value, updated_at) VALUES (:k, :v, NOW()) ON CONFLICT (setting_key) DO UPDATE SET setting_value = :v2, updated_at = NOW()");
    foreach ($pairs as $k => $v) { $s->execute(array(':k' => $k, ':v' => $v, ':v2' => $v)); }
}

// ---- Handle form submit ----

 $success = false;
 $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        try {
            $fields = array('site_name', 'site_tagline', 'contact_email', 'contact_phone', 'contact_address', 'footer_text');
            $pairs = array();
            foreach ($fields as $f) { $pairs[$f] = trim($_POST[$f] ?? ''); }
            saveSettings($pairs);
            $success = true;
        } catch (Exception $e) {
            $error = 'Error: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// CSRF token
if (session_status() === PHP_SESSION_NONE) { start_admin_session(); }
 $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
 $csrf = $_SESSION['csrf_token'];

// Load settings
 $settings = getAllSettings();
 $sn = htmlspecialchars($settings['site_name'] ?? '');
 $st = htmlspecialchars($settings['site_tagline'] ?? '');
 $ce = htmlspecialchars($settings['contact_email'] ?? '');
 $cp = htmlspecialchars($settings['contact_phone'] ?? '');
 $ca = htmlspecialchars($settings['contact_address'] ?? '');
 $ft = htmlspecialchars($settings['footer_text'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
            <li class="nav-item d-none d-sm-inline-block"><span class="nav-link font-weight-bold">Site Settings</span></li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a class="nav-link" href="/admin/dashboard.php"><i class="fas fa-arrow-left mr-1"></i> Dashboard</a></li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="/admin/dashboard.php" class="brand-link"><span class="brand-text font-weight-light">UCZ Research Admin</span></a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" role="menu">
                    <li class="nav-item"><a href="/admin/dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p></a></li>
                    <li class="nav-item"><a href="/admin/reports.php" class="nav-link"><i class="nav-icon fas fa-file-alt"></i><p>Research Reports</p></a></li>
                    <li class="nav-item"><a href="/admin/departments.php" class="nav-link"><i class="nav-icon fas fa-building"></i><p>Schools / Departments</p></a></li>
                    <li class="nav-item"><a href="/admin/categories.php" class="nav-link"><i class="nav-icon fas fa-tags"></i><p>Categories</p></a></li>
                    <li class="nav-item active"><a href="/admin/settings.php" class="nav-link"><i class="nav-icon fas fa-cog"></i><p>Site Settings</p></a></li>
                    <?php if ($admin['role'] === 'super_admin'): ?>
                    <li class="nav-item"><a href="/admin/users.php" class="nav-link"><i class="nav-icon fas fa-users-cog"></i><p>Admin Users</p></a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a href="/admin/logout.php" class="nav-link"><i class="nav-icon fas fa-sign-out-alt"></i><p>Logout</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6"><h1>Site Settings</h1></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Site Settings</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">

                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle mr-2"></i>Settings saved successfully!
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle mr-2"></i><?= $error ?>
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-globe mr-2"></i>General Information</h3>
                            </div>
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <div class="card-body">

                                    <div class="form-group">
                                        <label>Site Name</label>
                                        <input type="text" name="site_name" class="form-control" value="<?= $sn ?>" required>
                                        <small class="text-muted">Displayed in the browser tab and page headings.</small>
                                    </div>

                                    <div class="form-group">
                                        <label>Site Tagline</label>
                                        <textarea name="site_tagline" class="form-control" rows="3"><?= $st ?></textarea>
                                        <small class="text-muted">Shown below the site name on the homepage.</small>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3"><i class="fas fa-address-card mr-2"></i>Contact Information</h5>

                                    <div class="form-group">
                                        <label>Contact Email</label>
                                        <input type="email" name="contact_email" class="form-control" value="<?= $ce ?>">
                                    </div>

                                    <div class="form-group">
                                        <label>Contact Phone</label>
                                        <input type="text" name="contact_phone" class="form-control" value="<?= $cp ?>">
                                    </div>

                                    <div class="form-group">
                                        <label>Contact Address</label>
                                        <textarea name="contact_address" class="form-control" rows="2"><?= $ca ?></textarea>
                                    </div>

                                    <hr>

                                    <div class="form-group">
                                        <label>Footer Text</label>
                                        <input type="text" name="footer_text" class="form-control" value="<?= $ft ?>">
                                        <small class="text-muted">Displayed at the bottom of every public page.</small>
                                    </div>

                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Save Settings</button>
                                    <a href="/admin/dashboard.php" class="btn btn-default ml-2">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card card-info">
                            <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Quick Info</h3></div>
                            <div class="card-body">
                                <p>Edit your site public information without code.</p>
                                <ul>
                                    <li><strong>Site Name</strong> — tab and headings</li>
                                    <li><strong>Tagline</strong> — homepage</li>
                                    <li><strong>Contact info</strong> — about and footer</li>
                                    <li><strong>Footer text</strong> — every page</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer"><strong>UCZ Research Admin</strong></footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>