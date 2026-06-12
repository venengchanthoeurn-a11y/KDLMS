<?php
// ================================================================
// includes/footer.php — Public Page Footer
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

$siteName   = getSetting('site_name_kh', 'បណ្ណាល័យឌីជីថលខ្មែរ');
$siteNameEn = getSetting('site_name_en', 'Khmer Digital Library');
$stats      = getStats();
?>

<!-- Lotus Divider -->
<div class="lotus-divider">
    <span class="lotus-divider-inner"><i class="bi bi-flower1"></i></span>
</div>

<!-- ================================================================
     FOOTER
     ================================================================ -->
<footer class="kdlms-footer">
    <div class="container">
        <div class="row g-4">
            <!-- Brand column -->
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div style="width:40px;height:40px;background:linear-gradient(135deg,#8B0000,#C8960C);border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-size:1.2rem;">
                        <i class="bi bi-building"></i>
                    </div>
                    <div>
                        <div class="footer-brand"><?= e($siteName) ?></div>
                        <div style="font-size:.6rem;color:rgba(255,255,255,.4);font-family:'Inter',sans-serif;letter-spacing:.08em;text-transform:uppercase;"><?= e($siteNameEn) ?></div>
                    </div>
                </div>
                <p class="footer-desc">ប្រព័ន្ធបណ្ណាល័យឌីជីថលដ៏ទំនើប សម្រាប់ការអប់រំ និងការស្រាវជ្រាវវិទ្យាសាស្ត្រ ។ ចំណេះដឹងគ្មានព្រំដែន — Knowledge Without Boundaries.</p>

                <!-- Stats mini in footer -->
                <div class="d-flex gap-3 mt-3 flex-wrap">
                    <div style="text-align:center;">
                        <div style="font-family:'Inter',sans-serif;font-weight:700;color:#C8960C;font-size:1rem;"><?= number_format($stats['books']) ?></div>
                        <div style="font-size:.65rem;color:rgba(255,255,255,.4);font-family:'Kantumruy Pro',sans-serif;">សៀវភៅ</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-family:'Inter',sans-serif;font-weight:700;color:#C8960C;font-size:1rem;"><?= $stats['categories'] ?></div>
                        <div style="font-size:.65rem;color:rgba(255,255,255,.4);font-family:'Kantumruy Pro',sans-serif;">ប្រភេទ</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-family:'Inter',sans-serif;font-weight:700;color:#C8960C;font-size:1rem;"><?= number_format($stats['users']) ?></div>
                        <div style="font-size:.65rem;color:rgba(255,255,255,.4);font-family:'Kantumruy Pro',sans-serif;">សមាជិក</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-family:'Inter',sans-serif;font-weight:700;color:#C8960C;font-size:1rem;"><?= number_format($stats['downloads']) ?></div>
                        <div style="font-size:.65rem;color:rgba(255,255,255,.4);font-family:'Kantumruy Pro',sans-serif;">ទាញយក</div>
                    </div>
                </div>
            </div>

            <!-- Quick links -->
            <div class="col-6 col-lg-2">
                <div class="footer-heading">ជំនួយ</div>
                <a href="<?= BASE_URL ?>/" class="footer-link">ទំព័រដើម</a>
                <a href="<?= BASE_URL ?>/browse.php" class="footer-link">ស្វែងរកសៀវភៅ</a>
                <a href="<?= BASE_URL ?>/register.php" class="footer-link">ចុះឈ្មោះ</a>
                <a href="<?= BASE_URL ?>/login.php" class="footer-link">ចូលប្រើ</a>
            </div>

            <!-- Categories list -->
            <div class="col-6 col-lg-3">
                <div class="footer-heading">ប្រភេទសៀវភៅ</div>
                <?php
                $footerCats = getCategories(false);
                $shown = 0;
                foreach ($footerCats as $fc):
                    if ($shown++ >= 8) break;
                ?>
                <a href="<?= BASE_URL ?>/browse.php?category=<?= e($fc['id']) ?>" class="footer-link">
                    <?= e($fc['name_kh']) ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- About / Creator -->
            <div class="col-lg-3">
                <div class="footer-heading">អំពីប្រព័ន្ធ</div>
                <p class="footer-desc" style="font-size:.78rem;">
                    ប្រព័ន្ធ KDLMS ត្រូវបានរចនា និងអភិវឌ្ឍដោយប្រើ PHP 8, MySQL, Bootstrap 5 ក្នុងគោលបំណង ផ្ដល់ការចូលដំណើរការឯកសារ និងសៀវភៅ ប្រកបដោយភាព ប្រសើរ។
                </p>
                <div style="margin-top:12px;padding:10px 14px;background:rgba(200,150,12,.12);border:1px solid rgba(200,150,12,.25);border-radius:8px;font-size:.75rem;display:flex;align-items:center;gap:12px;">
                    <div style="width:50px;height:50px;border-radius:50%;border:1.5px solid #C8960C;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;padding:2px;">
                        <img src="<?= BASE_URL ?>/assets/img/creator.jpg" alt="អេង ចាន់ធឿន" style="max-width:100%;max-height:100%;object-fit:contain;">
                    </div>
                    <div>
                        <div style="color:#F5D76E;font-family:'Kantumruy Pro',sans-serif;font-weight:700;margin-bottom:3px;">
                            <i class="bi bi-code-slash me-1"></i>បង្កើតដោយ (Created by)
                        </div>
                        <div style="color:rgba(255,255,255,.85);font-family:'Hanuman',serif;font-weight:700;">
                            អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
                        </div>
                        <div style="color:rgba(255,255,255,.4);font-size:.65rem;font-family:'Inter',sans-serif;margin-top:2px;">
                            KDLMS v<?= APP_VERSION ?> © <?= date('Y') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom bar -->
        <div class="footer-bottom">
            <p>
                © <?= date('Y') ?> <span class="footer-creator">បណ្ណាល័យឌីជីថលខ្មែរ (KDLMS)</span> —
                បង្កើតដោយ <span class="footer-creator">អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)</span> ·
                Built with PHP 8 + MySQL + Bootstrap 5.3
            </p>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- KDLMS Main JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
