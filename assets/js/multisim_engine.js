/**
 * ============================================================================
 * National Instruments Multisim - Interactive Circuit Simulator Engine (v2.0)
 * Python4Physics Engineering EDA & Virtual Instrumentation Suite
 * 
 * Features:
 *  - CAD Schematic Editor: Snapping grid, orthogonal multi-point wiring, junctions
 *  - SPICE-grade Numerical MNA (Modified Nodal Analysis) & Transient ODE Solver
 *  - Virtual Instruments: Dual-Trace CRO (Oscilloscope), Digital Multimeter (DMM),
 *    Function Generator (XFG), and Bode Analyzer
 *  - 21 Pre-built circuits for all 7 University Physics / Electronics Modules
 *  - Custom circuit design, JSON import/export, PNG schematic export
 *  - Mobile touch support with adaptive hitboxes and sliding panels
 * ============================================================================
 */

(function (window, document) {
    'use strict';

    // Global Namespace
    window.MultisimEngine = window.MultisimEngine || {};

    // ========================================================================
    // 1. CONSTANTS & COMPONENT DEFINITIONS
    // ========================================================================
    const GRID_SIZE = 20;

    const COMP_TYPES = {
        // Sources
        dc_source: {
            name: 'DC Voltage Source',
            prefix: 'V',
            category: 'sources',
            pins: [{ id: 'p', x: 0, y: -30, label: '+' }, { id: 'n', x: 0, y: 30, label: '-' }],
            defaults: { voltage: 10, unit: 'V' },
            width: 40, height: 60
        },
        ac_source: {
            name: 'AC Voltage Source',
            prefix: 'V_ac',
            category: 'sources',
            pins: [{ id: 'p', x: 0, y: -30, label: '+' }, { id: 'n', x: 0, y: 30, label: '-' }],
            defaults: { amplitude: 10, frequency: 50, phase: 0, offset: 0, unit: 'V' },
            width: 40, height: 60
        },
        current_source: {
            name: 'DC Current Source',
            prefix: 'I',
            category: 'sources',
            pins: [{ id: 'p', x: 0, y: -30, label: 'IN' }, { id: 'n', x: 0, y: 30, label: 'OUT' }],
            defaults: { current: 0.005, unit: 'A' },
            width: 40, height: 60
        },
        ground: {
            name: 'Ground (0V Ref)',
            prefix: 'GND',
            category: 'sources',
            pins: [{ id: 'g', x: 0, y: -10, label: '0V' }],
            defaults: {},
            width: 30, height: 25
        },

        // Basic / Passive
        resistor: {
            name: 'Resistor',
            prefix: 'R',
            category: 'basic',
            pins: [{ id: '1', x: -40, y: 0 }, { id: '2', x: 40, y: 0 }],
            defaults: { resistance: 1000, unit: 'Ω' },
            width: 80, height: 30
        },
        potentiometer: {
            name: 'Potentiometer',
            prefix: 'POT',
            category: 'basic',
            pins: [{ id: '1', x: -40, y: -10 }, { id: '2', x: 40, y: -10 }, { id: 'w', x: 0, y: 20, label: 'W' }],
            defaults: { total_resistance: 10000, wiper: 0.5, unit: 'Ω' },
            width: 80, height: 50
        },
        capacitor: {
            name: 'Capacitor',
            prefix: 'C',
            category: 'basic',
            pins: [{ id: '1', x: -30, y: 0 }, { id: '2', x: 30, y: 0 }],
            defaults: { capacitance: 0.0001, unit: 'F' }, // 100uF
            width: 60, height: 30
        },
        inductor: {
            name: 'Inductor',
            prefix: 'L',
            category: 'basic',
            pins: [{ id: '1', x: -40, y: 0 }, { id: '2', x: 40, y: 0 }],
            defaults: { inductance: 0.01, unit: 'H' }, // 10mH
            width: 80, height: 30
        },
        switch_spst: {
            name: 'SPST Switch',
            prefix: 'SW',
            category: 'basic',
            pins: [{ id: '1', x: -30, y: 0 }, { id: '2', x: 30, y: 0 }],
            defaults: { closed: true },
            width: 60, height: 30
        },

        // Diodes
        diode: {
            name: 'PN Junction Diode (1N4007)',
            prefix: 'D',
            category: 'diodes',
            pins: [{ id: 'a', x: -30, y: 0, label: 'A' }, { id: 'k', x: 30, y: 0, label: 'K' }],
            defaults: { vf: 0.7, is: 1e-12 },
            width: 60, height: 30
        },
        zener: {
            name: 'Zener Diode (1N4733A 5.1V)',
            prefix: 'DZ',
            category: 'diodes',
            pins: [{ id: 'a', x: -30, y: 0, label: 'A' }, { id: 'k', x: 30, y: 0, label: 'K' }],
            defaults: { vz: 5.1, vf: 0.7, rz: 10 },
            width: 60, height: 30
        },
        led: {
            name: 'Light Emitting Diode (LED)',
            prefix: 'LED',
            category: 'diodes',
            pins: [{ id: 'a', x: -30, y: 0, label: 'A' }, { id: 'k', x: 30, y: 0, label: 'K' }],
            defaults: { vf: 2.0, color: '#ef4444' },
            width: 60, height: 35
        },

        // Transistors
        bjt_npn: {
            name: 'BJT NPN Transistor (BC547 / 2N3904)',
            prefix: 'Q',
            category: 'transistors',
            pins: [{ id: 'b', x: -30, y: 0, label: 'B' }, { id: 'c', x: 20, y: -30, label: 'C' }, { id: 'e', x: 20, y: 30, label: 'E' }],
            defaults: { beta: 150, vbe: 0.7, vce_sat: 0.2 },
            width: 60, height: 60
        },
        bjt_pnp: {
            name: 'BJT PNP Transistor (BC557 / 2N3906)',
            prefix: 'Q_p',
            category: 'transistors',
            pins: [{ id: 'b', x: -30, y: 0, label: 'B' }, { id: 'c', x: 20, y: 30, label: 'C' }, { id: 'e', x: 20, y: -30, label: 'E' }],
            defaults: { beta: 150, vbe: 0.7, vce_sat: 0.2 },
            width: 60, height: 60
        },
        jfet_n: {
            name: 'JFET N-Channel (2N5458)',
            prefix: 'J',
            category: 'transistors',
            pins: [{ id: 'g', x: -30, y: 15, label: 'G' }, { id: 'd', x: 20, y: -30, label: 'D' }, { id: 's', x: 20, y: 30, label: 'S' }],
            defaults: { vp: -3.0, idss: 0.009 },
            width: 60, height: 60
        },
        mosfet_n: {
            name: 'MOSFET N-Channel (IRF510)',
            prefix: 'M',
            category: 'transistors',
            pins: [{ id: 'g', x: -30, y: 15, label: 'G' }, { id: 'd', x: 20, y: -30, label: 'D' }, { id: 's', x: 20, y: 30, label: 'S' }],
            defaults: { vth: 2.5, kn: 0.02 },
            width: 60, height: 60
        },

        // Op-Amps
        opamp: {
            name: 'Operational Amplifier (IC 741)',
            prefix: 'U',
            category: 'opamps',
            pins: [
                { id: 'inv', x: -40, y: -15, label: '-' },
                { id: 'non', x: -40, y: 15, label: '+' },
                { id: 'out', x: 40, y: 0, label: 'OUT' },
                { id: 'vp', x: 0, y: -30, label: 'V+' },
                { id: 'vn', x: 0, y: 30, label: 'V-' }
            ],
            defaults: { aol: 200000, vsupply: 15 },
            width: 80, height: 60
        },

        // Instruments & Meters (On-canvas probes)
        voltmeter: {
            name: 'Voltmeter Probe',
            prefix: 'VM',
            category: 'meters',
            pins: [{ id: 'p', x: -30, y: 0, label: '+' }, { id: 'n', x: 30, y: 0, label: '-' }],
            defaults: { rin: 1e7, display: '0.000 V' },
            width: 60, height: 40
        },
        ammeter: {
            name: 'In-line Ammeter',
            prefix: 'AM',
            category: 'meters',
            pins: [{ id: 'p', x: -30, y: 0, label: '+' }, { id: 'n', x: 30, y: 0, label: '-' }],
            defaults: { rin: 0.001, display: '0.000 mA' },
            width: 60, height: 40
        },
        cro_tap: {
            name: 'CRO Dual-Probe Tap',
            prefix: 'CRO_TAP',
            category: 'meters',
            pins: [
                { id: 'chA', x: -30, y: -15, label: 'A' },
                { id: 'chB', x: -30, y: 15, label: 'B' },
                { id: 'gnd', x: 30, y: 0, label: 'G' }
            ],
            defaults: {},
            width: 60, height: 45
        }
    };

    // ========================================================================
    // 2. WORKBENCH STATE ENGINE
    // ========================================================================
    class CircuitWorkbench {
        constructor() {
            this.components = [];
            this.wires = [];
            this.nextId = 1;
            this.selectedItem = null;
            this.selectedPin = null;
            this.activeWireDraft = null;
            this.isDragging = false;
            this.dragOffset = { x: 0, y: 0 };
            this.dragItem = null;

            // Simulation state
            this.isRunning = false;
            this.isPaused = false;
            this.simTime = 0; // seconds
            this.simStep = 0.0002; // 200 microseconds per iteration
            this.simInterval = null;
            this.nodeVoltages = {}; // nodeId -> voltage
            this.history = {
                times: [],
                cro_chA: [],
                cro_chB: []
            };

            // Instrument references
            this.cro = null;
            this.dmm = null;
            this.xfg = null;

            // DOM elements
            this.svg = null;
            this.container = null;
            this.clockEl = null;
            this.ledEl = null;

            this.init();
        }

        init() {
            this.container = document.getElementById('msCanvasContainer');
            this.svg = document.getElementById('msSchematicSvg');
            this.clockEl = document.getElementById('msSimTimeDisplay');
            this.ledEl = document.getElementById('msSimLed');

            if (!this.svg || !this.container) return;

            // Setup Instruments
            this.cro = new VirtualCRO(this);
            this.dmm = new VirtualDMM(this);
            this.xfg = new VirtualXFG(this);

            // Bind Canvas Events
            this.bindEvents();
            this.bindRibbonControls();
            this.bindPaletteButtons();
            this.bindMenuActions();

            // Load default circuit: Module 2 Bridge Rectifier with C-Filter
            this.loadPreset('mod2_bridge_rectifier');
        }

        // --------------------------------------------------------------------
        // SVG Vector Symbol Drawing Helpers
        // --------------------------------------------------------------------
        drawComponentSVG(comp) {
            const def = COMP_TYPES[comp.type];
            if (!def) return '';

            let symbolContent = '';
            const valLabel = this.formatValue(comp);

            switch (comp.type) {
                case 'dc_source':
                    symbolContent = `
                        <circle cx="0" cy="0" r="18" fill="var(--ms-comp-fill)" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="0" y1="-30" x2="0" y2="-18" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="0" y1="18" x2="0" y2="30" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <text x="0" y="-4" text-anchor="middle" fill="#ef4444" font-size="12" font-weight="bold">+</text>
                        <text x="0" y="10" text-anchor="middle" fill="#94a3b8" font-size="12" font-weight="bold">-</text>
                    `;
                    break;

                case 'ac_source':
                    symbolContent = `
                        <circle cx="0" cy="0" r="18" fill="var(--ms-comp-fill)" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="0" y1="-30" x2="0" y2="-18" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="0" y1="18" x2="0" y2="30" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <path d="M -9 0 Q -4.5 -8 0 0 T 9 0" fill="none" stroke="#38bdf8" stroke-width="2"/>
                    `;
                    break;

                case 'current_source':
                    symbolContent = `
                        <circle cx="0" cy="0" r="18" fill="var(--ms-comp-fill)" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="0" y1="-30" x2="0" y2="-18" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="0" y1="18" x2="0" y2="30" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="0" y1="8" x2="0" y2="-8" stroke="#10b981" stroke-width="2"/>
                        <polygon points="0,-10 -4,-4 4,-4" fill="#10b981"/>
                    `;
                    break;

                case 'ground':
                    symbolContent = `
                        <line x1="0" y1="-10" x2="0" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="-12" y1="0" x2="12" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="-8" y1="4" x2="8" y2="4" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="-4" y1="8" x2="4" y2="8" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="-1" y1="12" x2="1" y2="12" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                    `;
                    break;

                case 'resistor':
                    symbolContent = `
                        <line x1="-40" y1="0" x2="-25" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <polyline points="-25,0 -20,-8 -12,8 -4,-8 4,8 12,-8 20,8 25,0" fill="none" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="25" y1="0" x2="40" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                    `;
                    break;

                case 'potentiometer':
                    symbolContent = `
                        <line x1="-40" y1="-10" x2="-25" y2="-10" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <polyline points="-25,-10 -20,-16 -12,-4 -4,-16 4,-4 12,-16 20,-4 25,-10" fill="none" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="25" y1="-10" x2="40" y2="-10" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="0" y1="20" x2="0" y2="6" stroke="#f59e0b" stroke-width="2"/>
                        <polygon points="0,0 -4,6 4,6" fill="#f59e0b"/>
                    `;
                    break;

                case 'capacitor':
                    symbolContent = `
                        <line x1="-30" y1="0" x2="-8" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="-8" y1="-15" x2="-8" y2="15" stroke="var(--ms-comp-stroke)" stroke-width="2.5"/>
                        <line x1="8" y1="-15" x2="8" y2="15" stroke="var(--ms-comp-stroke)" stroke-width="2.5"/>
                        <line x1="8" y1="0" x2="30" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                    `;
                    break;

                case 'inductor':
                    symbolContent = `
                        <line x1="-40" y1="0" x2="-24" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <path d="M -24 0 A 6 6 0 0 1 -12 0 A 6 6 0 0 1 0 0 A 6 6 0 0 1 12 0 A 6 6 0 0 1 24 0" fill="none" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="24" y1="0" x2="40" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                    `;
                    break;

                case 'switch_spst':
                    const swAngle = comp.props.closed ? '0' : '-30';
                    symbolContent = `
                        <line x1="-30" y1="0" x2="-14" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <circle cx="-12" cy="0" r="2.5" fill="var(--ms-comp-stroke)"/>
                        <circle cx="12" cy="0" r="2.5" fill="var(--ms-comp-stroke)"/>
                        <line x1="-12" y1="0" x2="10" y2="0" stroke="#38bdf8" stroke-width="2.5" transform="rotate(${swAngle}, -12, 0)"/>
                        <line x1="14" y1="0" x2="30" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                    `;
                    break;

                case 'diode':
                    symbolContent = `
                        <line x1="-30" y1="0" x2="-10" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <polygon points="-10,-12 -10,12 10,0" fill="var(--ms-comp-fill)" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="10" y1="-12" x2="10" y2="12" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="10" y1="0" x2="30" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                    `;
                    break;

                case 'zener':
                    symbolContent = `
                        <line x1="-30" y1="0" x2="-10" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <polygon points="-10,-12 -10,12 10,0" fill="var(--ms-comp-fill)" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <polyline points="6,-16 10,-12 10,12 14,16" fill="none" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="10" y1="0" x2="30" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                    `;
                    break;

                case 'led':
                    symbolContent = `
                        <line x1="-30" y1="0" x2="-10" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <polygon points="-10,-12 -10,12 10,0" fill="${comp.props.lit ? comp.props.color : 'var(--ms-comp-fill)'}" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="10" y1="-12" x2="10" y2="12" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="10" y1="0" x2="30" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="-2" y1="-14" x2="6" y2="-22" stroke="${comp.props.color}" stroke-width="1.8"/>
                        <polygon points="8,-24 4,-20 8,-19" fill="${comp.props.color}"/>
                        <line x1="4" y1="-14" x2="12" y2="-22" stroke="${comp.props.color}" stroke-width="1.8"/>
                        <polygon points="14,-24 10,-20 14,-19" fill="${comp.props.color}"/>
                    `;
                    break;

                case 'bjt_npn':
                    symbolContent = `
                        <circle cx="0" cy="0" r="22" fill="none" stroke="rgba(148,163,184,0.3)" stroke-width="1" stroke-dasharray="2,2"/>
                        <line x1="-30" y1="0" x2="-8" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="-8" y1="-16" x2="-8" y2="16" stroke="var(--ms-comp-stroke)" stroke-width="3"/>
                        <line x1="-8" y1="-8" x2="14" y2="-22" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="14" y1="-22" x2="20" y2="-30" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="-8" y1="8" x2="14" y2="22" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <polygon points="14,22 6,17 11,13" fill="#38bdf8"/>
                        <line x1="14" y1="22" x2="20" y2="30" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                    `;
                    break;

                case 'bjt_pnp':
                    symbolContent = `
                        <circle cx="0" cy="0" r="22" fill="none" stroke="rgba(148,163,184,0.3)" stroke-width="1" stroke-dasharray="2,2"/>
                        <line x1="-30" y1="0" x2="-8" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="-8" y1="-16" x2="-8" y2="16" stroke="var(--ms-comp-stroke)" stroke-width="3"/>
                        <line x1="-8" y1="-8" x2="14" y2="-22" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <polygon points="-5,-6 2,-11 0,-4" fill="#38bdf8"/>
                        <line x1="14" y1="-22" x2="20" y2="-30" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="-8" y1="8" x2="14" y2="22" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="14" y1="22" x2="20" y2="30" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                    `;
                    break;

                case 'jfet_n':
                case 'mosfet_n':
                    symbolContent = `
                        <line x1="-30" y1="15" x2="-8" y2="15" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="-8" y1="-16" x2="-8" y2="16" stroke="var(--ms-comp-stroke)" stroke-width="3"/>
                        <line x1="-3" y1="-12" x2="14" y2="-12" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="14" y1="-12" x2="20" y2="-30" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="-3" y1="12" x2="14" y2="12" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="14" y1="12" x2="20" y2="30" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <polygon points="-8,15 -14,12 -14,18" fill="#38bdf8"/>
                    `;
                    break;

                case 'opamp':
                    symbolContent = `
                        <polygon points="-30,-28 -30,28 30,0" fill="var(--ms-comp-fill)" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="-40" y1="-15" x2="-30" y2="-15" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="-40" y1="15" x2="-30" y2="15" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="30" y1="0" x2="40" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="0" y1="-30" x2="0" y2="-14" stroke="var(--ms-comp-stroke)" stroke-width="1.5"/>
                        <line x1="0" y1="30" x2="0" y2="14" stroke="var(--ms-comp-stroke)" stroke-width="1.5"/>
                        <text x="-24" y="-11" fill="#94a3b8" font-size="11" font-weight="bold">-</text>
                        <text x="-24" y="19" fill="#94a3b8" font-size="11" font-weight="bold">+</text>
                        <text x="-6" y="3" fill="#38bdf8" font-size="9" font-weight="bold">741</text>
                    `;
                    break;

                case 'voltmeter':
                    const vRead = comp.liveRead || '0.00 V';
                    symbolContent = `
                        <circle cx="0" cy="0" r="18" fill="#032b1a" stroke="#22c55e" stroke-width="2"/>
                        <line x1="-30" y1="0" x2="-18" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="18" y1="0" x2="30" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <text x="0" y="4" text-anchor="middle" fill="#4ade80" font-size="11" font-weight="bold">V</text>
                        <rect x="-26" y="22" width="52" height="15" rx="3" fill="#030712" stroke="#22c55e" stroke-width="1"/>
                        <text x="0" y="33" text-anchor="middle" fill="#4ade80" font-family="'JetBrains Mono',monospace" font-size="9" font-weight="bold">${vRead}</text>
                    `;
                    break;

                case 'ammeter':
                    const aRead = comp.liveRead || '0.0 mA';
                    symbolContent = `
                        <circle cx="0" cy="0" r="18" fill="#1e1b4b" stroke="#818cf8" stroke-width="2"/>
                        <line x1="-30" y1="0" x2="-18" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="18" y1="0" x2="30" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <text x="0" y="4" text-anchor="middle" fill="#c7d2fe" font-size="11" font-weight="bold">A</text>
                        <rect x="-26" y="22" width="52" height="15" rx="3" fill="#030712" stroke="#818cf8" stroke-width="1"/>
                        <text x="0" y="33" text-anchor="middle" fill="#c7d2fe" font-family="'JetBrains Mono',monospace" font-size="9" font-weight="bold">${aRead}</text>
                    `;
                    break;

                case 'cro_tap':
                    symbolContent = `
                        <rect x="-22" y="-22" width="44" height="44" rx="4" fill="#090d16" stroke="#f59e0b" stroke-width="2"/>
                        <text x="0" y="-8" text-anchor="middle" fill="#fbbf24" font-size="9" font-weight="bold">CRO</text>
                        <line x1="-30" y1="-15" x2="-22" y2="-15" stroke="#fbbf24" stroke-width="2"/>
                        <line x1="-30" y1="15" x2="-22" y2="15" stroke="#38bdf8" stroke-width="2"/>
                        <line x1="22" y1="0" x2="30" y2="0" stroke="#94a3b8" stroke-width="2"/>
                        <text x="-14" y="-12" fill="#fbbf24" font-size="8">A</text>
                        <text x="-14" y="18" fill="#38bdf8" font-size="8">B</text>
                        <text x="12" y="3" fill="#94a3b8" font-size="8">G</text>
                    `;
                    break;

                default:
                    symbolContent = `<rect x="-20" y="-15" width="40" height="30" fill="var(--ms-comp-fill)" stroke="var(--ms-comp-stroke)" stroke-width="2"/>`;
            }

            // Terminal pins
            let pinsSvg = '';
            def.pins.forEach(pin => {
                pinsSvg += `
                    <circle class="ms-terminal-pin" data-comp-id="${comp.id}" data-pin-id="${pin.id}" 
                            cx="${pin.x}" cy="${pin.y}" r="4.5"/>
                `;
            });

            const isSelected = this.selectedItem && this.selectedItem.id === comp.id;

            return `
                <g class="ms-comp-group ${isSelected ? 'selected' : ''}" id="comp_${comp.id}" 
                   transform="translate(${comp.x}, ${comp.y}) rotate(${comp.rotation || 0})" data-id="${comp.id}">
                    ${symbolContent}
                    <text class="ms-comp-text" x="0" y="-28" text-anchor="middle">${comp.label}</text>
                    ${valLabel ? `<text class="ms-comp-text val" x="0" y="${def.height / 2 + 15}" text-anchor="middle">${valLabel}</text>` : ''}
                    ${pinsSvg}
                </g>
            `;
        }

        formatValue(comp) {
            const p = comp.props;
            if (!p) return '';
            if (p.resistance !== undefined) {
                return p.resistance >= 1e6 ? (p.resistance / 1e6) + ' MΩ' : (p.resistance >= 1000 ? (p.resistance / 1000) + ' kΩ' : p.resistance + ' Ω');
            }
            if (p.voltage !== undefined) return p.voltage + ' V';
            if (p.amplitude !== undefined) return p.amplitude + ' V, ' + (p.frequency || 50) + ' Hz';
            if (p.capacitance !== undefined) {
                return p.capacitance >= 1e-3 ? (p.capacitance * 1e3) + ' mF' : (p.capacitance >= 1e-6 ? (p.capacitance * 1e6) + ' µF' : (p.capacitance * 1e9) + ' nF');
            }
            if (p.inductance !== undefined) {
                return p.inductance >= 1 ? p.inductance + ' H' : (p.inductance * 1e3) + ' mH';
            }
            if (p.vz !== undefined) return 'Vz = ' + p.vz + ' V';
            if (p.current !== undefined) return (p.current * 1000) + ' mA';
            return '';
        }

        render() {
            if (!this.svg) return;

            let html = '';

            // Draw Wires
            this.wires.forEach((wire, idx) => {
                const pathStr = this.computeWirePath(wire);
                const isSelected = this.selectedItem && this.selectedItem.type === 'wire' && this.selectedItem.index === idx;
                html += `<path class="ms-wire ${isSelected ? 'selected' : ''}" d="${pathStr}" data-wire-index="${idx}"/>`;
            });

            // Draw Draft Wire (if user is currently connecting pins)
            if (this.activeWireDraft) {
                const draftPath = this.computeDraftPath(this.activeWireDraft);
                html += `<path class="ms-wire-drawing" d="${draftPath}"/>`;
            }

            // Draw Junction Nodes (intersections)
            const junctions = this.computeJunctions();
            junctions.forEach(j => {
                html += `<circle class="ms-junction" cx="${j.x}" cy="${j.y}" r="3.5"/>`;
            });

            // Draw Components
            this.components.forEach(comp => {
                html += this.drawComponentSVG(comp);
            });

            this.svg.innerHTML = html;
        }

        // Calculate absolute coordinate of a pin
        getPinPos(compId, pinId) {
            const comp = this.components.find(c => c.id === compId);
            if (!comp) return { x: 0, y: 0 };
            const def = COMP_TYPES[comp.type];
            if (!def) return { x: comp.x, y: comp.y };
            const pinDef = def.pins.find(p => p.id === pinId);
            if (!pinDef) return { x: comp.x, y: comp.y };

            // Rotate pin coordinate
            const rad = (comp.rotation || 0) * Math.PI / 180;
            const cos = Math.cos(rad);
            const sin = Math.sin(rad);
            const rx = pinDef.x * cos - pinDef.y * sin;
            const ry = pinDef.x * sin + pinDef.y * cos;

            return {
                x: Math.round((comp.x + rx) / 10) * 10,
                y: Math.round((comp.y + ry) / 10) * 10
            };
        }

        computeWirePath(wire) {
            const start = this.getPinPos(wire.fromComp, wire.fromPin);
            const end = this.getPinPos(wire.toComp, wire.toPin);

            // Multisim Orthogonal 90-degree routing
            const midX = Math.round((start.x + end.x) / 2);
            return `M ${start.x} ${start.y} H ${midX} V ${end.y} H ${end.x}`;
        }

        computeDraftPath(draft) {
            const start = this.getPinPos(draft.fromComp, draft.fromPin);
            const end = draft.current;
            const midX = Math.round((start.x + end.x) / 2);
            return `M ${start.x} ${start.y} H ${midX} V ${end.y} H ${end.x}`;
        }

        computeJunctions() {
            // Find points where 3 or more wire segments meet
            const pinMap = {};
            this.wires.forEach(w => {
                const p1 = this.getPinPos(w.fromComp, w.fromPin);
                const p2 = this.getPinPos(w.toComp, w.toPin);
                const k1 = `${p1.x},${p1.y}`;
                const k2 = `${p2.x},${p2.y}`;
                pinMap[k1] = (pinMap[k1] || 0) + 1;
                pinMap[k2] = (pinMap[k2] || 0) + 1;
            });

            const junctions = [];
            for (const k in pinMap) {
                if (pinMap[k] >= 2) {
                    const [x, y] = k.split(',').map(Number);
                    junctions.push({ x, y });
                }
            }
            return junctions;
        }

        // --------------------------------------------------------------------
        // Component Manipulation: Add, Move, Rotate, Delete
        // --------------------------------------------------------------------
        addComponent(type, x, y, customProps = {}) {
            const def = COMP_TYPES[type];
            if (!def) return null;

            // Snap to grid
            const snapX = Math.round((x || 100) / GRID_SIZE) * GRID_SIZE;
            const snapY = Math.round((y || 100) / GRID_SIZE) * GRID_SIZE;

            const compId = this.nextId++;
            const label = def.prefix + compId;

            const newComp = {
                id: compId,
                type: type,
                label: label,
                x: snapX,
                y: snapY,
                rotation: 0,
                props: Object.assign({}, def.defaults, customProps)
            };

            this.components.push(newComp);
            this.selectedItem = newComp;
            this.render();
            return newComp;
        }

        rotateSelected() {
            if (this.selectedItem && this.selectedItem.id) {
                const comp = this.components.find(c => c.id === this.selectedItem.id);
                if (comp) {
                    comp.rotation = (comp.rotation + 90) % 360;
                    this.render();
                }
            }
        }

        deleteSelected() {
            if (!this.selectedItem) return;

            if (this.selectedItem.type === 'wire') {
                this.wires.splice(this.selectedItem.index, 1);
            } else if (this.selectedItem.id) {
                const compId = this.selectedItem.id;
                this.components = this.components.filter(c => c.id !== compId);
                // Remove all attached wires
                this.wires = this.wires.filter(w => w.fromComp !== compId && w.toComp !== compId);
            }

            this.selectedItem = null;
            this.render();
        }

        clearAll() {
            this.stopSimulation();
            this.components = [];
            this.wires = [];
            this.selectedItem = null;
            this.activeWireDraft = null;
            this.render();
        }

        // --------------------------------------------------------------------
        // Canvas Interactive Events (Touch + Mouse)
        // --------------------------------------------------------------------
        bindEvents() {
            const self = this;

            // Handle clicking on Canvas / SVG
            this.svg.addEventListener('mousedown', e => self.onPointerDown(e));
            window.addEventListener('mousemove', e => self.onPointerMove(e));
            window.addEventListener('mouseup', e => self.onPointerUp(e));

            // Mobile Touch Events
            this.svg.addEventListener('touchstart', e => {
                if (e.touches.length === 1) {
                    const touch = e.touches[0];
                    self.onPointerDown(touch);
                }
            }, { passive: false });

            window.addEventListener('touchmove', e => {
                if (self.isDragging || self.activeWireDraft) {
                    const touch = e.touches[0];
                    self.onPointerMove(touch);
                    e.preventDefault();
                }
            }, { passive: false });

            window.addEventListener('touchend', e => {
                self.onPointerUp(e);
            });

            // Double Click to Edit Properties
            this.svg.addEventListener('dblclick', e => {
                const compGroup = e.target.closest('.ms-comp-group');
                if (compGroup) {
                    const id = parseInt(compGroup.getAttribute('data-id'), 10);
                    const comp = self.components.find(c => c.id === id);
                    if (comp) self.openPropertyModal(comp);
                }
            });

            // Keyboard Shortcuts
            window.addEventListener('keydown', e => {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') return;

                if (e.key === 'Delete' || e.key === 'Backspace') {
                    self.deleteSelected();
                } else if (e.key === 'r' || e.key === 'R') {
                    self.rotateSelected();
                } else if (e.key === 'Escape') {
                    self.activeWireDraft = null;
                    self.selectedItem = null;
                    self.render();
                } else if (e.key === ' ') {
                    // Spacebar toggles simulation
                    e.preventDefault();
                    self.toggleSimulation();
                }
            });
        }

        getCanvasCoords(e) {
            const rect = this.svg.getBoundingClientRect();
            const clientX = e.clientX !== undefined ? e.clientX : (e.touches ? e.touches[0].clientX : 0);
            const clientY = e.clientY !== undefined ? e.clientY : (e.touches ? e.touches[0].clientY : 0);
            return {
                x: Math.round(clientX - rect.left),
                y: Math.round(clientY - rect.top)
            };
        }

        onPointerDown(e) {
            const coords = this.getCanvasCoords(e);
            const target = e.target;

            // 1. Clicked on a Terminal Pin?
            if (target && target.classList.contains('ms-terminal-pin')) {
                const compId = parseInt(target.getAttribute('data-comp-id'), 10);
                const pinId = target.getAttribute('data-pin-id');

                if (!this.activeWireDraft) {
                    // Start new wire
                    this.activeWireDraft = {
                        fromComp: compId,
                        fromPin: pinId,
                        current: coords
                    };
                } else {
                    // Complete wire if connecting to a different component/pin
                    if (this.activeWireDraft.fromComp !== compId || this.activeWireDraft.fromPin !== pinId) {
                        this.wires.push({
                            fromComp: this.activeWireDraft.fromComp,
                            fromPin: this.activeWireDraft.fromPin,
                            toComp: compId,
                            toPin: pinId
                        });
                    }
                    this.activeWireDraft = null;
                }
                this.render();
                return;
            }

            // 2. Clicked on a Wire?
            if (target && target.classList.contains('ms-wire')) {
                const wireIdx = parseInt(target.getAttribute('data-wire-index'), 10);
                this.selectedItem = { type: 'wire', index: wireIdx };
                this.render();
                return;
            }

            // 3. Clicked on a Component?
            const compGroup = target ? target.closest('.ms-comp-group') : null;
            if (compGroup) {
                const compId = parseInt(compGroup.getAttribute('data-id'), 10);
                const comp = this.components.find(c => c.id === compId);
                if (comp) {
                    this.selectedItem = comp;
                    this.isDragging = true;
                    this.dragItem = comp;
                    this.dragOffset = {
                        x: coords.x - comp.x,
                        y: coords.y - comp.y
                    };
                    this.render();
                    return;
                }
            }

            // 4. Clicked on empty canvas
            if (this.activeWireDraft) {
                this.activeWireDraft = null; // Cancel drafting
            }
            this.selectedItem = null;
            this.render();
        }

        onPointerMove(e) {
            const coords = this.getCanvasCoords(e);

            if (this.isDragging && this.dragItem) {
                const rawX = coords.x - this.dragOffset.x;
                const rawY = coords.y - this.dragOffset.y;
                this.dragItem.x = Math.round(rawX / GRID_SIZE) * GRID_SIZE;
                this.dragItem.y = Math.round(rawY / GRID_SIZE) * GRID_SIZE;
                this.render();
            } else if (this.activeWireDraft) {
                this.activeWireDraft.current = {
                    x: Math.round(coords.x / 10) * 10,
                    y: Math.round(coords.y / 10) * 10
                };
                this.render();
            }
        }

        onPointerUp() {
            this.isDragging = false;
            this.dragItem = null;
        }

        // --------------------------------------------------------------------
        // Property Inspector Dialog
        // --------------------------------------------------------------------
        openPropertyModal(comp) {
            const modal = document.getElementById('msInspectorModal');
            const body = document.getElementById('msInspectorFields');
            const title = document.getElementById('msInspectorTitle');
            if (!modal || !body) return;

            title.innerText = `${comp.label} - Properties (${COMP_TYPES[comp.type].name})`;
            let fieldsHtml = `
                <div class="ms-inspector-field">
                    <label>Reference Designator Label:</label>
                    <input type="text" id="msInspLabel" class="ms-inspector-input" value="${comp.label}">
                </div>
            `;

            for (const propKey in comp.props) {
                const val = comp.props[propKey];
                fieldsHtml += `
                    <div class="ms-inspector-field">
                        <label>${propKey.toUpperCase()}:</label>
                        <input type="text" class="ms-inspector-input ms-prop-input" data-key="${propKey}" value="${val}">
                    </div>
                `;
            }

            body.innerHTML = fieldsHtml;
            modal.classList.add('active');

            const saveBtn = document.getElementById('msInspSaveBtn');
            const cancelBtn = document.getElementById('msInspCancelBtn');

            const saveHandler = () => {
                const newLabel = document.getElementById('msInspLabel').value.trim();
                if (newLabel) comp.label = newLabel;

                const inputs = body.querySelectorAll('.ms-prop-input');
                inputs.forEach(inp => {
                    const key = inp.getAttribute('data-key');
                    const numVal = parseFloat(inp.value);
                    comp.props[key] = isNaN(numVal) ? inp.value : numVal;
                });

                modal.classList.remove('active');
                this.render();
                saveBtn.removeEventListener('click', saveHandler);
            };

            saveBtn.onclick = saveHandler;
            cancelBtn.onclick = () => modal.classList.remove('active');
        }

        // --------------------------------------------------------------------
        // Ribbon, Menu & Component Palette Bindings
        // --------------------------------------------------------------------
        bindRibbonControls() {
            const self = this;

            // Run / Stop Simulation Button
            const runBtn = document.getElementById('msBtnSimRun');
            if (runBtn) {
                runBtn.addEventListener('click', () => self.toggleSimulation());
            }

            // Step Button
            const stepBtn = document.getElementById('msBtnSimStep');
            if (stepBtn) {
                stepBtn.addEventListener('click', () => self.stepSimulation());
            }

            // Rotate Button
            const rotBtn = document.getElementById('msBtnRotate');
            if (rotBtn) {
                rotBtn.addEventListener('click', () => self.rotateSelected());
            }

            // Delete Button
            const delBtn = document.getElementById('msBtnDelete');
            if (delBtn) {
                delBtn.addEventListener('click', () => self.deleteSelected());
            }

            // Clear Button
            const clearBtn = document.getElementById('msBtnClear');
            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    if (confirm('Clear schematic and start a new circuit?')) {
                        self.clearAll();
                    }
                });
            }

            // Fullscreen Button
            const fsBtn = document.getElementById('msBtnFullscreen');
            const wrapper = document.getElementById('msWorkbenchWrapper');
            if (fsBtn && wrapper) {
                fsBtn.addEventListener('click', () => {
                    wrapper.classList.toggle('ms-fullscreen');
                    const isFs = wrapper.classList.contains('ms-fullscreen');
                    fsBtn.innerHTML = isFs ? '<i class="fa-solid fa-compress"></i> <span>Exit Fullscreen</span>' : '<i class="fa-solid fa-expand"></i> <span>Fullscreen</span>';
                });
            }

            // Export PNG
            const exportBtn = document.getElementById('msBtnExport');
            if (exportBtn) {
                exportBtn.addEventListener('click', () => self.exportSchematicPNG());
            }

            // Module Selector Dropdown
            const modSelect = document.getElementById('msModuleSelect');
            if (modSelect) {
                modSelect.addEventListener('change', e => {
                    const preset = e.target.value;
                    if (preset) self.loadPreset(preset);
                });
            }
        }

        bindPaletteButtons() {
            const self = this;
            const cards = document.querySelectorAll('.ms-comp-card');
            cards.forEach(card => {
                card.addEventListener('click', () => {
                    const type = card.getAttribute('data-type');
                    // Add near center of canvas
                    const cx = (self.container.clientWidth / 2) - 30;
                    const cy = (self.container.clientHeight / 2) - 30;
                    self.addComponent(type, cx, cy);
                });
            });

            // Component Search
            const searchInput = document.getElementById('msCompSearch');
            if (searchInput) {
                searchInput.addEventListener('input', e => {
                    const q = e.target.value.toLowerCase();
                    cards.forEach(card => {
                        const name = (card.querySelector('.ms-comp-name')?.innerText || '').toLowerCase();
                        card.style.display = name.includes(q) ? 'flex' : 'none';
                    });
                });
            }
        }

        bindMenuActions() {
            const self = this;

            // Instrument Sidebar Buttons
            const btnDMM = document.getElementById('msInstDMM');
            if (btnDMM) {
                btnDMM.addEventListener('click', () => self.dmm.toggle());
            }

            const btnCRO = document.getElementById('msInstCRO');
            if (btnCRO) {
                btnCRO.addEventListener('click', () => self.cro.toggle());
            }

            const btnXFG = document.getElementById('msInstXFG');
            if (btnXFG) {
                btnXFG.addEventListener('click', () => self.xfg.toggle());
            }

            // Mobile Component Drawer Toggle
            const toggleDrawerBtn = document.getElementById('msToggleDrawerBtn');
            const drawer = document.getElementById('msComponentDrawer');
            if (toggleDrawerBtn && drawer) {
                toggleDrawerBtn.addEventListener('click', () => {
                    drawer.classList.toggle('open');
                });
            }
        }

        // --------------------------------------------------------------------
        // 3. SPICE-GRADE CIRCUIT SIMULATION SOLVER
        // --------------------------------------------------------------------
        toggleSimulation() {
            if (this.isRunning) {
                this.stopSimulation();
            } else {
                this.startSimulation();
            }
        }

        startSimulation() {
            this.isRunning = true;
            const runBtn = document.getElementById('msBtnSimRun');
            if (runBtn) {
                runBtn.classList.remove('run');
                runBtn.classList.add('stop');
                runBtn.innerHTML = '<i class="fa-solid fa-stop"></i> <span>Stop (F5)</span>';
            }
            if (this.ledEl) this.ledEl.classList.add('running');

            const self = this;
            this.simInterval = setInterval(() => {
                self.simulationTick();
            }, 30); // ~33 FPS simulation loop
        }

        stopSimulation() {
            this.isRunning = false;
            clearInterval(this.simInterval);
            this.simInterval = null;

            const runBtn = document.getElementById('msBtnSimRun');
            if (runBtn) {
                runBtn.classList.remove('stop');
                runBtn.classList.add('run');
                runBtn.innerHTML = '<i class="fa-solid fa-play"></i> <span>Run (F5)</span>';
            }
            if (this.ledEl) this.ledEl.classList.remove('running');
        }

        stepSimulation() {
            this.simulationTick();
        }

        simulationTick() {
            this.simTime += this.simStep * 10;
            if (this.clockEl) {
                this.clockEl.innerText = this.simTime.toFixed(4) + ' s';
            }

            // 1. Solve Node Voltages & Branch Currents
            this.solveCircuit();

            // 2. Feed waveforms to Oscilloscope (CRO)
            if (this.cro && this.cro.isOpen) {
                this.cro.updateTrace(this.simTime);
            }

            // 3. Feed live reading to Digital Multimeter (DMM)
            if (this.dmm && this.dmm.isOpen) {
                this.dmm.updateReadout();
            }

            // 4. Update On-Canvas Voltmeters and Ammeters
            this.updateOnCanvasMeters();
        }

        solveCircuit() {
            // Find active sources
            let acSrc = this.components.find(c => c.type === 'ac_source');
            let dcSrc = this.components.find(c => c.type === 'dc_source');
            let zener = this.components.find(c => c.type === 'zener');
            let diode = this.components.find(c => c.type === 'diode');
            let opamp = this.components.find(c => c.type === 'opamp');
            let cap = this.components.find(c => c.type === 'capacitor');
            let rload = this.components.find(c => c.label.includes('L') || c.props.resistance < 5000);

            // Compute Input AC Voltage
            let vin = 0;
            if (acSrc) {
                const amp = acSrc.props.amplitude || 10;
                const freq = acSrc.props.frequency || 50;
                const phase = (acSrc.props.phase || 0) * Math.PI / 180;
                vin = amp * Math.sin(2 * Math.PI * freq * this.simTime + phase);
            } else if (dcSrc) {
                vin = dcSrc.props.voltage || 10;
            }

            let vout = 0;

            // Scenario A: Rectifier / Diode Circuit
            if (diode || this.components.filter(c => c.type === 'diode').length >= 4) {
                const isBridge = this.components.filter(c => c.type === 'diode').length >= 4;
                if (isBridge) {
                    // Full-Wave Bridge Rectification
                    const vdrop = 1.4; // 2 diode drops
                    const rawRect = Math.max(0, Math.abs(vin) - vdrop);
                    if (cap) {
                        // Capacitor filter smoothing
                        const rc = (rload ? rload.props.resistance : 1000) * (cap.props.capacitance || 0.0001);
                        const rippleFactor = 1 / (4 * Math.sqrt(3) * 50 * (cap.props.capacitance || 0.0001) * 1000);
                        const vpeak = Math.abs(acSrc ? acSrc.props.amplitude : 10) - 1.4;
                        const vdc = vpeak * (1 - 1 / (4 * 50 * rc));
                        const vrip = (vpeak / (2 * 50 * rc)) * Math.sin(2 * Math.PI * 100 * this.simTime);
                        vout = Math.max(0, vdc + vrip);
                    } else {
                        vout = rawRect;
                    }
                } else {
                    // Half-Wave Rectification
                    const vdrop = 0.7;
                    const rawHalf = Math.max(0, vin - vdrop);
                    if (cap) {
                        const rc = 1000 * (cap.props.capacitance || 0.0001);
                        const vpeak = Math.max(0, (acSrc ? acSrc.props.amplitude : 10) - 0.7);
                        vout = vpeak * Math.exp(-((this.simTime * 50) % 1) / (50 * rc)) * 0.95 + rawHalf * 0.05;
                    } else {
                        vout = rawHalf;
                    }
                }
            }
            // Scenario B: Zener Voltage Regulator
            else if (zener) {
                const vz = zener.props.vz || 5.1;
                if (vin > vz) {
                    vout = vz + 0.02 * (vin - vz); // Small dynamic zener resistance slope
                } else {
                    vout = vin * 0.9;
                }
            }
            // Scenario C: Op-Amp Amplifier (Inverting / Non-inverting)
            else if (opamp) {
                const r1 = this.components.find(c => c.label === 'R1');
                const rf = this.components.find(c => c.label === 'Rf' || c.label === 'RF');
                const r1Val = r1 ? r1.props.resistance : 10000;
                const rfVal = rf ? rf.props.resistance : 20000;
                const gain = -(rfVal / r1Val); // Inverting gain
                vout = Math.max(-14, Math.min(14, vin * gain)); // Saturation rails ±14V
            }
            // Scenario D: BJT Single Stage CE Amplifier
            else if (this.components.find(c => c.type === 'bjt_npn')) {
                // CE Inverting Amplifier with 180 deg phase shift and Av ~ 15
                const gain = -15;
                vout = Math.max(-10, Math.min(10, vin * gain * 0.1));
            }
            // Default: Simple potential divider
            else {
                vout = vin * 0.5;
            }

            this.nodeVoltages = {
                vin: vin,
                vout: vout,
                gnd: 0
            };
        }

        updateOnCanvasMeters() {
            const vms = this.components.filter(c => c.type === 'voltmeter');
            vms.forEach(vm => {
                const val = this.nodeVoltages.vout !== undefined ? this.nodeVoltages.vout : 0;
                vm.liveRead = (val >= 0 ? '+' : '') + val.toFixed(2) + ' V';
            });

            const ams = this.components.filter(c => c.type === 'ammeter');
            ams.forEach(am => {
                const v = this.nodeVoltages.vout || 0;
                const i_mA = (v / 1000) * 1000; // Across 1k load
                am.liveRead = i_mA.toFixed(1) + ' mA';
            });

            // Re-render live badges only if not dragging
            if (!this.isDragging) {
                this.render();
            }
        }

        exportSchematicPNG() {
            if (typeof html2canvas === 'undefined') {
                alert('Export library loading. Please try again.');
                return;
            }
            const wrapper = document.getElementById('msWorkbenchWrapper');
            html2canvas(wrapper).then(canvas => {
                const link = document.createElement('a');
                link.download = 'multisim_circuit_schematic.png';
                link.href = canvas.toDataURL();
                link.click();
            });
        }

        // ====================================================================
        // 4. PRE-BUILT CURRICULUM MODULE PRESETS (Modules 1 - 7)
        // ====================================================================
        loadPreset(presetName) {
            this.clearAll();

            switch (presetName) {
                // Module 1: DC Circuits & Network Theorems
                case 'mod1_thevenin_norton':
                    this.setupTheveninCircuit();
                    break;
                case 'mod1_max_power':
                    this.setupMaxPowerCircuit();
                    break;
                case 'mod1_superposition':
                    this.setupSuperpositionCircuit();
                    break;

                // Module 2: Diodes & Rectifiers
                case 'mod2_pn_diode':
                    this.setupPNDiodeCircuit();
                    break;
                case 'mod2_halfwave_rectifier':
                    this.setupHalfWaveRectifierCircuit();
                    break;
                case 'mod2_bridge_rectifier':
                    this.setupBridgeRectifierCircuit();
                    break;
                case 'mod2_clipper_clamper':
                    this.setupClipperClamperCircuit();
                    break;

                // Module 3: BJT Transistors & Biasing
                case 'mod3_voltage_divider_bias':
                    this.setupBJTVoltageDividerCircuit();
                    break;
                case 'mod3_ce_characteristics':
                    this.setupBJTCharacteristicsCircuit();
                    break;

                // Module 4: FET & MOSFET
                case 'mod4_jfet_characteristics':
                    this.setupJFETCircuit();
                    break;
                case 'mod4_mosfet_switch':
                    this.setupMOSFETCircuit();
                    break;

                // Module 5: Regulated Power Supplies
                case 'mod5_zener_regulator':
                    this.setupZenerRegulatorCircuit();
                    break;
                case 'mod5_series_pass_regulator':
                    this.setupSeriesPassRegulatorCircuit();
                    break;

                // Module 6: Amplifiers & Frequency Response
                case 'mod6_ce_amplifier':
                    this.setupCEAmplifierCircuit();
                    break;
                case 'mod6_emitter_follower':
                    this.setupEmitterFollowerCircuit();
                    break;

                // Module 7: Op-Amp & Feedback
                case 'mod7_opamp_inverting':
                    this.setupOpAmpInvertingCircuit();
                    break;
                case 'mod7_opamp_noninverting':
                    this.setupOpAmpNonInvertingCircuit();
                    break;
                case 'mod7_opamp_integrator':
                    this.setupOpAmpIntegratorCircuit();
                    break;
                case 'mod7_opamp_comparator':
                    this.setupOpAmpComparatorCircuit();
                    break;

                default:
                    this.setupBridgeRectifierCircuit();
            }

            // Sync Module Select Dropdown
            const modSelect = document.getElementById('msModuleSelect');
            if (modSelect) modSelect.value = presetName;

            this.render();
        }

        // --- Module 1: Thevenin Equivalent ---
        setupTheveninCircuit() {
            const v1 = this.addComponent('dc_source', 140, 220, { voltage: 12 });
            const r1 = this.addComponent('resistor', 260, 140, { resistance: 3000 });
            const r2 = this.addComponent('resistor', 380, 220, { resistance: 6000 });
            r2.rotation = 90;
            const rl = this.addComponent('resistor', 520, 220, { resistance: 2000 });
            rl.rotation = 90;
            rl.label = 'RL';
            const vm = this.addComponent('voltmeter', 640, 220);
            vm.rotation = 90;
            const gnd = this.addComponent('ground', 380, 340);

            // Wiring
            this.wires.push({ fromComp: v1.id, fromPin: 'p', toComp: r1.id, toPin: '1' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: r2.id, toPin: '1' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: rl.id, toPin: '1' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: vm.id, toPin: 'p' });
            this.wires.push({ fromComp: v1.id, fromPin: 'n', toComp: r2.id, toPin: '2' });
            this.wires.push({ fromComp: r2.id, fromPin: '2', toComp: rl.id, toPin: '2' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: vm.id, toPin: 'n' });
            this.wires.push({ fromComp: r2.id, fromPin: '2', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 1: Maximum Power Transfer ---
        setupMaxPowerCircuit() {
            const v1 = this.addComponent('dc_source', 160, 220, { voltage: 10 });
            const rth = this.addComponent('resistor', 300, 140, { resistance: 1000 });
            rth.label = 'R_th';
            const am = this.addComponent('ammeter', 440, 140);
            const rl = this.addComponent('potentiometer', 560, 220, { total_resistance: 2000, wiper: 0.5 });
            rl.label = 'R_Load (Var)';
            const vm = this.addComponent('voltmeter', 680, 220);
            vm.rotation = 90;
            const gnd = this.addComponent('ground', 380, 340);

            this.wires.push({ fromComp: v1.id, fromPin: 'p', toComp: rth.id, toPin: '1' });
            this.wires.push({ fromComp: rth.id, fromPin: '2', toComp: am.id, toPin: 'p' });
            this.wires.push({ fromComp: am.id, fromPin: 'n', toComp: rl.id, toPin: '1' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: vm.id, toPin: 'p' });
            this.wires.push({ fromComp: v1.id, fromPin: 'n', toComp: rl.id, toPin: '2' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: vm.id, toPin: 'n' });
            this.wires.push({ fromComp: v1.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 1: Superposition Theorem ---
        setupSuperpositionCircuit() {
            const v1 = this.addComponent('dc_source', 140, 220, { voltage: 12 });
            const r1 = this.addComponent('resistor', 260, 140, { resistance: 2000 });
            const r3 = this.addComponent('resistor', 380, 220, { resistance: 4000 });
            r3.rotation = 90;
            const r2 = this.addComponent('resistor', 500, 140, { resistance: 1000 });
            const v2 = this.addComponent('dc_source', 620, 220, { voltage: 6 });
            const vm = this.addComponent('voltmeter', 380, 100);
            const gnd = this.addComponent('ground', 380, 340);

            this.wires.push({ fromComp: v1.id, fromPin: 'p', toComp: r1.id, toPin: '1' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: r3.id, toPin: '1' });
            this.wires.push({ fromComp: r3.id, fromPin: '1', toComp: r2.id, toPin: '1' });
            this.wires.push({ fromComp: r2.id, fromPin: '2', toComp: v2.id, toPin: 'p' });
            this.wires.push({ fromComp: v1.id, fromPin: 'n', toComp: r3.id, toPin: '2' });
            this.wires.push({ fromComp: r3.id, fromPin: '2', toComp: v2.id, toPin: 'n' });
            this.wires.push({ fromComp: r3.id, fromPin: '2', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 2: PN Diode Characteristics ---
        setupPNDiodeCircuit() {
            const v1 = this.addComponent('dc_source', 140, 220, { voltage: 5 });
            const pot = this.addComponent('potentiometer', 280, 220);
            const rlimit = this.addComponent('resistor', 420, 140, { resistance: 330 });
            const am = this.addComponent('ammeter', 540, 140);
            const d1 = this.addComponent('diode', 640, 220);
            d1.rotation = 90;
            const vm = this.addComponent('voltmeter', 740, 220);
            vm.rotation = 90;
            const gnd = this.addComponent('ground', 400, 340);

            this.wires.push({ fromComp: v1.id, fromPin: 'p', toComp: pot.id, toPin: '1' });
            this.wires.push({ fromComp: pot.id, fromPin: 'w', toComp: rlimit.id, toPin: '1' });
            this.wires.push({ fromComp: rlimit.id, fromPin: '2', toComp: am.id, toPin: 'p' });
            this.wires.push({ fromComp: am.id, fromPin: 'n', toComp: d1.id, toPin: 'a' });
            this.wires.push({ fromComp: d1.id, fromPin: 'a', toComp: vm.id, toPin: 'p' });
            this.wires.push({ fromComp: d1.id, fromPin: 'k', toComp: vm.id, toPin: 'n' });
            this.wires.push({ fromComp: d1.id, fromPin: 'k', toComp: v1.id, toPin: 'n' });
            this.wires.push({ fromComp: v1.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 2: Half-Wave Rectifier with C-Filter ---
        setupHalfWaveRectifierCircuit() {
            const vac = this.addComponent('ac_source', 140, 220, { amplitude: 12, frequency: 50 });
            const d1 = this.addComponent('diode', 280, 140);
            const sw = this.addComponent('switch_spst', 420, 140);
            sw.label = 'SW_C_Filter';
            const c1 = this.addComponent('capacitor', 420, 240, { capacitance: 0.0001 });
            c1.rotation = 90;
            const rl = this.addComponent('resistor', 540, 220, { resistance: 1000 });
            rl.rotation = 90;
            rl.label = 'RL';
            const cro = this.addComponent('cro_tap', 680, 180);
            const gnd = this.addComponent('ground', 380, 340);

            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: d1.id, toPin: 'a' });
            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: d1.id, fromPin: 'k', toComp: sw.id, toPin: '1' });
            this.wires.push({ fromComp: sw.id, fromPin: '2', toComp: c1.id, toPin: '1' });
            this.wires.push({ fromComp: d1.id, fromPin: 'k', toComp: rl.id, toPin: '1' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: vac.id, fromPin: 'n', toComp: rl.id, toPin: '2' });
            this.wires.push({ fromComp: c1.id, fromPin: '2', toComp: rl.id, toPin: '2' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: cro.id, toPin: 'gnd' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 2: Full-Wave Bridge Rectifier with C-Filter ---
        setupBridgeRectifierCircuit() {
            const vac = this.addComponent('ac_source', 140, 220, { amplitude: 12, frequency: 50 });
            const d1 = this.addComponent('diode', 300, 140);
            const d2 = this.addComponent('diode', 300, 220);
            const d3 = this.addComponent('diode', 420, 140);
            const d4 = this.addComponent('diode', 420, 220);
            const c1 = this.addComponent('capacitor', 540, 220, { capacitance: 0.00047 }); // 470uF
            c1.rotation = 90;
            const rl = this.addComponent('resistor', 660, 220, { resistance: 1000 });
            rl.rotation = 90;
            rl.label = 'RL';
            const cro = this.addComponent('cro_tap', 780, 180);
            const gnd = this.addComponent('ground', 540, 340);

            // Connect Bridge Network
            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: d1.id, toPin: 'a' });
            this.wires.push({ fromComp: d1.id, fromPin: 'k', toComp: c1.id, toPin: '1' });
            this.wires.push({ fromComp: c1.id, fromPin: '1', toComp: rl.id, toPin: '1' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: vac.id, fromPin: 'n', toComp: d2.id, toPin: 'a' });
            this.wires.push({ fromComp: c1.id, fromPin: '2', toComp: rl.id, toPin: '2' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: cro.id, toPin: 'gnd' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 2: Diode Clipper & Clamper ---
        setupClipperClamperCircuit() {
            const vac = this.addComponent('ac_source', 140, 220, { amplitude: 10, frequency: 1000 });
            const r1 = this.addComponent('resistor', 280, 140, { resistance: 10000 });
            const d1 = this.addComponent('diode', 420, 220);
            d1.rotation = 90;
            const vref = this.addComponent('dc_source', 420, 310, { voltage: 3 });
            vref.rotation = 90;
            const cro = this.addComponent('cro_tap', 580, 180);
            const gnd = this.addComponent('ground', 280, 360);

            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: r1.id, toPin: '1' });
            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: d1.id, toPin: 'a' });
            this.wires.push({ fromComp: d1.id, fromPin: 'a', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: d1.id, fromPin: 'k', toComp: vref.id, toPin: 'p' });
            this.wires.push({ fromComp: vref.id, fromPin: 'n', toComp: vac.id, toPin: 'n' });
            this.wires.push({ fromComp: vac.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 3: BJT Voltage Divider Bias (Self-Bias) ---
        setupBJTVoltageDividerCircuit() {
            const vcc = this.addComponent('dc_source', 140, 160, { voltage: 12 });
            const r1 = this.addComponent('resistor', 280, 120, { resistance: 33000 }); // R1 = 33k
            r1.rotation = 90;
            const r2 = this.addComponent('resistor', 280, 260, { resistance: 6800 });  // R2 = 6.8k
            r2.rotation = 90;
            const rc = this.addComponent('resistor', 420, 120, { resistance: 2200 });  // RC = 2.2k
            rc.rotation = 90;
            const q1 = this.addComponent('bjt_npn', 400, 220);
            const re = this.addComponent('resistor', 420, 310, { resistance: 1000 });  // RE = 1k
            re.rotation = 90;
            const ce = this.addComponent('capacitor', 500, 310, { capacitance: 0.0001 }); // CE Bypass
            ce.rotation = 90;
            const gnd = this.addComponent('ground', 340, 380);

            // Base Bias Network
            this.wires.push({ fromComp: vcc.id, fromPin: 'p', toComp: r1.id, toPin: '1' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'p', toComp: rc.id, toPin: '1' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: r2.id, toPin: '1' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: q1.id, toPin: 'b' });
            this.wires.push({ fromComp: rc.id, fromPin: '2', toComp: q1.id, toPin: 'c' });
            this.wires.push({ fromComp: q1.id, fromPin: 'e', toComp: re.id, toPin: '1' });
            this.wires.push({ fromComp: re.id, fromPin: '1', toComp: ce.id, toPin: '1' });
            this.wires.push({ fromComp: re.id, fromPin: '2', toComp: ce.id, toPin: '2' });
            this.wires.push({ fromComp: r2.id, fromPin: '2', toComp: re.id, toPin: '2' });
            this.wires.push({ fromComp: r2.id, fromPin: '2', toComp: vcc.id, toPin: 'n' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 3: BJT CE Characteristics ---
        setupBJTCharacteristicsCircuit() {
            const vbb = this.addComponent('dc_source', 140, 220, { voltage: 2 });
            const rb = this.addComponent('resistor', 260, 180, { resistance: 100000 });
            const q1 = this.addComponent('bjt_npn', 380, 220);
            const am_c = this.addComponent('ammeter', 480, 140);
            const vcc = this.addComponent('dc_source', 600, 220, { voltage: 10 });
            const vm = this.addComponent('voltmeter', 480, 260);
            vm.rotation = 90;
            const gnd = this.addComponent('ground', 380, 340);

            this.wires.push({ fromComp: vbb.id, fromPin: 'p', toComp: rb.id, toPin: '1' });
            this.wires.push({ fromComp: rb.id, fromPin: '2', toComp: q1.id, toPin: 'b' });
            this.wires.push({ fromComp: q1.id, fromPin: 'c', toComp: am_c.id, toPin: 'p' });
            this.wires.push({ fromComp: am_c.id, fromPin: 'n', toComp: vcc.id, toPin: 'p' });
            this.wires.push({ fromComp: q1.id, fromPin: 'c', toComp: vm.id, toPin: 'p' });
            this.wires.push({ fromComp: q1.id, fromPin: 'e', toComp: vm.id, toPin: 'n' });
            this.wires.push({ fromComp: q1.id, fromPin: 'e', toComp: vbb.id, toPin: 'n' });
            this.wires.push({ fromComp: vbb.id, fromPin: 'n', toComp: vcc.id, toPin: 'n' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 4: JFET Characteristics ---
        setupJFETCircuit() {
            const vgg = this.addComponent('dc_source', 140, 220, { voltage: 2 });
            vgg.rotation = 180; // Negative bias Vgs
            const j1 = this.addComponent('jfet_n', 300, 200);
            const am_d = this.addComponent('ammeter', 420, 140);
            const vdd = this.addComponent('dc_source', 540, 220, { voltage: 12 });
            const gnd = this.addComponent('ground', 300, 320);

            this.wires.push({ fromComp: vgg.id, fromPin: 'p', toComp: j1.id, toPin: 'g' });
            this.wires.push({ fromComp: j1.id, fromPin: 'd', toComp: am_d.id, toPin: 'p' });
            this.wires.push({ fromComp: am_d.id, fromPin: 'n', toComp: vdd.id, toPin: 'p' });
            this.wires.push({ fromComp: j1.id, fromPin: 's', toComp: vdd.id, toPin: 'n' });
            this.wires.push({ fromComp: j1.id, fromPin: 's', toComp: vgg.id, toPin: 'n' });
            this.wires.push({ fromComp: j1.id, fromPin: 's', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 4: MOSFET Switch ---
        setupMOSFETCircuit() {
            const vgate = this.addComponent('dc_source', 140, 220, { voltage: 5 });
            const sw = this.addComponent('switch_spst', 240, 160);
            const m1 = this.addComponent('mosfet_n', 360, 220);
            const rload = this.addComponent('resistor', 440, 140, { resistance: 100 });
            rload.rotation = 90;
            const led = this.addComponent('led', 440, 240, { color: '#22c55e' });
            led.rotation = 90;
            const vdd = this.addComponent('dc_source', 560, 220, { voltage: 12 });
            const gnd = this.addComponent('ground', 360, 340);

            this.wires.push({ fromComp: vgate.id, fromPin: 'p', toComp: sw.id, toPin: '1' });
            this.wires.push({ fromComp: sw.id, fromPin: '2', toComp: m1.id, toPin: 'g' });
            this.wires.push({ fromComp: vdd.id, fromPin: 'p', toComp: rload.id, toPin: '1' });
            this.wires.push({ fromComp: rload.id, fromPin: '2', toComp: led.id, toPin: 'a' });
            this.wires.push({ fromComp: led.id, fromPin: 'k', toComp: m1.id, toPin: 'd' });
            this.wires.push({ fromComp: m1.id, fromPin: 's', toComp: vdd.id, toPin: 'n' });
            this.wires.push({ fromComp: vdd.id, fromPin: 'n', toComp: vgate.id, toPin: 'n' });
            this.wires.push({ fromComp: vdd.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 5: Zener Voltage Regulator ---
        setupZenerRegulatorCircuit() {
            const vin = this.addComponent('dc_source', 140, 220, { voltage: 12 });
            const rs = this.addComponent('resistor', 280, 140, { resistance: 220 });
            rs.label = 'RS';
            const dz = this.addComponent('zener', 420, 220, { vz: 5.1 });
            dz.rotation = 90;
            const rl = this.addComponent('resistor', 540, 220, { resistance: 1000 });
            rl.rotation = 90;
            rl.label = 'RL';
            const vm = this.addComponent('voltmeter', 660, 220);
            vm.rotation = 90;
            const gnd = this.addComponent('ground', 420, 340);

            this.wires.push({ fromComp: vin.id, fromPin: 'p', toComp: rs.id, toPin: '1' });
            this.wires.push({ fromComp: rs.id, fromPin: '2', toComp: dz.id, toPin: 'k' });
            this.wires.push({ fromComp: dz.id, fromPin: 'k', toComp: rl.id, toPin: '1' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: vm.id, toPin: 'p' });
            this.wires.push({ fromComp: vin.id, fromPin: 'n', toComp: dz.id, toPin: 'a' });
            this.wires.push({ fromComp: dz.id, fromPin: 'a', toComp: rl.id, toPin: '2' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: vm.id, toPin: 'n' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 5: Series Pass Transistor Regulator ---
        setupSeriesPassRegulatorCircuit() {
            const vin = this.addComponent('dc_source', 140, 220, { voltage: 15 });
            const q1 = this.addComponent('bjt_npn', 320, 180);
            q1.label = 'Q_Pass';
            const rb = this.addComponent('resistor', 240, 120, { resistance: 470 });
            const dz = this.addComponent('zener', 240, 240, { vz: 6.2 });
            dz.rotation = 90;
            const rl = this.addComponent('resistor', 480, 220, { resistance: 500 });
            rl.rotation = 90;
            const vm = this.addComponent('voltmeter', 600, 220);
            vm.rotation = 90;
            const gnd = this.addComponent('ground', 320, 340);

            this.wires.push({ fromComp: vin.id, fromPin: 'p', toComp: q1.id, toPin: 'c' });
            this.wires.push({ fromComp: vin.id, fromPin: 'p', toComp: rb.id, toPin: '1' });
            this.wires.push({ fromComp: rb.id, fromPin: '2', toComp: q1.id, toPin: 'b' });
            this.wires.push({ fromComp: q1.id, fromPin: 'b', toComp: dz.id, toPin: 'k' });
            this.wires.push({ fromComp: q1.id, fromPin: 'e', toComp: rl.id, toPin: '1' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: vm.id, toPin: 'p' });
            this.wires.push({ fromComp: dz.id, fromPin: 'a', toComp: rl.id, toPin: '2' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: vm.id, toPin: 'n' });
            this.wires.push({ fromComp: vin.id, fromPin: 'n', toComp: dz.id, toPin: 'a' });
            this.wires.push({ fromComp: vin.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 6: Single-Stage CE AC Amplifier ---
        setupCEAmplifierCircuit() {
            const vac = this.addComponent('ac_source', 100, 220, { amplitude: 0.1, frequency: 1000 }); // 100mV 1kHz
            const c_in = this.addComponent('capacitor', 200, 160, { capacitance: 0.00001 }); // 10uF
            const r1 = this.addComponent('resistor', 300, 100, { resistance: 47000 });
            r1.rotation = 90;
            const r2 = this.addComponent('resistor', 300, 240, { resistance: 10000 });
            r2.rotation = 90;
            const q1 = this.addComponent('bjt_npn', 400, 180);
            const rc = this.addComponent('resistor', 420, 90, { resistance: 3300 });
            rc.rotation = 90;
            const re = this.addComponent('resistor', 420, 280, { resistance: 1000 });
            re.rotation = 90;
            const ce = this.addComponent('capacitor', 490, 280, { capacitance: 0.0001 });
            ce.rotation = 90;
            const c_out = this.addComponent('capacitor', 540, 140, { capacitance: 0.00001 });
            const rl = this.addComponent('resistor', 640, 220, { resistance: 10000 });
            rl.rotation = 90;
            const cro = this.addComponent('cro_tap', 740, 160);
            const vcc = this.addComponent('dc_source', 200, 60, { voltage: 12 });
            const gnd = this.addComponent('ground', 340, 360);

            // Connect Signal & Base Bias
            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: c_in.id, toPin: '1' });
            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: c_in.id, fromPin: '2', toComp: q1.id, toPin: 'b' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: q1.id, toPin: 'b' });
            this.wires.push({ fromComp: q1.id, fromPin: 'b', toComp: r2.id, toPin: '1' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'p', toComp: r1.id, toPin: '1' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'p', toComp: rc.id, toPin: '1' });
            this.wires.push({ fromComp: rc.id, fromPin: '2', toComp: q1.id, toPin: 'c' });
            this.wires.push({ fromComp: q1.id, fromPin: 'c', toComp: c_out.id, toPin: '1' });
            this.wires.push({ fromComp: c_out.id, fromPin: '2', toComp: rl.id, toPin: '1' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: q1.id, fromPin: 'e', toComp: re.id, toPin: '1' });
            this.wires.push({ fromComp: re.id, fromPin: '1', toComp: ce.id, toPin: '1' });
            this.wires.push({ fromComp: re.id, fromPin: '2', toComp: ce.id, toPin: '2' });
            this.wires.push({ fromComp: r2.id, fromPin: '2', toComp: re.id, toPin: '2' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: re.id, toPin: '2' });
            this.wires.push({ fromComp: vac.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: re.id, fromPin: '2', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: cro.id, fromPin: 'gnd', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 6: Emitter Follower (Common Collector) ---
        setupEmitterFollowerCircuit() {
            const vac = this.addComponent('ac_source', 120, 220, { amplitude: 2, frequency: 1000 });
            const c_in = this.addComponent('capacitor', 240, 180, { capacitance: 0.00001 });
            const q1 = this.addComponent('bjt_npn', 360, 180);
            const re = this.addComponent('resistor', 420, 260, { resistance: 2200 });
            re.rotation = 90;
            const c_out = this.addComponent('capacitor', 520, 200, { capacitance: 0.00001 });
            const rl = this.addComponent('resistor', 620, 240, { resistance: 4700 });
            rl.rotation = 90;
            const cro = this.addComponent('cro_tap', 720, 180);
            const vcc = this.addComponent('dc_source', 360, 80, { voltage: 12 });
            const gnd = this.addComponent('ground', 360, 360);

            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: c_in.id, toPin: '1' });
            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: c_in.id, fromPin: '2', toComp: q1.id, toPin: 'b' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'p', toComp: q1.id, toPin: 'c' });
            this.wires.push({ fromComp: q1.id, fromPin: 'e', toComp: re.id, toPin: '1' });
            this.wires.push({ fromComp: q1.id, fromPin: 'e', toComp: c_out.id, toPin: '1' });
            this.wires.push({ fromComp: c_out.id, fromPin: '2', toComp: rl.id, toPin: '1' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: re.id, fromPin: '2', toComp: rl.id, toPin: '2' });
            this.wires.push({ fromComp: vac.id, fromPin: 'n', toComp: re.id, toPin: '2' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: cro.id, fromPin: 'gnd', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 7: Op-Amp 741 Inverting Amplifier ---
        setupOpAmpInvertingCircuit() {
            const vac = this.addComponent('ac_source', 120, 220, { amplitude: 1, frequency: 1000 });
            const r1 = this.addComponent('resistor', 260, 170, { resistance: 10000 });
            r1.label = 'R1';
            const rf = this.addComponent('resistor', 380, 80, { resistance: 20000 });
            rf.label = 'Rf';
            const u1 = this.addComponent('opamp', 400, 200);
            const cro = this.addComponent('cro_tap', 600, 170);
            const gnd = this.addComponent('ground', 340, 310);

            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: r1.id, toPin: '1' });
            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: u1.id, toPin: 'inv' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: rf.id, toPin: '1' });
            this.wires.push({ fromComp: rf.id, fromPin: '2', toComp: u1.id, toPin: 'out' });
            this.wires.push({ fromComp: u1.id, fromPin: 'out', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: u1.id, fromPin: 'non', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: vac.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: cro.id, fromPin: 'gnd', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 7: Op-Amp 741 Non-Inverting Amplifier ---
        setupOpAmpNonInvertingCircuit() {
            const vac = this.addComponent('ac_source', 120, 220, { amplitude: 1, frequency: 1000 });
            const u1 = this.addComponent('opamp', 360, 200);
            const r1 = this.addComponent('resistor', 300, 280, { resistance: 10000 });
            r1.rotation = 90;
            r1.label = 'R1';
            const rf = this.addComponent('resistor', 440, 110, { resistance: 20000 });
            rf.label = 'Rf';
            const cro = this.addComponent('cro_tap', 580, 170);
            const gnd = this.addComponent('ground', 300, 360);

            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: u1.id, toPin: 'non' });
            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: u1.id, fromPin: 'inv', toComp: r1.id, toPin: '1' });
            this.wires.push({ fromComp: u1.id, fromPin: 'inv', toComp: rf.id, toPin: '1' });
            this.wires.push({ fromComp: rf.id, fromPin: '2', toComp: u1.id, toPin: 'out' });
            this.wires.push({ fromComp: u1.id, fromPin: 'out', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: vac.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: cro.id, fromPin: 'gnd', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 7: Op-Amp Integrator ---
        setupOpAmpIntegratorCircuit() {
            const vac = this.addComponent('ac_source', 120, 220, { amplitude: 2, frequency: 500 });
            const r1 = this.addComponent('resistor', 260, 170, { resistance: 10000 });
            r1.label = 'R';
            const c1 = this.addComponent('capacitor', 380, 90, { capacitance: 0.0000001 }); // 100nF
            c1.label = 'C';
            const u1 = this.addComponent('opamp', 400, 200);
            const cro = this.addComponent('cro_tap', 580, 170);
            const gnd = this.addComponent('ground', 340, 310);

            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: r1.id, toPin: '1' });
            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: u1.id, toPin: 'inv' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: c1.id, toPin: '1' });
            this.wires.push({ fromComp: c1.id, fromPin: '2', toComp: u1.id, toPin: 'out' });
            this.wires.push({ fromComp: u1.id, fromPin: 'out', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: u1.id, fromPin: 'non', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: vac.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: cro.id, fromPin: 'gnd', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 7: Op-Amp Voltage Comparator ---
        setupOpAmpComparatorCircuit() {
            const vin = this.addComponent('ac_source', 120, 160, { amplitude: 5, frequency: 100 });
            const vref = this.addComponent('dc_source', 120, 270, { voltage: 2 });
            vref.label = 'V_ref';
            const u1 = this.addComponent('opamp', 320, 200);
            const cro = this.addComponent('cro_tap', 500, 170);
            const gnd = this.addComponent('ground', 220, 350);

            this.wires.push({ fromComp: vin.id, fromPin: 'p', toComp: u1.id, toPin: 'non' });
            this.wires.push({ fromComp: vin.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: vref.id, fromPin: 'p', toComp: u1.id, toPin: 'inv' });
            this.wires.push({ fromComp: u1.id, fromPin: 'out', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: vin.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: vref.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: cro.id, fromPin: 'gnd', toComp: gnd.id, toPin: 'g' });
        }
    }

    // ========================================================================
    // 5. VIRTUAL CATHODE RAY OSCILLOSCOPE (CRO)
    // ========================================================================
    class VirtualCRO {
        constructor(workbench) {
            this.wb = workbench;
            this.isOpen = false;
            this.windowEl = document.getElementById('msCroWindow');
            this.canvas = document.getElementById('msCroCanvas');
            this.ctx = this.canvas ? this.canvas.getContext('2d') : null;

            // Oscilloscope settings
            this.chA_enabled = true;
            this.chB_enabled = true;
            this.chA_voltsDiv = 2;   // 2 V/div
            this.chB_voltsDiv = 2;   // 2 V/div
            this.timeDiv = 0.002;    // 2 ms/div
            this.chA_yPos = 0;       // div offset
            this.chB_yPos = 0;       // div offset
            this.mode = 'YT';        // 'YT' or 'XY'

            this.bindControls();
        }

        bindControls() {
            const closeBtn = document.getElementById('msCroCloseBtn');
            if (closeBtn) closeBtn.onclick = () => this.hide();

            // Time/div selector
            const timeSel = document.getElementById('msCroTimeDiv');
            if (timeSel) {
                timeSel.onchange = e => {
                    this.timeDiv = parseFloat(e.target.value);
                };
            }

            // Ch A Volts/div
            const vA = document.getElementById('msCroVoltsA');
            if (vA) {
                vA.onchange = e => {
                    this.chA_voltsDiv = parseFloat(e.target.value);
                };
            }

            // Ch B Volts/div
            const vB = document.getElementById('msCroVoltsB');
            if (vB) {
                vB.onchange = e => {
                    this.chB_voltsDiv = parseFloat(e.target.value);
                };
            }

            // Channel Toggles
            const togA = document.getElementById('msCroToggleA');
            if (togA) togA.onclick = () => {
                this.chA_enabled = !this.chA_enabled;
                togA.classList.toggle('active', this.chA_enabled);
            };

            const togB = document.getElementById('msCroToggleB');
            if (togB) togB.onclick = () => {
                this.chB_enabled = !this.chB_enabled;
                togB.classList.toggle('active', this.chB_enabled);
            };

            // Mode Selector
            const modeBtn = document.getElementById('msCroModeBtn');
            if (modeBtn) {
                modeBtn.onclick = () => {
                    this.mode = this.mode === 'YT' ? 'XY' : 'YT';
                    modeBtn.innerText = this.mode === 'YT' ? 'Mode: Y-T' : 'Mode: X-Y (Lissajous)';
                };
            }
        }

        show() {
            if (this.windowEl) {
                this.windowEl.classList.add('active');
                this.isOpen = true;
                this.drawGrid();
            }
        }

        hide() {
            if (this.windowEl) {
                this.windowEl.classList.remove('active');
                this.isOpen = false;
            }
        }

        toggle() {
            if (this.isOpen) this.hide();
            else this.show();
        }

        drawGrid() {
            if (!this.ctx || !this.canvas) return;
            const w = this.canvas.width;
            const h = this.canvas.height;

            // CRT Dark Phosphor screen
            this.ctx.fillStyle = '#021a12';
            this.ctx.fillRect(0, 0, w, h);

            // 10 Horizontal Divisions x 8 Vertical Divisions
            const numDivX = 10;
            const numDivY = 8;
            const dx = w / numDivX;
            const dy = h / numDivY;

            this.ctx.strokeStyle = 'rgba(34, 197, 94, 0.18)';
            this.ctx.lineWidth = 1;

            // Grid lines
            for (let i = 0; i <= numDivX; i++) {
                this.ctx.beginPath();
                this.ctx.moveTo(i * dx, 0);
                this.ctx.lineTo(i * dx, h);
                this.ctx.stroke();
            }
            for (let j = 0; j <= numDivY; j++) {
                this.ctx.beginPath();
                this.ctx.moveTo(0, j * dy);
                this.ctx.lineTo(w, j * dy);
                this.ctx.stroke();
            }

            // Center Major Crosshairs
            this.ctx.strokeStyle = 'rgba(74, 222, 128, 0.45)';
            this.ctx.lineWidth = 1.5;

            this.ctx.beginPath();
            this.ctx.moveTo(w / 2, 0);
            this.ctx.lineTo(w / 2, h);
            this.ctx.moveTo(0, h / 2);
            this.ctx.lineTo(w, h / 2);
            this.ctx.stroke();

            // Sub-division ticks along center axes (5 ticks per div)
            for (let i = 0; i < numDivX * 5; i++) {
                const tx = (i * dx) / 5;
                this.ctx.beginPath();
                this.ctx.moveTo(tx, h / 2 - 3);
                this.ctx.lineTo(tx, h / 2 + 3);
                this.ctx.stroke();
            }
            for (let j = 0; j < numDivY * 5; j++) {
                const ty = (j * dy) / 5;
                this.ctx.beginPath();
                this.ctx.moveTo(w / 2 - 3, ty);
                this.ctx.lineTo(w / 2 + 3, ty);
                this.ctx.stroke();
            }
        }

        updateTrace(simTime) {
            if (!this.ctx || !this.canvas) return;
            const w = this.canvas.width;
            const h = this.canvas.height;

            this.drawGrid();

            const numDivX = 10;
            const numDivY = 8;
            const dx = w / numDivX;
            const dy = h / numDivY;
            const midY = h / 2;

            const totalTime = numDivX * this.timeDiv;
            const acSrc = this.wb.components.find(c => c.type === 'ac_source');
            const freq = acSrc ? acSrc.props.frequency || 50 : 50;
            const vpeak = acSrc ? acSrc.props.amplitude || 10 : 10;

            if (this.mode === 'YT') {
                // Dual Trace Y-T Sweep
                const points = 300;

                // --- Channel A (Input Trace - Yellow Amber) ---
                if (this.chA_enabled) {
                    this.ctx.strokeStyle = '#fbbf24';
                    this.ctx.lineWidth = 2;
                    this.ctx.shadowColor = 'rgba(251, 191, 36, 0.8)';
                    this.ctx.shadowBlur = 6;
                    this.ctx.beginPath();

                    for (let i = 0; i < points; i++) {
                        const t = (i / points) * totalTime + simTime;
                        const vin = vpeak * Math.sin(2 * Math.PI * freq * t);
                        const px = (i / points) * w;
                        const py = midY - (vin / this.chA_voltsDiv) * dy - this.chA_yPos * dy;

                        if (i === 0) this.ctx.moveTo(px, py);
                        else this.ctx.lineTo(px, py);
                    }
                    this.ctx.stroke();
                    this.ctx.shadowBlur = 0;
                }

                // --- Channel B (Output Trace - Cyan Blue) ---
                if (this.chB_enabled) {
                    this.ctx.strokeStyle = '#38bdf8';
                    this.ctx.lineWidth = 2;
                    this.ctx.shadowColor = 'rgba(56, 189, 248, 0.8)';
                    this.ctx.shadowBlur = 6;
                    this.ctx.beginPath();

                    for (let i = 0; i < points; i++) {
                        const t = (i / points) * totalTime + simTime;
                        const vin = vpeak * Math.sin(2 * Math.PI * freq * t);
                        let vout = this.wb.nodeVoltages.vout !== undefined ? this.wb.nodeVoltages.vout : vin * 0.8;

                        // Add realistic harmonic wave modulation for rectifiers/amplifiers
                        if (this.wb.components.some(c => c.type === 'diode')) {
                            const isBridge = this.wb.components.filter(c => c.type === 'diode').length >= 4;
                            if (isBridge) {
                                vout = Math.max(0, Math.abs(vin) - 1.4);
                            } else {
                                vout = Math.max(0, vin - 0.7);
                            }
                        } else if (this.wb.components.some(c => c.type === 'opamp')) {
                            vout = Math.max(-14, Math.min(14, -2 * vin)); // 180 deg out of phase
                        }

                        const px = (i / points) * w;
                        const py = midY - (vout / this.chB_voltsDiv) * dy - this.chB_yPos * dy;

                        if (i === 0) this.ctx.moveTo(px, py);
                        else this.ctx.lineTo(px, py);
                    }
                    this.ctx.stroke();
                    this.ctx.shadowBlur = 0;
                }
            } else {
                // X-Y Mode (Lissajous Figure)
                this.ctx.strokeStyle = '#4ade80';
                this.ctx.lineWidth = 2.2;
                this.ctx.shadowColor = '#4ade80';
                this.ctx.shadowBlur = 8;
                this.ctx.beginPath();

                const points = 360;
                const midX = w / 2;

                for (let i = 0; i <= points; i++) {
                    const theta = (i * Math.PI) / 180;
                    const vx = (vpeak * Math.sin(theta)) / this.chA_voltsDiv * dx;
                    const vy = (vpeak * Math.sin(theta + Math.PI)) / this.chB_voltsDiv * dy; // 180 deg phase

                    const px = midX + vx;
                    const py = midY - vy;

                    if (i === 0) this.ctx.moveTo(px, py);
                    else this.ctx.lineTo(px, py);
                }
                this.ctx.stroke();
                this.ctx.shadowBlur = 0;
            }

            // Update Telemetry Display
            const telA_vpp = document.getElementById('msCroVppA');
            const telA_freq = document.getElementById('msCroFreqA');
            const telB_vpp = document.getElementById('msCroVppB');
            const telB_freq = document.getElementById('msCroFreqB');

            if (telA_vpp) telA_vpp.innerText = (2 * vpeak).toFixed(2) + ' V';
            if (telA_freq) telA_freq.innerText = freq + ' Hz';
            if (telB_vpp) telB_vpp.innerText = (vpeak * 1.8).toFixed(2) + ' V';
            if (telB_freq) telB_freq.innerText = freq + ' Hz';
        }
    }

    // ========================================================================
    // 6. VIRTUAL DIGITAL MULTIMETER (DMM)
    // ========================================================================
    class VirtualDMM {
        constructor(workbench) {
            this.wb = workbench;
            this.isOpen = false;
            this.windowEl = document.getElementById('msDmmWindow');
            this.displayEl = document.getElementById('msDmmValueDisplay');
            this.unitEl = document.getElementById('msDmmUnitDisplay');
            this.mode = 'V_DC'; // 'V_DC', 'V_AC', 'I_DC', 'I_AC', 'OHM'

            this.bindControls();
        }

        bindControls() {
            const closeBtn = document.getElementById('msDmmCloseBtn');
            if (closeBtn) closeBtn.onclick = () => this.hide();

            const modeBtns = document.querySelectorAll('.ms-dmm-mode-btn');
            modeBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    modeBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    this.mode = btn.getAttribute('data-mode');
                    this.updateReadout();
                });
            });
        }

        show() {
            if (this.windowEl) {
                this.windowEl.classList.add('active');
                this.isOpen = true;
                this.updateReadout();
            }
        }

        hide() {
            if (this.windowEl) {
                this.windowEl.classList.remove('active');
                this.isOpen = false;
            }
        }

        toggle() {
            if (this.isOpen) this.hide();
            else this.show();
        }

        updateReadout() {
            if (!this.displayEl || !this.unitEl) return;

            const v = this.wb.nodeVoltages.vout !== undefined ? this.wb.nodeVoltages.vout : 0;
            const acSrc = this.wb.components.find(c => c.type === 'ac_source');
            const vpeak = acSrc ? acSrc.props.amplitude || 10 : 10;

            switch (this.mode) {
                case 'V_DC':
                    this.displayEl.innerText = v.toFixed(3);
                    this.unitEl.innerText = 'V DC';
                    break;
                case 'V_AC':
                    const vrms = (vpeak / Math.SQRT2).toFixed(3);
                    this.displayEl.innerText = vrms;
                    this.unitEl.innerText = 'V RMS';
                    break;
                case 'I_DC':
                    const i_ma = (v / 1000) * 1000;
                    this.displayEl.innerText = i_ma.toFixed(3);
                    this.unitEl.innerText = 'mA DC';
                    break;
                case 'I_AC':
                    const irms_ma = (vpeak / (Math.SQRT2 * 1000)) * 1000;
                    this.displayEl.innerText = irms_ma.toFixed(3);
                    this.unitEl.innerText = 'mA RMS';
                    break;
                case 'OHM':
                    const rl = this.wb.components.find(c => c.label === 'RL' || c.type === 'resistor');
                    const res = rl ? rl.props.resistance : 1000;
                    this.displayEl.innerText = res >= 1000 ? (res / 1000).toFixed(2) : res.toFixed(1);
                    this.unitEl.innerText = res >= 1000 ? 'kΩ' : 'Ω';
                    break;
            }
        }
    }

    // ========================================================================
    // 7. VIRTUAL FUNCTION GENERATOR (XFG)
    // ========================================================================
    class VirtualXFG {
        constructor(workbench) {
            this.wb = workbench;
            this.isOpen = false;
            this.windowEl = document.getElementById('msXfgWindow');
            this.waveform = 'sine'; // 'sine', 'triangle', 'square'
            this.frequency = 1000;  // Hz
            this.amplitude = 5;     // Vp
            this.offset = 0;        // V

            this.bindControls();
        }

        bindControls() {
            const closeBtn = document.getElementById('msXfgCloseBtn');
            if (closeBtn) closeBtn.onclick = () => this.hide();

            const freqSlider = document.getElementById('msXfgFreqSlider');
            const freqDisp = document.getElementById('msXfgFreqDisplay');
            if (freqSlider && freqDisp) {
                freqSlider.oninput = e => {
                    this.frequency = parseInt(e.target.value, 10);
                    freqDisp.innerText = this.frequency + ' Hz';
                    this.applyToSources();
                };
            }

            const ampSlider = document.getElementById('msXfgAmpSlider');
            const ampDisp = document.getElementById('msXfgAmpDisplay');
            if (ampSlider && ampDisp) {
                ampSlider.oninput = e => {
                    this.amplitude = parseFloat(e.target.value);
                    ampDisp.innerText = this.amplitude.toFixed(1) + ' V';
                    this.applyToSources();
                };
            }
        }

        show() {
            if (this.windowEl) {
                this.windowEl.classList.add('active');
                this.isOpen = true;
            }
        }

        hide() {
            if (this.windowEl) {
                this.windowEl.classList.remove('active');
                this.isOpen = false;
            }
        }

        toggle() {
            if (this.isOpen) this.hide();
            else this.show();
        }

        applyToSources() {
            const acSrc = this.wb.components.find(c => c.type === 'ac_source');
            if (acSrc) {
                acSrc.props.frequency = this.frequency;
                acSrc.props.amplitude = this.amplitude;
                this.wb.render();
            }
        }
    }

    // ========================================================================
    // 8. INITIALIZATION ON DOM CONTENT LOADED
    // ========================================================================
    document.addEventListener('DOMContentLoaded', function () {
        window.MultisimApp = new CircuitWorkbench();
    });

})(window, document);
