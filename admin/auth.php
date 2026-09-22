<?php
/**
 * Python4Physics - Admin Authentication Middleware
 */
if (session_status() === PHP_SESSION_NONE) {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443);
    session_set_cookie_params([
        'lifetime' => 86400 * 7,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $is_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../site_config.php';

function is_admin_logged_in() {
    return isset($_SESSION['p4p_admin_logged_in']) && $_SESSION['p4p_admin_logged_in'] === true;
}

function require_admin_login() {
    if (!is_admin_logged_in()) {
        $login_url = get_base_url() . '/admin/login.php';
        header("Location: " . $login_url);
        exit;
    }
}

function get_logged_in_admin() {
    return [
        'id' => $_SESSION['p4p_admin_id'] ?? null,
        'username' => $_SESSION['p4p_admin_username'] ?? 'Admin',
        'name' => $_SESSION['p4p_admin_name'] ?? 'Administrator',
        'email' => $_SESSION['p4p_admin_email'] ?? '',
        'is_superuser' => $_SESSION['p4p_admin_superuser'] ?? 0
    ];
}
