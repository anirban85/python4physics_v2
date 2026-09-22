<?php
/**
 * program.php (Pyodide client-side runner)
 * - Replaces server-side Python execution with Pyodide (runs in browser)
 * - Keeps styles, CodeMirror editors, layout and DB program list intact
 * - IMPORTANT: This disables server-side code execution (safer on shared hosts)
 */

// ---------------- INCLUDES ----------------
include '../../site_config.php';
include '../../db.php'; // must provide $conn (PDO)

// ---------------- SESSION (minimal) ----------------
session_start();

// ---------------- INPUTS ----------------
$menu_id = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 1;
$submenu_id = isset($_GET['submenu_id']) ? intval($_GET['submenu_id']) : 1;

include 'menu.php';
$header_text = 'Interactive Python Programs';
$menu_text = $menu_titles[$menu_id] ?? 'Menu';
$submenu_titles_for_menu = $sub_menu_titles[$menu_id] ?? [];
$header_text = $submenu_titles_for_menu[$submenu_id] ?? $header_text;

// ---------------- FETCH PROGRAMS (existing DB logic) ----------------
$sql = "SELECT id, program_id, content, algo, explanation FROM python WHERE menu_id = ? AND submenu_id = ? ORDER BY program_id";
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute([$menu_id, $submenu_id]);
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error executing query: " . $e->getMessage());
}

// ---------------- HTML / UI ----------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- keep GTM and defer heavy ad loading to reduce initial pressure -->
    <!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-NRDD47HL');</script>
<!-- End Google Tag Manager -->
<script>
function loadAdsDeferred(){
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1857733312974112';
    s.crossOrigin = 'anonymous';
    document.body.appendChild(s);
}
if ('requestIdleCallback' in window) requestIdleCallback(loadAdsDeferred, {timeout:2000}); else setTimeout(loadAdsDeferred, 2000);
</script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Master computational physics with Python (Client-side execution via Pyodide)">
    <meta name="keywords" content="<?php echo htmlspecialchars($header_text); ?>">
    <title><?php echo htmlspecialchars($menu_text); ?>: <?php echo htmlspecialchars($header_text); ?></title>
    <link rel="icon" href="python.ico" type="image/x-icon">
    <link rel="shortcut icon" href="python.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/theme/monokai.min.css">
    <style>
        /* (kept identical to your original styles) */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f0f8ff;
            color: #333;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #333;
            color: #f7f7f7;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        main {
            display: flex;
            flex-wrap: wrap;
            padding: 10px;
            background-color: #ffffff;
            margin: 0 auto;
            max-width: calc(100% - 20px);
        }
        .sidebar {
            width: 250px;
            padding: 15px;
            background-color: #343a40;
            color: #ffffff;
            border-right: 1px solid #ddd;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
            border-radius: 8px 0 0 8px;
            position: sticky;
            top: 20px;
            height: calc(100vh - 40px);
        }
        .content {
            flex: 1;
            padding: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .program {
            flex: 1;
            min-width: 340px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-sizing: border-box;
            overflow: hidden;
        }
        textarea {
            width: calc(100% - 5px);
            height: 200px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 18px;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            resize: vertical;
            background-color: #fdfdfd;
        }
        button {
            background-color: #007bff;
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            cursor: pointer;
            font-size: 16px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        pre {
            background-color: #f1f1f1;
            padding: 15px;
            border-left: 4px solid #007bff;
            white-space: pre-wrap;
            word-wrap: break-word;
            border-radius: 4px;
            overflow-y: auto;
            font-size: 18px;
        }
        h1 {
            margin-top: 0;
            font-size: 20px;
            color: #333;
        }
        img {
            max-width: 100%;
            height: auto;
            display: block;
            border-radius: 4px;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        .sidebar ul li {
            position: relative;
            padding-left: 30px;
            margin-bottom: 10px;
        }
        .sidebar ul li::before {
            content: '\2714';
            position: absolute;
            left: 0;
            color: #ffffff;
            font-size: 20px;
            line-height: 1;
            top: 50%;
            transform: translateY(-50%);
        }
        .sidebar ul li a {
            text-decoration: none;
            color: #ffffff;
            font-size: 16px;
        }
        .sidebar ul li a:hover {
            text-decoration: underline;
            color: #ffffff;
        }
        .sidebar ul li.active::before {
            color: #28a745;
        }
        .CodeMirror {
            font-size: 16px;
            max-height: 900px;
            min-width: 340px;
            overflow: hidden;
        }
        @media only screen and (max-width: 768px) and (orientation: portrait) {
            .sidebar {
                position: static;
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #ddd;
                border-radius: 0;
                height: auto;
                box-shadow: none;
            }
            .CodeMirror {
                max-width: 280px;
                font-size: 11px;
                padding: 4px;
            }
            .content {
                padding: 5px;
            }
        }
        @media only screen and (max-width: 1268px) and (orientation: landscape) {
            .sidebar {
                position: static;
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #ddd;
                border-radius: 0;
                height: auto;
                box-shadow: none;
            }
            .CodeMirror {
                min-width: 340px;
                max-width: 550px;
                font-size: 12px;
                padding: 4px;
            }
            .program {
                flex: 1;
                min-width: 350px;
                max-width: 550px;
            }
            .content {
                padding: 5px;
            }
        }
        @media only screen and (min-width: 1270px) and (orientation: landscape) {
            .CodeMirror {
                flex: 1;
                min-width: 400px;
                max-width: 1800px;
                font-size: 15px;
                padding: 4px;
            }
            .content {
                padding: 5px;
            }
            .program {
                flex: 1;
                min-width: 400px;
                max-width: 1800px;
                background-color: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                box-sizing: border-box;
                overflow: hidden;
            }
        }
        @media only screen and (min-width: 1550px) and (orientation: landscape) {
            .CodeMirror {
                flex: 1;
                min-width: 600px;
                max-width: 1800px;
                font-size: 18px;
                padding: 4px;
            }
            .content {
                padding: 5px;
            }
            .program {
                flex: 1;
                min-width: 600px;
                max-width: 1800px;
                background-color: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                box-sizing: border-box;
                overflow: hidden;
            }
        }
       /* CSS for the loading spinner */
        #loading-spinner {
            display: none; /* Hidden by default */
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000; /* Ensure it's on top of everything */
            width: 50px;
            height: 50px;
            border: 6px solid #f3f3f3;
            border-radius: 50%;
            border-top: 6px solid #3498db;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .hidden {
            display: none;
        }

        .pyodide-warning {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
            border: 1px solid #ffeeba;
        }

    </style>

    <!-- CodeMirror JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/addon/selection/active-line.min.js"></script>

    <!-- Pyodide loader (we'll load lazily via JS when user first runs code) -->
</head>
<body>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NRDD47HL" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php include '../../include/navbar.php'; ?>
<header class='secTitle'><?php echo htmlspecialchars($menu_text); ?>: <?php echo htmlspecialchars($header_text); ?></header>

<main>
    <div id="loading-spinner"></div>
    <div class="sidebar"><?php include 'sidebar.php'; ?></div>
    <div class="content">
        <div class="pyodide-warning">
            <strong>Client-side execution:</strong> Python runs in your browser using <em>Pyodide</em>. This is safe for the server (no server-side execution), but heavy or infinite loops may still freeze your browser tab. If you need long-running or heavy jobs, use a server-side sandboxed worker on a VPS.
        </div>

        <?php
        foreach ($programs as $program) {
            $id = $program['id'];
            $program_id = $program['program_id'];
            $content = $program['content'];
            $algo = $program['algo'];
            $explanation = $program['explanation'];
            $code_name = "python_code{$id}";
            $run_name = "run_code_{$id}";
            $output_id = "output{$id}";

            echo "<div class='program'>\n";
            echo "<h1>$header_text: Program $program_id</h1>\n";
            echo "<a> $algo </a>";
            // Note: form is kept for UI but submission is hijacked by JS (no POST)
            echo "<form onsubmit='return false;'>\n";
            echo "<textarea name='" . htmlspecialchars($code_name) . "' id='" . htmlspecialchars($code_name) . "'>" . htmlspecialchars($content) . "</textarea>\n";
            echo "<button type='button' class='run-btn' data-target='" . htmlspecialchars($code_name) . "' data-output='" . htmlspecialchars($output_id) . "'>Run Code</button>\n";
            echo "</form>\n";
            echo "</div>\n";

            echo "<div class='program'>\n";
            echo "<h1 id='" . htmlspecialchars($output_id) . "'>Output " . htmlspecialchars($program_id) . "</h1>\n";
            echo "<div class='exec-output' id='" . htmlspecialchars($output_id) . "_console'></div>\n";
            echo "<div class='exec-images' id='" . htmlspecialchars($output_id) . "_images'></div>\n";
            echo "</div>\n";
            echo "<div style='padding:15px;'><a> $explanation </a></div>";
        }
        ?>
        <script>
            /***********************
             * Client-side Pyodide runner
             ***********************/

            // CodeMirror setup
            const editors = {};
            function initCodeMirror(){
                document.querySelectorAll('textarea').forEach(function(textarea){
                    var editor = CodeMirror.fromTextArea(textarea, { mode:'python', theme:'monokai', lineNumbers:true, styleActiveLine:true, matchBrackets:true, lineWrapping:true });
                    editor.on('change', function(){ adjustEditorHeight(editor); });
                    adjustEditorHeight(editor);
                    editors[textarea.id] = editor;
                });
            }
            function adjustEditorHeight(editor){ var lineCount = editor.lineCount(); var lineHeight = editor.defaultTextHeight(); var newHeight = Math.min(lineCount * lineHeight + 70, 500); editor.getWrapperElement().style.height = newHeight + 'px'; }

            // Pyodide state
            let pyodideReady = false;
            let pyodide = null;
            let pyodideLoading = false;

            // Lazy-load pyodide and required packages
            async function ensurePyodide() {
                if (pyodideReady) return;
                if (pyodideLoading) {
                    // wait until loaded
                    while (!pyodideReady) await new Promise(r => setTimeout(r, 100));
                    return;
                }
                pyodideLoading = true;
                showSpinner(true);
                try {
                    // load pyodide from CDN
                    // note: pick a stable release; using 0.24.x (adjust if needed)
                    const indexUrl = "https://cdn.jsdelivr.net/pyodide/v0.24.0/full/pyodide.js";
                    await new Promise((resolve, reject) => {
                        const s = document.createElement('script');
                        s.src = indexUrl;
                        s.onload = resolve;
                        s.onerror = reject;
                        document.head.appendChild(s);
                    });
                    pyodide = await loadPyodide({indexURL: "https://cdn.jsdelivr.net/pyodide/v0.24.0/full/"});

                    // load micropip if needed and packages: matplotlib, numpy
                    await pyodide.loadPackage(['micropip']);
                    const micropip = pyodide.pyimport('micropip');
                    // install matplotlib and numpy (prebuilt wheels available)
                    await pyodide.loadPackage("micropip");   // if needed
await pyodide.loadPackage("numpy");
await pyodide.loadPackage("matplotlib");
                    // Provide helper code injection into Python environment:
                    // - capture stdout/stderr into JS callbacks
                    // - wrap matplotlib.pyplot.savefig to capture images into an in-memory list
                    await pyodide.runPythonAsync(`
import sys, io, base64
from js import console

# holder for images: list of base64 PNG strings
_pyodide_captured_images = []

def _pyodide_save_image_bytes(buf_bytes):
    import base64
    s = base64.b64encode(buf_bytes).decode('ascii')
    _pyodide_captured_images.append(s)

# Replace matplotlib's savefig to capture PNG bytes to _pyodide_captured_images
try:
    import matplotlib
    matplotlib.use('Agg')
    import matplotlib.pyplot as plt
    from io import BytesIO
    _orig_savefig = plt.savefig
    def _savefig_capture(*args, **kwargs):
        buf = BytesIO()
        _orig_savefig(*args, **{**kwargs, 'dpi': kwargs.get('dpi', None) , 'bbox_inches': kwargs.get('bbox_inches', None), 'format': 'png'})
        # Some backends write to file - ensure we use BytesIO below by calling figure.savefig on buffer
        # To be safe, call figure.savefig on buffer explicitly if possible
        try:
            fig = plt.gcf()
            buf = BytesIO()
            fig.savefig(buf, format='png')
            buf.seek(0)
            _pyodide_save_image_bytes(buf.read())
        except Exception as e:
            # fallback - try saving via original if it returns a file name (rare)
            console.warn('savefig capture fallback:', str(e))
    plt.savefig = _savefig_capture
except Exception as e:
    # matplotlib not available - some programs won't have plotting ability
    console.warn('matplotlib not available or capture wrapper failed:', str(e))
`);

                    pyodideReady = true;
                } catch (err) {
                    console.error("Failed to load Pyodide or packages:", err);
                    alert("Failed to load Pyodide. Check console for details.");
                    throw err;
                } finally {
                    pyodideLoading = false;
                    showSpinner(false);
                }
            }

            // Utility to show/hide spinner
            function showSpinner(on) {
                document.getElementById('loading-spinner').style.display = on ? 'block' : 'none';
            }

            // Run code for a given editor and output container
            async function runCode(editorId, outputId) {
                const editor = editors[editorId];
                if (!editor) return;
                const code = editor.getValue();

                // Prepare UI
                const consoleDiv = document.getElementById(outputId + "_console");
                const imagesDiv = document.getElementById(outputId + "_images");
                consoleDiv.innerHTML = "";
                imagesDiv.innerHTML = "";

                // ensure pyodide loaded
                try {
                    await ensurePyodide();
                } catch(err) {
                    consoleDiv.innerText = "Error loading Pyodide: " + err;
                    return;
                }

                // Capture stdout/stderr by redirecting sys.stdout/sys.stderr to buffer we can pull
                const runWrapper = `
import sys, io, traceback
_stdout_buf = io.StringIO()
_stderr_buf = io.StringIO()
_old_stdout = sys.stdout
_old_stderr = sys.stderr
sys.stdout = _stdout_buf
sys.stderr = _stderr_buf
_pyodide_captured_images = globals().get('_pyodide_captured_images', [])
try:
${code.split("\n").map(line => "    " + line).join("\n")}
except Exception as _e:
    traceback.print_exc(file=_stderr_buf)
finally:
    sys.stdout = _old_stdout
    sys.stderr = _old_stderr
_out = _stdout_buf.getvalue()
_err = _stderr_buf.getvalue()
# expose outputs to JS
_out, _err, _pyodide_captured_images
`;
                showSpinner(true);

                // Set a client-side timeout: if code does not complete in X ms, attempt to cancel by rejecting
                const TIMEOUT_MS = 20000; // 20s browser-side timeout (adjust as you see fit)
                let timedOut = false;
                const timeoutPromise = new Promise((_, reject) => {
                    setTimeout(() => { timedOut = true; reject(new Error("Execution timed out after " + (TIMEOUT_MS/1000) + "s")); }, TIMEOUT_MS);
                });

                try {
                    const execPromise = pyodide.runPythonAsync(runWrapper);
                    const [out, err, images] = await Promise.race([execPromise, timeoutPromise]);

                    // display stderr first (if any)
                    if (err && err.trim() !== "") {
                        const preErr = document.createElement('pre');
                        preErr.style.color = 'red';
                        preErr.textContent = err;
                        consoleDiv.appendChild(preErr);
                    }
                    if (out && out.trim() !== "") {
                        const preOut = document.createElement('pre');
                        preOut.textContent = out;
                        consoleDiv.appendChild(preOut);
                    }
                    // images is list of base64 strings (PNG) or bytes depending on environment
                    if (Array.isArray(images) && images.length > 0) {
                        images.forEach(b64 => {
                            if (!b64) return;
                            const img = document.createElement('img');
                            img.src = 'data:image/png;base64,' + b64;
                            imagesDiv.appendChild(img);
                        });
                    }
                } catch (err) {
                    // timed out or runtime error blocking the UI thread
                    const preErr = document.createElement('pre');
                    preErr.style.color = 'red';
                    preErr.textContent = 'Execution failed: ' + err;
                    consoleDiv.appendChild(preErr);

                    // If the timeout occurred, attempt to interrupt pyodide (best-effort)
                    if (timedOut && pyodide && pyodide.runPython) {
                        try {
                            // best-effort interruption: create a new interpreter state by reloading - note: this is heavy
                            // we choose not to forcibly reload automatically to avoid affecting other runs
                            console.warn('Execution timed out. Consider reloading the page to reset Pyodide state.');
                        } catch (ie) {
                            console.warn('Interruption attempt failed:', ie);
                        }
                    }
                } finally {
                    showSpinner(false);
                    // scroll to output
                    const outEl = document.getElementById(outputId);
                    if (outEl) outEl.scrollIntoView({behavior:'smooth'});
                }
            }

            // Wire run buttons
            window.addEventListener('load', function(){
                initCodeMirror();

                document.querySelectorAll('.run-btn').forEach(btn => {
                    btn.addEventListener('click', async function(e){
                        const target = this.getAttribute('data-target');
                        const output = this.getAttribute('data-output');
                        this.disabled = true;
                        this.textContent = 'Running...';
                        try {
                            await runCode(target, output);
                        } catch (err) {
                            console.error(err);
                        } finally {
                            this.disabled = false;
                            this.textContent = 'Run Code';
                        }
                    });
                });

                // hide spinner once loaded
                document.getElementById('loading-spinner').style.display = 'none';
                // scroll to top initially
                window.scrollTo(0,0);
            });
        </script>
    </div>
</main>

<?php include '../../include/footer.php'; ?>
</body>
</html>