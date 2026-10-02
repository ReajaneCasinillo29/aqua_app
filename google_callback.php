<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/google_config.php';

// ── Verify state to prevent CSRF ──
if (!isset($_GET['code']) || !isset($_GET['state']) || !isset($_SESSION['google_state'])) {
    header('Location: login.php?error=' . urlencode('Google login failed. Please try again.'));
    exit;
}

if ($_GET['state'] !== $_SESSION['google_state']) {
    unset($_SESSION['google_state']);
    header('Location: login.php?error=' . urlencode('Invalid request. Please try again.'));
    exit;
}
unset($_SESSION['google_state']);

// ── Exchange authorization code for access token ──
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
];

$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($tokenData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 15,
]);
$tokenResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || $tokenResponse === false) {
    header('Location: login.php?error=' . urlencode('Failed to authenticate with Google.'));
    exit;
}

$tokenJson = json_decode($tokenResponse, true);
$accessToken = $tokenJson['access_token'] ?? null;

if (!$accessToken) {
    header('Location: login.php?error=' . urlencode('Failed to get access token from Google.'));
    exit;
}

// ── Fetch user info from Google ──
$ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$accessToken}"],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 15,
]);
$userResponse = curl_exec($ch);
curl_close($ch);

$userInfo = json_decode($userResponse, true);
$googleId    = (string)($userInfo['id'] ?? '');
$googleEmail = (string)($userInfo['email'] ?? '');
$googleName  = (string)($userInfo['name'] ?? '');
$avatarUrl   = (string)($userInfo['picture'] ?? '');

if ($googleId === '' || $googleEmail === '') {
    header('Location: login.php?error=' . urlencode('Could not retrieve your Google account info.'));
    exit;
}

// ── Look up student by google_id or google_email ──
$stmt = $pdo->prepare('SELECT id, sr_code, full_name, school_name, is_active FROM students WHERE google_id = :gid OR google_email = :gem LIMIT 1');
$stmt->execute([':gid' => $googleId, ':gem' => $googleEmail]);
$student = $stmt->fetch();

if (!$student) {
    // No linked account found
    header('Location: login.php?error=' . urlencode('No student account is linked to this Google email (' . $googleEmail . '). Please contact your administrator to link your account.'));
    exit;
}

if ((int)$student['is_active'] !== 1) {
    header('Location: login.php?error=' . urlencode('Your account is inactive. Please contact your administrator.'));
    exit;
}

// ── Update google_id and avatar if not yet saved ──
$upd = $pdo->prepare('UPDATE students SET google_id = :gid, avatar_url = :avatar WHERE id = :id');
$upd->execute([':gid' => $googleId, ':avatar' => $avatarUrl, ':id' => $student['id']]);

// ── Set session and redirect ──
$_SESSION['student_id']          = (int)$student['id'];
$_SESSION['student_sr_code']     = (string)$student['sr_code'];
$_SESSION['student_full_name']   = (string)($student['full_name'] ?? $googleName);
$_SESSION['student_school_name'] = (string)($student['school_name'] ?? '');
header('Location: students/dashboard.php');
exit;
