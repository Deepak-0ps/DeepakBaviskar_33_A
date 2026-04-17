<?php // includes/sidebar_director.php
$depth = isset($depth) ? $depth : '../';
$initials = strtoupper(substr($_SESSION['name'],0,1));
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
function navItem($depth,$path,$icon,$label,$dir,$file,$currentDir,$currentFile){
    $active = ($currentDir===$dir && $currentFile===$file) ? 'active' : '';
    echo "<a href='{$depth}{$path}' class='nav-item {$active}'><span class='nav-icon'>{$icon}</span>{$label}</a>";
}
?>
<div class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">
      <svg viewBox="0 0 64 64"><path d="M32 4L8 16v16c0 14 10.7 26.4 24 29.3C45.3 58.4 56 46 56 32V16L32 4zm0 8l18 9v11c0 10.5-7.8 19.8-18 22.3C21.8 51.8 14 42.5 14 32V21l18-9z"/></svg>
    </div>
    <div class="brand-text">
      <strong>CMS Portal</strong>
      <span>Director Panel</span>
    </div>
  </div>
  <div class="sidebar-user">
    <div class="user-avatar"><?= $initials ?></div>
    <div class="user-info">
      <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>
      <span>Director</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <?php navItem($depth,'dashboard/director.php','🏠','Dashboard','dashboard','director.php',$currentDir,$currentFile); ?>

    <div class="nav-section-label">Management</div>
    <?php navItem($depth,'students/list.php','👨‍🎓','Students','students','list.php',$currentDir,$currentFile); ?>
    <?php navItem($depth,'teachers/list.php','👨‍🏫','Teachers','teachers','list.php',$currentDir,$currentFile); ?>
    <?php navItem($depth,'subjects/list.php','📚','Subjects','subjects','list.php',$currentDir,$currentFile); ?>

    <div class="nav-section-label">Academics</div>
    <?php navItem($depth,'attendance/report.php','📋','Attendance Report','attendance','report.php',$currentDir,$currentFile); ?>
    <?php navItem($depth,'tests/list.php','📝','Class Tests','tests','list.php',$currentDir,$currentFile); ?>
    <?php navItem($depth,'students/fees.php','💰','Fee Management','students','fees.php',$currentDir,$currentFile); ?>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= $depth ?>auth/logout.php" class="logout-btn">🚪 Sign Out</a>
  </div>
</div>
