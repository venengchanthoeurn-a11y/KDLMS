<?php
// ================================================================
// includes/auth.php — Authentication & RBAC Helpers
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

// ─── UTF-8 / Khmer session encoding fix ─────────────────────────
// Must run BEFORE session_start() so PHP session file uses UTF-8.
// This prevents Khmer name/text from becoming ??? in browser sessions.
ini_set('default_charset', 'UTF-8');
ini_set('session.serialize_handler', 'php_serialize'); // standard PHP serialize, UTF-8 safe
mb_internal_encoding('UTF-8');
mb_language('uni');  // Unicode mode for mb_ functions
// ────────────────────────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false, // set true on HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';

// ================================================================
// SESSION HELPERS — ជំនួយ Session
// ================================================================

/**
 * isLoggedIn() — Check if a user is currently logged in.
 * ពិនិត្យមើលថាតើអ្នកប្រើប្រាស់បានចូលប្រើឬយ៉ាងណា។
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * currentUser() — Get user data, with name always fresh from DB (UTF-8 safe).
 * ទទួលទិន្នន័យអ្នកប្រើ — ឈ្មោះខ្មែរត្រូវបានទាញពី DB ដើម្បីជៀសវាងការខូច encoding ។
 */
function currentUser(): array {
    if (!isLoggedIn()) return [];

    // Always re-fetch name from DB so Khmer characters are never
    // corrupted by PHP session serialization on Windows XAMPP.
    static $cachedUser = null;
    if ($cachedUser !== null) return $cachedUser;

    $id = $_SESSION['user_id'];
    try {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('SELECT id, name, role FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row  = $stmt->fetch();
        if ($row) {
            $cachedUser = [
                'id'   => (int)$row['id'],
                'name' => $row['name'],   // UTF-8 from utf8mb4 PDO connection
                'role' => $row['role'],
            ];
            return $cachedUser;
        }
    } catch (Exception $e) {
        // fallback to session data if DB query fails
    }

    return [
        'id'   => $id,
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'user',
    ];
}


/**
 * requireLogin() — Redirect to login if not authenticated.
 * តម្រូវការ Login — redirect ប្រសិនបើមិនបាន Login ។
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . '/login.php?msg=login_required');
        exit;
    }
}

/**
 * requireAdmin() — Redirect if not admin or superadmin.
 * តម្រូវការ Admin — redirect ប្រសិនបើមិន មែន Admin ។
 */
function requireAdmin(): void {
    requireLogin();
    $role = $_SESSION['user_role'] ?? 'user';
    if (!in_array($role, ['admin', 'superadmin'])) {
        header('Location: ' . BASE_URL . '/browse.php?msg=access_denied');
        exit;
    }
}

/**
 * requireSuperadmin() — Redirect if not superadmin.
 * តម្រូវការ Superadmin ប៉ុណ្ណោះ។
 */
function requireSuperadmin(): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'superadmin') {
        header('Location: ' . BASE_URL . '/admin/index.php?msg=access_denied');
        exit;
    }
}

// ================================================================
// CSRF PROTECTION — ការការពារ CSRF
// ================================================================

/**
 * generateCsrfToken() — Generate or return existing CSRF token.
 * បង្កើត CSRF Token ដើម្បីការពារ Form Submission ។
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * verifyCsrfToken() — Validate CSRF token from POST data.
 * ពិនិត្យ CSRF Token ពី POST Form ។
 */
function verifyCsrfToken(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('<h2 style="color:#8B0000;font-family:sans-serif;text-align:center;padding:40px;">
            ❌ CSRF Token Invalid — Invalid form submission.<br>
            <a href="javascript:history.back()">Go Back</a>
        </h2>');
    }
    // Rotate token after use
    unset($_SESSION['csrf_token']);
}

/**
 * csrfField() — Return hidden HTML input with CSRF token.
 * Output: <input type="hidden" name="csrf_token" value="...">
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

// ================================================================
// RATE LIMITING — ការកំណត់ចំនួន Login
// ================================================================

/**
 * checkLoginRateLimit() — Block login after 5 failed attempts for 10 minutes.
 * ប្លុក Login ក្រោយ 5 ដងខុស ក្នុងរយៈពេល 10 នាទី។
 *
 * @return bool — true if allowed, false if blocked
 */
function checkLoginRateLimit(string $email): bool {
    $key       = 'login_attempts_' . md5($email);
    $timeKey   = 'login_lockout_' . md5($email);
    $maxTries  = 5;
    $lockoutSec = 600; // 10 minutes

    // If currently locked out
    if (isset($_SESSION[$timeKey]) && time() < $_SESSION[$timeKey]) {
        return false;
    }

    // Reset if lockout expired
    if (isset($_SESSION[$timeKey]) && time() >= $_SESSION[$timeKey]) {
        unset($_SESSION[$key], $_SESSION[$timeKey]);
    }

    return true;
}

function recordFailedLogin(string $email): int {
    $key     = 'login_attempts_' . md5($email);
    $timeKey = 'login_lockout_' . md5($email);

    $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;

    if ($_SESSION[$key] >= 5) {
        $_SESSION[$timeKey] = time() + 600; // lock 10 min
    }

    return $_SESSION[$key];
}

function resetLoginAttempts(string $email): void {
    $key     = 'login_attempts_' . md5($email);
    $timeKey = 'login_lockout_' . md5($email);
    unset($_SESSION[$key], $_SESSION[$timeKey]);
}

function getRemainingLockoutSeconds(string $email): int {
    $timeKey = 'login_lockout_' . md5($email);
    if (isset($_SESSION[$timeKey])) {
        return max(0, $_SESSION[$timeKey] - time());
    }
    return 0;
}
