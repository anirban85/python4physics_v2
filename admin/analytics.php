<?php
/**
 * Python4Physics - Admin Traffic & Telemetry Intelligence Dashboard
 * Advanced, professional, and user-friendly insights for demography, page visits,
 * engagement duration, and computational code execution telemetry.
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../include/analytics_db.php';

require_admin_login();

$page_title = "Traffic & Telemetry Intelligence Dashboard";

// Parse Filter Query Parameters
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
if ($days < 1) $days = 30;
if ($days > 365) $days = 365;

$include_bots = isset($_GET['bots']) && $_GET['bots'] === '1';

// Fetch Analytics Data
$analytics = get_analytics_dashboard_data($conn, $days, $include_bots);
$kpis = $analytics['kpis'];
$execStats = $analytics['execStats'];
$dailyTrends = $analytics['dailyTrends'];
$topCountries = $analytics['topCountries'];
$deviceBreakdown = $analytics['deviceBreakdown'];
$osBreakdown = $analytics['osBreakdown'];
$browserBreakdown = $analytics['browserBreakdown'];
$topPages = $analytics['topPages'];
$topReferrers = $analytics['topReferrers'];
$activeNow = $analytics['activeNow'];
$recentVisits = $analytics['recentVisits'];
$recentEvents = $analytics['recentEvents'];

// Compute formatted engagement duration
function format_seconds_duration($seconds) {
    $sec = (int)$seconds;
    if ($sec <= 0) return "0s";
    $m = floor($sec / 60);
    $s = $sec % 60;
    if ($m > 0) {
        return "{$m}m " . str_pad($s, 2, '0', STR_PAD_LEFT) . "s";
    }
    return "{$s}s";
}

// Country code to Flag Emoji helper
function get_country_flag_emoji($code) {
    $code = strtoupper(trim($code));
    if (strlen($code) !== 2 || $code === 'XX') return '🌐';
    $first = ord($code[0]) - ord('A') + 0x1F1E6;
    $second = ord($code[1]) - ord('A') + 0x1F1E6;
    return mb_chr($first, 'UTF-8') . mb_chr($second, 'UTF-8');
}

// Calculate percentages
$totalViews = max(1, (int)($kpis['total_views'] ?? 0));
$totalExec = (int)($execStats['total_executions'] ?? 0);
$successExec = (int)($execStats['success_runs'] ?? 0);
$successRate = $totalExec > 0 ? round(($successExec / $totalExec) * 100, 1) : 100.0;
$bounceCount = (int)($kpis['bounces'] ?? 0);
$bounceRate = round(($bounceCount / $totalViews) * 100, 1);

require_once __DIR__ . '/layout_top.php';
?>

<!-- Chart.js 4.4 from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
/* Analytics Specialized Styles */
.analytics-hero-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.25rem;
    margin-bottom: 2rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid var(--admin-border);
}

.analytics-live-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #34d399;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.03em;
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

.analytics-filter-bar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.date-pill-group {
    display: inline-flex;
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    padding: 3px;
    gap: 2px;
}

.date-pill-btn {
    background: transparent;
    border: none;
    color: var(--admin-text-muted);
    padding: 6px 12px;
    font-size: 0.8rem;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
}

.date-pill-btn:hover {
    color: var(--admin-text-main);
    background: rgba(255, 255, 255, 0.05);
}

.date-pill-btn.active {
    background: var(--admin-accent);
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(14, 165, 233, 0.35);
}

/* Tabbed Navigation */
.analytics-tab-bar {
    display: flex;
    gap: 0.5rem;
    border-bottom: 1px solid var(--admin-border);
    margin-bottom: 2rem;
    overflow-x: auto;
    padding-bottom: 0.25rem;
}

.analytics-tab-btn {
    background: transparent;
    border: none;
    color: var(--admin-text-muted);
    padding: 0.85rem 1.25rem;
    font-size: 0.92rem;
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
    animation: fadeInTab 0.25s ease-in-out;
}

.analytics-tab-content.active {
    display: block;
}

@keyframes fadeInTab {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

/* KPI Cards Extension */
.kpi-accent-glow {
    position: absolute;
    top: -40px;
    right: -40px;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    opacity: 0.15;
    pointer-events: none;
    filter: blur(24px);
}

/* Charts Card Containers */
.chart-box {
    position: relative;
    width: 100%;
    height: 340px;
}

.chart-box-donut {
    position: relative;
    width: 100%;
    height: 260px;
}

/* Engine stat mini cards */
.engine-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
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

/* Search input */
.table-filter-input {
    background: var(--admin-surface-2);
    border: 1px solid var(--admin-border);
    color: var(--admin-text-main);
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    outline: none;
    width: 260px;
}
.table-filter-input:focus {
    border-color: var(--admin-accent);
}

/* Auto-refresh pill */
.auto-refresh-box {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    color: var(--admin-text-muted);
}
</style>

<!-- Top Dashboard Header -->
<div class="analytics-hero-header">
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 0.4rem;">
            <h1 class="admin-page-title" style="margin: 0;">
                <i class="fa-solid fa-chart-line" style="color: var(--admin-accent); margin-right: 6px;"></i> Traffic &amp; Telemetry Intelligence
            </h1>
            <div class="analytics-live-pill">
                <span class="pulse-dot"></span>
                <span><?php echo number_format($activeNow); ?> Live Active Now</span>
            </div>
        </div>
        <p class="admin-page-subtitle">
            First-party audience demography, active session engagement, real-time page impressions, and computational physics code executions.
        </p>
    </div>

    <div class="analytics-filter-bar">
        <!-- Date Range Filter Pills -->
        <div class="date-pill-group">
            <a href="?days=1<?php echo $include_bots ? '&bots=1' : ''; ?>" class="date-pill-btn <?php echo $days === 1 ? 'active' : ''; ?>">Today</a>
            <a href="?days=7<?php echo $include_bots ? '&bots=1' : ''; ?>" class="date-pill-btn <?php echo $days === 7 ? 'active' : ''; ?>">7 Days</a>
            <a href="?days=30<?php echo $include_bots ? '&bots=1' : ''; ?>" class="date-pill-btn <?php echo $days === 30 ? 'active' : ''; ?>">30 Days</a>
            <a href="?days=90<?php echo $include_bots ? '&bots=1' : ''; ?>" class="date-pill-btn <?php echo $days === 90 ? 'active' : ''; ?>">90 Days</a>
            <a href="?days=365<?php echo $include_bots ? '&bots=1' : ''; ?>" class="date-pill-btn <?php echo $days === 365 ? 'active' : ''; ?>">All Year</a>
        </div>

        <!-- Bot Filter Checkbox -->
        <a href="?days=<?php echo $days; ?>&bots=<?php echo $include_bots ? '0' : '1'; ?>" class="btn-admin btn-admin-secondary btn-admin-sm" title="Toggle Search Crawlers / Bots">
            <i class="fa-solid fa-robot"></i> <?php echo $include_bots ? 'Crawlers Included' : 'Human Traffic Only'; ?>
        </a>

        <!-- Export Buttons -->
        <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" onclick="exportAnalyticsReport('csv')" title="Download CSV Audit Report">
            <i class="fa-solid fa-file-csv"></i> Export CSV
        </button>
        <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" onclick="exportAnalyticsReport('json')" title="Download JSON Dataset">
            <i class="fa-solid fa-file-code"></i> JSON
        </button>
        <button type="button" class="btn-admin btn-admin-primary btn-admin-sm" onclick="location.reload()" title="Refresh telemetry">
            <i class="fa-solid fa-rotate"></i>
        </button>
    </div>
</div>

<!-- ====================================================================
     EXECUTIVE 6-KPI STATS GRID
     ==================================================================== -->
<div class="admin-grid-stats" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 2rem;">
    <!-- 1. Total Impressions -->
    <div class="admin-stat-card">
        <div class="kpi-accent-glow" style="background: #38bdf8;"></div>
        <i class="fa-solid fa-eye admin-stat-icon" style="color: #38bdf8;"></i>
        <div class="admin-stat-label">Total Impressions</div>
        <div class="admin-stat-value" style="color: #38bdf8;"><?php echo number_format($kpis['total_views'] ?? 0); ?></div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Across <?php echo number_format($kpis['unique_sessions'] ?? 0); ?> user sessions
        </div>
    </div>

    <!-- 2. Unique Audience -->
    <div class="admin-stat-card">
        <div class="kpi-accent-glow" style="background: #6366f1;"></div>
        <i class="fa-solid fa-users admin-stat-icon" style="color: #6366f1;"></i>
        <div class="admin-stat-label">Unique Visitors</div>
        <div class="admin-stat-value" style="color: #818cf8;"><?php echo number_format($kpis['unique_visitors'] ?? 0); ?></div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Privacy-salted IP hash
        </div>
    </div>

    <!-- 3. Active Engagement Time -->
    <div class="admin-stat-card">
        <div class="kpi-accent-glow" style="background: #10b981;"></div>
        <i class="fa-solid fa-stopwatch admin-stat-icon" style="color: #10b981;"></i>
        <div class="admin-stat-label">Avg Engagement</div>
        <div class="admin-stat-value" style="color: #34d399;"><?php echo format_seconds_duration($kpis['avg_duration'] ?? 0); ?></div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Active tab visibility &amp; reading
        </div>
    </div>

    <!-- 4. Scroll Depth -->
    <div class="admin-stat-card">
        <div class="kpi-accent-glow" style="background: #06b6d4;"></div>
        <i class="fa-solid fa-arrows-up-down admin-stat-icon" style="color: #06b6d4;"></i>
        <div class="admin-stat-label">Avg Scroll Depth</div>
        <div class="admin-stat-value" style="color: #22d3ee;"><?php echo round($kpis['avg_scroll'] ?? 0); ?>%</div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Page read completion rate
        </div>
    </div>

    <!-- 5. Code & Lab Executions -->
    <div class="admin-stat-card">
        <div class="kpi-accent-glow" style="background: #f59e0b;"></div>
        <i class="fa-solid fa-bolt admin-stat-icon" style="color: #f59e0b;"></i>
        <div class="admin-stat-label">Lab Executions</div>
        <div class="admin-stat-value" style="color: #fbbf24;"><?php echo number_format($totalExec); ?></div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Python &bull; SPICE &bull; Arduino &bull; Plots
        </div>
    </div>

    <!-- 6. Simulation Success Rate -->
    <div class="admin-stat-card">
        <div class="kpi-accent-glow" style="background: #a855f7;"></div>
        <i class="fa-solid fa-circle-check admin-stat-icon" style="color: #a855f7;"></i>
        <div class="admin-stat-label">Solver Reliability</div>
        <div class="admin-stat-value" style="color: #c084fc;"><?php echo $successRate; ?>%</div>
        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--admin-text-muted);">
            Avg Latency: <?php echo round($execStats['avg_latency'] ?? 0); ?> ms
        </div>
    </div>
</div>

<!-- ====================================================================
     TABBED NAVIGATION BAR
     ==================================================================== -->
<div class="analytics-tab-bar">
    <button type="button" class="analytics-tab-btn active" onclick="switchAnalyticsTab('tab-overview', this)">
        <i class="fa-solid fa-chart-area"></i> Overview &amp; Trends
    </button>
    <button type="button" class="analytics-tab-btn" onclick="switchAnalyticsTab('tab-demography', this)">
        <i class="fa-solid fa-earth-americas"></i> Demography &amp; Devices
    </button>
    <button type="button" class="analytics-tab-btn" onclick="switchAnalyticsTab('tab-pages', this)">
        <i class="fa-solid fa-file-lines"></i> Page Visits &amp; Channels
    </button>
    <button type="button" class="analytics-tab-btn" onclick="switchAnalyticsTab('tab-executions', this)">
        <i class="fa-solid fa-microchip"></i> Code Executions &amp; Labs
    </button>
    <button type="button" class="analytics-tab-btn" onclick="switchAnalyticsTab('tab-realtime', this)">
        <i class="fa-solid fa-tower-broadcast"></i> Live Activity Feed
    </button>
</div>

<!-- ====================================================================
     TAB 1: OVERVIEW & TRENDS
     ==================================================================== -->
<div id="tab-overview" class="analytics-tab-content active">
    <!-- Main Traffic Trends Chart Card -->
    <div class="admin-card" style="margin-bottom: 2rem;">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title"><i class="fa-solid fa-chart-line" style="color: var(--admin-accent);"></i> Traffic &amp; Audience Volume Over Time</h2>
                <p class="admin-page-subtitle" style="font-size: 0.85rem; margin-top: 0.2rem;">Daily breakdown of page impressions, unique sessions, and code executions.</p>
            </div>
            <div style="font-size: 0.8rem; color: var(--admin-text-muted);">
                Window: <strong>Last <?php echo $days; ?> Days</strong>
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
                <div style="font-size: 0.74rem; color: #38bdf8;">WASM in-browser execution</div>
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
                <div style="font-size: 0.74rem; color: #f59e0b;">GNUplot SVG &amp; KaTeX/LaTeX</div>
            </div>
        </div>
    </div>

    <!-- Secondary Split: Top Referrers & Device Distribution -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem;">
        <!-- Top Traffic Acquisition Sources -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="fa-solid fa-arrow-trend-up" style="color: var(--admin-accent);"></i> Traffic Acquisition Channels</h2>
            </div>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Channel / Referrer</th>
                            <th style="text-align: right;">Visits</th>
                            <th style="text-align: right;">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topReferrers)): ?>
                            <tr><td colspan="3" style="text-align: center; color: var(--admin-text-muted);">No referrers logged in this period.</td></tr>
                        <?php else: ?>
                            <?php foreach ($topReferrers as $ref): 
                                $pct = round(($ref['count'] / $totalViews) * 100, 1);
                            ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <?php if (str_contains($ref['source'], 'google')): ?>
                                                <i class="fa-brands fa-google" style="color: #ea4335;"></i>
                                            <?php elseif (str_contains($ref['source'], 'bing')): ?>
                                                <i class="fa-brands fa-microsoft" style="color: #00a4ef;"></i>
                                            <?php elseif (str_contains($ref['source'], 'github')): ?>
                                                <i class="fa-brands fa-github"></i>
                                            <?php elseif (str_contains($ref['source'], 'Direct')): ?>
                                                <i class="fa-solid fa-bookmark" style="color: #f59e0b;"></i>
                                            <?php else: ?>
                                                <i class="fa-solid fa-link" style="color: var(--admin-accent);"></i>
                                            <?php endif; ?>
                                            <strong><?php echo htmlspecialchars($ref['source']); ?></strong>
                                        </div>
                                    </td>
                                    <td style="text-align: right; font-weight: 700;"><?php echo number_format($ref['count']); ?></td>
                                    <td style="text-align: right;">
                                        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 6px;">
                                            <div style="width: 50px; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden;">
                                                <div style="width: <?php echo min(100, $pct); ?>%; height: 100%; background: var(--admin-accent);"></div>
                                            </div>
                                            <span style="font-size: 0.78rem; color: var(--admin-text-muted);"><?php echo $pct; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Device Categories Donut -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="fa-solid fa-laptop" style="color: var(--admin-accent);"></i> Device Categorization</h2>
            </div>
            <div class="chart-box-donut">
                <canvas id="deviceDonutChart"></canvas>
            </div>
            <div style="display: flex; justify-content: space-around; margin-top: 1rem; border-top: 1px solid var(--admin-border); padding-top: 0.85rem;">
                <?php foreach ($deviceBreakdown as $dev): 
                    $dpct = round(($dev['cnt'] / $totalViews) * 100, 1);
                ?>
                    <div style="text-align: center;">
                        <div style="font-size: 0.76rem; text-transform: uppercase; color: var(--admin-text-muted); font-weight: 700;">
                            <?php echo htmlspecialchars(ucfirst($dev['device_type'])); ?>
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; color: #fff;">
                            <?php echo $dpct; ?>%
                        </div>
                        <div style="font-size: 0.75rem; color: var(--admin-text-muted);"><?php echo number_format($dev['cnt']); ?> visits</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ====================================================================
     TAB 2: DEMOGRAPHY & GEOLOCATION
     ==================================================================== -->
<div id="tab-demography" class="analytics-tab-content">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <!-- Top Countries Table -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="fa-solid fa-earth-asia" style="color: var(--admin-accent);"></i> Geographic Visitor Distribution</h2>
                <span class="admin-badge admin-badge-cyan"><?php echo count($topCountries); ?> Countries</span>
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
                            <?php foreach ($topCountries as $c): 
                                $cpct = round(($c['views'] / $totalViews) * 100, 1);
                                $flag = get_country_flag_emoji($c['country_code']);
                            ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 1.25rem;"><?php echo $flag; ?></span>
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
                                                <div style="width: <?php echo min(100, $cpct); ?>%; height: 100%; background: #38bdf8;"></div>
                                            </div>
                                            <span style="font-size: 0.78rem;"><?php echo $cpct; ?>%</span>
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

        <!-- Browser & Operating System Breakdown Grid -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Operating Systems Card -->
            <div class="admin-card" style="margin-bottom: 0;">
                <div class="admin-card-header">
                    <h2 class="admin-card-title"><i class="fa-solid fa-microchip" style="color: var(--admin-accent);"></i> Operating Systems</h2>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
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

            <!-- Browsers Card -->
            <div class="admin-card" style="margin-bottom: 0;">
                <div class="admin-card-header">
                    <h2 class="admin-card-title"><i class="fa-solid fa-window-maximize" style="color: var(--admin-accent);"></i> Web Browsers</h2>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
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
        </div>
    </div>
</div>

<!-- ====================================================================
     TAB 3: PAGE VISITS & CHANNELS
     ==================================================================== -->
<div id="tab-pages" class="analytics-tab-content">
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title"><i class="fa-solid fa-file-waveform" style="color: var(--admin-accent);"></i> Most Visited Pages &amp; Engagement</h2>
                <p class="admin-page-subtitle" style="font-size: 0.85rem; margin-top: 0.2rem;">Track user retention, average duration on page, and reading scroll depth.</p>
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
                        <th>Page Title &amp; Path</th>
                        <th>Module Type</th>
                        <th style="text-align: right;">Total Impressions</th>
                        <th style="text-align: right;">Unique Visitors</th>
                        <th style="text-align: right;">Avg Duration</th>
                        <th style="text-align: right;">Scroll Depth</th>
                        <th style="text-align: center; width: 60px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topPages)): ?>
                        <tr><td colspan="8" style="text-align: center; color: var(--admin-text-muted);">No page visits logged.</td></tr>
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
                                <td style="text-align: right; font-weight: 800; font-size: 0.95rem;">
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
     TAB 4: CODE EXECUTIONS & LAB TELEMETRY
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

    <!-- Recent Execution Events Stream -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="fa-solid fa-terminal" style="color: var(--admin-accent);"></i> Recent Computational Simulation Runs</h2>
            <span class="admin-badge admin-badge-green"><?php echo $successRate; ?>% Reliability</span>
        </div>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Laboratory Category</th>
                        <th>Algorithm / Preset Details</th>
                        <th style="text-align: right;">Latency</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: right;">Executed At</th>
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
                                        <?php echo htmlspecialchars($ev['event_label'] ?: 'Interactive Run'); ?>
                                    </div>
                                    <?php if (!empty($ev['meta_json'])): ?>
                                        <div style="font-size: 0.72rem; color: var(--admin-text-muted); font-family: monospace;">
                                            <?php echo htmlspecialchars(substr($ev['meta_json'], 0, 80)); ?>
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
                                <td style="text-align: right; font-size: 0.78rem; color: var(--admin-text-muted);">
                                    <?php echo htmlspecialchars($ev['created_at']); ?>
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
     TAB 5: REAL-TIME LIVE ACTIVITY FEED
     ==================================================================== -->
<div id="tab-realtime" class="analytics-tab-content">
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title"><i class="fa-solid fa-satellite-dish" style="color: #10b981;"></i> Real-Time Visitor Live Stream</h2>
                <p class="admin-page-subtitle" style="font-size: 0.85rem; margin-top: 0.2rem;">Live telemetry stream of incoming visitors, active reading sessions, and simulations.</p>
            </div>
            <div class="auto-refresh-box">
                <input type="checkbox" id="autoRefreshCheckbox" checked>
                <label for="autoRefreshCheckbox">Auto-refresh every 10s</label>
            </div>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Origin</th>
                        <th>Page Visited</th>
                        <th>Device &amp; Browser</th>
                        <th style="text-align: right;">Time on Page</th>
                        <th style="text-align: right;">Timestamp</th>
                    </tr>
                </thead>
                <tbody id="realtimeTableBody">
                    <?php if (empty($recentVisits)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--admin-text-muted);">No live visits detected.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentVisits as $rv): 
                            $rflag = get_country_flag_emoji($rv['country_code']);
                        ?>
                            <tr>
                                <td>
                                    <div style="font-size: 1.35rem;" title="<?php echo htmlspecialchars($rv['country_name']); ?>">
                                        <?php echo $rflag; ?>
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
                                        <span><?php echo htmlspecialchars($rv['browser']); ?> &bull; <?php echo htmlspecialchars($rv['device_type']); ?></span>
                                    </div>
                                </td>
                                <td style="text-align: right; font-family: monospace; color: #34d399; font-weight: 600;">
                                    <?php echo format_seconds_duration($rv['duration_seconds']); ?>
                                </td>
                                <td style="text-align: right; font-size: 0.78rem; color: var(--admin-text-muted);">
                                    <?php echo htmlspecialchars($rv['created_at']); ?>
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
     CLIENT-SIDE CHART RENDERING & TAB CONTROLS
     ==================================================================== -->
<script>
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

// Export Analytics Data
function exportAnalyticsReport(format) {
    const data = {
        generated_at: new Date().toISOString(),
        summary: <?php echo json_encode($kpis); ?>,
        executions: <?php echo json_encode($execStats); ?>,
        daily_trends: <?php echo json_encode($dailyTrends); ?>,
        top_countries: <?php echo json_encode($topCountries); ?>,
        top_pages: <?php echo json_encode($topPages); ?>
    };

    if (format === 'json') {
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `p4p_analytics_report_${new Date().toISOString().slice(0, 10)}.json`;
        a.click();
        URL.revokeObjectURL(url);
    } else {
        // Build CSV
        let csv = 'Type,Name,Impressions,Sessions,Extra\n';
        data.top_countries.forEach(c => {
            csv += `Country,"${c.country_name}",${c.views},${c.sessions},"${c.country_code}"\n`;
        });
        data.top_pages.forEach(p => {
            csv += `Page,"${p.page_title.replace(/"/g, '""')}",${p.views},${p.unique_visits},"${p.page_path}"\n`;
        });
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `p4p_analytics_report_${new Date().toISOString().slice(0, 10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    }
}

// Chart.js Visualizations Setup
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.06)';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    // 1. Traffic Trend Line Chart
    const trendCtx = document.getElementById('trafficTrendChart');
    if (trendCtx) {
        const trendData = <?php echo json_encode($dailyTrends); ?>;
        const labels = trendData.map(d => d.visit_date);
        const views = trendData.map(d => parseInt(d.page_views) || 0);
        const sessions = trendData.map(d => parseInt(d.sessions) || 0);
        const execs = trendData.map(d => parseInt(d.executions) || 0);

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
                        pointRadius: 3,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Unique Sessions',
                        data: sessions,
                        borderColor: '#818cf8',
                        backgroundColor: 'rgba(129, 140, 248, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 2.5,
                        pointHoverRadius: 5
                    },
                    {
                        label: 'Code & Simulation Runs',
                        data: execs,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.08)',
                        borderWidth: 2,
                        borderDash: [4, 4],
                        fill: false,
                        tension: 0.3,
                        pointRadius: 2.5,
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
                        ticks: { color: textColor, maxTicksLimit: 12 }
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

    // 2. Device Categorization Donut Chart
    const devCtx = document.getElementById('deviceDonutChart');
    if (devCtx) {
        const devData = <?php echo json_encode($deviceBreakdown); ?>;
        const devLabels = devData.map(d => d.device_type.charAt(0).toUpperCase() + d.device_type.slice(1));
        const devCounts = devData.map(d => parseInt(d.cnt) || 0);

        new Chart(devCtx, {
            type: 'doughnut',
            data: {
                labels: devLabels,
                datasets: [{
                    data: devCounts,
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
                    legend: {
                        position: 'bottom',
                        labels: { color: textColor, font: { weight: 600 } }
                    }
                }
            }
        });
    }
});
</script>

<?php
require_once __DIR__ . '/layout_bottom.php';
?>
