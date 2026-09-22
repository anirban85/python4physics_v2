<?php
/**
 * Python4Physics - Interactive Python Physics Workbench
 * Client-Side Execution via Pyodide 0.27+ with WebAssembly, Matplotlib capture, and Jupyter Export.
 * Fully Responsive - Zero Horizontal Scroll.
 */
require_once __DIR__ . '/../../site_config.php';
require_once __DIR__ . '/../../db.php';
include_once __DIR__ . '/menu.php';

$menu_id    = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 1;
$submenu_id = isset($_GET['submenu_id']) ? intval($_GET['submenu_id']) : 1;

$chapter_title = $menu_titles[$menu_id] ?? "Chapter {$menu_id}";
$submenu_list  = $sub_menu_titles[$menu_id] ?? [];
$subtopic_title = $submenu_list[$submenu_id] ?? "Topic {$submenu_id}";

$page_title = "{$chapter_title} - {$subtopic_title}";
$page_description = "Interactive computational physics simulation for {$subtopic_title} in {$chapter_title}. Run Python code in-browser with Pyodide.";

// Fetch programs
$programs = [];
if (isset($conn) && $conn !== null) {
    try {
        $stmt = $conn->prepare("SELECT id, program_id, content, algo, explanation FROM python WHERE menu_id = ? AND submenu_id = ? ORDER BY program_id");
        $stmt->execute([$menu_id, $submenu_id]);
        $programs = $stmt->fetchAll();
    } catch (Exception $e) {
        $db_error = $e->getMessage();
    }
}

require_once __DIR__ . '/../../include/header.php';
require_once __DIR__ . '/../../include/navbar.php';
?>

<div class="container-fluid" style="padding-top: 1.5rem; padding-bottom: 4rem; max-width: 100%; box-sizing: border-box; overflow-x: hidden;">
    <!-- Breadcrumb & Topic Title Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--card-border);">
        <div style="min-width: 0;">
            <div style="font-size: 0.85rem; color: var(--text-dim); margin-bottom: 0.25rem;">
                <a href="<?php echo $siteurl; ?>"><i class="fa-solid fa-house"></i> Home</a> &gt; 
                <a href="<?php echo $siteurl; ?>program/python/index.php">Python</a> &gt; 
                <span><?php echo htmlspecialchars($chapter_title); ?></span>
            </div>
            <h1 style="font-size: 1.75rem; display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                <span class="badge badge-cyan" style="font-size: 0.82rem;">Ch. <?php echo $menu_id; ?>.<?php echo $submenu_id; ?></span>
                <span><?php echo htmlspecialchars($subtopic_title); ?></span>
            </h1>
        </div>

        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn-modern btn-secondary btn-sm sidebar-toggle-btn" id="sidebarToggleBtn" onclick="toggleWorkbenchSidebar()">
                <i class="fa-solid fa-angles-left"></i> <span>Hide Sidebar</span>
            </button>
            <a href="<?php echo $siteurl; ?>program/python/index.php" class="btn-modern btn-secondary btn-sm">
                <i class="fa-solid fa-bars-staggered"></i> Chapters Index
            </a>
            <button type="button" class="btn-modern btn-secondary btn-sm trigger-global-search">
                <i class="fa-solid fa-magnifying-glass"></i> Search Codes
            </button>
        </div>
    </div>

    <!-- Main Workspace Layout with Collapsible Sidebar -->
    <div class="workbench-layout" id="pythonWorkbenchLayout">
        <!-- Sidebar Navigation -->
        <aside class="workbench-sidebar" id="workbenchSidebar">
            <div style="font-size: 0.82rem; font-weight: 700; text-transform: uppercase; color: var(--accent); margin-bottom: 0.85rem; letter-spacing: 0.05em;">
                <i class="fa-brands fa-python"></i> <?php echo htmlspecialchars($chapter_title); ?>
            </div>
            <ul style="list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.35rem;">
                <?php foreach ($submenu_list as $sid => $stitle): 
                    $isActive = ($sid == $submenu_id);
                ?>
                    <li>
                        <a href="?menu_id=<?php echo $menu_id; ?>&submenu_id=<?php echo $sid; ?>" 
                           style="display: block; padding: 0.55rem 0.85rem; border-radius: var(--radius-sm); font-size: 0.88rem; text-decoration: none; color: <?php echo $isActive ? 'var(--accent)' : 'var(--text-muted)'; ?>; background: <?php echo $isActive ? 'rgba(6, 182, 212, 0.12)' : 'transparent'; ?>; font-weight: <?php echo $isActive ? '600' : '400'; ?>; border-left: <?php echo $isActive ? '3px solid var(--accent)' : '3px solid transparent'; ?>;">
                            <?php echo htmlspecialchars($stitle); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <hr style="border: 0; border-top: 1px solid var(--card-border); margin: 1.5rem 0;">

            <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-dim); margin-bottom: 0.5rem;">
                SELECT CHAPTER
            </div>
            <select class="btn-modern btn-secondary" style="width: 100%; font-size: 0.85rem; padding: 0.5rem;" onchange="if(this.value) window.location.href=this.value;">
                <?php foreach ($menu_titles as $mid => $mtitle): ?>
                    <option value="?menu_id=<?php echo $mid; ?>&submenu_id=1" <?php echo ($mid == $menu_id) ? 'selected' : ''; ?>>
                        Ch. <?php echo $mid; ?>: <?php echo htmlspecialchars($mtitle); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </aside>

        <!-- Main Programs Area -->
        <main style="min-width: 0; max-width: 100%; overflow: hidden;">
            <?php if (empty($programs)): ?>
                <div class="glass-card" style="text-align: center; padding: 4rem 2rem;">
                    <i class="fa-solid fa-code fa-3x" style="color: var(--text-dim); margin-bottom: 1rem;"></i>
                    <h3>No programs found for this subtopic</h3>
                    <p>Please select another topic from the sidebar.</p>
                </div>
            <?php else: ?>
                <?php foreach ($programs as $idx => $prog): 
                    $pid = $prog['id'];
                    $progNumber = $prog['program_id'];
                    $code = $prog['content'];
                    $algo = $prog['algo'];
                    $explanation = $prog['explanation'];
                ?>
                <section class="glass-card" style="margin-bottom: 2.5rem; max-width: 100%; box-sizing: border-box;" id="prog-<?php echo $progNumber; ?>">
                    <!-- Program Header -->
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.25rem;">
                        <div style="display: flex; align-items: center; gap: 0.65rem; min-width: 0;">
                            <span class="badge badge-cyan" style="font-size: 0.85rem; flex-shrink: 0;">Program <?php echo $progNumber; ?></span>
                            <h2 style="font-size: 1.35rem; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($subtopic_title); ?></h2>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="copyProgramCode('code_<?php echo $pid; ?>')">
                                <i class="fa-regular fa-copy"></i> Copy
                            </button>
                            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="exportProgramPy('<?php echo $pid; ?>', '<?php echo addslashes($subtopic_title); ?>')">
                                <i class="fa-brands fa-python"></i> .py
                            </button>
                            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="exportProgramJupyter('<?php echo $pid; ?>', '<?php echo addslashes($subtopic_title); ?> - Program <?php echo $progNumber; ?>')">
                                <i class="fa-solid fa-book-journal-whills"></i> .ipynb
                            </button>
                        </div>
                    </div>

                    <!-- Dual-Pane Workspace for this Program -->
                    <div class="workspace-container">
                        <!-- Left Pane: Editor -->
                        <div class="workspace-pane">
                            <div class="editor-header">
                                <span class="editor-title"><i class="fa-brands fa-python" style="color: var(--accent);"></i> Python 3.11</span>
                                <span id="status_<?php echo $pid; ?>" class="badge badge-cyan">Ready</span>
                            </div>
                            <div class="editor-wrapper" id="wrapper_<?php echo $pid; ?>">
                                <textarea id="code_<?php echo $pid; ?>"><?php echo htmlspecialchars($code); ?></textarea>
                            </div>
                            <!-- Editor Actions -->
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <button type="button" class="btn-modern btn-primary" id="run_btn_<?php echo $pid; ?>" onclick="executeProgram('<?php echo $pid; ?>')">
                                        <i class="fa-solid fa-play"></i> Run Code (Ctrl+Enter)
                                    </button>
                                    <button type="button" class="btn-modern btn-secondary" onclick="resetProgramCode('<?php echo $pid; ?>')">
                                        <i class="fa-solid fa-rotate-left"></i> Reset
                                    </button>
                                </div>
                                <div>
                                    <button type="button" class="btn-modern btn-secondary btn-sm" onclick="toggleFullscreen('wrapper_<?php echo $pid; ?>')" title="Toggle Fullscreen">
                                        <i class="fa-solid fa-expand"></i> Fullscreen
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right Pane: Tabbed Results & Theory -->
                        <div class="workspace-pane">
                            <div class="tabs-header">
                                <button class="tab-btn active" id="tab_console_btn_<?php echo $pid; ?>" onclick="switchProgramTab('<?php echo $pid; ?>', 'console')">
                                    <i class="fa-solid fa-terminal"></i> Console Output
                                </button>
                                <button class="tab-btn" id="tab_plots_btn_<?php echo $pid; ?>" onclick="switchProgramTab('<?php echo $pid; ?>', 'plots')">
                                    <i class="fa-regular fa-image"></i> Rendered Plots
                                </button>
                                <?php if (!empty(trim($algo ?? '')) || !empty(trim($explanation ?? ''))): ?>
                                <button class="tab-btn" id="tab_theory_btn_<?php echo $pid; ?>" onclick="switchProgramTab('<?php echo $pid; ?>', 'theory')">
                                    <i class="fa-solid fa-square-root-variable"></i> Theory & Math
                                </button>
                                <?php endif; ?>
                            </div>

                            <!-- Console Output -->
                            <div id="panel_console_<?php echo $pid; ?>" class="tab-content active">
                                <pre id="console_<?php echo $pid; ?>" class="console-output" style="min-height: 380px;"></pre>
                                <div id="time_<?php echo $pid; ?>" class="console-timing"></div>
                            </div>

                            <!-- Plots Output -->
                            <div id="panel_plots_<?php echo $pid; ?>" class="tab-content">
                                <div id="plots_<?php echo $pid; ?>" class="plots-container" style="min-height: 380px; display: flex; align-items: center; justify-content: center;">
                                    <div style="color: var(--text-dim); text-align: center;">Click "Run Code" to execute script and render figures.</div>
                                </div>
                            </div>

                            <!-- Theory & Algorithm Formulation -->
                            <?php if (!empty(trim($algo ?? '')) || !empty(trim($explanation ?? ''))): ?>
                            <div id="panel_theory_<?php echo $pid; ?>" class="tab-content">
                                <div class="theory-card" style="min-height: 380px; max-height: 480px; overflow-y: auto;">
                                    <?php if (!empty(trim($algo ?? ''))): ?>
                                        <div style="margin-bottom: 1.25rem;">
                                            <h4 style="color: var(--accent); margin-bottom: 0.5rem;"><i class="fa-solid fa-diagram-project"></i> Mathematical Problem Formulation</h4>
                                            <div><?php echo $algo; ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty(trim($explanation ?? ''))): ?>
                                        <div>
                                            <h4 style="color: var(--primary); margin-bottom: 0.5rem;"><i class="fa-solid fa-book-open-reader"></i> Theoretical Background & Explanation</h4>
                                            <div><?php echo $explanation; ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Pyodide Execution Engine Script -->
<script src="<?php echo $siteurl; ?>assets/js/pyodide-runner.js"></script>

<script>
var p4pEditors = {};
var p4pOriginalCodes = {};

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('textarea[id^="code_"]').forEach(function(textarea) {
        var pid = textarea.id.replace('code_', '');
        p4pOriginalCodes[pid] = textarea.value;

        var editor = CodeMirror.fromTextArea(textarea, {
            mode: 'python',
            theme: 'material-darker',
            lineNumbers: true,
            lineWrapping: true,
            matchBrackets: true,
            styleActiveLine: true,
            indentUnit: 4,
            viewportMargin: Infinity
        });

        editor.setOption("extraKeys", {
            "Ctrl-Enter": function() { executeProgram(pid); },
            "Cmd-Enter": function() { executeProgram(pid); }
        });

        p4pEditors[pid] = editor;
    });
});

function toggleWorkbenchSidebar() {
    var layout = document.getElementById('pythonWorkbenchLayout');
    var btn = document.getElementById('sidebarToggleBtn');
    if (!layout) return;

    var isCollapsed = layout.classList.toggle('sidebar-collapsed');
    if (btn) {
        btn.innerHTML = isCollapsed 
            ? '<i class="fa-solid fa-angles-right"></i> <span>Show Sidebar</span>' 
            : '<i class="fa-solid fa-angles-left"></i> <span>Hide Sidebar</span>';
    }
    // Refresh CodeMirror editors
    setTimeout(() => {
        Object.values(p4pEditors).forEach(ed => ed.refresh());
    }, 300);
}

async function executeProgram(pid) {
    var editor = p4pEditors[pid];
    if (!editor) return;

    var code = editor.getValue();
    var runBtn = document.getElementById('run_btn_' + pid);
    var statusBadge = document.getElementById('status_' + pid);
    var consoleEl = document.getElementById('console_' + pid);
    var plotsEl = document.getElementById('plots_' + pid);
    var timeEl = document.getElementById('time_' + pid);

    runBtn.disabled = true;
    runBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Running...';
    statusBadge.className = 'badge badge-amber';
    statusBadge.innerText = 'Executing...';

    try {
        await window.physicsRunner.run(code, {
            onStatus: function(msg) { statusBadge.innerText = msg; },
            consoleEl: consoleEl,
            plotsEl: plotsEl,
            timeEl: timeEl
        });
        statusBadge.className = 'badge badge-emerald';
        statusBadge.innerText = 'Completed';
    } catch (err) {
        statusBadge.className = 'badge badge-purple';
        statusBadge.innerText = 'Failed';
    } finally {
        runBtn.disabled = false;
        runBtn.innerHTML = '<i class="fa-solid fa-play"></i> Run Code (Ctrl+Enter)';
    }
}

function switchProgramTab(pid, tab) {
    var consoleBtn = document.getElementById('tab_console_btn_' + pid);
    var plotsBtn = document.getElementById('tab_plots_btn_' + pid);
    var theoryBtn = document.getElementById('tab_theory_btn_' + pid);

    var consolePanel = document.getElementById('panel_console_' + pid);
    var plotsPanel = document.getElementById('panel_plots_' + pid);
    var theoryPanel = document.getElementById('panel_theory_' + pid);

    [consoleBtn, plotsBtn, theoryBtn].forEach(b => b && b.classList.remove('active'));
    [consolePanel, plotsPanel, theoryPanel].forEach(p => p && (p.style.display = 'none'));

    if (tab === 'console') {
        if (consoleBtn) consoleBtn.classList.add('active');
        if (consolePanel) consolePanel.style.display = 'block';
    } else if (tab === 'plots') {
        if (plotsBtn) plotsBtn.classList.add('active');
        if (plotsPanel) plotsPanel.style.display = 'block';
    } else if (tab === 'theory') {
        if (theoryBtn) theoryBtn.classList.add('active');
        if (theoryPanel) theoryPanel.style.display = 'block';
    }
}

function copyProgramCode(textareaId) {
    var pid = textareaId.replace('code_', '');
    var code = p4pEditors[pid] ? p4pEditors[pid].getValue() : document.getElementById(textareaId).value;
    navigator.clipboard.writeText(code).then(() => {
        showToast("Code copied to clipboard!", "success");
    });
}

function resetProgramCode(pid) {
    if (p4pEditors[pid] && p4pOriginalCodes[pid]) {
        p4pEditors[pid].setValue(p4pOriginalCodes[pid]);
        showToast("Code reset to default.", "success");
    }
}

function exportProgramPy(pid, title) {
    var code = p4pEditors[pid] ? p4pEditors[pid].getValue() : '';
    window.physicsRunner.exportPythonScript('physics_' + pid, code);
    showToast("Python script downloaded!", "success");
}

function exportProgramJupyter(pid, title) {
    var code = p4pEditors[pid] ? p4pEditors[pid].getValue() : '';
    window.physicsRunner.exportToJupyter(title, code, "Computational Physics Program #" + pid);
    showToast("Jupyter Notebook downloaded!", "success");
}

function toggleFullscreen(wrapperId) {
    var el = document.getElementById(wrapperId);
    if (!el) return;
    el.classList.toggle('fullscreen-mode');
}

function showToast(msg, type = 'success') {
    var container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.innerHTML = '<i class="fa-solid fa-circle-check"></i> <span>' + msg + '</span>';
    container.appendChild(toast);
    setTimeout(() => { toast.remove(); }, 3000);
}
</script>

<?php require_once __DIR__ . '/../../include/footer.php'; ?>
