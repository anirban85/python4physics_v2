<?php
$page_title = "Developer REST API & Integrations";
$page_description = "Complete API documentation for Python4Physics: programmatically search, retrieve, and export 500+ computational physics algorithms in JSON, Python, and Jupyter Notebook formats.";
require_once __DIR__ . '/../include/header.php';
require_once __DIR__ . '/../include/navbar.php';
?>

<main class="container" style="padding-top: 3rem; padding-bottom: 5rem;">
    <!-- API Header -->
    <div style="text-align: center; max-width: 800px; margin: 0 auto 3.5rem auto;">
        <span class="badge badge-cyan" style="margin-bottom: 1rem;"><i class="fa-solid fa-code"></i> Developer Platform</span>
        <h1 style="font-size: 2.8rem; margin-bottom: 1rem;">Python4Physics <span class="gradient-text">REST API</span></h1>
        <p style="font-size: 1.15rem; line-height: 1.6;">
            Programmatically access over 500+ computational physics codes, mathematical algorithms, and scientific simulations. Integrate directly with JupyterLab, Python clients, and research platforms.
        </p>
    </div>

    <!-- API Overview Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 3.5rem;">
        <div class="glass-card">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(6, 182, 212, 0.15); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.4rem; margin-bottom: 1rem;">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            <h3>1. Global Search API</h3>
            <p>Full-text instant search across 345 Python algorithms, 49 GNUplot visualizations, and 115 LaTeX formulations.</p>
            <code>GET /api/search.php?q=Runge</code>
        </div>

        <div class="glass-card">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59, 130, 246, 0.15); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.4rem; margin-bottom: 1rem;">
                <i class="fa-solid fa-database"></i>
            </div>
            <h3>2. Programs Query API</h3>
            <p>Retrieve structured code, mathematical formulations, and explanations by language, chapter, and topic.</p>
            <code>GET /api/programs.php?lang=python&menu_id=12</code>
        </div>

        <div class="glass-card">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.15); display: flex; align-items: center; justify-content: center; color: var(--success); font-size: 1.4rem; margin-bottom: 1rem;">
                <i class="fa-solid fa-book-open"></i>
            </div>
            <h3>3. Jupyter (.ipynb) Exporter</h3>
            <p>Dynamically generate and download standard Jupyter Notebooks with markdown theory and runnable code cells.</p>
            <code>GET /api/export.php?id=350&format=ipynb</code>
        </div>
    </div>

    <!-- Interactive API Playground -->
    <section class="glass-card highlight" style="margin-bottom: 4rem;">
        <h2 style="margin-bottom: 1rem;"><i class="fa-solid fa-play" style="color: var(--accent); margin-right: 8px;"></i> Live API Explorer</h2>
        <p style="margin-bottom: 1.5rem;">Test API queries directly from your browser to preview real-time JSON responses:</p>

        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
            <select id="apiEndpointSelect" class="btn-modern btn-secondary" style="padding: 0.65rem 1rem;">
                <option value="api/search.php?q=matrix">GET /api/search.php?q=matrix</option>
                <option value="api/search.php?q=Runge">GET /api/search.php?q=Runge</option>
                <option value="api/programs.php?lang=python&menu_id=1&submenu_id=1">GET /api/programs.php (Python Ch 1)</option>
                <option value="api/programs.php?lang=gnuplot&menu_id=1">GET /api/programs.php (GNUplot Ch 1)</option>
                <option value="api/programs.php?lang=latex&menu_id=1">GET /api/programs.php (LaTeX Ch 1)</option>
            </select>
            <button type="button" id="apiTestBtn" class="btn-modern btn-primary">
                <i class="fa-solid fa-paper-plane"></i> Send Request
            </button>
        </div>

        <div style="position: relative;">
            <div style="position: absolute; top: 12px; right: 12px; z-index: 2;">
                <span id="apiStatusBadge" class="badge badge-cyan">Status: Ready</span>
            </div>
            <pre id="apiResponseView" class="console-output" style="max-height: 380px;"></pre>
        </div>
    </section>

    <!-- Code Examples -->
    <section class="glass-card">
        <h2 style="margin-bottom: 1.5rem;"><i class="fa-solid fa-code" style="color: var(--primary); margin-right: 8px;"></i> Client Code Snippets</h2>

        <div class="tabs-header">
            <button class="tab-btn active" onclick="switchSnippet('python', this)"><i class="fa-brands fa-python"></i> Python (requests)</button>
            <button class="tab-btn" onclick="switchSnippet('js', this)"><i class="fa-brands fa-js"></i> JavaScript (fetch)</button>
            <button class="tab-btn" onclick="switchSnippet('curl', this)"><i class="fa-solid fa-terminal"></i> cURL</button>
        </div>

        <div id="snippet-python" class="tab-content active">
            <pre class="console-output"><code>import requests

BASE_URL = "<?php echo $siteurl; ?>api"

# 1. Search for differential equation programs
search_res = requests.get(f"{BASE_URL}/search.php", params={"q": "Runge-Kutta"}).json()
print(f"Found {search_res['count']} results")

# 2. Fetch specific Python program
if search_res['results']:
    first_prog = search_res['results'][0]
    prog_id = first_prog['id']
    detail = requests.get(f"{BASE_URL}/programs.php", params={"lang": "python", "id": prog_id}).json()
    print("Code:\n", detail['program']['content'])

# 3. Download as Jupyter Notebook
notebook = requests.get(f"{BASE_URL}/export.php", params={"id": prog_id, "format": "ipynb"})
with open("simulation.ipynb", "wb") as f:
    f.write(notebook.content)
print("Saved simulation.ipynb!")</code></pre>
        </div>

        <div id="snippet-js" class="tab-content">
            <pre class="console-output"><code>const baseUrl = "<?php echo $siteurl; ?>api";

// Search algorithms
async function searchPhysics(query) {
    const res = await fetch(`${baseUrl}/search.php?q=${encodeURIComponent(query)}`);
    const data = await res.json();
    console.log("Matching algorithms:", data.results);
    return data.results;
}

searchPhysics("Fourier");</code></pre>
        </div>

        <div id="snippet-curl" class="tab-content">
            <pre class="console-output"><code># Search across 509 programs
curl "<?php echo $siteurl; ?>api/search.php?q=harmonic"

# Fetch Python Chapter 12 ODE programs
curl "<?php echo $siteurl; ?>api/programs.php?lang=python&menu_id=12"

# Export Program #350 as Jupyter Notebook (.ipynb)
curl -O -J "<?php echo $siteurl; ?>api/export.php?id=350&format=ipynb"</code></pre>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('apiTestBtn');
    const select = document.getElementById('apiEndpointSelect');
    const view = document.getElementById('apiResponseView');
    const badge = document.getElementById('apiStatusBadge');

    btn.addEventListener('click', async function() {
        const endpoint = select.value;
        badge.className = 'badge badge-amber';
        badge.innerText = 'Requesting...';
        view.innerText = 'Fetching ' + endpoint + '...';

        try {
            const start = performance.now();
            const res = await fetch('<?php echo $siteurl; ?>' + endpoint);
            const json = await res.json();
            const elapsed = (performance.now() - start).toFixed(1);

            badge.className = 'badge badge-emerald';
            badge.innerText = `HTTP ${res.status} (${elapsed}ms)`;
            view.innerText = JSON.stringify(json, null, 2);
        } catch (e) {
            badge.className = 'badge badge-purple';
            badge.innerText = 'Error';
            view.innerText = 'Error: ' + e.message;
        }
    });

    // Trigger initial test
    btn.click();
});

function switchSnippet(id, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('snippet-' + id).classList.add('active');
}
</script>

<?php require_once __DIR__ . '/../include/footer.php'; ?>
