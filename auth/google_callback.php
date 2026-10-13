<?php
/**
 * Python4Physics - Google OAuth 2.0 Callback Handler
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

// Exchange authorization code for access token
$token_url = 'https://oauth2.googleapis.com/token';
$post_data = [
    'code'          => $code,
    'client_id'     => $google_client_id,
    'client_secret' => $google_client_secret,
    'redirect_uri'  => $google_redirect_uri,
    'grant_type'    => 'authorization_code'
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$response) {
    die("Failed to obtain token from Google. HTTP code: {$http_code}. <a href='{$siteurl}auth/login.php'>Try Again</a>");
}

$token_data = json_decode($response, true);
$access_token = $token_data['access_token'] ?? null;

if (!$access_token) {
    die("Invalid token response from Google. <a href='{$siteurl}auth/login.php'>Try Again</a>");
}

// Fetch user profile information
$userinfo_url = 'https://www.googleapis.com/oauth2/v3/userinfo';
$ch = curl_init($userinfo_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$user_response = curl_exec($ch);
curl_close($ch);

$user_info = json_decode($user_response, true);
$provider_id = $user_info['sub'] ?? null;
$email = $user_info['email'] ?? null;
$name = $user_info['name'] ?? 'Google User';
$picture = $user_info['picture'] ?? null;

if (!$provider_id || !$email) {
    die("Could not retrieve email and profile from Google. <a href='{$siteurl}auth/login.php'>Try Again</a>");
}

// Register or Login user
try {
    login_or_register_user('google', $provider_id, $email, $name, $picture);
    header("Location: " . $return_url);
    exit;
} catch (Exception $e) {
    die("Authentication processing error: " . htmlspecialchars($e->getMessage()));
}
