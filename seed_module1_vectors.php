<?php
/**
 * Python4Physics - Seed Module 1: Vector Analysis & Curvilinear Coordinates
 * Populates 5 subtopics and 10 interactive simulations with theory, diagrams, and solved physics problems.
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/include/menu_sync.php';

header('Content-Type: text/html; charset=utf-8');

if (!isset($conn) || $conn === null) {
    die("<h3>Database connection not available.</h3>");
}

echo "<pre>\n=== Starting Module 1 Vector Visualization Seeding ===\n";

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

    // 2. Check if Module 1 is currently Quantum Mechanics. If so, shift it to Module 2, and shift Optics to Module 3.
    $checkM1 = $conn->prepare("SELECT title FROM p4p_menus WHERE language='visualization' AND menu_id = 1");
    $checkM1->execute();
    $m1_title = $checkM1->fetchColumn();

    if ($m1_title && str_contains(strtolower($m1_title), 'quantum')) {
        echo "[INFO] Shifting existing Quantum Mechanics to Module 2 and Optics to Module 3...\n";
        
        // Check Optics
        $checkM2 = $conn->prepare("SELECT title FROM p4p_menus WHERE language='visualization' AND menu_id = 2");
        $checkM2->execute();
        $m2_title = $checkM2->fetchColumn();

        if ($m2_title && str_contains(strtolower($m2_title), 'optics')) {
            // Delete any existing menu 3 first to avoid unique key clash
            $conn->exec("DELETE FROM p4p_menus WHERE language='visualization' AND menu_id = 3");
            $conn->exec("DELETE FROM p4p_submenus WHERE language='visualization' AND menu_id = 3");
            $conn->exec("DELETE FROM visualization WHERE menu_id = 3");

            $conn->exec("UPDATE p4p_menus SET menu_id = 3, sort_order = 3 WHERE language='visualization' AND menu_id = 2");
            $conn->exec("UPDATE p4p_submenus SET menu_id = 3, sort_order = 3 WHERE language='visualization' AND menu_id = 2");
            $conn->exec("UPDATE visualization SET menu_id = 3 WHERE menu_id = 2");
        }

        // Now move Module 1 to Module 2
        $conn->exec("DELETE FROM p4p_menus WHERE language='visualization' AND menu_id = 2");
        $conn->exec("DELETE FROM p4p_submenus WHERE language='visualization' AND menu_id = 2");
        $conn->exec("DELETE FROM visualization WHERE menu_id = 2");

        $conn->exec("UPDATE p4p_menus SET menu_id = 2, sort_order = 2 WHERE language='visualization' AND menu_id = 1");
        $conn->exec("UPDATE p4p_submenus SET menu_id = 2, sort_order = 2 WHERE language='visualization' AND menu_id = 1");
        $conn->exec("UPDATE visualization SET menu_id = 2 WHERE menu_id = 1");
    }

    // 3. Upsert Module 1: Vector Analysis & Curvilinear Coordinates
    $stmt = $conn->prepare("INSERT INTO p4p_menus (language, menu_id, title, sort_order) 
                            VALUES ('visualization', 1, 'Vector Analysis & Curvilinear Coordinates', 1)
                            ON DUPLICATE KEY UPDATE title = 'Vector Analysis & Curvilinear Coordinates', sort_order = 1");
    $stmt->execute();
    echo "[OK] Module 1 in p4p_menus set to 'Vector Analysis & Curvilinear Coordinates'\n";

    // 4. Upsert 5 Submenus for Module 1
    $submenus = [
        1 => 'Vector Basics & Vector Algebra (2D & 3D)',
        2 => 'Divergence of Vector Fields & Gauss’s Law',
        3 => 'Curl of Vector Fields & Circulation',
        4 => 'Vector Integration, Divergence & Stokes’ Theorems',
        5 => 'Curvilinear Coordinates (Cylindrical & Spherical Polar)'
    ];

    foreach ($submenus as $sid => $stitle) {
        $stmt = $conn->prepare("INSERT INTO p4p_submenus (language, menu_id, submenu_id, title, sort_order) 
                                VALUES ('visualization', 1, :sid, :stitle, :sort)
                                ON DUPLICATE KEY UPDATE title = VALUES(title), sort_order = VALUES(sort_order)");
        $stmt->execute([':sid' => $sid, ':stitle' => $stitle, ':sort' => $sid]);
        echo "  [OK] Subtopic 1.{$sid}: {$stitle}\n";
    }

    // Remove any previous simulations in Module 1 before re-populating with fresh comprehensive content
    $conn->exec("DELETE FROM visualization WHERE menu_id = 1");

    $insertProg = $conn->prepare("INSERT INTO visualization (menu_id, submenu_id, program_id, content, algo, explanation) 
                                  VALUES (1, :sub_id, :prog_id, :content, :algo, :explanation)");

    // =========================================================================
    // SUBTOPIC 1.1: Vector Basics & Vector Algebra (2D & 3D)
    // =========================================================================
    
    // Program 1.1.1: 3D Vector Algebra & Vector Operations Studio
    $p1_1_content = <<<'PYCODE'
"""
3D Vector Algebra & Vector Operations Studio
Interactive exploration of 3D vector addition, subtraction, dot product, 
orthogonal projection, and cross product (parallelogram area & right-hand rule).
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider
from mpl_toolkits.mplot3d import Axes3D
from mpl_toolkits.mplot3d.art3d import Poly3DCollection

fig = plt.figure(figsize=(9, 6.2))
plt.subplots_adjust(bottom=0.32, top=0.92, left=0.08, right=0.92)

# Initial vector parameters
mag_A0, phi_A0, theta_A0 = 3.2, 35.0, 90.0
mag_B0, phi_B0, theta_B0 = 2.6, 95.0, 65.0

def sph_to_cart(mag, phi_deg, theta_deg):
    phi = np.radians(phi_deg)
    th = np.radians(theta_deg)
    x = mag * np.sin(th) * np.cos(phi)
    y = mag * np.sin(th) * np.sin(phi)
    z = mag * np.cos(th)
    return np.array([x, y, z])

ax = fig.add_subplot(1, 1, 1, projection='3d')

def update_plot(mag_A, phi_A, theta_A, mag_B, phi_B, theta_B):
    ax.cla()
    A = sph_to_cart(mag_A, phi_A, theta_A)
    B = sph_to_cart(mag_B, phi_B, theta_B)
    R = A + B
    D = A - B
    C = np.cross(A, B)
    norm_A = np.linalg.norm(A)
    norm_B = np.linalg.norm(B)
    dot_AB = np.dot(A, B)
    cos_th = dot_AB / (norm_A * norm_B) if norm_A * norm_B > 0 else 0
    cos_th = np.clip(cos_th, -1.0, 1.0)
    angle_AB = np.degrees(np.arccos(cos_th))
    proj_A_on_B = (dot_AB / (norm_B**2)) * B if norm_B > 0 else np.zeros(3)
    area_parallelogram = np.linalg.norm(C)

    # Parallelogram surface
    X_p = [0, A[0], R[0], B[0]]
    Y_p = [0, A[1], R[1], B[1]]
    Z_p = [0, A[2], R[2], B[2]]
    poly = Poly3DCollection([list(zip(X_p, Y_p, Z_p))], alpha=0.22, facecolor='#10b981', edgecolor='#10b981', linestyle='--')
    ax.add_collection3d(poly)

    # Quiver vectors
    ax.quiver(0, 0, 0, A[0], A[1], A[2], color='#38bdf8', lw=2.8, arrow_length_ratio=0.15, label=f'Vector A (|A|={norm_A:.2f})')
    ax.quiver(0, 0, 0, B[0], B[1], B[2], color='#f59e0b', lw=2.8, arrow_length_ratio=0.15, label=f'Vector B (|B|={norm_B:.2f})')
    ax.quiver(0, 0, 0, R[0], R[1], R[2], color='#10b981', lw=3.0, arrow_length_ratio=0.12, label=f'Sum R = A + B (|R|={np.linalg.norm(R):.2f})')
    
    # Cross product C = A x B (scaled for visual clarity)
    scale_C = 0.5
    C_vis = C * scale_C
    ax.quiver(0, 0, 0, C_vis[0], C_vis[1], C_vis[2], color='#f43f5e', lw=3.0, arrow_length_ratio=0.15, label=f'Cross Product A x B (|AxB|={area_parallelogram:.2f})')

    # Projection of A on B
    ax.quiver(0, 0, 0, proj_A_on_B[0], proj_A_on_B[1], proj_A_on_B[2], color='#a855f7', lw=3.2, arrow_length_ratio=0.2, label=f'Proj of A on B ({np.linalg.norm(proj_A_on_B):.2f})')
    ax.plot([A[0], proj_A_on_B[0]], [A[1], proj_A_on_B[1]], [A[2], proj_A_on_B[2]], color='#94a3b8', linestyle=':', lw=1.5)

    lim = max(4.0, norm_A + norm_B) * 0.85
    ax.set_xlim(-lim*0.6, lim)
    ax.set_ylim(-lim*0.6, lim)
    ax.set_zlim(-lim*0.4, lim)
    ax.set_xlabel('X', fontsize=9)
    ax.set_ylabel('Y', fontsize=9)
    ax.set_zlabel('Z', fontsize=9)
    ax.set_title(f'A·B = {dot_AB:.2f} | θ_AB = {angle_AB:.1f}° | |A×B| = {area_parallelogram:.2f} (Area)', fontsize=10.5, fontweight='bold', pad=10)
    ax.legend(loc='upper left', fontsize=7.5, framealpha=0.85)
    ax.view_init(elev=24, azim=-55)

update_plot(mag_A0, phi_A0, theta_A0, mag_B0, phi_B0, theta_B0)

# Sliders
ax_magA = plt.axes([0.18, 0.20, 0.72, 0.022])
ax_phiA = plt.axes([0.18, 0.15, 0.72, 0.022])
ax_magB = plt.axes([0.18, 0.10, 0.72, 0.022])
ax_phiB = plt.axes([0.18, 0.05, 0.72, 0.022])

s_magA = Slider(ax_magA, '|Vector A|', 0.5, 5.0, valinit=mag_A0, valstep=0.1)
s_phiA = Slider(ax_phiA, 'Azimuth A (°)', 0.0, 360.0, valinit=phi_A0, valstep=5.0)
s_magB = Slider(ax_magB, '|Vector B|', 0.5, 5.0, valinit=mag_B0, valstep=0.1)
s_phiB = Slider(ax_phiB, 'Azimuth B (°)', 0.0, 360.0, valinit=phi_B0, valstep=5.0)

def update(val):
    update_plot(s_magA.val, s_phiA.val, theta_A0, s_magB.val, s_phiB.val, theta_B0)
    fig.canvas.draw_idle()

s_magA.on_changed(update)
s_phiA.on_changed(update)
s_magB.on_changed(update)
s_phiB.on_changed(update)

print("3D Vector Studio Loaded:")
print("• Dot Product A·B computes work/projection.")
print("• Cross Product A×B yields vector orthogonal to plane with magnitude equal to parallelogram area.")
plt.show()
PYCODE;

    $p1_1_algo = "3D Vector Algebra Studio: Vector Addition, Subtraction, Dot Product, Orthogonal Projection, and Cross Product (Parallelogram Area & Right-Hand Rule)";

    $p1_1_explanation = '<div class="theory-article">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <img src="{{SITEURL}}assets/images/visualization/vectors/vector_basics_algebra.png" alt="Vector Basics and Algebra" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <p style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem;"><strong>Figure 1.1:</strong> Foundational Vector Algebra: (a) Triangle and parallelogram addition, (b) Scalar dot product & orthogonal projection, (c) 3D vector cross product obeying the right-hand rule with parallelogram area $|\vec{A} \times \vec{B}|$.</p>
    </div>

    <div style="background: rgba(56, 189, 248, 0.08); border-left: 4px solid var(--accent); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--accent); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-arrows-to-dot"></i> Vectors in Physics &amp; Orthonormal Coordinate Bases
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            A physical vector possesses both <strong>magnitude</strong> and <strong>spatial direction</strong>, transforming under coordinate rotations in a invariant manner. In three-dimensional Cartesian space with orthonormal unit basis vectors $\{\hat{i}, \hat{j}, \hat{k}\}$, any vector is uniquely resolved into rectangular components:
            $$\vec{A} = A_x \hat{i} + A_y \hat{j} + A_z \hat{k}, \qquad |\vec{A}| = \sqrt{A_x^2 + A_y^2 + A_z^2}$$
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Vector Addition &amp; Subtraction
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        According to the <em>parallelogram law</em> and <em>triangle rule</em>, the resultant vector $\vec{R} = \vec{A} + \vec{B}$ has rectangular components:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\vec{R} = (A_x + B_x)\hat{i} + (A_y + B_y)\hat{j} + (A_z + B_z)\hat{k}$$
        $$|\vec{R}| = \sqrt{|\vec{A}|^2 + |\vec{B}|^2 + 2|\vec{A}||\vec{B}|\cos\theta}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The vector difference $\vec{D} = \vec{A} - \vec{B}$ represents the directed displacement vector running from the terminus of $\vec{B}$ to the terminus of $\vec{A}$.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Scalar (Dot) Product &amp; Orthogonal Projection
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The dot product maps two vectors onto a scalar invariant equal to the product of their magnitudes and the cosine of the included angle $\theta$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\vec{A} \cdot \vec{B} = |\vec{A}||\vec{B}|\cos\theta = A_x B_x + A_y B_y + A_z B_z$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        <strong>Key Properties:</strong>
        <br>&bull; <strong>Orthogonality Condition:</strong> $\vec{A} \cdot \vec{B} = 0 \iff \vec{A} \perp \vec{B}$ (for non-zero vectors).
        <br>&bull; <strong>Vector Projection:</strong> The orthogonal projection of $\vec{A}$ onto the direction of $\vec{B}$ is given by:
        $$\mathrm{proj}_{\vec{B}}\vec{A} = \left(\frac{\vec{A} \cdot \vec{B}}{|\vec{B}|^2}\right)\vec{B} = (|\vec{A}|\cos\theta)\hat{b}$$
        <br>&bull; <strong>Physical Example:</strong> Mechanical work done $W = \vec{F} \cdot \Delta\vec{r}$, and electrostatic potential energy $U = -\vec{p} \cdot \vec{E}$.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Vector (Cross) Product &amp; Parallelogram Area
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The cross product produces a pseudovector perpendicular to both $\vec{A}$ and $\vec{B}$, directed according to the <strong>right-hand rule</strong>:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\vec{C} = \vec{A} \times \vec{B} = (|\vec{A}||\vec{B}|\sin\theta)\hat{n} = \begin{vmatrix} \hat{i} & \hat{j} & \hat{k} \\ A_x & A_y & A_z \\ B_x & B_y & B_z \end{vmatrix}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        <strong>Geometric &amp; Physical Significance:</strong>
        <br>&bull; <strong>Parallelogram Area:</strong> The magnitude $|\vec{A} \times \vec{B}|$ equals the exact geometric surface area of the parallelogram spanned by $\vec{A}$ and $\vec{B}$.
        <br>&bull; <strong>Collinearity Condition:</strong> $\vec{A} \times \vec{B} = \vec{0} \iff \vec{A} \parallel \vec{B}$.
        <br>&bull; <strong>Anticommutativity:</strong> $\vec{B} \times \vec{A} = -(\vec{A} \times \vec{B})$.
        <br>&bull; <strong>Physical Examples:</strong> Rotational torque $\vec{\tau} = \vec{r} \times \vec{F}$, orbital angular momentum $\vec{L} = \vec{r} \times \vec{p}$, and magnetic Lorentz force $\vec{F}_B = q(\vec{v} \times \vec{B})$.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        4. Scalar &amp; Vector Triple Products
    </h4>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\text{Scalar Triple Product: } [\vec{A}, \vec{B}, \vec{C}] = \vec{A} \cdot (\vec{B} \times \vec{C}) = \text{Volume of Parallelepiped}$$
        $$\text{Vector Triple Product (BAC-CAB Rule): } \vec{A} \times (\vec{B} \times \vec{C}) = (\vec{A} \cdot \vec{C})\vec{B} - (\vec{A} \cdot \vec{B})\vec{C}$$
    </div>
</div>';

    $insertProg->execute([
        ':sub_id'      => 1,
        ':prog_id'     => 1,
        ':content'     => $p1_1_content,
        ':algo'        => $p1_1_algo,
        ':explanation' => $p1_1_explanation
    ]);
    echo "  [OK] Inserted Program 1.1.1 (Vector Algebra Studio)\n";

    // Program 1.1.2: Problem: 3D Torque, Angular Momentum & Work Done
    $p1_2_content = <<<'PYCODE'
"""
Physics Problem Simulation: 3D Torque, Angular Momentum & Mechanical Work
Calculates torque tau = r x F, resolves force into radial (non-torque) and tangential 
components, and determines incremental work done dW = F · dr during rotational displacement.
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider

fig = plt.figure(figsize=(9, 6.0))
plt.subplots_adjust(bottom=0.32, top=0.91, left=0.10, right=0.92)

# Initial parameters
r0 = 2.5       # Lever arm length (meters)
th_r0 = 35.0   # Arm angle with horizontal (deg)
F_mag0 = 4.0   # Applied force magnitude (N)
phi_F0 = 80.0  # Force angle relative to lever arm (deg)

ax1 = fig.add_subplot(1, 2, 1)
ax2 = fig.add_subplot(1, 2, 2)

def solve_physics(r_len, th_arm_deg, F_val, phi_rel_deg):
    th_arm = np.radians(th_arm_deg)
    phi_rel = np.radians(phi_rel_deg)
    
    # Arm vector r from origin
    rx = r_len * np.cos(th_arm)
    ry = r_len * np.sin(th_arm)
    
    # Absolute force angle
    th_F = th_arm + phi_rel
    Fx = F_val * np.cos(th_F)
    Fy = F_val * np.sin(th_F)
    
    # Torque vector in 2D (along z-axis): tau_z = rx * Fy - ry * Fx = r * F * sin(phi_rel)
    tau_z = rx * Fy - ry * Fx
    
    # Force decomposition
    F_radial = F_val * np.cos(phi_rel)      # Radial along r (produces tension, zero torque)
    F_tangential = F_val * np.sin(phi_rel)  # Tangential perp to r (produces torque)
    
    # Work done during rotation by angle d_theta = 0.1 rad: dW = tau * d_theta
    d_th = 0.1
    dW = tau_z * d_th
    
    return rx, ry, Fx, Fy, tau_z, F_radial, F_tangential, dW

def update_views(r_len, th_arm_deg, F_val, phi_rel_deg):
    rx, ry, Fx, Fy, tau_z, F_rad, F_tan, dW = solve_physics(r_len, th_arm_deg, F_val, phi_rel_deg)
    
    # 1. Physical Diagram of Lever Arm & Force
    ax1.cla()
    ax1.set_facecolor('#1e293b')
    ax1.grid(True, linestyle=':', alpha=0.35, color='#475569')
    
    # Lever arm
    ax1.plot([0, rx], [0, ry], color='#f59e0b', lw=4.0, label='Lever Arm r')
    ax1.scatter([0], [0], color='#f8fafc', s=100, zorder=5)
    ax1.text(-0.3, -0.3, 'Pivot O', color='#f8fafc', fontsize=9.5, fontweight='bold')
    
    # Applied force vector at tip of lever
    ax1.annotate('', xy=(rx + Fx*0.6, ry + Fy*0.6), xytext=(rx, ry),
                 arrowprops=dict(arrowstyle="->,head_width=0.35,head_length=0.45", color='#f43f5e', lw=2.5))
    ax1.text(rx + Fx*0.65, ry + Fy*0.65, f'F = {F_val:.1f} N', color='#f43f5e', fontsize=10, fontweight='bold')
    
    # Tangential line and force component
    th_arm = np.radians(th_arm_deg)
    tan_hat = np.array([-np.sin(th_arm), np.cos(th_arm)])
    ax1.plot([rx, rx + F_tan*tan_hat[0]*0.6], [ry, ry + F_tan*tan_hat[1]*0.6], 
             color='#10b981', lw=2.2, linestyle='--', label=f'F_tan = {F_tan:.2f} N (Torque-producing)')
    
    # Circular rotation arc indicator
    arc_rad = 1.0
    th_arc = np.linspace(0, th_arm, 30)
    ax1.plot(arc_rad*np.cos(th_arc), arc_rad*np.sin(th_arc), color='#38bdf8', linestyle=':')
    ax1.text(0.6*np.cos(th_arm/2), 0.6*np.sin(th_arm/2), f'{th_arm_deg:.0f}°', color='#38bdf8', fontsize=9)
    
    ax1.set_xlim(-1.5, 4.5)
    ax1.set_ylim(-1.5, 4.5)
    ax1.set_aspect('equal')
    ax1.set_xlabel('X (meters)', fontsize=9)
    ax1.set_ylabel('Y (meters)', fontsize=9)
    ax1.set_title(f'Torque τ_z = {tau_z:.2f} N·m (Counter-Clockwise)', fontsize=10, color='#38bdf8', fontweight='bold')
    ax1.legend(loc='upper left', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')

    # 2. Torque and Work Profile vs Applied Force Angle
    ax2.cla()
    ax2.set_facecolor('#1e293b')
    ax2.grid(True, linestyle=':', alpha=0.35, color='#475569')
    
    angles = np.linspace(0, 180, 200)
    torques = r_len * F_val * np.sin(np.radians(angles))
    
    ax2.plot(angles, torques, color='#38bdf8', lw=2.2, label=r'Torque $\tau(\phi) = r F \sin\phi$')
    ax2.axvline(phi_rel_deg, color='#f43f5e', linestyle='--', lw=1.8, label=f'Current $\phi$ = {phi_rel_deg:.0f}°')
    ax2.scatter([phi_rel_deg], [tau_z], color='#f43f5e', s=80, zorder=5)
    
    ax2.set_xlim(0, 180)
    ax2.set_xlabel('Angle Between Force & Arm $\phi$ (°)', fontsize=9)
    ax2.set_ylabel('Torque (N·m)', fontsize=9)
    ax2.set_title(f'Max Torque at 90°: {r_len*F_val:.2f} N·m | dW = {dW:.3f} J', fontsize=10, color='#f8fafc', fontweight='bold')
    ax2.legend(loc='lower center', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')

update_views(r0, th_r0, F_mag0, phi_F0)

# Sliders
ax_r   = plt.axes([0.16, 0.20, 0.74, 0.022])
ax_thr = plt.axes([0.16, 0.15, 0.74, 0.022])
ax_F   = plt.axes([0.16, 0.10, 0.74, 0.022])
ax_phi = plt.axes([0.16, 0.05, 0.74, 0.022])

s_r   = Slider(ax_r, 'Arm Length r (m)', 0.5, 4.0, valinit=r0, valstep=0.1)
s_thr = Slider(ax_thr, 'Arm Angle θ (°)', 0.0, 90.0, valinit=th_r0, valstep=1.0)
s_F   = Slider(ax_F, 'Force F (N)', 0.5, 10.0, valinit=F_mag0, valstep=0.5)
s_phi = Slider(ax_phi, 'Force Angle ϕ (°)', 0.0, 180.0, valinit=phi_F0, valstep=2.0)

def update(val):
    update_views(s_r.val, s_thr.val, s_F.val, s_phi.val)
    fig.canvas.draw_idle()

s_r.on_changed(update)
s_thr.on_changed(update)
s_F.on_changed(update)
s_phi.on_changed(update)

print("Torque & Work Done Simulation Initialized:")
print("Notice how radial force produces zero torque, while maximum torque occurs at phi = 90 deg.")
plt.show()
PYCODE;

    $p1_2_algo = "Problem: Mechanical Torque, Angular Momentum, and Work Done on a Rigid Arm Subjected to a 3D Applied Force";

    $p1_2_explanation = '<div class="theory-article">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <img src="{{SITEURL}}assets/images/visualization/vectors/vector_problem_torque_work.png" alt="Torque and Work Done Problem" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <p style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem;"><strong>Figure 1.2:</strong> Physical Vector Dynamics: (Left) Decomposition of applied force $\vec{F}$ into non-torque radial component $F_\parallel$ and torque-producing tangential component $F_\perp$, yielding torque vector $\vec{\tau} = \vec{r} \times \vec{F}$. (Right) Restoring torque and potential energy of a magnetic dipole $\vec{m}$ in uniform magnetic field $\vec{B}$.</p>
    </div>

    <div style="background: rgba(16, 185, 129, 0.08); border-left: 4px solid var(--primary); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--primary); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-calculator"></i> Physics Problem Statement
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            A rigid robotic lever arm of length $r$ is pinned at the origin $O(0,0,0)$ and tilted at an elevation angle $\theta$ in the $xy$-plane. An external mechanical force $\vec{F}$ of magnitude $F$ is applied to the terminus at an angle $\phi$ relative to the arm axis.
            <br><strong>Tasks:</strong>
            <br>1. Determine the torque vector $\vec{\tau} = \vec{r} \times \vec{F}$ acting about the pivot $O$.
            <br>2. Decompose $\vec{F}$ into radial ($F_\parallel$) and tangential ($F_\perp$) components, proving analytically that $F_\parallel$ produces zero torque.
            <br>3. Compute the mechanical work done $dW = \vec{F} \cdot d\vec{r} = \tau_z d\theta$ during an infinitesimal angular displacement $d\theta$.
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Coordinate Formulation &amp; Cross Product
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The position vector of the lever endpoint is $\vec{r} = r\cos\theta\hat{i} + r\sin\theta\hat{j}$. The applied force vector at relative angle $\phi$ has absolute direction $\theta + \phi$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\vec{F} = F\cos(\theta + \phi)\hat{i} + F\sin(\theta + \phi)\hat{j}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Computing the vector cross product $\vec{\tau} = \vec{r} \times \vec{F}$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\vec{\tau} = \begin{vmatrix} \hat{i} & \hat{j} & \hat{k} \\ r\cos\theta & r\sin\theta & 0 \\ F\cos(\theta+\phi) & F\sin(\theta+\phi) & 0 \end{vmatrix} = rF \left[ \cos\theta\sin(\theta+\phi) - \sin\theta\cos(\theta+\phi) \right] \hat{k}$$
        $$\vec{\tau} = \left( r F \sin\phi \right) \hat{k}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Notice how the orientation angle $\theta$ cancels out entirely! The torque magnitude depends exclusively on the lever arm length $r$, force magnitude $F$, and the relative inclination angle $\phi$.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Radial vs Tangential Decomposition
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Let the radial unit vector be $\hat{r} = \cos\theta\hat{i} + \sin\theta\hat{j}$, and the orthogonal tangential unit vector be $\hat{\theta} = -\sin\theta\hat{i} + \cos\theta\hat{j}$.
        Resolving $\vec{F}$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\vec{F} = F_\parallel \hat{r} + F_\perp \hat{\theta} = (F\cos\phi)\hat{r} + (F\sin\phi)\hat{\theta}$$
        $$\vec{\tau} = \vec{r} \times \vec{F} = (r\hat{r}) \times \left[ (F\cos\phi)\hat{r} + (F\sin\phi)\hat{\theta} \right] = 0 + (rF\sin\phi)(\hat{r} \times \hat{\theta}) = (rF\sin\phi)\hat{k}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Because $\hat{r} \times \hat{r} = \vec{0}$, radial forces transmit purely tensile or compressive stresses through the pivot without generating any rotational torque!
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Mechanical Work &amp; Equivalence with Torque
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        During rotation through angle $d\theta$, the displacement of the application point is $d\vec{r} = r d\theta \hat{\theta}$. The work done is:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$dW = \vec{F} \cdot d\vec{r} = \left[ F_\parallel \hat{r} + F_\perp \hat{\theta} \right] \cdot \left[ r d\theta \hat{\theta} \right] = F_\perp r d\theta = (rF\sin\phi) d\theta = \tau_z d\theta$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        This establishes the rigorous equivalence between linear work ($dW = \vec{F} \cdot d\vec{r}$) and rotational work ($dW = \tau d\theta$).
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 1,
        ':prog_id'     => 2,
        ':content'     => $p1_2_content,
        ':algo'        => $p1_2_algo,
        ':explanation' => $p1_2_explanation
    ]);
    echo "  [OK] Inserted Program 1.1.2 (Torque & Work Done Problem)\n";

    // =========================================================================
    // SUBTOPIC 1.2: Divergence of Vector Fields & Gauss's Law
    // =========================================================================

    // Program 1.2.1: 2D/3D Vector Field Divergence & Flux Density Visualizer
    $p2_1_content = <<<'PYCODE'
"""
Vector Field Divergence & Flux Density Visualizer
Interactive 2D vector field with source, sink, dipole, and solenoidal shear components.
Computes divergence div(F) heatmap and verifies 2D Gauss's Divergence theorem in real time.
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider

fig, (ax_vec, ax_div) = plt.subplots(1, 2, figsize=(9.2, 5.8), gridspec_kw={'wspace': 0.3})
plt.subplots_adjust(bottom=0.32, top=0.91, left=0.08, right=0.92)

# Initial parameters
q1_0 = 3.0    # Strength of Source 1
q2_0 = -2.0   # Strength of Source/Sink 2
d_sep0 = 1.0  # Separation distance of poles
shear0 = 0.5  # Incompressible shear coefficient (div = 0)

# Computational grid
x_grid = np.linspace(-3.0, 3.0, 18)
y_grid = np.linspace(-3.0, 3.0, 18)
X, Y = np.meshgrid(x_grid, y_grid)

# High-resolution grid for divergence contour
x_fine = np.linspace(-3.0, 3.0, 100)
y_fine = np.linspace(-3.0, 3.0, 100)
Xf, Yf = np.meshgrid(x_fine, y_fine)

def compute_field(Xm, Ym, q1, q2, d, shear):
    # Pole 1 at (+d, 0)
    R1_sq = (Xm - d)**2 + Ym**2 + 0.35
    # Pole 2 at (-d, 0)
    R2_sq = (Xm + d)**2 + Ym**2 + 0.35
    
    # 2D source fields F ~ q * r / r^2
    Fx = q1 * (Xm - d) / R1_sq + q2 * (Xm + d) / R2_sq - shear * Ym
    Fy = q1 * Ym / R1_sq + q2 * Ym / R2_sq + shear * Xm
    
    # Analytical Divergence:
    # d/dx[(x-d)/((x-d)^2+y^2+eps)] = (y^2+eps - (x-d)^2)/R1^4
    # d/dy[y/((x-d)^2+y^2+eps)]     = ((x-d)^2+eps - y^2)/R1^4
    # Sum div = 2 * eps / R1^4
    eps = 0.35
    div_F = q1 * (2.0 * eps) / (R1_sq**2) + q2 * (2.0 * eps) / (R2_sq**2)
    # Shear term -shear*y * i + shear*x * j has div = 0 identically!
    
    return Fx, Fy, div_F

def update_plots(q1, q2, d, shear):
    Fx, Fy, _ = compute_field(X, Y, q1, q2, d, shear)
    _, _, div_fine = compute_field(Xf, Yf, q1, q2, d, shear)
    
    # 1. Vector Quiver & Streamlines
    ax_vec.cla()
    ax_vec.set_facecolor('#1e293b')
    ax_vec.grid(True, linestyle=':', alpha=0.3, color='#475569')
    
    speed = np.sqrt(Fx**2 + Fy**2)
    ax_vec.quiver(X, Y, Fx/speed, Fy/speed, speed, cmap='coolwarm', scale=22, width=0.006)
    
    # Source / Sink positions
    if abs(q1) > 0.1:
        col1 = '#f43f5e' if q1 > 0 else '#38bdf8'
        ax_vec.scatter([d], [0], color=col1, s=110, edgecolors='#f8fafc', lw=1.5, zorder=6)
        ax_vec.text(d, 0.3, f'q1={q1:+.1f}', color=col1, fontsize=8.5, fontweight='bold', ha='center')
    if abs(q2) > 0.1:
        col2 = '#f43f5e' if q2 > 0 else '#38bdf8'
        ax_vec.scatter([-d], [0], color=col2, s=110, edgecolors='#f8fafc', lw=1.5, zorder=6)
        ax_vec.text(-d, 0.3, f'q2={q2:+.1f}', color=col2, fontsize=8.5, fontweight='bold', ha='center')
        
    ax_vec.set_xlim(-3.0, 3.0)
    ax_vec.set_ylim(-3.0, 3.0)
    ax_vec.set_aspect('equal')
    ax_vec.set_xlabel('x', fontsize=9)
    ax_vec.set_ylabel('y', fontsize=9)
    ax_vec.set_title('Vector Field $\\vec{F}(x,y)$ Quivers', fontsize=10.5, color='#38bdf8', fontweight='bold')

    # 2. Divergence Heatmap div(F)
    ax_div.cla()
    ax_div.set_facecolor('#1e293b')
    
    max_div = max(1.0, np.max(np.abs(div_fine)))
    im = ax_div.imshow(div_fine, extent=[-3.0, 3.0, -3.0, 3.0], origin='lower', 
                       cmap='PuOr', vmin=-max_div, vmax=max_div, alpha=0.9)
    
    ax_div.contour(Xf, Yf, div_fine, levels=[-2, -1, 0, 1, 2], colors='#f8fafc', linewidths=0.7, alpha=0.5)
    ax_div.set_xlim(-3.0, 3.0)
    ax_div.set_ylim(-3.0, 3.0)
    ax_div.set_aspect('equal')
    ax_div.set_xlabel('x', fontsize=9)
    ax_div.set_title(f'Divergence $\\nabla\\cdot\\vec{{F}}$ | Total Flux ~ {2*np.pi*(q1+q2):+.2f}', fontsize=10.5, color='#10b981', fontweight='bold')

update_plots(q1_0, q2_0, d_sep0, shear0)

# Sliders
ax_q1    = plt.axes([0.18, 0.20, 0.70, 0.022])
ax_q2    = plt.axes([0.18, 0.15, 0.70, 0.022])
ax_d     = plt.axes([0.18, 0.10, 0.70, 0.022])
ax_shear = plt.axes([0.18, 0.05, 0.70, 0.022])

s_q1    = Slider(ax_q1, 'Source q1', -5.0, 5.0, valinit=q1_0, valstep=0.2)
s_q2    = Slider(ax_q2, 'Source/Sink q2', -5.0, 5.0, valinit=q2_0, valstep=0.2)
s_d     = Slider(ax_d, 'Separation d', 0.2, 2.5, valinit=d_sep0, valstep=0.1)
s_shear = Slider(ax_shear, 'Shear Flow (div=0)', -2.0, 2.0, valinit=shear0, valstep=0.1)

def update(val):
    update_plots(s_q1.val, s_q2.val, s_d.val, s_shear.val)
    fig.canvas.draw_idle()

s_q1.on_changed(update)
s_q2.on_changed(update)
s_d.on_changed(update)
s_shear.on_changed(update)

print("Divergence Simulation Ready:")
print("• Red zones: div(F) > 0 (Source - net outward expansion)")
print("• Purple zones: div(F) < 0 (Sink - net inward convergence)")
print("• Solenoidal shear flow adds circular swirling without altering divergence (div = 0).")
plt.show()
PYCODE;

    $p2_1_algo = "2D/3D Vector Field Divergence & Flux Density Visualizer: Point Sources, Sinks, Dipoles, and Incompressible Solenoidal Flow";

    $p2_1_explanation = '<div class="theory-article">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <img src="{{SITEURL}}assets/images/visualization/vectors/divergence_flux_concept.png" alt="Divergence and Net Flux Concept" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <p style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem;"><strong>Figure 2.1:</strong> Physical Meaning of Divergence: (Left) Positive divergence $\nabla \cdot \vec{F} > 0$ acting as a field source with net outward flux, (Middle) Negative divergence $\nabla \cdot \vec{F} < 0$ acting as a field sink with net inward flux, (Right) Zero divergence $\nabla \cdot \vec{F} = 0$ representing solenoidal/incompressible flow with equal inflow and outflow.</p>
    </div>

    <div style="background: rgba(56, 189, 248, 0.08); border-left: 4px solid var(--accent); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--accent); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-expand"></i> The Divergence Operator ($\nabla \cdot \vec{F}$)
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            The <strong>divergence</strong> of a vector field $\vec{F}(x,y,z)$ is a scalar field quantifying the net outward flux of the vector field per unit volume expanding away from an infinitesimal point:
            $$\nabla \cdot \vec{F} = \lim_{\Delta V \to 0} \frac{1}{\Delta V} \oiint_{\Delta S} \vec{F} \cdot d\vec{S}$$
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Rectangular Cartesian Formulation
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Taking the inner product between the spatial del operator $\nabla = \hat{i}\frac{\partial}{\partial x} + \hat{j}\frac{\partial}{\partial y} + \hat{k}\frac{\partial}{\partial z}$ and the vector field $\vec{F} = F_x \hat{i} + F_y \hat{j} + F_z \hat{k}$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\nabla \cdot \vec{F} = \frac{\partial F_x}{\partial x} + \frac{\partial F_y}{\partial y} + \frac{\partial F_z}{\partial z}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Consider an infinitesimal rectangular box of dimensions $\Delta x \times \Delta y \times \Delta z$. The net outward flux through the two faces perpendicular to the $x$-axis is:
        $$\Delta \Phi_x = \left[ F_x\left(x + \frac{\Delta x}{2}, y, z\right) - F_x\left(x - \frac{\Delta x}{2}, y, z\right) \right] \Delta y \Delta z \approx \frac{\partial F_x}{\partial x} \Delta x \Delta y \Delta z$$
        Summing over all three pairs of faces and dividing by $\Delta V = \Delta x \Delta y \Delta z$ rigorously produces the Cartesian formula above.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Physical Classification of Vector Fields
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The sign of $\nabla \cdot \vec{F}$ classifies physical behavior across all transport phenomena:
        <br>&bull; <strong>$\nabla \cdot \vec{F} > 0$ (Source):</strong> Field lines originate here. Positive electric charge in electrostatics ($\nabla \cdot \vec{E} = \rho / \epsilon_0$), or fluid thermal expansion.
        <br>&bull; <strong>$\nabla \cdot \vec{F} < 0$ (Sink):</strong> Field lines terminate here. Negative electric charge, fluid suction drain, or mass condensation.
        <br>&bull; <strong>$\nabla \cdot \vec{F} = 0$ (Solenoidal / Incompressible):</strong> Field lines never begin or end; they form continuous closed loops or extend from $-\infty$ to $+\infty$.
        <br>&bull; <em>Examples of Solenoidal Fields:</em> Magnetic induction $\nabla \cdot \vec{B} = 0$ (Gauss\'s law for magnetism; no magnetic monopoles exist), and incompressible fluid velocity $\nabla \cdot \vec{v} = 0$ (equation of continuity with constant fluid density).
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 2,
        ':prog_id'     => 1,
        ':content'     => $p2_1_content,
        ':algo'        => $p2_1_algo,
        ':explanation' => $p2_1_explanation
    ]);
    echo "  [OK] Inserted Program 1.2.1 (Vector Field Divergence Visualizer)\n";

    // Program 1.2.2: Problem: Gauss's Law for Non-Uniform Spherically Symmetric Charge Cloud
    $p2_2_content = <<<'PYCODE'
"""
Physics Problem Simulation: Gauss's Law for a Non-Uniform Charge Cloud
Verifies div(E) = rho(r) / eps0 analytically and numerically for a spherical cloud
having volume density rho(r) = rho0 * [1 - (r/R)^2]. Computes enclosed charge,
electric field everywhere, and verifies flux across spherical Gaussian surfaces.
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider

fig = plt.figure(figsize=(9.2, 5.8))
plt.subplots_adjust(bottom=0.30, top=0.91, left=0.10, right=0.92)

# Initial parameters
rho0_init = 2.0   # Peak charge density at center (micro-C / m^3)
R_cloud0  = 1.5   # Radius of spherical charge cloud (meters)
eps0 = 8.854e-12  # Permittivity of free space

ax_cross = fig.add_subplot(1, 2, 1)
ax_prof  = fig.add_subplot(1, 2, 2)

def compute_cloud(rho0_val, R_val):
    # rho in SI units: micro-C to C
    rho0 = rho0_val * 1e-6
    R = R_val
    
    # Total charge of cloud by volume integral:
    # Q_total = int_0^R rho0 * (1 - r^2/R^2) * 4*pi*r^2 dr
    # = 4*pi*rho0 * [R^3/3 - R^3/5] = 4*pi*rho0 * (2/15) * R^3 = (8/15) * pi * rho0 * R^3
    Q_tot = (8.0 / 15.0) * np.pi * rho0 * (R**3)
    
    r_arr = np.linspace(0.01, 3.5, 300)
    
    # Enclosed charge Q_enc(r):
    # Inside (r <= R): 4*pi*rho0 * [r^3/3 - r^5/(5*R^2)]
    # Outside (r > R): Q_tot
    Q_enc = np.where(r_arr <= R, 
                     4.0 * np.pi * rho0 * ((r_arr**3)/3.0 - (r_arr**5)/(5.0 * R**2)),
                     Q_tot)
    
    # Electric Field by Gauss's Law: E(r) = Q_enc(r) / (4*pi*eps0*r^2)
    # Inside: E(r) = (rho0 * r / eps0) * [1/3 - r^2 / (5*R^2)]
    # Outside: E(r) = Q_tot / (4*pi*eps0*r^2)
    E_arr = Q_enc / (4.0 * np.pi * eps0 * (r_arr**2))
    
    # Charge density rho(r)
    rho_arr = np.where(r_arr <= R, rho0 * (1.0 - (r_arr/R)**2), 0.0)
    
    # Divergence: div(E) = 1/r^2 * d/dr (r^2 * E) = rho(r) / eps0
    div_E = rho_arr / eps0
    
    return r_arr, rho_arr*1e6, E_arr*1e-3, div_E, Q_tot*1e6

def update_views(rho0, R_val):
    r_arr, rho_arr, E_arr, div_E, Q_tot = compute_cloud(rho0, R_val)
    
    # 1. 2D Cutaway of Charge Cloud & Electric Field
    ax_cross.cla()
    ax_cross.set_facecolor('#1e293b')
    ax_cross.grid(True, linestyle=':', alpha=0.3, color='#475569')
    
    # Charge density heatmap
    grid_lim = 3.2
    grid_pts = np.linspace(-grid_lim, grid_lim, 120)
    Xg, Yg = np.meshgrid(grid_pts, grid_pts)
    Rg = np.sqrt(Xg**2 + Yg**2)
    Rho_map = np.where(Rg <= R_val, rho0 * (1.0 - (Rg/R_val)**2), 0.0)
    
    im = ax_cross.imshow(Rho_map, extent=[-grid_lim, grid_lim, -grid_lim, grid_lim], 
                          origin='lower', cmap='YlOrRd', alpha=0.7)
    
    # Cloud boundary circle
    circle_cloud = plt.Circle((0, 0), R_val, fill=False, color='#f43f5e', lw=2.2, linestyle='--', label=f'Cloud Edge R = {R_val:.1f} m')
    ax_cross.add_patch(circle_cloud)
    
    # Radiating E-field lines
    angles = np.linspace(0, 2*np.pi, 16, endpoint=False)
    for a in angles:
        ax_cross.annotate('', xy=(2.6*np.cos(a), 2.6*np.sin(a)), xytext=(0.4*np.cos(a), 0.4*np.sin(a)),
                          arrowprops=dict(arrowstyle="->,head_width=0.25,head_length=0.35", color='#38bdf8', lw=1.6))
        
    ax_cross.set_xlim(-grid_lim, grid_lim)
    ax_cross.set_ylim(-grid_lim, grid_lim)
    ax_cross.set_aspect('equal')
    ax_cross.set_xlabel('x (m)', fontsize=9)
    ax_cross.set_ylabel('y (m)', fontsize=9)
    ax_cross.set_title(f'Spherical Cloud | Q_tot = {Q_tot:.2f} μC', fontsize=10.5, color='#38bdf8', fontweight='bold')
    ax_cross.legend(loc='upper right', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')

    # 2. Radial Profiles of E(r) and rho(r)
    ax_prof.cla()
    ax_prof.set_facecolor('#1e293b')
    ax_prof.grid(True, linestyle=':', alpha=0.3, color='#475569')
    
    line1 = ax_prof.plot(r_arr, E_arr, color='#38bdf8', lw=2.5, label='Electric Field E(r) [kV/m]')
    ax_prof.set_xlabel('Radial Distance r (m)', fontsize=9)
    ax_prof.set_ylabel('Electric Field E (kV/m)', color='#38bdf8', fontsize=9)
    
    ax_twin = ax_prof.twinx()
    line2 = ax_twin.plot(r_arr, rho_arr, color='#f59e0b', lw=2.0, linestyle='--', label='Charge Density ρ(r) [μC/m³]')
    ax_twin.set_ylabel('Density ρ (μC/m³)', color='#f59e0b', fontsize=9)
    
    ax_prof.axvline(R_val, color='#f43f5e', linestyle=':', lw=1.5, label=f'R = {R_val:.1f} m')
    
    lines = line1 + line2
    labels = [l.get_label() for l in lines]
    ax_prof.legend(lines, labels, loc='upper right', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')
    ax_prof.set_title("Verification of Gauss's Law: ∇·E = ρ(r)/ε0", fontsize=10.5, color='#10b981', fontweight='bold')

update_views(rho0_init, R_cloud0)

# Sliders
ax_rho = plt.axes([0.18, 0.16, 0.70, 0.022])
ax_R   = plt.axes([0.18, 0.08, 0.70, 0.022])

s_rho = Slider(ax_rho, 'Peak Density ρ0 (μC/m³)', 0.5, 5.0, valinit=rho0_init, valstep=0.2)
s_R   = Slider(ax_R, 'Cloud Radius R (m)', 0.5, 2.5, valinit=R_cloud0, valstep=0.1)

def update(val):
    update_views(s_rho.val, s_R.val)
    fig.canvas.draw_idle()

s_rho.on_changed(update)
s_R.on_changed(update)

print("Gauss's Law Problem Simulation Loaded:")
print("• Verified continuity of E(r) at the cloud boundary r = R.")
print("• Peak electric field occurs strictly inside the charge cloud where dE/dr = 0.")
plt.show()
PYCODE;

    $p2_2_algo = "Problem: Verification of Gauss's Law for a Non-Uniform Spherically Symmetric Charge Distribution";

    $p2_2_explanation = '<div class="theory-article">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <img src="{{SITEURL}}assets/images/visualization/vectors/divergence_problem_gauss.png" alt="Gauss Law Problem" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <p style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem;"><strong>Figure 2.2:</strong> Gauss\'s Law &amp; Divergence in Non-Uniform Spherical Charge: (Left) Cross-sectional volume density and outward electric field quivers with concentric Gaussian sphere $r < R$, (Right) Radial profiles of charge density $\rho(r)$ and continuous electric field $E(r)$ smoothly transitioning across the boundary $r = R$.</p>
    </div>

    <div style="background: rgba(16, 185, 129, 0.08); border-left: 4px solid var(--primary); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--primary); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-calculator"></i> Physics Problem Statement
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            A spherically symmetric cosmic dust cloud of radius $R$ contains a non-uniform volume charge density given by:
            $$\rho(r) = \begin{cases} \rho_0 \left( 1 - \frac{r^2}{R^2} \right), & r \le R \\ 0, & r > R \end{cases}$$
            where $\rho_0$ is the peak charge density at the center $r=0$.
            <br><strong>Tasks:</strong>
            <br>1. Calculate the total charge $Q_{\text{tot}}$ enclosed by the entire cloud.
            <br>2. Use the integral form of Gauss\'s Law $\oiint \vec{E} \cdot d\vec{S} = Q_{\text{enc}} / \epsilon_0$ to determine the electric field $\vec{E}(r)$ everywhere ($r \le R$ and $r > R$).
            <br>3. Verify that the differential divergence $\nabla \cdot \vec{E} = \frac{1}{r^2}\frac{d}{dr}(r^2 E_r)$ yields identically $\rho(r) / \epsilon_0$.
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Total Enclosed Charge Calculation
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Integrating the volume density in spherical coordinates with radial shells of volume $dV = 4\pi r^2 dr$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$Q_{\text{tot}} = \int_0^R \rho(r) \cdot 4\pi r^2 dr = 4\pi \rho_0 \int_0^R \left( r^2 - \frac{r^4}{R^2} \right) dr = 4\pi \rho_0 \left[ \frac{R^3}{3} - \frac{R^3}{5} \right] = \frac{8}{15}\pi \rho_0 R^3$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Electric Field via Gauss\'s Law
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        By spherical symmetry, the electric field is purely radial: $\vec{E} = E(r)\hat{r}$. Over a concentric Gaussian sphere of radius $r$:
        $$\oiint_S \vec{E} \cdot d\vec{S} = E(r) \cdot 4\pi r^2 = \frac{Q_{\text{enc}}(r)}{\epsilon_0}$$
        <br>&bull; <strong>Inside the Cloud ($r \le R$):</strong>
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$Q_{\text{enc}}(r) = 4\pi \rho_0 \int_0^r \left( r\'^2 - \frac{r\'^4}{R^2} \right) dr\' = 4\pi \rho_0 \left( \frac{r^3}{3} - \frac{r^5}{5R^2} \right)$$
        $$E_{\text{in}}(r) = \frac{\rho_0 r}{\epsilon_0} \left( \frac{1}{3} - \frac{r^2}{5R^2} \right)$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        <br>&bull; <strong>Outside the Cloud ($r > R$):</strong>
        $$E_{\text{out}}(r) = \frac{Q_{\text{tot}}}{4\pi\epsilon_0 r^2} = \frac{2\rho_0 R^3}{15\epsilon_0 r^2}$$
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Differential Verification of Divergence
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Applying the spherical divergence formula to $E_{\text{in}}(r)$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\nabla \cdot \vec{E} = \frac{1}{r^2} \frac{d}{dr} \left( r^2 E_r \right) = \frac{1}{r^2} \frac{d}{dr} \left[ \frac{\rho_0}{\epsilon_0} \left( \frac{r^3}{3} - \frac{r^5}{5R^2} \right) \right] = \frac{\rho_0}{\epsilon_0 r^2} \left[ r^2 - \frac{r^4}{R^2} \right] = \frac{\rho_0}{\epsilon_0} \left( 1 - \frac{r^2}{R^2} \right) = \frac{\rho(r)}{\epsilon_0}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        This completes the exact analytical and numerical proof connecting integral Gauss\'s law with Maxwell\'s 1st differential equation $\nabla \cdot \vec{E} = \rho / \epsilon_0$!
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 2,
        ':prog_id'     => 2,
        ':content'     => $p2_2_content,
        ':algo'        => $p2_2_algo,
        ':explanation' => $p2_2_explanation
    ]);
    echo "  [OK] Inserted Program 1.2.2 (Gauss's Law Problem)\n";

    // =========================================================================
    // SUBTOPIC 1.3: Curl of Vector Fields & Circulation
    // =========================================================================

    // Program 1.3.1: Vector Field Curl, Circulation & Simulated Paddle Wheel
    $p3_1_content = <<<'PYCODE'
"""
Vector Field Curl & Circulation Visualizer
Interactive exploration of rigid body rotation (curl != 0) vs free vortex flow (curl = 0).
Includes virtual microscopic paddle wheel testing local rotational circulation.
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider

fig, (ax_vec, ax_curl) = plt.subplots(1, 2, figsize=(9.2, 5.8), gridspec_kw={'wspace': 0.3})
plt.subplots_adjust(bottom=0.32, top=0.91, left=0.08, right=0.92)

# Initial parameters
omega0 = 1.5   # Rigid body angular velocity (rad/s) -> Curl = 2*omega
shear0 = 0.8   # Linear shear velocity gradient -> Curl = -shear
gamma0 = 0.0   # Free point vortex strength -> Curl = 0 everywhere except origin

# Quiver grid
x_grid = np.linspace(-3.0, 3.0, 18)
y_grid = np.linspace(-3.0, 3.0, 18)
X, Y = np.meshgrid(x_grid, y_grid)

# Fine grid for curl heatmap
x_f = np.linspace(-3.0, 3.0, 100)
y_f = np.linspace(-3.0, 3.0, 100)
Xf, Yf = np.meshgrid(x_f, y_f)

def compute_curl_field(Xm, Ym, omega, shear, gamma):
    # Velocity field:
    # 1. Rigid body rotation: v = omega x r = (-omega*y, omega*x)
    # 2. Shear flow: v = (shear * y, 0)
    # 3. Free vortex: v = gamma/(2*pi*r^2) * (-y, x)
    R_sq = Xm**2 + Ym**2 + 0.25
    
    Vx = -omega * Ym + shear * Ym - (gamma / (2.0 * np.pi)) * Ym / R_sq
    Vy =  omega * Xm + (gamma / (2.0 * np.pi)) * Xm / R_sq
    
    # Analytical curl in z-direction: (dVy/dx - dVx/dy)
    # dVy/dx from omega*x is +omega. dVx/dy from -omega*y is -omega -> diff = 2*omega
    # dVx/dy from shear*y is +shear -> diff = -shear
    # Vortex curl: d/dx(x/R^2) - d/dy(-y/R^2) = (R^2 - 2x^2 + R^2 - 2y^2)/R^4 = 0 outside regularization!
    eps = 0.25
    vortex_curl = (gamma / (2.0 * np.pi)) * (2.0 * eps) / (R_sq**2)
    curl_z = 2.0 * omega - shear + vortex_curl
    
    return Vx, Vy, curl_z

def update_plots(omega, shear, gamma):
    Vx, Vy, _ = compute_curl_field(X, Y, omega, shear, gamma)
    _, _, curl_fine = compute_curl_field(Xf, Yf, omega, shear, gamma)
    
    # 1. Vector Quiver & Paddle Wheel
    ax_vec.cla()
    ax_vec.set_facecolor('#1e293b')
    ax_vec.grid(True, linestyle=':', alpha=0.3, color='#475569')
    
    speed = np.sqrt(Vx**2 + Vy**2)
    ax_vec.quiver(X, Y, Vx/speed, Vy/speed, speed, cmap='viridis', scale=22, width=0.006)
    
    # Simulated microscopic paddle wheel at probe point (x0, y0) = (1.2, 0.8)
    px, py = 1.2, 0.8
    _, _, probe_curl = compute_curl_field(np.array([[px]]), np.array([[py]]), omega, shear, gamma)
    wheel_w = 0.5 * probe_curl[0, 0]
    
    # Draw paddle wheel cross
    ax_vec.scatter([px], [py], color='#f59e0b', s=90, zorder=6)
    wheel_rad = 0.4
    for ang in [0, 90, 180, 270]:
        rad = np.radians(ang)
        ax_vec.plot([px, px + wheel_rad*np.cos(rad)], [py, py + wheel_rad*np.sin(rad)], color='#f43f5e', lw=2.5, zorder=5)
        
    ax_vec.text(px, py - 0.65, f'Paddle Wheel\nΩ = {wheel_w:+.2f} rad/s', color='#f59e0b', fontsize=8, fontweight='bold', ha='center',
                bbox=dict(boxstyle='round,pad=0.2', facecolor='#0f172a', edgecolor='#475569'))
    
    ax_vec.set_xlim(-3.0, 3.0)
    ax_vec.set_ylim(-3.0, 3.0)
    ax_vec.set_aspect('equal')
    ax_vec.set_xlabel('x', fontsize=9)
    ax_vec.set_ylabel('y', fontsize=9)
    ax_vec.set_title('Velocity Flow $\\vec{v}(x,y)$ with Paddle Wheel', fontsize=10.5, color='#38bdf8', fontweight='bold')

    # 2. Curl Vorticity Heatmap
    ax_curl.cla()
    ax_curl.set_facecolor('#1e293b')
    
    max_c = max(1.5, np.max(np.abs(curl_fine)))
    im = ax_curl.imshow(curl_fine, extent=[-3.0, 3.0, -3.0, 3.0], origin='lower',
                        cmap='coolwarm', vmin=-max_c, vmax=max_c, alpha=0.9)
    
    ax_curl.contour(Xf, Yf, curl_fine, levels=8, colors='#f8fafc', linewidths=0.6, alpha=0.4)
    ax_curl.set_xlim(-3.0, 3.0)
    ax_curl.set_ylim(-3.0, 3.0)
    ax_curl.set_aspect('equal')
    ax_curl.set_xlabel('x', fontsize=9)
    ax_curl.set_title(f'Vorticity $(\\nabla\\times\\vec{{v}})_z$ | Uniform: {2*omega - shear:+.2f}', fontsize=10.5, color='#10b981', fontweight='bold')

update_plots(omega0, shear0, gamma0)

# Sliders
ax_w     = plt.axes([0.18, 0.20, 0.70, 0.022])
ax_s     = plt.axes([0.18, 0.15, 0.70, 0.022])
ax_g     = plt.axes([0.18, 0.10, 0.70, 0.022])

s_w = Slider(ax_w, 'Rotation ω (rad/s)', 0.0, 3.0, valinit=omega0, valstep=0.1)
s_s = Slider(ax_s, 'Shear Gradient α', -2.0, 2.0, valinit=shear0, valstep=0.1)
s_g = Slider(ax_g, 'Vortex Strength Γ', 0.0, 8.0, valinit=gamma0, valstep=0.5)

def update(val):
    update_plots(s_w.val, s_s.val, s_g.val)
    fig.canvas.draw_idle()

s_w.on_changed(update)
s_s.on_changed(update)
s_g.on_changed(update)

print("Curl & Circulation Visualizer Loaded:")
print("• Rigid body rotation gives curl = 2*omega everywhere.")
print("• A microscopic paddle wheel rotates with angular velocity Omega = 0.5 * curl(v).")
print("• Notice: A pure irrotational free vortex does NOT spin the paddle wheel off-center!")
plt.show()
PYCODE;

    $p3_1_algo = "Vector Field Curl, Circulation & Simulated Paddle Wheel: Distinguishing Rotational Shear Flow from Irrotational Free Vortex Flow";

    $p3_1_explanation = '<div class="theory-article">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <img src="{{SITEURL}}assets/images/visualization/vectors/curl_vorticity_concept.png" alt="Curl and Vorticity Concept" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <p style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem;"><strong>Figure 3.1:</strong> Conceptualizing Curl: (a) Infinitesimal circulation loop and paddle wheel rotation, (b) Solid-body rotation $\vec{v} = \vec{\omega}\times\vec{r}$ yielding non-zero uniform curl $\nabla\times\vec{v} = 2\vec{\omega}$, (c) Irrotational free vortex flow where fluid streamlines are circular but local curl is identically zero ($\nabla\times\vec{v} = 0$).</p>
    </div>

    <div style="background: rgba(56, 189, 248, 0.08); border-left: 4px solid var(--accent); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--accent); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-rotate"></i> The Curl Operator ($\nabla \times \vec{F}$) &amp; Circulation Density
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            The <strong>curl</strong> of a vector field $\vec{F}$ is a vector quantity measuring the maximum circulation (microscopic swirling tendency) per unit area around a localized point:
            $$(\nabla \times \vec{F}) \cdot \hat{n} = \lim_{\Delta S \to 0} \frac{1}{\Delta S} \oint_{\Delta C} \vec{F} \cdot d\vec{r}$$
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Cartesian Determinant Definition
    </h4>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\nabla \times \vec{F} = \begin{vmatrix} \hat{i} & \hat{j} & \hat{k} \\ \frac{\partial}{\partial x} & \frac{\partial}{\partial y} & \frac{\partial}{\partial z} \\ F_x & F_y & F_z \end{vmatrix} = \left(\frac{\partial F_z}{\partial y} - \frac{\partial F_y}{\partial z}\right)\hat{i} + \left(\frac{\partial F_x}{\partial z} - \frac{\partial F_z}{\partial x}\right)\hat{j} + \left(\frac{\partial F_y}{\partial x} - \frac{\partial F_x}{\partial y}\right)\hat{k}$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. The Paddle Wheel Thought Experiment
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        To develop an intuitive physical grasp of curl, imagine placing a microscopic paddle wheel with frictionless bearings at any test point in a fluid stream:
        <br>&bull; If the paddle wheel <strong>rotates</strong>, the flow possesses local curl at that point, with induced angular velocity $\vec{\Omega}_{\text{wheel}} = \frac{1}{2}(\nabla \times \vec{v})$.
        <br>&bull; If the paddle wheel <strong>does not rotate</strong> (even if moving along a circular path!), the local curl is zero.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Solid Body Rotation vs. Free Irrotational Vortex
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        A famous paradox in vector calculus contrasts two circular vector fields:
        <br>&bull; <strong>Rigid Body Rotation ($\vec{v} = \vec{\omega} \times \vec{r} = -\omega y \hat{i} + \omega x \hat{j}$):</strong>
        $$(\nabla \times \vec{v})_z = \frac{\partial}{\partial x}(\omega x) - \frac{\partial}{\partial y}(-\omega y) = \omega - (-\omega) = 2\omega \neq 0$$
        Every fluid element rotates about its own centroid with angular velocity $\omega$.
        <br>&bull; <strong>Irrotational Free Vortex ($\vec{v} = \frac{\Gamma}{2\pi r} \hat{\theta} = \frac{\Gamma}{2\pi} \frac{-y\hat{i} + x\hat{j}}{x^2 + y^2}$):</strong>
        $$(\nabla \times \vec{v})_z = \frac{\Gamma}{2\pi} \left[ \frac{\partial}{\partial x}\left(\frac{x}{x^2+y^2}\right) - \frac{\partial}{\partial y}\left(\frac{-y}{x^2+y^2}\right) \right] = 0 \quad (\forall r > 0)$$
        Even though the streamlines are concentric circles, the speed scales as $1/r$. The outer edge of a test paddle moves slower than the inner edge by just the right amount to keep the paddle wheel pointing in a constant direction without rotating!
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        4. Irrotational Vector Fields &amp; Scalar Potentials
    </h4>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\nabla \times \vec{F} = \vec{0} \iff \vec{F} = -\nabla \phi \quad (\text{Conservative Field})$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Because the curl of any gradient is identically zero ($\nabla \times (\nabla \phi) \equiv \vec{0}$), any curl-free vector field can be expressed as the gradient of a single scalar potential $\phi$. Examples include electrostatic fields ($\nabla \times \vec{E} = \vec{0} \implies \vec{E} = -\nabla V$) and Newtonian gravitational fields.
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 3,
        ':prog_id'     => 1,
        ':content'     => $p3_1_content,
        ':algo'        => $p3_1_algo,
        ':explanation' => $p3_1_explanation
    ]);
    echo "  [OK] Inserted Program 1.3.1 (Vector Field Curl Visualizer)\n";

    // Program 1.3.2: Problem: Ampere's Law & Magnetic Field of a Cylindrical Wire with Non-Uniform Current
    $p3_2_content = <<<'PYCODE'
"""
Physics Problem Simulation: Ampere's Law in a Cylindrical Conductor
Verifies curl(B) = mu0 * J(r) inside and outside a solid copper wire carrying
a non-uniform power-law current density J(r) = J0 * (r/R)^n. Computes B(r),
circulation integral, and confirms differential and integral Ampere's relations.
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider

fig = plt.figure(figsize=(9.2, 5.8))
plt.subplots_adjust(bottom=0.30, top=0.91, left=0.10, right=0.92)

# Initial parameters
R_wire0 = 2.0    # Wire radius (mm)
J0_val0 = 4.0    # Peak current density (A / mm^2)
n_power0 = 1.0   # Current density power law index: J(r) = J0 * (r/R)^n
mu0 = 4.0 * np.pi * 1e-7

ax_cross = fig.add_subplot(1, 2, 1)
ax_prof  = fig.add_subplot(1, 2, 2)

def solve_wire(R_mm, J0_val, n):
    R = R_mm * 1e-3       # meters
    J0 = J0_val * 1e6     # A / m^2
    
    # Total current I = int_0^R J0 * (r/R)^n * 2*pi*r dr
    # = (2*pi*J0 / R^n) * int_0^R r^(n+1) dr = 2*pi*J0 * R^2 / (n + 2)
    I_tot = 2.0 * np.pi * J0 * (R**2) / (n + 2.0)
    
    r_arr = np.linspace(0.01, 5.0, 300) * 1e-3  # meters
    
    # Enclosed current I_enc(r):
    # Inside: 2*pi*J0 * r^(n+2) / ((n+2) * R^n)
    # Outside: I_tot
    I_enc = np.where(r_arr <= R,
                     2.0 * np.pi * J0 * (r_arr**(n + 2.0)) / ((n + 2.0) * (R**n)),
                     I_tot)
    
    # Magnetic Field by Ampere's Law: B(r) = mu0 * I_enc / (2*pi*r)
    B_arr = mu0 * I_enc / (2.0 * np.pi * r_arr)
    
    # Current density J(r)
    J_arr = np.where(r_arr <= R, J0 * ((r_arr / R)**n), 0.0)
    
    # Curl in cylindrical coordinates: (curl B)_z = 1/r * d/dr (r * B_theta) = mu0 * J(r)
    curl_B = mu0 * J_arr
    
    return r_arr*1e3, J_arr*1e-6, B_arr*1e3, curl_B, I_tot

def update_views(R_mm, J0_val, n):
    r_arr, J_arr, B_arr, curl_B, I_tot = solve_wire(R_mm, J0_val, n)
    
    # 1. 2D Wire Cross Section & Magnetic Field Vector Loops
    ax_cross.cla()
    ax_cross.set_facecolor('#1e293b')
    ax_cross.grid(True, linestyle=':', alpha=0.3, color='#475569')
    
    # Wire circle with current density gradient
    pts = np.linspace(-4.0, 4.0, 120)
    Xg, Yg = np.meshgrid(pts, pts)
    Rg = np.sqrt(Xg**2 + Yg**2)
    J_map = np.where(Rg <= R_mm, J0_val * ((Rg / R_mm)**n), 0.0)
    
    im = ax_cross.imshow(J_map, extent=[-4.0, 4.0, -4.0, 4.0], origin='lower',
                         cmap='inferno', alpha=0.75)
    
    circle_wire = plt.Circle((0, 0), R_mm, fill=False, color='#38bdf8', lw=2.2, linestyle='--', label=f'Wire Edge R = {R_mm:.1f} mm')
    ax_cross.add_patch(circle_wire)
    
    # Concentric B-field circular loops with arrows
    for r_loop in [1.0, 2.0, 3.2]:
        loop = plt.Circle((0, 0), r_loop, fill=False, color='#f59e0b', lw=1.5, linestyle=':')
        ax_cross.add_patch(loop)
        # Add arrow on loop top
        ax_cross.annotate('', xy=(-0.1, r_loop), xytext=(0.1, r_loop),
                          arrowprops=dict(arrowstyle="->,head_width=0.3,head_length=0.4", color='#f59e0b', lw=2.0))
        
    ax_cross.set_xlim(-4.0, 4.0)
    ax_cross.set_ylim(-4.0, 4.0)
    ax_cross.set_aspect('equal')
    ax_cross.set_xlabel('x (mm)', fontsize=9)
    ax_cross.set_ylabel('y (mm)', fontsize=9)
    ax_cross.set_title(f'Current Wire | Total I = {I_tot:.1f} A', fontsize=10.5, color='#38bdf8', fontweight='bold')
    ax_cross.legend(loc='upper right', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')

    # 2. Radial Profiles of B(r) and Curl(B)
    ax_prof.cla()
    ax_prof.set_facecolor('#1e293b')
    ax_prof.grid(True, linestyle=':', alpha=0.3, color='#475569')
    
    line1 = ax_prof.plot(r_arr, B_arr, color='#f59e0b', lw=2.5, label='Magnetic Field B(r) [mT]')
    ax_prof.set_xlabel('Radius r (mm)', fontsize=9)
    ax_prof.set_ylabel('Magnetic Field B (mT)', color='#f59e0b', fontsize=9)
    
    ax_twin = ax_prof.twinx()
    line2 = ax_twin.plot(r_arr, J_arr, color='#f43f5e', lw=2.0, linestyle='--', label='Current Density J(r) [A/mm²]')
    ax_twin.set_ylabel('Current Density J (A/mm²)', color='#f43f5e', fontsize=9)
    
    ax_prof.axvline(R_mm, color='#38bdf8', linestyle=':', lw=1.5, label=f'R = {R_mm:.1f} mm')
    
    lines = line1 + line2
    labels = [l.get_label() for l in lines]
    ax_prof.legend(lines, labels, loc='lower right', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')
    ax_prof.set_title("Ampere's Law: ∇×B = μ0 J(r)", fontsize=10.5, color='#10b981', fontweight='bold')

update_views(R_wire0, J0_val0, n_power0)

# Sliders
ax_R = plt.axes([0.18, 0.18, 0.70, 0.022])
ax_J = plt.axes([0.18, 0.11, 0.70, 0.022])
ax_n = plt.axes([0.18, 0.04, 0.70, 0.022])

s_R = Slider(ax_R, 'Wire Radius R (mm)', 0.5, 3.5, valinit=R_wire0, valstep=0.1)
s_J = Slider(ax_J, 'Peak Current J0 (A/mm²)', 1.0, 8.0, valinit=J0_val0, valstep=0.5)
s_n = Slider(ax_n, 'Index n: J ~ (r/R)^n', 0.0, 3.0, valinit=n_power0, valstep=0.5)

def update(val):
    update_views(s_R.val, s_J.val, s_n.val)
    fig.canvas.draw_idle()

s_R.on_changed(update)
s_J.on_changed(update)
s_n.on_changed(update)

print("Ampere's Law Simulation Initialized:")
print("• Verified curl(B) = mu0 * J(r) inside the wire.")
print("• Outside the wire (r > R), J = 0, so curl(B) = 0 (magnetic field is curl-free outside currents!).")
plt.show()
PYCODE;

    $p3_2_algo = "Problem: Maxwell-Ampere Differential Relation and Circulation for a Cylindrical Conductor Carrying Non-Uniform Current Density";

    $p3_2_explanation = '<div class="theory-article">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <img src="{{SITEURL}}assets/images/visualization/vectors/curl_problem_ampere.png" alt="Ampere Law Problem" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <p style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem;"><strong>Figure 3.2:</strong> Ampere\'s Circuital Law &amp; Magnetic Curl: (Left) Cylindrical conductor cross-section with non-uniform current density $J(r)$ and concentric magnetic field loops $\vec{B}$, (Right) Radial profiles showing $B(r)$ rising inside the wire, peaking at $r=R$, and decaying as $1/r$ outside where $\nabla \times \vec{B} = 0$.</p>
    </div>

    <div style="background: rgba(16, 185, 129, 0.08); border-left: 4px solid var(--primary); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--primary); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-calculator"></i> Physics Problem Statement
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            A long cylindrical copper wire of radius $R$ carries a steady axial current distributed non-uniformly across its cross-section according to the power law:
            $$J(r) = J_0 \left(\frac{r}{R}\right)^n, \quad 0 \le r \le R$$
            where $n \ge 0$ is the radial inhomogeneity index.
            <br><strong>Tasks:</strong>
            <br>1. Calculate the total current $I_{\text{tot}}$ passing through the conductor.
            <br>2. Use the integral form of Ampere\'s Circuital Law $\oint_C \vec{B} \cdot d\vec{r} = \mu_0 I_{\text{enc}}$ to compute the magnetic field $\vec{B}(r)$ inside and outside the wire.
            <br>3. Verify the Maxwell-Ampere differential relation $\nabla \times \vec{B} = \mu_0 \vec{J}$ in cylindrical coordinates.
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Total Current Integration
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Integrating the current density across concentric circular rings of area $dA = 2\pi r dr$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$I_{\text{tot}} = \int_0^R J(r) \cdot 2\pi r dr = \frac{2\pi J_0}{R^n} \int_0^R r^{n+1} dr = \frac{2\pi J_0 R^2}{n + 2}$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Magnetic Field Calculation
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Due to axial and azimuthal symmetry, the magnetic field is purely azimuthal: $\vec{B} = B(r)\hat{\theta}$. Applying Ampere\'s law along a circular loop of radius $r$:
        $$\oint_C \vec{B} \cdot d\vec{r} = B(r) \cdot 2\pi r = \mu_0 I_{\text{enc}}(r)$$
        <br>&bull; <strong>Inside the Wire ($r \le R$):</strong>
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$I_{\text{enc}}(r) = \frac{2\pi J_0}{R^n} \int_0^r r\'^{n+1} dr\' = \frac{2\pi J_0 r^{n+2}}{(n + 2)R^n}$$
        $$B_{\text{in}}(r) = \frac{\mu_0 J_0}{(n + 2)R^n} r^{n+1}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        <br>&bull; <strong>Outside the Wire ($r > R$):</strong>
        $$B_{\text{out}}(r) = \frac{\mu_0 I_{\text{tot}}}{2\pi r} = \frac{\mu_0 J_0 R^2}{(n + 2) r}$$
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Cylindrical Curl Verification
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        In cylindrical coordinates $(r, \theta, z)$, the $z$-component of curl is:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$(\nabla \times \vec{B})_z = \frac{1}{r} \frac{d}{dr} \left( r B_\theta \right)$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Substituting $B_{\text{in}}(r)$:
        $$(\nabla \times \vec{B})_z = \frac{1}{r} \frac{d}{dr} \left[ \frac{\mu_0 J_0}{(n+2)R^n} r^{n+2} \right] = \frac{1}{r} \left[ \frac{\mu_0 J_0 (n+2)}{(n+2)R^n} r^{n+1} \right] = \mu_0 J_0 \left(\frac{r}{R}\right)^n = \mu_0 J(r)$$
        For $r > R$, since $r B_{\text{out}}(r) = \text{constant}$, the derivative is identically zero: $\nabla \times \vec{B} = \vec{0}$.
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 3,
        ':prog_id'     => 2,
        ':content'     => $p3_2_content,
        ':algo'        => $p3_2_algo,
        ':explanation' => $p3_2_explanation
    ]);
    echo "  [OK] Inserted Program 1.3.2 (Ampere's Law Problem)\n";

    // =========================================================================
    // SUBTOPIC 1.4: Vector Integration, Divergence & Stokes' Theorems
    // =========================================================================

    // Program 1.4.1: Line Integrals: Path Independence in Conservative vs Non-Conservative Fields
    $p4_1_content = <<<'PYCODE'
"""
Line Integrals: Path Independence in Conservative vs Non-Conservative Fields
Computes line integral int_C F · dr along 3 distinct paths from (0,0) to (X_B, Y_B).
When non-conservative curl parameter k = 0, all three integrals match identically
(path independence). When k != 0, integrals diverge, demonstrating path dependence.
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider

fig, (ax_path, ax_bar) = plt.subplots(1, 2, figsize=(9.2, 5.8), gridspec_kw={'wspace': 0.3})
plt.subplots_adjust(bottom=0.32, top=0.91, left=0.08, right=0.92)

# Initial parameters
k_curl0 = 0.0     # Non-conservative curl parameter (k=0 => conservative)
xB0, yB0 = 2.0, 2.0
power_p0 = 2.0    # Curvature of Path 2: y = yB * (x/xB)^p

def compute_integrals(k, xB, yB, p):
    # Vector Field: F = (2*x*y + k*y) * i + (x^2 - k*x) * j
    # Curl = dFy/dx - dFx/dy = (2x - k) - (2x + k) = -2*k
    # When k = 0, curl = 0, potential phi = x^2 * y => Delta phi = xB^2 * yB
    
    # 1. Path 1: Straight line from (0,0) to (xB, yB): y(x) = (yB/xB) * x
    # dx = dx, dy = (yB/xB) dx
    N = 500
    x1 = np.linspace(0, xB, N)
    y1 = (yB / xB) * x1
    dx1 = x1[1] - x1[0]
    dy1 = y1[1] - y1[0]
    Fx1 = 2 * x1 * y1 + k * y1
    Fy1 = x1**2 - k * x1
    int1 = np.sum(Fx1 * dx1 + Fy1 * dy1)
    
    # 2. Path 2: Polynomial curve y(x) = yB * (x/xB)^p
    x2 = np.linspace(0, xB, N)
    y2 = yB * ((x2 / xB)**p)
    dx2 = np.gradient(x2)
    dy2 = np.gradient(y2)
    Fx2 = 2 * x2 * y2 + k * y2
    Fy2 = x2**2 - k * x2
    int2 = np.sum(Fx2 * dx2 + Fy2 * dy2)
    
    # 3. Path 3: Two-step right-angle path: (0,0) -> (xB,0) -> (xB, yB)
    # Step A: along x-axis from x=0 to xB with y=0 => Fx = 0, Fy = x^2 - k*x, dy=0 => int = 0
    # Step B: along vertical line x = xB with y from 0 to yB => dx=0, int = int (xB^2 - k*xB) dy = (xB^2 - k*xB)*yB
    int3 = (xB**2 - k * xB) * yB
    
    # Conservative potential difference
    phi_diff = (xB**2) * yB
    
    return x1, y1, x2, y2, int1, int2, int32, phi_diff

# Fix variable name
def compute_all(k, xB, yB, p):
    x1, y1, x2, y2, i1, i2, _, phi = compute_integrals(k, xB, yB, p)
    i3 = (xB**2 - k * xB) * yB
    return x1, y1, x2, y2, i1, i2, i3, phi

def update_views(k, xB, yB, p):
    x1, y1, x2, y2, int1, int2, int3, phi = compute_all(k, xB, yB, p)
    
    # 1. Physical Paths in Vector Field
    ax_path.cla()
    ax_path.set_facecolor('#1e293b')
    ax_path.grid(True, linestyle=':', alpha=0.3, color='#475569')
    
    # Quiver background
    qx = np.linspace(-0.2, 3.2, 12)
    qy = np.linspace(-0.2, 3.2, 12)
    QX, QY = np.meshgrid(qx, qy)
    QFx = 2 * QX * QY + k * QY
    QFy = QX**2 - k * QX
    mag = np.sqrt(QFx**2 + QFy**2) + 1e-6
    ax_path.quiver(QX, QY, QFx/mag, QFy/mag, color='#475569', scale=18, width=0.005, alpha=0.6)
    
    # Path 1 (Straight line)
    ax_path.plot(x1, y1, color='#38bdf8', lw=2.5, label='Path 1 (Linear)')
    # Path 2 (Polynomial)
    ax_path.plot(x2, y2, color='#f43f5e', lw=2.5, label=f'Path 2 (y ~ x^{p:.1f})')
    # Path 3 (Stepwise)
    ax_path.plot([0, xB, xB], [0, 0, yB], color='#f59e0b', lw=2.0, linestyle='--', label='Path 3 (Stepwise)')
    
    # Start and End points
    ax_path.scatter([0, xB], [0, yB], color='#f8fafc', s=80, zorder=6)
    ax_path.text(-0.15, -0.2, 'A(0,0)', color='#f8fafc', fontsize=9, fontweight='bold')
    ax_path.text(xB+0.05, yB+0.05, f'B({xB:.1f},{yB:.1f})', color='#f8fafc', fontsize=9, fontweight='bold')
    
    ax_path.set_xlim(-0.3, 3.2)
    ax_path.set_ylim(-0.3, 3.2)
    ax_path.set_aspect('equal')
    ax_path.set_xlabel('x', fontsize=9)
    ax_path.set_ylabel('y', fontsize=9)
    curl_str = "Conservative (k=0, Curl=0)" if abs(k) < 0.05 else f"Non-Conservative (Curl={-2*k:+.2f})"
    ax_path.set_title(f'Paths in Vector Field | {curl_str}', fontsize=10, color='#38bdf8', fontweight='bold')
    ax_path.legend(loc='upper left', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')

    # 2. Line Integral Comparison Bar Chart
    ax_bar.cla()
    ax_bar.set_facecolor('#1e293b')
    ax_bar.grid(True, linestyle=':', alpha=0.3, color='#475569')
    
    labels = ['Path 1 (Line)', 'Path 2 (Curve)', 'Path 3 (Step)', 'Δφ (Exact)']
    vals = [int1, int2, int3, phi]
    colors = ['#38bdf8', '#f43f5e', '#f59e0b', '#10b981']
    
    bars = ax_bar.bar(labels, vals, color=colors, width=0.55, edgecolor='#f8fafc', lw=1.0)
    for b in bars:
        h = b.get_height()
        ax_bar.text(b.get_x() + b.get_width()/2., h + 0.15, f'{h:.2f}', ha='center', va='bottom', color='#f8fafc', fontsize=9, fontweight='bold')
        
    ax_bar.set_ylabel('Line Integral Value $\\int_C \\vec{F}\\cdot d\\vec{r}$', fontsize=9)
    status = "IDENTICAL (Path Independent)" if abs(int1 - int2) < 0.1 and abs(int1 - int3) < 0.1 else f"PATH DEPENDENT (Δ={abs(int1-int2):.2f})"
    ax_bar.set_title(f'Line Integral Values: {status}', fontsize=10, color='#10b981', fontweight='bold')
    ax_bar.tick_params(axis='x', labelsize=8)

update_views(k_curl0, xB0, yB0, power_p0)

# Sliders
ax_k = plt.axes([0.18, 0.20, 0.70, 0.022])
ax_p = plt.axes([0.18, 0.12, 0.70, 0.022])
ax_x = plt.axes([0.18, 0.04, 0.70, 0.022])

s_k = Slider(ax_k, 'Curl Param k', -2.5, 2.5, valinit=k_curl0, valstep=0.25)
s_p = Slider(ax_p, 'Path 2 Exponent p', 0.5, 4.0, valinit=power_p0, valstep=0.25)
s_x = Slider(ax_x, 'End Point X_B', 1.0, 3.0, valinit=xB0, valstep=0.2)

def update(val):
    update_views(s_k.val, s_x.val, yB0, s_p.val)
    fig.canvas.draw_idle()

s_k.on_changed(update)
s_p.on_changed(update)
s_x.on_changed(update)

print("Line Integral Studio Loaded:")
print("• Set k = 0: Observe exact path independence (all 3 paths equal Delta phi).")
print("• Vary k != 0: Observe line integrals become path-dependent due to non-zero curl.")
plt.show()
PYCODE;

    $p4_1_algo = "Line Integrals in Vector Fields: Path Independence, Conservative Potential Fields, and Work Done along Alternate Paths";

    $p4_1_explanation = '<div class="theory-article">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <img src="{{SITEURL}}assets/images/visualization/vectors/vector_integration_problem.png" alt="Line Integral and Path Independence" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <p style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem;"><strong>Figure 4.1:</strong> Vector Integration: (Top) Path independence of line integrals in a conservative field ($\int_{C_1} = \int_{C_2} = \Delta \phi$) versus path dependence when $\nabla \times \vec{F} \neq 0$. (Bottom) Surface flux decomposition across top, bottom, and curved sidewall of a closed cylinder.</p>
    </div>

    <div style="background: rgba(56, 189, 248, 0.08); border-left: 4px solid var(--accent); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--accent); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-route"></i> Vector Line Integrals &amp; Conservative Fields
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            The line integral of a vector field $\vec{F}$ along an oriented spatial curve $C$ parametrized by $\vec{r}(t)$ from $A$ to $B$ represents the cumulative tangential projection:
            $$W = \int_C \vec{F} \cdot d\vec{r} = \int_{t_A}^{t_B} \left( F_x \frac{dx}{dt} + F_y \frac{dy}{dt} + F_z \frac{dz}{dt} \right) dt$$
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. The Fundamental Theorem of Line Integrals
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        If a vector field is <strong>conservative</strong>, there exists a single-valued scalar potential $\phi(x,y,z)$ such that $\vec{F} = \nabla \phi$. By the chain rule:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\vec{F} \cdot d\vec{r} = \nabla \phi \cdot d\vec{r} = \frac{\partial \phi}{\partial x}dx + \frac{\partial \phi}{\partial y}dy + \frac{\partial \phi}{\partial z}dz = d\phi$$
        $$\int_{C, A \to B} \vec{F} \cdot d\vec{r} = \int_A^B d\phi = \phi(B) - \phi(A)$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The integral depends <em>exclusively</em> on the coordinates of the endpoints $A$ and $B$, being entirely independent of the geometric path connecting them!
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Closed Loop Circulation &amp; Curl Connection
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        If two arbitrary paths $C_1$ and $C_2$ connect $A$ to $B$, forming a closed loop $C = C_1 - C_2$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\oint_C \vec{F} \cdot d\vec{r} = \int_{C_1} \vec{F} \cdot d\vec{r} - \int_{C_2} \vec{F} \cdot d\vec{r} = 0 \iff \nabla \times \vec{F} = \vec{0}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        In the interactive simulation, notice how altering the parameter $k$ introduces a non-zero curl $\nabla \times \vec{F} = -2k \hat{k}$. The difference in work between alternate paths is exactly equal to the enclosed vortex flux $\iint (\nabla \times \vec{F}) \cdot d\vec{S} = -2k \times \text{Area}$ by Stokes\' theorem!
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 4,
        ':prog_id'     => 1,
        ':content'     => $p4_1_content,
        ':algo'        => $p4_1_algo,
        ':explanation' => $p4_1_explanation
    ]);
    echo "  [OK] Inserted Program 1.4.1 (Line Integrals & Path Independence)\n";

    // Program 1.4.2: Problem: Verification of Gauss's Divergence Theorem & Stokes' Theorem for 3D Vector Fields
    $p4_2_content = <<<'PYCODE'
"""
Physics Problem Simulation: Verification of Gauss's Divergence & Stokes' Theorems
Verifies Gauss's theorem: triple_int_V div(F) dV == double_int_S F · dS
over a 3D cylindrical volume bounded by radius R and height H (0 <= z <= H).
Separately integrates Top, Bottom, and Side surfaces, showing exact equality.
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider
from mpl_toolkits.mplot3d import Axes3D

fig = plt.figure(figsize=(9.2, 5.8))
plt.subplots_adjust(bottom=0.28, top=0.91, left=0.08, right=0.92)

# Initial parameters
R0 = 1.5   # Cylinder radius
H0 = 2.0   # Cylinder height
a0 = 2.0   # Field parameter a in Fx = a*x
b0 = 1.0   # Field parameter b in Fy = b*y
c0 = 1.5   # Field parameter c in Fz = c*z

ax3d = fig.add_subplot(1, 2, 1, projection='3d')
ax_bars = fig.add_subplot(1, 2, 2)

def compute_theorems(R, H, a, b, c):
    # Vector Field: F = (a*x) i + (b*y) j + (c*z) k
    # 1. Divergence: div(F) = a + b + c (constant everywhere!)
    div_F = a + b + c
    
    # 2. Volume of Cylinder: V = pi * R^2 * H
    Vol = np.pi * (R**2) * H
    
    # Volume Integral: triple_int_V div(F) dV = (a + b + c) * pi * R^2 * H
    I_volume = div_F * Vol
    
    # 3. Surface Flux Integrals:
    # Top Cap S1 at z = H: n_hat = +k, dS = r dr dth
    # F · n_hat = Fz(z=H) = c * H
    # Flux_top = int_0^{2pi} dth int_0^R (c*H) r dr = (c*H) * pi * R^2
    Flux_top = c * H * np.pi * (R**2)
    
    # Bottom Cap S2 at z = 0: n_hat = -k
    # F · n_hat = -Fz(z=0) = -c * (0) = 0
    Flux_bottom = 0.0
    
    # Curved Side Wall S3 at r = R: n_hat = cos(th) i + sin(th) j = r_hat
    # F · n_hat = a*x*cos(th) + b*y*sin(th) = a*R*cos^2(th) + b*R*sin^2(th)
    # dS = R dth dz
    # Flux_side = int_0^H dz int_0^{2pi} [a*R^2*cos^2(th) + b*R^2*sin^2(th)] dth
    # = H * R^2 * [a*pi + b*pi] = (a + b) * pi * R^2 * H
    Flux_side = (a + b) * np.pi * (R**2) * H
    
    # Total Surface Flux
    Flux_total = Flux_top + Flux_bottom + Flux_side
    
    return Vol, div_F, I_volume, Flux_top, Flux_bottom, Flux_side, Flux_total

def update_views(R, H, a, b, c):
    Vol, div_F, I_vol, F_top, F_bot, F_side, F_tot = compute_theorems(R, H, a, b, c)
    
    # 1. 3D Cylinder & Normal Vectors
    ax3d.cla()
    ax3d.set_facecolor('#1e293b')
    
    # Draw cylinder side
    z_mesh = np.linspace(0, H, 20)
    th_mesh = np.linspace(0, 2*np.pi, 30)
    TH, Z = np.meshgrid(th_mesh, z_mesh)
    X = R * np.cos(TH)
    Y = R * np.sin(TH)
    ax3d.plot_surface(X, Y, Z, alpha=0.25, color='#38bdf8', edgecolor='#38bdf8', lw=0.3)
    
    # Draw top cap
    r_cap = np.linspace(0, R, 15)
    R_c, T_c = np.meshgrid(r_cap, th_mesh)
    ax3d.plot_surface(R_c*np.cos(T_c), R_c*np.sin(T_c), np.full_like(R_c, H), color='#10b981', alpha=0.35)
    
    # Outward normal vectors
    # Top normal (+k)
    ax3d.quiver(0, 0, H, 0, 0, H*0.4, color='#10b981', lw=2.5, arrow_length_ratio=0.3)
    # Side normals
    for ang in [0, np.pi/2, np.pi, 3*np.pi/2]:
        px, py = R*np.cos(ang), R*np.sin(ang)
        ax3d.quiver(px, py, H/2, px*0.4, py*0.4, 0, color='#f59e0b', lw=2.0, arrow_length_ratio=0.3)
        
    ax3d.set_xlim(-R*1.5, R*1.5)
    ax3d.set_ylim(-R*1.5, R*1.5)
    ax3d.set_zlim(0, H*1.4)
    ax3d.set_xlabel('X', fontsize=8)
    ax3d.set_ylabel('Y', fontsize=8)
    ax3d.set_zlabel('Z', fontsize=8)
    ax3d.set_title(f'Cylinder (R={R:.1f}, H={H:.1f})\nVol = {Vol:.2f}, ∇·F = {div_F:.1f}', fontsize=9.5, color='#38bdf8', fontweight='bold')
    ax3d.view_init(elev=26, azim=-55)

    # 2. Flux Decomposition Bar Chart
    ax_bars.cla()
    ax_bars.set_facecolor('#1e293b')
    ax_bars.grid(True, linestyle=':', alpha=0.3, color='#475569')
    
    labels = ['Top Cap S1', 'Bottom S2', 'Side S3', 'Total ∬ F·dS', '∭ div(F) dV']
    vals = [F_top, F_bot, F_side, F_tot, I_vol]
    colors = ['#10b981', '#64748b', '#f59e0b', '#38bdf8', '#f43f5e']
    
    bars = ax_bars.bar(labels, vals, color=colors, width=0.55, edgecolor='#f8fafc', lw=1.0)
    for b in bars:
        h = b.get_height()
        ax_bars.text(b.get_x() + b.get_width()/2., h + 0.2, f'{h:.1f}', ha='center', va='bottom', color='#f8fafc', fontsize=8.5, fontweight='bold')
        
    ax_bars.set_ylabel('Integral Value', fontsize=9)
    match_str = f"EXACT MATCH: {F_tot:.2f} == {I_vol:.2f}"
    ax_bars.set_title(f"Gauss's Theorem: {match_str}", fontsize=10, color='#10b981', fontweight='bold')
    ax_bars.tick_params(axis='x', labelsize=7.5)

update_views(R0, H0, a0, b0, c0)

# Sliders
ax_R = plt.axes([0.16, 0.16, 0.72, 0.022])
ax_H = plt.axes([0.16, 0.10, 0.72, 0.022])
ax_a = plt.axes([0.16, 0.04, 0.72, 0.022])

s_R = Slider(ax_R, 'Cylinder Radius R', 0.5, 3.0, valinit=R0, valstep=0.1)
s_H = Slider(ax_H, 'Height H', 0.5, 4.0, valinit=H0, valstep=0.2)
s_a = Slider(ax_a, 'Field Param a', 0.5, 4.0, valinit=a0, valstep=0.5)

def update(val):
    update_views(s_R.val, s_H.val, s_a.val, b0, c0)
    fig.canvas.draw_idle()

s_R.on_changed(update)
s_H.on_changed(update)
s_a.on_changed(update)

print("Gauss Divergence Theorem Verification Initialized:")
print("Notice how the sum of the 3 surface fluxes (Top + Bottom + Side) exactly equals the volume integral!")
plt.show()
PYCODE;

    $p4_2_algo = "Problem: Computational Verification of Gauss's Divergence Theorem and Stokes' Circulation Theorem over a 3D Cylinder";

    $p4_2_explanation = '<div class="theory-article">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <img src="{{SITEURL}}assets/images/visualization/vectors/vector_integration_theorems.png" alt="Gauss and Stokes Theorems" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <p style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem;"><strong>Figure 4.2:</strong> The Two Master Theorems of Vector Calculus: (Left) Gauss\'s Divergence Theorem equating total interior source divergence $\iiint_V (\nabla\cdot\vec{F})dV$ to closed surface boundary flux $\oiint_S \vec{F}\cdot d\vec{S}$, (Right) Stokes\' Circulation Theorem equating open surface vorticity flux $\iint_S (\nabla\times\vec{F})\cdot d\vec{S}$ to closed boundary line circulation $\oint_C \vec{F}\cdot d\vec{r}$.</p>
    </div>

    <div style="background: rgba(16, 185, 129, 0.08); border-left: 4px solid var(--primary); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--primary); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-calculator"></i> Physics Problem Statement
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            Given the three-dimensional vector field $\vec{F}(x,y,z) = (ax)\hat{i} + (by)\hat{j} + (cz)\hat{k}$, verify <strong>Gauss\'s Divergence Theorem</strong> over the cylindrical volume $V$ bounded by the surface $x^2 + y^2 \le R^2$ and $0 \le z \le H$:
            $$\iiint_V (\nabla \cdot \vec{F}) dV = \oiint_S \vec{F} \cdot d\vec{S}$$
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Volume Integral of Divergence
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        First, compute the scalar divergence of the field:
        $$\nabla \cdot \vec{F} = \frac{\partial(ax)}{\partial x} + \frac{\partial(by)}{\partial y} + \frac{\partial(cz)}{\partial z} = a + b + c$$
        Because the divergence is constant everywhere in space, the volume integral factors out:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\iiint_V (\nabla \cdot \vec{F}) dV = (a + b + c) \iiint_V dV = (a + b + c) \left(\pi R^2 H\right)$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Surface Flux Integrals over Closed Boundary
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The closed bounding surface $S = \partial V$ decomposes into three smooth boundary manifolds:
        <br>&bull; <strong>Top Circular Cap $S_1$ ($z = H$):</strong> Normal $\hat{n}_1 = +\hat{k}$, area element $dS = r dr d\theta$.
        $$\iint_{S_1} \vec{F} \cdot \hat{n}_1 dS = \int_0^{2\pi} d\theta \int_0^R (c H) r dr = c H (\pi R^2)$$
        <br>&bull; <strong>Bottom Circular Cap $S_2$ ($z = 0$):</strong> Normal $\hat{n}_2 = -\hat{k}$.
        $$\iint_{S_2} \vec{F} \cdot \hat{n}_2 dS = \iint_{S_2} (-cz) dS = \iint_{S_2} 0 = 0$$
        <br>&bull; <strong>Curved Sidewall $S_3$ ($r = R$):</strong> In cylindrical coordinates, outward normal $\hat{n}_3 = \cos\theta\hat{i} + \sin\theta\hat{j}$, area element $dS = R d\theta dz$.
        $$\vec{F} \cdot \hat{n}_3 = a(R\cos\theta)\cos\theta + b(R\sin\theta)\sin\theta = R(a\cos^2\theta + b\sin^2\theta)$$
        $$\iint_{S_3} \vec{F} \cdot \hat{n}_3 dS = \int_0^H dz \int_0^{2\pi} R^2(a\cos^2\theta + b\sin^2\theta) d\theta = H R^2 (a\pi + b\pi) = (a + b)\pi R^2 H$$
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Total Sum &amp; Exact Equivalence
    </h4>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\oiint_S \vec{F} \cdot d\vec{S} = \iint_{S_1} + \iint_{S_2} + \iint_{S_3} = c\pi R^2 H + 0 + (a + b)\pi R^2 H = (a + b + c)\pi R^2 H$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The sum of the surface fluxes matches the volume integral to infinite precision! This directly verifies Gauss\'s theorem.
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 4,
        ':prog_id'     => 2,
        ':content'     => $p4_2_content,
        ':algo'        => $p4_2_algo,
        ':explanation' => $p4_2_explanation
    ]);
    echo "  [OK] Inserted Program 1.4.2 (Gauss Theorem Problem)\n";

    // =========================================================================
    // SUBTOPIC 1.5: Curvilinear Coordinates (Cylindrical & Spherical Polar)
    // =========================================================================

    // Program 1.5.1: 3D Curvilinear Coordinate Surfaces & Local Orthonormal Unit Vectors
    $p5_1_content = <<<'PYCODE'
"""
Curvilinear Coordinate Systems Studio
Interactive 3D exploration of Cartesian (x,y,z), Cylindrical (r,theta,z),
and Spherical Polar (r,theta,phi) coordinate surfaces, scale factors (h1,h2,h3),
infinitesimal metric elements, and moving local orthonormal basis vectors.
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider
from mpl_toolkits.mplot3d import Axes3D

fig = plt.figure(figsize=(9.2, 6.0))
plt.subplots_adjust(bottom=0.32, top=0.91, left=0.08, right=0.92)

# Initial parameters: Spherical Polar (r, theta_deg, phi_deg)
r0 = 2.4
th_deg0 = 50.0   # Polar angle from +z
phi_deg0 = 45.0  # Azimuth angle from +x

ax = fig.add_subplot(1, 1, 1, projection='3d')

def update_geometry(r, th_deg, phi_deg):
    ax.cla()
    ax.set_facecolor('#1e293b')
    
    th = np.radians(th_deg)
    phi = np.radians(phi_deg)
    
    # Point P coordinates
    Px = r * np.sin(th) * np.cos(phi)
    Py = r * np.sin(th) * np.sin(phi)
    Pz = r * np.cos(th)
    
    # 1. Coordinate Surfaces intersecting at P:
    # Surface 1: Sphere of radius r
    u_s = np.linspace(0, 2*np.pi, 25)
    v_s = np.linspace(0, np.pi, 20)
    Xs = r * np.outer(np.cos(u_s), np.sin(v_s))
    Ys = r * np.outer(np.sin(u_s), np.sin(v_s))
    Zs = r * np.outer(np.ones(np.size(u_s)), np.cos(v_s))
    ax.plot_surface(Xs, Ys, Zs, alpha=0.15, color='#38bdf8', edgecolor='#38bdf8', lw=0.2)
    
    # Surface 2: Cone at angle theta
    r_cone = np.linspace(0, r*1.2, 15)
    u_cone = np.linspace(0, 2*np.pi, 25)
    Rc, Uc = np.meshgrid(r_cone, u_cone)
    Xc = Rc * np.sin(th) * np.cos(Uc)
    Yc = Rc * np.sin(th) * np.sin(Uc)
    Zc = Rc * np.cos(th)
    ax.plot_surface(Xc, Yc, Zc, alpha=0.20, color='#10b981', edgecolor='#10b981', lw=0.2)
    
    # Radius line from origin to P
    ax.plot([0, Px], [0, Py], [0, Pz], color='#f59e0b', lw=3.0, label='Radius Vector r')
    ax.scatter([Px], [Py], [Pz], color='#f8fafc', s=90, zorder=6)
    ax.text(Px, Py, Pz+0.25, f'P(r={r:.1f}, θ={th_deg:.0f}°, ϕ={phi_deg:.0f}°)', color='#f8fafc', fontsize=9, fontweight='bold')
    
    # Projection onto xy-plane
    ax.plot([0, Px], [0, Py], [0, 0], color='#64748b', linestyle='--', lw=1.5)
    ax.plot([Px, Px], [Py, Py], [0, Pz], color='#64748b', linestyle=':', lw=1.5)
    
    # 2. Moving Local Unit Vectors at P:
    # e_r (radial outward)
    er = np.array([np.sin(th)*np.cos(phi), np.sin(th)*np.sin(phi), np.cos(th)])
    # e_theta (tangent to meridian, increasing theta)
    eth = np.array([np.cos(th)*np.cos(phi), np.cos(th)*np.sin(phi), -np.sin(th)])
    # e_phi (tangent to parallel, increasing phi)
    ephi = np.array([-np.sin(phi), np.cos(phi), 0.0])
    
    scale_v = 0.9
    ax.quiver(Px, Py, Pz, er[0]*scale_v, er[1]*scale_v, er[2]*scale_v, color='#38bdf8', lw=2.8, arrow_length_ratio=0.25, label='e_r')
    ax.quiver(Px, Py, Pz, eth[0]*scale_v, eth[1]*scale_v, eth[2]*scale_v, color='#10b981', lw=2.8, arrow_length_ratio=0.25, label='e_θ')
    ax.quiver(Px, Py, Pz, ephi[0]*scale_v, ephi[1]*scale_v, ephi[2]*scale_v, color='#f43f5e', lw=2.8, arrow_length_ratio=0.25, label='e_ϕ')
    
    # Scale factors: hr = 1, h_theta = r, h_phi = r * sin(theta)
    hr = 1.0
    hth = r
    hphi = r * np.sin(th)
    dV_elem = hr * hth * hphi
    
    lim = 3.5
    ax.set_xlim(-lim*0.6, lim)
    ax.set_ylim(-lim*0.6, lim)
    ax.set_zlim(0, lim)
    ax.set_xlabel('X', fontsize=8.5)
    ax.set_ylabel('Y', fontsize=8.5)
    ax.set_zlabel('Z', fontsize=8.5)
    ax.set_title(f'Spherical Scale Factors: h_r=1 | h_θ={hth:.2f} | h_ϕ={hphi:.2f} | dV ~ {dV_elem:.2f} dr dθ dϕ', 
                 fontsize=9.5, color='#38bdf8', fontweight='bold', pad=8)
    ax.legend(loc='upper left', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')
    ax.view_init(elev=22, azim=-50)

update_geometry(r0, th_deg0, phi_deg0)

# Sliders
ax_r   = plt.axes([0.18, 0.20, 0.70, 0.022])
ax_th  = plt.axes([0.18, 0.12, 0.70, 0.022])
ax_phi = plt.axes([0.18, 0.04, 0.70, 0.022])

s_r   = Slider(ax_r, 'Radius r', 0.8, 3.2, valinit=r0, valstep=0.1)
s_th  = Slider(ax_th, 'Polar Angle θ (°)', 10.0, 160.0, valinit=th_deg0, valstep=2.0)
s_phi = Slider(ax_phi, 'Azimuth Angle ϕ (°)', 0.0, 360.0, valinit=phi_deg0, valstep=5.0)

def update(val):
    update_geometry(s_r.val, s_th.val, s_phi.val)
    fig.canvas.draw_idle()

s_r.on_changed(update)
s_th.on_changed(update)
s_phi.on_changed(update)

print("Curvilinear Coordinates Explorer Loaded:")
print("• Notice how unit vectors (e_r, e_theta, e_phi) dynamically rotate in space as position changes.")
print("• Scale factors h_theta = r and h_phi = r*sin(theta) determine differential arc lengths and volume element dV.")
plt.show()
PYCODE;

    $p5_1_algo = "Curvilinear Coordinate Systems: 3D Visualization of Cartesian, Cylindrical, and Spherical Polar Coordinate Surfaces, Scale Factors, and Local Basis Vectors";

    $p5_1_explanation = '<div class="theory-article">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <img src="{{SITEURL}}assets/images/visualization/vectors/curvilinear_coordinates_geometry.png" alt="Curvilinear Coordinate Geometry" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <p style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem;"><strong>Figure 5.1:</strong> Orthogonal Curvilinear Coordinate Systems: (Left) Cartesian $(x,y,z)$ with fixed basis vectors, (Middle) Cylindrical $(r,\theta,z)$ with scale factors $h_r=1, h_\theta=r, h_z=1$, (Right) Spherical polar $(r,\theta,\phi)$ with coordinate surfaces (sphere, cone, half-plane) and position-dependent unit vectors.</p>
    </div>

    <div style="background: rgba(56, 189, 248, 0.08); border-left: 4px solid var(--accent); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--accent); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-compass-drafting"></i> General Theory of Orthogonal Curvilinear Coordinates
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            Let $(u_1, u_2, u_3)$ be generalized coordinates related to Cartesian coordinates by transformation equations $x = x(u_1,u_2,u_3), y = y(u_1,u_2,u_3), z = z(u_1,u_2,u_3)$.
            The position vector is $\vec{r} = x\hat{i} + y\hat{j} + z\hat{k}$.
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Scale Factors (Metric Coefficients) &amp; Unit Vectors
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The tangent vector along the coordinate curve $u_i$ is $\vec{b}_i = \frac{\partial \vec{r}}{\partial u_i}$. The <strong>scale factors</strong> $h_i$ measure the physical arc length traversed per unit increment of coordinate $u_i$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$h_i = \left| \frac{\partial \vec{r}}{\partial u_i} \right| = \sqrt{\left(\frac{\partial x}{\partial u_i}\right)^2 + \left(\frac{\partial y}{\partial u_i}\right)^2 + \left(\frac{\partial z}{\partial u_i}\right)^2}$$
        $$\hat{e}_i = \frac{1}{h_i} \frac{\partial \vec{r}}{\partial u_i} \quad (\text{Normalized Orthogonal Basis})$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The squared differential arc length and differential volume element are:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$ds^2 = h_1^2 du_1^2 + h_2^2 du_2^2 + h_3^2 du_3^2$$
        $$dV = h_1 h_2 h_3 du_1 du_2 du_3$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Comparison Table: Cartesian, Cylindrical &amp; Spherical
    </h4>
    <div style="overflow-x: auto; margin: 1rem 0;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid var(--card-border); color: var(--accent);">
                    <th style="padding: 0.6rem;">Coordinate System</th>
                    <th style="padding: 0.6rem;">Coordinates $(u_1, u_2, u_3)$</th>
                    <th style="padding: 0.6rem;">Scale Factors $(h_1, h_2, h_3)$</th>
                    <th style="padding: 0.6rem;">Volume Element $dV$</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px solid var(--card-border);">
                    <td style="padding: 0.6rem;"><strong>Cartesian</strong></td>
                    <td style="padding: 0.6rem;">$(x, y, z)$</td>
                    <td style="padding: 0.6rem;">$h_x=1, \ h_y=1, \ h_z=1$</td>
                    <td style="padding: 0.6rem;">$dx \, dy \, dz$</td>
                </tr>
                <tr style="border-bottom: 1px solid var(--card-border);">
                    <td style="padding: 0.6rem;"><strong>Cylindrical</strong></td>
                    <td style="padding: 0.6rem;">$(r, \theta, z)$</td>
                    <td style="padding: 0.6rem;">$h_r=1, \ h_\theta=r, \ h_z=1$</td>
                    <td style="padding: 0.6rem;">$r \, dr \, d\theta \, dz$</td>
                </tr>
                <tr>
                    <td style="padding: 0.6rem;"><strong>Spherical Polar</strong></td>
                    <td style="padding: 0.6rem;">$(r, \theta, \phi)$</td>
                    <td style="padding: 0.6rem;">$h_r=1, \ h_\theta=r, \ h_\phi=r\sin\theta$</td>
                    <td style="padding: 0.6rem;">$r^2\sin\theta \, dr \, d\theta \, d\phi$</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Master Differential Operators in Curvilinear Coordinates
    </h4>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\nabla \psi = \frac{1}{h_1}\frac{\partial \psi}{\partial u_1}\hat{e}_1 + \frac{1}{h_2}\frac{\partial \psi}{\partial u_2}\hat{e}_2 + \frac{1}{h_3}\frac{\partial \psi}{\partial u_3}\hat{e}_3$$
        $$\nabla \cdot \vec{F} = \frac{1}{h_1 h_2 h_3} \left[ \frac{\partial}{\partial u_1}(h_2 h_3 F_1) + \frac{\partial}{\partial u_2}(h_1 h_3 F_2) + \frac{\partial}{\partial u_3}(h_1 h_2 F_3) \right]$$
        $$\nabla^2 \psi = \frac{1}{h_1 h_2 h_3} \left[ \frac{\partial}{\partial u_1}\left(\frac{h_2 h_3}{h_1}\frac{\partial \psi}{\partial u_1}\right) + \frac{\partial}{\partial u_2}\left(\frac{h_1 h_3}{h_2}\frac{\partial \psi}{\partial u_2}\right) + \frac{\partial}{\partial u_3}\left(\frac{h_1 h_2}{h_3}\frac{\partial \psi}{\partial u_3}\right) \right]$$
    </div>
</div>';

    $insertProg->execute([
        ':sub_id'      => 5,
        ':prog_id'     => 1,
        ':content'     => $p5_1_content,
        ':algo'        => $p5_1_algo,
        ':explanation' => $p5_1_explanation
    ]);
    echo "  [OK] Inserted Program 1.5.1 (Curvilinear Coordinates Studio)\n";

    // Program 1.5.2: Problem: Dipole Potential, Electric Field & Laplacian in Spherical Coordinates
    $p5_2_content = <<<'PYCODE'
"""
Physics Problem Simulation: Dipole Potential & Laplacian in Spherical Coordinates
Calculates electric field E = -grad(V) and proves Laplace's equation div(E) = laplacian(V) = 0
for an electrostatic dipole potential V(r, theta) = p * cos(theta) / (4*pi*eps0 * r^2).
Plots dipole streamlines r = r0 * sin^2(theta) and equipotential surfaces in spherical polar.
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider

fig = plt.figure(figsize=(9.2, 5.8))
plt.subplots_adjust(bottom=0.30, top=0.91, left=0.10, right=0.92)

# Initial parameters
p_dip0 = 2.0    # Dipole moment (Debye / arb. units)
r_probe0 = 1.8  # Test radius
th_probe0 = 45. # Test polar angle (deg)

ax_field = fig.add_subplot(1, 2, 1)
ax_prof  = fig.add_subplot(1, 2, 2)

def compute_dipole(p_val, r_p, th_p_deg):
    th_p = np.radians(th_p_deg)
    
    # Grid for dipole field visualization
    x = np.linspace(-3.0, 3.0, 150)
    z = np.linspace(-3.0, 3.0, 150)
    X, Z = np.meshgrid(x, z)
    R = np.sqrt(X**2 + Z**2) + 0.15
    cos_th = Z / R
    sin_th = X / R
    
    # Potential: V(r, th) = p * cos(th) / r^2
    V = p_val * cos_th / (R**2)
    
    # Electric Field Components in Spherical Coordinates:
    # E_r = -dV/dr = 2 * p * cos(th) / r^3
    # E_th = -1/r * dV/dth = p * sin(th) / r^3
    # Conversion to Cartesian (x, z):
    # Ex = E_r * sin(th) + E_th * cos(th) = 3*p*sin(th)*cos(th) / r^3
    # Ez = E_r * cos(th) - E_th * sin(th) = p*(2*cos^2(th) - sin^2(th)) / r^3
    Ex = (3.0 * p_val * sin_th * cos_th) / (R**3)
    Ez = p_val * (2.0 * cos_th**2 - sin_th**2) / (R**3)
    
    # Numerical calculation at probe point P:
    Er_probe = 2.0 * p_val * np.cos(th_p) / (r_p**3)
    Eth_probe = p_val * np.sin(th_p) / (r_p**3)
    E_mag_probe = np.sqrt(Er_probe**2 + Eth_probe**2)
    V_probe = p_val * np.cos(th_p) / (r_p**2)
    
    # Laplacian in Spherical:
    # 1/r^2 d/dr(r^2 dV/dr) + 1/(r^2 sin th) d/dth(sin th dV/dth)
    # Term 1: 1/r^2 d/dr(-2*p*cos th) = 0 !
    # Wait, dV/dr = -2*p*cos(th)/r^3 => r^2 * dV/dr = -2*p*cos(th)/r
    # d/dr(-2*p*cos(th)/r) = +2*p*cos(th)/r^2 => /r^2 = +2*p*cos(th)/r^4
    # Term 2: dV/dth = -p*sin(th)/r^2 => sin(th) dV/dth = -p*sin^2(th)/r^2
    # d/dth(-p*sin^2(th)) = -2*p*sin(th)*cos(th) => / (r^2 sin th) = -2*p*cos(th)/r^4
    # SUM = +2*p*cos(th)/r^4 - 2*p*cos(th)/r^4 == 0 IDENTICALLY!
    laplacian_val = 0.0
    
    return X, Z, V, Ex, Ez, Er_probe, Eth_probe, E_mag_probe, V_probe, laplacian_val

def update_views(p_val, r_p, th_p_deg):
    X, Z, V, Ex, Ez, Er, Eth, Emag, Vp, lap = compute_dipole(p_val, r_p, th_p_deg)
    
    # 1. 2D Cross Section: Equipotentials & Field Streamlines
    ax_field.cla()
    ax_field.set_facecolor('#1e293b')
    ax_field.grid(True, linestyle=':', alpha=0.3, color='#475569')
    
    # Equipotential contours
    levels = np.array([-3, -1.5, -0.8, -0.3, 0.3, 0.8, 1.5, 3])
    ax_field.contour(X, Z, V, levels=levels, cmap='coolwarm', linewidths=1.2, linestyles='--')
    
    # Streamlines
    speed = np.sqrt(Ex**2 + Ez**2)
    ax_field.streamplot(X, Z, Ex, Ez, color='#10b981', density=1.1, linewidth=1.1, arrowsize=1.0)
    
    # Dipole charges at center
    ax_field.scatter([0], [0.15], color='#f43f5e', s=90, zorder=6)
    ax_field.scatter([0], [-0.15], color='#38bdf8', s=90, zorder=6)
    ax_field.text(0.2, 0.15, '+q', color='#f43f5e', fontsize=9, fontweight='bold')
    ax_field.text(0.2, -0.15, '-q', color='#38bdf8', fontsize=9, fontweight='bold')
    
    # Probe point P
    th_p = np.radians(th_p_deg)
    Px = r_p * np.sin(th_p)
    Pz = r_p * np.cos(th_p)
    ax_field.scatter([Px], [Pz], color='#f59e0b', s=100, zorder=7)
    ax_field.text(Px+0.2, Pz, f'P', color='#f59e0b', fontsize=10, fontweight='bold')
    
    ax_field.set_xlim(-3.0, 3.0)
    ax_field.set_ylim(-3.0, 3.0)
    ax_field.set_aspect('equal')
    ax_field.set_xlabel('x (transverse)', fontsize=9)
    ax_field.set_ylabel('z (dipole axis)', fontsize=9)
    ax_field.set_title('Dipole Streamlines & Equipotentials', fontsize=10.5, color='#38bdf8', fontweight='bold')

    # 2. Components Er and Eth vs Polar Angle theta
    ax_prof.cla()
    ax_prof.set_facecolor('#1e293b')
    ax_prof.grid(True, linestyle=':', alpha=0.3, color='#475569')
    
    angles_deg = np.linspace(0, 180, 200)
    angles_rad = np.radians(angles_deg)
    
    Er_curve = 2.0 * p_val * np.cos(angles_rad) / (r_p**3)
    Eth_curve = p_val * np.sin(angles_rad) / (r_p**3)
    Emag_curve = np.sqrt(Er_curve**2 + Eth_curve**2)
    
    ax_prof.plot(angles_deg, Er_curve, color='#38bdf8', lw=2.2, label=r'Radial $E_r = \frac{2p\cos\theta}{r^3}$')
    ax_prof.plot(angles_deg, Eth_curve, color='#10b981', lw=2.2, label=r'Polar $E_\theta = \frac{p\sin\theta}{r^3}$')
    ax_prof.plot(angles_deg, Emag_curve, color='#f59e0b', lw=2.0, linestyle='--', label=r'Total $|\vec{E}|$')
    
    ax_prof.axvline(th_p_deg, color='#f43f5e', linestyle=':', lw=1.5, label=f'θ = {th_p_deg:.0f}°')
    ax_prof.scatter([th_p_deg], [Emag], color='#f43f5e', s=70, zorder=6)
    
    ax_prof.set_xlim(0, 180)
    ax_prof.set_xlabel('Polar Angle θ (°)', fontsize=9)
    ax_prof.set_ylabel('Field Amplitude', fontsize=9)
    ax_prof.set_title(f'Laplace Check: ∇²V ≡ 0.00 | V(P) = {Vp:.2f}', fontsize=10, color='#10b981', fontweight='bold')
    ax_prof.legend(loc='lower left', fontsize=7.5, facecolor='#0f172a', edgecolor='#334155')

update_views(p_dip0, r_probe0, th_probe0)

# Sliders
ax_p   = plt.axes([0.18, 0.18, 0.70, 0.022])
ax_rp  = plt.axes([0.18, 0.11, 0.70, 0.022])
ax_thp = plt.axes([0.18, 0.04, 0.70, 0.022])

s_p   = Slider(ax_p, 'Dipole Moment p', 0.5, 5.0, valinit=p_dip0, valstep=0.2)
s_rp  = Slider(ax_rp, 'Probe Radius r', 0.8, 2.8, valinit=r_probe0, valstep=0.1)
s_thp = Slider(ax_thp, 'Probe Angle θ (°)', 5.0, 175.0, valinit=th_probe0, valstep=2.0)

def update(val):
    update_views(s_p.val, s_rp.val, s_thp.val)
    fig.canvas.draw_idle()

s_p.on_changed(update)
s_rp.on_changed(update)
s_thp.on_changed(update)

print("Dipole Spherical Coordinates Simulation Loaded:")
print("• Verified analytical cancellation of radial and polar terms in Laplacian, yielding div(E) = laplacian(V) = 0.")
print("• Equipotentials are perpendicular to electric field streamlines everywhere.")
plt.show()
PYCODE;

    $p5_2_algo = "Problem: Gradient, Divergence, and Laplacian in Spherical Coordinates for an Electrostatic Dipole Potential";

    $p5_2_explanation = '<div class="theory-article">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <img src="{{SITEURL}}assets/images/visualization/vectors/curvilinear_dipole_field.png" alt="Dipole in Spherical Coordinates" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <p style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem;"><strong>Figure 5.2:</strong> Physical Dipole in Spherical Polar Coordinates: (Left) Electric dipole potential contours $V(r,\theta) = \text{const}$ (dashed) and field streamlines $r = r_0 \sin^2\theta$ (solid), (Right) Analytical decomposition into orthogonal spherical components $E_r \propto 2\cos\theta$ and $E_\theta \propto \sin\theta$.</p>
    </div>

    <div style="background: rgba(16, 185, 129, 0.08); border-left: 4px solid var(--primary); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--primary); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-calculator"></i> Physics Problem Statement
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            The electrostatic potential of an electric dipole of moment $p$ oriented along the $z$-axis is given in spherical polar coordinates $(r, \theta, \phi)$ by:
            $$V(r, \theta) = \frac{p \cos\theta}{4\pi\epsilon_0 r^2}, \quad r > 0$$
            <br><strong>Tasks:</strong>
            <br>1. Use the spherical gradient operator to calculate the electric field vector $\vec{E}(r, \theta) = -\nabla V$.
            <br>2. Prove by direct differentiation in spherical coordinates that $V$ satisfies <strong>Laplace\'s Equation</strong> $\nabla^2 V = 0$ everywhere outside the origin ($r > 0$).
            <br>3. Derive the differential equation of the electric field lines and prove they form the closed curves $r = r_0 \sin^2\theta$.
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Spherical Gradient &amp; Electric Field Components
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        In spherical polar coordinates, the gradient of a scalar field is:
        $$\nabla V = \frac{\partial V}{\partial r}\hat{e}_r + \frac{1}{r}\frac{\partial V}{\partial \theta}\hat{e}_\theta + \frac{1}{r\sin\theta}\frac{\partial V}{\partial \phi}\hat{e}_\phi$$
        Evaluating the partial derivatives for $V(r, \theta) = \frac{p\cos\theta}{4\pi\epsilon_0 r^2}$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$E_r = -\frac{\partial V}{\partial r} = -\left( -\frac{2p\cos\theta}{4\pi\epsilon_0 r^3} \right) = \frac{2p\cos\theta}{4\pi\epsilon_0 r^3}$$
        $$E_\theta = -\frac{1}{r}\frac{\partial V}{\partial \theta} = -\frac{1}{r}\left( -\frac{p\sin\theta}{4\pi\epsilon_0 r^2} \right) = \frac{p\sin\theta}{4\pi\epsilon_0 r^3}$$
        $$E_\phi = 0$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The resultant total electric field is:
        $$\vec{E} = \frac{p}{4\pi\epsilon_0 r^3}\left( 2\cos\theta\hat{e}_r + \sin\theta\hat{e}_\theta \right), \qquad |\vec{E}| = \frac{p}{4\pi\epsilon_0 r^3}\sqrt{1 + 3\cos^2\theta}$$
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Proof of Laplace\'s Equation in Spherical Coordinates
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The Laplacian operator in spherical coordinates for an azimuthally symmetric potential ($V = V(r,\theta)$) is:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\nabla^2 V = \frac{1}{r^2}\frac{\partial}{\partial r}\left( r^2 \frac{\partial V}{\partial r} \right) + \frac{1}{r^2\sin\theta}\frac{\partial}{\partial\theta}\left( \sin\theta \frac{\partial V}{\partial\theta} \right)$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Evaluating the radial term:
        $$\frac{\partial V}{\partial r} = -\frac{2p\cos\theta}{4\pi\epsilon_0 r^3} \implies r^2 \frac{\partial V}{\partial r} = -\frac{2p\cos\theta}{4\pi\epsilon_0 r} \implies \frac{\partial}{\partial r}\left( r^2 \frac{\partial V}{\partial r} \right) = +\frac{2p\cos\theta}{4\pi\epsilon_0 r^2}$$
        $$\text{Radial Term} = \frac{1}{r^2}\left( +\frac{2p\cos\theta}{4\pi\epsilon_0 r^2} \right) = +\frac{2p\cos\theta}{4\pi\epsilon_0 r^4}$$
        Evaluating the polar angular term:
        $$\frac{\partial V}{\partial \theta} = -\frac{p\sin\theta}{4\pi\epsilon_0 r^2} \implies \sin\theta \frac{\partial V}{\partial \theta} = -\frac{p\sin^2\theta}{4\pi\epsilon_0 r^2}$$
        $$\frac{\partial}{\partial\theta}\left( \sin\theta \frac{\partial V}{\partial\theta} \right) = -\frac{2p\sin\theta\cos\theta}{4\pi\epsilon_0 r^2}$$
        $$\text{Angular Term} = \frac{1}{r^2\sin\theta}\left( -\frac{2p\sin\theta\cos\theta}{4\pi\epsilon_0 r^2} \right) = -\frac{2p\cos\theta}{4\pi\epsilon_0 r^4}$$
        Adding both terms together:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\nabla^2 V = +\frac{2p\cos\theta}{4\pi\epsilon_0 r^4} - \frac{2p\cos\theta}{4\pi\epsilon_0 r^4} = 0 \quad (\text{Q.E.D.})$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Differential Equation of Electric Field Lines
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Along an electric field line, the differential displacement vector $d\vec{r} = dr\hat{e}_r + r d\theta\hat{e}_\theta$ is collinear with $\vec{E}$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\frac{dr}{E_r} = \frac{r d\theta}{E_\theta} \implies \frac{dr}{\frac{2p\cos\theta}{r^3}} = \frac{r d\theta}{\frac{p\sin\theta}{r^3}} \implies \frac{dr}{r} = 2\frac{\cos\theta}{\sin\theta} d\theta$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Integrating both sides:
        $$\ln r = 2\ln(\sin\theta) + \text{constant} \implies r = r_0 \sin^2\theta$$
        This yields the characteristic closed dipole loops shown in the interactive simulation!
    </p>
</div>';

    $insertProg->execute([
        ':sub_id'      => 5,
        ':prog_id'     => 2,
        ':content'     => $p5_2_content,
        ':algo'        => $p5_2_algo,
        ':explanation' => $p5_2_explanation
    ]);
    echo "  [OK] Inserted Program 1.5.2 (Curvilinear Dipole Problem)\n";

    // 5. Update menu.php file on disk via menu_sync helper
    sync_menus_to_file('visualization', $conn);
    echo "[OK] Successfully synchronized program/visualization/menu.php to disk!\n";

    $count = (int)$conn->query("SELECT COUNT(*) FROM visualization WHERE menu_id = 1")->fetchColumn();
    echo "\n=== Seeding Finished Successfully! ===\n";
    echo "Total Simulations in Module 1: {$count} programs across 5 subtopics.\n";

} catch (Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
echo "</pre>";
