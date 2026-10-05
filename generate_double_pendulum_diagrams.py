"""
Python4Physics - Generator for Module 3 Double Pendulum Dynamics & Chaos Diagrams
Produces high-resolution scientific diagrams for the Theory & Math tabs:
1. double_pendulum_schematic.png & .svg : Complete physical schematic with coordinates, angles, tension, and gravity
2. double_pendulum_chaos_concept.png   : Sensitivity to initial conditions, Lyapunov divergence, and phase space
3. double_pendulum_normal_modes.png    : Small-angle normal modes (in-phase vs out-of-phase) and harmonic resonance
"""
import os
import numpy as np
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from matplotlib.patches import FancyArrowPatch, Rectangle, Circle, Arc, Polygon, FancyBboxPatch

OUTPUT_DIR = os.path.join(os.path.dirname(__file__), 'assets', 'images', 'visualization', 'double_pendulum')
os.makedirs(OUTPUT_DIR, exist_ok=True)

# Theme Palette matching the dark laboratory aesthetic
BG_DARK = '#0b1120'       # Deep space background
CARD_BG = '#1e293b'       # Dark card background
BORDER_COL = '#334155'    # Subtle slate border
TEXT_LIGHT = '#f8fafc'    # Bright white text
TEXT_MUTED = '#94a3b8'    # Slate gray text
ACCENT_CYAN = '#38bdf8'   # Cyan
ACCENT_EMERALD = '#10b981'# Emerald
ACCENT_AMBER = '#f59e0b'  # Amber
ACCENT_ROSE = '#f43f5e'   # Rose
ACCENT_PURPLE = '#a855f7' # Purple
ACCENT_BLUE = '#3b82f6'   # Blue

plt.rcParams.update({
    'font.family': 'sans-serif',
    'font.sans-serif': ['DejaVu Sans', 'Arial', 'Helvetica'],
    'text.color': TEXT_LIGHT,
    'axes.labelcolor': TEXT_LIGHT,
    'xtick.color': TEXT_MUTED,
    'ytick.color': TEXT_MUTED,
    'axes.edgecolor': BORDER_COL,
    'figure.facecolor': BG_DARK,
    'axes.facecolor': CARD_BG,
    'savefig.facecolor': BG_DARK,
    'savefig.edgecolor': 'none'
})

print(f"Generating optimized Double Pendulum diagrams into {OUTPUT_DIR}...")

# -----------------------------------------------------------------------------
# HELPER: Numerical Double Pendulum Integrator (RK4)
# -----------------------------------------------------------------------------
def solve_double_pendulum(L1=1.0, L2=1.0, m1=1.0, m2=1.0, th1_0=np.pi/2, th2_0=np.pi/2, 
                          w1_0=0.0, w2_0=0.0, g=9.81, t_max=12.0, num_steps=1800):
    t = np.linspace(0, t_max, num_steps)
    dt = t[1] - t[0]
    
    # State: [th1, th2, w1, w2]
    y = np.zeros((num_steps, 4))
    y[0] = [th1_0, th2_0, w1_0, w2_0]
    
    def derivatives(state):
        th1, th2, w1, w2 = state
        delta = th1 - th2
        
        # Coupled 2x2 linear system for angular accelerations [a1, a2]
        d1 = (m1 + m2) * L1
        d2 = m2 * L2 * np.cos(delta)
        d3 = m2 * L1 * np.cos(delta)
        d4 = m2 * L2
        
        r1 = -m2 * L2 * w2**2 * np.sin(delta) - (m1 + m2) * g * np.sin(th1)
        r2 = m2 * L1 * w1**2 * np.sin(delta) - m2 * g * np.sin(th2)
        
        det = d1 * d4 - d2 * d3
        a1 = (r1 * d4 - d2 * r2) / det
        a2 = (d1 * r2 - r1 * d3) / det
        
        return np.array([w1, w2, a1, a2])

    for i in range(num_steps - 1):
        s = y[i]
        k1 = derivatives(s)
        k2 = derivatives(s + 0.5 * dt * k1)
        k3 = derivatives(s + 0.5 * dt * k2)
        k4 = derivatives(s + dt * k3)
        y[i + 1] = s + (dt / 6.0) * (k1 + 2*k2 + 2*k3 + k4)

    th1_arr = y[:, 0]
    th2_arr = y[:, 1]
    x1 = L1 * np.sin(th1_arr)
    y1 = -L1 * np.cos(th1_arr)
    x2 = x1 + L2 * np.sin(th2_arr)
    y2 = y1 - L2 * np.cos(th2_arr)
    
    return t, y, x1, y1, x2, y2

# -----------------------------------------------------------------------------
# DIAGRAM 1: Double Pendulum Physical Schematic & Coordinate Geometry
# -----------------------------------------------------------------------------
def make_diagram_schematic():
    fig, ax = plt.subplots(figsize=(10.8, 6.4), dpi=160)
    fig.patch.set_facecolor(BG_DARK)
    ax.set_facecolor(CARD_BG)
    ax.set_xlim(-2.4, 3.2)
    ax.set_ylim(-2.6, 0.6)
    ax.set_aspect('equal')
    ax.axis('off')

    # 1. Ceiling / Fixed Mount centered at Origin
    ceiling_y = 0.0
    ax.fill_between([-0.7, 0.7], [ceiling_y, ceiling_y], [ceiling_y + 0.18, ceiling_y + 0.18], color='#475569', zorder=2)
    ax.plot([-0.7, 0.7], [ceiling_y, ceiling_y], color='#94a3b8', lw=2.5, zorder=3)
    # Hatching
    for hx in np.linspace(-0.65, 0.65, 12):
        ax.plot([hx, hx + 0.08], [ceiling_y, ceiling_y + 0.15], color='#334155', lw=1.5, zorder=2)

    # Origin / Pivot Point O(0,0)
    O = np.array([0.0, 0.0])
    ax.plot(O[0], O[1], 'o', color=TEXT_LIGHT, markersize=8, zorder=6)
    ax.text(O[0] - 0.45, O[1] + 0.06, r'Pivot $\mathcal{O}(0, 0)$', fontsize=11, fontweight='bold', color=TEXT_LIGHT)

    # Physical parameters
    L1 = 1.30
    L2 = 1.15
    th1_deg = 35.0
    th2_deg = 62.0
    th1 = np.radians(th1_deg)
    th2 = np.radians(th2_deg)

    # Position vectors
    P1 = O + np.array([L1 * np.sin(th1), -L1 * np.cos(th1)])
    P2 = P1 + np.array([L2 * np.sin(th2), -L2 * np.cos(th2)])

    # Coordinate Reference Axes at Origin
    ax.annotate('', xy=(0.85, 0.0), xytext=(-0.35, 0.0),
                arrowprops=dict(arrowstyle="->", color='#64748b', lw=1.4, ls='--'))
    ax.text(0.90, 0.01, r'$+x$', color='#94a3b8', fontsize=10, fontweight='bold')
    ax.annotate('', xy=(0.0, -0.75), xytext=(0.0, 0.22),
                arrowprops=dict(arrowstyle="->", color='#64748b', lw=1.4, ls='--'))
    ax.text(-0.20, -0.75, r'$-y$', color='#94a3b8', fontsize=10, fontweight='bold')

    # Vertical reference lines for angles
    ax.plot([O[0], O[0]], [O[1], O[1] - 1.4], color='#64748b', ls=':', lw=1.4, zorder=1)
    ax.plot([P1[0], P1[0]], [P1[1], P1[1] - 1.2], color='#64748b', ls=':', lw=1.4, zorder=1)

    # Rod 1 & Rod 2
    ax.plot([O[0], P1[0]], [O[1], P1[1]], color=ACCENT_CYAN, lw=3.6, zorder=4, solid_capstyle='round')
    ax.plot([P1[0], P2[0]], [P1[1], P2[1]], color=ACCENT_AMBER, lw=3.6, zorder=4, solid_capstyle='round')

    # Angle Arc theta 1
    arc1_r = 0.45
    arc1_angles = np.linspace(-np.pi/2, -np.pi/2 + th1, 40)
    ax.plot(arc1_r * np.cos(arc1_angles), arc1_r * np.sin(arc1_angles), color=ACCENT_CYAN, lw=2.2, zorder=5)
    ax.text(0.18, -0.38, r'$\theta_1$', color=ACCENT_CYAN, fontsize=13, fontweight='bold')

    # Angle Arc theta 2
    arc2_r = 0.42
    arc2_angles = np.linspace(-np.pi/2, -np.pi/2 + th2, 40)
    ax.plot(P1[0] + arc2_r * np.cos(arc2_angles), P1[1] + arc2_r * np.sin(arc2_angles), color=ACCENT_AMBER, lw=2.2, zorder=5)
    ax.text(P1[0] + 0.22, P1[1] - 0.28, r'$\theta_2$', color=ACCENT_AMBER, fontsize=13, fontweight='bold')

    # Bob 1
    bob1_radius = 0.12
    circle1 = Circle(P1, bob1_radius, facecolor=ACCENT_CYAN, edgecolor=TEXT_LIGHT, lw=2, zorder=7)
    ax.add_patch(circle1)
    ax.text(P1[0] - 0.85, P1[1] + 0.10, r'Bob 1: $m_1$', color=ACCENT_CYAN, fontsize=11.5, fontweight='bold')
    ax.text(P1[0] - 1.25, P1[1] - 0.12, r'$x_1 = L_1\sin\theta_1$' + '\n' + r'$y_1 = -L_1\cos\theta_1$', color=TEXT_LIGHT, fontsize=9.2)

    # Bob 2
    bob2_radius = 0.14
    circle2 = Circle(P2, bob2_radius, facecolor=ACCENT_AMBER, edgecolor=TEXT_LIGHT, lw=2, zorder=7)
    ax.add_patch(circle2)
    ax.text(P2[0] + 0.20, P2[1] + 0.10, r'Bob 2: $m_2$', color=ACCENT_AMBER, fontsize=11.5, fontweight='bold')
    ax.text(P2[0] + 0.20, P2[1] - 0.16, r'$x_2 = L_1\sin\theta_1 + L_2\sin\theta_2$' + '\n' + r'$y_2 = -L_1\cos\theta_1 - L_2\cos\theta_2$', color=TEXT_LIGHT, fontsize=9.2)

    # Rod length labels
    mid1 = (O + P1) / 2
    ax.text(mid1[0] + 0.10, mid1[1] + 0.08, r'$L_1$', color=ACCENT_CYAN, fontsize=12.5, fontweight='bold')
    mid2 = (P1 + P2) / 2
    ax.text(mid2[0] - 0.16, mid2[1] + 0.10, r'$L_2$', color=ACCENT_AMBER, fontsize=12.5, fontweight='bold')

    # Forces: Gravitational vectors (Downward)
    g_len = 0.48
    ax.annotate('', xy=(P1[0], P1[1] - g_len), xytext=P1,
                arrowprops=dict(arrowstyle="->", color=ACCENT_ROSE, lw=2.2))
    ax.text(P1[0] - 0.42, P1[1] - 0.38, r'$m_1 \vec{g}$', color=ACCENT_ROSE, fontsize=10.5, fontweight='bold')

    ax.annotate('', xy=(P2[0], P2[1] - g_len), xytext=P2,
                arrowprops=dict(arrowstyle="->", color=ACCENT_ROSE, lw=2.2))
    ax.text(P2[0] - 0.40, P2[1] - 0.38, r'$m_2 \vec{g}$', color=ACCENT_ROSE, fontsize=10.5, fontweight='bold')

    # Tension forces along rods
    ax.annotate('', xy=(P1[0] - 0.32 * np.sin(th1), P1[1] + 0.32 * np.cos(th1)), xytext=P1,
                arrowprops=dict(arrowstyle="->", color=ACCENT_EMERALD, lw=1.8))
    ax.text(P1[0] - 0.38, P1[1] + 0.32, r'$\vec{T}_1$', color=ACCENT_EMERALD, fontsize=10.5, fontweight='bold')

    ax.annotate('', xy=(P2[0] - 0.32 * np.sin(th2), P2[1] + 0.32 * np.cos(th2)), xytext=P2,
                arrowprops=dict(arrowstyle="->", color=ACCENT_EMERALD, lw=1.8))
    ax.text(P2[0] - 0.36, P2[1] + 0.30, r'$\vec{T}_2$', color=ACCENT_EMERALD, fontsize=10.5, fontweight='bold')

    # Velocity vectors (tangential)
    v1_dir = np.array([np.cos(th1), np.sin(th1)]) * 0.40
    ax.annotate('', xy=(P1 + v1_dir), xytext=P1,
                arrowprops=dict(arrowstyle="->", color=ACCENT_PURPLE, lw=1.8))
    ax.text(P1[0] + v1_dir[0] + 0.05, P1[1] + v1_dir[1], r'$\vec{v}_1$', color=ACCENT_PURPLE, fontsize=10.5, fontweight='bold')

    v2_dir = np.array([np.cos(th2), np.sin(th2)]) * 0.46
    ax.annotate('', xy=(P2 + v2_dir), xytext=P2,
                arrowprops=dict(arrowstyle="->", color=ACCENT_PURPLE, lw=1.8))
    ax.text(P2[0] + v2_dir[0] + 0.05, P2[1] + v2_dir[1], r'$\vec{v}_2$', color=ACCENT_PURPLE, fontsize=10.5, fontweight='bold')

    # Trajectory trail sketch of lower bob
    t_sketch = np.linspace(0, 1.8, 80)
    x_trail = P2[0] - 0.50 * np.sin(2.5 * t_sketch)
    y_trail = P2[1] - 0.25 * (1 - np.cos(2.8 * t_sketch))
    ax.plot(x_trail, y_trail, color='#a855f7', ls='--', lw=1.4, alpha=0.55, zorder=3)
    ax.text(x_trail[-1] - 0.45, y_trail[-1] - 0.12, 'Chaotic Trajectory Trace', color='#c084fc', fontsize=8.8, style='italic')

    # Clean Informative Box on Top Right
    info_box = (
        r"$\mathbf{Lagrangian\ Mechanics:}$" + "\n"
        r"$\bullet\ \text{Lagrangian: }\mathcal{L}(\theta_1, \theta_2, \dot{\theta}_1, \dot{\theta}_2) = T - V$" + "\n"
        r"$\bullet\ T = \frac{1}{2}(m_1+m_2)L_1^2\dot{\theta}_1^2 + \frac{1}{2}m_2L_2^2\dot{\theta}_2^2 + m_2L_1L_2\dot{\theta}_1\dot{\theta}_2\cos(\theta_1-\theta_2)$" + "\n"
        r"$\bullet\ V = -(m_1+m_2)gL_1\cos\theta_1 - m_2gL_2\cos\theta_2$" + "\n"
        r"$\bullet\ \text{Conserved Mechanical Energy: } E = T + V = \text{constant}$"
    )
    ax.text(0.96, 0.95, info_box, transform=ax.transAxes, fontsize=8.8, verticalalignment='top', horizontalalignment='right',
            bbox=dict(boxstyle='round,pad=0.55', facecolor='#0f172a', edgecolor=BORDER_COL, alpha=0.95))

    # Title
    ax.set_title('Double Pendulum: Coordinate Geometry, Angles, and Forces', fontsize=13.5, fontweight='bold', color=ACCENT_CYAN, pad=10)

    plt.tight_layout()
    png_path = os.path.join(OUTPUT_DIR, 'double_pendulum_schematic.png')
    svg_path = os.path.join(OUTPUT_DIR, 'double_pendulum_schematic.svg')
    plt.savefig(png_path, dpi=160, bbox_inches='tight')
    plt.savefig(svg_path, format='svg', bbox_inches='tight')
    plt.close()
    print("[OK] Created double_pendulum_schematic.png & .svg")

# -----------------------------------------------------------------------------
# DIAGRAM 2: Deterministic Chaos & Butterfly Sensitivity (Exact Simulation)
# -----------------------------------------------------------------------------
def make_diagram_chaos():
    fig = plt.figure(figsize=(13.5, 4.8), dpi=150)
    fig.patch.set_facecolor(BG_DARK)

    # Integrate realistic chaotic motion
    t, yA, x1A, y1A, x2A, y2A = solve_double_pendulum(
        L1=1.0, L2=1.0, m1=1.0, m2=1.0, 
        th1_0=np.radians(90), th2_0=np.radians(90),
        t_max=12.0, num_steps=1800
    )
    
    # Perturbed pendulum B (10^-3 rad delta)
    _, yB, x1B, y1B, x2B, y2B = solve_double_pendulum(
        L1=1.0, L2=1.0, m1=1.0, m2=1.0, 
        th1_0=np.radians(90), th2_0=np.radians(90) + 0.001,
        t_max=12.0, num_steps=1800
    )

    # 1. Dual Pendulum Divergence Trajectory
    ax1 = fig.add_subplot(1, 3, 1)
    ax1.set_facecolor(CARD_BG)
    ax1.plot(x2A, y2A, color=ACCENT_CYAN, lw=1.2, alpha=0.8, label=r'Pendulum A $(\theta_0)$')
    ax1.plot(x2B, y2B, color=ACCENT_AMBER, lw=1.2, alpha=0.8, label=r'Pendulum B $(\theta_0 + 10^{-3}\text{ rad})$')
    
    # Mark final positions
    ax1.plot(x2A[-1], y2A[-1], 'o', color=ACCENT_CYAN, markersize=8)
    ax1.plot(x2B[-1], y2B[-1], 'o', color=ACCENT_AMBER, markersize=8)
    ax1.plot(0, 0, 's', color=TEXT_LIGHT, markersize=6, label='Pivot')

    ax1.set_xlim(-2.2, 2.2)
    ax1.set_ylim(-2.2, 1.2)
    ax1.set_aspect('equal')
    ax1.set_title('Chaotic Orbital Trajectories', fontsize=11, fontweight='bold', color=ACCENT_CYAN)
    ax1.set_xlabel('Lower Bob Position $x$ (m)', fontsize=9.5)
    ax1.set_ylabel('Lower Bob Position $y$ (m)', fontsize=9.5)
    ax1.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)
    ax1.legend(loc='upper right', fontsize=8.0, framealpha=0.85)

    # 2. Phase Space Portrait (theta2 vs omega2 for Pendulum A)
    ax2 = fig.add_subplot(1, 3, 2)
    ax2.set_facecolor(CARD_BG)
    # Wrap angles to [-pi, pi]
    th2_wrapped = np.mod(yA[:, 1] + np.pi, 2 * np.pi) - np.pi
    w2 = yA[:, 3]
    ax2.plot(th2_wrapped, w2, color=ACCENT_PURPLE, lw=0.8, alpha=0.7)
    ax2.scatter(th2_wrapped[::30], w2[::30], color=ACCENT_ROSE, s=12, alpha=0.85, zorder=3)
    ax2.set_title('Phase Space Portrait $(\\theta_2, \\dot{\\theta}_2)$', fontsize=11, fontweight='bold', color=ACCENT_PURPLE)
    ax2.set_xlabel(r'Angle $\theta_2$ (rad)', fontsize=9.5)
    ax2.set_ylabel(r'Angular Velocity $\dot{\theta}_2$ (rad/s)', fontsize=9.5)
    ax2.set_xlim(-np.pi - 0.1, np.pi + 0.1)
    ax2.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)

    # 3. Lyapunov Exponential Divergence (Log scale)
    ax3 = fig.add_subplot(1, 3, 3)
    ax3.set_facecolor(CARD_BG)
    
    # Euclidean separation between bobs: delta_r(t) = sqrt((x2A - x2B)^2 + (y2A - y2B)^2)
    delta_r = np.sqrt((x2A - x2B)**2 + (y2A - y2B)**2)
    # Avoid zero for log
    delta_r = np.maximum(delta_r, 1e-6)

    ax3.semilogy(t, delta_r, color=ACCENT_ROSE, lw=2.0, label=r'$\Delta r(t) = \|\vec{r}_{2,A} - \vec{r}_{2,B}\|$')
    ax3.axhline(2.0 * (1.0 + 1.0), color='#94a3b8', ls='--', lw=1.2, label='Geometric Bound $2(L_1+L_2)$')
    
    # Estimate predictability horizon where delta_r crosses 0.5 m
    cross_idx = np.where(delta_r > 0.5)[0]
    if len(cross_idx) > 0:
        t_horizon = t[cross_idx[0]]
        ax3.axvline(t_horizon, color=ACCENT_AMBER, ls=':', lw=1.4, label=f'Lyapunov Horizon ($t\\approx {t_horizon:.1f}$ s)')

    ax3.set_title(r'Lyapunov Divergence: $\Delta r(t) \sim e^{\lambda t}$', fontsize=11, fontweight='bold', color=ACCENT_ROSE)
    ax3.set_xlabel('Time $t$ (seconds)', fontsize=9.5)
    ax3.set_ylabel(r'Separation Distance $\Delta r$ (m)', fontsize=9.5)
    ax3.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)
    ax3.legend(loc='lower right', fontsize=8.0, framealpha=0.85)

    plt.tight_layout()
    out_path = os.path.join(OUTPUT_DIR, 'double_pendulum_chaos_concept.png')
    plt.savefig(out_path, dpi=160, bbox_inches='tight')
    plt.close()
    print("[OK] Created realistic double_pendulum_chaos_concept.png")

# -----------------------------------------------------------------------------
# DIAGRAM 3: Small-Angle Normal Modes & Harmonic Superposition
# -----------------------------------------------------------------------------
def make_diagram_normal_modes():
    fig, (ax1, ax2, ax3) = plt.subplots(1, 3, figsize=(13.5, 4.6), dpi=150)
    fig.patch.set_facecolor(BG_DARK)

    # Mode 1: In-Phase (Symmetric)
    ax1.set_facecolor(CARD_BG)
    ax1.set_xlim(-1.2, 1.2)
    ax1.set_ylim(-2.6, 0.4)
    ax1.set_aspect('equal')
    ax1.axis('off')
    ax1.set_title('Mode 1: In-Phase (Low Freq $\\omega_1$)', fontsize=11, fontweight='bold', color=ACCENT_EMERALD)

    # Mount
    ax1.plot([-0.6, 0.6], [0, 0], color='#94a3b8', lw=2)
    ax1.plot(0, 0, 'o', color=TEXT_LIGHT, markersize=6)
    
    # In-phase: both rods swing together to right
    th_a = np.radians(22)
    th_b = np.radians(30)
    p1 = np.array([np.sin(th_a), -np.cos(th_a)])
    p2 = p1 + np.array([np.sin(th_b), -np.cos(th_b)])
    ax1.plot([0, p1[0]], [0, p1[1]], color=ACCENT_EMERALD, lw=3)
    ax1.plot([p1[0], p2[0]], [p1[1], p2[1]], color=ACCENT_EMERALD, lw=3)
    ax1.plot(p1[0], p1[1], 'o', color=ACCENT_EMERALD, markersize=10)
    ax1.plot(p2[0], p2[1], 'o', color=ACCENT_EMERALD, markersize=12)
    ax1.text(0.45, -0.6, r'$\theta_1 > 0$', color=ACCENT_EMERALD, fontsize=10)
    ax1.text(0.85, -1.8, r'$\theta_2 > 0$', color=ACCENT_EMERALD, fontsize=10)
    ax1.text(0, -2.4, r'$\omega_1 = \sqrt{\frac{g}{L}(2 - \sqrt{2})}$' + '\n' + '(Symmetric Slow Swing)',
             color=TEXT_LIGHT, fontsize=9.5, ha='center',
             bbox=dict(boxstyle='round,pad=0.4', facecolor='#0f172a', edgecolor=BORDER_COL))

    # Mode 2: Out-of-Phase (Anti-Symmetric)
    ax2.set_facecolor(CARD_BG)
    ax2.set_xlim(-1.2, 1.2)
    ax2.set_ylim(-2.6, 0.4)
    ax2.set_aspect('equal')
    ax2.axis('off')
    ax2.set_title('Mode 2: Out-of-Phase (High Freq $\\omega_2$)', fontsize=11, fontweight='bold', color=ACCENT_ROSE)

    ax2.plot([-0.6, 0.6], [0, 0], color='#94a3b8', lw=2)
    ax2.plot(0, 0, 'o', color=TEXT_LIGHT, markersize=6)
    
    # Out of phase: rod 1 right, rod 2 left
    th_c = np.radians(22)
    th_d = np.radians(-32)
    p3 = np.array([np.sin(th_c), -np.cos(th_c)])
    p4 = p3 + np.array([np.sin(th_d), -np.cos(th_d)])
    ax2.plot([0, p3[0]], [0, p3[1]], color=ACCENT_ROSE, lw=3)
    ax2.plot([p3[0], p4[0]], [p3[1], p4[1]], color=ACCENT_ROSE, lw=3)
    ax2.plot(p3[0], p3[1], 'o', color=ACCENT_ROSE, markersize=10)
    ax2.plot(p4[0], p4[1], 'o', color=ACCENT_ROSE, markersize=12)
    ax2.text(0.45, -0.6, r'$\theta_1 > 0$', color=ACCENT_ROSE, fontsize=10)
    ax2.text(-0.45, -1.8, r'$\theta_2 < 0$', color=ACCENT_ROSE, fontsize=10)
    ax2.text(0, -2.4, r'$\omega_2 = \sqrt{\frac{g}{L}(2 + \sqrt{2})}$' + '\n' + '(Anti-Symmetric Fast Flutter)',
             color=TEXT_LIGHT, fontsize=9.5, ha='center',
             bbox=dict(boxstyle='round,pad=0.4', facecolor='#0f172a', edgecolor=BORDER_COL))

    # General Motion: Beat Phenomenon (Superposition)
    ax3.set_facecolor(CARD_BG)
    t = np.linspace(0, 20, 500)
    w1 = 1.0
    w2 = 2.414
    theta1_t = 0.5 * np.cos(w1 * t) + 0.5 * np.cos(w2 * t)
    envelope = np.cos((w2 - w1) * t / 2)

    ax3.plot(t, theta1_t, color=ACCENT_CYAN, lw=1.8, label=r'Angle $\theta_1(t)$')
    ax3.plot(t, envelope, color='#94a3b8', ls='--', lw=1.2, alpha=0.7, label='Beats Envelope')
    ax3.plot(t, -envelope, color='#94a3b8', ls='--', lw=1.2, alpha=0.7)
    ax3.set_title('Superposition & Beat Oscillations', fontsize=11, fontweight='bold', color=ACCENT_CYAN)
    ax3.set_xlabel('Time $t$ (seconds)', fontsize=9.5)
    ax3.set_ylabel('Deflection Angle $\\theta_1$ (rad)', fontsize=9.5)
    ax3.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)
    ax3.legend(loc='lower right', fontsize=8.2, framealpha=0.8)

    plt.tight_layout()
    out_path = os.path.join(OUTPUT_DIR, 'double_pendulum_normal_modes.png')
    plt.savefig(out_path, dpi=160, bbox_inches='tight')
    plt.close()
    print("[OK] Created double_pendulum_normal_modes.png")

if __name__ == '__main__':
    make_diagram_schematic()
    make_diagram_chaos()
    make_diagram_normal_modes()
    print("All Double Pendulum diagrams generated successfully!")
