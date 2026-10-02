<?php
/**
 * Python4Physics - Emergency & Permanent Git Sync Tool
 * Solves cPanel "Your local changes would be overwritten by merge" error permanently.
 */
session_start();
$is_admin = isset($_SESSION['p4p_admin_logged_in']) && $_SESSION['p4p_admin_logged_in'] === true;

$action = $_GET['action'] ?? '';
$output = '';
$status_out = '';

function run_git($cmd) {
    $full_cmd = "git " . $cmd . " 2>&1";
    if (function_exists('shell_exec')) {
        return @shell_exec($full_cmd);
    }
    if (function_exists('exec')) {
        @exec($full_cmd, $out, $ret);
        return implode("\n", $out);
    }
    return "shell_exec is not enabled on this server.";
}

if ($action === 'fix_and_pull') {
    $steps = [];
    $steps[] = "=== Step 1: Discard local changes on menu.php and sitemap.xml ===";
    $steps[] = run_git("checkout -- program/visualization/menu.php sitemap.xml");
    $steps[] = run_git("checkout -- .");

    $steps[] = "=== Step 2: Configure Git to ignore runtime changes permanently ===";
    $steps[] = run_git("update-index --skip-worktree program/visualization/menu.php");
    $steps[] = run_git("update-index --skip-worktree sitemap.xml");

    $steps[] = "=== Step 3: Pull latest master from GitHub ===";
    $steps[] = run_git("pull origin master");

    $steps[] = "=== Step 4: Final Git Status ===";
    $steps[] = run_git("status");

    $output = implode("\n", $steps);
} elseif ($action === 'skip_worktree') {
    $steps = [];
    $steps[] = run_git("update-index --skip-worktree program/visualization/menu.php");
    $steps[] = run_git("update-index --skip-worktree sitemap.xml");
    $steps[] = "Configured skip-worktree on program/visualization/menu.php and sitemap.xml";
    $output = implode("\n", $steps);
}

$status_out = run_git("status");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Git Permanent Sync & Fix Tool - Python4Physics</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #0b1120; color: #f8fafc; padding: 2rem; max-width: 800px; margin: auto; line-height: 1.5; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 1.75rem; box-shadow: 0 10px 25px rgba(0,0,0,0.5); margin-bottom: 1.5rem; }
        h1 { color: #38bdf8; margin-top: 0; font-size: 1.4rem; display: flex; align-items: center; gap: 8px; }
        h2 { color: #e2e8f0; font-size: 1.1rem; margin-top: 0; }
        pre { background: #0f172a; border: 1px solid #334155; padding: 1rem; border-radius: 8px; color: #38bdf8; font-family: monospace; font-size: 0.85rem; overflow-x: auto; white-space: pre-wrap; }
        .btn { display: inline-block; background: #0284c7; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; cursor: pointer; border: none; font-size: 0.95rem; }
        .btn:hover { background: #0369a1; }
        .btn-green { background: #059669; }
        .btn-green:hover { background: #047857; }
        .code-block { background: #030712; padding: 0.85rem 1rem; border-radius: 6px; font-family: monospace; color: #4ade80; border: 1px solid #1f2937; margin: 0.5rem 0; user-select: all; }
        .badge { display: inline-block; padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; background: #f59e0b; color: #18181b; }
    </style>
</head>
<body>
    <div class="card">
        <h1><i class="fa-solid fa-wrench"></i> Git Permanent Sync & Fix Tool</h1>
        <p style="color: #94a3b8; font-size: 0.95rem;">
            This tool permanently clears the cPanel Git error: <em>"Your local changes to the following files would be overwritten by merge"</em> by discarding server runtime modifications and pulling the latest remote code.
        </p>

        <?php if (!empty($output)): ?>
            <h2>Execution Result:</h2>
            <pre><?php echo htmlspecialchars($output); ?></pre>
        <?php endif; ?>

        <h2>Current Git Working Tree:</h2>
        <pre><?php echo htmlspecialchars($status_out); ?></pre>

        <div style="margin-top: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="?action=fix_and_pull" class="btn btn-green">
                &#9654; Click Here to Auto-Fix &amp; Pull Latest GitHub Master
            </a>
            <a href="?action=skip_worktree" class="btn">
                Configure Git to Ignore Runtime Menu/Sitemap Changes
            </a>
        </div>
    </div>

    <div class="card">
        <h2>Manual Terminal Instructions (If Preferred)</h2>
        <p style="color: #94a3b8; font-size: 0.9rem;">
            If you have the <strong>cPanel Terminal</strong> tab open, simply copy and paste these commands to fix and pull immediately:
        </p>
        <div class="code-block">git checkout -- program/visualization/menu.php sitemap.xml</div>
        <div class="code-block">git pull origin master</div>
        <div class="code-block">git update-index --skip-worktree program/visualization/menu.php sitemap.xml</div>
    </div>
</body>
</html>
