<?php
require_once 'includes/config.php';

if (isLoggedIn()) { header("Location: " . APP_URL . "/dashboard.php"); exit(); }

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = sanitize($_POST['full_name'] ?? '');
    $email      = sanitize($_POST['email'] ?? '');
    $college_id = sanitize($_POST['college_id'] ?? '');
    $role       = sanitize($_POST['role'] ?? 'student');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($email) || empty($college_id) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['student','faculty','staff'])) {
        $error = 'Invalid role selected.';
    } else {
        $db = getDB();
        $check = $db->prepare("SELECT id FROM users WHERE email = ? OR college_id = ?");
        $check->bind_param("ss", $email, $college_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email or College ID already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (full_name, email, college_id, password, role) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssss", $full_name, $email, $college_id, $hashed, $role);
            if ($stmt->execute()) {
                header("Location: login.php?registered=1");
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width:500px;">
        <div class="auth-logo">
            <div class="auth-logo-icon">🎓</div>
            <h1 class="auth-title">Create Account</h1>
            <p class="auth-subtitle"><?= APP_NAME ?></p>
        </div>

        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

        <form method="POST">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" placeholder="John Doe" required value="<?= sanitize($_POST['full_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">College ID</label>
                    <input type="text" name="college_id" class="form-control" placeholder="STU2024001" required value="<?= sanitize($_POST['college_id'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">College Email</label>
                <input type="email" name="email" class="form-control" placeholder="yourname@college.edu" required value="<?= sanitize($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-control">
                    <option value="student">Student</option>
                    <option value="faculty">Faculty</option>
                    <option value="staff">Staff</option>
                </select>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>
        <div class="auth-footer">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>
</div>
</body>
</html>
