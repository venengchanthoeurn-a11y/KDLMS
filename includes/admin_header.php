<?php
// ================================================================
// includes/admin_header.php — Admin Panel Layout Header
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================
// Call requireAdmin() BEFORE including this file.
// Expects: $pageTitle (string), $activeNav (string)
// ================================================================

$siteName  = getSetting('site_name_kh', 'បណ្ណាល័យឌីជីថលខ្មែរ');
$pageTitle = $pageTitle ?? 'Admin Panel';
$activeNav = $activeNav ?? '';
$user      = currentUser();

// ─── Re-fetch admin name fresh from DB to guarantee correct Khmer UTF-8 ───
// Sessions on Windows XAMPP can corrupt multibyte names; fetching from PDO
// (which is set to utf8mb4) is always reliable.
if (!empty($user['id'])) {
    $pdo       = getPDO();
    $freshUser = $pdo->prepare('SELECT name, role FROM users WHERE id = ? LIMIT 1');
    $freshUser->execute([$user['id']]);
    $freshRow  = $freshUser->fetch();
    if ($freshRow) {
        $user['name'] = $freshRow['name'];
        $user['role'] = $freshRow['role'];
    }
}
// ───────────────────────────────────────────────────────────────────────────

$navItems = [
    'dashboard'  => ['icon'=>'bi-speedometer2', 'label'=>'Dashboard',     'url'=>BASE_URL.'/admin/index.php',      'roles'=>['admin','superadmin']],
    'books'      => ['icon'=>'bi-book',          'label'=>'គ្រប់គ្រងសៀវភៅ', 'url'=>BASE_URL.'/admin/books.php',      'roles'=>['admin','superadmin']],
    'upload'     => ['icon'=>'bi-cloud-upload',  'label'=>'Upload Files',   'url'=>BASE_URL.'/admin/upload.php',     'roles'=>['admin','superadmin']],
    'categories' => ['icon'=>'bi-tags',          'label'=>'ប្រភេទ',          'url'=>BASE_URL.'/admin/categories.php','roles'=>['admin','superadmin']],
    'users'      => ['icon'=>'bi-people',        'label'=>'គ្រប់គ្រងអ្នកប្រើ','url'=>BASE_URL.'/admin/users.php',    'roles'=>['admin','superadmin']],
    'logs'       => ['icon'=>'bi-journal-text',  'label'=>'Download Logs',  'url'=>BASE_URL.'/admin/logs.php',      'roles'=>['admin','superadmin']],
    'settings'   => ['icon'=>'bi-gear',          'label'=>'Settings',       'url'=>BASE_URL.'/admin/settings.php',  'roles'=>['superadmin']],
];
?>
<!DOCTYPE html>
<html lang="km" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — KDLMS Admin</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moul&family=Hanuman:wght@300;400;700&family=Kantumruy+Pro:wght@300;400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <!-- KDLMS Admin CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">

<!-- ================================================================
     SIDEBAR
     ================================================================ -->
<aside class="admin-sidebar" id="adminSidebar">

    <!-- Sidebar header: logo + brand -->
    <div class="admin-sidebar-header">
        <div class="admin-sidebar-logo"><i class="bi bi-building"></i></div>
        <div class="admin-sidebar-brand">
            KDLMS Admin
            <small>Khmer Digital Library</small>
        </div>
    </div>

    <!-- Navigation -->
    <div style="flex:1;overflow-y:auto;padding-bottom:1rem;">

        <div class="sidebar-nav-section">
            <div class="sidebar-nav-section-label">ទូទៅ / General</div>

            <?php foreach ($navItems as $key => $nav):
                if (!in_array($user['role'] ?? '', $nav['roles'])) continue;
            ?>
            <a href="<?= $nav['url'] ?>"
               class="sidebar-nav-item <?= ($activeNav === $key) ? 'active' : '' ?>">
                <i class="bi <?= $nav['icon'] ?>"></i>
                <span><?= e($nav['label']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="sidebar-nav-section">
            <div class="sidebar-nav-section-label">ផ្សេងទៀត / Other</div>
            <a href="<?= BASE_URL ?>/" target="_blank" class="sidebar-nav-item">
                <i class="bi bi-globe"></i> <span>View Public Site</span>
            </a>
            <a href="<?= BASE_URL ?>/logout.php" class="sidebar-nav-item">
                <i class="bi bi-power"></i> <span>ចាកចេញ / Logout</span>
            </a>
        </div>

        <!-- System info card -->
        <div style="margin:1rem 0.75rem;padding:12px;background:rgba(200,150,12,.07);border:1px solid rgba(200,150,12,.2);border-radius:10px;font-size:.7rem;color:rgba(255,255,255,.5);font-family:'Inter',sans-serif;">
            <div style="color:#C8960C;font-weight:700;margin-bottom:4px;"><i class="bi bi-shield-check me-1"></i>System Info</div>
            <div>PHP: <?= PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION ?> · MySQL via PDO</div>
            <div>Role: <span style="color:#C8960C;font-weight:600;"><?= e(strtoupper($user['role'] ?? '')) ?></span></div>
            <div>v<?= APP_VERSION ?> · <?= date('d/m/Y') ?></div>
        </div>
    </div>

    <!-- Sidebar footer -->
    <div class="sidebar-footer">
        <div>KDLMS v<?= APP_VERSION ?></div>
        <div class="creator">អេង ចាន់ធឿន (Vthe)</div>
    </div>
</aside>

<!-- ================================================================
     MAIN CONTENT AREA
     ================================================================ -->
<div class="admin-main" id="adminMain">

    <!-- Admin top header bar -->
    <header class="admin-header">
        <div class="d-flex align-items-center gap-3">
            <!-- Mobile sidebar toggle -->
            <button class="btn btn-sm d-lg-none border-0 p-1" onclick="toggleAdminSidebar()" style="color:#374151;">
                <i class="bi bi-list fs-5"></i>
            </button>

            <div>
                <div class="admin-page-title">
                    <i class="bi <?= $navItems[$activeNav]['icon'] ?? 'bi-grid' ?>"></i>
                    <?= e($pageTitle) ?>
                </div>
                <div class="admin-breadcrumb">
                    <a href="<?= BASE_URL ?>/admin/index.php">KDLMS Admin</a>
                    <?php if ($activeNav !== 'dashboard'): ?>
                    <span class="mx-1">›</span> <?= e($pageTitle) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: user pill + actions -->
        <div class="d-flex align-items-center gap-2">
            <a href="<?= BASE_URL ?>/" target="_blank" class="btn-admin-secondary" style="padding:6px 14px;font-size:.75rem;">
                <i class="bi bi-globe"></i>
                <span class="d-none d-md-inline">Public Site</span>
            </a>

            <div class="admin-user-pill">
                <div class="admin-user-avatar" style="background:#fff;border:1px solid #C8960C;overflow:hidden;padding:1.5px;display:flex;align-items:center;justify-content:center;">
                    <img src="<?= BASE_URL ?>/assets/img/creator.jpg" alt="User Avatar" style="max-width:100%;max-height:100%;object-fit:contain;">
                </div>
                <div class="d-none d-md-block">
                    <div style="line-height:1.2;font-family:'Kantumruy Pro','Hanuman',sans-serif;"><?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                    <div style="font-size:.62rem;color:#94A3B8;font-weight:500;text-transform:uppercase;letter-spacing:.04em;"><?= e($user['role'] ?? '') ?></div>
                </div>
            </div>

            <a href="<?= BASE_URL ?>/logout.php" class="btn-admin-secondary" style="padding:7px 10px;" title="Logout">
                <i class="bi bi-power" style="color:#DC2626;"></i>
            </a>
        </div>
    </header>

    <!-- Flash messages -->
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="admin-content pb-0 pt-3">
        <div class="alert-kdlms <?= e($flash['type']) ?>">
            <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle' : ($flash['type'] === 'error' ? 'bi-exclamation-circle' : 'bi-info-circle') ?>"></i>
            <span><?= e($flash['message']) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- CONTENT starts here — closed in admin_footer.php or page itself -->
    <div class="admin-content">
