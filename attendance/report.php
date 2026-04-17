<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director','coordinator','faculty']);
$pageTitle = 'Attendance Report';
$depth = '../';
$role = $_SESSION['role'];

$filterSubject = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$filterClass   = isset($_GET['class_id'])   ? (int)$_GET['class_id']   : 0;
$filterFrom    = isset($_GET['from']) ? sanitize($conn, $_GET['from']) : date('Y-m-01');
$filterTo      = isset($_GET['to'])   ? sanitize($conn, $_GET['to'])   : date('Y-m-d');

$teacherId = $_SESSION['linked_id'] ?? 0;
if (hasRole(['director','coordinator'])) {
    $subjects = $conn->query("SELECT s.*, c.name as class_name FROM subjects s LEFT JOIN classes c ON s.class_id=c.id ORDER BY s.name");
} else {
    $subjects = $conn->query("SELECT s.*, c.name as class_name FROM subjects s LEFT JOIN classes c ON s.class_id=c.id WHERE s.teacher_id=$teacherId ORDER BY s.name");
}
$classes = $conn->query("SELECT * FROM classes ORDER BY name");

$subWhere   = $filterSubject ? "AND a.subject_id=$filterSubject" : '';
$classWhere = $filterClass   ? "AND a.class_id=$filterClass" : '';
$dateWhere  = "AND a.date BETWEEN '$filterFrom' AND '$filterTo'";

$report = $conn->query("
    SELECT u.name as student_name, s.roll_number,
           c.name as class_name, sub.name as subject_name,
           COUNT(a.id) as total_classes,
           SUM(a.status='present') as present_count,
           SUM(a.status='absent') as absent_count,
           SUM(a.status='late') as late_count,
           ROUND(SUM(a.status='present')/COUNT(a.id)*100, 1) as percentage
    FROM attendance a
    JOIN students s ON a.student_id=s.id
    JOIN users u ON s.user_id=u.id
    JOIN classes c ON a.class_id=c.id
    JOIN subjects sub ON a.subject_id=sub.id
    WHERE 1=1 $subWhere $classWhere $dateWhere
    GROUP BY a.student_id, a.subject_id
    ORDER BY u.name, sub.name
");

$totalStudentsReported = $report ? $report->num_rows : 0;

include __DIR__ . '/../includes/header.php';
include __DIR__ . "/../includes/sidebar_{$role}.php";
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title"><h2>Attendance Report</h2><p>Filter and view attendance records</p></div>
    <div class="topbar-right">
      <button onclick="window.print()" class="btn btn-outline">🖨 Print Report</button>
    </div>
  </div>
  <div class="content">
    <?php showFlash(); ?>

    <!-- Filters -->
    <div class="form-card" style="margin-bottom:24px">
      <form method="GET" style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="flex:1;min-width:180px">
          <label>Subject</label>
          <select name="subject_id" style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;width:100%;font-size:14px;outline:none">
            <option value="">All Subjects</option>
            <?php $subjects->data_seek(0); while($s=$subjects->fetch_assoc()): ?>
            <option value="<?= $s['id'] ?>" <?= $filterSubject==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['name']) ?> (<?= $s['code'] ?>)</option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group" style="flex:1;min-width:160px">
          <label>Class</label>
          <select name="class_id" style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;width:100%;font-size:14px;outline:none">
            <option value="">All Classes</option>
            <?php while($c=$classes->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>" <?= $filterClass==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>From Date</label>
          <input type="date" name="from" value="<?= $filterFrom ?>" style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;font-size:14px;outline:none">
        </div>
        <div class="form-group">
          <label>To Date</label>
          <input type="date" name="to" value="<?= $filterTo ?>" style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;font-size:14px;outline:none">
        </div>
        <button type="submit" class="btn btn-primary" style="height:46px">🔍 Filter</button>
        <a href="report.php" class="btn btn-outline" style="height:46px;display:flex;align-items:center">Clear</a>
      </form>
    </div>

    <!-- Report Table -->
    <div class="table-card">
      <div class="table-card-header">
        <h3>📋 Attendance Records</h3>
        <span style="font-size:13px;color:#6b7280"><?= date('d M Y',strtotime($filterFrom)) ?> — <?= date('d M Y',strtotime($filterTo)) ?></span>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>#</th><th>Student</th><th>Class</th><th>Subject</th>
              <th>Total</th><th>Present</th><th>Absent</th><th>Late</th><th>Percentage</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php if($report && $report->num_rows > 0): $i=1; while($r=$report->fetch_assoc()): ?>
            <tr>
              <td style="color:#6b7280"><?= $i++ ?></td>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($r['student_name']) ?></div>
                <div style="font-size:12px;color:#6b7280"><?= $r['roll_number'] ?></div>
              </td>
              <td style="font-size:13px"><?= htmlspecialchars($r['class_name']) ?></td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($r['subject_name']) ?></span></td>
              <td style="font-weight:600"><?= $r['total_classes'] ?></td>
              <td style="color:#27ae60;font-weight:600"><?= $r['present_count'] ?></td>
              <td style="color:#e74c3c;font-weight:600"><?= $r['absent_count'] ?></td>
              <td style="color:#e67e22;font-weight:600"><?= $r['late_count'] ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:8px;min-width:120px">
                  <div class="att-bar" style="flex:1">
                    <div class="att-fill <?= $r['percentage']>=75?'high':($r['percentage']>=60?'mid':'low') ?>" style="width:<?= $r['percentage'] ?>%"></div>
                  </div>
                  <span style="font-size:13px;font-weight:700;min-width:38px;color:<?= $r['percentage']>=75?'#27ae60':($r['percentage']>=60?'#e67e22':'#e74c3c') ?>">
                    <?= $r['percentage'] ?>%
                  </span>
                </div>
              </td>
              <td>
                <?php if($r['percentage'] >= 75): ?>
                  <span class="badge badge-green">✓ OK</span>
                <?php elseif($r['percentage'] >= 60): ?>
                  <span class="badge badge-yellow">⚠ Low</span>
                <?php else: ?>
                  <span class="badge badge-red">✗ Shortage</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="10" style="text-align:center;padding:40px;color:#6b7280">No attendance records found for selected filters</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
