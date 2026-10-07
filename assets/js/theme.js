/**
 * Python4Physics - Theme Management System
 * Supports dark mode (default) and light mode with instant persistence across all pages.
 */
(function () {
  const THEME_KEY = 'p4p_theme';

  function getPreferredTheme() {
    try {
      const saved = localStorage.getItem(THEME_KEY);
      if (saved === 'light' || saved === 'dark') return saved;
    } catch (e) {}
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
  }

  function updateThemeUI(theme) {
    const isDark = (theme === 'dark');
    document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
      const icon = btn.querySelector('i');
      if (icon) {
        if (isDark) {
          icon.className = 'fa-solid fa-moon';
          icon.style.color = '#38bdf8'; // Glowing cyan moon
        } else {
          icon.className = 'fa-solid fa-sun';
          icon.style.color = '#f59e0b'; // Radiant amber sun
        }
      }

      // Update text in label/span if present
      const label = btn.querySelector('.theme-toggle-label, .theme-text, .theme-mode-text, .theme-status-text');
      if (label) {
        if (btn.classList.contains('mobile-theme-toggle-btn') || label.classList.contains('theme-mode-text')) {
          label.textContent = isDark ? 'Dark Mode' : 'Light Mode';
        } else {
          label.textContent = isDark ? 'Dark' : 'Light';
        }
      }

      const title = isDark 
        ? 'Current: Dark Mode (Click to switch to Light Mode)' 
        : 'Current: Light Mode (Click to switch to Dark Mode)';
      btn.setAttribute('title', title);
      btn.setAttribute('aria-label', title);
      btn.setAttribute('data-active-theme', theme);
    });
  }

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    try {
      localStorage.setItem(THEME_KEY, theme);
    } catch (e) {}

    updateThemeUI(theme);

    try {
      window.dispatchEvent(new CustomEvent('p4p_theme_change', { detail: { theme } }));
    } catch (e) {}
  }

  // Early apply to <html data-theme="..."> to prevent flash of wrong theme
  const initialTheme = getPreferredTheme();
  document.documentElement.setAttribute('data-theme', initialTheme);

  // Global toggle function
  window.toggleTheme = function () {
    const current = document.documentElement.getAttribute('data-theme') || 'dark';
    const next = current === 'dark' ? 'light' : 'dark';
    applyTheme(next);
  };

  function initThemeListeners() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || getPreferredTheme();
    updateThemeUI(currentTheme);

    document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
      if (!btn.dataset.themeBound) {
        btn.dataset.themeBound = 'true';
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          window.toggleTheme();
        });
      }
    });
  }

  // Global Unified Fullscreen Mode Handler for Code Editors
  window.toggleFullscreen = function(wrapperId) {
    var el = document.getElementById(wrapperId);
    if (!el) return;

    var isNowFs = el.classList.toggle('fullscreen-mode');
    document.body.classList.toggle('p4p-fullscreen-active', isNowFs);

    // If entering fullscreen, ensure an exit button bar exists at the top
    if (isNowFs) {
      var existingBar = el.querySelector('.fullscreen-exit-bar');
      if (!existingBar) {
        var bar = document.createElement('div');
        bar.className = 'fullscreen-exit-bar';
        bar.innerHTML = '<div class="fullscreen-bar-title"><i class="fa-solid fa-code"></i> <span>Editor Fullscreen</span> <span class="fullscreen-esc-hint">(Press <kbd>Esc</kbd> to exit)</span></div><button type="button" class="fullscreen-exit-btn" title="Exit Fullscreen (Esc)"><i class="fa-solid fa-compress"></i> Exit Fullscreen</button>';
        var exitBtn = bar.querySelector('.fullscreen-exit-btn');
        if (exitBtn) {
          exitBtn.onclick = function(e) {
            e.stopPropagation();
            window.toggleFullscreen(wrapperId);
          };
        }
        el.insertBefore(bar, el.firstChild);
      }
    }

    // Find CodeMirror instance inside or associated with this wrapper
    var cm = null;
    var cmEl = el.querySelector('.CodeMirror');
    if (cmEl && cmEl.CodeMirror) {
      cm = cmEl.CodeMirror;
    } else {
      var pid = wrapperId.replace(/^(wrapper_|latex_wrapper_|gp_wrapper_)/, '');
      if (window.p4pEditors && window.p4pEditors[pid]) cm = window.p4pEditors[pid];
      else if (window.latexEditors && window.latexEditors[pid]) cm = window.latexEditors[pid];
      else if (window.gpEditors && window.gpEditors[pid]) cm = window.gpEditors[pid];
    }

    // Trigger immediate & throttled refreshes to eliminate measurement oscillation and screen flickering
    if (cm) {
      if (window.requestAnimationFrame) {
        window.requestAnimationFrame(function() {
          cm.refresh();
          if (isNowFs) cm.focus();
        });
      }
      setTimeout(function() {
        cm.refresh();
      }, 30);
      setTimeout(function() {
        cm.refresh();
      }, 150);
    }
  };

  // Global Esc key listener to cleanly exit fullscreen
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
      var fs = document.querySelector('.fullscreen-mode');
      if (fs && window.toggleFullscreen) {
        window.toggleFullscreen(fs.id);
      }
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initThemeListeners);
  } else {
    initThemeListeners();
  }
})();
