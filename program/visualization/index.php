<?php
require_once __DIR__ . '/../../site_config.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../include/menu_sync.php';

if (isset($conn) && $conn !== null) {
    if (!file_exists(__DIR__ . '/menu.php')) {
        ensure_visualization_installed($conn);
    }
}
include_once __DIR__ . '/menu.php';

$page_title = "Physics Visualization - Interactive Computational Simulations with Sliders";
$page_description = "Explore interactive computational physics simulations with live parameter manipulation, numerical shooting methods, and real-time Matplotlib sliders.";

require_once __DIR__ . '/../../include/header.php';
require_once __DIR__ . '/../../include/navbar.php';
?>

<main class="container" style="padding-top: 3rem; padding-bottom: 5rem;">
    <!-- Page Header -->
    <div style="text-align: center; max-width: 800px; margin: 0 auto 3rem auto;">
        <span class="badge badge-emerald"><i class="fa-solid fa-sliders"></i> Interactive Parameter Manipulation</span>
        <h1 style="font-size: 2.6rem; margin-top: 0.75rem; margin-bottom: 0.75rem;">Interactive <span class="gradient-text">Physics Visualization</span></h1>
        <p style="font-size: 1.1rem; line-height: 1.6;">
            Explore dynamical physical models, quantum wave equations, and boundary value problems with <strong>real-time Matplotlib sliders</strong> running in your browser via WebAssembly.
        </p>

        <div style="max-width: 500px; margin: 1.5rem auto 0 auto;">
            <button type="button" class="btn-modern btn-secondary trigger-global-search" style="width: 100%; justify-content: space-between; padding: 0.8rem 1.25rem;">
                <span><i class="fa-solid fa-magnifying-glass" style="color: var(--accent); margin-right: 8px;"></i> Search within simulations...</span>
                <kbd>Ctrl+K</kbd>
            </button>
        </div>
    </div>

    <!-- Modules Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1.75rem;">
        <?php
        if (isset($menu_titles) && is_array($menu_titles)) {
            $icons = [
                1 => 'fa-arrows-split-up-and-left', 
                2 => 'fa-shapes', 
                3 => 'fa-compass-drafting', 
                4 => 'fa-atom', 
                5 => 'fa-lightbulb', 
                6 => 'fa-chart-line', 
                7 => 'fa-magnet'
            ];

            foreach ($menu_titles as $mid => $mtitle) {
                $icon = $icons[$mid] ?? 'fa-sliders';
                $subtopics = $sub_menu_titles[$mid] ?? [];
                $subCount = count($subtopics);
                
                echo "
                <div class=\"glass-card\" style=\"display: flex; flex-direction: column; justify-content: space-between;\">
                    <div>
                        <div style=\"display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;\">
                            <span style=\"width: 44px; height: 44px; border-radius: 10px; background: rgba(16, 185, 129, 0.12); display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 1.25rem;\">
                                <i class=\"fa-solid {$icon}\"></i>
                            </span>
                            <span class=\"badge badge-emerald\">Module {$mid}</span>
                        </div>
                        <h3 style=\"font-size: 1.25rem; margin-bottom: 0.75rem;\">" . htmlspecialchars($mtitle) . "</h3>
                        <p style=\"font-size: 0.88rem; color: var(--text-dim); margin-bottom: 1rem;\">{$subCount} interactive simulations with theory and live sliders.</p>
                        
                        <div style=\"background: var(--bg-secondary); border-radius: var(--radius-md); padding: 0.75rem; margin-bottom: 1.25rem; max-height: 140px; overflow-y: auto;\">
                            <ul style=\"list-style: none; padding: 0; margin: 0; font-size: 0.82rem; display: flex; flex-direction: column; gap: 0.35rem;\">";
                
                foreach ($subtopics as $sid => $stitle) {
                    echo "<li><a href=\"program.php?menu_id={$mid}&submenu_id={$sid}\" style=\"color: var(--text-muted); text-decoration: none;\">&bull; " . htmlspecialchars($stitle) . "</a></li>";
                }

                echo "      </ul>
                        </div>
                    </div>
                    <div>
                        <a href=\"program.php?menu_id={$mid}&submenu_id=1\" class=\"btn-modern btn-primary btn-sm\" style=\"width: 100%;\">
                            Launch Module {$mid} &rarr;
                        </a>
                    </div>
                </div>";
            }
        }
        ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../include/footer.php'; ?>
