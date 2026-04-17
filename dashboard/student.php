<?php
require_once __DIR__ . '/../auth/session.php';
requireRole('student');
$pageTitle = 'Student Dashboard';
$depth = '../';

$studentId = $_SESSION['linked_id'] ?? 0;

// Student info
$student = $conn->query("
    SELECT s.*, u.name, u.email, u.phone,
           c.name as class_name, d.name as dept_name
    FROM students s
    JOIN users u ON s.user_id=u.id
    LEFT JOIN classes c ON s.class_id=c.id
    LEFT JOIN departments d ON s.department_id=d.id
    WHERE s.id=$studentId
")->fetch_assoc();

// Attendance per subject
$attSummary = $conn->query("
    SELECT sub.name as subject_name, sub.code,
        COUNT(a.id) as total,
        SUM(a.status='present') as present,
        ROUND(SUM(a.status='present')/COUNT(a.id)*100,1) as pct
    FROM attendance a
    JOIN subjects sub ON a.subject_id=sub.id
    WHERE a.student_id=$studentId
    GROUP BY a.subject_id
");

// Overall attendance
$overall = $conn->query("
    SELECT COUNT(*) as total, SUM(status='present') as present
    FROM attendance WHERE student_id=$studentId
")->fetch_assoc();
$overallPct = ($overall['total'] > 0) ? round($overall['present'] / $overall['total'] * 100, 1) : 0;

// Test results
$results = $conn->query("
    SELECT tr.*, ct.title, ct.total_marks, ct.test_date, sub.name as subject_name
    FROM test_results tr
    JOIN class_tests ct ON tr.test_id=ct.id
    JOIN subjects sub ON ct.subject_id=sub.id
    WHERE tr.student_id=$studentId
    ORDER BY ct.test_date DESC
    LIMIT 5
");

// Fees
$feesDue = $student ? ($student['total_fees'] - $student['fees_paid']) : 0;

// Notices
$notices = $conn->query("
    SELECT n.*, u.name as posted_by_name FROM notices n
    JOIN users u ON n.posted_by=u.id
    WHERE n.target_role IN ('all','student')
    ORDER BY n.created_at DESC LIMIT 3
");

include __DIR__ . '/../includes/header.php';
?>
<!– Student Sidebar inline –>
<?php
$initials = strtoupper(substr($_SESSION['name'],0,1));
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<div class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">
      <svg viewBox="0 0 64 64" fill="var(--gold)"><path d="M32 4L8 16v16c0 14 10.7 26.4 24 29.3C45.3 58.4 56 46 56 32V16L32 4zm0 8l18 9v11c0 10.5-7.8 19.8-18 22.3C21.8 51.8 14 42.5 14 32V21l18-9z"/></svg>
    </div>
    <div class="brand-text">
      <strong>CMS Portal</strong>
      <span>Student Panel</span>
    </div>
  </div>
  <div class="sidebar-user">
    <div class="user-avatar"><?= $initials ?></div>
    <div class="user-info">
      <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>
      <span>Student</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <a href="<?= $depth ?>dashboard/student.php" class="nav-item <?= $currentFile==='student.php'?'active':'' ?>"><span class="nav-icon">🏠</span>Dashboard</a>
    <div class="nav-section-label">Academics</div>
    <a href="<?= $depth ?>attendance/student_report.php" class="nav-item <?= $currentFile==='student_report.php'?'active':'' ?>"><span class="nav-icon">📋</span>My Attendance</a>
    <a href="<?= $depth ?>tests/results.php" class="nav-item <?= $currentFile==='results.php'?'active':'' ?>"><span class="nav-icon">📝</span>My Test Results</a>
    <div class="nav-section-label">Fees</div>
    <a href="<?= $depth ?>students/fees.php" class="nav-item <?= $currentFile==='fees.php'?'active':'' ?>"><span class="nav-icon">💰</span>My Fees</a>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= $depth ?>auth/logout.php" class="logout-btn">🚪 Sign Out</a>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">
      <h2>Student Dashboard</h2>
      <p>Welcome, <?= htmlspecialchars($_SESSION['name']) ?> · <?= date('D, d M Y') ?></p>
    </div>
  </div>

  <div class="content">
    <?php showFlash(); ?>

    <!-- STAT CARDS -->
    <div class="stats-grid">
      <div class="stat-card" style="--accent:#3b82f6;--icon-bg:#eff6ff">
        <div class="stat-icon">📋</div>
        <div class="stat-info">
          <div class="val"><?= $overallPct ?>%</div>
          <div class="lbl">Overall Attendance</div>
          <div class="sub" style="color:<?= $overallPct>=75?'#27ae60':'#e74c3c' ?>">
            <?= $overallPct>=75 ? '✓ Adequate' : '⚠ Below 75%' ?>
          </div>
        </div>
      </div>
      <div class="stat-card" style="--accent:#10b981;--icon-bg:#d1fae5">
        <div class="stat-icon">📚</div>
        <div class="stat-info">
          <div class="val"><?= htmlspecialchars($student['class_name'] ?? '—') ?></div>
          <div class="lbl">My Class</div>
          <div class="sub"><?= htmlspecialchars($student['dept_name'] ?? '') ?></div>
        </div>
      </div>
      <div class="stat-card" style="--accent:#f59e0b;--icon-bg:#fef3c7">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
          <div class="val">₹<?= number_format($student['fees_paid'] ?? 0) ?></div>
          <div class="lbl">Fees Paid</div>
          <div class="sub" style="color:<?= $feesDue>0?'#e74c3c':'#27ae60' ?>">
            <?= $feesDue>0 ? '₹'.number_format($feesDue).' due' : '✓ Fully Paid' ?>
          </div>
        </div>
      </div>
      <div class="stat-card" style="--accent:#8b5cf6;--icon-bg:#ede9fe">
        <div class="stat-icon">🎓</div>
        <div class="stat-info">
          <div class="val"><?= htmlspecialchars($student['roll_number'] ?? '—') ?></div>
          <div class="lbl">Roll Number</div>
          <div class="sub">Year <?= $student['admission_year'] ?? '' ?></div>
        </div>
      </div>
    </div>

    <div class="two-col">
      <!-- Attendance Subject-wise -->
      <div class="table-card">
        <div class="table-card-header">
          <h3>📋 Attendance by Subject</h3>
          <a href="../attendance/student_report.php" class="btn btn-sm btn-outline">Full Report</a>
        </div>
        <div style="padding:20px">
          <?php if($attSummary && $attSummary->num_rows > 0): while($a = $attSummary->fetch_assoc()): ?>
          <div style="margin-bottom:18px">
            <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:6px">
              <div>
                <span style="font-weight:600"><?= htmlspecialchars($a['subject_name']) ?></span>
                <span style="font-size:12px;color:#6b7280;margin-left:6px">(<?= $a['code'] ?>)</span>
              </div>
              <span style="font-weight:700;color:<?= $a['pct']>=75?'#27ae60':($a['pct']>=60?'#e67e22':'#e74c3c') ?>">
                <?= $a['pct'] ?>%
              </span>
            </div>
            <div class="att-bar">
              <div class="att-fill <?= $a['pct']>=75?'high':($a['pct']>=60?'mid':'low') ?>" style="width:<?= $a['pct'] ?>%"></div>
            </div>
            <div style="font-size:12px;color:#6b7280;margin-top:4px">
              <?= $a['present'] ?> present / <?= $a['total'] ?> classes
              <?php if($a['pct'] < 75): ?>
                <span style="color:#e74c3c;font-weight:600"> · ⚠ Low Attendance</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endwhile; else: ?>
            <p style="color:#6b7280;text-align:center;padding:20px">No attendance records yet</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent Test Results -->
      <div class="table-card">
        <div class="table-card-header">
          <h3>📝 Recent Test Results</h3>
          <a href="../tests/results.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="table-responsive">
          <table>
            <thead><tr><th>Test</th><th>Subject</th><th>Score</th><th>Date</th></tr></thead>
            <tbody>
            <?php if($results && $results->num_rows > 0): while($r = $results->fetch_assoc()):
              $pct = $r['total_marks'] > 0 ? round($r['marks_obtained'] / $r['total_marks'] * 100) : 0;
            ?>
              <tr>
                <td style="font-weight:600;font-size:13px"><?= htmlspecialchars($r['title']) ?></td>
                <td style="font-size:13px;color:#6b7280"><?= htmlspecialchars($r['subject_name']) ?></td>
                <td>
                  <span style="font-weight:700;color:<?= $pct>=75?'#27ae60':($pct>=40?'#e67e22':'#e74c3c') ?>">
                    <?= $r['marks_obtained'] ?>/<?= $r['total_marks'] ?>
                  </span>
                  <span class="badge <?= $pct>=75?'badge-green':($pct>=40?'badge-yellow':'badge-red') ?>" style="margin-left:6px"><?= $pct ?>%</span>
                </td>
                <td style="font-size:13px"><?= date('d M Y', strtotime($r['test_date'])) ?></td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="4" style="text-align:center;color:#6b7280;padding:20px">No test results yet</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Notices -->
    <div style="margin-top:4px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <h3 style="font-size:16px;font-weight:700">📢 Latest Notices</h3>
      </div>
      <?php if($notices && $notices->num_rows > 0): while($n = $notices->fetch_assoc()): ?>
        <div class="notice-card">
          <h4><?= htmlspecialchars($n['title']) ?></h4>
          <p><?= htmlspecialchars($n['content']) ?></p>
          <div class="notice-meta">Posted by <?= htmlspecialchars($n['posted_by_name']) ?> · <?= date('d M Y', strtotime($n['created_at'])) ?></div>
        </div>
      <?php endwhile; else: ?>
        <div class="notice-card"><p style="color:#6b7280">No notices at the moment.</p></div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
