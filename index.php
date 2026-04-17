<?php
// index.php — root redirect
session_start();
if (isset($_SESSION['role'])) {
    header("Location: dashboard/" . $_SESSION['role'] . ".php");
} else {
    header("Location: auth/login.php");
}
exit();
?>
