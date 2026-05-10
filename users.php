<?php
require_once '../includes/config.php';
requireLogin();
requireAdmin();

$pageTitle = 'Manage Users';
$db = getDB();

$search = sanitize($_GET['search'] ?? '');
$role_filter = sanitize($_GET['role'] ?? '');

$where = "WHERE id != " . $_SESSION['user_id'];
if ($search) $where .= " AND (full_name LIKE '%" . $db->real_escape_string($search) . "%' OR email LIKE '%" . $db->real_escape_string($search) . "%' OR college_id LIKE '%" . $db->real_escape_string($search) . "%')";
if ($role_filter) $where .= " AND role = '" . $db->real_escape_string($role_filter) . "'";

$users = $db->query("SELECT u.*, (SELECT COUNT(*) FROM tickets WHERE user_id=u.id) as ticket_count FROM users u $where ORDER BY created_at DESC");

include '../includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1 class="page-title">👥 User Management</h1>
        <p class="page-subtitle">View and manage all registered users</p>
    </div>

    <form method="GET" class="filters-bar">
        <input type="text" name="search" class="form-control search-input" placeholder="🔍 Search name, email, college ID..." value="<?= $search ?>">
        <select name="role" class="form-control">
            <option value="">All Roles</option>
            <option value="student" <?= $role_filter==='student'?'selected':'' ?>>Student</option>
            <option value="faculty" <?= $role_filter==='faculty'?'selected':'' ?>>Faculty</option>
            <option value="staff" <?= $role_filter==='staff'?'selected':'' ?>>Staff</option>
            <option value="admin" <?= $role_filter==='admin'?'selected':'' ?>>Admin</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        <?php if ($search || $role_filter): ?>
        <a href="users.php" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
    </form>

    <div class="card">
        <div class="table-wrap">
            <?php $rows = $users->fetch_all(MYSQLI_ASSOC); ?>
            <?php if (empty($rows)): ?>
                <div class="empty-state"><div class="icon">👥</div><h3>No users found</h3></div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>College ID</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Tickets</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $u): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:34px;height:34px;background:var(--primary);border-radius:50%;display:grid;place-items:center;color:white;font-weight:700;font-size:.85rem;flex-shrink:0;"><?= strtoupper(substr($u['full_name'],0,1)) ?></div>
                                <span><?= sanitize($u['full_name']) ?></span>
                            </div>
                        </td>
                        <td><code><?= $u['college_id'] ?></code></td>
                        <td style="font-size:.88rem;"><?= sanitize($u['email']) ?></td>
                        <td>
                            <span class="badge <?= $u['role']==='admin' ? 'badge-progress' : 'badge-resolved' ?>" style="text-transform:capitalize;">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                        <td style="text-align:center;"><?= $u['ticket_count'] ?></td>
                        <td style="font-size:.85rem;"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <a href="<?= APP_URL ?>/admin/tickets.php?user=<?= $u['id'] ?>" class="btn btn-outline btn-sm">Tickets</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
