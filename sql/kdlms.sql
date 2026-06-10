-- ================================================================
-- KDLMS - Khmer Digital Library Management System
-- Database Setup Script
-- Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
-- Charset: utf8mb4_unicode_ci
-- ================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Create and select database
CREATE DATABASE IF NOT EXISTS `kdlms`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `kdlms`;

-- ================================================================
-- Table: users — អ្នកប្រើប្រាស់ទាំងអស់
-- ================================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`            INT          NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(150) NOT NULL,
  `email`         VARCHAR(200) NOT NULL,
  `password`      VARCHAR(255) NOT NULL,
  `role`          ENUM('superadmin','admin','user') NOT NULL DEFAULT 'user',
  `is_verified`   TINYINT(1)   NOT NULL DEFAULT 0,
  `verify_token`  VARCHAR(100)          DEFAULT NULL,
  `reset_token`   VARCHAR(100)          DEFAULT NULL,
  `reset_expires` DATETIME              DEFAULT NULL,
  `profile_pic`   VARCHAR(255)          DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login`    DATETIME              DEFAULT NULL,
  `status`        ENUM('active','banned') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `idx_users_role`   (`role`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table: categories — ប្រភេទសៀវភៅ
-- ================================================================
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id`          INT          NOT NULL AUTO_INCREMENT,
  `name_kh`     VARCHAR(200) NOT NULL,
  `name_en`     VARCHAR(200) NOT NULL,
  `slug`        VARCHAR(200) NOT NULL,
  `description` TEXT                  DEFAULT NULL,
  `icon`        VARCHAR(100)          DEFAULT 'bi-folder',
  `color`       VARCHAR(20)           DEFAULT '#8B0000',
  `parent_id`   INT                   DEFAULT NULL,
  `sort_order`  INT          NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_cat_parent` (`parent_id`),
  KEY `idx_cat_sort`   (`sort_order`),
  CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table: books — ឯកសារ/សៀវភៅ
-- ================================================================
DROP TABLE IF EXISTS `books`;
CREATE TABLE `books` (
  `id`             INT          NOT NULL AUTO_INCREMENT,
  `title_kh`       VARCHAR(500) NOT NULL,
  `title_en`       VARCHAR(500)          DEFAULT NULL,
  `author`         VARCHAR(300)          DEFAULT NULL,
  `category_id`    INT                   DEFAULT NULL,
  `description`    TEXT                  DEFAULT NULL,
  `cover_image`    VARCHAR(300)          DEFAULT NULL,
  `file_path`      VARCHAR(500) NOT NULL,
  `file_name`      VARCHAR(300) NOT NULL,
  `file_size`      BIGINT       NOT NULL DEFAULT 0,
  `file_type`      VARCHAR(50)  NOT NULL,
  `file_extension` VARCHAR(10)  NOT NULL,
  `language`       ENUM('kh','en','both','other') NOT NULL DEFAULT 'kh',
  `tags`           TEXT                  DEFAULT NULL,
  `download_count` INT          NOT NULL DEFAULT 0,
  `is_featured`    TINYINT(1)   NOT NULL DEFAULT 0,
  `status`         ENUM('active','hidden','deleted') NOT NULL DEFAULT 'active',
  `uploaded_by`    INT                   DEFAULT NULL,
  `published_year` YEAR                  DEFAULT NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_books_category` (`category_id`),
  KEY `idx_books_status`   (`status`),
  KEY `idx_books_featured` (`is_featured`),
  KEY `idx_books_downloads`(`download_count`),
  KEY `idx_books_lang`     (`language`),
  FULLTEXT KEY `idx_books_search` (`title_kh`,`title_en`,`author`,`tags`),
  CONSTRAINT `fk_books_category`    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_books_uploaded_by` FOREIGN KEY (`uploaded_by`)  REFERENCES `users`      (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table: download_logs — កំណត់ហេតុទាញយក
-- ================================================================
DROP TABLE IF EXISTS `download_logs`;
CREATE TABLE `download_logs` (
  `id`            BIGINT       NOT NULL AUTO_INCREMENT,
  `user_id`       INT                   DEFAULT NULL,
  `book_id`       INT                   DEFAULT NULL,
  `downloaded_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address`    VARCHAR(45)           DEFAULT NULL,
  `user_agent`    VARCHAR(500)          DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_logs_user`  (`user_id`),
  KEY `idx_logs_book`  (`book_id`),
  KEY `idx_logs_date`  (`downloaded_at`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_logs_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table: settings — ការកំណត់ប្រព័ន្ធ
-- ================================================================
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id`            INT          NOT NULL AUTO_INCREMENT,
  `setting_key`   VARCHAR(100) NOT NULL,
  `setting_value` TEXT                  DEFAULT NULL,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ================================================================
-- SEED DATA: Default System Settings
-- ================================================================
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name_kh',         'ប្រព័ន្ធបណ្ណាល័យឌីជីថលខ្មែរ'),
('site_name_en',         'Khmer Digital Library Management System'),
('site_tagline_kh',      'ចំណេះដឹងគ្មានព្រំដែន'),
('site_tagline_en',      'Knowledge Without Boundaries'),
('allow_registration',   '1'),
('require_email_verify', '0'),
('max_file_size_mb',     '200'),
('allowed_extensions',   'pdf,docx,epub,txt,zip,mp4,pptx,xlsx'),
('books_per_page',       '20'),
('maintenance_mode',     '0'),
('site_logo',            'logo.png'),
('admin_email',          'admin@kdlms.kh'),
('contact_email',        'contact@kdlms.kh');

-- ================================================================
-- SEED DATA: 20 Default Categories (ប្រភេទឯកសារ)
-- ================================================================
INSERT INTO `categories` (`name_kh`, `name_en`, `slug`, `icon`, `color`, `sort_order`) VALUES
('បច្ចេកវិទ្យាព័ត៌មាន',  'Information Technology', 'it',        'bi-laptop',       '#1A3A5C', 1),
('គណិតវិទ្យា',            'Mathematics',            'math',      'bi-calculator',   '#8B0000', 2),
('រូបវិទ្យា',             'Physics',                'physics',   'bi-atom',         '#1B4332', 3),
('គីមីវិទ្យា',            'Chemistry',              'chemistry', 'bi-flask',        '#4A1B5C', 4),
('ជីវវិទ្យា',             'Biology',                'biology',   'bi-tree',         '#1B4332', 5),
('ប្រវត្តិសាស្ត្រ',       'History',                'history',   'bi-book',         '#5C3A1A', 6),
('ភូមិវិទ្យា',            'Geography',              'geography', 'bi-globe',        '#1A3A5C', 7),
('អក្សរសាស្ត្រខ្មែរ',    'Khmer Literature',       'khmer-lit', 'bi-journal-text', '#8B0000', 8),
('សេដ្ឋកិច្ច',            'Economics',              'economics', 'bi-graph-up',     '#1A3A5C', 9),
('ច្បាប់',                'Law',                    'law',       'bi-scales',       '#4A4A4A', 10),
('វេជ្ជសាស្ត្រ',          'Medicine',               'medicine',  'bi-heart-pulse',  '#8B0000', 11),
('កសិកម្ម',               'Agriculture',            'agriculture','bi-flower1',     '#1B4332', 12),
('វិស្វករ',               'Engineering',            'engineering','bi-wrench',      '#1A3A5C', 13),
('ពាណិជ្ជកម្ម',           'Business',               'business',  'bi-briefcase',    '#5C3A1A', 14),
('ភាសាបរទេស',             'Foreign Languages',      'languages', 'bi-translate',    '#4A1B5C', 15),
('សាសនា/ពុទ្ធសាសនា',     'Religion / Buddhism',    'religion',  'bi-peace',        '#C8960C', 16),
('សិល្បៈ',                'Arts & Culture',         'arts',      'bi-palette',      '#8B0000', 17),
('កីឡា',                  'Sports',                 'sports',    'bi-trophy',       '#1A3A5C', 18),
('បរិស្ថាន',              'Environment',            'environment','bi-leaf',        '#1B4332', 19),
('នយោបាយ',               'Politics',               'politics',  'bi-flag',         '#4A4A4A', 20);

-- ================================================================
-- SEED DATA: Default Superadmin Account
-- Password: Admin@2025 (bcrypt hash)
-- ================================================================
INSERT INTO `users` (`name`, `email`, `password`, `role`, `is_verified`, `status`) VALUES
('អេង ចាន់ធឿន (Vthe)',
 'admin@kdlms.kh',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'superadmin', 1, 'active');

-- ================================================================
-- SEED DATA: Sample Books (for demo)
-- ================================================================
INSERT INTO `books` (`title_kh`, `title_en`, `author`, `category_id`, `description`, `file_path`, `file_name`, `file_size`, `file_type`, `file_extension`, `language`, `tags`, `download_count`, `is_featured`, `status`, `uploaded_by`, `published_year`) VALUES
('គ្រឹះនៃការសរសេរកូដ PHP', 'PHP Programming Fundamentals', 'អេង ចាន់ធឿន', 1, 'ការបង្រៀន PHP ពីដំបូង ដល់កម្រិតខ្ពស់', 'it/sample-php.pdf', 'php-fundamentals.pdf', 2048576, 'application/pdf', 'pdf', 'kh', 'php,programming,web', 245, 1, 'active', 1, 2024),
('គណិតវិទ្យាថ្នាក់ទី១២', 'Grade 12 Mathematics', 'ក្រសួងអប់រំ', 2, 'មេរៀនគណិតវិទ្យាថ្នាក់វិទ្យាល័យ', 'math/grade12-math.pdf', 'grade12-math.pdf', 5120000, 'application/pdf', 'pdf', 'kh', 'math,grade12,school', 1205, 1, 'active', 1, 2023),
('ប្រវត្តិសាស្ត្រខ្មែរ', 'Khmer History', 'ជិន ដារ៉ា', 6, 'ប្រវត្តិជាតិខ្មែរតាំងពីបុរាណ', 'history/khmer-history.pdf', 'khmer-history.pdf', 3145728, 'application/pdf', 'pdf', 'kh', 'history,khmer,ancient', 892, 1, 'active', 1, 2022),
('អក្សរសាស្ត្រខ្មែរបុរាណ', 'Ancient Khmer Literature', 'ម៉ម ចន្ថាណា', 8, 'ស្នាដៃអក្សរសាស្ត្រខ្មែរបុរាណ', 'khmer-lit/ancient-lit.pdf', 'ancient-khmer-lit.pdf', 4194304, 'application/pdf', 'pdf', 'kh', 'literature,khmer,poetry', 678, 1, 'active', 1, 2023),
('ជីវវិទ្យាមូលដ្ឋាន', 'Basic Biology', 'លី សុផល', 5, 'មូលដ្ឋានវិទ្យាសាស្ត្រជីវវិទ្យា', 'biology/basic-bio.pdf', 'basic-biology.pdf', 6291456, 'application/pdf', 'pdf', 'kh', 'biology,science,cells', 534, 1, 'active', 1, 2024),
('ច្បាប់រដ្ឋធម្មនុញ្ញ', 'Constitutional Law', 'ហោ សម្បត្ដិ', 10, 'ច្បាប់រដ្ឋធម្មនុញ្ញនៃព្រះរាជាណាចក្រកម្ពុជា', 'law/constitution.pdf', 'constitution-law.pdf', 1572864, 'application/pdf', 'pdf', 'kh', 'law,constitution,cambodia', 1456, 1, 'active', 1, 2022),
('Database Design with MySQL', 'Database Design with MySQL', 'John Smith', 1, 'Complete guide to MySQL database design', 'it/mysql-design.pdf', 'mysql-database-design.pdf', 8388608, 'application/pdf', 'pdf', 'en', 'mysql,database,design,sql', 389, 1, 'active', 1, 2024),
('ពុទ្ធសាសនា និងសីលធម៌', 'Buddhism and Ethics', 'ព្រះអាចារ្យ ស៊ុន', 16, 'ការបង្រៀនពុទ្ធសាសនា និងសីលធម៌', 'religion/buddhism.pdf', 'buddhism-ethics.pdf', 2621440, 'application/pdf', 'pdf', 'kh', 'buddhism,ethics,religion', 923, 1, 'active', 1, 2023),
('វិទ្យាសាស្ត្របរិស្ថាន', 'Environmental Science', 'ចាន់ ណារ', 19, 'ការយល់ដឹងអំពីបរិស្ថាន', 'environment/env-science.pdf', 'environmental-science.pdf', 3670016, 'application/pdf', 'pdf', 'kh', 'environment,ecology,climate', 312, 0, 'active', 1, 2024),
('Web Development Bootcamp', 'Web Development Bootcamp', 'Angela Yu', 1, 'Full stack web development course materials', 'it/web-bootcamp.pdf', 'web-dev-bootcamp.pdf', 12582912, 'application/pdf', 'pdf', 'en', 'web,html,css,javascript', 1678, 1, 'active', 1, 2024);

-- ================================================================
-- SEED DATA: Sample Download Logs
-- ================================================================
-- Note: These are demo logs; real logs generated dynamically.
-- (Requires user IDs > 1; skipped for minimal seed)

-- ================================================================
-- Done! Run: mysql -u root kdlms < kdlms.sql
-- Or import via phpMyAdmin
-- ================================================================
