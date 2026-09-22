 <h2><?php echo htmlspecialchars(isset($menu_titles[$menu_id]) ? $menu_titles[$menu_id] : 'Menu'); ?></h2>
        <ul>
            <?php
            if (isset($sub_menu_titles[$menu_id])) {
                foreach ($sub_menu_titles[$menu_id] as $id => $title) {
                    $active_class = ($submenu_id == $id) ? 'class="active"' : '';
                    echo "<li $active_class><a href='program.php?menu_id=$menu_id&submenu_id=$id'>$title</a></li>";
                }
            }
            ?>
        </ul>