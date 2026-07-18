<?php
$page_title = "Settings";
require_once __DIR__ . '/includes/admin_header.php';

$settings = get_settings($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name'] ?? '');
    $site_tagline = trim($_POST['site_tagline'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $reports_per_page = (int)($_POST['reports_per_page'] ?? 12);

    $stmt = $pdo->prepare("UPDATE settings SET site_name=?, site_tagline=?, contact_email=?, reports_per_page=? WHERE id=?");
    $stmt->execute([$site_name, $site_tagline, $contact_email, $reports_per_page, $settings['id']]);
    flash_set('success', 'Settings updated successfully.');
    header('Location: settings.php');
    exit;
}
?>

<div class="card">
  <div class="card-header"><h3 class="card-title">Site Settings</h3></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-group">
        <label>Site Name</label>
        <input type="text" name="site_name" class="form-control" value="<?= e($settings['site_name']) ?>">
      </div>
      <div class="form-group">
        <label>Tagline</label>
        <input type="text" name="site_tagline" class="form-control" value="<?= e($settings['site_tagline']) ?>">
      </div>
      <div class="form-group">
        <label>Contact Email</label>
        <input type="email" name="contact_email" class="form-control" value="<?= e($settings['contact_email']) ?>">
      </div>
      <div class="form-group">
        <label>Reports Per Page (public browse)</label>
        <input type="number" name="reports_per_page" class="form-control" value="<?= (int)$settings['reports_per_page'] ?>" min="4" max="48">
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save Settings</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3 class="card-title">Change Base URL</h3></div>
  <div class="card-body">
    <p class="text-muted">To change the site's Base URL (used for links and file downloads), edit the <code>BASE_URL</code> constant in
    <code>/config/config.php</code>. This is set at the file level for security and is not editable from this page.</p>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
