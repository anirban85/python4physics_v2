<?php
/**
 * Python4Physics - Interactive Physics Assignments & Problem Bank
 * Curated for Undergraduate & Postgraduate (Graduate) Physics Students
 * Topics Include: Central Force (8), Scattering (2), Mechanics of Continuum (6), Quantum, Electrodynamics & Thermo.
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';

$page_title = "Interactive Physics Assignments & Graduate Labs";
$page_description = "Graduate & undergraduate level computational physics assignments with interactive Python parameter sliders: Central Force orbits, Two-body scattering, and Continuum fluid mechanics.";

require_once __DIR__ . '/include/header.php';
require_once __DIR__ . '/include/navbar.php';

// Fetch assignments from database
$db_assignments = [];
try {
    if (isset($conn) && $conn !== null) {
        $stmt = $conn->query("SELECT * FROM `assignments` ORDER BY `id` ASC");
        $db_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $db_assignments = [];
}

// Fallback assignments definitions if database is empty or not yet seeded
if (count($db_assignments) < 9) {
    // Require the seeding definition file to get the full comprehensive 9-lab catalog
    if (file_exists(__DIR__ . '/seed_assignments_v2.php')) {
        // Run seed or read array
        try {
            if (isset($conn) && $conn !== null) {
                include_once __DIR__ . '/seed_assignments_v2.php';
                $stmt = $conn->query("SELECT * FROM `assignments` ORDER BY `id` ASC");
                $db_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {}
    }
}

// Interactive Slider Configuration for Physics Labs
$SLIDER_CONFIG = [
    5 => [
        'title' => 'Orbital Mechanics & Relativistic Precession Parameters',
        'sliders' => [
            ['id' => 'e', 'var' => 'e', 'label' => 'Eccentricity (e)', 'min' => 0.0, 'max' => 0.85, 'step' => 0.05, 'val' => 0.60, 'unit' => ''],
            ['id' => 'a', 'var' => 'a', 'label' => 'Semi-Major Axis (a)', 'min' => 0.8, 'max' => 3.0, 'step' => 0.1, 'val' => 1.50, 'unit' => 'AU'],
            ['id' => 'alpha', 'var' => 'alpha', 'label' => 'Perturbation Strength (α)', 'min' => 0.000, 'max' => 0.040, 'step' => 0.005, 'val' => 0.015, 'unit' => ''],
            ['id' => 'num_orbits', 'var' => 'num_orbits', 'label' => 'Simulation Orbits', 'min' => 1, 'max' => 8, 'step' => 1, 'val' => 4, 'unit' => 'rev']
        ],
        'hud_id' => 'hud_5'
    ],
    6 => [
        'title' => 'Planetary Potential & Gauss Theory Parameters',
        'sliders' => [
            ['id' => 'R', 'var' => 'R', 'label' => 'Body Radius (R)', 'min' => 2.0, 'max' => 10.0, 'step' => 0.5, 'val' => 5.0, 'unit' => 'units'],
            ['id' => 'M', 'var' => 'M', 'label' => 'Total Mass (M)', 'min' => 2.0, 'max' => 25.0, 'step' => 1.0, 'val' => 10.0, 'unit' => 'units'],
            ['id' => 'sigma', 'var' => 'sigma', 'label' => 'Sheet Surface Density (σ)', 'min' => 0.5, 'max' => 5.0, 'step' => 0.5, 'val' => 2.0, 'unit' => 'units']
        ],
        'hud_id' => 'hud_6'
    ],
    7 => [
        'title' => 'Two-Body Collision & Scattering Parameters',
        'sliders' => [
            ['id' => 'E_cm', 'var' => 'E_cm', 'label' => 'Center-of-Mass Energy (E)', 'min' => 1.0, 'max' => 15.0, 'step' => 0.5, 'val' => 5.0, 'unit' => 'MeV'],
            ['id' => 'k', 'var' => 'k', 'label' => 'Coulomb Repulsion (k)', 'min' => 1.0, 'max' => 8.0, 'step' => 0.5, 'val' => 4.0, 'unit' => 'units']
        ],
        'hud_id' => 'hud_7'
    ],
    8 => [
        'title' => 'Stokes Settling & Reynolds Dynamics Parameters',
        'sliders' => [
            ['id' => 'r_mm', 'var' => 'r_mm', 'label' => 'Sphere Radius (r)', 'min' => 0.5, 'max' => 8.0, 'step' => 0.5, 'val' => 3.0, 'unit' => 'mm'],
            ['id' => 'eta', 'var' => 'eta', 'label' => 'Dynamic Viscosity (η)', 'min' => 0.05, 'max' => 1.50, 'step' => 0.05, 'val' => 0.45, 'unit' => 'Pa·s'],
            ['id' => 'rho_s', 'var' => 'rho_s', 'label' => 'Sphere Density (ρ_s)', 'min' => 1500, 'max' => 8900, 'step' => 200, 'val' => 7800, 'unit' => 'kg/m³']
        ],
        'hud_id' => 'hud_8'
    ],
    9 => [
        'title' => 'Continuity & Venturi Bernoulli Parameters',
        'sliders' => [
            ['id' => 'Q', 'var' => 'Q', 'label' => 'Volumetric Flow Rate (Q)', 'min' => 0.005, 'max' => 0.040, 'step' => 0.002, 'val' => 0.018, 'unit' => 'm³/s'],
            ['id' => 'throat_ratio', 'var' => 'throat_ratio', 'label' => 'Throat Constriction (R_th/R0)', 'min' => 0.25, 'max' => 0.85, 'step' => 0.05, 'val' => 0.45, 'unit' => ''],
            ['id' => 'P0', 'var' => 'P0', 'label' => 'Inlet Pressure (P_0)', 'min' => 80000, 'max' => 160000, 'step' => 5000, 'val' => 101325, 'unit' => 'Pa']
        ],
        'hud_id' => 'hud_9'
    ],
    1 => [
        'title' => 'Projectile Ballistics & Drag Parameters',
        'sliders' => [
            ['id' => 'v0', 'var' => 'v0', 'label' => 'Launch Speed (v_0)', 'min' => 15, 'max' => 90, 'step' => 1, 'val' => 45.0, 'unit' => 'm/s'],
            ['id' => 'c', 'var' => 'c', 'label' => 'Air Drag Constant (c)', 'min' => 0.0005, 'max' => 0.0040, 'step' => 0.0005, 'val' => 0.0015, 'unit' => 'kg/m']
        ],
        'hud_id' => 'hud_1'
    ]
];
?>

<main class="container" style="padding-top: 3rem; padding-bottom: 5rem; max-width: 100vw; overflow-x: hidden;">
    <!-- Page Header -->
    <div style="text-align: center; max-width: 900px; margin: 0 auto 3rem auto;">
        <span class="badge badge-emerald" style="font-size: 0.85rem; padding: 0.3rem 0.8rem;">
            <i class="fa-solid fa-graduation-cap"></i> Advanced Computational Physics Lab Bank
        </span>
        <h1 style="font-size: 2.8rem; margin-top: 0.75rem; margin-bottom: 0.75rem; letter-spacing: -0.02em;">
            Interactive Physics <span class="gradient-text">Assignments & Labs</span>
        </h1>
        <p style="font-size: 1.15rem; line-height: 1.6; color: var(--text-muted);">
            Graduate and undergraduate computational physics problem sets matching premier university curricula: 
            <strong>Central Force (8)</strong>, <strong>Two-Body Scattering (2)</strong>, <strong>Mechanics of Continuum (6)</strong>, 
            Electrodynamics, and Quantum Mechanics. Experiment interactively with real-time parameter sliders.
        </p>
    </div>

    <!-- Curriculum Topic Filter Tabs -->
    <div style="display: flex; justify-content: center; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 3.5rem;">
        <button type="button" class="btn-modern btn-primary btn-sm" onclick="filterAssignments('all', this)">
            <i class="fa-solid fa-cubes"></i> All Topics (<?php echo count($db_assignments); ?> Labs)
        </button>
        <button type="button" class="btn-modern btn-secondary btn-sm" onclick="filterAssignments('central-force', this)">
            <i class="fa-solid fa-circle-nodes"></i> 4. Central Force (8)
        </button>
        <button type="button" class="btn-modern btn-secondary btn-sm" onclick="filterAssignments('scattering', this)">
            <i class="fa-solid fa-burst"></i> 5. Scattering (2)
        </button>
        <button type="button" class="btn-modern btn-secondary btn-sm" onclick="filterAssignments('fluid-mechanics', this)">
            <i class="fa-solid fa-water"></i> 6. Mechanics of Continuum (6)
        </button>
        <button type="button" class="btn-modern btn-secondary btn-sm" onclick="filterAssignments('mechanics', this)">
            <i class="fa-solid fa-atom"></i> Classical Mechanics
        </button>
        <button type="button" class="btn-modern btn-secondary btn-sm" onclick="filterAssignments('electrodynamics', this)">
            <i class="fa-solid fa-bolt"></i> Electrodynamics
        </button>
        <button type="button" class="btn-modern btn-secondary btn-sm" onclick="filterAssignments('quantum', this)">
            <i class="fa-solid fa-wave-square"></i> Quantum Mechanics
        </button>
        <button type="button" class="btn-modern btn-secondary btn-sm" onclick="filterAssignments('thermo', this)">
            <i class="fa-solid fa-fire"></i> Thermodynamics
        </button>
    </div>

    <!-- Assignments List -->
    <div style="display: flex; flex-direction: column; gap: 3.5rem;">
        <?php if (empty($db_assignments)): ?>
            <div class="glass-card" style="text-align: center; padding: 4rem 2rem;">
                <i class="fa-solid fa-graduation-cap" style="font-size: 3rem; opacity: 0.4; margin-bottom: 1rem; color: var(--accent);"></i>
                <h2>Loading Physics Problem Bank...</h2>
                <p style="color: var(--text-muted);">Initializing numerical algorithms and WebAssembly runtime.</p>
            </div>
        <?php else: ?>
            <?php foreach ($db_assignments as $idx => $as): 
                $as_id = (int)$as['id'];
                $cat = htmlspecialchars($as['category']);
                $badge_class = 'badge-cyan';
                $cat_display = 'Classical Mechanics';
                
                if ($cat === 'central-force') {
                    $badge_class = 'badge-blue';
                    $cat_display = '4. Central Force (8)';
                } elseif ($cat === 'scattering') {
                    $badge_class = 'badge-purple';
                    $cat_display = '5. Scattering (2)';
                } elseif ($cat === 'fluid-mechanics') {
                    $badge_class = 'badge-emerald';
                    $cat_display = '6. Mechanics of Continuum (6)';
                } elseif ($cat === 'electrodynamics') {
                    $badge_class = 'badge-blue';
                    $cat_display = 'Electrodynamics';
                } elseif ($cat === 'quantum') {
                    $badge_class = 'badge-purple';
                    $cat_display = 'Quantum Mechanics';
                } elseif ($cat === 'thermo') {
                    $badge_class = 'badge-amber';
                    $cat_display = 'Thermodynamics';
                }
            ?>
                <article class="glass-card assignment-item" data-category="<?php echo $cat; ?>" id="assign-<?php echo $as_id; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <span class="badge <?php echo $badge_class; ?>" style="margin-bottom: 0.5rem;">
                                Lab <?php echo str_pad($as_id, 2, '0', STR_PAD_LEFT); ?> &bull; <?php echo $cat_display; ?>
                            </span>
                            <h2 style="font-size: 1.7rem; margin-bottom: 0.25rem; letter-spacing: -0.01em;"><?php echo htmlspecialchars($as['title']); ?></h2>
                            <?php if (!empty($as['subtitle'])): ?>
                                <p style="margin: 0; font-size: 0.98rem; color: var(--text-muted); font-weight: 500;"><?php echo htmlspecialchars($as['subtitle']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($as['description'])): ?>
                                <p style="margin: 0.5rem 0 0 0; font-size: 0.94rem; line-height: 1.55;"><?php echo htmlspecialchars($as['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="exportAssignmentJupyter('assign_code_<?php echo $as_id; ?>', '<?php echo htmlspecialchars(addslashes($as['title'])); ?>')">
                                <i class="fa-solid fa-download"></i> Jupyter (.ipynb)
                            </button>
                        </div>
                    </div>

                    <?php if (!empty($as['theory_equations']) || !empty($as['parameters'])): ?>
                        <div class="theory-card" style="margin-bottom: 1.5rem; background: var(--bg-tertiary); border: 1px solid var(--card-border); border-radius: var(--radius-md); padding: 1.25rem 1.5rem;">
                            <h4 style="font-size: 1rem; margin-bottom: 0.6rem; color: var(--text);">
                                <i class="fa-solid fa-calculator" style="color: var(--accent); margin-right: 6px;"></i> Governing Equations & Physical Formulation
                            </h4>
                            <?php if (!empty($as['theory_equations'])): ?>
                                <div class="katex-display" style="overflow-x: auto; margin: 0.5rem 0 0.75rem 0; font-size: 1.05rem;">
                                    <?php echo $as['theory_equations']; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($as['parameters'])): ?>
                                <p style="margin: 0; font-size: 0.88rem; color: var(--text-dim);">
                                    <strong>Default Baseline:</strong> <?php echo htmlspecialchars($as['parameters']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Interactive Parameter Studio (If Sliders Defined) -->
                    <?php if (isset($SLIDER_CONFIG[$as_id])): 
                        $cfg = $SLIDER_CONFIG[$as_id];
                    ?>
                        <div class="param-slider-studio">
                            <div class="param-slider-studio-header">
                                <h3 class="param-studio-title">
                                    <i class="fa-solid fa-sliders" style="color: var(--accent);"></i>
                                    Interactive Parameter Studio
                                    <span style="font-size: 0.76rem; font-weight: normal; color: var(--text-muted); margin-left: 0.5rem;">(Changes dynamically update Python Code & Math Telemetry)</span>
                                </h3>
                                <button type="button" class="btn-modern btn-secondary btn-sm" onclick="runParamSimulation(<?php echo $as_id; ?>)">
                                    <i class="fa-solid fa-play" style="color: var(--success);"></i> Run with Parameters
                                </button>
                            </div>

                            <div class="param-sliders-grid">
                                <?php foreach ($cfg['sliders'] as $sl): ?>
                                    <div class="param-slider-group">
                                        <div class="param-slider-label">
                                            <span><?php echo htmlspecialchars($sl['label']); ?></span>
                                            <span class="param-slider-val" id="val_<?php echo $as_id; ?>_<?php echo $sl['id']; ?>">
                                                <?php echo $sl['val']; ?> <?php echo $sl['unit']; ?>
                                            </span>
                                        </div>
                                        <input type="range" 
                                               class="physics-range-slider" 
                                               id="slider_<?php echo $as_id; ?>_<?php echo $sl['id']; ?>"
                                               min="<?php echo $sl['min']; ?>" 
                                               max="<?php echo $sl['max']; ?>" 
                                               step="<?php echo $sl['step']; ?>" 
                                               value="<?php echo $sl['val']; ?>"
                                               oninput="onParamSliderChange(<?php echo $as_id; ?>, '<?php echo $sl['var']; ?>', this.value, '<?php echo $sl['unit']; ?>')">
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Live Telemetry HUD -->
                            <div class="param-hud-ribbon" id="<?php echo $cfg['hud_id']; ?>">
                                <span><i class="fa-solid fa-gauge-high" style="color: var(--accent);"></i> TELEMETRY:</span>
                                <span class="param-hud-loading">Drag sliders above to view live analytical metrics...</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Workspace Pane: Editor & Output -->
                    <div class="workspace-container" style="margin-top: 0; min-height: 440px; width: 100%; max-width: 100%;">
                        <div class="workspace-pane" style="min-width: 0; max-width: 100%;">
                            <div class="editor-header">
                                <span class="editor-title"><i class="fa-brands fa-python" style="color: var(--accent);"></i> Python Computational Solver</span>
                                <span id="assign_status_<?php echo $as_id; ?>" class="badge <?php echo $badge_class; ?>">Ready</span>
                            </div>
                            <div class="editor-wrapper" style="width: 100%; max-width: 100%;">
                                <textarea id="assign_code_<?php echo $as_id; ?>" class="assignment-code-textarea"><?php echo htmlspecialchars($as['starter_code']); ?></textarea>
                            </div>
                            <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <button type="button" class="btn-modern btn-primary" onclick="runAssignmentCode('assign_code_<?php echo $as_id; ?>', 'assign_status_<?php echo $as_id; ?>', 'assign_console_<?php echo $as_id; ?>', 'assign_plots_<?php echo $as_id; ?>')">
                                    <i class="fa-solid fa-play"></i> Execute Python Solver
                                </button>
                            </div>
                        </div>

                        <div class="workspace-pane" style="min-width: 0; max-width: 100%;">
                            <div class="tabs-header">
                                <button class="tab-btn active"><i class="fa-solid fa-chart-line"></i> Dynamic Scientific Plot & Diagnostics</button>
                            </div>
                            <div class="plots-container" id="assign_plots_<?php echo $as_id; ?>" style="min-height: 260px; width: 100%; max-width: 100%; display: flex; align-items: center; justify-content: center; background: var(--bg-tertiary);">
                                <span style="color: var(--text-dim); font-size: 0.88rem; font-family: 'JetBrains Mono', monospace;">
                                    <i class="fa-solid fa-chart-area" style="opacity: 0.5; margin-right: 6px;"></i> Click "Execute Python Solver" to render scientific figures
                                </span>
                            </div>
                            <pre class="console-output" id="assign_console_<?php echo $as_id; ?>" style="min-height: 140px; max-width: 100%; overflow-x: hidden; white-space: pre-wrap; word-break: break-word; font-size: 0.82rem;"></pre>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<script src="<?php echo $siteurl; ?>assets/js/pyodide-runner.js"></script>
<script>
var assignEditors = {};

document.addEventListener('DOMContentLoaded', function() {
    // Initialize CodeMirror Editors
    document.querySelectorAll('.assignment-code-textarea').forEach(el => {
        assignEditors[el.id] = CodeMirror.fromTextArea(el, {
            mode: 'python',
            theme: 'material-darker',
            lineNumbers: true,
            matchBrackets: true,
            lineWrapping: true,
            viewportMargin: Infinity
        });
    });

    // Initialize Telemetry HUDs for labs with sliders
    [1, 5, 6, 7, 8, 9].forEach(id => {
        updateAssignmentTelemetry(id);
    });
});

/**
 * Handle Slider Input & Synchronize with CodeMirror
 */
function onParamSliderChange(asId, varName, value, unit) {
    // 1. Update Slider Value Badge
    const valBadge = document.getElementById(`val_${asId}_${varName}`);
    if (valBadge) {
        valBadge.innerText = `${value} ${unit}`;
    }

    // 2. Synchronize variable value directly into the CodeMirror editor
    const editor = assignEditors[`assign_code_${asId}`];
    if (editor) {
        let code = editor.getValue();
        // Regex matches assignment e.g. "e = 0.60" or "r_mm = 3.0"
        const regex = new RegExp(`^(\\s*${varName}\\s*=\\s*)[0-9\\.eE\\-\\+]+(.*)$`, 'm');
        if (regex.test(code)) {
            code = code.replace(regex, `$1${value}$2`);
            editor.setValue(code);
        }
    }

    // 3. Update Real-Time Live Telemetry HUD
    updateAssignmentTelemetry(asId);
}

/**
 * Live Mathematical Telemetry HUD Calculations
 */
function updateAssignmentTelemetry(asId) {
    const hud = document.getElementById(`hud_${asId}`);
    if (!hud) return;

    if (asId === 5) {
        // Central Force Orbits
        const eEl = document.getElementById('slider_5_e');
        const aEl = document.getElementById('slider_5_a');
        const alphaEl = document.getElementById('slider_5_alpha');
        if (!eEl || !aEl) return;

        const e = parseFloat(eEl.value);
        const a = parseFloat(aEl.value);
        const alpha = parseFloat(alphaEl.value);

        const r_peri = (a * (1 - e)).toFixed(3);
        const r_aph = (a * (1 + e)).toFixed(3);
        const period = (2 * Math.PI * Math.sqrt(Math.pow(a, 3))).toFixed(2);
        const L = Math.sqrt(a * (1 - e * e)).toFixed(3);
        const precession = (alpha > 0) ? (2 * Math.PI * alpha / (1 - e * e) * 180 / Math.PI).toFixed(2) + '°/rev' : '0.00° (Closed)';

        hud.innerHTML = `
            <span class="param-hud-item"><i class="fa-solid fa-route" style="color:var(--accent)"></i> Perihelion r₀: <span class="param-hud-val">${r_peri} AU</span></span>
            <span class="param-hud-item"><i class="fa-solid fa-circle" style="color:var(--success)"></i> Aphelion: <span class="param-hud-val">${r_aph} AU</span></span>
            <span class="param-hud-item"><i class="fa-solid fa-clock" style="color:var(--warning)"></i> Kepler Period T: <span class="param-hud-val">${period}</span></span>
            <span class="param-hud-item"><i class="fa-solid fa-arrows-spin" style="color:var(--danger)"></i> Precession Δθ: <span class="param-hud-val">${precession}</span></span>
            <span class="param-hud-item"><i class="fa-solid fa-shield" style="color:var(--accent)"></i> L Ang. Momentum: <span class="param-hud-val">${L}</span></span>
        `;
    } else if (asId === 6) {
        // Gauss's Law Gravitation
        const rEl = document.getElementById('slider_6_R');
        const mEl = document.getElementById('slider_6_M');
        if (!rEl || !mEl) return;

        const R = parseFloat(rEl.value);
        const M = parseFloat(mEl.value);

        const g_surf = (-M / (R * R)).toFixed(3);
        const phi_0 = (-1.5 * M / R).toFixed(3);
        const t_tunnel = (2 * Math.PI * Math.sqrt(Math.pow(R, 3) / M)).toFixed(2);

        hud.innerHTML = `
            <span class="param-hud-item"><i class="fa-solid fa-weight-hanging" style="color:var(--accent)"></i> Surface Gravity g(R): <span class="param-hud-val">${g_surf}</span></span>
            <span class="param-hud-item"><i class="fa-solid fa-circle-down" style="color:var(--warning)"></i> Center Potential Φ(0): <span class="param-hud-val">${phi_0}</span></span>
            <span class="param-hud-item"><i class="fa-solid fa-stopwatch" style="color:var(--success)"></i> Tunnel Period T: <span class="param-hud-val">${t_tunnel} s</span></span>
        `;
    } else if (asId === 7) {
        // Two-Body Scattering
        const eEl = document.getElementById('slider_7_E_cm');
        const kEl = document.getElementById('slider_7_k');
        if (!eEl || !kEl) return;

        const E = parseFloat(eEl.value);
        const k = parseFloat(kEl.value);
        const b90 = (k / (2 * E)).toFixed(3);
        const r_min_1 = (k / (2 * E) + Math.sqrt(Math.pow(k / (2 * E), 2) + 1.44)).toFixed(3);

        hud.innerHTML = `
            <span class="param-hud-item"><i class="fa-solid fa-bolt" style="color:var(--accent)"></i> Collision Energy E: <span class="param-hud-val">${E} MeV</span></span>
            <span class="param-hud-item"><i class="fa-solid fa-arrows-split-up-and-left" style="color:var(--success)"></i> Impact Param for 90°: <span class="param-hud-val">${b90} fm</span></span>
            <span class="param-hud-item"><i class="fa-solid fa-compress" style="color:var(--warning)"></i> Closest Approach (b=1.2): <span class="param-hud-val">${r_min_1} fm</span></span>
        `;
    } else if (asId === 8) {
        // Stokes Settling & Reynolds Dynamics
        const rEl = document.getElementById('slider_8_r_mm');
        const etaEl = document.getElementById('slider_8_eta');
        const rhoEl = document.getElementById('slider_8_rho_s');
        if (!rEl || !etaEl || !rhoEl) return;

        const r_m = parseFloat(rEl.value) * 1e-3;
        const eta = parseFloat(etaEl.value);
        const rho_s = parseFloat(rhoEl.value);
        const rho_f = 1260.0;
        const g = 9.81;

        const v_t = ((2 * r_m * r_m * g * (rho_s - rho_f)) / (9 * eta)).toFixed(4);
        const Re = ((rho_f * parseFloat(v_t) * 2 * r_m) / eta).toFixed(2);

        let chipClass = 'status-laminar';
        let chipText = 'Creeping Laminar (Stokes Valid)';
        if (Re >= 1000) {
            chipClass = 'status-turbulent';
            chipText = 'Turbulent Separation (Newton Regime)';
        } else if (Re >= 1) {
            chipClass = 'status-transitional';
            chipText = 'Transitional Vortex Shedding';
        }

        hud.innerHTML = `
            <span class="param-hud-item"><i class="fa-solid fa-arrow-down" style="color:var(--accent)"></i> Stokes v_t: <span class="param-hud-val">${v_t} m/s</span></span>
            <span class="param-hud-item"><i class="fa-solid fa-wind" style="color:var(--warning)"></i> Reynolds Number Re: <span class="param-hud-val">${Re}</span></span>
            <span class="param-status-chip ${chipClass}"><i class="fa-solid fa-circle"></i> ${chipText}</span>
        `;
    } else if (asId === 9) {
        // Continuity & Venturi Bernoulli
        const qEl = document.getElementById('slider_9_Q');
        const ratioEl = document.getElementById('slider_9_throat_ratio');
        const p0El = document.getElementById('slider_9_P0');
        if (!qEl || !ratioEl || !p0El) return;

        const Q = parseFloat(qEl.value);
        const ratio = parseFloat(ratioEl.value);
        const P0 = parseFloat(p0El.value);
        const R0 = 0.05;
        const R_th = R0 * ratio;

        const v0 = (Q / (Math.PI * R0 * R0)).toFixed(2);
        const v_th = (Q / (Math.PI * R_th * R_th)).toFixed(2);
        const deltaP = (0.5 * 1000 * (v_th * v_th - v0 * v0)).toFixed(0);
        const P_th = ((P0 - deltaP) / 1000).toFixed(1);

        let cavChip = (P_th <= 2.34) 
            ? '<span class="param-status-chip status-turbulent"><i class="fa-solid fa-triangle-exclamation"></i> CAVITATION ONSET! Vapor Cavity Forms</span>'
            : '<span class="param-status-chip status-laminar"><i class="fa-solid fa-check"></i> Safe Single-Phase Incompressible Flow</span>';

        hud.innerHTML = `
            <span class="param-hud-item"><i class="fa-solid fa-gauge" style="color:var(--accent)"></i> Inlet v₀: <span class="param-hud-val">${v0} m/s</span></span>
            <span class="param-hud-item"><i class="fa-solid fa-gauge-high" style="color:var(--warning)"></i> Throat v_max: <span class="param-hud-val">${v_th} m/s</span></span>
            <span class="param-hud-item"><i class="fa-solid fa-arrows-up-down" style="color:var(--danger)"></i> ΔP Drop: <span class="param-hud-val">${(deltaP/1000).toFixed(2)} kPa</span></span>
            <span class="param-hud-item">Throat P: <span class="param-hud-val">${P_th} kPa</span></span>
            ${cavChip}
        `;
    } else if (asId === 1) {
        // Projectile Drag
        const vEl = document.getElementById('slider_1_v0');
        const cEl = document.getElementById('slider_1_c');
        if (!vEl) return;
        const v0 = parseFloat(vEl.value);
        const R_vac = ((v0 * v0) / 9.81).toFixed(1);
        hud.innerHTML = `
            <span class="param-hud-item"><i class="fa-solid fa-crosshairs" style="color:var(--accent)"></i> Vacuum Range R_vac: <span class="param-hud-val">${R_vac} m</span></span>
            <span class="param-hud-item"><i class="fa-solid fa-stopwatch" style="color:var(--success)"></i> Flight Time t: <span class="param-hud-val">${(2*v0*Math.sin(Math.PI/4)/9.81).toFixed(2)} s</span></span>
        `;
    }
}

/**
 * Run Simulation with current parameters
 */
function runParamSimulation(asId) {
    runAssignmentCode(
        `assign_code_${asId}`,
        `assign_status_${asId}`,
        `assign_console_${asId}`,
        `assign_plots_${asId}`
    );
}

/**
 * Execute Python Solver in Pyodide WebAssembly
 */
async function runAssignmentCode(codeId, statusId, consoleId, plotsId) {
    var editor = assignEditors[codeId];
    var code = editor ? editor.getValue() : document.getElementById(codeId).value;
    var status = document.getElementById(statusId);
    var consoleEl = document.getElementById(consoleId);
    var plotsEl = document.getElementById(plotsId);

    status.className = 'badge badge-amber';
    status.innerText = 'Solving ODE/PDE...';
    consoleEl.innerText = "Initializing numerical physics kernel...\n";

    try {
        await window.physicsRunner.run(code, {
            onStatus: function(msg) { status.innerText = msg; },
            consoleEl: consoleEl,
            plotsEl: plotsEl
        });
        status.className = 'badge badge-emerald';
        status.innerText = 'Completed (Converged)';
    } catch (e) {
        status.className = 'badge badge-purple';
        status.innerText = 'Numerical Error';
        consoleEl.innerText += "\n[Execution Error]: " + e.message;
    }
}

/**
 * Export to Interactive Jupyter Notebook (.ipynb) with ipywidgets Sliders
 */
function exportAssignmentJupyter(codeId, title) {
    var editor = assignEditors[codeId];
    var rawCode = editor ? editor.getValue() : document.getElementById(codeId).value;
    
    // Wrap code with ipywidgets header for instant interactive slider exploration in JupyterLab
    var jupyterCode = `# Interactive Computational Physics Notebook - Python4Physics
# Run this notebook in JupyterLab, Google Colab, or VS Code!
# Ensure packages are installed: pip install numpy scipy matplotlib ipywidgets

%matplotlib inline
import numpy as np
import matplotlib.pyplot as plt
from ipywidgets import interact, FloatSlider, IntSlider

` + rawCode;

    window.physicsRunner.exportToJupyter(title, jupyterCode, "Graduate & Undergraduate Computational Physics Assignment with Interactive Parameters");
}

/**
 * Filter Assignments by Syllabus Category
 */
function filterAssignments(cat, btn) {
    document.querySelectorAll('.btn-modern').forEach(b => {
        if (b.innerText.toLowerCase().includes('topic') || 
            b.innerText.toLowerCase().includes('central') || 
            b.innerText.toLowerCase().includes('scattering') || 
            b.innerText.toLowerCase().includes('continuum') || 
            b.innerText.toLowerCase().includes('mechanics') || 
            b.innerText.toLowerCase().includes('electrodynamics') || 
            b.innerText.toLowerCase().includes('quantum') || 
            b.innerText.toLowerCase().includes('thermo')) {
            b.classList.remove('btn-primary');
            b.classList.add('btn-secondary');
        }
    });
    btn.classList.remove('btn-secondary');
    btn.classList.add('btn-primary');

    document.querySelectorAll('.assignment-item').forEach(item => {
        if (cat === 'all' || item.getAttribute('data-category') === cat) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/include/footer.php'; ?>
