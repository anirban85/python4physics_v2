<?php
/**
 * Python4Physics - Admin Authentication Middleware
 */
if (session_status() === PHP_SESSION_NONE) {
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
