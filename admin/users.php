<?php
/**
 * Python4Physics - Admin User Management & Telemetry Tracking Dashboard
 * Comprehensive tracking of Google & GitHub authenticated users, activity histories,
 * code executions, notebook exports, and client forensics.
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth.php';
require_admin_login();

// Handle CSV Exports
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    if ($export_type === 'users_csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="p4p_users_' . date('Y-m-d_His') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Provider', 'Provider ID', 'Name', 'Email', 'Logins', 'IP Address', 'Country', 'Status', 'Registered At', 'Last Login At']);
        
        $stmt = $conn->query("SELECT id, provider, provider_id, name, email, login_count, ip_address, country, status, created_at, last_login_at FROM p4p_users ORDER BY id DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    } elseif ($export_type === 'activities_csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="p4p_user_activities_' . date('Y-m-d_His') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Activity ID', 'User ID', 'User Name', 'User Email', 'Action', 'Page Title', 'Page URL', 'Menu ID', 'Submenu ID', 'Program ID', 'Details', 'IP Address', 'Timestamp']);
        
        $stmt = $conn->query("
            SELECT a.id, a.user_id, u.name, u.email, a.action, a.page_title, a.page_url, a.menu_id, a.submenu_id, a.program_id, a.details, a.ip_address, a.created_at
            FROM p4p_user_activities a
            LEFT JOIN p4p_users u ON a.user_id = u.id
            ORDER BY a.id DESC
            LIMIT 5000
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}

// Relative time helper
function time_elapsed_string($datetime, $full = false) {
    if (empty($datetime)) return 'Never';
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'min',
        's' => 'sec',
    ];
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Action badge styling helper
function get_action_badge_html($action) {
    switch ($action) {
        case 'execute_code':
            return '<span class="admin-badge admin-badge-emerald"><i class="fa-solid fa-play"></i> Execute Code</span>';
        case 'copy_code':
            return '<span class="admin-badge admin-badge-amber"><i class="fa-regular fa-copy"></i> Copy Code</span>';
        case 'export_ipynb':
            return '<span class="admin-badge admin-badge-cyan"><i class="fa-solid fa-book-journal-whills"></i> Export .ipynb</span>';
        case 'export_py':
            return '<span class="admin-badge admin-badge-purple"><i class="fa-brands fa-python"></i> Export .py</span>';
        case 'view_topic':
            return '<span class="admin-badge" style="background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3);"><i class="fa-regular fa-eye"></i> View Topic</span>';
        case 'login':
            return '<span class="admin-badge admin-badge-primary"><i class="fa-solid fa-right-to-bracket"></i> Signed In</span>';
        case 'logout':
            return '<span class="admin-badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);"><i class="fa-solid fa-right-from-bracket"></i> Signed Out</span>';
        default:
            return '<span class="admin-badge" style="background: rgba(148, 163, 184, 0.15); color: #94a3b8;"><i class="fa-solid fa-bolt"></i> ' . htmlspecialchars($action) . '</span>';
    }
}

// Read parameters
$tab = $_GET['tab'] ?? 'users'; // 'users' or 'telemetry'
$search = trim($_GET['search'] ?? '');
$provider_filter = $_GET['provider'] ?? 'all';
$action_filter = $_GET['action'] ?? 'all';
$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Aggregate KPI metrics
$total_users = 0;
$active_today = 0;
$google_users = 0;
$github_users = 0;
$total_activities = 0;
$total_execs = 0;
$total_exports = 0;

try {
    if ($conn) {
        $total_users = (int)$conn->query("SELECT COUNT(*) FROM p4p_users")->fetchColumn();
        $active_today = (int)$conn->query("SELECT COUNT(DISTINCT user_id) FROM p4p_user_activities WHERE created_at >= (NOW() - INTERVAL 1 DAY)")->fetchColumn();
        $google_users = (int)$conn->query("SELECT COUNT(*) FROM p4p_users WHERE provider = 'google'")->fetchColumn();
        $github_users = (int)$conn->query("SELECT COUNT(*) FROM p4p_users WHERE provider = 'github'")->fetchColumn();
        $total_activities = (int)$conn->query("SELECT COUNT(*) FROM p4p_user_activities")->fetchColumn();
        $total_execs = (int)$conn->query("SELECT COUNT(*) FROM p4p_user_activities WHERE action = 'execute_code'")->fetchColumn();
        $total_exports = (int)$conn->query("SELECT COUNT(*) FROM p4p_user_activities WHERE action IN ('export_ipynb', 'export_py')")->fetchColumn();
    }
} catch (Throwable $e) {
    $db_error = $e->getMessage();
}

// Fetch Users List
$users_list = [];
if ($tab === 'users') {
    try {
        $where_clauses = [];
        $params = [];

        if ($search !== '') {
            $where_clauses[] = "(name LIKE ? OR email LIKE ? OR ip_address LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($provider_filter !== 'all') {
            $where_clauses[] = "provider = ?";
            $params[] = $provider_filter;
        }

        $where_sql = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";
        $query = "
            SELECT u.*, 
                   COUNT(a.id) as activity_count,
                   MAX(a.created_at) as last_action_at
            FROM p4p_users u
            LEFT JOIN p4p_user_activities a ON u.id = a.user_id
            {$where_sql}
            GROUP BY u.id
            ORDER BY u.last_login_at DESC, u.id DESC
            LIMIT 200
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $users_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $users_error = $e->getMessage();
    }
}

// Fetch Telemetry List
$telemetry_list = [];
$inspected_user = null;
if ($tab === 'telemetry') {
    try {
        $where_clauses = [];
        $params = [];

        if ($selected_user_id > 0) {
            $where_clauses[] = "a.user_id = ?";
            $params[] = $selected_user_id;

            // Fetch user info for header
            $u_stmt = $conn->prepare("SELECT * FROM p4p_users WHERE id = ?");
            $u_stmt->execute([$selected_user_id]);
            $inspected_user = $u_stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($action_filter !== 'all') {
            $where_clauses[] = "a.action = ?";
            $params[] = $action_filter;
        }

        if ($search !== '') {
            $where_clauses[] = "(u.name LIKE ? OR u.email LIKE ? OR a.page_title LIKE ? OR a.ip_address LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $where_sql = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";
        $query = "
            SELECT a.*, u.name as user_name, u.email as user_email, u.avatar_url, u.provider as user_provider
            FROM p4p_user_activities a
            LEFT JOIN p4p_users u ON a.user_id = u.id
            {$where_sql}
            ORDER BY a.id DESC
            LIMIT 300
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $telemetry_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $telemetry_error = $e->getMessage();
    }
}

$page_title = "Users & Activity Forensics";
require_once __DIR__ . '/layout_top.php';
?>

<!-- Header -->
<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title"><i class="fa-solid fa-users" style="color: var(--admin-accent);"></i> Registered Users &amp; Telemetry</h1>
        <p class="admin-page-subtitle">Inspect student profiles authenticated via Google &amp; GitHub, trace code execution telemetry, and review lab exports.</p>
    </div>
    <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
        <a href="?export=users_csv" class="btn-admin btn-admin-secondary" title="Export users as CSV">
            <i class="fa-solid fa-file-csv"></i> Export Users
        </a>
        <a href="?export=activities_csv" class="btn-admin btn-admin-secondary" title="Export recent activities as CSV">
            <i class="fa-solid fa-download"></i> Export Telemetry
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="admin-grid-stats" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 2rem;">
    <div class="admin-stat-card">
        <i class="fa-solid fa-user-check admin-stat-icon" style="color: #38bdf8;"></i>
        <div class="admin-stat-label">Total Users</div>
        <div class="admin-stat-value"><?php echo number_format($total_users); ?></div>
        <div style="margin-top: 0.4rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            OAuth authenticated
        </div>
    </div>

    <div class="admin-stat-card">
        <i class="fa-solid fa-bolt admin-stat-icon" style="color: #10b981;"></i>
        <div class="admin-stat-label">Active Today</div>
        <div class="admin-stat-value"><?php echo number_format($active_today); ?></div>
        <div style="margin-top: 0.4rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Interacted in last 24h
        </div>
    </div>

    <div class="admin-stat-card">
        <i class="fa-solid fa-terminal admin-stat-icon" style="color: #a855f7;"></i>
        <div class="admin-stat-label">Code Runs</div>
        <div class="admin-stat-value"><?php echo number_format($total_execs); ?></div>
        <div style="margin-top: 0.4rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Pyodide simulation executions
        </div>
    </div>

    <div class="admin-stat-card">
        <i class="fa-solid fa-file-export admin-stat-icon" style="color: #f59e0b;"></i>
        <div class="admin-stat-label">Notebook Exports</div>
        <div class="admin-stat-value"><?php echo number_format($total_exports); ?></div>
        <div style="margin-top: 0.4rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            .ipynb &amp; .py scripts exported
        </div>
    </div>

    <div class="admin-stat-card">
        <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 0.5rem;">
            <i class="fa-brands fa-google" style="color: #ea4335; font-size: 1.1rem;"></i>
            <span style="font-weight: 700; font-size: 1.15rem; color: var(--admin-text-main);"><?php echo $google_users; ?></span>
            <span style="color: var(--admin-border); margin: 0 0.2rem;">|</span>
            <i class="fa-brands fa-github" style="color: #f8fafc; font-size: 1.1rem;"></i>
            <span style="font-weight: 700; font-size: 1.15rem; color: var(--admin-text-main);"><?php echo $github_users; ?></span>
        </div>
        <div class="admin-stat-label">OAuth Providers</div>
        <div style="margin-top: 0.25rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Google vs GitHub split
        </div>
    </div>
</div>

<!-- Tab Navigation -->
<div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.5rem;">
    <a href="?tab=users" class="btn-admin <?php echo $tab === 'users' ? 'btn-admin-primary' : 'btn-admin-secondary'; ?>" style="border-radius: 8px;">
        <i class="fa-solid fa-address-book"></i> User Directory (<?php echo $total_users; ?>)
    </a>
    <a href="?tab=telemetry" class="btn-admin <?php echo $tab === 'telemetry' ? 'btn-admin-primary' : 'btn-admin-secondary'; ?>" style="border-radius: 8px;">
        <i class="fa-solid fa-stream"></i> Telemetry Activity Stream (<?php echo $total_activities; ?>)
    </a>
</div>

<?php if ($tab === 'users'): ?>
<!-- ========================================================================= -->
<!-- TAB 1: USER DIRECTORY                                                     -->
<!-- ========================================================================= -->

<!-- Filter Toolbar -->
<div class="admin-card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
        <input type="hidden" name="tab" value="users">
        
        <div style="flex: 1; min-width: 240px; position: relative;">
            <input type="text" name="search" class="admin-input" placeholder="Search by name, email, or IP address..." value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div style="min-width: 160px;">
            <select name="provider" class="admin-select" onchange="this.form.submit()">
                <option value="all" <?php echo $provider_filter === 'all' ? 'selected' : ''; ?>>All Providers</option>
                <option value="google" <?php echo $provider_filter === 'google' ? 'selected' : ''; ?>>Google Users</option>
                <option value="github" <?php echo $provider_filter === 'github' ? 'selected' : ''; ?>>GitHub Users</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn-admin btn-admin-primary">
                <i class="fa-solid fa-magnifying-glass"></i> Filter
            </button>
            <?php if ($search !== '' || $provider_filter !== 'all'): ?>
                <a href="?tab=users" class="btn-admin btn-admin-secondary" title="Reset Filters">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="admin-card" style="overflow: hidden; padding: 0;">
    <div style="overflow-x: auto;">
        <table class="admin-table" style="margin: 0; width: 100%;">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>User Profile</th>
                    <th>Provider</th>
                    <th>Logins</th>
                    <th>IP &amp; Location</th>
                    <th>Joined</th>
                    <th>Last Active</th>
                    <th style="text-align: right;">Activity Forensics</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users_list)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem 1rem; color: var(--admin-text-muted);">
                            <i class="fa-solid fa-user-slash fa-3x" style="opacity: 0.4; margin-bottom: 0.75rem;"></i>
                            <div>No registered users found matching your criteria.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users_list as $u): 
                        $avatar = !empty($u['avatar_url']) ? $u['avatar_url'] : 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($u['name'] ?? 'User');
                    ?>
                    <tr>
                        <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.82rem; color: var(--admin-text-muted);">
                            #<?php echo $u['id']; ?>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1px solid var(--admin-border); flex-shrink: 0;" onerror="this.src='https://api.dicebear.com/7.x/initials/svg?seed=U';">
                                <div style="min-width: 0;">
                                    <div style="font-weight: 600; font-size: 0.95rem; color: var(--admin-text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($u['name'] ?: 'Physics Student'); ?>
                                    </div>
                                    <div style="font-size: 0.82rem; color: var(--admin-text-muted); font-family: 'JetBrains Mono', monospace;">
                                        <?php echo htmlspecialchars($u['email']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($u['provider'] === 'google'): ?>
                                <span class="admin-badge" style="background: rgba(234, 67, 53, 0.12); color: #f87171; border: 1px solid rgba(234, 67, 53, 0.25);">
                                    <i class="fa-brands fa-google"></i> Google
                                </span>
                            <?php else: ?>
                                <span class="admin-badge" style="background: rgba(255, 255, 255, 0.1); color: #f8fafc; border: 1px solid rgba(255, 255, 255, 0.2);">
                                    <i class="fa-brands fa-github"></i> GitHub
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="admin-badge admin-badge-cyan" style="font-weight: 700;">
                                <?php echo $u['login_count']; ?> logins
                            </span>
                        </td>
                        <td>
                            <div style="font-size: 0.85rem; font-family: 'JetBrains Mono', monospace; color: var(--admin-text-main);">
                                <?php echo htmlspecialchars($u['ip_address'] ?? '127.0.0.1'); ?>
                            </div>
                            <div style="font-size: 0.78rem; color: var(--admin-text-muted);">
                                <?php echo htmlspecialchars($u['country'] ?? 'Unknown'); ?>
                            </div>
                        </td>
                        <td style="font-size: 0.84rem; color: var(--admin-text-muted);">
                            <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                        </td>
                        <td>
                            <div style="font-size: 0.86rem; font-weight: 600; color: var(--admin-text-main);">
                                <?php echo time_elapsed_string($u['last_login_at']); ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--admin-text-muted);">
                                <?php echo date('H:i', strtotime($u['last_login_at'])); ?>
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <a href="?tab=telemetry&user_id=<?php echo $u['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm" title="View all tracked events for this user">
                                <i class="fa-solid fa-list-check"></i> Events (<?php echo (int)($u['activity_count'] ?? 0); ?>)
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<!-- ========================================================================= -->
<!-- TAB 2: TELEMETRY ACTIVITY STREAM                                          -->
<!-- ========================================================================= -->

<?php if ($inspected_user): ?>
    <!-- Inspected User Focus Banner -->
    <div class="admin-card" style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.12), rgba(99, 102, 241, 0.08)); border-color: rgba(14, 165, 233, 0.3); margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <img src="<?php echo htmlspecialchars($inspected_user['avatar_url'] ?: 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($inspected_user['name'])); ?>" style="width: 48px; height: 48px; border-radius: 50%; border: 2px solid var(--admin-accent);">
            <div>
                <div style="font-size: 1.15rem; font-weight: 700; color: var(--admin-text-main);">
                    <?php echo htmlspecialchars($inspected_user['name']); ?>
                </div>
                <div style="font-size: 0.85rem; color: var(--admin-text-muted); font-family: 'JetBrains Mono', monospace;">
                    <?php echo htmlspecialchars($inspected_user['email']); ?> &bull; 
                    Provider: <strong><?php echo ucfirst($inspected_user['provider']); ?></strong> &bull;
                    Logins: <strong><?php echo $inspected_user['login_count']; ?></strong>
                </div>
            </div>
        </div>
        <a href="?tab=telemetry" class="btn-admin btn-admin-secondary btn-admin-sm">
            <i class="fa-solid fa-xmark"></i> Clear User Filter
        </a>
    </div>
<?php endif; ?>

<!-- Filter Toolbar -->
<div class="admin-card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
        <input type="hidden" name="tab" value="telemetry">
        <?php if ($selected_user_id > 0): ?>
            <input type="hidden" name="user_id" value="<?php echo $selected_user_id; ?>">
        <?php endif; ?>

        <div style="flex: 1; min-width: 240px;">
            <input type="text" name="search" class="admin-input" placeholder="Search by student, page title, or IP..." value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div style="min-width: 170px;">
            <select name="action" class="admin-select" onchange="this.form.submit()">
                <option value="all" <?php echo $action_filter === 'all' ? 'selected' : ''; ?>>All Actions</option>
                <option value="execute_code" <?php echo $action_filter === 'execute_code' ? 'selected' : ''; ?>>Execute Code</option>
                <option value="export_ipynb" <?php echo $action_filter === 'export_ipynb' ? 'selected' : ''; ?>>Export .ipynb</option>
                <option value="export_py" <?php echo $action_filter === 'export_py' ? 'selected' : ''; ?>>Export .py</option>
                <option value="copy_code" <?php echo $action_filter === 'copy_code' ? 'selected' : ''; ?>>Copy Code</option>
                <option value="view_topic" <?php echo $action_filter === 'view_topic' ? 'selected' : ''; ?>>View Topic</option>
                <option value="login" <?php echo $action_filter === 'login' ? 'selected' : ''; ?>>Logins</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn-admin btn-admin-primary">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            <?php if ($search !== '' || $action_filter !== 'all' || $selected_user_id > 0): ?>
                <a href="?tab=telemetry" class="btn-admin btn-admin-secondary" title="Reset Filters">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Telemetry Event Stream Table -->
<div class="admin-card" style="overflow: hidden; padding: 0;">
    <div style="overflow-x: auto;">
        <table class="admin-table" style="margin: 0; width: 100%;">
            <thead>
                <tr>
                    <th style="width: 130px;">Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Topic / Page</th>
                    <th>Forensic Details</th>
                    <th>Client IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($telemetry_list)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem 1rem; color: var(--admin-text-muted);">
                            <i class="fa-solid fa-list-check fa-3x" style="opacity: 0.4; margin-bottom: 0.75rem;"></i>
                            <div>No telemetry logs found matching your criteria.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($telemetry_list as $ev): ?>
                    <tr>
                        <td>
                            <div style="font-size: 0.85rem; font-weight: 600; color: var(--admin-text-main);">
                                <?php echo date('H:i:s', strtotime($ev['created_at'])); ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--admin-text-muted);">
                                <?php echo time_elapsed_string($ev['created_at']); ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($ev['user_name'])): ?>
                                <a href="?tab=telemetry&user_id=<?php echo $ev['user_id']; ?>" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: var(--admin-surface-2); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; color: var(--admin-accent); font-weight: 700;">
                                        <?php echo strtoupper(substr($ev['user_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-size: 0.88rem; font-weight: 600; color: var(--admin-text-main);">
                                            <?php echo htmlspecialchars($ev['user_name']); ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--admin-text-muted); font-family: 'JetBrains Mono', monospace;">
                                            <?php echo htmlspecialchars($ev['user_email']); ?>
                                        </div>
                                    </div>
                                </a>
                            <?php else: ?>
                                <span style="font-size: 0.85rem; color: var(--admin-text-muted);">Guest / Anonymous</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo get_action_badge_html($ev['action']); ?>
                        </td>
                        <td>
                            <?php if (!empty($ev['page_title'])): ?>
                                <div style="font-size: 0.88rem; font-weight: 600; color: var(--admin-text-main);">
                                    <?php echo htmlspecialchars($ev['page_title']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($ev['page_url'])): ?>
                                <div style="font-size: 0.76rem; color: var(--admin-text-muted); max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <a href="<?php echo htmlspecialchars($ev['page_url']); ?>" target="_blank" style="color: var(--admin-text-muted); text-decoration: none;">
                                        <?php echo htmlspecialchars($ev['page_url']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($ev['program_id'])): ?>
                                <span class="admin-badge admin-badge-cyan" style="font-size: 0.74rem;">
                                    Program #<?php echo $ev['program_id']; ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($ev['details'])): ?>
                                <span style="font-size: 0.76rem; color: var(--admin-text-muted); font-family: 'JetBrains Mono', monospace; display: block; margin-top: 0.2rem; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($ev['details']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-family: 'JetBrains Mono', monospace; font-size: 0.82rem; color: var(--admin-text-main);">
                                <?php echo htmlspecialchars($ev['ip_address'] ?? '127.0.0.1'); ?>
                            </div>
                            <div style="font-size: 0.74rem; color: var(--admin-text-muted); max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($ev['user_agent'] ?? ''); ?>">
                                <?php echo htmlspecialchars($ev['user_agent'] ?? 'Browser'); ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_bottom.php'; ?>
