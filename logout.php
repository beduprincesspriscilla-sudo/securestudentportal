<?php
require 'config.php';
if (!empty($_SESSION['uid'])) log_event('LOGOUT', $_SESSION['uid']);
$_SESSION = []; session_destroy();
header('Location: login.php'); exit;
