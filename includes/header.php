<?php
// includes/header.php
$depth = isset($depth) ? $depth : '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= isset($pageTitle) ? $pageTitle . ' — ' : '' ?><?= SITE_NAME ?></title>
<link rel="stylesheet" href="<?= $depth ?>assets/css/style.css">
</head>
<body>
