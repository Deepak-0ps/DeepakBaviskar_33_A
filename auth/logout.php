<?php
require_once __DIR__ . '/../auth/session.php';
session_destroy();
header("Location: ../auth/login.php");
exit();
?>
