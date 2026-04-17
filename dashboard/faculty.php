<?php
require_once __DIR__ . '/../auth/session.php';
requireRole('faculty');
$pageTitle = 'Faculty Dashboard';
$depth = '../';

$teacherId = $_SESSION['linked_id'] ?? 0;

$mySubjects = $conn->query("SELECT s.*, cl.name as class_name FROM subjects s LEFT JOIN classes cl ON s.class_id=cl.id WHERE s.teacher_id=$teacherId");
$mySubjectIds = [];
$mySubjectsList = [];
if ($mySubjects) { while($r=$mySubjects->fetch_assoc()) { $mySubjectIds[]=$r['id']; $mySubjectsList[]=$r; } }
$subIdStr = implode(',', $mySubjectIds ?: [0]);

$myTests = $conn->query("SELECT ct.*, s.name as subject_name FROM class_tests ct JOIN subjects s ON ct.subject_id=s.id WHERE ct.teacher_id=$teacherId ORDER BY ct.test_date DESC LIMIT 5");

$todayDate = date('Y-m-d');
$todayAtt = $conn->query("SELECT COUNT(DISTINCT student_id) as c FROM attendance WHERE subject_id IN ($subIdStr) AND date='$todayDate' AND status='present'")->fetch_assoc()['c'];
$totalTests = $conn->query("SELECT COUNT(*) as c FROM class_tests WHERE teacher_id=$teacherId")->fetch_assoc()['c'];
$totalSubs = count($mySubjectIds);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_faculty.php';
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">
      <h2>Faculty Dashboard</h2>
      <p>Welcome, <?= htmlspecialchars($_SESSION['name']) ?> · <?= date('D, d M Y') ?></p>
    </div>
    <div class="topbar-right">
      <a href="../attendance/take.php" class="btn btn-gold">✅ Take Attendance</a>
    </div>
  </div>

  <div class="content">
    <?php showFlash(); ?>

    <div class="stats-grid">
      <div class="stat-card" style="--accent:#3b82f6;--icon-bg:#eff6ff">
        <div class="stat-icon">📚</div>
        <div class="stat-info"><div class="val"><?= $totalSubs ?></div><div class="lbl">My Subjects</div></div>
      </div>
      <div class="stat-card" style="--accent:#10b981;--icon-bg:#d1fae5">
        <div class="stat-icon">✅</div>
        <div class="stat-info"><div class="val"><?= $todayAtt ?></div><div class="lbl">Present Today</div><div class="sub">In my classes</div></div>
      </div>
      <div class="stat-card" style="--accent:#f59e0b;--icon-bg:#fef3c7">
        <div class="stat-icon">📝</div>
        <div class="stat-info"><div class="val"><?= $totalTests ?></div><div class="lbl">Tests Created</div></div>
      </div>
    </div>

    <div class="two-col">
      <!-- My Subjects -->
      <div class="table-card">
        <div class="table-card-header">
          <h3>📚 My Subjects</h3>
        </div>
        <div class="table-responsive">
          <table>
            <thead><tr><th>Subject</th><th>Code</th><th>Class</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($mySubjectsList as $sub): ?>
              <tr>
                <td style="font-weight:600"><?= htmlspecialchars($sub['name']) ?></td>
                <td><span class="badge badge-blue"><?= $sub['code'] ?></span></td>
                <td><?= htmlspecialchars($sub['class_name'] ?? '—') ?></td>
                <td><a href="../attendance/take.php?subject_id=<?= $sub['id'] ?>" class="btn btn-sm btn-green">Take Att.</a></td>
              </tr>
            <?php endforeach; ?>
            <?php if(empty($mySubjectsList)): ?>
              <tr><td colspan="4" style="text-align:center;color:#6b7280;padding:20px">No subjects assigned yet</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- My Recent Tests -->
      <div class="table-card">
        <div class="table-card-header">
          <h3>📝 My Recent Tests</h3>
          <a href="../tests/add.php" class="btn btn-sm btn-primary">+ Add Test</a>
        </div>
        <div class="table-responsive">
          <table>
            <thead><tr><th>Title</th><th>Subject</th><th>Date</th><th>Marks</th></tr></thead>
            <tbody>
            <?php if($myTests && $myTests->num_rows>0): while($t=$myTests->fetch_assoc()): ?>
              <tr>
                <td style="font-weight:600"><?= htmlspecialchars($t['title']) ?></td>
                <td><?= htmlspecialchars($t['subject_name']) ?></td>
                <td><?= date('d M Y', strtotime($t['test_date'])) ?></td>
                <td><?= $t['total_marks'] ?></td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="4" style="text-align:center;color:#6b7280;padding:20px">No tests created yet</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
