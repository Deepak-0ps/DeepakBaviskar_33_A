<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director','coordinator']);
$pageTitle = 'Add Subject';
$depth = '../';
$role = $_SESSION['role'];

$departments = $conn->query("SELECT * FROM departments ORDER BY name");
$classes     = $conn->query("SELECT * FROM classes ORDER BY name");
$teachers    = $conn->query("SELECT t.id, u.name FROM teachers t JOIN users u ON t.user_id=u.id ORDER BY u.name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($conn, $_POST['name']);
    $code    = sanitize($conn, $_POST['code']);
    $deptId  = (int)$_POST['department_id'];
    $classId = (int)$_POST['class_id'];
    $teachId = (int)$_POST['teacher_id'];
    $credits = (int)$_POST['credits'];

    $check = $conn->query("SELECT id FROM subjects WHERE code='$code'");
    if ($check && $check->num_rows > 0) {
        flashMessage('error', 'Subject code already exists!');
    } else {
        $conn->query("INSERT INTO subjects (name,code,department_id,class_id,teacher_id,credits) VALUES ('$name','$code',$deptId,$classId,$teachId,$credits)");
        flashMessage('success', 'Subject added successfully!');
        redirect('subjects/list.php');
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . "/../includes/sidebar_{$role}.php";
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title"><h2>Add Subject</h2><p>Register a new subject</p></div>
    <div class="topbar-right"><a href="list.php" class="btn btn-outline">← Back</a></div>
  </div>
  <div class="content">
    <?php showFlash(); ?>
    <div class="form-card">
      <form method="POST">
        <div class="form-grid">
          <div class="form-group">
            <label>Subject Name *</label>
            <input type="text" name="name" required placeholder="e.g. Data Structures">
          </div>
          <div class="form-group">
            <label>Subject Code *</label>
            <input type="text" name="code" required placeholder="e.g. CS201">
          </div>
          <div class="form-group">
            <label>Department *</label>
            <select name="department_id" required style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;width:100%;font-size:14px;outline:none">
              <option value="">— Select Department —</option>
              <?php while($d=$departments->fetch_assoc()): ?>
              <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Class *</label>
            <select name="class_id" required style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;width:100%;font-size:14px;outline:none">
              <option value="">— Select Class —</option>
              <?php while($c=$classes->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Assigned Faculty *</label>
            <select name="teacher_id" required style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;width:100%;font-size:14px;outline:none">
              <option value="">— Select Faculty —</option>
              <?php while($t=$teachers->fetch_assoc()): ?>
              <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Credits *</label>
            <select name="credits" style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;width:100%;font-size:14px;outline:none">
              <?php for($c=1;$c<=6;$c++): ?><option value="<?= $c ?>" <?= $c===3?'selected':'' ?>><?= $c ?> Credits</option><?php endfor; ?>
            </select>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">✓ Add Subject</button>
          <a href="list.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
