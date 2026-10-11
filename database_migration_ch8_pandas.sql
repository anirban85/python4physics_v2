-- =========================================================================
-- Python4Physics Database Migration: Chapter 8 Introduction to Pandas
-- Generated: 2026-10-11 06:50:16
-- =========================================================================

-- 1. Shift existing chapters 8..20 to 9..21
UPDATE `python` SET `menu_id` = `menu_id` + 1 WHERE `menu_id` >= 8 ORDER BY `menu_id` DESC;
UPDATE `p4p_menus` SET `menu_id` = `menu_id` + 1, `sort_order` = `sort_order` + 1 WHERE `language` = 'python' AND `menu_id` >= 8 ORDER BY `menu_id` DESC;
UPDATE `p4p_submenus` SET `menu_id` = `menu_id` + 1 WHERE `language` = 'python' AND `menu_id` >= 8 ORDER BY `menu_id` DESC;

-- 2. Insert Chapter 8 into p4p_menus
INSERT INTO `p4p_menus` (`language`, `menu_id`, `title`, `sort_order`) VALUES ('python', 8, 'Introduction to Pandas for Physics', 8);

-- 3. Insert Chapter 8 Subtopics into p4p_submenus
INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES ('python', 8, 1, 'Pandas Series & Physical Quantities', 1);
INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES ('python', 8, 2, 'DataFrames & Experimental Lab Data', 2);
INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES ('python', 8, 3, 'File I/O with Experimental Datasets', 3);
INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES ('python', 8, 4, 'Data Cleaning & Missing Experimental Data', 4);
INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES ('python', 8, 5, 'Data Transformation, Filtering & Physical Slicing', 5);
INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES ('python', 8, 6, 'Descriptive Statistics & Uncertainty Analysis', 6);
INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES ('python', 8, 7, 'GroupBy Operations & Multi-Trial Experiments', 7);
INSERT INTO `p4p_submenus` (`language`, `menu_id`, `submenu_id`, `title`, `sort_order`) VALUES ('python', 8, 8, 'Reshaping Data & Pivot Tables in Grid Measurements', 8);

-- 4. Insert Chapter 8 Programs into python table
INSERT INTO `python` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) VALUES (8, 1, 1, 'import pandas as pd
import numpy as np

# Time elapsed in seconds during calorimeter cooling
time_seconds = [0, 30, 60, 90, 120, 150, 180, 210, 240, 270, 300]

# Digital thermocouple temperature measurements in Celsius (T_env = 22.0 C)
temp_celsius = [85.4, 79.8, 75.1, 71.0, 67.5, 64.3, 61.6, 59.1, 56.9, 54.8, 53.0]

# 1. Create a Pandas Series with explicit physical time index
cooling_series = pd.Series(data=temp_celsius, index=time_seconds, name=\"Temp_Celsius\")
cooling_series.index.name = \"Time_s\"

print(\"=== Calorimeter Cooling Temperature Series ===\")
print(cooling_series)

# 2. Vectorized SI Unit Conversion: Celsius -> Kelvin (T_K = T_C + 273.15)
cooling_kelvin = cooling_series + 273.15
cooling_kelvin.name = \"Temp_Kelvin\"

# 3. Label-based (.loc) vs Position-based (.iloc) inspection
t_query = 120
print(f\"\\n[Probe Inspection at t = {t_query}s]\")
print(f\"  Via .loc[{t_query}]:  {cooling_series.loc[t_query]:.2f} deg C ({cooling_kelvin.loc[t_query]:.2f} K)\")
print(f\"  Initial State (.iloc[0]): {cooling_series.iloc[0]:.2f} deg C\")
print(f\"  Final State (.iloc[-1]):  {cooling_series.iloc[-1]:.2f} deg C\")

# 4. Instantaneous Cooling Rate using finite difference: dT / dt
delta_T = cooling_series.diff()
delta_t = 30.0  # sampling step in seconds
cooling_rate = delta_T / delta_t  # deg C per second
cooling_rate.name = \"Cooling_Rate_degC_s\"

print(\"\\n=== Experimental Cooling Rate (dT/dt) ===\")
print(cooling_rate.dropna().round(4))

# 5. Physical Regime Filtering: Timesteps where temperature exceeds 70.0 deg C
high_temp_trials = cooling_series[cooling_series > 70.0]
duration_s = (len(high_temp_trials) - 1) * 30
print(f\"\\nHigh-temperature regime (T > 70 deg C): spans {duration_s} seconds ({len(high_temp_trials)} data points).\")', '<div class=\"math-formulation\">
    <p><strong>Physical Law & Mathematical Formulation:</strong></p>
    <p>In physical calorimetry and experimental thermal physics, the rate of heat exchange between a system and its ambient surroundings is governed by <strong>Newton\'s Law of Cooling</strong>:</p>
    <p>$$\\frac{dT(t)}{dt} = -k \\left( T(t) - T_{\\text{env}} \\right)$$</p>
    <p>where $T(t)$ is the instantaneous temperature, $T_{\\text{env}}$ is the constant ambient temperature, and $k > 0$ is the characteristic cooling constant determined by the heat capacity and exposed surface area.</p>
    
    <p><strong>Discrete Experimental Sensor Sampling:</strong></p>
    <p>In laboratory data acquisition, temperature is sampled at discrete equispaced time intervals $t_i = i \\cdot \\Delta t$ ($i = 0, 1, 2, \\dots, N$). The finite difference rate of cooling is estimated as:</p>
    <p>$$\\left( \\frac{\\Delta T}{\\Delta t} \\right)_i = \\frac{T_{i} - T_{i-1}}{t_i - t_{i-1}}$$</p>
    <p>In Pandas, a <code>Series</code> maps discrete indices $t_i \\in \\mathbb{R}$ directly to physical values $T_i \\in \\mathbb{R}$, enabling vectorized unit transformations ($T_{\\text{K}} = T_{^{\\circ}\\text{C}} + 273.15$) and adjacent difference calculations without explicit iteration loops.</p>
</div>', '<div class=\"theory-explanation\">
    <p><strong>1. The 1D Pandas Series in Experimental Physics:</strong></p>
    <p>A Pandas <code>Series</code> is a one-dimensional labeled array capable of holding any data type (integers, floating-point measurements, strings). Unlike traditional Python lists or raw NumPy 1D arrays, each data element in a Series is bound to a meaningful physical index (such as timestamps, detector channel IDs, or spatial coordinates).</p>

    <p><strong>2. Label-Based vs Position-Based Indexing:</strong></p>
    <ul>
        <li><code>.loc[label]</code>: Retrieves physical readings by exact physical index values (e.g., query sensor state at exactly $t = 120\\,\\text{s}$).</li>
        <li><code>.iloc[integer_pos]</code>: Retrieves readings by zero-based integer index (e.g., initial condition at <code>.iloc[0]</code> or final equilibrium at <code>.iloc[-1]</code>).</li>
    </ul>

    <p><strong>3. Vectorized Temperature Conversion:</strong></p>
    <p>Physical calculations frequently require SI unit conversions (such as converting Celsius to Kelvin). Pandas broadcasts scalar addition across the entire Series without requiring explicit for-loops, preserving underlying index metadata and accelerating data processing.</p>

    <p><strong>4. Physical Boolean Masking:</strong></p>
    <p>By evaluating relational conditions such as <code>cooling_series > 70.0</code>, researchers isolate critical physical regimes—such as determining the duration of high-temperature phases in thermal annealing experiments.</p>
</div>');
INSERT INTO `python` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) VALUES (8, 2, 1, 'import pandas as pd
import numpy as np

# Physical constants for the kinematics laboratory experiment
mass_kg = 0.250        # Glider mass on track (kg)
g = 9.80665            # Standard acceleration due to gravity (m/s^2)
initial_height = 1.200 # Initial release height above ground (m)

# Photogate sensor readings recorded during free-fall
lab_data = {
    \'time_s\': [0.00, 0.10, 0.20, 0.30, 0.40, 0.48],
    \'fall_distance_m\': [0.000, 0.049, 0.196, 0.441, 0.784, 1.130],
    \'velocity_mps\': [0.000, 0.980, 1.961, 2.942, 3.923, 4.707],
    \'photogate_active\': [True, True, True, True, True, True]
}

# 1. Initialize DataFrame
df_kinematics = pd.DataFrame(lab_data)

# 2. Compute Instantaneous Height: h(t) = h_0 - y(t)
df_kinematics[\'height_m\'] = initial_height - df_kinematics[\'fall_distance_m\']

# 3. Compute Energy Components:
# Kinetic Energy: K = 0.5 * m * v^2
df_kinematics[\'kinetic_energy_J\'] = 0.5 * mass_kg * (df_kinematics[\'velocity_mps\'] ** 2)

# Potential Energy: U = m * g * h
df_kinematics[\'potential_energy_J\'] = mass_kg * g * df_kinematics[\'height_m\']

# Total Mechanical Energy: E_total = K + U
df_kinematics[\'total_energy_J\'] = (
    df_kinematics[\'kinetic_energy_J\'] + df_kinematics[\'potential_energy_J\']
)

# 4. Energy Conservation Discrepancy (% deviation from initial state)
E0 = df_kinematics[\'total_energy_J\'].iloc[0]
df_kinematics[\'energy_dev_pct\'] = 100.0 * (df_kinematics[\'total_energy_J\'] - E0) / E0

print(\"=== Experimental Kinematics & Energy Ledger ===\")
cols_to_show = [\'time_s\', \'height_m\', \'velocity_mps\', \'kinetic_energy_J\', \'potential_energy_J\', \'total_energy_J\', \'energy_dev_pct\']
print(df_kinematics[cols_to_show].to_string(index=False))

print(f\"\\nInitial Potential Energy (E0): {E0:.4f} Joules\")
print(f\"Maximum Energy Deviation: {df_kinematics[\'energy_dev_pct\'].abs().max():.3f}% (within experimental tolerance)\")', '<div class=\"math-formulation\">
    <p><strong>Physical Model: Conservation of Mechanical Energy in Kinematics:</strong></p>
    <p>For a rigid particle of mass $m$ released from an initial height $h_0$ in a uniform gravitational field $g$, the instantaneous height is $h(t) = h_0 - y(t)$, where $y(t)$ is the vertical displacement fallen.</p>
    
    <p>The components of total mechanical energy are:</p>
    <ul>
        <li><strong>Kinetic Energy:</strong> $K(t) = \\frac{1}{2} m v(t)^2$</li>
        <li><strong>Gravitational Potential Energy:</strong> $U(t) = m g h(t) = m g [h_0 - y(t)]$</li>
        <li><strong>Total Mechanical Energy:</strong> $E_{\\text{tot}}(t) = K(t) + U(t)$</li>
    </ul>

    <p>In an isolated conservative system without non-conservative air drag, energy conservation requires:</p>
    <p>$$\\frac{dE_{\\text{tot}}}{dt} = 0 \\implies E_{\\text{tot}}(t) = E_0 = m g h_0$$</p>
    <p>Pandas DataFrames enable column-wise matrix evaluation of these physical state variables across all measurement time steps simultaneously.</p>
</div>', '<div class=\"theory-explanation\">
    <p><strong>1. Structure of a Pandas DataFrame:</strong></p>
    <p>A <code>DataFrame</code> is a 2-dimensional labeled data structure with columns of potentially different types (like a spreadsheet or database table). In computational physics, each row represents an experimental observation (e.g., a specific timestamp or state vector), and each column represents a measured physical quantity ($t$, $x$, $v$, $a$, $E$).</p>

    <p><strong>2. Column-Wise Vectorized Calculations:</strong></p>
    <p>Rather than evaluating kinetic energy through iterative Python loops, Pandas allows direct column expressions: <code>df[\'kinetic_energy\'] = 0.5 * m * (df[\'velocity\'] ** 2)</code>. This leverages low-level C and SIMD vectorization for speed and mathematical readability.</p>

    <p><strong>3. Energy Conservation Auditing:</strong></p>
    <p>By computing percentage energy discrepancies $\\delta E = 100 \\times \\frac{E(0) - E(t)}{E(0)}$, students verify the accuracy of photogate sensors and detect frictional energy dissipation in the laboratory.</p>
</div>');
INSERT INTO `python` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) VALUES (8, 3, 1, 'import pandas as pd
import io

# Simulated output file from a laboratory digital Geiger-Muller counter
# Real instrument files often contain metadata headers prefixed by \'#\'
detector_stream = \"\"\"# ========================================================
# Department of Physics - Nuclear Radiation Laboratory
# Radioactive Source: Co-60 (Cobalt-60 gamma emitter)
# High Voltage Bias: 450 V | Source-to-tube distance: 5.0 cm
# Background baseline: 14.50 counts/min (sigma_bg = 1.20)
# ========================================================
interval_id,elapsed_time_s,raw_counts,tube_voltage_v,gate_status
1,10.0,82,450.1,OK
2,20.0,89,450.0,OK
3,30.0,77,449.8,OK
4,40.0,94,450.2,OK
5,50.0,86,450.0,OK
6,60.0,81,449.9,OK
7,70.0,90,450.1,OK
8,80.0,84,450.0,OK
\"\"\"

# 1. Parse CSV stream, skipping metadata comments starting with \'#\'
df_radiation = pd.read_csv(io.StringIO(detector_stream), comment=\'#\')

print(\"=== Ingested Instrument Dataset ===\")
print(df_radiation.head(4))

# 2. Calculate Gross Count Rate in Counts Per Second (CPS) and Counts Per Minute (CPM)
gate_interval_s = 10.0  # gate window duration in seconds
df_radiation[\'cpm_gross\'] = (df_radiation[\'raw_counts\'] / gate_interval_s) * 60.0

# 3. Background Radiation Subtraction: Net CPM = Gross CPM - Background CPM
bg_cpm = 14.50
bg_cpm_unc = 1.20
df_radiation[\'cpm_net\'] = df_radiation[\'cpm_gross\'] - bg_cpm

# 4. Statistical Counting Uncertainty via Poisson Law: sigma_N = sqrt(N)
df_radiation[\'cpm_uncertainty\'] = (
    ((df_radiation[\'raw_counts\'] ** 0.5 / gate_interval_s) * 60.0) ** 2 + bg_cpm_unc ** 2
) ** 0.5

print(\"\\n=== Calibrated Nuclear Counting Table ===\")
display_cols = [\'elapsed_time_s\', \'raw_counts\', \'cpm_gross\', \'cpm_net\', \'cpm_uncertainty\']
print(df_radiation[display_cols].round(2).to_string(index=False))

# 5. Export calibrated data to a clean CSV buffer
out_buffer = io.StringIO()
df_radiation[display_cols].to_csv(out_buffer, index=False, float_format=\'%.2f\')
print(\"\\n=== Exported CSV Output Preview ===\")
print(out_buffer.getvalue().strip())', '<div class=\"math-formulation\">
    <p><strong>Physical Problem: Nuclear Radiation Counting & Poisson Statistics:</strong></p>
    <p>In nuclear physics, radioactive decay is an intrinsically random Poisson process. If a detector records $N$ radioactive decay pulses in a time window $\\Delta t$, the statistical counting uncertainty is:</p>
    <p>$$\\sigma_N = \\sqrt{N}$$</p>
    
    <p>The gross count rate in counts per minute (CPM) is:</p>
    <p>$$R_{\\text{gross}} = \\frac{N}{\\Delta t} \\times 60$$</p>
    
    <p>The net radiation activity after subtracting ambient background radiation $R_{\\text{bg}}$ is:</p>
    <p>$$R_{\\text{net}} = R_{\\text{gross}} - R_{\\text{bg}}$$</p>
    <p>$$\\sigma_{R_{\\text{net}}} = \\sqrt{\\sigma_{R_{\\text{gross}}}^2 + \\sigma_{R_{\\text{bg}}}^2} = \\sqrt{\\frac{N}{\\Delta t^2} + \\sigma_{\\text{bg}}^2} \\times 60$$</p>
</div>', '<div class=\"theory-explanation\">
    <p><strong>1. Laboratory Instrument I/O in Computational Physics:</strong></p>
    <p>Modern physics data loggers (digital oscilloscopes, Geiger-Müller counters, spectrometers) output CSV files with preamble comments containing calibration metadata (e.g. high voltage, detector ID, source distance). Pandas <code>pd.read_csv()</code> handles these gracefully via the <code>comment=\'#\'</code> parameter.</p>

    <p><strong>2. Key Parameters of <code>pd.read_csv()</code>:</strong></p>
    <ul>
        <li><code>comment=\'#\'</code>: Skips equipment header lines beginning with hash comments.</li>
        <li><code>usecols=[...]</code>: Ingests only the physical channels of interest to optimize memory.</li>
        <li><code>skiprows=n</code>: Bypasses corrupted or preamble instrument rows.</li>
    </ul>

    <p><strong>3. Exporting Processed Physical Results:</strong></p>
    <p>The <code>df.to_csv(\'cleaned_output.csv\', index=False)</code> method enables standardized exporting of calibrated data, essential for publication figures or archival in laboratory databases.</p>
</div>');
INSERT INTO `python` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) VALUES (8, 4, 1, 'import pandas as pd
import numpy as np

# Simulated telemetry stream from an atmospheric physics observatory
# np.nan simulates temporary sensor dropouts or wireless connection drop
sensor_stream = {
    \'time_min\': [0, 5, 10, 15, 20, 25, 30, 35, 40, 45],
    \'solar_irradiance_W_m2\': [842.1, 846.5, np.nan, 852.0, 855.4, np.nan, np.nan, 863.1, 866.7, 869.2],
    \'barometric_pressure_hPa\': [1013.2, 1013.1, 1013.0, np.nan, 1012.8, 1012.7, 1012.5, 1012.4, np.nan, 1012.1],
    \'sensor_flag\': [\'VALID\', \'VALID\', \'DROPOUT\', \'VALID\', \'VALID\', \'ERROR\', \'ERROR\', \'VALID\', \'VALID\', \'VALID\']
}

df_telemetry = pd.DataFrame(sensor_stream)

print(\"=== Raw Telemetry with Sensor Dropouts ===\")
print(df_telemetry)

# 1. Null Value Diagnostics: Tally missing physical readings
print(\"\\nMissing Measurement Count per Sensor Channel:\")
print(df_telemetry.isna().sum())

# 2. Create clean working copy
df_clean = df_telemetry.copy()

# 3. Forward-Fill (ffill) for high-inertia thermodynamic state (Barometric Pressure)
df_clean[\'barometric_pressure_hPa\'] = df_clean[\'barometric_pressure_hPa\'].ffill()

# 4. Linear Interpolation for continuous physical fields (Solar Irradiance)
df_clean[\'solar_irradiance_W_m2\'] = df_clean[\'solar_irradiance_W_m2\'].interpolate(method=\'linear\')

# 5. Type Casting and Filtering
df_clean[\'time_min\'] = df_clean[\'time_min\'].astype(int)
df_clean[\'barometric_pressure_hPa\'] = df_clean[\'barometric_pressure_hPa\'].astype(float)
df_clean[\'is_reconstructed\'] = df_telemetry[\'sensor_flag\'] != \'VALID\'

print(\"\\n=== Reconstructed & Cleaned Physics Dataset ===\")
print(df_clean[[\'time_min\', \'solar_irradiance_W_m2\', \'barometric_pressure_hPa\', \'is_reconstructed\']].to_string(index=False))

# Verify all numerical gaps are resolved
remaining_nans = df_clean[[\'solar_irradiance_W_m2\', \'barometric_pressure_hPa\']].isna().sum().sum()
print(f\"\\nVerification: {remaining_nans} missing values remaining. Data stream continuous and valid.\")', '<div class=\"math-formulation\">
    <p><strong>Physical Model: Reconstruction of Missing Experimental Observables:</strong></p>
    <p>In experimental astrophysics and environmental telemetry, sensors experience intermittent dropouts where recorded data is missing ($\\text{NaN}$).</p>
    
    <p><strong>1. Physical Continuity & Linear Interpolation:</strong></p>
    <p>For smoothly varying continuous physical fields (such as atmospheric solar irradiance $I(t)$), a missing value at time $t \\in [t_0, t_1]$ is reconstructed via the linear interpolation scheme:</p>
    <p>$$I(t) = I(t_0) + \\frac{I(t_1) - I(t_0)}{t_1 - t_0} (t - t_0)$$</p>
    
    <p><strong>2. Thermodynamic Persistence (Forward-Fill):</strong></p>
    <p>For high-inertia thermodynamic variables such as barometric pressure $P(t)$, when sensor disconnection occurs for brief intervals ($\\Delta t \\ll \\tau_{\\text{relax}}$), the previous equilibrium value provides a reliable estimate:</p>
    <p>$$P(t) = P(t - \\Delta t)$$</p>
</div>', '<div class=\"theory-explanation\">
    <p><strong>1. Why Experimental Data Cleaning is Critical:</strong></p>
    <p>Raw physics laboratory streams rarely arrive in pristine condition. Loose probe connections, voltage sags, ADC overflow errors, and wireless packet drops introduce <code>NaN</code> (Not a Number) values into data arrays.</p>

    <p><strong>2. Methods of Handling Missing Data in Pandas:</strong></p>
    <ul>
        <li><code>.isna().sum()</code>: Rapid diagnostic tool to tally missing sensor readings per physical channel.</li>
        <li><code>.dropna()</code>: Removes records containing missing values. In physics time series, blind dropping can distort temporal continuity and step-sizes $\\Delta t$.</li>
        <li><code>.interpolate(method=\'linear\')</code>: Smoothly estimates missing samples based on physical continuity.</li>
        <li><code>.ffill()</code> / <code>.bfill()</code>: Forward or backward propagation of persistent states.</li>
    </ul>

    <p><strong>3. Data Type Validation:</strong></p>
    <p>Sensor loggers often store numbers as text strings due to formatting anomalies. Using <code>.astype()</code> or <code>pd.to_numeric()</code> ensures physical values are true 64-bit IEEE floating-point numbers ready for numerical computation.</p>
</div>');
INSERT INTO `python` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) VALUES (8, 5, 1, 'import pandas as pd
import numpy as np

# Fundamental physical constants
e_charge = 1.60217663e-19  # Electron charge (Coulombs)
c_speed  = 2.99792458e8    # Speed of light (m/s)

# Trial 1: Cesium Cathode (Work Function Phi ~ 2.14 eV)
cesium_data = {
    \'wavelength_nm\': [650.0, 589.0, 546.0, 436.0, 405.0, 365.0],
    \'stopping_potential_V\': [0.00, 0.21, 0.45, 1.15, 1.42, 1.88],
    \'cathode_material\': [\'Cesium\'] * 6
}

# Trial 2: Potassium Cathode (Work Function Phi ~ 2.29 eV)
potassium_data = {
    \'wavelength_nm\': [600.0, 546.0, 436.0, 405.0, 365.0],
    \'stopping_potential_V\': [0.00, 0.32, 1.02, 1.28, 1.73],
    \'cathode_material\': [\'Potassium\'] * 5
}

df_cs = pd.DataFrame(cesium_data)
df_k  = pd.DataFrame(potassium_data)

# 1. Concatenate datasets into a unified experimental DataFrame
df_photo = pd.concat([df_cs, df_k], ignore_index=True)

# 2. Derived Physical Quantities: Frequency nu = c / lambda
df_photo[\'frequency_THz\'] = (c_speed / (df_photo[\'wavelength_nm\'] * 1e-9)) * 1e-12

# Maximum kinetic energy: K_max = e * V_0 (eV)
df_photo[\'K_max_eV\'] = df_photo[\'stopping_potential_V\']

# 3. Physical Filtering: Isolate active photoemission regime (V_0 > 0)
df_active = df_photo[df_photo[\'stopping_potential_V\'] > 0.0].copy()

# 4. Multi-Condition Slicing: Ultraviolet region (lambda < 450 nm) for Cesium
df_uv_cs = df_photo[(df_photo[\'cathode_material\'] == \'Cesium\') & (df_photo[\'wavelength_nm\'] < 450.0)]

print(\"=== Complete Photoelectric Experiment Ledger ===\")
print(df_photo.head(6))

# Sort active regime by cathode material and frequency
df_sorted = df_active.sort_values(by=[\'cathode_material\', \'frequency_THz\'], ascending=[True, True])
print(\"\\n=== Active Emission Regime (V_0 > 0) Sorted by Frequency ===\")
print(df_sorted[[\'cathode_material\', \'wavelength_nm\', \'frequency_THz\', \'stopping_potential_V\', \'K_max_eV\']].to_string(index=False))

# 5. Calculate Experimental Planck\'s Constant: h = e * (Delta V0 / Delta nu)
cs_trials = df_active[df_active[\'cathode_material\'] == \'Cesium\']
delta_V = cs_trials[\'stopping_potential_V\'].iloc[-1] - cs_trials[\'stopping_potential_V\'].iloc[0]
delta_nu = (cs_trials[\'frequency_THz\'].iloc[-1] - cs_trials[\'frequency_THz\'].iloc[0]) * 1e12
h_exp = e_charge * (delta_V / delta_nu)

h_accepted = 6.62607015e-34
pct_err = abs(h_exp - h_accepted) / h_accepted * 100.0

print(f\"\\nCalculated Planck\'s Constant: h = {h_exp:.3e} J*s (Accepted: {h_accepted:.3e} J*s, Error: {pct_err:.2f}%)\")', '<div class=\"math-formulation\">
    <p><strong>Physical Law: Einstein\'s Photoelectric Equation:</strong></p>
    <p>When monochromatic light of frequency $\\nu = \\frac{c}{\\lambda}$ strikes a photocathode, the maximum kinetic energy $K_{\\text{max}}$ of emitted photoelectrons is given by Einstein\'s relation:</p>
    <p>$$K_{\\text{max}} = e V_0 = h \\nu - \\Phi$$</p>
    <p>where $V_0$ is the stopping potential (in Volts), $e$ is the elementary charge ($1.602 \\times 10^{-19}\\,\\text{C}$), $h$ is Planck\'s constant, and $\\Phi$ is the work function of the material.</p>
    
    <p><strong>Condition for Active Photoelectric Emission:</strong></p>
    <p>Photoemission occurs exclusively when photon energy exceeds the work function:</p>
    <p>$$h \\nu > \\Phi \\iff V_0 > 0$$</p>
    <p>The slope of stopping potential versus optical frequency yields Planck\'s constant:</p>
    <p>$$\\frac{dV_0}{d\\nu} = \\frac{h}{e} \\implies h = e \\left( \\frac{\\Delta V_0}{\\Delta \\nu} \\right)$$</p>
</div>', '<div class=\"theory-explanation\">
    <p><strong>1. Filtering & Relational Slicing in Experimental Physics:</strong></p>
    <p>Physical experiments often encompass multiple regimes (e.g., sub-threshold non-emission vs above-threshold photoemission). Pandas enables instantaneous filtering using boolean conditions (such as <code>df[df[\'stopping_potential_V\'] > 0]</code>) without mutating original records.</p>

    <p><strong>2. Multi-Dataset Merging & Concatenation:</strong></p>
    <p>In experimental laboratories, measurements on different materials (e.g., Cesium vs Potassium cathodes) are often logged in separate trials. <code>pd.concat()</code> unifies them into a comprehensive comparative ledger, while <code>.sort_values()</code> organizes them monotonically by frequency.</p>

    <p><strong>3. Derivation of Fundamental Physical Constants:</strong></p>
    <p>By extracting active rows from the filtered DataFrame and computing differential ratios $\\frac{\\Delta V_0}{\\Delta \\nu}$, students determine experimental values of Planck\'s constant $h$ directly from laboratory data.</p>
</div>');
INSERT INTO `python` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) VALUES (8, 6, 1, 'import pandas as pd
import numpy as np

# High-precision student laboratory measurements of Simple Pendulum period T (seconds)
# For length L = 1.000 m. Note: 2.450 s represents an accidental stopwatch blunder
raw_trials = [
    2.008, 2.012, 1.995, 2.004, 2.019,
    2.001, 1.998, 2.450, 2.007, 2.011,
    2.005, 1.999, 2.015, 2.003, 2.006
]

df_pendulum = pd.DataFrame({
    \'trial_id\': range(1, len(raw_trials) + 1),
    \'period_s\': raw_trials
})

# 1. Comprehensive Summary Statistics using .describe()
print(\"=== Initial Descriptive Statistics (with Outlier) ===\")
print(df_pendulum[\'period_s\'].describe().round(4))

# 2. Outlier Detection via Tukey\'s Interquartile Range (IQR) Method
Q1 = df_pendulum[\'period_s\'].quantile(0.25)
Q3 = df_pendulum[\'period_s\'].quantile(0.75)
IQR = Q3 - Q1
lower_bound = Q1 - 1.5 * IQR
upper_bound = Q3 + 1.5 * IQR

print(f\"\\nIQR: {IQR:.4f} s | Acceptance Window: [{lower_bound:.4f} s, {upper_bound:.4f} s]\")

# Filter out experimental blunders
is_valid = (df_pendulum[\'period_s\'] >= lower_bound) & (df_pendulum[\'period_s\'] <= upper_bound)
df_clean = df_pendulum[is_valid].copy()
blunders = df_pendulum[~is_valid]

print(f\"Detected & removed {len(blunders)} blunder(s): {blunders[\'period_s\'].tolist()} s\")

# 3. Precision Statistical Reduction on Cleaned Trials
N = len(df_clean)
mean_T = df_clean[\'period_s\'].mean()
median_T = df_clean[\'period_s\'].median()
std_T = df_clean[\'period_s\'].std(ddof=1)  # Sample standard deviation (N-1)
sem_T = df_clean[\'period_s\'].sem()        # Standard Error of the Mean: s / sqrt(N)

print(\"\\n=== Refined Experimental Statistics ===\")
print(f\"Number of Valid Trials (N):    {N}\")
print(f\"Mean Period (<T>):             {mean_T:.4f} s\")
print(f\"Median Period:                 {median_T:.4f} s\")
print(f\"Sample Standard Deviation (s): {std_T:.4f} s\")
print(f\"Standard Error of Mean (SEM):  {sem_T:.4f} s\")

# 4. Error Propagation to Gravitational Acceleration: g = 4 * pi^2 * L / T^2
L = 1.000  # meters
g_exp = (4.0 * (np.pi ** 2) * L) / (mean_T ** 2)
delta_g = g_exp * 2.0 * (sem_T / mean_T)

print(f\"\\nExperimental Acceleration due to Gravity:\")
print(f\"  g = ({g_exp:.3f} +/- {delta_g:.3f}) m/s^2\")
print(f\"  Accepted Reference: g = 9.807 m/s^2\")', '<div class=\"math-formulation\">
    <p><strong>Statistical Metrics in Experimental Physics:</strong></p>
    <p>Given $N$ independent measurements $x_1, x_2, \\dots, x_N$ of a physical observable:</p>
    
    <p><strong>1. Sample Mean ($\\bar{x}$):</strong></p>
    <p>$$\\bar{x} = \\frac{1}{N} \\sum_{i=1}^N x_i$$</p>
    
    <p><strong>2. Sample Standard Deviation ($s_x$ with Bessel\'s Correction):</strong></p>
    <p>$$s_x = \\sqrt{\\frac{1}{N - 1} \\sum_{i=1}^N (x_i - \\bar{x})^2}$$</p>
    
    <p><strong>3. Standard Error of the Mean ($\\mathrm{SEM}$):</strong></p>
    <p>$$\\mathrm{SEM} = \\frac{s_x}{\\sqrt{N}}$$</p>
    
    <p><strong>4. Outlier Rejection via Tukey\'s Interquartile Range (IQR):</strong></p>
    <p>Let $Q_1$ and $Q_3$ represent the 25th and 75th percentiles. A measurement is classified as a gross experimental error (blunder) if it falls outside the Tukey fences:</p>
    <p>$$x_{\\text{outlier}} \\notin [Q_1 - 1.5 \\cdot \\mathrm{IQR},\\; Q_3 + 1.5 \\cdot \\mathrm{IQR}]$$</p>
    
    <p><strong>5. Error Propagation to Derived Quantities:</strong></p>
    <p>For a simple pendulum of length $L$ and period $T$, $g = \\frac{4\\pi^2 L}{T^2}$. The propagated uncertainty is:</p>
    <p>$$\\delta g = g \\cdot 2 \\frac{\\mathrm{SEM}_T}{\\bar{T}}$$</p>
</div>', '<div class=\"theory-explanation\">
    <p><strong>1. Precision vs Uncertainty in Experimental Physics:</strong></p>
    <p>In scientific measurement, stating a result as a single number without an uncertainty interval is meaningless. Pandas provides specialized functions for statistical reduction:</p>
    <ul>
        <li><code>.mean()</code>: Best estimate of the true physical parameter.</li>
        <li><code>.std(ddof=1)</code>: Sample standard deviation reflecting experimental spread (uses $N-1$ degrees of freedom by default).</li>
        <li><code>.sem()</code>: Standard error of the mean, quantifying the uncertainty in the mean estimate.</li>
    </ul>

    <p><strong>2. Automated Outlier Rejection:</strong></p>
    <p>Student laboratory sessions often suffer from blunders (e.g. premature stopwatch clicks or electrical glitches). Tukey\'s IQR rule provides a principled, objective method to prune outliers without subjective investigator bias.</p>

    <p><strong>3. Error Propagation:</strong></p>
    <p>Combining the calculated $\\mathrm{SEM}$ with analytical error propagation yields the final experimental report formatted according to international metrology standards: $g = (\\bar{g} \\pm \\delta g)\\,\\text{m/s}^2$.</p>
</div>');
INSERT INTO `python` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) VALUES (8, 7, 1, 'import pandas as pd
import numpy as np

# Projectile motion experiment: steel ball launched at varying angles
# Initial velocity v0 = 8.50 m/s, g = 9.81 m/s^2
# 3 independent trials recorded per launch angle (degrees)
trials_data = [
    {\'angle_deg\': 15, \'trial\': 1, \'range_m\': 3.65, \'tof_s\': 0.44},
    {\'angle_deg\': 15, \'trial\': 2, \'range_m\': 3.72, \'tof_s\': 0.46},
    {\'angle_deg\': 15, \'trial\': 3, \'range_m\': 3.68, \'tof_s\': 0.45},
    {\'angle_deg\': 30, \'trial\': 1, \'range_m\': 6.35, \'tof_s\': 0.86},
    {\'angle_deg\': 30, \'trial\': 2, \'range_m\': 6.42, \'tof_s\': 0.88},
    {\'angle_deg\': 30, \'trial\': 3, \'range_m\': 6.38, \'tof_s\': 0.87},
    {\'angle_deg\': 45, \'trial\': 1, \'range_m\': 7.32, \'tof_s\': 1.21},
    {\'angle_deg\': 45, \'trial\': 2, \'range_m\': 7.41, \'tof_s\': 1.24},
    {\'angle_deg\': 45, \'trial\': 3, \'range_m\': 7.36, \'tof_s\': 1.22},
    {\'angle_deg\': 60, \'trial\': 1, \'range_m\': 6.34, \'tof_s\': 1.50},
    {\'angle_deg\': 60, \'trial\': 2, \'range_m\': 6.40, \'tof_s\': 1.52},
    {\'angle_deg\': 60, \'trial\': 3, \'range_m\': 6.31, \'tof_s\': 1.49},
    {\'angle_deg\': 75, \'trial\': 1, \'range_m\': 3.62, \'tof_s\': 1.66},
    {\'angle_deg\': 75, \'trial\': 2, \'range_m\': 3.70, \'tof_s\': 1.68},
    {\'angle_deg\': 75, \'trial\': 3, \'range_m\': 3.66, \'tof_s\': 1.67},
]

df_proj = pd.DataFrame(trials_data)

# 1. GroupBy Launch Angle and Aggregate Mean, Std, and Count
grouped = df_proj.groupby(\'angle_deg\')
summary_df = grouped[\'range_m\'].agg(
    mean_range=\'mean\',
    std_range=\'std\',
    trials=\'count\'
)

# 2. Standard Error of the Mean (SEM) per angle group
summary_df[\'sem_range\'] = summary_df[\'std_range\'] / np.sqrt(summary_df[\'trials\'])

# 3. Theoretical Kinematics Comparison: R = (v_0^2 * sin(2*theta)) / g
v0 = 8.50
g = 9.81
theta_rad = np.radians(summary_df.index)
summary_df[\'theory_range_m\'] = (v0**2 * np.sin(2.0 * theta_rad)) / g
summary_df[\'residual_m\'] = summary_df[\'mean_range\'] - summary_df[\'theory_range_m\']

print(\"=== Projectile Motion GroupBy Statistical Analysis ===\")
print(summary_df.round(3))

# 4. Group-wise Transformation: Relative percentage deviation within each angle group
df_proj[\'dev_from_group_mean_pct\'] = df_proj.groupby(\'angle_deg\')[\'range_m\'].transform(
    lambda x: 100.0 * (x - x.mean()) / x.mean()
)

print(\"\\n=== Individual Trials with Group Deviation (Sample Rows) ===\")
print(df_proj[[\'angle_deg\', \'trial\', \'range_m\', \'dev_from_group_mean_pct\']].iloc[::3].to_string(index=False))

# 5. Complementary Angle Symmetry Test (15 deg vs 75 deg)
r15 = summary_df.loc[15, \'mean_range\']
r75 = summary_df.loc[75, \'mean_range\']
print(f\"\\nKinematic Symmetry Verification:\")
print(f\"  Range at 15 deg: {r15:.3f} m | Range at 75 deg: {r75:.3f} m | Difference: {abs(r15 - r75):.3f} m\")', '<div class=\"math-formulation\">
    <p><strong>Physical Model: Projectile Motion Range & Symmetry:</strong></p>
    <p>For a projectile launched on level ground with initial muzzle speed $v_0$ at launch angle $\\theta$ with respect to horizontal, the theoretical range $R_{\\text{theory}}$ is:</p>
    <p>$$R_{\\text{theory}}(\\theta) = \\frac{v_0^2 \\sin(2\\theta)}{g}$$</p>
    
    <p><strong>Complementary Angle Symmetry:</strong></p>
    <p>Since $\\sin(2(90^{\\circ} - \\theta)) = \\sin(180^{\\circ} - 2\\theta) = \\sin(2\\theta)$, complementary angles achieve identical theoretical horizontal ranges:</p>
    <p>$$R(\\theta) = R(90^{\\circ} - \\theta)$$</p>
    
    <p><strong>The Split-Apply-Combine Workflow in Physics:</strong></p>
    <p>In multi-trial multi-variable experiments, the experimental dataset $\\mathcal{D} = \\{(x_i, y_i, \\theta_i)\\}$ is partitioned into disjoint subsets by condition $\\theta_k$, an aggregation operator $\\mathcal{A}$ computes group metrics $(\\bar{R}_k, s_k)$, and results are recombined into a consolidated physical comparison table.</p>
</div>', '<div class=\"theory-explanation\">
    <p><strong>1. GroupBy Paradigm in Physics Laboratory Data:</strong></p>
    <p>Physics experiments frequently involve sweeping a control parameter (such as launch angle $\\theta$, temperature $T$, or voltage $V$) across multiple repeated runs. The Pandas <code>groupby()</code> operation executes the classic <strong>Split-Apply-Combine</strong> strategy:</p>
    <ul>
        <li><strong>Split:</strong> Partition the DataFrame into groups corresponding to distinct values of the control variable.</li>
        <li><strong>Apply:</strong> Evaluate statistical reductions (mean, standard deviation, count) on each group independently.</li>
        <li><strong>Combine:</strong> Stitch the resulting summaries into a unified indexed DataFrame.</li>
    </ul>

    <p><strong>2. Multi-Metric Aggregations:</strong></p>
    <p>Using <code>.agg({\'range_m\': [\'mean\', \'std\', \'count\']})</code> produces structured multi-level summary tables, facilitating direct computation of experimental residuals against theoretical kinematic trajectories.</p>

    <p><strong>3. Group-Wise Transformations:</strong></p>
    <p>The <code>.transform()</code> method scales individual trial values by their respective group statistics, allowing researchers to evaluate relative deviations across different experimental configurations.</p>
</div>');
INSERT INTO `python` (`menu_id`, `submenu_id`, `program_id`, `content`, `algo`, `explanation`) VALUES (8, 8, 1, 'import pandas as pd
import numpy as np

# Spatial Magnetic Field Mapping: B_z(x, y) in milliTesla (mT)
# Measured across a 5x5 transverse grid (-2 cm to +2 cm) inside a Helmholtz coil
x_coords = [-2, -1, 0, 1, 2]  # x positions (cm)
y_coords = [-2, -1, 0, 1, 2]  # y positions (cm)

# Center field B0 = 10.00 mT, coil radius R = 10 cm
B0 = 10.00
scan_log = []

for x in x_coords:
    for y in y_coords:
        r_sq = x**2 + y**2
        # Off-axis expansion drop-off B(r) = B0 * (1 - 0.0075 * r^2) + noise
        b_measured = B0 * (1.0 - 0.0075 * r_sq) + np.random.normal(0, 0.012)
        scan_log.append({
            \'x_cm\': x,
            \'y_cm\': y,
            \'B_z_mT\': round(b_measured, 3),
            \'probe_id\': \'HALL-PROBE-01\'
        })

df_field = pd.DataFrame(scan_log)

print(\"=== Sequential Scanning Log (Long Format, First 5 Records) ===\")
print(df_field.head(5))

# 1. Reshape Long-Format into 2D Spatial Grid Matrix using pd.pivot_table
grid_matrix = df_field.pivot_table(index=\'y_cm\', columns=\'x_cm\', values=\'B_z_mT\')

# Invert y index so top row corresponds to positive y (Cartesian orientation)
grid_matrix = grid_matrix.sort_index(ascending=False)

print(\"\\n=== 2D Magnetic Field Matrix B_z(x, y) [mT] (Wide Format) ===\")
print(grid_matrix.to_string())

# 2. Central Field Homogeneity Assessment: Delta_B / B_0 (%)
B_center = grid_matrix.loc[0, 0]
deviation_matrix = 100.0 * (grid_matrix - B_center) / B_center

print(\"\\n=== Percentage Field Deviation Relative to Center Delta_B / B_0 (%) ===\")
print(deviation_matrix.round(2).to_string())

# 3. MultiIndex Stacking: Convert 2D Grid back into Hierarchical Series
stacked_data = grid_matrix.stack()
print(\"\\n=== Stacked Coordinate MultiIndex Series (Sample) ===\")
print(stacked_data.head(6))

# Max spatial field deviation across 4 cm x 4 cm working region
max_drop = np.abs(grid_matrix.values - B_center).max()
print(f\"\\nCoil Center Field B(0,0): {B_center:.3f} mT\")
print(f\"Max Field Inhomogeneity within r = 2 cm: {max_drop:.3f} mT ({(max_drop/B_center)*100:.2f}%)\")', '<div class=\"math-formulation\">
    <p><strong>Physical Law: Spatial Magnetic Field Uniformity in a Helmholtz Coil:</strong></p>
    <p>A Helmholtz coil pair consists of two identical circular coils of radius $R$, separated by distance equal to their radius ($d = R$). On the central transverse plane ($z = 0$), the axial magnetic field $B_z(x, y)$ exhibits maximal uniformity due to cancellation of second-order spatial derivatives:</p>
    <p>$$\\left. \\frac{\\partial B_z}{\\partial z} \\right|_{0} = 0, \\quad \\left. \\frac{\\partial^2 B_z}{\\partial z^2} \\right|_{0} = 0$$</p>
    
    <p>Off-axis in the transverse plane, the field diminishes quadratically with radial distance $r = \\sqrt{x^2 + y^2}$ according to the multipole expansion:</p>
    <p>$$B_z(r) \\approx B_0 \\left( 1 - \\alpha \\frac{r^2}{R^2} \\right)$$</p>
    
    <p><strong>Spatial Matrix Transformation (Pivot Table):</strong></p>
    <p>Laboratory data collected as sequential serial tuples $(x_i, y_i, B_i)$ in \"long format\" is mapped via a pivot transformation into a 2D Cartesian spatial grid matrix $\\mathbf{B} \\in \\mathbb{R}^{M \\times N}$:</p>
    <p>$$\\mathbf{B}_{j, k} = B_z(x_k, y_j)$$</p>
</div>', '<div class=\"theory-explanation\">
    <p><strong>1. Long vs Wide Formats in Experimental Physics:</strong></p>
    <p>Laboratory automated scanning rigs (such as 2D Hall probe positioners or automated spectrometer goniometers) output continuous streaming logs where each line records a point coordinate and a sensor reading (\"long format\"). However, physical analysis and 2D contour visualization require a 2D matrix structure (\"wide format\").</p>

    <p><strong>2. <code>pd.pivot_table()</code> for Spatial Grid Construction:</strong></p>
    <p>The <code>pivot_table()</code> function maps coordinates to index rows and columns, reorganizing 1D lists of records into coordinate matrices. If multiple measurements exist at the same coordinate $(x, y)$, it automatically aggregates them via mean or median.</p>

    <p><strong>3. Stacking & Unstacking:</strong></p>
    <ul>
        <li><code>.unstack()</code>: Pivots an index level into column headers.</li>
        <li><code>.stack()</code>: Condenses column headers back into a compact multi-index Series.</li>
    </ul>

    <p><strong>4. Field Homogeneity Evaluation:</strong></p>
    <p>By subtracting the central field $B(0, 0)$ across the matrix, researchers quantify spatial field deviations $\\frac{\\Delta B}{B_0}\\%$, establishing the effective uniform working volume of the magnet apparatus.</p>
</div>');
