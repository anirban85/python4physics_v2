<?php
/**
 * Python4Physics - Dedicated Menu & Submenu Manager
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../include/menu_sync.php';
require_once __DIR__ . '/auth.php';

$page_title = "Manage Menus & Submenus";
$selected_lang = $_GET['lang'] ?? 'python';
if (!in_array($selected_lang, ['python', 'gnuplot', 'latex'])) {
    $selected_lang = 'python';
}

$notice = "";
$error = "";

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_menu') {
        $lang = $_POST['language'] ?? 'python';
        $menu_id = (int)($_POST['menu_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? $menu_id);

        if ($menu_id > 0 && !empty($title)) {
            try {
                $stmt = $conn->prepare("INSERT INTO `p4p_menus` (`language`, `menu_id`, `title`, `sort_order`) VALUES (:lang, :menu_id, :title, :sort_order)");
                $stmt->execute([':lang' => $lang, ':menu_id' => $menu_id, ':title' => $title, ':sort_order' => $sort_order]);
                sync_menus_to_file($lang, $conn);
                $notice = "Chapter #{$menu_id} ('" . htmlspecialchars($title) . "') created and synced successfully!";
            } catch (PDOException $e) {
                $error = "Failed to add menu: " . $e->getMessage();
            }
        } else {
            $error = "Please provide both a valid Menu ID and Title.";
        }
    } elseif ($action === 'edit_menu') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        if ($id > 0 && !empty($title)) {
            try {
                $stmt = $conn->prepare("UPDATE `p4p_menus` SET `title` = :title, `sort_order` = :sort_order WHERE `id` = :id");
                $stmt->execute([':title' => $title, ':sort_order' => $sort_order, ':id' => $id]);
                sync_menus_to_file($selected_lang, $conn);
                $notice = "Menu updated successfully!";
            } catch (PDOException $e) {
                $error = "Failed to update menu: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_menu') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                // Fetch info first for sync
                $stmt = $conn->prepare("SELECT language, menu_id FROM `p4p_menus` WHERE `id` = :id");
                $stmt->execute([':id' => $id]);
                $m = $stmt->fetch();
                if ($m) {
                    // Delete submenus belonging to this menu as well
                    $del_sub = $conn->prepare("DELETE FROM `p4p_submenus` WHERE `language` = :lang AND `menu_id` = :m_id");
                    $del_sub->execute([':lang' => $m['language'], ':m_id' => $m['menu_id']]);

                    $del = $conn->prepare("DELETE FROM `p4p_menus` WHERE `id` = :id");
                    $del->execute([':id' => $id]);

                    sync_menus_to_file($m['language'], $conn);
                    $notice = "Chapter and all associated subtopics deleted.";
                }
            } catch (PDOException $e) {
                $error = "Failed to delete menu: " . $e->getMessage();
            }
        }
    } elseif ($action === 'add_submenu') {
        $lang = $_POST['language'] ?? 'python';
        $menu_id = (int)($_POST['menu_id'] ?? 0);
        $submenu_id = (int)($_POST['submenu_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? $submenu_id);

        if ($menu_id > 0 && $submenu_id > 0 && !empty($title)) {
            try {
                $stmt = $conn->prepare("INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES (:lang, :menu_id, :submenu_id, :title, :sort_order)");
                $stmt->execute([':lang' => $lang, ':menu_id' => $menu_id, ':submenu_id' => $submenu_id, ':title' => $title, ':sort_order' => $sort_order]);
                sync_menus_to_file($lang, $conn);
                $notice = "Subtopic #{$submenu_id} ('" . htmlspecialchars($title) . "') created and synced!";
            } catch (PDOException $e) {
                $error = "Failed to add subtopic: " . $e->getMessage();
            }
        } else {
            $error = "Please fill in all submenu fields.";
        }
    } elseif ($action === 'edit_submenu') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        if ($id > 0 && !empty($title)) {
            try {
                $stmt = $conn->prepare("UPDATE `p4p_submenus` SET `title` = :title, `sort_order` = :sort_order WHERE `id` = :id");
                $stmt->execute([':title' => $title, ':sort_order' => $sort_order, ':id' => $id]);
                sync_menus_to_file($selected_lang, $conn);
                $notice = "Subtopic updated successfully!";
            } catch (PDOException $e) {
                $error = "Failed to update subtopic: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_submenu') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM `p4p_submenus` WHERE `id` = :id");
                $stmt->execute([':id' => $id]);
                sync_menus_to_file($selected_lang, $conn);
                $notice = "Subtopic deleted successfully!";
            } catch (PDOException $e) {
                $error = "Failed to delete subtopic: " . $e->getMessage();
            }
        }
    } elseif ($action === 'sync_all') {
        sync_menus_to_file('python', $conn);
        sync_menus_to_file('gnuplot', $conn);
        sync_menus_to_file('latex', $conn);
        $notice = "All Python, GNUplot, and LaTeX menus have been regenerated and synchronized!";
    }
}

// Fetch menus and submenus for current language
$menus = get_admin_menus($selected_lang, $conn);

// Suggest next menu_id
$max_menu_id = 0;
foreach ($menus as $m) {
    if ($m['menu_id'] > $max_menu_id) $max_menu_id = $m['menu_id'];
}
$next_menu_id = $max_menu_id + 1;

require_once __DIR__ . '/layout_top.php';
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Curriculum Menus & Submenus</h1>
        <p class="admin-page-subtitle">Add, organize, and edit chapters and topics across Python, GNUplot, and LaTeX suites.</p>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <form method="POST" style="display: inline;">
            <input type="hidden" name="action" value="sync_all">
            <button type="submit" class="btn-admin btn-admin-secondary">
                <i class="fa-solid fa-arrows-rotate"></i> Force Sync All Menus
            </button>
        </form>
        <button type="button" class="btn-admin btn-admin-primary" onclick="openAddMenuModal()">
            <i class="fa-solid fa-folder-plus"></i> Add New Chapter
        </button>
    </div>
</div>

<?php if (!empty($notice)): ?>
    <div style="padding: 0.85rem 1.25rem; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.75rem; color: #34d399; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-circle-check"></i>
        <span><?php echo $notice; ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div style="padding: 0.85rem 1.25rem; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 0.75rem; color: #f87171; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<!-- Language Switcher Tabs -->
<div style="display: flex; gap: 0.5rem; margin-bottom: 1.75rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.75rem; overflow-x: auto;">
    <a href="?lang=python" class="btn-admin <?php echo $selected_lang === 'python' ? 'btn-admin-primary' : 'btn-admin-secondary'; ?>">
        <i class="fa-brands fa-python"></i> Python Chapters (<?php echo count(get_admin_menus('python', $conn)); ?>)
    </a>
    <a href="?lang=gnuplot" class="btn-admin <?php echo $selected_lang === 'gnuplot' ? 'btn-admin-primary' : 'btn-admin-secondary'; ?>">
        <i class="fa-solid fa-chart-line"></i> GNUplot Chapters (<?php echo count(get_admin_menus('gnuplot', $conn)); ?>)
    </a>
    <a href="?lang=latex" class="btn-admin <?php echo $selected_lang === 'latex' ? 'btn-admin-primary' : 'btn-admin-secondary'; ?>">
        <i class="fa-solid fa-file-invoice"></i> LaTeX Chapters (<?php echo count(get_admin_menus('latex', $conn)); ?>)
    </a>
</div>

<!-- Menus Accordion / List -->
<div style="display: flex; flex-direction: column; gap: 1rem;">
    <?php if (empty($menus)): ?>
        <div class="admin-card" style="text-align: center; padding: 3rem; color: var(--admin-text-muted);">
            <i class="fa-solid fa-folder-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.4;"></i>
            <h3>No Chapters found for <?php echo strtoupper($selected_lang); ?></h3>
            <p>Click "Add New Chapter" to create your first curriculum section.</p>
        </div>
    <?php else: ?>
        <?php foreach ($menus as $menu): ?>
            <div class="admin-card" style="margin-bottom: 0; padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                        <span class="admin-badge admin-badge-cyan" style="font-size: 0.85rem; padding: 0.3rem 0.65rem;">
                            Chapter <?php echo (int)$menu['menu_id']; ?>
                        </span>
                        <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700;">
                            <?php echo htmlspecialchars($menu['title']); ?>
                        </h3>
                        <span class="admin-badge admin-badge-purple">
                            <?php echo count($menu['submenus']); ?> Subtopics
                        </span>
                    </div>

                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" onclick="openAddSubmenuModal(<?php echo (int)$menu['menu_id']; ?>, '<?php echo htmlspecialchars(addslashes($menu['title'])); ?>')">
                            <i class="fa-solid fa-plus"></i> Add Subtopic
                        </button>
                        <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" onclick="openEditMenuModal(<?php echo (int)$menu['id']; ?>, '<?php echo htmlspecialchars(addslashes($menu['title'])); ?>', <?php echo (int)$menu['sort_order']; ?>)">
                            <i class="fa-solid fa-pen"></i> Edit
                        </button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete Chapter <?php echo (int)$menu['menu_id']; ?> and ALL its subtopics? This cannot be undone.');">
                            <input type="hidden" name="action" value="delete_menu">
                            <input type="hidden" name="id" value="<?php echo (int)$menu['id']; ?>">
                            <button type="submit" class="btn-admin btn-admin-danger btn-admin-sm">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Subtopics list -->
                <?php if (!empty($menu['submenus'])): ?>
                    <div style="margin-top: 1rem; border-top: 1px solid var(--admin-border); padding-top: 0.75rem;">
                        <div class="admin-table-container">
                            <table class="admin-table" style="font-size: 0.85rem;">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">Topic ID</th>
                                        <th>Subtopic Title</th>
                                        <th style="width: 100px;">Order</th>
                                        <th style="width: 180px; text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($menu['submenus'] as $sub): ?>
                                        <tr>
                                            <td>
                                                <span class="admin-badge admin-badge-amber">
                                                    <?php echo (int)$menu['menu_id']; ?>.<?php echo (int)$sub['submenu_id']; ?>
                                                </span>
                                            </td>
                                            <td style="font-weight: 600;">
                                                <?php echo htmlspecialchars($sub['title']); ?>
                                            </td>
                                            <td><?php echo (int)$sub['sort_order']; ?></td>
                                            <td style="text-align: right;">
                                                <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" onclick="openEditSubmenuModal(<?php echo (int)$sub['id']; ?>, '<?php echo htmlspecialchars(addslashes($sub['title'])); ?>', <?php echo (int)$sub['sort_order']; ?>)">
                                                    <i class="fa-solid fa-pen"></i> Edit
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete subtopic \'<?php echo htmlspecialchars(addslashes($sub['title'])); ?>\'?');">
                                                    <input type="hidden" name="action" value="delete_submenu">
                                                    <input type="hidden" name="id" value="<?php echo (int)$sub['id']; ?>">
                                                    <button type="submit" class="btn-admin btn-admin-danger btn-admin-sm">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: Add Menu -->
<div id="addMenuModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: 1rem; width: 100%; max-width: 500px; padding: 1.5rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="margin: 0; font-size: 1.25rem;"><i class="fa-solid fa-folder-plus" style="color: var(--admin-accent);"></i> Add New Chapter</h3>
            <button type="button" onclick="closeAddMenuModal()" style="background: none; border: none; color: var(--admin-text-muted); font-size: 1.25rem; cursor: pointer;">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="add_menu">
            <input type="hidden" name="language" value="<?php echo htmlspecialchars($selected_lang); ?>">

            <div class="admin-form-group">
                <label class="admin-label">Curriculum Language</label>
                <input type="text" class="admin-input" value="<?php echo strtoupper($selected_lang); ?>" readonly style="opacity: 0.7;">
            </div>

            <div class="admin-form-group">
                <label class="admin-label">Chapter Number (Menu ID)</label>
                <input type="number" name="menu_id" class="admin-input" value="<?php echo $next_menu_id; ?>" required min="1">
            </div>

            <div class="admin-form-group">
                <label class="admin-label">Chapter Title</label>
                <input type="text" name="title" class="admin-input" placeholder="e.g. Electromagnetic Wave Propagation" required autofocus>
            </div>

            <div class="admin-form-group">
                <label class="admin-label">Display Sort Order</label>
                <input type="number" name="sort_order" class="admin-input" value="<?php echo $next_menu_id; ?>" required min="0">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" class="btn-admin btn-admin-secondary" onclick="closeAddMenuModal()">Cancel</button>
                <button type="submit" class="btn-admin btn-admin-primary">Create Chapter</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Menu -->
<div id="editMenuModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: 1rem; width: 100%; max-width: 500px; padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="margin: 0; font-size: 1.25rem;"><i class="fa-solid fa-pen-to-square" style="color: var(--admin-accent);"></i> Edit Chapter</h3>
            <button type="button" onclick="closeEditMenuModal()" style="background: none; border: none; color: var(--admin-text-muted); font-size: 1.25rem; cursor: pointer;">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="edit_menu">
            <input type="hidden" name="id" id="edit_menu_id">

            <div class="admin-form-group">
                <label class="admin-label">Chapter Title</label>
                <input type="text" name="title" id="edit_menu_title" class="admin-input" required>
            </div>

            <div class="admin-form-group">
                <label class="admin-label">Display Sort Order</label>
                <input type="number" name="sort_order" id="edit_menu_sort" class="admin-input" required min="0">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" class="btn-admin btn-admin-secondary" onclick="closeEditMenuModal()">Cancel</button>
                <button type="submit" class="btn-admin btn-admin-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Submenu -->
<div id="addSubmenuModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: 1rem; width: 100%; max-width: 500px; padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="margin: 0; font-size: 1.25rem;"><i class="fa-solid fa-plus" style="color: var(--admin-accent);"></i> Add Subtopic</h3>
            <button type="button" onclick="closeAddSubmenuModal()" style="background: none; border: none; color: var(--admin-text-muted); font-size: 1.25rem; cursor: pointer;">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="add_submenu">
            <input type="hidden" name="language" value="<?php echo htmlspecialchars($selected_lang); ?>">
            <input type="hidden" name="menu_id" id="sub_parent_menu_id">

            <div class="admin-form-group">
                <label class="admin-label">Parent Chapter</label>
                <input type="text" id="sub_parent_menu_title" class="admin-input" readonly style="opacity: 0.8;">
            </div>

            <div class="admin-form-group">
                <label class="admin-label">Subtopic Number (Submenu ID)</label>
                <input type="number" name="submenu_id" class="admin-input" value="1" required min="1">
            </div>

            <div class="admin-form-group">
                <label class="admin-label">Subtopic Title</label>
                <input type="text" name="title" class="admin-input" placeholder="e.g. Poynting Vector & Energy Flux" required autofocus>
            </div>

            <div class="admin-form-group">
                <label class="admin-label">Display Sort Order</label>
                <input type="number" name="sort_order" class="admin-input" value="1" required min="0">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" class="btn-admin btn-admin-secondary" onclick="closeAddSubmenuModal()">Cancel</button>
                <button type="submit" class="btn-admin btn-admin-primary">Add Subtopic</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Submenu -->
<div id="editSubmenuModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: 1rem; width: 100%; max-width: 500px; padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="margin: 0; font-size: 1.25rem;"><i class="fa-solid fa-pen-to-square" style="color: var(--admin-accent);"></i> Edit Subtopic</h3>
            <button type="button" onclick="closeEditSubmenuModal()" style="background: none; border: none; color: var(--admin-text-muted); font-size: 1.25rem; cursor: pointer;">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="edit_submenu">
            <input type="hidden" name="id" id="edit_sub_id">

            <div class="admin-form-group">
                <label class="admin-label">Subtopic Title</label>
                <input type="text" name="title" id="edit_sub_title" class="admin-input" required>
            </div>

            <div class="admin-form-group">
                <label class="admin-label">Display Sort Order</label>
                <input type="number" name="sort_order" id="edit_sub_sort" class="admin-input" required min="0">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" class="btn-admin btn-admin-secondary" onclick="closeEditSubmenuModal()">Cancel</button>
                <button type="submit" class="btn-admin btn-admin-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddMenuModal() {
    document.getElementById('addMenuModal').style.display = 'flex';
}
function closeAddMenuModal() {
    document.getElementById('addMenuModal').style.display = 'none';
}
function openEditMenuModal(id, title, sort) {
    document.getElementById('edit_menu_id').value = id;
    document.getElementById('edit_menu_title').value = title;
    document.getElementById('edit_menu_sort').value = sort;
    document.getElementById('editMenuModal').style.display = 'flex';
}
function closeEditMenuModal() {
    document.getElementById('editMenuModal').style.display = 'none';
}
function openAddSubmenuModal(menuId, menuTitle) {
    document.getElementById('sub_parent_menu_id').value = menuId;
    document.getElementById('sub_parent_menu_title').value = 'Chapter ' + menuId + ': ' + menuTitle;
    document.getElementById('addSubmenuModal').style.display = 'flex';
}
function closeAddSubmenuModal() {
    document.getElementById('addSubmenuModal').style.display = 'none';
}
function openEditSubmenuModal(id, title, sort) {
    document.getElementById('edit_sub_id').value = id;
    document.getElementById('edit_sub_title').value = title;
    document.getElementById('edit_sub_sort').value = sort;
    document.getElementById('editSubmenuModal').style.display = 'flex';
}
function closeEditSubmenuModal() {
    document.getElementById('editSubmenuModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/layout_bottom.php'; ?>
