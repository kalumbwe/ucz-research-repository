<?php
$page_title = "Edit Research Report";
require_once __DIR__ . '/includes/admin_header.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM research_reports WHERE id = ?");
$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    flash_set('danger', 'Report not found.');
    header('Location: reports.php');
    exit;
}

$departments = get_departments($pdo, false);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $abstract = trim($_POST['abstract'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $author_name = trim($_POST['author_name'] ?? '');
    $co_authors = trim($_POST['co_authors'] ?? '');
    $supervisor = trim($_POST['supervisor'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $degree_type = $_POST['degree_type'] ?? 'Undergraduate';
    $academic_year = trim($_POST['academic_year'] ?? '');
    $publication_date = $_POST['publication_date'] ?? '';
    $keywords = trim($_POST['keywords'] ?? '');
    $language = trim($_POST['language'] ?? 'English');
    $pages = $_POST['pages'] !== '' ? (int)$_POST['pages'] : null;
    $isbn_issn = trim($_POST['isbn_issn'] ?? '');
    $status = $_POST['status'] ?? 'published';
    $access_level = $_POST['access_level'] ?? 'public';

    if ($title === '') $errors[] = 'Title is required.';
    if ($abstract === '') $errors[] = 'Abstract is required.';
    if ($author_name === '') $errors[] = 'Author name is required.';
    if (!$department_id) $errors[] = 'Department is required.';

    $fileName = $report['file_name'];
    $originalFileName = $report['original_file_name'];
    $fileSize = $report['file_size'];

    // Replace PDF only if a new one was uploaded
    if (!empty($_FILES['pdf_file']['name'])) {
        $file = $_FILES['pdf_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $errors[] = 'Only PDF files are allowed.';
        } elseif ($file['size'] > MAX_FILE_SIZE_MB * 1024 * 1024) {
            $errors[] = 'File exceeds the maximum size of ' . MAX_FILE_SIZE_MB . ' MB.';
        } elseif (empty($errors)) {
            $newStoredName = generate_safe_filename($file['name']);
            if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR_REPORTS . $newStoredName)) {
                $oldPath = UPLOAD_DIR_REPORTS . $fileName;
                if (file_exists($oldPath)) unlink($oldPath);
                $fileName = $newStoredName;
                $originalFileName = $file['name'];
                $fileSize = $file['size'];
            } else {
                $errors[] = 'Failed to save the replacement PDF file.';
            }
        }
    }

    $coverName = $report['cover_image'];
    if (!empty($_FILES['cover_image']['name'])) {
        $coverExt = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        if (in_array($coverExt, ['jpg','jpeg','png','webp'])) {
            $newCover = generate_safe_filename($_FILES['cover_image']['name']);
            if (!is_dir(UPLOAD_DIR_COVERS)) mkdir(UPLOAD_DIR_COVERS, 0755, true);
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], UPLOAD_DIR_COVERS . $newCover)) {
                if ($coverName && file_exists(UPLOAD_DIR_COVERS . $coverName)) unlink(UPLOAD_DIR_COVERS . $coverName);
                $coverName = $newCover;
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE research_reports SET
            title=?, abstract=?, description=?, author_name=?, co_authors=?, supervisor=?, department_id=?,
            degree_type=?, academic_year=?, publication_date=?, keywords=?, language=?, pages=?, isbn_issn=?,
            file_name=?, original_file_name=?, file_size=?, cover_image=?, status=?, access_level=?
            WHERE id=?");
        $stmt->execute([
            $title, $abstract, $description, $author_name, $co_authors, $supervisor, $department_id,
            $degree_type, $academic_year, $publication_date, $keywords, $language, $pages, $isbn_issn,
            $fileName, $originalFileName, $fileSize, $coverName, $status, $access_level, $id
        ]);
        flash_set('success', 'Report updated successfully.');
        header('Location: reports.php');
        exit;
    }
}
?>

<div class="card">
  <div class="card-header"><h3 class="card-title">Edit: <?= e($report['title']) ?></h3></div>
  <div class="card-body">
    <?php if ($errors): ?>
      <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= $report['id'] ?>">
      <div class="form-row">
        <div class="form-group col-md-8">
          <label>Title *</label>
          <input type="text" name="title" class="form-control" value="<?= e($report['title']) ?>" required>
        </div>
        <div class="form-group col-md-4">
          <label>Department *</label>
          <select name="department_id" class="form-control" required>
            <?php foreach ($departments as $d): ?>
              <option value="<?= $d['id'] ?>" <?= $d['id'] == $report['department_id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Abstract *</label>
        <textarea name="abstract" class="form-control" rows="4" required><?= e($report['abstract']) ?></textarea>
      </div>

      <div class="form-group">
        <label>Description / Notes</label>
        <textarea name="description" class="form-control" rows="3"><?= e($report['description']) ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group col-md-4">
          <label>Author Name *</label>
          <input type="text" name="author_name" class="form-control" value="<?= e($report['author_name']) ?>" required>
        </div>
        <div class="form-group col-md-4">
          <label>Co-Authors</label>
          <input type="text" name="co_authors" class="form-control" value="<?= e($report['co_authors']) ?>">
        </div>
        <div class="form-group col-md-4">
          <label>Supervisor</label>
          <input type="text" name="supervisor" class="form-control" value="<?= e($report['supervisor']) ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-3">
          <label>Degree Type *</label>
          <select name="degree_type" class="form-control">
            <?php foreach (['Undergraduate','Masters','PhD','Staff Research','Conference Paper','Journal Article'] as $dt): ?>
              <option value="<?= $dt ?>" <?= $dt === $report['degree_type'] ? 'selected' : '' ?>><?= $dt ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group col-md-3">
          <label>Academic Year *</label>
          <input type="text" name="academic_year" class="form-control" value="<?= e($report['academic_year']) ?>" required>
        </div>
        <div class="form-group col-md-3">
          <label>Publication Date *</label>
          <input type="date" name="publication_date" class="form-control" value="<?= e($report['publication_date']) ?>" required>
        </div>
        <div class="form-group col-md-3">
          <label>Language</label>
          <input type="text" name="language" class="form-control" value="<?= e($report['language']) ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-4">
          <label>Keywords</label>
          <input type="text" name="keywords" class="form-control" value="<?= e($report['keywords']) ?>">
        </div>
        <div class="form-group col-md-2">
          <label>Pages</label>
          <input type="number" name="pages" class="form-control" value="<?= e($report['pages']) ?>">
        </div>
        <div class="form-group col-md-3">
          <label>ISBN / ISSN</label>
          <input type="text" name="isbn_issn" class="form-control" value="<?= e($report['isbn_issn']) ?>">
        </div>
        <div class="form-group col-md-3">
          <label>Access Level</label>
          <select name="access_level" class="form-control">
            <option value="public" <?= $report['access_level']==='public'?'selected':'' ?>>Public</option>
            <option value="restricted" <?= $report['access_level']==='restricted'?'selected':'' ?>>Restricted</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-6">
          <label>Replace PDF File (optional)</label>
          <input type="file" name="pdf_file" class="form-control-file" accept="application/pdf">
          <small class="text-muted">Current file: <?= e($report['original_file_name']) ?> (<?= format_file_size($report['file_size']) ?>)</small>
        </div>
        <div class="form-group col-md-4">
          <label>Replace Cover Image (optional)</label>
          <input type="file" name="cover_image" class="form-control-file" accept="image/*">
        </div>
        <div class="form-group col-md-2">
          <label>Status</label>
          <select name="status" class="form-control">
            <option value="published" <?= $report['status']==='published'?'selected':'' ?>>Published</option>
            <option value="draft" <?= $report['status']==='draft'?'selected':'' ?>>Draft</option>
            <option value="archived" <?= $report['status']==='archived'?'selected':'' ?>>Archived</option>
          </select>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save Changes</button>
      <a href="reports.php" class="btn btn-outline-secondary">Cancel</a>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
