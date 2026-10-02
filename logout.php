<?php
declare(strict_types=1);
session_start();

$isAdmin = isset($_SESSION['admin_id']);

$_SESSION = [];
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000,
    $params['path'], $params['domain'],
    $params['secure'], $params['httponly']
  );
}

session_destroy();
header('Location: ' . ($isAdmin ? 'admin_login.php' : 'login.php'));
exit;

