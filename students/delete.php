<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director']);
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $s = $conn->query("SELECT user_id FROM students WHERE id=$id")->fetch_assoc();
    if ($s) {
        $conn->query("DELETE FROM users WHERE id={$s['user_id']}");
        flashMessage('success','Student deleted successfully.');
    } else {
        flashMessage('error','Student not found.');
    }
}
redirect('students/list.php');
?>
