<?php
// ================================================================
// admin/logs.php — Download Logs Viewer
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pdo = getPDO();

$dateFrom  = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo    = $_GET['date_to']   ?? date('Y-m-d');
$searchUser= trim($_GET['user'] ?? '');
$catFilter = (int)($_GET['category'] ?? 0);

$where  = ['dl.downloaded_at BETWEEN ? AND ?'];
$params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

if (!empty($searchUser)) {
    $where[]  = '(u.name LIKE ? OR u.email LIKE ?)';
    $params[] = '%'.$searchUser.'%';
    $params[] = '%'.$searchUser.'%';
}
if ($catFilter > 0) {
    $where[]  = 'c.id = ?';
    $params[] = $catFilter;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// Paginate
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset  = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM download_logs dl
    LEFT JOIN users u ON dl.user_id=u.id
    LEFT JOIN books b ON dl.book_id=b.id
    LEFT JOIN categories c ON b.category_id=c.id
    $whereSQL");
$countStmt->execute($params);
$totalLogs = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalLogs / $perPage);

$stmt = $pdo->prepare(
    "SELECT dl.id, dl.downloaded_at, dl.ip_address,
            u.name AS user_name, u.email AS user_email,
            b.title_kh AS book_title, b.file_extension,
            c.name_kh AS cat_name
     FROM download_logs dl
     LEFT JOIN users u ON dl.user_id=u.id
     LEFT JOIN books b ON dl.book_id=b.id
     LEFT JOIN categories c ON b.category_id=c.id
     $whereSQL
     ORDER BY dl.downloaded_at DESC
     LIMIT ? OFFSET ?"
);
$stmt->execute(array_merge($params, [$perPage, $offset]));
$logs = $stmt->fetchAll();

// Top 5 downloaded books
$topBooks = $pdo->query(
    "SELECT b.title_kh, COUNT(dl.id) AS cnt FROM download_logs dl
     JOIN books b ON dl.book_id=b.id
     GROUP BY dl.book_id ORDER BY cnt DESC LIMIT 5"
)->fetchAll();

// Top 5 active users
$topUsers = $pdo->query(
    "SELECT u.name, u.email, COUNT(dl.id) AS cnt FROM download_logs dl
     JOIN users u ON dl.user_id=u.id
     GROUP BY dl.user_id ORDER BY cnt DESC LIMIT 5"
)->fetchAll();

$categories = getCategories();
$pageTitle  = 'Download Logs';
$activeNav  = 'logs';
include '../includes/admin_header.php';
?>

<!-- Filter form -->
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div class="admin-card-title"><i class="bi bi-funnel"></i> Filter Logs</div>
    </div>
    <div class="admin-card-body">
        <form method="GET" action="logs.php" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
            <div>
                <label style="font-family:'Inter',sans-serif;font-size:.75rem;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Date From</label>
                <input type="date" name="date_from" class="admin-form-input" style="width:auto;" value="<?= e($dateFrom) ?>">
            </div>
            <div>
                <label style="font-family:'Inter',sans-serif;font-size:.75rem;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Date To</label>
                <input type="date" name="date_to" class="admin-form-input" style="width:auto;" value="<?= e($dateTo) ?>">
            </div>
            <div>
                <label style="font-family:'Inter',sans-serif;font-size:.75rem;font-weight:600;color:#374151;display:block;margin-bottom:4px;">User (name/email)</label>
                <input type="text" name="user" class="admin-form-input" style="width:200px;" placeholder="Search user..." value="<?= e($searchUser) ?>">
            </div>
            <div>
                <label style="font-family:'Inter',sans-serif;font-size:.75rem;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Category</label>
                <select name="category" class="admin-form-select" style="width:auto;">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $catFilter==$cat['id']?'selected':''?>><?= e($cat['name_kh']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn-admin-primary"><i class="bi bi-search"></i> Filter</button>
                <a href="logs.php" class="btn-admin-secondary"><i class="bi bi-x"></i> Reset</a>
                <button type="button" class="btn-admin-secondary" onclick="exportTableToCSV('logsTable','kdlms-logs.csv')">
                    <i class="bi bi-download"></i> Export CSV
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title"><i class="bi bi-trophy"></i> សៀវភៅទាញយកច្រើន · Top Books</div>
            </div>
            <div class="admin-card-body" style="padding:0.5rem 1rem;">
                <?php foreach ($topBooks as $i => $tb): ?>
                <div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #F1F5F9;">
                    <span style="width:22px;height:22px;border-radius:50%;background:<?= ['#C8960C','#94A3B8','#92400E','#374151','#64748B'][$i] ?>;color:white;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;flex-shrink:0;"><?= $i+1 ?></span>
                    <span style="flex:1;font-family:'Kantumruy Pro',sans-serif;font-size:.78rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= e($tb['title_kh']) ?>"><?= e($tb['title_kh']) ?></span>
                    <span style="font-family:'Inter',sans-serif;font-weight:700;font-size:.78rem;color:#1A3A5C;"><?= number_format($tb['cnt']) ?>×</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title"><i class="bi bi-person-check"></i> អ្នកប្រើ Active · Top Users</div>
            </div>
            <div class="admin-card-body" style="padding:0.5rem 1rem;">
                <?php foreach ($topUsers as $i => $tu): ?>
                <div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #F1F5F9;">
                    <span style="width:22px;height:22px;border-radius:50%;background:<?= ['#8B0000','#94A3B8','#92400E','#374151','#64748B'][$i] ?>;color:white;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0;"><?= mb_strtoupper(mb_substr($tu['name'],0,1)) ?></span>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.78rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($tu['name']) ?></div>
                        <div style="font-size:.65rem;color:#94A3B8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($tu['email']) ?></div>
                    </div>
                    <span style="font-family:'Inter',sans-serif;font-weight:700;font-size:.78rem;color:#1A3A5C;"><?= number_format($tu['cnt']) ?>×</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Logs table -->
<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">
            <i class="bi bi-journal-text"></i>
            Download Logs (<?= number_format($totalLogs) ?> records · <?= e($dateFrom) ?> → <?= e($dateTo) ?>)
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="kdlms-table" id="logsTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Book</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Time</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td style="font-size:.72rem;color:#94A3B8;font-family:'Inter',sans-serif;"><?= $log['id'] ?></td>
                    <td>
                        <div style="font-weight:600;font-size:.8rem;font-family:'Kantumruy Pro',sans-serif;"><?= e($log['user_name']) ?></div>
                        <div style="font-size:.68rem;color:#94A3B8;"><?= e($log['user_email']) ?></div>
                    </td>
                    <td style="max-width:180px;">
                        <div style="font-family:'Kantumruy Pro',sans-serif;font-size:.78rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= e($log['book_title']) ?>"><?= e($log['book_title']) ?></div>
                    </td>
                    <td style="font-family:'Kantumruy Pro',sans-serif;font-size:.75rem;color:#64748B;"><?= e($log['cat_name']) ?></td>
                    <td><span class="badge bg-<?= getFileExtBadgeClass($log['file_extension']) ?>"><?= strtoupper(e($log['file_extension'])) ?></span></td>
                    <td style="font-size:.75rem;white-space:nowrap;font-family:'Inter',sans-serif;color:#374151;"><?= e($log['downloaded_at']) ?></td>
                    <td style="font-size:.72rem;color:#94A3B8;font-family:'Inter',sans-serif;"><?= e($log['ip_address']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                <tr><td colspan="7" style="text-align:center;padding:3rem;color:#94A3B8;">No logs found for this date range.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="admin-pagination">
        <span><?= number_format($totalLogs) ?> records · Page <?= $page ?> of <?= $totalPages ?></span>
        <div style="display:flex;gap:4px;">
            <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$p])) ?>"
               class="page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
