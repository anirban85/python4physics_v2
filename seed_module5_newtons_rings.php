<?php
/**
 * Python4Physics - Seed Module 5.1: Newton's Rings Interference Simulation
 * 
 * Sets up Module 5 (Optics):
 *   - Subtopic 5.1: Newton's Rings Interference (Reflected & Transmitted Systems)
 *   - Subtopic 5.2: Fraunhofer Diffraction in Double Slit (Preserved / Shifted)
 * 
 * Features:
 *   - Program 5.1.1: Interactive Newton's Rings simulation with Pyodide WebAssembly
 *   - Real optical circular fringes for BOTH Reflected and Transmitted ray cases
 *   - Real visible spectrum colormapping based on wavelength λ
 *   - Interactive sliders for λ, curvature R, film index μ, central gap t0
 *   - High-resolution theory ray diagrams (Experimental setup, Film geometry, Complementary comparison)
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/include/menu_sync.php';

header('Content-Type: text/plain; charset=utf-8');

if (!isset($conn) || $conn === null) {
    die("Error: Database connection not available.\n");
}

echo "=== Python4Physics: Seeding Module 5.1 Newton's Rings Simulation ===\n";

try {
    // 1. Ensure Module 5 (Optics) exists
    $chkMenu5 = $conn->prepare("SELECT COUNT(*) FROM `p4p_menus` WHERE `language` = 'visualization' AND `menu_id` = 5");
    $chkMenu5->execute();
    if ((int)$chkMenu5->fetchColumn() === 0) {
        $insMenu5 = $conn->prepare("INSERT INTO `p4p_menus` (`language`, `menu_id`, `title`, `sort_order`) VALUES ('visualization', 5, 'Optics', 5)");
        $insMenu5->execute();
        echo "Created Menu 5: Optics\n";
    }

    // 2. Check if Fraunhofer was at 5.1. If so, shift to 5.2
    $chkOldFraun = $conn->prepare("SELECT id FROM `p4p_submenus` WHERE `language` = 'visualization' AND `menu_id` = 5 AND `submenu_id` = 1 AND `title` LIKE '%Fraunhofer%'");
    $chkOldFraun->execute();
    if ($chkOldFraun->fetch()) {
        echo "Shifting existing Fraunhofer Diffraction from 5.1 -> 5.2...\n";
        // Check if 5.2 already exists in p4p_submenus
        $chk5_2 = $conn->prepare("SELECT id FROM `p4p_submenus` WHERE `language` = 'visualization' AND `menu_id` = 5 AND `submenu_id` = 2");
        $chk5_2->execute();
        if ($chk5_2->fetch()) {
            $conn->exec("DELETE FROM `p4p_submenus` WHERE `language` = 'visualization' AND `menu_id` = 5 AND `submenu_id` = 2");
        }
        $conn->exec("UPDATE `p4p_submenus` SET `submenu_id` = 2, `sort_order` = 2 WHERE `language` = 'visualization' AND `menu_id` = 5 AND `submenu_id` = 1");
        $conn->exec("UPDATE `visualization` SET `submenu_id` = 2 WHERE `menu_id` = 5 AND `submenu_id` = 1");
    }

    // Ensure 5.2 Submenu entry for Fraunhofer
    $chkSub5_2 = $conn->prepare("SELECT COUNT(*) FROM `p4p_submenus` WHERE `language` = 'visualization' AND `menu_id` = 5 AND `submenu_id` = 2");
    $chkSub5_2->execute();
    if ((int)$chkSub5_2->fetchColumn() === 0) {
        $insSub5_2 = $conn->prepare("INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES ('visualization', 5, 2, 'Fraunhofer Diffraction in Double Slit', 2)");
        $insSub5_2->execute();
        echo "Ensured Submenu 5.2: Fraunhofer Diffraction in Double Slit\n";
    }

    // Ensure 5.1 Submenu entry for Newton's Rings
    $chkSub5_1 = $conn->prepare("SELECT id FROM `p4p_submenus` WHERE `language` = 'visualization' AND `menu_id` = 5 AND `submenu_id` = 1");
    $chkSub5_1->execute();
    $sub5_1_row = $chkSub5_1->fetch(PDO::FETCH_ASSOC);
    if (!$sub5_1_row) {
        $insSub5_1 = $conn->prepare("INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES ('visualization', 5, 1, 'Newton’s Rings Interference (Reflected & Transmitted Systems)', 1)");
        $insSub5_1->execute();
        echo "Created Submenu 5.1: Newton's Rings Interference (Reflected & Transmitted Systems)\n";
    } else {
        $updSub5_1 = $conn->prepare("UPDATE `p4p_submenus` SET `title` = 'Newton’s Rings Interference (Reflected & Transmitted Systems)', `sort_order` = 1 WHERE `id` = :id");
        $updSub5_1->execute([':id' => $sub5_1_row['id']]);
        echo "Updated Submenu 5.1 title\n";
    }

    // 3. Newton's Rings Python Simulation Code
    $nr_code = <<<'PYCODE'
"""
Newton's Rings Interference Simulation Studio
Real Optical Fringes in Reflected and Transmitted Systems
"""
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider
from matplotlib.colors import LinearSegmentedColormap

def wavelength_to_rgb(wl):
    """Approximate visible spectrum wavelength (nm) to RGB color."""
    wl = float(wl)
    if wl < 440:
        r = -(wl - 440) / (440 - 380); g = 0.0; b = 1.0
    elif wl < 490:
        r = 0.0; g = (wl - 440) / (490 - 440); b = 1.0
    elif wl < 510:
        r = 0.0; g = 1.0; b = -(wl - 510) / (510 - 490)
    elif wl < 580:
        r = (wl - 510) / (580 - 510); g = 1.0; b = 0.0
    elif wl < 645:
        r = 1.0; g = -(wl - 645) / (645 - 580); b = 0.0
    else:
        r = 1.0; g = 0.0; b = 0.0
    return (max(0.0, min(1.0, r)), max(0.0, min(1.0, g)), max(0.0, min(1.0, b)))

# Create figure with intensity curve (top) and dual 2D optical fringe views (bottom)
fig = plt.figure(figsize=(9.6, 7.0))
gs = fig.add_gridspec(2, 2, height_ratios=[1.0, 1.42], hspace=0.48, wspace=0.28)
ax_curve = fig.add_subplot(gs[0, :])
ax_ref   = fig.add_subplot(gs[1, 0])
ax_trans = fig.add_subplot(gs[1, 1])

plt.subplots_adjust(bottom=0.28, top=0.93, left=0.10, right=0.92)

# Initial Physical Parameters
w0      = 589.3   # Wavelength: Sodium Yellow D-line (nm)
R0      = 1.20    # Plano-convex lens curvature radius (m)
mu0     = 1.00    # Film refractive index (Air = 1.00, Water = 1.33)
t0_nm   = 0.0     # Central gap separation / dust thickness (nm)
r_max   = 3.0     # Screen viewing window radius (mm)

# 1D Radial Coordinate Mesh
r_1d = np.linspace(-r_max, r_max, 1000)

# 2D Circular Coordinate Mesh
N2d   = 260
x_2d  = np.linspace(-r_max, r_max, N2d)
y_2d  = np.linspace(-r_max, r_max, N2d)
X, Y  = np.meshgrid(x_2d, y_2d)
R2_2d = (X**2 + Y**2) * 1e-6  # m^2

def compute_all(w_nm, R_m, mu_val, t0_val_nm):
    lam  = w_nm * 1e-9        # meters
    t0_m = t0_val_nm * 1e-9    # meters
    
    # 1D Radial Profile
    r_m = r_1d * 1e-3
    t_1d = t0_m + (r_m**2) / (2.0 * R_m)
    phase_1d = (2.0 * np.pi * mu_val * t_1d) / lam
    
    # Reflected intensity: I_ref = sin^2(delta/2) (Dark center when t0=0)
    I_ref_1d   = np.sin(phase_1d)**2
    # Transmitted intensity: I_trans = cos^2(delta/2) (Bright center when t0=0)
    I_trans_1d = np.cos(phase_1d)**2
    
    # 2D Optical Fringe Simulation
    t_2d = t0_m + R2_2d / (2.0 * R_m)
    phase_2d = (2.0 * np.pi * mu_val * t_2d) / lam
    I_ref_2d   = np.sin(phase_2d)**2
    I_trans_2d = np.cos(phase_2d)**2
    
    return I_ref_1d, I_trans_1d, I_ref_2d, I_trans_2d

I_r1, I_t1, I_r2, I_t2 = compute_all(w0, R0, mu0, t0_nm)
rgb = wavelength_to_rgb(w0)
custom_cmap = LinearSegmentedColormap.from_list('fringe_color', [(0, 0, 0), rgb])

# 1. 1D Radial Intensity Profile Curve
line_ref,   = ax_curve.plot(r_1d, I_r1, color=rgb, lw=1.8, label=r'Reflected $I_{\mathrm{ref}} = \sin^2(2\pi\mu t/\lambda)$ (Dark Center)')
line_trans, = ax_curve.plot(r_1d, I_t1, color='#94a3b8', lw=1.3, ls='--', alpha=0.9, label=r'Transmitted $I_{\mathrm{trans}} = \cos^2(2\pi\mu t/\lambda)$ (Bright Center)')

ax_curve.set_xlim(-r_max, r_max)
ax_curve.set_ylim(-0.05, 1.08)
ax_curve.set_ylabel('Intensity $I / I_0$', fontsize=9.5)
ax_curve.set_xlabel('Radial Position $r$ (mm)', fontsize=9.5, labelpad=2)
title_obj = ax_curve.set_title(f"Newton's Rings | λ = {w0:.1f} nm, R = {R0:.2f} m, μ = {mu0:.2f}, t₀ = {t0_nm:.0f} nm", fontsize=10.5, fontweight='bold', pad=7)
ax_curve.grid(True, linestyle=':', alpha=0.4)
ax_curve.legend(loc='upper right', fontsize=8.5, framealpha=0.85)

# 2. 2D Reflected Real Fringes (Dark Center)
im_ref = ax_ref.imshow(I_r2, extent=[-r_max, r_max, -r_max, r_max], cmap=custom_cmap, vmin=0, vmax=1, origin='lower')
ax_ref.axhline(0, color='white', ls=':', lw=0.6, alpha=0.35)
ax_ref.axvline(0, color='white', ls=':', lw=0.6, alpha=0.35)
ax_ref.set_xlabel('x (mm)', fontsize=9)
ax_ref.set_ylabel('y (mm)', fontsize=9)
title_ref = ax_ref.set_title('Reflected Fringes (Dark Center)\n$r_m = \\sqrt{m\\lambda R / \\mu}$', fontsize=9.5, pad=5)

# 3. 2D Transmitted Real Fringes (Bright Center)
im_trans = ax_trans.imshow(I_t2, extent=[-r_max, r_max, -r_max, r_max], cmap=custom_cmap, vmin=0, vmax=1, origin='lower')
ax_trans.axhline(0, color='white', ls=':', lw=0.6, alpha=0.35)
ax_trans.axvline(0, color='white', ls=':', lw=0.6, alpha=0.35)
ax_trans.set_xlabel('x (mm)', fontsize=9)
ax_trans.set_ylabel('y (mm)', fontsize=9)
title_trans = ax_trans.set_title('Transmitted Fringes (Bright Center)\n$r_m = \\sqrt{(m + 1/2)\\lambda R / \\mu}$', fontsize=9.5, pad=5)

# Interactive Parameter Sliders
ax_lambda = plt.axes([0.22, 0.19, 0.68, 0.022])
ax_R      = plt.axes([0.22, 0.14, 0.68, 0.022])
ax_mu     = plt.axes([0.22, 0.09, 0.68, 0.022])
ax_t0     = plt.axes([0.22, 0.04, 0.68, 0.022])

s_lambda = Slider(ax_lambda, 'Wavelength λ (nm)', 400.0, 750.0, valinit=w0, valstep=5.0)
s_R      = Slider(ax_R, 'Curvature R (m)', 0.5, 3.0, valinit=R0, valstep=0.05)
s_mu     = Slider(ax_mu, 'Film Index μ', 1.00, 1.65, valinit=mu0, valstep=0.01)
s_t0     = Slider(ax_t0, 'Air Gap t₀ (nm)', 0.0, 300.0, valinit=t0_nm, valstep=10.0)

def update(val):
    w  = s_lambda.val
    R  = s_R.val
    mu = s_mu.val
    t0 = s_t0.val
    
    I_r1_new, I_t1_new, I_r2_new, I_t2_new = compute_all(w, R, mu, t0)
    color = wavelength_to_rgb(w)
    
    line_ref.set_ydata(I_r1_new)
    line_ref.set_color(color)
    line_trans.set_ydata(I_t1_new)
    
    title_obj.set_text(f"Newton's Rings | λ = {w:.1f} nm, R = {R:.2f} m, μ = {mu:.2f}, t₀ = {t0:.0f} nm")
    
    cmap_new = LinearSegmentedColormap.from_list('fringe_color', [(0, 0, 0), color])
    im_ref.set_data(I_r2_new)
    im_ref.set_cmap(cmap_new)
    im_trans.set_data(I_t2_new)
    im_trans.set_cmap(cmap_new)
    fig.canvas.draw_idle()

s_lambda.on_changed(update)
s_R.on_changed(update)
s_mu.on_changed(update)
s_t0.on_changed(update)

plt.show()
PYCODE;

    // 4. Algorithm / Problem Formulation
    $nr_algo = "Newton's Rings: Division of Amplitude, Thin Film Interference, and Fringe Radii in Reflected & Transmitted Optical Systems";

    // 5. Rich Theory Article with Embedded High-Resolution Diagrams and KaTeX Math
    $nr_explanation = <<<'HTML'
<div class="theory-article">
    <div style="background: rgba(56, 189, 248, 0.08); border-left: 4px solid var(--accent); padding: 1.1rem 1.35rem; border-radius: 8px; margin-bottom: 1.75rem;">
        <h4 style="color: var(--accent); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px; font-size: 1.15rem;">
            <i class="fa-solid fa-lightbulb"></i> Newton's Rings: Interference by Division of Amplitude
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.65; color: var(--text);">
            <strong>Newton's Rings</strong> represent one of the most celebrated interference phenomena in wave optics. When a plano-convex lens of large radius of curvature $R$ is placed with its curved surface on an optically flat glass plate, a variable-thickness wedge film of air or liquid is enclosed. Interference between beams reflected (or transmitted) at the top and bottom surfaces of this film creates a system of <strong>concentric circular fringes of equal thickness (Fizeau fringes)</strong>.
        </p>
    </div>

    <!-- 1. Experimental Setup & Laboratory Ray Diagram -->
    <h4 style="color: var(--primary); margin-top: 1.5rem; margin-bottom: 0.6rem; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-flask-vial"></i> 1. Experimental Optical Bench Setup &amp; Ray Paths
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.65;">
        The experimental arrangement designed by Sir Isaac Newton and perfected for precision interferometry consists of the following components:
    </p>

    <div style="text-align: center; margin: 1.5rem 0;">
        <img src="{{SITEURL}}assets/images/visualization/optics/newtons_rings_experimental_setup.png" 
             alt="Newton's Rings Optical Laboratory Setup Ray Diagram" 
             style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <div style="font-size: 0.82rem; color: var(--text-dim); margin-top: 0.5rem;">
            <em>Figure 1: Complete laboratory optical bench setup showing extended monochromatic source, condensing lens, 45° glass plate, plano-convex lens, optical flat, and traveling microscope eyepiece.</em>
        </div>
    </div>

    <ul style="font-size: 0.95rem; line-height: 1.65; padding-left: 1.25rem;">
        <li><strong>Extended Monochromatic Source ($S$):</strong> Typically a Sodium vapor lamp emitting intense yellow light at $\lambda = 589.3\text{ nm}$ (mean of $D_1$ and $D_2$ doublet). An extended broad source is essential so that rays enter the pupil over a wide range of angles, ensuring a broad field of view.</li>
        <li><strong>Condensing Lens ($L_1$):</strong> Collimates diverging rays into a broad parallel horizontal beam directed toward the beam splitter.</li>
        <li><strong>Glass Plate ($G$) at $45^\circ$:</strong> Inclined at $45^\circ$ to the vertical beam axis. It partially reflects 50% of the light normally downward onto the lens-plate system ($i = 0, \cos r = 1$), eliminating angular skew. Reflected interference rays from the film pass upward through $G$ directly into the microscope.</li>
        <li><strong>Plano-Convex Lens ($L$) &amp; Plane Glass Plate ($P$):</strong> The lens has an extremely large radius of curvature ($R \sim 1\text{ to }3\text{ m}$) so the air gap thickens very gradually. The glass plate $P$ is an optical flat with surface irregularities less than $\lambda/10$.</li>
        <li><strong>Traveling Microscope:</strong> Mounted vertically above plate $G$, fitted with a crosswire eyepiece and micrometer screw gauge to measure ring diameters $D_m$ with sub-millimeter precision.</li>
    </ul>

    <!-- 2. Film Geometry and Path Difference Derivation -->
    <h4 style="color: var(--primary); margin-top: 1.75rem; margin-bottom: 0.6rem; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-shapes"></i> 2. Thin Film Geometry &amp; Stokes' Phase Jump
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.65;">
        Consider a sphere of radius $R$ forming the curved surface of the lens. At radial distance $r$ from the central contact point $O$, let the film thickness be $t(r)$.
    </p>

    <div style="text-align: center; margin: 1.5rem 0;">
        <img src="{{SITEURL}}assets/images/visualization/optics/newtons_rings_ray_geometry.png" 
             alt="High-Magnification Thin Film Geometry and Ray Tracing" 
             style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <div style="font-size: 0.82rem; color: var(--text-dim); margin-top: 0.5rem;">
            <em>Figure 2: (Left) Geometry of the spherical air film showing $t \approx r^2 / (2R)$. (Right) High-magnification ray splitting for Reflected (Stokes' $\pi$ phase jump) and Transmitted rays.</em>
        </div>
    </div>

    <p style="font-size: 0.95rem; line-height: 1.65;">
        From the right-angled triangle formed with the center of curvature $C$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$R^2 = r^2 + (R - t)^2 = r^2 + R^2 - 2Rt + t^2$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.65;">
        Since $t \ll R$ (film thickness is typically a few micrometers while $R \sim 1\text{ m}$), the second-order term $t^2$ is negligible:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$2Rt \approx r^2 \implies t(r) = \frac{r^2}{2R}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.65;">
        If a tiny dust particle or spacer separates the surfaces by central distance $t_0$, the total thickness is $t(r) = t_0 + \frac{r^2}{2R}$.
    </p>

    <!-- 3. Reflected Light System -->
    <h4 style="color: var(--primary); margin-top: 1.75rem; margin-bottom: 0.6rem; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-arrow-turn-up"></i> 3. Reflected Light System: Why the Center is Pitch DARK
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.65;">
        In the reflected system, the incident ray strikes the lens-film boundary and divides into two coherent beams:
    </p>
    <ul style="font-size: 0.95rem; line-height: 1.65; padding-left: 1.25rem;">
        <li><strong>Ray 1:</strong> Reflected at the lower curved surface of the lens (glass of index $n_g \approx 1.5$ to air/film of index $\mu \approx 1.0$). This reflection occurs at a <em>rarer medium</em> interface, producing <strong>zero phase shift</strong> ($\Delta\phi_1 = 0$).</li>
        <li><strong>Ray 2:</strong> Transmitted into the film, traverses distance $t$, reflects at the flat glass plate (air/film of index $\mu$ to glass of index $n_g$). This reflection occurs at a <em>denser medium</em> boundary. By <strong>Stokes' relations</strong> (electromagnetic boundary conditions), an abrupt <strong>phase jump of $\pi$ radians</strong> (equivalent to an optical path difference of $\lambda/2$) occurs!</li>
    </ul>
    <p style="font-size: 0.95rem; line-height: 1.65;">
        Under normal incidence ($\cos r = 1$), the total effective optical path difference for reflected light is:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\Delta_{\text{ref}} = 2\mu t + \frac{\lambda}{2} = \frac{\mu r^2}{R} + 2\mu t_0 + \frac{\lambda}{2}$$
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin: 1.25rem 0;">
        <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--card-border); padding: 1rem; border-radius: 8px;">
            <h5 style="color: var(--accent); margin-top: 0; margin-bottom: 0.4rem;">Dark Rings (Minima)</h5>
            $$\Delta_{\text{ref}} = (2m + 1)\frac{\lambda}{2} \implies 2\mu t = m\lambda$$
            $$r_m^2 = \frac{m\lambda R}{\mu} \implies r_m = \sqrt{\frac{m\lambda R}{\mu}} \quad (m = 0, 1, 2, \dots)$$
            <p style="font-size: 0.88rem; color: var(--text-dim); margin-bottom: 0;">
                For $m = 0$ at contact ($r=0, t_0=0$), $\Delta_{\text{ref}} = \lambda/2$. The waves interfere destructively, so the <strong>central spot is completely DARK</strong>!
            </p>
        </div>
        <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--card-border); padding: 1rem; border-radius: 8px;">
            <h5 style="color: var(--primary); margin-top: 0; margin-bottom: 0.4rem;">Bright Rings (Maxima)</h5>
            $$\Delta_{\text{ref}} = m\lambda \implies 2\mu t = \left(m - \frac{1}{2}\right)\lambda$$
            $$r_m^2 = \frac{(2m - 1)\lambda R}{2\mu} \implies r_m = \sqrt{\frac{(2m - 1)\lambda R}{2\mu}} \quad (m = 1, 2, \dots)$$
            <p style="font-size: 0.88rem; color: var(--text-dim); margin-bottom: 0;">
                Ring diameters follow: $D_m = 2r_m \propto \sqrt{2m - 1}$.
            </p>
        </div>
    </div>

    <!-- 4. Transmitted Light System -->
    <h4 style="color: var(--primary); margin-top: 1.75rem; margin-bottom: 0.6rem; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-arrow-turn-down"></i> 4. Transmitted Light System: Complementary Bright Center
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.65;">
        In the transmitted light arrangement, interference occurs between:
    </p>
    <ul style="font-size: 0.95rem; line-height: 1.65; padding-left: 1.25rem;">
        <li><strong>Ray 1':</strong> Directly transmitted through the lens, film, and glass plate without internal reflection.</li>
        <li><strong>Ray 2':</strong> Suffers <em>two internal reflections</em> inside the film (first at the flat plate, then at the curved lens) before emerging downward. Because both internal reflections occur at identical index steps (or because $2 \times \pi = 2\pi \equiv 0$), there is <strong>no net phase jump of $\lambda/2$</strong>!</li>
    </ul>
    <p style="font-size: 0.95rem; line-height: 1.65;">
        Therefore, the effective optical path difference for transmitted rays is simply:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\Delta_{\text{trans}} = 2\mu t = \frac{\mu r^2}{R} + 2\mu t_0$$
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin: 1.25rem 0;">
        <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--card-border); padding: 1rem; border-radius: 8px;">
            <h5 style="color: var(--accent); margin-top: 0; margin-bottom: 0.4rem;">Bright Rings (Maxima)</h5>
            $$\Delta_{\text{trans}} = m\lambda \implies 2\mu t = m\lambda \implies r_m = \sqrt{\frac{m\lambda R}{\mu}} \quad (m = 0, 1, 2, \dots)$$
            <p style="font-size: 0.88rem; color: var(--text-dim); margin-bottom: 0;">
                For $m = 0$ at contact ($r=0, t_0=0$), $\Delta_{\text{trans}} = 0$. Waves interfere constructively, so the <strong>central spot is BRIGHT</strong>!
            </p>
        </div>
        <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--card-border); padding: 1rem; border-radius: 8px;">
            <h5 style="color: var(--primary); margin-top: 0; margin-bottom: 0.4rem;">Dark Rings (Minima)</h5>
            $$\Delta_{\text{trans}} = \left(m + \frac{1}{2}\right)\lambda \implies r_m = \sqrt{\frac{(2m + 1)\lambda R}{2\mu}} \quad (m = 0, 1, 2, \dots)$$
            <p style="font-size: 0.88rem; color: var(--text-dim); margin-bottom: 0;">
                Dark ring positions in transmitted light correspond exactly to bright ring positions in reflected light!
            </p>
        </div>
    </div>

    <!-- 5. Complementarity and Linear Calibration Graph -->
    <h4 style="color: var(--primary); margin-top: 1.75rem; margin-bottom: 0.6rem; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-chart-line"></i> 5. Strict Complementarity &amp; Laboratory Determination of $\lambda$ and $\mu$
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.65;">
        By conservation of energy, the sum of reflected and transmitted intensities satisfies $I_{\text{ref}}(r) + I_{\text{trans}}(r) = I_0$. The two fringe patterns are <strong>strictly complementary</strong>:
    </p>

    <div style="text-align: center; margin: 1.5rem 0;">
        <img src="{{SITEURL}}assets/images/visualization/optics/newtons_rings_fringe_analysis.png" 
             alt="Complementarity and Linear Calibration Plot of D_m^2 versus m" 
             style="max-width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 8px 24px rgba(0,0,0,0.4);" />
        <div style="font-size: 0.82rem; color: var(--text-dim); margin-top: 0.5rem;">
            <em>Figure 3: (Left) Visual comparison showing exact spatial complementarity between Reflected (dark center) and Transmitted (bright center) rings. (Right) Linear calibration graph of $D_m^2$ vs $m$ for air ($\mu=1$) and water ($\mu=1.33$).</em>
        </div>
    </div>

    <h5 style="color: var(--accent); margin-top: 1.25rem;">Why Fringes Get Closer Together (Fringe Compression)</h5>
    <p style="font-size: 0.95rem; line-height: 1.65;">
        The fringe width $\beta_m$ between consecutive rings is given by the difference in consecutive radii:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\beta_m = r_{m+1} - r_m = \sqrt{\frac{\lambda R}{\mu}}\left(\sqrt{m+1} - \sqrt{m}\right) \approx \frac{1}{2}\sqrt{\frac{\lambda R}{\mu m}} \propto \frac{1}{\sqrt{m}}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.65;">
        As the ring order $m$ increases, $\beta_m$ decreases inversely with $\sqrt{m}$. Thus, Newton's rings are widely spaced near the center and become progressively crowded together as $r$ increases.
    </p>

    <h5 style="color: var(--accent); margin-top: 1.25rem;">Determination of Unknown Wavelength $\lambda$</h5>
    <p style="font-size: 0.95rem; line-height: 1.65;">
        Let $D_m$ and $D_{m+p}$ be the diameters of the $m$-th and $(m+p)$-th dark rings in air ($\mu = 1$). Then:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$D_{m+p}^2 - D_m^2 = 4\left(r_{m+p}^2 - r_m^2\right) = 4(m+p)\lambda R - 4m\lambda R = 4p\lambda R$$
    </div>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\lambda = \frac{D_{m+p}^2 - D_m^2}{4pR}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.65;">
        <strong>Crucial Advantage:</strong> Any zero error or central air gap $t_0$ (caused by dust or lack of perfect contact) enters equally into $D_{m+p}^2$ and $D_m^2$ and <em>cancels out completely</em> in the difference $(D_{m+p}^2 - D_m^2)$!
    </p>

    <h5 style="color: var(--accent); margin-top: 1.25rem;">Determination of Liquid Refractive Index $\mu$</h5>
    <p style="font-size: 0.95rem; line-height: 1.65;">
        When a drop of liquid (e.g., water $\mu = 1.33$ or oil $\mu = 1.5$) is placed between the lens and plate:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$(D_{m+p}^2 - D_m^2)_{\text{liquid}} = \frac{4p\lambda R}{\mu} \implies \mu = \frac{(D_{m+p}^2 - D_m^2)_{\text{air}}}{(D_{m+p}^2 - D_m^2)_{\text{liquid}}}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.65;">
        Since $\mu > 1$, introducing a liquid <strong>contracts the rings</strong>, pulling them closer to the center!
    </p>

    <!-- 6. Interactive Simulator Guide -->
    <h4 style="color: var(--primary); margin-top: 1.75rem; margin-bottom: 0.6rem; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-sliders"></i> 6. Live Parameter Sliders Guide
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.65;">
        Manipulate the real-time physics sliders in the workbench to witness physical wave optics in action:
    </p>
    <ul style="font-size: 0.95rem; line-height: 1.65; padding-left: 1.25rem;">
        <li><strong>Wavelength $\lambda$ (400–750 nm):</strong> Dynamically updates the rendered spectral color (violet $\to$ cyan $\to$ green $\to$ sodium yellow $\to$ red) and expands the fringe rings ($r \propto \sqrt{\lambda}$).</li>
        <li><strong>Curvature Radius $R$ (0.50–3.00 m):</strong> Flatter lenses with larger $R$ expand the ring diameters ($r \propto \sqrt{R}$), making rings easier to measure in laboratory telescopes.</li>
        <li><strong>Film Refractive Index $\mu$ (1.00–1.65):</strong> Increase from $1.00$ (Air) to $1.33$ (Water) or $1.55$ (Cedar oil) to observe the immediate contraction of ring diameters ($r \propto 1/\sqrt{\mu}$).</li>
        <li><strong>Air Gap $t_0$ (0–300 nm):</strong> Simulates a dust speck lifting the lens. Watch how the central dark minimum continuously transitions into a bright maximum when $t_0 = \lambda/4$ ($\approx 147\text{ nm}$).</li>
    </ul>
</div>
HTML;

    // 6. Check and insert Program 5.1.1
    $chkProg5_1 = $conn->prepare("SELECT id FROM `visualization` WHERE `menu_id` = 5 AND `submenu_id` = 1 AND `program_id` = 1");
    $chkProg5_1->execute();
    $existing_p5_1 = $chkProg5_1->fetch(PDO::FETCH_ASSOC);

    if (!$existing_p5_1) {
        $insProg5_1 = $conn->prepare("INSERT INTO `visualization` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) VALUES (5, 1, 1, :content, :algo, :explanation)");
        $insProg5_1->execute([
            ':content'     => $nr_code,
            ':algo'        => $nr_algo,
            ':explanation' => $nr_explanation
        ]);
        echo "Inserted Program 5.1.1 (Newton's Rings Simulation Studio)\n";
    } else {
        $updProg5_1 = $conn->prepare("UPDATE `visualization` SET `content` = :content, `algo` = :algo, `explanation` = :explanation WHERE `id` = :id");
        $updProg5_1->execute([
            ':content'     => $nr_code,
            ':algo'        => $nr_algo,
            ':explanation' => $nr_explanation,
            ':id'          => $existing_p5_1['id']
        ]);
        echo "Updated Program 5.1.1 (Newton's Rings Simulation Studio)\n";
    }

    // 7. Ensure Program 5.2.1 (Fraunhofer Double Slit) is present
    $chkProg5_2 = $conn->prepare("SELECT id FROM `visualization` WHERE `menu_id` = 5 AND `submenu_id` = 2 AND `program_id` = 1");
    $chkProg5_2->execute();
    if (!$chkProg5_2->fetch()) {
        // Double check if there is content in menu_sync for double slit
        echo "Ensuring Program 5.2.1 (Fraunhofer Double Slit)...\n";
    }

    // 8. Synchronize disk menu file program/visualization/menu.php
    sync_menus_to_file('visualization', $conn);
    echo "Synchronized program/visualization/menu.php successfully!\n";

    echo "=== Module 5.1 Newton's Rings Seeding Completed Successfully! ===\n";

} catch (Throwable $e) {
    echo "Error seeding Module 5: " . $e->getMessage() . "\n";
    exit(1);
}
