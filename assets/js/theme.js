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
  window.p4pToggleFullscreen = function(wrapperId) {
    var el = document.getElementById(wrapperId);
    if (!el) return;

    var isNowFs = el.classList.toggle('fullscreen-mode');
    document.body.classList.toggle('p4p-fullscreen-active', isNowFs);

    // If entering fullscreen, ensure high-visibility floating exit button exists
    if (isNowFs) {
      var existingBtn = el.querySelector('.fullscreen-exit-floating-btn');
      if (!existingBtn) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'fullscreen-exit-floating-btn';
        btn.title = 'Exit Fullscreen (Esc)';
        btn.innerHTML = '<i class="fa-solid fa-compress"></i> <span>Exit Fullscreen</span> <kbd>Esc</kbd>';
        btn.onclick = function(e) {
          e.stopPropagation();
          window.p4pToggleFullscreen(wrapperId);
        };
        el.appendChild(btn);
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

  // Alias toggleFullscreen to p4pToggleFullscreen for backward compatibility
  window.toggleFullscreen = window.p4pToggleFullscreen;

  // Global Esc key listener to cleanly exit fullscreen
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
      var fs = document.querySelector('.fullscreen-mode');
      if (fs && window.p4pToggleFullscreen) {
        window.p4pToggleFullscreen(fs.id);
      }
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initThemeListeners);
  } else {
    initThemeListeners();
  }
})();
