<?php
/**
 * Python4Physics - Global Standardized Header
 */
if (!isset($siteurl)) {
    require_once __DIR__ . '/../site_config.php';
}

$page_title_text = isset($page_title) ? $page_title . " | Python4Physics" : "Python4Physics - Computational Physics with Python, GNUplot, LaTeX, Arduino";
$page_desc_text = isset($page_description) ? $page_description : "Explore 500+ interactive computational physics algorithms, numerical simulations, GNUplot scientific graphs, and LaTeX document templates by Dr. Alorika Chatterjee and Dr. Anirban Shaw.";

$page_keywords_text = isset($page_keywords) 
    ? $page_keywords . ", computational physics, python for physics, numerical methods" 
    : "computational physics, python for physics, numerical methods, ode, pde, runge kutta, quantum physics simulation, tise, tdse, gnuplot, latex, arduino physics lab";

// Compute Canonical URL
$canonical_base = rtrim($siteurl, '/');
$current_path = ltrim($_SERVER['REQUEST_URI'] ?? '', '/');
$script_base = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
if (!empty($script_base) && str_starts_with($current_path, $script_base)) {
    $current_rel = substr($current_path, strlen($script_base));
} else {
    $current_rel = '/' . $current_path;
}
$canonical_url = isset($page_canonical) ? $page_canonical : rtrim($siteurl, '/') . '/' . ltrim($current_rel, '/');
$canonical_url_clean = strtok($canonical_url, '?');
if (isset($_GET['menu_id']) || isset($_GET['submenu_id']) || isset($_GET['category']) || isset($_GET['id'])) {
    $canonical_url_clean = $canonical_url;
}
$clean_path = parse_url($canonical_url_clean, PHP_URL_PATH);
if (empty($clean_path) || $clean_path === '/' || str_ends_with($clean_path, '/index.php')) {
    $canonical_url_clean = rtrim($siteurl, '/') . '/';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <?php if (!empty($gtm_container_id)): ?>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','<?php echo htmlspecialchars($gtm_container_id); ?>');</script>
    <!-- End Google Tag Manager -->
    <?php endif; ?>

    <?php if (!empty($ga_measurement_id)): ?>
    <!-- Google tag (gtag.js) GA4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($ga_measurement_id); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?php echo htmlspecialchars($ga_measurement_id); ?>', {
        'page_title': <?php echo json_encode($page_title_text); ?>,
        'page_location': <?php echo json_encode($canonical_url_clean); ?>,
        'anonymize_ip': true,
        'cookie_flags': 'SameSite=None;Secure',
        'transport_type': 'beacon'
      });
    </script>
    <?php endif; ?>

    <?php if (!empty($adsense_client_id)): ?>
    <!-- Google AdSense (Auto Ads & Publisher Code) -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo htmlspecialchars($adsense_client_id); ?>"
     crossorigin="anonymous"></script>
    <?php endif; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title_text); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_desc_text); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($page_keywords_text); ?>">
    <meta name="author" content="Dr. Alorika Chatterjee & Dr. Anirban Shaw">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url_clean); ?>">

    <!-- Google Search Console Site Verification -->
    <meta name="google-site-verification" content="_NAHQtPqFwdwcx50u8ZV2jv8SCkScUd2QwqqYwT2Fbs">

    <?php if (!empty($adsense_client_id)): ?>
    <!-- Google AdSense Account Verification Meta Tag -->
    <meta name="google-adsense-account" content="<?php echo htmlspecialchars($adsense_client_id); ?>">
    <?php endif; ?>

    <!-- Search Engine Indexing Directives -->
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="bingbot" content="index, follow">

    <!-- OpenGraph / Social Graph Meta Tags -->
    <meta property="og:locale" content="en_US">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Python4Physics">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title_text); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_desc_text); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url_clean); ?>">
    <meta property="og:image" content="<?php echo $siteurl; ?>assets/images/logo.png">
    <meta property="og:image:alt" content="Python4Physics Computational Laboratory">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page_title_text); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($page_desc_text); ?>">
    <meta name="twitter:image" content="<?php echo $siteurl; ?>assets/images/logo.png">

    <!-- Schema.org JSON-LD Structured Data for Google Rich Snippets -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "WebSite",
          "@id": "https://v2.python4physics.in/#website",
          "url": "https://v2.python4physics.in/",
          "name": "Python4Physics",
          "description": "500+ Computational Physics Algorithms, Numerical Solvers & Scientific Telemetry",
          "publisher": {
            "@id": "https://v2.python4physics.in/#organization"
          },
          "potentialAction": {
            "@type": "SearchAction",
            "target": {
              "@type": "EntryPoint",
              "urlTemplate": "https://v2.python4physics.in/index.php?q={search_term_string}"
            },
            "query-input": "required name=search_term_string"
          }
        },
        {
          "@type": "EducationalOrganization",
          "@id": "https://v2.python4physics.in/#organization",
          "name": "Python4Physics",
          "url": "https://v2.python4physics.in/",
          "logo": "https://v2.python4physics.in/assets/images/logo.png",
          "founder": [
            {
              "@type": "Person",
              "name": "Dr. Alorika Chatterjee"
            },
            {
              "@type": "Person",
              "name": "Dr. Anirban Shaw"
            }
          ],
          "knowsAbout": [
            "Computational Physics",
            "Numerical Differential Equations",
            "Quantum Mechanics Simulation",
            "GNUplot Scientific Plotting",
            "LaTeX Document Typesetting",
            "Arduino Physics Instrumentation"
          ]
        }
      ]
    }
    </script>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo $siteurl; ?>program/python/python.ico" type="image/x-icon">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Outfit:wght@500;600;700;800&family=STIX+Two+Text:ital,wght@0,400;0,600;0,700;1,400;1,600&display=swap" rel="stylesheet">

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

    <!-- Global Physics Telemetry & Analytics Event Tracker -->
    <script>
      window.trackPhysicsEvent = function(eventName, params) {
        try {
          if (typeof gtag === 'function') {
            gtag('event', eventName, params || {});
          }
          if (window.dataLayer) {
            window.dataLayer.push(Object.assign({ event: eventName }, params || {}));
          }
        } catch (e) {
          console.warn('[Analytics Telemetry Error]', e);
        }
      };
    </script>
</head>
<body>
    <?php if (!empty($gtm_container_id)): ?>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo htmlspecialchars($gtm_container_id); ?>"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <?php endif; ?>
