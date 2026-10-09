<?php
/**
 * Python4Physics - Telemetry Ingestion Endpoint
 * High-speed, non-blocking telemetry ingestion for page visits, heartbeats, and simulation executions.
 */

// Disable error display to avoid breaking JSON/beacon responses
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Allow cross-origin requests from site subdomains (e.g. v2.python4physics.in)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!empty($origin) && (str_contains($origin, 'python4physics.in') || str_contains($origin, 'localhost'))) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../include/analytics_db.php';

if (!isset($conn) || !$conn) {
    http_response_code(503);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

// Ensure schema exists
init_analytics_tables($conn);

// Parse input body
$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$action = $payload['action'] ?? 'page_view';
$sessionId = substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', $payload['session_id'] ?? ''), 0, 64);
if (empty($sessionId)) {
    $sessionId = 'p4p_sess_' . substr(bin2hex(random_bytes(16)), 0, 32);
}

// Compute client IP hash for privacy
$clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$clientIp = trim(explode(',', $clientIp)[0]);
$salt = 'p4p_telemetry_secure_salt_2026';
$ipHash = hash('sha256', $salt . $clientIp);

$uaDetails = parse_user_agent_details($_SERVER['HTTP_USER_AGENT'] ?? '');
$location = resolve_client_location();

try {
    switch ($action) {
        case 'page_view':
            $pageUrl = substr(trim($payload['url'] ?? ''), 0, 500);
            $pageTitle = substr(trim($payload['title'] ?? ''), 0, 255);
            $referrer = substr(trim($payload['referrer'] ?? ''), 0, 500);
            $screenRes = substr(trim($payload['screen_res'] ?? ''), 0, 30);

            // Parse path
            $parsedUrl = parse_url($pageUrl);
            $pagePath = $parsedUrl['path'] ?? '/';
            $pageType = derive_page_type_from_path($pagePath);

            // Referrer domain
            $refDomain = '';
            if (!empty($referrer)) {
                $refHost = parse_url($referrer, PHP_URL_HOST);
                if ($refHost) {
                    $refDomain = preg_replace('/^www\./i', '', strtolower($refHost));
                }
            }

            $stmt = $conn->prepare("
                INSERT INTO `p4p_analytics_visits`
                (`session_id`, `ip_hash`, `page_url`, `page_title`, `page_path`, `page_type`, `referrer`, `referrer_domain`, `country_code`, `country_name`, `city`, `device_type`, `browser`, `os`, `screen_res`, `duration_seconds`, `scroll_depth_pct`, `is_bot`, `created_at`, `updated_at`)
                VALUES
                (:sid, :iph, :purl, :ptitle, :ppath, :ptype, :ref, :refd, :cc, :cn, :city, :dev, :brow, :os, :res, 0, 0, :bot, NOW(), NOW())
            ");

            $stmt->execute([
                ':sid'    => $sessionId,
                ':iph'    => $ipHash,
                ':purl'   => $pageUrl,
                ':ptitle' => $pageTitle,
                ':ppath'  => $pagePath,
                ':ptype'  => $pageType,
                ':ref'    => $referrer,
                ':refd'   => $refDomain,
                ':cc'     => $location['code'],
                ':cn'     => $location['name'],
                ':city'   => $location['city'],
                ':dev'    => $uaDetails['device'],
                ':brow'   => $uaDetails['browser'],
                ':os'     => $uaDetails['os'],
                ':res'    => $screenRes,
                ':bot'    => $uaDetails['is_bot']
            ]);

            $visitId = (int)$conn->lastInsertId();

            echo json_encode([
                'status'     => 'ok',
                'visit_id'   => $visitId,
                'session_id' => $sessionId
            ]);
            break;

        case 'heartbeat':
            $visitId = (int)($payload['visit_id'] ?? 0);
            $dur = min(86400, max(0, (int)($payload['duration_seconds'] ?? 0)));
            $scroll = min(100, max(0, (int)($payload['scroll_depth_pct'] ?? 0)));

            if ($visitId > 0) {
                $stmt = $conn->prepare("
                    UPDATE `p4p_analytics_visits`
                    SET 
                        `duration_seconds` = GREATEST(`duration_seconds`, :dur),
                        `scroll_depth_pct` = GREATEST(`scroll_depth_pct`, :scroll),
                        `updated_at` = NOW()
                    WHERE `id` = :vid
                ");
                $stmt->execute([
                    ':dur'    => $dur,
                    ':scroll' => $scroll,
                    ':vid'    => $visitId
                ]);
            }

            echo json_encode(['status' => 'ok']);
            break;

        case 'event':
            $visitId = (int)($payload['visit_id'] ?? 0);
            $eventName = substr(trim($payload['event_name'] ?? 'custom_event'), 0, 100);
            $eventCat = substr(trim($payload['event_category'] ?? 'engagement'), 0, 50);
            $eventLabel = substr(trim($payload['event_label'] ?? ''), 0, 255);
            $execTimeMs = isset($payload['execution_time_ms']) ? (int)$payload['execution_time_ms'] : null;
            $status = substr(trim($payload['status'] ?? 'success'), 0, 20);
            $metaJson = isset($payload['meta']) ? json_encode($payload['meta']) : null;

            $stmt = $conn->prepare("
                INSERT INTO `p4p_analytics_events`
                (`visit_id`, `session_id`, `event_name`, `event_category`, `event_label`, `execution_time_ms`, `status`, `meta_json`, `created_at`)
                VALUES
                (:vid, :sid, :ename, :ecat, :elabel, :etime, :estatus, :emeta, NOW())
            ");

            $stmt->execute([
                ':vid'     => ($visitId > 0 ? $visitId : null),
                ':sid'     => $sessionId,
                ':ename'   => $eventName,
                ':ecat'    => $eventCat,
                ':elabel'  => $eventLabel,
                ':etime'   => $execTimeMs,
                ':estatus' => $status,
                ':emeta'   => $metaJson
            ]);

            echo json_encode(['status' => 'ok']);
            break;

        default:
            echo json_encode(['status' => 'ignored']);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Telemetry error', 'message' => $e->getMessage()]);
}
