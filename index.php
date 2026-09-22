<?php
/**
 * Python4Physics - Main Landing Portal
 * Authored by Dr. Alorika Chatterjee & Dr. Anirban Shaw
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';
include_once __DIR__ . '/program/python/menu.php';

$page_title = "Computational Physics with Python, GNUplot, LaTeX, Arduino";
$page_description = "The premier computational physics portal: 345+ Python scripts, 49+ GNUplot visualizations, 115+ LaTeX formulations, interactive problem sets, and Arduino lab simulations.";

require_once __DIR__ . '/include/header.php';
require_once __DIR__ . '/include/navbar.php';
?>

<!-- Hero Section with Interactive Physics Canvas Simulation -->
<section style="position: relative; min-height: 85vh; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 4rem 1.5rem;">
    <!-- Interactive Background Canvas -->
    <div style="position: absolute; inset: 0; z-index: 0; opacity: 0.65;">
        <canvas id="physicsHeroCanvas" style="width: 100%; height: 100%; display: block;"></canvas>
    </div>

    <div class="container" style="position: relative; z-index: 1; text-align: center; max-width: 960px;">
        <div style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
            <span class="badge badge-cyan"><i class="fa-solid fa-atom fa-spin"></i> Interactive Physics Portal</span>
            <span class="badge badge-blue"><i class="fa-brands fa-python"></i> Pyodide WebAssembly Engine</span>
        </div>

        <h1 style="font-size: clamp(2.4rem, 5vw, 4.2rem); font-weight: 800; line-height: 1.15; margin-bottom: 1.5rem;">
            Computational Physics <br>
            <span class="gradient-text">Reimagined for the Modern Web</span>
        </h1>

        <p style="font-size: clamp(1.05rem, 2vw, 1.3rem); line-height: 1.6; max-width: 780px; margin: 0 auto 2.5rem auto; color: var(--text-muted);">
            Master numerical physics, differential equations, quantum simulations, and scientific visualization with over <strong>500+ curated algorithms</strong> running directly in your browser.
        </p>

        <!-- Main Action Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 3.5rem;">
            <a href="<?php echo $siteurl; ?>program/python/index.php" class="btn-modern btn-primary btn-lg">
                <i class="fa-brands fa-python"></i> Explore 345+ Python Codes
            </a>
            <a href="#liveSandboxSection" class="btn-modern btn-secondary btn-lg">
                <i class="fa-solid fa-play"></i> Live Sandbox
            </a>
            <button type="button" class="btn-modern btn-secondary btn-lg trigger-global-search">
                <i class="fa-solid fa-magnifying-glass"></i> Search (Ctrl+K)
            </button>
        </div>

        <!-- Metric Badges Counter -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1.25rem; max-width: 820px; margin: 0 auto;">
            <div class="glass-card" style="padding: 1.25rem 1rem;">
                <div style="font-size: 2.2rem; font-weight: 800; color: var(--accent); font-family: 'Outfit';">345+</div>
                <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Python Programs</div>
            </div>
            <div class="glass-card" style="padding: 1.25rem 1rem;">
                <div style="font-size: 2.2rem; font-weight: 800; color: var(--warning); font-family: 'Outfit';">49+</div>
                <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">GNUplot Scripts</div>
            </div>
            <div class="glass-card" style="padding: 1.25rem 1rem;">
                <div style="font-size: 2.2rem; font-weight: 800; color: var(--primary); font-family: 'Outfit';">115+</div>
                <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">LaTeX Formulations</div>
            </div>
            <div class="glass-card" style="padding: 1.25rem 1rem;">
                <div style="font-size: 2.2rem; font-weight: 800; color: var(--success); font-family: 'Outfit';">20+</div>
                <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Physics Domains</div>
            </div>
        </div>
    </div>
</section>

<!-- Main Portal Body -->
<main class="container" style="padding-bottom: 5rem;">

    <!-- Live In-Browser Physics Sandbox -->
    <section id="liveSandboxSection" class="glass-card highlight" style="margin-bottom: 5rem; padding: 2.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <span class="badge badge-cyan"><i class="fa-solid fa-bolt"></i> Instant WebAssembly Runner</span>
                <h2 style="font-size: 2rem; margin-top: 0.5rem; margin-bottom: 0.25rem;">Interactive Physics Playground</h2>
                <p style="margin: 0; font-size: 0.95rem;">Test and execute Python physics code in real-time. NumPy and Matplotlib plots render right inside your browser.</p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <button type="button" id="homeRunBtn" class="btn-modern btn-primary">
                    <i class="fa-solid fa-play"></i> Run Simulation
                </button>
                <button type="button" id="homeExportJupyterBtn" class="btn-modern btn-secondary">
                    <i class="fa-solid fa-download"></i> Export (.ipynb)
                </button>
            </div>
        </div>

        <!-- Sandbox Editor & Output Split View -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;" id="homeSandboxGrid">
            <div>
                <div class="editor-header">
                    <span class="editor-title"><i class="fa-brands fa-python" style="color: var(--accent);"></i> Simulation: Damped Harmonic Oscillator</span>
                    <span id="homeExecStatus" class="badge badge-cyan">Ready</span>
                </div>
                <div class="editor-wrapper">
                    <textarea id="homeCodeEditor"># Damped Harmonic Oscillator Simulation
# m*x'' + b*x' + k*x = 0
import numpy as np
import matplotlib.pyplot as plt

# Physical Parameters
m = 1.0     # Mass (kg)
k = 16.0    # Spring constant (N/m)
b = 0.5     # Damping coefficient (kg/s)
omega0 = np.sqrt(k / m)
gamma = b / (2 * m)

# Time array
t = np.linspace(0, 15, 600)
x0 = 1.0    # Initial displacement (m)
v0 = 0.0    # Initial velocity (m/s)

# Underdamped solution
omega = np.sqrt(omega0**2 - gamma**2)
x = np.exp(-gamma * t) * (x0 * np.cos(omega * t) + ((v0 + gamma * x0) / omega) * np.sin(omega * t))

print(f"Natural Frequency (omega0): {omega0:.3f} rad/s")
print(f"Damping Ratio (gamma): {gamma:.3f} s^-1")
print(f"Oscillation Frequency (omega): {omega:.3f} rad/s")

# Plot Waveform
plt.figure(figsize=(8, 4.5))
plt.plot(t, x, label='Displacement x(t)', color='#06b6d4', lw=2)
plt.plot(t, np.exp(-gamma * t), 'r--', label='Envelope', alpha=0.7)
plt.plot(t, -np.exp(-gamma * t), 'r--', alpha=0.7)
plt.title('Damped Harmonic Oscillator Trajectory', fontsize=12)
plt.xlabel('Time (s)')
plt.ylabel('Position (m)')
plt.grid(True, alpha=0.3)
plt.legend()
plt.tight_layout()
plt.show()
</textarea>
                </div>
            </div>

            <!-- Output Side -->
            <div>
                <div class="tabs-header">
                    <button class="tab-btn active" id="homeTabConsoleBtn"><i class="fa-solid fa-terminal"></i> Console</button>
                    <button class="tab-btn" id="homeTabPlotsBtn"><i class="fa-regular fa-image"></i> Rendered Plot</button>
                </div>
                <div id="homeConsolePanel">
                    <pre id="homeConsoleOutput" class="console-output" style="height: 480px;"></pre>
                </div>
                <div id="homePlotsPanel" style="display: none;">
                    <div id="homePlotsOutput" class="plots-container" style="min-height: 480px; display: flex; align-items: center; justify-content: center;">
                        <div style="color: var(--text-dim); text-align: center;">Click "Run Simulation" to render the plot.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5 Core Learning Tracks -->
    <section style="margin-bottom: 5rem;">
        <div style="text-align: center; max-width: 700px; margin: 0 auto 3rem auto;">
            <span class="badge badge-cyan">Structured Curriculum</span>
            <h2 style="font-size: 2.2rem; margin-top: 0.5rem; margin-bottom: 0.75rem;">Curated Physics Learning Tracks</h2>
            <p>From introductory algorithms to advanced quantum differential equations, follow systematically organized learning tracks.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.75rem;">
            <!-- Track 1 -->
            <div class="glass-card">
                <div style="font-size: 1.8rem; color: var(--accent); margin-bottom: 1rem;"><i class="fa-solid fa-code"></i></div>
                <h3>1. Python Foundations</h3>
                <p>Variables, control flow, loops, functions, mathematical modules (math & cmath), and robust scientific I/O operations.</p>
                <div style="margin-top: 1.25rem;">
                    <a href="<?php echo $siteurl; ?>program/python/program.php?menu_id=1&submenu_id=1" class="btn-modern btn-outline btn-sm">Start Track &rarr;</a>
                </div>
            </div>

            <!-- Track 2 -->
            <div class="glass-card">
                <div style="font-size: 1.8rem; color: var(--primary); margin-bottom: 1rem;"><i class="fa-solid fa-matrix fa-table-cells"></i></div>
                <h3>2. Numerical Methods & Matrices</h3>
                <p>NumPy arrays, SciPy algorithms, root finding, Newton-Raphson, Simpson's integration, and eigenvalue systems.</p>
                <div style="margin-top: 1.25rem;">
                    <a href="<?php echo $siteurl; ?>program/python/program.php?menu_id=7&submenu_id=1" class="btn-modern btn-outline btn-sm">Start Track &rarr;</a>
                </div>
            </div>

            <!-- Track 3 -->
            <div class="glass-card">
                <div style="font-size: 1.8rem; color: var(--success); margin-bottom: 1rem;"><i class="fa-solid fa-wave-square"></i></div>
                <h3>3. Differential Equations</h3>
                <p>Euler, RK2, RK4 algorithms, coupled ODEs, boundary value problems with the Shooting method, and Fourier transformations.</p>
                <div style="margin-top: 1.25rem;">
                    <a href="<?php echo $siteurl; ?>program/python/program.php?menu_id=12&submenu_id=1" class="btn-modern btn-outline btn-sm">Start Track &rarr;</a>
                </div>
            </div>

            <!-- Track 4 -->
            <div class="glass-card">
                <div style="font-size: 1.8rem; color: var(--purple); margin-bottom: 1rem;"><i class="fa-solid fa-atom"></i></div>
                <h3>4. Quantum & Statistical Physics</h3>
                <p>Solving the Time-Independent (TISE) & Time-Dependent Schrödinger Equation (TDSE), potential wells, and partition functions.</p>
                <div style="margin-top: 1.25rem;">
                    <a href="<?php echo $siteurl; ?>program/python/program.php?menu_id=18&submenu_id=1" class="btn-modern btn-outline btn-sm">Start Track &rarr;</a>
                </div>
            </div>

            <!-- Track 5 -->
            <div class="glass-card">
                <div style="font-size: 1.8rem; color: var(--warning); margin-bottom: 1rem;"><i class="fa-solid fa-chart-line"></i></div>
                <h3>5. Visualization & Publication</h3>
                <p>2D/3D plotting with GNUplot, publication-quality graphics, and complete LaTeX document preparation with TikZ.</p>
                <div style="margin-top: 1.25rem;">
                    <a href="<?php echo $siteurl; ?>program/gnuplot/index.php" class="btn-modern btn-outline btn-sm">Start Track &rarr;</a>
                </div>
            </div>

            <!-- Track 6: Arduino Lab -->
            <div class="glass-card">
                <div style="font-size: 1.8rem; color: #ec4899; margin-bottom: 1rem;"><i class="fa-solid fa-microchip"></i></div>
                <h3>6. Arduino Physics Lab</h3>
                <p>Computer-interfaced physics experiments: photogates, RC decay curves, ultrasonic acoustics, and real-time serial plotting.</p>
                <div style="margin-top: 1.25rem;">
                    <a href="<?php echo $siteurl; ?>arduino.php" class="btn-modern btn-outline btn-sm">Explore Lab &rarr;</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Computational Physics Chapter Directory (20 Topics) -->
    <section style="margin-bottom: 5rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
            <div>
                <span class="badge badge-cyan">Full Syllabus Catalog</span>
                <h2 style="font-size: 2.2rem; margin-top: 0.5rem; margin-bottom: 0.25rem;">All 20 Python Physics Chapters</h2>
                <p style="margin: 0;">Comprehensive computational modules matching university physics curricula.</p>
            </div>
            <a href="<?php echo $siteurl; ?>program/python/index.php" class="btn-modern btn-secondary">
                View Full Catalog &rarr;
            </a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem;">
            <?php
            if (isset($menu_titles) && is_array($menu_titles)) {
                $icons = [
                    1 => 'fa-terminal', 2 => 'fa-layer-group', 3 => 'fa-calculator', 4 => 'fa-table-cells',
                    5 => 'fa-chart-pie', 6 => 'fa-chart-line', 7 => 'fa-cubes', 8 => 'fa-equals',
                    9 => 'fa-microscope', 10 => 'fa-bezier-curve', 11 => 'fa-chart-area', 12 => 'fa-route',
                    13 => 'fa-chart-column', 14 => 'fa-infinity', 15 => 'fa-wave-square', 16 => 'fa-water',
                    17 => 'fa-crosshairs', 18 => 'fa-atom', 19 => 'fa-circle-nodes', 20 => 'fa-temperature-half'
                ];

                foreach ($menu_titles as $mid => $mtitle) {
                    $icon = $icons[$mid] ?? 'fa-folder';
                    $subCount = isset($sub_menu_titles[$mid]) ? count($sub_menu_titles[$mid]) : 0;
                    echo "
                    <a href=\"{$siteurl}program/python/program.php?menu_id={$mid}&submenu_id=1\" class=\"glass-card\" style=\"display: block; text-decoration: none; padding: 1.25rem;\">
                        <div style=\"display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;\">
                            <span style=\"width: 36px; height: 36px; border-radius: 8px; background: rgba(6, 182, 212, 0.12); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.1rem;\">
                                <i class=\"fa-solid {$icon}\"></i>
                            </span>
                            <span class=\"badge badge-cyan\" style=\"font-size: 0.7rem;\">Ch. {$mid}</span>
                        </div>
                        <h4 style=\"font-size: 1.05rem; margin-bottom: 0.4rem; color: var(--text);\">" . htmlspecialchars($mtitle) . "</h4>
                        <div style=\"font-size: 0.8rem; color: var(--text-dim); display: flex; align-items: center; gap: 0.4rem;\">
                            <i class=\"fa-regular fa-folder-open\"></i> {$subCount} Subtopics Available
                        </div>
                    </a>";
                }
            }
            ?>
        </div>
    </section>

    <!-- Faculty & Authors Showcase -->
    <section class="glass-card" style="padding: 3rem 2rem; margin-bottom: 3rem;">
        <div style="text-align: center; max-width: 700px; margin: 0 auto 3rem auto;">
            <span class="badge badge-cyan"><i class="fa-solid fa-graduation-cap"></i> Academic Leadership</span>
            <h2 style="font-size: 2.2rem; margin-top: 0.5rem; margin-bottom: 0.75rem;">Platform Authors & Curators</h2>
            <p>Authored by faculty members dedicated to advancing computational physics pedagogy and open educational resources.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2.5rem;">
            <!-- Dr. Alorika Chatterjee -->
            <div style="display: flex; gap: 1.5rem; align-items: flex-start;">
                <div style="width: 72px; height: 72px; border-radius: 50%; background: var(--accent-gradient); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 2rem; font-weight: 700; flex-shrink: 0; box-shadow: 0 4px 15px var(--accent-glow);">
                    AC
                </div>
                <div>
                    <h3 style="font-size: 1.35rem; margin-bottom: 0.25rem;">Dr. Alorika Chatterjee</h3>
                    <p style="color: var(--accent); font-size: 0.95rem; font-weight: 500; margin-bottom: 0.5rem;">Assistant Professor, Department of Physics</p>
                    <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1rem;">
                        The Heritage College, Kolkata, India. Researcher in theoretical and computational physics, curriculum design, and scientific programming.
                    </p>
                    <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
                        <a href="mailto:alorika.chatterjee1@gmail.com" class="btn-modern btn-secondary btn-sm"><i class="fa-regular fa-envelope"></i> Email</a>
                        <a href="https://www.thc.edu.in/Faculty.aspx" target="_blank" rel="noopener" class="btn-modern btn-secondary btn-sm"><i class="fa-solid fa-globe"></i> Faculty Page</a>
                    </div>
                </div>
            </div>

            <!-- Dr. Anirban Shaw -->
            <div style="display: flex; gap: 1.5rem; align-items: flex-start;">
                <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 2rem; font-weight: 700; flex-shrink: 0; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);">
                    AS
                </div>
                <div>
                    <h3 style="font-size: 1.35rem; margin-bottom: 0.25rem;">Dr. Anirban Shaw</h3>
                    <p style="color: var(--primary); font-size: 0.95rem; font-weight: 500; margin-bottom: 0.5rem;">Assistant Professor, Department of Physics</p>
                    <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1rem;">
                        Dhruba Chand Halder College, West Bengal, India. Specialist in numerical methods, computational electrodynamics, and automated physics instruction.
                    </p>
                    <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
                        <a href="mailto:anirbanshaw@python4physics.in" class="btn-modern btn-secondary btn-sm"><i class="fa-regular fa-envelope"></i> Email</a>
                        <a href="https://dchcollege.org/main/departments/details.php?faculty=1103" target="_blank" rel="noopener" class="btn-modern btn-secondary btn-sm"><i class="fa-solid fa-globe"></i> Faculty Page</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

<!-- Load Physics Simulation Canvas Script & Pyodide Runner Script -->
<script src="<?php echo $siteurl; ?>assets/js/physics-canvas.js"></script>
<script src="<?php echo $siteurl; ?>assets/js/pyodide-runner.js"></script>

<!-- Live Sandbox Interactive Logic -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize CodeMirror for homepage sandbox
    var editorEl = document.getElementById('homeCodeEditor');
    var homeEditor = CodeMirror.fromTextArea(editorEl, {
        mode: 'python',
        theme: 'material-darker',
        lineNumbers: true,
        matchBrackets: true,
        styleActiveLine: true,
        indentUnit: 4,
        viewportMargin: Infinity
    });

    var runBtn = document.getElementById('homeRunBtn');
    var exportBtn = document.getElementById('homeExportJupyterBtn');
    var statusBadge = document.getElementById('homeExecStatus');
    var consoleOutput = document.getElementById('homeConsoleOutput');
    var plotsOutput = document.getElementById('homePlotsOutput');

    var tabConsoleBtn = document.getElementById('homeTabConsoleBtn');
    var tabPlotsBtn = document.getElementById('homeTabPlotsBtn');
    var consolePanel = document.getElementById('homeConsolePanel');
    var plotsPanel = document.getElementById('homePlotsPanel');

    // Tab Switching
    tabConsoleBtn.addEventListener('click', function() {
        tabConsoleBtn.classList.add('active');
        tabPlotsBtn.classList.remove('active');
        consolePanel.style.display = 'block';
        plotsPanel.style.display = 'none';
    });

    tabPlotsBtn.addEventListener('click', function() {
        tabPlotsBtn.classList.add('active');
        tabConsoleBtn.classList.remove('active');
        plotsPanel.style.display = 'block';
        consolePanel.style.display = 'none';
    });

    // Run Code
    runBtn.addEventListener('click', async function() {
        var code = homeEditor.getValue();
        runBtn.disabled = true;
        runBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Running...';
        statusBadge.className = 'badge badge-amber';
        statusBadge.innerText = 'Executing...';

        try {
            await window.physicsRunner.run(code, {
                onStatus: function(msg) {
                    statusBadge.innerText = msg;
                },
                consoleEl: consoleOutput,
                plotsEl: plotsOutput,
                timeEl: null
            });
            statusBadge.className = 'badge badge-emerald';
            statusBadge.innerText = 'Completed';
        } catch (e) {
            statusBadge.className = 'badge badge-purple';
            statusBadge.innerText = 'Failed';
        } finally {
            runBtn.disabled = false;
            runBtn.innerHTML = '<i class="fa-solid fa-play"></i> Run Simulation';
        }
    });

    // Export to Jupyter
    exportBtn.addEventListener('click', function() {
        var code = homeEditor.getValue();
        window.physicsRunner.exportToJupyter(
            "Damped Harmonic Oscillator Simulation",
            code,
            "Simulation of underdamped harmonic oscillator using NumPy and Matplotlib."
        );
    });

    // Ctrl+Enter shortcut in editor to run
    homeEditor.setOption("extraKeys", {
        "Ctrl-Enter": function() { runBtn.click(); },
        "Cmd-Enter": function() { runBtn.click(); }
    });
});
</script>

<?php require_once __DIR__ . '/include/footer.php'; ?>
