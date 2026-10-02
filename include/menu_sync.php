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

        // Generate PHP content
        $code = "<?php\n";
        $code .= "// Automatically synced from Database via Admin Manager\n";
        $code .= "// Language: " . strtoupper($lang) . "\n";
        $code .= "// Last Updated: " . date('Y-m-d H:i:s') . "\n\n";

        $code .= "\$menu_titles = " . var_export($menus, true) . ";\n\n";
        $code .= "\$sub_menu_titles = " . var_export($submenus, true) . ";\n";

        $target_dir = __DIR__ . "/../program/{$lang}";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = "{$target_dir}/menu.php";
        $saved = file_put_contents($target_file, $code) !== false;
        
        // Also automatically re-sync dynamic sitemap.xml
        sync_sitemap_xml($conn);

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
              `menu_id` int(11) NOT NULL,
              `submenu_id` int(11) NOT NULL,
              `program_id` int(11) DEFAULT NULL,
              `content` longtext DEFAULT NULL,
              `algo` longtext DEFAULT NULL,
              `explanation` longtext NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

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

            // Check / insert Module 1
            $chkMenu = $conn->prepare("SELECT COUNT(*) FROM `p4p_menus` WHERE `language` = 'visualization' AND `menu_id` = 1");
            $chkMenu->execute();
            if ((int)$chkMenu->fetchColumn() === 0) {
                $insMenu = $conn->prepare("INSERT INTO `p4p_menus` (`language`, `menu_id`, `title`, `sort_order`) VALUES ('visualization', 1, 'Quantum Mechanics & Wave Equations', 1)");
                $insMenu->execute();
            }

            // Check / insert Subtopic 1
            $chkSub = $conn->prepare("SELECT COUNT(*) FROM `p4p_submenus` WHERE `language` = 'visualization' AND `menu_id` = 1 AND `submenu_id` = 1");
            $chkSub->execute();
            if ((int)$chkSub->fetchColumn() === 0) {
                $insSub = $conn->prepare("INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES ('visualization', 1, 1, '1D Time-Independent Schroedinger Equation (TISE) - QHO Shooting Method', 1)");
                $insSub->execute();
            }

            // Check / insert Program 1
            $chkProg = $conn->prepare("SELECT COUNT(*) FROM `visualization` WHERE `menu_id` = 1 AND `submenu_id` = 1 AND `program_id` = 1");
            $chkProg->execute();
            if ((int)$chkProg->fetchColumn() === 0) {
                $qho_content = "\"\"\"\nSolve 1-dimensional time-independent Schroedinger Equation (TISE) for \nQuantum Harmonic Oscillator (QHO) using shooting algorithm and Numerov method  \n\"\"\"\nimport numpy as np\nfrom scipy.integrate import odeint, trapezoid\nimport matplotlib.pyplot as plt\nfrom matplotlib.widgets import Slider\n\n\nfig, ax = plt.subplots()\nplt.subplots_adjust(left=0.25, bottom=0.25)\n\ndef V(x):\n    return 0.5*m*w**2*x**2\n    \nL, m, hbar, w, E0, En = 6, 1, 1, 1, 0, 10\n\ndEn = 0.1\nx = np.linspace(-L, L, 1000)\nu = [0, 0.1]\n    \ndef f(u, x, E):\n    y, z = u\n    f1, f2 = z, ((2*m)/(hbar)**2)*(V(x) -E)*y\n    return (f1, f2)\n\ndef psinorm(En):\n    psi = odeint(f, u, x, args = (En, ))[:,0]\n    normpsi = psi/np.sqrt(trapezoid(psi*psi, x)) #Normalize the wave function\n    return normpsi\n\ns = psinorm(1.5)\np, = plt.plot(x, s, lw = 2)\nplt.xlabel(\"x\")\nplt.ylabel(\"\\u03c8(x)\")\nplt.grid()\n\naxEn = plt.axes([0.25, 0.1, 0.65, 0.03])\nsEn = Slider(axEn, 'Energy', 0, 10, valinit=1.5, valstep=dEn)\n\ndef update(val):\n    En = sEn.val\n    p.set_ydata(psinorm(En))\n    fig.canvas.draw_idle()\n\nsEn.on_changed(update)\n        \nplt.show()";

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

            // 5. Ensure disk menu file exists
            $menu_file = __DIR__ . '/../program/visualization/menu.php';
            if (!file_exists($menu_file)) {
                sync_menus_to_file('visualization', $conn);
            }

            return true;
        } catch (Throwable $t) {
            error_log("Failed to ensure visualization table: " . $t->getMessage());
            return false;
        }
    }
}

