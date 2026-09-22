<?php
/**
 * Python4Physics - Interactive LaTeX Documentation Workbench
 * Live KaTeX Math Equation Preview + Online PDF Compilation.
 * Fully Responsive - Zero Horizontal Scroll.
 */
require_once __DIR__ . '/../../site_config.php';
require_once __DIR__ . '/../../db.php';
include_once __DIR__ . '/menu.php';

$menu_id    = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 1;
$submenu_id = isset($_GET['submenu_id']) ? intval($_GET['submenu_id']) : 1;

$chapter_title = $menu_titles[$menu_id] ?? "LaTeX Section {$menu_id}";
$submenu_list  = $sub_menu_titles[$menu_id] ?? [];
$subtopic_title = $submenu_list[$submenu_id] ?? "Topic {$submenu_id}";

$page_title = "LaTeX: {$chapter_title} - {$subtopic_title}";
$page_description = "Learn LaTeX for scientific and physics publications: {$subtopic_title}. View and customize source code with live preview.";

$programs = [];
if (isset($conn) && $conn !== null) {
    try {
        $stmt = $conn->prepare("SELECT id, program_id, content, algo, explanation FROM latex WHERE menu_id = ? AND submenu_id = ? ORDER BY program_id");
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
    <!-- Breadcrumbs -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--card-border);">
        <div style="min-width: 0;">
            <div style="font-size: 0.85rem; color: var(--text-dim); margin-bottom: 0.25rem;">
                <a href="<?php echo $siteurl; ?>"><i class="fa-solid fa-house"></i> Home</a> &gt; 
                <a href="<?php echo $siteurl; ?>program/latex/index.php">LaTeX</a> &gt; 
                <span><?php echo htmlspecialchars($chapter_title); ?></span>
            </div>
            <h1 style="font-size: 1.75rem; display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                <span class="badge badge-blue" style="font-size: 0.82rem;">LaTeX <?php echo $menu_id; ?>.<?php echo $submenu_id; ?></span>
                <span><?php echo htmlspecialchars($subtopic_title); ?></span>
            </h1>
        </div>

        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn-modern btn-secondary btn-sm sidebar-toggle-btn" id="latexSidebarToggleBtn" onclick="toggleLatexSidebar()">
                <i class="fa-solid fa-angles-left"></i> <span>Hide Sidebar</span>
            </button>
            <a href="<?php echo $siteurl; ?>program/latex/index.php" class="btn-modern btn-secondary btn-sm">
                <i class="fa-solid fa-bars-staggered"></i> LaTeX Index
            </a>
            <button type="button" class="btn-modern btn-secondary btn-sm trigger-global-search">
                <i class="fa-solid fa-magnifying-glass"></i> Search Codes
            </button>
        </div>
    </div>

    <!-- Workspace Layout with Collapsible Sidebar -->
    <div class="workbench-layout" id="latexWorkbenchLayout">
        <!-- Sidebar Navigation -->
        <aside class="workbench-sidebar" id="latexWorkbenchSidebar">
            <div style="font-size: 0.82rem; font-weight: 700; text-transform: uppercase; color: var(--primary); margin-bottom: 0.85rem; letter-spacing: 0.05em;">
                <i class="fa-solid fa-file-code"></i> <?php echo htmlspecialchars($chapter_title); ?>
            </div>
            <ul style="list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.35rem;">
                <?php foreach ($submenu_list as $sid => $stitle): 
                    $isActive = ($sid == $submenu_id);
                ?>
                    <li>
                        <a href="?menu_id=<?php echo $menu_id; ?>&submenu_id=<?php echo $sid; ?>" 
                           style="display: block; padding: 0.55rem 0.85rem; border-radius: var(--radius-sm); font-size: 0.88rem; text-decoration: none; color: <?php echo $isActive ? 'var(--primary)' : 'var(--text-muted)'; ?>; background: <?php echo $isActive ? 'rgba(59, 130, 246, 0.12)' : 'transparent'; ?>; font-weight: <?php echo $isActive ? '600' : '400'; ?>; border-left: <?php echo $isActive ? '3px solid var(--primary)' : '3px solid transparent'; ?>;">
                            <?php echo htmlspecialchars($stitle); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <hr style="border: 0; border-top: 1px solid var(--card-border); margin: 1.5rem 0;">

            <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-dim); margin-bottom: 0.5rem;">
                LATEX CHAPTERS
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
                    <i class="fa-solid fa-file-code fa-3x" style="color: var(--text-dim); margin-bottom: 1rem;"></i>
                    <h3>No LaTeX documents found for this topic</h3>
                </div>
            <?php else: ?>
                <?php foreach ($programs as $idx => $prog): 
                    $pid = $prog['id'];
                    $progNumber = $prog['program_id'];
                    $code = $prog['content'];
                    $algo = $prog['algo'];
                    $explanation = $prog['explanation'];
                ?>
                <section class="glass-card" style="margin-bottom: 2.5rem; max-width: 100%; box-sizing: border-box;" id="latex-<?php echo $progNumber; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.25rem;">
                        <div style="display: flex; align-items: center; gap: 0.65rem; min-width: 0;">
                            <span class="badge badge-blue" style="font-size: 0.85rem; flex-shrink: 0;">Document <?php echo $progNumber; ?></span>
                            <h2 style="font-size: 1.35rem; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($subtopic_title); ?></h2>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="copyLatexCode('latex_code_<?php echo $pid; ?>')">
                                <i class="fa-regular fa-copy"></i> Copy LaTeX
                            </button>
                            <a href="<?php echo $siteurl; ?>api/export.php?id=<?php echo $pid; ?>&lang=latex&format=tex" class="btn-modern btn-secondary btn-sm">
                                <i class="fa-solid fa-download"></i> .tex
                            </a>
                        </div>
                    </div>

                    <!-- Dual Pane Layout -->
                    <div class="workspace-container">
                        <!-- Left Pane: LaTeX Code Editor -->
                        <div class="workspace-pane">
                            <div class="editor-header">
                                <span class="editor-title"><i class="fa-solid fa-file-code" style="color: var(--primary);"></i> LaTeX Source</span>
                                <span class="badge badge-blue">Ready</span>
                            </div>
                            <div class="editor-wrapper" id="latex_wrapper_<?php echo $pid; ?>">
                                <textarea id="latex_code_<?php echo $pid; ?>"><?php echo htmlspecialchars($code); ?></textarea>
                            </div>
                            <div style="margin-top: 0.5rem; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <button type="button" class="btn-modern btn-primary" onclick="compileLatexPDF('<?php echo $pid; ?>')">
                                        <i class="fa-solid fa-file-pdf"></i> Compile Online PDF
                                    </button>
                                    <button type="button" class="btn-modern btn-secondary" onclick="renderLatexPreview('<?php echo $pid; ?>')">
                                        <i class="fa-solid fa-eye"></i> Refresh Math Preview
                                    </button>
                                </div>
                                <div>
                                    <button type="button" class="btn-modern btn-secondary btn-sm" onclick="toggleFullscreen('latex_wrapper_<?php echo $pid; ?>')">
                                        <i class="fa-solid fa-expand"></i> Fullscreen
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right Pane: Live Equation Preview & PDF -->
                        <div class="workspace-pane">
                            <div class="tabs-header">
                                <button class="tab-btn active" id="tab_preview_btn_<?php echo $pid; ?>" onclick="switchLatexTab('<?php echo $pid; ?>', 'preview')">
                                    <i class="fa-solid fa-square-root-variable"></i> Live Math Preview
                                </button>
                                <button class="tab-btn" id="tab_pdf_btn_<?php echo $pid; ?>" onclick="switchLatexTab('<?php echo $pid; ?>', 'pdf')">
                                    <i class="fa-solid fa-file-pdf"></i> PDF Viewer
                                </button>
                            </div>

                            <!-- Live Math Preview -->
                            <div id="panel_preview_<?php echo $pid; ?>" class="tab-content active">
                                <div class="theory-card" style="min-height: 480px; background: rgba(0,0,0,0.25);">
                                    <h4 style="color: var(--primary); margin-bottom: 1rem;"><i class="fa-solid fa-wand-magic-sparkles"></i> Rendered Output Preview</h4>
                                    <div id="latex_preview_target_<?php echo $pid; ?>" style="line-height: 1.8;">
                                        <!-- Equations rendered on load -->
                                    </div>
                                </div>
                            </div>

                            <!-- PDF Viewer Panel -->
                            <div id="panel_pdf_<?php echo $pid; ?>" class="tab-content">
                                <div class="glass-card" style="padding: 1rem; text-align: center; min-height: 480px;" id="latex_pdf_box_<?php echo $pid; ?>">
                                    <div style="color: var(--text-dim); padding-top: 5rem;">
                                        <i class="fa-solid fa-file-pdf fa-3x" style="color: var(--primary); margin-bottom: 1rem; display: block;"></i>
                                        Click <strong>"Compile Online PDF"</strong> to generate and view full PDF.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty(trim($algo ?? '')) || !empty(trim($explanation ?? ''))): ?>
                    <div class="theory-card" style="margin-top: 1.5rem;">
                        <?php if (!empty(trim($algo ?? ''))): ?>
                            <h4 style="color: var(--primary); margin-bottom: 0.5rem;"><i class="fa-solid fa-circle-info"></i> Formulation Guide</h4>
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
var latexEditors = {};

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('textarea[id^="latex_code_"]').forEach(function(textarea) {
        var pid = textarea.id.replace('latex_code_', '');
        var editor = CodeMirror.fromTextArea(textarea, {
            mode: 'stex',
            theme: 'material-darker',
            lineNumbers: true,
            lineWrapping: true,
            matchBrackets: true,
            viewportMargin: Infinity
        });
        latexEditors[pid] = editor;

        // Auto render equation preview on load
        renderLatexPreview(pid);
    });
});

function toggleLatexSidebar() {
    var layout = document.getElementById('latexWorkbenchLayout');
    var btn = document.getElementById('latexSidebarToggleBtn');
    if (!layout) return;

    var isCollapsed = layout.classList.toggle('sidebar-collapsed');
    if (btn) {
        btn.innerHTML = isCollapsed 
            ? '<i class="fa-solid fa-angles-right"></i> <span>Show Sidebar</span>' 
            : '<i class="fa-solid fa-angles-left"></i> <span>Hide Sidebar</span>';
    }
    setTimeout(() => {
        Object.values(latexEditors).forEach(ed => ed.refresh());
    }, 300);
}

function renderLatexPreview(pid) {
    var editor = latexEditors[pid];
    var code = editor ? editor.getValue() : document.getElementById('latex_code_' + pid).value;
    var target = document.getElementById('latex_preview_target_' + pid);
    if (!target) return;

    var bodyMatch = code.match(/\\begin\{document\}([\s\S]*?)\\end\{document\}/);
    var content = bodyMatch ? bodyMatch[1] : code;

    content = content
        .replace(/\\maketitle/g, '')
        .replace(/\\section\*?\{([^}]+)\}/g, '<h3 style="color: var(--accent); margin: 1rem 0 0.5rem 0;">$1</h3>')
        .replace(/\\subsection\*?\{([^}]+)\}/g, '<h4 style="color: var(--text); margin: 0.75rem 0 0.25rem 0;">$1</h4>')
        .replace(/\\begin\{equation\*?\}/g, '$$')
        .replace(/\\end\{equation\*?\}/g, '$$')
        .replace(/\\begin\{align\*?\}/g, '$$\\begin{aligned}')
        .replace(/\\end\{align\*?\}/g, '\\end{aligned}$$');

    target.innerHTML = content;

    if (typeof renderMathInElement === 'function') {
        renderMathInElement(target, {
            delimiters: [
                {left: '$$', right: '$$', display: true},
                {left: '$', right: '$', display: false},
                {left: '\\[', right: '\\]', display: true},
                {left: '\\(', right: '\\)', display: false}
            ],
            throwOnError: false
        });
    }
}

function compileLatexPDF(pid) {
    var editor = latexEditors[pid];
    var code = editor ? editor.getValue() : document.getElementById('latex_code_' + pid).value;
    var pdfBox = document.getElementById('latex_pdf_box_' + pid);

    switchLatexTab(pid, 'pdf');
    pdfBox.innerHTML = '<div style="padding-top: 5rem; color: var(--text-muted);"><i class="fa-solid fa-spinner fa-spin fa-2x" style="color: var(--primary); margin-bottom: 1rem; display: block;"></i> Compiling LaTeX document with LaTeX Online...</div>';

    var encoded = encodeURIComponent(code);
    var compileUrl = 'https://latexonline.cc/compile?text=' + encoded + '&format=pdf';

    pdfBox.innerHTML = `
        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-bottom: 0.75rem;">
            <a href="${compileUrl}" target="_blank" class="btn-modern btn-primary btn-sm"><i class="fa-solid fa-external-link"></i> Open PDF in New Tab</a>
        </div>
        <iframe src="${compileUrl}" style="width: 100%; height: 500px; border-radius: 8px; border: 1px solid var(--card-border);"></iframe>
    `;
}

function switchLatexTab(pid, tab) {
    var previewBtn = document.getElementById('tab_preview_btn_' + pid);
    var pdfBtn = document.getElementById('tab_pdf_btn_' + pid);
    var previewPanel = document.getElementById('panel_preview_' + pid);
    var pdfPanel = document.getElementById('panel_pdf_' + pid);

    if (tab === 'preview') {
        previewBtn.classList.add('active');
        pdfBtn.classList.remove('active');
        previewPanel.style.display = 'block';
        pdfPanel.style.display = 'none';
    } else {
        pdfBtn.classList.add('active');
        previewBtn.classList.remove('active');
        pdfPanel.style.display = 'block';
        previewPanel.style.display = 'none';
    }
}

function copyLatexCode(id) {
    var pid = id.replace('latex_code_', '');
    var code = latexEditors[pid] ? latexEditors[pid].getValue() : document.getElementById(id).value;
    navigator.clipboard.writeText(code).then(() => {
        alert("LaTeX code copied to clipboard!");
    });
}

function toggleFullscreen(wrapperId) {
    var el = document.getElementById(wrapperId);
    if (el) el.classList.toggle('fullscreen-mode');
}
</script>

<?php require_once __DIR__ . '/../../include/footer.php'; ?>