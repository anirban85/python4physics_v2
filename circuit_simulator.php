<?php
/**
 * Python4Physics - Interactive Circuit Simulator & Virtual Instrumentation Suite
 * Interactive SPICE Circuit Simulator & Virtual Electronics Lab
 * 
 * Includes:
 *  - Schematic CAD Editor with Orthogonal Routing & Grid Snapping
 *  - Real-time SPICE Numerical Solver for DC, AC & Transient Analysis
 *  - Virtual Instruments: Dual-Trace Cathode Ray Oscilloscope (CRO), Digital Multimeter (DMM),
 *    Function Generator (XFG), and In-line Probes (Voltmeter, Ammeter)
 *  - 21 Comprehensive Presets covering all 7 Modules from the University Electronics Syllabus:
 *    Module 1: DC Circuits & Network Theorems
 *    Module 2: Semiconductor Diodes, Rectifiers, Filters & Wave-Shaping
 *    Module 3: BJT Characteristics, Biasing Stabilization & Hybrid Parameters
 *    Module 4: Field Effect Transistors (JFET & MOSFET)
 *    Module 5: Regulated Power Supplies (Zener & Series-Pass Transistors)
 *    Module 6: Single-Stage Amplifiers & Frequency Response
 *    Module 7: Operational Amplifiers (IC 741) & Feedback Topologies
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';

$page_title = "Circuit Simulator - Virtual Electronics Laboratory & Schematic CAD";
$page_description = "Interactive SPICE circuit simulator: build analog circuits, wire components, connect virtual CRO oscilloscope, digital multimeter, and analyze DC/AC transients across 7 electronics modules.";
$page_keywords = "circuit simulator, schematic cad, virtual electronics lab, spice simulation, oscilloscope CRO, multimeter DMM, opamp 741, bjt amplifier, bridge rectifier, zener regulator";

require_once __DIR__ . '/include/header.php';
require_once __DIR__ . '/include/navbar.php';
?>

<!-- Circuit Simulator Stylesheet -->
<link rel="stylesheet" href="<?php echo $siteurl; ?>assets/css/multisim.css?v=<?php echo file_exists(__DIR__ . '/assets/css/multisim.css') ? filemtime(__DIR__ . '/assets/css/multisim.css') : time(); ?>">

<!-- html2canvas for High-Resolution Schematic PNG Export -->
<script src="<?php echo $siteurl; ?>assets/js/html2canvas.min.js"></script>

<!-- Schema.org WebApplication & FAQPage JSON-LD for Google Rich Snippets & Page Ranking -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "WebApplication",
      "name": "Python4Physics Circuit Simulator & Virtual Instrumentation Suite",
      "url": "https://python4physics.in/circuit_simulator.php",
      "applicationCategory": "EducationalApplication",
      "operatingSystem": "All (Modern Web Browser)",
      "browserRequirements": "Requires HTML5, Canvas and JavaScript support",
      "description": "Professional browser-based electronics CAD & SPICE simulation environment with dual-trace oscilloscope (CRO), digital multimeter (DMM), and 21 standard university electronics curriculum presets.",
      "featureList": [
        "Real-time SPICE Modified Nodal Analysis (MNA) solver for DC, AC and Transient responses",
        "Schematic CAD workbench with orthogonal net routing and grid snapping",
        "Dual-trace Cathode Ray Oscilloscope (CRO) with dual-channel timebase and Lissajous X-Y modes",
        "Digital Multimeter (DMM) measuring true RMS AC/DC voltage, current and resistance",
        "Component Value Inspector with standard E12/EIA decade pickers and potentiometer wiper sliders",
        "21 curriculum preset circuits spanning BJT, FET, Op-Amp, and Rectifier topologies"
      ],
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "USD"
      }
    },
    {
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "How does the Python4Physics Circuit Simulator work?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "The circuit simulator employs Modified Nodal Analysis (MNA) with trapezoidal numerical integration to simulate linear and nonlinear analog circuits in real time directly inside your web browser."
          }
        },
        {
          "@type": "Question",
          "name": "Can I connect virtual laboratory instruments like an oscilloscope and multimeter?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Yes. The simulator includes an authentic dual-trace Cathode Ray Oscilloscope (CRO) with dual-channel timebase, Volts/Div, and Trigger controls, as well as a 4.5-digit Digital Multimeter (DMM) for DC/AC voltage, current, and resistance measurement."
          }
        },
        {
          "@type": "Question",
          "name": "What circuit components and semiconductor models are supported?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Supported components include Resistors, Capacitors, Inductors, Potentiometers, DC/AC Voltage Sources, Current Sources, Ground, Silicon/Germanium/Schottky/Zener Diodes, LEDs, NPN/PNP Bipolar Junction Transistors (BJTs), JFETs, MOSFETs, Operational Amplifiers (IC 741), SPST Switches, and Live Voltage/Current Probes."
          }
        },
        {
          "@type": "Question",
          "name": "Is the circuit simulator free for students and university educators?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Yes, Python4Physics is completely free and open for educational use, scientific learning, and university coursework worldwide."
          }
        }
      ]
    }
  ]
}
</script>

<main class="container-fluid" style="padding-top: 1.5rem; padding-bottom: 5rem; max-width: 1520px; margin: 0 auto; box-sizing: border-box; overflow-x: hidden;">
    
    <!-- Hero Header -->
    <div style="text-align: center; max-width: 960px; margin: 0 auto 1.5rem auto;">
        <span class="badge badge-cyan" style="font-size: 0.85rem; padding: 4px 14px; margin-bottom: 0.5rem; display: inline-block;">
            <i class="fa-solid fa-wave-square"></i> Virtual Electronics Laboratory &middot; Interactive Circuit Simulator
        </span>
        <h1 style="font-size: 2.3rem; margin-top: 0.35rem; margin-bottom: 0.5rem;">
            Virtual Circuit Simulator &amp; <span class="gradient-text">Instruments Suite</span>
        </h1>
        <p style="font-size: 1.02rem; line-height: 1.5; color: var(--text-muted); margin: 0;">
            A professional browser-based electronics CAD &amp; SPICE simulation environment. 
            Wire active and passive components on the engineering grid, probe nodes with a dual-trace CRO and digital multimeter, 
            and execute real-time SPICE transient simulations across all 7 curriculum modules.
        </p>
    </div>

    <!-- ====================================================================
         CIRCUIT SIMULATOR WORKBENCH CONTAINER
         ==================================================================== -->
    <div class="ms-workbench-wrapper" id="msWorkbenchWrapper">
        
        <!-- Top Menu Bar -->
        <div class="ms-menu-bar">
            <div class="ms-menu-left">
                <span class="ms-logo">
                    <i class="fa-solid fa-bolt"></i> CIRCUIT SIMULATOR
                </span>

                <!-- File Menu -->
                <div class="ms-menu-item">
                    File
                    <div class="ms-dropdown-menu">
                        <div class="ms-dropdown-item" id="msMenuNew"><i class="fa-solid fa-file"></i> New Schematic <kbd>Ctrl+N</kbd></div>
                        <div class="ms-dropdown-item" id="msMenuExport"><i class="fa-solid fa-image"></i> Export Schematic PNG</div>
                        <div class="ms-dropdown-divider"></div>
                        <div class="ms-dropdown-item" id="msMenuClear"><i class="fa-solid fa-trash-can"></i> Clear All</div>
                    </div>
                </div>

                <!-- Edit Menu -->
                <div class="ms-menu-item">
                    Edit
                    <div class="ms-dropdown-menu">
                        <div class="ms-dropdown-item" id="msMenuProps"><i class="fa-solid fa-sliders"></i> Edit Component Value <kbd>Enter</kbd></div>
                        <div class="ms-dropdown-item" id="msMenuRotate"><i class="fa-solid fa-rotate-right"></i> Rotate 90&deg; <kbd>R</kbd></div>
                        <div class="ms-dropdown-item" id="msMenuDelete"><i class="fa-solid fa-xmark"></i> Delete Item <kbd>Del</kbd></div>
                    </div>
                </div>

                <!-- Simulate Menu -->
                <div class="ms-menu-item">
                    Simulate
                    <div class="ms-dropdown-menu">
                        <div class="ms-dropdown-item" id="msMenuRun"><i class="fa-solid fa-play"></i> Run / Stop Simulation <kbd>Space</kbd></div>
                        <div class="ms-dropdown-item" id="msMenuStep"><i class="fa-solid fa-forward-step"></i> Step Forward</div>
                    </div>
                </div>

                <!-- Instruments Menu -->
                <div class="ms-menu-item">
                    Instruments
                    <div class="ms-dropdown-menu">
                        <div class="ms-dropdown-item" id="msMenuCRO"><i class="fa-solid fa-chart-line"></i> 2-Channel Oscilloscope (CRO)</div>
                        <div class="ms-dropdown-item" id="msMenuDMM"><i class="fa-solid fa-calculator"></i> Digital Multimeter (DMM)</div>
                        <div class="ms-dropdown-item" id="msMenuXFG"><i class="fa-solid fa-wave-square"></i> Function Generator (XFG)</div>
                    </div>
                </div>

                <!-- Mobile Drawer Toggle Button -->
                <button type="button" class="ms-tool-btn" id="msToggleDrawerBtn" style="margin-left: 8px;" title="Toggle Component Palette">
                    <i class="fa-solid fa-bars-staggered"></i> <span>Components</span>
                </button>
            </div>

            <!-- Right Status Ribbon -->
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 0.72rem; color: #94a3b8; font-family: 'JetBrains Mono', monospace;">
                    MNA SPICE ENGINE 2.0 &middot; 33 FPS
                </span>
                <button type="button" class="ms-tool-btn" id="msBtnFullscreen" title="Toggle Fullscreen Mode">
                    <i class="fa-solid fa-expand"></i> <span>Fullscreen</span>
                </button>
            </div>
        </div>

        <!-- Simulation Ribbon Toolbar -->
        <div class="ms-ribbon-toolbar">
            
            <!-- Quick Actions -->
            <div class="ms-toolbar-group">
                <button type="button" class="ms-tool-btn" id="msBtnClear" title="New / Clear Schematic">
                    <i class="fa-solid fa-file-circle-plus"></i> <span>New</span>
                </button>
                <button type="button" class="ms-tool-btn" id="msBtnProps" title="Edit Component Value (Enter / Double-Click)" style="color: #38bdf8;">
                    <i class="fa-solid fa-sliders"></i> <span>Edit Value</span>
                </button>
                <button type="button" class="ms-tool-btn" id="msBtnRotate" title="Rotate Selected Component (R)">
                    <i class="fa-solid fa-rotate-right"></i> <span>Rotate</span>
                </button>
                <button type="button" class="ms-tool-btn" id="msBtnDelete" title="Delete Selected Wire / Component (Del)">
                    <i class="fa-solid fa-trash-can"></i> <span>Delete</span>
                </button>
                <button type="button" class="ms-tool-btn" id="msBtnExport" title="Export Circuit Schematic as PNG">
                    <i class="fa-solid fa-camera"></i> <span>Snapshot</span>
                </button>
            </div>

            <div class="ms-tool-divider"></div>

            <!-- Simulation Controls -->
            <div class="ms-toolbar-group">
                <button type="button" class="ms-sim-btn run" id="msBtnSimRun" title="Toggle Interactive SPICE Simulation (Space / F5)">
                    <i class="fa-solid fa-play"></i> <span>Run (F5)</span>
                </button>
                <button type="button" class="ms-tool-btn" id="msBtnSimStep" title="Single Simulation Step">
                    <i class="fa-solid fa-forward-step"></i> <span>Step</span>
                </button>
                <div class="ms-sim-clock">
                    <div class="ms-clock-led" id="msSimLed"></div>
                    <span id="msSimTimeDisplay">0.0000 s</span>
                </div>
            </div>

            <div class="ms-tool-divider"></div>

            <!-- Pre-built Curriculum Module Selector -->
            <div class="ms-toolbar-group" style="flex: 1; justify-content: flex-end;">
                <label for="msModuleSelect" style="font-size: 0.74rem; font-weight: 700; color: #94a3b8; margin-right: 4px; white-space: nowrap;">
                    <i class="fa-solid fa-diagram-project"></i> Module Preset:
                </label>
                <select id="msModuleSelect" class="ms-module-select" title="Load Pre-configured Curriculum Circuit">
                    <optgroup label="Module 1: DC Circuits &amp; Network Theorems">
                        <option value="mod1_thevenin_norton">1a. Thevenin &amp; Norton Equivalent Circuit</option>
                        <option value="mod1_max_power">1b. Maximum Power Transfer Theorem</option>
                        <option value="mod1_superposition">1c. Superposition Theorem (2 Sources)</option>
                    </optgroup>
                    <optgroup label="Module 2: Semiconductor Diodes &amp; Rectifiers">
                        <option value="mod2_bridge_rectifier" selected>2a. Full-Wave Bridge Rectifier with C-Filter (Default)</option>
                        <option value="mod2_halfwave_rectifier">2b. Half-Wave Rectifier with Filter</option>
                        <option value="mod2_pn_diode">2c. PN Junction Diode I-V Characteristics</option>
                        <option value="mod2_clipper_clamper">2d. Diode Clipping &amp; Clamping Wave-Shaping</option>
                    </optgroup>
                    <optgroup label="Module 3: BJT Transistors &amp; Biasing">
                        <option value="mod3_voltage_divider_bias">3a. BJT Voltage Divider Bias (Self-Bias)</option>
                        <option value="mod3_ce_characteristics">3b. BJT CE Input/Output Characteristics &amp; Q-Point</option>
                    </optgroup>
                    <optgroup label="Module 4: Field Effect Transistors (FET / MOSFET)">
                        <option value="mod4_jfet_characteristics">4a. JFET Transfer &amp; Drain Characteristics</option>
                        <option value="mod4_mosfet_switch">4b. N-MOSFET Electronic Switch</option>
                    </optgroup>
                    <optgroup label="Module 5: Regulated Power Supplies">
                        <option value="mod5_zener_regulator">5a. Zener Diode Voltage Regulator</option>
                        <option value="mod5_series_pass_regulator">5b. Series Pass Transistor Regulated Supply</option>
                    </optgroup>
                    <optgroup label="Module 6: Amplifiers &amp; Frequency Response">
                        <option value="mod6_ce_amplifier">6a. Single-Stage CE BJT AC Amplifier</option>
                        <option value="mod6_emitter_follower">6b. Emitter Follower (Common Collector Buffer)</option>
                    </optgroup>
                    <optgroup label="Module 7: Operational Amplifiers (IC 741)">
                        <option value="mod7_opamp_inverting">7a. IC 741 Inverting Amplifier</option>
                        <option value="mod7_opamp_noninverting">7b. IC 741 Non-Inverting Amplifier</option>
                        <option value="mod7_opamp_integrator">7c. IC 741 Operational Integrator</option>
                        <option value="mod7_opamp_comparator">7d. IC 741 Voltage Comparator</option>
                    </optgroup>
                </select>
            </div>
        </div>

        <!-- Main Workspace Body (Palette, Canvas, Instrument Rack) -->
        <div class="ms-body-layout">
            
            <!-- Left Component Drawer (Component Bin) -->
            <div class="ms-component-drawer" id="msComponentDrawer">
                <div class="ms-drawer-header">
                    <span class="ms-drawer-title">
                        <i class="fa-solid fa-microchip"></i> Components
                    </span>
                    <span style="font-size: 0.68rem; color: #64748b;">Click to Add</span>
                </div>

                <input type="text" id="msCompSearch" class="ms-search-input" placeholder="Search components (e.g. diode, opamp)...">

                <div class="ms-palette-categories">
                    
                    <!-- Sources -->
                    <div class="ms-category-group">
                        <div class="ms-category-header">
                            <span><i class="fa-solid fa-car-battery"></i> Sources</span>
                        </div>
                        <div class="ms-category-list">
                            <div class="ms-comp-card" data-type="dc_source" title="DC Voltage Source (Battery)">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-20 -30 40 60"><circle cx="0" cy="0" r="16" fill="none" stroke="#38bdf8" stroke-width="2"/><line x1="0" y1="-28" x2="0" y2="-16" stroke="#38bdf8" stroke-width="2"/><line x1="0" y1="16" x2="0" y2="28" stroke="#38bdf8" stroke-width="2"/><text x="0" y="-3" text-anchor="middle" fill="#ef4444" font-size="11" font-weight="bold">+</text><text x="0" y="9" text-anchor="middle" fill="#94a3b8" font-size="11" font-weight="bold">-</text></svg>
                                </div>
                                <span class="ms-comp-name">DC Source</span>
                            </div>
                            <div class="ms-comp-card" data-type="ac_source" title="AC Voltage Source (Sinusoidal)">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-20 -30 40 60"><circle cx="0" cy="0" r="16" fill="none" stroke="#38bdf8" stroke-width="2"/><line x1="0" y1="-28" x2="0" y2="-16" stroke="#38bdf8" stroke-width="2"/><line x1="0" y1="16" x2="0" y2="28" stroke="#38bdf8" stroke-width="2"/><path d="M -8 0 Q -4 -7 0 0 T 8 0" fill="none" stroke="#38bdf8" stroke-width="2"/></svg>
                                </div>
                                <span class="ms-comp-name">AC Source</span>
                            </div>
                            <div class="ms-comp-card" data-type="ground" title="Ground 0V Reference Node">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-15 -12 30 25"><line x1="0" y1="-10" x2="0" y2="0" stroke="#38bdf8" stroke-width="2"/><line x1="-10" y1="0" x2="10" y2="0" stroke="#38bdf8" stroke-width="2"/><line x1="-6" y1="4" x2="6" y2="4" stroke="#38bdf8" stroke-width="2"/><line x1="-2" y1="8" x2="2" y2="8" stroke="#38bdf8" stroke-width="2"/></svg>
                                </div>
                                <span class="ms-comp-name">Ground (GND)</span>
                            </div>
                            <div class="ms-comp-card" data-type="current_source" title="DC Current Source">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-20 -30 40 60"><circle cx="0" cy="0" r="16" fill="none" stroke="#38bdf8" stroke-width="2"/><line x1="0" y1="6" x2="0" y2="-6" stroke="#10b981" stroke-width="2"/><polygon points="0,-8 -3,-3 3,-3" fill="#10b981"/></svg>
                                </div>
                                <span class="ms-comp-name">Current Src</span>
                            </div>
                        </div>
                    </div>

                    <!-- Passives -->
                    <div class="ms-category-group">
                        <div class="ms-category-header">
                            <span><i class="fa-solid fa-shapes"></i> Basic / Passive</span>
                        </div>
                        <div class="ms-category-list">
                            <div class="ms-comp-card" data-type="resistor" title="Standard Linear Resistor">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-35 -15 70 30"><line x1="-30" y1="0" x2="-20" y2="0" stroke="#38bdf8" stroke-width="2"/><polyline points="-20,0 -16,-6 -10,6 -4,-6 2,6 8,-6 14,6 18,-6 20,0" fill="none" stroke="#38bdf8" stroke-width="2"/><line x1="20" y1="0" x2="30" y2="0" stroke="#38bdf8" stroke-width="2"/></svg>
                                </div>
                                <span class="ms-comp-name">Resistor</span>
                            </div>
                            <div class="ms-comp-card" data-type="potentiometer" title="Variable Potentiometer">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-35 -15 70 30"><line x1="-30" y1="-4" x2="-18" y2="-4" stroke="#38bdf8" stroke-width="2"/><polyline points="-18,-4 -14,-9 -8,1 -2,-9 4,1 10,-9 14,1 18,-4" fill="none" stroke="#38bdf8" stroke-width="2"/><line x1="18" y1="-4" x2="30" y2="-4" stroke="#38bdf8" stroke-width="2"/><line x1="0" y1="12" x2="0" y2="2" stroke="#f59e0b" stroke-width="2"/></svg>
                                </div>
                                <span class="ms-comp-name">Potentiometer</span>
                            </div>
                            <div class="ms-comp-card" data-type="capacitor" title="Capacitor">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-25 -15 50 30"><line x1="-20" y1="0" x2="-6" y2="0" stroke="#38bdf8" stroke-width="2"/><line x1="-6" y1="-10" x2="-6" y2="10" stroke="#38bdf8" stroke-width="2.5"/><line x1="6" y1="-10" x2="6" y2="10" stroke="#38bdf8" stroke-width="2.5"/><line x1="6" y1="0" x2="20" y2="0" stroke="#38bdf8" stroke-width="2"/></svg>
                                </div>
                                <span class="ms-comp-name">Capacitor</span>
                            </div>
                            <div class="ms-comp-card" data-type="inductor" title="Inductor Coil">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-30 -15 60 30"><path d="M -18 0 A 5 5 0 0 1 -8 0 A 5 5 0 0 1 2 0 A 5 5 0 0 1 12 0 A 5 5 0 0 1 18 0" fill="none" stroke="#38bdf8" stroke-width="2"/></svg>
                                </div>
                                <span class="ms-comp-name">Inductor</span>
                            </div>
                            <div class="ms-comp-card" data-type="switch_spst" title="SPST Toggle Switch">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-25 -15 50 30"><circle cx="-8" cy="0" r="2" fill="#38bdf8"/><circle cx="8" cy="0" r="2" fill="#38bdf8"/><line x1="-8" y1="0" x2="6" y2="-6" stroke="#38bdf8" stroke-width="2"/></svg>
                                </div>
                                <span class="ms-comp-name">SPST Switch</span>
                            </div>
                        </div>
                    </div>

                    <!-- Diodes -->
                    <div class="ms-category-group">
                        <div class="ms-category-header">
                            <span><i class="fa-solid fa-play"></i> Diodes</span>
                        </div>
                        <div class="ms-category-list">
                            <div class="ms-comp-card" data-type="diode" title="PN Junction Diode (1N4007)">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-25 -15 50 30"><polygon points="-8,-9 -8,9 8,0" fill="#38bdf8"/><line x1="8" y1="-9" x2="8" y2="9" stroke="#38bdf8" stroke-width="2"/><line x1="-20" y1="0" x2="-8" y2="0" stroke="#38bdf8" stroke-width="2"/><line x1="8" y1="0" x2="20" y2="0" stroke="#38bdf8" stroke-width="2"/></svg>
                                </div>
                                <span class="ms-comp-name">Diode (1N4007)</span>
                            </div>
                            <div class="ms-comp-card" data-type="zener" title="Zener Diode (1N4733A 5.1V)">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-25 -15 50 30"><polygon points="-8,-9 -8,9 8,0" fill="#38bdf8"/><polyline points="5,-12 8,-9 8,9 11,12" fill="none" stroke="#38bdf8" stroke-width="2"/><line x1="-20" y1="0" x2="-8" y2="0" stroke="#38bdf8" stroke-width="2"/><line x1="8" y1="0" x2="20" y2="0" stroke="#38bdf8" stroke-width="2"/></svg>
                                </div>
                                <span class="ms-comp-name">Zener Diode</span>
                            </div>
                            <div class="ms-comp-card" data-type="led" title="Light Emitting Diode">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-25 -15 50 30"><polygon points="-8,-8 -8,8 6,0" fill="#ef4444"/><line x1="6" y1="-8" x2="6" y2="8" stroke="#ef4444" stroke-width="2"/><line x1="-1" y1="-9" x2="5" y2="-15" stroke="#ef4444" stroke-width="1.5"/><line x1="4" y1="-9" x2="10" y2="-15" stroke="#ef4444" stroke-width="1.5"/></svg>
                                </div>
                                <span class="ms-comp-name">LED (Diode)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Transistors & FETs -->
                    <div class="ms-category-group">
                        <div class="ms-category-header">
                            <span><i class="fa-solid fa-code-branch"></i> Transistors &amp; FETs</span>
                        </div>
                        <div class="ms-category-list">
                            <div class="ms-comp-card" data-type="bjt_npn" title="BJT NPN Transistor (BC547 / 2N3904)">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-20 -25 40 50"><line x1="-15" y1="0" x2="-4" y2="0" stroke="#38bdf8" stroke-width="2"/><line x1="-4" y1="-12" x2="-4" y2="12" stroke="#38bdf8" stroke-width="3"/><line x1="-4" y1="-6" x2="12" y2="-18" stroke="#38bdf8" stroke-width="2"/><line x1="-4" y1="6" x2="12" y2="18" stroke="#38bdf8" stroke-width="2"/><polygon points="12,18 6,13 10,9" fill="#38bdf8"/></svg>
                                </div>
                                <span class="ms-comp-name">BJT NPN</span>
                            </div>
                            <div class="ms-comp-card" data-type="bjt_pnp" title="BJT PNP Transistor (BC557 / 2N3906)">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-20 -25 40 50"><line x1="-15" y1="0" x2="-4" y2="0" stroke="#38bdf8" stroke-width="2"/><line x1="-4" y1="-12" x2="-4" y2="12" stroke="#38bdf8" stroke-width="3"/><line x1="-4" y1="-6" x2="12" y2="-18" stroke="#38bdf8" stroke-width="2"/><polygon points="-2,-4 3,-8 1,-2" fill="#38bdf8"/><line x1="-4" y1="6" x2="12" y2="18" stroke="#38bdf8" stroke-width="2"/></svg>
                                </div>
                                <span class="ms-comp-name">BJT PNP</span>
                            </div>
                            <div class="ms-comp-card" data-type="jfet_n" title="JFET N-Channel (2N5458)">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-20 -25 40 50"><line x1="-15" y1="10" x2="-4" y2="10" stroke="#38bdf8" stroke-width="2"/><line x1="-4" y1="-12" x2="-4" y2="12" stroke="#38bdf8" stroke-width="3"/><line x1="0" y1="-8" x2="12" y2="-8" stroke="#38bdf8" stroke-width="2"/><line x1="0" y1="8" x2="12" y2="8" stroke="#38bdf8" stroke-width="2"/><polygon points="-4,10 -8,8 -8,12" fill="#38bdf8"/></svg>
                                </div>
                                <span class="ms-comp-name">JFET (N-Ch)</span>
                            </div>
                            <div class="ms-comp-card" data-type="mosfet_n" title="MOSFET N-Channel (IRF510)">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-20 -25 40 50"><line x1="-15" y1="10" x2="-6" y2="10" stroke="#38bdf8" stroke-width="2"/><line x1="-6" y1="-12" x2="-6" y2="12" stroke="#38bdf8" stroke-width="2.5"/><line x1="0" y1="-10" x2="12" y2="-10" stroke="#38bdf8" stroke-width="2"/><line x1="0" y1="10" x2="12" y2="10" stroke="#38bdf8" stroke-width="2"/></svg>
                                </div>
                                <span class="ms-comp-name">MOSFET (N-Ch)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Op-Amps -->
                    <div class="ms-category-group">
                        <div class="ms-category-header">
                            <span><i class="fa-solid fa-square-root-variable"></i> Analog / Op-Amps</span>
                        </div>
                        <div class="ms-category-list">
                            <div class="ms-comp-card" data-type="opamp" title="Operational Amplifier IC 741">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-25 -20 50 40"><polygon points="-16,-16 -16,16 16,0" fill="none" stroke="#38bdf8" stroke-width="2"/><text x="-12" y="-5" fill="#94a3b8" font-size="8">-</text><text x="-12" y="11" fill="#94a3b8" font-size="8">+</text></svg>
                                </div>
                                <span class="ms-comp-name">Op-Amp (741)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Meters & Probes -->
                    <div class="ms-category-group">
                        <div class="ms-category-header">
                            <span><i class="fa-solid fa-gauge"></i> In-Circuit Probes</span>
                        </div>
                        <div class="ms-category-list">
                            <div class="ms-comp-card" data-type="voltmeter" title="Parallel Voltmeter Probe">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-20 -20 40 40"><circle cx="0" cy="0" r="14" fill="#032b1a" stroke="#22c55e" stroke-width="2"/><text x="0" y="4" text-anchor="middle" fill="#4ade80" font-size="11" font-weight="bold">V</text></svg>
                                </div>
                                <span class="ms-comp-name">Voltmeter</span>
                            </div>
                            <div class="ms-comp-card" data-type="ammeter" title="In-Line Series Ammeter">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-20 -20 40 40"><circle cx="0" cy="0" r="14" fill="#1e1b4b" stroke="#818cf8" stroke-width="2"/><text x="0" y="4" text-anchor="middle" fill="#c7d2fe" font-size="11" font-weight="bold">A</text></svg>
                                </div>
                                <span class="ms-comp-name">Ammeter</span>
                            </div>
                            <div class="ms-comp-card" data-type="cro_tap" title="Dual-Trace CRO Probe Connector">
                                <div class="ms-comp-icon-preview">
                                    <svg viewBox="-20 -20 40 40"><rect x="-14" y="-14" width="28" height="28" rx="3" fill="#090d16" stroke="#f59e0b" stroke-width="2"/><text x="0" y="4" text-anchor="middle" fill="#fbbf24" font-size="9" font-weight="bold">CRO</text></svg>
                                </div>
                                <span class="ms-comp-name">CRO Probes</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Center Schematic CAD Canvas Container -->
            <div class="ms-canvas-container" id="msCanvasContainer">
                
                <!-- Dynamic Schematic SVG Layer -->
                <svg class="ms-schematic-svg" id="msSchematicSvg"></svg>

                <!-- On-Canvas Floating Quick-Action Bar for Selected Component -->
                <div class="ms-comp-quick-actions" id="msCompQuickActions" style="display: none;">
                    <button type="button" class="ms-quick-btn edit" id="msQuickBtnEdit" title="Edit Component Value (Enter)">
                        <i class="fa-solid fa-sliders"></i> <span>Edit Value</span>
                    </button>
                    <button type="button" class="ms-quick-btn rotate" id="msQuickBtnRot" title="Rotate Component 90° (R)">
                        <i class="fa-solid fa-rotate-right"></i> <span>Rotate</span>
                    </button>
                    <button type="button" class="ms-quick-btn delete" id="msQuickBtnDel" title="Delete Component (Del)">
                        <i class="fa-solid fa-trash-can"></i> <span>Delete</span>
                    </button>
                </div>

                <!-- Floating Canvas Toast Notification -->
                <div class="ms-canvas-toast" id="msCanvasToast" style="display: none;"></div>

                <!-- On-Screen Guidance Floating Badge -->
                <div style="position: absolute; bottom: 12px; left: 16px; background: rgba(15, 23, 42, 0.88); border: 1px solid #334155; border-radius: 6px; padding: 6px 12px; font-size: 0.74rem; color: #94a3b8; pointer-events: none; z-index: 5;">
                    <i class="fa-solid fa-lightbulb" style="color: #f59e0b;"></i> 
                    <strong>Quick Controls:</strong> Click red pins to wire &middot; Drag components &middot; Press <kbd style="background: rgba(0,0,0,0.4); padding: 1px 4px; border-radius: 3px; color:#cbd5e1;">R</kbd> to rotate &middot; Press <kbd style="background: rgba(0,0,0,0.4); padding: 1px 4px; border-radius: 3px; color:#cbd5e1;">Del</kbd> to remove &middot; Double-click, tap or press <kbd style="background: rgba(0,0,0,0.4); padding: 1px 4px; border-radius: 3px; color:#38bdf8;">Enter</kbd> to edit value
                </div>
            </div>

            <!-- Right Instruments Toolbar -->
            <div class="ms-instruments-bar" id="msInstrumentsBar">
                <button type="button" class="ms-inst-btn" id="msInstCRO" title="Dual-Trace Oscilloscope (CRO)">
                    <i class="fa-solid fa-chart-line"></i>
                    <span class="ms-inst-label">CRO</span>
                </button>
                <button type="button" class="ms-inst-btn" id="msInstDMM" title="Digital Multimeter (DMM)">
                    <i class="fa-solid fa-calculator"></i>
                    <span class="ms-inst-label">DMM</span>
                </button>
                <button type="button" class="ms-inst-btn" id="msInstXFG" title="Function Generator (XFG)">
                    <i class="fa-solid fa-wave-square"></i>
                    <span class="ms-inst-label">XFG</span>
                </button>
            </div>

        </div>

        <!-- ====================================================================
             FLOATING VIRTUAL INSTRUMENT PANELS
             ==================================================================== -->

        <!-- 1. Dual-Channel Cathode Ray Oscilloscope (CRO) -->
        <div class="ms-floating-window ms-cro-window" id="msCroWindow">
            <div class="ms-window-header">
                <span class="ms-window-title">
                    <i class="fa-solid fa-chart-line" style="color: #fbbf24;"></i>
                    Tektronix &middot; Dual-Trace Cathode Ray Oscilloscope (CRO)
                </span>
                <button type="button" class="ms-window-close-btn" id="msCroCloseBtn">&times;</button>
            </div>
            <div class="ms-cro-body">
                <!-- Phosphor Screen Display -->
                <div class="ms-cro-screen-container">
                    <canvas class="ms-cro-canvas" id="msCroCanvas" width="380" height="300"></canvas>
                </div>

                <!-- Control Console -->
                <div class="ms-cro-controls">
                    <!-- Autoset Button -->
                    <button type="button" class="ms-cro-autoset-btn" id="msCroAutosetBtn" title="Auto-calibrate Timebase, Volts/Div, and Y-Offsets for both channels">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> AUTOSET (Dual Channels)
                    </button>

                    <!-- Timebase -->
                    <div class="ms-cro-section">
                        <div class="ms-cro-sec-title">Timebase (Horizontal)</div>
                        <div class="ms-cro-knob-row">
                            <span class="ms-cro-label">Time / Div:</span>
                            <select id="msCroTimeDiv" class="ms-cro-select">
                                <option value="0.00005">50 µs</option>
                                <option value="0.0001">100 µs</option>
                                <option value="0.0005">500 µs</option>
                                <option value="0.001">1 ms</option>
                                <option value="0.002">2 ms</option>
                                <option value="0.005" selected>5 ms</option>
                                <option value="0.01">10 ms</option>
                                <option value="0.02">20 ms</option>
                                <option value="0.05">50 ms</option>
                            </select>
                        </div>
                    </div>

                    <!-- Channel A (Yellow) -->
                    <div class="ms-cro-section">
                        <div class="ms-cro-sec-title" style="color: #fbbf24;">Channel A (Input)</div>
                        <div class="ms-cro-knob-row">
                            <span class="ms-cro-label">Volts / Div:</span>
                            <select id="msCroVoltsA" class="ms-cro-select">
                                <option value="0.1">100 mV</option>
                                <option value="0.2">200 mV</option>
                                <option value="0.5">500 mV</option>
                                <option value="1">1 V</option>
                                <option value="2">2 V</option>
                                <option value="5" selected>5 V</option>
                                <option value="10">10 V</option>
                                <option value="20">20 V</option>
                            </select>
                        </div>
                        <div class="ms-cro-knob-row">
                            <span class="ms-cro-label">Y-Pos:</span>
                            <div class="ms-cro-pos-stepper">
                                <button type="button" class="ms-cro-pos-btn" id="msCroPosADown" title="Shift Down">-</button>
                                <span class="ms-cro-pos-val" id="msCroPosADisp">+1.0 div</span>
                                <button type="button" class="ms-cro-pos-btn" id="msCroPosAUp" title="Shift Up">+</button>
                            </div>
                        </div>
                        <button type="button" class="ms-tool-btn active" id="msCroToggleA" style="width: 100%; justify-content: center; margin-top: 4px;">
                            <i class="fa-solid fa-eye"></i> Channel A Active
                        </button>
                    </div>

                    <!-- Channel B (Cyan) -->
                    <div class="ms-cro-section">
                        <div class="ms-cro-sec-title" style="color: #38bdf8;">Channel B (Output)</div>
                        <div class="ms-cro-knob-row">
                            <span class="ms-cro-label">Volts / Div:</span>
                            <select id="msCroVoltsB" class="ms-cro-select">
                                <option value="0.1">100 mV</option>
                                <option value="0.2">200 mV</option>
                                <option value="0.5">500 mV</option>
                                <option value="1">1 V</option>
                                <option value="2">2 V</option>
                                <option value="5" selected>5 V</option>
                                <option value="10">10 V</option>
                                <option value="20">20 V</option>
                            </select>
                        </div>
                        <div class="ms-cro-knob-row">
                            <span class="ms-cro-label">Y-Pos:</span>
                            <div class="ms-cro-pos-stepper">
                                <button type="button" class="ms-cro-pos-btn" id="msCroPosBDown" title="Shift Down">-</button>
                                <span class="ms-cro-pos-val" id="msCroPosBDisp">-1.5 div</span>
                                <button type="button" class="ms-cro-pos-btn" id="msCroPosBUp" title="Shift Up">+</button>
                            </div>
                        </div>
                        <button type="button" class="ms-tool-btn active" id="msCroToggleB" style="width: 100%; justify-content: center; margin-top: 4px;">
                            <i class="fa-solid fa-eye"></i> Channel B Active
                        </button>
                    </div>

                    <!-- Mode Selector -->
                    <button type="button" class="ms-tool-btn" id="msCroModeBtn" style="justify-content: center;">
                        Mode: Y-T (Dual Sweep)
                    </button>

                    <!-- Waveform Telemetry -->
                    <div class="ms-cro-telemetry">
                        <div>Ch A Vp-p: <span id="msCroVppA" style="color: #fbbf24;">--</span></div>
                        <div>Ch A Freq: <span id="msCroFreqA" style="color: #fbbf24;">--</span></div>
                        <div>Ch B Vp-p: <span id="msCroVppB" style="color: #38bdf8;">--</span></div>
                        <div>Ch B Freq: <span id="msCroFreqB" style="color: #38bdf8;">--</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Digital Multimeter (DMM) -->
        <div class="ms-floating-window ms-dmm-window" id="msDmmWindow">
            <div class="ms-window-header">
                <span class="ms-window-title">
                    <i class="fa-solid fa-calculator" style="color: #4ade80;"></i>
                    Agilent 34401A &middot; Precision Digital Multimeter (DMM)
                </span>
                <button type="button" class="ms-window-close-btn" id="msDmmCloseBtn">&times;</button>
            </div>
            <div class="ms-dmm-body">
                <!-- 4.5 Digit LCD Screen -->
                <div class="ms-dmm-display">
                    <span class="ms-dmm-value" id="msDmmValueDisplay">0.000</span>
                    <span class="ms-dmm-unit" id="msDmmUnitDisplay">V DC</span>
                </div>

                <!-- Measurement Mode Selectors -->
                <div class="ms-dmm-modes">
                    <button type="button" class="ms-dmm-mode-btn active" data-mode="V_DC">V (DC)</button>
                    <button type="button" class="ms-dmm-mode-btn" data-mode="V_AC">V (AC)</button>
                    <button type="button" class="ms-dmm-mode-btn" data-mode="I_DC">I (DC)</button>
                    <button type="button" class="ms-dmm-mode-btn" data-mode="I_AC">I (AC)</button>
                    <button type="button" class="ms-dmm-mode-btn" data-mode="OHM">&Omega;</button>
                </div>
            </div>
        </div>

        <!-- 3. Function Generator (XFG) -->
        <div class="ms-floating-window ms-xfg-window" id="msXfgWindow">
            <div class="ms-window-header">
                <span class="ms-window-title">
                    <i class="fa-solid fa-wave-square" style="color: #0284c7;"></i>
                    Agilent &middot; Function Generator (XFG)
                </span>
                <button type="button" class="ms-window-close-btn" id="msXfgCloseBtn">&times;</button>
            </div>
            <div class="ms-xfg-body">
                <div class="ms-xfg-row">
                    <span style="font-size: 0.78rem; color: #cbd5e1; font-weight: 600;">Frequency:</span>
                    <span id="msXfgFreqDisplay" style="font-family: 'JetBrains Mono', monospace; color: #38bdf8;">1000 Hz</span>
                </div>
                <input type="range" class="ms-xfg-slider" id="msXfgFreqSlider" min="10" max="20000" step="10" value="1000" style="width: 100%; margin-bottom: 12px;">

                <div class="ms-xfg-row">
                    <span style="font-size: 0.78rem; color: #cbd5e1; font-weight: 600;">Amplitude (Peak):</span>
                    <span id="msXfgAmpDisplay" style="font-family: 'JetBrains Mono', monospace; color: #38bdf8;">5.0 V</span>
                </div>
                <input type="range" class="ms-xfg-slider" id="msXfgAmpSlider" min="0.1" max="20" step="0.1" value="5" style="width: 100%;">
            </div>
        </div>

        <!-- 4. Component Properties Inspector Modal -->
        <div class="ms-inspector-modal" id="msInspectorModal">
            <div class="ms-window-header">
                <span class="ms-window-title" id="msInspectorTitle">
                    <i class="fa-solid fa-sliders"></i> Component Properties
                </span>
                <button type="button" class="ms-window-close-btn" id="msInspCancelBtn">&times;</button>
            </div>
            <div class="ms-inspector-body" id="msInspectorFields">
                <!-- Dynamically filled by openPropertyModal -->
            </div>
            <div class="ms-inspector-footer">
                <button type="button" class="ms-tool-btn" id="msInspResetBtn" title="Reset this component to default values">
                    <i class="fa-solid fa-arrow-rotate-left"></i> <span>Default</span>
                </button>
                <div style="flex: 1;"></div>
                <button type="button" class="ms-tool-btn" id="msInspCloseBtn" onclick="document.getElementById('msInspectorModal').classList.remove('active');">Cancel</button>
                <button type="button" class="ms-tool-btn active" id="msInspSaveBtn"><i class="fa-solid fa-check"></i> <span>Apply Changes</span></button>
            </div>
        </div>

    </div>

    <!-- ====================================================================
         BOTTOM SYLLABUS CURRICULUM MODULES (Modules 1 - 7 from Uploaded Image)
         ==================================================================== -->
    <div class="ms-curriculum-container">
        
        <div style="border-bottom: 2px solid var(--accent); padding-bottom: 0.6rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <h2 style="font-size: 1.6rem; margin: 0;">
                    <i class="fa-solid fa-graduation-cap" style="color: var(--accent);"></i> 
                    Electronics Curriculum &amp; Experimental Physics Modules
                </h2>
                <p style="margin: 0; color: var(--text-muted); font-size: 0.92rem;">
                    Launch pre-built circuits into the simulator with one click to observe and test the theoretical principles.
                </p>
            </div>
            <span class="badge badge-cyan" style="font-size: 0.8rem;">7 Core Modules &middot; 21 Interactive Circuits</span>
        </div>

        <div class="ms-curriculum-grid">
            
            <!-- Module 1: Circuits and Network (DC) -->
            <article class="ms-module-card">
                <span class="ms-module-badge" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">Module 1 &middot; DC Circuits</span>
                <h3 style="font-size: 1.15rem; margin-top: 0.4rem; margin-bottom: 0.4rem;">Circuits and Network (DC) (4)</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.45; margin-bottom: 0.75rem;">
                    Discrete active &amp; passive components, ideal constant voltage/current sources, Kirchhoff's laws (KCL, KVL), Thevenin's and Norton's theorem, Superposition theorem, and Maximum power transfer theorem ($R_L = R_{th}$).
                </p>
                <div class="katex-display" style="font-size: 0.86rem; margin: 0.4rem 0;">
                    $$ V_{th} = V_{oc}, \quad R_{th} = \frac{V_{oc}}{I_{sc}}, \quad P_{max} = \frac{V_{th}^2}{4 R_{th}} $$
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px;">
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod1_thevenin_norton">
                        <i class="fa-solid fa-play"></i> Thevenin Equivalent
                    </button>
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod1_max_power" style="background: #0284c7;">
                        <i class="fa-solid fa-bolt"></i> Max Power Transfer
                    </button>
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod1_superposition" style="background: #334155;">
                        <i class="fa-solid fa-layer-group"></i> Superposition
                    </button>
                </div>
            </article>

            <!-- Module 2: Semiconductor Diodes and Applications -->
            <article class="ms-module-card">
                <span class="ms-module-badge" style="background: rgba(34, 197, 94, 0.15); color: #22c55e;">Module 2 &middot; Semiconductor Diodes</span>
                <h3 style="font-size: 1.15rem; margin-top: 0.4rem; margin-bottom: 0.4rem;">Semiconductor Diodes &amp; Applications (9)</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.45; margin-bottom: 0.75rem;">
                    PN junction fabrication, barrier potential, static/dynamic resistance, half-wave &amp; full-wave bridge rectifiers, Ripple Factor ($\gamma$), Rectification efficiency ($\eta$), L &amp; C smoothing filters, clipping &amp; clamping circuits, LEDs.
                </p>
                <div class="katex-display" style="font-size: 0.86rem; margin: 0.4rem 0;">
                    $$ \gamma = \frac{1}{4\sqrt{3} f C R_L}, \quad \eta_{bridge} = 81.2\%, \quad I = I_s\left(e^{\frac{qV}{\eta k_B T}} - 1\right) $$
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px;">
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod2_bridge_rectifier">
                        <i class="fa-solid fa-play"></i> Bridge Rectifier + Filter
                    </button>
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod2_halfwave_rectifier" style="background: #0284c7;">
                        <i class="fa-solid fa-wave-square"></i> Half-Wave Rectifier
                    </button>
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod2_clipper_clamper" style="background: #334155;">
                        <i class="fa-solid fa-scissors"></i> Clipper &amp; Clamper
                    </button>
                </div>
            </article>

            <!-- Module 3: Bipolar Junction Transistors and Biasing -->
            <article class="ms-module-card">
                <span class="ms-module-badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">Module 3 &middot; BJT Biasing</span>
                <h3 style="font-size: 1.15rem; margin-top: 0.4rem; margin-bottom: 0.4rem;">BJT Transistors &amp; Biasing (10)</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.45; margin-bottom: 0.75rem;">
                    NPN &amp; PNP configurations (CB, CE, CC), active, cut-off and saturation regions, DC load line &amp; Q-point, stability factors, fixed bias vs voltage divider bias (self-bias), 2-port hybrid $h$-parameter equivalent circuit.
                </p>
                <div class="katex-display" style="font-size: 0.86rem; margin: 0.4rem 0;">
                    $$ I_C = \beta I_B + I_{CEO}, \quad V_{CE} = V_{CC} - I_C R_C, \quad S = \frac{1 + \beta}{1 + \beta \frac{R_E}{R_B + R_E}} $$
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px;">
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod3_voltage_divider_bias">
                        <i class="fa-solid fa-play"></i> Voltage Divider Bias
                    </button>
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod3_ce_characteristics" style="background: #0284c7;">
                        <i class="fa-solid fa-chart-line"></i> CE Characteristics &amp; Q-Point
                    </button>
                </div>
            </article>

            <!-- Module 4: Field Effect Transistors (FET / MOSFET) -->
            <article class="ms-module-card">
                <span class="ms-module-badge" style="background: rgba(168, 85, 247, 0.15); color: #c084fc;">Module 4 &middot; FET &amp; MOSFET</span>
                <h3 style="font-size: 1.15rem; margin-top: 0.4rem; margin-bottom: 0.4rem;">Field Effect Transistors (3)</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.45; margin-bottom: 0.75rem;">
                    JFET and MOSFET (both depletion and enhancement mode MISFET), pinch-off voltage ($V_p$), drain saturation current ($I_{DSS}$), threshold voltage ($V_{th}$), transconductance $g_m$, and short channel switching characteristics.
                </p>
                <div class="katex-display" style="font-size: 0.86rem; margin: 0.4rem 0;">
                    $$ I_D = I_{DSS}\left(1 - \frac{V_{GS}}{V_P}\right)^2, \quad g_m = \frac{2 I_{DSS}}{|V_P|}\left(1 - \frac{V_{GS}}{V_P}\right) $$
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px;">
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod4_jfet_characteristics">
                        <i class="fa-solid fa-play"></i> JFET Drain &amp; Transfer
                    </button>
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod4_mosfet_switch" style="background: #0284c7;">
                        <i class="fa-solid fa-toggle-on"></i> MOSFET Electronic Switch
                    </button>
                </div>
            </article>

            <!-- Module 5: Regulated Power Supplies -->
            <article class="ms-module-card">
                <span class="ms-module-badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">Module 5 &middot; Voltage Regulation</span>
                <h3 style="font-size: 1.15rem; margin-top: 0.4rem; margin-bottom: 0.4rem;">Regulated Power Supply (3)</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.45; margin-bottom: 0.75rem;">
                    Load regulation and line regulation, Zener diode as shunt regulator, limitations of Zener circuits, error feedback amplifiers, and series regulated power supply using a pass transistor assisted by a Zener voltage reference.
                </p>
                <div class="katex-display" style="font-size: 0.86rem; margin: 0.4rem 0;">
                    $$ \text{Line Reg} = \frac{\Delta V_{out}}{\Delta V_{in}}, \quad \text{Load Reg} = \frac{V_{NL} - V_{FL}}{V_{FL}} \times 100\% $$
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px;">
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod5_zener_regulator">
                        <i class="fa-solid fa-play"></i> Zener Voltage Regulator
                    </button>
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod5_series_pass_regulator" style="background: #0284c7;">
                        <i class="fa-solid fa-shield-halved"></i> Series Pass Regulator
                    </button>
                </div>
            </article>

            <!-- Module 6: Amplifiers & Frequency Response -->
            <article class="ms-module-card">
                <span class="ms-module-badge" style="background: rgba(14, 165, 233, 0.15); color: #38bdf8;">Module 6 &middot; Amplifiers</span>
                <h3 style="font-size: 1.15rem; margin-top: 0.4rem; margin-bottom: 0.4rem;">Amplifiers &amp; Frequency Response (5)</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.45; margin-bottom: 0.75rem;">
                    Common Emitter (CE), Common Base (CB) and Emitter Follower buffer circuits, dynamic load line analysis, Class A, B and C amplifier classification, and midband frequency response with low and high cut-off frequencies ($f_L, f_H$).
                </p>
                <div class="katex-display" style="font-size: 0.86rem; margin: 0.4rem 0;">
                    $$ A_v = -\frac{h_{fe} R_L'}{h_{ie}}, \quad f_L = \frac{1}{2\pi (R_{in} + R_s) C_1}, \quad BW = f_H - f_L $$
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px;">
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod6_ce_amplifier">
                        <i class="fa-solid fa-play"></i> Single-Stage CE Amplifier
                    </button>
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod6_emitter_follower" style="background: #0284c7;">
                        <i class="fa-solid fa-arrows-split-up-and-left"></i> Emitter Follower Buffer
                    </button>
                </div>
            </article>

            <!-- Module 7: Feedback Amplifiers and OPAMP -->
            <article class="ms-module-card">
                <span class="ms-module-badge" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">Module 7 &middot; OP-AMP &amp; Feedback</span>
                <h3 style="font-size: 1.15rem; margin-top: 0.4rem; margin-bottom: 0.4rem;">Feedback Amplifiers &amp; OPAMP (8)</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.45; margin-bottom: 0.75rem;">
                    Positive and negative feedback topologies (Voltage series, current series, voltage shunt, current shunt), Gain stabilization, IC 741 characteristics, inverting, non-inverting, summing adder, integrator, differentiator, and comparator.
                </p>
                <div class="katex-display" style="font-size: 0.86rem; margin: 0.4rem 0;">
                    $$ A_f = \frac{A}{1 + A\beta}, \quad V_{out(inv)} = -\frac{R_f}{R_1} V_{in}, \quad V_{out(int)} = -\frac{1}{R C}\int V_{in} dt $$
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px;">
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod7_opamp_inverting">
                        <i class="fa-solid fa-play"></i> Inverting Amplifier
                    </button>
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod7_opamp_noninverting" style="background: #0284c7;">
                        <i class="fa-solid fa-plus-minus"></i> Non-Inverting Amplifier
                    </button>
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod7_opamp_integrator" style="background: #334155;">
                        <i class="fa-solid fa-chart-area"></i> Op-Amp Integrator
                    </button>
                    <button type="button" class="ms-module-btn tc-launch-btn" data-preset="mod7_opamp_comparator" style="background: #475569;">
                        <i class="fa-solid fa-scale-balanced"></i> Voltage Comparator
                    </button>
                </div>
            </article>

        </div>
    </div>

</main>

<!-- Circuit CAD & Numerical Simulation Engine -->
<script src="<?php echo $siteurl; ?>assets/js/multisim_engine.js?v=<?php echo file_exists(__DIR__ . '/assets/js/multisim_engine.js') ? filemtime(__DIR__ . '/assets/js/multisim_engine.js') : time(); ?>"></script>

<script>
// Hook bottom syllabus module cards to load circuits and scroll to workbench
document.addEventListener('DOMContentLoaded', function() {
    const launchBtns = document.querySelectorAll('.tc-launch-btn');
    launchBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const preset = this.getAttribute('data-preset');
            if (preset && window.MultisimApp) {
                window.MultisimApp.loadPreset(preset);
                
                // Scroll smoothly to workbench
                const workbench = document.getElementById('msWorkbenchWrapper');
                if (workbench) {
                    workbench.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });

    // Top menu bar item click handlers
    const menuNew = document.getElementById('msMenuNew');
    if (menuNew) menuNew.onclick = () => window.MultisimApp && window.MultisimApp.clearAll();

    const menuExport = document.getElementById('msMenuExport');
    if (menuExport) menuExport.onclick = () => window.MultisimApp && window.MultisimApp.exportSchematicPNG();

    const menuClear = document.getElementById('msMenuClear');
    if (menuClear) menuClear.onclick = () => window.MultisimApp && window.MultisimApp.clearAll();

    const menuRotate = document.getElementById('msMenuRotate');
    if (menuRotate) menuRotate.onclick = () => window.MultisimApp && window.MultisimApp.rotateSelected();

    const menuProps = document.getElementById('msMenuProps');
    if (menuProps) menuProps.onclick = () => {
        if (window.MultisimApp) {
            if (window.MultisimApp.selectedItem && window.MultisimApp.selectedItem.id) {
                window.MultisimApp.openPropertyModal(window.MultisimApp.selectedItem);
            } else {
                window.MultisimApp.showToast('Please click or tap a component on the schematic to edit its value.');
            }
        }
    };

    const menuDelete = document.getElementById('msMenuDelete');
    if (menuDelete) menuDelete.onclick = () => window.MultisimApp && window.MultisimApp.deleteSelected();

    const menuRun = document.getElementById('msMenuRun');
    if (menuRun) menuRun.onclick = () => window.MultisimApp && window.MultisimApp.toggleSimulation();

    const menuStep = document.getElementById('msMenuStep');
    if (menuStep) menuStep.onclick = () => window.MultisimApp && window.MultisimApp.stepSimulation();

    const menuCRO = document.getElementById('msMenuCRO');
    if (menuCRO) menuCRO.onclick = () => window.MultisimApp && window.MultisimApp.cro && window.MultisimApp.cro.toggle();

    const menuDMM = document.getElementById('msMenuDMM');
    if (menuDMM) menuDMM.onclick = () => window.MultisimApp && window.MultisimApp.dmm && window.MultisimApp.dmm.toggle();

    const menuXFG = document.getElementById('msMenuXFG');
    if (menuXFG) menuXFG.onclick = () => window.MultisimApp && window.MultisimApp.xfg && window.MultisimApp.xfg.toggle();
});
</script>

<?php require_once __DIR__ . '/include/footer.php'; ?>
