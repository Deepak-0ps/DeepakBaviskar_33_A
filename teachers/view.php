<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director','coordinator']);
$pageTitle = 'Teacher Profile';
$depth = '../';

$id = (int)($_GET['id'] ?? 0);
$teacher = $conn->query("
    SELECT t.*, u.name, u.email, u.phone, u.address, u.is_active, u.created_at,
           d.name as dept_name
    FROM teachers t JOIN users u ON t.user_id=u.id
    LEFT JOIN departments d ON t.department_id=d.id WHERE t.id=$id
")->fetch_assoc();
if (!$teacher) { flashMessage('error','Teacher not found'); redirect('teachers/list.php'); }

$subjects = $conn->query("
    SELECT s.*, c.name as class_name FROM subjects s
    LEFT JOIN classes c ON s.class_id=c.id WHERE s.teacher_id=$id
");
$tests = $conn->query("
    SELECT ct.*, s.name as subject_name FROM class_tests ct
    JOIN subjects s ON ct.subject_id=s.id
    WHERE ct.teacher_id=$id ORDER BY ct.test_date DESC LIMIT 5
");
$totalTests   = $conn->query("SELECT COUNT(*) as c FROM class_tests WHERE teacher_id=$id")->fetch_assoc()['c'];
$totalSubjects= $conn->query("SELECT COUNT(*) as c FROM subjects WHERE teacher_id=$id")->fetch_assoc()['c'];

include __DIR__ . '/../includes/header.php';
$role = $_SESSION['role'];
include __DIR__ . "/../includes/sidebar_{$role}.php";
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title"><h2>Teacher Profile</h2><p><?= htmlspecialchars($teacher['name']) ?> — <?= htmlspecialchars($teacher['employee_id']) ?></p></div>
    <div class="topbar-right">
      <a href="list.php" class="btn btn-outline">← Back</a>
      <a href="edit.php?id=<?= $id ?>" class="btn btn-primary">✏️ Edit</a>
    </div>
  </div>
  <div class="content">
    <?php showFlash(); ?>

    <div class="form-card" style="margin-bottom:24px">
      <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap">
        <div style="width:80px;height:80px;border-radius:50%;background:var(--ink);color:var(--gold);display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;flex-shrink:0">
          <?= strtoupper(substr($teacher['name'],0,1)) ?>
        </div>
        <div style="flex:1">
          <h2 style="font-size:22px;font-weight:700"><?= htmlspecialchars($teacher['name']) ?></h2>
          <div style="color:#6b7280;font-size:14px;margin-top:4px"><?= htmlspecialchars($teacher['designation'] ?? '') ?> · <?= htmlspecialchars($teacher['dept_name'] ?? '') ?></div>
          <div style="display:flex;gap:16px;margin-top:10px;flex-wrap:wrap;font-size:14px">
            <span>📧 <?= htmlspecialchars($teacher['email']) ?></span>
            <span>📞 <?= htmlspecialchars($teacher['phone'] ?? '—') ?></span>
            <span>🎓 <?= htmlspecialchars($teacher['qualification'] ?? '—') ?></span>
            <span>📅 Joined: <?= $teacher['joining_date'] ? date('d M Y', strtotime($teacher['joining_date'])) : '—' ?></span>
          </div>
          <div style="margin-top:12px;display:flex;gap:10px">
            <span class="badge badge-purple"><?= $teacher['employee_id'] ?></span>
            <span class="badge <?= $teacher['is_active']?'badge-green':'badge-red' ?>"><?= $teacher['is_active']?'Active':'Inactive' ?></span>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div style="background:#f9fafb;border-radius:10px;padding:16px;text-align:center">
            <div style="font-size:24px;font-weight:700;color:var(--ink)"><?= $totalSubjects ?></div>
            <div style="font-size:12px;color:#6b7280;margin-top:4px">Subjects</div>
          </div>
          <div style="background:#f9fafb;border-radius:10px;padding:16px;text-align:center">
            <div style="font-size:24px;font-weight:700;color:var(--ink)"><?= $totalTests ?></div>
            <div style="font-size:12px;color:#6b7280;margin-top:4px">Tests Created</div>
          </div>
        </div>
      </div>
    </div>

    <div class="two-col">
      <div class="table-card">
        <div class="table-card-header"><h3>📚 Assigned Subjects</h3></div>
        <div class="table-responsive">
          <table>
            <thead><tr><th>Subject</th><th>Code</th><th>Class</th><th>Credits</th></tr></thead>
            <tbody>
            <?php if($subjects && $subjects->num_rows>0): while($s=$subjects->fetch_assoc()): ?>
              <tr>
                <td style="font-weight:600"><?= htmlspecialchars($s['name']) ?></td>
                <td><span class="badge badge-blue"><?= $s['code'] ?></span></td>
                <td><?= htmlspecialchars($s['class_name'] ?? '—') ?></td>
                <td><?= $s['credits'] ?></td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="4" style="text-align:center;color:#6b7280;padding:20px">No subjects assigned</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="table-card">
        <div class="table-card-header"><h3>📝 Recent Tests</h3></div>
        <div class="table-responsive">
          <table>
            <thead><tr><th>Title</th><th>Subject</th><th>Date</th><th>Marks</th></tr></thead>
            <tbody>
            <?php if($tests && $tests->num_rows>0): while($t=$tests->fetch_assoc()): ?>
              <tr>
                <td style="font-weight:600;font-size:13px"><?= htmlspecialchars($t['title']) ?></td>
                <td style="font-size:13px"><?= htmlspecialchars($t['subject_name']) ?></td>
                <td style="font-size:13px"><?= date('d M Y', strtotime($t['test_date'])) ?></td>
                <td><?= $t['total_marks'] ?></td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="4" style="text-align:center;color:#6b7280;padding:20px">No tests created</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
