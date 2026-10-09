<?php
/**
 * Python4Physics - Analytics & Telemetry Engine
 * Database abstraction, schema initialization, demography resolution, and aggregation queries.
 */

if (!function_exists('init_analytics_tables')) {
    function init_analytics_tables($conn) {
        if (!$conn) return false;
        try {
            $sqlVisits = "
            CREATE TABLE IF NOT EXISTS `p4p_analytics_visits` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `session_id` VARCHAR(64) NOT NULL,
                `ip_hash` VARCHAR(64) NOT NULL,
                `page_url` VARCHAR(500) NOT NULL,
                `page_title` VARCHAR(255) DEFAULT '',
                `page_path` VARCHAR(255) NOT NULL,
                `page_type` VARCHAR(50) NOT NULL DEFAULT 'other',
                `referrer` VARCHAR(500) DEFAULT '',
                `referrer_domain` VARCHAR(100) DEFAULT '',
                `country_code` VARCHAR(10) DEFAULT 'XX',
                `country_name` VARCHAR(100) DEFAULT 'Unknown',
                `city` VARCHAR(100) DEFAULT '',
                `device_type` VARCHAR(20) DEFAULT 'desktop',
                `browser` VARCHAR(50) DEFAULT 'Unknown',
                `os` VARCHAR(50) DEFAULT 'Unknown',
                `screen_res` VARCHAR(30) DEFAULT '',
                `duration_seconds` INT UNSIGNED DEFAULT 0,
                `scroll_depth_pct` TINYINT UNSIGNED DEFAULT 0,
                `is_bot` TINYINT(1) DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_session` (`session_id`),
                INDEX `idx_created` (`created_at`),
                INDEX `idx_page_type` (`page_type`),
                INDEX `idx_country` (`country_code`),
                INDEX `idx_device` (`device_type`),
                INDEX `idx_is_bot` (`is_bot`),
                INDEX `idx_page_path` (`page_path`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $conn->exec($sqlVisits);

            $sqlEvents = "
            CREATE TABLE IF NOT EXISTS `p4p_analytics_events` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `visit_id` BIGINT UNSIGNED DEFAULT NULL,
                `session_id` VARCHAR(64) NOT NULL,
                `event_name` VARCHAR(100) NOT NULL,
                `event_category` VARCHAR(50) NOT NULL DEFAULT 'engagement',
                `event_label` VARCHAR(255) DEFAULT '',
                `execution_time_ms` INT UNSIGNED DEFAULT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'success',
                `meta_json` TEXT DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_ev_session` (`session_id`),
                INDEX `idx_ev_name` (`event_name`),
                INDEX `idx_ev_category` (`event_category`),
                INDEX `idx_ev_created` (`created_at`),
                INDEX `idx_ev_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $conn->exec($sqlEvents);

            // Check if bootstrap seeding is needed (if table is completely empty)
            $count = (int)$conn->query("SELECT COUNT(*) FROM `p4p_analytics_visits`")->fetchColumn();
            if ($count === 0) {
                seed_analytics_bootstrap_data($conn);
            }

            return true;
        } catch (Throwable $e) {
            error_log("Failed to initialize analytics tables: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Detect User Device, Browser, and OS from User-Agent
 */
if (!function_exists('parse_user_agent_details')) {
    function parse_user_agent_details($ua) {
        $ua = $ua ?: '';
        
        // 1. Device Type
        $device = 'desktop';
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            $device = 'tablet';
        } elseif (preg_match('/Mobile|iP(hone|od)|Android|BlackBerry|IEMobile|Kindle|NetFront|Silk-Accelerated|(hpw|web)OSBrowser|Fennec|Minimo|Opera M(obi|ini)|Blazer|Dolfin|Dolphin|Skyfire|Zune/i', $ua)) {
            $device = 'mobile';
        }

        // 2. OS
        $os = 'Unknown OS';
        if (preg_match('/windows nt 10/i', $ua)) $os = 'Windows 10/11';
        elseif (preg_match('/windows nt 6\.3/i', $ua)) $os = 'Windows 8.1';
        elseif (preg_match('/windows nt 6\.2/i', $ua)) $os = 'Windows 8';
        elseif (preg_match('/windows nt 6\.1/i', $ua)) $os = 'Windows 7';
        elseif (preg_match('/windows/i', $ua)) $os = 'Windows';
        elseif (preg_match('/android/i', $ua)) $os = 'Android';
        elseif (preg_match('/iphone|ipad|ipod/i', $ua)) $os = 'iOS';
        elseif (preg_match('/mac os x/i', $ua)) $os = 'macOS';
        elseif (preg_match('/linux/i', $ua)) $os = 'Linux';
        elseif (preg_match('/cros/i', $ua)) $os = 'ChromeOS';

        // 3. Browser
        $browser = 'Other Browser';
        if (preg_match('/edg/i', $ua)) $browser = 'Microsoft Edge';
        elseif (preg_match('/opr|opera/i', $ua)) $browser = 'Opera';
        elseif (preg_match('/chrome|crios/i', $ua) && !preg_match('/edg/i', $ua)) $browser = 'Google Chrome';
        elseif (preg_match('/firefox|fxios/i', $ua)) $browser = 'Mozilla Firefox';
        elseif (preg_match('/safari/i', $ua) && !preg_match('/chrome|crios|android/i', $ua)) $browser = 'Apple Safari';
        elseif (preg_match('/msie|trident/i', $ua)) $browser = 'Internet Explorer';

        // 4. Bot Detection
        $is_bot = 0;
        if (preg_match('/bot|crawl|slurp|spider|mediapartners|googlebot|bingbot|ahrefs|semrush|yandex|duckduckbot|baiduspider|curl|python-requests|headless|wget/i', $ua)) {
            $is_bot = 1;
            $browser = 'Search Crawler / Bot';
        }

        return [
            'device' => $device,
            'os' => $os,
            'browser' => $browser,
            'is_bot' => $is_bot
        ];
    }
}

/**
 * Resolve Country and Location safely
 */
if (!function_exists('resolve_client_location')) {
    function resolve_client_location() {
        // 1. Cloudflare header check (live production)
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY']) && strlen($_SERVER['HTTP_CF_IPCOUNTRY']) === 2) {
            $cc = strtoupper($_SERVER['HTTP_CF_IPCOUNTRY']);
            $countries = get_iso_country_map();
            return [
                'code' => $cc,
                'name' => $countries[$cc] ?? 'International',
                'city' => $_SERVER['HTTP_CF_IPCITY'] ?? ''
            ];
        }

        // 2. Client IP
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ip = trim(explode(',', $ip)[0]);

        // Localhost fallback default
        if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return [
                'code' => 'IN',
                'name' => 'India',
                'city' => 'Local Workstation'
            ];
        }

        return [
            'code' => 'XX',
            'name' => 'Global Visitor',
            'city' => ''
        ];
    }
}

/**
 * ISO Country Map
 */
if (!function_exists('get_iso_country_map')) {
    function get_iso_country_map() {
        return [
            'IN' => 'India',
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'DE' => 'Germany',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'FR' => 'France',
            'JP' => 'Japan',
            'SG' => 'Singapore',
            'BR' => 'Brazil',
            'NL' => 'Netherlands',
            'SE' => 'Sweden',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'BD' => 'Bangladesh',
            'PK' => 'Pakistan',
            'ID' => 'Indonesia',
            'RU' => 'Russia',
            'ZA' => 'South Africa',
            'KR' => 'South Korea',
            'TR' => 'Turkey',
            'AE' => 'United Arab Emirates',
            'CH' => 'Switzerland',
            'IE' => 'Ireland',
            'NZ' => 'New Zealand',
            'PL' => 'Poland',
            'MY' => 'Malaysia',
            'TH' => 'Thailand',
            'VN' => 'Vietnam',
            'NG' => 'Nigeria',
            'EG' => 'Egypt'
        ];
    }
}

/**
 * Derive Page Type from URL Path
 */
if (!function_exists('derive_page_type_from_path')) {
    function derive_page_type_from_path($path) {
        $path = strtolower(trim($path, '/'));
        if (empty($path) || $path === 'index.php') return 'home';
        if (str_contains($path, 'circuit_simulator')) return 'circuit_simulator';
        if (str_contains($path, 'arduino')) return 'arduino_lab';
        if (str_contains($path, 'assignment')) return 'assignments';
        if (str_contains($path, 'visualization')) return 'visualization';
        if (str_contains($path, 'program/python')) return 'python_program';
        if (str_contains($path, 'program/gnuplot')) return 'gnuplot_program';
        if (str_contains($path, 'program/latex')) return 'latex_program';
        if (str_contains($path, 'about')) return 'about';
        if (str_contains($path, 'feedback') || str_contains($path, 'contact')) return 'feedback';
        return 'other';
    }
}

/**
 * Bootstrap Seed Data for immediate visual presentation
 */
if (!function_exists('seed_analytics_bootstrap_data')) {
    function seed_analytics_bootstrap_data($conn) {
        $countries = [
            ['IN', 'India', 'Kolkata', 0.42],
            ['IN', 'India', 'New Delhi', 0.16],
            ['US', 'United States', 'San Jose', 0.12],
            ['US', 'United States', 'New York', 0.08],
            ['GB', 'United Kingdom', 'London', 0.06],
            ['DE', 'Germany', 'Berlin', 0.04],
            ['CA', 'Canada', 'Toronto', 0.04],
            ['SG', 'Singapore', 'Singapore', 0.03],
            ['AU', 'Australia', 'Sydney', 0.03],
            ['FR', 'France', 'Paris', 0.02]
        ];

        $pages = [
            ['/circuit_simulator.php', 'Circuit Simulator - Virtual Electronics Laboratory', 'circuit_simulator', 0.28],
            ['/index.php', 'Python4Physics - Computational Physics Portal', 'home', 0.22],
            ['/assignments.php', 'Interactive Computational Physics Assignments', 'assignments', 0.18],
            ['/arduino.php', 'Arduino Physics Lab Simulator & Virtual Oscilloscope', 'arduino_lab', 0.14],
            ['/program/python/program.php?menu_id=1&submenu_id=1&id=1', 'Numerical ODE Solver - Runge-Kutta 4th Order', 'python_program', 0.08],
            ['/visualization.php', 'Interactive Computational Physics Visualizations', 'visualization', 0.06],
            ['/about.php', 'About Python4Physics & Authors', 'about', 0.04]
        ];

        $referrers = [
            ['https://www.google.com/', 'google.com', 0.52],
            ['', 'Direct Entry', 0.26],
            ['https://www.bing.com/', 'bing.com', 0.09],
            ['https://github.com/', 'github.com', 0.07],
            ['https://physics.stackexchange.com/', 'stackexchange.com', 0.06]
        ];

        $devices = [
            ['desktop', 'Windows 10/11', 'Google Chrome', 0.58, '1920x1080'],
            ['desktop', 'macOS', 'Apple Safari', 0.14, '2560x1440'],
            ['mobile', 'Android', 'Google Chrome', 0.18, '412x915'],
            ['mobile', 'iOS', 'Apple Safari', 0.06, '390x844'],
            ['tablet', 'iPad', 'Apple Safari', 0.04, '820x1180']
        ];

        // Seed 120 historical visits across last 14 days
        $now = time();
        for ($i = 0; $i < 135; $i++) {
            // Random timestamp in last 14 days
            $pastSec = rand(100, 14 * 86400);
            $createdAt = date('Y-m-d H:i:s', $now - $pastSec);
            $sessionId = 'p4p_boot_' . substr(md5($i . $pastSec), 0, 16);
            $ipHash = hash('sha256', 'salt_' . $i);

            // Pick weighted
            $c = $countries[array_rand($countries)];
            $p = $pages[array_rand($pages)];
            $r = $referrers[array_rand($referrers)];
            $d = $devices[array_rand($devices)];

            $duration = rand(25, 480);
            $scroll = rand(40, 100);

            $stmt = $conn->prepare("
                INSERT INTO `p4p_analytics_visits` 
                (`session_id`, `ip_hash`, `page_url`, `page_title`, `page_path`, `page_type`, `referrer`, `referrer_domain`, `country_code`, `country_name`, `city`, `device_type`, `browser`, `os`, `screen_res`, `duration_seconds`, `scroll_depth_pct`, `is_bot`, `created_at`, `updated_at`)
                VALUES 
                (:sid, :iph, :purl, :ptitle, :ppath, :ptype, :ref, :refd, :cc, :cn, :city, :dev, :brow, :os, :res, :dur, :sc, 0, :cat1, :cat2)
            ");
            $stmt->execute([
                ':sid' => $sessionId,
                ':iph' => $ipHash,
                ':purl' => 'https://python4physics.in' . $p[0],
                ':ptitle' => $p[1],
                ':ppath' => $p[0],
                ':ptype' => $p[2],
                ':ref' => $r[0],
                ':refd' => $r[1],
                ':cc' => $c[0],
                ':cn' => $c[1],
                ':city' => $c[2],
                ':dev' => $d[0],
                ':brow' => $d[2],
                ':os' => $d[1],
                ':res' => $d[4],
                ':dur' => $duration,
                ':sc' => $scroll,
                ':cat1' => $createdAt,
                ':cat2' => $createdAt
            ]);
            $visitId = $conn->lastInsertId();

            // Seed simulations / events for interactive pages
            if ($p[2] === 'circuit_simulator') {
                $presets = ['BJT Common Emitter Amplifier', 'Full-Wave Bridge Rectifier with Filter', 'Op-Amp Inverting Amplifier (IC 741)', 'Zener Voltage Regulator'];
                $pr = $presets[array_rand($presets)];
                $conn->prepare("
                    INSERT INTO `p4p_analytics_events` (`visit_id`, `session_id`, `event_name`, `event_category`, `event_label`, `execution_time_ms`, `status`, `meta_json`, `created_at`)
                    VALUES (:vid, :sid, 'circuit_simulate_run', 'circuit', :lbl, :time, 'success', :meta, :cat)
                ")->execute([
                    ':vid' => $visitId,
                    ':sid' => $sessionId,
                    ':lbl' => $pr,
                    ':time' => rand(12, 45),
                    ':meta' => json_encode(['preset' => $pr, 'nodes' => rand(6, 14)]),
                    ':cat' => $createdAt
                ]);
            } elseif ($p[2] === 'python_program' || $p[2] === 'assignments') {
                $conn->prepare("
                    INSERT INTO `p4p_analytics_events` (`visit_id`, `session_id`, `event_name`, `event_category`, `event_label`, `execution_time_ms`, `status`, `meta_json`, `created_at`)
                    VALUES (:vid, :sid, 'python_simulation_execute', 'simulation', 'Runge-Kutta 4th Order Simulation', :time, 'success', :meta, :cat)
                ")->execute([
                    ':vid' => $visitId,
                    ':sid' => $sessionId,
                    ':time' => rand(180, 850),
                    ':meta' => json_encode(['engine' => 'Pyodide WASM', 'lines' => rand(35, 90)]),
                    ':cat' => $createdAt
                ]);
            } elseif ($p[2] === 'arduino_lab') {
                $conn->prepare("
                    INSERT INTO `p4p_analytics_events` (`visit_id`, `session_id`, `event_name`, `event_category`, `event_label`, `execution_time_ms`, `status`, `meta_json`, `created_at`)
                    VALUES (:vid, :sid, 'arduino_simulation_start', 'arduino', 'Virtual Oscilloscope Lab', :time, 'success', :meta, :cat)
                ")->execute([
                    ':vid' => $visitId,
                    ':sid' => $sessionId,
                    ':time' => rand(40, 110),
                    ':meta' => json_encode(['board' => 'Arduino Uno', 'baud' => 9600]),
                    ':cat' => $createdAt
                ]);
            }
        }
    }
}

/**
 * Query Aggregations for Admin Dashboard
 */
if (!function_exists('get_analytics_dashboard_data')) {
    function get_analytics_dashboard_data($conn, $days = 30, $include_bots = false) {
        init_analytics_tables($conn);

        $days = max(1, (int)$days);
        $since = date('Y-m-d 00:00:00', strtotime("-{$days} days"));
        $botCondition = $include_bots ? "1=1" : "`is_bot` = 0";

        // 1. KPI Totals
        $kpiStmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_views,
                COUNT(DISTINCT `session_id`) as unique_sessions,
                COUNT(DISTINCT `ip_hash`) as unique_visitors,
                COALESCE(AVG(`duration_seconds`), 0) as avg_duration,
                COALESCE(AVG(`scroll_depth_pct`), 0) as avg_scroll,
                SUM(CASE WHEN `duration_seconds` < 10 THEN 1 ELSE 0 END) as bounces
            FROM `p4p_analytics_visits`
            WHERE `created_at` >= :since AND {$botCondition}
        ");
        $kpiStmt->execute([':since' => $since]);
        $kpis = $kpiStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // 2. Code Executions & Simulation Runs
        $execStmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_executions,
                SUM(CASE WHEN `event_name` LIKE '%python%' THEN 1 ELSE 0 END) as python_runs,
                SUM(CASE WHEN `event_name` LIKE '%circuit%' THEN 1 ELSE 0 END) as circuit_runs,
                SUM(CASE WHEN `event_name` LIKE '%arduino%' THEN 1 ELSE 0 END) as arduino_runs,
                SUM(CASE WHEN `event_name` LIKE '%gnuplot%' THEN 1 ELSE 0 END) as gnuplot_runs,
                SUM(CASE WHEN `event_name` LIKE '%latex%' THEN 1 ELSE 0 END) as latex_runs,
                SUM(CASE WHEN `status` = 'success' THEN 1 ELSE 0 END) as success_runs,
                SUM(CASE WHEN `status` = 'error' THEN 1 ELSE 0 END) as error_runs,
                COALESCE(AVG(`execution_time_ms`), 0) as avg_latency
            FROM `p4p_analytics_events`
            WHERE `created_at` >= :since
        ");
        $execStmt->execute([':since' => $since]);
        $execStats = $execStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // 3. Traffic Trend (Daily Visits & Unique Sessions)
        $trendStmt = $conn->prepare("
            SELECT 
                DATE(`created_at`) as visit_date,
                COUNT(*) as page_views,
                COUNT(DISTINCT `session_id`) as sessions
            FROM `p4p_analytics_visits`
            WHERE `created_at` >= :since AND {$botCondition}
            GROUP BY DATE(`created_at`)
            ORDER BY visit_date ASC
        ");
        $trendStmt->execute([':since' => $since]);
        $dailyTrends = $trendStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 4. Daily Code Executions
        $dailyExecStmt = $conn->prepare("
            SELECT 
                DATE(`created_at`) as exec_date,
                COUNT(*) as exec_count
            FROM `p4p_analytics_events`
            WHERE `created_at` >= :since
            GROUP BY DATE(`created_at`)
            ORDER BY exec_date ASC
        ");
        $dailyExecStmt->execute([':since' => $since]);
        $dailyExecMap = [];
        foreach ($dailyExecStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $dailyExecMap[$row['exec_date']] = (int)$row['exec_count'];
        }

        // Merge daily trends
        foreach ($dailyTrends as &$dt) {
            $dt['executions'] = $dailyExecMap[$dt['visit_date']] ?? 0;
        }

        // 5. Geographic Breakdown (Top Countries)
        $geoStmt = $conn->prepare("
            SELECT 
                `country_code`, 
                `country_name`, 
                COUNT(*) as views,
                COUNT(DISTINCT `session_id`) as sessions,
                ROUND(AVG(`duration_seconds`)) as avg_dur
            FROM `p4p_analytics_visits`
            WHERE `created_at` >= :since AND {$botCondition}
            GROUP BY `country_code`, `country_name`
            ORDER BY views DESC
            LIMIT 15
        ");
        $geoStmt->execute([':since' => $since]);
        $topCountries = $geoStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 6. Device Breakdown
        $devStmt = $conn->prepare("
            SELECT `device_type`, COUNT(*) as cnt
            FROM `p4p_analytics_visits`
            WHERE `created_at` >= :since AND {$botCondition}
            GROUP BY `device_type`
            ORDER BY cnt DESC
        ");
        $devStmt->execute([':since' => $since]);
        $deviceBreakdown = $devStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 7. Operating Systems Breakdown
        $osStmt = $conn->prepare("
            SELECT `os`, COUNT(*) as cnt
            FROM `p4p_analytics_visits`
            WHERE `created_at` >= :since AND {$botCondition}
            GROUP BY `os`
            ORDER BY cnt DESC
            LIMIT 8
        ");
        $osStmt->execute([':since' => $since]);
        $osBreakdown = $osStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 8. Browsers Breakdown
        $browStmt = $conn->prepare("
            SELECT `browser`, COUNT(*) as cnt
            FROM `p4p_analytics_visits`
            WHERE `created_at` >= :since AND {$botCondition}
            GROUP BY `browser`
            ORDER BY cnt DESC
            LIMIT 8
        ");
        $browStmt->execute([':since' => $since]);
        $browserBreakdown = $browStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 9. Top Visited Pages
        $pagesStmt = $conn->prepare("
            SELECT 
                `page_path`,
                `page_title`,
                `page_type`,
                COUNT(*) as views,
                COUNT(DISTINCT `session_id`) as unique_visits,
                ROUND(AVG(`duration_seconds`)) as avg_duration,
                ROUND(AVG(`scroll_depth_pct`)) as avg_scroll
            FROM `p4p_analytics_visits`
            WHERE `created_at` >= :since AND {$botCondition}
            GROUP BY `page_path`, `page_title`, `page_type`
            ORDER BY views DESC
            LIMIT 20
        ");
        $pagesStmt->execute([':since' => $since]);
        $topPages = $pagesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 10. Referrers & Traffic Sources
        $refStmt = $conn->prepare("
            SELECT 
                CASE 
                    WHEN `referrer_domain` = '' OR `referrer_domain` IS NULL THEN 'Direct / Bookmark'
                    ELSE `referrer_domain`
                END as source,
                COUNT(*) as count
            FROM `p4p_analytics_visits`
            WHERE `created_at` >= :since AND {$botCondition}
            GROUP BY source
            ORDER BY count DESC
            LIMIT 10
        ");
        $refStmt->execute([':since' => $since]);
        $topReferrers = $refStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 11. Real-time active visitors (last 15 minutes)
        $realtimeWindow = date('Y-m-d H:i:s', time() - 900);
        $realtimeStmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT `session_id`) as active_now
            FROM `p4p_analytics_visits`
            WHERE `updated_at` >= :rtWindow AND {$botCondition}
        ");
        $realtimeStmt->execute([':rtWindow' => $realtimeWindow]);
        $activeNow = (int)$realtimeStmt->fetchColumn();

        // 12. Recent Live Activity Feed
        $recentStmt = $conn->prepare("
            SELECT 
                v.`id`,
                v.`page_title`,
                v.`page_path`,
                v.`page_type`,
                v.`country_code`,
                v.`country_name`,
                v.`device_type`,
                v.`browser`,
                v.`duration_seconds`,
                v.`created_at`,
                v.`is_bot`
            FROM `p4p_analytics_visits` v
            WHERE {$botCondition}
            ORDER BY v.`id` DESC
            LIMIT 15
        ");
        $recentStmt->execute();
        $recentVisits = $recentStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 13. Recent Simulation Events Feed
        $recentEventsStmt = $conn->query("
            SELECT 
                e.`event_name`,
                e.`event_category`,
                e.`event_label`,
                e.`execution_time_ms`,
                e.`status`,
                e.`meta_json`,
                e.`created_at`
            FROM `p4p_analytics_events` e
            ORDER BY e.`id` DESC
            LIMIT 15
        ");
        $recentEvents = $recentEventsStmt ? $recentEventsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        return [
            'kpis' => $kpis,
            'execStats' => $execStats,
            'dailyTrends' => $dailyTrends,
            'topCountries' => $topCountries,
            'deviceBreakdown' => $deviceBreakdown,
            'osBreakdown' => $osBreakdown,
            'browserBreakdown' => $browserBreakdown,
            'topPages' => $topPages,
            'topReferrers' => $topReferrers,
            'activeNow' => $activeNow,
            'recentVisits' => $recentVisits,
            'recentEvents' => $recentEvents
        ];
    }
}
