<?php
/**
 * Python4Physics - User Authentication & Activity Tracking Engine
 * Manages OAuth sessions, user profiles, security verification, and activity telemetry.
 */

if (session_status() === PHP_SESSION_NONE) {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443);
    session_set_cookie_params([
        'lifetime' => 86400 * 30, // 30 days persistence
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

/**
 * Check if a student/user is currently authenticated
 */
function is_user_logged_in() {
    return isset($_SESSION['p4p_user_logged_in']) && $_SESSION['p4p_user_logged_in'] === true && !empty($_SESSION['p4p_user_id']);
}

/**
 * Retrieve current logged-in user profile details
 */
function get_logged_in_user() {
    if (!is_user_logged_in()) {
        return null;
    }
    return [
        'id'         => (int)$_SESSION['p4p_user_id'],
        'name'       => $_SESSION['p4p_user_name'] ?? 'Student',
        'email'      => $_SESSION['p4p_user_email'] ?? '',
        'avatar_url' => $_SESSION['p4p_user_avatar'] ?? '',
        'provider'   => $_SESSION['p4p_user_provider'] ?? 'google'
    ];
}

/**
 * Helper to extract accurate client IP address
 */
function get_client_ip_address() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR'
    ];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Helper to extract client country from headers
 */
function get_client_country_name() {
    return $_SERVER['HTTP_CF_IPCOUNTRY'] ?? $_SERVER['GEOIP_COUNTRY_NAME'] ?? 'Unknown';
}

/**
 * Log user activity into p4p_user_activities
 */
function log_user_activity($action, $page_url = null, $page_title = null, $menu_id = null, $submenu_id = null, $program_id = null, $details = null) {
    global $conn;
    if (!isset($conn) || $conn === null) {
        return false;
    }

    $user_id = is_user_logged_in() ? (int)$_SESSION['p4p_user_id'] : null;
    if (!$user_id) {
        return false; // Only log registered user interactions
    }

    $ip = get_client_ip_address();
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500);
    $url = $page_url ?? ($_SERVER['REQUEST_URI'] ?? '');
    $title = $page_title ?? '';
    
    if (is_array($details)) {
        $details = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO p4p_user_activities 
            (user_id, action, page_url, page_title, menu_id, submenu_id, program_id, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            substr($action, 0, 64),
            substr($url, 0, 500),
            substr($title, 0, 255),
            $menu_id !== null ? intval($menu_id) : null,
            $submenu_id !== null ? intval($submenu_id) : null,
            $program_id !== null ? intval($program_id) : null,
            $details,
            substr($ip, 0, 45),
            $ua
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to log user activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Register or authenticate user from OAuth Provider
 */
function login_or_register_user($provider, $provider_id, $email, $name, $avatar_url = null) {
    global $conn;
    if (!isset($conn) || $conn === null) {
        throw new Exception("Database connection unavailable.");
    }

    $ip = get_client_ip_address();
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500);
    $country = get_client_country_name();
    $email = strtolower(trim($email));
    $name = trim($name);

    // Look up existing user by email or (provider, provider_id)
    $stmt = $conn->prepare("SELECT * FROM p4p_users WHERE email = ? OR (provider = ? AND provider_id = ?) LIMIT 1");
    $stmt->execute([$email, $provider, $provider_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $user_id = (int)$user['id'];
        // Update user record with latest login timestamp, IP and avatar
        $updateStmt = $conn->prepare("
            UPDATE p4p_users 
            SET login_count = login_count + 1,
                last_login_at = NOW(),
                ip_address = ?,
                user_agent = ?,
                country = ?,
                avatar_url = COALESCE(?, avatar_url),
                name = COALESCE(NULLIF(?, ''), name)
            WHERE id = ?
        ");
        $updateStmt->execute([$ip, $ua, $country, $avatar_url, $name, $user_id]);
    } else {
        // Insert new user
        $insertStmt = $conn->prepare("
            INSERT INTO p4p_users 
            (provider, provider_id, email, name, avatar_url, ip_address, user_agent, country, login_count, created_at, last_login_at, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW(), 'active')
        ");
        $insertStmt->execute([
            $provider,
            $provider_id,
            $email,
            $name,
            $avatar_url,
            $ip,
            $ua,
            $country
        ]);
        $user_id = (int)$conn->lastInsertId();
    }

    // Set Session
    $_SESSION['p4p_user_logged_in'] = true;
    $_SESSION['p4p_user_id']        = $user_id;
    $_SESSION['p4p_user_name']      = $name;
    $_SESSION['p4p_user_email']     = $email;
    $_SESSION['p4p_user_avatar']    = $avatar_url;
    $_SESSION['p4p_user_provider']  = $provider;

    // Log the successful authentication event
    log_user_activity('login', $_SERVER['REQUEST_URI'] ?? '', 'User Authenticated', null, null, null, [
        'provider' => $provider,
        'ip' => $ip,
        'country' => $country
    ]);

    return $user_id;
}

/**
 * Terminate user session
 */
function logout_user() {
    if (is_user_logged_in()) {
        log_user_activity('logout', $_SERVER['REQUEST_URI'] ?? '', 'User Signed Out');
    }
    unset($_SESSION['p4p_user_logged_in']);
    unset($_SESSION['p4p_user_id']);
    unset($_SESSION['p4p_user_name']);
    unset($_SESSION['p4p_user_email']);
    unset($_SESSION['p4p_user_avatar']);
    unset($_SESSION['p4p_user_provider']);
}
