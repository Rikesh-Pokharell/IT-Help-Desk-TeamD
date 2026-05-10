<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Change to your DB username
define('DB_PASS', '');            // Change to your DB password
define('DB_NAME', 'it_helpdesk');

// App Config
define('APP_NAME', 'College IT Helpdesk');
define('APP_URL', 'http://localhost/helpdesk');

// Database Connection
function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Database connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . APP_URL . "/login.php");
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: " . APP_URL . "/dashboard.php");
        exit();
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateTicketNumber() {
    return 'TKT-' . strtoupper(substr(md5(uniqid()), 0, 8));
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return $time . ' seconds ago';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    return floor($time/86400) . ' days ago';
}

function getStatusBadge($status) {
    $classes = [
        'Pending'     => 'badge-pending',
        'In Progress' => 'badge-progress',
        'Resolved'    => 'badge-resolved',
        'Closed'      => 'badge-closed',
    ];
    $class = $classes[$status] ?? 'badge-pending';
    return "<span class='badge $class'>$status</span>";
}

function getPriorityBadge($priority) {
    $classes = [
        'Low'      => 'priority-low',
        'Medium'   => 'priority-medium',
        'High'     => 'priority-high',
        'Critical' => 'priority-critical',
    ];
    $class = $classes[$priority] ?? 'priority-medium';
    return "<span class='priority-badge $class'>$priority</span>";
}
?>
