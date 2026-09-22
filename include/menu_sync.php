<?php
/**
 * Python4Physics - Menu Sync & DB Helper
 * Keeps p4p_menus, p4p_submenus and program/{lang}/menu.php files seamlessly synchronized.
 */
require_once __DIR__ . '/../db.php';

if (!function_exists('sync_menus_to_file')) {
    function sync_menus_to_file($lang, $conn) {
        $valid_langs = ['python', 'gnuplot', 'latex'];
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

        $target_file = __DIR__ . "/../program/{$lang}/menu.php";
        return file_put_contents($target_file, $code) !== false;
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
