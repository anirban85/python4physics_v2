<?php
/**
 * Python4Physics - Modern Academic Footer
 */
if (!isset($siteurl)) {
    require_once __DIR__ . '/../site_config.php';
}
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand & Mission -->
            <div class="footer-brand">
                <a href="<?php echo $siteurl; ?>" class="brand-link" style="margin-bottom: 1rem; display: inline-flex;">
                    <i class="fa-brands fa-python" style="color: var(--accent); font-size: 1.6rem;"></i>
                    <span>Python<span style="color: var(--accent);">4</span>Physics</span>
                </a>
                <p style="font-size: 0.92rem; max-width: 380px; line-height: 1.6;">
                    An open, globally accessible computational physics portal for undergraduate and postgraduate students, researchers, and educators worldwide.
                </p>
                <div style="display: flex; gap: 0.75rem; margin-top: 1.25rem;">
                    <a href="https://github.com/anirbanshaw/python4physics" target="_blank" rel="noopener" class="btn-modern btn-secondary btn-sm" aria-label="GitHub Repository">
                        <i class="fa-brands fa-github"></i> GitHub
                    </a>
                    <a href="<?php echo $siteurl; ?>api/docs.php" class="btn-modern btn-secondary btn-sm">
                        <i class="fa-solid fa-code"></i> Developer API
                    </a>
                </div>
            </div>

            <!-- Curriculum Tracks -->
            <div class="footer-col">
                <h4>Learning Tracks</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo $siteurl; ?>program/python/program.php?menu_id=1&submenu_id=1">Python Foundations</a></li>
                    <li><a href="<?php echo $siteurl; ?>program/python/program.php?menu_id=7&submenu_id=1">NumPy & SciPy Physics</a></li>
                    <li><a href="<?php echo $siteurl; ?>program/python/program.php?menu_id=6&submenu_id=1">Differential Equations</a></li>
                    <li><a href="<?php echo $siteurl; ?>program/python/program.php?menu_id=18&submenu_id=1">Quantum Mechanics (TISE/TDSE)</a></li>
                    <li><a href="<?php echo $siteurl; ?>program/gnuplot/index.php">GNUplot Visualizations</a></li>
                    <li><a href="<?php echo $siteurl; ?>program/latex/index.php">LaTeX Research Writing</a></li>
                </ul>
            </div>

            <!-- Academic Resources -->
            <div class="footer-col">
                <h4>Academic Portal</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo $siteurl; ?>assignments.php">Interactive Problem Sets</a></li>
                    <li><a href="<?php echo $siteurl; ?>arduino.php">Arduino Lab Interfacing</a></li>
                    <li><a href="<?php echo $siteurl; ?>feedback.php">Submit Feedback</a></li>
                    <li><a href="<?php echo $siteurl; ?>contact.php">Faculty & Contact</a></li>
                    <li><a href="<?php echo $siteurl; ?>privacy.php">Privacy & Terms</a></li>
                    <li><a href="<?php echo $siteurl; ?>admin/index.php"><i class="fa-solid fa-lock" style="font-size: 0.75rem;"></i> Admin Portal</a></li>
                </ul>
            </div>

            <!-- Academic Authors Showcase -->
            <div class="footer-col">
                <h4>Created & Curated By</h4>
                <div class="faculty-pill">
                    <div class="faculty-pill-name">Dr. Alorika Chatterjee</div>
                    <div class="faculty-pill-affil">Assistant Professor, Dept of Physics<br>The Heritage College, Kolkata</div>
                </div>
                <div class="faculty-pill">
                    <div class="faculty-pill-name">Dr. Anirban Shaw</div>
                    <div class="faculty-pill-affil">Assistant Professor, Dept of Physics<br>Dhruba Chand Halder College</div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div>
                &copy; <?php echo date("Y"); ?> <strong>Python4Physics</strong>. All Rights Reserved. Authored by Dr. Alorika Chatterjee & Dr. Anirban Shaw.
            </div>
            <div style="display: flex; gap: 1.5rem;">
                <a href="<?php echo $siteurl; ?>privacy.php">Privacy Policy</a>
                <a href="<?php echo $siteurl; ?>contact.php">Contact</a>
                <a href="<?php echo $siteurl; ?>api/docs.php">REST API</a>
            </div>
        </div>
    </div>
</footer>

<!-- Include Global Search Modal on Every Page -->
<?php include __DIR__ . '/search_modal.php'; ?>

<!-- KaTeX Auto-Render Initializer -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof renderMathInElement === 'function') {
        renderMathInElement(document.body, {
            delimiters: [
                {left: '$$', right: '$$', display: true},
                {left: '$', right: '$', display: false},
                {left: '\\(', right: '\\)', display: false},
                {left: '\\[', right: '\\]', display: true}
            ],
            throwOnError: false
        });
    }
});
</script>

</body>
</html>
