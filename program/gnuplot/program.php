<?php
/**
 * Python4Physics - Interactive GNUplot Visualization Workbench
 * Real-time Execution via Server Gnuplot 6.0 Engine + SVG Vector Rendering.
 * Fully Responsive - Zero Horizontal Scroll.
 */
require_once __DIR__ . '/../../site_config.php';
require_once __DIR__ . '/../../db.php';
include_once __DIR__ . '/menu.php';

$menu_id    = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 1;
$submenu_id = isset($_GET['submenu_id']) ? intval($_GET['submenu_id']) : 1;

$chapter_title = $menu_titles[$menu_id] ?? "Menu {$menu_id}";
$submenu_list  = $sub_menu_titles[$menu_id] ?? [];
$subtopic_title = $submenu_list[$submenu_id] ?? "Topic {$submenu_id}";

$page_title = "GNUplot: {$chapter_title} - {$subtopic_title}";
$page_description = "Interactive GNUplot scientific plotting for physics: {$subtopic_title}. View and customize graphs online.";

$programs = [];
if (isset($conn) && $conn !== null) {
    try {
        $stmt = $conn->prepare("SELECT id, program_id, content, algo, explanation FROM gnuplot WHERE menu_id = ? AND submenu_id = ? ORDER BY program_id");
        $stmt->execute([$menu_id, $submenu_id]);
        $programs = $stmt->fetchAll();
    } catch (Exception $e) {
        $db_error = $e->getMessage();
    }
}

require_once __DIR__ . '/../../include/header.php';
require_once __DIR__ . '/../../include/navbar.php';
?>

<style>
/* Guarantee Pure White Background for all GNUplot graphs and previews */
.plot-card,
.plot-card[id^="gp_plot_box_"],
[id^="gp_plot_box_"] {
    background: #ffffff !important;
    background-color: #ffffff !important;
    border-radius: var(--radius-md) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
}

[id^="gp_plot_box_"] svg {
    background: #ffffff !important;
    background-color: #ffffff !important;
    width: 100% !important;
    max-width: 100% !important;
    height: auto !important;
    display: block !important;
    margin: 0 auto !important;
    border-radius: var(--radius-sm) !important;
}

[id^="gp_plot_box_"] img {
    background: #ffffff !important;
    background-color: #ffffff !important;
    max-width: 100% !important;
    height: auto !important;
    display: block !important;
    margin: 0 auto !important;
    border-radius: var(--radius-sm) !important;
}
</style>

<div class="container-fluid" style="padding-top: 1.5rem; padding-bottom: 4rem; max-width: 100%; box-sizing: border-box; overflow-x: hidden;">
    <!-- Breadcrumbs -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--card-border);">
        <div style="min-width: 0;">
            <div style="font-size: 0.85rem; color: var(--text-dim); margin-bottom: 0.25rem;">
                <a href="<?php echo $siteurl; ?>"><i class="fa-solid fa-house"></i> Home</a> &gt; 
                <a href="<?php echo $siteurl; ?>program/gnuplot/index.php">GNUplot</a> &gt; 
                <span><?php echo htmlspecialchars($chapter_title); ?></span>
            </div>
            <h1 style="font-size: 1.75rem; display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                <span class="badge badge-amber" style="font-size: 0.82rem;">GNUplot <?php echo $menu_id; ?>.<?php echo $submenu_id; ?></span>
                <span><?php echo htmlspecialchars($subtopic_title); ?></span>
            </h1>
        </div>

        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn-modern btn-secondary btn-sm sidebar-toggle-btn" id="gpSidebarToggleBtn" onclick="toggleGpSidebar()">
                <i class="fa-solid fa-angles-left"></i> <span>Hide Sidebar</span>
            </button>
            <a href="<?php echo $siteurl; ?>program/gnuplot/index.php" class="btn-modern btn-secondary btn-sm">
                <i class="fa-solid fa-bars-staggered"></i> GNUplot Index
            </a>
            <button type="button" class="btn-modern btn-secondary btn-sm trigger-global-search">
                <i class="fa-solid fa-magnifying-glass"></i> Search Codes
            </button>
        </div>
    </div>

    <!-- Workspace Layout with Collapsible Sidebar -->
    <div class="workbench-layout" id="gnuplotWorkbenchLayout">
        <!-- Sidebar Navigation -->
        <aside class="workbench-sidebar" id="gpWorkbenchSidebar">
            <div style="font-size: 0.82rem; font-weight: 700; text-transform: uppercase; color: var(--warning); margin-bottom: 0.85rem; letter-spacing: 0.05em;">
                <i class="fa-solid fa-chart-line"></i> <?php echo htmlspecialchars($chapter_title); ?>
            </div>
            <ul style="list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.35rem;">
                <?php foreach ($submenu_list as $sid => $stitle): 
                    $isActive = ($sid == $submenu_id);
                ?>
                    <li>
                        <a href="?menu_id=<?php echo $menu_id; ?>&submenu_id=<?php echo $sid; ?>" 
                           style="display: block; padding: 0.55rem 0.85rem; border-radius: var(--radius-sm); font-size: 0.88rem; text-decoration: none; color: <?php echo $isActive ? 'var(--warning)' : 'var(--text-muted)'; ?>; background: <?php echo $isActive ? 'rgba(245, 158, 11, 0.12)' : 'transparent'; ?>; font-weight: <?php echo $isActive ? '600' : '400'; ?>; border-left: <?php echo $isActive ? '3px solid var(--warning)' : '3px solid transparent'; ?>;">
                            <?php echo htmlspecialchars($stitle); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <hr style="border: 0; border-top: 1px solid var(--card-border); margin: 1.5rem 0;">

            <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-dim); margin-bottom: 0.5rem;">
                GNUPLOT CATEGORIES
            </div>
            <select class="btn-modern btn-secondary" style="width: 100%; font-size: 0.85rem; padding: 0.5rem;" onchange="if(this.value) window.location.href=this.value;">
                <?php foreach ($menu_titles as $mid => $mtitle): ?>
                    <option value="?menu_id=<?php echo $mid; ?>&submenu_id=1" <?php echo ($mid == $menu_id) ? 'selected' : ''; ?>>
                        <?php echo $mid; ?>. <?php echo htmlspecialchars($mtitle); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </aside>

        <!-- Main Content Area -->
        <main style="min-width: 0; max-width: 100%; overflow: hidden;">
            <?php if (empty($programs)): ?>
                <div class="glass-card" style="text-align: center; padding: 4rem 2rem;">
                    <i class="fa-solid fa-chart-line fa-3x" style="color: var(--text-dim); margin-bottom: 1rem;"></i>
                    <h3>No GNUplot scripts found for this topic</h3>
                </div>
            <?php else: ?>
                <?php foreach ($programs as $idx => $prog): 
                    $pid = $prog['id'];
                    $progNumber = $prog['program_id'];
                    $code = $prog['content'];
                    $algo = $prog['algo'];
                    $explanation = $prog['explanation'];
                ?>
                <section class="glass-card" style="margin-bottom: 2.5rem; max-width: 100%; box-sizing: border-box;" id="gnuplot-<?php echo $progNumber; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.25rem;">
                        <div style="display: flex; align-items: center; gap: 0.65rem; min-width: 0;">
                            <span class="badge badge-amber" style="font-size: 0.85rem; flex-shrink: 0;">Plot <?php echo $progNumber; ?></span>
                            <h2 style="font-size: 1.35rem; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($subtopic_title); ?></h2>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="copyGnuplotCode('gp_code_<?php echo $pid; ?>')">
                                <i class="fa-regular fa-copy"></i> Copy Script
                            </button>
                            <a href="<?php echo $siteurl; ?>api/export.php?id=<?php echo $pid; ?>&lang=gnuplot&format=gp" class="btn-modern btn-secondary btn-sm">
                                <i class="fa-solid fa-download"></i> .gp
                            </a>
                        </div>
                    </div>

                    <!-- Dual Pane Layout -->
                    <div class="workspace-container">
                        <!-- Left Pane: Code Editor -->
                        <div class="workspace-pane">
                            <div class="editor-header">
                                <span class="editor-title"><i class="fa-solid fa-code" style="color: var(--warning);"></i> GNUplot Script</span>
                                <span id="gp_status_<?php echo $pid; ?>" class="badge badge-amber">Ready</span>
                            </div>
                            <div class="editor-wrapper" id="gp_wrapper_<?php echo $pid; ?>">
                                <textarea id="gp_code_<?php echo $pid; ?>"><?php echo htmlspecialchars($code); ?></textarea>
                            </div>
                            <div style="margin-top: 0.5rem; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <div style="display: flex; gap: 0.5rem;">
                                    <button type="button" class="btn-modern btn-primary" id="gp_run_btn_<?php echo $pid; ?>" onclick="executeGnuplotScript('<?php echo $pid; ?>')">
                                        <i class="fa-solid fa-play"></i> Run Code (Ctrl+Enter)
                                    </button>
                                </div>
                                <div>
                                    <button type="button" class="btn-modern btn-secondary btn-sm" onclick="toggleFullscreen('gp_wrapper_<?php echo $pid; ?>')">
                                        <i class="fa-solid fa-expand"></i> Fullscreen
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right Pane: Scientific Graph Output & Console -->
                        <div class="workspace-pane">
                            <div class="tabs-header">
                                <button class="tab-btn active" id="gp_tab_graph_btn_<?php echo $pid; ?>" onclick="switchGpTab('<?php echo $pid; ?>', 'graph')">
                                    <i class="fa-regular fa-image"></i> Scientific Graph
                                </button>
                                <button class="tab-btn" id="gp_tab_log_btn_<?php echo $pid; ?>" onclick="switchGpTab('<?php echo $pid; ?>', 'log')">
                                    <i class="fa-solid fa-terminal"></i> Execution Log
                                </button>
                            </div>

                            <!-- Graph Display -->
                            <div id="gp_panel_graph_<?php echo $pid; ?>" class="tab-content active">
                                <div class="plot-card" style="min-height: 480px; display: flex; align-items: center; justify-content: center; background: #ffffff !important; border-radius: var(--radius-md); box-shadow: 0 4px 16px rgba(0,0,0,0.1); overflow: hidden; padding: 10px;" id="gp_plot_box_<?php echo $pid; ?>">
                                    <div style="color: #64748b;"><i class="fa-solid fa-spinner fa-spin" style="margin-right: 8px;"></i> Compiling GNUplot graph...</div>
                                </div>
                                <div id="gp_download_bar_<?php echo $pid; ?>" style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.75rem;"></div>
                            </div>

                            <!-- Execution Log -->
                            <div id="gp_panel_log_<?php echo $pid; ?>" class="tab-content">
                                <pre id="gp_console_<?php echo $pid; ?>" class="console-output" style="min-height: 480px;"></pre>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty(trim($algo ?? '')) || !empty(trim($explanation ?? ''))): ?>
                    <div class="theory-card" style="margin-top: 1.5rem;">
                        <?php if (!empty(trim($algo ?? ''))): ?>
                            <h4 style="color: var(--warning); margin-bottom: 0.5rem;"><i class="fa-solid fa-circle-info"></i> Plot Description</h4>
                            <div><?php echo $algo; ?></div>
                        <?php endif; ?>
                        <?php if (!empty(trim($explanation ?? ''))): ?>
                            <div><?php echo $explanation; ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
var gpEditors = {};

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('textarea[id^="gp_code_"]').forEach(function(textarea) {
        var pid = textarea.id.replace('gp_code_', '');
        var editor = CodeMirror.fromTextArea(textarea, {
            mode: 'shell',
            theme: 'material-darker',
            lineNumbers: true,
            lineWrapping: true,
            matchBrackets: true,
            viewportMargin: Infinity
        });

        editor.setOption("extraKeys", {
            "Ctrl-Enter": function() { executeGnuplotScript(pid); },
            "Cmd-Enter": function() { executeGnuplotScript(pid); }
        });

        gpEditors[pid] = editor;

        // Auto execute on page load
        executeGnuplotScript(pid);
    });
});

function toggleGpSidebar() {
    var layout = document.getElementById('gnuplotWorkbenchLayout');
    var btn = document.getElementById('gpSidebarToggleBtn');
    if (!layout) return;

    var isCollapsed = layout.classList.toggle('sidebar-collapsed');
    if (btn) {
        btn.innerHTML = isCollapsed 
            ? '<i class="fa-solid fa-angles-right"></i> <span>Show Sidebar</span>' 
            : '<i class="fa-solid fa-angles-left"></i> <span>Hide Sidebar</span>';
    }
    setTimeout(() => {
        Object.values(gpEditors).forEach(ed => ed.refresh());
    }, 300);
}

async function executeGnuplotScript(pid) {
    var editor = gpEditors[pid];
    var code = editor ? editor.getValue() : document.getElementById('gp_code_' + pid).value;
    var targetEl = document.getElementById('gp_plot_box_' + pid);
    var statusBadge = document.getElementById('gp_status_' + pid);
    var consoleEl = document.getElementById('gp_console_' + pid);
    var runBtn = document.getElementById('gp_run_btn_' + pid);
    var downloadBar = document.getElementById('gp_download_bar_' + pid);

    if (runBtn) {
        runBtn.disabled = true;
        runBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Running...';
    }
    statusBadge.className = 'badge badge-amber';
    statusBadge.innerText = 'Compiling...';

    try {
        var formData = new FormData();
        formData.append('code', code);

        var resp = await fetch('execute.php', {
            method: 'POST',
            body: formData
        });

        var data = await resp.json();

        if (data.success && (data.svg || data.img_data)) {
            if (data.format === 'svg' && data.svg) {
                targetEl.innerHTML = data.svg;
                statusBadge.className = 'badge badge-emerald';
                statusBadge.innerText = 'Rendered (SVG)';
            } else if (data.img_data) {
                targetEl.innerHTML = `<img src="${data.img_data}" style="max-width: 100%; height: auto; display: block; margin: auto; border-radius: 8px;" alt="GNUplot Graph">`;
                statusBadge.className = 'badge badge-emerald';
                statusBadge.innerText = 'Rendered';
            }

            if (consoleEl) {
                consoleEl.innerText = data.stdout || data.stderr || "Execution successful! Plot generated.";
            }

            // Create download button
            if (downloadBar) {
                if (data.svg) {
                    var blob = new Blob([data.svg], { type: "image/svg+xml" });
                    var svgUrl = URL.createObjectURL(blob);
                    downloadBar.innerHTML = `
                        <a href="${svgUrl}" download="gnuplot_plot_${pid}.svg" class="btn-modern btn-secondary btn-sm">
                            <i class="fa-solid fa-download"></i> Download Vector SVG
                        </a>
                    `;
                } else if (data.img_data) {
                    downloadBar.innerHTML = `
                        <a href="${data.img_data}" download="gnuplot_plot_${pid}.png" class="btn-modern btn-secondary btn-sm">
                            <i class="fa-solid fa-download"></i> Download Image
                        </a>
                    `;
                }
            }

            switchGpTab(pid, 'graph');
        } else {
            statusBadge.className = 'badge badge-purple';
            statusBadge.innerText = 'Plot Error';
            targetEl.innerHTML = `
                <div style="color: var(--danger); padding: 2rem; text-align: center;">
                    <i class="fa-solid fa-triangle-exclamation fa-2x" style="margin-bottom: 0.75rem; display: block;"></i>
                    <strong>GNUplot Execution Error</strong>
                    <div style="font-size: 0.85rem; margin-top: 0.5rem; color: var(--text-muted); font-family: monospace;">${data.error || 'Unknown error'}</div>
                </div>
            `;
            if (consoleEl) {
                consoleEl.innerText = (data.error || '') + "\n" + (data.stderr || '') + "\n" + (data.stdout || '');
            }
        }
    } catch (err) {
        console.error("GNUplot Error:", err);
        statusBadge.className = 'badge badge-purple';
        statusBadge.innerText = 'Server Error';
        targetEl.innerHTML = `<div style="color: var(--danger);">Connection failed: ${err.message}</div>`;
    } finally {
        if (runBtn) {
            runBtn.disabled = false;
            runBtn.innerHTML = '<i class="fa-solid fa-play"></i> Run Code (Ctrl+Enter)';
        }
    }
}

function switchGpTab(pid, tab) {
    var graphBtn = document.getElementById('gp_tab_graph_btn_' + pid);
    var logBtn = document.getElementById('gp_tab_log_btn_' + pid);
    var graphPanel = document.getElementById('gp_panel_graph_' + pid);
    var logPanel = document.getElementById('gp_panel_log_' + pid);

    if (tab === 'graph') {
        graphBtn.classList.add('active');
        logBtn.classList.remove('active');
        graphPanel.style.display = 'block';
        logPanel.style.display = 'none';
    } else {
        logBtn.classList.add('active');
        graphBtn.classList.remove('active');
        logPanel.style.display = 'block';
        graphPanel.style.display = 'none';
    }
}

function copyGnuplotCode(id) {
    var pid = id.replace('gp_code_', '');
    var code = gpEditors[pid] ? gpEditors[pid].getValue() : document.getElementById(id).value;
    navigator.clipboard.writeText(code).then(() => {
        alert("GNUplot script copied to clipboard!");
    });
}

function toggleFullscreen(wrapperId) {
    var el = document.getElementById(wrapperId);
    if (el) el.classList.toggle('fullscreen-mode');
}
</script>

<?php require_once __DIR__ . '/../../include/footer.php'; ?>