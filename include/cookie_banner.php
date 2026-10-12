<?php
/**
 * Python4Physics - Interactive Cookie Consent & Privacy Preference Banner
 * Compliant with Google AdSense EU User Consent Policy, GDPR, and ePrivacy Directive.
 */
?>
<!-- Floating Cookie Consent Banner -->
<div id="p4pCookieBanner" class="p4p-cookie-banner" style="display: none;" role="region" aria-label="Cookie and Privacy Consent">
    <div class="p4p-cookie-content">
        <div class="p4p-cookie-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-shield-halved" style="color: var(--accent); font-size: 1.15rem;"></i>
                <span style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">Privacy & Cookie Preferences</span>
            </div>
            <button type="button" class="p4p-cookie-close-btn" onclick="p4pCookieDismiss()" aria-label="Dismiss">&times;</button>
        </div>
        <p style="font-size: 0.84rem; line-height: 1.5; color: var(--text-muted); margin: 8px 0 14px 0;">
            Python4Physics uses essential cookies to preserve your interactive simulation workspace and editor preferences. With your consent, we and our partners (such as <strong>Google AdSense</strong> and <strong>Google Analytics</strong>) also use cookies to display relevant educational advertisements and evaluate traffic to keep this portal completely free for students. For details, please review our <a href="<?php echo $siteurl ?? '/'; ?>privacy.php" style="color: var(--accent); text-decoration: underline;">Privacy Policy</a>.
        </p>
        <div class="p4p-cookie-actions">
            <button type="button" class="btn-modern btn-primary btn-sm" onclick="p4pCookieAcceptAll()">
                <i class="fa-solid fa-check"></i> Accept All
            </button>
            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="p4pCookieAcceptEssential()">
                Essential Only
            </button>
            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="p4pCookieOpenModal()">
                <i class="fa-solid fa-sliders"></i> Preferences
            </button>
        </div>
    </div>
</div>

<!-- Detailed Cookie Preferences Modal -->
<div id="p4pCookieModalBackdrop" class="p4p-cookie-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="p4pCookieModalTitle">
    <div class="p4p-cookie-modal">
        <div class="p4p-cookie-modal-header">
            <h3 id="p4pCookieModalTitle" style="margin: 0; font-size: 1.25rem; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-cookie-bite" style="color: var(--accent);"></i> Manage Cookie & Ad Preferences
            </h3>
            <button type="button" class="p4p-cookie-close-btn" onclick="p4pCookieCloseModal()" aria-label="Close modal">&times;</button>
        </div>
        
        <div class="p4p-cookie-modal-body">
            <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1.25rem;">
                Customize which cookies you wish to allow during your visit. Essential cookies cannot be disabled as they are required for computational physics script execution and basic site functionality.
            </p>

            <!-- 1. Strictly Necessary Cookies -->
            <div class="p4p-cookie-category">
                <div class="p4p-cookie-cat-head">
                    <div>
                        <strong>1. Strictly Necessary Cookies</strong>
                        <span class="badge badge-cyan" style="font-size: 0.68rem; margin-left: 6px;">Always Active</span>
                    </div>
                    <input type="checkbox" checked disabled>
                </div>
                <p style="font-size: 0.8rem; color: var(--text-dim); margin-top: 4px;">
                    Required for client-side Pyodide WebAssembly sandboxing, theme preservation (Dark/Light mode), and CSRF security tokens.
                </p>
            </div>

            <!-- 2. Performance & Analytics -->
            <div class="p4p-cookie-category">
                <div class="p4p-cookie-cat-head">
                    <div>
                        <strong>2. Academic Analytics & Metrics (GA4)</strong>
                    </div>
                    <label class="p4p-switch">
                        <input type="checkbox" id="p4pConsentAnalytics" checked>
                        <span class="p4p-slider"></span>
                    </label>
                </div>
                <p style="font-size: 0.8rem; color: var(--text-dim); margin-top: 4px;">
                    Helps us understand which physics algorithms and assignments are most frequently studied by university students. Anonymized IP addresses are used.
                </p>
            </div>

            <!-- 3. Advertising & Google AdSense -->
            <div class="p4p-cookie-category">
                <div class="p4p-cookie-cat-head">
                    <div>
                        <strong>3. Advertising & Google AdSense</strong>
                    </div>
                    <label class="p4p-switch">
                        <input type="checkbox" id="p4pConsentAds" checked>
                        <span class="p4p-slider"></span>
                    </label>
                </div>
                <p style="font-size: 0.8rem; color: var(--text-dim); margin-top: 4px;">
                    Google and third-party advertising partners display non-intrusive technical and educational advertisements that fund server hosting and bandwidth for free student access.
                </p>
            </div>
        </div>

        <div class="p4p-cookie-modal-footer">
            <button type="button" class="btn-modern btn-secondary btn-sm" onclick="p4pCookieCloseModal()">Cancel</button>
            <button type="button" class="btn-modern btn-primary btn-sm" onclick="p4pCookieSaveCustom()">
                <i class="fa-solid fa-floppy-disk"></i> Save Preferences
            </button>
        </div>
    </div>
</div>

<style>
/* Cookie Consent Banner Styling */
.p4p-cookie-banner {
    position: fixed;
    bottom: 20px;
    left: 20px;
    right: 20px;
    max-width: 580px;
    background: rgba(15, 23, 42, 0.94);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(56, 189, 248, 0.35);
    border-radius: 14px;
    padding: 1.15rem 1.35rem;
    box-shadow: 0 16px 45px rgba(0, 0, 0, 0.65), 0 0 1px rgba(255, 255, 255, 0.15);
    z-index: 99999;
    font-family: var(--font-main, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
    animation: p4pSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes p4pSlideUp {
    from { transform: translateY(40px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.p4p-cookie-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.p4p-cookie-close-btn {
    background: transparent;
    border: none;
    color: var(--text-dim);
    font-size: 1.35rem;
    cursor: pointer;
    line-height: 1;
    padding: 2px 6px;
    border-radius: 4px;
    transition: color 0.15s;
}
.p4p-cookie-close-btn:hover {
    color: #ffffff;
}
.p4p-cookie-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}

/* Modal Styling */
.p4p-cookie-modal-backdrop {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(8px);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.p4p-cookie-modal {
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 14px;
    max-width: 540px;
    width: 100%;
    padding: 1.5rem;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.85);
}
.p4p-cookie-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid #1e293b;
    margin-bottom: 1rem;
}
.p4p-cookie-category {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
}
.p4p-cookie-cat-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
}
.p4p-cookie-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 1rem;
    border-top: 1px solid #1e293b;
    margin-top: 1.25rem;
}

/* Toggle Switch */
.p4p-switch {
    position: relative;
    display: inline-block;
    width: 38px;
    height: 20px;
}
.p4p-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.p4p-slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #334155;
    transition: .25s;
    border-radius: 20px;
}
.p4p-slider:before {
    position: absolute;
    content: "";
    height: 14px;
    width: 14px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .25s;
    border-radius: 50%;
}
.p4p-switch input:checked + .p4p-slider {
    background-color: #0284c7;
}
.p4p-switch input:checked + .p4p-slider:before {
    transform: translateX(18px);
}
</style>

<script>
(function() {
    function getConsent() {
        try {
            return JSON.parse(localStorage.getItem('p4p_cookie_consent'));
        } catch(e) {
            return null;
        }
    }

    function setConsent(settings) {
        try {
            localStorage.setItem('p4p_cookie_consent', JSON.stringify({
                timestamp: Date.now(),
                analytics: !!settings.analytics,
                ads: !!settings.ads
            }));
        } catch(e) {}
    }

    window.p4pCookieAcceptAll = function() {
        setConsent({ analytics: true, ads: true });
        p4pCookieHideBanner();
    };

    window.p4pCookieAcceptEssential = function() {
        setConsent({ analytics: false, ads: false });
        p4pCookieHideBanner();
    };

    window.p4pCookieDismiss = function() {
        setConsent({ analytics: true, ads: true });
        p4pCookieHideBanner();
    };

    window.p4pCookieSaveCustom = function() {
        var aBox = document.getElementById('p4pConsentAnalytics');
        var adBox = document.getElementById('p4pConsentAds');
        setConsent({
            analytics: aBox ? aBox.checked : true,
            ads: adBox ? adBox.checked : true
        });
        p4pCookieCloseModal();
        p4pCookieHideBanner();
    };

    window.p4pCookieHideBanner = function() {
        var b = document.getElementById('p4pCookieBanner');
        if (b) b.style.display = 'none';
    };

    window.p4pCookieOpenModal = function() {
        var m = document.getElementById('p4pCookieModalBackdrop');
        if (m) m.style.display = 'flex';
    };

    window.p4pCookieCloseModal = function() {
        var m = document.getElementById('p4pCookieModalBackdrop');
        if (m) m.style.display = 'none';
    };

    window.openCookieSettings = function() {
        var c = getConsent() || { analytics: true, ads: true };
        var aBox = document.getElementById('p4pConsentAnalytics');
        var adBox = document.getElementById('p4pConsentAds');
        if (aBox) aBox.checked = c.analytics !== false;
        if (adBox) adBox.checked = c.ads !== false;
        p4pCookieOpenModal();
    };

    document.addEventListener('DOMContentLoaded', function() {
        var consent = getConsent();
        if (!consent) {
            var banner = document.getElementById('p4pCookieBanner');
            if (banner) {
                setTimeout(function() {
                    banner.style.display = 'block';
                }, 800);
            }
        }
    });
})();
</script>
