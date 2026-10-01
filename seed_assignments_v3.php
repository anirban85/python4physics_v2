<?php
/**
 * Python4Physics - Complete Seed for 12 University Physics Assignments
 * Units 1-6 Classical Mechanics, Quantum, Electrodynamics & Thermo
 */
require_once __DIR__ . "/db.php";

if (!isset($conn) || $conn === null) {
    echo "Database connection not available.";
    exit(1);
}

$assignments_data = array (
  0 => 
  array (
    'id' => 1,
    'category' => 'mechanics',
    'title' => 'Projectile Motion with Quadratic Air Drag',
    'subtitle' => 'Model aerodynamic deceleration using coupled ODEs',
    'description' => 'Model the trajectory of a spherical projectile under the influence of gravity and non-linear aerodynamic air resistance.',
    'theory_equations' => '$$\\frac{dx}{dt} = v_x, \\quad \\frac{dv_x}{dt} = -\\frac{c}{m} v_x \\sqrt{v_x^2 + v_y^2}$$\\n$$\\frac{dy}{dt} = v_y, \\quad \\frac{dv_y}{dt} = -g -\\frac{c}{m} v_y \\sqrt{v_x^2 + v_y^2}$$',
    'parameters' => 'Mass $m = 0.145\\text{ kg}$, initial speed $v_0 = 45\\text{ m/s}$, launch angle $\\theta = 45^\\circ$, drag constant $c = 0.0015\\text{ kg/m}$, $g = 9.81\\text{ m/s}^2$.',
    'starter_code' => 'import numpy as np
import matplotlib.pyplot as plt

m = 0.145
g = 9.81
c = 0.0015
v0 = 45.0
theta = np.radians(45)

dt = 0.001
x, y = [0.0], [0.0]
vx, vy = [v0 * np.cos(theta)], [v0 * np.sin(theta)]

while y[-1] >= 0:
    v = np.sqrt(vx[-1]**2 + vy[-1]**2)
    ax = -(c/m) * v * vx[-1]
    ay = -g - (c/m) * v * vy[-1]
    vx.append(vx[-1] + ax * dt)
    vy.append(vy[-1] + ay * dt)
    x.append(x[-1] + vx[-1] * dt)
    y.append(y[-1] + vy[-1] * dt)

R_vac = (v0**2 * np.sin(2*theta)) / g
print(f"Vacuum Range: {R_vac:.2f} m")
print(f"Realistic Range (Drag): {x[-1]:.2f} m")

plt.figure(figsize=(7, 3.8))
plt.plot(x, y, label=\'With Quadratic Drag\', color=\'#06b6d4\', lw=2)
plt.title(\'Projectile Motion with Air Drag\')
plt.xlabel(\'Distance (m)\')
plt.ylabel(\'Height (m)\')
plt.grid(True, alpha=0.3)
plt.legend()
plt.show()',
    'solution_code' => '',
    'created_at' => '2026-09-22 16:28:18',
    'updated_at' => '2026-09-22 16:28:18',
  ),
  1 => 
  array (
    'id' => 2,
    'category' => 'electrodynamics',
    'title' => 'Electric Field & Equipotential Contours of an Electric Quadrupole',
    'subtitle' => '2D electrostatic field line integration and potential mesh computation',
    'description' => 'Calculate and visualize the 2D electrostatic potential field $\\Phi(x, y)$ and field vector gradient $\\vec{E} = -\\nabla \\Phi$ for an arrangement of four point charges in a planar quadrupole configuration.',
    'theory_equations' => '$$\\Phi(\\vec{r}) = \\frac{1}{4\\pi \\varepsilon_0} \\sum_{i=1}^{N} \\frac{q_i}{|\\vec{r} - \\vec{r}_i|}, \\quad \\vec{E} = -\\nabla \\Phi = -\\left( \\frac{\\partial \\Phi}{\\partial x} \\hat{i} + \\frac{\\partial \\Phi}{\\partial y} \\hat{j} \\right)$$',
    'parameters' => 'Charges $q = \\pm 1\\text{ nC}$ placed at $(1, 1), (-1, 1), (-1, -1), (1, -1)$. Mesh resolution $200 \\times 200$.',
    'starter_code' => '# Python4Physics Assignment: Electric Quadrupole Potential & Field Contours
import numpy as np
import matplotlib.pyplot as plt

# ==========================================
# PHYSICAL PARAMETERS (Adjust with sliders)
# ==========================================
d = 1.00              # Charge coordinate offset from origin (m)
q = 1.00              # Charge magnitude (arbitrary units / nC)

x = np.linspace(-3, 3, 200)
y = np.linspace(-3, 3, 200)
X, Y = np.meshgrid(x, y)

charges = [
    {\'q\':  q, \'pos\': ( d,  d)},
    {\'q\': -q, \'pos\': (-d,  d)},
    {\'q\':  q, \'pos\': (-d, -d)},
    {\'q\': -q, \'pos\': ( d, -d)},
]

V = np.zeros_like(X)
for c in charges:
    r = np.sqrt((X - c[\'pos\'][0])**2 + (Y - c[\'pos\'][1])**2)
    r = np.maximum(r, 0.15)
    V += c[\'q\'] / r

Ey, Ex = np.gradient(-V, y, x)
E_mag = np.sqrt(Ex**2 + Ey**2)

print(f"--- Electrostatic Quadrupole Telemetry ---")
print(f"Separation d: {d:.2f} m | Charge q: {q:.2f}")
print(f"Quadrupole Moment Q_xy = 4*q*d^2: {4 * q * d**2:.2f}")
print(f"Center Potential V(0,0): {V[100, 100]:.4f} (Strict Zero by Symmetry)")

plt.figure(figsize=(7, 6))
levels = np.linspace(-2.5, 2.5, 25)
cp = plt.contourf(X, Y, V, levels=levels, cmap="RdBu_r", extend="both")
plt.colorbar(cp, label="Electrostatic Potential V")
plt.streamplot(x, y, Ex, Ey, color="#38bdf8", density=1.1, linewidth=0.8, arrowsize=0.8)

for c in charges:
    color = "#f43f5e" if c[\'q\'] > 0 else "#0284c7"
    plt.scatter(*c[\'pos\'], color=color, s=120, zorder=5)

plt.title(f"Electric Quadrupole Field & Equipotentials (d={d:.2f})")
plt.xlabel("x (m)")
plt.ylabel("y (m)")
plt.axis("equal")
plt.tight_layout()
plt.show()',
    'solution_code' => '',
    'created_at' => '2026-09-22 16:28:18',
    'updated_at' => '2026-10-01 13:54:06',
  ),
  2 => 
  array (
    'id' => 3,
    'category' => 'quantum',
    'title' => 'Finite Square Well: Bound State Energies via Transcendental Bisection',
    'subtitle' => 'Numerical root finding for symmetric and antisymmetric quantum wavefunctions',
    'description' => 'Determine the bound-state energy eigenvalues for a particle of mass $m$ trapped inside a finite 1D square potential well of width $2a$ and depth $V_0$.',
    'theory_equations' => '$$\\xi \\tan \\xi = \\sqrt{\\xi_0^2 - \\xi^2} \\quad \\text{(Even Parity)}, \\quad -\\xi \\cot \\xi = \\sqrt{\\xi_0^2 - \\xi^2} \\quad \\text{(Odd Parity)}$$\\n$$\\text{where } \\xi = \\frac{a}{\\hbar}\\sqrt{2m(E + V_0)}, \\quad \\xi_0 = \\frac{a}{\\hbar}\\sqrt{2m V_0}$$',
    'parameters' => 'Well half-width $a = 1.0\\text{ nm}$, Well depth $V_0 = 10.0\\text{ eV}$, electron mass $m_e$.',
    'starter_code' => '# Python4Physics Assignment: Finite Square Well Bound States
import numpy as np
from scipy.optimize import brentq
import matplotlib.pyplot as plt

# ==========================================
# PHYSICAL PARAMETERS (Adjust with sliders)
# ==========================================
V0 = 25.0             # Potential well depth (eV)
a_nm = 1.20           # Well half-width (nm)

a = a_nm * 1e-9       # Well half-width (m)
hbar = 1.0545718e-34  # Reduced Planck constant (J*s)
m = 9.10938356e-31    # Electron mass (kg)
e = 1.60217663e-19    # Elementary charge (C)

# Dimensionless well parameter xi0 = (a/hbar) * sqrt(2 * m * V0 * e)
xi0 = (a / hbar) * np.sqrt(2.0 * m * V0 * e)
print(f"--- Quantum Finite Square Well Telemetry ---")
print(f"Well Depth V0: {V0:.2f} eV | Half-Width a: {a_nm:.2f} nm")
print(f"Dimensionless Strength Parameter xi_0: {xi0:.3f}")

def even_eq(xi):
    return xi * np.tan(xi) - np.sqrt(np.maximum(0, xi0**2 - xi**2))

def odd_eq(xi):
    return -xi / np.tan(xi) - np.sqrt(np.maximum(0, xi0**2 - xi**2))

# Find roots for even and odd parity states
even_roots = []
odd_roots = []
xi_vals = np.linspace(0.01, min(xi0 - 0.005, 12 * np.pi), 1200)

for i in range(len(xi_vals) - 1):
    x1, x2 = xi_vals[i], xi_vals[i+1]
    if np.cos(x1) * np.cos(x2) > 0 and even_eq(x1) * even_eq(x2) < 0:
        try:
            r = brentq(even_eq, x1, x2)
            even_roots.append(r)
        except:
            pass
    if np.sin(x1) * np.sin(x2) > 0 and odd_eq(x1) * odd_eq(x2) < 0:
        try:
            r = brentq(odd_eq, x1, x2)
            odd_roots.append(r)
        except:
            pass

all_states = []
for r in even_roots:
    E = (r / xi0)**2 * V0 - V0
    all_states.append((\'Even\', r, E))
for r in odd_roots:
    E = (r / xi0)**2 * V0 - V0
    all_states.append((\'Odd\', r, E))

all_states.sort(key=lambda s: s[2])
print(f"Total Bound States: {len(all_states)}")
for idx, (parity, r, E) in enumerate(all_states, 1):
    print(f"  Level n={idx} [{parity}]: E = {E:.3f} eV (Binding Energy = {-E:.3f} eV)")

# Plot: 1. Transcendental Intersections | 2. Finite Well with Bound State Energy Levels
fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(10, 4.5))

# 1. Transcendental Curves
xi_plot = np.linspace(0.01, xi0 * 0.999, 400)
circle_rhs = np.sqrt(xi0**2 - xi_plot**2)
ax1.plot(xi_plot, circle_rhs, color="#f59e0b", lw=2, label=r"$\\sqrt{\\xi_0^2 - \\xi^2}$")
tan_lhs = xi_plot * np.tan(xi_plot)
tan_lhs[tan_lhs < 0] = np.nan
ax1.plot(xi_plot, tan_lhs, color="#0284c7", lw=1.5, label=r"$\\xi \\tan\\xi$ (Even)")
cot_lhs = -xi_plot / np.tan(xi_plot)
cot_lhs[cot_lhs < 0] = np.nan
ax1.plot(xi_plot, cot_lhs, color="#ec4899", lw=1.5, label=r"$-\\xi \\cot\\xi$ (Odd)")
for parity, r, E in all_states:
    color = "#0284c7" if parity == "Even" else "#ec4899"
    ax1.scatter([r], [np.sqrt(xi0**2 - r**2)], color=color, s=60, zorder=5)

ax1.set_title("Transcendental Eigenvalue Intersections")
ax1.set_xlabel(r"Dimensionless Parameter $\\xi = k a$")
ax1.set_ylabel(r"Transcendental Curves")
ax1.set_ylim(0, xi0 * 1.25)
ax1.grid(True, alpha=0.3)
ax1.legend(loc="upper right", fontsize=8)

# 2. Potential Well & Bound Levels
x_nm = np.linspace(-2.5 * a_nm, 2.5 * a_nm, 300)
V_well = np.where(np.abs(x_nm) <= a_nm, -V0, 0.0)
ax2.plot(x_nm, V_well, color="#94a3b8", lw=2, label="Potential Well $V(x)$")
for idx, (parity, r, E) in enumerate(all_states, 1):
    color = "#10b981" if parity == "Even" else "#38bdf8"
    ax2.hlines(E, -a_nm, a_nm, colors=color, lw=2, label=f"n={idx} ({E:.2f} eV)" if idx <= 3 else "")

ax2.set_title("Finite Well Potential & Bound State Energies")
ax2.set_xlabel("Coordinate x (nm)")
ax2.set_ylabel("Energy (eV)")
ax2.set_ylim(-V0 * 1.15, 2.0)
ax2.grid(True, alpha=0.3)
if len(all_states) > 0:
    ax2.legend(loc="lower right", fontsize=8)

plt.tight_layout()
plt.show()',
    'solution_code' => '',
    'created_at' => '2026-09-22 16:28:18',
    'updated_at' => '2026-10-01 13:55:06',
  ),
  3 => 
  array (
    'id' => 4,
    'category' => 'thermo',
    'title' => '2D Ising Model Monte Carlo via Metropolis Algorithm',
    'subtitle' => 'Spontaneous symmetry breaking, magnetization, and phase transition simulation',
    'description' => 'Simulate the magnetic phase transition in a 2D ferromagnetic square lattice using the Metropolis Monte Carlo algorithm.',
    'theory_equations' => '$$\\mathcal{H} = -J \\sum_{\\langle i, j \\rangle} s_i s_j, \\quad \\Delta E = 2 J s_i \\sum_{\\text{neighbors}} s_j$$\\n$$P(\\text{flip}) = \\min\\left(1, e^{-\\Delta E / k_B T}\\right)$$',
    'parameters' => 'Lattice size $L = 32 \\times 32$, Coupling $J = 1.0$, $T_c \\approx 2.269$.',
    'starter_code' => '# Python4Physics Assignment: 2D Ising Model Monte Carlo Simulation
import numpy as np
import matplotlib.pyplot as plt

# ==========================================
# PHYSICAL PARAMETERS (Adjust with sliders)
# ==========================================
T = 2.27              # Temperature in units of J / k_B (Critical T_c approx 2.269)
L = 24                # Lattice linear dimension (L x L spins)
mc_sweeps = 60        # Number of Monte Carlo sweeps

# Initialize 2D Spin Lattice (+1: Up, -1: Down)
spins = np.random.choice([-1, 1], size=(L, L))

def sweep(lattice, temperature):
    for _ in range(L * L):
        i = np.random.randint(0, L)
        j = np.random.randint(0, L)
        s = lattice[i, j]
        # Periodic boundary condition nearest-neighbors
        nb = lattice[(i+1)%L, j] + lattice[(i-1)%L, j] + lattice[i, (j+1)%L] + lattice[i, (j-1)%L]
        # Delta E = 2 * J * s * sum(nb) with J = 1.0
        dE = 2 * s * nb
        if dE <= 0 or np.random.rand() < np.exp(-dE / temperature):
            lattice[i, j] = -s
    return lattice

# Thermalization sweeps
for _ in range(mc_sweeps):
    spins = sweep(spins, T)

# Compute Magnetization per spin M = (1/N) * sum(s)
mag = np.mean(spins)
abs_mag = np.abs(mag)
T_c = 2.0 / np.log(1.0 + np.sqrt(2.0)) # Exact Onsager T_c approx 2.269185

print(f"--- 2D Ising Model Telemetry ---")
print(f"Lattice Size: {L}x{L} ({L*L} spins) | Temperature T: {T:.2f} J/kB")
print(f"Onsager Critical Temperature T_c: {T_c:.4f} J/kB")
print(f"Net Magnetization per spin M: {mag:.4f} (|M| = {abs_mag:.4f})")
if T < T_c:
    exact_M = (1.0 - np.sinh(2.0 / T)**(-4))**(1.0 / 8.0) if np.sinh(2.0/T) > 1.0 else 0.0
    print(f"Phase Regime: FERROMAGNETIC ORDER (Spontaneous symmetry breaking)")
    print(f"Onsager Exact Analytical Magnetization M_exact: {exact_M:.4f}")
else:
    print(f"Phase Regime: PARAMAGNETIC DISORDER (Fluctuating domains, M -> 0)")

# Visualization: Spin Configuration Matrix
plt.figure(figsize=(6, 5.5))
plt.imshow(spins, cmap=\'coolwarm\', interpolation=\'nearest\', vmin=-1, vmax=1)
plt.title(f"2D Ising Spin Matrix (T = {T:.2f}, L = {L})")
cbar = plt.colorbar(ticks=[-1, 1])
cbar.ax.set_yticklabels([\'Spin Down (-1)\', \'Spin Up (+1)\'])
plt.axis(\'off\')
plt.tight_layout()
plt.show()',
    'solution_code' => '',
    'created_at' => '2026-09-22 16:28:18',
    'updated_at' => '2026-10-01 13:55:06',
  ),
  4 => 
  array (
    'id' => 5,
    'category' => 'central-force',
    'title' => 'Central Force: Inverse-Square Orbits & Relativistic Perihelion Precession',
    'subtitle' => 'Solve orbital differential equations in central gravitational fields and model relativistic perturbations',
    'description' => 'Formulate the equations of motion for a celestial body orbiting under Newton\'s inverse-square gravitational force. Compute orbital eccentricity, verify Kepler\'s 2nd Law (areal velocity conservation), and introduce a perturbative potential V_{pert}(r) = -\\alpha / r^2 to model relativistic perihelion precession.',
    'theory_equations' => '$$\\frac{d^2 u}{d\\theta^2} + u = \\frac{GM m^2}{L^2} + \\frac{\\alpha m}{L^2} u^2 \\quad \\left(u = \\frac{1}{r}\\right)$$
$$\\vec{F}(\\vec{r}) = -\\frac{GMm}{r^3} \\vec{r} - \\frac{\\alpha}{r^4}\\hat{r}, \\quad \\frac{dA}{dt} = \\frac{1}{2} r^2 \\dot{\\theta} = \\frac{L}{2m} = \\text{constant}$$
$$r(\\theta) = \\frac{p}{1 + e \\cos(\\theta - \\theta_0)}, \\quad e = \\sqrt{1 + \\frac{2 E L^2}{m (GMm)^2}}, \\quad \\Delta\\theta_{\\text{prec}} \\approx \\frac{2\\pi \\alpha m^2}{L^2}$$',
    'parameters' => 'Gravitational parameter $GM = 1.0$, Mass $m = 1.0$, Semi-major axis $a = 1.5$, Eccentricity $e = 0.60$, Relativistic perturbation $\\alpha = 0.015$.',
    'starter_code' => '# Python4Physics Assignment: Central Force & Relativistic Orbit Precession
import numpy as np
import matplotlib.pyplot as plt

# ==========================================
# PHYSICAL PARAMETERS (Adjust with sliders)
# ==========================================
GM = 1.0              # Gravitational parameter (G * M_central)
m = 1.0               # Mass of orbiting planet
a = 1.50              # Semi-major axis (AU)
e = 0.60              # Orbital eccentricity (0 <= e < 1)
alpha = 0.015         # Perturbation strength (Perihelion advance)
num_orbits = 4        # Number of completed orbital periods

# Initial orbital state at perihelion (r_min = a * (1 - e))
r0 = a * (1.0 - e)
# Vis-viva equation for initial velocity at perihelion:
v0 = np.sqrt(GM * (2.0 / r0 - 1.0 / a))
L = m * r0 * v0       # Angular momentum (conserved)
T_period = 2.0 * np.pi * np.sqrt(a**3 / GM)
print(f"--- Orbital Telemetry ---")
print(f"Semi-Major Axis a: {a:.2f} AU | Eccentricity e: {e:.2f}")
print(f"Initial Perihelion r0: {r0:.3f} AU | Speed v0: {v0:.3f}")
print(f"Orbital Period T: {T_period:.2f} | Angular Momentum L: {L:.3f}")

# Time integration setup using Symplectic Velocity-Verlet
dt = 0.002
t_max = num_orbits * T_period
t_steps = int(t_max / dt)

t = np.zeros(t_steps)
x = np.zeros(t_steps)
y = np.zeros(t_steps)
vx = np.zeros(t_steps)
vy = np.zeros(t_steps)

x[0], y[0] = r0, 0.0
vx[0], vy[0] = 0.0, v0

def get_accel(px, py):
    r = np.sqrt(px**2 + py**2)
    # Gravitational inverse-square + General Relativistic perturbation
    ar = -(GM / r**2) - (alpha / r**3)
    return ar * (px / r), ar * (py / r)

ax0, ay0 = get_accel(x[0], y[0])

# Velocity-Verlet Integration Loop
for i in range(t_steps - 1):
    t[i+1] = t[i] + dt
    # Half-step velocity
    vx_half = vx[i] + 0.5 * ax0 * dt
    vy_half = vy[i] + 0.5 * ay0 * dt
    # Full-step position
    x[i+1] = x[i] + vx_half * dt
    y[i+1] = y[i] + vy_half * dt
    # New acceleration
    ax1, ay1 = get_accel(x[i+1], y[i+1])
    # Full-step velocity
    vx[i+1] = vx_half + 0.5 * ax1 * dt
    vy[i+1] = vy_half + 0.5 * ay1 * dt
    ax0, ay0 = ax1, ay1

# Visualizations
fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(10, 4.5))

# 1. Orbital Trajectory in xy-plane
ax1.plot(x, y, color="#0284c7", lw=1.2, label=f"Precessing Orbit (alpha={alpha})")
ax1.scatter([0], [0], color="#f59e0b", s=150, zorder=5, label="Central Mass M")
ax1.plot([0, x[0]], [0, y[0]], "--", color="#10b981", alpha=0.6, label="Periapsis Line")
ax1.set_title("Central Force Orbit & Precession")
ax1.set_xlabel("x (AU)")
ax1.set_ylabel("y (AU)")
ax1.axis("equal")
ax1.grid(True, alpha=0.3)
ax1.legend(loc="upper right", fontsize=8)

# 2. Angular Momentum & Radial Distance vs Time
r_hist = np.sqrt(x**2 + y**2)
L_hist = m * (x * vy - y * vx)
L_err = np.abs(L_hist - L) / L * 100

ax2.plot(t, r_hist, color="#38bdf8", lw=1.2, label="Radius r(t)")
ax2.axhline(a * (1 - e), color="#f43f5e", ls=":", label="r_min (perihelion)")
ax2.axhline(a * (1 + e), color="#10b981", ls=":", label="r_max (aphelion)")
ax2.set_title(f"Radial Distance | Max L Err: {np.max(L_err):.4e}%")
ax2.set_xlabel("Time t")
ax2.set_ylabel("Radial Coordinate r")
ax2.grid(True, alpha=0.3)
ax2.legend(loc="upper right", fontsize=8)

plt.tight_layout()
plt.show()',
    'solution_code' => '',
    'created_at' => '2026-09-30 22:50:50',
    'updated_at' => '2026-09-30 22:50:50',
  ),
  5 => 
  array (
    'id' => 6,
    'category' => 'central-force',
    'title' => 'Gauss\'s Law for Gravitation: Spherical Shell, Solid Sphere & Flat Sheet',
    'subtitle' => 'Compute integral gravitational potentials $\\Phi(r)$, field intensities $g(r)$, and interior tunneling dynamics',
    'description' => 'Apply the integral form of Gauss\'s Law for Gravitation $\\oint \\vec{g} \\cdot d\\vec{A} = -4\\pi G M_{\\text{enc}}$ to compute and visualize gravitational potential $\\Phi(r)$ and field intensity $g(r)$ for a uniform spherical shell, a homogeneous solid planet, and an infinite flat mass sheet. Simulate the interior diametrical tunnel harmonic motion.',
    'theory_equations' => '$$\\oint_S \\vec{g} \\cdot d\\vec{A} = -4\\pi G M_{\\text{enc}} \\iff \\nabla \\cdot \\vec{g} = -4\\pi G \\rho, \\quad \\vec{g} = -\\nabla \\Phi$$
$$\\text{Solid Sphere: } g(r) = \\begin{cases} -\\frac{GM}{R^3} r & r \\le R \\\\ -\\frac{GM}{r^2} & r > R \\end{cases}, \\quad \\Phi(r) = \\begin{cases} -\\frac{GM}{2R^3}(3R^2 - r^2) & r \\le R \\\\ -\\frac{GM}{r} & r > R \\end{cases}$$
$$\\text{Spherical Shell: } g(r) = \\begin{cases} 0 & r < R \\\\ -\\frac{GM}{r^2} & r \\ge R \\end{cases}, \\quad \\text{Flat Sheet: } g(z) = -2\\pi G \\sigma \\, \\text{sgn}(z), \\quad \\Phi(z) = 2\\pi G \\sigma |z|$$',
    'parameters' => 'Radius $R = 5.0\\text{ units}$, Total Mass $M = 10.0\\text{ units}$, Surface Density $\\sigma = 2.0\\text{ units}$, Gravitational constant $G = 1.0$.',
    'starter_code' => '# Python4Physics Assignment: Gravitational Gauss Law & Potential Theory
import numpy as np
import matplotlib.pyplot as plt

# ==========================================
# PHYSICAL PARAMETERS (Adjust with sliders)
# ==========================================
G = 1.0               # Gravitational constant
R = 5.0               # Planetary radius
M = 10.0              # Total sphere mass
sigma = 2.0           # Flat sheet surface mass density

r = np.linspace(0.01, 3 * R, 600)
z = np.linspace(-3 * R, 3 * R, 600)

# 1. Solid Sphere Profiles
g_solid = np.where(r <= R, -(G * M / R**3) * r, -(G * M / r**2))
Phi_solid = np.where(r <= R, -(G * M / (2 * R**3)) * (3 * R**2 - r**2), -(G * M / r))

# 2. Thin Spherical Shell Profiles
g_shell = np.where(r < R, 0.0, -(G * M / r**2))
Phi_shell = np.where(r < R, -(G * M / R), -(G * M / r))

# 3. Infinite Flat Sheet
g_sheet = -2.0 * np.pi * G * sigma * np.sign(z)
Phi_sheet = 2.0 * np.pi * G * sigma * np.abs(z)

# 4. Interior Diametrical Tunnel Simple Harmonic Motion
# Inside earth: d^2r/dt^2 = -(GM/R^3) r = -omega^2 r
omega = np.sqrt(G * M / R**3)
T_tunnel = 2 * np.pi / omega
print(f"--- Analytical Results ---")
print(f"Surface Gravity g(R): {-G * M / R**2:.3f}")
print(f"Center Potential Phi(0): {-1.5 * G * M / R:.3f}")
print(f"Tunnel Oscillation Period T: {T_tunnel:.3f} s")

# Plotting Profiles
fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(10, 4.5))

# Subplot 1: Field Intensity g(r)
ax1.plot(r, g_solid, color="#0284c7", lw=2, label="Solid Sphere g(r)")
ax1.plot(r, g_shell, color="#f59e0b", lw=2, ls="--", label="Spherical Shell g(r)")
ax1.axvline(R, color="#94a3b8", ls=":", alpha=0.7, label=f"Boundary R={R}")
ax1.set_title("Gravitational Field Intensity g(r)")
ax1.set_xlabel("Radial Distance r")
ax1.set_ylabel("Field g(r)")
ax1.grid(True, alpha=0.3)
ax1.legend()

# Subplot 2: Potential Phi(r)
ax2.plot(r, Phi_solid, color="#0284c7", lw=2, label="Solid Sphere Phi(r)")
ax2.plot(r, Phi_shell, color="#f59e0b", lw=2, ls="--", label="Spherical Shell Phi(r)")
ax2.axvline(R, color="#94a3b8", ls=":", alpha=0.7, label=f"Boundary R={R}")
ax2.set_title("Gravitational Potential Phi(r)")
ax2.set_xlabel("Radial Distance r")
ax2.set_ylabel("Potential Phi(r)")
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()',
    'solution_code' => '',
    'created_at' => '2026-09-30 22:50:50',
    'updated_at' => '2026-09-30 22:50:50',
  ),
  6 => 
  array (
    'id' => 7,
    'category' => 'scattering',
    'title' => 'Two-Body Classical Scattering: Trajectories, Deflection & Rutherford Cross-Section',
    'subtitle' => 'Simulate center-of-mass orbital deflection, impact parameter mapping b(theta), and Rutherford scattering',
    'description' => 'Model two-body collisions in the Center-of-Mass (CM) frame for a central repulsive potential V(r) = k / r^n. Trace classical particle trajectories for a continuous beam of impact parameters b, compute the classical deflection function Theta(b), and verify the differential scattering cross-section against Rutherford\'s analytical law.',
    'theory_equations' => '$$\\Theta(b) = \\pi - 2 b \\int_{r_{\\text{min}}}^\\infty \\frac{dr}{r^2 \\sqrt{1 - \\frac{b^2}{r^2} - \\frac{V(r)}{E_{\\text{cm}}}}}$$
$$\\text{Rutherford Formula: } b(\\theta) = \\frac{k}{2E} \\cot\\left(\\frac{\\theta}{2}\\right) \\implies \\frac{d\\sigma}{d\\Omega} = \\left(\\frac{k}{4E}\\right)^2 \\frac{1}{\\sin^4(\\theta/2)}$$
$$\\text{CM to Lab Transformation: } \\tan\\theta_{\\text{lab}} = \\frac{\\sin\\theta_{\\text{cm}}}{\\cos\\theta_{\\text{cm}} + m_1/m_2}$$',
    'parameters' => 'Center-of-Mass Energy $E = 5.0\\text{ MeV}$, Repulsive Strength $k = 4.0$, Target Charge $Z = 79$, Beam Impact Parameters $b \\in [0.2, 3.5]$.',
    'starter_code' => '# Python4Physics Assignment: Classical Two-Body Scattering & Rutherford Cross-Section
import numpy as np
import matplotlib.pyplot as plt

# ==========================================
# PHYSICAL PARAMETERS (Adjust with sliders)
# ==========================================
E_cm = 5.0            # Center of mass collision energy
k = 4.0               # Repulsive Coulomb coupling constant (k = Z1*Z2*e^2)
dt = 0.004            # Integration timestep
x_start = -10.0       # Initial incoming beam distance
x_end = 10.0

# Generate a beam of incoming particles at various impact parameters b
impact_parameters = np.linspace(0.4, 3.2, 10)

plt.figure(figsize=(10, 4.8))
ax1 = plt.subplot(1, 2, 1)
ax2 = plt.subplot(1, 2, 2)

theta_sim = []
theta_theory = []

for b in impact_parameters:
    # Initial conditions
    # r = sqrt(x^2 + b^2), v_inf = sqrt(2*E/m), setting m=1
    v_inf = np.sqrt(2 * E_cm)
    x = x_start
    y = b
    vx = v_inf
    vy = 0.0
    
    xs, ys = [x], [y]
    
    while x < x_end and len(xs) < 4000:
        r = np.sqrt(x**2 + y**2)
        # Repulsive Coulomb force F = k / r^2
        F = k / (r**2)
        ax = F * (x / r)
        ay = F * (y / r)
        
        vx += ax * dt
        vy += ay * dt
        x += vx * dt
        y += vy * dt
        xs.append(x)
        ys.append(y)
    
    # Final scattering angle
    final_angle = np.degrees(np.arctan2(vy, vx))
    theta_sim.append(final_angle)
    
    # Analytical Rutherford angle: b = (k / 2E) * cot(theta / 2)
    # theta_analytic = 2 * arctan(k / (2 * E * b))
    theta_ana = np.degrees(2 * np.arctan(k / (2 * E_cm * b)))
    theta_theory.append(theta_ana)
    
    ax1.plot(xs, ys, lw=1.2, alpha=0.85)

# Target center marker
ax1.scatter([0], [0], color="#f43f5e", s=120, zorder=6, label="Scattering Center")
ax1.set_title("Scattering Trajectories in CM Frame")
ax1.set_xlabel("x Coordinate")
ax1.set_ylabel("y Coordinate (Impact b)")
ax1.set_xlim(-10, 10)
ax1.set_ylim(-1, 6)
ax1.grid(True, alpha=0.3)
ax1.legend(loc="upper left")

# Subplot 2: Deflection Function b vs Theta (Simulated vs Analytical)
theta_grid = np.linspace(10, 160, 200)
theta_rad = np.radians(theta_grid)
# Differential cross section dsigma/dOmega = (k / 4E)^2 * 1 / sin^4(theta/2)
ds_domega = (k / (4 * E_cm))**2 / (np.sin(theta_rad / 2)**4)

ax2.semilogy(theta_grid, ds_domega, color="#10b981", lw=2, label="Rutherford Formula dsigma/dOmega")
ax2.scatter(theta_sim, [(k / (4 * E_cm))**2 / (np.sin(np.radians(th) / 2)**4) for th in theta_sim], 
            color="#38bdf8", s=40, zorder=5, label="Simulated Particles")
ax2.set_title("Differential Cross-Section dsigma/dOmega")
ax2.set_xlabel("Scattering Angle theta (deg)")
ax2.set_ylabel("dsigma/dOmega (arb units, log)")
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()',
    'solution_code' => '',
    'created_at' => '2026-09-30 22:50:50',
    'updated_at' => '2026-09-30 22:50:50',
  ),
  7 => 
  array (
    'id' => 8,
    'category' => 'fluid-mechanics',
    'title' => 'Viscous Fluid Mechanics: Stokes\' Law, Drag Coefficient Cd(Re) & Terminal Velocity',
    'subtitle' => 'Simulate falling spheres in viscous media and transition from creeping laminar flow to turbulence',
    'description' => 'Investigate the motion of a spherical particle through a viscous incompressible fluid. Derive Stokes\' Law from dimensional analysis, solve the vertical equation of motion under buoyant and non-linear drag forces across creeping (Re << 1) to transitional (Re ~ 10^3) flow regimes, and analyze terminal velocity relaxation.',
    'theory_equations' => '$$m_{\\text{eff}} \\frac{dv}{dt} = (\\rho_s - \\rho_f) V g - \\frac{1}{2} C_d(Re) \\rho_f A v^2, \\quad Re = \\frac{\\rho_f v (2r)}{\\eta}$$
$$\\text{Stokes Creeping Regime } (Re < 0.1): \\quad C_d = \\frac{24}{Re} \\implies F_{\\text{drag}} = 6\\pi \\eta r v, \\quad v_t = \\frac{2 r^2 g (\\rho_s - \\rho_f)}{9\\eta}$$
$$\\text{Schiller-Naumann Transition } (Re < 1000): \\quad C_d(Re) = \\frac{24}{Re}\\left(1 + 0.15 Re^{0.687}\\right)$$',
    'parameters' => 'Sphere radius $r = 3.0\\text{ mm}$, Steel density $\\rho_s = 7800\\text{ kg/m}^3$, Glycerin density $\\rho_f = 1260\\text{ kg/m}^3$, Dynamic viscosity $\\eta = 0.45\\text{ Pa}\\cdot\\text{s}$.',
    'starter_code' => '# Python4Physics Assignment: Stokes Drag & Non-Linear Terminal Velocity
import numpy as np
import matplotlib.pyplot as plt

# ==========================================
# PHYSICAL PARAMETERS (Adjust with sliders)
# ==========================================
r_mm = 3.0            # Sphere radius in mm
rho_s = 7800.0        # Density of falling sphere (kg/m^3, Steel)
rho_f = 1260.0        # Density of fluid (kg/m^3, Glycerin)
eta = 0.45            # Dynamic viscosity (Pa*s)
g = 9.81              # Gravity (m/s^2)

r = r_mm * 1e-3
Volume = (4.0 / 3.0) * np.pi * r**3
Area = np.pi * r**2
m_sphere = rho_s * Volume
m_buoyancy = rho_f * Volume
m_eff = m_sphere      # Net inertial mass

# Analytical Stokes Terminal Velocity (Creeping Flow)
v_terminal_stokes = (2.0 * r**2 * g * (rho_s - rho_f)) / (9.0 * eta)
Re_stokes = (rho_f * v_terminal_stokes * 2 * r) / eta

print(f"--- Settling Dynamics ---")
print(f"Sphere Mass: {m_sphere*1000:.3f} g | Effective Buoyant Force: {(m_sphere-m_buoyancy)*g*1000:.2f} mN")
print(f"Analytical Stokes v_t: {v_terminal_stokes:.4f} m/s")
print(f"Estimated Reynolds Number Re: {Re_stokes:.2f}")

# Time integration
dt = 0.001
t_max = 1.2
steps = int(t_max / dt)

t = np.linspace(0, t_max, steps)
v = np.zeros(steps)
y = np.zeros(steps)
re_arr = np.zeros(steps)

for i in range(steps - 1):
    vel = v[i]
    Re = (rho_f * np.abs(vel) * (2 * r)) / eta
    re_arr[i] = Re
    
    # Empirical Schiller-Naumann Drag Coefficient
    if Re < 0.1:
        Cd = 24.0 / max(Re, 1e-6)
    elif Re < 1000:
        Cd = (24.0 / Re) * (1.0 + 0.15 * (Re**0.687))
    else:
        Cd = 0.44  # Newton turbulent regime
        
    F_drag = 0.5 * Cd * rho_f * Area * vel**2
    # Gravitational + Buoyant - Drag
    F_net = (m_sphere - m_buoyancy) * g - F_drag
    accel = F_net / m_sphere
    
    v[i+1] = v[i] + accel * dt
    y[i+1] = y[i] + v[i+1] * dt

re_arr[-1] = (rho_f * v[-1] * (2 * r)) / eta

# Visualizations
fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(10, 4.5))

# 1. Velocity Relaxation
ax1.plot(t, v, color="#0284c7", lw=2, label="Numerical v(t) (Schiller-Naumann)")
ax1.axhline(v_terminal_stokes, color="#f59e0b", ls="--", label=f"Stokes Limit ({v_terminal_stokes:.3f} m/s)")
ax1.set_title("Falling Sphere Velocity v(t)")
ax1.set_xlabel("Time (s)")
ax1.set_ylabel("Velocity (m/s)")
ax1.grid(True, alpha=0.3)
ax1.legend()

# 2. Reynolds Number Evolution Re(t)
ax2.plot(t, re_arr, color="#10b981", lw=2, label="Reynolds Number Re(t)")
ax2.axhline(0.1, color="#38bdf8", ls=":", label="Creeping Flow Limit (Re=0.1)")
regime_str = "Laminar Creeping" if re_arr[-1] < 1 else "Transitional Vortex"
ax2.set_title(f"Flow Regime: {regime_str}")
ax2.set_xlabel("Time (s)")
ax2.set_ylabel("Reynolds Number Re")
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()',
    'solution_code' => '',
    'created_at' => '2026-09-30 22:50:50',
    'updated_at' => '2026-09-30 22:50:50',
  ),
  8 => 
  array (
    'id' => 9,
    'category' => 'fluid-mechanics',
    'title' => 'Incompressible Continuum Flow: Continuity Equation & Bernoulli Venturi Dynamics',
    'subtitle' => 'Model streamline velocity vector fields, pressure gradients, and cavitation thresholds in constricted ducts',
    'description' => 'Formulate the kinematics and dynamics of steady, incompressible, irrotational fluid flow through a converging-diverging Venturi tube. Solve the Continuity Equation A(x) v(x) = Q and Bernoulli\'s Theorem to map streamline velocity fields, hydrodynamic pressure drops, and identify cavitation onset.',
    'theory_equations' => '$$\\nabla \\cdot \\vec{v} = 0 \\implies Q = A_1 v_1 = A_2 v_2 = \\text{constant} \\implies v(x) = \\frac{Q}{\\pi R(x)^2}$$
$$P(x) + \\frac{1}{2}\\rho v(x)^2 + \\rho g z = \\text{constant} \\implies P(x) = P_0 - \\frac{1}{2}\\rho \\left[ v(x)^2 - v_0^2 \\right]$$
$$\\text{Euler\'s Equation: } \\rho v \\frac{dv}{dx} = -\\frac{dP}{dx}, \\quad \\text{Cavitation Condition: } P_{\\text{throat}} \\le P_{\\text{vapor}}$$
$$\\text{Manometer Differential: } \\Delta h = \\frac{P_1 - P_2}{\\rho_m g} = \\frac{Q^2}{2g}\\left(\\frac{1}{A_2^2} - \\frac{1}{A_1^2}\\right)$$',
    'parameters' => 'Inlet pipe radius $R_0 = 0.05\\text{ m}$, Throat constriction radius ratio $R_{\\text{throat}}/R_0 = 0.45$, Volumetric flow rate $Q = 0.018\\text{ m}^3/\\text{s}$, Fluid density $\\rho = 1000\\text{ kg/m}^3$ (Water).',
    'starter_code' => '# Python4Physics Assignment: Continuity Equation & Bernoulli Venturi Dynamics
import numpy as np
import matplotlib.pyplot as plt

# ==========================================
# PHYSICAL PARAMETERS (Adjust with sliders)
# ==========================================
Q = 0.018             # Volumetric flux (m^3/s)
R0 = 0.05             # Inlet pipe radius (m)
throat_ratio = 0.45   # Constriction ratio (R_throat / R0)
P0 = 101325.0         # Atmospheric inlet pressure (Pa)
rho = 1000.0          # Fluid density (kg/m^3, Water)
P_vapor = 2338.0      # Water vapor pressure at 20 deg C (Pa, Cavitation threshold)

# Tube geometry along axis x in [-0.5, 0.5] meters
x = np.linspace(-0.5, 0.5, 300)
# Gaussian constriction: R(x) = R0 - delta * exp(-x^2 / (2*sigma^2))
R_throat = R0 * throat_ratio
delta_R = R0 - R_throat
sigma = 0.10
R_x = R0 - delta_R * np.exp(-x**2 / (2 * sigma**2))
Area_x = np.pi * R_x**2

# Continuity Equation: v(x) = Q / A(x)
v_x = Q / Area_x
v0 = v_x[0]

# Bernoulli\'s Equation: P(x) + 0.5*rho*v(x)^2 = P0 + 0.5*rho*v0^2
P_x = P0 - 0.5 * rho * (v_x**2 - v0**2)
P_min = np.min(P_x)

print(f"--- Continuum Flow Telemetry ---")
print(f"Inlet Velocity: {v_x[0]:.2f} m/s | Throat Max Velocity: {np.max(v_x):.2f} m/s")
print(f"Inlet Pressure: {P0/1000:.1f} kPa | Throat Minimum Pressure: {P_min/1000:.2f} kPa")
print(f"Pressure Drop Delta P: {(P0 - P_min)/1000:.2f} kPa")
if P_min <= P_vapor:
    print("WARNING: Cavitation Threshold Exceeded! Vapor bubbles will form.")
else:
    print("Flow Regime: Stable Single-Phase Flow (No Cavitation)")

# Plots: Geometry, Streamline Velocity & Pressure Gradient
fig, (ax1, ax2) = plt.subplots(2, 1, figsize=(9, 5.5), sharex=True)

# 1. Duct Geometry and Streamlines
ax1.plot(x, R_x, color="#0284c7", lw=2)
ax1.plot(x, -R_x, color="#0284c7", lw=2)
ax1.fill_between(x, R_x, 0.08, color="#94a3b8", alpha=0.2)
ax1.fill_between(x, -0.08, -R_x, color="#94a3b8", alpha=0.2)

# Streamline lines proportional to velocity
for f in np.linspace(-0.7, 0.7, 7):
    ax1.plot(x, f * R_x, color="#38bdf8", alpha=0.6, lw=1)

ax1.set_title("Venturi Channel Geometry & Continuity Streamlines")
ax1.set_ylabel("Radius R(x) [m]")
ax1.set_ylim(-0.07, 0.07)
ax1.grid(True, alpha=0.3)

# 2. Pressure Profile & Cavitation Threshold
ax2.plot(x, P_x / 1000, color="#10b981", lw=2, label="Hydrodynamic Pressure P(x)")
ax2.axhline(P_vapor / 1000, color="#f43f5e", ls="--", label="Vapor Pressure P_v (Cavitation)")
ax2.set_title("Bernoulli Pressure Distribution P(x)")
ax2.set_xlabel("Axial Distance x (m)")
ax2.set_ylabel("Pressure (kPa)")
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()',
    'solution_code' => '',
    'created_at' => '2026-09-30 22:50:50',
    'updated_at' => '2026-09-30 22:50:50',
  ),
  9 => 
  array (
    'id' => 10,
    'category' => 'newtons-laws',
    'title' => 'Newton\'s Laws & Rotational Dynamics: Damped Physical Pendulum & Phase Portraits',
    'subtitle' => 'Model non-linear torque, moment of inertia, viscous damping, and angular momentum phase trajectories',
    'description' => 'Formulate Newton\'s second law for rotational dynamics under restoring gravitational torque and viscous aerodynamic damping. Integrate the non-linear equation of motion d²θ/dt² + γ dθ/dt + (mgd/I) sinθ = 0 for large angular amplitudes using the Runge-Kutta 4th-order (RK4) method. Analyze phase-space portraits (θ vs dθ/dt), energy dissipation curves, and compare small-angle harmonic approximations with full non-linear solutions.',
    'theory_equations' => '$$\\tau_{\\text{net}} = I \\alpha = I \\frac{d^2\\theta}{dt^2} = -m g d \\sin\\theta - b \\frac{d\\theta}{dt}, \\quad \\text{where } I = \\frac{1}{3} m L^2, \\quad d = \\frac{L}{2}$$
$$\\frac{d^2\\theta}{dt^2} + \\gamma \\frac{d\\theta}{dt} + \\omega_0^2 \\sin\\theta = 0, \\quad \\omega_0 = \\sqrt{\\frac{3g}{2L}}, \\quad \\gamma = \\frac{b}{I}$$
$$E(t) = \\frac{1}{2} I \\left(\\frac{d\\theta}{dt}\\right)^2 + m g d (1 - \\cos\\theta) \\implies \\frac{dE}{dt} = -b \\left(\\frac{d\\theta}{dt}\\right)^2 \\le 0$$
$$T_{\\text{exact}} \\approx 2\\pi \\sqrt{\\frac{2L}{3g}} \\left( 1 + \\frac{1}{16}\\theta_0^2 + \\frac{11}{3072}\\theta_0^4 \\right)$$',
    'parameters' => 'Rod Length $L = 1.0\\text{ m}$, Mass $m = 1.5\\text{ kg}$, Initial Angle $\\theta_0 = 75^\\circ$, Damping Factor $\\gamma = 0.35\\text{ s}^{-1}$.',
    'starter_code' => '# Python4Physics Assignment: Rotational Dynamics & Damped Physical Pendulum
import numpy as np
import matplotlib.pyplot as plt

# ==========================================
# PHYSICAL PARAMETERS (Adjust with sliders)
# ==========================================
theta0_deg = 75.0     # Initial displacement angle (degrees)
gamma = 0.35          # Damping coefficient (1/s)
L = 1.0               # Rod length (m)
m = 1.5               # Rod mass (kg)
g = 9.81              # Gravitational acceleration (m/s^2)

# Physical Pendulum quantities (pivot at end of thin uniform rod)
d = L / 2.0           # Center of mass distance from pivot
I = (1.0 / 3.0) * m * L**2  # Moment of inertia about end pivot
omega0_sq = (m * g * d) / I
omega0 = np.sqrt(omega0_sq)
T_linear = 2.0 * np.pi / omega0

theta0 = np.radians(theta0_deg)
omega_init = 0.0      # Released from rest

print(f"--- Rotational Dynamics Telemetry ---")
print(f"Rod Length L: {L:.2f} m | Mass m: {m:.2f} kg | Moment of Inertia I: {I:.3f} kg*m^2")
print(f"Small-Angle Natural Frequency omega_0: {omega0:.3f} rad/s (T_0 = {T_linear:.2f} s)")
print(f"Initial Angle: {theta0_deg:.1f} deg ({theta0:.3f} rad) | Damping gamma: {gamma:.3f} s^-1")
if gamma < 2 * omega0:
    print(f"Damping Regime: Underdamped Periodic (Damped Frequency omega_d = {np.sqrt(max(0, omega0_sq - (gamma/2)**2)):.3f} rad/s)")
else:
    print("Damping Regime: Overdamped / Aperiodic")

# Numerical Integration via Runge-Kutta 4th Order (RK4)
# State vector: [theta, omega]
def derivatives(state):
    th, om = state[0], state[1]
    dth_dt = om
    dom_dt = -gamma * om - omega0_sq * np.sin(th)
    return np.array([dth_dt, dom_dt])

dt = 0.005
t_max = 12.0
t_steps = int(t_max / dt)
t = np.linspace(0, t_max, t_steps)
states = np.zeros((t_steps, 2))
states[0] = [theta0, omega_init]

for i in range(t_steps - 1):
    s = states[i]
    k1 = dt * derivatives(s)
    k2 = dt * derivatives(s + 0.5 * k1)
    k3 = dt * derivatives(s + 0.5 * k2)
    k4 = dt * derivatives(s + k3)
    states[i+1] = s + (k1 + 2*k2 + 2*k3 + k4) / 6.0

theta = states[:, 0]
omega = states[:, 1]
theta_deg = np.degrees(theta)

# Total Mechanical Energy E(t) = (1/2) I omega^2 + m g d (1 - cos(theta))
E_kinetic = 0.5 * I * omega**2
E_potential = m * g * d * (1.0 - np.cos(theta))
E_total = E_kinetic + E_potential

# Visualizations: Waveform, Phase Portrait & Energy Dissipation
fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(10, 4.5))

# 1. Non-linear Waveform vs Time
ax1.plot(t, theta_deg, color="#0284c7", lw=1.8, label=r"Non-linear $\\theta(t)$ [RK4]")
# Linear small-angle damped comparison
th_lin = theta0_deg * np.exp(-0.5 * gamma * t) * np.cos(np.sqrt(max(0.1, omega0_sq - 0.25*gamma**2)) * t)
ax1.plot(t, th_lin, "--", color="#94a3b8", alpha=0.7, lw=1.2, label=r"Linear Approx ($\\sin\\theta \\approx \\theta$)")
ax1.set_title("Rotational Motion: Angular Displacement $\\theta(t)$")
ax1.set_xlabel("Time t (s)")
ax1.set_ylabel("Angle $\\theta$ (degrees)")
ax1.grid(True, alpha=0.3)
ax1.legend(loc="upper right", fontsize=8)

# 2. Phase-Space Portrait (theta vs omega)
ax2.plot(theta_deg, omega, color="#10b981", lw=1.5, label="Phase Trajectory")
ax2.scatter([theta_deg[0]], [omega[0]], color="#f43f5e", s=60, zorder=5, label="Initial State")
ax2.scatter([0], [0], color="#f59e0b", s=60, zorder=5, label="Stable Attractor (0,0)")
ax2.set_title(r"Phase-Space Portrait $(\\theta, \\dot{\\theta})$")
ax2.set_xlabel(r"Angle $\\theta$ (degrees)")
ax2.set_ylabel(r"Angular Velocity $\\dot{\\theta}$ (rad/s)")
ax2.grid(True, alpha=0.3)
ax2.legend(loc="upper right", fontsize=8)

plt.tight_layout()
plt.show()',
    'solution_code' => '',
    'created_at' => '2026-10-01 13:51:15',
    'updated_at' => '2026-10-01 13:52:24',
  ),
  10 => 
  array (
    'id' => 11,
    'category' => 'potential-wells',
    'title' => 'Potential Theory: 1D Asymmetric Potential Wells, Equilibrium Stability & Anharmonic Oscillations',
    'subtitle' => 'Determine stable/unstable equilibria, Taylor series effective spring constants, and phase portraits',
    'description' => 'Investigate one-dimensional conservative motion governed by an asymmetric double-well potential V(x) = (a/4)x⁴ - (b/2)x² + c x. Compute equilibrium points where dV/dx = 0, classify stability using the second derivative d²V/dx², derive the effective harmonic oscillation frequency ω₀ = √(V\'\'(x₀)/m) for small displacements, and integrate the equation of motion to map closed phase-space trajectories and barrier transitions.',
    'theory_equations' => '$$F(x) = -\\frac{dV}{dx} = -a x^3 + b x - c = m \\frac{d^2x}{dt^2}$$
$$\\text{Equilibrium Condition: } \\left.\\frac{dV}{dx}\\right|_{x_0} = 0 \\implies a x_0^3 - b x_0 + c = 0$$
$$k_{\\text{eff}} = \\left.\\frac{d^2V}{dx^2}\\right|_{x_0} = 3 a x_0^2 - b > 0 \\implies \\omega_0 = \\sqrt{\\frac{k_{\\text{eff}}}{m}}, \\quad T_0 = \\frac{2\\pi}{\\omega_0}$$
$$V(x) \\approx V(x_0) + \\frac{1}{2} k_{\\text{eff}} (x - x_0)^2 + \\frac{1}{6} V\'\'\'(x_0) (x - x_0)^3 + \\dots$$',
    'parameters' => 'Quartic barrier parameter $a = 1.0$, Double-well depth parameter $b = 4.0$, Asymmetry tilt $c = 0.4$, Particle mass $m = 1.0$, Initial displacement $x_0 = 1.85$.',
    'starter_code' => '# Python4Physics Assignment: 1D Potential Well & Stability Analysis
import numpy as np
import matplotlib.pyplot as plt

# ==========================================
# PHYSICAL PARAMETERS (Adjust with sliders)
# ==========================================
a = 1.00              # Quartic confinement coefficient: (a/4)*x^4
b = 4.00              # Quadratic barrier coefficient: -(b/2)*x^2
c = 0.40              # Linear asymmetry tilt: c*x
m = 1.00              # Particle mass (kg)
x0 = 1.85             # Initial release position
v0 = 0.00             # Initial velocity (released from rest)

# Define Potential V(x) and Force F(x) = -dV/dx
def V(pos):
    return 0.25 * a * pos**4 - 0.5 * b * pos**2 + c * pos

def force(pos):
    return -a * pos**3 + b * pos - c

def d2V_dx2(pos):
    return 3.0 * a * pos**2 - b

# Find local equilibrium points numerically: roots of a*x^3 - b*x + c = 0
roots = np.roots([a, 0.0, -b, c])
real_roots = np.sort([np.real(r) for r in roots if np.isclose(np.imag(r), 0.0, atol=1e-5)])

print(f"--- Potential Field & Equilibrium Telemetry ---")
print(f"Parameters: a={a:.2f}, b={b:.2f}, c={c:.2f} | Particle Mass m={m:.2f}")
print(f"Equilibrium Points found: {len(real_roots)}")
for r in real_roots:
    curv = d2V_dx2(r)
    st = "STABLE MINIMUM (d^2V/dx^2 > 0)" if curv > 0 else "UNSTABLE MAXIMUM (d^2V/dx^2 < 0)"
    freq = np.sqrt(max(0, curv / m))
    print(f"  x_0 = {r:+.3f} | V(x_0) = {V(r):+.3f} | k_eff = {curv:+.3f} | {st}")
    if curv > 0:
        print(f"    -> Small-oscillation natural frequency omega_0: {freq:.3f} rad/s (T_0 = {2*np.pi/freq:.2f} s)")

# Total initial energy E0 = (1/2)m v0^2 + V(x0)
E0 = 0.5 * m * v0**2 + V(x0)
print(f"Total Particle Energy E_0: {E0:.3f} J")

# Velocity-Verlet Numerical Integration of m d^2x/dt^2 = F(x)
dt = 0.005
t_max = 14.0
t_steps = int(t_max / dt)
t = np.linspace(0, t_max, t_steps)
x = np.zeros(t_steps)
v = np.zeros(t_steps)
x[0] = x0
v[0] = v0

ax0 = force(x[0]) / m
for i in range(t_steps - 1):
    v_half = v[i] + 0.5 * ax0 * dt
    x[i+1] = x[i] + v_half * dt
    ax1 = force(x[i+1]) / m
    v[i+1] = v_half + 0.5 * ax1 * dt
    ax0 = ax1

# Plots: 1. Potential Curve V(x) with Energy Level | 2. Phase-Space Portrait (x, v)
fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(10, 4.5))

# 1. Potential Curve V(x)
x_grid = np.linspace(-2.5, 2.5, 300)
ax1.plot(x_grid, V(x_grid), color="#0284c7", lw=2, label="Potential $V(x)$")
ax1.axhline(E0, color="#f59e0b", ls="--", lw=1.5, label=f"Total Energy $E_0={E0:.2f}$ J")
for r in real_roots:
    color = "#10b981" if d2V_dx2(r) > 0 else "#f43f5e"
    marker = "o" if d2V_dx2(r) > 0 else "^"
    lbl = "Stable Min" if d2V_dx2(r) > 0 else "Unstable Saddle"
    ax1.scatter([r], [V(r)], color=color, marker=marker, s=70, zorder=5)

ax1.scatter([x0], [V(x0)], color="#a855f7", s=80, zorder=6, label=f"Release $x_0={x0:.2f}$")
ax1.set_title("1D Asymmetric Potential Curve $V(x)$")
ax1.set_xlabel("Position x")
ax1.set_ylabel("Potential Energy V(x) [J]")
ax1.set_ylim(min(V(x_grid)) - 1.0, max(V(x_grid)) + 1.0)
ax1.grid(True, alpha=0.3)
ax1.legend(loc="upper right", fontsize=8)

# 2. Phase-Space Trajectory (x vs v)
ax2.plot(x, v, color="#10b981", lw=1.5, label="Phase Trajectory")
ax2.scatter([x[0]], [v[0]], color="#a855f7", s=70, zorder=5, label="Start $(x_0, v_0)$")
for r in real_roots:
    if d2V_dx2(r) > 0:
        ax2.scatter([r], [0], color="#0284c7", s=50, zorder=5)
ax2.set_title(r"Phase-Space Portrait $(x, \\dot{x})$")
ax2.set_xlabel("Position x")
ax2.set_ylabel(r"Velocity $v = \\dot{x}$")
ax2.grid(True, alpha=0.3)
ax2.legend(loc="upper right", fontsize=8)

plt.tight_layout()
plt.show()',
    'solution_code' => '',
    'created_at' => '2026-10-01 13:51:15',
    'updated_at' => '2026-10-01 13:52:24',
  ),
  11 => 
  array (
    'id' => 12,
    'category' => 'particle-dynamics',
    'title' => 'Two-Body Problem & Center-of-Mass Dynamics: Coupled Binary Motion & Momentum Conservation',
    'subtitle' => 'Transform between Laboratory and Center-of-Mass frames, solve reduced mass ODEs, and verify momentum conservation',
    'description' => 'Analyze the dynamics of an isolated two-body interacting system under mutual central forces. Decompose the motion into the uniform translation of the Center of Mass (CM) and the equivalent single-body relative motion with reduced mass μ = m₁m₂/(m₁+m₂). Verify total linear momentum conservation, compute total energy partition E = E_cm + E_rel, and visualize the trajectories in both the Laboratory frame and the Center-of-Mass frame.',
    'theory_equations' => '$$\\vec{R}_{\\text{cm}} = \\frac{m_1 \\vec{r}_1 + m_2 \\vec{r}_2}{m_1 + m_2}, \\quad \\vec{r} = \\vec{r}_1 - \\vec{r}_2, \\quad M = m_1 + m_2, \\quad \\mu = \\frac{m_1 m_2}{m_1 + m_2}$$
$$M \\frac{d^2\\vec{R}_{\\text{cm}}}{dt^2} = \\vec{0} \\implies \\vec{P}_{\\text{tot}} = M \\vec{V}_{\\text{cm}} = \\text{constant}$$
$$\\mu \\frac{d^2\\vec{r}}{dt^2} = -\\frac{G m_1 m_2}{r^3} \\vec{r} \\implies \\frac{d^2\\vec{r}}{dt^2} = -\\frac{G M}{r^3} \\vec{r}$$
$$\\vec{r}_1(t) = \\vec{R}_{\\text{cm}}(t) + \\frac{m_2}{M} \\vec{r}(t), \\quad \\vec{r}_2(t) = \\vec{R}_{\\text{cm}}(t) - \\frac{m_1}{M} \\vec{r}(t)$$',
    'parameters' => 'Primary mass $m_1 = 3.0$, Secondary mass $m_2 = 1.0$, Gravitational parameter $G = 1.0$, Initial separation $r_0 = 2.0$, Relative orbital velocity $v_0 = 1.35$.',
    'starter_code' => '# Python4Physics Assignment: Two-Body Problem & Center of Mass Dynamics
import numpy as np
import matplotlib.pyplot as plt

# ==========================================
# PHYSICAL PARAMETERS (Adjust with sliders)
# ==========================================
m1 = 3.00             # Mass of body 1 (e.g. primary star)
m2 = 1.00             # Mass of body 2 (e.g. secondary companion)
r0 = 2.00             # Initial separation distance between bodies
v0 = 1.35             # Relative orbital speed
G = 1.00              # Gravitational constant

# System Parameters
M = m1 + m2           # Total Mass
mu = (m1 * m2) / M    # Reduced Mass
print(f"--- Two-Body System Telemetry ---")
print(f"Mass m1: {m1:.2f} | Mass m2: {m2:.2f} | Total Mass M: {M:.2f}")
print(f"Reduced Mass mu: {mu:.3f} | Initial Separation r0: {r0:.2f}")

# Circular orbit speed for reference: v_circ = sqrt(G*M / r0)
v_circ = np.sqrt(G * M / r0)
print(f"Circular Reference Speed v_circ: {v_circ:.3f} | Current v0: {v0:.3f}")

# Initial positions and velocities in Center-of-Mass frame:
# R_cm = (0, 0), V_cm = (0, 0.2) to demonstrate CM drifting in Lab frame
V_cm_lab = np.array([0.15, 0.25])

# In CM frame, r1_cm = (m2/M)*r, r2_cm = -(m1/M)*r
r1_cm_0 = np.array([-(m2 / M) * r0, 0.0])
r2_cm_0 = np.array([(m1 / M) * r0, 0.0])

v1_cm_0 = np.array([0.0, -(m2 / M) * v0])
v2_cm_0 = np.array([0.0, (m1 / M) * v0])

# Time integration setup via Symplectic Velocity-Verlet on Relative vector r = r2 - r1
dt = 0.002
t_max = 10.0
t_steps = int(t_max / dt)
t = np.linspace(0, t_max, t_steps)

# Relative coordinate arrays: r(t)
r_rel = np.zeros((t_steps, 2))
v_rel = np.zeros((t_steps, 2))
r_rel[0] = np.array([r0, 0.0])
v_rel[0] = np.array([0.0, v0])

def get_rel_accel(r_vec):
    dist = np.hypot(r_vec[0], r_vec[1])
    return -(G * M / dist**3) * r_vec

a_rel0 = get_rel_accel(r_rel[0])

for i in range(t_steps - 1):
    v_half = v_rel[i] + 0.5 * a_rel0 * dt
    r_rel[i+1] = r_rel[i] + v_half * dt
    a_rel1 = get_rel_accel(r_rel[i+1])
    v_rel[i+1] = v_half + 0.5 * a_rel1 * dt
    a_rel0 = a_rel1

# Reconstruct trajectories in CM Frame and Lab Frame:
# r1_cm(t) = -(m2/M) * r_rel(t)
# r2_cm(t) = +(m1/M) * r_rel(t)
r1_cm = -(m2 / M) * r_rel
r2_cm = +(m1 / M) * r_rel

# CM drifting in Lab frame: R_cm(t) = R_cm(0) + V_cm_lab * t
R_cm_lab = np.outer(t, V_cm_lab)
r1_lab = R_cm_lab + r1_cm
r2_lab = R_cm_lab + r2_cm

# Energy and Momentum Verification
E_rel = 0.5 * mu * np.sum(v_rel**2, axis=1) - (G * m1 * m2) / np.hypot(r_rel[:, 0], r_rel[:, 1])
energy_drift = (np.max(E_rel) - np.min(E_rel)) / np.abs(E_rel[0]) * 100.0
print(f"Initial Relative Energy: {E_rel[0]:.4f} | Energy Drift: {energy_drift:.4e}%")

# Plots: 1. Center-of-Mass Frame | 2. Laboratory Drift Frame
fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(10, 4.5))

# 1. Center of Mass Frame
ax1.plot(r1_cm[:, 0], r1_cm[:, 1], color="#0284c7", lw=1.8, label=f"Body 1 (m1={m1})")
ax1.plot(r2_cm[:, 0], r2_cm[:, 1], color="#ec4899", lw=1.8, label=f"Body 2 (m2={m2})")
ax1.scatter([0], [0], color="#f59e0b", marker="+", s=100, zorder=5, label="Barycenter (CM)")
ax1.scatter([r1_cm[0, 0]], [r1_cm[0, 1]], color="#0284c7", s=50)
ax1.scatter([r2_cm[0, 0]], [r2_cm[0, 1]], color="#ec4899", s=50)
ax1.set_title("Center-of-Mass (Barycentric) Frame")
ax1.set_xlabel("x (AU)")
ax1.set_ylabel("y (AU)")
ax1.axis("equal")
ax1.grid(True, alpha=0.3)
ax1.legend(loc="upper right", fontsize=8)

# 2. Laboratory Frame (Drifting Center of Mass)
ax2.plot(r1_lab[:, 0], r1_lab[:, 1], color="#0284c7", lw=1.5, label="Body 1 (Lab)")
ax2.plot(r2_lab[:, 0], r2_lab[:, 1], color="#ec4899", lw=1.5, label="Body 2 (Lab)")
ax2.plot(R_cm_lab[:, 0], R_cm_lab[:, 1], "--", color="#f59e0b", lw=1.2, label=r"Uniform CM Drift $\\vec{V}_{\\mathrm{cm}}$")
ax2.set_title("Laboratory Frame (Translating CM)")
ax2.set_xlabel("x (AU)")
ax2.set_ylabel("y (AU)")
ax2.axis("equal")
ax2.grid(True, alpha=0.3)
ax2.legend(loc="upper right", fontsize=8)

plt.tight_layout()
plt.show()',
    'solution_code' => '',
    'created_at' => '2026-10-01 13:51:15',
    'updated_at' => '2026-10-01 13:51:15',
  ),
);

foreach ($assignments_data as $as) {
    $check = $conn->prepare("SELECT COUNT(*) FROM assignments WHERE id = ?");
    $check->execute([$as["id"]]);
    if ($check->fetchColumn() == 0) {
        $ins = $conn->prepare("INSERT INTO assignments (id, category, title, subtitle, description, theory_equations, parameters, starter_code, solution_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$as["id"], $as["category"], $as["title"], $as["subtitle"], $as["description"], $as["theory_equations"], $as["parameters"], $as["starter_code"], $as["solution_code"]]);
    }
}
echo "Seeded " . count($assignments_data) . " assignments successfully.";
