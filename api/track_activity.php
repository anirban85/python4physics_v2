<?php
/**
 * Python4Physics - Client-Side User Activity Tracking API
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../site_config.php';
require_once __DIR__ . '/../include/user_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$action = $data['action'] ?? null;
if (!$action) {
    echo json_encode(['success' => false, 'error' => 'Missing action parameter']);
    exit;
}

$page_url   = $data['page_url'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
$page_title = $data['page_title'] ?? null;
$menu_id    = isset($data['menu_id']) ? intval($data['menu_id']) : null;
$submenu_id = isset($data['submenu_id']) ? intval($data['submenu_id']) : null;
$program_id = isset($data['program_id']) ? intval($data['program_id']) : null;
$details    = $data['details'] ?? null;

$logged = log_user_activity($action, $page_url, $page_title, $menu_id, $submenu_id, $program_id, $details);

echo json_encode([
    'success' => $logged,
    'authenticated' => is_user_logged_in()
]);
exit;
