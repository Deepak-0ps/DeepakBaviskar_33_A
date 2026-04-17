<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['faculty','coordinator','director']);
$pageTitle = 'Enter Marks';
$depth = '../';
$role = $_SESSION['role'];

$testId = isset($_GET['test_id']) ? (int)$_GET['test_id'] : 0;
if (!$testId) { flashMessage('error','No test selected.'); redirect('tests/list.php'); }

$test = $conn->query("
    SELECT ct.*, s.name as subject_name, s.code, c.name as class_name, c.id as class_id_val, u.name as teacher_name
    FROM class_tests ct
    JOIN subjects s ON ct.subject_id=s.id
    JOIN classes c ON ct.class_id=c.id
    JOIN teachers t ON ct.teacher_id=t.id
    JOIN users u ON t.user_id=u.id
    WHERE ct.id=$testId
")->fetch_assoc();

if (!$test) { flashMessage('error','Test not found.'); redirect('tests/list.php'); }

$students = $conn->query("
    SELECT s.id, s.roll_number, u.name,
        (SELECT marks_obtained FROM test_results WHERE test_id=$testId AND student_id=s.id) as existing_marks
    FROM students s
    JOIN users u ON s.user_id=u.id
    WHERE s.class_id={$test['class_id_val']}
    ORDER BY s.roll_number
");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_marks'])) {
    $marks = $_POST['marks'] ?? [];
    $saved = 0;
    foreach ($marks as $studentId => $mark) {
        $studentId = (int)$studentId;
        $mark = trim($mark);
        if ($mark === '') continue;
        $mark = min((float)$mark, $test['total_marks']);
        $conn->query("
            INSERT INTO test_results (test_id, student_id, marks_obtained)
            VALUES ($testId, $studentId, $mark)
            ON DUPLICATE KEY UPDATE marks_obtained=$mark
        ");
        $saved++;
    }
    flashMessage('success', "Marks saved for $saved students!");
    redirect("tests/results.php?test_id=$testId");
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . "/../includes/sidebar_{$role}.php";
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">
      <h2>Enter Test Marks</h2>
      <p><?= htmlspecialchars($test['title']) ?> — <?= htmlspecialchars($test['subject_name']) ?></p>
    </div>
    <div class="topbar-right">
      <a href="list.php" class="btn btn-outline">← Back to Tests</a>
    </div>
  </div>
  <div class="content">
    <?php showFlash(); ?>

    <!-- Test Info Banner -->
    <div style="background:var(--ink);color:#fff;border-radius:12px;padding:20px 28px;margin-bottom:24px;display:flex;gap:32px;flex-wrap:wrap;align-items:center">
      <div>
        <div style="font-size:11px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px">Test</div>
        <div style="font-weight:700;font-size:17px"><?= htmlspecialchars($test['title']) ?></div>
      </div>
      <div>
        <div style="font-size:11px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px">Subject</div>
        <div style="font-weight:600;color:var(--gold)"><?= htmlspecialchars($test['subject_name']) ?> (<?= $test['code'] ?>)</div>
      </div>
      <div>
        <div style="font-size:11px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px">Class</div>
        <div style="font-weight:600"><?= htmlspecialchars($test['class_name']) ?></div>
      </div>
      <div>
        <div style="font-size:11px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px">Total Marks</div>
        <div style="font-weight:700;font-size:20px;color:var(--gold)"><?= $test['total_marks'] ?></div>
      </div>
      <div>
        <div style="font-size:11px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px">Date</div>
        <div style="font-weight:600"><?= date('d M Y', strtotime($test['test_date'])) ?></div>
      </div>
    </div>

    <form method="POST">
      <input type="hidden" name="submit_marks" value="1">
      <div class="table-card">
        <div class="table-card-header">
          <h3>👨‍🎓 Enter Marks for Each Student</h3>
          <div style="font-size:13px;color:#6b7280">Max marks: <strong><?= $test['total_marks'] ?></strong> · Leave blank to skip</div>
        </div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Roll No</th>
                <th>Student Name</th>
                <th style="width:200px">Marks Obtained</th>
                <th style="width:120px">Percentage</th>
              </tr>
            </thead>
            <tbody>
            <?php if($students && $students->num_rows > 0): $i=1; while($st=$students->fetch_assoc()): ?>
              <tr>
                <td style="color:#6b7280"><?= $i++ ?></td>
                <td><span class="badge badge-blue"><?= htmlspecialchars($st['roll_number']) ?></span></td>
                <td style="font-weight:600"><?= htmlspecialchars($st['name']) ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px">
                    <input
                      type="number"
                      name="marks[<?= $st['id'] ?>]"
                      value="<?= $st['existing_marks'] !== null ? $st['existing_marks'] : '' ?>"
                      min="0"
                      max="<?= $test['total_marks'] ?>"
                      step="0.5"
                      placeholder="—"
                      class="marks-input"
                      data-max="<?= $test['total_marks'] ?>"
                      data-row="<?= $st['id'] ?>"
                      oninput="updatePct(this)"
                      style="width:100px;padding:10px 14px;border:2px solid #e5e7eb;border-radius:8px;font-size:15px;font-weight:700;outline:none;text-align:center;transition:border-color 0.2s"
                    >
                    <span style="color:#6b7280;font-size:13px">/ <?= $test['total_marks'] ?></span>
                  </div>
                </td>
                <td>
                  <span id="pct_<?= $st['id'] ?>" style="font-size:14px;font-weight:700;color:#6b7280">
                    <?php if($st['existing_marks'] !== null):
                      $p = $test['total_marks'] > 0 ? round($st['existing_marks']/$test['total_marks']*100) : 0;
                      $col = $p>=75?'#27ae60':($p>=40?'#e67e22':'#e74c3c');
                      echo "<span style='color:$col'>$p%</span>";
                    else: echo '—'; endif; ?>
                  </span>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="5" style="text-align:center;padding:40px;color:#6b7280">No students found in this class</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div style="padding:20px;border-top:1px solid #f3f4f6;display:flex;gap:12px">
          <button type="submit" class="btn btn-primary" style="min-width:180px">💾 Save All Marks</button>
          <a href="list.php" class="btn btn-outline">Cancel</a>
        </div>
      </div>
    </form>

  </div>
</div>

<script>
function updatePct(input) {
    const max  = parseFloat(input.dataset.max);
    const val  = parseFloat(input.value);
    const row  = input.dataset.row;
    const span = document.getElementById('pct_' + row);
    if (isNaN(val) || input.value === '') {
        span.innerHTML = '—';
        input.style.borderColor = '#e5e7eb';
        return;
    }
    if (val > max) { input.value = max; }
    const pct = Math.round((Math.min(val, max) / max) * 100);
    const col  = pct >= 75 ? '#27ae60' : (pct >= 40 ? '#e67e22' : '#e74c3c');
    span.innerHTML = `<span style="color:${col}">${pct}%</span>`;
    input.style.borderColor = col;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
