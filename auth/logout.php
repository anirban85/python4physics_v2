<?php
/**
 * Python4Physics - Student Sign-Out Handler
 */
require_once __DIR__ . '/../site_config.php';
require_once __DIR__ . '/../include/user_auth.php';

logout_user();

$return_url = $_GET['return_url'] ?? $siteurl;
if (!str_starts_with($return_url, $siteurl) && !str_starts_with($return_url, '/')) {
    $return_url = $siteurl;
}

header("Location: " . $return_url);
exit;
