<?php
/**
 * Python4Physics - Academic, Simulation & Advertising Disclaimer
 * Authored by Dr. Alorika Chatterjee & Dr. Anirban Shaw
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';

$page_title = "Disclaimer - Academic, Numerical Simulation & Advertising Notice";
$page_description = "Important legal, pedagogical, and advertising disclaimers for computational physics codes, numerical approximations, and Google AdSense on Python4Physics.";

require_once __DIR__ . '/include/header.php';
require_once __DIR__ . '/include/navbar.php';
?>

<main class="container" style="padding-top: 3.5rem; padding-bottom: 5rem; max-width: 880px;">
    <div style="margin-bottom: 3rem;">
        <span class="badge badge-amber"><i class="fa-solid fa-triangle-exclamation"></i> Academic & Advertising Notice</span>
        <h1 style="font-size: clamp(2.2rem, 4vw, 3rem); margin-top: 0.75rem; margin-bottom: 0.5rem; line-height: 1.2;">
            Legal & Academic <span class="gradient-text">Disclaimer</span>
        </h1>
        <p style="color: var(--text-dim);">Last Revised: October 2026</p>
    </div>

    <div class="glass-card" style="padding: 2.5rem; display: flex; flex-direction: column; gap: 2rem; line-height: 1.8;">
        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">1. Educational & Pedagogical Disclaimer</h2>
            <p>
                The numerical solvers, algorithms, interactive simulations, LaTeX templates, GNUplot scripts, and hardware interfacing codes published on <strong>Python4Physics</strong> (<code>https://python4physics.in</code>) are provided solely for educational, pedagogical, and academic research purposes. They are designed to illustrate fundamental physical principles, numerical approximations (such as Euler, Runge-Kutta, Verlet, and Finite Difference schemes), and computational techniques.
            </p>
            <p>
                While every effort has been made by the authors (<strong>Dr. Alorika Chatterjee</strong> and <strong>Dr. Anirban Shaw</strong>) to ensure mathematical correctness, stability, and adherence to established physical laws, numerical methods inherently involve truncation errors, step-size dependencies, and round-off approximations. These simulations should not be deployed in safety-critical, industrial, or mission-critical engineering applications without independent verification.
            </p>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">2. Advertising & Google AdSense Disclosure</h2>
            <p>
                Python4Physics partners with <strong>Google AdSense</strong> to display advertisements across certain pages. These advertisements allow us to maintain web hosting servers, domain registrations, bandwidth, and open-source infrastructure free of charge for university students and educators globally.
            </p>
            <ul style="padding-left: 1.5rem; margin-top: 0.5rem;">
                <li>The presence of any third-party advertisement on this portal does <strong>not</strong> constitute an endorsement, sponsorship, or recommendation by the authors, The Heritage College, or Dhruba Chand Halder College.</li>
                <li>Google AdSense uses cookies to serve ads based on your prior browsing history. For details on how to control or opt out of personalized advertising, please review our <a href="<?php echo $siteurl; ?>privacy.php">Privacy Policy</a> or visit <a href="https://www.google.com/settings/ads" target="_blank" rel="noopener">Google Ads Settings</a>.</li>
                <li>We do not accept paid sponsored content or native advertorials that compromise academic objectivity.</li>
            </ul>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">3. External Links Disclaimer</h2>
            <p>
                This website contains hyperlinks to external resources including GitHub, university portals, scientific libraries (NumPy, SciPy, Matplotlib), and official collegiate department pages. These external links are provided strictly as an academic convenience. We have no editorial control over the content, availability, or privacy policies of third-party platforms.
            </p>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">4. Institutional Independence</h2>
            <p>
                Python4Physics is an academic initiative authored and curated by Dr. Alorika Chatterjee and Dr. Anirban Shaw in their personal academic capacities as physics faculty members. The views, pedagogical approaches, and curriculum materials presented here do not necessarily reflect the official administrative positions or policies of The Heritage College, Dhruba Chand Halder College, or the University of Calcutta.
            </p>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">5. Contact for Questions</h2>
            <p>
                If you have questions regarding this disclaimer, please contact us at:
            </p>
            <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid var(--card-border); padding: 1.25rem 1.5rem; border-radius: var(--radius-md); margin-top: 0.75rem;">
                Email: <a href="mailto:anirbanshaw@python4physics.in">anirbanshaw@python4physics.in</a><br>
                Department of Physics, Dhruba Chand Halder College & The Heritage College, West Bengal, India
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
