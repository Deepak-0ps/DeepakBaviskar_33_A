<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director','coordinator','faculty']);
$pageTitle = 'View Attendance';
$depth = '../';
$role = $_SESSION['role'];
$teacherId = $_SESSION['linked_id'] ?? 0;

if (hasRole(['director','coordinator'])) {
    $subjects = $conn->query("SELECT s.*, c.name as class_name FROM subjects s LEFT JOIN classes c ON s.class_id=c.id ORDER BY s.name");
} else {
    $subjects = $conn->query("SELECT s.*, c.name as class_name FROM subjects s LEFT JOIN classes c ON s.class_id=c.id WHERE s.teacher_id=$teacherId ORDER BY s.name");
}

$filterSubject = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$filterDate    = isset($_GET['date']) ? sanitize($conn, $_GET['date']) : date('Y-m-d');

$records = null;
$subjectInfo = null;
if ($filterSubject) {
    $subjectInfo = $conn->query("SELECT s.*, c.name as class_name FROM subjects s LEFT JOIN classes c ON s.class_id=c.id WHERE s.id=$filterSubject")->fetch_assoc();
    $records = $conn->query("
        SELECT a.*, u.name as student_name, s.roll_number
        FROM attendance a
        JOIN students s ON a.student_id=s.id
        JOIN users u ON s.user_id=u.id
        WHERE a.subject_id=$filterSubject AND a.date='$filterDate'
        ORDER BY s.roll_number
    ");
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . "/../includes/sidebar_{$role}.php";
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title"><h2>View Attendance</h2><p>View marked attendance for a specific date</p></div>
    <div class="topbar-right">
      <?php if($filterSubject): ?><a href="../attendance/take.php?subject_id=<?= $filterSubject ?>&date=<?= $filterDate ?>" class="btn btn-gold">✏️ Edit Attendance</a><?php endif; ?>
    </div>
  </div>
  <div class="content">
    <?php showFlash(); ?>

    <div class="form-card" style="margin-bottom:24px">
      <form method="GET" style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="flex:1;min-width:220px">
          <label>Subject</label>
          <select name="subject_id" style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;width:100%;font-size:14px;outline:none">
            <option value="">— Select Subject —</option>
            <?php $subjects->data_seek(0); while($s=$subjects->fetch_assoc()): ?>
            <option value="<?= $s['id'] ?>" <?= $filterSubject==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['name']) ?> (<?= $s['code'] ?>) — <?= htmlspecialchars($s['class_name'] ?? '') ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Date</label>
          <input type="date" name="date" value="<?= $filterDate ?>" max="<?= date('Y-m-d') ?>" style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;font-size:14px;outline:none">
        </div>
        <button type="submit" class="btn btn-primary" style="height:46px">View</button>
      </form>
    </div>

    <?php if($filterSubject && $subjectInfo): ?>
    <div class="table-card">
      <div class="table-card-header">
        <h3><?= htmlspecialchars($subjectInfo['name']) ?> — <?= date('D, d M Y', strtotime($filterDate)) ?></h3>
        <?php if($records):
          $total = $records->num_rows;
          $records->data_seek(0);
          $presentCount = 0; $absentCount = 0; $lateCount = 0;
          $allRows = [];
          while($rr=$records->fetch_assoc()) {
            $allRows[] = $rr;
            if($rr['status']==='present') $presentCount++;
            elseif($rr['status']==='absent') $absentCount++;
            else $lateCount++;
          }
        ?>
        <div style="display:flex;gap:10px">
          <span class="badge badge-green">✅ <?= $presentCount ?> Present</span>
          <span class="badge badge-red">❌ <?= $absentCount ?> Absent</span>
          <span class="badge badge-yellow">🕐 <?= $lateCount ?> Late</span>
        </div>
        <?php endif; ?>
      </div>
      <div class="table-responsive">
        <table>
          <thead><tr><th>#</th><th>Roll No</th><th>Student</th><th>Status</th></tr></thead>
          <tbody>
          <?php if(!empty($allRows)): foreach($allRows as $i => $r): ?>
            <tr>
              <td style="color:#6b7280"><?= $i+1 ?></td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($r['roll_number']) ?></span></td>
              <td style="font-weight:600"><?= htmlspecialchars($r['student_name']) ?></td>
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
          <?php endforeach; else: ?>
            <tr><td colspan="4" style="text-align:center;padding:40px;color:#6b7280">No attendance marked for this date</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
