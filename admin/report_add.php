<?php
$page_title = "Upload Research Report";
require_once __DIR__ . '/includes/admin_header.php';

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
    if ($academic_year === '') $errors[] = 'Academic year is required.';
    if ($publication_date === '') $errors[] = 'Publication date is required.';

    // Validate PDF file
    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'A PDF file is required.';
    } else {
        $file = $_FILES['pdf_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $errors[] = 'Only PDF files are allowed.';
        }
        if ($file['size'] > MAX_FILE_SIZE_MB * 1024 * 1024) {
            $errors[] = 'File exceeds the maximum size of ' . MAX_FILE_SIZE_MB . ' MB.';
        }
    }

    if (empty($errors)) {
        $storedName = generate_safe_filename($file['name']);
        $destination = UPLOAD_DIR_REPORTS . $storedName;

        if (!is_dir(UPLOAD_DIR_REPORTS)) mkdir(UPLOAD_DIR_REPORTS, 0755, true);

        if (move_uploaded_file($file['tmp_name'], $destination)) {

            // Optional cover image
            $coverName = null;
            if (!empty($_FILES['cover_image']['name'])) {
                $coverExt = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
                if (in_array($coverExt, ['jpg','jpeg','png','webp'])) {
                    $coverName = generate_safe_filename($_FILES['cover_image']['name']);
                    if (!is_dir(UPLOAD_DIR_COVERS)) mkdir(UPLOAD_DIR_COVERS, 0755, true);
                    move_uploaded_file($_FILES['cover_image']['tmp_name'], UPLOAD_DIR_COVERS . $coverName);
                }
            }

            $stmt = $pdo->prepare("INSERT INTO research_reports
                (title, abstract, description, author_name, co_authors, supervisor, department_id,
                 degree_type, academic_year, publication_date, keywords, language, pages, isbn_issn,
                 file_name, original_file_name, file_size, cover_image, status, access_level, uploaded_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $title, $abstract, $description, $author_name, $co_authors, $supervisor, $department_id,
                $degree_type, $academic_year, $publication_date, $keywords, $language, $pages, $isbn_issn,
                $storedName, $file['name'], $file['size'], $coverName, $status, $access_level, $_SESSION['admin_id']
            ]);

            flash_set('success', 'Research report uploaded successfully.');
            header('Location: reports.php');
            exit;
        } else {
            $errors[] = 'Failed to save the uploaded file. Check folder permissions on uploads/reports.';
        }
    }
}
?>

<div class="card">
  <div class="card-header"><h3 class="card-title">Upload New Research Report</h3></div>
  <div class="card-body">
    <?php if ($errors): ?>
      <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="form-row">
        <div class="form-group col-md-8">
          <label>Title *</label>
          <input type="text" name="title" class="form-control" value="<?= e($_POST['title'] ?? '') ?>" required>
        </div>
        <div class="form-group col-md-4">
          <label>Department *</label>
          <select name="department_id" class="form-control" required>
            <option value="">-- Select --</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Abstract *</label>
        <textarea name="abstract" class="form-control" rows="4" required><?= e($_POST['abstract'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label>Description / Notes</label>
        <textarea name="description" class="form-control" rows="3"><?= e($_POST['description'] ?? '') ?></textarea>
        <small class="text-muted">Extra context shown on the report's public page (optional).</small>
      </div>

      <div class="form-row">
        <div class="form-group col-md-4">
          <label>Author Name *</label>
          <input type="text" name="author_name" class="form-control" value="<?= e($_POST['author_name'] ?? '') ?>" required>
        </div>
        <div class="form-group col-md-4">
          <label>Co-Authors</label>
          <input type="text" name="co_authors" class="form-control" value="<?= e($_POST['co_authors'] ?? '') ?>">
        </div>
        <div class="form-group col-md-4">
          <label>Supervisor</label>
          <input type="text" name="supervisor" class="form-control" value="<?= e($_POST['supervisor'] ?? '') ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-3">
          <label>Degree Type *</label>
          <select name="degree_type" class="form-control">
            <?php foreach (['Undergraduate','Masters','PhD','Staff Research','Conference Paper','Journal Article'] as $dt): ?>
              <option value="<?= $dt ?>"><?= $dt ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group col-md-3">
          <label>Academic Year *</label>
          <input type="text" name="academic_year" class="form-control" placeholder="e.g. 2025/2026" value="<?= e($_POST['academic_year'] ?? '') ?>" required>
        </div>
        <div class="form-group col-md-3">
          <label>Publication Date *</label>
          <input type="date" name="publication_date" class="form-control" value="<?= e($_POST['publication_date'] ?? '') ?>" required>
        </div>
        <div class="form-group col-md-3">
          <label>Language</label>
          <input type="text" name="language" class="form-control" value="<?= e($_POST['language'] ?? 'English') ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-4">
          <label>Keywords</label>
          <input type="text" name="keywords" class="form-control" placeholder="comma, separated, terms" value="<?= e($_POST['keywords'] ?? '') ?>">
        </div>
        <div class="form-group col-md-2">
          <label>Pages</label>
          <input type="number" name="pages" class="form-control" value="<?= e($_POST['pages'] ?? '') ?>">
        </div>
        <div class="form-group col-md-3">
          <label>ISBN / ISSN</label>
          <input type="text" name="isbn_issn" class="form-control" value="<?= e($_POST['isbn_issn'] ?? '') ?>">
        </div>
        <div class="form-group col-md-3">
          <label>Access Level</label>
          <select name="access_level" class="form-control">
            <option value="public">Public</option>
            <option value="restricted">Restricted</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-6">
          <label>PDF File * (max <?= MAX_FILE_SIZE_MB ?> MB)</label>
          <input type="file" name="pdf_file" class="form-control-file" accept="application/pdf" required>
        </div>
        <div class="form-group col-md-4">
          <label>Cover Image (optional)</label>
          <input type="file" name="cover_image" class="form-control-file" accept="image/*">
        </div>
        <div class="form-group col-md-2">
          <label>Status</label>
          <select name="status" class="form-control">
            <option value="published">Published</option>
            <option value="draft">Draft</option>
          </select>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><i class="fas fa-upload mr-1"></i>Upload Report</button>
      <a href="reports.php" class="btn btn-outline-secondary">Cancel</a>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
