<?php
// ================================================================
// includes/functions.php — Utility & Helper Functions
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

require_once __DIR__ . '/db.php';

// ================================================================
// SANITIZATION — ការសម្អាត Input
// ================================================================

/**
 * sanitize() — Strip tags and encode special characters.
 * សម្អាត Input ពី XSS attack ។
 */
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * e() — Short alias for sanitize() used in templates.
 */
function e(?string $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// ================================================================
// BOOK FUNCTIONS — មុខងារសៀវភៅ
// ================================================================

/**
 * getBookById() — Fetch a single book by ID (active only).
 * ទទួលសៀវភៅមួយ ដោយប្រើ ID ។
 */
function getBookById(int $id): ?array {
    $pdo  = getPDO();
    $stmt = $pdo->prepare(
        "SELECT b.*, c.name_kh AS cat_name_kh, c.name_en AS cat_name_en, c.slug AS cat_slug
         FROM books b
         LEFT JOIN categories c ON b.category_id = c.id
         WHERE b.id = ? AND b.status = 'active'"
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * getFeaturedBooks() — Fetch featured books for homepage.
 * ទទួលសៀវភៅដែលលេចធ្លោ សម្រាប់ Homepage ។
 */
function getFeaturedBooks(int $limit = 8): array {
    $pdo  = getPDO();
    $stmt = $pdo->prepare(
        "SELECT b.*, c.name_kh AS cat_name_kh, c.name_en AS cat_name_en, c.color AS cat_color
         FROM books b
         LEFT JOIN categories c ON b.category_id = c.id
         WHERE b.status = 'active' AND b.is_featured = 1
         ORDER BY b.download_count DESC
         LIMIT ?"
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * getBooks() — Paginated book list with filters and FULLTEXT search.
 * ទទួលបញ្ជីសៀវភៅ ជាមួយ Filter, Search, និង Pagination ។
 */
function getBooks(array $opts = []): array {
    $pdo       = getPDO();
    $page      = max(1, (int)($opts['page'] ?? 1));
    $perPage   = (int)getSetting('books_per_page', 20);
    $offset    = ($page - 1) * $perPage;
    $catId     = (int)($opts['category_id'] ?? 0);
    $lang      = $opts['language'] ?? '';
    $ext       = $opts['extension'] ?? '';
    $sort      = $opts['sort'] ?? 'newest';
    $search    = trim($opts['search'] ?? '');

    $where  = ["b.status = 'active'"];
    $params = [];

    if ($catId > 0) {
        $where[]  = 'b.category_id = ?';
        $params[] = $catId;
    }
    if (in_array($lang, ['kh','en','both','other'])) {
        $where[]  = 'b.language = ?';
        $params[] = $lang;
    }
    if (!empty($ext)) {
        $where[]  = 'b.file_extension = ?';
        $params[] = $ext;
    }

    $searchRelevance = '';
    $isSqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');

    if (!empty($search)) {
        if ($isSqlite) {
            $where[] = '(b.title_kh LIKE ? OR b.title_en LIKE ? OR b.author LIKE ? OR b.tags LIKE ?)';
            $searchParam = '%' . $search . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $searchRelevance = ', 0 AS relevance';
        } else {
            $where[]         = 'MATCH(b.title_kh, b.title_en, b.author, b.tags) AGAINST(? IN BOOLEAN MODE)';
            $params[]        = $search . '*';
            $searchRelevance = ', MATCH(b.title_kh, b.title_en, b.author, b.tags) AGAINST(? IN BOOLEAN MODE) AS relevance';
            $params[]        = $search . '*'; // for SELECT
        }
    }

    $orderMap = [
        'newest'    => 'b.created_at DESC',
        'popular'   => 'b.download_count DESC',
        'alpha_asc' => 'b.title_kh ASC',
        'alpha_desc'=> 'b.title_kh DESC',
        'relevance' => (!empty($search) ? 'relevance DESC' : 'b.created_at DESC'),
    ];
    $orderBy = $orderMap[$sort] ?? 'b.created_at DESC';

    $whereSQL = implode(' AND ', $where);

    // Count total
    if (!empty($search) && !$isSqlite) {
        $countParamsCopy = $params;
        array_pop($countParamsCopy); // remove the second search param for MySQL
    } else {
        $countParamsCopy = $params;
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM books b WHERE $whereSQL");
    $countStmt->execute($countParamsCopy);
    $total = (int)$countStmt->fetchColumn();

    // Reorder params: relevance comes after WHERE, before LIMIT
    $selectParams = $params;
    $selectParams[] = $perPage;
    $selectParams[] = $offset;

    $sql = "SELECT b.* $searchRelevance,
                   c.name_kh AS cat_name_kh, c.name_en AS cat_name_en,
                   c.color AS cat_color, c.slug AS cat_slug
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            WHERE $whereSQL
            ORDER BY $orderBy
            LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($selectParams);
    $books = $stmt->fetchAll();

    return [
        'books'      => $books,
        'total'      => $total,
        'page'       => $page,
        'per_page'   => $perPage,
        'total_pages'=> (int)ceil($total / $perPage),
    ];
}

// ================================================================
// CATEGORY FUNCTIONS — មុខងារប្រភេទ
// ================================================================

/**
 * getCategories() — Fetch all categories (parent ones first).
 * ទទួលប្រភេទទាំងអស់ ។
 */
function getCategories(bool $withCount = false): array {
    $pdo = getPDO();
    if ($withCount) {
        $stmt = $pdo->query(
            "SELECT c.*, COUNT(b.id) AS book_count
             FROM categories c
             LEFT JOIN books b ON b.category_id = c.id AND b.status = 'active'
             WHERE c.parent_id IS NULL
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.name_en ASC"
        );
    } else {
        $stmt = $pdo->query(
            "SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order ASC, name_en ASC"
        );
    }
    return $stmt->fetchAll();
}

/**
 * getCategoryBySlug() — Fetch category by slug.
 */
function getCategoryBySlug(string $slug): ?array {
    $pdo  = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

// ================================================================
// DOWNLOAD FUNCTIONS — មុខងារការទាញយក
// ================================================================

/**
 * logDownload() — Record a download event in download_logs.
 * កំណត់ហេតុ Download ចូល download_logs ។
 */
function logDownload(int $bookId, int $userId, string $ip): void {
    $pdo  = getPDO();
    $ua   = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    $stmt = $pdo->prepare(
        "INSERT INTO download_logs (user_id, book_id, ip_address, user_agent)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$userId, $bookId, $ip, $ua]);
}

/**
 * incrementDownloadCount() — Increment books.download_count safely.
 * បន្ថែម download_count ក្នុងតារាង books ។
 */
function incrementDownloadCount(int $bookId): void {
    $pdo  = getPDO();
    $stmt = $pdo->prepare("UPDATE books SET download_count = download_count + 1 WHERE id = ?");
    $stmt->execute([$bookId]);
}

// ================================================================
// SETTINGS FUNCTIONS — ការកំណត់ប្រព័ន្ធ
// ================================================================

/**
 * getSetting() — Get a setting value from the settings table.
 * ទទួលតម្លៃ Setting ពីតារាង settings ។
 */
function getSetting(string $key, string $default = ''): string {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];

    $pdo  = getPDO();
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $val  = $stmt->fetchColumn();
    $cache[$key] = ($val !== false) ? $val : $default;
    return $cache[$key];
}

/**
 * setSetting() — Update or insert a setting.
 * ធ្វើបច្ចុប្បន្នភាព Setting ។
 */
function setSetting(string $key, string $value): void {
    $pdo  = getPDO();
    // Database-agnostic upsert (compatible with MySQL and SQLite)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $exists = $stmt->fetchColumn() > 0;

    if ($exists) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
}

// ================================================================
// STATISTICS — ស្ថិតិ
// ================================================================

/**
 * getStats() — Return array of homepage/dashboard stats.
 * ទទួលស្ថិតិ ៤ ប្រភេទ ។
 */
function getStats(): array {
    $pdo = getPDO();
    return [
        'books'     => (int)$pdo->query("SELECT COUNT(*) FROM books WHERE status='active'")->fetchColumn(),
        'categories'=> (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
        'users'     => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
        'downloads' => (int)$pdo->query("SELECT SUM(download_count) FROM books WHERE status='active'")->fetchColumn(),
    ];
}

// ================================================================
// FILE UTILITIES — ជំនួយឯកសារ
// ================================================================

/**
 * formatFileSize() — Convert bytes to human-readable format.
 * បំប្លែង bytes ទៅជាអក្សរ KB/MB/GB ។
 */
function formatFileSize(int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576)    return number_format($bytes / 1048576, 2)    . ' MB';
    if ($bytes >= 1024)       return number_format($bytes / 1024, 2)       . ' KB';
    return $bytes . ' B';
}

/**
 * generateUUID() — Create a unique filename with UUID prefix.
 * បង្កើតឈ្មោះឯកសារ UUID ដើម្បីការពារ Path Attack ។
 */
function generateUUID(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * getFileExtBadgeClass() — Return Bootstrap badge color class for a file extension.
 */
function getFileExtBadgeClass(string $ext): string {
    $map = [
        'pdf'  => 'danger',
        'docx' => 'primary',
        'doc'  => 'primary',
        'epub' => 'success',
        'txt'  => 'secondary',
        'zip'  => 'warning',
        'mp4'  => 'info',
        'pptx' => 'warning',
        'xlsx' => 'success',
    ];
    return $map[strtolower($ext)] ?? 'dark';
}

/**
 * getAllowedExtensions() — Get allowed file extensions from settings.
 */
function getAllowedExtensions(): array {
    $val = getSetting('allowed_extensions', 'pdf,docx,epub,txt,zip,mp4,pptx,xlsx');
    return array_map('trim', explode(',', $val));
}

/**
 * getClientIp() — Get real client IP address.
 */
function getClientIp(): string {
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            return $_SERVER[$key];
        }
    }
    return '0.0.0.0';
}

/**
 * timeAgo() — Convert timestamp to "X minutes ago" format (bilingual).
 */
function timeAgo(string $datetime): string {
    $now  = time();
    $then = strtotime($datetime);
    $diff = $now - $then;

    if ($diff < 60)     return $diff . ' វិនាទីមុន';
    if ($diff < 3600)   return floor($diff/60)   . ' នាទីមុន';
    if ($diff < 86400)  return floor($diff/3600)  . ' ម៉ោងមុន';
    if ($diff < 604800) return floor($diff/86400) . ' ថ្ងៃមុន';
    return date('d/m/Y', $then);
}

/**
 * redirect() — Send location header and exit.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * flashMessage() — Set a flash message in session.
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * getFlash() — Get and clear flash message.
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
