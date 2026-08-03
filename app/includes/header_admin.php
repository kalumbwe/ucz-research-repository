<?php
/**
 * Expects (optional) before include:
 *   $pageTitle    string
 *   $activeMenu   string  — one of: dashboard, reports, departments, categories, users, profile
 *   $breadcrumbs  array   — [['label' => 'Reports', 'url' => '/admin/reports/index.php'], ['label' => 'Edit']]
 */
require_once __DIR__ . '/auth.php';
$admin = require_login();

$pageTitle = $pageTitle ?? 'Admin';
$activeMenu = $activeMenu ?? '';
$breadcrumbs = $breadcrumbs ?? [];
$flash = flash_get();

function nav_active(string $key, string $active): string
{
    return $key === $active ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($pageTitle) ?> · Admin · <?= e(APP_NAME) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 64 64%27%3E%3Ccircle cx=%2732%27 cy=%2732%27 r=%2730%27 fill=%27%2314213D%27/%3E%3Ccircle cx=%2732%27 cy=%2732%27 r=%2729%27 fill=%27none%27 stroke=%27%23B8892B%27 stroke-width=%272%27/%3E%3Ctext x=%2732%27 y=%2743%27 font-family=%27Georgia,serif%27 font-size=%2726%27 font-weight=%27700%27 fill=%27%23B8892B%27 text-anchor=%27middle%27%3EU%3C/text%3E%3C/svg%3E">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
<link rel="stylesheet" href="/assets/css/admin-custom.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li>
      <li class="nav-item d-none d-sm-inline-block"><a href="/" class="nav-link" target="_blank">View public site</a></li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-user-circle mr-1"></i><?= e($admin['full_name']) ?>
        </a>
        <div class="dropdown-menu dropdown-menu-right">
          <a href="/admin/profile.php" class="dropdown-item"><i class="fas fa-key mr-2"></i> Change password</a>
          <div class="dropdown-divider"></div>
          <a href="/admin/logout.php" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
        </div>
      </li>
    </ul>
  </nav>

  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="/admin/dashboard.php" class="brand-link">
      <span class="brand-text font-weight-light ml-2"><b>UCZ</b> Repository</span>
    </a>
    <div class="sidebar">
      <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
        <div class="info">
          <span class="d-block text-white"><?= e($admin['full_name']) ?></span>
          <span class="badge badge-warning" style="text-transform:uppercase;letter-spacing:.03em;font-size:.65rem"><?= e(str_replace('_', ' ', $admin['role'])) ?></span>
        </div>
      </div>
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
          <li class="nav-item">
            <a href="/admin/dashboard.php" class="nav-link <?= nav_active('dashboard', $activeMenu) ?>">
              <i class="nav-icon fas fa-gauge-high"></i><p>Dashboard</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="/admin/reports/index.php" class="nav-link <?= nav_active('reports', $activeMenu) ?>">
              <i class="nav-icon fas fa-file-pdf"></i><p>Research Reports</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="/admin/departments/index.php" class="nav-link <?= nav_active('departments', $activeMenu) ?>">
              <i class="nav-icon fas fa-building-columns"></i><p>Schools / Departments</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="/admin/categories/index.php" class="nav-link <?= nav_active('categories', $activeMenu) ?>">
              <i class="nav-icon fas fa-tags"></i><p>Categories</p>
            </a>
          </li>
          <?php if ($admin['role'] === 'super_admin'): ?>
          <li class="nav-item">
            <a href="/admin/users/index.php" class="nav-link <?= nav_active('users', $activeMenu) ?>">
              <i class="nav-icon fas fa-user-shield"></i><p>Admin Users</p>
            </a>
          </li>
          <?php endif; ?>
          <li class="nav-item">
            <a href="/admin/profile.php" class="nav-link <?= nav_active('profile', $activeMenu) ?>">
              <i class="nav-icon fas fa-key"></i><p>My Account</p>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </aside>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2 align-items-center">
          <div class="col-sm-6">
            <h1 class="m-0"><?= e($pageTitle) ?></h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="/admin/dashboard.php">Home</a></li>
              <?php foreach ($breadcrumbs as $bc): ?>
                <?php if (!empty($bc['url'])): ?>
                  <li class="breadcrumb-item"><a href="<?= e($bc['url']) ?>"><?= e($bc['label']) ?></a></li>
                <?php else: ?>
                  <li class="breadcrumb-item active"><?= e($bc['label']) ?></li>
                <?php endif; ?>
              <?php endforeach; ?>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <?php if ($flash): ?>
          <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?= e($flash['message']) ?>
          </div>
        <?php endif; ?>
