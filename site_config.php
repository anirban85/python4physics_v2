<?php
/**
 * Python4Physics - Dynamic Site Configuration
 * Automatically detects protocol, host, and base path across local and live environments.
 */
if (!isset($siteurl) || empty($siteurl)) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Normalize script directory
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    
    // Strip nested folders to find the project root
    $subDirs = ['/program/visualization', '/program/python', '/program/gnuplot', '/program/latex', '/program', '/api', '/include', '/assets', '/admin', '/auth'];
    $basePath = $scriptDir;
    foreach ($subDirs as $sub) {
        if (str_ends_with($basePath, $sub)) {
            $basePath = substr($basePath, 0, -strlen($sub));
        }
    }
    $basePath = rtrim($basePath, '/') . '/';
    $siteurl = $protocol . $host . $basePath;
}

if (!function_exists('get_base_url')) {
    function get_base_url() {
        global $siteurl;
        return rtrim($siteurl, '/');
    }
}

// Global Site Metadata
$site_name = "Python4Physics";
$site_tagline = "Computational Physics & Scientific Computing Portal";
$site_authors = "Dr. Alorika Chatterjee & Dr. Anirban Shaw";

// Google Analytics 4 & Google Tag Manager Configuration
$ga_measurement_id = getenv('GA_MEASUREMENT_ID') ?: 'G-LKNBL5PKSH';
$gtm_container_id  = getenv('GTM_CONTAINER_ID')  ?: 'GTM-NRDD47HL';
$ga_enabled        = !empty($ga_measurement_id);

// Google AdSense Configuration (Publisher ID: pub-1857733312974112)
$adsense_client_id = getenv('ADSENSE_CLIENT_ID') ?: 'ca-pub-1857733312974112';
$adsense_enabled   = !empty($adsense_client_id);

// Infolinks Advertising Configuration (PID: 3428216)
$infolinks_pid     = getenv('INFOLINKS_PID') ?: '3428216';
$infolinks_wsid    = getenv('INFOLINKS_WSID') ?: '0';
$infolinks_enabled = !empty($infolinks_pid);

// ==============================================================================
// User Authentication & OAuth 2.0 Configuration (Google & GitHub)
// ==============================================================================
// Google OAuth 2.0 Credentials (obtain from https://console.cloud.google.com/apis/credentials)
$google_client_id     = getenv('GOOGLE_CLIENT_ID') ?: '';
$google_client_secret = getenv('GOOGLE_CLIENT_SECRET') ?: '';
$google_redirect_uri  = get_base_url() . '/auth/google_callback.php';
$google_oauth_enabled = !empty($google_client_id) && !empty($google_client_secret);

// GitHub OAuth App Credentials (obtain from https://github.com/settings/developers)
$github_client_id     = getenv('GITHUB_CLIENT_ID') ?: '';
$github_client_secret = getenv('GITHUB_CLIENT_SECRET') ?: '';
$github_redirect_uri  = get_base_url() . '/auth/github_callback.php';
$github_oauth_enabled = !empty($github_client_id) && !empty($github_client_secret);

// Development/Testing Authentication Mode
// When true, allows instant testing on localhost with one-click test logins until live API keys are populated.
$auth_dev_mode = (getenv('AUTH_DEV_MODE') === 'true' || (!$google_oauth_enabled && !$github_oauth_enabled));