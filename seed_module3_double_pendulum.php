<?php
/**
 * Python4Physics - Seed Module 3: Classical Mechanics - Double Pendulum Dynamics & Chaos
 * 
 * Re-indexes visualization modules:
 *   - Module 4 (Optics) -> Module 5
 *   - Module 3 (Quantum Mechanics) -> Module 4
 *   - Inserts Module 3: Double Pendulum Dynamics & Chaos (3 subtopics, interactive simulations & KaTeX theory)
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/include/menu_sync.php';

header('Content-Type: text/plain; charset=utf-8');

if (!isset($conn) || $conn === null) {
    die("Error: Database connection not available.\n");
}

echo "=== Python4Physics: Seeding Module 3 Double Pendulum Simulation ===\n";

try {
    // 1. Ensure base tables exist
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

    // 2. Check if Module 3 is currently Quantum Mechanics. If so, shift 4 -> 5 and 3 -> 4.
    $chkM3 = $conn->prepare("SELECT title FROM p4p_menus WHERE language='visualization' AND menu_id = 3");
    $chkM3->execute();
    $m3_title = $chkM3->fetchColumn();

    if ($m3_title && str_contains(strtolower($m3_title), 'quantum')) {
        echo "[INFO] Detected Quantum Mechanics at Module 3. Shifting Optics (4->5) and Quantum (3->4)...\n";
        
        // Check if Module 4 already exists (Optics)
        $chkM4 = $conn->prepare("SELECT COUNT(*) FROM p4p_menus WHERE language='visualization' AND menu_id = 4");
        $chkM4->execute();
        if ((int)$chkM4->fetchColumn() > 0) {
            // Delete any stale module 5 first if empty, or shift 4 -> 5
            $conn->exec("DELETE FROM p4p_submenus WHERE language='visualization' AND menu_id = 5");
            $conn->exec("DELETE FROM p4p_menus WHERE language='visualization' AND menu_id = 5");
            $conn->exec("DELETE FROM visualization WHERE menu_id = 5");

            $conn->exec("UPDATE `p4p_submenus` SET `menu_id` = 5 WHERE `language` = 'visualization' AND `menu_id` = 4");
            $conn->exec("UPDATE `p4p_menus` SET `menu_id` = 5, `sort_order` = 5 WHERE `language` = 'visualization' AND `menu_id` = 4");
            $conn->exec("UPDATE `visualization` SET `menu_id` = 5 WHERE `menu_id` = 4");
            echo "  -> Shifted Optics from Module 4 to Module 5.\n";
        }

        // Shift 3 -> 4
        $conn->exec("DELETE FROM p4p_submenus WHERE language='visualization' AND menu_id = 4");
        $conn->exec("DELETE FROM p4p_menus WHERE language='visualization' AND menu_id = 4");
        $conn->exec("DELETE FROM visualization WHERE menu_id = 4");

        $conn->exec("UPDATE `p4p_submenus` SET `menu_id` = 4 WHERE `language` = 'visualization' AND `menu_id` = 3");
        $conn->exec("UPDATE `p4p_menus` SET `menu_id` = 4, `sort_order` = 4 WHERE `language` = 'visualization' AND `menu_id` = 3");
        $conn->exec("UPDATE `visualization` SET `menu_id` = 4 WHERE `menu_id` = 3");
        echo "  -> Shifted Quantum Mechanics from Module 3 to Module 4.\n";
    }

    // 3. Upsert Module 3 in p4p_menus
    $module3_title = 'Classical Mechanics: Double Pendulum Dynamics & Chaos';
    $stmtMenu3 = $conn->prepare("INSERT INTO `p4p_menus` (`language`, `menu_id`, `title`, `sort_order`) 
                                 VALUES ('visualization', 3, :title, 3) 
                                 ON DUPLICATE KEY UPDATE `title` = :title2, `sort_order` = 3");
    $stmtMenu3->execute([':title' => $module3_title, ':title2' => $module3_title]);
    echo "[OK] Module 3 Menu registered: '$module3_title'\n";

    // 4. Subtopics for Module 3
    $subtopics = [
        1 => 'Coupled Nonlinear Oscillations & Parameter Tuning Studio',
        2 => 'Deterministic Chaos & The Butterfly Effect: Sensitivity to Initial Conditions',
        3 => 'Small-Angle Normal Modes, Resonance & Harmonic Beats'
    ];

    $stmtSub = $conn->prepare("INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) 
                              VALUES ('visualization', 3, :sid, :title, :sort)
                              ON DUPLICATE KEY UPDATE `title` = :title2, `sort_order` = :sort2");
    foreach ($subtopics as $sid => $stitle) {
        $stmtSub->execute([
            ':sid'    => $sid,
            ':title'  => $stitle,
            ':sort'   => $sid,
            ':title2' => $stitle,
            ':sort2'  => $sid
        ]);
        echo "  [OK] Subtopic 3.{$sid}: '$stitle'\n";
    }

    // Clear old programs under module 3 before inserting clean fresh programs
    $conn->exec("DELETE FROM `visualization` WHERE `menu_id` = 3");

    // =========================================================================
    // PROGRAM 3.1.1: Double Pendulum Interactive Parameter Studio
    // =========================================================================
    $code_3_1_1 = <<<'PYCODE'
"""
Interactive Double Pendulum Simulation Studio
Coupled Nonlinear Oscillations, Phase Space, Energy Conservation & Trajectory Trail
Powered by Pyodide with Matplotlib Interactive Sliders
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider

# Create multi-panel figure
fig = plt.figure(figsize=(10.5, 6.4))
gs = fig.add_gridspec(2, 2, width_ratios=[1.25, 1.0], height_ratios=[1, 1], hspace=0.38, wspace=0.28)
ax_sim = fig.add_subplot(gs[:, 0])
ax_phase = fig.add_subplot(gs[0, 1])
ax_energy = fig.add_subplot(gs[1, 1])
plt.subplots_adjust(bottom=0.34, top=0.92, left=0.08, right=0.95)

# Slider Axes Layout (2 columns of 4 sliders each)
ax_L1 = plt.axes([0.10, 0.23, 0.35, 0.022])
ax_L2 = plt.axes([0.10, 0.17, 0.35, 0.022])
ax_m1 = plt.axes([0.10, 0.11, 0.35, 0.022])
ax_m2 = plt.axes([0.10, 0.05, 0.35, 0.022])

ax_th1 = plt.axes([0.58, 0.23, 0.35, 0.022])
ax_th2 = plt.axes([0.58, 0.17, 0.35, 0.022])
ax_g   = plt.axes([0.58, 0.11, 0.35, 0.022])
ax_gam = plt.axes([0.58, 0.05, 0.35, 0.022])

# Interactive Sliders with explicit step sizes
s_L1 = Slider(ax_L1, 'L1 (m)', 0.5, 2.5, valinit=1.0, valstep=0.05)
s_L2 = Slider(ax_L2, 'L2 (m)', 0.5, 2.5, valinit=1.0, valstep=0.05)
s_m1 = Slider(ax_m1, 'm1 (kg)', 0.2, 5.0, valinit=1.0, valstep=0.1)
s_m2 = Slider(ax_m2, 'm2 (kg)', 0.2, 5.0, valinit=1.0, valstep=0.1)

s_th1 = Slider(ax_th1, 'θ1 (deg)', -180.0, 180.0, valinit=90.0, valstep=2.0)
s_th2 = Slider(ax_th2, 'θ2 (deg)', -180.0, 180.0, valinit=90.0, valstep=2.0)
s_g   = Slider(ax_g, 'g (m/s²)', 0.0, 25.0, valinit=9.8, valstep=0.2)
s_gam = Slider(ax_gam, 'Damping γ', 0.0, 0.1, valinit=0.0, valstep=0.005)

def solve_double_pendulum(L1, L2, m1, m2, th1_deg, th2_deg, g, gamma, t_max=14.0, steps=800):
    t = np.linspace(0, t_max, steps)
    dt = t[1] - t[0]
    y = np.zeros((steps, 4))
    y[0] = [np.radians(th1_deg), np.radians(th2_deg), 0.0, 0.0]

    def deriv(s):
        th1, th2, w1, w2 = s
        dth = th1 - th2
        m11 = (m1 + m2) * L1
        m12 = m2 * L2 * np.cos(dth)
        m21 = m2 * L1 * np.cos(dth)
        m22 = m2 * L2

        r1 = -m2 * L2 * w2**2 * np.sin(dth) - (m1 + m2) * g * np.sin(th1) - gamma * w1
        r2 = m2 * L1 * w1**2 * np.sin(dth) - m2 * g * np.sin(th2) - gamma * w2

        det = m11 * m22 - m12 * m21
        a1 = (r1 * m22 - m12 * r2) / det
        a2 = (m11 * r2 - r1 * m21) / det
        return np.array([w1, w2, a1, a2])

    for i in range(steps - 1):
        s = y[i]
        k1 = deriv(s)
        k2 = deriv(s + 0.5 * dt * k1)
        k3 = deriv(s + 0.5 * dt * k2)
        k4 = deriv(s + dt * k3)
        y[i + 1] = s + (dt / 6.0) * (k1 + 2*k2 + 2*k3 + k4)

    th1 = y[:, 0]
    th2 = y[:, 1]
    w1 = y[:, 2]
    w2 = y[:, 3]

    x1 = L1 * np.sin(th1)
    y1 = -L1 * np.cos(th1)
    x2 = x1 + L2 * np.sin(th2)
    y2 = y1 - L2 * np.cos(th2)

    # Mechanical Energy (reference zero at bottom rest position)
    T = 0.5 * (m1 + m2) * (L1 * w1)**2 + 0.5 * m2 * (L2 * w2)**2 + m2 * L1 * L2 * w1 * w2 * np.cos(th1 - th2)
    V = (m1 + m2) * g * L1 * (1.0 - np.cos(th1)) + m2 * g * L2 * (1.0 - np.cos(th2))
    E = T + V
    return t, x1, y1, x2, y2, th1, th2, w1, w2, T, V, E

# Compute initial baseline
t, x1, y1, x2, y2, th1, th2, w1, w2, T, V, E = solve_double_pendulum(
    s_L1.val, s_L2.val, s_m1.val, s_m2.val,
    s_th1.val, s_th2.val, s_g.val, s_gam.val
)

# 1. Physical Simulation Plot
R_max = s_L1.val + s_L2.val + 0.3
line_trail, = ax_sim.plot(x2, y2, color='#c084fc', lw=1.2, alpha=0.6, label='Lower Bob Trail')
line_rods, = ax_sim.plot([0, x1[-1], x2[-1]], [0, y1[-1], y2[-1]], 'o-', color='#38bdf8', lw=2.8, markersize=7, label='Pendulum Arms')
bob1_pt, = ax_sim.plot(x1[-1], y1[-1], 'o', color='#38bdf8', markersize=10)
bob2_pt, = ax_sim.plot(x2[-1], y2[-1], 'o', color='#f59e0b', markersize=12)
pivot_pt = ax_sim.plot(0, 0, 's', color='#f8fafc', markersize=6, label='Pivot (0,0)')

ax_sim.set_xlim(-R_max, R_max)
ax_sim.set_ylim(-R_max, R_max * 0.7)
ax_sim.set_aspect('equal')
ax_sim.set_title('Physical Trajectory & Real-Time Trail', fontsize=10.5, fontweight='bold', pad=6)
ax_sim.set_xlabel('Horizontal Position $x$ (m)', fontsize=9)
ax_sim.set_ylabel('Vertical Position $y$ (m)', fontsize=9)
ax_sim.grid(True, linestyle=':', alpha=0.35)
ax_sim.legend(loc='lower left', fontsize=7.5, framealpha=0.8)

# 2. Phase Space Portrait (theta2 vs omega2)
th2_wrapped = np.mod(th2 + np.pi, 2 * np.pi) - np.pi
line_phase, = ax_phase.plot(th2_wrapped, w2, color='#a855f7', lw=0.9, alpha=0.8)
ax_phase.set_title('Phase Space Portrait $(\\theta_2, \\dot{\\theta}_2)$', fontsize=10, fontweight='bold', pad=4)
ax_phase.set_xlabel(r'$\theta_2$ (rad)', fontsize=8.5)
ax_phase.set_ylabel(r'$\omega_2$ (rad/s)', fontsize=8.5)
ax_phase.set_xlim(-np.pi - 0.2, np.pi + 0.2)
ax_phase.grid(True, linestyle=':', alpha=0.3)

# 3. Energy Breakdown
line_E, = ax_energy.plot(t, E, color='#f43f5e', lw=1.8, label=r'Total $E = T + V$')
line_T, = ax_energy.plot(t, T, color='#38bdf8', lw=1.1, ls='--', alpha=0.8, label='Kinetic $T$')
line_V, = ax_energy.plot(t, V, color='#10b981', lw=1.1, ls=':', alpha=0.8, label='Potential $V$')
ax_energy.set_title(f'Energy Conservation | E0={E[0]:.2f} J, ΔE={abs(E[-1]-E[0]):.3f} J', fontsize=9.5, fontweight='bold', pad=4)
ax_energy.set_xlabel('Time $t$ (s)', fontsize=8.5)
ax_energy.set_ylabel('Energy (J)', fontsize=8.5)
ax_energy.grid(True, linestyle=':', alpha=0.3)
ax_energy.legend(loc='upper right', fontsize=7.5, framealpha=0.8)

def update(val):
    L1, L2 = s_L1.val, s_L2.val
    m1, m2 = s_m1.val, s_m2.val
    th1_0, th2_0 = s_th1.val, s_th2.val
    g, gam = s_g.val, s_gam.val

    t_new, x1_n, y1_n, x2_n, y2_n, th1_n, th2_n, w1_n, w2_n, T_n, V_n, E_n = solve_double_pendulum(
        L1, L2, m1, m2, th1_0, th2_0, g, gam
    )

    # Update physical space
    R_new = L1 + L2 + 0.3
    ax_sim.set_xlim(-R_new, R_new)
    ax_sim.set_ylim(-R_new, R_new * 0.7)
    line_trail.set_data(x2_n, y2_n)
    line_rods.set_data([0, x1_n[-1], x2_n[-1]], [0, y1_n[-1], y2_n[-1]])
    bob1_pt.set_data([x1_n[-1]], [y1_n[-1]])
    bob2_pt.set_data([x2_n[-1]], [y2_n[-1]])
    bob1_pt.set_markersize(max(6, min(18, 7 * np.sqrt(m1))))
    bob2_pt.set_markersize(max(6, min(20, 8 * np.sqrt(m2))))

    # Update phase space
    th2_w = np.mod(th2_n + np.pi, 2 * np.pi) - np.pi
    line_phase.set_data(th2_w, w2_n)
    ax_phase.relim()
    ax_phase.autoscale_view(scalex=False, scaley=True)

    # Update energy
    line_E.set_ydata(E_n)
    line_T.set_ydata(T_n)
    line_V.set_ydata(V_n)
    ax_energy.relim()
    ax_energy.autoscale_view()
    
    delE = abs(E_n[-1] - E_n[0])
    ax_energy.set_title(f'Energy Conservation | E0={E_n[0]:.2f} J, ΔE={delE:.3f} J', fontsize=9.5, fontweight='bold', pad=4)
    fig.canvas.draw_idle()

# Bind callbacks to all 8 sliders
s_L1.on_changed(update)
s_L2.on_changed(update)
s_m1.on_changed(update)
s_m2.on_changed(update)
s_th1.on_changed(update)
s_th2.on_changed(update)
s_g.on_changed(update)
s_gam.on_changed(update)

plt.show()
PYCODE;

    $algo_3_1_1 = "Double Pendulum Simulation Studio: Coupled Nonlinear Oscillations, Phase Space, Energy Conservation & Trajectory Trail";

    $theory_3_1_1 = <<<'HTML'
<div class="theory-article">
    <div style="background: rgba(56, 189, 248, 0.08); border-left: 4px solid var(--accent); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--accent); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-compass-drafting"></i> Classical Mechanics: Double Pendulum Dynamics &amp; Chaos
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            The double pendulum is the quintessential archetype of <strong>deterministic chaos in classical mechanics</strong>. While completely governed by deterministic Newton-Euler-Lagrange equations, its motion at moderate and high energies exhibits extreme sensitivity to initial conditions, complex fractal trajectories, and phase space mixing.
        </p>
    </div>

    <!-- Embedded High-Resolution Physical Schematic Diagram -->
    <div style="text-align: center; margin: 1.5rem 0;">
        <img src="{{SITEURL}}assets/images/visualization/double_pendulum/double_pendulum_schematic.png" 
             alt="Double Pendulum Coordinate Geometry, Angles, and Forces Schematic" 
             style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <div style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem; font-style: italic;">
            Figure 3.1.1: Physical schematic of the double pendulum displaying generalized coordinates $(\theta_1, \theta_2)$, Cartesian positions, gravitational loads $m_i \vec{g}$, and tangential velocities.
        </div>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Coordinate Kinematics &amp; Generalized Coordinates
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Consider two point masses $m_1$ and $m_2$ suspended by rigid, massless rods of lengths $L_1$ and $L_2$ from a fixed frictionless pivot $\mathcal{O}(0, 0)$. Defining generalized coordinates $\theta_1$ and $\theta_2$ as the counter-clockwise deflection angles relative to the downward vertical:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$x_1 = L_1 \sin\theta_1, \qquad y_1 = -L_1 \cos\theta_1$$
        $$x_2 = L_1 \sin\theta_1 + L_2 \sin\theta_2, \qquad y_2 = -L_1 \cos\theta_1 - L_2 \cos\theta_2$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Differentiating with respect to time $t$ yields the velocity components:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\dot{x}_1 = L_1 \dot{\theta}_1 \cos\theta_1, \qquad \dot{y}_1 = L_1 \dot{\theta}_1 \sin\theta_1 \implies v_1^2 = L_1^2 \dot{\theta}_1^2$$
        $$\dot{x}_2 = L_1 \dot{\theta}_1 \cos\theta_1 + L_2 \dot{\theta}_2 \cos\theta_2, \qquad \dot{y}_2 = L_1 \dot{\theta}_1 \sin\theta_1 + L_2 \dot{\theta}_2 \sin\theta_2$$
        $$v_2^2 = \dot{x}_2^2 + \dot{y}_2^2 = L_1^2 \dot{\theta}_1^2 + L_2^2 \dot{\theta}_2^2 + 2 L_1 L_2 \dot{\theta}_1 \dot{\theta}_2 \cos(\theta_1 - \theta_2)$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Kinetic &amp; Potential Energy Formulation
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The total kinetic energy $T$ of the two-particle system is:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$T = \frac{1}{2} m_1 v_1^2 + \frac{1}{2} m_2 v_2^2 = \frac{1}{2}(m_1 + m_2) L_1^2 \dot{\theta}_1^2 + \frac{1}{2} m_2 L_2^2 \dot{\theta}_2^2 + m_2 L_1 L_2 \dot{\theta}_1 \dot{\theta}_2 \cos(\theta_1 - \theta_2)$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Taking gravitational potential energy reference $V = 0$ at the downward equilibrium position ($\theta_1 = \theta_2 = 0$):
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$V(\theta_1, \theta_2) = (m_1 + m_2) g L_1 (1 - \cos\theta_1) + m_2 g L_2 (1 - \cos\theta_2)$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The Lagrangian function $\mathcal{L} = T - V$ fully defines the system's dynamics:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\mathcal{L} = \frac{1}{2}(m_1 + m_2) L_1^2 \dot{\theta}_1^2 + \frac{1}{2} m_2 L_2^2 \dot{\theta}_2^2 + m_2 L_1 L_2 \dot{\theta}_1 \dot{\theta}_2 \cos(\theta_1 - \theta_2) - (m_1 + m_2) g L_1 (1 - \cos\theta_1) - m_2 g L_2 (1 - \cos\theta_2)$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Euler-Lagrange Equations of Motion
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Applying Hamilton's principle of stationary action $\frac{d}{dt}\left(\frac{\partial \mathcal{L}}{\partial \dot{\theta}_i}\right) - \frac{\partial \mathcal{L}}{\partial \theta_i} = -\gamma \dot{\theta}_i$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$(m_1 + m_2) L_1 \ddot{\theta}_1 + m_2 L_2 \ddot{\theta}_2 \cos(\theta_1 - \theta_2) = -m_2 L_2 \dot{\theta}_2^2 \sin(\theta_1 - \theta_2) - (m_1 + m_2) g \sin\theta_1 - \gamma \dot{\theta}_1$$
        $$m_2 L_1 \ddot{\theta}_1 \cos(\theta_1 - \theta_2) + m_2 L_2 \ddot{\theta}_2 = m_2 L_1 \dot{\theta}_1^2 \sin(\theta_1 - \theta_2) - m_2 g \sin\theta_2 - \gamma \dot{\theta}_2$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Letting $\Delta = \theta_1 - \theta_2$, we rewrite the system in compact matrix form $\mathbf{M}(\vec{\theta}) \ddot{\vec{\theta}} = \vec{R}(\vec{\theta}, \dot{\vec{\theta}})$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\begin{pmatrix} (m_1 + m_2) L_1 & m_2 L_2 \cos\Delta \\ m_2 L_1 \cos\Delta & m_2 L_2 \end{pmatrix} \begin{pmatrix} \ddot{\theta}_1 \\ \ddot{\theta}_2 \end{pmatrix} = \begin{pmatrix} R_1 \\ R_2 \end{pmatrix}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The determinant of the mass matrix is strictly positive for all physical parameters:
        $$\det \mathbf{M} = m_2 L_1 L_2 \left[ (m_1 + m_2) - m_2 \cos^2\Delta \right] = m_2 L_1 L_2 \left[ m_1 + m_2 \sin^2\Delta \right] > 0$$
        By matrix inversion (Cramer's rule), the angular accelerations $\ddot{\theta}_1, \ddot{\theta}_2$ are computed unconditionally without singularities:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\ddot{\theta}_1 = \frac{R_1 m_2 L_2 - R_2 m_2 L_2 \cos\Delta}{\det \mathbf{M}}, \qquad \ddot{\theta}_2 = \frac{R_2 (m_1 + m_2) L_1 - R_1 m_2 L_1 \cos\Delta}{\det \mathbf{M}}$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        4. Numerical Runge-Kutta 4 (RK4) Integration &amp; Conservation of Energy
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The 2nd-order coupled ODEs are transformed into four 1st-order state equations for state vector $\vec{Y} = (\theta_1, \theta_2, \omega_1, \omega_2)^T$. The Runge-Kutta 4th Order (RK4) integration algorithm updates the state across time step $\Delta t$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\vec{Y}_{n+1} = \vec{Y}_n + \frac{\Delta t}{6} \left( \vec{k}_1 + 2\vec{k}_2 + 2\vec{k}_3 + \vec{k}_4 \right)$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        In the absence of damping ($\gamma = 0$), the total mechanical energy $E = T + V$ is a rigorous constant of motion ($dE/dt = 0$). The live simulation displays the energy breakdown and monitors energy conservation precision ($|\Delta E / E_0| < 0.05\%$).
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        5. Student-Friendly Parameter Tuning Guide
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Use the live sliders below the workbench to explore exciting physics regimes:
        <br>&bull; <strong>Small Angles ($|\theta_{1,2}| < 20^\circ$):</strong> Observe smooth, predictable, periodic/quasi-periodic normal mode oscillations.
        <br>&bull; <strong>High Angles ($|\theta_{1,2}| > 90^\circ$):</strong> Witness the onset of deterministic chaos! The lower bob flips unpredictably over the top.
        <br>&bull; <strong>Mass Dominance ($m_1 \gg m_2$ vs $m_1 \ll m_2$):</strong> When $m_1 \gg m_2$, the upper pendulum behaves almost as a simple pendulum driving the chaotic whip of $m_2$.
        <br>&bull; <strong>Zero-Gravity ($g = 0$):</strong> Observe pure conservation of angular momentum and inertial tumbling!
        <br>&bull; <strong>Damping ($\gamma > 0$):</strong> Watch the chaotic strange attractor collapse into the stable downward equilibrium sink $(0, 0)$.
    </p>
</div>
HTML;

    $stmtProg1 = $conn->prepare("INSERT INTO `visualization` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) 
                                 VALUES (3, 1, 1, :content, :algo, :explanation)");
    $stmtProg1->execute([
        ':content'     => $code_3_1_1,
        ':algo'        => $algo_3_1_1,
        ':explanation' => $theory_3_1_1
    ]);
    echo "[OK] Inserted Program 3.1.1 (Interactive Parameter Studio)\n";

    // =========================================================================
    // PROGRAM 3.2.1: Deterministic Chaos & Sensitivity to Initial Conditions
    // =========================================================================
    $code_3_2_1 = <<<'PYCODE'
"""
Deterministic Chaos & The Butterfly Effect in Double Pendulums
Sensitivity to Initial Conditions & Lyapunov Divergence
Powered by Pyodide with Matplotlib Interactive Sliders
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider

# Create 2-panel figure
fig = plt.figure(figsize=(10.5, 5.8))
gs = fig.add_gridspec(1, 2, width_ratios=[1.1, 1.0], wspace=0.28)
ax_orbit = fig.add_subplot(gs[0, 0])
ax_lyap  = fig.add_subplot(gs[0, 1])
plt.subplots_adjust(bottom=0.32, top=0.90, left=0.09, right=0.94)

# Sliders: 2 columns of 3 sliders
ax_th1   = plt.axes([0.12, 0.18, 0.34, 0.024])
ax_th2   = plt.axes([0.12, 0.10, 0.34, 0.024])
ax_delta = plt.axes([0.12, 0.02, 0.34, 0.024])

ax_tmax  = plt.axes([0.58, 0.18, 0.34, 0.024])
ax_L1    = plt.axes([0.58, 0.10, 0.34, 0.024])
ax_m2m1  = plt.axes([0.58, 0.02, 0.34, 0.024])

s_th1   = Slider(ax_th1, 'θ1 (deg)', 10.0, 175.0, valinit=90.0, valstep=2.0)
s_th2   = Slider(ax_th2, 'θ2 (deg)', 10.0, 175.0, valinit=90.0, valstep=2.0)
s_delta = Slider(ax_delta, 'Δθ2 (rad)', 0.0001, 0.02, valinit=0.001, valstep=0.0002)

s_tmax  = Slider(ax_tmax, 'Time (s)', 5.0, 25.0, valinit=12.0, valstep=1.0)
s_L1    = Slider(ax_L1, 'L1=L2 (m)', 0.5, 2.0, valinit=1.0, valstep=0.05)
s_m2m1  = Slider(ax_m2m1, 'm2/m1 Ratio', 0.1, 3.0, valinit=1.0, valstep=0.1)

def run_dual_simulation(th1_deg, th2_deg, delta_rad, t_max, L, m_ratio, g=9.81, steps=900):
    t = np.linspace(0, t_max, steps)
    dt = t[1] - t[0]
    L1, L2 = L, L
    m1, m2 = 1.0, m_ratio

    def rk4_integrate(th1_init, th2_init):
        y = np.zeros((steps, 4))
        y[0] = [np.radians(th1_init), np.radians(th2_init), 0.0, 0.0]

        def deriv(s):
            th1, th2, w1, w2 = s
            dth = th1 - th2
            m11 = (m1 + m2) * L1
            m12 = m2 * L2 * np.cos(dth)
            m21 = m2 * L1 * np.cos(dth)
            m22 = m2 * L2

            r1 = -m2 * L2 * w2**2 * np.sin(dth) - (m1 + m2) * g * np.sin(th1)
            r2 = m2 * L1 * w1**2 * np.sin(dth) - m2 * g * np.sin(th2)

            det = m11 * m22 - m12 * m21
            a1 = (r1 * m22 - m12 * r2) / det
            a2 = (m11 * r2 - r1 * m21) / det
            return np.array([w1, w2, a1, a2])

        for i in range(steps - 1):
            s = y[i]
            k1 = deriv(s)
            k2 = deriv(s + 0.5 * dt * k1)
            k3 = deriv(s + 0.5 * dt * k2)
            k4 = deriv(s + dt * k3)
            y[i + 1] = s + (dt / 6.0) * (k1 + 2*k2 + 2*k3 + k4)

        x1 = L1 * np.sin(y[:, 0])
        y1 = -L1 * np.cos(y[:, 0])
        x2 = x1 + L2 * np.sin(y[:, 1])
        y2 = y1 - L2 * np.cos(y[:, 1])
        return x1, y1, x2, y2

    # Pendulum A
    x1A, y1A, x2A, y2A = rk4_integrate(th1_deg, th2_deg)
    # Pendulum B (perturbed by delta_rad in theta2)
    th2_B_deg = th2_deg + np.degrees(delta_rad)
    x1B, y1B, x2B, y2B = rk4_integrate(th1_deg, th2_B_deg)

    # Separation distance
    delta_r = np.sqrt((x2A - x2B)**2 + (y2A - y2B)**2)
    delta_r = np.maximum(delta_r, 1e-7)

    return t, x1A, y1A, x2A, y2A, x1B, y1B, x2B, y2B, delta_r

# Initial computation
t, x1A, y1A, x2A, y2A, x1B, y1B, x2B, y2B, delta_r = run_dual_simulation(
    s_th1.val, s_th2.val, s_delta.val, s_tmax.val, s_L1.val, s_m2m1.val
)

# 1. Trajectory Overlay Plot
R = 2.0 * s_L1.val + 0.3
trailA, = ax_orbit.plot(x2A, y2A, color='#38bdf8', lw=1.2, alpha=0.75, label=r'Pendulum A $(\theta_0)$')
trailB, = ax_orbit.plot(x2B, y2B, color='#f59e0b', lw=1.2, alpha=0.75, label=r'Pendulum B $(\theta_0 + \Delta\theta)$')
bobA,   = ax_orbit.plot(x2A[-1], y2A[-1], 'o', color='#38bdf8', markersize=9)
bobB,   = ax_orbit.plot(x2B[-1], y2B[-1], 'o', color='#f59e0b', markersize=9)
pivot_pt = ax_orbit.plot(0, 0, 's', color='#f8fafc', markersize=6)

ax_orbit.set_xlim(-R, R)
ax_orbit.set_ylim(-R, R * 0.7)
ax_orbit.set_aspect('equal')
ax_orbit.set_title('Side-by-Side Dual Pendulum Orbits', fontsize=10.5, fontweight='bold', pad=6)
ax_orbit.set_xlabel('Lower Bob $x$ (m)', fontsize=9)
ax_orbit.set_ylabel('Lower Bob $y$ (m)', fontsize=9)
ax_orbit.grid(True, linestyle=':', alpha=0.35)
ax_orbit.legend(loc='lower left', fontsize=7.5, framealpha=0.85)

# 2. Lyapunov Divergence Plot (Semi-Log)
line_lyap, = ax_lyap.semilogy(t, delta_r, color='#f43f5e', lw=2.0, label=r'$\Delta r(t) = \|\vec{r}_A - \vec{r}_B\|$')
line_bound = ax_lyap.axhline(4.0 * s_L1.val, color='#94a3b8', ls='--', lw=1.2, label=r'Max System Span $2(L_1+L_2)$')

ax_lyap.set_title(r'Lyapunov Exponential Divergence $\Delta r \sim e^{\lambda t}$', fontsize=10.5, fontweight='bold', pad=6)
ax_lyap.set_xlabel('Time $t$ (seconds)', fontsize=9)
ax_lyap.set_ylabel(r'Separation Distance $\Delta r$ (m)', fontsize=9)
ax_lyap.grid(True, linestyle=':', alpha=0.35)
ax_lyap.legend(loc='lower right', fontsize=7.5, framealpha=0.85)

def update(val):
    th1_0 = s_th1.val
    th2_0 = s_th2.val
    delta = s_delta.val
    t_max = s_tmax.val
    L = s_L1.val
    m_ratio = s_m2m1.val

    t_n, x1A_n, y1A_n, x2A_n, y2A_n, x1B_n, y1B_n, x2B_n, y2B_n, dr_n = run_dual_simulation(
        th1_0, th2_0, delta, t_max, L, m_ratio
    )

    R_n = 2.0 * L + 0.3
    ax_orbit.set_xlim(-R_n, R_n)
    ax_orbit.set_ylim(-R_n, R_n * 0.7)

    trailA.set_data(x2A_n, y2A_n)
    trailB.set_data(x2B_n, y2B_n)
    bobA.set_data([x2A_n[-1]], [y2A_n[-1]])
    bobB.set_data([x2B_n[-1]], [y2B_n[-1]])

    line_lyap.set_data(t_n, dr_n)
    line_bound.set_ydata([4.0 * L, 4.0 * L])
    ax_lyap.set_xlim(0, t_max)
    ax_lyap.relim()
    ax_lyap.autoscale_view(scalex=False, scaley=True)

    fig.canvas.draw_idle()

s_th1.on_changed(update)
s_th2.on_changed(update)
s_delta.on_changed(update)
s_tmax.on_changed(update)
s_L1.on_changed(update)
s_m2m1.on_changed(update)

plt.show()
PYCODE;

    $algo_3_2_1 = "Deterministic Chaos, Butterfly Effect & Lyapunov Divergence in Dual Pendulums";

    $theory_3_2_1 = <<<'HTML'
<div class="theory-article">
    <div style="background: rgba(168, 85, 247, 0.08); border-left: 4px solid var(--accent-purple, #a855f7); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: #a855f7; margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-shuffle"></i> Deterministic Chaos &amp; The Butterfly Effect
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            The concept of deterministic chaos implies that a physical system governed by completely exact, deterministic equations of motion without any stochastic randomness can nonetheless produce <strong>fundamentally unpredictable long-term trajectories</strong> due to exponential sensitivity to initial conditions.
        </p>
    </div>

    <!-- Embedded High-Resolution Chaos Diagram -->
    <div style="text-align: center; margin: 1.5rem 0;">
        <img src="{{SITEURL}}assets/images/visualization/double_pendulum/double_pendulum_chaos_concept.png" 
             alt="Deterministic Chaos and Lyapunov Divergence in Double Pendulums" 
             style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <div style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem; font-style: italic;">
            Figure 3.2.1: Demonstration of chaotic divergence: two identical double pendulums released with an initial discrepancy $\Delta\theta_0 = 10^{-3}$ rad stay aligned during the initial predictability window before exponentially flying apart ($\Delta r(t) \sim e^{\lambda t}$).
        </div>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. The Maximal Lyapunov Exponent ($\lambda$)
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Consider two initial states in 4D phase space $\vec{X}_A(0)$ and $\vec{X}_B(0)$ separated by an infinitesimal Euclidean perturbation $\|\Delta \vec{X}(0)\| = \delta_0 \ll 1$. In a chaotic dynamical system, the separation distance evolves exponentially in time:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\|\Delta \vec{X}(t)\| \approx \delta_0 \, e^{\lambda t}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The <em>maximal Lyapunov exponent</em> $\lambda$ is formally defined by the asymptotic limit:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\lambda = \lim_{t \to \infty} \lim_{\delta_0 \to 0} \frac{1}{t} \ln \left( \frac{\|\Delta \vec{X}(t)\|}{\delta_0} \right)$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        &bull; <strong>$\lambda < 0$:</strong> Regular dissipative system converging to a stable fixed-point attractor.<br>
        &bull; <strong>$\lambda = 0$:</strong> Conservative, integrable periodic/quasi-periodic orbits (e.g. harmonic oscillators or planetary orbits without resonances).<br>
        &bull; <strong>$\lambda > 0$:</strong> The system is <strong>chaotic</strong>. Adjacent trajectories diverge exponentially, destroying deterministic long-range forecastability!
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. The Predictability Horizon &amp; Geometric Saturation
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Because physical pendulums are mechanically bounded to a maximum possible distance $D_{\text{max}} = 2(L_1 + L_2)$, the exponential growth $\Delta r(t) \sim e^{\lambda t}$ cannot continue infinitely. It reaches a <em>saturation boundary</em> when the two bobs become as separated as physically possible in space:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$t_{\text{horizon}} \approx \frac{1}{\lambda} \ln\left( \frac{D_{\text{max}}}{\Delta r_0} \right)$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Notice the logarithmic dependence: even if you improve your measurement precision by a factor of $1,000$ ($\Delta r_0 \to 10^{-3} \Delta r_0$), your forecast horizon $t_{\text{horizon}}$ only increases additively by $\frac{\ln(1000)}{\lambda} \approx \frac{6.9}{\lambda}$ seconds! This mathematical fact is why weather forecasting beyond two weeks is fundamentally impossible, as discovered by Edward Lorenz in 1963.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Energy Threshold: Transition from Integrability to Chaos
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The double pendulum does not exhibit chaos at all energies:
        <br>&bull; <strong>Low Energy Regime ($E < m_2 g L_1$):</strong> The potential well confines both angles to small amplitudes. KAM (Kolmogorov-Arnold-Moser) tori dominate phase space, and trajectories remain regular and quasi-periodic.
        <br>&bull; <strong>Critical Transition ($E \approx m_2 g L_1$):</strong> The lower pendulum has sufficient energy to reach the inverted saddle point $\theta_2 = \pi$. Separatrix crossing creates homoclinic chaos.
        <br>&bull; <strong>Fully Chaotic Regime ($E \gg m_2 g L_1$):</strong> KAM tori are shattered into a sea of chaos, and the phase space portrait exhibits ergodicity and dense orbital mixing.
    </p>
</div>
HTML;

    $stmtProg2 = $conn->prepare("INSERT INTO `visualization` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) 
                                 VALUES (3, 2, 1, :content, :algo, :explanation)");
    $stmtProg2->execute([
        ':content'     => $code_3_2_1,
        ':algo'        => $algo_3_2_1,
        ':explanation' => $theory_3_2_1
    ]);
    echo "[OK] Inserted Program 3.2.1 (Chaos & Butterfly Divergence)\n";

    // =========================================================================
    // PROGRAM 3.3.1: Small-Angle Normal Modes & Harmonic Resonance
    // =========================================================================
    $code_3_3_1 = <<<'PYCODE'
"""
Coupled Harmonic Normal Modes, Eigenfrequencies & Beat Superposition
Small-Angle Linear Approximation of the Double Pendulum
Powered by Pyodide with Matplotlib Interactive Sliders
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider

# Create 2-panel figure
fig = plt.figure(figsize=(10.5, 5.8))
gs = fig.add_gridspec(1, 2, width_ratios=[1.0, 1.2], wspace=0.28)
ax_pend = fig.add_subplot(gs[0, 0])
ax_beats = fig.add_subplot(gs[0, 1])
plt.subplots_adjust(bottom=0.30, top=0.90, left=0.09, right=0.94)

# Sliders
ax_mratio = plt.axes([0.15, 0.16, 0.32, 0.024])
ax_lratio = plt.axes([0.15, 0.06, 0.32, 0.024])
ax_mode   = plt.axes([0.62, 0.16, 0.32, 0.024])
ax_amp    = plt.axes([0.62, 0.06, 0.32, 0.024])

s_mratio = Slider(ax_mratio, 'm2/m1 Ratio', 0.1, 4.0, valinit=1.0, valstep=0.1)
s_lratio = Slider(ax_lratio, 'L2/L1 Ratio', 0.2, 3.0, valinit=1.0, valstep=0.05)
s_mode   = Slider(ax_mode, 'Mode Mixture', -1.0, 1.0, valinit=0.0, valstep=0.05)
s_amp    = Slider(ax_amp, 'Amp θ0 (deg)', 1.0, 25.0, valinit=8.0, valstep=1.0)

def compute_normal_modes(mu, eta, mode_mix, theta0_deg, g=9.81, L1=1.0, t_max=18.0, steps=800):
    """
    mu  = m2 / m1
    eta = L2 / L1
    """
    th0 = np.radians(theta0_deg)
    L2 = eta * L1
    
    # Characteristic quadratic equation for omega^2:
    # det([ (1+mu)*L1*w^2 - (1+mu)*g,   mu*L2*w^2 ]
    #     [ L1*w^2,                    L2*w^2 - g ]) = 0
    #
    # (1+mu)*eta*(w^2)^2 - (1+mu)*(1+eta)*(g/L1)*w^2 + (1+mu)*(g/L1)^2 = 0
    # Divide by (1+mu): eta * W^2 - (1+eta)*W + 1 = 0  where W = w^2 * (L1/g)
    # Roots: W = [ (1+eta) +/- sqrt( (1+eta)^2 - 4*eta*(1 - ... ) ) ]
    
    A = eta
    B = -(1.0 + mu) * (1.0 + eta) * (g / L1)
    # Mass matrix M and Stiffness matrix K
    # M = [[ (1+mu)*L1^2, mu*L1*L2 ], [ mu*L1*L2, mu*L2^2 ]]
    # K = [[ (1+mu)*g*L1, 0        ], [ 0,         mu*g*L2 ]]
    M11 = (1.0 + mu) * L1**2
    M12 = mu * L1 * L2
    M22 = mu * L2**2
    K11 = (1.0 + mu) * g * L1
    K22 = mu * g * L2

    # Solve generalized eigenvalues K v = w^2 M v
    # (K11 - w^2 M11)(K22 - w^2 M22) - (w^2 M12)^2 = 0
    # (M11*M22 - M12^2) (w^2)^2 - (K11*M22 + K22*M11) w^2 + K11*K22 = 0
    detM = M11 * M22 - M12**2
    trace_KM = K11 * M22 + K22 * M11
    detK = K11 * K22

    disc = np.sqrt(max(0.0, trace_KM**2 - 4.0 * detM * detK))
    w1_sq = (trace_KM - disc) / (2.0 * detM)  # Lower frequency (In-phase)
    w2_sq = (trace_KM + disc) / (2.0 * detM)  # Higher frequency (Out-of-phase)

    w1 = np.sqrt(max(0.01, w1_sq))
    w2 = np.sqrt(max(0.01, w2_sq))

    # Eigenvector ratios r = theta2 / theta1
    # From 1st row: (K11 - w^2 M11) - w^2 M12 * r = 0  => r = (K11 - w^2 M11) / (w^2 M12)
    r1 = (K11 - w1_sq * M11) / (w1_sq * M12) # In-phase: r1 > 0
    r2 = (K11 - w2_sq * M11) / (w2_sq * M12) # Out-of-phase: r2 < 0

    # Mode weights: mode_mix in [-1, 1]
    # +1 -> 100% Mode 1, -1 -> 100% Mode 2, 0 -> 50% Mode 1 + 50% Mode 2 (Beats)
    w_mode1 = 0.5 * (1.0 + mode_mix)
    w_mode2 = 0.5 * (1.0 - mode_mix)

    t = np.linspace(0, t_max, steps)
    c1 = th0 * w_mode1
    c2 = th0 * w_mode2

    th1_t = c1 * np.cos(w1 * t) + c2 * np.cos(w2 * t)
    th2_t = c1 * r1 * np.cos(w1 * t) + c2 * r2 * np.cos(w2 * t)

    # Positions at t=0
    th1_now, th2_now = th1_t[0], th2_t[0]
    x1 = L1 * np.sin(th1_now); y1 = -L1 * np.cos(th1_now)
    x2 = x1 + L2 * np.sin(th2_now); y2 = y1 - L2 * np.cos(th2_now)

    return t, th1_t, th2_t, w1, w2, r1, r2, x1, y1, x2, y2, L1, L2

t, th1_t, th2_t, w1, w2, r1, r2, x1, y1, x2, y2, L1, L2 = compute_normal_modes(
    s_mratio.val, s_lratio.val, s_mode.val, s_amp.val
)

# 1. Pendulum Display
line_arm, = ax_pend.plot([0, x1, x2], [0, y1, y2], 'o-', color='#10b981', lw=3, markersize=8)
bob1, = ax_pend.plot(x1, y1, 'o', color='#38bdf8', markersize=10)
bob2, = ax_pend.plot(x2, y2, 'o', color='#f59e0b', markersize=12)
ax_pend.plot(0, 0, 's', color='#f8fafc', markersize=6)
ax_pend.plot([0, 0], [0, -(L1 + L2 + 0.2)], color='#64748b', ls=':', lw=1.2)

R = L1 + L2 + 0.3
ax_pend.set_xlim(-R * 0.7, R * 0.7)
ax_pend.set_ylim(-R, 0.3)
ax_pend.set_aspect('equal')
ax_pend.set_title('Small-Angle Normal Mode Shape', fontsize=10.5, fontweight='bold', pad=6)
ax_pend.grid(True, linestyle=':', alpha=0.35)

# 2. Beat Superposition History
line_th1, = ax_beats.plot(t, np.degrees(th1_t), color='#38bdf8', lw=1.8, label=r'$\theta_1(t)$ Upper')
line_th2, = ax_beats.plot(t, np.degrees(th2_t), color='#f59e0b', lw=1.4, ls='--', label=r'$\theta_2(t)$ Lower')

# Beat envelope if both modes are active
if abs(s_mode.val) < 0.9:
    w_beat = abs(w2 - w1)
    env = np.degrees(np.radians(s_amp.val) * np.cos(0.5 * w_beat * t))
    line_env1, = ax_beats.plot(t, env, color='#94a3b8', ls=':', lw=1.2, alpha=0.7, label='Beat Envelope')
    line_env2, = ax_beats.plot(t, -env, color='#94a3b8', ls=':', lw=1.2, alpha=0.7)
else:
    line_env1, = ax_beats.plot(t, np.zeros_like(t), color='none')
    line_env2, = ax_beats.plot(t, np.zeros_like(t), color='none')

ax_beats.set_title(f'Harmonic Beats | ω1={w1:.2f} rad/s, ω2={w2:.2f} rad/s', fontsize=10.5, fontweight='bold', pad=6)
ax_beats.set_xlabel('Time $t$ (seconds)', fontsize=9)
ax_beats.set_ylabel('Deflection Angle (degrees)', fontsize=9)
ax_beats.grid(True, linestyle=':', alpha=0.35)
ax_beats.legend(loc='upper right', fontsize=7.5, framealpha=0.85)

def update(val):
    mu = s_mratio.val
    eta = s_lratio.val
    mix = s_mode.val
    amp = s_amp.val

    t_n, th1_n, th2_n, w1_n, w2_n, r1_n, r2_n, x1_n, y1_n, x2_n, y2_n, L1_n, L2_n = compute_normal_modes(
        mu, eta, mix, amp
    )

    line_arm.set_data([0, x1_n, x2_n], [0, y1_n, y2_n])
    bob1.set_data([x1_n], [y1_n])
    bob2.set_data([x2_n], [y2_n])

    line_th1.set_ydata(np.degrees(th1_n))
    line_th2.set_ydata(np.degrees(th2_n))

    if abs(mix) < 0.9:
        w_beat = abs(w2_n - w1_n)
        env_n = np.degrees(np.radians(amp) * np.cos(0.5 * w_beat * t_n))
        line_env1.set_data(t_n, env_n)
        line_env2.set_data(t_n, -env_n)
        line_env1.set_color('#94a3b8')
        line_env2.set_color('#94a3b8')
    else:
        line_env1.set_color('none')
        line_env2.set_color('none')

    ax_beats.relim()
    ax_beats.autoscale_view(scalex=False, scaley=True)
    ax_beats.set_title(f'Harmonic Beats | ω1={w1_n:.2f} rad/s (r1={r1_n:+.2f}), ω2={w2_n:.2f} rad/s (r2={r2_n:+.2f})', fontsize=10.0, fontweight='bold', pad=6)

    fig.canvas.draw_idle()

s_mratio.on_changed(update)
s_lratio.on_changed(update)
s_mode.on_changed(update)
s_amp.on_changed(update)

plt.show()
PYCODE;

    $algo_3_3_1 = "Coupled Harmonic Normal Modes, Eigenfrequencies & Beat Superposition";

    $theory_3_3_1 = <<<'HTML'
<div class="theory-article">
    <div style="background: rgba(16, 185, 129, 0.08); border-left: 4px solid var(--accent-emerald, #10b981); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: #10b981; margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-wave-square"></i> Small-Angle Normal Modes &amp; Resonance
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            Before chaos emerges at large deflections, the small-angle regime ($|\theta_1|, |\theta_2| \ll 1$) reveals pure, elegant linear physics. The complex coupled system decouples into <strong>two independent normal modes of vibration</strong> with distinct characteristic frequencies $\omega_1$ and $\omega_2$.
        </p>
    </div>

    <!-- Embedded High-Resolution Normal Modes Diagram -->
    <div style="text-align: center; margin: 1.5rem 0;">
        <img src="{{SITEURL}}assets/images/visualization/double_pendulum/double_pendulum_normal_modes.png" 
             alt="Small-Angle Normal Modes and Beat Phenomenon in Double Pendulum" 
             style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <div style="font-size: 0.85rem; color: var(--text-dim); margin-top: 0.5rem; font-style: italic;">
            Figure 3.3.1: Small-angle normal modes: Mode 1 (In-Phase symmetric slow swing), Mode 2 (Out-of-Phase anti-symmetric fast flutter), and beat oscillation superposition.
        </div>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Linearization of the Equations of Motion
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        For small angles $\theta_1, \theta_2 \ll 1$, we apply the Taylor approximations:
        $$\sin\theta \approx \theta, \qquad \cos(\theta_1 - \theta_2) \approx 1, \qquad \dot{\theta}_i^2 \approx 0$$
        The nonlinear equations linearize into the classic coupled harmonic oscillator system:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$(m_1 + m_2) L_1 \ddot{\theta}_1 + m_2 L_2 \ddot{\theta}_2 + (m_1 + m_2) g \theta_1 = 0$$
        $$m_2 L_1 \ddot{\theta}_1 + m_2 L_2 \ddot{\theta}_2 + m_2 g \theta_2 = 0$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Generalized Matrix Eigenvalue Problem
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Assuming harmonic normal mode solutions $\theta_j(t) = A_j e^{i\omega t}$, we obtain the generalized eigenvalue problem:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\left[ \mathbf{K} - \omega^2 \mathbf{M} \right] \vec{A} = \mathbf{0}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        where the symmetric mass and stiffness matrices are:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\mathbf{M} = \begin{pmatrix} (m_1 + m_2) L_1^2 & m_2 L_1 L_2 \\ m_2 L_1 L_2 & m_2 L_2^2 \end{pmatrix}, \qquad \mathbf{K} = \begin{pmatrix} (m_1 + m_2) g L_1 & 0 \\ 0 & m_2 g L_2 \end{pmatrix}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        For equal masses ($m_1 = m_2 = m$) and equal lengths ($L_1 = L_2 = L$), the secular equation $\det(\mathbf{K} - \omega^2 \mathbf{M}) = 0$ simplifies to:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\omega^4 - 4\frac{g}{L} \omega^2 + 2\left(\frac{g}{L}\right)^2 = 0$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Solving the bi-quadratic equation gives the exact normal frequencies:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\omega_1 = \sqrt{\frac{g}{L}(2 - \sqrt{2})} \approx 0.765 \sqrt{\frac{g}{L}}, \qquad \omega_2 = \sqrt{\frac{g}{L}(2 + \sqrt{2})} \approx 1.848 \sqrt{\frac{g}{L}}$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Physical Interpretation of Normal Modes
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        &bull; <strong>Mode 1 (Low Frequency $\omega_1$):</strong> The eigenvector ratio is $r_1 = \frac{\theta_2}{\theta_1} = +\sqrt{2} \approx +1.414$. Both rods swing <em>in-phase</em> in the same direction, resembling a flexible extended pendulum.<br>
        &bull; <strong>Mode 2 (High Frequency $\omega_2$):</strong> The eigenvector ratio is $r_2 = \frac{\theta_2}{\theta_1} = -\sqrt{2} \approx -1.414$. The two rods swing <em>out-of-phase</em> in opposite directions, rapidly counter-balancing each other.<br>
        &bull; <strong>Beats &amp; Energy Transfer:</strong> In general superposition, energy oscillates back and forth between rod 1 and rod 2 at the beat frequency $\Delta\omega = \omega_2 - \omega_1$, creating envelope modulation!
    </p>
</div>
HTML;

    $stmtProg3 = $conn->prepare("INSERT INTO `visualization` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) 
                                 VALUES (3, 3, 1, :content, :algo, :explanation)");
    $stmtProg3->execute([
        ':content'     => $code_3_3_1,
        ':algo'        => $algo_3_3_1,
        ':explanation' => $theory_3_3_1
    ]);
    echo "[OK] Inserted Program 3.3.1 (Normal Modes & Harmonic Beats)\n";

    // 5. Synchronize menus to disk file: program/visualization/menu.php
    sync_menus_to_file('visualization', $conn);
    echo "[OK] Synced menus to program/visualization/menu.php\n";

    echo "\n=== Module 3 Seeding Completed Successfully! ===\n";

} catch (Throwable $e) {
    echo "ERROR during seeding: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
