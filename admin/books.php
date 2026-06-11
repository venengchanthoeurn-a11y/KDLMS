<?php
// ================================================================
// admin/books.php — Manage Books / Files
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pdo = getPDO();

// --- Handle actions ---
$action = $_GET['action'] ?? '';
$bookId = (int)($_GET['id'] ?? 0);

if ($bookId > 0 && in_array($action, ['delete','hide','show'])) {
    $statusMap = ['delete'=>'deleted', 'hide'=>'hidden', 'show'=>'active'];
    $newStatus = $statusMap[$action];
    $pdo->prepare("UPDATE books SET status=? WHERE id=?")->execute([$newStatus, $bookId]);
    setFlash('success', "Book status updated to '{$newStatus}'.");
    redirect(BASE_URL . '/admin/books.php');
}

// --- Bulk actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    verifyCsrfToken();
    $bulkAction = $_POST['bulk_action'];
    $ids        = array_filter(array_map('intval', $_POST['book_ids'] ?? []));
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        if ($bulkAction === 'delete') {
            $pdo->prepare("UPDATE books SET status='deleted' WHERE id IN ($placeholders)")->execute($ids);
            setFlash('success', count($ids) . ' books deleted (soft).');
        } elseif ($bulkAction === 'hide') {
            $pdo->prepare("UPDATE books SET status='hidden' WHERE id IN ($placeholders)")->execute($ids);
            setFlash('success', count($ids) . ' books hidden.');
        }
    }
    redirect(BASE_URL . '/admin/books.php');
}

// --- Fetch books with filters ---
$filterCat  = (int)($_GET['category'] ?? 0);
$filterStat = $_GET['status'] ?? 'active';
$filterExt  = $_GET['ext']    ?? '';
$search     = trim($_GET['search'] ?? '');

$where  = [];
$params = [];

if (!empty($filterStat) && $filterStat !== 'all') {
    $where[]  = 'b.status = ?';
    $params[] = $filterStat;
} else {
    $where[] = "b.status != 'deleted'";
}
if ($filterCat > 0)   { $where[] = 'b.category_id = ?'; $params[] = $filterCat; }
if (!empty($filterExt)) { $where[] = 'b.file_extension = ?'; $params[] = $filterExt; }
if (!empty($search)) {
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $where[]  = '(b.title_kh LIKE ? OR b.title_en LIKE ? OR b.author LIKE ? OR b.tags LIKE ?)';
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    } else {
        $where[]  = 'MATCH(b.title_kh, b.title_en, b.author, b.tags) AGAINST(? IN BOOLEAN MODE)';
        $params[] = $search . '*';
    }
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare(
    "SELECT b.*, c.name_kh AS cat_name FROM books b LEFT JOIN categories c ON b.category_id=c.id
     $whereSQL ORDER BY b.created_at DESC LIMIT 500"
);
$stmt->execute($params);
$books = $stmt->fetchAll();

$categories = getCategories();

$pageTitle = 'គ្រប់គ្រងសៀវភៅ · Manage Books';
$activeNav = 'books';
include '../includes/admin_header.php';
?>

<!-- Toolbar -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div class="admin-search-bar" style="flex:1;">
        <div class="position-relative">
            <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:.85rem;"></i>
            <input type="text" id="booksSearch" class="admin-search-input" placeholder="Search by title, author..." style="padding-left:32px;"
                   value="<?= e($search) ?>" onkeyup="filterBooksTable()">
        </div>
        <select class="admin-filter-select" onchange="window.location.href='books.php?status='+this.value+'&category=<?= $filterCat ?>&ext=<?= e($filterExt) ?>'">
            <option value="active"  <?= $filterStat==='active'  ?'selected':''?>>Active</option>
            <option value="hidden"  <?= $filterStat==='hidden'  ?'selected':''?>>Hidden</option>
            <option value="all"     <?= $filterStat==='all'     ?'selected':''?>>All</option>
        </select>
        <select class="admin-filter-select" onchange="window.location.href='books.php?category='+this.value+'&status=<?= e($filterStat) ?>'">
            <option value="0">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $filterCat==$cat['id']?'selected':''?>><?= e($cat['name_kh']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="d-flex gap-2">
        <a href="upload.php" class="btn-admin-gold"><i class="bi bi-plus-lg"></i> Add New</a>
        <button class="btn-admin-secondary" onclick="exportTableToCSV('booksTable','kdlms-books.csv')">
            <i class="bi bi-download"></i> Export CSV
        </button>
    </div>
</div>

<!-- Bulk action bar -->
<div id="bulkActionBar" style="display:none;background:#FFF8F0;border:1px solid #FDE68A;border-radius:9px;padding:8px 14px;margin-bottom:12px;align-items:center;gap:12px;flex-wrap:wrap;">
    <span style="font-size:.82rem;font-weight:700;color:#92400E;"><i class="bi bi-check2-square me-1"></i><span id="bulkSelectedCount">0</span> selected</span>
    <form method="POST" action="books.php" id="bulkForm" style="display:flex;gap:8px;align-items:center;">
        <?= csrfField() ?>
        <select name="bulk_action" class="admin-filter-select" style="padding:5px 10px;">
            <option value="">-- Action --</option>
            <option value="hide">Hide</option>
            <option value="delete">Delete (Soft)</option>
        </select>
        <button type="submit" class="btn-admin-primary" style="padding:6px 14px;font-size:.78rem;"
                onclick="document.querySelectorAll('.row-check:checked').forEach(cb=>{const inp=document.createElement('input');inp.type='hidden';inp.name='book_ids[]';inp.value=cb.value;document.getElementById('bulkForm').appendChild(inp);});">
            Apply
        </button>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title"><i class="bi bi-collection"></i> Books / Documents (<?= count($books) ?>)</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="kdlms-table" id="booksTable">
            <thead>
                <tr>
                    <th style="width:36px;"><input type="checkbox" id="selectAllBooks" onchange="document.querySelectorAll('.row-check').forEach(c=>c.checked=this.checked);updateBulkActionBar();"></th>
                    <th data-sort="0">ID</th>
                    <th>Cover</th>
                    <th data-sort="3">ចំណងជើង / Title</th>
                    <th data-sort="4">ប្រភេទ</th>
                    <th data-sort="5">Type</th>
                    <th data-sort="6">Size</th>
                    <th data-sort="7">Downloads</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book):
                    $extBadge = getFileExtBadgeClass($book['file_extension']);
                ?>
                <tr>
                    <td><input type="checkbox" class="row-check" value="<?= $book['id'] ?>" onchange="updateBulkActionBar()"></td>
                    <td style="color:#94A3B8;font-size:.75rem;font-family:'Inter',sans-serif;">#<?= $book['id'] ?></td>
                    <td>
                        <div class="cover-thumb">
                            <?php if ($book['cover_image']): ?>
                            <img src="<?= BASE_URL ?>/assets/img/covers/<?= e($book['cover_image']) ?>" alt="">
                            <?php else: ?>
                            <i class="bi bi-file-earmark-text"></i>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="max-width:220px;">
                        <div style="font-family:'Kantumruy Pro',sans-serif;font-weight:700;font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= e($book['title_kh']) ?>"><?= e($book['title_kh']) ?></div>
                        <?php if ($book['author']): ?>
                        <div style="font-size:.7rem;color:#94A3B8;"><?= e($book['author']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-family:'Kantumruy Pro',sans-serif;font-size:.78rem;"><?= e($book['cat_name']) ?></td>
                    <td><span class="badge bg-<?= $extBadge ?>"><?= strtoupper(e($book['file_extension'])) ?></span></td>
                    <td style="font-family:'Inter',sans-serif;font-size:.75rem;white-space:nowrap;"><?= formatFileSize($book['file_size']) ?></td>
                    <td style="font-family:'Inter',sans-serif;font-weight:700;color:#1A3A5C;"><?= number_format($book['download_count']) ?></td>
                    <td>
                        <span class="status-badge status-<?= $book['status'] ?>">
                            <i class="bi <?= $book['status']==='active'?'bi-check-circle':($book['status']==='hidden'?'bi-eye-slash':'bi-trash') ?>"></i>
                            <?= ucfirst($book['status']) ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="upload.php?edit=<?= $book['id'] ?>" class="action-btn edit" title="Edit"><i class="bi bi-pencil"></i></a>
                            <?php if ($book['status'] === 'active'): ?>
                            <a href="books.php?action=hide&id=<?= $book['id'] ?>" class="action-btn hide" title="Hide"><i class="bi bi-eye-slash"></i></a>
                            <?php else: ?>
                            <a href="books.php?action=show&id=<?= $book['id'] ?>" class="action-btn view" title="Show"><i class="bi bi-eye"></i></a>
                            <?php endif; ?>
                            <button onclick="confirmDelete('books.php', <?= $book['id'] ?>, '<?= addslashes(e($book['title_kh'])) ?>')" class="action-btn delete" title="Delete"><i class="bi bi-trash"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($books)): ?>
                <tr><td colspan="10" style="text-align:center;padding:3rem;color:#94A3B8;">No books found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => initBulkActions('selectAllBooks', 'row-check'));
function filterBooksTable() {
    const q    = document.getElementById('booksSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#booksTable tbody tr');
    rows.forEach(r => r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none');
}
</script>
</body>
</html>
