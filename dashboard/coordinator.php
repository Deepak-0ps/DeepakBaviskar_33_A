<?php
require_once __DIR__ . '/../auth/session.php';
requireRole('coordinator');
$pageTitle = 'Coordinator Dashboard';
$depth = '../';

$totalStudents = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
$totalTeachers = $conn->query("SELECT COUNT(*) as c FROM teachers")->fetch_assoc()['c'];
$totalSubjects = $conn->query("SELECT COUNT(*) as c FROM subjects")->fetch_assoc()['c'];
$totalTests    = $conn->query("SELECT COUNT(*) as c FROM class_tests")->fetch_assoc()['c'];

$todayDate = date('Y-m-d');
$attendanceToday = $conn->query("SELECT status, COUNT(*) as c FROM attendance WHERE date='$todayDate' GROUP BY status");
$attStats = ['present'=>0,'absent'=>0,'late'=>0];
while($r=$attendanceToday->fetch_assoc()) $attStats[$r['status']] = $r['c'];

$recentTests = $conn->query("
    SELECT ct.*, s.name as subject_name, u.name as teacher_name
    FROM class_tests ct
    JOIN subjects s ON ct.subject_id=s.id
    JOIN teachers t ON ct.teacher_id=t.id
    JOIN users u ON t.user_id=u.id
    ORDER BY ct.created_at DESC LIMIT 5
");

$subjectAttendance = $conn->query("
    SELECT sub.name as subject_name,
        COUNT(a.id) as total_classes,
        SUM(a.status='present') as present_count,
        ROUND(SUM(a.status='present')/COUNT(a.id)*100,1) as pct
    FROM attendance a
    JOIN subjects sub ON a.subject_id=sub.id
    GROUP BY a.subject_id
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_coordinator.php';
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">
      <h2>Coordinator Dashboard</h2>
      <p>Department management overview for <?= date('D, d M Y') ?></p>
    </div>
  </div>

  <div class="content">
    <?php showFlash(); ?>

    <div class="stats-grid">
      <div class="stat-card" style="--accent:#3b82f6;--icon-bg:#eff6ff">
        <div class="stat-icon">👨‍🎓</div>
        <div class="stat-info"><div class="val"><?= $totalStudents ?></div><div class="lbl">Students</div></div>
      </div>
      <div class="stat-card" style="--accent:#8b5cf6;--icon-bg:#ede9fe">
        <div class="stat-icon">👨‍🏫</div>
        <div class="stat-info"><div class="val"><?= $totalTeachers ?></div><div class="lbl">Faculty</div></div>
      </div>
      <div class="stat-card" style="--accent:#10b981;--icon-bg:#d1fae5">
        <div class="stat-icon">✅</div>
        <div class="stat-info"><div class="val"><?= $attStats['present'] ?></div><div class="lbl">Present Today</div></div>
      </div>
      <div class="stat-card" style="--accent:#f59e0b;--icon-bg:#fef3c7">
        <div class="stat-icon">📝</div>
        <div class="stat-info"><div class="val"><?= $totalTests ?></div><div class="lbl">Tests Created</div></div>
      </div>
    </div>

    <div class="two-col">
      <!-- Subject-wise Attendance -->
      <div class="table-card">
        <div class="table-card-header">
          <h3>📚 Subject-wise Attendance</h3>
        </div>
        <div style="padding:20px">
          <?php while($r=$subjectAttendance->fetch_assoc()): ?>
            <div style="margin-bottom:16px">
              <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:6px">
                <span style="font-weight:600"><?= htmlspecialchars($r['subject_name']) ?></span>
                <span style="font-weight:700;color:<?= $r['pct']>=75?'#27ae60':($r['pct']>=60?'#e67e22':'#e74c3c') ?>"><?= $r['pct'] ?>%</span>
              </div>
              <div class="att-bar">
                <div class="att-fill <?= $r['pct']>=75?'high':($r['pct']>=60?'mid':'low') ?>" style="width:<?= $r['pct'] ?>%"></div>
              </div>
              <div style="font-size:12px;color:#6b7280;margin-top:4px"><?= $r['present_count'] ?>/<?= $r['total_classes'] ?> classes present</div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

      <!-- Recent Tests -->
      <div class="table-card">
        <div class="table-card-header">
          <h3>📝 Recent Tests</h3>
          <a href="../tests/list.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="table-responsive">
          <table>
            <thead><tr><th>Title</th><th>Subject</th><th>Date</th><th>Marks</th></tr></thead>
            <tbody>
            <?php while($r=$recentTests->fetch_assoc()): ?>
              <tr>
                <td style="font-weight:600"><?= htmlspecialchars($r['title']) ?></td>
                <td><span class="badge badge-blue"><?= htmlspecialchars($r['subject_name']) ?></span></td>
                <td style="font-size:13px"><?= date('d M', strtotime($r['test_date'])) ?></td>
                <td><?= $r['total_marks'] ?></td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
