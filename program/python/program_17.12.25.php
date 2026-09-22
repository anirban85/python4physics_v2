<?php
/**
 * program.php (Pyodide client-side runner, worker-based)
 * - Client-only Python execution via Pyodide Web Worker
 * - Keeps DB/menu logic; modernized UI; cleans plots per run/show
 */

include '../../site_config.php';
include '../../db.php'; // must provide $conn (mysqli)
session_start();

$menu_id    = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 1;
$submenu_id = isset($_GET['submenu_id']) ? intval($_GET['submenu_id']) : 1;

include 'menu.php';
$header_text = 'Interactive Python Programs';
$menu_text   = $menu_titles[$menu_id] ?? 'Menu';
$submenu_titles_for_menu = $sub_menu_titles[$menu_id] ?? [];
$header_text = $submenu_titles_for_menu[$submenu_id] ?? $header_text;

$sql = "SELECT id, program_id, content, algo, explanation
        FROM python
        WHERE menu_id = ? AND submenu_id = ?
        ORDER BY program_id";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing the statement: " . $conn->error);
}
$stmt->bind_param("ii", $menu_id, $submenu_id);
if ($stmt->execute()) {
    $result   = $stmt->get_result();
    $programs = $result->fetch_all(MYSQLI_ASSOC);
} else {
    die("Error executing query: " . $stmt->error);
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- GTM + deferred ads preserved -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-NRDD47HL');</script>
    <script>
    function loadAdsDeferred(){
        var s=document.createElement('script');
        s.async=true;
        s.src='https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1857733312974112';
        s.crossOrigin='anonymous';
        document.body.appendChild(s);
    }
    if('requestIdleCallback'in window) requestIdleCallback(loadAdsDeferred,{timeout:2000});
    else setTimeout(loadAdsDeferred,2000);
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
        :root {
            --bg: radial-gradient(circle at 20% 20%, #132743 0, #0b1726 45%, #090f1a 80%);
            --card: rgba(255,255,255,0.06);
            --stroke: rgba(255,255,255,0.08);
            --text: #e9eef7;
            --muted: #9fb3c8;
            --accent: #4fd1c5;
            --accent-strong: #06b6d4;
            --danger: #ef4444;
            --shadow: 0 20px 50px rgba(0,0,0,0.45);
        }
        * { box-sizing: border-box; }
        body {
            font-family:'Inter','Roboto','Segoe UI',sans-serif;
            background: var(--bg);
            color: var(--text);
            margin:0; padding:0;
        }
        header {
            background: linear-gradient(120deg, #0e1a2b, #0f2b46);
            color: var(--text);
            padding: 24px;
            text-align: center;
            font-size: 24px;
            letter-spacing: 0.4px;
            border-bottom: 1px solid var(--stroke);
            box-shadow: var(--shadow);
        }
        main {
            display:flex;
            flex-wrap:wrap;
            gap:18px;
            padding:18px;
            margin:0 auto;
            max-width:1400px;
        }
        .sidebar {
            width:260px;
            padding:16px;
            background: var(--card);
            color: var(--text);
            border: 1px solid var(--stroke);
            border-radius:16px;
            box-shadow: var(--shadow);
            position:sticky;
            top:24px;
            height:calc(100vh - 48px);
            backdrop-filter: blur(12px);
        }
        .content {
            flex:1;
            padding:4px;
            display:flex;
            flex-wrap:wrap;
            gap:18px;
            align-content:flex-start;
        }
        .program {
            flex:1 1 360px;
            background: var(--card);
            border:1px solid var(--stroke);
            border-radius:16px;
            padding:18px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(12px);
        }
        textarea {
            width:100%;
            height:220px;
            font-family:'JetBrains Mono','Courier New',monospace;
            font-size:16px;
            margin-bottom:10px;
            padding:12px;
            border:1px solid var(--stroke);
            border-radius:12px;
            background: rgba(15,23,42,0.65);
            color: #e2e8f0;
            outline:none;
        }
        textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 2px rgba(79,209,197,0.25); }
        button {
            background: linear-gradient(120deg, var(--accent), var(--accent-strong));
            color:#041019;
            border:none;
            padding:12px 18px;
            cursor:pointer;
            font-size:15px;
            border-radius:12px;
            transition: transform 0.1s ease, box-shadow 0.2s ease;
            font-weight:600;
            box-shadow: 0 10px 30px rgba(6,182,212,0.25);
        }
        button:hover { transform: translateY(-1px); box-shadow: 0 14px 32px rgba(6,182,212,0.3); }
        .btn-secondary {
            background: linear-gradient(120deg, #475569, #1f2937);
            color:#e2e8f0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .btn-secondary:hover { box-shadow: 0 14px 32px rgba(0,0,0,0.4); }
        pre {
            background: rgba(0,0,0,0.55);
            padding:14px;
            border-left:4px solid var(--accent);
            white-space:pre-wrap;
            word-wrap:break-word;
            border-radius:12px;
            overflow-y:auto;
            font-size:16px;
            color:#e2e8f0;
        }
        h1 { margin:0 0 10px 0; font-size:19px; color:var(--text); letter-spacing:0.2px; }
        img { max-width:100%; height:auto; display:block; border-radius:12px; border:1px solid var(--stroke); }
        .sidebar ul { list-style:none; padding:0; margin:0; }
        .sidebar ul li { position:relative; padding-left:26px; margin-bottom:10px; color: var(--muted); }
        .sidebar ul li::before { content:'\2714'; position:absolute; left:0; color: var(--accent); font-size:18px; top:50%; transform:translateY(-50%); }
        .sidebar ul li a { text-decoration:none; color: var(--text); font-size:15px; }
        .sidebar ul li a:hover { text-decoration:underline; color: var(--accent); }
        .sidebar ul li.active::before { color:#28a745; }
        .CodeMirror { font-size:15px; max-height:900px; min-width:320px; overflow:hidden; border:1px solid var(--stroke); border-radius:12px; }
        .toolbar { display:flex; gap:10px; flex-wrap:wrap; }
        .badge {
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:6px 10px;
            border-radius:999px;
            background: rgba(79,209,197,0.14);
            color: var(--accent);
            font-size:13px;
            border:1px solid rgba(79,209,197,0.25);
            margin-bottom:8px;
        }
        @media (max-width: 900px) {
            .sidebar { position:static; width:100%; height:auto; }
            main { padding:12px; }
            .CodeMirror { max-width:100%; font-size:13px; }
        }
        #loading-spinner { display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); z-index:1000; width:52px; height:52px; border:6px solid rgba(255,255,255,0.15); border-radius:50%; border-top:6px solid var(--accent); animation:spin 0.9s linear infinite; }
        @keyframes spin { 0%{transform:rotate(0deg);} 100%{transform:rotate(360deg);} }
        .hidden { display:none; }
        .pyodide-warning { background: rgba(255,243,205,0.12); color:#fcd34d; padding:12px; border-radius:12px; margin-bottom:12px; border:1px solid rgba(252,211,77,0.3); }
        #pyodide-loader { position:fixed; top:20px; right:20px; background:#0b1626; color:var(--accent); border:1px solid rgba(79,209,197,0.5); padding:12px 18px; border-radius:12px; font-family:"Inter",sans-serif; font-size:15px; display:flex; gap:12px; align-items:center; z-index:9999; box-shadow:0 0 18px rgba(6,182,212,0.35); animation:fadeSlideIn 0.6s ease-out; }
        .pyodide-spinner { width:18px; height:18px; border:3px solid var(--accent); border-top:3px solid transparent; border-radius:50%; animation:spinPyodide 0.9s linear infinite; }
        @keyframes spinPyodide { from{transform:rotate(0deg);} to{transform:rotate(360deg);} }
        @keyframes fadeSlideIn { from{opacity:0; transform:translateY(-10px);} to{opacity:1; transform:translateY(0);} }
        #pyodide-loader.hide { animation:fadeOut 0.4s ease-in forwards; }
        @keyframes fadeOut { to{opacity:0; transform:translateY(-8px);} }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/addon/selection/active-line.min.js"></script>
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
             Python runs in your browser. Heavy or infinite loops may freeze your browser tab. Use "Stop" if needed; for heavy jobs, run locally.
        </div>

        <?php
        foreach ($programs as $program) {
            $id         = $program['id'];
            $program_id = $program['program_id'];
            $content    = $program['content'];
            $algo       = $program['algo'];
            $explanation= $program['explanation'];
            $code_name  = "python_code{$id}";
            $output_id  = "output{$id}";

            echo "<div class='program'>\n";
            echo "<div class='badge'>Program $program_id</div>\n";
            echo "<h1>$header_text</h1>\n";
            echo "<div style='color:#FFF; margin-bottom:8px;'>$algo</div>";
            echo "<form onsubmit='return false;'>\n";
            echo "<textarea name='" . htmlspecialchars($code_name) . "' id='" . htmlspecialchars($code_name) . "'>" . htmlspecialchars($content) . "</textarea>\n";
            echo "<div class='toolbar'>\n";
            echo "<button type='button' class='run-btn' data-target='" . htmlspecialchars($code_name) . "' data-output='" . htmlspecialchars($output_id) . "'>Run</button>\n";
            echo "<button type='button' class='btn-secondary stop-btn' data-output='" . htmlspecialchars($output_id) . "'>Stop</button>\n";
            echo "</div>\n";
            echo "</form>\n";
            echo "</div>\n";

            echo "<div class='program'>\n";
            echo "<div class='badge' id='" . htmlspecialchars($output_id) . "'>Output " . htmlspecialchars($program_id) . "</div>\n";
            echo "<div class='exec-output' id='" . htmlspecialchars($output_id) . "_console'></div>\n";
            echo "<div class='exec-images' id='" . htmlspecialchars($output_id) . "_images'></div>\n";
            echo "</div>\n";
            echo "<div style='padding:8px 4px 24px 4px; color:#FFF; line-height:1.6;'>" . $explanation . "</div>";
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

