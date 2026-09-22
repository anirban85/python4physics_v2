/**
 * Python4Physics - Pyodide Client-Side Execution Engine
 * Pyodide 0.27+ with auto-package loading, Matplotlib plot capture, execution timing, and Jupyter Notebook export.
 */
class PhysicsPyodideRunner {
  constructor() {
    this.pyodide = null;
    this.isLoading = false;
    this.isReady = false;
    this.loadedPackages = new Set();
    this.matplotlibBootstrapped = false;
  }

  async init(onStatus) {
    if (this.isReady) return this.pyodide;
    if (this.isLoading) {
      while (this.isLoading) {
        await new Promise(r => setTimeout(r, 100));
      }
      return this.pyodide;
    }

    this.isLoading = true;
    if (onStatus) onStatus("Initializing WebAssembly Python Engine (Pyodide 0.27)...");

    try {
      if (!window.loadPyodide) {
        await new Promise((resolve, reject) => {
          const script = document.createElement('script');
          script.src = "https://cdn.jsdelivr.net/pyodide/v0.27.0/full/pyodide.js";
          script.onload = resolve;
          script.onerror = () => reject(new Error("Failed to load Pyodide from CDN"));
          document.head.appendChild(script);
        });
      }

      this.pyodide = await window.loadPyodide({
        indexURL: "https://cdn.jsdelivr.net/pyodide/v0.27.0/full/"
      });

      if (onStatus) onStatus("Loading micropip...");
      await this.pyodide.loadPackage("micropip");

      this.isReady = true;
      if (onStatus) onStatus("Pyodide Engine Ready!");
      return this.pyodide;
    } catch (err) {
      console.error("Pyodide Init Error:", err);
      if (onStatus) onStatus("Failed to load Python engine. Please check internet connection.");
      throw err;
    } finally {
      this.isLoading = false;
    }
  }

  detectImports(code) {
    const pkgs = new Set();
    const regex = /^\s*(?:import|from)\s+([a-zA-Z0-9_]+)/gm;
    let match;
    while ((match = regex.exec(code)) !== null) {
      pkgs.add(match[1]);
    }
    return [...pkgs];
  }

  async bootstrapMatplotlib() {
    if (this.matplotlibBootstrapped) return;
    await this.pyodide.runPythonAsync(`
import sys, io, base64
_pyodide_captured_frames = []

def _save_frame(fig=None):
    from io import BytesIO
    import matplotlib.pyplot as plt
    if fig is None:
        fig = plt.gcf()
    buf = BytesIO()
    fig.canvas.draw()
    fig.savefig(buf, format="png", bbox_inches='tight', dpi=150)
    buf.seek(0)
    _pyodide_captured_frames.append(
        base64.b64encode(buf.read()).decode("ascii")
    )

def _reset_frames():
    _pyodide_captured_frames.clear()

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt

_orig_show = plt.show
def _show_capture(*a, **k):
    _save_frame(plt.gcf())
    plt.close('all')
plt.show = _show_capture

_orig_pause = plt.pause
def _pause_capture(interval=0.05):
    _save_frame(plt.gcf())
    return _orig_pause(0.01)
plt.pause = _pause_capture
`);
    this.matplotlibBootstrapped = true;
  }

  async loadRequiredPackages(code, onStatus) {
    const imports = this.detectImports(code);
    if (!imports.length) return;

    const stdlib = [
      "sys", "math", "random", "io", "time", "re", "itertools",
      "functools", "collections", "statistics", "typing", "pathlib", "cmath"
    ];

    for (const pkg of imports) {
      if (stdlib.includes(pkg) || this.loadedPackages.has(pkg)) continue;

      if (onStatus) onStatus(`Loading physics package: ${pkg}...`);

      if (["numpy", "scipy", "pandas", "sympy", "matplotlib"].includes(pkg)) {
        await this.pyodide.loadPackage(pkg);
        this.loadedPackages.add(pkg);
        if (pkg === "matplotlib") {
          await this.bootstrapMatplotlib();
        }
      } else {
        try {
          await this.pyodide.runPythonAsync(`
import micropip, asyncio
async def _install():
    await micropip.install("${pkg}")
asyncio.run(_install())
`);
          this.loadedPackages.add(pkg);
        } catch (e) {
          console.warn(`Could not install package ${pkg}:`, e);
        }
      }
    }
  }

  async run(code, options = {}) {
    const { onStatus, consoleEl, plotsEl, timeEl } = options;
    const startTime = performance.now();

    if (consoleEl) consoleEl.innerText = "";
    if (plotsEl) plotsEl.innerHTML = "";
    if (timeEl) timeEl.innerText = "";

    try {
      await this.init(onStatus);
      await this.loadRequiredPackages(code, onStatus);

      if (this.matplotlibBootstrapped) {
        await this.pyodide.runPythonAsync(`_reset_frames(); import matplotlib.pyplot as plt; plt.close('all')`);
      }

      if (onStatus) onStatus("Executing code...");

      const indented = code.split("\n").map(l => "    " + l).join("\n");
      const wrapper = `
import sys, io, traceback
_stdout, _stderr = io.StringIO(), io.StringIO()
_orig_out, _orig_err = sys.stdout, sys.stderr
sys.stdout, sys.stderr = _stdout, _stderr

try:
${indented}
except Exception:
    traceback.print_exc(file=_stderr)
finally:
    sys.stdout, sys.stderr = _orig_out, _orig_err
`;

      await this.pyodide.runPythonAsync(wrapper);

      // Extract stdout and stderr
      const stdout = await this.pyodide.runPythonAsync(`_stdout.getvalue()`);
      const stderr = await this.pyodide.runPythonAsync(`_stderr.getvalue()`);

      let outputText = "";
      if (stdout) outputText += stdout;
      if (stderr) outputText += (outputText ? "\n" : "") + "[Error Traceback]\n" + stderr;

      if (consoleEl) {
        consoleEl.innerText = outputText || "(Program executed successfully with no stdout output)";
      }

      // Check captured plots
      if (this.matplotlibBootstrapped) {
        // Also check if any open figures exist that weren't closed by show()
        await this.pyodide.runPythonAsync(`
if len(plt.get_fignums()) > 0 and len(_pyodide_captured_frames) == 0:
    _save_frame(plt.gcf())
    plt.close('all')
`);
        const frames = await this.pyodide.runPythonAsync(`_pyodide_captured_frames`);
        if (frames && frames.length > 0 && plotsEl) {
          plotsEl.innerHTML = "";
          frames.forEach((b64, idx) => {
            const card = document.createElement('div');
            card.className = 'plot-card';
            card.innerHTML = `
              <img src="data:image/png;base64,${b64}" class="plot-img" alt="Matplotlib Plot ${idx + 1}">
              <div class="plot-toolbar">
                <a href="data:image/png;base64,${b64}" download="physics_plot_${idx + 1}.png" class="btn-modern btn-secondary btn-sm">
                  <i class="fa-solid fa-download"></i> Download PNG
                </a>
              </div>
            `;
            plotsEl.appendChild(card);
          });
          // Switch to plots tab automatically if plots exist
          const plotTabBtn = document.querySelector('[data-tab="plots"]');
          if (plotTabBtn) plotTabBtn.click();
        }
      }

      const elapsed = ((performance.now() - startTime) / 1000).toFixed(3);
      if (timeEl) {
        timeEl.innerHTML = `<i class="fa-solid fa-bolt"></i> Executed in ${elapsed}s`;
      }
      if (onStatus) onStatus("Execution complete!");

      return { stdout, stderr, elapsed };
    } catch (err) {
      console.error("Execution error:", err);
      if (consoleEl) {
        consoleEl.innerText = `System Execution Error: ${err.message}`;
      }
      if (onStatus) onStatus("Execution failed.");
      throw err;
    }
  }

  /**
   * Generates a fully compliant Jupyter Notebook (.ipynb) structure
   */
  exportToJupyter(title, code, description = "") {
    const notebook = {
      cells: [
        {
          cell_type: "markdown",
          metadata: {},
          source: [
            `# ${title}\n`,
            `*Computational Physics with Python - Dr. Alorika Chatterjee & Dr. Anirban Shaw*\n\n`,
            description ? `${description}\n` : ""
          ]
        },
        {
          cell_type: "code",
          execution_count: null,
          metadata: {},
          outputs: [],
          source: code.split("\n").map((line, idx, arr) => idx === arr.length - 1 ? line : line + "\n")
        }
      ],
      metadata: {
        language_info: {
          name: "python",
          version: "3.11"
        },
        kernelspec: {
          display_name: "Python 3",
          language: "python",
          name: "python3"
        }
      },
      nbformat: 4,
      nbformat_minor: 5
    };

    const blob = new Blob([JSON.stringify(notebook, null, 2)], { type: "application/x-ipynb+json" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `${title.toLowerCase().replace(/[^a-z0-9]+/g, "_")}.ipynb`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  exportPythonScript(filename, code) {
    const blob = new Blob([code], { type: "text/x-python" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename.endsWith(".py") ? filename : `${filename}.py`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }
}

window.physicsRunner = new PhysicsPyodideRunner();
