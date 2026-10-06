<?php
/**
 * Python4Physics - Global Search Modal Component
 */
?>
<div id="searchModalBackdrop" class="search-modal-backdrop" role="dialog" aria-modal="true" aria-label="Search Programs">
    <div class="search-modal">
        <div class="search-input-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="globalSearchInput" class="search-input" placeholder="Search algorithms, equations, ODEs, quantum, matrices..." autocomplete="off">
            <button type="button" data-close-search class="btn-modern btn-secondary btn-sm" style="padding: 0.35rem 0.65rem;">
                <kbd style="font-size: 0.75rem;">ESC</kbd>
            </button>
        </div>
        <div id="searchResultsContainer" class="search-results-container">
            <div style="padding: 2.5rem 1rem; text-align: center; color: var(--text-dim);">
                <i class="fa-solid fa-bolt fa-2x" style="margin-bottom: 0.75rem; color: var(--accent); opacity: 0.7; display: block;"></i>
                Instant search across <strong>345 Python</strong>, <strong>49 GNUplot</strong>, and <strong>115 LaTeX</strong> physics algorithms.
            </div>
        </div>
        <div class="search-modal-footer">
            <div style="display: flex; gap: 1rem; align-items: center;">
                <span><kbd style="padding: 2px 6px; background: var(--bg-tertiary); border-radius: 4px; border: 1px solid var(--card-border);">↑</kbd> <kbd style="padding: 2px 6px; background: var(--bg-tertiary); border-radius: 4px; border: 1px solid var(--card-border);">↓</kbd> to navigate</span>
                <span><kbd style="padding: 2px 6px; background: var(--bg-tertiary); border-radius: 4px; border: 1px solid var(--card-border);">↵</kbd> to select</span>
                <span><kbd style="padding: 2px 6px; background: var(--bg-tertiary); border-radius: 4px; border: 1px solid var(--card-border);">ESC</kbd> to close</span>
            </div>
            <div>
                <span class="badge badge-cyan">Python4Physics API</span>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo $siteurl; ?>assets/js/search.js"></script>
