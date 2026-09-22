<?php
require_once __DIR__ . '/../../site_config.php';
require_once __DIR__ . '/../../db.php';
include_once __DIR__ . '/menu.php';

$page_title = "GNUplot for Physics - Plotting Catalog";
$page_description = "Explore 49 GNUplot scientific visualization scripts for physics: 2D functions, piecewise functions, data fitting, and polar/parametric plots.";

require_once __DIR__ . '/../../include/header.php';
require_once __DIR__ . '/../../include/navbar.php';
?>

<main class="container" style="padding-top: 3rem; padding-bottom: 5rem;">
    <div style="text-align: center; max-width: 800px; margin: 0 auto 3rem auto;">
        <span class="badge badge-amber"><i class="fa-solid fa-chart-line"></i> Data Visualization</span>
        <h1 style="font-size: 2.6rem; margin-top: 0.75rem; margin-bottom: 0.75rem;">GNUplot for <span class="gradient-text">Scientific Visualization</span></h1>
        <p style="font-size: 1.1rem; line-height: 1.6;">
            A complete collection of 49 GNUplot recipes for physics research: 2D curves, experimental data fitting, polar representations, and parametric graphing.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.75rem;">
        <?php foreach ($menu_titles as $mid => $mtitle): 
            $subtopics = $sub_menu_titles[$mid] ?? [];
        ?>
            <div class="glass-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <span style="width: 44px; height: 44px; border-radius: 10px; background: rgba(245, 158, 11, 0.14); display: flex; align-items: center; justify-content: center; color: var(--warning); font-size: 1.25rem;">
                            <i class="fa-solid fa-chart-line"></i>
                        </span>
                        <span class="badge badge-amber">Section <?php echo $mid; ?></span>
                    </div>
                    <h3 style="font-size: 1.25rem; margin-bottom: 0.75rem;"><?php echo htmlspecialchars($mtitle); ?></h3>
                    
                    <ul style="list-style: none; padding: 0; margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.88rem;">
                        <?php foreach ($subtopics as $sid => $stitle): ?>
                            <li>
                                <a href="program.php?menu_id=<?php echo $mid; ?>&submenu_id=<?php echo $sid; ?>" style="color: var(--text-muted); text-decoration: none;">
                                    <i class="fa-solid fa-angle-right" style="color: var(--warning); margin-right: 5px;"></i> <?php echo htmlspecialchars($stitle); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <a href="program.php?menu_id=<?php echo $mid; ?>&submenu_id=1" class="btn-modern btn-primary btn-sm" style="width: 100%;">
                        Open Section <?php echo $mid; ?> &rarr;
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../include/footer.php'; ?>
