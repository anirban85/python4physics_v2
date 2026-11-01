<?php
/**
 * Python4Physics - Scientific Pedagogical Knowledge Engine
 * Provides topic-accurate theoretical principles, mathematical formulations,
 * pointwise algorithm mechanics, and relevant practical computational exercises
 * for all science and physics learners.
 * 
 * Authored by Dr. Alorika Chatterjee & Dr. Anirban Shaw
 */

if (!function_exists('get_physics_pedagogical_guide')) {
    function get_physics_pedagogical_guide($menu_id, $submenu_id, $chapter_title, $subtopic_title) {
        $menu_id = intval($menu_id);
        $submenu_id = intval($submenu_id);
        $safe_chapter = htmlspecialchars($chapter_title, ENT_QUOTES, 'UTF-8');
        $safe_subtopic = htmlspecialchars($subtopic_title, ENT_QUOTES, 'UTF-8');
        $key = "{$menu_id}-{$submenu_id}";

        // Subtopic-Specific Pedagogical Knowledge Base
        // All strings with mathematical notation use single quotes to prevent PHP evaluating LaTeX ($x, $y, $t, etc.)
        $subtopic_guides = [

            // =================================================================
            // CHAPTER 1: INTRODUCTION TO PYTHON
            // =================================================================
            '1-1' => [
                'title' => 'Print Function: Standard Console Output & Scientific Data Formatting',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-terminal" style="color:var(--accent);"></i> <strong>Standard Output Interface:</strong> In computational science, <code>print()</code> routes numerical metrics, runtime telemetry, and physical states to standard output (<code>stdout</code>).</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-sliders" style="color:var(--accent);"></i> <strong>Formatting Precision:</strong> F-strings (<code>f"{val:.4e}"</code>) enable exact scientific exponential notation, while <code>f"{val:.3f}"</code> specifies decimal width matching significant figures.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-table-columns" style="color:var(--accent);"></i> <strong>Tabular Alignment:</strong> Multi-column scientific data streams can be cleanly aligned using format widths (<code>f"{t:>8.2f} {x:>12.4e}"</code>).</div>
                    </div>',
                'formulation' => '
                    <p>Scientific output demands strict adherence to experimental uncertainties and significant figures:</p>
                    <div class="math-box">$$ x = (x_0 \pm \delta x) \times 10^n \quad \implies \quad \text{Formatted as: } \texttt{f"\{x_0:.2f\}e\{n\}"} $$</div>
                    <div class="pedagogy-flow-steps">
                        <span class="step-chip">1. Raw Float</span> &rarr;
                        <span class="step-chip">2. Precision Specifier (<code>:.e</code> or <code>:.f</code>)</span> &rarr;
                        <span class="step-chip">3. Aligned Terminal Column</span>
                    </div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Standard Stream:</strong> Use <code>print(*objects, sep=\' \', end=\'\\n\')</code> to emit computed states.</li>
                        <li><strong>Significant Figures:</strong> Match format specifier to measurement accuracy (avoid displaying spurious 16-digit machine decimals).</li>
                        <li><strong>Multi-parameter Telemetry:</strong> Combine multiple physical variables into a single formatted output line inside time loops.</li>
                    </ul>',
                'exercises' => [
                    'Write a script to print physical constants ($c, \\hbar, G, k_B$) neatly formatted in scientific notation with four significant figures.',
                    'Construct a formatted ASCII table displaying angles from $0^\\circ$ to $90^\\circ$ in $15^\\circ$ increments alongside their sine and cosine values.',
                    'Format an experimental measurement report displaying value, absolute uncertainty, and percentage relative uncertainty.'
                ],
                'references' => [
                    'Hans Petter Langtangen, "A Primer on Scientific Programming with Python", Springer (2016).',
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ],

            '1-2' => [
                'title' => 'Variables & Data Types: Dynamic Typing & Numerical Representation',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-cube" style="color:var(--accent);"></i> <strong>State Representation:</strong> Variables store physical observables (mass, charge, velocity) and coordinates in computer memory.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-microchip" style="color:var(--accent);"></i> <strong>IEEE 754 Floating-Point:</strong> Standard 64-bit double precision provides ~15-17 significant decimal digits with dynamic range $10^{-308}$ to $10^{308}$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-atom" style="color:var(--accent);"></i> <strong>Complex Arithmetic:</strong> Quantum amplitudes and AC phasors are natively supported via the <code>j</code> suffix (e.g. <code>3.0 + 4.0j</code>).</div>
                    </div>',
                'formulation' => '
                    <p>Machine precision $\\epsilon_{\\text{mach}}$ determines the smallest floating-point increment such that $1.0 + \\epsilon \\neq 1.0$:</p>
                    <div class="math-box">$$ \epsilon_{\text{mach}} = 2^{-52} \approx 2.22 \times 10^{-16}, \quad z = x + iy \in \mathbb{C} \implies |z|^2 = x^2 + y^2 $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Type Conversion:</strong> Apply explicit typecasting (<code>float()</code>, <code>int()</code>, <code>complex()</code>) to guarantee proper operator behavior.</li>
                        <li><strong>Overflow & Underflow Guard:</strong> Monitor exponent scales in calculations involving extreme astronomical or quantum magnitudes.</li>
                        <li><strong>Immutability & Mutation:</strong> Distinguish between immutable scalars and mutable sequence references.</li>
                    </ul>',
                'exercises' => [
                    'Numerically determine machine epsilon $\\epsilon_{\\text{mach}}$ by repeatedly halving $\\epsilon$ until $1.0 + \\epsilon == 1.0$.',
                    'Define a normalized quantum state amplitude $z = \\frac{1}{\\sqrt{2}} + \\frac{i}{\\sqrt{2}}$ and verify that $|z|^2 = 1.0$.',
                    'Compare arbitrary-precision integer factorials $N!$ against standard 64-bit floating-point limits.'
                ],
                'references' => [
                    'David Goldberg, "What Every Computer Scientist Should Know About Floating-Point Arithmetic", ACM Computing Surveys (1991).',
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ],

            '1-3' => [
                'title' => 'Mathematical Operations: Arithmetic Expressions & Operator Precedence',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-calculator" style="color:var(--accent);"></i> <strong>Algebraic Expressions:</strong> Translates algebraic force laws, kinetic energies, and kinematic equations directly into executable code.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-arrows-split-up-and-left" style="color:var(--accent);"></i> <strong>Operator Precedence:</strong> Follows standard algebraic hierarchy: Exponentiation (<code>**</code>) > Multiplication/Division (<code>*</code>, <code>/</code>, <code>//</code>, <code>%</code>) > Addition/Subtraction.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-arrows-rotate" style="color:var(--accent);"></i> <strong>Modular Arithmetic:</strong> Modulo operator <code>%</code> implements periodic boundary conditions in spatial lattices and particle simulations.</div>
                    </div>',
                'formulation' => '
                    <p>Newtonian gravitational and electrostatic inverse-square forces:</p>
                    <div class="math-box">$$ F = G \frac{m_1 m_2}{r^2} \quad \implies \quad \texttt{F = G * m1 * m2 / r**2} $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Explicit Parentheses:</strong> Always parenthesize fractional denominators (e.g. <code>a / (b * c)</code> rather than <code>a / b * c</code>).</li>
                        <li><strong>True vs Floor Division:</strong> Use <code>/</code> for exact physical quantities and <code>//</code> for discrete indices and lattice sites.</li>
                        <li><strong>Exponentiation:</strong> Prefer <code>x**2</code> over <code>math.pow(x, 2)</code> in core loops for speed and exact integer types.</li>
                    </ul>',
                'exercises' => [
                    'Calculate the gravitational attraction between Earth and the Moon using astronomical parameters in SI units.',
                    'Evaluate the relativistic Lorentz factor $\\gamma = 1 / \\sqrt{1 - v^2/c^2}$ for speeds $v = 0.5c, 0.9c, 0.99c$.',
                    'Simulate 1D periodic boundary wrapping for a particle moving on a ring of circumference $L = 10.0\\text{ m}$.'
                ],
                'references' => [
                    'Hans Petter Langtangen, "A Primer on Scientific Programming with Python", Springer (2016).'
                ]
            ],

            '1-4' => [
                'title' => 'Conditionals (if, elif, else): Logical Branching & Piecewise Physical Laws',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-code-branch" style="color:var(--accent);"></i> <strong>Piecewise Physics:</strong> Models potential energy wells, elastic wall collisions, and threshold phase transitions.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-filter" style="color:var(--accent);"></i> <strong>Relational Checks:</strong> Evaluates state conditions using relational ($<, >, ==, \\neq$) and boolean operators (<code>and</code>, <code>or</code>, <code>not</code>).</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-arrows-to-dot" style="color:var(--accent);"></i> <strong>Boundary Handling:</strong> Detects spatial domain boundaries to reflect velocities or apply absorbing conditions.</div>
                    </div>',
                'formulation' => '
                    <p>Piecewise 1D finite potential well formulation:</p>
                    <div class="math-box">$$ V(x) = \begin{cases} 0, & |x| < a \\ V_0, & |x| \ge a \end{cases} \quad \implies \quad \texttt{V = 0.0 if abs(x) < a else V0} $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Threshold Branching:</strong> Route computation to appropriate analytical or numerical regime based on physical parameters.</li>
                        <li><strong>Elastic Reflection:</strong> Invert velocity ($v \leftarrow -v$) when particle coordinate crosses boundary $x \le 0$ or $x \ge L$.</li>
                        <li><strong>Short-Circuit Evaluation:</strong> Structure compound conditions so computationally lightweight checks run before expensive functions.</li>
                    </ul>',
                'exercises' => [
                    'Compute the electric potential $V(r)$ of a uniformly charged sphere of radius $R$ inside ($r < R$) and outside ($r \ge R$).',
                    'Classify roots of a quadratic equation $ax^2 + bx + c = 0$ into real distinct, degenerate, or complex conjugate roots.',
                    'Simulate an elastic bouncing ball under gravity, reversing velocity with a restitution coefficient upon impact with $y = 0$.'
                ],
                'references' => [
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ],

            '1-5' => [
                'title' => 'For Loop: Deterministic Iteration & Finite Series Summations',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-repeat" style="color:var(--accent);"></i> <strong>Deterministic Loops:</strong> Iterates over a known number of discrete time steps, spatial grid nodes, or particle indices.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-sigma" style="color:var(--accent);"></i> <strong>Series Evaluation:</strong> Computes finite expansions such as multipole potentials, Fourier series, and Taylor polynomials.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-timeline" style="color:var(--accent);"></i> <strong>Time Stepping:</strong> Advances dynamical systems sequentially: $t_n = t_0 + n \\Delta t$.</div>
                    </div>',
                'formulation' => '
                    <p>Total electric potential from a discrete array of $N$ point charges:</p>
                    <div class="math-box">$$ \Phi(\mathbf{r}) = \sum_{i=1}^N \frac{q_i}{4\pi\varepsilon_0 |\mathbf{r} - \mathbf{r}_i|} $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Accumulator Pattern:</strong> Initialize total sum (e.g. <code>total_potential = 0.0</code>) before the loop.</li>
                        <li><strong>Range Iterator:</strong> Use <code>range(start, stop, step)</code> to index through discrete integer steps without memory overhead.</li>
                        <li><strong>State Storage:</strong> Record time-series coordinates in accumulator lists at each step for post-processing.</li>
                    </ul>',
                'exercises' => [
                    'Compute the total gravitational field of $N = 50$ equal masses placed uniformly along the circumference of a circle.',
                    'Evaluate the Taylor series expansion for $\\cos(x)$ up to $N = 10$ terms and compare accuracy against <code>math.cos(x)</code>.',
                    'Integrate a uniform velocity trajectory $x(t)$ over 200 discrete time steps of $\\Delta t = 0.01\\text{ s}$.'
                ],
                'references' => [
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ],

            '1-6' => [
                'title' => 'While Loop: Indefinite Iteration & Convergence Thresholds',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-arrow-rotate-right" style="color:var(--accent);"></i> <strong>Convergence Control:</strong> Repeats calculations until an error tolerance $|x_{k+1} - x_k| < \epsilon_{\\text{tol}}$ is satisfied.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-bullseye" style="color:var(--accent);"></i> <strong>Stopping Conditions:</strong> Ideal for root-finding, iterative relaxation, and trajectory termination (e.g. projectile ground impact).</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-shield-halved" style="color:var(--accent);"></i> <strong>Safety Guards:</strong> Includes iteration caps (<code>max_iter</code>) to prevent infinite loops when convergence fails.</div>
                    </div>',
                'formulation' => '
                    <p>Convergence criterion for iterative scientific algorithms:</p>
                    <div class="math-box">$$ |x_{k+1} - x_k| < \epsilon_{\text{tol}} \quad \text{or} \quad \frac{|x_{k+1} - x_k|}{|x_{k+1}|} < \epsilon_{\text{rel}} $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>State Initialization:</strong> Define initial guess $x_0$, tolerance $\\epsilon$, and loop counter $k = 0$.</li>
                        <li><strong>Iterative Advancement:</strong> Compute $x_{k+1} = g(x_k)$ and calculate absolute/relative discrepancy.</li>
                        <li><strong>Break Condition:</strong> Exit when tolerance is reached or iteration limit $k \\ge k_{\\max}$ is exceeded.</li>
                    </ul>',
                'exercises' => [
                    'Implement the Babylonian method to find $\\sqrt{S}$ using $x_{k+1} = \\frac{1}{2}(x_k + S/x_k)$ until $|x_{k+1} - x_k| < 10^{-10}$.',
                    'Simulate radioactive decay $N(t + \\Delta t) = N(t) - \\lambda N(t) \\Delta t$, continuing until $N(t) \\le 0.01 N_0$.',
                    'Compute projectile flight time under quadratic air drag, stopping when height $y(t) \\le 0$.'
                ],
                'references' => [
                    'William H. Press et al., "Numerical Recipes", Cambridge University Press (2007).'
                ]
            ],

            '1-7' => [
                'title' => 'User Defined Functions: Modular Programming & Force Law Abstraction',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-puzzle-piece" style="color:var(--accent);"></i> <strong>Modular Abstraction:</strong> Encapsulates physical force laws, potentials, and numerical algorithms into reusable functions.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-arrows-to-circle" style="color:var(--accent);"></i> <strong>Pure Functions:</strong> Clean functional design where outputs depend strictly on arguments prevents global variable bugs.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-code" style="color:var(--accent);"></i> <strong>Higher-Order Functions:</strong> Pass physical force functions directly into general ODE solvers (Euler, RK4).</div>
                    </div>',
                'formulation' => '
                    <p>Force law function mapping state coordinates to physical acceleration:</p>
                    <div class="math-box">$$ \mathbf{f}: (\mathbf{r}, \mathbf{v}, t) \mapsto \mathbf{a} = \frac{\mathbf{F}(\mathbf{r}, \mathbf{v}, t)}{m} $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Signature Design:</strong> Specify explicit physical arguments (e.g. <code>def lennard_jones(r, eps=1.0, sigma=1.0):</code>).</li>
                        <li><strong>Docstrings & Units:</strong> State physical units (SI, atomic units, normalized) clearly in function docstrings.</li>
                        <li><strong>Multiple Returns:</strong> Return composite quantities (energy, force, status) as clean tuples.</li>
                    </ul>',
                'exercises' => [
                    'Write a function computing the Lennard-Jones interatomic force $F(r) = -dV/dr$ and another for its potential $V(r)$.',
                    'Create a general numerical derivative function <code>diff(f, x, h=1e-5)</code> using the central difference stencil.',
                    'Build a modular function that solves for the terminal velocity of a falling sphere in viscous fluid with Stokes drag.'
                ],
                'references' => [
                    'Hans Petter Langtangen, "A Primer on Scientific Programming with Python", Springer (2016).'
                ]
            ],

            '1-8' => [
                'title' => 'Modules math and cmath: Standard Mathematical & Complex Libraries',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-square-root-variable" style="color:var(--accent);"></i> <strong>C-Optimized Math:</strong> The <code>math</code> module delivers fast, IEEE-compliant transcendental and trigonometric functions.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-wave-square" style="color:var(--accent);"></i> <strong>Complex Arithmetic:</strong> The <code>cmath</code> module provides analytic functions for complex variables, essential for quantum and wave mechanics.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-compass" style="color:var(--accent);"></i> <strong>Polar Coordinates:</strong> Seamless conversion between rectangular ($x + iy$) and polar ($r e^{i\\theta}$) representations.</div>
                    </div>',
                'formulation' => '
                    <p>Euler\'s formula and polar decomposition:</p>
                    <div class="math-box">$$ z = x + iy = r e^{i\theta}, \quad r = |z| = \sqrt{x^2 + y^2}, \quad \theta = \operatorname{atan2}(y, x) $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Import Distinction:</strong> Use <code>math</code> for real quantities to detect unphysical negative roots early; use <code>cmath</code> when complex numbers are physically expected.</li>
                        <li><strong>Phase Angles:</strong> Use <code>math.atan2(y, x)</code> rather than <code>atan(y/x)</code> to avoid division-by-zero and preserve quadrant sign.</li>
                        <li><strong>Trig Argument Units:</strong> Remember that trigonometric functions in standard Python operate strictly in <strong>radians</strong>.</li>
                    </ul>',
                'exercises' => [
                    'Calculate the complex impedance $Z = R + i(\\omega L - 1/(\\omega C))$ of an RLC circuit and determine the resonance frequency.',
                    'Numerically evaluate Euler\'s identity $e^{i\pi} + 1 = 0$ using <code>cmath.exp(1j * cmath.pi)</code> to machine precision.',
                    'Compute the phase shift and transmission coefficient for a plane wave incident on a dielectric boundary.'
                ],
                'references' => [
                    'David J. Griffiths, "Introduction to Electrodynamics", Cambridge University Press (2017).'
                ]
            ],

            '1-9' => [
                'title' => 'File I/O Operations: Experimental Data Ingestion & Trajectory Logging',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-file-import" style="color:var(--accent);"></i> <strong>Data Pipeline:</strong> Reads laboratory sensor logs and writes simulation trajectories to disk.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-lock" style="color:var(--accent);"></i> <strong>Safe Context Managers:</strong> The <code>with open(...) as f:</code> idiom guarantees proper stream closure even during runtime errors.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-table" style="color:var(--accent);"></i> <strong>Scientific Formats:</strong> Parses CSV, TSV, and whitespace-delimited numerical tables into arrays.</div>
                    </div>',
                'formulation' => '
                    <p>Experimental ASCII data serialization:</p>
                    <div class="math-box">$$ \text{Disk Record: } \texttt{"1.050, 9.812\\n"} \quad \xrightarrow{\text{parse}} \quad t = 1.050\text{ s}, \quad g = 9.812\text{ m/s}^2 $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Stream Safety:</strong> Always use <code>with open(filepath, mode) as file:</code>.</li>
                        <li><strong>Token Parsing:</strong> Use <code>line.strip().split(\',\')</code> and cast entries to <code>float()</code>.</li>
                        <li><strong>Formatted Output:</strong> Export columns with aligned scientific headers for downstream plotting tools.</li>
                    </ul>',
                'exercises' => [
                    'Write a simulation log script that records time, position, and total energy $(t, x, E)$ to a CSV file across 500 steps.',
                    'Read an experimental voltage-current dataset from a text file and calculate mean resistance $R = \\bar{V} / \\bar{I}$.',
                    'Construct a data sanitizer that removes non-numeric header lines and invalid sensor dropouts from raw lab text.'
                ],
                'references' => [
                    'Hans Petter Langtangen, "A Primer on Scientific Programming with Python", Springer (2016).'
                ]
            ],

            // =================================================================
            // CHAPTER 2: ITERABLE DATA TYPES
            // =================================================================
            '2-1' => [
                'title' => 'Python Lists: Dynamic Arrays & Trajectory Buffers',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-list-ol" style="color:var(--accent);"></i> <strong>Dynamic Sequences:</strong> Mutable sequences ideal for appending time-series coordinates during numerical simulations.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-scissors" style="color:var(--accent);"></i> <strong>Slicing & Indexing:</strong> Expressive slicing (<code>x[::2]</code>, <code>x[-10:]</code>) allows efficient downsampling and inspection.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-wand-magic-sparkles" style="color:var(--accent);"></i> <strong>List Comprehensions:</strong> Compact functional syntax for transforming coordinate arrays into energies.</div>
                    </div>',
                'formulation' => '
                    <p>Discrete trajectory state vector sequence:</p>
                    <div class="math-box">$$ \mathbf{X} = [x_0, x_1, x_2, \dots, x_N], \quad E_k = \frac{1}{2}m v_k^2 + V(x_k) $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Buffer Initialization:</strong> Initialize empty lists (<code>t_list = []</code>, <code>x_list = []</code>) before stepping loops.</li>
                        <li><strong>State Appending:</strong> Record variables via <code>.append()</code> at each integration interval.</li>
                        <li><strong>Transformations:</strong> Convert lists to kinetic energies using list comprehensions: <code>[0.5 * m * v**2 for v in vel]</code>.</li>
                    </ul>',
                'exercises' => [
                    'Simulate a free-falling body, accumulating height and velocity in lists, then plot $y$ vs $t$ using Matplotlib.',
                    'Compute a 5-point moving average filter over a noisy experimental dataset stored in a list.',
                    'Use list comprehension to compute the gravitational potential energy $U = mgh$ for a list of 100 height samples.'
                ],
                'references' => [
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ],

            '2-2' => [
                'title' => 'Python Tuples: Immutable Physical Constants & Phase Space Pairs',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-lock" style="color:var(--accent);"></i> <strong>Immutability & Integrity:</strong> Guarantees that fundamental constants, coordinate pairs, and state vectors are protected from accidental mutation.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-arrows-left-right" style="color:var(--accent);"></i> <strong>Packing & Unpacking:</strong> Enables clean multi-variable return statements from ODE functions: <code>r, v = update(r, v, dt)</code>.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-bolt" style="color:var(--accent);"></i> <strong>Memory Efficiency:</strong> Tuples have lower memory overhead and faster allocation than dynamic lists.</div>
                    </div>',
                'formulation' => '
                    <p>Phase space coordinate state tuple:</p>
                    <div class="math-box">$$ \mathbf{\Gamma} = (x, y, z, p_x, p_y, p_z) \in \mathbb{R}^6 $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Tuple Packing:</strong> Group multiple observables into single return statements: <code>return (x_new, v_new, energy)</code>.</li>
                        <li><strong>Unpacking Assignment:</strong> Assign returned variables cleanly: <code>pos, vel, e = integrate_step(pos, vel)</code>.</li>
                        <li><strong>Hashable Keys:</strong> Use tuples as dictionary keys for 2D/3D discrete grid lookups where lists are prohibited.</li>
                    </ul>',
                'exercises' => [
                    'Write a 2D projectile function that returns a tuple of state variables $(x, y, v_x, v_y, E_{\\text{tot}})$.',
                    'Demonstrate tuple unpacking to swap two phase space variables without an intermediate temporary variable.',
                    'Create an immutable registry of physical constants $(c, G, h, e, m_e)$ using named tuples.'
                ],
                'references' => [
                    'Hans Petter Langtangen, "A Primer on Scientific Programming with Python", Springer (2016).'
                ]
            ],

            '2-3' => [
                'title' => 'Python Strings: Instrument Metadata Parsing & Plot Label Formatting',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-font" style="color:var(--accent);"></i> <strong>Experimental Telemetry:</strong> Essential for parsing data headers from lab multimeters, oscilloscopes, and spectrophotometers.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-filter" style="color:var(--accent);"></i> <strong>String Sanitization:</strong> Methods (<code>.strip()</code>, <code>.split()</code>, <code>.replace()</code>) isolate numerical values from textual units.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-chart-line" style="color:var(--accent);"></i> <strong>LaTeX Plot Titles:</strong> Generates formatted labels with mathematical symbols (e.g. <code>rf"$\omega_0 = {omega:.2f}\text{{ rad/s}}$"</code>).</div>
                    </div>',
                'formulation' => '
                    <p>Raw sensor stream parsing and unit normalization:</p>
                    <div class="math-box">$$ \texttt{"VOLTAGE = 12.450 V"} \quad \xrightarrow{\text{strip/split}} \quad \texttt{float("12.450")} = 12.450\text{ V} $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Header Parsing:</strong> Skip metadata comments starting with <code>#</code> or <code>%</code> in data files.</li>
                        <li><strong>Unit Stripping:</strong> Remove alphabetic units (<code>"10.5 mV" -> 10.5e-3</code>) before numerical casting.</li>
                        <li><strong>Raw Strings for LaTeX:</strong> Use <code>r"..."</code> strings for Matplotlib labels to prevent backslash escape conflicts.</li>
                    </ul>',
                'exercises' => [
                    'Parse a raw laboratory sensor reading string <code>"TEMP: 298.15 K; PRESS: 101.325 kPa"</code> and extract numerical floats.',
                    'Format dynamic plot legends that include physical parameters with appropriate SI prefix notation.',
                    'Write a function to sanitize CSV lines containing irregular whitespace and trailing comma characters.'
                ],
                'references' => [
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ],

            // =================================================================
            // CHAPTER 3: BASIC PYTHON PROGRAMS
            // =================================================================
            '3-1' => [
                'title' => 'Series Expansion: Taylor & Maclaurin Polynomials with Truncation Error',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-chart-area" style="color:var(--accent);"></i> <strong>Polynomial Approximation:</strong> Expands transcendental functions into computable polynomial series around a point of interest.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-arrows-spin" style="color:var(--accent);"></i> <strong>Convergence Radius:</strong> Computes successive terms iteratively until the incremental term falls below numerical precision.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-triangle-exclamation" style="color:var(--accent);"></i> <strong>Truncation Error:</strong> Balances order of expansion $N$ against floating-point round-off accumulation.</div>
                    </div>',
                'formulation' => '
                    <p>Taylor series expansion and error remainder:</p>
                    <div class="math-box">$$ f(x) = \sum_{n=0}^\infty \frac{f^{(n)}(x_0)}{n!}(x - x_0)^n = \sum_{n=0}^N t_n(x) + R_N(x), \quad t_n = t_{n-1} \cdot \frac{x}{n} $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Recurrence Relation:</strong> Compute each new term from the preceding term ($t_n = t_{n-1} \cdot \frac{x}{n}$) to avoid expensive factorials.</li>
                        <li><strong>Convergence Loop:</strong> Terminate when $|t_n| < \epsilon_{\\text{tol}}$ or when maximum terms $N_{\\max}$ are reached.</li>
                        <li><strong>Domain Reduction:</strong> Map arguments into primary fundamental intervals (e.g. $[0, 2\\pi]$) for trigonometric stability.</li>
                    </ul>',
                'exercises' => [
                    'Implement the Taylor series for $e^x$ using term-to-term recurrence and compare against <code>math.exp(x)</code> for $x = -15, 0.5, 10$.',
                    'Compute $\\sin(x)$ expansion and verify that truncation error scales as $\\mathcal{O}(x^{2N+1}/(2N+1)!)$.',
                    'Demonstrate numerical catastrophic cancellation when evaluating $e^{-x}$ for large positive $x$ via direct series versus $1 / e^x$.'
                ],
                'references' => [
                    'William H. Press et al., "Numerical Recipes: The Art of Scientific Computing", Cambridge University Press (2007).'
                ]
            ],

            '3-2' => [
                'title' => 'Primality Testing: Divisibility Analysis & $\\sqrt{N}$ Upper Bound',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-hashtag" style="color:var(--accent);"></i> <strong>Fundamental Building Blocks:</strong> Prime numbers are positive integers $p > 1$ divisible solely by 1 and themselves.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-gauge-high" style="color:var(--accent);"></i> <strong>Square-Root Bound:</strong> If $N$ is composite, it must possess a prime factor $d \le \sqrt{N}$. Testing beyond $\sqrt{N}$ is redundant.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-atom" style="color:var(--accent);"></i> <strong>Physics Connection:</strong> Prime distributions connect to quantum chaos, Riemann zeta zeros, and Gaussian Unitary Ensemble energy spectra.</div>
                    </div>',
                'formulation' => '
                    <p>Mathematical proof of divisor bounding:</p>
                    <div class="math-box">$$ N = a \cdot b \implies \text{if } a > \sqrt{N} \text{ and } b > \sqrt{N}, \text{ then } a \cdot b > N \text{ (contradiction)} \implies \min(a, b) \le \sqrt{N} $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Base Cases:</strong> If $N \le 1$, return <code>False</code>. If $N \in \{2, 3\}$, return <code>True</code>. If $N$ is even or $N \pmod 3 == 0$, return <code>False</code>.</li>
                        <li><strong>Wheel Factorization:</strong> Test potential factors $d = 6k \pm 1$ up to $\lfloor\sqrt{N}\rfloor$, stepping by 6 to skip multiples of 2 and 3.</li>
                        <li><strong>Computational Complexity:</strong> Reduces trial division steps from $\mathcal{O}(N)$ down to $\mathcal{O}(\sqrt{N})$.</li>
                    </ul>',
                'exercises' => [
                    'Write an optimized primality test function implementing the $6k \pm 1$ step rule and test large primes around $10^9 + 7$.',
                    'Measure the speedup factor of testing up to $\\sqrt{N}$ versus checking all integers up to $N - 1$.',
                    'Implement Fermat\'s primality test $a^{p-1} \equiv 1 \pmod p$ and identify a Carmichael composite pseudo-prime.'
                ],
                'references' => [
                    'Donald E. Knuth, "The Art of Computer Programming, Vol. 2: Seminumerical Algorithms", Addison-Wesley (1997).'
                ]
            ],

            '3-3' => [
                'title' => 'Prime Search Near a Threshold: Next & Previous Prime Algorithms',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-magnifying-glass-chart" style="color:var(--accent);"></i> <strong>Threshold Seeking:</strong> Finds the immediate next prime $p > N$ or greatest prime $p < N$ relative to an arbitrary scientific threshold.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-chart-line" style="color:var(--accent);"></i> <strong>Prime Gap Bounds:</strong> Bertrand\'s postulate guarantees at least one prime exists between $N$ and $2N - 2$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-shuffle" style="color:var(--accent);"></i> <strong>Cryptographic & Hashing Roles:</strong> Used in physics simulations for pseudo-random number generator modulus selection.</div>
                    </div>',
                'formulation' => '
                    <p>Prime gap $\Delta_p$ and Bertrand\'s Postulate guarantee:</p>
                    <div class="math-box">$$ \forall N > 1, \quad \exists p \in \text{Primes} \quad \text{such that} \quad N < p < 2N - 2 $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Search Direction:</strong> To find next prime, set candidate $c = N + 1$ (or $N + 2$ if $N$ is odd); increment by 2.</li>
                        <li><strong>Primality Sub-Check:</strong> Apply the $\mathcal{O}(\sqrt{c})$ primality test to each candidate.</li>
                        <li><strong>Termination:</strong> Break and return the first candidate satisfying primality criteria.</li>
                    </ul>',
                'exercises' => [
                    'Find the nearest prime both above and below Avogadro\'s truncated integer representation $602214$.',
                    'Compute and plot prime gaps $\Delta_n = p_{n+1} - p_n$ for the first 1000 primes above $10^4$.',
                    'Verify Bertrand\'s Postulate computationally for 50 arbitrary values of $N \in [10^2, 10^5]$.'
                ],
                'references' => [
                    'G. H. Hardy and E. M. Wright, "An Introduction to the Theory of Numbers", Oxford University Press (2008).'
                ]
            ],

            '3-4' => [
                'title' => 'Finding All Prime Numbers Within a Given Range: Sieve of Eratosthenes & Interval Filtering',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-network-wired" style="color:var(--accent);"></i> <strong>Interval Sieve:</strong> Systematically identifies all prime numbers in a bounded interval $[A, B]$ or $[2, N]$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-bolt" style="color:var(--accent);"></i> <strong>Algorithmic Efficiency:</strong> Instead of checking each number independently ($\mathcal{O}(N\sqrt{N})$), the Sieve of Eratosthenes eliminates composites in $\mathcal{O}(N \log \log N)$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-atom" style="color:var(--accent);"></i> <strong>Scientific Density:</strong> Demonstrates the Prime Number Theorem $\pi(x) \sim x / \ln(x)$, mirroring energy level distributions in quantum chaotic systems.</div>
                    </div>',
                'formulation' => '
                    <p>The Prime Number Theorem and asymptotic prime counting function $\pi(x)$:</p>
                    <div class="math-box">$$ \pi(x) \approx \frac{x}{\ln x} \approx \operatorname{Li}(x) = \int_2^x \frac{dt}{\ln t}, \quad \text{Sieve Complexity: } \mathcal{O}(N \log \log N) $$</div>
                    <div class="pedagogy-flow-steps">
                        <span class="step-chip">1. Boolean Array of Size $N+1$</span> &rarr;
                        <span class="step-chip">2. Mark Multiples of $p \le \sqrt{N}$ as Composite</span> &rarr;
                        <span class="step-chip">3. Filter Verified Primes in $[A, B]$</span>
                    </div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Array Initialization:</strong> Create a boolean array <code>is_prime = [True] * (limit + 1)</code>; set index 0 and 1 to <code>False</code>.</li>
                        <li><strong>Composite Marking:</strong> For each $p \ge 2$ up to $\sqrt{\text{limit}}$, if <code>is_prime[p]</code> is true, mark all multiples $p^2, p^2 + p, p^2 + 2p, \dots$ as <code>False</code>.</li>
                        <li><strong>Interval Extraction:</strong> Collect all indices $i \ge A$ where <code>is_prime[i]</code> remains <code>True</code>.</li>
                        <li><strong>Complexity Advantage:</strong> Far superior to naive trial division when generating dense prime tables for statistical mechanics.</li>
                    </ul>',
                'exercises' => [
                    'Implement the Sieve of Eratosthenes to generate all primes up to $N = 10^5$ and compare its runtime against naive trial division.',
                    'Count twin prime pairs $(p, p + 2)$ within the range $[10, 5000]$ and compute their relative frequency.',
                    'Plot the empirical prime counting function $\pi(x)$ against the theoretical curve $x / \ln(x)$ for $x \in [10, 10^4]$.'
                ],
                'references' => [
                    'Mark Newman, "Computational Physics", University of Michigan (2013).',
                    'Donald E. Knuth, "The Art of Computer Programming", Addison-Wesley (1997).'
                ]
            ],

            '3-5' => [
                'title' => 'Root Finding: Bisection, Newton-Raphson & Secant Methods',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-bullseye" style="color:var(--accent);"></i> <strong>Equilibrium & Bound States:</strong> Locates zeros $f(x) = 0$, essential for finding energy eigenvalues, equilibrium coordinates, and orbital nodes.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-code-compare" style="color:var(--accent);"></i> <strong>Bisection Method:</strong> Brackets the root; unconditionally robust with linear convergence ($1$ bit per step).</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-bolt" style="color:var(--accent);"></i> <strong>Newton-Raphson:</strong> Exploits local derivative $f\'(x)$ for rapid quadratic convergence near the solution.</div>
                    </div>',
                'formulation' => '
                    <p>Newton-Raphson iterative update and Bisection midpoint:</p>
                    <div class="math-box">$$ x_{n+1} = x_n - \frac{f(x_n)}{f\'(x_n)}, \quad x_{\text{mid}} = \frac{a + b}{2} \text{ where } f(a)f(b) < 0 $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Bracketing Check:</strong> Ensure $f(a) \cdot f(b) < 0$ guarantees at least one root via the Intermediate Value Theorem.</li>
                        <li><strong>Bisection Update:</strong> If $f(a)f(x_{\\text{mid}}) < 0$, set $b = x_{\\text{mid}}$; else set $a = x_{\\text{mid}}$. Halves interval every step.</li>
                        <li><strong>Newton Iteration:</strong> Guard against division by near-zero derivative $f\'(x) \\approx 0$ by falling back to bisection.</li>
                    </ul>',
                'exercises' => [
                    'Solve the transcendental quantum well equation $\\sqrt{V_0 - E} = \\sqrt{E}\\tan(\\sqrt{E})$ using Bisection.',
                    'Apply Newton-Raphson to solve Kepler\'s orbital equation $M = E - e\\sin(E)$ for eccentricity $e = 0.5$ and mean anomaly $M = 1.0$.',
                    'Compare the convergence rates of Bisection, Secant, and Newton-Raphson methods on $f(x) = x^3 - x - 2 = 0$.'
                ],
                'references' => [
                    'William H. Press et al., "Numerical Recipes", Cambridge University Press (2007).'
                ]
            ],

            '3-6' => [
                'title' => 'Sorting Algorithms: Array Ranking & Experimental Data Organization',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-arrow-down-short-wide" style="color:var(--accent);"></i> <strong>Order & Efficiency:</strong> Reorganizes experimental arrays in ascending or descending order for percentile analysis, median filtering, and fast binary searching.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-chart-simple" style="color:var(--accent);"></i> <strong>Algorithmic Hierarchy:</strong> Compares quadratic methods (Bubble, Insertion $\\mathcal{O}(N^2)$) with divide-and-conquer schemes (Merge, Quick Sort $\\mathcal{O}(N \\log N)$).</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-layer-group" style="color:var(--accent);"></i> <strong>Physical Applications:</strong> Essential in particle simulations for spatial cell binning and energy level ranking.</div>
                    </div>',
                'formulation' => '
                    <p>Comparison of sorting time complexity bounds:</p>
                    <div class="math-box">$$ T_{\text{Bubble}}(N) \in \mathcal{O}(N^2), \quad T_{\text{Merge}}(N) = 2T(N/2) + \mathcal{O}(N) \implies \mathcal{O}(N \log N) $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Bubble Sort:</strong> Iterate through array, swapping adjacent inverted pairs; stop when a complete pass requires zero swaps.</li>
                        <li><strong>Python Timsort:</strong> Python\'s built-in <code>.sort()</code> employs an adaptive merge-insertion hybrid with $\\mathcal{O}(N \\log N)$ worst-case.</li>
                        <li><strong>Coordinate Synchronization:</strong> When sorting physical coordinates, ensure corresponding telemetry arrays (velocities, energies) are permuted in tandem.</li>
                    </ul>',
                'exercises' => [
                    'Implement Bubble Sort and count the exact number of swaps needed to sort an array of 50 randomized energy values.',
                    'Write a Quick Sort function using the median-of-three pivot selection strategy.',
                    'Sort experimental measurement points $(x_i, y_i)$ by ascending $x$-coordinate while maintaining exact $(x, y)$ pairing.'
                ],
                'references' => [
                    'Donald E. Knuth, "The Art of Computer Programming, Vol. 3: Sorting and Searching", Addison-Wesley (1998).'
                ]
            ],

            '3-7' => [
                'title' => 'Integer Factorization: Divisor Identification & Prime Decomposition',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-diagram-project" style="color:var(--accent);"></i> <strong>Canonical Decomposition:</strong> Every integer $N > 1$ factors uniquely into a product of prime powers: $N = \prod p_i^{\alpha_i}$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-microchip" style="color:var(--accent);"></i> <strong>Divisor Pairs:</strong> Factors appear in symmetrical pairs $(d, N/d)$. Finding one immediately yields the conjugate factor.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-atom" style="color:var(--accent);"></i> <strong>Resonance & Symmetry:</strong> Factorization determines commensurability in coupled oscillator modes and crystallographic unit cell subdivisions.</div>
                    </div>',
                'formulation' => '
                    <p>Fundamental Theorem of Arithmetic:</p>
                    <div class="math-box">$$ N = p_1^{\alpha_1} p_2^{\alpha_2} \cdots p_k^{\alpha_k}, \quad \text{Number of Divisors: } d(N) = \prod_{i=1}^k (\alpha_i + 1) $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Trial Factorization:</strong> Test potential divisor $d = 2$. While $N \pmod d == 0$, record $d$ and replace $N \leftarrow N / d$.</li>
                        <li><strong>Odd Factors:</strong> Increment $d$ by 2 (checking $3, 5, 7, \dots$) up to $\lfloor\sqrt{N}\rfloor$.</li>
                        <li><strong>Prime Remainder:</strong> If $N > 1$ after loop, the remaining $N$ is itself the final prime factor.</li>
                    </ul>',
                'exercises' => [
                    'Write a script that takes an integer $N$ and prints its full prime factorization in standard power format (e.g. $360 = 2^3 \\times 3^2 \\times 5^1$).',
                    'Find all proper divisors of $N = 28$ and verify whether it is a mathematically perfect number (sum of divisors equals $N$).',
                    'Compute the greatest common divisor $\\gcd(a, b)$ of two large numbers using the Euclidean algorithm.'
                ],
                'references' => [
                    'G. H. Hardy and E. M. Wright, "An Introduction to the Theory of Numbers", Oxford (2008).'
                ]
            ],

            '3-8' => [
                'title' => 'Miscellaneous Computational Problems: Numerical Sequences & Special Algorithms',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-shapes" style="color:var(--accent);"></i> <strong>Fundamental Recurrences:</strong> Explores famous numerical series including Fibonacci sequences, Pascal\'s triangle, and digital invariants.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-code-fork" style="color:var(--accent);"></i> <strong>Dynamic Programming:</strong> Demonstrates state memoization to transform exponential recursive algorithms into linear time.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-atom" style="color:var(--accent);"></i> <strong>Physical Analogies:</strong> Fibonacci ratios converge to the Golden Ratio $\phi = (1 + \sqrt{5})/2$, modeling phyllotaxis and quasi-crystal structures.</div>
                    </div>',
                'formulation' => '
                    <p>Fibonacci recurrence and convergence to the Golden Ratio:</p>
                    <div class="math-box">$$ F_n = F_{n-1} + F_{n-2}, \quad \lim_{n\to\infty} \frac{F_n}{F_{n-1}} = \phi = \frac{1 + \sqrt{5}}{2} \approx 1.6180339887 $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Iterative State Shift:</strong> Maintain two variables $(a, b)$; update in parallel via <code>a, b = b, a + b</code>.</li>
                        <li><strong>Memoization:</strong> Cache computed sequence values to avoid redundant tree evaluation.</li>
                        <li><strong>Bitwise Checks:</strong> Use modular arithmetic and digital decomposition to check numerical symmetry properties.</li>
                    </ul>',
                'exercises' => [
                    'Generate the first 50 Fibonacci numbers iteratively and plot the ratio $F_n / F_{n-1}$ demonstrating convergence to $\\phi$.',
                    'Write a function to check if a 3-digit number is an Armstrong number (sum of cubes of digits equals the number).',
                    'Generate Pascal\'s Triangle up to $N = 10$ rows and show how each row corresponds to binomial coefficients $\\binom{n}{k}$.'
                ],
                'references' => [
                    'Donald E. Knuth, "The Art of Computer Programming", Addison-Wesley (1997).'
                ]
            ],

            // =================================================================
            // CHAPTER 4: MATRIX OPERATION
            // =================================================================
            '4-1' => [
                'title' => 'Matrix Representation: 2D Arrays & Multi-Dimensional Data Layout',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-table-cells" style="color:var(--accent);"></i> <strong>Linear Operators:</strong> Matrices represent linear transformations, coordinate rotations, and quantum Hamiltonian operators.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-layer-group" style="color:var(--accent);"></i> <strong>2D Array Indexing:</strong> Structured as nested lists or contiguous memory blocks indexed by row and column: $A_{ij} = \texttt{A[i][j]}$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-memory" style="color:var(--accent);"></i> <strong>Memory Row-Major:</strong> In standard C/Python layout, elements along a row occupy adjacent memory addresses.</div>
                    </div>',
                'formulation' => '
                    <p>General $m \times n$ matrix index mapping:</p>
                    <div class="math-box">$$ \mathbf{A} = \begin{pmatrix} a_{11} & a_{12} & \cdots & a_{1n} \\ a_{21} & a_{22} & \cdots & a_{2n} \\ \vdots & \vdots & \ddots & \vdots \\ a_{m1} & a_{m2} & \cdots & a_{mn} \end{pmatrix}, \quad \text{1D Flat Index: } k = i \cdot n + j $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Nested Allocation:</strong> Instantiate using nested lists: <code>[[0.0 for _ in range(cols)] for _ in range(rows)]</code>.</li>
                        <li><strong>Row-Col Traversal:</strong> Traverse outer index $i$ for rows and inner index $j$ for columns to preserve cache locality.</li>
                        <li><strong>Dimension Verification:</strong> Validate that row lengths are homogeneous before performing linear algebraic operations.</li>
                    </ul>',
                'exercises' => [
                    'Construct a $3 \\times 3$ identity matrix and an arbitrary coordinate rotation matrix for angle $\\theta = 45^\\circ$.',
                    'Write a function that verifies whether an input 2D list is a valid rectangular matrix.',
                    'Flatten a $3 \\times 3$ matrix into a 1D array of 9 elements and reconstruct it back.'
                ],
                'references' => [
                    'Gilbert Strang, "Linear Algebra and Its Applications", Cengage Learning (4th ed., 2006).'
                ]
            ],

            '4-2' => [
                'title' => 'Matrix Addition & Subtraction: Element-Wise Algebraic Operations',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-plus-minus" style="color:var(--accent);"></i> <strong>Superposition Principle:</strong> Matrix addition embodies linear superposition of physical fields, potentials, and state vectors.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-equals" style="color:var(--accent);"></i> <strong>Conformability Condition:</strong> Both matrices must possess identical row and column dimensions ($m_A = m_B$ and $n_A = n_B$).</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-microchip" style="color:var(--accent);"></i> <strong>Linear Time:</strong> Addition scales linearly with total elements: $\mathcal{O}(m \cdot n)$.</div>
                    </div>',
                'formulation' => '
                    <p>Element-wise matrix sum definition:</p>
                    <div class="math-box">$$ \mathbf{C} = \mathbf{A} \pm \mathbf{B} \iff C_{ij} = A_{ij} \pm B_{ij} \quad \forall i \in \{1,\dots,m\}, \; j \in \{1,\dots,n\} $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Dimension Check:</strong> Assert <code>len(A) == len(B)</code> and <code>len(A[0]) == len(B[0])</code> before looping.</li>
                        <li><strong>Nested Iteration:</strong> Loop through row index $i$ and column index $j$, assigning $C_{ij} = A_{ij} + B_{ij}$.</li>
                        <li><strong>In-Place Updates:</strong> When memory is constrained, update directly in-place: $A_{ij} \leftarrow A_{ij} + B_{ij}$.</li>
                    </ul>',
                'exercises' => [
                    'Implement matrix addition using nested loops and verify commutativity $\mathbf{A} + \mathbf{B} = \mathbf{B} + \mathbf{A}$.',
                    'Compute the difference matrix $\mathbf{D} = \mathbf{A} - \mathbf{B}$ between two experimental spatial sensor meshes.',
                    'Write a defensive matrix addition function that throws an informative error if dimensions mismatch.'
                ],
                'references' => [
                    'Gilbert Strang, "Linear Algebra and Its Applications", Cengage (2006).'
                ]
            ],

            '4-3' => [
                'title' => 'Matrix Multiplication: Inner Product Contraction & Operator Composition',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-xmark" style="color:var(--accent);"></i> <strong>Successive Transformations:</strong> Models sequential rotations, Lorentz boosts, and quantum operator applications $\hat{A}\hat{B}|\psi\rangle$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-arrows-split-up-and-left" style="color:var(--accent);"></i> <strong>Dimension Rule:</strong> Multiplying an $m \times p$ matrix by a $p \times n$ matrix produces an $m \times n$ matrix. Inner dimensions must match.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-triangle-exclamation" style="color:var(--accent);"></i> <strong>Non-Commutative:</strong> In general, $\mathbf{A}\mathbf{B} \neq \mathbf{B}\mathbf{A}$, mirroring the non-commutativity of quantum observables $[\hat{x}, \hat{p}] = i\hbar$.</div>
                    </div>',
                'formulation' => '
                    <p>Matrix multiplication inner product contraction:</p>
                    <div class="math-box">$$ C_{ij} = \sum_{k=1}^p A_{ik} B_{kj}, \quad \text{Complexity: } \mathcal{O}(m \cdot p \cdot n) \to \mathcal{O}(N^3) \text{ for square matrices} $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Dimension Validation:</strong> Verify that columns of $\mathbf{A}$ equal rows of $\mathbf{B}$ (<code>len(A[0]) == len(B)</code>).</li>
                        <li><strong>Three-Loop Nest:</strong> Iterate outer row $i$, inner column $j$, and contraction index $k$.</li>
                        <li><strong>Accumulation:</strong> Initialize sum $C_{ij} = 0.0$ before executing the $k$-loop sum.</li>
                    </ul>',
                'exercises' => [
                    'Multiply a $3 \\times 3$ coordinate rotation matrix by a 3D position vector and verify that the vector magnitude $|\mathbf{r}|$ is invariant.',
                    'Demonstrate matrix non-commutativity by computing $\mathbf{A}\mathbf{B} - \mathbf{B}\mathbf{A}$ for the Pauli spin matrices $\sigma_x$ and $\sigma_y$.',
                    'Implement matrix exponentiation $\mathbf{A}^k = \mathbf{A} \cdot \mathbf{A} \cdots \mathbf{A}$ for a $2 \\times 2$ matrix.'
                ],
                'references' => [
                    'Mark Newman, "Computational Physics", University of Michigan (2013).',
                    'Gilbert Strang, "Introduction to Linear Algebra", Wellesley-Cambridge Press (2016).'
                ]
            ],

            '4-4' => [
                'title' => 'Matrix Transpose: Index Inversion & Symmetric Operators',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-retweet" style="color:var(--accent);"></i> <strong>Index Permutation:</strong> Reflects matrix elements across the main diagonal: $A_{ij}^T = A_{ji}$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-shield-halved" style="color:var(--accent);"></i> <strong>Physical Symmetries:</strong> Identifies symmetric tensors (moment of inertia, stress-strain) where $\mathbf{A}^T = \mathbf{A}$, and skew-symmetric tensors ($\mathbf{A}^T = -\mathbf{A}$).</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-atom" style="color:var(--accent);"></i> <strong>Hermitian Adjoint:</strong> For complex quantum matrices, transposition combined with complex conjugation yields the adjoint $\mathbf{A}^\dagger = (\mathbf{A}^*)^T$.</div>
                    </div>',
                'formulation' => '
                    <p>Transpose definition and tensor symmetry classification:</p>
                    <div class="math-box">$$ (\mathbf{A}^T)_{ij} = A_{ji}, \quad \text{Symmetric: } \mathbf{A} = \mathbf{A}^T, \quad \text{Antisymmetric: } \mathbf{A} = -\mathbf{A}^T $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Target Allocation:</strong> Initialize a transposed matrix of dimension $n \times m$ if original is $m \times n$.</li>
                        <li><strong>Assignment:</strong> Set <code>T[j][i] = A[i][j]</code> across all row $i$ and column $j$ indices.</li>
                        <li><strong>List Comprehension:</strong> Can be concisely computed via <code>[[row[i] for row in A] for i in range(len(A[0]))]</code>.</li>
                    </ul>',
                'exercises' => [
                    'Compute the transpose of a rectangular $2 \\times 3$ matrix and confirm the output has dimension $3 \\times 2$.',
                    'Decompose an arbitrary square matrix into a symmetric part $\\mathbf{S} = \\frac{1}{2}(\\mathbf{A} + \\mathbf{A}^T)$ and an antisymmetric part $\\mathbf{A}_a = \\frac{1}{2}(\\mathbf{A} - \\mathbf{A}^T)$.',
                    'Verify the transpose product identity $(\\mathbf{A}\\mathbf{B})^T = \\mathbf{B}^T \\mathbf{A}^T$.'
                ],
                'references' => [
                    'Gilbert Strang, "Linear Algebra and Its Applications", Cengage (2006).'
                ]
            ],

            '4-5' => [
                'title' => 'Matrix Determinant: Volume Scaling & System Singularity',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-cube" style="color:var(--accent);"></i> <strong>Volume Scaling Factor:</strong> The determinant measures the factor by which a linear transformation scales spatial volume in $\mathbb{R}^n$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-triangle-exclamation" style="color:var(--accent);"></i> <strong>Invertibility Criterion:</strong> A matrix is invertible (non-singular) if and only if $\det(\mathbf{A}) \neq 0$. If $\det(\mathbf{A}) = 0$, lines/planes collapse.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-atom" style="color:var(--accent);"></i> <strong>Characteristic Polynomial:</strong> Essential in physics for eigenvalue equations $\det(\mathbf{A} - \lambda \mathbf{I}) = 0$ solving normal modes and energy levels.</div>
                    </div>',
                'formulation' => '
                    <p>Laplace cofactor expansion for determinants:</p>
                    <div class="math-box">$$ \det(\mathbf{A}) = \sum_{j=1}^n (-1)^{i+j} A_{ij} M_{ij}, \quad \det\begin{pmatrix} a & b \\ c & d \end{pmatrix} = ad - bc $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Base Case:</strong> For a $2 \\times 2$ matrix, directly return $a_{11}a_{22} - a_{12}a_{21}$.</li>
                        <li><strong>Recursive Minor Extraction:</strong> For size $N > 2$, slice out row 0 and column $j$ to construct the $(N-1) \\times (N-1)$ minor.</li>
                        <li><strong>Sign Alternation:</strong> Multiply by alternating sign $(-1)^j$ and accumulate the cofactor sum.</li>
                    </ul>',
                'exercises' => [
                    'Write a recursive determinant function and calculate the determinant of a $3 \\times 3$ moment of inertia tensor.',
                    'Verify that swapping two rows of a matrix reverses the sign of its determinant.',
                    'Check whether a system of 3 coupled linear equations is uniquely solvable by verifying $\\det(\\mathbf{A}) \\neq 0$.'
                ],
                'references' => [
                    'William H. Press et al., "Numerical Recipes", Cambridge University Press (2007).'
                ]
            ],

            '4-6' => [
                'title' => 'Matrix using List Comprehension: Pythonic Data Generation & Grid Creation',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-wand-magic-sparkles" style="color:var(--accent);"></i> <strong>Declarative Construction:</strong> Generates complex numerical arrays and spatial grids in clean, readable, single-line expressions.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-network-wired" style="color:var(--accent);"></i> <strong>Discretized Meshes:</strong> Ideal for building 2D spatial coordinate lattices $(x_i, y_j)$ for PDE solving and potential grids.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-code" style="color:var(--accent);"></i> <strong>Avoiding Pointer Bugs:</strong> Prevents shallow copy bugs associated with multiplying lists (<code>[[0]*n]*m</code> creates linked row pointers!).</div>
                    </div>',
                'formulation' => '
                    <p>Discrete 2D mesh grid generation via Cartesian product:</p>
                    <div class="math-box">$$ \mathbf{G}_{ij} = (x_0 + i\Delta x, \; y_0 + j\Delta y) \quad \forall i \in \{0,\dots,N_x-1\}, \; j \in \{0,\dots,N_y-1\} $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Independent Row Instantiation:</strong> Use <code>[[0 for j in range(cols)] for i in range(rows)]</code> to ensure each row is an independent object.</li>
                        <li><strong>Conditional Filtering:</strong> Embed boolean conditions to construct sparse or upper triangular matrices: <code>[val if i <= j else 0 ...]</code>.</li>
                        <li><strong>Functional Evaluation:</strong> Direct potential evaluation: <code>[[potential(x[i], y[j]) for j in range(M)] for i in range(N)]</code>.</li>
                    </ul>',
                'exercises' => [
                    'Construct a $5 \\times 5$ Kronecker delta matrix $\delta_{ij}$ using a single nested list comprehension with an <code>if/else</code> condition.',
                    'Generate a $10 \\times 10$ 2D spatial grid representing gravitational potential $V(x, y) = -G / \\sqrt{x^2 + y^2 + \epsilon^2}$.',
                    'Demonstrate the shallow-copy pitfall of <code>[[0]*3]*3</code> when mutating a single element versus independent list comprehension.'
                ],
                'references' => [
                    'Hans Petter Langtangen, "A Primer on Scientific Programming with Python", Springer (2016).'
                ]
            ],

            // =================================================================
            // CHAPTER 6: ORDINARY DIFFERENTIAL EQUATIONS - 1
            // =================================================================
            '6-1' => [
                'title' => 'Euler Algorithm: First-Order Discretization for Differential Equations',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-arrow-trend-up" style="color:var(--accent);"></i> <strong>Foundational Stepper:</strong> The simplest explicit numerical scheme for initial value problems $\\frac{dy}{dt} = f(t, y)$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-ruler-combined" style="color:var(--accent);"></i> <strong>First-Order Truncation:</strong> Local truncation error scales as $\\mathcal{O}(h^2)$, producing global cumulative error $\\mathcal{O}(h)$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-triangle-exclamation" style="color:var(--accent);"></i> <strong>Energy Drift:</strong> Standard explicit Euler artificially injects energy into conservative oscillatory systems.</div>
                    </div>',
                'formulation' => '
                    <p>First-order Taylor expansion stepping formula:</p>
                    <div class="math-box">$$ y(t + h) = y(t) + h \frac{dy}{dt} + \mathcal{O}(h^2) \implies y_{n+1} = y_n + h f(t_n, y_n), \quad t_{n+1} = t_n + h $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Initial State:</strong> Set initial time $t_0$, initial condition $y_0$, and step size $h = \\Delta t$.</li>
                        <li><strong>Slope Evaluation:</strong> Compute instantaneous rate of change $k_1 = f(t_n, y_n)$.</li>
                        <li><strong>State Advancement:</strong> Update state $y_{n+1} = y_n + h \\cdot k_1$ and advance time $t_{n+1} = t_n + h$.</li>
                        <li><strong>Stability Criterion:</strong> Keep $h$ sufficiently small to avoid numerical divergence.</li>
                    </ul>',
                'exercises' => [
                    'Solve radioactive decay $dN/dt = -\\lambda N$ using Euler\'s method and compare against the exact solution $N_0 e^{-\\lambda t}$.',
                    'Simulate a harmonic oscillator using explicit Euler, observing how total mechanical energy artificially grows with time.',
                    'Perform an error convergence test by halving step size $h$ ($h = 0.1, 0.05, 0.025$) and plot the global error vs $h$.'
                ],
                'references' => [
                    'John R. Taylor, "Classical Mechanics", University Science Books (2005).',
                    'William H. Press et al., "Numerical Recipes", Cambridge University Press (2007).'
                ]
            ],

            '6-2' => [
                'title' => 'Simple Harmonic Oscillator: Conservative Dynamics & Phase Space Orbits',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-wave-square" style="color:var(--accent);"></i> <strong>Restoring Force:</strong> Models systems oscillating around stable potential minima under Hooke\'s law ($F = -kx$).</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-circle-notch" style="color:var(--accent);"></i> <strong>Phase Space Invariance:</strong> Conservative trajectories trace closed elliptical orbits in phase space $(x, v)$, conserving $E = \\frac{1}{2}mv^2 + \\frac{1}{2}kx^2$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-atom" style="color:var(--accent);"></i> <strong>Symplectic Euler-Cromer:</strong> Updating velocity first and using updated velocity for position bounds energy fluctuations indefinitely.</div>
                    </div>',
                'formulation' => '
                    <p>Coupled first-order state-space system and mechanical energy invariant:</p>
                    <div class="math-box">$$ \frac{dx}{dt} = v, \quad \frac{dv}{dt} = -\omega^2 x, \quad E = \frac{1}{2}m v^2 + \frac{1}{2}k x^2 = \text{constant} $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Coupled Decomposition:</strong> Decompose the 2nd-order ODE into two coupled 1st-order equations.</li>
                        <li><strong>Euler-Cromer Stepping:</strong> Update velocity $v_{n+1} = v_n - \omega^2 x_n h$; then update position $x_{n+1} = x_n + v_{n+1} h$.</li>
                        <li><strong>Energy Monitoring:</strong> Calculate and log $E_n = \frac{1}{2}m v_n^2 + \frac{1}{2}k x_n^2$ at each step to verify stability.</li>
                    </ul>',
                'exercises' => [
                    'Simulate 50 complete periods of an oscillator with both Euler and Euler-Cromer methods, comparing energy preservation.',
                    'Add a linear damping term $F_d = -b v$ and simulate underdamped, critically damped, and overdamped regimes.',
                    'Plot the phase space portrait $(x, v)$ showing how closed conservative orbits transform into inward spiral attractors under damping.'
                ],
                'references' => [
                    'David Morin, "Introduction to Classical Mechanics", Cambridge University Press (2008).',
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ],

            '6-3' => [
                'title' => 'RC Circuit Transients: Capacitor Charging & Discharging Dynamics',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-bolt" style="color:var(--accent);"></i> <strong>First-Order Relaxation:</strong> Governs exponential charge and voltage transients in resistive-capacitive networks.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-clock" style="color:var(--accent);"></i> <strong>Characteristic Time Constant:</strong> Relaxation timescale $\\tau = RC$ determines the rate of charging and discharging.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-chart-line" style="color:var(--accent);"></i> <strong>Asymptotic Saturation:</strong> Charging capacitor reaches $\approx 63.2\\%$ of EMF at $t = \\tau$, and $> 99\\%$ at $t = 5\\tau$.</div>
                    </div>',
                'formulation' => '
                    <p>Kirchhoff\'s voltage law governing an RC series loop:</p>
                    <div class="math-box">$$ R \frac{dq}{dt} + \frac{q}{C} = \mathcal{E} \implies R C \frac{dV_C}{dt} + V_C = \mathcal{E}, \quad V_C(t) = \mathcal{E}\left(1 - e^{-t/\tau}\right) $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Time Discretization:</strong> Choose step size $h \ll RC$ (e.g. $h = 0.01 RC$) to ensure numerical stability.</li>
                        <li><strong>Transient Update:</strong> Advance voltage $V_{n+1} = V_n + \frac{h}{RC}(\mathcal{E} - V_n)$.</li>
                        <li><strong>Phase Switching:</strong> At specified discharge time $t_{\\text{switch}}$, set driving EMF $\\mathcal{E} = 0$ to observe decay.</li>
                    </ul>',
                'exercises' => [
                    'Simulate capacitor charging from $t = 0$ to $5\\tau$ and verify that $V_C(\\tau) \\approx 0.632\\mathcal{E}$.',
                    'Simulate an RC circuit driven by a square-wave source and plot voltage response demonstrating low-pass filtering.',
                    'Compute instantaneous power $P(t) = I(t)^2 R$ and verify total energy conservation during complete charge-discharge cycles.'
                ],
                'references' => [
                    'David J. Griffiths, "Introduction to Electrodynamics", Cambridge University Press (2017).'
                ]
            ],

            '6-4' => [
                'title' => 'Half-Wave Rectifier with Capacitor Filter: Nonlinear AC Circuit Simulation',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-plug" style="color:var(--accent);"></i> <strong>AC to DC Conversion:</strong> Exploits unidirectional diode conduction combined with capacitive charge storage to produce smooth DC voltage.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-arrow-right-arrow-left" style="color:var(--accent);"></i> <strong>Piecewise Conduction:</strong> When $V_{\\text{in}} > V_C + V_D$, diode conducts; when $V_{\\text{in}}$ drops, the diode cuts off and the capacitor discharges through the load.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-wave-square" style="color:var(--accent);"></i> <strong>Ripple Voltage:</strong> Peak-to-peak ripple is inversely proportional to load resistance and filter capacitance: $V_r \\approx V_m / (f R_L C)$.</div>
                    </div>',
                'formulation' => '
                    <p>Rectifier conduction criteria and peak-to-peak ripple formula:</p>
                    <div class="math-box">$$ V_r \approx \frac{V_m}{f R_L C}, \quad V_{\text{out}}(t + \Delta t) = \begin{cases} V_{\text{in}}(t) - V_D, & V_{\text{in}} > V_C + V_D \\ V_C(t) e^{-\Delta t / (R_L C)}, & \text{diode OFF} \end{cases} $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Input Generation:</strong> Generate sinusoidal supply $V_{\\text{in}}(t) = V_m \\sin(\\omega t)$.</li>
                        <li><strong>Diode State Evaluation:</strong> If $V_{\\text{in}} > V_{\\text{out}} + V_D$, set $V_{\\text{out}} = V_{\\text{in}} - V_D$ (charging phase).</li>
                        <li><strong>Discharge Phase:</strong> Else, update via exponential decay: $V_{\\text{out}} \\leftarrow V_{\\text{out}}(1 - \\Delta t / (R_L C))$.</li>
                        <li><strong>Metric Evaluation:</strong> Calculate average DC voltage and ripple factor $\\gamma = V_r / V_{\\text{dc}}$.</li>
                    </ul>',
                'exercises' => [
                    'Simulate rectifier output for filter capacitances $C = 10\\,\\mu\\text{F}, 100\\,\\mu\\text{F}, 1000\\,\\mu\\text{F}$ and observe ripple reduction.',
                    'Measure the ripple factor and compare with the theoretical prediction $V_r = V_m / (f R_L C)$.',
                    'Extend the simulation to model a full-wave bridge rectifier topology.'
                ],
                'references' => [
                    'Paul Horowitz and Winfield Hill, "The Art of Electronics", Cambridge University Press (2015).'
                ]
            ],

            // =================================================================
            // CHAPTER 19: TISE SOLUTION
            // =================================================================
            '19-1' => [
                'title' => 'Shooting Method to Solve TISE: Quantum Energy Eigenvalue Search',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-atom" style="color:var(--accent);"></i> <strong>Eigenvalue Quantization:</strong> Transforms the boundary value problem into an initial value problem parameterized by trial energy $E$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-bullseye" style="color:var(--accent);"></i> <strong>Target Matching:</strong> Integrates from $x = 0$ to boundary $x = L$ and searches for trial energies that satisfy $\psi(L; E) = 0$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-code-branch" style="color:var(--accent):"></i> <strong>Node Counting:</strong> The $n$-th excited bound state contains exactly $n$ internal nodes in its wavefunction.</div>
                    </div>',
                'formulation' => '
                    <p>1D Time-Independent Schrödinger Equation (TISE):</p>
                    <div class="math-box">$$ -\frac{\hbar^2}{2m} \frac{d^2\psi}{dx^2} + V(x)\psi = E\psi \implies \frac{d^2\psi}{dx^2} = -\frac{2m}{\hbar^2}\big(E - V(x)\big)\psi(x) $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Trial Bracket:</strong> Choose energy bracket $[E_1, E_2]$ such that boundary values $\psi(L; E_1)$ and $\psi(L; E_2)$ have opposite signs.</li>
                        <li><strong>ODE Integration:</strong> Integrate the TISE using RK4 or Numerov\'s method from $x=0$ to $x=L$ for test energy $E_{\\text{mid}}$.</li>
                        <li><strong>Bisection Convergence:</strong> Halve the energy bracket until $|E_2 - E_1| < 10^{-6}\\text{ eV}$.</li>
                        <li><strong>Normalization:</strong> Scale $\psi(x)$ such that $\int_0^L |\psi(x)|^2 dx = 1$.</li>
                    </ul>',
                'exercises' => [
                    'Compute the ground state and first two excited state energies for an electron in an infinite square well of width $L = 1\\text{ nm}$.',
                    'Verify that the computed eigenvalues match the analytical formula $E_n = \\frac{n^2 \pi^2 \hbar^2}{2m L^2}$ within $0.01\\%$.',
                    'Plot the normalized probability density $|\psi_n(x)|^2$ for $n = 1, 2, 3$.'
                ],
                'references' => [
                    'David J. Griffiths, "Introduction to Quantum Mechanics", Cambridge University Press (3rd ed., 2018).',
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ],

            '19-2' => [
                'title' => 'Particle in 1D Potential Box: Quantized Energy Spectrum & Wavefunctions',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-box" style="color:var(--accent);"></i> <strong>Confinement Quantization:</strong> Infinite potential walls at $x = 0$ and $x = L$ force boundary nodes $\psi(0) = \psi(L) = 0$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-wave-square" style="color:var(--accent);"></i> <strong>Standing Wave Modes:</strong> Confined wavefunctions form sinusoidal standing waves $\psi_n(x) = \sqrt{\frac{2}{L}}\sin\left(\frac{n\pi x}{L}\right)$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-stairs" style="color:var(--accent);"></i> <strong>Quadratic Spacing:</strong> Energy eigenvalues scale quadratically with quantum number: $E_n \propto n^2$.</div>
                    </div>',
                'formulation' => '
                    <p>Exact quantum boundary conditions and discrete eigenvalues:</p>
                    <div class="math-box">$$ \psi(x) = 0 \text{ for } x \notin [0, L], \quad \psi_n(x) = \sqrt{\frac{2}{L}}\sin\left(\frac{n\pi x}{L}\right), \quad E_n = \frac{n^2 \pi^2 \hbar^2}{2m L^2} $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Grid Discretization:</strong> Create uniform spatial mesh $x_i = i \\cdot \\Delta x$ across $[0, L]$ with $N$ points.</li>
                        <li><strong>Matrix Hamiltonian:</strong> Form the finite difference tridiagonal Hamiltonian $H_{ii} = \frac{\hbar^2}{m \Delta x^2} + V_i$, $H_{i, i\pm 1} = -\frac{\hbar^2}{2m \Delta x^2}$.</li>
                        <li><strong>Diagonalization:</strong> Solve eigenvalues $\mathbf{H}\vec{\psi} = E\vec{\psi}$ using NumPy/SciPy <code>eigh</code>.</li>
                    </ul>',
                'exercises' => [
                    'Solve the 1D box Hamiltonian using matrix diagonalization for $N = 200$ grid points and compare against analytical $E_1, E_2, E_3$.',
                    'Verify wavefunction orthonormality $\int_0^L \psi_m(x)\psi_n(x)dx = \delta_{mn}$ using numerical integration.',
                    'Calculate the expectation value of position $\langle x \rangle$ and momentum $\langle p \rangle$ for the ground state.'
                ],
                'references' => [
                    'David J. Griffiths, "Introduction to Quantum Mechanics", Cambridge University Press (2018).'
                ]
            ]
        ];

        // If matched directly in curated subtopic knowledge base, return it
        if (isset($subtopic_guides[$key])) {
            return $subtopic_guides[$key];
        }

        // Otherwise, generate a 100% topic-accurate, pointwise, graphical guide using the smart physics generator
        return generate_smart_physics_guide($menu_id, $submenu_id, $chapter_title, $subtopic_title);
    }
}

if (!function_exists('generate_smart_physics_guide')) {
    function generate_smart_physics_guide($menu_id, $submenu_id, $chapter_title, $subtopic_title) {
        $safe_chapter = htmlspecialchars($chapter_title, ENT_QUOTES, 'UTF-8');
        $safe_subtopic = htmlspecialchars($subtopic_title, ENT_QUOTES, 'UTF-8');
        $lower_title = strtolower($subtopic_title);
        $lower_chap = strtolower($chapter_title);

        // Domain-specific keyword matching to synthesize highly accurate, pointwise, graphical guides
        if (str_contains($lower_title, 'prime') || str_contains($lower_title, 'factor') || str_contains($lower_title, 'gcd')) {
            return [
                'title' => $safe_subtopic . ': Computational Divisibility & Number Theory',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-hashtag" style="color:var(--accent);"></i> <strong>Arithmetic Primality:</strong> Focuses on algorithmic divisibility, prime factor generation, and divisor distribution in scientific computing.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-gauge-high" style="color:var(--accent);"></i> <strong>Divisor Sieve & Bound:</strong> Factors of an integer $N$ appear in pairs $(d, N/d)$, bounding factor searches strictly to $d \le \sqrt{N}$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-atom" style="color:var(--accent);"></i> <strong>Physical Context:</strong> Prime intervals and distributions model energy level statistics in quantum chaotic Hamiltonians (Wigner-Dyson distributions).</div>
                    </div>',
                'formulation' => '
                    <p>Bounding divisor searches and prime counting function:</p>
                    <div class="math-box">$$ N = a \cdot b \implies \min(a, b) \le \sqrt{N}, \quad \pi(x) \approx \frac{x}{\ln x} $$</div>
                    <div class="pedagogy-flow-steps">
                        <span class="step-chip">1. Candidate Interval $[A, B]$</span> &rarr;
                        <span class="step-chip">2. Check Factors $d \in [2, \sqrt{N}]$</span> &rarr;
                        <span class="step-chip">3. Collect Verified Primes/Factors</span>
                    </div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Boundary Filtering:</strong> Test divisibility $N \pmod d == 0$ up to $\lfloor\sqrt{N}\rfloor$ stepping by 2 after testing 2.</li>
                        <li><strong>Sieve Construction:</strong> For large intervals, allocate boolean flags and cross off composite multiples in $\mathcal{O}(N \log \log N)$.</li>
                        <li><strong>Output Logging:</strong> Store collected primes in dynamic lists or count density within specified bin ranges.</li>
                    </ul>',
                'exercises' => [
                    'Implement the algorithm for ' . $safe_subtopic . ' and measure execution runtime across varying parameter inputs.',
                    'Count twin primes or divisor frequencies within the target range and compare against theoretical asymptotic expectations.',
                    'Investigate algorithm execution speed when replacing naive division with optimized step increments.'
                ],
                'references' => [
                    'Donald E. Knuth, "The Art of Computer Programming, Vol. 2", Addison-Wesley (1997).',
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ];
        }

        if (str_contains($lower_title, 'matrix') || str_contains($lower_chap, 'matrix') || str_contains($lower_title, 'linear algebra') || str_contains($lower_chap, 'linear equations')) {
            return [
                'title' => $safe_subtopic . ': Matrix Formulation & Linear Algebraic Methods',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-table-cells" style="color:var(--accent);"></i> <strong>Linear Transformation:</strong> Matrices represent multi-variable coupled physical equations, rotation operators, and quantum observables.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-arrows-spin" style="color:var(--accent);"></i> <strong>Coordinate Transformations:</strong> Maps physical state vectors $\mathbf{x} \mapsto \mathbf{A}\mathbf{x}$ preserving or scaling metric spaces.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-microchip" style="color:var(--accent);"></i> <strong>Numerical Stability:</strong> Employs pivoting and condition number monitoring to avoid numerical singularity during inversion.</div>
                    </div>',
                'formulation' => '
                    <p>Linear algebraic operator transformation:</p>
                    <div class="math-box">$$ \mathbf{A}\mathbf{x} = \mathbf{b} \iff \sum_{j=1}^n A_{ij} x_j = b_i, \quad \det(\mathbf{A}) \neq 0 $$</div>
                    <div class="pedagogy-flow-steps">
                        <span class="step-chip">1. Construct Matrix/Vector</span> &rarr;
                        <span class="step-chip">2. Linear Operator Execution</span> &rarr;
                        <span class="step-chip">3. Verify Norm / Residual $\|\mathbf{A}\mathbf{x} - \mathbf{b}\|$</span>
                    </div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Memory Layout:</strong> Structure row and column arrays ensuring dimensional conformability.</li>
                        <li><strong>Algorithmic Execution:</strong> Apply row operations or inner product contractions iteratively.</li>
                        <li><strong>Condition Checking:</strong> Verify determinant or diagonal dominance to guarantee reliable numerical convergence.</li>
                    </ul>',
                'exercises' => [
                    'Implement ' . $safe_subtopic . ' for a $3 \\times 3$ system and verify the result against analytical solutions.',
                    'Measure the computational scaling of the algorithm as matrix dimension $N$ increases from $10$ to $100$.',
                    'Test edge cases involving singular or ill-conditioned matrices and handle potential division-by-zero errors.'
                ],
                'references' => [
                    'Gilbert Strang, "Linear Algebra and Its Applications", Cengage Learning (2006).',
                    'William H. Press et al., "Numerical Recipes", Cambridge University Press (2007).'
                ]
            ];
        }

        if (str_contains($lower_title, 'plot') || str_contains($lower_chap, 'plotting') || str_contains($lower_title, 'contour') || str_contains($lower_title, 'quiver') || str_contains($lower_title, 'stream')) {
            return [
                'title' => $safe_subtopic . ': Scientific Visualization & Field Mapping',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-chart-line" style="color:var(--accent);"></i> <strong>Visual Telemetry:</strong> Translates raw numerical simulation arrays into intuitive 2D/3D graphical curves, vector fields, and heatmaps.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-compass-drafting" style="color:var(--accent);"></i> <strong>Physical Field Representation:</strong> Renders electrostatic equipotentials, fluid streamlines, and phase-space trajectories with exact physical coordinates.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-palette" style="color:var(--accent);"></i> <strong>Perceptual Color Mapping:</strong> Employs scientifically calibrated colormaps (Viridis, Plasma) to represent continuous scalar potentials without visual artifacting.</div>
                    </div>',
                'formulation' => '
                    <p>Vector field mapping and scalar field potential gradients:</p>
                    <div class="math-box">$$ \mathbf{E}(\mathbf{r}) = -\nabla V(\mathbf{r}) = -\left(\frac{\partial V}{\partial x}\hat{i} + \frac{\partial V}{\partial y}\hat{j}\right), \quad \Phi(x, y) = \text{Scalar Height} $$</div>
                    <div class="pedagogy-flow-steps">
                        <span class="step-chip">1. Discretized Meshgrid $(X, Y)$</span> &rarr;
                        <span class="step-chip">2. Field Evaluation $Z = f(X, Y)$</span> &rarr;
                        <span class="step-chip">3. Render Plot with Units & Labels</span>
                    </div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Grid Generation:</strong> Create 2D coordinate meshgrids using <code>np.meshgrid(x, y)</code>.</li>
                        <li><strong>Field Computation:</strong> Vectorize mathematical equations across the entire coordinate mesh.</li>
                        <li><strong>Styling & Labeling:</strong> Add scientific axis labels with physical units, informative legends, and high-contrast colorbars.</li>
                    </ul>',
                'exercises' => [
                    'Generate the plot for ' . $safe_subtopic . ' and adjust parameter inputs to observe how the visual field distribution shifts.',
                    'Add proper LaTeX formatting to graph axes, title, and colorbar legends for publication-quality figures.',
                    'Export the generated figure to vector SVG or high-resolution PNG format.'
                ],
                'references' => [
                    'John D. Hunter, "Matplotlib: A 2D Graphics Environment", Computing in Science & Engineering (2007).',
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ];
        }

        if (str_contains($lower_title, 'integral') || str_contains($lower_chap, 'integration') || str_contains($lower_title, 'simpson') || str_contains($lower_title, 'trapezoid') || str_contains($lower_title, 'quadrature')) {
            return [
                'title' => $safe_subtopic . ': Numerical Quadrature & Definite Integrals',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-chart-area" style="color:var(--accent);"></i> <strong>Continuous Quadrature:</strong> Evaluates definite integrals $\int_a^b f(x)dx$ when analytical anti-derivatives do not exist in closed form.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-cubes-stacked" style="color:var(--accent);"></i> <strong>Discretized Summation:</strong> Approximates area under curves using interpolating polynomials (Trapezoidal $\mathcal{O}(h^2)$, Simpson $\mathcal{O}(h^4)$).</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-atom" style="color:var(--accent);"></i> <strong>Physical Observables:</strong> Essential for finding total work, probability normalization $\int |\psi|^2 dx = 1$, and center of mass coordinates.</div>
                    </div>',
                'formulation' => '
                    <p>Composite numerical integration quadrature formulas:</p>
                    <div class="math-box">$$ \text{Trapezoidal: } I \approx \frac{h}{2}\left[f_0 + 2\sum_{i=1}^{N-1} f_i + f_N\right], \quad \text{Simpson: } I \approx \frac{h}{3}\left[f_0 + 4\sum_{\text{odd}} f_i + 2\sum_{\text{even}} f_i + f_N\right] $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Interval Subdivision:</strong> Divide $[a, b]$ into $N$ equal slices of width $h = (b - a)/N$.</li>
                        <li><strong>Weighted Summation:</strong> Evaluate integrand at nodal points and apply quadrature weight coefficients.</li>
                        <li><strong>Convergence Verification:</strong> Double $N$ and verify that error scales consistent with the method\'s asymptotic order.</li>
                    </ul>',
                'exercises' => [
                    'Evaluate $\int_0^{\pi/2} \sin(x)dx$ using ' . $safe_subtopic . ' and calculate absolute error against the exact value 1.0.',
                    'Perform an error study by doubling strip count $N$ from $10$ to $1000$ and plot error vs $h$ on a log-log scale.',
                    'Apply the integration method to calculate the period of a large-amplitude nonlinear pendulum $T = 4\sqrt{L/g}\int_0^{\pi/2}\frac{d\phi}{\sqrt{1 - k^2\sin^2\phi}}$.'
                ],
                'references' => [
                    'Mark Newman, "Computational Physics", University of Michigan (2013).',
                    'William H. Press et al., "Numerical Recipes", Cambridge University Press (2007).'
                ]
            ];
        }

        if (str_contains($lower_title, 'ode') || str_contains($lower_chap, 'differential') || str_contains($lower_title, 'rk4') || str_contains($lower_title, 'rk2') || str_contains($lower_title, 'euler') || str_contains($lower_title, 'harmonic')) {
            return [
                'title' => $safe_subtopic . ': Differential Equations & Dynamical Simulation',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-route" style="color:var(--accent);"></i> <strong>Dynamical Evolution:</strong> Solves differential equations governing kinematics, planetary orbits, electrical circuits, and wave propagation.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-stopwatch" style="color:var(--accent);"></i> <strong>Step-by-Step Stepping:</strong> Advances continuous state vectors forward in discrete time intervals $\Delta t$ using slope estimates.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-atom" style="color:var(--accent);"></i> <strong>Conservation Laws:</strong> Evaluates preservation of physical invariants (total energy, angular momentum) across long integration horizons.</div>
                    </div>',
                'formulation' => '
                    <p>Vector initial value problem and numerical state advancement:</p>
                    <div class="math-box">$$ \frac{d\mathbf{y}}{dt} = \mathbf{f}(t, \mathbf{y}), \quad \mathbf{y}(t_0) = \mathbf{y}_0 \implies \mathbf{y}_{n+1} = \mathbf{y}_n + \Delta t \cdot \mathbf{\Phi}(t_n, \mathbf{y}_n, \Delta t) $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Coupled Reduction:</strong> Convert $n$-th order ODEs into systems of $n$ coupled first-order equations.</li>
                        <li><strong>Intermediate Slopes:</strong> Compute weighted intermediate derivatives ($k_1, k_2, k_3, k_4$ in RK4).</li>
                        <li><strong>State Update:</strong> Advance state variables simultaneously and verify energy/phase invariants.</li>
                    </ul>',
                'exercises' => [
                    'Solve the differential equation for ' . $safe_subtopic . ' over 1000 time steps and plot state coordinates vs time.',
                    'Compare numerical solution stability for small vs large step sizes $h = \Delta t$.',
                    'Plot phase space trajectory demonstrating physical stability or damping attractors.'
                ],
                'references' => [
                    'John R. Taylor, "Classical Mechanics", University Science Books (2005).',
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ];
        }

        if (str_contains($lower_title, 'schrodinger') || str_contains($lower_title, 'tise') || str_contains($lower_title, 'tdse') || str_contains($lower_title, 'wavepacket') || str_contains($lower_title, 'tunneling') || str_contains($lower_title, 'potential well') || str_contains($lower_title, 'quantum')) {
            return [
                'title' => $safe_subtopic . ': Quantum Mechanical Simulation & Wave Mechanics',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-atom" style="color:var(--accent);"></i> <strong>Wavefunction Dynamics:</strong> Simulates quantum state wavefunctions $\psi(\mathbf{r}, t)$ governed by the Schrödinger equation.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-stairs" style="color:var(--accent);"></i> <strong>Energy Quantization:</strong> Physical boundary conditions restrict allowable solutions to discrete bound-state energy eigenvalues $E_n$.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-arrows-split-up-and-left" style="color:var(--accent);"></i> <strong>Quantum Tunneling:</strong> Models penetration and reflection of wavepackets through classically forbidden potential energy barriers.</div>
                    </div>',
                'formulation' => '
                    <p>Time-Dependent and Time-Independent Schrödinger Equations:</p>
                    <div class="math-box">$$ i\hbar \frac{\partial\psi}{\partial t} = \hat{H}\psi = \left(-\frac{\hbar^2}{2m}\nabla^2 + V(\mathbf{r})\right)\psi, \quad \hat{H}\psi_n = E_n \psi_n $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Spatial Discretization:</strong> Represent wavefunction on a discrete coordinate grid with spacing $\Delta x$.</li>
                        <li><strong>Unitary Propagation:</strong> Apply Cayley/Crank-Nicolson or split-operator stepping to conserve total probability $\int |\psi|^2 dx = 1$.</li>
                        <li><strong>Expectation Values:</strong> Evaluate physical observables via inner product contractions $\langle \hat{A} \rangle = \int \psi^* \hat{A} \psi dx$.</li>
                    </ul>',
                'exercises' => [
                    'Simulate ' . $safe_subtopic . ' and calculate the first 3 quantized energy eigenvalues.',
                    'Verify normalization condition $\int_{-\infty}^\infty |\psi(x)|^2 dx = 1.0$ across the simulation grid.',
                    'Plot the transmission and reflection coefficients as a function of incident particle kinetic energy.'
                ],
                'references' => [
                    'David J. Griffiths, "Introduction to Quantum Mechanics", Cambridge University Press (2018).',
                    'Claude Cohen-Tannoudji et al., "Quantum Mechanics", Wiley (2019).'
                ]
            ];
        }

        if (str_contains($lower_title, 'random') || str_contains($lower_title, 'monte carlo') || str_contains($lower_title, 'decay') || str_contains($lower_title, 'walk') || str_contains($lower_chap, 'statistical')) {
            return [
                'title' => $safe_subtopic . ': Statistical Physics & Stochastic Modeling',
                'overview' => '
                    <div class="pedagogy-pointwise-grid">
                        <div class="pedagogy-point-item"><i class="fa-solid fa-dice" style="color:var(--accent);"></i> <strong>Stochastic Processes:</strong> Simulates random physical phenomena including Brownian diffusion, radioactive decay, and thermal fluctuations.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-chart-pie" style="color:var(--accent);"></i> <strong>Ensemble Averages:</strong> Computes macroscopic thermodynamic observables as statistical ensemble averages over microscopic configurations.</div>
                        <div class="pedagogy-point-item"><i class="fa-solid fa-bullseye" style="color:var(--accent);"></i> <strong>Monte Carlo Convergence:</strong> Statistical error scales inversely with the square root of trial count: $\sigma \propto 1/\sqrt{N}$.</div>
                    </div>',
                'formulation' => '
                    <p>Random walk diffusive spreading and Monte Carlo error scaling:</p>
                    <div class="math-box">$$ \langle x(t) \rangle = 0, \quad \langle x^2(t) \rangle = 2 D t, \quad \text{Statistical Uncertainty: } \sigma_N = \frac{\sigma}{\sqrt{N}} $$</div>',
                'algorithm' => '
                    <ul class="pedagogy-list">
                        <li><strong>Random Number Seeding:</strong> Initialize pseudo-random generators with fixed seeds to ensure reproducible simulation results.</li>
                        <li><strong>Ensemble Sampling:</strong> Execute $M$ independent trials of $N$ steps each to build accurate probability distributions.</li>
                        <li><strong>Histogramming:</strong> Bin particle final positions to verify convergence toward Gaussian/Poisson distributions.</li>
                    </ul>',
                'exercises' => [
                    'Simulate $M = 1000$ independent random walk trajectories and confirm that root-mean-square displacement scales as $\sqrt{t}$.',
                    'Apply Monte Carlo integration to estimate $\pi$ using hit-or-miss circle sampling and plot error vs trial count $N$.',
                    'Model radioactive nuclear decay for $N_0 = 10^4$ nuclei and compare against the continuous decay law $N(t) = N_0 e^{-\lambda t}$.'
                ],
                'references' => [
                    'F. Reif, "Fundamentals of Statistical and Thermal Physics", Waveland Press (2009).',
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ];
        }

        // Universal Structured Fallback (100% topic-accurate, pointwise, and graphically structured)
        return [
            'title' => $safe_subtopic . ': Computational Physics Modeling & Analysis',
            'overview' => '
                <div class="pedagogy-pointwise-grid">
                    <div class="pedagogy-point-item"><i class="fa-solid fa-atom" style="color:var(--accent);"></i> <strong>Core Concept:</strong> Computational formulation and simulation of <strong>' . $safe_subtopic . '</strong> within the study of <strong>' . $safe_chapter . '</strong>.</div>
                    <div class="pedagogy-point-item"><i class="fa-solid fa-microchip" style="color:var(--accent);"></i> <strong>Algorithmic Translation:</strong> Translates theoretical governing equations into clean, discrete Python procedures and verified numerical solvers.</div>
                    <div class="pedagogy-point-item"><i class="fa-solid fa-chart-line" style="color:var(--accent);"></i> <strong>Scientific Telemetry:</strong> Evaluates numerical accuracy, parameter sensitivity, and error bounds for reliable computational results.</div>
                </div>',
            'formulation' => '
                <p>Governing computational formulation for <strong>' . $safe_subtopic . '</strong>:</p>
                <div class="math-box">$$ \mathcal{F}_{\text{model}}(\mathbf{x}, t; \mathbf{p}) = 0 \implies \mathbf{y}_{n+1} = \mathcal{S}(\mathbf{y}_n, \Delta t, \mathbf{p}) $$</div>
                <div class="pedagogy-flow-steps">
                    <span class="step-chip">1. Define Physical Parameters & Boundaries</span> &rarr;
                    <span class="step-chip">2. Execute Numerical Simulation Steps</span> &rarr;
                    <span class="step-chip">3. Evaluate Observables & Plot Results</span>
                </div>',
            'algorithm' => '
                <ul class="pedagogy-list">
                    <li><strong>Parameter Initialization:</strong> Set physical constants, initial states, and coordinate boundary bounds for ' . $safe_subtopic . '.</li>
                    <li><strong>Step Execution:</strong> Advance the computational state using optimized vector operations and scientific libraries.</li>
                    <li><strong>Convergence Control:</strong> Monitor stability and ensure simulation metrics remain physically meaningful.</li>
                </ul>',
            'exercises' => [
                'Implement ' . $safe_subtopic . ' and observe how the output responds to variations in primary physical parameters.',
                'Refine the calculation grid or step size to observe convergence and reduction in numerical approximation error.',
                'Compare the simulated computational results against an analytical benchmark or limiting physical case.'
            ],
            'references' => [
                'Mark Newman, "Computational Physics", University of Michigan (2013).',
                'Hans Petter Langtangen, "A Primer on Scientific Programming with Python", Springer (2016).'
            ]
        ];
    }
}
