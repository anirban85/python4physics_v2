<?php
/**
 * Python4Physics - Global Standardized Header
 */
if (!isset($siteurl)) {
    require_once __DIR__ . '/../site_config.php';
}

$page_title_text = isset($page_title) ? $page_title . " | Python4Physics" : "Python4Physics - Computational Physics with Python, GNUplot, LaTeX, Arduino";
$page_desc_text = isset($page_description) ? $page_description : "Explore 500+ interactive computational physics algorithms, numerical simulations, GNUplot scientific graphs, and LaTeX document templates by Dr. Alorika Chatterjee and Dr. Anirban Shaw.";
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title_text); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_desc_text); ?>">
    <meta name="keywords" content="computational physics, python for physics, numerical methods, ode, pde, runge kutta, quantum physics simulation, tise, tdse, gnuplot, latex, arduino physics lab">
    <meta name="author" content="Dr. Alorika Chatterjee & Dr. Anirban Shaw">
    
    <!-- OpenGraph / Social Sharing -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title_text); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_desc_text); ?>">
    <meta property="og:image" content="<?php echo $siteurl; ?>assets/images/logo.png">
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo $siteurl; ?>program/python/python.ico" type="image/x-icon">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- KaTeX for Superfast Mathematical Equations -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>

    <!-- CodeMirror 5 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/material-darker.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/stex/stex.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/shell/shell.min.js"></script>

    <!-- Global Application Styles -->
    <link rel="stylesheet" href="<?php echo $siteurl; ?>assets/css/main.css?v=<?php echo file_exists(__DIR__ . '/../assets/css/main.css') ? filemtime(__DIR__ . '/../assets/css/main.css') : '1.0'; ?>">
    <link rel="stylesheet" href="<?php echo $siteurl; ?>assets/css/editor.css?v=<?php echo file_exists(__DIR__ . '/../assets/css/editor.css') ? filemtime(__DIR__ . '/../assets/css/editor.css') : '1.0'; ?>">

    <!-- Global Site Config JS Variable -->
    <script>
        window.p4p_siteurl = "<?php echo $siteurl; ?>";
    </script>
    <!-- Early Theme Applier (prevents theme flicker) -->
    <script src="<?php echo $siteurl; ?>assets/js/theme.js"></script>
</head>
<body>
