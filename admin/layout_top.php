<?php
/**
 * Python4Physics - Admin Layout Header & Navigation
 */
require_once __DIR__ . '/auth.php';
require_admin_login();

$admin_user = get_logged_in_admin();
$current_page = basename($_SERVER['PHP_SELF']);
$base_url = get_base_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($page_title ?? 'Admin Dashboard'); ?> &bull; Python4Physics Admin</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/admin/css/admin.css">

    <!-- CodeMirror for code editing if on programs or assignments page -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/material-ocean.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/stex/stex.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/shell/shell.min.js"></script>
</head>
<body>

<nav class="admin-navbar">
    <div class="admin-nav-container">
        <a href="<?php echo $base_url; ?>/admin/index.php" class="admin-brand">
            <i class="fa-solid fa-atom"></i>
            <span>Python4Physics <span class="admin-badge admin-badge-cyan" style="font-size: 0.7rem; margin-left: 4px;">CMS</span></span>
        </a>

        <div class="admin-nav-links">
            <a href="<?php echo $base_url; ?>/admin/index.php" class="admin-nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge"></i> Dashboard
            </a>
            <a href="<?php echo $base_url; ?>/admin/menus.php" class="admin-nav-link <?php echo $current_page === 'menus.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-sitemap"></i> Menus & Submenus
            </a>
            <a href="<?php echo $base_url; ?>/admin/programs.php" class="admin-nav-link <?php echo $current_page === 'programs.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-code"></i> Programs & Upload
            </a>
            <a href="<?php echo $base_url; ?>/admin/assignments.php" class="admin-nav-link <?php echo $current_page === 'assignments.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-graduation-cap"></i> Assignments
            </a>
            <a href="<?php echo $base_url; ?>/admin/feedback.php" class="admin-nav-link <?php echo $current_page === 'feedback.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-comments"></i> Feedback
            </a>
        </div>

        <div class="admin-nav-actions">
            <a href="<?php echo $base_url; ?>/index.php" target="_blank" class="btn-admin btn-admin-secondary btn-admin-sm" title="View live website in new tab">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Live Site
            </a>
            
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-left: 0.5rem;">
                <span style="font-size: 0.85rem; font-weight: 600; color: #cbd5e1;">
                    <i class="fa-solid fa-circle-user" style="color: var(--admin-accent);"></i> <?php echo htmlspecialchars($admin_user['username']); ?>
                </span>
                <a href="<?php echo $base_url; ?>/admin/logout.php" class="btn-admin btn-admin-danger btn-admin-sm" title="Sign out">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<main class="admin-main">
