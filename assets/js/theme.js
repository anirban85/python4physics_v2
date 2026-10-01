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

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initThemeListeners);
  } else {
    initThemeListeners();
  }
})();
