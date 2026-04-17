<?php // includes/sidebar_faculty.php
$depth = isset($depth) ? $depth : '../';
$initials = strtoupper(substr($_SESSION['name'],0,1));
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
function navItemF($depth,$path,$icon,$label,$dir,$file,$currentDir,$currentFile){
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
      <span>Faculty Panel</span>
    </div>
  </div>
  <div class="sidebar-user">
    <div class="user-avatar"><?= $initials ?></div>
    <div class="user-info">
      <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>
      <span>Faculty</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <?php navItemF($depth,'dashboard/faculty.php','🏠','Dashboard','dashboard','faculty.php',$currentDir,$currentFile); ?>

    <div class="nav-section-label">Attendance</div>
    <?php navItemF($depth,'attendance/take.php','✅','Take Attendance','attendance','take.php',$currentDir,$currentFile); ?>
    <?php navItemF($depth,'attendance/view.php','📋','View Attendance','attendance','view.php',$currentDir,$currentFile); ?>

    <div class="nav-section-label">Tests</div>
    <?php navItemF($depth,'tests/list.php','📝','Class Tests','tests','list.php',$currentDir,$currentFile); ?>
    <?php navItemF($depth,'tests/add.php','➕','Add Test','tests','add.php',$currentDir,$currentFile); ?>
    <?php navItemF($depth,'tests/results.php','📊','Test Results','tests','results.php',$currentDir,$currentFile); ?>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= $depth ?>auth/logout.php" class="logout-btn">🚪 Sign Out</a>
  </div>
</div>
