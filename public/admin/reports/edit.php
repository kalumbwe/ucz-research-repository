<?php
require_once __DIR__ . '/../../../app/includes/auth.php';
$admin = require_login();
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM reports WHERE id = ?');
$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    flash_set('error', 'Report not found.');
    redirect('/admin/reports/index.php');
}

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

    $fileName = $report['file_name'];
    $originalFileName = $report['original_file_name'];
    $fileSize = $report['file_size_bytes'];
    $newUpload = null;

    if (empty($errors) && !empty($_FILES['pdf_file']['name'])) {
        try {
            $newUpload = handle_pdf_upload('pdf_file');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        if ($newUpload) {
            $oldPath = STORAGE_PATH . '/' . $report['file_name'];
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
            $fileName = $newUpload['file_name'];
            $originalFileName = $newUpload['original_file_name'];
            $fileSize = $newUpload['size'];
        }

        $stmt = $pdo->prepare(
            "UPDATE reports SET
                title = ?, authors = ?, abstract = ?, keywords = ?, department_id = ?, category_id = ?,
                publication_year = ?, file_name = ?, original_file_name = ?, file_size_bytes = ?,
                status = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([
            $title, $authors, $abstract, $keywords ?: null,
            $departmentId ?: null, $categoryId ?: null, (int) $year,
            $fileName, $originalFileName, $fileSize,
            $status, $id,
        ]);

        clear_old();
        flash_set('success', 'Report "' . $title . '" was updated.');
        redirect('/admin/reports/index.php');
    }

    // Re-populate the form with submitted values on validation failure.
    $report = array_merge($report, [
        'title' => $title, 'authors' => $authors, 'abstract' => $abstract, 'keywords' => $keywords,
        'department_id' => $departmentId, 'category_id' => $categoryId, 'publication_year' => $year, 'status' => $status,
    ]);
}

$pageTitle = 'Edit Report';
$activeMenu = 'reports';
$breadcrumbs = [['label' => 'Reports', 'url' => '/admin/reports/index.php'], ['label' => 'Edit']];
require __DIR__ . '/../../../app/includes/header_admin.php';
?>

<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Edit Report — <span class="accession-code">UCZ-<?= str_pad((string) $report['id'], 4, '0', STR_PAD_LEFT) ?></span></h3></div>
      <div class="card-body">
        <?php foreach ($errors as $err): ?>
          <div class="alert alert-danger py-2"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $report['id'] ?>">

          <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" class="form-control" value="<?= e($report['title']) ?>" required>
          </div>

          <div class="form-group">
            <label>Author(s) *</label>
            <input type="text" name="authors" class="form-control" value="<?= e($report['authors']) ?>" required>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label>School / Department</label>
              <select name="department_id" class="form-control">
                <option value="">— Select —</option>
                <?php foreach (all_departments() as $d): ?>
                  <option value="<?= (int) $d['id'] ?>" <?= $report['department_id'] == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label>Type</label>
              <select name="category_id" class="form-control">
                <option value="">— Select —</option>
                <?php foreach (all_categories() as $c): ?>
                  <option value="<?= (int) $c['id'] ?>" <?= $report['category_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label>Publication Year *</label>
              <input type="number" name="publication_year" class="form-control" min="1950" max="<?= date('Y') + 1 ?>" value="<?= e((string) $report['publication_year']) ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label>Abstract *</label>
            <textarea name="abstract" rows="6" class="form-control" required><?= e($report['abstract']) ?></textarea>
          </div>

          <div class="form-group">
            <label>Keywords</label>
            <input type="text" name="keywords" class="form-control" value="<?= e($report['keywords'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label>Current file</label>
            <div class="mb-2"><i class="fas fa-file-pdf text-danger mr-1"></i> <?= e($report['original_file_name']) ?> (<?= format_bytes((int) $report['file_size_bytes']) ?>)</div>
            <div class="custom-file">
              <input type="file" name="pdf_file" id="pdf_file" class="custom-file-input" accept="application/pdf">
              <label class="custom-file-label" for="pdf_file">Replace file (optional)&hellip;</label>
            </div>
            <small class="form-text text-muted">Leave blank to keep the current file. Max <?= (int) (MAX_UPLOAD_BYTES / 1024 / 1024) ?> MB.</small>
          </div>

          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control" style="max-width:220px">
              <option value="published" <?= $report['status'] === 'published' ? 'selected' : '' ?>>Published (visible to the public)</option>
              <option value="draft" <?= $report['status'] === 'draft' ? 'selected' : '' ?>>Draft (hidden from the public)</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Changes</button>
          <a href="/admin/reports/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card card-outline card-primary">
      <div class="card-header"><h3 class="card-title">Record Stats</h3></div>
      <div class="card-body" style="font-size:.9rem">
        <p class="mb-2"><strong>Views:</strong> <?= number_format((int) $report['views_count']) ?></p>
        <p class="mb-2"><strong>Downloads:</strong> <?= number_format((int) $report['downloads_count']) ?></p>
        <p class="mb-0"><a href="/report.php?slug=<?= urlencode($report['slug']) ?>" target="_blank">View public page &rarr;</a></p>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../../app/includes/footer_admin.php'; ?>
