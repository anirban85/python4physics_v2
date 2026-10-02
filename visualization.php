<?php
/**
 * Python4Physics - Interactive Physics Visualization Workbench
 * Dynamic Computational Simulations, Quantum Mechanics, and Wave Equations
 * Powered by Pyodide 0.27+ with WebAssembly, Matplotlib Slider controllers, and Jupyter Export.
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';
include_once __DIR__ . '/program/visualization/menu.php';

$menu_id    = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 1;
$submenu_id = isset($_GET['submenu_id']) ? intval($_GET['submenu_id']) : 1;

$chapter_title = $menu_titles[$menu_id] ?? "Module {$menu_id}";
$submenu_list  = $sub_menu_titles[$menu_id] ?? [];
$subtopic_title = $submenu_list[$submenu_id] ?? "Visualization Topic {$submenu_id}";

$page_title = "{$chapter_title} - {$subtopic_title} • Interactive Visualization";
$page_description = "Interactive computational physics simulation for {$subtopic_title} in {$chapter_title}. Run Python code and manipulate parameters in real time with Pyodide and Matplotlib Sliders.";

// Fetch programs from visualization table
$programs = [];
if (isset($conn) && $conn !== null) {
    try {
        $stmt = $conn->prepare("SELECT id, program_id, content, algo, explanation FROM visualization WHERE menu_id = ? AND submenu_id = ? ORDER BY program_id");
        $stmt->execute([$menu_id, $submenu_id]);
        $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $db_error = $e->getMessage();
    }
}

require_once __DIR__ . '/include/header.php';
require_once __DIR__ . '/include/navbar.php';
?>

<div class="container-fluid" style="padding-top: 1.5rem; padding-bottom: 4rem; max-width: 100%; box-sizing: border-box; overflow-x: hidden;">
    <!-- Breadcrumb & Topic Title Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--card-border);">
        <div style="min-width: 0;">
            <div style="font-size: 0.85rem; color: var(--text-dim); margin-bottom: 0.25rem;">
                <a href="<?php echo $siteurl; ?>"><i class="fa-solid fa-house"></i> Home</a> &gt; 
                <a href="<?php echo $siteurl; ?>visualization.php"><i class="fa-solid fa-sliders"></i> Visualization</a> &gt; 
                <span><?php echo htmlspecialchars($chapter_title); ?></span>
            </div>
            <h1 style="font-size: 1.75rem; display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; margin: 0;">
                <span class="badge badge-emerald" style="font-size: 0.82rem;"><i class="fa-solid fa-sliders"></i> Module <?php echo $menu_id; ?>.<?php echo $submenu_id; ?></span>
                <span><?php echo htmlspecialchars($subtopic_title); ?></span>
            </h1>
        </div>

        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn-modern btn-secondary btn-sm sidebar-toggle-btn" id="sidebarToggleBtn" onclick="toggleWorkbenchSidebar()">
                <i class="fa-solid fa-angles-left"></i> <span>Hide Sidebar</span>
            </button>
            <a href="<?php echo $siteurl; ?>program/visualization/index.php" class="btn-modern btn-secondary btn-sm">
                <i class="fa-solid fa-bars-staggered"></i> Modules Index
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
            <div style="font-size: 0.82rem; font-weight: 700; text-transform: uppercase; color: var(--accent); margin-bottom: 0.85rem; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.4rem;">
                <i class="fa-solid fa-sliders"></i> <span><?php echo htmlspecialchars($chapter_title); ?></span>
            </div>
            <ul style="list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.35rem;">
                <?php foreach ($submenu_list as $sid => $stitle): 
                    $isActive = ($sid == $submenu_id);
                ?>
                    <li>
                        <a href="?menu_id=<?php echo $menu_id; ?>&submenu_id=<?php echo $sid; ?>" 
                           style="display: block; padding: 0.55rem 0.85rem; border-radius: var(--radius-sm); font-size: 0.88rem; text-decoration: none; color: <?php echo $isActive ? 'var(--accent)' : 'var(--text-muted)'; ?>; background: <?php echo $isActive ? 'rgba(56, 189, 248, 0.12)' : 'transparent'; ?>; font-weight: <?php echo $isActive ? '600' : '400'; ?>; border-left: <?php echo $isActive ? '3px solid var(--accent)' : '3px solid transparent'; ?>;">
                            <?php echo htmlspecialchars($stitle); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <hr style="border: 0; border-top: 1px solid var(--card-border); margin: 1.5rem 0;">

            <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-dim); margin-bottom: 0.5rem; text-transform: uppercase;">
                Select Module
            </div>
            <select class="btn-modern btn-secondary" style="width: 100%; font-size: 0.85rem; padding: 0.5rem;" onchange="if(this.value) window.location.href=this.value;">
                <?php foreach ($menu_titles as $mid => $mtitle): ?>
                    <option value="?menu_id=<?php echo $mid; ?>&submenu_id=1" <?php echo ($mid == $menu_id) ? 'selected' : ''; ?>>
                        Mod. <?php echo $mid; ?>: <?php echo htmlspecialchars($mtitle); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </aside>

        <!-- Main Programs Area -->
        <main style="min-width: 0; max-width: 100%; overflow: hidden;">
            <?php if (isset($db_error) || !isset($conn) || $conn === null): ?>
                <div class="glass-card" style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); padding: 1.5rem; margin-bottom: 2rem; border-radius: var(--radius-md);">
                    <div style="display: flex; align-items: center; gap: 0.75rem; color: #ef4444; font-weight: 700; margin-bottom: 0.5rem;">
                        <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
                        <span>Database Connection Notice</span>
                    </div>
                    <p style="color: var(--text-color); margin-bottom: 0.5rem; font-size: 0.95rem;">
                        Could not connect to the MySQL database: <code><?php echo htmlspecialchars($db_error ?? 'Database connection not initialized'); ?></code>
                    </p>
                </div>
            <?php endif; ?>

            <?php if (empty($programs)): ?>
                <div class="glass-card" style="text-align: center; padding: 4rem 2rem;">
                    <i class="fa-solid fa-sliders fa-3x" style="color: var(--text-dim); margin-bottom: 1rem;"></i>
                    <h3>No simulation programs found for this subtopic</h3>
                    <p style="color: var(--text-dim);">Please select another topic from the sidebar or add one from Admin.</p>
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
                            <span class="badge badge-emerald" style="font-size: 0.85rem; flex-shrink: 0;"><i class="fa-solid fa-sliders"></i> Simulation <?php echo $progNumber; ?></span>
                            <h2 style="font-size: 1.35rem; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($subtopic_title); ?></h2>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="copyProgramCode('code_<?php echo $pid; ?>')" title="Copy Code to Clipboard">
                                <i class="fa-regular fa-copy"></i> Copy
                            </button>
                            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="exportProgramPy('<?php echo $pid; ?>', '<?php echo addslashes($subtopic_title); ?>')" title="Download Python Script">
                                <i class="fa-brands fa-python"></i> .py
                            </button>
                            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="exportProgramJupyter('<?php echo $pid; ?>', '<?php echo addslashes($subtopic_title); ?> - Simulation <?php echo $progNumber; ?>')" title="Download Jupyter Notebook">
                                <i class="fa-solid fa-book-journal-whills"></i> .ipynb
                            </button>
                        </div>
                    </div>

                    <!-- Dual-Pane Workspace for this Program -->
                    <div class="workspace-container">
                        <!-- Left Pane: Editor -->
                        <div class="workspace-pane">
                            <div class="editor-header">
                                <span class="editor-title"><i class="fa-brands fa-python" style="color: var(--accent);"></i> Python 3.11 with Matplotlib Slider</span>
                                <span id="status_<?php echo $pid; ?>" class="badge badge-cyan">Ready</span>
                            </div>
                            <div class="editor-wrapper" id="wrapper_<?php echo $pid; ?>">
                                <textarea id="code_<?php echo $pid; ?>"><?php echo htmlspecialchars($code); ?></textarea>
                            </div>
                            <!-- Editor Actions -->
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                                    <button type="button" class="btn-modern btn-primary" id="run_btn_<?php echo $pid; ?>" onclick="executeProgram('<?php echo $pid; ?>')">
                                        <i class="fa-solid fa-play"></i> Run Code (Ctrl+Enter)
                                    </button>
                                    <button type="button" class="btn-modern btn-danger" id="stop_btn_<?php echo $pid; ?>" onclick="stopProgram('<?php echo $pid; ?>')" style="display: none;">
                                        <i class="fa-solid fa-stop"></i> Stop
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
                                <button class="tab-btn active" id="tab_plots_btn_<?php echo $pid; ?>" onclick="switchProgramTab('<?php echo $pid; ?>', 'plots')">
                                    <i class="fa-solid fa-sliders"></i> Interactive Simulation Plot
                                </button>
                                <button class="tab-btn" id="tab_console_btn_<?php echo $pid; ?>" onclick="switchProgramTab('<?php echo $pid; ?>', 'console')">
                                    <i class="fa-solid fa-terminal"></i> Console Output
                                </button>
                                <?php if (!empty(trim($algo ?? '')) || !empty(trim($explanation ?? ''))): ?>
                                <button class="tab-btn" id="tab_theory_btn_<?php echo $pid; ?>" onclick="switchProgramTab('<?php echo $pid; ?>', 'theory')">
                                    <i class="fa-solid fa-square-root-variable"></i> Theory &amp; Math
                                </button>
                                <?php endif; ?>
                            </div>

                            <!-- Plots Output (Default active for Visualization suite) -->
                            <div id="panel_plots_<?php echo $pid; ?>" class="tab-content active">
                                <div id="plots_<?php echo $pid; ?>" class="plots-container" style="min-height: 380px; display: flex; align-items: center; justify-content: center;">
                                    <div style="color: var(--text-dim); text-align: center; padding: 2rem;">
                                        <i class="fa-solid fa-circle-play fa-3x" style="color: var(--accent); margin-bottom: 0.85rem; display: block; opacity: 0.85;"></i>
                                        <h4 style="color: var(--text); margin-bottom: 0.35rem;">Interactive Physics Ready</h4>
                                        <p style="margin: 0; font-size: 0.9rem;">Click <strong>"Run Code"</strong> to launch simulation and interactive parameter sliders.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Console Output -->
                            <div id="panel_console_<?php echo $pid; ?>" class="tab-content">
                                <pre id="console_<?php echo $pid; ?>" class="console-output" style="min-height: 380px;"></pre>
                                <div id="time_<?php echo $pid; ?>" class="console-timing"></div>
                            </div>

                            <!-- Theory & Algorithm Formulation -->
                            <?php if (!empty(trim($algo ?? '')) || !empty(trim($explanation ?? ''))): ?>
                            <div id="panel_theory_<?php echo $pid; ?>" class="tab-content">
                                <div class="theory-card" style="min-height: 380px; max-height: 520px; overflow-y: auto;">
                                    <?php if (!empty(trim($algo ?? ''))): ?>
                                        <div style="margin-bottom: 1.25rem;">
                                            <h4 style="color: var(--accent); margin-bottom: 0.5rem;"><i class="fa-solid fa-diagram-project"></i> Mathematical Problem Formulation</h4>
                                            <div><?php echo $algo; ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty(trim($explanation ?? ''))): ?>
                                        <div>
                                            <h4 style="color: var(--primary); margin-bottom: 0.5rem;"><i class="fa-solid fa-book-open-reader"></i> Theoretical Background &amp; Explanation</h4>
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
<script src="<?php echo $siteurl; ?>assets/js/pyodide-runner.js?v=<?php echo file_exists(__DIR__ . '/assets/js/pyodide-runner.js') ? filemtime(__DIR__ . '/assets/js/pyodide-runner.js') : time(); ?>"></script>

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

        p4pEditors[pid] = editor;

        editor.setOption("extraKeys", {
            "Ctrl-Enter": function(cm) {
                executeProgram(pid);
            },
            "Cmd-Enter": function(cm) {
                executeProgram(pid);
            }
        });
    });

    // Automatically trigger KaTeX / MathJax typesetting
    if (window.renderMathInElement) {
        renderMathInElement(document.body, {
            delimiters: [
                {left: '$$', right: '$$', display: true},
                {left: '\\[', right: '\\]', display: true},
                {left: '$', right: '$', display: false},
                {left: '\\(', right: '\\)', display: false}
            ],
            throwOnError: false
        });
    } else if (window.MathJax && window.MathJax.typesetPromise) {
        window.MathJax.typesetPromise();
    }
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
    setTimeout(() => {
        Object.values(p4pEditors).forEach(ed => ed.refresh());
    }, 300);
}

function switchProgramTab(pid, tabName) {
    var tabs = ['console', 'plots', 'theory'];
    tabs.forEach(function(t) {
        var panel = document.getElementById('panel_' + t + '_' + pid);
        var btn = document.getElementById('tab_' + t + '_btn_' + pid);
        if (panel) {
            if (t === tabName) {
                panel.classList.add('active');
            } else {
                panel.classList.remove('active');
            }
        }
        if (btn) {
            if (t === tabName) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        }
    });

    if (tabName === 'theory') {
        if (window.renderMathInElement) {
            renderMathInElement(document.getElementById('panel_theory_' + pid), {
                delimiters: [
                    {left: '$$', right: '$$', display: true},
                    {left: '\\[', right: '\\]', display: true},
                    {left: '$', right: '$', display: false},
                    {left: '\\(', right: '\\)', display: false}
                ],
                throwOnError: false
            });
        } else if (window.MathJax && window.MathJax.typesetPromise) {
            window.MathJax.typesetPromise();
        }
    }
}

async function executeProgram(pid) {
    var editor = p4pEditors[pid];
    if (!editor) return;

    var code = editor.getValue();
    var runBtn = document.getElementById('run_btn_' + pid);
    var stopBtn = document.getElementById('stop_btn_' + pid);
    var statusBadge = document.getElementById('status_' + pid);
    var consoleEl = document.getElementById('console_' + pid);
    var plotsEl = document.getElementById('plots_' + pid);
    var timeEl = document.getElementById('time_' + pid);

    if (stopBtn) stopBtn.style.display = 'inline-flex';
    runBtn.disabled = true;
    runBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Running...';
    statusBadge.className = 'badge badge-amber';
    statusBadge.innerText = 'Executing...';

    // If code uses plotting or sliders, switch to plots tab immediately
    if (code.includes('plt.') || code.includes('matplotlib') || code.includes('Slider')) {
        switchProgramTab(pid, 'plots');
    }

    try {
        await window.physicsRunner.run(code, {
            pid: pid,
            onStatus: function(msg) { 
                if (statusBadge) statusBadge.innerText = msg; 
            },
            onPlotsReady: function(count, isAnim, hasSliders) {
                switchProgramTab(pid, 'plots');
                var plotBtn = document.getElementById('tab_plots_btn_' + pid);
                if (plotBtn) {
                    if (hasSliders) {
                        plotBtn.innerHTML = '<i class="fa-solid fa-sliders" style="color: #818cf8;"></i> Interactive Controls';
                    } else if (isAnim) {
                        plotBtn.innerHTML = '<i class="fa-solid fa-play" style="color: #10b981;"></i> Animated View (' + count + ')';
                    } else {
                        plotBtn.innerHTML = '<i class="fa-regular fa-image"></i> Rendered Plots (' + count + ')';
                    }
                }
                if (hasSliders) {
                    statusBadge.className = 'badge badge-primary';
                    statusBadge.innerText = 'Interactive';
                } else if (isAnim) {
                    statusBadge.className = 'badge badge-emerald';
                    statusBadge.innerText = 'Animating';
                }
            },
            consoleEl: consoleEl,
            plotsEl: plotsEl,
            timeEl: timeEl
        });
        if (statusBadge.innerText !== 'Animating' && statusBadge.innerText !== 'Interactive') {
            statusBadge.className = 'badge badge-emerald';
            statusBadge.innerText = 'Completed';
        }
    } catch (err) {
        statusBadge.className = 'badge badge-danger';
        statusBadge.innerText = 'Failed';
        if (stopBtn) stopBtn.style.display = 'none';
        console.error("Execution error for program " + pid + ":", err);
    } finally {
        runBtn.disabled = false;
        runBtn.innerHTML = '<i class="fa-solid fa-play"></i> Run Code (Ctrl+Enter)';
    }
}

function stopProgram(pid) {
    if (window.physicsRunner) {
        window.physicsRunner.stopAnimation(pid);
        window.physicsRunner.stopSliders(pid);
    }
    var runBtn = document.getElementById('run_btn_' + pid);
    var stopBtn = document.getElementById('stop_btn_' + pid);
    var statusBadge = document.getElementById('status_' + pid);

    if (stopBtn) stopBtn.style.display = 'none';
    if (runBtn) {
        runBtn.disabled = false;
        runBtn.innerHTML = '<i class="fa-solid fa-play"></i> Run Code (Ctrl+Enter)';
    }
    if (statusBadge) {
        statusBadge.className = 'badge badge-cyan';
        statusBadge.innerText = 'Ready';
    }
}

function copyProgramCode(textareaId) {
    var pid = textareaId.replace('code_', '');
    var code = p4pEditors[pid] ? p4pEditors[pid].getValue() : document.getElementById(textareaId).value;
    navigator.clipboard.writeText(code).then(function() {
        if (typeof showToast === 'function') {
            showToast("Simulation Python code copied to clipboard!", "success");
        } else {
            alert("Code copied to clipboard!");
        }
    }).catch(function(err) {
        console.error("Could not copy text: ", err);
    });
}

function resetProgramCode(pid) {
    if (p4pOriginalCodes[pid] && p4pEditors[pid]) {
        p4pEditors[pid].setValue(p4pOriginalCodes[pid]);
        if (typeof showToast === 'function') {
            showToast("Code reset to original simulation state.", "info");
        }
    }
}

function exportProgramPy(pid, title) {
    var code = p4pEditors[pid] ? p4pEditors[pid].getValue() : '';
    var blob = new Blob([code], { type: 'text/x-python;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    var filename = (title || 'simulation_' + pid).toLowerCase().replace(/[^a-z0-9]+/g, '_') + '.py';
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function exportProgramJupyter(pid, title) {
    window.open('<?php echo $siteurl; ?>api/export.php?id=' + pid + '&lang=visualization&format=ipynb', '_blank');
}

function toggleFullscreen(wrapperId) {
    var wrapper = document.getElementById(wrapperId);
    if (!wrapper) return;
    if (!document.fullscreenElement) {
        wrapper.requestFullscreen().catch(function(err) {
            alert("Error attempting to enable fullscreen: " + err.message);
        });
    } else {
        document.exitFullscreen();
    }
}
</script>

<?php require_once __DIR__ . '/include/footer.php'; ?>
