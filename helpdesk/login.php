<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    header("Location: " . APP_URL . "/dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitize($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR college_id = ? LIMIT 1");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];
            header("Location: " . APP_URL . "/dashboard.php");
            exit();
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

        <form method="POST">
            <div class="form-group">
                <label class="form-label">College Email or ID</label>
                <input type="text" name="identifier" class="form-control" placeholder="e.g. john@college.edu or STU2024001" required value="<?= sanitize($_POST['identifier'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <div class="flex justify-between items-center mb-2" style="margin-bottom:16px;">
                <a href="forgot_password.php" style="font-size:.88rem;color:var(--primary);">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="register.php">Sign up</a>
        </div>

        <hr class="divider">
        <div style="background:var(--bg);border-radius:8px;padding:12px 16px;font-size:0.8rem;color:var(--text-muted);">
            <strong>Demo Credentials:</strong><br>
            Admin: <code>admin@college.edu</code> / <code>password</code>
        </div>
    </div>
</div>
</body>
</html>
