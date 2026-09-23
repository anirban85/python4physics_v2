/**
 * Python4Physics - Advanced Tinkercad Circuits Clone Simulator Engine
 * Features:
 * - Interactive Multi-Point Orthogonal Wire Drawing with Waypoints & Snapping
 * - Component Palette with Pick & Place onto Photorealistic Breadboard
 * - Component Selection, Rotation (R), Deletion (Del), and Inspector Popover
 * - Breadboard Bus Electrical Connectivity & Closed-Loop Circuit Validation
 * - Live C++ Firmware Execution, Serial Monitor (9600 baud) & Serial Plotter
 */

(function () {
    'use strict';

    // ==========================================
    // GLOBAL STATE
    // ==========================================
    const state = {
        isSimulating: false,
        simStartTime: 0,
        simInterval: null,
        currentPreset: 'blink',
        selectedWireColor: '#10b981', // Green by default
        selectedWireName: 'Green',
        wireType: 'normal',
        
        // Active Wire Drawing State
        drawingWire: null, // { startTerminalId, waypoints: [{x, y}, ...] }
        hoverTerminalId: null,
        
        // Selection State
        selectedItem: null, // { type: 'wire' | 'component', id: string }
        
        // Canvas Pan & Zoom
        zoom: 1.0,
        panX: 0,
        panY: 0,
        isPanning: false,
        panStartX: 0,
        panStartY: 0,

        // Placed Components & Wires
        components: [],
        wires: [],

        // Telemetry
        serialLogs: [],
        plotterData: [],
        hardwareValues: {
            potentiometer: 512,
            ldrLux: 400,
            ultrasonicCm: 35,
            blinkDelay: 800
        }
    };

    let codeEditor = null;

    // Preset Circuits Definition
    const presets = {
        blink: {
            title: "LED Blink & Optical Timing",
            components: [
                { type: 'led', x: 740, y: 175, rotation: 0, props: { color: '#ef4444', name: 'LED1' } },
                { type: 'resistor', x: 755, y: 220, rotation: 90, props: { resistance: 220, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                { from: 'ard-pin-13', to: 'comp_0_anode', color: '#ef4444', waypoints: [{ x: 180, y: 55 }, { x: 740, y: 55 }] },
                { from: 'comp_0_cathode', to: 'comp_1_t1', color: '#10b981', waypoints: [] },
                { from: 'comp_1_t2', to: 'bb-bot-neg-15', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-1', color: '#0f172a', waypoints: [{ x: 221, y: 340 }, { x: 470, y: 340 }] }
            ],
            code: `// ========================================================\n// Lab 01: Standard LED Blink & Optical Timing\n// Digital Pin 13 & Breadboard Red LED with 220 Ohm Resistor\n// ========================================================\n\nconst int ledPin = 13;\n\nvoid setup() {\n  pinMode(ledPin, OUTPUT);\n  Serial.begin(9600);\n  Serial.println("--- Optical Timing System Initialized ---");\n}\n\nvoid loop() {\n  digitalWrite(ledPin, HIGH);\n  Serial.println("LED State: ON  (5.00 V)");\n  delay(800);\n  \n  digitalWrite(ledPin, LOW);\n  Serial.println("LED State: OFF (0.00 V)");\n  delay(800);\n}`
        },
        pwm_fade: {
            title: "PWM Breathing & Effective DC Voltage",
            components: [
                { type: 'led', x: 740, y: 175, rotation: 0, props: { color: '#0284c7', name: 'LED1' } },
                { type: 'resistor', x: 755, y: 220, rotation: 90, props: { resistance: 220, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                { from: 'ard-pin-9', to: 'comp_0_anode', color: '#0284c7', waypoints: [{ x: 232, y: 55 }, { x: 740, y: 55 }] },
                { from: 'comp_0_cathode', to: 'comp_1_t1', color: '#10b981', waypoints: [] },
                { from: 'comp_1_t2', to: 'bb-bot-neg-15', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-1', color: '#0f172a', waypoints: [{ x: 221, y: 340 }, { x: 470, y: 340 }] }
            ],
            code: `// ========================================================\n// Lab 02: Pulse-Width Modulation (PWM) LED Breathing\n// Effective DC Voltage: V_eff = 5.0 * (Duty / 255.0)\n// ========================================================\n\nconst int pwmPin = 9;\nint brightness = 0;\nint fadeStep = 5;\n\nvoid setup() {\n  pinMode(pwmPin, OUTPUT);\n  Serial.begin(9600);\n  Serial.println("--- PWM Voltage Modulation Active ---");\n}\n\nvoid loop() {\n  analogWrite(pwmPin, brightness);\n  float vEff = (brightness / 255.0) * 5.0;\n  \n  Serial.print("Duty Cycle: ");\n  Serial.print(brightness);\n  Serial.print(" | V_eff: ");\n  Serial.print(vEff, 2);\n  Serial.println(" V");\n  \n  brightness += fadeStep;\n  if (brightness <= 0 || brightness >= 255) {\n    fadeStep = -fadeStep;\n  }\n  delay(30);\n}`
        },
        potentiometer: {
            title: "Potentiometer 10-Bit ADC Voltage Divider",
            components: [
                { type: 'potentiometer', x: 680, y: 190, rotation: 0, props: { resistance: 10000, name: 'POT1' } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'comp_0_t1', color: '#ef4444', waypoints: [{ x: 209, y: 335 }, { x: 672, y: 335 }] },
                { from: 'ard-pin-gnd1', to: 'comp_0_t2', color: '#0f172a', waypoints: [{ x: 221, y: 345 }, { x: 688, y: 345 }] },
                { from: 'ard-pin-a0', to: 'comp_0_wiper', color: '#10b981', waypoints: [{ x: 278, y: 325 }, { x: 680, y: 325 }] }
            ],
            code: `// ========================================================\n// Lab 03: Potentiometer Voltage Divider (Ohm's Law)\n// 10-Bit ADC Resolution: 5.0V / 1024 = 4.88 mV per count\n// ========================================================\n\nconst int potPin = A0;\n\nvoid setup() {\n  Serial.begin(9600);\n  Serial.println("--- 10-Bit ADC Acquisition Ready ---");\n}\n\nvoid loop() {\n  int rawADC = analogRead(potPin);\n  float voltage = (rawADC / 1023.0) * 5.0;\n  \n  Serial.print("ADC: ");\n  Serial.print(rawADC);\n  Serial.print(" | Voltage: ");\n  Serial.print(voltage, 3);\n  Serial.println(" V");\n  \n  delay(100);\n}`
        },
        ldr_sensor: {
            title: "Photoresistor (LDR) Solar Light Sensor",
            components: [
                { type: 'ldr', x: 680, y: 190, rotation: 0, props: { name: 'LDR1' } },
                { type: 'resistor', x: 710, y: 220, rotation: 90, props: { resistance: 10000, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'comp_0_t1', color: '#ef4444', waypoints: [] },
                { from: 'comp_0_t2', to: 'comp_1_t1', color: '#f59e0b', waypoints: [] },
                { from: 'comp_0_t2', to: 'ard-pin-a1', color: '#10b981', waypoints: [] },
                { from: 'comp_1_t2', to: 'ard-pin-gnd1', color: '#0f172a', waypoints: [] }
            ],
            code: `// ========================================================\n// Lab 04: LDR Photoelectric Sensor & Solar Insolation\n// Semiconductor photo-conductivity increases with lux\n// ========================================================\n\nconst int ldrPin = A1;\n\nvoid setup() {\n  Serial.begin(9600);\n  Serial.println("--- Photometric Telemetry Online ---");\n}\n\nvoid loop() {\n  int sensorVal = analogRead(ldrPin);\n  float vOut = sensorVal * (5.0 / 1023.0);\n  \n  Serial.print("LDR ADC: ");\n  Serial.print(sensorVal);\n  Serial.print(" | V_out: ");\n  Serial.print(vOut, 2);\n  Serial.println(" V");\n  \n  delay(200);\n}`
        },
        ultrasonic: {
            title: "Ultrasonic HC-SR04 Speed of Sound Rangefinder",
            components: [
                { type: 'ultrasonic', x: 700, y: 170, rotation: 0, props: { name: 'US1' } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'comp_0_vcc', color: '#ef4444', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'comp_0_gnd', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-9', to: 'comp_0_trig', color: '#0284c7', waypoints: [] },
                { from: 'ard-pin-8', to: 'comp_0_echo', color: '#8b5cf6', waypoints: [] }
            ],
            code: `// ========================================================\n// Lab 05: Ultrasonic HC-SR04 Speed of Sound & Range\n// Velocity of Sound in Air v = 343 m/s = 0.0343 cm/us\n// ========================================================\n\nconst int trigPin = 9;\nconst int echoPin = 8;\n\nvoid setup() {\n  pinMode(trigPin, OUTPUT);\n  pinMode(echoPin, INPUT);\n  Serial.begin(9600);\n  Serial.println("--- Ultrasonic Acoustic Telemetry Ready ---");\n}\n\nvoid loop() {\n  digitalWrite(trigPin, LOW);\n  delayMicroseconds(2);\n  digitalWrite(trigPin, HIGH);\n  delayMicroseconds(10);\n  digitalWrite(trigPin, LOW);\n  \n  long duration = pulseIn(echoPin, HIGH);\n  float distanceCm = duration * 0.0343 / 2.0;\n  \n  Serial.print("Echo Transit Time: ");\n  Serial.print(duration);\n  Serial.print(" us | Distance: ");\n  Serial.print(distanceCm, 1);\n  Serial.println(" cm");\n  \n  delay(250);\n}`
        }
    };

    // ==========================================
    // INITIALIZATION
    // ==========================================
    function init() {
        initCodeEditor();
        initTerminals();
        bindUI();
        loadPreset('blink');
        setupCanvasPanning();
    }

    // CodeMirror C++ IDE Init
    function initCodeEditor() {
        const textarea = document.getElementById('tcCodeTextarea');
        if (!textarea) return;

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

    // ==========================================
    // TERMINAL REGISTRY & PINS SETUP
    // ==========================================
    const terminals = {};

    function initTerminals() {
        const container = document.getElementById('tcTerminalsContainer');
        if (!container) return;
        container.innerHTML = '';

        // 1. Arduino Uno Pin Terminals (mapped to SVG header holes)
        // Arduino Board top-left is at (40, 80)
        const ardX = 40;
        const ardY = 80;

        // Top Digital Header Pins (x: 114 to 313, y: 27)
        const digitalPins = [
            { id: 'ard-pin-aref', name: 'AREF', x: 118, y: 31 },
            { id: 'ard-pin-gnd0', name: 'GND', x: 131, y: 31 },
            { id: 'ard-pin-13',   name: 'Digital 13 (SCK)', x: 144, y: 31 },
            { id: 'ard-pin-12',   name: 'Digital 12 (MISO)', x: 157, y: 31 },
            { id: 'ard-pin-11',   name: 'Digital 11 (~PWM)', x: 170, y: 31 },
            { id: 'ard-pin-10',   name: 'Digital 10 (~PWM)', x: 183, y: 31 },
            { id: 'ard-pin-9',    name: 'Digital 9 (~PWM)', x: 196, y: 31 },
            { id: 'ard-pin-8',    name: 'Digital 8', x: 209, y: 31 },
            { id: 'ard-pin-7',    name: 'Digital 7', x: 226, y: 31 },
            { id: 'ard-pin-6',    name: 'Digital 6 (~PWM)', x: 239, y: 31 },
            { id: 'ard-pin-5',    name: 'Digital 5 (~PWM)', x: 252, y: 31 },
            { id: 'ard-pin-4',    name: 'Digital 4', x: 265, y: 31 },
            { id: 'ard-pin-3',    name: 'Digital 3 (~PWM)', x: 278, y: 31 },
            { id: 'ard-pin-2',    name: 'Digital 2 (INT0)', x: 291, y: 31 },
            { id: 'ard-pin-tx',   name: 'Digital 1 (TX)', x: 304, y: 31 },
            { id: 'ard-pin-rx',   name: 'Digital 0 (RX)', x: 317, y: 31 }
        ];

        digitalPins.forEach(p => {
            registerTerminal(p.id, p.name, ardX + p.x, ardY + p.y);
        });

        // Bottom Power & Analog Pins (y: 217)
        const powerPins = [
            { id: 'ard-pin-ioref', name: 'IOREF', x: 137, y: 217 },
            { id: 'ard-pin-reset', name: 'RESET', x: 149, y: 217 },
            { id: 'ard-pin-3v3',   name: '3.3V Power', x: 161, y: 217 },
            { id: 'ard-pin-5v',    name: '5V Power', x: 173, y: 217 },
            { id: 'ard-pin-gnd1',  name: 'GND (Power)', x: 185, y: 217 },
            { id: 'ard-pin-gnd2',  name: 'GND (Power)', x: 197, y: 217 },
            { id: 'ard-pin-vin',   name: 'VIN', x: 209, y: 217 }
        ];

        powerPins.forEach(p => {
            registerTerminal(p.id, p.name, ardX + p.x, ardY + p.y);
        });

        const analogPins = [
            { id: 'ard-pin-a0', name: 'Analog In A0', x: 242, y: 217 },
            { id: 'ard-pin-a1', name: 'Analog In A1', x: 254, y: 217 },
            { id: 'ard-pin-a2', name: 'Analog In A2', x: 266, y: 217 },
            { id: 'ard-pin-a3', name: 'Analog In A3', x: 278, y: 217 },
            { id: 'ard-pin-a4', name: 'Analog In A4', x: 290, y: 217 },
            { id: 'ard-pin-a5', name: 'Analog In A5', x: 302, y: 217 }
        ];

        analogPins.forEach(p => {
            registerTerminal(p.id, p.name, ardX + p.x, ardY + p.y);
        });

        // 2. Breadboard Holes (mapped to DOM breadboard)
        // Breadboard top-left is at (450, 100)
        const bbX = 450;
        const bbY = 100;
        const colPitch = 18.2; // approx horizontal spacing for 30 cols in 600px width
        const xOffset = 38;

        for (let col = 1; col <= 30; col++) {
            const posX = bbX + xOffset + (col - 1) * colPitch;

            // Top Power Rails
            registerTerminal(`bb-top-pos-${col}`, `Breadboard (+) Rail [${col}]`, posX, bbY + 16);
            registerTerminal(`bb-top-neg-${col}`, `Breadboard (-) Ground [${col}]`, posX, bbY + 34);

            // Upper Rows a-e
            ['a', 'b', 'c', 'd', 'e'].forEach((r, idx) => {
                registerTerminal(`bb-${r}${col}`, `Breadboard Terminal ${r.toUpperCase()}${col}`, posX, bbY + 54 + idx * 11);
            });

            // Lower Rows f-j
            ['f', 'g', 'h', 'i', 'j'].forEach((r, idx) => {
                registerTerminal(`bb-${r}${col}`, `Breadboard Terminal ${r.toUpperCase()}${col}`, posX, bbY + 128 + idx * 11);
            });

            // Bottom Power Rails
            registerTerminal(`bb-bot-pos-${col}`, `Breadboard (+) Rail [${col}]`, posX, bbY + 188);
            registerTerminal(`bb-bot-neg-${col}`, `Breadboard (-) Ground [${col}]`, posX, bbY + 204);
        }
    }

    function registerTerminal(id, name, x, y) {
        terminals[id] = { id, name, x, y };

        const container = document.getElementById('tcTerminalsContainer');
        if (!container) return;

        const pin = document.createElement('div');
        pin.className = 'tc-terminal-pin';
        pin.id = `term_${id}`;
        pin.style.left = `${x}px`;
        pin.style.top = `${y}px`;

        // Tooltip
        const tip = document.createElement('div');
        tip.className = 'tc-pin-tooltip';
        tip.textContent = name;
        pin.appendChild(tip);

        // Interaction
        pin.addEventListener('mouseenter', () => handleTerminalHover(id));
        pin.addEventListener('mouseleave', () => handleTerminalUnhover(id));
        pin.addEventListener('click', (e) => {
            e.stopPropagation();
            handleTerminalClick(id);
        });

        container.appendChild(pin);
    }

    // ==========================================
    // INTERACTIVE TINKERCAD WIRING SYSTEM
    // ==========================================
    function handleTerminalHover(id) {
        state.hoverTerminalId = id;
        const el = document.getElementById(`term_${id}`);
        if (el) el.classList.add('active');

        if (state.drawingWire) {
            if (state.drawingWire.startTerminalId !== id) {
                if (el) el.classList.add('snap-candidate');
            }
        }
    }

    function handleTerminalUnhover(id) {
        if (state.hoverTerminalId === id) state.hoverTerminalId = null;
        const el = document.getElementById(`term_${id}`);
        if (el) {
            el.classList.remove('active');
            el.classList.remove('snap-candidate');
        }
    }

    function handleTerminalClick(id) {
        if (!state.drawingWire) {
            // Start Drawing Wire from this terminal
            startWireDrawing(id);
        } else if (state.drawingWire.startTerminalId === id) {
            // Clicked same terminal -> cancel
            cancelWireDrawing();
        } else {
            // Complete Wire Connection!
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

        // Add final point
        const wps = [...state.drawingWire.waypoints.slice(1)]; // Intermediate points

        const wireId = `wire_${Date.now()}_${Math.floor(Math.random() * 1000)}`;
        state.wires.push({
            id: wireId,
            from: startId,
            to: targetTerminalId,
            color: state.selectedWireColor,
            waypoints: wps
        });

        cancelWireDrawing();
        renderWires();
        checkCircuitConnectivity();
    }

    function cancelWireDrawing() {
        state.drawingWire = null;
        const rubber = document.getElementById('tcRubberbandWire');
        if (rubber) rubber.style.display = 'none';

        document.querySelectorAll('.tc-terminal-pin').forEach(p => {
            p.classList.remove('active');
            p.classList.remove('snap-candidate');
        });
    }

    // Canvas click: Add intermediate waypoint (orthogonal bend) or cancel
    function handleCanvasClick(e) {
        if (!state.drawingWire) {
            deselectAll();
            return;
        }

        // If wire is being drawn, clicking blank canvas adds an intermediate corner waypoint!
        const rect = document.getElementById('tcCanvasStage').getBoundingClientRect();
        const stageX = (e.clientX - rect.left) / state.zoom;
        const stageY = (e.clientY - rect.top) / state.zoom;

        state.drawingWire.waypoints.push({ x: stageX, y: stageY });
    }

    // Canvas mousemove: track rubberband wire
    function handleCanvasMouseMove(e) {
        if (!state.drawingWire) return;

        const rect = document.getElementById('tcCanvasStage').getBoundingClientRect();
        let targetX = (e.clientX - rect.left) / state.zoom;
        let targetY = (e.clientY - rect.top) / state.zoom;

        // If hovering over a terminal, snap to its coordinates!
        if (state.hoverTerminalId && terminals[state.hoverTerminalId]) {
            targetX = terminals[state.hoverTerminalId].x;
            targetY = terminals[state.hoverTerminalId].y;
        }

        const pts = [...state.drawingWire.waypoints, { x: targetX, y: targetY }];
        const d = buildWirePathString(pts);

        const rubber = document.getElementById('tcRubberbandWire');
        if (rubber) {
            rubber.setAttribute('d', d);
        }
    }

    // Construct SVG Path String with smooth/orthogonal curves
    function buildWirePathString(points) {
        if (!points || points.length === 0) return '';
        if (points.length === 1) return `M ${points[0].x} ${points[0].y}`;

        if (points.length === 2) {
            // 2 Points: Smooth natural catenary sag / bezier
            const p1 = points[0];
            const p2 = points[1];
            const dx = p2.x - p1.x;
            const dy = p2.y - p1.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            const sag = Math.min(60, dist * 0.25);
            return `M ${p1.x} ${p1.y} C ${p1.x} ${p1.y + sag}, ${p2.x} ${p2.y + sag}, ${p2.x} ${p2.y}`;
        }

        // Multi-Point Orthogonal / Waypoint routing
        let d = `M ${points[0].x} ${points[0].y}`;
        for (let i = 1; i < points.length; i++) {
            d += ` L ${points[i].x} ${points[i].y}`;
        }
        return d;
    }

    // Render Placed Wires in SVG Layer
    function renderWires() {
        const group = document.getElementById('tcWiresGroup');
        if (!group) return;
        group.innerHTML = '';

        state.wires.forEach(w => {
            const t1 = terminals[w.from];
            const t2 = terminals[w.to];
            if (!t1 || !t2) return;

            const allPts = [{ x: t1.x, y: t1.y }, ...w.waypoints, { x: t2.x, y: t2.y }];
            const d = buildWirePathString(allPts);

            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', d);
            path.setAttribute('class', 'tc-wire-svg-path' + (state.selectedItem?.id === w.id ? ' selected' : ''));
            path.setAttribute('stroke', w.color);
            path.setAttribute('stroke-width', state.selectedItem?.id === w.id ? '6' : '3.5');

            path.addEventListener('click', (e) => {
                e.stopPropagation();
                selectWire(w.id);
            });

            group.appendChild(path);

            // If selected, render draggable waypoint handles!
            if (state.selectedItem?.id === w.id) {
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
            wire.waypoints[wpIndex].x = (e.clientX - rect.left) / state.zoom;
            wire.waypoints[wpIndex].y = (e.clientY - rect.top) / state.zoom;
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
    // COMPONENT PLACEMENT & MANAGEMENT
    // ==========================================
    function placeComponent(type, x, y, rotation = 0, props = {}) {
        const compId = `comp_${state.components.length}_${Date.now() % 1000}`;
        const comp = {
            id: compId,
            type,
            x: x || 650,
            y: y || 180,
            rotation: rotation || 0,
            props: {
                name: `${type.toUpperCase()}_${state.components.length + 1}`,
                color: '#ef4444',
                resistance: type === 'resistor' ? 220 : 10000,
                ...props
            }
        };

        state.components.push(comp);
        renderComponentDOM(comp);
        registerComponentTerminals(comp);
        selectComponent(comp.id);
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

        // SVG Render based on type
        if (comp.type === 'led') {
            el.innerHTML = `
                <div style="width: 32px; height: 42px; position: relative;">
                    <!-- Leads -->
                    <div style="position: absolute; bottom: 0; left: 10px; width: 2px; height: 16px; background: #94a3b8;"></div>
                    <div style="position: absolute; bottom: 0; left: 20px; width: 2px; height: 16px; background: #94a3b8;"></div>
                    <!-- Epoxy Lens Dome -->
                    <div id="${comp.id}_lens" style="position: absolute; top: 0; left: 6px; width: 20px; height: 26px; border-radius: 10px 10px 4px 4px; background: ${comp.props.color}; opacity: 0.85; border: 2px solid rgba(0,0,0,0.3); transition: all 0.2s;"></div>
                </div>
            `;
        } else if (comp.type === 'resistor') {
            el.innerHTML = `
                <div style="width: 50px; height: 18px; position: relative; display: flex; align-items: center;">
                    <div style="width: 12px; height: 2px; background: #94a3b8;"></div>
                    <div style="width: 26px; height: 14px; background: #fcd34d; border-radius: 4px; border: 1px solid #d97706; display: flex; justify-content: space-around; align-items: center; padding: 0 2px;">
                        <div style="width: 2px; height: 100%; background: #dc2626;"></div>
                        <div style="width: 2px; height: 100%; background: #dc2626;"></div>
                        <div style="width: 2px; height: 100%; background: #78350f;"></div>
                        <div style="width: 2px; height: 100%; background: #eab308;"></div>
                    </div>
                    <div style="width: 12px; height: 2px; background: #94a3b8;"></div>
                </div>
            `;
        } else if (comp.type === 'potentiometer') {
            el.innerHTML = `
                <div style="width: 44px; height: 44px; border-radius: 50%; background: #0284c7; border: 3px solid #0369a1; box-shadow: 0 4px 10px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; cursor: pointer;">
                    <div id="${comp.id}_dial" style="width: 6px; height: 20px; background: #38bdf8; border-radius: 3px;"></div>
                </div>
            `;
        } else if (comp.type === 'ldr') {
            el.innerHTML = `
                <div style="width: 32px; height: 32px; border-radius: 50%; background: #ea580c; border: 2px solid #c2410c; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 8px rgba(0,0,0,0.25);">
                    <i class="fa-solid fa-sun" style="color: #fef08a; font-size: 0.9rem;"></i>
                </div>
            `;
        } else if (comp.type === 'ultrasonic') {
            el.innerHTML = `
                <div style="width: 90px; height: 38px; background: #0284c7; border-radius: 6px; border: 2px solid #0369a1; display: flex; justify-content: space-around; align-items: center; padding: 2px;">
                    <div style="width: 26px; height: 26px; border-radius: 50%; background: #cbd5e1; border: 2px solid #64748b; font-weight: bold; font-size: 0.65rem; display: flex; align-items: center; justify-content: center; color: #0f172a;">T</div>
                    <div style="width: 26px; height: 26px; border-radius: 50%; background: #cbd5e1; border: 2px solid #64748b; font-weight: bold; font-size: 0.65rem; display: flex; align-items: center; justify-content: center; color: #0f172a;">R</div>
                </div>
            `;
        }

        // Selection & Dragging
        el.addEventListener('click', (e) => {
            e.stopPropagation();
            selectComponent(comp.id);
        });

        makeComponentDraggable(el, comp);
    }

    function registerComponentTerminals(comp) {
        if (comp.type === 'led') {
            registerTerminal(`${comp.id}_anode`, `${comp.props.name} Anode (+)`, comp.x + 10, comp.y + 42);
            registerTerminal(`${comp.id}_cathode`, `${comp.props.name} Cathode (-)`, comp.x + 20, comp.y + 42);
        } else if (comp.type === 'resistor') {
            registerTerminal(`${comp.id}_t1`, `${comp.props.name} Terminal 1`, comp.x + 2, comp.y + 9);
            registerTerminal(`${comp.id}_t2`, `${comp.props.name} Terminal 2`, comp.x + 48, comp.y + 9);
        } else if (comp.type === 'potentiometer') {
            registerTerminal(`${comp.id}_t1`, `${comp.props.name} 5V (Leg 1)`, comp.x + 8, comp.y + 44);
            registerTerminal(`${comp.id}_wiper`, `${comp.props.name} Wiper (Signal)`, comp.x + 22, comp.y + 44);
            registerTerminal(`${comp.id}_t2`, `${comp.props.name} GND (Leg 2)`, comp.x + 36, comp.y + 44);
        } else if (comp.type === 'ldr') {
            registerTerminal(`${comp.id}_t1`, `${comp.props.name} Terminal 1`, comp.x + 8, comp.y + 32);
            registerTerminal(`${comp.id}_t2`, `${comp.props.name} Terminal 2`, comp.x + 24, comp.y + 32);
        } else if (comp.type === 'ultrasonic') {
            registerTerminal(`${comp.id}_vcc`, `${comp.props.name} VCC (5V)`, comp.x + 15, comp.y + 38);
            registerTerminal(`${comp.id}_trig`, `${comp.props.name} Trig`, comp.x + 35, comp.y + 38);
            registerTerminal(`${comp.id}_echo`, `${comp.props.name} Echo`, comp.x + 55, comp.y + 38);
            registerTerminal(`${comp.id}_gnd`, `${comp.props.name} GND`, comp.x + 75, comp.y + 38);
        }
    }

    function makeComponentDraggable(el, comp) {
        let isDragging = false;
        let startX, startY;

        el.addEventListener('mousedown', (e) => {
            if (e.button !== 0) return;
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;

            function onMouseMove(moveEvent) {
                if (!isDragging) return;
                const dx = (moveEvent.clientX - startX) / state.zoom;
                const dy = (moveEvent.clientY - startY) / state.zoom;

                comp.x += dx;
                comp.y += dy;
                startX = moveEvent.clientX;
                startY = moveEvent.clientY;

                el.style.left = `${comp.x}px`;
                el.style.top = `${comp.y}px`;

                // Update terminal positions
                registerComponentTerminals(comp);
                renderWires();
            }

            function onMouseUp() {
                isDragging = false;
                window.removeEventListener('mousemove', onMouseMove);
                window.removeEventListener('mouseup', onMouseUp);
            }

            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
        });
    }

    // ==========================================
    // SELECTION & TOOLBAR ACTIONS (ROTATE, DELETE)
    // ==========================================
    function selectWire(wireId) {
        deselectAll();
        state.selectedItem = { type: 'wire', id: wireId };
        const w = state.wires.find(item => item.id === wireId);
        if (w) {
            updateWireColorDisplay(w.color);
        }
        renderWires();
    }

    function selectComponent(compId) {
        deselectAll();
        state.selectedItem = { type: 'component', id: compId };
        const el = document.getElementById(compId);
        if (el) el.classList.add('selected');
        showComponentInspector(compId);
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
    }

    function rotateSelected() {
        if (state.selectedItem?.type !== 'component') return;
        const comp = state.components.find(c => c.id === state.selectedItem.id);
        if (!comp) return;

        comp.rotation = (comp.rotation + 90) % 360;
        const el = document.getElementById(comp.id);
        if (el) el.style.transform = `rotate(${comp.rotation}deg)`;

        registerComponentTerminals(comp);
        renderWires();
    }

    function deleteSelected() {
        if (!state.selectedItem) return;

        if (state.selectedItem.type === 'wire') {
            state.wires = state.wires.filter(w => w.id !== state.selectedItem.id);
        } else if (state.selectedItem.type === 'component') {
            const id = state.selectedItem.id;
            state.components = state.components.filter(c => c.id !== id);
            const el = document.getElementById(id);
            if (el) el.remove();

            // Remove connected wires
            state.wires = state.wires.filter(w => !w.from.startsWith(id) && !w.to.startsWith(id));
        }

        deselectAll();
        renderWires();
        checkCircuitConnectivity();
    }

    // Inspector Popover
    function showComponentInspector(compId) {
        const comp = state.components.find(c => c.id === compId);
        const pop = document.getElementById('tcComponentInspector');
        const fields = document.getElementById('tcInspectorFields');
        const title = document.getElementById('tcInspectorName');
        if (!comp || !pop || !fields || !title) return;

        title.textContent = comp.props.name;
        pop.style.left = `${Math.min(comp.x + 60, 800)}px`;
        pop.style.top = `${comp.y - 20}px`;
        pop.style.display = 'block';

        if (comp.type === 'led') {
            fields.innerHTML = `
                <div class="tc-inspector-row">
                    <span>Color:</span>
                    <select id="tcLedColorSelect" class="tc-inspector-input">
                        <option value="#ef4444" ${comp.props.color === '#ef4444' ? 'selected' : ''}>Red</option>
                        <option value="#10b981" ${comp.props.color === '#10b981' ? 'selected' : ''}>Green</option>
                        <option value="#eab308" ${comp.props.color === '#eab308' ? 'selected' : ''}>Yellow</option>
                        <option value="#0284c7" ${comp.props.color === '#0284c7' ? 'selected' : ''}>Blue</option>
                        <option value="#ffffff" ${comp.props.color === '#ffffff' ? 'selected' : ''}>White</option>
                    </select>
                </div>
            `;
            document.getElementById('tcLedColorSelect')?.addEventListener('change', function () {
                comp.props.color = this.value;
                const lens = document.getElementById(`${comp.id}_lens`);
                if (lens) lens.style.background = this.value;
            });
        } else if (comp.type === 'resistor') {
            fields.innerHTML = `
                <div class="tc-inspector-row">
                    <span>Resistance:</span>
                    <input type="number" id="tcResVal" class="tc-inspector-input" value="${comp.props.resistance}">
                    <span>Ω</span>
                </div>
            `;
            document.getElementById('tcResVal')?.addEventListener('input', function () {
                comp.props.resistance = parseFloat(this.value) || 220;
            });
        } else {
            fields.innerHTML = `<div style="color: var(--tc-text-muted); font-size: 0.8rem;">No editable parameters</div>`;
        }
    }

    function hideComponentInspector() {
        const pop = document.getElementById('tcComponentInspector');
        if (pop) pop.style.display = 'none';
    }

    // ==========================================
    // UI BINDINGS & COLOR SELECTION
    // ==========================================
    function bindUI() {
        // Preset Selector
        document.getElementById('tcPresetSelector')?.addEventListener('change', function () {
            loadPreset(this.value);
        });

        // Rotate & Delete
        document.getElementById('tcRotateBtn')?.addEventListener('click', rotateSelected);
        document.getElementById('tcDeleteBtn')?.addEventListener('click', deleteSelected);

        // Keyboard Shortcuts
        window.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            if (e.key === 'r' || e.key === 'R') rotateSelected();
            if (e.key === 'Delete' || e.key === 'Backspace') deleteSelected();
            if (e.key === 'Escape') cancelWireDrawing();
        });

        // Wire Color Dropdown Popover
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
                    popover.style.display = 'none';
                });
            });

            document.addEventListener('click', () => {
                popover.style.display = 'none';
            });
        }

        // Code Drawer Toggle
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

        // Simulation Start / Stop
        document.getElementById('tcStartSimBtn')?.addEventListener('click', toggleSimulation);

        // Auto-Wire & Clear
        document.getElementById('tcAutoWireBtn')?.addEventListener('click', autoWirePreset);
        document.getElementById('tcClearWiresBtn')?.addEventListener('click', clearAllWires);

        // Component Palette Cards Click-to-Place
        document.querySelectorAll('.tc-component-card').forEach(card => {
            card.addEventListener('click', function () {
                const type = this.getAttribute('data-component-type');
                if (type === 'arduino' || type === 'breadboard') return;
                placeComponent(type, 650 + Math.random() * 40, 180 + Math.random() * 40);
            });
        });

        // Close Inspector Button
        document.getElementById('tcCloseInspectorBtn')?.addEventListener('click', hideComponentInspector);

        // Canvas Click & Mousemove
        const stage = document.getElementById('tcCanvasStage');
        if (stage) {
            stage.addEventListener('click', handleCanvasClick);
            stage.addEventListener('mousemove', handleCanvasMouseMove);
        }

        // Serial Tabs & Clear
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
                tabMonitor.classList.add('active');
                tabPlotter.classList.remove('active');
                if (viewMonitor) viewMonitor.style.display = 'block';
                if (viewPlotter) viewPlotter.style.display = 'none';
            });
            tabPlotter.addEventListener('click', () => {
                tabPlotter.classList.add('active');
                tabMonitor.classList.remove('active');
                if (viewMonitor) viewMonitor.style.display = 'none';
                if (viewPlotter) {
                    viewPlotter.style.display = 'block';
                    drawPlotter();
                }
            });
        }
    }

    function setWireColor(color, name) {
        state.selectedWireColor = color;
        state.selectedWireName = name;
        updateWireColorDisplay(color, name);

        // If a wire is selected, update its color immediately!
        if (state.selectedItem?.type === 'wire') {
            const w = state.wires.find(item => item.id === state.selectedItem.id);
            if (w) {
                w.color = color;
                renderWires();
            }
        }
    }

    function updateWireColorDisplay(color, name) {
        const swatch = document.getElementById('tcCurrentColorSwatch');
        const label = document.getElementById('tcCurrentColorName');
        if (swatch) swatch.style.background = color;
        if (label && name) label.textContent = name;
    }

    // ==========================================
    // CANVAS PANNING & ZOOM
    // ==========================================
    function setupCanvasPanning() {
        const container = document.getElementById('tcCanvasContainer');
        const stage = document.getElementById('tcCanvasStage');
        if (!container || !stage) return;

        // Zoom Buttons
        document.getElementById('tcZoomInBtn')?.addEventListener('click', () => setZoom(state.zoom + 0.1));
        document.getElementById('tcZoomOutBtn')?.addEventListener('click', () => setZoom(state.zoom - 0.1));
        document.getElementById('tcZoomResetBtn')?.addEventListener('click', () => {
            state.panX = 0;
            state.panY = 0;
            setZoom(1.0);
        });

        // Mouse Wheel Zoom
        container.addEventListener('wheel', (e) => {
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.05 : 0.05;
            setZoom(state.zoom + delta);
        }, { passive: false });
    }

    function setZoom(val) {
        state.zoom = Math.max(0.6, Math.min(1.8, val));
        const stage = document.getElementById('tcCanvasStage');
        if (stage) {
            stage.style.transform = `translate(${state.panX}px, ${state.panY}px) scale(${state.zoom})`;
        }
    }

    // ==========================================
    // LAB PRESET LOADER
    // ==========================================
    function loadPreset(key) {
        if (!presets[key]) return;
        state.currentPreset = key;
        const p = presets[key];

        // Clear existing placed components and wires
        state.components = [];
        state.wires = [];
        document.getElementById('tcComponentsContainer').innerHTML = '';
        deselectAll();

        // Spawn preset components
        p.components.forEach(c => {
            placeComponent(c.type, c.x, c.y, c.rotation, c.props);
        });

        // Set Code in CodeMirror
        if (codeEditor) {
            codeEditor.setValue(p.code);
        } else {
            const ta = document.getElementById('tcCodeTextarea');
            if (ta) ta.value = p.code;
        }

        // Auto-wire preset
        autoWirePreset();

        appendSerial(`--- Loaded Preset: ${p.title} ---\n`);
        if (state.isSimulating) {
            stopSimulation();
            startSimulation();
        }
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
                waypoints: w.waypoints || []
            });
        });

        renderWires();
        checkCircuitConnectivity();
    }

    function clearAllWires() {
        state.wires = [];
        deselectAll();
        renderWires();
        checkCircuitConnectivity();
        appendSerial('--- All wires cleared. Ready for manual wiring! ---\n');
    }

    // ==========================================
    // CIRCUIT CONNECTIVITY VALIDATION
    // ==========================================
    function checkCircuitConnectivity() {
        // Verifies whether a closed circuit is formed between Arduino power/signal and GND
        if (state.wires.length === 0) return false;
        // In freeform/preset mode, check that at least one wire connects to Arduino power/pin and one to GND
        const hasSignal = state.wires.some(w => w.from.startsWith('ard-pin') || w.to.startsWith('ard-pin'));
        const hasGnd = state.wires.some(w => w.from.includes('gnd') || w.to.includes('gnd') || w.from.includes('neg') || w.to.includes('neg'));
        return hasSignal && hasGnd;
    }

    // ==========================================
    // SIMULATION ENGINE & TELEMETRY
    // ==========================================
    function toggleSimulation() {
        if (state.isSimulating) {
            stopSimulation();
        } else {
            startSimulation();
        }
    }

    function startSimulation() {
        state.isSimulating = true;
        state.simStartTime = Date.now();

        const btn = document.getElementById('tcStartSimBtn');
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-stop"></i> Stop Simulation';
            btn.className = 'btn-tc-sim stop';
        }

        const onLed = document.getElementById('arduinoOnLed');
        if (onLed) onLed.setAttribute('fill', '#22c55e');

        appendSerial('--- Simulation Active: Clock 16 MHz Online ---\n');

        let tick = 0;
        let pwmVal = 0;
        let pwmDir = 5;

        state.simInterval = setInterval(() => {
            tick++;
            updateSimTimer();

            const isClosed = checkCircuitConnectivity();

            if (!isClosed) {
                if (tick % 25 === 0) {
                    appendSerial('[CIRCUIT WARNING] Circuit Incomplete! Connect wires between Arduino & components to allow current flow.\n');
                }
                setLedGlow(false);
                return;
            }

            // Execute virtual firmware based on preset
            if (state.currentPreset === 'blink') {
                const isHigh = Math.floor(tick / 16) % 2 === 0;
                setPinBuiltin(isHigh);
                setLedGlow(isHigh);
                if (tick % 16 === 0) {
                    appendSerial(isHigh ? 'LED State: ON  (5.00 V)\n' : 'LED State: OFF (0.00 V)\n');
                }
            } else if (state.currentPreset === 'pwm_fade') {
                pwmVal += pwmDir;
                if (pwmVal >= 255 || pwmVal <= 0) pwmDir = -pwmDir;
                const vEff = (pwmVal / 255.0) * 5.0;
                setLedPWM(pwmVal);
                if (tick % 5 === 0) {
                    appendSerial(`PWM: ${pwmVal} | V_eff: ${vEff.toFixed(2)} V\n`);
                    pushPlotter(vEff);
                }
            } else if (state.currentPreset === 'potentiometer') {
                const raw = state.hardwareValues.potentiometer;
                const v = (raw / 1023.0) * 5.0;
                if (tick % 4 === 0) {
                    appendSerial(`ADC A0: ${raw} | Voltage: ${v.toFixed(3)} V\n`);
                    pushPlotter(v);
                }
            } else if (state.currentPreset === 'ldr_sensor') {
                const lux = state.hardwareValues.ldrLux;
                const adc = Math.round(1023 * (lux / (lux + 300)));
                const v = (adc / 1023.0) * 5.0;
                if (tick % 5 === 0) {
                    appendSerial(`Lux: ${lux} | A1: ${adc} (${v.toFixed(2)} V)\n`);
                    pushPlotter(v);
                }
            } else if (state.currentPreset === 'ultrasonic') {
                const cm = state.hardwareValues.ultrasonicCm;
                const us = Math.round(cm * 2 / 0.0343);
                if (tick % 5 === 0) {
                    appendSerial(`Echo: ${us} us | Distance: ${cm.toFixed(1)} cm\n`);
                    pushPlotter(cm);
                }
            }
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
        appendSerial('--- Simulation Stopped ---\n');
    }

    function setPinBuiltin(stateVal) {
        const lLed = document.getElementById('arduinoBuiltinLed');
        if (lLed) lLed.setAttribute('fill', stateVal ? '#eab308' : '#334155');
    }

    function setLedGlow(isLit) {
        state.components.filter(c => c.type === 'led').forEach(comp => {
            const lens = document.getElementById(`${comp.id}_lens`);
            if (lens) {
                if (isLit) {
                    lens.classList.add('tc-led-lit');
                    lens.style.opacity = '1';
                } else {
                    lens.classList.remove('tc-led-lit');
                    lens.style.opacity = '0.7';
                }
            }
        });
    }

    function setLedPWM(pwm) {
        const opacity = Math.max(0.2, pwm / 255.0);
        state.components.filter(c => c.type === 'led').forEach(comp => {
            const lens = document.getElementById(`${comp.id}_lens`);
            if (lens) {
                lens.style.opacity = opacity.toString();
                if (pwm > 30) lens.classList.add('tc-led-lit');
                else lens.classList.remove('tc-led-lit');
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
        if (state.serialLogs.length > 250) state.serialLogs.shift();
        const stream = document.getElementById('tcSerialStream');
        if (stream) {
            stream.textContent = state.serialLogs.join('');
            stream.scrollTop = stream.scrollHeight;
        }
    }

    function pushPlotter(val) {
        state.plotterData.push(val);
        if (state.plotterData.length > 90) state.plotterData.shift();
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

        // Grid lines
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.08)';
        ctx.lineWidth = 1;
        for (let y = 20; y < h; y += 25) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(w, y);
            ctx.stroke();
        }

        if (state.plotterData.length < 2) return;

        const minVal = Math.min(...state.plotterData, 0);
        const maxVal = Math.max(...state.plotterData, 5.0);
        const range = (maxVal - minVal) || 1;

        ctx.strokeStyle = '#38bdf8';
        ctx.lineWidth = 2.5;
        ctx.beginPath();

        const stepX = w / 90;
        state.plotterData.forEach((pt, i) => {
            const x = i * stepX;
            const y = h - 15 - ((pt - minVal) / range) * (h - 30);
            if (i === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        });
        ctx.stroke();

        ctx.fillStyle = '#94a3b8';
        ctx.font = '11px "JetBrains Mono"';
        ctx.fillText(`Max: ${maxVal.toFixed(2)} | Min: ${minVal.toFixed(2)}`, 10, 15);
    }

    // ==========================================
    // BOOTSTRAP
    // ==========================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.TinkercadClone = {
        state,
        loadPreset,
        autoWirePreset,
        clearAllWires,
        startSimulation,
        stopSimulation
    };

})();
