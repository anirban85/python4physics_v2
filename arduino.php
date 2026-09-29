<?php
/**
 * Python4Physics - Authentic Tinkercad Circuits Clone
 * Interactive Electronics Workbench: Photorealistic Breadboard & Arduino Uno R3,
 * Component Palette Picker, Multi-Point Orthogonal Wiring, Live Simulation & Firmware.
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';

$page_title = "Arduino in the Physics Laboratory - Tinkercad Circuits Virtual Simulator";
$page_description = "Autodesk Tinkercad Circuits style virtual Arduino laboratory: pick components, wire breadboards with professional orthogonal routing, execute live C++ firmware, and plot real-time telemetry.";

require_once __DIR__ . '/include/header.php';
require_once __DIR__ . '/include/navbar.php';
?>

<!-- Tinkercad Studio CSS -->
<link rel="stylesheet" href="<?php echo $siteurl; ?>assets/css/tinkercad.css?v=<?php echo file_exists(__DIR__ . '/assets/css/tinkercad.css') ? filemtime(__DIR__ . '/assets/css/tinkercad.css') : time(); ?>">
<!-- CodeMirror for Arduino C++ -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/material-ocean.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/clike/clike.min.js"></script>

<main class="container-fluid" style="padding-top: 1.5rem; padding-bottom: 5rem; max-width: 1480px; margin: 0 auto; box-sizing: border-box; overflow-x: hidden;">
    
    <!-- Hero Header -->
    <div style="text-align: center; max-width: 900px; margin: 0 auto 1.75rem auto;">
        <span class="badge badge-cyan" style="font-size: 0.85rem; padding: 4px 12px; margin-bottom: 0.5rem; display: inline-block;">
            <i class="fa-solid fa-microchip"></i> Autodesk Tinkercad Circuits Style Simulator
        </span>
        <h1 style="font-size: 2.4rem; margin-top: 0.4rem; margin-bottom: 0.6rem;">
            Virtual Arduino <span class="gradient-text">Circuits Workbench</span>
        </h1>
        <p style="font-size: 1.05rem; line-height: 1.5; color: var(--text-muted); margin: 0;">
            Pick components from the right-hand drawer, mount them onto the solderless breadboard, draw professional multi-point orthogonal wires, and run real-time C++ firmware simulations with live telemetry.
        </p>
    </div>

    <!-- Tinkercad Studio Workbench -->
    <div class="tinkercad-studio" id="tcStudio">
        
        <!-- Top App Navbar (Tinkercad Header) -->
        <div class="tc-top-navbar">
            <div class="tc-project-info">
                <div class="tc-logo-badge">
                    <i class="fa-solid fa-bolt"></i> CIRCUITS
                </div>
                <input type="text" id="tcProjectTitle" class="tc-title-input" value="Arduino & Solderless Breadboard Physics Lab" title="Click to rename project">
            </div>

            <!-- View Modes: Circuits | Schematic | Components -->
            <div class="tc-view-modes">
                <button type="button" class="tc-view-tab active" id="tcTabCircuits" title="Interactive Breadboard Circuit View">
                    <i class="fa-solid fa-diagram-project"></i> Circuits
                </button>
                <button type="button" class="tc-view-tab" id="tcTabSchematic" title="Schematic View">
                    <i class="fa-solid fa-bezier-curve"></i> Schematic
                </button>
                <button type="button" class="tc-view-tab" id="tcTabBom" title="Bill of Materials">
                    <i class="fa-solid fa-list-check"></i> Components
                </button>
            </div>

            <!-- Preset Lab Quick Selector -->
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label style="font-size: 0.8rem; font-weight: 700; color: var(--tc-text-muted);">LAB PRESET:</label>
                <select id="tcPresetSelector" style="font-size: 0.82rem; padding: 0.35rem 0.75rem; background: var(--tc-toolbar-bg); color: var(--tc-text-main); border: 1px solid var(--tc-toolbar-border); border-radius: 6px; font-weight: 600;">
                    <option value="blink">1. LED Blink & Optical Timing</option>
                    <option value="pwm_fade">2. PWM Breathing & Effective DC Voltage</option>
                    <option value="potentiometer">3. Potentiometer 10-Bit ADC Voltage Divider</option>
                    <option value="ldr_sensor">4. Photoresistor (LDR) Solar Light Sensor</option>
                    <option value="ultrasonic">5. Ultrasonic HC-SR04 Speed of Sound Range</option>
                    <option value="pir_alarm">6. PIR Motion Detector & Security Alarm</option>
                    <option value="tmp36_temp">7. TMP36 Precision Thermometer & Heat Monitor</option>
                    <option value="servo_sweep">8. Micro Servo Motor 180° Angle Sweeper</option>
                    <option value="rgb_mixer">9. RGB LED Color Spectrum PWM Mixer</option>
                    <option value="button_toggle">10. Pushbutton Digital Input & Pullup</option>
                    <option value="rc_transient">11. RC Transient Charging & Discharging Curve</option>
                    <option value="photogate">12. Simple Pendulum Optical Photogate ('g' Measurement)</option>
                </select>
            </div>
        </div>

        <!-- Action Tools Bar (Rotate, Delete, Undo, Redo, Wire Color, Code, Start Sim) -->
        <div class="tc-tools-bar">
            <!-- Left Tools -->
            <div class="tc-tools-left">
                <!-- Rotate (R) -->
                <button type="button" class="tc-tool-btn" id="tcRotateBtn" title="Rotate Selected Component (R)">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
                <!-- Delete (Del) -->
                <button type="button" class="tc-tool-btn" id="tcDeleteBtn" title="Delete Selected Item (Delete/Backspace)">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
                <!-- Undo (Ctrl+Z) -->
                <button type="button" class="tc-tool-btn" id="tcUndoBtn" title="Undo (Ctrl+Z)">
                    <i class="fa-solid fa-arrow-rotate-left"></i>
                </button>
                <!-- Redo (Ctrl+Y) -->
                <button type="button" class="tc-tool-btn" id="tcRedoBtn" title="Redo (Ctrl+Y)">
                    <i class="fa-solid fa-arrow-rotate-right"></i>
                </button>
                
                <div class="tc-divider-v"></div>

                <!-- Wire Color Dropdown (Tinkercad Style) -->
                <div class="tc-wire-color-dropdown">
                    <button type="button" class="tc-wire-color-btn" id="tcWireColorBtn" title="Select Wire Color">
                        <span class="swatch-preview" id="tcCurrentColorSwatch" style="background: #10b981;"></span>
                        <span id="tcCurrentColorName">Green</span>
                        <i class="fa-solid fa-caret-down" style="font-size: 0.7rem; margin-left: 2px;"></i>
                    </button>
                    <!-- Color Palette Popover -->
                    <div class="tc-color-palette-popover" id="tcColorPalettePopover" style="display: none;">
                        <div class="tc-color-option selected" data-color="#10b981" data-name="Green" style="background: #10b981;" title="Green"></div>
                        <div class="tc-color-option" data-color="#0f172a" data-name="Black" style="background: #0f172a;" title="Black (GND)"></div>
                        <div class="tc-color-option" data-color="#ef4444" data-name="Red" style="background: #ef4444;" title="Red (5V Power)"></div>
                        <div class="tc-color-option" data-color="#0284c7" data-name="Blue" style="background: #0284c7;" title="Blue"></div>
                        <div class="tc-color-option" data-color="#eab308" data-name="Yellow" style="background: #eab308;" title="Yellow"></div>
                        <div class="tc-color-option" data-color="#f97316" data-name="Orange" style="background: #f97316;" title="Orange"></div>
                        <div class="tc-color-option" data-color="#ffffff" data-name="White" style="background: #ffffff; border-color: #cbd5e1;" title="White"></div>
                        <div class="tc-color-option" data-color="#8b5cf6" data-name="Purple" style="background: #8b5cf6;" title="Purple"></div>
                        <div class="tc-color-option" data-color="#ec4899" data-name="Pink" style="background: #ec4899;" title="Pink"></div>
                        <div class="tc-color-option" data-color="#78350f" data-name="Brown" style="background: #78350f;" title="Brown"></div>
                        <div class="tc-color-option" data-color="#64748b" data-name="Grey" style="background: #64748b;" title="Grey"></div>
                        <div class="tc-color-option" data-color="#06b6d4" data-name="Turquoise" style="background: #06b6d4;" title="Turquoise"></div>
                    </div>
                </div>

                <!-- Wire Type Dropdown -->
                <button type="button" class="tc-wire-type-btn" id="tcWireTypeBtn" title="Wire Type">
                    <i class="fa-solid fa-grip-lines"></i>
                    <span>Normal</span>
                </button>

                <div class="tc-divider-v"></div>

                <!-- Quick Auto-Wire / Reset Wires -->
                <button type="button" class="btn-tc-code-toggle" id="tcAutoWireBtn" style="font-size: 0.78rem; padding: 0.35rem 0.7rem;" title="Auto-connect required wires for current preset">
                    <i class="fa-solid fa-wand-magic-sparkles" style="color: #0284c7;"></i> Auto-Wire
                </button>
                <button type="button" class="btn-tc-code-toggle" id="tcClearWiresBtn" style="font-size: 0.78rem; padding: 0.35rem 0.7rem;" title="Clear all wires for freeform practice">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </button>
            </div>

            <!-- Right Tools -->
            <div class="tc-tools-right">
                <div class="tc-sim-time-badge" id="tcSimTimer" title="Simulation Time Elapsed">00:00.000</div>
                
                <!-- Code Toggle Button -->
                <button type="button" class="btn-tc-code-toggle" id="tcToggleCodeBtn" title="Toggle Code Editor Side-by-Side with Circuit">
                    <i class="fa-solid fa-code"></i> Code
                </button>

                <!-- Export Dropdown -->
                <div class="tc-export-dropdown">
                    <button type="button" class="btn-tc-code-toggle" id="tcExportMenuBtn" title="Export Circuit Image or Arduino Code">
                        <i class="fa-solid fa-file-export"></i> Export <i class="fa-solid fa-caret-down" style="font-size: 0.7rem; margin-left: 2px;"></i>
                    </button>
                    <div class="tc-export-menu" id="tcExportMenu" style="display: none;">
                        <div class="tc-export-header"><i class="fa-regular fa-image"></i> Export Images</div>
                        <button type="button" class="tc-export-item" id="tcExportCircuitPngBtn">
                            <i class="fa-regular fa-image" style="color: #38bdf8;"></i> Circuit Image (.png)
                        </button>
                        <button type="button" class="tc-export-item" id="tcExportCircuitSvgBtn">
                            <i class="fa-solid fa-bezier-curve" style="color: #a855f7;"></i> Circuit Vector (.svg)
                        </button>
                        <button type="button" class="tc-export-item" id="tcExportSchematicBtn">
                            <i class="fa-solid fa-diagram-project" style="color: #0284c7;"></i> Schematic Vector (.svg)
                        </button>
                        <div class="tc-export-divider"></div>
                        <div class="tc-export-header"><i class="fa-solid fa-code"></i> Export Firmware Code</div>
                        <button type="button" class="tc-export-item" id="tcExportCodeInoBtn">
                            <i class="fa-brands fa-cuttlefish" style="color: #10b981;"></i> Download Sketch (.ino)
                        </button>
                        <button type="button" class="tc-export-item" id="tcExportCodeCppBtn">
                            <i class="fa-solid fa-file-code" style="color: #f59e0b;"></i> Download C++ Source (.cpp)
                        </button>
                        <button type="button" class="tc-export-item" id="tcCopyCodeMenuBtn">
                            <i class="fa-regular fa-copy" style="color: #e2e8f0;"></i> Copy Code to Clipboard
                        </button>
                    </div>
                </div>

                <!-- Start / Stop Simulation -->
                <button type="button" class="btn-tc-sim start" id="tcStartSimBtn">
                    <i class="fa-solid fa-play"></i> Start Simulation
                </button>
            </div>
        </div>

        <!-- Main Studio Workspace (Canvas + Right Drawer) -->
        <div class="tc-workbench-body">
            
            <!-- Left/Center: Interactive Circuit Canvas Container -->
            <div class="tc-canvas-container" id="tcCanvasContainer">
                
                <!-- Circuit Warning & Connectivity Floating Banner -->
                <div class="tc-circuit-warning" id="tcCircuitWarning"></div>

                <!-- Canvas Floating Zoom / Fit Controls -->
                <div class="tc-canvas-nav">
                    <button type="button" class="tc-nav-btn" id="tcZoomInBtn" title="Zoom In (+)"><i class="fa-solid fa-plus"></i></button>
                    <button type="button" class="tc-nav-btn" id="tcZoomOutBtn" title="Zoom Out (-)"><i class="fa-solid fa-minus"></i></button>
                    <button type="button" class="tc-nav-btn" id="tcZoomResetBtn" title="Fit to Viewport"><i class="fa-solid fa-expand"></i></button>
                </div>

                <!-- Infinite/Pannable Stage -->
                <div class="tc-canvas-stage" id="tcCanvasStage">
                    
                    <!-- SVG Wires Layer (Multi-Point Orthogonal & Curved Wires) -->
                    <svg class="tc-wires-svg" id="tcWiresSvg">
                        <g id="tcWiresGroup"></g>
                        <!-- Rubberband Live Drawing Wire Path -->
                        <path id="tcRubberbandWire" class="tc-rubberband-wire" style="display: none;"></path>
                    </svg>

                    <!-- Interactive Terminal Pins Overlay Container -->
                    <div id="tcTerminalsContainer"></div>

                    <!-- Photorealistic Arduino Uno R3 Board SVG -->
                    <div class="tc-arduino-uno" id="arduinoUno" style="top: 75px; left: 35px;">
                        <svg viewBox="0 0 360 250" width="360" height="250" xmlns="http://www.w3.org/2000/svg">
                            <!-- Drop Shadow Filter -->
                            <defs>
                                <filter id="pcbShadow" x="-10%" y="-10%" width="130%" height="130%">
                                    <feDropShadow dx="0" dy="8" stdDeviation="6" flood-opacity="0.35"/>
                                </filter>
                                <linearGradient id="arduinoPcb" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#00979d"/>
                                    <stop offset="100%" stop-color="#006468"/>
                                </linearGradient>
                                <linearGradient id="usbMetal" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#e2e8f0"/>
                                    <stop offset="50%" stop-color="#94a3b8"/>
                                    <stop offset="100%" stop-color="#cbd5e1"/>
                                </linearGradient>
                            </defs>

                            <!-- USB Type-B Port and Cable (Extending Left as in Tinkercad) -->
                            <!-- Black USB Cable Strain Relief -->
                            <rect x="-65" y="42" width="45" height="24" rx="3" fill="#1e293b"/>
                            <rect x="-85" y="49" width="22" height="10" rx="2" fill="#0f172a"/>
                            <rect x="-20" y="38" width="35" height="32" rx="3" fill="url(#usbMetal)" stroke="#64748b" stroke-width="1.5"/>
                            <rect x="-8" y="44" width="16" height="20" rx="1" fill="#0f172a"/>

                            <!-- DC Barrel Power Jack (Bottom Left) -->
                            <rect x="-15" y="160" width="45" height="42" rx="3" fill="#1e293b" stroke="#0f172a" stroke-width="2"/>
                            <circle cx="20" cy="181" r="5" fill="#475569"/>

                            <!-- Main PCB Board (Authentic Arduino Uno Outline with mounting tabs) -->
                            <path d="M 15 20 L 320 20 A 10 10 0 0 1 330 30 L 330 220 A 10 10 0 0 1 320 230 L 25 230 A 10 10 0 0 1 15 220 Z" 
                                  fill="url(#arduinoPcb)" stroke="#004d50" stroke-width="2.5" filter="url(#pcbShadow)"/>

                            <!-- Mounting Holes with Gold Annular Rings -->
                            <circle cx="35" cy="75" r="7" fill="none" stroke="#eab308" stroke-width="2.5"/>
                            <circle cx="35" cy="75" r="4.5" fill="#0f172a"/>
                            <circle cx="150" cy="215" r="7" fill="none" stroke="#eab308" stroke-width="2.5"/>
                            <circle cx="150" cy="215" r="4.5" fill="#0f172a"/>
                            <circle cx="315" cy="35" r="7" fill="none" stroke="#eab308" stroke-width="2.5"/>
                            <circle cx="315" cy="35" r="4.5" fill="#0f172a"/>
                            <circle cx="315" cy="215" r="7" fill="none" stroke="#eab308" stroke-width="2.5"/>
                            <circle cx="315" cy="215" r="4.5" fill="#0f172a"/>

                            <!-- Silkscreen Branding & Logos -->
                            <text x="140" y="70" fill="#ffffff" font-family="'Plus Jakarta Sans', sans-serif" font-weight="800" font-size="14" letter-spacing="1">ARDUINO</text>
                            <rect x="235" y="56" width="45" height="18" rx="3" fill="#004d50"/>
                            <text x="242" y="69" fill="#ffffff" font-family="monospace" font-weight="700" font-size="11">UNO</text>
                            <!-- Infinity Logo -->
                            <circle cx="120" cy="66" r="6" fill="none" stroke="#ffffff" stroke-width="2"/>
                            <circle cx="130" cy="66" r="6" fill="none" stroke="#ffffff" stroke-width="2"/>

                            <!-- Microcontroller: ATmega328P DIP-28 Chip with Silver Legs -->
                            <g transform="translate(140, 115)">
                                <rect x="0" y="0" width="130" height="34" rx="4" fill="#0a0e17" stroke="#334155" stroke-width="1.5"/>
                                <circle cx="8" cy="17" r="3" fill="#334155"/>
                                <text x="18" y="21" fill="#94a3b8" font-family="'JetBrains Mono', monospace" font-size="9" letter-spacing="1">ATmega328P-PU</text>
                                <!-- Silver Pins top & bottom -->
                                <?php for($p=0; $p<14; $p++): ?>
                                    <rect x="<?php echo 10 + $p * 8.2; ?>" y="-4" width="3.5" height="4" fill="#cbd5e1"/>
                                    <rect x="<?php echo 10 + $p * 8.2; ?>" y="34" width="3.5" height="4" fill="#cbd5e1"/>
                                <?php endfor; ?>
                            </g>

                            <!-- 16 MHz Quartz Crystal Oscillator (Silver Oval) -->
                            <rect x="85" y="115" width="22" height="32" rx="10" fill="url(#usbMetal)" stroke="#64748b" stroke-width="1.5"/>
                            <text x="91" y="134" fill="#334155" font-family="monospace" font-size="6" transform="rotate(90 91 134)">16.000</text>

                            <!-- Reset Pushbutton (Red momentary cap with metal housing) -->
                            <rect x="42" y="32" width="18" height="18" rx="3" fill="#e2e8f0" stroke="#94a3b8" stroke-width="1"/>
                            <circle cx="51" cy="41" r="5" fill="#dc2626" id="arduinoHwResetBtn" style="cursor: pointer;"/>

                            <!-- Onboard LEDs: ON (Green) & L Pin 13 (Amber) -->
                            <rect x="95" y="60" width="5" height="8" rx="1" fill="#334155" id="arduinoOnLed"/>
                            <text x="82" y="67" fill="#cbd5e1" font-family="monospace" font-size="7" font-weight="bold">ON</text>

                            <rect x="108" y="80" width="5" height="8" rx="1" fill="#334155" id="arduinoBuiltinLed"/>
                            <text x="97" y="87" fill="#cbd5e1" font-family="monospace" font-size="7" font-weight="bold">L</text>

                            <!-- ================= TOP DIGITAL PIN HEADERS ================= -->
                            <!-- Digital Socket Header Black Bar (D0 to D13, GND, AREF) -->
                            <rect x="110" y="24" width="210" height="15" rx="2" fill="#0f172a" stroke="#1e293b" stroke-width="1"/>
                            <!-- Silkscreen Header Labels -->
                            <text x="114" y="18" fill="#e2e8f0" font-family="monospace" font-size="6.5">AREF GND 13 12 ~11 ~10 ~9 8   7 ~6 ~5 4 ~3 2 TX>1 RX<0</text>

                            <!-- Individual Gold Pin Receptacles -->
                            <!-- AREF, GND, D13, D12, D11, D10, D9, D8 -->
                            <g fill="#1e293b" stroke="#eab308" stroke-width="1">
                                <rect x="114" y="27" width="8" height="9" rx="1"/>
                                <rect x="127" y="27" width="8" height="9" rx="1"/>
                                <rect x="140" y="27" width="8" height="9" rx="1" id="pin-13-rect"/>
                                <rect x="153" y="27" width="8" height="9" rx="1" id="pin-12-rect"/>
                                <rect x="166" y="27" width="8" height="9" rx="1" id="pin-11-rect"/>
                                <rect x="179" y="27" width="8" height="9" rx="1" id="pin-10-rect"/>
                                <rect x="192" y="27" width="8" height="9" rx="1" id="pin-9-rect"/>
                                <rect x="205" y="27" width="8" height="9" rx="1" id="pin-8-rect"/>
                                <!-- Gap -->
                                <rect x="222" y="27" width="8" height="9" rx="1" id="pin-7-rect"/>
                                <rect x="235" y="27" width="8" height="9" rx="1" id="pin-6-rect"/>
                                <rect x="248" y="27" width="8" height="9" rx="1" id="pin-5-rect"/>
                                <rect x="261" y="27" width="8" height="9" rx="1" id="pin-4-rect"/>
                                <rect x="274" y="27" width="8" height="9" rx="1" id="pin-3-rect"/>
                                <rect x="287" y="27" width="8" height="9" rx="1" id="pin-2-rect"/>
                                <rect x="300" y="27" width="8" height="9" rx="1" id="pin-1-rect"/>
                                <rect x="313" y="27" width="8" height="9" rx="1" id="pin-0-rect"/>
                            </g>

                            <!-- ================= BOTTOM POWER & ANALOG PIN HEADERS ================= -->
                            <!-- Power Socket Header (IOREF, RESET, 3.3V, 5V, GND, GND, VIN) -->
                            <rect x="130" y="210" width="88" height="15" rx="2" fill="#0f172a" stroke="#1e293b" stroke-width="1"/>
                            <text x="132" y="206" fill="#e2e8f0" font-family="monospace" font-size="6.5">IOREF RST 3.3V 5V GND GND VIN</text>
                            <g fill="#1e293b" stroke="#eab308" stroke-width="1">
                                <rect x="133" y="213" width="8" height="9" rx="1"/>
                                <rect x="145" y="213" width="8" height="9" rx="1"/>
                                <rect x="157" y="213" width="8" height="9" rx="1"/>
                                <rect x="169" y="213" width="8" height="9" rx="1" id="pin-5v-rect"/>
                                <rect x="181" y="213" width="8" height="9" rx="1" id="pin-gnd1-rect"/>
                                <rect x="193" y="213" width="8" height="9" rx="1" id="pin-gnd2-rect"/>
                                <rect x="205" y="213" width="8" height="9" rx="1"/>
                            </g>

                            <!-- Analog In Socket Header (A0 to A5) -->
                            <rect x="235" y="210" width="75" height="15" rx="2" fill="#0f172a" stroke="#1e293b" stroke-width="1"/>
                            <text x="237" y="206" fill="#e2e8f0" font-family="monospace" font-size="6.5">A0  A1  A2  A3  A4  A5</text>
                            <g fill="#1e293b" stroke="#eab308" stroke-width="1">
                                <rect x="238" y="213" width="8" height="9" rx="1" id="pin-a0-rect"/>
                                <rect x="250" y="213" width="8" height="9" rx="1" id="pin-a1-rect"/>
                                <rect x="262" y="213" width="8" height="9" rx="1" id="pin-a2-rect"/>
                                <rect x="274" y="213" width="8" height="9" rx="1" id="pin-a3-rect"/>
                                <rect x="286" y="213" width="8" height="9" rx="1" id="pin-a4-rect"/>
                                <rect x="298" y="213" width="8" height="9" rx="1" id="pin-a5-rect"/>
                            </g>

                        </svg>
                    </div>

                    <!-- Photorealistic Half-Size Solderless Breadboard (600x330 SVG) -->
                    <div class="tc-breadboard" id="breadboardSmall" style="top: 35px; left: 430px; width: 600px; height: 330px;">
                        <svg id="tcBreadboardSvg" viewBox="0 0 600 330" width="600" height="330" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <filter id="bbShadow" x="-5%" y="-5%" width="110%" height="115%">
                                    <feDropShadow dx="0" dy="6" stdDeviation="5" flood-opacity="0.22"/>
                                </filter>
                                <linearGradient id="bbPlastic" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#faf8f2"/>
                                    <stop offset="100%" stop-color="#f1ece1"/>
                                </linearGradient>
                                <linearGradient id="bbTrough" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#d5ccbb"/>
                                    <stop offset="50%" stop-color="#baa992"/>
                                    <stop offset="100%" stop-color="#d5ccbb"/>
                                </linearGradient>
                                <!-- 3D embossed hole socket template with phosphor-bronze spring clips -->
                                <g id="bbHoleTemplate">
                                    <rect x="-4.2" y="-4.2" width="8.4" height="8.4" rx="1.5" fill="#e8e2d5" stroke="#cac0af" stroke-width="0.7"/>
                                    <rect x="-2.8" y="-2.8" width="5.6" height="5.6" rx="0.8" fill="#181c24"/>
                                    <line x1="-1.2" y1="-2" x2="-1.2" y2="2" stroke="#64748b" stroke-width="0.6"/>
                                    <line x1="1.2" y1="-2" x2="1.2" y2="2" stroke="#64748b" stroke-width="0.6"/>
                                </g>
                            </defs>

                            <!-- Perimeter Dovetail Interlocking Tabs (Left, Right, Top, Bottom) -->
                            <!-- Left Tab Notch -->
                            <path d="M 0 145 L 8 152 L 8 178 L 0 185 Z" fill="#ebe4d5" stroke="#d5cebe" stroke-width="1.2"/>
                            <!-- Right Protruding Tab -->
                            <path d="M 600 145 L 608 152 L 608 178 L 600 185 Z" fill="#faf8f2" stroke="#d5cebe" stroke-width="1.2"/>
                            <!-- Top Tab -->
                            <path d="M 285 0 L 292 8 L 308 8 L 315 0 Z" fill="#ebe4d5" stroke="#d5cebe" stroke-width="1.2"/>
                            <!-- Bottom Protruding Tab -->
                            <path d="M 285 330 L 292 338 L 308 338 L 315 330 Z" fill="#f1ece1" stroke="#d5cebe" stroke-width="1.2"/>

                            <!-- Main ABS Breadboard Body -->
                            <rect x="0" y="0" width="600" height="330" rx="8" ry="8" fill="url(#bbPlastic)" stroke="#d5cebe" stroke-width="1.8" filter="url(#bbShadow)"/>
                            <!-- Inner border highlight -->
                            <rect x="2" y="2" width="596" height="326" rx="6" ry="6" fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="1"/>

                            <!-- Silkscreen Header: Breadboard Title & Maker Notes -->
                            <text x="300" y="13" fill="#a89f8f" font-family="'Plus Jakarta Sans', sans-serif" font-weight="700" font-size="8.5" text-anchor="middle" letter-spacing="1.2">SOLDERLESS BREADBOARD • 400 TIE-POINTS</text>

                            <!-- ================= TOP POWER RAILS ================= -->
                            <!-- Positive (+) Red Line -->
                            <line x1="42" y1="20" x2="558" y2="20" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
                            <text x="28" y="24" fill="#ef4444" font-family="sans-serif" font-weight="900" font-size="13" text-anchor="middle">+</text>
                            <text x="572" y="24" fill="#ef4444" font-family="sans-serif" font-weight="900" font-size="13" text-anchor="middle">+</text>

                            <!-- Negative (-) Blue Line -->
                            <line x1="42" y1="42" x2="558" y2="42" stroke="#3b82f6" stroke-width="2" stroke-linecap="round"/>
                            <text x="28" y="46" fill="#3b82f6" font-family="sans-serif" font-weight="900" font-size="15" text-anchor="middle">−</text>
                            <text x="572" y="46" fill="#3b82f6" font-family="sans-serif" font-weight="900" font-size="15" text-anchor="middle">−</text>

                            <!-- Holes for Top Power Rails (1 to 30) -->
                            <?php for($c=1; $c<=30; $c++): 
                                $hx = 52 + ($c - 1) * 17;
                            ?>
                                <use href="#bbHoleTemplate" x="<?php echo $hx; ?>" y="20" id="svg-bb-top-pos-<?php echo $c; ?>"/>
                                <use href="#bbHoleTemplate" x="<?php echo $hx; ?>" y="42" id="svg-bb-top-neg-<?php echo $c; ?>"/>
                            <?php endfor; ?>

                            <!-- ================= UPPER TERMINAL BANK (Rows a to e) ================= -->
                            <!-- Column Numbers Top -->
                            <?php for($c=1; $c<=30; $c++): 
                                $hx = 52 + ($c - 1) * 17;
                                if($c == 1 || $c % 5 == 0):
                            ?>
                                <text x="<?php echo $hx; ?>" y="67" fill="#64748b" font-family="'JetBrains Mono', monospace" font-weight="700" font-size="8.5" text-anchor="middle"><?php echo $c; ?></text>
                            <?php endif; endfor; ?>

                            <!-- Row Letters Left and Right -->
                            <?php 
                            $upperRows = ['a' => 82, 'b' => 99, 'c' => 116, 'd' => 133, 'e' => 150];
                            foreach($upperRows as $r => $ry): 
                            ?>
                                <text x="28" y="<?php echo $ry + 3; ?>" fill="#64748b" font-family="'JetBrains Mono', monospace" font-weight="700" font-size="9" text-anchor="middle"><?php echo $r; ?></text>
                                <text x="572" y="<?php echo $ry + 3; ?>" fill="#64748b" font-family="'JetBrains Mono', monospace" font-weight="700" font-size="9" text-anchor="middle"><?php echo $r; ?></text>
                                <?php for($c=1; $c<=30; $c++): 
                                    $hx = 52 + ($c - 1) * 17;
                                ?>
                                    <use href="#bbHoleTemplate" x="<?php echo $hx; ?>" y="<?php echo $ry; ?>" id="svg-bb-<?php echo $r . $c; ?>"/>
                                <?php endfor; ?>
                            <?php endforeach; ?>

                            <!-- Center IC Isolation Trench / Ravine -->
                            <rect x="36" y="160" width="528" height="14" rx="2" fill="url(#bbTrough)" stroke="#c4baa7" stroke-width="0.8"/>
                            <line x1="38" y1="167" x2="562" y2="167" stroke="rgba(255,255,255,0.4)" stroke-width="0.8"/>

                            <!-- ================= LOWER TERMINAL BANK (Rows f to j) ================= -->
                            <?php 
                            $lowerRows = ['f' => 184, 'g' => 201, 'h' => 218, 'i' => 235, 'j' => 252];
                            foreach($lowerRows as $r => $ry): 
                            ?>
                                <text x="28" y="<?php echo $ry + 3; ?>" fill="#64748b" font-family="'JetBrains Mono', monospace" font-weight="700" font-size="9" text-anchor="middle"><?php echo $r; ?></text>
                                <text x="572" y="<?php echo $ry + 3; ?>" fill="#64748b" font-family="'JetBrains Mono', monospace" font-weight="700" font-size="9" text-anchor="middle"><?php echo $r; ?></text>
                                <?php for($c=1; $c<=30; $c++): 
                                    $hx = 52 + ($c - 1) * 17;
                                ?>
                                    <use href="#bbHoleTemplate" x="<?php echo $hx; ?>" y="<?php echo $ry; ?>" id="svg-bb-<?php echo $r . $c; ?>"/>
                                <?php endfor; ?>
                            <?php endforeach; ?>

                            <!-- Column Numbers Bottom -->
                            <?php for($c=1; $c<=30; $c++): 
                                $hx = 52 + ($c - 1) * 17;
                                if($c == 1 || $c % 5 == 0):
                            ?>
                                <text x="<?php echo $hx; ?>" y="271" fill="#64748b" font-family="'JetBrains Mono', monospace" font-weight="700" font-size="8.5" text-anchor="middle"><?php echo $c; ?></text>
                            <?php endif; endfor; ?>

                            <!-- ================= BOTTOM POWER RAILS ================= -->
                            <!-- Positive (+) Red Line -->
                            <line x1="42" y1="288" x2="558" y2="288" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
                            <text x="28" y="292" fill="#ef4444" font-family="sans-serif" font-weight="900" font-size="13" text-anchor="middle">+</text>
                            <text x="572" y="292" fill="#ef4444" font-family="sans-serif" font-weight="900" font-size="13" text-anchor="middle">+</text>

                            <!-- Negative (-) Blue Line -->
                            <line x1="42" y1="310" x2="558" y2="310" stroke="#3b82f6" stroke-width="2" stroke-linecap="round"/>
                            <text x="28" y="314" fill="#3b82f6" font-family="sans-serif" font-weight="900" font-size="15" text-anchor="middle">−</text>
                            <text x="572" y="314" fill="#3b82f6" font-family="sans-serif" font-weight="900" font-size="15" text-anchor="middle">−</text>

                            <!-- Holes for Bottom Power Rails (1 to 30) -->
                            <?php for($c=1; $c<=30; $c++): 
                                $hx = 52 + ($c - 1) * 17;
                            ?>
                                <use href="#bbHoleTemplate" x="<?php echo $hx; ?>" y="288" id="svg-bb-bot-pos-<?php echo $c; ?>"/>
                                <use href="#bbHoleTemplate" x="<?php echo $hx; ?>" y="310" id="svg-bb-bot-neg-<?php echo $c; ?>"/>
                            <?php endfor; ?>

                            <!-- Bus Highlight Overlay Group (for when a pin/hole is hovered) -->
                            <g id="tcBbBusHighlightGroup" pointer-events="none"></g>
                        </svg>
                    </div>

                    <!-- Placed Electronic Components Container (Rendered dynamically) -->
                    <div id="tcComponentsContainer"></div>

                    <!-- Component Inspector Dialog Popover -->
                    <div class="tc-component-inspector" id="tcComponentInspector" style="display: none;">
                        <div class="tc-inspector-title">
                            <span id="tcInspectorName">LED</span>
                            <button type="button" id="tcCloseInspectorBtn" style="background: none; border: none; cursor: pointer; color: var(--tc-text-muted);">&times;</button>
                        </div>
                        <div id="tcInspectorFields"></div>
                    </div>

                </div>

                <!-- Bottom Status Bar -->
                <div class="tc-status-bar" id="tcStatusBar"></div>

            </div>

            <!-- Draggable Splitter Divider between Circuit and Code -->
            <div class="tc-split-resizer" id="tcSplitResizer" style="display: none;" title="Drag left or right to adjust Circuit and Code window width (Double-click to reset 50/50)">
                <div class="tc-resizer-handle"></div>
            </div>

            <!-- Code Editor Resizable Pane (side-by-side with Circuit) -->
            <div class="tc-code-pane" id="tcCodePane" style="display: none; width: 480px; min-width: 280px; max-width: calc(100% - 250px);">
                <div class="tc-code-header">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fa-brands fa-cuttlefish" style="color: #38bdf8;"></i>
                        <span style="font-weight: 700;">Arduino C++ Sketch (.ino)</span>
                    </div>
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <button type="button" class="btn-tc-code-toggle" id="tcVerifyCodeBtn" style="padding: 2px 7px; font-size: 0.74rem;" title="Verify / Check Sketch Syntax">
                            <i class="fa-solid fa-check" style="color: #10b981;"></i> Verify
                        </button>
                        <button type="button" class="btn-tc-code-toggle" id="tcResetCodeBtn" style="padding: 2px 7px; font-size: 0.74rem;" title="Reset to Preset Sketch">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </button>
                        <button type="button" class="btn-tc-code-toggle" id="tcDownloadInoBtn" style="padding: 2px 7px; font-size: 0.74rem;" title="Download Sketch (.ino)">
                            <i class="fa-solid fa-download" style="color: #38bdf8;"></i> .ino
                        </button>
                        <button type="button" class="btn-tc-code-toggle" id="tcCopyCodeBtn" style="padding: 2px 7px; font-size: 0.74rem;" title="Copy to Clipboard">
                            <i class="fa-regular fa-copy"></i> Copy
                        </button>
                        <button type="button" class="btn-tc-code-toggle" id="tcCloseCodeBtn" style="padding: 2px 6px; font-size: 0.74rem;" title="Close Code Window">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
                <div class="tc-codemirror-holder" style="flex: 1; height: calc(100% - 40px); position: relative;">
                    <textarea id="tcCodeTextarea"></textarea>
                </div>
            </div>

            <!-- Schematic Diagram View Container -->
            <div class="tc-schematic-container" id="tcSchematicContainer" style="display: none; flex: 1; height: 100%; overflow: auto; background: var(--tc-bg-canvas); padding: 1.25rem; position: relative;">
                <div class="tc-schematic-toolbar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
                    <div style="font-weight: 700; font-size: 0.95rem; color: var(--tc-text-main); display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-bezier-curve" style="color: #0284c7;"></i> Live Schematic Diagram
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="btn-tc-code-toggle" id="tcExportSchematicBtn" style="font-size: 0.78rem; padding: 0.35rem 0.75rem;" title="Download Schematic Diagram as SVG">
                            <i class="fa-solid fa-download"></i> Export SVG
                        </button>
                    </div>
                </div>
                <div id="tcSchematicSvgWrapper" style="background: #ffffff; border-radius: 8px; border: 1px solid var(--tc-toolbar-border); padding: 16px; min-height: 480px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; justify-content: center; align-items: center; overflow: auto;">
                    <!-- Rendered dynamically -->
                </div>
            </div>

            <!-- Bill of Materials (BOM) & Components Container -->
            <div class="tc-bom-container" id="tcBomContainer" style="display: none; flex: 1; height: 100%; overflow: auto; background: var(--tc-panel-bg); padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div style="font-weight: 700; font-size: 0.95rem; color: var(--tc-text-main); display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-list-check" style="color: #10b981;"></i> Bill of Materials (BOM)
                    </div>
                    <button type="button" class="btn-tc-code-toggle" id="tcExportBomBtn" style="font-size: 0.78rem; padding: 0.35rem 0.75rem;" title="Download BOM Table as CSV">
                        <i class="fa-solid fa-file-csv"></i> Export CSV
                    </button>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;" id="tcBomTable">
                        <thead>
                            <tr style="background: rgba(0,0,0,0.03); border-bottom: 2px solid var(--tc-toolbar-border); color: var(--tc-text-muted);">
                                <th style="padding: 8px 12px; text-align: left;">Item #</th>
                                <th style="padding: 8px 12px; text-align: left;">Part Name</th>
                                <th style="padding: 8px 12px; text-align: left;">Designator</th>
                                <th style="padding: 8px 12px; text-align: left;">Properties / Value</th>
                                <th style="padding: 8px 12px; text-align: left;">Net Connections</th>
                                <th style="padding: 8px 12px; text-align: center;">Qty</th>
                            </tr>
                        </thead>
                        <tbody id="tcBomTableBody">
                            <!-- Generated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right-Hand Drawer: Components Palette OR Code Editor -->
            <div class="tc-right-drawer" id="tcRightDrawer">
                
                <!-- Drawer 1: Components Basic Palette (as in Tinkercad screenshot) -->
                <div id="tcComponentsPalettePane" style="display: flex; flex-direction: column; height: 100%;">
                    
                    <div class="tc-drawer-header">
                        <select class="tc-comp-category-select" id="tcCompCategory">
                            <option value="basic">Components: Basic</option>
                            <option value="sensors">Components: Sensors</option>
                            <option value="actuators">Components: Actuators / Output</option>
                            <option value="all">Components: All</option>
                            <option value="starters">Starters: Arduino</option>
                        </select>
                        <div class="tc-search-box">
                            <i class="fa-solid fa-magnifying-glass" style="color: var(--tc-text-muted); font-size: 0.8rem;"></i>
                            <input type="text" id="tcSearchInput" class="tc-search-input" placeholder="Search components...">
                        </div>
                    </div>

                    <!-- 2-Column Components Grid -->
                    <div class="tc-components-grid" id="tcComponentsGrid">
                        
                        <!-- Resistor -->
                        <div class="tc-component-card" data-component-type="resistor" data-category="basic" title="Click or Drag onto breadboard">
                            <div class="tc-component-card-icon">
                                <svg width="44" height="20" viewBox="0 0 44 20">
                                    <line x1="2" y1="10" x2="12" y2="10" stroke="#94a3b8" stroke-width="2"/>
                                    <rect x="12" y="4" width="20" height="12" rx="4" fill="#fcd34d" stroke="#d97706" stroke-width="1"/>
                                    <rect x="15" y="4" width="2" height="12" fill="#dc2626"/>
                                    <rect x="19" y="4" width="2" height="12" fill="#dc2626"/>
                                    <rect x="23" y="4" width="2" height="12" fill="#78350f"/>
                                    <rect x="28" y="4" width="2" height="12" fill="#eab308"/>
                                    <line x1="32" y1="10" x2="42" y2="10" stroke="#94a3b8" stroke-width="2"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Resistor</span>
                        </div>

                        <!-- 5mm LED -->
                        <div class="tc-component-card" data-component-type="led" data-category="basic" title="Click or Drag onto breadboard">
                            <div class="tc-component-card-icon">
                                <svg width="34" height="40" viewBox="0 0 34 40">
                                    <line x1="12" y1="24" x2="12" y2="38" stroke="#94a3b8" stroke-width="2"/>
                                    <path d="M 22 24 L 22 30 L 20 33 L 20 38" fill="none" stroke="#94a3b8" stroke-width="2"/>
                                    <path d="M 8 22 A 9 9 0 0 1 26 22 L 26 24 L 8 24 Z" fill="#ef4444" stroke="#b91c1c" stroke-width="1.5"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">LED</span>
                        </div>

                        <!-- RGB LED (Common Cathode) -->
                        <div class="tc-component-card" data-component-type="rgb_led" data-category="basic" title="4-pin RGB LED (Common Cathode)">
                            <div class="tc-component-card-icon">
                                <svg width="36" height="40" viewBox="0 0 36 40">
                                    <!-- 4 leads -->
                                    <line x1="8" y1="24" x2="8" y2="38" stroke="#94a3b8" stroke-width="1.8"/>
                                    <line x1="14" y1="24" x2="14" y2="39" stroke="#94a3b8" stroke-width="1.8"/>
                                    <line x1="20" y1="24" x2="20" y2="37" stroke="#94a3b8" stroke-width="1.8"/>
                                    <line x1="26" y1="24" x2="26" y2="36" stroke="#94a3b8" stroke-width="1.8"/>
                                    <!-- Frosted RGB Dome -->
                                    <defs>
                                        <linearGradient id="rgbDomeGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" stop-color="#ef4444"/>
                                            <stop offset="50%" stop-color="#22c55e"/>
                                            <stop offset="100%" stop-color="#3b82f6"/>
                                        </linearGradient>
                                    </defs>
                                    <path d="M 6 22 A 12 12 0 0 1 30 22 L 30 24 L 6 24 Z" fill="url(#rgbDomeGrad)" stroke="#475569" stroke-width="1.2" opacity="0.9"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">RGB LED</span>
                        </div>

                        <!-- Pushbutton -->
                        <div class="tc-component-card" data-component-type="pushbutton" data-category="basic" title="Tactile momentary pushbutton">
                            <div class="tc-component-card-icon">
                                <svg width="36" height="36" viewBox="0 0 36 36">
                                    <rect x="6" y="6" width="24" height="24" rx="3" fill="#cbd5e1" stroke="#64748b" stroke-width="1.5"/>
                                    <circle cx="18" cy="18" r="7" fill="#0f172a"/>
                                    <line x1="2" y1="10" x2="6" y2="10" stroke="#64748b" stroke-width="2"/>
                                    <line x1="2" y1="26" x2="6" y2="26" stroke="#64748b" stroke-width="2"/>
                                    <line x1="30" y1="10" x2="34" y2="10" stroke="#64748b" stroke-width="2"/>
                                    <line x1="30" y1="26" x2="34" y2="26" stroke="#64748b" stroke-width="2"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Pushbutton</span>
                        </div>

                        <!-- Potentiometer -->
                        <div class="tc-component-card" data-component-type="potentiometer" data-category="basic" title="10k Rotary Potentiometer">
                            <div class="tc-component-card-icon">
                                <svg width="36" height="38" viewBox="0 0 36 38">
                                    <circle cx="18" cy="16" r="14" fill="#0284c7" stroke="#0369a1" stroke-width="1.5"/>
                                    <circle cx="18" cy="16" r="8" fill="#334155"/>
                                    <line x1="18" y1="16" x2="18" y2="10" stroke="#38bdf8" stroke-width="2"/>
                                    <line x1="10" y1="30" x2="10" y2="36" stroke="#94a3b8" stroke-width="2"/>
                                    <line x1="18" y1="30" x2="18" y2="36" stroke="#94a3b8" stroke-width="2"/>
                                    <line x1="26" y1="30" x2="26" y2="36" stroke="#94a3b8" stroke-width="2"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Potentiometer</span>
                        </div>

                        <!-- Slide Switch (SPDT) -->
                        <div class="tc-component-card" data-component-type="slide_switch" data-category="basic" title="SPDT Slide Switch">
                            <div class="tc-component-card-icon">
                                <svg width="40" height="24" viewBox="0 0 40 24">
                                    <rect x="4" y="2" width="32" height="14" rx="2" fill="#94a3b8" stroke="#475569" stroke-width="1"/>
                                    <rect x="8" y="0" width="8" height="18" rx="2" fill="#0f172a" stroke="#334155" stroke-width="1"/>
                                    <line x1="10" y1="16" x2="10" y2="22" stroke="#64748b" stroke-width="2"/>
                                    <line x1="20" y1="16" x2="20" y2="22" stroke="#64748b" stroke-width="2"/>
                                    <line x1="30" y1="16" x2="30" y2="22" stroke="#64748b" stroke-width="2"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Slide Switch</span>
                        </div>

                        <!-- Capacitor -->
                        <div class="tc-component-card" data-component-type="capacitor" data-category="basic" title="100uF Electrolytic Capacitor">
                            <div class="tc-component-card-icon">
                                <svg width="34" height="38" viewBox="0 0 34 38">
                                    <rect x="8" y="2" width="18" height="22" rx="4" fill="#1e293b" stroke="#475569" stroke-width="1.2"/>
                                    <rect x="20" y="2" width="6" height="22" rx="0" fill="#94a3b8"/>
                                    <line x1="12" y1="24" x2="12" y2="36" stroke="#94a3b8" stroke-width="2"/>
                                    <line x1="22" y1="24" x2="22" y2="33" stroke="#94a3b8" stroke-width="2"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Capacitor</span>
                        </div>

                        <!-- Diode (1N4007) -->
                        <div class="tc-component-card" data-component-type="diode" data-category="basic" title="1N4007 Rectifier Diode">
                            <div class="tc-component-card-icon">
                                <svg width="44" height="18" viewBox="0 0 44 18">
                                    <line x1="2" y1="9" x2="12" y2="9" stroke="#94a3b8" stroke-width="2"/>
                                    <rect x="12" y="3" width="20" height="12" rx="2" fill="#0f172a" stroke="#334155" stroke-width="1"/>
                                    <rect x="27" y="3" width="4" height="12" fill="#cbd5e1"/>
                                    <line x1="32" y1="9" x2="42" y2="9" stroke="#94a3b8" stroke-width="2"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Diode</span>
                        </div>

                        <!-- Photoresistor (LDR) -->
                        <div class="tc-component-card" data-component-type="ldr" data-category="sensors" title="Light-Dependent Resistor">
                            <div class="tc-component-card-icon">
                                <svg width="36" height="38" viewBox="0 0 36 38">
                                    <circle cx="18" cy="15" r="11" fill="#ea580c" stroke="#c2410c" stroke-width="1.5"/>
                                    <path d="M 12 11 Q 18 13, 24 11 Q 18 15, 12 17 Q 18 19, 24 17" fill="none" stroke="#fef08a" stroke-width="1.5"/>
                                    <line x1="13" y1="26" x2="13" y2="36" stroke="#94a3b8" stroke-width="2"/>
                                    <line x1="23" y1="26" x2="23" y2="36" stroke="#94a3b8" stroke-width="2"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Photoresistor</span>
                        </div>

                        <!-- Ultrasonic Sensor HC-SR04 -->
                        <div class="tc-component-card" data-component-type="ultrasonic" data-category="sensors" title="HC-SR04 Ultrasonic Distance Sensor">
                            <div class="tc-component-card-icon">
                                <svg width="50" height="28" viewBox="0 0 50 28">
                                    <rect x="2" y="2" width="46" height="24" rx="4" fill="#0284c7" stroke="#0369a1" stroke-width="1"/>
                                    <circle cx="14" cy="14" r="8" fill="#e2e8f0" stroke="#64748b" stroke-width="1"/>
                                    <circle cx="36" cy="14" r="8" fill="#e2e8f0" stroke="#64748b" stroke-width="1"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Ultrasonic Sensor</span>
                        </div>

                        <!-- PIR Motion Sensor -->
                        <div class="tc-component-card" data-component-type="pir" data-category="sensors" title="HC-SR501 PIR Motion Sensor">
                            <div class="tc-component-card-icon">
                                <svg width="42" height="36" viewBox="0 0 42 36">
                                    <rect x="2" y="10" width="38" height="22" rx="3" fill="#15803d" stroke="#166534" stroke-width="1.2"/>
                                    <!-- White Fresnel Dome -->
                                    <circle cx="21" cy="14" r="10" fill="#f8fafc" stroke="#cbd5e1" stroke-width="1"/>
                                    <path d="M 15 14 Q 21 10, 27 14 M 17 17 Q 21 15, 25 17" fill="none" stroke="#94a3b8" stroke-width="0.8"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">PIR Motion Sensor</span>
                        </div>

                        <!-- Temperature Sensor (TMP36) -->
                        <div class="tc-component-card" data-component-type="tmp36" data-category="sensors" title="TMP36 Precision Temperature Sensor (-40C to 125C)">
                            <div class="tc-component-card-icon">
                                <svg width="34" height="38" viewBox="0 0 34 38">
                                    <!-- TO-92 Transistor Package Shape -->
                                    <path d="M 8 18 A 9 9 0 0 1 26 18 L 26 6 L 8 6 Z" fill="#1e293b" stroke="#0f172a" stroke-width="1.2"/>
                                    <text x="17" y="14" fill="#94a3b8" font-size="5" font-family="monospace" text-anchor="middle">TMP</text>
                                    <line x1="11" y1="18" x2="11" y2="34" stroke="#94a3b8" stroke-width="1.8"/>
                                    <line x1="17" y1="18" x2="17" y2="34" stroke="#94a3b8" stroke-width="1.8"/>
                                    <line x1="23" y1="18" x2="23" y2="34" stroke="#94a3b8" stroke-width="1.8"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Temperature (TMP36)</span>
                        </div>

                        <!-- Tilt Sensor -->
                        <div class="tc-component-card" data-component-type="tilt" data-category="sensors" title="Ball Tilt Switch / Vibration Sensor">
                            <div class="tc-component-card-icon">
                                <svg width="34" height="36" viewBox="0 0 34 36">
                                    <rect x="10" y="2" width="14" height="22" rx="7" fill="#eab308" stroke="#ca8a04" stroke-width="1.2"/>
                                    <line x1="13" y1="24" x2="13" y2="34" stroke="#94a3b8" stroke-width="1.8"/>
                                    <line x1="21" y1="24" x2="21" y2="34" stroke="#94a3b8" stroke-width="1.8"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Tilt Sensor</span>
                        </div>

                        <!-- Micro Servo Motor (SG90) -->
                        <div class="tc-component-card" data-component-type="servo" data-category="actuators" title="SG90 9g Micro Servo Motor (0 to 180 degrees)">
                            <div class="tc-component-card-icon">
                                <svg width="44" height="36" viewBox="0 0 44 36">
                                    <rect x="6" y="8" width="32" height="22" rx="3" fill="#0284c7" stroke="#0369a1" stroke-width="1.2"/>
                                    <!-- Gear tower -->
                                    <circle cx="16" cy="10" r="7" fill="#0369a1"/>
                                    <!-- White rotating horn -->
                                    <rect x="14" y="2" width="16" height="5" rx="2" fill="#f8fafc" stroke="#cbd5e1" stroke-width="1"/>
                                    <circle cx="16" cy="4.5" r="2" fill="#0f172a"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Micro Servo SG90</span>
                        </div>

                        <!-- Piezo Buzzer -->
                        <div class="tc-component-card" data-component-type="buzzer" data-category="actuators" title="Piezoelectric Sound Buzzer (tone / noTone)">
                            <div class="tc-component-card-icon">
                                <svg width="38" height="38" viewBox="0 0 38 38">
                                    <circle cx="19" cy="18" r="14" fill="#1e293b" stroke="#334155" stroke-width="1.5"/>
                                    <circle cx="19" cy="18" r="4" fill="#0284c7"/>
                                    <line x1="14" y1="32" x2="14" y2="37" stroke="#94a3b8" stroke-width="1.8"/>
                                    <line x1="24" y1="32" x2="24" y2="37" stroke="#94a3b8" stroke-width="1.8"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Piezo Buzzer</span>
                        </div>

                        <!-- DC Motor with Propeller -->
                        <div class="tc-component-card" data-component-type="dc_motor" data-category="actuators" title="DC Electric Motor with Propeller">
                            <div class="tc-component-card-icon">
                                <svg width="42" height="38" viewBox="0 0 42 38">
                                    <circle cx="21" cy="20" r="13" fill="#94a3b8" stroke="#475569" stroke-width="1.5"/>
                                    <!-- Propeller blades -->
                                    <ellipse cx="21" cy="10" rx="4" ry="7" fill="#ef4444"/>
                                    <ellipse cx="21" cy="30" rx="4" ry="7" fill="#ef4444"/>
                                    <ellipse cx="11" cy="20" rx="7" ry="4" fill="#ef4444"/>
                                    <ellipse cx="31" cy="20" rx="7" ry="4" fill="#ef4444"/>
                                    <circle cx="21" cy="20" r="3" fill="#0f172a"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">DC Motor / Fan</span>
                        </div>

                        <!-- Breadboard Small -->
                        <div class="tc-component-card" data-component-type="breadboard" data-category="basic" title="Solderless Breadboard">
                            <div class="tc-component-card-icon">
                                <svg width="48" height="28" viewBox="0 0 48 28">
                                    <rect x="2" y="2" width="44" height="24" rx="2" fill="#f8fafc" stroke="#cbd5e1" stroke-width="1"/>
                                    <line x1="6" y1="5" x2="42" y2="5" stroke="#ef4444" stroke-width="0.8"/>
                                    <line x1="6" y1="7" x2="42" y2="7" stroke="#0284c7" stroke-width="0.8"/>
                                    <line x1="6" y1="14" x2="42" y2="14" stroke="#94a3b8" stroke-width="1"/>
                                    <line x1="6" y1="21" x2="42" y2="21" stroke="#ef4444" stroke-width="0.8"/>
                                    <line x1="6" y1="23" x2="42" y2="23" stroke="#0284c7" stroke-width="0.8"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Breadboard Small</span>
                        </div>

                        <!-- Arduino Uno R3 -->
                        <div class="tc-component-card" data-component-type="arduino" data-category="basic" title="Arduino Uno R3 Microcontroller">
                            <div class="tc-component-card-icon">
                                <svg width="46" height="34" viewBox="0 0 46 34">
                                    <rect x="2" y="2" width="42" height="30" rx="3" fill="#00878a" stroke="#004d50" stroke-width="1"/>
                                    <rect x="2" y="6" width="6" height="8" fill="#94a3b8"/>
                                    <rect x="18" y="16" width="18" height="6" fill="#0f172a"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Arduino Uno R3</span>
                        </div>

                        <!-- ================= STARTER PROJECTS (Category: starters) ================= -->
                        <!-- Starter 1: LED Blink -->
                        <div class="tc-component-card tc-starter-card" data-component-type="starter" data-preset="blink" data-category="starters" style="display: none;" title="Click to load complete LED Blink & Timing project">
                            <span class="tc-starter-badge">Lab 01</span>
                            <div class="tc-component-card-icon" style="margin-top: 6px;">
                                <i class="fa-solid fa-lightbulb" style="color: #ef4444; font-size: 1.3rem;"></i>
                            </div>
                            <span class="tc-component-card-name">LED Blink</span>
                            <span class="tc-starter-card-desc">Pin 13 & 220Ω Resistor</span>
                        </div>

                        <!-- Starter 2: PWM Breathing -->
                        <div class="tc-component-card tc-starter-card" data-component-type="starter" data-preset="pwm_fade" data-category="starters" style="display: none;" title="Click to load PWM LED Breathing project">
                            <span class="tc-starter-badge">Lab 02</span>
                            <div class="tc-component-card-icon" style="margin-top: 6px;">
                                <i class="fa-solid fa-wave-square" style="color: #0284c7; font-size: 1.3rem;"></i>
                            </div>
                            <span class="tc-component-card-name">PWM Breathing</span>
                            <span class="tc-starter-card-desc">Analog Duty & V_eff</span>
                        </div>

                        <!-- Starter 3: Potentiometer Divider -->
                        <div class="tc-component-card tc-starter-card" data-component-type="starter" data-preset="potentiometer" data-category="starters" style="display: none;" title="Click to load Potentiometer 10-Bit ADC project">
                            <span class="tc-starter-badge">Lab 03</span>
                            <div class="tc-component-card-icon" style="margin-top: 6px;">
                                <i class="fa-solid fa-sliders" style="color: #10b981; font-size: 1.3rem;"></i>
                            </div>
                            <span class="tc-component-card-name">Potentiometer ADC</span>
                            <span class="tc-starter-card-desc">10-Bit Voltage Divider</span>
                        </div>

                        <!-- Starter 4: Photoresistor (LDR) -->
                        <div class="tc-component-card tc-starter-card" data-component-type="starter" data-preset="ldr_sensor" data-category="starters" style="display: none;" title="Click to load LDR Solar Light Sensor project">
                            <span class="tc-starter-badge">Lab 04</span>
                            <div class="tc-component-card-icon" style="margin-top: 6px;">
                                <i class="fa-solid fa-sun" style="color: #f59e0b; font-size: 1.3rem;"></i>
                            </div>
                            <span class="tc-component-card-name">LDR Solar Sensor</span>
                            <span class="tc-starter-card-desc">Photoelectric Lux Telemetry</span>
                        </div>

                        <!-- Starter 5: Ultrasonic Rangefinder -->
                        <div class="tc-component-card tc-starter-card" data-component-type="starter" data-preset="ultrasonic" data-category="starters" style="display: none;" title="Click to load Ultrasonic HC-SR04 Rangefinder project">
                            <span class="tc-starter-badge">Lab 05</span>
                            <div class="tc-component-card-icon" style="margin-top: 6px;">
                                <i class="fa-solid fa-satellite-dish" style="color: #38bdf8; font-size: 1.3rem;"></i>
                            </div>
                            <span class="tc-component-card-name">Ultrasonic Range</span>
                            <span class="tc-starter-card-desc">Speed of Sound & Echo</span>
                        </div>

                        <!-- Starter 6: PIR Security Alarm -->
                        <div class="tc-component-card tc-starter-card" data-component-type="starter" data-preset="pir_alarm" data-category="starters" style="display: none;" title="Click to load PIR Motion Alarm project">
                            <span class="tc-starter-badge">Lab 06</span>
                            <div class="tc-component-card-icon" style="margin-top: 6px;">
                                <i class="fa-solid fa-shield-halved" style="color: #ec4899; font-size: 1.3rem;"></i>
                            </div>
                            <span class="tc-component-card-name">PIR Security Alarm</span>
                            <span class="tc-starter-card-desc">Pyroelectric & Siren Alert</span>
                        </div>

                        <!-- Starter 7: TMP36 Thermometer -->
                        <div class="tc-component-card tc-starter-card" data-component-type="starter" data-preset="tmp36_temp" data-category="starters" style="display: none;" title="Click to load TMP36 Thermometer project">
                            <span class="tc-starter-badge">Lab 07</span>
                            <div class="tc-component-card-icon" style="margin-top: 6px;">
                                <i class="fa-solid fa-temperature-half" style="color: #ef4444; font-size: 1.3rem;"></i>
                            </div>
                            <span class="tc-component-card-name">TMP36 Thermometer</span>
                            <span class="tc-starter-card-desc">Linear 10mV/°C Transducer</span>
                        </div>

                        <!-- Starter 8: Micro Servo Sweeper -->
                        <div class="tc-component-card tc-starter-card" data-component-type="starter" data-preset="servo_sweep" data-category="starters" style="display: none;" title="Click to load SG90 Micro Servo Sweeper project">
                            <span class="tc-starter-badge">Lab 08</span>
                            <div class="tc-component-card-icon" style="margin-top: 6px;">
                                <i class="fa-solid fa-gear" style="color: #8b5cf6; font-size: 1.3rem;"></i>
                            </div>
                            <span class="tc-component-card-name">Micro Servo SG90</span>
                            <span class="tc-starter-card-desc">0-180° Angular Kinematics</span>
                        </div>

                        <!-- Starter 9: RGB Color Mixer -->
                        <div class="tc-component-card tc-starter-card" data-component-type="starter" data-preset="rgb_mixer" data-category="starters" style="display: none;" title="Click to load RGB Spectrum Mixer project">
                            <span class="tc-starter-badge">Lab 09</span>
                            <div class="tc-component-card-icon" style="margin-top: 6px;">
                                <i class="fa-solid fa-palette" style="color: #10b981; font-size: 1.3rem;"></i>
                            </div>
                            <span class="tc-component-card-name">RGB Spectrum Mixer</span>
                            <span class="tc-starter-card-desc">Trichromatic PWM Color</span>
                        </div>

                        <!-- Starter 10: Pushbutton Pullup -->
                        <div class="tc-component-card tc-starter-card" data-component-type="starter" data-preset="button_toggle" data-category="starters" style="display: none;" title="Click to load Pushbutton Pullup project">
                            <span class="tc-starter-badge">Lab 10</span>
                            <div class="tc-component-card-icon" style="margin-top: 6px;">
                                <i class="fa-regular fa-circle-dot" style="color: #64748b; font-size: 1.3rem;"></i>
                            </div>
                            <span class="tc-component-card-name">Pushbutton Pullup</span>
                            <span class="tc-starter-card-desc">Active-LOW Debounced Logic</span>
                        </div>

                        <!-- Starter 11: RC Transient Curve -->
                        <div class="tc-component-card tc-starter-card" data-component-type="starter" data-preset="rc_transient" data-category="starters" style="display: none;" title="Click to load RC Transient Curve project">
                            <span class="tc-starter-badge">Lab 11</span>
                            <div class="tc-component-card-icon" style="margin-top: 6px;">
                                <i class="fa-solid fa-chart-line" style="color: #f59e0b; font-size: 1.3rem;"></i>
                            </div>
                            <span class="tc-component-card-name">RC Transient Curve</span>
                            <span class="tc-starter-card-desc">Charging & Decay τ = RC</span>
                        </div>

                        <!-- Starter 12: Optical Photogate -->
                        <div class="tc-component-card tc-starter-card" data-component-type="starter" data-preset="photogate" data-category="starters" style="display: none;" title="Click to load Optical Photogate project">
                            <span class="tc-starter-badge">Lab 12</span>
                            <div class="tc-component-card-icon" style="margin-top: 6px;">
                                <i class="fa-solid fa-stopwatch" style="color: #06b6d4; font-size: 1.3rem;"></i>
                            </div>
                            <span class="tc-component-card-name">Photogate Period 'g'</span>
                            <span class="tc-starter-card-desc">Hardware INT0 Microsecond Timing</span>
                        </div>

                    </div>
                </div>

            </div>

        </div>

        <!-- Bottom Drawer: Live Serial Monitor & Serial Plotter -->
        <div class="tc-bottom-serial-drawer">
            <div class="tc-serial-head">
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="tc-view-tab active" id="tcSerialTabMonitor" style="padding: 2px 8px; font-size: 0.75rem;">
                        <i class="fa-solid fa-terminal"></i> Serial Monitor
                    </button>
                    <button type="button" class="tc-view-tab" id="tcSerialTabPlotter" style="padding: 2px 8px; font-size: 0.75rem;">
                        <i class="fa-solid fa-chart-line"></i> Serial Plotter
                    </button>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <select id="tcBaudSelect" style="background: #0f172a; color: #94a3b8; border: 1px solid rgba(255,255,255,0.1); border-radius: 4px; font-size: 0.72rem; padding: 2px 6px;">
                        <option value="9600" selected>9600 baud</option>
                        <option value="19200">19200 baud</option>
                        <option value="38400">38400 baud</option>
                        <option value="57600">57600 baud</option>
                        <option value="115200">115200 baud</option>
                    </select>
                    <button type="button" id="tcClearSerialBtn" style="background: none; border: none; color: #94a3b8; font-size: 0.72rem; cursor: pointer;">
                        <i class="fa-solid fa-ban"></i> Clear
                    </button>
                </div>
            </div>

            <!-- Serial Stream Console -->
            <div class="tc-serial-stream" id="tcSerialStream">--- Serial Monitor Ready (9600 baud) ---
</div>

            <!-- Oscilloscope / Serial Plotter Canvas -->
            <canvas id="tcSerialPlotterCanvas" width="600" height="120" style="display: none; width: 100%; height: 120px; background: #020617;"></canvas>
        </div>

    </div>

    <!-- Curriculum Laboratory Experiments & Interfacing Guides -->
    <div style="max-width: 1000px; margin: 2rem auto 0 auto;">
        <h2 style="font-size: 1.85rem; margin-bottom: 1.5rem; text-align: center;">
            Physics Laboratory Interfacing Curriculum
        </h2>

        <!-- Module 1: Optical Photogate Timing -->
        <article class="glass-card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <span class="badge badge-cyan">Experiment 01</span>
                    <h3 style="font-size: 1.4rem; margin-top: 0.25rem; margin-bottom: 0.25rem;">Determination of 'g' via Optical Photogate Timing</h3>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.92rem;">Measure time period $T$ of a simple pendulum with microsecond accuracy using hardware interrupt Pin 2 (<code>INT0</code>).</p>
                </div>
                <button type="button" class="btn-modern btn-primary btn-sm tc-launch-project-btn" data-preset="photogate" style="font-size: 0.82rem; padding: 0.4rem 0.9rem; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-play"></i> Open in Circuit Simulator
                </button>
            </div>

            <div class="theory-card" style="margin-bottom: 1rem; padding: 1.25rem;">
                <h4 style="font-size: 1rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-atom" style="color: var(--accent);"></i> Governing Equations</h4>
                <div class="katex-display">
                    $$ T = 2\pi \sqrt{\frac{L}{g}} \implies g = 4\pi^2 \frac{L}{T^2} $$
                </div>
                <p style="margin: 0; font-size: 0.88rem; color: var(--text-muted);">
                    When the pendulum bob passes through the optical gate twice, the hardware interrupt timer registers period $\Delta t$ in microseconds and calculates local gravitational acceleration $g$.
                </p>
            </div>
        </article>

        <!-- Module 2: RC Transient Curve -->
        <article class="glass-card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <span class="badge badge-amber">Experiment 02</span>
                    <h3 style="font-size: 1.4rem; margin-top: 0.25rem; margin-bottom: 0.25rem;">RC Circuit Transient Charging & Discharging Curve</h3>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.92rem;">Capacitor voltage transient response through resistor $R = 10\text{ k}\Omega$, $C = 100\ \mu\text{F}$ ($\tau = 1.00\text{ s}$).</p>
                </div>
                <button type="button" class="btn-modern btn-primary btn-sm tc-launch-project-btn" data-preset="rc_transient" style="font-size: 0.82rem; padding: 0.4rem 0.9rem; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-play"></i> Open in Circuit Simulator
                </button>
            </div>

            <div class="theory-card" style="margin-bottom: 1rem; padding: 1.25rem;">
                <h4 style="font-size: 1rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-wave-square" style="color: var(--warning);"></i> Exponential Dynamics</h4>
                <div class="katex-display">
                    $$ V_C(t) = V_0 \left(1 - e^{-t / RC}\right) \quad (\text{Charging}), \quad V_C(t) = V_0 e^{-t / RC} \quad (\text{Discharging}) $$
                </div>
                <p style="margin: 0; font-size: 0.88rem; color: var(--text-muted);">
                    Digital Pin 10 drives square-wave charging cycles while Analog Pin A0 measures the capacitor voltage every 50ms, producing real-time exponential curves on the Serial Plotter.
                </p>
            </div>
        </article>

        <!-- Module 3: Ultrasonic Rangefinder -->
        <article class="glass-card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <span class="badge badge-cyan">Experiment 03</span>
                    <h3 style="font-size: 1.4rem; margin-top: 0.25rem; margin-bottom: 0.25rem;">Speed of Sound & Distance via HC-SR04 Ultrasonic Ping</h3>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.92rem;">Acoustic time-of-flight echo measurement using $40\text{ kHz}$ ultrasonic wave bursts.</p>
                </div>
                <button type="button" class="btn-modern btn-primary btn-sm tc-launch-project-btn" data-preset="ultrasonic" style="font-size: 0.82rem; padding: 0.4rem 0.9rem; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-play"></i> Open in Circuit Simulator
                </button>
            </div>

            <div class="theory-card" style="margin-bottom: 1rem; padding: 1.25rem;">
                <h4 style="font-size: 1rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-satellite-dish" style="color: #38bdf8;"></i> Acoustic Time-of-Flight</h4>
                <div class="katex-display">
                    $$ d = \frac{v_{\text{sound}} \cdot \Delta t}{2} \quad \text{where } v_{\text{sound}} \approx 343\text{ m/s} = 0.0343\text{ cm/}\mu\text{s} $$
                </div>
                <p style="margin: 0; font-size: 0.88rem; color: var(--text-muted);">
                    Trigger Pin 9 transmits a $10\,\mu\text{s}$ pulse. The echo duration returned on Echo Pin 8 measures obstacle distance with millimetric resolution.
                </p>
            </div>
        </article>

        <!-- Module 4: Potentiometer Voltage Divider -->
        <article class="glass-card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <span class="badge badge-emerald">Experiment 04</span>
                    <h3 style="font-size: 1.4rem; margin-top: 0.25rem; margin-bottom: 0.25rem;">Potentiometer Voltage Divider & 10-Bit ADC Telemetry</h3>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.92rem;">Verify Ohm's law and potential divider relation with 1024-step analog digitization.</p>
                </div>
                <button type="button" class="btn-modern btn-primary btn-sm tc-launch-project-btn" data-preset="potentiometer" style="font-size: 0.82rem; padding: 0.4rem 0.9rem; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-play"></i> Open in Circuit Simulator
                </button>
            </div>

            <div class="theory-card" style="margin-bottom: 1rem; padding: 1.25rem;">
                <h4 style="font-size: 1rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-sliders" style="color: #10b981;"></i> Linear Potential Gradient</h4>
                <div class="katex-display">
                    $$ V_{\text{out}} = V_{\text{in}} \cdot \frac{R_2}{R_1 + R_2}, \quad \text{ADC} = \left\lfloor \frac{V_{\text{out}}}{5.0\text{V}} \times 1023 \right\rfloor $$
                </div>
                <p style="margin: 0; font-size: 0.88rem; color: var(--text-muted);">
                    Turning the rotary wiper continuously varies the potential between $0\text{V}$ and $5\text{V}$, providing $4.88\text{ mV}$ precision per ADC step.
                </p>
            </div>
        </article>

        <!-- Module 5: LDR Light Sensor -->
        <article class="glass-card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <span class="badge badge-amber">Experiment 05</span>
                    <h3 style="font-size: 1.4rem; margin-top: 0.25rem; margin-bottom: 0.25rem;">Photoresistor (LDR) Solar Insolation & Light Sensing</h3>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.92rem;">Semiconductor photoconductivity in CdS photoresistors under variable ambient illumination.</p>
                </div>
                <button type="button" class="btn-modern btn-primary btn-sm tc-launch-project-btn" data-preset="ldr_sensor" style="font-size: 0.82rem; padding: 0.4rem 0.9rem; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-play"></i> Open in Circuit Simulator
                </button>
            </div>

            <div class="theory-card" style="margin-bottom: 1rem; padding: 1.25rem;">
                <h4 style="font-size: 1rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-sun" style="color: #f59e0b;"></i> Photoconductive Relation</h4>
                <div class="katex-display">
                    $$ R_{\text{LDR}} \propto E_{\text{lux}}^{-\gamma}, \quad V_{\text{out}} = 5.0\text{V} \cdot \frac{R_{\text{fixed}}}{R_{\text{LDR}} + R_{\text{fixed}}} $$
                </div>
                <p style="margin: 0; font-size: 0.88rem; color: var(--text-muted);">
                    Light photons create electron-hole pairs, reducing LDR resistance from $1\text{ M}\Omega$ (dark) to $500\ \Omega$ (bright light), detected on Analog Pin A1.
                </p>
            </div>
        </article>

        <!-- Module 6: Micro Servo Positioner -->
        <article class="glass-card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <span class="badge badge-purple">Experiment 06</span>
                    <h3 style="font-size: 1.4rem; margin-top: 0.25rem; margin-bottom: 0.25rem;">Micro Servo SG90 Kinematics & Angular Positioning</h3>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.92rem;">Closed-loop servo positioning mapped from potentiometer ADC across $0^\circ$ to $180^\circ$.</p>
                </div>
                <button type="button" class="btn-modern btn-primary btn-sm tc-launch-project-btn" data-preset="servo_sweep" style="font-size: 0.82rem; padding: 0.4rem 0.9rem; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-play"></i> Open in Circuit Simulator
                </button>
            </div>

            <div class="theory-card" style="margin-bottom: 1rem; padding: 1.25rem;">
                <h4 style="font-size: 1rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-gear" style="color: #8b5cf6;"></i> Pulse-Width Angular Encoding</h4>
                <div class="katex-display">
                    $$ \theta = \frac{\text{ADC}}{1023} \times 180^\circ, \quad \tau_{\text{pulse}} \in [1000\,\mu\text{s}, 2000\,\mu\text{s}] $$
                </div>
                <p style="margin: 0; font-size: 0.88rem; color: var(--text-muted);">
                    Arduino C++ `#include <Servo.h>` sends a $50\text{ Hz}$ PPM train with pulse widths proportional to the target angle, driving the motor horn in real time.
                </p>
            </div>
        </article>
    </div>

</main>

<!-- Tinkercad Simulator JavaScript Engine -->
<script src="<?php echo $siteurl; ?>assets/js/arduino_simulator.js?v=<?php echo file_exists(__DIR__ . '/assets/js/arduino_simulator.js') ? filemtime(__DIR__ . '/assets/js/arduino_simulator.js') : time(); ?>"></script>

<?php require_once __DIR__ . '/include/footer.php'; ?>
