<?php
/**
 * Python4Physics - Privacy Policy & Academic Terms
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';

$page_title = "Privacy Policy & Academic Terms";
$page_description = "Privacy policy, data protection, and open academic usage guidelines for the Python4Physics scientific computing portal.";

require_once __DIR__ . '/include/header.php';
require_once __DIR__ . '/include/navbar.php';
?>

<main class="container" style="padding-top: 3.5rem; padding-bottom: 5rem; max-width: 860px;">
    <div style="margin-bottom: 3rem;">
        <span class="badge badge-cyan">Legal & Academic Policies</span>
        <h1 style="font-size: 2.6rem; margin-top: 0.75rem; margin-bottom: 0.5rem;">Privacy Policy & <span class="gradient-text">Academic Terms</span></h1>
        <p style="color: var(--text-dim);">Last updated: September 2026</p>
    </div>

    <div class="glass-card" style="padding: 2.5rem; display: flex; flex-direction: column; gap: 2rem; line-height: 1.75;">
        <section>
            <h3 style="color: var(--accent); margin-bottom: 0.75rem;">1. Academic Open Educational Mission</h3>
            <p>
                <strong>Python4Physics</strong> is an open-access educational initiative founded by <strong>Dr. Alorika Chatterjee</strong> and <strong>Dr. Anirban Shaw</strong>. The platform is designed strictly for academic instruction, student learning, and research in computational physics and numerical methods.
            </p>
        </section>

        <section>
            <h3 style="color: var(--accent); margin-bottom: 0.75rem;">2. Client-Side Code Execution & Privacy</h3>
            <p>
                Python simulations on this portal are executed entirely client-side using <strong>Pyodide (WebAssembly)</strong> inside your own web browser. Your proprietary code modifications, student assignments, and experimental data are not transmitted to or stored on our servers during execution.
            </p>
        </section>

        <section>
            <h3 style="color: var(--accent); margin-bottom: 0.75rem;">3. Data Collection & Feedback</h3>
            <p>
                We do not collect personal data without your consent. If you voluntarily submit our feedback form, we store your name, email, and message solely to respond to inquiries and improve curriculum offerings. We never sell, lease, or monetize user data.
            </p>
        </section>

        <section>
            <h3 style="color: var(--accent); margin-bottom: 0.75rem;">4. Cookies & Local Storage</h3>
            <p>
                We utilize minimal <code>localStorage</code> purely to remember your theme preference (Dark Mode / Light Mode). No third-party tracking or behavioral profiling cookies are placed by our application logic.
            </p>
        </section>

        <section>
            <h3 style="color: var(--accent); margin-bottom: 0.75rem;">5. Citation & Academic Attribution</h3>
            <p>
                Educators and researchers utilizing algorithms, assignments, or Arduino laboratory modules from this platform in publications or university coursework are requested to cite:
            </p>
            <pre class="console-output" style="margin-top: 0.75rem;">Chatterjee, A., & Shaw, A. (2026). Computational Physics with Python, GNUplot, LaTeX, and Arduino. Python4Physics Portal, https://python4physics.in</pre>
        </section>

        <section>
            <h3 style="color: var(--accent); margin-bottom: 0.75rem;">6. Contact</h3>
            <p>
                For privacy or curriculum inquiries, please contact: <a href="mailto:anirbanshaw@python4physics.in">anirbanshaw@python4physics.in</a> or visit our <a href="<?php echo $siteurl; ?>contact.php">Faculty page</a>.
            </p>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
