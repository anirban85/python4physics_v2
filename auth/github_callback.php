<?php
/**
 * Python4Physics - GitHub OAuth App Callback Handler
 */
require_once __DIR__ . '/../site_config.php';
require_once __DIR__ . '/../include/user_auth.php';

$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;
$session_state = $_SESSION['oauth_state'] ?? null;
$return_url = $_SESSION['oauth_return_url'] ?? ($siteurl . 'program/python/index.php');

unset($_SESSION['oauth_state']);
unset($_SESSION['oauth_return_url']);

if (!$code || !$state || $state !== $session_state) {
    die("Security verification failed (Invalid OAuth state token). <a href='{$siteurl}auth/login.php'>Return to Login</a>");
}

// Exchange code for GitHub access token
$token_url = 'https://github.com/login/oauth/access_token';
$post_data = [
    'code'          => $code,
    'client_id'     => $github_client_id,
    'client_secret' => $github_client_secret,
    'redirect_uri'  => $github_redirect_uri
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'User-Agent: Python4Physics-Auth']);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$response) {
    die("Failed to obtain token from GitHub. HTTP code: {$http_code}. <a href='{$siteurl}auth/login.php'>Try Again</a>");
}

$token_data = json_decode($response, true);
$access_token = $token_data['access_token'] ?? null;

if (!$access_token) {
    die("Invalid token response from GitHub. <a href='{$siteurl}auth/login.php'>Try Again</a>");
}

// Fetch GitHub profile
$user_url = 'https://api.github.com/user';
$ch = curl_init($user_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'User-Agent: Python4Physics-Auth',
    'Accept: application/vnd.github.v3+json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$user_response = curl_exec($ch);
curl_close($ch);

$user_info = json_decode($user_response, true);
$provider_id = (string)($user_info['id'] ?? '');
$name = $user_info['name'] ?? $user_info['login'] ?? 'GitHub User';
$avatar_url = $user_info['avatar_url'] ?? null;
$email = $user_info['email'] ?? null;

// If email is private in public profile, query user/emails endpoint
if (!$email) {
    $emails_url = 'https://api.github.com/user/emails';
    $ch = curl_init($emails_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'User-Agent: Python4Physics-Auth',
        'Accept: application/vnd.github.v3+json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $emails_response = curl_exec($ch);
    curl_close($ch);

    $emails_list = json_decode($emails_response, true);
    if (is_array($emails_list)) {
        foreach ($emails_list as $e) {
            if (!empty($e['primary']) && !empty($e['email'])) {
                $email = $e['email'];
                break;
            }
        }
        if (!$email && !empty($emails_list[0]['email'])) {
            $email = $emails_list[0]['email'];
        }
    }
}

if (!$email) {
    // Fallback email based on GitHub username if no verified email was shared
    $email = ($user_info['login'] ?? $provider_id) . '@users.noreply.github.com';
}

if (!$provider_id) {
    die("Could not retrieve profile ID from GitHub. <a href='{$siteurl}auth/login.php'>Try Again</a>");
}

// Register or Login user
try {
    login_or_register_user('github', $provider_id, $email, $name, $avatar_url);
    header("Location: " . $return_url);
    exit;
} catch (Exception $e) {
    die("Authentication processing error: " . htmlspecialchars($e->getMessage()));
}
