<?php
/**
 * Python4Physics - Seed LaTeX Chapter 8 Figures
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/program/latex/latex_figure_data.php';

if (!isset($conn) || $conn === null) {
    die("Database connection failed.\n");
}

$data = get_latex_figure_data();
$inserted = 0;
$updated = 0;

$checkStmt = $conn->prepare("SELECT id FROM latex WHERE menu_id = ? AND submenu_id = ? AND program_id = ?");
$updateStmt = $conn->prepare("UPDATE latex SET content = ?, algo = ?, explanation = ? WHERE id = ?");
$insertStmt = $conn->prepare("INSERT INTO latex (menu_id, submenu_id, program_id, content, algo, explanation) VALUES (?, ?, ?, ?, ?, ?)");

$sqlStatements = [];

foreach ($data as $item) {
    $menu_id = $item['menu_id'];
    $submenu_id = $item['submenu_id'];
    $program_id = $item['program_id'];
    $content = $item['content'];
    $algo = $item['algo'];
    $explanation = $item['explanation'];

    $checkStmt->execute([$menu_id, $submenu_id, $program_id]);
    $row = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $updateStmt->execute([$content, $algo, $explanation, $row['id']]);
        $updated++;
        echo "Updated menu {$menu_id}.{$submenu_id} program {$program_id} (ID {$row['id']})\n";
    } else {
        $insertStmt->execute([$menu_id, $submenu_id, $program_id, $content, $algo, $explanation]);
        $newId = $conn->lastInsertId();
        $inserted++;
        echo "Inserted menu {$menu_id}.{$submenu_id} program {$program_id} (ID {$newId})\n";
    }

    $cEsc = addslashes($content);
    $aEsc = addslashes($algo);
    $eEsc = addslashes($explanation);
    $sqlStatements[] = "INSERT INTO `latex` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) VALUES ({$menu_id}, {$submenu_id}, {$program_id}, '{$cEsc}', '{$aEsc}', '{$eEsc}');";
}

echo "\nSummary: {$inserted} inserted, {$updated} updated.\n";

// Append SQL to database_v2_migration.sql
$migrationFile = __DIR__ . '/database_v2_migration.sql';
if (file_exists($migrationFile)) {
    $sqlBlock = "\n\n-- ==========================================================\n" .
                "-- LaTeX Chapter 8: Figure Insertion Techniques\n" .
                "-- Added submenus 8.1 - 8.8 with rigorous physics content\n" .
                "-- ==========================================================\n" .
                implode("\n", $sqlStatements) . "\n";
    file_put_contents($migrationFile, $sqlBlock, FILE_APPEND);
    echo "Appended " . count($sqlStatements) . " statements to database_v2_migration.sql\n";
}
