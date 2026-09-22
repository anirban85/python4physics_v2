/**
 * Python4Physics - Global Search Palette (Ctrl+K / Cmd+K)
 * Real-time debounced search across all 509 programs in Python, Gnuplot, LaTeX.
 */
(function () {
  let searchBackdrop = null;
  let searchInput = null;
  let resultsContainer = null;
  let debounceTimeout = null;
  let selectedIndex = -1;
  let currentResults = [];

  function initSearch() {
    searchBackdrop = document.getElementById('searchModalBackdrop');
    if (!searchBackdrop) return;

    searchInput = document.getElementById('globalSearchInput');
    resultsContainer = document.getElementById('searchResultsContainer');

    // Shortcut: Ctrl+K or Cmd+K
    document.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        openSearch();
      } else if (e.key === 'Escape' && searchBackdrop.classList.contains('active')) {
        closeSearch();
      } else if (searchBackdrop.classList.contains('active')) {
        handleKeyboardNav(e);
      }
    });

    // Close on backdrop click
    searchBackdrop.addEventListener('click', (e) => {
      if (e.target === searchBackdrop) closeSearch();
    });

    // Close buttons
    document.querySelectorAll('[data-close-search]').forEach(btn => {
      btn.addEventListener('click', closeSearch);
    });

    // Open triggers
    document.querySelectorAll('.trigger-global-search').forEach(btn => {
      btn.addEventListener('click', openSearch);
    });

    // Input debounce
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimeout);
        const query = e.target.value.trim();
        if (query.length < 2) {
          resultsContainer.innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--text-dim);">Type at least 2 characters to search programs, topics, or formulas...</div>';
          currentResults = [];
          selectedIndex = -1;
          return;
        }
        debounceTimeout = setTimeout(() => executeSearch(query), 200);
      });
    }
  }

  function openSearch() {
    if (!searchBackdrop) return;
    searchBackdrop.classList.add('active');
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
      if (searchInput) {
        searchInput.focus();
        searchInput.select();
      }
    }, 50);
  }

  function closeSearch() {
    if (!searchBackdrop) return;
    searchBackdrop.classList.remove('active');
    document.body.style.overflow = '';
  }

  async function executeSearch(query) {
    resultsContainer.innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--text-muted);"><i class="fa-solid fa-spinner fa-spin" style="margin-right: 8px;"></i> Searching across 500+ physics algorithms...</div>';

    try {
      const baseUrl = window.p4p_siteurl || '';
      const response = await fetch(`${baseUrl}api/search.php?q=${encodeURIComponent(query)}`);
      const data = await response.json();

      if (!data.success || !data.results || data.results.length === 0) {
        resultsContainer.innerHTML = `<div style="padding: 2.5rem 1rem; text-align: center; color: var(--text-dim);"><i class="fa-solid fa-circle-question fa-2x" style="margin-bottom: 0.75rem; display: block; opacity: 0.5;"></i> No programs found matching "<strong>${escapeHtml(query)}</strong>". Try searching for keywords like <em>matrix</em>, <em>Runge-Kutta</em>, <em>plot</em>, or <em>harmonic</em>.</div>`;
        currentResults = [];
        selectedIndex = -1;
        return;
      }

      currentResults = data.results;
      selectedIndex = 0;
      renderResults();
    } catch (err) {
      console.error('Search error:', err);
      resultsContainer.innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--danger);">Error performing search. Please try again.</div>';
    }
  }

  function renderResults() {
    let html = '';
    currentResults.forEach((item, index) => {
      const isSelected = index === selectedIndex ? 'selected' : '';
      const langIcon = item.lang === 'python' ? 'fa-brands fa-python' : (item.lang === 'gnuplot' ? 'fa-solid fa-chart-line' : 'fa-solid fa-file-code');
      const badgeClass = item.lang === 'python' ? 'badge-cyan' : (item.lang === 'gnuplot' ? 'badge-amber' : 'badge-blue');

      html += `
        <a href="${item.url}" class="search-result-item ${isSelected}" data-index="${index}">
          <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span class="badge ${badgeClass}"><i class="${langIcon}"></i> ${item.lang.toUpperCase()}</span>
            <div>
              <div class="search-result-title">${escapeHtml(item.title)}</div>
              <div class="search-result-meta">${escapeHtml(item.topic || '')} &bull; Program #${item.program_id}</div>
            </div>
          </div>
          <i class="fa-solid fa-chevron-right" style="font-size: 0.8rem; color: var(--text-dim);"></i>
        </a>
      `;
    });

    resultsContainer.innerHTML = html;
  }

  function handleKeyboardNav(e) {
    if (currentResults.length === 0) return;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      selectedIndex = (selectedIndex + 1) % currentResults.length;
      updateSelection();
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      selectedIndex = (selectedIndex - 1 + currentResults.length) % currentResults.length;
      updateSelection();
    } else if (e.key === 'Enter' && selectedIndex >= 0) {
      e.preventDefault();
      const selectedItem = currentResults[selectedIndex];
      if (selectedItem && selectedItem.url) {
        window.location.href = selectedItem.url;
      }
    }
  }

  function updateSelection() {
    const items = resultsContainer.querySelectorAll('.search-result-item');
    items.forEach((item, i) => {
      if (i === selectedIndex) {
        item.classList.add('selected');
        item.scrollIntoView({ block: 'nearest' });
      } else {
        item.classList.remove('selected');
      }
    });
  }

  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, m => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    })[m]);
  }

  window.openGlobalSearch = openSearch;
  window.closeGlobalSearch = closeSearch;

  document.addEventListener('DOMContentLoaded', initSearch);
})();
