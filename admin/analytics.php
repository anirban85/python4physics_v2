<?php
/**
 * Python4Physics - Admin Traffic & Telemetry Intelligence Dashboard
 * Advanced, professional, and user-friendly insights for demography, page visits,
 * engagement duration, and computational code execution telemetry.
 * ALL DATES AND TIMES EXCLUSIVELY IN INDIAN STANDARD TIME (IST, UTC+05:30).
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../include/analytics_db.php';

require_admin_login();

// Enforce Indian Standard Time (IST) globally
date_default_timezone_set('Asia/Kolkata');

$page_title = "Traffic & Telemetry Intelligence Dashboard";

// Parse Date / Range Query Parameters
$filterInput = [];
if (!empty($_GET['date'])) {
    $filterInput['date'] = trim($_GET['date']);
} elseif (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
    $filterInput['from_date'] = trim($_GET['from_date']);
    $filterInput['to_date'] = trim($_GET['to_date']);
} elseif (!empty($_GET['preset'])) {
    $filterInput['preset'] = trim($_GET['preset']);
} elseif (isset($_GET['days'])) {
    $filterInput['days'] = (int)$_GET['days'];
} else {
    // Default to last 30 days if no filter specified
    $filterInput['preset'] = '30days';
}

$include_bots = isset($_GET['bots']) && $_GET['bots'] === '1';
$filterInput['include_bots'] = $include_bots;

// Fetch Comprehensive Analytics Dataset in IST
$analytics = get_analytics_dashboard_data($conn, $filterInput, $include_bots);

$filter             = $analytics['filter'];
$kpis               = $analytics['kpis'];
$execStats          = $analytics['execStats'];
$hourlyBreakdown    = $analytics['hourlyBreakdown'];
$peakHour           = $analytics['peakHour'];
$peakHourLabel      = $analytics['peakHourLabel'];
$dailyTrends        = $analytics['dailyTrends'];
$dayOfWeekBreakdown = $analytics['dayOfWeekBreakdown'];
$peakDay            = $analytics['peakDay'];
$topCountries       = $analytics['topCountries'];
$topCities          = $analytics['topCities'];
$deviceBreakdown    = $analytics['deviceBreakdown'];
$osBreakdown        = $analytics['osBreakdown'];
$browserBreakdown   = $analytics['browserBreakdown'];
$screenResolutions  = $analytics['screenResolutions'];
$topPages           = $analytics['topPages'];
$moduleBreakdown    = $analytics['moduleBreakdown'];
$topReferrers       = $analytics['topReferrers'];
$topPresets         = $analytics['topPresets'];
$activeNow          = $analytics['activeNow'];
$recentVisits       = $analytics['recentVisits'];
$recentEvents       = $analytics['recentEvents'];

$totalViews = max(1, (int)($kpis['total_views'] ?? 0));
$totalExec = (int)($execStats['total_executions'] ?? 0);
$successRate = $execStats['success_rate'] ?? 100.0;
$bounceRate = $kpis['bounce_rate'] ?? 0.0;

require_once __DIR__ . '/layout_top.php';
?>

<!-- Chart.js 4.4 from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
/* ====================================================================
   ADVANCED ANALYTICS DASHBOARD STYLING (IST EDITION)
   ==================================================================== */

:root {
    --ist-gold: #f59e0b;
    --ist-cyan: #06b6d4;
    --ist-blue: #0ea5e9;
    --ist-emerald: #10b981;
    --ist-purple: #8b5cf6;
}

.analytics-hero-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--admin-border);
}

.analytics-live-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.35);
    color: #34d399;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.pulse-dot {
    width: 9px;
    height: 9px;
    background: #10b981;
    border-radius: 50%;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    animation: pulseGlow 1.8s infinite;
}

@keyframes pulseGlow {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

/* Futuristic Live IST Clock Widget */
.ist-clock-widget {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(14, 165, 233, 0.1);
    border: 1px solid rgba(14, 165, 233, 0.3);
    color: #38bdf8;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 0.84rem;
    font-weight: 700;
    font-family: 'JetBrains Mono', monospace;
    letter-spacing: 0.02em;
}

/* Master Filter Panel */
.analytics-filter-control-panel {
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.75rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.filter-row-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.date-pill-group {
    display: inline-flex;
    background: var(--admin-surface-2);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    padding: 3px;
    gap: 2px;
    flex-wrap: wrap;
}

.date-pill-btn {
    background: transparent;
    border: none;
    color: var(--admin-text-muted);
    padding: 6px 11px;
    font-size: 0.78rem;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.date-pill-btn:hover {
    color: var(--admin-text-main);
    background: rgba(255, 255, 255, 0.08);
}

.date-pill-btn.active {
    background: var(--admin-accent);
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(14, 165, 233, 0.35);
}

.filter-row-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    padding-top: 0.85rem;
}

.custom-date-form-group {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    flex-wrap: wrap;
}

.ist-date-input {
    background: var(--admin-surface-2);
    border: 1px solid var(--admin-border);
    color: var(--admin-text-main);
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 0.82rem;
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s ease;
}

.ist-date-input:focus {
    border-color: var(--admin-accent);
    box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.2);
}

/* Active Filter Summary Banner */
.active-range-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    background: linear-gradient(90deg, rgba(14, 165, 233, 0.08) 0%, rgba(99, 102, 241, 0.08) 100%);
    border: 1px solid rgba(14, 165, 233, 0.25);
    border-radius: 8px;
    padding: 0.65rem 1rem;
    margin-bottom: 2rem;
    font-size: 0.85rem;
}

/* Tabbed Navigation */
.analytics-tab-bar {
    display: flex;
    gap: 0.35rem;
    border-bottom: 1px solid var(--admin-border);
    margin-bottom: 1.75rem;
    overflow-x: auto;
    padding-bottom: 0.15rem;
    scrollbar-width: thin;
}

.analytics-tab-btn {
    background: transparent;
    border: none;
    color: var(--admin-text-muted);
    padding: 0.8rem 1.15rem;
    font-size: 0.88rem;
    font-weight: 700;
    border-radius: 8px 8px 0 0;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    position: relative;
    white-space: nowrap;
    transition: all 0.2s ease;
}

.analytics-tab-btn:hover {
    color: var(--admin-text-main);
    background: rgba(255, 255, 255, 0.03);
}

.analytics-tab-btn.active {
    color: var(--admin-accent);
}

.analytics-tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--admin-accent);
    border-radius: 3px 3px 0 0;
}

.analytics-tab-content {
    display: none;
    animation: fadeInTab 0.2s ease-in-out;
}

.analytics-tab-content.active {
    display: block;
}

@keyframes fadeInTab {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Charts Containers */
.chart-box {
    position: relative;
    width: 100%;
    height: 360px;
}

.chart-box-donut {
    position: relative;
    width: 100%;
    height: 270px;
}

.chart-box-sm {
    position: relative;
    width: 100%;
    height: 260px;
}

/* Engine stat mini cards */
.engine-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.25rem;
    margin-bottom: 2rem;
}

.engine-card {
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: 0.85rem;
    padding: 1.15rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.2s, border-color 0.2s;
}

.engine-card:hover {
    transform: translateY(-2px);
    border-color: rgba(14, 165, 233, 0.4);
}

.engine-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}

/* Heatmap & Matrix styling */
.matrix-heat-pill {
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-block;
}

.matrix-heat-peak {
    background: rgba(239, 68, 68, 0.18);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.matrix-heat-high {
    background: rgba(245, 158, 11, 0.18);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.matrix-heat-mod {
    background: rgba(14, 165, 233, 0.15);
    color: #38bdf8;
    border: 1px solid rgba(14, 165, 233, 0.3);
}

.matrix-heat-low {
    background: rgba(148, 163, 184, 0.1);
    color: #94a3b8;
    border: 1px solid rgba(148, 163, 184, 0.2);
}

/* Search input */
.table-filter-input {
    background: var(--admin-surface-2);
    border: 1px solid var(--admin-border);
    color: var(--admin-text-main);
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    outline: none;
    width: 280px;
    transition: all 0.2s ease;
}
.table-filter-input:focus {
    border-color: var(--admin-accent);
}

/* KPI Card Glass Accent */
.kpi-accent-glow {
    position: absolute;
    top: -40px;
    right: -40px;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    opacity: 0.12;
    pointer-events: none;
    filter: blur(24px);
}

@media print {
    .admin-navbar, .analytics-filter-control-panel, .analytics-tab-bar, .btn-admin, .table-filter-input {
        display: none !important;
    }
    .analytics-tab-content {
        display: block !important;
        margin-bottom: 2rem;
    }
}
</style>

<!-- Top Dashboard Header with Live Time and Status -->
<div class="analytics-hero-header">
    <div>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 0.4rem; flex-wrap: wrap;">
            <h1 class="admin-page-title" style="margin: 0;">
                <i class="fa-solid fa-chart-line" style="color: var(--admin-accent); margin-right: 6px;"></i> Traffic &amp; Telemetry Intelligence
            </h1>
            <div class="analytics-live-pill">
                <span class="pulse-dot"></span>
                <span><?php echo number_format($activeNow); ?> Active Now (IST)</span>
            </div>
            <div class="ist-clock-widget" title="Current Indian Standard Time (IST, UTC+05:30)">
                <i class="fa-regular fa-clock"></i>
                <span id="liveIstClock"><?php echo date('d M Y, h:i:s A'); ?> IST</span>
            </div>
        </div>
        <p class="admin-page-subtitle">
            First-party audience demography, active session duration, interactive physics laboratory execution telemetry, and intraday velocity in Indian Standard Time (IST).
        </p>
    </div>

    <!-- Quick Action Buttons -->
    <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
        <!-- Bot Filter Toggle -->
        <a href="?preset=<?php echo urlencode($filter['preset']); ?>&bots=<?php echo $include_bots ? '0' : '1'; ?><?php echo !empty($filter['date']) ? '&date=' . urlencode($filter['date']) : ''; ?><?php echo !empty($_GET['from_date']) ? '&from_date=' . urlencode($_GET['from_date']) . '&to_date=' . urlencode($_GET['to_date']) : ''; ?>" class="btn-admin btn-admin-secondary btn-admin-sm" title="Toggle Search Crawlers / Bots">
            <i class="fa-solid fa-robot"></i> <?php echo $include_bots ? 'Crawlers Included' : 'Human Traffic Only'; ?>
        </a>

        <!-- Export CSV & JSON -->
        <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" onclick="exportAnalyticsReport('csv')" title="Download CSV Audit Report in IST">
            <i class="fa-solid fa-file-csv"></i> Export CSV
        </button>
        <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" onclick="exportAnalyticsReport('json')" title="Download JSON Dataset in IST">
            <i class="fa-solid fa-file-code"></i> JSON
        </button>
        <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" onclick="window.print()" title="Print / Save PDF Report">
            <i class="fa-solid fa-print"></i> Print
        </button>
        <button type="button" class="btn-admin btn-admin-primary btn-admin-sm" onclick="location.reload()" title="Refresh telemetry">
            <i class="fa-solid fa-rotate"></i>
        </button>
    </div>
</div>

<!-- ====================================================================
     MASTER DATE & RANGE FILTER CONTROL PANEL (STRICTLY IN IST)
     ==================================================================== -->
<div class="analytics-filter-control-panel">
    <!-- Row 1: Fast Presets -->
    <div class="filter-row-top">
        <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 0.82rem; font-weight: 700; color: var(--admin-text-muted); text-transform: uppercase; letter-spacing: 0.04em;">
                <i class="fa-regular fa-calendar-days" style="color: var(--admin-accent);"></i> Presets (IST):
            </span>
            <div class="date-pill-group">
                <a href="?preset=today<?php echo $include_bots ? '&bots=1' : ''; ?>" class="date-pill-btn <?php echo $filter['preset'] === 'today' ? 'active' : ''; ?>">Today</a>
                <a href="?preset=yesterday<?php echo $include_bots ? '&bots=1' : ''; ?>" class="date-pill-btn <?php echo $filter['preset'] === 'yesterday' ? 'active' : ''; ?>">Yesterday</a>
                <a href="?preset=7days<?php echo $include_bots ? '&bots=1' : ''; ?>" class="date-pill-btn <?php echo $filter['preset'] === '7days' ? 'active' : ''; ?>">Last 7 Days</a>
                <a href="?preset=14days<?php echo $include_bots ? '&bots=1' : ''; ?>" class="date-pill-btn <?php echo $filter['preset'] === '14days' ? 'active' : ''; ?>">Last 14 Days</a>
                <a href="?preset=30days<?php echo $include_bots ? '&bots=1' : ''; ?>" class="date-pill-btn <?php echo $filter['preset'] === '30days' ? 'active' : ''; ?>">Last 30 Days</a>
                <a href="?preset=this_month<?php echo $include_bots ? '&bots=1' : ''; ?>" class="date-pill-btn <?php echo $filter['preset'] === 'this_month' ? 'active' : ''; ?>">This Month</a>
                <a href="?preset=last_month<?php echo $include_bots ? '&bots=1' : ''; ?>" class="date-pill-btn <?php echo $filter['preset'] === 'last_month' ? 'active' : ''; ?>">Last Month</a>
                <a href="?preset=90days<?php echo $include_bots ? '&bots=1' : ''; ?>" class="date-pill-btn <?php echo $filter['preset'] === '90days' ? 'active' : ''; ?>">Last 90 Days</a>
                <a href="?preset=this_year<?php echo $include_bots ? '&bots=1' : ''; ?>" class="date-pill-btn <?php echo $filter['preset'] === 'this_year' ? 'active' : ''; ?>">This Year</a>
                <a href="?preset=all<?php echo $include_bots ? '&bots=1' : ''; ?>" class="date-pill-btn <?php echo $filter['preset'] === 'all' ? 'active' : ''; ?>">All Time</a>
            </div>
        </div>
    </div>

    <!-- Row 2: Date-wise (Specific Single Date) & Date Ranges Form -->
    <div class="filter-row-bottom">
        <!-- Option 1: Pick a Single Specific Date (Date-wise) -->
        <form method="GET" action="" class="custom-date-form-group">
            <span style="font-size: 0.82rem; font-weight: 700; color: var(--admin-text-muted);">
                <i class="fa-solid fa-calendar-day" style="color: #38bdf8;"></i> Specific Date (IST):
            </span>
            <input type="date" name="date" class="ist-date-input" value="<?php echo htmlspecialchars($filter['selected_date']); ?>" max="<?php echo date('Y-m-d'); ?>" required>
            <?php if ($include_bots): ?><input type="hidden" name="bots" value="1"><?php endif; ?>
            <button type="submit" class="btn-admin btn-admin-secondary btn-admin-sm">
                View Day
            </button>
        </form>

        <!-- Option 2: Pick Custom Date Range (Date ranges) -->
        <form method="GET" action="" class="custom-date-form-group">
            <span style="font-size: 0.82rem; font-weight: 700; color: var(--admin-text-muted);">
                <i class="fa-solid fa-calendar-week" style="color: #818cf8;"></i> Date Range (IST):
            </span>
            <input type="date" name="from_date" class="ist-date-input" value="<?php echo htmlspecialchars($filter['from_date']); ?>" max="<?php echo date('Y-m-d'); ?>" required>
            <span style="color: var(--admin-text-muted); font-size: 0.8rem;">to</span>
            <input type="date" name="to_date" class="ist-date-input" value="<?php echo htmlspecialchars($filter['to_date']); ?>" max="<?php echo date('Y-m-d'); ?>" required>
            <?php if ($include_bots): ?><input type="hidden" name="bots" value="1"><?php endif; ?>
            <button type="submit" class="btn-admin btn-admin-primary btn-admin-sm">
                Apply Range
            </button>
        </form>
    </div>
</div>

<!-- Active Filter Status Indicator Banner -->
<div class="active-range-banner">
    <div>
        <i class="fa-solid fa-circle-check" style="color: #34d399; margin-right: 6px;"></i>
        <span>Active Scope: <strong><?php echo htmlspecialchars($filter['label']); ?></strong></span>
        <span style="color: var(--admin-text-muted); margin-left: 8px;">
            (<?php echo date('d M Y H:i:s', strtotime($filter['start'])); ?> IST — <?php echo date('d M Y H:i:s', strtotime($filter['end'])); ?> IST)
        </span>
    </div>
    <div style="display: flex; align-items: center; gap: 14px;">
        <span style="color: #fbbf24; font-size: 0.82rem; font-weight: 600;">
            <i class="fa-solid fa-bolt"></i> Peak Velocity: <?php echo htmlspecialchars($peakHourLabel); ?>
        </span>
        <span class="admin-badge admin-badge-cyan" style="font-size: 0.72rem;">
            Timezone: Asia/Kolkata (UTC+05:30)
        </span>
    </div>
</div>

<!-- ====================================================================
     EXECUTIVE 8-KPI METRIC GRID
     ==================================================================== -->
<div class="admin-grid-stats" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 2rem;">
    <!-- 1. Total Impressions -->
    <div class="admin-stat-card">
        <div class="kpi-accent-glow" style="background: #38bdf8;"></div>
        <i class="fa-solid fa-eye admin-stat-icon" style="color: #38bdf8;"></i>
        <div class="admin-stat-label">Total Impressions</div>
        <div class="admin-stat-value" style="color: #38bdf8;"><?php echo number_format($kpis['total_views'] ?? 0); ?></div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Across <?php echo number_format($kpis['unique_sessions'] ?? 0); ?> user sessions &bull; <?php echo $kpis['views_per_session']; ?>/sess
        </div>
    </div>

    <!-- 2. Unique Audience -->
    <div class="admin-stat-card">
        <div class="kpi-accent-glow" style="background: #6366f1;"></div>
        <i class="fa-solid fa-users admin-stat-icon" style="color: #6366f1;"></i>
        <div class="admin-stat-label">Unique Visitors</div>
        <div class="admin-stat-value" style="color: #818cf8;"><?php echo number_format($kpis['unique_visitors'] ?? 0); ?></div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            <?php echo number_format($kpis['returning_visitors']); ?> Returning (<?php echo $kpis['returning_visitor_pct']; ?>%) &bull; Salted IP
        </div>
    </div>

    <!-- 3. Active Engagement Time -->
    <div class="admin-stat-card">
        <div class="kpi-accent-glow" style="background: #10b981;"></div>
        <i class="fa-solid fa-stopwatch admin-stat-icon" style="color: #10b981;"></i>
        <div class="admin-stat-label">Avg Engagement</div>
        <div class="admin-stat-value" style="color: #34d399;"><?php echo format_seconds_duration($kpis['avg_duration'] ?? 0); ?></div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Total: <?php echo format_seconds_duration($kpis['total_duration'] ?? 0); ?> active reading
        </div>
    </div>

    <!-- 4. Scroll Depth -->
    <div class="admin-stat-card">
        <div class="kpi-accent-glow" style="background: #06b6d4;"></div>
        <i class="fa-solid fa-arrows-up-down admin-stat-icon" style="color: #06b6d4;"></i>
        <div class="admin-stat-label">Avg Scroll Depth</div>
        <div class="admin-stat-value" style="color: #22d3ee;"><?php echo round($kpis['avg_scroll'] ?? 0); ?>%</div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            <?php echo number_format($kpis['deep_reads']); ?> Deep Reads (&gt;75% read)
        </div>
    </div>

    <!-- 5. Bounce Rate -->
    <div class="admin-stat-card">
        <div class="kpi-accent-glow" style="background: #ec4899;"></div>
        <i class="fa-solid fa-arrow-right-from-bracket admin-stat-icon" style="color: #ec4899;"></i>
        <div class="admin-stat-label">Bounce Rate</div>
        <div class="admin-stat-value" style="color: #f472b6;"><?php echo $bounceRate; ?>%</div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            <?php echo round(100 - $bounceRate, 1); ?>% Engaged reading sessions
        </div>
    </div>

    <!-- 6. Code & Lab Executions -->
    <div class="admin-stat-card">
        <div class="kpi-accent-glow" style="background: #f59e0b;"></div>
        <i class="fa-solid fa-bolt admin-stat-icon" style="color: #f59e0b;"></i>
        <div class="admin-stat-label">Lab Executions</div>
        <div class="admin-stat-value" style="color: #fbbf24;"><?php echo number_format($totalExec); ?></div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Python &bull; SPICE &bull; Arduino &bull; Plots
        </div>
    </div>

    <!-- 7. Simulation Reliability -->
    <div class="admin-stat-card">
        <div class="kpi-accent-glow" style="background: #a855f7;"></div>
        <i class="fa-solid fa-circle-check admin-stat-icon" style="color: #a855f7;"></i>
        <div class="admin-stat-label">Solver Reliability</div>
        <div class="admin-stat-value" style="color: #c084fc;"><?php echo $successRate; ?>%</div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Avg Latency: <?php echo round($execStats['avg_latency'] ?? 0); ?> ms
        </div>
    </div>

    <!-- 8. Peak Traffic Velocity -->
    <div class="admin-stat-card">
        <div class="kpi-accent-glow" style="background: #14b8a6;"></div>
        <i class="fa-solid fa-gauge-high admin-stat-icon" style="color: #14b8a6;"></i>
        <div class="admin-stat-label">Peak Velocity (IST)</div>
        <div class="admin-stat-value" style="color: #2dd4bf; font-size: 1.35rem;"><?php echo date('h A', strtotime("2026-01-01 {$peakHour}:00:00")); ?> IST</div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Peak Day: <strong><?php echo htmlspecialchars($peakDay); ?></strong>
        </div>
    </div>
</div>

<!-- ====================================================================
     TABBED NAVIGATION BAR (8 ADVANCED MODULES)
     ==================================================================== -->
<div class="analytics-tab-bar">
    <button type="button" class="analytics-tab-btn active" onclick="switchAnalyticsTab('tab-overview', this)">
        <i class="fa-solid fa-chart-area"></i> Overview &amp; Trends
    </button>
    <button type="button" class="analytics-tab-btn" onclick="switchAnalyticsTab('tab-hourly', this)">
        <i class="fa-solid fa-clock"></i> Hourly Matrix (IST)
    </button>
    <button type="button" class="analytics-tab-btn" onclick="switchAnalyticsTab('tab-channels', this)">
        <i class="fa-solid fa-arrow-trend-up"></i> Acquisition &amp; Referrers
    </button>
    <button type="button" class="analytics-tab-btn" onclick="switchAnalyticsTab('tab-demography', this)">
        <i class="fa-solid fa-earth-asia"></i> Demography &amp; Cities
    </button>
    <button type="button" class="analytics-tab-btn" onclick="switchAnalyticsTab('tab-devices', this)">
        <i class="fa-solid fa-laptop-code"></i> Devices &amp; Tech Stack
    </button>
    <button type="button" class="analytics-tab-btn" onclick="switchAnalyticsTab('tab-pages', this)">
        <i class="fa-solid fa-file-waveform"></i> Page-by-Page Performance
    </button>
    <button type="button" class="analytics-tab-btn" onclick="switchAnalyticsTab('tab-executions', this)">
        <i class="fa-solid fa-microchip"></i> Computational Labs
    </button>
    <button type="button" class="analytics-tab-btn" onclick="switchAnalyticsTab('tab-realtime', this)">
        <i class="fa-solid fa-satellite-dish"></i> Live Stream Feed
    </button>
</div>

<!-- ====================================================================
     TAB 1: OVERVIEW & TEMPORAL TRENDS
     ==================================================================== -->
<div id="tab-overview" class="analytics-tab-content active">
    <!-- Primary Traffic Timeline Chart Card -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">
                    <i class="fa-solid fa-chart-line" style="color: var(--admin-accent);"></i> 
                    <?php echo $filter['is_single_day'] ? '24-Hour Intraday Traffic Curve (IST)' : 'Daily Traffic & Audience Volume Over Time (IST)'; ?>
                </h2>
                <p class="admin-page-subtitle" style="font-size: 0.85rem; margin-top: 0.2rem;">
                    <?php echo $filter['is_single_day'] ? 'Hour-by-hour telemetry from 00:00 IST to 23:59 IST' : 'Daily timeline of page impressions, unique sessions, and computational simulations'; ?> &bull; All dates strictly in Indian Standard Time.
                </p>
            </div>
            <div style="font-size: 0.8rem; color: var(--admin-text-muted);">
                Scope: <strong><?php echo htmlspecialchars($filter['label']); ?></strong>
            </div>
        </div>
        <div class="chart-box">
            <canvas id="trafficTrendChart"></canvas>
        </div>
    </div>

    <!-- Laboratory Engine Breakdown Mini Grid -->
    <div class="engine-grid">
        <!-- Python -->
        <div class="engine-card">
            <div class="engine-icon" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                <i class="fa-brands fa-python"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--admin-text-muted); font-weight: 700;">Python Pyodide</div>
                <div style="font-size: 1.4rem; font-weight: 800; color: #fff;"><?php echo number_format($execStats['python_runs'] ?? 0); ?> <span style="font-size: 0.78rem; color: var(--admin-text-muted); font-weight: 500;">runs</span></div>
                <div style="font-size: 0.74rem; color: #38bdf8;">In-browser WebAssembly engine</div>
            </div>
        </div>

        <!-- Circuit Simulator -->
        <div class="engine-card">
            <div class="engine-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fa-solid fa-bolt"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--admin-text-muted); font-weight: 700;">Circuit Simulator</div>
                <div style="font-size: 1.4rem; font-weight: 800; color: #fff;"><?php echo number_format($execStats['circuit_runs'] ?? 0); ?> <span style="font-size: 0.78rem; color: var(--admin-text-muted); font-weight: 500;">solves</span></div>
                <div style="font-size: 0.74rem; color: #10b981;">SPICE MNA numerical solver</div>
            </div>
        </div>

        <!-- Arduino Lab -->
        <div class="engine-card">
            <div class="engine-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
                <i class="fa-solid fa-microchip"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--admin-text-muted); font-weight: 700;">Arduino Studio</div>
                <div style="font-size: 1.4rem; font-weight: 800; color: #fff;"><?php echo number_format($execStats['arduino_runs'] ?? 0); ?> <span style="font-size: 0.78rem; color: var(--admin-text-muted); font-weight: 500;">sims</span></div>
                <div style="font-size: 0.74rem; color: #06b6d4;">C++ firmware &amp; breadboard</div>
            </div>
        </div>

        <!-- GNUplot & LaTeX -->
        <div class="engine-card">
            <div class="engine-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fa-solid fa-square-root-variable"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--admin-text-muted); font-weight: 700;">Plots &amp; Formulas</div>
                <div style="font-size: 1.4rem; font-weight: 800; color: #fff;"><?php echo number_format(($execStats['gnuplot_runs'] ?? 0) + ($execStats['latex_runs'] ?? 0)); ?> <span style="font-size: 0.78rem; color: var(--admin-text-muted); font-weight: 500;">renders</span></div>
                <div style="font-size: 0.74rem; color: #f59e0b;">GNUplot SVG &amp; KaTeX math</div>
            </div>
        </div>
    </div>

    <!-- Secondary Split: Intraday Hourly Distribution & Day of Week -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem;">
        <!-- Intraday Distribution (00:00 to 23:00 IST) -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="fa-solid fa-clock" style="color: var(--admin-accent);"></i> Hourly Traffic Velocity Profile (00 to 23 IST)</h2>
                <span class="admin-badge admin-badge-cyan">Peak: <?php echo date('h A', strtotime("2026-01-01 {$peakHour}:00:00")); ?> IST</span>
            </div>
            <div class="chart-box-sm">
                <canvas id="intradayHourlyChart"></canvas>
            </div>
        </div>

        <!-- Day of Week Distribution -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="fa-solid fa-calendar-week" style="color: var(--admin-accent);"></i> Day-of-Week Engagement Curve</h2>
                <span class="admin-badge admin-badge-green">Peak: <?php echo htmlspecialchars($peakDay); ?></span>
            </div>
            <div class="chart-box-sm">
                <canvas id="dayOfWeekChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ====================================================================
     TAB 2: HOURLY MATRIX (IST)
     ==================================================================== -->
<div id="tab-hourly" class="analytics-tab-content">
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title"><i class="fa-solid fa-table-cells-large" style="color: var(--admin-accent);"></i> Complete 24-Hour Indian Standard Time (IST) Matrix</h2>
                <p class="admin-page-subtitle" style="font-size: 0.85rem; margin-top: 0.2rem;">
                    Hour-by-hour breakdown for <?php echo htmlspecialchars($filter['label']); ?> from 00:00:00 IST to 23:59:59 IST.
                </p>
            </div>
            <div>
                <span class="admin-badge admin-badge-cyan">All 24 Hour Slots &bull; UTC+05:30</span>
            </div>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 180px;">Time Slot (IST)</th>
                        <th>Standard 12-Hour</th>
                        <th style="text-align: right;">Page Impressions</th>
                        <th style="text-align: right;">Unique Sessions</th>
                        <th style="text-align: right;">Lab Solves</th>
                        <th style="text-align: right;">Traffic Share</th>
                        <th style="text-align: center; width: 140px;">Velocity Tier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $maxViewsInSlot = 1;
                    foreach ($hourlyBreakdown as $hb) {
                        if ($hb['views'] > $maxViewsInSlot) $maxViewsInSlot = $hb['views'];
                    }

                    foreach ($hourlyBreakdown as $hb): 
                        $ratio = $maxViewsInSlot > 0 ? ($hb['views'] / $maxViewsInSlot) : 0;
                        if ($hb['hour'] === $peakHour && $hb['views'] > 0) {
                            $heatClass = 'matrix-heat-peak';
                            $heatLabel = '🔥 Peak Velocity';
                        } elseif ($ratio >= 0.65) {
                            $heatClass = 'matrix-heat-high';
                            $heatLabel = '⚡ High Surge';
                        } elseif ($ratio >= 0.25) {
                            $heatClass = 'matrix-heat-mod';
                            $heatLabel = '🟢 Moderate';
                        } else {
                            $heatClass = 'matrix-heat-low';
                            $heatLabel = '⚪ Baseline';
                        }
                    ?>
                        <tr <?php if ($hb['hour'] === $peakHour && $hb['views'] > 0) echo 'style="background: rgba(245, 158, 11, 0.05);"'; ?>>
                            <td>
                                <strong style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: #fff;">
                                    <?php echo htmlspecialchars($hb['slot_label']); ?>
                                </strong>
                            </td>
                            <td style="color: var(--admin-text-muted); font-size: 0.85rem;">
                                <?php echo htmlspecialchars($hb['label12']); ?>
                            </td>
                            <td style="text-align: right; font-weight: 800; font-size: 0.95rem; color: #38bdf8;">
                                <?php echo number_format($hb['views']); ?>
                            </td>
                            <td style="text-align: right; font-weight: 600;">
                                <?php echo number_format($hb['sessions']); ?>
                            </td>
                            <td style="text-align: right; font-weight: 600; color: #fbbf24;">
                                <?php echo number_format($hb['executions']); ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                    <div style="width: 55px; height: 6px; background: rgba(255,255,255,0.08); border-radius: 3px; overflow: hidden;">
                                        <div style="width: <?php echo min(100, $hb['pct']); ?>%; height: 100%; background: #0ea5e9;"></div>
                                    </div>
                                    <span style="font-size: 0.8rem; font-family: monospace;"><?php echo $hb['pct']; ?>%</span>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="matrix-heat-pill <?php echo $heatClass; ?>">
                                    <?php echo $heatLabel; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ====================================================================
     TAB 3: ACQUISITION & REFERRERS
     ==================================================================== -->
<div id="tab-channels" class="analytics-tab-content">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 1.5rem;">
        <!-- Channels Table -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="fa-solid fa-arrow-trend-up" style="color: var(--admin-accent);"></i> Traffic Acquisition Channels</h2>
                <span class="admin-badge admin-badge-cyan"><?php echo count($topReferrers); ?> Detected Sources</span>
            </div>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Channel / Referrer</th>
                            <th style="text-align: right;">Visits</th>
                            <th style="text-align: right;">Sessions</th>
                            <th style="text-align: right;">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topReferrers)): ?>
                            <tr><td colspan="4" style="text-align: center; color: var(--admin-text-muted);">No referrers logged in this period.</td></tr>
                        <?php else: ?>
                            <?php foreach ($topReferrers as $ref): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <?php if (str_contains($ref['source'], 'google')): ?>
                                                <i class="fa-brands fa-google" style="color: #ea4335;"></i>
                                            <?php elseif (str_contains($ref['source'], 'bing')): ?>
                                                <i class="fa-brands fa-microsoft" style="color: #00a4ef;"></i>
                                            <?php elseif (str_contains($ref['source'], 'github')): ?>
                                                <i class="fa-brands fa-github"></i>
                                            <?php elseif (str_contains($ref['source'], 'stackexchange')): ?>
                                                <i class="fa-brands fa-stack-exchange" style="color: #f48024;"></i>
                                            <?php elseif (str_contains($ref['source'], 'Direct')): ?>
                                                <i class="fa-solid fa-bookmark" style="color: #f59e0b;"></i>
                                            <?php else: ?>
                                                <i class="fa-solid fa-link" style="color: var(--admin-accent);"></i>
                                            <?php endif; ?>
                                            <strong><?php echo htmlspecialchars($ref['source']); ?></strong>
                                        </div>
                                    </td>
                                    <td style="text-align: right; font-weight: 700;"><?php echo number_format($ref['count']); ?></td>
                                    <td style="text-align: right; color: var(--admin-text-muted);"><?php echo number_format($ref['sessions']); ?></td>
                                    <td style="text-align: right;">
                                        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 6px;">
                                            <div style="width: 50px; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden;">
                                                <div style="width: <?php echo min(100, $ref['share_pct']); ?>%; height: 100%; background: var(--admin-accent);"></div>
                                            </div>
                                            <span style="font-size: 0.78rem; color: var(--admin-text-muted);"><?php echo $ref['share_pct']; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Channels Donut Chart -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="fa-solid fa-chart-pie" style="color: var(--admin-accent);"></i> Source Composition</h2>
            </div>
            <div class="chart-box-donut">
                <canvas id="channelDonutChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ====================================================================
     TAB 4: DEMOGRAPHY & GEOLOCATION
     ==================================================================== -->
<div id="tab-demography" class="analytics-tab-content">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 1.5rem;">
        <!-- Top Countries Table -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="fa-solid fa-earth-asia" style="color: var(--admin-accent);"></i> International Demography</h2>
                <span class="admin-badge admin-badge-cyan"><?php echo count($topCountries); ?> Sovereign Nations</span>
            </div>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Country</th>
                            <th style="text-align: right;">Impressions</th>
                            <th style="text-align: right;">Sessions</th>
                            <th style="text-align: right;">Share</th>
                            <th style="text-align: right;">Avg Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topCountries)): ?>
                            <tr><td colspan="5" style="text-align: center; color: var(--admin-text-muted);">No geographic data recorded.</td></tr>
                        <?php else: ?>
                            <?php foreach ($topCountries as $c): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 1.25rem;"><?php echo $c['flag']; ?></span>
                                            <div>
                                                <strong><?php echo htmlspecialchars($c['country_name']); ?></strong>
                                                <div style="font-size: 0.72rem; color: var(--admin-text-muted); font-family: monospace;"><?php echo htmlspecialchars($c['country_code']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="text-align: right; font-weight: 700;"><?php echo number_format($c['views']); ?></td>
                                    <td style="text-align: right;"><?php echo number_format($c['sessions']); ?></td>
                                    <td style="text-align: right;">
                                        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 6px;">
                                            <div style="width: 45px; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden;">
                                                <div style="width: <?php echo min(100, $c['share_pct']); ?>%; height: 100%; background: #38bdf8;"></div>
                                            </div>
                                            <span style="font-size: 0.78rem;"><?php echo $c['share_pct']; ?>%</span>
                                        </div>
                                    </td>
                                    <td style="text-align: right; font-family: monospace; color: #34d399; font-size: 0.82rem;">
                                        <?php echo format_seconds_duration($c['avg_dur']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Cities Table -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="fa-solid fa-city" style="color: var(--admin-accent);"></i> Top Metropolitan Cities &amp; Hubs</h2>
                <span class="admin-badge admin-badge-green"><?php echo count($topCities); ?> Urban Regions</span>
            </div>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>City / Metro</th>
                            <th>Country</th>
                            <th style="text-align: right;">Impressions</th>
                            <th style="text-align: right;">Sessions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topCities)): ?>
                            <tr><td colspan="4" style="text-align: center; color: var(--admin-text-muted);">No urban city data recorded.</td></tr>
                        <?php else: ?>
                            <?php foreach ($topCities as $city): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <i class="fa-solid fa-location-dot" style="color: #ec4899;"></i>
                                            <strong><?php echo htmlspecialchars($city['city']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.85rem; color: var(--admin-text-muted);">
                                            <?php echo get_country_flag_emoji($city['country_code']); ?> <?php echo htmlspecialchars($city['country_name']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: #38bdf8;">
                                        <?php echo number_format($city['views']); ?>
                                    </td>
                                    <td style="text-align: right; color: var(--admin-text-muted);">
                                        <?php echo number_format($city['sessions']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ====================================================================
     TAB 5: DEVICES & TECH STACK
     ==================================================================== -->
<div id="tab-devices" class="analytics-tab-content">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
        <!-- 1. Device Form Factor Donut -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="fa-solid fa-laptop" style="color: var(--admin-accent);"></i> Form Factor</h2>
            </div>
            <div class="chart-box-donut">
                <canvas id="deviceDonutChart"></canvas>
            </div>
        </div>

        <!-- 2. Operating Systems Breakdown -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="fa-solid fa-microchip" style="color: var(--admin-accent);"></i> Operating Systems</h2>
            </div>
            <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-top: 0.5rem;">
                <?php foreach ($osBreakdown as $os): 
                    $opct = round(($os['cnt'] / $totalViews) * 100, 1);
                ?>
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 4px;">
                            <span><strong><?php echo htmlspecialchars($os['os']); ?></strong></span>
                            <span style="color: var(--admin-text-muted);"><?php echo number_format($os['cnt']); ?> (<?php echo $opct; ?>%)</span>
                        </div>
                        <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.08); border-radius: 3px; overflow: hidden;">
                            <div style="width: <?php echo $opct; ?>%; height: 100%; background: linear-gradient(90deg, #6366f1, #38bdf8);"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 3. Web Browsers -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="fa-solid fa-window-maximize" style="color: var(--admin-accent);"></i> Web Browsers</h2>
            </div>
            <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-top: 0.5rem;">
                <?php foreach ($browserBreakdown as $b): 
                    $bpct = round(($b['cnt'] / $totalViews) * 100, 1);
                ?>
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 4px;">
                            <span><strong><?php echo htmlspecialchars($b['browser']); ?></strong></span>
                            <span style="color: var(--admin-text-muted);"><?php echo number_format($b['cnt']); ?> (<?php echo $bpct; ?>%)</span>
                        </div>
                        <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.08); border-radius: 3px; overflow: hidden;">
                            <div style="width: <?php echo $bpct; ?>%; height: 100%; background: linear-gradient(90deg, #10b981, #06b6d4);"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 4. Screen Resolutions -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="fa-solid fa-display" style="color: var(--admin-accent);"></i> Display Resolutions</h2>
            </div>
            <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-top: 0.5rem;">
                <?php foreach ($screenResolutions as $sr): 
                    $spct = round(($sr['cnt'] / $totalViews) * 100, 1);
                ?>
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 4px;">
                            <span style="font-family: 'JetBrains Mono', monospace;"><strong><?php echo htmlspecialchars($sr['screen_res']); ?></strong></span>
                            <span style="color: var(--admin-text-muted);"><?php echo number_format($sr['cnt']); ?> (<?php echo $spct; ?>%)</span>
                        </div>
                        <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.08); border-radius: 3px; overflow: hidden;">
                            <div style="width: <?php echo $spct; ?>%; height: 100%; background: linear-gradient(90deg, #f59e0b, #ec4899);"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ====================================================================
     TAB 6: PAGE-BY-PAGE PERFORMANCE
     ==================================================================== -->
<div id="tab-pages" class="analytics-tab-content">
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title"><i class="fa-solid fa-file-waveform" style="color: var(--admin-accent);"></i> Detailed Page Performance &amp; Engagement</h2>
                <p class="admin-page-subtitle" style="font-size: 0.85rem; margin-top: 0.2rem;">Track user retention, average duration on page, scroll depth, and bounce rate per route.</p>
            </div>
            <div>
                <input type="text" id="pageFilterInput" class="table-filter-input" placeholder="Search page title or URL..." onkeyup="filterPagesTable()">
            </div>
        </div>

        <div class="admin-table-container">
            <table class="admin-table" id="pagesTable">
                <thead>
                    <tr>
                        <th style="width: 45px;">#</th>
                        <th>Page Title &amp; Relative Path</th>
                        <th>Module Type</th>
                        <th style="text-align: right;">Total Impressions</th>
                        <th style="text-align: right;">Unique Visitors</th>
                        <th style="text-align: right;">Avg Time</th>
                        <th style="text-align: right;">Scroll Depth</th>
                        <th style="text-align: right;">Traffic Share</th>
                        <th style="text-align: center; width: 60px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topPages)): ?>
                        <tr><td colspan="9" style="text-align: center; color: var(--admin-text-muted);">No page visits logged in this period.</td></tr>
                    <?php else: ?>
                        <?php $rank = 1; foreach ($topPages as $p): ?>
                            <tr>
                                <td style="color: var(--admin-text-muted); font-weight: 700;"><?php echo $rank++; ?></td>
                                <td>
                                    <div style="font-weight: 700; color: #fff; margin-bottom: 2px;">
                                        <?php echo htmlspecialchars($p['page_title'] ?: 'Untitled Page'); ?>
                                    </div>
                                    <div style="font-size: 0.74rem; font-family: 'JetBrains Mono', monospace; color: var(--admin-text-muted);">
                                        <?php echo htmlspecialchars($p['page_path']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $ptype = $p['page_type'];
                                    $badgeClass = 'admin-badge-cyan';
                                    if ($ptype === 'circuit_simulator') $badgeClass = 'admin-badge-green';
                                    elseif ($ptype === 'arduino_lab') $badgeClass = 'admin-badge-cyan';
                                    elseif ($ptype === 'python_program') $badgeClass = 'admin-badge-cyan';
                                    elseif ($ptype === 'assignments') $badgeClass = 'admin-badge-purple';
                                    ?>
                                    <span class="admin-badge <?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($ptype))); ?>
                                    </span>
                                </td>
                                <td style="text-align: right; font-weight: 800; font-size: 0.95rem; color: #38bdf8;">
                                    <?php echo number_format($p['views']); ?>
                                </td>
                                <td style="text-align: right; color: var(--admin-text-muted);">
                                    <?php echo number_format($p['unique_visits']); ?>
                                </td>
                                <td style="text-align: right; font-family: monospace; color: #34d399; font-weight: 600;">
                                    <?php echo format_seconds_duration($p['avg_duration']); ?>
                                </td>
                                <td style="text-align: right;">
                                    <span style="font-size: 0.82rem; font-weight: 600; color: #38bdf8;">
                                        <?php echo round($p['avg_scroll']); ?>%
                                    </span>
                                </td>
                                <td style="text-align: right; font-family: monospace; font-size: 0.82rem;">
                                    <?php echo $p['share_pct']; ?>%
                                </td>
                                <td style="text-align: center;">
                                    <a href="<?php echo htmlspecialchars($p['page_path']); ?>" target="_blank" class="btn-admin btn-admin-secondary btn-admin-sm" title="View page in new tab">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ====================================================================
     TAB 7: COMPUTATIONAL LABS & SOLVERS
     ==================================================================== -->
<div id="tab-executions" class="analytics-tab-content">
    <!-- Lab Execution Metric Summary Cards -->
    <div class="admin-grid-stats" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 2rem;">
        <div class="admin-stat-card">
            <i class="fa-brands fa-python admin-stat-icon" style="color: #38bdf8;"></i>
            <div class="admin-stat-label">Python Solvers</div>
            <div class="admin-stat-value" style="color: #38bdf8;"><?php echo number_format($execStats['python_runs'] ?? 0); ?></div>
            <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">ODE &bull; PDE &bull; Matplotlib</div>
        </div>

        <div class="admin-stat-card">
            <i class="fa-solid fa-wave-square admin-stat-icon" style="color: #10b981;"></i>
            <div class="admin-stat-label">Circuit Simulations</div>
            <div class="admin-stat-value" style="color: #34d399;"><?php echo number_format($execStats['circuit_runs'] ?? 0); ?></div>
            <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">BJT &bull; Op-Amp &bull; CRO Probes</div>
        </div>

        <div class="admin-stat-card">
            <i class="fa-solid fa-microchip admin-stat-icon" style="color: #06b6d4;"></i>
            <div class="admin-stat-label">Arduino Firmware</div>
            <div class="admin-stat-value" style="color: #22d3ee;"><?php echo number_format($execStats['arduino_runs'] ?? 0); ?></div>
            <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">C++ Sketch compiles &amp; serial</div>
        </div>

        <div class="admin-stat-card">
            <i class="fa-solid fa-clock admin-stat-icon" style="color: #a855f7;"></i>
            <div class="admin-stat-label">Execution Latency</div>
            <div class="admin-stat-value" style="color: #c084fc;"><?php echo round($execStats['avg_latency'] ?? 0); ?> <span style="font-size: 0.9rem;">ms</span></div>
            <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">Fast in-browser client speed</div>
        </div>
    </div>

    <!-- Top Executed Simulation Presets -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="fa-solid fa-fire" style="color: #f59e0b;"></i> Most Executed Physics Algorithms &amp; Presets</h2>
            <span class="admin-badge admin-badge-cyan">Top Algorithms</span>
        </div>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Algorithm / Simulation Preset</th>
                        <th>Laboratory Category</th>
                        <th style="text-align: right;">Executions</th>
                        <th style="text-align: right;">Avg Latency</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topPresets)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--admin-text-muted);">No simulation executions recorded in this window.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topPresets as $tp): ?>
                            <tr>
                                <td>
                                    <strong style="color: #fff;"><?php echo htmlspecialchars($tp['label']); ?></strong>
                                </td>
                                <td>
                                    <span class="admin-badge admin-badge-cyan"><?php echo htmlspecialchars($tp['event_category']); ?></span>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #fbbf24;">
                                    <?php echo number_format($tp['run_count']); ?>
                                </td>
                                <td style="text-align: right; font-family: monospace; color: #38bdf8;">
                                    <?php echo round($tp['avg_latency']); ?> ms
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Execution Events Stream with IST Timestamps -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="fa-solid fa-terminal" style="color: var(--admin-accent);"></i> Simulation Execution Activity Stream (IST)</h2>
            <span class="admin-badge admin-badge-green"><?php echo $successRate; ?>% Reliability</span>
        </div>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Category</th>
                        <th>Algorithm / Parameters</th>
                        <th style="text-align: right;">Latency</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: right;">Executed At (IST)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentEvents)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--admin-text-muted);">No simulation executions recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentEvents as $ev): ?>
                            <tr>
                                <td>
                                    <div style="font-family: 'JetBrains Mono', monospace; font-size: 0.82rem; font-weight: 700; color: #fff;">
                                        <?php echo htmlspecialchars($ev['event_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="admin-badge admin-badge-cyan">
                                        <?php echo htmlspecialchars($ev['event_category']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 600;">
                                        <?php echo htmlspecialchars($ev['event_label'] ?: 'Interactive Solve'); ?>
                                    </div>
                                    <?php if (!empty($ev['meta_json'])): ?>
                                        <div style="font-size: 0.72rem; color: var(--admin-text-muted); font-family: monospace;">
                                            <?php echo htmlspecialchars(substr($ev['meta_json'], 0, 75)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; font-family: monospace; color: #38bdf8;">
                                    <?php echo !empty($ev['execution_time_ms']) ? $ev['execution_time_ms'] . ' ms' : '—'; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($ev['status'] === 'success'): ?>
                                        <span class="admin-badge admin-badge-green"><i class="fa-solid fa-check"></i> Success</span>
                                    <?php else: ?>
                                        <span class="admin-badge admin-badge-red"><i class="fa-solid fa-triangle-exclamation"></i> Error</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; font-size: 0.78rem; font-family: 'JetBrains Mono', monospace; color: var(--admin-text-muted);">
                                    <?php echo htmlspecialchars($ev['created_at_ist']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ====================================================================
     TAB 8: REAL-TIME LIVE ACTIVITY FEED
     ==================================================================== -->
<div id="tab-realtime" class="analytics-tab-content">
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title"><i class="fa-solid fa-satellite-dish" style="color: #10b981;"></i> Real-Time Live Activity Stream (IST)</h2>
                <p class="admin-page-subtitle" style="font-size: 0.85rem; margin-top: 0.2rem;">Live telemetry stream of incoming visitors, active reading sessions, and simulations with exact IST timestamps.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="auto-refresh-box" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; color: var(--admin-text-muted);">
                    <input type="checkbox" id="autoRefreshCheckbox" checked>
                    <label for="autoRefreshCheckbox">Auto-refresh every 10s</label>
                </div>
                <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" onclick="location.reload()">
                    <i class="fa-solid fa-rotate"></i> Poll Now
                </button>
            </div>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Origin</th>
                        <th>Page &amp; Module Visited</th>
                        <th>Device &amp; Operating System</th>
                        <th style="text-align: right;">Time on Page</th>
                        <th style="text-align: right;">Timestamp (IST)</th>
                    </tr>
                </thead>
                <tbody id="realtimeTableBody">
                    <?php if (empty($recentVisits)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--admin-text-muted);">No live visits detected in the current scope.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentVisits as $rv): ?>
                            <tr>
                                <td>
                                    <div style="font-size: 1.35rem;" title="<?php echo htmlspecialchars($rv['country_name'] . ($rv['city'] ? ' (' . $rv['city'] . ')' : '')); ?>">
                                        <?php echo $rv['flag']; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #fff;">
                                        <?php echo htmlspecialchars($rv['page_title'] ?: 'Python4Physics Portal'); ?>
                                    </div>
                                    <div style="font-size: 0.74rem; font-family: monospace; color: var(--admin-text-muted);">
                                        <?php echo htmlspecialchars($rv['page_path']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 6px; font-size: 0.85rem;">
                                        <?php if ($rv['device_type'] === 'mobile'): ?>
                                            <i class="fa-solid fa-mobile-screen" style="color: #38bdf8;"></i>
                                        <?php elseif ($rv['device_type'] === 'tablet'): ?>
                                            <i class="fa-solid fa-tablet-screen-button" style="color: #a855f7;"></i>
                                        <?php else: ?>
                                            <i class="fa-solid fa-desktop" style="color: #10b981;"></i>
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($rv['browser']); ?> &bull; <?php echo htmlspecialchars($rv['os']); ?></span>
                                    </div>
                                </td>
                                <td style="text-align: right; font-family: monospace; color: #34d399; font-weight: 600;">
                                    <?php echo format_seconds_duration($rv['duration_seconds']); ?>
                                </td>
                                <td style="text-align: right; font-size: 0.78rem; font-family: 'JetBrains Mono', monospace; color: var(--admin-text-muted);">
                                    <?php echo htmlspecialchars($rv['created_at_ist']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ====================================================================
     CLIENT-SIDE CHART RENDERING, LIVE CLOCK & CONTROLS
     ==================================================================== -->
<script>
// Live IST Clock Counter
function updateLiveIstClock() {
    const clockElem = document.getElementById('liveIstClock');
    if (!clockElem) return;

    const now = new Date();
    // Convert to IST (UTC + 5.5 hours)
    const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
    const istTime = new Date(utc + (3600000 * 5.5));

    const options = {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    };
    clockElem.textContent = istTime.toLocaleString('en-IN', options) + ' IST';
}
setInterval(updateLiveIstClock, 1000);
updateLiveIstClock();

// Tab Switching
function switchAnalyticsTab(tabId, btn) {
    document.querySelectorAll('.analytics-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.analytics-tab-content').forEach(c => c.classList.remove('active'));

    btn.classList.add('active');
    const target = document.getElementById(tabId);
    if (target) target.classList.add('active');
}

// Table Filter for Page Visits
function filterPagesTable() {
    const input = document.getElementById('pageFilterInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('pagesTable');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        const text = tr[i].textContent || tr[i].innerText;
        tr[i].style.display = text.toLowerCase().includes(filter) ? '' : 'none';
    }
}

// Auto-Refresh Realtime Stream
let autoRefreshTimer = null;
const autoRefreshCheckbox = document.getElementById('autoRefreshCheckbox');
function setupAutoRefresh() {
    if (autoRefreshTimer) clearInterval(autoRefreshTimer);
    if (autoRefreshCheckbox && autoRefreshCheckbox.checked) {
        autoRefreshTimer = setInterval(() => {
            const realtimeTab = document.getElementById('tab-realtime');
            if (realtimeTab && realtimeTab.classList.contains('active')) {
                location.reload();
            }
        }, 10000);
    }
}
if (autoRefreshCheckbox) {
    autoRefreshCheckbox.addEventListener('change', setupAutoRefresh);
    setupAutoRefresh();
}

// Export Analytics Data in IST
function exportAnalyticsReport(format) {
    const data = {
        generated_at_ist: "<?php echo date('d M Y, h:i:s A T'); ?>",
        timezone: "Asia/Kolkata (IST, UTC+05:30)",
        scope: "<?php echo addslashes($filter['label']); ?>",
        summary: <?php echo json_encode($kpis); ?>,
        executions: <?php echo json_encode($execStats); ?>,
        hourly_breakdown_ist: <?php echo json_encode($hourlyBreakdown); ?>,
        daily_trends_ist: <?php echo json_encode($dailyTrends); ?>,
        top_countries: <?php echo json_encode($topCountries); ?>,
        top_cities: <?php echo json_encode($topCities); ?>,
        top_pages: <?php echo json_encode($topPages); ?>,
        top_referrers: <?php echo json_encode($topReferrers); ?>
    };

    if (format === 'json') {
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `p4p_analytics_report_ist_${new Date().toISOString().slice(0, 10)}.json`;
        a.click();
        URL.revokeObjectURL(url);
    } else {
        // Build Comprehensive CSV
        let csv = 'Python4Physics Analytics Report (Indian Standard Time)\n';
        csv += `Generated At IST,"${data.generated_at_ist}"\n`;
        csv += `Scope,"${data.scope}"\n\n`;

        csv += 'Category,Item,Impressions,Sessions,Extra Details\n';
        data.hourly_breakdown_ist.forEach(h => {
            csv += `Hourly (IST),"${h.slot_label}",${h.views},${h.sessions},"Executions: ${h.executions}"\n`;
        });
        data.top_countries.forEach(c => {
            csv += `Country,"${c.country_name}",${c.views},${c.sessions},"ISO: ${c.country_code}"\n`;
        });
        data.top_pages.forEach(p => {
            csv += `Page,"${p.page_title.replace(/"/g, '""')}",${p.views},${p.unique_visits},"${p.page_path}"\n`;
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `p4p_analytics_report_ist_${new Date().toISOString().slice(0, 10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    }
}

// Chart.js Visualizations Setup
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.06)';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    const isSingleDay = <?php echo $filter['is_single_day'] ? 'true' : 'false'; ?>;

    // 1. Primary Traffic Trend Chart (Hourly if single day, Daily if multi-day)
    const trendCtx = document.getElementById('trafficTrendChart');
    if (trendCtx) {
        let labels = [];
        let views = [];
        let sessions = [];
        let execs = [];

        if (isSingleDay) {
            const hourly = <?php echo json_encode($hourlyBreakdown); ?>;
            labels = hourly.map(h => h.label);
            views = hourly.map(h => h.views);
            sessions = hourly.map(h => h.sessions);
            execs = hourly.map(h => h.executions);
        } else {
            const daily = <?php echo json_encode($dailyTrends); ?>;
            labels = daily.map(d => d.formatted_date_ist);
            views = daily.map(d => parseInt(d.page_views) || 0);
            sessions = daily.map(d => parseInt(d.sessions) || 0);
            execs = daily.map(d => parseInt(d.executions) || 0);
        }

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Page Impressions',
                        data: views,
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.15)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.35,
                        pointRadius: isSingleDay ? 3.5 : 2.5,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Unique Sessions',
                        data: sessions,
                        borderColor: '#818cf8',
                        backgroundColor: 'rgba(129, 140, 248, 0.08)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointRadius: isSingleDay ? 3 : 2,
                        pointHoverRadius: 5
                    },
                    {
                        label: 'Code & Simulation Solves',
                        data: execs,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.06)',
                        borderWidth: 2,
                        borderDash: [4, 4],
                        fill: false,
                        tension: 0.3,
                        pointRadius: isSingleDay ? 3 : 2,
                        pointHoverRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: textColor, font: { weight: 600 } }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { color: gridColor },
                        ticks: { color: textColor, maxTicksLimit: 14 }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    }
                }
            }
        });
    }

    // 2. Intraday Hourly Bar Chart (00:00 to 23:00 IST)
    const hourlyCtx = document.getElementById('intradayHourlyChart');
    if (hourlyCtx) {
        const hourlyData = <?php echo json_encode($hourlyBreakdown); ?>;
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: hourlyData.map(h => h.label),
                datasets: [{
                    label: 'Hourly Page Views (IST)',
                    data: hourlyData.map(h => h.views),
                    backgroundColor: hourlyData.map(h => h.hour === <?php echo $peakHour; ?> ? '#f59e0b' : 'rgba(14, 165, 233, 0.65)'),
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: textColor, maxTicksLimit: 12 } },
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } }
                }
            }
        });
    }

    // 3. Day of Week Bar Chart
    const dowCtx = document.getElementById('dayOfWeekChart');
    if (dowCtx) {
        const dowData = <?php echo json_encode($dayOfWeekBreakdown); ?>;
        new Chart(dowCtx, {
            type: 'bar',
            data: {
                labels: dowData.map(d => d.day_name),
                datasets: [{
                    label: 'Page Views',
                    data: dowData.map(d => d.views),
                    backgroundColor: 'rgba(99, 102, 241, 0.7)',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: textColor } },
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } }
                }
            }
        });
    }

    // 4. Device Donut Chart
    const devCtx = document.getElementById('deviceDonutChart');
    if (devCtx) {
        const devData = <?php echo json_encode($deviceBreakdown); ?>;
        new Chart(devCtx, {
            type: 'doughnut',
            data: {
                labels: devData.map(d => d.device_type.toUpperCase()),
                datasets: [{
                    data: devData.map(d => d.cnt),
                    backgroundColor: ['#0ea5e9', '#10b981', '#a855f7', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: isDark ? '#111827' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: textColor, font: { weight: 600 } } }
                }
            }
        });
    }

    // 5. Channel Donut Chart
    const chanCtx = document.getElementById('channelDonutChart');
    if (chanCtx) {
        const chanData = <?php echo json_encode($topReferrers); ?>;
        new Chart(chanCtx, {
            type: 'doughnut',
            data: {
                labels: chanData.map(c => c.source),
                datasets: [{
                    data: chanData.map(c => c.count),
                    backgroundColor: ['#0ea5e9', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#14b8a6'],
                    borderWidth: 2,
                    borderColor: isDark ? '#111827' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: textColor, font: { weight: 600 } } }
                }
            }
        });
    }
});
</script>

<?php
require_once __DIR__ . '/layout_bottom.php';
?>
