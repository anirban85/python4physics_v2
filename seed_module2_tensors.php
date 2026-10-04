<?php
/**
 * Python4Physics - Seed Module 2: Tensor Analysis & Applications in Physics
 * Populates 5 subtopics and 10 interactive simulations with theory, diagrams, and solved physics problems.
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/include/menu_sync.php';

header('Content-Type: text/html; charset=utf-8');

if (!isset($conn) || $conn === null) {
    die("<h3>Database connection not available.</h3>");
}

echo "<pre>\n=== Starting Module 2 Tensor Visualization Seeding ===\n";

try {
    // 1. Ensure tables exist
    $conn->exec("CREATE TABLE IF NOT EXISTS `visualization` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `menu_id` int(11) NOT NULL DEFAULT 1,
      `submenu_id` int(11) NOT NULL DEFAULT 1,
      `program_id` int(11) DEFAULT 1,
      `content` longtext DEFAULT NULL,
      `algo` longtext DEFAULT NULL,
      `explanation` longtext DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->exec("CREATE TABLE IF NOT EXISTS `p4p_menus` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `language` varchar(32) NOT NULL,
      `menu_id` int(11) NOT NULL,
      `title` varchar(255) NOT NULL,
      `sort_order` int(11) DEFAULT 0,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `lang_menu_unique` (`language`,`menu_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->exec("CREATE TABLE IF NOT EXISTS `p4p_submenus` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `language` varchar(32) NOT NULL,
      `menu_id` int(11) NOT NULL,
      `submenu_id` int(11) NOT NULL,
      `title` varchar(255) NOT NULL,
      `sort_order` int(11) DEFAULT 0,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `lang_menu_sub_unique` (`language`,`menu_id`,`submenu_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Check if Module 2 is currently Quantum Mechanics. If so, shift it to Module 3, and shift Optics to Module 4.
    $checkM2 = $conn->prepare("SELECT title FROM p4p_menus WHERE language='visualization' AND menu_id = 2");
    $checkM2->execute();
    $m2_title = $checkM2->fetchColumn();

    if ($m2_title && !str_contains(strtolower($m2_title), 'tensor')) {
        echo "[INFO] Shifting existing Module 2 ({$m2_title}) and higher modules to make room for Module 2 Tensors...\n";

        // Shift Module 3 (Optics) to Module 4 if needed
        $checkM3 = $conn->prepare("SELECT title FROM p4p_menus WHERE language='visualization' AND menu_id = 3");
        $checkM3->execute();
        $m3_title = $checkM3->fetchColumn();

        if ($m3_title) {
            // Delete any existing menu 4 to avoid conflict
            $conn->exec("DELETE FROM p4p_menus WHERE language='visualization' AND menu_id = 4");
            $conn->exec("DELETE FROM p4p_submenus WHERE language='visualization' AND menu_id = 4");
            $conn->exec("DELETE FROM visualization WHERE menu_id = 4");

            $conn->exec("UPDATE p4p_menus SET menu_id = 4, sort_order = 4 WHERE language='visualization' AND menu_id = 3");
            $conn->exec("UPDATE p4p_submenus SET menu_id = 4, sort_order = 4 WHERE language='visualization' AND menu_id = 3");
            $conn->exec("UPDATE visualization SET menu_id = 4 WHERE menu_id = 3");
            echo "  [OK] Shifted {$m3_title} to Module 4\n";
        }

        // Shift Module 2 to Module 3
        $conn->exec("DELETE FROM p4p_menus WHERE language='visualization' AND menu_id = 3");
        $conn->exec("DELETE FROM p4p_submenus WHERE language='visualization' AND menu_id = 3");
        $conn->exec("DELETE FROM visualization WHERE menu_id = 3");

        $conn->exec("UPDATE p4p_menus SET menu_id = 3, sort_order = 3 WHERE language='visualization' AND menu_id = 2");
        $conn->exec("UPDATE p4p_submenus SET menu_id = 3, sort_order = 3 WHERE language='visualization' AND menu_id = 2");
        $conn->exec("UPDATE visualization SET menu_id = 3 WHERE menu_id = 2");
        echo "  [OK] Shifted {$m2_title} to Module 3\n";
    }

    // 3. Upsert Module 2: Tensor Analysis & Applications in Physics
    $stmt = $conn->prepare("INSERT INTO p4p_menus (language, menu_id, title, sort_order) 
                            VALUES ('visualization', 2, 'Tensor Analysis & Applications in Physics', 2)
                            ON DUPLICATE KEY UPDATE title = 'Tensor Analysis & Applications in Physics', sort_order = 2");
    $stmt->execute();
    echo "[OK] Module 2 in p4p_menus set to 'Tensor Analysis & Applications in Physics'\n";

    // 4. Upsert 5 Submenus for Module 2
    $submenus = [
        1 => 'Tensor Basics, Rank Hierarchy & Coordinate Transformations',
        2 => 'Cauchy Stress Tensor & Mohr’s Circle',
        3 => 'Moment of Inertia Tensor & Rotational Dynamics',
        4 => 'Metric Tensor, Christoffel Symbols & Covariant Differentiation',
        5 => 'Electromagnetic Field Tensor & Relativistic Invariants'
    ];

    foreach ($submenus as $sid => $stitle) {
        $stmt = $conn->prepare("INSERT INTO p4p_submenus (language, menu_id, submenu_id, title, sort_order) 
                                VALUES ('visualization', 2, :sid, :stitle, :sort)
                                ON DUPLICATE KEY UPDATE title = VALUES(title), sort_order = VALUES(sort_order)");
        $stmt->execute([':sid' => $sid, ':stitle' => $stitle, ':sort' => $sid]);
        echo "  [OK] Subtopic 2.{$sid}: {$stitle}\n";
    }

    // Remove any previous simulations in Module 2 before re-populating with fresh comprehensive content
    $conn->exec("DELETE FROM visualization WHERE menu_id = 2");

    $insertProg = $conn->prepare("INSERT INTO visualization (menu_id, submenu_id, program_id, content, algo, explanation) 
                                  VALUES (2, :sub_id, :prog_id, :content, :algo, :explanation)");

    // =========================================================================
    // SUBTOPIC 2.1: Tensor Basics, Rank Hierarchy & Coordinate Transformations
    // =========================================================================
    
    // Program 2.1.1: Rank Hierarchy & Tensor Transformation Studio
    $p1_1_content = <<<'PYCODE'
"""
Tensor Rank Hierarchy & Coordinate Transformation Studio
Interactive exploration of Rank-0 Scalars (invariant), Rank-1 Vectors (v'_i = R_ij v_j), 
and Rank-2 Tensors (T'_ij = R_im R_jn T_mn) under 2D coordinate frame rotation.
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider
from matplotlib.patches import Ellipse

fig, axes = plt.subplots(1, 3, figsize=(13.5, 4.8))
plt.subplots_adjust(bottom=0.28, top=0.88, left=0.06, right=0.96, wspace=0.32)

# Initial parameters
theta0 = 30.0   # Rotation angle in degrees
lam0 = 1.8      # Eigenvalue anisotropy ratio

# Setup coordinate grid
grid_x, grid_y = np.meshgrid(np.linspace(-2.5, 2.5, 80), np.linspace(-2.5, 2.5, 80))

def update_views(theta_deg, lam):
    th = np.radians(theta_deg)
    R = np.array([[np.cos(th), np.sin(th)],
                  [-np.sin(th), np.cos(th)]])  # Coordinate rotation matrix
    
    # ----------------------------------------------------
    # Subplot 1: Rank-0 Scalar Field (Invariant under rotation)
    # ----------------------------------------------------
    ax1 = axes[0]
    ax1.cla()
    # Temperature scalar field: T(x, y) = 300 * exp(-(x^2 + y^2)/2.0)
    T = 300.0 * np.exp(-(grid_x**2 + grid_y**2) / 2.0)
    cs = ax1.contourf(grid_x, grid_y, T, levels=12, cmap='inferno', alpha=0.85)
    
    # Rotated axes x', y'
    axis_len = 2.0
    xp_axis = axis_len * np.array([np.cos(th), np.sin(th)])
    yp_axis = axis_len * np.array([-np.sin(th), np.cos(th)])
    ax1.quiver(0, 0, xp_axis[0], xp_axis[1], color='#38bdf8', angles='xy', scale_units='xy', scale=1, lw=2.2, label="x' axis")
    ax1.quiver(0, 0, yp_axis[0], yp_axis[1], color='#a855f7', angles='xy', scale_units='xy', scale=1, lw=2.2, label="y' axis")
    
    # Value at probe point P(1.2, 0.8)
    P = np.array([1.2, 0.8])
    T_P = 300.0 * np.exp(-(P[0]**2 + P[1]**2) / 2.0)
    P_rot = R @ P
    ax1.plot(P[0], P[1], 'o', color='#10b981', ms=8, label=f'Probe P: T={T_P:.1f} K')
    ax1.set_xlim(-2.5, 2.5)
    ax1.set_ylim(-2.5, 2.5)
    ax1.set_aspect('equal')
    ax1.set_title(f'Rank 0 (Scalar): Invariant\nT(P) = {T_P:.1f} K (No change with θ)', fontsize=10, fontweight='bold', color='#38bdf8')
    ax1.legend(loc='lower left', fontsize=7.5, framealpha=0.85)
    ax1.grid(True, linestyle=':', alpha=0.35)

    # ----------------------------------------------------
    # Subplot 2: Rank-1 Vector Field Transformation
    # ----------------------------------------------------
    ax2 = axes[1]
    ax2.cla()
    # Original vector v in frame S
    v = np.array([2.0, 1.2])
    v_prime = R @ v
    
    # Draw original coordinate axes (gray)
    ax2.axhline(0, color='#64748b', lw=1, linestyle='--')
    ax2.axvline(0, color='#64748b', lw=1, linestyle='--')
    
    # Draw rotated coordinate axes
    ax2.quiver(0, 0, xp_axis[0], xp_axis[1], color='#38bdf8', angles='xy', scale_units='xy', scale=1, lw=1.8, alpha=0.7)
    ax2.quiver(0, 0, yp_axis[0], yp_axis[1], color='#a855f7', angles='xy', scale_units='xy', scale=1, lw=1.8, alpha=0.7)
    
    # Vector v
    ax2.quiver(0, 0, v[0], v[1], color='#10b981', angles='xy', scale_units='xy', scale=1, lw=3.2, label=f'Vector v (|v|={np.linalg.norm(v):.2f})')
    
    ax2.set_xlim(-2.5, 2.5)
    ax2.set_ylim(-2.5, 2.5)
    ax2.set_aspect('equal')
    ax2.set_title(f'Rank 1 (Vector): v\'_i = R_ij v_j\nS: ({v[0]:.2f}, {v[1]:.2f}) → S\': ({v_prime[0]:.2f}, {v_prime[1]:.2f})', fontsize=10, fontweight='bold', color='#10b981')
    ax2.legend(loc='lower left', fontsize=7.5, framealpha=0.85)
    ax2.grid(True, linestyle=':', alpha=0.35)

    # ----------------------------------------------------
    # Subplot 3: Rank-2 Tensor Transformation (Quadric Ellipse)
    # ----------------------------------------------------
    ax3 = axes[2]
    ax3.cla()
    # Tensor in principal frame: T_diag = diag(1, 1/lam^2)
    # Under rotation by th: T' = R T R^T
    T_orig = np.array([[2.5, 0.5],
                       [0.5, 1.0]])
    T_rot = R @ T_orig @ R.T
    
    # Invariants
    I1 = np.trace(T_orig)
    I2 = np.linalg.det(T_orig)
    I1_rot = np.trace(T_rot)
    I2_rot = np.linalg.det(T_rot)
    
    # Tensor representation quadric: x^T T x = 1 (ellipse)
    eigvals, eigvecs = np.linalg.eigh(T_orig)
    a = 1.0 / np.sqrt(eigvals[0])
    b = 1.0 / np.sqrt(eigvals[1])
    angle_deg = np.degrees(np.arctan2(eigvecs[1, 0], eigvecs[0, 0]))
    
    ell = Ellipse(xy=(0, 0), width=2*a, height=2*b, angle=angle_deg,
                  edgecolor='#f59e0b', facecolor='none', lw=2.5, linestyle='-', label='Tensor Quadric x^T T x = 1')
    ax3.add_patch(ell)
    
    # Principal directions
    ax3.quiver(0, 0, a*eigvecs[0, 0], a*eigvecs[1, 0], color='#f43f5e', angles='xy', scale_units='xy', scale=1, lw=2, label=f'λ1={eigvals[0]:.2f}')
    ax3.quiver(0, 0, b*eigvecs[0, 1], b*eigvecs[1, 1], color='#38bdf8', angles='xy', scale_units='xy', scale=1, lw=2, label=f'λ2={eigvals[1]:.2f}')
    
    ax3.set_xlim(-2.5, 2.5)
    ax3.set_ylim(-2.5, 2.5)
    ax3.set_aspect('equal')
    ax3.set_title(f'Rank 2 (Tensor): T\'_ij = R_im R_jn T_mn\nTr(T)={I1_rot:.2f}, det(T)={I2_rot:.2f} (Invariants!)', fontsize=10, fontweight='bold', color='#f59e0b')
    ax3.legend(loc='lower left', fontsize=7.5, framealpha=0.85)
    ax3.grid(True, linestyle=':', alpha=0.35)

update_views(theta0, lam0)

# Sliders
ax_th = plt.axes([0.15, 0.12, 0.70, 0.03])
ax_lam = plt.axes([0.15, 0.05, 0.70, 0.03])

s_th = Slider(ax_th, 'Frame Rotation θ (°)', 0.0, 360.0, valinit=theta0, valstep=2.0)
s_lam = Slider(ax_lam, 'Anisotropy λ', 0.8, 3.5, valinit=lam0, valstep=0.1)

def update(val):
    update_views(s_th.val, s_lam.val)
    fig.canvas.draw_idle()

s_th.on_changed(update)
s_lam.on_changed(update)

print("Tensor Rank Hierarchy & Transformation Studio Loaded.")
print("Observe: Rank-0 is invariant; Rank-1 components transform as R v; Rank-2 components transform as R T R^T.")
print("The Trace and Determinant of the Rank-2 tensor remain strictly invariant under all rotations!")
plt.show()
PYCODE;

    $p1_1_algo = "Tensor Rank Hierarchy & Coordinate Transformation Studio: Rank-0 Scalars, Rank-1 Vectors, and Rank-2 Tensors under Coordinate Rotation (Trace & Determinant Invariants)";

    $p1_1_explanation = '<div class="theory-article">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <img src="{{SITEURL}}assets/images/visualization/tensors/tensor_basics_rank_hierarchy.png" alt="Tensor Basics and Rank Hierarchy" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <p style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem;"><strong>Figure 2.1:</strong> Tensor Rank Hierarchy: (a) Rank-0 scalar field (temperature $T$), (b) Rank-1 vector field (electric dipole $\vec{E}$), (c) Rank-2 tensor transformation quadric ($x^T T x = 1$) under frame rotation showing strict invariance of trace and determinant.</p>
    </div>

    <div style="background: rgba(56, 189, 248, 0.08); border-left: 4px solid var(--accent); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--accent); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-shapes"></i> What is a Tensor in Physics?
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            A <strong>tensor</strong> is a geometric, coordinate-independent physical entity that establishes a linear multilinear relationship between geometric vectors. Crucially, a tensor is defined by how its numerical components transform under a change of coordinate basis. Physical laws must be form-invariant (covariant) under coordinate transformations.
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. The Tensor Rank Hierarchy
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        In an $N$-dimensional space (typically $N=3$ in Newtonian physics or $N=4$ in Special Relativity), the rank of a tensor determines how many indices it carries and how many components describe it ($N^{\text{rank}}$):
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 0.95rem; line-height: 1.6;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid var(--card-border); color: var(--accent);">
                    <th style="padding: 6px;">Rank</th>
                    <th style="padding: 6px;">Entity</th>
                    <th style="padding: 6px;">Components ($N=3$)</th>
                    <th style="padding: 6px;">Transformation Law</th>
                    <th style="padding: 6px;">Physical Examples</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 6px;"><strong>0</strong></td>
                    <td style="padding: 6px;">Scalar</td>
                    <td style="padding: 6px;">$3^0 = 1$</td>
                    <td style="padding: 6px;">$S\' = S$</td>
                    <td style="padding: 6px;">Temperature $T$, Mass $m$, Energy $E$, Pressure $p$</td>
                </tr>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 6px;"><strong>1</strong></td>
                    <td style="padding: 6px;">Vector</td>
                    <td style="padding: 6px;">$3^1 = 3$</td>
                    <td style="padding: 6px;">$v\'_i = R_{ij} v_j$</td>
                    <td style="padding: 6px;">Displacement $\vec{r}$, Velocity $\vec{v}$, Force $\vec{F}$, Electric Field $\vec{E}$</td>
                </tr>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 6px;"><strong>2</strong></td>
                    <td style="padding: 6px;">Dyad / Matrix</td>
                    <td style="padding: 6px;">$3^2 = 9$</td>
                    <td style="padding: 6px;">$T\'_{ij} = R_{im} R_{jn} T_{mn}$</td>
                    <td style="padding: 6px;">Stress $[\sigma]$, Strain $[\varepsilon]$, Inertia $[I]$, Metric $[g]$, Conductivity $[K]$</td>
                </tr>
                <tr>
                    <td style="padding: 6px;"><strong>4</strong></td>
                    <td style="padding: 6px;">4th-Order Tensor</td>
                    <td style="padding: 6px;">$3^4 = 81$</td>
                    <td style="padding: 6px;">$C\'_{ijkl} = R_{ia} R_{jb} R_{kc} R_{ld} C_{abcd}$</td>
                    <td style="padding: 6px;">Elastic Stiffness Tensor $C_{ijkl}$, Riemann Curvature $R^\rho_{\ \sigma\mu\nu}$</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Transformation Law for Rank-2 Tensors
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Consider an orthogonal rotation matrix $R$ between coordinate frame $S$ and rotated frame $S\'$, satisfying $R R^T = \mathbb{I}$. The transformation of a rank-2 Cartesian tensor in index notation and matrix notation is:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$T\'_{ij} = \sum_{m=1}^3 \sum_{n=1}^3 R_{im} R_{jn} T_{mn} \iff [T\'] = [R][T][R]^T$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Notice that each tensor index transforms with one factor of the rotation matrix $R$.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Fundamental Tensor Invariants
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        While individual matrix components $T_{ij}$ change with coordinate rotation, certain scalar combinations remain completely <strong>invariant</strong>:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$I_1 = \mathrm{Tr}(T) = T_{ii} = T_{11} + T_{22} + T_{33} \quad (\text{First Invariant})$$
        $$I_2 = \frac{1}{2}\left[(\mathrm{Tr}(T))^2 - \mathrm{Tr}(T^2)\right] = T_{11}T_{22} + T_{22}T_{33} + T_{33}T_{11} - T_{12}^2 - T_{23}^2 - T_{31}^2$$
        $$I_3 = \det(T) \quad (\text{Third Invariant})$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        In the interactive studio above, rotate the coordinate frame to see that the trace $\mathrm{Tr}(T)$ and determinant $\det(T)$ are constant regardless of $\theta$!
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 1,
        ':prog_id'     => 1,
        ':content'     => $p1_1_content,
        ':algo'        => $p1_1_algo,
        ':explanation' => $p1_1_explanation
    ]);
    echo "  [OK] Inserted Program 2.1.1 (Tensor Rank Studio)\n";

    // Program 2.1.2: Physics Problem: Anisotropic Thermal Conductivity
    $p1_2_content = <<<'PYCODE'
"""
Physics Problem Simulation: Anisotropic Thermal Conductivity & Directional Heat Flux
Fourier's law in anisotropic crystalline media: q_i = -K_ij * (grad T)_j.
Demonstrates that heat flux vector q is generally NOT collinear with temperature gradient -grad T,
calculating the non-collinearity angle delta(theta) as the crystal is rotated.
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider

fig, axes = plt.subplots(1, 2, figsize=(12.5, 5.2))
plt.subplots_adjust(bottom=0.25, top=0.88, left=0.08, right=0.94, wspace=0.32)

# Crystal principal conductivities (W / m*K)
k1_init = 65.0   # Along principal crystal axis (e.g. graphite basal plane)
k2_init = 15.0   # Across principal crystal axis (e.g. c-axis)
theta_c_init = 40.0 # Crystal orientation relative to x-axis in degrees
grad_T_mag = 50.0   # K / m

def solve_heat_conduction(k1, k2, theta_deg):
    th = np.radians(theta_deg)
    # Rotation matrix from crystal principal frame to lab frame
    R = np.array([[np.cos(th), -np.sin(th)],
                  [np.sin(th),  np.cos(th)]])
    K_principal = np.array([[k1, 0.0],
                            [0.0, k2]])
    # Lab frame conductivity tensor K_lab = R * K_principal * R^T
    K_lab = R @ K_principal @ R.T
    
    # Applied temperature gradient along x-axis: grad T = (dT/dx, 0)^T
    # So -grad T is directed along +x axis
    neg_grad_T = np.array([grad_T_mag, 0.0])
    
    # Heat flux vector q = -K * grad T = K_lab * (-grad T)
    q = K_lab @ neg_grad_T
    
    # Collinearity angle delta = angle between -grad T and q
    cos_delta = q[0] / np.linalg.norm(q)
    delta_deg = np.degrees(np.arccos(np.clip(cos_delta, -1.0, 1.0)))
    if q[1] < 0:
        delta_deg = -delta_deg
        
    return K_lab, q, delta_deg, R

def update_plots(k1, k2, theta_deg):
    K_lab, q, delta_deg, R = solve_heat_conduction(k1, k2, theta_deg)
    
    # ----------------------------------------------------
    # Subplot 1: Vector Field & Crystal Alignment in Lab Frame
    # ----------------------------------------------------
    ax1 = axes[0]
    ax1.cla()
    ax1.set_facecolor('#1e293b')
    ax1.grid(True, linestyle=':', alpha=0.35, color='#475569')
    
    # Plot crystal principal axes
    axis_scale = 1.6
    c1 = axis_scale * R[:, 0]
    c2 = axis_scale * R[:, 1]
    ax1.plot([-c1[0], c1[0]], [-c1[1], c1[1]], color='#94a3b8', lw=2.2, linestyle='--', label=f'Fast Axis (k1={k1:.0f})')
    ax1.plot([-c2[0], c2[0]], [-c2[1], c2[1]], color='#64748b', lw=1.8, linestyle=':', label=f'Slow Axis (k2={k2:.0f})')
    
    # Plot applied -grad T (along x-axis)
    scale_vec = 1.8 / (k1 * grad_T_mag)
    neg_grad_vis = np.array([1.5, 0.0])
    ax1.quiver(0, 0, neg_grad_vis[0], neg_grad_vis[1], color='#38bdf8', angles='xy', scale_units='xy', scale=1, lw=3.2,
               label=r'Driving $-\nabla T$ Vector')
    
    # Plot resulting heat flux vector q
    q_vis = q * scale_vec
    ax1.quiver(0, 0, q_vis[0], q_vis[1], color='#f43f5e', angles='xy', scale_units='xy', scale=1, lw=3.2,
               label=r'Heat Flux $\vec{q} = -[K]\nabla T$')
    
    # Draw deflection angle arc
    r_arc = 0.8
    angles_arc = np.linspace(0, np.radians(delta_deg), 30)
    ax1.plot(r_arc * np.cos(angles_arc), r_arc * np.sin(angles_arc), color='#f59e0b', lw=2.0)
    ax1.text(r_arc * 1.15 * np.cos(np.radians(delta_deg)/2), r_arc * 1.15 * np.sin(np.radians(delta_deg)/2),
             f'δ = {delta_deg:.1f}°', color='#f59e0b', fontweight='bold', fontsize=10.5)
    
    ax1.set_xlim(-2.0, 2.0)
    ax1.set_ylim(-2.0, 2.0)
    ax1.set_aspect('equal')
    ax1.set_xlabel('Lab X (m)', fontsize=9)
    ax1.set_ylabel('Lab Y (m)', fontsize=9)
    ax1.set_title(r'Anisotropic Non-Collinear Heat Conduction' + f'\n|q| = {np.linalg.norm(q):.0f} W/m², Deflection δ = {delta_deg:.1f}°',
                  fontsize=10.5, color='#38bdf8', fontweight='bold')
    ax1.legend(loc='lower left', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')

    # ----------------------------------------------------
    # Subplot 2: Deflection Angle delta vs Crystal Angle theta
    # ----------------------------------------------------
    ax2 = axes[1]
    ax2.cla()
    ax2.set_facecolor('#1e293b')
    ax2.grid(True, linestyle=':', alpha=0.35, color='#475569')
    
    theta_range = np.linspace(0, 180, 180)
    delta_curve = []
    for th_val in theta_range:
        _, _, d_val, _ = solve_heat_conduction(k1, k2, th_val)
        delta_curve.append(d_val)
    
    ax2.plot(theta_range, delta_curve, color='#38bdf8', lw=2.5, label=r'Deflection $\delta(\theta)$')
    # Marker at current theta
    ax2.plot(theta_deg, delta_deg, 'o', color='#f43f5e', ms=9, zorder=5, label=f'Current: θ={theta_deg:.0f}°, δ={delta_deg:.1f}°')
    
    # Maximum deflection line
    max_d = np.max(delta_curve)
    max_th = theta_range[np.argmax(delta_curve)]
    ax2.axhline(max_d, color='#f59e0b', linestyle='--', alpha=0.6, label=f'Max δ = {max_d:.1f}° at θ = {max_th:.0f}°')
    
    ax2.set_xlim(0, 180)
    ax2.set_ylim(-max_d*1.2, max_d*1.2)
    ax2.set_xlabel(r'Crystal Cut Angle $\theta$ (°)', fontsize=9)
    ax2.set_ylabel(r'Heat Deflection Angle $\delta$ (°)', fontsize=9)
    ax2.set_title(r'Angular Deflection Curve $\delta(\theta) = \arctan(q_y / q_x)$', fontsize=10.5, color='#f59e0b', fontweight='bold')
    ax2.legend(loc='upper right', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')

update_plots(k1_init, k2_init, theta_c_init)

# Sliders
ax_th = plt.axes([0.15, 0.12, 0.70, 0.03])
ax_ratio = plt.axes([0.15, 0.05, 0.70, 0.03])

s_th = Slider(ax_th, 'Crystal Angle θ (°)', 0.0, 180.0, valinit=theta_c_init, valstep=1.0)
s_ratio = Slider(ax_ratio, 'k1 / k2 Ratio', 1.0, 10.0, valinit=k1_init/k2_init, valstep=0.2)

def update(val):
    ratio = s_ratio.val
    k1 = 15.0 * ratio
    update_plots(k1, 15.0, s_th.val)
    fig.canvas.draw_idle()

s_th.on_changed(update)
s_ratio.on_changed(update)

print("Anisotropic Thermal Conduction Simulation Loaded.")
print("Fourier's law in tensor form: q = -[K] grad T.")
print("Notice how heat does not flow along the temperature gradient when crystal axes are inclined!")
plt.show()
PYCODE;

    $p1_2_algo = "Physics Problem: Anisotropic Thermal Conductivity & Directional Heat Flux Vector under Arbitrary Coordinate Rotation";

    $p1_2_explanation = '<div class="theory-article">
    <div style="background: rgba(16, 185, 129, 0.08); border-left: 4px solid var(--emerald); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--emerald); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-fire-burner"></i> Problem Statement: Anisotropic Heat Flow
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            In isotropic materials, heat flows strictly anti-parallel to the thermal gradient ($\vec{q} = -k \nabla T$). However, in anisotropic crystals (such as graphite, quartz, or bismuth telluride composites), the thermal conductivity is a rank-2 symmetric tensor $[K]$. 
            Given principal conductivities $k_1$ and $k_2$ along orthogonal crystal axes, determine the heat flux vector $\vec{q}$ and its angular deviation $\delta$ from $-\nabla T$ when the crystal axis is inclined at an angle $\theta$ relative to an applied horizontal gradient.
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Fourier\'s Law in General Tensor Form
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Fourier\'s law for an anisotropic medium is expressed in index notation as:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$q_i = -\sum_{j=1}^3 K_{ij} \frac{\partial T}{\partial x_j} \iff \vec{q} = -[K]\nabla T$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        From the Onsager reciprocal relations, the thermal conductivity tensor is strictly symmetric: $K_{ij} = K_{ji}$.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Coordinate Rotation of the Conductivity Tensor
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        In the crystal\'s principal coordinate frame, $[K]$ is diagonal:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$[K]_{\text{principal}} = \begin{pmatrix} k_1 & 0 \\ 0 & k_2 \end{pmatrix}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Rotating the crystal by angle $\theta$ relative to the laboratory $x$-axis via rotation matrix $R(\theta)$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$[K]_{\text{lab}} = R(\theta) [K]_{\text{principal}} R^T(\theta) = \begin{pmatrix} k_1\cos^2\theta + k_2\sin^2\theta & (k_1 - k_2)\sin\theta\cos\theta \\ (k_1 - k_2)\sin\theta\cos\theta & k_1\sin^2\theta + k_2\cos^2\theta \end{pmatrix}$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Heat Flux Components and Angular Deflection
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        For an applied gradient $-\nabla T = (G_0, 0)^T$ along the laboratory $x$-axis:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$q_x = (k_1\cos^2\theta + k_2\sin^2\theta) G_0, \qquad q_y = (k_1 - k_2)\sin\theta\cos\theta\, G_0$$
        $$\tan\delta = \frac{q_y}{q_x} = \frac{(k_1 - k_2)\sin 2\theta}{(k_1 + k_2) + (k_1 - k_2)\cos 2\theta}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        <strong>Key Insights:</strong>
        <br>&bull; If $\theta = 0^\circ$ or $90^\circ$, $q_y = 0$, so $\vec{q} \parallel -\nabla T$ (flow along principal symmetry directions).
        <br>&bull; For intermediate angles, $q_y \neq 0$, generating a sideways transverse heat current!
        <br>&bull; The maximum deflection occurs at $\cos 2\theta = -(k_1 - k_2)/(k_1 + k_2)$, with $\sin\delta_{\max} = \frac{k_1 - k_2}{k_1 + k_2}$.
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 1,
        ':prog_id'     => 2,
        ':content'     => $p1_2_content,
        ':algo'        => $p1_2_algo,
        ':explanation' => $p1_2_explanation
    ]);
    echo "  [OK] Inserted Program 2.1.2 (Anisotropic Heat Conduction)\n";

    // =========================================================================
    // SUBTOPIC 2.2: Cauchy Stress Tensor & Mohr's Circle
    // =========================================================================

    // Program 2.2.1: Cauchy Stress Tensor & Mohr's Circle Studio
    $p2_1_content = <<<'PYCODE'
"""
Cauchy Stress Tensor & Mohr's Circle 2D/3D Studio
Calculates traction vector T^(n) = [sigma] * n on arbitrary inclined plane,
normal stress sigma_n, shear stress tau_n, principal stresses (sigma_1, sigma_2),
and plots the dynamic Mohr's Circle with live stress element rotation.
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider
from matplotlib.patches import Rectangle, Arc

fig, axes = plt.subplots(1, 2, figsize=(13.0, 5.2))
plt.subplots_adjust(bottom=0.28, top=0.88, left=0.08, right=0.94, wspace=0.32)

# Initial stress state (MPa)
sigma_x0 = 80.0
sigma_y0 = 30.0
tau_xy0 = 25.0
theta_cut0 = 25.0 # Degrees

def solve_mohr(sx, sy, txy, th_deg):
    th = np.radians(th_deg)
    # Center and Radius of Mohr's circle
    sigma_avg = (sx + sy) / 2.0
    R = np.sqrt(((sx - sy) / 2.0)**2 + txy**2)
    
    # Principal stresses
    sigma_1 = sigma_avg + R
    sigma_2 = sigma_avg - R
    tau_max = R
    
    # Principal angle theta_p
    theta_p = 0.5 * np.degrees(np.arctan2(2 * txy, (sx - sy)))
    
    # Stresses on plane inclined at theta:
    # sigma_n = sigma_avg + ((sx - sy)/2)*cos(2*th) + txy*sin(2*th)
    # tau_n = -((sx - sy)/2)*sin(2*th) + txy*cos(2*th)
    sigma_n = sigma_avg + ((sx - sy) / 2.0) * np.cos(2 * th) + txy * np.sin(2 * th)
    tau_n = -((sx - sy) / 2.0) * np.sin(2 * th) + txy * np.cos(2 * th)
    
    return sigma_avg, R, sigma_1, sigma_2, tau_max, theta_p, sigma_n, tau_n

def update_views(sx, sy, txy, th_deg):
    sigma_avg, R, s1, s2, tau_max, th_p, s_n, t_n = solve_mohr(sx, sy, txy, th_deg)
    
    # ----------------------------------------------------
    # Subplot 1: Mohr's Circle in (sigma, tau) Plane
    # ----------------------------------------------------
    ax1 = axes[0]
    ax1.cla()
    ax1.set_facecolor('#1e293b')
    ax1.grid(True, linestyle=':', alpha=0.35, color='#475569')
    
    # Mohr's circle
    angles = np.linspace(0, 2*np.pi, 200)
    circle_x = sigma_avg + R * np.cos(angles)
    circle_y = R * np.sin(angles)
    ax1.plot(circle_x, circle_y, color='#38bdf8', lw=2.5, label="Mohr's Circle")
    
    # Center
    ax1.plot(sigma_avg, 0, 'o', color='#38bdf8', ms=7)
    
    # Principal stress points
    ax1.plot([s1, s2], [0, 0], 's', color='#10b981', ms=8, label=f'Principal: σ1={s1:.1f}, σ2={s2:.1f}')
    
    # Max shear stress point
    ax1.plot([sigma_avg, sigma_avg], [tau_max, -tau_max], '^', color='#f59e0b', ms=8, label=f'Max Shear: τ_max={tau_max:.1f}')
    
    # Current plane state point (sigma_n, tau_n)
    ax1.plot([sigma_avg, s_n], [0, t_n], color='#f43f5e', lw=2.0, linestyle='--')
    ax1.plot(s_n, t_n, 'o', color='#f43f5e', ms=10, zorder=5, label=f'Cut Plane: σ_n={s_n:.1f}, τ_n={t_n:.1f}')
    
    ax1.axhline(0, color='#64748b', lw=1.2)
    ax1.axvline(0, color='#64748b', lw=1.2)
    
    limit = max(abs(s1), abs(s2), tau_max) * 1.35
    ax1.set_xlim(sigma_avg - limit*0.8, sigma_avg + limit*0.8)
    ax1.set_ylim(-limit*0.8, limit*0.8)
    ax1.set_aspect('equal')
    ax1.set_xlabel('Normal Stress σ (MPa)', fontsize=9)
    ax1.set_ylabel('Shear Stress τ (MPa)', fontsize=9)
    ax1.set_title(f"Mohr's Circle: R = {R:.1f} MPa | σ_avg = {sigma_avg:.1f} MPa\nPrincipal Orientation θ_p = {th_p:.1f}°",
                  fontsize=10.5, color='#38bdf8', fontweight='bold')
    ax1.legend(loc='upper right', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')

    # ----------------------------------------------------
    # Subplot 2: Physical Stress Element with Inclined Cut Plane
    # ----------------------------------------------------
    ax2 = axes[1]
    ax2.cla()
    ax2.set_facecolor('#1e293b')
    ax2.grid(True, linestyle=':', alpha=0.35, color='#475569')
    
    # Original square element
    L = 1.6
    rect = Rectangle((-L/2, -L/2), L, L, facecolor='#334155', edgecolor='#94a3b8', lw=1.5, alpha=0.4)
    ax2.add_patch(rect)
    
    # Cut plane line passing through origin at angle theta_deg
    th = np.radians(th_deg)
    nx, ny = np.cos(th), np.sin(th)       # Normal unit vector to plane
    tx, ty = -np.sin(th), np.cos(th)      # Tangent unit vector along plane
    
    cut_line_x = [-L*0.8 * tx, L*0.8 * tx]
    cut_line_y = [-L*0.8 * ty, L*0.8 * ty]
    ax2.plot(cut_line_x, cut_line_y, color='#f59e0b', lw=2.5, linestyle='-', label=f'Cut Plane (θ={th_deg:.0f}°)')
    
    # Normal unit vector n
    scale_vec = 0.8
    ax2.quiver(0, 0, nx*scale_vec, ny*scale_vec, color='#94a3b8', angles='xy', scale_units='xy', scale=1, lw=2.0, label='Normal Vector n')
    
    # Normal stress vector sigma_n * n
    s_vis = (s_n / max(s1, 1.0)) * 0.8
    ax2.quiver(0, 0, nx*s_vis, ny*s_vis, color='#10b981', angles='xy', scale_units='xy', scale=1, lw=3.0, label=f'Normal Stress σ_n ({s_n:.1f})')
    
    # Shear stress vector tau_n * t
    t_vis = (t_n / max(tau_max, 1.0)) * 0.8
    ax2.quiver(0, 0, tx*t_vis, ty*t_vis, color='#f43f5e', angles='xy', scale_units='xy', scale=1, lw=3.0, label=f'Shear Stress τ_n ({t_n:.1f})')
    
    ax2.set_xlim(-1.8, 1.8)
    ax2.set_ylim(-1.8, 1.8)
    ax2.set_aspect('equal')
    ax2.set_xlabel('X (m)', fontsize=9)
    ax2.set_ylabel('Y (m)', fontsize=9)
    ax2.set_title(f'Traction Vector on Plane θ = {th_deg:.0f}°\nσ_n = {s_n:.1f} MPa,  τ_n = {t_n:.1f} MPa',
                  fontsize=10.5, color='#10b981', fontweight='bold')
    ax2.legend(loc='lower left', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')

update_views(sigma_x0, sigma_y0, tau_xy0, theta_cut0)

# Sliders
ax_sx = plt.axes([0.15, 0.17, 0.70, 0.025])
ax_sy = plt.axes([0.15, 0.12, 0.70, 0.025])
ax_txy = plt.axes([0.15, 0.07, 0.70, 0.025])
ax_th = plt.axes([0.15, 0.02, 0.70, 0.025])

s_sx = Slider(ax_sx, 'σ_x (MPa)', -100.0, 150.0, valinit=sigma_x0, valstep=5.0)
s_sy = Slider(ax_sy, 'σ_y (MPa)', -100.0, 150.0, valinit=sigma_y0, valstep=5.0)
s_txy = Slider(ax_txy, 'τ_xy (MPa)', -80.0, 80.0, valinit=tau_xy0, valstep=2.0)
s_th = Slider(ax_th, 'Cut Angle θ (°)', 0.0, 180.0, valinit=theta_cut0, valstep=1.0)

def update(val):
    update_views(s_sx.val, s_sy.val, s_txy.val, s_th.val)
    fig.canvas.draw_idle()

s_sx.on_changed(update)
s_sy.on_changed(update)
s_txy.on_changed(update)
s_th.on_changed(update)

print("Cauchy Stress Tensor & Mohr's Circle Studio Loaded.")
print("Rotate cut plane angle theta to witness (sigma_n, tau_n) trace Mohr's Circle at frequency 2*theta!")
plt.show()
PYCODE;

    $p2_1_algo = "Cauchy Stress Tensor & Mohr's Circle 2D/3D Studio: Traction Vectors, Principal Stresses, and Maximum In-Plane Shear";

    $p2_1_explanation = '<div class="theory-article">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <img src="{{SITEURL}}assets/images/visualization/tensors/stress_tensor_mohrs_circle.png" alt="Cauchy Stress Tensor and Mohrs Circle" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <p style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem;"><strong>Figure 2.2:</strong> Cauchy Stress Tensor &amp; Mohr\'s Circle: (a) Traction vector $\vec{T}^{(\hat{n})} = [\sigma]\hat{n}$ on an inclined cut plane, (b) Graphical Mohr\'s circle mapping normal stress $\sigma_n$ and shear stress $\tau_n$ with principal axes $\sigma_1, \sigma_2$.</p>
    </div>

    <div style="background: rgba(56, 189, 248, 0.08); border-left: 4px solid var(--accent); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--accent); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-compress"></i> The Cauchy Stress Principle
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            When external forces act on a deformable body, internal contact forces are transmitted across any imaginary surface dividing the body. The <strong>Cauchy stress tensor</strong> $[\sigma]$ is a symmetric rank-2 tensor whose matrix components represent the force per unit area acting on coordinate planes.
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Traction Vector on an Arbitrary Cut Plane
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        For a plane defined by outward unit normal $\hat{n} = (n_x, n_y, n_z)^T$, the surface force density (traction vector) $\vec{T}^{(\hat{n})}$ is given by the Cauchy formula:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$T_i^{(\hat{n})} = \sum_{j=1}^3 \sigma_{ij} n_j \iff \vec{T}^{(\hat{n})} = [\sigma]\hat{n} = \begin{pmatrix} \sigma_{xx} & \tau_{xy} & \tau_{xz} \\ \tau_{yx} & \sigma_{yy} & \tau_{yz} \\ \tau_{zx} & \tau_{zy} & \sigma_{zz} \end{pmatrix} \begin{pmatrix} n_x \\ n_y \\ n_z \end{pmatrix}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Conservation of angular momentum requires the stress tensor to be symmetric: $\sigma_{ij} = \sigma_{ji}$ (in the absence of body couples).
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Normal &amp; Shear Components on an Inclined Plane (2D State)
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        For a 2D plane stress state with plane normal $\hat{n} = (\cos\theta, \sin\theta)^T$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\text{Normal Stress: } \sigma_n = \hat{n} \cdot \vec{T}^{(\hat{n})} = \frac{\sigma_x + \sigma_y}{2} + \frac{\sigma_x - \sigma_y}{2}\cos 2\theta + \tau_{xy}\sin 2\theta$$
        $$\text{Shear Stress: } \tau_n = \hat{t} \cdot \vec{T}^{(\hat{n})} = -\frac{\sigma_x - \sigma_y}{2}\sin 2\theta + \tau_{xy}\cos 2\theta$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Mohr\'s Circle Derivation &amp; Principal Stresses
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Squaring and adding the expressions for $(\sigma_n - \sigma_{\text{avg}})$ and $\tau_n$ yields the equation of a circle:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$(\sigma_n - \sigma_{\text{avg}})^2 + \tau_n^2 = R^2$$
        $$\sigma_{\text{avg}} = \frac{\sigma_x + \sigma_y}{2}, \qquad R = \sqrt{\left(\frac{\sigma_x - \sigma_y}{2}\right)^2 + \tau_{xy}^2}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        <strong>Principal Values:</strong>
        <br>&bull; <strong>Principal Stresses ($\tau_n = 0$):</strong> $\sigma_1 = \sigma_{\text{avg}} + R, \quad \sigma_2 = \sigma_{\text{avg}} - R$.
        <br>&bull; <strong>Maximum Shear Stress:</strong> $\tau_{\max} = R = \frac{\sigma_1 - \sigma_2}{2}$ (occurs at $\pm 45^\circ$ to principal axes).
        <br>&bull; <strong>Principal Orientation:</strong> $\tan 2\theta_p = \frac{2\tau_{xy}}{\sigma_x - \sigma_y}$.
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 2,
        ':prog_id'     => 1,
        ':content'     => $p2_1_content,
        ':algo'        => $p2_1_algo,
        ':explanation' => $p2_1_explanation
    ]);
    echo "  [OK] Inserted Program 2.2.1 (Cauchy Stress & Mohr's Circle Studio)\n";

    // Program 2.2.2: Physics Problem: Hydrostatic vs Deviatoric Stress & Von Mises Yield
    $p2_2_content = <<<'PYCODE'
"""
Physics Problem Simulation: Hydrostatic vs. Deviatoric Stress Decomposition & Von Mises Yield Criterion
Decomposes stress tensor into hydrostatic pressure p*delta_ij (volumetric change) and 
deviatoric stress s_ij (shear distortion). Computes second deviatoric invariant J_2 and 
evaluates Von Mises yield failure envelope for structural metals.
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider
from matplotlib.patches import Ellipse

fig, axes = plt.subplots(1, 2, figsize=(12.8, 5.2))
plt.subplots_adjust(bottom=0.28, top=0.88, left=0.08, right=0.94, wspace=0.32)

# Initial stresses (MPa)
sx0 = 120.0
sy0 = 60.0
txy0 = 45.0
yield_strength = 250.0 # Structural steel yield limit (MPa)

def solve_plasticity(sx, sy, txy, sigma_y_mat):
    # Plane stress tensor (sigma_z = 0, tau_xz = tau_yz = 0)
    sigma = np.array([[sx, txy, 0.0],
                      [txy, sy, 0.0],
                      [0.0, 0.0, 0.0]])
    
    # 1. Hydrostatic pressure: p = (1/3) * Tr(sigma)
    p_hydro = (sx + sy + 0.0) / 3.0
    
    # 2. Deviatoric stress tensor: s_ij = sigma_ij - p * delta_ij
    s = sigma - p_hydro * np.eye(3)
    
    # 3. Second invariant J2 = (1/2) * s_ij * s_ij
    J2 = 0.5 * np.sum(s * s)
    
    # 4. Von Mises equivalent stress: sigma_vM = sqrt(3 * J2)
    sigma_vM = np.sqrt(3.0 * J2)
    
    # Principal stresses in plane
    s_avg = (sx + sy) / 2.0
    R = np.sqrt(((sx - sy)/2.0)**2 + txy**2)
    s1 = s_avg + R
    s2 = s_avg - R
    
    # Safety factor
    sf = sigma_y_mat / sigma_vM if sigma_vM > 0 else 99.0
    is_yielded = sigma_vM >= sigma_y_mat
    
    return p_hydro, s, J2, sigma_vM, s1, s2, sf, is_yielded

def update_views(sx, sy, txy, sig_y_val):
    p_h, s, J2, s_vM, s1, s2, sf, yielded = solve_plasticity(sx, sy, txy, sig_y_val)
    
    # ----------------------------------------------------
    # Subplot 1: Von Mises Yield Ellipse in Principal Stress Space
    # ----------------------------------------------------
    ax1 = axes[0]
    ax1.cla()
    ax1.set_facecolor('#1e293b')
    ax1.grid(True, linestyle=':', alpha=0.35, color='#475569')
    
    # Von Mises yield locus in plane stress: s1^2 - s1*s2 + s2^2 = sigma_y^2
    # This is an ellipse rotated by 45 degrees
    a_ell = sig_y_val * np.sqrt(2.0)
    b_ell = sig_y_val * np.sqrt(2.0 / 3.0)
    
    ell = Ellipse(xy=(0, 0), width=2*a_ell, height=2*b_ell, angle=45.0,
                  facecolor='#10b981' if not yielded else '#f43f5e', alpha=0.15,
                  edgecolor='#10b981' if not yielded else '#f43f5e', lw=2.5,
                  label=f'Yield Boundary (σ_Y={sig_y_val:.0f} MPa)')
    ax1.add_patch(ell)
    
    # Axes
    ax1.axhline(0, color='#64748b', lw=1.2)
    ax1.axvline(0, color='#64748b', lw=1.2)
    
    # Pure shear line (s1 = -s2) and Hydrostatic axis (s1 = s2)
    lim_ax = sig_y_val * 1.6
    ax1.plot([-lim_ax, lim_ax], [-lim_ax, lim_ax], color='#38bdf8', linestyle=':', lw=1.5, alpha=0.6, label='Hydrostatic Line (s1=s2)')
    ax1.plot([-lim_ax, lim_ax], [lim_ax, -lim_ax], color='#a855f7', linestyle=':', lw=1.5, alpha=0.6, label='Pure Shear Line (s1=-s2)')
    
    # Current stress state point
    pt_color = '#f43f5e' if yielded else '#38bdf8'
    ax1.plot(s1, s2, 'o', color=pt_color, ms=10, zorder=5, label=f'Current: σ1={s1:.1f}, σ2={s2:.1f}')
    
    ax1.set_xlim(-lim_ax, lim_ax)
    ax1.set_ylim(-lim_ax, lim_ax)
    ax1.set_aspect('equal')
    ax1.set_xlabel('Principal Stress σ_1 (MPa)', fontsize=9)
    ax1.set_ylabel('Principal Stress σ_2 (MPa)', fontsize=9)
    
    status_txt = "PLASTIC YIELD FAILING!" if yielded else f"SAFE ELASTIC (SF = {sf:.2f})"
    ax1.set_title(f'Von Mises Yield Locus: σ_vM = {s_vM:.1f} MPa\nStatus: {status_txt}',
                  fontsize=10.5, color='#f43f5e' if yielded else '#10b981', fontweight='bold')
    ax1.legend(loc='lower left', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')

    # ----------------------------------------------------
    # Subplot 2: Hydrostatic vs Deviatoric Energy Breakdown
    # ----------------------------------------------------
    ax2 = axes[1]
    ax2.cla()
    ax2.set_facecolor('#1e293b')
    ax2.grid(True, linestyle=':', alpha=0.35, color='#475569')
    
    # Bar chart comparing Hydrostatic pressure vs Deviatoric components
    categories = ['Hydrostatic p', '|s_xx|', '|s_yy|', '|s_zz|', '|s_xy|', 'Von Mises σ_vM']
    values = [abs(p_h), abs(s[0, 0]), abs(s[1, 1]), abs(s[2, 2]), abs(s[0, 1]), s_vM]
    colors = ['#38bdf8', '#a855f7', '#a855f7', '#a855f7', '#f59e0b', '#f43f5e' if yielded else '#10b981']
    
    bars = ax2.bar(categories, values, color=colors, alpha=0.85, edgecolor=colors, lw=1.5)
    for bar in bars:
        yval = bar.get_height()
        ax2.text(bar.get_x() + bar.get_width()/2.0, yval + 3.0, f'{yval:.1f}', ha='center', va='bottom', fontsize=8.5, color='#f8fafc', fontweight='bold')
        
    ax2.axhline(sig_y_val, color='#f43f5e', linestyle='--', lw=2.0, label=f'Yield Limit σ_Y ({sig_y_val:.0f} MPa)')
    ax2.set_ylim(0, max(max(values)*1.25, sig_y_val*1.2))
    ax2.set_ylabel('Stress Magnitude (MPa)', fontsize=9)
    ax2.set_title(f'Tensor Decomposition: σ_ij = p·δ_ij + s_ij\nJ_2 = {J2:.1f} MPa² (Distortion Invariant)', fontsize=10.5, color='#38bdf8', fontweight='bold')
    ax2.legend(loc='upper right', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')

update_views(sx0, sy0, txy0, yield_strength)

# Sliders
ax_sx = plt.axes([0.15, 0.17, 0.70, 0.025])
ax_sy = plt.axes([0.15, 0.12, 0.70, 0.025])
ax_txy = plt.axes([0.15, 0.07, 0.70, 0.025])
ax_syld = plt.axes([0.15, 0.02, 0.70, 0.025])

s_sx = Slider(ax_sx, 'σ_x (MPa)', -180.0, 220.0, valinit=sx0, valstep=5.0)
s_sy = Slider(ax_sy, 'σ_y (MPa)', -180.0, 220.0, valinit=sy0, valstep=5.0)
s_txy = Slider(ax_txy, 'τ_xy (MPa)', -120.0, 120.0, valinit=txy0, valstep=5.0)
s_syld = Slider(ax_syld, 'Yield Limit σ_Y', 150.0, 350.0, valinit=yield_strength, valstep=10.0)

def update(val):
    update_views(s_sx.val, s_sy.val, s_txy.val, s_syld.val)
    fig.canvas.draw_idle()

s_sx.on_changed(update)
s_sy.on_changed(update)
s_txy.on_changed(update)
s_syld.on_changed(update)

print("Hydrostatic vs. Deviatoric Stress Decomposition Simulation Loaded.")
print("Hydrostatic stress p causes volume dilation without yielding.")
print("Deviatoric stress s_ij drives dislocation motion and yields when sigma_vM >= sigma_Y!")
plt.show()
PYCODE;

    $p2_2_algo = "Physics Problem: Hydrostatic vs. Deviatoric Stress Decomposition & Von Mises Yield Criterion under Complex Multi-Axial Load";

    $p2_2_explanation = '<div class="theory-article">
    <div style="background: rgba(16, 185, 129, 0.08); border-left: 4px solid var(--emerald); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--emerald); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-shield-halved"></i> Engineering Physics Problem: Multi-Axial Yield Failure
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            A ductile structural steel component is subjected to simultaneous bi-axial normal stresses ($\sigma_x, \sigma_y$) and in-plane shear $\tau_{xy}$. Isotropic metals do not yield under purely hydrostatic pressure (even at thousands of atmospheres at the bottom of the Mariana Trench), but rather yield due to shape distortion governed by the <em>deviatoric stress tensor</em>. Decompose the stress tensor into volumetric and distortional parts and compute the Von Mises safety factor against plastic yield.
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Tensor Decomposition: Hydrostatic vs. Deviatoric
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Any symmetric stress tensor $\sigma_{ij}$ can be uniquely decomposed into a spherical (hydrostatic) part and a trace-free deviatoric part:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\sigma_{ij} = p\,\delta_{ij} + s_{ij}$$
        $$\text{Mean Hydrostatic Stress: } p = \frac{1}{3}\mathrm{Tr}(\sigma) = \frac{\sigma_{kk}}{3} = \frac{\sigma_{xx} + \sigma_{yy} + \sigma_{zz}}{3}$$
        $$\text{Deviatoric Stress Tensor: } s_{ij} = \sigma_{ij} - p\,\delta_{ij}, \qquad \mathrm{Tr}(s) = s_{ii} = 0$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        <strong>Physical Roles:</strong>
        <br>&bull; <strong>$p\,\delta_{ij}$ (Spherical):</strong> Produces purely volumetric dilatation or contraction ($\Delta V / V$) with zero shear distortion. Does not cause dislocation slip in crystalline metals.
        <br>&bull; <strong>$s_{ij}$ (Deviatoric):</strong> Produces pure isochoric shape distortion and shear strains, directly driving irreversible plastic deformation.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Second Invariant $J_2$ &amp; Von Mises Yield Criterion
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The second invariant of the deviatoric stress tensor $J_2$ quantifies the total shear strain energy density:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$J_2 = \frac{1}{2} s_{ij} s_{ij} = \frac{1}{6}\left[(\sigma_1 - \sigma_2)^2 + (\sigma_2 - \sigma_3)^2 + (\sigma_3 - \sigma_1)^2\right]$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The <strong>Von Mises equivalent stress</strong> $\sigma_{\text{vM}}$ is defined such that in uniaxial tension ($\sigma_1 = \sigma_Y, \sigma_2 = \sigma_3 = 0$), $\sigma_{\text{vM}} = \sigma_Y$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\sigma_{\text{vM}} = \sqrt{3 J_2} = \sqrt{\frac{1}{2}\left[(\sigma_1 - \sigma_2)^2 + (\sigma_2 - \sigma_3)^2 + (\sigma_3 - \sigma_1)^2\right]}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        For plane stress ($\sigma_3 = 0$), the yield condition $\sigma_{\text{vM}} = \sigma_Y$ forms the classic ellipse in $(\sigma_1, \sigma_2)$ space:
        $$\sigma_1^2 - \sigma_1\sigma_2 + \sigma_2^2 = \sigma_Y^2$$
        The material remains elastic if $\sigma_{\text{vM}} < \sigma_Y$ with safety factor $\text{SF} = \sigma_Y / \sigma_{\text{vM}}$.
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 2,
        ':prog_id'     => 2,
        ':content'     => $p2_2_content,
        ':algo'        => $p2_2_algo,
        ':explanation' => $p2_2_explanation
    ]);
    echo "  [OK] Inserted Program 2.2.2 (Von Mises Plasticity)\n";

    // =========================================================================
    // SUBTOPIC 2.3: Moment of Inertia Tensor & Rotational Dynamics
    // =========================================================================

    // Program 2.3.1: 3D Moment of Inertia Tensor & Poinsot's Ellipsoid Visualizer
    $p3_1_content = <<<'PYCODE'
"""
3D Moment of Inertia Tensor & Poinsot's Ellipsoid Visualizer
Constructs symmetric 3x3 inertia tensor I_ij, computes principal moments of inertia
(I_1, I_2, I_3) and principal axes via eigen-decomposition, and renders Poinsot's 
ellipsoid: n^T [I] n = 2T_rot, showing misalignment between omega and L = [I]*omega.
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider
from mpl_toolkits.mplot3d import Axes3D

fig = plt.figure(figsize=(9.5, 6.2))
plt.subplots_adjust(bottom=0.28, top=0.92, left=0.08, right=0.92)

# Initial principal inertia moments (kg*m^2)
I1_0, I2_0, I3_0 = 1.2, 2.4, 4.0
phi_w0, theta_w0 = 45.0, 55.0 # Orientation of angular velocity omega in deg

ax = fig.add_subplot(1, 1, 1, projection='3d')

def update_views(I1, I2, I3, phi_deg, th_deg):
    ax.cla()
    
    # Inertia matrix in principal frame
    I_mat = np.diag([I1, I2, I3])
    
    # Angular velocity vector omega with magnitude |omega| = 2.0 rad/s
    omega_mag = 2.0
    phi = np.radians(phi_deg)
    th = np.radians(th_deg)
    
    wx = omega_mag * np.sin(th) * np.cos(phi)
    wy = omega_mag * np.sin(th) * np.sin(phi)
    wz = omega_mag * np.cos(th)
    omega = np.array([wx, wy, wz])
    
    # Angular momentum vector L = I * omega
    L = I_mat @ omega
    L_norm = np.linalg.norm(L)
    w_norm = np.linalg.norm(omega)
    
    # Rotational kinetic energy: T = (1/2) omega · L
    T_rot = 0.5 * np.dot(omega, L)
    
    # Angle between omega and L
    cos_alpha = np.dot(omega, L) / (w_norm * L_norm)
    cos_alpha = np.clip(cos_alpha, -1.0, 1.0)
    alpha_deg = np.degrees(np.arccos(cos_alpha))
    
    # Poinsot's Ellipsoid: I1 x^2 + I2 y^2 + I3 z^2 = 2 * T_rot
    # Semi-axes: a = sqrt(2T / I1), b = sqrt(2T / I2), c = sqrt(2T / I3)
    a = np.sqrt(2 * T_rot / I1)
    b = np.sqrt(2 * T_rot / I2)
    c = np.sqrt(2 * T_rot / I3)
    
    u = np.linspace(0, 2 * np.pi, 30)
    v = np.linspace(0, np.pi, 20)
    X_ell = a * np.outer(np.cos(u), np.sin(v))
    Y_ell = b * np.outer(np.sin(u), np.sin(v))
    Z_ell = c * np.outer(np.ones_like(u), np.cos(v))
    
    ax.plot_wireframe(X_ell, Y_ell, Z_ell, color='#38bdf8', alpha=0.25, lw=0.8)
    
    # Principal coordinate axes
    axis_len = max(a, b, c) * 1.25
    ax.plot([-axis_len, axis_len], [0, 0], [0, 0], color='#94a3b8', linestyle=':', lw=1.2)
    ax.plot([0, 0], [-axis_len, axis_len], [0, 0], color='#94a3b8', linestyle=':', lw=1.2)
    ax.plot([0, 0], [0, 0], [-axis_len, axis_len], color='#94a3b8', linestyle=':', lw=1.2)
    ax.text(axis_len, 0, 0, f'Axis 1 (I1={I1:.1f})', color='#38bdf8', fontsize=8.5)
    ax.text(0, axis_len, 0, f'Axis 2 (I2={I2:.1f})', color='#a855f7', fontsize=8.5)
    ax.text(0, 0, axis_len, f'Axis 3 (I3={I3:.1f})', color='#f59e0b', fontsize=8.5)
    
    # Plot angular velocity omega (green)
    scale_w = 0.9 * axis_len / w_norm
    ax.quiver(0, 0, 0, omega[0]*scale_w, omega[1]*scale_w, omega[2]*scale_w,
              color='#10b981', lw=3.2, arrow_length_ratio=0.15, label=f'Angular Velocity ω (|ω|={w_norm:.2f} rad/s)')
    
    # Plot angular momentum L (rose)
    scale_L = 0.9 * axis_len / L_norm
    ax.quiver(0, 0, 0, L[0]*scale_L, L[1]*scale_L, L[2]*scale_L,
              color='#f43f5e', lw=3.2, arrow_length_ratio=0.15, label=f'Angular Momentum L (|L|={L_norm:.2f} J·s)')
    
    ax.set_xlim(-axis_len, axis_len)
    ax.set_ylim(-axis_len, axis_len)
    ax.set_zlim(-axis_len, axis_len)
    ax.set_xlabel('X', fontsize=8.5)
    ax.set_ylabel('Y', fontsize=8.5)
    ax.set_zlabel('Z', fontsize=8.5)
    ax.set_title(f'Poinsot\'s Inertia Ellipsoid | T_rot = {T_rot:.2f} J\nMisalignment Angle ∠(ω, L) = {alpha_deg:.1f}°',
                 fontsize=10.5, color='#38bdf8', fontweight='bold', pad=10)
    ax.legend(loc='upper left', fontsize=7.5, framealpha=0.85)
    ax.view_init(elev=22, azim=-55)

update_views(I1_0, I2_0, I3_0, phi_w0, theta_w0)

# Sliders
ax_phi = plt.axes([0.15, 0.16, 0.70, 0.022])
ax_th = plt.axes([0.15, 0.11, 0.70, 0.022])
ax_i2 = plt.axes([0.15, 0.06, 0.70, 0.022])
ax_i3 = plt.axes([0.15, 0.01, 0.70, 0.022])

s_phi = Slider(ax_phi, 'Azimuth φ (°)', 0.0, 360.0, valinit=phi_w0, valstep=5.0)
s_th = Slider(ax_th, 'Polar θ (°)', 5.0, 175.0, valinit=theta_w0, valstep=5.0)
s_i2 = Slider(ax_i2, 'Moment I_2', 1.3, 3.8, valinit=I2_0, valstep=0.1)
s_i3 = Slider(ax_i3, 'Moment I_3', 2.5, 6.0, valinit=I3_0, valstep=0.1)

def update(val):
    update_views(I1_0, s_i2.val, s_i3.val, s_phi.val, s_th.val)
    fig.canvas.draw_idle()

s_phi.on_changed(update)
s_th.on_changed(update)
s_i2.on_changed(update)
s_i3.on_changed(update)

print("Poinsot's Inertia Ellipsoid Studio Loaded.")
print("Notice how angular momentum L is NOT collinear with omega unless rotating along a principal axis!")
plt.show()
PYCODE;

    $p3_1_algo = "3D Moment of Inertia Tensor & Poinsot's Ellipsoid Visualizer: Principal Axes, Kinetic Energy, and Non-Collinear Angular Momentum";

    $p3_1_explanation = '<div class="theory-article">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <img src="{{SITEURL}}assets/images/visualization/tensors/inertia_tensor_ellipsoid.png" alt="Inertia Tensor and Poinsots Ellipsoid" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <p style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem;"><strong>Figure 2.3:</strong> Moment of Inertia Tensor &amp; Poinsot\'s Ellipsoid: (a) Poinsot\'s kinetic energy ellipsoid $2T = \vec{\omega}^T [I] \vec{\omega}$ showing non-collinearity between $\vec{\omega}$ and $\vec{L} = [I]\vec{\omega}$, (b) Principal axes of an asymmetric rigid body.</p>
    </div>

    <div style="background: rgba(56, 189, 248, 0.08); border-left: 4px solid var(--accent); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--accent); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-arrows-spin"></i> Rotational Dynamics &amp; The Inertia Tensor
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            In linear mechanics, mass is a simple scalar constant ($p = mv$). In 3D rotational mechanics, however, an applied torque does not generally produce an angular acceleration in the same direction! The relationship between angular velocity $\vec{\omega}$ and angular momentum $\vec{L}$ is governed by the <strong>moment of inertia tensor</strong> $[I]$:
            $$\vec{L} = [I]\vec{\omega}$$
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Definition of the Inertia Tensor
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        For a continuous mass distribution $\rho(\vec{r})$, the components of the symmetric rank-2 inertia tensor are defined by:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$I_{ij} = \int_V \rho(\vec{r})\left(r^2 \delta_{ij} - x_i x_j\right) dV$$
        $$[I] = \begin{pmatrix} \int (y^2 + z^2) dm & -\int xy\, dm & -\int xz\, dm \\ -\int yx\, dm & \int (x^2 + z^2) dm & -\int yz\, dm \\ -\int zx\, dm & -\int zy\, dm & \int (x^2 + y^2) dm \end{pmatrix}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The diagonal entries $I_{xx}, I_{yy}, I_{zz}$ are the moments of inertia about the coordinate axes, while the off-diagonal entries $I_{xy}, I_{yz}, I_{zx}$ are the products of inertia.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Principal Axes of Inertia &amp; Diagonalization
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Because $[I]$ is a real symmetric tensor, the spectral theorem guarantees the existence of an orthonormal basis of eigenvectors (the <strong>principal axes</strong>) in which $[I]$ is strictly diagonal:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$[I]_{\text{principal}} = \begin{pmatrix} I_1 & 0 & 0 \\ 0 & I_2 & 0 \\ 0 & 0 & I_3 \end{pmatrix}, \qquad I_1 \le I_2 \le I_3$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Only when rotation occurs along one of these three principal axes are $\vec{L}$ and $\vec{\omega}$ collinear ($\vec{L} = I_k \vec{\omega}$). For any arbitrary rotation axis, $\vec{L}$ tilts away from $\vec{\omega}$!
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Rotational Kinetic Energy &amp; Poinsot\'s Ellipsoid
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The rotational kinetic energy is a quadratic form in $\vec{\omega}$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$T_{\text{rot}} = \frac{1}{2} \vec{\omega}^T [I] \vec{\omega} = \frac{1}{2}\left(I_1 \omega_1^2 + I_2 \omega_2^2 + I_3 \omega_3^2\right) = \frac{1}{2}\vec{\omega}\cdot\vec{L}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Louis Poinsot (1834) introduced the geometric visualization known as <strong>Poinsot\'s Ellipsoid</strong>:
        $$I_1 x^2 + I_2 y^2 + I_3 z^2 = 2 T_{\text{rot}}$$
        In torque-free rotation, both $T_{\text{rot}}$ and $|\vec{L}|$ are strictly conserved. The angular momentum vector $\vec{L}$ is orthogonal to the tangent plane of this ellipsoid at the tip of $\vec{\omega}$, causing the ellipsoid to roll without slipping on an invariant plane!
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 3,
        ':prog_id'     => 1,
        ':content'     => $p3_1_content,
        ':algo'        => $p3_1_algo,
        ':explanation' => $p3_1_explanation
    ]);
    echo "  [OK] Inserted Program 2.3.1 (Inertia Tensor Studio)\n";

    // Program 2.3.2: Physics Problem: Tennis Racket / Dzhanibekov Effect
    $p3_2_content = <<<'PYCODE'
"""
Physics Problem Simulation: Asymmetric Top Stability & Dzhanibekov / Tennis Racket Effect
Solves Euler's equations of motion for torque-free rotation of an asymmetric body:
I_1 * dw1/dt = (I_2 - I_3) * w2 * w3
I_2 * dw2/dt = (I_3 - I_1) * w3 * w1
I_3 * dw3/dt = (I_1 - I_2) * w1 * w2
Demonstrates exponential instability about intermediate axis I_2 vs stable rotation about I_1 and I_3.
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider
from scipy.integrate import odeint

fig, axes = plt.subplots(1, 2, figsize=(12.8, 5.2))
plt.subplots_adjust(bottom=0.28, top=0.88, left=0.08, right=0.94, wspace=0.32)

# Principal moments of inertia (I1 < I2 < I3)
I1_val, I2_val, I3_val = 1.0, 2.5, 4.0
axis_choice = 2 # 1: Min, 2: Intermediate, 3: Max
perturbation = 0.05

def euler_derivs(w, t, I1, I2, I3):
    w1, w2, w3 = w
    dw1_dt = ((I2 - I3) / I1) * w2 * w3
    dw2_dt = ((I3 - I1) / I2) * w3 * w1
    dw3_dt = ((I1 - I2) / I3) * w1 * w2
    return [dw1_dt, dw2_dt, dw3_dt]

def run_simulation(I1, I2, I3, axis_sel, eps):
    t = np.linspace(0, 30.0, 1000)
    w0_mag = 3.0
    
    if axis_sel == 1:
        # Rotation predominantly around Axis 1 (Minimum moment)
        w_init = [w0_mag, eps, eps]
    elif axis_sel == 2:
        # Rotation predominantly around Axis 2 (Intermediate moment)
        w_init = [eps, w0_mag, eps]
    else:
        # Rotation predominantly around Axis 3 (Maximum moment)
        w_init = [eps, eps, w0_mag]
        
    sol = odeint(euler_derivs, w_init, t, args=(I1, I2, I3))
    return t, sol

def update_views(I2, I3, axis_sel, eps):
    I1 = 1.0
    t, sol = run_simulation(I1, I2, I3, axis_sel, eps)
    w1, w2, w3 = sol[:, 0], sol[:, 1], sol[:, 2]
    
    # ----------------------------------------------------
    # Subplot 1: Time Series of Angular Velocity Components
    # ----------------------------------------------------
    ax1 = axes[0]
    ax1.cla()
    ax1.set_facecolor('#1e293b')
    ax1.grid(True, linestyle=':', alpha=0.35, color='#475569')
    
    ax1.plot(t, w1, color='#38bdf8', lw=2.2, label=r'$\omega_1$ (Min Axis)')
    ax1.plot(t, w2, color='#10b981', lw=2.5, label=r'$\omega_2$ (Intermediate Axis)')
    ax1.plot(t, w3, color='#f59e0b', lw=2.2, label=r'$\omega_3$ (Max Axis)')
    
    ax1.set_xlim(0, 30.0)
    ax1.set_ylim(-3.8, 3.8)
    ax1.set_xlabel('Time t (seconds)', fontsize=9)
    ax1.set_ylabel(r'Angular Velocity $\omega_i$ (rad/s)', fontsize=9)
    
    axis_names = {1: 'Axis 1 (Minimum Moment I1)', 2: 'Axis 2 (Intermediate Moment I2)', 3: 'Axis 3 (Maximum Moment I3)'}
    instability_txt = "UNSTABLE! (Dzhanibekov Flipping)" if axis_sel == 2 else "STABLE (Harmonic Precession)"
    ax1.set_title(f'Rotation about {axis_names[axis_sel]}\nStatus: {instability_txt}',
                  fontsize=10.5, color='#f43f5e' if axis_sel == 2 else '#10b981', fontweight='bold')
    ax1.legend(loc='lower right', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')

    # ----------------------------------------------------
    # Subplot 2: Phase Trajectory in (omega_1, omega_2, omega_3) Space
    # ----------------------------------------------------
    ax2 = axes[1]
    ax2.cla()
    ax2.set_facecolor('#1e293b')
    ax2.grid(True, linestyle=':', alpha=0.35, color='#475569')
    
    # Plot phase portrait in (w1, w3) projection
    ax2.plot(w1, w3, color='#38bdf8' if axis_sel != 2 else '#f43f5e', lw=2.0)
    ax2.plot(w1[0], w3[0], 'o', color='#10b981', ms=8, label='Initial State')
    ax2.plot(w1[-1], w3[-1], 's', color='#f43f5e', ms=8, label='Final State')
    
    # Invariant energy / momentum conservation check
    E_rot = 0.5 * (I1 * w1**2 + I2 * w2**2 + I3 * w3**2)
    L_sq = (I1 * w1)**2 + (I2 * w2)**2 + (I3 * w3)**2
    
    ax2.set_xlim(-3.8, 3.8)
    ax2.set_ylim(-3.8, 3.8)
    ax2.set_xlabel(r'$\omega_1$ (rad/s)', fontsize=9)
    ax2.set_ylabel(r'$\omega_3$ (rad/s)', fontsize=9)
    ax2.set_title(f'Phase Trajectory $(\\omega_1, \\omega_3)$ Projection\nEnergy E = {E_rot[0]:.2f} J | |L| = {np.sqrt(L_sq[0]):.2f} J·s (Conserved)',
                  fontsize=10.5, color='#f59e0b', fontweight='bold')
    ax2.legend(loc='upper right', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')

update_views(I2_val, I3_val, axis_choice, perturbation)

# Sliders and Controls
ax_ax = plt.axes([0.15, 0.16, 0.70, 0.025])
ax_i2 = plt.axes([0.15, 0.11, 0.70, 0.025])
ax_i3 = plt.axes([0.15, 0.06, 0.70, 0.025])
ax_eps = plt.axes([0.15, 0.01, 0.70, 0.025])

s_ax = Slider(ax_ax, 'Rotation Axis (1/2/3)', 1, 3, valinit=axis_choice, valstep=1)
s_i2 = Slider(ax_i2, 'Moment I_2', 1.2, 3.5, valinit=I2_val, valstep=0.1)
s_i3 = Slider(ax_i3, 'Moment I_3', 3.6, 6.0, valinit=I3_val, valstep=0.2)
s_eps = Slider(ax_eps, 'Perturbation ε', 0.01, 0.30, valinit=perturbation, valstep=0.01)

def update(val):
    update_views(s_i2.val, s_i3.val, int(s_ax.val), s_eps.val)
    fig.canvas.draw_idle()

s_ax.on_changed(update)
s_i2.on_changed(update)
s_i3.on_changed(update)
s_eps.on_changed(update)

print("Tennis Racket / Dzhanibekov Effect Simulation Loaded.")
print("Set Rotation Axis = 2 to see the intermediate axis flip periodically in zero-g!")
print("Set Rotation Axis = 1 or 3 to observe unconditionally stable precession.")
plt.show()
PYCODE;

    $p3_2_algo = "Physics Problem: Rotational Stability of Asymmetric Rigid Body & Dzhanibekov / Tennis Racket Effect via Euler's Equations";

    $p3_2_explanation = '<div class="theory-article">
    <div style="background: rgba(16, 185, 129, 0.08); border-left: 4px solid var(--emerald); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--emerald); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-satellite"></i> The Dzhanibekov Effect / Tennis Racket Theorem
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            In 1985, Soviet cosmonaut Vladimir Dzhanibekov observed aboard the Salyut 7 space station that a wing nut spinning in microgravity about its intermediate principal axis flips its orientation by $180^\circ$ at regular intervals. This counter-intuitive behavior—also known as the <em>Tennis Racket Theorem</em>—is a rigorous mathematical consequence of Euler\'s equations of motion for an asymmetric rigid body ($I_1 < I_2 < I_3$).
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Euler\'s Equations for Torque-Free Rotation
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        In the body-fixed principal frame of reference, torque-free rotational dynamics ($\vec{\tau} = 0$) are governed by Euler\'s non-linear differential equations:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$I_1 \dot{\omega}_1 = (I_2 - I_3)\omega_2 \omega_3$$
        $$I_2 \dot{\omega}_2 = (I_3 - I_1)\omega_3 \omega_1$$
        $$I_3 \dot{\omega}_3 = (I_1 - I_2)\omega_1 \omega_2$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Where $I_1 < I_2 < I_3$ are the ordered principal moments of inertia.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Perturbation Analysis: Proof of Stability &amp; Instability
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Let us test the stability of steady rotation $\Omega$ about each principal axis under small perturbations $\eta_i \ll \Omega$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 0.95rem; line-height: 1.6;">
        <p><strong>Case 1: Rotation about Axis 1 (Minimum Moment $I_1$):</strong></p>
        $$\vec{\omega} = (\Omega, \eta_2, \eta_3) \implies \ddot{\eta}_2 = -\Omega^2 \frac{(I_1 - I_3)(I_1 - I_2)}{I_2 I_3}\eta_2$$
        Since $I_1 < I_2 < I_3$, both $(I_1 - I_3) < 0$ and $(I_1 - I_2) < 0$, their product is <strong>positive</strong>:
        $$\ddot{\eta}_2 + \omega_0^2 \eta_2 = 0 \implies \text{Bounded Harmonic Oscillation } \implies \mathbf{STABLE!}$$

        <p style="margin-top: 0.75rem;"><strong>Case 2: Rotation about Axis 2 (Intermediate Moment $I_2$):</strong></p>
        $$\vec{\omega} = (\eta_1, \Omega, \eta_3) \implies \ddot{\eta}_1 = \Omega^2 \frac{(I_2 - I_3)(I_2 - I_1)}{I_1 I_3}\eta_1$$
        Here, $(I_2 - I_3) < 0$ but $(I_2 - I_1) > 0$, so their product is <strong>strictly negative</strong>:
        $$\ddot{\eta}_1 - \lambda^2 \eta_1 = 0 \implies \eta_1(t) \sim e^{+\lambda t} \implies \mathbf{EXPONENTIAL\ INSTABILITY!}$$

        <p style="margin-top: 0.75rem;"><strong>Case 3: Rotation about Axis 3 (Maximum Moment $I_3$):</strong></p>
        Both $(I_3 - I_1) > 0$ and $(I_3 - I_2) > 0$, product is positive $\implies \mathbf{STABLE!}$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Geometric Interpretation on Poinsot\'s Ellipsoid
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        In phase space, the trajectory of $\vec{\omega}$ is the curve of intersection between the <strong>energy ellipsoid</strong> ($2E = \vec{\omega}^T [I] \vec{\omega}$) and the <strong>angular momentum sphere</strong> ($L^2 = \vec{\omega}^T [I]^2 \vec{\omega}$). 
        Around axes 1 and 3, the intersections form closed concentric topological circles (elliptic fixed points). Around intermediate axis 2, however, the intersection forms a figure-eight separatrix with a hyperbolic saddle point, forcing any perturbed state to make complete $180^\circ$ excursions between $+\Omega$ and $-\Omega$!
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 3,
        ':prog_id'     => 2,
        ':content'     => $p3_2_content,
        ':algo'        => $p3_2_algo,
        ':explanation' => $p3_2_explanation
    ]);
    echo "  [OK] Inserted Program 2.3.2 (Dzhanibekov Stability)\n";

    // =========================================================================
    // SUBTOPIC 2.4: Metric Tensor, Christoffel Symbols & Covariant Differentiation
    // =========================================================================

    // Program 2.4.1: Metric Tensor, Scale Factors & Christoffel Symbols Studio
    $p4_1_content = <<<'PYCODE'
"""
Metric Tensor, Scale Factors & Christoffel Symbols Studio
Explores Riemannian metric tensor g_ij, inverse metric g^ij, coordinate scale factors h_i,
and evaluates non-zero Christoffel symbols Gamma^k_ij = (1/2)*g^kl*(d_i g_jl + d_j g_il - d_l g_ij)
in Cylindrical, Spherical, and Parabolic coordinates.
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider, RadioButtons

fig, axes = plt.subplots(1, 2, figsize=(13.0, 5.2))
plt.subplots_adjust(bottom=0.25, top=0.88, left=0.20, right=0.94, wspace=0.32)

# Coordinates system selector
current_coord = 'Spherical'
r0 = 2.0
theta0 = 45.0 # Degrees

def compute_metric(coord_type, r_val, th_deg):
    th = np.radians(th_deg)
    
    if coord_type == 'Cylindrical':
        # Coordinates (r, theta, z)
        # ds^2 = dr^2 + r^2 dtheta^2 + dz^2
        coord_names = ['r', 'θ', 'z']
        g_diag = [1.0, r_val**2, 1.0]
        h = [1.0, r_val, 1.0]
        g_mat = np.diag(g_diag)
        det_g = r_val**2
        # Non-zero Christoffel symbols:
        # Gamma^r_th,th = -r, Gamma^th_r,th = Gamma^th_th,r = 1/r
        chris = [
            ("Γʳ_θθ", -r_val),
            ("Γᶿ_rθ", 1.0 / r_val if r_val > 0 else 0),
            ("Γᶿ_θr", 1.0 / r_val if r_val > 0 else 0)
        ]
        
    elif coord_type == 'Spherical':
        # Coordinates (r, theta, phi)
        # ds^2 = dr^2 + r^2 dtheta^2 + r^2 sin^2(theta) dphi^2
        coord_names = ['r', 'θ', 'φ']
        sin_th = np.sin(th)
        cos_th = np.cos(th)
        g_diag = [1.0, r_val**2, (r_val * sin_th)**2]
        h = [1.0, r_val, r_val * abs(sin_th)]
        g_mat = np.diag(g_diag)
        det_g = (r_val**2 * sin_th)**2
        # Non-zero Christoffel symbols
        chris = [
            ("Γʳ_θθ", -r_val),
            ("Γʳ_φφ", -r_val * sin_th**2),
            ("Γᶿ_rθ", 1.0 / r_val if r_val > 0 else 0),
            ("Γᶿ_φφ", -sin_th * cos_th),
            ("Γᵠ_rφ", 1.0 / r_val if r_val > 0 else 0),
            ("Γᵠ_θφ", cos_th / sin_th if abs(sin_th) > 1e-4 else 0)
        ]
    else: # Parabolic (u, v, phi)
        # ds^2 = (u^2 + v^2) du^2 + (u^2 + v^2) dv^2 + u^2 v^2 dphi^2
        coord_names = ['u', 'v', 'φ']
        u_val, v_val = r_val, 1.5
        g_diag = [u_val**2 + v_val**2, u_val**2 + v_val**2, (u_val * v_val)**2]
        h = [np.sqrt(g_diag[0]), np.sqrt(g_diag[1]), u_val * v_val]
        g_mat = np.diag(g_diag)
        det_g = (u_val**2 + v_val**2)**2 * (u_val * v_val)**2
        chris = [
            ("Γᵘ_uu", u_val / (u_val**2 + v_val**2)),
            ("Γᵘ_vv", -u_val / (u_val**2 + v_val**2)),
            ("Γᵘ_φφ", -u_val * v_val**2 / (u_val**2 + v_val**2)),
            ("Γᵛ_uv", u_val / (u_val**2 + v_val**2))
        ]
        
    return coord_names, g_mat, h, det_g, chris

def update_views(coord_type, r_val, th_deg):
    coord_names, g_mat, h, det_g, chris = compute_metric(coord_type, r_val, th_deg)
    
    # ----------------------------------------------------
    # Subplot 1: Metric Tensor Matrix Heatmap
    # ----------------------------------------------------
    ax1 = axes[0]
    ax1.cla()
    
    im = ax1.imshow(g_mat, cmap='Blues', vmin=0, vmax=max(np.max(g_mat), 1.0))
    for i in range(3):
        for j in range(3):
            val = g_mat[i, j]
            text_col = '#f8fafc' if val > np.max(g_mat)*0.5 else '#0f172a'
            ax1.text(j, i, f'{val:.2f}' if val != 0 else '0', ha='center', va='center',
                     color=text_col, fontweight='bold', fontsize=11)
            
    ax1.set_xticks(range(3))
    ax1.set_yticks(range(3))
    ax1.set_xticklabels([f'd{c}' for c in coord_names], fontsize=9)
    ax1.set_yticklabels([f'd{c}' for c in coord_names], fontsize=9)
    ax1.set_title(f'Covariant Metric Tensor [g_ij] ({coord_type})\ndet(g) = {det_g:.2f} | √g = {np.sqrt(det_g):.2f}',
                  fontsize=10.5, color='#38bdf8', fontweight='bold')

    # ----------------------------------------------------
    # Subplot 2: Scale Factors & Non-Zero Christoffel Symbols
    # ----------------------------------------------------
    ax2 = axes[1]
    ax2.cla()
    ax2.set_facecolor('#1e293b')
    ax2.grid(True, linestyle=':', alpha=0.35, color='#475569')
    
    # Display Christoffel symbols as bar chart
    names = [c[0] for c in chris]
    vals = [c[1] for c in chris]
    colors = ['#10b981' if v >= 0 else '#f43f5e' for v in vals]
    
    bars = ax2.bar(names, vals, color=colors, alpha=0.85, edgecolor=colors, lw=1.5)
    for bar in bars:
        yval = bar.get_height()
        va = 'bottom' if yval >= 0 else 'top'
        ax2.text(bar.get_x() + bar.get_width()/2.0, yval + (0.1 if yval >= 0 else -0.1),
                 f'{yval:.2f}', ha='center', va=va, fontsize=8.5, color='#f8fafc', fontweight='bold')
        
    ax2.axhline(0, color='#64748b', lw=1.2)
    max_c = max(max(map(abs, vals), default=1.0), 1.0) * 1.35
    ax2.set_ylim(-max_c, max_c)
    
    h_str = ", ".join([f"h_{c}={h[i]:.2f}" for i, c in enumerate(coord_names)])
    ax2.set_title(f'Christoffel Connection Coefficients Γᵏ_ij\nScale Factors: {h_str}',
                  fontsize=10.5, color='#f59e0b', fontweight='bold')
    ax2.set_ylabel('Connection Value', fontsize=9)

update_views(current_coord, r0, theta0)

# Radio buttons for Coordinate System
rax = plt.axes([0.02, 0.55, 0.14, 0.28], facecolor='#1e293b')
radio = RadioButtons(rax, ('Spherical', 'Cylindrical', 'Parabolic'), active=0)

# Sliders
ax_r = plt.axes([0.25, 0.12, 0.65, 0.025])
ax_th = plt.axes([0.25, 0.05, 0.65, 0.025])

s_r = Slider(ax_r, 'Radius r / u', 0.5, 4.0, valinit=r0, valstep=0.1)
s_th = Slider(ax_th, 'Polar θ (°)', 5.0, 175.0, valinit=theta0, valstep=2.0)

def update(val):
    update_views(radio.value_selected, s_r.val, s_th.val)
    fig.canvas.draw_idle()

def coord_change(label):
    update_views(label, s_r.val, s_th.val)
    fig.canvas.draw_idle()

radio.on_clicked(coord_change)
s_r.on_changed(update)
s_th.on_changed(update)

print("Metric Tensor & Christoffel Symbols Studio Loaded.")
print("The metric tensor g_ij defines proper distance ds^2 = g_ij du^i du^j.")
print("Christoffel symbols Gamma^k_ij represent the rate of change of basis vectors!")
plt.show()
PYCODE;

    $p4_1_algo = "Metric Tensor, Scale Factors & Christoffel Symbols Studio: Covariant vs Contravariant Bases in Curvilinear Coordinates";

    $p4_1_explanation = '<div class="theory-article">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <img src="{{SITEURL}}assets/images/visualization/tensors/metric_tensor_covariant.png" alt="Metric Tensor and Covariant Calculus" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <p style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem;"><strong>Figure 2.4:</strong> Metric Tensor &amp; Differential Geometry: (a) Covariant metric $g_{ij}$ and basis vectors $\vec{e}_i = \partial \vec{r}/\partial u^i$, (b) Christoffel symbols $\Gamma^k_{ij}$ and parallel transport around a latitude circle showing holonomy deficit $\Delta\alpha = 2\pi(1 - \cos\theta_0)$.</p>
    </div>

    <div style="background: rgba(56, 189, 248, 0.08); border-left: 4px solid var(--accent); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--accent); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-ruler-combined"></i> The Metric Tensor $g_{ij}$
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            In any arbitrary curvilinear or Riemannian coordinate system $\{u^1, u^2, u^3\}$, the infinitesimal invariant distance $ds$ between two neighboring points is determined by the <strong>metric tensor</strong> $g_{ij}$:
            $$ds^2 = g_{ij} du^i du^j = g_{11}(du^1)^2 + g_{22}(du^2)^2 + g_{33}(du^3)^2 + 2g_{12}du^1 du^2 + \dots$$
            The metric tensor acts as the "ruler" of spacetime, lowering indices ($v_i = g_{ij} v^j$) while its inverse $g^{ij}$ raises indices ($v^i = g^{ij} v_j$).
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Covariant Basis Vectors &amp; Scale Factors
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        For a position vector $\vec{r}(u^1, u^2, u^3)$, the tangent covariant basis vectors are $\vec{e}_i = \frac{\partial \vec{r}}{\partial u^i}$. The metric components are their mutual inner products:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$g_{ij} = \vec{e}_i \cdot \vec{e}_j, \qquad h_i = |\vec{e}_i| = \sqrt{g_{ii}} \quad (\text{Lamé Scale Factors})$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        In orthogonal curvilinear systems, $g_{ij} = 0$ for $i \neq j$. For example:
        <br>&bull; <strong>Cylindrical $(r, \theta, z)$:</strong> $ds^2 = dr^2 + r^2 d\theta^2 + dz^2 \implies g_{rr} = 1, g_{\theta\theta} = r^2, g_{zz} = 1$.
        <br>&bull; <strong>Spherical $(r, \theta, \phi)$:</strong> $ds^2 = dr^2 + r^2 d\theta^2 + r^2\sin^2\theta\, d\phi^2 \implies g_{rr} = 1, g_{\theta\theta} = r^2, g_{\phi\phi} = r^2\sin^2\theta$.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Christoffel Symbols of the Second Kind
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Unlike Cartesian basis vectors, curvilinear basis vectors change their direction from point to point: $\frac{\partial \vec{e}_i}{\partial u^j} = \Gamma^k_{ij} \vec{e}_k$.
        The <strong>Christoffel symbols</strong> (affine connection coefficients) are completely determined by derivatives of the metric tensor:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\Gamma^k_{ij} = \frac{1}{2} g^{kl}\left(\frac{\partial g_{jl}}{\partial u^i} + \frac{\partial g_{il}}{\partial u^j} - \frac{\partial g_{ij}}{\partial u^l}\right)$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        <em>Note:</em> The Christoffel symbol $\Gamma^k_{ij}$ is symmetric in its lower indices ($\Gamma^k_{ij} = \Gamma^k_{ji}$ in torsion-free spaces), but is <strong>not</strong> a tensor itself!
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Covariant Differentiation
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Because the basis vectors vary spatially, the true directional derivative of a vector field (the <strong>covariant derivative</strong> $\nabla_j$) contains a correction term via $\Gamma$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\text{Contravariant Vector: } \nabla_j V^i = \frac{\partial V^i}{\partial u^j} + \Gamma^i_{jk} V^k$$
        $$\text{Covariant Vector (1-Form): } \nabla_j V_i = \frac{\partial V_i}{\partial u^j} - \Gamma^k_{ji} V_k$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        A curve $x^i(\lambda)$ is a <strong>geodesic</strong> (path of shortest proper distance) if its tangent vector is parallel-transported along itself:
        $$\frac{d^2 x^i}{d\lambda^2} + \Gamma^i_{jk}\frac{dx^j}{d\lambda}\frac{dx^k}{d\lambda} = 0$$
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 4,
        ':prog_id'     => 1,
        ':content'     => $p4_1_content,
        ':algo'        => $p4_1_algo,
        ':explanation' => $p4_1_explanation
    ]);
    echo "  [OK] Inserted Program 2.4.1 (Metric Tensor Studio)\n";

    // Program 2.4.2: Physics Problem: Parallel Transport & Geodesics on Sphere S^2
    $p4_2_content = <<<'PYCODE'
"""
Physics Problem Simulation: Parallel Transport & Holonomy on a 2-Sphere (S^2)
Solves the parallel transport differential equation D V^i / dt = 0 for a tangent vector 
guided along a closed latitude circle theta = theta_0 on a sphere of radius R.
Demonstrates the geometric phase (holonomy deficit angle) Delta_alpha = 2*pi*(1 - cos(theta_0)),
proving the Gauss-Bonnet theorem and explaining the Foucault pendulum precession rate!
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider
from mpl_toolkits.mplot3d import Axes3D

fig = plt.figure(figsize=(9.5, 6.2))
plt.subplots_adjust(bottom=0.28, top=0.92, left=0.08, right=0.92)

# Initial parameters
R_sphere = 2.0
theta0_deg = 45.0  # Latitude angle (colatitude)
phi_progress0 = 180.0 # Traversal progress in deg (0 to 360)

ax = fig.add_subplot(1, 1, 1, projection='3d')

def update_views(th_colat_deg, phi_trav_deg):
    ax.cla()
    
    th0 = np.radians(th_colat_deg)
    phi_max = np.radians(phi_trav_deg)
    
    # Draw sphere wireframe
    u = np.linspace(0, 2 * np.pi, 30)
    v = np.linspace(0, np.pi, 20)
    xs = R_sphere * np.outer(np.cos(u), np.sin(v))
    ys = R_sphere * np.outer(np.sin(u), np.sin(v))
    zs = R_sphere * np.outer(np.ones_like(u), np.cos(v))
    ax.plot_wireframe(xs, ys, zs, color='#38bdf8', alpha=0.12, lw=0.6)
    
    # Draw equator (th = pi/2)
    phi_full = np.linspace(0, 2*np.pi, 100)
    ax.plot(R_sphere * np.cos(phi_full), R_sphere * np.sin(phi_full), np.zeros_like(phi_full),
            color='#64748b', linestyle=':', lw=1.2)
    
    # Draw latitude circle of interest: theta = th0
    r_lat = R_sphere * np.sin(th0)
    z_lat = R_sphere * np.cos(th0)
    ax.plot(r_lat * np.cos(phi_full), r_lat * np.sin(phi_full), z_lat * np.ones_like(phi_full),
            color='#f59e0b', lw=2.2, label=f'Latitude Circle θ₀ = {th_colat_deg:.0f}°')
    
    # North Pole
    ax.plot([0], [0], [R_sphere], 'o', color='#f8fafc', ms=6)
    ax.text(0, 0, R_sphere*1.08, 'North Pole', color='#f8fafc', fontsize=8.5, ha='center')
    
    # Parallel transport along latitude loop:
    # On S^2: V = V^theta e_theta + V^phi e_phi.
    # The parallel transport angle relative to e_theta rotates as:
    # alpha(phi) = -cos(theta_0) * phi
    phi_pts = np.linspace(0, phi_max, 8)
    for p in phi_pts:
        # Position on sphere
        pos_x = r_lat * np.cos(p)
        pos_y = r_lat * np.sin(p)
        pos_z = z_lat
        
        # Local orthonormal basis on sphere surface:
        # e_theta: points south along meridian
        e_th = np.array([np.cos(th0)*np.cos(p), np.cos(th0)*np.sin(p), -np.sin(th0)])
        # e_phi: points east along parallel
        e_ph = np.array([-np.sin(p), np.cos(p), 0.0])
        
        # Vector angle under parallel transport
        alpha = -np.cos(th0) * p
        vec = np.cos(alpha) * e_th + np.sin(alpha) * e_ph
        
        vec_len = 0.7
        is_initial = (p == 0)
        is_final = (abs(p - phi_max) < 1e-4)
        v_col = '#10b981' if is_initial else ('#f43f5e' if is_final else '#38bdf8')
        
        ax.quiver(pos_x, pos_y, pos_z, vec[0]*vec_len, vec[1]*vec_len, vec[2]*vec_len,
                  color=v_col, lw=2.8 if (is_initial or is_final) else 1.8, arrow_length_ratio=0.2)
        
    # Holonomy deficit angle for complete loop (phi = 2*pi)
    holonomy_full_deg = 360.0 * (1.0 - np.cos(th0))
    current_rot_deg = np.degrees(np.cos(th0) * phi_max)
    
    ax.set_xlim(-R_sphere*1.2, R_sphere*1.2)
    ax.set_ylim(-R_sphere*1.2, R_sphere*1.2)
    ax.set_zlim(-R_sphere*1.2, R_sphere*1.2)
    ax.set_xlabel('X', fontsize=8.5)
    ax.set_ylabel('Y', fontsize=8.5)
    ax.set_zlabel('Z', fontsize=8.5)
    ax.set_title(f'Parallel Transport on Sphere S² (Colatitude θ₀ = {th_colat_deg:.0f}°)\nVector Rotation = {current_rot_deg:.1f}° | Full Loop Holonomy Δα = {holonomy_full_deg:.1f}°',
                 fontsize=10.5, color='#38bdf8', fontweight='bold', pad=10)
    ax.legend(loc='upper left', fontsize=7.5, framealpha=0.85)
    ax.view_init(elev=28, azim=-60)

update_views(theta0_deg, phi_progress0)

# Sliders
ax_th = plt.axes([0.15, 0.14, 0.70, 0.025])
ax_phi = plt.axes([0.15, 0.06, 0.70, 0.025])

s_th = Slider(ax_th, 'Colatitude θ₀ (°)', 10.0, 85.0, valinit=theta0_deg, valstep=5.0)
s_phi = Slider(ax_phi, 'Loop Progress φ (°)', 0.0, 360.0, valinit=phi_progress0, valstep=10.0)

def update(val):
    update_views(s_th.val, s_phi.val)
    fig.canvas.draw_idle()

s_th.on_changed(update)
s_phi.on_changed(update)

print("Parallel Transport on Curved Surface Simulation Loaded.")
print("When a vector is parallel transported around a complete closed loop on a curved manifold,")
print("it returns rotated by the solid angle Omega enclosed by the loop (Gauss-Bonnet Theorem)!")
plt.show()
PYCODE;

    $p4_2_algo = "Physics Problem: Geodesics and Parallel Transport of Vectors on a Curved 2-Sphere (S^2) - Holonomy & Gauss-Bonnet";

    $p4_2_explanation = '<div class="theory-article">
    <div style="background: rgba(16, 185, 129, 0.08); border-left: 4px solid var(--emerald); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--emerald); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-globe"></i> Differential Geometry Problem: Holonomy Deficit on a Sphere
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            A key hallmark of intrinsic Riemannian curvature is that <strong>parallel transporting</strong> a vector along a closed circuit does not return the vector to its initial orientation. Consider a unit tangent vector parallel-transported around a constant latitude line $\theta = \theta_0$ on a 2-sphere $S^2$ of radius $R$. Derive the transport differential equation and determine the geometric angle (holonomy deficit $\Delta\alpha$) by which the vector is rotated upon completing one full loop.
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Parallel Transport Equation on $S^2$
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        On a 2-sphere with metric $ds^2 = R^2 d\theta^2 + R^2\sin^2\theta\, d\phi^2$, the only non-zero Christoffel symbols are:
        $$\Gamma^\theta_{\phi\phi} = -\sin\theta\cos\theta, \qquad \Gamma^\phi_{\theta\phi} = \Gamma^\phi_{\phi\theta} = \cot\theta$$
        Along a curve parameterized by longitude $\phi$, the parallel transport equation $\frac{D V^i}{d\phi} = 0$ reads:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\frac{dV^\theta}{d\phi} + \Gamma^\theta_{\phi\phi} V^\phi = \frac{dV^\theta}{d\phi} - \sin\theta_0\cos\theta_0\, V^\phi = 0$$
        $$\frac{dV^\phi}{d\phi} + \Gamma^\phi_{\phi\theta} V^\theta = \frac{dV^\phi}{d\phi} + \cot\theta_0\, V^\theta = 0$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Analytical Solution in Orthonormal Basis
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Converting to normalized components in the orthonormal frame $\{\hat{e}_\theta, \hat{e}_\phi\}$:
        $$v_1 = R\, V^\theta, \qquad v_2 = R\sin\theta_0\, V^\phi$$
        Differentiating yields simple harmonic rotational coupling:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\frac{dv_1}{d\phi} = \cos\theta_0\, v_2, \qquad \frac{dv_2}{d\phi} = -\cos\theta_0\, v_1$$
        $$\begin{pmatrix} v_1(\phi) \\ v_2(\phi) \end{pmatrix} = \begin{pmatrix} \cos(\phi\cos\theta_0) & \sin(\phi\cos\theta_0) \\ -\sin(\phi\cos\theta_0) & \cos(\phi\cos\theta_0) \end{pmatrix} \begin{pmatrix} v_1(0) \\ v_2(0) \end{pmatrix}$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. The Gauss-Bonnet Theorem &amp; Foucault Precession
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Upon completing a full circuit $\Delta\phi = 2\pi$, the vector returns rotated by the <strong>holonomy deficit</strong>:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\Delta\alpha = 2\pi - 2\pi\cos\theta_0 = 2\pi(1 - \cos\theta_0) = \iint_{\text{Cap}} K\, dA = \Omega_{\text{solid}}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        <strong>Profound Physical Connections:</strong>
        <br>&bull; <strong>Gauss-Bonnet Theorem:</strong> The holonomy angle equals the integral of Gaussian curvature $K = 1/R^2$ over the enclosed spherical cap (the enclosed solid angle $\Omega$).
        <br>&bull; <strong>Foucault Pendulum:</strong> A Foucault pendulum is a physical realization of parallel transport on Earth! In one sidereal day ($\Delta t = 24\text{ h}$), its plane of oscillation precesses by angle $\Delta\alpha = 2\pi\cos\theta_0 = 2\pi\sin(\text{latitude})$, taking $T = 24/\sin(\text{lat})$ hours to complete a revolution.
        <br>&bull; <strong>Berry Phase:</strong> In quantum mechanics, adiabatic evolution of a quantum state around a closed parameter loop acquires an identical geometric Berry phase!
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 4,
        ':prog_id'     => 2,
        ':content'     => $p4_2_content,
        ':algo'        => $p4_2_algo,
        ':explanation' => $p4_2_explanation
    ]);
    echo "  [OK] Inserted Program 2.4.2 (Parallel Transport on S^2)\n";

    // =========================================================================
    // SUBTOPIC 2.5: Electromagnetic Field Tensor & Relativistic Invariants
    // =========================================================================

    // Program 2.5.1: 4D Faraday Field Tensor & Lorentz Boost Transformation Explorer
    $p5_1_content = <<<'PYCODE'
"""
4D Faraday Field Tensor & Lorentz Boost Transformation Explorer
Constructs antisymmetric 4x4 Maxwell tensor F^mu,nu unifying E and B fields:
F^mu,nu = [[0, -Ex/c, -Ey/c, -Ez/c], [Ex/c, 0, -Bz, By], [Ey/c, Bz, 0, -Bx], [Ez/c, -By, Bx, 0]].
Applies relativistic Lorentz boost Lambda(beta) along x-axis to demonstrate how motion mixes
electric and magnetic fields: E'_perp = gamma*(E + v x B), B'_perp = gamma*(B - v/c^2 x E).
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider

fig, axes = plt.subplots(1, 2, figsize=(13.2, 5.2))
plt.subplots_adjust(bottom=0.28, top=0.88, left=0.08, right=0.94, wspace=0.32)

c_light = 3.0e8 # m/s

# Initial fields in rest frame S (kV/m and mT)
Ex0 = 0.0
Ey0 = 60.0 # kV/m
Ez0 = 0.0
Bx0 = 0.0
By0 = 0.0
Bz0 = 0.40 # Tesla (or mT)
beta0 = 0.60 # v / c

def lorentz_transform_fields(Ex, Ey, Ez, Bx, By, Bz, beta):
    # Lorentz factor gamma = 1 / sqrt(1 - beta^2)
    beta_val = np.clip(beta, -0.99, 0.99)
    gamma = 1.0 / np.sqrt(1.0 - beta_val**2)
    v = beta_val * c_light
    
    # In Special Relativity with boost along x:
    # E'_x = Ex,  B'_x = Bx
    # E'_y = gamma * (Ey - v * Bz)
    # E'_z = gamma * (Ez + v * By)
    # B'_y = gamma * (By + (v/c^2) * Ez)
    # B'_z = gamma * (Bz - (v/c^2) * Ey)
    
    Ex_p = Ex
    Ey_p = gamma * (Ey - v * Bz)
    Ez_p = gamma * (Ez + v * By)
    
    Bx_p = Bx
    By_p = gamma * (By + (v / c_light**2) * Ez)
    Bz_p = gamma * (Bz - (v / c_light**2) * Ey)
    
    # 4D Faraday Tensor in frame S'
    F_prime = np.array([
        [0.0, -Ex_p/c_light, -Ey_p/c_light, -Ez_p/c_light],
        [Ex_p/c_light, 0.0, -Bz_p, By_p],
        [Ey_p/c_light, Bz_p, 0.0, -Bx_p],
        [Ez_p/c_light, -By_p, Bx_p, 0.0]
    ])
    
    # Relativistic invariants
    # I1 = 2 * (B^2 - E^2/c^2)
    # I2 = -4 * (E · B) / c
    E_mag_sq = Ex**2 + Ey**2 + Ez**2
    B_mag_sq = Bx**2 + By**2 + Bz**2
    I1 = 2.0 * (B_mag_sq - E_mag_sq / c_light**2)
    I2 = -4.0 * (Ex*Bx + Ey*By + Ez*Bz) / c_light
    
    return gamma, Ex_p, Ey_p, Ez_p, Bx_p, By_p, Bz_p, F_prime, I1, I2

def update_views(Ex_val, Ey_val, Bz_val, beta_val):
    gamma, Exp, Eyp, Ezp, Bxp, Byp, Bzp, F_p, I1, I2 = lorentz_transform_fields(
        Ex_val*1e3, Ey_val*1e3, 0.0, 0.0, 0.0, Bz_val, beta_val)
    
    # ----------------------------------------------------
    # Subplot 1: Field Transformation Bar Chart (S vs S')
    # ----------------------------------------------------
    ax1 = axes[0]
    ax1.cla()
    ax1.set_facecolor('#1e293b')
    ax1.grid(True, linestyle=':', alpha=0.35, color='#475569')
    
    categories = ['E_y (kV/m)', 'E\'_y (kV/m)', 'B_z (mT)', 'B\'_z (mT)']
    values = [Ey_val, Eyp / 1e3, Bz_val*1e3, Bzp*1e3]
    colors = ['#38bdf8', '#38bdf8', '#f59e0b', '#f59e0b']
    
    bars = ax1.bar(categories, values, color=colors, alpha=0.85, edgecolor=colors, lw=1.5)
    for bar in bars:
        yval = bar.get_height()
        va = 'bottom' if yval >= 0 else 'top'
        ax1.text(bar.get_x() + bar.get_width()/2.0, yval + (2.0 if yval >= 0 else -6.0),
                 f'{yval:.1f}', ha='center', va=va, fontsize=9.0, color='#f8fafc', fontweight='bold')
        
    ax1.axhline(0, color='#64748b', lw=1.2)
    max_val = max(map(abs, values), default=1.0) * 1.35
    ax1.set_ylim(-max_val, max_val)
    ax1.set_ylabel('Field Magnitude', fontsize=9)
    ax1.set_title(f'Lorentz Boost β = {beta_val:.2f} (γ = {gamma:.2f})\nField Mixing: E\'_y = γ(E_y - v·B_z), B\'_z = γ(B_z - (v/c²)·E_y)',
                  fontsize=10.5, color='#38bdf8', fontweight='bold')

    # ----------------------------------------------------
    # Subplot 2: 4x4 Faraday Tensor F^mu,nu Matrix Display
    # ----------------------------------------------------
    ax2 = axes[1]
    ax2.cla()
    
    # Normalized matrix display
    labels_em = [
        ["0", f"{-Exp/c_light:.2e}", f"{-Eyp/c_light:.2e}", f"{-Ezp/c_light:.2e}"],
        [f"{Exp/c_light:.2e}", "0", f"{-Bzp:.3f}", f"{Byp:.3f}"],
        [f"{Eyp/c_light:.2e}", f"{Bzp:.3f}", "0", f"{-Bxp:.3f}"],
        [f"{Ezp/c_light:.2e}", f"{-Byp:.3f}", f"{Bxp:.3f}", "0"]
    ]
    
    # Custom colored matrix cells
    ax2.set_xlim(-0.5, 3.5)
    ax2.set_ylim(-0.5, 3.5)
    ax2.set_aspect('equal')
    
    for i in range(4):
        for j in range(4):
            fc = '#334155' if i == j else ('#f43f5e' if (i==0 or j==0) else '#f59e0b')
            rect = plt.Rectangle((j - 0.45, 3 - i - 0.45), 0.9, 0.9, facecolor=fc, alpha=0.35, edgecolor=fc, lw=1.5)
            ax2.add_patch(rect)
            ax2.text(j, 3 - i, labels_em[i][j], ha='center', va='center', color='#f8fafc', fontsize=8.0, fontweight='bold')
            
    coord_labels = ['0 (ct)', '1 (x)', '2 (y)', '3 (z)']
    ax2.set_xticks(range(4))
    ax2.set_yticks(range(4))
    ax2.set_xticklabels(coord_labels, fontsize=8.5)
    ax2.set_yticklabels(list(reversed(coord_labels)), fontsize=8.5)
    
    ax2.set_title(f'Transformed 4D Faraday Tensor F\'^μν\nInvariants: I₁ = 2(B² - E²/c²) = {I1:.2e} | I₂ = -4(E·B)/c = {I2:.2e}',
                  fontsize=10.5, color='#f59e0b', fontweight='bold')

update_views(Ex0, Ey0, Bz0, beta0)

# Sliders
ax_b = plt.axes([0.15, 0.17, 0.70, 0.025])
ax_ey = plt.axes([0.15, 0.11, 0.70, 0.025])
ax_bz = plt.axes([0.15, 0.05, 0.70, 0.025])

s_b = Slider(ax_b, 'Boost Velocity β = v/c', -0.95, 0.95, valinit=beta0, valstep=0.05)
s_ey = Slider(ax_ey, 'Rest E_y (kV/m)', -100.0, 100.0, valinit=Ey0, valstep=5.0)
s_bz = Slider(ax_bz, 'Rest B_z (Tesla)', -1.0, 1.0, valinit=Bz0, valstep=0.05)

def update(val):
    update_views(Ex0, s_ey.val, s_bz.val, s_b.val)
    fig.canvas.draw_idle()

s_b.on_changed(update)
s_ey.on_changed(update)
s_bz.on_changed(update)

print("4D Faraday Electromagnetic Tensor Studio Loaded.")
print("Observe: A pure electric field in rest frame S creates a magnetic field in moving frame S'!")
print("Magnetism is fundamentally a relativistic consequence of electrostatics and length contraction.")
plt.show()
PYCODE;

    $p5_1_algo = "4D Faraday Field Tensor & Lorentz Boost Transformation Explorer: Relativistic Unification of Electric and Magnetic Fields";

    $p5_1_explanation = '<div class="theory-article">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <img src="{{SITEURL}}assets/images/visualization/tensors/em_field_tensor.png" alt="Electromagnetic Field Tensor" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <p style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem;"><strong>Figure 2.5:</strong> Electromagnetic Field Tensor $F^{\mu\nu}$: (a) Antisymmetric 4x4 matrix unifying 3 components of $\vec{E}$ and 3 components of $\vec{B}$, (b) Combined field lines under relativistic boost.</p>
    </div>

    <div style="background: rgba(56, 189, 248, 0.08); border-left: 4px solid var(--accent); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--accent); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-bolt-lightning"></i> Unification of $\vec{E}$ and $\vec{B}$ in 4D Spacetime
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            In Newtonian physics, the electric field $\vec{E}$ and magnetic field $\vec{B}$ appear as distinct 3-vectors. In Einstein\'s Special Relativity, however, space and time unify into 4-dimensional Minkowski spacetime. $\vec{E}$ and $\vec{B}$ are not independent physical vectors, but are simply different components of a single, unified antisymmetric rank-2 tensor: the <strong>Faraday electromagnetic field tensor</strong> $F^{\mu\nu}$.
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. The Faraday Tensor $F^{\mu\nu}$
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        From the 4-potential $A^\mu = (\Phi/c, \vec{A})$, the field-strength tensor is defined as the 4-dimensional curl:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$F^{\mu\nu} = \partial^\mu A^\nu - \partial^\nu A^\mu = \begin{pmatrix} 0 & -E_x/c & -E_y/c & -E_z/c \\ E_x/c & 0 & -B_z & B_y \\ E_y/c & B_z & 0 & -B_x \\ E_z/c & -B_y & B_x & 0 \end{pmatrix}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Notice that $F^{\mu\nu}$ is strictly <strong>antisymmetric</strong> ($F^{\mu\nu} = -F^{\nu\mu}$), having $4 \times 3 / 2 = 6$ independent components: exactly 3 electric components $E_i/c$ and 3 magnetic components $B_i$.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Lorentz Transformation of Fields
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Under a Lorentz boost along the $x$-axis with velocity $v = \beta c$ ($\gamma = 1/\sqrt{1 - \beta^2}$), the tensor transforms as $F\'^{\mu\nu} = \Lambda^\mu_\alpha \Lambda^\nu_\beta F^{\alpha\beta}$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$E\'_x = E_x, \qquad E\'_y = \gamma(E_y - v B_z), \qquad E\'_z = \gamma(E_z + v B_y)$$
        $$B\'_x = B_x, \qquad B\'_y = \gamma\left(B_y + \frac{v}{c^2} E_z\right), \qquad B\'_z = \gamma\left(B_z - \frac{v}{c^2} E_y\right)$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        <strong>Profound Physical Consequence:</strong> A charge at rest in frame $S$ produces only an electrostatic field $\vec{E}$. To an observer moving relative to this charge in frame $S\'$, the same charge is a moving current that generates a magnetic field $\vec{B}\'$! Thus, <em>magnetism is fundamentally a relativistic consequence of electrostatics</em>.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Relativistic Field Invariants
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Just as proper time $d\tau$ is invariant, there are two fundamental Lorentz scalar invariants constructed by contracting $F^{\mu\nu}$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$I_1 = F_{\mu\nu} F^{\mu\nu} = 2\left(|\vec{B}|^2 - \frac{|\vec{E}|^2}{c^2}\right) = \text{Invariant under all Lorentz boosts!}$$
        $$I_2 = \frac{1}{2}\epsilon_{\mu\nu\alpha\beta} F^{\mu\nu} F^{\alpha\beta} = -\frac{4}{c}(\vec{E} \cdot \vec{B}) = \text{Pseudoscalar Invariant!}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        &bull; If $\vec{E} \perp \vec{B}$ and $|\vec{E}| = c|\vec{B}|$ in one inertial frame (like an EM wave in vacuum), then $I_1 = 0$ and $I_2 = 0$ in <strong>every</strong> inertial frame.
        <br>&bull; If $|\vec{E}| < c|\vec{B}|$ ($I_1 > 0$), there exists a reference frame where the electric field vanishes completely ($\vec{E}\' = 0$).
        <br>&bull; If $|\vec{E}| > c|\vec{B}|$ ($I_1 < 0$), there exists a frame where the magnetic field vanishes completely ($\vec{B}\' = 0$).
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 5,
        ':prog_id'     => 1,
        ':content'     => $p5_1_content,
        ':algo'        => $p5_1_algo,
        ':explanation' => $p5_1_explanation
    ]);
    echo "  [OK] Inserted Program 2.5.1 (Faraday Tensor Studio)\n";

    // Program 2.5.2: Physics Problem: Relativistic Particle in Crossed E x B Fields & Invariants
    $p5_2_content = <<<'PYCODE'
"""
Physics Problem Simulation: Relativistic Particle in Crossed E x B Fields & Field Invariants
Simulates charged particle motion in crossed fields: E = (0, Ey, 0) and B = (0, 0, Bz).
Computes relativistic drift velocity v_drift = (E x B) / B^2 and explores the three distinct regimes:
1. Ey < c*Bz: Magnetic dominant (I1 > 0) -> Cycloidal drift trajectory
2. Ey = c*Bz: Null field (I1 = 0) -> Parabolic trajectory
3. Ey > c*Bz: Electric dominant (I1 < 0) -> Relativistic hyperbolic acceleration
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider
from scipy.integrate import odeint

fig, axes = plt.subplots(1, 2, figsize=(13.0, 5.2))
plt.subplots_adjust(bottom=0.28, top=0.88, left=0.08, right=0.94, wspace=0.32)

c_light = 3.0e8 # m/s
q_over_m = 1.76e11 # C/kg (electron)

# Initial parameters
Ey0 = 30.0 # kV/m
Bz0 = 0.25 # Tesla
dt_sim = 1.0e-9

def lorentz_derivs(state, t, Ey_val, Bz_val):
    # state = [x, y, vx, vy]
    x, y, vx, vy = state
    v_sq = vx**2 + vy**2
    v = np.sqrt(v_sq)
    gamma = 1.0 / np.sqrt(1.0 - (v / c_light)**2) if v < c_light else 10.0
    
    # Relativistic Lorentz force: d(gamma * m * v)/dt = q(E + v x B)
    # dv/dt = (q / (gamma * m)) * [E + v x B - (v/c^2) * (v · E)]
    Ex, Ez = 0.0, 0.0
    Bx, By = 0.0, 0.0
    
    # E + v x B components:
    # (v x B)_x = vy * Bz - vz * By = vy * Bz
    # (v x B)_y = vz * Bx - vx * Bz = -vx * Bz
    F_lor_x = q_over_m * (vy * Bz_val)
    F_lor_y = q_over_m * (Ey_val - vx * Bz_val)
    
    v_dot_E = vy * Ey_val
    acc_x = (1.0 / gamma) * (F_lor_x - (vx / c_light**2) * q_over_m * v_dot_E)
    acc_y = (1.0 / gamma) * (F_lor_y - (vy / c_light**2) * q_over_m * v_dot_E)
    
    return [vx, vy, acc_x, acc_y]

def update_plots(Ey_kV, Bz_T):
    Ey = Ey_kV * 1e3 # V/m
    Bz = Bz_T        # Tesla
    
    # Invariant I1 = 2 * (B^2 - E^2/c^2)
    I1 = 2.0 * (Bz**2 - (Ey / c_light)**2)
    # Drift velocity v_d = E / B
    v_drift = Ey / Bz if Bz != 0 else 0
    beta_drift = v_drift / c_light
    
    # Time domain for ~5 cyclotron periods: T_c = 2*pi*m / (q*B)
    T_c = 2.0 * np.pi / (q_over_m * Bz) if Bz > 0 else 1.0e-9
    t_span = np.linspace(0, 5 * T_c, 1000)
    
    init_state = [0.0, 0.0, 0.0, 0.0] # Released from rest at origin
    sol = odeint(lorentz_derivs, init_state, t_span, args=(Ey, Bz))
    x_traj, y_traj = sol[:, 0], sol[:, 1]
    vx, vy = sol[:, 2], sol[:, 3]
    v_total = np.sqrt(vx**2 + vy**2)
    
    # ----------------------------------------------------
    # Subplot 1: Particle Trajectory in Crossed Fields
    # ----------------------------------------------------
    ax1 = axes[0]
    ax1.cla()
    ax1.set_facecolor('#1e293b')
    ax1.grid(True, linestyle=':', alpha=0.35, color='#475569')
    
    # Trajectory
    ax1.plot(x_traj * 1e3, y_traj * 1e3, color='#38bdf8', lw=2.2, label='Trajectory')
    ax1.plot(x_traj[0]*1e3, y_traj[0]*1e3, 'o', color='#10b981', ms=8, label='Start (Rest)')
    ax1.plot(x_traj[-1]*1e3, y_traj[-1]*1e3, 's', color='#f43f5e', ms=8, label='End')
    
    # Drift velocity guide line
    drift_line_x = np.linspace(0, max(x_traj)*1e3, 50)
    y_mean = np.mean(y_traj) * 1e3
    ax1.axhline(y_mean, color='#f59e0b', linestyle='--', lw=1.5, label=f'Mean Drift Line (v_d = {v_drift/1e6:.1f} Mm/s)')
    
    # Field indicator arrows
    ax1.quiver(max(x_traj)*0.15*1e3, max(y_traj)*0.85*1e3, 0, 0.3*max(y_traj)*1e3,
               color='#f43f5e', angles='xy', scale_units='xy', scale=1, lw=2.0)
    ax1.text(max(x_traj)*0.18*1e3, max(y_traj)*0.9*1e3, r'Applied $\vec{E}$', color='#f43f5e', fontweight='bold', fontsize=9)
    
    ax1.set_xlabel('X Position (mm)', fontsize=9)
    ax1.set_ylabel('Y Position (mm)', fontsize=9)
    
    regime_txt = "Magnetic Dominant (E < cB): Closed Cycloid" if I1 > 0 else "Electric Dominant (E > cB): Runaway!"
    ax1.set_title(f'Relativistic Crossed Fields Trajectory\nRegime: {regime_txt}',
                  fontsize=10.5, color='#38bdf8', fontweight='bold')
    ax1.legend(loc='lower right', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')

    # ----------------------------------------------------
    # Subplot 2: Velocity Profile & Relativistic Beta
    # ----------------------------------------------------
    ax2 = axes[1]
    ax2.cla()
    ax2.set_facecolor('#1e293b')
    ax2.grid(True, linestyle=':', alpha=0.35, color='#475569')
    
    ax2.plot(t_span * 1e9, v_total / c_light, color='#10b981', lw=2.2, label=r'Total $\beta = v / c$')
    ax2.axhline(beta_drift, color='#f59e0b', linestyle='--', lw=1.8, label=f'Drift β_d = {beta_drift:.3f}')
    ax2.axhline(1.0, color='#f43f5e', linestyle=':', lw=1.5, label='Speed of Light c')
    
    ax2.set_xlim(0, max(t_span)*1e9)
    ax2.set_ylim(0, 1.1)
    ax2.set_xlabel('Time t (nanoseconds)', fontsize=9)
    ax2.set_ylabel('Relativistic Speed v / c', fontsize=9)
    ax2.set_title(f'Speed vs Time | Max Speed = {max(v_total)/c_light:.3f} c\nInvariant I₁ = 2(B² - E²/c²) = {I1:.2e} T²',
                  fontsize=10.5, color='#f59e0b', fontweight='bold')
    ax2.legend(loc='lower right', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')

update_plots(Ey0, Bz0)

# Sliders
ax_ey = plt.axes([0.15, 0.14, 0.70, 0.025])
ax_bz = plt.axes([0.15, 0.06, 0.70, 0.025])

s_ey = Slider(ax_ey, 'E_y (kV/m)', 5.0, 80.0, valinit=Ey0, valstep=5.0)
s_bz = Slider(ax_bz, 'B_z (Tesla)', 0.05, 0.60, valinit=Bz0, valstep=0.02)

def update(val):
    update_plots(s_ey.val, s_bz.val)
    fig.canvas.draw_idle()

s_ey.on_changed(update)
s_bz.on_changed(update)

print("Relativistic Crossed E x B Field Simulation Loaded.")
print("Demonstrates ExB drift velocity v_d = E/B and verifies the relativistic invariant I1.")
plt.show()
PYCODE;

    $p5_2_algo = "Physics Problem: Relativistic Particle in Crossed E x B Fields & Lorentz Invariants (E < cB vs E > cB)";

    $p5_2_explanation = '<div class="theory-article">
    <div style="background: rgba(16, 185, 129, 0.08); border-left: 4px solid var(--emerald); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--emerald); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-atom"></i> Relativistic Dynamics in Crossed $\vec{E} \times \vec{B}$ Fields
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            A charged particle of rest mass $m$ and charge $q$ is released from rest at the origin in mutually perpendicular uniform fields $\vec{E} = (0, E_y, 0)$ and $\vec{B} = (0, 0, B_z)$. By evaluating the relativistic field invariant $I_1 = 2(B^2 - E^2/c^2)$, analyze the particle\'s cycloidal trajectory and determine the reference frame where the electric field vanishes.
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Relativistic Equations of Motion
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The relativistic Lorentz force equation is:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\frac{d(\gamma m \vec{v})}{dt} = q\left(\vec{E} + \vec{v} \times \vec{B}\right), \qquad \gamma = \frac{1}{\sqrt{1 - v^2/c^2}}$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. The Three Field Invariant Regimes
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The nature of particle motion is strictly dictated by the invariant $I_1 = 2(B^2 - E^2/c^2)$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        <p><strong>Regime 1: Magnetic-Dominant ($E < c B \implies I_1 > 0$):</strong></p>
        There exists a moving reference frame $S\'$ boosted along the $x$-axis with velocity:
        $$\vec{v}_d = \frac{\vec{E} \times \vec{B}}{B^2} = \left(\frac{E_y}{B_z}\right)\hat{i} < c$$
        In frame $S\'$, the electric field transforms to exactly zero: $\vec{E}\' = 0$! The particle simply executes pure circular cyclotron motion in frame $S\'$, which transforms to a periodic <strong>cycloidal drift</strong> in the laboratory frame.
        
        <p style="margin-top: 0.75rem;"><strong>Regime 2: Null / Light-Like Field ($E = c B \implies I_1 = 0$):</strong></p>
        No rest frame exists where either field vanishes. The particle follows a semi-parabolic trajectory accelerating asymptotically toward the speed of light.

        <p style="margin-top: 0.75rem;"><strong>Regime 3: Electric-Dominant ($E > c B \implies I_1 < 0$):</strong></p>
        There exists a frame where the magnetic field vanishes ($\vec{B}\' = 0$). The particle experiences unbounded relativistic hyperbolic acceleration along $\vec{E}$.
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Physical Applications
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        $\vec{E} \times \vec{B}$ drift is fundamentally <strong>independent of both charge and mass</strong> (both electrons and ions drift in the exact same direction with velocity $\vec{v}_d = \frac{\vec{E}\times\vec{B}}{B^2}$). 
        This principle underlies:
        <br>&bull; <strong>Wien Velocity Filters:</strong> Used in mass spectrometers to select charged particles with exact velocity $v = E/B$.
        <br>&bull; <strong>Magnetron Microwave Generators:</strong> Found in radar systems and microwave ovens.
        <br>&bull; <strong>Hall Effect Thrusters:</strong> Advanced ion propulsion for deep space probes and satellites.
        <br>&bull; <strong>Tokamak Plasma Confinement:</strong> Guiding center drift in magnetic fusion reactors.
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 5,
        ':prog_id'     => 2,
        ':content'     => $p5_2_content,
        ':algo'        => $p5_2_algo,
        ':explanation' => $p5_2_explanation
    ]);
    echo "  [OK] Inserted Program 2.5.2 (Relativistic Crossed Fields)\n";

    // 5. Update menu.php file on disk via menu_sync helper
    sync_menus_to_file('visualization', $conn);
    echo "[OK] Successfully synchronized program/visualization/menu.php to disk!\n";

    // 6. Update sitemap.xml
    if (function_exists('sync_sitemap_xml')) {
        sync_sitemap_xml($conn);
        echo "[OK] Successfully synchronized sitemap.xml!\n";
    }

    $count = (int)$conn->query("SELECT COUNT(*) FROM visualization WHERE menu_id = 2")->fetchColumn();
    echo "\n=== Seeding Finished Successfully! ===\n";
    echo "Total Simulations in Module 2: {$count} programs across 5 subtopics.\n";

} catch (Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
echo "</pre>";
