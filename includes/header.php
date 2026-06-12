<?php
// ================================================================
// includes/header.php — Public Page Header
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================
// Usage: include at the top of every public page.
// Expects: $pageTitle (string), $bodyClass (optional)
// ================================================================

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$siteName = getSetting('site_name_kh', 'បណ្ណាល័យឌីជីថលខ្មែរ');
$siteNameEn = getSetting('site_name_en', 'Khmer Digital Library');
$pageTitle  = $pageTitle ?? $siteNameEn;
$bodyClass  = $bodyClass ?? '';

// ─── Fetch display name fresh from DB for correct Khmer UTF-8 ───
// $_SESSION['user_name'] may be corrupted on Windows XAMPP.
// We always pull the name direct from the DB via PDO (utf8mb4).
$navUserName = '';
if (isLoggedIn() && !empty($_SESSION['user_id'])) {
    $pdo = getPDO();
    $navUserStmt = $pdo->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
    $navUserStmt->execute([$_SESSION['user_id']]);
    $navUserRow  = $navUserStmt->fetch();
    $navUserName = $navUserRow['name'] ?? '';
}
// ──────────────────────────────────────────────────────

// Maintenance mode check
if (getSetting('maintenance_mode', '0') === '1' && !isLoggedIn()) {
    http_response_code(503);
    die('<div style="font-family:Hanuman,serif;text-align:center;padding:80px;background:#FDF8F0;min-height:100vh;color:#4A4A4A;">
        <h1 style="font-family:Moul,serif;color:#8B0000;font-size:2rem;">🛠️ ប្រព័ន្ធកំពុងថែទាំ</h1>
        <p style="margin-top:16px;">System is under maintenance. Please try again later.</p>
    </div>');
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="km" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($siteNameEn) ?> — ចំណេះដឹងគ្មានព្រំដែន | Knowledge Without Boundaries">
    <meta name="keywords" content="khmer library, digital library, cambodia books, PDF download, KDLMS">
    <meta name="author" content="អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)">
    <meta name="theme-color" content="#8B0000">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="KDLMS">
    <meta property="og:title" content="<?= e($pageTitle) ?> | KDLMS">
    <meta property="og:type" content="website">
    <meta property="og:description" content="Khmer Digital Library — Knowledge Without Boundaries">
    <meta property="og:image" content="<?= BASE_URL ?>/assets/img/icons/icon-512.png">
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
    <!-- Apple touch icons -->
    <link rel="apple-touch-icon" sizes="192x192" href="<?= BASE_URL ?>/assets/img/icons/icon-192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="<?= BASE_URL ?>/assets/img/icons/icon-512.png">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= BASE_URL ?>/assets/img/icons/icon-192.png">
    <title><?= e($pageTitle) ?> | KDLMS</title>

    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Google Fonts: Khmer + English -->
    <link href="https://fonts.googleapis.com/css2?family=Moul&family=Hanuman:wght@300;400;700&family=Noto+Serif+Khmer:wght@300;400;700&family=Kantumruy+Pro:wght@300;400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- KDLMS Custom Styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="angkor-bg <?= e($bodyClass) ?>">

<!-- PWA Service Worker Registration -->
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .then(reg => console.log('[KDLMS PWA] SW registered:', reg.scope))
            .catch(err => console.warn('[KDLMS PWA] SW failed:', err));
    });
}
</script>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<?php if ($flash): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showToast('<?= e($flash['type']) ?>', '<?= e($flash['message']) ?>');
});
</script>
<?php endif; ?>

<!-- ================================================================
     NAVBAR — Fixed top navigation
     ================================================================ -->
<nav class="kdlms-navbar transparent" id="kdlmsNavbar">
    <div class="container-fluid d-flex align-items-center justify-content-between">

        <!-- Brand / Logo -->
        <a href="<?= BASE_URL ?>/" class="d-flex align-items-center gap-2 text-decoration-none">
            <div class="navbar-logo-icon">
                <i class="bi bi-building"></i>
            </div>
            <div>
                <div class="navbar-brand-text"><?= e($siteName) ?></div>
                <div class="navbar-brand-sub">KDLMS v<?= APP_VERSION ?></div>
            </div>
        </a>

        <!-- Center Nav Links -->
        <div class="d-none d-lg-flex align-items-center gap-1">
            <a href="<?= BASE_URL ?>/" class="nav-link-custom <?= (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : '' ?>">
                <i class="bi bi-house me-1"></i>ទំព័រដើម
            </a>
            <a href="<?= BASE_URL ?>/browse.php" class="nav-link-custom <?= (basename($_SERVER['PHP_SELF']) === 'browse.php') ? 'active' : '' ?>">
                <i class="bi bi-grid me-1"></i>ស្វែងរកសៀវភៅ
            </a>
            <?php if (isLoggedIn() && in_array($_SESSION['user_role'] ?? '', ['admin','superadmin'])): ?>
            <a href="<?= BASE_URL ?>/admin/index.php" class="nav-link-custom">
                <i class="bi bi-speedometer2 me-1"></i>Admin Panel
            </a>
            <?php endif; ?>
        </div>

        <!-- Right: Auth buttons -->
        <div class="d-flex align-items-center gap-2">
            <?php if (isLoggedIn()): ?>
                <div class="d-flex align-items-center gap-2">
                    <span class="d-none d-md-block" style="font-family:'Kantumruy Pro','Hanuman',sans-serif;font-size:.8rem;color:rgba(255,255,255,.8);">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($navUserName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                    </span>
                    <a href="<?= BASE_URL ?>/logout.php" class="btn-secondary-kdlms py-2 px-3" style="font-size:.8rem;">
                        <i class="bi bi-power"></i>
                        <span class="d-none d-md-inline">ចាកចេញ</span>
                    </a>
                </div>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login.php" class="btn-secondary-kdlms py-2 px-3" style="font-size:.8rem;">
                    <i class="bi bi-box-arrow-in-right"></i> ចូលប្រើ
                </a>
                <?php if (getSetting('allow_registration', '1') === '1'): ?>
                <a href="<?= BASE_URL ?>/register.php" class="btn-primary-kdlms py-2 px-3" style="font-size:.8rem;">
                    <i class="bi bi-person-plus"></i> ចុះឈ្មោះ
                </a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Mobile hamburger -->
            <button class="d-lg-none btn btn-sm" style="color:white;" onclick="toggleMobileNav()" id="navToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
        </div>
    </div>
</nav>

<!-- Mobile Nav Menu -->
<div id="mobileNav" style="display:none;position:fixed;top:70px;left:0;right:0;z-index:999;background:var(--primary);padding:1rem;border-bottom:2px solid var(--secondary);">
    <a href="<?= BASE_URL ?>/" class="nav-link-custom d-block mb-1"><i class="bi bi-house me-1"></i>ទំព័រដើម</a>
    <a href="<?= BASE_URL ?>/browse.php" class="nav-link-custom d-block mb-1"><i class="bi bi-grid me-1"></i>ស្វែងរកសៀវភៅ</a>
    <?php if (!isLoggedIn()): ?>
    <a href="<?= BASE_URL ?>/login.php" class="nav-link-custom d-block mb-1"><i class="bi bi-box-arrow-in-right me-1"></i>ចូលប្រើ</a>
    <a href="<?= BASE_URL ?>/register.php" class="nav-link-custom d-block"><i class="bi bi-person-plus me-1"></i>ចុះឈ្មោះ</a>
    <?php else: ?>
    <a href="<?= BASE_URL ?>/logout.php" class="nav-link-custom d-block"><i class="bi bi-power me-1"></i>ចាកចេញ</a>
    <?php endif; ?>
</div>
