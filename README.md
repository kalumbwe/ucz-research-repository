# UCZ University Research Repository

A digital archive for research reports, theses, dissertations and scholarly
papers produced across the University. Visitors can search, browse and
download published PDFs from a modern public site; administrators manage
every record from an AdminLTE-powered dashboard.

## What's included

- **Public site** — home page, search & filter (school, category, year),
  report detail pages with abstract + metadata, PDF download, about page.
- **Admin panel** — AdminLTE 3 login and dashboard, full CRUD for reports
  (upload/edit/delete PDFs), schools/departments, categories, and admin
  user accounts with two roles (`super_admin`, `editor`).
- **One-time browser install wizard** (`/install.php`) that applies the
  database schema and creates your first admin account — no shell access
  required.
- Deployment files for Render: `Dockerfile`, `docker-entrypoint.sh`,
  `render.yaml` (Blueprint), `.dockerignore`.

## Tech stack

- PHP 8.2 (plain PHP, no framework — PDO for all database access)
- PostgreSQL (Render's managed Postgres has no managed MySQL, so this
  project uses Postgres instead of the MySQL you may be used to from
  XAMPP; all SQL lives in `database/schema.sql`)
- Apache (via the official `php:8.2-apache` Docker image)
- AdminLTE 3 + Bootstrap 4 (admin, via CDN) · custom design system for the
  public site (via CDN Google Fonts, no build step)

No Composer, no Node build step, no XAMPP-specific configuration — the
whole app runs from plain `.php` files, which keeps the Docker image small
and the deploy simple.

## Project structure

```
app/config/       database + environment configuration
app/includes/      shared PHP: auth, helpers, header/footer partials
database/schema.sql   full Postgres schema + starter departments/categories
public/            Apache document root — every publicly-routable file
public/admin/      the admin panel (session-protected)
public/assets/     CSS/JS for both the public site and admin skin
storage/uploads/   uploaded PDFs (mount a Render Disk here in production)
```

## Local development

You'll need PHP 8.2+ with the `pdo_pgsql` and `mbstring` extensions, and a
local PostgreSQL database.

```bash
cp .env.example .env
# edit .env: set DB_NAME / DB_USER / DB_PASSWORD to your local Postgres
php -S localhost:8080 -t public
```

Visit `http://localhost:8080/install.php`, enter the `INSTALL_KEY` you set
in `.env`, and create your first admin account. That single step applies
`database/schema.sql` for you — no manual `psql` needed.

## Deploying to Render

### 1. Push this project to GitHub

Create a new GitHub repository and push this entire folder to it. Render
deploys straight from your Git repo.

### 2. Create the Blueprint on Render

1. In the Render dashboard: **New → Blueprint**.
2. Connect the GitHub repo you just created. Render will detect
   `render.yaml` automatically and show you what it's about to create:
   - a **Postgres database** (`ucz-research-db`, Starter plan)
   - a **Docker web service** (`ucz-research-repository`, Starter plan)
     built from the included `Dockerfile`
   - a **5 GB persistent disk** mounted at
     `/var/www/html/storage/uploads`, so uploaded PDFs survive future
     deploys
3. Click **Apply**. Render provisions the database, builds the Docker
   image, and deploys the service. The first build takes a few minutes.
4. Once it's live, open your service in the Render dashboard → **Environment**
   and set `APP_URL` to your Render URL (e.g. `https://ucz-research-repository.onrender.com`),
   or your custom domain once you've attached one.

Render also auto-generated a random `INSTALL_KEY` for you as part of the
Blueprint — find it under your web service's **Environment** tab.

### 3. Run the install wizard

Visit `https://<your-service>.onrender.com/install.php`, paste in the
`INSTALL_KEY` from step 4 above, and fill in your name/email/password.
This applies the database schema (tables + starter schools/categories) and
creates your first `super_admin` account. The installer locks itself
after one successful run.

### 4. Log in and start uploading

Go to `/admin/login.php`, sign in, and upload your first report from
**Research Reports → Upload New Report**. From **Admin Users** (visible to
super admins) you can invite colleagues with either role.

### Prefer to set things up manually instead of using the Blueprint?

1. Render dashboard → **New → PostgreSQL** → note the **Internal Database URL**.
2. Render dashboard → **New → Web Service** → connect your repo → runtime
   **Docker** → plan **Starter**.
3. Under **Advanced**, add environment variables:
   | Key | Value |
   |---|---|
   | `DATABASE_URL` | the Internal Database URL from step 1 |
   | `APP_ENV` | `production` |
   | `APP_URL` | your service's URL |
   | `STORAGE_PATH` | `/var/www/html/storage/uploads` |
   | `MAX_UPLOAD_MB` | `25` |
   | `INSTALL_KEY` | a long random string you choose |
4. Under **Advanced → Add Disk**, mount a disk at
   `/var/www/html/storage/uploads` (5 GB is plenty to start).
5. Deploy, then continue from "Run the install wizard" above.

## Environment variables reference

| Variable | Purpose |
|---|---|
| `DATABASE_URL` | Full Postgres connection string (Render provides this) |
| `DB_HOST` / `DB_PORT` / `DB_NAME` / `DB_USER` / `DB_PASSWORD` | Used only if `DATABASE_URL` is unset (local dev) |
| `APP_ENV` | `production` or `local` — controls error display |
| `APP_URL` | Your site's public URL |
| `STORAGE_PATH` | Where uploaded PDFs are stored — should match your Render Disk's mount path |
| `MAX_UPLOAD_MB` | Max PDF upload size |
| `INSTALL_KEY` | One-time secret required to run `/install.php` |

## Troubleshooting the database connection

If the site shows **"Database connection failed"**, the app could not open a
PostgreSQL connection. Two ways to see the real reason:

1. **Render logs** — the underlying driver error is always written to the
   service log, prefixed with `[db] connection failed`, together with the
   host/port/database/user actually in use (never the password).
2. **Health endpoint** — visit
   `https://<your-service>.onrender.com/healthz.php?db=1&key=<INSTALL_KEY>`.
   It reports `db: ok` or `db: fail`, and with the correct `INSTALL_KEY` it
   also prints the connection settings and the driver error. Without `?db=1`
   the endpoint stays a plain `OK` liveness probe for Render's health check.

Common causes:

| Error contains | Cause | Fix |
|---|---|---|
| `could not translate host name` | `DATABASE_URL` points at a database that no longer exists (a free Render database is deleted when its trial period ends) | Create a new Postgres instance, update `DATABASE_URL`, then re-run `/install.php` |
| `Connection refused` or a timeout | Database suspended, still provisioning, or in a different region than the web service | Check the database's status in the Render dashboard; keep both in the same region |
| `password authentication failed` | Stale credentials — the URL was copied before the database was recreated | Copy the current **Internal Database URL** into `DATABASE_URL` |
| `no pg_hba.conf entry` / SSL required | Using the **External** database URL without SSL | Prefer the Internal URL, or set `DB_SSLMODE=require` |
| `dbname=(empty)` in the log | `DATABASE_URL` is unset or malformed | Re-link it in the dashboard (Blueprint services get it via `fromDatabase`) |

## Security notes

- Change or remove `INSTALL_KEY` after installing — the installer refuses
  to run once an admin account already exists, but rotating the key is
  good hygiene.
- Uploaded PDFs are stored outside of any predictable public path and are
  served through `download.php`, which only streams files attached to
  **published** reports.
- Passwords are hashed with PHP's `password_hash()` (bcrypt); sessions are
  regenerated on login and cookies are `HttpOnly`.
- All forms are CSRF-protected and all database queries use prepared
  statements.

## Customizing

- **Branding/colors** — edit the CSS variables at the top of
  `public/assets/css/public.css` (public site) and
  `public/assets/css/admin-custom.css` (admin skin).
- **Schools & categories** — manage these from the admin panel
  (Schools/Departments, Categories) rather than editing the database
  directly; `database/schema.sql` only seeds sensible starting values.
