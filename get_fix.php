<?php
/**
 * Python4Physics - Emergency & Permanent Git Sync Tool
 * Solves cPanel "Your local changes would be overwritten by merge" error permanently.
 */
session_start();
$is_admin = isset($_SESSION['p4p_admin_logged_in']) && $_SESSION['p4p_admin_logged_in'] === true;

// Ensure working directory is always repository root
if (defined('__DIR__') && is_dir(__DIR__)) {
    @chdir(__DIR__);
}

function get_git_binary() {
    $candidates = [
        '/usr/local/cpanel/3rdparty/bin/git',
        '/usr/bin/git',
        '/bin/git',
        'git'
    ];
    foreach ($candidates as $bin) {
        if (@file_exists($bin) && @is_executable($bin)) {
            return $bin;
        }
    }
    return 'git';
}

function run_git($cmd) {
    $git_bin = get_git_binary();
    $repo_dir = escapeshellarg(__DIR__);
    $full_cmd = "cd {$repo_dir} && {$git_bin} " . $cmd . " 2>&1";
    if (function_exists('shell_exec')) {
        return @shell_exec($full_cmd);
    }
    if (function_exists('exec')) {
        @exec($full_cmd, $out, $ret);
        return implode("\n", $out);
    }
    return "shell_exec is not enabled on this server.";
}

$action = $_GET['action'] ?? '';
$output = '';

if ($action === 'fix_and_pull' || $action === 'force_reset') {
    $steps = [];
    $steps[] = "=== Step 1: Remove skip-worktree flags on locked files ===";
    $steps[] = run_git("update-index --no-skip-worktree program/visualization/menu.php sitemap.xml");

    $steps[] = "=== Step 2: Force discard any dirty working tree changes ===";
    $steps[] = run_git("checkout -f -- .");

    $steps[] = "=== Step 3: Fetch latest commits from origin/master ===";
    $steps[] = run_git("fetch origin master");

    $steps[] = "=== Step 4: Hard reset local branch to match origin/master exactly ===";
    $steps[] = run_git("reset --hard origin/master");

    $steps[] = "=== Step 5: Clean untracked files if any ===";
    $steps[] = run_git("clean -fd");

    $steps[] = "=== Step 6: Lock volatile / dated files as assume-unchanged ===";
    $steps[] = run_git("update-index --assume-unchanged program/python/program_21.11.25.php program/python/program_pyodide_22.11.25.php counter.txt");

    $steps[] = "=== Step 7: Verify Final Git Status ===";
    $steps[] = run_git("status");

    $output = implode("\n", array_filter($steps, fn($s) => $s !== ''));
} elseif ($action === 'deploy_to_public_html') {
    $steps = [];
    $steps[] = "=== Deploying Repository Files to /home/python4p/public_html ===";
    $target = '/home/python4p/public_html';
    if (is_dir($target)) {
        $src = rtrim(__DIR__, '/\\');
        $cmd = "cp -R {$src}/* {$target}/ 2>&1";
        $res = @shell_exec($cmd);
        $steps[] = "Deployment to {$target} completed.\n" . ($res ? $res : "[SUCCESS] All files synchronized to public_html.");
    } else {
        $steps[] = "Target directory {$target} not found on this server.";
    }
    $output = implode("\n", $steps);
} elseif ($action === 'seed_modules') {
    $steps = [];
    $steps[] = "=== Seeding Module 1 (Vectors) ===";
    $seed1 = __DIR__ . '/seed_module1_vectors.php';
    if (file_exists($seed1)) {
        ob_start();
        include $seed1;
        $steps[] = strip_tags(ob_get_clean());
    } else {
        $steps[] = "seed_module1_vectors.php not found. Please sync git first.";
    }

    $steps[] = "\n=== Seeding Module 2 (Tensors) ===";
    $seed2 = __DIR__ . '/seed_module2_tensors.php';
    if (file_exists($seed2)) {
        ob_start();
        include $seed2;
        $steps[] = strip_tags(ob_get_clean());
    } else {
        $steps[] = "seed_module2_tensors.php not found. Please sync git first.";
    }

    $output = implode("\n", $steps);
} elseif ($action === 'unlock_only') {
    $steps = [];
    $steps[] = "=== Unlocking skip-worktree flags ===";
    $steps[] = run_git("update-index --no-skip-worktree program/visualization/menu.php sitemap.xml");
    $steps[] = run_git("status");
    $output = implode("\n", array_filter($steps));
} elseif ($action === 'custom_git' && !empty($_POST['git_cmd'])) {
    $allowed_prefixes = ['status', 'log', 'diff', 'branch', 'remote', 'fetch', 'reset', 'checkout', 'pull', 'clean', 'update-index'];
    $cmd_input = trim($_POST['git_cmd']);
    $first_word = explode(' ', $cmd_input)[0] ?? '';
    if (in_array($first_word, $allowed_prefixes, true)) {
        $output = "=== Running: git {$cmd_input} ===\n" . run_git($cmd_input);
    } else {
        $output = "Command prefix not permitted. Allowed: " . implode(', ', $allowed_prefixes);
    }
}

$status_out = run_git("status");
$recent_log = run_git("log -n 3 --oneline");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Git Permanent Sync & Deployment Tool - Python4Physics</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #0b1120; color: #f8fafc; padding: 2rem; max-width: 850px; margin: auto; line-height: 1.5; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 1.75rem; box-shadow: 0 10px 25px rgba(0,0,0,0.5); margin-bottom: 1.5rem; }
        h1 { color: #38bdf8; margin-top: 0; font-size: 1.4rem; display: flex; align-items: center; gap: 8px; }
        h2 { color: #e2e8f0; font-size: 1.1rem; margin-top: 0; }
        pre { background: #0f172a; border: 1px solid #334155; padding: 1rem; border-radius: 8px; color: #38bdf8; font-family: monospace; font-size: 0.85rem; overflow-x: auto; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
        .btn { display: inline-flex; align-items: center; gap: 6px; background: #0284c7; color: white; padding: 0.75rem 1.25rem; border-radius: 8px; text-decoration: none; font-weight: 600; cursor: pointer; border: none; font-size: 0.95rem; }
        .btn:hover { background: #0369a1; }
        .btn-green { background: #059669; }
        .btn-green:hover { background: #047857; }
        .btn-amber { background: #d97706; }
        .btn-amber:hover { background: #b45309; }
        .code-block { background: #030712; padding: 0.85rem 1rem; border-radius: 6px; font-family: monospace; color: #4ade80; border: 1px solid #1f2937; margin: 0.5rem 0; user-select: all; }
        .actions-bar { display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1.25rem; }
        input[type="text"] { background: #0f172a; border: 1px solid #334155; color: #f8fafc; padding: 0.6rem 1rem; border-radius: 6px; font-family: monospace; width: 65%; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>&#9881; Git Permanent Sync &amp; Deployment Tool</h1>
        <p style="color: #94a3b8; font-size: 0.95rem;">
            Resolves cPanel Git merge blocks (<code>program/visualization/menu.php</code> and <code>sitemap.xml</code>) by unlocking the index and performing an authoritative hard reset to match GitHub master.
        </p>

        <?php if (!empty($output)): ?>
            <h2>Execution Result:</h2>
            <pre><?php echo htmlspecialchars($output); ?></pre>
        <?php endif; ?>

        <h2>Live Git Status:</h2>
        <pre><?php echo htmlspecialchars($status_out); ?></pre>

        <h2>Recent Commits:</h2>
        <pre><?php echo htmlspecialchars($recent_log); ?></pre>

        <div class="actions-bar">
            <a href="?action=force_reset" class="btn btn-green">
                &#9654; Force Reset &amp; Sync to GitHub Master
            </a>
            <a href="?action=deploy_to_public_html" class="btn" style="background: #6366f1;">
                &#128640; Sync to Main Domain (public_html)
            </a>
            <a href="?action=seed_modules" class="btn btn-amber">
                &#127793; Run Database Seeding (Module 1 &amp; 2)
            </a>
            <a href="?action=unlock_only" class="btn">
                &#128275; Unlock Skip-Worktree Only
            </a>
        </div>
    </div>

    <div class="card">
        <h2>Run Custom Git Command</h2>
        <form method="POST" action="?action=custom_git" style="display: flex; gap: 0.75rem; align-items: center; margin-top: 0.75rem;">
            <span style="font-family: monospace; color: #38bdf8;">git </span>
            <input type="text" name="git_cmd" placeholder="status, log -n 5, diff, reset --hard origin/master" required>
            <button type="submit" class="btn">Execute</button>
        </form>
    </div>

    <div class="card">
        <h2>cPanel Terminal Copy-Paste (Alternative)</h2>
        <p style="color: #94a3b8; font-size: 0.9rem;">
            If you have the cPanel Terminal open, copy and paste this one-liner:
        </p>
        <div class="code-block">cd /home/python4p/v2.python4physics.in &amp;&amp; git update-index --no-skip-worktree program/visualization/menu.php sitemap.xml &amp;&amp; git reset --hard origin/master</div>
    </div>
</body>
</html>
