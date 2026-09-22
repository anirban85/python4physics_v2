<?php
/**
 * Python4Physics - Arduino & Sensor Interfacing for the Physics Laboratory
 */
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';

$page_title = "Arduino in the Physics Laboratory - Sensor Interfacing & Real-Time DAQ";
$page_description = "Complete guide to Arduino-interfaced physics experiments: photogates, RC transient curves, ultrasonic sound velocity, thermistors, and real-time Python data acquisition.";

require_once __DIR__ . '/include/header.php';
require_once __DIR__ . '/include/navbar.php';
?>

<main class="container" style="padding-top: 3rem; padding-bottom: 5rem;">
    <!-- Page Header -->
    <div style="text-align: center; max-width: 860px; margin: 0 auto 3.5rem auto;">
        <span class="badge badge-amber"><i class="fa-solid fa-microchip"></i> Laboratory DAQ & Interfacing</span>
        <h1 style="font-size: 2.8rem; margin-top: 0.75rem; margin-bottom: 0.75rem;">Arduino in the <span class="gradient-text">Physics Laboratory</span></h1>
        <p style="font-size: 1.15rem; line-height: 1.6;">
            Bridge physical experimentation with computational data acquisition. Complete firmware sketches, wiring schematics, real-time Python logging scripts, and an interactive virtual oscilloscope.
        </p>
    </div>

    <!-- Interactive Virtual Oscilloscope Simulator -->
    <section class="glass-card highlight" style="margin-bottom: 4rem; padding: 2.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <span class="badge badge-cyan"><i class="fa-solid fa-wave-square"></i> Virtual Hardware Simulator</span>
                <h2 style="font-size: 1.85rem; margin-top: 0.5rem; margin-bottom: 0.25rem;">Interactive Digital Oscilloscope</h2>
                <p style="margin: 0; font-size: 0.92rem;">Simulate real-time voltage acquisition from Arduino ADC pin A0.</p>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <label style="font-size: 0.88rem; color: var(--text-muted);">Signal:</label>
                <select id="scopeSignalType" class="btn-modern btn-secondary btn-sm">
                    <option value="sine">AC Sine Wave (50 Hz)</option>
                    <option value="rc_decay">RC Charging / Discharging</option>
                    <option value="damped_osc">Damped Mechanical Oscillation</option>
                    <option value="square">Square Wave Generator</option>
                </select>
                <button type="button" id="scopePauseBtn" class="btn-modern btn-primary btn-sm">
                    <i class="fa-solid fa-pause"></i> Pause
                </button>
            </div>
        </div>

        <div style="background: #020617; border: 1px solid var(--card-border); border-radius: var(--radius-lg); padding: 1rem; text-align: center; position: relative;">
            <canvas id="scopeCanvas" width="900" height="340" style="width: 100%; height: auto; display: block; border-radius: 8px;"></canvas>
            <div style="position: absolute; bottom: 18px; right: 24px; font-family: 'JetBrains Mono'; font-size: 0.8rem; color: var(--accent); background: rgba(0,0,0,0.7); padding: 4px 10px; border-radius: 6px;">
                V_max: 5.0V | Sample Rate: 1.0 kS/s | 10-bit ADC
            </div>
        </div>
    </section>

    <!-- Complete Laboratory Experiments Modules -->
    <h2 style="font-size: 2rem; margin-bottom: 2rem; text-align: center;">Standard Physics Lab Interfacing Modules</h2>

    <div style="display: flex; flex-direction: column; gap: 3rem;">

        <!-- Experiment 1: Simple Pendulum & 'g' -->
        <article class="glass-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <span class="badge badge-cyan">Experiment 01</span>
                    <h3 style="font-size: 1.6rem; margin-top: 0.4rem; margin-bottom: 0.25rem;">Determination of 'g' via Optical Photogate Timing</h3>
                    <p style="margin: 0;">Measure the time period $T$ of a simple pendulum with microsecond precision using digital hardware interrupts.</p>
                </div>
            </div>

            <div class="theory-card" style="margin-bottom: 1.5rem;">
                <h4><i class="fa-solid fa-atom" style="color: var(--accent); margin-right: 6px;"></i> Principle & Theory</h4>
                <p>For small amplitude oscillations ($\theta < 10^\circ$), the time period $T$ of a simple pendulum of length $L$ is:</p>
                <div class="katex-display">
                    $$ T = 2\pi \sqrt{\frac{L}{g}} \implies g = 4\pi^2 \frac{L}{T^2} $$
                </div>
                <p>The photogate detector is connected to Arduino Pin 2 (Hardware Interrupt <code>INT0</code>). A full period corresponds to 2 successive interruptions of the optical beam.</p>
            </div>

            <!-- Code Tabs -->
            <div class="tabs-header">
                <button class="tab-btn active" onclick="switchExpTab('exp1', 'arduino', this)"><i class="fa-solid fa-microchip"></i> Arduino C++ Sketch</button>
                <button class="tab-btn" onclick="switchExpTab('exp1', 'python', this)"><i class="fa-brands fa-python"></i> Python DAQ Script</button>
            </div>

            <div id="exp1-arduino" class="tab-content active">
                <div class="editor-header">
                    <span class="editor-title"><i class="fa-solid fa-code"></i> photogate_pendulum.ino</span>
                    <button type="button" class="btn-modern btn-secondary btn-sm" onclick="copySnippet('exp1_ino')"><i class="fa-regular fa-copy"></i> Copy</button>
                </div>
                <pre class="console-output" id="exp1_ino" style="height: 320px;">// ============================================================
// Photogate Simple Pendulum Period Timer (Microsecond Accuracy)
// Connect IR Photogate sensor to Digital Pin 2 (INT0)
// ============================================================

const byte photogatePin = 2;
volatile unsigned long t1 = 0;
volatile unsigned long t2 = 0;
volatile byte count = 0;
volatile bool newPeriodReady = false;

void IRAM_ATTR onBeamBreak() {
  unsigned long now = micros();
  count++;
  if (count == 1) {
    t1 = now;
  } else if (count == 3) {
    t2 = now;
    newPeriodReady = true;
    count = 1;
    t1 = now;
  }
}

void setup() {
  Serial.begin(115200);
  pinMode(photogatePin, INPUT_PULLUP);
  attachInterrupt(digitalPinToInterrupt(photogatePin), onBeamBreak, FALLING);
  Serial.println("P4P_PENDULUM_READY");
}

void loop() {
  if (newPeriodReady) {
    unsigned long durationMicros = t2 - t1;
    float periodSec = durationMicros / 1000000.0;
    
    Serial.print("PERIOD_S:");
    Serial.println(periodSec, 5);
    newPeriodReady = false;
  }
}</pre>
            </div>

            <div id="exp1-python" class="tab-content">
                <div class="editor-header">
                    <span class="editor-title"><i class="fa-brands fa-python"></i> serial_pendulum_logger.py</span>
                    <button type="button" class="btn-modern btn-secondary btn-sm" onclick="copySnippet('exp1_py')"><i class="fa-regular fa-copy"></i> Copy</button>
                </div>
                <pre class="console-output" id="exp1_py" style="height: 320px;">import serial
import numpy as np

PORT = 'COM3' # Adjust to your Arduino serial port (e.g., /dev/ttyUSB0 on Linux)
BAUD = 115200
L = 0.85 # Measured pendulum length in meters

ser = serial.Serial(PORT, BAUD, timeout=2)
periods = []

print(f"Logging 20 periods for pendulum L = {L:.3f} m...")

while len(periods) < 20:
    line = ser.readline().decode('utf-8', errors='ignore').strip()
    if line.startswith("PERIOD_S:"):
        T = float(line.split(":")[1])
        periods.append(T)
        g_inst = 4 * (np.pi**2) * L / (T**2)
        print(f"Sample {len(periods):02d}: T = {T:.4f} s  -->  g = {g_inst:.3f} m/s^2")

ser.close()

T_mean = np.mean(periods)
T_std = np.std(periods)
g_final = 4 * (np.pi**2) * L / (T_mean**2)

print("\n--- RESULTS ---")
print(f"Mean Time Period: {T_mean:.4f} +/- {T_std:.4f} s")
print(f"Experimental 'g': {g_final:.3f} m/s^2")
</pre>
            </div>
        </article>

        <!-- Experiment 2: RC Circuit Transient Response -->
        <article class="glass-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <span class="badge badge-emerald">Experiment 02</span>
                    <h3 style="font-size: 1.6rem; margin-top: 0.4rem; margin-bottom: 0.25rem;">RC Circuit Charging, Discharging & Time Constant $\tau$</h3>
                    <p style="margin: 0;">Automated step-voltage response measurement of a capacitor and non-linear regression fit of $\tau = RC$.</p>
                </div>
            </div>

            <div class="theory-card" style="margin-bottom: 1.5rem;">
                <h4><i class="fa-solid fa-bolt" style="color: var(--success); margin-right: 6px;"></i> Transient Equations</h4>
                <p>During capacitor charging from $V_0 = 5\text{V}$ through resistor $R$:</p>
                <div class="katex-display">
                    $$ V_C(t) = V_0 \left( 1 - e^{-t/\tau} \right), \quad \text{where } \tau = RC $$
                </div>
                <p>During discharging to ground:</p>
                <div class="katex-display">
                    $$ V_C(t) = V_0 e^{-t/\tau} $$
                </div>
            </div>

            <!-- Code Tabs -->
            <div class="tabs-header">
                <button class="tab-btn active" onclick="switchExpTab('exp2', 'arduino', this)"><i class="fa-solid fa-microchip"></i> Arduino C++ Sketch</button>
                <button class="tab-btn" onclick="switchExpTab('exp2', 'python', this)"><i class="fa-brands fa-python"></i> Python Plotting Script</button>
            </div>

            <div id="exp2-arduino" class="tab-content active">
                <div class="editor-header">
                    <span class="editor-title"><i class="fa-solid fa-code"></i> rc_transient.ino</span>
                    <button type="button" class="btn-modern btn-secondary btn-sm" onclick="copySnippet('exp2_ino')"><i class="fa-regular fa-copy"></i> Copy</button>
                </div>
                <pre class="console-output" id="exp2_ino" style="height: 320px;">// ============================================================
// RC Circuit Step Response Logger
// Pin 8: Charge Pin (Digital Out)
// Pin A0: Voltage Sensing across Capacitor (Analog In)
// ============================================================

const byte chargePin = 8;
const byte sensorPin = A0;

void setup() {
  Serial.begin(115200);
  pinMode(chargePin, OUTPUT);
}

void loop() {
  // Start Charging Phase
  digitalWrite(chargePin, HIGH);
  unsigned long startT = micros();
  
  for (int i = 0; i < 400; i++) {
    int raw = analogRead(sensorPin);
    float voltage = (raw * 5.0) / 1023.0;
    float t_ms = (micros() - startT) / 1000.0;
    
    Serial.print(t_ms, 2);
    Serial.print(",");
    Serial.println(voltage, 3);
    delay(5);
  }

  // Discharge phase
  digitalWrite(chargePin, LOW);
  delay(2000); // Fully discharge
  delay(1000);
}</pre>
            </div>

            <div id="exp2-python" class="tab-content">
                <div class="editor-header">
                    <span class="editor-title"><i class="fa-brands fa-python"></i> plot_rc_curve.py</span>
                    <button type="button" class="btn-modern btn-secondary btn-sm" onclick="copySnippet('exp2_py')"><i class="fa-regular fa-copy"></i> Copy</button>
                </div>
                <pre class="console-output" id="exp2_py" style="height: 320px;">import numpy as np
import matplotlib.pyplot as plt
from scipy.optimize import curve_fit

# Simulated/Acquired experimental data
def rc_model(t, V0, tau):
    return V0 * (1 - np.exp(-t / tau))

t_data = np.linspace(0, 50, 100) # milliseconds
# Sample synthetic noisy acquisition for R=10k, C=1uF (tau = 10ms)
V_data = 5.0 * (1 - np.exp(-t_data / 10.0)) + np.random.normal(0, 0.04, len(t_data))

popt, pcov = curve_fit(rc_model, t_data, V_data, p0=[5.0, 10.0])
v_fit, tau_fit = popt
tau_err = np.sqrt(np.diag(pcov))[1]

print(f"Fitted Voltage V0: {v_fit:.3f} V")
print(f"Fitted Time Constant tau: {tau_fit:.3f} +/- {tau_err:.3f} ms")

plt.figure(figsize=(8, 4.5))
plt.scatter(t_data, V_data, color='#3b82f6', s=15, alpha=0.7, label='ADC Data Points')
plt.plot(t_data, rc_model(t_data, *popt), color='#06b6d4', lw=2, label=f'Fit: $\\tau = {tau_fit:.2f}$ ms')
plt.axvline(tau_fit, color='#ef4444', linestyle='--', label=f'63.2% V_0 at t={tau_fit:.2f}ms')
plt.title('RC Circuit Charging Curve & Exponential Fit')
plt.xlabel('Time (ms)')
plt.ylabel('Capacitor Voltage $V_C$ (V)')
plt.legend()
plt.grid(True, alpha=0.3)
plt.show()
</pre>
            </div>
        </article>

    </div>
</main>

<!-- Virtual Oscilloscope Simulator Script -->
<script>
(function() {
    var canvas = document.getElementById('scopeCanvas');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var signalSelect = document.getElementById('scopeSignalType');
    var pauseBtn = document.getElementById('scopePauseBtn');

    var isPaused = false;
    var t = 0;

    pauseBtn.addEventListener('click', function() {
        isPaused = !isPaused;
        pauseBtn.innerHTML = isPaused ? '<i class="fa-solid fa-play"></i> Resume' : '<i class="fa-solid fa-pause"></i> Pause';
        pauseBtn.className = isPaused ? 'btn-modern btn-secondary btn-sm' : 'btn-modern btn-primary btn-sm';
    });

    function drawGrid() {
        var w = canvas.width;
        var h = canvas.height;
        ctx.fillStyle = "#020617";
        ctx.fillRect(0, 0, w, h);

        // Oscilloscope phosphor grid
        ctx.strokeStyle = "rgba(6, 182, 212, 0.12)";
        ctx.lineWidth = 1;

        var xStep = w / 10;
        var yStep = h / 8;

        for (var x = 0; x <= w; x += xStep) {
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, h);
            ctx.stroke();
        }

        for (var y = 0; y <= h; y += yStep) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(w, y);
            ctx.stroke();
        }

        // Center crosshairs
        ctx.strokeStyle = "rgba(6, 182, 212, 0.25)";
        ctx.beginPath();
        ctx.moveTo(0, h / 2);
        ctx.lineTo(w, h / 2);
        ctx.moveTo(w / 2, 0);
        ctx.lineTo(w / 2, h);
        ctx.stroke();
    }

    function renderScope() {
        if (!isPaused) {
            t += 0.05;
        }

        drawGrid();

        var w = canvas.width;
        var h = canvas.height;
        var mode = signalSelect.value;

        ctx.strokeStyle = "#10b981"; // Phosphor Green trace
        ctx.shadowColor = "#10b981";
        ctx.shadowBlur = 8;
        ctx.lineWidth = 2.5;
        ctx.beginPath();

        var points = 400;
        for (var i = 0; i < points; i++) {
            var xNorm = i / points;
            var px = xNorm * w;
            var val = 0;

            if (mode === 'sine') {
                val = 2.0 * Math.sin(xNorm * 18 + t * 4);
            } else if (mode === 'rc_decay') {
                var cycle = (xNorm * 4 + t * 0.5) % 2;
                val = cycle < 1 ? (2.5 * (1 - Math.exp(-cycle * 4)) - 1.25) : (2.5 * Math.exp(-(cycle - 1) * 4) - 1.25);
            } else if (mode === 'damped_osc') {
                var tau = (xNorm * 4 + t * 0.5) % 2;
                val = 2.2 * Math.exp(-tau * 1.8) * Math.cos(tau * 24);
            } else if (mode === 'square') {
                val = Math.sin(xNorm * 18 + t * 4) > 0 ? 1.8 : -1.8;
            }

            var py = (h / 2) - (val / 3.0) * (h / 2 - 30);
            if (i === 0) ctx.moveTo(px, py);
            else ctx.lineTo(px, py);
        }

        ctx.stroke();
        ctx.shadowBlur = 0;

        requestAnimationFrame(renderScope);
    }

    renderScope();
})();

function switchExpTab(expId, lang, btn) {
    var parent = btn.parentElement.parentElement;
    parent.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    parent.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(expId + '-' + lang).classList.add('active');
}

function copySnippet(id) {
    var text = document.getElementById(id).innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert("Code copied to clipboard!");
    });
}
</script>

<?php require_once __DIR__ . '/include/footer.php'; ?>
