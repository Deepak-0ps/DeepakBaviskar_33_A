<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director','coordinator']);
$pageTitle = 'Edit Teacher';
$depth = '../';

$id = (int)($_GET['id'] ?? 0);
$teacher = $conn->query("
    SELECT t.*, u.name, u.email, u.phone, u.address, u.is_active
    FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.id=$id
")->fetch_assoc();
if (!$teacher) { flashMessage('error','Teacher not found'); redirect('teachers/list.php'); }

$departments = $conn->query("SELECT * FROM departments ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = sanitize($conn, $_POST['name']);
    $email       = sanitize($conn, $_POST['email']);
    $phone       = sanitize($conn, $_POST['phone']);
    $empId       = sanitize($conn, $_POST['employee_id']);
    $deptId      = (int)$_POST['department_id'];
    $designation = sanitize($conn, $_POST['designation']);
    $qualification = sanitize($conn, $_POST['qualification']);
    $joining     = sanitize($conn, $_POST['joining_date']);
    $address     = sanitize($conn, $_POST['address']);
    $isActive    = (int)$_POST['is_active'];

    $conn->query("UPDATE users SET name='$name',email='$email',phone='$phone',address='$address',is_active=$isActive WHERE id={$teacher['user_id']}");
    $conn->query("UPDATE teachers SET employee_id='$empId',department_id=$deptId,designation='$designation',qualification='$qualification',joining_date='$joining' WHERE id=$id");
    flashMessage('success', 'Teacher updated successfully!');
    redirect("teachers/view.php?id=$id");
}

include __DIR__ . '/../includes/header.php';
$role = $_SESSION['role'];
include __DIR__ . "/../includes/sidebar_{$role}.php";
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title"><h2>Edit Teacher</h2><p><?= htmlspecialchars($teacher['name']) ?></p></div>
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
            <input type="text" name="name" value="<?= htmlspecialchars($teacher['name']) ?>" required>
          </div>
          <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" value="<?= htmlspecialchars($teacher['email']) ?>" required>
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($teacher['phone'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" value="<?= htmlspecialchars($teacher['address'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="is_active" style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;width:100%;font-size:14px;outline:none">
              <option value="1" <?= $teacher['is_active']?'selected':'' ?>>Active</option>
              <option value="0" <?= !$teacher['is_active']?'selected':'' ?>>Inactive</option>
            </select>
          </div>
        </div>
        <h3 style="margin:28px 0 22px;font-size:16px;border-bottom:1px solid #e5e7eb;padding-bottom:12px">🎓 Professional Details</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>Employee ID *</label>
            <input type="text" name="employee_id" value="<?= htmlspecialchars($teacher['employee_id']) ?>" required>
          </div>
          <div class="form-group">
            <label>Department *</label>
            <select name="department_id" required style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;width:100%;font-size:14px;outline:none">
              <?php while($d=$departments->fetch_assoc()): ?>
              <option value="<?= $d['id'] ?>" <?= $teacher['department_id']==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Designation</label>
            <input type="text" name="designation" value="<?= htmlspecialchars($teacher['designation'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Qualification</label>
            <input type="text" name="qualification" value="<?= htmlspecialchars($teacher['qualification'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Joining Date</label>
            <input type="date" name="joining_date" value="<?= $teacher['joining_date'] ?>">
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">✓ Update Teacher</button>
          <a href="view.php?id=<?= $id ?>" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
