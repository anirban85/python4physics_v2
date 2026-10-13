<?php
/**
 * Python4Physics - Google OAuth 2.0 Initiator
 */
require_once __DIR__ . '/../site_config.php';
require_once __DIR__ . '/../include/user_auth.php';

$return_url = $_GET['return_url'] ?? ($siteurl . 'program/python/index.php');
if (!str_starts_with($return_url, $siteurl) && !str_starts_with($return_url, '/')) {
    $return_url = $siteurl . 'program/python/index.php';
}

$is_demo = isset($_GET['demo']) || ($auth_dev_mode && !$google_oauth_enabled);

if ($is_demo) {
    // Instant Simulated Student Login for Local Testing & Development
    $user_id = login_or_register_user(
        'google',
        'goog_demo_' . rand(1000, 9999),
        'student.google@physics.edu',
        'Dr. Student (Google)',
        'https://ui-avatars.com/api/?name=Google+Student&background=4285F4&color=fff'
    );
    header("Location: " . $return_url);
    exit;
}

if (!$google_oauth_enabled) {
    die("Google OAuth credentials are not configured in site_config.php yet. Enable AUTH_DEV_MODE to test locally.");
}

// Generate CSRF State Token
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_return_url'] = $return_url;

$params = [
    'client_id'     => $google_client_id,
    'redirect_uri'  => $google_redirect_uri,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
    'prompt'        => 'select_account'
];

$auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
header("Location: " . $auth_url);
exit;
