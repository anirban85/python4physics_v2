<?php
/**
 * Python4Physics - GNUplot Server Execution Engine
 * Executes GNUplot scripts securely and returns SVG vector output or detailed error logs.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

$raw_code = isset($_POST['code']) ? trim($_POST['code']) : '';

if (empty($raw_code)) {
    echo json_encode(['success' => false, 'error' => 'No GNUplot code provided.']);
    exit;
}

// 1. Locate GNUplot binary
$possible_paths = [
    'C:\Program Files\gnuplot\bin\gnuplot.exe',
    'C:\Program Files (x86)\gnuplot\bin\gnuplot.exe',
    'gnuplot.exe',
    'gnuplot',
    getenv('HOME') . '/gnuplot/bin/gnuplot',
    '/usr/bin/gnuplot',
    '/usr/local/bin/gnuplot'
];

$gnuplot_bin = null;
foreach ($possible_paths as $p) {
    if (file_exists($p) || (PHP_OS_FAMILY === 'Windows' && file_exists($p))) {
        $gnuplot_bin = $p;
        break;
    }
}

// Fallback to checking PATH via where/which
if (!$gnuplot_bin) {
    $check_cmd = (PHP_OS_FAMILY === 'Windows') ? 'where gnuplot 2>nul' : 'which gnuplot 2>/dev/null';
    $found = trim(shell_exec($check_cmd) ?? '');
    if (!empty($found)) {
        $lines = explode("\n", str_replace("\r", "", $found));
        $gnuplot_bin = trim($lines[0]);
    }
}

if (!$gnuplot_bin) {
    echo json_encode([
        'success' => false,
        'error' => 'GNUplot executable not found on server host.'
    ]);
    exit;
}

// 2. Prepare temporary script and output path
$tmp_dir = sys_get_temp_dir();
$unique_id = uniqid('p4p_gp_');
$tmp_gp = $tmp_dir . DIRECTORY_SEPARATOR . $unique_id . '.gp';
$tmp_out = $tmp_dir . DIRECTORY_SEPARATOR . $unique_id . '.out';
$tmp_out_fwd = str_replace('\\', '/', $tmp_out);

// 3. Script Sanitization & Terminal Configuration
// We convert scripts to output to our managed temp file in SVG format for crisp vector rendering.
$lines = explode("\n", str_replace("\r", "", $raw_code));
$cleaned_lines = [];
$has_terminal = false;

foreach ($lines as $line) {
    $trimmed = trim($line);
    
    // Ignore interactive pause commands that could hang the process
    if (preg_match('/^pause\b/i', $trimmed)) {
        continue;
    }
    
    // Check if terminal is declared
    if (preg_match('/^set\s+term(?:inal)?\b/i', $trimmed)) {
        $has_terminal = true;
        // Replace with SVG terminal with pure white background for crisp display and publishing
        $cleaned_lines[] = "set terminal svg size 800,480 font \"Arial,11\" background \"white\"";
        continue;
    }
    
    // Replace custom output destinations with our temporary destination
    if (preg_match('/^set\s+out(?:put)?\b/i', $trimmed)) {
        $cleaned_lines[] = "set output \"{$tmp_out_fwd}\"";
        continue;
    }
    
    $cleaned_lines[] = $line;
}

$wrapped_code = "";
if (!$has_terminal) {
    $wrapped_code .= "set terminal svg size 800,480 font \"Arial,11\" background \"white\"\n";
}
$wrapped_code .= "set output \"{$tmp_out_fwd}\"\n";
$wrapped_code .= implode("\n", $cleaned_lines);
$wrapped_code .= "\nset output\n";

file_put_contents($tmp_gp, $wrapped_code);

// 4. Execution in the GNUplot data directory
$working_dir = realpath(__DIR__);
$cmd = "\"" . $gnuplot_bin . "\" \"" . $tmp_gp . "\"";

$descriptorspec = [
    0 => ["pipe", "r"],
    1 => ["pipe", "w"],
    2 => ["pipe", "w"]
];

$process = proc_open($cmd, $descriptorspec, $pipes, $working_dir);

$stdout = "";
$stderr = "";

if (is_resource($process)) {
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $return_value = proc_close($process);
} else {
    $stdout = shell_exec($cmd . " 2>&1");
}

// 5. Read Output
$file_content = "";
$format = "unknown";
$img_data = "";
$svg_markup = "";

if (file_exists($tmp_out) && filesize($tmp_out) > 50) {
    $file_content = file_get_contents($tmp_out);
    
    // Check if output is SVG markup
    if (str_contains(substr($file_content, 0, 500), '<svg') || str_contains(substr($file_content, 0, 500), '<?xml')) {
        $format = "svg";
        $svg_markup = $file_content;
        
        // Guarantee solid pure white background (#ffffff) directly inside the SVG
        if (!str_contains($svg_markup, 'fill="#ffffff"') && !str_contains($svg_markup, "fill='#ffffff'") && !str_contains($svg_markup, 'fill="white"')) {
            $svg_markup = preg_replace('/(<svg[^>]*>)/i', "$1\n<rect width=\"100%\" height=\"100%\" fill=\"#ffffff\"/>", $svg_markup, 1);
        }
    } else {
        // Binary image (PNG, JPEG, GIF, etc.) - encode as base64 data URI to prevent json_encode UTF-8 failure
        $format = "png";
        $img_data = "data:image/png;base64," . base64_encode($file_content);
    }
}

// Cleanup temporary files
@unlink($tmp_gp);
@unlink($tmp_out);

if (!empty($svg_markup) || !empty($img_data)) {
    echo json_encode([
        'success'   => true,
        'format'    => $format,
        'svg'       => $svg_markup,
        'img_data'  => $img_data,
        'stdout'    => trim($stdout),
        'stderr'    => trim($stderr)
    ], JSON_UNESCAPED_SLASHES);
} else {
    $err_msg = !empty(trim($stderr)) ? trim($stderr) : (!empty(trim($stdout)) ? trim($stdout) : "GNUplot executed but produced no image output.");
    echo json_encode([
        'success'   => false,
        'error'     => $err_msg,
        'stdout'    => trim($stdout),
        'stderr'    => trim($stderr)
    ], JSON_UNESCAPED_SLASHES);
}
