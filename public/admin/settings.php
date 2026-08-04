<?php
require_once __DIR__ . '/../../app/includes/auth.php';
$admin = require_role('super_admin');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $contactEmail = trim($_POST['contact_email'] ?? '');
        if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid contact email, or leave it blank.';
        }
        if (trim($_POST['hero_tagline'] ?? '') === '') {
            $errors[] = 'The hero tagline cannot be empty.';
        }

        if (empty($errors)) {
            save_settings([
                'hero_eyebrow'    => $_POST['hero_eyebrow'] ?? '',
                'hero_tagline'    => $_POST['hero_tagline'] ?? '',
                'hero_subtext'    => $_POST['hero_subtext'] ?? '',
                'footer_about'    => $_POST['footer_about'] ?? '',
                'footer_tagline'  => $_POST['footer_tagline'] ?? '',
                'contact_address' => $_POST['contact_address'] ?? '',
                'contact_email'   => $contactEmail,
                'contact_phone'   => $_POST['contact_phone'] ?? '',
            ]);
            flash_set('success', 'Site settings updated.');
            redirect('/admin/settings.php');
        }
    }
}

$s = all_settings();

$pageTitle = 'Site Settings';
$activeMenu = 'settings';
$breadcrumbs = [['label' => 'Site Settings']];
require __DIR__ . '/../../app/includes/header_admin.php';
?>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post">
  <?= csrf_field() ?>

  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header"><h3 class="card-title">Homepage Hero</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Eyebrow text</label>
            <input type="text" name="hero_eyebrow" class="form-control" value="<?= e($s['hero_eyebrow']) ?>">
            <small class="form-text text-muted">The small line above the main headline.</small>
          </div>
          <div class="form-group">
            <label>Main tagline (headline) *</label>
            <input type="text" name="hero_tagline" class="form-control" value="<?= e($s['hero_tagline']) ?>" required>
          </div>
          <div class="form-group mb-0">
            <label>Subtext</label>
            <textarea name="hero_subtext" rows="3" class="form-control"><?= e($s['hero_subtext']) ?></textarea>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h3 class="card-title">Footer</h3></div>
        <div class="card-body">
          <div class="form-group">
            <label>Footer "about" blurb</label>
            <textarea name="footer_about" rows="3" class="form-control"><?= e($s['footer_about']) ?></textarea>
          </div>
          <div class="form-group mb-0">
            <label>Footer motto / tagline</label>
            <input type="text" name="footer_tagline" class="form-control" value="<?= e($s['footer_tagline']) ?>">
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h3 class="card-title">Contact Details</h3></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Address / Location</label>
              <input type="text" name="contact_address" class="form-control" value="<?= e($s['contact_address']) ?>">
            </div>
            <div class="form-group col-md-4">
              <label>Contact email</label>
              <input type="email" name="contact_email" class="form-control" value="<?= e($s['contact_email']) ?>">
            </div>
            <div class="form-group col-md-4">
              <label>Contact phone</label>
              <input type="text" name="contact_phone" class="form-control" value="<?= e($s['contact_phone']) ?>">
            </div>
          </div>
          <small class="form-text text-muted mb-0">Leave any field blank to hide it from the public footer.</small>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Settings</button>
      <a href="/" target="_blank" class="btn btn-outline-secondary">Preview public site &rarr;</a>
    </div>

    <div class="col-lg-4">
      <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">Where this shows up</h3></div>
        <div class="card-body" style="font-size:.88rem">
          <ul class="pl-3 mb-0">
            <li class="mb-2">The <strong>eyebrow</strong>, <strong>tagline</strong> and <strong>subtext</strong> appear at the top of the public homepage.</li>
            <li class="mb-2">The <strong>footer blurb</strong> and <strong>motto</strong> appear in the site footer on every public page.</li>
            <li class="mb-0"><strong>Contact details</strong> appear in the footer's "Get in Touch" column — any left blank simply won't be shown.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</form>

<?php require __DIR__ . '/../../app/includes/footer_admin.php'; ?>
