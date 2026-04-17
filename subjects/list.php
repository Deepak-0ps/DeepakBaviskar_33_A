<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director','coordinator','faculty']);
$pageTitle = 'Subjects';
$depth = '../';
$role = $_SESSION['role'];

$subjects = $conn->query("
    SELECT s.*, d.name as dept_name, c.name as class_name, u.name as teacher_name
    FROM subjects s
    LEFT JOIN departments d ON s.department_id=d.id
    LEFT JOIN classes c ON s.class_id=c.id
    LEFT JOIN teachers t ON s.teacher_id=t.id
    LEFT JOIN users u ON t.user_id=u.id
    ORDER BY s.name
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . "/../includes/sidebar_{$role}.php";
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title"><h2>Subjects</h2><p>All subjects offered this semester</p></div>
    <div class="topbar-right">
      <?php if(hasRole(['director','coordinator'])): ?>
      <a href="add.php" class="btn btn-primary">+ Add Subject</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="content">
    <?php showFlash(); ?>

    <div class="table-card">
      <div class="table-card-header">
        <h3>All Subjects (<?= $subjects ? $subjects->num_rows : 0 ?>)</h3>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr><th>#</th><th>Subject</th><th>Code</th><th>Department</th><th>Class</th><th>Faculty</th><th>Credits</th><th>Actions</th></tr>
          </thead>
          <tbody>
          <?php if($subjects && $subjects->num_rows > 0): $i=1; while($s=$subjects->fetch_assoc()): ?>
            <tr>
              <td style="color:#6b7280"><?= $i++ ?></td>
              <td style="font-weight:700;font-size:15px"><?= htmlspecialchars($s['name']) ?></td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($s['code']) ?></span></td>
              <td style="font-size:13px"><?= htmlspecialchars($s['dept_name'] ?? '—') ?></td>
              <td><span class="badge badge-purple"><?= htmlspecialchars($s['class_name'] ?? '—') ?></span></td>
              <td style="font-size:13px"><?= htmlspecialchars($s['teacher_name'] ?? 'Unassigned') ?></td>
              <td>
                <div style="display:flex;gap:4px">
                  <?php for($c=0;$c<$s['credits'];$c++): ?>
                  <div style="width:10px;height:10px;border-radius:50%;background:var(--gold)"></div>
                  <?php endfor; ?>
                  <span style="font-size:12px;color:#6b7280;margin-left:6px"><?= $s['credits'] ?></span>
                </div>
              </td>
              <td>
                <?php if(hasRole(['director','coordinator'])): ?>
                <div style="display:flex;gap:6px">
                  <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline">✏️ Edit</a>
                  <a href="delete.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-red" onclick="return confirm('Delete this subject?')">🗑</a>
                </div>
                <?php else: ?>—<?php endif; ?>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:#6b7280">No subjects found</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
