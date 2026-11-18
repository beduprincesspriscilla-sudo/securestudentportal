<?php
require 'config.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $sid = trim($_POST['student_id'] ?? ''); $name = trim($_POST['full_name'] ?? '');
  $phone = trim($_POST['phone'] ?? '');    $pw = $_POST['password'] ?? '';
  // Server-side input validation
  if (!preg_match('/^[A-Za-z0-9]{6,15}$/', $sid))            $err = 'Student ID must be 6-15 letters/numbers.';
  elseif (!preg_match("/^[A-Za-z .'-]{3,100}$/", $name))      $err = 'Enter a valid name.';
  elseif (!preg_match('/^\+?[0-9]{9,15}$/', $phone))          $err = 'Enter a valid phone number.';
  elseif (!strong($pw))                                       $err = 'Password does not meet the policy.';
  elseif ($pw !== ($_POST['confirm'] ?? ''))                  $err = 'Passwords do not match.';
  else {
    $q = $pdo->prepare('SELECT id FROM users WHERE student_id=?'); $q->execute([$sid]);
    if ($q->fetch()) $err = 'Student ID already registered.';
    else {
      $pdo->prepare('INSERT INTO users(student_id,full_name,phone_enc,password_hash) VALUES(?,?,?,?)')
          ->execute([$sid, $name, encrypt($phone), password_hash($pw, PASSWORD_DEFAULT)]);
      $uid = $pdo->lastInsertId();
      foreach ([['BSIT 309','A'],['BSIT 310','B+'],['BSIT 311','A-']] as $r)   // demo results
        $pdo->prepare('INSERT INTO results(user_id,course,grade) VALUES(?,?,?)')->execute([$uid, $r[0], $r[1]]);
      log_event('REGISTER', $uid);
      header('Location: login.php?registered=1'); exit;
    }
  }
}
top('Register');
?>
<div class="box">
  <h2>Create Account</h2>
  <?php if ($err) echo '<p class="error">'.e($err).'</p>'; ?>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <label>Student ID</label><input type="text" name="student_id" required>
    <label>Full Name</label><input type="text" name="full_name" required>
    <label>Phone Number</label><input type="text" name="phone" placeholder="0241234567" required>
    <label>Password</label><input type="password" name="password" id="password" required>
    <div id="meter" class="meter"></div>
    <label>Confirm Password</label><input type="password" name="confirm" required>
    <button type="submit">Register</button>
  </form>
  <p class="note">Password policy: at least 10 characters with an uppercase letter, lowercase letter, number and symbol.</p>
  <p class="note"><a href="login.php">Already registered? Login</a></p>
</div>
<?php bottom(); ?>
