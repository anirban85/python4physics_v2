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
    <div style="position: absolute; inset: 0; z-index: 0; opacity: 0.75;">
        <canvas id="physicsHeroCanvas" style="width: 100%; height: 100%; display: block;"></canvas>
    </div>

    <!-- Live Telemetry HUD -->
    <div class="canvas-telemetry">
        <span><i class="fa-solid fa-atom"></i> MODEL: <strong class="active-val">N-BODY CENTRAL FIELD</strong></span>
        <span>INTEGRATOR: <strong class="active-val">SYMPLECTIC VERLET</strong></span>
        <span>ENERGY: <strong class="active-val">$\mathcal{H} = T + V < 0$</strong></span>
        <span style="opacity: 0.7;"><i class="fa-solid fa-mouse-pointer"></i> Move cursor to perturb orbits &middot; Click to pulse</span>
    </div>

    <div class="container" style="position: relative; z-index: 1; text-align: center; max-width: 980px;">
        <div style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; justify-content: center;">
            <span class="badge badge-cyan"><i class="fa-solid fa-atom"></i> Computational Physics Laboratory</span>
            <span class="badge badge-amber"><i class="fa-solid fa-square-root-variable"></i> Numerical ODE & PDE Solvers</span>
            <span class="badge badge-blue"><i class="fa-brands fa-python"></i> Pyodide WASM Runtime</span>
        </div>

        <h1 style="font-size: clamp(2.4rem, 5vw, 4.2rem); font-weight: 800; line-height: 1.15; margin-bottom: 1.5rem;">
            Computational Physics <br>
            <span class="gradient-text">Algorithms, Solvers & Scientific Telemetry</span>
        </h1>

        <p style="font-size: clamp(1.05rem, 2vw, 1.25rem); line-height: 1.6; max-width: 820px; margin: 0 auto 2.5rem auto; color: var(--text-muted);">
            An open academic portal of over <strong>500+ verified computational physics programs</strong>: classical mechanics, coupled differential equations, electrodynamics, quantum state simulations, and Arduino hardware interfacing running natively in your browser.
        </p>

        <!-- Main Action Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 3.5rem;">
            <a href="<?php echo $siteurl; ?>program/python/index.php" class="btn-modern btn-primary btn-lg">
                <i class="fa-brands fa-python"></i> Explore 345+ Python Programs
            </a>
            <a href="#liveSandboxSection" class="btn-modern btn-secondary btn-lg">
                <i class="fa-solid fa-wave-square"></i> Live Oscillation Sandbox
            </a>
            <button type="button" class="btn-modern btn-secondary btn-lg trigger-global-search">
                <i class="fa-solid fa-magnifying-glass"></i> Search Index (Ctrl+K)
            </button>
        </div>

        <!-- Metric Badges Counter with Physics Notation -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1.25rem; max-width: 840px; margin: 0 auto;">
            <div class="glass-card" style="padding: 1.25rem 1rem;">
                <div style="font-size: 2.2rem; font-weight: 800; color: var(--accent); font-family: 'Outfit';">345+</div>
                <div style="font-size: 0.85rem; color: var(--text); font-weight: 600; margin-bottom: 2px;">Python Algorithms</div>
                <div style="font-size: 0.72rem; color: var(--text-dim); font-family: 'JetBrains Mono', monospace;">$N_{\mathrm{algo}} = 345$</div>
            </div>
            <div class="glass-card" style="padding: 1.25rem 1rem;">
                <div style="font-size: 2.2rem; font-weight: 800; color: var(--warning); font-family: 'Outfit';">49+</div>
                <div style="font-size: 0.85rem; color: var(--text); font-weight: 600; margin-bottom: 2px;">GNUplot Curves</div>
                <div style="font-size: 0.72rem; color: var(--text-dim); font-family: 'JetBrains Mono', monospace;">$\mathcal{C}_{\mathrm{2D/3D}} = 49$</div>
            </div>
            <div class="glass-card" style="padding: 1.25rem 1rem;">
                <div style="font-size: 2.2rem; font-weight: 800; color: var(--primary); font-family: 'Outfit';">115+</div>
                <div style="font-size: 0.85rem; color: var(--text); font-weight: 600; margin-bottom: 2px;">LaTeX Papers</div>
                <div style="font-size: 0.72rem; color: var(--text-dim); font-family: 'JetBrains Mono', monospace;">$\mathrm{TikZ} \cdot \mathrm{AMS}$</div>
            </div>
            <div class="glass-card" style="padding: 1.25rem 1rem;">
                <div style="font-size: 2.2rem; font-weight: 800; color: var(--success); font-family: 'Outfit';">20</div>
                <div style="font-size: 0.85rem; color: var(--text); font-weight: 600; margin-bottom: 2px;">Physics Chapters</div>
                <div style="font-size: 0.72rem; color: var(--text-dim); font-family: 'JetBrains Mono', monospace;">$\mathcal{M}_{1\dots 20}$</div>
            </div>
        </div>
    </div>
</section>

<!-- Fundamental Physics Constants Ticker Ribbon (CODATA Recommended) -->
<div class="physics-constants-strip" title="Fundamental Physical Constants (CODATA Reference)">
    <div class="physics-constant-item"><span class="sym">$c$</span><span class="val">$2.9979\times 10^8$</span><span class="unit">$\mathrm{m\cdot s^{-1}}$</span></div>
    <div class="physics-constant-item"><span class="sym">$\hbar$</span><span class="val">$1.0546\times 10^{-34}$</span><span class="unit">$\mathrm{J\cdot s}$</span></div>
    <div class="physics-constant-item"><span class="sym">$G$</span><span class="val">$6.6743\times 10^{-11}$</span><span class="unit">$\mathrm{m^3\cdot kg^{-1}\cdot s^{-2}}$</span></div>
    <div class="physics-constant-item"><span class="sym">$e$</span><span class="val">$1.6022\times 10^{-19}$</span><span class="unit">$\mathrm{C}$</span></div>
    <div class="physics-constant-item"><span class="sym">$k_B$</span><span class="val">$1.3806\times 10^{-23}$</span><span class="unit">$\mathrm{J\cdot K^{-1}}$</span></div>
    <div class="physics-constant-item"><span class="sym">$\varepsilon_0$</span><span class="val">$8.8542\times 10^{-12}$</span><span class="unit">$\mathrm{F\cdot m^{-1}}$</span></div>
    <div class="physics-constant-item"><span class="sym">$\mu_0$</span><span class="val">$4\pi\times 10^{-7}$</span><span class="unit">$\mathrm{H\cdot m^{-1}}$</span></div>
    <div class="physics-constant-item"><span class="sym">$m_e$</span><span class="val">$9.1094\times 10^{-31}$</span><span class="unit">$\mathrm{kg}$</span></div>
</div>


<!-- Main Portal Body -->
<main class="container" style="padding-bottom: 5rem;">

    <!-- Live In-Browser Physics Sandbox -->
    <section id="liveSandboxSection" class="glass-card highlight" style="margin-bottom: 5rem; padding: 2.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
            <div>
                <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap;">
                    <span class="badge badge-cyan"><i class="fa-solid fa-wave-square"></i> Numerical ODE Solver</span>
                    <span class="physics-formula-tag">$m\frac{d^2 x}{dt^2} + b\frac{dx}{dt} + kx = 0$</span>
                    <span class="physics-metric-chip">NumPy $\cdot$ Matplotlib</span>
                </div>
                <h2 style="font-size: 2rem; margin-top: 0.4rem; margin-bottom: 0.35rem;">Oscillatory Dynamics & Phase-Space Simulator</h2>
                <p style="margin: 0; font-size: 0.95rem; color: var(--text-muted);">
                    Execute real-time Python ODE solvers in your browser. Calculate analytical & numerical trajectories, damping envelopes, and phase curves.
                </p>
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

        <!-- Physical Parameters Ribbon -->
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; margin-bottom: 1.5rem; font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; background: var(--bg-secondary); padding: 0.65rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--card-border);">
            <span><strong style="color: var(--text);">Parameters:</strong></span>
            <span class="physics-metric-chip">$m = 1.0\,\mathrm{kg}$</span>
            <span class="physics-metric-chip">$k = 16.0\,\mathrm{N/m}$</span>
            <span class="physics-metric-chip">$b = 0.5\,\mathrm{kg/s}$</span>
            <span class="physics-metric-chip">$\omega_0 = 4.00\,\mathrm{rad/s}$</span>
            <span class="physics-metric-chip">$\gamma = 0.25\,\mathrm{s^{-1}}$</span>
            <span style="color: var(--accent); margin-left: auto;">Regime: Underdamped ($\omega_0 > \gamma$)</span>
        </div>

        <!-- Sandbox Editor & Output Split View -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;" id="homeSandboxGrid">
            <div>
                <div class="editor-header">
                    <span class="editor-title"><i class="fa-brands fa-python" style="color: var(--accent);"></i> Harmonic Oscillator Trajectory (ODE)</span>
                    <span id="homeExecStatus" class="badge badge-cyan">Ready</span>
                </div>
                <div class="editor-wrapper">
                    <textarea id="homeCodeEditor"># Damped Harmonic Oscillator Simulation
# Equation of Motion: m*x'' + b*x' + k*x = 0
import numpy as np
import matplotlib.pyplot as plt

# Physical System Parameters
m = 1.0     # Mass (kg)
k = 16.0    # Spring constant (N/m)
b = 0.5     # Damping coefficient (kg/s)
omega0 = np.sqrt(k / m)
gamma = b / (2 * m)

# Time mesh (s)
t = np.linspace(0, 15, 600)
x0 = 1.0    # Initial displacement (m)
v0 = 0.0    # Initial velocity (m/s)

# Exact analytical solution for underdamped regime
omega = np.sqrt(omega0**2 - gamma**2)
x = np.exp(-gamma * t) * (x0 * np.cos(omega * t) + ((v0 + gamma * x0) / omega) * np.sin(omega * t))

print(f"Natural Frequency (omega0): {omega0:.3f} rad/s")
print(f"Damping Ratio (gamma): {gamma:.3f} s^-1")
print(f"Oscillation Frequency (omega): {omega:.3f} rad/s")
print(f"Quality Factor Q: {omega0 / (2 * gamma):.2f}")

# Scientific Plotting
plt.figure(figsize=(8, 4.5))
plt.plot(t, x, label='Displacement x(t)', color='#38bdf8', lw=2)
plt.plot(t, np.exp(-gamma * t), 'r--', label='+ Envelope x_0*exp(-gamma*t)', alpha=0.75)
plt.plot(t, -np.exp(-gamma * t), 'r--', label='- Envelope', alpha=0.75)
plt.title('Damped Harmonic Oscillator Trajectory [m=1kg, k=16N/m, b=0.5kg/s]', fontsize=11)
plt.xlabel('Time t (seconds)')
plt.ylabel('Displacement x (meters)')
plt.grid(True, alpha=0.25, linestyle='--')
plt.legend(loc='upper right')
plt.tight_layout()
plt.show()
</textarea>
                </div>
            </div>

            <!-- Output Side -->
            <div>
                <div class="tabs-header">
                    <button class="tab-btn active" id="homeTabConsoleBtn"><i class="fa-solid fa-terminal"></i> Console Log</button>
                    <button class="tab-btn" id="homeTabPlotsBtn"><i class="fa-regular fa-image"></i> Rendered Trajectory Plot</button>
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

    <!-- 6 Core Learning Tracks -->
    <section style="margin-bottom: 5rem;">
        <div style="text-align: center; max-width: 760px; margin: 0 auto 3rem auto;">
            <span class="badge badge-cyan"><i class="fa-solid fa-graduation-cap"></i> Systematic Curriculum</span>
            <h2 style="font-size: 2.2rem; margin-top: 0.5rem; margin-bottom: 0.75rem;">Curated Physics Learning Tracks</h2>
            <p>From fundamental numerical algorithms to relativistic and quantum simulations, explore systematically structured tracks.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.75rem;">
            <!-- Track 1 -->
            <div class="glass-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div style="font-size: 1.8rem; color: var(--accent);"><i class="fa-solid fa-code"></i></div>
                    <span class="physics-formula-tag">$\sum x_i \;\vert\; \Delta t$</span>
                </div>
                <h3>1. Python Foundations</h3>
                <p>Variables, control flow, functions, mathematical modules (math & cmath), scientific floating-point precision, and robust file I/O.</p>
                <div style="margin-top: 1.25rem;">
                    <a href="<?php echo $siteurl; ?>program/python/program.php?menu_id=1&submenu_id=1" class="btn-modern btn-outline btn-sm">Explore Track &rarr;</a>
                </div>
            </div>

            <!-- Track 2 -->
            <div class="glass-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div style="font-size: 1.8rem; color: var(--primary);"><i class="fa-solid fa-table-cells"></i></div>
                    <span class="physics-formula-tag">$\mathbf{A}\mathbf{x} = \lambda\mathbf{x} \;\vert\; \int f(x)dx$</span>
                </div>
                <h3>2. Numerical Methods & Matrices</h3>
                <p>NumPy tensor operations, root-finding (Newton-Raphson, Bisection), Simpson & Gauss quadrature, and matrix eigenvalues.</p>
                <div style="margin-top: 1.25rem;">
                    <a href="<?php echo $siteurl; ?>program/python/program.php?menu_id=7&submenu_id=1" class="btn-modern btn-outline btn-sm">Explore Track &rarr;</a>
                </div>
            </div>

            <!-- Track 3 -->
            <div class="glass-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div style="font-size: 1.8rem; color: var(--success);"><i class="fa-solid fa-wave-square"></i></div>
                    <span class="physics-formula-tag">$\frac{d^2x}{dt^2} + \omega^2 x = 0$</span>
                </div>
                <h3>3. Differential Equations & Dynamics</h3>
                <p>Symplectic Euler, RK2, RK4 integrators, coupled nonlinear oscillators, shooting method for BVPs, and Fourier transformations.</p>
                <div style="margin-top: 1.25rem;">
                    <a href="<?php echo $siteurl; ?>program/python/program.php?menu_id=12&submenu_id=1" class="btn-modern btn-outline btn-sm">Explore Track &rarr;</a>
                </div>
            </div>

            <!-- Track 4 -->
            <div class="glass-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div style="font-size: 1.8rem; color: var(--purple);"><i class="fa-solid fa-atom"></i></div>
                    <span class="physics-formula-tag">$i\hbar\frac{\partial\psi}{\partial t} = \hat{H}\psi$</span>
                </div>
                <h3>4. Quantum & Statistical Mechanics</h3>
                <p>Finite-difference solvers for TISE/TDSE, potential barriers, quantum harmonic oscillators, and partition functions $Z = \sum e^{-\beta E_i}$.</p>
                <div style="margin-top: 1.25rem;">
                    <a href="<?php echo $siteurl; ?>program/python/program.php?menu_id=18&submenu_id=1" class="btn-modern btn-outline btn-sm">Explore Track &rarr;</a>
                </div>
            </div>

            <!-- Track 5 -->
            <div class="glass-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div style="font-size: 1.8rem; color: var(--warning);"><i class="fa-solid fa-chart-line"></i></div>
                    <span class="physics-formula-tag">$\mathrm{GNUplot} \;\vert\; \mathrm{TikZ}$</span>
                </div>
                <h3>5. Visualization & Publication</h3>
                <p>Publication-quality 2D/3D scientific graphing with GNUplot, vector field streamlines, and LaTeX manuscript typography with TikZ.</p>
                <div style="margin-top: 1.25rem;">
                    <a href="<?php echo $siteurl; ?>program/gnuplot/index.php" class="btn-modern btn-outline btn-sm">Explore Track &rarr;</a>
                </div>
            </div>

            <!-- Track 6: Arduino Lab -->
            <div class="glass-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div style="font-size: 1.8rem; color: var(--danger);"><i class="fa-solid fa-microchip"></i></div>
                    <span class="physics-formula-tag">$V(t) = V_0 e^{-t/RC}$</span>
                </div>
                <h3>6. Arduino Experimental Lab</h3>
                <p>Computer-interfaced physics apparatus: photogate timing, RC transient curves, acoustic resonance, and live serial telemetry.</p>
                <div style="margin-top: 1.25rem;">
                    <a href="<?php echo $siteurl; ?>arduino.php" class="btn-modern btn-outline btn-sm">Enter Lab &rarr;</a>
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
                            <span style=\"width: 36px; height: 36px; border-radius: 8px; background: rgba(56, 189, 248, 0.12); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.1rem;\">
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
            <p>Authored by physics faculty members dedicated to computational physics research, simulation pedagogy, and open scientific resources.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2.5rem;">
            <!-- Dr. Alorika Chatterjee -->
            <div style="display: flex; gap: 1.5rem; align-items: flex-start;">
                <div style="width: 70px; height: 70px; border-radius: 50%; background: var(--accent-gradient); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.8rem; font-weight: 700; flex-shrink: 0; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25); border: 2px solid var(--card-border);">
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
                <div style="width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, #1d4ed8, #3b82f6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.8rem; font-weight: 700; flex-shrink: 0; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25); border: 2px solid var(--card-border);">
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
