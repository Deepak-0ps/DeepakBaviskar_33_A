<?php
require_once __DIR__ . '/../auth/session.php';
requireRole('director');
$pageTitle = 'Director Dashboard';
$depth = '../';

// Stats
$totalStudents = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
$totalTeachers = $conn->query("SELECT COUNT(*) as c FROM teachers")->fetch_assoc()['c'];
$totalSubjects = $conn->query("SELECT COUNT(*) as c FROM subjects")->fetch_assoc()['c'];

$feesData = $conn->query("SELECT SUM(total_fees) as total, SUM(fees_paid) as paid FROM students")->fetch_assoc();
$totalFees = $feesData['total'] ?? 0;
$paidFees   = $feesData['paid'] ?? 0;
$pendingFees = $totalFees - $paidFees;

$todayDate = date('Y-m-d');
$todayPresent = $conn->query("SELECT COUNT(DISTINCT student_id) as c FROM attendance WHERE date='$todayDate' AND status='present'")->fetch_assoc()['c'];

$lowAttStudents = $conn->query("
    SELECT u.name, s.roll_number,
        ROUND(SUM(a.status='present')/COUNT(a.id)*100,1) as pct
    FROM attendance a
    JOIN students s ON a.student_id=s.id
    JOIN users u ON s.user_id=u.id
    GROUP BY a.student_id
    HAVING pct < 75
    ORDER BY pct ASC
    LIMIT 5
");

$recentPayments = $conn->query("
    SELECT fp.*, u.name as student_name, s.roll_number
    FROM fee_payments fp
    JOIN students s ON fp.student_id=s.id
    JOIN users u ON s.user_id=u.id
    ORDER BY fp.created_at DESC LIMIT 5
");

$notices = $conn->query("SELECT n.*, u.name as posted_by_name FROM notices n JOIN users u ON n.posted_by=u.id ORDER BY n.created_at DESC LIMIT 3");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_director.php';
?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">
      <h2>Director Dashboard</h2>
      <p>Welcome back, <?= htmlspecialchars($_SESSION['name']) ?>. Here's your institution overview.</p>
    </div>
    <div class="topbar-right">
      <span class="topbar-date">📅 <?= date('D, d M Y') ?></span>
    </div>
  </div>

  <div class="content">
    <?php showFlash(); ?>

    <!-- STAT CARDS -->
    <div class="stats-grid">
      <div class="stat-card" style="--accent:#3b82f6;--icon-bg:#eff6ff">
        <div class="stat-icon">👨‍🎓</div>
        <div class="stat-info">
          <div class="val"><?= $totalStudents ?></div>
          <div class="lbl">Total Students</div>
          <div class="sub">↑ Enrolled this year</div>
        </div>
      </div>
      <div class="stat-card" style="--accent:#8b5cf6;--icon-bg:#ede9fe">
        <div class="stat-icon">👨‍🏫</div>
        <div class="stat-info">
          <div class="val"><?= $totalTeachers ?></div>
          <div class="lbl">Total Faculty</div>
          <div class="sub">↑ Active staff</div>
        </div>
      </div>
      <div class="stat-card" style="--accent:#10b981;--icon-bg:#d1fae5">
        <div class="stat-icon">✅</div>
        <div class="stat-info">
          <div class="val"><?= $todayPresent ?></div>
          <div class="lbl">Present Today</div>
          <div class="sub">Students marked present</div>
        </div>
      </div>
      <div class="stat-card" style="--accent:#f59e0b;--icon-bg:#fef3c7">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
          <div class="val">₹<?= number_format($paidFees/1000,1) ?>K</div>
          <div class="lbl">Fees Collected</div>
          <div class="sub">₹<?= number_format($pendingFees/1000,1) ?>K pending</div>
        </div>
      </div>
    </div>

    <!-- FEE PROGRESS -->
    <div class="two-col" style="margin-bottom:24px">
      <div class="table-card">
        <div class="table-card-header">
          <h3>💰 Fee Collection Overview</h3>
          <a href="../students/fees.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div style="padding:24px">
          <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:14px">
            <span>Collected: <strong>₹<?= number_format($paidFees) ?></strong></span>
            <span>Total: <strong>₹<?= number_format($totalFees) ?></strong></span>
          </div>
          <div class="att-bar" style="height:14px;border-radius:7px">
            <?php $feePct = $totalFees > 0 ? round($paidFees/$totalFees*100) : 0; ?>
            <div class="att-fill <?= $feePct>75?'high':($feePct>50?'mid':'low') ?>" style="width:<?= $feePct ?>%"></div>
          </div>
          <div style="text-align:center;margin-top:10px;font-size:13px;color:#6b7280"><?= $feePct ?>% collected</div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px">
            <div style="background:#d1fae5;border-radius:8px;padding:14px;text-align:center">
              <div style="font-size:20px;font-weight:700;color:#065f46">₹<?= number_format($paidFees) ?></div>
              <div style="font-size:12px;color:#065f46;margin-top:4px">Paid</div>
            </div>
            <div style="background:#fee2e2;border-radius:8px;padding:14px;text-align:center">
              <div style="font-size:20px;font-weight:700;color:#991b1b">₹<?= number_format($pendingFees) ?></div>
              <div style="font-size:12px;color:#991b1b;margin-top:4px">Pending</div>
            </div>
          </div>
        </div>
      </div>

      <div class="table-card">
        <div class="table-card-header">
          <h3>⚠️ Low Attendance Students</h3>
          <a href="../attendance/report.php" class="btn btn-sm btn-outline">Full Report</a>
        </div>
        <div class="table-responsive">
          <table>
            <thead><tr><th>Student</th><th>Roll No</th><th>Attendance</th></tr></thead>
            <tbody>
            <?php if ($lowAttStudents && $lowAttStudents->num_rows > 0): while($r=$lowAttStudents->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><span class="badge badge-blue"><?= $r['roll_number'] ?></span></td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px">
                    <div class="att-bar" style="flex:1"><div class="att-fill low" style="width:<?= $r['pct'] ?>%"></div></div>
                    <span style="font-size:12px;font-weight:700;color:#e74c3c"><?= $r['pct'] ?>%</span>
                  </div>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="3" style="text-align:center;color:#6b7280;padding:20px">✅ All students have adequate attendance</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- RECENT PAYMENTS & NOTICES -->
    <div class="two-col">
      <div class="table-card">
        <div class="table-card-header">
          <h3>🧾 Recent Fee Payments</h3>
        </div>
        <div class="table-responsive">
          <table>
            <thead><tr><th>Student</th><th>Amount</th><th>Date</th><th>Mode</th></tr></thead>
            <tbody>
            <?php while($r=$recentPayments->fetch_assoc()): ?>
              <tr>
                <td>
                  <div style="font-weight:600"><?= htmlspecialchars($r['student_name']) ?></div>
                  <div style="font-size:12px;color:#6b7280"><?= $r['roll_number'] ?></div>
                </td>
                <td><strong style="color:#27ae60">₹<?= number_format($r['amount']) ?></strong></td>
                <td style="font-size:13px"><?= date('d M Y', strtotime($r['payment_date'])) ?></td>
                <td><span class="badge badge-blue"><?= ucfirst($r['payment_mode']) ?></span></td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <h3 style="font-size:16px;font-weight:700">📢 Notices</h3>
        </div>
        <?php while($n=$notices->fetch_assoc()): ?>
          <div class="notice-card">
            <h4><?= htmlspecialchars($n['title']) ?></h4>
            <p><?= htmlspecialchars(substr($n['content'],0,100)) ?>...</p>
            <div class="notice-meta">By <?= htmlspecialchars($n['posted_by_name']) ?> · <?= date('d M Y', strtotime($n['created_at'])) ?></div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
