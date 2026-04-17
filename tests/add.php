<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['faculty','coordinator','director']);
$pageTitle = 'Create Test';
$depth = '../';
$role = $_SESSION['role'];
$teacherId = $_SESSION['linked_id'] ?? 0;

if (hasRole(['director','coordinator'])) {
    $subjects = $conn->query("SELECT s.*, c.name as class_name FROM subjects s LEFT JOIN classes c ON s.class_id=c.id ORDER BY s.name");
    $teachers = $conn->query("SELECT t.id, u.name FROM teachers t JOIN users u ON t.user_id=u.id ORDER BY u.name");
} else {
    $subjects = $conn->query("SELECT s.*, c.name as class_name FROM subjects s LEFT JOIN classes c ON s.class_id=c.id WHERE s.teacher_id=$teacherId ORDER BY s.name");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = sanitize($conn, $_POST['title']);
    $subId    = (int)$_POST['subject_id'];
    $classId  = (int)$_POST['class_id'];
    $tId      = hasRole(['director','coordinator']) ? (int)$_POST['teacher_id'] : $teacherId;
    $marks    = (int)$_POST['total_marks'];
    $date     = sanitize($conn, $_POST['test_date']);

    $conn->query("INSERT INTO class_tests (title,subject_id,class_id,teacher_id,total_marks,test_date) VALUES ('$title',$subId,$classId,$tId,$marks,'$date')");
    flashMessage('success', 'Test created successfully!');
    redirect('tests/list.php');
}

$classes = $conn->query("SELECT * FROM classes ORDER BY name");

include __DIR__ . '/../includes/header.php';
include __DIR__ . "/../includes/sidebar_{$role}.php";
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title"><h2>Create Class Test</h2><p>Schedule a new test or assignment</p></div>
    <div class="topbar-right"><a href="list.php" class="btn btn-outline">← Back</a></div>
  </div>
  <div class="content">
    <?php showFlash(); ?>
    <div class="form-card">
      <form method="POST">
        <div class="form-grid">
          <div class="form-group" style="grid-column:1/-1">
            <label>Test Title *</label>
            <input type="text" name="title" required placeholder="e.g. Unit Test 1 — Data Structures">
          </div>
          <div class="form-group">
            <label>Subject *</label>
            <select name="subject_id" required style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;width:100%;font-size:14px;outline:none" onchange="autofillClass(this)">
              <option value="">— Select Subject —</option>
              <?php $subjects->data_seek(0); while($s=$subjects->fetch_assoc()): ?>
              <option value="<?= $s['id'] ?>" data-class="<?= $s['class_id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['code'] ?>)</option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Class *</label>
            <select name="class_id" id="class_id" required style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;width:100%;font-size:14px;outline:none">
              <option value="">— Select Class —</option>
              <?php $classes->data_seek(0); while($c=$classes->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <?php if(hasRole(['director','coordinator'])): ?>
          <div class="form-group">
            <label>Faculty *</label>
            <select name="teacher_id" required style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;width:100%;font-size:14px;outline:none">
              <option value="">— Select Faculty —</option>
              <?php while($t=$teachers->fetch_assoc()): ?>
              <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="form-group">
            <label>Total Marks *</label>
            <input type="number" name="total_marks" required min="1" max="200" placeholder="e.g. 25">
          </div>
          <div class="form-group">
            <label>Test Date *</label>
            <input type="date" name="test_date" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">✓ Create Test</button>
          <a href="list.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function autofillClass(sel) {
    const classId = sel.options[sel.selectedIndex].dataset.class;
    if (classId) {
        const classSelect = document.getElementById('class_id');
        for (let opt of classSelect.options) {
            if (opt.value === classId) { opt.selected = true; break; }
        }
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
