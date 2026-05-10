<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    header("Location: " . APP_URL . "/dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitize($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $db   = getDB();

        // Student can login with college_id OR email
        // Staff/Faculty can login with email ONLY
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR college_id = ? LIMIT 1");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {

            // Staff and Faculty: must use email only (not college_id)
            if (in_array($user['role'], ['staff','faculty']) && $identifier === $user['college_id']) {
                $error = 'Staff and Faculty must login using their email address only.';
            } else {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];
                header("Location: " . APP_URL . "/dashboard.php");
                exit();
            }
        } else {
            $error = 'Invalid credentials. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="auth-logo-icon">💻</div>
            <h1 class="auth-title"><?= APP_NAME ?></h1>
            <p class="auth-subtitle">Sign in to your account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">Account created! Please login.</div>
        <?php endif; ?>
        <?php if (isset($_GET['logout'])): ?>
            <div class="alert alert-info">You have been logged out.</div>
        <?php endif; ?>

        <!-- TAB SWITCHER -->
        <div style="display:flex;border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:20px;">
            <button type="button" id="tab-student" onclick="switchTab('student')"
                style="flex:1;padding:10px;border:none;cursor:pointer;font-weight:600;font-size:.9rem;background:var(--primary);color:#fff;transition:.2s;">
                🎓 Student
            </button>
            <button type="button" id="tab-staff" onclick="switchTab('staff')"
                style="flex:1;padding:10px;border:none;cursor:pointer;font-weight:600;font-size:.9rem;background:#f0f4f9;color:var(--text-muted);transition:.2s;">
                👤 Staff / Faculty
            </button>
        </div>

        <form method="POST" novalidate>
            <div class="form-group">
                <label class="form-label" id="identifier-label">Student ID or Email</label>
                <input type="text" name="identifier" id="identifier-input" class="form-control"
                       placeholder="e.g. STU2024001 or john@college.edu" required
                       value="<?= sanitize($_POST['identifier'] ?? '') ?>">
                <span style="font-size:.8rem;color:var(--text-muted);margin-top:4px;display:block;" id="identifier-hint">
                    Students can use College ID (e.g. STU2024001) or email
                </span>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <div style="margin-bottom:16px;">
                <a href="forgot_password.php" style="font-size:.88rem;color:var(--primary);">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="register.php">Sign up</a>
        </div>

        <hr class="divider">
        <div style="background:var(--bg);border-radius:8px;padding:12px 16px;font-size:0.8rem;color:var(--text-muted);">
            <strong>Login Guide:</strong><br>
            🎓 <strong>Student:</strong> Use College ID (STU...) or email<br>
            👤 <strong>Staff/Faculty:</strong> Use email only<br>
            🔑 <strong>Admin:</strong> <code>admin@college.edu</code> / <code>password</code>
        </div>
    </div>
</div>

<script>
function switchTab(type) {
    var studentBtn = document.getElementById('tab-student');
    var staffBtn   = document.getElementById('tab-staff');
    var label      = document.getElementById('identifier-label');
    var input      = document.getElementById('identifier-input');
    var hint       = document.getElementById('identifier-hint');

    if (type === 'student') {
        studentBtn.style.background = 'var(--primary)';
        studentBtn.style.color = '#fff';
        staffBtn.style.background = '#f0f4f9';
        staffBtn.style.color = 'var(--text-muted)';
        label.textContent = 'Student ID or Email';
        input.placeholder = 'e.g. STU2024001 or john@college.edu';
        hint.textContent = 'Students can use College ID (e.g. STU2024001) or email';
    } else {
        staffBtn.style.background = 'var(--primary)';
        staffBtn.style.color = '#fff';
        studentBtn.style.background = '#f0f4f9';
        studentBtn.style.color = 'var(--text-muted)';
        label.textContent = 'Email Address';
        input.placeholder = 'e.g. teacher@college.edu';
        hint.textContent = 'Staff and Faculty must login using email only';
    }
}
</script>
</body>
</html>
