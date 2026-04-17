<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director','coordinator','faculty']);
$pageTitle = 'Students';
$depth = '../';

$search = isset($_GET['search']) ? sanitize($conn, $_GET['search']) : '';
$whereClause = $search ? "AND (u.name LIKE '%$search%' OR s.roll_number LIKE '%$search%' OR u.email LIKE '%$search%')" : '';

$students = $conn->query("
    SELECT s.*, u.name, u.email, u.phone, u.is_active,
           c.name as class_name, d.name as dept_name
    FROM students s
    JOIN users u ON s.user_id=u.id
    LEFT JOIN classes c ON s.class_id=c.id
    LEFT JOIN departments d ON s.department_id=d.id
    WHERE 1=1 $whereClause
    ORDER BY s.roll_number ASC
");

include __DIR__ . '/../includes/header.php';
$role = $_SESSION['role'];
include __DIR__ . "/../includes/sidebar_{$role}.php";
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title"><h2>Students</h2><p>Manage all enrolled students</p></div>
    <div class="topbar-right">
      <?php if(hasRole(['director','coordinator'])): ?>
      <a href="add.php" class="btn btn-primary">+ Add Student</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="content">
    <?php showFlash(); ?>

    <div class="table-card">
      <div class="table-card-header">
        <h3>All Students (<?= $students ? $students->num_rows : 0 ?>)</h3>
        <form method="GET" style="display:flex;gap:10px">
          <div class="search-box">
            <input type="text" name="search" placeholder="Search by name, roll no..." value="<?= htmlspecialchars($search) ?>" style="min-width:260px">
          </div>
          <button type="submit" class="btn btn-outline">Search</button>
          <?php if($search): ?><a href="list.php" class="btn btn-outline">Clear</a><?php endif; ?>
        </form>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>#</th><th>Student</th><th>Roll No</th><th>Class</th><th>Dept</th>
              <th>Fees Paid</th><th>Status</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if($students && $students->num_rows>0): $i=1; while($s=$students->fetch_assoc()): ?>
            <tr>
              <td style="color:#6b7280"><?= $i++ ?></td>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($s['name']) ?></div>
                <div style="font-size:12px;color:#6b7280"><?= htmlspecialchars($s['email']) ?></div>
              </td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($s['roll_number']) ?></span></td>
              <td><?= htmlspecialchars($s['class_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($s['dept_name'] ?? '—') ?></td>
              <td>
                <?php
                $pct = $s['total_fees']>0 ? round($s['fees_paid']/$s['total_fees']*100) : 0;
                $due = $s['total_fees'] - $s['fees_paid'];
                ?>
                <div style="font-size:13px">₹<?= number_format($s['fees_paid']) ?> / ₹<?= number_format($s['total_fees']) ?></div>
                <div class="att-bar" style="margin-top:4px"><div class="att-fill <?= $pct>=100?'high':($pct>=50?'mid':'low') ?>" style="width:<?= $pct ?>%"></div></div>
                <?php if($due>0): ?><div style="font-size:11px;color:#e74c3c;margin-top:2px">₹<?= number_format($due) ?> due</div><?php endif; ?>
              </td>
              <td><span class="badge <?= $s['is_active']?'badge-green':'badge-red' ?>"><?= $s['is_active']?'Active':'Inactive' ?></span></td>
              <td>
                <div style="display:flex;gap:6px">
                  <a href="view.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline" title="View">👁</a>
                  <?php if(hasRole(['director','coordinator'])): ?>
                  <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline" title="Edit">✏️</a>
                  <a href="delete.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-red" onclick="return confirm('Delete this student?')" title="Delete">🗑</a>
                  <?php endif; ?>
                  <a href="../attendance/student_report.php?student_id=<?= $s['id'] ?>" class="btn btn-sm btn-outline" title="Attendance">📋</a>
                </div>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:#6b7280">No students found</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
