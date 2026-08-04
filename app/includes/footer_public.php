</main>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <h5>UCZ Research Repository</h5>
        <p style="max-width:38ch;font-size:.88rem"><?= e(setting('footer_about')) ?></p>
      </div>
      <div>
        <h5>Explore</h5>
        <ul>
          <li><a href="/reports.php">Browse Research</a></li>
          <li><a href="/about.php">About the Repository</a></li>
          <li><a href="/admin/login.php">Staff Login</a></li>
        </ul>
      </div>
      <div>
        <h5>Get in Touch</h5>
        <ul>
          <?php if (setting('contact_address') !== ''): ?>
            <li><span class="mono" style="font-size:.85rem"><?= e(setting('contact_address')) ?></span></li>
          <?php endif; ?>
          <?php if (setting('contact_email') !== ''): ?>
            <li><a href="mailto:<?= e(setting('contact_email')) ?>" class="mono" style="font-size:.85rem"><?= e(setting('contact_email')) ?></a></li>
          <?php endif; ?>
          <?php if (setting('contact_phone') !== ''): ?>
            <li><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('contact_phone'))) ?>" class="mono" style="font-size:.85rem"><?= e(setting('contact_phone')) ?></a></li>
          <?php endif; ?>
          <?php if (setting('footer_tagline') !== ''): ?>
            <li><span class="mono" style="font-size:.85rem">&quot;<?= e(setting('footer_tagline')) ?>&quot;</span></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> United Church of Zambia University</span>
      <span>Research Repository</span>
    </div>
  </div>
</footer>
<script src="/assets/js/public.js"></script>
</body>
</html>
