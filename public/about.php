<?php
require_once __DIR__ . '/../app/includes/functions.php';
$pageTitle = 'About — ' . APP_NAME;
require __DIR__ . '/../app/includes/header_public.php';
?>

<section class="page-hero">
  <div class="container">
    <h1>About the Repository</h1>
    <p>The digital home of research produced across the University.</p>
  </div>
</section>

<section class="section">
  <div class="container prose">
    <p>The <?= e(APP_NAME) ?> is the official digital archive for research reports, theses, dissertations, conference papers and scholarly publications produced by staff, postgraduate students and researchers across the University's schools and centres.</p>

    <h2>What you'll find here</h2>
    <p>Every record in the catalogue includes an abstract, authorship, school, publication year and a downloadable PDF of the full report. Use the search and filter tools on the <a href="/reports.php">Browse Research</a> page to narrow results by school, research type or year.</p>

    <h2>Open access</h2>
    <p>Reports published in this repository are made freely available for reading and download in support of the University's mission &mdash; <em>Knowledge for Service and Fullness of Life</em>.</p>

    <h2>Contributing research</h2>
    <p>Research is added to the repository by the University's designated repository administrators. If you are a staff member or postgraduate researcher who would like a completed report added, please contact your school office or the repository administrator directly.</p>
  </div>
</section>

<?php require __DIR__ . '/../app/includes/footer_public.php'; ?>
