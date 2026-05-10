<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'Dashboard';
$db = getDB();
$user_id = $_SESSION['user_id'];

if (isAdmin()) {
    // Admin stats
    $total   = $db->query("SELECT COUNT(*) as c FROM tickets")->fetch_assoc()['c'];
    $pending = $db->query("SELECT COUNT(*) as c FROM tickets WHERE status='Pending'")->fetch_assoc()['c'];
    $inprog  = $db->query("SELECT COUNT(*) as c FROM tickets WHERE status='In Progress'")->fetch_assoc()['c'];
    $resolved= $db->query("SELECT COUNT(*) as c FROM tickets WHERE status='Resolved'")->fetch_assoc()['c'];
    $recent  = $db->query("SELECT t.*, u.full_name FROM tickets t JOIN users u ON t.user_id=u.id ORDER BY t.created_at DESC LIMIT 8");
} else {
    // User stats
    $total   = $db->query("SELECT COUNT(*) as c FROM tickets WHERE user_id=$user_id")->fetch_assoc()['c'];
    $pending = $db->query("SELECT COUNT(*) as c FROM tickets WHERE user_id=$user_id AND status='Pending'")->fetch_assoc()['c'];
    $inprog  = $db->query("SELECT COUNT(*) as c FROM tickets WHERE user_id=$user_id AND status='In Progress'")->fetch_assoc()['c'];
    $resolved= $db->query("SELECT COUNT(*) as c FROM tickets WHERE user_id=$user_id AND status='Resolved'")->fetch_assoc()['c'];
    $recent  = $db->query("SELECT * FROM tickets WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 8");
}

include 'includes/header.php';
?>
<div class="container">
    <div class="page-header flex justify-between items-center">
        <div>
            <h1 class="page-title">👋 Welcome, <?= sanitize($_SESSION['full_name']) ?>!</h1>
            <p class="page-subtitle"><?= isAdmin() ? 'IT Support Admin Dashboard' : ucfirst($_SESSION['role']) . ' Portal' ?></p>
        </div>
        <?php if (!isAdmin()): ?>
        <a href="submit_ticket.php" class="btn btn-accent">+ Submit New Ticket</a>
        <?php endif; ?>
    </div>

    <!-- Stats -->
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
    </div>

    <!-- Recent Tickets -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Recent Tickets</span>
            <a href="<?= isAdmin() ? 'admin/tickets.php' : 'my_tickets.php' ?>" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="table-wrap">
            <?php $rows = $recent->fetch_all(MYSQLI_ASSOC); ?>
            <?php if (empty($rows)): ?>
                <div class="empty-state">
                    <div class="icon">🎫</div>
                    <h3>No tickets yet</h3>
                    <p><?= isAdmin() ? 'No tickets have been submitted.' : 'Submit your first support ticket.' ?></p>
                    <?php if (!isAdmin()): ?>
                    <a href="submit_ticket.php" class="btn btn-primary mt-2" style="margin-top:16px;">Submit Ticket</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <?php if (isAdmin()): ?><th>User</th><?php endif; ?>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $t): ?>
                    <tr>
                        <td><code style="font-size:.8rem;"><?= $t['ticket_number'] ?></code></td>
                        <td><?= sanitize($t['subject']) ?></td>
                        <td><?= $t['category'] ?></td>
                        <?php if (isAdmin()): ?><td><?= sanitize($t['full_name']) ?></td><?php endif; ?>
                        <td><?= getPriorityBadge($t['priority']) ?></td>
                        <td><?= getStatusBadge($t['status']) ?></td>
                        <td style="white-space:nowrap;"><?= timeAgo($t['created_at']) ?></td>
                        <td><a href="ticket.php?id=<?= $t['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
