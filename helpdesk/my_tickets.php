<?php
require_once 'includes/config.php';
requireLogin();
if (isAdmin()) { header("Location: " . APP_URL . "/admin/tickets.php"); exit(); }

$pageTitle = 'My Tickets';
$db = getDB();
$user_id = $_SESSION['user_id'];

$status   = sanitize($_GET['status'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$search   = sanitize($_GET['search'] ?? '');

$where = "WHERE t.user_id = $user_id";
if ($status)   $where .= " AND t.status = '" . $db->real_escape_string($status) . "'";
if ($category) $where .= " AND t.category = '" . $db->real_escape_string($category) . "'";
if ($search)   $where .= " AND (t.subject LIKE '%" . $db->real_escape_string($search) . "%' OR t.ticket_number LIKE '%" . $db->real_escape_string($search) . "%')";

$tickets = $db->query("SELECT * FROM tickets t $where ORDER BY t.created_at DESC");

include 'includes/header.php';
?>
<div class="container">
    <div class="page-header flex justify-between items-center">
        <div>
            <h1 class="page-title">🎫 My Tickets</h1>
            <p class="page-subtitle">Track all your submitted support requests</p>
        </div>
        <a href="submit_ticket.php" class="btn btn-accent">+ New Ticket</a>
    </div>

    <!-- Filters -->
    <form method="GET" class="filters-bar">
        <input type="text" name="search" class="form-control search-input" placeholder="🔍 Search ticket #, subject..." value="<?= $search ?>">
        <select name="status" class="form-control">
            <option value="">All Statuses</option>
            <option value="Pending" <?= $status==='Pending'?'selected':'' ?>>Pending</option>
            <option value="In Progress" <?= $status==='In Progress'?'selected':'' ?>>In Progress</option>
            <option value="Resolved" <?= $status==='Resolved'?'selected':'' ?>>Resolved</option>
            <option value="Closed" <?= $status==='Closed'?'selected':'' ?>>Closed</option>
        </select>
        <select name="category" class="form-control">
            <option value="">All Categories</option>
            <option value="Network" <?= $category==='Network'?'selected':'' ?>>Network</option>
            <option value="Account" <?= $category==='Account'?'selected':'' ?>>Account</option>
            <option value="Software" <?= $category==='Software'?'selected':'' ?>>Software</option>
            <option value="Hardware" <?= $category==='Hardware'?'selected':'' ?>>Hardware</option>
            <option value="Other" <?= $category==='Other'?'selected':'' ?>>Other</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <?php if ($status || $category || $search): ?>
        <a href="my_tickets.php" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
    </form>

    <div class="card">
        <div class="table-wrap">
            <?php $rows = $tickets->fetch_all(MYSQLI_ASSOC); ?>
            <?php if (empty($rows)): ?>
                <div class="empty-state">
                    <div class="icon">🔍</div>
                    <h3><?= $search || $status || $category ? 'No matching tickets' : 'No tickets yet' ?></h3>
                    <p><?= $search || $status || $category ? 'Try adjusting your filters.' : 'Your submitted tickets will appear here.' ?></p>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $t): ?>
                    <tr>
                        <td><code style="font-size:.8rem;"><?= $t['ticket_number'] ?></code></td>
                        <td><?= sanitize($t['subject']) ?></td>
                        <td><?= $t['category'] ?></td>
                        <td><?= getPriorityBadge($t['priority']) ?></td>
                        <td><?= getStatusBadge($t['status']) ?></td>
                        <td style="white-space:nowrap;"><?= timeAgo($t['updated_at']) ?></td>
                        <td><a href="ticket.php?id=<?= $t['id'] ?>" class="btn btn-outline btn-sm">View →</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
