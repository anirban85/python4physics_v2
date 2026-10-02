<?php
/**
 * Python4Physics - Menu Sync & DB Helper
 * Keeps p4p_menus, p4p_submenus and program/{lang}/menu.php files seamlessly synchronized.
 */
require_once __DIR__ . '/../db.php';

if (!function_exists('sync_menus_to_file')) {
    function sync_menus_to_file($lang, $conn) {
        $valid_langs = ['python', 'gnuplot', 'latex', 'visualization'];
        if (!in_array($lang, $valid_langs)) {
            return false;
        }

        // Fetch menus
        $stmt = $conn->prepare("SELECT menu_id, title FROM `p4p_menus` WHERE `language` = :lang ORDER BY `sort_order` ASC, `menu_id` ASC");
        $stmt->execute([':lang' => $lang]);
        $menus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Fetch submenus
        $stmt = $conn->prepare("SELECT menu_id, submenu_id, title FROM `p4p_submenus` WHERE `language` = :lang ORDER BY `menu_id` ASC, `sort_order` ASC, `submenu_id` ASC");
        $stmt->execute([':lang' => $lang]);
        $all_subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $submenus = [];
        foreach ($all_subs as $s) {
            $m_id = (string)$s['menu_id'];
            $s_id = (int)$s['submenu_id'];
            if (!isset($submenus[$m_id])) {
                $submenus[$m_id] = [];
            }
            $submenus[$m_id][$s_id] = $s['title'];
        }

        // Generate PHP content without volatile timestamps
        $code = "<?php\n";
        $code .= "// Automatically synced from Database via Admin Manager\n";
        $code .= "// Language: " . strtoupper($lang) . "\n\n";

        $code .= "\$menu_titles = " . var_export($menus, true) . ";\n\n";
        $code .= "\$sub_menu_titles = " . var_export($submenus, true) . ";\n";

        $target_dir = __DIR__ . "/../program/{$lang}";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = "{$target_dir}/menu.php";

        // Check if existing file on disk already has identical structure
        if (file_exists($target_file)) {
            $existing = file_get_contents($target_file);
            $clean_existing = preg_replace('/\/\/ Last Updated: [^\n]+\n+/', '', $existing);
            $clean_code = preg_replace('/\/\/ Last Updated: [^\n]+\n+/', '', $code);
            if (trim($clean_existing) === trim($clean_code)) {
                return true; // Already identical, do not touch file on disk
            }
        }

        $saved = file_put_contents($target_file, $code) !== false;
        return $saved;
    }
}

if (!function_exists('sync_sitemap_xml')) {
    function sync_sitemap_xml($conn = null) {
        $sitemap_script = __DIR__ . '/../sitemap.php';
        if (file_exists($sitemap_script)) {
            // Include functions from sitemap.php if not yet loaded
            require_once $sitemap_script;
            if (function_exists('build_sitemap_urls') && function_exists('generate_sitemap_xml_string') && function_exists('sync_sitemap_to_disk')) {
                $base_url = "https://v2.python4physics.in";
                if (!empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost' && !str_starts_with($_SERVER['HTTP_HOST'], '127.0.0.1')) {
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
                    $base_url = rtrim($protocol . $_SERVER['HTTP_HOST'], '/');
                }
                $urls = build_sitemap_urls($base_url, $conn);
                $xml = generate_sitemap_xml_string($urls);
                return sync_sitemap_to_disk($xml);
            }
        }
        return false;
    }
}

if (!function_exists('get_admin_menus')) {
    function get_admin_menus($lang, $conn) {
        $stmt = $conn->prepare("SELECT * FROM `p4p_menus` WHERE `language` = :lang ORDER BY `sort_order` ASC, `menu_id` ASC");
        $stmt->execute([':lang' => $lang]);
        $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("SELECT * FROM `p4p_submenus` WHERE `language` = :lang ORDER BY `menu_id` ASC, `sort_order` ASC, `submenu_id` ASC");
        $stmt->execute([':lang' => $lang]);
        $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $subs_by_menu = [];
        foreach ($subs as $s) {
            $subs_by_menu[$s['menu_id']][] = $s;
        }

        foreach ($menus as &$m) {
            $m['submenus'] = $subs_by_menu[$m['menu_id']] ?? [];
        }
        unset($m);

        return $menus;
    }
}

if (!function_exists('ensure_visualization_installed')) {
    function ensure_visualization_installed($conn) {
        if (!isset($conn) || $conn === null) {
            return false;
        }

        try {
            // 1. Ensure visualization table exists
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

            try {
                $conn->exec("ALTER TABLE `visualization` MODIFY `explanation` longtext NULL DEFAULT NULL");
                $conn->exec("ALTER TABLE `visualization` MODIFY `algo` longtext NULL DEFAULT NULL");
                $conn->exec("ALTER TABLE `visualization` MODIFY `content` longtext NULL DEFAULT NULL");
            } catch (Throwable $alterE) {}

            // 2. Ensure p4p_menus table exists and has Module 1
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

            // 3. Ensure p4p_submenus table exists and has Subtopic 1
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

            // Check / insert Module 1 (Quantum Mechanics)
            $chkMenu = $conn->prepare("SELECT COUNT(*) FROM `p4p_menus` WHERE `language` = 'visualization' AND `menu_id` = 1");
            $chkMenu->execute();
            if ((int)$chkMenu->fetchColumn() === 0) {
                $insMenu = $conn->prepare("INSERT INTO `p4p_menus` (`language`, `menu_id`, `title`, `sort_order`) VALUES ('visualization', 1, 'Quantum Mechanics & Wave Equations', 1)");
                $insMenu->execute();
            }

            // Check / insert Subtopic 1.1 (QHO)
            $chkSub = $conn->prepare("SELECT COUNT(*) FROM `p4p_submenus` WHERE `language` = 'visualization' AND `menu_id` = 1 AND `submenu_id` = 1");
            $chkSub->execute();
            if ((int)$chkSub->fetchColumn() === 0) {
                $insSub = $conn->prepare("INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES ('visualization', 1, 1, '1D Time-Independent Schroedinger Equation (TISE) - QHO Shooting Method', 1)");
                $insSub->execute();
            }

            // Check / insert Program 1.1.1 (QHO)
            $chkProg = $conn->prepare("SELECT COUNT(*) FROM `visualization` WHERE `menu_id` = 1 AND `submenu_id` = 1 AND `program_id` = 1");
            $chkProg->execute();
            if ((int)$chkProg->fetchColumn() === 0) {
                $qho_content = <<<'PYCODE'
"""
Solve 1-dimensional time-independent Schroedinger Equation (TISE) for 
Quantum Harmonic Oscillator (QHO) using shooting algorithm and Numerov method  
"""
import numpy as np
from scipy.integrate import odeint, trapezoid
import matplotlib.pyplot as plt
from matplotlib.widgets import Slider


fig, ax = plt.subplots()
plt.subplots_adjust(left=0.25, bottom=0.25)

def V(x):
    return 0.5*m*w**2*x**2
    
L, m, hbar, w, E0, En = 6, 1, 1, 1, 0, 10

dEn = 0.1
x = np.linspace(-L, L, 1000)
u = [0, 0.1]
    
def f(u, x, E):
    y, z = u
    f1, f2 = z, ((2*m)/(hbar)**2)*(V(x) -E)*y
    return (f1, f2)

def psinorm(En):
    psi = odeint(f, u, x, args = (En, ))[:,0]
    normpsi = psi/np.sqrt(trapezoid(psi*psi, x)) #Normalize the wave function
    return normpsi

s = psinorm(1.5)
p, = plt.plot(x, s, lw = 2)
plt.xlabel("x")
plt.ylabel("ψ(x)")
plt.grid()

axEn = plt.axes([0.25, 0.1, 0.65, 0.03])
sEn = Slider(axEn, 'Energy', 0, 10, valinit=1.5, valstep=dEn)

def update(val):
    En = sEn.val
    p.set_ydata(psinorm(En))
    fig.canvas.draw_idle()

sEn.on_changed(update)
        
plt.show()
PYCODE;

                $qho_algo = "Solve 1-dimensional time-independent Schroedinger Equation (TISE) for Quantum Harmonic Oscillator (QHO) using shooting algorithm and Numerov method";

                $qho_explanation = '<div class="theory-article">
    <div style="background: rgba(56, 189, 248, 0.08); border-left: 4px solid var(--accent); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--accent); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-atom"></i> Quantum Harmonic Oscillator (QHO) - Boundary Value Problem
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            The Quantum Harmonic Oscillator represents a foundational model in quantum mechanics, describing vibrating diatomic molecules, lattice vibrations (phonons), and quantum field fluctuations around stable equilibrium positions.
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. One-Dimensional Time-Independent Schr&ouml;dinger Equation (TISE)
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The stationary states $\\psi(x)$ of a non-relativistic quantum particle of mass $m$ confined within a potential well $V(x)$ are governed by the time-independent Schr&ouml;dinger equation:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$-\\frac{\\hbar^2}{2m} \\frac{d^2\\psi(x)}{dx^2} + V(x)\\psi(x) = E \\psi(x)$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Rearranging into standard second-order ordinary differential equation (ODE) form:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\\frac{d^2\\psi(x)}{dx^2} = \\frac{2m}{\\hbar^2} \\left[ V(x) - E \\right] \\psi(x)$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Parabolic Harmonic Potential &amp; Analytical Eigenvalues
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        For a harmonic oscillator with angular frequency $\\omega$, the parabolic restoring potential is:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$V(x) = \\frac{1}{2} m \\omega^2 x^2$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Analytical solution yields discrete, equally-spaced energy eigenvalues:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$E_n = \\left(n + \\frac{1}{2}\\right) \\hbar \\omega, \\quad n = 0, 1, 2, 3, \\dots$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        In dimensionless natural units ($m = 1, \\hbar = 1, \\omega = 1$):
        <br>&bull; <strong>Ground state ($n = 0$):</strong> $E_0 = 0.5$ (Zero-point energy)
        <br>&bull; <strong>1st Excited state ($n = 1$):</strong> $E_1 = 1.5$ (Odd parity, single central node)
        <br>&bull; <strong>2nd Excited state ($n = 2$):</strong> $E_2 = 2.5$ (Even parity, two symmetric nodes)
        <br>&bull; <strong>3rd Excited state ($n = 3$):</strong> $E_3 = 3.5$ (Odd parity, three nodes)
        <br>&bull; <strong>4th Excited state ($n = 4$):</strong> $E_4 = 4.5$ (Even parity, four nodes)
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. The Shooting Algorithm &amp; Boundary Conditions
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Because bound-state wave functions must be square-integrable, the physical boundary conditions require $\\psi(x) \\to 0$ as $x \\to \\pm \\infty$. On a finite computational domain $[-L, L]$ with $L = 6$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\\psi(-L) = 0, \\quad \\psi(+L) = 0$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The <em>shooting algorithm</em> converts the 2nd-order boundary value problem into a system of two coupled 1st-order differential equations:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\\frac{dy_1}{dx} = y_2, \\qquad \\frac{dy_2}{dx} = \\frac{2m}{\\hbar^2} \\left[ V(x) - E \\right] y_1$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Starting from $x = -L$ with initial vector $u = [y_1(-L), y_2(-L)] = [0, 0.1]$, the numerical integrator (<code>scipy.integrate.odeint</code>) integrates across the domain to $x = +L$ for a trial test energy $E$.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        4. Wave Function Normalization &amp; Interactive Slider
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The probability of finding the quantum particle anywhere along the $x$-axis is identically unity:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\\int_{-\\infty}^{\\infty} |\\psi(x)|^2 dx = 1 \\implies \\psi_{\\text{norm}}(x) = \\frac{\\psi(x)}{\\sqrt{\\int_{-L}^{L} \\psi^2(x) dx}}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        In the simulation code, the trapezoidal quadrature rule (<code>scipy.integrate.trapezoid</code>) computes the normalization integral dynamically.
        By adjusting the <strong>Energy Slider</strong> ($E \\in [0, 10]$), observe how non-eigenvalues diverge rapidly at $x = +L$, whereas true quantized energies ($E = 0.5, 1.5, 2.5, 3.5, 4.5, \\dots$) smoothly decay to zero at both boundaries, displaying exact quantum nodes!
    </p>
</div>';

                $insProg = $conn->prepare("INSERT INTO `visualization` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) VALUES (1, 1, 1, :content, :algo, :explanation)");
                $insProg->execute([
                    ':content'     => $qho_content,
                    ':algo'        => $qho_algo,
                    ':explanation' => $qho_explanation
                ]);
            }

            // Check / insert Module 2 (Optics)
            $chkMenu2 = $conn->prepare("SELECT COUNT(*) FROM `p4p_menus` WHERE `language` = 'visualization' AND `menu_id` = 2");
            $chkMenu2->execute();
            if ((int)$chkMenu2->fetchColumn() === 0) {
                $insMenu2 = $conn->prepare("INSERT INTO `p4p_menus` (`language`, `menu_id`, `title`, `sort_order`) VALUES ('visualization', 2, 'Optics', 2)");
                $insMenu2->execute();
            }

            // Check / insert Subtopic 2.1 (Fraunhofer Diffraction in Double Slit)
            $chkSub2 = $conn->prepare("SELECT COUNT(*) FROM `p4p_submenus` WHERE `language` = 'visualization' AND `menu_id` = 2 AND `submenu_id` = 1");
            $chkSub2->execute();
            if ((int)$chkSub2->fetchColumn() === 0) {
                $insSub2 = $conn->prepare("INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES ('visualization', 2, 1, 'Fraunhofer Diffraction in Double Slit', 1)");
                $insSub2->execute();
            }

            // Check / insert / update Program 2.1.1 (Double Slit Simulation)
            $ds_content = <<<'PYCODE'
"""
Fraunhofer Diffraction at a Double Slit
Intensity Distribution and Simulated Optical Fringe Pattern
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

# Create figure with intensity curve (top) and fringe simulation (bottom)
fig, (ax_curve, ax_fringe) = plt.subplots(2, 1, figsize=(9, 5.8), 
                                         gridspec_kw={'height_ratios': [3, 1], 'hspace': 0.38})
plt.subplots_adjust(bottom=0.32, top=0.91, left=0.12, right=0.92)

# Initial parameter values
w0, a0, d0, D0 = 632.8, 0.04, 0.20, 1.0
x_max = 20.0  # Screen extent in mm (+/- 20 mm)
x = np.linspace(-x_max, x_max, 1200)

def compute_intensity(w_nm, a_mm, d_mm, D_m):
    lam = w_nm * 1e-9      # meters
    a = a_mm * 1e-3        # meters
    d = d_mm * 1e-3        # meters
    D = D_m                # meters
    x_m = x * 1e-3         # meters
    
    sin_theta = x_m / np.sqrt(x_m**2 + D**2)
    beta = (np.pi * a / lam) * sin_theta
    alpha = (np.pi * d / lam) * sin_theta
    
    # Diffraction envelope (single slit): (sin(beta)/beta)^2
    diffraction = np.where(np.abs(beta) < 1e-9, 1.0, (np.sin(beta) / beta)**2)
    
    # Interference fringes (double slit): cos^2(alpha)
    interference = np.cos(alpha)**2
    
    # Total intensity: I(theta) = I0 * (sin(beta)/beta)^2 * cos^2(alpha)
    return diffraction * interference, diffraction

I_tot, I_env = compute_intensity(w0, a0, d0, D0)
rgb = wavelength_to_rgb(w0)
custom_cmap = LinearSegmentedColormap.from_list('laser', [(0, 0, 0), rgb])

# 1. Intensity distribution curve
line_tot, = ax_curve.plot(x, I_tot, color=rgb, lw=1.8, label=r'Double-Slit $I(\theta) = I_0 (\frac{\sin\beta}{\beta})^2 \cos^2\alpha$')
line_env, = ax_curve.plot(x, I_env, color='#94a3b8', lw=1.2, ls='--', alpha=0.9, label=r'Diffraction Envelope $(\frac{\sin\beta}{\beta})^2$')

ax_curve.set_xlim(-x_max, x_max)
ax_curve.set_ylim(-0.04, 1.06)
ax_curve.set_ylabel('Intensity $I / I_0$', fontsize=10)
title_obj = ax_curve.set_title(f'Fraunhofer Double-Slit Diffraction | λ = {w0:.1f} nm, a = {a0*1e3:.0f} μm, d = {d0:.2f} mm (d/a = {d0/a0:.1f})', fontsize=10.5, fontweight='bold', pad=8)
ax_curve.grid(True, linestyle=':', alpha=0.4)
ax_curve.legend(loc='upper right', fontsize=8.5, framealpha=0.85)

# 2. Simulated 2D optical fringe view
im_fringe = ax_fringe.imshow(np.tile(I_tot, (30, 1)), extent=[-x_max, x_max, 0, 1], 
                             aspect='auto', cmap=custom_cmap, vmin=0, vmax=1)
ax_fringe.set_xlim(-x_max, x_max)
ax_fringe.set_yticks([])
ax_fringe.set_xlabel('Screen Position $x$ (mm)', fontsize=10)
ax_fringe.set_title('Simulated Optical Fringes on Screen', fontsize=9.5, pad=4)

# Interactive Parameter Sliders
ax_lambda = plt.axes([0.22, 0.20, 0.68, 0.022])
ax_a      = plt.axes([0.22, 0.15, 0.68, 0.022])
ax_d      = plt.axes([0.22, 0.10, 0.68, 0.022])
ax_D      = plt.axes([0.22, 0.05, 0.68, 0.022])

s_lambda = Slider(ax_lambda, 'Wavelength λ (nm)', 400.0, 750.0, valinit=w0, valstep=5.0)
s_a      = Slider(ax_a, 'Slit Width a (mm)', 0.01, 0.10, valinit=a0, valstep=0.005)
s_d      = Slider(ax_d, 'Slit Separation d (mm)', 0.08, 0.60, valinit=d0, valstep=0.01)
s_D      = Slider(ax_D, 'Screen Dist. D (m)', 0.5, 2.5, valinit=D0, valstep=0.05)

def update(val):
    w = s_lambda.val
    a = s_a.val
    d = s_d.val
    D = s_D.val
    if d < a:
        d = a
        s_d.set_val(d)
        
    I_new, env_new = compute_intensity(w, a, d, D)
    color = wavelength_to_rgb(w)
    
    line_tot.set_ydata(I_new)
    line_tot.set_color(color)
    line_env.set_ydata(env_new)
    
    title_obj.set_text(f'Fraunhofer Double-Slit Diffraction | λ = {w:.1f} nm, a = {a*1e3:.0f} μm, d = {d:.2f} mm (d/a = {d/a:.1f})')
    
    cmap_new = LinearSegmentedColormap.from_list('laser', [(0, 0, 0), color])
    im_fringe.set_data(np.tile(I_new, (30, 1)))
    im_fringe.set_cmap(cmap_new)
    fig.canvas.draw_idle()

s_lambda.on_changed(update)
s_a.on_changed(update)
s_d.on_changed(update)
s_D.on_changed(update)

plt.show()
PYCODE;

            $ds_algo = "Fraunhofer Diffraction at a Double Slit: Intensity Distribution and Simulated Fringe Pattern";

            $ds_explanation = '<div class="theory-article">
    <div style="background: rgba(56, 189, 248, 0.08); border-left: 4px solid var(--accent); padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h4 style="color: var(--accent); margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-lightbulb"></i> Fraunhofer Double-Slit Diffraction &amp; Interference
        </h4>
        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
            Fraunhofer diffraction at a double slit illustrates the fundamental interplay between <strong>single-slit diffraction</strong> and <strong>two-beam Young\'s interference</strong>. The resulting pattern features rapid interference oscillations enveloped by a broad diffraction profile.
        </p>
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        1. Total Intensity Distribution
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Consider a monochromatic plane wave of wavelength $\\lambda$ incident normally upon two long parallel slits, each of width $a$, with center-to-center separation $d$ ($d > a$). At a diffraction angle $\\theta$ on a distant screen at distance $D$, the resultant intensity is given by:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$I(\\theta) = I_0 \\left(\\frac{\\sin\\beta}{\\beta}\\right)^2 \\cos^2\\alpha$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        where the dimensionless phase variables $\\beta$ and $\\alpha$ are defined by:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\\beta = \\frac{\\pi a}{\\lambda}\\sin\\theta \\approx \\frac{\\pi a x}{\\lambda D}, \\qquad \\alpha = \\frac{\\pi d}{\\lambda}\\sin\\theta \\approx \\frac{\\pi d x}{\\lambda D}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        &bull; <strong>Diffraction Factor $\\left(\\frac{\\sin\\beta}{\\beta}\\right)^2$:</strong> Arises from phase differences between secondary wavelets originating across the width $a$ of each individual slit.<br>
        &bull; <strong>Interference Factor $\\cos^2\\alpha$:</strong> Arises from phase differences between the two slits separated by distance $d$.
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        2. Interference Maxima &amp; Fringe Width
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Bright interference maxima occur when $\\cos^2\\alpha = 1$, which requires:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\\alpha = m\\pi \\implies d\\sin\\theta = m\\lambda \\implies x_m \\approx \\frac{m\\lambda D}{d}, \\quad m = 0, \\pm 1, \\pm 2, \\dots$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The linear fringe spacing $\\Delta x$ between consecutive interference maxima on the screen is:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\\Delta x = \\frac{\\lambda D}{d}$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        3. Diffraction Minima &amp; Envelope Zeros
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The overall diffraction envelope drops to zero whenever $\\sin\\beta = 0$ with $\\beta \\neq 0$:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\\beta = p\\pi \\implies a\\sin\\theta = p\\lambda \\implies x_p \\approx \\frac{p\\lambda D}{a}, \\quad p = \\pm 1, \\pm 2, \\dots$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        The central diffraction peak spans between the first minima at $p = \\pm 1$, with total angular width:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$2\\theta_0 = \\frac{2\\lambda}{a}$$
    </div>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        4. Missing Orders (Absent Spectra)
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        A crucial feature studied in optics laboratories is <strong>missing orders</strong>. If an interference maximum occurs at the exact same angle as a diffraction minimum, no light reaches that point, and the expected interference fringe vanishes completely:
    </p>
    <div style="background: var(--bg-secondary); padding: 0.85rem 1.25rem; border-radius: 8px; margin: 0.75rem 0; font-size: 1.05rem; overflow-x: auto; text-align: center;">
        $$\\frac{d\\sin\\theta}{a\\sin\\theta} = \\frac{m\\lambda}{p\\lambda} \\implies \\frac{d}{a} = \\frac{m}{p}$$
    </div>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        For instance, when $d = 5a$ (as set initially in this simulation), the missing interference orders are $m = \\pm 5, \\pm 10, \\pm 15, \\dots$. Within the central diffraction peak, exactly $2(d/a) - 1 = 9$ bright interference fringes are visible!
    </p>

    <h4 style="color: var(--primary); margin-top: 1.25rem; margin-bottom: 0.5rem;">
        5. Interactive Parameter Controls
    </h4>
    <p style="font-size: 0.95rem; line-height: 1.6;">
        Use the live sliders below to explore how physical parameters alter the optical pattern:
        <br>&bull; <strong>Wavelength $\\lambda$ (400&ndash;750 nm):</strong> Alters the laser color in real time and scales fringe spacing proportionally ($\\Delta x \\propto \\lambda$).
        <br>&bull; <strong>Slit Width $a$ (0.01&ndash;0.10 mm):</strong> Narrower slits widen the envelope, revealing more interference fringes inside the central peak.
        <br>&bull; <strong>Slit Separation $d$ (0.08&ndash;0.60 mm):</strong> Greater separation packs the interference fringes closer together.
        <br>&bull; <strong>Screen Distance $D$ (0.5&ndash;2.5 m):</strong> Increases linear magnification on the detection screen.
    </p>
</div>';

            $chkProg2 = $conn->prepare("SELECT id, content FROM `visualization` WHERE `menu_id` = 2 AND `submenu_id` = 1 AND `program_id` = 1");
            $chkProg2->execute();
            $existing_row = $chkProg2->fetch(PDO::FETCH_ASSOC);
            if (!$existing_row) {
                $insProg2 = $conn->prepare("INSERT INTO `visualization` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) VALUES (2, 1, 1, :content, :algo, :explanation)");
                $insProg2->execute([
                    ':content'     => $ds_content,
                    ':algo'        => $ds_algo,
                    ':explanation' => $ds_explanation
                ]);
            }

            // Only synchronize disk menu file if it does not yet exist
            $vis_menu_file = __DIR__ . '/../program/visualization/menu.php';
            if (!file_exists($vis_menu_file)) {
                sync_menus_to_file('visualization', $conn);
            }

            return true;
        } catch (Throwable $t) {
            error_log("Failed to ensure visualization table: " . $t->getMessage());
            return false;
        }
    }
}

