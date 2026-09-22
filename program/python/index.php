<?php
require_once __DIR__ . '/../../site_config.php';
require_once __DIR__ . '/../../db.php';
include_once __DIR__ . '/menu.php';

$page_title = "Python for Physics - Chapters Catalog";
$page_description = "Browse all 20 chapters and 345 interactive computational physics programs in Python, covering Kinematics, ODEs, PDEs, Quantum Mechanics, and Numerical Integration.";

require_once __DIR__ . '/../../include/header.php';
require_once __DIR__ . '/../../include/navbar.php';
?>

<main class="container" style="padding-top: 3rem; padding-bottom: 5rem;">
    <!-- Page Header -->
    <div style="text-align: center; max-width: 800px; margin: 0 auto 3rem auto;">
        <span class="badge badge-cyan"><i class="fa-brands fa-python"></i> Computational Physics</span>
        <h1 style="font-size: 2.6rem; margin-top: 0.75rem; margin-bottom: 0.75rem;">Python Physics <span class="gradient-text">Curriculum & Chapters</span></h1>
        <p style="font-size: 1.1rem; line-height: 1.6;">
            Explore over <strong>345 interactive Python scripts</strong> across 20 university physics modules. Run simulations directly in your browser with NumPy and Matplotlib.
        </p>

        <div style="max-width: 500px; margin: 1.5rem auto 0 auto;">
            <button type="button" class="btn-modern btn-secondary trigger-global-search" style="width: 100%; justify-content: space-between; padding: 0.8rem 1.25rem;">
                <span><i class="fa-solid fa-magnifying-glass" style="color: var(--accent); margin-right: 8px;"></i> Search within 345 Python algorithms...</span>
                <kbd>Ctrl+K</kbd>
            </button>
        </div>
    </div>

    <!-- Chapters Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1.75rem;">
        <?php
        if (isset($menu_titles) && is_array($menu_titles)) {
            $icons = [
                1 => 'fa-terminal', 2 => 'fa-layer-group', 3 => 'fa-calculator', 4 => 'fa-table-cells',
                5 => 'fa-chart-pie', 6 => 'fa-chart-line', 7 => 'fa-cubes', 8 => 'fa-equals',
                9 => 'fa-microscope', 10 => 'fa-bezier-curve', 11 => 'fa-chart-area', 12 => 'fa-route',
                13 => 'fa-chart-column', 14 => 'fa-infinity', 15 => 'fa-wave-square', 16 => 'fa-water',
                17 => 'fa-crosshairs', 18 => 'fa-atom', 19 => 'fa-circle-nodes', 20 => 'fa-temperature-half'
            ];

            foreach ($menu_titles as $mid => $mtitle) {
                $icon = $icons[$mid] ?? 'fa-folder';
                $subtopics = $sub_menu_titles[$mid] ?? [];
                $subCount = count($subtopics);
                
                echo "
                <div class=\"glass-card\" style=\"display: flex; flex-direction: column; justify-content: space-between;\">
                    <div>
                        <div style=\"display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;\">
                            <span style=\"width: 44px; height: 44px; border-radius: 10px; background: rgba(6, 182, 212, 0.14); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.25rem;\">
                                <i class=\"fa-solid {$icon}\"></i>
                            </span>
                            <span class=\"badge badge-cyan\">Chapter {$mid}</span>
                        </div>
                        <h3 style=\"font-size: 1.25rem; margin-bottom: 0.75rem;\">" . htmlspecialchars($mtitle) . "</h3>
                        <p style=\"font-size: 0.88rem; color: var(--text-dim); margin-bottom: 1rem;\">{$subCount} interactive subtopics with working code and theory.</p>
                        
                        <div style=\"background: var(--bg-secondary); border-radius: var(--radius-md); padding: 0.75rem; margin-bottom: 1.25rem; max-height: 130px; overflow-y: auto;\">
                            <ul style=\"list-style: none; padding: 0; margin: 0; font-size: 0.82rem; display: flex; flex-direction: column; gap: 0.35rem;\">";
                
                $itemIndex = 0;
                foreach ($subtopics as $sid => $stitle) {
                    $itemIndex++;
                    echo "<li><a href=\"program.php?menu_id={$mid}&submenu_id={$sid}\" style=\"color: var(--text-muted); text-decoration: none;\">&bull; " . htmlspecialchars($stitle) . "</a></li>";
                }

                echo "      </ul>
                        </div>
                    </div>
                    <div>
                        <a href=\"program.php?menu_id={$mid}&submenu_id=1\" class=\"btn-modern btn-primary btn-sm\" style=\"width: 100%;\">
                            Open Chapter {$mid} &rarr;
                        </a>
                    </div>
                </div>";
            }
        }
        ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../include/footer.php'; ?>
