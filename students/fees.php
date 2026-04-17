<?php
require_once __DIR__ . '/../auth/session.php';
requireLogin();
$pageTitle = 'Fee Management';
$depth = '../';
$role  = $_SESSION['role'];

// Student-only: show only their own fees
$filterStudentId = 0;
if ($role === 'student') {
    $filterStudentId = $_SESSION['linked_id'] ?? 0;
} elseif (isset($_GET['student_id'])) {
    $filterStudentId = (int)$_GET['student_id'];
}

// Handle payment addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasRole(['director','coordinator'])) {
    $sid      = (int)$_POST['student_id'];
    $amount   = (float)$_POST['amount'];
    $date     = sanitize($conn, $_POST['payment_date']);
    $mode     = sanitize($conn, $_POST['payment_mode']);
    $receipt  = sanitize($conn, $_POST['receipt_number']);
    $desc     = sanitize($conn, $_POST['description']);
    $by       = $_SESSION['user_id'];

    $exists = $conn->query("SELECT id FROM fee_payments WHERE receipt_number='$receipt'")->num_rows;
    if ($exists) {
        flashMessage('error', 'Receipt number already exists!');
    } else {
        $conn->query("INSERT INTO fee_payments (student_id,amount,payment_date,payment_mode,receipt_number,description,received_by)
            VALUES ($sid,$amount,'$date','$mode','$receipt','$desc',$by)");
        // Update fees_paid
        $conn->query("UPDATE students SET fees_paid = (SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE student_id=$sid) WHERE id=$sid");
        flashMessage('success', 'Payment recorded successfully!');
        redirect('students/fees.php' . ($filterStudentId ? "?student_id=$filterStudentId" : ''));
    }
}

// Fetch students list for dropdown
$studentsList = $conn->query("
    SELECT s.id, s.roll_number, u.name, s.total_fees, s.fees_paid
    FROM students s JOIN users u ON s.user_id=u.id ORDER BY u.name
");

// Fetch fee records
$whereClause = $filterStudentId ? "WHERE fp.student_id=$filterStudentId" : '';
$payments = $conn->query("
    SELECT fp.*, u.name as student_name, s.roll_number,
           ub.name as received_by_name
    FROM fee_payments fp
    JOIN students s ON fp.student_id=s.id
    JOIN users u ON s.user_id=u.id
    LEFT JOIN users ub ON fp.received_by=ub.id
    $whereClause
    ORDER BY fp.payment_date DESC
");

// Summary
$summary = $conn->query("
    SELECT SUM(s.total_fees) as total_fees, SUM(s.fees_paid) as paid_fees
    FROM students s
    " . ($filterStudentId ? "WHERE s.id=$filterStudentId" : '')
)->fetch_assoc();
$totalFees   = $summary['total_fees'] ?? 0;
$paidFees    = $summary['paid_fees']  ?? 0;
$pendingFees = $totalFees - $paidFees;

include __DIR__ . '/../includes/header.php';
include __DIR__ . "/../includes/sidebar_{$role}.php";
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">
      <h2>Fee Management</h2>
      <p><?= $filterStudentId ? 'Showing fees for selected student' : 'All student fee records' ?></p>
    </div>
    <?php if(hasRole(['director','coordinator'])): ?>
    <div class="topbar-right">
      <button onclick="document.getElementById('addPaymentModal').style.display='flex'" class="btn btn-green">+ Record Payment</button>
    </div>
    <?php endif; ?>
  </div>

  <div class="content">
    <?php showFlash(); ?>

    <!-- Summary Cards -->
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr)">
      <div class="stat-card" style="--accent:#3b82f6;--icon-bg:#eff6ff">
        <div class="stat-icon">💳</div>
        <div class="stat-info">
          <div class="val">₹<?= number_format($totalFees) ?></div>
          <div class="lbl">Total Fees</div>
        </div>
      </div>
      <div class="stat-card" style="--accent:#10b981;--icon-bg:#d1fae5">
        <div class="stat-icon">✅</div>
        <div class="stat-info">
          <div class="val">₹<?= number_format($paidFees) ?></div>
          <div class="lbl">Collected</div>
          <div class="sub"><?= $totalFees>0?round($paidFees/$totalFees*100):0 ?>% of total</div>
        </div>
      </div>
      <div class="stat-card" style="--accent:#ef4444;--icon-bg:#fee2e2">
        <div class="stat-icon">⏳</div>
        <div class="stat-info">
          <div class="val">₹<?= number_format($pendingFees) ?></div>
          <div class="lbl">Pending</div>
        </div>
      </div>
    </div>

    <!-- Payments Table -->
    <div class="table-card">
      <div class="table-card-header">
        <h3>💰 Payment Records</h3>
        <div style="display:flex;gap:10px;align-items:center">
          <?php if(!$filterStudentId && hasRole(['director','coordinator'])): ?>
          <form method="GET" style="display:flex;gap:8px">
            <select name="student_id" onchange="this.form.submit()" style="padding:8px 12px;border:2px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none">
              <option value="">All Students</option>
              <?php $studentsList->data_seek(0); while($s=$studentsList->fetch_assoc()): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['roll_number'] ?>)</option>
              <?php endwhile; ?>
            </select>
          </form>
          <?php endif; ?>
          <button onclick="window.print()" class="btn btn-outline btn-sm">🖨 Print</button>
        </div>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <?php if(!$filterStudentId): ?><th>Student</th><?php endif; ?>
              <th>Receipt #</th><th>Amount</th><th>Date</th><th>Mode</th><th>Description</th><th>Received By</th>
            </tr>
          </thead>
          <tbody>
          <?php if($payments && $payments->num_rows > 0): while($p = $payments->fetch_assoc()): ?>
            <tr>
              <?php if(!$filterStudentId): ?>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($p['student_name']) ?></div>
                <div style="font-size:12px;color:#6b7280"><?= $p['roll_number'] ?></div>
              </td>
              <?php endif; ?>
              <td><span class="badge badge-green"><?= htmlspecialchars($p['receipt_number']) ?></span></td>
              <td><strong style="color:#27ae60;font-size:16px">₹<?= number_format($p['amount']) ?></strong></td>
              <td><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
              <td><span class="badge badge-blue"><?= ucfirst($p['payment_mode']) ?></span></td>
              <td style="font-size:13px;color:#6b7280"><?= htmlspecialchars($p['description'] ?? '—') ?></td>
              <td style="font-size:13px"><?= htmlspecialchars($p['received_by_name'] ?? '—') ?></td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:#6b7280">No payment records found</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Student-wise Fee Status (director/coordinator only) -->
    <?php if(hasRole(['director','coordinator']) && !$filterStudentId): ?>
    <div class="table-card">
      <div class="table-card-header"><h3>📊 Student-wise Fee Status</h3></div>
      <div class="table-responsive">
        <table>
          <thead><tr><th>#</th><th>Student</th><th>Roll No</th><th>Total Fees</th><th>Paid</th><th>Pending</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
          <?php $studentsList->data_seek(0); $i=1; while($s=$studentsList->fetch_assoc()):
            $due = $s['total_fees'] - $s['fees_paid'];
            $pct = $s['total_fees']>0 ? round($s['fees_paid']/$s['total_fees']*100) : 0;
          ?>
            <tr>
              <td style="color:#6b7280"><?= $i++ ?></td>
              <td style="font-weight:600"><?= htmlspecialchars($s['name']) ?></td>
              <td><span class="badge badge-blue"><?= $s['roll_number'] ?></span></td>
              <td>₹<?= number_format($s['total_fees']) ?></td>
              <td style="color:#27ae60;font-weight:600">₹<?= number_format($s['fees_paid']) ?></td>
              <td style="color:<?= $due>0?'#e74c3c':'#27ae60' ?>;font-weight:600">₹<?= number_format($due) ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:8px;min-width:120px">
                  <div class="att-bar" style="flex:1"><div class="att-fill <?= $pct>=100?'high':($pct>=50?'mid':'low') ?>" style="width:<?= $pct ?>%"></div></div>
                  <span style="font-size:12px;font-weight:700"><?= $pct ?>%</span>
                </div>
              </td>
              <td><a href="fees.php?student_id=<?= $s['id'] ?>" class="btn btn-sm btn-outline">View</a></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ADD PAYMENT MODAL -->
<?php if(hasRole(['director','coordinator'])): ?>
<div id="addPaymentModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;padding:36px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;position:relative">
    <button onclick="document.getElementById('addPaymentModal').style.display='none'" style="position:absolute;top:16px;right:20px;background:none;border:none;font-size:22px;cursor:pointer;color:#6b7280">✕</button>
    <h3 style="font-size:20px;font-weight:700;margin-bottom:24px">💰 Record Fee Payment</h3>
    <form method="POST">
      <div class="form-group" style="margin-bottom:18px">
        <label>Select Student *</label>
        <select name="student_id" required style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;width:100%;font-size:14px;outline:none">
          <option value="">— Select Student —</option>
          <?php $studentsList->data_seek(0); while($s=$studentsList->fetch_assoc()): ?>
          <option value="<?= $s['id'] ?>" <?= $filterStudentId==$s['id']?'selected':'' ?>>
            <?= htmlspecialchars($s['name']) ?> (<?= $s['roll_number'] ?>) — Due: ₹<?= number_format($s['total_fees']-$s['fees_paid']) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Amount (₹) *</label>
          <input type="number" name="amount" required min="1" step="100" placeholder="e.g. 25000">
        </div>
        <div class="form-group">
          <label>Payment Date *</label>
          <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label>Payment Mode *</label>
          <select name="payment_mode" required style="padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;width:100%;font-size:14px;outline:none">
            <option value="cash">Cash</option>
            <option value="online">Online Transfer</option>
            <option value="cheque">Cheque</option>
            <option value="dd">DD</option>
          </select>
        </div>
        <div class="form-group">
          <label>Receipt Number *</label>
          <input type="text" name="receipt_number" required placeholder="e.g. REC2024001">
        </div>
      </div>
      <div class="form-group" style="margin-top:6px">
        <label>Description</label>
        <input type="text" name="description" placeholder="e.g. First installment, Full fees, etc.">
      </div>
      <div style="display:flex;gap:12px;margin-top:24px">
        <button type="submit" class="btn btn-green" style="flex:1">✓ Save Payment</button>
        <button type="button" onclick="document.getElementById('addPaymentModal').style.display='none'" class="btn btn-outline">Cancel</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
