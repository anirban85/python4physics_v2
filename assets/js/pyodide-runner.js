/**
 * Python4Physics - Pyodide Client-Side Execution Engine
 * Pyodide 0.27+ with auto-package loading, Matplotlib plot capture, execution timing, and Jupyter Notebook export.
 */
/**
 * PhysicsAnimationPlayer - Smooth Client-Side Simulation Playback
 * Supports live scrubbing, play/pause, speed controls, looping, and snapshot exports.
 */
class PhysicsAnimationPlayer {
  constructor(pid, frames, containerEl) {
    this.pid = pid;
    this.frames = frames;
    this.containerEl = containerEl;
    this.currentIndex = 0;
    this.isPlaying = true;
    this.isLooping = true;
    this.delayMs = 35; // Default ~28 fps for smooth physical animation
    this.timer = null;
    this.render();
  }

  render() {
    this.containerEl.innerHTML = "";
    
    const card = document.createElement("div");
    card.className = "animation-player-card";
    card.id = `anim_card_${this.pid}`;

    card.innerHTML = `
      <div class="anim-player-header">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span class="badge badge-emerald" style="display: flex; align-items: center; gap: 5px; font-size: 0.78rem;">
            <i class="fa-solid fa-play"></i> Animated Simulation
          </span>
          <span class="anim-status-pill" id="anim_status_${this.pid}">Playing</span>
        </div>
        <div class="anim-frame-counter">
          Step / Frame: <strong id="anim_current_frame_${this.pid}">1</strong> / <span>${this.frames.length}</span>
        </div>
      </div>

      <div class="anim-screen-wrapper">
        <img id="anim_img_${this.pid}" src="data:image/png;base64,${this.frames[0]}" class="plot-img animated-plot-display" alt="Physics Simulation Step 1">
      </div>

      <div class="anim-timeline">
        <input type="range" min="0" max="${this.frames.length - 1}" value="0" class="anim-scrubber" id="anim_scrubber_${this.pid}" title="Drag to scrub through simulation steps">
      </div>

      <div class="anim-controls-toolbar">
        <div class="anim-btn-group">
          <button type="button" class="btn-modern btn-primary btn-sm" id="anim_toggle_${this.pid}" title="Play / Pause Animation">
            <i class="fa-solid fa-pause"></i> <span id="anim_toggle_txt_${this.pid}">Pause</span>
          </button>
          <button type="button" class="btn-modern btn-secondary btn-sm" id="anim_stop_${this.pid}" title="Stop Animation & Reset to Step 1">
            <i class="fa-solid fa-stop"></i> Stop
          </button>
          <button type="button" class="btn-modern btn-secondary btn-sm" id="anim_replay_${this.pid}" title="Replay Simulation from Beginning">
            <i class="fa-solid fa-rotate-left"></i> Replay
          </button>
        </div>

        <div style="display: flex; align-items: center; gap: 6px;">
          <span style="font-size: 0.78rem; color: var(--text-dim);"><i class="fa-solid fa-gauge-high"></i> Speed:</span>
          <select class="btn-modern btn-secondary btn-sm" id="anim_speed_${this.pid}" style="padding: 2px 6px; font-size: 0.78rem;">
            <option value="75">0.5x</option>
            <option value="35" selected>1.0x</option>
            <option value="18">2.0x</option>
            <option value="8">Fast</option>
          </select>
        </div>

        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
          <button type="button" class="btn-modern btn-secondary btn-sm" id="anim_loop_${this.pid}" title="Toggle Continuous Looping">
            <i class="fa-solid fa-repeat"></i> <span id="anim_loop_txt_${this.pid}">Loop: On</span>
          </button>
          <a href="data:image/png;base64,${this.frames[0]}" download="physics_step_1.png" class="btn-modern btn-secondary btn-sm" id="anim_dl_frame_${this.pid}" title="Download Current Step as PNG">
            <i class="fa-solid fa-camera"></i> Snapshot
          </a>
          <a href="data:image/png;base64,${this.frames[this.frames.length - 1]}" download="physics_final_plot.png" class="btn-modern btn-secondary btn-sm" title="Download Final Completed Plot">
            <i class="fa-solid fa-download"></i> Final Plot
          </a>
        </div>
      </div>
    `;

    this.containerEl.appendChild(card);
    this.bindEvents();
    this.play();
  }

  bindEvents() {
    const toggleBtn = document.getElementById(`anim_toggle_${this.pid}`);
    const stopBtn = document.getElementById(`anim_stop_${this.pid}`);
    const replayBtn = document.getElementById(`anim_replay_${this.pid}`);
    const scrubber = document.getElementById(`anim_scrubber_${this.pid}`);
    const speedSelect = document.getElementById(`anim_speed_${this.pid}`);
    const loopBtn = document.getElementById(`anim_loop_${this.pid}`);

    if (toggleBtn) {
      toggleBtn.addEventListener("click", () => {
        if (this.isPlaying) this.pause();
        else this.play();
      });
    }

    if (stopBtn) {
      stopBtn.addEventListener("click", () => {
        this.stop();
      });
    }

    if (replayBtn) {
      replayBtn.addEventListener("click", () => {
        this.replay();
      });
    }

    if (scrubber) {
      scrubber.addEventListener("input", (e) => {
        this.pause();
        this.goToFrame(parseInt(e.target.value, 10));
      });
    }

    if (speedSelect) {
      speedSelect.addEventListener("change", (e) => {
        this.delayMs = parseInt(e.target.value, 10);
      });
    }

    if (loopBtn) {
      loopBtn.addEventListener("click", () => {
        this.isLooping = !this.isLooping;
        const txt = document.getElementById(`anim_loop_txt_${this.pid}`);
        if (txt) txt.innerText = this.isLooping ? "Loop: On" : "Loop: Off";
        loopBtn.classList.toggle("btn-primary", this.isLooping);
        loopBtn.classList.toggle("btn-secondary", !this.isLooping);
      });
    }
  }

  play() {
    this.isPlaying = true;
    if (this.timer) clearTimeout(this.timer);
    
    const toggleBtn = document.getElementById(`anim_toggle_${this.pid}`);
    const statusPill = document.getElementById(`anim_status_${this.pid}`);

    if (toggleBtn) toggleBtn.innerHTML = '<i class="fa-solid fa-pause"></i> <span id="anim_toggle_txt_' + this.pid + '">Pause</span>';
    if (statusPill) {
      statusPill.innerText = "Playing";
      statusPill.className = "anim-status-pill";
    }

    this.tick();
  }

  pause() {
    this.isPlaying = false;
    if (this.timer) clearTimeout(this.timer);
    this.timer = null;

    const toggleBtn = document.getElementById(`anim_toggle_${this.pid}`);
    const statusPill = document.getElementById(`anim_status_${this.pid}`);

    if (toggleBtn) toggleBtn.innerHTML = '<i class="fa-solid fa-play"></i> <span id="anim_toggle_txt_' + this.pid + '">Play</span>';
    if (statusPill) {
      statusPill.innerText = "Paused";
      statusPill.className = "anim-status-pill paused";
    }
  }

  stop() {
    this.pause();
    this.goToFrame(0);
    const statusPill = document.getElementById(`anim_status_${this.pid}`);
    if (statusPill) {
      statusPill.innerText = "Stopped";
      statusPill.className = "anim-status-pill stopped";
    }
  }

  replay() {
    this.goToFrame(0);
    this.play();
  }

  goToFrame(idx) {
    if (idx < 0) idx = 0;
    if (idx >= this.frames.length) idx = this.frames.length - 1;
    this.currentIndex = idx;

    const img = document.getElementById(`anim_img_${this.pid}`);
    const scrubber = document.getElementById(`anim_scrubber_${this.pid}`);
    const counter = document.getElementById(`anim_current_frame_${this.pid}`);
    const dlSnapshot = document.getElementById(`anim_dl_frame_${this.pid}`);

    if (img && this.frames[idx]) {
      img.src = `data:image/png;base64,${this.frames[idx]}`;
    }
    if (scrubber) scrubber.value = idx;
    if (counter) counter.innerText = idx + 1;
    if (dlSnapshot && this.frames[idx]) {
      dlSnapshot.href = `data:image/png;base64,${this.frames[idx]}`;
      dlSnapshot.download = `physics_${this.pid}_step_${idx + 1}.png`;
    }
  }

  tick() {
    if (!this.isPlaying) return;

    this.currentIndex++;
    if (this.currentIndex >= this.frames.length) {
      if (this.isLooping) {
        this.currentIndex = 0;
      } else {
        this.currentIndex = this.frames.length - 1;
        this.goToFrame(this.currentIndex);
        this.pause();
        const statusPill = document.getElementById(`anim_status_${this.pid}`);
        if (statusPill) {
          statusPill.innerText = "Completed";
          statusPill.className = "anim-status-pill";
        }
        return;
      }
    }

    this.goToFrame(this.currentIndex);
    this.timer = setTimeout(() => this.tick(), this.delayMs);
  }

  destroy() {
    this.pause();
    if (this.containerEl) this.containerEl.innerHTML = "";
  }
}

class PhysicsPyodideRunner {
  constructor() {
    this.pyodide = null;
    this.isLoading = false;
    this.isReady = false;
    this.loadedPackages = new Set();
    this.matplotlibBootstrapped = false;
    this.animationPlayers = {};
  }

  stopAnimation(pid) {
    if (pid && this.animationPlayers && this.animationPlayers[pid]) {
      this.animationPlayers[pid].stop();
    } else if (!pid && this.animationPlayers) {
      Object.values(this.animationPlayers).forEach(p => p.stop());
    }
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
_pyodide_is_animation = False

def _save_frame(fig=None, is_anim=False):
    global _pyodide_is_animation
    if is_anim:
        _pyodide_is_animation = True
    if len(_pyodide_captured_frames) >= 300:
        return
    try:
        from io import BytesIO
        import matplotlib.pyplot as plt
        if fig is None:
            fig = plt.gcf()
        buf = BytesIO()
        if is_anim:
            fig.savefig(buf, format="png", dpi=100)
        else:
            fig.savefig(buf, format="png", bbox_inches='tight', dpi=130)
        buf.seek(0)
        _pyodide_captured_frames.append(
            base64.b64encode(buf.read()).decode("ascii")
        )
    except Exception as e:
        pass

def _reset_frames():
    global _pyodide_is_animation
    _pyodide_captured_frames.clear()
    _pyodide_is_animation = False

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt

_orig_show = plt.show
def _show_capture(*a, **k):
    _save_frame(plt.gcf(), is_anim=False)
    plt.close('all')
plt.show = _show_capture

_orig_pause = plt.pause
def _pause_capture(interval=0.01):
    _save_frame(plt.gcf(), is_anim=True)
    return
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
    _save_frame(plt.gcf(), is_anim=False)
    plt.close('all')
`);
        const rawFrames = await this.pyodide.runPythonAsync(`_pyodide_captured_frames`);
        const frames = (rawFrames && typeof rawFrames.toJs === 'function')
          ? rawFrames.toJs()
          : (Array.isArray(rawFrames) ? rawFrames : []);
        if (rawFrames && typeof rawFrames.destroy === 'function') {
          rawFrames.destroy();
        }

        const isAnim = Boolean(await this.pyodide.runPythonAsync(`_pyodide_is_animation`));
        const pid = options.pid || 'default';

        if (this.animationPlayers && this.animationPlayers[pid]) {
          this.animationPlayers[pid].destroy();
          delete this.animationPlayers[pid];
        }

        if (frames && frames.length > 0 && plotsEl) {
          plotsEl.innerHTML = "";

          if (isAnim || frames.length > 1) {
            // RENDER ANIMATED SIMULATION VIEW
            this.animationPlayers = this.animationPlayers || {};
            this.animationPlayers[pid] = new PhysicsAnimationPlayer(pid, frames, plotsEl);
          } else {
            // RENDER SINGLE STATIC PLOT
            const b64 = frames[0];
            const card = document.createElement('div');
            card.className = 'plot-card';
            card.innerHTML = `
              <img src="data:image/png;base64,${b64}" class="plot-img" alt="Matplotlib Plot">
              <div class="plot-toolbar">
                <a href="data:image/png;base64,${b64}" download="physics_plot.png" class="btn-modern btn-secondary btn-sm">
                  <i class="fa-solid fa-download"></i> Download PNG
                </a>
              </div>
            `;
            plotsEl.appendChild(card);
          }

          if (typeof options.onPlotsReady === 'function') {
            options.onPlotsReady(frames.length, (isAnim || frames.length > 1));
          }
        }
      }

      const elapsed = ((performance.now() - startTime) / 1000).toFixed(3);
      if (timeEl) {
        timeEl.innerHTML = `<i class="fa-solid fa-bolt"></i> Executed in ${elapsed}s`;
      }
      if (onStatus) onStatus("Execution complete!");

      if (typeof window.trackPhysicsEvent === 'function') {
        window.trackPhysicsEvent('python_simulation_execute', {
          event_category: 'Pyodide WebAssembly',
          execution_time_seconds: parseFloat(elapsed)
        });
      }

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

    if (typeof window.trackPhysicsEvent === 'function') {
      window.trackPhysicsEvent('jupyter_export', {
        event_category: 'Physics Notebook',
        event_label: title
      });
    }
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

    if (typeof window.trackPhysicsEvent === 'function') {
      window.trackPhysicsEvent('script_download', {
        event_category: 'Python Source',
        event_label: filename
      });
    }
  }
}

window.physicsRunner = new PhysicsPyodideRunner();
