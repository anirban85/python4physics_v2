/**
 * Python4Physics - Advanced Tinkercad Circuits Simulator
 * Fully Interactive Wiring: Click Pin to Start Wire -> Move Cursor -> Click Pin to Complete.
 * Wire Color Selection, Wire Deletion, Pin Snapping, Circuit Validation,
 * Live C++ Firmware Execution, Serial Monitor & Oscilloscope Plotter.
 */

(function () {
    'use strict';

    // State
    let isSimulating = false;
    let simStartTime = 0;
    let simInterval = null;
    let serialBuffer = [];
    let plotterHistory = [];
    let codeEditor = null;
    let currentPreset = 'blink';

    // Wiring State (Tinkercad-style live wire drawing)
    let selectedWireColor = '#10b981'; // Green by default
    let activeWireStartPin = null;     // Pin currently drawing from
    let userWires = [];                // Array of { id, from, to, color }
    let selectedWireId = null;         // Selected wire for deletion

    // Component Values (Interactive inputs)
    const inputs = {
        potentiometer: 512, // 0 - 1023
        ldr_lux: 400,       // 0 - 1000 lux
        ultrasonic_cm: 25,  // 2 - 400 cm
        blink_delay: 800
    };

    // Hardware Pin Terminal Positions (stage relative coordinates)
    const pinMap = {
        // Arduino Digital Pins
        'pin-13':    { x: 330, y: 92,  name: 'Digital 13 (SCK)' },
        'pin-12':    { x: 330, y: 118, name: 'Digital 12 (MISO)' },
        'pin-11':    { x: 330, y: 144, name: 'Digital 11 (PWM)' },
        'pin-10':    { x: 330, y: 170, name: 'Digital 10 (PWM)' },
        'pin-9':     { x: 330, y: 196, name: 'Digital 9 (PWM)' },
        'pin-8':     { x: 330, y: 222, name: 'Digital 8' },
        'pin-gnd-1': { x: 330, y: 248, name: 'GND (Digital Ground)' },

        // Arduino Analog & Power Pins
        'pin-a0':    { x: 330, y: 335, name: 'Analog In A0' },
        'pin-a1':    { x: 330, y: 365, name: 'Analog In A1' },
        'pin-a2':    { x: 330, y: 395, name: 'Analog In A2' },
        'pin-5v':    { x: 330, y: 425, name: 'Power 5V' },
        'pin-gnd-2': { x: 330, y: 455, name: 'GND (Power Ground)' },

        // Breadboard Points
        'bb-rail-pos': { x: 420, y: 55,  name: 'Breadboard (+) Positive Rail' },
        'bb-rail-neg': { x: 420, y: 455, name: 'Breadboard (-) Ground Rail' },

        // Component Terminals on Breadboard
        'bb-f15': { x: 520, y: 175, name: 'LED Anode (Long Leg +)' },
        'bb-e16': { x: 535, y: 235, name: 'LED Cathode (Short Leg -)' },
        'bb-e17': { x: 550, y: 235, name: 'Resistor 220Ω (Leg A)' },
        'bb-j17': { x: 550, y: 350, name: 'Resistor 220Ω (Leg B)' },

        // Potentiometer Terminals
        'bb-j8':  { x: 480, y: 350, name: 'Potentiometer Leg 1 (5V)' },
        'bb-j9':  { x: 495, y: 350, name: 'Potentiometer Wiper (Output)' },
        'bb-j10': { x: 510, y: 350, name: 'Potentiometer Leg 3 (GND)' },

        // LDR Terminals
        'bb-j12': { x: 525, y: 350, name: 'LDR Photoresistor Leg 1 (5V)' },
        'bb-j13': { x: 540, y: 350, name: 'LDR Output / 10k Divider' },
        'bb-j14': { x: 555, y: 350, name: '10k Pull-down Resistor GND' },

        // Ultrasonic Sensor Terminals
        'bb-j5':  { x: 450, y: 350, name: 'HC-SR04 VCC (5V)' },
        'bb-j6':  { x: 465, y: 350, name: 'HC-SR04 Trigger Pin' },
        'bb-j7':  { x: 480, y: 350, name: 'HC-SR04 Echo Pin' }
    };

    // Preset Circuits Definition
    const presets = {
        blink: {
            title: "LED Blink & Optical Timing",
            badge: "Lab 01: Digital I/O",
            description: "Connect Arduino Digital Pin 13 to the LED Anode, and Ground (GND) to the Resistor Cathode.",
            sliderLabel: "Blink Frequency Control (ms)",
            sliderMin: 100, sliderMax: 2000, sliderVal: 800,
            unit: "ms",
            code: `// ========================================================\n// Lab 01: Standard LED Blink & Optical Timing\n// Digital Pin 13 & Breadboard Red LED\n// ========================================================\n\nconst int ledPin = 13;\n\nvoid setup() {\n  pinMode(ledPin, OUTPUT);\n  Serial.begin(9600);\n  Serial.println("--- System Initialized: Optical Blink Lab ---");\n}\n\nvoid loop() {\n  digitalWrite(ledPin, HIGH);\n  Serial.println("LED State: ON  (5.0V)");\n  delay(800);\n  \n  digitalWrite(ledPin, LOW);\n  Serial.println("LED State: OFF (0.0V)");\n  delay(800);\n}`,
            requiredWires: [
                { from: 'pin-13', to: 'bb-f15', color: '#ef4444' },      // Pin 13 to LED Anode
                { from: 'pin-gnd-1', to: 'bb-rail-neg', color: '#0f172a' }, // GND to Rail
                { from: 'bb-rail-neg', to: 'bb-j17', color: '#0f172a' }    // Rail to Resistor
            ]
        },
        pwm_fade: {
            title: "Pulse-Width Modulation (PWM) Fading",
            badge: "Lab 02: Duty Cycle",
            description: "Modulate average voltage across an LED using 8-bit timer PWM (Pin 9, 490 Hz).",
            sliderLabel: "Manual PWM Override (0-255)",
            sliderMin: 0, sliderMax: 255, sliderVal: 128,
            unit: "PWM",
            code: `// ========================================================\n// Lab 02: Pulse-Width Modulation (PWM) LED Breathing\n// Demonstrating effective DC voltage V_eff = V_max * (D / 255)\n// ========================================================\n\nconst int pwmPin = 9;\nint brightness = 0;\nint fadeAmount = 5;\n\nvoid setup() {\n  pinMode(pwmPin, OUTPUT);\n  Serial.begin(9600);\n  Serial.println("--- PWM Voltage Modulation Initialized ---");\n}\n\nvoid loop() {\n  analogWrite(pwmPin, brightness);\n  float effectiveVoltage = (brightness / 255.0) * 5.0;\n  \n  Serial.print("Duty Cycle: ");\n  Serial.print(brightness);\n  Serial.print(" | V_eff: ");\n  Serial.print(effectiveVoltage, 2);\n  Serial.println(" V");\n  \n  brightness = brightness + fadeAmount;\n  if (brightness <= 0 || brightness >= 255) {\n    fadeAmount = -fadeAmount;\n  }\n  delay(30);\n}`,
            requiredWires: [
                { from: 'pin-9', to: 'bb-f15', color: '#0284c7' },
                { from: 'pin-gnd-1', to: 'bb-rail-neg', color: '#0f172a' },
                { from: 'bb-rail-neg', to: 'bb-j17', color: '#0f172a' }
            ]
        },
        potentiometer: {
            title: "Potentiometer 10-Bit ADC Voltage Divider",
            badge: "Lab 03: Ohm's Law & ADC",
            description: "Wire 5V to Leg 1, GND to Leg 3, and Pin A0 to the Center Wiper to read continuous voltage.",
            sliderLabel: "Potentiometer Knob Position (0-1023)",
            sliderMin: 0, sliderMax: 1023, sliderVal: 512,
            unit: "ADC",
            code: `// ========================================================\n// Lab 03: Potentiometer Voltage Divider (Ohm's Law)\n// 10-Bit ADC Resolution: Delta V = 5.0V / 1024 = 4.88 mV\n// ========================================================\n\nconst int potPin = A0;\nconst int indicatorPin = 9;\n\nvoid setup() {\n  Serial.begin(9600);\n  pinMode(indicatorPin, OUTPUT);\n  Serial.println("--- 10-Bit ADC Sensor Calibrator ---");\n}\n\nvoid loop() {\n  int rawADC = analogRead(potPin);\n  float voltage = rawADC * (5.0 / 1023.0);\n  int ledPWM = map(rawADC, 0, 1023, 0, 255);\n  \n  analogWrite(indicatorPin, ledPWM);\n  \n  Serial.print("ADC: ");\n  Serial.print(rawADC);\n  Serial.print(" | Voltage: ");\n  Serial.print(voltage, 3);\n  Serial.println(" V");\n  \n  delay(100);\n}`,
            requiredWires: [
                { from: 'pin-5v', to: 'bb-rail-pos', color: '#ef4444' },
                { from: 'pin-gnd-2', to: 'bb-rail-neg', color: '#0f172a' },
                { from: 'bb-rail-pos', to: 'bb-j8', color: '#ef4444' },
                { from: 'bb-rail-neg', to: 'bb-j10', color: '#0f172a' },
                { from: 'pin-a0', to: 'bb-j9', color: '#10b981' }
            ]
        },
        ldr_sensor: {
            title: "Photoresistor (LDR) Solar Light Sensor",
            badge: "Lab 04: Photoelectric Sensors",
            description: "Wire 5V to LDR, Pin A1 to the divider midpoint, and 10k resistor to GND.",
            sliderLabel: "Simulated Ambient Light Intensity (Lux)",
            sliderMin: 10, sliderMax: 1000, sliderVal: 350,
            unit: "Lux",
            code: `// ========================================================\n// Lab 04: LDR Photoelectric Sensor & Solar Insolation\n// Resistance decreases exponentially with photon flux\n// ========================================================\n\nconst int ldrPin = A1;\nconst int nightLightPin = 8;\nconst int lightThreshold = 450;\n\nvoid setup() {\n  Serial.begin(9600);\n  pinMode(nightLightPin, OUTPUT);\n  Serial.println("--- Photometric Sensing System Online ---");\n}\n\nvoid loop() {\n  int sensorVal = analogRead(ldrPin);\n  float vOut = sensorVal * (5.0 / 1023.0);\n  \n  Serial.print("LDR Raw: ");\n  Serial.print(sensorVal);\n  Serial.print(" | V_out: ");\n  Serial.print(vOut, 2);\n  \n  if (sensorVal < lightThreshold) {\n    digitalWrite(nightLightPin, HIGH);\n    Serial.println(" V | Status: [DARK] LED ON");\n  } else {\n    digitalWrite(nightLightPin, LOW);\n    Serial.println(" V | Status: [DAYLIGHT] LED OFF");\n  }\n  \n  delay(200);\n}`,
            requiredWires: [
                { from: 'pin-5v', to: 'bb-rail-pos', color: '#ef4444' },
                { from: 'pin-gnd-2', to: 'bb-rail-neg', color: '#0f172a' },
                { from: 'bb-rail-pos', to: 'bb-j12', color: '#ef4444' },
                { from: 'pin-a1', to: 'bb-j13', color: '#f59e0b' },
                { from: 'bb-rail-neg', to: 'bb-j14', color: '#0f172a' }
            ]
        },
        ultrasonic: {
            title: "Ultrasonic HC-SR04 Speed of Sound Rangefinder",
            badge: "Lab 05: Acoustic Physics",
            description: "Connect 5V, GND, Pin 9 to Trig, and Pin 8 to Echo to measure acoustic transit time.",
            sliderLabel: "Target Object Distance (cm)",
            sliderMin: 2, sliderMax: 350, sliderVal: 45,
            unit: "cm",
            code: `// ========================================================\n// Lab 05: Ultrasonic HC-SR04 Speed of Sound & Range\n// Velocity of Sound in Air v ~ 343 m/s = 0.0343 cm/us\n// ========================================================\n\nconst int trigPin = 9;\nconst int echoPin = 8;\n\nvoid setup() {\n  Serial.begin(9600);\n  pinMode(trigPin, OUTPUT);\n  pinMode(echoPin, INPUT);\n  Serial.println("--- Ultrasonic Acoustic Telemetry Ready ---");\n}\n\nvoid loop() {\n  digitalWrite(trigPin, LOW);\n  delayMicroseconds(2);\n  digitalWrite(trigPin, HIGH);\n  delayMicroseconds(10);\n  digitalWrite(trigPin, LOW);\n  \n  long duration = pulseIn(echoPin, HIGH);\n  float distanceCm = duration * 0.0343 / 2.0;\n  \n  Serial.print("Transit Time: ");\n  Serial.print(duration);\n  Serial.print(" us | Distance: ");\n  Serial.print(distanceCm, 1);\n  Serial.println(" cm");\n  \n  delay(250);\n}`,
            requiredWires: [
                { from: 'pin-5v', to: 'bb-rail-pos', color: '#ef4444' },
                { from: 'pin-gnd-2', to: 'bb-rail-neg', color: '#0f172a' },
                { from: 'bb-rail-pos', to: 'bb-j5', color: '#ef4444' },
                { from: 'pin-9', to: 'bb-j6', color: '#06b6d4' },
                { from: 'pin-8', to: 'bb-j7', color: '#a855f7' },
                { from: 'bb-rail-neg', to: 'bb-rail-neg', color: '#0f172a' }
            ]
        }
    };

    // Initialize Simulator
    function init() {
        createTerminalPinOverlays();
        initCodeEditor();
        bindUI();
        loadPreset('blink');
    }

    // Generate Clickable Terminal Overlays for all Arduino & Breadboard pins
    function createTerminalPinOverlays() {
        const stage = document.getElementById('tcCircuitStage');
        if (!stage) return;

        // Remove existing pin overlays if any
        stage.querySelectorAll('.circuit-pin').forEach(el => el.remove());

        Object.keys(pinMap).forEach(pinId => {
            const p = pinMap[pinId];
            const pinEl = document.createElement('div');
            pinEl.className = 'circuit-pin';
            pinEl.id = pinId;
            pinEl.title = p.name;
            pinEl.style.left = p.x + 'px';
            pinEl.style.top = p.y + 'px';

            pinEl.addEventListener('click', function (e) {
                e.stopPropagation();
                handlePinClick(pinId);
            });

            stage.appendChild(pinEl);
        });

        // Rubberband drawing wire SVG path
        const svg = document.getElementById('tcWiresLayer');
        if (svg) {
            const tempPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            tempPath.id = 'tcRubberbandWire';
            tempPath.setAttribute('class', 'drawing-wire');
            tempPath.style.display = 'none';
            svg.appendChild(tempPath);
        }

        // Track cursor for live rubberband wire drawing
        stage.addEventListener('mousemove', function (e) {
            if (!activeWireStartPin) return;
            const rect = stage.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            const startPos = pinMap[activeWireStartPin];
            if (!startPos) return;

            const tempPath = document.getElementById('tcRubberbandWire');
            if (tempPath) {
                const d = `M ${startPos.x} ${startPos.y} Q ${(startPos.x + mouseX) / 2} ${(startPos.y + mouseY) / 2 - 30}, ${mouseX} ${mouseY}`;
                tempPath.setAttribute('d', d);
                tempPath.setAttribute('stroke', selectedWireColor);
                tempPath.setAttribute('stroke-width', '3.5');
                tempPath.style.display = 'block';
            }
        });

        // Cancel drawing if clicked on empty canvas
        stage.addEventListener('click', function () {
            if (activeWireStartPin) {
                cancelWireDrawing();
            }
            deselectWire();
        });
    }

    // Pin Click Handling (Connect Wire from Pin A to Pin B)
    function handlePinClick(pinId) {
        if (!activeWireStartPin) {
            // Start drawing wire from this pin
            activeWireStartPin = pinId;
            const pinEl = document.getElementById(pinId);
            if (pinEl) pinEl.classList.add('pin-active');
            showNotification(`Connecting from ${pinMap[pinId].name}... Click target pin to complete wire.`);
        } else if (activeWireStartPin === pinId) {
            // Clicked same pin: cancel
            cancelWireDrawing();
        } else {
            // Complete Wire Connection!
            const fromPin = activeWireStartPin;
            const toPin = pinId;

            addWire(fromPin, toPin, selectedWireColor);
            cancelWireDrawing();
            showNotification(`Wire connected: ${pinMap[fromPin].name} ➔ ${pinMap[toPin].name}`);

            // Check Circuit Connectivity
            checkCircuitConnectivity();
        }
    }

    function cancelWireDrawing() {
        if (activeWireStartPin) {
            const pinEl = document.getElementById(activeWireStartPin);
            if (pinEl) pinEl.classList.remove('pin-active');
        }
        activeWireStartPin = null;

        const tempPath = document.getElementById('tcRubberbandWire');
        if (tempPath) tempPath.style.display = 'none';
    }

    // Add Wire to Model and SVG Layer
    function addWire(fromPin, toPin, color) {
        const wireId = 'wire_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
        userWires.push({ id: wireId, from: fromPin, to: toPin, color: color });
        renderWires();
    }

    function removeWire(wireId) {
        userWires = userWires.filter(w => w.id !== wireId);
        deselectWire();
        renderWires();
        checkCircuitConnectivity();
    }

    function selectWire(wireId) {
        selectedWireId = wireId;
        const deleteBtn = document.getElementById('tcDeleteWireBtn');
        if (deleteBtn) deleteBtn.style.display = 'inline-flex';
        renderWires();
    }

    function deselectWire() {
        selectedWireId = null;
        const deleteBtn = document.getElementById('tcDeleteWireBtn');
        if (deleteBtn) deleteBtn.style.display = 'none';
        renderWires();
    }

    // Render User Wires in SVG Layer
    function renderWires() {
        const svg = document.getElementById('tcWiresLayer');
        if (!svg) return;

        // Keep rubberband path, remove all existing wire paths
        svg.querySelectorAll('.wire-path').forEach(p => p.remove());

        userWires.forEach(w => {
            const p1 = pinMap[w.from];
            const p2 = pinMap[w.to];
            if (!p1 || !p2) return;

            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            const d = `M ${p1.x} ${p1.y} C ${p1.x + 60} ${p1.y + 40}, ${p2.x - 60} ${p2.y + 40}, ${p2.x} ${p2.y}`;
            path.setAttribute('d', d);
            path.setAttribute('class', 'wire-path' + (w.id === selectedWireId ? ' selected' : ''));
            path.setAttribute('stroke', w.color);
            path.setAttribute('stroke-width', w.id === selectedWireId ? '6' : '3.5');

            path.addEventListener('click', function (e) {
                e.stopPropagation();
                selectWire(w.id);
            });

            svg.appendChild(path);
        });
    }

    // Circuit Connectivity Checker
    function checkCircuitConnectivity() {
        const p = presets[currentPreset];
        if (!p || !p.requiredWires) return true;

        // Check if all required wire pairs are present in userWires
        let allConnected = true;
        p.requiredWires.forEach(req => {
            const hasWire = userWires.some(u => 
                (u.from === req.from && u.to === req.to) ||
                (u.from === req.to && u.to === req.from)
            );
            if (!hasWire) allConnected = false;
        });

        const statusEl = document.getElementById('tcCircuitStatus');
        if (statusEl) {
            if (allConnected) {
                statusEl.innerHTML = '<i class="fa-solid fa-circle-check" style="color: #22c55e;"></i> Circuit Closed (Current Flowing)';
                statusEl.style.color = '#22c55e';
            } else {
                statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color: #f59e0b;"></i> Circuit Incomplete (Connect wires to power components)';
                statusEl.style.color = '#f59e0b';
            }
        }

        return allConnected;
    }

    // Notification Banner
    function showNotification(msg) {
        let banner = document.getElementById('tcNoticeBanner');
        if (!banner) {
            banner = document.createElement('div');
            banner.id = 'tcNoticeBanner';
            banner.className = 'wiring-hint-banner';
            const stage = document.getElementById('tcCircuitStage');
            if (stage) stage.appendChild(banner);
        }
        banner.innerHTML = `<i class="fa-solid fa-circle-info"></i> ${msg}`;
        banner.style.display = 'flex';
        setTimeout(() => {
            if (banner) banner.style.display = 'none';
        }, 3500);
    }

    // Code Editor Setup
    function initCodeEditor() {
        const textarea = document.getElementById('tcCodeTextarea');
        if (!textarea) return;

        if (window.CodeMirror) {
            codeEditor = CodeMirror.fromTextArea(textarea, {
                mode: 'text/x-c++src',
                theme: 'material-ocean',
                lineNumbers: true,
                lineWrapping: true,
                tabSize: 2,
                indentWithTabs: false
            });
        }
    }

    // Bind Controls
    function bindUI() {
        const simBtn = document.getElementById('tcStartSimBtn');
        if (simBtn) simBtn.addEventListener('click', toggleSimulation);

        const presetSelect = document.getElementById('tcPresetSelector');
        if (presetSelect) {
            presetSelect.addEventListener('change', function () {
                loadPreset(this.value);
            });
        }

        const autoWireBtn = document.getElementById('tcAutoWireBtn');
        if (autoWireBtn) {
            autoWireBtn.addEventListener('click', function () {
                autoWireCurrentPreset();
            });
        }

        const clearWiresBtn = document.getElementById('tcClearWiresBtn');
        if (clearWiresBtn) {
            clearWiresBtn.addEventListener('click', function () {
                userWires = [];
                deselectWire();
                renderWires();
                checkCircuitConnectivity();
                showNotification('All wires removed. Click pins to wire circuit manually!');
            });
        }

        const deleteWireBtn = document.getElementById('tcDeleteWireBtn');
        if (deleteWireBtn) {
            deleteWireBtn.addEventListener('click', function () {
                if (selectedWireId) removeWire(selectedWireId);
            });
        }

        // Delete wire via Keyboard Backspace / Delete
        window.addEventListener('keydown', function (e) {
            if ((e.key === 'Delete' || e.key === 'Backspace') && selectedWireId) {
                removeWire(selectedWireId);
            }
        });

        // Wire Color Palette
        document.querySelectorAll('.color-swatch').forEach(swatch => {
            swatch.addEventListener('click', function () {
                document.querySelectorAll('.color-swatch').forEach(s => s.classList.remove('active'));
                this.classList.add('active');
                selectedWireColor = this.dataset.color || '#10b981';
            });
        });

        const codeToggleBtn = document.getElementById('tcToggleCodeBtn');
        if (codeToggleBtn) {
            codeToggleBtn.addEventListener('click', function () {
                const editorPane = document.getElementById('tcEditorPane');
                if (editorPane) {
                    editorPane.classList.toggle('collapsed');
                    this.classList.toggle('active');
                    if (codeEditor) setTimeout(() => codeEditor.refresh(), 100);
                }
            });
        }

        const clearSerialBtn = document.getElementById('tcClearSerialBtn');
        if (clearSerialBtn) {
            clearSerialBtn.addEventListener('click', function () {
                serialBuffer = [];
                const monitor = document.getElementById('tcSerialOutput');
                if (monitor) monitor.textContent = '';
                plotterHistory = [];
                drawPlotter();
            });
        }

        // Serial Tabs
        const tabMonitor = document.getElementById('tcTabMonitor');
        const tabPlotter = document.getElementById('tcTabPlotter');
        const viewMonitor = document.getElementById('tcSerialOutput');
        const viewPlotter = document.getElementById('tcPlotterCanvas');

        if (tabMonitor && tabPlotter) {
            tabMonitor.addEventListener('click', function () {
                tabMonitor.classList.add('active');
                tabPlotter.classList.remove('active');
                if (viewMonitor) viewMonitor.style.display = 'block';
                if (viewPlotter) viewPlotter.style.display = 'none';
            });
            tabPlotter.addEventListener('click', function () {
                tabPlotter.classList.add('active');
                tabMonitor.classList.remove('active');
                if (viewMonitor) viewMonitor.style.display = 'none';
                if (viewPlotter) {
                    viewPlotter.style.display = 'block';
                    drawPlotter();
                }
            });
        }

        // Interactive Component Slider
        const interactiveSlider = document.getElementById('tcInteractiveSlider');
        if (interactiveSlider) {
            interactiveSlider.addEventListener('input', function () {
                const val = parseFloat(this.value);
                const display = document.getElementById('tcSliderValueDisplay');
                if (display) display.textContent = val + " " + (presets[currentPreset]?.unit || "");

                if (currentPreset === 'potentiometer') inputs.potentiometer = val;
                if (currentPreset === 'ldr_sensor') inputs.ldr_lux = val;
                if (currentPreset === 'ultrasonic') inputs.ultrasonic_cm = val;
                if (currentPreset === 'blink') inputs.blink_delay = val;
            });
        }
    }

    // Auto-Wire Current Preset (fills requiredWires)
    function autoWireCurrentPreset() {
        const p = presets[currentPreset];
        if (!p || !p.requiredWires) return;

        userWires = [];
        p.requiredWires.forEach((w, idx) => {
            userWires.push({
                id: 'wire_auto_' + idx,
                from: w.from,
                to: w.to,
                color: w.color
            });
        });
        deselectWire();
        renderWires();
        checkCircuitConnectivity();
        showNotification('Wires auto-connected for ' + p.title + '!');
    }

    // Load Specific Laboratory Preset
    function loadPreset(key) {
        if (!presets[key]) return;
        currentPreset = key;
        const p = presets[key];

        // Update Title & Badge
        const titleEl = document.getElementById('tcPresetTitle');
        const badgeEl = document.getElementById('tcPresetBadge');
        if (titleEl) titleEl.textContent = p.title;
        if (badgeEl) badgeEl.textContent = p.badge;

        // Update Code
        if (codeEditor) {
            codeEditor.setValue(p.code);
        } else {
            const ta = document.getElementById('tcCodeTextarea');
            if (ta) ta.value = p.code;
        }

        // Update Slider
        const sliderLabel = document.getElementById('tcSliderLabel');
        const slider = document.getElementById('tcInteractiveSlider');
        const display = document.getElementById('tcSliderValueDisplay');
        if (sliderLabel) sliderLabel.textContent = p.sliderLabel;
        if (slider) {
            slider.min = p.sliderMin;
            slider.max = p.sliderMax;
            slider.value = p.sliderVal;
        }
        if (display) display.textContent = p.sliderVal + " " + p.unit;

        // Auto-connect wires by default for easy start
        autoWireCurrentPreset();

        // Update Component visibility
        updateBreadboardComponents();

        // Reset Serial
        serialBuffer = [];
        plotterHistory = [];
        const monitor = document.getElementById('tcSerialOutput');
        if (monitor) monitor.textContent = '--- Serial Monitor Ready (9600 baud) --- \n';
        drawPlotter();

        if (isSimulating) {
            stopSimulation();
            startSimulation();
        }
    }

    function updateBreadboardComponents() {
        const led1 = document.getElementById('bbLedRed');
        const potComp = document.getElementById('bbPotentiometer');
        const ldrComp = document.getElementById('bbLdrSensor');
        const usComp = document.getElementById('bbUltrasonic');

        if (led1) led1.style.display = (currentPreset === 'blink' || currentPreset === 'pwm_fade' || currentPreset === 'potentiometer' || currentPreset === 'ldr_sensor') ? 'block' : 'none';
        if (potComp) potComp.style.display = (currentPreset === 'potentiometer') ? 'block' : 'none';
        if (ldrComp) ldrComp.style.display = (currentPreset === 'ldr_sensor') ? 'block' : 'none';
        if (usComp) usComp.style.display = (currentPreset === 'ultrasonic') ? 'block' : 'none';
    }

    // Toggle Simulation State
    function toggleSimulation() {
        if (isSimulating) {
            stopSimulation();
        } else {
            startSimulation();
        }
    }

    function startSimulation() {
        isSimulating = true;
        simStartTime = Date.now();

        const btn = document.getElementById('tcStartSimBtn');
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-stop"></i> Stop Simulation';
            btn.classList.add('running');
        }

        const onLed = document.getElementById('arduinoOnLed');
        if (onLed) onLed.style.background = '#22c55e';

        appendSerial('--- Simulation Started: Clock Active ---\n');

        let stepCount = 0;
        let pwmStep = 0;
        let pwmDir = 5;

        simInterval = setInterval(() => {
            stepCount++;
            updateTimerDisplay();

            // Verify if user connected the circuit!
            const isConnected = checkCircuitConnectivity();

            if (!isConnected) {
                if (stepCount % 20 === 0) {
                    appendSerial('[CIRCUIT WARNING] Wires incomplete or disconnected! Please wire circuit to allow current flow.\n');
                }
                setPinDigital(13, 0);
                setPinPWM(9, 0);
                return;
            }

            // Run Preset Virtual Firmware
            if (currentPreset === 'blink') {
                const interval = (inputs.blink_delay || 800) / 50;
                const state = Math.floor(stepCount / interval) % 2 === 0;
                setPinDigital(13, state ? 1 : 0);
                if (stepCount % Math.floor(interval) === 0) {
                    appendSerial(state ? 'LED State: ON (5.0V)\n' : 'LED State: OFF (0.0V)\n');
                }
            } else if (currentPreset === 'pwm_fade') {
                pwmStep += pwmDir;
                if (pwmStep >= 255 || pwmStep <= 0) pwmDir = -pwmDir;
                const effectiveV = (pwmStep / 255.0) * 5.0;
                setPinPWM(9, pwmStep);
                if (stepCount % 6 === 0) {
                    appendSerial(`PWM: ${pwmStep} | V_eff: ${effectiveV.toFixed(2)} V\n`);
                    pushPlotterPoint(effectiveV);
                }
            } else if (currentPreset === 'potentiometer') {
                const val = inputs.potentiometer;
                const voltage = (val / 1023.0) * 5.0;
                const ledPWM = Math.round((val / 1023.0) * 255);
                setPinPWM(9, ledPWM);
                if (stepCount % 4 === 0) {
                    appendSerial(`ADC A0: ${val} | Voltage: ${voltage.toFixed(3)} V\n`);
                    pushPlotterPoint(voltage);
                }
            } else if (currentPreset === 'ldr_sensor') {
                const lux = inputs.ldr_lux;
                const adcVal = Math.round(1023 * (lux / (lux + 300)));
                const vOut = (adcVal / 1023.0) * 5.0;
                const isDark = adcVal < 450;
                setPinDigital(8, isDark ? 1 : 0);
                if (stepCount % 5 === 0) {
                    appendSerial(`Lux: ${lux} | A1: ${adcVal} (${vOut.toFixed(2)}V) | Night Light: ${isDark ? 'ON' : 'OFF'}\n`);
                    pushPlotterPoint(vOut);
                }
            } else if (currentPreset === 'ultrasonic') {
                const dist = inputs.ultrasonic_cm;
                const durationUs = Math.round(dist * 2 / 0.0343);
                if (stepCount % 5 === 0) {
                    appendSerial(`Echo: ${durationUs} us | Distance: ${dist.toFixed(1)} cm | v_sound: 343 m/s\n`);
                    pushPlotterPoint(dist);
                }
            }
        }, 50);
    }

    function stopSimulation() {
        isSimulating = false;
        if (simInterval) clearInterval(simInterval);

        const btn = document.getElementById('tcStartSimBtn');
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-play"></i> Start Simulation';
            btn.classList.remove('running');
        }

        setPinDigital(13, 0);
        setPinPWM(9, 0);

        const onLed = document.getElementById('arduinoOnLed');
        if (onLed) onLed.style.background = '#334155';

        appendSerial('--- Simulation Halted ---\n');
    }

    // Hardware State Actuators
    function setPinDigital(pin, val) {
        if (pin === 13) {
            const lLed = document.getElementById('arduinoBuiltinLed');
            if (lLed) {
                lLed.style.background = val ? '#eab308' : '#334155';
                if (val) lLed.classList.add('led-glow-yellow');
                else lLed.classList.remove('led-glow-yellow');
            }
        }

        const bbLed = document.getElementById('bbLedRedCore');
        if (bbLed) {
            if (val) {
                bbLed.classList.add('led-glow-red');
                bbLed.style.opacity = '1';
            } else {
                bbLed.classList.remove('led-glow-red');
                bbLed.style.opacity = '0.3';
            }
        }
    }

    function setPinPWM(pin, pwmVal) {
        const opacity = Math.max(0.15, pwmVal / 255.0);
        const bbLed = document.getElementById('bbLedRedCore');
        if (bbLed) {
            bbLed.style.opacity = opacity.toString();
            if (pwmVal > 20) {
                bbLed.classList.add('led-glow-red');
            } else {
                bbLed.classList.remove('led-glow-red');
            }
        }
    }

    function updateTimerDisplay() {
        const timerEl = document.getElementById('tcSimTimer');
        if (!timerEl) return;
        const elapsedMs = Date.now() - simStartTime;
        const totalSec = Math.floor(elapsedMs / 1000);
        const ms = elapsedMs % 1000;
        const min = Math.floor(totalSec / 60);
        const sec = totalSec % 60;

        const str = `${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}.${String(ms).padStart(3, '0')}`;
        timerEl.textContent = str;
    }

    function appendSerial(text) {
        serialBuffer.push(text);
        if (serialBuffer.length > 200) serialBuffer.shift();

        const monitor = document.getElementById('tcSerialOutput');
        if (monitor) {
            monitor.textContent = serialBuffer.join('');
            monitor.scrollTop = monitor.scrollHeight;
        }
    }

    function pushPlotterPoint(val) {
        plotterHistory.push(val);
        if (plotterHistory.length > 80) plotterHistory.shift();
        drawPlotter();
    }

    function drawPlotter() {
        const canvas = document.getElementById('tcPlotterCanvas');
        if (!canvas || canvas.style.display === 'none') return;

        const ctx = canvas.getContext('2d');
        const w = canvas.width;
        const h = canvas.height;

        ctx.fillStyle = '#020617';
        ctx.fillRect(0, 0, w, h);

        ctx.strokeStyle = 'rgba(255, 255, 255, 0.08)';
        ctx.lineWidth = 1;
        for (let y = 20; y < h; y += 30) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(w, y);
            ctx.stroke();
        }

        if (plotterHistory.length < 2) return;

        const minVal = Math.min(...plotterHistory, 0);
        const maxVal = Math.max(...plotterHistory, 5.0);
        const range = (maxVal - minVal) || 1;

        ctx.strokeStyle = '#38bdf8';
        ctx.lineWidth = 2.5;
        ctx.beginPath();

        const stepX = w / 80;
        plotterHistory.forEach((pt, i) => {
            const x = i * stepX;
            const y = h - 20 - ((pt - minVal) / range) * (h - 40);
            if (i === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        });
        ctx.stroke();

        ctx.fillStyle = '#94a3b8';
        ctx.font = '11px "JetBrains Mono"';
        ctx.fillText(`Max: ${maxVal.toFixed(2)} | Min: ${minVal.toFixed(2)}`, 10, 16);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.TinkercadSimulator = {
        loadPreset: loadPreset,
        autoWireCurrentPreset: autoWireCurrentPreset,
        startSimulation: startSimulation,
        stopSimulation: stopSimulation,
        toggleSimulation: toggleSimulation
    };
})();
