<?php
/**
 * Python4Physics - Resilient Online & Local LaTeX Compiler Bridge
 * Handles LaTeX compilation with full figure/image asset bundling.
 * Supports:
 *  1. Local pdflatex (instant execution if TeX Live / MiKTeX is installed)
 *  2. Cloud compilation via LaTeX.Online API (/data tarball endpoint)
 *  3. Works with or without PHP cURL extension via native HTTP stream fallback
 */

// Avoid caching of compiler responses
header("X-Content-Type-Options: nosniff");
header("Cache-Control: no-cache, no-store, must-revalidate");

@set_time_limit(45);

$code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['code'])) {
    $code = $_GET['code'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    // Fetch document from database by ID if available
    require_once __DIR__ . '/../../site_config.php';
    require_once __DIR__ . '/../../db.php';
    $docId = intval($_GET['id']);
    if (isset($conn) && $conn !== null) {
        try {
            $stmt = $conn->prepare("SELECT content FROM latex WHERE id = ?");
            $stmt->execute([$docId]);
            $row = $stmt->fetch();
            if ($row && !empty($row['content'])) {
                $code = $row['content'];
            }
        } catch (Exception $e) {
            // fallback
        }
    }
}

if (empty(trim($code))) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "ERROR: No LaTeX code provided for compilation.";
    exit;
}

// 1. Identify all referenced images and figures
$imagesToInclude = [];

if (preg_match_all('/\\\\includegraphics(?:\s*\[[^\]]*\])?\s*\{([^}]+)\}/i', $code, $matches)) {
    foreach ($matches[1] as $rawImg) {
        $rawImg = trim($rawImg);
        $cleanName = basename(parse_url($rawImg, PHP_URL_PATH));
        
        // If a full web URL was used, replace it in the LaTeX source with just the local basename
        if ($rawImg !== $cleanName) {
            $code = str_replace($rawImg, $cleanName, $code);
        }

        // Search candidate paths for the image file
        $candidatePaths = [
            __DIR__ . '/' . $cleanName,
            __DIR__ . '/../../assets/images/' . $cleanName,
            __DIR__ . '/../../assets/img/' . $cleanName,
            __DIR__ . '/../../' . $cleanName
        ];

        $foundPath = null;
        foreach ($candidatePaths as $p) {
            if (file_exists($p)) {
                $foundPath = realpath($p);
                break;
            }
        }

        // If not found locally but is a reachable HTTP URL, attempt to download it
        if (!$foundPath && (str_starts_with($rawImg, 'http://') || str_starts_with($rawImg, 'https://'))) {
            $tempDownload = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'p4p_' . md5($rawImg) . '_' . $cleanName;
            $dlData = @file_get_contents($rawImg);
            if ($dlData !== false && strlen($dlData) > 0) {
                file_put_contents($tempDownload, $dlData);
                $foundPath = $tempDownload;
            }
        }

        if ($foundPath && file_exists($foundPath)) {
            $imagesToInclude[$cleanName] = $foundPath;
        }
    }
}

// 2. Prepare temporary directory
$tempId = 'p4p_tex_' . bin2hex(random_bytes(8));
$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $tempId;
@mkdir($tempDir, 0777, true);

$mainTexPath = $tempDir . DIRECTORY_SEPARATOR . 'main.tex';
file_put_contents($mainTexPath, $code);

// Copy images to the temporary folder
foreach ($imagesToInclude as $localName => $srcPath) {
    @copy($srcPath, $tempDir . DIRECTORY_SEPARATOR . $localName);
}

// 3. Check for local pdflatex executable
function find_pdflatex_path() {
    $candidates = [
        'C:\\Program Files\\MiKTeX\\miktex\\bin\\x64\\pdflatex.exe',
        'C:\\Program Files\\MiKTeX 2.9\\miktex\\bin\\x64\\pdflatex.exe',
        '/usr/bin/pdflatex',
        '/usr/local/bin/pdflatex'
    ];
    foreach ($candidates as $c) {
        if (@file_exists($c) && @is_executable($c)) {
            return $c;
        }
    }
    // Check if on system PATH
    $whichCmd = (DIRECTORY_SEPARATOR === '\\') ? 'where pdflatex 2>nul' : 'which pdflatex 2>/dev/null';
    $out = @shell_exec($whichCmd);
    if (!empty($out)) {
        $lines = preg_split('/[\r\n]+/', trim($out));
        if (!empty($lines[0]) && file_exists($lines[0])) {
            return $lines[0];
        }
    }
    return false;
}

$localPdflatex = find_pdflatex_path();
$pdfContent = false;
$httpCode = 0;
$errorMessage = '';

if ($localPdflatex) {
    // Run local pdflatex (super-fast, native)
    $cmd = escapeshellarg($localPdflatex) . ' -interaction=nonstopmode -output-directory=' . escapeshellarg($tempDir) . ' ' . escapeshellarg($mainTexPath);
    @exec($cmd . ' 2>&1', $execOutput, $returnVar);
    
    $generatedPdf = $tempDir . DIRECTORY_SEPARATOR . 'main.pdf';
    if (file_exists($generatedPdf) && filesize($generatedPdf) > 0) {
        $pdfContent = file_get_contents($generatedPdf);
        $httpCode = 200;
    } else {
        $logFile = $tempDir . DIRECTORY_SEPARATOR . 'main.log';
        $logText = file_exists($logFile) ? file_get_contents($logFile) : implode("\n", $execOutput);
        $errorMessage = "Local LaTeX compilation error:\n" . $logText;
        $httpCode = 400;
    }
}

// Fallback to Cloud LaTeX.Online if local compiler wasn't available or failed
if ($httpCode !== 200) {
    $tarPath = $tempDir . DIRECTORY_SEPARATOR . 'bundle.tar';
    $tarGzPath = $tarPath . '.gz';
    $bundleSuccess = false;

    try {
        if (class_exists('PharData')) {
            $tar = new PharData($tarPath);
            $tar->addFile($mainTexPath, 'main.tex');
            foreach ($imagesToInclude as $localName => $srcPath) {
                if (file_exists($srcPath)) {
                    $tar->addFile($srcPath, $localName);
                }
            }
            $tar->compress(Phar::GZ);
            unset($tar);
            @unlink($tarPath);
            $bundleSuccess = file_exists($tarGzPath);
        }
    } catch (Exception $e) {
        $bundleSuccess = false;
    }

    if ($bundleSuccess && file_exists($tarGzPath)) {
        $cloudRes = dispatchMultipartPost('https://latexonline.cc/data?target=main.tex', [], [
            'file' => [
                'path' => $tarGzPath,
                'name' => 'bundle.tar.gz',
                'type' => 'application/gzip'
            ]
        ]);
        $httpCode = $cloudRes['code'];
        $pdfContent = $cloudRes['body'];
        $errorMessage = $cloudRes['error'];
    } else {
        // Direct text compilation fallback
        $compileUrl = 'https://latexonline.cc/compile?text=' . urlencode($code) . '&format=pdf';
        $cloudRes = dispatchGetRequest($compileUrl);
        $httpCode = $cloudRes['code'];
        $pdfContent = $cloudRes['body'];
        $errorMessage = $cloudRes['error'];
    }
}

// Helper: Multipart POST with cURL or Native Stream fallback
function dispatchMultipartPost($url, $fields, $files, $timeout = 40) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $postData = $fields;
        foreach ($files as $name => $f) {
            $postData[$name] = new CURLFile($f['path'], $f['type'], $f['name']);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return ['code' => $code, 'body' => $body, 'error' => $err];
    }

    // Native PHP HTTP Stream Fallback
    $boundary = '--------------------------' . bin2hex(random_bytes(12));
    $payload = '';
    foreach ($fields as $name => $val) {
        $payload .= "--$boundary\r\n";
        $payload .= "Content-Disposition: form-data; name=\"$name\"\r\n\r\n";
        $payload .= "$val\r\n";
    }
    foreach ($files as $name => $f) {
        $payload .= "--$boundary\r\n";
        $payload .= "Content-Disposition: form-data; name=\"$name\"; filename=\"" . $f['name'] . "\"\r\n";
        $payload .= "Content-Type: " . $f['type'] . "\r\n\r\n";
        $payload .= file_get_contents($f['path']) . "\r\n";
    }
    $payload .= "--$boundary--\r\n";

    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: multipart/form-data; boundary=$boundary\r\n" .
                        "Content-Length: " . strlen($payload) . "\r\n",
            'content' => $payload,
            'timeout' => $timeout,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];
    $ctx = stream_context_create($opts);
    $res = @file_get_contents($url, false, $ctx);
    $code = 500;
    if (isset($http_response_header) && !empty($http_response_header)) {
        if (preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $m)) {
            $code = intval($m[1]);
        }
    }
    return ['code' => $code, 'body' => $res, 'error' => ''];
}

// Helper: GET Request
function dispatchGetRequest($url, $timeout = 40) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return ['code' => $code, 'body' => $body, 'error' => $err];
    }
    $opts = [
        'http' => [
            'method' => 'GET',
            'timeout' => $timeout,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];
    $ctx = stream_context_create($opts);
    $body = @file_get_contents($url, false, $ctx);
    $code = 500;
    if (isset($http_response_header) && !empty($http_response_header)) {
        if (preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $m)) {
            $code = intval($m[1]);
        }
    }
    return ['code' => $code, 'body' => $body, 'error' => ''];
}

// 4. Cleanup temporary directory
$filesInTemp = @scandir($tempDir);
if ($filesInTemp) {
    foreach ($filesInTemp as $f) {
        if ($f !== '.' && $f !== '..') {
            @unlink($tempDir . DIRECTORY_SEPARATOR . $f);
        }
    }
}
@rmdir($tempDir);

// 5. Output Response
if ($httpCode === 200 && !empty($pdfContent) && substr($pdfContent, 0, 4) === '%PDF') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="document.pdf"');
    header('Content-Length: ' . strlen($pdfContent));
    echo $pdfContent;
    exit;
} else {
    http_response_code($httpCode >= 400 ? $httpCode : 500);
    header('Content-Type: text/plain; charset=utf-8');
    if (!empty($pdfContent)) {
        echo $pdfContent;
    } else {
        echo !empty($errorMessage) ? $errorMessage : "LaTeX Compilation Failed with status " . $httpCode;
    }
    exit;
}
