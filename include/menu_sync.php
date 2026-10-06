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
            $norm_existing = str_replace(["\r\n", "\r"], "\n", $clean_existing);
            $norm_code = str_replace(["\r\n", "\r"], "\n", $clean_code);
            if (trim($norm_existing) === trim($norm_code)) {
                return true; // Already identical, do not touch file on disk
            }
        }

        $code = str_replace(["\r\n", "\r"], "\n", $code);
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

            // Check Module 1 (Vectors)
            $chkMenu1 = $conn->prepare("SELECT title FROM `p4p_menus` WHERE `language` = 'visualization' AND `menu_id` = 1");
            $chkMenu1->execute();
            $m1_title = $chkMenu1->fetchColumn();
            $chkProg1Count = (int)$conn->query("SELECT COUNT(*) FROM `visualization` WHERE `menu_id` = 1")->fetchColumn();

            if (!$m1_title || !str_contains(strtolower($m1_title), 'vector') || $chkProg1Count < 5) {
                if (file_exists(__DIR__ . '/../seed_module1_vectors.php')) {
                    require_once __DIR__ . '/../seed_module1_vectors.php';
                }
            }

            // Check Module 2 (Tensors)
            $chkMenu2 = $conn->prepare("SELECT title FROM `p4p_menus` WHERE `language` = 'visualization' AND `menu_id` = 2");
            $chkMenu2->execute();
            $m2_title = $chkMenu2->fetchColumn();
            $chkProg2Count = (int)$conn->query("SELECT COUNT(*) FROM `visualization` WHERE `menu_id` = 2")->fetchColumn();

            if (!$m2_title || !str_contains(strtolower($m2_title), 'tensor') || $chkProg2Count < 5) {
                if (file_exists(__DIR__ . '/../seed_module2_tensors.php')) {
                    require_once __DIR__ . '/../seed_module2_tensors.php';
                }
            }

            // Check Module 3 (Classical Mechanics: Double Pendulum Dynamics & Chaos)
            $chkMenu3 = $conn->prepare("SELECT title FROM `p4p_menus` WHERE `language` = 'visualization' AND `menu_id` = 3");
            $chkMenu3->execute();
            $m3_title = $chkMenu3->fetchColumn();
            $chkProg3Count = (int)$conn->query("SELECT COUNT(*) FROM `visualization` WHERE `menu_id` = 3")->fetchColumn();

            if (!$m3_title || !str_contains(strtolower($m3_title), 'pendulum') || $chkProg3Count < 3) {
                if (file_exists(__DIR__ . '/../seed_module3_double_pendulum.php')) {
                    require_once __DIR__ . '/../seed_module3_double_pendulum.php';
                }
            }

            // Check / insert Module 4 (Quantum Mechanics & Wave Equations)
            $chkMenu4 = $conn->prepare("SELECT COUNT(*) FROM `p4p_menus` WHERE `language` = 'visualization' AND `menu_id` = 4");
            $chkMenu4->execute();
            if ((int)$chkMenu4->fetchColumn() === 0) {
                $insMenu4 = $conn->prepare("INSERT INTO `p4p_menus` (`language`, `menu_id`, `title`, `sort_order`) VALUES ('visualization', 4, 'Quantum Mechanics & Wave Equations', 4)");
                $insMenu4->execute();
            }

            // Check / insert Subtopic 4.1 (QHO)
            $chkSub4 = $conn->prepare("SELECT COUNT(*) FROM `p4p_submenus` WHERE `language` = 'visualization' AND `menu_id` = 4 AND `submenu_id` = 1");
            $chkSub4->execute();
            if ((int)$chkSub4->fetchColumn() === 0) {
                $insSub4 = $conn->prepare("INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES ('visualization', 4, 1, '1D Time-Independent Schroedinger Equation (TISE) - QHO Shooting Method', 1)");
                $insSub4->execute();
            }

            // Check / insert Program 4.1.1 (QHO)
            $chkProg4 = $conn->prepare("SELECT COUNT(*) FROM `visualization` WHERE `menu_id` = 4 AND `submenu_id` = 1 AND `program_id` = 1");
            $chkProg4->execute();
            if ((int)$chkProg4->fetchColumn() === 0) {
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

                $insProg4 = $conn->prepare("INSERT INTO `visualization` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) VALUES (4, 1, 1, :content, :algo, :explanation)");
                $insProg4->execute([
                    ':content'     => $qho_content,
                    ':algo'        => $qho_algo,
                    ':explanation' => $qho_explanation
                ]);
            }

            // Check / insert Module 5 (Optics)
            $chkMenu5 = $conn->prepare("SELECT COUNT(*) FROM `p4p_menus` WHERE `language` = 'visualization' AND `menu_id` = 5");
            $chkMenu5->execute();
            if ((int)$chkMenu5->fetchColumn() === 0) {
                $insMenu5 = $conn->prepare("INSERT INTO `p4p_menus` (`language`, `menu_id`, `title`, `sort_order`) VALUES ('visualization', 5, 'Optics', 5)");
                $insMenu5->execute();
            }

            // Check / shift Fraunhofer if at 5.1
            $chkOldFraun = $conn->prepare("SELECT id FROM `p4p_submenus` WHERE `language` = 'visualization' AND `menu_id` = 5 AND `submenu_id` = 1 AND `title` LIKE '%Fraunhofer%'");
            $chkOldFraun->execute();
            if ($chkOldFraun->fetch()) {
                $chk5_2 = $conn->prepare("SELECT id FROM `p4p_submenus` WHERE `language` = 'visualization' AND `menu_id` = 5 AND `submenu_id` = 2");
                $chk5_2->execute();
                if ($chk5_2->fetch()) {
                    $conn->exec("DELETE FROM `p4p_submenus` WHERE `language` = 'visualization' AND `menu_id` = 5 AND `submenu_id` = 2");
                }
                $conn->exec("UPDATE `p4p_submenus` SET `submenu_id` = 2, `sort_order` = 2 WHERE `language` = 'visualization' AND `menu_id` = 5 AND `submenu_id` = 1");
                $conn->exec("UPDATE `visualization` SET `submenu_id` = 2 WHERE `menu_id` = 5 AND `submenu_id` = 1");
            }

            // Ensure Subtopic 5.1 (Newton's Rings)
            $chkSub5_1 = $conn->prepare("SELECT id FROM `p4p_submenus` WHERE `language` = 'visualization' AND `menu_id` = 5 AND `submenu_id` = 1");
            $chkSub5_1->execute();
            if (!$chkSub5_1->fetch()) {
                $insSub5_1 = $conn->prepare("INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES ('visualization', 5, 1, 'Newton’s Rings Interference (Reflected & Transmitted Systems)', 1)");
                $insSub5_1->execute();
            }

            // Ensure Subtopic 5.2 (Fraunhofer Diffraction in Double Slit)
            $chkSub5_2 = $conn->prepare("SELECT id FROM `p4p_submenus` WHERE `language` = 'visualization' AND `menu_id` = 5 AND `submenu_id` = 2");
            $chkSub5_2->execute();
            if (!$chkSub5_2->fetch()) {
                $insSub5_2 = $conn->prepare("INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES ('visualization', 5, 2, 'Fraunhofer Diffraction in Double Slit', 2)");
                $insSub5_2->execute();
            }

            // Program 5.1.1: Newton's Rings Simulation Studio
            $nr_content = <<<'PYCODE'
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

            $nr_algo = "Newton's Rings: Division of Amplitude, Thin Film Interference, and Fringe Radii in Reflected & Transmitted Optical Systems";

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

            $chkProg5_1 = $conn->prepare("SELECT id FROM `visualization` WHERE `menu_id` = 5 AND `submenu_id` = 1 AND `program_id` = 1");
            $chkProg5_1->execute();
            $existing_row5_1 = $chkProg5_1->fetch(PDO::FETCH_ASSOC);
            if (!$existing_row5_1) {
                $insProg5_1 = $conn->prepare("INSERT INTO `visualization` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) VALUES (5, 1, 1, :content, :algo, :explanation)");
                $insProg5_1->execute([
                    ':content'     => $nr_content,
                    ':algo'        => $nr_algo,
                    ':explanation' => $nr_explanation
                ]);
            }

            // Program 5.2.1 (Fraunhofer Double Slit)
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

            $chkProg5_2 = $conn->prepare("SELECT id, content FROM `visualization` WHERE `menu_id` = 5 AND `submenu_id` = 2 AND `program_id` = 1");
            $chkProg5_2->execute();
            $existing_row5_2 = $chkProg5_2->fetch(PDO::FETCH_ASSOC);
            if (!$existing_row5_2) {
                $insProg5_2 = $conn->prepare("INSERT INTO `visualization` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) VALUES (5, 2, 1, :content, :algo, :explanation)");
                $insProg5_2->execute([
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

