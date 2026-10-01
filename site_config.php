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
    $subDirs = ['/program/python', '/program/gnuplot', '/program/latex', '/program', '/api', '/include', '/assets', '/admin'];
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