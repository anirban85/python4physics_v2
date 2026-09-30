/**
 * Python4Physics - Central Force & Phase Space Orbital Mechanics Simulation
 * Computational Physics Model:
 * - Keplerian Central Attractor with N-Body Satellites
 * - Symplectic Velocity-Verlet Numerical Integration (Energy & Phase-Space Conserving)
 * - Interactive Gravitational Perturbations, Dynamic Orbits & Equipotential Contours
 * - Coordinate Axes & Telemetry HUD
 */
(function () {
  const canvas = document.getElementById('physicsHeroCanvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  let width, height;
  let cx, cy;
  let animationId = null;

  // Physical Parameters
  const GM = 14000;              // Gravitational parameter G * M of central attractor
  const softening = 28;          // Plummer softening length to prevent numerical singularity
  const dt = 0.45;               // Simulation time step
  const satelliteCount = 14;     // Number of orbiting particles
  let satellites = [];
  let gravitationalWaves = [];

  // Interactive Mouse Gravitational Perturber
  let mouse = {
    x: null,
    y: null,
    mass: 3500,
    radius: 180,
    active: false
  };

  function getTheme() {
    return document.documentElement.getAttribute('data-theme') || 'dark';
  }

  function resize() {
    const parent = canvas.parentElement;
    width = canvas.width = parent.offsetWidth;
    height = canvas.height = parent.offsetHeight;
    cx = width / 2;
    cy = height / 2;
  }

  // Satellite Particle Class
  class Satellite {
    constructor(semiMajorAxis, eccentricity, inclinationPhase, color) {
      this.a = semiMajorAxis;
      this.e = eccentricity;
      this.color = color;
      this.history = [];
      this.maxHistory = 70;
      this.reset(inclinationPhase);
    }

    reset(initialAngle) {
      const theta = initialAngle !== undefined ? initialAngle : Math.random() * Math.PI * 2;
      // Vis-viva equation: v^2 = GM * (2/r - 1/a)
      // Radius at periapsis / current theta
      const r = (this.a * (1 - this.e * this.e)) / (1 + this.e * Math.cos(theta));
      
      this.x = cx + r * Math.cos(theta);
      this.y = cy + r * Math.sin(theta);

      // Tangential velocity for elliptical orbit
      const speed = Math.sqrt(Math.max(0.1, GM * (2 / r - 1 / this.a)));
      const vx_dir = -Math.sin(theta);
      const vy_dir = Math.cos(theta);

      this.vx = vx_dir * speed;
      this.vy = vy_dir * speed;
      this.radius = 2.2 + Math.random() * 1.5;
      this.history = [];
    }

    computeAcceleration(x, y) {
      // Force from central mass
      const dx = cx - x;
      const dy = cy - y;
      const r2 = dx * dx + dy * dy + softening * softening;
      const r = Math.sqrt(r2);
      const forceMag = GM / (r2 * r);

      let ax = dx * forceMag;
      let ay = dy * forceMag;

      // Mouse gravitational perturbation
      if (mouse.active && mouse.x !== null && mouse.y !== null) {
        const mdx = mouse.x - x;
        const mdy = mouse.y - y;
        const mr2 = mdx * mdx + mdy * mdy + (softening * 1.5) * (softening * 1.5);
        const mr = Math.sqrt(mr2);
        if (mr < mouse.radius * 2) {
          const mForce = mouse.mass / (mr2 * mr);
          ax += mdx * mForce;
          ay += mdy * mForce;
        }
      }

      return { ax, ay };
    }

    update() {
      // Symplectic Velocity-Verlet Integration
      // Step 1: Compute a(t)
      const a1 = this.computeAcceleration(this.x, this.y);

      // Step 2: Half-step velocity update & full position update
      this.vx += 0.5 * a1.ax * dt;
      this.vy += 0.5 * a1.ay * dt;

      this.x += this.vx * dt;
      this.y += this.vy * dt;

      // Step 3: Compute a(t + dt) at new position
      const a2 = this.computeAcceleration(this.x, this.y);

      // Step 4: Complete velocity step
      this.vx += 0.5 * a2.ax * dt;
      this.vy += 0.5 * a2.ay * dt;

      // Record orbit trajectory history
      this.history.push({ x: this.x, y: this.y });
      if (this.history.length > this.maxHistory) {
        this.history.shift();
      }

      // If particle escaped too far, re-seed gracefully
      const distFromCenter = Math.hypot(this.x - cx, this.y - cy);
      if (distFromCenter > Math.max(width, height) * 0.95 || distFromCenter < 12) {
        this.reset();
      }
    }

    draw(isDark) {
      // Draw smooth fading orbital trail ribbon
      if (this.history.length > 2) {
        ctx.beginPath();
        ctx.moveTo(this.history[0].x, this.history[0].y);
        for (let i = 1; i < this.history.length; i++) {
          ctx.lineTo(this.history[i].x, this.history[i].y);
        }
        ctx.strokeStyle = this.color;
        ctx.lineWidth = 1.2;
        ctx.globalAlpha = isDark ? 0.35 : 0.45;
        ctx.stroke();
      }

      // Draw particle body
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
      ctx.fillStyle = this.color;
      ctx.globalAlpha = 0.9;
      ctx.fill();

      // Velocity direction indicator (subtle vector arrow)
      const vMag = Math.hypot(this.vx, this.vy);
      if (vMag > 0.05) {
        const vScale = 6;
        ctx.beginPath();
        ctx.moveTo(this.x, this.y);
        ctx.lineTo(this.x + (this.vx / vMag) * vScale, this.y + (this.vy / vMag) * vScale);
        ctx.strokeStyle = this.color;
        ctx.globalAlpha = 0.5;
        ctx.lineWidth = 1;
        ctx.stroke();
      }

      ctx.globalAlpha = 1.0;
    }
  }

  function initSatellites() {
    satellites = [];
    // Physics Spectral Color Palette
    const colors = [
      '#38bdf8', // Laser Cyan
      '#3b82f6', // Quantum Cobalt
      '#60a5fa', // Celestial Blue
      '#10b981', // 532nm Laser Green
      '#f59e0b', // Sodium D-line Amber
      '#a855f7', // Violet
      '#38bdf8',
      '#3b82f6'
    ];

    const baseRadius = Math.min(width, height) * 0.12;
    const stepRadius = Math.min(width, height) * 0.038;

    for (let i = 0; i < satelliteCount; i++) {
      const a = baseRadius + i * stepRadius;
      const e = 0.08 + (i % 5) * 0.06;
      const initialTheta = (i * (Math.PI * 2 / satelliteCount)) + (i * 0.2);
      const color = colors[i % colors.length];
      satellites.push(new Satellite(a, e, initialTheta, color));
    }
  }

  // Draw Scientific Coordinate Axes & Equipotential Contours
  function drawCoordinateGrid(isDark) {
    const gridColor = isDark ? 'rgba(56, 189, 248, 0.07)' : 'rgba(15, 23, 42, 0.06)';
    const textColor = isDark ? 'rgba(148, 163, 184, 0.45)' : 'rgba(71, 85, 105, 0.55)';
    const axisColor = isDark ? 'rgba(56, 189, 248, 0.16)' : 'rgba(15, 23, 42, 0.14)';

    // Coordinate Center Axes (Passing through Central Mass)
    ctx.beginPath();
    ctx.moveTo(0, cy);
    ctx.lineTo(width, cy);
    ctx.moveTo(cx, 0);
    ctx.lineTo(cx, height);
    ctx.strokeStyle = axisColor;
    ctx.lineWidth = 1;
    ctx.setLineDash([4, 4]);
    ctx.stroke();
    ctx.setLineDash([]);

    // Equipotential Radial Contours: r = 100, 200, 300, 400
    const rings = [80, 160, 240, 320, 420];
    ctx.strokeStyle = gridColor;
    ctx.lineWidth = 0.8;
    ctx.fillStyle = textColor;
    ctx.font = '10px "JetBrains Mono", monospace';

    rings.forEach((r, idx) => {
      ctx.beginPath();
      ctx.arc(cx, cy, r, 0, Math.PI * 2);
      ctx.setLineDash([2, 5]);
      ctx.stroke();
      ctx.setLineDash([]);

      // Label along positive X axis
      if (cx + r < width - 40) {
        ctx.fillText(`r=${r} AU`, cx + r + 4, cy - 4);
      }
    });

    // Central Mass Body
    ctx.beginPath();
    ctx.arc(cx, cy, 6.5, 0, Math.PI * 2);
    ctx.fillStyle = isDark ? '#f59e0b' : '#d97706';
    ctx.fill();

    // Central Mass Halo
    ctx.beginPath();
    ctx.arc(cx, cy, 14, 0, Math.PI * 2);
    ctx.strokeStyle = isDark ? 'rgba(245, 158, 11, 0.3)' : 'rgba(217, 119, 6, 0.25)';
    ctx.lineWidth = 1.5;
    ctx.stroke();

    // Center Origin Tag
    ctx.fillStyle = isDark ? '#f59e0b' : '#d97706';
    ctx.fillText('M☉ (0,0)', cx + 10, cy + 18);

    // Mouse Perturbation Horizon
    if (mouse.active && mouse.x !== null && mouse.y !== null) {
      ctx.beginPath();
      ctx.arc(mouse.x, mouse.y, mouse.radius, 0, Math.PI * 2);
      ctx.strokeStyle = isDark ? 'rgba(56, 189, 248, 0.2)' : 'rgba(2, 132, 199, 0.2)';
      ctx.lineWidth = 1;
      ctx.setLineDash([3, 4]);
      ctx.stroke();
      ctx.setLineDash([]);

      // Mouse Core
      ctx.beginPath();
      ctx.arc(mouse.x, mouse.y, 4, 0, Math.PI * 2);
      ctx.fillStyle = isDark ? '#38bdf8' : '#0284c7';
      ctx.fill();

      ctx.fillStyle = isDark ? '#38bdf8' : '#0284c7';
      ctx.fillText('m_pert (Perturber)', mouse.x + 8, mouse.y - 8);
    }
  }

  // Draw Gravitational Shockwaves (Triggered on Click)
  function drawGravitationalWaves(isDark) {
    for (let i = gravitationalWaves.length - 1; i >= 0; i--) {
      const wave = gravitationalWaves[i];
      wave.radius += 5;
      wave.alpha *= 0.96;

      ctx.beginPath();
      ctx.arc(wave.x, wave.y, wave.radius, 0, Math.PI * 2);
      ctx.strokeStyle = isDark ? `rgba(56, 189, 248, ${wave.alpha})` : `rgba(37, 99, 235, ${wave.alpha})`;
      ctx.lineWidth = 1.5;
      ctx.stroke();

      if (wave.alpha < 0.02 || wave.radius > Math.max(width, height)) {
        gravitationalWaves.splice(i, 1);
      }
    }
  }

  function animate() {
    ctx.clearRect(0, 0, width, height);
    const isDark = getTheme() === 'dark';

    drawCoordinateGrid(isDark);
    drawGravitationalWaves(isDark);

    // Update and draw satellites
    satellites.forEach(s => {
      s.update();
      s.draw(isDark);
    });

    animationId = requestAnimationFrame(animate);
  }

  // Mouse Interaction Listeners
  window.addEventListener('resize', () => {
    resize();
    initSatellites();
  });

  canvas.addEventListener('mousemove', (e) => {
    const rect = canvas.getBoundingClientRect();
    mouse.x = e.clientX - rect.left;
    mouse.y = e.clientY - rect.top;
    mouse.active = true;
  });

  canvas.addEventListener('mouseleave', () => {
    mouse.active = false;
    mouse.x = null;
    mouse.y = null;
  });

  // Pulse gravitational wave ripple on click
  canvas.addEventListener('click', (e) => {
    const rect = canvas.getBoundingClientRect();
    const clickX = e.clientX - rect.left;
    const clickY = e.clientY - rect.top;
    gravitationalWaves.push({ x: clickX, y: clickY, radius: 10, alpha: 0.8 });

    // Perturb satellites radially
    satellites.forEach(s => {
      const dx = s.x - clickX;
      const dy = s.y - clickY;
      const dist = Math.hypot(dx, dy) || 1;
      const impulse = 35 / dist;
      s.vx += (dx / dist) * impulse;
      s.vy += (dy / dist) * impulse;
    });
  });

  // Listen to Theme Change event to refresh visuals
  window.addEventListener('p4p_theme_change', () => {
    // Canvas repaints automatically next frame
  });

  // Initialize
  resize();
  initSatellites();
  animate();
})();
