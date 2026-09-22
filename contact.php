<?php
/**
 * Python4Physics - Faculty & Contact
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';

$page_title = "Contact Faculty & Academic Leadership";
$page_description = "Connect with the creators of Python4Physics: Dr. Alorika Chatterjee (The Heritage College) and Dr. Anirban Shaw (Dhruba Chand Halder College).";

require_once __DIR__ . '/include/header.php';
require_once __DIR__ . '/include/navbar.php';
?>

<main class="container" style="padding-top: 3.5rem; padding-bottom: 5rem;">
    <div style="text-align: center; max-width: 800px; margin: 0 auto 3.5rem auto;">
        <span class="badge badge-cyan"><i class="fa-solid fa-graduation-cap"></i> Academic Profiles</span>
        <h1 style="font-size: 2.8rem; margin-top: 0.75rem; margin-bottom: 0.75rem;">Faculty & <span class="gradient-text">Contact Information</span></h1>
        <p style="font-size: 1.15rem; line-height: 1.6;">
            The Python4Physics project is an academic initiative conceived, authored, and maintained by physics faculty in West Bengal, India.
        </p>
    </div>

    <!-- Faculty Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 2rem; margin-bottom: 4rem;">
        
        <!-- Dr. Alorika Chatterjee -->
        <div class="glass-card" style="padding: 2.5rem 2rem;">
            <div style="display: flex; align-items: center; gap: 1.25rem; margin-bottom: 1.5rem;">
                <div style="width: 76px; height: 76px; border-radius: 50%; background: var(--accent-gradient); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 2rem; font-weight: 700; flex-shrink: 0; box-shadow: 0 6px 20px var(--accent-glow);">
                    AC
                </div>
                <div>
                    <h2 style="font-size: 1.55rem; margin-bottom: 0.25rem;">Dr. Alorika Chatterjee</h2>
                    <span class="badge badge-cyan">Assistant Professor</span>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.85rem; font-size: 0.95rem; margin-bottom: 2rem;">
                <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                    <i class="fa-solid fa-building-columns" style="color: var(--accent); margin-top: 4px;"></i>
                    <div>
                        <strong>Department of Physics</strong><br>
                        The Heritage College, Kolkata<br>
                        West Bengal, India
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <i class="fa-regular fa-envelope" style="color: var(--accent);"></i>
                    <a href="mailto:alorika.chatterjee1@gmail.com">alorika.chatterjee1@gmail.com</a>
                </div>

                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <i class="fa-solid fa-globe" style="color: var(--accent);"></i>
                    <a href="https://www.thc.edu.in/Faculty.aspx" target="_blank" rel="noopener">Heritage College Faculty Profile &rarr;</a>
                </div>
            </div>

            <div>
                <a href="mailto:alorika.chatterjee1@gmail.com" class="btn-modern btn-primary" style="width: 100%;">
                    <i class="fa-regular fa-paper-plane"></i> Send Direct Email
                </a>
            </div>
        </div>

        <!-- Dr. Anirban Shaw -->
        <div class="glass-card" style="padding: 2.5rem 2rem;">
            <div style="display: flex; align-items: center; gap: 1.25rem; margin-bottom: 1.5rem;">
                <div style="width: 76px; height: 76px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 2rem; font-weight: 700; flex-shrink: 0; box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);">
                    AS
                </div>
                <div>
                    <h2 style="font-size: 1.55rem; margin-bottom: 0.25rem;">Dr. Anirban Shaw</h2>
                    <span class="badge badge-blue">Assistant Professor</span>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.85rem; font-size: 0.95rem; margin-bottom: 2rem;">
                <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                    <i class="fa-solid fa-building-columns" style="color: var(--primary); margin-top: 4px;"></i>
                    <div>
                        <strong>Department of Physics</strong><br>
                        Dhruba Chand Halder College<br>
                        Dakshin Barasat, South 24 Parganas, WB, India
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <i class="fa-regular fa-envelope" style="color: var(--primary);"></i>
                    <a href="mailto:anirbanshaw@python4physics.in">anirbanshaw@python4physics.in</a>
                </div>

                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <i class="fa-solid fa-globe" style="color: var(--primary);"></i>
                    <a href="https://dchcollege.org/main/departments/details.php?faculty=1103" target="_blank" rel="noopener">DCH College Faculty Profile &rarr;</a>
                </div>
            </div>

            <div>
                <a href="mailto:anirbanshaw@python4physics.in" class="btn-modern btn-primary" style="width: 100%;">
                    <i class="fa-regular fa-paper-plane"></i> Send Direct Email
                </a>
            </div>
        </div>

    </div>

    <!-- Institutional Collaboration Section -->
    <section class="glass-card highlight" style="text-align: center; padding: 3rem 2rem;">
        <h3 style="font-size: 1.8rem; margin-bottom: 1rem;">Educational & Research Collaboration</h3>
        <p style="max-width: 720px; margin: 0 auto 1.5rem auto; font-size: 1.05rem;">
            Interested in adopting the Python4Physics curriculum for your university department, proposing new physics computational algorithms, or contributing problem sets?
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo $siteurl; ?>feedback.php" class="btn-modern btn-primary">
                <i class="fa-regular fa-comment-dots"></i> Submit Feedback & Suggestions
            </a>
            <a href="<?php echo $siteurl; ?>api/docs.php" class="btn-modern btn-secondary">
                <i class="fa-solid fa-code"></i> Explore Developer API
            </a>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
