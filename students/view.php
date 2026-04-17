<?php
require_once __DIR__ . '/../auth/session.php';
requireRole(['director','coordinator','faculty']);
$pageTitle = 'Student Profile';
$depth = '../';

$id = (int)($_GET['id'] ?? 0);
$student = $conn->query("
    SELECT s.*, u.name, u.email, u.phone, u.address, u.is_active,
           c.name as class_name, d.name as dept_name
    FROM students s
    JOIN users u ON s.user_id=u.id
    LEFT JOIN classes c ON s.class_id=c.id
    LEFT JOIN departments d ON s.department_id=d.id
    WHERE s.id=$id
")->fetch_assoc();

if (!$student) { flashMessage('error','Student not found'); redirect('students/list.php'); }

// Attendance summary per subject
$attSummary = $conn->query("
    SELECT sub.name as subject_name, sub.code,
        COUNT(a.id) as total,
        SUM(a.status='present') as present,
        ROUND(SUM(a.status='present')/COUNT(a.id)*100,1) as pct
    FROM attendance a
    JOIN subjects sub ON a.subject_id=sub.id
    WHERE a.student_id=$id
    GROUP BY a.subject_id
");

// Fee payments
$payments = $conn->query("SELECT * FROM fee_payments WHERE student_id=$id ORDER BY payment_date DESC");

// Test results
$results = $conn->query("
    SELECT tr.*, ct.title, ct.total_marks, ct.test_date, sub.name as subject_name
    FROM test_results tr
    JOIN class_tests ct ON tr.test_id=ct.id
    JOIN subjects sub ON ct.subject_id=sub.id
    WHERE tr.student_id=$id
    ORDER BY ct.test_date DESC
");

include __DIR__ . '/../includes/header.php';
$role = $_SESSION['role'];
include __DIR__ . "/../includes/sidebar_{$role}.php";
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title"><h2>Student Profile</h2><p><?= htmlspecialchars($student['name']) ?> — <?= htmlspecialchars($student['roll_number']) ?></p></div>
    <div class="topbar-right">
      <a href="list.php" class="btn btn-outline">← Back</a>
      <?php if(hasRole(['director','coordinator'])): ?>
      <a href="edit.php?id=<?= $id ?>" class="btn btn-primary">✏️ Edit</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="content">
    <?php showFlash(); ?>

    <!-- Profile Header -->
    <div class="form-card" style="margin-bottom:24px">
      <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap">
        <div style="width:80px;height:80px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;color:var(--ink);flex-shrink:0">
          <?= strtoupper(substr($student['name'],0,1)) ?>
        </div>
        <div style="flex:1">
          <h2 style="font-size:22px;font-weight:700"><?= htmlspecialchars($student['name']) ?></h2>
          <div style="display:flex;gap:16px;margin-top:8px;flex-wrap:wrap">
            <span>📧 <?= htmlspecialchars($student['email']) ?></span>
            <span>📞 <?= htmlspecialchars($student['phone'] ?? '—') ?></span>
            <span>🎓 <?= htmlspecialchars($student['class_name'] ?? '—') ?></span>
            <span>🏫 <?= htmlspecialchars($student['dept_name'] ?? '—') ?></span>
          </div>
          <div style="display:flex;gap:10px;margin-top:12px">
            <span class="badge badge-blue"><?= $student['roll_number'] ?></span>
            <span class="badge <?= $student['is_active']?'badge-green':'badge-red' ?>"><?= $student['is_active']?'Active':'Inactive' ?></span>
            <?php
            $due = $student['total_fees'] - $student['fees_paid'];
            if($due>0): ?><span class="badge badge-red">₹<?= number_format($due) ?> Fees Due</span><?php endif;
            ?>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;text-align:center">
          <div style="background:#f9fafb;border-radius:10px;padding:16px">
            <div style="font-size:20px;font-weight:700;color:#27ae60">₹<?= number_format($student['fees_paid']) ?></div>
            <div style="font-size:12px;color:#6b7280">Fees Paid</div>
          </div>
          <div style="background:#f9fafb;border-radius:10px;padding:16px">
            <div style="font-size:20px;font-weight:700;color:#e74c3c">₹<?= number_format($due) ?></div>
            <div style="font-size:12px;color:#6b7280">Pending</div>
          </div>
        </div>
      </div>
    </div>

    <div class="two-col">
      <!-- Attendance Summary -->
      <div class="table-card">
        <div class="table-card-header">
          <h3>📋 Attendance by Subject</h3>
          <a href="../attendance/student_report.php?student_id=<?= $id ?>" class="btn btn-sm btn-outline">Full Report</a>
        </div>
        <div style="padding:20px">
          <?php if($attSummary && $attSummary->num_rows>0): while($a=$attSummary->fetch_assoc()): ?>
            <div style="margin-bottom:16px">
              <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:6px">
                <div>
                  <span style="font-weight:600"><?= htmlspecialchars($a['subject_name']) ?></span>
                  <span style="font-size:12px;color:#6b7280;margin-left:6px"><?= $a['code'] ?></span>
                </div>
                <span style="font-weight:700;color:<?= $a['pct']>=75?'#27ae60':($a['pct']>=60?'#e67e22':'#e74c3c') ?>">
                  <?= $a['pct'] ?>%
                  <?php if($a['pct']<75): ?><span style="font-size:11px;color:#e74c3c"> ⚠ Low</span><?php endif; ?>
                </span>
              </div>
              <div class="att-bar">
                <div class="att-fill <?= $a['pct']>=75?'high':($a['pct']>=60?'mid':'low') ?>" style="width:<?= $a['pct'] ?>%"></div>
              </div>
              <div style="font-size:12px;color:#6b7280;margin-top:4px"><?= $a['present'] ?>/<?= $a['total'] ?> classes attended</div>
            </div>
          <?php endwhile; else: ?>
            <p style="color:#6b7280;text-align:center;padding:20px">No attendance records found</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Test Results -->
      <div class="table-card">
        <div class="table-card-header">
          <h3>📝 Test Results</h3>
        </div>
        <div class="table-responsive">
          <table>
            <thead><tr><th>Test</th><th>Subject</th><th>Marks</th><th>Date</th></tr></thead>
            <tbody>
            <?php if($results && $results->num_rows>0): while($r=$results->fetch_assoc()):
              $pct = $r['total_marks']>0 ? round($r['marks_obtained']/$r['total_marks']*100) : 0;
            ?>
              <tr>
                <td style="font-weight:600"><?= htmlspecialchars($r['title']) ?></td>
                <td><?= htmlspecialchars($r['subject_name']) ?></td>
                <td>
                  <span style="font-weight:700;color:<?= $pct>=75?'#27ae60':($pct>=40?'#e67e22':'#e74c3c') ?>">
                    <?= $r['marks_obtained'] ?>/<?= $r['total_marks'] ?>
                  </span>
                  <span style="font-size:12px;color:#6b7280"> (<?= $pct ?>%)</span>
                </td>
                <td style="font-size:13px"><?= date('d M Y', strtotime($r['test_date'])) ?></td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="4" style="text-align:center;color:#6b7280;padding:20px">No test results found</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Fee Payment History -->
    <div class="table-card">
      <div class="table-card-header">
        <h3>💰 Fee Payment History</h3>
        <?php if(hasRole(['director','coordinator'])): ?>
        <a href="fees.php?student_id=<?= $id ?>" class="btn btn-sm btn-green">+ Add Payment</a>
        <?php endif; ?>
      </div>
      <div class="table-responsive">
        <table>
          <thead><tr><th>Receipt #</th><th>Amount</th><th>Date</th><th>Mode</th><th>Description</th></tr></thead>
          <tbody>
          <?php if($payments && $payments->num_rows>0): while($p=$payments->fetch_assoc()): ?>
            <tr>
              <td><span class="badge badge-green"><?= htmlspecialchars($p['receipt_number']) ?></span></td>
              <td><strong style="color:#27ae60">₹<?= number_format($p['amount']) ?></strong></td>
              <td><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
              <td><span class="badge badge-blue"><?= ucfirst($p['payment_mode']) ?></span></td>
              <td style="font-size:13px;color:#6b7280"><?= htmlspecialchars($p['description'] ?? '—') ?></td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="5" style="text-align:center;color:#6b7280;padding:20px">No payment records</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
