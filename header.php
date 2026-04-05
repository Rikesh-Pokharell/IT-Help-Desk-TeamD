<?php
if (!defined('APP_NAME')) require_once __DIR__ . '/config.php';
$currentPage = basename($_SERVER['PHP_SELF']);
$userInitial = isset($_SESSION['full_name']) ? strtoupper(substr($_SESSION['full_name'], 0, 1)) : 'U';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' — ' : '' ?><?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💻</text></svg>">
</head>
<body>
<nav class="navbar">
    <a class="navbar-brand" href="<?= APP_URL ?>/dashboard.php">
        💻 <?= APP_NAME ?>
    </a>
    <div class="navbar-nav">
        <?php if (isLoggedIn()): ?>
            <a href="<?= APP_URL ?>/dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
            <?php if (!isAdmin()): ?>
                <a href="<?= APP_URL ?>/submit_ticket.php" class="nav-link <?= $currentPage === 'submit_ticket.php' ? 'active' : '' ?>">+ New Ticket</a>
                <a href="<?= APP_URL ?>/my_tickets.php" class="nav-link <?= $currentPage === 'my_tickets.php' ? 'active' : '' ?>">My Tickets</a>
            <?php else: ?>
                <a href="<?= APP_URL ?>/admin/tickets.php" class="nav-link <?= $currentPage === 'tickets.php' ? 'active' : '' ?>">All Tickets</a>
                <a href="<?= APP_URL ?>/admin/users.php" class="nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>">Users</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php if (isLoggedIn()): ?>
    <div class="nav-user">
        <div class="nav-avatar"><?= $userInitial ?></div>
        <span><?= sanitize($_SESSION['full_name']) ?></span>
        <a href="<?= APP_URL ?>/logout.php" class="nav-link btn-sm" style="color:rgba(255,255,255,0.7);font-size:.82rem;">Logout</a>
    </div>
    <?php endif; ?>
</nav>
