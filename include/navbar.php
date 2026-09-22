<?php
/**
 * Python4Physics - Modern Navigation Bar
 */
if (!isset($siteurl)) {
    require_once __DIR__ . '/../site_config.php';
}

$current_script = basename($_SERVER['PHP_SELF'] ?? '');
$current_uri = $_SERVER['REQUEST_URI'] ?? '';
?>
<header class="site-navbar">
    <div class="container">
        <div class="navbar-inner">
            <!-- Brand Logo -->
            <a href="<?php echo $siteurl; ?>" class="brand-link">
                <i class="fa-brands fa-python" style="color: var(--accent); font-size: 1.6rem;"></i>
                <span>Python<span style="color: var(--accent);">4</span>Physics</span>
                <span class="brand-badge">PRO</span>
            </a>

            <!-- Desktop Nav Links -->
            <ul class="nav-links">
                <li><a href="<?php echo $siteurl; ?>" class="nav-link-item <?php echo ($current_script == 'index.php' && strpos($current_uri, 'program') === false) ? 'active' : ''; ?>"><i class="fa-solid fa-house"></i> Home</a></li>
                <li><a href="<?php echo $siteurl; ?>program/python/index.php" class="nav-link-item <?php echo (strpos($current_uri, 'python') !== false) ? 'active' : ''; ?>"><i class="fa-brands fa-python"></i> Python</a></li>
                <li><a href="<?php echo $siteurl; ?>program/gnuplot/index.php" class="nav-link-item <?php echo (strpos($current_uri, 'gnuplot') !== false) ? 'active' : ''; ?>"><i class="fa-solid fa-chart-line"></i> GNUplot</a></li>
                <li><a href="<?php echo $siteurl; ?>program/latex/index.php" class="nav-link-item <?php echo (strpos($current_uri, 'latex') !== false) ? 'active' : ''; ?>"><i class="fa-solid fa-file-code"></i> LaTeX</a></li>
                <li><a href="<?php echo $siteurl; ?>assignments.php" class="nav-link-item <?php echo ($current_script == 'assignments.php') ? 'active' : ''; ?>"><i class="fa-solid fa-list-check"></i> Assignments</a></li>
                <li><a href="<?php echo $siteurl; ?>arduino.php" class="nav-link-item <?php echo ($current_script == 'arduino.php') ? 'active' : ''; ?>"><i class="fa-solid fa-microchip"></i> Arduino Lab</a></li>
                <li><a href="<?php echo $siteurl; ?>api/docs.php" class="nav-link-item <?php echo (strpos($current_uri, 'api') !== false) ? 'active' : ''; ?>"><i class="fa-solid fa-terminal"></i> API</a></li>
                <li><a href="<?php echo $siteurl; ?>feedback.php" class="nav-link-item <?php echo ($current_script == 'feedback.php') ? 'active' : ''; ?>"><i class="fa-regular fa-comment-dots"></i> Feedback</a></li>
                <li><a href="<?php echo $siteurl; ?>contact.php" class="nav-link-item <?php echo ($current_script == 'contact.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-graduate"></i> Contact</a></li>
                <li><a href="<?php echo $siteurl; ?>admin/index.php" class="nav-link-item" title="Admin Control Center"><i class="fa-solid fa-shield-halved"></i> Admin</a></li>
            </ul>

            <!-- Nav Actions: Search & Theme Toggle -->
            <div class="nav-actions">
                <button type="button" class="nav-search-btn trigger-global-search" aria-label="Search Programs">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Search 500+ Codes</span>
                    <kbd>Ctrl+K</kbd>
                </button>

                <button type="button" class="theme-toggle-btn" aria-label="Toggle theme">
                    <i class="fa-solid fa-sun"></i>
                </button>

                <button type="button" class="mobile-nav-toggle" id="mobileMenuBtn" aria-label="Toggle Navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer Navigation -->
    <div id="mobileDrawer" style="display:none; background: var(--bg-secondary); border-bottom: 1px solid var(--card-border); padding: 1rem 1.5rem;">
        <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.5rem; margin: 0; padding: 0;">
            <li><a href="<?php echo $siteurl; ?>" class="nav-link-item"><i class="fa-solid fa-house fa-fw"></i> Home</a></li>
            <li><a href="<?php echo $siteurl; ?>program/python/index.php" class="nav-link-item"><i class="fa-brands fa-python fa-fw"></i> Python (345 Codes)</a></li>
            <li><a href="<?php echo $siteurl; ?>program/gnuplot/index.php" class="nav-link-item"><i class="fa-solid fa-chart-line fa-fw"></i> GNUplot Visualizer</a></li>
            <li><a href="<?php echo $siteurl; ?>program/latex/index.php" class="nav-link-item"><i class="fa-solid fa-file-code fa-fw"></i> LaTeX Formulations</a></li>
            <li><a href="<?php echo $siteurl; ?>assignments.php" class="nav-link-item"><i class="fa-solid fa-list-check fa-fw"></i> Interactive Assignments</a></li>
            <li><a href="<?php echo $siteurl; ?>arduino.php" class="nav-link-item"><i class="fa-solid fa-microchip fa-fw"></i> Arduino Physics Lab</a></li>
            <li><a href="<?php echo $siteurl; ?>api/docs.php" class="nav-link-item"><i class="fa-solid fa-terminal fa-fw"></i> Developer REST API</a></li>
            <li><a href="<?php echo $siteurl; ?>feedback.php" class="nav-link-item"><i class="fa-regular fa-comment-dots fa-fw"></i> Submit Feedback</a></li>
            <li><a href="<?php echo $siteurl; ?>contact.php" class="nav-link-item"><i class="fa-solid fa-user-graduate fa-fw"></i> Faculty & Contact</a></li>
            <li><a href="<?php echo $siteurl; ?>admin/index.php" class="nav-link-item"><i class="fa-solid fa-shield-halved fa-fw"></i> Admin Portal</a></li>
        </ul>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('mobileMenuBtn');
    const drawer = document.getElementById('mobileDrawer');
    if (btn && drawer) {
        btn.addEventListener('click', function() {
            drawer.style.display = drawer.style.display === 'none' ? 'block' : 'none';
        });
    }
});
</script>
