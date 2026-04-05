<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'View Ticket';
$db = getDB();
$ticket_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Get ticket
$stmt = $db->prepare("SELECT t.*, u.full_name, u.email, u.college_id, u.role as user_role FROM tickets t JOIN users u ON t.user_id=u.id WHERE t.id=?");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if (!$ticket) { echo "Ticket not found."; exit(); }

// Security: users can only see their own tickets
if (!isAdmin() && $ticket['user_id'] != $user_id) {
    header("Location: " . APP_URL . "/my_tickets.php");
    exit();
}

$error = '';

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $message = sanitize($_POST['message'] ?? '');
    if (empty($message)) {
        $error = 'Reply message cannot be empty.';
    } else {
        $is_admin = isAdmin() ? 1 : 0;
        $stmt = $db->prepare("INSERT INTO replies (ticket_id, user_id, message, is_admin) VALUES (?,?,?,?)");
        $stmt->bind_param("iisi", $ticket_id, $user_id, $message, $is_admin);
        $stmt->execute();

        // If admin replies, auto-set to In Progress if Pending
        if (isAdmin() && $ticket['status'] === 'Pending') {
            $db->query("UPDATE tickets SET status='In Progress' WHERE id=$ticket_id");
        }
        header("Location: ticket.php?id=$ticket_id#replies");
        exit();
    }
}

// Handle status/priority update (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ticket']) && isAdmin()) {
    $new_status   = sanitize($_POST['status'] ?? '');
    $new_priority = sanitize($_POST['priority'] ?? '');
    $valid_statuses  = ['Pending','In Progress','Resolved','Closed'];
    $valid_priorities = ['Low','Medium','High','Critical'];
    if (in_array($new_status, $valid_statuses) && in_array($new_priority, $valid_priorities)) {
        $upd = $db->prepare("UPDATE tickets SET status=?, priority=? WHERE id=?");
        $upd->bind_param("ssi", $new_status, $new_priority, $ticket_id);
        $upd->execute();
        header("Location: ticket.php?id=$ticket_id&updated=1");
        exit();
    }
}

// Re-fetch updated ticket
$stmt = $db->prepare("SELECT t.*, u.full_name, u.email, u.college_id, u.role as user_role FROM tickets t JOIN users u ON t.user_id=u.id WHERE t.id=?");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

// Get replies
$replies = $db->query("SELECT r.*, u.full_name, u.role FROM replies r JOIN users u ON r.user_id=u.id WHERE r.ticket_id=$ticket_id ORDER BY r.created_at ASC");

include 'includes/header.php';
?>
<div class="container" style="max-width:860px;">
    <div class="breadcrumb">
        <a href="dashboard.php">Dashboard</a>
        <span>›</span>
        <a href="<?= isAdmin() ? 'admin/tickets.php' : 'my_tickets.php' ?>"><?= isAdmin() ? 'All Tickets' : 'My Tickets' ?></a>
        <span>›</span>
        <span><?= $ticket['ticket_number'] ?></span>
    </div>

    <?php if (isset($_GET['created'])): ?>
    <div class="alert alert-success">✅ Your ticket has been submitted! We'll respond as soon as possible.</div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success">✅ Ticket updated successfully.</div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Ticket Header -->
    <div class="card" style="margin-bottom:20px;">
        <div class="ticket-header">
            <div class="ticket-id">Ticket <?= $ticket['ticket_number'] ?></div>
            <div class="ticket-subject"><?= sanitize($ticket['subject']) ?></div>
            <div class="ticket-meta">
                <span>📁 <?= $ticket['category'] ?></span>
                <span>👤 <?= sanitize($ticket['full_name']) ?> (<?= $ticket['college_id'] ?>)</span>
                <span>🕐 <?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></span>
                <span><?= getStatusBadge($ticket['status']) ?></span>
                <span><?= getPriorityBadge($ticket['priority']) ?></span>
            </div>
        </div>
        <div class="card-body">
            <strong style="font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);">Description</strong>
            <p style="margin-top:10px;line-height:1.7;white-space:pre-wrap;"><?= sanitize($ticket['description']) ?></p>
        </div>
    </div>

    <!-- Admin Update Panel -->
    <?php if (isAdmin()): ?>
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><span class="card-title">⚙️ Update Ticket</span></div>
        <div class="card-body">
            <form method="POST">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <?php foreach (['Pending','In Progress','Resolved','Closed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $ticket['status']===$s?'selected':'' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-control">
                            <?php foreach (['Low','Medium','High','Critical'] as $p): ?>
                            <option value="<?= $p ?>" <?= $ticket['priority']===$p?'selected':'' ?>><?= $p ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="update_ticket" class="btn btn-primary btn-sm">Update Ticket</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Replies -->
    <div class="card" id="replies">
        <div class="card-header"><span class="card-title">💬 Replies & Updates</span></div>
        <div class="card-body">
            <?php $reply_rows = $replies->fetch_all(MYSQLI_ASSOC); ?>
            <?php if (empty($reply_rows)): ?>
                <div style="text-align:center;padding:30px;color:var(--text-muted);">
                    <div style="font-size:2rem;margin-bottom:10px;">💬</div>
                    <p>No replies yet. IT support will respond soon.</p>
                </div>
            <?php else: ?>
                <?php foreach ($reply_rows as $r): ?>
                <div class="reply-card <?= $r['is_admin'] ? 'admin-reply' : '' ?>">
                    <div>
                        <span class="reply-author">
                            <?= $r['is_admin'] ? '🛠️ IT Support' : '👤 ' . sanitize($r['full_name']) ?>
                        </span>
                        <span class="reply-time"><?= date('M j, Y g:i A', strtotime($r['created_at'])) ?></span>
                    </div>
                    <div class="reply-body"><?= nl2br(sanitize($r['message'])) ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Reply Form -->
            <?php if ($ticket['status'] !== 'Closed'): ?>
            <hr class="divider">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label"><?= isAdmin() ? 'Reply to user:' : 'Add a reply or update:' ?></label>
                    <textarea name="message" class="form-control" rows="4" placeholder="Type your message here..." required></textarea>
                </div>
                <div style="display:flex;justify-content:flex-end;">
                    <button type="submit" name="reply" class="btn btn-primary">Send Reply →</button>
                </div>
            </form>
            <?php else: ?>
            <div class="alert alert-info mt-2" style="margin-top:16px;">This ticket is closed. No further replies can be added.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
