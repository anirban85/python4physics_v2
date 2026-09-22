<?php
/**
 * Python4Physics - Programs REST API Endpoint
 * Query programs by language (python, gnuplot, latex), menu_id, submenu_id, or program id.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../site_config.php';
require_once __DIR__ . '/../db.php';

$lang = isset($_GET['lang']) ? strtolower(trim($_GET['lang'])) : 'python';
$menu_id = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : null;
$submenu_id = isset($_GET['submenu_id']) ? intval($_GET['submenu_id']) : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$valid_tables = ['python', 'gnuplot', 'latex'];
if (!in_array($lang, $valid_tables)) {
    echo json_encode([
        'success' => false,
        'error' => "Invalid language '{$lang}'. Allowed: " . implode(', ', $valid_tables)
    ]);
    exit;
}

if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'error' => 'Database connection unavailable']);
    exit;
}

try {
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM {$lang} WHERE id = ?");
        $stmt->execute([$id]);
        $program = $stmt->fetch();
        if ($program) {
            echo json_encode(['success' => true, 'program' => $program], JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode(['success' => false, 'error' => 'Program not found']);
        }
        exit;
    }

    $sql = "SELECT id, menu_id, submenu_id, program_id, algo, content, explanation FROM {$lang} WHERE 1=1";
    $params = [];

    if ($menu_id !== null) {
        $sql .= " AND menu_id = ?";
        $params[] = $menu_id;
    }
    if ($submenu_id !== null) {
        $sql .= " AND submenu_id = ?";
        $params[] = $submenu_id;
    }

    $sql .= " ORDER BY menu_id, submenu_id, program_id LIMIT 100";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $programs = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'lang'    => $lang,
        'count'   => count($programs),
        'programs' => $programs
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
