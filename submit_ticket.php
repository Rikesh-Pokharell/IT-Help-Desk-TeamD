<?php
require_once '../includes/config.php';
requireLogin();
requireAdmin();

$pageTitle = 'Submit Ticket (Admin)';
$error = $success = '';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email       = sanitize($_POST['email'] ?? '');
    $category    = sanitize($_POST['category'] ?? '');
    $subject     = sanitize($_POST['subject'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $priority    = sanitize($_POST['priority'] ?? 'Medium');

    $valid_cats  = ['Network','Account','Software','Hardware','Other'];
    $valid_prios = ['Low','Medium','High','Critical'];

    if (empty($email) || empty($category) || empty($subject) || empty($description)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!in_array($category, $valid_cats)) {
        $error = 'Invalid category.';
    } elseif (!in_array($priority, $valid_prios)) {
        $error = 'Invalid priority.';
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $error = "No user found with this email.";
        } else {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            $ticket_number = generateTicketNumber();
            $stmt = $db->prepare("INSERT INTO tickets (ticket_number, user_id, category, subject, description, priority) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("sissss", $ticket_number, $user_id, $category, $subject, $description, $priority);
            if ($stmt->execute()) {
                $new_id = $db->insert_id;
                $db->query("UPDATE tickets SET status='In Progress' WHERE id=$new_id");
                $success = "Ticket <strong>$ticket_number</strong> submitted for <strong>$email</strong>.";
            } else {
                $error = 'Could not submit ticket.';
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container" style="max-width:760px;">

    <div class="breadcrumb">
        <a href="tickets.php">All Tickets</a>
        <span>›</span>
        <span>Submit Ticket (Admin)</span>
    </div>

    <div class="page-header">
        <h1 class="page-title">🛠️ Submit Ticket (Admin)</h1>
        <p class="page-subtitle">Submit a support ticket on behalf of a user using their email.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">⚠ <?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= $success ?> — <a href="tickets.php">View all tickets</a></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" novalidate>

                <div class="form-group">
                    <label class="form-label">User Email *</label>
                    <input type="email" name="email" class="form-control"
                           placeholder="Enter user's email (e.g. student@college.edu)"
                           value="<?= sanitize($_POST['email'] ?? '') ?>">
                    <span class="field-error" id="err-email"></span>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="category" class="form-control">
                            <option value="">-- Select --</option>
                            <option value="Network"  <?= ($_POST['category']??'')==='Network'  ?'selected':'' ?>>🌐 Network</option>
                            <option value="Account"  <?= ($_POST['category']??'')==='Account'  ?'selected':'' ?>>👤 Account</option>
                            <option value="Software" <?= ($_POST['category']??'')==='Software' ?'selected':'' ?>>💿 Software</option>
                            <option value="Hardware" <?= ($_POST['category']??'')==='Hardware' ?'selected':'' ?>>🖥️ Hardware</option>
                            <option value="Other"    <?= ($_POST['category']??'')==='Other'    ?'selected':'' ?>>❓ Other</option>
                        </select>
                        <span class="field-error" id="err-category"></span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-control">
                            <option value="Low">🟢 Low</option>
                            <option value="Medium" selected>🟡 Medium</option>
                            <option value="High">🔴 High</option>
                            <option value="Critical">🚨 Critical</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Subject *</label>
                    <input type="text" name="subject" class="form-control"
                           placeholder="Brief description of the issue" maxlength="200"
                           value="<?= sanitize($_POST['subject'] ?? '') ?>">
                    <span class="field-error" id="err-subject"></span>
                </div>

                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea name="description" class="form-control" rows="5"
                              placeholder="Describe the issue in detail..."><?= sanitize($_POST['description'] ?? '') ?></textarea>
                    <span class="field-error" id="err-description"></span>
                </div>

                <div style="display:flex;gap:12px;justify-content:flex-end;">
                    <a href="tickets.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">🎫 Submit Ticket →</button>
                </div>

            </form>
        </div>
    </div>
</div>

<style>
.field-error { color:#e53e3e; font-size:.82rem; margin-top:4px; display:none; }
.field-error.show { display:block; }
.input-error { border-color:#e53e3e !important; box-shadow:0 0 0 3px rgba(229,62,62,0.15) !important; }
</style>

<script>
(function() {
    var form = document.querySelector('form');

    function showError(fieldName, msg) {
        var field = form.querySelector('[name=' + fieldName + ']');
        var err   = document.getElementById('err-' + fieldName);
        field.classList.add('input-error');
        err.textContent = '⚠ ' + msg;
        err.classList.add('show');
    }

    function clearError(fieldName) {
        var field = form.querySelector('[name=' + fieldName + ']');
        var err   = document.getElementById('err-' + fieldName);
        field.classList.remove('input-error');
        err.textContent = '';
        err.classList.remove('show');
    }

    ['email', 'category', 'subject', 'description'].forEach(function(name) {
        var field = form.querySelector('[name=' + name + ']');
        field.addEventListener('input',  function() { clearError(name); });
        field.addEventListener('change', function() { clearError(name); });
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var valid = true;

        var email       = form.querySelector('[name=email]').value.trim();
        var category    = form.querySelector('[name=category]').value;
        var subject     = form.querySelector('[name=subject]').value.trim();
        var description = form.querySelector('[name=description]').value.trim();

        // All checked at once
        if (!email)                        { showError('email',       'Please enter user email.');               valid = false; }
        else if (!/\S+@\S+\.\S+/.test(email)) { showError('email',   'Please enter a valid email.');            valid = false; }
        else                               { clearError('email'); }

        if (!category)                     { showError('category',    'Please select a category.');              valid = false; }
        else                               { clearError('category'); }

        if (!subject)                      { showError('subject',     'Please enter a subject.');                valid = false; }
        else if (subject.length < 5)       { showError('subject',     'Subject must be at least 5 characters.'); valid = false; }
        else                               { clearError('subject'); }

        if (!description)                  { showError('description', 'Please enter a description.');            valid = false; }
        else if (description.length < 10)  { showError('description', 'Description must be at least 10 characters.'); valid = false; }
        else                               { clearError('description'); }

        if (valid) form.submit();
    });
})();
</script>

<?php include '../includes/footer.php'; ?>