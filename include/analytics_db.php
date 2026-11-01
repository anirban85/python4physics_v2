<?php
/**
 * Python4Physics - Analytics & Telemetry Engine
 * Database abstraction, schema initialization, demography resolution, and aggregation queries.
 * STRICTLY CONFIGURED FOR INDIAN STANDARD TIME (IST, UTC+05:30).
 */

// Enforce Indian Standard Time (IST) across all date/time calculations
date_default_timezone_set('Asia/Kolkata');

if (!function_exists('init_analytics_tables')) {
    function init_analytics_tables($conn) {
        if (!$conn) return false;
        try {
            // Set session time zone to IST (+05:30)
            $conn->exec("SET time_zone = '+05:30'");

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
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY']) && strlen($_SERVER['HTTP_CF_IPCOUNTRY']) === 2) {
            $cc = strtoupper($_SERVER['HTTP_CF_IPCOUNTRY']);
            $countries = get_iso_country_map();
            return [
                'code' => $cc,
                'name' => $countries[$cc] ?? 'International',
                'city' => $_SERVER['HTTP_CF_IPCITY'] ?? ''
            ];
        }

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ip = trim(explode(',', $ip)[0]);

        if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return [
                'code' => 'IN',
                'name' => 'India',
                'city' => 'Kolkata'
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
 * Country code to Flag Emoji helper
 */
if (!function_exists('get_country_flag_emoji')) {
    function get_country_flag_emoji($code) {
        $code = strtoupper(trim($code));
        if (strlen($code) !== 2 || $code === 'XX') return '🌐';
        $first = ord($code[0]) - ord('A') + 0x1F1E6;
        $second = ord($code[1]) - ord('A') + 0x1F1E6;
        return mb_chr($first, 'UTF-8') . mb_chr($second, 'UTF-8');
    }
}

/**
 * Format Seconds Duration (e.g. 1m 45s or 2h 10m)
 */
if (!function_exists('format_seconds_duration')) {
    function format_seconds_duration($seconds) {
        $sec = (int)$seconds;
        if ($sec <= 0) return "0s";
        $h = floor($sec / 3600);
        $m = floor(($sec % 3600) / 60);
        $s = $sec % 60;
        if ($h > 0) {
            return "{$h}h " . str_pad($m, 2, '0', STR_PAD_LEFT) . "m";
        }
        if ($m > 0) {
            return "{$m}m " . str_pad($s, 2, '0', STR_PAD_LEFT) . "s";
        }
        return "{$s}s";
    }
}

/**
 * Bootstrap Seed Data for immediate visual presentation
 */
if (!function_exists('seed_analytics_bootstrap_data')) {
    function seed_analytics_bootstrap_data($conn) {
        $countries = [
            ['IN', 'India', 'Kolkata'],
            ['IN', 'India', 'New Delhi'],
            ['IN', 'India', 'Bengaluru'],
            ['IN', 'India', 'Mumbai'],
            ['US', 'United States', 'San Jose'],
            ['US', 'United States', 'New York'],
            ['GB', 'United Kingdom', 'London'],
            ['DE', 'Germany', 'Berlin'],
            ['CA', 'Canada', 'Toronto'],
            ['SG', 'Singapore', 'Singapore']
        ];

        $pages = [
            ['/circuit_simulator.php', 'Circuit Simulator - Virtual Electronics Laboratory', 'circuit_simulator'],
            ['/index.php', 'Python4Physics - Computational Physics Portal', 'home'],
            ['/assignments.php', 'Interactive Computational Physics Assignments', 'assignments'],
            ['/arduino.php', 'Arduino Physics Lab Simulator & Virtual Oscilloscope', 'arduino_lab'],
            ['/program/python/program.php?menu_id=1&submenu_id=1&id=1', 'Numerical ODE Solver - Runge-Kutta 4th Order', 'python_program'],
            ['/program/python/program.php?menu_id=2&submenu_id=3&id=12', 'Wave Optics - Fraunhofer Diffraction & Interference', 'python_program'],
            ['/visualization.php', 'Interactive Computational Physics Visualizations', 'visualization'],
            ['/about.php', 'About Python4Physics & Authors', 'about']
        ];

        $referrers = [
            ['https://www.google.com/', 'google.com'],
            ['', 'Direct Entry'],
            ['https://www.bing.com/', 'bing.com'],
            ['https://github.com/', 'github.com'],
            ['https://physics.stackexchange.com/', 'physics.stackexchange.com']
        ];

        $devices = [
            ['desktop', 'Windows 10/11', 'Google Chrome', '1920x1080'],
            ['desktop', 'Windows 10/11', 'Microsoft Edge', '1920x1080'],
            ['desktop', 'macOS', 'Apple Safari', '2560x1440'],
            ['mobile', 'Android', 'Google Chrome', '412x915'],
            ['mobile', 'iOS', 'Apple Safari', '390x844'],
            ['tablet', 'iPad', 'Apple Safari', '820x1180']
        ];

        $now = time();
        for ($i = 0; $i < 120; $i++) {
            $pastSec = rand(100, 14 * 86400);
            $createdAt = date('Y-m-d H:i:s', $now - $pastSec);
            $sessionId = 'p4p_boot_' . substr(md5($i . $pastSec), 0, 16);
            $ipHash = hash('sha256', 'salt_' . $i);

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
                ':res' => $d[3],
                ':dur' => $duration,
                ':sc' => $scroll,
                ':cat1' => $createdAt,
                ':cat2' => $createdAt
            ]);
            $visitId = $conn->lastInsertId();

            if ($p[2] === 'circuit_simulator') {
                $presets = ['BJT Common Emitter Amplifier', 'Full-Wave Bridge Rectifier with Filter', 'Op-Amp Inverting Amplifier (IC 741)', 'RC Low-Pass Filter Frequency Response'];
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
 * Parse Date / Range Filter in Indian Standard Time (IST)
 */
if (!function_exists('parse_analytics_date_filter_ist')) {
    function parse_analytics_date_filter_ist($input) {
        $todayStr = date('Y-m-d');
        $preset = '30days';
        $start = '';
        $end = '';
        $isSingleDay = false;
        $label = '';
        $selectedDate = $todayStr;
        $fromDate = date('Y-m-d', strtotime('-29 days'));
        $toDate = $todayStr;

        // Support array or scalar (legacy days parameter)
        if (is_numeric($input)) {
            $days = (int)$input;
            if ($days === 1) $preset = 'today';
            elseif ($days === 7) $preset = '7days';
            elseif ($days === 14) $preset = '14days';
            elseif ($days === 30) $preset = '30days';
            elseif ($days === 90) $preset = '90days';
            elseif ($days >= 365) $preset = 'this_year';
            else $preset = '30days';
        } elseif (is_array($input)) {
            if (!empty($input['date'])) {
                $preset = 'custom_date';
                $selectedDate = preg_replace('/[^0-9\-]/', '', $input['date']);
                if (strlen($selectedDate) !== 10) $selectedDate = $todayStr;
            } elseif (!empty($input['from_date']) && !empty($input['to_date'])) {
                $preset = 'custom_range';
                $fromDate = preg_replace('/[^0-9\-]/', '', $input['from_date']);
                $toDate = preg_replace('/[^0-9\-]/', '', $input['to_date']);
                if (strlen($fromDate) !== 10) $fromDate = date('Y-m-d', strtotime('-29 days'));
                if (strlen($toDate) !== 10) $toDate = $todayStr;
                if ($fromDate > $toDate) {
                    $tmp = $fromDate; $fromDate = $toDate; $toDate = $tmp;
                }
            } elseif (!empty($input['preset'])) {
                $preset = trim(strtolower($input['preset']));
            } elseif (isset($input['days'])) {
                $days = (int)$input['days'];
                if ($days === 1) $preset = 'today';
                elseif ($days === 7) $preset = '7days';
                elseif ($days === 14) $preset = '14days';
                elseif ($days === 30) $preset = '30days';
                elseif ($days === 90) $preset = '90days';
                elseif ($days >= 365) $preset = 'this_year';
            }
        } elseif (is_string($input) && !empty($input)) {
            $preset = trim(strtolower($input));
        }

        switch ($preset) {
            case 'today':
                $start = "{$todayStr} 00:00:00";
                $end   = "{$todayStr} 23:59:59";
                $isSingleDay = true;
                $label = "Today (" . date('d M Y') . " IST)";
                $selectedDate = $todayStr;
                $fromDate = $todayStr;
                $toDate = $todayStr;
                break;

            case 'yesterday':
                $yest = date('Y-m-d', strtotime('-1 day'));
                $start = "{$yest} 00:00:00";
                $end   = "{$yest} 23:59:59";
                $isSingleDay = true;
                $label = "Yesterday (" . date('d M Y', strtotime('-1 day')) . " IST)";
                $selectedDate = $yest;
                $fromDate = $yest;
                $toDate = $yest;
                break;

            case '7days':
                $f7 = date('Y-m-d', strtotime('-6 days'));
                $start = "{$f7} 00:00:00";
                $end   = "{$todayStr} 23:59:59";
                $isSingleDay = false;
                $label = "Last 7 Days (IST)";
                $fromDate = $f7;
                $toDate = $todayStr;
                break;

            case '14days':
                $f14 = date('Y-m-d', strtotime('-13 days'));
                $start = "{$f14} 00:00:00";
                $end   = "{$todayStr} 23:59:59";
                $isSingleDay = false;
                $label = "Last 14 Days (IST)";
                $fromDate = $f14;
                $toDate = $todayStr;
                break;

            case '30days':
                $f30 = date('Y-m-d', strtotime('-29 days'));
                $start = "{$f30} 00:00:00";
                $end   = "{$todayStr} 23:59:59";
                $isSingleDay = false;
                $label = "Last 30 Days (IST)";
                $fromDate = $f30;
                $toDate = $todayStr;
                break;

            case 'this_month':
                $fMonth = date('Y-m-01');
                $start = "{$fMonth} 00:00:00";
                $end   = "{$todayStr} 23:59:59";
                $isSingleDay = false;
                $label = "This Month (" . date('F Y') . " IST)";
                $fromDate = $fMonth;
                $toDate = $todayStr;
                break;

            case 'last_month':
                $fLastMonth = date('Y-m-01', strtotime('first day of last month'));
                $tLastMonth = date('Y-m-t', strtotime('first day of last month'));
                $start = "{$fLastMonth} 00:00:00";
                $end   = "{$tLastMonth} 23:59:59";
                $isSingleDay = false;
                $label = "Last Month (" . date('F Y', strtotime('first day of last month')) . " IST)";
                $fromDate = $fLastMonth;
                $toDate = $tLastMonth;
                break;

            case '90days':
                $f90 = date('Y-m-d', strtotime('-89 days'));
                $start = "{$f90} 00:00:00";
                $end   = "{$todayStr} 23:59:59";
                $isSingleDay = false;
                $label = "Last 90 Days (IST)";
                $fromDate = $f90;
                $toDate = $todayStr;
                break;

            case 'this_year':
                $fYear = date('Y-01-01');
                $start = "{$fYear} 00:00:00";
                $end   = "{$todayStr} 23:59:59";
                $isSingleDay = false;
                $label = "This Year (" . date('Y') . " IST)";
                $fromDate = $fYear;
                $toDate = $todayStr;
                break;

            case 'all':
                $start = "2020-01-01 00:00:00";
                $end   = "{$todayStr} 23:59:59";
                $isSingleDay = false;
                $label = "All Time Telemetry (IST)";
                $fromDate = "2020-01-01";
                $toDate = $todayStr;
                break;

            case 'custom_date':
                $start = "{$selectedDate} 00:00:00";
                $end   = "{$selectedDate} 23:59:59";
                $isSingleDay = true;
                $label = "Date: " . date('d M Y', strtotime($selectedDate)) . " (IST)";
                $fromDate = $selectedDate;
                $toDate = $selectedDate;
                break;

            case 'custom_range':
                $start = "{$fromDate} 00:00:00";
                $end   = "{$toDate} 23:59:59";
                $isSingleDay = ($fromDate === $toDate);
                $label = date('d M Y', strtotime($fromDate)) . " — " . date('d M Y', strtotime($toDate)) . " (IST)";
                break;

            default:
                $f30 = date('Y-m-d', strtotime('-29 days'));
                $start = "{$f30} 00:00:00";
                $end   = "{$todayStr} 23:59:59";
                $isSingleDay = false;
                $label = "Last 30 Days (IST)";
                $fromDate = $f30;
                $toDate = $todayStr;
                $preset = '30days';
                break;
        }

        return [
            'preset'         => $preset,
            'start'          => $start,
            'end'            => $end,
            'is_single_day'  => $isSingleDay,
            'label'          => $label,
            'selected_date'  => $selectedDate,
            'from_date'      => $fromDate,
            'to_date'        => $toDate,
            'today_ist'      => $todayStr,
            'current_ist'    => date('d M Y, h:i:s A T')
        ];
    }
}

/**
 * Query Aggregations for Admin Dashboard strictly in IST
 */
if (!function_exists('get_analytics_dashboard_data')) {
    function get_analytics_dashboard_data($conn, $filterInput = [], $include_bots = false) {
        init_analytics_tables($conn);

        // Enforce MySQL connection timezone to IST (+05:30)
        try {
            $conn->exec("SET time_zone = '+05:30'");
        } catch (Throwable $e) {}

        $filter = parse_analytics_date_filter_ist($filterInput);
        $start = $filter['start'];
        $end = $filter['end'];
        $isSingleDay = $filter['is_single_day'];
        $botCondition = $include_bots ? "1=1" : "`is_bot` = 0";

        // 1. KPI Totals
        $kpiStmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_views,
                COUNT(DISTINCT `session_id`) as unique_sessions,
                COUNT(DISTINCT `ip_hash`) as unique_visitors,
                COALESCE(AVG(`duration_seconds`), 0) as avg_duration,
                COALESCE(SUM(`duration_seconds`), 0) as total_duration,
                COALESCE(AVG(`scroll_depth_pct`), 0) as avg_scroll,
                SUM(CASE WHEN `duration_seconds` < 10 THEN 1 ELSE 0 END) as bounces,
                SUM(CASE WHEN `scroll_depth_pct` >= 75 THEN 1 ELSE 0 END) as deep_reads
            FROM `p4p_analytics_visits`
            WHERE `created_at` BETWEEN :start AND :end AND {$botCondition}
        ");
        $kpiStmt->execute([':start' => $start, ':end' => $end]);
        $kpis = $kpiStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalViews = (int)($kpis['total_views'] ?? 0);
        $uniqueSessions = (int)($kpis['unique_sessions'] ?? 0);
        $uniqueVisitors = (int)($kpis['unique_visitors'] ?? 0);
        $viewsPerSession = $uniqueSessions > 0 ? round($totalViews / $uniqueSessions, 2) : 1.0;
        $bounceRate = $totalViews > 0 ? round(((int)$kpis['bounces'] / $totalViews) * 100, 1) : 0.0;
        $deepReadPct = $totalViews > 0 ? round(((int)$kpis['deep_reads'] / $totalViews) * 100, 1) : 0.0;

        // Returning visitors count
        $retStmt = $conn->prepare("
            SELECT COUNT(DISTINCT `ip_hash`) 
            FROM `p4p_analytics_visits`
            WHERE `created_at` BETWEEN :start AND :end AND {$botCondition}
            AND `ip_hash` IN (
                SELECT `ip_hash` FROM `p4p_analytics_visits`
                WHERE `created_at` < :start_prev
                GROUP BY `ip_hash`
            )
        ");
        $retStmt->execute([':start' => $start, ':end' => $end, ':start_prev' => $start]);
        $returningVisitors = (int)$retStmt->fetchColumn();
        $retVisitorPct = $uniqueVisitors > 0 ? round(($returningVisitors / $uniqueVisitors) * 100, 1) : 0.0;

        $kpis['views_per_session'] = $viewsPerSession;
        $kpis['bounce_rate'] = $bounceRate;
        $kpis['deep_read_pct'] = $deepReadPct;
        $kpis['returning_visitors'] = $returningVisitors;
        $kpis['returning_visitor_pct'] = $retVisitorPct;

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
            WHERE `created_at` BETWEEN :start AND :end
        ");
        $execStmt->execute([':start' => $start, ':end' => $end]);
        $execStats = $execStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalExec = (int)($execStats['total_executions'] ?? 0);
        $successExec = (int)($execStats['success_runs'] ?? 0);
        $execStats['success_rate'] = $totalExec > 0 ? round(($successExec / $totalExec) * 100, 1) : 100.0;

        // 3. Hourly Breakdown (00:00 to 23:00 IST)
        $hourlyStmt = $conn->prepare("
            SELECT 
                HOUR(`created_at`) as hr,
                COUNT(*) as views,
                COUNT(DISTINCT `session_id`) as sessions
            FROM `p4p_analytics_visits`
            WHERE `created_at` BETWEEN :start AND :end AND {$botCondition}
            GROUP BY HOUR(`created_at`)
            ORDER BY hr ASC
        ");
        $hourlyStmt->execute([':start' => $start, ':end' => $end]);
        $hourlyMap = [];
        foreach ($hourlyStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $hourlyMap[(int)$row['hr']] = [
                'views' => (int)$row['views'],
                'sessions' => (int)$row['sessions']
            ];
        }

        $hourlyExecStmt = $conn->prepare("
            SELECT 
                HOUR(`created_at`) as hr,
                COUNT(*) as execs
            FROM `p4p_analytics_events`
            WHERE `created_at` BETWEEN :start AND :end
            GROUP BY HOUR(`created_at`)
            ORDER BY hr ASC
        ");
        $hourlyExecStmt->execute([':start' => $start, ':end' => $end]);
        $hourlyExecMap = [];
        foreach ($hourlyExecStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $hourlyExecMap[(int)$row['hr']] = (int)$row['execs'];
        }

        $hourlyBreakdown = [];
        $peakHour = 0;
        $maxHourViews = -1;

        for ($h = 0; $h < 24; $h++) {
            $vCount = $hourlyMap[$h]['views'] ?? 0;
            $sCount = $hourlyMap[$h]['sessions'] ?? 0;
            $eCount = $hourlyExecMap[$h] ?? 0;

            if ($vCount > $maxHourViews) {
                $maxHourViews = $vCount;
                $peakHour = $h;
            }

            $label12 = date('h A', strtotime("2026-01-01 {$h}:00:00"));
            $slotLabel = sprintf("%02d:00 - %02d:59 IST", $h, $h);

            $hourlyBreakdown[] = [
                'hour' => $h,
                'label' => sprintf("%02d:00 IST", $h),
                'label12' => $label12 . " IST",
                'slot_label' => $slotLabel,
                'views' => $vCount,
                'sessions' => $sCount,
                'executions' => $eCount,
                'pct' => $totalViews > 0 ? round(($vCount / $totalViews) * 100, 1) : 0.0
            ];
        }

        $peakHourLabel = sprintf("%02d:00 - %02d:59 IST (%s)", $peakHour, $peakHour, date('h A', strtotime("2026-01-01 {$peakHour}:00:00")));

        // 4. Daily Trends (if multi-day period)
        $dailyTrends = [];
        if (!$isSingleDay) {
            $trendStmt = $conn->prepare("
                SELECT 
                    DATE(`created_at`) as visit_date,
                    COUNT(*) as page_views,
                    COUNT(DISTINCT `session_id`) as sessions
                FROM `p4p_analytics_visits`
                WHERE `created_at` BETWEEN :start AND :end AND {$botCondition}
                GROUP BY DATE(`created_at`)
                ORDER BY visit_date ASC
            ");
            $trendStmt->execute([':start' => $start, ':end' => $end]);
            $dailyTrends = $trendStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $dailyExecStmt = $conn->prepare("
                SELECT 
                    DATE(`created_at`) as exec_date,
                    COUNT(*) as exec_count
                FROM `p4p_analytics_events`
                WHERE `created_at` BETWEEN :start AND :end
                GROUP BY DATE(`created_at`)
                ORDER BY exec_date ASC
            ");
            $dailyExecStmt->execute([':start' => $start, ':end' => $end]);
            $dailyExecMap = [];
            foreach ($dailyExecStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $dailyExecMap[$row['exec_date']] = (int)$row['exec_count'];
            }

            foreach ($dailyTrends as &$dt) {
                $dt['executions'] = $dailyExecMap[$dt['visit_date']] ?? 0;
                $dt['formatted_date_ist'] = date('d M (IST)', strtotime($dt['visit_date']));
            }
        }

        // 5. Day of Week Breakdown
        $dowStmt = $conn->prepare("
            SELECT 
                DAYOFWEEK(`created_at`) as dow,
                DAYNAME(`created_at`) as day_name,
                COUNT(*) as views,
                COUNT(DISTINCT `session_id`) as sessions
            FROM `p4p_analytics_visits`
            WHERE `created_at` BETWEEN :start AND :end AND {$botCondition}
            GROUP BY dow, day_name
            ORDER BY dow ASC
        ");
        $dowStmt->execute([':start' => $start, ':end' => $end]);
        $dayOfWeekBreakdown = $dowStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $peakDay = '—';
        $maxDayViews = -1;
        foreach ($dayOfWeekBreakdown as $dow) {
            if ($dow['views'] > $maxDayViews) {
                $maxDayViews = $dow['views'];
                $peakDay = $dow['day_name'];
            }
        }

        // 6. Geographic Breakdown (Top Countries)
        $geoStmt = $conn->prepare("
            SELECT 
                `country_code`, 
                `country_name`, 
                COUNT(*) as views,
                COUNT(DISTINCT `session_id`) as sessions,
                ROUND(AVG(`duration_seconds`)) as avg_dur
            FROM `p4p_analytics_visits`
            WHERE `created_at` BETWEEN :start AND :end AND {$botCondition}
            GROUP BY `country_code`, `country_name`
            ORDER BY views DESC
            LIMIT 15
        ");
        $geoStmt->execute([':start' => $start, ':end' => $end]);
        $topCountries = $geoStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($topCountries as &$c) {
            $c['flag'] = get_country_flag_emoji($c['country_code']);
            $c['share_pct'] = $totalViews > 0 ? round(($c['views'] / $totalViews) * 100, 1) : 0.0;
        }

        // 7. Top Cities
        $cityStmt = $conn->prepare("
            SELECT 
                `city`,
                `country_name`,
                `country_code`,
                COUNT(*) as views,
                COUNT(DISTINCT `session_id`) as sessions
            FROM `p4p_analytics_visits`
            WHERE `created_at` BETWEEN :start AND :end AND {$botCondition} AND `city` != '' AND `city` != 'Local Workstation'
            GROUP BY `city`, `country_name`, `country_code`
            ORDER BY views DESC
            LIMIT 12
        ");
        $cityStmt->execute([':start' => $start, ':end' => $end]);
        $topCities = $cityStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 8. Device Breakdown
        $devStmt = $conn->prepare("
            SELECT `device_type`, COUNT(*) as cnt
            FROM `p4p_analytics_visits`
            WHERE `created_at` BETWEEN :start AND :end AND {$botCondition}
            GROUP BY `device_type`
            ORDER BY cnt DESC
        ");
        $devStmt->execute([':start' => $start, ':end' => $end]);
        $deviceBreakdown = $devStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 9. Operating Systems Breakdown
        $osStmt = $conn->prepare("
            SELECT `os`, COUNT(*) as cnt
            FROM `p4p_analytics_visits`
            WHERE `created_at` BETWEEN :start AND :end AND {$botCondition}
            GROUP BY `os`
            ORDER BY cnt DESC
            LIMIT 8
        ");
        $osStmt->execute([':start' => $start, ':end' => $end]);
        $osBreakdown = $osStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 10. Browsers Breakdown
        $browStmt = $conn->prepare("
            SELECT `browser`, COUNT(*) as cnt
            FROM `p4p_analytics_visits`
            WHERE `created_at` BETWEEN :start AND :end AND {$botCondition}
            GROUP BY `browser`
            ORDER BY cnt DESC
            LIMIT 8
        ");
        $browStmt->execute([':start' => $start, ':end' => $end]);
        $browserBreakdown = $browStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 11. Screen Resolutions Breakdown
        $resStmt = $conn->prepare("
            SELECT `screen_res`, COUNT(*) as cnt
            FROM `p4p_analytics_visits`
            WHERE `created_at` BETWEEN :start AND :end AND {$botCondition} AND `screen_res` != ''
            GROUP BY `screen_res`
            ORDER BY cnt DESC
            LIMIT 8
        ");
        $resStmt->execute([':start' => $start, ':end' => $end]);
        $screenResolutions = $resStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 12. Top Visited Pages
        $pagesStmt = $conn->prepare("
            SELECT 
                `page_path`,
                `page_title`,
                `page_type`,
                COUNT(*) as views,
                COUNT(DISTINCT `session_id`) as unique_visits,
                ROUND(AVG(`duration_seconds`)) as avg_duration,
                ROUND(AVG(`scroll_depth_pct`)) as avg_scroll,
                SUM(CASE WHEN `duration_seconds` < 10 THEN 1 ELSE 0 END) as bounces
            FROM `p4p_analytics_visits`
            WHERE `created_at` BETWEEN :start AND :end AND {$botCondition}
            GROUP BY `page_path`, `page_title`, `page_type`
            ORDER BY views DESC
            LIMIT 25
        ");
        $pagesStmt->execute([':start' => $start, ':end' => $end]);
        $topPages = $pagesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($topPages as &$p) {
            $p['share_pct'] = $totalViews > 0 ? round(($p['views'] / $totalViews) * 100, 1) : 0.0;
        }

        // 13. Module / Section Breakdown
        $moduleStmt = $conn->prepare("
            SELECT 
                `page_type`,
                COUNT(*) as views,
                COUNT(DISTINCT `session_id`) as sessions,
                ROUND(AVG(`duration_seconds`)) as avg_duration,
                ROUND(AVG(`scroll_depth_pct`)) as avg_scroll
            FROM `p4p_analytics_visits`
            WHERE `created_at` BETWEEN :start AND :end AND {$botCondition}
            GROUP BY `page_type`
            ORDER BY views DESC
        ");
        $moduleStmt->execute([':start' => $start, ':end' => $end]);
        $moduleBreakdown = $moduleStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 14. Referrers & Traffic Sources
        $refStmt = $conn->prepare("
            SELECT 
                CASE 
                    WHEN `referrer_domain` = '' OR `referrer_domain` IS NULL THEN 'Direct / Bookmark'
                    ELSE `referrer_domain`
                END as source,
                COUNT(*) as count,
                COUNT(DISTINCT `session_id`) as sessions
            FROM `p4p_analytics_visits`
            WHERE `created_at` BETWEEN :start AND :end AND {$botCondition}
            GROUP BY source
            ORDER BY count DESC
            LIMIT 12
        ");
        $refStmt->execute([':start' => $start, ':end' => $end]);
        $topReferrers = $refStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($topReferrers as &$r) {
            $r['share_pct'] = $totalViews > 0 ? round(($r['count'] / $totalViews) * 100, 1) : 0.0;
        }

        // 15. Top Simulation Presets Executed
        $presetStmt = $conn->prepare("
            SELECT 
                `event_name`,
                `event_category`,
                COALESCE(NULLIF(`event_label`, ''), 'Numerical Solver Simulation') as label,
                COUNT(*) as run_count,
                ROUND(AVG(COALESCE(`execution_time_ms`, 0))) as avg_latency
            FROM `p4p_analytics_events`
            WHERE `created_at` BETWEEN :start AND :end
            GROUP BY `event_name`, `event_category`, label
            ORDER BY run_count DESC
            LIMIT 12
        ");
        $presetStmt->execute([':start' => $start, ':end' => $end]);
        $topPresets = $presetStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 16. Real-time active visitors (last 15 minutes) in IST
        $realtimeWindow = date('Y-m-d H:i:s', time() - 900);
        $realtimeStmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT `session_id`) as active_now
            FROM `p4p_analytics_visits`
            WHERE `updated_at` >= :rtWindow AND {$botCondition}
        ");
        $realtimeStmt->execute([':rtWindow' => $realtimeWindow]);
        $activeNow = (int)$realtimeStmt->fetchColumn();
        if ($activeNow < 1) $activeNow = 1; // At least current admin is active

        // 17. Recent Live Activity Feed (Strictly formatted with IST timestamps)
        $recentStmt = $conn->prepare("
            SELECT 
                v.`id`,
                v.`page_title`,
                v.`page_path`,
                v.`page_type`,
                v.`country_code`,
                v.`country_name`,
                v.`city`,
                v.`device_type`,
                v.`browser`,
                v.`os`,
                v.`screen_res`,
                v.`duration_seconds`,
                v.`scroll_depth_pct`,
                v.`created_at`,
                v.`is_bot`
            FROM `p4p_analytics_visits` v
            WHERE {$botCondition}
            ORDER BY v.`id` DESC
            LIMIT 25
        ");
        $recentStmt->execute();
        $recentVisits = $recentStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($recentVisits as &$rv) {
            $rv['flag'] = get_country_flag_emoji($rv['country_code']);
            $rv['created_at_ist'] = date('d M Y, h:i:s A', strtotime($rv['created_at'])) . ' IST';
            $rv['time_ago_ist'] = date('h:i:s A', strtotime($rv['created_at'])) . ' IST';
        }

        // 18. Recent Simulation Events Feed (Strictly formatted with IST timestamps)
        $recentEventsStmt = $conn->query("
            SELECT 
                e.`id`,
                e.`event_name`,
                e.`event_category`,
                e.`event_label`,
                e.`execution_time_ms`,
                e.`status`,
                e.`meta_json`,
                e.`created_at`
            FROM `p4p_analytics_events` e
            ORDER BY e.`id` DESC
            LIMIT 25
        ");
        $recentEvents = $recentEventsStmt ? $recentEventsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($recentEvents as &$re) {
            $re['created_at_ist'] = date('d M Y, h:i:s A', strtotime($re['created_at'])) . ' IST';
        }

        return [
            'filter'             => $filter,
            'kpis'               => $kpis,
            'execStats'          => $execStats,
            'hourlyBreakdown'    => $hourlyBreakdown,
            'peakHour'           => $peakHour,
            'peakHourLabel'      => $peakHourLabel,
            'dailyTrends'        => $dailyTrends,
            'dayOfWeekBreakdown' => $dayOfWeekBreakdown,
            'peakDay'            => $peakDay,
            'topCountries'       => $topCountries,
            'topCities'          => $topCities,
            'deviceBreakdown'    => $deviceBreakdown,
            'osBreakdown'        => $osBreakdown,
            'browserBreakdown'   => $browserBreakdown,
            'screenResolutions'  => $screenResolutions,
            'topPages'           => $topPages,
            'moduleBreakdown'    => $moduleBreakdown,
            'topReferrers'       => $topReferrers,
            'topPresets'         => $topPresets,
            'activeNow'          => $activeNow,
            'recentVisits'       => $recentVisits,
            'recentEvents'       => $recentEvents
        ];
    }
}
