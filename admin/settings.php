<?php
// ================================================================
// admin/settings.php — System Settings
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireSuperadmin(); // Only superadmin can change settings

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $settingKeys = [
        'site_name_kh','site_name_en','site_tagline_kh','site_tagline_en',
        'allow_registration','require_email_verify','max_file_size_mb',
        'allowed_extensions','books_per_page','maintenance_mode',
        'admin_email','contact_email',
    ];

    foreach ($settingKeys as $key) {
        if (isset($_POST[$key])) {
            setSetting($key, trim($_POST[$key]));
        }
    }

    // Handle checkboxes (0 when unchecked)
    foreach (['allow_registration','require_email_verify','maintenance_mode'] as $k) {
        setSetting($k, isset($_POST[$k]) ? '1' : '0');
    }

    setFlash('success', 'Settings saved successfully · ការកំណត់ត្រូវបានរក្សាទុក');
    redirect(BASE_URL . '/admin/settings.php');
}

// Load current settings
$s = [];
$rows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
foreach ($rows as $row) $s[$row['setting_key']] = $row['setting_value'];

$pageTitle = 'System Settings';
$activeNav = 'settings';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="row g-4">
    <div class="col-lg-8">
        <form method="POST" action="settings.php">
            <?= csrfField() ?>

            <!-- Site Info -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <div class="admin-card-title"><i class="bi bi-globe"></i> Site Information</div>
                </div>
                <div class="admin-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="admin-form-group">
                                <label class="admin-form-label">ឈ្មោះ​ប្រព័ន្ធ​ (Khmer)</label>
                                <input type="text" name="site_name_kh" class="admin-form-input"
                                       value="<?= e($s['site_name_kh'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="admin-form-group">
                                <label class="admin-form-label">Site Name (English)</label>
                                <input type="text" name="site_name_en" class="admin-form-input"
                                       value="<?= e($s['site_name_en'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="admin-form-group">
                                <label class="admin-form-label">Tagline (Khmer)</label>
                                <input type="text" name="site_tagline_kh" class="admin-form-input"
                                       value="<?= e($s['site_tagline_kh'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="admin-form-group">
                                <label class="admin-form-label">Tagline (English)</label>
                                <input type="text" name="site_tagline_en" class="admin-form-input"
                                       value="<?= e($s['site_tagline_en'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="admin-form-group">
                                <label class="admin-form-label">Admin Email</label>
                                <input type="email" name="admin_email" class="admin-form-input"
                                       value="<?= e($s['admin_email'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="admin-form-group">
                                <label class="admin-form-label">Contact Email</label>
                                <input type="email" name="contact_email" class="admin-form-input"
                                       value="<?= e($s['contact_email'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upload Settings -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <div class="admin-card-title"><i class="bi bi-upload"></i> Upload Configuration</div>
                </div>
                <div class="admin-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="admin-form-group">
                                <label class="admin-form-label">Max File Size (MB)</label>
                                <input type="number" name="max_file_size_mb" class="admin-form-input"
                                       value="<?= e($s['max_file_size_mb'] ?? '200') ?>" min="1" max="2000">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="admin-form-group">
                                <label class="admin-form-label">Books Per Page</label>
                                <input type="number" name="books_per_page" class="admin-form-input"
                                       value="<?= e($s['books_per_page'] ?? '20') ?>" min="5" max="100">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="admin-form-group">
                                <label class="admin-form-label">Allowed File Extensions (comma-separated)</label>
                                <input type="text" name="allowed_extensions" class="admin-form-input"
                                       value="<?= e($s['allowed_extensions'] ?? 'pdf,docx,epub,txt,zip,mp4,pptx,xlsx') ?>"
                                       placeholder="pdf,docx,epub,txt,zip">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Switches -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <div class="admin-card-title"><i class="bi bi-toggles"></i> System Switches</div>
                </div>
                <div class="admin-card-body">
                    <?php
                    $toggles = [
                        ['key'=>'allow_registration',   'label'=>'Allow User Registration · ការចុះឈ្មោះ', 'desc'=>'Users can register new accounts'],
                        ['key'=>'require_email_verify',  'label'=>'Require Email Verification', 'desc'=>'Users must verify email before login'],
                        ['key'=>'maintenance_mode',      'label'=>'Maintenance Mode · ម៉ូតថែទាំ', 'desc'=>'Block all non-admin access'],
                    ];
                    foreach ($toggles as $t):
                    ?>
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:12px 0;border-bottom:1px solid #F1F5F9;gap:12px;">
                        <div>
                            <div style="font-family:'Kantumruy Pro',sans-serif;font-size:.85rem;font-weight:600;color:#1E293B;"><?= $t['label'] ?></div>
                            <div style="font-size:.75rem;color:#94A3B8;font-family:'Inter',sans-serif;"><?= $t['desc'] ?></div>
                        </div>
                        <label style="position:relative;display:inline-block;width:48px;height:26px;flex-shrink:0;cursor:pointer;">
                            <input type="checkbox" name="<?= $t['key'] ?>" value="1"
                                   <?= ($s[$t['key']] ?? '0') === '1' ? 'checked' : '' ?>
                                   style="opacity:0;width:0;height:0;">
                            <span style="position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#E2E8F0;border-radius:26px;transition:.3s;"
                                  onclick="this.previousElementSibling.click();this.style.background=this.previousElementSibling.checked?'#8B0000':'#E2E8F0';"></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn-admin-primary">
                    <i class="bi bi-save"></i> Save All Settings
                </button>
                <a href="index.php" class="btn-admin-secondary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
            </div>
        </form>
    </div>

    <!-- System Info Sidebar -->
    <div class="col-lg-4">
        <div class="admin-card mb-3">
            <div class="admin-card-header">
                <div class="admin-card-title"><i class="bi bi-server"></i> System Information</div>
            </div>
            <div class="admin-card-body" style="font-family:'Inter',sans-serif;font-size:.8rem;line-height:2;">
                <div style="display:flex;justify-content:space-between;"><span style="color:#64748B;">PHP Version</span><strong><?= PHP_VERSION ?></strong></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:#64748B;">KDLMS Version</span><strong>v<?= APP_VERSION ?></strong></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:#64748B;">Database</span><strong>MySQL (PDO)</strong></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:#64748B;">Upload Path</span><strong style="font-size:.68rem;word-break:break-all;">/uploads/</strong></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:#64748B;">Server Date</span><strong><?= date('d/m/Y H:i') ?></strong></div>
            </div>
        </div>

        <div class="admin-card" style="border:1px solid rgba(200,150,12,.3);background:linear-gradient(135deg,#FFF8F0,#FFFDF8);">
            <div class="admin-card-header" style="background:transparent;">
                <div class="admin-card-title" style="color:#92400E;"><i class="bi bi-person-badge"></i> System Creator</div>
            </div>
            <div class="admin-card-body" style="font-family:'Inter',sans-serif;font-size:.82rem;text-align:center;padding:1rem;">
                <div style="width:90px;height:90px;border-radius:50%;overflow:hidden;margin:0 auto 1rem;border:2px solid #C8960C;display:flex;align-items:center;justify-content:center;background:#fff;padding:2px;">
                    <img src="<?= BASE_URL ?>/assets/img/creator.jpg" alt="អេង ចាន់ធឿន" style="width:100%;height:100%;object-fit:contain;">
                </div>
                <div style="font-family:'Moul',serif;font-size:.9rem;color:#8B0000;margin-bottom:3px;">អេង ចាន់ធឿន</div>
                <div style="color:#64748B;font-size:.75rem;">Eng Chanthoeun - Vthe</div>
                <div style="color:#94A3B8;font-size:.68rem;margin-top:4px;">KDLMS v<?= APP_VERSION ?> · <?= date('Y') ?></div>
            </div>
        </div>
    </div>
</div>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
// Sync toggle background on page load
document.querySelectorAll('input[type=checkbox]').forEach(cb => {
    const slider = cb.nextElementSibling;
    if (slider) slider.style.background = cb.checked ? '#8B0000' : '#E2E8F0';
});
</script>
</body>
</html>
