<?php
if (!isset($pdo)) { require_once __DIR__ . '/../config/config.php'; }
$settings = get_settings($pdo);
$departments_nav = get_departments($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? e($page_title) . ' | ' : '' ?><?= e($settings['site_name']) ?></title>
<meta name="description" content="<?= e($settings['site_tagline']) ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700&family=Lato:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark ucz-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
      <i class="fa-solid fa-book-open-reader me-2"></i>
      <?= e($settings['site_name']) ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php">Home</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Departments</a>
          <ul class="dropdown-menu">
            <?php foreach ($departments_nav as $d): ?>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/department.php?id=<?= $d['id'] ?>"><?= e($d['name']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/browse.php">Browse All</a></li>
        <li class="nav-item ms-lg-2">
          <form action="<?= BASE_URL ?>/search.php" method="GET" class="d-flex" role="search">
            <input class="form-control form-control-sm me-1" type="search" name="q" placeholder="Search reports...">
            <button class="btn btn-sm btn-warning" type="submit"><i class="fa-solid fa-search"></i></button>
          </form>
        </li>
        <li class="nav-item ms-lg-2">
          <a class="btn btn-sm btn-outline-light" href="<?= BASE_URL ?>/admin/index.php"><i class="fa-solid fa-user-shield me-1"></i>Admin</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
