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
<link rel="stylesheet" href="<?php echo $siteurl; ?>assets/css/tinkercad.css">
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
                    <option value="ultrasonic">5. Ultrasonic HC-SR04 Speed of Sound</option>
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
                <button type="button" class="btn-tc-code-toggle" id="tcToggleCodeBtn">
                    <i class="fa-solid fa-code"></i> Code
                </button>

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
                    <div class="tc-arduino-uno" id="arduinoUno" style="top: 80px; left: 40px;">
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

                    <!-- Photorealistic Half-Size Solderless Breadboard -->
                    <div class="tc-breadboard" id="breadboardSmall" style="top: 100px; left: 450px;">
                        
                        <!-- Top Power Bus (+) Red & (-) Blue -->
                        <div class="tc-bb-power-rail">
                            <!-- Positive Rail (+) -->
                            <div class="tc-bb-rail-row">
                                <span class="tc-bb-sign-plus">+</span>
                                <div class="tc-bb-holes-group">
                                    <?php for($i=1; $i<=30; $i++): ?>
                                        <div class="tc-bb-hole" id="bb-top-pos-<?php echo $i; ?>" title="Top Power Rail (+) Column <?php echo $i; ?>"></div>
                                    <?php endfor; ?>
                                </div>
                                <span class="tc-bb-sign-plus">+</span>
                            </div>
                            <!-- Negative Rail (-) -->
                            <div class="tc-bb-rail-row">
                                <span class="tc-bb-sign-minus">-</span>
                                <div class="tc-bb-holes-group">
                                    <?php for($i=1; $i<=30; $i++): ?>
                                        <div class="tc-bb-hole" id="bb-top-neg-<?php echo $i; ?>" title="Top Ground Rail (-) Column <?php echo $i; ?>"></div>
                                    <?php endfor; ?>
                                </div>
                                <span class="tc-bb-sign-minus">-</span>
                            </div>
                        </div>

                        <!-- Center Terminal Grid: Columns 1 to 30, Rows a to e, Trough, Rows f to j -->
                        <div class="tc-bb-center-grid">
                            <!-- Upper Bank: Rows a to e -->
                            <?php foreach(['a', 'b', 'c', 'd', 'e'] as $rowLetter): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <span style="font-size: 0.65rem; color: #64748b; font-weight: 700; width: 12px; text-align: center;"><?php echo $rowLetter; ?></span>
                                    <div class="tc-bb-holes-group">
                                        <?php for($col=1; $col<=30; $col++): ?>
                                            <div class="tc-bb-hole" id="bb-<?php echo $rowLetter . $col; ?>" title="Breadboard Terminal <?php echo strtoupper($rowLetter) . $col; ?>"></div>
                                        <?php endfor; ?>
                                    </div>
                                    <span style="font-size: 0.65rem; color: #64748b; font-weight: 700; width: 12px; text-align: center;"><?php echo $rowLetter; ?></span>
                                </div>
                            <?php endforeach; ?>

                            <!-- Center IC Isolation Trough / Ravine -->
                            <div class="tc-bb-trough"></div>

                            <!-- Lower Bank: Rows f to j -->
                            <?php foreach(['f', 'g', 'h', 'i', 'j'] as $rowLetter): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <span style="font-size: 0.65rem; color: #64748b; font-weight: 700; width: 12px; text-align: center;"><?php echo $rowLetter; ?></span>
                                    <div class="tc-bb-holes-group">
                                        <?php for($col=1; $col<=30; $col++): ?>
                                            <div class="tc-bb-hole" id="bb-<?php echo $rowLetter . $col; ?>" title="Breadboard Terminal <?php echo strtoupper($rowLetter) . $col; ?>"></div>
                                        <?php endfor; ?>
                                    </div>
                                    <span style="font-size: 0.65rem; color: #64748b; font-weight: 700; width: 12px; text-align: center;"><?php echo $rowLetter; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Bottom Power Bus (+) Red & (-) Blue -->
                        <div class="tc-bb-power-rail">
                            <!-- Positive Rail (+) -->
                            <div class="tc-bb-rail-row">
                                <span class="tc-bb-sign-plus">+</span>
                                <div class="tc-bb-holes-group">
                                    <?php for($i=1; $i<=30; $i++): ?>
                                        <div class="tc-bb-hole" id="bb-bot-pos-<?php echo $i; ?>" title="Bottom Power Rail (+) Column <?php echo $i; ?>"></div>
                                    <?php endfor; ?>
                                </div>
                                <span class="tc-bb-sign-plus">+</span>
                            </div>
                            <!-- Negative Rail (-) -->
                            <div class="tc-bb-rail-row">
                                <span class="tc-bb-sign-minus">-</span>
                                <div class="tc-bb-holes-group">
                                    <?php for($i=1; $i<=30; $i++): ?>
                                        <div class="tc-bb-hole" id="bb-bot-neg-<?php echo $i; ?>" title="Bottom Ground Rail (-) Column <?php echo $i; ?>"></div>
                                    <?php endfor; ?>
                                </div>
                                <span class="tc-bb-sign-minus">-</span>
                            </div>
                        </div>

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

            </div>

            <!-- Right-Hand Drawer: Components Palette OR Code Editor -->
            <div class="tc-right-drawer" id="tcRightDrawer">
                
                <!-- Drawer 1: Components Basic Palette (as in Tinkercad screenshot) -->
                <div id="tcComponentsPalettePane" style="display: flex; flex-direction: column; height: 100%;">
                    
                    <div class="tc-drawer-header">
                        <select class="tc-comp-category-select" id="tcCompCategory">
                            <option value="basic">Components: Basic</option>
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
                        <div class="tc-component-card" data-component-type="resistor" title="Click or Drag onto breadboard">
                            <div class="tc-component-card-icon">
                                <svg width="44" height="20" viewBox="0 0 44 20">
                                    <line x1="2" y1="10" x2="12" y2="10" stroke="#94a3b8" stroke-width="2"/>
                                    <rect x="12" y="4" width="20" height="12" rx="4" fill="#fcd34d" stroke="#d97706" stroke-width="1"/>
                                    <!-- Color bands: Red, Red, Brown, Gold -->
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
                        <div class="tc-component-card" data-component-type="led" title="Click or Drag onto breadboard">
                            <div class="tc-component-card-icon">
                                <svg width="34" height="40" viewBox="0 0 34 40">
                                    <!-- Anode & Cathode leads -->
                                    <line x1="12" y1="24" x2="12" y2="38" stroke="#94a3b8" stroke-width="2"/>
                                    <path d="M 22 24 L 22 30 L 20 33 L 20 38" fill="none" stroke="#94a3b8" stroke-width="2"/>
                                    <!-- LED Dome (Red by default) -->
                                    <path d="M 8 22 A 9 9 0 0 1 26 22 L 26 24 L 8 24 Z" fill="#ef4444" stroke="#b91c1c" stroke-width="1.5"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">LED</span>
                        </div>

                        <!-- Pushbutton -->
                        <div class="tc-component-card" data-component-type="pushbutton" title="Tactile momentary pushbutton">
                            <div class="tc-component-card-icon">
                                <svg width="36" height="36" viewBox="0 0 36 36">
                                    <rect x="6" y="6" width="24" height="24" rx="3" fill="#cbd5e1" stroke="#64748b" stroke-width="1.5"/>
                                    <circle cx="18" cy="18" r="7" fill="#0f172a"/>
                                    <!-- 4 pins -->
                                    <line x1="2" y1="10" x2="6" y2="10" stroke="#64748b" stroke-width="2"/>
                                    <line x1="2" y1="26" x2="6" y2="26" stroke="#64748b" stroke-width="2"/>
                                    <line x1="30" y1="10" x2="34" y2="10" stroke="#64748b" stroke-width="2"/>
                                    <line x1="30" y1="26" x2="34" y2="26" stroke="#64748b" stroke-width="2"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Pushbutton</span>
                        </div>

                        <!-- Potentiometer -->
                        <div class="tc-component-card" data-component-type="potentiometer" title="10k Rotary Potentiometer">
                            <div class="tc-component-card-icon">
                                <svg width="36" height="38" viewBox="0 0 36 38">
                                    <circle cx="18" cy="16" r="14" fill="#0284c7" stroke="#0369a1" stroke-width="1.5"/>
                                    <circle cx="18" cy="16" r="8" fill="#334155"/>
                                    <line x1="18" y1="16" x2="18" y2="10" stroke="#38bdf8" stroke-width="2"/>
                                    <!-- 3 bottom legs -->
                                    <line x1="10" y1="30" x2="10" y2="36" stroke="#94a3b8" stroke-width="2"/>
                                    <line x1="18" y1="30" x2="18" y2="36" stroke="#94a3b8" stroke-width="2"/>
                                    <line x1="26" y1="30" x2="26" y2="36" stroke="#94a3b8" stroke-width="2"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Potentiometer</span>
                        </div>

                        <!-- Photoresistor (LDR) -->
                        <div class="tc-component-card" data-component-type="ldr" title="Light-Dependent Resistor">
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
                        <div class="tc-component-card" data-component-type="ultrasonic" title="HC-SR04 Ultrasonic Distance Sensor">
                            <div class="tc-component-card-icon">
                                <svg width="50" height="28" viewBox="0 0 50 28">
                                    <rect x="2" y="2" width="46" height="24" rx="4" fill="#0284c7" stroke="#0369a1" stroke-width="1"/>
                                    <circle cx="14" cy="14" r="8" fill="#e2e8f0" stroke="#64748b" stroke-width="1"/>
                                    <circle cx="36" cy="14" r="8" fill="#e2e8f0" stroke="#64748b" stroke-width="1"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Ultrasonic Distance</span>
                        </div>

                        <!-- Breadboard Small -->
                        <div class="tc-component-card" data-component-type="breadboard" title="Solderless Breadboard">
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
                        <div class="tc-component-card" data-component-type="arduino" title="Arduino Uno R3 Microcontroller">
                            <div class="tc-component-card-icon">
                                <svg width="46" height="34" viewBox="0 0 46 34">
                                    <rect x="2" y="2" width="42" height="30" rx="3" fill="#00878a" stroke="#004d50" stroke-width="1"/>
                                    <rect x="2" y="6" width="6" height="8" fill="#94a3b8"/>
                                    <rect x="18" y="16" width="18" height="6" fill="#0f172a"/>
                                </svg>
                            </div>
                            <span class="tc-component-card-name">Arduino Uno R3</span>
                        </div>

                    </div>
                </div>

                <!-- Drawer 2: Code Editor Pane (shown when Code toggle is active) -->
                <div class="tc-code-pane" id="tcCodePane" style="display: none;">
                    <div class="tc-code-header">
                        <span><i class="fa-brands fa-cuttlefish" style="color: #38bdf8;"></i> Arduino C++ Sketch (.ino)</span>
                        <button type="button" class="btn-tc-code-toggle" style="padding: 2px 8px; font-size: 0.75rem;" onclick="navigator.clipboard.writeText(window.tcCodeEditor ? window.tcCodeEditor.getValue() : ''); alert('Sketch copied to clipboard!');">
                            <i class="fa-regular fa-copy"></i> Copy
                        </button>
                    </div>
                    <div class="tc-codemirror-holder">
                        <textarea id="tcCodeTextarea"></textarea>
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
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 0.72rem; color: #64748b;">9600 baud</span>
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
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
                <div>
                    <span class="badge badge-cyan">Experiment 01</span>
                    <h3 style="font-size: 1.4rem; margin-top: 0.25rem; margin-bottom: 0.25rem;">Determination of 'g' via Optical Photogate Timing</h3>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.92rem;">Measure time period $T$ of a simple pendulum with microsecond accuracy using hardware interrupt Pin 2 (<code>INT0</code>).</p>
                </div>
            </div>

            <div class="theory-card" style="margin-bottom: 1rem; padding: 1.25rem;">
                <h4 style="font-size: 1rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-atom" style="color: var(--accent);"></i> Governing Equations</h4>
                <div class="katex-display">
                    $$ T = 2\pi \sqrt{\frac{L}{g}} \implies g = 4\pi^2 \frac{L}{T^2} $$
                </div>
                <p style="margin: 0; font-size: 0.88rem; color: var(--text-muted);">
                    When the bob breaks the infrared optical beam twice, a timer calculates the period $\Delta t$. Python reads the serial stream via <code>pyserial</code> for automated data regression.
                </p>
            </div>
        </article>

        <!-- Module 2: RC Transient Curve -->
        <article class="glass-card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
                <div>
                    <span class="badge badge-amber">Experiment 02</span>
                    <h3 style="font-size: 1.4rem; margin-top: 0.25rem; margin-bottom: 0.25rem;">RC Circuit Transient Charging & Discharging Curve</h3>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.92rem;">Capacitor voltage transient response through resistor $R = 10\text{ k}\Omega$, $C = 100\ \mu\text{F}$.</p>
                </div>
            </div>

            <div class="theory-card" style="margin-bottom: 1rem; padding: 1.25rem;">
                <h4 style="font-size: 1rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-wave-square" style="color: var(--warning);"></i> Exponential Dynamics</h4>
                <div class="katex-display">
                    $$ V_C(t) = V_0 \left(1 - e^{-t / RC}\right) \quad (\text{Charging}), \quad V_C(t) = V_0 e^{-t / RC} \quad (\text{Discharging}) $$
                </div>
                <p style="margin: 0; font-size: 0.88rem; color: var(--text-muted);">
                    Digital Pin 10 supplies charging pulses while Analog Pin A0 measures the capacitor voltage every 5 milliseconds to trace the exponential response curve.
                </p>
            </div>
        </article>
    </div>

</main>

<!-- Tinkercad Simulator JavaScript Engine -->
<script src="<?php echo $siteurl; ?>assets/js/arduino_simulator.js"></script>

<?php require_once __DIR__ . '/include/footer.php'; ?>
