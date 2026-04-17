<?php
require_once __DIR__ . '/../auth/session.php';

if (isLoggedIn()) {
    redirect('dashboard/' . $_SESSION['role'] . '.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($conn, $_POST['email']);
    $password = $_POST['password'];
    $role = sanitize($conn, $_POST['role']);

    $result = $conn->query("SELECT * FROM users WHERE email='$email' AND role='$role' AND is_active=1");
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['pic']     = $user['profile_pic'];

            // Get linked teacher/student id
            if ($user['role'] === 'student') {
                $r = $conn->query("SELECT id FROM students WHERE user_id={$user['id']}");
                if ($r && $r->num_rows) $_SESSION['linked_id'] = $r->fetch_assoc()['id'];
            } elseif (in_array($user['role'], ['faculty','coordinator'])) {
                $r = $conn->query("SELECT id FROM teachers WHERE user_id={$user['id']}");
                if ($r && $r->num_rows) $_SESSION['linked_id'] = $r->fetch_assoc()['id'];
            }

            redirect('dashboard/' . $user['role'] . '.php');
        }
    }
    $error = 'Invalid credentials. Please check email, password and role.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — College Management System</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
  --ink:#0d1117;--paper:#f7f4ef;--gold:#c8973a;--gold-light:#e8c87a;
  --cream:#ede9e0;--red:#c0392b;--green:#27ae60;
}
body{min-height:100vh;display:flex;font-family:'DM Sans',sans-serif;background:var(--ink);}

.login-visual{
  flex:1;background:linear-gradient(135deg,#0d1117 0%,#1a2236 50%,#0d1117 100%);
  display:flex;flex-direction:column;justify-content:center;align-items:center;
  padding:60px;position:relative;overflow:hidden;
}
.login-visual::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse at 30% 60%,rgba(200,151,58,0.15) 0%,transparent 60%);
}
.crest{
  width:120px;height:120px;border:3px solid var(--gold);border-radius:50%;
  display:flex;align-items:center;justify-content:center;margin-bottom:32px;
  position:relative;animation:pulse 3s ease-in-out infinite;
}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(200,151,58,0.3)}50%{box-shadow:0 0 0 20px rgba(200,151,58,0)}}
.crest svg{width:60px;height:60px;fill:var(--gold);}
.login-visual h1{
  font-family:'Playfair Display',serif;font-size:clamp(28px,3vw,42px);
  font-weight:900;color:#fff;text-align:center;line-height:1.2;margin-bottom:16px;
}
.login-visual p{color:rgba(255,255,255,0.5);text-align:center;font-size:15px;max-width:320px;line-height:1.6;}
.divider-line{width:60px;height:2px;background:var(--gold);margin:24px auto;}
.role-badges{display:flex;gap:12px;margin-top:40px;flex-wrap:wrap;justify-content:center;}
.role-badge{
  padding:8px 18px;border:1px solid rgba(200,151,58,0.4);border-radius:30px;
  color:rgba(255,255,255,0.6);font-size:13px;font-weight:500;
}

.login-form-side{
  width:480px;background:var(--paper);display:flex;flex-direction:column;
  justify-content:center;padding:60px 50px;position:relative;
}
.form-top{margin-bottom:40px;}
.form-top h2{font-family:'Playfair Display',serif;font-size:30px;color:var(--ink);margin-bottom:6px;}
.form-top p{color:#6b7280;font-size:14px;}

.form-group{margin-bottom:22px;}
label{display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:8px;letter-spacing:0.5px;text-transform:uppercase;}
input,select{
  width:100%;padding:14px 16px;border:2px solid #ddd;border-radius:10px;
  font-family:'DM Sans',sans-serif;font-size:15px;color:var(--ink);
  background:#fff;transition:all 0.2s;outline:none;
}
input:focus,select:focus{border-color:var(--gold);box-shadow:0 0 0 4px rgba(200,151,58,0.1);}
select{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236b7280' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 16px center;}

.btn-login{
  width:100%;padding:16px;background:var(--ink);color:#fff;border:none;border-radius:10px;
  font-family:'DM Sans',sans-serif;font-size:16px;font-weight:600;cursor:pointer;
  transition:all 0.3s;letter-spacing:0.3px;position:relative;overflow:hidden;
}
.btn-login:hover{background:var(--gold);transform:translateY(-2px);box-shadow:0 8px 24px rgba(200,151,58,0.4);}
.btn-login::after{
  content:'→';position:absolute;right:-20px;top:50%;transform:translateY(-50%);
  transition:right 0.3s;
}
.btn-login:hover::after{right:20px;}

.error-msg{
  background:#fef2f2;border:1px solid #fca5a5;color:var(--red);
  padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:20px;
}
.demo-creds{
  margin-top:30px;padding:16px;background:var(--cream);border-radius:10px;
  border-left:3px solid var(--gold);
}
.demo-creds h4{font-size:12px;text-transform:uppercase;letter-spacing:1px;color:var(--gold);margin-bottom:10px;}
.demo-creds table{width:100%;font-size:13px;border-collapse:collapse;}
.demo-creds td{padding:4px 0;color:#374151;}
.demo-creds td:first-child{color:#6b7280;width:100px;}

@media(max-width:900px){
  .login-visual{display:none;}
  .login-form-side{width:100%;padding:40px 30px;}
}
</style>
</head>
<body>

<div class="login-visual">
  <div class="crest">
    <svg viewBox="0 0 64 64"><path d="M32 4L8 16v16c0 14 10.7 26.4 24 29.3C45.3 58.4 56 46 56 32V16L32 4zm0 8l18 9v11c0 10.5-7.8 19.8-18 22.3C21.8 51.8 14 42.5 14 32V21l18-9z"/><path d="M32 20l-8 8h5v12h6V28h5z"/></svg>
  </div>
  <h1>College Management System</h1>
  <div class="divider-line"></div>
  <p>Empowering education through intelligent management. One platform for your entire institution.</p>
  <div class="role-badges">
    <div class="role-badge">📋 Director</div>
    <div class="role-badge">🎓 Coordinator</div>
    <div class="role-badge">👨‍🏫 Faculty</div>
    <div class="role-badge">👨‍🎓 Student</div>
  </div>
</div>

<div class="login-form-side">
  <div class="form-top">
    <h2>Welcome Back</h2>
    <p>Sign in to your account to continue</p>
  </div>

  <?php if ($error): ?>
    <div class="error-msg">⚠ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label>Login As</label>
      <select name="role" required>
        <option value="">— Select Your Role —</option>
        <option value="director" <?= (isset($_POST['role']) && $_POST['role']==='director')?'selected':'' ?>>Director</option>
        <option value="coordinator" <?= (isset($_POST['role']) && $_POST['role']==='coordinator')?'selected':'' ?>>Faculty Coordinator</option>
        <option value="faculty" <?= (isset($_POST['role']) && $_POST['role']==='faculty')?'selected':'' ?>>Faculty / Teacher</option>
        <option value="student" <?= (isset($_POST['role']) && $_POST['role']==='student')?'selected':'' ?>>Student</option>
      </select>
    </div>
    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="your@email.edu" value="<?= isset($_POST['email'])?htmlspecialchars($_POST['email']):'' ?>" required>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn-login">Sign In</button>
  </form>

  <div class="demo-creds">
    <h4>Demo Credentials (Password: password)</h4>
    <table>
      <tr><td>Director</td><td>director@college.edu</td></tr>
      <tr><td>Coordinator</td><td>coordinator@college.edu</td></tr>
      <tr><td>Faculty</td><td>faculty@college.edu</td></tr>
      <tr><td>Student</td><td>amit@student.edu</td></tr>
    </table>
  </div>
</div>

</body>
</html>
