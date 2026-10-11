<?php
/**
 * Python4Physics - Production Deployment & Migration Hub
 * Allows secure one-click Git sync, database migration, and sitemap generation on cPanel.
 */
session_start();
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';

$secret_token = 'p4p_deploy_2026';
$is_authenticated = false;

if (isset($_GET['token']) && $_GET['token'] === $secret_token) {
    $_SESSION['p4p_deploy_auth'] = true;
    $is_authenticated = true;
} elseif (isset($_POST['token']) && $_POST['token'] === $secret_token) {
    $_SESSION['p4p_deploy_auth'] = true;
    $is_authenticated = true;
} elseif (!empty($_SESSION['p4p_deploy_auth'])) {
    $is_authenticated = true;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$output_log = [];

function run_shell_cmd($cmd) {
    $output = [];
    $return_var = 0;
    exec($cmd . ' 2>&1', $output, $return_var);
    return [
        'cmd' => $cmd,
        'output' => implode("\n", $output),
        'status' => $return_var
    ];
}

// Handle deployment actions if authenticated
if ($is_authenticated && !empty($action)) {
    if ($action === 'git_pull' || $action === 'full_deploy') {
        $output_log[] = "=== [1/3] EXECUTING GIT SYNCHRONIZATION ===";
        // Clean local uncommitted files or conflicts
        $res0 = run_shell_cmd('git reset --hard HEAD');
        $output_log[] = "$ {$res0['cmd']}\n{$res0['output']}";

        $res1 = run_shell_cmd('git clean -fd');
        $output_log[] = "$ {$res1['cmd']}\n{$res1['output']}";

        // Fetch from GitHub master
        $res2 = run_shell_cmd('git fetch origin master');
        $output_log[] = "$ {$res2['cmd']}\n{$res2['output']}";

        // Reset to origin/master
        $res3 = run_shell_cmd('git reset --hard origin/master');
        $output_log[] = "$ {$res3['cmd']}\n{$res3['output']}";

        $res4 = run_shell_cmd('git log -n 1 --oneline');
        $output_log[] = "Current Server HEAD: {$res4['output']}\n";
    }

    if ($action === 'migrate_db' || $action === 'full_deploy') {
        $output_log[] = "=== [2/3] CHECKING DATABASE SCHEMA & CHAPTER 8 ===";
        if (isset($conn) && $conn !== null) {
            try {
                // Check if Chapter 8 already exists in p4p_menus
                $chk = $conn->query("SELECT title FROM p4p_menus WHERE language='python' AND menu_id=8")->fetchColumn();
                if ($chk === 'Introduction to Pandas for Physics') {
                    $cnt = $conn->query("SELECT COUNT(*) FROM python WHERE menu_id=8")->fetchColumn();
                    $output_log[] = "[OK] Chapter 8 is already registered in database with {$cnt} programs.";
                } else {
                    $output_log[] = "[ACTION] Executing Chapter 8 shift and program insertion...";
                    $sql_file = __DIR__ . '/database_migration_ch8_pandas.sql';
                    if (file_exists($sql_file)) {
                        $sql = file_get_contents($sql_file);
                        $conn->exec($sql);
                        $output_log[] = "[SUCCESS] Executed database_migration_ch8_pandas.sql successfully!";
                    } else {
                        $output_log[] = "[ERROR] database_migration_ch8_pandas.sql not found!";
                    }
                }
            } catch (Exception $e) {
                $output_log[] = "[DB ERROR]: " . $e->getMessage();
            }
        } else {
            $output_log[] = "[ERROR] Database connection not initialized.";
        }
        $output_log[] = "";
    }

    if ($action === 'sync_sitemap' || $action === 'full_deploy') {
        $output_log[] = "=== [3/3] REGENERATING SITEMAP.XML ===";
        if (file_exists(__DIR__ . '/sitemap.php')) {
            ob_start();
            include __DIR__ . '/sitemap.php';
            $sitemap_res = ob_get_clean();
            $output_log[] = trim(strip_tags($sitemap_res));
        } else {
            $output_log[] = "[WARNING] sitemap.php not found.";
        }
        $output_log[] = "\nDeployment Process Finished at " . date('Y-m-d H:i:s T');
    }
}

$page_title = "Deployment & Sync Control Hub";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Python4Physics</title>
    <link rel="stylesheet" href="<?php echo $siteurl; ?>assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #0b0f17; color: #e2e8f0; font-family: 'Segoe UI', system-ui, sans-serif; min-height: 100vh; display: flex; flex-direction: column; }
        .deploy-card { background: rgba(30, 41, 59, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); backdrop-filter: blur(12px); border-radius: 14px; padding: 2rem; max-width: 820px; margin: 3rem auto; box-shadow: 0 20px 40px rgba(0,0,0,0.4); width: 100%; box-sizing: border-box; }
        .console-box { background: #030712; border: 1px solid #1f2937; border-radius: 8px; padding: 1.25rem; font-family: 'Consolas', 'Courier New', monospace; font-size: 0.88rem; color: #38bdf8; white-space: pre-wrap; word-break: break-all; min-height: 180px; max-height: 450px; overflow-y: auto; margin-top: 1.5rem; }
    </style>
</head>
<body>
    <div class="deploy-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 1rem;">
            <div>
                <h1 style="font-size: 1.6rem; margin: 0; color: #38bdf8; display: flex; align-items: center; gap: 0.6rem;">
                    <i class="fa-solid fa-server"></i> Python4Physics Deployment Hub
                </h1>
                <p style="margin: 0.35rem 0 0 0; font-size: 0.85rem; color: #94a3b8;">
                    Direct cPanel synchronization, Git pull & automated database schema migration.
                </p>
            </div>
            <a href="<?php echo $siteurl; ?>" style="color: #94a3b8; text-decoration: none; font-size: 0.85rem;">
                <i class="fa-solid fa-house"></i> Home
            </a>
        </div>

        <?php if (!$is_authenticated): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; color: #f87171;">
                <i class="fa-solid fa-lock"></i> Authentication Required. Please enter the deployment access token.
            </div>
            <form method="POST" style="display: flex; gap: 0.75rem;">
                <input type="password" name="token" placeholder="Enter Deployment Token" required style="flex: 1; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #fff; font-size: 0.95rem;">
                <button type="submit" class="btn-modern btn-primary" style="padding: 0.75rem 1.5rem;">
                    <i class="fa-solid fa-unlock"></i> Unlock
                </button>
            </form>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <form method="POST">
                    <input type="hidden" name="action" value="full_deploy">
                    <button type="submit" class="btn-modern btn-primary" style="width: 100%; justify-content: center; padding: 0.85rem 1rem;">
                        <i class="fa-solid fa-bolt"></i> 1-Click Full Deploy
                    </button>
                </form>

                <form method="POST">
                    <input type="hidden" name="action" value="git_pull">
                    <button type="submit" class="btn-modern btn-secondary" style="width: 100%; justify-content: center; padding: 0.85rem 1rem;">
                        <i class="fa-brands fa-git-alt"></i> Pull Latest Git Code
                    </button>
                </form>

                <form method="POST">
                    <input type="hidden" name="action" value="migrate_db">
                    <button type="submit" class="btn-modern btn-secondary" style="width: 100%; justify-content: center; padding: 0.85rem 1rem;">
                        <i class="fa-solid fa-database"></i> Run DB Migration
                    </button>
                </form>

                <form method="POST">
                    <input type="hidden" name="action" value="sync_sitemap">
                    <button type="submit" class="btn-modern btn-secondary" style="width: 100%; justify-content: center; padding: 0.85rem 1rem;">
                        <i class="fa-solid fa-sitemap"></i> Sync Sitemap.xml
                    </button>
                </form>
            </div>

            <?php if (!empty($output_log)): ?>
                <div>
                    <h3 style="font-size: 1rem; color: #94a3b8; margin-bottom: 0.5rem;"><i class="fa-solid fa-terminal"></i> Execution Console</h3>
                    <div class="console-box"><?php echo htmlspecialchars(implode("\n", $output_log)); ?></div>
                </div>
            <?php else: ?>
                <div style="background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.2); border-radius: 8px; padding: 1rem; color: #94a3b8; font-size: 0.9rem;">
                    <i class="fa-solid fa-circle-info" style="color: #38bdf8; margin-right: 6px;"></i>
                    Click <strong>1-Click Full Deploy</strong> to pull the latest GitHub commit, apply the Chapter 8 database updates, and refresh the sitemap.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
