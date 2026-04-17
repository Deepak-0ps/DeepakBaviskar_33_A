<?php
require_once __DIR__ . '/../auth/session.php';
requireLogin();
$pageTitle = 'My Attendance';
$depth = '../';
$role = $_SESSION['role'];

// Student can only see their own; others can pass student_id
if ($role === 'student') {
    $studentId = $_SESSION['linked_id'] ?? 0;
} else {
    $studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
    if (!$studentId) { redirect('attendance/report.php'); }
}

$student = $conn->query("
    SELECT s.*, u.name, u.email, c.name as class_name, d.name as dept_name
    FROM students s JOIN users u ON s.user_id=u.id
    LEFT JOIN classes c ON s.class_id=c.id
    LEFT JOIN departments d ON s.department_id=d.id
    WHERE s.id=$studentId
")->fetch_assoc();

if (!$student) { flashMessage('error','Student not found'); redirect('attendance/report.php'); }

// Overall summary
$overall = $conn->query("
    SELECT COUNT(*) as total, SUM(status='present') as present, SUM(status='absent') as absent, SUM(status='late') as late
    FROM attendance WHERE student_id=$studentId
")->fetch_assoc();
$overallPct = $overall['total'] > 0 ? round($overall['present'] / $overall['total'] * 100, 1) : 0;

// Per subject
$bySubject = $conn->query("
    SELECT sub.name as subject_name, sub.code,
        COUNT(a.id) as total,
        SUM(a.status='present') as present,
        SUM(a.status='absent') as absent,
        SUM(a.status='late') as late,
        ROUND(SUM(a.status='present')/COUNT(a.id)*100,1) as pct
    FROM attendance a JOIN subjects sub ON a.subject_id=sub.id
    WHERE a.student_id=$studentId
    GROUP BY a.subject_id ORDER BY sub.name
");

// Day-wise last 30 days
$recent = $conn->query("
    SELECT a.date, a.status, sub.name as subject_name, sub.code
    FROM attendance a JOIN subjects sub ON a.subject_id=sub.id
    WHERE a.student_id=$studentId
    ORDER BY a.date DESC LIMIT 30
");

if ($role === 'student') {
    include __DIR__ . '/../includes/header.php';
    $initials = strtoupper(substr($_SESSION['name'],0,1));
    $currentFile = basename($_SERVER['PHP_SELF']);
    echo '<div class="sidebar">';
    echo '<div class="sidebar-brand"><div class="brand-icon"><svg viewBox="0 0 64 64" fill="var(--gold)"><path d="M32 4L8 16v16c0 14 10.7 26.4 24 29.3C45.3 58.4 56 46 56 32V16L32 4zm0 8l18 9v11c0 10.5-7.8 19.8-18 22.3C21.8 51.8 14 42.5 14 32V21l18-9z"/></svg></div><div class="brand-text"><strong>CMS Portal</strong><span>Student Panel</span></div></div>';
    echo '<div class="sidebar-user"><div class="user-avatar">'.$initials.'</div><div class="user-info"><strong>'.htmlspecialchars($_SESSION['name']).'</strong><span>Student</span></div></div>';
    echo '<nav class="sidebar-nav">';
    echo '<div class="nav-section-label">Overview</div>';
    echo '<a href="'.$depth.'dashboard/student.php" class="nav-item"><span class="nav-icon">🏠</span>Dashboard</a>';
    echo '<div class="nav-section-label">Academics</div>';
    echo '<a href="'.$depth.'attendance/student_report.php" class="nav-item active"><span class="nav-icon">📋</span>My Attendance</a>';
    echo '<a href="'.$depth.'tests/results.php" class="nav-item"><span class="nav-icon">📝</span>My Test Results</a>';
    echo '<div class="nav-section-label">Fees</div>';
    echo '<a href="'.$depth.'students/fees.php" class="nav-item"><span class="nav-icon">💰</span>My Fees</a>';
    echo '</nav><div class="sidebar-footer"><a href="'.$depth.'auth/logout.php" class="logout-btn">🚪 Sign Out</a></div></div>';
} else {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . "/../includes/sidebar_{$role}.php";
}
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">
      <h2>Attendance Report</h2>
      <p><?= htmlspecialchars($student['name']) ?> — <?= htmlspecialchars($student['roll_number']) ?></p>
    </div>
    <div class="topbar-right">
      <?php if($role !== 'student'): ?><a href="report.php" class="btn btn-outline">← Back</a><?php endif; ?>
      <button onclick="window.print()" class="btn btn-outline">🖨 Print</button>
    </div>
  </div>

  <div class="content">
    <!-- Summary Cards -->
    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
      <div class="stat-card" style="--accent:<?= $overallPct>=75?'#10b981':($overallPct>=60?'#f59e0b':'#ef4444') ?>;--icon-bg:#f9fafb">
        <div class="stat-icon">📊</div>
        <div class="stat-info">
          <div class="val" style="color:<?= $overallPct>=75?'#27ae60':($overallPct>=60?'#e67e22':'#e74c3c') ?>"><?= $overallPct ?>%</div>
          <div class="lbl">Overall Attendance</div>
          <div class="sub" style="color:<?= $overallPct>=75?'#27ae60':'#e74c3c' ?>"><?= $overallPct>=75?'✓ Adequate':'⚠ Below 75%' ?></div>
        </div>
      </div>
      <div class="stat-card" style="--accent:#3b82f6;--icon-bg:#eff6ff">
        <div class="stat-icon">📅</div>
        <div class="stat-info"><div class="val"><?= $overall['total'] ?></div><div class="lbl">Total Classes</div></div>
      </div>
      <div class="stat-card" style="--accent:#10b981;--icon-bg:#d1fae5">
        <div class="stat-icon">✅</div>
        <div class="stat-info"><div class="val"><?= $overall['present'] ?></div><div class="lbl">Present</div></div>
      </div>
      <div class="stat-card" style="--accent:#ef4444;--icon-bg:#fee2e2">
        <div class="stat-icon">❌</div>
        <div class="stat-info"><div class="val"><?= $overall['absent'] ?></div><div class="lbl">Absent</div></div>
      </div>
    </div>

    <div class="two-col">
      <!-- Subject-wise -->
      <div class="table-card">
        <div class="table-card-header"><h3>📚 Subject-wise Attendance</h3></div>
        <div style="padding:20px">
          <?php if($bySubject && $bySubject->num_rows > 0): while($s=$bySubject->fetch_assoc()): ?>
          <div style="margin-bottom:20px;padding:16px;background:#f9fafb;border-radius:10px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
              <div>
                <div style="font-weight:700;font-size:15px"><?= htmlspecialchars($s['subject_name']) ?></div>
                <div style="font-size:12px;color:#6b7280"><?= $s['code'] ?></div>
              </div>
              <div style="text-align:right">
                <div style="font-size:22px;font-weight:800;color:<?= $s['pct']>=75?'#27ae60':($s['pct']>=60?'#e67e22':'#e74c3c') ?>"><?= $s['pct'] ?>%</div>
                <?php if($s['pct'] < 75): ?>
                  <div style="font-size:11px;color:#e74c3c;font-weight:600">⚠ Low Attendance</div>
                <?php endif; ?>
              </div>
            </div>
            <div class="att-bar" style="height:10px;border-radius:5px">
              <div class="att-fill <?= $s['pct']>=75?'high':($s['pct']>=60?'mid':'low') ?>" style="width:<?= $s['pct'] ?>%"></div>
            </div>
            <div style="display:flex;gap:16px;margin-top:10px;font-size:13px">
              <span style="color:#27ae60">✅ Present: <strong><?= $s['present'] ?></strong></span>
              <span style="color:#e74c3c">❌ Absent: <strong><?= $s['absent'] ?></strong></span>
              <span style="color:#e67e22">🕐 Late: <strong><?= $s['late'] ?></strong></span>
              <span style="color:#6b7280">Total: <strong><?= $s['total'] ?></strong></span>
            </div>
          </div>
          <?php endwhile; else: ?>
            <p style="color:#6b7280;text-align:center;padding:20px">No attendance records found</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent Records -->
      <div class="table-card">
        <div class="table-card-header"><h3>📆 Recent Attendance (Last 30)</h3></div>
        <div class="table-responsive">
          <table>
            <thead><tr><th>Date</th><th>Subject</th><th>Status</th></tr></thead>
            <tbody>
            <?php if($recent && $recent->num_rows > 0): while($r=$recent->fetch_assoc()): ?>
              <tr>
                <td style="font-size:13px;font-weight:600"><?= date('D, d M Y', strtotime($r['date'])) ?></td>
                <td><span class="badge badge-blue"><?= $r['code'] ?></span></td>
                <td>
                  <?php if($r['status']==='present'): ?>
                    <span class="badge badge-green">✅ Present</span>
                  <?php elseif($r['status']==='absent'): ?>
                    <span class="badge badge-red">❌ Absent</span>
                  <?php else: ?>
                    <span class="badge badge-yellow">🕐 Late</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="3" style="text-align:center;color:#6b7280;padding:20px">No records</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
