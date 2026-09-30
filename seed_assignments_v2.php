<?php
/**
 * Python4Physics - Seed 5 Advanced Physics Assignments into Database
 * Topics: Central Force, Scattering, Mechanics of Continuum
 */
require_once __DIR__ . '/db.php';

if (!isset($conn) || $conn === null) {
    echo "Database connection not available. Exiting.\n";
    exit(1);
}

$assignments_to_add = [
    [
        'id' => 5,
        'category' => 'central-force',
        'title' => 'Central Force: Inverse-Square Orbits & Relativistic Perihelion Precession',
        'subtitle' => 'Solve orbital differential equations in central gravitational fields and model relativistic perturbations',
        'description' => 'Formulate the equations of motion for a celestial body orbiting under Newton\'s inverse-square gravitational force. Compute orbital eccentricity, verify Kepler\'s 2nd Law (areal velocity conservation), and introduce a perturbative potential V_{pert}(r) = -\alpha / r^2 to model relativistic perihelion precession.',
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
        'solution_code' => ''
    ],
    [
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
        'solution_code' => ''
    ],
    [
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
        'solution_code' => ''
    ],
    [
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
        'solution_code' => ''
    ],
    [
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
        'solution_code' => ''
    ]
];

foreach ($assignments_to_add as $as) {
    // Check if assignment exists
    $check = $conn->prepare("SELECT id FROM `assignments` WHERE `id` = :id");
    $check->execute([':id' => $as['id']]);
    if ($check->fetch()) {
        $stmt = $conn->prepare("UPDATE `assignments` SET 
            `category` = :category,
            `title` = :title,
            `subtitle` = :subtitle,
            `description` = :description,
            `theory_equations` = :theory_equations,
            `parameters` = :parameters,
            `starter_code` = :starter_code,
            `solution_code` = :solution_code
            WHERE `id` = :id");
        $stmt->execute($as);
        echo "Updated Assignment #{$as['id']}: {$as['title']}\n";
    } else {
        $stmt = $conn->prepare("INSERT INTO `assignments` 
            (`id`, `category`, `title`, `subtitle`, `description`, `theory_equations`, `parameters`, `starter_code`, `solution_code`) 
            VALUES 
            (:id, :category, :title, :subtitle, :description, :theory_equations, :parameters, :starter_code, :solution_code)");
        $stmt->execute($as);
        echo "Inserted Assignment #{$as['id']}: {$as['title']}\n";
    }
}

echo "Database seeding completed successfully!\n";
