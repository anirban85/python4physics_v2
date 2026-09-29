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

        // Wire Configuration (12 Tinkercad Wire Colors)
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
        circuitStatus: { valid: false, reason: 'no_wires', details: '', netVoltages: {} }
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
        blink: {
            title: "1. LED Blink & Optical Timing",
            components: [
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#ef4444', name: 'LED1' } },
                { id: 'res1', type: 'resistor', x: 788, y: 158, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                { from: 'ard-pin-13', to: 'bb-a18', color: '#f97316', waypoints: [{ x: 179, y: 40 }, { x: 771, y: 40 }] },
                { from: 'bb-a24', to: 'bb-bot-neg-24', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [{ x: 220, y: 345 }] }
            ],
            code: `// ========================================================
// Lab 01: Standard LED Blink & Optical Timing
// Digital Pin 13 & Breadboard Red LED with 220 Ohm Resistor
// ========================================================

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
            title: "2. PWM Breathing & Effective DC Voltage",
            components: [
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#0284c7', name: 'LED1' } },
                { id: 'res1', type: 'resistor', x: 788, y: 158, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                { from: 'ard-pin-9', to: 'bb-a18', color: '#0284c7', waypoints: [{ x: 231, y: 40 }, { x: 771, y: 40 }] },
                { from: 'bb-a24', to: 'bb-bot-neg-24', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [{ x: 220, y: 345 }] }
            ],
            code: `// ========================================================
// Lab 02: Pulse-Width Modulation (PWM) LED Breathing
// Effective DC Voltage: V_eff = 5.0 * (Duty / 255.0)
// ========================================================

const int pwmPin = 9;
int brightness = 0;
int fadeStep = 5;

void setup() {
  pinMode(pwmPin, OUTPUT);
  Serial.begin(9600);
  Serial.println("--- PWM Voltage Modulation Active ---");
}

void loop() {
  analogWrite(pwmPin, brightness);
  float vEff = (brightness / 255.0) * 5.0;
  
  Serial.print("Duty Cycle: ");
  Serial.print(brightness);
  Serial.print(" | V_eff: ");
  Serial.print(vEff, 2);
  Serial.println(" V");
  
  brightness += fadeStep;
  if (brightness <= 0 || brightness >= 255) {
    fadeStep = -fadeStep;
  }
  delay(30);
}`
        },
        potentiometer: {
            title: "3. Potentiometer 10-Bit ADC Voltage Divider",
            components: [
                { id: 'pot1', type: 'potentiometer', x: 627, y: 143, rotation: 0, props: { resistance: 10000, name: 'POT1', position: 0.5 } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'bb-a10', color: '#ef4444', waypoints: [{ x: 208, y: 330 }, { x: 635, y: 330 }] },
                { from: 'ard-pin-gnd1', to: 'bb-a12', color: '#0f172a', waypoints: [{ x: 220, y: 345 }, { x: 669, y: 345 }] },
                { from: 'ard-pin-a0', to: 'bb-a11', color: '#10b981', waypoints: [{ x: 277, y: 260 }, { x: 652, y: 260 }] }
            ],
            code: `// ========================================================
// Lab 03: Potentiometer Voltage Divider (Ohm's Law)
// 10-Bit ADC Resolution: 5.0V / 1024 = 4.88 mV per count
// ========================================================

const int potPin = A0;

void setup() {
  Serial.begin(9600);
  Serial.println("--- 10-Bit ADC Acquisition Ready ---");
}

void loop() {
  int rawADC = analogRead(potPin);
  float voltage = (rawADC / 1023.0) * 5.0;
  
  Serial.print("ADC: ");
  Serial.print(rawADC);
  Serial.print(" | Voltage: ");
  Serial.print(voltage, 3);
  Serial.println(" V");
  
  delay(100);
}`
        },
        ldr_sensor: {
            title: "4. Photoresistor (LDR) Solar Light Sensor",
            components: [
                { id: 'ldr1', type: 'ldr', x: 694.5, y: 151, rotation: 0, props: { name: 'LDR1', lux: 450 } },
                { id: 'res1', type: 'resistor', x: 720, y: 158, rotation: 0, props: { resistance: 10000, unit: 'Ω', name: 'R1' } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'bb-a14', color: '#ef4444', waypoints: [{ x: 208, y: 330 }, { x: 703, y: 330 }] },
                { from: 'ard-pin-a1', to: 'bb-a15', color: '#10b981', waypoints: [{ x: 289, y: 260 }, { x: 720, y: 260 }] },
                { from: 'bb-a20', to: 'bb-bot-neg-20', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [{ x: 220, y: 345 }] }
            ],
            code: `// ========================================================
// Lab 04: LDR Photoelectric Sensor & Solar Insolation
// Semiconductor photo-conductivity increases with lux
// ========================================================

const int ldrPin = A1;

void setup() {
  Serial.begin(9600);
  Serial.println("--- Photometric Telemetry Online ---");
}

void loop() {
  int sensorVal = analogRead(ldrPin);
  float vOut = sensorVal * (5.0 / 1023.0);
  
  Serial.print("LDR ADC: ");
  Serial.print(sensorVal);
  Serial.print(" | V_out: ");
  Serial.print(vOut, 2);
  Serial.println(" V");
  
  delay(200);
}`
        },
        ultrasonic: {
            title: "5. Ultrasonic HC-SR04 Speed of Sound Rangefinder",
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
// Lab 05: Ultrasonic HC-SR04 Speed of Sound & Range
// Velocity of Sound in Air v = 343 m/s = 0.0343 cm/us
// ========================================================

const int trigPin = 9;
const int echoPin = 8;

void setup() {
  pinMode(trigPin, OUTPUT);
  pinMode(echoPin, INPUT);
  Serial.begin(9600);
  Serial.println("--- Ultrasonic Acoustic Telemetry Ready ---");
}

void loop() {
  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);
  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);
  digitalWrite(trigPin, LOW);
  
  long duration = pulseIn(echoPin, HIGH);
  float distanceCm = duration * 0.0343 / 2.0;
  
  Serial.print("Echo Transit: ");
  Serial.print(duration);
  Serial.print(" us | Distance: ");
  Serial.print(distanceCm, 1);
  Serial.println(" cm");
  
  delay(250);
}`
        },
        pir_alarm: {
            title: "6. PIR Motion Detector & Security Alarm",
            components: [
                { id: 'pir1', type: 'pir', x: 654, y: 239, rotation: 0, props: { name: 'PIR1', motion: false } },
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#ef4444', name: 'LED1' } },
                { id: 'res1', type: 'resistor', x: 788, y: 158, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R1' } },
                { id: 'bz1', type: 'buzzer', x: 840, y: 247, rotation: 0, props: { name: 'BZ1', frequency: 1500 } }
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
// Lab 06: PIR Pyroelectric Infrared Motion Security Alarm
// Digital Pin 2 Interrupt/Input with Visual & Audio Alert
// ========================================================

const int pirPin = 2;
const int ledPin = 13;
const int buzzerPin = 11;

void setup() {
  pinMode(pirPin, INPUT);
  pinMode(ledPin, OUTPUT);
  pinMode(buzzerPin, OUTPUT);
  Serial.begin(9600);
  Serial.println("--- Security Perimeter Surveillance Active ---");
}

void loop() {
  int motionDetected = digitalRead(pirPin);
  
  if (motionDetected == HIGH) {
    digitalWrite(ledPin, HIGH);
    tone(buzzerPin, 1800);
    Serial.println(">>> INTRUSION DETECTED! Alarm Active <<<");
  } else {
    digitalWrite(ledPin, LOW);
    noTone(buzzerPin);
    Serial.println("Perimeter Clear - Scanning...");
  }
  
  delay(150);
}`
        },
        tmp36_temp: {
            title: "7. TMP36 Precision Thermometer & Heat Monitor",
            components: [
                { id: 'tmp1', type: 'tmp36', x: 660.5, y: 147, rotation: 0, props: { name: 'TMP1', tempC: 28.5 } },
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#ef4444', name: 'LED1' } },
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
// Lab 07: TMP36 Precision Analog Temperature Sensor
// Transfer Characteristic: V_out = 0.5V + (10 mV / deg C) * T
// ========================================================

const int tempPin = A0;
const int alertLedPin = 13;
const float TEMP_THRESHOLD = 30.0; // deg C threshold

void setup() {
  pinMode(alertLedPin, OUTPUT);
  Serial.begin(9600);
  Serial.println("--- Thermal Transducer Telemetry Active ---");
}

void loop() {
  int rawADC = analogRead(tempPin);
  float voltage = (rawADC / 1023.0) * 5.0;
  float temperatureC = (voltage - 0.5) * 100.0;
  
  Serial.print("Raw ADC: ");
  Serial.print(rawADC);
  Serial.print(" | Vout: ");
  Serial.print(voltage, 3);
  Serial.print(" V | Temp: ");
  Serial.print(temperatureC, 1);
  Serial.println(" C");
  
  if (temperatureC > TEMP_THRESHOLD) {
    digitalWrite(alertLedPin, HIGH);
  } else {
    digitalWrite(alertLedPin, LOW);
  }
  
  delay(200);
}`
        },
        servo_sweep: {
            title: "8. Micro Servo Motor 180° Angle Sweeper",
            components: [
                { id: 'servo1', type: 'servo', x: 671, y: 233, rotation: 0, props: { name: 'SERVO1', angle: 90 } },
                { id: 'pot1', type: 'potentiometer', x: 627, y: 143, rotation: 0, props: { resistance: 10000, name: 'POT1', position: 0.5 } }
            ],
            autoWires: [
                { from: 'ard-pin-5v', to: 'bb-top-pos-10', color: '#ef4444', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-top-neg-10', color: '#0f172a', waypoints: [] },
                { from: 'bb-top-pos-10', to: 'bb-a10', color: '#ef4444', waypoints: [] },
                { from: 'bb-top-neg-12', to: 'bb-a12', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-a0', to: 'bb-a11', color: '#10b981', waypoints: [{ x: 277, y: 260 }, { x: 652, y: 260 }] },
                { from: 'bb-top-neg-13', to: 'bb-f13', color: '#0f172a', waypoints: [] },
                { from: 'bb-top-pos-14', to: 'bb-f14', color: '#ef4444', waypoints: [] },
                { from: 'ard-pin-9', to: 'bb-f15', color: '#f97316', waypoints: [{ x: 231, y: 40 }, { x: 720, y: 40 }] }
            ],
            code: `// ========================================================
// Lab 08: Micro Servo SG90 Angular Position Control
// Servo Horn sweeps 0 to 180 degrees mapped from Potentiometer
// ========================================================

#include <Servo.h>

Servo myServo;
const int potPin = A0;
const int servoPin = 9;

int prevAngle = -1;

void setup() {
  myServo.attach(servoPin);
  Serial.begin(9600);
  Serial.println("--- Micro Servo Kinematic Positioner Online ---");
}

void loop() {
  int potValue = analogRead(potPin);
  int targetAngle = map(potValue, 0, 1023, 0, 180);
  
  if (abs(targetAngle - prevAngle) >= 1) {
    myServo.write(targetAngle);
    prevAngle = targetAngle;
    
    Serial.print("Pot ADC: ");
    Serial.print(potValue);
    Serial.print(" -> Servo Angle: ");
    Serial.print(targetAngle);
    Serial.println(" deg");
  }
  
  delay(40);
}`
        },
        rgb_mixer: {
            title: "9. RGB LED Color Spectrum PWM Mixer",
            components: [
                { id: 'rgb1', type: 'rgb_led', x: 711.5, y: 147, rotation: 0, props: { name: 'RGB1', rVal: 255, gVal: 100, bVal: 50 } },
                { id: 'res1', type: 'resistor', x: 771, y: 80, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R1' } },
                { id: 'res2', type: 'resistor', x: 771, y: 114, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R2' } },
                { id: 'res3', type: 'resistor', x: 771, y: 148, rotation: 0, props: { resistance: 220, unit: 'Ω', name: 'R3' } }
            ],
            autoWires: [
                { from: 'ard-pin-9', to: 'bb-a17', color: '#3b82f6', waypoints: [{ x: 231, y: 40 }, { x: 754, y: 40 }] },
                { from: 'ard-pin-10', to: 'bb-b17', color: '#10b981', waypoints: [{ x: 218, y: 45 }, { x: 754, y: 45 }] },
                { from: 'ard-pin-11', to: 'bb-c15', color: '#ef4444', waypoints: [{ x: 205, y: 50 }, { x: 720, y: 50 }] },
                { from: 'bb-e16', to: 'bb-bot-neg-16', color: '#0f172a', waypoints: [] },
                { from: 'ard-pin-gnd1', to: 'bb-bot-neg-2', color: '#0f172a', waypoints: [{ x: 220, y: 345 }] }
            ],
            code: `// ========================================================
// Lab 09: RGB LED Optical Spectrum Synthesis (PWM Mixing)
// Tri-chromatic additive color space (Pins 11, 10, 9)
// ========================================================

const int redPin = 11;
const int greenPin = 10;
const int bluePin = 9;

void setup() {
  pinMode(redPin, OUTPUT);
  pinMode(greenPin, OUTPUT);
  pinMode(bluePin, OUTPUT);
  Serial.begin(9600);
  Serial.println("--- RGB Trichromatic Synthesis Online ---");
}

void loop() {
  // Cycle through Red, Green, Blue, Cyan, Magenta, Yellow, White
  int colors[7][3] = {
    {255, 0, 0},     // Pure Red
    {0, 255, 0},     // Pure Green
    {0, 0, 255},     // Pure Blue
    {0, 255, 255},   // Cyan
    {255, 0, 255},   // Magenta
    {255, 255, 0},   // Yellow
    {255, 255, 255}  // White
  };
  
  for (int i = 0; i < 7; i++) {
    analogWrite(redPin, colors[i][0]);
    analogWrite(greenPin, colors[i][1]);
    analogWrite(bluePin, colors[i][2]);
    
    Serial.print("Color Phase ");
    Serial.print(i + 1);
    Serial.print(" | R: "); Serial.print(colors[i][0]);
    Serial.print(" G: "); Serial.print(colors[i][1]);
    Serial.print(" B: "); Serial.println(colors[i][2]);
    delay(1000);
  }
}`
        },
        button_toggle: {
            title: "10. Pushbutton Digital Input & Pullup",
            components: [
                { id: 'btn1', type: 'pushbutton', x: 677.5, y: 169, rotation: 0, props: { name: 'BTN1', pressed: false } },
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#10b981', name: 'LED1' } },
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
// Lab 10: Digital Input with Internal Pull-Up Resistor
// Active-LOW Switch Logic with Debounce
// ========================================================

const int buttonPin = 2;
const int ledPin = 13;

int ledState = LOW;
int lastButtonState = HIGH;

void setup() {
  pinMode(buttonPin, INPUT_PULLUP);
  pinMode(ledPin, OUTPUT);
  Serial.begin(9600);
  Serial.println("--- Digital Input Pull-Up Active ---");
}

void loop() {
  int reading = digitalRead(buttonPin);
  
  if (reading == LOW && lastButtonState == HIGH) {
    ledState = !ledState;
    digitalWrite(ledPin, ledState);
    
    Serial.print("Button Pressed! LED State -> ");
    Serial.println(ledState == HIGH ? "ON (Active)" : "OFF (Standby)");
    delay(50); // Simple debounce
  }
  
  lastButtonState = reading;
  delay(20);
}`
        },
        rc_transient: {
            title: "11. RC Transient Charging & Discharging Curve",
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
// Lab 11: RC Circuit Transient Charging & Discharging Curve
// Time Constant tau = R * C = 10,000 Ohm * 100 uF = 1.00 s
// ========================================================

const int chargePin = 10;
const int sensePin = A0;

void setup() {
  pinMode(chargePin, OUTPUT);
  digitalWrite(chargePin, LOW);
  Serial.begin(9600);
  Serial.println("--- RC Transient Dynamic Analysis Initialized ---");
}

void loop() {
  // 1. Charging Phase: V_C(t) = V_0 * (1 - exp(-t / RC))
  Serial.println(">>> STEP: CHARGING PHASE (Pin 10 HIGH) <<<");
  digitalWrite(chargePin, HIGH);
  for (int i = 0; i < 40; i++) {
    int raw = analogRead(sensePin);
    float vCap = (raw / 1023.0) * 5.0;
    Serial.print("Phase: CHARGE | Raw ADC: ");
    Serial.print(raw);
    Serial.print(" | V_C: ");
    Serial.print(vCap, 3);
    Serial.println(" V");
    delay(50);
  }

  // 2. Discharging Phase: V_C(t) = V_0 * exp(-t / RC)
  Serial.println(">>> STEP: DISCHARGING PHASE (Pin 10 LOW) <<<");
  digitalWrite(chargePin, LOW);
  for (int i = 0; i < 40; i++) {
    int raw = analogRead(sensePin);
    float vCap = (raw / 1023.0) * 5.0;
    Serial.print("Phase: DISCHARGE | Raw ADC: ");
    Serial.print(raw);
    Serial.print(" | V_C: ");
    Serial.print(vCap, 3);
    Serial.println(" V");
    delay(50);
  }
}`
        },
        photogate: {
            title: "12. Simple Pendulum Optical Photogate ('g' Measurement)",
            components: [
                { id: 'btn1', type: 'pushbutton', x: 677.5, y: 169, rotation: 0, props: { name: 'BEAM_TRIG', pressed: false } },
                { id: 'led1', type: 'led', x: 762.5, y: 147, rotation: 0, props: { color: '#10b981', name: 'BEAM_LED' } },
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
// Lab 12: Simple Pendulum Optical Photogate Timing ('g' Measurement)
// Time Period T = 2 * pi * sqrt(L / g) => g = 4 * pi^2 * L / T^2
// Connect Photogate sensor to Digital Pin 2 (Hardware INT0)
// ========================================================

const byte photogatePin = 2;
const byte indicatorLed = 13;
const float PENDULUM_LENGTH_M = 0.50; // Pendulum Length L = 50 cm

volatile unsigned long t1 = 0;
volatile unsigned long t2 = 0;
volatile byte count = 0;

void setup() {
  pinMode(photogatePin, INPUT_PULLUP);
  pinMode(indicatorLed, OUTPUT);
  Serial.begin(9600);
  Serial.println("--- Optical Photogate Timing System Active ---");
  Serial.println("Click BEAM_TRIG on breadboard to simulate pendulum beam cuts.");
}

void loop() {
  int beamState = digitalRead(photogatePin);
  
  if (beamState == LOW) {
    digitalWrite(indicatorLed, HIGH);
    unsigned long now = millis();
    count++;
    if (count == 1) {
      t1 = now;
      Serial.println(">> Beam Cut 1 registered: Timing started <<");
    } else if (count == 2) {
      t2 = now;
      count = 0;
      float periodSec = (t2 - t1) / 1000.0;
      if (periodSec > 0.1) {
        float g = (4.0 * 3.14159265 * 3.14159265 * PENDULUM_LENGTH_M) / (periodSec * periodSec);
        Serial.print("Oscillation Period T: ");
        Serial.print(periodSec, 3);
        Serial.print(" s | Acceleration 'g': ");
        Serial.print(g, 2);
        Serial.println(" m/s^2");
      }
    }
    delay(200); // debounce beam cut
  } else {
    digitalWrite(indicatorLed, LOW);
  }
  delay(20);
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

    function registerTerminal(id, name, x, y) {
        terminals[id] = { id, name, x, y };

        const container = document.getElementById('tcTerminalsContainer');
        if (!container) return;

        let el = document.getElementById(`term-${id}`);
        if (!el) {
            el = document.createElement('div');
            el.id = `term-${id}`;
            el.className = 'tc-terminal tc-terminal-pin';
            el.setAttribute('data-terminal-id', id);

            const tooltip = document.createElement('div');
            tooltip.className = 'tc-pin-tooltip';
            tooltip.textContent = name;
            el.appendChild(tooltip);

            container.appendChild(el);
        }

        el.style.left = `${x}px`;
        el.style.top = `${y}px`;

        el.onmouseenter = () => onTerminalHover(id);
        el.onmouseleave = () => onTerminalLeave(id);
        el.onclick = (e) => {
            e.stopPropagation();
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
        if (!show || !terminalId.startsWith('bb-')) return;

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

    function onTerminalClick(terminalId) {
        if (!state.drawingWire) {
            // Start drawing wire
            const startTerm = terminals[terminalId];
            if (!startTerm) return;

            state.drawingWire = {
                from: terminalId,
                color: state.selectedWireColor,
                waypoints: []
            };

            const rubber = document.getElementById('tcRubberbandWire');
            if (rubber) {
                rubber.style.display = 'block';
                rubber.setAttribute('stroke', state.selectedWireColor);
            }
        } else {
            // Complete drawing wire
            if (state.drawingWire.from === terminalId) {
                cancelWireDrawing();
                return;
            }

            const newWire = {
                id: `wire_${Date.now()}`,
                from: state.drawingWire.from,
                to: terminalId,
                color: state.drawingWire.color,
                waypoints: [...state.drawingWire.waypoints]
            };

            state.wires.push(newWire);
            pushUndo({
                type: 'addWire',
                undo: () => { state.wires = state.wires.filter(w => w.id !== newWire.id); renderWires(); triggerCircuitSolve(); },
                redo: () => { state.wires.push(newWire); renderWires(); triggerCircuitSolve(); }
            });

            cancelWireDrawing();
            renderWires();
            triggerCircuitSolve();
            updateStatusBar();
        }
    }

    function cancelWireDrawing() {
        state.drawingWire = null;
        const rubber = document.getElementById('tcRubberbandWire');
        if (rubber) rubber.style.display = 'none';
    }

    function handleCanvasClick(e) {
        if (!state.drawingWire) {
            deselectAll();
            return;
        }

        const rect = document.getElementById('tcCanvasStage').getBoundingClientRect();
        const stageX = (e.clientX - rect.left) / state.zoom;
        const stageY = (e.clientY - rect.top) / state.zoom;

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
        updateRubberband(stageX, stageY);
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
        let d = `M ${points[0].x} ${points[0].y}`;
        const radius = 6;

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
        el.innerHTML = getComponentSVG(comp);

        el.addEventListener('click', (e) => {
            e.stopPropagation();
            selectComponent(comp.id);
        });

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
            if (e.target.id === `${comp.id}_cap` || e.target.id === `${comp.id}_dial` || e.target.id === `${comp.id}_pir_btn`) return;

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

                // Highlight snapping candidates
                highlightSnapCandidates(comp, comp.x, comp.y);

                registerComponentTerminals(comp);
                renderWires();
            }

            function onMouseUp() {
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
                    }

                    document.querySelectorAll('.tc-snap-hole-halo').forEach(h => h.remove());

                    const newX = comp.x, newY = comp.y;
                    pushUndo({
                        type: 'moveComponent',
                        undo: () => { comp.x = origX; comp.y = origY; el.style.left = `${origX}px`; el.style.top = `${origY}px`; registerComponentTerminals(comp); renderWires(); triggerCircuitSolve(); },
                        redo: () => { comp.x = newX; comp.y = newY; el.style.left = `${newX}px`; el.style.top = `${newY}px`; registerComponentTerminals(comp); renderWires(); triggerCircuitSolve(); }
                    });

                    registerComponentTerminals(comp);
                    renderWires();
                    triggerCircuitSolve();
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
    // 11. DRAG & DROP PALETTE & CATEGORIES
    // ==========================================
    function bindDragAndDrop() {
        document.querySelectorAll('.tc-component-card').forEach(card => {
            card.addEventListener('dragstart', (e) => {
                const type = card.getAttribute('data-component-type');
                if (type === 'arduino' || type === 'breadboard' || !COMPONENT_LIBRARY[type]) {
                    e.preventDefault();
                    return;
                }
                state.dragType = type;
                e.dataTransfer.setData('text/plain', type);
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
    }

    // ==========================================
    // 12. COMPONENT INSPECTOR POPOVER
    // ==========================================
    function selectComponent(id) {
        deselectAll();
        state.selectedItem = { type: 'component', id };
        const el = document.getElementById(id);
        if (el) el.classList.add('selected');
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
        document.querySelectorAll('.tc-placed-component').forEach(el => el.classList.remove('selected'));
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

        pushUndo({
            type: 'rotateComponent',
            undo: () => { comp.rotation = origRot; if (el) el.style.transform = `rotate(${origRot}deg)`; registerComponentTerminals(comp); renderWires(); triggerCircuitSolve(); },
            redo: () => { comp.rotation = (origRot + 90) % 360; if (el) el.style.transform = `rotate(${comp.rotation}deg)`; registerComponentTerminals(comp); renderWires(); triggerCircuitSolve(); }
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
        renderWires();
    }

    function showComponentInspector(compId) {
        const comp = state.components.find(c => c.id === compId);
        const pop = document.getElementById('tcComponentInspector');
        const fields = document.getElementById('tcInspectorFields');
        const title = document.getElementById('tcInspectorName');
        if (!comp || !pop || !fields || !title) return;

        const lib = COMPONENT_LIBRARY[comp.type];
        title.textContent = `${lib ? lib.name : comp.type} — ${comp.props.name}`;

        const inspectX = Math.max(30, Math.min(comp.x - 70, 750));
        const inspectY = Math.max(20, Math.min(comp.y - 130, 260));

        pop.style.left = `${inspectX}px`;
        pop.style.top = `${inspectY}px`;
        pop.style.display = 'block';

        let html = '';
        if (comp.type === 'led') {
            html = `<div class="tc-inspector-row"><span>Color:</span>
                <select id="tcLedColorSelect" class="tc-inspector-input" style="width:95px;">
                    <option value="#ef4444" ${comp.props.color === '#ef4444' ? 'selected' : ''}>Red</option>
                    <option value="#10b981" ${comp.props.color === '#10b981' ? 'selected' : ''}>Green</option>
                    <option value="#eab308" ${comp.props.color === '#eab308' ? 'selected' : ''}>Yellow</option>
                    <option value="#0284c7" ${comp.props.color === '#0284c7' ? 'selected' : ''}>Blue</option>
                    <option value="#f97316" ${comp.props.color === '#f97316' ? 'selected' : ''}>Orange</option>
                    <option value="#ffffff" ${comp.props.color === '#ffffff' ? 'selected' : ''}>White</option>
                </select></div>
                <div class="tc-inspector-row"><span>Fwd V:</span><span>${comp.props.forwardVoltage || 2.0} V</span></div>`;
        } else if (comp.type === 'resistor') {
            html = `<div class="tc-inspector-row"><span>Resistance:</span>
                <input type="number" id="tcResVal" class="tc-inspector-input" value="${comp.props.resistance}" min="1" max="10000000">
                <select id="tcResUnit" class="tc-inspector-input" style="width:50px;">
                    <option value="1" ${comp.props.unit === 'Ω' ? 'selected' : ''}>Ω</option>
                    <option value="1000" ${comp.props.unit === 'kΩ' ? 'selected' : ''}>kΩ</option>
                    <option value="1000000" ${comp.props.unit === 'MΩ' ? 'selected' : ''}>MΩ</option>
                </select></div>`;
        } else if (comp.type === 'potentiometer') {
            html = `<div class="tc-inspector-row"><span>Total R:</span><span>${comp.props.resistance || 10000} Ω</span></div>
                <div class="tc-inspector-row"><span>Wiper Pos:</span>
                <input type="range" id="tcPotSlider" min="0" max="100" value="${Math.round((comp.props.position || 0.5) * 100)}" style="width:90px;">
                <span id="tcPotValText">${Math.round((comp.props.position || 0.5) * 100)}%</span></div>`;
        } else if (comp.type === 'ldr') {
            html = `<div class="tc-inspector-row"><span>Ambient Lux:</span>
                <input type="range" id="tcLdrSlider" min="0" max="1000" value="${comp.props.lux || 400}" style="width:90px;">
                <span id="tcLdrValText">${comp.props.lux || 400} lx</span></div>`;
        } else if (comp.type === 'ultrasonic') {
            html = `<div class="tc-inspector-row"><span>Obstacle:</span>
                <input type="range" id="tcUsSlider" min="2" max="400" value="${comp.props.distance || 25}" style="width:90px;">
                <span id="tcUsValText">${comp.props.distance || 25} cm</span></div>`;
        } else if (comp.type === 'tmp36') {
            html = `<div class="tc-inspector-row"><span>Temperature:</span>
                <input type="range" id="tcTmpSlider" min="-40" max="125" value="${Math.round(comp.props.tempC || 25)}" style="width:90px;">
                <span id="tcTmpValText">${(comp.props.tempC || 25).toFixed(1)}°C</span></div>`;
        } else if (comp.type === 'servo') {
            html = `<div class="tc-inspector-row"><span>Angle:</span>
                <input type="range" id="tcServoSlider" min="0" max="180" value="${comp.props.angle || 90}" style="width:90px;">
                <span id="tcServoValText">${comp.props.angle || 90}°</span></div>`;
        } else if (comp.type === 'pushbutton') {
            html = `<div class="tc-inspector-row"><span>Contact State:</span>
                <button type="button" id="tcBtnToggle" class="btn-tc-code-toggle" style="padding:2px 8px;font-size:0.75rem;">
                    ${comp.props.pressed ? 'Pressed (Closed)' : 'Normal (Open)'}
                </button></div>`;
        } else if (comp.type === 'slide_switch') {
            html = `<div class="tc-inspector-row"><span>Switch Pos:</span>
                <button type="button" id="tcSwitchToggle" class="btn-tc-code-toggle" style="padding:2px 8px;font-size:0.75rem;">
                    ${comp.props.state === 'left' ? 'Left Position' : 'Right Position'}
                </button></div>`;
        } else if (comp.type === 'capacitor') {
            html = `<div class="tc-inspector-row"><span>Capacitance:</span>
                <input type="number" id="tcCapVal" class="tc-inspector-input" value="${comp.props.capacitance}" min="1">
                <span>${comp.props.unit}</span></div>`;
        } else {
            html = `<div style="color:var(--tc-text-muted);font-size:0.75rem;">Interactive physics lab component</div>`;
        }

        fields.innerHTML = html;

        document.getElementById('tcLedColorSelect')?.addEventListener('change', function () {
            comp.props.color = this.value;
            renderComponentDOM(comp);
            triggerCircuitSolve();
        });

        document.getElementById('tcResVal')?.addEventListener('input', function () {
            comp.props.resistance = parseFloat(this.value) || 220;
            renderComponentDOM(comp);
            triggerCircuitSolve();
        });

        document.getElementById('tcPotSlider')?.addEventListener('input', function () {
            setPotPosition(comp, parseInt(this.value) / 100);
            const valText = document.getElementById('tcPotValText');
            if (valText) valText.textContent = `${this.value}%`;
        });

        document.getElementById('tcLdrSlider')?.addEventListener('input', function () {
            comp.props.lux = parseInt(this.value);
            state.hardwareValues.ldrLux = comp.props.lux;
            const valText = document.getElementById('tcLdrValText');
            if (valText) valText.textContent = `${comp.props.lux} lx`;
            triggerCircuitSolve();
        });

        document.getElementById('tcUsSlider')?.addEventListener('input', function () {
            comp.props.distance = parseFloat(this.value);
            state.hardwareValues.ultrasonicCm = comp.props.distance;
            const valText = document.getElementById('tcUsValText');
            if (valText) valText.textContent = `${comp.props.distance} cm`;
            triggerCircuitSolve();
        });

        document.getElementById('tcTmpSlider')?.addEventListener('input', function () {
            comp.props.tempC = parseFloat(this.value);
            state.hardwareValues.tmp36Temp = comp.props.tempC;
            const valText = document.getElementById('tcTmpValText');
            if (valText) valText.textContent = `${comp.props.tempC.toFixed(1)}°C`;
            renderComponentDOM(comp);
            triggerCircuitSolve();
        });

        document.getElementById('tcServoSlider')?.addEventListener('input', function () {
            comp.props.angle = parseInt(this.value);
            const horn = document.getElementById(`${comp.id}_horn`);
            if (horn) horn.style.transform = `rotate(${comp.props.angle}deg)`;
            const badge = document.querySelector(`#${comp.id} .tc-servo-angle-badge`);
            if (badge) badge.textContent = `${comp.props.angle}°`;
            const valText = document.getElementById('tcServoValText');
            if (valText) valText.textContent = `${comp.props.angle}°`;
        });

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
            netVoltages
        };

        updateCircuitWarning(warningText, validStatus);
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

            // Arrays
            l = l.replace(/\b(?:const\s+)?(?:int|float|double|long|unsigned\s+long|unsigned\s+int|short|byte|char|bool|boolean|String)\s+([a-zA-Z0-9_]+)\s*\[\s*\d*\s*\]\s*=\s*\{([^}]*)\}/g, 'let $1 = [$2]');
            l = l.replace(/\b(?:int|float|double|long|unsigned\s+long|unsigned\s+int|short|byte|char|bool|boolean)\s+([a-zA-Z0-9_]+)\s*\[\s*(\d+)\s*\]/g, 'let $1 = new Array($2).fill(0)');

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
                    return parts[parts.length - 1];
                }).filter(Boolean).join(', ');

                out.push(`${indent}async function ${name}(${cleanedParams}) ${brace}`);
                continue;
            }

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

            // Serial
            l = l.replace(/\bSerial\.begin\s*\(/g, '__env.Serial.begin(');
            l = l.replace(/\bSerial\.println\s*\(/g, '__env.Serial.println(');
            l = l.replace(/\bSerial\.print\s*\(/g, '__env.Serial.print(');
            l = l.replace(/\bSerial\.available\s*\(\s*\)/g, '__env.Serial.available()');
            l = l.replace(/\bSerial\.read\s*\(\s*\)/g, '__env.Serial.read()');

            // Constants
            l = l.replace(/\bHIGH\b/g, '__env.HIGH');
            l = l.replace(/\bLOW\b/g, '__env.LOW');
            l = l.replace(/\bOUTPUT\b/g, '__env.OUTPUT');
            l = l.replace(/\bINPUT_PULLUP\b/g, '__env.INPUT_PULLUP');
            l = l.replace(/\bINPUT\b/g, '__env.INPUT');
            l = l.replace(/\bLED_BUILTIN\b/g, '__env.LED_BUILTIN');
            l = l.replace(/\bA0\b/g, '__env.A0');
            l = l.replace(/\bA1\b/g, '__env.A1');
            l = l.replace(/\bA2\b/g, '__env.A2');
            l = l.replace(/\bA3\b/g, '__env.A3');
            l = l.replace(/\bA4\b/g, '__env.A4');
            l = l.replace(/\bA5\b/g, '__env.A5');

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
                available: () => 0,
                read: () => -1
            },
            isRunning: () => state.isSimulating && !state.firmwareCancelToken.cancelled
        };

        state.simInterval = setInterval(() => {
            state.simTick++;
            updateSimTimer();
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
        state.isSimulating = false;
        state.firmwareCancelToken.cancelled = true;

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
            return precision !== undefined ? msg.toFixed(precision) : msg.toString();
        }
        return String(msg);
    }

    function extractPlotterValue(text) {
        const match = text.match(/(?:[-+]?\d*\.?\d+(?:[eE][-+]?\d+)?)/g);
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

        if (state.plotterData.length < 2) return;

        const minVal = Math.min(...state.plotterData, 0);
        const maxVal = Math.max(...state.plotterData, 5.0);
        const range = (maxVal - minVal) || 1;

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
    // 16. SCHEMATIC & BOM GENERATORS
    // ==========================================
    function switchView(viewName) {
        state.currentView = viewName;
        const canvasContainer = document.getElementById('tcCanvasContainer');
        const schematicContainer = document.getElementById('tcSchematicContainer');
        const bomContainer = document.getElementById('tcBomContainer');

        document.getElementById('tcTabCircuits')?.classList.toggle('active', viewName === 'circuits');
        document.getElementById('tcTabSchematic')?.classList.toggle('active', viewName === 'schematic');
        document.getElementById('tcTabBom')?.classList.toggle('active', viewName === 'bom');

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
        if (!svg) return;
        const serializer = new XMLSerializer();
        const src = serializer.serializeToString(svg);
        const blob = new Blob([src], { type: 'image/svg+xml;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `arduino_schematic_${state.currentPreset}.svg`;
        a.click();
        URL.revokeObjectURL(url);
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

        document.getElementById('tcVerifyCodeBtn')?.addEventListener('click', () => {
            const code = codeEditor ? codeEditor.getValue() : (document.getElementById('tcCodeTextarea')?.value || '');
            try {
                const transpiled = transpileArduino(code);
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

        document.querySelectorAll('.tc-component-card').forEach(card => {
            card.addEventListener('click', function () {
                if (state.dragType) return;
                const type = this.getAttribute('data-component-type');
                if (type === 'arduino' || type === 'breadboard' || !COMPONENT_LIBRARY[type]) return;
                const comp = placeComponent(type, 650 + Math.random() * 40, 160 + Math.random() * 40);
                if (comp) {
                    selectComponent(comp.id);
                    triggerCircuitSolve();
                }
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

        const container = document.getElementById('tcCanvasContainer');
        if (container) {
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

        const projTitle = presets[state.currentPreset]?.title || state.currentPreset;

        bar.innerHTML = `
            <div class="tc-status-item"><i class="fa-solid fa-diagram-project" style="color:#38bdf8;"></i> Project: <strong>${projTitle}</strong></div>
            <div class="tc-status-item"><i class="fa-solid fa-microchip" style="color:#0284c7;"></i> Components: ${compCount}</div>
            <div class="tc-status-item"><i class="fa-solid fa-plug" style="color:#10b981;"></i> Wires: ${wireCount}</div>
            <div class="tc-status-item"><i class="fa-solid fa-hand-pointer" style="color:#f59e0b;"></i> Selected: ${selText}</div>
            <div class="tc-status-item"><i class="fa-solid fa-magnifying-glass" style="color:#64748b;"></i> ${Math.round(state.zoom * 100)}%</div>
        `;
    }

    function setZoom(val) {
        state.zoom = Math.max(0.5, Math.min(2.0, val));
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
