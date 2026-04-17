<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director']);
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $t = $conn->query("SELECT user_id FROM teachers WHERE id=$id")->fetch_assoc();
    if ($t) {
        $conn->query("DELETE FROM users WHERE id={$t['user_id']}");
        flashMessage('success', 'Teacher deleted successfully.');
    } else {
        flashMessage('error', 'Teacher not found.');
    }
}
redirect('teachers/list.php');
?>
