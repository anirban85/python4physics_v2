<?php
/**
 * Python4Physics - Admin Dashboard
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth.php';

$page_title = "Admin Dashboard";
require_once __DIR__ . '/layout_top.php';

// Safely fetch metrics with fallback if any table does not exist
function safe_count($conn, $table) {
    try {
        if (!$conn) return 0;
        return (int)$conn->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

$python_count   = safe_count($conn, 'python');
$vis_count      = safe_count($conn, 'visualization');
$gnuplot_count  = safe_count($conn, 'gnuplot');
$latex_count    = safe_count($conn, 'latex');
$assign_count   = safe_count($conn, 'assignments');
$feedback_count = safe_count($conn, 'feedback');
$menus_count    = safe_count($conn, 'p4p_menus');
$submenus_count = safe_count($conn, 'p4p_submenus');

// Check GNUplot binary across Windows and Linux server paths
$possible_gp = [
    'C:\Program Files\gnuplot\bin\gnuplot.exe',
    'C:\Program Files (x86)\gnuplot\bin\gnuplot.exe',
    (isset($_SERVER['DOCUMENT_ROOT']) ? dirname($_SERVER['DOCUMENT_ROOT']) . '/gnuplot/bin/gnuplot' : null),
    '/home/python4p/gnuplot/bin/gnuplot',
    '/usr/bin/gnuplot',
    '/usr/local/bin/gnuplot'
];
$gnuplot_installed = false;
foreach ($possible_gp as $p) {
    if ($p && file_exists($p)) {
        $gnuplot_installed = true;
        break;
    }
}

// Fetch recent Python & GNUplot programs safely
$recent_programs = [];
try {
    if ($conn) {
        $recent_stmt = $conn->query("
            (SELECT 'python' as lang, id, menu_id, submenu_id, program_id, algo FROM python ORDER BY id DESC LIMIT 3)
            UNION ALL
            (SELECT 'visualization' as lang, id, menu_id, submenu_id, program_id, algo FROM visualization ORDER BY id DESC LIMIT 3)
            UNION ALL
            (SELECT 'gnuplot' as lang, id, menu_id, submenu_id, program_id, algo FROM gnuplot ORDER BY id DESC LIMIT 3)
            UNION ALL
            (SELECT 'latex' as lang, id, menu_id, submenu_id, program_id, algo FROM latex ORDER BY id DESC LIMIT 3)
            ORDER BY id DESC LIMIT 8
        ");
        if ($recent_stmt) {
            $recent_programs = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Throwable $e) {
    $recent_programs = [];
}

// Fetch menu & submenu titles for clear context display
$menus_map = [];
$submenus_map = [];
try {
    if ($conn) {
        $m_stmt = $conn->query("SELECT language, menu_id, title FROM p4p_menus");
        if ($m_stmt) {
            foreach ($m_stmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
                $menus_map[$m['language']][(int)$m['menu_id']] = $m['title'];
            }
        }
        $s_stmt = $conn->query("SELECT language, menu_id, submenu_id, title FROM p4p_submenus");
        if ($s_stmt) {
            foreach ($s_stmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
                $submenus_map[$s['language']][(int)$s['menu_id']][(int)$s['submenu_id']] = $s['title'];
            }
        }
    }
} catch (Throwable $e) {}
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Welcome back, <?php echo htmlspecialchars($admin_user['name']); ?></h1>
        <p class="admin-page-subtitle">Overview of Computational Physics programs, curricula menus, and interactive problem sets.</p>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <a href="analytics.php" class="btn-admin btn-admin-primary" style="background: linear-gradient(135deg, #0ea5e9, #6366f1); border: none;">
            <i class="fa-solid fa-chart-pie"></i> Website Traffic &amp; Analytics
        </a>
        <a href="programs.php?action=create" class="btn-admin btn-admin-secondary">
            <i class="fa-solid fa-plus"></i> Upload Program
        </a>
        <a href="menus.php" class="btn-admin btn-admin-secondary">
            <i class="fa-solid fa-sitemap"></i> Manage Menus
        </a>
    </div>
</div>

<!-- Stat Grid -->
<div class="admin-grid-stats">
    <div class="admin-stat-card">
        <i class="fa-brands fa-python admin-stat-icon" style="color: #38bdf8;"></i>
        <div class="admin-stat-label">Python Programs</div>
        <div class="admin-stat-value"><?php echo number_format($python_count); ?></div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Active computational scripts
        </div>
    </div>

    <div class="admin-stat-card">
        <i class="fa-solid fa-sliders admin-stat-icon" style="color: #06b6d4;"></i>
        <div class="admin-stat-label">Visualizations</div>
        <div class="admin-stat-value"><?php echo number_format($vis_count); ?></div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Interactive simulations &amp; sliders
        </div>
    </div>

    <div class="admin-stat-card">
        <i class="fa-solid fa-chart-line admin-stat-icon" style="color: #f59e0b;"></i>
        <div class="admin-stat-label">GNUplot Scripts</div>
        <div class="admin-stat-value"><?php echo number_format($gnuplot_count); ?></div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Live SVG scientific plots
        </div>
    </div>

    <div class="admin-stat-card">
        <i class="fa-solid fa-file-invoice admin-stat-icon" style="color: #10b981;"></i>
        <div class="admin-stat-label">LaTeX Programs</div>
        <div class="admin-stat-value"><?php echo number_format($latex_count); ?></div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Scientific typesetting templates
        </div>
    </div>

    <div class="admin-stat-card">
        <i class="fa-solid fa-graduation-cap admin-stat-icon" style="color: #a855f7;"></i>
        <div class="admin-stat-label">Assignments & Labs</div>
        <div class="admin-stat-value"><?php echo number_format($assign_count); ?></div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Interactive computational problem sets
        </div>
    </div>

    <a href="analytics.php" class="admin-stat-card" style="border-color: rgba(14, 165, 233, 0.4); text-decoration: none; display: block;">
        <i class="fa-solid fa-chart-pie admin-stat-icon" style="color: #38bdf8;"></i>
        <div class="admin-stat-label">Website Traffic &amp; Analytics</div>
        <div class="admin-stat-value" style="color: #38bdf8; font-size: 1.55rem; display: flex; align-items: center; gap: 8px;">
            <span>Insights</span>
            <span class="admin-badge admin-badge-green" style="font-size: 0.72rem; padding: 2px 7px;"><i class="fa-solid fa-circle" style="font-size: 0.45rem; vertical-align: middle;"></i> Live</span>
        </div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Demography, engagement &amp; simulation runs &rarr;
        </div>
    </a>
</div>

<!-- Secondary Stats & Environment row -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- System Environment Status -->
    <div class="admin-card" style="margin-bottom: 0;">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="fa-solid fa-server" style="color: var(--admin-accent);"></i> Execution Environment</h2>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.85rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.5rem;">
                <span style="font-size: 0.9rem; color: var(--admin-text-muted);"><i class="fa-brands fa-php" style="margin-right: 6px;"></i> PHP Runtime</span>
                <span class="admin-badge admin-badge-green"><?php echo phpversion(); ?> &bull; Active</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.5rem;">
                <span style="font-size: 0.9rem; color: var(--admin-text-muted);"><i class="fa-solid fa-database" style="margin-right: 6px;"></i> MySQL Database</span>
                <span class="admin-badge admin-badge-green">Connected (`python4p`)</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.5rem;">
                <span style="font-size: 0.9rem; color: var(--admin-text-muted);"><i class="fa-solid fa-chart-line" style="margin-right: 6px;"></i> GNUplot Engine</span>
                <?php if ($gnuplot_installed): ?>
                    <span class="admin-badge admin-badge-green"><i class="fa-solid fa-check"></i> 6.0 Active (Local Engine)</span>
                <?php else: ?>
                    <span class="admin-badge admin-badge-amber">Binary Not Found</span>
                <?php endif; ?>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.9rem; color: var(--admin-text-muted);"><i class="fa-solid fa-layer-group" style="margin-right: 6px;"></i> Curriculum Structure</span>
                <span class="admin-badge admin-badge-cyan"><?php echo $menus_count; ?> Chapters / <?php echo $submenus_count; ?> Subtopics</span>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="admin-card" style="margin-bottom: 0;">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="fa-solid fa-bolt" style="color: #f59e0b;"></i> Quick Actions</h2>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem;">
            <a href="programs.php?action=create&lang=python" class="btn-admin btn-admin-secondary" style="justify-content: center; padding: 1rem; flex-direction: column; text-align: center;">
                <i class="fa-brands fa-python" style="font-size: 1.5rem; color: #38bdf8; margin-bottom: 0.35rem;"></i>
                <span>Add Python Code</span>
            </a>
            <a href="programs.php?action=create&lang=visualization" class="btn-admin btn-admin-secondary" style="justify-content: center; padding: 1rem; flex-direction: column; text-align: center;">
                <i class="fa-solid fa-sliders" style="font-size: 1.5rem; color: #06b6d4; margin-bottom: 0.35rem;"></i>
                <span>Add Visualization</span>
            </a>
            <a href="programs.php?action=create&lang=gnuplot" class="btn-admin btn-admin-secondary" style="justify-content: center; padding: 1rem; flex-direction: column; text-align: center;">
                <i class="fa-solid fa-chart-line" style="font-size: 1.5rem; color: #f59e0b; margin-bottom: 0.35rem;"></i>
                <span>Add GNUplot Code</span>
            </a>
            <a href="menus.php" class="btn-admin btn-admin-secondary" style="justify-content: center; padding: 1rem; flex-direction: column; text-align: center;">
                <i class="fa-solid fa-folder-plus" style="font-size: 1.5rem; color: #10b981; margin-bottom: 0.35rem;"></i>
                <span>Add Menu / Topic</span>
            </a>
            <a href="assignments.php?action=create" class="btn-admin btn-admin-secondary" style="justify-content: center; padding: 1rem; flex-direction: column; text-align: center;">
                <i class="fa-solid fa-graduation-cap" style="font-size: 1.5rem; color: #a855f7; margin-bottom: 0.35rem;"></i>
                <span>New Assignment</span>
            </a>
        </div>
    </div>
</div>

<!-- Recent Programs Card -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fa-solid fa-clock-rotate-left" style="color: var(--admin-accent);"></i> Recently Modified Programs</h2>
        <a href="programs.php" class="btn-admin btn-admin-secondary btn-admin-sm">View All Programs</a>
    </div>

    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 130px;">Language</th>
                    <th style="min-width: 180px;">Chapter (Menu)</th>
                    <th style="min-width: 180px;">Subtopic (Submenu)</th>
                    <th style="width: 80px;">Prog ID</th>
                    <th style="min-width: 220px;">Program Title / Algorithm</th>
                    <th style="width: 100px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_programs)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--admin-text-muted); padding: 2rem;">No programs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_programs as $p): ?>
                        <?php
                        $m_id = (int)$p['menu_id'];
                        $s_id = (int)$p['submenu_id'];
                        $m_title = $menus_map[$p['lang']][$m_id] ?? ($m_id === 0 ? 'General / Uncategorized' : "Chapter {$m_id}");
                        $s_title = $submenus_map[$p['lang']][$m_id][$s_id] ?? ($s_id === 0 ? 'General' : "Topic {$m_id}.{$s_id}");
                        $algo_disp = !empty(trim($p['algo'] ?? '')) ? trim($p['algo']) : ($s_title !== 'General' ? "{$s_title} (Prog #{$p['program_id']})" : "Program #{$p['program_id']}");
                        ?>
                        <tr>
                            <td>
                                <?php if ($p['lang'] === 'python'): ?>
                                    <span class="admin-badge admin-badge-cyan"><i class="fa-brands fa-python"></i> Python</span>
                                <?php elseif ($p['lang'] === 'visualization'): ?>
                                    <span class="admin-badge" style="background: rgba(6, 182, 212, 0.2); color: #06b6d4;"><i class="fa-solid fa-sliders"></i> Visualization</span>
                                <?php elseif ($p['lang'] === 'gnuplot'): ?>
                                    <span class="admin-badge admin-badge-amber"><i class="fa-solid fa-chart-line"></i> GNUplot</span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-green"><i class="fa-solid fa-file-invoice"></i> LaTeX</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--admin-text-main); font-size: 0.88rem; display: flex; align-items: center; gap: 5px;">
                                    <i class="fa-regular fa-folder-open" style="color: #38bdf8; font-size: 0.85rem;"></i>
                                    <span><?php echo htmlspecialchars($m_title); ?></span>
                                </div>
                                <div style="font-size: 0.72rem; color: var(--admin-text-muted); margin-left: 19px;">
                                    <?php echo $m_id > 0 ? "Chapter {$m_id}" : "General"; ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #a5b4fc; font-size: 0.88rem; display: flex; align-items: center; gap: 5px;">
                                    <i class="fa-solid fa-file-code" style="color: #818cf8; font-size: 0.82rem;"></i>
                                    <span><?php echo htmlspecialchars($s_title); ?></span>
                                </div>
                                <div style="font-size: 0.72rem; color: var(--admin-text-muted); margin-left: 18px;">
                                    <?php echo $s_id > 0 ? "Topic {$m_id}.{$s_id}" : "General"; ?>
                                </div>
                            </td>
                            <td>
                                <strong style="color: var(--admin-accent); font-family: 'JetBrains Mono', monospace;">#<?php echo (int)$p['program_id']; ?></strong>
                            </td>
                            <td style="font-weight: 600; color: var(--admin-text-main);">
                                <?php echo htmlspecialchars(mb_substr($algo_disp, 0, 70)); ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="programs.php?action=edit&lang=<?php echo urlencode($p['lang']); ?>&id=<?php echo (int)$p['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/layout_bottom.php'; ?>
