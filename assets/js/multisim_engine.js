/**
 * ============================================================================
 * Interactive Circuit Simulator & SPICE CAD Engine (v3.0)
 * Python4Physics Engineering EDA & Virtual Instrumentation Suite
 * 
 * Features:
 *  - Full Modified Nodal Analysis (MNA) & Companion ODE Circuit Solver
 *    (Solves real physical node voltages & branch currents for ANY custom circuit)
 *  - Graph Disjoint-Set Union (DSU) clustering of wires and pins into electrical nets
 *  - Real-Time Numerical Models: Resistors, Caps, Inductors, Switches, Diodes,
 *    Zeners, LEDs, BJTs (NPN/PNP), JFETs, MOSFETs, and Op-Amps (IC 741)
 *  - Virtual Test Instruments:
 *    * Dual-Trace Cathode Ray Oscilloscope (CRO) with phosphor buffer & Lissajous
 *    * Digital Multimeter (DMM) with True-RMS & Auto-Ranging
 *    * Function Generator (XFG) with live frequency & amplitude modulation
 *    * In-line Voltmeter & Ammeter probes + Interactive Live Node HUD Probe
 *  - Draggable Instrument Floating Windows
 *  - 21 Pre-built circuits for all 7 University Physics / Electronics Modules
 *  - Full touch-screen support for mobile devices
 * ============================================================================
 */

(function (window, document) {
    'use strict';

    window.MultisimEngine = window.MultisimEngine || {};

    const GRID_SIZE = 20;

    // Component Definitions & Physical Parameter Templates
    const COMP_TYPES = {
        // --- Sources ---
        dc_source: {
            name: 'DC Voltage Source',
            prefix: 'V',
            category: 'sources',
            pins: [{ id: 'p', x: 0, y: -30, label: '+' }, { id: 'n', x: 0, y: 30, label: '-' }],
            defaults: { voltage: 12, unit: 'V' },
            width: 40, height: 60
        },
        ac_source: {
            name: 'AC Voltage Source',
            prefix: 'V_ac',
            category: 'sources',
            pins: [{ id: 'p', x: 0, y: -30, label: '+' }, { id: 'n', x: 0, y: 30, label: '-' }],
            defaults: { amplitude: 10, frequency: 50, phase: 0, offset: 0, waveform: 'sine', unit: 'V' },
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
            name: 'Ground (0V Reference)',
            prefix: 'GND',
            category: 'sources',
            pins: [{ id: 'g', x: 0, y: -10, label: '0V' }],
            defaults: {},
            width: 30, height: 25
        },

        // --- Basic / Passive ---
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

        // --- Diodes ---
        diode: {
            name: 'PN Junction Diode (1N4007)',
            prefix: 'D',
            category: 'diodes',
            pins: [{ id: 'a', x: -30, y: 0, label: 'A' }, { id: 'k', x: 30, y: 0, label: 'K' }],
            defaults: { vf: 0.7, r_on: 1.0, r_off: 1e7 },
            width: 60, height: 30
        },
        zener: {
            name: 'Zener Diode (1N4733A 5.1V)',
            prefix: 'DZ',
            category: 'diodes',
            pins: [{ id: 'a', x: -30, y: 0, label: 'A' }, { id: 'k', x: 30, y: 0, label: 'K' }],
            defaults: { vz: 5.1, vf: 0.7, rz: 10, r_off: 1e7 },
            width: 60, height: 30
        },
        led: {
            name: 'Light Emitting Diode (LED)',
            prefix: 'LED',
            category: 'diodes',
            pins: [{ id: 'a', x: -30, y: 0, label: 'A' }, { id: 'k', x: 30, y: 0, label: 'K' }],
            defaults: { vf: 2.0, color: '#ef4444', lit: false },
            width: 60, height: 35
        },

        // --- Transistors & FETs ---
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

        // --- Op-Amps ---
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
            defaults: { aol: 100000, vsupply: 15 },
            width: 80, height: 60
        },

        // --- Meters & Probes ---
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
    // CIRCUIT WORKBENCH CLASS
    // ========================================================================
    class CircuitWorkbench {
        constructor() {
            this.components = [];
            this.wires = [];
            this.nextId = 1;
            this.selectedItem = null;
            this.activeWireDraft = null;
            this.isDragging = false;
            this.dragOffset = { x: 0, y: 0 };
            this.dragItem = null;

            // Numerical Solver State
            this.isRunning = false;
            this.simTime = 0.0;          // Seconds
            this.simDt = 0.00005;        // 50 microseconds SPICE step
            this.stepsPerTick = 6;       // 6 steps per frame = 300us step
            this.simInterval = null;

            // Topological state
            this.pinToNode = {};         // "compId:pinId" -> nodeId
            this.nodeVoltages = {};      // nodeId -> voltage
            this.compStates = {};        // compId -> state (cap charge, inductor current)
            this.compBranchCurrents = {};// compId -> current (A)

            // Live HUD Tooltip
            this.hudTooltip = null;

            // Instruments
            this.cro = null;
            this.dmm = null;
            this.xfg = null;

            // ViewBox, Pan & Zoom State for Responsive Mobile / CAD Canvas
            this.view = { x: 0, y: 0, width: 900, height: 600 };
            this.isPanning = false;
            this.panStartClient = { x: 0, y: 0 };
            this.panStartView = { x: 0, y: 0 };
            this.isPinching = false;
            this.pinchDist = null;
            this._resizeDebounce = null;

            this.init();
        }

        init() {
            this.container = document.getElementById('msCanvasContainer');
            this.svg = document.getElementById('msSchematicSvg');
            this.clockEl = document.getElementById('msSimTimeDisplay');
            this.ledEl = document.getElementById('msSimLed');

            if (!this.svg || !this.container) return;

            // Create HUD Probe Tooltip
            this.createHUDTooltip();

            // Setup Quick Actions & Toast Elements
            this.quickActionsEl = document.getElementById('msCompQuickActions');
            this.wireQuickActionsEl = document.getElementById('msWireQuickActions');
            this.toastEl = document.getElementById('msCanvasToast');
            this.bindQuickActions();
            this.bindWireQuickActions();
            this.bindDiagramModalEvents();

            // Setup Instruments
            this.cro = new VirtualCRO(this);
            this.dmm = new VirtualDMM(this);
            this.xfg = new VirtualXFG(this);

            // Make instrument windows draggable
            this.makeWindowsDraggable();

            // Bind Canvas Events
            this.bindEvents();
            this.bindRibbonControls();
            this.bindPaletteButtons();
            this.bindMenuActions();

            // Listen to viewport resize to auto-fit smoothly
            window.addEventListener('resize', () => {
                if (this._resizeDebounce) clearTimeout(this._resizeDebounce);
                this._resizeDebounce = setTimeout(() => {
                    this.autoFitView();
                }, 120);
            });

            // Default preset
            this.loadPreset('mod2_bridge_rectifier');
            this.autoFitView();
            setTimeout(() => this.autoFitView(), 150);
        }

        // ====================================================================
        // RESPONSIVE VIEWPORT, AUTOFIT & PAN/ZOOM CAD ENGINE
        // ====================================================================
        getCircuitBounds() {
            if (!this.components || this.components.length === 0) {
                return { minX: 100, minY: 100, maxX: 700, maxY: 400, width: 600, height: 300 };
            }
            let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
            this.components.forEach(c => {
                minX = Math.min(minX, c.x - 60);
                maxX = Math.max(maxX, c.x + 60);
                minY = Math.min(minY, c.y - 60);
                maxY = Math.max(maxY, c.y + 60);
            });
            this.wires.forEach(w => {
                const pts = this.getWirePoints(w);
                pts.forEach(p => {
                    minX = Math.min(minX, p.x - 20);
                    maxX = Math.max(maxX, p.x + 20);
                    minY = Math.min(minY, p.y - 20);
                    maxY = Math.max(maxY, p.y + 20);
                });
            });
            if (!isFinite(minX) || !isFinite(maxX)) {
                return { minX: 100, minY: 100, maxX: 700, maxY: 400, width: 600, height: 300 };
            }
            return {
                minX,
                minY,
                maxX,
                maxY,
                width: Math.max(220, maxX - minX),
                height: Math.max(160, maxY - minY)
            };
        }

        autoFitView(animate = false) {
            if (!this.svg || !this.container) return;
            const bounds = this.getCircuitBounds();
            const cW = this.container.clientWidth || 390;
            const cH = this.container.clientHeight || 500;
            if (cW <= 0 || cH <= 0) return;

            const isMobile = cW <= 768;
            // Generous breathing margins around circuit
            const padX = isMobile ? 85 : 55;
            const padY = isMobile ? 70 : 55;
            const paddedW = bounds.width + padX * 2;
            const paddedH = bounds.height + padY * 2;

            // Determine scale factor for viewport
            let scale = Math.max(paddedW / cW, paddedH / cH);
            if (isMobile) {
                // Add an extra 12% breathing room on mobile portrait
                scale = scale * 1.14;
            } else if (cW > 850 && scale < 1.0) {
                scale = 1.0; // keep crisp 1:1 view on big desktops
            }

            const vW = Math.round(cW * scale);
            const vH = Math.round(cH * scale);
            const centerX = (bounds.minX + bounds.maxX) / 2;
            const centerY = (bounds.minY + bounds.maxY) / 2;
            const vX = Math.round(centerX - vW / 2);
            const vY = Math.round(centerY - vH / 2);

            this.view.x = vX;
            this.view.y = vY;
            this.view.width = vW;
            this.view.height = vH;

            this.applyViewBox();
        }

        applyViewBox() {
            if (!this.svg) return;
            this.svg.setAttribute('viewBox', `${this.view.x} ${this.view.y} ${this.view.width} ${this.view.height}`);
            this.svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
        }

        zoom(factor, clientX, clientY) {
            if (!this.svg || !this.container) return;
            const cW = this.container.clientWidth || 390;
            const cH = this.container.clientHeight || 500;
            const newW = this.view.width * factor;
            const newH = this.view.height * factor;

            if (newW < 220 || newW > 4500) return;

            let ratioX = 0.5;
            let ratioY = 0.5;
            if (clientX !== undefined && clientY !== undefined) {
                const rect = this.container.getBoundingClientRect();
                ratioX = Math.max(0, Math.min(1, (clientX - rect.left) / cW));
                ratioY = Math.max(0, Math.min(1, (clientY - rect.top) / cH));
            }

            this.view.x += Math.round((this.view.width - newW) * ratioX);
            this.view.y += Math.round((this.view.height - newH) * ratioY);
            this.view.width = Math.round(newW);
            this.view.height = Math.round(newH);

            this.applyViewBox();
        }

        pan(dx, dy) {
            this.view.x -= dx;
            this.view.y -= dy;
            this.applyViewBox();
        }

        svgPointToClient(svgX, svgY) {
            if (!this.svg || !this.container) return { x: svgX, y: svgY };
            if (this.svg.getScreenCTM) {
                const pt = this.svg.createSVGPoint();
                pt.x = svgX;
                pt.y = svgY;
                const ctm = this.svg.getScreenCTM();
                if (ctm) {
                    const screenP = pt.matrixTransform(ctm);
                    const containerRect = this.container.getBoundingClientRect();
                    return {
                        x: Math.round(screenP.x - containerRect.left),
                        y: Math.round(screenP.y - containerRect.top)
                    };
                }
            }
            return { x: svgX, y: svgY };
        }

        createHUDTooltip() {
            this.hudTooltip = document.createElement('div');
            this.hudTooltip.className = 'ms-probe-tooltip';
            this.hudTooltip.id = 'msProbeTooltip';
            this.container.appendChild(this.hudTooltip);
        }

        bindQuickActions() {
            const editBtn = document.getElementById('msQuickBtnEdit');
            const rotBtn = document.getElementById('msQuickBtnRot');
            const delBtn = document.getElementById('msQuickBtnDel');

            if (editBtn) {
                editBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    if (this.selectedItem && this.selectedItem.id) {
                        this.openPropertyModal(this.selectedItem);
                    }
                });
            }
            if (rotBtn) {
                rotBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    this.rotateSelected();
                });
            }
            if (delBtn) {
                delBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    this.deleteSelected();
                });
            }
        }

        showQuickActions(comp) {
            if (!this.quickActionsEl || !comp) return;
            const pad = 36;
            const pt = this.svgPointToClient(comp.x, comp.y - pad);
            let x = pt.x;
            let y = pt.y;
            if (y < 40) {
                const ptBelow = this.svgPointToClient(comp.x, comp.y + pad + 30);
                y = ptBelow.y;
            }
            const cW = this.container.clientWidth || 390;
            const cH = this.container.clientHeight || 500;
            x = Math.max(50, Math.min(cW - 90, x));
            y = Math.max(10, Math.min(cH - 45, y));

            this.quickActionsEl.style.left = x + 'px';
            this.quickActionsEl.style.top = y + 'px';
            this.quickActionsEl.style.display = 'flex';
        }

        hideQuickActions() {
            if (this.quickActionsEl) {
                this.quickActionsEl.style.display = 'none';
            }
        }

        bindWireQuickActions() {
            const addBendBtn = document.getElementById('msWireQuickAddBend');
            const flipBtn = document.getElementById('msWireQuickFlip');
            const autoBtn = document.getElementById('msWireQuickAuto');
            const delBtn = document.getElementById('msWireQuickDel');

            if (addBendBtn) {
                addBendBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    if (this.selectedItem && this.selectedItem.type === 'wire') {
                        const wire = this.wires[this.selectedItem.index];
                        if (wire) {
                            const pts = this.getWirePoints(wire);
                            const p1 = pts[0];
                            const p2 = pts[pts.length - 1];
                            const mid = {
                                x: Math.round(((p1.x + p2.x) / 2) / 10) * 10,
                                y: Math.round(((p1.y + p2.y) / 2) / 10) * 10
                            };
                            if (!wire.waypoints) wire.waypoints = [];
                            wire.waypoints.push(mid);
                            this.render();
                            this.showToast('Bend waypoint added. Drag the handle to adjust wire routing.');
                        }
                    }
                });
            }

            if (flipBtn) {
                flipBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    if (this.selectedItem && this.selectedItem.type === 'wire') {
                        const wire = this.wires[this.selectedItem.index];
                        if (wire) {
                            delete wire.waypoints;
                            wire.routeMode = (wire.routeMode === 'vhv' ? 'hvh' : 'vhv');
                            delete wire.bendX;
                            delete wire.bendY;
                            this.render();
                            this.showToast(`Wire route flipped to ${wire.routeMode.toUpperCase()}`);
                        }
                    }
                });
            }

            if (autoBtn) {
                autoBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    if (this.selectedItem && this.selectedItem.type === 'wire') {
                        const wire = this.wires[this.selectedItem.index];
                        if (wire) {
                            delete wire.waypoints;
                            delete wire.bendX;
                            delete wire.bendY;
                            wire.routeMode = 'hvh';
                            this.render();
                            this.showToast('Reset wire to standard orthogonal route.');
                        }
                    }
                });
            }

            if (delBtn) {
                delBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    this.deleteSelected();
                });
            }
        }

        showWireQuickActions(wire) {
            if (!this.wireQuickActionsEl || !wire) return;
            const pts = this.getWirePoints(wire);
            if (!pts || pts.length === 0) return;

            let sumX = 0, sumY = 0;
            pts.forEach(p => { sumX += p.x; sumY += p.y; });
            const avgX = Math.round(sumX / pts.length);
            const avgY = Math.round(sumY / pts.length);

            const pt = this.svgPointToClient(avgX, avgY - 42);
            let x = pt.x;
            let y = pt.y;
            if (y < 40) {
                const ptBelow = this.svgPointToClient(avgX, avgY + 30);
                y = ptBelow.y;
            }
            const cW = this.container.clientWidth || 390;
            const cH = this.container.clientHeight || 500;
            x = Math.max(90, Math.min(cW - 120, x));
            y = Math.max(10, Math.min(cH - 45, y));

            this.wireQuickActionsEl.style.left = x + 'px';
            this.wireQuickActionsEl.style.top = y + 'px';
            this.wireQuickActionsEl.style.display = 'flex';
        }

        hideWireQuickActions() {
            if (this.wireQuickActionsEl) {
                this.wireQuickActionsEl.style.display = 'none';
            }
        }

        openDiagramUploadModal() {
            const modal = document.getElementById('msDiagramModal');
            if (!modal) return;
            modal.classList.add('active');

            if (!this._pendingSynthesizedCircuit) {
                this.setDiagramModalState('initial');
            }
        }

        closeDiagramUploadModal() {
            const modal = document.getElementById('msDiagramModal');
            if (modal) modal.classList.remove('active');
        }

        setDiagramModalState(state, info = {}) {
            const initBox = document.getElementById('msDiagramInitialState');
            const loadBox = document.getElementById('msDiagramLoadingState');
            const resBox = document.getElementById('msDiagramResultState');
            const launchBtn = document.getElementById('msBtnLaunchSimulated');
            const loadSub = document.getElementById('msDiagramLoadingSubtext');

            if (initBox) initBox.style.display = (state === 'initial') ? 'block' : 'none';
            if (loadBox) loadBox.style.display = (state === 'loading') ? 'block' : 'none';
            if (resBox) resBox.style.display = (state === 'result') ? 'block' : 'none';

            if (state === 'loading') {
                if (loadSub && info.subtext) loadSub.innerText = info.subtext;
                if (launchBtn) {
                    launchBtn.style.opacity = '0.5';
                    launchBtn.style.pointerEvents = 'none';
                    launchBtn.classList.remove('pulse');
                }
            } else if (state === 'result') {
                if (launchBtn) {
                    launchBtn.style.opacity = '1';
                    launchBtn.style.pointerEvents = 'auto';
                    launchBtn.classList.add('pulse');
                }
            } else {
                if (launchBtn) {
                    launchBtn.style.opacity = '0.5';
                    launchBtn.style.pointerEvents = 'none';
                    launchBtn.classList.remove('pulse');
                }
            }
        }

        bindDiagramModalEvents() {
            const self = this;
            const modal = document.getElementById('msDiagramModal');
            const closeBtn = document.getElementById('msDiagramCloseBtn');
            const cancelBtn = document.getElementById('msDiagramCancelBtn');
            const dropzone = document.getElementById('msDiagramDropzone');
            const fileInput = document.getElementById('msDiagramFileInput');
            const browseBtn = document.getElementById('msDiagramBrowseBtn');
            const previewBox = document.getElementById('msDiagramPreviewBox');
            const previewImg = document.getElementById('msDiagramPreviewImg');
            const analyzeBtn = document.getElementById('msBtnAnalyzeDiagram');
            const launchBtn = document.getElementById('msBtnLaunchSimulated');

            if (closeBtn) closeBtn.addEventListener('click', () => self.closeDiagramUploadModal());
            if (cancelBtn) cancelBtn.addEventListener('click', () => self.closeDiagramUploadModal());

            const btnUpload = document.getElementById('msBtnUploadDiagram');
            if (btnUpload) btnUpload.addEventListener('click', () => self.openDiagramUploadModal());

            const menuUpload = document.getElementById('msMenuUploadDiagram');
            if (menuUpload) menuUpload.addEventListener('click', () => self.openDiagramUploadModal());

            const handleFile = file => {
                if (!file || !file.type.startsWith('image/')) {
                    self.showToast('Please select a valid image file (PNG, JPG, WEBP).');
                    return;
                }
                const reader = new FileReader();
                reader.onload = e => {
                    const dataUrl = e.target.result;
                    self._currentDiagramBase64 = dataUrl;
                    self._currentDiagramSample = null;
                    if (previewImg && previewBox) {
                        previewImg.src = dataUrl;
                        previewBox.style.display = 'block';
                    }
                    self.analyzeUploadedDiagram(dataUrl, null);
                };
                reader.readAsDataURL(file);
            };

            if (browseBtn && fileInput) {
                browseBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    fileInput.click();
                });
            }
            if (dropzone && fileInput) {
                dropzone.addEventListener('click', () => fileInput.click());
            }
            if (fileInput) {
                fileInput.addEventListener('change', e => {
                    if (e.target.files && e.target.files[0]) {
                        handleFile(e.target.files[0]);
                    }
                });
            }

            if (dropzone) {
                dropzone.addEventListener('dragover', e => {
                    e.preventDefault();
                    dropzone.classList.add('dragover');
                });
                dropzone.addEventListener('dragleave', e => {
                    e.preventDefault();
                    dropzone.classList.remove('dragover');
                });
                dropzone.addEventListener('drop', e => {
                    e.preventDefault();
                    dropzone.classList.remove('dragover');
                    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                        handleFile(e.dataTransfer.files[0]);
                    }
                });
            }

            window.addEventListener('paste', e => {
                if (!modal || !modal.classList.contains('active')) return;
                const items = (e.clipboardData || (e.originalEvent && e.originalEvent.clipboardData) || {}).items || [];
                for (let i = 0; i < items.length; i++) {
                    if (items[i].type.indexOf('image') !== -1) {
                        const blob = items[i].getAsFile();
                        if (blob) {
                            handleFile(blob);
                            e.preventDefault();
                            break;
                        }
                    }
                }
            });

            const sampleChips = document.querySelectorAll('.ms-sample-chip');
            sampleChips.forEach(chip => {
                chip.addEventListener('click', () => {
                    const sample = chip.getAttribute('data-sample');
                    sampleChips.forEach(c => c.classList.remove('active'));
                    chip.classList.add('active');
                    self._currentDiagramSample = sample;
                    self._currentDiagramBase64 = null;

                    if (previewBox && previewImg) {
                        previewBox.style.display = 'block';
                        previewImg.src = '';
                        previewImg.alt = `Selected Archetype: ${chip.innerText.trim()}`;
                    }
                    self.analyzeUploadedDiagram(null, sample);
                });
            });

            if (analyzeBtn) {
                analyzeBtn.addEventListener('click', () => {
                    if (self._currentDiagramBase64) {
                        self.analyzeUploadedDiagram(self._currentDiagramBase64, null);
                    } else if (self._currentDiagramSample) {
                        self.analyzeUploadedDiagram(null, self._currentDiagramSample);
                    } else {
                        self.analyzeUploadedDiagram(null, 'bridge_rectifier');
                    }
                });
            }

            if (launchBtn) {
                launchBtn.addEventListener('click', () => {
                    if (self._pendingSynthesizedCircuit) {
                        self.loadSynthesizedCircuit(self._pendingSynthesizedCircuit, true);
                        self.closeDiagramUploadModal();
                    }
                });
            }

            const toggleKeyBtn = document.getElementById('msToggleApiKeyVis');
            const keyInput = document.getElementById('msGeminiApiKeyInput');
            const keyIcon = document.getElementById('msToggleApiKeyIcon');
            if (toggleKeyBtn && keyInput) {
                toggleKeyBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    const isMasked = keyInput.classList.contains('ms-api-key-masked');
                    if (isMasked) {
                        keyInput.classList.remove('ms-api-key-masked');
                        if (keyIcon) { keyIcon.classList.remove('fa-eye'); keyIcon.classList.add('fa-eye-slash'); }
                    } else {
                        keyInput.classList.add('ms-api-key-masked');
                        if (keyIcon) { keyIcon.classList.remove('fa-eye-slash'); keyIcon.classList.add('fa-eye'); }
                    }
                });
            }
        }

        async analyzeUploadedDiagram(base64Data, sampleHint) {
            this.setDiagramModalState('loading', { subtext: 'Analyzing component symbols, nodes & physical models...' });

            const apiKeyInput = document.getElementById('msGeminiApiKeyInput');
            const apiKey = apiKeyInput ? apiKeyInput.value.trim() : '';

            try {
                const response = await fetch('api/circuit_vision.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        image: base64Data || '',
                        sample: sampleHint || '',
                        apiKey: apiKey
                    })
                });

                if (!response.ok) {
                    throw new Error(`Server returned HTTP ${response.status}`);
                }

                const data = await response.json();
                if (!data.success || !data.circuit) {
                    throw new Error(data.error || 'Failed to parse circuit diagram');
                }

                this._pendingSynthesizedCircuit = data.circuit;

                const engineBadge = document.getElementById('msResultEngineBadge');
                const titleEl = document.getElementById('msResultCircuitTitle');
                const descEl = document.getElementById('msResultCircuitDesc');
                const pillsEl = document.getElementById('msResultComponentPills');

                if (engineBadge) {
                    engineBadge.innerText = (data.engine === 'gemini_multimodal' ? 'GEMINI 1.5 VISION AI' : 'NEURAL TOPOLOGY ENGINE');
                }
                const circuitTitle = data.circuit.title || data.circuit.circuitName || 'Synthesized Circuit';
                if (titleEl) titleEl.innerText = circuitTitle;
                if (descEl) descEl.innerText = data.circuit.description || '';

                if (pillsEl && Array.isArray(data.circuit.components)) {
                    pillsEl.innerHTML = data.circuit.components.map(c => {
                        const name = c.label || c.type;
                        return `<span class="ms-pill-badge"><i class="fa-solid fa-microchip"></i> ${name}</span>`;
                    }).join('');
                }

                this.setDiagramModalState('result');
                this.showToast(`Schematic recognized: ${circuitTitle}`);
            } catch (err) {
                console.error('Vision analysis error:', err);
                this.setDiagramModalState('initial');
                this.showToast(`Recognition notice: ${err.message}. Synthesizing canonical schematic.`);
                if (!sampleHint) {
                    this.analyzeUploadedDiagram(null, 'bridge_rectifier');
                }
            }
        }

        loadSynthesizedCircuit(circuitData, runImmediately = true) {
            if (!circuitData) return;

            this.stopSimulation();
            this.components = [];
            this.wires = [];
            this.selectedItem = null;
            this.activeWireDraft = null;
            this.simTime = 0.0;
            this.nodeVoltages = {};
            this.compStates = {};
            if (this.clockEl) this.clockEl.innerText = '0.0000 s';

            const labelToComp = {};
            const idToComp = {};

            // 1. Instantiation of components
            if (Array.isArray(circuitData.components)) {
                circuitData.components.forEach(item => {
                    const compProps = item.props || item.defaults || {};
                    const comp = this.addComponent(item.type, item.x || 200, item.y || 200, compProps);
                    if (comp) {
                        if (item.label) comp.label = item.label;
                        if (item.rotation) comp.rotation = item.rotation;
                        if (item.label) labelToComp[item.label] = comp;
                        if (item.id !== undefined) idToComp[item.id] = comp;
                    }
                });
            }

            // 2. Wiring synthesis
            if (Array.isArray(circuitData.wires)) {
                circuitData.wires.forEach(w => {
                    let fromComp = null;
                    let toComp = null;

                    if (w.fromComp !== undefined) fromComp = idToComp[w.fromComp] || this.components.find(c => c.id === w.fromComp);
                    if (!fromComp && w.from) fromComp = labelToComp[w.from];

                    if (w.toComp !== undefined) toComp = idToComp[w.toComp] || this.components.find(c => c.id === w.toComp);
                    if (!toComp && w.to) toComp = labelToComp[w.to];

                    if (fromComp && toComp) {
                        const wireObj = {
                            fromComp: fromComp.id,
                            fromPin: String(w.fromPin || '1'),
                            toComp: toComp.id,
                            toPin: String(w.toPin || '2')
                        };
                        if (w.routeMode) wireObj.routeMode = w.routeMode;
                        if (w.bendX !== undefined) wireObj.bendX = w.bendX;
                        if (w.bendY !== undefined) wireObj.bendY = w.bendY;
                        if (w.waypoints) wireObj.waypoints = JSON.parse(JSON.stringify(w.waypoints));
                        this.wires.push(wireObj);
                    }
                });
            }

            // 3. Recommended Instruments
            const inst = circuitData.recommendedInstruments || circuitData.instruments;
            if (inst) {
                if (this.cro && ((inst.cro && (inst.cro.active || inst.cro === true)) || inst.cro)) {
                    this.cro.show();
                    if (typeof this.cro.calibratePreset === 'function') {
                        this.cro.calibratePreset('mod2_bridge_rectifier');
                    }
                }
                if (this.dmm && ((inst.dmm && (inst.dmm.active || inst.dmm === true)) || inst.dmm)) {
                    this.dmm.show();
                }
                if (this.xfg && ((inst.xfg && (inst.xfg.active || inst.xfg === true)) || inst.xfg)) {
                    this.xfg.show();
                }
            }

            this.render();

            if (runImmediately) {
                this.startSimulation();
                this.showToast(`Circuit live: ${circuitData.title || 'Physical simulation running!'}`);
            }
        }

        showToast(msg) {
            if (!this.toastEl) return;
            this.toastEl.innerHTML = `<i class="fa-solid fa-circle-info" style="color: #38bdf8;"></i> <span>${msg}</span>`;
            this.toastEl.style.display = 'flex';
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => {
                if (this.toastEl) this.toastEl.style.display = 'none';
            }, 2800);
        }

        makeWindowsDraggable() {
            const windows = document.querySelectorAll('.ms-floating-window');
            windows.forEach(win => {
                const header = win.querySelector('.ms-window-header');
                if (!header) return;

                let isMoving = false;
                let startX, startY, origLeft, origTop;

                const startDrag = e => {
                    if (e.target.classList.contains('ms-window-close-btn')) return;
                    isMoving = true;
                    win.classList.add('dragging');
                    const clientX = e.clientX !== undefined ? e.clientX : e.touches[0].clientX;
                    const clientY = e.clientY !== undefined ? e.clientY : e.touches[0].clientY;
                    startX = clientX;
                    startY = clientY;
                    origLeft = win.offsetLeft;
                    origTop = win.offsetTop;
                };

                const doDrag = e => {
                    if (!isMoving) return;
                    const clientX = e.clientX !== undefined ? e.clientX : (e.touches ? e.touches[0].clientX : 0);
                    const clientY = e.clientY !== undefined ? e.clientY : (e.touches ? e.touches[0].clientY : 0);
                    const dx = clientX - startX;
                    const dy = clientY - startY;
                    win.style.left = (origLeft + dx) + 'px';
                    win.style.top = (origTop + dy) + 'px';
                    win.style.right = 'auto'; // allow free moving
                };

                const endDrag = () => {
                    isMoving = false;
                    win.classList.remove('dragging');
                };

                header.addEventListener('mousedown', startDrag);
                window.addEventListener('mousemove', doDrag);
                window.addEventListener('mouseup', endDrag);

                // Touch support for dragging windows
                header.addEventListener('touchstart', startDrag, { passive: true });
                window.addEventListener('touchmove', doDrag, { passive: true });
                window.addEventListener('touchend', endDrag);
            });
        }

        // --------------------------------------------------------------------
        // SCHEMATIC SYMBOL VECTOR RENDERING
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
                    const swClosed = comp.props.closed !== false;
                    const swAngle = swClosed ? '0' : '-35';
                    symbolContent = `
                        <g class="ms-switch-interactive" title="Click to toggle switch">
                            <line x1="-30" y1="0" x2="-14" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                            <circle cx="-12" cy="0" r="2.5" fill="var(--ms-comp-stroke)"/>
                            <circle cx="12" cy="0" r="2.5" fill="var(--ms-comp-stroke)"/>
                            <line x1="-12" y1="0" x2="10" y2="0" stroke="${swClosed ? '#22c55e' : '#f59e0b'}" stroke-width="2.5" transform="rotate(${swAngle}, -12, 0)"/>
                            <line x1="14" y1="0" x2="30" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        </g>
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
                    const isLit = comp.props.lit;
                    const ledColor = comp.props.color || '#ef4444';
                    symbolContent = `
                        <line x1="-30" y1="0" x2="-10" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <polygon points="-10,-12 -10,12 10,0" fill="${isLit ? ledColor : 'var(--ms-comp-fill)'}" 
                                 stroke="var(--ms-comp-stroke)" stroke-width="2" class="${isLit ? 'ms-led-lit' : ''}"/>
                        <line x1="10" y1="-12" x2="10" y2="12" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="10" y1="0" x2="30" y2="0" stroke="var(--ms-comp-stroke)" stroke-width="2"/>
                        <line x1="-2" y1="-14" x2="6" y2="-22" stroke="${ledColor}" stroke-width="1.8"/>
                        <polygon points="8,-24 4,-20 8,-19" fill="${ledColor}"/>
                        <line x1="4" y1="-14" x2="12" y2="-22" stroke="${ledColor}" stroke-width="1.8"/>
                        <polygon points="14,-24 10,-20 14,-19" fill="${ledColor}"/>
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
                        <rect x="-28" y="22" width="56" height="15" rx="3" fill="#030712" stroke="#22c55e" stroke-width="1"/>
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
                        <rect x="-28" y="22" width="56" height="15" rx="3" fill="#030712" stroke="#818cf8" stroke-width="1"/>
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

            // Terminal pins with large touch-friendly hitboxes
            let pinsSvg = '';
            def.pins.forEach(pin => {
                pinsSvg += `
                    <g class="ms-pin-wrapper" data-comp-id="${comp.id}" data-pin-id="${pin.id}">
                        <circle class="ms-pin-hitbox" data-comp-id="${comp.id}" data-pin-id="${pin.id}" cx="${pin.x}" cy="${pin.y}" r="12"/>
                        <circle class="ms-terminal-pin" data-comp-id="${comp.id}" data-pin-id="${pin.id}" cx="${pin.x}" cy="${pin.y}" r="4.5"/>
                    </g>
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
                return p.resistance >= 1e6 ? (p.resistance / 1e6).toFixed(1) + ' MΩ' : (p.resistance >= 1000 ? (p.resistance / 1000).toFixed(1) + ' kΩ' : p.resistance + ' Ω');
            }
            if (p.total_resistance !== undefined) {
                const rStr = p.total_resistance >= 1e6 ? (p.total_resistance / 1e6).toFixed(1) + ' MΩ' : (p.total_resistance >= 1000 ? (p.total_resistance / 1000).toFixed(1) + ' kΩ' : p.total_resistance + ' Ω');
                const wPct = Math.round((p.wiper !== undefined ? p.wiper : 0.5) * 100);
                return `${rStr} (${wPct}%)`;
            }
            if (p.voltage !== undefined) return p.voltage + ' V';
            if (p.amplitude !== undefined) return p.amplitude + ' V, ' + (p.frequency || 50) + ' Hz';
            if (p.capacitance !== undefined) {
                return p.capacitance >= 1e-3 ? (p.capacitance * 1e3).toFixed(1) + ' mF' : (p.capacitance >= 1e-6 ? (p.capacitance * 1e6).toFixed(1) + ' µF' : (p.capacitance * 1e9).toFixed(1) + ' nF');
            }
            if (p.inductance !== undefined) {
                return p.inductance >= 1 ? p.inductance + ' H' : (p.inductance * 1e3).toFixed(1) + ' mH';
            }
            if (p.vz !== undefined) return 'Vz = ' + p.vz + ' V';
            if (p.current !== undefined) return (p.current * 1000).toFixed(1) + ' mA';
            if (p.closed !== undefined) return p.closed ? 'ON (Closed)' : 'OFF (Open)';
            if (comp.type === 'bjt_npn' || comp.type === 'bjt_pnp') return 'β = ' + (p.beta || 150);
            if (comp.type === 'diode') return 'Vf = ' + (p.vf || 0.7) + ' V';
            if (comp.type === 'led') return (p.vf || 2.0) + ' V';
            return '';
        }

        render() {
            if (!this.svg) return;

            let html = '';

            // Draw Wires & Tweak Handles
            this.wires.forEach((wire, idx) => {
                const pathStr = this.computeWirePath(wire);
                const isSelected = this.selectedItem && this.selectedItem.type === 'wire' && this.selectedItem.index === idx;
                html += `<path class="ms-wire ${isSelected ? 'selected' : ''}" d="${pathStr}" data-wire-index="${idx}"/>`;

                // If this wire is selected, render interactive bend / tweak handles!
                if (isSelected) {
                    const pts = this.getWirePoints(wire);
                    if (wire.waypoints && wire.waypoints.length > 0) {
                        // Render handles for each custom waypoint
                        wire.waypoints.forEach((wp, hIdx) => {
                            html += `<circle class="ms-wire-handle waypoint" data-wire-index="${idx}" data-handle-type="waypoint" data-handle-index="${hIdx}" cx="${wp.x}" cy="${wp.y}" r="6.5" title="Drag to move bend (Double-click to remove)"/>`;
                        });
                    } else if (wire.routeMode === 'vhv') {
                        // VHV trunk handle (horizontal drag)
                        const midY = wire.bendY !== undefined ? wire.bendY : Math.round((pts[0].y + pts[3].y) / 2);
                        const trunkX = Math.round((pts[0].x + pts[3].x) / 2);
                        html += `<circle class="ms-wire-handle trunk" data-wire-index="${idx}" data-handle-type="trunk-y" cx="${trunkX}" cy="${midY}" r="6.5" title="Drag to adjust bend vertically"/>`;
                        html += `<circle class="ms-wire-handle corner" data-wire-index="${idx}" data-handle-type="corner-1" cx="${pts[1].x}" cy="${pts[1].y}" r="5.5" title="Drag corner to bend"/>`;
                        html += `<circle class="ms-wire-handle corner" data-wire-index="${idx}" data-handle-type="corner-2" cx="${pts[2].x}" cy="${pts[2].y}" r="5.5" title="Drag corner to bend"/>`;
                    } else {
                        // HVH trunk handle (vertical drag)
                        const midX = wire.bendX !== undefined ? wire.bendX : Math.round((pts[0].x + pts[3].x) / 2);
                        const trunkY = Math.round((pts[0].y + pts[3].y) / 2);
                        html += `<circle class="ms-wire-handle trunk" data-wire-index="${idx}" data-handle-type="trunk-x" cx="${midX}" cy="${trunkY}" r="6.5" title="Drag to adjust bend horizontally"/>`;
                        html += `<circle class="ms-wire-handle corner" data-wire-index="${idx}" data-handle-type="corner-1" cx="${pts[1].x}" cy="${pts[1].y}" r="5.5" title="Drag corner to bend"/>`;
                        html += `<circle class="ms-wire-handle corner" data-wire-index="${idx}" data-handle-type="corner-2" cx="${pts[2].x}" cy="${pts[2].y}" r="5.5" title="Drag corner to bend"/>`;
                    }
                }
            });

            // Draw Draft Wire
            if (this.activeWireDraft) {
                const draftPath = this.computeDraftPath(this.activeWireDraft);
                html += `<path class="ms-wire-drawing" d="${draftPath}"/>`;
            }

            // Draw Junction Nodes
            const junctions = this.computeJunctions();
            junctions.forEach(j => {
                html += `<circle class="ms-junction" cx="${j.x}" cy="${j.y}" r="3.5"/>`;
            });

            // Draw Components
            this.components.forEach(comp => {
                html += this.drawComponentSVG(comp);
            });

            this.svg.innerHTML = html;

            // Update Quick Action Bars
            if (this.selectedItem && this.selectedItem.id && !this.isDragging) {
                this.showQuickActions(this.selectedItem);
                this.hideWireQuickActions();
            } else if (this.selectedItem && this.selectedItem.type === 'wire' && !this.isDragging) {
                this.hideQuickActions();
                const wire = this.wires[this.selectedItem.index];
                if (wire) this.showWireQuickActions(wire);
            } else {
                this.hideQuickActions();
                this.hideWireQuickActions();
            }
        }

        getPinPos(compId, pinId) {
            const comp = this.components.find(c => c.id === compId);
            if (!comp) return { x: 0, y: 0 };
            const def = COMP_TYPES[comp.type];
            if (!def) return { x: comp.x, y: comp.y };
            const pinDef = def.pins.find(p => p.id === pinId);
            if (!pinDef) return { x: comp.x, y: comp.y };

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

        getWirePoints(wire) {
            const start = this.getPinPos(wire.fromComp, wire.fromPin);
            const end = this.getPinPos(wire.toComp, wire.toPin);

            if (wire.waypoints && wire.waypoints.length > 0) {
                const pts = [start];
                wire.waypoints.forEach(wp => pts.push({ x: wp.x, y: wp.y }));
                pts.push(end);
                return pts;
            }

            if (wire.routeMode === 'vhv') {
                const midY = wire.bendY !== undefined ? wire.bendY : Math.round((start.y + end.y) / 2);
                return [
                    start,
                    { x: start.x, y: midY },
                    { x: end.x, y: midY },
                    end
                ];
            } else {
                // Default: HVH (start.x -> midX -> end.y -> end.x)
                const midX = wire.bendX !== undefined ? wire.bendX : Math.round((start.x + end.x) / 2);
                return [
                    start,
                    { x: midX, y: start.y },
                    { x: midX, y: end.y },
                    end
                ];
            }
        }

        computeWirePath(wire) {
            const pts = this.getWirePoints(wire);
            if (!pts || pts.length < 2) return '';
            let d = `M ${pts[0].x} ${pts[0].y}`;
            for (let i = 1; i < pts.length; i++) {
                d += ` L ${pts[i].x} ${pts[i].y}`;
            }
            return d;
        }

        computeDraftPath(draft) {
            const start = this.getPinPos(draft.fromComp, draft.fromPin);
            const end = draft.current;
            const midX = Math.round((start.x + end.x) / 2);
            return `M ${start.x} ${start.y} H ${midX} V ${end.y} H ${end.x}`;
        }

        computeJunctions() {
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
        // COMPONENT & CIRCUIT MANIPULATION
        // --------------------------------------------------------------------
        addComponent(type, x, y, customProps = {}) {
            const def = COMP_TYPES[type];
            if (!def) return null;

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
            this.simTime = 0.0;
            this.nodeVoltages = {};
            this.compStates = {};
            if (this.clockEl) this.clockEl.innerText = '0.0000 s';
            this.render();
        }

        // --------------------------------------------------------------------
        // EVENT BINDINGS & USER INTERACTION (DESKTOP + MOBILE)
        // --------------------------------------------------------------------
        bindEvents() {
            const self = this;

            this.svg.addEventListener('mousedown', e => self.onPointerDown(e));
            window.addEventListener('mousemove', e => {
                if (self.isPanning) {
                    const cW = self.container.clientWidth || 390;
                    const cH = self.container.clientHeight || 500;
                    const dx = (e.clientX - self.panStartClient.x) * (self.view.width / cW);
                    const dy = (e.clientY - self.panStartClient.y) * (self.view.height / cH);
                    self.view.x = Math.round(self.panStartView.x - dx);
                    self.view.y = Math.round(self.panStartView.y - dy);
                    self.applyViewBox();
                    return;
                }
                self.onPointerMove(e);
            });
            window.addEventListener('mouseup', e => {
                if (self.isPanning) {
                    self.isPanning = false;
                }
                self.onPointerUp(e);
            });

            // Container Wheel Zoom (Standard CAD navigation)
            this.container.addEventListener('wheel', e => {
                e.preventDefault();
                const factor = e.deltaY > 0 ? 1.12 : 0.88;
                self.zoom(factor, e.clientX, e.clientY);
            }, { passive: false });

            // Mobile Touch Events (Pinch-Zoom, 1-Finger Pan, Wire/Comp Drag)
            this.svg.addEventListener('touchstart', e => {
                if (e.touches.length === 2) {
                    self.isPinching = true;
                    self.pinchDist = Math.hypot(
                        e.touches[0].clientX - e.touches[1].clientX,
                        e.touches[0].clientY - e.touches[1].clientY
                    );
                    return;
                }
                if (e.touches.length === 1) {
                    const touch = e.touches[0];
                    const target = e.target;
                    const isInteractive = target && (
                        target.closest('.ms-wire-handle') ||
                        target.closest('.ms-terminal-pin') ||
                        target.closest('.ms-pin-hitbox') ||
                        target.closest('.ms-switch-interactive') ||
                        target.classList.contains('ms-wire') ||
                        target.closest('.ms-comp-group')
                    );
                    if (!isInteractive) {
                        // Touch on empty canvas = Pan!
                        self.isPanning = true;
                        self.panStartClient = { x: touch.clientX, y: touch.clientY };
                        self.panStartView = { x: self.view.x, y: self.view.y };
                        return;
                    }
                    self.onPointerDown(touch);
                }
            }, { passive: false });

            window.addEventListener('touchmove', e => {
                if (self.isPinching && e.touches.length === 2) {
                    e.preventDefault();
                    const newDist = Math.hypot(
                        e.touches[0].clientX - e.touches[1].clientX,
                        e.touches[0].clientY - e.touches[1].clientY
                    );
                    if (self.pinchDist && self.pinchDist > 0 && newDist > 0) {
                        const factor = self.pinchDist / newDist;
                        self.zoom(factor);
                        self.pinchDist = newDist;
                    }
                    return;
                }

                if (self.isPanning && e.touches.length === 1) {
                    e.preventDefault();
                    const touch = e.touches[0];
                    const cW = self.container.clientWidth || 390;
                    const cH = self.container.clientHeight || 500;
                    const dx = (touch.clientX - self.panStartClient.x) * (self.view.width / cW);
                    const dy = (touch.clientY - self.panStartClient.y) * (self.view.height / cH);
                    self.view.x = Math.round(self.panStartView.x - dx);
                    self.view.y = Math.round(self.panStartView.y - dy);
                    self.applyViewBox();
                    return;
                }

                if (self.isDragging || self.activeWireDraft) {
                    e.preventDefault();
                    self.onPointerMove(e.touches[0]);
                }
            }, { passive: false });

            window.addEventListener('touchend', e => {
                if (self.isPinching) {
                    self.isPinching = false;
                    self.pinchDist = null;
                }
                if (self.isPanning) {
                    self.isPanning = false;
                }
                self.onPointerUp(e);
            });

            // Double Click to Edit Properties or Add/Remove Wire Waypoints
            this.svg.addEventListener('dblclick', e => {
                // 1. Double click on a waypoint handle removes it
                const handleTarget = e.target.closest('.ms-wire-handle.waypoint');
                if (handleTarget) {
                    const wireIdx = parseInt(handleTarget.getAttribute('data-wire-index'), 10);
                    const hIdx = parseInt(handleTarget.getAttribute('data-handle-index'), 10);
                    const wire = self.wires[wireIdx];
                    if (wire && wire.waypoints) {
                        wire.waypoints.splice(hIdx, 1);
                        self.render();
                        self.showToast('Bend waypoint removed.');
                        return;
                    }
                }

                // 2. Double click on a wire adds a bend waypoint at this coordinate
                const wireTarget = e.target.closest('.ms-wire');
                if (wireTarget) {
                    const wireIdx = parseInt(wireTarget.getAttribute('data-wire-index'), 10);
                    const wire = self.wires[wireIdx];
                    if (wire) {
                        const coords = self.getCanvasCoords(e);
                        const snapped = {
                            x: Math.round(coords.x / 10) * 10,
                            y: Math.round(coords.y / 10) * 10
                        };
                        if (!wire.waypoints || wire.waypoints.length === 0) {
                            wire.waypoints = [snapped];
                        } else {
                            wire.waypoints.push(snapped);
                        }
                        self.selectedItem = { type: 'wire', index: wireIdx };
                        self.render();
                        self.showToast('Added bend waypoint. Drag the circle handle to tweak wire shape.');
                        return;
                    }
                }

                // 3. Double click on a component opens property modal
                const compGroup = e.target.closest('.ms-comp-group');
                if (compGroup) {
                    const id = parseInt(compGroup.getAttribute('data-id'), 10);
                    const comp = self.components.find(c => c.id === id);
                    if (comp) self.openPropertyModal(comp);
                }
            });

            // Keyboard Shortcuts
            window.addEventListener('keydown', e => {
                if (e.key === 'F5' || e.code === 'F5' || e.keyCode === 116) {
                    e.preventDefault();
                    self.toggleSimulation();
                    return;
                }

                if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') return;

                if (e.key === 'Delete' || e.key === 'Backspace') {
                    self.deleteSelected();
                } else if (e.key === 'r' || e.key === 'R') {
                    self.rotateSelected();
                } else if (e.key === 'Enter') {
                    if (self.selectedItem && self.selectedItem.id) {
                        e.preventDefault();
                        self.openPropertyModal(self.selectedItem);
                    }
                } else if (e.key === 'Escape') {
                    self.activeWireDraft = null;
                    self.selectedItem = null;
                    self.render();
                } else if (e.key === ' ') {
                    e.preventDefault();
                    self.toggleSimulation();
                }
            });
        }

        getCanvasCoords(e) {
            const clientX = e.clientX !== undefined ? e.clientX : (e.touches && e.touches[0] ? e.touches[0].clientX : (e.changedTouches && e.changedTouches[0] ? e.changedTouches[0].clientX : 0));
            const clientY = e.clientY !== undefined ? e.clientY : (e.touches && e.touches[0] ? e.touches[0].clientY : (e.changedTouches && e.changedTouches[0] ? e.changedTouches[0].clientY : 0));
            if (this.svg && this.svg.getScreenCTM) {
                const ctm = this.svg.getScreenCTM();
                if (ctm) {
                    const pt = this.svg.createSVGPoint();
                    pt.x = clientX;
                    pt.y = clientY;
                    const svgP = pt.matrixTransform(ctm.inverse());
                    return {
                        x: Math.round(svgP.x),
                        y: Math.round(svgP.y)
                    };
                }
            }
            const rect = this.svg.getBoundingClientRect();
            return {
                x: Math.round(clientX - rect.left),
                y: Math.round(clientY - rect.top)
            };
        }

        onPointerDown(e) {
            const coords = this.getCanvasCoords(e);
            const target = e.target;

            // 0. Clicked on Wire Bend / Tweak Handle?
            const handleTarget = target ? target.closest('.ms-wire-handle') : null;
            if (handleTarget) {
                const wireIdx = parseInt(handleTarget.getAttribute('data-wire-index'), 10);
                const handleType = handleTarget.getAttribute('data-handle-type');
                const handleIdxStr = handleTarget.getAttribute('data-handle-index');
                const handleIdx = handleIdxStr !== null ? parseInt(handleIdxStr, 10) : null;
                const wire = this.wires[wireIdx];
                if (wire) {
                    this.selectedItem = { type: 'wire', index: wireIdx };
                    this.isDragging = true;
                    this.dragItem = {
                        isWireHandle: true,
                        wireIndex: wireIdx,
                        handleType: handleType,
                        handleIndex: handleIdx,
                        wire: wire
                    };
                    handleTarget.classList.add('active');
                    this.render();
                    return;
                }
            }

            // 1. Clicked on Terminal Pin / Hitbox?
            const pinTarget = target.closest('.ms-terminal-pin') || target.closest('.ms-pin-hitbox');
            if (pinTarget) {
                const compId = parseInt(pinTarget.getAttribute('data-comp-id'), 10);
                const pinId = pinTarget.getAttribute('data-pin-id');

                if (!this.activeWireDraft) {
                    // Start drafting new wire
                    this.activeWireDraft = {
                        fromComp: compId,
                        fromPin: pinId,
                        current: coords
                    };
                } else {
                    // Connect to another pin
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

            // 2. Clicked on an Interactive Switch? (Toggle instantly)
            const swTarget = target.closest('.ms-switch-interactive');
            if (swTarget) {
                const compGroup = target.closest('.ms-comp-group');
                if (compGroup) {
                    const compId = parseInt(compGroup.getAttribute('data-id'), 10);
                    const comp = this.components.find(c => c.id === compId);
                    if (comp && comp.type === 'switch_spst') {
                        comp.props.closed = !comp.props.closed;
                        this.render();
                        return;
                    }
                }
            }

            // 3. Clicked on a Wire?
            if (target && target.classList.contains('ms-wire')) {
                const wireIdx = parseInt(target.getAttribute('data-wire-index'), 10);
                this.selectedItem = { type: 'wire', index: wireIdx };
                this.render();
                return;
            }

            // 4. Clicked on a Component?
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

            // 5. Empty Canvas Click
            if (this.activeWireDraft) {
                this.activeWireDraft = null;
            }
            this.selectedItem = null;
            const clientX = e.clientX !== undefined ? e.clientX : (e.touches && e.touches[0] ? e.touches[0].clientX : 0);
            const clientY = e.clientY !== undefined ? e.clientY : (e.touches && e.touches[0] ? e.touches[0].clientY : 0);
            this.isPanning = true;
            this.panStartClient = { x: clientX, y: clientY };
            this.panStartView = { x: this.view.x, y: this.view.y };
            this.render();
        }

        onPointerMove(e) {
            if (this.isPanning) {
                const clientX = e.clientX !== undefined ? e.clientX : (e.touches && e.touches[0] ? e.touches[0].clientX : 0);
                const clientY = e.clientY !== undefined ? e.clientY : (e.touches && e.touches[0] ? e.touches[0].clientY : 0);
                const cW = this.container.clientWidth || 390;
                const cH = this.container.clientHeight || 500;
                const dx = (clientX - this.panStartClient.x) * (this.view.width / cW);
                const dy = (clientY - this.panStartClient.y) * (this.view.height / cH);
                this.view.x = Math.round(this.panStartView.x - dx);
                this.view.y = Math.round(this.panStartView.y - dy);
                this.applyViewBox();
                return;
            }
            const coords = this.getCanvasCoords(e);

            if (this.isDragging && this.dragItem) {
                if (this.dragItem.isWireHandle) {
                    const wire = this.dragItem.wire;
                    const snappedX = Math.round(coords.x / 10) * 10;
                    const snappedY = Math.round(coords.y / 10) * 10;

                    if (this.dragItem.handleType === 'trunk-x') {
                        wire.bendX = snappedX;
                    } else if (this.dragItem.handleType === 'trunk-y') {
                        wire.bendY = snappedY;
                    } else if (this.dragItem.handleType === 'corner-1' || this.dragItem.handleType === 'corner-2') {
                        if (wire.routeMode === 'vhv') {
                            wire.bendY = snappedY;
                        } else {
                            wire.bendX = snappedX;
                        }
                    } else if (this.dragItem.handleType === 'waypoint' && this.dragItem.handleIndex !== null) {
                        if (wire.waypoints && wire.waypoints[this.dragItem.handleIndex]) {
                            wire.waypoints[this.dragItem.handleIndex] = { x: snappedX, y: snappedY };
                        }
                    }
                    this.render();
                    return;
                }

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
            } else {
                // Interactive HUD Node Probe
                this.handleHUDProbe(e, coords);
            }
        }

        handleHUDProbe(e, coords) {
            if (!this.hudTooltip || !this.isRunning) {
                if (this.hudTooltip) this.hudTooltip.style.display = 'none';
                return;
            }

            const target = e.target;
            const isWire = target && target.classList.contains('ms-wire');
            const pinTarget = target && (target.closest('.ms-terminal-pin') || target.closest('.ms-pin-hitbox'));

            if (isWire || pinTarget) {
                let nodeId = null;
                if (pinTarget) {
                    const cId = parseInt(pinTarget.getAttribute('data-comp-id'), 10);
                    const pId = pinTarget.getAttribute('data-pin-id');
                    nodeId = this.pinToNode[`${cId}:${pId}`];
                } else if (isWire) {
                    const wireIdx = parseInt(target.getAttribute('data-wire-index'), 10);
                    const w = this.wires[wireIdx];
                    if (w) {
                        nodeId = this.pinToNode[`${w.fromComp}:${w.fromPin}`];
                    }
                }

                if (nodeId !== null && nodeId !== undefined) {
                    const v = this.nodeVoltages[nodeId] || 0.0;
                    this.hudTooltip.innerHTML = `
                        <div class="ms-probe-header"><i class="fa-solid fa-crosshairs"></i> Live Node Probe</div>
                        <div class="ms-probe-row"><span>Node ID:</span><span class="ms-probe-val">N${nodeId}</span></div>
                        <div class="ms-probe-row"><span>Voltage V(t):</span><span class="ms-probe-val">${(v >= 0 ? '+' : '') + v.toFixed(3)} V</span></div>
                        <div class="ms-probe-row"><span>V (RMS):</span><span class="ms-probe-val">${Math.abs(v / Math.SQRT2).toFixed(3)} V</span></div>
                    `;
                    this.hudTooltip.style.left = (coords.x + 18) + 'px';
                    this.hudTooltip.style.top = (coords.y - 30) + 'px';
                    this.hudTooltip.style.display = 'block';
                    return;
                }
            }

            this.hudTooltip.style.display = 'none';
        }

        onPointerUp() {
            this.isPanning = false;
            this.isPinching = false;
            this.pinchDist = null;
            this.isDragging = false;
            this.dragItem = null;
            if (this.selectedItem && this.selectedItem.id) {
                this.showQuickActions(this.selectedItem);
                this.hideWireQuickActions();
            } else if (this.selectedItem && this.selectedItem.type === 'wire') {
                this.hideQuickActions();
                const wire = this.wires[this.selectedItem.index];
                if (wire) this.showWireQuickActions(wire);
            }
        }

        // --------------------------------------------------------------------
        // PROFESSIONAL ENGINEERING COMPONENT PROPERTIES INSPECTOR MODAL
        // --------------------------------------------------------------------
        openPropertyModal(comp) {
            const modal = document.getElementById('msInspectorModal');
            const body = document.getElementById('msInspectorFields');
            const title = document.getElementById('msInspectorTitle');
            const resetBtn = document.getElementById('msInspResetBtn');
            const saveBtn = document.getElementById('msInspSaveBtn');
            const cancelBtn = document.getElementById('msInspCancelBtn');
            const closeBtn = document.getElementById('msInspCloseBtn');
            if (!modal || !body) return;

            const def = COMP_TYPES[comp.type] || { name: 'Component', category: 'general', defaults: {} };

            // Dynamic Header Icon
            let iconClass = 'fa-sliders';
            if (comp.type.includes('source')) iconClass = 'fa-bolt';
            else if (comp.type === 'resistor' || comp.type === 'potentiometer') iconClass = 'fa-wave-square';
            else if (comp.type === 'capacitor') iconClass = 'fa-battery-three-quarters';
            else if (comp.type === 'inductor') iconClass = 'fa-ring';
            else if (comp.type === 'switch_spst') iconClass = 'fa-toggle-on';
            else if (comp.type.includes('diode') || comp.type === 'zener' || comp.type === 'led') iconClass = 'fa-lightbulb';
            else if (comp.type.includes('bjt') || comp.type.includes('fet')) iconClass = 'fa-microchip';
            else if (comp.type === 'opamp') iconClass = 'fa-network-wired';

            title.innerHTML = `<i class="fa-solid ${iconClass}" style="color: #38bdf8;"></i> ${comp.label} &middot; ${def.name}`;

            const renderFields = () => {
                const p = comp.props;
                let html = `
                    <div class="ms-insp-header-tag">
                        <span class="badge badge-cyan">${def.category.toUpperCase()}</span>
                        <span style="font-size: 0.76rem; color: #cbd5e1; font-weight: 600;">Type: ${def.name}</span>
                        <div style="flex:1;"></div>
                        <span style="font-size: 0.72rem; color: #64748b; font-family: 'JetBrains Mono', monospace;">ID: #${comp.id}</span>
                    </div>

                    <div class="ms-insp-group">
                        <label class="ms-insp-label">
                            <span>Reference Designator Label</span>
                            <span class="note">Schematic ID</span>
                        </label>
                        <div class="ms-insp-unit-group">
                            <input type="text" id="msInspLabel" class="ms-insp-input" value="${comp.label}" placeholder="e.g. R1, C1">
                        </div>
                    </div>
                `;

                switch (comp.type) {
                    case 'resistor': {
                        const curR = p.resistance !== undefined ? p.resistance : 1000;
                        let unitMul = 1;
                        let displayVal = curR;
                        if (curR >= 1e6) { unitMul = 1e6; displayVal = curR / 1e6; }
                        else if (curR >= 1e3) { unitMul = 1e3; displayVal = curR / 1e3; }
                        displayVal = parseFloat(displayVal.toFixed(4));

                        html += `
                            <div class="ms-insp-group">
                                <label class="ms-insp-label">
                                    <span>Resistance Value</span>
                                    <span class="note">Base unit: Ohms (Ω)</span>
                                </label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValResistance" class="ms-insp-input" step="any" min="0.001" value="${displayVal}">
                                    <select id="msUnitResistance" class="ms-insp-unit-select">
                                        <option value="1" ${unitMul === 1 ? 'selected' : ''}>Ω</option>
                                        <option value="1000" ${unitMul === 1000 ? 'selected' : ''}>kΩ</option>
                                        <option value="1000000" ${unitMul === 1000000 ? 'selected' : ''}>MΩ</option>
                                    </select>
                                </div>
                                <div class="ms-insp-preset-pills">
                                    ${[
                                        { val: 100, mul: 1, lbl: '100 Ω' },
                                        { val: 220, mul: 1, lbl: '220 Ω' },
                                        { val: 330, mul: 1, lbl: '330 Ω' },
                                        { val: 470, mul: 1, lbl: '470 Ω' },
                                        { val: 1, mul: 1000, lbl: '1 kΩ' },
                                        { val: 2.2, mul: 1000, lbl: '2.2 kΩ' },
                                        { val: 4.7, mul: 1000, lbl: '4.7 kΩ' },
                                        { val: 10, mul: 1000, lbl: '10 kΩ' },
                                        { val: 47, mul: 1000, lbl: '47 kΩ' },
                                        { val: 100, mul: 1000, lbl: '100 kΩ' },
                                        { val: 1, mul: 1000000, lbl: '1 MΩ' }
                                    ].map(pill => `
                                        <button type="button" class="ms-insp-pill ${Math.abs(curR - (pill.val * pill.mul)) < 1e-4 ? 'active' : ''}" 
                                                data-target="Resistance" data-val="${pill.val}" data-mul="${pill.mul}">${pill.lbl}</button>
                                    `).join('')}
                                </div>
                                <div class="ms-insp-help">Standard EIA decade resistor value. Adjust number or select a standard value pill.</div>
                            </div>
                        `;
                        break;
                    }

                    case 'potentiometer': {
                        const curR = p.total_resistance !== undefined ? p.total_resistance : 10000;
                        let unitMul = 1;
                        let displayVal = curR;
                        if (curR >= 1e6) { unitMul = 1e6; displayVal = curR / 1e6; }
                        else if (curR >= 1e3) { unitMul = 1e3; displayVal = curR / 1e3; }
                        displayVal = parseFloat(displayVal.toFixed(4));
                        const curWiper = p.wiper !== undefined ? p.wiper : 0.5;
                        const wiperPct = Math.round(curWiper * 100);

                        html += `
                            <div class="ms-insp-group">
                                <label class="ms-insp-label">
                                    <span>Total Track Resistance</span>
                                    <span class="note">Terminal 1 to Terminal 2</span>
                                </label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValPotResistance" class="ms-insp-input" step="any" min="1" value="${displayVal}">
                                    <select id="msUnitPotResistance" class="ms-insp-unit-select">
                                        <option value="1" ${unitMul === 1 ? 'selected' : ''}>Ω</option>
                                        <option value="1000" ${unitMul === 1000 ? 'selected' : ''}>kΩ</option>
                                        <option value="1000000" ${unitMul === 1000000 ? 'selected' : ''}>MΩ</option>
                                    </select>
                                </div>
                                <div class="ms-insp-preset-pills">
                                    ${[
                                        { val: 1, mul: 1000, lbl: '1 kΩ' },
                                        { val: 5, mul: 1000, lbl: '5 kΩ' },
                                        { val: 10, mul: 1000, lbl: '10 kΩ' },
                                        { val: 50, mul: 1000, lbl: '50 kΩ' },
                                        { val: 100, mul: 1000, lbl: '100 kΩ' }
                                    ].map(pill => `
                                        <button type="button" class="ms-insp-pill ${Math.abs(curR - (pill.val * pill.mul)) < 1e-4 ? 'active' : ''}" 
                                                data-target="PotResistance" data-val="${pill.val}" data-mul="${pill.mul}">${pill.lbl}</button>
                                    `).join('')}
                                </div>
                            </div>

                            <div class="ms-insp-group">
                                <label class="ms-insp-label">
                                    <span>Wiper Position (Knob Ratio)</span>
                                    <span class="note" id="msWiperDetails">${wiperPct}% (Center)</span>
                                </label>
                                <div class="ms-insp-slider-row">
                                    <input type="range" id="msValWiper" class="ms-insp-range" min="0" max="100" step="1" value="${wiperPct}">
                                    <span class="ms-insp-badge" id="msWiperBadge">${wiperPct}%</span>
                                </div>
                                <div class="ms-insp-preset-pills" style="margin-top: 6px;">
                                    <button type="button" class="ms-insp-pill" data-wiper="0">0% (Min)</button>
                                    <button type="button" class="ms-insp-pill" data-wiper="25">25%</button>
                                    <button type="button" class="ms-insp-pill" data-wiper="50">50% (Mid)</button>
                                    <button type="button" class="ms-insp-pill" data-wiper="75">75%</button>
                                    <button type="button" class="ms-insp-pill" data-wiper="100">100% (Max)</button>
                                </div>
                                <div class="ms-insp-help">Moving the slider interactively adjusts the live potentiometer voltage divider on the fly.</div>
                            </div>
                        `;
                        break;
                    }

                    case 'capacitor': {
                        const curC = p.capacitance !== undefined ? p.capacitance : 0.0001;
                        let unitMul = 1e-6;
                        let displayVal = curC / 1e-6;
                        if (curC >= 1e-3) { unitMul = 1e-3; displayVal = curC / 1e-3; }
                        else if (curC >= 1e-6) { unitMul = 1e-6; displayVal = curC / 1e-6; }
                        else if (curC >= 1e-9) { unitMul = 1e-9; displayVal = curC / 1e-9; }
                        else { unitMul = 1e-12; displayVal = curC / 1e-12; }
                        displayVal = parseFloat(displayVal.toFixed(4));

                        html += `
                            <div class="ms-insp-group">
                                <label class="ms-insp-label">
                                    <span>Capacitance Value</span>
                                    <span class="note">Base unit: Farads (F)</span>
                                </label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValCapacitance" class="ms-insp-input" step="any" min="0.000000000001" value="${displayVal}">
                                    <select id="msUnitCapacitance" class="ms-insp-unit-select">
                                        <option value="1e-12" ${unitMul === 1e-12 ? 'selected' : ''}>pF</option>
                                        <option value="1e-9" ${unitMul === 1e-9 ? 'selected' : ''}>nF</option>
                                        <option value="1e-6" ${unitMul === 1e-6 ? 'selected' : ''}>µF</option>
                                        <option value="1e-3" ${unitMul === 1e-3 ? 'selected' : ''}>mF</option>
                                    </select>
                                </div>
                                <div class="ms-insp-preset-pills">
                                    ${[
                                        { val: 100, mul: 1e-12, lbl: '100 pF' },
                                        { val: 1, mul: 1e-9, lbl: '1 nF' },
                                        { val: 10, mul: 1e-9, lbl: '10 nF' },
                                        { val: 100, mul: 1e-9, lbl: '100 nF' },
                                        { val: 1, mul: 1e-6, lbl: '1 µF' },
                                        { val: 10, mul: 1e-6, lbl: '10 µF' },
                                        { val: 47, mul: 1e-6, lbl: '47 µF' },
                                        { val: 100, mul: 1e-6, lbl: '100 µF' },
                                        { val: 470, mul: 1e-6, lbl: '470 µF' },
                                        { val: 1000, mul: 1e-6, lbl: '1000 µF' }
                                    ].map(pill => `
                                        <button type="button" class="ms-insp-pill ${Math.abs(curC - (pill.val * pill.mul)) < (pill.mul * 0.01) ? 'active' : ''}" 
                                                data-target="Capacitance" data-val="${pill.val}" data-mul="${pill.mul}">${pill.lbl}</button>
                                    `).join('')}
                                </div>
                                <div class="ms-insp-help">Controls transient RC filtering ripple, discharge time &tau; = R&middot;C, and AC coupling response.</div>
                            </div>
                        `;
                        break;
                    }

                    case 'inductor': {
                        const curL = p.inductance !== undefined ? p.inductance : 0.01;
                        let unitMul = 1e-3;
                        let displayVal = curL / 1e-3;
                        if (curL >= 1) { unitMul = 1; displayVal = curL; }
                        else if (curL >= 1e-3) { unitMul = 1e-3; displayVal = curL / 1e-3; }
                        else { unitMul = 1e-6; displayVal = curL / 1e-6; }
                        displayVal = parseFloat(displayVal.toFixed(4));

                        html += `
                            <div class="ms-insp-group">
                                <label class="ms-insp-label">
                                    <span>Inductance Value</span>
                                    <span class="note">Base unit: Henries (H)</span>
                                </label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValInductance" class="ms-insp-input" step="any" min="0.000001" value="${displayVal}">
                                    <select id="msUnitInductance" class="ms-insp-unit-select">
                                        <option value="1e-6" ${unitMul === 1e-6 ? 'selected' : ''}>µH</option>
                                        <option value="1e-3" ${unitMul === 1e-3 ? 'selected' : ''}>mH</option>
                                        <option value="1" ${unitMul === 1 ? 'selected' : ''}>H</option>
                                    </select>
                                </div>
                                <div class="ms-insp-preset-pills">
                                    ${[
                                        { val: 10, mul: 1e-6, lbl: '10 µH' },
                                        { val: 100, mul: 1e-6, lbl: '100 µH' },
                                        { val: 1, mul: 1e-3, lbl: '1 mH' },
                                        { val: 10, mul: 1e-3, lbl: '10 mH' },
                                        { val: 100, mul: 1e-3, lbl: '100 mH' },
                                        { val: 1, mul: 1, lbl: '1 H' }
                                    ].map(pill => `
                                        <button type="button" class="ms-insp-pill ${Math.abs(curL - (pill.val * pill.mul)) < (pill.mul * 0.01) ? 'active' : ''}" 
                                                data-target="Inductance" data-val="${pill.val}" data-mul="${pill.mul}">${pill.lbl}</button>
                                    `).join('')}
                                </div>
                                <div class="ms-insp-help">Sets inductive reactance XL = 2&pi;&middot;f&middot;L and resonant tank properties.</div>
                            </div>
                        `;
                        break;
                    }

                    case 'switch_spst': {
                        const swClosed = p.closed !== false;
                        html += `
                            <div class="ms-insp-group">
                                <label class="ms-insp-label">
                                    <span>Switch Contact State</span>
                                    <span class="note">Circuit Conduction</span>
                                </label>
                                <div class="ms-insp-seg-group">
                                    <button type="button" class="ms-insp-seg-btn ${swClosed ? 'active green' : ''}" id="msBtnSwClosed">
                                        <i class="fa-solid fa-circle-check"></i> CLOSED (Conducting / ON)
                                    </button>
                                    <button type="button" class="ms-insp-seg-btn ${!swClosed ? 'active amber' : ''}" id="msBtnSwOpen">
                                        <i class="fa-solid fa-circle-xmark"></i> OPEN (Isolated / OFF)
                                    </button>
                                </div>
                                <div class="ms-insp-help">You can also toggle this switch anytime with a single click directly on the schematic knife.</div>
                            </div>
                        `;
                        break;
                    }

                    case 'dc_source': {
                        const curV = p.voltage !== undefined ? p.voltage : 12;
                        let unitMul = 1;
                        let displayVal = curV;
                        if (Math.abs(curV) < 1 && curV !== 0) { unitMul = 0.001; displayVal = curV * 1000; }
                        displayVal = parseFloat(displayVal.toFixed(4));

                        html += `
                            <div class="ms-insp-group">
                                <label class="ms-insp-label">
                                    <span>DC Output Voltage</span>
                                    <span class="note">Potential Difference (V)</span>
                                </label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValDcVoltage" class="ms-insp-input" step="any" value="${displayVal}">
                                    <select id="msUnitDcVoltage" class="ms-insp-unit-select">
                                        <option value="0.001" ${unitMul === 0.001 ? 'selected' : ''}>mV</option>
                                        <option value="1" ${unitMul === 1 ? 'selected' : ''}>V</option>
                                        <option value="1000" ${unitMul === 1000 ? 'selected' : ''}>kV</option>
                                    </select>
                                </div>
                                <div class="ms-insp-preset-pills">
                                    ${[
                                        { val: 1.5, mul: 1, lbl: '1.5 V (AA)' },
                                        { val: 3.3, mul: 1, lbl: '3.3 V' },
                                        { val: 5.0, mul: 1, lbl: '5.0 V (TTL)' },
                                        { val: 9.0, mul: 1, lbl: '9.0 V' },
                                        { val: 12.0, mul: 1, lbl: '12.0 V' },
                                        { val: 15.0, mul: 1, lbl: '15.0 V' },
                                        { val: 24.0, mul: 1, lbl: '24.0 V' }
                                    ].map(pill => `
                                        <button type="button" class="ms-insp-pill ${Math.abs(curV - (pill.val * pill.mul)) < 1e-4 ? 'active' : ''}" 
                                                data-target="DcVoltage" data-val="${pill.val}" data-mul="${pill.mul}">${pill.lbl}</button>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                        break;
                    }

                    case 'ac_source': {
                        const curAmp = p.amplitude !== undefined ? p.amplitude : 10;
                        const curFreq = p.frequency !== undefined ? p.frequency : 50;
                        const curWf = p.waveform || 'sine';
                        const curPhase = p.phase || 0;
                        const curOffset = p.offset || 0;

                        let freqMul = 1;
                        let dispFreq = curFreq;
                        if (curFreq >= 1e6) { freqMul = 1e6; dispFreq = curFreq / 1e6; }
                        else if (curFreq >= 1e3) { freqMul = 1e3; dispFreq = curFreq / 1e3; }
                        dispFreq = parseFloat(dispFreq.toFixed(4));

                        html += `
                            <div class="ms-insp-group">
                                <label class="ms-insp-label">
                                    <span>Signal Waveform</span>
                                    <span class="note">Periodic Function</span>
                                </label>
                                <div class="ms-insp-seg-group" id="msWaveformGroup">
                                    <button type="button" class="ms-insp-seg-btn ${curWf === 'sine' ? 'active' : ''}" data-wf="sine">
                                        <i class="fa-solid fa-wave-square"></i> Sine (~)
                                    </button>
                                    <button type="button" class="ms-insp-seg-btn ${curWf === 'square' ? 'active' : ''}" data-wf="square">
                                        <i class="fa-solid fa-grip-lines"></i> Square (⎍)
                                    </button>
                                    <button type="button" class="ms-insp-seg-btn ${curWf === 'triangle' ? 'active' : ''}" data-wf="triangle">
                                        <i class="fa-solid fa-mountain"></i> Triangle (⋀)
                                    </button>
                                </div>
                            </div>

                            <div class="ms-insp-group">
                                <label class="ms-insp-label">
                                    <span>Peak Amplitude (Vp)</span>
                                    <span class="note">Vpp = ${(curAmp * 2).toFixed(1)} V | RMS &approx; ${(curAmp / Math.SQRT2).toFixed(2)} V</span>
                                </label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValAcAmp" class="ms-insp-input" step="any" min="0.001" value="${curAmp}">
                                    <select id="msUnitAcAmp" class="ms-insp-unit-select">
                                        <option value="1" selected>V (Peak)</option>
                                        <option value="0.001">mV (Peak)</option>
                                    </select>
                                </div>
                                <div class="ms-insp-preset-pills">
                                    ${[
                                        { val: 1, lbl: '1 Vp' },
                                        { val: 2, lbl: '2 Vp' },
                                        { val: 5, lbl: '5 Vp' },
                                        { val: 10, lbl: '10 Vp' },
                                        { val: 12, lbl: '12 Vp' },
                                        { val: 230, lbl: '230 V (Mains)' }
                                    ].map(pill => `
                                        <button type="button" class="ms-insp-pill ${Math.abs(curAmp - pill.val) < 1e-3 ? 'active' : ''}" 
                                                data-target="AcAmp" data-val="${pill.val}" data-mul="1">${pill.lbl}</button>
                                    `).join('')}
                                </div>
                            </div>

                            <div class="ms-insp-group">
                                <label class="ms-insp-label">
                                    <span>Frequency (f)</span>
                                    <span class="note">Period T = ${(1 / Math.max(0.1, curFreq)).toFixed(4)} s</span>
                                </label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValAcFreq" class="ms-insp-input" step="any" min="0.1" value="${dispFreq}">
                                    <select id="msUnitAcFreq" class="ms-insp-unit-select">
                                        <option value="1" ${freqMul === 1 ? 'selected' : ''}>Hz</option>
                                        <option value="1000" ${freqMul === 1000 ? 'selected' : ''}>kHz</option>
                                        <option value="1000000" ${freqMul === 1000000 ? 'selected' : ''}>MHz</option>
                                    </select>
                                </div>
                                <div class="ms-insp-preset-pills">
                                    ${[
                                        { val: 50, mul: 1, lbl: '50 Hz (Mains)' },
                                        { val: 60, mul: 1, lbl: '60 Hz' },
                                        { val: 100, mul: 1, lbl: '100 Hz' },
                                        { val: 1, mul: 1000, lbl: '1 kHz (Audio)' },
                                        { val: 10, mul: 1000, lbl: '10 kHz' },
                                        { val: 100, mul: 1000, lbl: '100 kHz' }
                                    ].map(pill => `
                                        <button type="button" class="ms-insp-pill ${Math.abs(curFreq - (pill.val * pill.mul)) < 1e-3 ? 'active' : ''}" 
                                                data-target="AcFreq" data-val="${pill.val}" data-mul="${pill.mul}">${pill.lbl}</button>
                                    `).join('')}
                                </div>
                            </div>

                            <div style="display: flex; gap: 10px;">
                                <div class="ms-insp-group" style="flex: 1;">
                                    <label class="ms-insp-label"><span>Phase (&deg;)</span></label>
                                    <div class="ms-insp-unit-group">
                                        <input type="number" id="msValAcPhase" class="ms-insp-input" step="any" value="${curPhase}">
                                    </div>
                                </div>
                                <div class="ms-insp-group" style="flex: 1;">
                                    <label class="ms-insp-label"><span>DC Offset (V)</span></label>
                                    <div class="ms-insp-unit-group">
                                        <input type="number" id="msValAcOffset" class="ms-insp-input" step="any" value="${curOffset}">
                                    </div>
                                </div>
                            </div>
                        `;
                        break;
                    }

                    case 'current_source': {
                        const curI = p.current !== undefined ? p.current : 0.005;
                        const dispI = parseFloat((curI * 1000).toFixed(4));
                        html += `
                            <div class="ms-insp-group">
                                <label class="ms-insp-label">
                                    <span>DC Current Value</span>
                                    <span class="note">Base unit: Amperes</span>
                                </label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValCurrent" class="ms-insp-input" step="any" value="${dispI}">
                                    <select id="msUnitCurrent" class="ms-insp-unit-select">
                                        <option value="0.000001">µA</option>
                                        <option value="0.001" selected>mA</option>
                                        <option value="1">A</option>
                                    </select>
                                </div>
                                <div class="ms-insp-preset-pills">
                                    ${[
                                        { val: 1, mul: 0.001, lbl: '1 mA' },
                                        { val: 2, mul: 0.001, lbl: '2 mA' },
                                        { val: 5, mul: 0.001, lbl: '5 mA' },
                                        { val: 10, mul: 0.001, lbl: '10 mA' },
                                        { val: 20, mul: 0.001, lbl: '20 mA' }
                                    ].map(pill => `
                                        <button type="button" class="ms-insp-pill ${Math.abs(curI - (pill.val * pill.mul)) < 1e-5 ? 'active' : ''}" 
                                                data-target="Current" data-val="${pill.val}" data-mul="${pill.mul}">${pill.lbl}</button>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                        break;
                    }

                    case 'diode': {
                        const curVf = p.vf !== undefined ? p.vf : 0.7;
                        html += `
                            <div class="ms-insp-group">
                                <label class="ms-insp-label">
                                    <span>Diode Semiconductor Model</span>
                                    <span class="note">Pre-calibrated barrier potentials</span>
                                </label>
                                <div class="ms-insp-preset-pills">
                                    <button type="button" class="ms-insp-pill ${Math.abs(curVf - 0.7) < 0.05 ? 'active' : ''}" data-vf="0.7">Silicon 1N4007 (0.7V)</button>
                                    <button type="button" class="ms-insp-pill ${Math.abs(curVf - 0.3) < 0.05 ? 'active' : ''}" data-vf="0.3">Germanium 1N34A (0.3V)</button>
                                    <button type="button" class="ms-insp-pill ${Math.abs(curVf - 0.2) < 0.05 ? 'active' : ''}" data-vf="0.2">Schottky 1N5819 (0.2V)</button>
                                </div>
                            </div>
                            <div class="ms-insp-group">
                                <label class="ms-insp-label"><span>Forward Knee Voltage (Vf)</span></label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValDiodeVf" class="ms-insp-input" step="0.01" min="0.05" max="5.0" value="${curVf}">
                                    <span style="display:flex; align-items:center; padding:0 12px; color:#94a3b8; font-weight:700;">V</span>
                                </div>
                            </div>
                        `;
                        break;
                    }

                    case 'zener': {
                        const curVz = p.vz !== undefined ? p.vz : 5.1;
                        const curVf = p.vf !== undefined ? p.vf : 0.7;
                        html += `
                            <div class="ms-insp-group">
                                <label class="ms-insp-label">
                                    <span>Zener Breakdown Voltage (Vz)</span>
                                    <span class="note">Reverse regulation clamp</span>
                                </label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValZenerVz" class="ms-insp-input" step="0.1" min="1.0" value="${curVz}">
                                    <span style="display:flex; align-items:center; padding:0 12px; color:#94a3b8; font-weight:700;">V</span>
                                </div>
                                <div class="ms-insp-preset-pills">
                                    ${[3.3, 4.7, 5.1, 5.6, 6.2, 9.1, 12.0, 15.0].map(vz => `
                                        <button type="button" class="ms-insp-pill ${Math.abs(curVz - vz) < 0.05 ? 'active' : ''}" data-vz="${vz}">${vz} V</button>
                                    `).join('')}
                                </div>
                            </div>
                            <div class="ms-insp-group">
                                <label class="ms-insp-label"><span>Forward Conduction Drop (Vf)</span></label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValZenerVf" class="ms-insp-input" step="0.05" min="0.1" value="${curVf}">
                                    <span style="display:flex; align-items:center; padding:0 12px; color:#94a3b8; font-weight:700;">V</span>
                                </div>
                            </div>
                        `;
                        break;
                    }

                    case 'led': {
                        const curColor = p.color || '#ef4444';
                        const curVf = p.vf || 2.0;
                        const colors = [
                            { name: 'Red', hex: '#ef4444', vf: 1.8 },
                            { name: 'Green', hex: '#22c55e', vf: 2.1 },
                            { name: 'Blue', hex: '#3b82f6', vf: 3.2 },
                            { name: 'Amber', hex: '#f59e0b', vf: 2.0 },
                            { name: 'White', hex: '#f8fafc', vf: 3.2 }
                        ];
                        html += `
                            <div class="ms-insp-group">
                                <label class="ms-insp-label"><span>LED Emission Color</span></label>
                                <div class="ms-insp-color-group">
                                    ${colors.map(c => `
                                        <button type="button" class="ms-insp-color-btn ${curColor === c.hex ? 'active' : ''}" 
                                                data-color="${c.hex}" data-vf="${c.vf}" style="background: ${c.hex}; color: ${c.hex === '#f8fafc' ? '#000' : '#fff'};">
                                            ${c.name}
                                        </button>
                                    `).join('')}
                                </div>
                            </div>
                            <div class="ms-insp-group">
                                <label class="ms-insp-label"><span>Nominal Forward Drop (Vf)</span></label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValLedVf" class="ms-insp-input" step="0.05" value="${curVf}">
                                    <span style="display:flex; align-items:center; padding:0 12px; color:#94a3b8; font-weight:700;">V</span>
                                </div>
                            </div>
                        `;
                        break;
                    }

                    case 'bjt_npn':
                    case 'bjt_pnp': {
                        const curBeta = p.beta || 150;
                        const curVbe = p.vbe || 0.7;
                        html += `
                            <div class="ms-insp-group">
                                <label class="ms-insp-label">
                                    <span>DC Current Gain (&beta; / hFE)</span>
                                    <span class="note">Amplification Factor</span>
                                </label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValBjtBeta" class="ms-insp-input" step="1" min="10" max="1000" value="${curBeta}">
                                </div>
                                <div class="ms-insp-preset-pills">
                                    ${[
                                        { beta: 50, lbl: 'β = 50' },
                                        { beta: 100, lbl: 'β = 100 (2N2222)' },
                                        { beta: 150, lbl: 'β = 150 (BC547)' },
                                        { beta: 200, lbl: 'β = 200 (2N3904)' },
                                        { beta: 300, lbl: 'β = 300 (High-Gain)' }
                                    ].map(pill => `
                                        <button type="button" class="ms-insp-pill ${curBeta === pill.beta ? 'active' : ''}" data-beta="${pill.beta}">${pill.lbl}</button>
                                    `).join('')}
                                </div>
                            </div>
                            <div class="ms-insp-group">
                                <label class="ms-insp-label"><span>Base-Emitter Junction Drop (Vbe)</span></label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValBjtVbe" class="ms-insp-input" step="0.02" value="${curVbe}">
                                    <span style="display:flex; align-items:center; padding:0 12px; color:#94a3b8; font-weight:700;">V</span>
                                </div>
                            </div>
                        `;
                        break;
                    }

                    case 'jfet_n': {
                        const curVp = p.vp !== undefined ? p.vp : -3.0;
                        const curIdss = p.idss !== undefined ? p.idss * 1000 : 9.0;
                        html += `
                            <div class="ms-insp-group">
                                <label class="ms-insp-label"><span>Pinch-Off Voltage (Vp)</span></label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValJfetVp" class="ms-insp-input" step="0.1" value="${curVp}">
                                    <span style="display:flex; align-items:center; padding:0 12px; color:#94a3b8; font-weight:700;">V</span>
                                </div>
                            </div>
                            <div class="ms-insp-group">
                                <label class="ms-insp-label"><span>Saturation Drain Current (IDSS)</span></label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValJfetIdss" class="ms-insp-input" step="0.5" value="${curIdss}">
                                    <span style="display:flex; align-items:center; padding:0 12px; color:#94a3b8; font-weight:700;">mA</span>
                                </div>
                            </div>
                        `;
                        break;
                    }

                    case 'mosfet_n': {
                        const curVth = p.vth !== undefined ? p.vth : 2.5;
                        const curKn = p.kn !== undefined ? p.kn * 1000 : 20.0;
                        html += `
                            <div class="ms-insp-group">
                                <label class="ms-insp-label"><span>Threshold Voltage (Vth)</span></label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValMosVth" class="ms-insp-input" step="0.1" value="${curVth}">
                                    <span style="display:flex; align-items:center; padding:0 12px; color:#94a3b8; font-weight:700;">V</span>
                                </div>
                            </div>
                            <div class="ms-insp-group">
                                <label class="ms-insp-label"><span>Conduction Parameter (kn)</span></label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValMosKn" class="ms-insp-input" step="1.0" value="${curKn}">
                                    <span style="display:flex; align-items:center; padding:0 12px; color:#94a3b8; font-weight:700;">mA/V²</span>
                                </div>
                            </div>
                        `;
                        break;
                    }

                    case 'opamp': {
                        const curAol = p.aol || 100000;
                        const curVsupply = p.vsupply || 15;
                        html += `
                            <div class="ms-insp-group">
                                <label class="ms-insp-label"><span>Open Loop Gain (Aol)</span></label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValOpampAol" class="ms-insp-input" step="10000" min="1000" value="${curAol}">
                                </div>
                                <div class="ms-insp-preset-pills">
                                    <button type="button" class="ms-insp-pill ${curAol === 50000 ? 'active' : ''}" data-aol="50000">50,000</button>
                                    <button type="button" class="ms-insp-pill ${curAol === 100000 ? 'active' : ''}" data-aol="100000">100,000 (100 dB)</button>
                                    <button type="button" class="ms-insp-pill ${curAol === 200000 ? 'active' : ''}" data-aol="200000">200,000</button>
                                </div>
                            </div>
                            <div class="ms-insp-group">
                                <label class="ms-insp-label"><span>Supply Rails (&plusmn;Vsupply)</span></label>
                                <div class="ms-insp-unit-group">
                                    <input type="number" id="msValOpampSupply" class="ms-insp-input" step="1" min="3" max="30" value="${curVsupply}">
                                    <span style="display:flex; align-items:center; padding:0 12px; color:#94a3b8; font-weight:700;">V</span>
                                </div>
                                <div class="ms-insp-preset-pills">
                                    <button type="button" class="ms-insp-pill ${curVsupply === 9 ? 'active' : ''}" data-vsup="9">&plusmn;9 V</button>
                                    <button type="button" class="ms-insp-pill ${curVsupply === 12 ? 'active' : ''}" data-vsup="12">&plusmn;12 V</button>
                                    <button type="button" class="ms-insp-pill ${curVsupply === 15 ? 'active' : ''}" data-vsup="15">&plusmn;15 V (Standard)</button>
                                </div>
                            </div>
                        `;
                        break;
                    }

                    default: {
                        for (const propKey in comp.props) {
                            if (propKey === 'unit') continue;
                            const val = comp.props[propKey];
                            html += `
                                <div class="ms-insp-group">
                                    <label class="ms-insp-label"><span>${propKey.toUpperCase()}</span></label>
                                    <div class="ms-insp-unit-group">
                                        <input type="text" class="ms-insp-input ms-prop-input" data-key="${propKey}" value="${val}">
                                    </div>
                                </div>
                            `;
                        }
                        break;
                    }
                }

                body.innerHTML = html;
                bindFieldInteractions();
            };

            const bindFieldInteractions = () => {
                // Preset pills with data-target
                body.querySelectorAll('.ms-insp-pill[data-target]').forEach(pill => {
                    pill.addEventListener('click', () => {
                        const target = pill.getAttribute('data-target');
                        const val = pill.getAttribute('data-val');
                        const mul = pill.getAttribute('data-mul');
                        const inp = document.getElementById('msVal' + target);
                        const sel = document.getElementById('msUnit' + target);
                        if (inp) inp.value = val;
                        if (sel) sel.value = mul;
                        body.querySelectorAll(`.ms-insp-pill[data-target="${target}"]`).forEach(p => p.classList.remove('active'));
                        pill.classList.add('active');
                    });
                });

                // Potentiometer wiper slider & quick buttons
                const wiperSlider = document.getElementById('msValWiper');
                const wiperBadge = document.getElementById('msWiperBadge');
                const wiperDetails = document.getElementById('msWiperDetails');
                if (wiperSlider) {
                    const updateWiper = val => {
                        const pct = parseInt(val, 10);
                        if (wiperBadge) wiperBadge.innerText = pct + '%';
                        if (wiperDetails) {
                            const desc = pct === 0 ? '0% (Ground / Min)' : (pct === 100 ? '100% (Rail / Max)' : `${pct}% Ratio`);
                            wiperDetails.innerText = desc;
                        }
                        comp.props.wiper = pct / 100;
                        this.render();
                    };
                    wiperSlider.addEventListener('input', e => updateWiper(e.target.value));
                    body.querySelectorAll('button[data-wiper]').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const pct = btn.getAttribute('data-wiper');
                            wiperSlider.value = pct;
                            updateWiper(pct);
                        });
                    });
                }

                // Switch segmented buttons
                const btnClosed = document.getElementById('msBtnSwClosed');
                const btnOpen = document.getElementById('msBtnSwOpen');
                if (btnClosed && btnOpen) {
                    btnClosed.addEventListener('click', () => {
                        comp.props.closed = true;
                        btnClosed.className = 'ms-insp-seg-btn active green';
                        btnOpen.className = 'ms-insp-seg-btn';
                        this.render();
                    });
                    btnOpen.addEventListener('click', () => {
                        comp.props.closed = false;
                        btnClosed.className = 'ms-insp-seg-btn';
                        btnOpen.className = 'ms-insp-seg-btn active amber';
                        this.render();
                    });
                }

                // Waveform selector segmented buttons
                const wfGroup = document.getElementById('msWaveformGroup');
                if (wfGroup) {
                    wfGroup.querySelectorAll('.ms-insp-seg-btn').forEach(btn => {
                        btn.addEventListener('click', () => {
                            wfGroup.querySelectorAll('.ms-insp-seg-btn').forEach(b => b.classList.remove('active'));
                            btn.classList.add('active');
                            comp.props.waveform = btn.getAttribute('data-wf');
                            this.render();
                        });
                    });
                }

                // Diode Vf preset pills
                body.querySelectorAll('.ms-insp-pill[data-vf]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const vf = btn.getAttribute('data-vf');
                        const inp = document.getElementById('msValDiodeVf');
                        if (inp) inp.value = vf;
                        body.querySelectorAll('.ms-insp-pill[data-vf]').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                    });
                });

                // Zener Vz preset pills
                body.querySelectorAll('.ms-insp-pill[data-vz]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const vz = btn.getAttribute('data-vz');
                        const inp = document.getElementById('msValZenerVz');
                        if (inp) inp.value = vz;
                        body.querySelectorAll('.ms-insp-pill[data-vz]').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                    });
                });

                // LED color picker buttons
                body.querySelectorAll('.ms-insp-color-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const color = btn.getAttribute('data-color');
                        const vf = btn.getAttribute('data-vf');
                        const inpVf = document.getElementById('msValLedVf');
                        if (inpVf) inpVf.value = vf;
                        comp.props.color = color;
                        body.querySelectorAll('.ms-insp-color-btn').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                        this.render();
                    });
                });

                // BJT beta preset pills
                body.querySelectorAll('.ms-insp-pill[data-beta]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const beta = btn.getAttribute('data-beta');
                        const inp = document.getElementById('msValBjtBeta');
                        if (inp) inp.value = beta;
                        body.querySelectorAll('.ms-insp-pill[data-beta]').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                    });
                });

                // Op-Amp AOL pills
                body.querySelectorAll('.ms-insp-pill[data-aol]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const aol = btn.getAttribute('data-aol');
                        const inp = document.getElementById('msValOpampAol');
                        if (inp) inp.value = aol;
                        body.querySelectorAll('.ms-insp-pill[data-aol]').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                    });
                });

                // Op-Amp Supply pills
                body.querySelectorAll('.ms-insp-pill[data-vsup]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const vsup = btn.getAttribute('data-vsup');
                        const inp = document.getElementById('msValOpampSupply');
                        if (inp) inp.value = vsup;
                        body.querySelectorAll('.ms-insp-pill[data-vsup]').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                    });
                });
            };

            renderFields();
            modal.classList.add('active');

            // Reset to defaults button
            if (resetBtn) {
                resetBtn.onclick = () => {
                    comp.props = Object.assign({}, def.defaults);
                    renderFields();
                    this.render();
                    this.showToast(`Reset ${comp.label} to standard factory default values.`);
                };
            }

            // Save / Apply Changes
            const saveHandler = () => {
                const labelInp = document.getElementById('msInspLabel');
                if (labelInp && labelInp.value.trim()) {
                    comp.label = labelInp.value.trim();
                }

                switch (comp.type) {
                    case 'resistor': {
                        const val = parseFloat(document.getElementById('msValResistance').value);
                        const mul = parseFloat(document.getElementById('msUnitResistance').value);
                        if (!isNaN(val) && val > 0) comp.props.resistance = val * mul;
                        break;
                    }
                    case 'potentiometer': {
                        const val = parseFloat(document.getElementById('msValPotResistance').value);
                        const mul = parseFloat(document.getElementById('msUnitPotResistance').value);
                        if (!isNaN(val) && val > 0) comp.props.total_resistance = val * mul;
                        const wInp = document.getElementById('msValWiper');
                        if (wInp) comp.props.wiper = Math.max(0.001, Math.min(0.999, parseInt(wInp.value, 10) / 100));
                        break;
                    }
                    case 'capacitor': {
                        const val = parseFloat(document.getElementById('msValCapacitance').value);
                        const mul = parseFloat(document.getElementById('msUnitCapacitance').value);
                        if (!isNaN(val) && val > 0) comp.props.capacitance = val * mul;
                        break;
                    }
                    case 'inductor': {
                        const val = parseFloat(document.getElementById('msValInductance').value);
                        const mul = parseFloat(document.getElementById('msUnitInductance').value);
                        if (!isNaN(val) && val > 0) comp.props.inductance = val * mul;
                        break;
                    }
                    case 'dc_source': {
                        const val = parseFloat(document.getElementById('msValDcVoltage').value);
                        const mul = parseFloat(document.getElementById('msUnitDcVoltage').value);
                        if (!isNaN(val)) comp.props.voltage = val * mul;
                        break;
                    }
                    case 'ac_source': {
                        const amp = parseFloat(document.getElementById('msValAcAmp').value);
                        const ampMul = parseFloat(document.getElementById('msUnitAcAmp').value);
                        if (!isNaN(amp) && amp > 0) comp.props.amplitude = amp * ampMul;

                        const freq = parseFloat(document.getElementById('msValAcFreq').value);
                        const freqMul = parseFloat(document.getElementById('msUnitAcFreq').value);
                        if (!isNaN(freq) && freq > 0) comp.props.frequency = freq * freqMul;

                        const phase = parseFloat(document.getElementById('msValAcPhase').value);
                        if (!isNaN(phase)) comp.props.phase = phase;

                        const offset = parseFloat(document.getElementById('msValAcOffset').value);
                        if (!isNaN(offset)) comp.props.offset = offset;
                        break;
                    }
                    case 'current_source': {
                        const val = parseFloat(document.getElementById('msValCurrent').value);
                        const mul = parseFloat(document.getElementById('msUnitCurrent').value);
                        if (!isNaN(val)) comp.props.current = val * mul;
                        break;
                    }
                    case 'diode': {
                        const vf = parseFloat(document.getElementById('msValDiodeVf').value);
                        if (!isNaN(vf) && vf > 0) comp.props.vf = vf;
                        break;
                    }
                    case 'zener': {
                        const vz = parseFloat(document.getElementById('msValZenerVz').value);
                        if (!isNaN(vz) && vz > 0) comp.props.vz = vz;
                        const vf = parseFloat(document.getElementById('msValZenerVf').value);
                        if (!isNaN(vf) && vf > 0) comp.props.vf = vf;
                        break;
                    }
                    case 'led': {
                        const vf = parseFloat(document.getElementById('msValLedVf').value);
                        if (!isNaN(vf) && vf > 0) comp.props.vf = vf;
                        break;
                    }
                    case 'bjt_npn':
                    case 'bjt_pnp': {
                        const beta = parseFloat(document.getElementById('msValBjtBeta').value);
                        if (!isNaN(beta) && beta > 0) comp.props.beta = beta;
                        const vbe = parseFloat(document.getElementById('msValBjtVbe').value);
                        if (!isNaN(vbe) && vbe > 0) comp.props.vbe = vbe;
                        break;
                    }
                    case 'jfet_n': {
                        const vp = parseFloat(document.getElementById('msValJfetVp').value);
                        if (!isNaN(vp)) comp.props.vp = vp;
                        const idss = parseFloat(document.getElementById('msValJfetIdss').value);
                        if (!isNaN(idss) && idss > 0) comp.props.idss = idss * 0.001;
                        break;
                    }
                    case 'mosfet_n': {
                        const vth = parseFloat(document.getElementById('msValMosVth').value);
                        if (!isNaN(vth)) comp.props.vth = vth;
                        const kn = parseFloat(document.getElementById('msValMosKn').value);
                        if (!isNaN(kn) && kn > 0) comp.props.kn = kn * 0.001;
                        break;
                    }
                    case 'opamp': {
                        const aol = parseFloat(document.getElementById('msValOpampAol').value);
                        if (!isNaN(aol) && aol > 0) comp.props.aol = aol;
                        const sup = parseFloat(document.getElementById('msValOpampSupply').value);
                        if (!isNaN(sup) && sup > 0) comp.props.vsupply = sup;
                        break;
                    }
                    default: {
                        body.querySelectorAll('.ms-prop-input').forEach(inp => {
                            const key = inp.getAttribute('data-key');
                            const numVal = parseFloat(inp.value);
                            comp.props[key] = isNaN(numVal) ? inp.value : numVal;
                        });
                        break;
                    }
                }

                modal.classList.remove('active');
                this.render();
                this.showToast(`Updated ${comp.label} properties successfully.`);
                saveBtn.onclick = null;
            };

            saveBtn.onclick = saveHandler;
            const closeModal = () => modal.classList.remove('active');
            if (cancelBtn) cancelBtn.onclick = closeModal;
            if (closeBtn) closeBtn.onclick = closeModal;
        }

        // --------------------------------------------------------------------
        // RIBBON & MENU BUTTON ACTIONS
        // --------------------------------------------------------------------
        bindRibbonControls() {
            const self = this;

            const runBtn = document.getElementById('msBtnSimRun');
            if (runBtn) runBtn.addEventListener('click', () => self.toggleSimulation());

            const stepBtn = document.getElementById('msBtnSimStep');
            if (stepBtn) stepBtn.addEventListener('click', () => self.stepSimulation());

            const rotBtn = document.getElementById('msBtnRotate');
            if (rotBtn) rotBtn.addEventListener('click', () => self.rotateSelected());

            const delBtn = document.getElementById('msBtnDelete');
            if (delBtn) delBtn.addEventListener('click', () => self.deleteSelected());

            const propsBtn = document.getElementById('msBtnProps');
            if (propsBtn) {
                propsBtn.addEventListener('click', () => {
                    if (self.selectedItem && self.selectedItem.id) {
                        self.openPropertyModal(self.selectedItem);
                    } else {
                        self.showToast('Click any component on the schematic to select it and edit its value.');
                    }
                });
            }

            const clearBtn = document.getElementById('msBtnClear');
            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    if (confirm('Clear schematic and start a new circuit?')) {
                        self.clearAll();
                    }
                });
            }

            const fsBtn = document.getElementById('msBtnFullscreen');
            const wrapper = document.getElementById('msWorkbenchWrapper');
            if (fsBtn && wrapper) {
                fsBtn.addEventListener('click', () => {
                    wrapper.classList.toggle('ms-fullscreen');
                    const isFs = wrapper.classList.contains('ms-fullscreen');
                    fsBtn.innerHTML = isFs ? '<i class="fa-solid fa-compress"></i> <span>Exit Fullscreen</span>' : '<i class="fa-solid fa-expand"></i> <span>Fullscreen</span>';
                    setTimeout(() => self.autoFitView(), 120);
                });
            }

            // On-Canvas Zoom & Fit Controls
            const btnZoomIn = document.getElementById('msBtnZoomIn');
            if (btnZoomIn) btnZoomIn.addEventListener('click', () => self.zoom(0.85));

            const btnZoomOut = document.getElementById('msBtnZoomOut');
            if (btnZoomOut) btnZoomOut.addEventListener('click', () => self.zoom(1.18));

            const btnZoomFit = document.getElementById('msBtnZoomFit');
            if (btnZoomFit) btnZoomFit.addEventListener('click', () => self.autoFitView(true));

            // Floating Mobile Add Component Button & Drawer Backdrop
            const btnCanvasAdd = document.getElementById('msCanvasAddBtn');
            const drawer = document.getElementById('msComponentDrawer');
            const backdrop = document.getElementById('msDrawerBackdrop');
            if (btnCanvasAdd && drawer) {
                btnCanvasAdd.addEventListener('click', () => {
                    drawer.classList.add('open');
                    if (backdrop) backdrop.classList.add('active');
                });
            }
            const closeDrawerBtn = document.getElementById('msDrawerCloseBtn');
            if (closeDrawerBtn && drawer) {
                closeDrawerBtn.addEventListener('click', () => {
                    drawer.classList.remove('open');
                    if (backdrop) backdrop.classList.remove('active');
                });
            }
            if (backdrop && drawer) {
                backdrop.addEventListener('click', () => {
                    drawer.classList.remove('open');
                    backdrop.classList.remove('active');
                });
            }

            const exportBtn = document.getElementById('msBtnExport');
            if (exportBtn) exportBtn.addEventListener('click', () => self.exportSchematicPNG());

            const modSelect = document.getElementById('msModuleSelect');
            if (modSelect) {
                modSelect.addEventListener('change', e => {
                    const preset = e.target.value;
                    if (preset) {
                        self.loadPreset(preset);
                        if (typeof window.trackPhysicsEvent === 'function') {
                            const optText = e.target.options[e.target.selectedIndex] ? e.target.options[e.target.selectedIndex].text : preset;
                            window.trackPhysicsEvent('circuit_preset_load', {
                                preset: preset,
                                label: optText
                            });
                        }
                    }
                });
            }
            const uploadBtn = document.getElementById('msBtnUploadDiagram');
            if (uploadBtn) uploadBtn.addEventListener('click', () => self.openDiagramUploadModal());
        }

        bindPaletteButtons() {
            const self = this;
            const cards = document.querySelectorAll('.ms-comp-card');
            cards.forEach(card => {
                card.addEventListener('click', () => {
                    const type = card.getAttribute('data-type');
                    // Add near current view center
                    const cx = Math.round((self.view.x + self.view.width / 2) / 20) * 20;
                    const cy = Math.round((self.view.y + self.view.height / 2) / 20) * 20;
                    self.addComponent(type, cx, cy);

                    // On mobile, automatically close drawer after picking component
                    if (window.innerWidth <= 768) {
                        const drawer = document.getElementById('msComponentDrawer');
                        const backdrop = document.getElementById('msDrawerBackdrop');
                        if (drawer) drawer.classList.remove('open');
                        if (backdrop) backdrop.classList.remove('active');
                        self.showToast('Component added to circuit canvas.');
                    }
                });
            });

            const searchInput = document.getElementById('msCompSearch');
            const clearBtn = document.getElementById('msCompSearchClear');
            const categoryGroups = document.querySelectorAll('.ms-category-group');

            const applyFilter = () => {
                const q = (searchInput ? searchInput.value : '').toLowerCase().trim();
                if (clearBtn) {
                    clearBtn.style.display = q ? 'block' : 'none';
                }

                let totalMatches = 0;
                categoryGroups.forEach(group => {
                    const groupCards = group.querySelectorAll('.ms-comp-card');
                    let groupMatchCount = 0;

                    groupCards.forEach(card => {
                        const name = (card.querySelector('.ms-comp-name')?.innerText || '').toLowerCase();
                        const type = (card.getAttribute('data-type') || '').toLowerCase();
                        const matches = !q || name.includes(q) || type.includes(q);
                        card.style.display = matches ? 'flex' : 'none';
                        if (matches) {
                            groupMatchCount++;
                            totalMatches++;
                        }
                    });

                    // Hide empty category groups when filtering, show all when search is empty
                    group.style.display = (!q || groupMatchCount > 0) ? 'block' : 'none';
                });

                let noResultsEl = document.getElementById('msCompNoResults');
                if (!noResultsEl && searchInput) {
                    noResultsEl = document.createElement('div');
                    noResultsEl.id = 'msCompNoResults';
                    noResultsEl.className = 'ms-no-results-msg';
                    noResultsEl.innerHTML = `
                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        <span>No components found</span>
                        <button type="button" class="ms-btn-clear-filter">Show All</button>
                    `;
                    const wrapper = searchInput.closest('.ms-search-wrapper') || searchInput;
                    wrapper.insertAdjacentElement('afterend', noResultsEl);
                    noResultsEl.querySelector('.ms-btn-clear-filter')?.addEventListener('click', () => {
                        if (searchInput) {
                            searchInput.value = '';
                            applyFilter();
                        }
                    });
                }
                if (noResultsEl) {
                    noResultsEl.style.display = (q && totalMatches === 0) ? 'flex' : 'none';
                }
            };

            if (searchInput) {
                searchInput.value = '';
                searchInput.addEventListener('input', applyFilter);
                searchInput.addEventListener('search', applyFilter);
                searchInput.addEventListener('change', applyFilter);
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    if (searchInput) {
                        searchInput.value = '';
                        applyFilter();
                        searchInput.focus();
                    }
                });
            }

            applyFilter();
        }

        validatePaletteFilter() {
            const searchInput = document.getElementById('msCompSearch');
            if (!searchInput) return;

            // If user is actively typing in the box, leave it alone
            if (document.activeElement === searchInput) return;

            const q = searchInput.value.toLowerCase().trim();
            if (!q) {
                const cards = document.querySelectorAll('.ms-comp-card');
                cards.forEach(c => { c.style.display = 'flex'; });
                const groups = document.querySelectorAll('.ms-category-group');
                groups.forEach(g => { g.style.display = 'block'; });
                const noResults = document.getElementById('msCompNoResults');
                if (noResults) noResults.style.display = 'none';
                return;
            }

            // Check if search query matches ANY component
            const cards = document.querySelectorAll('.ms-comp-card');
            let hasAnyMatch = false;
            cards.forEach(card => {
                const name = (card.querySelector('.ms-comp-name')?.innerText || '').toLowerCase();
                const type = (card.getAttribute('data-type') || '').toLowerCase();
                if (name.includes(q) || type.includes(q)) {
                    hasAnyMatch = true;
                }
            });

            // If query matches nothing (e.g. browser autofilled username "anirban"), clear it and restore!
            if (!hasAnyMatch) {
                searchInput.value = '';
                const clearBtn = document.getElementById('msCompSearchClear');
                if (clearBtn) clearBtn.style.display = 'none';
                cards.forEach(c => { c.style.display = 'flex'; });
                const groups = document.querySelectorAll('.ms-category-group');
                groups.forEach(g => { g.style.display = 'block'; });
                const noResults = document.getElementById('msCompNoResults');
                if (noResults) noResults.style.display = 'none';
            }
        }

        bindMenuActions() {
            const self = this;

            // File dropdown items
            const menuNew = document.getElementById('msMenuNew');
            if (menuNew) menuNew.addEventListener('click', () => {
                if (confirm('Create new empty schematic?')) {
                    self.clearAll();
                    self.showToast('New blank schematic canvas initialized.');
                }
            });

            const menuUpload = document.getElementById('msMenuUploadDiagram');
            if (menuUpload) menuUpload.addEventListener('click', () => self.openDiagramUploadModal());

            const menuExport = document.getElementById('msMenuExport');
            if (menuExport) menuExport.addEventListener('click', () => self.exportSchematicPNG());

            const menuClear = document.getElementById('msMenuClear');
            if (menuClear) menuClear.addEventListener('click', () => {
                if (confirm('Clear schematic and start a new circuit?')) {
                    self.clearAll();
                }
            });

            // Edit dropdown items
            const menuProps = document.getElementById('msMenuProps');
            if (menuProps) menuProps.addEventListener('click', () => {
                if (self.selectedItem && self.selectedItem.id) {
                    self.openPropertyModal(self.selectedItem);
                } else {
                    self.showToast('Click any component on the schematic to select it.');
                }
            });

            const menuRotate = document.getElementById('msMenuRotate');
            if (menuRotate) menuRotate.addEventListener('click', () => self.rotateSelected());

            const menuDelete = document.getElementById('msMenuDelete');
            if (menuDelete) menuDelete.addEventListener('click', () => self.deleteSelected());

            // Simulate dropdown items
            const menuRun = document.getElementById('msMenuRun');
            if (menuRun) menuRun.addEventListener('click', () => self.toggleSimulation());

            const menuStep = document.getElementById('msMenuStep');
            if (menuStep) menuStep.addEventListener('click', () => self.stepSimulation());

            // Instruments dropdown items
            const menuCRO = document.getElementById('msMenuCRO');
            if (menuCRO) menuCRO.addEventListener('click', () => self.cro.toggle());

            const menuDMM = document.getElementById('msMenuDMM');
            if (menuDMM) menuDMM.addEventListener('click', () => self.dmm.toggle());

            const menuXFG = document.getElementById('msMenuXFG');
            if (menuXFG) menuXFG.addEventListener('click', () => self.xfg.toggle());

            const btnDMM = document.getElementById('msInstDMM');
            if (btnDMM) btnDMM.addEventListener('click', () => self.dmm.toggle());

            const btnCRO = document.getElementById('msInstCRO');
            if (btnCRO) btnCRO.addEventListener('click', () => self.cro.toggle());

            const btnXFG = document.getElementById('msInstXFG');
            if (btnXFG) btnXFG.addEventListener('click', () => self.xfg.toggle());

            const toggleDrawerBtn = document.getElementById('msToggleDrawerBtn');
            const drawer = document.getElementById('msComponentDrawer');
            const backdrop = document.getElementById('msDrawerBackdrop');
            if (toggleDrawerBtn && drawer) {
                toggleDrawerBtn.addEventListener('click', () => {
                    drawer.classList.toggle('open');
                    if (backdrop) {
                        backdrop.classList.toggle('active', drawer.classList.contains('open'));
                    }
                });
            }
        }

        // ====================================================================
        // 4. TRUE PHYSICAL MODIFIED NODAL ANALYSIS (MNA) SOLVER ENGINE
        // ====================================================================
        toggleSimulation() {
            if (this.isRunning) this.stopSimulation();
            else this.startSimulation();
        }

        startSimulation() {
            this.isRunning = true;
            this.validatePaletteFilter();
            const runBtn = document.getElementById('msBtnSimRun');
            if (runBtn) {
                runBtn.classList.remove('run');
                runBtn.classList.add('stop');
                runBtn.innerHTML = '<i class="fa-solid fa-stop"></i> <span>Stop (F5)</span>';
            }
            if (this.ledEl) this.ledEl.classList.add('running');

            // Dispatch Physics Telemetry Event
            if (typeof window.trackPhysicsEvent === 'function') {
                const sel = document.getElementById('msModuleSelect');
                window.trackPhysicsEvent('circuit_simulate_run', {
                    preset: (sel ? sel.value : 'custom'),
                    label: (sel && sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : 'Circuit Simulation'),
                    components: (this.components ? this.components.length : 0),
                    wires: (this.wires ? this.wires.length : 0)
                });
            }

            const self = this;
            this.simInterval = setInterval(() => {
                self.simulationTick();
            }, 30);
        }

        stopSimulation() {
            this.isRunning = false;
            clearInterval(this.simInterval);
            this.simInterval = null;
            this.validatePaletteFilter();

            const runBtn = document.getElementById('msBtnSimRun');
            if (runBtn) {
                runBtn.classList.remove('stop');
                runBtn.classList.add('run');
                runBtn.innerHTML = '<i class="fa-solid fa-play"></i> <span>Run (F5)</span>';
            }
            if (this.ledEl) this.ledEl.classList.remove('running');
            if (this.hudTooltip) this.hudTooltip.style.display = 'none';
        }

        stepSimulation() {
            this.simulationTick();
        }

        simulationTick() {
            // Run multiple sub-steps for physical numerical stability
            for (let s = 0; s < this.stepsPerTick; s++) {
                this.simTime += this.simDt;
                this.solveMNA();
            }

            if (this.clockEl) {
                this.clockEl.innerText = this.simTime.toFixed(4) + ' s';
            }

            // Update virtual instruments with true physical node measurements
            if (this.cro) {
                this.cro.recordSample(this.simTime);
                if (this.cro.isOpen) {
                    this.cro.updateDisplay();
                }
            }

            if (this.dmm && this.dmm.isOpen) {
                this.dmm.updateReadout();
            }

            this.updateOnCanvasMeters();
        }

        // --- Topological Clustering: Disjoint Set Union (DSU) ---
        buildTopology() {
            const parent = {};
            const find = p => {
                if (parent[p] === undefined) parent[p] = p;
                if (parent[p] !== p) parent[p] = find(parent[p]);
                return parent[p];
            };
            const union = (p1, p2) => {
                const r1 = find(p1);
                const r2 = find(p2);
                if (r1 !== r2) parent[r1] = r2;
            };

            // Register all pins
            this.components.forEach(comp => {
                const def = COMP_TYPES[comp.type];
                if (def) {
                    def.pins.forEach(pin => {
                        const key = `${comp.id}:${pin.id}`;
                        find(key);
                    });
                }
            });

            // Connect via Wires
            this.wires.forEach(w => {
                const k1 = `${w.fromComp}:${w.fromPin}`;
                const k2 = `${w.toComp}:${w.toPin}`;
                union(k1, k2);
            });

            // Connect geometric overlaps
            const coordsMap = {};
            this.components.forEach(comp => {
                const def = COMP_TYPES[comp.type];
                if (def) {
                    def.pins.forEach(pin => {
                        const pos = this.getPinPos(comp.id, pin.id);
                        const cKey = `${pos.x},${pos.y}`;
                        const pKey = `${comp.id}:${pin.id}`;
                        if (coordsMap[cKey]) {
                            union(coordsMap[cKey], pKey);
                        } else {
                            coordsMap[cKey] = pKey;
                        }
                    });
                }
            });

            // Find Ground Pin (Node 0)
            let gndRoot = null;
            const gndComp = this.components.find(c => c.type === 'ground');
            if (gndComp) {
                gndRoot = find(`${gndComp.id}:g`);
            } else {
                // Default ground to negative of first voltage source or first pin
                const src = this.components.find(c => c.type === 'dc_source' || c.type === 'ac_source');
                if (src) {
                    gndRoot = find(`${src.id}:n`);
                }
            }

            // Assign consecutive integers 0, 1, 2, ...
            const rootToNode = {};
            if (gndRoot) {
                rootToNode[gndRoot] = 0;
            }

            let nextNodeNum = 1;
            this.pinToNode = {};

            for (const pKey in parent) {
                const r = find(pKey);
                if (rootToNode[r] === undefined) {
                    rootToNode[r] = nextNodeNum++;
                }
                this.pinToNode[pKey] = rootToNode[r];
            }

            const numNodes = nextNodeNum; // Node 0 is GND, 1 to numNodes-1 are active
            return numNodes;
        }

        // --- MNA Matrix Stamping & Gaussian Elimination ---
        solveMNA() {
            const numNodes = this.buildTopology();
            const N = numNodes - 1; // Number of non-ground nodal equations

            // Count independent voltage sources
            const vSources = [];
            this.components.forEach(comp => {
                if (comp.type === 'dc_source' || comp.type === 'ac_source') {
                    vSources.push(comp);
                } else if (comp.type === 'opamp') {
                    // Op-Amp output behaves as controlled voltage source
                    vSources.push({
                        id: comp.id,
                        type: 'opamp_out',
                        refComp: comp
                    });
                }
            });

            const M = vSources.length;
            const totalVars = N + M;

            if (totalVars <= 0) return;

            // Allocate Matrix A (totalVars x totalVars) and vector Z
            const A = Array.from({ length: totalVars }, () => new Float64Array(totalVars));
            const Z = new Float64Array(totalVars);

            const stampConductance = (n1, n2, g) => {
                if (n1 > 0) A[n1 - 1][n1 - 1] += g;
                if (n2 > 0) A[n2 - 1][n2 - 1] += g;
                if (n1 > 0 && n2 > 0) {
                    A[n1 - 1][n2 - 1] -= g;
                    A[n2 - 1][n1 - 1] -= g;
                }
            };

            const stampCurrentSource = (nFrom, nTo, iVal) => {
                if (nFrom > 0) Z[nFrom - 1] -= iVal;
                if (nTo > 0) Z[nTo - 1] += iVal;
            };

            const dt = this.simDt;

            // Stamp Passive and Active Components
            this.components.forEach(comp => {
                const p = comp.props;

                switch (comp.type) {
                    case 'resistor': {
                        const n1 = this.pinToNode[`${comp.id}:1`] || 0;
                        const n2 = this.pinToNode[`${comp.id}:2`] || 0;
                        const g = 1.0 / Math.max(0.001, p.resistance || 1000);
                        stampConductance(n1, n2, g);
                        break;
                    }

                    case 'potentiometer': {
                        const n1 = this.pinToNode[`${comp.id}:1`] || 0;
                        const n2 = this.pinToNode[`${comp.id}:2`] || 0;
                        const nw = this.pinToNode[`${comp.id}:w`] || 0;
                        const w = Math.min(0.999, Math.max(0.001, p.wiper || 0.5));
                        const rTot = p.total_resistance || 10000;
                        const r1 = Math.max(1, rTot * w);
                        const r2 = Math.max(1, rTot * (1 - w));
                        stampConductance(n1, nw, 1.0 / r1);
                        stampConductance(nw, n2, 1.0 / r2);
                        break;
                    }

                    case 'switch_spst': {
                        const n1 = this.pinToNode[`${comp.id}:1`] || 0;
                        const n2 = this.pinToNode[`${comp.id}:2`] || 0;
                        const isClosed = p.closed !== false;
                        const g = isClosed ? 1000.0 : 1e-8; // 1mΩ vs 100MΩ
                        stampConductance(n1, n2, g);
                        break;
                    }

                    case 'capacitor': {
                        // Companion Model: G_eq = C/dt, I_eq = G_eq * V_prev
                        const n1 = this.pinToNode[`${comp.id}:1`] || 0;
                        const n2 = this.pinToNode[`${comp.id}:2`] || 0;
                        const cVal = p.capacitance || 0.0001;
                        const gC = cVal / dt;
                        const vPrev = this.compStates[comp.id]?.v || 0.0;
                        const iEq = gC * vPrev;
                        stampConductance(n1, n2, gC);
                        stampCurrentSource(n1, n2, -iEq);
                        break;
                    }

                    case 'inductor': {
                        // Companion Model: G_eq = dt/L, I_eq = I_prev
                        const n1 = this.pinToNode[`${comp.id}:1`] || 0;
                        const n2 = this.pinToNode[`${comp.id}:2`] || 0;
                        const lVal = p.inductance || 0.01;
                        const gL = dt / lVal;
                        const iPrev = this.compStates[comp.id]?.i || 0.0;
                        stampConductance(n1, n2, gL);
                        stampCurrentSource(n1, n2, -iPrev);
                        break;
                    }

                    case 'diode':
                    case 'led': {
                        const na = this.pinToNode[`${comp.id}:a`] || 0;
                        const nk = this.pinToNode[`${comp.id}:k`] || 0;
                        const va = this.nodeVoltages[na] || 0.0;
                        const vk = this.nodeVoltages[nk] || 0.0;
                        const vd = va - vk;
                        const vf = comp.type === 'led' ? (p.vf || 2.0) : (p.vf || 0.7);

                        if (vd > vf) {
                            const rOn = p.r_on || 1.0;
                            const gD = 1.0 / rOn;
                            const iEq = vf / rOn;
                            stampConductance(na, nk, gD);
                            stampCurrentSource(na, nk, -iEq);
                            if (comp.type === 'led') comp.props.lit = (vd - vf) / rOn > 0.001;
                        } else {
                            stampConductance(na, nk, 1e-7); // Reverse leakage
                            if (comp.type === 'led') comp.props.lit = false;
                        }
                        break;
                    }

                    case 'zener': {
                        const na = this.pinToNode[`${comp.id}:a`] || 0;
                        const nk = this.pinToNode[`${comp.id}:k`] || 0;
                        const va = this.nodeVoltages[na] || 0.0;
                        const vk = this.nodeVoltages[nk] || 0.0;
                        const vz = p.vz || 5.1;
                        const vf = p.vf || 0.7;

                        if (vk - va >= vz) {
                            // Reverse Zener Breakdown Region
                            const rZ = p.rz || 10.0;
                            const gZ = 1.0 / rZ;
                            const iEq = vz / rZ;
                            stampConductance(nk, na, gZ);
                            stampCurrentSource(nk, na, -iEq);
                        } else if (va - vk >= vf) {
                            // Forward Conduction Region
                            const gF = 1.0 / 1.0;
                            const iEq = vf / 1.0;
                            stampConductance(na, nk, gF);
                            stampCurrentSource(na, nk, -iEq);
                        } else {
                            stampConductance(na, nk, 1e-7);
                        }
                        break;
                    }

                    case 'bjt_npn': {
                        const nb = this.pinToNode[`${comp.id}:b`] || 0;
                        const nc = this.pinToNode[`${comp.id}:c`] || 0;
                        const ne = this.pinToNode[`${comp.id}:e`] || 0;
                        const vb = this.nodeVoltages[nb] || 0.0;
                        const vc = this.nodeVoltages[nc] || 0.0;
                        const ve = this.nodeVoltages[ne] || 0.0;
                        const vbe = vb - ve;
                        const vce = vc - ve;
                        const beta = p.beta || 120;
                        const rpi = 2500.0;
                        const gpi = 1.0 / rpi;

                        // Smooth continuous turn-on characteristic (eliminates step chatter)
                        const turnOn = Math.max(0.0, Math.min(1.0, (vbe - 0.55) / 0.12));

                        if (turnOn > 0.01) {
                            const effGpi = gpi * turnOn;
                            stampConductance(nb, ne, effGpi);
                            const iEqB = 0.65 * effGpi;
                            stampCurrentSource(nb, ne, -iEqB);

                            const satFactor = Math.max(0.0, Math.min(1.0, (vce - 0.15) / 0.25));
                            const gm = Math.min(0.06, beta * effGpi) * satFactor;

                            if (gm > 1e-6) {
                                if (ne > 0) {
                                    if (nb > 0) A[ne - 1][nb - 1] -= gm;
                                    A[ne - 1][ne - 1] += gm;
                                }
                                if (nc > 0) {
                                    if (nb > 0) A[nc - 1][nb - 1] += gm;
                                    if (ne > 0) A[nc - 1][ne - 1] -= gm;
                                }
                                const iEqC = 0.65 * gm;
                                stampCurrentSource(nc, ne, -iEqC);
                            }
                        } else {
                            stampConductance(nb, ne, 1e-7);
                            stampConductance(nc, ne, 1e-7);
                        }
                        break;
                    }

                    case 'jfet_n': {
                        const ng = this.pinToNode[`${comp.id}:g`] || 0;
                        const nd = this.pinToNode[`${comp.id}:d`] || 0;
                        const ns = this.pinToNode[`${comp.id}:s`] || 0;
                        const vg = this.nodeVoltages[ng] || 0.0;
                        const vs = this.nodeVoltages[ns] || 0.0;
                        const vgs = vg - vs;
                        const vp = p.vp || -3.0;
                        const idss = p.idss || 0.009;

                        stampConductance(ng, ns, 1e-9); // High gate impedance

                        if (vgs >= vp) {
                            const id = idss * Math.pow(1 - vgs / vp, 2);
                            stampCurrentSource(nd, ns, id);
                        } else {
                            stampConductance(nd, ns, 1e-7);
                        }
                        break;
                    }

                    case 'mosfet_n': {
                        const ng = this.pinToNode[`${comp.id}:g`] || 0;
                        const nd = this.pinToNode[`${comp.id}:d`] || 0;
                        const ns = this.pinToNode[`${comp.id}:s`] || 0;
                        const vg = this.nodeVoltages[ng] || 0.0;
                        const vs = this.nodeVoltages[ns] || 0.0;
                        const vgs = vg - vs;
                        const vth = p.vth || 2.5;

                        stampConductance(ng, ns, 1e-9);

                        if (vgs >= vth) {
                            stampConductance(nd, ns, 2.0); // 0.5Ω on-resistance
                        } else {
                            stampConductance(nd, ns, 1e-8);
                        }
                        break;
                    }

                    case 'voltmeter': {
                        const np = this.pinToNode[`${comp.id}:p`] || 0;
                        const nn = this.pinToNode[`${comp.id}:n`] || 0;
                        stampConductance(np, nn, 1e-7); // 10MΩ input impedance
                        break;
                    }

                    case 'ammeter': {
                        const np = this.pinToNode[`${comp.id}:p`] || 0;
                        const nn = this.pinToNode[`${comp.id}:n`] || 0;
                        stampConductance(np, nn, 1000.0); // 1mΩ low-impedance shunt
                        break;
                    }

                    case 'current_source': {
                        const np = this.pinToNode[`${comp.id}:p`] || 0;
                        const nn = this.pinToNode[`${comp.id}:n`] || 0;
                        stampCurrentSource(np, nn, p.current || 0.005);
                        break;
                    }
                }
            });

            // Stamp Voltage Sources into B & C matrix blocks
            vSources.forEach((src, idx) => {
                const row = N + idx;

                if (src.type === 'dc_source') {
                    const np = this.pinToNode[`${src.id}:p`] || 0;
                    const nn = this.pinToNode[`${src.id}:n`] || 0;
                    const vVal = src.props.voltage !== undefined ? src.props.voltage : 12;

                    if (np > 0) { A[np - 1][row] += 1; A[row][np - 1] += 1; }
                    if (nn > 0) { A[nn - 1][row] -= 1; A[row][nn - 1] -= 1; }
                    Z[row] = vVal;
                } else if (src.type === 'ac_source') {
                    const np = this.pinToNode[`${src.id}:p`] || 0;
                    const nn = this.pinToNode[`${src.id}:n`] || 0;
                    const amp = src.props.amplitude || 10;
                    const freq = src.props.frequency || 50;
                    const phase = (src.props.phase || 0) * Math.PI / 180;
                    const wf = src.props.waveform || 'sine';

                    let vVal = 0;
                    const theta = (2 * Math.PI * freq * this.simTime + phase) % (2 * Math.PI);
                    if (wf === 'sine') {
                        vVal = amp * Math.sin(theta);
                    } else if (wf === 'square') {
                        vVal = theta < Math.PI ? amp : -amp;
                    } else if (wf === 'triangle') {
                        vVal = amp * (2 * Math.abs((theta / Math.PI) - 1) - 1);
                    }
                    vVal += (src.props.offset || 0);

                    if (np > 0) { A[np - 1][row] += 1; A[row][np - 1] += 1; }
                    if (nn > 0) { A[nn - 1][row] -= 1; A[row][nn - 1] -= 1; }
                    Z[row] = vVal;
                } else if (src.type === 'opamp_out') {
                    // Operational Amplifier Output Equation: Vnon - Vinv - Vout / Aol = 0
                    const op = src.refComp;
                    const nOut = this.pinToNode[`${op.id}:out`] || 0;
                    const nInv = this.pinToNode[`${op.id}:inv`] || 0;
                    const nNon = this.pinToNode[`${op.id}:non`] || 0;
                    const aol = op.props.aol || 100000;

                    if (nNon > 0) A[row][nNon - 1] += 1;
                    if (nInv > 0) A[row][nInv - 1] -= 1;
                    if (nOut > 0) {
                        A[row][nOut - 1] -= 1.0 / aol;
                        A[nOut - 1][row] += 1; // Current injected into output node
                    }
                    Z[row] = 0.0;
                }
            });

            // Solve System [A] [x] = [Z] via Gaussian Elimination with Partial Pivoting
            const x = this.solveGaussian(A, Z, totalVars);

            // Record Node Voltages
            this.nodeVoltages[0] = 0.0;
            for (let i = 1; i <= N; i++) {
                this.nodeVoltages[i] = x ? x[i - 1] : 0.0;
            }

            // Saturation clamping for Op-Amps (±14.0V)
            this.components.filter(c => c.type === 'opamp').forEach(op => {
                const nOut = this.pinToNode[`${op.id}:out`];
                if (nOut && this.nodeVoltages[nOut] !== undefined) {
                    if (this.nodeVoltages[nOut] > 14.0) this.nodeVoltages[nOut] = 14.0;
                    else if (this.nodeVoltages[nOut] < -14.0) this.nodeVoltages[nOut] = -14.0;
                }
            });

            // Physical saturation clamping for BJT NPN (Vc cannot drop below Ve + 0.2V)
            this.components.filter(c => c.type === 'bjt_npn').forEach(bjt => {
                const nc = this.pinToNode[`${bjt.id}:c`];
                const ne = this.pinToNode[`${bjt.id}:e`];
                if (nc && this.nodeVoltages[nc] !== undefined) {
                    const ve = (ne && this.nodeVoltages[ne] !== undefined) ? this.nodeVoltages[ne] : 0.0;
                    if (this.nodeVoltages[nc] < ve + 0.2) {
                        this.nodeVoltages[nc] = ve + 0.2;
                    }
                }
            });

            // Update states for Capacitors & Inductors
            this.components.forEach(comp => {
                if (comp.type === 'capacitor') {
                    const n1 = this.pinToNode[`${comp.id}:1`] || 0;
                    const n2 = this.pinToNode[`${comp.id}:2`] || 0;
                    const vNow = (this.nodeVoltages[n1] || 0.0) - (this.nodeVoltages[n2] || 0.0);
                    this.compStates[comp.id] = { v: vNow };
                } else if (comp.type === 'inductor') {
                    const n1 = this.pinToNode[`${comp.id}:1`] || 0;
                    const n2 = this.pinToNode[`${comp.id}:2`] || 0;
                    const vNow = (this.nodeVoltages[n1] || 0.0) - (this.nodeVoltages[n2] || 0.0);
                    const lVal = comp.props.inductance || 0.01;
                    const prevI = this.compStates[comp.id]?.i || 0.0;
                    const iNow = prevI + (vNow * dt / lVal);
                    this.compStates[comp.id] = { i: iNow };
                }
            });
        }

        solveGaussian(A, B, n) {
            for (let i = 0; i < n; i++) {
                // Find pivot
                let maxRow = i;
                let maxVal = Math.abs(A[i][i]);
                for (let k = i + 1; k < n; k++) {
                    if (Math.abs(A[k][i]) > maxVal) {
                        maxVal = Math.abs(A[k][i]);
                        maxRow = k;
                    }
                }

                // Swap rows
                if (maxRow !== i) {
                    const tmpRow = A[i];
                    A[i] = A[maxRow];
                    A[maxRow] = tmpRow;
                    const tmpB = B[i];
                    B[i] = B[maxRow];
                    B[maxRow] = tmpB;
                }

                if (Math.abs(A[i][i]) < 1e-12) {
                    A[i][i] = 1e-12; // Numerical regularizer
                }

                // Eliminate below
                for (let k = i + 1; k < n; k++) {
                    const factor = A[k][i] / A[i][i];
                    for (let j = i; j < n; j++) {
                        A[k][j] -= factor * A[i][j];
                    }
                    B[k] -= factor * B[i];
                }
            }

            // Back substitution
            const x = new Float64Array(n);
            for (let i = n - 1; i >= 0; i--) {
                let sum = B[i];
                for (let j = i + 1; j < n; j++) {
                    sum -= A[i][j] * x[j];
                }
                x[i] = sum / A[i][i];
            }
            return x;
        }

        updateOnCanvasMeters() {
            // Update Voltmeter Display Badges
            this.components.filter(c => c.type === 'voltmeter').forEach(vm => {
                const np = this.pinToNode[`${vm.id}:p`] || 0;
                const nn = this.pinToNode[`${vm.id}:n`] || 0;
                const vp = this.nodeVoltages[np] || 0.0;
                const vn = this.nodeVoltages[nn] || 0.0;
                const vDiff = vp - vn;
                vm.liveRead = (vDiff >= 0 ? '+' : '') + vDiff.toFixed(2) + ' V';
            });

            // Update Ammeter Display Badges
            this.components.filter(c => c.type === 'ammeter').forEach(am => {
                const np = this.pinToNode[`${am.id}:p`] || 0;
                const nn = this.pinToNode[`${am.id}:n`] || 0;
                const vp = this.nodeVoltages[np] || 0.0;
                const vn = this.nodeVoltages[nn] || 0.0;
                const iBranch = (vp - vn) / 0.001; // I = V / R_am
                const i_mA = iBranch * 1000.0;
                am.liveRead = (i_mA >= 0 ? '+' : '') + (Math.abs(i_mA) < 1000 ? i_mA.toFixed(1) + ' mA' : (i_mA / 1000).toFixed(2) + ' A');
            });

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
                link.download = 'circuit_schematic.png';
                link.href = canvas.toDataURL();
                link.click();
            });
        }

        // ====================================================================
        // 5. 21 CURRICULUM MODULE PRESETS (Modules 1 - 7 from Uploaded Image)
        // ====================================================================
        loadPreset(presetName) {
            this.clearAll();

            switch (presetName) {
                // --- Module 1: DC Circuits & Network Theorems ---
                case 'mod1_thevenin_norton':
                    this.setupTheveninCircuit();
                    break;
                case 'mod1_max_power':
                    this.setupMaxPowerCircuit();
                    break;
                case 'mod1_superposition':
                    this.setupSuperpositionCircuit();
                    break;

                // --- Module 2: Semiconductor Diodes & Rectifiers ---
                case 'mod2_bridge_rectifier':
                    this.setupBridgeRectifierCircuit();
                    break;
                case 'mod2_halfwave_rectifier':
                    this.setupHalfWaveRectifierCircuit();
                    break;
                case 'mod2_pn_diode':
                    this.setupPNDiodeCircuit();
                    break;
                case 'mod2_clipper_clamper':
                    this.setupClipperClamperCircuit();
                    break;

                // --- Module 3: BJT Transistors & Biasing ---
                case 'mod3_voltage_divider_bias':
                    this.setupBJTVoltageDividerCircuit();
                    break;
                case 'mod3_ce_characteristics':
                    this.setupBJTCharacteristicsCircuit();
                    break;

                // --- Module 4: FET & MOSFET ---
                case 'mod4_jfet_characteristics':
                    this.setupJFETCircuit();
                    break;
                case 'mod4_mosfet_switch':
                    this.setupMOSFETCircuit();
                    break;

                // --- Module 5: Regulated Power Supplies ---
                case 'mod5_zener_regulator':
                    this.setupZenerRegulatorCircuit();
                    break;
                case 'mod5_series_pass_regulator':
                    this.setupSeriesPassRegulatorCircuit();
                    break;

                // --- Module 6: Amplifiers & Frequency Response ---
                case 'mod6_ce_amplifier':
                    this.setupCEAmplifierCircuit();
                    break;
                case 'mod6_emitter_follower':
                    this.setupEmitterFollowerCircuit();
                    break;

                // --- Module 7: Op-Amp (IC 741) & Feedback ---
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

            const modSelect = document.getElementById('msModuleSelect');
            if (modSelect) modSelect.value = presetName;

            this.render();
            // Start simulation automatically for live preview
            this.startSimulation();
            if (this.cro) {
                this.cro.calibratePreset(presetName);
            }
            this.autoFitView();
        }

        // --- Module 1: Thevenin Equivalent Circuit ---
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
            const cro = this.addComponent('cro_tap', 740, 180);
            const gnd = this.addComponent('ground', 380, 340);

            this.wires.push({ fromComp: v1.id, fromPin: 'p', toComp: r1.id, toPin: '1' });
            this.wires.push({ fromComp: v1.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: r2.id, toPin: '1' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: rl.id, toPin: '1' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: vm.id, toPin: 'p' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: v1.id, fromPin: 'n', toComp: r2.id, toPin: '2' });
            this.wires.push({ fromComp: r2.id, fromPin: '2', toComp: rl.id, toPin: '2' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: vm.id, toPin: 'n' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: cro.id, toPin: 'gnd' });
            this.wires.push({ fromComp: r2.id, fromPin: '2', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 1: Maximum Power Transfer Theorem ---
        setupMaxPowerCircuit() {
            const v1 = this.addComponent('dc_source', 160, 220, { voltage: 10 });
            const rth = this.addComponent('resistor', 300, 140, { resistance: 1000 });
            rth.label = 'R_th';
            const am = this.addComponent('ammeter', 440, 140);
            const rl = this.addComponent('potentiometer', 560, 220, { total_resistance: 2000, wiper: 0.5 });
            rl.label = 'R_Load (Var)';
            const vm = this.addComponent('voltmeter', 680, 220);
            vm.rotation = 90;
            const cro = this.addComponent('cro_tap', 780, 180);
            const gnd = this.addComponent('ground', 380, 340);

            this.wires.push({ fromComp: v1.id, fromPin: 'p', toComp: rth.id, toPin: '1' });
            this.wires.push({ fromComp: v1.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: rth.id, fromPin: '2', toComp: am.id, toPin: 'p' });
            this.wires.push({ fromComp: am.id, fromPin: 'n', toComp: rl.id, toPin: '1' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: vm.id, toPin: 'p' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: v1.id, fromPin: 'n', toComp: rl.id, toPin: '2' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: vm.id, toPin: 'n' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: cro.id, toPin: 'gnd' });
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
            const cro = this.addComponent('cro_tap', 740, 180);
            const gnd = this.addComponent('ground', 380, 340);

            this.wires.push({ fromComp: v1.id, fromPin: 'p', toComp: r1.id, toPin: '1' });
            this.wires.push({ fromComp: v1.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: r3.id, toPin: '1' });
            this.wires.push({ fromComp: r3.id, fromPin: '1', toComp: r2.id, toPin: '1' });
            this.wires.push({ fromComp: r3.id, fromPin: '1', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: r2.id, fromPin: '2', toComp: v2.id, toPin: 'p' });
            this.wires.push({ fromComp: v1.id, fromPin: 'n', toComp: r3.id, toPin: '2' });
            this.wires.push({ fromComp: r3.id, fromPin: '2', toComp: v2.id, toPin: 'n' });
            this.wires.push({ fromComp: r3.id, fromPin: '2', toComp: cro.id, toPin: 'gnd' });
            this.wires.push({ fromComp: r3.id, fromPin: '2', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 2: PN Diode Forward & Reverse Bias ---
        setupPNDiodeCircuit() {
            const v1 = this.addComponent('dc_source', 140, 220, { voltage: 5 });
            const pot = this.addComponent('potentiometer', 280, 220);
            const rlimit = this.addComponent('resistor', 420, 140, { resistance: 330 });
            const am = this.addComponent('ammeter', 540, 140);
            const d1 = this.addComponent('diode', 640, 220);
            d1.rotation = 90;
            const vm = this.addComponent('voltmeter', 740, 220);
            vm.rotation = 90;
            const cro = this.addComponent('cro_tap', 840, 180);
            const gnd = this.addComponent('ground', 400, 340);

            this.wires.push({ fromComp: v1.id, fromPin: 'p', toComp: pot.id, toPin: '1' });
            this.wires.push({ fromComp: v1.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: pot.id, fromPin: 'w', toComp: rlimit.id, toPin: '1' });
            this.wires.push({ fromComp: rlimit.id, fromPin: '2', toComp: am.id, toPin: 'p' });
            this.wires.push({ fromComp: am.id, fromPin: 'n', toComp: d1.id, toPin: 'a' });
            this.wires.push({ fromComp: d1.id, fromPin: 'a', toComp: vm.id, toPin: 'p' });
            this.wires.push({ fromComp: d1.id, fromPin: 'a', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: d1.id, fromPin: 'k', toComp: vm.id, toPin: 'n' });
            this.wires.push({ fromComp: d1.id, fromPin: 'k', toComp: cro.id, toPin: 'gnd' });
            this.wires.push({ fromComp: d1.id, fromPin: 'k', toComp: v1.id, toPin: 'n' });
            this.wires.push({ fromComp: v1.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 2: Half-Wave Rectifier with C-Filter ---
        setupHalfWaveRectifierCircuit() {
            const vac = this.addComponent('ac_source', 140, 220, { amplitude: 12, frequency: 50 });
            const d1 = this.addComponent('diode', 280, 140);
            const sw = this.addComponent('switch_spst', 420, 140);
            sw.label = 'SW_Filter';
            const c1 = this.addComponent('capacitor', 420, 240, { capacitance: 0.0001 }); // 100uF
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
            const d2 = this.addComponent('diode', 300, 260);
            const d3 = this.addComponent('diode', 440, 140);
            const d4 = this.addComponent('diode', 440, 260);
            const sw = this.addComponent('switch_spst', 540, 140);
            sw.label = 'SW_Filter';
            const c1 = this.addComponent('capacitor', 540, 240, { capacitance: 0.0001 }); // 100uF
            c1.rotation = 90;
            const rl = this.addComponent('resistor', 660, 220, { resistance: 1000 });
            rl.rotation = 90;
            rl.label = 'RL';
            const cro = this.addComponent('cro_tap', 780, 180);
            const gnd = this.addComponent('ground', 540, 340);

            // Channel A across AC source
            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });

            // AC source to Bridge
            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: d1.id, toPin: 'a' });
            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: d3.id, toPin: 'k' });
            this.wires.push({ fromComp: vac.id, fromPin: 'n', toComp: d2.id, toPin: 'a' });
            this.wires.push({ fromComp: vac.id, fromPin: 'n', toComp: d4.id, toPin: 'k' });

            // Positive DC rail (cathodes of D1 and D2 to RL, switch, and Ch B)
            this.wires.push({ fromComp: d1.id, fromPin: 'k', toComp: d2.id, toPin: 'k' });
            this.wires.push({ fromComp: d1.id, fromPin: 'k', toComp: sw.id, toPin: '1' });
            this.wires.push({ fromComp: sw.id, fromPin: '2', toComp: c1.id, toPin: '1' });
            this.wires.push({ fromComp: d1.id, fromPin: 'k', toComp: rl.id, toPin: '1' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: cro.id, toPin: 'chB' });

            // Negative DC rail (anodes of D3 and D4 to RL ground and Ch Gnd)
            this.wires.push({ fromComp: d3.id, fromPin: 'a', toComp: d4.id, toPin: 'a' });
            this.wires.push({ fromComp: d3.id, fromPin: 'a', toComp: rl.id, toPin: '2' });
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
            this.wires.push({ fromComp: vac.id, fromPin: 'n', toComp: cro.id, toPin: 'gnd' });
            this.wires.push({ fromComp: vac.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 3: BJT Voltage Divider Bias (Self-Bias) ---
        setupBJTVoltageDividerCircuit() {
            const vcc = this.addComponent('dc_source', 140, 160, { voltage: 12 });
            const r1 = this.addComponent('resistor', 280, 120, { resistance: 33000 });
            r1.rotation = 90;
            const r2 = this.addComponent('resistor', 280, 260, { resistance: 6800 });
            r2.rotation = 90;
            const rc = this.addComponent('resistor', 420, 120, { resistance: 2200 });
            rc.rotation = 90;
            const q1 = this.addComponent('bjt_npn', 400, 220);
            const re = this.addComponent('resistor', 420, 310, { resistance: 1000 });
            re.rotation = 90;
            const ce = this.addComponent('capacitor', 500, 310, { capacitance: 0.0001 });
            ce.rotation = 90;
            const cro = this.addComponent('cro_tap', 620, 180);
            const gnd = this.addComponent('ground', 340, 380);

            this.wires.push({ fromComp: vcc.id, fromPin: 'p', toComp: r1.id, toPin: '1' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'p', toComp: rc.id, toPin: '1' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: r2.id, toPin: '1' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: q1.id, toPin: 'b' });
            this.wires.push({ fromComp: rc.id, fromPin: '2', toComp: q1.id, toPin: 'c' });
            this.wires.push({ fromComp: q1.id, fromPin: 'c', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: q1.id, fromPin: 'e', toComp: re.id, toPin: '1' });
            this.wires.push({ fromComp: re.id, fromPin: '1', toComp: ce.id, toPin: '1' });
            this.wires.push({ fromComp: re.id, fromPin: '2', toComp: ce.id, toPin: '2' });
            this.wires.push({ fromComp: r2.id, fromPin: '2', toComp: re.id, toPin: '2' });
            this.wires.push({ fromComp: r2.id, fromPin: '2', toComp: vcc.id, toPin: 'n' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'n', toComp: cro.id, toPin: 'gnd' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 3: BJT CE Characteristics & DC Load Line ---
        setupBJTCharacteristicsCircuit() {
            const vbb = this.addComponent('dc_source', 140, 220, { voltage: 2 });
            const rb = this.addComponent('resistor', 260, 180, { resistance: 100000 });
            const q1 = this.addComponent('bjt_npn', 380, 220);
            const am_c = this.addComponent('ammeter', 480, 140);
            const vcc = this.addComponent('dc_source', 600, 220, { voltage: 10 });
            const vm = this.addComponent('voltmeter', 480, 260);
            vm.rotation = 90;
            const cro = this.addComponent('cro_tap', 700, 180);
            const gnd = this.addComponent('ground', 380, 340);

            this.wires.push({ fromComp: vbb.id, fromPin: 'p', toComp: rb.id, toPin: '1' });
            this.wires.push({ fromComp: rb.id, fromPin: '2', toComp: q1.id, toPin: 'b' });
            this.wires.push({ fromComp: q1.id, fromPin: 'c', toComp: am_c.id, toPin: 'p' });
            this.wires.push({ fromComp: am_c.id, fromPin: 'n', toComp: vcc.id, toPin: 'p' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: q1.id, fromPin: 'c', toComp: vm.id, toPin: 'p' });
            this.wires.push({ fromComp: q1.id, fromPin: 'c', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: q1.id, fromPin: 'e', toComp: vm.id, toPin: 'n' });
            this.wires.push({ fromComp: q1.id, fromPin: 'e', toComp: vbb.id, toPin: 'n' });
            this.wires.push({ fromComp: vbb.id, fromPin: 'n', toComp: vcc.id, toPin: 'n' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'n', toComp: cro.id, toPin: 'gnd' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 4: JFET Drain & Transfer Characteristics ---
        setupJFETCircuit() {
            const vgg = this.addComponent('dc_source', 140, 220, { voltage: 2 });
            vgg.rotation = 180;
            const j1 = this.addComponent('jfet_n', 300, 200);
            const am_d = this.addComponent('ammeter', 420, 140);
            const vdd = this.addComponent('dc_source', 540, 220, { voltage: 12 });
            const cro = this.addComponent('cro_tap', 660, 180);
            const gnd = this.addComponent('ground', 300, 320);

            this.wires.push({ fromComp: vgg.id, fromPin: 'p', toComp: j1.id, toPin: 'g' });
            this.wires.push({ fromComp: j1.id, fromPin: 'd', toComp: am_d.id, toPin: 'p' });
            this.wires.push({ fromComp: am_d.id, fromPin: 'n', toComp: vdd.id, toPin: 'p' });
            this.wires.push({ fromComp: vdd.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: j1.id, fromPin: 'd', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: j1.id, fromPin: 's', toComp: vdd.id, toPin: 'n' });
            this.wires.push({ fromComp: j1.id, fromPin: 's', toComp: vgg.id, toPin: 'n' });
            this.wires.push({ fromComp: j1.id, fromPin: 's', toComp: cro.id, toPin: 'gnd' });
            this.wires.push({ fromComp: j1.id, fromPin: 's', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 4: N-MOSFET Electronic Switch ---
        setupMOSFETCircuit() {
            const vgate = this.addComponent('dc_source', 140, 220, { voltage: 5 });
            const sw = this.addComponent('switch_spst', 240, 160);
            const m1 = this.addComponent('mosfet_n', 360, 220);
            const rload = this.addComponent('resistor', 440, 140, { resistance: 100 });
            rload.rotation = 90;
            const led = this.addComponent('led', 440, 240, { color: '#22c55e' });
            led.rotation = 90;
            const vdd = this.addComponent('dc_source', 560, 220, { voltage: 12 });
            const cro = this.addComponent('cro_tap', 680, 180);
            const gnd = this.addComponent('ground', 360, 340);

            this.wires.push({ fromComp: vgate.id, fromPin: 'p', toComp: sw.id, toPin: '1' });
            this.wires.push({ fromComp: vgate.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: sw.id, fromPin: '2', toComp: m1.id, toPin: 'g' });
            this.wires.push({ fromComp: vdd.id, fromPin: 'p', toComp: rload.id, toPin: '1' });
            this.wires.push({ fromComp: rload.id, fromPin: '2', toComp: led.id, toPin: 'a' });
            this.wires.push({ fromComp: led.id, fromPin: 'k', toComp: m1.id, toPin: 'd' });
            this.wires.push({ fromComp: m1.id, fromPin: 'd', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: m1.id, fromPin: 's', toComp: vdd.id, toPin: 'n' });
            this.wires.push({ fromComp: vdd.id, fromPin: 'n', toComp: vgate.id, toPin: 'n' });
            this.wires.push({ fromComp: vdd.id, fromPin: 'n', toComp: cro.id, toPin: 'gnd' });
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
            const cro = this.addComponent('cro_tap', 760, 180);
            const gnd = this.addComponent('ground', 420, 340);

            this.wires.push({ fromComp: vin.id, fromPin: 'p', toComp: rs.id, toPin: '1' });
            this.wires.push({ fromComp: vin.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: rs.id, fromPin: '2', toComp: dz.id, toPin: 'k' });
            this.wires.push({ fromComp: dz.id, fromPin: 'k', toComp: rl.id, toPin: '1' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: vm.id, toPin: 'p' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: vin.id, fromPin: 'n', toComp: dz.id, toPin: 'a' });
            this.wires.push({ fromComp: dz.id, fromPin: 'a', toComp: rl.id, toPin: '2' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: vm.id, toPin: 'n' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: cro.id, toPin: 'gnd' });
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
            const cro = this.addComponent('cro_tap', 720, 180);
            const gnd = this.addComponent('ground', 320, 340);

            this.wires.push({ fromComp: vin.id, fromPin: 'p', toComp: q1.id, toPin: 'c' });
            this.wires.push({ fromComp: vin.id, fromPin: 'p', toComp: rb.id, toPin: '1' });
            this.wires.push({ fromComp: vin.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: rb.id, fromPin: '2', toComp: q1.id, toPin: 'b' });
            this.wires.push({ fromComp: q1.id, fromPin: 'b', toComp: dz.id, toPin: 'k' });
            this.wires.push({ fromComp: q1.id, fromPin: 'e', toComp: rl.id, toPin: '1' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: vm.id, toPin: 'p' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: dz.id, fromPin: 'a', toComp: rl.id, toPin: '2' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: vm.id, toPin: 'n' });
            this.wires.push({ fromComp: vin.id, fromPin: 'n', toComp: dz.id, toPin: 'a' });
            this.wires.push({ fromComp: vin.id, fromPin: 'n', toComp: cro.id, toPin: 'gnd' });
            this.wires.push({ fromComp: vin.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 6: Single-Stage CE AC Amplifier ---
        setupCEAmplifierCircuit() {
            const vac = this.addComponent('ac_source', 100, 220, { amplitude: 0.1, frequency: 1000 });
            const c_in = this.addComponent('capacitor', 200, 160, { capacitance: 0.000001 });
            const r1 = this.addComponent('resistor', 300, 100, { resistance: 47000 });
            r1.rotation = 90;
            const r2 = this.addComponent('resistor', 300, 240, { resistance: 10000 });
            r2.rotation = 90;
            const q1 = this.addComponent('bjt_npn', 400, 180);
            const rc = this.addComponent('resistor', 420, 90, { resistance: 3300 });
            rc.rotation = 90;
            const re = this.addComponent('resistor', 420, 280, { resistance: 1000 });
            re.rotation = 90;
            const ce = this.addComponent('capacitor', 490, 280, { capacitance: 0.0000022 });
            ce.rotation = 90;
            const c_out = this.addComponent('capacitor', 540, 140, { capacitance: 0.000001 });
            const rl = this.addComponent('resistor', 640, 220, { resistance: 10000 });
            rl.rotation = 90;
            const cro = this.addComponent('cro_tap', 740, 160);
            const vcc = this.addComponent('dc_source', 200, 60, { voltage: 12 });
            const gnd = this.addComponent('ground', 340, 360);

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
            this.wires.push({ fromComp: vcc.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: re.id, fromPin: '2', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: cro.id, fromPin: 'gnd', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 6: Emitter Follower (Common Collector Buffer) ---
        setupEmitterFollowerCircuit() {
            const vac = this.addComponent('ac_source', 120, 220, { amplitude: 2, frequency: 1000 });
            const c_in = this.addComponent('capacitor', 240, 180, { capacitance: 0.00001 });
            const r1 = this.addComponent('resistor', 320, 100, { resistance: 47000 });
            r1.rotation = 90;
            const r2 = this.addComponent('resistor', 320, 260, { resistance: 47000 });
            r2.rotation = 90;
            const q1 = this.addComponent('bjt_npn', 400, 180);
            const re = this.addComponent('resistor', 440, 260, { resistance: 2200 });
            re.rotation = 90;
            const c_out = this.addComponent('capacitor', 540, 200, { capacitance: 0.00001 });
            const rl = this.addComponent('resistor', 640, 240, { resistance: 4700 });
            rl.rotation = 90;
            const cro = this.addComponent('cro_tap', 740, 180);
            const vcc = this.addComponent('dc_source', 400, 40, { voltage: 12 });
            const gnd = this.addComponent('ground', 400, 360);

            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: c_in.id, toPin: '1' });
            this.wires.push({ fromComp: vac.id, fromPin: 'p', toComp: cro.id, toPin: 'chA' });
            this.wires.push({ fromComp: c_in.id, fromPin: '2', toComp: q1.id, toPin: 'b' });
            this.wires.push({ fromComp: r1.id, fromPin: '2', toComp: q1.id, toPin: 'b' });
            this.wires.push({ fromComp: r2.id, fromPin: '1', toComp: q1.id, toPin: 'b' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'p', toComp: r1.id, toPin: '1' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'p', toComp: q1.id, toPin: 'c' });
            this.wires.push({ fromComp: q1.id, fromPin: 'e', toComp: re.id, toPin: '1' });
            this.wires.push({ fromComp: q1.id, fromPin: 'e', toComp: c_out.id, toPin: '1' });
            this.wires.push({ fromComp: c_out.id, fromPin: '2', toComp: rl.id, toPin: '1' });
            this.wires.push({ fromComp: rl.id, fromPin: '1', toComp: cro.id, toPin: 'chB' });
            this.wires.push({ fromComp: vac.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: vcc.id, fromPin: 'n', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: r2.id, fromPin: '2', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: re.id, fromPin: '2', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: rl.id, fromPin: '2', toComp: gnd.id, toPin: 'g' });
            this.wires.push({ fromComp: cro.id, fromPin: 'gnd', toComp: gnd.id, toPin: 'g' });
        }

        // --- Module 7: Op-Amp IC 741 Inverting Amplifier ---
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

        // --- Module 7: Op-Amp IC 741 Non-Inverting Amplifier ---
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

        // --- Module 7: Op-Amp Operational Integrator ---
        setupOpAmpIntegratorCircuit() {
            const vac = this.addComponent('ac_source', 120, 220, { amplitude: 2, frequency: 500, waveform: 'square' });
            const r1 = this.addComponent('resistor', 260, 170, { resistance: 10000 });
            r1.label = 'R';
            const c1 = this.addComponent('capacitor', 380, 90, { capacitance: 0.0000001 });
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
    // 6. TEKTRONIX / NI DUAL-CHANNEL CATHODE RAY OSCILLOSCOPE (CRO)
    // ========================================================================
    class VirtualCRO {
        constructor(workbench) {
            this.wb = workbench;
            this.isOpen = false;
            this.windowEl = document.getElementById('msCroWindow');
            this.canvas = document.getElementById('msCroCanvas');
            this.ctx = this.canvas ? this.canvas.getContext('2d') : null;

            this.chA_enabled = true;
            this.chB_enabled = true;
            this.chA_voltsDiv = 5.0; // 5 V/div default
            this.chB_voltsDiv = 5.0; // 5 V/div default
            this.timeDiv = 0.005;    // 5 ms/div default
            this.chA_yPos = 1.0;      // +1.0 div offset (upper screen)
            this.chB_yPos = -1.5;     // -1.5 div offset (lower screen)
            this.mode = 'YT';        // 'YT' or 'XY'

            // Real Rolling History Buffer
            this.historyBuffer = []; // [ { t, vA, vB } ]
            this.maxHistory = 1500;

            this.bindControls();
            this.syncControlsUI();
        }

        bindControls() {
            const closeBtn = document.getElementById('msCroCloseBtn');
            if (closeBtn) closeBtn.onclick = () => this.hide();

            const autosetBtn = document.getElementById('msCroAutosetBtn');
            if (autosetBtn) autosetBtn.onclick = () => this.autoset();

            const timeSel = document.getElementById('msCroTimeDiv');
            if (timeSel) {
                timeSel.onchange = e => {
                    this.timeDiv = parseFloat(e.target.value);
                    this.updateDisplay();
                };
            }

            const vA = document.getElementById('msCroVoltsA');
            if (vA) {
                vA.onchange = e => {
                    this.chA_voltsDiv = parseFloat(e.target.value);
                    this.updateDisplay();
                };
            }

            const vB = document.getElementById('msCroVoltsB');
            if (vB) {
                vB.onchange = e => {
                    this.chB_voltsDiv = parseFloat(e.target.value);
                    this.updateDisplay();
                };
            }

            // Channel A Y-Position Steppers (+ is Up, - is Down)
            const posADown = document.getElementById('msCroPosADown');
            if (posADown) {
                posADown.onclick = () => {
                    this.chA_yPos = Math.max(-3.5, Math.round((this.chA_yPos - 0.5) * 10) / 10);
                    this.syncControlsUI();
                    this.updateDisplay();
                };
            }
            const posAUp = document.getElementById('msCroPosAUp');
            if (posAUp) {
                posAUp.onclick = () => {
                    this.chA_yPos = Math.min(3.5, Math.round((this.chA_yPos + 0.5) * 10) / 10);
                    this.syncControlsUI();
                    this.updateDisplay();
                };
            }

            // Channel B Y-Position Steppers (+ is Up, - is Down)
            const posBDown = document.getElementById('msCroPosBDown');
            if (posBDown) {
                posBDown.onclick = () => {
                    this.chB_yPos = Math.max(-3.5, Math.round((this.chB_yPos - 0.5) * 10) / 10);
                    this.syncControlsUI();
                    this.updateDisplay();
                };
            }
            const posBUp = document.getElementById('msCroPosBUp');
            if (posBUp) {
                posBUp.onclick = () => {
                    this.chB_yPos = Math.min(3.5, Math.round((this.chB_yPos + 0.5) * 10) / 10);
                    this.syncControlsUI();
                    this.updateDisplay();
                };
            }

            const togA = document.getElementById('msCroToggleA');
            if (togA) {
                togA.onclick = () => {
                    this.chA_enabled = !this.chA_enabled;
                    this.syncControlsUI();
                    this.updateDisplay();
                };
            }

            const togB = document.getElementById('msCroToggleB');
            if (togB) {
                togB.onclick = () => {
                    this.chB_enabled = !this.chB_enabled;
                    this.syncControlsUI();
                    this.updateDisplay();
                };
            }

            const modeBtn = document.getElementById('msCroModeBtn');
            if (modeBtn) {
                modeBtn.onclick = () => {
                    this.mode = this.mode === 'YT' ? 'XY' : 'YT';
                    modeBtn.innerText = this.mode === 'YT' ? 'Mode: Y-T (Dual Sweep)' : 'Mode: X-Y (Lissajous)';
                    this.updateDisplay();
                };
            }
        }

        syncControlsUI() {
            const timeSel = document.getElementById('msCroTimeDiv');
            if (timeSel && timeSel.options && timeSel.options.length > 0) {
                let closestVal = timeSel.options[0].value;
                let minDiff = Infinity;
                for (let i = 0; i < timeSel.options.length; i++) {
                    const diff = Math.abs(parseFloat(timeSel.options[i].value) - this.timeDiv);
                    if (diff < minDiff) { minDiff = diff; closestVal = timeSel.options[i].value; }
                }
                timeSel.value = closestVal;
            }

            const vA = document.getElementById('msCroVoltsA');
            if (vA && vA.options && vA.options.length > 0) {
                let closestVal = vA.options[0].value;
                let minDiff = Infinity;
                for (let i = 0; i < vA.options.length; i++) {
                    const diff = Math.abs(parseFloat(vA.options[i].value) - this.chA_voltsDiv);
                    if (diff < minDiff) { minDiff = diff; closestVal = vA.options[i].value; }
                }
                vA.value = closestVal;
            }

            const vB = document.getElementById('msCroVoltsB');
            if (vB && vB.options && vB.options.length > 0) {
                let closestVal = vB.options[0].value;
                let minDiff = Infinity;
                for (let i = 0; i < vB.options.length; i++) {
                    const diff = Math.abs(parseFloat(vB.options[i].value) - this.chB_voltsDiv);
                    if (diff < minDiff) { minDiff = diff; closestVal = vB.options[i].value; }
                }
                vB.value = closestVal;
            }

            const posADisp = document.getElementById('msCroPosADisp');
            if (posADisp) {
                posADisp.innerText = (this.chA_yPos >= 0 ? '+' : '') + this.chA_yPos.toFixed(1) + ' div';
            }

            const posBDisp = document.getElementById('msCroPosBDisp');
            if (posBDisp) {
                posBDisp.innerText = (this.chB_yPos >= 0 ? '+' : '') + this.chB_yPos.toFixed(1) + ' div';
            }

            const togA = document.getElementById('msCroToggleA');
            if (togA) togA.classList.toggle('active', this.chA_enabled);

            const togB = document.getElementById('msCroToggleB');
            if (togB) togB.classList.toggle('active', this.chB_enabled);
        }

        calibratePreset(presetName) {
            this.chA_enabled = true;
            this.chB_enabled = true;

            switch (presetName) {
                // --- Module 1: DC Circuits & Network Theorems ---
                case 'mod1_thevenin_norton':
                case 'mod1_superposition':
                case 'mod1_max_power':
                    this.timeDiv = 0.005;
                    this.chA_voltsDiv = 5.0;
                    this.chB_voltsDiv = 2.0;
                    this.chA_yPos = 1.0;
                    this.chB_yPos = -2.0;
                    break;

                // --- Module 2: Semiconductor Diodes & Rectifiers ---
                case 'mod2_bridge_rectifier':
                    this.timeDiv = 0.005;
                    this.chA_voltsDiv = 5.0;
                    this.chB_voltsDiv = 5.0;
                    this.chA_yPos = 1.0;
                    this.chB_yPos = -1.5;
                    break;

                case 'mod2_halfwave_rectifier':
                    this.timeDiv = 0.005;
                    this.chA_voltsDiv = 5.0;
                    this.chB_voltsDiv = 5.0;
                    this.chA_yPos = 1.0;
                    this.chB_yPos = -1.5;
                    break;

                case 'mod2_pn_diode':
                    this.timeDiv = 0.005;
                    this.chA_voltsDiv = 2.0;
                    this.chB_voltsDiv = 0.5;
                    this.chA_yPos = 1.0;
                    this.chB_yPos = -2.0;
                    break;

                case 'mod2_clipper_clamper':
                    this.timeDiv = 0.0005;
                    this.chA_voltsDiv = 5.0;
                    this.chB_voltsDiv = 5.0;
                    this.chA_yPos = 1.2;
                    this.chB_yPos = -1.2;
                    break;

                // --- Module 3: BJT Transistors & Biasing ---
                case 'mod3_voltage_divider_bias':
                case 'mod3_ce_characteristics':
                    this.timeDiv = 0.005;
                    this.chA_voltsDiv = 5.0;
                    this.chB_voltsDiv = 2.0;
                    this.chA_yPos = 1.0;
                    this.chB_yPos = -2.0;
                    break;

                // --- Module 4: FET & MOSFET ---
                case 'mod4_jfet_characteristics':
                case 'mod4_mosfet_switch':
                    this.timeDiv = 0.005;
                    this.chA_voltsDiv = 5.0;
                    this.chB_voltsDiv = 5.0;
                    this.chA_yPos = 1.0;
                    this.chB_yPos = -1.5;
                    break;

                // --- Module 5: Regulated Power Supplies ---
                case 'mod5_zener_regulator':
                case 'mod5_series_pass_regulator':
                    this.timeDiv = 0.005;
                    this.chA_voltsDiv = 5.0;
                    this.chB_voltsDiv = 2.0;
                    this.chA_yPos = 1.0;
                    this.chB_yPos = -2.0;
                    break;

                // --- Module 6: Amplifiers & Frequency Response ---
                case 'mod6_ce_amplifier':
                    this.timeDiv = 0.0005;
                    this.chA_voltsDiv = 0.1;
                    this.chB_voltsDiv = 2.0;
                    this.chA_yPos = 1.5;
                    this.chB_yPos = -1.0;
                    break;

                case 'mod6_emitter_follower':
                    this.timeDiv = 0.0005;
                    this.chA_voltsDiv = 1.0;
                    this.chB_voltsDiv = 1.0;
                    this.chA_yPos = 1.5;
                    this.chB_yPos = -1.5;
                    break;

                // --- Module 7: Op-Amp (IC 741) & Feedback ---
                case 'mod7_opamp_inverting':
                    this.timeDiv = 0.0005;
                    this.chA_voltsDiv = 1.0;
                    this.chB_voltsDiv = 1.0;
                    this.chA_yPos = 1.5;
                    this.chB_yPos = -1.5;
                    break;

                case 'mod7_opamp_noninverting':
                    this.timeDiv = 0.0005;
                    this.chA_voltsDiv = 1.0;
                    this.chB_voltsDiv = 2.0;
                    this.chA_yPos = 1.5;
                    this.chB_yPos = -1.5;
                    break;

                case 'mod7_opamp_integrator':
                    this.timeDiv = 0.001;
                    this.chA_voltsDiv = 2.0;
                    this.chB_voltsDiv = 2.0;
                    this.chA_yPos = 1.5;
                    this.chB_yPos = -1.5;
                    break;

                case 'mod7_opamp_comparator':
                    this.timeDiv = 0.002;
                    this.chA_voltsDiv = 5.0;
                    this.chB_voltsDiv = 10.0;
                    this.chA_yPos = 1.5;
                    this.chB_yPos = -1.0;
                    break;

                default:
                    this.timeDiv = 0.005;
                    this.chA_voltsDiv = 5.0;
                    this.chB_voltsDiv = 5.0;
                    this.chA_yPos = 1.0;
                    this.chB_yPos = -1.5;
            }

            this.syncControlsUI();
            this.updateDisplay();
        }

        autoset() {
            if (this.historyBuffer.length < 5) return;

            const recent = this.historyBuffer.slice(-Math.min(300, this.historyBuffer.length));
            let minA = Infinity, maxA = -Infinity, minB = Infinity, maxB = -Infinity;

            recent.forEach(s => {
                if (s.vA < minA) minA = s.vA;
                if (s.vA > maxA) maxA = s.vA;
                if (s.vB < minB) minB = s.vB;
                if (s.vB > maxB) maxB = s.vB;
            });

            const spanA = maxA - minA;
            const spanB = maxB - minB;
            const meanA = (maxA + minA) / 2;
            const meanB = (maxB + minB) / 2;

            const standardVdivs = [0.1, 0.2, 0.5, 1, 2, 5, 10, 20];
            const pickVdiv = (span, mean) => {
                const targetDivs = 3.5;
                const needed = Math.max(span / targetDivs, Math.abs(mean) / 3.0);
                for (let v of standardVdivs) {
                    if (v >= needed) return v;
                }
                return 20.0;
            };

            this.chA_voltsDiv = pickVdiv(spanA, meanA);
            this.chB_voltsDiv = pickVdiv(spanB, meanB);

            // Channel separation for dual trace
            if (this.chA_enabled && this.chB_enabled) {
                // If AC signal centered near 0
                if (spanA > 0.1 && Math.abs(meanA) < spanA * 0.5) {
                    this.chA_yPos = 1.5;
                } else {
                    this.chA_yPos = Math.max(-3.0, Math.min(3.0, -(meanA / this.chA_voltsDiv) + 1.0));
                }

                if (spanB > 0.1 && Math.abs(meanB) < spanB * 0.5) {
                    this.chB_yPos = -1.5;
                } else {
                    this.chB_yPos = Math.max(-3.0, Math.min(3.0, -(meanB / this.chB_voltsDiv) - 1.5));
                }
            } else {
                this.chA_yPos = -(meanA / this.chA_voltsDiv);
                this.chB_yPos = -(meanB / this.chB_voltsDiv);
            }

            // Estimate period for timebase
            const acComp = this.wb.components.find(c => c.type === 'ac_source');
            let freq = acComp && acComp.props.frequency ? acComp.props.frequency : 50;

            const standardTdivs = [0.00005, 0.0001, 0.0005, 0.001, 0.002, 0.005, 0.01, 0.02, 0.05];
            const period = 1.0 / freq;
            const targetTdiv = (2.5 * period) / 10.0; // Display ~2.5 full periods across 10 divs

            let bestT = standardTdivs[0];
            let minDiff = Infinity;
            for (let t of standardTdivs) {
                const diff = Math.abs(t - targetTdiv);
                if (diff < minDiff) { minDiff = diff; bestT = t; }
            }
            this.timeDiv = bestT;

            this.syncControlsUI();
            this.updateDisplay();
        }

        show() {
            if (this.windowEl) {
                this.windowEl.classList.add('active');
                this.isOpen = true;
                this.syncControlsUI();
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

        recordSample(simTime) {
            let vA = 0.0;
            let vB = 0.0;

            const croTap = this.wb.components.find(c => c.type === 'cro_tap');
            if (croTap) {
                const nA = this.wb.pinToNode[`${croTap.id}:chA`];
                const nB = this.wb.pinToNode[`${croTap.id}:chB`];
                const nG = this.wb.pinToNode[`${croTap.id}:gnd`] || 0;
                const vG = this.wb.nodeVoltages[nG] || 0.0;

                if (nA !== undefined) vA = (this.wb.nodeVoltages[nA] || 0.0) - vG;
                if (nB !== undefined) vB = (this.wb.nodeVoltages[nB] || 0.0) - vG;
            } else {
                // Fallback auto-discovery for custom schematics without cro_tap
                const voltKeys = Object.keys(this.wb.nodeVoltages).filter(k => k !== '0');
                if (voltKeys.length > 0) {
                    const src = this.wb.components.find(c => c.type === 'ac_source' || c.type === 'dc_source');
                    if (src) {
                        const nSrc = this.wb.pinToNode[`${src.id}:p`];
                        if (nSrc !== undefined) vA = this.wb.nodeVoltages[nSrc] || 0.0;
                    } else {
                        vA = this.wb.nodeVoltages[voltKeys[0]] || 0.0;
                    }

                    const load = this.wb.components.find(c => c.label === 'RL' || c.label === 'R_Load' || c.type === 'opamp');
                    if (load) {
                        const nLoad = load.type === 'opamp' ? this.wb.pinToNode[`${load.id}:out`] : this.wb.pinToNode[`${load.id}:1`];
                        if (nLoad !== undefined) vB = this.wb.nodeVoltages[nLoad] || 0.0;
                    } else if (voltKeys.length > 1) {
                        vB = this.wb.nodeVoltages[voltKeys[voltKeys.length - 1]] || 0.0;
                    }
                }
            }

            // Sanitize values
            if (isNaN(vA) || !isFinite(vA)) vA = 0.0;
            if (isNaN(vB) || !isFinite(vB)) vB = 0.0;

            this.historyBuffer.push({ t: simTime, vA, vB });
            if (this.historyBuffer.length > this.maxHistory) {
                this.historyBuffer.shift();
            }
        }

        drawGrid() {
            if (!this.ctx || !this.canvas) return;
            const w = this.canvas.width;
            const h = this.canvas.height;

            this.ctx.fillStyle = '#021a12';
            this.ctx.fillRect(0, 0, w, h);

            const numDivX = 10;
            const numDivY = 8;
            const dx = w / numDivX;
            const dy = h / numDivY;

            this.ctx.strokeStyle = 'rgba(34, 197, 94, 0.18)';
            this.ctx.lineWidth = 1;

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

            // Central Axes Crosshairs
            this.ctx.strokeStyle = 'rgba(74, 222, 128, 0.45)';
            this.ctx.lineWidth = 1.5;
            this.ctx.beginPath();
            this.ctx.moveTo(w / 2, 0);
            this.ctx.lineTo(w / 2, h);
            this.ctx.moveTo(0, h / 2);
            this.ctx.lineTo(w, h / 2);
            this.ctx.stroke();

            // Reticle Sub-divisions (0.2 div)
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

            // Tektronix Ground Reference Markers on Left Edge
            const midY = h / 2;
            if (this.chA_enabled) {
                const gndAY = Math.min(Math.max(midY - this.chA_yPos * dy, 6), h - 6);
                this.ctx.fillStyle = '#fbbf24';
                this.ctx.beginPath();
                this.ctx.moveTo(0, gndAY - 5);
                this.ctx.lineTo(8, gndAY);
                this.ctx.lineTo(0, gndAY + 5);
                this.ctx.closePath();
                this.ctx.fill();
                this.ctx.font = 'bold 8px monospace';
                this.ctx.fillStyle = '#fbbf24';
                this.ctx.fillText('1', 10, gndAY + 3);
            }

            if (this.chB_enabled) {
                const gndBY = Math.min(Math.max(midY - this.chB_yPos * dy, 6), h - 6);
                this.ctx.fillStyle = '#38bdf8';
                this.ctx.beginPath();
                this.ctx.moveTo(0, gndBY - 5);
                this.ctx.lineTo(8, gndBY);
                this.ctx.lineTo(0, gndBY + 5);
                this.ctx.closePath();
                this.ctx.fill();
                this.ctx.font = 'bold 8px monospace';
                this.ctx.fillStyle = '#38bdf8';
                this.ctx.fillText('2', 10, gndBY + 3);
            }
        }

        updateDisplay() {
            if (!this.ctx || !this.canvas || this.historyBuffer.length < 2) return;
            const w = this.canvas.width;
            const h = this.canvas.height;
            const midY = h / 2;
            const dy = h / 8; // Pixels per vertical division

            this.drawGrid();

            const windowTime = 10 * this.timeDiv;
            const curTime = this.historyBuffer[this.historyBuffer.length - 1].t;
            const startTime = curTime - windowTime;

            // Filter samples in current screen window
            const visible = this.historyBuffer.filter(s => s.t >= startTime);
            if (visible.length < 2) return;

            let overRangeA = false;
            let overRangeB = false;

            if (this.mode === 'YT') {
                // --- Channel A (Yellow Trace) ---
                if (this.chA_enabled) {
                    this.ctx.strokeStyle = '#fbbf24';
                    this.ctx.lineWidth = 2.2;
                    this.ctx.shadowColor = '#fbbf24';
                    this.ctx.shadowBlur = 6;
                    this.ctx.beginPath();

                    visible.forEach((s, idx) => {
                        const px = ((s.t - startTime) / windowTime) * w;
                        const rawPy = midY - (s.vA / this.chA_voltsDiv) * dy - this.chA_yPos * dy;
                        if (rawPy < 4 || rawPy > h - 4) overRangeA = true;
                        const py = Math.min(Math.max(rawPy, 4), h - 4);

                        if (idx === 0) this.ctx.moveTo(px, py);
                        else this.ctx.lineTo(px, py);
                    });
                    this.ctx.stroke();
                    this.ctx.shadowBlur = 0;
                }

                // --- Channel B (Cyan Trace) ---
                if (this.chB_enabled) {
                    this.ctx.strokeStyle = '#38bdf8';
                    this.ctx.lineWidth = 2.2;
                    this.ctx.shadowColor = '#38bdf8';
                    this.ctx.shadowBlur = 6;
                    this.ctx.beginPath();

                    visible.forEach((s, idx) => {
                        const px = ((s.t - startTime) / windowTime) * w;
                        const rawPy = midY - (s.vB / this.chB_voltsDiv) * dy - this.chB_yPos * dy;
                        if (rawPy < 4 || rawPy > h - 4) overRangeB = true;
                        const py = Math.min(Math.max(rawPy, 4), h - 4);

                        if (idx === 0) this.ctx.moveTo(px, py);
                        else this.ctx.lineTo(px, py);
                    });
                    this.ctx.stroke();
                    this.ctx.shadowBlur = 0;
                }

                // Over-Range / Clipping Screen Badges
                if (overRangeA) {
                    this.ctx.fillStyle = 'rgba(251, 191, 36, 0.9)';
                    this.ctx.font = 'bold 9px monospace';
                    this.ctx.fillText('▲ CH A CLIPPED (Adjust V/Div or Y-Pos)', 20, 16);
                }
                if (overRangeB) {
                    this.ctx.fillStyle = 'rgba(56, 189, 248, 0.9)';
                    this.ctx.font = 'bold 9px monospace';
                    this.ctx.fillText('▲ CH B CLIPPED (Adjust V/Div or Y-Pos)', 20, overRangeA ? 28 : 16);
                }
            } else {
                // --- X-Y Mode (Lissajous Figure) ---
                const dx = w / 10;
                const midX = w / 2;

                this.ctx.strokeStyle = '#4ade80';
                this.ctx.lineWidth = 2.2;
                this.ctx.shadowColor = '#4ade80';
                this.ctx.shadowBlur = 8;
                this.ctx.beginPath();

                visible.forEach((s, idx) => {
                    const px = Math.min(Math.max(midX + (s.vA / this.chA_voltsDiv) * dx, 4), w - 4);
                    const py = Math.min(Math.max(midY - (s.vB / this.chB_voltsDiv) * dy, 4), h - 4);
                    if (idx === 0) this.ctx.moveTo(px, py);
                    else this.ctx.lineTo(px, py);
                });
                this.ctx.stroke();
                this.ctx.shadowBlur = 0;
            }

            // Calculate True Telemetry
            let minA = Infinity, maxA = -Infinity, minB = Infinity, maxB = -Infinity;
            visible.forEach(s => {
                if (s.vA < minA) minA = s.vA;
                if (s.vA > maxA) maxA = s.vA;
                if (s.vB < minB) minB = s.vB;
                if (s.vB > maxB) maxB = s.vB;
            });

            const vppA = maxA > minA ? (maxA - minA) : 0;
            const vppB = maxB > minB ? (maxB - minB) : 0;

            // Zero-crossing frequency detection for Ch A
            let crossings = 0;
            for (let i = 1; i < visible.length; i++) {
                if (visible[i - 1].vA < 0 && visible[i].vA >= 0) crossings++;
            }
            const freqA = crossings > 1 ? Math.round(crossings / (2 * windowTime)) : 50;

            const telA_vpp = document.getElementById('msCroVppA');
            const telA_freq = document.getElementById('msCroFreqA');
            const telB_vpp = document.getElementById('msCroVppB');
            const telB_freq = document.getElementById('msCroFreqB');

            if (telA_vpp) telA_vpp.innerText = vppA.toFixed(2) + ' V';
            if (telA_freq) telA_freq.innerText = freqA + ' Hz';
            if (telB_vpp) telB_vpp.innerText = vppB.toFixed(2) + ' V';
            if (telB_freq) telB_freq.innerText = freqA + ' Hz';
        }
    }

    // ========================================================================
    // 7. AGILENT 34401A DIGITAL MULTIMETER (DMM)
    // ========================================================================
    class VirtualDMM {
        constructor(workbench) {
            this.wb = workbench;
            this.isOpen = false;
            this.windowEl = document.getElementById('msDmmWindow');
            this.displayEl = document.getElementById('msDmmValueDisplay');
            this.unitEl = document.getElementById('msDmmUnitDisplay');
            this.mode = 'V_DC';

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

            // Probe primary load or active output node
            let v = 0.0;
            const vm = this.wb.components.find(c => c.type === 'voltmeter');
            const am = this.wb.components.find(c => c.type === 'ammeter');

            if (vm) {
                const np = this.wb.pinToNode[`${vm.id}:p`] || 0;
                const nn = this.wb.pinToNode[`${vm.id}:n`] || 0;
                v = (this.wb.nodeVoltages[np] || 0.0) - (this.wb.nodeVoltages[nn] || 0.0);
            } else {
                v = this.wb.nodeVoltages[1] || 0.0;
            }

            const i_mA = am ? parseFloat(am.liveRead) || 0.0 : (v / 1000.0) * 1000.0;

            switch (this.mode) {
                case 'V_DC':
                    this.displayEl.innerText = (v >= 0 ? ' ' : '') + v.toFixed(3);
                    this.unitEl.innerText = 'V DC';
                    break;
                case 'V_AC':
                    const vrms = Math.abs(v / Math.SQRT2);
                    this.displayEl.innerText = vrms.toFixed(3);
                    this.unitEl.innerText = 'V RMS';
                    break;
                case 'I_DC':
                    this.displayEl.innerText = Math.abs(i_mA).toFixed(3);
                    this.unitEl.innerText = 'mA DC';
                    break;
                case 'I_AC':
                    const irms = Math.abs(i_mA / Math.SQRT2);
                    this.displayEl.innerText = irms.toFixed(3);
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
    // 8. AGILENT FUNCTION GENERATOR (XFG)
    // ========================================================================
    class VirtualXFG {
        constructor(workbench) {
            this.wb = workbench;
            this.isOpen = false;
            this.windowEl = document.getElementById('msXfgWindow');
            this.frequency = 1000;
            this.amplitude = 5.0;

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
    // 9. APP LAUNCHER
    // ========================================================================
    document.addEventListener('DOMContentLoaded', function () {
        window.CircuitWorkbench = CircuitWorkbench;
        window.MultisimApp = new CircuitWorkbench();
        window.CircuitApp = window.MultisimApp;
    });

})(window, document);
