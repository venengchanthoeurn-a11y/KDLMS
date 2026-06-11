<?php
// ================================================================
// includes/db.php — PDO Database Connection Singleton
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

// ─── UTF-8 / Khmer Encoding Fix ─────────────────────────────────
// Force UTF-8 for all PHP string operations and HTTP responses.
// This MUST run before any output so Khmer text displays correctly.
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Send UTF-8 Content-Type if not already sent (CLI safe)
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
// ────────────────────────────────────────────────────────────────


// ─── Database Configuration (env vars → XAMPP fallback) ─────────
// On Vercel: set DB_HOST, DB_NAME, DB_USER, DB_PASS as env vars.
// On XAMPP:  uses localhost defaults automatically.
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'kdlms');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');

// ─── File Upload Path ────────────────────────────────────────────
define('UPLOAD_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);

// ─── Base URL ────────────────────────────────────────────────────
// Auto-detects: Vercel (HTTPS) vs XAMPP (http://localhost/MSDL)
function detectBaseUrl(): string {
    // Explicit env var set in Vercel dashboard
    if (getenv('APP_BASE_URL')) return rtrim(getenv('APP_BASE_URL'), '/');

    if (PHP_SAPI === 'cli') return 'http://localhost/MSDL';

    $proto  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';

    // Vercel deployment — no subfolder
    if (str_contains($host, 'vercel.app') || str_contains($host, '.vercel.app') ||
        getenv('VERCEL') || getenv('VERCEL_ENV')) {
        return $proto . '://' . $host;
    }

    // XAMPP: derive base from script path (e.g. /MSDL/index.php → /MSDL)
    $base = dirname($script);
    if ($base === '/' || $base === '\\') $base = '';
    // Handle admin subfolder
    if (str_ends_with($base, '/admin')) $base = substr($base, 0, -6);
    return $proto . '://' . $host . $base;
}
define('BASE_URL', detectBaseUrl());

// ─── App version ─────────────────────────────────────────────────
define('APP_VERSION', '1.0');


/**
 * getPDO() — Returns a singleton PDO connection.
 * ការតភ្ជាប់ PDO តែមួយ ដើម្បីសន្សំបរិក្ខារ។
 *
 * @return PDO
 */
function getPDO(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        // We fall back to SQLite if DB_HOST is not set or MySQL connection fails,
        // unless a non-default/non-localhost DB_HOST is explicitly configured (e.g. remote MySQL in production)
        $useSqliteFallback = true;
        if (getenv('DB_HOST') && getenv('DB_HOST') !== 'localhost' && getenv('DB_HOST') !== '127.0.0.1') {
            $useSqliteFallback = false;
        }

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $mysqlOptions = $options;
            $mysqlOptions[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $mysqlOptions);
        } catch (PDOException $e) {
            if ($useSqliteFallback) {
                // Try to find the SQLite database file
                $sqlitePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'kdlms.sqlite';
                if (file_exists($sqlitePath)) {
                    try {
                        $pdo = new PDO('sqlite:' . $sqlitePath);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                        return $pdo;
                    } catch (PDOException $sqle) {
                        error_log('SQLite fallback failed: ' . $sqle->getMessage());
                    }
                }
            }

            // If SQLite fallback is disabled or failed, log and show MySQL error
            error_log('KDLMS DB Error: ' . $e->getMessage());
            die('<div style="font-family:sans-serif;padding:40px;text-align:center;color:#8B0000;">
                <h2>⚠️ មិនអាចភ្ជាប់ទិន្នន័យ (Database Connection Failed)</h2>
                <p>សូម Start MySQL ក្នុង XAMPP Control Panel ហើយ Import kdlms.sql</p>
                <p style="color:#888;font-size:12px;">Please start MySQL in XAMPP and import sql/kdlms.sql</p>
            </div>');
        }
    }

    return $pdo;
}
