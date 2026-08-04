-- ============================================================
-- UCZ University Research Repository — PostgreSQL schema
-- Safe to run more than once (uses IF NOT EXISTS throughout).
-- No admin credentials are seeded here — create your first admin
-- account through public/install.php after this schema is applied.
-- ============================================================

CREATE TABLE IF NOT EXISTS admin_users (
    id              SERIAL PRIMARY KEY,
    full_name       VARCHAR(150)  NOT NULL,
    email           VARCHAR(150)  NOT NULL UNIQUE,
    password_hash   VARCHAR(255)  NOT NULL,
    role            VARCHAR(20)   NOT NULL DEFAULT 'editor', -- 'super_admin' | 'editor'
    is_active       BOOLEAN       NOT NULL DEFAULT TRUE,
    last_login_at   TIMESTAMP     NULL,
    created_at      TIMESTAMP     NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP     NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS departments (
    id              SERIAL PRIMARY KEY,
    name            VARCHAR(150)  NOT NULL,
    slug            VARCHAR(160)  NOT NULL UNIQUE,
    description     TEXT          NULL,
    created_at      TIMESTAMP     NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS categories (
    id              SERIAL PRIMARY KEY,
    name            VARCHAR(120)  NOT NULL,
    slug            VARCHAR(140)  NOT NULL UNIQUE,
    created_at      TIMESTAMP     NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS reports (
    id                  SERIAL PRIMARY KEY,
    title               VARCHAR(300)  NOT NULL,
    slug                VARCHAR(320)  NOT NULL UNIQUE,
    authors             VARCHAR(400)  NOT NULL,
    abstract            TEXT          NOT NULL,
    keywords            VARCHAR(400)  NULL,
    department_id       INTEGER       NULL REFERENCES departments(id) ON DELETE SET NULL,
    category_id         INTEGER       NULL REFERENCES categories(id) ON DELETE SET NULL,
    publication_year    INTEGER       NOT NULL,
    file_name           VARCHAR(255)  NOT NULL,   -- stored (randomised) filename on disk
    original_file_name  VARCHAR(255)  NOT NULL,   -- original filename, shown to visitors
    file_size_bytes     BIGINT        NOT NULL DEFAULT 0,
    status              VARCHAR(20)   NOT NULL DEFAULT 'published', -- 'published' | 'draft'
    views_count         INTEGER       NOT NULL DEFAULT 0,
    downloads_count     INTEGER       NOT NULL DEFAULT 0,
    uploaded_by         INTEGER       NULL REFERENCES admin_users(id) ON DELETE SET NULL,
    created_at          TIMESTAMP     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP     NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key     VARCHAR(100)  PRIMARY KEY,
    setting_value   TEXT          NOT NULL DEFAULT '',
    updated_at      TIMESTAMP     NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_reports_department ON reports(department_id);
CREATE INDEX IF NOT EXISTS idx_reports_category   ON reports(category_id);
CREATE INDEX IF NOT EXISTS idx_reports_year        ON reports(publication_year);
CREATE INDEX IF NOT EXISTS idx_reports_status      ON reports(status);
CREATE INDEX IF NOT EXISTS idx_reports_title       ON reports USING gin (to_tsvector('english', title));

-- --------------------------------------------------------------
-- Starter reference data — safe defaults, edit any time from
-- Admin → Departments / Admin → Categories.
-- --------------------------------------------------------------
INSERT INTO departments (name, slug, description) VALUES
    ('School of Theology & Religious Studies', 'school-of-theology-religious-studies', NULL),
    ('School of Education',                    'school-of-education', NULL),
    ('School of Business & Management',        'school-of-business-management', NULL),
    ('School of Humanities & Social Sciences',  'school-of-humanities-social-sciences', NULL),
    ('School of Natural & Applied Sciences',    'school-of-natural-applied-sciences', NULL),
    ('Open, Distance & e-Learning (ODeL)',      'odel', NULL)
ON CONFLICT (slug) DO NOTHING;

INSERT INTO categories (name, slug) VALUES
    ('Journal Article',            'journal-article'),
    ('Thesis',                     'thesis'),
    ('Dissertation',               'dissertation'),
    ('Conference Paper',           'conference-paper'),
    ('Book Chapter',                'book-chapter'),
    ('Working Paper / Technical Report', 'working-paper-technical-report')
ON CONFLICT (slug) DO NOTHING;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('hero_eyebrow',    'Est. digital archive · United Church of Zambia University'),
    ('hero_tagline',    'Knowledge for Service, catalogued for discovery.'),
    ('hero_subtext',    'The official repository of research reports, theses, dissertations and scholarly papers produced across every school of the University. Search the record, read the abstract, download the PDF.'),
    ('footer_about',    'The digital archive of research reports, theses and scholarly work produced across the United Church of Zambia University community.'),
    ('footer_tagline',  'Knowledge for Service and Fullness of Life'),
    ('contact_address', 'Lusaka, Zambia'),
    ('contact_email',   ''),
    ('contact_phone',   '')
ON CONFLICT (setting_key) DO NOTHING;
