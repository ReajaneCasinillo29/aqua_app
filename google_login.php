<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/google_config.php';

// Generate CSRF state token
$state = bin2hex(random_bytes(16));
$_SESSION['google_state'] = $state;

// Build Google OAuth URL
$params = http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'prompt'        => 'select_account',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit;
