<?php
require_once __DIR__ . '/../auth/session.php';
requireLogin();
$pageTitle = 'Class Tests';
$depth = '../';
$role = $_SESSION['role'];
$teacherId = $_SESSION['linked_id'] ?? 0;

if (hasRole(['director','coordinator'])) {
    $tests = $conn->query("
        SELECT ct.*, s.name as subject_name, s.code, c.name as class_name, u.name as teacher_name
        FROM class_tests ct
        JOIN subjects s ON ct.subject_id=s.id
        JOIN classes c ON ct.class_id=c.id
        JOIN teachers t ON ct.teacher_id=t.id
        JOIN users u ON t.user_id=u.id
        ORDER BY ct.test_date DESC
    ");
} elseif ($role === 'faculty') {
    $tests = $conn->query("
        SELECT ct.*, s.name as subject_name, s.code, c.name as class_name, u.name as teacher_name
        FROM class_tests ct
        JOIN subjects s ON ct.subject_id=s.id
        JOIN classes c ON ct.class_id=c.id
        JOIN teachers t ON ct.teacher_id=t.id
        JOIN users u ON t.user_id=u.id
        WHERE ct.teacher_id=$teacherId
        ORDER BY ct.test_date DESC
    ");
} else {
    // Student
    $studentId = $_SESSION['linked_id'] ?? 0;
    $tests = $conn->query("
        SELECT ct.*, s.name as subject_name, s.code, c.name as class_name, u.name as teacher_name,
            tr.marks_obtained, tr.submitted_at
        FROM class_tests ct
        JOIN subjects s ON ct.subject_id=s.id
        JOIN classes c ON ct.class_id=c.id
        JOIN teachers t ON ct.teacher_id=t.id
        JOIN users u ON t.user_id=u.id
        LEFT JOIN test_results tr ON tr.test_id=ct.id AND tr.student_id=$studentId
        WHERE ct.class_id=(SELECT class_id FROM students WHERE id=$studentId)
        ORDER BY ct.test_date DESC
    ");
}

include __DIR__ . '/../includes/header.php';
if ($role === 'student') {
    $initials = strtoupper(substr($_SESSION['name'],0,1));
    echo '<div class="sidebar">';
    echo '<div class="sidebar-brand"><div class="brand-icon"><svg viewBox="0 0 64 64" fill="var(--gold)"><path d="M32 4L8 16v16c0 14 10.7 26.4 24 29.3C45.3 58.4 56 46 56 32V16L32 4zm0 8l18 9v11c0 10.5-7.8 19.8-18 22.3C21.8 51.8 14 42.5 14 32V21l18-9z"/></svg></div><div class="brand-text"><strong>CMS Portal</strong><span>Student Panel</span></div></div>';
    echo '<div class="sidebar-user"><div class="user-avatar">'.$initials.'</div><div class="user-info"><strong>'.htmlspecialchars($_SESSION['name']).'</strong><span>Student</span></div></div>';
    echo '<nav class="sidebar-nav"><div class="nav-section-label">Overview</div><a href="'.$depth.'dashboard/student.php" class="nav-item"><span class="nav-icon">🏠</span>Dashboard</a><div class="nav-section-label">Academics</div><a href="'.$depth.'attendance/student_report.php" class="nav-item"><span class="nav-icon">📋</span>My Attendance</a><a href="'.$depth.'tests/results.php" class="nav-item active"><span class="nav-icon">📝</span>My Test Results</a><div class="nav-section-label">Fees</div><a href="'.$depth.'students/fees.php" class="nav-item"><span class="nav-icon">💰</span>My Fees</a></nav>';
    echo '<div class="sidebar-footer"><a href="'.$depth.'auth/logout.php" class="logout-btn">🚪 Sign Out</a></div></div>';
} else {
    include __DIR__ . "/../includes/sidebar_{$role}.php";
}
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title"><h2>Class Tests</h2><p><?= $role==='student'?'Your tests and results':'Manage class tests' ?></p></div>
    <div class="topbar-right">
      <?php if($role==='faculty'): ?>
      <a href="add.php" class="btn btn-primary">+ Create Test</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="content">
    <?php showFlash(); ?>

    <div class="table-card">
      <div class="table-card-header">
        <h3>All Tests (<?= $tests ? $tests->num_rows : 0 ?>)</h3>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>#</th><th>Title</th><th>Subject</th><th>Class</th>
              <?php if($role!=='student'): ?><th>Faculty</th><?php endif; ?>
              <th>Date</th><th>Total Marks</th>
              <?php if($role==='student'): ?><th>Your Score</th><?php endif; ?>
              <?php if($role==='faculty'): ?><th>Results</th><?php endif; ?>
              <?php if(hasRole(['director','coordinator','faculty'])): ?><th>Actions</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
          <?php if($tests && $tests->num_rows > 0): $i=1; while($t=$tests->fetch_assoc()): ?>
            <tr>
              <td style="color:#6b7280"><?= $i++ ?></td>
              <td style="font-weight:700"><?= htmlspecialchars($t['title']) ?></td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($t['subject_name']) ?> (<?= $t['code'] ?>)</span></td>
              <td><?= htmlspecialchars($t['class_name']) ?></td>
              <?php if($role!=='student'): ?>
              <td style="font-size:13px"><?= htmlspecialchars($t['teacher_name']) ?></td>
              <?php endif; ?>
              <td style="font-size:13px"><?= date('d M Y', strtotime($t['test_date'])) ?></td>
              <td style="font-weight:700;font-size:16px"><?= $t['total_marks'] ?></td>
              <?php if($role==='student'): ?>
              <td>
                <?php if(isset($t['marks_obtained']) && $t['marks_obtained'] !== null):
                  $pct = $t['total_marks'] > 0 ? round($t['marks_obtained']/$t['total_marks']*100) : 0;
                ?>
                  <span style="font-weight:700;font-size:16px;color:<?= $pct>=75?'#27ae60':($pct>=40?'#e67e22':'#e74c3c') ?>"><?= $t['marks_obtained'] ?>/<?= $t['total_marks'] ?></span>
                  <span class="badge <?= $pct>=75?'badge-green':($pct>=40?'badge-yellow':'badge-red') ?>" style="margin-left:4px"><?= $pct ?>%</span>
                <?php else: ?>
                  <span class="badge badge-yellow">Pending</span>
                <?php endif; ?>
              </td>
              <?php endif; ?>
              <?php if($role==='faculty'): ?>
              <td>
                <?php
                $resultCount = $conn->query("SELECT COUNT(*) as c FROM test_results WHERE test_id={$t['id']}")->fetch_assoc()['c'];
                $classSize   = $conn->query("SELECT COUNT(*) as c FROM students WHERE class_id={$t['class_id']}")->fetch_assoc()['c'];
                ?>
                <span class="badge <?= $resultCount>0?'badge-green':'badge-red' ?>"><?= $resultCount ?>/<?= $classSize ?> submitted</span>
              </td>
              <?php endif; ?>
              <?php if(hasRole(['director','coordinator','faculty'])): ?>
              <td>
                <div style="display:flex;gap:6px">
                  <a href="results.php?test_id=<?= $t['id'] ?>" class="btn btn-sm btn-outline">📊 Results</a>
                  <?php if($role==='faculty'): ?>
                  <a href="submit.php?test_id=<?= $t['id'] ?>" class="btn btn-sm btn-green">✏️ Enter Marks</a>
                  <?php endif; ?>
                  <?php if(hasRole(['director'])): ?>
                  <a href="delete.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-red" onclick="return confirm('Delete this test?')">🗑</a>
                  <?php endif; ?>
                </div>
              </td>
              <?php endif; ?>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="9" style="text-align:center;padding:40px;color:#6b7280">No tests found</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
