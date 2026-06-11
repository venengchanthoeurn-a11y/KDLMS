<?php
// ================================================================
// admin/categories.php — Manage Categories
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$pdo    = getPDO();
$action = $_GET['action'] ?? '';
$catId  = (int)($_GET['id'] ?? 0);

// --- Handle form submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $nameKh   = trim($_POST['name_kh']   ?? '');
    $nameEn   = trim($_POST['name_en']   ?? '');
    $slug     = trim($_POST['slug']      ?? '');
    $icon     = trim($_POST['icon']      ?? 'bi-folder');
    $color    = trim($_POST['color']     ?? '#8B0000');
    $desc     = trim($_POST['description'] ?? '');
    $parentId = (int)($_POST['parent_id'] ?? 0) ?: null;
    $sortOrd  = (int)($_POST['sort_order'] ?? 0);
    $editId   = (int)($_POST['edit_id'] ?? 0);

    if (empty($nameKh) || empty($nameEn) || empty($slug)) {
        setFlash('error', 'Name KH, Name EN and Slug are required.');
    } else {
        // Make slug URL-safe
        $slug = strtolower(preg_replace('/[^a-z0-9\-]/', '', str_replace(' ', '-', $slug)));

        if ($editId > 0) {
            $pdo->prepare(
                "UPDATE categories SET name_kh=?,name_en=?,slug=?,icon=?,color=?,description=?,parent_id=?,sort_order=? WHERE id=?"
            )->execute([$nameKh,$nameEn,$slug,$icon,$color,$desc,$parentId,$sortOrd,$editId]);
            setFlash('success', 'Category updated successfully.');
        } else {
            $pdo->prepare(
                "INSERT INTO categories (name_kh,name_en,slug,icon,color,description,parent_id,sort_order) VALUES (?,?,?,?,?,?,?,?)"
            )->execute([$nameKh,$nameEn,$slug,$icon,$color,$desc,$parentId,$sortOrd]);
            setFlash('success', 'Category added successfully.');
        }
    }
    redirect(BASE_URL . '/admin/categories.php');
}

// --- Delete action ---
if ($action === 'delete' && $catId > 0) {
    // Reassign books to NULL before deleting
    $pdo->prepare("UPDATE books SET category_id=NULL WHERE category_id=?")->execute([$catId]);
    $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$catId]);
    setFlash('warning', 'Category deleted. Books set to uncategorized.');
    redirect(BASE_URL . '/admin/categories.php');
}

// Fetch edit data
$editCat = null;
if ($catId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([$catId]);
    $editCat = $stmt->fetch();
}

// Fetch all categories with book count
$categories = getCategories(withCount: true);

$pageTitle = 'ប្រភេទ · Categories';
$activeNav = 'categories';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="row g-4">
    <!-- Category List -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title"><i class="bi bi-tags"></i> Categories (<?= count($categories) ?>)</div>
                <a href="categories.php" class="btn-admin-secondary" style="padding:5px 12px;font-size:.75rem;">
                    <i class="bi bi-plus"></i> Add New
                </a>
            </div>
            <div style="overflow-x:auto;">
                <table class="kdlms-table">
                    <thead>
                        <tr>
                            <th>Icon/Color</th>
                            <th>ឈ្មោះ / Name</th>
                            <th>Slug</th>
                            <th>Books</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="width:32px;height:32px;border-radius:8px;background:<?= e($cat['color']) ?>20;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:<?= e($cat['color']) ?>;">
                                        <i class="bi <?= e($cat['icon']) ?>"></i>
                                    </div>
                                    <div style="width:14px;height:14px;border-radius:4px;background:<?= e($cat['color']) ?>;border:1px solid rgba(0,0,0,.1);"></div>
                                </div>
                            </td>
                            <td>
                                <div style="font-family:'Kantumruy Pro',sans-serif;font-weight:700;font-size:.82rem;"><?= e($cat['name_kh']) ?></div>
                                <div style="font-size:.72rem;color:#94A3B8;"><?= e($cat['name_en']) ?></div>
                            </td>
                            <td><code style="font-size:.72rem;background:#F1F5F9;padding:2px 8px;border-radius:4px;"><?= e($cat['slug']) ?></code></td>
                            <td style="font-family:'Inter',sans-serif;font-weight:700;color:#1A3A5C;"><?= number_format($cat['book_count'] ?? 0) ?></td>
                            <td style="font-family:'Inter',sans-serif;font-size:.82rem;"><?= $cat['sort_order'] ?></td>
                            <td>
                                <div style="display:flex;gap:4px;">
                                    <a href="categories.php?id=<?= $cat['id'] ?>" class="action-btn edit" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <a href="categories.php?action=delete&id=<?= $cat['id'] ?>"
                                       class="action-btn delete" title="Delete"
                                       onclick="return confirm('Delete category \'<?= addslashes(e($cat['name_en'])) ?>\'? Books will be uncategorized.')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Form -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title">
                    <i class="bi <?= $editCat ? 'bi-pencil' : 'bi-plus-circle' ?>"></i>
                    <?= $editCat ? 'Edit Category' : 'Add New Category' ?>
                </div>
            </div>
            <div class="admin-card-body">
                <form method="POST" action="categories.php">
                    <?= csrfField() ?>
                    <?php if ($editCat): ?>
                    <input type="hidden" name="edit_id" value="<?= $editCat['id'] ?>">
                    <?php endif; ?>

                    <div class="admin-form-group">
                        <label class="admin-form-label">ឈ្មោះខ្មែរ · Khmer Name <span class="required">*</span></label>
                        <input type="text" name="name_kh" class="admin-form-input"
                               placeholder="ជីវវិទ្យា"
                               value="<?= e($editCat['name_kh'] ?? '') ?>" required>
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">English Name <span class="required">*</span></label>
                        <input type="text" name="name_en" class="admin-form-input"
                               placeholder="Biology"
                               value="<?= e($editCat['name_en'] ?? '') ?>" required>
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">Slug (URL-friendly) <span class="required">*</span></label>
                        <input type="text" name="slug" class="admin-form-input"
                               placeholder="biology"
                               value="<?= e($editCat['slug'] ?? '') ?>" required>
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">Bootstrap Icon class</label>
                        <input type="text" name="icon" class="admin-form-input"
                               placeholder="bi-tree"
                               value="<?= e($editCat['icon'] ?? 'bi-folder') ?>">
                        <div style="font-size:.68rem;color:#94A3B8;margin-top:3px;">e.g. bi-book, bi-laptop, bi-globe. Find at icons.getbootstrap.com</div>
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">Color</label>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <input type="color" name="color" data-preview="colorPreview"
                                   value="<?= e($editCat['color'] ?? '#8B0000') ?>"
                                   style="width:40px;height:38px;border:1px solid #E2E8F0;border-radius:8px;cursor:pointer;padding:2px;">
                            <div id="colorPreview" style="width:38px;height:38px;border-radius:8px;background:<?= e($editCat['color'] ?? '#8B0000') ?>;border:1px solid rgba(0,0,0,.1);"></div>
                            <input type="text" name="color_text" placeholder="#8B0000"
                                   value="<?= e($editCat['color'] ?? '#8B0000') ?>"
                                   class="admin-form-input" style="flex:1;">
                        </div>
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="admin-form-input"
                               value="<?= e($editCat['sort_order'] ?? 0) ?>" min="0">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">Description</label>
                        <textarea name="description" class="admin-form-textarea" rows="2"
                                  placeholder="Short description..."><?= e($editCat['description'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-admin-primary">
                            <i class="bi <?= $editCat ? 'bi-check-lg' : 'bi-plus-lg' ?>"></i>
                            <?= $editCat ? 'Update Category' : 'Add Category' ?>
                        </button>
                        <?php if ($editCat): ?>
                        <a href="categories.php" class="btn-admin-secondary"><i class="bi bi-x"></i> Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
