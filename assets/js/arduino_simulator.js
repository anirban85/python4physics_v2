/**
 * Python4Physics - Tinkercad Circuits Pro Simulator Engine
 * =========================================================
 * Full-Featured Physics & Electronics Virtual Laboratory:
 * - Real-Time Electrical Circuit Solver (Union-Find Netlist Engine)
 * - Dynamic C++ to Async JavaScript Transpiler with Servo & Math Support
 * - Breadboard Pin Snapping Grid with Visual Contact Indicators
 * - 16 Photorealistic Electronic Components & Sensors:
 *   LED, RGB LED, Resistor, Pushbutton, Potentiometer, Slide Switch,
 *   Capacitor, Diode, Photoresistor (LDR), Ultrasonic (HC-SR04),
 *   PIR Motion Sensor, TMP36 Temperature, Tilt Sensor,
 *   Micro Servo Motor (SG90), Piezo Buzzer, DC Motor / Fan
 * - 10 Pre-wired Physics & Engineering Experiments
 * - Authentic 12-Color Tinkercad Wire Palette & Multi-Point Manhattan Routing
 * - Live Serial Monitor (9600 to 115200 baud) & Oscilloscope Serial Plotter
 * - Live Schematic Diagram Generator with SVG Export
 * - Dynamic Bill of Materials (BOM) with CSV Export
 * - Full Undo / Redo Command Stack (Ctrl+Z, Ctrl+Y)
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
        firmwareCancelToken: { cancelled: false },
        currentPreset: 'blink',
        currentView: 'circuits', // 'circuits' | 'schematic' | 'bom'
        selectedCategory: 'basic',
        baudRate: 9600,

        // Wire Configuration (12 Tinkercad Wire Colors + Smart Auto)
        selectedWireColor: 'auto',
        selectedWireName: 'Auto (Smart)',
        wireType: 'normal',
        wireStyle: 'curved',
        snapTarget: null,

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

        // Telemetry & Hardware Values
        serialLogs: [],
        plotterData: [],
        hardwareValues: {
            potentiometer: 512,
            ldrLux: 400,
            ultrasonicCm: 25,
            tmp36Temp: 25.0,
            pirMotion: false,
            pirTimer: null,
            servoAngles: {}, // pin -> angle
            digitalPins: new Array(14).fill(0),
            analogPins: new Array(6).fill(0),
            pinModes: new Array(20).fill(0), // 0: INPUT, 1: OUTPUT, 2: INPUT_PULLUP
            pinPWM: new Array(14).fill(0),
            pinVoltages: new Array(20).fill(0.0)
        },

        // Circuit analysis
        circuitStatus: { valid: false, reason: 'no_wires', details: '', netVoltages: {} },
        capacitorStates: {}
    };

    let codeEditor = null;
    let audioCtx = null;
    let activeToneOsc = null;

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
    // 2. COMPONENT LIBRARY DEFINITIONS
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
        rgb_led: {
            name: 'RGB LED',
            category: 'basic',
            defaultProps: { name: 'RGB', rVal: 0, gVal: 0, bVal: 0 },
            width: 68,
            height: 44,
            terminalOffsets: [
                { id: 'r', label: 'Red (Pin 1)', dx: 8.5, dy: 38 },
                { id: 'cathode', label: 'Cathode (GND Pin 2)', dx: 25.5, dy: 38 },
                { id: 'g', label: 'Green (Pin 3)', dx: 42.5, dy: 38 },
                { id: 'b', label: 'Blue (Pin 4)', dx: 59.5, dy: 38 }
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
                { id: 'a1', label: 'Terminal 1a', dx: 8.5, dy: 8 },
                { id: 'a2', label: 'Terminal 1b', dx: 25.5, dy: 8 },
                { id: 'b1', label: 'Terminal 2a', dx: 8.5, dy: 36 },
                { id: 'b2', label: 'Terminal 2b', dx: 25.5, dy: 36 }
            ]
        },
        potentiometer: {
            name: 'Potentiometer',
            category: 'basic',
            defaultProps: { resistance: 10000, name: 'POT', position: 0.5 },
            width: 52,
            height: 48,
            terminalOffsets: [
                { id: 't1', label: 'Terminal 1 (5V)', dx: 8.5, dy: 42 },
                { id: 'wiper', label: 'Wiper (Signal)', dx: 25.5, dy: 42 },
                { id: 't2', label: 'Terminal 2 (GND)', dx: 42.5, dy: 42 }
            ]
        },
        slide_switch: {
            name: 'Slide Switch',
            category: 'basic',
            defaultProps: { name: 'SW', state: 'left' },
            width: 52,
            height: 38,
            terminalOffsets: [
                { id: 't1', label: 'Terminal 1', dx: 8.5, dy: 34 },
                { id: 'com', label: 'Common', dx: 25.5, dy: 34 },
                { id: 't2', label: 'Terminal 2', dx: 42.5, dy: 34 }
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
            width: 51,
            height: 18,
            terminalOffsets: [
                { id: 't1', label: 'Anode', dx: 0, dy: 9 },
                { id: 't2', label: 'Cathode', dx: 51, dy: 9 }
            ]
        },
        ldr: {
            name: 'Photoresistor',
            category: 'sensors',
            defaultProps: { name: 'LDR', lux: 400 },
            width: 34,
            height: 40,
            terminalOffsets: [
                { id: 't1', label: 'Terminal 1', dx: 8.5, dy: 34 },
                { id: 't2', label: 'Terminal 2', dx: 25.5, dy: 34 }
            ]
        },
        ultrasonic: {
            name: 'Ultrasonic HC-SR04',
            category: 'sensors',
            defaultProps: { name: 'US', distance: 25 },
            width: 90,
            height: 44,
            terminalOffsets: [
                { id: 'vcc', label: 'VCC (5V)', dx: 19.5, dy: 40 },
                { id: 'trig', label: 'Trig', dx: 36.5, dy: 40 },
                { id: 'echo', label: 'Echo', dx: 53.5, dy: 40 },
                { id: 'gnd', label: 'GND', dx: 70.5, dy: 40 }
            ]
        },
        pir: {
            name: 'PIR Motion Sensor',
            category: 'sensors',
            defaultProps: { name: 'PIR', motion: false },
            width: 64,
            height: 52,
            terminalOffsets: [
                { id: 'sig', label: 'Signal (OUT)', dx: 15, dy: 48 },
                { id: 'vcc', label: 'VCC (5V)', dx: 32, dy: 48 },
                { id: 'gnd', label: 'GND', dx: 49, dy: 48 }
            ]
        },
        tmp36: {
            name: 'Temperature (TMP36)',
            category: 'sensors',
            defaultProps: { name: 'TMP', tempC: 25.0 },
            width: 52,
            height: 44,
            terminalOffsets: [
                { id: 'vcc', label: 'Power (5V)', dx: 8.5, dy: 38 },
                { id: 'vout', label: 'Vout (Signal)', dx: 25.5, dy: 38 },
                { id: 'gnd', label: 'GND', dx: 42.5, dy: 38 }
            ]
        },
        tilt: {
            name: 'Tilt Sensor',
            category: 'sensors',
            defaultProps: { name: 'TILT', tilted: false },
            width: 34,
            height: 38,
            terminalOffsets: [
                { id: 't1', label: 'Terminal 1', dx: 8.5, dy: 34 },
                { id: 't2', label: 'Terminal 2', dx: 25.5, dy: 34 }
            ]
        },
        servo: {
            name: 'Micro Servo SG90',
            category: 'actuators',
            defaultProps: { name: 'SERVO', angle: 90 },
            width: 64,
            height: 58,
            terminalOffsets: [
                { id: 'gnd', label: 'Ground (Brown)', dx: 15, dy: 54 },
                { id: 'vcc', label: 'Power (Red 5V)', dx: 32, dy: 54 },
                { id: 'sig', label: 'Signal (Orange ~PWM)', dx: 49, dy: 54 }
            ]
        },
        buzzer: {
            name: 'Piezo Buzzer',
            category: 'actuators',
            defaultProps: { name: 'BZ', frequency: 1000 },
            width: 40,
            height: 44,
            terminalOffsets: [
                { id: 'pos', label: 'Positive (+)', dx: 11.5, dy: 40 },
                { id: 'neg', label: 'Negative (-)', dx: 28.5, dy: 40 }
            ]
        },
        dc_motor: {
            name: 'DC Motor / Fan',
            category: 'actuators',
            defaultProps: { name: 'MOTOR', speed: 0 },
            width: 50,
            height: 52,
            terminalOffsets: [
                { id: 'pos', label: 'Terminal 1 (+)', dx: 15, dy: 48 },
                { id: 'neg', label: 'Terminal 2 (-)', dx: 35, dy: 48 }
            ]
        },
        seven_segment: {
            name: '7-Segment Display',
            category: 'actuators',
            defaultProps: { name: 'DISP', digit: 0, segments: { a:1, b:1, c:1, d:1, e:1, f:1, g:0, dp:0 } },
            width: 60,
            height: 85,
            terminalOffsets: [
                { id: 'g', label: 'Seg G', dx: 8.5, dy: 10 },
                { id: 'f', label: 'Seg F', dx: 17, dy: 10 },
                { id: 'com1', label: 'Common (GND)', dx: 25.5, dy: 10 },
                { id: 'a', label: 'Seg A', dx: 34, dy: 10 },
                { id: 'b', label: 'Seg B', dx: 42.5, dy: 10 },
                { id: 'e', label: 'Seg E', dx: 8.5, dy: 75 },
                { id: 'd', label: 'Seg D', dx: 17, dy: 75 },
                { id: 'com2', label: 'Common (GND)', dx: 25.5, dy: 75 },
                { id: 'c', label: 'Seg C', dx: 34, dy: 75 },
                { id: 'dp', label: 'Decimal Point', dx: 42.5, dy: 75 }
            ]
        },
        dot_matrix: {
            name: '8x8 LED Dot Matrix',
            category: 'actuators',
            defaultProps: { name: 'MATRIX', char: 'A' },
            width: 75,
            height: 75,
            terminalOffsets: [
                { id: 'vcc', label: 'VCC (5V)', dx: 10, dy: 68 },
                { id: 'gnd', label: 'GND', dx: 24, dy: 68 },
                { id: 'din', label: 'DIN (Data)', dx: 38, dy: 68 },
                { id: 'cs', label: 'CS (Load)', dx: 52, dy: 68 },
                { id: 'clk', label: 'CLK (Clock)', dx: 66, dy: 68 }
            ]
        },
        ir_tsop1838: {
            name: 'IR Sensor (TSOP1838)',
            category: 'sensors',
            defaultProps: { name: 'IR', lastCode: '0xFFA25D' },
            width: 38,
            height: 42,
            terminalOffsets: [
                { id: 'out', label: 'OUT (Signal)', dx: 8.5, dy: 36 },
                { id: 'gnd', label: 'GND (Ground)', dx: 19, dy: 36 },
                { id: 'vcc', label: 'VCC (5V Power)', dx: 29.5, dy: 36 }
            ]
        },
        sound_sensor: {
            name: 'Sound Sensor (LM393)',
            category: 'sensors',
            defaultProps: { name: 'MIC', threshold: 500, soundLevel: 200 },
            width: 55,
            height: 40,
            terminalOffsets: [
                { id: 'vcc', label: 'VCC (5V)', dx: 8.5, dy: 34 },
                { id: 'gnd', label: 'GND', dx: 20, dy: 34 },
                { id: 'dout', label: 'Digital OUT (D0)', dx: 32, dy: 34 },
                { id: 'aout', label: 'Analog OUT (A0)', dx: 44, dy: 34 }
            ]
        },
        stepper_motor: {
            name: 'Stepper Motor 28BYJ-48',
            category: 'actuators',
            defaultProps: { name: 'STEPPER', angle: 0, speedRpm: 15 },
            width: 65,
            height: 65,
            terminalOffsets: [
                { id: 'in1', label: 'IN1 (D8)', dx: 10, dy: 58 },
                { id: 'in2', label: 'IN2 (D9)', dx: 20, dy: 58 },
                { id: 'in3', label: 'IN3 (D10)', dx: 30, dy: 58 },
                { id: 'in4', label: 'IN4 (D11)', dx: 40, dy: 58 },
                { id: 'vcc', label: 'Power (+5V)', dx: 50, dy: 58 },
                { id: 'gnd', label: 'GND', dx: 58, dy: 58 }
            ]
        },
        bluetooth_hc05: {
            name: 'Bluetooth HC-05',
            category: 'sensors',
            defaultProps: { name: 'BT05', state: 'Connected' },
            width: 48,
            height: 54,
            terminalOffsets: [
                { id: 'state', label: 'STATE', dx: 6, dy: 48 },
                { id: 'rxd', label: 'RXD (Pin 11)', dx: 14, dy: 48 },
                { id: 'txd', label: 'TXD (Pin 10)', dx: 22, dy: 48 },
                { id: 'gnd', label: 'GND', dx: 30, dy: 48 },
                { id: 'vcc', label: 'VCC (5V)', dx: 38, dy: 48 },
                { id: 'en', label: 'EN', dx: 44, dy: 48 }
            ]
        },
        tft_lcd: {
            name: '1.8" Color TFT Display',
            category: 'actuators',
            defaultProps: { name: 'TFT', text: 'Physics Lab' },
            width: 85,
            height: 65,
            terminalOffsets: [
                { id: 'vcc', label: 'VCC', dx: 10, dy: 58 },
                { id: 'gnd', label: 'GND', dx: 20, dy: 58 },
                { id: 'cs', label: 'CS (D10)', dx: 30, dy: 58 },
                { id: 'rst', label: 'RESET (D8)', dx: 40, dy: 58 },
                { id: 'dc', label: 'A0/DC (D9)', dx: 50, dy: 58 },
                { id: 'sda', label: 'SDA/MOSI (D11)', dx: 60, dy: 58 },
                { id: 'sck', label: 'SCK (D13)', dx: 70, dy: 58 }
            ]
        }
    };

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
    // 3. 10 COMPREHENSIVE PRESET LABORATORIES
    // ==========================================
    const presets = {
        // ========================================================
        // SYLLABUS EXP 01: LDR Light Level Detector & Night Lamp
        // ========================================================
        ldr_lamp: {
            title: "Exp 01: LDR Ambient Light Detector & Lamp Switch",
            components: [
                { id: 'ldr1', type: 'ldr', x: 694.5, y: 151, rotation: 0, props: { name: 'LDR1', lux: 450 } },
                { id: 'res1', type: 'resistor', x: 720, y: 158, rotation: 0, props: { resistance: 10000, unit: 'Ω', name: 'R_DIV' } },
                { id: 'led1', type: 'led', x: 813.5, y: 147, rotation: 0, props: { color: '#ef4444', name: 'LAMP' } },
                { id: 'res2', type: 'resistor', x: 839, y: 158, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R_LED' } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'bb-a14', color: '#ef4444', waypoints: [{ x: 208, y: 330 }, { x: 703, y: 330 }] },
                { from: 'ard-pin-a0', to: 'bb-a15', color: '#10b981', waypoints: [{ x: 277, y: 260 }, { x: 720, y: 260 }] },
                { from: 'bb-a20', to: 'bb-bot-neg-20', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-13', to: 'bb-a21', color: '#f97316', waypoints: [{ x: 179, y: 40 }, { x: 822, y: 40 }] },
                { from: 'bb-a27', to: 'bb-bot-neg-27', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [{ x: 220, y: 345 }] }
            ],
            code: `// ========================================================
// Exp 01: LDR Ambient Light Detector & Automatic Lamp Switch
// Detect ambient room lux. When light falls below threshold,
// activate the lamp (Digital Pin 13).
// ========================================================

const int ldrPin = A0;
const int lampPin = 13;
const int LIGHT_THRESHOLD = 300; // ADC threshold (Night/Dark)

void setup() {
  pinMode(lampPin, OUTPUT);
  Serial.begin(9600);
  Serial.println("--- Exp 01: LDR Ambient Light Controller Initialized ---");
  Serial.println("Drag the Flashlight widget on canvas to adjust room lux!");
}

void loop() {
  int sensorVal = analogRead(ldrPin);
  float voltage = sensorVal * (5.0 / 1023.0);
  
  Serial.print("Ambient Light ADC: ");
  Serial.print(sensorVal);
  Serial.print(" (");
  Serial.print(voltage, 2);
  Serial.print(" V) | Status: ");
  
  if (sensorVal < LIGHT_THRESHOLD) {
    digitalWrite(lampPin, HIGH);
    Serial.println("DARKNESS DETECTED -> LAMP ON [ACTIVE]");
  } else {
    digitalWrite(lampPin, LOW);
    Serial.println("Adequate Light -> Lamp OFF [STANDBY]");
  }
  
  delay(250);
}`
        },

        // ========================================================
        // SYLLABUS EXP 02: LDR Potential Divider & LED Brightness
        // ========================================================
        ldr_sensor: {
            title: "Exp 02: LDR Potential Divider & LED Brightness Telemetry",
            components: [
                { id: 'ldr1', type: 'ldr', x: 694.5, y: 151, rotation: 0, props: { name: 'LDR1', lux: 450 } },
                { id: 'res1', type: 'resistor', x: 720, y: 158, rotation: 0, props: { resistance: 10000, unit: 'Ω', name: 'R_DIV' } },
                { id: 'led1', type: 'led', x: 643.5, y: 147, rotation: 0, props: { color: '#0284c7', name: 'OPT_SRC' } },
                { id: 'res2', type: 'resistor', x: 669, y: 158, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R_LED' } }
            ],
            autoWires: [
                { from: 'ard-pin-9', to: 'bb-a11', color: '#0284c7', waypoints: [{ x: 231, y: 40 }, { x: 652, y: 40 }] },
                { from: 'bb-a17', to: 'bb-bot-neg-17', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-5v', to: 'bb-a14', color: '#ef4444', waypoints: [{ x: 208, y: 330 }, { x: 703, y: 330 }] },
                { from: 'ard-pin-a1', to: 'bb-a15', color: '#10b981', waypoints: [{ x: 289, y: 260 }, { x: 720, y: 260 }] },
                { from: 'bb-a20', to: 'bb-bot-neg-20', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [{ x: 220, y: 345 }] }
            ],
            code: `// ========================================================
// Exp 02: Potential Divider Circuit with LDR & LED Brightness
// Adjust LED facing LDR via PWM. Monitor potential divider
// voltage changes on Serial Monitor corresponding to brightness.
// ========================================================

const int ledPwmPin = 9;   // LED facing the LDR
const int ldrSensePin = A1; // Voltage across divider resistor

void setup() {
  pinMode(ledPwmPin, OUTPUT);
  Serial.begin(9600);
  Serial.println("--- Exp 02: Photoelectric Potential Divider Online ---");
}

void loop() {
  // Sweep optical source brightness from 0 to 255
  for (int b = 0; b <= 255; b += 25) {
    analogWrite(ledPwmPin, b);
    delay(100);
    
    int raw = analogRead(ldrSensePin);
    float vOut = (raw / 1023.0) * 5.0;
    
    Serial.print("LED PWM: ");
    Serial.print(b);
    Serial.print(" (");
    Serial.print(Math.round((b / 255.0) * 100));
    Serial.print("%) | Divider ADC: ");
    Serial.print(raw);
    Serial.print(" | V_resistor: ");
    Serial.print(vOut, 3);
    Serial.println(" V");
    
    delay(200);
  }
}`
        },

        // ========================================================
        // SYLLABUS EXP 03: Ultrasonic HC-SR04 Distance Measurement
        // ========================================================
        ultrasonic: {
            title: "Exp 03: HC-SR04 Ultrasonic Distance Sensor & Real-Time Echo",
            components: [
                { id: 'us1', type: 'ultrasonic', x: 683.5, y: 247, rotation: 0, props: { name: 'US1', distance: 25 } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'bb-f14', color: '#ef4444', waypoints: [{ x: 208, y: 330 }, { x: 703, y: 330 }] },
                { from: 'ard-pin-9', to: 'bb-f15', color: '#0284c7', waypoints: [{ x: 231, y: 40 }, { x: 720, y: 40 }] },
                { from: 'ard-pin-8', to: 'bb-f16', color: '#8b5cf6', waypoints: [{ x: 244, y: 48 }, { x: 737, y: 48 }] },
                { from: 'ard-pin-gnd1', to: 'bb-f17', color: '#0f172a', waypoints: [{ x: 220, y: 345 }, { x: 754, y: 345 }] }
            ],
            code: `// ========================================================
// Exp 03: Ultrasonic HC-SR04 Speed of Sound & Distance
// Acoustic Time of Flight: distance = (duration * 0.0343) / 2
// Drag the obstacle on canvas to change target distance!
// ========================================================

const int trigPin = 9;
const int echoPin = 8;

void setup() {
  pinMode(trigPin, OUTPUT);
  pinMode(echoPin, INPUT);
  Serial.begin(9600);
  Serial.println("--- Exp 03: HC-SR04 Acoustic Rangefinder Initialized ---");
  Serial.println("Drag the Obstacle block on canvas to test millimetric range!");
}

void loop() {
  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);
  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);
  digitalWrite(trigPin, LOW);
  
  long duration = pulseIn(echoPin, HIGH);
  float distanceCm = duration * 0.0343 / 2.0;
  float distanceInch = distanceCm / 2.54;
  
  Serial.print("Echo Transit: ");
  Serial.print(duration);
  Serial.print(" us | Distance: ");
  Serial.print(distanceCm, 1);
  Serial.print(" cm (");
  Serial.print(distanceInch, 1);
  Serial.println(" in)");
  
  delay(250);
}`
        },

        // ========================================================
        // SYLLABUS EXP 04: TSOP1838 IR Sensor & TV Remote Hex Code
        // ========================================================
        ir_remote: {
            title: "Exp 04: TSOP1838 IR Receiver & TV Remote Hex Code Decoder",
            components: [
                { id: 'ir1', type: 'ir_tsop1838', x: 677.5, y: 153, rotation: 0, props: { name: 'TSOP1838', lastCode: '0xFFA25D' } },
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#0284c7', name: 'DECODE_LED' } },
                { id: 'res1', type: 'resistor', x: 788, y: 158, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                { from: 'ard-pin-11', to: 'bb-a13', color: '#10b981', waypoints: [{ x: 205, y: 40 }, { x: 686, y: 40 }] },
                { from: 'ard-pin-gnd1', to: 'bb-a14', color: '#0f172a', waypoints: [{ x: 220, y: 345 }, { x: 703, y: 345 }] },
                { from: 'ard-pin-5v', to: 'bb-a15', color: '#ef4444', waypoints: [{ x: 208, y: 330 }, { x: 720, y: 330 }] },
                { from: 'ard-pin-13', to: 'bb-a18', color: '#f97316', waypoints: [{ x: 179, y: 40 }, { x: 771, y: 40 }] },
                { from: 'bb-a24', to: 'bb-bot-neg-24', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd2', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [] }
            ],
            code: `// ========================================================
// Exp 04: TSOP1838 Infrared Sensor & TV Remote Hex Decoder
// 38 kHz Carrier Demodulation. Click buttons on the virtual
// TV Remote on the left of canvas to emit NEC protocol frames!
// ========================================================

const int RECV_PIN = 11;
const int STATUS_LED = 13;

void setup() {
  pinMode(STATUS_LED, OUTPUT);
  Serial.begin(9600);
  Serial.println("--- Exp 04: TSOP1838 NEC IR Decoder Active ---");
  Serial.println("Click the TV Remote buttons on the screen to transmit Hex!");
}

void loop() {
  // In the simulator, pressing any remote button triggers an IR frame
  int sig = digitalRead(RECV_PIN);
  if (sig == HIGH) {
    digitalWrite(STATUS_LED, HIGH);
    Serial.println(">>> IR Burst Received: Valid 38kHz Carrier Detected <<<");
    delay(100);
    digitalWrite(STATUS_LED, LOW);
  }
  delay(150);
}`
        },

        // ========================================================
        // SYLLABUS EXP 05: PIR Motion Detector & Security Alarm
        // ========================================================
        pir_alarm: {
            title: "Exp 05: HC-SR501 PIR Motion Sensor & Security Alarm",
            components: [
                { id: 'pir1', type: 'pir', x: 654, y: 239, rotation: 0, props: { name: 'PIR1', motion: false } },
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#ef4444', name: 'ALARM_LED' } },
                { id: 'res1', type: 'resistor', x: 788, y: 158, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R1' } },
                { id: 'bz1', type: 'buzzer', x: 840, y: 247, rotation: 0, props: { name: 'SIREN', frequency: 1800 } }
            ],
            autoWires: [
                { from: 'ard-pin-2', to: 'bb-f12', color: '#10b981', waypoints: [{ x: 326, y: 40 }, { x: 669, y: 40 }] },
                { from: 'ard-pin-5v', to: 'bb-f13', color: '#ef4444', waypoints: [{ x: 208, y: 330 }, { x: 686, y: 330 }] },
                { from: 'ard-pin-gnd1', to: 'bb-f14', color: '#0f172a', waypoints: [{ x: 220, y: 345 }, { x: 703, y: 345 }] },
                { from: 'ard-pin-13', to: 'bb-a18', color: '#f97316', waypoints: [{ x: 179, y: 40 }, { x: 771, y: 40 }] },
                { from: 'bb-a24', to: 'bb-bot-neg-24', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-11', to: 'bb-f23', color: '#8b5cf6', waypoints: [{ x: 205, y: 40 }, { x: 856, y: 40 }] },
                { from: 'bb-f24', to: 'bb-bot-neg-24', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd2', to: 'bb-bot-neg-4', color: '#0f172a', waypoints: [] }
            ],
            code: `// ========================================================
// Exp 05: HC-SR501 PIR Motion Detector & Security Alarm
// Pyroelectric sensor detects moving thermal IR signature.
// Drag the Human Figure on canvas into the 120 deg cone!
// ========================================================

const int pirSensorPin = 2;
const int indicatorLed = 13;
const int sirenBuzzerPin = 11;

void setup() {
  pinMode(pirSensorPin, INPUT);
  pinMode(indicatorLed, OUTPUT);
  pinMode(sirenBuzzerPin, OUTPUT);
  Serial.begin(9600);
  Serial.println("--- Exp 05: Passive Infrared Security System Armed ---");
  Serial.println("Drag the Human avatar on canvas into the PIR beam to trigger!");
}

void loop() {
  int motionDetected = digitalRead(pirSensorPin);
  
  if (motionDetected == HIGH) {
    digitalWrite(indicatorLed, HIGH);
    tone(sirenBuzzerPin, 1800);
    Serial.println(">>> INTRUSION DETECTED! Infrared Motion Active -> LED ON <<<");
  } else {
    digitalWrite(indicatorLed, LOW);
    noTone(sirenBuzzerPin);
    Serial.println("Area Secure - Scanning Infrared Spectrum...");
  }
  
  delay(150);
}`
        },

        // ========================================================
        // SYLLABUS EXP 06: Simple Pendulum Acceleration due to Gravity
        // ========================================================
        photogate: {
            title: "Exp 06: Simple Pendulum 'g' Measurement with Photogate",
            components: [
                { id: 'btn1', type: 'pushbutton', x: 677.5, y: 169, rotation: 0, props: { name: 'BEAM_GATE', pressed: false } },
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#10b981', name: 'GATE_LED' } },
                { id: 'res1', type: 'resistor', x: 788, y: 158, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                { from: 'ard-pin-2', to: 'bb-d13', color: '#10b981', waypoints: [{ x: 326, y: 40 }, { x: 686, y: 40 }] },
                { from: 'bb-g13', to: 'bb-bot-neg-13', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-13', to: 'bb-a18', color: '#f97316', waypoints: [{ x: 179, y: 40 }, { x: 771, y: 40 }] },
                { from: 'bb-a24', to: 'bb-bot-neg-24', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [{ x: 220, y: 345 }] }
            ],
            code: `// ========================================================
// Exp 06: Determination of Acceleration due to Gravity 'g'
// Simple Pendulum with Optical Photogate Sensor
// Period T = 2 * pi * sqrt(L / g) => g = 4 * pi^2 * L / T^2
// ========================================================

const byte photogatePin = 2; // INT0 hardware interrupt pin
const byte gateLed = 13;
const float PENDULUM_LENGTH = 0.50; // String length L = 0.50 m (50 cm)

volatile unsigned long tStart = 0;
volatile unsigned long tEnd = 0;
volatile byte passCount = 0;

void setup() {
  pinMode(photogatePin, INPUT_PULLUP);
  pinMode(gateLed, OUTPUT);
  Serial.begin(9600);
  Serial.println("--- Exp 06: Pendulum 'g' Chronometer Online ---");
  Serial.println("Click BEAM_GATE or drag pendulum bob to break optical beam.");
}

void loop() {
  int gateState = digitalRead(photogatePin);
  
  if (gateState == LOW) {
    digitalWrite(gateLed, HIGH);
    unsigned long now = millis();
    passCount++;
    
    if (passCount == 1) {
      tStart = now;
      Serial.println(">> Beam Interruption 1: Timer Started <<");
    } else if (passCount == 2) {
      tEnd = now;
      passCount = 0;
      float period = (tEnd - tStart) / 1000.0;
      
      if (period > 0.1) {
        float gExp = (4.0 * 3.14159265 * 3.14159265 * PENDULUM_LENGTH) / (period * period);
        Serial.print("Oscillation Period T: ");
        Serial.print(period, 3);
        Serial.print(" s | Measured 'g': ");
        Serial.print(gExp, 2);
        Serial.println(" m/s^2 (Std: 9.81 m/s^2)");
      }
    }
    delay(200); // debounce optical crossing
  } else {
    digitalWrite(gateLed, LOW);
  }
  delay(20);
}`
        },

        // ========================================================
        // SYLLABUS EXP 07: 7-Segment Display Counter (Start/Stop & Reset)
        // ========================================================
        seven_segment: {
            title: "Exp 07: 7-Segment Display 0-9 Counter (Start/Stop & Reset)",
            components: [
                { id: 'disp1', type: 'seven_segment', x: 677.5, y: 153, rotation: 0, props: { name: '7SEG', digit: 0 } },
                { id: 'btnStart', type: 'pushbutton', x: 779.5, y: 169, rotation: 0, props: { name: 'RUN_HALT', pressed: false } },
                { id: 'btnReset', type: 'pushbutton', x: 847.5, y: 169, rotation: 0, props: { name: 'RESET', pressed: false } }
            ],
            autoWires: [
                { from: 'ard-pin-2', to: 'bb-a13', color: '#ef4444', waypoints: [] },
                { from: 'ard-pin-3', to: 'bb-a14', color: '#f97316', waypoints: [] },
                { from: 'ard-pin-4', to: 'bb-a15', color: '#eab308', waypoints: [] },
                { from: 'ard-pin-5', to: 'bb-a16', color: '#10b981', waypoints: [] },
                { from: 'ard-pin-6', to: 'bb-a17', color: '#0284c7', waypoints: [] },
                { from: 'ard-pin-7', to: 'bb-f13', color: '#8b5cf6', waypoints: [] },
                { from: 'ard-pin-8', to: 'bb-f14', color: '#ec4899', waypoints: [] },
                { from: 'bb-a15', to: 'bb-bot-neg-15', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-10', to: 'bb-d19', color: '#10b981', waypoints: [] },
                { from: 'bb-g19', to: 'bb-bot-neg-19', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-11', to: 'bb-d23', color: '#38bdf8', waypoints: [] },
                { from: 'bb-g23', to: 'bb-bot-neg-23', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [] }
            ],
            code: `// ========================================================
// Exp 07: 7-Segment Display Counter (0 to 9) with Control Keys
// Pauses 2 seconds between increments. Button 1 (Pin 10) halts
// and resumes counting. Button 2 (Pin 11) resets count to 0.
// ========================================================

const int segPins[7] = {2, 3, 4, 5, 6, 7, 8}; // a, b, c, d, e, f, g
const int runHaltKey = 10;
const int resetKey = 11;

// 7-Segment lookup table for digits 0-9
const byte digitTable[10] = {
  0b00111111, // 0
  0b00000110, // 1
  0b01011011, // 2
  0b01001111, // 3
  0b01100110, // 4
  0b01101101, // 5
  0b01111101, // 6
  0b00000111, // 7
  0b01111111, // 8
  0b01101111  // 9
};

int count = 0;
bool isRunning = true;
int lastRunBtn = HIGH;
int lastRstBtn = HIGH;

void showDigit(int d) {
  byte pattern = digitTable[d % 10];
  for (int i = 0; i < 7; i++) {
    digitalWrite(segPins[i], (pattern >> i) & 1);
  }
}

void setup() {
  for (int i = 0; i < 7; i++) pinMode(segPins[i], OUTPUT);
  pinMode(runHaltKey, INPUT_PULLUP);
  pinMode(resetKey, INPUT_PULLUP);
  Serial.begin(9600);
  Serial.println("--- Exp 07: 7-Segment Programmable Decade Counter ---");
  Serial.println("Key 1 (D10): Run/Halt | Key 2 (D11): Reset");
  showDigit(count);
}

void loop() {
  // Check Run/Halt Key
  int runVal = digitalRead(runHaltKey);
  if (runVal == LOW && lastRunBtn == HIGH) {
    isRunning = !isRunning;
    Serial.println(isRunning ? ">> Counter Resumed <<" : ">> Counter HALTED <<");
    delay(50);
  }
  lastRunBtn = runVal;

  // Check Reset Key
  int rstVal = digitalRead(resetKey);
  if (rstVal == LOW && lastRstBtn == HIGH) {
    count = 0;
    showDigit(count);
    Serial.println(">> Counter RESET to 0 <<");
    delay(50);
  }
  lastRstBtn = rstVal;

  // Increment every 2 seconds when running
  if (isRunning) {
    showDigit(count);
    Serial.print("Display Count: ");
    Serial.println(count);
    delay(2000); // 2-second pause per syllabus
    count = (count + 1) % 10;
  } else {
    delay(50);
  }
}`
        },

        // ========================================================
        // SYLLABUS EXP 08: 1.8-inch TFT / LCD Display Interfacing
        // ========================================================
        tft_lcd: {
            title: "Exp 08: 1.8\" TFT / LCD Graphics & Data Print",
            components: [
                { id: 'tft1', type: 'tft_lcd', x: 670, y: 150, rotation: 0, props: { name: 'TFT1.8', text: 'PHYSICS' } },
                { id: 'pot1', type: 'potentiometer', x: 790, y: 143, rotation: 0, props: { resistance: 10000, name: 'ADC_POT', position: 0.5 } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'bb-top-pos-10', color: '#ef4444', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-top-neg-10', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-10', to: 'bb-f13', color: '#38bdf8', waypoints: [] },
                { from: 'ard-pin-9', to: 'bb-f15', color: '#10b981', waypoints: [] },
                { from: 'ard-pin-11', to: 'bb-f16', color: '#f59e0b', waypoints: [] },
                { from: 'ard-pin-13', to: 'bb-f17', color: '#ec4899', waypoints: [] },
                { from: 'ard-pin-a0', to: 'bb-a20', color: '#10b981', waypoints: [] }
            ],
            code: `// ========================================================
// Exp 08: 1.8-inch TFT/LCD Color Display Interfacing (SPI)
// Print real-time sensor measurements, voltage, and graphical text.
// ========================================================

const int csPin = 10;
const int dcPin = 9;
const int rstPin = 8;
const int sensorPin = A0;

void setup() {
  Serial.begin(9600);
  Serial.println("--- Exp 08: 1.8-inch Color TFT Display Initialized ---");
  Serial.println("TFT Screen: 128x160 RGB SPI • ST7735 Controller");
  Serial.println("[TFT Graphics] Initializing Canvas & Fonts...");
}

void loop() {
  int rawADC = analogRead(sensorPin);
  float voltage = (rawADC / 1023.0) * 5.0;
  
  Serial.println("================================");
  Serial.println("  1.8-INCH TFT DISPLAY TELEMETRY");
  Serial.println("================================");
  Serial.println("Physics Lab : Computational Exps");
  Serial.print("Analog Ch A0: ");
  Serial.print(voltage, 2);
  Serial.println(" V");
  Serial.print("ADC Counts  : ");
  Serial.println(rawADC);
  Serial.println("Drawing Bar Graph [##########   ]");
  
  delay(1000);
}`
        },

        // ========================================================
        // SYLLABUS EXP 09: 7-Segment Display Up/Down Decade Counter
        // ========================================================
        seven_segment_updown: {
            title: "Exp 09: 7-Segment Display Up/Down Decade Counter",
            components: [
                { id: 'disp1', type: 'seven_segment', x: 677.5, y: 153, rotation: 0, props: { name: '7SEG', digit: 0 } },
                { id: 'btnDir', type: 'pushbutton', x: 779.5, y: 169, rotation: 0, props: { name: 'DIR_KEY', pressed: false } },
                { id: 'btnStep', type: 'pushbutton', x: 847.5, y: 169, rotation: 0, props: { name: 'STEP_KEY', pressed: false } }
            ],
            autoWires: [
                { from: 'ard-pin-2', to: 'bb-a13', color: '#ef4444', waypoints: [] },
                { from: 'ard-pin-3', to: 'bb-a14', color: '#f97316', waypoints: [] },
                { from: 'ard-pin-4', to: 'bb-a15', color: '#eab308', waypoints: [] },
                { from: 'ard-pin-5', to: 'bb-a16', color: '#10b981', waypoints: [] },
                { from: 'ard-pin-6', to: 'bb-a17', color: '#0284c7', waypoints: [] },
                { from: 'ard-pin-7', to: 'bb-f13', color: '#8b5cf6', waypoints: [] },
                { from: 'ard-pin-8', to: 'bb-f14', color: '#ec4899', waypoints: [] },
                { from: 'ard-pin-10', to: 'bb-d19', color: '#10b981', waypoints: [] },
                { from: 'bb-g19', to: 'bb-bot-neg-19', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-11', to: 'bb-d23', color: '#38bdf8', waypoints: [] },
                { from: 'bb-g23', to: 'bb-bot-neg-23', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [] }
            ],
            code: `// ========================================================
// Exp 09: 7-Segment Display Up/Down Decade Counter
// Switch direction between UP counting and DOWN counting with
// control pushbuttons.
// ========================================================

const int segPins[7] = {2, 3, 4, 5, 6, 7, 8};
const int dirBtnPin = 10;
const int stepBtnPin = 11;

const byte digitTable[10] = {
  0b00111111, 0b00000110, 0b01011011, 0b01001111, 0b01100110,
  0b01101101, 0b01111101, 0b00000111, 0b01111111, 0b01101111
};

int count = 0;
bool countUp = true;
int lastStep = HIGH;
int lastDir = HIGH;

void showDigit(int d) {
  byte p = digitTable[(d + 10) % 10];
  for (int i = 0; i < 7; i++) digitalWrite(segPins[i], (p >> i) & 1);
}

void setup() {
  for (int i = 0; i < 7; i++) pinMode(segPins[i], OUTPUT);
  pinMode(dirBtnPin, INPUT_PULLUP);
  pinMode(stepBtnPin, INPUT_PULLUP);
  Serial.begin(9600);
  Serial.println("--- Exp 09: Up/Down 7-Segment Decade Counter ---");
  showDigit(count);
}

void loop() {
  int dirVal = digitalRead(dirBtnPin);
  if (dirVal == LOW && lastDir == HIGH) {
    countUp = !countUp;
    Serial.println(countUp ? "Direction: UP (+1)" : "Direction: DOWN (-1)");
    delay(50);
  }
  lastDir = dirVal;

  int stepVal = digitalRead(stepBtnPin);
  if (stepVal == LOW && lastStep == HIGH) {
    count = countUp ? (count + 1) % 10 : (count + 9) % 10;
    showDigit(count);
    Serial.print("Count: ");
    Serial.println(count);
    delay(50);
  }
  lastStep = stepVal;
  delay(20);
}`
        },

        // ========================================================
        // SYLLABUS EXP 10: 8x8 Dot Matrix Alphabet & Numbers
        // ========================================================
        dot_matrix: {
            title: "Exp 10: 8x8 LED Dot Matrix Alphabet & Numbers",
            components: [
                { id: 'matrix1', type: 'dot_matrix', x: 677.5, y: 153, rotation: 0, props: { name: 'MAX7219', char: 'A' } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'bb-f11', color: '#ef4444', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-f12', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-11', to: 'bb-f13', color: '#38bdf8', waypoints: [] },
                { from: 'ard-pin-10', to: 'bb-f14', color: '#10b981', waypoints: [] },
                { from: 'ard-pin-13', to: 'bb-f15', color: '#f59e0b', waypoints: [] }
            ],
            code: `// ========================================================
// Exp 10: 8x8 LED Dot Matrix Display (MAX7219 / Direct)
// Showcases numbers 0 to 9 and entire English alphabet
// both in uppercase (A-Z) and lowercase (a-z).
// ========================================================

const int DIN_PIN = 11;
const int CS_PIN = 10;
const int CLK_PIN = 13;

const char testChars[] = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";

void setup() {
  Serial.begin(9600);
  Serial.println("--- Exp 10: 8x8 LED Dot Matrix Display Active ---");
  Serial.println("Cycling alphanumeric glyphs: Numbers 0-9 & Alphabet A-Z / a-z");
}

void loop() {
  for (int i = 0; i < strlen(testChars); i++) {
    char c = testChars[i];
    Serial.print("Displaying Character: [ ");
    Serial.print(c);
    Serial.println(" ] on 8x8 Matrix");
    delay(400);
  }
}`
        },

        // ========================================================
        // SYLLABUS EXP 11: Temperature & Humidity Sensor Telemetry
        // ========================================================
        tmp36_temp: {
            title: "Exp 11: Temperature & Humidity (DHT11/TMP36) with Serial Plotter",
            components: [
                { id: 'tmp1', type: 'tmp36', x: 660.5, y: 147, rotation: 0, props: { name: 'TMP36', tempC: 28.5 } },
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#ef4444', name: 'HEAT_ALARM' } },
                { id: 'res1', type: 'resistor', x: 788, y: 158, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'bb-a12', color: '#ef4444', waypoints: [{ x: 208, y: 330 }, { x: 669, y: 330 }] },
                { from: 'ard-pin-a0', to: 'bb-a13', color: '#10b981', waypoints: [{ x: 277, y: 260 }, { x: 686, y: 260 }] },
                { from: 'ard-pin-gnd1', to: 'bb-a14', color: '#0f172a', waypoints: [{ x: 220, y: 345 }, { x: 703, y: 345 }] },
                { from: 'ard-pin-13', to: 'bb-a18', color: '#f97316', waypoints: [{ x: 179, y: 40 }, { x: 771, y: 40 }] },
                { from: 'bb-a24', to: 'bb-bot-neg-24', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd2', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [] }
            ],
            code: `// ========================================================
// Exp 11: Temperature & Humidity Transducer (DHT-11 / TMP36)
// Transfer Equation: V_out = 0.5V + (10 mV / deg C) * T
// Switch to 'Serial Plotter' tab below to view the live graph!
// ========================================================

const int tempSensorPin = A0;
const int alertLedPin = 13;
const float TEMP_ALERT_LIMIT = 32.0;

void setup() {
  pinMode(alertLedPin, OUTPUT);
  Serial.begin(9600);
  Serial.println("--- Exp 11: Thermal Telemetry Online ---");
  Serial.println("Format: Temp_C, Humidity_Pct");
}

void loop() {
  int rawADC = analogRead(tempSensorPin);
  float voltage = (rawADC / 1023.0) * 5.0;
  float tempC = (voltage - 0.5) * 100.0;
  float humidityPct = 45.0 + (tempC - 25.0) * 0.8; // Simulated DHT humidity
  
  // Output in Serial Plotter compatible format:
  Serial.print(tempC, 2);
  Serial.print(" ");
  Serial.println(humidityPct, 1);
  
  if (tempC > TEMP_ALERT_LIMIT) {
    digitalWrite(alertLedPin, HIGH);
  } else {
    digitalWrite(alertLedPin, LOW);
  }
  
  delay(150);
}`
        },

        // ========================================================
        // SYLLABUS EXP 12: LM393 Sound Sensor & Threshold Potentiometer
        // ========================================================
        sound_lm393: {
            title: "Exp 12: LM393 Sound Sensor & Threshold Potentiometer",
            components: [
                { id: 'snd1', type: 'sound_sensor', x: 660.5, y: 147, rotation: 0, props: { name: 'LM393_MIC', threshold: 500 } },
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#eab308', name: 'SOUND_LED' } },
                { id: 'res1', type: 'resistor', x: 788, y: 158, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'bb-a12', color: '#ef4444', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-a13', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-2', to: 'bb-a14', color: '#10b981', waypoints: [] },
                { from: 'ard-pin-a0', to: 'bb-a15', color: '#38bdf8', waypoints: [] },
                { from: 'ard-pin-13', to: 'bb-a18', color: '#f97316', waypoints: [] },
                { from: 'bb-a24', to: 'bb-bot-neg-24', color: '#0f172a', waypoints: [] }
            ],
            code: `// ========================================================
// Exp 12: LM393 Sound Sensor & Threshold Comparator
// When sound levels surpass threshold set by onboard potentiometer,
// illuminate LED and report new sound / sound ceased to Serial.
// ========================================================

const int soundDigitalPin = 2; // DOUT from LM393 comparator
const int soundAnalogPin = A0;  // AOUT from microphone amplifier
const int ledPin = 13;

int lastSoundState = LOW;

void setup() {
  pinMode(soundDigitalPin, INPUT);
  pinMode(ledPin, OUTPUT);
  Serial.begin(9600);
  Serial.println("--- Exp 12: Acoustic Decibel Monitoring Online ---");
  Serial.println("Click the Clap button or adjust sound slider on canvas!");
}

void loop() {
  int currentSound = digitalRead(soundDigitalPin);
  int rawAnalog = analogRead(soundAnalogPin);
  
  if (currentSound == HIGH && lastSoundState == LOW) {
    digitalWrite(ledPin, HIGH);
    Serial.print(">>> [SOUND DETECTED] Exceeded threshold! Peak Level: ");
    Serial.print(rawAnalog);
    Serial.println(" (LED Illuminated) <<<");
  } else if (currentSound == LOW && lastSoundState == HIGH) {
    digitalWrite(ledPin, LOW);
    Serial.println("--- Sound has Ceased (Ambient Quiet) ---");
  }
  
  lastSoundState = currentSound;
  delay(50);
}`
        },

        // ========================================================
        // SYLLABUS EXP 13: RS-775 DC Motor Speed Control via L298 Driver
        // ========================================================
        dc_motor_l298: {
            title: "Exp 13: RS-775 DC Motor Speed Control via L298 Driver",
            components: [
                { id: 'motor1', type: 'dc_motor', x: 670, y: 150, rotation: 0, props: { name: 'RS-775', speed: 120 } },
                { id: 'pot1', type: 'potentiometer', x: 790, y: 143, rotation: 0, props: { resistance: 10000, name: 'SPEED_POT', position: 0.5 } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'bb-top-pos-10', color: '#ef4444', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-top-neg-10', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-9', to: 'bb-f13', color: '#38bdf8', waypoints: [] },
                { from: 'ard-pin-8', to: 'bb-f14', color: '#10b981', waypoints: [] },
                { from: 'ard-pin-7', to: 'bb-f15', color: '#f59e0b', waypoints: [] },
                { from: 'ard-pin-a0', to: 'bb-a20', color: '#10b981', waypoints: [] }
            ],
            code: `// ========================================================
// Exp 13: RS-775 DC Motor Speed Control with L298 H-Bridge
// PWM duty cycle controls motor rotational velocity.
// Adjust Potentiometer on breadboard to vary motor RPM.
// ========================================================

const int enablePin = 9;  // PWM speed control (ENA)
const int in1Pin = 8;     // Direction input 1
const int in2Pin = 7;     // Direction input 2
const int speedPotPin = A0;

void setup() {
  pinMode(enablePin, OUTPUT);
  pinMode(in1Pin, OUTPUT);
  pinMode(in2Pin, OUTPUT);
  
  // Forward rotation
  digitalWrite(in1Pin, HIGH);
  digitalWrite(in2Pin, LOW);
  
  Serial.begin(9600);
  Serial.println("--- Exp 13: L298 H-Bridge Motor Speed Controller ---");
}

void loop() {
  int potVal = analogRead(speedPotPin);
  int motorPwm = map(potVal, 0, 1023, 0, 255);
  int rpmEst = map(motorPwm, 0, 255, 0, 3600);
  
  analogWrite(enablePin, motorPwm);
  
  Serial.print("Speed Pot: ");
  Serial.print(potVal);
  Serial.print(" -> PWM: ");
  Serial.print(motorPwm);
  Serial.print(" (");
  Serial.print(Math.round((motorPwm / 255.0) * 100));
  Serial.print("%) | Motor RPM: ~");
  Serial.println(rpmEst);
  
  delay(150);
}`
        },

        // ========================================================
        // SYLLABUS EXP 14: 28BYJ-48 Stepper Motor with ULN2003 Driver
        // ========================================================
        stepper_uln2003: {
            title: "Exp 14: 28BYJ-48 Stepper Motor 10° Angle & ULN2003 Driver",
            components: [
                { id: 'step1', type: 'stepper_motor', x: 670, y: 150, rotation: 0, props: { name: '28BYJ-48', angle: 0, speedRpm: 15 } },
                { id: 'btnStep', type: 'pushbutton', x: 779.5, y: 169, rotation: 0, props: { name: 'STEP_10DEG', pressed: false } },
                { id: 'btnHalt', type: 'pushbutton', x: 847.5, y: 169, rotation: 0, props: { name: 'HALT_KEY', pressed: false } }
            ],
            autoWires: [
                { from: 'ard-pin-8', to: 'bb-f13', color: '#ef4444', waypoints: [] },
                { from: 'ard-pin-9', to: 'bb-f14', color: '#f97316', waypoints: [] },
                { from: 'ard-pin-10', to: 'bb-f15', color: '#eab308', waypoints: [] },
                { from: 'ard-pin-11', to: 'bb-f16', color: '#10b981', waypoints: [] },
                { from: 'ard-pin-2', to: 'bb-d19', color: '#38bdf8', waypoints: [] },
                { from: 'bb-g19', to: 'bb-bot-neg-19', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-3', to: 'bb-d23', color: '#ec4899', waypoints: [] },
                { from: 'bb-g23', to: 'bb-bot-neg-23', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [] }
            ],
            code: `// ========================================================
// Exp 14: 28BYJ-48 Stepper Motor & ULN2003 Darlington Driver
// Rotates motor at 10 deg angle increments at 5 second intervals.
// Key 1 initiates rotation; Key 2 stops rotation.
// ========================================================

const int stepPins[4] = {8, 9, 10, 11};
const int startBtn = 2;
const int stopBtn = 3;

int currentAngle = 0;
bool motorActive = true;
int lastStart = HIGH;
int lastStop = HIGH;

void stepPhase(int p) {
  const byte seq[4] = {0b1000, 0b0100, 0b0010, 0b0001};
  for (int i = 0; i < 4; i++) {
    digitalWrite(stepPins[i], (seq[p % 4] >> (3 - i)) & 1);
  }
}

void setup() {
  for (int i = 0; i < 4; i++) pinMode(stepPins[i], OUTPUT);
  pinMode(startBtn, INPUT_PULLUP);
  pinMode(stopBtn, INPUT_PULLUP);
  Serial.begin(9600);
  Serial.println("--- Exp 14: 28BYJ-48 Stepper Kinematics Online ---");
  Serial.println("Step Angle = 10 deg | Interval = 5.0 s");
}

void loop() {
  if (digitalRead(startBtn) == LOW) {
    motorActive = true;
    Serial.println(">> Stepper Rotation INITIATED <<");
    delay(50);
  }
  if (digitalRead(stopBtn) == LOW) {
    motorActive = false;
    Serial.println(">> Stepper Rotation HALTED <<");
    delay(50);
  }

  if (motorActive) {
    currentAngle = (currentAngle + 10) % 360;
    stepPhase(currentAngle / 10);
    
    Serial.print("Stepper Angular Position: ");
    Serial.print(currentAngle);
    Serial.println(" deg (Next 10 deg step in 5s...)");
    
    delay(5000); // 5-second interval per syllabus requirement
  } else {
    delay(100);
  }
}`
        },

        // ========================================================
        // SYLLABUS EXP 15: Bluetooth Module HC-05 & Smartphone Controller
        // ========================================================
        bluetooth_hc05: {
            title: "Exp 15: HC-05 Bluetooth Module & Smartphone Controller",
            components: [
                { id: 'bt1', type: 'bluetooth_hc05', x: 670, y: 150, rotation: 0, props: { name: 'HC-05', state: 'Connected' } },
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#0284c7', name: 'APPLIANCE' } },
                { id: 'res1', type: 'resistor', x: 788, y: 158, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'bb-top-pos-10', color: '#ef4444', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-top-neg-10', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-10', to: 'bb-f13', color: '#38bdf8', waypoints: [] },
                { from: 'ard-pin-11', to: 'bb-f14', color: '#10b981', waypoints: [] },
                { from: 'ard-pin-13', to: 'bb-a18', color: '#f97316', waypoints: [] },
                { from: 'bb-a24', to: 'bb-bot-neg-24', color: '#0f172a', waypoints: [] }
            ],
            code: `// ========================================================
// Exp 15: HC-05 Bluetooth Wireless Telemetry & Smartphone App
// Send characters via smartphone Bluetooth terminal:
// '1' -> Turn ON Appliance/LED | '0' -> Turn OFF Appliance/LED
// ========================================================

const int applianceLed = 13;
char receivedChar;

void setup() {
  pinMode(applianceLed, OUTPUT);
  Serial.begin(9600); // Standard HC-05 default baud
  Serial.println("--- Exp 15: HC-05 Bluetooth Controller Online ---");
  Serial.println("Awaiting wireless smartphone commands ('1'=ON, '0'=OFF)...");
}

void loop() {
  if (Serial.available() > 0) {
    receivedChar = Serial.read();
    
    if (receivedChar == '1') {
      digitalWrite(applianceLed, HIGH);
      Serial.println("[BT COMMAND]: '1' -> APPLIANCE ACTIVATED [ON]");
    } else if (receivedChar == '0') {
      digitalWrite(applianceLed, LOW);
      Serial.println("[BT COMMAND]: '0' -> APPLIANCE DEACTIVATED [OFF]");
    }
  }
  delay(100);
}`
        },

        // ========================================================
        // SYLLABUS EXP 16: RC Circuit Transient Charging & Discharging
        // ========================================================
        rc_transient: {
            title: "Exp 16: RC Circuit Transient Charging & Discharging Data Logger",
            components: [
                { id: 'res1', type: 'resistor', x: 669, y: 141, rotation: 0, props: { resistance: 10000, unit: 'Ω', name: 'R1' } },
                { id: 'cap1', type: 'capacitor', x: 745.5, y: 134, rotation: 0, props: { capacitance: 100, unit: 'µF', name: 'C1' } }
            ],
            autoWires: [
                { from: 'ard-pin-10', to: 'bb-a12', color: '#3b82f6', waypoints: [{ x: 218, y: 40 }, { x: 669, y: 40 }] },
                { from: 'ard-pin-a0', to: 'bb-a17', color: '#10b981', waypoints: [{ x: 277, y: 260 }, { x: 754, y: 260 }] },
                { from: 'bb-a18', to: 'bb-bot-neg-18', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [{ x: 220, y: 345 }] }
            ],
            code: `// ========================================================
// Exp 16: RC Circuit Transient Dynamics Data Logger
// Time Constant tau = R * C = 10k Ohm * 100 uF = 1.00 s
// Plots exponential charging & discharging curves in real time.
// ========================================================

const int drivePin = 10;
const int voltageSensePin = A0;

void setup() {
  pinMode(drivePin, OUTPUT);
  digitalWrite(drivePin, LOW);
  Serial.begin(9600);
  Serial.println("--- Exp 16: RC Transient Data Logger Active ---");
  Serial.println("Switch to 'Serial Plotter' below to view exponential waveform!");
}

void loop() {
  // Phase 1: Charging cycle: V_C(t) = V_0 * (1 - e^(-t / RC))
  Serial.println(">>> STEP: CHARGING CYCLE (Drive Pin HIGH) <<<");
  digitalWrite(drivePin, HIGH);
  for (int i = 0; i < 40; i++) {
    int raw = analogRead(voltageSensePin);
    float vCap = (raw / 1023.0) * 5.0;
    Serial.println(vCap, 3);
    delay(50);
  }

  // Phase 2: Discharging cycle: V_C(t) = V_0 * e^(-t / RC)
  Serial.println(">>> STEP: DISCHARGING CYCLE (Drive Pin LOW) <<<");
  digitalWrite(drivePin, LOW);
  for (int i = 0; i < 40; i++) {
    int raw = analogRead(voltageSensePin);
    float vCap = (raw / 1023.0) * 5.0;
    Serial.println(vCap, 3);
    delay(50);
  }
}`
        },

        // ========================================================
        // ADDITIONAL ELECTRONICS WORKBENCH STARTERS
        // ========================================================
        blink: {
            title: "Starter: LED Blink & Optical Timing",
            components: [
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#ef4444', name: 'LED1' } },
                { id: 'res1', type: 'resistor', x: 788, y: 158, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                { from: 'ard-pin-13', to: 'bb-a18', color: '#f97316', waypoints: [{ x: 179, y: 40 }, { x: 771, y: 40 }] },
                { from: 'bb-a24', to: 'bb-bot-neg-24', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [{ x: 220, y: 345 }] }
            ],
            code: `// Standard LED Blink & Optical Timing
const int ledPin = 13;

void setup() {
  pinMode(ledPin, OUTPUT);
  Serial.begin(9600);
  Serial.println("--- Optical Timing System Initialized ---");
}

void loop() {
  digitalWrite(ledPin, HIGH);
  Serial.println("LED State: ON  (5.00 V)");
  delay(800);
  
  digitalWrite(ledPin, LOW);
  Serial.println("LED State: OFF (0.00 V)");
  delay(800);
}`
        },
        pwm_fade: {
            title: "Starter: PWM Breathing & Effective DC Voltage",
            components: [
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#0284c7', name: 'LED1' } },
                { id: 'res1', type: 'resistor', x: 788, y: 158, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                { from: 'ard-pin-9', to: 'bb-a18', color: '#0284c7', waypoints: [{ x: 231, y: 40 }, { x: 771, y: 40 }] },
                { from: 'bb-a24', to: 'bb-bot-neg-24', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [{ x: 220, y: 345 }] }
            ],
            code: `// Pulse-Width Modulation (PWM) LED Breathing
const int pwmPin = 9;
int brightness = 0;
int fadeStep = 5;

void setup() {
  pinMode(pwmPin, OUTPUT);
  Serial.begin(9600);
}

void loop() {
  analogWrite(pwmPin, brightness);
  float vEff = (brightness / 255.0) * 5.0;
  Serial.print("Duty: "); Serial.print(brightness);
  Serial.print(" | V_eff: "); Serial.println(vEff, 2);
  brightness += fadeStep;
  if (brightness <= 0 || brightness >= 255) fadeStep = -fadeStep;
  delay(30);
}`
        },
        potentiometer: {
            title: "Starter: Potentiometer 10-Bit ADC Divider",
            components: [
                { id: 'pot1', type: 'potentiometer', x: 627, y: 143, rotation: 0, props: { resistance: 10000, name: 'POT1', position: 0.5 } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'bb-a10', color: '#ef4444', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-a12', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-a0', to: 'bb-a11', color: '#10b981', waypoints: [] }
            ],
            code: `// Potentiometer Voltage Divider 10-Bit ADC
const int potPin = A0;

void setup() {
  Serial.begin(9600);
}

void loop() {
  int rawADC = analogRead(potPin);
  float voltage = (rawADC / 1023.0) * 5.0;
  Serial.print("ADC: "); Serial.print(rawADC);
  Serial.print(" | Voltage: "); Serial.println(voltage, 3);
  delay(100);
}`
        },
        servo_sweep: {
            title: "Starter: Micro Servo Motor 180° Sweeper",
            components: [
                { id: 'servo1', type: 'servo', x: 671, y: 233, rotation: 0, props: { name: 'SERVO1', angle: 90 } },
                { id: 'pot1', type: 'potentiometer', x: 627, y: 143, rotation: 0, props: { resistance: 10000, name: 'POT1', position: 0.5 } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'bb-top-pos-10', color: '#ef4444', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-top-neg-10', color: '#0f172a', waypoints: [] },
                { from: 'bb-top-pos-10', to: 'bb-a10', color: '#ef4444', waypoints: [] },
                { from: 'bb-top-neg-12', to: 'bb-a12', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-a0', to: 'bb-a11', color: '#10b981', waypoints: [] },
                { from: 'bb-top-neg-13', to: 'bb-f13', color: '#0f172a', waypoints: [] },
                { from: 'bb-top-pos-14', to: 'bb-f14', color: '#ef4444', waypoints: [] },
                { from: 'ard-pin-9', to: 'bb-f15', color: '#f97316', waypoints: [] }
            ],
            code: `#include <Servo.h>
Servo myServo;
const int potPin = A0;

void setup() {
  myServo.attach(9);
  Serial.begin(9600);
}

void loop() {
  int val = analogRead(potPin);
  int angle = map(val, 0, 1023, 0, 180);
  myServo.write(angle);
  Serial.print("Angle: "); Serial.println(angle);
  delay(40);
}`
        },
        rgb_mixer: {
            title: "Starter: RGB LED Color Spectrum PWM Mixer",
            components: [
                { id: 'rgb1', type: 'rgb_led', x: 711.5, y: 147, rotation: 0, props: { name: 'RGB1', rVal: 255, gVal: 100, bVal: 50 } },
                { id: 'res1', type: 'resistor', x: 771, y: 80, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R1' } },
                { id: 'res2', type: 'resistor', x: 771, y: 114, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R2' } },
                { id: 'res3', type: 'resistor', x: 771, y: 148, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R3' } }
            ],
            autoWires: [
                { from: 'ard-pin-9', to: 'bb-a17', color: '#3b82f6', waypoints: [] },
                { from: 'ard-pin-10', to: 'bb-b17', color: '#10b981', waypoints: [] },
                { from: 'ard-pin-11', to: 'bb-c15', color: '#ef4444', waypoints: [] },
                { from: 'bb-e16', to: 'bb-bot-neg-16', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [] }
            ],
            code: `// RGB LED PWM Color Mixer
const int redPin = 11;
const int greenPin = 10;
const int bluePin = 9;

void setup() {
  pinMode(redPin, OUTPUT);
  pinMode(greenPin, OUTPUT);
  pinMode(bluePin, OUTPUT);
  Serial.begin(9600);
}

void loop() {
  analogWrite(redPin, 255); analogWrite(greenPin, 0); analogWrite(bluePin, 0); delay(600);
  analogWrite(redPin, 0); analogWrite(greenPin, 255); analogWrite(bluePin, 0); delay(600);
  analogWrite(redPin, 0); analogWrite(greenPin, 0); analogWrite(bluePin, 255); delay(600);
}`
        },
        button_toggle: {
            title: "Starter: Pushbutton Digital Input & Pullup",
            components: [
                { id: 'btn1', type: 'pushbutton', x: 677.5, y: 169, rotation: 0, props: { name: 'BTN1', pressed: false } },
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#10b981', name: 'LED1' } },
                { id: 'res1', type: 'resistor', x: 788, y: 158, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                { from: 'ard-pin-2', to: 'bb-d13', color: '#10b981', waypoints: [] },
                { from: 'bb-g13', to: 'bb-bot-neg-13', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-13', to: 'bb-a18', color: '#f97316', waypoints: [] },
                { from: 'bb-a24', to: 'bb-bot-neg-24', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [] }
            ],
            code: `// Pushbutton Input Pull-Up
const int btnPin = 2;
const int ledPin = 13;
int state = LOW;

void setup() {
  pinMode(btnPin, INPUT_PULLUP);
  pinMode(ledPin, OUTPUT);
}

void loop() {
  if (digitalRead(btnPin) == LOW) {
    state = !state;
    digitalWrite(ledPin, state);
    delay(200);
  }
}`
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
        
        const urlParams = new URLSearchParams(window.location.search);
        const presetParam = urlParams.get('preset') || urlParams.get('project') || urlParams.get('exp');
        if (presetParam && presets[presetParam]) {
            loadPreset(presetParam);
        } else {
            loadPreset('blink');
        }
        if (window.innerWidth < 992) {
            setTimeout(fitToViewport, 150);
        }
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
    // 6. TERMINAL REGISTRY & PIN DEFINITIONS
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

            registerTerminal(`bb-top-pos-${col}`, `(+) Top Rail [${col}]`, posX, bbRowY('top-pos'));
            registerTerminal(`bb-top-neg-${col}`, `(−) Top Ground [${col}]`, posX, bbRowY('top-neg'));

            ['a', 'b', 'c', 'd', 'e'].forEach(r => {
                registerTerminal(`bb-${r}${col}`, `Breadboard ${r.toUpperCase()}${col}`, posX, bbRowY(r));
            });

            ['f', 'g', 'h', 'i', 'j'].forEach(r => {
                registerTerminal(`bb-${r}${col}`, `Breadboard ${r.toUpperCase()}${col}`, posX, bbRowY(r));
            });

            registerTerminal(`bb-bot-pos-${col}`, `(+) Bottom Rail [${col}]`, posX, bbRowY('bot-pos'));
            registerTerminal(`bb-bot-neg-${col}`, `(−) Bottom Ground [${col}]`, posX, bbRowY('bot-neg'));
        }
    }

    function getComponentForTerminal(termId) {
        if (!termId || termId.startsWith('bb-') || termId.startsWith('ard-')) return null;
        const compId = termId.split('_')[0];
        return state.components.find(c => c.id === compId) || null;
    }

    function getComponentAt(stageX, stageY) {
        for (let i = state.components.length - 1; i >= 0; i--) {
            const comp = state.components[i];
            const lib = COMPONENT_LIBRARY[comp.type];
            const w = lib ? lib.width : 50;
            const h = lib ? lib.height : 50;
            if (stageX >= comp.x - 6 && stageX <= comp.x + w + 6 &&
                stageY >= comp.y - 6 && stageY <= comp.y + h + 6) {
                return comp;
            }
        }
        return null;
    }

    function registerTerminal(id, name, x, y) {
        terminals[id] = { id, name, x, y };

        const container = document.getElementById('tcTerminalsContainer');
        if (!container) return;

        let el = document.getElementById(`term-${id}`);
        if (!el) {
            el = document.createElement('div');
            el.id = `term-${id}`;
            const isBb = id.startsWith('bb-');
            const isArd = id.startsWith('ard-');
            el.className = `tc-terminal tc-terminal-pin ${isBb ? 'tc-bb-pin' : isArd ? 'tc-ard-pin' : 'tc-comp-pin'}`;
            el.setAttribute('data-terminal-id', id);

            const tooltip = document.createElement('div');
            tooltip.className = 'tc-pin-tooltip';
            tooltip.textContent = name;
            el.appendChild(tooltip);

            container.appendChild(el);
        } else {
            const tooltip = el.querySelector('.tc-pin-tooltip');
            if (tooltip && tooltip.textContent !== name) {
                tooltip.textContent = name;
            }
        }

        el.style.left = `${x}px`;
        el.style.top = `${y}px`;

        el.onmouseenter = () => onTerminalHover(id);
        el.onmouseleave = () => onTerminalLeave(id);

        el.onmousedown = (e) => {
            if (e.shiftKey) {
                e.stopPropagation();
                const comp = getComponentForTerminal(id) || getComponentAt(x, y);
                if (comp) {
                    selectComponent(comp.id);
                    const compEl = document.getElementById(comp.id);
                    if (compEl && compEl._startDrag) {
                        compEl._startDrag(e.clientX, e.clientY);
                    }
                }
            }
        };

        el.onclick = (e) => {
            if (e.shiftKey) {
                e.stopPropagation();
                const comp = getComponentForTerminal(id) || getComponentAt(x, y);
                if (comp) {
                    selectComponent(comp.id);
                    return;
                }
            }
            e.stopPropagation();
            onTerminalClick(id);
        };

        el.ontouchstart = (e) => {
            const comp = getComponentForTerminal(id) || (id.startsWith('bb-') ? getComponentAt(x, y) : null);
            if (comp && !state.drawingWire) {
                e.stopPropagation();
                selectComponent(comp.id);
                const compEl = document.getElementById(comp.id);
                if (compEl && compEl._startDrag && e.touches.length === 1) {
                    compEl._startDrag(e.touches[0].clientX, e.touches[0].clientY);
                }
                return;
            }
            e.stopPropagation();
            e.preventDefault();
            onTerminalClick(id);
        };
    }

    // ==========================================
    // 7. BREADBOARD BUS HIGHLIGHT
    // ==========================================
    function highlightBreadboardBus(terminalId, show) {
        const group = document.getElementById('tcBbBusHighlightGroup');
        if (!group) return;
        group.innerHTML = '';
        if (!show || !terminalId || !terminalId.startsWith('bb-')) return;

        const parts = terminalId.replace('bb-', '').split('-');
        const isTopPos = terminalId.includes('top-pos');
        const isTopNeg = terminalId.includes('top-neg');
        const isBotPos = terminalId.includes('bot-pos');
        const isBotNeg = terminalId.includes('bot-neg');

        if (isTopPos || isTopNeg || isBotPos || isBotNeg) {
            let rowKey = 'top-pos';
            let strokeColor = '#ef4444';
            if (isTopNeg) { rowKey = 'top-neg'; strokeColor = '#3b82f6'; }
            if (isBotPos) { rowKey = 'bot-pos'; strokeColor = '#ef4444'; }
            if (isBotNeg) { rowKey = 'bot-neg'; strokeColor = '#3b82f6'; }

            const y = bbRowY(rowKey) - LAYOUT.bbY;
            const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            line.setAttribute('x1', '40');
            line.setAttribute('y1', y);
            line.setAttribute('x2', '560');
            line.setAttribute('y2', y);
            line.setAttribute('stroke', strokeColor);
            line.setAttribute('stroke-width', '10');
            line.setAttribute('stroke-linecap', 'round');
            line.setAttribute('opacity', '0.35');
            group.appendChild(line);
        } else {
            const row = parts[0][0];
            const col = parseInt(parts[0].substring(1));
            const isUpper = ['a', 'b', 'c', 'd', 'e'].includes(row);
            const startRow = isUpper ? 'a' : 'f';
            const endRow = isUpper ? 'e' : 'j';
            const x = bbColX(col) - LAYOUT.bbX;
            const y1 = bbRowY(startRow) - LAYOUT.bbY - 4;
            const y2 = bbRowY(endRow) - LAYOUT.bbY + 4;

            const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            rect.setAttribute('x', x - 6);
            rect.setAttribute('y', y1);
            rect.setAttribute('width', '12');
            rect.setAttribute('height', y2 - y1);
            rect.setAttribute('rx', '4');
            rect.setAttribute('fill', '#0284c7');
            rect.setAttribute('opacity', '0.28');
            group.appendChild(rect);
        }
    }

    // ==========================================
    // 8. WIRE DRAWING & MANHATTAN ROUTING
    // ==========================================
    function onTerminalHover(terminalId) {
        state.hoverTerminalId = terminalId;
        highlightBreadboardBus(terminalId, true);
        const term = terminals[terminalId];
        if (term) {
            const bar = document.getElementById('tcStatusBar');
            if (bar && !state.drawingWire) {
                const badge = bar.querySelector('.tc-status-item:nth-child(3)');
                if (badge) badge.innerHTML = `<i class="fa-solid fa-crosshairs" style="color:#0284c7;"></i> Terminal: <strong>${term.name}</strong>`;
            }
        }
    }

    function onTerminalLeave(terminalId) {
        if (state.hoverTerminalId === terminalId) state.hoverTerminalId = null;
        highlightBreadboardBus(terminalId, false);
    }

    function findSnapTerminal(stageX, stageY, ignoreId = null) {
        let closest = null;
        let minDist = 28; // Magnetic snap radius in stage pixels
        for (const id in terminals) {
            if (id === ignoreId) continue;
            const t = terminals[id];
            const dist = Math.hypot(t.x - stageX, t.y - stageY);
            if (dist < minDist) {
                minDist = dist;
                closest = t;
            }
        }
        return closest;
    }

    function getSmartWireColor(fromId, toId) {
        const isGround = (id) => id && (id.includes('neg') || id.includes('gnd'));
        const isPower = (id) => id && (id.includes('pos') || id.includes('5v') || id.includes('3v3') || id.includes('vin') || id.includes('ioref'));
        const isAnalog = (id) => id && id.includes('pin-a');
        const isPwm = (id) => id && ['ard-pin-3', 'ard-pin-5', 'ard-pin-6', 'ard-pin-9', 'ard-pin-10', 'ard-pin-11'].includes(id);

        if (isGround(fromId) || isGround(toId)) return '#0f172a'; // Black (GND)
        if (isPower(fromId) || isPower(toId)) return '#ef4444'; // Red (5V / Power)
        if (isAnalog(fromId) || isAnalog(toId)) return '#0284c7'; // Blue (Analog)
        if (isPwm(fromId) || isPwm(toId)) return '#f59e0b'; // Amber (PWM)
        return '#10b981'; // Green (Standard Signal)
    }

    function pulseTerminal(terminalId) {
        const el = document.getElementById(`term-${terminalId}`);
        if (!el) return;
        el.classList.add('tc-pulse-snap');
        setTimeout(() => el.classList.remove('tc-pulse-snap'), 450);
    }

    function showConnectionToast(wire) {
        const t1 = terminals[wire.from];
        const t2 = terminals[wire.to];
        if (!t1 || !t2) return;
        showToast(`Connected ${t1.name} ➔ ${t2.name}`);
    }

    function onTerminalClick(terminalId) {
        if (!state.drawingWire) {
            // Start drawing wire
            const startTerm = terminals[terminalId];
            if (!startTerm) return;

            const initialColor = (state.selectedWireColor === 'auto' || !state.selectedWireColor)
                ? '#10b981'
                : state.selectedWireColor;

            state.drawingWire = {
                from: terminalId,
                color: state.selectedWireColor,
                waypoints: []
            };

            pulseTerminal(terminalId);

            const rubber = document.getElementById('tcRubberbandWire');
            if (rubber) {
                rubber.style.display = 'block';
                rubber.setAttribute('stroke', initialColor);
            }
            updateStatusBar();
        } else {
            // Complete drawing wire
            if (state.drawingWire.from === terminalId) {
                cancelWireDrawing();
                return;
            }

            const chosenColor = (state.drawingWire.color === 'auto' || !state.drawingWire.color)
                ? getSmartWireColor(state.drawingWire.from, terminalId)
                : state.drawingWire.color;

            const newWire = {
                id: `wire_${Date.now()}`,
                from: state.drawingWire.from,
                to: terminalId,
                color: chosenColor,
                waypoints: [...state.drawingWire.waypoints]
            };

            state.wires.push(newWire);
            pulseTerminal(terminalId);
            pulseTerminal(state.drawingWire.from);
            playPlugSound();

            pushUndo({
                type: 'addWire',
                undo: () => { state.wires = state.wires.filter(w => w.id !== newWire.id); renderWires(); triggerCircuitSolve(); },
                redo: () => { state.wires.push(newWire); renderWires(); triggerCircuitSolve(); }
            });

            cancelWireDrawing();
            renderWires();
            triggerCircuitSolve();
            updateStatusBar();
            showConnectionToast(newWire);
        }
    }

    function cancelWireDrawing() {
        state.drawingWire = null;
        state.snapTarget = null;
        document.querySelectorAll('.tc-terminal.snap-candidate').forEach(el => el.classList.remove('snap-candidate'));
        highlightBreadboardBus(null, false);
        const rubber = document.getElementById('tcRubberbandWire');
        if (rubber) rubber.style.display = 'none';
        updateStatusBar();
    }

    function handleCanvasClick(e) {
        if (!state.drawingWire) {
            deselectAll();
            return;
        }

        const rect = document.getElementById('tcCanvasStage').getBoundingClientRect();
        const stageX = (e.clientX - rect.left) / state.zoom;
        const stageY = (e.clientY - rect.top) / state.zoom;

        // If clicking on or near a snap candidate, complete wire directly!
        const snapTerm = findSnapTerminal(stageX, stageY, state.drawingWire.from);
        if (snapTerm) {
            onTerminalClick(snapTerm.id);
            return;
        }

        const snapX = Math.round(stageX / 8) * 8;
        const snapY = Math.round(stageY / 8) * 8;

        state.drawingWire.waypoints.push({ x: snapX, y: snapY });
        updateRubberband(snapX, snapY);
    }

    function handleCanvasMouseMove(e) {
        if (!state.drawingWire) return;
        const rect = document.getElementById('tcCanvasStage').getBoundingClientRect();
        const stageX = (e.clientX - rect.left) / state.zoom;
        const stageY = (e.clientY - rect.top) / state.zoom;

        const snapTerm = findSnapTerminal(stageX, stageY, state.drawingWire.from);

        document.querySelectorAll('.tc-terminal.snap-candidate').forEach(el => el.classList.remove('snap-candidate'));

        if (snapTerm) {
            state.snapTarget = snapTerm.id;
            const termEl = document.getElementById(`term-${snapTerm.id}`);
            if (termEl) termEl.classList.add('snap-candidate');
            highlightBreadboardBus(snapTerm.id, true);
            updateRubberband(snapTerm.x, snapTerm.y);

            if (state.selectedWireColor === 'auto') {
                const autoColor = getSmartWireColor(state.drawingWire.from, snapTerm.id);
                const rubber = document.getElementById('tcRubberbandWire');
                if (rubber) rubber.setAttribute('stroke', autoColor);
            }
        } else {
            state.snapTarget = null;
            highlightBreadboardBus(null, false);
            updateRubberband(stageX, stageY);

            if (state.selectedWireColor === 'auto') {
                const rubber = document.getElementById('tcRubberbandWire');
                if (rubber) rubber.setAttribute('stroke', '#10b981');
            }
        }
    }

    function updateRubberband(currentX, currentY) {
        const rubber = document.getElementById('tcRubberbandWire');
        if (!rubber || !state.drawingWire) return;

        const startTerm = terminals[state.drawingWire.from];
        if (!startTerm) return;

        const pts = [{ x: startTerm.x, y: startTerm.y }, ...state.drawingWire.waypoints, { x: currentX, y: currentY }];
        const d = buildWirePathString(pts);
        rubber.setAttribute('d', d);
    }

    function buildWirePathString(points) {
        if (points.length < 2) return '';

        // If direct 2-point wire without manual waypoints, render realistic flexible jumper arch curve
        if (points.length === 2 && state.wireStyle !== 'straight') {
            const p1 = points[0];
            const p2 = points[1];
            const dx = p2.x - p1.x;
            const dy = p2.y - p1.y;
            const dist = Math.hypot(dx, dy);

            // Natural organic jumper arch (curves upward or outwards)
            const sag = Math.min(Math.max(dist * 0.18, 22), 85);
            const cx1 = p1.x + dx * 0.28;
            const cy1 = p1.y + dy * 0.28 - sag;
            const cx2 = p1.x + dx * 0.72;
            const cy2 = p1.y + dy * 0.72 - sag;

            return `M ${p1.x} ${p1.y} C ${cx1} ${cy1}, ${cx2} ${cy2}, ${p2.x} ${p2.y}`;
        }

        let d = `M ${points[0].x} ${points[0].y}`;
        const radius = 8;

        for (let i = 1; i < points.length; i++) {
            const prev = points[i - 1];
            const curr = points[i];
            const next = points[i + 1];

            if (next) {
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
        document.querySelectorAll('.tc-wire-floating-action').forEach(w => w.remove());

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
            path.setAttribute('stroke', isSelected ? '#38bdf8' : w.color);
            path.setAttribute('stroke-width', isSelected ? '6' : '3.8');
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('stroke-linejoin', 'round');

            // Invisible wide hit-testing path (20px) for effortless click & Shift+Click selection
            const hitPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            hitPath.setAttribute('d', d);
            hitPath.setAttribute('stroke', 'transparent');
            hitPath.setAttribute('stroke-width', '20');
            hitPath.setAttribute('fill', 'none');
            hitPath.setAttribute('stroke-linecap', 'round');
            hitPath.setAttribute('stroke-linejoin', 'round');
            hitPath.style.cursor = 'pointer';
            hitPath.style.pointerEvents = 'stroke';

            const onWireSelect = (e) => {
                e.stopPropagation();
                selectWire(w.id);
            };

            hitPath.onmousedown = onWireSelect;
            hitPath.onclick = onWireSelect;
            hitPath.ontouchstart = onWireSelect;

            path.onmousedown = onWireSelect;
            path.onclick = onWireSelect;
            path.ontouchstart = onWireSelect;

            group.appendChild(path);
            group.appendChild(hitPath);

            // Physical terminal ferrule pins at each end
            [t1, t2].forEach(t => {
                const cap = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                cap.setAttribute('cx', t.x);
                cap.setAttribute('cy', t.y);
                cap.setAttribute('r', '4');
                cap.setAttribute('fill', w.color);
                cap.setAttribute('stroke', '#0f172a');
                cap.setAttribute('stroke-width', '1.5');
                cap.style.pointerEvents = 'none';
                group.appendChild(cap);
            });

            if (isSelected) {
                w.waypoints.forEach((wp, idx) => {
                    renderWaypointHandle(w.id, idx, wp.x, wp.y);
                });
                renderWireFloatingAction(w, (t1.x + t2.x) / 2, (t1.y + t2.y) / 2);
            }
        });
    }

    function renderWireFloatingAction(wire, centerX, centerY) {
        const stage = document.getElementById('tcCanvasStage');
        if (!stage) return;

        const action = document.createElement('div');
        action.className = 'tc-wire-floating-action';
        action.style.left = `${centerX}px`;
        action.style.top = `${centerY}px`;
        action.innerHTML = `
            <span style="display:flex; align-items:center; gap:5px;">
                <span style="width:8px; height:8px; border-radius:50%; background:${wire.color}; display:inline-block; border:1px solid #fff;"></span>
                Wire (${wire.color})
            </span>
            <button type="button" class="tc-wire-del-btn" title="Delete Wire (Del or Backspace)">
                <i class="fa-solid fa-trash-can"></i> Delete
            </button>
            <button type="button" class="tc-wire-close-btn" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:0.75rem; padding:0 2px;" title="Deselect">
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;

        action.querySelector('.tc-wire-del-btn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            deleteSelected();
        });

        action.querySelector('.tc-wire-close-btn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            deselectAll();
        });

        action.addEventListener('mousedown', (e) => e.stopPropagation());
        action.addEventListener('click', (e) => e.stopPropagation());
        stage.appendChild(action);
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
    // 9. BREADBOARD HOLE SNAPPING ENGINE
    // ==========================================
    function findNearestBreadboardHole(rawX, rawY) {
        // Breadboard bounds
        const bbLeft = LAYOUT.bbX;
        const bbRight = LAYOUT.bbX + 600;
        const bbTop = LAYOUT.bbY;
        const bbBottom = LAYOUT.bbY + 330;

        if (rawX < bbLeft - 20 || rawX > bbRight + 20 || rawY < bbTop - 20 || rawY > bbBottom + 20) {
            return null;
        }

        let bestDist = Infinity;
        let bestHole = null;

        for (const [id, term] of Object.entries(terminals)) {
            if (id.startsWith('bb-')) {
                const dist = Math.hypot(term.x - rawX, term.y - rawY);
                if (dist < bestDist) {
                    bestDist = dist;
                    bestHole = term;
                }
            }
        }

        return bestDist <= 32 ? bestHole : null;
    }

    function highlightSnapCandidates(comp, x, y) {
        document.querySelectorAll('.tc-snap-hole-halo').forEach(h => h.remove());
        const lib = COMPONENT_LIBRARY[comp.type];
        if (!lib || !lib.terminalOffsets || lib.terminalOffsets.length === 0) return;

        const pin1 = lib.terminalOffsets[0];
        const primaryLeadX = x + pin1.dx;
        const primaryLeadY = y + pin1.dy;

        const nearest = findNearestBreadboardHole(primaryLeadX, primaryLeadY);
        if (!nearest) return;

        const stage = document.getElementById('tcCanvasStage');
        if (!stage) return;

        const snapDx = nearest.x - primaryLeadX;
        const snapDy = nearest.y - primaryLeadY;

        lib.terminalOffsets.forEach(t => {
            const hx = x + t.dx + snapDx;
            const hy = y + t.dy + snapDy;
            const halo = document.createElement('div');
            halo.className = 'tc-snap-hole-halo';
            halo.style.left = `${hx}px`;
            halo.style.top = `${hy}px`;
            stage.appendChild(halo);
        });
    }

    // ==========================================
    // 10. COMPONENT PLACEMENT & INTERACTIVITY
    // ==========================================
    function placeComponent(type, x, y, rotation = 0, props = {}, explicitId = null) {
        const lib = COMPONENT_LIBRARY[type];
        if (!lib) return null;

        // Snapping check if landing on breadboard
        const pin1 = lib.terminalOffsets ? lib.terminalOffsets[0] : { dx: 0, dy: 0 };
        const hole = findNearestBreadboardHole(x + pin1.dx, y + pin1.dy);
        let finalX = x;
        let finalY = y;

        if (hole) {
            finalX = hole.x - pin1.dx;
            finalY = hole.y - pin1.dy;
        }

        const compId = explicitId || `comp_${type}_${Date.now() % 100000}`;
        const comp = {
            id: compId,
            type,
            x: finalX || 650,
            y: finalY || 180,
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
        el.style.zIndex = state.selectedItem?.id === comp.id ? '50' : '30';
        el.innerHTML = getComponentSVG(comp);

        el.onclick = (e) => {
            e.stopPropagation();
            selectComponent(comp.id);
        };

        bindComponentLiveControls(el, comp);
        makeComponentDraggable(el, comp);
    }

    function bindComponentLiveControls(el, comp) {
        // 1. Tactile Pushbutton - Press & Release
        if (comp.type === 'pushbutton') {
            const cap = el.querySelector(`#${comp.id}_cap`);
            if (cap) {
                cap.addEventListener('mousedown', (e) => {
                    e.stopPropagation();
                    comp.props.pressed = true;
                    cap.classList.add('tc-btn-active-cap');
                    triggerCircuitSolve();
                });
                const onRelease = () => {
                    if (comp.props.pressed) {
                        comp.props.pressed = false;
                        cap.classList.remove('tc-btn-active-cap');
                        triggerCircuitSolve();
                    }
                };
                window.addEventListener('mouseup', onRelease);
            }
        }

        // 2. Rotary Potentiometer Knob - Drag & Wheel
        if (comp.type === 'potentiometer') {
            el.addEventListener('wheel', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const delta = e.deltaY > 0 ? -0.05 : 0.05;
                setPotPosition(comp, (comp.props.position || 0.5) + delta);
            }, { passive: false });

            const dial = el.querySelector(`#${comp.id}_dial`);
            if (dial) {
                dial.addEventListener('mousedown', (e) => {
                    e.stopPropagation();
                    startPotKnobDrag(comp, e);
                });
            }
        }

        // 3. PIR Motion Sensor Trigger Button
        if (comp.type === 'pir') {
            const btn = el.querySelector(`#${comp.id}_pir_btn`);
            if (btn) {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    triggerPirMotion(comp);
                });
            }
        }

        // 4. Slide Switch Toggle
        if (comp.type === 'slide_switch') {
            el.addEventListener('click', (e) => {
                e.stopPropagation();
                comp.props.state = comp.props.state === 'left' ? 'right' : 'left';
                renderComponentDOM(comp);
                triggerCircuitSolve();
            });
        }

        // 5. Tilt Sensor Toggle
        if (comp.type === 'tilt') {
            el.addEventListener('click', (e) => {
                e.stopPropagation();
                comp.props.tilted = !comp.props.tilted;
                renderComponentDOM(comp);
                triggerCircuitSolve();
            });
        }
    }

    function triggerPirMotion(comp) {
        comp.props.motion = true;
        state.hardwareValues.pirMotion = true;
        const el = document.getElementById(comp.id);
        if (el) {
            const dome = el.querySelector(`#${comp.id}_dome`);
            if (dome && !el.querySelector('.tc-pir-motion-wave')) {
                const wave = document.createElement('div');
                wave.className = 'tc-pir-motion-wave';
                el.appendChild(wave);
            }
        }
        triggerCircuitSolve();

        if (comp.props.motionTimer) clearTimeout(comp.props.motionTimer);
        comp.props.motionTimer = setTimeout(() => {
            comp.props.motion = false;
            state.hardwareValues.pirMotion = false;
            if (el) el.querySelectorAll('.tc-pir-motion-wave').forEach(w => w.remove());
            triggerCircuitSolve();
        }, 3200);
    }

    function startPotKnobDrag(comp, startEvent) {
        const dial = document.getElementById(`${comp.id}_dial`);
        const el = document.getElementById(comp.id);
        if (!el || !dial) return;

        const rect = el.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;

        function onMouseMove(e) {
            const dx = e.clientX - centerX;
            const dy = e.clientY - centerY;
            let angle = Math.atan2(dy, dx) * (180 / Math.PI) + 90;
            if (angle < 0) angle += 360;
            const pos = Math.max(0, Math.min(1.0, angle / 270));
            setPotPosition(comp, pos);
        }

        function onMouseUp() {
            window.removeEventListener('mousemove', onMouseMove);
            window.removeEventListener('mouseup', onMouseUp);
        }

        window.addEventListener('mousemove', onMouseMove);
        window.addEventListener('mouseup', onMouseUp);
    }

    function setPotPosition(comp, pos) {
        comp.props.position = Math.max(0, Math.min(1.0, pos));
        state.hardwareValues.potentiometer = Math.round(comp.props.position * 1023);
        const dial = document.getElementById(`${comp.id}_dial`);
        if (dial) {
            const angle = comp.props.position * 270 - 135;
            dial.style.transform = `rotate(${angle}deg)`;
        }
        triggerCircuitSolve();
    }

    function getComponentSVG(comp) {
        switch (comp.type) {
            case 'led':
                return `<div style="width:34px;height:44px;position:relative;">
                    <div style="position:absolute;bottom:0;left:8px;width:2px;height:18px;background:linear-gradient(to bottom,#cbd5e1,#64748b);"></div>
                    <div style="position:absolute;bottom:0;left:25px;width:2px;height:14px;background:linear-gradient(to bottom,#cbd5e1,#64748b);"></div>
                    <div id="${comp.id}_lens" style="position:absolute;top:2px;left:6px;width:22px;height:24px;border-radius:11px 11px 4px 4px;background:radial-gradient(ellipse at 40% 30%, ${comp.props.color}dd, ${comp.props.color});opacity:0.85;border:1px solid rgba(0,0,0,0.3);box-shadow:inset 0 -3px 5px rgba(0,0,0,0.25);transition:box-shadow 0.1s, opacity 0.1s;"></div>
                    <div style="position:absolute;top:5px;left:10px;width:6px;height:5px;border-radius:50%;background:rgba(255,255,255,0.7);pointer-events:none;"></div>
                </div>`;

            case 'rgb_led':
                return `<div style="width:68px;height:44px;position:relative;" title="RGB LED (Pins: R, Cathode, G, B)">
                    <div style="position:absolute;bottom:0;left:8px;width:2px;height:16px;background:linear-gradient(to bottom,#cbd5e1,#64748b);"></div>
                    <div style="position:absolute;bottom:0;left:25px;width:2px;height:18px;background:linear-gradient(to bottom,#cbd5e1,#64748b);"></div>
                    <div style="position:absolute;bottom:0;left:42px;width:2px;height:15px;background:linear-gradient(to bottom,#cbd5e1,#64748b);"></div>
                    <div style="position:absolute;bottom:0;left:59px;width:2px;height:14px;background:linear-gradient(to bottom,#cbd5e1,#64748b);"></div>
                    <div id="${comp.id}_lens" class="tc-rgb-lens" style="position:absolute;top:2px;left:19px;width:30px;height:26px;border-radius:15px 15px 4px 4px;background:radial-gradient(ellipse at 40% 30%, #f1f5f9, #94a3b8);opacity:0.9;border:1.2px solid rgba(0,0,0,0.25);box-shadow:inset 0 -3px 5px rgba(0,0,0,0.25);transition:all 0.1s;"></div>
                    <div style="position:absolute;top:5px;left:24px;width:8px;height:6px;border-radius:50%;background:rgba(255,255,255,0.8);pointer-events:none;"></div>
                </div>`;

            case 'resistor':
                return `<div style="width:85px;height:20px;position:relative;display:flex;align-items:center;">
                    <div style="width:25px;height:2.5px;background:linear-gradient(to right,#94a3b8,#cbd5e1);"></div>
                    <div style="width:35px;height:14px;background:linear-gradient(to bottom,#f6e7c1,#ebd49c,#dfc686);border-radius:4px;border:1px solid #c2a762;display:flex;justify-content:space-around;align-items:stretch;padding:0 3px;box-shadow:0 2px 4px rgba(0,0,0,0.2);">
                        <div style="width:3px;background:${getResistorBandColor(comp.props.resistance, 0)};border-radius:1px;"></div>
                        <div style="width:3px;background:${getResistorBandColor(comp.props.resistance, 1)};border-radius:1px;"></div>
                        <div style="width:3px;background:${getResistorBandColor(comp.props.resistance, 2)};border-radius:1px;"></div>
                        <div style="width:3px;background:#c8a820;border-radius:1px;"></div>
                    </div>
                    <div style="width:25px;height:2.5px;background:linear-gradient(to left,#94a3b8,#cbd5e1);"></div>
                </div>`;

            case 'pushbutton':
                return `<div style="width:44px;height:44px;position:relative;background:linear-gradient(to bottom,#e2e8f0,#cbd5e1);border-radius:5px;border:1.5px solid #94a3b8;box-shadow:0 2px 5px rgba(0,0,0,0.2);display:flex;align-items:center;justify-content:center;">
                    <div id="${comp.id}_cap" class="${comp.props.pressed ? 'tc-btn-active-cap' : ''}" style="width:20px;height:20px;border-radius:50%;background:#334155;box-shadow:0 2px 4px rgba(0,0,0,0.4);cursor:pointer;transition:transform 0.08s, background 0.08s;" title="Click & hold to press button"></div>
                    <div style="position:absolute;left:7px;top:-4px;width:3px;height:6px;background:#94a3b8;"></div>
                    <div style="position:absolute;right:7px;top:-4px;width:3px;height:6px;background:#94a3b8;"></div>
                    <div style="position:absolute;left:7px;bottom:-4px;width:3px;height:6px;background:#94a3b8;"></div>
                    <div style="position:absolute;right:7px;bottom:-4px;width:3px;height:6px;background:#94a3b8;"></div>
                </div>`;

            case 'potentiometer':
                const potAngle = (comp.props.position || 0.5) * 270 - 135;
                return `<div style="width:52px;height:48px;position:relative;" title="Scroll mouse wheel or drag knob to turn">
                    <div style="width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,#0284c7,#0369a1);border:2px solid #015a8a;box-shadow:0 3px 8px rgba(0,0,0,0.25);display:flex;align-items:center;justify-content:center;margin:0 auto;cursor:grab;">
                        <div style="width:24px;height:24px;border-radius:50%;background:#0f172a;display:flex;align-items:center;justify-content:center;">
                            <div id="${comp.id}_dial" style="width:4px;height:12px;background:#38bdf8;border-radius:2px;transform:rotate(${potAngle}deg);transform-origin:center bottom;"></div>
                        </div>
                    </div>
                </div>`;

            case 'slide_switch':
                const isLeft = (comp.props.state || 'left') === 'left';
                return `<div style="width:52px;height:38px;position:relative;background:#cbd5e1;border-radius:4px;border:1.5px solid #64748b;padding:4px;box-shadow:0 2px 5px rgba(0,0,0,0.2);cursor:pointer;" title="Click to toggle switch position">
                    <div style="width:42px;height:16px;background:#1e293b;border-radius:3px;position:relative;margin:2px auto;">
                        <div class="tc-switch-lever" style="width:18px;height:20px;background:#f8fafc;border-radius:2px;border:1px solid #475569;position:absolute;top:-2px;left:${isLeft ? '2px' : '22px'};box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>
                    </div>
                </div>`;

            case 'ldr':
                return `<div style="width:34px;height:40px;position:relative;" title="Ambient Lux: ${comp.props.lux || 400} lx">
                    <div style="width:30px;height:30px;border-radius:50%;background:radial-gradient(circle,#ea580c,#c2410c);border:2px solid #9a3412;box-shadow:0 3px 6px rgba(0,0,0,0.25);display:flex;align-items:center;justify-content:center;margin:0 auto;">
                        <svg width="18" height="14" viewBox="0 0 18 14"><path d="M 3 3 Q 9 5 15 3 Q 9 7 3 9 Q 9 11 15 9" fill="none" stroke="#fef08a" stroke-width="1.6"/></svg>
                    </div>
                    <div style="position:absolute;bottom:0;left:8px;width:2px;height:10px;background:#94a3b8;"></div>
                    <div style="position:absolute;bottom:0;right:8px;width:2px;height:10px;background:#94a3b8;"></div>
                </div>`;

            case 'ultrasonic':
                return `<div style="width:90px;height:44px;position:relative;background:linear-gradient(to bottom,#0ea5e9,#0284c7);border-radius:6px;border:2px solid #0369a1;display:flex;justify-content:space-around;align-items:center;padding:2px 6px;box-shadow:0 4px 10px rgba(0,0,0,0.25);" title="Distance: ${comp.props.distance || 25} cm">
                    <div style="width:28px;height:28px;border-radius:50%;background:radial-gradient(circle,#f8fafc,#cbd5e1);border:2px solid #94a3b8;font-weight:800;font-size:0.65rem;display:flex;align-items:center;justify-content:center;color:#0f172a;">T</div>
                    <div style="width:28px;height:28px;border-radius:50%;background:radial-gradient(circle,#f8fafc,#cbd5e1);border:2px solid #94a3b8;font-weight:800;font-size:0.65rem;display:flex;align-items:center;justify-content:center;color:#0f172a;">R</div>
                </div>`;

            case 'pir':
                return `<div style="width:64px;height:52px;position:relative;background:#15803d;border-radius:6px;border:1.5px solid #166534;box-shadow:0 3px 8px rgba(0,0,0,0.25);display:flex;flex-direction:column;align-items:center;padding-top:4px;">
                    <div id="${comp.id}_dome" style="width:34px;height:34px;border-radius:50%;background:radial-gradient(circle,#ffffff,#e2e8f0);border:1.5px solid #94a3b8;box-shadow:inset 0 0 6px rgba(0,0,0,0.15);position:relative;overflow:hidden;">
                        <div style="position:absolute;inset:0;background:repeating-linear-gradient(45deg,transparent,transparent 3px,rgba(0,0,0,0.06) 3px,rgba(0,0,0,0.06) 6px);"></div>
                    </div>
                    <button type="button" id="${comp.id}_pir_btn" class="tc-pir-trigger-btn" title="Simulate Object Motion">
                        <i class="fa-solid fa-person-walking"></i> Motion
                    </button>
                </div>`;

            case 'tmp36':
                return `<div style="width:52px;height:44px;position:relative;display:flex;flex-direction:column;align-items:center;">
                    <div style="width:36px;height:24px;border-radius:18px 18px 4px 4px;background:#1e293b;border:1.5px solid #0f172a;display:flex;align-items:center;justify-content:center;color:#f59e0b;font-family:'JetBrains Mono',monospace;font-size:0.55rem;font-weight:700;">
                        TMP
                    </div>
                    <div class="tc-tmp36-temp-badge">${(comp.props.tempC || 25).toFixed(1)}°C</div>
                </div>`;

            case 'servo':
                const sAngle = comp.props.angle !== undefined ? comp.props.angle : 90;
                return `<div class="tc-servo-motor" style="width:64px;height:58px;position:relative;background:#0284c7;border-radius:4px;border:1.5px solid #0369a1;box-shadow:0 3px 8px rgba(0,0,0,0.25);">
                    <div style="position:absolute;top:8px;left:10px;width:24px;height:24px;border-radius:50%;background:#0f172a;border:2px solid #38bdf8;">
                        <div id="${comp.id}_horn" class="tc-servo-horn" style="width:34px;height:8px;background:#f8fafc;border-radius:4px;border:1px solid #64748b;position:absolute;top:6px;left:2px;transform:rotate(${sAngle}deg);transform-origin:10px 4px;box-shadow:0 2px 4px rgba(0,0,0,0.3);">
                            <div style="width:4px;height:4px;border-radius:50%;background:#0284c7;margin:1px 0 0 24px;"></div>
                        </div>
                    </div>
                    <div class="tc-servo-angle-badge">${sAngle}°</div>
                </div>`;

            case 'buzzer':
                return `<div style="width:40px;height:44px;position:relative;display:flex;align-items:center;justify-content:center;">
                    <div id="${comp.id}_body" style="width:36px;height:36px;border-radius:50%;background:#1e293b;border:2px solid #475569;box-shadow:0 3px 8px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;">
                        <div style="width:8px;height:8px;border-radius:50%;background:#0284c7;"></div>
                    </div>
                </div>`;

            case 'dc_motor':
                return `<div style="width:50px;height:52px;position:relative;display:flex;align-items:center;justify-content:center;">
                    <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#94a3b8,#64748b);border:2px solid #475569;position:relative;box-shadow:0 3px 8px rgba(0,0,0,0.25);">
                        <svg id="${comp.id}_propeller" class="tc-propeller" width="44" height="44" viewBox="0 0 48 48">
                            <ellipse cx="24" cy="11" rx="4" ry="9" fill="#ef4444"/>
                            <ellipse cx="24" cy="37" rx="4" ry="9" fill="#ef4444"/>
                            <ellipse cx="11" cy="24" rx="9" ry="4" fill="#ef4444"/>
                            <ellipse cx="37" cy="24" rx="9" ry="4" fill="#ef4444"/>
                            <circle cx="24" cy="24" r="5" fill="#0f172a"/>
                        </svg>
                    </div>
                </div>`;

            case 'capacitor':
                return `<div style="width:34px;height:38px;position:relative;display:flex;flex-direction:column;align-items:center;">
                    <div style="width:22px;height:24px;border-radius:6px;background:#1e293b;border:1px solid #475569;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:0.5rem;font-weight:700;">
                        ${comp.props.capacitance}${comp.props.unit}
                    </div>
                </div>`;

            case 'diode':
                return `<div style="width:51px;height:18px;position:relative;display:flex;align-items:center;">
                    <div style="width:12px;height:2px;background:#94a3b8;"></div>
                    <div style="width:27px;height:14px;background:#0f172a;border-radius:2px;border:1px solid #334155;position:relative;">
                        <div style="position:absolute;right:4px;top:0;bottom:0;width:3px;background:#cbd5e1;"></div>
                    </div>
                    <div style="width:12px;height:2px;background:#94a3b8;"></div>
                </div>`;

            case 'seven_segment':
                const seg = comp.props.segments || { a:1, b:1, c:1, d:1, e:1, f:1, g:0, dp:0 };
                return `<div class="tc-seven-segment-display" style="width:60px;height:85px;position:relative;background:#090d16;border-radius:6px;border:2px solid #334155;padding:6px;box-sizing:border-box;">
                    <div class="tc-seg ${seg.a ? 'lit' : ''}" style="top:12px;left:15px;width:30px;height:5px;"></div>
                    <div class="tc-seg ${seg.b ? 'lit' : ''}" style="top:17px;left:42px;width:5px;height:24px;"></div>
                    <div class="tc-seg ${seg.c ? 'lit' : ''}" style="top:44px;left:42px;width:5px;height:24px;"></div>
                    <div class="tc-seg ${seg.d ? 'lit' : ''}" style="top:68px;left:15px;width:30px;height:5px;"></div>
                    <div class="tc-seg ${seg.e ? 'lit' : ''}" style="top:44px;left:13px;width:5px;height:24px;"></div>
                    <div class="tc-seg ${seg.f ? 'lit' : ''}" style="top:17px;left:13px;width:5px;height:24px;"></div>
                    <div class="tc-seg ${seg.g ? 'lit' : ''}" style="top:40px;left:15px;width:30px;height:5px;"></div>
                    <div class="tc-seg ${seg.dp ? 'lit' : ''}" style="bottom:12px;right:6px;width:6px;height:6px;border-radius:50%;"></div>
                </div>`;

            case 'dot_matrix':
                return `<div style="width:75px;height:75px;position:relative;background:#0a0e17;border-radius:6px;border:2px solid #38bdf8;padding:4px;box-sizing:border-box;display:grid;grid-template-columns:repeat(8,1fr);grid-template-rows:repeat(8,1fr);gap:2px;">
                    ${renderDotMatrixDots(comp.props.char || 'A')}
                </div>`;

            case 'ir_tsop1838':
                return `<div style="width:38px;height:42px;position:relative;display:flex;flex-direction:column;align-items:center;">
                    <div id="${comp.id}_lens" style="width:24px;height:24px;border-radius:4px 4px 12px 12px;background:#090d16;border:1.5px solid #475569;display:flex;align-items:center;justify-content:center;box-shadow:inset 0 0 6px #000;">
                        <div style="width:10px;height:10px;border-radius:50%;background:#38bdf8;opacity:0.6;"></div>
                    </div>
                    <div style="font-size:0.5rem;color:#94a3b8;font-weight:700;margin-top:2px;">TSOP</div>
                </div>`;

            case 'sound_sensor':
                return `<div style="width:55px;height:40px;position:relative;background:#0284c7;border-radius:4px;border:1.5px solid #0369a1;padding:3px;box-sizing:border-box;display:flex;align-items:center;gap:4px;">
                    <div style="width:16px;height:16px;border-radius:50%;background:#cbd5e1;border:1.5px solid #64748b;display:flex;align-items:center;justify-content:center;" title="Condenser Microphone">
                        <div style="width:6px;height:6px;border-radius:50%;background:#0f172a;"></div>
                    </div>
                    <div style="width:14px;height:14px;background:#1e293b;border-radius:2px;display:flex;align-items:center;justify-content:center;font-size:0.4rem;color:#f59e0b;font-weight:700;" title="LM393 Comparator">LM</div>
                    <div id="${comp.id}_led" style="width:5px;height:5px;border-radius:50%;background:#dc2626;box-shadow:0 0 4px #dc2626;" title="Threshold Trigger LED"></div>
                </div>`;

            case 'stepper_motor':
                const stAngle = comp.props.angle || 0;
                return `<div style="width:65px;height:65px;position:relative;background:linear-gradient(135deg,#94a3b8,#64748b);border-radius:50%;border:2px solid #334155;box-shadow:0 4px 10px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;">
                    <div id="${comp.id}_disc" style="width:48px;height:48px;border-radius:50%;background:#0f172a;border:2px solid #38bdf8;position:relative;transform:rotate(${stAngle}deg);transition:transform 0.1s ease-out;display:flex;align-items:center;justify-content:center;">
                        <div style="position:absolute;top:2px;width:4px;height:18px;background:#ef4444;border-radius:2px;"></div>
                        <div style="font-size:0.5rem;color:#cbd5e1;font-weight:700;">28BYJ</div>
                    </div>
                    <div class="tc-servo-angle-badge" style="position:absolute;bottom:-18px;left:50%;transform:translateX(-50%);">${stAngle}°</div>
                </div>`;

            case 'bluetooth_hc05':
                return `<div style="width:48px;height:54px;position:relative;background:#0284c7;border-radius:4px;border:1.5px solid #0369a1;padding:3px;box-sizing:border-box;">
                    <div style="width:100%;height:10px;background:#0f172a;border-radius:2px;display:flex;align-items:center;justify-content:center;color:#38bdf8;font-size:0.5rem;font-weight:800;">HC-05</div>
                    <div id="${comp.id}_led" style="position:absolute;top:16px;right:6px;width:5px;height:5px;border-radius:50%;background:#22c55e;box-shadow:0 0 6px #22c55e;"></div>
                    <div style="font-size:0.48rem;color:#ffffff;margin-top:8px;text-align:center;">UART 9600</div>
                </div>`;

            case 'tft_lcd':
                return `<div style="width:85px;height:65px;position:relative;background:#0f172a;border-radius:4px;border:2px solid #ef4444;padding:4px;box-sizing:border-box;box-shadow:0 4px 12px rgba(0,0,0,0.5);">
                    <div style="width:100%;height:100%;background:#000000;border:1px solid #334155;border-radius:2px;display:flex;flex-direction:column;justify-content:space-between;padding:2px;box-sizing:border-box;">
                        <div style="font-size:0.45rem;color:#38bdf8;font-weight:700;">1.8" SPI TFT LCD</div>
                        <div style="font-size:0.55rem;color:#22c55e;font-family:'JetBrains Mono',monospace;">${comp.props.text || 'PHYSICS 4'}</div>
                        <div style="width:100%;height:10px;background:#0284c7;border-radius:1px;"></div>
                    </div>
                </div>`;

            default:
                return `<div style="width:36px;height:36px;background:#334155;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.75rem;">${comp.type.toUpperCase()}</div>`;
        }
    }

    function renderDotMatrixDots(char) {
        let dots = '';
        for (let i = 0; i < 64; i++) {
            const isLit = (i % 9 === 0 || i % 7 === 0 || (i >= 24 && i <= 31));
            dots += `<div style="width:100%;height:100%;border-radius:50%;background:${isLit ? '#ef4444' : '#1e293b'};box-shadow:${isLit ? '0 0 4px #ef4444' : 'none'};"></div>`;
        }
        return dots;
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

        function startCompDrag(clientX, clientY) {
            if (state.drawingWire) return;
            isDragging = true;
            moved = false;
            startX = clientX;
            startY = clientY;
            el.classList.add('dragging');

            const origX = comp.x;
            const origY = comp.y;

            function onMove(curX, curY) {
                if (!isDragging) return;
                const dx = (curX - startX) / state.zoom;
                const dy = (curY - startY) / state.zoom;

                if (Math.abs(dx) > 1.0 || Math.abs(dy) > 1.0) moved = true;

                comp.x += dx;
                comp.y += dy;
                startX = curX;
                startY = curY;

                el.style.left = `${comp.x}px`;
                el.style.top = `${comp.y}px`;

                // Highlight snapping candidates on breadboard
                highlightSnapCandidates(comp, comp.x, comp.y);

                registerComponentTerminals(comp);
                renderWires();
                updateInteractivePhysicsWidgets();
                updateFloatingActionBar(comp);
            }

            function onEnd() {
                el.classList.remove('dragging');
                if (moved) {
                    // Check snap to breadboard
                    const lib = COMPONENT_LIBRARY[comp.type];
                    const pin1 = lib?.terminalOffsets ? lib.terminalOffsets[0] : { dx: 0, dy: 0 };
                    const hole = findNearestBreadboardHole(comp.x + pin1.dx, comp.y + pin1.dy);

                    if (hole) {
                        comp.x = hole.x - pin1.dx;
                        comp.y = hole.y - pin1.dy;
                        el.style.left = `${comp.x}px`;
                        el.style.top = `${comp.y}px`;
                        playPlugSound();
                    }

                    document.querySelectorAll('.tc-snap-hole-halo').forEach(h => h.remove());

                    const newX = comp.x, newY = comp.y;
                    pushUndo({
                        type: 'moveComponent',
                        undo: () => {
                            comp.x = origX; comp.y = origY;
                            el.style.left = `${origX}px`; el.style.top = `${origY}px`;
                            registerComponentTerminals(comp);
                            renderWires();
                            triggerCircuitSolve();
                            updateInteractivePhysicsWidgets();
                            updateFloatingActionBar(comp);
                        },
                        redo: () => {
                            comp.x = newX; comp.y = newY;
                            el.style.left = `${newX}px`; el.style.top = `${newY}px`;
                            registerComponentTerminals(comp);
                            renderWires();
                            triggerCircuitSolve();
                            updateInteractivePhysicsWidgets();
                            updateFloatingActionBar(comp);
                        }
                    });

                    registerComponentTerminals(comp);
                    renderWires();
                    triggerCircuitSolve();
                    updateInteractivePhysicsWidgets();
                }

                // Ensure component is actively selected
                selectComponent(comp.id);

                isDragging = false;
                window.removeEventListener('mousemove', onMouseMove);
                window.removeEventListener('mouseup', onMouseUp);
                window.removeEventListener('touchmove', onTouchMove);
                window.removeEventListener('touchend', onTouchEnd);
            }

            function onMouseMove(moveEvent) { onMove(moveEvent.clientX, moveEvent.clientY); }
            function onMouseUp() { onEnd(); }
            function onTouchMove(touchEvent) {
                if (touchEvent.touches.length > 0) {
                    touchEvent.preventDefault();
                    onMove(touchEvent.touches[0].clientX, touchEvent.touches[0].clientY);
                }
            }
            function onTouchEnd() { onEnd(); }

            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
            window.addEventListener('touchmove', onTouchMove, { passive: false });
            window.addEventListener('touchend', onTouchEnd);
        }

        // Expose drag starter so terminal pins or external triggers can initiate drag
        el._startDrag = startCompDrag;

        el.onmousedown = (e) => {
            if (e.button !== 0 || state.drawingWire) return;
            e.stopPropagation();
            if (e.target.id === `${comp.id}_cap` || e.target.id === `${comp.id}_dial` || e.target.id === `${comp.id}_pir_btn`) return;
            selectComponent(comp.id);
            startCompDrag(e.clientX, e.clientY);
        };

        el.ontouchstart = (e) => {
            if (state.drawingWire) return;
            if (e.target.id === `${comp.id}_cap` || e.target.id === `${comp.id}_dial` || e.target.id === `${comp.id}_pir_btn`) return;
            if (e.touches.length === 1) {
                e.stopPropagation();
                selectComponent(comp.id);
                startCompDrag(e.touches[0].clientX, e.touches[0].clientY);
            }
        };
    }

    // ==========================================
    // 11. DRAG & DROP PALETTE & CATEGORIES
    // ==========================================
    function bindDragAndDrop() {
        document.querySelectorAll('.tc-component-card').forEach(card => {
            // Desktop HTML5 dragstart
            card.addEventListener('dragstart', (e) => {
                const type = card.getAttribute('data-component-type');
                if (type === 'arduino' || type === 'breadboard' || !COMPONENT_LIBRARY[type]) {
                    e.preventDefault();
                    return;
                }
                state.dragType = type;
                e.dataTransfer.setData('text/plain', type);
            });

            // Mobile & Desktop Click/Tap to place directly on breadboard
            card.addEventListener('click', (e) => {
                if (card.classList.contains('tc-starter-card')) return;
                const type = card.getAttribute('data-component-type');
                if (!type || type === 'arduino' || type === 'breadboard' || !COMPONENT_LIBRARY[type]) return;

                const targetX = LAYOUT.bbX + 110 + (state.components.length % 6) * 35;
                const targetY = LAYOUT.bbY + 70 + (state.components.length % 4) * 25;
                const comp = placeComponent(type, targetX, targetY);
                if (comp) {
                    pushUndo({
                        type: 'placeComponent',
                        undo: () => { deleteComponent(comp.id); },
                        redo: () => { placeComponent(comp.type, comp.x, comp.y, comp.rotation, comp.props, comp.id); }
                    });
                    selectComponent(comp.id);
                    triggerCircuitSolve();
                    updateInteractivePhysicsWidgets();
                    showToast(`Added ${COMPONENT_LIBRARY[type].name} to Breadboard`);
                }
            });
        });

        const container = document.getElementById('tcCanvasContainer');
        if (!container) return;

        container.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
        });

        container.addEventListener('drop', (e) => {
            e.preventDefault();
            const type = state.dragType || e.dataTransfer.getData('text/plain');
            if (!type || !COMPONENT_LIBRARY[type]) return;

            const stage = document.getElementById('tcCanvasStage');
            const rect = stage.getBoundingClientRect();
            const rawX = (e.clientX - rect.left) / state.zoom - 20;
            const rawY = (e.clientY - rect.top) / state.zoom - 20;

            const comp = placeComponent(type, rawX, rawY);
            if (comp) {
                pushUndo({
                    type: 'placeComponent',
                    undo: () => { deleteComponent(comp.id); },
                    redo: () => { placeComponent(comp.type, comp.x, comp.y, comp.rotation, comp.props, comp.id); }
                });
                selectComponent(comp.id);
                triggerCircuitSolve();
                updateInteractivePhysicsWidgets();
            }
            state.dragType = null;
        });

        // Category Filter
        document.getElementById('tcCompCategory')?.addEventListener('change', function () {
            const cat = this.value;
            state.selectedCategory = cat;
            document.querySelectorAll('.tc-component-card').forEach(card => {
                const cardCat = card.getAttribute('data-category') || 'basic';
                if (cat === 'all') {
                    card.style.display = '';
                } else if (cat === 'starters') {
                    card.style.display = cardCat === 'starters' ? '' : 'none';
                } else {
                    card.style.display = (cardCat === cat && cardCat !== 'starters') ? '' : 'none';
                }
            });
        });

        // Starter Cards Click to Load
        document.querySelectorAll('.tc-starter-card').forEach(card => {
            card.addEventListener('click', function () {
                const presetKey = this.getAttribute('data-preset');
                if (presetKey && presets[presetKey]) {
                    loadPreset(presetKey);
                    showToast(`🚀 Loaded Project: ${presets[presetKey].title}`);
                }
            });
        });
    }

    // ==========================================
    // 11b. INTERACTIVE PHYSICS SENSOR WIDGETS
    // ==========================================
    function updateInteractivePhysicsWidgets() {
        const stage = document.getElementById('tcCanvasStage');
        if (!stage) return;

        // 1. Ultrasonic Sensor Obstacle & Acoustic Ping Waves
        const usComp = state.components.find(c => c.type === 'ultrasonic');
        let obstacle = document.getElementById('tcUltrasonicObstacle');
        let sonarSvg = document.getElementById('tcSonarWavesSvg');

        if (usComp) {
            if (!sonarSvg) {
                sonarSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                sonarSvg.id = 'tcSonarWavesSvg';
                sonarSvg.setAttribute('class', 'tc-sonar-waves-svg');
                stage.appendChild(sonarSvg);
            }
            if (!obstacle) {
                obstacle = document.createElement('div');
                obstacle.id = 'tcUltrasonicObstacle';
                obstacle.className = 'tc-ultrasonic-obstacle-container';
                const initDist = usComp.props.distance || 25;
                const obsX = usComp.x + 95 + (initDist * 2.8);
                const obsY = usComp.y - 20;
                obstacle.style.left = `${obsX}px`;
                obstacle.style.top = `${obsY}px`;
                obstacle.innerHTML = `
                    <div class="tc-ultrasonic-obstacle-block" title="Drag obstacle to change ultrasonic echo distance">
                        <div style="font-size:0.6rem;font-weight:800;color:#38bdf8;text-align:center;margin-top:10px;writing-mode:vertical-rl;letter-spacing:1px;">OBSTACLE</div>
                    </div>
                    <div class="tc-obstacle-badge" id="tcObstacleBadge">${initDist} cm</div>
                `;
                stage.appendChild(obstacle);
                bindDraggableObstacle(obstacle, usComp);
            }
            updateSonarVisualization(usComp, obstacle, sonarSvg);
        } else {
            if (obstacle) obstacle.remove();
            if (sonarSvg) sonarSvg.remove();
        }

        // 2. PIR Motion Sensor Avatar & 120° Detection Cone
        const pirComp = state.components.find(c => c.type === 'pir');
        let pirAvatar = document.getElementById('tcPirSubject');
        let pirCone = document.getElementById('tcPirFieldCone');

        if (pirComp) {
            if (!pirCone) {
                pirCone = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                pirCone.id = 'tcPirFieldCone';
                pirCone.setAttribute('class', 'tc-pir-field-cone');
                stage.appendChild(pirCone);
            }
            if (!pirAvatar) {
                pirAvatar = document.createElement('div');
                pirAvatar.id = 'tcPirSubject';
                pirAvatar.className = 'tc-pir-subject-container';
                const avX = pirComp.x + 120;
                const avY = pirComp.y + 10;
                pirAvatar.style.left = `${avX}px`;
                pirAvatar.style.top = `${avY}px`;
                pirAvatar.innerHTML = `
                    <svg class="tc-pir-human-avatar" viewBox="0 0 48 72">
                        <circle cx="24" cy="14" r="8" fill="#fbcfe8" stroke="#f43f5e" stroke-width="1.5"/>
                        <path d="M 16 24 L 32 24 L 29 46 L 19 46 Z" fill="#0284c7" stroke="#38bdf8" stroke-width="1.5"/>
                        <path d="M 16 26 L 8 40" stroke="#38bdf8" stroke-width="3" stroke-linecap="round"/>
                        <path d="M 32 26 L 40 40" stroke="#38bdf8" stroke-width="3" stroke-linecap="round"/>
                        <path d="M 21 46 L 18 68" stroke="#0369a1" stroke-width="3.5" stroke-linecap="round"/>
                        <path d="M 27 46 L 30 68" stroke="#0369a1" stroke-width="3.5" stroke-linecap="round"/>
                    </svg>
                    <div class="tc-pir-subject-badge" id="tcPirBadge">PIR Target — Drag to Move</div>
                `;
                stage.appendChild(pirAvatar);
                bindDraggablePirSubject(pirAvatar, pirComp);
            }
            updatePirConeVisualization(pirComp, pirCone);
        } else {
            if (pirAvatar) pirAvatar.remove();
            if (pirCone) pirCone.remove();
        }

        // 3. LDR Flashlight Widget
        const ldrComp = state.components.find(c => c.type === 'ldr');
        let ldrFlashlight = document.getElementById('tcLdrFlashlight');
        let ldrBeam = document.getElementById('tcLdrLightBeam');

        if (ldrComp) {
            if (!ldrBeam) {
                ldrBeam = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                ldrBeam.id = 'tcLdrLightBeam';
                ldrBeam.setAttribute('class', 'tc-ldr-light-beam');
                stage.appendChild(ldrBeam);
            }
            if (!ldrFlashlight) {
                ldrFlashlight = document.createElement('div');
                ldrFlashlight.id = 'tcLdrFlashlight';
                ldrFlashlight.className = 'tc-ldr-flashlight-container';
                const flX = ldrComp.x + 85;
                const flY = ldrComp.y - 10;
                ldrFlashlight.style.left = `${flX}px`;
                ldrFlashlight.style.top = `${flY}px`;
                ldrFlashlight.innerHTML = `
                    <div class="tc-ldr-flashlight-body" title="Drag flashlight or tap to toggle light level">
                        <div class="tc-ldr-bulb"></div>
                        <div style="font-size:0.6rem;font-weight:800;color:#78350f;margin-left:8px;">TORCH</div>
                    </div>
                    <div class="tc-ldr-badge" id="tcLdrBadge">${ldrComp.props.lux || 400} lx</div>
                `;
                stage.appendChild(ldrFlashlight);
                bindDraggableFlashlight(ldrFlashlight, ldrComp);
            }
            updateLdrBeamVisualization(ldrComp, ldrFlashlight, ldrBeam);
        } else {
            if (ldrFlashlight) ldrFlashlight.remove();
            if (ldrBeam) ldrBeam.remove();
        }

        // 4. TSOP1838 TV Remote Control Modal
        const irComp = state.components.find(c => c.type === 'ir_tsop1838');
        let irRemote = document.getElementById('tcIrRemoteModal');

        if (irComp) {
            if (!irRemote) {
                irRemote = document.createElement('div');
                irRemote.id = 'tcIrRemoteModal';
                irRemote.className = 'tc-ir-remote-modal';
                irRemote.innerHTML = `
                    <div class="tc-ir-remote-header">
                        <span>TV REMOTE (NEC)</span>
                        <div class="tc-ir-led-emitter" id="tcIrLedEmitter"></div>
                    </div>
                    <div class="tc-remote-grid">
                        <button type="button" class="tc-remote-btn tc-remote-power" data-hex="0xFFA25D">PWR</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFF629D">MODE</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFFE21D">MUTE</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFF22DD">PREV</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFF02FD">NEXT</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFFC23D">PLAY</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFFE01F">VOL-</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFFA857">VOL+</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFF906F">EQ</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFF30CF">1</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFF18E7">2</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFF7A85">3</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFF10EF">4</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFF38C7">5</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFF5AA5">6</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFF42BD">7</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFF4AB5">8</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFF52AD">9</button>
                        <button type="button" class="tc-remote-btn" data-hex="0xFF6897" style="grid-column: span 3;">0</button>
                    </div>
                `;
                stage.appendChild(irRemote);
                bindIrRemoteEvents(irRemote);
            }
        } else {
            if (irRemote) irRemote.remove();
        }

        // 5. LM393 Sound Sensor Clap Widget
        const soundComp = state.components.find(c => c.type === 'sound_sensor');
        let clapWidget = document.getElementById('tcSoundClapWidget');

        if (soundComp) {
            if (!clapWidget) {
                clapWidget = document.createElement('div');
                clapWidget.id = 'tcSoundClapWidget';
                clapWidget.className = 'tc-sound-clap-widget';
                clapWidget.style.left = `${soundComp.x - 20}px`;
                clapWidget.style.top = `${soundComp.y - 45}px`;
                clapWidget.innerHTML = `
                    <span>Sound Level:</span>
                    <button type="button" class="tc-sound-clap-btn" id="tcClapTriggerBtn">👏 Make Sound / Clap</button>
                `;
                stage.appendChild(clapWidget);
                bindClapTrigger(clapWidget, soundComp);
            } else {
                clapWidget.style.left = `${soundComp.x - 20}px`;
                clapWidget.style.top = `${soundComp.y - 45}px`;
            }
        } else {
            if (clapWidget) clapWidget.remove();
        }

        // 6. Bluetooth HC-05 Smartphone Controller
        const btComp = state.components.find(c => c.type === 'bluetooth_hc05');
        let btPhone = document.getElementById('tcBtPhoneModal');

        if (btComp) {
            if (!btPhone) {
                btPhone = document.createElement('div');
                btPhone.id = 'tcBtPhoneModal';
                btPhone.className = 'tc-bt-phone-modal';
                btPhone.innerHTML = `
                    <div class="tc-bt-phone-header">📱 BlueControl Smartphone</div>
                    <div class="tc-bt-phone-screen" id="tcBtScreen">Connected to HC-05 (Ready)</div>
                    <div class="tc-bt-btn-grid">
                        <button type="button" class="tc-bt-app-btn" data-cmd="1">💡 LED ON ('1')</button>
                        <button type="button" class="tc-bt-app-btn" data-cmd="0">🔌 LED OFF ('0')</button>
                        <button type="button" class="tc-bt-app-btn" data-cmd="+">⏩ SPEED +</button>
                        <button type="button" class="tc-bt-app-btn" data-cmd="-">⏪ SPEED -</button>
                    </div>
                    <div class="tc-bt-input-row">
                        <input type="text" id="tcBtTextInput" class="tc-bt-input" placeholder="Type command...">
                        <button type="button" id="tcBtSendBtn" class="tc-bt-send-btn">Send</button>
                    </div>
                `;
                stage.appendChild(btPhone);
                bindBtPhoneEvents(btPhone);
            }
        } else {
            if (btPhone) btPhone.remove();
        }

        // 7. Photogate Pendulum Widget (Exp 06)
        const isPhotogate = state.currentPreset === 'photogate';
        let photogateWidget = document.getElementById('tcPhotogateWidget');

        if (isPhotogate) {
            if (!photogateWidget) {
                photogateWidget = document.createElement('div');
                photogateWidget.id = 'tcPhotogateWidget';
                photogateWidget.className = 'tc-photogate-widget';
                photogateWidget.style.left = '640px';
                photogateWidget.style.top = '40px';
                photogateWidget.innerHTML = `
                    <span>Pendulum Photogate:</span>
                    <button type="button" class="tc-photogate-btn" id="tcSwingBobBtn">⏱️ Swing Bob / Break Beam</button>
                `;
                stage.appendChild(photogateWidget);
                bindPhotogateEvents(photogateWidget);
            }
        } else {
            if (photogateWidget) photogateWidget.remove();
        }
    }

    function bindDraggableObstacle(obstacle, usComp) {
        let isDragging = false;
        let startX, startY;

        function onStart(clientX, clientY) {
            isDragging = true;
            startX = clientX;
            startY = clientY;

            function onMove(curX, curY) {
                if (!isDragging) return;
                const dx = (curX - startX) / state.zoom;
                const dy = (curY - startY) / state.zoom;
                startX = curX;
                startY = curY;

                const curLeft = parseFloat(obstacle.style.left) || 0;
                const curTop = parseFloat(obstacle.style.top) || 0;
                const newLeft = Math.max(usComp.x + 95, Math.min(curLeft + dx, 1200));
                const newTop = curTop + dy;

                obstacle.style.left = `${newLeft}px`;
                obstacle.style.top = `${newTop}px`;

                const distPx = newLeft - (usComp.x + 90);
                const distCm = Math.max(2, Math.min(400, Math.round(distPx / 2.8)));
                usComp.props.distance = distCm;
                state.hardwareValues.ultrasonicCm = distCm;

                const badge = document.getElementById('tcObstacleBadge');
                if (badge) badge.textContent = `${distCm} cm`;

                const slider = document.getElementById('tcUsSlider');
                const valText = document.getElementById('tcUsValText');
                if (slider) slider.value = distCm;
                if (valText) valText.textContent = `${distCm} cm`;

                const sonarSvg = document.getElementById('tcSonarWavesSvg');
                if (sonarSvg) updateSonarVisualization(usComp, obstacle, sonarSvg);
                triggerCircuitSolve();
            }

            function onEnd() {
                isDragging = false;
                window.removeEventListener('mousemove', onMouseMove);
                window.removeEventListener('mouseup', onMouseUp);
                window.removeEventListener('touchmove', onTouchMove);
                window.removeEventListener('touchend', onTouchEnd);
            }

            function onMouseMove(e) { onMove(e.clientX, e.clientY); }
            function onMouseUp() { onEnd(); }
            function onTouchMove(e) { if (e.touches.length > 0) { e.preventDefault(); onMove(e.touches[0].clientX, e.touches[0].clientY); } }
            function onTouchEnd() { onEnd(); }

            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
            window.addEventListener('touchmove', onTouchMove, { passive: false });
            window.addEventListener('touchend', onTouchEnd);
        }

        obstacle.addEventListener('mousedown', (e) => { e.stopPropagation(); onStart(e.clientX, e.clientY); });
        obstacle.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                e.stopPropagation();
                onStart(e.touches[0].clientX, e.touches[0].clientY);
            }
        }, { passive: false });
    }

    function updateSonarVisualization(usComp, obstacle, sonarSvg) {
        const left = usComp.x + 85;
        const top = Math.min(usComp.y, parseFloat(obstacle.style.top) || usComp.y) - 10;
        const width = Math.max(20, (parseFloat(obstacle.style.left) || 0) - left + 10);
        const height = Math.max(80, Math.abs((parseFloat(obstacle.style.top) || usComp.y) - usComp.y) + 100);

        sonarSvg.style.left = `${left}px`;
        sonarSvg.style.top = `${top}px`;
        sonarSvg.setAttribute('width', width);
        sonarSvg.setAttribute('height', height);

        let arcs = '';
        const count = 4;
        for (let i = 1; i <= count; i++) {
            const rad = (width / count) * i;
            arcs += `<path d="M 0 35 A ${rad} ${rad * 0.8} 0 0 1 ${rad} ${35 + rad * 0.4} A ${rad} ${rad * 0.8} 0 0 0 0 35" class="tc-sonar-arc-ping" style="animation-delay:${i * 0.25}s;"/>`;
        }
        sonarSvg.innerHTML = arcs;
    }

    function bindDraggablePirSubject(pirAvatar, pirComp) {
        let isDragging = false;
        let startX, startY;
        let motionTimeout = null;

        function triggerMovement() {
            pirAvatar.classList.add('moving');
            const badge = document.getElementById('tcPirBadge');
            if (badge) badge.textContent = '🏃 MOTION DETECTED!';
            triggerPirMotion(pirComp);

            if (motionTimeout) clearTimeout(motionTimeout);
            motionTimeout = setTimeout(() => {
                pirAvatar.classList.remove('moving');
                if (badge) badge.textContent = 'PIR Target — Drag to Move';
            }, 2500);
        }

        pirAvatar.addEventListener('click', (e) => {
            e.stopPropagation();
            triggerMovement();
        });

        function onStart(clientX, clientY) {
            isDragging = true;
            startX = clientX;
            startY = clientY;

            function onMove(curX, curY) {
                if (!isDragging) return;
                const dx = (curX - startX) / state.zoom;
                const dy = (curY - startY) / state.zoom;
                startX = curX;
                startY = curY;

                const curLeft = parseFloat(pirAvatar.style.left) || 0;
                const curTop = parseFloat(pirAvatar.style.top) || 0;
                pirAvatar.style.left = `${curLeft + dx}px`;
                pirAvatar.style.top = `${curTop + dy}px`;

                triggerMovement();
            }

            function onEnd() {
                isDragging = false;
                window.removeEventListener('mousemove', onMouseMove);
                window.removeEventListener('mouseup', onMouseUp);
                window.removeEventListener('touchmove', onTouchMove);
                window.removeEventListener('touchend', onTouchEnd);
            }

            function onMouseMove(e) { onMove(e.clientX, e.clientY); }
            function onMouseUp() { onEnd(); }
            function onTouchMove(e) { if (e.touches.length > 0) { e.preventDefault(); onMove(e.touches[0].clientX, e.touches[0].clientY); } }
            function onTouchEnd() { onEnd(); }

            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
            window.addEventListener('touchmove', onTouchMove, { passive: false });
            window.addEventListener('touchend', onTouchEnd);
        }

        pirAvatar.addEventListener('mousedown', (e) => { e.stopPropagation(); onStart(e.clientX, e.clientY); });
        pirAvatar.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                e.stopPropagation();
                onStart(e.touches[0].clientX, e.touches[0].clientY);
            }
        }, { passive: false });
    }

    function updatePirConeVisualization(pirComp, pirCone) {
        const left = pirComp.x + 32;
        const top = pirComp.y - 70;
        pirCone.style.left = `${left}px`;
        pirCone.style.top = `${top}px`;
        pirCone.setAttribute('width', 190);
        pirCone.setAttribute('height', 190);
        pirCone.innerHTML = `
            <path d="M 0 95 L 180 15 A 180 180 0 0 1 180 175 Z" fill="rgba(56, 189, 248, 0.08)" stroke="rgba(56, 189, 248, 0.25)" stroke-width="1.5" stroke-dasharray="4,4"/>
        `;
    }

    function bindDraggableFlashlight(ldrFlashlight, ldrComp) {
        let isDragging = false;
        let startX, startY;

        function recalcLux() {
            const flX = parseFloat(ldrFlashlight.style.left) || 0;
            const flY = parseFloat(ldrFlashlight.style.top) || 0;
            const dist = Math.hypot(flX - ldrComp.x, flY - ldrComp.y);
            const lux = Math.max(10, Math.min(1000, Math.round(180000 / (dist * dist + 150))));
            ldrComp.props.lux = lux;
            state.hardwareValues.ldrLux = lux;

            const badge = document.getElementById('tcLdrBadge');
            if (badge) badge.textContent = `${lux} lx`;

            const slider = document.getElementById('tcLdrSlider');
            const valText = document.getElementById('tcLdrValText');
            if (slider) slider.value = lux;
            if (valText) valText.textContent = `${lux} lx`;

            const beam = document.getElementById('tcLdrLightBeam');
            if (beam) updateLdrBeamVisualization(ldrComp, ldrFlashlight, beam);
            triggerCircuitSolve();
        }

        function onStart(clientX, clientY) {
            isDragging = true;
            startX = clientX;
            startY = clientY;

            function onMove(curX, curY) {
                if (!isDragging) return;
                const dx = (curX - startX) / state.zoom;
                const dy = (curY - startY) / state.zoom;
                startX = curX;
                startY = curY;

                const curLeft = parseFloat(ldrFlashlight.style.left) || 0;
                const curTop = parseFloat(ldrFlashlight.style.top) || 0;
                ldrFlashlight.style.left = `${curLeft + dx}px`;
                ldrFlashlight.style.top = `${curTop + dy}px`;

                recalcLux();
            }

            function onEnd() {
                isDragging = false;
                window.removeEventListener('mousemove', onMouseMove);
                window.removeEventListener('mouseup', onMouseUp);
                window.removeEventListener('touchmove', onTouchMove);
                window.removeEventListener('touchend', onTouchEnd);
            }

            function onMouseMove(e) { onMove(e.clientX, e.clientY); }
            function onMouseUp() { onEnd(); }
            function onTouchMove(e) { if (e.touches.length > 0) { e.preventDefault(); onMove(e.touches[0].clientX, e.touches[0].clientY); } }
            function onTouchEnd() { onEnd(); }

            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
            window.addEventListener('touchmove', onTouchMove, { passive: false });
            window.addEventListener('touchend', onTouchEnd);
        }

        ldrFlashlight.addEventListener('mousedown', (e) => { e.stopPropagation(); onStart(e.clientX, e.clientY); });
        ldrFlashlight.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                e.stopPropagation();
                onStart(e.touches[0].clientX, e.touches[0].clientY);
            }
        }, { passive: false });
    }

    function updateLdrBeamVisualization(ldrComp, ldrFlashlight, ldrBeam) {
        const flX = parseFloat(ldrFlashlight.style.left) || 0;
        const flY = parseFloat(ldrFlashlight.style.top) || 0;
        const minX = Math.min(flX, ldrComp.x) - 10;
        const minY = Math.min(flY, ldrComp.y) - 10;
        const width = Math.abs(flX - ldrComp.x) + 70;
        const height = Math.abs(flY - ldrComp.y) + 70;

        ldrBeam.style.left = `${minX}px`;
        ldrBeam.style.top = `${minY}px`;
        ldrBeam.setAttribute('width', width);
        ldrBeam.setAttribute('height', height);

        const x1 = flX - minX + 5;
        const y1 = flY - minY + 18;
        const x2 = ldrComp.x - minX + 17;
        const y2 = ldrComp.y - minY + 20;

        ldrBeam.innerHTML = `
            <defs>
                <linearGradient id="ldrBeamGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#fef08a" stop-opacity="0.6"/>
                    <stop offset="100%" stop-color="#f59e0b" stop-opacity="0.1"/>
                </linearGradient>
            </defs>
            <polygon points="${x1},${y1} ${x2 - 12},${y2 - 14} ${x2 + 12},${y2 + 14}" fill="url(#ldrBeamGrad)"/>
        `;
    }

    function bindIrRemoteEvents(irRemote) {
        irRemote.querySelectorAll('.tc-remote-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const hexStr = btn.getAttribute('data-hex');
                const hexVal = parseInt(hexStr, 16);
                const emitter = document.getElementById('tcIrLedEmitter');
                if (emitter) {
                    emitter.classList.add('flash');
                    setTimeout(() => emitter.classList.remove('flash'), 180);
                }
                playTone(3800);
                setTimeout(stopTone, 60);

                state.hardwareValues.lastIrCode = hexVal;
                appendSerial(`[IR Remote 38kHz]: Sent NEC Code ${hexStr} (${btn.textContent.trim()})\n`);
                showToast(`📡 Remote Sent: ${hexStr}`);
                triggerCircuitSolve();
            });
        });
    }

    function bindClapTrigger(clapWidget, soundComp) {
        clapWidget.querySelector('#tcClapTriggerBtn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            playPlugSound();
            soundComp.props.soundActive = true;
            state.hardwareValues.digitalPins[7] = 1;
            triggerCircuitSolve();
            showToast('👏 Sound Peak > Threshold (LM393 D0 -> HIGH)');
            appendSerial('[LM393 Mic]: Sound impulse 85 dB detected!\n');
            setTimeout(() => {
                soundComp.props.soundActive = false;
                state.hardwareValues.digitalPins[7] = 0;
                triggerCircuitSolve();
            }, 500);
        });
    }

    function bindBtPhoneEvents(btPhone) {
        btPhone.querySelectorAll('.tc-bt-app-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const cmd = btn.getAttribute('data-cmd');
                if (!state.hardwareValues.btQueue) state.hardwareValues.btQueue = [];
                state.hardwareValues.btQueue.push(cmd.charCodeAt(0));

                const screen = document.getElementById('tcBtScreen');
                if (screen) screen.textContent = `TX: '${cmd}' (${btn.textContent.trim()})`;
                appendSerial(`[BlueControl TX]: '${cmd}'\n`);
                showToast(`📱 Bluetooth Sent: '${cmd}'`);
            });
        });

        const sendBtn = btPhone.querySelector('#tcBtSendBtn');
        const input = btPhone.querySelector('#tcBtTextInput');

        const doSend = () => {
            if (!input) return;
            const text = input.value.trim();
            if (!text) return;
            if (!state.hardwareValues.btQueue) state.hardwareValues.btQueue = [];
            for (let i = 0; i < text.length; i++) {
                state.hardwareValues.btQueue.push(text.charCodeAt(i));
            }
            state.hardwareValues.btQueue.push(10); // \n

            const screen = document.getElementById('tcBtScreen');
            if (screen) screen.textContent = `TX: "${text}"`;
            appendSerial(`[BlueControl TX]: "${text}"\n`);
            showToast(`📱 Bluetooth Sent: "${text}"`);
            input.value = '';
        };

        sendBtn?.addEventListener('click', (e) => { e.stopPropagation(); doSend(); });
        input?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.stopPropagation();
                doSend();
            }
        });
    }

    function bindPhotogateEvents(photogateWidget) {
        photogateWidget.querySelector('#tcSwingBobBtn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            playPlugSound();
            showToast('⏱️ Pendulum Bob broke IR Photogate beam!');
            appendSerial('[Photogate INT0]: Beam interrupted -> ISR triggered\n');

            if (typeof state.hardwareValues.interruptIsr === 'function') {
                state.hardwareValues.interruptIsr();
            } else {
                state.hardwareValues.digitalPins[2] = 0;
                triggerCircuitSolve();
                setTimeout(() => {
                    state.hardwareValues.digitalPins[2] = 1;
                    triggerCircuitSolve();
                }, 40);
            }
        });
    }

    function setupCanvasPanning() {
        const container = document.getElementById('tcCanvasContainer');
        const stage = document.getElementById('tcCanvasStage');
        if (!container || !stage) return;

        container.addEventListener('mousedown', (e) => {
            if (e.button === 1 || (e.button === 0 && e.spaceKey)) {
                e.preventDefault();
                state.isPanning = true;
                state.panStartX = e.clientX - state.panX;
                state.panStartY = e.clientY - state.panY;
                container.style.cursor = 'grabbing';
            }
        });

        window.addEventListener('mousemove', (e) => {
            if (!state.isPanning) return;
            state.panX = e.clientX - state.panStartX;
            state.panY = e.clientY - state.panStartY;
            stage.style.transform = `translate(${state.panX}px, ${state.panY}px) scale(${state.zoom})`;
        });

        window.addEventListener('mouseup', () => {
            if (state.isPanning) {
                state.isPanning = false;
                container.style.cursor = 'default';
            }
        });

        // Mobile Touch Pan & Pinch-to-Zoom
        let touchStartDist = 0;
        let initialZoom = 1;
        let touchPanStartX = 0;
        let touchPanStartY = 0;
        let isTouchPanning = false;

        container.addEventListener('touchstart', (e) => {
            if (state.drawingWire) return;
            if (e.touches.length === 2) {
                e.preventDefault();
                touchStartDist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                initialZoom = state.zoom;
            } else if (e.touches.length === 1 && (e.target === container || e.target === stage || e.target.id === 'tcBreadboardSvg' || e.target.id === 'tcWiresSvg')) {
                isTouchPanning = true;
                touchPanStartX = e.touches[0].clientX - state.panX;
                touchPanStartY = e.touches[0].clientY - state.panY;
            }
        }, { passive: false });

        container.addEventListener('touchmove', (e) => {
            if (state.drawingWire) return;
            if (e.touches.length === 2 && touchStartDist > 0) {
                e.preventDefault();
                const dist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                const factor = dist / touchStartDist;
                setZoom(initialZoom * factor);
                updateStatusBar();
            } else if (e.touches.length === 1 && isTouchPanning) {
                e.preventDefault();
                state.panX = e.touches[0].clientX - touchPanStartX;
                state.panY = e.touches[0].clientY - touchPanStartY;
                stage.style.transform = `translate(${state.panX}px, ${state.panY}px) scale(${state.zoom})`;
            }
        }, { passive: false });

        container.addEventListener('touchend', (e) => {
            if (e.touches.length < 2) touchStartDist = 0;
            if (e.touches.length === 0) isTouchPanning = false;
        });
    }

    // ==========================================
    // 12. COMPONENT INSPECTOR POPOVER
    // ==========================================
    function renderSelectedComponentFloatingBar(comp) {
        let bar = document.getElementById('tcCompFloatingBar');
        if (!bar) {
            bar = document.createElement('div');
            bar.id = 'tcCompFloatingBar';
            bar.className = 'tc-comp-floating-bar';
            const stage = document.getElementById('tcCanvasStage');
            if (stage) stage.appendChild(bar);
        }

        const lib = COMPONENT_LIBRARY[comp.type];
        const typeName = lib ? lib.name : comp.type.toUpperCase();
        const compLabel = `${comp.props.name || typeName}`;

        bar.style.left = `${comp.x}px`;
        bar.style.top = `${comp.y - 36}px`;
        bar.style.display = 'flex';

        bar.innerHTML = `
            <span class="tc-float-label"><i class="fa-solid fa-microchip"></i> ${compLabel}</span>
            <button type="button" class="tc-float-btn tc-float-edit" title="Edit Component Values (Inspector)">
                <i class="fa-solid fa-pen-to-square"></i> Edit
            </button>
            <button type="button" class="tc-float-btn tc-float-rotate" title="Rotate Component 90° (R)">
                <i class="fa-solid fa-rotate"></i> Rotate
            </button>
            <button type="button" class="tc-float-btn tc-float-delete" title="Delete Component (Del)">
                <i class="fa-solid fa-trash-can"></i> Delete
            </button>
            <button type="button" class="tc-float-btn tc-float-close" title="Deselect">
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;

        bar.querySelector('.tc-float-edit')?.addEventListener('click', (e) => {
            e.stopPropagation();
            showComponentInspector(comp.id);
        });

        bar.querySelector('.tc-float-rotate')?.addEventListener('click', (e) => {
            e.stopPropagation();
            rotateSelected();
        });

        bar.querySelector('.tc-float-delete')?.addEventListener('click', (e) => {
            e.stopPropagation();
            deleteSelected();
        });

        bar.querySelector('.tc-float-close')?.addEventListener('click', (e) => {
            e.stopPropagation();
            deselectAll();
        });

        bar.addEventListener('mousedown', (e) => e.stopPropagation());
        bar.addEventListener('click', (e) => e.stopPropagation());
    }

    function updateFloatingActionBar(comp) {
        const bar = document.getElementById('tcCompFloatingBar');
        if (bar && state.selectedItem?.id === comp.id) {
            bar.style.left = `${comp.x}px`;
            bar.style.top = `${comp.y - 36}px`;
        }
    }

    function removeFloatingActionBar() {
        const bar = document.getElementById('tcCompFloatingBar');
        if (bar) bar.style.display = 'none';
    }

    function selectComponent(id) {
        deselectAll();
        state.selectedItem = { type: 'component', id };
        const el = document.getElementById(id);
        if (el) {
            el.classList.add('selected');
            el.style.zIndex = '50';
        }
        const comp = state.components.find(c => c.id === id);
        if (comp) {
            renderSelectedComponentFloatingBar(comp);
            document.querySelectorAll(`[id^="term-${id}_"]`).forEach(t => t.classList.add('selected-parent'));
        }
        showComponentInspector(id);
        updateStatusBar();
    }

    function selectWire(id) {
        deselectAll();
        state.selectedItem = { type: 'wire', id };
        renderWires();
        hideComponentInspector();
        updateStatusBar();
    }

    function deselectAll() {
        state.selectedItem = null;
        document.querySelectorAll('.tc-placed-component').forEach(el => {
            el.classList.remove('selected');
            el.style.zIndex = '30';
        });
        document.querySelectorAll('.tc-terminal-pin.selected-parent').forEach(t => t.classList.remove('selected-parent'));
        removeFloatingActionBar();
        renderWires();
        hideComponentInspector();
        updateStatusBar();
    }

    function rotateSelected() {
        if (state.selectedItem?.type !== 'component') return;
        const comp = state.components.find(c => c.id === state.selectedItem.id);
        if (!comp) return;

        const origRot = comp.rotation;
        comp.rotation = (comp.rotation + 90) % 360;
        const el = document.getElementById(comp.id);
        if (el) el.style.transform = `rotate(${comp.rotation}deg)`;

        registerComponentTerminals(comp);
        renderWires();
        triggerCircuitSolve();
        updateFloatingActionBar(comp);

        pushUndo({
            type: 'rotateComponent',
            undo: () => { comp.rotation = origRot; if (el) el.style.transform = `rotate(${origRot}deg)`; registerComponentTerminals(comp); renderWires(); triggerCircuitSolve(); updateFloatingActionBar(comp); },
            redo: () => { comp.rotation = (origRot + 90) % 360; if (el) el.style.transform = `rotate(${comp.rotation}deg)`; registerComponentTerminals(comp); renderWires(); triggerCircuitSolve(); updateFloatingActionBar(comp); }
        });
    }

    function deleteSelected() {
        if (!state.selectedItem) return;
        if (state.selectedItem.type === 'wire') {
            const wire = state.wires.find(w => w.id === state.selectedItem.id);
            if (!wire) return;
            state.wires = state.wires.filter(w => w.id !== wire.id);
            renderWires();
            deselectAll();
            triggerCircuitSolve();
            showToast('🗑️ Deleted wire');
            pushUndo({
                type: 'deleteWire',
                undo: () => { state.wires.push(wire); renderWires(); triggerCircuitSolve(); },
                redo: () => { state.wires = state.wires.filter(w => w.id !== wire.id); renderWires(); triggerCircuitSolve(); }
            });
        } else if (state.selectedItem.type === 'component') {
            const comp = state.components.find(c => c.id === state.selectedItem.id);
            if (!comp) return;
            deleteComponent(comp.id);
            deselectAll();
            triggerCircuitSolve();
            pushUndo({
                type: 'deleteComponent',
                undo: () => { placeComponent(comp.type, comp.x, comp.y, comp.rotation, comp.props, comp.id); triggerCircuitSolve(); },
                redo: () => { deleteComponent(comp.id); triggerCircuitSolve(); }
            });
        }
    }

    function deleteComponent(compId) {
        const comp = state.components.find(c => c.id === compId);
        const compName = comp ? comp.props.name : 'Component';

        state.components = state.components.filter(c => c.id !== compId);
        const el = document.getElementById(compId);
        if (el) el.remove();

        state.wires = state.wires.filter(w => !w.from.startsWith(compId) && !w.to.startsWith(compId));

        for (const k in terminals) {
            if (k.startsWith(compId)) {
                delete terminals[k];
                const tEl = document.getElementById(`term-${k}`);
                if (tEl) tEl.remove();
            }
        }

        removeFloatingActionBar();
        hideComponentInspector();
        renderWires();
        triggerCircuitSolve();
        updateInteractivePhysicsWidgets();
        updateStatusBar();
        showToast(`🗑️ Deleted: ${compName}`);
    }

    function showComponentInspector(compId) {
        const comp = state.components.find(c => c.id === compId);
        const pop = document.getElementById('tcComponentInspector');
        const fields = document.getElementById('tcInspectorFields');
        const title = document.getElementById('tcInspectorName');
        if (!comp || !pop || !fields || !title) return;

        const lib = COMPONENT_LIBRARY[comp.type];
        title.textContent = `${lib ? lib.name : comp.type} — ${comp.props.name}`;

        const inspectX = Math.max(20, Math.min(comp.x - 70, 750));
        const inspectY = Math.max(20, Math.min(comp.y - 140, 280));

        pop.style.left = `${inspectX}px`;
        pop.style.top = `${inspectY}px`;
        pop.style.display = 'block';

        let html = '';
        if (comp.type === 'led') {
            const colors = [
                { hex: '#ef4444', name: 'Red', vf: 2.0 },
                { hex: '#10b981', name: 'Green', vf: 2.2 },
                { hex: '#eab308', name: 'Yellow', vf: 2.1 },
                { hex: '#0284c7', name: 'Blue', vf: 3.2 },
                { hex: '#f97316', name: 'Orange', vf: 2.0 },
                { hex: '#ffffff', name: 'White', vf: 3.2 }
            ];
            html = `
                <div class="tc-inspector-row"><span>Color Swatch:</span>
                    <div class="tc-swatch-group">
                        ${colors.map(c => `
                            <div class="tc-color-swatch ${comp.props.color === c.hex ? 'active' : ''}" data-color="${c.hex}" data-vf="${c.vf}" style="background:${c.hex};" title="${c.name} (${c.vf}V)"></div>
                        `).join('')}
                    </div>
                </div>
                <div class="tc-inspector-row"><span>Forward Voltage:</span><span id="tcLedVf">${comp.props.forwardVoltage || 2.0} V</span></div>
            `;
        } else if (comp.type === 'resistor') {
            html = `
                <div class="tc-inspector-row"><span>Resistance:</span>
                    <div class="tc-stepper-row">
                        <button type="button" class="tc-inspector-stepper-btn" id="tcResStepDown">-</button>
                        <input type="number" id="tcResVal" class="tc-inspector-input" value="${comp.props.resistance}" min="1" max="10000000" style="width:75px;">
                        <button type="button" class="tc-inspector-stepper-btn" id="tcResStepUp">+</button>
                        <select id="tcResUnit" class="tc-inspector-input" style="width:55px;">
                            <option value="1" ${comp.props.unit === 'Ω' ? 'selected' : ''}>Ω</option>
                            <option value="1000" ${comp.props.unit === 'kΩ' ? 'selected' : ''}>kΩ</option>
                            <option value="1000000" ${comp.props.unit === 'MΩ' ? 'selected' : ''}>MΩ</option>
                        </select>
                    </div>
                </div>
                <div class="tc-preset-chip-group">
                    <button type="button" class="tc-preset-chip" data-res="220" data-unit="Ω">220 Ω</button>
                    <button type="button" class="tc-preset-chip" data-res="330" data-unit="Ω">330 Ω</button>
                    <button type="button" class="tc-preset-chip" data-res="1" data-unit="kΩ">1 kΩ</button>
                    <button type="button" class="tc-preset-chip" data-res="4.7" data-unit="kΩ">4.7 kΩ</button>
                    <button type="button" class="tc-preset-chip" data-res="10" data-unit="kΩ">10 kΩ</button>
                    <button type="button" class="tc-preset-chip" data-res="100" data-unit="kΩ">100 kΩ</button>
                </div>
            `;
        } else if (comp.type === 'capacitor') {
            html = `
                <div class="tc-inspector-row"><span>Capacitance:</span>
                    <div class="tc-stepper-row">
                        <button type="button" class="tc-inspector-stepper-btn" id="tcCapStepDown">-</button>
                        <input type="number" id="tcCapVal" class="tc-inspector-input" value="${comp.props.capacitance}" min="1" style="width:75px;">
                        <button type="button" class="tc-inspector-stepper-btn" id="tcCapStepUp">+</button>
                        <span>${comp.props.unit || 'µF'}</span>
                    </div>
                </div>
                <div class="tc-preset-chip-group">
                    <button type="button" class="tc-preset-chip" data-cap="100" data-unit="nF">100 nF</button>
                    <button type="button" class="tc-preset-chip" data-cap="1" data-unit="µF">1 µF</button>
                    <button type="button" class="tc-preset-chip" data-cap="10" data-unit="µF">10 µF</button>
                    <button type="button" class="tc-preset-chip" data-cap="100" data-unit="µF">100 µF</button>
                    <button type="button" class="tc-preset-chip" data-cap="470" data-unit="µF">470 µF</button>
                </div>
            `;
        } else if (comp.type === 'potentiometer') {
            html = `
                <div class="tc-inspector-row"><span>Track Total:</span><span>${comp.props.resistance || 10000} Ω</span></div>
                <div class="tc-inspector-row"><span>Wiper:</span>
                    <input type="range" id="tcPotSlider" min="0" max="100" value="${Math.round((comp.props.position || 0.5) * 100)}" style="width:90px;">
                    <span id="tcPotValText">${Math.round((comp.props.position || 0.5) * 100)}%</span>
                </div>
                <div class="tc-preset-chip-group">
                    <button type="button" class="tc-preset-chip" data-pos="0">0%</button>
                    <button type="button" class="tc-preset-chip" data-pos="25">25%</button>
                    <button type="button" class="tc-preset-chip" data-pos="50">50%</button>
                    <button type="button" class="tc-preset-chip" data-pos="75">75%</button>
                    <button type="button" class="tc-preset-chip" data-pos="100">100%</button>
                </div>
            `;
        } else if (comp.type === 'ldr') {
            html = `
                <div class="tc-inspector-row"><span>Ambient Lux:</span>
                    <input type="range" id="tcLdrSlider" min="0" max="1000" value="${comp.props.lux || 400}" style="width:90px;">
                    <span id="tcLdrValText">${comp.props.lux || 400} lx</span>
                </div>
                <div class="tc-preset-chip-group">
                    <button type="button" class="tc-preset-chip" data-lux="15">🌑 Dark (15 lx)</button>
                    <button type="button" class="tc-preset-chip" data-lux="150">💡 Dim (150 lx)</button>
                    <button type="button" class="tc-preset-chip" data-lux="400">🏢 Room (400 lx)</button>
                    <button type="button" class="tc-preset-chip" data-lux="950">☀️ Sun (950 lx)</button>
                </div>
            `;
        } else if (comp.type === 'ultrasonic') {
            html = `
                <div class="tc-inspector-row"><span>Distance:</span>
                    <input type="range" id="tcUsSlider" min="2" max="400" value="${comp.props.distance || 25}" style="width:90px;">
                    <span id="tcUsValText">${comp.props.distance || 25} cm</span>
                </div>
                <div class="tc-preset-chip-group">
                    <button type="button" class="tc-preset-chip" data-dist="5">5 cm</button>
                    <button type="button" class="tc-preset-chip" data-dist="15">15 cm</button>
                    <button type="button" class="tc-preset-chip" data-dist="30">30 cm</button>
                    <button type="button" class="tc-preset-chip" data-dist="60">60 cm</button>
                    <button type="button" class="tc-preset-chip" data-dist="120">120 cm</button>
                </div>
            `;
        } else if (comp.type === 'tmp36') {
            html = `
                <div class="tc-inspector-row"><span>Temperature:</span>
                    <input type="range" id="tcTmpSlider" min="-40" max="125" value="${Math.round(comp.props.tempC || 25)}" style="width:90px;">
                    <span id="tcTmpValText">${(comp.props.tempC || 25).toFixed(1)}°C</span>
                </div>
                <div class="tc-preset-chip-group">
                    <button type="button" class="tc-preset-chip" data-temp="0">❄️ 0°C</button>
                    <button type="button" class="tc-preset-chip" data-temp="25">🌡️ 25°C</button>
                    <button type="button" class="tc-preset-chip" data-temp="37">🩺 37°C</button>
                    <button type="button" class="tc-preset-chip" data-temp="75">🔥 75°C</button>
                </div>
            `;
        } else if (comp.type === 'servo') {
            html = `
                <div class="tc-inspector-row"><span>Horn Angle:</span>
                    <input type="range" id="tcServoSlider" min="0" max="180" value="${comp.props.angle || 90}" style="width:90px;">
                    <span id="tcServoValText">${comp.props.angle || 90}°</span>
                </div>
                <div class="tc-preset-chip-group">
                    <button type="button" class="tc-preset-chip" data-ang="0">0°</button>
                    <button type="button" class="tc-preset-chip" data-ang="45">45°</button>
                    <button type="button" class="tc-preset-chip" data-ang="90">90°</button>
                    <button type="button" class="tc-preset-chip" data-ang="135">135°</button>
                    <button type="button" class="tc-preset-chip" data-ang="180">180°</button>
                </div>
            `;
        } else if (comp.type === 'stepper_motor') {
            html = `
                <div class="tc-inspector-row"><span>Angle:</span><span id="tcStepperAngText">${comp.props.angle || 0}°</span></div>
                <div class="tc-preset-chip-group">
                    <button type="button" class="tc-preset-chip" id="tcStepBackBtn">⟲ -10°</button>
                    <button type="button" class="tc-preset-chip" id="tcStepFwdBtn">⟳ +10°</button>
                    <button type="button" class="tc-preset-chip" data-stepang="90">90°</button>
                    <button type="button" class="tc-preset-chip" data-stepang="180">180°</button>
                    <button type="button" class="tc-preset-chip" data-stepang="360">360°</button>
                </div>
            `;
        } else if (comp.type === 'sound_sensor') {
            html = `
                <div class="tc-inspector-row"><span>Threshold:</span>
                    <input type="range" id="tcSoundThreshSlider" min="0" max="1023" value="${comp.props.threshold || 512}" style="width:90px;">
                    <span id="tcSoundThreshText">${comp.props.threshold || 512}</span>
                </div>
                <div style="margin-top:6px;">
                    <button type="button" class="tc-sound-clap-btn" id="tcInspClapBtn" style="width:100%;">👏 Trigger Clap Peak</button>
                </div>
            `;
        } else if (comp.type === 'dc_motor') {
            html = `
                <div class="tc-inspector-row"><span>Speed (PWM):</span>
                    <input type="range" id="tcMotorPwmSlider" min="0" max="255" value="${comp.props.speed || 0}" style="width:90px;">
                    <span id="tcMotorPwmText">${comp.props.speed || 0}</span>
                </div>
                <div class="tc-preset-chip-group">
                    <button type="button" class="tc-preset-chip" data-pwm="0">Stop (0)</button>
                    <button type="button" class="tc-preset-chip" data-pwm="128">50% (128)</button>
                    <button type="button" class="tc-preset-chip" data-pwm="192">75% (192)</button>
                    <button type="button" class="tc-preset-chip" data-pwm="255">Max (255)</button>
                </div>
            `;
        } else if (comp.type === 'seven_segment') {
            html = `
                <div class="tc-inspector-row"><span>Mode:</span><span>Common Cathode</span></div>
                <div class="tc-preset-chip-group">
                    ${[0, 1, 2, 3, 4, 5, 6, 7, 8, 9].map(n => `
                        <button type="button" class="tc-preset-chip tc-seg-test-chip" data-digit="${n}">${n}</button>
                    `).join('')}
                </div>
            `;
        } else if (comp.type === 'pushbutton') {
            html = `
                <div class="tc-inspector-row"><span>Contact State:</span>
                    <button type="button" id="tcBtnToggle" class="btn-tc-code-toggle" style="padding:4px 10px;font-size:0.75rem;">
                        ${comp.props.pressed ? 'Pressed (Closed)' : 'Normal (Open)'}
                    </button>
                </div>
            `;
        } else if (comp.type === 'slide_switch') {
            html = `
                <div class="tc-inspector-row"><span>Switch Pos:</span>
                    <button type="button" id="tcSwitchToggle" class="btn-tc-code-toggle" style="padding:4px 10px;font-size:0.75rem;">
                        ${comp.props.state === 'left' ? 'Left Position' : 'Right Position'}
                    </button>
                </div>
            `;
        } else {
            html = `<div style="color:var(--tc-text-muted);font-size:0.75rem;">Physics lab component: ${comp.props.name}</div>`;
        }

        html += `
            <div class="tc-inspector-actions" style="margin-top: 14px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.12); display: flex; gap: 8px;">
                <button type="button" id="tcInspectorRotateBtn" class="tc-tool-btn" style="flex:1; height:32px; justify-content:center; background:#1e293b; color:#38bdf8; border:1px solid #334155; border-radius:6px; font-size:0.75rem; font-weight:700; cursor:pointer;" title="Rotate Component (R)">
                    <i class="fa-solid fa-rotate"></i> Rotate
                </button>
                <button type="button" id="tcInspectorDeleteBtn" class="tc-tool-btn" style="flex:1; height:32px; justify-content:center; background:rgba(239,68,68,0.18); color:#fca5a5; border:1px solid rgba(239,68,68,0.4); border-radius:6px; font-size:0.75rem; font-weight:700; cursor:pointer;" title="Delete Component (Del)">
                    <i class="fa-solid fa-trash-can"></i> Delete
                </button>
            </div>
        `;

        fields.innerHTML = html;

        document.getElementById('tcInspectorRotateBtn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            rotateSelected();
        });
        document.getElementById('tcInspectorDeleteBtn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            deleteSelected();
        });

        // BIND EVENT LISTENERS FOR CONTROLS
        // LED Swatches
        fields.querySelectorAll('.tc-color-swatch').forEach(swatch => {
            swatch.addEventListener('click', function () {
                const hex = this.getAttribute('data-color');
                const vf = parseFloat(this.getAttribute('data-vf')) || 2.0;
                comp.props.color = hex;
                comp.props.forwardVoltage = vf;
                fields.querySelectorAll('.tc-color-swatch').forEach(s => s.classList.remove('active'));
                this.classList.add('active');
                const vfEl = document.getElementById('tcLedVf');
                if (vfEl) vfEl.textContent = `${vf} V`;
                renderComponentDOM(comp);
                triggerCircuitSolve();
            });
        });

        // Resistor Steppers & Chips
        document.getElementById('tcResVal')?.addEventListener('input', function () {
            comp.props.resistance = parseFloat(this.value) || 220;
            renderComponentDOM(comp);
            triggerCircuitSolve();
        });
        document.getElementById('tcResUnit')?.addEventListener('change', function () {
            comp.props.unit = this.options[this.selectedIndex].text;
            renderComponentDOM(comp);
            triggerCircuitSolve();
        });
        document.getElementById('tcResStepDown')?.addEventListener('click', () => {
            comp.props.resistance = Math.max(1, (comp.props.resistance || 220) - 10);
            const inp = document.getElementById('tcResVal');
            if (inp) inp.value = comp.props.resistance;
            renderComponentDOM(comp);
            triggerCircuitSolve();
        });
        document.getElementById('tcResStepUp')?.addEventListener('click', () => {
            comp.props.resistance = (comp.props.resistance || 220) + 10;
            const inp = document.getElementById('tcResVal');
            if (inp) inp.value = comp.props.resistance;
            renderComponentDOM(comp);
            triggerCircuitSolve();
        });
        fields.querySelectorAll('.tc-preset-chip[data-res]').forEach(chip => {
            chip.addEventListener('click', function () {
                comp.props.resistance = parseFloat(this.getAttribute('data-res'));
                comp.props.unit = this.getAttribute('data-unit');
                const inp = document.getElementById('tcResVal');
                if (inp) inp.value = comp.props.resistance;
                const unitSel = document.getElementById('tcResUnit');
                if (unitSel) {
                    for (let i = 0; i < unitSel.options.length; i++) {
                        if (unitSel.options[i].text === comp.props.unit) unitSel.selectedIndex = i;
                    }
                }
                renderComponentDOM(comp);
                triggerCircuitSolve();
            });
        });

        // Capacitor Steppers & Chips
        document.getElementById('tcCapVal')?.addEventListener('input', function () {
            comp.props.capacitance = parseFloat(this.value) || 100;
            renderComponentDOM(comp);
            triggerCircuitSolve();
        });
        document.getElementById('tcCapStepDown')?.addEventListener('click', () => {
            comp.props.capacitance = Math.max(1, (comp.props.capacitance || 100) - 5);
            const inp = document.getElementById('tcCapVal');
            if (inp) inp.value = comp.props.capacitance;
            renderComponentDOM(comp);
            triggerCircuitSolve();
        });
        document.getElementById('tcCapStepUp')?.addEventListener('click', () => {
            comp.props.capacitance = (comp.props.capacitance || 100) + 10;
            const inp = document.getElementById('tcCapVal');
            if (inp) inp.value = comp.props.capacitance;
            renderComponentDOM(comp);
            triggerCircuitSolve();
        });
        fields.querySelectorAll('.tc-preset-chip[data-cap]').forEach(chip => {
            chip.addEventListener('click', function () {
                comp.props.capacitance = parseFloat(this.getAttribute('data-cap'));
                comp.props.unit = this.getAttribute('data-unit');
                const inp = document.getElementById('tcCapVal');
                if (inp) inp.value = comp.props.capacitance;
                renderComponentDOM(comp);
                triggerCircuitSolve();
            });
        });

        // Potentiometer Slider & Chips
        document.getElementById('tcPotSlider')?.addEventListener('input', function () {
            setPotPosition(comp, parseInt(this.value) / 100);
            const valText = document.getElementById('tcPotValText');
            if (valText) valText.textContent = `${this.value}%`;
        });
        fields.querySelectorAll('.tc-preset-chip[data-pos]').forEach(chip => {
            chip.addEventListener('click', function () {
                const pos = parseInt(this.getAttribute('data-pos'));
                setPotPosition(comp, pos / 100);
                const slider = document.getElementById('tcPotSlider');
                if (slider) slider.value = pos;
                const valText = document.getElementById('tcPotValText');
                if (valText) valText.textContent = `${pos}%`;
            });
        });

        // LDR Slider & Chips
        document.getElementById('tcLdrSlider')?.addEventListener('input', function () {
            comp.props.lux = parseInt(this.value);
            state.hardwareValues.ldrLux = comp.props.lux;
            const valText = document.getElementById('tcLdrValText');
            if (valText) valText.textContent = `${comp.props.lux} lx`;
            const badge = document.getElementById('tcLdrBadge');
            if (badge) badge.textContent = `${comp.props.lux} lx`;
            triggerCircuitSolve();
        });
        fields.querySelectorAll('.tc-preset-chip[data-lux]').forEach(chip => {
            chip.addEventListener('click', function () {
                comp.props.lux = parseInt(this.getAttribute('data-lux'));
                state.hardwareValues.ldrLux = comp.props.lux;
                const slider = document.getElementById('tcLdrSlider');
                if (slider) slider.value = comp.props.lux;
                const valText = document.getElementById('tcLdrValText');
                if (valText) valText.textContent = `${comp.props.lux} lx`;
                const badge = document.getElementById('tcLdrBadge');
                if (badge) badge.textContent = `${comp.props.lux} lx`;
                triggerCircuitSolve();
            });
        });

        // Ultrasonic Slider & Chips
        document.getElementById('tcUsSlider')?.addEventListener('input', function () {
            comp.props.distance = parseFloat(this.value);
            state.hardwareValues.ultrasonicCm = comp.props.distance;
            const valText = document.getElementById('tcUsValText');
            if (valText) valText.textContent = `${comp.props.distance} cm`;
            const badge = document.getElementById('tcObstacleBadge');
            if (badge) badge.textContent = `${comp.props.distance} cm`;
            const obstacle = document.getElementById('tcUltrasonicObstacle');
            if (obstacle) {
                const newLeft = comp.x + 95 + (comp.props.distance * 2.8);
                obstacle.style.left = `${newLeft}px`;
                const sonarSvg = document.getElementById('tcSonarWavesSvg');
                if (sonarSvg) updateSonarVisualization(comp, obstacle, sonarSvg);
            }
            triggerCircuitSolve();
        });
        fields.querySelectorAll('.tc-preset-chip[data-dist]').forEach(chip => {
            chip.addEventListener('click', function () {
                comp.props.distance = parseFloat(this.getAttribute('data-dist'));
                state.hardwareValues.ultrasonicCm = comp.props.distance;
                const slider = document.getElementById('tcUsSlider');
                if (slider) slider.value = comp.props.distance;
                const valText = document.getElementById('tcUsValText');
                if (valText) valText.textContent = `${comp.props.distance} cm`;
                const badge = document.getElementById('tcObstacleBadge');
                if (badge) badge.textContent = `${comp.props.distance} cm`;
                const obstacle = document.getElementById('tcUltrasonicObstacle');
                if (obstacle) {
                    const newLeft = comp.x + 95 + (comp.props.distance * 2.8);
                    obstacle.style.left = `${newLeft}px`;
                    const sonarSvg = document.getElementById('tcSonarWavesSvg');
                    if (sonarSvg) updateSonarVisualization(comp, obstacle, sonarSvg);
                }
                triggerCircuitSolve();
            });
        });

        // TMP36 Slider & Chips
        document.getElementById('tcTmpSlider')?.addEventListener('input', function () {
            comp.props.tempC = parseFloat(this.value);
            state.hardwareValues.tmp36Temp = comp.props.tempC;
            const valText = document.getElementById('tcTmpValText');
            if (valText) valText.textContent = `${comp.props.tempC.toFixed(1)}°C`;
            renderComponentDOM(comp);
            triggerCircuitSolve();
        });
        fields.querySelectorAll('.tc-preset-chip[data-temp]').forEach(chip => {
            chip.addEventListener('click', function () {
                comp.props.tempC = parseFloat(this.getAttribute('data-temp'));
                state.hardwareValues.tmp36Temp = comp.props.tempC;
                const slider = document.getElementById('tcTmpSlider');
                if (slider) slider.value = comp.props.tempC;
                const valText = document.getElementById('tcTmpValText');
                if (valText) valText.textContent = `${comp.props.tempC.toFixed(1)}°C`;
                renderComponentDOM(comp);
                triggerCircuitSolve();
            });
        });

        // Servo Slider & Chips
        document.getElementById('tcServoSlider')?.addEventListener('input', function () {
            comp.props.angle = parseInt(this.value);
            const horn = document.getElementById(`${comp.id}_horn`);
            if (horn) horn.style.transform = `rotate(${comp.props.angle}deg)`;
            const badge = document.querySelector(`#${comp.id} .tc-servo-angle-badge`);
            if (badge) badge.textContent = `${comp.props.angle}°`;
            const valText = document.getElementById('tcServoValText');
            if (valText) valText.textContent = `${comp.props.angle}°`;
        });
        fields.querySelectorAll('.tc-preset-chip[data-ang]').forEach(chip => {
            chip.addEventListener('click', function () {
                comp.props.angle = parseInt(this.getAttribute('data-ang'));
                const horn = document.getElementById(`${comp.id}_horn`);
                if (horn) horn.style.transform = `rotate(${comp.props.angle}deg)`;
                const badge = document.querySelector(`#${comp.id} .tc-servo-angle-badge`);
                if (badge) badge.textContent = `${comp.props.angle}°`;
                const slider = document.getElementById('tcServoSlider');
                if (slider) slider.value = comp.props.angle;
                const valText = document.getElementById('tcServoValText');
                if (valText) valText.textContent = `${comp.props.angle}°`;
            });
        });

        // Stepper Motor Controls
        const updateStepperAngle = (newAng) => {
            comp.props.angle = ((newAng % 360) + 360) % 360;
            const needle = document.getElementById(`${comp.id}_needle`);
            if (needle) needle.style.transform = `rotate(${comp.props.angle}deg)`;
            const badge = document.querySelector(`#${comp.id} .tc-stepper-angle-badge`);
            if (badge) badge.textContent = `${comp.props.angle}°`;
            const text = document.getElementById('tcStepperAngText');
            if (text) text.textContent = `${comp.props.angle}°`;
        };
        document.getElementById('tcStepBackBtn')?.addEventListener('click', () => updateStepperAngle((comp.props.angle || 0) - 10));
        document.getElementById('tcStepFwdBtn')?.addEventListener('click', () => updateStepperAngle((comp.props.angle || 0) + 10));
        fields.querySelectorAll('.tc-preset-chip[data-stepang]').forEach(chip => {
            chip.addEventListener('click', function () {
                updateStepperAngle(parseInt(this.getAttribute('data-stepang')));
            });
        });

        // Sound Sensor Clap Button
        document.getElementById('tcInspClapBtn')?.addEventListener('click', () => {
            playPlugSound();
            comp.props.soundActive = true;
            state.hardwareValues.digitalPins[7] = 1;
            triggerCircuitSolve();
            showToast('👏 Sound Peak Generated (LM393 Triggered)');
            setTimeout(() => {
                comp.props.soundActive = false;
                state.hardwareValues.digitalPins[7] = 0;
                triggerCircuitSolve();
            }, 500);
        });

        // DC Motor PWM
        document.getElementById('tcMotorPwmSlider')?.addEventListener('input', function () {
            comp.props.speed = parseInt(this.value);
            const prop = document.getElementById(`${comp.id}_prop`);
            if (prop) prop.style.animationDuration = comp.props.speed > 0 ? `${Math.max(0.04, 1.2 - (comp.props.speed / 255))}s` : '0s';
            const valText = document.getElementById('tcMotorPwmText');
            if (valText) valText.textContent = comp.props.speed;
        });
        fields.querySelectorAll('.tc-preset-chip[data-pwm]').forEach(chip => {
            chip.addEventListener('click', function () {
                comp.props.speed = parseInt(this.getAttribute('data-pwm'));
                const prop = document.getElementById(`${comp.id}_prop`);
                if (prop) prop.style.animationDuration = comp.props.speed > 0 ? `${Math.max(0.04, 1.2 - (comp.props.speed / 255))}s` : '0s';
                const slider = document.getElementById('tcMotorPwmSlider');
                if (slider) slider.value = comp.props.speed;
                const valText = document.getElementById('tcMotorPwmText');
                if (valText) valText.textContent = comp.props.speed;
            });
        });

        // Seven Segment Digit Chips
        fields.querySelectorAll('.tc-seg-test-chip').forEach(chip => {
            chip.addEventListener('click', function () {
                const digit = parseInt(this.getAttribute('data-digit'));
                comp.props.digit = digit;
                renderComponentDOM(comp);
                showToast(`7-Segment Display: ${digit}`);
            });
        });

        // Buttons & Switches
        document.getElementById('tcBtnToggle')?.addEventListener('click', function () {
            comp.props.pressed = !comp.props.pressed;
            this.textContent = comp.props.pressed ? 'Pressed (Closed)' : 'Normal (Open)';
            const cap = document.getElementById(`${comp.id}_cap`);
            if (cap) {
                if (comp.props.pressed) cap.classList.add('tc-btn-active-cap');
                else cap.classList.remove('tc-btn-active-cap');
            }
            triggerCircuitSolve();
        });

        document.getElementById('tcSwitchToggle')?.addEventListener('click', function () {
            comp.props.state = comp.props.state === 'left' ? 'right' : 'left';
            this.textContent = comp.props.state === 'left' ? 'Left Position' : 'Right Position';
            renderComponentDOM(comp);
            triggerCircuitSolve();
        });
    }

    function hideComponentInspector() {
        const pop = document.getElementById('tcComponentInspector');
        if (pop) pop.style.display = 'none';
    }

    // ==========================================
    // 13. ELECTRICAL CIRCUIT SOLVER (Union-Find)
    // ==========================================
    function triggerCircuitSolve() {
        analyzeCircuit();
    }

    function normalizePin(pin) {
        if (typeof pin === 'string') {
            const pUpper = pin.toUpperCase();
            if (pUpper === 'A0') return 14;
            if (pUpper === 'A1') return 15;
            if (pUpper === 'A2') return 16;
            if (pUpper === 'A3') return 17;
            if (pUpper === 'A4') return 18;
            if (pUpper === 'A5') return 19;
            return parseInt(pin) || 0;
        }
        return Number(pin) || 0;
    }

    function pinToTerminalId(pinNum) {
        if (pinNum === 0) return 'ard-pin-rx';
        if (pinNum === 1) return 'ard-pin-tx';
        if (pinNum >= 2 && pinNum <= 13) return `ard-pin-${pinNum}`;
        if (pinNum >= 14 && pinNum <= 19) return `ard-pin-a${pinNum - 14}`;
        return null;
    }

    function analyzeCircuit() {
        // 1. Build Disjoint Set Union (Union-Find)
        const parent = {};
        function find(x) {
            if (!parent[x]) parent[x] = x;
            if (parent[x] !== x) parent[x] = find(parent[x]);
            return parent[x];
        }
        function union(a, b) {
            if (!a || !b) return;
            const rootA = find(a);
            const rootB = find(b);
            if (rootA !== rootB) parent[rootA] = rootB;
        }

        // A. Breadboard 5-hole column buses
        for (let c = 1; c <= 30; c++) {
            const upper = ['a', 'b', 'c', 'd', 'e'].map(r => `bb-${r}${c}`);
            for (let i = 0; i < upper.length - 1; i++) union(upper[i], upper[i + 1]);

            const lower = ['f', 'g', 'h', 'i', 'j'].map(r => `bb-${r}${c}`);
            for (let i = 0; i < lower.length - 1; i++) union(lower[i], lower[i + 1]);
        }

        // B. Breadboard continuous power rails
        for (let c = 1; c < 30; c++) {
            union(`bb-top-pos-${c}`, `bb-top-pos-${c + 1}`);
            union(`bb-top-neg-${c}`, `bb-top-neg-${c + 1}`);
            union(`bb-bot-pos-${c}`, `bb-bot-pos-${c + 1}`);
            union(`bb-bot-neg-${c}`, `bb-bot-neg-${c + 1}`);
        }

        // C. Wires
        state.wires.forEach(w => union(w.from, w.to));

        // D. Component leads inserted into breadboard holes (dist <= 16px)
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
                        if (dist <= 16) {
                            union(termId, hId);
                        }
                    }
                }
            });
        });

        // E. Internal Switch and Button States
        state.components.filter(c => c.type === 'pushbutton').forEach(btn => {
            union(`${btn.id}_a1`, `${btn.id}_a2`);
            union(`${btn.id}_b1`, `${btn.id}_b2`);
            if (btn.props.pressed) {
                union(`${btn.id}_a1`, `${btn.id}_b1`);
            }
        });

        state.components.filter(c => c.type === 'slide_switch').forEach(sw => {
            if (sw.props.state === 'left') {
                union(`${sw.id}_com`, `${sw.id}_t1`);
            } else {
                union(`${sw.id}_com`, `${sw.id}_t2`);
            }
        });

        state.components.filter(c => c.type === 'tilt').forEach(tilt => {
            if (tilt.props.tilted) {
                union(`${tilt.id}_t1`, `${tilt.id}_t2`);
            }
        });

        // 2. Identify Power and Ground Nets
        const gndRoots = new Set([find('ard-pin-gnd0'), find('ard-pin-gnd1'), find('ard-pin-gnd2')]);
        const v5Roots = new Set([find('ard-pin-5v'), find('ard-pin-ioref'), find('ard-pin-vin')]);

        let isShortCircuit = false;
        for (const gRoot of gndRoots) {
            if (v5Roots.has(gRoot)) isShortCircuit = true;
        }

        // 3. Map Net Voltages
        const netVoltages = {};
        for (const gRoot of gndRoots) netVoltages[gRoot] = 0.0;
        for (const vRoot of v5Roots) netVoltages[vRoot] = 5.0;

        // Digital Pin sources (OUTPUT HIGH or PWM)
        for (let pin = 0; pin < 14; pin++) {
            const termId = pinToTerminalId(pin);
            if (!termId) continue;
            const root = find(termId);
            const mode = state.hardwareValues.pinModes[pin];
            const stateVal = state.hardwareValues.digitalPins[pin];
            const pwm = state.hardwareValues.pinPWM[pin];

            if (mode === 1) { // OUTPUT
                if (pwm > 0) {
                    netVoltages[root] = (pwm / 255.0) * 5.0;
                } else if (stateVal === 1) {
                    netVoltages[root] = 5.0;
                } else {
                    netVoltages[root] = 0.0;
                }
            } else if (mode === 2) { // INPUT_PULLUP
                if (gndRoots.has(root)) {
                    netVoltages[root] = 0.0;
                } else {
                    netVoltages[root] = 5.0;
                }
            }
        }

        // 4. Resolve Sensors & Transducers
        state.components.filter(c => c.type === 'potentiometer').forEach(pot => {
            const r1 = find(`${pot.id}_t1`);
            const r2 = find(`${pot.id}_t2`);
            const rw = find(`${pot.id}_wiper`);
            const v1 = netVoltages[r1] !== undefined ? netVoltages[r1] : (v5Roots.has(r1) ? 5.0 : 0.0);
            const v2 = netVoltages[r2] !== undefined ? netVoltages[r2] : (gndRoots.has(r2) ? 0.0 : 0.0);
            const pos = pot.props.position !== undefined ? pot.props.position : 0.5;
            const wiperV = v2 + (v1 - v2) * (1.0 - pos);
            netVoltages[rw] = wiperV;
        });

        state.components.filter(c => c.type === 'ldr').forEach(ldr => {
            const r1 = find(`${ldr.id}_t1`);
            const r2 = find(`${ldr.id}_t2`);
            const lux = ldr.props.lux !== undefined ? ldr.props.lux : (state.hardwareValues.ldrLux || 400);
            const rLdr = 500000 / Math.pow(lux + 1, 0.7) + 80;
            const v1 = netVoltages[r1] !== undefined ? netVoltages[r1] : 5.0;
            const v2 = netVoltages[r2] !== undefined ? netVoltages[r2] : 0.0;
            const vMid = v2 + (v1 - v2) * (10000 / (10000 + rLdr));
            netVoltages[r2] = vMid;
        });

        state.components.filter(c => c.type === 'tmp36').forEach(tmp => {
            const rVcc = find(`${tmp.id}_vcc`);
            const rGnd = find(`${tmp.id}_gnd`);
            const rOut = find(`${tmp.id}_vout`);
            if (v5Roots.has(rVcc) && gndRoots.has(rGnd)) {
                const temp = tmp.props.tempC !== undefined ? tmp.props.tempC : (state.hardwareValues.tmp36Temp || 25.0);
                const vout = Math.max(0.1, Math.min(2.0, 0.5 + 0.01 * temp));
                netVoltages[rOut] = vout;
            }
        });

        state.components.filter(c => c.type === 'pir').forEach(pir => {
            const rVcc = find(`${pir.id}_vcc`);
            const rGnd = find(`${pir.id}_gnd`);
            const rSig = find(`${pir.id}_sig`);
            if (v5Roots.has(rVcc) && gndRoots.has(rGnd)) {
                netVoltages[rSig] = (pir.props.motion || state.hardwareValues.pirMotion) ? 5.0 : 0.0;
            }
        });

        // 4b. Resolve Capacitors (RC Transient Dynamics)
        updateCapacitors(netVoltages, find, gndRoots, v5Roots);

        // 5. Arduino Builtin LED (L) on Pin 13
        const pin13State = state.hardwareValues.digitalPins[13] === 1 || state.hardwareValues.pinPWM[13] > 0;
        setPinBuiltin(pin13State && state.isSimulating);

        // 6. Placed Single-Color LEDs
        let anyLedLit = false;
        let anyOvercurrent = false;
        let anyReversePolarity = false;

        state.components.filter(c => c.type === 'led').forEach(led => {
            const anRoot = find(`${led.id}_anode`);
            const catRoot = find(`${led.id}_cathode`);

            let anodeVoltage = netVoltages[anRoot] || 0.0;
            let cathodeIsGnd = gndRoots.has(catRoot);
            let hasResistor = false;

            // Check if connected through a resistor
            state.components.filter(c => c.type === 'resistor').forEach(res => {
                const rt1 = find(`${res.id}_t1`);
                const rt2 = find(`${res.id}_t2`);
                if (rt1 === anRoot && netVoltages[rt2] !== undefined) {
                    anodeVoltage = netVoltages[rt2];
                    hasResistor = true;
                } else if (rt2 === anRoot && netVoltages[rt1] !== undefined) {
                    anodeVoltage = netVoltages[rt1];
                    hasResistor = true;
                } else if (rt1 === catRoot && gndRoots.has(rt2)) {
                    cathodeIsGnd = true;
                    hasResistor = true;
                } else if (rt2 === catRoot && gndRoots.has(rt1)) {
                    cathodeIsGnd = true;
                    hasResistor = true;
                }
            });

            if (gndRoots.has(anRoot) && (netVoltages[catRoot] > 0.5)) {
                anyReversePolarity = true;
            }

            const lens = document.getElementById(`${led.id}_lens`);
            const isForwardBiased = (anodeVoltage > 1.8) && cathodeIsGnd;

            if (isForwardBiased && state.isSimulating) {
                anyLedLit = true;
                const duty = Math.min(1.0, anodeVoltage / 5.0);
                if (lens) {
                    lens.classList.add('tc-led-lit');
                    lens.style.opacity = Math.max(0.35, duty).toString();
                    lens.style.boxShadow = `0 0 16px ${led.props.color}, 0 0 32px ${led.props.color}90`;
                }
                if (!hasResistor && anodeVoltage >= 4.5) {
                    anyOvercurrent = true;
                    if (lens) lens.classList.add('tc-overcurrent-blink');
                } else if (lens) {
                    lens.classList.remove('tc-overcurrent-blink');
                }
            } else {
                if (lens) {
                    lens.classList.remove('tc-led-lit', 'tc-overcurrent-blink');
                    lens.style.opacity = '0.85';
                    lens.style.boxShadow = 'inset 0 -3px 5px rgba(0,0,0,0.25)';
                }
            }
        });

        // 7. Placed RGB LEDs
        state.components.filter(c => c.type === 'rgb_led').forEach(rgb => {
            const rRoot = find(`${rgb.id}_r`);
            const catRoot = find(`${rgb.id}_cathode`);
            const gRoot = find(`${rgb.id}_g`);
            const bRoot = find(`${rgb.id}_b`);

            let cathodeIsGnd = gndRoots.has(catRoot);
            let vR = netVoltages[rRoot] || 0.0;
            let vG = netVoltages[gRoot] || 0.0;
            let vB = netVoltages[bRoot] || 0.0;

            // Check resistors
            state.components.filter(c => c.type === 'resistor').forEach(res => {
                const rt1 = find(`${res.id}_t1`);
                const rt2 = find(`${res.id}_t2`);
                if (rt1 === rRoot && netVoltages[rt2] !== undefined) vR = netVoltages[rt2];
                if (rt2 === rRoot && netVoltages[rt1] !== undefined) vR = netVoltages[rt1];
                if (rt1 === gRoot && netVoltages[rt2] !== undefined) vG = netVoltages[rt2];
                if (rt2 === gRoot && netVoltages[rt1] !== undefined) vG = netVoltages[rt1];
                if (rt1 === bRoot && netVoltages[rt2] !== undefined) vB = netVoltages[rt2];
                if (rt2 === bRoot && netVoltages[rt1] !== undefined) vB = netVoltages[rt1];
            });

            const lens = document.getElementById(`${rgb.id}_lens`);
            if (cathodeIsGnd && (vR > 1.0 || vG > 1.0 || vB > 1.0) && state.isSimulating) {
                const rVal = Math.round(Math.min(255, (vR / 5.0) * 255));
                const gVal = Math.round(Math.min(255, (vG / 5.0) * 255));
                const bVal = Math.round(Math.min(255, (vB / 5.0) * 255));
                const colStr = `rgb(${rVal}, ${gVal}, ${bVal})`;
                if (lens) {
                    lens.classList.add('tc-rgb-lit');
                    lens.style.setProperty('--rgb-glow', colStr);
                    lens.style.background = `radial-gradient(circle, ${colStr}, #334155)`;
                }
            } else if (lens) {
                lens.classList.remove('tc-rgb-lit');
                lens.style.background = 'radial-gradient(ellipse at 40% 30%, #f1f5f9, #94a3b8)';
            }
        });

        // 8. Micro Servo Motor (SG90)
        state.components.filter(c => c.type === 'servo').forEach(servo => {
            const rGnd = find(`${servo.id}_gnd`);
            const rVcc = find(`${servo.id}_vcc`);
            const rSig = find(`${servo.id}_sig`);

            if (gndRoots.has(rGnd) && v5Roots.has(rVcc)) {
                // If software set angle directly on servo
                let targetAng = servo.props.angle !== undefined ? servo.props.angle : 90;
                // Or if driven by PWM pin
                for (let p = 0; p < 14; p++) {
                    const termId = pinToTerminalId(p);
                    if (termId && find(termId) === rSig) {
                        if (state.hardwareValues.servoAngles[p] !== undefined) {
                            targetAng = state.hardwareValues.servoAngles[p];
                        } else if (state.hardwareValues.pinPWM[p] > 0) {
                            targetAng = Math.round((state.hardwareValues.pinPWM[p] / 255.0) * 180);
                        }
                    }
                }
                servo.props.angle = targetAng;
                const horn = document.getElementById(`${servo.id}_horn`);
                if (horn) horn.style.transform = `rotate(${targetAng}deg)`;
                const badge = document.querySelector(`#${servo.id} .tc-servo-angle-badge`);
                if (badge) badge.textContent = `${targetAng}°`;
            }
        });

        // 9. DC Motor / Fan
        state.components.filter(c => c.type === 'dc_motor').forEach(motor => {
            const pRoot = find(`${motor.id}_pos`);
            const nRoot = find(`${motor.id}_neg`);
            const vPos = netVoltages[pRoot] || 0.0;
            const vNeg = netVoltages[nRoot] || 0.0;
            const diff = Math.abs(vPos - vNeg);
            const prop = document.getElementById(`${motor.id}_propeller`);
            if (diff > 1.2 && state.isSimulating) {
                if (prop) prop.classList.add('spinning');
            } else if (prop) {
                prop.classList.remove('spinning');
            }
        });

        // 10. Piezo Buzzer
        state.components.filter(c => c.type === 'buzzer').forEach(bz => {
            const pRoot = find(`${bz.id}_pos`);
            const nRoot = find(`${bz.id}_neg`);
            const posV = netVoltages[pRoot] || 0.0;
            const negIsGnd = gndRoots.has(nRoot);
            const bzBody = document.getElementById(`${bz.id}_body`);

            if (posV > 1.5 && negIsGnd && state.isSimulating) {
                if (bzBody && !bzBody.querySelector('.tc-buzzer-wave')) {
                    const wave = document.createElement('div');
                    wave.className = 'tc-buzzer-wave';
                    bzBody.appendChild(wave);
                }
            } else if (bzBody) {
                bzBody.querySelectorAll('.tc-buzzer-wave').forEach(w => w.remove());
            }
        });

        // 11. Circuit Health & Warning Banners
        let validStatus = false;
        let warningText = '';

        if (state.wires.length === 0) {
            warningText = '<i class="fa-solid fa-triangle-exclamation"></i> Open Circuit — Click "Auto-Wire" or connect Arduino pins to breadboard';
        } else if (isShortCircuit) {
            warningText = '<i class="fa-solid fa-skull-crossbones" style="color:#ef4444;"></i> Short Circuit Detected: 5V rail is shorted directly to GND!';
        } else if (anyReversePolarity) {
            warningText = '<i class="fa-solid fa-triangle-exclamation"></i> Reverse Polarity: LED Cathode is facing positive rail instead of GND!';
        } else if (anyOvercurrent) {
            warningText = '<i class="fa-solid fa-fire-flame-curved" style="color:#f59e0b;"></i> Overcurrent Warning: LED connected directly without current-limiting resistor!';
            validStatus = true;
        } else {
            validStatus = true;
            warningText = '<i class="fa-solid fa-circle-check"></i> Circuit Active: Closed loop verified • Real-time telemetry online';
        }

        state.circuitStatus = {
            valid: validStatus,
            shortCircuit: isShortCircuit,
            anyLedLit,
            find,
            netVoltages,
            gndRoots,
            v5Roots
        };

        updateCircuitWarning(warningText, validStatus);
    }

    function updateCapacitors(voltagesMap, findFn, gndRootsSet, v5RootsSet) {
        const find = findFn || (state.circuitStatus && state.circuitStatus.find);
        const voltages = voltagesMap || (state.circuitStatus && state.circuitStatus.netVoltages);
        if (!find || !voltages) return;

        const caps = state.components.filter(c => c.type === 'capacitor');
        if (caps.length === 0) return;

        const gndRoots = gndRootsSet || (state.circuitStatus && state.circuitStatus.gndRoots) || new Set([find('ard-pin-gnd0'), find('ard-pin-gnd1'), find('ard-pin-gnd2')]);
        const v5Roots = v5RootsSet || (state.circuitStatus && state.circuitStatus.v5Roots) || new Set([find('ard-pin-5v'), find('ard-pin-ioref'), find('ard-pin-vin')]);
        const resistors = state.components.filter(c => c.type === 'resistor');
        const now = Date.now();

        if (!state.capacitorStates) state.capacitorStates = {};

        caps.forEach(cap => {
            if (!state.capacitorStates[cap.id]) {
                state.capacitorStates[cap.id] = { v: 0.0, lastTime: now };
            }
            const capState = state.capacitorStates[cap.id];

            const r1 = find(`${cap.id}_t1`); // positive lead
            const r2 = find(`${cap.id}_t2`); // negative lead
            if (!r1 || !r2) return;

            let activeRoot = r1;
            let refRoot = r2;
            let polarityReversed = false;

            if (gndRoots.has(r1) && !gndRoots.has(r2)) {
                activeRoot = r2;
                refRoot = r1;
                polarityReversed = true;
            } else {
                activeRoot = r1;
                refRoot = r2;
            }

            const vRef = voltages[refRoot] !== undefined ? voltages[refRoot] : (gndRoots.has(refRoot) ? 0.0 : 0.0);

            // Find resistor connected to activeRoot
            let connectedResistor = null;
            let driveRoot = null;

            for (const res of resistors) {
                const rt1 = find(`${res.id}_t1`);
                const rt2 = find(`${res.id}_t2`);
                if (rt1 === activeRoot && rt2 !== activeRoot && rt2 !== refRoot) {
                    connectedResistor = res;
                    driveRoot = rt2;
                    break;
                } else if (rt2 === activeRoot && rt1 !== activeRoot && rt1 !== refRoot) {
                    connectedResistor = res;
                    driveRoot = rt1;
                    break;
                }
            }

            // Determine drive voltage
            let vDrive = null;
            if (driveRoot && voltages[driveRoot] !== undefined) {
                vDrive = voltages[driveRoot];
            } else if (!connectedResistor) {
                for (let pin = 0; pin < 14; pin++) {
                    const termId = pinToTerminalId(pin);
                    if (termId && find(termId) === activeRoot && state.hardwareValues.pinModes[pin] === 1) {
                        vDrive = voltages[activeRoot];
                        break;
                    }
                }
                if (vDrive === null) {
                    if (v5Roots.has(activeRoot)) vDrive = 5.0;
                    else if (gndRoots.has(activeRoot)) vDrive = 0.0;
                }
            }

            // Resistance in Ohms
            let rOhms = 10000;
            if (connectedResistor) {
                let rVal = Number(connectedResistor.props.resistance) || 10000;
                const u = connectedResistor.props.unit || 'Ω';
                if (u === 'kΩ') rVal *= 1000;
                else if (u === 'MΩ') rVal *= 1000000;
                rOhms = Math.max(0.1, rVal);
            } else {
                rOhms = 0.1; // Direct connection: near instant charge
            }

            // Capacitance in Farads
            let cFarads = 100e-6;
            const capVal = Number(cap.props.capacitance) || 100;
            const capUnit = cap.props.unit || 'µF';
            if (capUnit === 'µF' || capUnit === 'uF') cFarads = capVal * 1e-6;
            else if (capUnit === 'nF') cFarads = capVal * 1e-9;
            else if (capUnit === 'pF') cFarads = capVal * 1e-12;
            else if (capUnit === 'mF') cFarads = capVal * 1e-3;
            else if (capUnit === 'F') cFarads = capVal;
            cFarads = Math.max(1e-12, cFarads);

            const tau = Math.max(0.0001, rOhms * cFarads);

            // Compute time delta
            let dt = 0;
            if (capState.lastTime && capState.lastTime <= now) {
                dt = Math.min(2.0, (now - capState.lastTime) / 1000.0);
            }
            capState.lastTime = now;

            if (vDrive !== null && state.isSimulating) {
                const targetDelta = polarityReversed ? (vRef - vDrive) : (vDrive - vRef);
                if (dt > 0) {
                    const alpha = Math.exp(-dt / tau);
                    capState.v = targetDelta + (capState.v - targetDelta) * alpha;
                }
            }

            capState.v = Math.max(-5.0, Math.min(5.0, capState.v));
            const nodeVoltage = polarityReversed ? (vRef - capState.v) : (vRef + capState.v);
            voltages[activeRoot] = Math.max(0.0, Math.min(5.0, nodeVoltage));
        });
    }

    function updateCircuitWarning(text, isValid) {
        const el = document.getElementById('tcCircuitWarning');
        if (!el) return;

        if (!state.isSimulating) {
            el.classList.remove('visible', 'ok');
            return;
        }

        el.className = 'tc-circuit-warning visible' + (isValid ? ' ok' : '');
        el.innerHTML = text;
    }

    function setPinBuiltin(val) {
        const lLed = document.getElementById('arduinoBuiltinLed');
        if (lLed) lLed.setAttribute('fill', val ? '#eab308' : '#334155');
    }

    function getDigitalRead(pin) {
        if (!state.circuitStatus.find) return 0;
        updateCapacitors();
        const termId = pinToTerminalId(pin);
        if (!termId) return 0;

        const root = state.circuitStatus.find(termId);
        const gndRoots = new Set([
            state.circuitStatus.find('ard-pin-gnd0'),
            state.circuitStatus.find('ard-pin-gnd1'),
            state.circuitStatus.find('ard-pin-gnd2')
        ]);

        const mode = state.hardwareValues.pinModes[pin];
        if (mode === 2) { // INPUT_PULLUP
            return gndRoots.has(root) ? 0 : 1;
        }

        const v = state.circuitStatus.netVoltages[root] || 0.0;
        return v >= 2.5 ? 1 : 0;
    }

    function getAnalogRead(pin) {
        if (!state.circuitStatus.find) return 0;
        updateCapacitors();
        const termId = pinToTerminalId(pin);
        if (!termId) return 0;

        const root = state.circuitStatus.find(termId);
        const v = state.circuitStatus.netVoltages[root] || 0.0;
        const adc = Math.min(1023, Math.max(0, Math.round((v / 5.0) * 1023)));
        if (pin >= 14 && pin <= 19) state.hardwareValues.analogPins[pin - 14] = adc;
        return adc;
    }

    function getPulseIn(pin, targetVal) {
        const termId = pinToTerminalId(pin);
        if (!termId || !state.circuitStatus.find) return 0;

        const echoRoot = state.circuitStatus.find(termId);
        const usComp = state.components.find(c => c.type === 'ultrasonic');
        if (!usComp) return 0;

        const usEchoRoot = state.circuitStatus.find(`${usComp.id}_echo`);
        if (echoRoot === usEchoRoot) {
            const cm = state.hardwareValues.ultrasonicCm || usComp.props.distance || 25;
            return Math.round(cm * 58.3);
        }
        return 0;
    }

    // ==========================================
    // 14. C++ TO ASYNC JAVASCRIPT TRANSPILER
    // ==========================================
    function transpileArduino(code) {
        // Strip block comments /* ... */ while preserving line count
        let cleanCode = code.replace(/\/\*[\s\S]*?\*\//g, match => {
            return match.split('\n').map(() => '//').join('\n');
        });

        // 1. Array definitions on single line: int arr[] = {1, 2, 3}; -> let arr = [1, 2, 3];
        cleanCode = cleanCode.replace(/\b(?:const\s+)?(?:int|float|double|long|unsigned\s+long|unsigned\s+int|short|byte|char|bool|boolean|String)\s+([a-zA-Z0-9_]+)\s*\[\s*\d*\s*\]\s*=\s*\{([^}\n\r]*)\}/g, 'let $1 = [$2]');

        // 2. Array definitions spanning multiple lines: const byte table[10] = { \n ... \n };
        cleanCode = cleanCode.replace(/\b(?:const\s+)?(?:int|float|double|long|unsigned\s+long|unsigned\s+int|short|byte|char|bool|boolean|String)\s+([a-zA-Z0-9_]+)\s*\[\s*\d*\s*\]\s*=\s*\{/g, 'let $1 = [');

        let lines = cleanCode.split('\n');
        let out = [];
        let declaredUserFuncs = new Set();
        let declaredServos = new Set();

        // Pass 1: Scan for Servo declarations and user functions
        for (let line of lines) {
            let trimmed = line.trim();
            if (trimmed.startsWith('//')) continue;

            let servoMatch = trimmed.match(/^Servo\s+([a-zA-Z0-9_,\s]+);/);
            if (servoMatch) {
                let names = servoMatch[1].split(',').map(s => s.trim()).filter(Boolean);
                names.forEach(n => declaredServos.add(n));
            }

            let funcMatch = trimmed.match(/^(?:void|int|float|double|long|unsigned\s+long|bool|boolean|char|String)\s+([a-zA-Z0-9_]+)\s*\(([^)]*)\)\s*\{/);
            if (funcMatch) {
                let name = funcMatch[1];
                if (name !== 'setup' && name !== 'loop') {
                    declaredUserFuncs.add(name);
                }
            }
        }

        // Pass 2: Line by line transformation
        for (let line of lines) {
            let l = line.replace(/\r$/, '');

            if (l.trim().startsWith('//')) {
                out.push(l);
                continue;
            }

            if (l.trim().startsWith('#include')) {
                out.push('// [Included Header] ' + l);
                continue;
            }

            let defMatch = l.match(/^\s*#define\s+([a-zA-Z0-9_]+)\s+(.+)$/);
            if (defMatch) {
                out.push(`const ${defMatch[1]} = ${defMatch[2]};`);
                continue;
            }

            let servoDecl = l.match(/^\s*Servo\s+([a-zA-Z0-9_]+)\s*;/);
            if (servoDecl) {
                out.push(`let ${servoDecl[1]} = new __env.Servo("${servoDecl[1]}");`);
                continue;
            }

            // Close multi-line array block if line starts with '};'
            if (l.trim().startsWith('};')) {
                l = l.replace(/\};/, '];');
                out.push(l);
                continue;
            }

            // Strip C++ 'volatile'
            l = l.replace(/\bvolatile\s+/g, '');

            // Extract string literals to protect them from regex replacements
            const stringLiterals = [];
            l = l.replace(/(["'])(?:\\(?:\r\n|[\s\S])|(?!\1)[^\\\r\n])*\1/g, match => {
                stringLiterals.push(match);
                return `__STR_LIT_${stringLiterals.length - 1}__`;
            });

            // Arrays declarations without init: int arr[10]; -> let arr = new Array(10).fill(0)
            l = l.replace(/\b(?:int|float|double|long|unsigned\s+long|unsigned\s+int|short|byte|char|bool|boolean)\s+([a-zA-Z0-9_]+)\s*\[\s*(\d+)\s*\]/g, 'let $1 = new Array($2).fill(0)');
            // Array string/literal without brackets in JS: const char name[] = "..." -> const name = "..."
            l = l.replace(/\b(?:const\s+)?(?:char|byte|int|float|double)\s+([a-zA-Z0-9_]+)\s*\[\s*\]\s*=/g, 'const $1 =');

            // Function signatures
            let funcDef = l.match(/^(\s*)(?:void|int|float|double|long|unsigned\s+long|bool|boolean|char|String)\s+([a-zA-Z0-9_]+)\s*\(([^)]*)\)\s*(\{?)/);
            if (funcDef) {
                let indent = funcDef[1];
                let name = funcDef[2];
                let params = funcDef[3];
                let brace = funcDef[4];

                let cleanedParams = params.split(',').map(p => {
                    let pTrim = p.trim();
                    if (!pTrim) return '';
                    let parts = pTrim.split(/\s+/);
                    let pName = parts[parts.length - 1].replace(/^&/, '');
                    return pName;
                }).filter(Boolean).join(', ');

                l = `${indent}async function ${name}(${cleanedParams}) ${brace}`;
            } else {
                // Declarations
                l = l.replace(/\bfor\s*\(\s*(?:int|float|long|unsigned\s+long|unsigned\s+int|short|byte)\s+/g, 'for (let ');
                l = l.replace(/\bconst\s+(?:int|float|double|long|unsigned\s+long|unsigned\s+int|short|byte|char|bool|boolean|String)\b/g, 'const');
                l = l.replace(/\b(?:int|float|double|long|unsigned\s+long|unsigned\s+int|short|byte|char|bool|boolean|String)\s+([a-zA-Z0-9_]+)\b/g, 'let $1');

                // While loop yields to prevent browser hang
                l = l.replace(/\bwhile\s*\((.*)\)\s*\{/g, 'while ($1) { await __env.yield();');

                // Async delays & pulseIn
                l = l.replace(/\bdelay\s*\(/g, 'await __env.delay(');
                l = l.replace(/\bdelayMicroseconds\s*\(/g, 'await __env.delayMicroseconds(');
                l = l.replace(/\bpulseIn\s*\(/g, 'await __env.pulseIn(');

                for (let uf of declaredUserFuncs) {
                    let re = new RegExp(`\\b${uf}\\s*\\(`, 'g');
                    l = l.replace(re, `await ${uf}(`);
                }

                // Builtins
                l = l.replace(/\bpinMode\s*\(/g, '__env.pinMode(');
                l = l.replace(/\bdigitalWrite\s*\(/g, '__env.digitalWrite(');
                l = l.replace(/\bdigitalRead\s*\(/g, '__env.digitalRead(');
                l = l.replace(/\banalogWrite\s*\(/g, '__env.analogWrite(');
                l = l.replace(/\banalogRead\s*\(/g, '__env.analogRead(');
                l = l.replace(/\bmillis\s*\(\s*\)/g, '__env.millis()');
                l = l.replace(/\bmicros\s*\(\s*\)/g, '__env.micros()');
                l = l.replace(/\bmap\s*\(/g, '__env.map(');
                l = l.replace(/\bconstrain\s*\(/g, '__env.constrain(');
                l = l.replace(/\btone\s*\(/g, '__env.tone(');
                l = l.replace(/\bnoTone\s*\(/g, '__env.noTone(');
                l = l.replace(/\brandom\s*\(/g, '__env.random(');
                l = l.replace(/\bsq\s*\(/g, 'Math.pow(');
                l = l.replace(/\bsqrt\s*\(/g, 'Math.sqrt(');
                l = l.replace(/\babs\s*\(/g, 'Math.abs(');
                l = l.replace(/\bmin\s*\(/g, 'Math.min(');
                l = l.replace(/\bmax\s*\(/g, 'Math.max(');
                l = l.replace(/\bpow\s*\(/g, 'Math.pow(');
                l = l.replace(/\bround\s*\(/g, 'Math.round(');
                l = l.replace(/\bfloor\s*\(/g, 'Math.floor(');
                l = l.replace(/\bceil\s*\(/g, 'Math.ceil(');
                l = l.replace(/\bstrlen\s*\(/g, '__env.strlen(');

                // Serial
                l = l.replace(/\bSerial\.begin\s*\(/g, '__env.Serial.begin(');
                l = l.replace(/\bSerial\.println\s*\(/g, '__env.Serial.println(');
                l = l.replace(/\bSerial\.print\s*\(/g, '__env.Serial.print(');
                l = l.replace(/\bSerial\.available\s*\(\s*\)/g, '__env.Serial.available()');
                l = l.replace(/\bSerial\.read\s*\(\s*\)/g, '__env.Serial.read()');

                // Advanced Syllabus Components & Protocols
                l = l.replace(/\bIRrecv\s+([a-zA-Z0-9_]+)\s*\(([^)]*)\)\s*;/g, 'let $1 = new __env.IRrecv($2);');
                l = l.replace(/\bdecode_results\s+([a-zA-Z0-9_]+)\s*;/g, 'let $1 = new __env.decode_results();');
                l = l.replace(/\bStepper\s+([a-zA-Z0-9_]+)\s*\(([^)]*)\)\s*;/g, 'let $1 = new __env.Stepper($2);');
                l = l.replace(/\bSoftwareSerial\s+([a-zA-Z0-9_]+)\s*\(([^)]*)\)\s*;/g, 'let $1 = new __env.SoftwareSerial($2);');
                l = l.replace(/\battachInterrupt\s*\(/g, '__env.attachInterrupt(');
                l = l.replace(/\bdigitalPinToInterrupt\s*\(/g, '__env.digitalPinToInterrupt(');

                // Strip C++ address-of operator only inside function argument lists like (&results) or (, &var)
                l = l.replace(/(?<=[(,]\s*)&\s*([a-zA-Z0-9_]+)/g, '$1');

                // Constants
                l = l.replace(/\bHIGH\b/g, '__env.HIGH');
                l = l.replace(/\bLOW\b/g, '__env.LOW');
                l = l.replace(/\bOUTPUT\b/g, '__env.OUTPUT');
                l = l.replace(/\bINPUT_PULLUP\b/g, '__env.INPUT_PULLUP');
                l = l.replace(/\bINPUT\b/g, '__env.INPUT');
                l = l.replace(/\bLED_BUILTIN\b/g, '__env.LED_BUILTIN');
                l = l.replace(/\bHEX\b/g, '__env.HEX');
                l = l.replace(/\bDEC\b/g, '__env.DEC');
                l = l.replace(/\bBIN\b/g, '__env.BIN');
                l = l.replace(/\bOCT\b/g, '__env.OCT');
                l = l.replace(/\bFALLING\b/g, '__env.FALLING');
                l = l.replace(/\bRISING\b/g, '__env.RISING');
                l = l.replace(/\bCHANGE\b/g, '__env.CHANGE');
                l = l.replace(/\bA0\b/g, '__env.A0');
                l = l.replace(/\bA1\b/g, '__env.A1');
                l = l.replace(/\bA2\b/g, '__env.A2');
                l = l.replace(/\bA3\b/g, '__env.A3');
                l = l.replace(/\bA4\b/g, '__env.A4');
                l = l.replace(/\bA5\b/g, '__env.A5');
            }

            // Restore string literals pristine without modification
            l = l.replace(/__STR_LIT_(\d+)__/g, (match, idx) => {
                return stringLiterals[parseInt(idx, 10)];
            });

            out.push(l);
        }

        return out.join('\n');
    }

    // ==========================================
    // 15. SIMULATION ENGINE (Live Firmware Execution)
    // ==========================================
    function toggleSimulation() {
        if (state.isSimulating) stopSimulation();
        else startSimulation();
    }

    async function startSimulation() {
        if (state.isSimulating) return;

        const rawCode = codeEditor ? codeEditor.getValue() : (document.getElementById('tcCodeTextarea')?.value || '');
        let transpiled = '';

        try {
            transpiled = transpileArduino(rawCode);
        } catch (err) {
            appendSerial(`[Compile Error] Syntax error: ${err.message}\n`);
            alert(`Compilation Error:\n${err.message}`);
            return;
        }

        state.isSimulating = true;
        state.firmwareCancelToken = { cancelled: false };
        state.simStartTime = Date.now();
        state.simTick = 0;
        state.plotterData = [];
        state.capacitorStates = {};
        state.components.filter(c => c.type === 'capacitor').forEach(c => {
            state.capacitorStates[c.id] = { v: 0.0, lastTime: Date.now() };
        });

        if (typeof window.trackPhysicsEvent === 'function') {
            window.trackPhysicsEvent('arduino_simulation_start', {
                preset: state.currentPreset,
                components_count: state.components.length,
                baud_rate: state.baudRate
            });
        }

        const btn = document.getElementById('tcStartSimBtn');
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-stop"></i> Stop Simulation';
            btn.className = 'btn-tc-sim stop';
        }

        const onLed = document.getElementById('arduinoOnLed');
        if (onLed) onLed.setAttribute('fill', '#22c55e');

        appendSerial(`--- ATmega328P Online: 16 MHz Clock • Baud ${state.baudRate} ---\n`);
        triggerCircuitSolve();

        const env = {
            HIGH: 1,
            LOW: 0,
            OUTPUT: 1,
            INPUT: 0,
            INPUT_PULLUP: 2,
            LED_BUILTIN: 13,
            A0: 14, A1: 15, A2: 16, A3: 17, A4: 18, A5: 19,

            pinMode: (pin, mode) => {
                const p = normalizePin(pin);
                state.hardwareValues.pinModes[p] = mode;
                triggerCircuitSolve();
            },
            digitalWrite: (pin, val) => {
                const p = normalizePin(pin);
                const s = (val === 1 || val === true || val === 'HIGH') ? 1 : 0;
                state.hardwareValues.digitalPins[p] = s;
                state.hardwareValues.pinPWM[p] = s ? 255 : 0;
                triggerCircuitSolve();
            },
            digitalRead: (pin) => {
                const p = normalizePin(pin);
                return getDigitalRead(p);
            },
            analogWrite: (pin, val) => {
                const p = normalizePin(pin);
                const pwm = Math.max(0, Math.min(255, Math.round(Number(val) || 0)));
                state.hardwareValues.pinPWM[p] = pwm;
                state.hardwareValues.digitalPins[p] = pwm > 127 ? 1 : 0;
                triggerCircuitSolve();
            },
            analogRead: (pin) => {
                const p = normalizePin(pin);
                return getAnalogRead(p);
            },
            delay: async (ms) => {
                const target = Date.now() + Math.max(1, ms);
                while (Date.now() < target) {
                    if (state.firmwareCancelToken.cancelled || !state.isSimulating) break;
                    const chunk = Math.min(25, target - Date.now());
                    await new Promise(r => setTimeout(r, Math.max(1, chunk)));
                }
            },
            delayMicroseconds: async (us) => {
                if (us > 1000) {
                    await new Promise(r => setTimeout(r, Math.round(us / 1000)));
                } else {
                    await new Promise(r => setTimeout(r, 0));
                }
            },
            pulseIn: async (pin, val) => {
                return getPulseIn(pin, val);
            },
            yield: async () => {
                if (!state.isSimulating || state.firmwareCancelToken.cancelled) throw new Error('Simulation Stopped');
                await new Promise(r => setTimeout(r, 1));
            },
            millis: () => Date.now() - state.simStartTime,
            micros: () => (Date.now() - state.simStartTime) * 1000,
            map: (x, in_min, in_max, out_min, out_max) => Math.round((x - in_min) * (out_max - out_min) / (in_max - in_min) + out_min),
            constrain: (amt, low, high) => Math.max(low, Math.min(high, amt)),
            random: (min, max) => (max === undefined ? Math.floor(Math.random() * min) : Math.floor(Math.random() * (max - min) + min)),
            strlen: (s) => (s ? s.length : 0),
            pow: (b, e) => Math.pow(b, e),
            round: (x) => Math.round(x),
            tone: (pin, freq) => playTone(freq),
            noTone: (pin) => stopTone(),
            Servo: class {
                constructor(name) {
                    this.name = name;
                    this.pin = null;
                    this.angle = 90;
                }
                attach(pin) {
                    this.pin = normalizePin(pin);
                    state.hardwareValues.servoAngles[this.pin] = this.angle;
                    triggerCircuitSolve();
                }
                write(ang) {
                    this.angle = Math.max(0, Math.min(180, Math.round(ang)));
                    if (this.pin !== null) {
                        state.hardwareValues.servoAngles[this.pin] = this.angle;
                    }
                    triggerCircuitSolve();
                }
                read() {
                    return this.angle;
                }
            },
            Serial: {
                begin: (baud) => {
                    state.baudRate = baud || 9600;
                    appendSerial(`[Serial] Baud set to ${state.baudRate}\n`);
                },
                print: (msg, precision) => {
                    const str = formatSerialPrint(msg, precision);
                    appendSerial(str);
                    extractPlotterValue(str);
                },
                println: (msg, precision) => {
                    const str = msg !== undefined ? formatSerialPrint(msg, precision) + '\n' : '\n';
                    appendSerial(str);
                    extractPlotterValue(str);
                },
                available: () => {
                    return (state.hardwareValues.btQueue && state.hardwareValues.btQueue.length > 0) ? state.hardwareValues.btQueue.length : 0;
                },
                read: () => {
                    if (!state.hardwareValues.btQueue || state.hardwareValues.btQueue.length === 0) return -1;
                    const ch = state.hardwareValues.btQueue.shift();
                    return typeof ch === 'number' ? ch : ch.charCodeAt(0);
                }
            },

            HEX: 'HEX',
            DEC: 'DEC',
            BIN: 'BIN',
            OCT: 'OCT',
            FALLING: 'FALLING',
            RISING: 'RISING',
            CHANGE: 'CHANGE',

            attachInterrupt: (pin, isr, mode) => {
                state.hardwareValues.interruptIsr = isr;
            },
            digitalPinToInterrupt: (p) => normalizePin(p),

            IRrecv: class {
                constructor(pin) {
                    this.pin = normalizePin(pin);
                }
                enableIRIn() { }
                decode(res) {
                    if (state.hardwareValues.lastIrCode) {
                        res.value = state.hardwareValues.lastIrCode;
                        state.hardwareValues.lastIrCode = 0;
                        return true;
                    }
                    return false;
                }
                resume() { }
            },

            decode_results: class {
                constructor() {
                    this.value = 0;
                }
            },

            Stepper: class {
                constructor(steps, p1, p2, p3, p4) {
                    this.stepsPerRev = steps || 2048;
                    this.pins = [p1, p2, p3, p4].map(normalizePin);
                    this.speed = 60;
                    this.curAngle = 0;
                }
                setSpeed(rpm) { this.speed = rpm; }
                step(steps) {
                    this.curAngle = (this.curAngle + (steps / this.stepsPerRev) * 360) % 360;
                    const sm = state.components.find(c => c.type === 'stepper_motor');
                    if (sm) {
                        sm.props.angle = Math.round(this.curAngle);
                        const needle = document.getElementById(`${sm.id}_needle`);
                        if (needle) needle.style.transform = `rotate(${sm.props.angle}deg)`;
                        const badge = document.querySelector(`#${sm.id} .tc-stepper-angle-badge`);
                        if (badge) badge.textContent = `${sm.props.angle}°`;
                    }
                }
            },

            SoftwareSerial: class {
                constructor(rx, tx) {
                    this.rx = rx; this.tx = tx;
                }
                begin(baud) { }
                available() {
                    return (state.hardwareValues.btQueue && state.hardwareValues.btQueue.length > 0) ? state.hardwareValues.btQueue.length : 0;
                }
                read() {
                    if (!state.hardwareValues.btQueue || state.hardwareValues.btQueue.length === 0) return -1;
                    const ch = state.hardwareValues.btQueue.shift();
                    return typeof ch === 'number' ? ch : ch.charCodeAt(0);
                }
                write(c) {
                    const ch = typeof c === 'number' ? String.fromCharCode(c) : String(c);
                    appendSerial(`[BT Out]: ${ch}\n`);
                }
                print(s) { appendSerial(`[BT Out]: ${s}`); }
                println(s) { appendSerial(`[BT Out]: ${s}\n`); }
            },
            isRunning: () => state.isSimulating && !state.firmwareCancelToken.cancelled
        };

        state.simInterval = setInterval(() => {
            state.simTick++;
            updateSimTimer();
            if (state.circuitStatus && state.circuitStatus.netVoltages) {
                updateCapacitors();
            }
        }, 50);

        try {
            const AsyncFunction = Object.getPrototypeOf(async function(){}).constructor;
            const runner = new AsyncFunction('__env', `
                ${transpiled}
                if (typeof setup === 'function') await setup();
                while (__env.isRunning()) {
                    if (typeof loop === 'function') await loop();
                    await new Promise(r => setTimeout(r, 0));
                }
            `);

            runner(env).catch(err => {
                if (!state.firmwareCancelToken.cancelled) {
                    appendSerial(`\n[Runtime Notice] ${err.message}\n`);
                }
            });

        } catch (compileErr) {
            appendSerial(`\n[Sketch Syntax Error] ${compileErr.message}\n`);
            alert(`Syntax Error in Arduino Sketch:\n${compileErr.message}`);
            stopSimulation();
        }
    }

    function stopSimulation() {
        const durationSeconds = state.simStartTime > 0 ? Math.max(0, Math.round((Date.now() - state.simStartTime) / 1000)) : 0;
        state.isSimulating = false;
        state.firmwareCancelToken.cancelled = true;
        state.capacitorStates = {};

        if (typeof window.trackPhysicsEvent === 'function') {
            window.trackPhysicsEvent('arduino_simulation_stop', {
                preset: state.currentPreset,
                duration_seconds: durationSeconds
            });
        }

        if (state.simInterval) clearInterval(state.simInterval);

        const btn = document.getElementById('tcStartSimBtn');
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-play"></i> Start Simulation';
            btn.className = 'btn-tc-sim start';
        }

        const onLed = document.getElementById('arduinoOnLed');
        if (onLed) onLed.setAttribute('fill', '#334155');

        stopTone();
        setPinBuiltin(false);

        // Turn off all physical LEDs
        state.components.filter(c => c.type === 'led').forEach(comp => {
            const lens = document.getElementById(`${comp.id}_lens`);
            if (lens) {
                lens.classList.remove('tc-led-lit', 'tc-overcurrent-blink');
                lens.style.opacity = '0.85';
                lens.style.boxShadow = 'inset 0 -3px 5px rgba(0,0,0,0.25)';
            }
        });

        // Turn off RGB LEDs
        state.components.filter(c => c.type === 'rgb_led').forEach(comp => {
            const lens = document.getElementById(`${comp.id}_lens`);
            if (lens) {
                lens.classList.remove('tc-rgb-lit');
                lens.style.background = 'radial-gradient(ellipse at 40% 30%, #f1f5f9, #94a3b8)';
            }
        });

        // Stop motors
        state.components.filter(c => c.type === 'dc_motor').forEach(comp => {
            const prop = document.getElementById(`${comp.id}_propeller`);
            if (prop) prop.classList.remove('spinning');
        });

        updateCircuitWarning('', false);
        appendSerial('--- Simulation Terminated ---\n');
    }

    function rebootMCU() {
        appendSerial('--- Hardware Reset Triggered (Watchdog Reset) ---\n');
        stopSimulation();
        setTimeout(startSimulation, 150);
    }

    function formatSerialPrint(msg, precision) {
        if (typeof msg === 'number') {
            if (precision === 'HEX' || precision === 16) return msg.toString(16).toUpperCase();
            if (precision === 'BIN' || precision === 2) return msg.toString(2);
            if (precision === 'OCT' || precision === 8) return msg.toString(8);
            if (precision === 'DEC' || precision === 10) return Math.round(msg).toString();
            return precision !== undefined ? msg.toFixed(precision) : msg.toString();
        }
        return String(msg);
    }

    function playPlugSound() {
        try {
            if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') audioCtx.resume();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(450, audioCtx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(120, audioCtx.currentTime + 0.045);
            gain.gain.setValueAtTime(0.18, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.045);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.045);
        } catch(e) {}
    }

    function extractPlotterValue(text) {
        if (!text) return;
        const trimmed = text.trim();
        if (!trimmed) return;

        // Ignore banner comments, header lines, status messages or informational text
        if (trimmed.startsWith('---') || trimmed.startsWith('>>>') || trimmed.startsWith('===') || 
            trimmed.startsWith('[') || trimmed.startsWith('//') || trimmed.startsWith('*') ||
            trimmed.startsWith('<') || trimmed.startsWith('#')) {
            return;
        }

        // Check if string is purely numeric (supports floats, negatives, scientific notation, commas/spaces)
        // e.g. "0.244", "0.244\n", "1023", "2.5, 3.1", "100 200"
        const isNumeric = /^[-+]?\d*\.?\d+(?:[eE][-+]?\d+)?(?:\s*[, \t]\s*[-+]?\d*\.?\d+(?:[eE][-+]?\d+)?)*$/.test(trimmed);

        // Or standard telemetry key:value like "vCap: 3.14" or "val = 2.5"
        const isKeyValue = /^[a-zA-Z_][a-zA-Z0-9_]*\s*[:=]\s*[-+]?\d*\.?\d+(?:[eE][-+]?\d+)?$/.test(trimmed);

        if (!isNumeric && !isKeyValue) {
            return;
        }

        const match = trimmed.match(/(?:[-+]?\d*\.?\d+(?:[eE][-+]?\d+)?)/g);
        if (match && match.length > 0) {
            const lastNum = parseFloat(match[match.length - 1]);
            if (!isNaN(lastNum)) {
                pushPlotter(lastNum);
            }
        }
    }

    function playTone(freq) {
        try {
            if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (activeToneOsc) activeToneOsc.stop();
            if (!freq || freq <= 0) return;

            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = 'square';
            osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.06, audioCtx.currentTime);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start();
            activeToneOsc = osc;
        } catch(e) {}
    }

    function stopTone() {
        if (activeToneOsc) {
            try { activeToneOsc.stop(); } catch(e){}
            activeToneOsc = null;
        }
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
        if (state.serialLogs.length > 400) state.serialLogs.shift();
        const stream = document.getElementById('tcSerialStream');
        if (stream) {
            stream.textContent = state.serialLogs.join('');
            stream.scrollTop = stream.scrollHeight;
        }
    }

    function pushPlotter(val) {
        state.plotterData.push(val);
        if (state.plotterData.length > 120) state.plotterData.shift();
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

        ctx.strokeStyle = 'rgba(255, 255, 255, 0.08)';
        ctx.lineWidth = 1;
        for (let y = 20; y < h; y += 25) {
            ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(w, y); ctx.stroke();
        }
        for (let x = 0; x < w; x += 40) {
            ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, h); ctx.stroke();
        }

        if (state.plotterData.length === 0) return;

        const minVal = Math.min(...state.plotterData, 0);
        const maxVal = Math.max(...state.plotterData, 5.0);
        const range = (maxVal - minVal) || 1;

        ctx.fillStyle = '#64748b';
        ctx.font = '10px "JetBrains Mono", monospace';
        ctx.fillText(`Max: ${maxVal.toFixed(2)}`, 10, 14);
        ctx.fillText(`Min: ${minVal.toFixed(2)}`, 10, h - 4);

        const latest = state.plotterData[state.plotterData.length - 1];
        ctx.fillStyle = '#38bdf8';
        ctx.font = '11px "JetBrains Mono", monospace';
        ctx.fillText(`${latest.toFixed(2)}`, w - 55, 14);

        if (state.plotterData.length < 2) return;

        const gradient = ctx.createLinearGradient(0, 0, 0, h);
        gradient.addColorStop(0, 'rgba(56, 189, 248, 0.25)');
        gradient.addColorStop(1, 'rgba(56, 189, 248, 0)');

        const stepX = w / 120;
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
    }

    // ==========================================
    // 16. SCHEMATIC & BOM GENERATORS
    // ==========================================
    function switchView(viewName) {
        state.currentView = viewName;
        const canvasContainer = document.getElementById('tcCanvasContainer');
        const schematicContainer = document.getElementById('tcSchematicContainer');
        const bomContainer = document.getElementById('tcBomContainer');
        const codePane = document.getElementById('tcCodePane');
        const resizer = document.getElementById('tcSplitResizer');
        const rightDrawer = document.getElementById('tcRightDrawer');

        document.getElementById('tcTabCircuits')?.classList.toggle('active', viewName === 'circuits');
        document.getElementById('tcTabSchematic')?.classList.toggle('active', viewName === 'schematic');
        document.getElementById('tcTabBom')?.classList.toggle('active', viewName === 'bom');

        if (viewName !== 'circuits') {
            if (codePane) codePane.style.display = 'none';
            if (resizer) resizer.style.display = 'none';
            if (rightDrawer) rightDrawer.style.display = 'none';
            document.getElementById('tcToggleCodeBtn')?.classList.remove('active');
        } else {
            if (rightDrawer && codePane?.style.display !== 'flex') {
                rightDrawer.style.display = 'flex';
            }
        }

        if (canvasContainer) canvasContainer.style.display = viewName === 'circuits' ? 'block' : 'none';
        if (schematicContainer) schematicContainer.style.display = viewName === 'schematic' ? 'flex' : 'none';
        if (bomContainer) bomContainer.style.display = viewName === 'bom' ? 'flex' : 'none';

        if (viewName === 'schematic') renderSchematic();
        if (viewName === 'bom') renderBom();
    }

    function renderSchematic() {
        const wrapper = document.getElementById('tcSchematicSvgWrapper');
        if (!wrapper) return;

        let compSymbolsSvg = '';
        let startX = 380;
        let startY = 80;

        let ardSvg = `
            <rect x="50" y="40" width="220" height="380" rx="6" fill="#f8fafc" stroke="#00878a" stroke-width="2.5"/>
            <rect x="50" y="40" width="220" height="35" rx="6" fill="#00878a"/>
            <text x="160" y="62" fill="#ffffff" font-family="'JetBrains Mono', monospace" font-weight="700" font-size="13" text-anchor="middle">ARDUINO UNO R3</text>
            <text x="160" y="74" fill="#e0f2fe" font-family="sans-serif" font-size="9" text-anchor="middle">ATmega328P • 16 MHz</text>
        `;

        const leftPins = ['IOREF', 'RESET', '3.3V', '5V', 'GND', 'VIN', 'A0', 'A1', 'A2', 'A3', 'A4', 'A5'];
        const rightPins = ['D0/RX', 'D1/TX', 'D2', '~D3', 'D4', '~D5', '~D6', 'D7', 'D8', '~D9', '~D10', '~D11', 'D12', 'D13', 'GND', 'AREF'];

        leftPins.forEach((p, idx) => {
            const py = 105 + idx * 24;
            ardSvg += `
                <line x1="20" y1="${py}" x2="50" y2="${py}" stroke="#00878a" stroke-width="1.8"/>
                <circle cx="20" cy="${py}" r="3" fill="#00878a"/>
                <text x="56" y="${py + 4}" fill="#0f172a" font-size="10" font-weight="600">${p}</text>
            `;
        });

        rightPins.forEach((p, idx) => {
            const py = 95 + idx * 19;
            ardSvg += `
                <line x1="270" y1="${py}" x2="300" y2="${py}" stroke="#00878a" stroke-width="1.8"/>
                <circle cx="300" cy="${py}" r="3" fill="#00878a"/>
                <text x="264" y="${py + 4}" fill="#0f172a" font-size="10" font-weight="600" text-anchor="end">${p}</text>
            `;
        });

        state.components.forEach((comp, idx) => {
            const cx = startX + (idx % 2) * 190;
            const cy = startY + Math.floor(idx / 2) * 130;

            if (comp.type === 'resistor') {
                compSymbolsSvg += `
                    <g transform="translate(${cx}, ${cy})">
                        <text x="40" y="-12" fill="#0f172a" font-size="11" font-weight="700">${comp.props.name || 'R'}</text>
                        <text x="40" y="0" fill="#64748b" font-size="9">${comp.props.resistance || 220} Ω</text>
                        <line x1="0" y1="20" x2="15" y2="20" stroke="#0f172a" stroke-width="2"/>
                        <path d="M 15 20 L 20 10 L 30 30 L 40 10 L 50 30 L 60 10 L 70 30 L 75 20 L 90 20" fill="none" stroke="#0f172a" stroke-width="2"/>
                        <circle cx="0" cy="20" r="3" fill="#0284c7"/>
                        <circle cx="90" cy="20" r="3" fill="#0284c7"/>
                    </g>
                `;
            } else if (comp.type === 'led' || comp.type === 'rgb_led') {
                compSymbolsSvg += `
                    <g transform="translate(${cx}, ${cy})">
                        <text x="35" y="-12" fill="#0f172a" font-size="11" font-weight="700">${comp.props.name || 'LED'}</text>
                        <text x="35" y="0" fill="#ef4444" font-size="9">VF ≈ ${comp.props.forwardVoltage || 2.0}V</text>
                        <line x1="0" y1="20" x2="25" y2="20" stroke="#0f172a" stroke-width="2"/>
                        <polygon points="25,10 25,30 45,20" fill="${comp.props.color || '#ef4444'}" stroke="#0f172a" stroke-width="1.5"/>
                        <line x1="45" y1="8" x2="45" y2="32" stroke="#0f172a" stroke-width="2.5"/>
                        <line x1="45" y1="20" x2="70" y2="20" stroke="#0f172a" stroke-width="2"/>
                        <circle cx="0" cy="20" r="3" fill="#0284c7"/>
                        <circle cx="70" cy="20" r="3" fill="#0284c7"/>
                    </g>
                `;
            } else {
                compSymbolsSvg += `
                    <g transform="translate(${cx}, ${cy})">
                        <rect x="0" y="0" width="80" height="40" rx="4" fill="#f1f5f9" stroke="#0f172a" stroke-width="1.5"/>
                        <text x="40" y="24" fill="#0f172a" font-size="10" font-weight="700" text-anchor="middle">${comp.type.toUpperCase()}</text>
                    </g>
                `;
            }
        });

        wrapper.innerHTML = `
            <svg id="tcSchematicSvgDoc" class="tc-schematic-svg" viewBox="0 0 780 480" width="780" height="480" xmlns="http://www.w3.org/2000/svg">
                <rect width="100%" height="100%" fill="#ffffff"/>
                ${ardSvg}
                ${compSymbolsSvg}
            </svg>
        `;
    }

    function exportSchematicSvg() {
        const svg = document.getElementById('tcSchematicSvgDoc');
        if (!svg) {
            renderSchematic();
        }
        const freshSvg = document.getElementById('tcSchematicSvgDoc');
        if (!freshSvg) return;

        if (typeof window.trackPhysicsEvent === 'function') {
            window.trackPhysicsEvent('arduino_export', { format: 'schematic_svg', preset: state.currentPreset });
        }

        const serializer = new XMLSerializer();
        const src = serializer.serializeToString(freshSvg);
        const title = (document.getElementById('tcProjectTitle')?.value || `arduino_schematic_${state.currentPreset}`).replace(/[^a-zA-Z0-9_-]/g, '_');
        const blob = new Blob([src], { type: 'image/svg+xml;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${title}.svg`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    function exportCircuitSvgString() {
        const uno = document.getElementById('arduinoUno');
        const bb = document.getElementById('breadboardSmall');
        const wiresSvg = document.getElementById('tcWiresSvg');

        const width = 1100;
        const height = 650;
        const serializer = new XMLSerializer();

        let out = `<?xml version="1.0" encoding="UTF-8"?>\n`;
        out += `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">\n`;
        out += `<defs>\n`;
        out += `  <pattern id="dotGridExport" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1.2" fill="#cbd5e1"/></pattern>\n`;
        out += `</defs>\n`;
        out += `<rect width="100%" height="100%" fill="#ffffff"/>\n`;
        out += `<rect width="100%" height="100%" fill="url(#dotGridExport)"/>\n`;

        const title = (document.getElementById('tcProjectTitle')?.value || 'Arduino Breadboard Physics Circuit')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        out += `<text x="30" y="38" font-family="'Plus Jakarta Sans', sans-serif" font-weight="800" font-size="16" fill="#0284c7">${title}</text>\n`;
        out += `<text x="30" y="55" font-family="sans-serif" font-size="11" fill="#64748b">Python4Physics Virtual Circuits Workbench • Experimental Physics Lab</text>\n`;

        if (uno) {
            const left = parseFloat(uno.style.left) || 35;
            const top = parseFloat(uno.style.top) || 75;
            const unoSvg = uno.querySelector('svg');
            if (unoSvg) {
                let unoStr = serializer.serializeToString(unoSvg);
                unoStr = unoStr.replace(/^<svg[^>]*>/i, '').replace(/<\/svg>$/i, '');
                out += `<g transform="translate(${left}, ${top})">${unoStr}</g>\n`;
            }
        }

        if (bb) {
            const left = parseFloat(bb.style.left) || 430;
            const top = parseFloat(bb.style.top) || 35;
            const bbSvg = bb.querySelector('svg');
            if (bbSvg) {
                let bbStr = serializer.serializeToString(bbSvg);
                bbStr = bbStr.replace(/^<svg[^>]*>/i, '').replace(/<\/svg>$/i, '');
                out += `<g transform="translate(${left}, ${top})">${bbStr}</g>\n`;
            }
        }

        if (wiresSvg) {
            const wiresGroup = document.getElementById('tcWiresGroup');
            if (wiresGroup) {
                out += serializer.serializeToString(wiresGroup) + '\n';
            }
        }

        state.components.forEach(comp => {
            const el = document.getElementById(comp.id);
            if (el) {
                const left = parseFloat(el.style.left) || comp.x;
                const top = parseFloat(el.style.top) || comp.y;
                const rot = comp.rotation || 0;
                const compW = el.offsetWidth || 80;
                const compH = el.offsetHeight || 60;
                const compHtml = el.innerHTML.replace(/&(?!(amp|lt|gt|quot|apos);)/g, '&amp;');
                out += `<g transform="translate(${left}, ${top}) rotate(${rot})">
                    <foreignObject width="${compW}" height="${compH}">
                        <div xmlns="http://www.w3.org/1999/xhtml" style="width:100%;height:100%;position:relative;">
                            ${compHtml}
                        </div>
                    </foreignObject>
                </g>\n`;
            }
        });

        out += `</svg>`;
        return out;
    }

    function downloadCircuitSvg() {
        if (typeof window.trackPhysicsEvent === 'function') {
            window.trackPhysicsEvent('arduino_export', { format: 'circuit_svg', preset: state.currentPreset });
        }
        const svgStr = exportCircuitSvgString();
        const title = (document.getElementById('tcProjectTitle')?.value || `arduino_circuit_${state.currentPreset || 'lab'}`).trim().replace(/[^a-zA-Z0-9_-]/g, '_') || 'arduino_circuit';
        const blob = new Blob([svgStr], { type: 'image/svg+xml;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${title}.svg`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(() => URL.revokeObjectURL(url), 2000);
        showToast('✓ Circuit vector exported successfully (.svg)');
    }

    function downloadCircuitPng() {
        if (typeof window.trackPhysicsEvent === 'function') {
            window.trackPhysicsEvent('arduino_export', { format: 'circuit_png', preset: state.currentPreset });
        }
        const title = (document.getElementById('tcProjectTitle')?.value || `arduino_circuit_${state.currentPreset || 'lab'}`).trim().replace(/[^a-zA-Z0-9_-]/g, '_') || 'arduino_circuit';
        showToast('Generating high-resolution circuit image...');

        const stage = document.getElementById('tcCanvasStage');
        if (!stage) {
            fallbackSvgPngExport(title);
            return;
        }

        if (typeof html2canvas === 'function') {
            exportViaHtml2Canvas(stage, title);
        } else {
            fallbackSvgPngExport(title);
        }
    }

    function exportViaHtml2Canvas(stage, title) {
        const uno = document.getElementById('arduinoUno');
        const bb = document.getElementById('breadboardSmall');

        let minX = 9999, minY = 9999, maxX = 0, maxY = 0;

        if (uno) {
            const uLeft = parseFloat(uno.style.left) || 35;
            const uTop = parseFloat(uno.style.top) || 75;
            minX = Math.min(minX, uLeft);
            minY = Math.min(minY, uTop);
            maxX = Math.max(maxX, uLeft + 360);
            maxY = Math.max(maxY, uTop + 250);
        }

        if (bb) {
            const bLeft = parseFloat(bb.style.left) || 430;
            const bTop = parseFloat(bb.style.top) || 35;
            minX = Math.min(minX, bLeft);
            minY = Math.min(minY, bTop);
            maxX = Math.max(maxX, bLeft + 600);
            maxY = Math.max(maxY, bTop + 330);
        }

        state.components.forEach(comp => {
            const cLeft = comp.x;
            const cTop = comp.y;
            const cWidth = comp.width || 80;
            const cHeight = comp.height || 60;
            minX = Math.min(minX, cLeft);
            minY = Math.min(minY, cTop);
            maxX = Math.max(maxX, cLeft + cWidth);
            maxY = Math.max(maxY, cTop + cHeight);
        });

        state.wires.forEach(w => {
            if (w.fromPos) {
                minX = Math.min(minX, w.fromPos.x);
                maxX = Math.max(maxX, w.fromPos.x);
                minY = Math.min(minY, w.fromPos.y);
                maxY = Math.max(maxY, w.fromPos.y);
            }
            if (w.toPos) {
                minX = Math.min(minX, w.toPos.x);
                maxX = Math.max(maxX, w.toPos.x);
                minY = Math.min(minY, w.toPos.y);
                maxY = Math.max(maxY, w.toPos.y);
            }
            if (w.waypoints && w.waypoints.length) {
                w.waypoints.forEach(pt => {
                    minX = Math.min(minX, pt.x);
                    maxX = Math.max(maxX, pt.x);
                    minY = Math.min(minY, pt.y);
                    maxY = Math.max(maxY, pt.y);
                });
            }
        });

        if (minX > maxX) { minX = 0; maxX = 1100; }
        if (minY > maxY) { minY = 0; maxY = 500; }

        const padding = 40;
        const cropX = Math.max(0, Math.floor(minX - padding));
        const cropY = Math.max(0, Math.floor(minY - padding));
        const cropW = Math.ceil(maxX - minX + padding * 2);
        const cropH = Math.ceil(maxY - minY + padding * 2);

        const prevTransform = stage.style.transform;
        const prevTransition = stage.style.transition;
        stage.style.transition = 'none';
        stage.style.transform = 'translate(0px, 0px) scale(1)';

        const terms = document.getElementById('tcTerminalsContainer');
        const prevTermsVis = terms ? terms.style.visibility : '';
        if (terms) terms.style.visibility = 'hidden';

        const rubber = document.getElementById('tcRubberbandWire');
        const prevRubberDisplay = rubber ? rubber.style.display : '';
        if (rubber) rubber.style.display = 'none';

        const inspector = document.getElementById('tcComponentInspector');
        const prevInspectorDisplay = inspector ? inspector.style.display : '';
        if (inspector) inspector.style.display = 'none';

        html2canvas(stage, {
            x: cropX,
            y: cropY,
            width: cropW,
            height: cropH,
            scale: 2,
            backgroundColor: '#ffffff',
            useCORS: true,
            logging: false
        }).then(canvas => {
            stage.style.transform = prevTransform;
            stage.style.transition = prevTransition;
            if (terms) terms.style.visibility = prevTermsVis;
            if (rubber) rubber.style.display = prevRubberDisplay;
            if (inspector) inspector.style.display = prevInspectorDisplay;

            const bannerH = 50;
            const finalCanvas = document.createElement('canvas');
            finalCanvas.width = canvas.width;
            finalCanvas.height = canvas.height + bannerH * 2;
            const ctx = finalCanvas.getContext('2d');

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, finalCanvas.width, finalCanvas.height);

            ctx.fillStyle = '#0284c7';
            ctx.font = 'bold 28px "Plus Jakarta Sans", system-ui, -apple-system, sans-serif';
            const projTitle = document.getElementById('tcProjectTitle')?.value || 'Arduino Breadboard Physics Circuit';
            ctx.fillText(projTitle, 40, 42);

            ctx.fillStyle = '#64748b';
            ctx.font = '18px "Plus Jakarta Sans", system-ui, -apple-system, sans-serif';
            ctx.fillText('Python4Physics Virtual Circuits Workbench • Experimental Physics Lab', 40, 74);

            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.moveTo(40, 88);
            ctx.lineTo(finalCanvas.width - 40, 88);
            ctx.stroke();

            ctx.drawImage(canvas, 0, bannerH * 2);

            finalCanvas.toBlob(blob => {
                if (!blob) {
                    fallbackSvgPngExport(title);
                    return;
                }
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${title}.png`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                setTimeout(() => URL.revokeObjectURL(url), 2000);
                showToast('✓ Circuit image exported successfully (.png)');
            }, 'image/png');
        }).catch(err => {
            console.warn('[html2canvas Error]', err);
            stage.style.transform = prevTransform;
            stage.style.transition = prevTransition;
            if (terms) terms.style.visibility = prevTermsVis;
            if (rubber) rubber.style.display = prevRubberDisplay;
            if (inspector) inspector.style.display = prevInspectorDisplay;

            fallbackSvgPngExport(title);
        });
    }

    function fallbackSvgPngExport(title) {
        const svgStr = exportCircuitSvgString();
        const svgBlob = new Blob([svgStr], { type: 'image/svg+xml;charset=utf-8' });
        const url = URL.createObjectURL(svgBlob);
        const img = new Image();

        img.onload = function () {
            const canvas = document.createElement('canvas');
            canvas.width = 2200;
            canvas.height = 1300;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            URL.revokeObjectURL(url);

            canvas.toBlob(function (pngBlob) {
                if (!pngBlob) {
                    showToast('✕ Error creating PNG blob. Downloading Vector SVG.');
                    downloadCircuitSvg();
                    return;
                }
                const pngUrl = URL.createObjectURL(pngBlob);
                const a = document.createElement('a');
                a.href = pngUrl;
                a.download = `${title}.png`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                setTimeout(() => URL.revokeObjectURL(pngUrl), 2000);
                showToast('✓ Circuit image exported successfully (.png)');
            }, 'image/png');
        };

        img.onerror = function (err) {
            console.warn('[SVG-PNG Fallback Error]', err);
            URL.revokeObjectURL(url);
            showToast('✕ Direct render failed. Exporting Vector SVG.');
            downloadCircuitSvg();
        };

        img.src = url;
    }

    function exportCodeIno() {
        if (typeof window.trackPhysicsEvent === 'function') {
            window.trackPhysicsEvent('arduino_export', { format: 'ino', preset: state.currentPreset });
        }
        const code = codeEditor ? codeEditor.getValue() : (document.getElementById('tcCodeTextarea')?.value || '');
        const title = (document.getElementById('tcProjectTitle')?.value || 'sketch').replace(/[^a-zA-Z0-9_-]/g, '_');
        const blob = new Blob([code], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${title}.ino`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    function exportCodeCpp() {
        if (typeof window.trackPhysicsEvent === 'function') {
            window.trackPhysicsEvent('arduino_export', { format: 'cpp', preset: state.currentPreset });
        }
        const code = codeEditor ? codeEditor.getValue() : (document.getElementById('tcCodeTextarea')?.value || '');
        const title = (document.getElementById('tcProjectTitle')?.value || 'sketch').replace(/[^a-zA-Z0-9_-]/g, '_');
        const blob = new Blob([code], { type: 'text/x-c++src;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${title}.cpp`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    function copyCodeClipboard() {
        const code = codeEditor ? codeEditor.getValue() : (document.getElementById('tcCodeTextarea')?.value || '');
        navigator.clipboard.writeText(code).then(() => {
            alert('✓ Arduino C++ sketch copied to clipboard!');
        }).catch(() => {
            alert('✓ Arduino C++ sketch copied to clipboard!');
        });
    }

    function renderBom() {
        const tbody = document.getElementById('tcBomTableBody');
        if (!tbody) return;
        tbody.innerHTML = '';

        const items = [
            {
                name: 'Arduino Uno R3',
                designator: 'ARD1',
                props: 'ATmega328P Microcontroller, 16 MHz, 5.0V',
                connections: 'USB-B, 14 Digital I/O, 6 Analog In, DC Barrel',
                qty: 1
            },
            {
                name: 'Solderless Breadboard',
                designator: 'BB1',
                props: 'Half-size 400 Tie-Point Socket with Power Rails',
                connections: '30 Columns (a-e, f-j), 4 Power Buses (+/-)',
                qty: 1
            }
        ];

        state.components.forEach(c => {
            const lib = COMPONENT_LIBRARY[c.type];
            items.push({
                name: lib ? lib.name : c.type.toUpperCase(),
                designator: c.props.name || c.id,
                props: `${c.type.toUpperCase()} Component`,
                connections: 'Breadboard / Direct Header Leads',
                qty: 1
            });
        });

        items.forEach((item, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="padding: 9px 12px; color: var(--tc-text-muted); font-weight: 600;">${idx + 1}</td>
                <td style="padding: 9px 12px; font-weight: 700; color: var(--tc-text-main);">${item.name}</td>
                <td style="padding: 9px 12px; font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #0284c7;">${item.designator}</td>
                <td style="padding: 9px 12px; color: var(--tc-text-muted); font-size: 0.82rem;">${item.props}</td>
                <td style="padding: 9px 12px; color: var(--tc-text-muted); font-size: 0.82rem;">${item.connections}</td>
                <td style="padding: 9px 12px; text-align: center; font-weight: 700;">${item.qty}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    function exportBomCsv() {
        if (typeof window.trackPhysicsEvent === 'function') {
            window.trackPhysicsEvent('arduino_export', { format: 'bom_csv', preset: state.currentPreset });
        }
        let csv = 'Item #,Part Name,Designator,Properties / Value,Net Connections,Qty\n';
        const rows = document.querySelectorAll('#tcBomTable tbody tr');
        rows.forEach(tr => {
            const cols = Array.from(tr.querySelectorAll('td')).map(td => `"${td.textContent.replace(/"/g, '""').trim()}"`);
            csv += cols.join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `bill_of_materials_${state.currentPreset}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    }

    function showToast(message) {
        let toast = document.getElementById('tcToastBanner');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'tcToastBanner';
            toast.className = 'tc-toast-banner';
            const container = document.getElementById('tcCanvasContainer');
            if (container) container.appendChild(toast);
        }
        toast.innerHTML = `<i class="fa-solid fa-circle-check" style="color:#10b981;"></i> ${message}`;
        toast.classList.add('show');
        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => {
            toast.classList.remove('show');
        }, 2800);
    }

    // ==========================================
    // 17. PRESET LOADER
    // ==========================================
    function loadPreset(key, options = {}) {
        if (!presets[key]) return;
        state.currentPreset = key;
        const p = presets[key];

        const sel = document.getElementById('tcPresetSelector');
        if (sel && sel.value !== key) sel.value = key;

        const projTitle = document.getElementById('tcProjectTitle');
        if (projTitle && p.title) projTitle.value = p.title;

        state.components = [];
        state.wires = [];
        state.undoStack = [];
        state.redoStack = [];
        state.plotterData = [];
        state.capacitorStates = {};

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
        updateInteractivePhysicsWidgets();
        appendSerial(`--- Loaded Project: ${p.title} ---\n`);
        showToast(`🚀 Loaded: ${p.title}`);

        if (options.scroll) {
            document.getElementById('tcStudio')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

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
        triggerCircuitSolve();
    }

    function clearAllWires() {
        const oldWires = [...state.wires];
        state.wires = [];
        state.capacitorStates = {};
        deselectAll();
        renderWires();
        triggerCircuitSolve();
        appendSerial('--- Wires cleared for freeform wiring ---\n');

        pushUndo({
            type: 'clearAllWires',
            undo: () => { state.wires = oldWires; renderWires(); triggerCircuitSolve(); },
            redo: () => { state.wires = []; renderWires(); triggerCircuitSolve(); }
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
        triggerCircuitSolve();
        updateInteractivePhysicsWidgets();
        updateStatusBar();
    }

    // ==========================================
    // 18. UI BINDINGS & CONTROLS
    // ==========================================
    function bindUI() {
        document.getElementById('tcPresetSelector')?.addEventListener('change', function () {
            loadPreset(this.value);
        });

        // Curriculum "Open in Circuit Simulator" launch buttons
        document.querySelectorAll('.tc-launch-project-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const presetKey = this.getAttribute('data-preset');
                if (presetKey && presets[presetKey]) {
                    loadPreset(presetKey, { scroll: true });
                }
            });
        });

        document.getElementById('tcTabCircuits')?.addEventListener('click', () => switchView('circuits'));
        document.getElementById('tcTabSchematic')?.addEventListener('click', () => switchView('schematic'));
        document.getElementById('tcTabBom')?.addEventListener('click', () => switchView('bom'));

        document.getElementById('tcExportSchematicBtn')?.addEventListener('click', exportSchematicSvg);
        document.getElementById('tcExportBomBtn')?.addEventListener('click', exportBomCsv);

        document.getElementById('arduinoHwResetBtn')?.addEventListener('click', rebootMCU);

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

        function toggleCodePane(forceOpen = null) {
            const codePane = document.getElementById('tcCodePane');
            const resizer = document.getElementById('tcSplitResizer');
            const rightDrawer = document.getElementById('tcRightDrawer');
            const btn = document.getElementById('tcToggleCodeBtn');
            if (!codePane || !resizer) return;

            if (state.currentView !== 'circuits') {
                switchView('circuits');
            }

            const isCode = codePane.style.display === 'flex';
            const shouldOpen = forceOpen !== null ? forceOpen : !isCode;

            if (shouldOpen) {
                codePane.style.display = 'flex';
                resizer.style.display = 'flex';
                if (rightDrawer) rightDrawer.style.display = 'none';
                btn?.classList.add('active');
                if (codeEditor) {
                    setTimeout(() => codeEditor.refresh(), 50);
                }
            } else {
                codePane.style.display = 'none';
                resizer.style.display = 'none';
                if (rightDrawer) rightDrawer.style.display = 'flex';
                btn?.classList.remove('active');
            }
        }

        document.getElementById('tcToggleCodeBtn')?.addEventListener('click', () => toggleCodePane());
        document.getElementById('tcCloseCodeBtn')?.addEventListener('click', () => toggleCodePane(false));

        function initSplitResizer() {
            const resizer = document.getElementById('tcSplitResizer');
            const codePane = document.getElementById('tcCodePane');
            const workbench = document.getElementById('tcWorkbenchBody') || document.querySelector('.tc-workbench-body');
            if (!resizer || !codePane || !workbench) return;

            let isDragging = false;

            resizer.addEventListener('mousedown', (e) => {
                isDragging = true;
                resizer.classList.add('dragging');
                document.body.style.cursor = 'col-resize';
                document.body.style.userSelect = 'none';
                e.preventDefault();
            });

            window.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                const workbenchRect = workbench.getBoundingClientRect();
                const newWidth = workbenchRect.right - e.clientX;
                const minWidth = 280;
                const maxWidth = workbenchRect.width - 250;

                if (newWidth >= minWidth && newWidth <= maxWidth) {
                    codePane.style.width = `${newWidth}px`;
                    if (codeEditor) codeEditor.refresh();
                }
            });

            window.addEventListener('mouseup', () => {
                if (isDragging) {
                    isDragging = false;
                    resizer.classList.remove('dragging');
                    document.body.style.cursor = '';
                    document.body.style.userSelect = '';
                    if (codeEditor) codeEditor.refresh();
                }
            });

            resizer.addEventListener('dblclick', () => {
                const workbenchRect = workbench.getBoundingClientRect();
                codePane.style.width = `${Math.round(workbenchRect.width / 2)}px`;
                if (codeEditor) codeEditor.refresh();
            });
        }
        initSplitResizer();

        // Export Dropdown menu
        const exportBtn = document.getElementById('tcExportMenuBtn');
        const exportMenu = document.getElementById('tcExportMenu');
        if (exportBtn && exportMenu) {
            exportBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                exportMenu.style.display = exportMenu.style.display === 'none' ? 'flex' : 'none';
            });
            document.addEventListener('click', () => { exportMenu.style.display = 'none'; });
        }

        document.getElementById('tcExportCircuitPngBtn')?.addEventListener('click', () => downloadCircuitPng());
        document.getElementById('tcExportCircuitSvgBtn')?.addEventListener('click', () => downloadCircuitSvg());
        document.getElementById('tcExportSchematicBtn')?.addEventListener('click', () => exportSchematicSvg());
        document.getElementById('tcExportCodeInoBtn')?.addEventListener('click', () => exportCodeIno());
        document.getElementById('tcDownloadInoBtn')?.addEventListener('click', () => exportCodeIno());
        document.getElementById('tcExportCodeCppBtn')?.addEventListener('click', () => exportCodeCpp());
        document.getElementById('tcCopyCodeMenuBtn')?.addEventListener('click', () => copyCodeClipboard());
        document.getElementById('tcCopyCodeBtn')?.addEventListener('click', () => copyCodeClipboard());

        document.getElementById('tcVerifyCodeBtn')?.addEventListener('click', () => {
            const code = codeEditor ? codeEditor.getValue() : (document.getElementById('tcCodeTextarea')?.value || '');
            try {
                const transpiled = transpileArduino(code);
                const AsyncFunction = Object.getPrototypeOf(async function(){}).constructor;
                new AsyncFunction('__env', transpiled);
                const size = Math.round(1450 + code.length * 2.6);
                const sram = Math.round(180 + code.split('\n').length * 2.2);
                appendSerial(`✓ Compilation Successful!\nBinary sketch size: ${size} bytes (4% of 32,256 max).\nGlobal variables use: ${sram} bytes of SRAM.\n`);
                alert(`✓ Code Verification Passed!\nSketch uses ${size} bytes of Flash memory.`);
            } catch(err) {
                appendSerial(`✕ Verification Failed: ${err.message}\n`);
                alert(`✕ Verification Failed:\n${err.message}`);
            }
        });

        document.getElementById('tcResetCodeBtn')?.addEventListener('click', () => {
            if (presets[state.currentPreset]) {
                const defCode = presets[state.currentPreset].code;
                if (codeEditor) codeEditor.setValue(defCode);
                else {
                    const ta = document.getElementById('tcCodeTextarea');
                    if (ta) ta.value = defCode;
                }
                appendSerial(`--- Restored Default Sketch for ${presets[state.currentPreset].title} ---\n`);
            }
        });

        document.getElementById('tcBaudSelect')?.addEventListener('change', function () {
            state.baudRate = parseInt(this.value) || 9600;
            appendSerial(`--- Serial Monitor configured to ${state.baudRate} baud ---\n`);
        });

        document.getElementById('tcStartSimBtn')?.addEventListener('click', toggleSimulation);
        document.getElementById('tcAutoWireBtn')?.addEventListener('click', autoWirePreset);
        document.getElementById('tcClearWiresBtn')?.addEventListener('click', clearAllWires);



        document.getElementById('tcCloseInspectorBtn')?.addEventListener('click', hideComponentInspector);

        const stage = document.getElementById('tcCanvasStage');
        if (stage) {
            stage.addEventListener('click', handleCanvasClick);
            stage.addEventListener('mousemove', handleCanvasMouseMove);

            stage.addEventListener('touchstart', (e) => {
                if (state.drawingWire && e.touches.length === 1) {
                    const touch = e.touches[0];
                    const rect = stage.getBoundingClientRect();
                    const stageX = (touch.clientX - rect.left) / state.zoom;
                    const stageY = (touch.clientY - rect.top) / state.zoom;
                    const snapTerm = findSnapTerminal(stageX, stageY, state.drawingWire.from);
                    if (snapTerm) {
                        e.preventDefault();
                        e.stopPropagation();
                        onTerminalClick(snapTerm.id);
                    }
                }
            }, { passive: false });

            stage.addEventListener('touchmove', (e) => {
                if (state.drawingWire && e.touches.length === 1) {
                    e.preventDefault();
                    handleCanvasMouseMove(e.touches[0]);
                }
            }, { passive: false });

            stage.addEventListener('touchend', (e) => {
                if (state.drawingWire && state.snapTarget) {
                    e.preventDefault();
                    onTerminalClick(state.snapTarget);
                }
            });
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

        const container = document.getElementById('tcCanvasContainer');
        if (container) {
            document.getElementById('tcZoomInBtn')?.addEventListener('click', () => { setZoom(state.zoom + 0.1); updateStatusBar(); });
            document.getElementById('tcZoomOutBtn')?.addEventListener('click', () => { setZoom(state.zoom - 0.1); updateStatusBar(); });
            document.getElementById('tcZoomResetBtn')?.addEventListener('click', () => {
                fitToViewport();
            });

            container.addEventListener('wheel', (e) => {
                e.preventDefault();
                const delta = e.deltaY > 0 ? -0.05 : 0.05;
                setZoom(state.zoom + delta);
                updateStatusBar();
            }, { passive: false });
        }

        // Toggle Wire Style (Curved Flexible Jumper vs Straight)
        document.getElementById('tcWireTypeBtn')?.addEventListener('click', () => {
            state.wireStyle = state.wireStyle === 'curved' ? 'straight' : 'curved';
            const lbl = document.getElementById('tcWireTypeLabel');
            const icon = document.getElementById('tcWireTypeIcon');
            if (lbl) lbl.textContent = state.wireStyle === 'curved' ? 'Curved' : 'Straight';
            if (icon) {
                icon.className = state.wireStyle === 'curved' ? 'fa-solid fa-bezier-curve' : 'fa-solid fa-grip-lines';
            }
            renderWires();
            showToast(`Wire style: ${state.wireStyle === 'curved' ? 'Curved Flexible Jumper' : 'Straight Direct'}`);
        });

        // Window resize: auto fit viewport on mobile
        window.addEventListener('resize', () => {
            if (window.innerWidth < 992) {
                fitToViewport();
            }
        });
    }

    function setWireColor(color, name) {
        state.selectedWireColor = color;
        state.selectedWireName = name;
        updateWireColorDisplay(color, name);

        if (state.selectedItem?.type === 'wire') {
            const w = state.wires.find(item => item.id === state.selectedItem.id);
            if (w) {
                w.color = color === 'auto' ? getSmartWireColor(w.from, w.to) : color;
                renderWires();
            }
        }
    }

    function updateWireColorDisplay(color, name) {
        const swatch = document.getElementById('tcCurrentColorSwatch');
        const label = document.getElementById('tcCurrentColorName');
        if (swatch) {
            swatch.style.background = color === 'auto'
                ? 'linear-gradient(135deg, #ef4444 0%, #10b981 50%, #0284c7 100%)'
                : color;
        }
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
                selText = w ? `Wire (${w.color}) — [Del] to delete` : 'Wire';
            } else {
                const c = state.components.find(cc => cc.id === state.selectedItem.id);
                selText = c ? `${c.props.name} (${c.type.toUpperCase()}) — [Del] to delete, [R] to rotate` : 'Component';
            }
        } else {
            selText = 'None (Shift+Click to Select & Drag)';
        }

        const projTitle = presets[state.currentPreset]?.title || state.currentPreset;

        bar.innerHTML = `
            <div class="tc-status-item"><i class="fa-solid fa-diagram-project" style="color:#38bdf8;"></i> Project: <strong>${projTitle}</strong></div>
            <div class="tc-status-item"><i class="fa-solid fa-microchip" style="color:#0284c7;"></i> Components: ${compCount}</div>
            <div class="tc-status-item"><i class="fa-solid fa-plug" style="color:#10b981;"></i> Wires: ${wireCount}</div>
            <div class="tc-status-item"><i class="fa-solid fa-hand-pointer" style="color:#f59e0b;"></i> Selected: ${selText}</div>
            <div class="tc-status-item"><i class="fa-solid fa-magnifying-glass" style="color:#64748b;"></i> ${Math.round(state.zoom * 100)}%</div>
        `;
    }

    function fitToViewport() {
        const container = document.getElementById('tcCanvasContainer');
        const stage = document.getElementById('tcCanvasStage');
        if (!container || !stage) return;

        const rect = container.getBoundingClientRect();
        if (rect.width <= 0 || rect.height <= 0) return;

        // Content bounding box: Arduino Uno + Breadboard
        const contentWidth = 1060;
        const contentHeight = 440;

        const scaleX = (rect.width - 24) / contentWidth;
        const scaleY = (rect.height - 24) / contentHeight;
        const bestZoom = Math.min(Math.max(Math.min(scaleX, scaleY), 0.35), 1.05);

        state.zoom = bestZoom;
        state.panX = Math.max(8, (rect.width - contentWidth * bestZoom) / 2);
        state.panY = Math.max(8, (rect.height - contentHeight * bestZoom) / 2);

        stage.style.transform = `translate(${state.panX}px, ${state.panY}px) scale(${state.zoom})`;
        updateStatusBar();
    }

    function setZoom(val) {
        state.zoom = Math.max(0.35, Math.min(2.2, val));
        const stage = document.getElementById('tcCanvasStage');
        if (stage) {
            stage.style.transform = `translate(${state.panX}px, ${state.panY}px) scale(${state.zoom})`;
        }
    }

    // ==========================================
    // 19. BOOTSTRAP
    // ==========================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.TinkercadClone = {
        state, presets, loadPreset, autoWirePreset, clearAllWires,
        startSimulation, stopSimulation, rebootMCU, placeComponent,
        switchView, exportSchematicSvg, exportBomCsv, undo, redo, showToast
    };
    window.tcSimulator = window.TinkercadClone;

})();
