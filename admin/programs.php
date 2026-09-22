<?php
/**
 * Python4Physics - Dedicated Program Uploader & Code Manager
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth.php';

$page_title = "Manage & Upload Programs";

$action = $_GET['action'] ?? 'list';
$selected_lang = $_GET['lang'] ?? 'python';
if (!in_array($selected_lang, ['python', 'gnuplot', 'latex'])) {
    $selected_lang = 'python';
}

$notice = "";
$error = "";

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['post_action'] ?? '';
    $lang = $_POST['language'] ?? 'python';
    if (!in_array($lang, ['python', 'gnuplot', 'latex'])) {
        $lang = 'python';
    }

    if ($post_action === 'save_program') {
        $id = (int)($_POST['id'] ?? 0);
        $menu_id = (int)($_POST['menu_id'] ?? 0);
        $submenu_id = (int)($_POST['submenu_id'] ?? 0);
        $program_id = (int)($_POST['program_id'] ?? 1);
        $algo = trim($_POST['algo'] ?? '');
        $explanation = trim($_POST['explanation'] ?? '');
        $content = $_POST['content'] ?? '';
        $gnuplot_output = $_POST['output'] ?? '';

        // Check if a file was uploaded
        if (isset($_FILES['code_file']) && $_FILES['code_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded_content = file_get_contents($_FILES['code_file']['tmp_name']);
            if (!empty($uploaded_content)) {
                $content = $uploaded_content;
            }
        }

        if ($menu_id <= 0 || $submenu_id <= 0 || empty($algo) || empty($content)) {
            $error = "Please fill in all required fields (Chapter, Subtopic, Title, and Program Code).";
        } else {
            try {
                $table = $lang; // table name matches 'python', 'gnuplot', 'latex'

                if ($id > 0) {
                    // Update existing
                    if ($lang === 'gnuplot') {
                        $stmt = $conn->prepare("UPDATE `$table` SET `menu_id` = :menu_id, `submenu_id` = :submenu_id, `program_id` = :program_id, `algo` = :algo, `explanation` = :explanation, `content` = :content, `output` = :output WHERE `id` = :id");
                        $stmt->execute([
                            ':menu_id' => $menu_id,
                            ':submenu_id' => $submenu_id,
                            ':program_id' => $program_id,
                            ':algo' => $algo,
                            ':explanation' => $explanation,
                            ':content' => $content,
                            ':output' => $gnuplot_output,
                            ':id' => $id
                        ]);
                    } else {
                        $stmt = $conn->prepare("UPDATE `$table` SET `menu_id` = :menu_id, `submenu_id` = :submenu_id, `program_id` = :program_id, `algo` = :algo, `explanation` = :explanation, `content` = :content WHERE `id` = :id");
                        $stmt->execute([
                            ':menu_id' => $menu_id,
                            ':submenu_id' => $submenu_id,
                            ':program_id' => $program_id,
                            ':algo' => $algo,
                            ':explanation' => $explanation,
                            ':content' => $content,
                            ':id' => $id
                        ]);
                    }
                    $notice = "Program #{$program_id} updated successfully!";
                } else {
                    // Insert new
                    if ($lang === 'gnuplot') {
                        $stmt = $conn->prepare("INSERT INTO `$table` (`menu_id`, `submenu_id`, `program_id`, `algo`, `explanation`, `content`, `output`) VALUES (:menu_id, :submenu_id, :program_id, :algo, :explanation, :content, :output)");
                        $stmt->execute([
                            ':menu_id' => $menu_id,
                            ':submenu_id' => $submenu_id,
                            ':program_id' => $program_id,
                            ':algo' => $algo,
                            ':explanation' => $explanation,
                            ':content' => $content,
                            ':output' => $gnuplot_output
                        ]);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO `$table` (`menu_id`, `submenu_id`, `program_id`, `algo`, `explanation`, `content`) VALUES (:menu_id, :submenu_id, :program_id, :algo, :explanation, :content)");
                        $stmt->execute([
                            ':menu_id' => $menu_id,
                            ':submenu_id' => $submenu_id,
                            ':program_id' => $program_id,
                            ':algo' => $algo,
                            ':explanation' => $explanation,
                            ':content' => $content
                        ]);
                    }
                    $notice = "New {$lang} program #{$program_id} ('" . htmlspecialchars($algo) . "') published successfully!";
                }
                $action = 'list';
                $selected_lang = $lang;
            } catch (PDOException $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    } elseif ($post_action === 'delete_program') {
        $del_id = (int)($_POST['id'] ?? 0);
        $del_lang = $_POST['language'] ?? 'python';
        if ($del_id > 0 && in_array($del_lang, ['python', 'gnuplot', 'latex'])) {
            try {
                $stmt = $conn->prepare("DELETE FROM `$del_lang` WHERE `id` = :id");
                $stmt->execute([':id' => $del_id]);
                $notice = "Program deleted successfully.";
                $selected_lang = $del_lang;
            } catch (PDOException $e) {
                $error = "Failed to delete program: " . $e->getMessage();
            }
        }
    }
}

// Fetch menus and submenus for cascade
$menus_stmt = $conn->query("SELECT language, menu_id, title FROM `p4p_menus` ORDER BY sort_order ASC, menu_id ASC");
$all_menus = $menus_stmt->fetchAll(PDO::FETCH_ASSOC);

$subs_stmt = $conn->query("SELECT language, menu_id, submenu_id, title FROM `p4p_submenus` ORDER BY menu_id ASC, sort_order ASC, submenu_id ASC");
$all_submenus = $subs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Map for Javascript cascading select
$cascade_data = ['python' => [], 'gnuplot' => [], 'latex' => []];
foreach ($all_menus as $m) {
    $cascade_data[$m['language']][$m['menu_id']] = [
        'title' => $m['title'],
        'submenus' => []
    ];
}
foreach ($all_submenus as $s) {
    if (isset($cascade_data[$s['language']][$s['menu_id']])) {
        $cascade_data[$s['language']][$s['menu_id']]['submenus'][$s['submenu_id']] = $s['title'];
    }
}

// If editing, fetch program
$edit_program = null;
if ($action === 'edit') {
    $edit_id = (int)($_GET['id'] ?? 0);
    $edit_lang = $_GET['lang'] ?? 'python';
    if ($edit_id > 0 && in_array($edit_lang, ['python', 'gnuplot', 'latex'])) {
        $stmt = $conn->prepare("SELECT * FROM `$edit_lang` WHERE `id` = :id");
        $stmt->execute([':id' => $edit_id]);
        $edit_program = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($edit_program) {
            $selected_lang = $edit_lang;
        }
    }
}

// If listing, fetch programs for selected language
$filter_menu = isset($_GET['menu']) && is_numeric($_GET['menu']) ? (int)$_GET['menu'] : null;
$programs_list = [];
if ($action === 'list') {
    if ($filter_menu) {
        $stmt = $conn->prepare("SELECT * FROM `$selected_lang` WHERE `menu_id` = :m_id ORDER BY `menu_id` ASC, `submenu_id` ASC, `program_id` ASC");
        $stmt->execute([':m_id' => $filter_menu]);
    } else {
        $stmt = $conn->query("SELECT * FROM `$selected_lang` ORDER BY `menu_id` ASC, `submenu_id` ASC, `program_id` ASC LIMIT 150");
    }
    $programs_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/layout_top.php';
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Program Management & Code Uploader</h1>
        <p class="admin-page-subtitle">Publish, upload, and update computational scripts with interactive CodeMirror support.</p>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <?php if ($action === 'create' || $action === 'edit'): ?>
            <a href="programs.php?lang=<?php echo urlencode($selected_lang); ?>" class="btn-admin btn-admin-secondary">
                <i class="fa-solid fa-list"></i> View All Programs
            </a>
        <?php else: ?>
            <a href="programs.php?action=create&lang=<?php echo urlencode($selected_lang); ?>" class="btn-admin btn-admin-primary">
                <i class="fa-solid fa-plus"></i> Upload New Program
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($notice)): ?>
    <div style="padding: 0.85rem 1.25rem; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.75rem; color: #34d399; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-circle-check"></i>
        <span><?php echo $notice; ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div style="padding: 0.85rem 1.25rem; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 0.75rem; color: #f87171; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<?php if ($action === 'create' || $action === 'edit'): ?>
    <!-- PROGRAM FORM (UPLOAD / CREATE / EDIT) -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">
                <i class="fa-solid fa-code" style="color: var(--admin-accent);"></i>
                <?php echo $action === 'edit' ? 'Edit Program #' . (int)$edit_program['program_id'] : 'Upload New Scientific Program'; ?>
            </h2>
        </div>

        <form method="POST" enctype="multipart/form-data" id="programForm">
            <input type="hidden" name="post_action" value="save_program">
            <input type="hidden" name="id" value="<?php echo $edit_program['id'] ?? 0; ?>">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div class="admin-form-group">
                    <label class="admin-label" for="prog_lang">Language Suite *</label>
                    <select name="language" id="prog_lang" class="admin-select" onchange="updateChapterOptions()">
                        <option value="python" <?php echo ($edit_program['lang'] ?? $selected_lang) === 'python' ? 'selected' : ''; ?>>Python 3</option>
                        <option value="gnuplot" <?php echo ($edit_program['lang'] ?? $selected_lang) === 'gnuplot' ? 'selected' : ''; ?>>GNUplot 6.0</option>
                        <option value="latex" <?php echo ($edit_program['lang'] ?? $selected_lang) === 'latex' ? 'selected' : ''; ?>>LaTeX Typesetting</option>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label class="admin-label" for="prog_menu">Chapter (Menu) *</label>
                    <select name="menu_id" id="prog_menu" class="admin-select" onchange="updateSubmenuOptions()" required>
                        <option value="">Select Chapter...</option>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label class="admin-label" for="prog_submenu">Subtopic (Submenu) *</label>
                    <select name="submenu_id" id="prog_submenu" class="admin-select" required>
                        <option value="">Select Subtopic...</option>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label class="admin-label" for="prog_program_id">Program Number (Prog ID) *</label>
                    <input type="number" name="program_id" id="prog_program_id" class="admin-input" value="<?php echo (int)($edit_program['program_id'] ?? 1); ?>" required min="1">
                </div>
            </div>

            <div class="admin-form-group">
                <label class="admin-label" for="prog_algo">Program Title / Algorithm Name *</label>
                <input type="text" name="algo" id="prog_algo" class="admin-input" placeholder="e.g. Runge-Kutta 4th Order Simulation of Damped Driven Oscillator" value="<?php echo htmlspecialchars($edit_program['algo'] ?? ''); ?>" required>
            </div>

            <div class="admin-form-group">
                <label class="admin-label" for="prog_explanation">Physical Theory / Algorithm Explanation (Supports KaTeX/Math)</label>
                <textarea name="explanation" id="prog_explanation" class="admin-textarea" rows="3" placeholder="Explain the underlying physical formula or numerical algorithm..."><?php echo htmlspecialchars($edit_program['explanation'] ?? ''); ?></textarea>
            </div>

            <!-- Drag & Drop / Direct Code File Upload -->
            <div class="upload-dropzone" id="dropZone" onclick="document.getElementById('fileInput').click()">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <h4 style="margin: 0.25rem 0; font-size: 1rem;">Click or Drag & Drop Code File to Auto-Load</h4>
                <p style="margin: 0; font-size: 0.825rem; color: var(--admin-text-muted);">
                    Supports .py, .gp, .plt, .tex, or .txt. Content will be instantly inserted into the live code editor below.
                </p>
                <input type="file" id="fileInput" name="code_file" accept=".py,.gp,.plt,.tex,.txt" style="display: none;" onchange="handleFileSelect(event)">
            </div>

            <div class="admin-form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                    <label class="admin-label" style="margin-bottom: 0;">Program Source Code *</label>
                    <span id="fileLoadedBadge" class="admin-badge admin-badge-cyan" style="display: none;">File Content Loaded</span>
                </div>
                <textarea name="content" id="prog_content" class="admin-textarea" rows="16" required><?php echo htmlspecialchars($edit_program['content'] ?? ''); ?></textarea>
            </div>

            <div class="admin-form-group" id="gnuplotOutputGroup" style="display: none;">
                <label class="admin-label">Optional Cached Plot Output (for GNUplot)</label>
                <textarea name="output" id="prog_output" class="admin-textarea" rows="3" placeholder="Optional SVG or text representation..."><?php echo htmlspecialchars($edit_program['output'] ?? ''); ?></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; border-top: 1px solid var(--admin-border); padding-top: 1.25rem;">
                <a href="programs.php?lang=<?php echo urlencode($selected_lang); ?>" class="btn-admin btn-admin-secondary">Cancel</a>
                <button type="submit" class="btn-admin btn-admin-primary">
                    <i class="fa-solid fa-floppy-disk"></i> <?php echo $action === 'edit' ? 'Save Changes' : 'Publish Program'; ?>
                </button>
            </div>
        </form>
    </div>

    <script>
    const cascadeData = <?php echo json_encode($cascade_data); ?>;
    const currentMenu = <?php echo (int)($edit_program['menu_id'] ?? 0); ?>;
    const currentSubmenu = <?php echo (int)($edit_program['submenu_id'] ?? 0); ?>;

    let editorInstance = null;

    document.addEventListener('DOMContentLoaded', () => {
        // Initialize CodeMirror
        editorInstance = CodeMirror.fromTextArea(document.getElementById('prog_content'), {
            lineNumbers: true,
            theme: 'material-ocean',
            mode: 'python',
            lineWrapping: true,
            indentUnit: 4
        });
        editorInstance.setSize("100%", "380px");

        updateChapterOptions(currentMenu);
        checkGnuplotOutputField();

        // Drag and drop handlers
        const dropZone = document.getElementById('dropZone');
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('dragover');
            }, false);
        });

        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                loadFile(files[0]);
            }
        });
    });

    function updateChapterOptions(preselectMenu = 0) {
        const lang = document.getElementById('prog_lang').value;
        const menuSelect = document.getElementById('prog_menu');
        menuSelect.innerHTML = '<option value="">Select Chapter...</option>';

        const chapters = cascadeData[lang] || {};
        for (const [mId, mData] of Object.entries(chapters)) {
            const opt = document.createElement('option');
            opt.value = mId;
            opt.textContent = `Chapter ${mId}: ${mData.title}`;
            if (parseInt(mId) === parseInt(preselectMenu)) {
                opt.selected = true;
            }
            menuSelect.appendChild(opt);
        }

        updateSubmenuOptions(currentSubmenu);
        checkGnuplotOutputField();

        // Switch CodeMirror mode
        if (editorInstance) {
            if (lang === 'latex') {
                editorInstance.setOption('mode', 'stex');
            } else if (lang === 'gnuplot') {
                editorInstance.setOption('mode', 'shell');
            } else {
                editorInstance.setOption('mode', 'python');
            }
        }
    }

    function updateSubmenuOptions(preselectSub = 0) {
        const lang = document.getElementById('prog_lang').value;
        const menuId = document.getElementById('prog_menu').value;
        const subSelect = document.getElementById('prog_submenu');
        subSelect.innerHTML = '<option value="">Select Subtopic...</option>';

        if (menuId && cascadeData[lang] && cascadeData[lang][menuId]) {
            const subtopics = cascadeData[lang][menuId].submenus || {};
            for (const [sId, sTitle] of Object.entries(subtopics)) {
                const opt = document.createElement('option');
                opt.value = sId;
                opt.textContent = `${menuId}.${sId}: ${sTitle}`;
                if (parseInt(sId) === parseInt(preselectSub)) {
                    opt.selected = true;
                }
                subSelect.appendChild(opt);
            }
        }
    }

    function checkGnuplotOutputField() {
        const lang = document.getElementById('prog_lang').value;
        const outputGroup = document.getElementById('gnuplotOutputGroup');
        if (outputGroup) {
            outputGroup.style.display = (lang === 'gnuplot') ? 'block' : 'none';
        }
    }

    function handleFileSelect(e) {
        const file = e.target.files[0];
        if (file) {
            loadFile(file);
        }
    }

    function loadFile(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const text = e.target.result;
            if (editorInstance) {
                editorInstance.setValue(text);
            } else {
                document.getElementById('prog_content').value = text;
            }
            const badge = document.getElementById('fileLoadedBadge');
            badge.style.display = 'inline-block';
            badge.textContent = `Loaded: ${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
        };
        reader.readAsText(file);
    }
    </script>

<?php else: ?>
    <!-- PROGRAMS DIRECTORY & TABLE LIST -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.75rem; overflow-x: auto;">
        <a href="?lang=python" class="btn-admin <?php echo $selected_lang === 'python' ? 'btn-admin-primary' : 'btn-admin-secondary'; ?>">
            <i class="fa-brands fa-python"></i> Python Programs
        </a>
        <a href="?lang=gnuplot" class="btn-admin <?php echo $selected_lang === 'gnuplot' ? 'btn-admin-primary' : 'btn-admin-secondary'; ?>">
            <i class="fa-solid fa-chart-line"></i> GNUplot Programs
        </a>
        <a href="?lang=latex" class="btn-admin <?php echo $selected_lang === 'latex' ? 'btn-admin-primary' : 'btn-admin-secondary'; ?>">
            <i class="fa-solid fa-file-invoice"></i> LaTeX Programs
        </a>
    </div>

    <!-- Filter toolbar -->
    <div class="admin-card" style="padding: 1rem 1.25rem; margin-bottom: 1.25rem;">
        <form method="GET" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <input type="hidden" name="lang" value="<?php echo htmlspecialchars($selected_lang); ?>">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label class="admin-label" style="margin-bottom: 0;">Filter Chapter:</label>
                <select name="menu" class="admin-select" style="width: auto; min-width: 200px;" onchange="this.form.submit()">
                    <option value="">All Chapters</option>
                    <?php if (isset($cascadeData[$selected_lang])): ?>
                        <?php foreach ($cascadeData[$selected_lang] as $mId => $mData): ?>
                            <option value="<?php echo $mId; ?>" <?php echo $filter_menu === (int)$mId ? 'selected' : ''; ?>>
                                Chapter <?php echo $mId; ?>: <?php echo htmlspecialchars($mData['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <?php if ($filter_menu): ?>
                <a href="?lang=<?php echo urlencode($selected_lang); ?>" class="btn-admin btn-admin-secondary btn-admin-sm">Reset Filter</a>
            <?php endif; ?>
            <span style="color: var(--admin-text-muted); font-size: 0.85rem; margin-left: auto;">
                Showing <?php echo count($programs_list); ?> program(s)
            </span>
        </form>
    </div>

    <div class="admin-card">
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 100px;">Location</th>
                        <th style="width: 80px;">Prog ID</th>
                        <th>Program Title / Algorithm</th>
                        <th style="width: 120px;">Code Size</th>
                        <th style="width: 220px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($programs_list)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--admin-text-muted); padding: 2.5rem;">
                                No programs found for this selection. Click <strong>Upload New Program</strong> to add one!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($programs_list as $prog): ?>
                            <tr>
                                <td>
                                    <span class="admin-badge admin-badge-cyan">
                                        <?php echo (int)$prog['menu_id']; ?>.<?php echo (int)$prog['submenu_id']; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>#<?php echo (int)$prog['program_id']; ?></strong>
                                </td>
                                <td style="font-weight: 600;">
                                    <?php echo htmlspecialchars($prog['algo'] ?? 'Untitled Program'); ?>
                                </td>
                                <td>
                                    <span style="color: var(--admin-text-muted); font-size: 0.8rem;">
                                        <?php echo strlen($prog['content'] ?? ''); ?> bytes
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" onclick="previewCode(<?php echo (int)$prog['id']; ?>, '<?php echo htmlspecialchars(addslashes($prog['algo'] ?? '')); ?>')">
                                        <i class="fa-solid fa-eye"></i> View
                                    </button>
                                    <a href="?action=edit&lang=<?php echo urlencode($selected_lang); ?>&id=<?php echo (int)$prog['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">
                                        <i class="fa-solid fa-pen"></i> Edit
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this program?');">
                                        <input type="hidden" name="post_action" value="delete_program">
                                        <input type="hidden" name="language" value="<?php echo htmlspecialchars($selected_lang); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int)$prog['id']; ?>">
                                        <button type="submit" class="btn-admin btn-admin-danger btn-admin-sm">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                    <!-- Hidden code store for preview -->
                                    <textarea id="code_store_<?php echo (int)$prog['id']; ?>" style="display: none;"><?php echo htmlspecialchars($prog['content']); ?></textarea>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Preview Code -->
    <div id="previewModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; padding: 1rem;">
        <div style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: 1rem; width: 100%; max-width: 800px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center;">
                <h3 id="previewTitle" style="margin: 0; font-size: 1.15rem;"></h3>
                <button type="button" onclick="closePreviewModal()" style="background: none; border: none; color: var(--admin-text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div style="padding: 1rem; overflow-y: auto; flex: 1;">
                <pre id="previewCodeBox" style="margin: 0; padding: 1rem; background: #0b0f19; border-radius: 0.5rem; color: #f8fafc; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; line-height: 1.5; white-space: pre-wrap; word-break: break-all;"></pre>
            </div>
            <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--admin-border); text-align: right;">
                <button type="button" class="btn-admin btn-admin-secondary" onclick="closePreviewModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
    function previewCode(id, title) {
        const code = document.getElementById('code_store_' + id).value;
        document.getElementById('previewTitle').textContent = title;
        document.getElementById('previewCodeBox').textContent = code;
        document.getElementById('previewModal').style.display = 'flex';
    }
    function closePreviewModal() {
        document.getElementById('previewModal').style.display = 'none';
    }
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_bottom.php'; ?>
