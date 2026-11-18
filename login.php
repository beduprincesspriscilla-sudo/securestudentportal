<?php
require 'config.php';
$err = '';
if (isset($_GET['cancel'])) unset($_SESSION['pending'], $_SESSION['pending_sid'], $_SESSION['demo_otp']);
$step = isset($_SESSION['pending']) ? 2 : 1;

// Brute-force / rate limiting: 5 failures per account or 20 per IP in 15 minutes = locked
function locked($sid) {
  global $pdo;
  $base = "SELECT COUNT(*) FROM security_logs WHERE action IN ('LOGIN_FAIL','OTP_FAIL') AND created_at > NOW() - INTERVAL 15 MINUTE AND ";
  $q = $pdo->prepare($base.'detail=?'); $q->execute([$sid]);
  if ($q->fetchColumn() >= 5) return true;
  $q = $pdo->prepare($base.'ip=?'); $q->execute([$_SERVER['REMOTE_ADDR']]);
  return $q->fetchColumn() >= 20;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  if (($_POST['step'] ?? '') === '1') {                    // STEP 1: password
    $sid = substr(trim($_POST['student_id'] ?? ''), 0, 50);
    if (locked($sid)) { $err = 'Too many failed attempts. Try again in 15 minutes.'; log_event('LOCKED', null, $sid); }
    else {
      $q = $pdo->prepare('SELECT * FROM users WHERE student_id=?'); $q->execute([$sid]);   // prepared statement = no SQL injection
      $u = $q->fetch(PDO::FETCH_ASSOC);
      if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        $otp = (string)random_int(100000, 999999);          // 6-digit MFA code, valid 5 minutes
        $pdo->prepare('UPDATE users SET otp_hash=?, otp_expires=DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id=?')
            ->execute([password_hash($otp, PASSWORD_DEFAULT), $u['id']]);
        $_SESSION['pending'] = $u['id']; $_SESSION['pending_sid'] = $sid;
        if (DEMO_MODE) $_SESSION['demo_otp'] = $otp;
        log_event('PASSWORD_OK', $u['id']); $step = 2;
      } else { $err = 'Invalid Student ID or password.'; log_event('LOGIN_FAIL', null, $sid); }   // same message for both cases
    }
  } elseif (($_POST['step'] ?? '') === '2' && isset($_SESSION['pending'])) {   // STEP 2: MFA code
    $sid = $_SESSION['pending_sid'];
    if (locked($sid)) { $err = 'Too many failed attempts.'; unset($_SESSION['pending']); $step = 1; }
    else {
      $q = $pdo->prepare('SELECT * FROM users WHERE id=? AND otp_expires > NOW()'); $q->execute([$_SESSION['pending']]);
      $u = $q->fetch(PDO::FETCH_ASSOC);
      if ($u && password_verify(trim($_POST['code'] ?? ''), $u['otp_hash'])) {
        $pdo->prepare('UPDATE users SET otp_hash=NULL, otp_expires=NULL WHERE id=?')->execute([$u['id']]);
        session_regenerate_id(true);                        // new session ID after login
        $_SESSION = ['uid' => $u['id'], 'role' => $u['role'], 'name' => $u['full_name'], 'last' => time()];
        log_event('LOGIN_OK', $u['id']);
        header('Location: '.($u['role'] === 'admin' ? 'admin.php' : 'dashboard.php')); exit;
      } else { $err = 'Invalid or expired code.'; log_event('OTP_FAIL', $_SESSION['pending'], $sid); $step = 2; }
    }
  }
}
top('Login');
?>
<div class="box">
<?php if ($step === 1): ?>
  <h2>Student Login</h2>
  <?php if (isset($_GET['registered'])) echo '<p class="ok">Account created. Please login.</p>'; ?>
  <?php if ($err) echo '<p class="error">'.e($err).'</p>'; ?>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="step" value="1">
    <label>Student ID</label><input type="text" name="student_id" required>
    <label>Password</label><input type="password" name="password" required>
    <button type="submit">Login</button>
  </form>
  <p class="note"><a href="register.php">Create an account</a></p>
<?php else: ?>
  <h2>Verification Code (MFA)</h2>
  <p>A 6-digit code was sent to your phone. It expires in 5 minutes.</p>
  <?php if (DEMO_MODE && isset($_SESSION['demo_otp'])) echo '<p class="demo">DEMO MODE - your code is: <b>'.e($_SESSION['demo_otp']).'</b></p>'; ?>
  <?php if ($err) echo '<p class="error">'.e($err).'</p>'; ?>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="step" value="2">
    <label>Enter code</label><input type="text" name="code" maxlength="6" inputmode="numeric" required>
    <button type="submit">Verify</button>
  </form>
  <p class="note"><a href="login.php?cancel=1">Cancel</a></p>
<?php endif; ?>
</div>
<?php bottom(); ?>
