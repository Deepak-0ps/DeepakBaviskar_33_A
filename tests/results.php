<?php
require_once __DIR__ . '/../auth/session.php';
requireLogin();
$pageTitle = 'Test Results';
$depth = '../';
$role = $_SESSION['role'];
$studentId = ($role === 'student') ? ($_SESSION['linked_id'] ?? 0) : 0;

$testId = isset($_GET['test_id']) ? (int)$_GET['test_id'] : 0;

// Tests dropdown
if ($role === 'student') {
    $myClassId = $conn->query("SELECT class_id FROM students WHERE id=$studentId")->fetch_assoc()['class_id'] ?? 0;
    $tests = $conn->query("SELECT ct.*, s.name as subject_name FROM class_tests ct JOIN subjects s ON ct.subject_id=s.id WHERE ct.class_id=$myClassId ORDER BY ct.test_date DESC");
} elseif ($role === 'faculty') {
    $teacherId = $_SESSION['linked_id'] ?? 0;
    $tests = $conn->query("SELECT ct.*, s.name as subject_name FROM class_tests ct JOIN subjects s ON ct.subject_id=s.id WHERE ct.teacher_id=$teacherId ORDER BY ct.test_date DESC");
} else {
    $tests = $conn->query("SELECT ct.*, s.name as subject_name FROM class_tests ct JOIN subjects s ON ct.subject_id=s.id ORDER BY ct.test_date DESC");
}

$testInfo = null;
$results  = null;
$stats    = [];

if ($testId) {
    $testInfo = $conn->query("
        SELECT ct.*, s.name as subject_name, s.code, c.name as class_name, u.name as teacher_name
        FROM class_tests ct
        JOIN subjects s ON ct.subject_id=s.id
        JOIN classes c ON ct.class_id=c.id
        JOIN teachers t ON ct.teacher_id=t.id
        JOIN users u ON t.user_id=u.id
        WHERE ct.id=$testId
    ")->fetch_assoc();

    if ($role === 'student') {
        $results = $conn->query("
            SELECT tr.*, u.name as student_name, st.roll_number
            FROM test_results tr
            JOIN students st ON tr.student_id=st.id
            JOIN users u ON st.user_id=u.id
            WHERE tr.test_id=$testId AND tr.student_id=$studentId
        ");
    } else {
        $results = $conn->query("
            SELECT tr.*, u.name as student_name, st.roll_number
            FROM test_results tr
            JOIN students st ON tr.student_id=st.id
            JOIN users u ON st.user_id=u.id
            WHERE tr.test_id=$testId
            ORDER BY tr.marks_obtained DESC
        ");
    }

    // Stats
    $statsRow = $conn->query("
        SELECT COUNT(*) as total, AVG(marks_obtained) as avg_marks,
               MAX(marks_obtained) as max_marks, MIN(marks_obtained) as min_marks
        FROM test_results WHERE test_id=$testId
    ")->fetch_assoc();
    $stats = $statsRow;
}

include __DIR__ . '/../includes/header.php';
if ($role === 'student') {
    $initials = strtoupper(substr($_SESSION['name'],0,1));
    echo '<div class="sidebar">
    <div class="sidebar-brand"><div class="brand-icon"><svg viewBox="0 0 64 64" fill="var(--gold)"><path d="M32 4L8 16v16c0 14 10.7 26.4 24 29.3C45.3 58.4 56 46 56 32V16L32 4zm0 8l18 9v11c0 10.5-7.8 19.8-18 22.3C21.8 51.8 14 42.5 14 32V21l18-9z"/></svg></div><div class="brand-text"><strong>CMS Portal</strong><span>Student Panel</span></div></div>
    <div class="sidebar-user"><div class="user-avatar">'.$initials.'</div><div class="user-info"><strong>'.htmlspecialchars($_SESSION['name']).'</strong><span>Student</span></div></div>
    <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <a href="'.$depth.'dashboard/student.php" class="nav-item"><span class="nav-icon">🏠</span>Dashboard</a>
    <div class="nav-section-label">Academics</div>
    <a href="'.$depth.'attendance/student_report.php" class="nav-item"><span class="nav-icon">📋</span>My Attendance</a>
    <a href="'.$depth.'tests/results.php" class="nav-item active"><span class="nav-icon">📝</span>My Test Results</a>
    <div class="nav-section-label">Fees</div>
    <a href="'.$depth.'students/fees.php" class="nav-item"><span class="nav-icon">💰</span>My Fees</a>
    </nav>
    <div class="sidebar-footer"><a href="'.$depth.'auth/logout.php" class="logout-btn">🚪 Sign Out</a></div>
    </div>';
} else {
    include __DIR__ . "/../includes/sidebar_{$role}.php";
}
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">
      <h2>Test Results</h2>
      <p><?= $testInfo ? htmlspecialchars($testInfo['title']) : 'Select a test to view results' ?></p>
    </div>
    <div class="topbar-right">
      <?php if($testId && hasRole(['faculty','coordinator','director'])): ?>
      <a href="submit.php?test_id=<?= $testId ?>" class="btn btn-gold">✏️ Edit Marks</a>
      <button onclick="window.print()" class="btn btn-outline">🖨 Print</button>
      <?php endif; ?>
    </div>
  </div>
  <div class="content">
    <?php showFlash(); ?>

    <!-- Test Selector -->
    <div class="form-card" style="margin-bottom:24px">
      <form method="GET" style="display:flex;gap:16px;align-items:flex-end">
        <div class="form-group" style="flex:1">
          <label>Select Test</label>
          <select name="test_id" onchange="this.form.submit()" style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;width:100%;font-size:14px;outline:none">
            <option value="">— Select a Test —</option>
            <?php $tests->data_seek(0); while($t=$tests->fetch_assoc()): ?>
            <option value="<?= $t['id'] ?>" <?= $testId==$t['id']?'selected':'' ?>>
              <?= htmlspecialchars($t['title']) ?> — <?= htmlspecialchars($t['subject_name']) ?> (<?= date('d M Y', strtotime($t['test_date'])) ?>)
            </option>
            <?php endwhile; ?>
          </select>
        </div>
      </form>
    </div>

    <?php if($testId && $testInfo): ?>

    <!-- Stats Cards -->
    <?php if(!empty($stats) && $stats['total'] > 0): ?>
    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
      <div class="stat-card" style="--accent:#3b82f6;--icon-bg:#eff6ff">
        <div class="stat-icon">👨‍🎓</div>
        <div class="stat-info"><div class="val"><?= $stats['total'] ?></div><div class="lbl">Submitted</div></div>
      </div>
      <div class="stat-card" style="--accent:#10b981;--icon-bg:#d1fae5">
        <div class="stat-icon">📈</div>
        <div class="stat-info">
          <div class="val"><?= round($stats['avg_marks'],1) ?></div>
          <div class="lbl">Average Marks</div>
          <div class="sub"><?= $testInfo['total_marks'] > 0 ? round($stats['avg_marks']/$testInfo['total_marks']*100) : 0 ?>%</div>
        </div>
      </div>
      <div class="stat-card" style="--accent:#f59e0b;--icon-bg:#fef3c7">
        <div class="stat-icon">🏆</div>
        <div class="stat-info">
          <div class="val"><?= $stats['max_marks'] ?></div>
          <div class="lbl">Highest</div>
          <div class="sub"><?= $testInfo['total_marks'] > 0 ? round($stats['max_marks']/$testInfo['total_marks']*100) : 0 ?>%</div>
        </div>
      </div>
      <div class="stat-card" style="--accent:#ef4444;--icon-bg:#fee2e2">
        <div class="stat-icon">📉</div>
        <div class="stat-info">
          <div class="val"><?= $stats['min_marks'] ?></div>
          <div class="lbl">Lowest</div>
          <div class="sub"><?= $testInfo['total_marks'] > 0 ? round($stats['min_marks']/$testInfo['total_marks']*100) : 0 ?>%</div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Results Table -->
    <div class="table-card">
      <div class="table-card-header">
        <h3>📊 Results: <?= htmlspecialchars($testInfo['title']) ?></h3>
        <div style="display:flex;gap:10px;align-items:center">
          <span class="badge badge-blue"><?= htmlspecialchars($testInfo['subject_name']) ?></span>
          <span class="badge badge-purple"><?= htmlspecialchars($testInfo['class_name']) ?></span>
          <span style="font-size:13px;color:#6b7280">Out of <?= $testInfo['total_marks'] ?></span>
        </div>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <?php if($role !== 'student'): ?><th>Rank</th><th>Roll No</th><th>Student</th><?php endif; ?>
              <th>Marks</th><th>Percentage</th><th>Grade</th><th>Performance</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $rank = 1;
          if ($results && $results->num_rows > 0): while($r=$results->fetch_assoc()):
            $pct = $testInfo['total_marks'] > 0 ? round($r['marks_obtained']/$testInfo['total_marks']*100) : 0;
            $grade = $pct>=90?'O':($pct>=75?'A+':($pct>=60?'A':($pct>=50?'B':($pct>=40?'C':'F'))));
            $gradeCol = $pct>=75?'badge-green':($pct>=50?'badge-yellow':'badge-red');
          ?>
            <tr>
              <?php if($role !== 'student'): ?>
              <td>
                <?php if($rank===1): ?><span style="font-size:20px">🥇</span>
                <?php elseif($rank===2): ?><span style="font-size:20px">🥈</span>
                <?php elseif($rank===3): ?><span style="font-size:20px">🥉</span>
                <?php else: ?><span style="font-weight:700;color:#6b7280">#<?= $rank ?></span>
                <?php endif; ?>
              </td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($r['roll_number']) ?></span></td>
              <td style="font-weight:600"><?= htmlspecialchars($r['student_name']) ?></td>
              <?php endif; ?>
              <td>
                <span style="font-size:20px;font-weight:800;color:<?= $pct>=75?'#27ae60':($pct>=40?'#e67e22':'#e74c3c') ?>">
                  <?= $r['marks_obtained'] ?>
                </span>
                <span style="font-size:13px;color:#6b7280"> / <?= $testInfo['total_marks'] ?></span>
              </td>
              <td style="font-weight:700;color:<?= $pct>=75?'#27ae60':($pct>=40?'#e67e22':'#e74c3c') ?>"><?= $pct ?>%</td>
              <td><span class="badge <?= $gradeCol ?>" style="font-size:14px;font-weight:800"><?= $grade ?></span></td>
              <td style="min-width:150px">
                <div class="att-bar">
                  <div class="att-fill <?= $pct>=75?'high':($pct>=40?'mid':'low') ?>" style="width:<?= $pct ?>%"></div>
                </div>
              </td>
            </tr>
          <?php $rank++; endwhile; else: ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:#6b7280">
              No marks entered yet. 
              <?php if(hasRole(['faculty','coordinator','director'])): ?>
                <a href="submit.php?test_id=<?= $testId ?>" style="color:var(--gold)">Enter marks now →</a>
              <?php endif; ?>
            </td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Grade Legend -->
    <div style="background:#fff;border-radius:12px;padding:20px 24px;border:1px solid #e5e7eb;margin-top:16px">
      <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:12px">📊 Grading Scale</div>
      <div style="display:flex;gap:16px;flex-wrap:wrap">
        <?php foreach([
          ['O','90–100%','badge-green'],['A+','75–89%','badge-green'],['A','60–74%','badge-blue'],
          ['B','50–59%','badge-yellow'],['C','40–49%','badge-yellow'],['F','Below 40%','badge-red']
        ] as $g): ?>
        <div style="display:flex;align-items:center;gap:8px">
          <span class="badge <?= $g[2] ?>" style="font-size:14px;font-weight:800"><?= $g[0] ?></span>
          <span style="font-size:13px;color:#6b7280"><?= $g[1] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
