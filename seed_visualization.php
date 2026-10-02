<?php
/**
 * Python4Physics - Seed Visualization Simulation Data
 * Creates visualization table and populates initial interactive quantum simulations.
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/include/menu_sync.php';

header('Content-Type: text/html; charset=utf-8');

if (!isset($conn) || $conn === null) {
    echo "<h3>Database connection not available. Please verify db.php credentials.</h3>";
    exit(1);
}

$ok = ensure_visualization_installed($conn);

// Verify table and rows
$count = 0;
try {
    $count = (int)$conn->query("SELECT COUNT(*) FROM `visualization`")->fetchColumn();
    $menus = (int)$conn->query("SELECT COUNT(*) FROM `p4p_menus` WHERE `language` = 'visualization'")->fetchColumn();
    $submenus = (int)$conn->query("SELECT COUNT(*) FROM `p4p_submenus` WHERE `language` = 'visualization'")->fetchColumn();
} catch (Exception $e) {
    echo "<h3>Error querying visualization table: " . htmlspecialchars($e->getMessage()) . "</h3>";
    exit(1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Visualization Setup & Migration Status</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #0b1120; color: #f8fafc; padding: 2rem; max-width: 650px; margin: auto; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h1 { color: #38bdf8; margin-top: 0; font-size: 1.5rem; }
        .stat { display: flex; justify-content: space-between; padding: 0.6rem 0; border-bottom: 1px solid #334155; }
        .btn { display: inline-block; margin-top: 1.5rem; background: #0284c7; color: white; padding: 0.65rem 1.25rem; border-radius: 8px; text-decoration: none; font-weight: 600; }
        .badge { background: #10b981; color: white; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1><i class="fa-solid fa-sliders"></i> Visualization Table Status</h1>
        <p style="color: #94a3b8;">The interactive physics visualization database has been initialized and synchronized.</p>
        
        <div class="stat">
            <span>Status:</span>
            <span class="badge"><?php echo $ok ? "Successfully Verified" : "Error Initializing"; ?></span>
        </div>
        <div class="stat">
            <span>Visualization Modules:</span>
            <strong><?php echo $menus; ?></strong>
        </div>
        <div class="stat">
            <span>Visualization Subtopics:</span>
            <strong><?php echo $submenus; ?></strong>
        </div>
        <div class="stat">
            <span>Simulations in Database:</span>
            <strong><?php echo $count; ?></strong>
        </div>

        <a href="<?php echo $siteurl; ?>visualization.php" class="btn">Go to Visualization Workbench &rarr;</a>
    </div>
</body>
</html>
