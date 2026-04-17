<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director','coordinator']);
$pageTitle = 'Edit Student';
$depth = '../';

$id = (int)($_GET['id'] ?? 0);
$student = $conn->query("SELECT s.*, u.name, u.email, u.phone, u.address, u.is_active FROM students s JOIN users u ON s.user_id=u.id WHERE s.id=$id")->fetch_assoc();
if (!$student) { flashMessage('error','Student not found'); redirect('students/list.php'); }

$classes = $conn->query("SELECT * FROM classes ORDER BY name");
$departments = $conn->query("SELECT * FROM departments ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($conn, $_POST['name']);
    $email   = sanitize($conn, $_POST['email']);
    $phone   = sanitize($conn, $_POST['phone']);
    $roll    = sanitize($conn, $_POST['roll_number']);
    $classId = (int)$_POST['class_id'];
    $deptId  = (int)$_POST['department_id'];
    $dob     = sanitize($conn, $_POST['date_of_birth']);
    $gender  = sanitize($conn, $_POST['gender']);
    $guardian= sanitize($conn, $_POST['guardian_name']);
    $gPhone  = sanitize($conn, $_POST['guardian_phone']);
    $totalFees=(float)$_POST['total_fees'];
    $address = sanitize($conn, $_POST['address']);
    $isActive= (int)$_POST['is_active'];

    $conn->query("UPDATE users SET name='$name',email='$email',phone='$phone',address='$address',is_active=$isActive WHERE id={$student['user_id']}");
    $conn->query("UPDATE students SET roll_number='$roll',class_id=$classId,department_id=$deptId,date_of_birth='$dob',gender='$gender',guardian_name='$guardian',guardian_phone='$gPhone',total_fees=$totalFees WHERE id=$id");
    flashMessage('success','Student updated successfully!');
    redirect("students/view.php?id=$id");
}

include __DIR__ . '/../includes/header.php';
$role = $_SESSION['role'];
include __DIR__ . "/../includes/sidebar_{$role}.php";
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title"><h2>Edit Student</h2><p><?= htmlspecialchars($student['name']) ?></p></div>
    <div class="topbar-right"><a href="view.php?id=<?= $id ?>" class="btn btn-outline">← Back</a></div>
  </div>
  <div class="content">
    <?php showFlash(); ?>
    <div class="form-card">
      <form method="POST">
        <h3 style="margin-bottom:22px;font-size:16px;border-bottom:1px solid #e5e7eb;padding-bottom:12px">👤 Personal Information</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>
          </div>
          <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Date of Birth</label>
            <input type="date" name="date_of_birth" value="<?= $student['date_of_birth'] ?>">
          </div>
          <div class="form-group">
            <label>Gender</label>
            <select name="gender">
              <?php foreach(['male','female','other'] as $g): ?>
              <option value="<?= $g ?>" <?= $student['gender']===$g?'selected':'' ?>><?= ucfirst($g) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" value="<?= htmlspecialchars($student['address'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="is_active">
              <option value="1" <?= $student['is_active']?'selected':'' ?>>Active</option>
              <option value="0" <?= !$student['is_active']?'selected':'' ?>>Inactive</option>
            </select>
          </div>
        </div>

        <h3 style="margin:28px 0 22px;font-size:16px;border-bottom:1px solid #e5e7eb;padding-bottom:12px">🎓 Academic Details</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>Roll Number *</label>
            <input type="text" name="roll_number" value="<?= htmlspecialchars($student['roll_number']) ?>" required>
          </div>
          <div class="form-group">
            <label>Department *</label>
            <select name="department_id" required>
              <?php $departments->data_seek(0); while($d=$departments->fetch_assoc()): ?>
              <option value="<?= $d['id'] ?>" <?= $student['department_id']==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Class *</label>
            <select name="class_id" required>
              <?php $classes->data_seek(0); while($c=$classes->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>" <?= $student['class_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>

        <h3 style="margin:28px 0 22px;font-size:16px;border-bottom:1px solid #e5e7eb;padding-bottom:12px">💰 Guardian & Fees</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>Guardian Name</label>
            <input type="text" name="guardian_name" value="<?= htmlspecialchars($student['guardian_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Guardian Phone</label>
            <input type="text" name="guardian_phone" value="<?= htmlspecialchars($student['guardian_phone'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Total Fees (₹)</label>
            <input type="number" name="total_fees" value="<?= $student['total_fees'] ?>" min="0" step="500">
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">✓ Update Student</button>
          <a href="view.php?id=<?= $id ?>" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
