<?php
/**
 * Python4Physics - Dynamic XML Sitemap Generator
 * Automatically catalogs all educational pages, curriculum chapters, 
 * interactive physics labs, GNUplot scripts, and LaTeX templates.
 * 
 * Synchronizes with database in real-time and writes fresh sitemap.xml to disk.
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';

// Detect canonical production URL
$base_url = "https://python4physics.in";
if (!empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost' && !str_starts_with($_SERVER['HTTP_HOST'], '127.0.0.1')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $base_url = rtrim($protocol . $_SERVER['HTTP_HOST'], '/');
}

/**
 * Builds the full array of sitemap URLs with metadata
 */
function build_sitemap_urls($base_url, $conn = null) {
    $today = date('Y-m-d');
    $urls = [];

    // Helper to add URL cleanly
    $add_url = function($path, $priority, $changefreq, $lastmod = null) use (&$urls, $base_url, $today) {
        $loc = rtrim($base_url, '/') . '/' . ltrim($path, '/');
        $urls[] = [
            'loc' => $loc,
            'lastmod' => $lastmod ?: $today,
            'changefreq' => $changefreq,
            'priority' => number_format((float)$priority, 2, '.', '')
        ];
    };

    // 1. Core Landing & Main Directory Pages
    $core_pages = [
        ['path' => '', 'priority' => 1.0, 'freq' => 'daily', 'file' => 'index.php'],
        ['path' => 'index.php', 'priority' => 1.0, 'freq' => 'daily', 'file' => 'index.php'],
        ['path' => 'about.php', 'priority' => 0.85, 'freq' => 'monthly', 'file' => 'about.php'],
        ['path' => 'assignments.php', 'priority' => 0.95, 'freq' => 'weekly', 'file' => 'assignments.php'],
        ['path' => 'visualization.php', 'priority' => 0.95, 'freq' => 'weekly', 'file' => 'visualization.php'],
        ['path' => 'program/visualization/index.php', 'priority' => 0.95, 'freq' => 'weekly', 'file' => 'program/visualization/index.php'],
        ['path' => 'program/python/index.php', 'priority' => 0.95, 'freq' => 'weekly', 'file' => 'program/python/index.php'],
        ['path' => 'program/gnuplot/index.php', 'priority' => 0.90, 'freq' => 'weekly', 'file' => 'program/gnuplot/index.php'],
        ['path' => 'program/latex/index.php', 'priority' => 0.90, 'freq' => 'weekly', 'file' => 'program/latex/index.php'],
        ['path' => 'arduino.php', 'priority' => 0.90, 'freq' => 'weekly', 'file' => 'arduino.php'],
        ['path' => 'api/docs.php', 'priority' => 0.70, 'freq' => 'monthly', 'file' => 'api/docs.php'],
        ['path' => 'contact.php', 'priority' => 0.65, 'freq' => 'monthly', 'file' => 'contact.php'],
        ['path' => 'feedback.php', 'priority' => 0.60, 'freq' => 'monthly', 'file' => 'feedback.php'],
        ['path' => 'privacy.php', 'priority' => 0.50, 'freq' => 'monthly', 'file' => 'privacy.php'],
        ['path' => 'terms.php', 'priority' => 0.50, 'freq' => 'monthly', 'file' => 'terms.php'],
        ['path' => 'disclaimer.php', 'priority' => 0.50, 'freq' => 'monthly', 'file' => 'disclaimer.php'],
    ];

    foreach ($core_pages as $cp) {
        $filePath = __DIR__ . '/' . $cp['file'];
        $mod = file_exists($filePath) ? date('Y-m-d', filemtime($filePath)) : $today;
        $add_url($cp['path'], $cp['priority'], $cp['freq'], $mod);
    }

    // 2. Syllabus & Category Assignment Pages
    $categories = [
        'newtons-laws' => 0.88,
        'potential-wells' => 0.88,
        'particle-dynamics' => 0.88,
        'central-force' => 0.85,
        'scattering' => 0.85,
        'fluid-mechanics' => 0.85,
        'mechanics' => 0.80,
        'electrodynamics' => 0.80,
        'quantum' => 0.80,
        'thermo' => 0.80
    ];
    foreach ($categories as $cat => $prio) {
        $add_url("assignments.php?category=" . urlencode($cat), $prio, 'weekly', $today);
    }

    // Individual Assignment Labs (From Database)
    if ($conn !== null) {
        try {
            $stmt = $conn->query("SELECT id, category, updated_at, created_at FROM `assignments` ORDER BY id ASC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $mod = !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : $today;
                $add_url("assignments.php?id=" . urlencode($row['id']), 0.85, 'weekly', $mod);
            }
        } catch (Exception $e) {}
    }

    // 3. Curriculum Chapters & Subtopics (Python, GNUplot, LaTeX)
    $languages = [
        'python' => ['prio' => 0.80, 'path' => 'program/python/program.php'],
        'visualization' => ['prio' => 0.85, 'path' => 'program/visualization/program.php'],
        'gnuplot' => ['prio' => 0.75, 'path' => 'program/gnuplot/program.php'],
        'latex' => ['prio' => 0.75, 'path' => 'program/latex/program.php']
    ];

    $db_found = false;
    if ($conn !== null) {
        try {
            $stmt = $conn->query("SELECT language, menu_id, submenu_id, created_at FROM `p4p_submenus` ORDER BY language, menu_id, submenu_id");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $db_found = true;
                foreach ($rows as $r) {
                    $lang = strtolower($r['language']);
                    if (!isset($languages[$lang])) continue;
                    $cfg = $languages[$lang];
                    $mod = !empty($r['created_at']) ? date('Y-m-d', strtotime($r['created_at'])) : $today;
                    $path = $cfg['path'] . "?menu_id=" . urlencode($r['menu_id']) . "&submenu_id=" . urlencode($r['submenu_id']);
                    $add_url($path, $cfg['prio'], 'monthly', $mod);
                }
            }
        } catch (Exception $e) {}
    }

    // Fallback to static menu files if DB is empty or unavailable
    if (!$db_found) {
        foreach ($languages as $lang => $cfg) {
            $menuFile = __DIR__ . "/program/{$lang}/menu.php";
            if (file_exists($menuFile)) {
                unset($menu_titles, $sub_menu_titles);
                include $menuFile;
                if (isset($menu_titles) && is_array($menu_titles)) {
                    $fileMod = date('Y-m-d', filemtime($menuFile));
                    foreach ($menu_titles as $mid => $mtitle) {
                        $subs = $sub_menu_titles[$mid] ?? [];
                        foreach ($subs as $sid => $stitle) {
                            $path = $cfg['path'] . "?menu_id=" . urlencode($mid) . "&submenu_id=" . urlencode($sid);
                            $add_url($path, $cfg['prio'], 'monthly', $fileMod);
                        }
                    }
                }
            }
        }
    }

    return $urls;
}

/**
 * Generate XML String from URLs
 */
function generate_sitemap_xml_string($urls) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
    $xml .= '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
    $xml .= '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n";
    $xml .= '        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

    foreach ($urls as $u) {
        $xml .= "    <url>\n";
        $xml .= "        <loc>" . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
        $xml .= "        <lastmod>" . htmlspecialchars($u['lastmod'], ENT_XML1, 'UTF-8') . "</lastmod>\n";
        $xml .= "        <changefreq>" . htmlspecialchars($u['changefreq'], ENT_XML1, 'UTF-8') . "</changefreq>\n";
        $xml .= "        <priority>" . htmlspecialchars($u['priority'], ENT_XML1, 'UTF-8') . "</priority>\n";
        $xml .= "    </url>\n";
    }

    $xml .= "</urlset>\n";
    return $xml;
}

/**
 * Synchronize generated XML to physical sitemap.xml on disk atomically
 */
function sync_sitemap_to_disk($xml_content) {
    $target = __DIR__ . '/sitemap.xml';
    $norm_xml = str_replace(["\r\n", "\r"], "\n", $xml_content);
    if (file_exists($target)) {
        $existing = file_get_contents($target);
        $norm_existing = str_replace(["\r\n", "\r"], "\n", $existing);
        
        // Strip volatile <lastmod> timestamps for structural comparison to prevent git dirtying
        $strip_dates = function($str) {
            return preg_replace('/<lastmod>[^<]*<\/lastmod>/', '', $str);
        };
        
        if (trim($strip_dates($norm_existing)) === trim($strip_dates($norm_xml))) {
            return true; // Structure, URLs, and priorities are identical - do not touch file on disk!
        }
    }
    $temp = __DIR__ . '/sitemap.xml.tmp';
    if (@file_put_contents($temp, $norm_xml) !== false) {
        @rename($temp, $target);
        return true;
    }
    return @file_put_contents($target, $norm_xml) !== false;
}

// Only auto-run if sitemap.php is accessed directly (not when included via require_once)
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'sitemap.php' || php_sapi_name() === 'cli') {
    // Generate sitemap
    $urls = build_sitemap_urls($base_url ?? 'https://v2.python4physics.in', $conn ?? null);
    $xml_output = generate_sitemap_xml_string($urls);

    // Automatically sync to disk file only when needed
    sync_sitemap_to_disk($xml_output);

    // Serve response based on SAPI
    if (php_sapi_name() !== 'cli') {
        header("Content-Type: application/xml; charset=utf-8");
        header("Cache-Control: public, max-age=3600");
        echo $xml_output;
        exit;
    } else {
        echo "Dynamic sitemap generated successfully with " . count($urls) . " URLs synced to sitemap.xml.\n";
    }
}
