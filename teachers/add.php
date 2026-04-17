<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director','coordinator']);
$pageTitle = 'Add Teacher';
$depth = '../';

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
    $password    = password_hash('password', PASSWORD_DEFAULT);

    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    $checkEmp = $conn->query("SELECT id FROM teachers WHERE employee_id='$empId'");
    if ($check && $check->num_rows > 0) {
        flashMessage('error', 'Email already exists!');
    } elseif ($checkEmp && $checkEmp->num_rows > 0) {
        flashMessage('error', 'Employee ID already exists!');
    } else {
        $conn->query("INSERT INTO users (name,email,password,role,phone,address) VALUES ('$name','$email','$password','faculty','$phone','$address')");
        $userId = $conn->insert_id;
        $conn->query("INSERT INTO teachers (user_id,employee_id,department_id,designation,qualification,joining_date)
            VALUES ($userId,'$empId',$deptId,'$designation','$qualification','$joining')");
        flashMessage('success', 'Teacher added successfully! Default password: password');
        redirect('teachers/list.php');
    }
}

include __DIR__ . '/../includes/header.php';
$role = $_SESSION['role'];
include __DIR__ . "/../includes/sidebar_{$role}.php";
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title"><h2>Add New Teacher</h2><p>Register a new faculty member</p></div>
    <div class="topbar-right"><a href="list.php" class="btn btn-outline">← Back</a></div>
  </div>
  <div class="content">
    <?php showFlash(); ?>
    <div class="form-card">
      <form method="POST">
        <h3 style="margin-bottom:22px;font-size:16px;border-bottom:1px solid #e5e7eb;padding-bottom:12px">👤 Personal Information</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="name" required placeholder="Prof. Full Name">
          </div>
          <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" required placeholder="faculty@college.edu">
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone" placeholder="10-digit mobile">
          </div>
          <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" placeholder="Residential address">
          </div>
        </div>

        <h3 style="margin:28px 0 22px;font-size:16px;border-bottom:1px solid #e5e7eb;padding-bottom:12px">🎓 Professional Details</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>Employee ID *</label>
            <input type="text" name="employee_id" required placeholder="e.g. EMP005">
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
            <label>Designation</label>
            <input type="text" name="designation" placeholder="e.g. Assistant Professor">
          </div>
          <div class="form-group">
            <label>Qualification</label>
            <input type="text" name="qualification" placeholder="e.g. M.Tech CS, Ph.D">
          </div>
          <div class="form-group">
            <label>Joining Date</label>
            <input type="date" name="joining_date" value="<?= date('Y-m-d') ?>">
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">✓ Add Teacher</button>
          <a href="list.php" class="btn btn-outline">Cancel</a>
          <span style="font-size:13px;color:#6b7280">Default password: <strong>password</strong></span>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
