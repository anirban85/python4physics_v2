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

/**
 * PhysicsSliderController - Bridge between Matplotlib Slider widgets and Web UI
 * Supports live parameter manipulation, debounced async updates, step buttons, auto-sweep, and snapshot exports.
 */
class PhysicsSliderController {
  constructor(pid, initialImageB64, sliders, containerEl, pyodideRunner) {
    this.pid = pid;
    this.currentImageB64 = initialImageB64;
    this.sliders = sliders; // array of { id, label, min, max, val, step, valinit }
    this.containerEl = containerEl;
    this.runner = pyodideRunner;
    this.isUpdating = false;
    this.pendingUpdate = null;
    this.autoSweepTimer = null;
    this.autoSweepDirection = 1;
    this.render();
  }

  render() {
    this.containerEl.innerHTML = "";

    const card = document.createElement("div");
    card.className = "interactive-slider-card";
    card.id = `slider_card_${this.pid}`;

    const slidersHtml = this.sliders.map(s => {
      const stepStr = (s.step || 0.01).toString();
      const decimals = stepStr.includes('.') ? Math.min(stepStr.split('.')[1].length, 6) : (s.step >= 1 ? 1 : 2);
      return `
        <div class="interactive-slider-row" id="slider_row_${this.pid}_${s.id}">
          <div class="interactive-slider-header">
            <div class="slider-label-group">
              <i class="fa-solid fa-sliders"></i>
              <label for="slider_input_${this.pid}_${s.id}" class="slider-title">${s.label || 'Parameter ' + (s.id + 1)}</label>
              <span class="slider-range-badge" title="Slider Range strictly from user's Python code">
                Range: [${s.min} &rarr; ${s.max}], &Delta;: ${s.step}
              </span>
            </div>
            <div class="slider-val-container">
              <label for="slider_num_${this.pid}_${s.id}" class="slider-val-label">Value:</label>
              <input type="number" 
                     class="slider-num-input" 
                     id="slider_num_${this.pid}_${s.id}"
                     min="${s.min}" 
                     max="${s.max}" 
                     step="${s.step}" 
                     value="${Number(s.val).toFixed(decimals)}" 
                     title="Direct numeric input (strictly between ${s.min} and ${s.max})">
              <span class="slider-val-badge" id="slider_val_badge_${this.pid}_${s.id}">
                ${s.label}: <strong>${Number(s.val).toFixed(decimals)}</strong>
              </span>
            </div>
          </div>
          <div class="interactive-slider-controls">
            <button type="button" class="btn-slider-step" id="slider_dec_${this.pid}_${s.id}" title="Step Down (-${s.step})">
              <i class="fa-solid fa-minus"></i>
            </button>
            <span class="slider-bound slider-min" title="Minimum bound from code: ${s.min}">Min: ${s.min}</span>
            <input type="range" class="interactive-range-input" 
                   id="slider_input_${this.pid}_${s.id}" 
                   min="${s.min}" max="${s.max}" step="${s.step}" value="${s.val}"
                   title="${s.label} Slider: [${s.min} to ${s.max}]">
            <span class="slider-bound slider-max" title="Maximum bound from code: ${s.max}">Max: ${s.max}</span>
            <button type="button" class="btn-slider-step" id="slider_inc_${this.pid}_${s.id}" title="Step Up (+${s.step})">
              <i class="fa-solid fa-plus"></i>
            </button>
          </div>
        </div>
      `;
    }).join("");

    card.innerHTML = `
      <div class="interactive-card-header">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span class="badge badge-primary" style="display: flex; align-items: center; gap: 5px; font-size: 0.78rem;">
            <i class="fa-solid fa-sliders"></i> Interactive Parameter Controls
          </span>
          <span class="slider-status-pill" id="slider_status_${this.pid}">Active</span>
        </div>
        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
          <button type="button" class="btn-modern btn-secondary btn-sm" id="slider_sweep_${this.pid}" title="Auto-sweep parameter dynamically within user's range">
            <i class="fa-solid fa-play"></i> <span id="slider_sweep_txt_${this.pid}">Auto Sweep</span>
          </button>
          <button type="button" class="btn-modern btn-secondary btn-sm" id="slider_reset_${this.pid}" title="Reset parameters to initial code values">
            <i class="fa-solid fa-rotate-left"></i> Reset
          </button>
          <a href="data:image/png;base64,${this.currentImageB64}" download="physics_interactive_plot.png" class="btn-modern btn-secondary btn-sm" id="slider_dl_${this.pid}" title="Download Plot PNG">
            <i class="fa-solid fa-download"></i> Save PNG
          </a>
        </div>
      </div>

      <div class="interactive-plot-screen">
        <img id="slider_plot_img_${this.pid}" src="data:image/png;base64,${this.currentImageB64}" class="plot-img interactive-plot-display" alt="Interactive Physics Plot">
      </div>

      <div class="interactive-sliders-panel">
        ${slidersHtml}
      </div>
    `;

    this.containerEl.appendChild(card);
    this.bindEvents();
  }

  bindEvents() {
    this.sliders.forEach(s => {
      const input = document.getElementById(`slider_input_${this.pid}_${s.id}`);
      const numInput = document.getElementById(`slider_num_${this.pid}_${s.id}`);
      const decBtn = document.getElementById(`slider_dec_${this.pid}_${s.id}`);
      const incBtn = document.getElementById(`slider_inc_${this.pid}_${s.id}`);
      const stepStr = (s.step || 0.01).toString();
      const decimals = stepStr.includes('.') ? Math.min(stepStr.split('.')[1].length, 6) : (s.step >= 1 ? 1 : 2);

      const applyValue = (rawVal) => {
        let val = parseFloat(rawVal);
        if (isNaN(val)) return;
        // Strictly clamp within user-defined range in code
        if (val < s.min) val = s.min;
        if (val > s.max) val = s.max;
        // Snap to step precision
        val = Math.round((val - s.min) / s.step) * s.step + s.min;
        val = parseFloat(val.toFixed(decimals));
        if (val < s.min) val = s.min;
        if (val > s.max) val = s.max;

        s.val = val;
        if (input) input.value = val;
        if (numInput) numInput.value = Number(val).toFixed(decimals);
        this.updateBadge(s, decimals);
        this.queueUpdate(s.id, val);
      };

      if (input) {
        input.addEventListener("input", (e) => {
          this.stopAutoSweep();
          let val = parseFloat(e.target.value);
          if (isNaN(val)) return;
          if (val < s.min) val = s.min;
          if (val > s.max) val = s.max;
          s.val = val;
          if (numInput) numInput.value = Number(val).toFixed(decimals);
          this.updateBadge(s, decimals);
          this.queueUpdate(s.id, val);
        });
      }

      if (numInput) {
        numInput.addEventListener("input", (e) => {
          this.stopAutoSweep();
          let val = parseFloat(e.target.value);
          if (isNaN(val)) return;
          if (val < s.min) val = s.min;
          if (val > s.max) val = s.max;
          s.val = val;
          if (input) input.value = val;
          this.updateBadge(s, decimals);
          this.queueUpdate(s.id, val);
        });

        numInput.addEventListener("change", (e) => {
          applyValue(e.target.value);
        });
      }

      if (decBtn && input) {
        decBtn.addEventListener("click", () => {
          this.stopAutoSweep();
          let cur = parseFloat(input.value);
          let val = cur - s.step;
          applyValue(val);
        });
      }

      if (incBtn && input) {
        incBtn.addEventListener("click", () => {
          this.stopAutoSweep();
          let cur = parseFloat(input.value);
          let val = cur + s.step;
          applyValue(val);
        });
      }
    });

    const resetBtn = document.getElementById(`slider_reset_${this.pid}`);
    if (resetBtn) {
      resetBtn.addEventListener("click", () => {
        this.stopAutoSweep();
        this.sliders.forEach(s => {
          const input = document.getElementById(`slider_input_${this.pid}_${s.id}`);
          const numInput = document.getElementById(`slider_num_${this.pid}_${s.id}`);
          const stepStr = (s.step || 0.01).toString();
          const decimals = stepStr.includes('.') ? Math.min(stepStr.split('.')[1].length, 6) : (s.step >= 1 ? 1 : 2);
          s.val = s.valinit;
          if (input) input.value = s.valinit;
          if (numInput) numInput.value = Number(s.valinit).toFixed(decimals);
          this.updateBadge(s, decimals);
          this.queueUpdate(s.id, s.valinit);
        });
      });
    }

    const sweepBtn = document.getElementById(`slider_sweep_${this.pid}`);
    if (sweepBtn) {
      sweepBtn.addEventListener("click", () => {
        if (this.autoSweepTimer) {
          this.stopAutoSweep();
        } else {
          this.startAutoSweep();
        }
      });
    }
  }

  updateBadge(s, decimals) {
    const badge = document.getElementById(`slider_val_badge_${this.pid}_${s.id}`);
    if (!badge) return;
    if (decimals === undefined) {
      const stepStr = (s.step || 0.01).toString();
      decimals = stepStr.includes('.') ? Math.min(stepStr.split('.')[1].length, 6) : (s.step >= 1 ? 1 : 2);
    }
    badge.innerHTML = `${s.label}: <strong>${Number(s.val).toFixed(decimals)}</strong>`;
  }

  startAutoSweep() {
    if (this.sliders.length === 0) return;
    const primarySlider = this.sliders[0];
    const sweepBtn = document.getElementById(`slider_sweep_${this.pid}`);
    const sweepTxt = document.getElementById(`slider_sweep_txt_${this.pid}`);
    const statusPill = document.getElementById(`slider_status_${this.pid}`);

    if (sweepBtn) {
      sweepBtn.classList.remove("btn-secondary");
      sweepBtn.classList.add("btn-primary");
    }
    if (sweepTxt) sweepTxt.innerText = "Pause Sweep";
    if (statusPill) {
      statusPill.innerText = "Sweeping";
      statusPill.className = "slider-status-pill sweeping";
    }

    const input = document.getElementById(`slider_input_${this.pid}_${primarySlider.id}`);
    const numInput = document.getElementById(`slider_num_${this.pid}_${primarySlider.id}`);
    const stepStr = (primarySlider.step || 0.01).toString();
    const decimals = stepStr.includes('.') ? Math.min(stepStr.split('.')[1].length, 6) : (primarySlider.step >= 1 ? 1 : 2);

    this.autoSweepTimer = setInterval(() => {
      let cur = parseFloat(input.value);
      let next = cur + (this.autoSweepDirection * primarySlider.step);
      next = Math.round((next - primarySlider.min) / primarySlider.step) * primarySlider.step + primarySlider.min;
      next = parseFloat(next.toFixed(decimals));

      if (next >= primarySlider.max) {
        next = primarySlider.max;
        this.autoSweepDirection = -1;
      } else if (next <= primarySlider.min) {
        next = primarySlider.min;
        this.autoSweepDirection = 1;
      }
      input.value = next;
      if (numInput) numInput.value = Number(next).toFixed(decimals);
      primarySlider.val = next;
      this.updateBadge(primarySlider, decimals);
      this.queueUpdate(primarySlider.id, next);
    }, 70);
  }

  stopAutoSweep() {
    if (this.autoSweepTimer) {
      clearInterval(this.autoSweepTimer);
      this.autoSweepTimer = null;
    }
    const sweepBtn = document.getElementById(`slider_sweep_${this.pid}`);
    const sweepTxt = document.getElementById(`slider_sweep_txt_${this.pid}`);
    const statusPill = document.getElementById(`slider_status_${this.pid}`);

    if (sweepBtn) {
      sweepBtn.classList.remove("btn-primary");
      sweepBtn.classList.add("btn-secondary");
    }
    if (sweepTxt) sweepTxt.innerText = "Auto Sweep";
    if (statusPill && statusPill.innerText === "Sweeping") {
      statusPill.innerText = "Active";
      statusPill.className = "slider-status-pill";
    }
  }

  queueUpdate(sliderId, val) {
    this.pendingUpdate = { id: sliderId, val: val };
    this.processQueue();
  }

  async processQueue() {
    if (this.isUpdating || !this.pendingUpdate) return;
    this.isUpdating = true;
    const target = this.pendingUpdate;
    this.pendingUpdate = null;

    const statusPill = document.getElementById(`slider_status_${this.pid}`);
    if (statusPill && !this.autoSweepTimer) {
      statusPill.innerText = "Updating...";
      statusPill.className = "slider-status-pill updating";
    }

    try {
      const b64 = await this.runner.updateSlider(target.id, target.val);
      if (b64) {
        this.currentImageB64 = b64;
        const img = document.getElementById(`slider_plot_img_${this.pid}`);
        if (img) img.src = `data:image/png;base64,${b64}`;
        const dl = document.getElementById(`slider_dl_${this.pid}`);
        if (dl) dl.href = `data:image/png;base64,${b64}`;
      }
      if (statusPill && !this.autoSweepTimer) {
        statusPill.innerText = "Active";
        statusPill.className = "slider-status-pill";
      }
    } catch (err) {
      console.error("Slider update error:", err);
      if (statusPill) {
        statusPill.innerText = "Error";
        statusPill.className = "slider-status-pill stopped";
      }
    } finally {
      this.isUpdating = false;
      if (this.pendingUpdate) {
        this.processQueue();
      }
    }
  }

  destroy() {
    this.stopAutoSweep();
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
    this.sliderControllers = {};
  }

  stopAnimation(pid) {
    if (pid && this.animationPlayers && this.animationPlayers[pid]) {
      this.animationPlayers[pid].stop();
    } else if (!pid && this.animationPlayers) {
      Object.values(this.animationPlayers).forEach(p => p.stop());
    }
  }

  stopSliders(pid) {
    if (pid && this.sliderControllers && this.sliderControllers[pid]) {
      this.sliderControllers[pid].stopAutoSweep();
    } else if (!pid && this.sliderControllers) {
      Object.values(this.sliderControllers).forEach(c => c.stopAutoSweep());
    }
  }

  async updateSlider(idx, val) {
    if (!this.pyodide || !this.isReady) return null;
    try {
      const res = await this.pyodide.runPythonAsync(`_update_slider_val(${idx}, ${val})`);
      return res || null;
    } catch (err) {
      console.error("Pyodide updateSlider error:", err);
      return null;
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
_registered_sliders = []

import matplotlib.widgets as mwidgets
if not hasattr(mwidgets.Slider, '_orig_init'):
    mwidgets.Slider._orig_init = mwidgets.Slider.__init__

def _hooked_slider_init(self, *args, **kwargs):
    mwidgets.Slider._orig_init(self, *args, **kwargs)
    if self not in _registered_sliders:
        _registered_sliders.append(self)

mwidgets.Slider.__init__ = _hooked_slider_init

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
    global _pyodide_is_animation, _registered_sliders
    _pyodide_captured_frames.clear()
    _pyodide_is_animation = False
    _registered_sliders.clear()
    try:
        import matplotlib.pyplot as plt
        plt.close('all')
    except Exception:
        pass

def _get_slider_metadata():
    import json
    import matplotlib.pyplot as plt
    open_figs = set(plt.get_fignums())
    meta = []
    active = []
    for s in _registered_sliders:
        try:
            fig = s.ax.figure if hasattr(s, 'ax') and hasattr(s.ax, 'figure') else None
            if fig is not None and hasattr(fig, 'number') and fig.number not in open_figs:
                continue
            idx = len(active)
            active.append(s)
            label = s.label.get_text() if (hasattr(s, 'label') and hasattr(s.label, 'get_text')) else f"Parameter {idx+1}"
            
            # Exact min and max from the user's code
            valmin = float(s.valmin) if hasattr(s, 'valmin') and s.valmin is not None else 0.0
            valmax = float(s.valmax) if hasattr(s, 'valmax') and s.valmax is not None else 10.0
            
            if hasattr(s, 'slidermin') and s.slidermin is not None:
                s_min = s.slidermin.val if hasattr(s.slidermin, 'val') else s.slidermin
                if s_min is not None:
                    valmin = max(valmin, float(s_min))
            if hasattr(s, 'slidermax') and s.slidermax is not None:
                s_max = s.slidermax.val if hasattr(s.slidermax, 'val') else s.slidermax
                if s_max is not None:
                    valmax = min(valmax, float(s_max))
                    
            val = float(s.val) if hasattr(s, 'val') and s.val is not None else valmin
            valinit = float(s.valinit) if hasattr(s, 'valinit') and s.valinit is not None else val
            
            # Handle valstep accurately: float or array-like
            valstep = None
            if hasattr(s, 'valstep') and s.valstep is not None:
                try:
                    valstep = float(s.valstep)
                except (TypeError, ValueError):
                    try:
                        valstep = float(s.valstep[1] - s.valstep[0])
                    except Exception:
                        valstep = None
                        
            step = valstep if valstep is not None else (abs(valmax - valmin) / 100.0 or 0.01)
            
            meta.append({
                "id": idx,
                "label": label,
                "min": valmin,
                "max": valmax,
                "val": val,
                "step": step,
                "valstep_explicit": (valstep is not None),
                "valinit": valinit
            })
        except Exception:
            pass
    _registered_sliders[:] = active
    return json.dumps(meta)

def _update_slider_val(idx, val):
    import base64
    from io import BytesIO
    import matplotlib.pyplot as plt
    if idx < 0 or idx >= len(_registered_sliders):
        return ""
    slider = _registered_sliders[idx]
    try:
        slider.set_val(float(val))
    except Exception as e:
        import sys
        print(f"[Slider Callback Error]: {e}", file=sys.stderr)
        return ""
    fig = slider.ax.figure if hasattr(slider, 'ax') and hasattr(slider.ax, 'figure') else None
    if fig is None:
        fig = plt.gcf()
    buf = BytesIO()
    fig.savefig(buf, format="png", bbox_inches='tight', dpi=130)
    buf.seek(0)
    return base64.b64encode(buf.read()).decode("ascii")

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt

_orig_show = plt.show
def _show_capture(*a, **k):
    _save_frame(plt.gcf(), is_anim=False)
    if not _registered_sliders:
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

      // Ensure matplotlib is bootstrapped whenever matplotlib or sliders are present
      const hasMatplotlib = this.loadedPackages.has("matplotlib") || code.includes("matplotlib") || code.includes("plt.") || code.includes("Slider");
      if (hasMatplotlib) {
        if (!this.loadedPackages.has("matplotlib")) {
          await this.pyodide.loadPackage("matplotlib");
          this.loadedPackages.add("matplotlib");
        }
        await this.bootstrapMatplotlib();
        await this.pyodide.runPythonAsync(`_reset_frames()`);
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
    if not _registered_sliders:
        plt.close('all')
`);
        const rawFrames = await this.pyodide.runPythonAsync(`_pyodide_captured_frames`);
        const frames = (rawFrames && typeof rawFrames.toJs === 'function')
          ? rawFrames.toJs()
          : (Array.isArray(rawFrames) ? rawFrames : []);
        if (rawFrames && typeof rawFrames.destroy === 'function') {
          rawFrames.destroy();
        }

        const rawSlidersJson = await this.pyodide.runPythonAsync(`_get_slider_metadata()`);
        let slidersMeta = [];
        try {
          slidersMeta = JSON.parse(rawSlidersJson || "[]");
        } catch(e) {
          slidersMeta = [];
        }

        const isAnim = Boolean(await this.pyodide.runPythonAsync(`_pyodide_is_animation`));
        const pid = options.pid || 'default';

        if (this.animationPlayers && this.animationPlayers[pid]) {
          this.animationPlayers[pid].destroy();
          delete this.animationPlayers[pid];
        }
        if (this.sliderControllers && this.sliderControllers[pid]) {
          this.sliderControllers[pid].destroy();
          delete this.sliderControllers[pid];
        }

        if (frames && frames.length > 0 && plotsEl) {
          plotsEl.innerHTML = "";

          if (slidersMeta && slidersMeta.length > 0) {
            // RENDER INTERACTIVE SLIDER VIEW
            this.sliderControllers = this.sliderControllers || {};
            this.sliderControllers[pid] = new PhysicsSliderController(pid, frames[0], slidersMeta, plotsEl, this);
          } else if (isAnim || frames.length > 1) {
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
            options.onPlotsReady(frames.length, (isAnim || frames.length > 1), (slidersMeta && slidersMeta.length > 0));
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
