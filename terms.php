<?php
/**
 * Python4Physics - Terms of Service & Academic Usage Terms
 * Authored by Dr. Alorika Chatterjee & Dr. Anirban Shaw
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';

$page_title = "Terms of Service & Academic Usage Terms";
$page_description = "Terms of service, educational usage licensing, code execution guidelines, and academic attribution rules for Python4Physics.";

require_once __DIR__ . '/include/header.php';
require_once __DIR__ . '/include/navbar.php';
?>

<main class="container" style="padding-top: 3.5rem; padding-bottom: 5rem; max-width: 880px;">
    <div style="margin-bottom: 3rem;">
        <span class="badge badge-cyan"><i class="fa-solid fa-scale-balanced"></i> Legal & Academic Governance</span>
        <h1 style="font-size: clamp(2.2rem, 4vw, 3rem); margin-top: 0.75rem; margin-bottom: 0.5rem; line-height: 1.2;">
            Terms of Service & <span class="gradient-text">Academic Usage</span>
        </h1>
        <p style="color: var(--text-dim);">Last Revised: October 2026 | Effective Date: Immediate</p>
    </div>

    <div class="glass-card" style="padding: 2.5rem; display: flex; flex-direction: column; gap: 2rem; line-height: 1.8;">
        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">1. Acceptance of Terms</h2>
            <p>
                By accessing, browsing, or utilizing the <strong>Python4Physics</strong> website (located at <code>https://python4physics.in</code> and its associated domains), you agree to comply with and be bound by these Terms of Service, our <a href="<?php echo $siteurl; ?>privacy.php">Privacy Policy</a>, and our <a href="<?php echo $siteurl; ?>disclaimer.php">Disclaimer</a>. If you do not agree with any part of these terms, you should not access or use this educational portal.
            </p>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">2. Open Academic & Educational License</h2>
            <p>
                Python4Physics is founded by <strong>Dr. Alorika Chatterjee</strong> and <strong>Dr. Anirban Shaw</strong> as an open educational resource (OER). 
            </p>
            <ul style="padding-left: 1.5rem; margin-top: 0.5rem;">
                <li><strong>Students & Educators:</strong> You are granted a free, non-exclusive, non-transferable license to access, view, modify, and execute all scientific code, algorithm scripts, LaTeX templates, and GNUplot scripts for personal learning, classroom teaching, academic laboratory sessions, and non-commercial research.</li>
                <li><strong>Attribution:</strong> Whenever computational algorithms or laboratory modules from this platform are reproduced, modified, or incorporated into academic theses, publications, or lecture notes, proper attribution to the authors is requested:
                    <pre class="console-output" style="margin-top: 0.5rem;">Chatterjee, A., & Shaw, A. (2026). Computational Physics with Python, GNUplot, LaTeX, and Arduino. Python4Physics Portal, https://python4physics.in</pre>
                </li>
                <li><strong>Commercial Restrictions:</strong> Bulk scraping, unauthorized republishing of the entire codebase as a commercial textbook, or reselling access behind a paid paywall without express written permission is strictly prohibited.</li>
            </ul>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">3. Client-Side Code Execution & Pyodide WASM Runtime</h2>
            <p>
                All Python simulations on this portal are executed client-side via Pyodide (WebAssembly) inside your local web browser sandbox. 
            </p>
            <ul style="padding-left: 1.5rem; margin-top: 0.5rem;">
                <li>The platform provides computational scripts "as is" for pedagogical demonstration.</li>
                <li>You acknowledge that browser execution speed and memory limits are constrained by your local hardware capabilities.</li>
                <li>You agree not to use the browser-based editor or execution tools to craft malicious scripts, cross-site attacks, or automated denial-of-service attempts.</li>
            </ul>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">4. Intellectual Property & Copyright</h2>
            <p>
                The visual layout, proprietary design elements, curated curriculum sequence, interactive widget architectures, and original authored explanations on Python4Physics are copyrighted by <strong>Dr. Alorika Chatterjee & Dr. Anirban Shaw</strong> (&copy; <?php echo date("Y"); ?>). Third-party open-source libraries (such as NumPy, SciPy, Matplotlib, KaTeX, CodeMirror, and Pyodide) remain the intellectual property of their respective creators and are used under their permissive open-source licenses.
            </p>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">5. User Submissions & Feedback</h2>
            <p>
                When you submit feedback, suggestions, code bug reports, or student inquiries via our <a href="<?php echo $siteurl; ?>feedback.php">Feedback Portal</a> or official email addresses:
            </p>
            <ul style="padding-left: 1.5rem; margin-top: 0.5rem;">
                <li>You grant the platform maintainers a perpetual, non-exclusive right to implement suggested algorithm fixes, curriculum improvements, and pedagogical enhancements.</li>
                <li>You agree not to submit offensive, defamatory, unlawful, or copyrighted material without appropriate license permissions.</li>
            </ul>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">6. Third-Party Links & Advertising Partners</h2>
            <p>
                This website may contain links to external collegiate websites, academic repositories (e.g., GitHub, University of Calcutta), and third-party tools. We do not endorse or assume liability for the content, privacy policies, or practices of any external third-party sites.
            </p>
            <p>
                To support server hosting, bandwidth, and maintenance of this free portal, we display advertisements provided by <strong>Google AdSense</strong>. Google and its advertising partners serve contextual and non-intrusive ads in accordance with Google's Program Policies.
            </p>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">7. Limitation of Liability & Warranty Disclaimer</h2>
            <p>
                Python4Physics is provided on an "AS IS" and "AS AVAILABLE" basis without warranties of any kind, either express or implied. In no event shall the authors, their respective collegiate institutions (The Heritage College or Dhruba Chand Halder College), or affiliated contributors be liable for any direct, indirect, incidental, or consequential damages arising from the use or inability to use the scripts, algorithms, or content on this website.
            </p>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">8. Changes to These Terms</h2>
            <p>
                We reserve the right to modify or replace these Terms of Service at any time to reflect curriculum additions or regulatory requirements. Changes are effective immediately upon posting to this page. Continued usage of the portal constitutes your acceptance of the updated terms.
            </p>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">9. Contact Information</h2>
            <p>
                If you have questions regarding these Terms of Service or academic licensing, please contact:
            </p>
            <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid var(--card-border); padding: 1.25rem 1.5rem; border-radius: var(--radius-md); margin-top: 0.75rem;">
                <strong>Python4Physics Academic Secretariat</strong><br>
                Dr. Anirban Shaw & Dr. Alorika Chatterjee<br>
                Email: <a href="mailto:anirbanshaw@python4physics.in">anirbanshaw@python4physics.in</a> / <a href="mailto:alorika.chatterjee1@gmail.com">alorika.chatterjee1@gmail.com</a><br>
                Department of Physics, Dhruba Chand Halder College & The Heritage College, West Bengal, India
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
