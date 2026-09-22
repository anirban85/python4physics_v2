<?php
/**
 * Python4Physics - Global Search API Endpoint
 * High-speed full-text search across all 509 programs (Python, GNUplot, LaTeX).
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../site_config.php';
require_once __DIR__ . '/../db.php';

// Include menu definitions for human-readable topic labels
include_once __DIR__ . '/../program/python/menu.php';
$python_menus = $menu_titles ?? [];
$python_submenus = $sub_menu_titles ?? [];

include_once __DIR__ . '/../program/gnuplot/menu.php';
$gnuplot_menus = $menu_titles ?? [];
$gnuplot_submenus = $sub_menu_titles ?? [];

include_once __DIR__ . '/../program/latex/menu.php';
$latex_menus = $menu_titles ?? [];
$latex_submenus = $sub_menu_titles ?? [];

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 30) : 15;

if (empty($query) || strlen($query) < 2) {
    echo json_encode([
        'success' => false,
        'message' => 'Query string must be at least 2 characters long.',
        'results' => []
    ]);
    exit;
}

$results = [];

if (isset($conn) && $conn !== null) {
    $search_term = "%{$query}%";

    // 1. Search Python programs
    try {
        $stmt = $conn->prepare("
            SELECT id, menu_id, submenu_id, program_id, algo, content, explanation
            FROM python
            WHERE algo LIKE ? OR content LIKE ? OR explanation LIKE ?
            ORDER BY 
                CASE 
                    WHEN algo LIKE ? THEN 1 
                    WHEN content LIKE ? THEN 2 
                    ELSE 3 
                END
            LIMIT ?
        ");
        $stmt->bindValue(1, $search_term, PDO::PARAM_STR);
        $stmt->bindValue(2, $search_term, PDO::PARAM_STR);
        $stmt->bindValue(3, $search_term, PDO::PARAM_STR);
        $stmt->bindValue(4, $search_term, PDO::PARAM_STR);
        $stmt->bindValue(5, $search_term, PDO::PARAM_STR);
        $stmt->bindValue(6, $limit, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            $menu_id = $row['menu_id'];
            $sub_id = $row['submenu_id'];
            $topic = $python_submenus[$menu_id][$sub_id] ?? ($python_menus[$menu_id] ?? "Python Chapter $menu_id");
            $title = !empty(trim(strip_tags($row['algo']))) ? trim(strip_tags($row['algo'])) : "$topic - Program {$row['program_id']}";

            $results[] = [
                'id'         => $row['id'],
                'lang'       => 'python',
                'menu_id'    => $menu_id,
                'submenu_id' => $sub_id,
                'program_id' => $row['program_id'],
                'title'      => $title,
                'topic'      => $topic,
                'url'        => "{$siteurl}program/python/program.php?menu_id={$menu_id}&submenu_id={$sub_id}#prog-{$row['program_id']}"
            ];
        }
    } catch (Exception $e) {
        error_log("Search Python error: " . $e->getMessage());
    }

    // 2. Search GNUplot programs
    try {
        $stmt = $conn->prepare("
            SELECT id, menu_id, submenu_id, program_id, algo, content
            FROM gnuplot
            WHERE algo LIKE ? OR content LIKE ?
            LIMIT ?
        ");
        $stmt->bindValue(1, $search_term, PDO::PARAM_STR);
        $stmt->bindValue(2, $search_term, PDO::PARAM_STR);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            $menu_id = $row['menu_id'];
            $sub_id = $row['submenu_id'];
            $topic = $gnuplot_submenus[$menu_id][$sub_id] ?? ($gnuplot_menus[$menu_id] ?? "GNUplot Plot $menu_id");
            $title = !empty(trim(strip_tags($row['algo']))) ? trim(strip_tags($row['algo'])) : "$topic - Plot {$row['program_id']}";

            $results[] = [
                'id'         => $row['id'],
                'lang'       => 'gnuplot',
                'menu_id'    => $menu_id,
                'submenu_id' => $sub_id,
                'program_id' => $row['program_id'],
                'title'      => $title,
                'topic'      => $topic,
                'url'        => "{$siteurl}program/gnuplot/program.php?menu_id={$menu_id}&submenu_id={$sub_id}"
            ];
        }
    } catch (Exception $e) {
        error_log("Search GNUplot error: " . $e->getMessage());
    }

    // 3. Search LaTeX programs
    try {
        $stmt = $conn->prepare("
            SELECT id, menu_id, submenu_id, program_id, algo, content
            FROM latex
            WHERE algo LIKE ? OR content LIKE ?
            LIMIT ?
        ");
        $stmt->bindValue(1, $search_term, PDO::PARAM_STR);
        $stmt->bindValue(2, $search_term, PDO::PARAM_STR);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            $menu_id = $row['menu_id'];
            $sub_id = $row['submenu_id'];
            $topic = $latex_submenus[$menu_id][$sub_id] ?? ($latex_menus[$menu_id] ?? "LaTeX Section $menu_id");
            $title = !empty(trim(strip_tags($row['algo']))) ? trim(strip_tags($row['algo'])) : "$topic - Example {$row['program_id']}";

            $results[] = [
                'id'         => $row['id'],
                'lang'       => 'latex',
                'menu_id'    => $menu_id,
                'submenu_id' => $sub_id,
                'program_id' => $row['program_id'],
                'title'      => $title,
                'topic'      => $topic,
                'url'        => "{$siteurl}program/latex/program.php?menu_id={$menu_id}&submenu_id={$sub_id}"
            ];
        }
    } catch (Exception $e) {
        error_log("Search LaTeX error: " . $e->getMessage());
    }
}

echo json_encode([
    'success' => true,
    'query'   => $query,
    'count'   => count($results),
    'results' => array_slice($results, 0, $limit)
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
