<?php
// Include configuration file and check user authentication
require_once 'includes/config.php';
requireLogin();

// Redirect admin users to dashboard
if (isAdmin()) {
    header("Location: " . APP_URL . "/dashboard.php");
    exit();
}

$pageTitle = 'Submit Ticket';
$error = $success = '';

// Handle ticket submission form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize user inputs
    $category    = sanitize($_POST['category'] ?? '');
    $subject     = sanitize($_POST['subject'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $priority    = sanitize($_POST['priority'] ?? 'Medium');

    // Define valid categories and priorities
    $valid_cats = ['Network','Account','Software','Hardware','Other'];
    $valid_prios = ['Low','Medium','High','Critical'];

    // Validate form fields
    if (empty($category) || empty($subject) || empty($description)) {
        $error = 'Please fill in all required fields.';
    } elseif (!in_array($category, $valid_cats)) {
        $error = 'Invalid category.';
    } elseif (!in_array($priority, $valid_prios)) {
        $error = 'Invalid priority.';
    } else {

        // Generate unique ticket number
        $ticket_number = generateTicketNumber();

        // Get logged-in user ID
        $user_id = $_SESSION['user_id'];

        // Connect to database and insert ticket record
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO tickets (ticket_number, user_id, category, subject, description, priority) VALUES (?,?,?,?,?,?)");

        $stmt->bind_param(
            "sissss",
            $ticket_number,
            $user_id,
            $category,
            $subject,
            $description,
            $priority
        );

        // Redirect user after successful ticket creation
        if ($stmt->execute()) {
            $new_id = $db->insert_id;
            header("Location: ticket.php?id=$new_id&created=1");
            exit();
        } else {
            $error = 'Could not submit ticket. Please try again.';
        }
    }
}

// Load page header
include 'includes/header.php';
?>

<div class="container" style="max-width:720px;">

    <!-- Navigation breadcrumb -->
    <div class="breadcrumb">
        <a href="dashboard.php">Dashboard</a>
        <span>›</span>
        <span>Submit Ticket</span>
    </div>

    <!-- Page title section -->
    <div class="page-header">
        <h1 class="page-title">🎫 Submit a Support Ticket</h1>
        <p class="page-subtitle">
            Describe your issue and our IT team will respond as soon as possible.
        </p>
    </div>

    <!-- Display error messages -->
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Ticket submission form -->
    <div class="card">
        <div class="card-body">

            <form method="POST" novalidate>

                <!-- Category and priority fields -->
                <div class="grid-2">

                    <div class="form-group">
                        <label class="form-label">Category *</label>

                        <select name="category" class="form-control">
                            <option value="">-- Select category --</option>
                            <option value="Network" <?= ($_POST['category']??'')==='Network'?'selected':'' ?>>
                                🌐 Network / WiFi
                            </option>
                            <option value="Account" <?= ($_POST['category']??'')==='Account'?'selected':'' ?>>
                                👤 Account / Login
                            </option>
                            <option value="Software" <?= ($_POST['category']??'')==='Software'?'selected':'' ?>>
                                💿 Software / App
                            </option>
                            <option value="Hardware" <?= ($_POST['category']??'')==='Hardware'?'selected':'' ?>>
                                🖥️ Hardware
                            </option>
                            <option value="Other" <?= ($_POST['category']??'')==='Other'?'selected':'' ?>>
                                ❓ Other
                            </option>
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

                <!-- Ticket subject -->
                <div class="form-group">
                    <label class="form-label">Subject *</label>

                    <input
                        type="text"
                        name="subject"
                        class="form-control"
                        placeholder="Brief description of the issue"
                        maxlength="200"
                        value="<?= sanitize($_POST['subject'] ?? '') ?>">

                    <span class="field-error" id="err-subject"></span>
                </div>

                <!-- Detailed issue description -->
                <div class="form-group">
                    <label class="form-label">Description *</label>

                    <textarea
                        name="description"
                        class="form-control"
                        rows="5"
                        placeholder="Describe the problem in detail. Include any error messages, when it started, what you've already tried..."><?= sanitize($_POST['description'] ?? '') ?></textarea>

                    <span class="field-error" id="err-description"></span>
                </div>

                <!-- Form action buttons -->
                <div style="display:flex;gap:12px;justify-content:flex-end;">
                    <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        Submit Ticket →
                    </button>
                </div>

            </form>

        </div>
    </div>

    <!-- User guidance section -->
    <div class="card mt-3" style="margin-top:20px;background:var(--bg);">
        <div class="card-body">

            <strong>💡 Tips for faster resolution:</strong>

            <ul style="margin-top:8px;margin-left:20px;font-size:.88rem;color:var(--text-muted);line-height:1.8;">
                <li>Be specific about the problem and when it started</li>
                <li>Include any error messages you see</li>
                <li>Mention what steps you've already tried</li>
                <li>Include your device type if relevant</li>
            </ul>

        </div>
    </div>

</div>

<style>
/* Validation error message style */
.field-error {
    color:#e53e3e;
    font-size:.82rem;
    margin-top:4px;
    display:none;
}

.field-error.show {
    display:block;
}

/* Highlight invalid fields */
.input-error {
    border-color:#e53e3e !important;
    box-shadow:0 0 0 3px rgba(229,62,62,0.15) !important;
}
</style>

<script>
(function() {

    var form = document.querySelector('form');

    // Show validation error
    function showError(fieldName, msg) {
        var field = form.querySelector('[name=' + fieldName + ']');
        var err   = document.getElementById('err-' + fieldName);

        field.classList.add('input-error');
        err.textContent = '⚠ ' + msg;
        err.classList.add('show');
    }

    // Remove validation error
    function clearError(fieldName) {
        var field = form.querySelector('[name=' + fieldName + ']');
        var err   = document.getElementById('err-' + fieldName);

        field.classList.remove('input-error');
        err.textContent = '';
        err.classList.remove('show');
    }

    // Clear errors while user types
    ['category', 'subject', 'description'].forEach(function(name) {

        var field = form.querySelector('[name=' + name + ']');

        field.addEventListener('input', function() {
            clearError(name);
        });

        field.addEventListener('change', function() {
            clearError(name);
        });
    });

    // Client-side form validation
    form.addEventListener('submit', function(e) {

        e.preventDefault();

        var valid = true;

        var category =
            form.querySelector('[name=category]').value;

        var subject =
            form.querySelector('[name=subject]').value.trim();

        var description =
            form.querySelector('[name=description]').value.trim();

        if (!category) {
            showError('category', 'Please select a category.');
            valid = false;
        } else {
            clearError('category');
        }

        if (!subject) {
            showError('subject', 'Please enter a subject.');
            valid = false;
        } else if (subject.length < 5) {
            showError('subject', 'Subject must be at least 5 characters.');
            valid = false;
        } else {
            clearError('subject');
        }

        if (!description) {
            showError('description', 'Please enter a description.');
            valid = false;
        } else if (description.length < 10) {
            showError('description', 'Description must be at least 10 characters.');
            valid = false;
        } else {
            clearError('description');
        }

        // Submit form if all validations pass
        if (valid) {
            form.submit();
        }
    });

})();
</script>

<?php
// Load footer template
include 'includes/footer.php';
?>