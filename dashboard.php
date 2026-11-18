<?php
require 'config.php';
require_login();
// Uses the ID from the SESSION (not from the URL), so a student can only ever see their own data
$q = $pdo->prepare('SELECT student_id, full_name, phone_enc, role FROM users WHERE id=?'); $q->execute([$_SESSION['uid']]);
$u = $q->fetch(PDO::FETCH_ASSOC);
$r = $pdo->prepare('SELECT course, grade FROM results WHERE user_id=?'); $r->execute([$_SESSION['uid']]);
$rows = $r->fetchAll(PDO::FETCH_ASSOC);
top('Dashboard');
?>
<div class="box wide">
  <h2>Welcome, <?= e($u['full_name']) ?></h2>
  <div class="menu">
    <button class="tab" data-tab="profile">My Profile</button>
    <button class="tab" data-tab="courses">My Courses</button>
    <button class="tab" data-tab="results">My Results</button>
    <?php if ($u['role'] === 'admin') echo '<a class="btn" href="admin.php">Admin Panel</a>'; ?>
    <a class="btn" href="logout.php">Logout</a>
  </div>
  <div id="profile" class="page">
    <h3>My Profile</h3>
    <p><strong>Student ID:</strong> <?= e($u['student_id']) ?></p>
    <p><strong>Name:</strong> <?= e($u['full_name']) ?></p>
    <p><strong>Phone (decrypted):</strong> <?= e(decrypt($u['phone_enc'])) ?></p>
    <p><strong>Role (RBAC):</strong> <?= e($u['role']) ?></p>
  </div>
  <div id="courses" class="page hide">
    <h3>My Courses</h3>
    <ul><?php foreach ($rows as $x) echo '<li>'.e($x['course']).'</li>'; ?></ul>
  </div>
  <div id="results" class="page hide">
    <h3>My Results</h3>
    <table><tr><th>Course</th><th>Grade</th></tr>
    <?php foreach ($rows as $x) echo '<tr><td>'.e($x['course']).'</td><td>'.e($x['grade']).'</td></tr>'; ?>
    </table>
  </div>
</div>
<?php bottom(); ?>
