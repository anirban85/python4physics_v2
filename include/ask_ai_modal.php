<?php
/**
 * Python4Physics - Ask AI: Computational Physics Code Generator Modal
 * Interactive UI for synthesizing Python code from natural language problem descriptions.
 */
?>
<div id="askAiModalBackdrop" class="ai-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="askAiModalTitle">
    <div class="ai-modal-dialog">
        <!-- Modal Header -->
        <div class="ai-modal-header">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #0284c7, #6366f1, #a855f7); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.15rem; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <div>
                    <h3 id="askAiModalTitle" style="font-size: 1.25rem; margin: 0; color: var(--text);">Ask AI: Python Code Generator</h3>
                    <div style="font-size: 0.78rem; color: var(--text-dim); display: flex; gap: 0.75rem; align-items: center; margin-top: 2px;">
                        <span><i class="fa-solid fa-microchip"></i> Powered by Gemini 2.0 & Scientific Physics Engine</span>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="closeAskAIModal()" aria-label="Close modal" style="padding: 0.35rem 0.65rem;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="ai-modal-body">
            <!-- Prompt Input Section -->
            <div>
                <label for="aiProblemInput" style="display: block; font-size: 0.88rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text);">
                    <i class="fa-solid fa-pen-nib" style="color: var(--accent); margin-right: 4px;"></i> Describe Your Scientific Problem or Simulation:
                </label>
                <textarea id="aiProblemInput" class="ai-prompt-area" placeholder="e.g. 'Generate code to simulate a double pendulum with RK4 and plot chaotic motion', or 'Find all prime numbers up to N and plot prime counting density', or 'Solve 1D Schrodinger wavepacket tunneling through a rectangular barrier'..."></textarea>
                
                <!-- Quick Preset Chips -->
                <div style="margin-top: 0.65rem;">
                    <span style="font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-dim); font-weight: 700; margin-right: 4px;">Example Prompts:</span>
                    <div class="ai-preset-chips">
                        <button type="button" class="ai-preset-chip" onclick="setAiPromptPreset('Find all prime numbers up to 500 using Sieve of Eratosthenes and compare with Prime Number Theorem')">
                            <i class="fa-solid fa-hashtag"></i> Prime Sieve
                        </button>
                        <button type="button" class="ai-preset-chip" onclick="setAiPromptPreset('Simulate nonlinear damped driven pendulum using RK4 and plot phase space trajectory')">
                            <i class="fa-solid fa-wave-square"></i> Pendulum RK4
                        </button>
                        <button type="button" class="ai-preset-chip" onclick="setAiPromptPreset('Solve 1D Time-Independent Schrodinger equation for a finite potential well using matrix diagonalization')">
                            <i class="fa-solid fa-atom"></i> Quantum Well TISE
                        </button>
                        <button type="button" class="ai-preset-chip" onclick="setAiPromptPreset('Simulate 1D heat diffusion along a copper rod using FTCS scheme and plot cooling profiles')">
                            <i class="fa-solid fa-temperature-half"></i> 1D Heat Equation
                        </button>
                        <button type="button" class="ai-preset-chip" onclick="setAiPromptPreset('Biot-Savart law magnetic field of circular current loop')">
                            <i class="fa-solid fa-compass"></i> Biot-Savart
                        </button>
                        <button type="button" class="ai-preset-chip" onclick="setAiPromptPreset('Simulate motion of a charged particle in a magnetic field under Lorentz force')">
                            <i class="fa-solid fa-circle-notch"></i> Lorentz Force
                        </button>
                        <button type="button" class="ai-preset-chip" onclick="setAiPromptPreset('Fraunhofer single slit diffraction and double slit interference pattern')">
                            <i class="fa-solid fa-eye"></i> Double Slit
                        </button>
                        <button type="button" class="ai-preset-chip" onclick="setAiPromptPreset('Bessel function of the first kind plot for J0, J1, J2, J3')">
                            <i class="fa-solid fa-chart-line"></i> Bessel Functions
                        </button>
                        <button type="button" class="ai-preset-chip" onclick="setAiPromptPreset('Check if an integer is Armstrong number and find all Armstrong numbers up to 10000')">
                            <i class="fa-solid fa-calculator"></i> Armstrong Number
                        </button>
                        <button type="button" class="ai-preset-chip" onclick="setAiPromptPreset('Half wave rectifier with capacitor filter circuit simulation')">
                            <i class="fa-solid fa-bolt"></i> Rectifier Circuit
                        </button>
                    </div>
                </div>
            </div>

            <!-- Configuration & Action Bar -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; padding: 0.75rem 1rem; background: var(--bg-tertiary); border: 1px solid var(--card-border); border-radius: var(--radius-md);">
                <div style="display: flex; gap: 1.25rem; align-items: center; flex-wrap: wrap;">
                    <label style="display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.85rem; cursor: pointer; color: var(--text);">
                        <input type="checkbox" id="aiIncludePlotCheck" checked style="accent-color: var(--accent);">
                        <span>Include Matplotlib Graph</span>
                    </label>
                    <details style="font-size: 0.82rem; color: var(--text-dim);">
                        <summary style="cursor: pointer; user-select: none;"><i class="fa-solid fa-key" style="color: #f59e0b;"></i> Optional Gemini API Key</summary>
                        <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem; align-items: center;">
                            <input type="password" id="aiApiKeyInput" placeholder="Enter personal Gemini API key" style="background: var(--bg-secondary); border: 1px solid var(--card-border); border-radius: 4px; padding: 0.3rem 0.6rem; font-size: 0.78rem; color: var(--text); width: 220px;" oninput="saveAiApiKey(this.value)">
                            <span style="font-size: 0.72rem; color: var(--text-dim);">Saved locally in browser</span>
                        </div>
                    </details>
                </div>

                <button type="button" class="btn-modern btn-ai" id="aiGenerateBtn" onclick="submitAskAI()">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <span>Generate Python Code</span>
                </button>
            </div>

            <!-- Loading Spinner State -->
            <div id="aiLoadingIndicator" style="display: none; padding: 3rem 1rem; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-circle-notch fa-spin fa-2x" style="color: var(--accent); margin-bottom: 1rem;"></i>
                <div style="font-size: 1.05rem; font-weight: 600; color: var(--text); margin-bottom: 0.35rem;">Synthesizing Computational Physics Script...</div>
                <div style="font-size: 0.85rem; color: var(--text-dim);">Analyzing governing physical equations and formulating optimized Python code</div>
            </div>

            <!-- Error Message Box -->
            <div id="aiErrorAlert" style="display: none; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: var(--radius-sm); padding: 0.85rem 1.25rem; color: #ef4444; font-size: 0.9rem;"></div>

            <!-- Results Output Area -->
            <div id="aiResultsArea" style="display: none; flex-direction: column; gap: 1.25rem;">
                <!-- User Prompt Banner -->
                <div id="aiUserPromptCard" style="background: rgba(14, 165, 233, 0.08); border: 1px solid rgba(14, 165, 233, 0.28); border-left: 4px solid var(--accent); border-radius: var(--radius-sm); padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                    <div style="flex: 1; min-width: 200px;">
                        <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--accent); font-weight: 700; margin-bottom: 0.2rem;">
                            <i class="fa-solid fa-terminal"></i> Your Prompt:
                        </div>
                        <div id="aiPromptEcho" style="font-size: 0.92rem; font-weight: 600; color: #fff; line-height: 1.4; font-family: 'JetBrains Mono', monospace;"></div>
                    </div>
                    <button type="button" class="btn-modern btn-secondary btn-sm" onclick="editModalPrompt()" style="flex-shrink: 0; padding: 0.25rem 0.65rem; font-size: 0.75rem;">
                        <i class="fa-solid fa-pen-to-square"></i> Edit
                    </button>
                </div>

                <!-- Result Header & Actions -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span class="badge badge-cyan" id="aiResultSourceBadge">Generated</span>
                            <span class="badge badge-amber" id="aiResultComplexityBadge">O(N)</span>
                        </div>
                        <h4 id="aiResultTitle" style="font-size: 1.15rem; margin: 0.35rem 0 0 0; color: var(--text);"></h4>
                    </div>

                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button type="button" class="btn-modern btn-secondary btn-sm" onclick="copyAiGeneratedCode()">
                            <i class="fa-regular fa-copy"></i> Copy Code
                        </button>
                        <button type="button" class="btn-modern btn-primary btn-sm" id="aiLoadWorkbenchBtn" onclick="loadAiCodeIntoWorkbench()">
                            <i class="fa-solid fa-play"></i> Load in Editor & Run
                        </button>
                    </div>
                </div>

                <!-- Code Block -->
                <div class="ai-code-preview-box">
                    <div class="ai-code-preview-header">
                        <span><i class="fa-brands fa-python" style="color: #38bdf8; margin-right: 6px;"></i> Python 3.11 &bull; Scientific Script</span>
                        <span id="aiCodeLinesCount" style="font-family: 'JetBrains Mono', monospace; font-size: 0.75rem;">0 lines</span>
                    </div>
                    <pre id="aiCodeBlock" class="ai-code-preview-content"></pre>
                </div>

                <!-- Theoretical & Algorithmic Summary -->
                <div style="background: rgba(56, 189, 248, 0.04); border: 1px solid rgba(56, 189, 248, 0.2); border-radius: var(--radius-md); padding: 1.25rem;">
                    <h5 style="color: var(--accent); margin: 0 0 0.5rem 0; font-size: 0.95rem; display: flex; align-items: center; gap: 0.45rem;">
                        <i class="fa-solid fa-lightbulb"></i> Theoretical Overview & Mechanics
                    </h5>
                    <div id="aiExplanationText" style="font-size: 0.92rem; line-height: 1.65; color: var(--text-color);"></div>
                </div>

                <!-- Suggested Exercises -->
                <div id="aiExercisesContainer" style="display: none;">
                    <h5 style="color: var(--primary); margin: 0 0 0.65rem 0; font-size: 0.95rem; display: flex; align-items: center; gap: 0.45rem;">
                        <i class="fa-solid fa-flask-vial"></i> Suggested Exploratory Exercises
                    </h5>
                    <div id="aiExercisesList" style="display: flex; flex-direction: column; gap: 0.5rem;"></div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="ai-modal-footer">
            <div style="font-size: 0.78rem; color: var(--text-dim);">
                <i class="fa-solid fa-shield-halved" style="color: var(--accent); margin-right: 4px;"></i> Generates safe, verified numerical code directly executable in browser via Pyodide WebAssembly.
            </div>
            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="closeAskAIModal()">
                Close Window
            </button>
        </div>
    </div>
</div>

<script>
window.p4pAiState = {
    generatedCode: '',
    targetEditorPid: null
};

// Initialize API Key from localStorage
document.addEventListener('DOMContentLoaded', function() {
    var storedKey = localStorage.getItem('p4p_gemini_api_key');
    if (storedKey) {
        var keyInput = document.getElementById('aiApiKeyInput');
        if (keyInput) keyInput.value = storedKey;
    }
});

function saveAiApiKey(val) {
    if (val && val.trim()) {
        localStorage.setItem('p4p_gemini_api_key', val.trim());
    } else {
        localStorage.removeItem('p4p_gemini_api_key');
    }
}

function openAskAIModal(initialQuery, targetPid) {
    var modal = document.getElementById('askAiModalBackdrop');
    var input = document.getElementById('aiProblemInput');
    if (!modal) return;

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    if (targetPid) {
        window.p4pAiState.targetEditorPid = targetPid;
    } else {
        // Look for first editor if available on page
        var firstTextarea = document.querySelector('textarea[id^="code_"]');
        if (firstTextarea) {
            window.p4pAiState.targetEditorPid = firstTextarea.id.replace('code_', '');
        }
    }

    if (initialQuery && input) {
        input.value = initialQuery;
    }
    if (input) {
        setTimeout(function() { input.focus(); }, 150);
    }
}

function closeAskAIModal() {
    var modal = document.getElementById('askAiModalBackdrop');
    if (!modal) return;
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

function setAiPromptPreset(promptText) {
    var input = document.getElementById('aiProblemInput');
    if (input) {
        input.value = promptText;
        input.focus();
    }
}

async function submitAskAI() {
    var input = document.getElementById('aiProblemInput');
    var plotCheck = document.getElementById('aiIncludePlotCheck');
    var keyInput = document.getElementById('aiApiKeyInput');
    var btn = document.getElementById('aiGenerateBtn');
    var loader = document.getElementById('aiLoadingIndicator');
    var errorAlert = document.getElementById('aiErrorAlert');
    var resultsArea = document.getElementById('aiResultsArea');

    if (!input || !input.value.trim()) {
        alert('Please enter a problem description first.');
        if (input) input.focus();
        return;
    }

    var problemText = input.value.trim();
    var apiKey = keyInput ? keyInput.value.trim() : (localStorage.getItem('p4p_gemini_api_key') || '');
    var includePlot = plotCheck ? plotCheck.checked : true;

    // UI state: loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';
    loader.style.display = 'block';
    errorAlert.style.display = 'none';
    resultsArea.style.display = 'none';

    try {
        var response = await fetch('<?php echo $siteurl; ?>api/generate_code.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                problem: problemText,
                gemini_api_key: apiKey,
                include_plot: includePlot
            })
        });

        var data = await response.json();

        loader.style.display = 'none';
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Generate Python Code';

        if (!data || !data.success || !data.code) {
            errorAlert.innerText = (data && data.error) ? data.error : 'Failed to generate code. Please try again.';
            errorAlert.style.display = 'block';
            return;
        }

        // Populate results
        window.p4pAiState.generatedCode = data.code;
        var promptEcho = document.getElementById('aiPromptEcho');
        if (promptEcho) promptEcho.innerText = data.user_prompt || problemText;
        document.getElementById('aiResultTitle').innerText = data.title || 'Computational Physics Solution';
        document.getElementById('aiResultComplexityBadge').innerText = data.complexity || 'O(N)';
        document.getElementById('aiResultSourceBadge').innerText = data.source === 'gemini_ai' ? 'Gemini 2.0 AI' : 'Physics Synthesizer';
        document.getElementById('aiResultSourceBadge').className = data.source === 'gemini_ai' ? 'badge badge-primary' : 'badge badge-cyan';

        var codeBlock = document.getElementById('aiCodeBlock');
        codeBlock.innerText = data.code;
        var lineCount = data.code.split('\n').length;
        document.getElementById('aiCodeLinesCount').innerText = lineCount + ' lines';

        document.getElementById('aiExplanationText').innerHTML = data.explanation || 'Numerical simulation script ready for in-browser execution.';

        // Exercises
        var exContainer = document.getElementById('aiExercisesContainer');
        var exList = document.getElementById('aiExercisesList');
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
                    '<button type="button" class="btn-modern btn-secondary btn-sm" onclick="setAiPromptPreset(\'' + escapeJsString(ex) + '\')" style="padding:0.2rem 0.5rem; font-size:0.72rem; flex-shrink:0;">' +
                    '<i class="fa-solid fa-arrow-up"></i> Ask This</button>';
                exList.appendChild(item);
            });
            exContainer.style.display = 'block';
        } else {
            exContainer.style.display = 'none';
        }

        resultsArea.style.display = 'flex';

        // Scroll to results
        setTimeout(function() {
            resultsArea.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);

    } catch (err) {
        loader.style.display = 'none';
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Generate Python Code';
        errorAlert.innerText = 'Network error or unable to reach API: ' + err.message;
        errorAlert.style.display = 'block';
    }
}

function copyAiGeneratedCode() {
    if (!window.p4pAiState.generatedCode) return;
    navigator.clipboard.writeText(window.p4pAiState.generatedCode).then(function() {
        alert('Python code copied to clipboard successfully!');
    }).catch(function() {
        var textarea = document.createElement('textarea');
        textarea.value = window.p4pAiState.generatedCode;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('Python code copied to clipboard!');
    });
}

function loadAiCodeIntoWorkbench() {
    if (!window.p4pAiState.generatedCode) return;

    var targetPid = window.p4pAiState.targetEditorPid;
    
    // Check if we are currently on program.php with CodeMirror editor instance
    if (typeof p4pEditors !== 'undefined' && targetPid && p4pEditors[targetPid]) {
        p4pEditors[targetPid].setValue(window.p4pAiState.generatedCode);
        closeAskAIModal();

        // Highlight editor and optionally auto-execute
        var targetSection = document.getElementById('prog-' + targetPid) || document.getElementById('wrapper_' + targetPid);
        if (targetSection) {
            targetSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        // Execute the code in Pyodide
        if (typeof executeProgram === 'function') {
            executeProgram(targetPid);
        }
    } else {
        // If on another page, save code in sessionStorage and redirect to Python Workbench
        sessionStorage.setItem('p4p_injected_code', window.p4pAiState.generatedCode);
        closeAskAIModal();
        window.location.href = '<?php echo $siteurl; ?>program/python/program.php?menu_id=1&submenu_id=1&auto_run=1';
    }
}

function editModalPrompt() {
    var input = document.getElementById('aiProblemInput');
    if (input) {
        input.focus();
        input.select();
    }
}

function escapeHtml(text) {
    var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

function escapeJsString(str) {
    return String(str).replace(/'/g, "\\'").replace(/"/g, '\\"');
}

// Close on backdrop click or Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAskAIModal();
    }
});
</script>
