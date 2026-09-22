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