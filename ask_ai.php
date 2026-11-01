<?php
/**
 * Python4Physics - Ask AI: Computational Physics Code Generator
 * Interactive Dedicated Page for AI-driven Scientific Script Synthesis.
 * Authored by Dr. Alorika Chatterjee & Dr. Anirban Shaw
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';

$page_title = "Ask AI - Scientific Python Code Generator & Numerical Physics Synthesizer";
$page_description = "Generate verified Python scripts, numerical ODE/PDE solvers, matrix algorithms, and scientific plots from problem descriptions using AI.";

require_once __DIR__ . '/include/header.php';
require_once __DIR__ . '/include/navbar.php';
?>

<main class="container" style="padding-top: 2.5rem; padding-bottom: 5rem; max-width: 1200px;">
    <!-- Page Header -->
    <div style="text-align: center; max-width: 820px; margin: 0 auto 2.5rem auto;">
        <span class="badge badge-primary" style="font-size: 0.82rem; padding: 0.35rem 0.85rem;">
            <i class="fa-solid fa-wand-magic-sparkles"></i> AI Scientific Assistant
        </span>
        <h1 style="font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; margin-top: 0.75rem; margin-bottom: 0.75rem;">
            Ask AI: <span class="gradient-text">Physics Code Generator</span>
        </h1>
        <p style="font-size: 1.1rem; line-height: 1.6; color: var(--text-muted);">
            Describe any physics simulation, mathematical formulation, or numerical analysis task. Our AI engine synthesizes clean, robust, and verified Python code with NumPy, SciPy, and Matplotlib ready to run directly in your browser.
        </p>
    </div>

    <!-- Main Generator Card -->
    <div class="glass-card" style="padding: 2.25rem; border-radius: var(--radius-lg); border-top: 4px solid var(--accent); margin-bottom: 3rem;">
        <!-- Prompt Input Area -->
        <div style="margin-bottom: 1.5rem;">
            <label for="pageAiPrompt" style="display: block; font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: 0.65rem;">
                <i class="fa-solid fa-terminal" style="color: var(--accent); margin-right: 6px;"></i> Problem Description / Computational Physics Task:
            </label>
            <textarea id="pageAiPrompt" class="ai-prompt-area" style="min-height: 120px;" placeholder="Describe your physics problem here... e.g. 'Solve 1D time-dependent heat equation for a copper bar with fixed end temperatures and plot temperature distribution at different times', or 'Find all prime numbers up to 1000 and calculate prime density', or 'Simulate a chaotic double pendulum with RK4 and compute energy conservation'..."></textarea>
            
            <!-- Quick Chips -->
            <div style="margin-top: 0.85rem;">
                <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-dim); font-weight: 700; margin-right: 6px;">Featured Presets:</span>
                <div class="ai-preset-chips">
                    <button type="button" class="ai-preset-chip" onclick="setPagePrompt('Find all prime numbers up to 1000 using Sieve of Eratosthenes and plot against Prime Number Theorem')">
                        <i class="fa-solid fa-hashtag"></i> Prime Sieve Algorithm
                    </button>
                    <button type="button" class="ai-preset-chip" onclick="setPagePrompt('Simulate nonlinear damped driven pendulum using RK4 and plot phase space trajectory')">
                        <i class="fa-solid fa-wave-square"></i> Nonlinear Pendulum (RK4)
                    </button>
                    <button type="button" class="ai-preset-chip" onclick="setPagePrompt('Solve 1D Time-Independent Schrodinger equation for a finite square well using matrix diagonalization')">
                        <i class="fa-solid fa-atom"></i> Quantum Well (TISE)
                    </button>
                    <button type="button" class="ai-preset-chip" onclick="setPagePrompt('Simulate 1D heat diffusion along a copper rod using FTCS scheme and plot temperature profiles')">
                        <i class="fa-solid fa-temperature-half"></i> 1D Heat Equation
                    </button>
                    <button type="button" class="ai-preset-chip" onclick="setPagePrompt('Biot-Savart law magnetic field of circular current loop')">
                        <i class="fa-solid fa-compass"></i> Biot-Savart Current Loop
                    </button>
                    <button type="button" class="ai-preset-chip" onclick="setPagePrompt('Simulate motion of a charged particle in a magnetic field under Lorentz force')">
                        <i class="fa-solid fa-circle-notch"></i> Lorentz Force Trajectory
                    </button>
                    <button type="button" class="ai-preset-chip" onclick="setPagePrompt('Fraunhofer single slit diffraction and double slit interference pattern')">
                        <i class="fa-solid fa-eye"></i> Double Slit Optics
                    </button>
                    <button type="button" class="ai-preset-chip" onclick="setPagePrompt('Bessel function of the first kind plot for J0, J1, J2, J3')">
                        <i class="fa-solid fa-chart-line"></i> Bessel Functions
                    </button>
                    <button type="button" class="ai-preset-chip" onclick="setPagePrompt('Check if an integer is Armstrong number and find all Armstrong numbers up to 10000')">
                        <i class="fa-solid fa-calculator"></i> Armstrong Numbers
                    </button>
                    <button type="button" class="ai-preset-chip" onclick="setPagePrompt('Half wave rectifier with capacitor filter circuit simulation')">
                        <i class="fa-solid fa-bolt"></i> Half-Wave Rectifier
                    </button>
                </div>
            </div>
        </div>

        <!-- Options & Action Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; padding: 1rem 1.25rem; background: var(--bg-tertiary); border: 1px solid var(--card-border); border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
                <label style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.88rem; cursor: pointer; color: var(--text);">
                    <input type="checkbox" id="pagePlotCheck" checked style="accent-color: var(--accent);">
                    <span>Include Matplotlib Visualization</span>
                </label>
                <details style="font-size: 0.82rem; color: var(--text-dim);">
                    <summary style="cursor: pointer; user-select: none;"><i class="fa-solid fa-key" style="color: #f59e0b;"></i> Optional Gemini API Key</summary>
                    <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem; align-items: center;">
                        <input type="password" id="pageApiKeyInput" placeholder="Enter personal Gemini API key" style="background: var(--bg-secondary); border: 1px solid var(--card-border); border-radius: 4px; padding: 0.35rem 0.65rem; font-size: 0.8rem; color: var(--text); width: 240px;" oninput="savePageApiKey(this.value)">
                        <span style="font-size: 0.72rem; color: var(--text-dim);">Stored locally</span>
                    </div>
                </details>
            </div>

            <button type="button" class="btn-modern btn-ai" id="pageGenerateBtn" onclick="generateCodeFromPage()">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <span>Generate Python Code</span>
            </button>
        </div>

        <!-- Loading State -->
        <div id="pageLoading" style="display: none; padding: 3rem 1rem; text-align: center; color: var(--text-muted);">
            <i class="fa-solid fa-circle-notch fa-spin fa-2x" style="color: var(--accent); margin-bottom: 1rem;"></i>
            <div style="font-size: 1.1rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">Synthesizing Scientific Python Code...</div>
            <div style="font-size: 0.88rem; color: var(--text-dim);">Analyzing mathematical formulation, algorithm stability, and plotting routines</div>
        </div>

        <!-- Error Alert -->
        <div id="pageError" style="display: none; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: var(--radius-sm); padding: 1rem 1.25rem; color: #ef4444; margin-bottom: 1.5rem;"></div>

        <!-- Output Results Container -->
        <div id="pageResults" style="display: none; flex-direction: column; gap: 1.25rem;">
            <!-- User Prompt Banner -->
            <div id="pageUserPromptCard" style="background: rgba(14, 165, 233, 0.08); border: 1px solid rgba(14, 165, 233, 0.3); border-left: 4px solid var(--accent); border-radius: var(--radius-sm); padding: 0.9rem 1.25rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--accent); font-weight: 700; margin-bottom: 0.3rem;">
                        <i class="fa-solid fa-terminal"></i> User Prompt (Exact Request):
                    </div>
                    <div id="pagePromptEcho" style="font-size: 1.05rem; font-weight: 600; color: #fff; line-height: 1.5; font-family: 'JetBrains Mono', monospace;"></div>
                </div>
                <button type="button" class="btn-modern btn-secondary btn-sm" onclick="editPagePrompt()" style="flex-shrink: 0; padding: 0.4rem 0.85rem; font-size: 0.82rem;">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Prompt
                </button>
            </div>

            <!-- Output Title Bar -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; border-bottom: 1px solid var(--card-border); padding-bottom: 1rem;">
                <div>
                    <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.35rem;">
                        <span class="badge badge-cyan" id="pageSourceBadge">Generated</span>
                        <span class="badge badge-amber" id="pageComplexityBadge">O(N)</span>
                    </div>
                    <h3 id="pageResultTitle" style="font-size: 1.35rem; margin: 0; color: var(--text);"></h3>
                </div>

                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button type="button" class="btn-modern btn-secondary btn-sm" onclick="copyPageCode()">
                        <i class="fa-regular fa-copy"></i> Copy Code
                    </button>
                    <button type="button" class="btn-modern btn-primary btn-sm" id="pageRunBtn" onclick="runPageCode()">
                        <i class="fa-solid fa-play"></i> Run Code (Pyodide)
                    </button>
                </div>
            </div>

            <!-- Dual-Pane Workspace: Code Editor & Execution Output -->
            <div class="workspace-container">
                <!-- Left Pane: Code -->
                <div class="workspace-pane">
                    <div class="editor-header">
                        <span class="editor-title"><i class="fa-brands fa-python" style="color: var(--accent);"></i> Generated Python Script</span>
                        <span id="pageRunStatus" class="badge badge-cyan">Ready to Run</span>
                    </div>
                    <div class="editor-wrapper">
                        <textarea id="pageCodeArea"></textarea>
                    </div>
                </div>

                <!-- Right Pane: Execution Output & Theory -->
                <div class="workspace-pane">
                    <div class="tabs-header">
                        <button class="tab-btn active" id="tab_page_plots_btn" onclick="switchPageTab('plots')">
                            <i class="fa-regular fa-image"></i> Rendered Plots
                        </button>
                        <button class="tab-btn" id="tab_page_console_btn" onclick="switchPageTab('console')">
                            <i class="fa-solid fa-terminal"></i> Console Output
                        </button>
                        <button class="tab-btn" id="tab_page_theory_btn" onclick="switchPageTab('theory')">
                            <i class="fa-solid fa-lightbulb"></i> Theory & Method
                        </button>
                    </div>

                    <!-- Plots Panel -->
                    <div id="panel_page_plots" class="tab-content active">
                        <div id="pagePlotsContainer" class="plots-container" style="min-height: 380px; display: flex; align-items: center; justify-content: center;">
                            <div style="color: var(--text-dim); text-align: center;">Click "Run Code (Pyodide)" to execute script and render plots.</div>
                        </div>
                    </div>

                    <!-- Console Panel -->
                    <div id="panel_page_console" class="tab-content">
                        <pre id="pageConsole" class="console-output" style="min-height: 380px;"></pre>
                    </div>

                    <!-- Theory Panel -->
                    <div id="panel_page_theory" class="tab-content">
                        <div class="theory-card" style="min-height: 380px; max-height: 480px; overflow-y: auto;">
                            <h4 style="color: var(--accent); margin-bottom: 0.5rem;"><i class="fa-solid fa-book-open"></i> Mathematical Formulation & Computational Approach</h4>
                            <div id="pageExplanation" style="line-height: 1.7; color: var(--text-color); margin-bottom: 1.5rem;"></div>

                            <div id="pageExercisesBlock" style="border-top: 1px solid var(--card-border); padding-top: 1rem;">
                                <h5 style="color: var(--primary); margin-bottom: 0.5rem;"><i class="fa-solid fa-flask-vial"></i> Suggested Explorations:</h5>
                                <div id="pageExercisesList" style="display: flex; flex-direction: column; gap: 0.5rem;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Pyodide Execution Engine Script -->
<script src="<?php echo $siteurl; ?>assets/js/pyodide-runner.js?v=<?php echo file_exists(__DIR__ . '/assets/js/pyodide-runner.js') ? filemtime(__DIR__ . '/assets/js/pyodide-runner.js') : time(); ?>"></script>

<script>
var pageCodeMirror = null;
var generatedCodeText = '';

document.addEventListener('DOMContentLoaded', function() {
    var storedKey = localStorage.getItem('p4p_gemini_api_key');
    if (storedKey) {
        var keyInput = document.getElementById('pageApiKeyInput');
        if (keyInput) keyInput.value = storedKey;
    }

    var textarea = document.getElementById('pageCodeArea');
    if (textarea) {
        pageCodeMirror = CodeMirror.fromTextArea(textarea, {
            mode: 'python',
            theme: 'material-darker',
            lineNumbers: true,
            lineWrapping: true,
            matchBrackets: true,
            styleActiveLine: true,
            indentUnit: 4,
            viewportMargin: Infinity
        });
    }

    // Check if query was passed in URL: ?q=...
    var urlParams = new URLSearchParams(window.location.search);
    var q = urlParams.get('q');
    if (q) {
        setPagePrompt(q);
        generateCodeFromPage();
    }
});

function savePageApiKey(val) {
    if (val && val.trim()) {
        localStorage.setItem('p4p_gemini_api_key', val.trim());
    } else {
        localStorage.removeItem('p4p_gemini_api_key');
    }
}

function setPagePrompt(text) {
    var promptEl = document.getElementById('pageAiPrompt');
    if (promptEl) {
        promptEl.value = text;
        promptEl.focus();
    }
}

function switchPageTab(tabName) {
    ['plots', 'console', 'theory'].forEach(function(t) {
        var btn = document.getElementById('tab_page_' + t + '_btn');
        var panel = document.getElementById('panel_page_' + t);
        if (btn) btn.classList.toggle('active', t === tabName);
        if (panel) panel.classList.toggle('active', t === tabName);
    });
}

async function generateCodeFromPage() {
    var promptEl = document.getElementById('pageAiPrompt');
    var plotCheck = document.getElementById('pagePlotCheck');
    var keyInput = document.getElementById('pageApiKeyInput');
    var btn = document.getElementById('pageGenerateBtn');
    var loader = document.getElementById('pageLoading');
    var errorEl = document.getElementById('pageError');
    var resultsEl = document.getElementById('pageResults');

    if (!promptEl || !promptEl.value.trim()) {
        alert('Please describe a physics or computational problem first.');
        if (promptEl) promptEl.focus();
        return;
    }

    var problemText = promptEl.value.trim();
    var apiKey = keyInput ? keyInput.value.trim() : (localStorage.getItem('p4p_gemini_api_key') || '');
    var includePlot = plotCheck ? plotCheck.checked : true;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';
    loader.style.display = 'block';
    errorEl.style.display = 'none';
    resultsEl.style.display = 'none';

    var data = null;

    // 1. If user provided a Gemini API Key, attempt direct client-side call to Google Gemini
    if (apiKey) {
        try {
            data = await callGeminiClientDirectly(apiKey, problemText, includePlot);
        } catch (geminiErr) {
            console.warn('Direct client-side Gemini API call failed, falling back to server engine:', geminiErr);
        }
    }

    // 2. Fall back to backend computational physics engine if no API key or direct call was bypassed
    if (!data || !data.success) {
        try {
            var response = await fetch('<?php echo $siteurl; ?>api/generate_code.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    problem: problemText,
                    gemini_api_key: apiKey,
                    include_plot: includePlot
                })
            });

            data = await response.json();
        } catch (err) {
            loader.style.display = 'none';
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Generate Python Code';
            errorEl.innerText = 'Network error: ' + err.message;
            errorEl.style.display = 'block';
            return;
        }
    }

    loader.style.display = 'none';
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Generate Python Code';

    if (!data || !data.success || !data.code) {
        errorEl.innerText = (data && data.error) ? data.error : 'Failed to generate solution. Please try again.';
        errorEl.style.display = 'block';
        return;
    }

        generatedCodeText = data.code;
        document.getElementById('pagePromptEcho').innerText = data.user_prompt || problemText;
        document.getElementById('pageResultTitle').innerText = data.title || 'Computational Physics Solution';
        document.getElementById('pageComplexityBadge').innerText = data.complexity || 'O(N)';
        document.getElementById('pageSourceBadge').innerText = data.source === 'gemini_ai' ? 'Gemini 2.0 AI' : (data.source === 'curriculum_library' ? 'Curriculum Engine' : 'Physics Synthesizer');
        document.getElementById('pageSourceBadge').className = data.source === 'gemini_ai' ? 'badge badge-primary' : 'badge badge-cyan';

        if (pageCodeMirror) {
            pageCodeMirror.setValue(data.code);
            setTimeout(function() { pageCodeMirror.refresh(); }, 150);
        }

        document.getElementById('pageExplanation').innerHTML = data.explanation || 'Numerical simulation script ready for in-browser execution.';

        // Exercises
        var exList = document.getElementById('pageExercisesList');
        if (data.exercises && data.exercises.length > 0) {
            exList.innerHTML = '';
            data.exercises.forEach(function(ex, idx) {
                var item = document.createElement('div');
                item.style.background = 'rgba(255, 255, 255, 0.02)';
                item.style.border = '1px solid var(--card-border)';
                item.style.borderLeft = '3px solid var(--primary)';
                item.style.padding = '0.65rem 0.95rem';
                item.style.borderRadius = '0 var(--radius-sm) var(--radius-sm) 0';
                item.style.fontSize = '0.88rem';
                item.style.display = 'flex';
                item.style.justifyContent = 'space-between';
                item.style.alignItems = 'center';
                item.style.gap = '0.75rem';

                item.innerHTML = '<span><strong style="color:var(--primary); margin-right:6px;">Ex ' + (idx + 1) + ':</strong> ' + escapeHtml(ex) + '</span>' +
                    '<button type="button" class="btn-modern btn-secondary btn-sm" onclick="setPagePrompt(\'' + escapeJsString(ex) + '\'); generateCodeFromPage();" style="padding:0.2rem 0.5rem; font-size:0.72rem; flex-shrink:0;">' +
                    '<i class="fa-solid fa-arrow-up"></i> Solve This</button>';
                exList.appendChild(item);
            });
            document.getElementById('pageExercisesBlock').style.display = 'block';
        } else {
            document.getElementById('pageExercisesBlock').style.display = 'none';
        }

        resultsEl.style.display = 'flex';

        // Auto run if plotting is expected
        setTimeout(function() {
            resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            runPageCode();
        }, 200);

    } catch (err) {
        loader.style.display = 'none';
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Generate Python Code';
        errorEl.innerText = 'Network error: ' + err.message;
        errorEl.style.display = 'block';
    }
}

async function runPageCode() {
    if (!pageCodeMirror) return;
    var code = pageCodeMirror.getValue();
    var runBtn = document.getElementById('pageRunBtn');
    var statusBadge = document.getElementById('pageRunStatus');
    var consoleEl = document.getElementById('pageConsole');
    var plotsEl = document.getElementById('pagePlotsContainer');

    runBtn.disabled = true;
    runBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Executing...';
    statusBadge.className = 'badge badge-amber';
    statusBadge.innerText = 'Running in Pyodide...';

    if (code.includes('plt.') || code.includes('matplotlib')) {
        switchPageTab('plots');
    } else {
        switchPageTab('console');
    }

    try {
        await window.physicsRunner.run(code, {
            pid: 'ask_ai_page',
            onStatus: function(msg) { statusBadge.innerText = msg; },
            onPlotsReady: function(count) {
                switchPageTab('plots');
                statusBadge.className = 'badge badge-emerald';
                statusBadge.innerText = 'Rendered (' + count + ' plots)';
            },
            consoleEl: consoleEl,
            plotsEl: plotsEl
        });
        statusBadge.className = 'badge badge-emerald';
        statusBadge.innerText = 'Execution Complete';
    } catch (err) {
        statusBadge.className = 'badge badge-danger';
        statusBadge.innerText = 'Runtime Error';
        consoleEl.innerText += '\n[Execution Error]: ' + err.message;
        switchPageTab('console');
    } finally {
        runBtn.disabled = false;
        runBtn.innerHTML = '<i class="fa-solid fa-play"></i> Run Code (Pyodide)';
    }
}

function copyPageCode() {
    if (!pageCodeMirror) return;
    var code = pageCodeMirror.getValue();
    navigator.clipboard.writeText(code).then(function() {
        alert('Python script copied to clipboard!');
    });
}

function editPagePrompt() {
    var promptEl = document.getElementById('pageAiPrompt');
    if (promptEl) {
        promptEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        promptEl.focus();
        promptEl.select();
    }
}

async function callGeminiClientDirectly(apiKey, problemText, includePlot) {
    var systemInstruction = "You are an expert computational physicist, numerical mathematician, and senior Python scientific developer for Python4Physics. " +
        "The user will describe a physics, mathematical, or scientific programming problem. " +
        "Generate fully runnable, self-contained, high-quality Python 3 code using standard libraries, NumPy, SciPy, and Matplotlib (plt.show()). " +
        "The code must execute seamlessly in a browser environment via Pyodide. " +
        "Do NOT require external file downloads or local file paths. " +
        "Provide your answer strictly as a JSON object with this schema:\n" +
        "{\n" +
        "  \"title\": \"Clear descriptive title of the solution\",\n" +
        "  \"code\": \"Executable Python code string with clear comments\",\n" +
        "  \"explanation\": \"Pointwise concise theoretical overview and algorithm mechanics\",\n" +
        "  \"complexity\": \"Time and memory complexity (e.g. O(N log N))\",\n" +
        "  \"exercises\": [\"Exercise 1 for student\", \"Exercise 2\", \"Exercise 3\"]\n" +
        "}";

    var userPrompt = "Problem Description: " + problemText + "\n" +
        "Include Graphical Matplotlib Visualization: " + (includePlot ? "YES" : "NO") + "\n" +
        "Please output strictly the JSON object without markdown code blocks.";

    var models = ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-1.5-flash'];
    var lastError = null;

    for (var i = 0; i < models.length; i++) {
        var model = models[i];
        try {
            var url = 'https://generativelanguage.googleapis.com/v1beta/models/' + model + ':generateContent?key=' + encodeURIComponent(apiKey);
            var res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    contents: [{
                        role: 'user',
                        parts: [{ text: systemInstruction + "\n\n" + userPrompt }]
                    }],
                    generationConfig: {
                        temperature: 0.2,
                        maxOutputTokens: 2500,
                        responseMimeType: 'application/json'
                    }
                })
            });

            if (res.ok) {
                var json = await res.json();
                var text = json && json.candidates && json.candidates[0] && json.candidates[0].content && json.candidates[0].content.parts && json.candidates[0].content.parts[0] ? json.candidates[0].content.parts[0].text : '';
                text = text.trim().replace(/^```(?:json)?\s*/i, '').replace(/\s*```$/, '');
                var parsed = JSON.parse(text);
                if (parsed && parsed.code) {
                    var doc = '"""\n' +
                        '='.repeat(78) + '\n' +
                        'Python4Physics - Ask AI Generated Solution\n' +
                        'User Prompt: ' + problemText.replace(/[\r\n]+/g, ' ') + '\n' +
                        'Physical Domain: ' + (parsed.title || 'Gemini 2.0 AI Synthesis') + '\n' +
                        'Complexity: ' + (parsed.complexity || 'O(N)') + '\n' +
                        '='.repeat(78) + '\n' +
                        '"""\n\n';
                    return {
                        success: true,
                        source: 'gemini_ai',
                        title: parsed.title || 'Computational Physics Solution',
                        code: doc + parsed.code.trim(),
                        explanation: parsed.explanation || '',
                        complexity: parsed.complexity || 'O(N)',
                        exercises: parsed.exercises || [
                            'Modify simulation parameters and observe parameter sensitivity.',
                            'Compare numerical convergence with smaller discretization steps.',
                            'Extend the computational model with additional physical interactions.'
                        ],
                        user_prompt: problemText
                    };
                }
            } else {
                var errData = await res.json().catch(function() { return {}; });
                lastError = (errData && errData.error && errData.error.message) ? errData.error.message : ('HTTP ' + res.status);
            }
        } catch (e) {
            lastError = e.message;
        }
    }
    throw new Error(lastError || 'Gemini API call failed');
}

function escapeHtml(text) {
    var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

function escapeJsString(str) {
    return String(str).replace(/'/g, "\\'").replace(/"/g, '\\"');
}
</script>

<?php require_once __DIR__ . '/include/footer.php'; ?>
