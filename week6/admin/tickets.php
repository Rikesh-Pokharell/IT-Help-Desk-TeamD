<?php
/**
 * Admin Ticket Overview & Filtering
 * Developer: Rikesh | Week 6
 * Tasks:
 *   1. Ticket Overview & Filtering (search/filter by status, category, user)
 *   2. All admin-side buttons functional and clickable
 *   3. Database code decoded with inline comments
 */

require_once '../includes/config.php';
requireLogin();
requireAdmin();

$pageTitle = 'Ticket Overview';
$db = getDB();

// ════════════════════════════════════════════════════════════════════
// TASK 2 — Handle all button POST actions
// ════════════════════════════════════════════════════════════════════

// [BUTTON] Inline status update (Save button on each row)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $tid        = intval($_POST['ticket_id'] ?? 0);
    $new_status = sanitize($_POST['new_status'] ?? '');
    $valid      = ['Pending', 'In Progress', 'Resolved', 'Closed'];
    if ($tid > 0 && in_array($new_status, $valid)) {
        $stmt = $db->prepare("UPDATE tickets SET status=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("si", $new_status, $tid);
        $stmt->execute();
    }
    $qs = http_build_query(array_filter([
        'status'   => $_POST['f_status']   ?? '',
        'category' => $_POST['f_category'] ?? '',
        'priority' => $_POST['f_priority'] ?? '',
        'user'     => $_POST['f_user']     ?? '',
        'search'   => $_POST['f_search']   ?? '',
    ]));
    header("Location: tickets.php?updated=$tid&$qs");
    exit();
}

// [BUTTON] Close ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_ticket'])) {
    $tid = intval($_POST['ticket_id'] ?? 0);
    if ($tid > 0) {
        $stmt = $db->prepare("UPDATE tickets SET status='Closed', updated_at=NOW() WHERE id=?");
        $stmt->bind_param("i", $tid);
        $stmt->execute();
    }
    header("Location: tickets.php?closed=$tid");
    exit();
}

// [BUTTON] Delete ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ticket'])) {
    $tid = intval($_POST['ticket_id'] ?? 0);
    if ($tid > 0) {
        $stmt = $db->prepare("DELETE FROM tickets WHERE id=?");
        $stmt->bind_param("i", $tid);
        $stmt->execute();
    }
    header("Location: tickets.php?deleted=1");
    exit();
}

// ════════════════════════════════════════════════════════════════════
// TASK 1 — Collect filter inputs
// ════════════════════════════════════════════════════════════════════
$status      = sanitize($_GET['status']    ?? '');
$category    = sanitize($_GET['category']  ?? '');
$priority    = sanitize($_GET['priority']  ?? '');
$search      = sanitize($_GET['search']    ?? '');
$user_filter = sanitize($_GET['user']      ?? '');
$date_from   = sanitize($_GET['date_from'] ?? '');
$date_to     = sanitize($_GET['date_to']   ?? '');

// ════════════════════════════════════════════════════════════════════
// TASK 1 + TASK 3 — Build WHERE clause with DB decode comments
// ════════════════════════════════════════════════════════════════════

// "WHERE 1=1" is a harmless always-true anchor so we can safely
// append AND clauses without worrying about the first one.
$where = "WHERE 1=1";

if ($status) {
    // DB DECODE: status is an ENUM column. We match exact value only.
    $where .= " AND t.status = '" . $db->real_escape_string($status) . "'";
}
if ($category) {
    // DB DECODE: category ENUM = Network | Account | Software | Hardware | Other
    $where .= " AND t.category = '" . $db->real_escape_string($category) . "'";
}
if ($priority) {
    // DB DECODE: priority ENUM = Low | Medium | High | Critical
    $where .= " AND t.priority = '" . $db->real_escape_string($priority) . "'";
}
if ($search) {
    // DB DECODE: LIKE '%value%' does a partial substring match.
    // Searches across ticket number, subject, user full name, and college ID.
    $esc = $db->real_escape_string($search);
    $where .= " AND (t.subject LIKE '%$esc%'
                  OR t.ticket_number LIKE '%$esc%'
                  OR u.full_name LIKE '%$esc%'
                  OR u.college_id LIKE '%$esc%')";
}
if ($user_filter && is_numeric($user_filter)) {
    // DB DECODE: Exact match on the foreign key user_id (integer).
    // Isolates all tickets submitted by one specific user.
    $where .= " AND t.user_id = " . intval($user_filter);
}
if ($date_from) {
    // DB DECODE: DATE() strips the time part of the TIMESTAMP so we compare dates only.
    $where .= " AND DATE(t.created_at) >= '" . $db->real_escape_string($date_from) . "'";
}
if ($date_to) {
    $where .= " AND DATE(t.created_at) <= '" . $db->real_escape_string($date_to) . "'";
}

// DB DECODE: Main SELECT
// t.*           = all columns from tickets table
// u.full_name   = the name of the user who submitted the ticket
// u.college_id  = their college ID
// JOIN tickets t ON users u via t.user_id = u.id (foreign key relationship)
// ORDER BY created_at DESC = newest tickets first
$tickets = $db->query(
    "SELECT t.*, u.full_name, u.email, u.college_id
     FROM tickets t
     JOIN users u ON t.user_id = u.id
     $where
     ORDER BY t.created_at DESC"
);

// DB DECODE: COUNT(*) returns total rows matching each condition — used for stat cards.
$total    = $db->query("SELECT COUNT(*) AS c FROM tickets")->fetch_assoc()['c'];
$pending  = $db->query("SELECT COUNT(*) AS c FROM tickets WHERE status='Pending'")->fetch_assoc()['c'];
$inprog   = $db->query("SELECT COUNT(*) AS c FROM tickets WHERE status='In Progress'")->fetch_assoc()['c'];
$resolved = $db->query("SELECT COUNT(*) AS c FROM tickets WHERE status='Resolved'")->fetch_assoc()['c'];
$closed   = $db->query("SELECT COUNT(*) AS c FROM tickets WHERE status='Closed'")->fetch_assoc()['c'];
$critical = $db->query("SELECT COUNT(*) AS c FROM tickets WHERE priority='Critical' AND status NOT IN ('Resolved','Closed')")->fetch_assoc()['c'];

// DB DECODE: Fetch user list for the Filter-by-User dropdown.
// Only non-admin users are included since admins don't submit tickets.
$all_users = $db->query("SELECT id, full_name FROM users WHERE role != 'admin' ORDER BY full_name ASC");

include '../includes/header.php';
?>

<div class="container">

    <div class="page-header">
        <h1 class="page-title">🗂️ Ticket Overview</h1>
        <p class="page-subtitle">Search, filter, and manage all support tickets</p>
    </div>

    <?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success">✅ Ticket status updated successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">🗑️ Ticket deleted successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['closed'])): ?>
    <div class="alert alert-success">🔒 Ticket has been closed.</div>
    <?php endif; ?>

    <!-- ── Stats Cards (TASK 2: each card is a clickable filter link) ── -->
    <div class="stats-grid">
        <a href="tickets.php" style="text-decoration:none;">
            <div class="stat-card blue">
                <div class="stat-icon">🎫</div>
                <div class="stat-value"><?= $total ?></div>
                <div class="stat-label">Total Tickets</div>
            </div>
        </a>
        <a href="tickets.php?status=Pending" style="text-decoration:none;">
            <div class="stat-card orange">
                <div class="stat-icon">⏳</div>
                <div class="stat-value"><?= $pending ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </a>
        <a href="tickets.php?status=In+Progress" style="text-decoration:none;">
            <div class="stat-card red">
                <div class="stat-icon">🔄</div>
                <div class="stat-value"><?= $inprog ?></div>
                <div class="stat-label">In Progress</div>
            </div>
        </a>
        <a href="tickets.php?status=Resolved" style="text-decoration:none;">
            <div class="stat-card green">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?= $resolved ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </a>
        <a href="tickets.php?status=Closed" style="text-decoration:none;">
            <div class="stat-card" style="background:var(--surface);border:1px solid var(--border);">
                <div class="stat-icon">🔒</div>
                <div class="stat-value"><?= $closed ?></div>
                <div class="stat-label">Closed</div>
            </div>
        </a>
        <?php if ($critical > 0): ?>
        <a href="tickets.php?priority=Critical" style="text-decoration:none;">
            <div class="stat-card red">
                <div class="stat-icon">🚨</div>
                <div class="stat-value"><?= $critical ?></div>
                <div class="stat-label">Critical Open</div>
            </div>
        </a>
        <?php endif; ?>
    </div>

    <!-- ── TASK 1: Quick Status Tabs ──────────────────────────────────── -->
    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
        <span style="font-size:.82rem;color:var(--text-muted);font-weight:600;letter-spacing:.04em;">FILTER BY STATUS:</span>
        <?php
        $tabs = [
            ''            => ['All Tickets', '📋'],
            'Pending'     => ['Pending',     '⏳'],
            'In Progress' => ['In Progress', '🔄'],
            'Resolved'    => ['Resolved',    '✅'],
            'Closed'      => ['Closed',      '🔒'],
        ];
        foreach ($tabs as $val => $meta):
            $active = ($status === $val);
            $qs = http_build_query(array_filter(['status'=>$val,'category'=>$category,'priority'=>$priority,'search'=>$search,'user'=>$user_filter]));
        ?>
        <a href="tickets.php?<?= $qs ?>"
           class="btn <?= $active ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            <?= $meta[1] ?> <?= $meta[0] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ── TASK 1: Advanced Filter Bar ───────────────────────────────── -->
    <form method="GET" class="filters-bar" style="flex-wrap:wrap;gap:8px;margin-bottom:20px;">
        <input type="text" name="search" class="form-control search-input"
               placeholder="🔍 Ticket #, subject, name, college ID…"
               value="<?= $search ?>" style="min-width:200px;">

        <select name="status" class="form-control">
            <option value="">All Statuses</option>
            <?php foreach (['Pending','In Progress','Resolved','Closed'] as $s): ?>
            <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>

        <select name="category" class="form-control">
            <option value="">All Categories</option>
            <?php foreach (['Network','Account','Software','Hardware','Other'] as $c): ?>
            <option value="<?= $c ?>" <?= $category===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
        </select>

        <select name="priority" class="form-control">
            <option value="">All Priorities</option>
            <?php foreach (['Low','Medium','High','Critical'] as $p): ?>
            <option value="<?= $p ?>" <?= $priority===$p?'selected':'' ?>><?= $p ?></option>
            <?php endforeach; ?>
        </select>

        <!-- TASK 1: Filter by User dropdown -->
        <select name="user" class="form-control">
            <option value="">All Users</option>
            <?php
            $user_rows = $all_users->fetch_all(MYSQLI_ASSOC);
            foreach ($user_rows as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $user_filter==(string)$u['id']?'selected':'' ?>>
                <?= sanitize($u['full_name']) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <!-- Date range -->
        <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>"
               title="From" style="max-width:145px;">
        <input type="date" name="date_to"   class="form-control" value="<?= $date_to ?>"
               title="To"   style="max-width:145px;">

        <!-- TASK 2: Filter and Clear buttons — fully wired up -->
        <button type="submit" class="btn btn-primary btn-sm">🔍 Filter</button>
        <?php if ($status||$category||$priority||$search||$user_filter||$date_from||$date_to): ?>
        <a href="tickets.php" class="btn btn-outline btn-sm">✖ Clear All</a>
        <?php endif; ?>
    </form>

    <!-- ── Ticket Table ──────────────────────────────────────────────── -->
    <div class="card">
        <?php $rows = $tickets->fetch_all(MYSQLI_ASSOC); ?>
        <div class="card-header" style="justify-content:space-between;">
            <span class="card-title">
                Tickets
                <span style="font-size:.82rem;font-weight:400;color:var(--text-muted);margin-left:6px;">
                    (<?= count($rows) ?> result<?= count($rows)!=1?'s':'' ?>)
                </span>
            </span>
            <span style="font-size:.8rem;color:var(--text-muted);">
                <?= count($rows) ?> shown &nbsp;/&nbsp; <?= $total ?> total
            </span>
        </div>
        <div class="table-wrap">
            <?php if (empty($rows)): ?>
                <div class="empty-state">
                    <div class="icon">🔍</div>
                    <h3>No tickets found</h3>
                    <p>Try adjusting your filters or search terms.</p>
                    <a href="tickets.php" class="btn btn-outline btn-sm" style="margin-top:12px;">Clear Filters</a>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Subject</th>
                        <th>User</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th style="min-width:185px;">Update Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $t):
                    $isCritical  = $t['priority']==='Critical' && !in_array($t['status'],['Resolved','Closed']);
                    $justUpdated = isset($_GET['updated']) && intval($_GET['updated'])===$t['id'];
                ?>
                <tr <?= $isCritical ? 'style="background:#fff5f5;"' : '' ?>
                    <?= $justUpdated ? 'id="updated-row"' : '' ?>>

                    <td><code style="font-size:.78rem;"><?= $t['ticket_number'] ?></code></td>

                    <td style="max-width:200px;">
                        <a href="<?= APP_URL ?>/ticket.php?id=<?= $t['id'] ?>"
                           style="color:var(--text);text-decoration:none;font-weight:500;"
                           title="<?= sanitize($t['subject']) ?>">
                            <?= strlen($t['subject'])>45
                                ? sanitize(substr($t['subject'],0,45)).'…'
                                : sanitize($t['subject']) ?>
                        </a>
                    </td>

                    <td>
                        <div style="font-size:.88rem;font-weight:500;"><?= sanitize($t['full_name']) ?></div>
                        <div style="font-size:.75rem;color:var(--text-muted);"><?= $t['college_id'] ?></div>
                    </td>

                    <td><?= $t['category'] ?></td>
                    <td><?= getPriorityBadge($t['priority']) ?></td>
                    <td><?= getStatusBadge($t['status']) ?></td>
                    <td style="white-space:nowrap;font-size:.83rem;"><?= timeAgo($t['created_at']) ?></td>

                    <!-- TASK 2: Inline status update form -->
                    <td>
                        <?php if ($t['status'] !== 'Closed'): ?>
                        <form method="POST" style="display:flex;gap:5px;align-items:center;">
                            <input type="hidden" name="ticket_id"  value="<?= $t['id'] ?>">
                            <input type="hidden" name="f_status"   value="<?= $status ?>">
                            <input type="hidden" name="f_category" value="<?= $category ?>">
                            <input type="hidden" name="f_priority" value="<?= $priority ?>">
                            <input type="hidden" name="f_user"     value="<?= $user_filter ?>">
                            <input type="hidden" name="f_search"   value="<?= $search ?>">
                            <select name="new_status" class="form-control"
                                    style="font-size:.8rem;padding:4px 6px;height:auto;">
                                <?php foreach (['Pending','In Progress','Resolved','Closed'] as $s): ?>
                                <option value="<?= $s ?>" <?= $t['status']===$s?'selected':'' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="update_status"
                                    class="btn btn-primary btn-sm">Save</button>
                        </form>
                        <?php else: ?>
                        <span style="font-size:.8rem;color:var(--text-muted);">— Closed —</span>
                        <?php endif; ?>
                    </td>

                    <!-- TASK 2: View / Close / Delete action buttons -->
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap;">

                            <a href="<?= APP_URL ?>/ticket.php?id=<?= $t['id'] ?>"
                               class="btn btn-outline btn-sm">View →</a>

                            <?php if ($t['status'] !== 'Closed'): ?>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('Close ticket <?= $t['ticket_number'] ?>?');">
                                <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                <button type="submit" name="close_ticket" class="btn btn-sm"
                                        style="background:#f59e0b;color:#fff;border:none;cursor:pointer;">
                                    🔒 Close
                                </button>
                            </form>
                            <?php endif; ?>

                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('Permanently delete <?= $t['ticket_number'] ?>? This cannot be undone.');">
                                <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                <button type="submit" name="delete_ticket" class="btn btn-sm"
                                        style="background:#ef4444;color:#fff;border:none;cursor:pointer;">
                                    🗑️ Delete
                                </button>
                            </form>

                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php if (isset($_GET['updated'])): ?>
<script>
var el = document.getElementById('updated-row');
if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el.style.transition = 'background .5s';
    el.style.background = '#d1fae5';
    setTimeout(function(){ el.style.background = ''; }, 2500);
}
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
