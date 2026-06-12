<?php
// ================================================================
// scratch/seed_42_categories.php — Sync 42 Academic Categories
// Runs on CLI using XAMPP PHP.
// ================================================================

require_once dirname(__DIR__) . '/includes/db.php';

$categories = [
    [1, 'បច្ចេកវិទ្យាព័ត៌មាន', 'Information Technology', 'it', 'bi-laptop', '#1A3A5C', 1],
    [2, 'គណិតវិទ្យា', 'Mathematics', 'math', 'bi-calculator', '#8B0000', 2],
    [3, 'រូបវិទ្យា', 'Physics', 'physics', 'bi-magnet', '#1B4332', 3],
    [4, 'គីមីវិទ្យា', 'Chemistry', 'chemistry', 'bi-droplet-half', '#4A1B5C', 4],
    [5, 'ជីវវិទ្យា', 'Biology', 'biology', 'bi-tree', '#1B4332', 5],
    [6, 'ប្រវត្តិសាស្ត្រ', 'History', 'history', 'bi-book', '#5C3A1A', 6],
    [7, 'ភូមិវិទ្យា', 'Geography', 'geography', 'bi-globe', '#1A3A5C', 7],
    [8, 'អក្សរសាស្ត្រខ្មែរ', 'Khmer Literature', 'khmer-lit', 'bi-journal-text', '#8B0000', 8],
    [9, 'សេដ្ឋកិច្ច', 'Economics', 'economics', 'bi-graph-up', '#1A3A5C', 9],
    [10, 'ច្បាប់', 'Law', 'law', 'bi-bank', '#4A4A4A', 10],
    [11, 'វេជ្ជសាស្ត្រ', 'Medicine', 'medicine', 'bi-heart-pulse', '#8B0000', 11],
    [12, 'កសិកម្ម', 'Agriculture', 'agriculture', 'bi-flower1', '#1B4332', 12],
    [13, 'វិស្វកម្ម', 'Engineering', 'engineering', 'bi-wrench', '#1A3A5C', 13],
    [14, 'ពាណិជ្ជកម្ម', 'Business', 'business', 'bi-briefcase', '#5C3A1A', 14],
    [15, 'ភាសាបរទេស', 'Foreign Languages', 'languages', 'bi-translate', '#4A1B5C', 15],
    [16, 'សាសនា/ពុទ្ធសាសនា', 'Religion / Buddhism', 'religion', 'bi-peace', '#C8960C', 16],
    [17, 'សិល្បៈ', 'Arts & Culture', 'arts', 'bi-palette', '#8B0000', 17],
    [18, 'កីឡា', 'Sports', 'sports', 'bi-trophy', '#1A3A5C', 18],
    [19, 'បរិស្ថាន', 'Environment', 'environment', 'bi-leaf', '#1B4332', 19],
    [20, 'នយោបាយ', 'Politics', 'politics', 'bi-flag', '#4A4A4A', 20],
    [21, 'ទស្សនវិជ្ជា', 'Philosophy', 'philosophy', 'bi-lightbulb', '#5C3A1A', 21],
    [22, 'ចិត្តវិទ្យា', 'Psychology', 'psychology', 'bi-heart', '#8B0000', 22],
    [23, 'សង្គមវិទ្យា', 'Sociology', 'sociology', 'bi-people', '#1A3A5C', 23],
    [24, 'អប់រំ និងគរុកោសល្យ', 'Education & Pedagogy', 'education', 'bi-mortarboard', '#C8960C', 24],
    [25, 'គមនាគមន៍ និងសារព័ត៌មាន', 'Journalism & Media', 'journalism', 'bi-newspaper', '#4A4A4A', 25],
    [26, 'ហិរញ្ញវត្ថុ និងធនាគារ', 'Finance & Banking', 'finance', 'bi-cash-coin', '#1B4332', 26],
    [27, 'គណនេយ្យ', 'Accounting', 'accounting', 'bi-safe', '#1A3A5C', 27],
    [28, 'ទីផ្សារ', 'Marketing', 'marketing', 'bi-shop', '#5C3A1A', 28],
    [29, 'ទេសចរណ៍ និងបដិសណ្ឋារកិច្ច', 'Tourism & Hospitality', 'tourism', 'bi-geo-alt', '#4A1B5C', 29],
    [30, 'ស្ថាបត្យកម្ម', 'Architecture', 'architecture', 'bi-buildings', '#4A4A4A', 30],
    [31, 'តន្ត្រី', 'Music', 'music', 'bi-music-note', '#8B0000', 31],
    [32, 'វិទ្យាសាស្ត្រកុំព្យូទ័រ', 'Computer Science', 'computer-science', 'bi-code-slash', '#1A3A5C', 32],
    [33, 'សន្តិសុខបច្ចេកវិទ្យា', 'Cybersecurity', 'cybersecurity', 'bi-shield-check', '#4A4A4A', 33],
    [34, 'បញ្ញាសិប្បនិម្មិត', 'Artificial Intelligence', 'ai', 'bi-cpu', '#4A1B5C', 34],
    [35, 'ទូរគមនាគមន៍', 'Telecommunications', 'telecom', 'bi-broadcast', '#1B4332', 35],
    [36, 'អាកាសចរណ៍ និងភស្តុភារ', 'Aviation & Logistics', 'aviation', 'bi-airplane', '#1A3A5C', 36],
    [37, 'ការគ្រប់គ្រងគម្រោង', 'Project Management', 'project-management', 'bi-clipboard-check', '#5C3A1A', 37],
    [38, 'អក្សរសាស្ត្រអង់គ្លេស', 'English Literature', 'english-lit', 'bi-spellcheck', '#8B0000', 38],
    [39, 'វិទ្យាសាស្ត្រអាហារ', 'Food Science', 'food-science', 'bi-cup-hot', '#1B4332', 39],
    [40, 'វិទ្យាសាស្ត្រអវកាស', 'Space Science', 'space-science', 'bi-rocket', '#4A1B5C', 40],
    [41, 'បុរាណវិទ្យា', 'Archaeology', 'archaeology', 'bi-compass', '#5C3A1A', 41],
    [42, 'រូបភាព និងវីដេអូ', 'Photography & Film', 'photography', 'bi-camera-video', '#4A4A4A', 42],
];

// 1. Update MySQL if possible
try {
    $mysqlDsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $mysqlPdo = new PDO($mysqlDsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
    syncDatabase($mysqlPdo, $categories, 'MySQL');
} catch (PDOException $e) {
    echo "MySQL Sync Skipped/Failed: " . $e->getMessage() . "\n";
}

// 2. Update SQLite
try {
    $sqlitePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'kdlms.sqlite';
    if (file_exists($sqlitePath)) {
        $sqlitePdo = new PDO('sqlite:' . $sqlitePath);
        $sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        syncDatabase($sqlitePdo, $categories, 'SQLite');
    } else {
        echo "SQLite file not found at: $sqlitePath\n";
    }
} catch (PDOException $e) {
    echo "SQLite Sync Failed: " . $e->getMessage() . "\n";
}

function syncDatabase(PDO $pdo, array $categories, string $dbType) {
    echo "Syncing $dbType database...\n";

    // Delete categories > 42 to keep it strictly 42
    $pdo->exec("DELETE FROM categories WHERE id > 42");

    // Prepare check, update, and insert statements
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");
    
    // SQLite uses slightly different parameter/exec style or same
    $updateStmt = $pdo->prepare("UPDATE categories SET name_kh = ?, name_en = ?, slug = ?, icon = ?, color = ?, sort_order = ? WHERE id = ?");
    $insertStmt = $pdo->prepare("INSERT INTO categories (id, name_kh, name_en, slug, icon, color, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");

    foreach ($categories as $cat) {
        list($id, $kh, $en, $slug, $icon, $color, $sort) = $cat;
        
        $checkStmt->execute([$id]);
        $exists = $checkStmt->fetchColumn() > 0;

        if ($exists) {
            $updateStmt->execute([$kh, $en, $slug, $icon, $color, $sort, $id]);
        } else {
            $insertStmt->execute([$id, $kh, $en, $slug, $icon, $color, $sort]);
        }
    }

    $count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    echo "Successfully synced $count categories in $dbType.\n";

    // Sync the setting site_name_kh to 'បណ្ណាល័យឌីជីថលខ្មែរ'
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = 'site_name_kh'");
    $stmt->execute();
    $exists = $stmt->fetchColumn() > 0;
    if ($exists) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = 'បណ្ណាល័យឌីជីថលខ្មែរ' WHERE setting_key = 'site_name_kh'");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_name_kh', 'បណ្ណាល័យឌីជីថលខ្មែរ')");
        $stmt->execute();
    }
    echo "Successfully updated site_name_kh setting in $dbType.\n";
}
