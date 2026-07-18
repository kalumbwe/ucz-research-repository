USE defaultdb;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin','admin') DEFAULT 'admin',
    status ENUM('active','disabled') DEFAULT 'active',
    last_login DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    code VARCHAR(20) NULL,
    description TEXT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS research_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(300) NOT NULL,
    abstract TEXT NOT NULL,
    description TEXT NULL,
    author_name VARCHAR(200) NOT NULL,
    co_authors VARCHAR(300) NULL,
    supervisor VARCHAR(200) NULL,
    department_id INT NOT NULL,
    degree_type ENUM('Undergraduate','Masters','PhD','Staff Research','Conference Paper','Journal Article') NOT NULL DEFAULT 'Undergraduate',
    academic_year VARCHAR(9) NOT NULL,
    publication_date DATE NOT NULL,
    keywords VARCHAR(400) NULL,
    language VARCHAR(50) DEFAULT 'English',
    pages INT NULL,
    isbn_issn VARCHAR(50) NULL,
    file_name VARCHAR(255) NOT NULL,
    original_file_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    cover_image VARCHAR(255) NULL,
    status ENUM('published','draft','archived') DEFAULT 'published',
    access_level ENUM('public','restricted') DEFAULT 'public',
    views INT DEFAULT 0,
    downloads INT DEFAULT 0,
    uploaded_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (uploaded_by) REFERENCES admins(id) ON DELETE RESTRICT,
    FULLTEXT KEY ft_search (title, abstract, keywords, author_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS download_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    downloaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES research_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_name VARCHAR(150) DEFAULT 'UCZ University Research Repository',
    site_tagline VARCHAR(255) DEFAULT 'Advancing Knowledge in Faith and Scholarship',
    contact_email VARCHAR(150) NULL,
    reports_per_page INT DEFAULT 12,
    logo_path VARCHAR(255) NULL
) ENGINE=InnoDB;

INSERT INTO settings (site_name, site_tagline, contact_email, reports_per_page)
VALUES ('UCZ University Research Repository', 'Advancing Knowledge in Faith and Scholarship', 'research@uczuniversity.ac.zm', 12);

INSERT INTO departments (name, code, description) VALUES
('Theology and Religious Studies', 'THEO', 'Studies in theology, biblical studies and ministry formation'),
('Education', 'EDUC', 'Teacher education and educational leadership'),
('Business and Administration', 'BUSN', 'Business, accounting, economics and management'),
('Science and Technology', 'SCIT', 'Computer science, mathematics and applied sciences'),
('Social Sciences', 'SOSC', 'Sociology, development studies and social work'),
('Health Sciences', 'HLTH', 'Nursing, public health and allied health sciences');

INSERT INTO admins (full_name, username, email, password, role) VALUES
('Repository Administrator', 'admin', 'admin@uczuniversity.ac.zm', '$2b$10$XDVnq4BQJp86N2K.JGttVONd/PcvCuRTwPiAuQGPAMT9HwMjctGQG', 'super_admin');