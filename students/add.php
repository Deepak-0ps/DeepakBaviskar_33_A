<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director','coordinator']);
$pageTitle = 'Add Student';
$depth = '../';

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
    $year    = (int)$_POST['admission_year'];
    $address = sanitize($conn, $_POST['address']);
    $password = password_hash('password', PASSWORD_DEFAULT);

    // Check duplicate
    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if ($check && $check->num_rows > 0) {
        flashMessage('error', 'Email already exists!');
    } else {
        $conn->query("INSERT INTO users (name,email,password,role,phone,address) VALUES ('$name','$email','$password','student','$phone','$address')");
        $userId = $conn->insert_id;
        $conn->query("INSERT INTO students (user_id,roll_number,class_id,department_id,admission_year,date_of_birth,gender,guardian_name,guardian_phone,total_fees,fees_paid)
            VALUES ($userId,'$roll',$classId,$deptId,$year,'$dob','$gender','$guardian','$gPhone',$totalFees,0)");
        flashMessage('success', 'Student added successfully! Default password: password');
        redirect('students/list.php');
    }
}

include __DIR__ . '/../includes/header.php';
$role = $_SESSION['role'];
include __DIR__ . "/../includes/sidebar_{$role}.php";
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title"><h2>Add New Student</h2><p>Fill in the details to enroll a new student</p></div>
    <div class="topbar-right"><a href="list.php" class="btn btn-outline">← Back to List</a></div>
  </div>
  <div class="content">
    <?php showFlash(); ?>
    <div class="form-card">
      <form method="POST">
        <h3 style="margin-bottom:22px;font-size:16px;color:#374151;border-bottom:1px solid #e5e7eb;padding-bottom:12px">👤 Personal Information</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="name" required placeholder="e.g. Amit Desai">
          </div>
          <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" required placeholder="student@email.com">
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone" placeholder="10-digit mobile number">
          </div>
          <div class="form-group">
            <label>Date of Birth</label>
            <input type="date" name="date_of_birth">
          </div>
          <div class="form-group">
            <label>Gender</label>
            <select name="gender">
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" placeholder="Residential address">
          </div>
        </div>

        <h3 style="margin:28px 0 22px;font-size:16px;color:#374151;border-bottom:1px solid #e5e7eb;padding-bottom:12px">🎓 Academic Details</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>Roll Number *</label>
            <input type="text" name="roll_number" required placeholder="e.g. CS2024001">
          </div>
          <div class="form-group">
            <label>Admission Year *</label>
            <input type="number" name="admission_year" required value="<?= date('Y') ?>" min="2000" max="<?= date('Y') ?>">
          </div>
          <div class="form-group">
            <label>Department *</label>
            <select name="department_id" required>
              <option value="">— Select Department —</option>
              <?php $departments->data_seek(0); while($d=$departments->fetch_assoc()): ?>
              <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Class *</label>
            <select name="class_id" required>
              <option value="">— Select Class —</option>
              <?php while($c=$classes->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>

        <h3 style="margin:28px 0 22px;font-size:16px;color:#374151;border-bottom:1px solid #e5e7eb;padding-bottom:12px">👨‍👩‍👦 Guardian & Fees</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>Guardian Name</label>
            <input type="text" name="guardian_name" placeholder="Parent/Guardian full name">
          </div>
          <div class="form-group">
            <label>Guardian Phone</label>
            <input type="text" name="guardian_phone" placeholder="Guardian mobile number">
          </div>
          <div class="form-group">
            <label>Total Fees (₹) *</label>
            <input type="number" name="total_fees" required min="0" step="500" placeholder="e.g. 85000">
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">✓ Add Student</button>
          <a href="list.php" class="btn btn-outline">Cancel</a>
          <span style="font-size:13px;color:#6b7280">Default password will be set to: <strong>password</strong></span>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
