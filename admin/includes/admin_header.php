<?php
require_once __DIR__ . '/../../config/config.php';
require_login();
$settings = get_settings($pdo);
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? e($page_title) . ' | ' : '' ?>Admin | UCZ Research Repository</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
  .brand-link, .nav-sidebar { background-color: #3f0f1f; }
  .main-sidebar { background-color: #3f0f1f !important; }
  .brand-link { border-bottom: 1px solid rgba(255,255,255,.15); }
  .btn-primary { background-color: #5c1a2e; border-color: #5c1a2e; }
  .btn-primary:hover { background-color: #3f0f1f; border-color: #3f0f1f; }
  .badge-primary { background-color: #5c1a2e; }
</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <ul class="navbar-nav">
    <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="<?= BASE_URL ?>/index.php" target="_blank" class="nav-link">View Public Site</a></li>
  </ul>
  <ul class="navbar-nav ml-auto">
    <li class="nav-item"><span class="nav-link">Hi, <?= e($_SESSION['admin_name']) ?></span></li>
    <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
  </ul>
</nav>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="dashboard.php" class="brand-link">
    <i class="fa-solid fa-book-open-reader ml-2" style="color:#d4a017"></i>
    <span class="brand-text font-weight-light ml-2">UCZ Repository</span>
  </a>
  <div class="sidebar">
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="reports.php" class="nav-link <?= in_array($current, ['reports.php','report_add.php','report_edit.php']) ? 'active' : '' ?>">
            <i class="nav-icon fas fa-file-pdf"></i><p>Research Reports</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="departments.php" class="nav-link <?= $current === 'departments.php' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-sitemap"></i><p>Departments</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="download_logs.php" class="nav-link <?= $current === 'download_logs.php' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-chart-line"></i><p>Download Logs</p>
          </a>
        </li>
        <?php if (($_SESSION['admin_role'] ?? '') === 'super_admin'): ?>
        <li class="nav-item">
          <a href="admins.php" class="nav-link <?= $current === 'admins.php' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-users-cog"></i><p>Admin Users</p>
          </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
          <a href="settings.php" class="nav-link <?= $current === 'settings.php' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-cog"></i><p>Settings</p>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</aside>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0"><?= isset($page_title) ? e($page_title) : 'Dashboard' ?></h1>
    </div>
  </div>
  <div class="content">
    <div class="container-fluid">
      <?php $flash = flash_get(); ?>
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <?= e($flash['message']) ?>
        </div>
      <?php endif; ?>
