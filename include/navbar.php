<?php
/**
 * Python4Physics - Modern Navigation Bar
 */
if (!isset($siteurl)) {
    require_once __DIR__ . '/../site_config.php';
}
require_once __DIR__ . '/user_auth.php';

$current_script = basename($_SERVER['PHP_SELF'] ?? '');
$current_uri = $_SERVER['REQUEST_URI'] ?? '';
$logged_user = get_logged_in_user();
?>
<header class="site-navbar">
    <div class="container">
        <div class="navbar-inner">
            <!-- Brand Logo -->
            <a href="<?php echo $siteurl; ?>" class="brand-link">
                <i class="fa-brands fa-python" style="color: var(--accent); font-size: 1.6rem;"></i>
                <span>Python<span style="color: var(--accent);">4</span>Physics</span>
                <span class="brand-badge" style="font-family: 'JetBrains Mono', monospace; font-size: 0.68rem; letter-spacing: 0.05em;">LAB</span>
            </a>

            <!-- Desktop Nav Links -->
            <ul class="nav-links">
                <li><a href="<?php echo $siteurl; ?>" class="nav-link-item <?php echo ($current_script == 'index.php' && strpos($current_uri, 'program') === false) ? 'active' : ''; ?>"><i class="fa-solid fa-house"></i> Home</a></li>
                <li><a href="<?php echo $siteurl; ?>about.php" class="nav-link-item <?php echo ($current_script == 'about.php') ? 'active' : ''; ?>"><i class="fa-solid fa-circle-info"></i> About</a></li>
                <li><a href="<?php echo $siteurl; ?>program/python/index.php" class="nav-link-item <?php echo (strpos($current_uri, 'python') !== false) ? 'active' : ''; ?>"><i class="fa-brands fa-python"></i> Python</a></li>
                <li><a href="<?php echo $siteurl; ?>program/gnuplot/index.php" class="nav-link-item <?php echo (strpos($current_uri, 'gnuplot') !== false) ? 'active' : ''; ?>"><i class="fa-solid fa-chart-line"></i> GNUplot</a></li>
                <li><a href="<?php echo $siteurl; ?>program/latex/index.php" class="nav-link-item <?php echo (strpos($current_uri, 'latex') !== false) ? 'active' : ''; ?>"><i class="fa-solid fa-file-code"></i> LaTeX</a></li>
                <li><a href="<?php echo $siteurl; ?>assignments.php" class="nav-link-item <?php echo ($current_script == 'assignments.php') ? 'active' : ''; ?>"><i class="fa-solid fa-list-check"></i> Assignments</a></li>
                <li><a href="<?php echo $siteurl; ?>visualization.php" class="nav-link-item <?php echo ($current_script == 'visualization.php' || strpos($current_uri, 'visualization') !== false) ? 'active' : ''; ?>"><i class="fa-solid fa-sliders"></i> Visualization</a></li>
                <li><a href="<?php echo $siteurl; ?>arduino.php" class="nav-link-item <?php echo ($current_script == 'arduino.php') ? 'active' : ''; ?>"><i class="fa-solid fa-microchip"></i> Arduino Lab</a></li>
                <li><a href="<?php echo $siteurl; ?>circuit_simulator.php" class="nav-link-item <?php echo ($current_script == 'circuit_simulator.php') ? 'active' : ''; ?>"><i class="fa-solid fa-wave-square"></i> Circuit Simulator</a></li>
                <li><a href="<?php echo $siteurl; ?>contact.php" class="nav-link-item <?php echo ($current_script == 'contact.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-graduate"></i> Contact</a></li>
            </ul>

            <!-- Nav Actions: Search, Theme Toggle & User Auth -->
            <div class="nav-actions">
                <a href="https://v1.python4physics.in/" target="_blank" rel="noopener" class="btn-classic-archive" title="Visit Python4Physics Classic (v1.0) Archive">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span class="classic-text">Classic v1</span>
                    <i class="fa-solid fa-arrow-up-right-from-square classic-external-icon"></i>
                </a>

                <button type="button" class="nav-search-btn trigger-global-search" aria-label="Search Programs">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Search Physics Index</span>
                    <kbd>Ctrl+K</kbd>
                </button>

                <button type="button" class="theme-toggle-btn" id="themeToggleBtn" aria-label="Toggle Dark and Light Mode" title="Toggle Theme (Dark / Light Mode)">
                    <i class="fa-solid fa-moon"></i>
                    <span class="theme-toggle-label">Dark</span>
                </button>

                <!-- User Account / Sign In -->
                <?php if ($logged_user): ?>
                <div class="user-profile-menu" style="position: relative;">
                    <button type="button" class="btn-modern btn-secondary btn-sm" id="userMenuBtn" onclick="toggleUserDropdown()" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0.65rem;">
                        <?php if (!empty($logged_user['avatar_url'])): ?>
                            <img src="<?php echo htmlspecialchars($logged_user['avatar_url']); ?>" alt="Avatar" style="width: 22px; height: 22px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fa-solid fa-circle-user" style="color: var(--accent);"></i>
                        <?php endif; ?>
                        <span style="max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.85rem; font-weight: 600;">
                            <?php echo htmlspecialchars(explode(' ', $logged_user['name'])[0]); ?>
                        </span>
                        <i class="fa-solid fa-chevron-down" style="font-size: 0.65rem; opacity: 0.7;"></i>
                    </button>
                    <div id="userDropdownMenu" style="display: none; position: absolute; right: 0; top: calc(100% + 8px); background: var(--bg-secondary); border: 1px solid var(--card-border); border-radius: var(--radius-sm); box-shadow: 0 10px 25px rgba(0,0,0,0.4); min-width: 220px; z-index: 1000; padding: 0.75rem;">
                        <div style="padding-bottom: 0.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--card-border);">
                            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color);"><?php echo htmlspecialchars($logged_user['name']); ?></div>
                            <div style="font-size: 0.78rem; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($logged_user['email']); ?></div>
                            <div style="margin-top: 0.35rem;">
                                <span class="badge badge-cyan" style="font-size: 0.7rem; text-transform: capitalize;">
                                    <i class="fa-brands fa-<?php echo $logged_user['provider'] === 'google' ? 'google' : 'github'; ?>"></i> <?php echo htmlspecialchars($logged_user['provider']); ?>
                                </span>
                            </div>
                        </div>
                        <a href="<?php echo $siteurl; ?>auth/logout.php?return_url=<?php echo urlencode($current_uri); ?>" style="display: flex; align-items: center; gap: 0.5rem; color: #ef4444; font-size: 0.85rem; text-decoration: none; padding: 0.4rem 0.25rem;">
                            <i class="fa-solid fa-right-from-bracket"></i> Sign Out
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <a href="<?php echo $siteurl; ?>auth/login.php?return_url=<?php echo urlencode($current_uri); ?>" class="btn-modern btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 0.45rem;" title="Sign in with Google or GitHub">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> <span>Sign In</span>
                </a>
                <?php endif; ?>

                <button type="button" class="mobile-nav-toggle" id="mobileMenuBtn" aria-label="Toggle Navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer Navigation -->
    <div id="mobileDrawer" style="display:none; background: var(--bg-secondary); border-bottom: 1px solid var(--card-border); padding: 1rem 1.5rem;">
        <!-- Mobile User Row -->
        <div style="padding-bottom: 0.85rem; margin-bottom: 0.85rem; border-bottom: 1px solid var(--card-border);">
            <?php if ($logged_user): ?>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.95rem;"><?php echo htmlspecialchars($logged_user['name']); ?></div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($logged_user['email']); ?></div>
                </div>
                <a href="<?php echo $siteurl; ?>auth/logout.php?return_url=<?php echo urlencode($current_uri); ?>" class="btn-modern btn-danger btn-sm" style="font-size: 0.78rem;">
                    Sign Out
                </a>
            </div>
            <?php else: ?>
            <a href="<?php echo $siteurl; ?>auth/login.php?return_url=<?php echo urlencode($current_uri); ?>" class="btn-modern btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%;">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In with Google / GitHub
            </a>
            <?php endif; ?>
        </div>

        <!-- Mobile Drawer Color Theme Switcher Option -->
        <div class="mobile-theme-row">
            <span class="mobile-theme-title">
                <i class="fa-solid fa-circle-half-stroke" style="color: var(--accent);"></i> Color Theme
            </span>
            <button type="button" class="theme-toggle-btn mobile-theme-toggle-btn" aria-label="Toggle Dark and Light Mode" title="Toggle Theme (Dark / Light Mode)">
                <i class="fa-solid fa-moon"></i>
                <span class="theme-toggle-label theme-mode-text">Dark Mode</span>
            </button>
        </div>

        <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.5rem; margin: 0; padding: 0;">
            <li><a href="<?php echo $siteurl; ?>" class="nav-link-item"><i class="fa-solid fa-house fa-fw"></i> Home</a></li>
            <li><a href="<?php echo $siteurl; ?>about.php" class="nav-link-item"><i class="fa-solid fa-circle-info fa-fw"></i> About Us</a></li>
            <li><a href="<?php echo $siteurl; ?>program/python/index.php" class="nav-link-item"><i class="fa-brands fa-python fa-fw"></i> Python (345 Codes)</a></li>
            <li><a href="<?php echo $siteurl; ?>program/gnuplot/index.php" class="nav-link-item"><i class="fa-solid fa-chart-line fa-fw"></i> GNUplot Visualizer</a></li>
            <li><a href="<?php echo $siteurl; ?>program/latex/index.php" class="nav-link-item"><i class="fa-solid fa-file-code fa-fw"></i> LaTeX Formulations</a></li>
            <li><a href="<?php echo $siteurl; ?>assignments.php" class="nav-link-item"><i class="fa-solid fa-list-check fa-fw"></i> Interactive Assignments</a></li>
            <li><a href="<?php echo $siteurl; ?>visualization.php" class="nav-link-item"><i class="fa-solid fa-sliders fa-fw"></i> Interactive Visualization</a></li>
            <li><a href="<?php echo $siteurl; ?>arduino.php" class="nav-link-item"><i class="fa-solid fa-microchip fa-fw"></i> Arduino Physics Lab</a></li>
            <li><a href="<?php echo $siteurl; ?>circuit_simulator.php" class="nav-link-item"><i class="fa-solid fa-wave-square fa-fw"></i> Circuit Simulator</a></li>
            <li><a href="<?php echo $siteurl; ?>contact.php" class="nav-link-item"><i class="fa-solid fa-user-graduate fa-fw"></i> Faculty & Contact</a></li>
            <li>
                <a href="https://v1.python4physics.in/" target="_blank" rel="noopener" class="nav-link-item" style="border: 1px dashed rgba(245, 158, 11, 0.4); background: rgba(245, 158, 11, 0.08); margin-top: 0.25rem;">
                    <i class="fa-solid fa-clock-rotate-left fa-fw" style="color: #f59e0b;"></i>
                    <span>Classic Portal (v1.0 Archive)</span>
                    <i class="fa-solid fa-arrow-up-right-from-square" style="margin-left: auto; font-size: 0.75rem; color: #f59e0b;"></i>
                </a>
            </li>
        </ul>
    </div>
</header>

<script>
function toggleUserDropdown() {
    var menu = document.getElementById('userDropdownMenu');
    if (menu) {
        menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
    }
}
document.addEventListener('click', function(e) {
    var btn = document.getElementById('userMenuBtn');
    var menu = document.getElementById('userDropdownMenu');
    if (menu && btn && !btn.contains(e.target) && !menu.contains(e.target)) {
        menu.style.display = 'none';
    }
});
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
