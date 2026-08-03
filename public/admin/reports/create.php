<?php
require_once __DIR__ . '/../../../app/includes/auth.php';
$admin = require_login();
$pdo = db();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    }

    $title = trim($_POST['title'] ?? '');
    $authors = trim($_POST['authors'] ?? '');
    $abstract = trim($_POST['abstract'] ?? '');
    $keywords = trim($_POST['keywords'] ?? '');
    $departmentId = $_POST['department_id'] ?? '';
    $categoryId = $_POST['category_id'] ?? '';
    $year = trim($_POST['publication_year'] ?? '');
    $status = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';

    if ($title === '') $errors[] = 'Title is required.';
    if ($authors === '') $errors[] = 'Author(s) is required.';
    if ($abstract === '') $errors[] = 'Abstract is required.';
    if (!ctype_digit($year) || (int) $year < 1950 || (int) $year > (int) date('Y') + 1) {
        $errors[] = 'Enter a valid publication year.';
    }

    $upload = null;
    if (empty($errors)) {
        try {
            $upload = handle_pdf_upload('pdf_file');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $slug = unique_report_slug($title);
        $stmt = $pdo->prepare(
            "INSERT INTO reports
                (title, slug, authors, abstract, keywords, department_id, category_id, publication_year, file_name, original_file_name, file_size_bytes, status, uploaded_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $title, $slug, $authors, $abstract, $keywords ?: null,
            $departmentId ?: null, $categoryId ?: null, (int) $year,
            $upload['file_name'], $upload['original_file_name'], $upload['size'],
            $status, $admin['id'],
        ]);

        clear_old();
        flash_set('success', 'Report "' . $title . '" was uploaded successfully.');
        redirect('/admin/reports/index.php');
    }

    stash_old($_POST);
}

$pageTitle = 'Upload New Report';
$activeMenu = 'reports';
$breadcrumbs = [['label' => 'Reports', 'url' => '/admin/reports/index.php'], ['label' => 'Upload']];
require __DIR__ . '/../../../app/includes/header_admin.php';
?>

<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Report Details</h3></div>
      <div class="card-body">
        <?php foreach ($errors as $err): ?>
          <div class="alert alert-danger py-2"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>

          <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" class="form-control" value="<?= e(old('title')) ?>" required>
          </div>

          <div class="form-group">
            <label>Author(s) *</label>
            <input type="text" name="authors" class="form-control" value="<?= e(old('authors')) ?>" placeholder="e.g. M. Banda, C. Mwansa" required>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label>School / Department</label>
              <select name="department_id" class="form-control">
                <option value="">— Select —</option>
                <?php foreach (all_departments() as $d): ?>
                  <option value="<?= (int) $d['id'] ?>" <?= old('department_id') == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label>Type</label>
              <select name="category_id" class="form-control">
                <option value="">— Select —</option>
                <?php foreach (all_categories() as $c): ?>
                  <option value="<?= (int) $c['id'] ?>" <?= old('category_id') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label>Publication Year *</label>
              <input type="number" name="publication_year" class="form-control" min="1950" max="<?= date('Y') + 1 ?>" value="<?= e(old('publication_year', date('Y'))) ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label>Abstract *</label>
            <textarea name="abstract" rows="6" class="form-control" required><?= e(old('abstract')) ?></textarea>
          </div>

          <div class="form-group">
            <label>Keywords</label>
            <input type="text" name="keywords" class="form-control" value="<?= e(old('keywords')) ?>" placeholder="Comma-separated, e.g. theology, ethics, Zambia">
          </div>

          <div class="form-group">
            <label>PDF File *</label>
            <div class="custom-file">
              <input type="file" name="pdf_file" id="pdf_file" class="custom-file-input" accept="application/pdf" required>
              <label class="custom-file-label" for="pdf_file">Choose PDF file&hellip;</label>
            </div>
            <small class="form-text text-muted">PDF only, up to <?= (int) (MAX_UPLOAD_BYTES / 1024 / 1024) ?> MB.</small>
          </div>

          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control" style="max-width:220px">
              <option value="published" <?= old('status', 'published') === 'published' ? 'selected' : '' ?>>Published (visible to the public)</option>
              <option value="draft" <?= old('status') === 'draft' ? 'selected' : '' ?>>Draft (hidden from the public)</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary"><i class="fas fa-upload mr-1"></i> Upload Report</button>
          <a href="/admin/reports/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card card-outline card-primary">
      <div class="card-header"><h3 class="card-title">Tips</h3></div>
      <div class="card-body" style="font-size:.88rem">
        <ul class="pl-3 mb-0">
          <li class="mb-2">Use the full official title as it appears on the report's cover page.</li>
          <li class="mb-2">The abstract is shown in full on the public record page — keep it as written in the original document.</li>
          <li class="mb-2">Set status to <strong>Draft</strong> to save a record without publishing it to the public site yet.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../../app/includes/footer_admin.php'; ?>
