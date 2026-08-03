<?php
/**
 * Expects (optional) before include:
 *   $pageTitle        string  — shown in <title> and browser tab
 *   $metaDescription  string  — <meta name="description">
 */
$pageTitle = $pageTitle ?? APP_NAME;
$metaDescription = $metaDescription ?? 'Browse and download peer-reviewed research, theses and dissertations from ' . APP_NAME . '.';
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 64 64%27%3E%3Ccircle cx=%2732%27 cy=%2732%27 r=%2730%27 fill=%27%2314213D%27/%3E%3Ccircle cx=%2732%27 cy=%2732%27 r=%2729%27 fill=%27none%27 stroke=%27%23B8892B%27 stroke-width=%272%27/%3E%3Ctext x=%2732%27 y=%2743%27 font-family=%27Georgia,serif%27 font-size=%2726%27 font-weight=%27700%27 fill=%27%23B8892B%27 text-anchor=%27middle%27%3EU%3C/text%3E%3C/svg%3E">
<meta name="description" content="<?= e($metaDescription) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/public.css">
</head>
<body>
<header class="site-header">
  <div class="container">
    <a href="/" class="brand">
      <span class="brand-mark">UCZ</span>
      <span class="brand-text">
        <strong>Research Repository</strong>
        <span>United Church of Zambia University</span>
      </span>
    </a>
    <nav class="site-nav" aria-label="Primary">
      <a href="/">Home</a>
      <a href="/reports.php">Browse Research</a>
      <a href="/about.php">About</a>
      <a href="/admin/login.php" class="nav-admin">Staff Login</a>
    </nav>
  </div>
</header>
<main>
<?php if ($flash): ?>
  <div class="container" style="padding-top:24px">
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>"><?= e($flash['message']) ?></div>
  </div>
<?php endif; ?>
