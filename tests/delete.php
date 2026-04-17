<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director','coordinator']);
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $conn->query("DELETE FROM test_results WHERE test_id=$id");
    $conn->query("DELETE FROM class_tests WHERE id=$id");
    flashMessage('success', 'Test and all its results deleted.');
} else {
    flashMessage('error', 'Invalid test.');
}
redirect('tests/list.php');
?>
