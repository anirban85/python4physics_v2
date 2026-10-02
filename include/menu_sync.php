<?php
/**
 * Python4Physics - Menu Sync & DB Helper
 * Keeps p4p_menus, p4p_submenus and program/{lang}/menu.php files seamlessly synchronized.
 */
require_once __DIR__ . '/../db.php';

if (!function_exists('sync_menus_to_file')) {
    function sync_menus_to_file($lang, $conn) {
        $valid_langs = ['python', 'gnuplot', 'latex', 'visualization'];
        if (!in_array($lang, $valid_langs)) {
            return false;
        }

        // Fetch menus
        $stmt = $conn->prepare("SELECT menu_id, title FROM `p4p_menus` WHERE `language` = :lang ORDER BY `sort_order` ASC, `menu_id` ASC");
        $stmt->execute([':lang' => $lang]);
        $menus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Fetch submenus
        $stmt = $conn->prepare("SELECT menu_id, submenu_id, title FROM `p4p_submenus` WHERE `language` = :lang ORDER BY `menu_id` ASC, `sort_order` ASC, `submenu_id` ASC");
        $stmt->execute([':lang' => $lang]);
        $all_subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $submenus = [];
        foreach ($all_subs as $s) {
            $m_id = (string)$s['menu_id'];
            $s_id = (int)$s['submenu_id'];
            if (!isset($submenus[$m_id])) {
                $submenus[$m_id] = [];
            }
            $submenus[$m_id][$s_id] = $s['title'];
        }

        // Generate PHP content
        $code = "<?php\n";
        $code .= "// Automatically synced from Database via Admin Manager\n";
        $code .= "// Language: " . strtoupper($lang) . "\n";
        $code .= "// Last Updated: " . date('Y-m-d H:i:s') . "\n\n";

        $code .= "\$menu_titles = " . var_export($menus, true) . ";\n\n";
        $code .= "\$sub_menu_titles = " . var_export($submenus, true) . ";\n";

        $target_dir = __DIR__ . "/../program/{$lang}";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = "{$target_dir}/menu.php";
        $saved = file_put_contents($target_file, $code) !== false;
        
        // Also automatically re-sync dynamic sitemap.xml
        sync_sitemap_xml($conn);

        return $saved;
    }
}

if (!function_exists('sync_sitemap_xml')) {
    function sync_sitemap_xml($conn = null) {
        $sitemap_script = __DIR__ . '/../sitemap.php';
        if (file_exists($sitemap_script)) {
            // Include functions from sitemap.php if not yet loaded
            require_once $sitemap_script;
            if (function_exists('build_sitemap_urls') && function_exists('generate_sitemap_xml_string') && function_exists('sync_sitemap_to_disk')) {
                $base_url = "https://v2.python4physics.in";
                if (!empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost' && !str_starts_with($_SERVER['HTTP_HOST'], '127.0.0.1')) {
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
                    $base_url = rtrim($protocol . $_SERVER['HTTP_HOST'], '/');
                }
                $urls = build_sitemap_urls($base_url, $conn);
                $xml = generate_sitemap_xml_string($urls);
                return sync_sitemap_to_disk($xml);
            }
        }
        return false;
    }
}

if (!function_exists('get_admin_menus')) {
    function get_admin_menus($lang, $conn) {
        $stmt = $conn->prepare("SELECT * FROM `p4p_menus` WHERE `language` = :lang ORDER BY `sort_order` ASC, `menu_id` ASC");
        $stmt->execute([':lang' => $lang]);
        $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("SELECT * FROM `p4p_submenus` WHERE `language` = :lang ORDER BY `menu_id` ASC, `sort_order` ASC, `submenu_id` ASC");
        $stmt->execute([':lang' => $lang]);
        $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $subs_by_menu = [];
        foreach ($subs as $s) {
            $subs_by_menu[$s['menu_id']][] = $s;
        }

        foreach ($menus as &$m) {
            $m['submenus'] = $subs_by_menu[$m['menu_id']] ?? [];
        }
        unset($m);

        return $menus;
    }
}
