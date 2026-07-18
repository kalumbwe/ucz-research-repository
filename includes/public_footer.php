<footer class="ucz-footer text-light mt-5 pt-5 pb-4">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <h5><i class="fa-solid fa-book-open-reader me-2"></i><?= e($settings['site_name']) ?></h5>
        <p class="small text-light-emphasis"><?= e($settings['site_tagline']) ?></p>
      </div>
      <div class="col-md-4">
        <h6>Quick Links</h6>
        <ul class="list-unstyled small">
          <li><a href="<?= BASE_URL ?>/index.php" class="footer-link">Home</a></li>
          <li><a href="<?= BASE_URL ?>/browse.php" class="footer-link">Browse Repository</a></li>
          <li><a href="<?= BASE_URL ?>/admin/index.php" class="footer-link">Admin Login</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h6>Contact</h6>
        <p class="small text-light-emphasis mb-0">
          <i class="fa-solid fa-envelope me-2"></i><?= e($settings['contact_email']) ?>
        </p>
      </div>
    </div>
    <hr class="border-secondary">
    <p class="text-center small mb-0 text-light-emphasis">
      &copy; <?= date('Y') ?> United Church of Zambia University. All rights reserved.
    </p>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
