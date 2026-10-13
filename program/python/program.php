<?php
/**
 * Python4Physics - Interactive Python Physics Workbench
 * Client-Side Execution via Pyodide 0.27+ with WebAssembly, Matplotlib capture, and Jupyter Export.
 * Fully Responsive - Zero Horizontal Scroll.
 */
require_once __DIR__ . '/../../site_config.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../include/user_auth.php';
require_once __DIR__ . '/../../include/physics_pedagogy.php';
include_once __DIR__ . '/menu.php';

$menu_id    = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 1;
$submenu_id = isset($_GET['submenu_id']) ? intval($_GET['submenu_id']) : 1;

$chapter_title = $menu_titles[$menu_id] ?? "Chapter {$menu_id}";
$submenu_list  = $sub_menu_titles[$menu_id] ?? [];
$subtopic_title = $submenu_list[$submenu_id] ?? "Topic {$submenu_id}";

// Load authoritative university-grade physics pedagogy and numerical analysis
$pedagogy = get_physics_pedagogical_guide($menu_id, $submenu_id, $chapter_title, $subtopic_title);

$page_title = "{$chapter_title}: {$subtopic_title} - Computational Physics Formulation & Simulation";
$page_description = "Theoretical principles, mathematical derivation, numerical error analysis, and interactive Python simulation for {$subtopic_title} ({$chapter_title}). Run Python in-browser with Pyodide.";

$is_user_auth = is_user_logged_in();
$current_user = get_logged_in_user();

if ($is_user_auth) {
    log_user_activity('view_topic', null, $page_title, $menu_id, $submenu_id);
}

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
                           style="display: block; padding: 0.55rem 0.85rem; border-radius: var(--radius-sm); font-size: 0.88rem; text-decoration: none; color: <?php echo $isActive ? 'var(--accent)' : 'var(--text-muted)'; ?>; background: <?php echo $isActive ? 'rgba(56, 189, 248, 0.12)' : 'transparent'; ?>; font-weight: <?php echo $isActive ? '600' : '400'; ?>; border-left: <?php echo $isActive ? '3px solid var(--accent)' : '3px solid transparent'; ?>;">
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
            <?php if (isset($db_error) || !isset($conn) || $conn === null): ?>
                <div class="glass-card" style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); padding: 1.5rem; margin-bottom: 2rem; border-radius: var(--radius-md);">
                    <div style="display: flex; align-items: center; gap: 0.75rem; color: #ef4444; font-weight: 700; margin-bottom: 0.5rem;">
                        <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
                        <span>Database Connection Notice</span>
                    </div>
                    <p style="color: var(--text-color); margin-bottom: 0.5rem; font-size: 0.95rem;">
                        Could not connect to the MySQL database: <code><?php echo htmlspecialchars($db_error ?? 'Database connection not initialized'); ?></code>
                    </p>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                        Please verify that <strong><code>db_config.php</code></strong> in the website root directory contains the correct database name (e.g. <code>python4p_v2</code>), username, and password.
                    </p>
                </div>
            <?php endif; ?>
            <?php if (!$is_user_auth): ?>
                <!-- Member Access Gate Banner (Preserves open reading of theory for AdSense & Googlebot, while gating code execution) -->
                <div class="glass-card" style="background: linear-gradient(135deg, rgba(56, 189, 248, 0.1), rgba(129, 140, 248, 0.08)); border: 1px solid rgba(56, 189, 248, 0.25); padding: 1.25rem 1.5rem; margin-bottom: 2rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(56, 189, 248, 0.2); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.25rem; flex-shrink: 0;">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-color); margin-bottom: 0.2rem;">
                                Interactive Physics Lab & Code Access
                            </div>
                            <div style="font-size: 0.88rem; color: var(--text-muted);">
                                Academic derivations and theoretical formulations are publicly readable below. Sign in with Google or GitHub to execute live Pyodide simulations, copy scripts, and export Jupyter Notebooks.
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="promptLoginModal('banner')" class="btn-modern btn-primary" style="white-space: nowrap;">
                        <i class="fa-solid fa-right-to-bracket"></i> Sign In to Run Code
                    </button>
                </div>
            <?php endif; ?>

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
                            <?php if ($is_user_auth): ?>
                            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="copyProgramCode('code_<?php echo $pid; ?>')">
                                <i class="fa-regular fa-copy"></i> Copy
                            </button>
                            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="exportProgramPy('<?php echo $pid; ?>', '<?php echo addslashes($subtopic_title); ?>')">
                                <i class="fa-brands fa-python"></i> .py
                            </button>
                            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="exportProgramJupyter('<?php echo $pid; ?>', '<?php echo addslashes($subtopic_title); ?> - Program <?php echo $progNumber; ?>')">
                                <i class="fa-solid fa-book-journal-whills"></i> .ipynb
                            </button>
                            <?php else: ?>
                            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="promptLoginModal('copy_code')">
                                <i class="fa-solid fa-lock" style="color: var(--accent); font-size: 0.8rem;"></i> Copy
                            </button>
                            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="promptLoginModal('export_py')">
                                <i class="fa-solid fa-lock" style="color: var(--accent); font-size: 0.8rem;"></i> .py
                            </button>
                            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="promptLoginModal('export_ipynb')">
                                <i class="fa-solid fa-lock" style="color: var(--accent); font-size: 0.8rem;"></i> .ipynb
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Dual-Pane Workspace for this Program -->
                    <div class="workspace-container">
                        <!-- Left Pane: Editor -->
                        <div class="workspace-pane">
                            <div class="editor-header">
                                <span class="editor-title"><i class="fa-brands fa-python" style="color: var(--accent);"></i> Python 3.11</span>
                                <?php if ($is_user_auth): ?>
                                <span id="status_<?php echo $pid; ?>" class="badge badge-cyan">Ready</span>
                                <?php else: ?>
                                <span class="badge badge-amber" style="cursor: pointer;" onclick="promptLoginModal('status')"><i class="fa-solid fa-lock"></i> Sign In to Run</span>
                                <?php endif; ?>
                            </div>
                            <div class="editor-wrapper" id="wrapper_<?php echo $pid; ?>">
                                <button type="button" class="fullscreen-exit-floating-btn" onclick="toggleFullscreen('wrapper_<?php echo $pid; ?>')" title="Exit Fullscreen (Esc)">
                                    <i class="fa-solid fa-compress"></i> <span>Exit Fullscreen</span> <kbd>Esc</kbd>
                                </button>
                                <textarea id="code_<?php echo $pid; ?>"><?php echo htmlspecialchars($code); ?></textarea>
                            </div>
                            <!-- Editor Actions -->
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                                    <?php if ($is_user_auth): ?>
                                    <button type="button" class="btn-modern btn-primary" id="run_btn_<?php echo $pid; ?>" onclick="executeProgram('<?php echo $pid; ?>')">
                                        <i class="fa-solid fa-play"></i> Run Code (Ctrl+Enter)
                                    </button>
                                    <button type="button" class="btn-modern btn-danger" id="stop_btn_<?php echo $pid; ?>" onclick="stopProgram('<?php echo $pid; ?>')" style="display: none;">
                                        <i class="fa-solid fa-stop"></i> Stop
                                    </button>
                                    <button type="button" class="btn-modern btn-secondary" onclick="resetProgramCode('<?php echo $pid; ?>')">
                                        <i class="fa-solid fa-rotate-left"></i> Reset
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn-modern btn-primary" onclick="promptLoginModal('run_code')">
                                        <i class="fa-solid fa-lock"></i> Sign In to Run Code
                                    </button>
                                    <button type="button" class="btn-modern btn-secondary" onclick="promptLoginModal('reset_code')">
                                        <i class="fa-solid fa-rotate-left"></i> Reset
                                    </button>
                                    <?php endif; ?>
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

                            <!-- Theory & Algorithm Formulation (Only when program has specific theoretical notes) -->
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
                                        <h4 style="color: var(--primary); margin-bottom: 0.5rem;"><i class="fa-solid fa-book-open-reader"></i> Theoretical Background & Computational Mechanics</h4>
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

            <!-- ========================================================================= -->
            <!-- SCHOLARLY PHYSICS THEORY, MATHEMATICAL FORMULATION & NUMERICAL ANALYSIS  -->
            <!-- High-Value Educational Content to Fulfill Google AdSense Policy Criteria -->
            <!-- ========================================================================= -->
            <article class="glass-card pedagogical-article" style="margin-top: 2rem; margin-bottom: 2.5rem; padding: 2.25rem; border-radius: var(--radius-md); border-top: 4px solid var(--accent); background: var(--card-bg);">
                <header style="margin-bottom: 1.75rem; border-bottom: 1px solid var(--card-border); padding-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">
                        <i class="fa-solid fa-graduation-cap"></i> Theoretical Principles & Computational Physics Guide
                    </div>
                    <h2 style="font-size: 1.65rem; color: var(--text-color); margin: 0 0 0.5rem 0;">
                        <?php echo htmlspecialchars($pedagogy['title']); ?>
                    </h2>
                    <div style="font-size: 0.85rem; color: var(--text-dim); display: flex; gap: 1.5rem; flex-wrap: wrap;">
                        <span><i class="fa-solid fa-user-pen"></i> Dr. Alorika Chatterjee & Dr. Anirban Shaw</span>
                        <span><i class="fa-solid fa-book-bookmark"></i> University Undergraduate & Postgraduate Curriculum</span>
                        <span><i class="fa-solid fa-laptop-code"></i> Python 3 / NumPy / SciPy</span>
                    </div>
                </header>

                <!-- Section 1: Physical Principles -->
                <section style="margin-bottom: 2.25rem;">
                    <h3 style="font-size: 1.3rem; color: var(--accent); display: flex; align-items: center; gap: 0.55rem; margin-bottom: 0.85rem;">
                        <i class="fa-solid fa-atom"></i> 1. Physical Principles & Conceptual Framework
                    </h3>
                    <div class="pedagogy-text" style="line-height: 1.85; color: var(--text-color); font-size: 1.02rem;">
                        <?php echo $pedagogy['overview']; ?>
                    </div>
                </section>

                <!-- Section 2: Mathematical Formulation -->
                <section style="margin-bottom: 2.25rem; background: rgba(56, 189, 248, 0.035); padding: 1.75rem; border-radius: var(--radius-sm); border: 1px solid var(--card-border);">
                    <h3 style="font-size: 1.3rem; color: var(--accent); display: flex; align-items: center; gap: 0.55rem; margin-bottom: 0.85rem;">
                        <i class="fa-solid fa-square-root-variable"></i> 2. Rigorous Mathematical Formulation & Boundary Constraints
                    </h3>
                    <div class="pedagogy-text" style="line-height: 1.85; color: var(--text-color); font-size: 1.02rem;">
                        <?php echo $pedagogy['formulation']; ?>
                    </div>
                </section>

                <!-- Section 3: Computational Mechanics & Error Analysis -->
                <section style="margin-bottom: 2.25rem;">
                    <h3 style="font-size: 1.3rem; color: var(--accent); display: flex; align-items: center; gap: 0.55rem; margin-bottom: 0.85rem;">
                        <i class="fa-solid fa-microchip"></i> 3. Numerical Algorithm Mechanics & Error Convergence
                    </h3>
                    <div class="pedagogy-text" style="line-height: 1.85; color: var(--text-color); font-size: 1.02rem;">
                        <?php echo $pedagogy['algorithm']; ?>
                    </div>
                </section>

                <!-- Section 4: Hands-On Exercises -->
                <section style="margin-bottom: 2.25rem;">
                    <h3 style="font-size: 1.3rem; color: var(--accent); display: flex; align-items: center; gap: 0.55rem; margin-bottom: 0.85rem;">
                        <i class="fa-solid fa-flask-vial"></i> 4. Computational Physics Laboratory Exercises
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-top: 0.75rem;">
                        <?php foreach ($pedagogy['exercises'] as $ex_idx => $exercise): ?>
                            <div style="background: rgba(255, 255, 255, 0.02); border-left: 3px solid var(--primary); padding: 1rem 1.25rem; border-radius: 0 var(--radius-sm) var(--radius-sm) 0; border: 1px solid var(--card-border); border-left-width: 3px;">
                                <strong style="color: var(--primary);">Exercise <?php echo ($ex_idx + 1); ?>:</strong>
                                <span style="color: var(--text-color); margin-left: 0.35rem; line-height: 1.65;"><?php echo htmlspecialchars($exercise); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Section 5: Standard Academic Literature -->
                <section style="border-top: 1px solid var(--card-border); padding-top: 1.5rem; font-size: 0.92rem; color: var(--text-muted);">
                    <strong style="color: var(--text-color); display: block; margin-bottom: 0.5rem;"><i class="fa-solid fa-bookmark"></i> Standard Literature & References:</strong>
                    <ul style="padding-left: 1.25rem; margin: 0; line-height: 1.75;">
                        <?php foreach ($pedagogy['references'] as $ref): ?>
                            <li><?php echo htmlspecialchars($ref); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </article>

            <!-- Schema.org LearningResource & TechArticle Structured Data -->
            <script type="application/ld+json">
            {
              "@context": "https://schema.org",
              "@type": "TechArticle",
              "headline": <?php echo json_encode($pedagogy['title']); ?>,
              "description": <?php echo json_encode(strip_tags(substr($pedagogy['overview'], 0, 250)) . '...'); ?>,
              "author": [
                {
                  "@type": "Person",
                  "name": "Dr. Alorika Chatterjee",
                  "jobTitle": "Assistant Professor of Physics",
                  "affiliation": "The Heritage College, Kolkata"
                },
                {
                  "@type": "Person",
                  "name": "Dr. Anirban Shaw",
                  "jobTitle": "Assistant Professor of Physics",
                  "affiliation": "Dhruba Chand Halder College"
                }
              ],
              "publisher": {
                "@type": "EducationalOrganization",
                "name": "Python4Physics",
                "url": "https://python4physics.in"
              },
              "educationalLevel": "Undergraduate / Postgraduate Physics",
              "learningResourceType": "Numerical Simulation & Computational Physics Laboratory Guide",
              "inLanguage": "en"
            }
            </script>
        </main>
    </div>
</div>

<!-- Member Authentication Gate Modal -->
<div id="authGateModal" class="auth-gate-modal-overlay" style="display: none;" onclick="if(event.target === this) closeAuthGateModal();">
    <div class="auth-gate-modal-card glass-card">
        <button type="button" class="auth-gate-close" onclick="closeAuthGateModal()" title="Close">&times;</button>
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div class="auth-gate-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h3 style="font-size: 1.35rem; margin-bottom: 0.4rem; color: var(--text-color);">Interactive Lab Access</h3>
            <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
                Academic theory and mathematical formulations are freely open. Sign in to execute live Pyodide simulations, generate figures, copy scripts, and export Jupyter Notebooks.
            </p>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.25rem;">
            <a href="<?php echo $siteurl; ?>auth/google.php" class="auth-btn auth-btn-google">
                <svg width="20" height="20" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Continue with Google</span>
            </a>

            <a href="<?php echo $siteurl; ?>auth/github.php" class="auth-btn auth-btn-github">
                <i class="fa-brands fa-github fa-lg"></i>
                <span>Continue with GitHub</span>
            </a>
        </div>

        <?php if (!empty($auth_dev_mode)): ?>
        <div style="background: rgba(14, 165, 233, 0.1); border: 1px dashed rgba(14, 165, 233, 0.4); border-radius: var(--radius-sm); padding: 0.75rem; text-align: center; margin-bottom: 1.25rem;">
            <div style="font-size: 0.76rem; color: var(--accent); font-weight: 700; margin-bottom: 0.45rem; letter-spacing: 0.05em;">
                <i class="fa-solid fa-flask"></i> LOCAL DEV TESTING SIMULATION
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                <a href="<?php echo $siteurl; ?>auth/login.php?demo=google" class="btn-modern btn-secondary btn-sm" style="font-size: 0.78rem;">
                    <i class="fa-brands fa-google"></i> Test Google Login
                </a>
                <a href="<?php echo $siteurl; ?>auth/login.php?demo=github" class="btn-modern btn-secondary btn-sm" style="font-size: 0.78rem;">
                    <i class="fa-brands fa-github"></i> Test GitHub Login
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div style="font-size: 0.8rem; color: var(--text-dim); text-align: center;">
            <i class="fa-solid fa-lock"></i> 100% Free educational resource. No spam. View our <a href="<?php echo $siteurl; ?>privacy.php" style="color: var(--accent);" target="_blank">Privacy Policy</a>.
        </div>
    </div>
</div>

<style>
.auth-gate-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    box-sizing: border-box;
}
.auth-gate-modal-card {
    max-width: 440px;
    width: 100%;
    position: relative;
    padding: 2.25rem 2rem;
    border-radius: var(--radius-lg, 16px);
    background: var(--card-bg, #0f172a);
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.14));
    box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.65);
    animation: authModalFadeIn 0.22s ease-out;
}
@keyframes authModalFadeIn {
    from { opacity: 0; transform: scale(0.94) translateY(12px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.auth-gate-close {
    position: absolute;
    top: 1rem;
    right: 1.25rem;
    background: transparent;
    border: none;
    color: var(--text-dim);
    font-size: 1.6rem;
    line-height: 1;
    cursor: pointer;
    transition: color 0.2s;
}
.auth-gate-close:hover {
    color: var(--text-color);
}
.auth-gate-icon {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: rgba(56, 189, 248, 0.15);
    border: 1px solid rgba(56, 189, 248, 0.35);
    color: var(--accent);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 0.75rem;
}
.auth-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.85rem;
    padding: 0.85rem 1.25rem;
    border-radius: var(--radius-md, 10px);
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    transition: all 0.2s ease;
}
.auth-btn-google {
    background: #ffffff;
    color: #1f2937 !important;
    border: 1px solid #e5e7eb;
}
.auth-btn-google:hover {
    background: #f9fafb;
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.16);
}
.auth-btn-github {
    background: #24292f;
    color: #ffffff !important;
    border: 1px solid #374151;
}
.auth-btn-github:hover {
    background: #1b1f24;
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
}
</style>

<!-- Pyodide Execution Engine Script -->
<script src="<?php echo $siteurl; ?>assets/js/pyodide-runner.js?v=<?php echo file_exists(__DIR__ . '/../../assets/js/pyodide-runner.js') ? filemtime(__DIR__ . '/../../assets/js/pyodide-runner.js') : time(); ?>"></script>

<script>
var p4pEditors = {};
var p4pOriginalCodes = {};
var isUserAuthenticated = <?php echo $is_user_auth ? 'true' : 'false'; ?>;

function promptLoginModal(reason) {
    var modal = document.getElementById('authGateModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeAuthGateModal() {
    var modal = document.getElementById('authGateModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function trackActivity(action, extraData = {}) {
    if (!isUserAuthenticated) return;
    fetch('<?php echo $siteurl; ?>api/track_activity.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: action,
            page_url: window.location.href,
            page_title: document.title,
            menu_id: extraData.menu_id || <?php echo $menu_id; ?>,
            submenu_id: extraData.submenu_id || <?php echo $submenu_id; ?>,
            program_id: extraData.program_id || null,
            details: extraData.details || null
        })
    }).catch(function(err) {
        console.warn('Telemetry error:', err);
    });
}

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
            "Ctrl-Enter": function() { 
                if (isUserAuthenticated) {
                    executeProgram(pid); 
                } else {
                    promptLoginModal('run_code');
                }
            },
            "Cmd-Enter": function() { 
                if (isUserAuthenticated) {
                    executeProgram(pid); 
                } else {
                    promptLoginModal('run_code');
                }
            }
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
    if (!isUserAuthenticated) {
        promptLoginModal('run_code');
        return;
    }

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
    if (runBtn) {
        runBtn.disabled = true;
        runBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Running...';
    }
    if (statusBadge) {
        statusBadge.className = 'badge badge-amber';
        statusBadge.innerText = 'Executing...';
    }

    // Telemetry tracking
    trackActivity('execute_code', { program_id: pid });

    // If code uses plotting, switch to plots tab immediately so student sees live animation
    if (code.includes('plt.') || code.includes('matplotlib')) {
        switchProgramTab(pid, 'plots');
    }

    try {
        await window.physicsRunner.run(code, {
            pid: pid,
            onStatus: function(msg) { if (statusBadge) statusBadge.innerText = msg; },
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
                if (statusBadge) {
                    if (hasSliders) {
                        statusBadge.className = 'badge badge-primary';
                        statusBadge.innerText = 'Interactive';
                    } else if (isAnim) {
                        statusBadge.className = 'badge badge-emerald';
                        statusBadge.innerText = 'Animating';
                    }
                }
            },
            consoleEl: consoleEl,
            plotsEl: plotsEl,
            timeEl: timeEl
        });
        if (statusBadge && statusBadge.innerText !== 'Animating' && statusBadge.innerText !== 'Interactive') {
            statusBadge.className = 'badge badge-emerald';
            statusBadge.innerText = 'Completed';
        }
    } catch (err) {
        if (statusBadge) {
            statusBadge.className = 'badge badge-purple';
            statusBadge.innerText = 'Failed';
        }
        if (stopBtn) stopBtn.style.display = 'none';
    } finally {
        if (runBtn) {
            runBtn.disabled = false;
            runBtn.innerHTML = '<i class="fa-solid fa-play"></i> Run Code (Ctrl+Enter)';
        }
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
        statusBadge.className = 'badge badge-purple';
        statusBadge.innerText = 'Stopped';
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
    if (!isUserAuthenticated) {
        promptLoginModal('copy_code');
        return;
    }
    var pid = textareaId.replace('code_', '');
    var code = p4pEditors[pid] ? p4pEditors[pid].getValue() : document.getElementById(textareaId).value;
    navigator.clipboard.writeText(code).then(() => {
        showToast("Code copied to clipboard!", "success");
        trackActivity('copy_code', { program_id: pid });
    });
}

function resetProgramCode(pid) {
    if (!isUserAuthenticated) {
        promptLoginModal('reset_code');
        return;
    }
    if (p4pEditors[pid] && p4pOriginalCodes[pid]) {
        p4pEditors[pid].setValue(p4pOriginalCodes[pid]);
        showToast("Code reset to default.", "success");
    }
}

function exportProgramPy(pid, title) {
    if (!isUserAuthenticated) {
        promptLoginModal('export_py');
        return;
    }
    var code = p4pEditors[pid] ? p4pEditors[pid].getValue() : '';
    window.physicsRunner.exportPythonScript('physics_' + pid, code);
    showToast("Python script downloaded!", "success");
    trackActivity('export_py', { program_id: pid });
}

function exportProgramJupyter(pid, title) {
    if (!isUserAuthenticated) {
        promptLoginModal('export_ipynb');
        return;
    }
    var code = p4pEditors[pid] ? p4pEditors[pid].getValue() : '';
    window.physicsRunner.exportToJupyter(title, code, "Computational Physics Program #" + pid);
    showToast("Jupyter Notebook downloaded!", "success");
    trackActivity('export_ipynb', { program_id: pid });
}

function toggleFullscreen(wrapperId) {
    if (typeof window.p4pToggleFullscreen === 'function') {
        window.p4pToggleFullscreen(wrapperId);
    } else {
        var el = document.getElementById(wrapperId);
        if (!el) return;
        el.classList.toggle('fullscreen-mode');
    }
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
