<?php
// ================================================================
// admin/users.php — Manage Users
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pdo    = getPDO();
$me     = currentUser();

// --- Handle actions ---
$action = $_GET['action'] ?? '';
$uid    = (int)($_GET['id'] ?? 0);

if ($uid > 0 && $uid !== (int)$me['id']) {
    if ($action === 'ban') {
        $pdo->prepare("UPDATE users SET status='banned' WHERE id=?")->execute([$uid]);
        setFlash('warning', 'User has been banned.');
    } elseif ($action === 'unban') {
        $pdo->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$uid]);
        setFlash('success', 'User has been unbanned.');
    } elseif ($action === 'make_admin' && $me['role'] === 'superadmin') {
        $pdo->prepare("UPDATE users SET role='admin' WHERE id=? AND role='user'")->execute([$uid]);
        setFlash('success', 'User promoted to Admin.');
    } elseif ($action === 'make_user' && $me['role'] === 'superadmin') {
        $pdo->prepare("UPDATE users SET role='user' WHERE id=? AND role='admin'")->execute([$uid]);
        setFlash('success', 'Admin demoted to User.');
    }
    redirect(BASE_URL . '/admin/users.php');
}

// --- Fetch users ---
$search = trim($_GET['search'] ?? '');
$roleF  = $_GET['role'] ?? '';
$where  = [];
$params = [];

if (!empty($search)) {
    $where[]  = '(name LIKE ? OR email LIKE ?)';
    $params[] = '%'.$search.'%';
    $params[] = '%'.$search.'%';
}
if (in_array($roleF, ['user','admin','superadmin'])) {
    $where[]  = 'role = ?';
    $params[] = $roleF;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare(
    "SELECT u.*, (SELECT COUNT(*) FROM download_logs dl WHERE dl.user_id=u.id) AS dl_count
     FROM users u $whereSQL ORDER BY u.created_at DESC"
);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Fetch user download history for modal (AJAX)
if (isset($_GET['history_user'])) {
    $huid  = (int)$_GET['history_user'];
    $hstmt = $pdo->prepare(
        "SELECT dl.downloaded_at, b.title_kh, b.file_extension, c.name_kh AS cat_name
         FROM download_logs dl
         JOIN books b ON dl.book_id=b.id
         LEFT JOIN categories c ON b.category_id=c.id
         WHERE dl.user_id=?
         ORDER BY dl.downloaded_at DESC LIMIT 20"
    );
    $hstmt->execute([$huid]);
    header('Content-Type: application/json');
    echo json_encode($hstmt->fetchAll(), JSON_UNESCAPED_UNICODE);
    exit;
}

$pageTitle = 'គ្រប់គ្រងអ្នកប្រើ · Manage Users';
$activeNav = 'users';
include '../includes/admin_header.php';
?>

<!-- Toolbar -->
<div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
    <div class="admin-search-bar" style="flex:1;">
        <form method="GET" action="users.php" style="display:flex;gap:8px;flex:1;">
            <div class="position-relative" style="flex:1;">
                <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:.85rem;"></i>
                <input type="text" name="search" class="admin-search-input" style="padding-left:32px;"
                       placeholder="Search by name or email..." value="<?= e($search) ?>">
            </div>
            <select name="role" class="admin-filter-select" onchange="this.form.submit()">
                <option value="">All Roles</option>
                <option value="user"       <?= $roleF==='user'       ?'selected':''?>>Users</option>
                <option value="admin"      <?= $roleF==='admin'      ?'selected':''?>>Admins</option>
                <option value="superadmin" <?= $roleF==='superadmin' ?'selected':''?>>Superadmins</option>
            </select>
            <button type="submit" class="btn-admin-primary" style="padding:8px 16px;"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <button class="btn-admin-secondary" onclick="exportTableToCSV('usersTable','kdlms-users.csv')">
        <i class="bi bi-download"></i> Export CSV
    </button>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title"><i class="bi bi-people"></i> Users (<?= count($users) ?>)</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="kdlms-table" id="usersTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Downloads</th>
                    <th>Joined</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u):
                    $isMe = ((int)$u['id'] === (int)$me['id']);
                ?>
                <tr>
                    <td style="color:#94A3B8;font-size:.75rem;font-family:'Inter',sans-serif;"><?= $u['id'] ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#8B0000,#C8960C);color:white;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;flex-shrink:0;">
                                <?= mb_strtoupper(mb_substr($u['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:.82rem;font-family:'Kantumruy Pro',sans-serif;"><?= e($u['name']) ?></div>
                                <?php if ($isMe): ?><span style="font-size:.65rem;background:#DBEAFE;color:#1E40AF;border-radius:4px;padding:1px 6px;">You</span><?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td style="font-family:'Inter',sans-serif;font-size:.78rem;"><?= e($u['email']) ?></td>
                    <td>
                        <span class="status-badge <?= $u['role']==='superadmin'?'status-super':($u['role']==='admin'?'status-admin':'') ?>">
                            <i class="bi <?= $u['role']==='superadmin'?'bi-star-fill':($u['role']==='admin'?'bi-shield-check':'bi-person') ?>"></i>
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-<?= $u['status'] ?>">
                            <i class="bi <?= $u['status']==='active'?'bi-check-circle':'bi-x-circle' ?>"></i>
                            <?= ucfirst($u['status']) ?>
                        </span>
                    </td>
                    <td style="font-family:'Inter',sans-serif;font-weight:700;color:#1A3A5C;"><?= number_format($u['dl_count']) ?></td>
                    <td style="font-size:.75rem;font-family:'Inter',sans-serif;color:#64748B;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td style="font-size:.75rem;font-family:'Inter',sans-serif;color:#64748B;"><?= $u['last_login'] ? timeAgo($u['last_login']) : '—' ?></td>
                    <td>
                        <?php if (!$isMe): ?>
                        <div style="display:flex;gap:4px;flex-wrap:wrap;">
                            <!-- Download history -->
                            <button onclick="viewUserHistory(<?= $u['id'] ?>, '<?= addslashes(e($u['name'])) ?>')" class="action-btn view" title="View History"><i class="bi bi-clock-history"></i></button>
                            <!-- Ban/Unban -->
                            <?php if ($u['status'] === 'active'): ?>
                            <a href="users.php?action=ban&id=<?= $u['id'] ?>" class="action-btn delete" title="Ban User" onclick="return confirm('Ban this user?')"><i class="bi bi-slash-circle"></i></a>
                            <?php else: ?>
                            <a href="users.php?action=unban&id=<?= $u['id'] ?>" class="action-btn edit" title="Unban User"><i class="bi bi-check-circle"></i></a>
                            <?php endif; ?>
                            <!-- Role change (superadmin only) -->
                            <?php if ($me['role'] === 'superadmin' && $u['role'] === 'user'): ?>
                            <a href="users.php?action=make_admin&id=<?= $u['id'] ?>" class="action-btn hide" title="Promote to Admin" onclick="return confirm('Promote to Admin?')"><i class="bi bi-arrow-up"></i></a>
                            <?php elseif ($me['role'] === 'superadmin' && $u['role'] === 'admin'): ?>
                            <a href="users.php?action=make_user&id=<?= $u['id'] ?>" class="action-btn delete" title="Demote to User" onclick="return confirm('Demote to User?')"><i class="bi bi-arrow-down"></i></a>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <span style="font-size:.7rem;color:#94A3B8;">Current User</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="9" style="text-align:center;padding:3rem;color:#94A3B8;">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- User History Modal -->
<div class="admin-modal-overlay" id="historyModal">
    <div class="admin-modal" style="max-width:500px;">
        <div class="admin-modal-header">
            <div class="admin-modal-title"><i class="bi bi-clock-history"></i> Download History: <span id="historyUserName"></span></div>
            <button onclick="closeModal('historyModal')" style="background:none;border:none;cursor:pointer;font-size:1.2rem;color:#94A3B8;">×</button>
        </div>
        <div class="admin-modal-body" style="padding:0;max-height:400px;overflow-y:auto;">
            <table class="kdlms-table" id="historyTable">
                <thead><tr><th>Book</th><th>Category</th><th>Time</th></tr></thead>
                <tbody id="historyTableBody">
                    <tr><td colspan="3" style="text-align:center;padding:2rem;color:#94A3B8;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="admin-modal-footer">
            <button class="btn-admin-secondary" onclick="closeModal('historyModal')">Close</button>
        </div>
    </div>
</div>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
function viewUserHistory(uid, name) {
    document.getElementById('historyUserName').textContent = name;
    document.getElementById('historyTableBody').innerHTML = '<tr><td colspan="3" style="text-align:center;padding:2rem;color:#94A3B8;">Loading...</td></tr>';
    openModal('historyModal');
    fetch(`users.php?history_user=${uid}`)
        .then(r => r.json())
        .then(data => {
            if (!data.length) {
                document.getElementById('historyTableBody').innerHTML = '<tr><td colspan="3" style="text-align:center;padding:2rem;color:#94A3B8;">No downloads yet.</td></tr>';
                return;
            }
            document.getElementById('historyTableBody').innerHTML = data.map(d => `
                <tr>
                    <td style="font-family:'Kantumruy Pro',sans-serif;font-size:.78rem;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${d.title_kh}</td>
                    <td style="font-size:.75rem;color:#64748B;">${d.cat_name||'—'}</td>
                    <td style="font-size:.72rem;color:#94A3B8;white-space:nowrap;">${d.downloaded_at}</td>
                </tr>
            `).join('');
        })
        .catch(() => {
            document.getElementById('historyTableBody').innerHTML = '<tr><td colspan="3" style="text-align:center;color:#DC2626;">Error loading data.</td></tr>';
        });
}
</script>
</body>
</html>
