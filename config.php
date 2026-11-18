<?php
// ===== 1. SETTINGS =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'securestudentportal');
define('DB_USER', 'root');
define('DB_PASS', '');
define('ENC_KEY', hash('sha256', 'I_LOVE_JESUS', true)); // key for database encryption
define('DEMO_MODE', true); // true = MFA code is shown on screen (lab has no email/SMS server)

// ===== 2. SECURE SESSIONS =====
session_set_cookie_params([
  'lifetime' => 0, 'path' => '/',
  'secure'   => !empty($_SERVER['HTTPS']),  // cookie only over HTTPS
  'httponly' => true,                       // JavaScript cannot steal it
  'samesite' => 'Strict'                    // blocks cross-site requests
]);
session_start();
if (isset($_SESSION['last']) && time() - $_SESSION['last'] > 900) { // 15 min idle timeout
  session_unset(); session_destroy(); session_start();
}
$_SESSION['last'] = time();

// ===== 3. SECURITY HEADERS =====
header("X-Frame-Options: DENY");                 // anti-clickjacking
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src 'self'"); // blocks injected scripts (XSS)
if (!empty($_SERVER['HTTPS'])) header("Strict-Transport-Security: max-age=31536000");

// ===== 4. DATABASE (PDO) =====
$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS,
  [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);

// ===== 5. HELPER FUNCTIONS =====
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } // output encoding (XSS)

function csrf_field() {                                    // CSRF token
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return '<input type="hidden" name="csrf" value="'.$_SESSION['csrf'].'">';
}
function csrf_check() {
  if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(403); die('Invalid CSRF token'); }
}

function strong($p) {                                      // password policy
  return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{10,}$/', $p);
}

function encrypt($t) {                                     // AES-256-GCM encryption at rest
  $iv = random_bytes(12);
  $c = openssl_encrypt($t, 'aes-256-gcm', ENC_KEY, OPENSSL_RAW_DATA, $iv, $tag);
  return base64_encode($iv.$tag.$c);
}
function decrypt($t) {
  $r = base64_decode($t);
  $p = openssl_decrypt(substr($r, 28), 'aes-256-gcm', ENC_KEY, OPENSSL_RAW_DATA, substr($r, 0, 12), substr($r, 12, 16));
  return $p === false ? '[unreadable]' : $p;
}

function log_event($action, $uid = null, $detail = '') {   // security logging
  global $pdo;
  $pdo->prepare('INSERT INTO security_logs(user_id,action,detail,ip) VALUES(?,?,?,?)')
      ->execute([$uid, $action, substr($detail, 0, 100), $_SERVER['REMOTE_ADDR']]);
}

function require_login() { if (empty($_SESSION['uid'])) { header('Location: login.php'); exit; } }
function require_role($role) {                             // RBAC check
  require_login();
  if ($_SESSION['role'] !== $role) { log_event('RBAC_DENIED', $_SESSION['uid'], $role); http_response_code(403); die('Access denied'); }
}

function top($title) {
  echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>'.e($title).'</title><link rel="stylesheet" href="style.css"></head><body>'
     . '<div class="top"><h1>University Student Portal</h1><p>BSIT 309 - Secure Information Systems</p></div>';
}
function bottom() { echo '<script src="script.js"></script></body></html>'; }
