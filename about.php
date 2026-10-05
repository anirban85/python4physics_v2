<?php
/**
 * Python4Physics - About Us & Academic Editorial Board
 * Authored by Dr. Alorika Chatterjee & Dr. Anirban Shaw
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';

$page_title = "About Us - Academic Mission, Faculty & Editorial Standards";
$page_description = "Learn about Python4Physics: an open-access computational physics initiative founded by Dr. Alorika Chatterjee and Dr. Anirban Shaw for university students, educators, and researchers worldwide.";

require_once __DIR__ . '/include/header.php';
require_once __DIR__ . '/include/navbar.php';
?>

<main class="container" style="padding-top: 3.5rem; padding-bottom: 5rem; max-width: 1040px;">
    <!-- Page Header -->
    <div style="text-align: center; max-width: 860px; margin: 0 auto 3.5rem auto;">
        <span class="badge badge-cyan"><i class="fa-solid fa-atom"></i> Academic Open Science</span>
        <h1 style="font-size: clamp(2.2rem, 4vw, 3.2rem); margin-top: 0.75rem; margin-bottom: 0.75rem; line-height: 1.2;">
            About <span class="gradient-text">Python4Physics</span>
        </h1>
        <p style="font-size: 1.15rem; line-height: 1.6; color: var(--text-muted);">
            An open-access educational initiative conceived, authored, and maintained by collegiate physics faculty to bridge the gap between theoretical physics formulations and modern scientific computation.
        </p>
    </div>

    <!-- Project Overview & Mission -->
    <div class="glass-card" style="padding: 2.5rem; margin-bottom: 3rem; line-height: 1.8;">
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(0, 198, 255, 0.15); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.3rem;">
                <i class="fa-solid fa-compass"></i>
            </div>
            <h2 style="font-size: 1.75rem; margin: 0;">Our Pedagogical Mission</h2>
        </div>
        <p>
            Computational physics has become an indispensable third pillar of modern physical inquiry alongside theoretical deduction and experimental observation. However, undergraduate and postgraduate students often face significant hurdles when transitioning from abstract mathematical equations to clean, robust, and reproducible computer simulations.
        </p>
        <p>
            Founded in West Bengal, India, <strong>Python4Physics</strong> (accessible at <a href="<?php echo $siteurl; ?>">python4physics.in</a>) provides free, ad-supported, and open-access educational material. The portal hosts <strong>over 500+ verified computational physics programs, numerical solvers, GNUplot visual scripts, and LaTeX research templates</strong>. Our curriculum is tailored specifically for Bachelor of Science (B.Sc.) Honours, Master of Science (M.Sc.), and integrated research scholars in Physics and Applied Mathematics.
        </p>
        <p>
            The original collegiate release of the platform remains permanently preserved for reference at our <a href="https://v1.python4physics.in/" target="_blank" rel="noopener" style="color: #f59e0b; font-weight: 600;"><i class="fa-solid fa-clock-rotate-left"></i> Python4Physics Classic v1 Archive <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.75rem;"></i></a>, while this modernized architecture delivers instantaneous in-browser WebAssembly execution, parameter exploration, and responsive scientific typography.
        </p>
    </div>

    <!-- Faculty & Editorial Leadership -->
    <div style="margin-bottom: 3.5rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <span class="badge badge-amber"><i class="fa-solid fa-user-graduate"></i> Academic Leadership</span>
            <h2 style="font-size: 2.2rem; margin-top: 0.5rem;">Founders & Editorial Curators</h2>
            <p style="color: var(--text-dim); max-width: 600px; margin: 0 auto;">
                Python4Physics is independently conceived, peer-reviewed, and developed by active collegiate physics educators.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 2rem;">
            <!-- Dr. Alorika Chatterjee -->
            <div class="glass-card" style="padding: 2.5rem 2rem; display: flex; flex-direction: column;">
                <div style="display: flex; align-items: center; gap: 1.25rem; margin-bottom: 1.5rem;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--accent-gradient); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 2rem; font-weight: 700; flex-shrink: 0; box-shadow: 0 4px 14px rgba(0, 198, 255, 0.35); border: 2px solid var(--card-border);">
                        AC
                    </div>
                    <div>
                        <h3 style="font-size: 1.5rem; margin-bottom: 0.25rem;">Dr. Alorika Chatterjee</h3>
                        <span class="badge badge-cyan">Assistant Professor of Physics</span>
                    </div>
                </div>

                <div style="font-size: 0.95rem; line-height: 1.7; margin-bottom: 1.5rem; flex-grow: 1;">
                    <p style="margin-bottom: 0.75rem;">
                        <strong>Department of Physics</strong><br>
                        The Heritage College, Kolkata, West Bengal, India
                    </p>
                    <p style="color: var(--text-muted); margin-bottom: 0.75rem;">
                        Dr. Chatterjee is an experienced researcher and educator with deep expertise in computational physics methods, condensed matter physics, and numerical modeling. Her academic efforts center on curriculum modernization and helping students develop intuition for complex physical systems through computational algorithms.
                    </p>
                    <ul style="list-style: none; padding-left: 0; margin-bottom: 0; display: flex; flex-direction: column; gap: 0.4rem; color: var(--text-dim);">
                        <li><i class="fa-solid fa-graduation-cap" style="color: var(--accent); margin-right: 6px;"></i> Ph.D. in Physics</li>
                        <li><i class="fa-solid fa-book-open" style="color: var(--accent); margin-right: 6px;"></i> Specialization: Numerical Solvers & Condensed Matter</li>
                        <li><i class="fa-solid fa-building-columns" style="color: var(--accent); margin-right: 6px;"></i> Faculty at The Heritage College</li>
                    </ul>
                </div>

                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <a href="https://www.thc.edu.in/Faculty.aspx" target="_blank" rel="noopener" class="btn-modern btn-secondary btn-sm">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Institutional Profile
                    </a>
                    <a href="mailto:alorika.chatterjee1@gmail.com" class="btn-modern btn-primary btn-sm">
                        <i class="fa-regular fa-envelope"></i> Contact Faculty
                    </a>
                </div>
            </div>

            <!-- Dr. Anirban Shaw -->
            <div class="glass-card" style="padding: 2.5rem 2rem; display: flex; flex-direction: column;">
                <div style="display: flex; align-items: center; gap: 1.25rem; margin-bottom: 1.5rem;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #1d4ed8, #3b82f6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 2rem; font-weight: 700; flex-shrink: 0; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35); border: 2px solid var(--card-border);">
                        AS
                    </div>
                    <div>
                        <h3 style="font-size: 1.5rem; margin-bottom: 0.25rem;">Dr. Anirban Shaw</h3>
                        <span class="badge badge-blue">Assistant Professor of Physics</span>
                    </div>
                </div>

                <div style="font-size: 0.95rem; line-height: 1.7; margin-bottom: 1.5rem; flex-grow: 1;">
                    <p style="margin-bottom: 0.75rem;">
                        <strong>Department of Physics</strong><br>
                        Dhruba Chand Halder College, Dakshin Barasat, West Bengal, India
                    </p>
                    <p style="color: var(--text-muted); margin-bottom: 0.75rem;">
                        Dr. Shaw specializes in computational astrophysics, orbital mechanics, differential equation solvers, and hardware-software telemetry. He designs practical laboratory modules integrating Python, Arduino microcontrollers, and WebAssembly to make scientific computing directly accessible to undergraduate students.
                    </p>
                    <ul style="list-style: none; padding-left: 0; margin-bottom: 0; display: flex; flex-direction: column; gap: 0.4rem; color: var(--text-dim);">
                        <li><i class="fa-solid fa-graduation-cap" style="color: var(--primary); margin-right: 6px;"></i> Ph.D. in Physics</li>
                        <li><i class="fa-solid fa-code" style="color: var(--primary); margin-right: 6px;"></i> Specialization: Computational Astrophysics & Scientific Programming</li>
                        <li><i class="fa-solid fa-building-columns" style="color: var(--primary); margin-right: 6px;"></i> Faculty at Dhruba Chand Halder College (CU Affiliated)</li>
                    </ul>
                </div>

                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <a href="https://dchcollege.org/main/departments/details.php?faculty=1103" target="_blank" rel="noopener" class="btn-modern btn-secondary btn-sm">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Institutional Profile
                    </a>
                    <a href="mailto:anirbanshaw@python4physics.in" class="btn-modern btn-primary btn-sm">
                        <i class="fa-regular fa-envelope"></i> Contact Faculty
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Curriculum Alignment & Academic Standards Grid -->
    <div style="margin-bottom: 3.5rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <span class="badge badge-blue"><i class="fa-solid fa-check-double"></i> Rigorous Pedagogy</span>
            <h2 style="font-size: 2.2rem; margin-top: 0.5rem;">Curriculum Alignment & Verification</h2>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.75rem;">
            <div class="glass-card" style="padding: 2rem;">
                <div style="color: var(--accent); font-size: 1.5rem; margin-bottom: 1rem;">
                    <i class="fa-solid fa-scroll"></i>
                </div>
                <h3 style="font-size: 1.3rem; margin-bottom: 0.75rem;">University Curriculum Aligned</h3>
                <p style="font-size: 0.95rem; line-height: 1.6; color: var(--text-muted); margin: 0;">
                    All computational algorithms, numerical methods, and laboratory exercises comply directly with university curricula including UGC CBCS (Choice Based Credit System), NEP 2020 syllabus recommendations, University of Calcutta, and international computational physics syllabi.
                </p>
            </div>

            <div class="glass-card" style="padding: 2rem;">
                <div style="color: var(--accent); font-size: 1.5rem; margin-bottom: 1rem;">
                    <i class="fa-solid fa-calculator"></i>
                </div>
                <h3 style="font-size: 1.3rem; margin-bottom: 0.75rem;">Analytical Verification</h3>
                <p style="font-size: 0.95rem; line-height: 1.6; color: var(--text-muted); margin: 0;">
                    Every numerical implementation—from 4th-Order Runge-Kutta and Symplectic Verlet to Finite Difference Time Domain (FDTD) and quantum shooting methods—is cross-verified against exact analytical solutions to ensure mathematical precision and stability.
                </p>
            </div>

            <div class="glass-card" style="padding: 2rem;">
                <div style="color: var(--accent); font-size: 1.5rem; margin-bottom: 1rem;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 style="font-size: 1.3rem; margin-bottom: 0.75rem;">Client-Side Privacy (Pyodide)</h3>
                <p style="font-size: 0.95rem; line-height: 1.6; color: var(--text-muted); margin: 0;">
                    Python programs execute natively inside the user's browser via Pyodide WebAssembly. Student code, modifications, and experimental data are never logged or stored on our servers, ensuring complete privacy, zero latency, and safe experimentation.
                </p>
            </div>
        </div>
    </div>

    <!-- Editorial Policy & Open Science -->
    <div class="glass-card" style="padding: 2.5rem; line-height: 1.8; margin-bottom: 3rem;">
        <h3 style="color: var(--accent); margin-bottom: 1rem;">Editorial Standards & Corrections Policy</h3>
        <p>
            We are committed to maintaining the highest standard of academic accuracy. All code contributions, physics models, and documentation undergo continuous review by the authors. If you detect any typographical error, mathematical discrepancy, or numerical instability in any of our published scripts, please notify us immediately through our <a href="<?php echo $siteurl; ?>feedback.php">Feedback Portal</a> or via email at <a href="mailto:anirbanshaw@python4physics.in">anirbanshaw@python4physics.in</a>. All verified corrections are promptly incorporated and documented.
        </p>
        <p style="margin-bottom: 0;">
            Python4Physics operates as an independent educational resource. To keep the platform completely free and accessible to students across developing and developed nations alike, we partner with reputable ad networks such as <strong>Google AdSense</strong> to offset server, domain, and development maintenance costs.
        </p>
    </div>

    <!-- Institutional Collaboration Callout -->
    <div class="glass-card highlight" style="text-align: center; padding: 3rem 2rem;">
        <h3 style="font-size: 1.8rem; margin-bottom: 1rem;">Adopt Python4Physics in Your Department</h3>
        <p style="max-width: 720px; margin: 0 auto 1.5rem auto; font-size: 1.05rem; color: var(--text-muted);">
            Colleges, universities, and physics departments are encouraged to incorporate our interactive problem sets, Python simulations, and Arduino laboratory interfacing modules into their coursework.
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo $siteurl; ?>contact.php" class="btn-modern btn-primary">
                <i class="fa-solid fa-graduation-cap"></i> Faculty & Contact Directory
            </a>
            <a href="<?php echo $siteurl; ?>privacy.php" class="btn-modern btn-secondary">
                <i class="fa-solid fa-shield-halved"></i> Privacy Policy & Terms
            </a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
