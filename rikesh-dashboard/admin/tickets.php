<?php
/**
 * Admin Ticket Dashboard
 * Developer: Rikesh
 * Task: Ticket Dashboard & Status Update (Week: 2–4 April 2026)
 *
 * Displays all submitted tickets for admin/IT support.
 * Supports filtering by status (Pending, In Progress, Resolved).
 * Enables inline status update directly from the dashboard.
 */

require_once '../includes/config.php';
requireLogin();
requireAdmin();

$pageTitle = 'Ticket Dashboard';
$db = getDB();

// ── Filters ──────────────────────────────────────────────────────────────────
$status      = sanitize($_GET['status']   ?? '');
$priority    = sanitize($_GET['priority'] ?? '');
$category    = sanitize($_GET['category'] ?? '');
$search      = sanitize($_GET['search']   ?? '');

$where = "WHERE 1=1";
if ($status)   $where .= " AND t.status   = '" . $db->real_escape_string($status)   . "'";
if ($priority) $where .= " AND t.priority = '" . $db->real_escape_string($priority) . "'";
if ($category) $where .= " AND t.category = '" . $db->real_escape_string($category) . "'";
if ($search)   $where .= " AND (t.subject LIKE '%" . $db->real_escape_string($search) . "%'
                               OR t.ticket_number LIKE '%" . $db->real_escape_string($search) . "%'
                               OR u.full_name LIKE '%" . $db->real_escape_string($search) . "%')";

// ── Inline status update (AJAX-friendly POST) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $ticket_id  = intval($_POST['ticket_id']  ?? 0);
    $new_status = sanitize($_POST['new_status'] ?? '');
    $valid      = ['Pending', 'In Progress', 'Resolved'];

    if ($ticket_id > 0 && in_array($new_status, $valid)) {
        $upd = $db->prepare("UPDATE tickets SET status = ? WHERE id = ?");
        $upd->bind_param("si", $new_status, $ticket_id);
        $upd->execute();
    }
    // Redirect back preserving filters
    $qs = http_build_query(array_filter([
        'status'   => $status,
        'priority' => $priority,
        'category' => $category,
        'search'   => $search,
        'updated'  => $ticket_id,
    ]));
    header("Location: tickets.php?$qs");
    exit();
}

// ── Fetch tickets ─────────────────────────────────────────────────────────────
$tickets = $db->query(
    "SELECT t.*, u.full_name, u.email, u.college_id
     FROM tickets t
     JOIN users u ON t.user_id = u.id
     $where
     ORDER BY t.created_at DESC"
);

// ── Stats counters ────────────────────────────────────────────────────────────
$total    = $db->query("SELECT COUNT(*) AS c FROM tickets")->fetch_assoc()['c'];
$pending  = $db->query("SELECT COUNT(*) AS c FROM tickets WHERE status='Pending'")->fetch_assoc()['c'];
$inprog   = $db->query("SELECT COUNT(*) AS c FROM tickets WHERE status='In Progress'")->fetch_assoc()['c'];
$resolved = $db->query("SELECT COUNT(*) AS c FROM tickets WHERE status='Resolved'")->fetch_assoc()['c'];
$critical = $db->query("SELECT COUNT(*) AS c FROM tickets WHERE priority='Critical' AND status NOT IN ('Resolved','Closed')")->fetch_assoc()['c'];

include '../includes/header.php';
?>

<div class="container">

    <!-- ── Page Header ──────────────────────────────────────────────────── -->
    <div class="page-header">
        <h1 class="page-title">🛠️ Ticket Dashboard</h1>
        <p class="page-subtitle">Monitor and update all support tickets across the system</p>
    </div>

    <?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success">✅ Ticket #<?= intval($_GET['updated']) ?> status updated successfully.</div>
    <?php endif; ?>

    <!-- ── Stats Grid ───────────────────────────────────────────────────── -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon">🎫</div>
            <div class="stat-value"><?= $total ?></div>
            <div class="stat-label">Total Tickets</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon">⏳</div>
            <div class="stat-value"><?= $pending ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon">🔄</div>
            <div class="stat-value"><?= $inprog ?></div>
            <div class="stat-label">In Progress</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">✅</div>
            <div class="stat-value"><?= $resolved ?></div>
            <div class="stat-label">Resolved</div>
        </div>
        <?php if ($critical > 0): ?>
        <div class="stat-card red">
            <div class="stat-icon">🚨</div>
            <div class="stat-value"><?= $critical ?></div>
            <div class="stat-label">Critical Open</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Quick-Status Filter Tabs ─────────────────────────────────────── -->
    <div class="status-tabs" style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;">
        <?php
        $statuses = [
            ''            => ['label' => 'All',         'icon' => '📋'],
            'Pending'     => ['label' => 'Pending',     'icon' => '⏳'],
            'In Progress' => ['label' => 'In Progress', 'icon' => '🔄'],
            'Resolved'    => ['label' => 'Resolved',    'icon' => '✅'],
        ];
        foreach ($statuses as $val => $meta):
            $active = ($status === $val);
            $qs = http_build_query(array_filter(['status'=>$val,'priority'=>$priority,'category'=>$category,'search'=>$search]));
        ?>
        <a href="tickets.php?<?= $qs ?>"
           class="btn <?= $active ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            <?= $meta['icon'] ?> <?= $meta['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ── Search & Advanced Filters ────────────────────────────────────── -->
    <form method="GET" class="filters-bar" style="margin-bottom:20px;">
        <?php if ($status): ?><input type="hidden" name="status" value="<?= $status ?>"><?php endif; ?>
        <input type="text" name="search" class="form-control search-input"
               placeholder="🔍 Search ticket #, subject, user…"
               value="<?= $search ?>">
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
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <?php if ($search || $priority || $category): ?>
        <a href="tickets.php<?= $status ? '?status='.$status : '' ?>" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
    </form>

    <!-- ── Ticket Table ──────────────────────────────────────────────────── -->
    <div class="card">
        <div class="table-wrap">
            <?php $rows = $tickets->fetch_all(MYSQLI_ASSOC); ?>
            <?php if (empty($rows)): ?>
                <div class="empty-state">
                    <div class="icon">🔍</div>
                    <h3>No tickets found</h3>
                    <p>Try adjusting your filters or search terms.</p>
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
                        <th>Current Status</th>
                        <th>Submitted</th>
                        <th style="min-width:200px;">Update Status</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $t):
                    $isCritical = $t['priority'] === 'Critical'
                                  && !in_array($t['status'], ['Resolved','Closed']);
                    $highlightRow = $isCritical ? 'style="background:#fff5f5;"' : '';
                    $updated     = isset($_GET['updated']) && intval($_GET['updated']) === $t['id'];
                ?>
                <tr <?= $highlightRow ?> <?= $updated ? 'id="updated-row"' : '' ?>>
                    <td><code style="font-size:.78rem;"><?= $t['ticket_number'] ?></code></td>
                    <td style="max-width:220px;word-break:break-word;"><?= sanitize($t['subject']) ?></td>
                    <td>
                        <div style="font-size:.88rem;"><?= sanitize($t['full_name']) ?></div>
                        <div style="font-size:.78rem;color:var(--text-muted);"><?= $t['college_id'] ?></div>
                    </td>
                    <td><?= $t['category'] ?></td>
                    <td><?= getPriorityBadge($t['priority']) ?></td>
                    <td><?= getStatusBadge($t['status']) ?></td>
                    <td style="white-space:nowrap;font-size:.85rem;"><?= timeAgo($t['created_at']) ?></td>

                    <!-- ── Inline Status Update ────────────────────────── -->
                    <td>
                        <form method="POST" style="display:flex;gap:6px;align-items:center;">
                            <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                            <select name="new_status" class="form-control"
                                    style="font-size:.82rem;padding:4px 6px;height:auto;">
                                <?php foreach (['Pending','In Progress','Resolved'] as $s): ?>
                                <option value="<?= $s ?>" <?= $t['status']===$s?'selected':'' ?>>
                                    <?= $s ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="update_status"
                                    class="btn btn-primary btn-sm"
                                    title="Save status change">
                                Save
                            </button>
                        </form>
                    </td>

                    <td>
                        <a href="<?= APP_URL ?>/ticket.php?id=<?= $t['id'] ?>"
                           class="btn btn-outline btn-sm">View →</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div><!-- /.card -->

</div><!-- /.container -->

<?php if (isset($_GET['updated'])): ?>
<script>
// Scroll to the updated row so admin can confirm the change
const el = document.getElementById('updated-row');
if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el.style.transition = 'background .6s';
    el.style.background  = '#e8f5e9';
    setTimeout(() => { el.style.background = ''; }, 2500);
}
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
