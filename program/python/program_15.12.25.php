<?php
/**
 * program.php (Pyodide client-side runner)
 * - Replaces server-side Python execution with Pyodide (runs in browser)
 * - Keeps styles, CodeMirror editors, layout and DB program list intact
 * - IMPORTANT: This disables server-side code execution (safer on shared hosts)
 */

// ---------------- INCLUDES ----------------
include '../../site_config.php';
include '../../db.php'; // must provide $conn (mysqli)

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
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing the statement: " . $conn->error);
}
$stmt->bind_param("ii", $menu_id, $submenu_id);
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $programs = $result->fetch_all(MYSQLI_ASSOC);
} else {
    die("Error executing query: " . $stmt->error);
}
$stmt->close();

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
        #pyodide-loader {
  position: fixed;
  top: 20px;
  right: 20px;
  background: #1e1e1e;
  color: #00e6ff;
  border: 1px solid #00e6ff;
  padding: 12px 18px;
  border-radius: 12px;
  font-family: "Segoe UI", sans-serif;
  font-size: 15px;
  display: flex;
  gap: 12px;
  align-items: center;
  z-index: 9999;

  box-shadow: 0 0 12px #00e6ff;
  animation: fadeSlideIn 0.6s ease-out;
}

.pyodide-spinner {
  width: 18px;
  height: 18px;
  border: 3px solid #00e6ff;
  border-top: 3px solid transparent;
  border-radius: 50%;
  animation: spinPyodide 0.9s linear infinite;
}

@keyframes spinPyodide {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}

@keyframes fadeSlideIn {
  from { opacity: 0; transform: translateY(-10px); }
  to   { opacity: 1; transform: translateY(0); }
}

#pyodide-loader.hide {
  animation: fadeOut 0.4s ease-in forwards;
}

@keyframes fadeOut {
  to { opacity: 0; transform: translateY(-8px); }
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
        <div id="pyodide-loader" style="display:none;">
          <div class="pyodide-spinner"></div>
          <div class="pyodide-text">Loading Python Environment…</div>
        </div>


        <div class="pyodide-warning">
             Python runs in your browser. Heavy or infinite loops may freeze your browser tab. For heavy jobs, run locally.
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
/*****************************************
 * Pyodide Runner with Matplotlib Animation
 *****************************************/

// CodeMirror setup
const editors = {};
function initCodeMirror(){
    document.querySelectorAll("textarea").forEach(function(textarea){
        var editor = CodeMirror.fromTextArea(textarea, {
            mode:"python",
            theme:"monokai",
            lineNumbers:true,
            styleActiveLine:true,
            matchBrackets:true,
            lineWrapping:true
        });
        editor.on("change", function(){ adjustEditorHeight(editor); });
        adjustEditorHeight(editor);
        editors[textarea.id] = editor;
    });
}
function adjustEditorHeight(editor){
    var h = Math.min(editor.lineCount()*editor.defaultTextHeight() + 70, 500);
    editor.getWrapperElement().style.height = h + "px";
}

// Pyodide system
let pyodide = null;
let pyodideReady = false;
let pyodideLoading = false;

//  PRELOAD PYODIDE AUTOMATICALLY WHEN PAGE LOADS
window.addEventListener("load", () => {
    setTimeout(() => { ensurePyodide(); }, 500);
});

async function ensurePyodide(){
    if(pyodideReady) return;
    if(pyodideLoading){
        while(!pyodideReady) await new Promise(r => setTimeout(r,100));
        return;
    }

    pyodideLoading = true;

    // 🔥 NEW: Show stylish loader
    const pyLoader = document.getElementById("pyodide-loader");
    if (pyLoader) pyLoader.style.display = "flex";

    showSpinner(true); // your original spinner

    try {
        const indexUrl = "https://cdn.jsdelivr.net/pyodide/v0.24.0/full/pyodide.js";

        await new Promise((resolve, reject)=>{
            const s = document.createElement("script");
            s.src = indexUrl;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });

        pyodide = await loadPyodide({
            indexURL:"https://cdn.jsdelivr.net/pyodide/v0.24.0/full/"
        });

        await pyodide.loadPackage(["numpy","matplotlib","scipy","micropip"]);

        // Bootstrap: animation-enabled matplotlib
        await pyodide.runPythonAsync(`
import sys, io, base64, traceback
from js import console

_pyodide_captured_frames = []

def _save_frame(fig):
    try:
        from io import BytesIO
        import base64
        buf = BytesIO()
        fig.savefig(buf, format="png")
        buf.seek(0)
        _pyodide_captured_frames.append(base64.b64encode(buf.read()).decode("ascii"))
    except Exception as e:
        console.warn("Frame save failed:", str(e))

def _reset_frames():
    _pyodide_captured_frames.clear()

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt

_orig_pause = plt.pause
def _pause_capture(interval):
    fig = plt.gcf()
    _save_frame(fig)
    return _orig_pause(0.1)
plt.pause = _pause_capture

_orig_show = plt.show
def _show_capture(*args, **kwargs):
    fig = plt.gcf()
    _save_frame(fig)
plt.show = _show_capture

def _close_all():
    try:
        plt.close("all")
    except:
        pass
        `);

        pyodideReady = true;
    }
    catch(e){
        console.error(e);
        alert("Pyodide loading failed!");
        throw e;
    }
    finally {
        pyodideLoading = false;

        showSpinner(false); // your original spinner hide

        // 🔥 NEW: Smooth fade-out of stylish loader
        if (pyLoader) {
            pyLoader.classList.add("hide");
            setTimeout(() => {
                pyLoader.style.display = "none";
                pyLoader.classList.remove("hide");
            }, 400);
        }
    }
}


function showSpinner(on){
    document.getElementById("loading-spinner").style.display =
        on ? "block" : "none";
}

// Run Code
async function runCode(editorId, outputId){
    const ed = editors[editorId];
    if(!ed) return;

    const code = ed.getValue();
    const consoleDiv = document.getElementById(outputId+"_console");
    const imgDiv = document.getElementById(outputId+"_images");
    consoleDiv.innerHTML = "";
    imgDiv.innerHTML = "";

    try { await ensurePyodide(); }
    catch { consoleDiv.innerText="Pyodide load error"; return; }

    // Reset old frames
    await pyodide.runPythonAsync(`_reset_frames(); _close_all()`);

    // Wrap code
    const ind = code.split("\n").map(l=>"    "+l).join("\n");
    const wrapper = `
import sys, io, traceback
_stdout = io.StringIO()
_stderr = io.StringIO()
_old_out, _old_err = sys.stdout, sys.stderr
sys.stdout, sys.stderr = _stdout, _stderr
try:
${ind}
except Exception:
    traceback.print_exc(file=_stderr)
finally:
    sys.stdout, sys.stderr = _old_out, _old_err
_out = _stdout.getvalue()
_err = _stderr.getvalue()
_frames = _pyodide_captured_frames
_out, _err, _frames
`;

    showSpinner(true);

    try {
        const res = await pyodide.runPythonAsync(wrapper);
        const out = res.get(0).toString();
        const err = res.get(1).toString();
        const frames = res.get(2).toJs();

        if(err.trim()){
            let p=document.createElement("pre");
            p.style.color="red";
            p.textContent=err;
            consoleDiv.appendChild(p);
        }
        if(out.trim()){
            let p=document.createElement("pre");
            p.textContent=out;
            consoleDiv.appendChild(p);
        }

        // Animation playback
        if(frames.length){
            let i=0;
            function showNext(){
                imgDiv.innerHTML="";
                let img=document.createElement("img");
                img.src="data:image/png;base64,"+frames[i];
                imgDiv.appendChild(img);
                i++;
                if(i < frames.length){
                    requestAnimationFrame(showNext);
                }
            }
            requestAnimationFrame(showNext);
        }
    }
    catch(err){
        let p=document.createElement("pre");
        p.style.color="red";
        p.textContent="Execution failed: "+err;
        consoleDiv.appendChild(p);
    }
    finally {
        showSpinner(false);
        document.getElementById(outputId).scrollIntoView({behavior:"smooth"});
    }
}

window.addEventListener("load",()=>{
    initCodeMirror();
    document.querySelectorAll(".run-btn").forEach(btn=>{
        btn.addEventListener("click", async function(){
            const t = this.getAttribute("data-target");
            const o = this.getAttribute("data-output");
            const prev = this.textContent;
            this.disabled=true;
            this.textContent="Running...";
            await runCode(t,o);
            this.disabled=false;
            this.textContent=prev;
        });
    });
});
</script>


    </div>
</main>

<?php include '../../include/footer.php'; ?>
</body>
</html>
