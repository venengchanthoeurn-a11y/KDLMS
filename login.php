<?php
// ================================================================
// login.php — User Login Page
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect appropriately
if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? 'user';
    redirect(in_array($role, ['admin','superadmin']) ? BASE_URL . '/admin/index.php' : BASE_URL . '/browse.php');
}

$error   = '';
$success = '';

// --- Process login form ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    // Input validation
    if (empty($email) || empty($password)) {
        $error = 'សូមបំពេញ Email និង Password · Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email មិនត្រឹមត្រូវ · Invalid email format.';
    } elseif (!checkLoginRateLimit($email)) {
        $remaining = getRemainingLockoutSeconds($email);
        $mins      = ceil($remaining / 60);
        $error     = "គណនីត្រូវបានខ្ទប់ · Too many failed attempts. Try again in {$mins} minute(s).";
    } else {
        // Fetch user from database
        $pdo  = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'banned') {
                $error = 'គណនីរបស់អ្នកត្រូវបានផ្អាក · Your account has been suspended.';
            } else {
                // Successful login
                resetLoginAttempts($email);
                session_regenerate_id(true); // Prevent session fixation

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                // Update last_login timestamp
                $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?")->execute([$user['id']]);

                // Redirect based on role
                $redirectTo = $_SESSION['redirect_after_login'] ?? null;
                unset($_SESSION['redirect_after_login']);

                if (in_array($user['role'], ['admin', 'superadmin'])) {
                    redirect($redirectTo ?: BASE_URL . '/admin/index.php');
                } else {
                    redirect($redirectTo ?: BASE_URL . '/browse.php');
                }
            }
        } else {
            // Failed login
            $attempts = recordFailedLogin($email);
            $remaining = 5 - $attempts;
            if ($remaining > 0) {
                $error = "Email ឬ Password មិនត្រឹមត្រូវ · Invalid credentials. ({$remaining} attempt(s) left)";
            } else {
                $error = 'គណនីត្រូវបានខ្ទប់ 10 នាទី · Account locked for 10 minutes.';
            }
        }
    }
}

// Check for message from redirect
$msgParam = $_GET['msg'] ?? '';
if ($msgParam === 'login_required') {
    $error = 'សូម Login ជាមុន ដើម្បីទាញយកឯកសារ · Please login to download files.';
}
if ($msgParam === 'registered') {
    $success = 'ការចុះឈ្មោះជោគជ័យ! · Registration successful! Please login.';
}
if ($msgParam === 'logged_out') {
    $success = 'អ្នកបានចាកចេញ · You have been logged out successfully.';
}
?>
<!DOCTYPE html>
<html lang="km" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ចូលប្រើ · Login — KDLMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moul&family=Hanuman:wght@300;400;700&family=Kantumruy+Pro:wght@300;400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="auth-page">
    <div class="auth-card">
        <!-- Header -->
        <div class="auth-header">
            <div class="auth-logo"><i class="bi bi-building"></i></div>
            <h1 class="auth-title">ចូលប្រើប្រព័ន្ធ</h1>
            <p class="auth-subtitle">Sign in to KDLMS · Khmer Digital Library</p>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
        <div class="alert-kdlms error mb-3">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span><?= e($error) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert-kdlms success mb-3">
            <i class="bi bi-check-circle-fill"></i>
            <span><?= e($success) ?></span>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="login.php" novalidate>
            <?= csrfField() ?>

            <div class="mb-3">
                <label class="form-label-kh" for="email">
                    <i class="bi bi-envelope me-1"></i>Email Address
                </label>
                <div class="position-relative">
                    <input type="email"
                           id="email"
                           name="email"
                           class="form-control-kdlms"
                           placeholder="yourname@example.com"
                           value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>"
                           required autocomplete="email">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label-kh" for="password">
                    <i class="bi bi-lock me-1"></i>ពាក្យសម្ងាត់ · Password
                </label>
                <div class="position-relative">
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control-kdlms"
                           placeholder="Enter your password"
                           required autocomplete="current-password">
                    <button type="button"
                            onclick="togglePassword('password', 'passwordEyeIcon')"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);">
                        <i class="bi bi-eye" id="passwordEyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary-kdlms w-100 justify-content-center" style="padding:12px;">
                <i class="bi bi-box-arrow-in-right"></i>
                ចូលប្រើ · Sign In
            </button>
        </form>

        <!-- Divider -->
        <div class="lotus-divider mt-4">
            <span class="lotus-divider-inner" style="background:white;"><i class="bi bi-flower1"></i></span>
        </div>

        <!-- Register link -->
        <?php if (getSetting('allow_registration', '1') === '1'): ?>
        <p class="text-center mt-3" style="font-family:var(--font-kantumruy);font-size:.82rem;color:var(--text-light);">
            មិនទាន់មានគណនី? ·
            <a href="<?= BASE_URL ?>/register.php" style="color:var(--primary);font-weight:700;text-decoration:none;">
                ចុះឈ្មោះ · Register Free
            </a>
        </p>
        <?php endif; ?>

        <p class="text-center mt-2">
            <a href="<?= BASE_URL ?>/" style="font-family:var(--font-kantumruy);font-size:.78rem;color:var(--text-muted);text-decoration:none;">
                <i class="bi bi-arrow-left me-1"></i>ត្រឡប់ទំព័រដើម · Back to Home
            </a>
        </p>

        <!-- Demo credentials info -->
        
</div>

<script>
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>
