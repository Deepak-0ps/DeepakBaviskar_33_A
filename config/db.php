<?php
// config/db.php
define('DB_HOST', '127.0.0.1:3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'college_management');
define('SITE_NAME', 'College Management System');
define('BASE_URL', 'http://localhost/college_management/');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("<div style='font-family:sans-serif;padding:40px;text-align:center;'>
        <h2 style='color:#e74c3c'>Database Connection Failed</h2>
        <p>Please make sure XAMPP is running and the database is set up.</p>
        <p><strong>Error:</strong> " . $conn->connect_error . "</p>
        <p>Import <code>database/college_db.sql</code> into phpMyAdmin first.</p>
    </div>");
}

$conn->set_charset("utf8");

// Helper functions
function sanitize($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    if (!isset($_SESSION['role'])) return false;
    if (is_array($role)) return in_array($_SESSION['role'], $role);
    return $_SESSION['role'] === $role;
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('auth/login.php');
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        redirect('dashboard/' . $_SESSION['role'] . '.php');
    }
}

function flashMessage($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function showFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        $icon = $f['type'] === 'success' ? '✓' : ($f['type'] === 'error' ? '✗' : 'ℹ');
        echo "<div class='alert alert-{$f['type']}'><span>{$icon}</span> {$f['msg']}</div>";
        unset($_SESSION['flash']);
    }
}
?>
