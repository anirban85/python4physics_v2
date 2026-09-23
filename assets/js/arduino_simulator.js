/**
 * Python4Physics - Tinkercad Circuits Pro Simulator Engine
 * =========================================================
 * Features:
 * - Photorealistic SVG Breadboard with 400 3D spring-clip tie points
 * - High-precision terminal registry mathematically matching SVG layout
 * - Direct breadboard-mounted component integration for all 5 labs
 * - Professional orthogonal (Manhattan) wiring with waypoint drag handles
 * - SPICE-lite graph solver with breadboard column buses, power rails,
 *   and internal component conductivity
 * - Interactive simulation: pot knob, tactile button, LDR, ultrasonic
 * - Live Serial Monitor (9600 baud) & HTML5 Canvas Serial Plotter
 * - Full Undo/Redo command stack (Ctrl+Z, Ctrl+Y)
 * - Circuit connectivity banner & breadboard bus highlight
 */

(function () {
    'use strict';

    // ==========================================
    // 1. GLOBAL STATE
    // ==========================================
    const state = {
        isSimulating: false,
        simStartTime: 0,
        simInterval: null,
        simTick: 0,
        currentPreset: 'blink',
        selectedWireColor: '#10b981',
        selectedWireName: 'Green',
        wireType: 'normal',

        // Wire Drawing
        drawingWire: null,
        hoverTerminalId: null,

        // Selection
        selectedItem: null, // { type: 'wire'|'component', id }

        // Pan & Zoom
        zoom: 1.0,
        panX: 0,
        panY: 0,
        isPanning: false,
        panStartX: 0,
        panStartY: 0,

        // Placed items
        components: [],
        wires: [],

        // Drag from palette
        dragGhost: null,
        dragType: null,

        // Undo/Redo
        undoStack: [],
        redoStack: [],

        // Telemetry
        serialLogs: [],
        plotterData: [],
        hardwareValues: {
            potentiometer: 512,
            ldrLux: 400,
            ultrasonicCm: 35,
            pwmDuty: 0,
            digital13: false
        },

        // Circuit analysis
        circuitStatus: { valid: false, reason: 'no_wires' }
    };

    let codeEditor = null;

    // Stage offsets for board elements
    const LAYOUT = {
        ardX: 35,
        ardY: 75,
        bbX: 430,
        bbY: 35,
        colPitch: 17,
        colStart: 52
    };

    // ==========================================
    // 2. COMPONENT LIBRARY
    // ==========================================
    const COMPONENT_LIBRARY = {
        led: {
            name: 'LED',
            category: 'basic',
            defaultProps: { color: '#ef4444', forwardVoltage: 2.0, name: 'LED' },
            width: 34,
            height: 44,
            terminalOffsets: [
                { id: 'anode', label: 'Anode (+)', dx: 8.5, dy: 38 },
                { id: 'cathode', label: 'Cathode (-)', dx: 25.5, dy: 38 }
            ]
        },
        resistor: {
            name: 'Resistor',
            category: 'basic',
            defaultProps: { resistance: 220, unit: 'Ω', name: 'R' },
            width: 85,
            height: 20,
            terminalOffsets: [
                { id: 't1', label: 'Terminal 1', dx: 0, dy: 10 },
                { id: 't2', label: 'Terminal 2', dx: 85, dy: 10 }
            ]
        },
        pushbutton: {
            name: 'Pushbutton',
            category: 'basic',
            defaultProps: { name: 'BTN', pressed: false },
            width: 44,
            height: 44,
            terminalOffsets: [
                { id: 'a1', label: 'Pin A1', dx: 5, dy: 10 },
                { id: 'a2', label: 'Pin A2', dx: 39, dy: 10 },
                { id: 'b1', label: 'Pin B1', dx: 5, dy: 34 },
                { id: 'b2', label: 'Pin B2', dx: 39, dy: 34 }
            ]
        },
        potentiometer: {
            name: 'Potentiometer',
            category: 'basic',
            defaultProps: { resistance: 10000, name: 'POT', position: 0.5 },
            width: 50,
            height: 48,
            terminalOffsets: [
                { id: 't1', label: 'Leg 1 (5V)', dx: 8, dy: 40 },
                { id: 'wiper', label: 'Wiper (Signal)', dx: 25, dy: 40 },
                { id: 't2', label: 'Leg 2 (GND)', dx: 42, dy: 40 }
            ]
        },
        ldr: {
            name: 'Photoresistor',
            category: 'basic',
            defaultProps: { name: 'LDR' },
            width: 34,
            height: 40,
            terminalOffsets: [
                { id: 't1', label: 'Terminal 1', dx: 8.5, dy: 34 },
                { id: 't2', label: 'Terminal 2', dx: 25.5, dy: 34 }
            ]
        },
        ultrasonic: {
            name: 'Ultrasonic HC-SR04',
            category: 'basic',
            defaultProps: { name: 'US' },
            width: 90,
            height: 44,
            terminalOffsets: [
                { id: 'vcc', label: 'VCC (5V)', dx: 19.5, dy: 38 },
                { id: 'trig', label: 'Trig', dx: 36.5, dy: 38 },
                { id: 'echo', label: 'Echo', dx: 53.5, dy: 38 },
                { id: 'gnd', label: 'GND', dx: 70.5, dy: 38 }
            ]
        },
        capacitor: {
            name: 'Capacitor',
            category: 'basic',
            defaultProps: { capacitance: 100, unit: 'µF', name: 'C' },
            width: 34,
            height: 38,
            terminalOffsets: [
                { id: 't1', label: 'Positive (+)', dx: 8.5, dy: 34 },
                { id: 't2', label: 'Negative (-)', dx: 25.5, dy: 34 }
            ]
        },
        diode: {
            name: 'Diode',
            category: 'basic',
            defaultProps: { name: 'D', forwardVoltage: 0.7 },
            width: 50,
            height: 18,
            terminalOffsets: [
                { id: 't1', label: 'Anode', dx: 0, dy: 9 },
                { id: 't2', label: 'Cathode', dx: 50, dy: 9 }
            ]
        },
        transistor: {
            name: 'NPN Transistor',
            category: 'basic',
            defaultProps: { name: 'Q', type: '2N2222' },
            width: 34,
            height: 34,
            terminalOffsets: [
                { id: 'collector', label: 'Collector', dx: 8.5, dy: 30 },
                { id: 'base', label: 'Base', dx: 17, dy: 30 },
                { id: 'emitter', label: 'Emitter', dx: 25.5, dy: 30 }
            ]
        },
        buzzer: {
            name: 'Piezo Buzzer',
            category: 'basic',
            defaultProps: { name: 'BZ', frequency: 1000 },
            width: 40,
            height: 42,
            terminalOffsets: [
                { id: 'pos', label: 'Positive (+)', dx: 11.5, dy: 38 },
                { id: 'neg', label: 'Negative (-)', dx: 28.5, dy: 38 }
            ]
        },
        battery9v: {
            name: '9V Battery',
            category: 'basic',
            defaultProps: { name: 'BAT', voltage: 9 },
            width: 44,
            height: 60,
            terminalOffsets: [
                { id: 'pos', label: 'Positive (+)', dx: 14, dy: 0 },
                { id: 'neg', label: 'Negative (-)', dx: 30, dy: 0 }
            ]
        }
    };

    // Helper functions for breadboard stage coordinates
    function bbColX(col) {
        return LAYOUT.bbX + LAYOUT.colStart + (col - 1) * LAYOUT.colPitch;
    }
    function bbRowY(row) {
        const rowOffsets = {
            'top-pos': 20, 'top-neg': 42,
            'a': 82, 'b': 99, 'c': 116, 'd': 133, 'e': 150,
            'f': 184, 'g': 201, 'h': 218, 'i': 235, 'j': 252,
            'bot-pos': 288, 'bot-neg': 310
        };
        return LAYOUT.bbY + (rowOffsets[row] || 82);
    }

    // ==========================================
    // 3. PRESET DEFINITIONS (Breadboard Mounted)
    // ==========================================
    const presets = {
        blink: {
            title: "LED Blink & Optical Timing",
            components: [
                // LED Anode in hole e18 (x=771, y=185), Cathode in e19 (x=788, y=185)
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#ef4444', name: 'LED1' } },
                // 220Ω Resistor plugged between d19 (x=788, y=168) and d24 (x=873, y=168)
                { id: 'res1', type: 'resistor', x: 788, y: 158, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                // Arduino D13 (x=179, y=106) -> Breadboard hole a18 (x=771, y=117)
                { from: 'ard-pin-13', to: 'bb-a18', color: '#f97316', waypoints: [{ x: 179, y: 40 }, { x: 771, y: 40 }] },
                // Resistor leg 2 in column 24: hole a24 (x=873, y=117) down to bottom negative rail (x=873, y=345)
                { from: 'bb-a24', to: 'bb-bot-neg-24', color: '#0f172a', waypoints: [] },
                // Arduino GND (x=220, y=292) -> Bottom negative rail hole 2 (x=499, y=345)
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [{ x: 220, y: 345 }] }
            ],
            code: `// ========================================================\n// Lab 01: Standard LED Blink & Optical Timing\n// Digital Pin 13 & Breadboard Red LED with 220 Ohm Resistor\n// ========================================================\n\nconst int ledPin = 13;\n\nvoid setup() {\n  pinMode(ledPin, OUTPUT);\n  Serial.begin(9600);\n  Serial.println("--- Optical Timing System Initialized ---");\n}\n\nvoid loop() {\n  digitalWrite(ledPin, HIGH);\n  Serial.println("LED State: ON  (5.00 V)");\n  delay(800);\n  \n  digitalWrite(ledPin, LOW);\n  Serial.println("LED State: OFF (0.00 V)");\n  delay(800);\n}`
        },
        pwm_fade: {
            title: "PWM Breathing & Effective DC Voltage",
            components: [
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#0284c7', name: 'LED1' } },
                { id: 'res1', type: 'resistor', x: 788, y: 158, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                // Arduino D9 PWM (x=231, y=106) -> Breadboard hole a18 (x=771, y=117)
                { from: 'ard-pin-9', to: 'bb-a18', color: '#0284c7', waypoints: [{ x: 231, y: 40 }, { x: 771, y: 40 }] },
                { from: 'bb-a24', to: 'bb-bot-neg-24', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [{ x: 220, y: 345 }] }
            ],
            code: `// ========================================================\n// Lab 02: Pulse-Width Modulation (PWM) LED Breathing\n// Effective DC Voltage: V_eff = 5.0 * (Duty / 255.0)\n// ========================================================\n\nconst int pwmPin = 9;\nint brightness = 0;\nint fadeStep = 5;\n\nvoid setup() {\n  pinMode(pwmPin, OUTPUT);\n  Serial.begin(9600);\n  Serial.println("--- PWM Voltage Modulation Active ---");\n}\n\nvoid loop() {\n  analogWrite(pwmPin, brightness);\n  float vEff = (brightness / 255.0) * 5.0;\n  \n  Serial.print("Duty Cycle: ");\n  Serial.print(brightness);\n  Serial.print(" | V_eff: ");\n  Serial.print(vEff, 2);\n  Serial.println(" V");\n  \n  brightness += fadeStep;\n  if (brightness <= 0 || brightness >= 255) {\n    fadeStep = -fadeStep;\n  }\n  delay(30);\n}`
        },
        potentiometer: {
            title: "Potentiometer 10-Bit ADC Voltage Divider",
            components: [
                // Potentiometer mounted at columns 10, 11, 12 in row e (x=635, y=145)
                { id: 'pot1', type: 'potentiometer', x: 627, y: 145, rotation: 0, props: { resistance: 10000, name: 'POT1', position: 0.5 } }
            ],
            autoWires: [
                // 5V power from Arduino to breadboard hole a10
                { from: 'ard-pin-5v', to: 'bb-a10', color: '#ef4444', waypoints: [{ x: 208, y: 330 }, { x: 635, y: 330 }] },
                // GND from Arduino to breadboard hole a12
                { from: 'ard-pin-gnd1', to: 'bb-a12', color: '#0f172a', waypoints: [{ x: 220, y: 345 }, { x: 669, y: 345 }] },
                // Wiper signal from hole a11 to Arduino Analog A0
                { from: 'ard-pin-a0', to: 'bb-a11', color: '#10b981', waypoints: [{ x: 277, y: 260 }, { x: 652, y: 260 }] }
            ],
            code: `// ========================================================\n// Lab 03: Potentiometer Voltage Divider (Ohm's Law)\n// 10-Bit ADC Resolution: 5.0V / 1024 = 4.88 mV per count\n// ========================================================\n\nconst int potPin = A0;\n\nvoid setup() {\n  Serial.begin(9600);\n  Serial.println("--- 10-Bit ADC Acquisition Ready ---");\n}\n\nvoid loop() {\n  int rawADC = analogRead(potPin);\n  float voltage = (rawADC / 1023.0) * 5.0;\n  \n  Serial.print("ADC: ");\n  Serial.print(rawADC);\n  Serial.print(" | Voltage: ");\n  Serial.print(voltage, 3);\n  Serial.println(" V");\n  \n  delay(100);\n}`
        },
        ldr_sensor: {
            title: "Photoresistor (LDR) Solar Light Sensor",
            components: [
                // LDR in columns 14 & 15, row e
                { id: 'ldr1', type: 'ldr', x: 694.5, y: 151, rotation: 0, props: { name: 'LDR1' } },
                // 10kΩ Resistor in columns 15 to 19, row d
                { id: 'res1', type: 'resistor', x: 720, y: 158, rotation: 0, props: { resistance: 10000, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'bb-a14', color: '#ef4444', waypoints: [{ x: 208, y: 330 }, { x: 703, y: 330 }] },
                { from: 'ard-pin-a1', to: 'bb-a15', color: '#10b981', waypoints: [{ x: 289, y: 260 }, { x: 720, y: 260 }] },
                { from: 'bb-a20', to: 'bb-bot-neg-20', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [{ x: 220, y: 345 }] }
            ],
            code: `// ========================================================\n// Lab 04: LDR Photoelectric Sensor & Solar Insolation\n// Semiconductor photo-conductivity increases with lux\n// ========================================================\n\nconst int ldrPin = A1;\n\nvoid setup() {\n  Serial.begin(9600);\n  Serial.println("--- Photometric Telemetry Online ---");\n}\n\nvoid loop() {\n  int sensorVal = analogRead(ldrPin);\n  float vOut = sensorVal * (5.0 / 1023.0);\n  \n  Serial.print("LDR ADC: ");\n  Serial.print(sensorVal);\n  Serial.print(" | V_out: ");\n  Serial.print(vOut, 2);\n  Serial.println(" V");\n  \n  delay(200);\n}`
        },
        ultrasonic: {
            title: "Ultrasonic HC-SR04 Speed of Sound Rangefinder",
            components: [
                // Ultrasonic sensor plugged into lower bank columns 14, 15, 16, 17 in row j
                { id: 'us1', type: 'ultrasonic', x: 683.5, y: 249, rotation: 0, props: { name: 'US1' } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'bb-f14', color: '#ef4444', waypoints: [{ x: 208, y: 330 }, { x: 703, y: 330 }] },
                { from: 'ard-pin-9', to: 'bb-f15', color: '#0284c7', waypoints: [{ x: 231, y: 40 }, { x: 720, y: 40 }] },
                { from: 'ard-pin-8', to: 'bb-f16', color: '#8b5cf6', waypoints: [{ x: 244, y: 48 }, { x: 737, y: 48 }] },
                { from: 'ard-pin-gnd1', to: 'bb-f17', color: '#0f172a', waypoints: [{ x: 220, y: 345 }, { x: 754, y: 345 }] }
            ],
            code: `// ========================================================\n// Lab 05: Ultrasonic HC-SR04 Speed of Sound & Range\n// Velocity of Sound in Air v = 343 m/s = 0.0343 cm/us\n// ========================================================\n\nconst int trigPin = 9;\nconst int echoPin = 8;\n\nvoid setup() {\n  pinMode(trigPin, OUTPUT);\n  pinMode(echoPin, INPUT);\n  Serial.begin(9600);\n  Serial.println("--- Ultrasonic Acoustic Telemetry Ready ---");\n}\n\nvoid loop() {\n  digitalWrite(trigPin, LOW);\n  delayMicroseconds(2);\n  digitalWrite(trigPin, HIGH);\n  delayMicroseconds(10);\n  digitalWrite(trigPin, LOW);\n  \n  long duration = pulseIn(echoPin, HIGH);\n  float distanceCm = duration * 0.0343 / 2.0;\n  \n  Serial.print("Echo Transit Time: ");\n  Serial.print(duration);\n  Serial.print(" us | Distance: ");\n  Serial.print(distanceCm, 1);\n  Serial.println(" cm");\n  \n  delay(250);\n}`
        }
    };

    // ==========================================
    // 4. UNDO / REDO COMMAND STACK
    // ==========================================
    function pushUndo(action) {
        state.undoStack.push(action);
        if (state.undoStack.length > 50) state.undoStack.shift();
        state.redoStack = [];
    }

    function undo() {
        if (state.undoStack.length === 0) return;
        const action = state.undoStack.pop();
        state.redoStack.push(action);
        action.undo();
        renderAll();
    }

    function redo() {
        if (state.redoStack.length === 0) return;
        const action = state.redoStack.pop();
        state.undoStack.push(action);
        action.redo();
        renderAll();
    }

    // ==========================================
    // 5. INITIALIZATION
    // ==========================================
    function init() {
        initCodeEditor();
        initTerminals();
        bindUI();
        bindDragAndDrop();
        setupCanvasPanning();
        loadPreset('blink');
        updateStatusBar();
    }

    function initCodeEditor() {
        const textarea = document.getElementById('tcCodeTextarea');
        if (!textarea) return;
        if (window.CodeMirror) {
            codeEditor = CodeMirror.fromTextArea(textarea, {
                lineNumbers: true,
                mode: 'text/x-c++src',
                theme: 'material-ocean',
                indentUnit: 2,
                tabSize: 2,
                lineWrapping: true
            });
            window.tcCodeEditor = codeEditor;
        }
    }

    // ==========================================
    // 6. TERMINAL REGISTRY
    // ==========================================
    const terminals = {};

    function initTerminals() {
        const container = document.getElementById('tcTerminalsContainer');
        if (!container) return;
        container.innerHTML = '';
        for (const k in terminals) delete terminals[k];

        // 1. Arduino Uno Pin Terminals
        const { ardX, ardY } = LAYOUT;

        const digitalPins = [
            { id: 'ard-pin-aref', name: 'AREF', x: 118, y: 31 },
            { id: 'ard-pin-gnd0', name: 'GND', x: 131, y: 31 },
            { id: 'ard-pin-13', name: 'D13 (SCK)', x: 144, y: 31 },
            { id: 'ard-pin-12', name: 'D12 (MISO)', x: 157, y: 31 },
            { id: 'ard-pin-11', name: 'D11 (~PWM)', x: 170, y: 31 },
            { id: 'ard-pin-10', name: 'D10 (~PWM)', x: 183, y: 31 },
            { id: 'ard-pin-9', name: 'D9 (~PWM)', x: 196, y: 31 },
            { id: 'ard-pin-8', name: 'D8', x: 209, y: 31 },
            { id: 'ard-pin-7', name: 'D7', x: 226, y: 31 },
            { id: 'ard-pin-6', name: 'D6 (~PWM)', x: 239, y: 31 },
            { id: 'ard-pin-5', name: 'D5 (~PWM)', x: 252, y: 31 },
            { id: 'ard-pin-4', name: 'D4', x: 265, y: 31 },
            { id: 'ard-pin-3', name: 'D3 (~PWM)', x: 278, y: 31 },
            { id: 'ard-pin-2', name: 'D2 (INT0)', x: 291, y: 31 },
            { id: 'ard-pin-tx', name: 'D1 (TX)', x: 304, y: 31 },
            { id: 'ard-pin-rx', name: 'D0 (RX)', x: 317, y: 31 }
        ];
        digitalPins.forEach(p => registerTerminal(p.id, p.name, ardX + p.x, ardY + p.y));

        const powerPins = [
            { id: 'ard-pin-ioref', name: 'IOREF', x: 137, y: 217 },
            { id: 'ard-pin-reset', name: 'RESET', x: 149, y: 217 },
            { id: 'ard-pin-3v3', name: '3.3V', x: 161, y: 217 },
            { id: 'ard-pin-5v', name: '5V', x: 173, y: 217 },
            { id: 'ard-pin-gnd1', name: 'GND', x: 185, y: 217 },
            { id: 'ard-pin-gnd2', name: 'GND', x: 197, y: 217 },
            { id: 'ard-pin-vin', name: 'VIN', x: 209, y: 217 }
        ];
        powerPins.forEach(p => registerTerminal(p.id, p.name, ardX + p.x, ardY + p.y));

        const analogPins = [
            { id: 'ard-pin-a0', name: 'A0', x: 242, y: 217 },
            { id: 'ard-pin-a1', name: 'A1', x: 254, y: 217 },
            { id: 'ard-pin-a2', name: 'A2', x: 266, y: 217 },
            { id: 'ard-pin-a3', name: 'A3', x: 278, y: 217 },
            { id: 'ard-pin-a4', name: 'A4', x: 290, y: 217 },
            { id: 'ard-pin-a5', name: 'A5', x: 302, y: 217 }
        ];
        analogPins.forEach(p => registerTerminal(p.id, p.name, ardX + p.x, ardY + p.y));

        // 2. Breadboard 400 Hole Terminals
        for (let col = 1; col <= 30; col++) {
            const posX = bbColX(col);

            // Power rails top
            registerTerminal(`bb-top-pos-${col}`, `(+) Top Rail [${col}]`, posX, bbRowY('top-pos'));
            registerTerminal(`bb-top-neg-${col}`, `(−) Top Ground [${col}]`, posX, bbRowY('top-neg'));

            // Upper terminal strip rows a to e
            ['a', 'b', 'c', 'd', 'e'].forEach(r => {
                registerTerminal(`bb-${r}${col}`, `Terminal ${r.toUpperCase()}${col}`, posX, bbRowY(r));
            });

            // Lower terminal strip rows f to j
            ['f', 'g', 'h', 'i', 'j'].forEach(r => {
                registerTerminal(`bb-${r}${col}`, `Terminal ${r.toUpperCase()}${col}`, posX, bbRowY(r));
            });

            // Power rails bottom
            registerTerminal(`bb-bot-pos-${col}`, `(+) Bottom Rail [${col}]`, posX, bbRowY('bot-pos'));
            registerTerminal(`bb-bot-neg-${col}`, `(−) Bottom Ground [${col}]`, posX, bbRowY('bot-neg'));
        }
    }

    function registerTerminal(id, name, x, y) {
        terminals[id] = { id, name, x, y };

        const container = document.getElementById('tcTerminalsContainer');
        if (!container) return;

        let pin = document.getElementById(`term_${id}`);
        if (pin) {
            pin.style.left = `${x}px`;
            pin.style.top = `${y}px`;
            return;
        }

        pin = document.createElement('div');
        pin.className = 'tc-terminal-pin';
        pin.id = `term_${id}`;
        pin.style.left = `${x}px`;
        pin.style.top = `${y}px`;

        const tip = document.createElement('div');
        tip.className = 'tc-pin-tooltip';
        tip.textContent = name;
        pin.appendChild(tip);

        pin.addEventListener('mouseenter', () => handleTerminalHover(id));
        pin.addEventListener('mouseleave', () => handleTerminalUnhover(id));
        pin.addEventListener('click', (e) => {
            e.stopPropagation();
            handleTerminalClick(id);
        });

        container.appendChild(pin);
    }

    // ==========================================
    // 7. BREADBOARD BUS HIGHLIGHT
    // ==========================================
    function handleTerminalHover(id) {
        state.hoverTerminalId = id;
        const el = document.getElementById(`term_${id}`);
        if (el) el.classList.add('active');

        if (state.drawingWire && state.drawingWire.startTerminalId !== id) {
            if (el) el.classList.add('snap-candidate');
        }

        showBusHighlight(id);
    }

    function handleTerminalUnhover(id) {
        if (state.hoverTerminalId === id) state.hoverTerminalId = null;
        const el = document.getElementById(`term_${id}`);
        if (el) {
            el.classList.remove('active');
            el.classList.remove('snap-candidate');
        }
        clearBusHighlight();
    }

    function showBusHighlight(terminalId) {
        const group = document.getElementById('tcBbBusHighlightGroup');
        if (!group) return;
        group.innerHTML = '';

        if (!terminalId.startsWith('bb-')) return;

        let rectX, rectY, rectW, rectH;

        if (terminalId.startsWith('bb-top-pos-')) {
            rectX = 40; rectY = 14; rectW = 520; rectH = 12;
        } else if (terminalId.startsWith('bb-top-neg-')) {
            rectX = 40; rectY = 36; rectW = 520; rectH = 12;
        } else if (terminalId.startsWith('bb-bot-pos-')) {
            rectX = 40; rectY = 282; rectW = 520; rectH = 12;
        } else if (terminalId.startsWith('bb-bot-neg-')) {
            rectX = 40; rectY = 304; rectW = 520; rectH = 12;
        } else {
            const match = terminalId.match(/^bb-([a-j])(\d+)$/);
            if (!match) return;
            const row = match[1];
            const col = parseInt(match[2]);
            const hx = LAYOUT.colStart + (col - 1) * LAYOUT.colPitch;

            if (['a', 'b', 'c', 'd', 'e'].includes(row)) {
                rectX = hx - 6.5; rectY = 75; rectW = 13; rectH = 82;
            } else {
                rectX = hx - 6.5; rectY = 177; rectW = 13; rectH = 82;
            }
        }

        const r = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        r.setAttribute('x', rectX);
        r.setAttribute('y', rectY);
        r.setAttribute('width', rectW);
        r.setAttribute('height', rectH);
        r.setAttribute('class', 'tc-bb-bus-rect');
        group.appendChild(r);
    }

    function clearBusHighlight() {
        const group = document.getElementById('tcBbBusHighlightGroup');
        if (group) group.innerHTML = '';
    }

    // ==========================================
    // 8. WIRING SYSTEM
    // ==========================================
    function handleTerminalClick(id) {
        if (!state.drawingWire) {
            startWireDrawing(id);
        } else if (state.drawingWire.startTerminalId === id) {
            cancelWireDrawing();
        } else {
            completeWireDrawing(id);
        }
    }

    function startWireDrawing(terminalId) {
        const t = terminals[terminalId];
        if (!t) return;

        state.drawingWire = {
            startTerminalId: terminalId,
            waypoints: [{ x: t.x, y: t.y }],
            color: state.selectedWireColor
        };

        const container = document.getElementById('tcCanvasContainer');
        if (container) container.classList.add('wiring-mode');

        const rubber = document.getElementById('tcRubberbandWire');
        if (rubber) {
            rubber.setAttribute('stroke', state.selectedWireColor);
            rubber.setAttribute('stroke-width', '3.5');
            rubber.style.display = 'block';
        }

        deselectAll();
    }

    function completeWireDrawing(targetTerminalId) {
        const startId = state.drawingWire.startTerminalId;
        const tEnd = terminals[targetTerminalId];
        if (!tEnd) return;

        const wps = [...state.drawingWire.waypoints.slice(1)];
        const wireId = `wire_${Date.now()}_${Math.floor(Math.random() * 1000)}`;
        const newWire = {
            id: wireId,
            from: startId,
            to: targetTerminalId,
            color: state.selectedWireColor,
            waypoints: wps
        };

        state.wires.push(newWire);

        pushUndo({
            type: 'addWire',
            wire: { ...newWire },
            undo: () => { state.wires = state.wires.filter(w => w.id !== wireId); },
            redo: () => { state.wires.push({ ...newWire }); }
        });

        cancelWireDrawing();
        renderWires();
        analyzeCircuit();
    }

    function cancelWireDrawing() {
        state.drawingWire = null;

        const container = document.getElementById('tcCanvasContainer');
        if (container) container.classList.remove('wiring-mode');

        const rubber = document.getElementById('tcRubberbandWire');
        if (rubber) rubber.style.display = 'none';

        document.querySelectorAll('.tc-terminal-pin').forEach(p => {
            p.classList.remove('active');
            p.classList.remove('snap-candidate');
        });
    }

    function handleCanvasClick(e) {
        if (!state.drawingWire) {
            deselectAll();
            return;
        }

        const rect = document.getElementById('tcCanvasStage').getBoundingClientRect();
        let stageX = (e.clientX - rect.left) / state.zoom;
        let stageY = (e.clientY - rect.top) / state.zoom;

        // Snap to 8px grid
        stageX = Math.round(stageX / 8) * 8;
        stageY = Math.round(stageY / 8) * 8;

        state.drawingWire.waypoints.push({ x: stageX, y: stageY });
    }

    function handleCanvasMouseMove(e) {
        if (!state.drawingWire) return;

        const rect = document.getElementById('tcCanvasStage').getBoundingClientRect();
        let targetX = (e.clientX - rect.left) / state.zoom;
        let targetY = (e.clientY - rect.top) / state.zoom;

        if (state.hoverTerminalId && terminals[state.hoverTerminalId]) {
            targetX = terminals[state.hoverTerminalId].x;
            targetY = terminals[state.hoverTerminalId].y;
        }

        const lastWP = state.drawingWire.waypoints[state.drawingWire.waypoints.length - 1];
        const orthoPts = buildOrthogonalSegment(lastWP, { x: targetX, y: targetY });
        const allPts = [...state.drawingWire.waypoints, ...orthoPts];
        const d = buildWirePathString(allPts);

        const rubber = document.getElementById('tcRubberbandWire');
        if (rubber) rubber.setAttribute('d', d);
    }

    function buildOrthogonalSegment(from, to) {
        const dx = Math.abs(to.x - from.x);
        const dy = Math.abs(to.y - from.y);
        if (dx >= dy) {
            return [{ x: to.x, y: from.y }, { x: to.x, y: to.y }];
        }
        return [{ x: from.x, y: to.y }, { x: to.x, y: to.y }];
    }

    function buildWirePathString(points) {
        if (!points || points.length === 0) return '';
        if (points.length === 1) return `M ${points[0].x} ${points[0].y}`;

        let d = `M ${points[0].x} ${points[0].y}`;

        for (let i = 1; i < points.length; i++) {
            const prev = points[i - 1];
            const curr = points[i];
            const next = points[i + 1];

            if (next) {
                const radius = 6;
                const dx1 = curr.x - prev.x;
                const dy1 = curr.y - prev.y;
                const dx2 = next.x - curr.x;
                const dy2 = next.y - curr.y;

                const len1 = Math.hypot(dx1, dy1);
                const len2 = Math.hypot(dx2, dy2);

                if (len1 > 0 && len2 > 0) {
                    const r = Math.min(radius, len1 / 2, len2 / 2);
                    const beforeX = curr.x - (dx1 / len1) * r;
                    const beforeY = curr.y - (dy1 / len1) * r;
                    const afterX = curr.x + (dx2 / len2) * r;
                    const afterY = curr.y + (dy2 / len2) * r;

                    d += ` L ${beforeX} ${beforeY}`;
                    d += ` Q ${curr.x} ${curr.y} ${afterX} ${afterY}`;
                } else {
                    d += ` L ${curr.x} ${curr.y}`;
                }
            } else {
                d += ` L ${curr.x} ${curr.y}`;
            }
        }
        return d;
    }

    function renderWires() {
        const group = document.getElementById('tcWiresGroup');
        if (!group) return;
        group.innerHTML = '';

        document.querySelectorAll('.tc-wire-waypoint').forEach(w => w.remove());

        state.wires.forEach(w => {
            const t1 = terminals[w.from];
            const t2 = terminals[w.to];
            if (!t1 || !t2) return;

            const allPts = [{ x: t1.x, y: t1.y }, ...w.waypoints, { x: t2.x, y: t2.y }];
            const d = buildWirePathString(allPts);
            const isSelected = state.selectedItem?.id === w.id;

            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', d);
            path.setAttribute('class', 'tc-wire-svg-path' + (isSelected ? ' selected' : ''));
            path.setAttribute('stroke', w.color);
            path.setAttribute('stroke-width', isSelected ? '5.5' : '3.5');

            path.addEventListener('click', (e) => {
                e.stopPropagation();
                selectWire(w.id);
            });

            group.appendChild(path);

            if (isSelected) {
                w.waypoints.forEach((wp, idx) => {
                    renderWaypointHandle(w.id, idx, wp.x, wp.y);
                });
            }
        });
    }

    function renderWaypointHandle(wireId, wpIndex, x, y) {
        const stage = document.getElementById('tcCanvasStage');
        if (!stage) return;

        const handle = document.createElement('div');
        handle.className = 'tc-wire-waypoint';
        handle.style.left = `${x}px`;
        handle.style.top = `${y}px`;

        handle.addEventListener('mousedown', (e) => {
            e.stopPropagation();
            startDraggingWaypoint(wireId, wpIndex, e);
        });

        stage.appendChild(handle);
    }

    function startDraggingWaypoint(wireId, wpIndex, startEvent) {
        const wire = state.wires.find(w => w.id === wireId);
        if (!wire) return;

        function onMouseMove(e) {
            const rect = document.getElementById('tcCanvasStage').getBoundingClientRect();
            let x = (e.clientX - rect.left) / state.zoom;
            let y = (e.clientY - rect.top) / state.zoom;
            x = Math.round(x / 8) * 8;
            y = Math.round(y / 8) * 8;
            wire.waypoints[wpIndex].x = x;
            wire.waypoints[wpIndex].y = y;
            renderWires();
        }

        function onMouseUp() {
            window.removeEventListener('mousemove', onMouseMove);
            window.removeEventListener('mouseup', onMouseUp);
        }

        window.addEventListener('mousemove', onMouseMove);
        window.addEventListener('mouseup', onMouseUp);
    }

    // ==========================================
    // 9. COMPONENT PLACEMENT & RENDERING
    // ==========================================
    function placeComponent(type, x, y, rotation = 0, props = {}, explicitId = null) {
        const lib = COMPONENT_LIBRARY[type];
        if (!lib) return null;

        const compId = explicitId || `comp_${type}_${state.components.length + 1}`;
        const comp = {
            id: compId,
            type,
            x: x || 650,
            y: y || 180,
            rotation: rotation || 0,
            props: {
                ...lib.defaultProps,
                name: `${lib.defaultProps.name || type.toUpperCase()}${state.components.length + 1}`,
                ...props
            }
        };

        state.components.push(comp);
        renderComponentDOM(comp);
        registerComponentTerminals(comp);

        const el = document.getElementById(comp.id);
        if (el) {
            el.classList.add('entering');
            setTimeout(() => el.classList.remove('entering'), 300);
        }

        return comp;
    }

    function renderComponentDOM(comp) {
        const container = document.getElementById('tcComponentsContainer');
        if (!container) return;

        let el = document.getElementById(comp.id);
        if (!el) {
            el = document.createElement('div');
            el.className = 'tc-placed-component';
            el.id = comp.id;
            container.appendChild(el);
        }

        el.style.left = `${comp.x}px`;
        el.style.top = `${comp.y}px`;
        el.style.transform = `rotate(${comp.rotation}deg)`;
        el.innerHTML = getComponentSVG(comp);

        el.addEventListener('click', (e) => {
            e.stopPropagation();
            selectComponent(comp.id);
        });

        makeComponentDraggable(el, comp);
    }

    function getComponentSVG(comp) {
        switch (comp.type) {
            case 'led':
                return `<div style="width:34px;height:44px;position:relative;">
                    <!-- Leads -->
                    <div style="position:absolute;bottom:0;left:8px;width:2px;height:18px;background:linear-gradient(to bottom,#cbd5e1,#64748b);"></div>
                    <div style="position:absolute;bottom:0;left:25px;width:2px;height:14px;background:linear-gradient(to bottom,#cbd5e1,#64748b);"></div>
                    <!-- Dome -->
                    <div id="${comp.id}_lens" style="position:absolute;top:2px;left:6px;width:22px;height:24px;border-radius:11px 11px 4px 4px;background:radial-gradient(ellipse at 40% 30%, ${comp.props.color}dd, ${comp.props.color});opacity:0.85;border:1px solid rgba(0,0,0,0.3);box-shadow:inset 0 -3px 5px rgba(0,0,0,0.25);transition:all 0.15s;"></div>
                    <div style="position:absolute;top:5px;left:10px;width:6px;height:5px;border-radius:50%;background:rgba(255,255,255,0.7);pointer-events:none;"></div>
                </div>`;

            case 'resistor':
                return `<div style="width:85px;height:20px;position:relative;display:flex;align-items:center;">
                    <!-- Left wire bent down into hole -->
                    <div style="width:25px;height:2.5px;background:linear-gradient(to right,#94a3b8,#cbd5e1);"></div>
                    <!-- Ceramic Body -->
                    <div style="width:35px;height:14px;background:linear-gradient(to bottom,#f6e7c1,#ebd49c,#dfc686);border-radius:4px;border:1px solid #c2a762;display:flex;justify-content:space-around;align-items:stretch;padding:0 3px;box-shadow:0 2px 4px rgba(0,0,0,0.2);">
                        <div style="width:3px;background:${getResistorBandColor(comp.props.resistance, 0)};border-radius:1px;"></div>
                        <div style="width:3px;background:${getResistorBandColor(comp.props.resistance, 1)};border-radius:1px;"></div>
                        <div style="width:3px;background:${getResistorBandColor(comp.props.resistance, 2)};border-radius:1px;"></div>
                        <div style="width:3px;background:#c8a820;border-radius:1px;"></div>
                    </div>
                    <!-- Right wire bent down into hole -->
                    <div style="width:25px;height:2.5px;background:linear-gradient(to left,#94a3b8,#cbd5e1);"></div>
                </div>`;

            case 'pushbutton':
                return `<div style="width:44px;height:44px;position:relative;background:linear-gradient(to bottom,#e2e8f0,#cbd5e1);border-radius:5px;border:1.5px solid #94a3b8;box-shadow:0 2px 5px rgba(0,0,0,0.2);display:flex;align-items:center;justify-content:center;">
                    <div id="${comp.id}_cap" style="width:20px;height:20px;border-radius:50%;background:${comp.props.pressed ? '#1e293b' : '#334155'};box-shadow:${comp.props.pressed ? 'inset 0 2px 4px rgba(0,0,0,0.6)' : '0 2px 4px rgba(0,0,0,0.4)'};cursor:pointer;transition:all 0.1s;"></div>
                    <!-- 4 Pins -->
                    <div style="position:absolute;left:-4px;top:8px;width:4px;height:4px;background:#94a3b8;"></div>
                    <div style="position:absolute;left:-4px;bottom:8px;width:4px;height:4px;background:#94a3b8;"></div>
                    <div style="position:absolute;right:-4px;top:8px;width:4px;height:4px;background:#94a3b8;"></div>
                    <div style="position:absolute;right:-4px;bottom:8px;width:4px;height:4px;background:#94a3b8;"></div>
                </div>`;

            case 'potentiometer':
                const angle = (comp.props.position || 0.5) * 270 - 135;
                return `<div style="width:50px;height:48px;position:relative;">
                    <div style="width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,#0284c7,#0369a1);border:2px solid #015a8a;box-shadow:0 3px 8px rgba(0,0,0,0.25);display:flex;align-items:center;justify-content:center;margin:0 auto;">
                        <div style="width:24px;height:24px;border-radius:50%;background:#0f172a;display:flex;align-items:center;justify-content:center;">
                            <div id="${comp.id}_dial" style="width:4px;height:12px;background:#38bdf8;border-radius:2px;transform:rotate(${angle}deg);transform-origin:center bottom;"></div>
                        </div>
                    </div>
                </div>`;

            case 'ldr':
                return `<div style="width:34px;height:40px;position:relative;">
                    <div style="width:30px;height:30px;border-radius:50%;background:radial-gradient(circle,#ea580c,#c2410c);border:2px solid #9a3412;box-shadow:0 3px 6px rgba(0,0,0,0.25);display:flex;align-items:center;justify-content:center;margin:0 auto;">
                        <svg width="18" height="14" viewBox="0 0 18 14"><path d="M 3 3 Q 9 5 15 3 Q 9 7 3 9 Q 9 11 15 9" fill="none" stroke="#fef08a" stroke-width="1.6"/></svg>
                    </div>
                    <div style="position:absolute;bottom:0;left:8px;width:2px;height:10px;background:#94a3b8;"></div>
                    <div style="position:absolute;bottom:0;right:8px;width:2px;height:10px;background:#94a3b8;"></div>
                </div>`;

            case 'ultrasonic':
                return `<div style="width:90px;height:44px;position:relative;background:linear-gradient(to bottom,#0ea5e9,#0284c7);border-radius:6px;border:2px solid #0369a1;display:flex;justify-content:space-around;align-items:center;padding:2px 6px;box-shadow:0 4px 10px rgba(0,0,0,0.25);">
                    <div style="width:28px;height:28px;border-radius:50%;background:radial-gradient(circle,#f8fafc,#cbd5e1);border:2px solid #94a3b8;font-weight:800;font-size:0.65rem;display:flex;align-items:center;justify-content:center;color:#0f172a;">T</div>
                    <div style="width:28px;height:28px;border-radius:50%;background:radial-gradient(circle,#f8fafc,#cbd5e1);border:2px solid #94a3b8;font-weight:800;font-size:0.65rem;display:flex;align-items:center;justify-content:center;color:#0f172a;">R</div>
                </div>`;

            default:
                return `<div style="width:36px;height:36px;background:#334155;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.75rem;">${comp.type.toUpperCase()}</div>`;
        }
    }

    function getResistorBandColor(resistance, bandIndex) {
        const colors = ['#0f172a', '#78350f', '#dc2626', '#f97316', '#eab308', '#10b981', '#2563eb', '#7c3aed', '#6b7280', '#ffffff'];
        const str = Math.round(resistance || 220).toString();
        if (bandIndex === 0) return colors[parseInt(str[0]) || 0];
        if (bandIndex === 1) return colors[parseInt(str[1]) || 0];
        const multiplier = str.length - 2;
        return colors[Math.max(0, Math.min(9, multiplier))];
    }

    function registerComponentTerminals(comp) {
        const lib = COMPONENT_LIBRARY[comp.type];
        if (!lib) return;

        lib.terminalOffsets.forEach(t => {
            const termId = `${comp.id}_${t.id}`;
            const posX = comp.x + t.dx;
            const posY = comp.y + t.dy;
            registerTerminal(termId, `${comp.props.name} ${t.label}`, posX, posY);
        });
    }

    function makeComponentDraggable(el, comp) {
        let isDragging = false;
        let startX, startY;
        let moved = false;

        el.addEventListener('mousedown', (e) => {
            if (e.button !== 0 || state.drawingWire) return;
            isDragging = true;
            moved = false;
            startX = e.clientX;
            startY = e.clientY;

            const origX = comp.x;
            const origY = comp.y;

            function onMouseMove(moveEvent) {
                if (!isDragging) return;
                const dx = (moveEvent.clientX - startX) / state.zoom;
                const dy = (moveEvent.clientY - startY) / state.zoom;

                if (Math.abs(dx) > 2 || Math.abs(dy) > 2) moved = true;

                comp.x += dx;
                comp.y += dy;
                startX = moveEvent.clientX;
                startY = moveEvent.clientY;

                el.style.left = `${comp.x}px`;
                el.style.top = `${comp.y}px`;

                registerComponentTerminals(comp);
                renderWires();
            }

            function onMouseUp() {
                if (moved) {
                    const newX = comp.x, newY = comp.y;
                    pushUndo({
                        type: 'moveComponent',
                        undo: () => { comp.x = origX; comp.y = origY; el.style.left = `${origX}px`; el.style.top = `${origY}px`; registerComponentTerminals(comp); renderWires(); analyzeCircuit(); },
                        redo: () => { comp.x = newX; comp.y = newY; el.style.left = `${newX}px`; el.style.top = `${newY}px`; registerComponentTerminals(comp); renderWires(); analyzeCircuit(); }
                    });
                    analyzeCircuit();
                }
                isDragging = false;
                window.removeEventListener('mousemove', onMouseMove);
                window.removeEventListener('mouseup', onMouseUp);
            }

            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
        });
    }

    // ==========================================
    // 10. DRAG & DROP FROM PALETTE
    // ==========================================
    function bindDragAndDrop() {
        document.querySelectorAll('.tc-component-card').forEach(card => {
            card.addEventListener('mousedown', (e) => {
                const type = card.getAttribute('data-component-type');
                if (type === 'arduino' || type === 'breadboard' || !COMPONENT_LIBRARY[type]) return;
                e.preventDefault();
                startPaletteDrag(type, card, e);
            });
        });
    }

    function startPaletteDrag(type, card, startEvent) {
        card.classList.add('dragging');

        const ghost = document.createElement('div');
        ghost.className = 'tc-drag-ghost';
        ghost.innerHTML = card.querySelector('.tc-component-card-icon').innerHTML;
        ghost.style.position = 'fixed';
        ghost.style.left = `${startEvent.clientX - 20}px`;
        ghost.style.top = `${startEvent.clientY - 20}px`;
        ghost.style.zIndex = '9999';
        ghost.style.pointerEvents = 'none';
        document.body.appendChild(ghost);

        state.dragGhost = ghost;
        state.dragType = type;

        function onMouseMove(e) {
            ghost.style.left = `${e.clientX - 20}px`;
            ghost.style.top = `${e.clientY - 20}px`;
        }

        function onMouseUp(e) {
            card.classList.remove('dragging');
            ghost.remove();
            state.dragGhost = null;

            const canvas = document.getElementById('tcCanvasContainer');
            if (canvas) {
                const rect = canvas.getBoundingClientRect();
                if (e.clientX >= rect.left && e.clientX <= rect.right &&
                    e.clientY >= rect.top && e.clientY <= rect.bottom) {
                    const stageRect = document.getElementById('tcCanvasStage').getBoundingClientRect();
                    const dropX = (e.clientX - stageRect.left) / state.zoom;
                    const dropY = (e.clientY - stageRect.top) / state.zoom;

                    const comp = placeComponent(type, dropX - 15, dropY - 15);
                    if (comp) {
                        selectComponent(comp.id);
                        pushUndo({
                            type: 'placeComponent',
                            undo: () => { removeComponentById(comp.id); },
                            redo: () => { placeComponent(comp.type, comp.x, comp.y, comp.rotation, comp.props, comp.id); }
                        });
                        analyzeCircuit();
                    }
                }
            }

            state.dragType = null;
            window.removeEventListener('mousemove', onMouseMove);
            window.removeEventListener('mouseup', onMouseUp);
        }

        window.addEventListener('mousemove', onMouseMove);
        window.addEventListener('mouseup', onMouseUp);
    }

    // ==========================================
    // 11. SELECTION & INSPECTOR
    // ==========================================
    function selectWire(wireId) {
        deselectAll();
        state.selectedItem = { type: 'wire', id: wireId };
        const w = state.wires.find(item => item.id === wireId);
        if (w) updateWireColorDisplay(w.color);
        renderWires();
        updateStatusBar();
    }

    function selectComponent(compId) {
        deselectAll();
        state.selectedItem = { type: 'component', id: compId };
        const el = document.getElementById(compId);
        if (el) el.classList.add('selected');
        showComponentInspector(compId);
        updateStatusBar();
    }

    function deselectAll() {
        if (state.selectedItem?.type === 'component') {
            const el = document.getElementById(state.selectedItem.id);
            if (el) el.classList.remove('selected');
        }
        state.selectedItem = null;
        hideComponentInspector();
        renderWires();
        document.querySelectorAll('.tc-wire-waypoint').forEach(w => w.remove());
        updateStatusBar();
    }

    function rotateSelected() {
        if (state.selectedItem?.type !== 'component') return;
        const comp = state.components.find(c => c.id === state.selectedItem.id);
        if (!comp) return;

        const oldRot = comp.rotation;
        comp.rotation = (comp.rotation + 90) % 360;
        const el = document.getElementById(comp.id);
        if (el) el.style.transform = `rotate(${comp.rotation}deg)`;

        registerComponentTerminals(comp);
        renderWires();
        analyzeCircuit();

        pushUndo({
            type: 'rotateComponent',
            undo: () => { comp.rotation = oldRot; if (el) el.style.transform = `rotate(${oldRot}deg)`; registerComponentTerminals(comp); renderWires(); analyzeCircuit(); },
            redo: () => { comp.rotation = (oldRot + 90) % 360; if (el) el.style.transform = `rotate(${comp.rotation}deg)`; registerComponentTerminals(comp); renderWires(); analyzeCircuit(); }
        });
    }

    function deleteSelected() {
        if (!state.selectedItem) return;

        if (state.selectedItem.type === 'wire') {
            const wireId = state.selectedItem.id;
            const wire = state.wires.find(w => w.id === wireId);
            state.wires = state.wires.filter(w => w.id !== wireId);

            if (wire) {
                pushUndo({
                    type: 'deleteWire',
                    undo: () => { state.wires.push({ ...wire }); renderWires(); analyzeCircuit(); },
                    redo: () => { state.wires = state.wires.filter(w => w.id !== wireId); renderWires(); analyzeCircuit(); }
                });
            }
        } else if (state.selectedItem.type === 'component') {
            const id = state.selectedItem.id;
            const comp = state.components.find(c => c.id === id);
            const connectedWires = state.wires.filter(w => w.from.startsWith(id) || w.to.startsWith(id));

            removeComponentById(id);

            if (comp) {
                pushUndo({
                    type: 'deleteComponent',
                    undo: () => {
                        placeComponent(comp.type, comp.x, comp.y, comp.rotation, comp.props, comp.id);
                        connectedWires.forEach(w => state.wires.push({ ...w }));
                        renderWires();
                        analyzeCircuit();
                    },
                    redo: () => { removeComponentById(id); analyzeCircuit(); }
                });
            }
        }

        deselectAll();
        renderWires();
        analyzeCircuit();
    }

    function removeComponentById(id) {
        state.components = state.components.filter(c => c.id !== id);
        const el = document.getElementById(id);
        if (el) el.remove();
        state.wires = state.wires.filter(w => !w.from.startsWith(id) && !w.to.startsWith(id));

        Object.keys(terminals).forEach(tid => {
            if (tid.startsWith(id)) {
                const termEl = document.getElementById(`term_${tid}`);
                if (termEl) termEl.remove();
                delete terminals[tid];
            }
        });
    }

    function showComponentInspector(compId) {
        const comp = state.components.find(c => c.id === compId);
        const pop = document.getElementById('tcComponentInspector');
        const fields = document.getElementById('tcInspectorFields');
        const title = document.getElementById('tcInspectorName');
        if (!comp || !pop || !fields || !title) return;

        const lib = COMPONENT_LIBRARY[comp.type];
        title.textContent = `${lib ? lib.name : comp.type} — ${comp.props.name}`;

        // Ensure inspector does not overflow canvas or hide under drawer
        const inspectX = Math.max(30, Math.min(comp.x - 70, 750));
        const inspectY = Math.max(20, Math.min(comp.y - 120, 240));

        pop.style.left = `${inspectX}px`;
        pop.style.top = `${inspectY}px`;
        pop.style.display = 'block';

        let html = '';
        if (comp.type === 'led') {
            html = `<div class="tc-inspector-row"><span>Color:</span>
                <select id="tcLedColorSelect" class="tc-inspector-input" style="width:90px;">
                    <option value="#ef4444" ${comp.props.color === '#ef4444' ? 'selected' : ''}>Red</option>
                    <option value="#10b981" ${comp.props.color === '#10b981' ? 'selected' : ''}>Green</option>
                    <option value="#eab308" ${comp.props.color === '#eab308' ? 'selected' : ''}>Yellow</option>
                    <option value="#0284c7" ${comp.props.color === '#0284c7' ? 'selected' : ''}>Blue</option>
                    <option value="#ffffff" ${comp.props.color === '#ffffff' ? 'selected' : ''}>White</option>
                </select></div>
                <div class="tc-inspector-row"><span>Fwd V:</span><span>${comp.props.forwardVoltage || 2.0} V</span></div>`;
        } else if (comp.type === 'resistor') {
            html = `<div class="tc-inspector-row"><span>Resistance:</span>
                <input type="number" id="tcResVal" class="tc-inspector-input" value="${comp.props.resistance}" min="1" max="10000000">
                <span>Ω</span></div>`;
        } else if (comp.type === 'potentiometer') {
            html = `<div class="tc-inspector-row"><span>Max R:</span><span>${comp.props.resistance} Ω</span></div>
                <div class="tc-inspector-row"><span>Position:</span>
                <input type="range" id="tcPotSlider" min="0" max="100" value="${(comp.props.position || 0.5) * 100}" style="width:90px;"></div>`;
        } else if (comp.type === 'capacitor') {
            html = `<div class="tc-inspector-row"><span>Capacitance:</span>
                <input type="number" id="tcCapVal" class="tc-inspector-input" value="${comp.props.capacitance}" min="1">
                <span>${comp.props.unit}</span></div>`;
        } else {
            html = `<div style="color:var(--tc-text-muted);font-size:0.75rem;">Standard laboratory component</div>`;
        }

        fields.innerHTML = html;

        document.getElementById('tcLedColorSelect')?.addEventListener('change', function () {
            comp.props.color = this.value;
            renderComponentDOM(comp);
            if (state.isSimulating) setLedGlow(state.hardwareValues.digital13);
        });

        document.getElementById('tcResVal')?.addEventListener('input', function () {
            comp.props.resistance = parseFloat(this.value) || 220;
            renderComponentDOM(comp);
        });

        document.getElementById('tcPotSlider')?.addEventListener('input', function () {
            comp.props.position = parseInt(this.value) / 100;
            state.hardwareValues.potentiometer = Math.round(comp.props.position * 1023);
            const dial = document.getElementById(`${comp.id}_dial`);
            if (dial) {
                const angle = comp.props.position * 270 - 135;
                dial.style.transform = `rotate(${angle}deg)`;
            }
        });

        document.getElementById('tcCapVal')?.addEventListener('input', function () {
            comp.props.capacitance = parseFloat(this.value) || 100;
        });
    }

    function hideComponentInspector() {
        const pop = document.getElementById('tcComponentInspector');
        if (pop) pop.style.display = 'none';
    }

    // ==========================================
    // 12. CIRCUIT ANALYSIS (Breadboard Graph Solver)
    // ==========================================
    function analyzeCircuit() {
        state.circuitStatus = checkCircuitConnectivity();
        updateCircuitWarning();
    }

    function checkCircuitConnectivity() {
        if (state.wires.length === 0) return { valid: false, reason: 'no_wires' };

        const graph = {};
        function addEdge(u, v) {
            if (!u || !v) return;
            if (!graph[u]) graph[u] = new Set();
            if (!graph[v]) graph[v] = new Set();
            graph[u].add(v);
            graph[v].add(u);
        }

        // 1. Add all wire connections
        state.wires.forEach(w => addEdge(w.from, w.to));

        // 2. Add Breadboard 5-hole internal column buses
        for (let c = 1; c <= 30; c++) {
            // Upper bank a to e
            const upper = ['a', 'b', 'c', 'd', 'e'].map(r => `bb-${r}${c}`);
            for (let i = 0; i < upper.length - 1; i++) addEdge(upper[i], upper[i + 1]);

            // Lower bank f to j
            const lower = ['f', 'g', 'h', 'i', 'j'].map(r => `bb-${r}${c}`);
            for (let i = 0; i < lower.length - 1; i++) addEdge(lower[i], lower[i + 1]);
        }

        // 3. Add Breadboard 30-hole power rails (+ and -)
        for (let c = 1; c < 30; c++) {
            addEdge(`bb-top-pos-${c}`, `bb-top-pos-${c + 1}`);
            addEdge(`bb-top-neg-${c}`, `bb-top-neg-${c + 1}`);
            addEdge(`bb-bot-pos-${c}`, `bb-bot-pos-${c + 1}`);
            addEdge(`bb-bot-neg-${c}`, `bb-bot-neg-${c + 1}`);
        }

        // 4. Add component internal continuity
        state.components.forEach(comp => {
            if (comp.type === 'led') {
                addEdge(`${comp.id}_anode`, `${comp.id}_cathode`);
            } else if (comp.type === 'resistor' || comp.type === 'ldr' || comp.type === 'capacitor' || comp.type === 'diode') {
                addEdge(`${comp.id}_t1`, `${comp.id}_t2`);
            } else if (comp.type === 'potentiometer') {
                addEdge(`${comp.id}_t1`, `${comp.id}_wiper`);
                addEdge(`${comp.id}_wiper`, `${comp.id}_t2`);
            } else if (comp.type === 'pushbutton') {
                addEdge(`${comp.id}_a1`, `${comp.id}_a2`);
                addEdge(`${comp.id}_b1`, `${comp.id}_b2`);
                if (comp.props.pressed) addEdge(`${comp.id}_a1`, `${comp.id}_b1`);
            } else if (comp.type === 'buzzer') {
                addEdge(`${comp.id}_pos`, `${comp.id}_neg`);
            } else if (comp.type === 'ultrasonic') {
                addEdge(`${comp.id}_vcc`, `${comp.id}_gnd`);
            }
        });

        // 5. Connect component leads to overlapping breadboard holes (within 12px)
        state.components.forEach(comp => {
            const lib = COMPONENT_LIBRARY[comp.type];
            if (!lib) return;
            lib.terminalOffsets.forEach(t => {
                const termId = `${comp.id}_${t.id}`;
                const term = terminals[termId];
                if (!term) return;

                for (const [hId, hTerm] of Object.entries(terminals)) {
                    if (hId.startsWith('bb-')) {
                        const dist = Math.hypot(term.x - hTerm.x, term.y - hTerm.y);
                        if (dist <= 12) {
                            addEdge(termId, hId);
                        }
                    }
                }
            });
        });

        // 6. Check BFS path from any active Arduino Signal/Power pin to any GND
        const ardPins = Object.keys(terminals).filter(id => id.startsWith('ard-pin'));
        const gndPins = ardPins.filter(id => id.includes('gnd'));
        const sourcePins = ardPins.filter(id => !id.includes('gnd') && graph[id]);

        for (const src of sourcePins) {
            const visited = new Set();
            const queue = [src];
            visited.add(src);

            while (queue.length > 0) {
                const curr = queue.shift();
                if (gndPins.includes(curr) || curr.includes('neg')) {
                    return { valid: true };
                }
                if (graph[curr]) {
                    for (const next of graph[curr]) {
                        if (!visited.has(next)) {
                            visited.add(next);
                            queue.push(next);
                        }
                    }
                }
            }
        }

        return { valid: false, reason: 'open_circuit' };
    }

    function updateCircuitWarning() {
        const el = document.getElementById('tcCircuitWarning');
        if (!el) return;

        if (!state.isSimulating) {
            el.classList.remove('visible', 'ok');
            return;
        }

        if (state.circuitStatus.valid) {
            el.className = 'tc-circuit-warning visible ok';
            el.innerHTML = '<i class="fa-solid fa-circle-check"></i> Circuit Active: Closed loop verified • Live telemetry online';
        } else {
            el.className = 'tc-circuit-warning visible';
            if (state.wires.length === 0) {
                el.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Open Circuit — Click "Auto-Wire" or draw wires between Arduino and breadboard';
            } else {
                el.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Open Circuit — Connect signal pin through breadboard components back to GND';
            }
        }
    }

    // ==========================================
    // 13. SIMULATION ENGINE
    // ==========================================
    function toggleSimulation() {
        if (state.isSimulating) stopSimulation();
        else startSimulation();
    }

    function startSimulation() {
        state.isSimulating = true;
        state.simStartTime = Date.now();
        state.simTick = 0;
        analyzeCircuit();

        const btn = document.getElementById('tcStartSimBtn');
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-stop"></i> Stop Simulation';
            btn.className = 'btn-tc-sim stop';
        }

        const onLed = document.getElementById('arduinoOnLed');
        if (onLed) onLed.setAttribute('fill', '#22c55e');

        appendSerial('--- Firmware Simulation Started: 16 MHz Clock Online ---\n');

        let pwmVal = 0;
        let pwmDir = 5;

        state.simInterval = setInterval(() => {
            state.simTick++;
            updateSimTimer();

            const isCircuitClosed = state.circuitStatus.valid;

            if (state.currentPreset === 'blink') {
                const isHigh = Math.floor(state.simTick / 16) % 2 === 0;
                state.hardwareValues.digital13 = isHigh;
                setPinBuiltin(isHigh);
                setLedGlow(isHigh && isCircuitClosed);

                if (state.simTick % 16 === 0) {
                    appendSerial(isHigh ? 'LED State: ON  (5.00 V)\n' : 'LED State: OFF (0.00 V)\n');
                }
            } else if (state.currentPreset === 'pwm_fade') {
                pwmVal += pwmDir;
                if (pwmVal >= 255 || pwmVal <= 0) pwmDir = -pwmDir;
                state.hardwareValues.pwmDuty = pwmVal;
                const vEff = (pwmVal / 255.0) * 5.0;

                setPinBuiltin(pwmVal > 128);
                setLedPWM(isCircuitClosed ? pwmVal : 0);

                if (state.simTick % 4 === 0) {
                    appendSerial(`PWM: ${pwmVal} | V_eff: ${vEff.toFixed(2)} V\n`);
                    pushPlotter(vEff);
                }
            } else if (state.currentPreset === 'potentiometer') {
                const raw = state.hardwareValues.potentiometer;
                const v = (raw / 1023.0) * 5.0;
                if (state.simTick % 4 === 0) {
                    appendSerial(`ADC A0: ${raw} | Voltage: ${v.toFixed(3)} V\n`);
                    pushPlotter(v);
                }
            } else if (state.currentPreset === 'ldr_sensor') {
                const lux = state.hardwareValues.ldrLux;
                const adc = Math.round(1023 * (lux / (lux + 300)));
                const v = (adc / 1023.0) * 5.0;
                if (state.simTick % 5 === 0) {
                    appendSerial(`Lux: ${lux} | A1: ${adc} (${v.toFixed(2)} V)\n`);
                    pushPlotter(v);
                }
            } else if (state.currentPreset === 'ultrasonic') {
                const cm = state.hardwareValues.ultrasonicCm;
                const us = Math.round(cm * 2 / 0.0343);
                if (state.simTick % 5 === 0) {
                    appendSerial(`Echo Transit: ${us} µs | Distance: ${cm.toFixed(1)} cm\n`);
                    pushPlotter(cm);
                }
            }

            updateCircuitWarning();
        }, 50);
    }

    function stopSimulation() {
        state.isSimulating = false;
        if (state.simInterval) clearInterval(state.simInterval);

        const btn = document.getElementById('tcStartSimBtn');
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-play"></i> Start Simulation';
            btn.className = 'btn-tc-sim start';
        }

        const onLed = document.getElementById('arduinoOnLed');
        if (onLed) onLed.setAttribute('fill', '#334155');

        setPinBuiltin(false);
        setLedGlow(false);
        updateCircuitWarning();
        appendSerial('--- Simulation Stopped ---\n');
    }

    function setPinBuiltin(val) {
        const lLed = document.getElementById('arduinoBuiltinLed');
        if (lLed) lLed.setAttribute('fill', val ? '#eab308' : '#334155');
    }

    function setLedGlow(isLit) {
        state.components.filter(c => c.type === 'led').forEach(comp => {
            const lens = document.getElementById(`${comp.id}_lens`);
            if (lens) {
                if (isLit) {
                    lens.classList.add('tc-led-lit');
                    lens.style.opacity = '1';
                    lens.style.boxShadow = `0 0 14px ${comp.props.color}, 0 0 28px ${comp.props.color}70`;
                } else {
                    lens.classList.remove('tc-led-lit');
                    lens.style.opacity = '0.85';
                    lens.style.boxShadow = 'inset 0 -3px 5px rgba(0,0,0,0.25)';
                }
            }
        });
    }

    function setLedPWM(pwm) {
        const opacity = Math.max(0.25, pwm / 255.0);
        state.components.filter(c => c.type === 'led').forEach(comp => {
            const lens = document.getElementById(`${comp.id}_lens`);
            if (lens) {
                lens.style.opacity = opacity.toString();
                if (pwm > 20) {
                    lens.classList.add('tc-led-lit');
                    lens.style.boxShadow = `0 0 ${Math.round(pwm / 18)}px ${comp.props.color}, 0 0 ${Math.round(pwm / 9)}px ${comp.props.color}40`;
                } else {
                    lens.classList.remove('tc-led-lit');
                    lens.style.boxShadow = 'inset 0 -3px 5px rgba(0,0,0,0.25)';
                }
            }
        });
    }

    function updateSimTimer() {
        const el = document.getElementById('tcSimTimer');
        if (!el) return;
        const diff = Date.now() - state.simStartTime;
        const min = Math.floor(diff / 60000);
        const sec = Math.floor((diff % 60000) / 1000);
        const ms = diff % 1000;
        el.textContent = `${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}.${String(ms).padStart(3, '0')}`;
    }

    function appendSerial(msg) {
        state.serialLogs.push(msg);
        if (state.serialLogs.length > 300) state.serialLogs.shift();
        const stream = document.getElementById('tcSerialStream');
        if (stream) {
            stream.textContent = state.serialLogs.join('');
            stream.scrollTop = stream.scrollHeight;
        }
    }

    function pushPlotter(val) {
        state.plotterData.push(val);
        if (state.plotterData.length > 100) state.plotterData.shift();
        drawPlotter();
    }

    function drawPlotter() {
        const canvas = document.getElementById('tcSerialPlotterCanvas');
        if (!canvas || canvas.style.display === 'none') return;

        const ctx = canvas.getContext('2d');
        const w = canvas.width;
        const h = canvas.height;

        ctx.fillStyle = '#020617';
        ctx.fillRect(0, 0, w, h);

        ctx.strokeStyle = 'rgba(255, 255, 255, 0.07)';
        ctx.lineWidth = 1;
        for (let y = 20; y < h; y += 25) {
            ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(w, y); ctx.stroke();
        }
        for (let x = 0; x < w; x += 40) {
            ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, h); ctx.stroke();
        }

        if (state.plotterData.length < 2) return;

        const minVal = Math.min(...state.plotterData, 0);
        const maxVal = Math.max(...state.plotterData, 5.0);
        const range = (maxVal - minVal) || 1;

        const gradient = ctx.createLinearGradient(0, 0, 0, h);
        gradient.addColorStop(0, 'rgba(56, 189, 248, 0.25)');
        gradient.addColorStop(1, 'rgba(56, 189, 248, 0)');

        const stepX = w / 100;
        ctx.beginPath();
        state.plotterData.forEach((pt, i) => {
            const x = i * stepX;
            const y = h - 15 - ((pt - minVal) / range) * (h - 30);
            if (i === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        });

        const lastX = (state.plotterData.length - 1) * stepX;
        ctx.lineTo(lastX, h);
        ctx.lineTo(0, h);
        ctx.fillStyle = gradient;
        ctx.fill();

        ctx.beginPath();
        state.plotterData.forEach((pt, i) => {
            const x = i * stepX;
            const y = h - 15 - ((pt - minVal) / range) * (h - 30);
            if (i === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        });
        ctx.strokeStyle = '#38bdf8';
        ctx.lineWidth = 2;
        ctx.stroke();

        ctx.fillStyle = '#64748b';
        ctx.font = '10px "JetBrains Mono", monospace';
        ctx.fillText(`Max: ${maxVal.toFixed(2)}`, 10, 14);
        ctx.fillText(`Min: ${minVal.toFixed(2)}`, 10, h - 4);

        const latest = state.plotterData[state.plotterData.length - 1];
        ctx.fillStyle = '#38bdf8';
        ctx.font = '11px "JetBrains Mono", monospace';
        ctx.fillText(`${latest.toFixed(2)}`, w - 55, 14);
    }

    // ==========================================
    // 14. PRESET LOADER
    // ==========================================
    function loadPreset(key) {
        if (!presets[key]) return;
        state.currentPreset = key;
        const p = presets[key];

        const sel = document.getElementById('tcPresetSelector');
        if (sel && sel.value !== key) sel.value = key;

        state.components = [];
        state.wires = [];
        state.undoStack = [];
        state.redoStack = [];

        const compContainer = document.getElementById('tcComponentsContainer');
        if (compContainer) compContainer.innerHTML = '';

        initTerminals();
        deselectAll();

        p.components.forEach(c => {
            placeComponent(c.type, c.x, c.y, c.rotation, c.props, c.id);
        });

        if (codeEditor) {
            codeEditor.setValue(p.code);
        } else {
            const ta = document.getElementById('tcCodeTextarea');
            if (ta) ta.value = p.code;
        }

        autoWirePreset();
        appendSerial(`--- Loaded Lab Preset: ${p.title} ---\n`);

        if (state.isSimulating) {
            stopSimulation();
            startSimulation();
        }
        updateStatusBar();
    }

    function autoWirePreset() {
        const p = presets[state.currentPreset];
        if (!p || !p.autoWires) return;

        state.wires = [];
        p.autoWires.forEach((w, idx) => {
            state.wires.push({
                id: `wire_auto_${idx}`,
                from: w.from,
                to: w.to,
                color: w.color,
                waypoints: w.waypoints ? [...w.waypoints] : []
            });
        });

        renderWires();
        analyzeCircuit();
    }

    function clearAllWires() {
        const oldWires = [...state.wires];
        state.wires = [];
        deselectAll();
        renderWires();
        analyzeCircuit();
        appendSerial('--- Wires cleared for freeform breadboard practice ---\n');

        pushUndo({
            type: 'clearAllWires',
            undo: () => { state.wires = oldWires; renderWires(); analyzeCircuit(); },
            redo: () => { state.wires = []; renderWires(); analyzeCircuit(); }
        });
    }

    function renderAll() {
        const compContainer = document.getElementById('tcComponentsContainer');
        if (compContainer) compContainer.innerHTML = '';
        initTerminals();

        state.components.forEach(comp => {
            renderComponentDOM(comp);
            registerComponentTerminals(comp);
        });

        renderWires();
        analyzeCircuit();
        updateStatusBar();
    }

    // ==========================================
    // 15. UI BINDINGS & CONTROLS
    // ==========================================
    function bindUI() {
        document.getElementById('tcPresetSelector')?.addEventListener('change', function () {
            loadPreset(this.value);
        });

        document.getElementById('tcRotateBtn')?.addEventListener('click', rotateSelected);
        document.getElementById('tcDeleteBtn')?.addEventListener('click', deleteSelected);
        document.getElementById('tcUndoBtn')?.addEventListener('click', undo);
        document.getElementById('tcRedoBtn')?.addEventListener('click', redo);

        window.addEventListener('keydown', (e) => {
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;
            if (e.key === 'r' || e.key === 'R') rotateSelected();
            if (e.key === 'Delete' || e.key === 'Backspace') deleteSelected();
            if (e.key === 'Escape') cancelWireDrawing();
            if ((e.ctrlKey || e.metaKey) && e.key === 'z') { e.preventDefault(); undo(); }
            if ((e.ctrlKey || e.metaKey) && e.key === 'y') { e.preventDefault(); redo(); }
        });

        const colorBtn = document.getElementById('tcWireColorBtn');
        const popover = document.getElementById('tcColorPalettePopover');
        if (colorBtn && popover) {
            colorBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                popover.style.display = popover.style.display === 'none' ? 'grid' : 'none';
            });

            popover.querySelectorAll('.tc-color-option').forEach(opt => {
                opt.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const color = this.getAttribute('data-color');
                    const name = this.getAttribute('data-name');
                    setWireColor(color, name);
                    popover.querySelectorAll('.tc-color-option').forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                    popover.style.display = 'none';
                });
            });

            document.addEventListener('click', () => { popover.style.display = 'none'; });
        }

        document.getElementById('tcToggleCodeBtn')?.addEventListener('click', function () {
            const codePane = document.getElementById('tcCodePane');
            const compPane = document.getElementById('tcComponentsPalettePane');
            if (!codePane || !compPane) return;

            const isCode = codePane.style.display === 'flex';
            if (isCode) {
                codePane.style.display = 'none';
                compPane.style.display = 'flex';
                this.classList.remove('active');
            } else {
                codePane.style.display = 'flex';
                compPane.style.display = 'none';
                this.classList.add('active');
                if (codeEditor) codeEditor.refresh();
            }
        });

        document.getElementById('tcStartSimBtn')?.addEventListener('click', toggleSimulation);
        document.getElementById('tcAutoWireBtn')?.addEventListener('click', autoWirePreset);
        document.getElementById('tcClearWiresBtn')?.addEventListener('click', clearAllWires);

        document.querySelectorAll('.tc-component-card').forEach(card => {
            card.addEventListener('click', function () {
                if (state.dragType) return;
                const type = this.getAttribute('data-component-type');
                if (type === 'arduino' || type === 'breadboard' || !COMPONENT_LIBRARY[type]) return;
                const comp = placeComponent(type, 650 + Math.random() * 40, 160 + Math.random() * 40);
                if (comp) selectComponent(comp.id);
            });
        });

        document.getElementById('tcCloseInspectorBtn')?.addEventListener('click', hideComponentInspector);

        const stage = document.getElementById('tcCanvasStage');
        if (stage) {
            stage.addEventListener('click', handleCanvasClick);
            stage.addEventListener('mousemove', handleCanvasMouseMove);
        }

        document.getElementById('tcClearSerialBtn')?.addEventListener('click', () => {
            state.serialLogs = [];
            const str = document.getElementById('tcSerialStream');
            if (str) str.textContent = '';
            state.plotterData = [];
            drawPlotter();
        });

        const tabMonitor = document.getElementById('tcSerialTabMonitor');
        const tabPlotter = document.getElementById('tcSerialTabPlotter');
        const viewMonitor = document.getElementById('tcSerialStream');
        const viewPlotter = document.getElementById('tcSerialPlotterCanvas');

        if (tabMonitor && tabPlotter) {
            tabMonitor.addEventListener('click', () => {
                tabMonitor.classList.add('active'); tabPlotter.classList.remove('active');
                if (viewMonitor) viewMonitor.style.display = 'block';
                if (viewPlotter) viewPlotter.style.display = 'none';
            });
            tabPlotter.addEventListener('click', () => {
                tabPlotter.classList.add('active'); tabMonitor.classList.remove('active');
                if (viewMonitor) viewMonitor.style.display = 'none';
                if (viewPlotter) { viewPlotter.style.display = 'block'; drawPlotter(); }
            });
        }

        document.getElementById('tcSearchInput')?.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.tc-component-card').forEach(card => {
                const name = card.querySelector('.tc-component-card-name')?.textContent.toLowerCase() || '';
                card.style.display = name.includes(query) ? '' : 'none';
            });
        });
    }

    function setWireColor(color, name) {
        state.selectedWireColor = color;
        state.selectedWireName = name;
        updateWireColorDisplay(color, name);

        if (state.selectedItem?.type === 'wire') {
            const w = state.wires.find(item => item.id === state.selectedItem.id);
            if (w) { w.color = color; renderWires(); }
        }
    }

    function updateWireColorDisplay(color, name) {
        const swatch = document.getElementById('tcCurrentColorSwatch');
        const label = document.getElementById('tcCurrentColorName');
        if (swatch) swatch.style.background = color;
        if (label && name) label.textContent = name;
    }

    function updateStatusBar() {
        const bar = document.getElementById('tcStatusBar');
        if (!bar) return;

        const compCount = state.components.length;
        const wireCount = state.wires.length;
        let selText = 'None';

        if (state.selectedItem) {
            if (state.selectedItem.type === 'wire') {
                const w = state.wires.find(ww => ww.id === state.selectedItem.id);
                selText = w ? `Wire (${w.color})` : 'Wire';
            } else {
                const c = state.components.find(cc => cc.id === state.selectedItem.id);
                selText = c ? `${c.props.name} (${c.type.toUpperCase()})` : 'Component';
            }
        }

        bar.innerHTML = `
            <div class="tc-status-item"><i class="fa-solid fa-microchip" style="color:#0284c7;"></i> Components: ${compCount}</div>
            <div class="tc-status-item"><i class="fa-solid fa-plug" style="color:#10b981;"></i> Wires: ${wireCount}</div>
            <div class="tc-status-item"><i class="fa-solid fa-hand-pointer" style="color:#f59e0b;"></i> Selected: ${selText}</div>
            <div class="tc-status-item"><i class="fa-solid fa-magnifying-glass" style="color:#64748b;"></i> ${Math.round(state.zoom * 100)}%</div>
        `;
    }

    function setupCanvasPanning() {
        const container = document.getElementById('tcCanvasContainer');
        const stage = document.getElementById('tcCanvasStage');
        if (!container || !stage) return;

        document.getElementById('tcZoomInBtn')?.addEventListener('click', () => { setZoom(state.zoom + 0.1); updateStatusBar(); });
        document.getElementById('tcZoomOutBtn')?.addEventListener('click', () => { setZoom(state.zoom - 0.1); updateStatusBar(); });
        document.getElementById('tcZoomResetBtn')?.addEventListener('click', () => {
            state.panX = 0; state.panY = 0;
            setZoom(1.0); updateStatusBar();
        });

        container.addEventListener('wheel', (e) => {
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.05 : 0.05;
            setZoom(state.zoom + delta);
            updateStatusBar();
        }, { passive: false });
    }

    function setZoom(val) {
        state.zoom = Math.max(0.5, Math.min(2.0, val));
        const stage = document.getElementById('tcCanvasStage');
        if (stage) {
            stage.style.transform = `translate(${state.panX}px, ${state.panY}px) scale(${state.zoom})`;
        }
    }

    // ==========================================
    // 16. BOOTSTRAP
    // ==========================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.TinkercadClone = {
        state, loadPreset, autoWirePreset, clearAllWires,
        startSimulation, stopSimulation, placeComponent,
        undo, redo
    };

})();
