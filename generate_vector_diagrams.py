"""
Python4Physics - Generator for Module 1 Vector Visualization Diagrams
Produces 10 high-resolution, professional scientific diagrams for Module 1:
Vectors, Divergence, Curl, Vector Integration, and Curvilinear Coordinates.
"""
import os
import numpy as np
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from matplotlib.patches import FancyArrowPatch, Rectangle, Circle, Arc, Polygon, Wedge
from mpl_toolkits.mplot3d import Axes3D
from mpl_toolkits.mplot3d.art3d import Poly3DCollection

OUTPUT_DIR = os.path.join(os.path.dirname(__file__), 'assets', 'images', 'visualization', 'vectors')
os.makedirs(OUTPUT_DIR, exist_ok=True)

# Custom color palette matching the dark modern lab theme
BG_DARK = '#0f172a'      # Deep slate background
CARD_BG = '#1e293b'      # Card background
BORDER_COL = '#334155'   # Subtle border
TEXT_LIGHT = '#f8fafc'   # White/Light slate text
TEXT_MUTED = '#94a3b8'   # Muted gray text
ACCENT_CYAN = '#38bdf8'  # Cyan accent
ACCENT_EMERALD = '#10b981' # Emerald green
ACCENT_AMBER = '#f59e0b' # Amber/gold
ACCENT_ROSE = '#f43f5e'  # Rose/coral
ACCENT_PURPLE = '#a855f7' # Violet/purple
ACCENT_BLUE = '#3b82f6'  # Royal blue

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

print(f"Generating diagram images into {OUTPUT_DIR}...")

# -----------------------------------------------------------------------------
# DIAGRAM 1: Vector Basics & Algebra (Addition, Dot & Cross Product)
# -----------------------------------------------------------------------------
def make_diagram_1():
    fig = plt.figure(figsize=(13.5, 4.8), dpi=150)
    fig.patch.set_facecolor(BG_DARK)

    # 1. Vector Addition & Parallelogram
    ax1 = fig.add_subplot(1, 3, 1)
    ax1.set_facecolor(CARD_BG)
    ax1.set_xlim(-0.5, 5.5)
    ax1.set_ylim(-0.5, 4.5)
    ax1.set_aspect('equal')
    ax1.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)

    # Vectors A and B
    A = np.array([3.2, 0.8])
    B = np.array([1.2, 2.8])
    R = A + B

    # Parallelogram fill
    poly = Polygon([[0, 0], A, R, B], closed=True, facecolor=ACCENT_CYAN, alpha=0.08, edgecolor=ACCENT_CYAN, linestyle='--')
    ax1.add_patch(poly)

    # Arrow A
    ax1.annotate('', xy=A, xytext=(0, 0), arrowprops=dict(arrowstyle="->,head_width=0.35,head_length=0.5", color=ACCENT_CYAN, lw=2.5))
    ax1.text(A[0]/2 - 0.1, A[1]/2 - 0.35, r'$\vec{A}$', color=ACCENT_CYAN, fontsize=13, fontweight='bold')

    # Arrow B
    ax1.annotate('', xy=B, xytext=(0, 0), arrowprops=dict(arrowstyle="->,head_width=0.35,head_length=0.5", color=ACCENT_AMBER, lw=2.5))
    ax1.text(B[0]/2 - 0.35, B[1]/2 + 0.1, r'$\vec{B}$', color=ACCENT_AMBER, fontsize=13, fontweight='bold')

    # Arrow R = A + B
    ax1.annotate('', xy=R, xytext=(0, 0), arrowprops=dict(arrowstyle="->,head_width=0.4,head_length=0.6", color=ACCENT_EMERALD, lw=3.0))
    ax1.text(R[0]/2 + 0.1, R[1]/2 + 0.25, r'$\vec{R} = \vec{A} + \vec{B}$', color=ACCENT_EMERALD, fontsize=12, fontweight='bold')

    # Difference A - B
    ax1.annotate('', xy=A, xytext=B, arrowprops=dict(arrowstyle="->,head_width=0.3,head_length=0.45", color=ACCENT_ROSE, lw=1.8, linestyle=':'))
    ax1.text((A[0]+B[0])/2 + 0.1, (A[1]+B[1])/2 + 0.1, r'$\vec{A} - \vec{B}$', color=ACCENT_ROSE, fontsize=10)

    ax1.set_title("Vector Addition & Subtraction", color=TEXT_LIGHT, fontsize=12, pad=10, fontweight='bold')
    ax1.set_xlabel("X component", fontsize=9)
    ax1.set_ylabel("Y component", fontsize=9)

    # 2. Dot Product & Orthogonal Projection
    ax2 = fig.add_subplot(1, 3, 2)
    ax2.set_facecolor(CARD_BG)
    ax2.set_xlim(-0.5, 5.0)
    ax2.set_ylim(-0.5, 4.0)
    ax2.set_aspect('equal')
    ax2.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)

    vB = np.array([4.0, 0.0])
    vA = np.array([2.5, 2.6])
    proj_len = 2.5
    vProj = np.array([proj_len, 0.0])

    ax2.annotate('', xy=vB, xytext=(0, 0), arrowprops=dict(arrowstyle="->,head_width=0.35,head_length=0.5", color=ACCENT_AMBER, lw=2.5))
    ax2.text(3.6, -0.35, r'$\vec{B}$', color=ACCENT_AMBER, fontsize=13, fontweight='bold')

    ax2.annotate('', xy=vA, xytext=(0, 0), arrowprops=dict(arrowstyle="->,head_width=0.35,head_length=0.5", color=ACCENT_CYAN, lw=2.5))
    ax2.text(1.1, 1.6, r'$\vec{A}$', color=ACCENT_CYAN, fontsize=13, fontweight='bold')

    # Dropped perpendicular
    ax2.plot([vA[0], vProj[0]], [vA[1], vProj[1]], color=TEXT_MUTED, linestyle='--', lw=1.5)
    # Right-angle indicator
    ax2.plot([vProj[0]-0.25, vProj[0]-0.25, vProj[0]], [0, 0.25, 0.25], color=TEXT_MUTED, lw=1)

    # Projection segment
    ax2.annotate('', xy=vProj, xytext=(0, 0), arrowprops=dict(arrowstyle="->,head_width=0.3,head_length=0.45", color=ACCENT_PURPLE, lw=3.2))
    ax2.text(0.4, -0.38, r'$\mathrm{proj}_{\vec{B}}\vec{A} = (A\cos\theta)\hat{b}$', color=ACCENT_PURPLE, fontsize=10, fontweight='bold')

    # Angle arc
    arc = Arc((0, 0), 1.2, 1.2, angle=0, theta1=0, theta2=np.degrees(np.arctan2(vA[1], vA[0])), color=ACCENT_AMBER, lw=1.5)
    ax2.add_patch(arc)
    ax2.text(0.7, 0.25, r'$\theta$', color=ACCENT_AMBER, fontsize=11)

    ax2.text(1.2, 3.4, r'$\vec{A}\cdot\vec{B} = |\vec{A}||\vec{B}|\cos\theta$', color=ACCENT_CYAN, fontsize=11,
             bbox=dict(boxstyle='round,pad=0.3', facecolor=BG_DARK, edgecolor=BORDER_COL))

    ax2.set_title("Dot Product & Projection", color=TEXT_LIGHT, fontsize=12, pad=10, fontweight='bold')
    ax2.set_xlabel("X component", fontsize=9)
    ax2.set_ylabel("Y component", fontsize=9)

    # 3. 3D Cross Product & Area of Parallelogram
    ax3 = fig.add_subplot(1, 3, 3, projection='3d')
    ax3.set_facecolor(CARD_BG)

    # Vectors
    uA = np.array([2.5, 0.5, 0.0])
    uB = np.array([0.5, 2.5, 0.0])
    uC = np.cross(uA, uB) # (0, 0, 6)
    scale_C = 0.4
    uC_plot = uC * scale_C

    # Parallelogram in xy-plane
    X_p = [0, uA[0], uA[0]+uB[0], uB[0]]
    Y_p = [0, uA[1], uA[1]+uB[1], uB[1]]
    Z_p = [0, uA[2], uA[2]+uB[2], uB[2]]
    verts = [list(zip(X_p, Y_p, Z_p))]
    poly3 = Poly3DCollection(verts, alpha=0.25, facecolor=ACCENT_EMERALD, edgecolor=ACCENT_EMERALD, linestyle='--')
    ax3.add_collection3d(poly3)

    # Vector A
    ax3.quiver(0, 0, 0, uA[0], uA[1], uA[2], color=ACCENT_CYAN, lw=2.5, arrow_length_ratio=0.15)
    ax3.text(uA[0], uA[1], uA[2]-0.2, r' $\vec{A}$', color=ACCENT_CYAN, fontsize=12, fontweight='bold')

    # Vector B
    ax3.quiver(0, 0, 0, uB[0], uB[1], uB[2], color=ACCENT_AMBER, lw=2.5, arrow_length_ratio=0.15)
    ax3.text(uB[0], uB[1], uB[2]-0.2, r' $\vec{B}$', color=ACCENT_AMBER, fontsize=12, fontweight='bold')

    # Vector C = A x B
    ax3.quiver(0, 0, 0, uC_plot[0], uC_plot[1], uC_plot[2], color=ACCENT_ROSE, lw=3.0, arrow_length_ratio=0.15)
    ax3.text(uC_plot[0], uC_plot[1], uC_plot[2]+0.2, r'$\vec{C} = \vec{A}\times\vec{B}$', color=ACCENT_ROSE, fontsize=12, fontweight='bold')

    # Text inside parallelogram
    ax3.text((uA[0]+uB[0])/2 - 0.5, (uA[1]+uB[1])/2, 0.1, r'$\mathrm{Area} = |\vec{A}\times\vec{B}|$', color=ACCENT_EMERALD, fontsize=9)

    ax3.set_xlim(-0.5, 3.5)
    ax3.set_ylim(-0.5, 3.5)
    ax3.set_zlim(0, 3.2)
    ax3.set_title("Cross Product (Right-Hand Rule)", color=TEXT_LIGHT, fontsize=12, pad=10, fontweight='bold')
    ax3.set_xlabel("X", fontsize=8)
    ax3.set_ylabel("Y", fontsize=8)
    ax3.set_zlabel("Z", fontsize=8)
    ax3.view_init(elev=26, azim=-55)

    plt.tight_layout()
    plt.savefig(os.path.join(OUTPUT_DIR, 'vector_basics_algebra.png'), dpi=160, bbox_inches='tight')
    plt.close()
    print("[OK] Created vector_basics_algebra.png")

make_diagram_1()

# -----------------------------------------------------------------------------
# DIAGRAM 2: Vector Physics Problem (Torque & Work Done)
# -----------------------------------------------------------------------------
def make_diagram_2():
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11.5, 4.8), dpi=150)
    fig.patch.set_facecolor(BG_DARK)

    # 1. Torque on a Lever Arm / Rigid Body
    ax1.set_facecolor(CARD_BG)
    ax1.set_xlim(-0.5, 4.5)
    ax1.set_ylim(-0.5, 4.0)
    ax1.set_aspect('equal')
    ax1.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)

    r = np.array([2.8, 1.2])
    theta_r = np.arctan2(r[1], r[0])
    # Lever arm
    ax1.plot([0, r[0]], [0, r[1]], color=ACCENT_AMBER, lw=3.5, label='Lever Arm $\\vec{r}$')
    ax1.scatter([0], [0], color=TEXT_LIGHT, s=90, zorder=5)
    ax1.text(-0.35, -0.35, "Pivot $O$", color=TEXT_LIGHT, fontsize=10, fontweight='bold')

    # Force vector F at r
    F = np.array([-0.6, 2.0])
    ax1.annotate('', xy=r + F, xytext=r, arrowprops=dict(arrowstyle="->,head_width=0.35,head_length=0.5", color=ACCENT_ROSE, lw=2.5))
    ax1.text(r[0] + F[0] + 0.1, r[1] + F[1] + 0.1, r'$\vec{F}$', color=ACCENT_ROSE, fontsize=13, fontweight='bold')

    # Decomposition of Force into F_perp and F_parallel
    r_hat = r / np.linalg.norm(r)
    r_perp = np.array([-r_hat[1], r_hat[0]])
    F_par = np.dot(F, r_hat) * r_hat
    F_perp = np.dot(F, r_perp) * r_perp

    ax1.plot([r[0], r[0] + F_par[0]], [r[1], r[1] + F_par[1]], color=ACCENT_CYAN, linestyle='--', lw=1.5, label='$F_\\parallel$ (Radial - no torque)')
    ax1.plot([r[0], r[0] + F_perp[0]], [r[1], r[1] + F_perp[1]], color=ACCENT_EMERALD, lw=2.0, label='$F_\\perp$ (Tangential - produces torque)')
    ax1.plot([r[0]+F_par[0], r[0]+F[0]], [r[1]+F_par[1], r[1]+F[1]], color=TEXT_MUTED, linestyle=':', lw=1)

    # Torque symbol (out of page)
    ax1.scatter([1.0], [2.6], color=ACCENT_PURPLE, s=180, edgecolors=TEXT_LIGHT, lw=1.5, zorder=6)
    ax1.scatter([1.0], [2.6], color=TEXT_LIGHT, s=25, zorder=7)
    ax1.text(1.25, 2.55, r'$\vec{\tau} = \vec{r}\times\vec{F} = (r F\sin\phi)\hat{k}$', color=ACCENT_PURPLE, fontsize=11, fontweight='bold')

    ax1.set_title("Mechanical Torque Vector: $\\vec{\\tau} = \\vec{r} \\times \\vec{F}$", color=TEXT_LIGHT, fontsize=11.5, pad=10, fontweight='bold')
    ax1.legend(loc='lower right', fontsize=8.5, facecolor=BG_DARK, edgecolor=BORDER_COL)

    # 2. Magnetic Dipole Torque & Potential Energy
    ax2.set_facecolor(CARD_BG)
    ax2.set_xlim(-2.5, 2.5)
    ax2.set_ylim(-2.0, 2.0)
    ax2.set_aspect('equal')
    ax2.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)

    # Uniform B field lines
    for y in np.linspace(-1.5, 1.5, 5):
        ax2.annotate('', xy=(2.2, y), xytext=(-2.2, y), arrowprops=dict(arrowstyle="->,head_width=0.25,head_length=0.35", color=ACCENT_BLUE, alpha=0.4, lw=1.5))
    ax2.text(1.7, 1.65, r'$\vec{B}$ Field', color=ACCENT_BLUE, fontsize=11, fontweight='bold')

    # Magnetic dipole moment m at angle theta = 45 deg
    theta_m = np.radians(48)
    L_m = 1.3
    mx = L_m * np.cos(theta_m)
    my = L_m * np.sin(theta_m)

    # Dipole body
    ax2.plot([-mx, mx], [-my, my], color='#e2e8f0', lw=5.0, solid_capstyle='round')
    ax2.scatter([-mx], [-my], color=ACCENT_BLUE, s=120, zorder=5, label='South Pole')
    ax2.scatter([mx], [my], color=ACCENT_ROSE, s=120, zorder=5, label='North Pole')

    # Vector m
    ax2.annotate('', xy=(mx*1.4, my*1.4), xytext=(0, 0), arrowprops=dict(arrowstyle="->,head_width=0.35,head_length=0.5", color=ACCENT_AMBER, lw=2.5))
    ax2.text(mx*1.4 + 0.1, my*1.4, r'$\vec{m}$', color=ACCENT_AMBER, fontsize=13, fontweight='bold')

    # Restoring torque rotation arc
    arc_t = Arc((0, 0), 1.6, 1.6, angle=0, theta1=0, theta2=48, color=ACCENT_EMERALD, lw=1.8)
    ax2.add_patch(arc_t)
    ax2.text(0.9, 0.25, r'$\theta$', color=ACCENT_EMERALD, fontsize=11)

    # Curved arrow for restoring torque
    ax2.annotate('', xy=(0.8*np.cos(0.1), 0.8*np.sin(0.1)), xytext=(0.8*np.cos(theta_m), 0.8*np.sin(theta_m)),
                 arrowprops=dict(arrowstyle="->,head_width=0.3,head_length=0.4", color=ACCENT_PURPLE, lw=2.0, connectionstyle="arc3,rad=-0.3"))
    ax2.text(-2.3, -1.65, r'$\vec{\tau} = \vec{m}\times\vec{B}, \quad U = -\vec{m}\cdot\vec{B}$', color=TEXT_LIGHT, fontsize=10.5,
             bbox=dict(boxstyle='round,pad=0.35', facecolor=BG_DARK, edgecolor=BORDER_COL))

    ax2.set_title("Magnetic Dipole in Uniform Field", color=TEXT_LIGHT, fontsize=11.5, pad=10, fontweight='bold')
    ax2.legend(loc='upper left', fontsize=8.5, facecolor=BG_DARK, edgecolor=BORDER_COL)

    plt.tight_layout()
    plt.savefig(os.path.join(OUTPUT_DIR, 'vector_problem_torque_work.png'), dpi=160, bbox_inches='tight')
    plt.close()
    print("[OK] Created vector_problem_torque_work.png")

make_diagram_2()

# -----------------------------------------------------------------------------
# DIAGRAM 3: Divergence Concept & Net Flux
# -----------------------------------------------------------------------------
def make_diagram_3():
    fig = plt.figure(figsize=(13.5, 4.6), dpi=150)
    fig.patch.set_facecolor(BG_DARK)

    # 1. Source (Positive Divergence)
    ax1 = fig.add_subplot(1, 3, 1)
    ax1.set_facecolor(CARD_BG)
    x = np.linspace(-2, 2, 9)
    y = np.linspace(-2, 2, 9)
    X, Y = np.meshgrid(x, y)
    R = np.sqrt(X**2 + Y**2) + 0.1
    # Pure source field F = (x, y)
    Fx, Fy = X / R, Y / R
    ax1.quiver(X, Y, Fx, Fy, color=ACCENT_ROSE, scale=14, width=0.007)
    circle1 = Circle((0, 0), 1.2, fill=False, edgecolor=ACCENT_AMBER, linestyle='--', lw=2)
    ax1.add_patch(circle1)
    ax1.scatter([0], [0], color=ACCENT_ROSE, s=100, zorder=5)
    ax1.text(0, -1.75, r'$\nabla \cdot \vec{F} > 0$ (Source)', color=ACCENT_ROSE, fontsize=11, fontweight='bold', ha='center',
             bbox=dict(boxstyle='round,pad=0.25', facecolor=BG_DARK, edgecolor=BORDER_COL))
    ax1.text(0, 1.4, r'Net Outward Flux $> 0$', color=ACCENT_AMBER, fontsize=9.5, ha='center')
    ax1.set_xlim(-2.2, 2.2); ax1.set_ylim(-2.2, 2.2); ax1.set_aspect('equal')
    ax1.set_title("Positive Divergence (Source)", color=TEXT_LIGHT, fontsize=11.5, pad=8, fontweight='bold')

    # 2. Sink (Negative Divergence)
    ax2 = fig.add_subplot(1, 3, 2)
    ax2.set_facecolor(CARD_BG)
    # Pure sink field F = (-x, -y)
    Fx, Fy = -X / R, -Y / R
    ax2.quiver(X, Y, Fx, Fy, color=ACCENT_CYAN, scale=14, width=0.007)
    circle2 = Circle((0, 0), 1.2, fill=False, edgecolor=ACCENT_AMBER, linestyle='--', lw=2)
    ax2.add_patch(circle2)
    ax2.scatter([0], [0], color=ACCENT_CYAN, s=100, zorder=5)
    ax2.text(0, -1.75, r'$\nabla \cdot \vec{F} < 0$ (Sink)', color=ACCENT_CYAN, fontsize=11, fontweight='bold', ha='center',
             bbox=dict(boxstyle='round,pad=0.25', facecolor=BG_DARK, edgecolor=BORDER_COL))
    ax2.text(0, 1.4, r'Net Inward Flux $< 0$', color=ACCENT_AMBER, fontsize=9.5, ha='center')
    ax2.set_xlim(-2.2, 2.2); ax2.set_ylim(-2.2, 2.2); ax2.set_aspect('equal')
    ax2.set_title("Negative Divergence (Sink)", color=TEXT_LIGHT, fontsize=11.5, pad=8, fontweight='bold')

    # 3. Solenoidal Field (Zero Divergence)
    ax3 = fig.add_subplot(1, 3, 3)
    ax3.set_facecolor(CARD_BG)
    # Circular or shear field F = (-y, x) -> div = 0
    Fx, Fy = -Y, X
    ax3.quiver(X, Y, Fx, Fy, color=ACCENT_EMERALD, scale=25, width=0.007)
    circle3 = Circle((0, 0), 1.2, fill=False, edgecolor=ACCENT_AMBER, linestyle='--', lw=2)
    ax3.add_patch(circle3)
    ax3.text(0, -1.75, r'$\nabla \cdot \vec{F} = 0$ (Solenoidal)', color=ACCENT_EMERALD, fontsize=11, fontweight='bold', ha='center',
             bbox=dict(boxstyle='round,pad=0.25', facecolor=BG_DARK, edgecolor=BORDER_COL))
    ax3.text(0, 1.4, r'Inflow = Outflow (Incompressible)', color=ACCENT_AMBER, fontsize=9.5, ha='center')
    ax3.set_xlim(-2.2, 2.2); ax3.set_ylim(-2.2, 2.2); ax3.set_aspect('equal')
    ax3.set_title("Zero Divergence (Solenoidal)", color=TEXT_LIGHT, fontsize=11.5, pad=8, fontweight='bold')

    plt.tight_layout()
    plt.savefig(os.path.join(OUTPUT_DIR, 'divergence_flux_concept.png'), dpi=160, bbox_inches='tight')
    plt.close()
    print("[OK] Created divergence_flux_concept.png")

make_diagram_3()

# -----------------------------------------------------------------------------
# DIAGRAM 4: Divergence Problem (Gauss's Law for Non-uniform Charge)
# -----------------------------------------------------------------------------
def make_diagram_4():
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11.5, 4.8), dpi=150)
    fig.patch.set_facecolor(BG_DARK)

    # 1. 2D Cross Section of Charge Sphere
    ax1.set_facecolor(CARD_BG)
    ax1.set_xlim(-2.5, 2.5); ax1.set_ylim(-2.5, 2.5); ax1.set_aspect('equal')
    ax1.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)

    # Color mesh for non-uniform charge density rho(r) = rho0 * (1 - r^2/R^2)
    r_grid = np.linspace(-1.5, 1.5, 120)
    Xg, Yg = np.meshgrid(r_grid, r_grid)
    Rg = np.sqrt(Xg**2 + Yg**2)
    Rho = np.where(Rg <= 1.5, 1.0 - (Rg/1.5)**2, 0.0)
    c = ax1.imshow(Rho, extent=[-1.5, 1.5, -1.5, 1.5], origin='lower', cmap='plasma', alpha=0.6)

    # Boundary of sphere R
    sphere_bound = Circle((0, 0), 1.5, fill=False, edgecolor='#f43f5e', lw=2.5, linestyle='-', label='Sphere Radius $R$')
    ax1.add_patch(sphere_bound)

    # Gaussian surface inside r < R
    gauss_in = Circle((0, 0), 0.9, fill=False, edgecolor=ACCENT_CYAN, lw=2.0, linestyle='--', label='Gaussian Surface ($r < R$)')
    ax1.add_patch(gauss_in)

    # Radiating Electric field vectors
    theta_pts = np.linspace(0, 2*np.pi, 16, endpoint=False)
    for t in theta_pts:
        # inside vector
        ax1.annotate('', xy=(1.3*np.cos(t), 1.3*np.sin(t)), xytext=(0.9*np.cos(t), 0.9*np.sin(t)),
                     arrowprops=dict(arrowstyle="->,head_width=0.25,head_length=0.35", color=ACCENT_CYAN, lw=1.6))
        # outside vector
        ax1.annotate('', xy=(2.3*np.cos(t), 2.3*np.sin(t)), xytext=(1.7*np.cos(t), 1.7*np.sin(t)),
                     arrowprops=dict(arrowstyle="->,head_width=0.25,head_length=0.35", color=ACCENT_AMBER, lw=1.6))

    ax1.text(0, -2.25, r'$\nabla\cdot\vec{E} = \rho(r)/\epsilon_0$', color=TEXT_LIGHT, fontsize=11, fontweight='bold', ha='center',
             bbox=dict(boxstyle='round,pad=0.25', facecolor=BG_DARK, edgecolor=BORDER_COL))
    ax1.legend(loc='upper right', fontsize=8.2, facecolor=BG_DARK, edgecolor=BORDER_COL)
    ax1.set_title("Non-Uniform Charge Cloud & Gaussian Sphere", color=TEXT_LIGHT, fontsize=11, pad=8, fontweight='bold')

    # 2. Electric Field & Divergence Profiles vs r/R
    ax2.set_facecolor(CARD_BG)
    r_arr = np.linspace(0.001, 2.5, 300)
    R_val = 1.0
    # Inside: E(r) = (rho0 * r / eps0) * (1/3 - r^2 / (5*R^2))
    # Outside: E(r) = Q_total / (4 pi eps0 r^2)
    rho_arr = np.where(r_arr <= R_val, (1 - (r_arr/R_val)**2), 0.0)
    E_inside = (r_arr/3.0 - (r_arr**3)/(5.0 * R_val**2))
    Q_tot = (1.0/3.0 - 1.0/5.0) # at r = R
    E_outside = Q_tot * (R_val**2) / (r_arr**2)
    E_arr = np.where(r_arr <= R_val, E_inside, E_outside)
    # Normalize for clean comparison
    E_norm = E_arr / np.max(E_arr)

    ax2.plot(r_arr, rho_arr, color=ACCENT_ROSE, lw=2.2, label=r'Charge Density $\rho(r)/\rho_0 = 1 - (r/R)^2$')
    ax2.plot(r_arr, E_norm, color=ACCENT_CYAN, lw=2.5, label=r'Electric Field $E(r) / E_{\max}$')
    ax2.axvline(1.0, color='#94a3b8', linestyle='--', lw=1.5, alpha=0.8, label='Cloud Boundary $r = R$')

    ax2.set_xlim(0, 2.5); ax2.set_ylim(-0.05, 1.15)
    ax2.set_xlabel('Normalized Radial Distance $r / R$', fontsize=9.5)
    ax2.set_ylabel('Normalized Amplitude', fontsize=9.5)
    ax2.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)
    ax2.legend(loc='upper right', fontsize=8.5, facecolor=BG_DARK, edgecolor=BORDER_COL)
    ax2.set_title("Gauss's Law: Radial Profiles of $\\rho(r)$ and $E(r)$", color=TEXT_LIGHT, fontsize=11, pad=8, fontweight='bold')

    plt.tight_layout()
    plt.savefig(os.path.join(OUTPUT_DIR, 'divergence_problem_gauss.png'), dpi=160, bbox_inches='tight')
    plt.close()
    print("[OK] Created divergence_problem_gauss.png")

make_diagram_4()

# -----------------------------------------------------------------------------
# DIAGRAM 5: Curl Concept & Circulation (Rotational vs Irrotational)
# -----------------------------------------------------------------------------
def make_diagram_5():
    fig = plt.figure(figsize=(13.5, 4.6), dpi=150)
    fig.patch.set_facecolor(BG_DARK)

    # 1. Microscopic Paddle Wheel / Circulation Loop
    ax1 = fig.add_subplot(1, 3, 1)
    ax1.set_facecolor(CARD_BG)
    ax1.set_xlim(-1.5, 1.5); ax1.set_ylim(-1.5, 1.5); ax1.set_aspect('equal')
    ax1.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)

    # Closed contour C
    rect = Rectangle((-0.8, -0.8), 1.6, 1.6, fill=False, edgecolor=ACCENT_AMBER, lw=2.5, linestyle='-')
    ax1.add_patch(rect)
    # Arrows on perimeter
    ax1.annotate('', xy=(0.2, -0.8), xytext=(-0.2, -0.8), arrowprops=dict(arrowstyle="->,head_width=0.3,head_length=0.4", color=ACCENT_AMBER, lw=2.5))
    ax1.annotate('', xy=(0.8, 0.2), xytext=(0.8, -0.2), arrowprops=dict(arrowstyle="->,head_width=0.3,head_length=0.4", color=ACCENT_AMBER, lw=2.5))
    ax1.annotate('', xy=(-0.2, 0.8), xytext=(0.2, 0.8), arrowprops=dict(arrowstyle="->,head_width=0.3,head_length=0.4", color=ACCENT_AMBER, lw=2.5))
    ax1.annotate('', xy=(-0.8, -0.2), xytext=(-0.8, 0.2), arrowprops=dict(arrowstyle="->,head_width=0.3,head_length=0.4", color=ACCENT_AMBER, lw=2.5))

    # Center paddle wheel
    circle = Circle((0, 0), 0.25, facecolor=ACCENT_PURPLE, edgecolor=TEXT_LIGHT, lw=1.5, zorder=5)
    ax1.add_patch(circle)
    for angle in [0, 90, 180, 270]:
        rad = np.radians(angle)
        ax1.plot([0, 0.55*np.cos(rad)], [0, 0.55*np.sin(rad)], color=ACCENT_ROSE, lw=3.0, zorder=6)

    # Rotation arrow
    ax1.annotate('', xy=(-0.25, 0.45), xytext=(0.25, 0.45),
                 arrowprops=dict(arrowstyle="->,head_width=0.25,head_length=0.35", color=ACCENT_CYAN, lw=2.0, connectionstyle="arc3,rad=-0.4"))

    ax1.text(0, -1.25, r'$\oint_C \vec{F}\cdot d\vec{r} = (\nabla\times\vec{F})_z \Delta x\Delta y$', color=ACCENT_AMBER, fontsize=9.5, ha='center',
             bbox=dict(boxstyle='round,pad=0.25', facecolor=BG_DARK, edgecolor=BORDER_COL))
    ax1.set_title("Paddle Wheel & Infinitesimal Circulation", color=TEXT_LIGHT, fontsize=11, pad=8, fontweight='bold')

    # 2. Solid Body Rotation (Non-zero Curl)
    ax2 = fig.add_subplot(1, 3, 2)
    ax2.set_facecolor(CARD_BG)
    x = np.linspace(-2, 2, 9)
    y = np.linspace(-2, 2, 9)
    X, Y = np.meshgrid(x, y)
    # v = omega * (-y, x) -> curl v = 2 * omega k
    Vx, Vy = -Y, X
    ax2.quiver(X, Y, Vx, Vy, color=ACCENT_ROSE, scale=22, width=0.007)
    ax2.set_xlim(-2.2, 2.2); ax2.set_ylim(-2.2, 2.2); ax2.set_aspect('equal')
    ax2.text(0, -1.8, r'$\vec{v} = \vec{\omega}\times\vec{r} \Rightarrow \nabla\times\vec{v} = 2\vec{\omega} \neq 0$', color=ACCENT_ROSE, fontsize=9.5, ha='center',
             bbox=dict(boxstyle='round,pad=0.25', facecolor=BG_DARK, edgecolor=BORDER_COL))
    ax2.set_title("Rigid Body Rotation (Non-Zero Curl)", color=TEXT_LIGHT, fontsize=11, pad=8, fontweight='bold')

    # 3. Free Vortex (Zero Curl everywhere except origin)
    ax3 = fig.add_subplot(1, 3, 3)
    ax3.set_facecolor(CARD_BG)
    R2 = X**2 + Y**2 + 0.15
    # v = (-y/r^2, x/r^2) -> curl = 0
    Vx3, Vy3 = -Y / R2, X / R2
    ax3.quiver(X, Y, Vx3, Vy3, color=ACCENT_EMERALD, scale=12, width=0.007)
    ax3.scatter([0], [0], color=ACCENT_ROSE, s=80, zorder=5) # singular point
    ax3.set_xlim(-2.2, 2.2); ax3.set_ylim(-2.2, 2.2); ax3.set_aspect('equal')
    ax3.text(0, -1.8, r'$\vec{v} \propto \frac{1}{r}\hat{\theta} \Rightarrow \nabla\times\vec{v} = 0 \ (r > 0)$', color=ACCENT_EMERALD, fontsize=9.5, ha='center',
             bbox=dict(boxstyle='round,pad=0.25', facecolor=BG_DARK, edgecolor=BORDER_COL))
    ax3.set_title("Irrotational Vortex (Zero Curl)", color=TEXT_LIGHT, fontsize=11, pad=8, fontweight='bold')

    plt.tight_layout()
    plt.savefig(os.path.join(OUTPUT_DIR, 'curl_vorticity_concept.png'), dpi=160, bbox_inches='tight')
    plt.close()
    print("[OK] Created curl_vorticity_concept.png")

make_diagram_5()

# -----------------------------------------------------------------------------
# DIAGRAM 6: Curl Problem (Ampere's Law in Current-Carrying Wire)
# -----------------------------------------------------------------------------
def make_diagram_6():
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11.5, 4.8), dpi=150)
    fig.patch.set_facecolor(BG_DARK)

    # 1. Wire Cross Section & Concentric B-Field Lines
    ax1.set_facecolor(CARD_BG)
    ax1.set_xlim(-2.4, 2.4); ax1.set_ylim(-2.4, 2.4); ax1.set_aspect('equal')
    ax1.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)

    # Wire circle of radius R = 1.2
    wire = Circle((0, 0), 1.2, facecolor=ACCENT_CYAN, alpha=0.15, edgecolor=ACCENT_CYAN, lw=2.5, label='Conductor Boundary ($R$)')
    ax1.add_patch(wire)

    # Current density arrows (out of page dots)
    for rad_i in [0.3, 0.6, 0.9]:
        for ang in np.linspace(0, 2*np.pi, int(rad_i*14), endpoint=False):
            ax1.scatter([rad_i*np.cos(ang)], [rad_i*np.sin(ang)], color=ACCENT_ROSE, s=35, zorder=4)
    ax1.scatter([0], [0], color=ACCENT_ROSE, s=45, zorder=4, label='Current Density $\\vec{J}$ (Out of page)')

    # B-field loops
    for r_b in [0.7, 1.5, 2.0]:
        b_loop = Circle((0, 0), r_b, fill=False, edgecolor=ACCENT_AMBER, lw=1.8, linestyle='--')
        ax1.add_patch(b_loop)
        # Add arrow on loop
        ax1.annotate('', xy=(-0.05, r_b), xytext=(0.05, r_b),
                     arrowprops=dict(arrowstyle="->,head_width=0.25,head_length=0.35", color=ACCENT_AMBER, lw=2.0))

    ax1.text(0, -2.15, r'$\nabla\times\vec{B} = \mu_0 \vec{J}$ (Ampere\'s Law)', color=TEXT_LIGHT, fontsize=10.5, fontweight='bold', ha='center',
             bbox=dict(boxstyle='round,pad=0.25', facecolor=BG_DARK, edgecolor=BORDER_COL))
    ax1.legend(loc='upper right', fontsize=8.2, facecolor=BG_DARK, edgecolor=BORDER_COL)
    ax1.set_title("Cylindrical Conductor with Current $\\vec{J}$ and $\\vec{B}$", color=TEXT_LIGHT, fontsize=11, pad=8, fontweight='bold')

    # 2. Radial Profiles of B(r) and Curl(B)(r)
    ax2.set_facecolor(CARD_BG)
    r = np.linspace(0.001, 2.5, 300)
    R_w = 1.0
    # J(r) = J0 * (r/R)^n with n = 1
    # Inside: B(r) proportional to r^(n+1) = r^2
    # Outside: B(r) proportional to 1/r
    B_in = (r/R_w)**2
    B_out = 1.0 / (r/R_w)
    B_profile = np.where(r <= R_w, B_in, B_out)
    Curl_profile = np.where(r <= R_w, (r/R_w), 0.0) # proportional to J(r)

    ax2.plot(r, B_profile / np.max(B_profile), color=ACCENT_AMBER, lw=2.5, label='Magnetic Field $B(r) / B_{\\max}$')
    ax2.plot(r, Curl_profile, color=ACCENT_ROSE, lw=2.2, label='Curl $(\\nabla\\times\\vec{B})_z = \\mu_0 J(r)$')
    ax2.axvline(1.0, color='#94a3b8', linestyle='--', lw=1.5, alpha=0.8, label='Wire Surface $r = R$')

    ax2.set_xlim(0, 2.5); ax2.set_ylim(-0.05, 1.15)
    ax2.set_xlabel('Normalized Radius $r / R$', fontsize=9.5)
    ax2.set_ylabel('Normalized Amplitude', fontsize=9.5)
    ax2.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)
    ax2.legend(loc='upper right', fontsize=8.5, facecolor=BG_DARK, edgecolor=BORDER_COL)
    ax2.set_title("Profiles of $B(r)$ and $\\nabla\\times\\vec{B} = \\mu_0 J(r)$", color=TEXT_LIGHT, fontsize=11, pad=8, fontweight='bold')

    plt.tight_layout()
    plt.savefig(os.path.join(OUTPUT_DIR, 'curl_problem_ampere.png'), dpi=160, bbox_inches='tight')
    plt.close()
    print("[OK] Created curl_problem_ampere.png")

make_diagram_6()

# -----------------------------------------------------------------------------
# DIAGRAM 7: Vector Integration Theorems (Gauss Divergence & Stokes)
# -----------------------------------------------------------------------------
def make_diagram_7():
    fig = plt.figure(figsize=(12.0, 5.0), dpi=150)
    fig.patch.set_facecolor(BG_DARK)

    # 1. 3D Gauss Divergence Theorem
    ax1 = fig.add_subplot(1, 2, 1, projection='3d')
    ax1.set_facecolor(CARD_BG)

    # Closed ellipsoid volume V
    u = np.linspace(0, 2 * np.pi, 25)
    v = np.linspace(0, np.pi, 20)
    x = 1.8 * np.outer(np.cos(u), np.sin(v))
    y = 1.3 * np.outer(np.sin(u), np.sin(v))
    z = 1.1 * np.outer(np.ones(np.size(u)), np.cos(v))

    ax1.plot_surface(x, y, z, color=ACCENT_CYAN, alpha=0.25, edgecolor=ACCENT_CYAN, lw=0.4)

    # Outward normal vectors on surface S
    for phi_i in [0.5, 2.0, 3.8, 5.2]:
        for th_i in [0.8, 1.6, 2.4]:
            px = 1.8 * np.cos(phi_i) * np.sin(th_i)
            py = 1.3 * np.sin(phi_i) * np.sin(th_i)
            pz = 1.1 * np.cos(th_i)
            norm = np.array([px/1.8**2, py/1.3**2, pz/1.1**2])
            norm = norm / np.linalg.norm(norm) * 0.65
            ax1.quiver(px, py, pz, norm[0], norm[1], norm[2], color=ACCENT_AMBER, lw=2.0, arrow_length_ratio=0.35)

    ax1.text(0, 0, 0, r'$\iiint_V (\nabla\cdot\vec{F})dV$', color=TEXT_LIGHT, fontsize=11, fontweight='bold', ha='center')
    ax1.text(0, 0, 1.6, r'$\oiint_S \vec{F}\cdot d\vec{S}$', color=ACCENT_AMBER, fontsize=12, fontweight='bold', ha='center')

    ax1.set_xlim(-2.2, 2.2); ax1.set_ylim(-2.2, 2.2); ax1.set_zlim(-1.5, 1.8)
    ax1.set_title("Gauss's Divergence Theorem\n$\\iiint_V (\\nabla\\cdot\\vec{F})dV = \\oiint_S \\vec{F}\\cdot d\\vec{S}$", color=TEXT_LIGHT, fontsize=11, pad=12, fontweight='bold')
    ax1.view_init(elev=24, azim=45)

    # 2. 3D Stokes' Circulation Theorem
    ax2 = fig.add_subplot(1, 2, 2, projection='3d')
    ax2.set_facecolor(CARD_BG)

    # Open bowl / paraboloid surface S
    r_s = np.linspace(0, 1.8, 20)
    theta_s = np.linspace(0, 2*np.pi, 30)
    R_s, T_s = np.meshgrid(r_s, theta_s)
    Xs = R_s * np.cos(T_s)
    Ys = R_s * np.sin(T_s)
    Zs = 0.45 * (R_s**2)

    ax2.plot_surface(Xs, Ys, Zs, color=ACCENT_PURPLE, alpha=0.3, edgecolor=ACCENT_PURPLE, lw=0.4)

    # Perimeter boundary curve C = partial S at r = 1.8
    t_c = np.linspace(0, 2*np.pi, 100)
    Xc = 1.8 * np.cos(t_c)
    Yc = 1.8 * np.sin(t_c)
    Zc = 0.45 * (1.8**2) * np.ones_like(t_c)
    ax2.plot(Xc, Yc, Zc, color=ACCENT_ROSE, lw=3.0, label='Boundary Curve $C = \\partial S$')

    # Direction arrows on C
    for t_arr in [np.pi/4, 3*np.pi/4, 5*np.pi/4, 7*np.pi/4]:
        dx = -1.8 * np.sin(t_arr) * 0.3
        dy = 1.8 * np.cos(t_arr) * 0.3
        ax2.quiver(1.8*np.cos(t_arr), 1.8*np.sin(t_arr), 0.45*(1.8**2), dx, dy, 0, color=ACCENT_ROSE, lw=2.5, arrow_length_ratio=0.4)

    # Normal vectors / Curl vectors through S
    for r_i in [0.6, 1.2]:
        for th_i in [0, np.pi/2, np.pi, 3*np.pi/2]:
            px = r_i * np.cos(th_i); py = r_i * np.sin(th_i); pz = 0.45 * (r_i**2)
            ax2.quiver(px, py, pz, 0, 0, 0.7, color=ACCENT_EMERALD, lw=2.0, arrow_length_ratio=0.35)

    ax2.text(0, 0, 0.4, r'$\iint_S (\nabla\times\vec{F})\cdot d\vec{S}$', color=ACCENT_EMERALD, fontsize=11, fontweight='bold', ha='center')
    ax2.text(1.2, -1.2, 1.8, r'$\oint_C \vec{F}\cdot d\vec{r}$', color=ACCENT_ROSE, fontsize=12, fontweight='bold')

    ax2.set_xlim(-2.2, 2.2); ax2.set_ylim(-2.2, 2.2); ax2.set_zlim(0, 2.2)
    ax2.set_title("Stokes' Circulation Theorem\n$\\iint_S (\\nabla\\times\\vec{F})\\cdot d\\vec{S} = \\oint_C \\vec{F}\\cdot d\\vec{r}$", color=TEXT_LIGHT, fontsize=11, pad=12, fontweight='bold')
    ax2.view_init(elev=28, azim=-55)

    plt.tight_layout()
    plt.savefig(os.path.join(OUTPUT_DIR, 'vector_integration_theorems.png'), dpi=160, bbox_inches='tight')
    plt.close()
    print("[OK] Created vector_integration_theorems.png")

make_diagram_7()

# -----------------------------------------------------------------------------
# DIAGRAM 8: Vector Integration Problem (Path Independence & Surface Flux)
# -----------------------------------------------------------------------------
def make_diagram_8():
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11.5, 4.8), dpi=150)
    fig.patch.set_facecolor(BG_DARK)

    # 1. Path Independence in Conservative vs Non-Conservative Field
    ax1.set_facecolor(CARD_BG)
    ax1.set_xlim(-0.2, 2.2); ax1.set_ylim(-0.2, 2.2); ax1.set_aspect('equal')
    ax1.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)

    # Points A and B
    pA = np.array([0.2, 0.2])
    pB = np.array([1.8, 1.8])
    ax1.scatter([pA[0], pB[0]], [pA[1], pB[1]], color=TEXT_LIGHT, s=90, zorder=6)
    ax1.text(pA[0]-0.15, pA[1]-0.15, "Point $A$", color=TEXT_LIGHT, fontsize=10.5, fontweight='bold')
    ax1.text(pB[0]+0.05, pB[1]+0.05, "Point $B$", color=TEXT_LIGHT, fontsize=10.5, fontweight='bold')

    # Path 1: Straight line
    ax1.plot([pA[0], pB[0]], [pA[1], pB[1]], color=ACCENT_CYAN, lw=2.5, label='Path $C_1$ (Straight Line)')
    ax1.annotate('', xy=(1.05, 1.05), xytext=(0.95, 0.95), arrowprops=dict(arrowstyle="->,head_width=0.3,head_length=0.4", color=ACCENT_CYAN, lw=2.5))

    # Path 2: Parabola
    t_p = np.linspace(0, 1, 100)
    x_p2 = pA[0] + (pB[0] - pA[0]) * t_p
    y_p2 = pA[1] + (pB[1] - pA[1]) * (t_p**2)
    ax1.plot(x_p2, y_p2, color=ACCENT_ROSE, lw=2.5, label='Path $C_2$ (Parabolic Curve)')
    ax1.annotate('', xy=(x_p2[55], y_p2[55]), xytext=(x_p2[50], y_p2[50]), arrowprops=dict(arrowstyle="->,head_width=0.3,head_length=0.4", color=ACCENT_ROSE, lw=2.5))

    # Path 3: Two-step right angle
    ax1.plot([pA[0], pB[0], pB[0]], [pA[1], pA[1], pB[1]], color=ACCENT_AMBER, lw=2.0, linestyle='--', label='Path $C_3$ (Stepwise $dx$ then $dy$)')

    ax1.text(1.0, 0.45, r'Conservative: $\int_{C_1} = \int_{C_2} = \int_{C_3} = \Delta \phi$', color=ACCENT_EMERALD, fontsize=10, fontweight='bold', ha='center',
             bbox=dict(boxstyle='round,pad=0.25', facecolor=BG_DARK, edgecolor=BORDER_COL))
    ax1.text(1.0, 0.15, r'Non-Conservative: $\oint \vec{F}\cdot d\vec{r} \neq 0$', color=ACCENT_ROSE, fontsize=9.5, ha='center')

    ax1.set_title("Path Independence in Conservative Fields", color=TEXT_LIGHT, fontsize=11, pad=8, fontweight='bold')
    ax1.legend(loc='upper left', fontsize=8.2, facecolor=BG_DARK, edgecolor=BORDER_COL)

    # 2. Closed Cylinder Surface Flux Decomposition
    ax2.set_facecolor(CARD_BG)
    ax2.set_xlim(-2.2, 2.2); ax2.set_ylim(-1.8, 1.8); ax2.set_aspect('equal')
    ax2.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)

    # Draw 2D projection of cylinder
    # Body
    rect_c = Rectangle((-1.0, -1.0), 2.0, 2.0, facecolor=ACCENT_CYAN, alpha=0.12, edgecolor=ACCENT_CYAN, lw=2.0)
    ax2.add_patch(rect_c)

    # Top Cap (z = +H)
    ax2.plot([-1.0, 1.0], [1.0, 1.0], color=ACCENT_EMERALD, lw=3.0, label='Top Cap $S_1$ ($\\hat{n} = +\\hat{k}$)')
    ax2.annotate('', xy=(0, 1.6), xytext=(0, 1.0), arrowprops=dict(arrowstyle="->,head_width=0.3,head_length=0.4", color=ACCENT_EMERALD, lw=2.2))
    ax2.text(0.15, 1.4, r'$\hat{n}_1 = +\hat{k}$', color=ACCENT_EMERALD, fontsize=10, fontweight='bold')

    # Bottom Cap (z = -H)
    ax2.plot([-1.0, 1.0], [-1.0, -1.0], color=ACCENT_ROSE, lw=3.0, label='Bottom Cap $S_2$ ($\\hat{n} = -\\hat{k}$)')
    ax2.annotate('', xy=(0, -1.6), xytext=(0, -1.0), arrowprops=dict(arrowstyle="->,head_width=0.3,head_length=0.4", color=ACCENT_ROSE, lw=2.2))
    ax2.text(0.15, -1.5, r'$\hat{n}_2 = -\hat{k}$', color=ACCENT_ROSE, fontsize=10, fontweight='bold')

    # Curved Side Wall (r = R)
    ax2.plot([-1.0, -1.0], [-1.0, 1.0], color=ACCENT_AMBER, lw=3.0, label='Side Wall $S_3$ ($\\hat{n} = \\hat{r}$)')
    ax2.plot([1.0, 1.0], [-1.0, 1.0], color=ACCENT_AMBER, lw=3.0)
    ax2.annotate('', xy=(1.6, 0), xytext=(1.0, 0), arrowprops=dict(arrowstyle="->,head_width=0.3,head_length=0.4", color=ACCENT_AMBER, lw=2.2))
    ax2.text(1.2, 0.15, r'$\hat{n}_3 = \hat{r}$', color=ACCENT_AMBER, fontsize=10, fontweight='bold')

    ax2.text(0, -0.15, r'$\oiint_S = \iint_{S_1} + \iint_{S_2} + \iint_{S_3}$', color=TEXT_LIGHT, fontsize=11, fontweight='bold', ha='center',
             bbox=dict(boxstyle='round,pad=0.25', facecolor=BG_DARK, edgecolor=BORDER_COL))
    ax2.set_title("Surface Flux Decomposition across Closed Boundary", color=TEXT_LIGHT, fontsize=11, pad=8, fontweight='bold')
    ax2.legend(loc='lower right', fontsize=8.0, facecolor=BG_DARK, edgecolor=BORDER_COL)

    plt.tight_layout()
    plt.savefig(os.path.join(OUTPUT_DIR, 'vector_integration_problem.png'), dpi=160, bbox_inches='tight')
    plt.close()
    print("[OK] Created vector_integration_problem.png")

make_diagram_8()

# -----------------------------------------------------------------------------
# DIAGRAM 9: Curvilinear Coordinate Systems (Cartesian, Cylindrical, Spherical)
# -----------------------------------------------------------------------------
def make_diagram_9():
    fig = plt.figure(figsize=(13.5, 4.6), dpi=150)
    fig.patch.set_facecolor(BG_DARK)

    # 1. Cartesian Coordinates (x, y, z)
    ax1 = fig.add_subplot(1, 3, 1, projection='3d')
    ax1.set_facecolor(CARD_BG)
    P = np.array([2.0, 2.0, 2.0])

    # Orthogonal planes / box
    ax1.plot([0, P[0], P[0], 0, 0], [0, 0, P[1], P[1], 0], [0, 0, 0, 0, 0], color=BORDER_COL, linestyle=':')
    ax1.plot([P[0], P[0]], [0, 0], [0, P[2]], color=BORDER_COL, linestyle=':')
    ax1.plot([0, 0], [P[1], P[1]], [0, P[2]], color=BORDER_COL, linestyle=':')
    ax1.plot([P[0], P[0]], [P[1], P[1]], [0, P[2]], color=BORDER_COL, linestyle=':')
    ax1.scatter([P[0]], [P[1]], [P[2]], color=ACCENT_AMBER, s=80, zorder=6)
    ax1.text(P[0], P[1], P[2]+0.2, " $P(x,y,z)$", color=ACCENT_AMBER, fontsize=10, fontweight='bold')

    # Unit vectors
    ax1.quiver(P[0], P[1], P[2], 0.8, 0, 0, color=ACCENT_CYAN, lw=2.2, arrow_length_ratio=0.3)
    ax1.quiver(P[0], P[1], P[2], 0, 0.8, 0, color=ACCENT_EMERALD, lw=2.2, arrow_length_ratio=0.3)
    ax1.quiver(P[0], P[1], P[2], 0, 0, 0.8, color=ACCENT_ROSE, lw=2.2, arrow_length_ratio=0.3)
    ax1.text(P[0]+0.9, P[1], P[2], r'$\hat{i}$', color=ACCENT_CYAN, fontsize=11, fontweight='bold')
    ax1.text(P[0], P[1]+0.9, P[2], r'$\hat{j}$', color=ACCENT_EMERALD, fontsize=11, fontweight='bold')
    ax1.text(P[0], P[1], P[2]+0.9, r'$\hat{k}$', color=ACCENT_ROSE, fontsize=11, fontweight='bold')

    ax1.set_xlim(0, 3.2); ax1.set_ylim(0, 3.2); ax1.set_zlim(0, 3.2)
    ax1.set_title("Cartesian Coordinates $(x,y,z)$\n$h_x=1, \\ h_y=1, \\ h_z=1$", color=TEXT_LIGHT, fontsize=10.5, pad=8, fontweight='bold')
    ax1.view_init(elev=22, azim=-50)

    # 2. Cylindrical Coordinates (r, theta, z)
    ax2 = fig.add_subplot(1, 3, 2, projection='3d')
    ax2.set_facecolor(CARD_BG)
    r_cyl = 2.2
    th_cyl = np.radians(45)
    z_cyl = 1.8
    Px = r_cyl * np.cos(th_cyl)
    Py = r_cyl * np.sin(th_cyl)
    Pz = z_cyl

    # Base projection
    t_base = np.linspace(0, th_cyl, 30)
    ax2.plot(r_cyl*np.cos(t_base), r_cyl*np.sin(t_base), np.zeros_like(t_base), color=ACCENT_AMBER, linestyle='--')
    ax2.plot([0, Px], [0, Py], [0, 0], color=ACCENT_CYAN, lw=2.0)
    ax2.plot([Px, Px], [Py, Py], [0, Pz], color=TEXT_MUTED, linestyle=':')

    # Point P
    ax2.scatter([Px], [Py], [Pz], color=ACCENT_AMBER, s=80, zorder=6)
    ax2.text(Px, Py, Pz+0.2, " $P(r,\\theta,z)$", color=ACCENT_AMBER, fontsize=10, fontweight='bold')

    # Unit vectors: e_r, e_theta, e_z
    e_r = np.array([np.cos(th_cyl), np.sin(th_cyl), 0]) * 0.8
    e_th = np.array([-np.sin(th_cyl), np.cos(th_cyl), 0]) * 0.8
    e_z = np.array([0, 0, 1]) * 0.8
    ax2.quiver(Px, Py, Pz, e_r[0], e_r[1], e_r[2], color=ACCENT_CYAN, lw=2.2, arrow_length_ratio=0.3)
    ax2.quiver(Px, Py, Pz, e_th[0], e_th[1], e_th[2], color=ACCENT_EMERALD, lw=2.2, arrow_length_ratio=0.3)
    ax2.quiver(Px, Py, Pz, e_z[0], e_z[1], e_z[2], color=ACCENT_ROSE, lw=2.2, arrow_length_ratio=0.3)
    ax2.text(Px+e_r[0], Py+e_r[1], Pz, r'$\hat{e}_r$', color=ACCENT_CYAN, fontsize=11, fontweight='bold')
    ax2.text(Px+e_th[0], Py+e_th[1], Pz, r'$\hat{e}_\theta$', color=ACCENT_EMERALD, fontsize=11, fontweight='bold')
    ax2.text(Px, Py, Pz+e_z[2]+0.1, r'$\hat{e}_z$', color=ACCENT_ROSE, fontsize=11, fontweight='bold')

    ax2.set_xlim(0, 3.2); ax2.set_ylim(0, 3.2); ax2.set_zlim(0, 3.2)
    ax2.set_title("Cylindrical Coordinates $(r,\\theta,z)$\n$h_r=1, \\ h_\\theta=r, \\ h_z=1$", color=TEXT_LIGHT, fontsize=10.5, pad=8, fontweight='bold')
    ax2.view_init(elev=22, azim=-50)

    # 3. Spherical Polar Coordinates (r, theta, phi)
    ax3 = fig.add_subplot(1, 3, 3, projection='3d')
    ax3.set_facecolor(CARD_BG)
    r_sph = 2.4
    th_sph = np.radians(45)  # polar angle from z
    phi_sph = np.radians(40) # azimuth from x
    Px3 = r_sph * np.sin(th_sph) * np.cos(phi_sph)
    Py3 = r_sph * np.sin(th_sph) * np.sin(phi_sph)
    Pz3 = r_sph * np.cos(th_sph)

    # Line from origin to P
    ax3.plot([0, Px3], [0, Py3], [0, Pz3], color=ACCENT_AMBER, lw=2.5)
    # Projection onto xy-plane
    ax3.plot([0, Px3], [0, Py3], [0, 0], color=TEXT_MUTED, linestyle='--')
    ax3.plot([Px3, Px3], [Py3, Py3], [0, Pz3], color=TEXT_MUTED, linestyle=':')

    # Point P
    ax3.scatter([Px3], [Py3], [Pz3], color=ACCENT_AMBER, s=80, zorder=6)
    ax3.text(Px3, Py3, Pz3+0.2, " $P(r,\\theta,\\phi)$", color=ACCENT_AMBER, fontsize=10, fontweight='bold')

    # Unit vectors: e_r, e_theta, e_phi
    e_r3 = np.array([np.sin(th_sph)*np.cos(phi_sph), np.sin(th_sph)*np.sin(phi_sph), np.cos(th_sph)]) * 0.8
    e_th3 = np.array([np.cos(th_sph)*np.cos(phi_sph), np.cos(th_sph)*np.sin(phi_sph), -np.sin(th_sph)]) * 0.8
    e_phi3 = np.array([-np.sin(phi_sph), np.cos(phi_sph), 0]) * 0.8

    ax3.quiver(Px3, Py3, Pz3, e_r3[0], e_r3[1], e_r3[2], color=ACCENT_CYAN, lw=2.2, arrow_length_ratio=0.3)
    ax3.quiver(Px3, Py3, Pz3, e_th3[0], e_th3[1], e_th3[2], color=ACCENT_EMERALD, lw=2.2, arrow_length_ratio=0.3)
    ax3.quiver(Px3, Py3, Pz3, e_phi3[0], e_phi3[1], e_phi3[2], color=ACCENT_PURPLE, lw=2.2, arrow_length_ratio=0.3)
    ax3.text(Px3+e_r3[0], Py3+e_r3[1], Pz3+e_r3[2], r'$\hat{e}_r$', color=ACCENT_CYAN, fontsize=11, fontweight='bold')
    ax3.text(Px3+e_th3[0], Py3+e_th3[1], Pz3+e_th3[2], r'$\hat{e}_\theta$', color=ACCENT_EMERALD, fontsize=11, fontweight='bold')
    ax3.text(Px3+e_phi3[0], Py3+e_phi3[1], Pz3, r'$\hat{e}_\phi$', color=ACCENT_PURPLE, fontsize=11, fontweight='bold')

    ax3.set_xlim(0, 3.2); ax3.set_ylim(0, 3.2); ax3.set_zlim(0, 3.2)
    ax3.set_title("Spherical Coordinates $(r,\\theta,\\phi)$\n$h_r=1, \\ h_\\theta=r, \\ h_\\phi=r\\sin\\theta$", color=TEXT_LIGHT, fontsize=10.5, pad=8, fontweight='bold')
    ax3.view_init(elev=22, azim=-50)

    plt.tight_layout()
    plt.savefig(os.path.join(OUTPUT_DIR, 'curvilinear_coordinates_geometry.png'), dpi=160, bbox_inches='tight')
    plt.close()
    print("[OK] Created curvilinear_coordinates_geometry.png")

make_diagram_9()

# -----------------------------------------------------------------------------
# DIAGRAM 10: Curvilinear Problem (Dipole Field & Equipotentials)
# -----------------------------------------------------------------------------
def make_diagram_10():
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11.5, 4.8), dpi=150)
    fig.patch.set_facecolor(BG_DARK)

    # 1. Electric Dipole Field Lines & Equipotentials in Spherical Coordinates
    ax1.set_facecolor(CARD_BG)
    ax1.set_xlim(-2.4, 2.4); ax1.set_ylim(-2.4, 2.4); ax1.set_aspect('equal')
    ax1.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)

    # Equipotentials: V = p cos(theta) / r^2 = const => r = sqrt(p cos(theta) / V)
    theta = np.linspace(-np.pi/2 + 0.05, np.pi/2 - 0.05, 100)
    for V_val in [0.5, 1.0, 2.0, 4.0]:
        r_pos = np.sqrt(np.maximum(0, np.cos(theta) / V_val))
        ax1.plot(r_pos * np.sin(theta), r_pos * np.cos(theta), color=ACCENT_CYAN, linestyle='--', lw=1.2)
        ax1.plot(-r_pos * np.sin(theta), -r_pos * np.cos(theta), color=ACCENT_ROSE, linestyle='--', lw=1.2)

    # Field lines: r = r0 * sin^2(theta)
    th_field = np.linspace(0.01, np.pi - 0.01, 100)
    for r0 in [0.8, 1.4, 2.0, 2.8]:
        r_f = r0 * (np.sin(th_field)**2)
        ax1.plot(r_f * np.sin(th_field), r_f * np.cos(th_field), color=ACCENT_EMERALD, lw=1.6)
        ax1.plot(-r_f * np.sin(th_field), r_f * np.cos(th_field), color=ACCENT_EMERALD, lw=1.6)

    # Dipole charges at center
    ax1.scatter([0], [0.15], color=ACCENT_ROSE, s=90, zorder=6, label='$+q$ (North)')
    ax1.scatter([0], [-0.15], color=ACCENT_CYAN, s=90, zorder=6, label='$-q$ (South)')

    ax1.text(0, -2.15, r'$V(r,\theta) = \frac{p\cos\theta}{4\pi\epsilon_0 r^2}, \quad \nabla^2 V = 0$', color=TEXT_LIGHT, fontsize=10.5, fontweight='bold', ha='center',
             bbox=dict(boxstyle='round,pad=0.25', facecolor=BG_DARK, edgecolor=BORDER_COL))
    ax1.set_title("Dipole Potential $V(r,\\theta)$ & Field Lines in Spherical Polar", color=TEXT_LIGHT, fontsize=11, pad=8, fontweight='bold')
    ax1.legend(loc='upper right', fontsize=8.2, facecolor=BG_DARK, edgecolor=BORDER_COL)

    # 2. Vector Components Er and E_theta vs Polar Angle theta
    ax2.set_facecolor(CARD_BG)
    th_deg = np.linspace(0, 180, 200)
    th_rad = np.radians(th_deg)
    # Er = 2 * cos(theta) / r^3
    # E_theta = sin(theta) / r^3
    Er = 2.0 * np.cos(th_rad)
    Eth = np.sin(th_rad)
    E_mag = np.sqrt(Er**2 + Eth**2)

    ax2.plot(th_deg, Er, color=ACCENT_CYAN, lw=2.4, label=r'Radial Component $E_r \propto 2\cos\theta$')
    ax2.plot(th_deg, Eth, color=ACCENT_EMERALD, lw=2.4, label=r'Polar Component $E_\theta \propto \sin\theta$')
    ax2.plot(th_deg, E_mag, color=ACCENT_AMBER, lw=2.0, linestyle='--', label=r'Total Field $|\vec{E}| \propto \sqrt{1 + 3\cos^2\theta}$')
    ax2.axhline(0, color='#64748b', linestyle=':', lw=1)

    ax2.set_xlim(0, 180)
    ax2.set_xlabel('Polar Angle $\\theta$ (degrees)', fontsize=9.5)
    ax2.set_ylabel('Field Component Amplitude (a.u.)', fontsize=9.5)
    ax2.grid(True, linestyle=':', alpha=0.3, color=BORDER_COL)
    ax2.legend(loc='lower left', fontsize=8.5, facecolor=BG_DARK, edgecolor=BORDER_COL)
    ax2.set_title("Field Decomposition: $\\vec{E} = -\\nabla V = E_r \\hat{r} + E_\\theta \\hat{\\theta}$", color=TEXT_LIGHT, fontsize=11, pad=8, fontweight='bold')

    plt.tight_layout()
    plt.savefig(os.path.join(OUTPUT_DIR, 'curvilinear_dipole_field.png'), dpi=160, bbox_inches='tight')
    plt.close()
    print("[OK] Created curvilinear_dipole_field.png")

make_diagram_10()

print("ALL 10 DIAGRAM IMAGES SUCCESSFULLY GENERATED!")
