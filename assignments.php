<?php
/**
 * Python4Physics - Interactive Physics Assignments & Problem Bank
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';

$page_title = "Interactive Physics Problem Sets & Computational Labs";
$page_description = "Solve interactive computational physics problem sets in Classical Mechanics, Electrodynamics, Quantum Mechanics, and Thermodynamics with live in-browser testing.";

require_once __DIR__ . '/include/header.php';
require_once __DIR__ . '/include/navbar.php';

// Fetch assignments from database
$db_assignments = [];
try {
    $stmt = $conn->query("SELECT * FROM `assignments` ORDER BY `id` ASC");
    $db_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $db_assignments = [];
}
?>

<main class="container" style="padding-top: 3rem; padding-bottom: 5rem; max-width: 100vw; overflow-x: hidden;">
    <!-- Page Header -->
    <div style="text-align: center; max-width: 840px; margin: 0 auto 3.5rem auto;">
        <span class="badge badge-emerald"><i class="fa-solid fa-graduation-cap"></i> Academic Problem Bank</span>
        <h1 style="font-size: 2.8rem; margin-top: 0.75rem; margin-bottom: 0.75rem;">Interactive Physics <span class="gradient-text">Assignments & Labs</span></h1>
        <p style="font-size: 1.15rem; line-height: 1.6;">
            Curated problem sets designed for undergraduate and postgraduate physics students. Formulate numerical solutions, execute code directly in your browser, and verify physical outcomes.
        </p>
    </div>

    <!-- Filter Buttons -->
    <div style="display: flex; justify-content: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 3rem;">
        <button type="button" class="btn-modern btn-primary btn-sm" onclick="filterAssignments('all', this)">All Topics</button>
        <button type="button" class="btn-modern btn-secondary btn-sm" onclick="filterAssignments('mechanics', this)">Classical Mechanics</button>
        <button type="button" class="btn-modern btn-secondary btn-sm" onclick="filterAssignments('electrodynamics', this)">Electrodynamics</button>
        <button type="button" class="btn-modern btn-secondary btn-sm" onclick="filterAssignments('quantum', this)">Quantum Mechanics</button>
        <button type="button" class="btn-modern btn-secondary btn-sm" onclick="filterAssignments('thermo', this)">Thermodynamics</button>
    </div>

    <!-- Assignments List -->
    <div style="display: flex; flex-direction: column; gap: 3rem;">
        <?php if (empty($db_assignments)): ?>
            <div class="glass-card" style="text-align: center; padding: 4rem 2rem;">
                <i class="fa-solid fa-graduation-cap" style="font-size: 3rem; opacity: 0.4; margin-bottom: 1rem; color: var(--accent);"></i>
                <h2>No assignments currently loaded</h2>
                <p style="color: var(--text-muted);">Please check back soon or log in to the admin panel to add computational labs.</p>
            </div>
        <?php else: ?>
            <?php foreach ($db_assignments as $idx => $as): 
                $as_id = (int)$as['id'];
                $cat = htmlspecialchars($as['category']);
                $badge_class = 'badge-cyan';
                if ($cat === 'electrodynamics') $badge_class = 'badge-blue';
                elseif ($cat === 'quantum') $badge_class = 'badge-purple';
                elseif ($cat === 'thermo') $badge_class = 'badge-amber';
            ?>
                <article class="glass-card assignment-item" data-category="<?php echo $cat; ?>" id="assign-<?php echo $as_id; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <span class="badge <?php echo $badge_class; ?>" style="margin-bottom: 0.5rem; text-transform: capitalize;">
                                Lab <?php echo str_pad($as_id, 2, '0', STR_PAD_LEFT); ?> &bull; <?php echo $cat; ?>
                            </span>
                            <h2 style="font-size: 1.65rem; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($as['title']); ?></h2>
                            <?php if (!empty($as['subtitle'])): ?>
                                <p style="margin: 0; font-size: 0.95rem; color: var(--text-muted);"><?php echo htmlspecialchars($as['subtitle']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($as['description'])): ?>
                                <p style="margin: 0.5rem 0 0 0; font-size: 0.95rem;"><?php echo htmlspecialchars($as['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="exportAssignmentJupyter('assign_code_<?php echo $as_id; ?>', '<?php echo htmlspecialchars(addslashes($as['title'])); ?>')">
                                <i class="fa-solid fa-download"></i> Download Notebook (.ipynb)
                            </button>
                        </div>
                    </div>

                    <?php if (!empty($as['theory_equations']) || !empty($as['parameters'])): ?>
                        <div class="theory-card" style="margin-bottom: 1.5rem;">
                            <h4><i class="fa-solid fa-calculator" style="color: var(--accent); margin-right: 6px;"></i> Governing Equations & Formulation</h4>
                            <?php if (!empty($as['theory_equations'])): ?>
                                <div class="katex-display" style="overflow-x: auto; margin: 0.75rem 0;">
                                    <?php echo $as['theory_equations']; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($as['parameters'])): ?>
                                <p style="margin-top: 0.5rem;"><strong>Parameters:</strong> <?php echo htmlspecialchars($as['parameters']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Editor & Console -->
                    <div class="workspace-container" style="margin-top: 0; min-height: 400px; width: 100%; max-width: 100%;">
                        <div class="workspace-pane" style="min-width: 0; max-width: 100%;">
                            <div class="editor-header">
                                <span class="editor-title"><i class="fa-brands fa-python" style="color: var(--accent);"></i> Starter Solution</span>
                                <span id="assign_status_<?php echo $as_id; ?>" class="badge <?php echo $badge_class; ?>">Ready</span>
                            </div>
                            <div class="editor-wrapper" style="width: 100%; max-width: 100%;">
                                <textarea id="assign_code_<?php echo $as_id; ?>" class="assignment-code-textarea"><?php echo htmlspecialchars($as['starter_code']); ?></textarea>
                            </div>
                            <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <button type="button" class="btn-modern btn-primary" onclick="runAssignmentCode('assign_code_<?php echo $as_id; ?>', 'assign_status_<?php echo $as_id; ?>', 'assign_console_<?php echo $as_id; ?>', 'assign_plots_<?php echo $as_id; ?>')">
                                    <i class="fa-solid fa-play"></i> Run & Test Solution
                                </button>
                            </div>
                        </div>

                        <div class="workspace-pane" style="min-width: 0; max-width: 100%;">
                            <div class="tabs-header">
                                <button class="tab-btn active"><i class="fa-solid fa-chart-line"></i> Results & Graph</button>
                            </div>
                            <div class="plots-container" id="assign_plots_<?php echo $as_id; ?>" style="min-height: 240px; width: 100%; max-width: 100%;"></div>
                            <pre class="console-output" id="assign_console_<?php echo $as_id; ?>" style="min-height: 140px; max-width: 100%; overflow-x: hidden; white-space: pre-wrap; word-break: break-word;"></pre>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<script src="<?php echo $siteurl; ?>assets/js/pyodide-runner.js"></script>
<script>
var assignEditors = {};

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.assignment-code-textarea').forEach(el => {
        assignEditors[el.id] = CodeMirror.fromTextArea(el, {
            mode: 'python',
            theme: 'material-darker',
            lineNumbers: true,
            matchBrackets: true,
            lineWrapping: true,
            viewportMargin: Infinity
        });
    });
});

async function runAssignmentCode(codeId, statusId, consoleId, plotsId) {
    var editor = assignEditors[codeId];
    var code = editor ? editor.getValue() : document.getElementById(codeId).value;
    var status = document.getElementById(statusId);
    var consoleEl = document.getElementById(consoleId);
    var plotsEl = document.getElementById(plotsId);

    status.className = 'badge badge-amber';
    status.innerText = 'Running...';

    try {
        await window.physicsRunner.run(code, {
            onStatus: function(msg) { status.innerText = msg; },
            consoleEl: consoleEl,
            plotsEl: plotsEl
        });
        status.className = 'badge badge-emerald';
        status.innerText = 'Completed';
    } catch (e) {
        status.className = 'badge badge-purple';
        status.innerText = 'Error';
    }
}

function exportAssignmentJupyter(codeId, title) {
    var editor = assignEditors[codeId];
    var code = editor ? editor.getValue() : document.getElementById(codeId).value;
    window.physicsRunner.exportToJupyter(title, code, "Interactive Computational Physics Lab Assignment");
}

function filterAssignments(cat, btn) {
    document.querySelectorAll('.btn-modern').forEach(b => {
        if (b.innerText.toLowerCase().includes('topic') || b.innerText.toLowerCase().includes('mechanics') || b.innerText.toLowerCase().includes('electrodynamics') || b.innerText.toLowerCase().includes('quantum') || b.innerText.toLowerCase().includes('thermo')) {
            b.classList.remove('btn-primary');
            b.classList.add('btn-secondary');
        }
    });
    btn.classList.remove('btn-secondary');
    btn.classList.add('btn-primary');

    document.querySelectorAll('.assignment-item').forEach(item => {
        if (cat === 'all' || item.getAttribute('data-category') === cat) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/include/footer.php'; ?>
