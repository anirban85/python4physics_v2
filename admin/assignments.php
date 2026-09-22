<?php
/**
 * Python4Physics - Dedicated Assignment & Lab Manager
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth.php';

$page_title = "Manage Assignments & Labs";

$action = $_GET['action'] ?? 'list';
$notice = "";
$error = "";

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['post_action'] ?? '';

    if ($post_action === 'save_assignment') {
        $id = (int)($_POST['id'] ?? 0);
        $category = trim($_POST['category'] ?? 'mechanics');
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $theory_equations = trim($_POST['theory_equations'] ?? '');
        $parameters = trim($_POST['parameters'] ?? '');
        $starter_code = $_POST['starter_code'] ?? '';
        $solution_code = $_POST['solution_code'] ?? '';

        // Check if starter code was uploaded via file
        if (isset($_FILES['code_file']) && $_FILES['code_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded = file_get_contents($_FILES['code_file']['tmp_name']);
            if (!empty($uploaded)) {
                $starter_code = $uploaded;
            }
        }

        if (empty($title) || empty($starter_code)) {
            $error = "Please provide both an Assignment Title and Starter Code.";
        } else {
            try {
                if ($id > 0) {
                    $stmt = $conn->prepare("UPDATE `assignments` SET `category` = :category, `title` = :title, `subtitle` = :subtitle, `description` = :description, `theory_equations` = :theory_equations, `parameters` = :parameters, `starter_code` = :starter_code, `solution_code` = :solution_code WHERE `id` = :id");
                    $stmt->execute([
                        ':category' => $category,
                        ':title' => $title,
                        ':subtitle' => $subtitle,
                        ':description' => $description,
                        ':theory_equations' => $theory_equations,
                        ':parameters' => $parameters,
                        ':starter_code' => $starter_code,
                        ':solution_code' => $solution_code,
                        ':id' => $id
                    ]);
                    $notice = "Assignment updated successfully!";
                } else {
                    $stmt = $conn->prepare("INSERT INTO `assignments` (`category`, `title`, `subtitle`, `description`, `theory_equations`, `parameters`, `starter_code`, `solution_code`) VALUES (:category, :title, :subtitle, :description, :theory_equations, :parameters, :starter_code, :solution_code)");
                    $stmt->execute([
                        ':category' => $category,
                        ':title' => $title,
                        ':subtitle' => $subtitle,
                        ':description' => $description,
                        ':theory_equations' => $theory_equations,
                        ':parameters' => $parameters,
                        ':starter_code' => $starter_code,
                        ':solution_code' => $solution_code
                    ]);
                    $notice = "New Assignment '" . htmlspecialchars($title) . "' created and published to live site!";
                }
                $action = 'list';
            } catch (PDOException $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    } elseif ($post_action === 'delete_assignment') {
        $del_id = (int)($_POST['id'] ?? 0);
        if ($del_id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM `assignments` WHERE `id` = :id");
                $stmt->execute([':id' => $del_id]);
                $notice = "Assignment removed successfully.";
            } catch (PDOException $e) {
                $error = "Failed to delete assignment: " . $e->getMessage();
            }
        }
    }
}

// If editing, fetch assignment
$edit_assign = null;
if ($action === 'edit') {
    $edit_id = (int)($_GET['id'] ?? 0);
    if ($edit_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM `assignments` WHERE `id` = :id");
        $stmt->execute([':id' => $edit_id]);
        $edit_assign = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Fetch all assignments
$assignments = $conn->query("SELECT * FROM `assignments` ORDER BY `id` ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/layout_top.php';
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Interactive Assignment Bank</h1>
        <p class="admin-page-subtitle">Curate computational problem sets, differential equations, and starter notebooks for students.</p>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <?php if ($action === 'create' || $action === 'edit'): ?>
            <a href="assignments.php" class="btn-admin btn-admin-secondary">
                <i class="fa-solid fa-list"></i> View All Assignments
            </a>
        <?php else: ?>
            <a href="assignments.php?action=create" class="btn-admin btn-admin-primary">
                <i class="fa-solid fa-plus"></i> Create New Assignment
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
    <!-- FORM (CREATE / EDIT) -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">
                <i class="fa-solid fa-graduation-cap" style="color: var(--admin-accent);"></i>
                <?php echo $action === 'edit' ? 'Edit Assignment #' . (int)$edit_assign['id'] : 'Create New Interactive Physics Lab'; ?>
            </h2>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="post_action" value="save_assignment">
            <input type="hidden" name="id" value="<?php echo (int)($edit_assign['id'] ?? 0); ?>">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div class="admin-form-group">
                    <label class="admin-label" for="assign_category">Discipline / Category *</label>
                    <select name="category" id="assign_category" class="admin-select" required>
                        <?php 
                        $cats = [
                            'mechanics' => 'Classical Mechanics & Dynamics',
                            'electrodynamics' => 'Electromagnetism & Potential Theory',
                            'quantum' => 'Quantum Mechanics & Wavepackets',
                            'thermo' => 'Thermodynamics & Statistical Physics',
                            'optics' => 'Optics & Wave Phenomena',
                            'astrophysics' => 'Astrophysics & Orbital Mechanics'
                        ];
                        $curr_cat = $edit_assign['category'] ?? 'mechanics';
                        foreach ($cats as $cKey => $cLabel) {
                            $sel = ($curr_cat === $cKey) ? 'selected' : '';
                            echo "<option value=\"{$cKey}\" {$sel}>{$cLabel}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label class="admin-label" for="assign_title">Assignment Title *</label>
                    <input type="text" name="title" id="assign_title" class="admin-input" placeholder="e.g. Chaotic Double Pendulum Dynamics via RK4" value="<?php echo htmlspecialchars($edit_assign['title'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="admin-form-group">
                <label class="admin-label" for="assign_subtitle">Subtitle / Short Summary</label>
                <input type="text" name="subtitle" id="assign_subtitle" class="admin-input" placeholder="e.g. Model sensitive dependence on initial conditions with Phase Space portraits" value="<?php echo htmlspecialchars($edit_assign['subtitle'] ?? ''); ?>">
            </div>

            <div class="admin-form-group">
                <label class="admin-label" for="assign_desc">Problem Description</label>
                <textarea name="description" id="assign_desc" class="admin-textarea" rows="3" placeholder="Provide background context and what the student is expected to calculate or simulate..."><?php echo htmlspecialchars($edit_assign['description'] ?? ''); ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div class="admin-form-group">
                    <label class="admin-label" for="assign_theory">Governing Equations (KaTeX / LaTeX $$...$$)</label>
                    <textarea name="theory_equations" id="assign_theory" class="admin-textarea" rows="4" placeholder="$$\frac{d^2\theta}{dt^2} + \frac{g}{L}\sin\theta = 0$$"><?php echo htmlspecialchars($edit_assign['theory_equations'] ?? ''); ?></textarea>
                </div>

                <div class="admin-form-group">
                    <label class="admin-label" for="assign_params">Physical Parameters & Numerical Constants</label>
                    <textarea name="parameters" id="assign_params" class="admin-textarea" rows="4" placeholder="Mass $m = 1.0\text{ kg}$, Length $L = 1.0\text{ m}$, $g = 9.81\text{ m/s}^2$."><?php echo htmlspecialchars($edit_assign['parameters'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Starter Code Upload Dropzone -->
            <div class="upload-dropzone" onclick="document.getElementById('assignFileInput').click()">
                <i class="fa-solid fa-file-code"></i>
                <h4 style="margin: 0.25rem 0; font-size: 1rem;">Upload Starter Python Script (.py or .txt)</h4>
                <p style="margin: 0; font-size: 0.825rem; color: var(--admin-text-muted);">
                    Click to browse or drop file here to auto-populate the Python editor below.
                </p>
                <input type="file" id="assignFileInput" name="code_file" accept=".py,.txt" style="display: none;" onchange="loadAssignFile(event)">
            </div>

            <div class="admin-form-group">
                <label class="admin-label" for="assign_starter">Starter Python Code *</label>
                <textarea name="starter_code" id="assign_starter" class="admin-textarea" rows="14" required><?php echo htmlspecialchars($edit_assign['starter_code'] ?? ''); ?></textarea>
            </div>

            <div class="admin-form-group">
                <label class="admin-label" for="assign_solution">Instructor Solution Code (Optional)</label>
                <textarea name="solution_code" id="assign_solution" class="admin-textarea" rows="6" placeholder="Optional full solution for grading reference..."><?php echo htmlspecialchars($edit_assign['solution_code'] ?? ''); ?></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; border-top: 1px solid var(--admin-border); padding-top: 1.25rem;">
                <a href="assignments.php" class="btn-admin btn-admin-secondary">Cancel</a>
                <button type="submit" class="btn-admin btn-admin-primary">
                    <i class="fa-solid fa-floppy-disk"></i> <?php echo $action === 'edit' ? 'Save Assignment' : 'Publish Assignment'; ?>
                </button>
            </div>
        </form>
    </div>

    <script>
    let cmEditor = null;
    document.addEventListener('DOMContentLoaded', () => {
        cmEditor = CodeMirror.fromTextArea(document.getElementById('assign_starter'), {
            lineNumbers: true,
            theme: 'material-ocean',
            mode: 'python',
            lineWrapping: true,
            indentUnit: 4
        });
        cmEditor.setSize("100%", "340px");
    });

    function loadAssignFile(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(evt) {
                if (cmEditor) {
                    cmEditor.setValue(evt.target.result);
                } else {
                    document.getElementById('assign_starter').value = evt.target.result;
                }
            };
            reader.readAsText(file);
        }
    }
    </script>

<?php else: ?>
    <!-- ASSIGNMENTS DIRECTORY TABLE -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="fa-solid fa-list-check" style="color: var(--admin-accent);"></i> Active Problem Sets</h2>
            <span style="color: var(--admin-text-muted); font-size: 0.85rem;">Total: <?php echo count($assignments); ?> Assignments</span>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th style="width: 140px;">Discipline</th>
                        <th>Problem Title & Subtitle</th>
                        <th style="width: 140px;">Created</th>
                        <th style="width: 180px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assignments)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--admin-text-muted); padding: 3rem;">
                                No assignments found. Click <strong>Create New Assignment</strong> to add one!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assignments as $as): ?>
                            <tr>
                                <td><strong>#<?php echo (int)$as['id']; ?></strong></td>
                                <td>
                                    <span class="admin-badge admin-badge-cyan" style="text-transform: capitalize;">
                                        <?php echo htmlspecialchars($as['category']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 700; font-size: 0.95rem;"><?php echo htmlspecialchars($as['title']); ?></div>
                                    <?php if (!empty($as['subtitle'])): ?>
                                        <div style="color: var(--admin-text-muted); font-size: 0.8rem; margin-top: 2px;">
                                            <?php echo htmlspecialchars($as['subtitle']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="color: var(--admin-text-muted); font-size: 0.8rem;">
                                    <?php echo htmlspecialchars(substr($as['created_at'] ?? '', 0, 10)); ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="<?php echo get_base_url(); ?>/assignments.php#assign-<?php echo (int)$as['id']; ?>" target="_blank" class="btn-admin btn-admin-secondary btn-admin-sm" title="Preview on live site">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                    <a href="?action=edit&id=<?php echo (int)$as['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">
                                        <i class="fa-solid fa-pen"></i> Edit
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete assignment \'<?php echo htmlspecialchars(addslashes($as['title'])); ?>\'?');">
                                        <input type="hidden" name="post_action" value="delete_assignment">
                                        <input type="hidden" name="id" value="<?php echo (int)$as['id']; ?>">
                                        <button type="submit" class="btn-admin btn-admin-danger btn-admin-sm">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_bottom.php'; ?>
