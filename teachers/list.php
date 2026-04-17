<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director','coordinator']);
$pageTitle = 'Teachers';
$depth = '../';

$search = isset($_GET['search']) ? sanitize($conn, $_GET['search']) : '';
$where  = $search ? "AND (u.name LIKE '%$search%' OR t.employee_id LIKE '%$search%' OR u.email LIKE '%$search%')" : '';

$teachers = $conn->query("
    SELECT t.*, u.name, u.email, u.phone, u.is_active,
           d.name as dept_name
    FROM teachers t
    JOIN users u ON t.user_id=u.id
    LEFT JOIN departments d ON t.department_id=d.id
    WHERE 1=1 $where
    ORDER BY u.name ASC
");

include __DIR__ . '/../includes/header.php';
$role = $_SESSION['role'];
include __DIR__ . "/../includes/sidebar_{$role}.php";
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title"><h2>Teachers / Faculty</h2><p>Manage all teaching staff</p></div>
    <div class="topbar-right">
      <a href="add.php" class="btn btn-primary">+ Add Teacher</a>
    </div>
  </div>
  <div class="content">
    <?php showFlash(); ?>

    <div class="table-card">
      <div class="table-card-header">
        <h3>All Faculty (<?= $teachers ? $teachers->num_rows : 0 ?>)</h3>
        <form method="GET" style="display:flex;gap:10px">
          <div class="search-box">
            <input type="text" name="search" placeholder="Search name, employee ID..." value="<?= htmlspecialchars($search) ?>" style="min-width:260px">
          </div>
          <button type="submit" class="btn btn-outline">Search</button>
          <?php if($search): ?><a href="list.php" class="btn btn-outline">Clear</a><?php endif; ?>
        </form>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr><th>#</th><th>Faculty</th><th>Emp ID</th><th>Department</th><th>Designation</th><th>Phone</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
          <?php if($teachers && $teachers->num_rows > 0): $i=1; while($t=$teachers->fetch_assoc()): ?>
            <tr>
              <td style="color:#6b7280"><?= $i++ ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:12px">
                  <div style="width:38px;height:38px;border-radius:50%;background:var(--ink);color:var(--gold);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;flex-shrink:0">
                    <?= strtoupper(substr($t['name'],0,1)) ?>
                  </div>
                  <div>
                    <div style="font-weight:600"><?= htmlspecialchars($t['name']) ?></div>
                    <div style="font-size:12px;color:#6b7280"><?= htmlspecialchars($t['email']) ?></div>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-purple"><?= htmlspecialchars($t['employee_id']) ?></span></td>
              <td><?= htmlspecialchars($t['dept_name'] ?? '—') ?></td>
              <td style="font-size:13px"><?= htmlspecialchars($t['designation'] ?? '—') ?></td>
              <td style="font-size:13px"><?= htmlspecialchars($t['phone'] ?? '—') ?></td>
              <td><span class="badge <?= $t['is_active']?'badge-green':'badge-red' ?>"><?= $t['is_active']?'Active':'Inactive' ?></span></td>
              <td>
                <div style="display:flex;gap:6px">
                  <a href="view.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline" title="View">👁</a>
                  <a href="edit.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline" title="Edit">✏️</a>
                  <a href="delete.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-red" onclick="return confirm('Delete this teacher? This cannot be undone.')" title="Delete">🗑</a>
                </div>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:#6b7280">No teachers found</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
