<?php
/**
 * Python4Physics - Dynamic XML Sitemap Generator
 * Automatically catalogs all educational pages, curriculum chapters, GNUplot scripts, and LaTeX templates.
 */
require_once __DIR__ . '/site_config.php';

// Base URL for production
$base_url = "https://v2.python4physics.in";
if (!empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $base_url = rtrim($protocol . $_SERVER['HTTP_HOST'], '/');
}

$today = date('Y-m-d');

header("Content-Type: application/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

    <!-- Core Landing Pages -->
    <url>
        <loc><?php echo $base_url; ?>/</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>/index.php</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>/program/python/index.php</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>/program/gnuplot/index.php</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>/program/latex/index.php</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>/arduino.php</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>/assignments.php</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>/api/docs.php</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>/feedback.php</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>/contact.php</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>

    <!-- Python Physics Programs & Subtopics -->
    <?php
    $pyMenuFile = __DIR__ . '/program/python/menu.php';
    if (file_exists($pyMenuFile)) {
        include $pyMenuFile;
        if (isset($menu_titles) && is_array($menu_titles)) {
            foreach ($menu_titles as $mid => $mtitle) {
                $subtopics = $sub_menu_titles[$mid] ?? [];
                foreach ($subtopics as $sid => $stitle) {
                    $loc = $base_url . "/program/python/program.php?menu_id=" . urlencode($mid) . "&amp;submenu_id=" . urlencode($sid);
                    echo "    <url>\n";
                    echo "        <loc>" . htmlspecialchars($loc) . "</loc>\n";
                    echo "        <lastmod>" . $today . "</lastmod>\n";
                    echo "        <changefreq>monthly</changefreq>\n";
                    echo "        <priority>0.75</priority>\n";
                    echo "    </url>\n";
                }
            }
        }
    }
    ?>

    <!-- GNUplot Graph Programs -->
    <?php
    $gnuMenuFile = __DIR__ . '/program/gnuplot/menu.php';
    if (file_exists($gnuMenuFile)) {
        unset($menu_titles, $sub_menu_titles);
        include $gnuMenuFile;
        if (isset($menu_titles) && is_array($menu_titles)) {
            foreach ($menu_titles as $mid => $mtitle) {
                $subtopics = $sub_menu_titles[$mid] ?? [];
                foreach ($subtopics as $sid => $stitle) {
                    $loc = $base_url . "/program/gnuplot/program.php?menu_id=" . urlencode($mid) . "&amp;submenu_id=" . urlencode($sid);
                    echo "    <url>\n";
                    echo "        <loc>" . htmlspecialchars($loc) . "</loc>\n";
                    echo "        <lastmod>" . $today . "</lastmod>\n";
                    echo "        <changefreq>monthly</changefreq>\n";
                    echo "        <priority>0.7</priority>\n";
                    echo "    </url>\n";
                }
            }
        }
    }
    ?>

    <!-- LaTeX Formulations & Document Classes -->
    <?php
    $latexMenuFile = __DIR__ . '/program/latex/menu.php';
    if (file_exists($latexMenuFile)) {
        unset($menu_titles, $sub_menu_titles);
        include $latexMenuFile;
        if (isset($menu_titles) && is_array($menu_titles)) {
            foreach ($menu_titles as $mid => $mtitle) {
                $subtopics = $sub_menu_titles[$mid] ?? [];
                foreach ($subtopics as $sid => $stitle) {
                    $loc = $base_url . "/program/latex/program.php?menu_id=" . urlencode($mid) . "&amp;submenu_id=" . urlencode($sid);
                    echo "    <url>\n";
                    echo "        <loc>" . htmlspecialchars($loc) . "</loc>\n";
                    echo "        <lastmod>" . $today . "</lastmod>\n";
                    echo "        <changefreq>monthly</changefreq>\n";
                    echo "        <priority>0.7</priority>\n";
                    echo "    </url>\n";
                }
            }
        }
    }
    ?>

</urlset>
