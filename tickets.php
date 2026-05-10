<?php
require_once '../includes/config.php';
requireLogin();
requireAdmin();

$pageTitle = 'All Tickets';
$db = getDB();

$status      = sanitize($_GET['status'] ?? '');
$category    = sanitize($_GET['category'] ?? '');
$priority    = sanitize($_GET['priority'] ?? '');
$search      = sanitize($_GET['search'] ?? '');
$active_tab  = sanitize($_GET['tab'] ?? 'all');

// Build WHERE for filters
$filter = "WHERE 1=1";
if ($status)   $filter .= " AND t.status = '"   . $db->real_escape_string($status)   . "'";
if ($category) $filter .= " AND t.category = '" . $db->real_escape_string($category) . "'";
if ($priority) $filter .= " AND t.priority = '" . $db->real_escape_string($priority) . "'";
if ($search)   $filter .= " AND (t.subject LIKE '%".$db->real_escape_string($search)."%'
                             OR t.ticket_number LIKE '%".$db->real_escape_string($search)."%'
                             OR u.full_name LIKE '%".$db->real_escape_string($search)."%')";

// Fetch tickets grouped by role
function fetchByRole($db, $role, $filter) {
    $roleFilter = $filter . " AND u.role = '" . $db->real_escape_string($role) . "'";
    $result = $db->query("SELECT t.*, u.full_name, u.email, u.college_id, u.role
                          FROM tickets t JOIN users u ON t.user_id = u.id
                          $roleFilter ORDER BY t.created_at DESC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$all_rows      = $db->query("SELECT t.*, u.full_name, u.email, u.college_id, u.role
                              FROM tickets t JOIN users u ON t.user_id = u.id
                              $filter ORDER BY t.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$student_rows  = fetchByRole($db, 'student', $filter);
$faculty_rows  = fetchByRole($db, 'faculty', $filter);
$staff_rows    = fetchByRole($db, 'staff',   $filter);

// Stats
$total    = $db->query("SELECT COUNT(*) as c FROM tickets")->fetch_assoc()['c'];
$pending  = $db->query("SELECT COUNT(*) as c FROM tickets WHERE status='Pending'")->fetch_assoc()['c'];
$inprog   = $db->query("SELECT COUNT(*) as c FROM tickets WHERE status='In Progress'")->fetch_assoc()['c'];
$resolved = $db->query("SELECT COUNT(*) as c FROM tickets WHERE status='Resolved'")->fetch_assoc()['c'];
$critical = $db->query("SELECT COUNT(*) as c FROM tickets WHERE priority='Critical' AND status NOT IN ('Resolved','Closed')")->fetch_assoc()['c'];

// Count per role
$cnt_student = $db->query("SELECT COUNT(*) as c FROM tickets t JOIN users u ON t.user_id=u.id WHERE u.role='student'")->fetch_assoc()['c'];
$cnt_faculty = $db->query("SELECT COUNT(*) as c FROM tickets t JOIN users u ON t.user_id=u.id WHERE u.role='faculty'")->fetch_assoc()['c'];
$cnt_staff   = $db->query("SELECT COUNT(*) as c FROM tickets t JOIN users u ON t.user_id=u.id WHERE u.role='staff'")->fetch_assoc()['c'];

include '../includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1 class="page-title">🛠️ All Support Tickets</h1>
        <p class="page-subtitle">Manage and respond to all submitted support requests</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card blue"><div class="stat-icon">🎫</div><div class="stat-value"><?= $total ?></div><div class="stat-label">Total Tickets</div></div>
        <div class="stat-card orange"><div class="stat-icon">⏳</div><div class="stat-value"><?= $pending ?></div><div class="stat-label">Pending</div></div>
        <div class="stat-card red"><div class="stat-icon">🔄</div><div class="stat-value"><?= $inprog ?></div><div class="stat-label">In Progress</div></div>
        <div class="stat-card green"><div class="stat-icon">✅</div><div class="stat-value"><?= $resolved ?></div><div class="stat-label">Resolved</div></div>
        <?php if ($critical > 0): ?>
        <div class="stat-card red"><div class="stat-icon">🚨</div><div class="stat-value"><?= $critical ?></div><div class="stat-label">Critical Open</div></div>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <form method="GET" class="filters-bar">
        <input type="hidden" name="tab" value="<?= $active_tab ?>">
        <input type="text" name="search" class="form-control search-input" placeholder="🔍 Search ticket #, subject, user..." value="<?= $search ?>">
        <select name="status" class="form-control">
            <option value="">All Statuses</option>
            <option value="Pending"     <?= $status==='Pending'     ?'selected':'' ?>>Pending</option>
            <option value="In Progress" <?= $status==='In Progress' ?'selected':'' ?>>In Progress</option>
            <option value="Resolved"    <?= $status==='Resolved'    ?'selected':'' ?>>Resolved</option>
            <option value="Closed"      <?= $status==='Closed'      ?'selected':'' ?>>Closed</option>
        </select>
        <select name="category" class="form-control">
            <option value="">All Categories</option>
            <option value="Network"  <?= $category==='Network'  ?'selected':'' ?>>Network</option>
            <option value="Account"  <?= $category==='Account'  ?'selected':'' ?>>Account</option>
            <option value="Software" <?= $category==='Software' ?'selected':'' ?>>Software</option>
            <option value="Hardware" <?= $category==='Hardware' ?'selected':'' ?>>Hardware</option>
            <option value="Other"    <?= $category==='Other'    ?'selected':'' ?>>Other</option>
        </select>
        <select name="priority" class="form-control">
            <option value="">All Priorities</option>
            <option value="Low"      <?= $priority==='Low'      ?'selected':'' ?>>Low</option>
            <option value="Medium"   <?= $priority==='Medium'   ?'selected':'' ?>>Medium</option>
            <option value="High"     <?= $priority==='High'     ?'selected':'' ?>>High</option>
            <option value="Critical" <?= $priority==='Critical' ?'selected':'' ?>>Critical</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <?php if ($status || $category || $search || $priority): ?>
        <a href="tickets.php?tab=<?= $active_tab ?>" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
    </form>

    <!-- ROLE TABS -->
    <div style="display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:24px;">
        <?php
        $tabs = [
            'all'     => ['label' => '📋 All Tickets',      'count' => count($all_rows),     'color' => '#0f4c81'],
            'student' => ['label' => '🎓 Student Tickets',  'count' => count($student_rows),  'color' => '#6366f1'],
            'faculty' => ['label' => '📚 Faculty Tickets',  'count' => count($faculty_rows),  'color' => '#0891b2'],
            'staff'   => ['label' => '👤 Staff Tickets',    'count' => count($staff_rows),    'color' => '#059669'],
        ];
        foreach ($tabs as $key => $tab):
            $isActive = $active_tab === $key;
        ?>
        <a href="?tab=<?= $key ?><?= $status ? '&status='.$status : '' ?><?= $category ? '&category='.$category : '' ?><?= $search ? '&search='.$search : '' ?>"
           style="padding:12px 20px;font-weight:600;font-size:.88rem;text-decoration:none;
                  color:<?= $isActive ? $tab['color'] : 'var(--text-muted)' ?>;
                  border-bottom:<?= $isActive ? '3px solid '.$tab['color'] : '3px solid transparent' ?>;
                  margin-bottom:-2px;display:flex;align-items:center;gap:8px;transition:.2s;">
            <?= $tab['label'] ?>
            <span style="background:<?= $isActive ? $tab['color'] : '#e2e8f0' ?>;
                         color:<?= $isActive ? '#fff' : 'var(--text-muted)' ?>;
                         border-radius:20px;padding:2px 8px;font-size:.75rem;">
                <?= $tab['count'] ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- TICKET TABLE -->
    <?php
    $show_rows = match($active_tab) {
        'student' => $student_rows,
        'faculty' => $faculty_rows,
        'staff'   => $staff_rows,
        default   => $all_rows,
    };

    $role_badge = [
        'student' => ['bg' => '#ede9fe', 'color' => '#5b21b6', 'label' => '🎓 Student'],
        'faculty' => ['bg' => '#e0f2fe', 'color' => '#0369a1', 'label' => '📚 Faculty'],
        'staff'   => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => '👤 Staff'],
        'admin'   => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => '🔑 Admin'],
    ];
    ?>

    <div class="card">
        <div class="table-wrap">
            <?php if (empty($show_rows)): ?>
                <div class="empty-state">
                    <div class="icon">🔍</div>
                    <h3>No tickets found</h3>
                    <p>No <?= $active_tab !== 'all' ? $active_tab : '' ?> tickets match your filters.</p>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Subject</th>
                        <th>User</th>
                        <?php if ($active_tab === 'all'): ?><th>Role</th><?php endif; ?>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($show_rows as $t): ?>
                    <tr <?= $t['priority']==='Critical' && !in_array($t['status'],['Resolved','Closed']) ? 'style="background:#fff5f5;"' : '' ?>>
                        <td><code style="font-size:.78rem;"><?= $t['ticket_number'] ?></code></td>
                        <td><?= sanitize($t['subject']) ?></td>
                        <td>
                            <div style="font-size:.88rem;font-weight:600;"><?= sanitize($t['full_name']) ?></div>
                            <div style="font-size:.75rem;color:var(--text-muted);"><?= $t['email'] ?></div>
                            <div style="font-size:.75rem;color:var(--text-muted);"><?= $t['college_id'] ?></div>
                        </td>
                        <?php if ($active_tab === 'all'): ?>
                        <td>
                            <?php $rb = $role_badge[$t['role']] ?? $role_badge['student']; ?>
                            <span style="background:<?= $rb['bg'] ?>;color:<?= $rb['color'] ?>;
                                         padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600;">
                                <?= $rb['label'] ?>
                            </span>
                        </td>
                        <?php endif; ?>
                        <td><?= $t['category'] ?></td>
                        <td><?= getPriorityBadge($t['priority']) ?></td>
                        <td><?= getStatusBadge($t['status']) ?></td>
                        <td style="white-space:nowrap;font-size:.85rem;"><?= timeAgo($t['created_at']) ?></td>
                        <td><a href="<?= APP_URL ?>/ticket.php?id=<?= $t['id'] ?>" class="btn btn-primary btn-sm">Manage →</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
