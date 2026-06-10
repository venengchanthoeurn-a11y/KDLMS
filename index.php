<?php
// ================================================================
// index.php — Homepage / Landing Page
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$pageTitle = getSetting('site_name_en', 'Khmer Digital Library Management System');
$bodyClass = ''; // No special class for homepage

include 'includes/header.php';

// Fetch data for homepage
$stats         = getStats();
$featuredBooks = getFeaturedBooks(8);
$categories    = getCategories(withCount: true);
?>

<!-- ================================================================
     HERO SECTION
     ================================================================ -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <!-- Badge -->
            <div class="hero-badge">
                <i class="bi bi-book-half"></i>
                ការអប់រំ · Education · ចំណេះដឹង
            </div>

            <!-- Main title -->
            <h1 class="hero-title">
                សូមស្វាគមន៍ការចូលមកកាន់<br>បណ្ណាល័យឌីជីថល វត្តឥន្ទខីលារាម-ថ្មគោល
            </h1>
            
            <p class="hero-subtitle">
                ផ្ទុក ស្វែងរក និងទាញយកសៀវភៅ ឯកសារសិក្សា និងគម្រោងស្រាវជ្រាវជាច្រើនដោយ
                ឥតគិតថ្លៃ — Free access to thousands of Khmer and international documents.
            </p>

            <!-- Action buttons -->
            <div class="d-flex flex-wrap gap-3 justify-content-center">
                <a href="<?= BASE_URL ?>/browse.php" class="btn-primary-kdlms">
                    <i class="bi bi-search"></i>
                    ស្វែងរកសៀវភៅឥឡូវនេះ
                </a>
                <?php if (!isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/register.php" class="btn-secondary-kdlms">
                    <i class="bi bi-person-plus"></i>
                    ចុះឈ្មោះ · Register Free
                </a>
                <?php endif; ?>
            </div>

            <!-- Trust badges -->
            <div class="d-flex flex-wrap gap-4 justify-content-center mt-4" style="font-size:.78rem;color:rgba(255,255,255,.55);font-family:var(--font-kantumruy);">
                <span><i class="bi bi-shield-check me-1" style="color:var(--secondary);"></i>Secure & Free</span>
                <span><i class="bi bi-globe me-1" style="color:var(--secondary);"></i>Bilingual KH/EN</span>
                <span><i class="bi bi-speedometer2 me-1" style="color:var(--secondary);"></i>FULLTEXT Search</span>
                <span><i class="bi bi-server me-1" style="color:var(--secondary);"></i>100,000+ Scale</span>
            </div>
        </div>
    </div>
</section>

<!-- ================================================================
     STATS SECTION
     ================================================================ -->
<section class="stats-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(139,0,0,.08);color:var(--primary);">
                        <i class="bi bi-book-fill"></i>
                    </div>
                    <div>
                        <div class="stat-label">សៀវភៅ / Books</div>
                        <div class="stat-number" data-counter="<?= $stats['books'] ?>"><?= number_format($stats['books']) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(200,150,12,.08);color:var(--secondary);">
                        <i class="bi bi-tags-fill"></i>
                    </div>
                    <div>
                        <div class="stat-label">ប្រភេទ / Categories</div>
                        <div class="stat-number" data-counter="<?= $stats['categories'] ?>"><?= $stats['categories'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(26,58,92,.08);color:var(--accent);">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-label">សមាជិក / Users</div>
                        <div class="stat-number" data-counter="<?= $stats['users'] ?>"><?= number_format($stats['users']) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(27,67,50,.08);color:var(--success);">
                        <i class="bi bi-download"></i>
                    </div>
                    <div>
                        <div class="stat-label">ទាញយក / Downloads</div>
                        <div class="stat-number" data-counter="<?= $stats['downloads'] ?>"><?= number_format($stats['downloads']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Lotus divider -->
<div class="container">
    <div class="lotus-divider">
        <span class="lotus-divider-inner"><i class="bi bi-flower1"></i></span>
    </div>
</div>

<!-- ================================================================
     CATEGORIES SECTION
     ================================================================ -->
<section style="padding:2rem 0 3rem;">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between mb-1">
            <div>
                <h2 class="section-title">ស្វែងរកតាមប្រភេទ</h2>
                <p style="font-family:var(--font-kantumruy);font-size:.82rem;color:var(--text-light);">Browse by Category — Choose your field of study</p>
            </div>
            <a href="<?= BASE_URL ?>/browse.php" style="font-family:var(--font-kantumruy);font-size:.8rem;color:var(--accent);text-decoration:none;font-weight:700;display:flex;align-items:center;gap:4px;">
                មើលទាំងអស់ <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="section-divider"></div>

        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <a href="<?= BASE_URL ?>/browse.php?category=<?= $cat['id'] ?>"
                   class="category-card"
                   style="--cat-color: <?= e($cat['color']) ?>;">
                    <span class="category-icon" style="color:<?= e($cat['color']) ?>;">
                        <i class="bi <?= e($cat['icon']) ?>"></i>
                    </span>
                    <span class="category-name-kh"><?= e($cat['name_kh']) ?></span>
                    <span class="category-name-en"><?= e($cat['name_en']) ?></span>
                    <span class="category-count">
                        <?= number_format($cat['book_count'] ?? 0) ?> ឯកសារ
                    </span>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================================================================
     FEATURED BOOKS SECTION
     ================================================================ -->
<?php if (!empty($featuredBooks)): ?>
<section style="padding:2rem 0 4rem;background:white;">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between mb-1">
            <div>
                <h2 class="section-title">សៀវភៅសិក្សាលេចធ្លោ</h2>
                <p style="font-family:var(--font-kantumruy);font-size:.82rem;color:var(--text-light);">Featured Publications — Most popular documents</p>
            </div>
            <a href="<?= BASE_URL ?>/browse.php?sort=popular" style="font-family:var(--font-kantumruy);font-size:.8rem;color:var(--accent);text-decoration:none;font-weight:700;display:flex;align-items:center;gap:4px;">
                មើលទាំងអស់ <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="section-divider"></div>

        <div class="books-grid">
            <?php foreach ($featuredBooks as $book):
                $extBadge = getFileExtBadgeClass($book['file_extension']);
            ?>
            <div class="book-card">
                <div class="book-cover-wrap">
                    <?php if ($book['cover_image']): ?>
                    <img data-src="<?= BASE_URL ?>/assets/img/covers/<?= e($book['cover_image']) ?>"
                         src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAALAAAAALAAAAABAAEAAAI="
                         alt="<?= e($book['title_kh']) ?>" class="img-fluid">
                    <?php else: ?>
                    <div class="book-cover-placeholder" style="background:linear-gradient(135deg,<?= e($book['cat_color'] ?? '#8B0000') ?>15,<?= e($book['cat_color'] ?? '#8B0000') ?>30);">
                        <i class="bi bi-file-earmark-text" style="color:<?= e($book['cat_color'] ?? '#8B0000') ?>;opacity:.4;"></i>
                        <span class="placeholder-title"><?= e(mb_substr($book['title_kh'], 0, 35)) ?></span>
                    </div>
                    <?php endif; ?>
                    <span class="badge bg-<?= $extBadge ?> book-ext-badge"><?= strtoupper(e($book['file_extension'])) ?></span>
                    <span class="book-featured-badge"><i class="bi bi-star-fill me-1"></i>Featured</span>
                </div>
                <div class="book-body">
                    <div class="book-category-tag"><?= e($book['cat_name_kh'] ?? '') ?></div>
                    <div class="book-title-kh" title="<?= e($book['title_kh']) ?>"><?= e($book['title_kh']) ?></div>
                    <?php if ($book['author']): ?>
                    <div class="book-author"><i class="bi bi-person me-1"></i><?= e($book['author']) ?></div>
                    <?php endif; ?>
                    <div class="book-meta">
                        <span class="book-size"><?= formatFileSize($book['file_size']) ?></span>
                        <span class="book-downloads"><i class="bi bi-download"></i><?= number_format($book['download_count']) ?></span>
                    </div>
                    <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/download.php?id=<?= $book['id'] ?>" class="btn-download mt-2">
                        <i class="bi bi-download"></i> ⬇ ទាញយក
                    </a>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php?msg=login_required" class="btn-download mt-2" style="background:linear-gradient(135deg,var(--accent),var(--accent-light));">
                        <i class="bi bi-lock me-1"></i> Login to Download
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- CTA to browse all -->
        <div class="text-center mt-5">
            <a href="<?= BASE_URL ?>/browse.php" class="btn-primary-kdlms">
                <i class="bi bi-grid"></i>
                ចូលមើលបណ្ណសារទាំងអស់ — Browse All Documents
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ================================================================
     HOW IT WORKS SECTION
     ================================================================ -->
<section style="padding:4rem 0;">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title" style="display:inline-block;">របៀបប្រើប្រាស់ · How It Works</h2>
            <div class="section-divider mx-auto"></div>
        </div>
        <div class="row g-4 text-center">
            <div class="col-md-3">
                <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--primary),#A52020);border-radius:50%;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:white;box-shadow:0 4px 16px rgba(139,0,0,.25);">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h5 style="font-family:var(--font-moul);font-size:.9rem;color:var(--primary);margin-bottom:.5rem;">១. ចុះឈ្មោះ</h5>
                <p style="font-family:var(--font-kantumruy);font-size:.8rem;color:var(--text-light);">បង្កើតគណនីដោយឥតគិតថ្លៃ ដោយប្រើ Email របស់អ្នក</p>
            </div>
            <div class="col-md-3">
                <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--secondary),#D97706);border-radius:50%;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:white;box-shadow:0 4px 16px rgba(200,150,12,.25);">
                    <i class="bi bi-search"></i>
                </div>
                <h5 style="font-family:var(--font-moul);font-size:.9rem;color:var(--primary);margin-bottom:.5rem;">២. ស្វែងរក</h5>
                <p style="font-family:var(--font-kantumruy);font-size:.8rem;color:var(--text-light);">ស្វែងរកតាមប្រភេទ ភាសា ឬពាក្យគន្លឹះ</p>
            </div>
            <div class="col-md-3">
                <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--accent),#1E40AF);border-radius:50%;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:white;box-shadow:0 4px 16px rgba(26,58,92,.25);">
                    <i class="bi bi-eye"></i>
                </div>
                <h5 style="font-family:var(--font-moul);font-size:.9rem;color:var(--primary);margin-bottom:.5rem;">៣. ជ្រើសរើស</h5>
                <p style="font-family:var(--font-kantumruy);font-size:.8rem;color:var(--text-light);">ពិនិត្យព័ត៌មានផ្សេងៗ ។ ជ្រើសរើសសៀវភៅដែលចង់បាន</p>
            </div>
            <div class="col-md-3">
                <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--success),#15803D);border-radius:50%;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:white;box-shadow:0 4px 16px rgba(27,67,50,.25);">
                    <i class="bi bi-download"></i>
                </div>
                <h5 style="font-family:var(--font-moul);font-size:.9rem;color:var(--primary);margin-bottom:.5rem;">៤. ទាញយក</h5>
                <p style="font-family:var(--font-kantumruy);font-size:.8rem;color:var(--text-light);">ចុចប៊ូតុង "ទាញយក" ហើយរក្សាទុកលើឧបករណ៍របស់អ្នក</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
