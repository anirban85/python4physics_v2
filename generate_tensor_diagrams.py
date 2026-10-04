"""
Python4Physics - Generator for Module 2 Tensor Visualization Diagrams
Produces 5 high-resolution, professional scientific diagrams for Module 2:
Tensor Basics, Stress Tensor, Inertia Tensor, Metric Tensor & Covariant Calculus,
Electromagnetic Field Tensor.
"""
import os
import numpy as np
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from matplotlib.patches import FancyArrowPatch, Rectangle, Arc
from mpl_toolkits.mplot3d import Axes3D
from mpl_toolkits.mplot3d.art3d import Poly3DCollection

OUT_DIR = os.path.join('assets', 'images', 'visualization', 'tensors')
os.makedirs(OUT_DIR, exist_ok=True)

# --- Professional style ---
COLORS = {
    'bg': '#0f172a',
    'card': '#1e293b',
    'accent': '#38bdf8',
    'accent2': '#a855f7',
    'green': '#10b981',
    'amber': '#f59e0b',
    'rose': '#f43f5e',
    'text': '#f8fafc',
    'dim': '#94a3b8',
    'grid': '#334155',
}

plt.rcParams.update({
    'figure.facecolor': COLORS['bg'],
    'axes.facecolor': COLORS['card'],
    'axes.edgecolor': COLORS['grid'],
    'axes.labelcolor': COLORS['text'],
    'xtick.color': COLORS['dim'],
    'ytick.color': COLORS['dim'],
    'text.color': COLORS['text'],
    'font.family': 'sans-serif',
    'font.size': 11,
    'axes.grid': True,
    'grid.color': COLORS['grid'],
    'grid.alpha': 0.3,
})


# =========================================================================
# DIAGRAM 1: Tensor Basics - Scalars, Vectors, Rank-2 Tensors
# =========================================================================
def generate_tensor_basics():
    fig, axes = plt.subplots(1, 3, figsize=(14, 5), dpi=150)
    fig.subplots_adjust(left=0.06, right=0.96, top=0.85, bottom=0.12, wspace=0.35)

    # (a) Scalar field - Temperature distribution
    ax = axes[0]
    x = np.linspace(-2, 2, 100)
    y = np.linspace(-2, 2, 100)
    X, Y = np.meshgrid(x, y)
    T = np.exp(-(X**2 + Y**2) / 1.5) * 300 + 200
    cs = ax.contourf(X, Y, T, levels=15, cmap='inferno', alpha=0.85)
    ax.contour(X, Y, T, levels=10, colors='white', linewidths=0.5, alpha=0.4)
    cbar = fig.colorbar(cs, ax=ax, shrink=0.8, pad=0.05)
    cbar.set_label('T (K)', fontsize=9, color=COLORS['dim'])
    cbar.ax.tick_params(colors=COLORS['dim'], labelsize=8)
    ax.set_title('(a) Scalar Field (Rank 0)\nTemperature $T(x,y)$', fontsize=11, fontweight='bold', color=COLORS['accent'], pad=8)
    ax.set_xlabel('x', fontsize=9)
    ax.set_ylabel('y', fontsize=9)
    ax.set_aspect('equal')

    # (b) Vector field - Electric field of dipole
    ax = axes[1]
    q = 1.0
    d_sep = 0.8
    xx = np.linspace(-2.5, 2.5, 20)
    yy = np.linspace(-2.5, 2.5, 20)
    Xg, Yg = np.meshgrid(xx, yy)
    Ex = np.zeros_like(Xg)
    Ey = np.zeros_like(Yg)
    for sign, x0 in [(+1, d_sep/2), (-1, -d_sep/2)]:
        dx = Xg - x0
        dy = Yg
        r = np.sqrt(dx**2 + dy**2)
        r = np.where(r < 0.25, 0.25, r)
        Ex += sign * q * dx / r**3
        Ey += sign * q * dy / r**3
    mag = np.sqrt(Ex**2 + Ey**2)
    mag = np.where(mag < 0.01, 0.01, mag)
    Ex_n = Ex / mag
    Ey_n = Ey / mag
    clr = np.log10(mag + 0.1)
    ax.quiver(Xg, Yg, Ex_n, Ey_n, clr, cmap='cool', alpha=0.85, scale=28, width=0.004)
    ax.plot(d_sep/2, 0, 'o', color=COLORS['rose'], ms=12, zorder=5)
    ax.plot(-d_sep/2, 0, 'o', color=COLORS['accent'], ms=12, zorder=5)
    ax.annotate('+q', (d_sep/2 + 0.15, 0.15), fontsize=10, color=COLORS['rose'], fontweight='bold')
    ax.annotate('-q', (-d_sep/2 - 0.4, 0.15), fontsize=10, color=COLORS['accent'], fontweight='bold')
    ax.set_title('(b) Vector Field (Rank 1)\nElectric Dipole $\\vec{E}(\\vec{r})$', fontsize=11, fontweight='bold', color=COLORS['accent'], pad=8)
    ax.set_xlabel('x', fontsize=9)
    ax.set_ylabel('y', fontsize=9)
    ax.set_xlim(-2.5, 2.5)
    ax.set_ylim(-2.5, 2.5)
    ax.set_aspect('equal')

    # (c) Rank-2 tensor - Stress tensor matrix visualization
    ax = axes[2]
    ax.set_xlim(-0.5, 4.5)
    ax.set_ylim(-0.5, 4.5)
    ax.set_aspect('equal')
    ax.axis('off')
    ax.set_title('(c) Rank-2 Tensor\nStress Tensor $\\sigma_{ij}$', fontsize=11, fontweight='bold', color=COLORS['accent'], pad=8)

    # Draw 3x3 matrix grid
    labels = [
        [r'$\sigma_{xx}$', r'$\sigma_{xy}$', r'$\sigma_{xz}$'],
        [r'$\sigma_{yx}$', r'$\sigma_{yy}$', r'$\sigma_{yz}$'],
        [r'$\sigma_{zx}$', r'$\sigma_{zy}$', r'$\sigma_{zz}$'],
    ]
    colors_mat = [
        [COLORS['accent'], COLORS['amber'], COLORS['amber']],
        [COLORS['amber'], COLORS['green'], COLORS['amber']],
        [COLORS['amber'], COLORS['amber'], COLORS['rose']],
    ]
    for i in range(3):
        for j in range(3):
            rect = Rectangle((j * 1.3 + 0.3, (2 - i) * 1.3 + 0.3), 1.1, 1.1,
                              facecolor=colors_mat[i][j], alpha=0.15,
                              edgecolor=colors_mat[i][j], lw=2, zorder=2)
            ax.add_patch(rect)
            ax.text(j * 1.3 + 0.85, (2 - i) * 1.3 + 0.85, labels[i][j],
                    fontsize=13, ha='center', va='center', color=colors_mat[i][j],
                    fontweight='bold', zorder=3)

    # Bracket decoration
    ax.plot([0.15, 0.15], [0.15, 4.25], color=COLORS['text'], lw=2.5, clip_on=False)
    ax.plot([0.15, 0.35], [0.15, 0.15], color=COLORS['text'], lw=2.5, clip_on=False)
    ax.plot([0.15, 0.35], [4.25, 4.25], color=COLORS['text'], lw=2.5, clip_on=False)
    ax.plot([4.25, 4.25], [0.15, 4.25], color=COLORS['text'], lw=2.5, clip_on=False)
    ax.plot([4.05, 4.25], [0.15, 0.15], color=COLORS['text'], lw=2.5, clip_on=False)
    ax.plot([4.05, 4.25], [4.25, 4.25], color=COLORS['text'], lw=2.5, clip_on=False)

    fig.suptitle('Tensor Rank Hierarchy: From Scalars to Rank-2 Tensors', fontsize=14,
                 fontweight='bold', color=COLORS['text'], y=0.97)
    fname = os.path.join(OUT_DIR, 'tensor_basics_rank_hierarchy.png')
    fig.savefig(fname, dpi=150, bbox_inches='tight')
    plt.close(fig)
    print("[OK] Created tensor_basics_rank_hierarchy.png")


# =========================================================================
# DIAGRAM 2: Cauchy Stress Tensor - Stress on a Cube Element
# =========================================================================
def generate_stress_tensor():
    fig = plt.figure(figsize=(12, 5.5), dpi=150)
    fig.subplots_adjust(left=0.02, right=0.98, top=0.88, bottom=0.05, wspace=0.15)

    # (a) 3D stress cube
    ax1 = fig.add_subplot(121, projection='3d')
    ax1.set_facecolor(COLORS['card'])

    # Draw cube faces
    vertices = np.array([
        [0, 0, 0], [1, 0, 0], [1, 1, 0], [0, 1, 0],  # bottom
        [0, 0, 1], [1, 0, 1], [1, 1, 1], [0, 1, 1],  # top
    ])
    faces = [
        [vertices[j] for j in [0, 1, 2, 3]],
        [vertices[j] for j in [4, 5, 6, 7]],
        [vertices[j] for j in [0, 1, 5, 4]],
        [vertices[j] for j in [2, 3, 7, 6]],
        [vertices[j] for j in [0, 3, 7, 4]],
        [vertices[j] for j in [1, 2, 6, 5]],
    ]
    face_colors = [COLORS['accent'] + '15', COLORS['accent'] + '25',
                   COLORS['green'] + '15', COLORS['green'] + '15',
                   COLORS['amber'] + '15', COLORS['rose'] + '15']
    for face, fc in zip(faces, face_colors):
        poly = Poly3DCollection([face], alpha=0.15, facecolor=COLORS['accent'],
                                 edgecolor=COLORS['dim'], lw=1.5)
        ax1.add_collection3d(poly)

    c = 0.5  # center
    arrow_len = 0.6
    # Normal stresses (outward from faces)
    ax1.quiver(1, c, c, arrow_len, 0, 0, color=COLORS['rose'], lw=2.5, arrow_length_ratio=0.25)
    ax1.text(1 + arrow_len + 0.1, c, c, r'$\sigma_{xx}$', fontsize=11, color=COLORS['rose'], fontweight='bold')

    ax1.quiver(c, 1, c, 0, arrow_len, 0, color=COLORS['green'], lw=2.5, arrow_length_ratio=0.25)
    ax1.text(c, 1 + arrow_len + 0.1, c, r'$\sigma_{yy}$', fontsize=11, color=COLORS['green'], fontweight='bold')

    ax1.quiver(c, c, 1, 0, 0, arrow_len, color=COLORS['accent'], lw=2.5, arrow_length_ratio=0.25)
    ax1.text(c, c, 1 + arrow_len + 0.15, r'$\sigma_{zz}$', fontsize=11, color=COLORS['accent'], fontweight='bold')

    # Shear stresses on x-face
    ax1.quiver(1, c, c, 0, arrow_len * 0.7, 0, color=COLORS['amber'], lw=2, arrow_length_ratio=0.3)
    ax1.text(1.05, c + arrow_len * 0.7 + 0.05, c, r'$\tau_{xy}$', fontsize=9, color=COLORS['amber'])

    ax1.quiver(1, c, c, 0, 0, arrow_len * 0.7, color=COLORS['accent2'], lw=2, arrow_length_ratio=0.3)
    ax1.text(1.05, c, c + arrow_len * 0.7 + 0.05, r'$\tau_{xz}$', fontsize=9, color=COLORS['accent2'])

    ax1.set_xlim(-0.3, 2.0)
    ax1.set_ylim(-0.3, 2.0)
    ax1.set_zlim(-0.3, 2.0)
    ax1.set_xlabel('x', fontsize=9)
    ax1.set_ylabel('y', fontsize=9)
    ax1.set_zlabel('z', fontsize=9)
    ax1.view_init(elev=22, azim=-50)
    ax1.set_title('(a) Cauchy Stress on Infinitesimal Cube\nNormal & Shear Stress Components',
                  fontsize=10.5, fontweight='bold', color=COLORS['accent'], pad=12)

    # (b) Mohr's Circle (2D stress transformation)
    ax2 = fig.add_subplot(122)
    sigma_x, sigma_y, tau_xy = 80, 20, 30  # MPa
    center = (sigma_x + sigma_y) / 2
    R = np.sqrt(((sigma_x - sigma_y) / 2)**2 + tau_xy**2)
    theta_arr = np.linspace(0, 2 * np.pi, 200)
    ax2.plot(center + R * np.cos(theta_arr), R * np.sin(theta_arr),
             color=COLORS['accent'], lw=2.5, label="Mohr's Circle")

    # Principal stresses
    s1 = center + R
    s2 = center - R
    ax2.plot(s1, 0, 'o', color=COLORS['rose'], ms=10, zorder=5)
    ax2.plot(s2, 0, 'o', color=COLORS['green'], ms=10, zorder=5)
    ax2.annotate(f'$\\sigma_1 = {s1:.1f}$', (s1, 0), xytext=(s1 + 5, 10),
                fontsize=10, color=COLORS['rose'], fontweight='bold',
                arrowprops=dict(arrowstyle='->', color=COLORS['rose']))
    ax2.annotate(f'$\\sigma_2 = {s2:.1f}$', (s2, 0), xytext=(s2 - 15, -15),
                fontsize=10, color=COLORS['green'], fontweight='bold',
                arrowprops=dict(arrowstyle='->', color=COLORS['green']))

    # Original state points
    ax2.plot(sigma_x, tau_xy, 's', color=COLORS['amber'], ms=9, zorder=5, label=f'Face X: ({sigma_x}, {tau_xy})')
    ax2.plot(sigma_y, -tau_xy, 's', color=COLORS['accent2'], ms=9, zorder=5, label=f'Face Y: ({sigma_y}, -{tau_xy})')
    ax2.plot([sigma_x, sigma_y], [tau_xy, -tau_xy], '--', color=COLORS['dim'], lw=1.5)

    ax2.axhline(0, color=COLORS['dim'], lw=0.8, alpha=0.5)
    ax2.axvline(0, color=COLORS['dim'], lw=0.8, alpha=0.5)
    ax2.set_xlabel('Normal Stress $\\sigma$ (MPa)', fontsize=10)
    ax2.set_ylabel('Shear Stress $\\tau$ (MPa)', fontsize=10)
    ax2.set_title("(b) Mohr's Circle for 2D Stress\nPrincipal Stress Determination",
                  fontsize=10.5, fontweight='bold', color=COLORS['accent'], pad=8)
    ax2.legend(fontsize=8.5, framealpha=0.85, loc='upper left')
    ax2.set_aspect('equal')

    fig.suptitle("Cauchy Stress Tensor: 3D Stress State & Mohr's Circle Transformation",
                 fontsize=13, fontweight='bold', color=COLORS['text'], y=0.97)
    fname = os.path.join(OUT_DIR, 'stress_tensor_mohrs_circle.png')
    fig.savefig(fname, dpi=150, bbox_inches='tight')
    plt.close(fig)
    print("[OK] Created stress_tensor_mohrs_circle.png")


# =========================================================================
# DIAGRAM 3: Moment of Inertia Tensor - Rigid Body Rotation
# =========================================================================
def generate_inertia_tensor():
    fig, axes = plt.subplots(1, 2, figsize=(13, 5.5), dpi=150)
    fig.subplots_adjust(left=0.06, right=0.96, top=0.85, bottom=0.12, wspace=0.3)

    # (a) Inertia ellipsoid concept
    ax = axes[0]
    # Draw an ellipse representing I_xx > I_yy > I_zz
    theta = np.linspace(0, 2 * np.pi, 200)
    a_ell, b_ell = 2.5, 1.2
    x_ell = a_ell * np.cos(theta)
    y_ell = b_ell * np.sin(theta)
    ax.fill(x_ell, y_ell, alpha=0.12, color=COLORS['accent'])
    ax.plot(x_ell, y_ell, color=COLORS['accent'], lw=2.5, label='Inertia Ellipse (2D projection)')

    # Principal axes
    ax.annotate('', xy=(a_ell + 0.5, 0), xytext=(-a_ell - 0.5, 0),
                arrowprops=dict(arrowstyle='->', color=COLORS['rose'], lw=2.5))
    ax.text(a_ell + 0.6, -0.15, '$I_1$ (max)', fontsize=10, color=COLORS['rose'], fontweight='bold')
    ax.annotate('', xy=(0, b_ell + 0.5), xytext=(0, -b_ell - 0.5),
                arrowprops=dict(arrowstyle='->', color=COLORS['green'], lw=2.5))
    ax.text(0.1, b_ell + 0.6, '$I_2$ (min)', fontsize=10, color=COLORS['green'], fontweight='bold')

    # Arbitrary omega direction
    omega_angle = 35
    omega_rad = np.radians(omega_angle)
    omega_len = 2.0
    ax.annotate('', xy=(omega_len * np.cos(omega_rad), omega_len * np.sin(omega_rad)),
                xytext=(0, 0),
                arrowprops=dict(arrowstyle='->', color=COLORS['amber'], lw=3))
    ax.text(omega_len * np.cos(omega_rad) + 0.15, omega_len * np.sin(omega_rad) + 0.15,
            r'$\vec{\omega}$', fontsize=14, color=COLORS['amber'], fontweight='bold')

    # L direction (different from omega for asymmetric body)
    L_angle = 55
    L_rad = np.radians(L_angle)
    L_len = 2.3
    ax.annotate('', xy=(L_len * np.cos(L_rad), L_len * np.sin(L_rad)),
                xytext=(0, 0),
                arrowprops=dict(arrowstyle='->', color=COLORS['accent2'], lw=3))
    ax.text(L_len * np.cos(L_rad) - 0.5, L_len * np.sin(L_rad) + 0.2,
            r'$\vec{L} = \mathbf{I} \cdot \vec{\omega}$', fontsize=12, color=COLORS['accent2'], fontweight='bold')

    # Arc showing angle
    arc_r = 1.0
    arc_theta = np.linspace(omega_rad, L_rad, 30)
    ax.plot(arc_r * np.cos(arc_theta), arc_r * np.sin(arc_theta), '--', color=COLORS['dim'], lw=1.5)
    mid_angle = (omega_rad + L_rad) / 2
    ax.text(0.7 * np.cos(mid_angle), 0.7 * np.sin(mid_angle) + 0.1, r'$\Delta\theta$',
            fontsize=10, color=COLORS['dim'])

    ax.set_xlim(-3.5, 3.5)
    ax.set_ylim(-2.5, 2.8)
    ax.set_aspect('equal')
    ax.set_title('(a) Inertia Ellipse & Principal Axes\n$\\vec{L} = \\mathbf{I}\\cdot\\vec{\\omega}$ (generally non-parallel)',
                 fontsize=10.5, fontweight='bold', color=COLORS['accent'], pad=8)
    ax.legend(fontsize=8.5, framealpha=0.85, loc='lower right')

    # (b) Parallel axis theorem visualization
    ax2 = axes[1]
    # Draw a rigid body cross-section
    body_theta = np.linspace(0, 2 * np.pi, 100)
    body_r = 1.5 + 0.3 * np.cos(3 * body_theta)
    body_x = body_r * np.cos(body_theta)
    body_y = body_r * np.sin(body_theta)
    ax2.fill(body_x, body_y, alpha=0.15, color=COLORS['green'])
    ax2.plot(body_x, body_y, color=COLORS['green'], lw=2)

    # CM
    ax2.plot(0, 0, 'o', color=COLORS['accent'], ms=10, zorder=5)
    ax2.text(0.15, -0.3, 'CM', fontsize=10, color=COLORS['accent'], fontweight='bold')

    # CM axis (into page)
    ax2.plot(0, 0, '+', color=COLORS['accent'], ms=20, mew=3, zorder=6)

    # Displaced axis
    d_x, d_y = 1.0, 0.8
    ax2.plot(d_x, d_y, 'x', color=COLORS['rose'], ms=12, mew=3, zorder=5)
    ax2.text(d_x + 0.15, d_y + 0.15, "P (parallel axis)", fontsize=9, color=COLORS['rose'], fontweight='bold')

    # Distance d
    ax2.plot([0, d_x], [0, d_y], '--', color=COLORS['amber'], lw=2)
    d_val = np.sqrt(d_x**2 + d_y**2)
    ax2.text(d_x / 2 - 0.3, d_y / 2 + 0.15, f'd = {d_val:.2f}', fontsize=10,
             color=COLORS['amber'], fontweight='bold')

    # Formula box
    box_props = dict(boxstyle='round,pad=0.4', facecolor=COLORS['card'], edgecolor=COLORS['accent'], alpha=0.9)
    ax2.text(0, -2.3, r'$I_P = I_{CM} + Md^2$', fontsize=14, ha='center', va='center',
             color=COLORS['text'], fontweight='bold', bbox=box_props)

    ax2.set_xlim(-2.5, 2.5)
    ax2.set_ylim(-3, 2.5)
    ax2.set_aspect('equal')
    ax2.set_title('(b) Parallel Axis (Steiner) Theorem\nMoment of Inertia about Displaced Axis',
                 fontsize=10.5, fontweight='bold', color=COLORS['accent'], pad=8)

    fig.suptitle('Moment of Inertia Tensor: Principal Axes, Ellipsoid & Parallel Axis Theorem',
                 fontsize=13, fontweight='bold', color=COLORS['text'], y=0.97)
    fname = os.path.join(OUT_DIR, 'inertia_tensor_ellipsoid.png')
    fig.savefig(fname, dpi=150, bbox_inches='tight')
    plt.close(fig)
    print("[OK] Created inertia_tensor_ellipsoid.png")


# =========================================================================
# DIAGRAM 4: Metric Tensor & Covariant Calculus
# =========================================================================
def generate_metric_tensor():
    fig, axes = plt.subplots(1, 2, figsize=(13, 5.5), dpi=150)
    fig.subplots_adjust(left=0.06, right=0.96, top=0.85, bottom=0.12, wspace=0.3)

    # (a) Curvilinear coordinate grid showing metric (stretched grid)
    ax = axes[0]
    u_range = np.linspace(0.5, 3.0, 12)
    v_range = np.linspace(0.2, np.pi - 0.2, 12)

    # Polar-like coordinates: x = u*cos(v), y = u*sin(v)
    for u in u_range:
        v_fine = np.linspace(0.2, np.pi - 0.2, 100)
        ax.plot(u * np.cos(v_fine), u * np.sin(v_fine), color=COLORS['accent'], lw=0.8, alpha=0.6)
    for v in v_range:
        u_fine = np.linspace(0.5, 3.0, 50)
        ax.plot(u_fine * np.cos(v), u_fine * np.sin(v), color=COLORS['amber'], lw=0.8, alpha=0.6)

    # Highlight a point and show basis vectors
    u0, v0 = 2.0, np.pi / 3
    x0 = u0 * np.cos(v0)
    y0 = u0 * np.sin(v0)
    ax.plot(x0, y0, 'o', color=COLORS['text'], ms=8, zorder=5)

    # e_u = (cos v, sin v), e_v = (-u sin v, u cos v)
    eu = np.array([np.cos(v0), np.sin(v0)])
    ev = np.array([-u0 * np.sin(v0), u0 * np.cos(v0)]) * 0.4  # scale for visibility
    ax.annotate('', xy=(x0 + eu[0] * 0.8, y0 + eu[1] * 0.8), xytext=(x0, y0),
                arrowprops=dict(arrowstyle='->', color=COLORS['rose'], lw=2.5))
    ax.text(x0 + eu[0] * 0.85 + 0.1, y0 + eu[1] * 0.85, r'$\vec{e}_r$',
            fontsize=12, color=COLORS['rose'], fontweight='bold')

    ax.annotate('', xy=(x0 + ev[0], y0 + ev[1]), xytext=(x0, y0),
                arrowprops=dict(arrowstyle='->', color=COLORS['green'], lw=2.5))
    ax.text(x0 + ev[0] - 0.3, y0 + ev[1] + 0.1, r'$\vec{e}_\theta$',
            fontsize=12, color=COLORS['green'], fontweight='bold')

    # Infinitesimal area element shading
    du = 0.3
    dv = 0.2
    patch_u = np.linspace(u0, u0 + du, 20)
    patch_v = np.linspace(v0, v0 + dv, 20)
    PU, PV = np.meshgrid(patch_u, patch_v)
    PX = PU * np.cos(PV)
    PY = PU * np.sin(PV)
    ax.fill(PX.ravel(), PY.ravel(), alpha=0.25, color=COLORS['accent2'])

    ax.text(x0 + 0.1, y0 - 0.35, '$ds^2 = g_{ij}\\,dx^i\\,dx^j$', fontsize=10,
            color=COLORS['accent2'], fontweight='bold',
            bbox=dict(boxstyle='round,pad=0.3', facecolor=COLORS['card'], edgecolor=COLORS['accent2'], alpha=0.9))

    ax.set_xlim(-3.5, 3.5)
    ax.set_ylim(-0.5, 3.5)
    ax.set_aspect('equal')
    ax.set_title('(a) Curvilinear Grid & Metric Tensor\nBasis Vectors $\\vec{e}_r, \\vec{e}_\\theta$ in Polar Coords',
                 fontsize=10.5, fontweight='bold', color=COLORS['accent'], pad=8)

    # (b) Christoffel symbol / parallel transport concept
    ax2 = axes[1]
    # Draw a curved surface (sphere projection)
    theta_s = np.linspace(0, 2 * np.pi, 200)
    R_s = 2.5
    ax2.plot(R_s * np.cos(theta_s), R_s * np.sin(theta_s), color=COLORS['dim'], lw=1.5, alpha=0.5)

    # Great circle path
    t_path = np.linspace(0, np.pi / 2, 100)
    path_x = R_s * np.cos(t_path)
    path_y = R_s * np.sin(t_path)
    ax2.plot(path_x, path_y, color=COLORS['accent'], lw=3, label='Geodesic path')

    # Parallel transported vectors along path
    n_arrows = 6
    for i in range(n_arrows):
        t = i * (np.pi / 2) / (n_arrows - 1)
        px = R_s * np.cos(t)
        py = R_s * np.sin(t)
        # Vector rotates as it's transported
        angle_rotation = -t * 0.0  # Parallel transport on sphere - vector rotates
        vx = 0.5 * np.cos(np.pi / 4 + angle_rotation)
        vy = 0.5 * np.sin(np.pi / 4 + angle_rotation)
        alpha_val = 0.4 + 0.6 * (i / (n_arrows - 1))
        ax2.annotate('', xy=(px + vx, py + vy), xytext=(px, py),
                    arrowprops=dict(arrowstyle='->', color=COLORS['amber'], lw=2, alpha=alpha_val))

    # Another path for comparison (showing holonomy)
    t_path2 = np.linspace(np.pi / 2, np.pi / 2, 2)
    ax2.plot([0, 0], [0, R_s], '--', color=COLORS['rose'], lw=2, alpha=0.7, label='Radial path')
    ax2.plot([0, R_s], [0, 0], '--', color=COLORS['green'], lw=2, alpha=0.7, label='Equatorial path')

    # Covariant derivative formula
    box_props = dict(boxstyle='round,pad=0.4', facecolor=COLORS['card'], edgecolor=COLORS['accent'], alpha=0.9)
    ax2.text(0, -1.8, r'$\nabla_\mu V^\nu = \partial_\mu V^\nu + \Gamma^\nu_{\mu\lambda} V^\lambda$',
             fontsize=12, ha='center', va='center', color=COLORS['text'], fontweight='bold', bbox=box_props)

    ax2.set_xlim(-3, 3.5)
    ax2.set_ylim(-2.5, 3.2)
    ax2.set_aspect('equal')
    ax2.legend(fontsize=8.5, framealpha=0.85, loc='upper left')
    ax2.set_title('(b) Parallel Transport & Covariant Derivative\nChristoffel Symbols $\\Gamma^\\nu_{\\mu\\lambda}$',
                 fontsize=10.5, fontweight='bold', color=COLORS['accent'], pad=8)

    fig.suptitle('Metric Tensor, Covariant Calculus & Parallel Transport', fontsize=13,
                 fontweight='bold', color=COLORS['text'], y=0.97)
    fname = os.path.join(OUT_DIR, 'metric_tensor_covariant.png')
    fig.savefig(fname, dpi=150, bbox_inches='tight')
    plt.close(fig)
    print("[OK] Created metric_tensor_covariant.png")


# =========================================================================
# DIAGRAM 5: Electromagnetic Field Tensor (F_mu_nu)
# =========================================================================
def generate_em_field_tensor():
    fig, axes = plt.subplots(1, 2, figsize=(13, 5.5), dpi=150)
    fig.subplots_adjust(left=0.06, right=0.96, top=0.85, bottom=0.12, wspace=0.3)

    # (a) F^{mu nu} matrix visualization
    ax = axes[0]
    ax.set_xlim(-1, 6)
    ax.set_ylim(-1.5, 6)
    ax.set_aspect('equal')
    ax.axis('off')
    ax.set_title('(a) Electromagnetic Field Tensor $F^{\\mu\\nu}$\nAntisymmetric Rank-2 Tensor in Minkowski Space',
                 fontsize=10.5, fontweight='bold', color=COLORS['accent'], pad=8)

    labels_em = [
        ['$0$', '$-E_x/c$', '$-E_y/c$', '$-E_z/c$'],
        ['$E_x/c$', '$0$', '$-B_z$', '$B_y$'],
        ['$E_y/c$', '$B_z$', '$0$', '$-B_x$'],
        ['$E_z/c$', '$-B_y$', '$B_x$', '$0$'],
    ]
    row_labels = ['$\\mu=0$', '$\\mu=1$', '$\\mu=2$', '$\\mu=3$']
    col_labels = ['$\\nu=0$', '$\\nu=1$', '$\\nu=2$', '$\\nu=3$']

    for i in range(4):
        for j in range(4):
            if i == j:
                fc = COLORS['dim']
                alpha = 0.08
                tc = COLORS['dim']
            elif i < j:
                fc = COLORS['rose']
                alpha = 0.12
                tc = COLORS['rose']
            else:
                fc = COLORS['accent']
                alpha = 0.12
                tc = COLORS['accent']

            rect = Rectangle((j * 1.2 + 0.5, (3 - i) * 1.2 + 0.5), 1.05, 1.05,
                              facecolor=fc, alpha=alpha,
                              edgecolor=fc, lw=1.5, zorder=2)
            ax.add_patch(rect)
            ax.text(j * 1.2 + 1.025, (3 - i) * 1.2 + 1.025, labels_em[i][j],
                    fontsize=10, ha='center', va='center', color=tc, fontweight='bold', zorder=3)

    # Row / col labels
    for i, lbl in enumerate(row_labels):
        ax.text(-0.1, (3 - i) * 1.2 + 1.025, lbl, fontsize=9, ha='right', va='center', color=COLORS['dim'])
    for j, lbl in enumerate(col_labels):
        ax.text(j * 1.2 + 1.025, 5.2, lbl, fontsize=9, ha='center', va='bottom', color=COLORS['dim'])

    # Antisymmetry note
    ax.text(2.9, -0.8, '$F^{\\mu\\nu} = -F^{\\nu\\mu}$  (antisymmetric)',
            fontsize=10, ha='center', color=COLORS['amber'], fontweight='bold')

    # (b) EM field lines and B field
    ax2 = axes[1]
    # Electric field radial from charge
    xx = np.linspace(-2.5, 2.5, 16)
    yy = np.linspace(-2.5, 2.5, 16)
    Xg, Yg = np.meshgrid(xx, yy)

    # Point charge E field
    r = np.sqrt(Xg**2 + Yg**2)
    r = np.where(r < 0.3, 0.3, r)
    Ex = Xg / r**3
    Ey = Yg / r**3
    mag = np.sqrt(Ex**2 + Ey**2)
    mag = np.where(mag < 0.01, 0.01, mag)

    ax2.streamplot(Xg, Yg, Ex, Ey, color=np.log10(mag + 0.01), cmap='cool',
                   density=1.5, linewidth=1.2, arrowsize=1.2, arrowstyle='->')

    # Charge at center
    ax2.plot(0, 0, 'o', color=COLORS['rose'], ms=14, zorder=5)
    ax2.text(0.2, -0.35, '$+Q$', fontsize=11, color=COLORS['rose'], fontweight='bold')

    # B field circles (from current)
    for r_b in [0.8, 1.5, 2.2]:
        circle = plt.Circle((0, 0), r_b, fill=False, color=COLORS['amber'],
                            lw=1.5, ls='--', alpha=0.5)
        ax2.add_patch(circle)

    # Current symbol (dot for out of page)
    ax2.plot(0, 0, '.', color=COLORS['amber'], ms=8, zorder=6)

    ax2.text(-2.3, 2.3, '$\\vec{E}$ field lines', fontsize=10, color=COLORS['accent'], fontweight='bold')
    ax2.text(0.8, 2.3, '$\\vec{B}$ circles', fontsize=10, color=COLORS['amber'], fontweight='bold')

    ax2.set_xlim(-2.8, 2.8)
    ax2.set_ylim(-2.8, 2.8)
    ax2.set_aspect('equal')
    ax2.set_xlabel('x', fontsize=9)
    ax2.set_ylabel('y', fontsize=9)
    ax2.set_title('(b) Electric & Magnetic Field Visualization\n$\\vec{E}$ (radial) and $\\vec{B}$ (azimuthal) Components',
                 fontsize=10.5, fontweight='bold', color=COLORS['accent'], pad=8)

    fig.suptitle('Electromagnetic Field Tensor $F^{\\mu\\nu}$: Unifying $\\vec{E}$ and $\\vec{B}$',
                 fontsize=13, fontweight='bold', color=COLORS['text'], y=0.97)
    fname = os.path.join(OUT_DIR, 'em_field_tensor.png')
    fig.savefig(fname, dpi=150, bbox_inches='tight')
    plt.close(fig)
    print("[OK] Created em_field_tensor.png")


# =========================================================================
# Run all generators
# =========================================================================
if __name__ == '__main__':
    print("=== Generating Module 2 Tensor Visualization Diagrams ===")
    generate_tensor_basics()
    generate_stress_tensor()
    generate_inertia_tensor()
    generate_metric_tensor()
    generate_em_field_tensor()
    print("=== All 5 tensor diagrams generated successfully ===")
