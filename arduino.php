<?php
/**
 * Python4Physics - Tinkercad Circuits Style Interactive Arduino Laboratory
 * Complete Virtual Electronics Workbench with Live Firmware Execution, Breadboard,
 * Dynamic SVG Wires, Serial Monitor, Serial Plotter & Physics DAQ Modules.
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';

$page_title = "Arduino in the Physics Laboratory - Tinkercad Style Virtual Circuit Simulator";
$page_description = "Interactive Tinkercad Circuits style virtual Arduino laboratory for computational physics: simulate breadboard circuits, live C++ firmware, Serial Monitor, and DAQ interfacing.";

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

<main class="container-fluid" style="padding-top: 2rem; padding-bottom: 5rem; max-width: 1440px; margin: 0 auto; box-sizing: border-box; overflow-x: hidden;">
    
    <!-- Hero Header -->
    <div style="text-align: center; max-width: 900px; margin: 0 auto 2.5rem auto;">
        <span class="badge badge-cyan" style="font-size: 0.85rem; padding: 4px 12px; margin-bottom: 0.5rem; display: inline-block;">
            <i class="fa-solid fa-microchip"></i> Interactive Circuit Simulator
        </span>
        <h1 style="font-size: 2.6rem; margin-top: 0.5rem; margin-bottom: 0.75rem;">
            Arduino <span class="gradient-text">Virtual Circuits Lab</span>
        </h1>
        <p style="font-size: 1.1rem; line-height: 1.6; color: var(--text-muted); margin: 0;">
            A full Tinkercad Circuits-style interactive electronics workbench. Wire up an Arduino Uno R3, test optical and sensor circuits on a breadboard, write C++ firmware, and monitor live serial streams and oscilloscope plots.
        </p>
    </div>

    <!-- Tinkercad Circuits Virtual Workbench Studio -->
    <div class="tinkercad-studio">
        <!-- Top Toolbar -->
        <div class="tc-toolbar">
            <div class="tc-title-group">
                <span class="tc-badge" id="tcPresetBadge">Lab 01: Digital I/O</span>
                <h3 class="tc-circuit-title" id="tcPresetTitle">LED Blink & Optical Timing</h3>
            </div>

            <!-- Lab Preset Selector -->
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <label style="font-size: 0.82rem; color: #94a3b8; font-weight: 600;">CIRCUIT PRESET:</label>
                <select id="tcPresetSelector" class="btn-modern btn-secondary btn-sm" style="font-size: 0.85rem; padding: 0.4rem 0.8rem; background: #0f172a; color: #f8fafc; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px;">
                    <option value="blink">1. LED Blink & Digital Output</option>
                    <option value="pwm_fade">2. PWM Breathing & Average DC Voltage</option>
                    <option value="potentiometer">3. Potentiometer 10-Bit ADC Voltage Divider</option>
                    <option value="ldr_sensor">4. Photoresistor (LDR) Solar Light Sensor</option>
                    <option value="ultrasonic">5. Ultrasonic HC-SR04 Speed of Sound Rangefinder</option>
                </select>
            </div>

            <!-- Wire Palette & Tools (Tinkercad Style) -->
            <div class="tc-wire-bar">
                <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Wire Color:</span>
                <div class="color-swatch active" data-color="#10b981" style="background: #10b981;" title="Green (Signal)"></div>
                <div class="color-swatch" data-color="#ef4444" style="background: #ef4444;" title="Red (Power 5V)"></div>
                <div class="color-swatch" data-color="#0f172a" style="background: #0f172a; border-color: #64748b;" title="Black (GND)"></div>
                <div class="color-swatch" data-color="#0284c7" style="background: #0284c7;" title="Blue (PWM/Analog)"></div>
                <div class="color-swatch" data-color="#eab308" style="background: #eab308;" title="Yellow (Clock/Bus)"></div>
                <div class="color-swatch" data-color="#f97316" style="background: #f97316;" title="Orange"></div>
                <div style="height: 18px; width: 1px; background: rgba(255,255,255,0.15); margin: 0 4px;"></div>
                <button type="button" class="btn-tc" id="tcAutoWireBtn" style="padding: 3px 8px; font-size: 0.78rem; background: #0284c7; color: #fff;" title="Auto-connect required wires">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Wire
                </button>
                <button type="button" class="btn-tc" id="tcClearWiresBtn" style="padding: 3px 8px; font-size: 0.78rem; background: #334155; color: #e2e8f0;" title="Remove all wires to wire manually">
                    <i class="fa-solid fa-rotate-left"></i> Reset Wires
                </button>
                <button type="button" class="btn-tc" id="tcDeleteWireBtn" style="padding: 3px 8px; font-size: 0.78rem; background: #dc2626; color: #fff; display: none;" title="Delete Selected Wire (Delete Key)">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>

            <!-- Simulation Action Buttons -->
            <div class="tc-controls">
                <div class="tc-timer" id="tcSimTimer" title="Simulation Elapsed Time">00:00.000</div>
                <button type="button" class="btn-tc btn-tc-simulate" id="tcStartSimBtn">
                    <i class="fa-solid fa-play"></i> Start Simulation
                </button>
                <button type="button" class="btn-tc btn-tc-code active" id="tcToggleCodeBtn">
                    <i class="fa-solid fa-code"></i> Code
                </button>
            </div>
        </div>

        <!-- Studio Workspace (Split View: Circuit Canvas + Code & Serial Pane) -->
        <div class="tc-workspace">
            
            <!-- Left: Virtual Circuit Canvas -->
            <div class="tc-canvas-pane" id="tcCanvasPane">
                
                <div class="circuit-stage" id="tcCircuitStage">
                    <!-- SVG Wires Layer -->
                    <svg class="wires-layer" id="tcWiresLayer"></svg>

                    <!-- Realistic Arduino Uno R3 Board -->
                    <div class="arduino-board" id="arduinoUnoBoard">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 6px; margin-bottom: 8px;">
                            <span style="font-weight: 800; font-size: 0.95rem; letter-spacing: 0.05em;"><i class="fa-solid fa-atom"></i> ARDUINO</span>
                            <span style="font-size: 0.75rem; background: #075985; padding: 2px 6px; border-radius: 3px; font-weight: 700;">UNO R3</span>
                        </div>

                        <!-- USB Connector & DC Jack -->
                        <div style="position: absolute; top: 12px; left: -14px; width: 36px; height: 32px; background: #94a3b8; border-radius: 3px; border: 2px solid #64748b; box-shadow: -2px 4px 10px rgba(0,0,0,0.5);"></div>
                        <div style="position: absolute; bottom: 20px; left: -12px; width: 44px; height: 38px; background: #1e293b; border-radius: 4px; border: 2px solid #0f172a;"></div>

                        <!-- Microcontroller Chip (ATmega328P DIP) -->
                        <div style="position: absolute; top: 150px; left: 80px; width: 140px; height: 36px; background: #090d16; border-radius: 4px; border: 1px solid #334155; display: flex; align-items: center; justify-content: center; font-family: monospace; font-size: 0.72rem; color: #94a3b8; letter-spacing: 1px;">
                            ATmega328P-PU
                        </div>

                        <!-- Onboard Power LED (ON) & Pin 13 LED (L) -->
                        <div style="position: absolute; top: 60px; right: 50px; display: flex; flex-direction: column; gap: 8px; font-size: 0.65rem; color: #cbd5e1; font-weight: 700;">
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <div id="arduinoOnLed" style="width: 8px; height: 8px; border-radius: 50%; background: #334155; transition: all 0.2s;"></div>
                                <span>ON</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <div id="arduinoBuiltinLed" style="width: 8px; height: 8px; border-radius: 50%; background: #334155; transition: all 0.2s;"></div>
                                <span>L (13)</span>
                            </div>
                        </div>

                        <!-- Digital Pin Headers (Right Side) -->
                        <div style="position: absolute; top: 55px; right: 4px; width: 22px; height: 180px; background: #0f172a; border-radius: 3px; display: flex; flex-direction: column; justify-content: space-around; align-items: center; padding: 2px 0;">
                            <div style="width: 8px; height: 8px; background: #334155; border-radius: 1px;" title="D13 (SCK)"></div>
                            <div style="width: 8px; height: 8px; background: #334155; border-radius: 1px;" title="D12 (MISO)"></div>
                            <div style="width: 8px; height: 8px; background: #334155; border-radius: 1px;" title="D11 (MOSI)"></div>
                            <div style="width: 8px; height: 8px; background: #334155; border-radius: 1px;" title="D10 (SS)"></div>
                            <div style="width: 8px; height: 8px; background: #334155; border-radius: 1px;" title="D9 (PWM)"></div>
                            <div style="width: 8px; height: 8px; background: #334155; border-radius: 1px;" title="D8"></div>
                            <div style="width: 8px; height: 8px; background: #334155; border-radius: 1px;" title="GND"></div>
                        </div>
                        <div style="position: absolute; top: 58px; right: 30px; font-size: 0.65rem; color: #e2e8f0; font-family: monospace; line-height: 25px; text-align: right;">
                            13<br>12<br>11<br>10<br>9~<br>8<br>GND
                        </div>

                        <!-- Power & Analog Headers (Bottom Right) -->
                        <div style="position: absolute; bottom: 40px; right: 4px; width: 22px; height: 160px; background: #0f172a; border-radius: 3px; display: flex; flex-direction: column; justify-content: space-around; align-items: center; padding: 2px 0;">
                            <div style="width: 8px; height: 8px; background: #334155; border-radius: 1px;" title="A0"></div>
                            <div style="width: 8px; height: 8px; background: #334155; border-radius: 1px;" title="A1"></div>
                            <div style="width: 8px; height: 8px; background: #334155; border-radius: 1px;" title="A2"></div>
                            <div style="width: 8px; height: 8px; background: #334155; border-radius: 1px;" title="5V"></div>
                            <div style="width: 8px; height: 8px; background: #334155; border-radius: 1px;" title="GND"></div>
                        </div>
                        <div style="position: absolute; bottom: 42px; right: 30px; font-size: 0.65rem; color: #e2e8f0; font-family: monospace; line-height: 26px; text-align: right;">
                            A0<br>A1<br>A2<br>5V<br>GND
                        </div>

                        <!-- Reset Switch -->
                        <div style="position: absolute; top: 60px; left: 60px; width: 14px; height: 14px; border-radius: 50%; background: #dc2626; border: 2px solid #f87171; cursor: pointer;" title="Reset Button"></div>
                    </div>

                    <!-- Realistic Half-Size Breadboard -->
                    <div class="breadboard" id="breadboardStudio">
                        <!-- Top Power Bus (+ Red, - Blue) -->
                        <div style="border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 800; font-size: 0.75rem; color: #dc2626;">+</span>
                            <div style="display: flex; gap: 8px; flex: 1; margin: 0 10px; justify-content: space-between;">
                                <?php for($i=1; $i<=20; $i++): ?>
                                    <div style="width: 5px; height: 5px; border-radius: 50%; background: #94a3b8;"></div>
                                <?php endfor; ?>
                            </div>
                            <span style="font-weight: 800; font-size: 0.75rem; color: #0284c7;">-</span>
                        </div>

                        <!-- Terminal Rows (Columns a-e, Divider, f-j) -->
                        <div style="flex: 1; padding: 12px 4px; display: flex; flex-direction: column; justify-content: space-around; position: relative;">
                            
                            <!-- Dynamic Plug-in Component 1: 5mm LED -->
                            <div id="bbLedRed" style="position: absolute; top: 85px; left: 85px; z-index: 6; text-align: center;">
                                <div id="bbLedRedCore" style="width: 22px; height: 22px; border-radius: 50%; background: #b91c1c; border: 2px solid #7f1d1d; opacity: 0.3; transition: all 0.15s ease; margin: 0 auto;"></div>
                                <span style="font-size: 0.65rem; color: #475569; font-weight: 700; display: block; margin-top: 2px;">LED</span>
                            </div>

                            <!-- Dynamic Plug-in Component 2: 10k Rotary Potentiometer -->
                            <div id="bbPotentiometer" style="position: absolute; top: 180px; left: 60px; z-index: 6; display: none; text-align: center;">
                                <div style="width: 44px; height: 44px; border-radius: 50%; background: #334155; border: 3px solid #0284c7; box-shadow: 0 4px 10px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                    <div style="width: 4px; height: 16px; background: #38bdf8; border-radius: 2px;"></div>
                                </div>
                                <span style="font-size: 0.68rem; color: #0284c7; font-weight: 700; display: block; margin-top: 4px;">10kΩ POT</span>
                            </div>

                            <!-- Dynamic Plug-in Component 3: LDR Photoresistor -->
                            <div id="bbLdrSensor" style="position: absolute; top: 180px; left: 100px; z-index: 6; display: none; text-align: center;">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: #ca8a04; border: 2px solid #a16207; display: flex; align-items: center; justify-content: center; margin: 0 auto; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
                                    <i class="fa-solid fa-sun" style="color: #fef08a; font-size: 0.8rem;"></i>
                                </div>
                                <span style="font-size: 0.68rem; color: #a16207; font-weight: 700; display: block; margin-top: 4px;">LDR Sensor</span>
                            </div>

                            <!-- Dynamic Plug-in Component 4: HC-SR04 Ultrasonic Sensor -->
                            <div id="bbUltrasonic" style="position: absolute; top: 170px; left: 30px; z-index: 6; display: none; text-align: center;">
                                <div style="width: 110px; height: 44px; background: #0284c7; border-radius: 8px; border: 2px solid #0369a1; display: flex; justify-content: space-around; align-items: center; padding: 4px; box-shadow: 0 6px 15px rgba(0,0,0,0.3);">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #94a3b8; border: 2px solid #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; color: #0f172a; font-weight: 800;">T</div>
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #94a3b8; border: 2px solid #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; color: #0f172a; font-weight: 800;">R</div>
                                </div>
                                <span style="font-size: 0.68rem; color: #0369a1; font-weight: 700; display: block; margin-top: 4px;">HC-SR04 Ultrasonic</span>
                            </div>

                            <!-- Grid of Breadboard Holes -->
                            <?php for($row=1; $row<=8; $row++): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <?php for($col=1; $col<=20; $col++): ?>
                                        <div style="width: 6px; height: 6px; border-radius: 1px; background: #cbd5e1; border: 1px solid #94a3b8;"></div>
                                    <?php endfor; ?>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <!-- Bottom Power Bus -->
                        <div style="border-top: 2px solid #e2e8f0; padding-top: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 800; font-size: 0.75rem; color: #dc2626;">+</span>
                            <div style="display: flex; gap: 8px; flex: 1; margin: 0 10px; justify-content: space-between;">
                                <?php for($i=1; $i<=20; $i++): ?>
                                    <div style="width: 5px; height: 5px; border-radius: 50%; background: #94a3b8;"></div>
                                <?php endfor; ?>
                            </div>
                            <span style="font-weight: 800; font-size: 0.75rem; color: #0284c7;">-</span>
                        </div>
                    </div>

                    <!-- Floating Interactive Physical Slider (Potentiometer / Lux / Distance) -->
                    <div class="interactive-overlay">
                        <div class="interactive-control">
                            <i class="fa-solid fa-sliders" style="color: var(--accent);"></i>
                            <span id="tcSliderLabel" style="font-weight: 600;">Hardware Input:</span>
                            <input type="range" id="tcInteractiveSlider" class="interactive-slider" min="0" max="1023" value="512">
                            <span id="tcSliderValueDisplay" style="font-family: monospace; font-weight: 700; color: #38bdf8; min-width: 65px;">512 ADC</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right: Code Editor & Serial Monitor Drawer -->
            <div class="tc-editor-pane" id="tcEditorPane">
                
                <!-- Code Header -->
                <div class="tc-editor-header">
                    <span><i class="fa-brands fa-cuttlefish" style="color: #38bdf8;"></i> Arduino C++ Sketch (.ino)</span>
                    <div style="display: flex; gap: 6px;">
                        <button type="button" class="btn-tc btn-tc-code" style="padding: 2px 8px; font-size: 0.75rem;" onclick="navigator.clipboard.writeText(document.getElementById('tcCodeTextarea').value); alert('Sketch copied to clipboard!');">
                            <i class="fa-regular fa-copy"></i> Copy
                        </button>
                    </div>
                </div>

                <!-- Code Editor Area -->
                <div class="tc-codemirror-wrapper">
                    <textarea id="tcCodeTextarea"></textarea>
                </div>

                <!-- Bottom Drawer: Serial Monitor & Serial Plotter -->
                <div class="tc-serial-drawer">
                    <div class="tc-serial-header">
                        <div class="tc-serial-tabs">
                            <button type="button" class="tc-serial-tab active" id="tcTabMonitor">
                                <i class="fa-solid fa-terminal"></i> Serial Monitor
                            </button>
                            <button type="button" class="tc-serial-tab" id="tcTabPlotter">
                                <i class="fa-solid fa-chart-line"></i> Serial Plotter
                            </button>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 0.72rem; color: #64748b;">9600 baud</span>
                            <button type="button" id="tcClearSerialBtn" style="background: none; border: none; color: #94a3b8; font-size: 0.72rem; cursor: pointer;" title="Clear Output">
                                <i class="fa-solid fa-ban"></i> Clear
                            </button>
                        </div>
                    </div>

                    <!-- Serial Text Stream -->
                    <div class="tc-serial-content" id="tcSerialOutput">--- Serial Monitor Ready (9600 baud) ---
</div>

                    <!-- Serial Waveform Plotter Canvas -->
                    <canvas id="tcPlotterCanvas" width="460" height="140" style="display: none; width: 100%; height: 140px; background: #020617;"></canvas>
                </div>

            </div>

        </div>
    </div>

    <!-- Description & Physics Laboratory Modules -->
    <div style="max-width: 1000px; margin: 0 auto;">
        <h2 style="font-size: 1.85rem; margin-bottom: 1.5rem; text-align: center;">
            Curriculum Laboratory Experiments & Interfacing Guides
        </h2>

        <!-- Module 1: Photogate -->
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
