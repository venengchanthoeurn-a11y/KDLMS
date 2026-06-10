<?php
// ================================================================
// register.php — User Registration Page
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// If already logged in, redirect
if (isLoggedIn()) {
    redirect(BASE_URL . '/browse.php');
}

// Check if registration is allowed
if (getSetting('allow_registration', '1') !== '1') {
    http_response_code(403);
    die('<div style="font-family:Hanuman,serif;text-align:center;padding:60px;color:#8B0000;"><h2>ការចុះឈ្មោះ​​ត្រូវបានបិទ · Registration Disabled</h2></div>');
}

$error   = '';
$success = '';

// CAPTCHA setup (simple math)
if (empty($_SESSION['captcha_a'])) {
    $_SESSION['captcha_a'] = rand(1, 9);
    $_SESSION['captcha_b'] = rand(1, 9);
}
$captchaQ = $_SESSION['captcha_a'] . ' + ' . $_SESSION['captcha_b'];

// --- Process registration form ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');
    $captcha  = (int)($_POST['captcha'] ?? -1);

    // --- Validation ---
    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'សូមបំពេញបែបបទឱ្យគ្រប់ · Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email មិនត្រឹមត្រូវ · Invalid email format.';
    } elseif (strlen($password) < 8) {
        $error = 'Password ត្រូវការ 8 តួអក្សរ · Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password ត្រូវមានអក្សរធំ · Password must include at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password ត្រូវមានតួលេខ · Password must include at least one number.';
    } elseif ($password !== $confirm) {
        $error = 'Password មិនត្រូវគ្នា · Passwords do not match.';
    } elseif ($captcha !== ($_SESSION['captcha_a'] + $_SESSION['captcha_b'])) {
        $error = 'ចម្លើយ CAPTCHA មិនត្រឹមត្រូវ · Incorrect CAPTCHA answer.';
        // Regenerate captcha
        $_SESSION['captcha_a'] = rand(1, 9);
        $_SESSION['captcha_b'] = rand(1, 9);
        $captchaQ = $_SESSION['captcha_a'] . ' + ' . $_SESSION['captcha_b'];
    } else {
        // Check email uniqueness
        $pdo  = getPDO();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'Email នេះមានការចុះឈ្មោះរួចហើយ · This email is already registered.';
        } else {
            // Hash password & insert user
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password, role, is_verified, status) VALUES (?, ?, ?, 'user', 1, 'active')"
            );
            $stmt->execute([$name, $email, $hashedPassword]);

            // Clear captcha
            unset($_SESSION['captcha_a'], $_SESSION['captcha_b']);

            redirect(BASE_URL . '/login.php?msg=registered');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="km" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ចុះឈ្មោះ · Register — KDLMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moul&family=Kantumruy+Pro:wght@300;400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="auth-page">
    <div class="auth-card" style="max-width:480px;">
        <!-- Header -->
        <div class="auth-header">
            <div class="auth-logo"><i class="bi bi-person-plus"></i></div>
            <h1 class="auth-title">ចុះឈ្មោះ · Register</h1>
            <p class="auth-subtitle">Create your free KDLMS account</p>
        </div>

        <!-- Alert -->
        <?php if ($error): ?>
        <div class="alert-kdlms error mb-3">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span><?= e($error) ?></span>
        </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form method="POST" action="register.php" novalidate id="registerForm">
            <?= csrfField() ?>

            <!-- Full Name -->
            <div class="mb-3">
                <label class="form-label-kh" for="reg_name">
                    <i class="bi bi-person me-1"></i>ឈ្មោះពេញ · Full Name <span style="color:#DC2626;">*</span>
                </label>
                <input type="text" id="reg_name" name="name" class="form-control-kdlms"
                       placeholder="e.g. ចាន់ ធឿន / Chan Thoeun"
                       value="<?= isset($_POST['name']) ? e($_POST['name']) : '' ?>"
                       required autocomplete="name">
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label class="form-label-kh" for="reg_email">
                    <i class="bi bi-envelope me-1"></i>Email Address <span style="color:#DC2626;">*</span>
                </label>
                <input type="email" id="reg_email" name="email" class="form-control-kdlms"
                       placeholder="yourname@example.com"
                       value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>"
                       required autocomplete="email">
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label class="form-label-kh" for="reg_password">
                    <i class="bi bi-lock me-1"></i>ពាក្យសម្ងាត់ · Password <span style="color:#DC2626;">*</span>
                </label>
                <div class="position-relative">
                    <input type="password" id="reg_password" name="password" class="form-control-kdlms"
                           placeholder="Min 8 chars, 1 uppercase, 1 number"
                           oninput="checkPasswordStrength(this.value)"
                           required autocomplete="new-password">
                    <button type="button" onclick="togglePassword('reg_password','eyeIcon1')"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);">
                        <i class="bi bi-eye" id="eyeIcon1"></i>
                    </button>
                </div>
                <!-- Password strength indicator -->
                <div style="margin-top:6px;height:4px;background:#E5E7EB;border-radius:2px;overflow:hidden;">
                    <div id="strengthBar" style="height:100%;width:0%;transition:all .3s;border-radius:2px;"></div>
                </div>
                <div id="strengthText" style="font-family:'Inter',sans-serif;font-size:.68rem;color:#94A3B8;margin-top:3px;"></div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-3">
                <label class="form-label-kh" for="reg_confirm">
                    <i class="bi bi-lock-fill me-1"></i>បញ្ជាក់ Password · Confirm Password <span style="color:#DC2626;">*</span>
                </label>
                <div class="position-relative">
                    <input type="password" id="reg_confirm" name="confirm" class="form-control-kdlms"
                           placeholder="Re-enter your password"
                           required autocomplete="new-password">
                    <button type="button" onclick="togglePassword('reg_confirm','eyeIcon2')"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);">
                        <i class="bi bi-eye" id="eyeIcon2"></i>
                    </button>
                </div>
            </div>

            <!-- CAPTCHA -->
            <div class="mb-4">
                <label class="form-label-kh" for="reg_captcha">
                    <i class="bi bi-shield-check me-1"></i>ការការពារ Bot · Anti-Bot: <strong style="color:var(--primary);"><?= $captchaQ ?> = ?</strong>
                </label>
                <input type="number" id="reg_captcha" name="captcha" class="form-control-kdlms"
                       placeholder="Enter the answer" required min="1" max="18">
            </div>

            <button type="submit" class="btn-primary-kdlms w-100 justify-content-center" style="padding:12px;">
                <i class="bi bi-person-check"></i>
                ចុះឈ្មោះ · Create Account
            </button>
        </form>

        <p class="text-center mt-3" style="font-family:var(--font-kantumruy);font-size:.82rem;color:var(--text-light);">
            មានគណនីរួចហើយ? ·
            <a href="<?= BASE_URL ?>/login.php" style="color:var(--primary);font-weight:700;text-decoration:none;">
                ចូលប្រើ · Sign In
            </a>
        </p>
        <p class="text-center mt-1">
            <a href="<?= BASE_URL ?>/" style="font-family:var(--font-kantumruy);font-size:.78rem;color:var(--text-muted);text-decoration:none;">
                <i class="bi bi-arrow-left me-1"></i>ត្រឡប់ទំព័រដើម
            </a>
        </p>
    </div>
</div>

<script>
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
}

function checkPasswordStrength(password) {
    const bar  = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    let score  = 0;
    if (password.length >= 8)         score++;
    if (/[A-Z]/.test(password))       score++;
    if (/[0-9]/.test(password))       score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;

    const levels = [
        { color:'#DC2626', label:'Weak / ខ្សោយ',       width:'25%' },
        { color:'#D97706', label:'Fair / ធម្មតា',        width:'50%' },
        { color:'#059669', label:'Good / ល្អ',           width:'75%' },
        { color:'#1B4332', label:'Strong / រឹងមាំ',      width:'100%'},
    ];

    const level = levels[Math.min(score - 1, 3)] || levels[0];
    if (password.length === 0) {
        bar.style.width = '0%';
        text.textContent = '';
    } else {
        bar.style.width      = level.width;
        bar.style.background = level.color;
        text.textContent     = level.label;
        text.style.color     = level.color;
    }
}
</script>
</body>
</html>
