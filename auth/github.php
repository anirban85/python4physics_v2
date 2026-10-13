<?php
/**
 * Python4Physics - GitHub OAuth App Initiator
 */
require_once __DIR__ . '/../site_config.php';
require_once __DIR__ . '/../include/user_auth.php';

$return_url = $_GET['return_url'] ?? ($siteurl . 'program/python/index.php');
if (!str_starts_with($return_url, $siteurl) && !str_starts_with($return_url, '/')) {
    $return_url = $siteurl . 'program/python/index.php';
}

$is_demo = isset($_GET['demo']) || ($auth_dev_mode && !$github_oauth_enabled);

if ($is_demo) {
    // Instant Simulated Researcher Login for Local Testing & Development
    $user_id = login_or_register_user(
        'github',
        'gh_demo_' . rand(1000, 9999),
        'researcher.github@physics.ac.in',
        'Prof. Researcher (GitHub)',
        'https://ui-avatars.com/api/?name=GitHub+Researcher&background=24292e&color=fff'
    );
    header("Location: " . $return_url);
    exit;
}

if (!$github_oauth_enabled) {
    die("GitHub OAuth credentials are not configured in site_config.php yet. Enable AUTH_DEV_MODE to test locally.");
}

// Generate CSRF State Token
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_return_url'] = $return_url;

$params = [
    'client_id'    => $github_client_id,
    'redirect_uri' => $github_redirect_uri,
    'scope'        => 'read:user user:email',
    'state'        => $state
];

$auth_url = 'https://github.com/login/oauth/authorize?' . http_build_query($params);
header("Location: " . $auth_url);
exit;
