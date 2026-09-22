<?php
require_once __DIR__ . '/../../site_config.php';
require_once __DIR__ . '/../../db.php';
include_once __DIR__ . '/menu.php';

$page_title = "LaTeX for Physics - Documentation Catalog";
$page_description = "Explore 115 LaTeX scientific documentation templates for physics: article classes, equations, calculus, matrices, tables, TikZ diagrams, and bibliography.";

require_once __DIR__ . '/../../include/header.php';
require_once __DIR__ . '/../../include/navbar.php';
?>

<main class="container" style="padding-top: 3rem; padding-bottom: 5rem;">
    <div style="text-align: center; max-width: 800px; margin: 0 auto 3rem auto;">
        <span class="badge badge-blue"><i class="fa-solid fa-file-code"></i> Scientific Writing</span>
        <h1 style="font-size: 2.6rem; margin-top: 0.75rem; margin-bottom: 0.75rem;">LaTeX for <span class="gradient-text">Physics & Research</span></h1>
        <p style="font-size: 1.1rem; line-height: 1.6;">
            A complete suite of 115 LaTeX templates and recipes for mathematical physics manuscripts: equations, complex matrices, scientific tables, TikZ graphics, and bibliographies.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.75rem;">
        <?php foreach ($menu_titles as $mid => $mtitle): 
            $subtopics = $sub_menu_titles[$mid] ?? [];
        ?>
            <div class="glass-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <span style="width: 44px; height: 44px; border-radius: 10px; background: rgba(59, 130, 246, 0.14); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.25rem;">
                            <i class="fa-solid fa-file-code"></i>
                        </span>
                        <span class="badge badge-blue">Chapter <?php echo $mid; ?></span>
                    </div>
                    <h3 style="font-size: 1.25rem; margin-bottom: 0.75rem;"><?php echo htmlspecialchars($mtitle); ?></h3>
                    
                    <ul style="list-style: none; padding: 0; margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.88rem;">
                        <?php foreach ($subtopics as $sid => $stitle): ?>
                            <li>
                                <a href="program.php?menu_id=<?php echo $mid; ?>&submenu_id=<?php echo $sid; ?>" style="color: var(--text-muted); text-decoration: none;">
                                    <i class="fa-solid fa-angle-right" style="color: var(--primary); margin-right: 5px;"></i> <?php echo htmlspecialchars($stitle); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <a href="program.php?menu_id=<?php echo $mid; ?>&submenu_id=1" class="btn-modern btn-primary btn-sm" style="width: 100%;">
                        Open Chapter <?php echo $mid; ?> &rarr;
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../include/footer.php'; ?>
