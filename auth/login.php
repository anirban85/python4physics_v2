<?php
/**
 * Python4Physics - Student & Researcher Sign-In Portal
 * Supports Google OAuth, GitHub OAuth, and Secure Session Management
 */
require_once __DIR__ . '/../site_config.php';
require_once __DIR__ . '/../include/user_auth.php';

$return_url = $_GET['return_url'] ?? ($siteurl . 'program/python/index.php');
// Sanitize return_url to ensure it belongs to the same domain
if (!str_starts_with($return_url, $siteurl) && !str_starts_with($return_url, '/')) {
    $return_url = $siteurl . 'program/python/index.php';
}

if (is_user_logged_in()) {
    header("Location: " . $return_url);
    exit;
}

$page_title = "Sign In with Google or GitHub | Python4Physics";
$page_description = "Authenticate to access computational physics source codes, in-browser Pyodide execution, and interactive laboratory exercises.";

require_once __DIR__ . '/../include/header.php';
require_once __DIR__ . '/../include/navbar.php';
?>

<div class="container" style="max-width: 520px; padding-top: 3.5rem; padding-bottom: 5rem;">
    <div class="glass-card" style="padding: 2.75rem 2.25rem; border-radius: var(--radius-lg); text-align: center; border-top: 4px solid var(--accent); box-shadow: 0 20px 45px rgba(0,0,0,0.4);">
        <!-- Academic Brand Emblem -->
        <div style="width: 68px; height: 68px; margin: 0 auto 1.5rem auto; border-radius: 50%; background: rgba(56, 189, 248, 0.12); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(56, 189, 248, 0.35);">
            <i class="fa-brands fa-python" style="font-size: 2.25rem; color: var(--accent);"></i>
        </div>

        <h1 style="font-size: 1.85rem; margin-bottom: 0.5rem; font-weight: 800; letter-spacing: -0.02em;">
            Welcome to <span style="color: var(--accent);">Python4Physics</span>
        </h1>
        <p style="font-size: 0.95rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 2rem;">
            Sign in to unlock interactive in-browser simulation, view full Python source codes, execute Pyodide scripts, and export Jupyter Notebooks.
        </p>

        <!-- Social OAuth Sign-In Buttons -->
        <div style="display: flex; flex-direction: column; gap: 0.9rem; margin-bottom: 1.75rem;">
            <!-- Google Login Button -->
            <a href="<?php echo $siteurl; ?>auth/google.php?return_url=<?php echo urlencode($return_url); ?>" 
               class="btn-modern" 
               style="background: #ffffff; color: #1f2937; border: 1px solid #e5e7eb; padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.75rem; text-decoration: none; font-size: 0.98rem; box-shadow: 0 2px 6px rgba(0,0,0,0.1); transition: all 0.2s ease;">
                <svg width="20" height="20" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Continue with Google</span>
            </a>

            <!-- GitHub Login Button -->
            <a href="<?php echo $siteurl; ?>auth/github.php?return_url=<?php echo urlencode($return_url); ?>" 
               class="btn-modern" 
               style="background: #24292e; color: #ffffff; border: 1px solid #3f4750; padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.75rem; text-decoration: none; font-size: 0.98rem; box-shadow: 0 2px 6px rgba(0,0,0,0.25); transition: all 0.2s ease;">
                <i class="fa-brands fa-github" style="font-size: 1.25rem;"></i>
                <span>Continue with GitHub</span>
            </a>
        </div>

        <?php if (!empty($auth_dev_mode)): ?>
        <!-- Local Testing Mode Banner -->
        <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25); padding: 1rem; border-radius: var(--radius-sm); margin-top: 1.5rem; text-align: left; font-size: 0.85rem;">
            <div style="color: #f59e0b; font-weight: 700; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.4rem;">
                <i class="fa-solid fa-flask"></i> Testing / Local Simulation Active
            </div>
            <p style="color: var(--text-color); margin-bottom: 0.75rem; line-height: 1.5;">
                OAuth credentials not configured yet. You can click the one-click test buttons below to simulate instant student authentication on localhost:
            </p>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a href="<?php echo $siteurl; ?>auth/google.php?demo=1&return_url=<?php echo urlencode($return_url); ?>" class="btn-modern btn-secondary btn-sm">
                    <i class="fa-brands fa-google"></i> Test Google Login
                </a>
                <a href="<?php echo $siteurl; ?>auth/github.php?demo=1&return_url=<?php echo urlencode($return_url); ?>" class="btn-modern btn-secondary btn-sm">
                    <i class="fa-brands fa-github"></i> Test GitHub Login
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Privacy & Terms Notice -->
        <p style="font-size: 0.8rem; color: var(--text-dim); margin-top: 1.75rem; line-height: 1.6;">
            By continuing, you agree to our <a href="<?php echo $siteurl; ?>terms.php" style="color: var(--accent);">Terms of Service</a> and <a href="<?php echo $siteurl; ?>privacy.php" style="color: var(--accent);">Privacy Policy</a>. We only request your public name, verified email, and profile avatar.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../include/footer.php'; ?>
