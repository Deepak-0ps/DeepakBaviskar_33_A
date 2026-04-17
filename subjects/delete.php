<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director']);
$id = (int)($_GET['id'] ?? 0);
if ($id) { $conn->query("DELETE FROM subjects WHERE id=$id"); flashMessage('success','Subject deleted.'); }
redirect('subjects/list.php');
?>
