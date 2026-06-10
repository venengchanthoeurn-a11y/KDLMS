<?php
// ================================================================
// browse.php — Book Browsing Page with AJAX Search
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Handle AJAX requests — return JSON
$isAjax = isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

$opts = [
    'page'        => max(1, (int)($_GET['page'] ?? 1)),
    'category_id' => (int)($_GET['category'] ?? 0),
    'language'    => in_array($_GET['language'] ?? '', ['kh','en','both','other']) ? $_GET['language'] : '',
    'extension'   => trim($_GET['ext'] ?? ''),
    'sort'        => in_array($_GET['sort'] ?? '', ['newest','popular','alpha_asc','alpha_desc','relevance']) ? $_GET['sort'] : 'newest',
    'search'      => trim($_GET['search'] ?? ''),
];

$result     = getBooks($opts);
$categories = getCategories(withCount: true);

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// Selected category info
$selectedCat = null;
if ($opts['category_id'] > 0) {
    $pdo  = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$opts['category_id']]);
    $selectedCat = $stmt->fetch();
}

$pageTitle = 'ស្វែងរកសៀវភៅ · Browse Library';
$bodyClass = '';
include 'includes/header.php';
?>

<div class="browse-layout">

    <!-- ============================================================
         LEFT SIDEBAR — Categories
         ============================================================ -->
    <aside class="browse-sidebar">
        <div style="font-family:var(--font-kantumruy);font-size:.7rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-funnel" style="color:var(--primary);"></i> ប្រភេទឯកសារ / Categories
        </div>

        <!-- All categories button -->
        <a href="<?= BASE_URL ?>/browse.php"
           class="sidebar-cat-item <?= $opts['category_id'] === 0 ? 'active' : '' ?>"
           style="margin-bottom:6px;">
            <span><i class="bi bi-grid me-2"></i>ប្រភេទទាំងអស់ (All)</span>
            <span class="sidebar-cat-count"><?= number_format(array_sum(array_column($categories, 'book_count'))) ?></span>
        </a>

        <div style="height:1px;background:var(--border);margin:8px 0;"></div>

        <!-- Category list -->
        <?php foreach ($categories as $cat): ?>
        <a href="<?= BASE_URL ?>/browse.php?category=<?= $cat['id'] ?>"
           class="sidebar-cat-item <?= $opts['category_id'] == $cat['id'] ? 'active' : '' ?>">
            <span>
                <i class="bi <?= e($cat['icon']) ?> me-2" style="color:<?= e($cat['color']) ?>;"></i>
                <?= e($cat['name_kh']) ?>
            </span>
            <span class="sidebar-cat-count"><?= number_format($cat['book_count'] ?? 0) ?></span>
        </a>
        <?php endforeach; ?>

        <!-- Language filter -->
        <div style="margin-top:1.5rem;padding:12px;background:var(--bg);border-radius:var(--radius-sm);border:1px solid var(--border);">
            <div style="font-family:var(--font-kantumruy);font-size:.72rem;font-weight:700;color:var(--primary);margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">
                <i class="bi bi-translate me-1"></i>ភាសា / Language
            </div>
            <?php foreach ([''=>'ទាំងអស់ (All)', 'kh'=>'🇰🇭 ភាសាខ្មែរ', 'en'=>'🇺🇸 English', 'both'=>'KH + EN'] as $val => $label): ?>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-family:var(--font-kantumruy);font-size:.8rem;padding:3px 0;color:var(--text-body);">
                <input type="radio" name="lang_sidebar" value="<?= $val ?>" <?= $opts['language'] === $val ? 'checked' : '' ?>
                       onchange="applyFilter('language', '<?= $val ?>')">
                <?= $label ?>
            </label>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- ============================================================
         MAIN CONTENT — Search + Book Grid
         ============================================================ -->
    <div class="browse-main">

        <!-- Search & Filter Bar -->
        <div class="filter-bar">
            <!-- Search input -->
            <div class="kdlms-search-wrap" style="flex:1;min-width:200px;">
                <span class="kdlms-search-icon"><i class="bi bi-search"></i></span>
                <input type="text"
                       id="searchInput"
                       class="kdlms-search-input"
                       placeholder="ស្វែងរក... (title, author, tags)"
                       value="<?= e($opts['search']) ?>"
                       oninput="triggerBrowseFilter()">
            </div>

            <!-- File type filter -->
            <select id="extFilter" onchange="executeBrowseFilter(1)">
                <option value="">ទម្រង់ (Format)</option>
                <option value="pdf"  <?= $opts['extension']==='pdf'  ?'selected':''?>>PDF</option>
                <option value="docx" <?= $opts['extension']==='docx' ?'selected':''?>>DOCX</option>
                <option value="epub" <?= $opts['extension']==='epub' ?'selected':''?>>EPUB</option>
                <option value="zip"  <?= $opts['extension']==='zip'  ?'selected':''?>>ZIP</option>
                <option value="mp4"  <?= $opts['extension']==='mp4'  ?'selected':''?>>MP4</option>
            </select>

            <!-- Sort -->
            <select id="sortFilter" onchange="executeBrowseFilter(1)">
                <option value="newest"    <?= $opts['sort']==='newest'    ?'selected':''?>>ថ្មីបំផុត (Newest)</option>
                <option value="popular"   <?= $opts['sort']==='popular'   ?'selected':''?>>ទាញយកច្រើន (Popular)</option>
                <option value="alpha_asc" <?= $opts['sort']==='alpha_asc' ?'selected':''?>>ក- អ (A-Z)</option>
                <option value="alpha_desc"<?= $opts['sort']==='alpha_desc'?'selected':''?>>អ-ក (Z-A)</option>
            </select>

            <!-- Hidden category filter for JS -->
            <input type="hidden" id="categoryFilter" value="<?= $opts['category_id'] ?>">
            <input type="hidden" id="langFilter"     value="<?= e($opts['language']) ?>">

            <!-- Result count -->
            <div style="font-family:var(--font-kantumruy);font-size:.78rem;color:var(--text-light);white-space:nowrap;">
                <i class="bi bi-collection me-1"></i>
                <span id="resultsCount"><?= number_format($result['total']) ?> ឯកសារ</span>
            </div>
        </div>

        <!-- Page heading -->
        <?php if ($selectedCat): ?>
        <div style="margin-bottom:1rem;padding:10px 16px;background:white;border-radius:var(--radius-sm);border-left:4px solid <?= e($selectedCat['color']) ?>;display:flex;align-items:center;gap:10px;">
            <i class="bi <?= e($selectedCat['icon']) ?>" style="color:<?= e($selectedCat['color']) ?>;font-size:1.2rem;"></i>
            <div>
                <div style="font-family:var(--font-moul);font-size:.9rem;color:var(--accent);"><?= e($selectedCat['name_kh']) ?></div>
                <div style="font-family:var(--font-en);font-size:.72rem;color:var(--text-muted);"><?= e($selectedCat['name_en']) ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Books Grid -->
        <div class="books-grid" id="booksGrid">
            <?php if (empty($result['books'])): ?>
            <div class="empty-state" style="grid-column:1/-1;">
                <div class="empty-state-icon"><i class="bi bi-search"></i></div>
                <div class="empty-state-title">រកមិនឃើញឯកសារ</div>
                <p style="font-size:.82rem;color:var(--text-muted);font-family:var(--font-kantumruy);">No documents found. Try different search terms or filters.</p>
            </div>
            <?php else: ?>
                <?php foreach ($result['books'] as $book):
                    $extBadge = getFileExtBadgeClass($book['file_extension']);
                ?>
                <div class="book-card">
                    <div class="book-cover-wrap">
                        <?php if ($book['cover_image']): ?>
                        <img data-src="<?= BASE_URL ?>/assets/img/covers/<?= e($book['cover_image']) ?>"
                             src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAALAAAAALAAAAABAAEAAAI="
                             alt="<?= e($book['title_kh']) ?>">
                        <?php else: ?>
                        <div class="book-cover-placeholder" style="background:linear-gradient(135deg,<?= e($book['cat_color'] ?? '#8B0000') ?>12,<?= e($book['cat_color'] ?? '#8B0000') ?>25);">
                            <i class="bi bi-file-earmark-text" style="color:<?= e($book['cat_color'] ?? '#8B0000') ?>;opacity:.35;"></i>
                            <span class="placeholder-title"><?= e(mb_substr($book['title_kh'], 0, 30)) ?></span>
                        </div>
                        <?php endif; ?>
                        <span class="badge bg-<?= $extBadge ?> book-ext-badge"><?= strtoupper(e($book['file_extension'])) ?></span>
                        <?php if ($book['is_featured']): ?>
                        <span class="book-featured-badge"><i class="bi bi-star-fill me-1"></i>Featured</span>
                        <?php endif; ?>
                    </div>
                    <div class="book-body">
                        <div class="book-category-tag"><?= e($book['cat_name_kh'] ?? '') ?></div>
                        <div class="book-title-kh" title="<?= e($book['title_kh']) ?>"><?= e($book['title_kh']) ?></div>
                        <?php if ($book['author']): ?>
                        <div class="book-author"><i class="bi bi-person me-1"></i><?= e($book['author']) ?></div>
                        <?php endif; ?>
                        <div class="book-meta">
                            <span class="book-size"><?= formatFileSize($book['file_size']) ?></span>
                            <span class="book-downloads"><i class="bi bi-download me-1"></i><?= number_format($book['download_count']) ?></span>
                        </div>
                        <?php if (isLoggedIn()): ?>
                        <a href="<?= BASE_URL ?>/download.php?id=<?= $book['id'] ?>" class="btn-download mt-2">
                            <i class="bi bi-download"></i> ⬇ ទាញយក
                        </a>
                        <?php else: ?>
                        <a href="<?= BASE_URL ?>/login.php?msg=login_required" class="btn-download mt-2"
                           style="background:linear-gradient(135deg,var(--accent),var(--accent-light));">
                            <i class="bi bi-lock me-1"></i>Login to Download
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <div id="paginationWrap">
            <?php if ($result['total_pages'] > 1): ?>
            <div class="kdlms-pagination">
                <?php if ($result['page'] > 1): ?>
                <button class="page-btn" onclick="executeBrowseFilter(<?= $result['page'] - 1 ?>)"><i class="bi bi-chevron-left"></i></button>
                <?php endif; ?>

                <?php
                $start = max(1, $result['page'] - 3);
                $end   = min($result['total_pages'], $result['page'] + 3);
                for ($p = $start; $p <= $end; $p++):
                ?>
                <button class="page-btn <?= $p === $result['page'] ? 'active' : '' ?>"
                        onclick="executeBrowseFilter(<?= $p ?>)"><?= $p ?></button>
                <?php endfor; ?>

                <?php if ($result['page'] < $result['total_pages']): ?>
                <button class="page-btn" onclick="executeBrowseFilter(<?= $result['page'] + 1 ?>)"><i class="bi bi-chevron-right"></i></button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div><!-- /browse-main -->
</div><!-- /browse-layout -->

<script>
// Apply sidebar filter and trigger AJAX
function applyFilter(key, value) {
    const el = document.getElementById(key + 'Filter');
    if (el) el.value = value;
    executeBrowseFilter(1);
}
</script>

<?php include 'includes/footer.php'; ?>
