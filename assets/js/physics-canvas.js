/**
 * Python4Physics - Central Force & Multi-Model Physics Simulation
 * Computational Physics Engine:
 * - Modes: Keplerian Gravity (1/r), Harmonic Oscillator (r²), and Electromagnetic Dipole
 * - Symplectic Velocity-Verlet Numerical Integration (Energy & Phase-Space Conserving)
 * - Interactive Field Mode Switching, Dynamic Perturbations & Real-Time Telemetry
 */
(function () {
  const canvas = document.getElementById('physicsHeroCanvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  let width, height;
  let cx, cy;
  let animationId = null;

  // Active Simulation Field Mode: 'kepler' | 'harmonic' | 'dipole'
  let currentMode = 'kepler';

  // Physical Parameters
  const GM = 14000;              // Gravitational parameter G * M of central attractor
  const softening = 28;          // Plummer softening length to prevent numerical singularity
  const dt = 0.45;               // Simulation time step
  const satelliteCount = 15;     // Number of orbiting test particles
  let satellites = [];
  let gravitationalWaves = [];

  // Interactive Mouse Gravitational Perturber
  let mouse = {
    x: null,
    y: null,
    mass: 3800,
    radius: 190,
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
      this.maxHistory = 65;
      this.reset(inclinationPhase);
    }

    reset(initialAngle) {
      const theta = initialAngle !== undefined ? initialAngle : Math.random() * Math.PI * 2;

      if (currentMode === 'kepler') {
        // Vis-viva equation: v^2 = GM * (2/r - 1/a)
        const r = (this.a * (1 - this.e * this.e)) / (1 + this.e * Math.cos(theta));
        this.x = cx + r * Math.cos(theta);
        this.y = cy + r * Math.sin(theta);
        const speed = Math.sqrt(Math.max(0.1, GM * (2 / r - 1 / this.a)));
        this.vx = -Math.sin(theta) * speed;
        this.vy = Math.cos(theta) * speed;
      } else if (currentMode === 'harmonic') {
        // Elliptical Lissajous motion around center: x = A cos(wt), y = B sin(wt)
        const A = this.a * 0.85;
        const B = this.a * (0.45 + (this.e * 0.8));
        this.x = cx + A * Math.cos(theta);
        this.y = cy + B * Math.sin(theta);
        const omega = 0.024;
        this.vx = -A * omega * Math.sin(theta);
        this.vy = B * omega * Math.cos(theta);
      } else if (currentMode === 'dipole') {
        // Start near positive pole
        const dOffset = Math.min(width, height) * 0.12;
        const angle = (Math.random() - 0.5) * Math.PI * 1.6;
        const r0 = 40 + Math.random() * 60;
        this.x = (cx - dOffset) + r0 * Math.cos(angle);
        this.y = cy + r0 * Math.sin(angle);
        const launchSpeed = 2.4 + Math.random() * 1.5;
        this.vx = Math.cos(angle) * launchSpeed;
        this.vy = Math.sin(angle) * launchSpeed;
      }

      this.radius = 2.0 + Math.random() * 1.5;
      this.history = [];
    }

    computeAcceleration(x, y) {
      let ax = 0;
      let ay = 0;

      if (currentMode === 'kepler') {
        // Inverse-Square Newtonian Gravitational Force: F = -GM/r^2 r̂
        const dx = cx - x;
        const dy = cy - y;
        const r2 = dx * dx + dy * dy + softening * softening;
        const r = Math.sqrt(r2);
        const forceMag = GM / (r2 * r);
        ax += dx * forceMag;
        ay += dy * forceMag;
      } else if (currentMode === 'harmonic') {
        // Isotropic Harmonic Oscillator Potential: F = -k r
        const kHarmonic = 0.00055;
        ax += (cx - x) * kHarmonic;
        ay += (cy - y) * kHarmonic;
      } else if (currentMode === 'dipole') {
        // Electric Dipole: +q and -q
        const dOffset = Math.min(width, height) * 0.12;
        const qPos = { x: cx - dOffset, y: cy };
        const qNeg = { x: cx + dOffset, y: cy };
        const kDipole = 9500;

        // Repulsive force from +q
        const dx1 = x - qPos.x;
        const dy1 = y - qPos.y;
        const r1_sq = dx1 * dx1 + dy1 * dy1 + 450;
        const r1 = Math.sqrt(r1_sq);
        ax += (dx1 / (r1_sq * r1)) * kDipole;
        ay += (dy1 / (r1_sq * r1)) * kDipole;

        // Attractive force towards -q
        const dx2 = qNeg.x - x;
        const dy2 = qNeg.y - y;
        const r2_sq = dx2 * dx2 + dy2 * dy2 + 450;
        const r2 = Math.sqrt(r2_sq);
        ax += (dx2 / (r2_sq * r2)) * kDipole;
        ay += (dy2 / (r2_sq * r2)) * kDipole;

        // Weak centering confinement
        ax += (cx - x) * 0.00008;
        ay += (cy - y) * 0.00008;
      }

      // Mouse perturbation
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
      const a1 = this.computeAcceleration(this.x, this.y);

      this.vx += 0.5 * a1.ax * dt;
      this.vy += 0.5 * a1.ay * dt;

      this.x += this.vx * dt;
      this.y += this.vy * dt;

      const a2 = this.computeAcceleration(this.x, this.y);

      this.vx += 0.5 * a2.ax * dt;
      this.vy += 0.5 * a2.ay * dt;

      // Trajectory trace history
      this.history.push({ x: this.x, y: this.y });
      if (this.history.length > this.maxHistory) {
        this.history.shift();
      }

      // Bounds checking
      const distFromCenter = Math.hypot(this.x - cx, this.y - cy);
      if (distFromCenter > Math.max(width, height) * 0.95 || (currentMode === 'kepler' && distFromCenter < 12)) {
        this.reset();
      }
    }

    draw(isDark) {
      if (this.history.length > 2) {
        ctx.beginPath();
        ctx.moveTo(this.history[0].x, this.history[0].y);
        for (let i = 1; i < this.history.length; i++) {
          ctx.lineTo(this.history[i].x, this.history[i].y);
        }
        ctx.strokeStyle = this.color;
        ctx.lineWidth = 1.2;
        ctx.globalAlpha = isDark ? 0.38 : 0.45;
        ctx.stroke();
      }

      ctx.beginPath();
      ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
      ctx.fillStyle = this.color;
      ctx.globalAlpha = 0.92;
      ctx.fill();

      // Vector velocity arrow
      const vMag = Math.hypot(this.vx, this.vy);
      if (vMag > 0.05) {
        const vScale = 6;
        ctx.beginPath();
        ctx.moveTo(this.x, this.y);
        ctx.lineTo(this.x + (this.vx / vMag) * vScale, this.y + (this.vy / vMag) * vScale);
        ctx.strokeStyle = this.color;
        ctx.globalAlpha = 0.55;
        ctx.lineWidth = 1;
        ctx.stroke();
      }

      ctx.globalAlpha = 1.0;
    }
  }

  function initSatellites() {
    satellites = [];
    const colors = [
      '#38bdf8', '#3b82f6', '#60a5fa', '#10b981', '#f59e0b', '#a855f7', '#38bdf8', '#3b82f6'
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

  // Draw Scientific Coordinate Axes & Mode-Specific Equipotentials
  function drawCoordinateGrid(isDark) {
    const gridColor = isDark ? 'rgba(56, 189, 248, 0.07)' : 'rgba(15, 23, 42, 0.06)';
    const textColor = isDark ? 'rgba(148, 163, 184, 0.45)' : 'rgba(71, 85, 105, 0.55)';
    const axisColor = isDark ? 'rgba(56, 189, 248, 0.16)' : 'rgba(15, 23, 42, 0.14)';

    // Center Coordinate Crosshair
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

    ctx.fillStyle = textColor;
    ctx.font = '10px "JetBrains Mono", monospace';

    if (currentMode === 'kepler') {
      // Concentric Keplerian Equipotential Rings
      const rings = [80, 160, 240, 320, 420];
      ctx.strokeStyle = gridColor;
      ctx.lineWidth = 0.8;
      rings.forEach((r) => {
        ctx.beginPath();
        ctx.arc(cx, cy, r, 0, Math.PI * 2);
        ctx.setLineDash([2, 5]);
        ctx.stroke();
        ctx.setLineDash([]);
        if (cx + r < width - 40) {
          ctx.fillText(`r=${r} AU`, cx + r + 4, cy - 4);
        }
      });

      // Central Mass M☉
      ctx.beginPath();
      ctx.arc(cx, cy, 6.5, 0, Math.PI * 2);
      ctx.fillStyle = isDark ? '#f59e0b' : '#d97706';
      ctx.fill();

      ctx.beginPath();
      ctx.arc(cx, cy, 14, 0, Math.PI * 2);
      ctx.strokeStyle = isDark ? 'rgba(245, 158, 11, 0.3)' : 'rgba(217, 119, 6, 0.25)';
      ctx.lineWidth = 1.5;
      ctx.stroke();

      ctx.fillStyle = isDark ? '#f59e0b' : '#d97706';
      ctx.fillText('M☉ (0,0)', cx + 10, cy + 18);

    } else if (currentMode === 'harmonic') {
      // Concentric Harmonic Well Energy Contours V = 1/2 k r^2
      const harmonicRings = [70, 130, 190, 260, 340];
      ctx.strokeStyle = isDark ? 'rgba(16, 185, 129, 0.12)' : 'rgba(5, 150, 105, 0.12)';
      ctx.lineWidth = 0.8;
      harmonicRings.forEach((r, idx) => {
        ctx.beginPath();
        ctx.arc(cx, cy, r, 0, Math.PI * 2);
        ctx.setLineDash([3, 4]);
        ctx.stroke();
        ctx.setLineDash([]);
        if (cx + r < width - 40) {
          ctx.fillText(`E_${idx} = ${(idx + 0.5).toFixed(1)}ℏω`, cx + r + 4, cy - 4);
        }
      });

      // Origin equilibrium point
      ctx.beginPath();
      ctx.arc(cx, cy, 5, 0, Math.PI * 2);
      ctx.fillStyle = '#10b981';
      ctx.fill();
      ctx.fillText('Equilibrium (r=0)', cx + 10, cy + 18);

    } else if (currentMode === 'dipole') {
      const dOffset = Math.min(width, height) * 0.12;
      const qPos = { x: cx - dOffset, y: cy };
      const qNeg = { x: cx + dOffset, y: cy };

      // Positive pole node (+q)
      ctx.beginPath();
      ctx.arc(qPos.x, qPos.y, 6.5, 0, Math.PI * 2);
      ctx.fillStyle = '#f43f5e';
      ctx.fill();
      ctx.fillStyle = '#f43f5e';
      ctx.fillText('+q', qPos.x - 8, qPos.y - 12);

      // Negative pole node (-q)
      ctx.beginPath();
      ctx.arc(qNeg.x, qNeg.y, 6.5, 0, Math.PI * 2);
      ctx.fillStyle = '#38bdf8';
      ctx.fill();
      ctx.fillStyle = '#38bdf8';
      ctx.fillText('-q', qNeg.x - 6, qNeg.y - 12);

      // Dipole field flux lines
      ctx.strokeStyle = isDark ? 'rgba(56, 189, 248, 0.1)' : 'rgba(30, 58, 138, 0.1)';
      ctx.lineWidth = 1;
      const arcSpans = [40, 80, 130, 190];
      arcSpans.forEach(h => {
        ctx.beginPath();
        ctx.moveTo(qPos.x, qPos.y);
        ctx.bezierCurveTo(qPos.x, qPos.y - h, qNeg.x, qNeg.y - h, qNeg.x, qNeg.y);
        ctx.stroke();

        ctx.beginPath();
        ctx.moveTo(qPos.x, qPos.y);
        ctx.bezierCurveTo(qPos.x, qPos.y + h, qNeg.x, qNeg.y + h, qNeg.x, qNeg.y);
        ctx.stroke();
      });
    }

    // Mouse Perturbation Horizon
    if (mouse.active && mouse.x !== null && mouse.y !== null) {
      ctx.beginPath();
      ctx.arc(mouse.x, mouse.y, mouse.radius, 0, Math.PI * 2);
      ctx.strokeStyle = isDark ? 'rgba(56, 189, 248, 0.2)' : 'rgba(2, 132, 199, 0.2)';
      ctx.lineWidth = 1;
      ctx.setLineDash([3, 4]);
      ctx.stroke();
      ctx.setLineDash([]);

      ctx.beginPath();
      ctx.arc(mouse.x, mouse.y, 4, 0, Math.PI * 2);
      ctx.fillStyle = isDark ? '#38bdf8' : '#0284c7';
      ctx.fill();

      ctx.fillStyle = isDark ? '#38bdf8' : '#0284c7';
      ctx.fillText('m_pert (Perturber)', mouse.x + 8, mouse.y - 8);
    }
  }

  // Draw Gravitational Shockwaves
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

    satellites.forEach(s => {
      s.update();
      s.draw(isDark);
    });

    animationId = requestAnimationFrame(animate);
  }

  // Global Function for User to Switch Physical Simulation Mode
  window.setPhysicsFieldMode = function (mode) {
    if (['kepler', 'harmonic', 'dipole'].includes(mode)) {
      currentMode = mode;
      initSatellites();

      // Update button visual states if present
      document.querySelectorAll('.potential-mode-btn').forEach(btn => {
        if (btn.getAttribute('data-mode') === mode) {
          btn.classList.add('active');
        } else {
          btn.classList.remove('active');
        }
      });

      // Update HUD telemetry model label
      const hudModel = document.getElementById('hudFieldModel');
      const hudEnergy = document.getElementById('hudFieldEnergy');
      if (hudModel) {
        if (mode === 'kepler') {
          hudModel.innerText = 'CENTRAL GRAVITY [1/r]';
          if (hudEnergy) hudEnergy.innerHTML = '$\\mathcal{H} = T + V < 0$';
        } else if (mode === 'harmonic') {
          hudModel.innerText = 'HARMONIC WELL [r²]';
          if (hudEnergy) hudEnergy.innerHTML = '$V = \\frac{1}{2}kr^2$';
        } else if (mode === 'dipole') {
          hudModel.innerText = 'ELECTRIC DIPOLE FIELD';
          if (hudEnergy) hudEnergy.innerHTML = '$V \\propto \\cos\\theta/r^2$';
        }
        if (window.renderMathInElement && hudEnergy) {
          renderMathInElement(hudEnergy, { delimiters: [{ left: '$', right: '$', display: false }] });
        }
      }
    }
  };

  // Event Listeners
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

  canvas.addEventListener('click', (e) => {
    const rect = canvas.getBoundingClientRect();
    const clickX = e.clientX - rect.left;
    const clickY = e.clientY - rect.top;
    gravitationalWaves.push({ x: clickX, y: clickY, radius: 10, alpha: 0.8 });

    satellites.forEach(s => {
      const dx = s.x - clickX;
      const dy = s.y - clickY;
      const dist = Math.hypot(dx, dy) || 1;
      const impulse = 35 / dist;
      s.vx += (dx / dist) * impulse;
      s.vy += (dy / dist) * impulse;
    });
  });

  window.addEventListener('p4p_theme_change', () => {
    // Redraws smoothly on next frame
  });

  // Initialize
  resize();
  initSatellites();
  animate();
})();
