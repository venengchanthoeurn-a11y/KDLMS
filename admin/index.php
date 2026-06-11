<?php
// ================================================================
// admin/index.php — Admin Dashboard
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$pdo = getPDO();

// --- Dashboard Stats ---
$totalBooks      = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE status='active'")->fetchColumn();
$totalUsers      = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$todayDownloads  = (int)$pdo->query("SELECT COUNT(*) FROM download_logs WHERE DATE(downloaded_at)=CURDATE()")->fetchColumn();

// --- Recent Downloads (last 10) ---
$recentDlStmt = $pdo->query(
    "SELECT dl.downloaded_at, dl.ip_address,
            u.name AS user_name, u.email AS user_email,
            b.title_kh AS book_title, c.name_kh AS cat_name
     FROM download_logs dl
     LEFT JOIN users u ON dl.user_id = u.id
     LEFT JOIN books b ON dl.book_id = b.id
     LEFT JOIN categories c ON b.category_id = c.id
     ORDER BY dl.downloaded_at DESC
     LIMIT 10"
);
$recentDownloads = $recentDlStmt->fetchAll();

// --- Last 7 Days Download Chart ---
$chartDays    = [];
$chartCounts  = [];
for ($i = 6; $i >= 0; $i--) {
    $date  = date('Y-m-d', strtotime("-{$i} days"));
    $label = date('D d/m', strtotime("-{$i} days"));
    $stmt  = $pdo->prepare("SELECT COUNT(*) FROM download_logs WHERE DATE(downloaded_at) = ?");
    $stmt->execute([$date]);
    $chartDays[]   = $label;
    $chartCounts[] = (int)$stmt->fetchColumn();
}

// --- Downloads by Category (Doughnut) ---
$catChartStmt = $pdo->query(
    "SELECT c.name_en AS cat_name, COUNT(dl.id) AS cnt
     FROM download_logs dl
     JOIN books b ON dl.book_id = b.id
     JOIN categories c ON b.category_id = c.id
     GROUP BY c.id
     ORDER BY cnt DESC
     LIMIT 8"
);
$catChart = $catChartStmt->fetchAll();

// --- New Users (last 5) ---
$newUsersStmt = $pdo->query(
    "SELECT id, name, email, created_at FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 5"
);
$newUsers = $newUsersStmt->fetchAll();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
include __DIR__ . '/../includes/admin_header.php';
?>

<!-- ============================================================
     STATS CARDS
     ============================================================ -->
<div class="row g-4 mb-4">
    <div class="col-6 col-xl-3">
        <div class="admin-stat-card stat-card-1">
            <div class="admin-stat-icon"><i class="bi bi-book-fill"></i></div>
            <div class="admin-stat-number"><?= number_format($totalBooks) ?></div>
            <div class="admin-stat-label">សៀវភៅសរុប · Total Books</div>
            <div class="admin-stat-change"><i class="bi bi-arrow-up-right me-1"></i>Active in library</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat-card stat-card-2">
            <div class="admin-stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="admin-stat-number"><?= number_format($totalUsers) ?></div>
            <div class="admin-stat-label">អ្នកប្រើប្រាស់ · Users</div>
            <div class="admin-stat-change"><i class="bi bi-person-check me-1"></i>Registered members</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat-card stat-card-3">
            <div class="admin-stat-icon"><i class="bi bi-download"></i></div>
            <div class="admin-stat-number"><?= number_format($todayDownloads) ?></div>
            <div class="admin-stat-label">ទាញយកថ្ងៃនេះ · Today's Downloads</div>
            <div class="admin-stat-change"><i class="bi bi-calendar-day me-1"></i><?= date('d M Y') ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat-card stat-card-4">
            <div class="admin-stat-icon"><i class="bi bi-tags-fill"></i></div>
            <div class="admin-stat-number"><?= $totalCategories ?></div>
            <div class="admin-stat-label">ប្រភេទ · Categories</div>
            <div class="admin-stat-change"><i class="bi bi-grid me-1"></i>Organized subjects</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="upload.php" class="btn-admin-gold"><i class="bi bi-cloud-upload"></i> Upload New Book</a>
    <a href="categories.php" class="btn-admin-secondary"><i class="bi bi-tags"></i> Manage Categories</a>
    <a href="logs.php" class="btn-admin-secondary"><i class="bi bi-journal-text"></i> View Logs</a>
    <a href="users.php" class="btn-admin-secondary"><i class="bi bi-people"></i> Manage Users</a>
</div>

<!-- ============================================================
     CHARTS ROW
     ============================================================ -->
<div class="row g-4 mb-4">
    <!-- Bar chart: 7-day downloads -->
    <div class="col-lg-7">
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title"><i class="bi bi-bar-chart-line"></i> ការទាញយកក្នុង 7 ថ្ងៃ · Downloads (Last 7 Days)</div>
            </div>
            <div class="admin-card-body">
                <div class="chart-container" style="height:220px;">
                    <canvas id="downloadsBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Doughnut chart: by category -->
    <div class="col-lg-5">
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title"><i class="bi bi-pie-chart"></i> ទាញយកតាមប្រភេទ · By Category</div>
            </div>
            <div class="admin-card-body">
                <div class="chart-container" style="height:220px;">
                    <canvas id="categoryDoughnutChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     RECENT DOWNLOADS + NEW USERS
     ============================================================ -->
<div class="row g-4">
    <!-- Recent Downloads -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title"><i class="bi bi-clock-history"></i> ការទាញយកថ្មីៗ · Recent Downloads</div>
                <a href="logs.php" class="btn-admin-secondary" style="padding:5px 12px;font-size:.75rem;">View All</a>
            </div>
            <div style="overflow-x:auto;">
                <table class="kdlms-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Book / Category</th>
                            <th>Time</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentDownloads as $dl): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600;font-size:.8rem;"><?= e($dl['user_name']) ?></div>
                                <div style="font-size:.7rem;color:#94A3B8;"><?= e($dl['user_email']) ?></div>
                            </td>
                            <td>
                                <div style="font-family:'Kantumruy Pro',sans-serif;font-size:.78rem;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= e($dl['book_title']) ?>"><?= e($dl['book_title']) ?></div>
                                <div style="font-size:.7rem;color:#94A3B8;"><?= e($dl['cat_name']) ?></div>
                            </td>
                            <td style="font-size:.75rem;white-space:nowrap;color:#64748B;"><?= timeAgo($dl['downloaded_at']) ?></td>
                            <td style="font-family:'Inter',sans-serif;font-size:.7rem;color:#94A3B8;"><?= e($dl['ip_address']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentDownloads)): ?>
                        <tr><td colspan="4" style="text-align:center;padding:2rem;color:#94A3B8;font-size:.82rem;">មិនទាន់មីការទាញយក · No downloads yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- New Users Widget -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title"><i class="bi bi-person-plus"></i> អ្នកប្រើថ្មី · New Members</div>
                <a href="users.php" class="btn-admin-secondary" style="padding:5px 12px;font-size:.75rem;">View All</a>
            </div>
            <div class="admin-card-body" style="padding:0.5rem 1rem;">
                <?php foreach ($newUsers as $u): ?>
                <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #F1F5F9;">
                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#8B0000,#C8960C);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0;">
                        <?= mb_strtoupper(mb_substr($u['name'], 0, 1)) ?>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.8rem;font-weight:600;color:#1E293B;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($u['name']) ?></div>
                        <div style="font-size:.68rem;color:#94A3B8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($u['email']) ?></div>
                    </div>
                    <div style="font-size:.68rem;color:#94A3B8;white-space:nowrap;"><?= timeAgo($u['created_at']) ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($newUsers)): ?>
                <div style="text-align:center;padding:2rem;color:#94A3B8;font-size:.82rem;">No users yet</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

    </div><!-- /admin-content -->
</div><!-- /admin-main -->

<!-- Bootstrap + Chart.js + Admin JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
// Initialize Charts
const downloadsData = {
    labels: <?= json_encode($chartDays, JSON_UNESCAPED_UNICODE) ?>,
    values: <?= json_encode($chartCounts) ?>
};
const categoryData = {
    labels: <?= json_encode(array_column($catChart, 'cat_name'), JSON_UNESCAPED_UNICODE) ?>,
    values: <?= json_encode(array_column($catChart, 'cnt')) ?>
};
document.addEventListener('DOMContentLoaded', () => {
    initDashboardCharts(downloadsData, categoryData);
});
</script>
</body>
</html>
