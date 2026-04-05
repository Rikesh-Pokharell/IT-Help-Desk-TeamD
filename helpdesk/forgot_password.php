<?php
require_once 'includes/config.php';
if (isLoggedIn()) { header("Location: " . APP_URL . "/dashboard.php"); exit(); }

$msg = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $college_id = sanitize($_POST['college_id'] ?? '');

    if (empty($email) || empty($college_id) || empty($new_password)) {
        $error = 'All fields are required.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new_password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND college_id = ?");
        $stmt->bind_param("ss", $email, $college_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $error = 'No account found with that email and College ID.';
        } else {
            $user = $result->fetch_assoc();
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $upd = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->bind_param("si", $hashed, $user['id']);
            $upd->execute();
            $msg = 'Password reset successfully! You can now login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="auth-logo-icon">🔑</div>
            <h1 class="auth-title">Reset Password</h1>
            <p class="auth-subtitle">Verify your identity to reset password</p>
        </div>

        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?> <a href="login.php">Login here</a></div><?php endif; ?>

        <?php if (!$msg): ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">College Email</label>
                <input type="email" name="email" class="form-control" placeholder="yourname@college.edu" required>
            </div>
            <div class="form-group">
                <label class="form-label">College ID (for verification)</label>
                <input type="text" name="college_id" class="form-control" placeholder="STU2024001" required>
            </div>
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters" required>
            </div>
            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
        </form>
        <?php endif; ?>

        <div class="auth-footer"><a href="login.php">← Back to login</a></div>
    </div>
</div>
</body>
</html>
