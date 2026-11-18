<?php
require 'config.php';
require_role('admin');            // students are blocked here (RBAC) and the attempt is logged
$users = $pdo->query('SELECT id, student_id, full_name, role FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$logs  = $pdo->query('SELECT created_at, action, detail, ip FROM security_logs ORDER BY id DESC LIMIT 25')->fetchAll(PDO::FETCH_ASSOC);
top('Admin Panel');
?>
<div class="box wide">
  <h2>Admin Panel</h2>
  <div class="menu"><a class="btn" href="dashboard.php">My Dashboard</a> <a class="btn" href="logout.php">Logout</a></div>
  <h3>Registered Users</h3>
  <table><tr><th>ID</th><th>Student ID</th><th>Name</th><th>Role</th></tr>
  <?php foreach ($users as $x) echo '<tr><td>'.e($x['id']).'</td><td>'.e($x['student_id']).'</td><td>'.e($x['full_name']).'</td><td>'.e($x['role']).'</td></tr>'; ?>
  </table>
  <h3>Security Log (latest 25)</h3>
  <table><tr><th>Time</th><th>Event</th><th>Detail</th><th>IP</th></tr>
  <?php foreach ($logs as $x) echo '<tr><td>'.e($x['created_at']).'</td><td>'.e($x['action']).'</td><td>'.e($x['detail']).'</td><td>'.e($x['ip']).'</td></tr>'; ?>
  </table>
</div>
<?php bottom(); ?>
