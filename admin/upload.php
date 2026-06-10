<?php
// ================================================================
// admin/upload.php — File Upload Page
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pdo        = getPDO();
$categories = getCategories();
$maxSizeMB  = (int)getSetting('max_file_size_mb', '200');
$allowedExt = getAllowedExtensions();

// --- Handle file upload ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $titleKh  = trim($_POST['title_kh']  ?? '');
    $titleEn  = trim($_POST['title_en']  ?? '');
    $author   = trim($_POST['author']    ?? '');
    $catId    = (int)($_POST['category_id'] ?? 0);
    $desc     = trim($_POST['description'] ?? '');
    $tags     = trim($_POST['tags']      ?? '');
    $lang     = $_POST['language']       ?? 'kh';
    $year     = $_POST['published_year'] ?? null;
    $featured = isset($_POST['is_featured']) ? 1 : 0;

    $errors = [];

    if (empty($titleKh)) $errors[] = 'ចំណងជើងខ្មែរ (Title KH) is required.';
    if ($catId <= 0)     $errors[] = 'Please select a category.';

    // --- File upload validation ---
    if (empty($_FILES['book_file']['name'])) {
        $errors[] = 'Please select a file to upload.';
    } else {
        $file      = $_FILES['book_file'];
        $origName  = basename($file['name']);
        $tmpPath   = $file['tmp_name'];
        $fileSize  = $file['size'];
        $ext       = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if ($fileSize > $maxSizeMB * 1048576) {
            $errors[] = "File too large. Max size: {$maxSizeMB}MB.";
        } elseif (!in_array($ext, $allowedExt)) {
            $errors[] = "File type .$ext not allowed. Allowed: " . implode(', ', $allowedExt);
        } else {
            // Validate MIME type
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);

            // Get category slug for folder
            $catStmt = $pdo->prepare("SELECT slug FROM categories WHERE id = ?");
            $catStmt->execute([$catId]);
            $catRow  = $catStmt->fetch();
            $catSlug = $catRow['slug'] ?? 'other';

            // Build destination
            $uuid        = generateUUID();
            $newFileName = $uuid . '.' . $ext;
            $destDir     = UPLOAD_PATH . $catSlug . DIRECTORY_SEPARATOR;
            $destPath    = $destDir . $newFileName;
            $relPath     = $catSlug . '/' . $newFileName;

            // Create directory if needed
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            if (move_uploaded_file($tmpPath, $destPath)) {
                // Handle cover image
                $coverImage = null;
                if (!empty($_FILES['cover_image']['name'])) {
                    $coverFile = $_FILES['cover_image'];
                    $coverExt  = strtolower(pathinfo($coverFile['name'], PATHINFO_EXTENSION));
                    if (in_array($coverExt, ['jpg','jpeg','png','webp','gif'])) {
                        $coverDir  = dirname(__DIR__) . '/assets/img/covers/';
                        if (!is_dir($coverDir)) mkdir($coverDir, 0755, true);
                        $coverName = generateUUID() . '.' . $coverExt;
                        if (move_uploaded_file($coverFile['tmp_name'], $coverDir . $coverName)) {
                            $coverImage = $coverName;
                        }
                    }
                }

                // Insert to database
                $stmt = $pdo->prepare(
                    "INSERT INTO books (title_kh, title_en, author, category_id, description, cover_image,
                     file_path, file_name, file_size, file_type, file_extension, language, tags,
                     is_featured, status, uploaded_by, published_year)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,'active',?,?)"
                );
                $stmt->execute([
                    $titleKh, $titleEn ?: null, $author ?: null, $catId,
                    $desc ?: null, $coverImage,
                    $relPath, $origName, $fileSize, $mimeType, $ext,
                    $lang, $tags ?: null, $featured,
                    (int)$_SESSION['user_id'],
                    $year ?: null
                ]);

                // Return JSON for AJAX uploads
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Upload ជោគជ័យ · File uploaded successfully!']);
                    exit;
                }

                setFlash('success', 'Upload ជោគជ័យ! ·  File uploaded successfully.');
                redirect(BASE_URL . '/admin/books.php');
            } else {
                $errors[] = 'Failed to move uploaded file. Check server permissions.';
            }
        }
    }

    if (!empty($errors) && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => implode(' | ', $errors)]);
        exit;
    }
}

$pageTitle = 'Upload Files';
$activeNav = 'upload';
include '../includes/admin_header.php';

$maxSizeBytes = $maxSizeMB * 1048576;
?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title"><i class="bi bi-cloud-upload"></i> Upload New Book / File</div>
            </div>
            <div class="admin-card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert-kdlms error mb-3">
                    <i class="bi bi-exclamation-circle"></i>
                    <ul style="margin:0;padding-left:1rem;">
                        <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" action="upload.php" enctype="multipart/form-data" id="uploadForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="MAX_FILE_SIZE" value="<?= $maxSizeBytes ?>">

                    <!-- Dropzone -->
                    <div class="upload-dropzone mb-4" id="uploadDropzone">
                        <div class="upload-dropzone-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                        <div class="upload-dropzone-title">ទាក់ទង ឬចុចដើម្បី Upload · Drag & Drop or Click</div>
                        <div class="upload-dropzone-sub">
                            Allowed: <?= implode(', ', array_map('strtoupper', $allowedExt)) ?> · Max: <?= $maxSizeMB ?>MB
                        </div>
                    </div>
                    <input type="file" id="book_file" name="book_file" style="display:none;"
                           accept="<?= implode(',', array_map(fn($e) => ".$e", $allowedExt)) ?>">

                    <!-- Progress bar -->
                    <div class="upload-progress-wrap" id="uploadProgressWrap">
                        <div style="display:flex;justify-content:space-between;font-size:.75rem;color:#64748B;margin-bottom:4px;">
                            <span>Uploading...</span>
                            <span id="uploadPct">0%</span>
                        </div>
                        <div class="upload-progress-bar">
                            <div class="upload-progress-fill" id="uploadProgressFill"></div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <!-- Title KH -->
                        <div class="col-12">
                            <div class="admin-form-group">
                                <label class="admin-form-label">ចំណងជើងខ្មែរ · Khmer Title <span class="required">*</span></label>
                                <input type="text" name="title_kh" class="admin-form-input"
                                       placeholder="ចំណងជើងជាភាសាខ្មែរ"
                                       value="<?= e($_POST['title_kh'] ?? '') ?>" required>
                            </div>
                        </div>
                        <!-- Title EN -->
                        <div class="col-12">
                            <div class="admin-form-group">
                                <label class="admin-form-label">English Title</label>
                                <input type="text" name="title_en" class="admin-form-input"
                                       placeholder="Title in English (optional)"
                                       value="<?= e($_POST['title_en'] ?? '') ?>">
                            </div>
                        </div>
                        <!-- Author + Category -->
                        <div class="col-md-6">
                            <div class="admin-form-group">
                                <label class="admin-form-label">អ្នកនិពន្ធ · Author</label>
                                <input type="text" name="author" class="admin-form-input"
                                       placeholder="Author name(s)"
                                       value="<?= e($_POST['author'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="admin-form-group">
                                <label class="admin-form-label">ប្រភេទ · Category <span class="required">*</span></label>
                                <select name="category_id" class="admin-form-select" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                        <?= e($cat['name_kh']) ?> (<?= e($cat['name_en']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <!-- Language + Year -->
                        <div class="col-md-6">
                            <div class="admin-form-group">
                                <label class="admin-form-label">ភាសា · Language</label>
                                <select name="language" class="admin-form-select">
                                    <option value="kh"    <?= ($_POST['language']??'kh')==='kh'    ?'selected':''?>>🇰🇭 ភាសាខ្មែរ</option>
                                    <option value="en"    <?= ($_POST['language']??'')==='en'      ?'selected':''?>>🇺🇸 English</option>
                                    <option value="both"  <?= ($_POST['language']??'')==='both'    ?'selected':''?>>🇰🇭+🇺🇸 Both</option>
                                    <option value="other" <?= ($_POST['language']??'')==='other'   ?'selected':''?>>Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="admin-form-group">
                                <label class="admin-form-label">ឆ្នាំផ្សព្វផ្សាយ · Published Year</label>
                                <input type="number" name="published_year" class="admin-form-input"
                                       placeholder="e.g. 2024" min="1900" max="<?= date('Y') ?>"
                                       value="<?= e($_POST['published_year'] ?? '') ?>">
                            </div>
                        </div>
                        <!-- Tags -->
                        <div class="col-12">
                            <div class="admin-form-group">
                                <label class="admin-form-label">Tags (comma-separated)</label>
                                <input type="text" name="tags" class="admin-form-input"
                                       placeholder="e.g. php, web, programming, ការអប់រំ"
                                       value="<?= e($_POST['tags'] ?? '') ?>">
                            </div>
                        </div>
                        <!-- Description -->
                        <div class="col-12">
                            <div class="admin-form-group">
                                <label class="admin-form-label">ការពិពណ៌នា · Description</label>
                                <textarea name="description" class="admin-form-textarea" rows="3"
                                          placeholder="Short description of the book/document..."><?= e($_POST['description'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <!-- Cover image -->
                        <div class="col-12">
                            <div class="admin-form-group">
                                <label class="admin-form-label">រូបថតគម្រប · Cover Image (optional, JPG/PNG)</label>
                                <input type="file" name="cover_image" class="admin-form-input"
                                       accept=".jpg,.jpeg,.png,.webp" style="padding:6px;">
                            </div>
                        </div>
                        <!-- Featured -->
                        <div class="col-12">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-family:'Kantumruy Pro',sans-serif;font-size:.85rem;">
                                <input type="checkbox" name="is_featured" value="1"
                                       <?= isset($_POST['is_featured']) ? 'checked' : '' ?>>
                                <span><i class="bi bi-star me-1" style="color:var(--secondary);"></i>Featured Book (បង្ហាញ​ ​លើ​ Homepage)</span>
                            </label>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn-admin-primary">
                            <i class="bi bi-cloud-upload"></i> Upload File
                        </button>
                        <a href="books.php" class="btn-admin-secondary">
                            <i class="bi bi-x"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar info -->
    <div class="col-lg-4">
        <div class="admin-card mb-3">
            <div class="admin-card-header">
                <div class="admin-card-title"><i class="bi bi-info-circle"></i> Upload Rules</div>
            </div>
            <div class="admin-card-body" style="font-family:'Inter',sans-serif;font-size:.8rem;color:#374151;line-height:1.8;">
                <p>✅ Allowed types: <strong><?= implode(', ', array_map('strtoupper', $allowedExt)) ?></strong></p>
                <p>📁 Max size: <strong><?= $maxSizeMB ?> MB</strong></p>
                <p>🔒 Files renamed to UUID for security</p>
                <p>📂 Stored in <code>/uploads/[category]/</code></p>
                <p>🔍 MIME type validated server-side</p>
                <p>🚫 Direct URL access blocked by .htaccess</p>
            </div>
        </div>
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title"><i class="bi bi-bar-chart"></i> Storage Used</div>
            </div>
            <div class="admin-card-body" style="font-family:'Inter',sans-serif;font-size:.82rem;">
                <?php
                $totalSize = (int)$pdo->query("SELECT SUM(file_size) FROM books WHERE status='active'")->fetchColumn();
                $bookCount = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE status='active'")->fetchColumn();
                ?>
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #F1F5F9;">
                    <span style="color:#64748B;">Total Files</span>
                    <strong><?= number_format($bookCount) ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding:6px 0;">
                    <span style="color:#64748B;">Total Size</span>
                    <strong><?= formatFileSize($totalSize) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initUploadDropzone('uploadDropzone', 'book_file', 'uploadProgressWrap');
    uploadFileWithProgress('uploadForm', 'uploadProgressWrap', 'uploadProgressFill', 'uploadPct');
});
</script>
</body>
</html>
