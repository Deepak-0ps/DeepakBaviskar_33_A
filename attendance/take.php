<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director','coordinator','faculty']);
$pageTitle = 'Take Attendance';
$depth = '../';
$role = $_SESSION['role'];
$teacherId = $_SESSION['linked_id'] ?? 0;

// If director/coordinator, they can see all subjects; faculty sees only their own
if (hasRole(['director','coordinator'])) {
    $subjects = $conn->query("SELECT s.*, c.name as class_name, u.name as teacher_name FROM subjects s LEFT JOIN classes c ON s.class_id=c.id LEFT JOIN teachers t ON s.teacher_id=t.id LEFT JOIN users u ON t.user_id=u.id ORDER BY s.name");
} else {
    $subjects = $conn->query("SELECT s.*, c.name as class_name FROM subjects s LEFT JOIN classes c ON s.class_id=c.id WHERE s.teacher_id=$teacherId ORDER BY s.name");
}

$selectedSubject = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$selectedDate    = isset($_GET['date']) ? sanitize($conn, $_GET['date']) : date('Y-m-d');
$students        = [];
$subjectInfo     = null;
$alreadyMarked   = false;
$message         = '';

if ($selectedSubject) {
    $subjectInfo = $conn->query("SELECT s.*, c.name as class_name, c.id as class_id_val FROM subjects s LEFT JOIN classes c ON s.class_id=c.id WHERE s.id=$selectedSubject")->fetch_assoc();

    if ($subjectInfo) {
        $classId = $subjectInfo['class_id_val'];
        $students = $conn->query("
            SELECT s.id, s.roll_number, u.name,
                (SELECT status FROM attendance WHERE student_id=s.id AND subject_id=$selectedSubject AND date='$selectedDate') as today_status
            FROM students s
            JOIN users u ON s.user_id=u.id
            WHERE s.class_id=$classId
            ORDER BY s.roll_number
        ");

        // Check if already marked
        $check = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE subject_id=$selectedSubject AND date='$selectedDate' LIMIT 1")->fetch_assoc();
        $alreadyMarked = $check['c'] > 0;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $subId   = (int)$_POST['subject_id'];
    $classId = (int)$_POST['class_id'];
    $attDate = sanitize($conn, $_POST['attendance_date']);
    $statuses = $_POST['status'] ?? [];
    $by      = $_SESSION['user_id'];
    $saved   = 0;

    foreach ($statuses as $studentId => $status) {
        $studentId = (int)$studentId;
        $status    = in_array($status, ['present','absent','late']) ? $status : 'absent';
        $conn->query("
            INSERT INTO attendance (student_id, subject_id, class_id, date, status, marked_by)
            VALUES ($studentId, $subId, $classId, '$attDate', '$status', $by)
            ON DUPLICATE KEY UPDATE status='$status', marked_by=$by
        ");
        $saved++;
    }

    flashMessage('success', "Attendance saved for $saved students on " . date('d M Y', strtotime($attDate)));
    redirect("attendance/take.php?subject_id=$subId&date=$attDate");
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . "/../includes/sidebar_{$role}.php";
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title"><h2>Take Attendance</h2><p>Mark student attendance for a subject</p></div>
  </div>
  <div class="content">
    <?php showFlash(); ?>

    <!-- Subject & Date Selector -->
    <div class="form-card" style="margin-bottom:24px">
      <form method="GET" style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="flex:1;min-width:200px">
          <label>Select Subject *</label>
          <select name="subject_id" required style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;width:100%;font-size:14px;outline:none">
            <option value="">— Select Subject —</option>
            <?php $subjects->data_seek(0); while($s=$subjects->fetch_assoc()): ?>
            <option value="<?= $s['id'] ?>" <?= $selectedSubject==$s['id']?'selected':'' ?>>
              <?= htmlspecialchars($s['name']) ?> (<?= $s['code'] ?>) — <?= htmlspecialchars($s['class_name'] ?? 'No Class') ?>
              <?php if(hasRole(['director','coordinator']) && isset($s['teacher_name'])): ?> · <?= htmlspecialchars($s['teacher_name']) ?><?php endif; ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group" style="min-width:180px">
          <label>Date *</label>
          <input type="date" name="date" value="<?= $selectedDate ?>" max="<?= date('Y-m-d') ?>" required>
        </div>
        <button type="submit" class="btn btn-primary" style="height:46px">Load Students</button>
      </form>
    </div>

    <?php if ($selectedSubject && $subjectInfo): ?>
    <!-- Subject Info Bar -->
    <div style="background:var(--ink);color:#fff;border-radius:12px;padding:16px 24px;margin-bottom:20px;display:flex;gap:24px;align-items:center;flex-wrap:wrap">
      <div>
        <div style="font-size:12px;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:1px">Subject</div>
        <div style="font-weight:700;font-size:16px"><?= htmlspecialchars($subjectInfo['name']) ?> <span style="color:var(--gold)">(<?= $subjectInfo['code'] ?>)</span></div>
      </div>
      <div>
        <div style="font-size:12px;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:1px">Class</div>
        <div style="font-weight:600"><?= htmlspecialchars($subjectInfo['class_name'] ?? '—') ?></div>
      </div>
      <div>
        <div style="font-size:12px;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:1px">Date</div>
        <div style="font-weight:600"><?= date('D, d M Y', strtotime($selectedDate)) ?></div>
      </div>
      <?php if($alreadyMarked): ?>
      <div style="margin-left:auto">
        <span style="background:rgba(200,151,58,0.3);border:1px solid var(--gold);color:var(--gold);padding:6px 14px;border-radius:20px;font-size:13px">⚠ Already marked · Will update</span>
      </div>
      <?php endif; ?>
    </div>

    <?php if($students && $students->num_rows > 0): ?>
    <form method="POST">
      <input type="hidden" name="submit_attendance" value="1">
      <input type="hidden" name="subject_id" value="<?= $selectedSubject ?>">
      <input type="hidden" name="class_id" value="<?= $subjectInfo['class_id_val'] ?>">
      <input type="hidden" name="attendance_date" value="<?= $selectedDate ?>">

      <div class="table-card">
        <div class="table-card-header">
          <h3>👨‍🎓 Students (<?= $students->num_rows ?>)</h3>
          <div style="display:flex;gap:8px">
            <button type="button" onclick="markAll('present')" class="btn btn-sm btn-green">✓ All Present</button>
            <button type="button" onclick="markAll('absent')" class="btn btn-sm btn-red">✗ All Absent</button>
          </div>
        </div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr><th>#</th><th>Roll No</th><th>Student Name</th><th style="width:280px">Attendance Status</th></tr>
            </thead>
            <tbody>
            <?php $i=1; while($st=$students->fetch_assoc()): 
              $todayStatus = $st['today_status'] ?? 'present';
            ?>
              <tr>
                <td style="color:#6b7280"><?= $i++ ?></td>
                <td><span class="badge badge-blue"><?= htmlspecialchars($st['roll_number']) ?></span></td>
                <td style="font-weight:600"><?= htmlspecialchars($st['name']) ?></td>
                <td>
                  <div style="display:flex;gap:8px" class="att-radio-group">
                    <label style="display:flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;cursor:pointer;border:2px solid #e5e7eb;font-size:13px;font-weight:600;transition:all 0.15s"
                           class="att-label <?= $todayStatus==='present'?'att-present-active':'' ?>">
                      <input type="radio" name="status[<?= $st['id'] ?>]" value="present" <?= $todayStatus==='present'?'checked':'' ?> style="display:none" onchange="updateLabel(this)">
                      ✅ Present
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;cursor:pointer;border:2px solid #e5e7eb;font-size:13px;font-weight:600;transition:all 0.15s"
                           class="att-label <?= $todayStatus==='absent'?'att-absent-active':'' ?>">
                      <input type="radio" name="status[<?= $st['id'] ?>]" value="absent" <?= $todayStatus==='absent'?'checked':'' ?> style="display:none" onchange="updateLabel(this)">
                      ❌ Absent
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;cursor:pointer;border:2px solid #e5e7eb;font-size:13px;font-weight:600;transition:all 0.15s"
                           class="att-label <?= $todayStatus==='late'?'att-late-active':'' ?>">
                      <input type="radio" name="status[<?= $st['id'] ?>]" value="late" <?= $todayStatus==='late'?'checked':'' ?> style="display:none" onchange="updateLabel(this)">
                      🕐 Late
                    </label>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <div style="padding:20px;border-top:1px solid #f3f4f6;display:flex;gap:12px">
          <button type="submit" class="btn btn-primary" style="min-width:180px">💾 Save Attendance</button>
          <a href="take.php" class="btn btn-outline">Reset</a>
        </div>
      </div>
    </form>

    <?php else: ?>
      <div style="background:#fff;border-radius:12px;padding:40px;text-align:center;color:#6b7280">
        <div style="font-size:48px;margin-bottom:16px">👨‍🎓</div>
        <h3 style="font-size:18px;margin-bottom:8px">No students found</h3>
        <p>No students are assigned to this class yet.</p>
      </div>
    <?php endif; ?>
    <?php endif; ?>

  </div>
</div>

<style>
.att-present-active { background:#d1fae5 !important; border-color:#27ae60 !important; color:#065f46 !important; }
.att-absent-active  { background:#fee2e2 !important; border-color:#e74c3c !important; color:#991b1b !important; }
.att-late-active    { background:#fef3c7 !important; border-color:#f59e0b !important; color:#92400e !important; }
</style>
<script>
function updateLabel(radio) {
    const group = radio.closest('.att-radio-group');
    group.querySelectorAll('.att-label').forEach(l => {
        l.classList.remove('att-present-active','att-absent-active','att-late-active');
    });
    const cls = radio.value === 'present' ? 'att-present-active' : (radio.value === 'absent' ? 'att-absent-active' : 'att-late-active');
    radio.closest('.att-label').classList.add(cls);
}
function markAll(status) {
    document.querySelectorAll('input[type=radio][value='+status+']').forEach(r => {
        r.checked = true;
        updateLabel(r);
    });
}
// Init active states on load
document.querySelectorAll('.att-radio-group input[type=radio]:checked').forEach(r => updateLabel(r));
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
