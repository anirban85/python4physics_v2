<?php
/**
 * Python4Physics - Comprehensive Privacy Policy & Academic Data Protection
 * Authored by Dr. Alorika Chatterjee & Dr. Anirban Shaw
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';

$page_title = "Privacy Policy - Academic Data Protection & Advertising Disclosures";
$page_description = "Comprehensive privacy policy, cookie disclosures, Google AdSense compliance, and client-side data protection for Python4Physics.";

require_once __DIR__ . '/include/header.php';
require_once __DIR__ . '/include/navbar.php';
?>

<main class="container" style="padding-top: 3.5rem; padding-bottom: 5rem; max-width: 880px;">
    <div style="margin-bottom: 3rem;">
        <span class="badge badge-cyan"><i class="fa-solid fa-user-shield"></i> Privacy & Transparency</span>
        <h1 style="font-size: clamp(2.2rem, 4vw, 3rem); margin-top: 0.75rem; margin-bottom: 0.5rem; line-height: 1.2;">
            Privacy Policy & <span class="gradient-text">Data Protection</span>
        </h1>
        <p style="color: var(--text-dim);">Last Revised: October 2026 | Effective Date: Immediate</p>
    </div>

    <div class="glass-card" style="padding: 2.5rem; display: flex; flex-direction: column; gap: 2rem; line-height: 1.8;">
        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">1. Introduction & Academic Scope</h2>
            <p>
                At <strong>Python4Physics</strong> (accessible from <a href="<?php echo $siteurl; ?>">https://python4physics.in</a>), the privacy and security of our students, educators, and academic visitors are of paramount importance. This Privacy Policy outlines the types of information collected, how it is handled, and our transparent adherence to international privacy standards.
            </p>
            <p>
                Python4Physics is an open educational initiative founded and curated by <strong>Dr. Alorika Chatterjee</strong> (The Heritage College, Kolkata) and <strong>Dr. Anirban Shaw</strong> (Dhruba Chand Halder College). We believe in strict data minimization and user privacy.
            </p>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">2. Client-Side Code Execution Guarantee (Pyodide WASM)</h2>
            <p>
                Unlike conventional programming websites that transmit user scripts to remote backend servers for evaluation, Python4Physics executes all interactive Python simulations entirely <strong>client-side</strong> using <strong>Pyodide (WebAssembly)</strong> directly in your local browser sandbox.
            </p>
            <ul style="padding-left: 1.5rem; margin-top: 0.5rem;">
                <li>Your student code modifications, experimental data arrays, and physics parameter adjustments are never uploaded to or logged on our web servers.</li>
                <li>Your intellectual proprietary work remains strictly on your local device.</li>
            </ul>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">3. Google AdSense & Third-Party Advertising Cookies</h2>
            <p>
                We partner with <strong>Google AdSense</strong> to display relevant educational, scientific, and technical advertisements to support website hosting, domain bandwidth, and curriculum development.
            </p>
            <ul style="padding-left: 1.5rem; margin-top: 0.5rem;">
                <li><strong>Third-Party Vendors:</strong> Google and other third-party vendors use cookies to serve ads based on a user's prior visits to this website or other websites on the Internet.</li>
                <li><strong>Google's Advertising Cookies:</strong> Google's use of advertising cookies enables it and its partners to serve ads to our users based on their visits to our site and/or other sites on the Internet.</li>
                <li><strong>Opting Out of Personalized Advertising:</strong> Users may opt out of personalized advertising by visiting:
                    <div style="margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.35rem;">
                        <a href="https://www.google.com/settings/ads" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.85rem;"></i> Google Ads Settings</a>
                        <a href="https://www.aboutads.info/choices/" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.85rem;"></i> Network Advertising Initiative / AboutAds Opt-Out</a>
                        <a href="https://policies.google.com/technologies/ads" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.85rem;"></i> How Google Uses Information From Sites or Apps That Use Our Services</a>
                    </div>
                </li>
            </ul>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">4. Web Server Log Files</h2>
            <p>
                Like most standard web hosting environments, Python4Physics follows standard operational procedures utilizing web server log files. These files log standard technical requests when visitors navigate the site. Logged parameters include:
            </p>
            <ul style="padding-left: 1.5rem; margin-top: 0.5rem;">
                <li>Internet Protocol (IP) addresses</li>
                <li>Browser user-agent and operating system type</li>
                <li>Internet Service Provider (ISP)</li>
                <li>Date, timestamp, and requested URL paths</li>
                <li>Referring / exit pages and HTTP response status codes</li>
            </ul>
            <p>
                This information is processed purely in the aggregate for technical server diagnostics, detecting automated malicious scrapers, measuring overall bandwidth demand, and ensuring uptime reliability. None of this data is linked to personally identifiable information.
            </p>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">5. Academic Analytics & Google Analytics 4 (GA4)</h2>
            <p>
                To understand which computational physics algorithms, numerical solvers, and assignments are most frequently studied by university students, we use <strong>Google Analytics 4 (GA4)</strong>. We have implemented:
            </p>
            <ul style="padding-left: 1.5rem; margin-top: 0.5rem;">
                <li><strong>Mandatory IP Anonymization</strong> (<code>anonymize_ip: true</code>) to prevent geographic tracking of specific individuals.</li>
                <li>Strict SameSite cookie policies to prevent cross-domain exploitation.</li>
                <li>Local storage is solely used to preserve your theme preference (Dark Mode vs Light Mode) and editor configuration across sessions.</li>
            </ul>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">6. Feedback Forms & Voluntary Submissions</h2>
            <p>
                If you choose to submit feedback, report a script bug, or request a new physics simulation via our <a href="<?php echo $siteurl; ?>feedback.php">Feedback Portal</a> or by emailing faculty:
            </p>
            <ul style="padding-left: 1.5rem; margin-top: 0.5rem;">
                <li>We collect your name, email address, feedback category, and message text solely to respond to your inquiry and incorporate corrections.</li>
                <li>We do not sell, rent, monetize, or disclose your contact information to commercial data brokers.</li>
            </ul>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">7. GDPR Compliance (European Economic Area Visitors)</h2>
            <p>
                Under the European General Data Protection Regulation (GDPR), users residing in the European Economic Area possess the following rights:
            </p>
            <ul style="padding-left: 1.5rem; margin-top: 0.5rem;">
                <li><strong>Right of Access:</strong> Request copies of any personal data held about you.</li>
                <li><strong>Right to Rectification:</strong> Request correction of inaccurate information.</li>
                <li><strong>Right to Erasure:</strong> Request deletion of your personal records ("Right to be Forgotten").</li>
                <li><strong>Right to Restrict or Object:</strong> Restrict processing or object to data processing.</li>
                <li><strong>Right to Data Portability:</strong> Request transmission of your data to another entity.</li>
            </ul>
            <p>
                To exercise any of these rights, please email our academic administration at <a href="mailto:anirbanshaw@python4physics.in">anirbanshaw@python4physics.in</a>. We fulfill valid requests within 30 days.
            </p>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">8. CCPA / CPRA Privacy Rights (California Residents)</h2>
            <p>
                Under the California Consumer Privacy Act (CCPA) and California Privacy Rights Act (CPRA), California residents are entitled to know what categories of personal data are collected, request deletion, and opt out of the sale or sharing of personal data.
            </p>
            <p>
                <strong>We Do Not Sell Personal Information:</strong> Python4Physics does not sell, trade, or transfer any visitor's personal information to third parties. If you wish to submit a CCPA inquiry, please contact us at <a href="mailto:anirbanshaw@python4physics.in">anirbanshaw@python4physics.in</a>.
            </p>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">9. Children's Online Privacy Protection (COPPA)</h2>
            <p>
                Python4Physics is directed towards university undergraduate and postgraduate students, researchers, and adult educators. We do not knowingly collect personal identifiable information from children under the age of 13. If you believe a child under 13 has submitted personal information on our portal, please contact us immediately, and we will promptly purge such data from our records.
            </p>
        </section>

        <section>
            <h2 style="color: var(--accent); font-size: 1.45rem; margin-bottom: 0.75rem;">10. Contacting Us About Privacy</h2>
            <p>
                If you have questions, feedback, or concerns regarding this Privacy Policy or our academic data practices, please contact:
            </p>
            <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid var(--card-border); padding: 1.25rem 1.5rem; border-radius: var(--radius-md); margin-top: 0.75rem;">
                <strong>Python4Physics Data Protection Officer</strong><br>
                Dr. Anirban Shaw & Dr. Alorika Chatterjee<br>
                Email: <a href="mailto:anirbanshaw@python4physics.in">anirbanshaw@python4physics.in</a> / <a href="mailto:alorika.chatterjee1@gmail.com">alorika.chatterjee1@gmail.com</a><br>
                Departments of Physics, Dhruba Chand Halder College & The Heritage College, West Bengal, India
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
