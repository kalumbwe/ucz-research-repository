# UCZ University Research Repository

A full-stack PHP/MySQL web application for the United Church of Zambia
University to publish, organize, and share research reports (dissertations,
theses, staff research, conference papers, journal articles) as
downloadable PDFs, organized by department.

## Modules included

- **Public site**: homepage, browse/filter (department, degree type, year),
  full-text search, report detail page, secure PDF download with tracking.
- **Admin panel** (AdminLTE): login, dashboard with stats, research report
  management (upload/edit/delete PDFs + cover image + full metadata),
  department management, download logs & analytics, admin user management
  (super admin only), site settings.
- **Database**: departments, research_reports, admins, download_logs,
  settings — with a full-text index for search and a download-log audit
  trail.

## Fields captured per report

Title, abstract, description/notes, author, co-authors, supervisor,
department, degree type, academic year, publication date, keywords,
language, page count, ISBN/ISSN, PDF file, optional cover image,
status (published/draft/archived), access level (public/restricted),
view count, download count.

## 1. Installation on XAMPP

1. Install [XAMPP](https://www.apachefriends.org/) if not already installed,
   and start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Copy the whole `ucz_research_repository` folder into your XAMPP
   `htdocs` directory, e.g.:
   - Windows: `C:\xampp\htdocs\ucz_research_repository`
   - Linux: `/opt/lampp/htdocs/ucz_research_repository`
   - macOS: `/Applications/XAMPP/htdocs/ucz_research_repository`
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`), click **Import**,
   and import `database/ucz_research_repository.sql`. This creates the
   database, tables, sample departments, default settings, and the
   default admin account.
4. Open `config/config.php` and confirm `BASE_URL` matches where you
   placed the folder, e.g.:
   ```php
   define('BASE_URL', 'http://localhost/ucz_research_repository');
   ```
5. Open `config/database.php` and confirm the DB credentials match your
   XAMPP MySQL setup (defaults are `root` with a blank password, which is
   standard for XAMPP).
6. Visit `http://localhost/ucz_research_repository/` — the public site
   should load with the seeded departments.

## 2. First login

Go to `http://localhost/ucz_research_repository/admin/`

- **Username:** `admin`
- **Password:** `ChangeMe@123`

**Change this password immediately** by adding a new admin user with your
own credentials (Admin Users menu, super admin only), or updating your
password directly in phpMyAdmin using PHP's `password_hash()` — then
disable/remove the default account.

## 3. Uploading a research report

Admin panel → **Research Reports** → **Upload Report**. Fill in the
metadata, choose the department, upload the PDF (25 MB limit by default,
adjustable in `config/config.php` via `MAX_FILE_SIZE_MB`), and set status
to **Published** to make it visible on the public site immediately, or
**Draft** to save it without publishing yet.

## 4. Linking this to your main website

This repository is a **self-contained web application** that must be
hosted on a web server (XAMPP locally, or a live PHP + MySQL server for
production) — a chat assistant cannot generate a working public URL for
you, since the app needs to actually run somewhere with a database behind
it. To make it reachable from your main UCZ University website, you have
two common options:

**Option A — Subdomain (recommended)**
Deploy this folder to a live server (shared hosting, VPS, or your
institution's server) that supports PHP + MySQL, point a subdomain like
`research.uczuniversity.ac.zm` at it, then add a link/button on your main
website's navigation menu pointing to that subdomain.

**Option B — Subfolder**
Deploy it inside a folder on your existing website's server, e.g.
`https://www.uczuniversity.ac.zm/research/`, update `BASE_URL` in
`config/config.php` to match, and link to it the same way.

Once you've chosen a host, update `BASE_URL` in `config/config.php` to the
live address (e.g. `https://research.uczuniversity.ac.zm`) before going
live — this value is used throughout the app for all internal links,
uploaded file URLs, and downloads.

## 5. Security notes before going live

- Change the default admin password immediately.
- Set a strong MySQL root/user password in production (not blank).
- Serve the site over HTTPS in production.
- The `uploads/` folder already has an `.htaccess` blocking script
  execution — keep this file in place.
- Consider daily backups of the `uploads/` folder and the database.

## Folder structure

```
ucz_research_repository/
├── admin/                  Admin panel (login, dashboard, CRUD, logs, settings)
│   └── includes/
├── assets/css/             Public site styling (UCZ branded)
├── config/                 Database + app configuration
├── database/                ucz_research_repository.sql (import this first)
├── includes/                Shared PHP helper functions + layout partials
├── uploads/reports/         Uploaded PDF files (created automatically)
├── uploads/covers/          Optional cover images
├── index.php                 Public homepage
├── browse.php                 Browse/filter all reports
├── department.php             Reports for one department
├── search.php                  Full-text search results
├── report.php                   Report detail page
└── download.php                  Tracked PDF download handler
```
